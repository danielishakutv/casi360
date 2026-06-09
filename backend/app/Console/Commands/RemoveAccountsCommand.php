<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove one or more user accounts safely, by email and/or id.
 *
 * This is the "always use this" tool for deleting specific logins without
 * losing the business documents those people created. It never issues a
 * raw `DELETE FROM users` — instead it reconciles every reference first:
 *
 *   • CASCADE  — rows that belong to the account and are meaningless once
 *                it's gone (messages, forum posts, notices the user
 *                authored, their notice-read marks, project notes, and
 *                help-article editor links) are deleted with the account.
 *                This mirrors the schema's own cascadeOnDelete intent.
 *
 *   • DETACH   — authorship on SHARED records (POs, requisitions, invoices,
 *                approvals, emails/SMS, RFQs/GRNs, audit + login history,
 *                the HR employee link, …) is detached so the document
 *                survives. Nullable columns are set NULL; the handful of
 *                NOT-NULL author columns (notes.created_by,
 *                disbursements.disbursed_by, requisition/boq audit actor)
 *                are REASSIGNED to a surviving admin so nothing is lost and
 *                no foreign key is left dangling.
 *
 * Safety guards (all enforced before anything is touched):
 *   • daniel@casi.org is protected and can never be removed here.
 *   • At least one super_admin must remain afterwards, or it aborts.
 *   • It refuses to remove every account.
 *   • Always preview first:  php artisan app:remove-accounts a@x.org --dry-run
 *
 * Usage:
 *   php artisan app:remove-accounts jane@casi.org john@casi.org
 *   php artisan app:remove-accounts jane@casi.org --dry-run
 *   php artisan app:remove-accounts --id=9b1f… --id=4c2a… --force
 *   php artisan app:remove-accounts jane@casi.org --reassign-to=admin@casi.org
 *   php artisan app:remove-accounts jane@casi.org --delete-employee   # also drop HR record
 */
class RemoveAccountsCommand extends Command
{
    protected $signature = 'app:remove-accounts
        {emails?* : One or more account emails to remove}
        {--id=* : One or more account UUIDs to remove (in addition to any emails)}
        {--reassign-to= : Email of a surviving admin to inherit not-nullable authorship (default: protected admin)}
        {--delete-employee : Also delete the linked HR employee record (default: keep it, just unlink)}
        {--dry-run : Show exactly what would change without touching anything}
        {--force : Skip the confirmation prompt (for non-interactive server runs)}';

    protected $description = 'Safely remove specific user accounts by email/id, reconciling all references so no business data is lost.';

    /** Accounts that can never be removed by this command. */
    private const PROTECTED_EMAILS = ['daniel@casi.org'];

    /**
     * Rows that belong to the account and are deleted with it.
     * [table => [columns that hold the account id]].
     * Guarded by hasTable/hasColumn at runtime, so missing ones are skipped.
     */
    private const CASCADE = [
        'messages'             => ['sender_id', 'recipient_id'],
        'forum_messages'       => ['user_id'],
        'notices'              => ['author_id'],
        'notice_reads'         => ['user_id'],
        'project_notes'        => ['created_by'],
        'help_article_editors' => ['user_id'],
    ];

