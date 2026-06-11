<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lets every department raise a Bill of Quantities.
 *
 * Staff could already create Purchase Requests; this extends the same
 * "anyone can raise the document, the approval chain routes it" principle to
 * BOQs by granting staff procurement.boq.create + procurement.boq.edit.
 * Visibility of other departments' BOQs is still governed by the
 * procurement.boq.view_all permission (staff = own/department only).
 *
 * Idempotent: updates the existing staff rows to allowed=true, or inserts them
 * if missing.
 */
return new class extends Migration
{
    private const KEYS = ['procurement.boq.create', 'procurement.boq.edit'];

    public function up(): void
    {
        $this->setStaff(true);
    }

    public function down(): void
    {
        $this->setStaff(false);
    }

    private function setStaff(bool $allowed): void
    {
        foreach (self::KEYS as $key) {
            $permissionId = DB::table('permissions')->where('key', $key)->value('id');
            if (!$permissionId) {
                continue;
            }

            $existing = DB::table('role_permissions')
                ->where('role', 'staff')
                ->where('permission_id', $permissionId)
                ->first();

            if ($existing) {
                DB::table('role_permissions')
                    ->where('id', $existing->id)
                    ->update(['allowed' => $allowed, 'updated_at' => now()]);
            } else {
                DB::table('role_permissions')->insert([
                    'id'            => (string) Str::uuid(),
                    'role'          => 'staff',
                    'permission_id' => $permissionId,
                    'allowed'       => $allowed,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
};
