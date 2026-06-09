<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\CommunicationSeeder;
use Database\Seeders\HRSeeder;
use Database\Seeders\ProcurementDefaultsSeeder;
use Database\Seeders\ProjectDefaultsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Go-live cleanup: wipe all demo / user-created business data while
 * KEEPING every real account and login. Built for the moment you stop
 * demoing CASI 360 and start using it for real, without losing the
 * accounts people already sign in with.
 *
 * PRESERVED (never wiped):
 *   - users  — all accounts and passwords are kept, EXCEPT the known
 *              demo seed accounts (see DEMO_SEED_EMAILS). The live admin
 *              daniel@casi.org is always protected and at least one
 *              super_admin is guaranteed to remain.
 *   - personal_access_tokens / sessions — kept for surviving users
 *              (orphans for removed demo users are cleaned up)
 *   - permissions, role_permissions, system_settings
 *   - help_articles (knowledge-base reference content)
 *   - the reference / lookup tables (departments, designations,
 *     leave_types, vendor_categories, budget_categories) — NOT wiped,
 *     only topped-up with their defaults so customisations survive.
 *   - migrations (Laravel internal)
 *
 * WIPED (demo / user-created data):
 *   - all procurement docs (BOQ / PR / RFQ / PO / GRN / Invoice / RFP,
 *     vendors, inventory, approvals, disbursements)
 *   - all project data (projects, budgets, donors, partners, team, notes)
 *   - HR employee records (rebuilt blank + relinked to each kept account)
 *   - HR notes and holidays
 *   - all communication data (forums, messages, notices, emails, sms)
 *   - programs data (beneficiaries) and support tickets
 *   - audit_logs and login_history
 *
 * AFTER THE WIPE the command:
 *   1. ensures the default reference data exists (HR, procurement,
 *      project defaults) without removing any custom rows,
 *   2. rebuilds a blank employee record for every surviving account,
 *   3. recreates the default communication scaffolding (General forum,
 *      per-department forums, welcome notice),
 *   4. writes a single 'system_data_wiped' entry to the now-empty audit
 *      log so the very first record in the fresh log is the wipe itself.
 *
 * Always dry-run first on live data:
 *   php artisan app:wipe-demo-data --dry-run
 *   php artisan app:wipe-demo-data --force
 */
class WipeDemoDataCommand extends Command
{
    protected $signature = 'app:wipe-demo-data
        {--force : Skip the confirmation prompt (for non-interactive server runs)}
        {--dry-run : Show exactly what would be wiped/removed without changing anything}';

    protected $description = 'Wipe demo/business data but keep all accounts + logins, ensure default categories, and log the wipe to a fresh audit log.';

    /**
     * Tables emptied completely. Foreign-key checks are disabled around
     * the wipe so order doesn't matter. Reference/lookup tables and
     * everything account/auth related are deliberately absent from this
     * list — they are preserved.
     */
    private const TABLES_TO_WIPE = [
        // ── Procurement ─────────────────────────────────────────────
        'requisition_audit_logs',
        'requisition_approvals',
        'requisition_items',
        'requisitions',
        'approval_steps',
        'disbursements',
        'purchase_order_items',
        'purchase_orders',
        'invoices',
        'rfp_items',
        'rfps',
        'rfq_items',
        'rfq_vendors',          // RFQ↔vendor pivot
        'rfqs',
        'grn_items',
        'grns',
        'boq_audit_logs',
        'boq_items',
        'boqs',
        'inventory_items',
        'vendors',

        // ── Projects ────────────────────────────────────────────────
        'project_team_members',
        'project_donors',
        'project_partners',
        'project_activities',
        'project_budget_lines',
        'project_notes',
        'projects',

        // ── HR (employees rebuilt blank afterwards) ─────────────────
        'notes',
        'employees',
        'holidays',

        // ── Communication (defaults recreated afterwards) ───────────
        'forum_messages',
        'forums',
        'messages',
        'notice_reads',
        'notice_audiences',
        'notices',
        'emails',
        'sms_messages',

        // ── Programs / support ──────────────────────────────────────
        'beneficiaries',
        'support_tickets',

        // ── Activity logs (a fresh wipe record is written after) ────
        'audit_logs',
        'login_history',
    ];