    /**
     * Authorship on shared records. Each column is detached: set NULL when
     * the column is nullable, otherwise reassigned to the fallback admin.
     * [table => [columns]]. Guarded by hasTable/hasColumn at runtime.
     * (employees.user_id is handled separately so --delete-employee works.)
     */
    private const DETACH = [
        'audit_logs'             => ['user_id'],
        'login_history'          => ['user_id'],
        'purchase_orders'        => ['submitted_by'],
        'requisitions'           => ['submitted_by', 'requested_by', 'budget_holder_id'],
        'requisition_approvals'  => ['actor_id'],
        'requisition_audit_logs' => ['actor_id'],
        'approval_steps'         => ['acted_by'],
        'boq_audit_logs'         => ['actor_id'],
        'rfqs'                   => ['created_by'],
        'grns'                   => ['created_by', 'confirmed_by'],
        'invoices'               => ['created_by', 'submitted_by', 'approved_by'],
        'notes'                  => ['created_by'],
        'disbursements'          => ['disbursed_by'],
        'emails'                 => ['sent_by'],
        'sms_messages'           => ['sent_by'],
        'support_tickets'        => ['user_id'],
        'help_article_editors'   => ['added_by'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // ── Resolve targets ─────────────────────────────────────────
        $emails = array_filter(array_map('trim', (array) $this->argument('emails')));
        $ids    = array_filter(array_map('trim', (array) $this->option('id')));

        if (!$emails && !$ids) {
            $this->error('Nothing to do: pass at least one email or --id=<uuid>.');
            $this->line('Example: php artisan app:remove-accounts jane@casi.org --dry-run');
            return self::FAILURE;
        }

        $query = DB::table('users');
        $query->where(function ($q) use ($emails, $ids) {
            if ($emails) {
                $q->orWhereIn('email', $emails);
            }
            if ($ids) {
                $q->orWhereIn('id', $ids);
            }
        });
        $targets = $query->get(['id', 'email', 'name', 'role']);

        // Report any requested emails/ids that don't exist.
        $foundEmails = $targets->pluck('email')->map(fn ($e) => strtolower($e))->all();
        foreach ($emails as $e) {
            if (!in_array(strtolower($e), $foundEmails, true)) {
                $this->warn("  [not found] {$e} — no such account, skipping.");
            }
        }
        $foundIds = $targets->pluck('id')->all();
        foreach ($ids as $id) {
            if (!in_array($id, $foundIds, true)) {
                $this->warn("  [not found] id={$id} — no such account, skipping.");
            }
        }

        // Drop protected accounts from the target set.
        $protected = $targets->filter(fn ($u) => in_array(strtolower($u->email), array_map('strtolower', self::PROTECTED_EMAILS), true));
        foreach ($protected as $u) {
            $this->warn("  [protected] {$u->email} is protected and will NOT be removed.");
        }
        $targets = $targets->reject(fn ($u) => in_array(strtolower($u->email), array_map('strtolower', self::PROTECTED_EMAILS), true))->values();

        if ($targets->isEmpty()) {
            $this->error('No removable accounts after applying protections. Nothing to do.');
            return self::FAILURE;
        }

        $targetIds = $targets->pluck('id')->all();

        // ── Guards ──────────────────────────────────────────────────
        $totalUsers = DB::table('users')->count();
        if (count($targetIds) >= $totalUsers) {
            $this->error('Aborting: that would remove every account. Refusing.');
            return self::FAILURE;
        }

        $superAdminsRemaining = DB::table('users')
            ->where('role', 'super_admin')
            ->whereNotIn('id', $targetIds)
            ->count();
        if ($superAdminsRemaining === 0) {
            $this->error('Aborting: that would leave zero super_admin accounts.');
            return self::FAILURE;
        }

        // Resolve the admin who inherits not-nullable authorship.
        $reassign = $this->resolveReassignTarget($targetIds);
        if ($reassign === null) {
            return self::FAILURE; // message already printed
        }

        // ── Preview ─────────────────────────────────────────────────
        $this->newLine();
        $this->info($dryRun ? 'DRY RUN — nothing will be changed.' : 'Preparing to remove account(s)…');
        $this->newLine();
        $this->line('Accounts to REMOVE:');
        foreach ($targets as $u) {
            $this->line(sprintf('  - %-32s (%s)', $u->email, $u->role));
        }
        $this->newLine();
        $this->line("Not-nullable authorship will be reassigned to: {$reassign->email}");
        $this->line($this->option('delete-employee')
            ? 'Linked HR employee records: WILL BE DELETED (--delete-employee).'
            : 'Linked HR employee records: kept (unlinked only).');

        // Reference scan.
        $this->newLine();
        $this->line('Reference reconciliation:');
        $cascadeTotal = 0;
        foreach (self::CASCADE as $table => $cols) {
            $n = $this->countRefs($table, $cols, $targetIds);
            if ($n > 0) {
                $cascadeTotal += $n;
                $this->line(sprintf('  [delete  ] %-24s %d row(s)', $table, $n));
            }
        }
        $detachTotal = $reassignTotal = 0;
        foreach (self::DETACH as $table => $cols) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($cols as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    continue;
                }
                $n = DB::table($table)->whereIn($col, $targetIds)->count();
                if ($n === 0) {
                    continue;
                }
                if ($this->isNullable($table, $col)) {
                    $detachTotal += $n;
                    $this->line(sprintf('  [null    ] %-24s %d row(s) (%s)', $table, $n, $col));
                } else {
                    $reassignTotal += $n;
                    $this->line(sprintf('  [reassign] %-24s %d row(s) (%s)', $table, $n, $col));
                }
            }
        }
        if ($cascadeTotal + $detachTotal + $reassignTotal === 0) {
            $this->line('  (these accounts have no referencing rows — clean removal)');
        }

