<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Permissions for the attendance / timesheet feature.
 *
 *   hr.attendance.view      — view attendance lists (own records if not view_all)
 *   hr.attendance.view_all  — see every staff member's attendance + timesheets
 *                             (the HR dashboard "all staff" view)
 *   hr.attendance.manage    — adjust/correct attendance records (HR)
 *
 * Clock in/out itself needs no permission — it is self-service for the
 * authenticated user. Idempotent.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'hr.attendance.view'     => 'View attendance records',
        'hr.attendance.view_all' => 'View attendance and monthly timesheets for all staff',
        'hr.attendance.manage'   => 'Adjust or correct attendance records',
    ];

    /** key => [admin, country_director, manager, staff] */
    private const GRANTS = [
        'hr.attendance.view'     => ['admin' => true, 'country_director' => true, 'manager' => true, 'staff' => true],
        'hr.attendance.view_all' => ['admin' => true, 'country_director' => true, 'manager' => true, 'staff' => false],
        'hr.attendance.manage'   => ['admin' => true, 'country_director' => false, 'manager' => true, 'staff' => false],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $key => $description) {
            [$module, $feature, $action] = explode('.', $key);

            $permissionId = DB::table('permissions')->where('key', $key)->value('id');
            if (!$permissionId) {
                $permissionId = (string) Str::uuid();
                DB::table('permissions')->insert([
                    'id'          => $permissionId,
                    'module'      => $module,
                    'feature'     => $feature,
                    'action'      => $action,
                    'key'         => $key,
                    'description' => $description,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            foreach (self::GRANTS[$key] as $role => $allowed) {
                $exists = DB::table('role_permissions')
                    ->where('role', $role)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'id'            => (string) Str::uuid(),
                        'role'          => $role,
                        'permission_id' => $permissionId,
                        'allowed'       => $allowed,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $key) {
            $permissionId = DB::table('permissions')->where('key', $key)->value('id');
            if ($permissionId) {
                DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
                DB::table('permissions')->where('id', $permissionId)->delete();
            }
        }
    }
};