    /**
     * Known demo seed accounts created by UserSeeder and DemoDataSeeder
     * (`demo:seed`). These are the only user rows this command removes —
     * any account created through the app UI is left untouched.
     * daniel@casi.org is intentionally NOT here: it is the live admin and
     * is protected below.
     */
    private const DEMO_SEED_EMAILS = [
        // UserSeeder.php (daniel excluded — protected live admin)
        'grace@casi.org',
        'samuel@casi.org',
        'amina@casi.org',
        'chidi@casi.org',
        // DemoDataSeeder.php  (php artisan demo:seed)
        'demo.super1@demo.casi.org',
        'demo.super2@demo.casi.org',
        'demo.admin1@demo.casi.org',
        'demo.admin2@demo.casi.org',
        'demo.procmgr@demo.casi.org',
        'demo.finmgr@demo.casi.org',
        'demo.opsstaff@demo.casi.org',
        'demo.logstaff@demo.casi.org',
    ];

    /** Accounts that must always survive, whatever else happens. */
    private const PROTECTED_EMAILS = [
        'daniel@casi.org',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // ── Safety guards ───────────────────────────────────────────
        $liveAdmin = DB::table('users')->where('email', self::PROTECTED_EMAILS[0])->first();
        if (!$liveAdmin) {
            $this->error('Aborting: protected admin ' . self::PROTECTED_EMAILS[0] . ' does not exist. Refusing to wipe without a guaranteed login.');
            return self::FAILURE;
        }

        // Which demo accounts actually exist and aren't protected?
        $removableEmails = DB::table('users')
            ->whereIn('email', self::DEMO_SEED_EMAILS)
            ->whereNotIn('email', self::PROTECTED_EMAILS)
            ->pluck('email')
            ->toArray();

        // Guard: at least one super_admin must remain after removal.
        $superAdminsRemaining = DB::table('users')
            ->where('role', 'super_admin')
            ->whereNotIn('email', $removableEmails)
            ->count();
        if ($superAdminsRemaining === 0) {
            $this->error('Aborting: removing the demo accounts would leave zero super_admin users.');
            return self::FAILURE;
        }

        // ── Preview ─────────────────────────────────────────────────
        $this->newLine();
        $this->info($dryRun ? 'DRY RUN — nothing will be changed.' : 'Preparing to wipe demo data…');
        $this->newLine();

        $totalToWipe = 0;
        foreach (self::TABLES_TO_WIPE as $table) {
            if (!Schema::hasTable($table)) {
                $this->line("  [skip ] {$table} (no such table)");
                continue;
            }
            $count = DB::table($table)->count();
            $totalToWipe += $count;
            $this->line(sprintf('  [wipe ] %-26s %d rows', $table, $count));
        }

        $this->newLine();
        $this->line('Accounts to REMOVE (known demo seeds):');
        if ($removableEmails) {
            foreach ($removableEmails as $email) {
                $this->line("  - {$email}");
            }
        } else {
            $this->line('  (none present)');
        }
        $keptUsers = DB::table('users')->whereNotIn('email', $removableEmails)->count();
        $this->newLine();
        $this->line("Accounts to KEEP: {$keptUsers} (incl. protected " . self::PROTECTED_EMAILS[0] . ')');
        $this->line('Reference data preserved + topped-up: departments, designations, leave_types, vendor_categories, budget_categories.');

        if ($dryRun) {
            $this->newLine();
            $this->info("Dry run complete. Would wipe {$totalToWipe} business rows and remove " . count($removableEmails) . ' demo account(s).');
            $this->line('Re-run without --dry-run to apply.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn(' This permanently wipes the data listed above.');
            $this->warn(' Make sure you have a database backup first.');
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            if (!$this->confirm('Proceed with the wipe?', false)) {
                $this->info('Aborted. Nothing was changed.');
                return self::FAILURE;
            }
        }

        // ── Destructive section (atomic + FK checks off) ────────────
        $this->newLine();
        $this->info('Disabling foreign-key checks…');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $totalWiped = 0;
        try {
            DB::transaction(function () use (&$totalWiped, $removableEmails) {
                foreach (self::TABLES_TO_WIPE as $table) {
                    if (!Schema::hasTable($table)) {
                        continue;
                    }
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        DB::table($table)->delete();
                        $totalWiped += $count;
                        $this->line("  [wiped] {$count} rows from {$table}");
                    }
                }

                // Remove the demo seed accounts (protected emails excluded).
                if ($removableEmails) {
                    $deleted = DB::table('users')->whereIn('email', $removableEmails)->delete();
                    $this->line("  [wiped] {$deleted} demo account(s)");
                }

                // Clean up auth artefacts that pointed at removed users.
                $survivingIds = DB::table('users')->pluck('id')->toArray();

                if (Schema::hasTable('personal_access_tokens')) {
                    $orphanTokens = DB::table('personal_access_tokens')
                        ->where('tokenable_type', User::class)
                        ->whereNotIn('tokenable_id', $survivingIds)
                        ->delete();
                    if ($orphanTokens > 0) {
                        $this->line("  [wiped] {$orphanTokens} orphaned access token(s)");
                    }
                }

                if (Schema::hasTable('sessions')) {
                    $orphanSessions = DB::table('sessions')
                        ->whereNotNull('user_id')
                        ->whereNotIn('user_id', $survivingIds)
                        ->delete();
                    if ($orphanSessions > 0) {
                        $this->line("  [wiped] {$orphanSessions} orphaned session(s)");
                    }
                }

                if (Schema::hasTable('help_article_editors')) {
                    $orphanEditors = DB::table('help_article_editors')
                        ->whereNotIn('user_id', $survivingIds)
                        ->delete();
                    if ($orphanEditors > 0) {
                        $this->line("  [wiped] {$orphanEditors} orphaned help-article editor link(s)");
                    }
                }
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('Foreign-key checks re-enabled.');
        }

        $this->newLine();
        $this->info("Wipe complete. Business rows wiped: {$totalWiped}. Accounts kept: {$keptUsers}.");

        // ── Ensure default reference data (idempotent — keeps customs) ─
        $this->newLine();
        $this->info('Ensuring default reference data exists…');
        foreach ([HRSeeder::class, ProcurementDefaultsSeeder::class, ProjectDefaultsSeeder::class] as $seeder) {
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        // ── Rebuild a blank employee record for every surviving user ──
        $this->newLine();
        $this->info('Rebuilding employee records for surviving accounts…');
        $linked = 0;
        User::all()->each(function (User $user) use (&$linked) {
            $user->ensureEmployeeRecord();
            $linked++;
        });
        $this->line("  [ok] linked {$linked} account(s) to fresh employee records");

        // ── Recreate default communication scaffolding ───────────────
        $this->newLine();
        $this->info('Recreating default communication scaffolding…');
        $this->call('db:seed', ['--class' => CommunicationSeeder::class, '--force' => true]);

        // ── Stamp the fresh audit log with the wipe record ───────────
        $actorId = DB::table('users')->where('email', self::PROTECTED_EMAILS[0])->value('id')
            ?? DB::table('users')->where('role', 'super_admin')->value('id');

        AuditLog::create([
            'user_id'      => $actorId,
            'action'       => 'system_data_wiped',
            'entity_type'  => 'system',
            'entity_id'    => null,
            'old_values'   => null,
            'new_values'   => null,
            'ip_address'   => null,
            'user_agent'   => 'console:app:wipe-demo-data',
            'metadata'     => [
                'summary'               => 'Demo/business data wiped; accounts and logins retained.',
                'business_rows_wiped'   => $totalWiped,
                'demo_accounts_removed' => $removableEmails,
                'accounts_kept'         => $keptUsers,
                'tables_wiped'          => array_values(self::TABLES_TO_WIPE),
                'defaults_reseeded'     => [
                    'departments', 'designations', 'leave_types',
                    'vendor_categories', 'budget_categories',
                    'general_forum', 'department_forums', 'welcome_notice',
                ],
                'performed_at'          => now()->toISOString(),
            ],
        ]);
        $this->line('  [ok] wrote "system_data_wiped" as the first entry in the fresh audit log');

        $this->newLine();
        $this->info('All done. The system is ready to use immediately.');
        $this->line('Recommended next step:');
        $this->line('  • php artisan optimize:clear   (drop cached config / routes)');

        return self::SUCCESS;
    }
}
