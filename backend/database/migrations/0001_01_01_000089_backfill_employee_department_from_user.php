<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill employees.department_id from the linked user's department name.
 *
 * Employee rows that were auto-created from a user account (the
 * ensureEmployeeRecord hook) are left with department_id = null for HR to
 * fill in later. That made the HR Overview "Staff Count" per department read
 * 0 and grouped/filtered views miss those staff. Here we match each such
 * employee's linked user.department (a name) to a departments.name row
 * (case-insensitive) and set department_id.
 *
 * Safe + idempotent: only fills NULL department_id, never overwrites an
 * existing link; rows whose user department doesn't match any department are
 * left as-is (the API still shows the name via the user fallback).
 */
return new class extends Migration
{
    public function up(): void
    {
        // name (lowercased) => department id
        $byName = [];
        foreach (DB::table('departments')->get(['id', 'name']) as $d) {
            $byName[strtolower(trim((string) $d->name))] = $d->id;
        }
        if (empty($byName)) {
            return;
        }

        $rows = DB::table('employees')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->whereNull('employees.department_id')
            ->whereNotNull('users.department')
            ->where('users.department', '!=', '')
            ->select('employees.id as eid', 'users.department as dept')
            ->get();

        foreach ($rows as $r) {
            $key = strtolower(trim((string) $r->dept));
            if (isset($byName[$key])) {
                DB::table('employees')
                    ->where('id', $r->eid)
                    ->update(['department_id' => $byName[$key]]);
            }
        }
    }

    public function down(): void
    {
        // No-op: we can't know which department_id values were null before this
        // ran, so reversing would risk wiping legitimately-set links.
    }
};
