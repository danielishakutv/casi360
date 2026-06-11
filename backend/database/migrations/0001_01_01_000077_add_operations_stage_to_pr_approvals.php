<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Adds Operations as the final (4th) stage of the Purchase Requisition
 * approval chain:  Budget Holder → Finance → Procurement → Operations.
 *
 *  1. Widens requisition_approvals.stage to include 'operations' (it had been
 *     renamed to 'procurement' in migration 000055; both now coexist — stage 3
 *     is Procurement, stage 4 is Operations).
 *  2. Adds the procurement.approvals.operations permission and grants it to
 *     manager (entitlement — runtime narrows to Operations-dept managers) and
 *     admin.
 *
 * In-flight PRs created before this migration keep their 3-stage chain; the
 * approval controller finalises them at the Procurement stage (legacy
 * fallback), so no rows are backfilled and no approval is disrupted.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE requisition_approvals MODIFY COLUMN stage "
            . "ENUM('budget_holder','finance','procurement','operations') NOT NULL"
        );

        $key = 'procurement.approvals.operations';
        $permissionId = DB::table('permissions')->where('key', $key)->value('id');

        if (!$permissionId) {
            $permissionId = (string) Str::uuid();
            DB::table('permissions')->insert([
                'id'          => $permissionId,
                'module'      => 'procurement',
                'feature'     => 'approvals',
                'action'      => 'operations',
                'key'         => $key,
                'description' => 'Act as Operations approver — final PR/BOQ/RFQ stage (Operations department manager)',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Entitlement grants; the ApprovalAuthorizer narrows managers to those
        // actually in the Operations department at runtime.
        foreach (['admin' => true, 'manager' => true] as $role => $allowed) {
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
        $permissionId = DB::table('permissions')->where('key', 'procurement.approvals.operations')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        // Re-point any operations rows to procurement before narrowing the enum
        // so existing data stays valid.
        DB::statement("UPDATE requisition_approvals SET stage = 'procurement' WHERE stage = 'operations'");
        DB::statement(
            "ALTER TABLE requisition_approvals MODIFY COLUMN stage "
            . "ENUM('budget_holder','finance','procurement') NOT NULL"
        );
    }
};
