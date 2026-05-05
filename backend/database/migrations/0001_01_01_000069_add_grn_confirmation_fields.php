<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * GRN dual-confirmation: receiver records goods, budget holder (or any
 * confirmer) accepts, partially accepts, or rejects. Adds the audit
 * columns for the confirmation step plus the new permission key that
 * gates the confirm/reject action.
 *
 * Status flow:
 *   draft → pending_inspection (when receiver clicks Submit) → accepted | partial | rejected
 *
 * Idempotent. Safe to re-run.
 */
return new class extends Migration
{
    private const PERMISSION_KEY = 'procurement.grn.confirm';
    private const PERMISSION_DESC = 'Confirm, partially accept, or reject goods received notes (budget-holder stage)';
    private const ROLE_DEFAULTS = [
        'admin'   => true,
        'manager' => true,
        'staff'   => false,
    ];

    public function up(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            if (!Schema::hasColumn('grns', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('grns', 'confirmed_by')) {
                $table->foreignUuid('confirmed_by')->nullable()->after('submitted_at')
                      ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('grns', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            }
            if (!Schema::hasColumn('grns', 'confirmation_notes')) {
                $table->text('confirmation_notes')->nullable()->after('confirmed_at');
            }
        });

        // Seed the new permission + role grants. Idempotent.
        $key = self::PERMISSION_KEY;
        $permissionId = DB::table('permissions')->where('key', $key)->value('id');

        if (!$permissionId) {
            $permissionId = (string) Str::uuid();
            DB::table('permissions')->insert([
                'id'          => $permissionId,
                'module'      => 'procurement',
                'feature'     => 'grn',
                'action'      => 'confirm',
                'key'         => $key,
                'description' => self::PERMISSION_DESC,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            DB::table('permissions')->where('id', $permissionId)->update([
                'description' => self::PERMISSION_DESC,
                'updated_at'  => now(),
            ]);
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

    public function down(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            foreach (['confirmation_notes', 'confirmed_at', 'confirmed_by', 'submitted_at'] as $col) {
                if ($col === 'confirmed_by' && Schema::hasColumn('grns', $col)) {
                    $table->dropForeign([$col]);
                }
                if (Schema::hasColumn('grns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        $permissionId = DB::table('permissions')->where('key', self::PERMISSION_KEY)->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
