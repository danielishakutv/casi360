<?php

namespace App\Console\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared logic for safely deleting HR employee records without losing the
 * business documents that reference them. Used by both
 * app:remove-employees (delete HR records directly) and
 * app:remove-accounts (drop the linked HR record when an account is
 * removed), so the two can never drift apart.
 *
 * The host class must be an Illuminate console Command (it calls
 * $this->line / $this->warn) and is responsible for disabling foreign-key
 * checks + wrapping the call in a transaction.
 */
trait ReconcilesEmployeeReferences
{
    /** Rows that belong to an employee and are deleted with them. */
    private function employeeCascadeMap(): array
    {
        return [
            'notes'                => ['employee_id'],         // HR notes about the person
            'project_team_members' => ['employee_id'],         // their project-team memberships
        ];
    }

    /**
     * Employee references on shared records: NULL when the column allows
     * it, otherwise REASSIGN to a surviving employee so the document and
     * its foreign key stay intact.
     */
    private function employeeDetachMap(): array
    {
        return [
            'projects'        => ['project_manager_id'],  // nullable -> null
            'requisitions'    => ['budget_holder_id'],    // nullable -> null
            'purchase_orders' => ['requested_by'],        // NOT NULL  -> reassign
        ];
    }

    /**
     * Reconcile every reference to $employeeIds, then delete those employee
     * rows. Foreign-key checks must already be OFF and a transaction open
     * in the caller. $reassignEmployeeId inherits not-nullable references;
     * pass null if you know none are needed (post-wipe).
     *
     * @return array{deleted:int,nulled:int,reassigned:int,employees_removed:int}
     */
    protected function reconcileAndDeleteEmployees(array $employeeIds, ?string $reassignEmployeeId): array
    {
        $deleted = $nulled = $reassigned = $removed = 0;
        if (!$employeeIds) {
            return compact('deleted', 'nulled', 'reassigned') + ['employees_removed' => 0];
        }

        // 1. Cascade-delete rows that belong to the employee.
        foreach ($this->employeeCascadeMap() as $table => $cols) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $present = array_filter($cols, fn ($c) => Schema::hasColumn($table, $c));
            if (!$present) {
                continue;
            }
            $n = DB::table($table)->where(function ($q) use ($present, $employeeIds) {
                foreach ($present as $c) {
                    $q->orWhereIn($c, $employeeIds);
                }
            })->delete();
            if ($n > 0) {
                $deleted += $n;
                $this->line("  [deleted ] {$n} from {$table} (belongs to employee)");
            }
        }

        // 2. Detach authorship: null if possible, else reassign.
        foreach ($this->employeeDetachMap() as $table => $cols) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($cols as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    continue;
                }
                $nullable = $this->columnIsNullable($table, $col);
                if (!$nullable && $reassignEmployeeId === null) {
                    $cnt = DB::table($table)->whereIn($col, $employeeIds)->count();
                    if ($cnt > 0) {
                        $this->warn("  [orphan  ] {$cnt} in {$table}.{$col} left dangling (no reassign target — pass --reassign-to)");
                    }
                    continue;
                }
                $value = $nullable ? null : $reassignEmployeeId;
                $n = DB::table($table)->whereIn($col, $employeeIds)->update([$col => $value]);
                if ($n > 0) {
                    if ($value === null) {
                        $nulled += $n;
                        $this->line("  [nulled  ] {$n} in {$table}.{$col}");
                    } else {
                        $reassigned += $n;
                        $this->line("  [reassign] {$n} in {$table}.{$col}");
                    }
                }
            }
        }

        // 3. Remove the employee rows themselves.
        $removed = DB::table('employees')->whereIn('id', $employeeIds)->delete();
        if ($removed > 0) {
            $this->line("  [removed ] {$removed} employee record(s)");
        }

        return ['deleted' => $deleted, 'nulled' => $nulled, 'reassigned' => $reassigned, 'employees_removed' => $removed];
    }

    /** Whether a column is declared NULLable in the live schema. */
    protected function columnIsNullable(string $table, string $col): bool
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $col]
        );
        return $row !== null && strtoupper($row->IS_NULLABLE) === 'YES';
    }
}
