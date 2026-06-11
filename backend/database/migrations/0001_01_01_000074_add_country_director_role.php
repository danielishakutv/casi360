<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Introduces the `country_director` role.
 *
 *  1. Widens the users.role ENUM to include 'country_director'.
 *  2. Grants the role its default permission set — an executive overseer who
 *     can SEE every department's records (view + *.view_all), is the final
 *     approver on payment documents (invoices today; RFP & disbursements get
 *     dedicated approve keys in the Phase-4 approval-chain migration), but is
 *     NOT a system administrator (no user management, no settings editing, no
 *     create/edit/delete on operational documents).
 *
 * Org-wide dashboard visibility for this role is enforced in code by
 * {@see \App\Services\Access\DepartmentScope}; the permission rows below gate
 * the menu items and individual actions.
 *
 * Idempotent: only inserts rows that don't already exist. Safe to re-run and
 * safe on a production database that was previously seeded.
 */
return new class extends Migration
{
    private const ROLE = 'country_director';

    /**
     * Permission keys this role is granted (allowed = true). Keys absent from
     * this list are implicitly denied (the permission middleware fails closed
     * when no allowed row exists), so only the grants need to be stored.
     */
    private const GRANTS = [
        // HR — view only (oversight)
        'hr.departments.view',
        'hr.designations.view',
        'hr.employees.view',
        'hr.notes.view',
        'hr.leave_types.view',
        'hr.holidays.view',

        // Procurement — full visibility across the whole organisation
        'procurement.vendors.view',
        'procurement.vendor_categories.view',
        'procurement.inventory.view',
        'procurement.purchase_orders.view',
        'procurement.purchase_orders.view_all',
        'procurement.requisitions.view',
        'procurement.requisitions.view_all',
        'procurement.approvals.view',
        'procurement.approvals.view_all',
        'procurement.boq.view',
        'procurement.boq.view_all',
        'procurement.rfq.view',
        'procurement.rfq.view_all',
        'procurement.grn.view',
        'procurement.grn.view_all',
        'procurement.rfp.view',
        'procurement.rfp.view_all',
        'procurement.invoices.view',
        'procurement.invoices.view_all',

        // Payment authority
        'procurement.invoices.approve',         // approve vendor invoices
        'procurement.disbursements.view',
        'procurement.disbursements.create',     // authorise the money-out record
        'procurement.approval.executive_approval', // legacy PO executive stage

        // Projects — view only (oversight) + commentary
        'projects.budget_categories.view',
        'projects.projects.view',
        'projects.activities.view',
        'projects.budget.view',
        'projects.notes.view',
        'projects.notes.create',

        // Communication
        'communication.messages.view',
        'communication.messages.create',
        'communication.messages.delete',
        'communication.forums.view',
        'communication.forums.create',
        'communication.notices.view',
        'communication.notices.create',
        'communication.emails.view',
        'communication.sms.view',

        // Programs — view only
        'programs.beneficiaries.view',

        // Reports & audit — full oversight
        'reports.reports.view',
        'reports.reports.download',
        'reports.reports.audit',
    ];

    public function up(): void
    {
        // 1. Widen the role enum (additive — existing rows untouched).
        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role "
            . "ENUM('super_admin','admin','country_director','manager','staff') "
            . "NOT NULL DEFAULT 'staff'"
        );

        // 2. Grant the role its default permissions.
        foreach (self::GRANTS as $key) {
            $permissionId = DB::table('permissions')->where('key', $key)->value('id');
            if (!$permissionId) {
                // Permission not present in this database — skip rather than fail.
                continue;
            }

            $exists = DB::table('role_permissions')
                ->where('role', self::ROLE)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$exists) {
                DB::table('role_permissions')->insert([
                    'id'            => (string) Str::uuid(),
                    'role'          => self::ROLE,
                    'permission_id' => $permissionId,
                    'allowed'       => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove this role's permission grants.
        DB::table('role_permissions')->where('role', self::ROLE)->delete();

        // Reassign any users still on this role to 'staff' so the enum can be
        // narrowed without violating the column definition, then narrow it.
        DB::table('users')->where('role', self::ROLE)->update(['role' => 'staff']);

        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role "
            . "ENUM('super_admin','admin','manager','staff') "
            . "NOT NULL DEFAULT 'staff'"
        );
    }
};
