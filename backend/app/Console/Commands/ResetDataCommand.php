<?php

namespace App\Console\Commands;

use Database\Seeders\HRSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wipe all business data from the database, leaving the system in a
 * clean-slate state for end-to-end manual testing. Preserved by design:
 *
 *   - users with role='super_admin' (and their personal_access_tokens)
 *   - permissions table
 *   - role_permissions table
 *   - system_settings (so org name, branding, localization stay)
 *   - migrations table (Laravel internal)
 *
 * Wiped:
 *   - all procurement data (PR/RFQ/PO/GRN/Invoice/RFP, vendors, etc.)
 *   - all project data (projects, budgets, donors, partners, team)
 *   - all HR data (departments, designations, employees, notes,
 *     leave types, holidays)
 *   - all communication data (forums, messages, notices, emails, sms)
 *   - all programs data (beneficiaries)
 *   - audit_logs and login_history (so the activity feed is fresh)
 *   - sessions (so everyone has to sign back in)
 *   - non-super-admin users
 *
 * Refuses to run without --force in production unless the operator
 * confirms the warning prompt.
 *
 * Usage:
 *   php artisan app:reset-data
 *   php artisan app:reset-data --force
 */
class ResetDataCommand extends Command
{
    protected $signature = 'app:reset-data
        {--force : Skip the confirmation prompt}
        {--no-defaults : Skip re-seeding the HR defaults (departments + designations) after the wipe}';

    protected $description = 'Wipe all business data, keeping super_admin users + permissions + system_settings. Re-seeds HR defaults unless --no-defaults is passed.';

    /**
     * Tables wiped on reset, in any order — foreign-key checks are
     * disabled around the wipe so dependency order doesn't matter.
     * Tables we want to *preserve* (users, permissions, role_permissions,
     * system_settings, migrations) are deliberately NOT in this list.
     */
    private const TABLES_TO_WIPE = [
        // Procurement
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
        'rfqs',
        'grn_items',
        'grns',
        'boq_audit_logs',
        'boq_items',
        'boqs',
        'inventory_items',
        'vendor_categories',
        'vendors',

        // Projects
        'project_team_members',
        'project_donors',
        'project_partners',
        'project_activities',
        'project_budget_lines',
        'project_notes',
        'projects',
        'budget_categories',

        // HR
        'notes',
        'employees',
        'designations',
        'departments',
        'holidays',
        'leave_types',

        // Communication
        'forum_messages',
        'forums',
        'messages',
        'notice_reads',
        'notice_audiences',
        'notices',
        'emails',
        'sms_messages',

        // Programs
        'beneficiaries',

        // Help center
        'support_tickets',
        'help_articles',

        // System logs / transient (clean slate so activity feeds + audit logs are fresh)
        'audit_logs',
        'login_history',
        'sessions',
        'password_reset_tokens',
    ];

    public function handle(): int
    {
        $superAdminCount = DB::table('users')->where('role', 'super_admin')->count();

        if ($superAdminCount === 0) {
            $this->error('Aborting: no super_admin user exists. Refusing to wipe a database with no admin to log back in with.');
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn(' WARNING — this wipes ALL business data');
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
            $this->line("Will preserve:");
            $this->line("  • {$superAdminCount} super_admin user(s) and their tokens");
            $this->line('  • permissions, role_permissions, system_settings');
            $this->newLine();
            $this->line('Will wipe:');
            $this->line('  • all procurement, projects, HR, communication, programs data');
            $this->line('  • audit_logs, login_history, sessions');
            $this->line('  • all non-super-admin user accounts');
            $this->newLine();

            if (!$this->confirm('Proceed?')) {
                $this->info('Aborted.');
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Disabling foreign-key checks…');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $totalWiped = 0;

            foreach (self::TABLES_TO_WIPE as $table) {
                if (!Schema::hasTable($table)) {
                    $this->line("  [skip] {$table} (table does not exist)");
                    continue;
                }
                $count = DB::table($table)->count();
                if ($count === 0) {
                    $this->line("  [empty] {$table}");
                    continue;
                }
                DB::table($table)->delete();
                $totalWiped += $count;
                $this->line("  [wiped] {$count} rows from {$table}");
            }

            // Users: keep only super_admin rows
            $deletedUsers = DB::table('users')->where('role', '!=', 'super_admin')->delete();
            if ($deletedUsers > 0) {
                $this->line("  [wiped] {$deletedUsers} non-super-admin user(s)");
            }

            // Personal access tokens: drop any whose owning user is gone
            if (Schema::hasTable('personal_access_tokens')) {
                $remainingUserIds = DB::table('users')->pluck('id')->toArray();
                $orphanTokens = DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->whereNotIn('tokenable_id', $remainingUserIds)
                    ->delete();
                if ($orphanTokens > 0) {
                    $this->line("  [wiped] {$orphanTokens} orphaned access token(s)");
                }
            }

            $this->newLine();
            $this->info("Reset complete. Total business rows wiped: {$totalWiped}.");
            $this->info("Super admin user(s) preserved: {$superAdminCount}.");

            // ── Re-seed HR defaults so the system is testable immediately
            if (!$this->option('no-defaults')) {
                $this->newLine();
                $this->info('Re-seeding HR defaults (departments + designations)…');
                $this->call('db:seed', [
                    '--class' => HRSeeder::class,
                    '--force' => true,
                ]);
            }

            $this->newLine();
            $this->line('Recommended next steps:');
            $this->line('  • php artisan optimize:clear      (drop cached config / routes)');
            $this->line('  • Sign in as super admin and rebuild the org from scratch.');

            return self::SUCCESS;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
