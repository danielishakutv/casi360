<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldKey = 'procurement.approvals.operations';
        $newKey = 'procurement.approvals.procurement';

        // If both rows exist (target was already seeded), merge role_permissions onto
        // the new row and drop the legacy one. Otherwise rename in place.
        $old = DB::table('permissions')->where('key', $oldKey)->first();
        $new = DB::table('permissions')->where('key', $newKey)->first();

        if ($old && $new) {
            // Move any role assignments on the old row to the new row (ignore conflicts)
            DB::statement(
                "UPDATE IGNORE role_permissions SET permission_id = ? WHERE permission_id = ?",
                [$new->id, $old->id]
            );
            DB::table('role_permissions')->where('permission_id', $old->id)->delete();
            DB::table('permissions')->where('id', $old->id)->delete();
        } elseif ($old) {
            DB::table('permissions')->where('id', $old->id)->update([
                'key'         => $newKey,
                'action'      => 'procurement',
                'description' => 'Stage 3 approval — Procurement Manager',
                'updated_at'  => now(),
            ]);
        }
        // If neither exists yet, the seeder will create the new key with run:once semantics.
    }

    public function down(): void
    {
        $newKey = 'procurement.approvals.procurement';
        $oldKey = 'procurement.approvals.operations';

        $new = DB::table('permissions')->where('key', $newKey)->first();
        if ($new) {
            DB::table('permissions')->where('id', $new->id)->update([
                'key'         => $oldKey,
                'action'      => 'operations',
                'description' => 'Stage 3 approval — Operations',
                'updated_at'  => now(),
            ]);
        }
    }
};
