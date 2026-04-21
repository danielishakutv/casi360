<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Define all permissions
        |--------------------------------------------------------------------------
        |
        | Key format: module.feature.action
        |
        | When adding a new module/feature, append entries to this array.
        |
        */
        $permissions = [
            // --- Auth: User Management ---
            ['module' => 'auth', 'feature' => 'users', 'action' => 'view', 'description' => 'View user list and details'],
            ['module' => 'auth', 'feature' => 'users', 'action' => 'create', 'description' => 'Create new users'],
            ['module' => 'auth', 'feature' => 'users', 'action' => 'edit', 'description' => 'Edit user details'],
            ['module' => 'auth', 'feature' => 'users', 'action' => 'delete', 'description' => 'Delete/deactivate users'],
            ['module' => 'auth', 'feature' => 'users', 'action' => 'manage_roles', 'description' => 'Change user roles'],
            ['module' => 'auth', 'feature' => 'users', 'action' => 'manage_status', 'description' => 'Change user status'],

            // --- HR: Departments ---
            ['module' => 'hr', 'feature' => 'departments', 'action' => 'view', 'description' => 'View departments list and details'],
            ['module' => 'hr', 'feature' => 'departments', 'action' => 'create', 'description' => 'Create new departments'],
            ['module' => 'hr', 'feature' => 'departments', 'action' => 'edit', 'description' => 'Edit department details'],
            ['module' => 'hr', 'feature' => 'departments', 'action' => 'delete', 'description' => 'Delete/deactivate departments'],

            // --- HR: Designations ---
            ['module' => 'hr', 'feature' => 'designations', 'action' => 'view', 'description' => 'View designations list and details'],
            ['module' => 'hr', 'feature' => 'designations', 'action' => 'create', 'description' => 'Create new designations'],
            ['module' => 'hr', 'feature' => 'designations', 'action' => 'edit', 'description' => 'Edit designation details'],
            ['module' => 'hr', 'feature' => 'designations', 'action' => 'delete', 'description' => 'Delete/deactivate designations'],

            // --- HR: Employees ---
            ['module' => 'hr', 'feature' => 'employees', 'action' => 'view', 'description' => 'View employees list and details'],
            ['module' => 'hr', 'feature' => 'employees', 'action' => 'create', 'description' => 'Create new employees'],
            ['module' => 'hr', 'feature' => 'employees', 'action' => 'edit', 'description' => 'Edit employee details'],
            ['module' => 'hr', 'feature' => 'employees', 'action' => 'delete', 'description' => 'Terminate employees'],
            ['module' => 'hr', 'feature' => 'employees', 'action' => 'manage_status', 'description' => 'Change employee status'],

            // --- HR: Notes ---
            ['module' => 'hr', 'feature' => 'notes', 'action' => 'view', 'description' => 'View employee notes'],
            ['module' => 'hr', 'feature' => 'notes', 'action' => 'create', 'description' => 'Create employee notes'],
            ['module' => 'hr', 'feature' => 'notes', 'action' => 'edit', 'description' => 'Edit employee notes'],
            ['module' => 'hr', 'feature' => 'notes', 'action' => 'delete', 'description' => 'Delete employee notes'],

            // --- HR: Leave Types ---
            ['module' => 'hr', 'feature' => 'leave_types', 'action' => 'view', 'description' => 'View leave types'],
            ['module' => 'hr', 'feature' => 'leave_types', 'action' => 'create', 'description' => 'Create leave types'],
            ['module' => 'hr', 'feature' => 'leave_types', 'action' => 'edit', 'description' => 'Edit leave types'],
            ['module' => 'hr', 'feature' => 'leave_types', 'action' => 'delete', 'description' => 'Delete leave types'],

            // --- HR: Holidays ---
            ['module' => 'hr', 'feature' => 'holidays', 'action' => 'view', 'description' => 'View holidays'],
            ['module' => 'hr', 'feature' => 'holidays', 'action' => 'create', 'description' => 'Create holidays'],
            ['module' => 'hr', 'feature' => 'holidays', 'action' => 'edit', 'description' => 'Edit holidays'],
            ['module' => 'hr', 'feature' => 'holidays', 'action' => 'delete', 'description' => 'Delete holidays'],

            // --- Settings: System Settings ---
            ['module' => 'settings', 'feature' => 'system', 'action' => 'view', 'description' => 'View system settings'],
            ['module' => 'settings', 'feature' => 'system', 'action' => 'edit', 'description' => 'Edit system settings'],

            // --- Procurement: Vendors ---
            ['module' => 'procurement', 'feature' => 'vendors', 'action' => 'view', 'description' => 'View vendors list and details'],
            ['module' => 'procurement', 'feature' => 'vendors', 'action' => 'create', 'description' => 'Create new vendors'],
            ['module' => 'procurement', 'feature' => 'vendors', 'action' => 'edit', 'description' => 'Edit vendor details'],
            ['module' => 'procurement', 'feature' => 'vendors', 'action' => 'delete', 'description' => 'Delete/deactivate vendors'],

            // --- Procurement: Purchase Orders ---
            ['module' => 'procurement', 'feature' => 'purchase_orders', 'action' => 'view', 'description' => 'View purchase orders list and details'],
            ['module' => 'procurement', 'feature' => 'purchase_orders', 'action' => 'create', 'description' => 'Create new purchase orders'],
            ['module' => 'procurement', 'feature' => 'purchase_orders', 'action' => 'edit', 'description' => 'Edit purchase order details'],
            ['module' => 'procurement', 'feature' => 'purchase_orders', 'action' => 'delete', 'description' => 'Cancel purchase orders'],

            // --- Procurement: Inventory ---
            ['module' => 'procurement', 'feature' => 'inventory', 'action' => 'view', 'description' => 'View inventory items'],
            ['module' => 'procurement', 'feature' => 'inventory', 'action' => 'create', 'description' => 'Create inventory items'],
            ['module' => 'procurement', 'feature' => 'inventory', 'action' => 'edit', 'description' => 'Edit inventory items'],
            ['module' => 'procurement', 'feature' => 'inventory', 'action' => 'delete', 'description' => 'Deactivate inventory items'],

            // --- Procurement: Requisitions ---
            ['module' => 'procurement', 'feature' => 'requisitions', 'action' => 'view', 'description' => 'View requisitions list and details'],
            ['module' => 'procurement', 'feature' => 'requisitions', 'action' => 'create', 'description' => 'Create new requisitions'],
            ['module' => 'procurement', 'feature' => 'requisitions', 'action' => 'edit', 'description' => 'Edit requisition details'],
            ['module' => 'procurement', 'feature' => 'requisitions', 'action' => 'delete', 'description' => 'Cancel requisitions'],

            // --- Procurement: Approval Workflow (legacy — used by Purchase Orders) ---
            ['module' => 'procurement', 'feature' => 'approval', 'action' => 'manager_review', 'description' => 'Act on Manager Review approval steps'],
            ['module' => 'procurement', 'feature' => 'approval', 'action' => 'finance_check', 'description' => 'Act on Finance Verification approval steps'],
            ['module' => 'procurement', 'feature' => 'approval', 'action' => 'operations_approval', 'description' => 'Act on Operations Approval steps (>threshold)'],
            ['module' => 'procurement', 'feature' => 'approval', 'action' => 'executive_approval', 'description' => 'Act on Executive Director Approval steps (>threshold)'],
            ['module' => 'procurement', 'feature' => 'approval', 'action' => 'self_approve', 'description' => 'Allow approving own submissions (overrides self-approval block)'],

            // --- Procurement: Purchase Request Approval Workflow (3-stage fixed chain) ---
            ['module' => 'procurement', 'feature' => 'approvals', 'action' => 'view',         'description' => 'View the Approvals page and pending purchase requests'],
            ['module' => 'procurement', 'feature' => 'approvals', 'action' => 'budget_holder', 'description' => 'Act as Budget Holder — Stage 1 PR approval'],
            ['module' => 'procurement', 'feature' => 'approvals', 'action' => 'finance',       'description' => 'Act as Finance approver — Stage 2 PR approval (approve final or forward)'],
            ['module' => 'procurement', 'feature' => 'approvals', 'action' => 'operations',    'description' => 'Act as Operations approver — Stage 3 PR approval (only when forwarded by Finance)'],

            // --- Procurement: Disbursements ---
            ['module' => 'procurement', 'feature' => 'disbursements', 'action' => 'view', 'description' => 'View disbursement records'],
            ['module' => 'procurement', 'feature' => 'disbursements', 'action' => 'create', 'description' => 'Record new disbursements'],

            // --- Procurement: Vendor Categories ---
            ['module' => 'procurement', 'feature' => 'vendor_categories', 'action' => 'view', 'description' => 'View vendor categories'],
            ['module' => 'procurement', 'feature' => 'vendor_categories', 'action' => 'create', 'description' => 'Create vendor categories'],
            ['module' => 'procurement', 'feature' => 'vendor_categories', 'action' => 'edit', 'description' => 'Edit vendor categories'],
            ['module' => 'procurement', 'feature' => 'vendor_categories', 'action' => 'delete', 'description' => 'Delete vendor categories'],

            // --- Procurement: BOQ ---
            ['module' => 'procurement', 'feature' => 'boq', 'action' => 'view', 'description' => 'View bills of quantities'],
            ['module' => 'procurement', 'feature' => 'boq', 'action' => 'create', 'description' => 'Create bills of quantities'],
            ['module' => 'procurement', 'feature' => 'boq', 'action' => 'edit', 'description' => 'Edit bills of quantities'],
            ['module' => 'procurement', 'feature' => 'boq', 'action' => 'delete', 'description' => 'Delete bills of quantities'],

            // --- Procurement: RFQ ---
            ['module' => 'procurement', 'feature' => 'rfq', 'action' => 'view', 'description' => 'View requests for quotation'],
            ['module' => 'procurement', 'feature' => 'rfq', 'action' => 'create', 'description' => 'Create requests for quotation'],
            ['module' => 'procurement', 'feature' => 'rfq', 'action' => 'edit', 'description' => 'Edit requests for quotation'],
            ['module' => 'procurement', 'feature' => 'rfq', 'action' => 'delete', 'description' => 'Cancel requests for quotation'],

            // --- Procurement: GRN ---
            ['module' => 'procurement', 'feature' => 'grn', 'action' => 'view', 'description' => 'View goods received notes'],
            ['module' => 'procurement', 'feature' => 'grn', 'action' => 'create', 'description' => 'Create goods received notes'],
            ['module' => 'procurement', 'feature' => 'grn', 'action' => 'edit', 'description' => 'Edit goods received notes'],
            ['module' => 'procurement', 'feature' => 'grn', 'action' => 'delete', 'description' => 'Delete goods received notes'],

            // --- Procurement: RFP ---
            ['module' => 'procurement', 'feature' => 'rfp', 'action' => 'view', 'description' => 'View requests for payment'],
            ['module' => 'procurement', 'feature' => 'rfp', 'action' => 'create', 'description' => 'Create requests for payment'],
            ['module' => 'procurement', 'feature' => 'rfp', 'action' => 'edit', 'description' => 'Edit requests for payment'],
            ['module' => 'procurement', 'feature' => 'rfp', 'action' => 'delete', 'description' => 'Reject requests for payment'],

            // --- Projects: Budget Categories ---
            ['module' => 'projects', 'feature' => 'budget_categories', 'action' => 'view', 'description' => 'View budget categories'],
            ['module' => 'projects', 'feature' => 'budget_categories', 'action' => 'create', 'description' => 'Create budget categories'],
            ['module' => 'projects', 'feature' => 'budget_categories', 'action' => 'edit', 'description' => 'Edit budget categories'],
            ['module' => 'projects', 'feature' => 'budget_categories', 'action' => 'delete', 'description' => 'Delete budget categories'],

            // --- Projects: Projects ---
            ['module' => 'projects', 'feature' => 'projects', 'action' => 'view', 'description' => 'View projects list and details'],
            ['module' => 'projects', 'feature' => 'projects', 'action' => 'create', 'description' => 'Create new projects'],
            ['module' => 'projects', 'feature' => 'projects', 'action' => 'edit', 'description' => 'Edit projects, donors, partners, and team'],
            ['module' => 'projects', 'feature' => 'projects', 'action' => 'delete', 'description' => 'Close projects'],

            // --- Projects: Activities ---
            ['module' => 'projects', 'feature' => 'activities', 'action' => 'view', 'description' => 'View project activities/milestones'],
            ['module' => 'projects', 'feature' => 'activities', 'action' => 'create', 'description' => 'Create project activities'],
            ['module' => 'projects', 'feature' => 'activities', 'action' => 'edit', 'description' => 'Edit project activities'],
            ['module' => 'projects', 'feature' => 'activities', 'action' => 'delete', 'description' => 'Delete project activities'],

            // --- Projects: Budget ---
            ['module' => 'projects', 'feature' => 'budget', 'action' => 'view', 'description' => 'View project budget lines'],
            ['module' => 'projects', 'feature' => 'budget', 'action' => 'create', 'description' => 'Add project budget lines'],
            ['module' => 'projects', 'feature' => 'budget', 'action' => 'edit', 'description' => 'Edit project budget lines'],
            ['module' => 'projects', 'feature' => 'budget', 'action' => 'delete', 'description' => 'Remove project budget lines'],

            // --- Projects: Notes ---
            ['module' => 'projects', 'feature' => 'notes', 'action' => 'view', 'description' => 'View project notes'],
            ['module' => 'projects', 'feature' => 'notes', 'action' => 'create', 'description' => 'Add project notes'],
            ['module' => 'projects', 'feature' => 'notes', 'action' => 'edit', 'description' => 'Edit project notes'],
            ['module' => 'projects', 'feature' => 'notes', 'action' => 'delete', 'description' => 'Delete project notes'],

            // --- Communication: Messages ---
            ['module' => 'communication', 'feature' => 'messages', 'action' => 'view', 'description' => 'View own messages and threads'],
            ['module' => 'communication', 'feature' => 'messages', 'action' => 'create', 'description' => 'Send new messages and replies'],
            ['module' => 'communication', 'feature' => 'messages', 'action' => 'delete', 'description' => 'Delete own messages'],

            // --- Communication: Forums ---
            ['module' => 'communication', 'feature' => 'forums', 'action' => 'view', 'description' => 'View accessible forums and messages'],
            ['module' => 'communication', 'feature' => 'forums', 'action' => 'create', 'description' => 'Post messages in forums'],
            ['module' => 'communication', 'feature' => 'forums', 'action' => 'manage', 'description' => 'Create, edit, and archive forums'],

            // --- Communication: Notices ---
            ['module' => 'communication', 'feature' => 'notices', 'action' => 'view', 'description' => 'View published notices'],
            ['module' => 'communication', 'feature' => 'notices', 'action' => 'create', 'description' => 'Create new notices'],
            ['module' => 'communication', 'feature' => 'notices', 'action' => 'edit', 'description' => 'Edit existing notices'],
            ['module' => 'communication', 'feature' => 'notices', 'action' => 'delete', 'description' => 'Delete notices'],

            // --- Communication: Emails ---
            ['module' => 'communication', 'feature' => 'emails', 'action' => 'view', 'description' => 'View sent emails'],
            ['module' => 'communication', 'feature' => 'emails', 'action' => 'create', 'description' => 'Send emails'],
            ['module' => 'communication', 'feature' => 'emails', 'action' => 'delete', 'description' => 'Delete email records'],

            // --- Communication: SMS ---
            ['module' => 'communication', 'feature' => 'sms', 'action' => 'view', 'description' => 'View sent SMS messages'],
            ['module' => 'communication', 'feature' => 'sms', 'action' => 'create', 'description' => 'Send SMS messages'],
            ['module' => 'communication', 'feature' => 'sms', 'action' => 'delete', 'description' => 'Delete SMS records'],

            // --- Programs: Beneficiaries ---
            ['module' => 'programs', 'feature' => 'beneficiaries', 'action' => 'view', 'description' => 'View beneficiaries list and details'],
            ['module' => 'programs', 'feature' => 'beneficiaries', 'action' => 'create', 'description' => 'Register new beneficiaries'],
            ['module' => 'programs', 'feature' => 'beneficiaries', 'action' => 'edit', 'description' => 'Edit beneficiary details'],
            ['module' => 'programs', 'feature' => 'beneficiaries', 'action' => 'delete', 'description' => 'Remove beneficiaries'],

            // --- Reports ---
            ['module' => 'reports', 'feature' => 'reports', 'action' => 'view', 'description' => 'Preview report data (JSON)'],
            ['module' => 'reports', 'feature' => 'reports', 'action' => 'download', 'description' => 'Download reports as CSV, Excel, or PDF'],
            ['module' => 'reports', 'feature' => 'reports', 'action' => 'audit', 'description' => 'Access audit log and login history reports'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Default role mappings
        |--------------------------------------------------------------------------
        |
        | super_admin: bypasses all checks (not stored — hardcoded in middleware)
        | admin:       all permissions enabled
        | manager:     all HR permissions enabled, no auth/user management
        | staff:       view-only on all features
        |
        */
        $roleDefaults = [
            'admin' => [
                'auth.users.view' => true,
                'auth.users.create' => true,
                'auth.users.edit' => true,
                'auth.users.delete' => true,
                'auth.users.manage_roles' => true,
                'auth.users.manage_status' => true,
                'hr.departments.view' => true,
                'hr.departments.create' => true,
                'hr.departments.edit' => true,
                'hr.departments.delete' => true,
                'hr.designations.view' => true,
                'hr.designations.create' => true,
                'hr.designations.edit' => true,
                'hr.designations.delete' => true,
                'hr.employees.view' => true,
                'hr.employees.create' => true,
                'hr.employees.edit' => true,
                'hr.employees.delete' => true,
                'hr.employees.manage_status' => true,
                'hr.notes.view' => true,
                'hr.notes.create' => true,
                'hr.notes.edit' => true,
                'hr.notes.delete' => true,
                'hr.leave_types.view' => true,
                'hr.leave_types.create' => true,
                'hr.leave_types.edit' => true,
                'hr.leave_types.delete' => true,
                'hr.holidays.view' => true,
                'hr.holidays.create' => true,
                'hr.holidays.edit' => true,
                'hr.holidays.delete' => true,
                'settings.system.view' => true,
                'settings.system.edit' => false,
                'procurement.vendors.view' => true,
                'procurement.vendors.create' => true,
                'procurement.vendors.edit' => true,
                'procurement.vendors.delete' => true,
                'procurement.purchase_orders.view' => true,
                'procurement.purchase_orders.create' => true,
                'procurement.purchase_orders.edit' => true,
                'procurement.purchase_orders.delete' => true,
                'procurement.inventory.view' => true,
                'procurement.inventory.create' => true,
                'procurement.inventory.edit' => true,
                'procurement.inventory.delete' => true,
                'procurement.requisitions.view' => true,
                'procurement.requisitions.create' => true,
                'procurement.requisitions.edit' => true,
                'procurement.requisitions.delete' => true,
                'procurement.approval.manager_review' => true,
                'procurement.approval.finance_check' => true,
                'procurement.approval.operations_approval' => false,
                'procurement.approval.executive_approval' => false,
                'procurement.approval.self_approve' => false,
                'procurement.approvals.view'         => true,
                'procurement.approvals.budget_holder' => true,
                'procurement.approvals.finance'       => true,
                'procurement.approvals.operations'    => true,
                'procurement.disbursements.view' => true,
                'procurement.disbursements.create' => true,
                'procurement.vendor_categories.view' => true,
                'procurement.vendor_categories.create' => true,
                'procurement.vendor_categories.edit' => true,
                'procurement.vendor_categories.delete' => true,
                'procurement.boq.view' => true,
                'procurement.boq.create' => true,
                'procurement.boq.edit' => true,
                'procurement.boq.delete' => true,
                'procurement.rfq.view' => true,
                'procurement.rfq.create' => true,
                'procurement.rfq.edit' => true,
                'procurement.rfq.delete' => true,
                'procurement.grn.view' => true,
                'procurement.grn.create' => true,
                'procurement.grn.edit' => true,
                'procurement.grn.delete' => true,
                'procurement.rfp.view' => true,
                'procurement.rfp.create' => true,
                'procurement.rfp.edit' => true,
                'procurement.rfp.delete' => true,
                'projects.budget_categories.view' => true,
                'projects.budget_categories.create' => true,
                'projects.budget_categories.edit' => true,
                'projects.budget_categories.delete' => true,
                'projects.projects.view' => true,
                'projects.projects.create' => true,
                'projects.projects.edit' => true,
                'projects.projects.delete' => true,
                'projects.activities.view' => true,
                'projects.activities.create' => true,
                'projects.activities.edit' => true,
                'projects.activities.delete' => true,
                'projects.budget.view' => true,
                'projects.budget.create' => true,
                'projects.budget.edit' => true,
                'projects.budget.delete' => true,
                'projects.notes.view' => true,
                'projects.notes.create' => true,
                'projects.notes.edit' => true,
                'projects.notes.delete' => true,
                'communication.messages.view' => true,
                'communication.messages.create' => true,
                'communication.messages.delete' => true,
                'communication.forums.view' => true,
                'communication.forums.create' => true,
                'communication.forums.manage' => true,
                'communication.notices.view' => true,
                'communication.notices.create' => true,
                'communication.notices.edit' => true,
                'communication.notices.delete' => true,
                'communication.emails.view' => true,
                'communication.emails.create' => true,
                'communication.emails.delete' => true,
                'communication.sms.view' => true,
                'communication.sms.create' => true,
                'communication.sms.delete' => true,
                'programs.beneficiaries.view' => true,
                'programs.beneficiaries.create' => true,
                'programs.beneficiaries.edit' => true,
                'programs.beneficiaries.delete' => true,
                'reports.reports.view' => true,
                'reports.reports.download' => true,
                'reports.reports.audit' => true,
            ],
            'manager' => [
                'auth.users.view' => false,
                'auth.users.create' => false,
                'auth.users.edit' => false,
                'auth.users.delete' => false,
                'auth.users.manage_roles' => false,
                'auth.users.manage_status' => false,
                'hr.departments.view' => true,
                'hr.departments.create' => true,
                'hr.departments.edit' => true,
                'hr.departments.delete' => true,
                'hr.designations.view' => true,
                'hr.designations.create' => true,
                'hr.designations.edit' => true,
                'hr.designations.delete' => true,
                'hr.employees.view' => true,
                'hr.employees.create' => true,
                'hr.employees.edit' => true,
                'hr.employees.delete' => true,
                'hr.employees.manage_status' => true,
                'hr.notes.view' => true,
                'hr.notes.create' => true,
                'hr.notes.edit' => true,
                'hr.notes.delete' => true,
                'hr.leave_types.view' => true,
                'hr.leave_types.create' => true,
                'hr.leave_types.edit' => true,
                'hr.leave_types.delete' => true,
                'hr.holidays.view' => true,
                'hr.holidays.create' => true,
                'hr.holidays.edit' => true,
                'hr.holidays.delete' => false,
                'settings.system.view' => false,
                'settings.system.edit' => false,
                'procurement.vendors.view' => true,
                'procurement.vendors.create' => true,
                'procurement.vendors.edit' => true,
                'procurement.vendors.delete' => true,
                'procurement.purchase_orders.view' => true,
                'procurement.purchase_orders.create' => true,
                'procurement.purchase_orders.edit' => true,
                'procurement.purchase_orders.delete' => true,
                'procurement.inventory.view' => true,
                'procurement.inventory.create' => true,
                'procurement.inventory.edit' => true,
                'procurement.inventory.delete' => true,
                'procurement.requisitions.view' => true,
                'procurement.requisitions.create' => true,
                'procurement.requisitions.edit' => true,
                'procurement.requisitions.delete' => true,
                'procurement.approval.manager_review' => true,
                'procurement.approval.finance_check' => false,
                'procurement.approval.operations_approval' => false,
                'procurement.approval.executive_approval' => false,
                'procurement.approval.self_approve' => false,
                'procurement.approvals.view'         => true,
                'procurement.approvals.budget_holder' => true,
                'procurement.approvals.finance'       => false,
                'procurement.approvals.operations'    => false,
                'procurement.disbursements.view' => true,
                'procurement.disbursements.create' => false,
                'procurement.vendor_categories.view' => true,
                'procurement.vendor_categories.create' => true,
                'procurement.vendor_categories.edit' => true,
                'procurement.vendor_categories.delete' => false,
                'procurement.boq.view' => true,
                'procurement.boq.create' => true,
                'procurement.boq.edit' => true,
                'procurement.boq.delete' => false,
                'procurement.rfq.view' => true,
                'procurement.rfq.create' => true,
                'procurement.rfq.edit' => true,
                'procurement.rfq.delete' => false,
                'procurement.grn.view' => true,
                'procurement.grn.create' => true,
                'procurement.grn.edit' => true,
                'procurement.grn.delete' => false,
                'procurement.rfp.view' => true,
                'procurement.rfp.create' => true,
                'procurement.rfp.edit' => true,
                'procurement.rfp.delete' => false,
                'projects.budget_categories.view' => true,
                'projects.budget_categories.create' => false,
                'projects.budget_categories.edit' => false,
                'projects.budget_categories.delete' => false,
                'projects.projects.view' => true,
                'projects.projects.create' => true,
                'projects.projects.edit' => true,
                'projects.projects.delete' => false,
                'projects.activities.view' => true,
                'projects.activities.create' => true,
                'projects.activities.edit' => true,
                'projects.activities.delete' => false,
                'projects.budget.view' => true,
                'projects.budget.create' => true,
                'projects.budget.edit' => true,
                'projects.budget.delete' => false,
                'projects.notes.view' => true,
                'projects.notes.create' => true,
                'projects.notes.edit' => true,
                'projects.notes.delete' => false,
                'communication.messages.view' => true,
                'communication.messages.create' => true,
                'communication.messages.delete' => true,
                'communication.forums.view' => true,
                'communication.forums.create' => true,
                'communication.forums.manage' => false,
                'communication.notices.view' => true,
                'communication.notices.create' => true,
                'communication.notices.edit' => true,
                'communication.notices.delete' => false,
                'communication.emails.view' => true,
                'communication.emails.create' => true,
                'communication.emails.delete' => false,
                'communication.sms.view' => true,
                'communication.sms.create' => true,
                'communication.sms.delete' => false,
                'programs.beneficiaries.view' => true,
                'programs.beneficiaries.create' => true,
                'programs.beneficiaries.edit' => true,
                'programs.beneficiaries.delete' => false,
                'reports.reports.view' => true,
                'reports.reports.download' => true,
                'reports.reports.audit' => false,
            ],
            'staff' => [
                'auth.users.view' => false,
                'auth.users.create' => false,
                'auth.users.edit' => false,
                'auth.users.delete' => false,
                'auth.users.manage_roles' => false,
                'auth.users.manage_status' => false,
                'hr.departments.view' => true,
                'hr.departments.create' => false,
                'hr.departments.edit' => false,
                'hr.departments.delete' => false,
                'hr.designations.view' => true,
                'hr.designations.create' => false,
                'hr.designations.edit' => false,
                'hr.designations.delete' => false,
                'hr.employees.view' => true,
                'hr.employees.create' => false,
                'hr.employees.edit' => false,
                'hr.employees.delete' => false,
                'hr.employees.manage_status' => false,
                'hr.notes.view' => true,
                'hr.notes.create' => false,
                'hr.notes.edit' => false,
                'hr.notes.delete' => false,
                'hr.leave_types.view' => true,
                'hr.leave_types.create' => false,
                'hr.leave_types.edit' => false,
                'hr.leave_types.delete' => false,
                'hr.holidays.view' => true,
                'hr.holidays.create' => false,
                'hr.holidays.edit' => false,
                'hr.holidays.delete' => false,
                'settings.system.view' => false,
                'settings.system.edit' => false,
                'procurement.vendors.view' => true,
                'procurement.vendors.create' => false,
                'procurement.vendors.edit' => false,
                'procurement.vendors.delete' => false,
                'procurement.purchase_orders.view' => true,
                'procurement.purchase_orders.create' => false,
                'procurement.purchase_orders.edit' => false,
                'procurement.purchase_orders.delete' => false,
                'procurement.inventory.view' => true,
                'procurement.inventory.create' => false,
                'procurement.inventory.edit' => false,
                'procurement.inventory.delete' => false,
                'procurement.requisitions.view' => true,
                'procurement.requisitions.create' => false,
                'procurement.requisitions.edit' => false,
                'procurement.requisitions.delete' => false,
                'procurement.approval.manager_review' => false,
                'procurement.approval.finance_check' => false,
                'procurement.approval.operations_approval' => false,
                'procurement.approval.executive_approval' => false,
                'procurement.approval.self_approve' => false,
                'procurement.approvals.view'         => true,
                'procurement.approvals.budget_holder' => false,
                'procurement.approvals.finance'       => false,
                'procurement.approvals.operations'    => false,
                'procurement.disbursements.view' => false,
                'procurement.disbursements.create' => false,
                'procurement.vendor_categories.view' => true,
                'procurement.vendor_categories.create' => false,
                'procurement.vendor_categories.edit' => false,
                'procurement.vendor_categories.delete' => false,
                'procurement.boq.view' => true,
                'procurement.boq.create' => false,
                'procurement.boq.edit' => false,
                'procurement.boq.delete' => false,
                'procurement.rfq.view' => true,
                'procurement.rfq.create' => false,
                'procurement.rfq.edit' => false,
                'procurement.rfq.delete' => false,
                'procurement.grn.view' => true,
                'procurement.grn.create' => false,
                'procurement.grn.edit' => false,
                'procurement.grn.delete' => false,
                'procurement.rfp.view' => true,
                'procurement.rfp.create' => false,
                'procurement.rfp.edit' => false,
                'procurement.rfp.delete' => false,
                'projects.budget_categories.view' => true,
                'projects.budget_categories.create' => false,
                'projects.budget_categories.edit' => false,
                'projects.budget_categories.delete' => false,
                'projects.projects.view' => true,
                'projects.projects.create' => false,
                'projects.projects.edit' => false,
                'projects.projects.delete' => false,
                'projects.activities.view' => true,
                'projects.activities.create' => false,
                'projects.activities.edit' => false,
                'projects.activities.delete' => false,
                'projects.budget.view' => true,
                'projects.budget.create' => false,
                'projects.budget.edit' => false,
                'projects.budget.delete' => false,
                'projects.notes.view' => true,
                'projects.notes.create' => true,
                'projects.notes.edit' => false,
                'projects.notes.delete' => false,
                'communication.messages.view' => true,
                'communication.messages.create' => true,
                'communication.messages.delete' => true,
                'communication.forums.view' => true,
                'communication.forums.create' => true,
                'communication.forums.manage' => false,
                'communication.notices.view' => true,
                'communication.notices.create' => false,
                'communication.notices.edit' => false,
                'communication.notices.delete' => false,
                'communication.emails.view' => true,
                'communication.emails.create' => false,
                'communication.emails.delete' => false,
                'communication.sms.view' => true,
                'communication.sms.create' => false,
                'communication.sms.delete' => false,
                'programs.beneficiaries.view' => true,
                'programs.beneficiaries.create' => false,
                'programs.beneficiaries.edit' => false,
                'programs.beneficiaries.delete' => false,
                'reports.reports.view' => true,
                'reports.reports.download' => true,
                'reports.reports.audit' => false,
            ],
        ];

        // --- Seed permissions ---
        $permMap = [];
        foreach ($permissions as $perm) {
            $key = "{$perm['module']}.{$perm['feature']}.{$perm['action']}";
            $p = Permission::updateOrCreate(
                ['key' => $key],
                array_merge($perm, ['key' => $key])
            );
            $permMap[$key] = $p->id;
        }

        // --- Seed role permissions ---
        $count = 0;
        foreach ($roleDefaults as $role => $perms) {
            foreach ($perms as $key => $allowed) {
                if (isset($permMap[$key])) {
                    RolePermission::updateOrCreate(
                        ['role' => $role, 'permission_id' => $permMap[$key]],
                        ['allowed' => $allowed]
                    );
                    $count++;
                }
            }
        }

        $this->command->info("Permission seed data created: " . count($permissions) . " permissions, {$count} role mappings.");
    }
}