        if ($dryRun) {
            $this->newLine();
            $this->info(sprintf(
                'Dry run complete. Would remove %d account(s): delete %d owned row(s), null %d, reassign %d.',
                count($targetIds), $cascadeTotal, $detachTotal, $reassignTotal
            ));
            $this->line('Re-run without --dry-run to apply.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn(' This permanently removes the account(s) above.');
            $this->warn(' Make sure you have a database backup first.');
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            if (!$this->confirm('Proceed with removal?', false)) {
                $this->info('Aborted. Nothing was changed.');
                return self::FAILURE;
            }
        }

        // ── Destructive section (atomic + FK checks off) ────────────
        $this->newLine();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::transaction(function () use ($targetIds, $reassign) {
                // 0. Notices authored by the removed users cascade to their
                //    audience + read children — but FK checks are off, so the
                //    DB won't do it for us. Clean the grandchildren by the
                //    soon-to-be-deleted notice ids first.
                if (Schema::hasTable('notices')) {
                    $noticeIds = DB::table('notices')->whereIn('author_id', $targetIds)->pluck('id')->all();
                    if ($noticeIds) {
                        foreach (['notice_reads', 'notice_audiences'] as $child) {
                            if (Schema::hasTable($child) && Schema::hasColumn($child, 'notice_id')) {
                                $n = DB::table($child)->whereIn('notice_id', $noticeIds)->delete();
                                if ($n > 0) {
                                    $this->line("  [deleted ] {$n} from {$child} (children of removed notices)");
                                }
                            }
                        }
                    }
                }

                // 1. Cascade-delete rows that belong to the account.
                foreach (self::CASCADE as $table => $cols) {
                    if (!Schema::hasTable($table)) {
                        continue;
                    }
                    $present = array_filter($cols, fn ($c) => Schema::hasColumn($table, $c));
                    if (!$present) {
                        continue;
                    }
                    $deleted = DB::table($table)->where(function ($q) use ($present, $targetIds) {
                        foreach ($present as $c) {
                            $q->orWhereIn($c, $targetIds);
                        }
                    })->delete();
                    if ($deleted > 0) {
                        $this->line("  [deleted ] {$deleted} from {$table}");
                    }
                }

                // 2. Detach authorship: null if possible, else reassign.
                foreach (self::DETACH as $table => $cols) {
                    if (!Schema::hasTable($table)) {
                        continue;
                    }
                    foreach ($cols as $col) {
                        if (!Schema::hasColumn($table, $col)) {
                            continue;
                        }
                        $value = $this->isNullable($table, $col) ? null : $reassign->id;
                        $changed = DB::table($table)->whereIn($col, $targetIds)->update([$col => $value]);
                        if ($changed > 0) {
                            $verb = $value === null ? 'nulled' : 'reassigned';
                            $this->line("  [{$verb}] {$changed} in {$table}.{$col}");
                        }
                    }
                }

                // 3. HR employee record: detach (default) or delete.
                if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'user_id')) {
                    if ($this->option('delete-employee')) {
                        $n = DB::table('employees')->whereIn('user_id', $targetIds)->delete();
                        if ($n > 0) {
                            $this->line("  [deleted ] {$n} employee record(s)");
                        }
                    } else {
                        $n = DB::table('employees')->whereIn('user_id', $targetIds)->update(['user_id' => null]);
                        if ($n > 0) {
                            $this->line("  [unlinked] {$n} employee record(s) (HR data kept)");
                        }
                    }
                }

                // 4. Auth artefacts.
                if (Schema::hasTable('personal_access_tokens')) {
                    $n = DB::table('personal_access_tokens')
                        ->where('tokenable_type', User::class)
                        ->whereIn('tokenable_id', $targetIds)
                        ->delete();
                    if ($n > 0) {
                        $this->line("  [deleted ] {$n} access token(s)");
                    }
                }
                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    $n = DB::table('sessions')->whereIn('user_id', $targetIds)->delete();
                    if ($n > 0) {
                        $this->line("  [deleted ] {$n} session(s)");
                    }
                }

