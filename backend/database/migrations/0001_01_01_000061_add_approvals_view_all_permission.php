<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Adds the procurement.approvals.view_all permission used to gate org-wide
 * approval history. When a user lacks this permission, the pending-approvals
 * endpoint scopes the history feed to records that concern that user.
 *
 * Idempotent: safe to run on production databases that may have been seeded
 * already. Only inserts rows that do not yet exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        $key         = 'procurement.approvals.view_all';
        $description = 'See approval history for the whole organisation, not just records that concern this user';

        $permissionId = DB::table('permissions')->where('key', $key)->value('id');

        if (!$permissionId) {
            // Match the existing permissions table convention (UUID primary key).
            $permissionId = (string) Str::uuid();
            DB::table('permissions')->insert([
                'id'          => $permissionId,
                'module'      => 'procurement',
                'feature'     => 'approvals',
                'action'      => 'view_all',
                'key'         => $key,
                'description' => $description,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            // Keep description in sync if a previous run created the row with stale text.
            DB::table('permissions')
                ->where('id', $permissionId)
                ->update(['description' => $description, 'updated_at' => now()]);
        }

        // Role defaults — only inserted when the (role, permission) pair is missing
        // so any super-admin overrides made in production are preserved.
        $defaults = [
            'admin'   => true,
            'manager' => false,
            'staff'   => false,
        ];

        foreach ($defaults as $role => $allowed) {
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

    public function down(): void
    {
        $key = 'procurement.approvals.view_all';
        $permissionId = DB::table('permissions')->where('key', $key)->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
