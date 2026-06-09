<?php

namespace App\Console\Commands;

use App\Console\Concerns\ReconcilesEmployeeReferences;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove HR employee records from the staff list, by email, staff id
 * (e.g. CASI-1003), and/or --id. This is the companion to
 * app:remove-accounts for the HR side: use it to clean up employee
 * records whose login was already removed, or to delete a staff record
 * that never had an account.
 *
 * Like the account tool it reconciles references first so no business
 * document is lost: HR notes + project-team memberships that belong to
 * the person are deleted with them; nullable references (project manager,
 * requisition budget holder) are detached; the NOT-NULL purchase-order
 * "requested_by" is reassigned to a surviving employee.
 *
 * Guards: the employee linked to (or matching) daniel@casi.org is
 * protected, and it refuses to remove every employee.
 *
 * Usage:
 *   php artisan app:remove-employees jane@casi.org CASI-1003 --dry-run
 *   php artisan app:remove-employees jane@casi.org --force
 *   php artisan app:remove-employees --id=9b1f… --reassign-to=admin@casi.org --force
 */
class RemoveEmployeesCommand extends Command
{
    use ReconcilesEmployeeReferences;

    protected $signature = 'app:remove-employees
        {identifiers?* : Emails or staff IDs (e.g. CASI-1003) of employees to remove}
        {--id=* : Employee UUIDs to remove (in addition to any identifiers)}
        {--reassign-to= : Email or staff ID of a surviving employee to inherit not-nullable references}
        {--dry-run : Show exactly what would change without touching anything}
        {--force : Skip the confirmation prompt (for non-interactive server runs)}';

    protected $description = 'Safely remove HR employee records by email / staff id / uuid, reconciling references so no business data is lost.';

