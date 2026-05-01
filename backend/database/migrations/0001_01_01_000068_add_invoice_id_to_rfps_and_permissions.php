<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Wires the new Invoice document into the rest of the system:
 *
 *   1. Adds nullable invoice_id to rfps so a payment request references
 *      the invoice it pays. Nullable for backward compatibility with
 *      existing RFPs created before invoices existed; new RFPs validate
 *      invoice_id at the request layer.
 *
 *   2. Seeds six new permission keys (procurement.invoices.{view, create,
 *      edit, delete, approve, view_all}) and the role defaults that match
 *      the existing procurement workflow conventions:
 *        - admin    : everything true
 *        - manager  : view/create/edit/approve true, delete + view_all false
 *        - staff    : view false (no access to invoices for MVP)
 *        - super_admin always bypasses permission checks
 *
 * Idempotent and value-preserving: column add is guarded with
 * Schema::hasColumn, and permission inserts only happen when the row
 * does not already exist, so any super-admin override in production is
 * preserved.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'procurement.invoices.view'     => 'View vendor invoices list and details',
        'procurement.invoices.create'   => 'Record new vendor invoices',
        'procurement.invoices.edit'     => 'Edit vendor invoice details (while pending)',
        'procurement.invoices.delete'   => 'Cancel vendor invoices',
        'procurement.invoices.approve'  => 'Approve, reject, or revise vendor invoices (Finance stage)',
        'procurement.invoices.view_all' => 'See every vendor invoice in the organisation, not just records that concern this user',
    ];

    private const ROLE_DEFAULTS = [
        'admin'   => [
            'procurement.invoices.view'     => true,
            'procurement.invoices.create'   => true,
            'procurement.invoices.edit'     => true,
            'procurement.invoices.delete'   => true,
            'procurement.invoices.approve'  => true,
            'procurement.invoices.view_all' => true,
        ],
        'manager' => [
            'procurement.invoices.view'     => true,
            'procurement.invoices.create'   => true,
            'procurement.invoices.edit'     => true,
            'procurement.invoices.delete'   => false,
            'procurement.invoices.approve'  => true,
            'procurement.invoices.view_all' => false,
        ],
        'staff'   => [
            'procurement.invoices.view'     => false,
            'procurement.invoices.create'   => false,
            'procurement.invoices.edit'     => false,
            'procurement.invoices.delete'   => false,
            'procurement.invoices.approve'  => false,
            'procurement.invoices.view_all' => false,
        ],
    ];

    public function up(): void
    {
        // 1. Add invoice_id to rfps if it isn't already there
        if (!Schema::hasColumn('rfps', 'invoice_id')) {
            Schema::table('rfps', function (Blueprint $table) {
                $table->foreignUuid('invoice_id')
                      ->nullable()
                      ->after('rfp_number')
                      ->constrained('invoices')
                      ->nullOnDelete();
                $table->index('invoice_id', 'rfps_invoice_id_idx');
            });
        }

        // 2. Seed the six new invoice permission keys + role defaults.
        foreach (self::PERMISSIONS as $key => $description) {
            $permissionId = DB::table('permissions')->where('key', $key)->value('id');

            if (!$permissionId) {
                $permissionId = (string) Str::uuid();
                [, $feature, $action] = explode('.', $key, 3);
                DB::table('permissions')->insert([
                    'id'          => $permissionId,
                    'module'      => 'procurement',
                    'feature'     => $feature,
                    'action'      => $action,
                    'key'         => $key,
                    'description' => $description,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                DB::table('permissions')
                    ->where('id', $permissionId)
                    ->update(['description' => $description, 'updated_at' => now()]);
            }

            foreach (self::ROLE_DEFAULTS as $role => $grants) {
                if (!array_key_exists($key, $grants)) {
                    continue;
                }
                $exists = DB::table('role_permissions')
                    ->where('role', $role)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'id'            => (string) Str::uuid(),
                        'role'          => $role,
                        'permission_id' => $permissionId,
                        'allowed'       => $grants[$key],
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Drop the column + permission rows. Role-permission rows go via
        // the cascade on permissions.id.
        if (Schema::hasColumn('rfps', 'invoice_id')) {
            Schema::table('rfps', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
                $table->dropIndex('rfps_invoice_id_idx');
                $table->dropColumn('invoice_id');
            });
        }

        foreach (array_keys(self::PERMISSIONS) as $key) {
            $permissionId = DB::table('permissions')->where('key', $key)->value('id');
            if ($permissionId) {
                DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
                DB::table('permissions')->where('id', $permissionId)->delete();
            }
        }
    }
};