                // 5. Finally remove the accounts.
                $removed = DB::table('users')->whereIn('id', $targetIds)->delete();
                $this->line("  [removed ] {$removed} account(s)");
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ── Audit trail ─────────────────────────────────────────────
        AuditLog::create([
            'user_id'     => $reassign->id,
            'action'      => 'accounts_removed',
            'entity_type' => 'user',
            'entity_id'   => null,
            'old_values'  => null,
            'new_values'  => null,
            'ip_address'  => null,
            'user_agent'  => 'console:app:remove-accounts',
            'metadata'    => [
                'summary'          => 'Specific account(s) removed via app:remove-accounts.',
                'removed'          => $targets->map(fn ($u) => ['id' => $u->id, 'email' => $u->email, 'role' => $u->role])->all(),
                'reassigned_to'    => ['id' => $reassign->id, 'email' => $reassign->email],
                'employee_records' => $this->option('delete-employee') ? 'deleted' : 'kept_unlinked',
                'performed_at'     => now()->toISOString(),
            ],
        ]);

        $this->newLine();
        $this->info(sprintf('Done. Removed %d account(s). Super admins remaining: %d.',
            count($targetIds), DB::table('users')->where('role', 'super_admin')->count()));
        $this->line('Tip: run  php artisan optimize:clear  if anything looks cached.');

        return self::SUCCESS;
    }

    /**
     * Resolve the surviving admin that inherits not-nullable authorship.
     * Prefers --reassign-to, then the protected admin, then any surviving
     * super_admin. Returns null (after printing an error) if none works.
     */
    private function resolveReassignTarget(array $targetIds): ?object
    {
        $wanted = trim((string) $this->option('reassign-to'));
        if ($wanted !== '') {
            $u = DB::table('users')->where('email', $wanted)->first(['id', 'email']);
            if (!$u) {
                $this->error("Aborting: --reassign-to {$wanted} does not exist.");
                return null;
            }
            if (in_array($u->id, $targetIds, true)) {
                $this->error("Aborting: --reassign-to {$wanted} is one of the accounts being removed.");
                return null;
            }
            return $u;
        }

        $protected = DB::table('users')
            ->where('email', self::PROTECTED_EMAILS[0])
            ->whereNotIn('id', $targetIds)
            ->first(['id', 'email']);
        if ($protected) {
            return $protected;
        }

        $admin = DB::table('users')
            ->where('role', 'super_admin')
            ->whereNotIn('id', $targetIds)
            ->first(['id', 'email']);
        if ($admin) {
            return $admin;
        }

        $this->error('Aborting: no surviving admin to inherit ownership. Pass --reassign-to=<email>.');
        return null;
    }

    /** Count referencing rows across several columns of one table. */
    private function countRefs(string $table, array $cols, array $targetIds): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }
        $present = array_filter($cols, fn ($c) => Schema::hasColumn($table, $c));
        if (!$present) {
            return 0;
        }
        return DB::table($table)->where(function ($q) use ($present, $targetIds) {
            foreach ($present as $c) {
                $q->orWhereIn($c, $targetIds);
            }
        })->count();
    }

    /** Whether a column is declared NULLable in the live schema. */
    private function isNullable(string $table, string $col): bool
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $col]
        );
        return $row !== null && strtoupper($row->IS_NULLABLE) === 'YES';
    }
}