    /** The person behind this account/email can never be removed here. */
    private const PROTECTED_EMAILS = ['daniel@casi.org'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $identifiers = array_filter(array_map('trim', (array) $this->argument('identifiers')));
        $ids         = array_filter(array_map('trim', (array) $this->option('id')));

        if (!$identifiers && !$ids) {
            $this->error('Nothing to do: pass at least one email, staff id, or --id=<uuid>.');
            $this->line('Example: php artisan app:remove-employees jane@casi.org CASI-1003 --dry-run');
            return self::FAILURE;
        }

        // Resolve targets by email OR staff_id (identifiers) plus explicit ids.
        $targets = DB::table('employees')
            ->where(function ($q) use ($identifiers, $ids) {
                if ($identifiers) {
                    $q->orWhereIn('email', $identifiers)
                      ->orWhereIn('staff_id', $identifiers);
                }
                if ($ids) {
                    $q->orWhereIn('id', $ids);
                }
            })
            ->get(['id', 'staff_id', 'name', 'email', 'user_id']);

        // Report identifiers / ids that matched nothing.
        $matched = $targets->flatMap(fn ($e) => [strtolower((string) $e->email), strtolower((string) $e->staff_id)])->all();
        foreach ($identifiers as $i) {
            if (!in_array(strtolower($i), $matched, true)) {
                $this->warn("  [not found] {$i} — no employee with that email or staff id, skipping.");
            }
        }
        $matchedIds = $targets->pluck('id')->all();
        foreach ($ids as $id) {
            if (!in_array($id, $matchedIds, true)) {
                $this->warn("  [not found] id={$id} — no such employee, skipping.");
            }
        }

        // Protect the admin's own HR record (by email or linked account).
        $protectedUserIds = DB::table('users')->whereIn('email', self::PROTECTED_EMAILS)->pluck('id')->all();
        $protected = $targets->filter(function ($e) use ($protectedUserIds) {
            return in_array(strtolower((string) $e->email), array_map('strtolower', self::PROTECTED_EMAILS), true)
                || ($e->user_id !== null && in_array($e->user_id, $protectedUserIds, true));
        });
        foreach ($protected as $e) {
            $this->warn("  [protected] {$e->name} ({$e->email}) is protected and will NOT be removed.");
        }
        $targets = $targets->reject(fn ($e) => $protected->contains('id', $e->id))->values();

        if ($targets->isEmpty()) {
            $this->error('No removable employees after applying protections. Nothing to do.');
            return self::FAILURE;
        }

        $targetIds  = $targets->pluck('id')->all();
        $totalEmps  = DB::table('employees')->count();
        if (count($targetIds) >= $totalEmps) {
            $this->error('Aborting: that would remove every employee record. Refusing.');
            return self::FAILURE;
        }

        // Resolve the surviving employee that inherits not-nullable refs.
        $reassignEmployeeId = $this->resolveReassignEmployee($targetIds);

        // ── Preview ─────────────────────────────────────────────────
        $this->newLine();
        $this->info($dryRun ? 'DRY RUN — nothing will be changed.' : 'Preparing to remove employee record(s)…');
        $this->newLine();
        $this->line('Employees to REMOVE:');
        foreach ($targets as $e) {
            $this->line(sprintf('  - %-12s %-22s %s', $e->staff_id, $e->name, $e->email));
        }
        $this->newLine();
        $this->line($reassignEmployeeId
            ? 'Not-nullable references will be reassigned to employee id ' . $reassignEmployeeId
            : 'No reassign target resolved — not-nullable references (if any) will be reported, not changed.');

        $this->newLine();
        $this->line('Reference reconciliation:');
        $any = false;
        foreach ($this->employeeCascadeMap() as $table => $cols) {
            $n = $this->countEmpRefs($table, $cols, $targetIds);
            if ($n > 0) {
                $any = true;
                $this->line(sprintf('  [delete  ] %-22s %d row(s)', $table, $n));
            }
        }
        foreach ($this->employeeDetachMap() as $table => $cols) {
            foreach ($cols as $col) {
                $n = $this->countEmpRefs($table, [$col], $targetIds);
                if ($n > 0) {
                    $any = true;
                    $mode = $this->columnIsNullableSafe($table, $col) ? 'null    ' : 'reassign';
                    $this->line(sprintf('  [%s] %-22s %d row(s) (%s)', $mode, $table, $n, $col));
                }
            }
        }
        if (!$any) {
            $this->line('  (these employees have no referencing rows — clean removal)');
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run complete. Would remove ' . count($targetIds) . ' employee record(s).');
            $this->line('Re-run without --dry-run to apply.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->warn(' This permanently removes the employee record(s) above.');
            $this->warn(' Make sure you have a database backup first.');
            $this->warn('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            if (!$this->confirm('Proceed with removal?', false)) {
                $this->info('Aborted. Nothing was changed.');
                return self::FAILURE;
            }
        }

        $this->newLine();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $summary = ['deleted' => 0, 'nulled' => 0, 'reassigned' => 0, 'employees_removed' => 0];
        try {
            DB::transaction(function () use ($targetIds, $reassignEmployeeId, &$summary) {
                $summary = $this->reconcileAndDeleteEmployees($targetIds, $reassignEmployeeId);
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ── Audit trail ─────────────────────────────────────────────
        $actorId = DB::table('users')->whereIn('email', self::PROTECTED_EMAILS)->value('id')
            ?? DB::table('users')->where('role', 'super_admin')->value('id');
        AuditLog::create([
            'user_id'     => $actorId,
            'action'      => 'employees_removed',
            'entity_type' => 'employee',
            'entity_id'   => null,
            'old_values'  => null,
            'new_values'  => null,
            'ip_address'  => null,
            'user_agent'  => 'console:app:remove-employees',
            'metadata'    => [
                'summary'      => 'HR employee record(s) removed via app:remove-employees.',
                'removed'      => $targets->map(fn ($e) => ['id' => $e->id, 'staff_id' => $e->staff_id, 'name' => $e->name, 'email' => $e->email])->all(),
                'counts'       => $summary,
                'performed_at' => now()->toISOString(),
            ],
        ]);

        $this->newLine();
        $this->info('Done. Employee records removed: ' . $summary['employees_removed'] . '.');
        return self::SUCCESS;
    }

    /** Resolve the surviving employee that inherits not-nullable refs. */
    private function resolveReassignEmployee(array $targetIds): ?string
    {
        $wanted = trim((string) $this->option('reassign-to'));
        if ($wanted !== '') {
            $emp = DB::table('employees')
                ->where(fn ($q) => $q->where('email', $wanted)->orWhere('staff_id', $wanted))
                ->whereNotIn('id', $targetIds)
                ->value('id');
            if (!$emp) {
                $this->warn("  [warn] --reassign-to {$wanted} not found among surviving employees; not-nullable refs will be reported only.");
            }
            return $emp ?: null;
        }

        // Default: the protected admin's employee record.
        $adminUserId = DB::table('users')->whereIn('email', self::PROTECTED_EMAILS)->value('id');
        $emp = DB::table('employees')
            ->where(function ($q) use ($adminUserId) {
                $q->whereIn('email', self::PROTECTED_EMAILS);
                if ($adminUserId) {
                    $q->orWhere('user_id', $adminUserId);
                }
            })
            ->whereNotIn('id', $targetIds)
            ->value('id');
        return $emp ?: null;
    }

    private function countEmpRefs(string $table, array $cols, array $targetIds): int
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

    /** Nullability check that tolerates a missing table/column in preview. */
    private function columnIsNullableSafe(string $table, string $col): bool
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) {
            return true;
        }
        return $this->columnIsNullable($table, $col);
    }
}
