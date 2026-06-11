<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Makes Operations the BOQ approver.
 *
 * Grants the manager role the procurement.boq.approve *entitlement* so that
 * Operations managers/leads pass the route guard; the BoqController +
 * pending-approvals queue then narrow the actual right to act to admins and
 * Operations-department managers (ApprovalAuthorizer::isOperationsApprover) —
 * the same "entitlement granted to all managers, narrowed by department at
 * runtime" pattern the PR approval stages use.
 *
 * Idempotent: updates the existing manager row to allowed=true, or inserts it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setManager(true);
    }

    public function down(): void
    {
        $this->setManager(false);
    }

    private function setManager(bool $allowed): void
    {
        $permissionId = DB::table('permissions')->where('key', 'procurement.boq.approve')->value('id');
        if (!$permissionId) {
            return;
        }

        $existing = DB::table('role_permissions')
            ->where('role', 'manager')
            ->where('permission_id', $permissionId)
            ->first();

        if ($existing) {
            DB::table('role_permissions')
                ->where('id', $existing->id)
                ->update(['allowed' => $allowed, 'updated_at' => now()]);
        } else {
            DB::table('role_permissions')->insert([
                'id'            => (string) Str::uuid(),
                'role'          => 'manager',
                'permission_id' => $permissionId,
                'allowed'       => $allowed,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
};
