<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 2 of personal-scope filtering: adds *.view_all permission keys for
 * the six procurement document lists (PR, PO, BOQ, RFQ, RFP, GRN). When a
 * user lacks the matching key, list endpoints scope results to records that
 * concern them (created/touched per audit_logs, named on the doc, or in
 * the same department as the requester).
 *
 * Idempotent: only inserts rows that don't yet exist. Safe to run on
 * production databases that may have been seeded already, and preserves any
 * super-admin overrides for these (role, permission) pairs.
 */
return new class extends Migration
{
    /**
     * key => default for [admin, manager, staff]
     * super_admin always bypasses permission checks (hardcoded), so it is
     * not stored in role_permissions.
     */
    private const PERMISSIONS = [
        'procurement.requisitions.view_all'    => [
            'feature'     => 'requisitions',
            'description' => 'See every purchase request in the organisation, not just records that concern this user',
        ],
        'procurement.purchase_orders.view_all' => [
            'feature'     => 'purchase_orders',
            'description' => 'See every purchase order in the organisation, not just records that concern this user',
        ],
        'procurement.boq.view_all'             => [
            'feature'     => 'boq',
            'description' => 'See every bill of quantities in the organisation, not just records that concern this user',
        ],
        'procurement.rfq.view_all'             => [
            'feature'     => 'rfq',
            'description' => 'See every request for quotation in the organisation, not just records that concern this user',
        ],
        'procurement.rfp.view_all'             => [
            'feature'     => 'rfp',
            'description' => 'See every request for payment in the organisation, not just records that concern this user',
        ],
        'procurement.grn.view_all'             => [
            'feature'     => 'grn',
            'description' => 'See every goods received note in the organisation, not just records that concern this user',
        ],
    ];

    /** Default role grants for each new key. */
    private const ROLE_DEFAULTS = [
        'admin'   => true,
        'manager' => false,
        'staff'   => false,
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $key => $meta) {
            $permissionId = DB::table('permissions')->where('key', $key)->value('id');

            if (!$permissionId) {
                $permissionId = (string) Str::uuid();
                DB::table('permissions')->insert([
                    'id'          => $permissionId,
                    'module'      => 'procurement',
                    'feature'     => $meta['feature'],
                    'action'      => 'view_all',
                    'key'         => $key,
                    'description' => $meta['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                DB::table('permissions')
                    ->where('id', $permissionId)
                    ->update(['description' => $meta['description'], 'updated_at' => now()]);
            }

            foreach (self::ROLE_DEFAULTS as $role => $allowed) {
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
