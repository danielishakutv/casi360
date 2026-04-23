<?php

/**
 * CASI360 API Routes - Authentication Module
 * 
 * All routes are prefixed with /api/v1
 * 
 * Endpoint Summary:
 * 
 * PUBLIC (no auth required):
 *   POST   /api/v1/auth/login              - Authenticate user
 *   POST   /api/v1/auth/forgot-password     - Request password reset email
 *   POST   /api/v1/auth/reset-password      - Reset password with token
 *   GET    /sanctum/csrf-cookie             - Get CSRF cookie (Sanctum)
 * 
 * AUTHENTICATED (any logged-in user):
 *   GET    /api/v1/auth/session             - Get current session/user
 *   POST   /api/v1/auth/logout              - Log out
 *   POST   /api/v1/auth/change-password     - Change own password
 *   GET    /api/v1/auth/profile             - Get own profile
 *   PATCH  /api/v1/auth/profile             - Update own profile
 *   DELETE /api/v1/auth/account             - Deactivate own account
 * 
 * ADMIN ONLY (super_admin, admin):
 *   POST   /api/v1/auth/register            - Create new user
 *   GET    /api/v1/auth/users               - List all users
 *   GET    /api/v1/auth/users/{id}          - Get specific user
 *   PATCH  /api/v1/auth/users/{id}          - Update user
 *   DELETE /api/v1/auth/users/{id}          - Deactivate user
 *   PATCH  /api/v1/auth/users/{id}/role     - Change user role
 *   PATCH  /api/v1/auth/users/{id}/status   - Change user status
 */

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\UserManagementController;
use App\Http\Controllers\HR\DepartmentController;
use App\Http\Controllers\HR\DesignationController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\HolidayController;
use App\Http\Controllers\HR\LeaveTypeController;
use App\Http\Controllers\HR\NoteController;
use App\Http\Controllers\Procurement\ApprovalController;
use App\Http\Controllers\Procurement\BoqController;
use App\Http\Controllers\Procurement\DisbursementController;
use App\Http\Controllers\Procurement\GrnController;
use App\Http\Controllers\Procurement\InventoryItemController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Procurement\ProcurementStatsController;
use App\Http\Controllers\Procurement\RequisitionController;
use App\Http\Controllers\Procurement\RfpController;
use App\Http\Controllers\Procurement\RfqController;
use App\Http\Controllers\Procurement\VendorCategoryController;
use App\Http\Controllers\Procurement\VendorController;
use App\Http\Controllers\Projects\BudgetCategoryController;
use App\Http\Controllers\Projects\ProjectActivityController;
use App\Http\Controllers\Projects\ProjectBudgetLineController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectDonorController;
use App\Http\Controllers\Projects\ProjectPartnerController;
use App\Http\Controllers\Projects\ProjectNoteController;
use App\Http\Controllers\Projects\ProjectTeamMemberController;
use App\Http\Controllers\Communication\EmailController;
use App\Http\Controllers\Communication\MessageController;
use App\Http\Controllers\Communication\ForumController;
use App\Http\Controllers\Communication\ForumMessageController;
use App\Http\Controllers\Communication\NoticeController;
use App\Http\Controllers\Communication\SmsController;
use App\Http\Controllers\Reports\AuditReportController;
use App\Http\Controllers\Reports\CommunicationReportController;
use App\Http\Controllers\Reports\FinanceReportController;
use App\Http\Controllers\Reports\HRReportController;
use App\Http\Controllers\Reports\ProcurementReportController;
use App\Http\Controllers\Reports\ProjectReportController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\DataManagementController;
use App\Http\Controllers\Settings\PermissionsController;
use App\Http\Controllers\Settings\RolesController;
use App\Http\Controllers\Settings\SystemSettingsController;
use App\Http\Controllers\Programs\BeneficiaryController;
use App\Http\Controllers\Programs\ProgramReportsController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\CacheResponse;
use App\Http\Middleware\ETagResponse;
use App\Http\Middleware\InvalidateCache;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Apply security headers + ETag to all API routes
|--------------------------------------------------------------------------
*/
Route::middleware([SecurityHeaders::class, ETagResponse::class])->prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Auth Routes (rate limited)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {

        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('throttle:' . env('LOGIN_RATE_LIMIT', 5) . ',1')
            ->name('auth.login');

        Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])
            ->middleware('throttle:' . env('PASSWORD_RESET_RATE_LIMIT', 3) . ',1')
            ->name('auth.forgot-password');

        Route::post('/reset-password', [PasswordController::class, 'resetPassword'])
            ->middleware('throttle:' . env('PASSWORD_RESET_RATE_LIMIT', 3) . ',1')
            ->name('auth.reset-password');
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum'])->prefix('auth')->group(function () {

        // Session & logout (exempt from force password change)
        Route::get('/session', [ProfileController::class, 'session'])->name('auth.session');
        Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');

        // Password change (allowed even during forced password change)
        Route::post('/change-password', [PasswordController::class, 'changePassword'])
            ->name('auth.change-password');

        // Routes that require active password (enforce force_password_change)
        Route::middleware([ForcePasswordChange::class])->group(function () {

            // Profile management
            Route::get('/profile', [ProfileController::class, 'show'])->name('auth.profile');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('auth.profile.update');
            Route::delete('/account', [ProfileController::class, 'destroy'])->name('auth.account.delete');

            /*
            |--------------------------------------------------------------------------
            | Admin-Only Routes (super_admin, admin)
            |--------------------------------------------------------------------------
            */
            Route::middleware([RoleMiddleware::class . ':super_admin,admin', 'throttle:60,1'])->group(function () {

                Route::post('/register', [RegisterController::class, 'register'])
                    ->middleware('throttle:' . env('REGISTER_RATE_LIMIT', 10) . ',1')
                    ->name('auth.register');

                // User management
                Route::get('/users', [UserManagementController::class, 'index'])->name('auth.users.index');
                Route::get('/users/{id}', [UserManagementController::class, 'show'])->name('auth.users.show');
                Route::patch('/users/{id}', [UserManagementController::class, 'update'])->name('auth.users.update');
                Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])->name('auth.users.destroy');
                Route::patch('/users/{id}/role', [UserManagementController::class, 'updateRole'])->name('auth.users.role');
                Route::patch('/users/{id}/status', [UserManagementController::class, 'updateStatus'])->name('auth.users.status');
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | HR Module Routes (Authenticated, Permission-Controlled)
    |--------------------------------------------------------------------------
    |
    | Departments:
    |   GET    /api/v1/hr/departments              - List departments
    |   POST   /api/v1/hr/departments              - Create department
    |   GET    /api/v1/hr/departments/{id}          - Get department
    |   PATCH  /api/v1/hr/departments/{id}          - Update department
    |   DELETE /api/v1/hr/departments/{id}          - Delete department
    |
    | Designations:
    |   GET    /api/v1/hr/designations              - List designations
    |   POST   /api/v1/hr/designations              - Create designation
    |   GET    /api/v1/hr/designations/{id}          - Get designation
    |   PATCH  /api/v1/hr/designations/{id}          - Update designation
    |   DELETE /api/v1/hr/designations/{id}          - Delete designation
    |
    | Employees (Staff):
    |   GET    /api/v1/hr/employees/stats           - Employee statistics
    |   GET    /api/v1/hr/employees                 - List employees
    |   POST   /api/v1/hr/employees                 - Create employee
    |   GET    /api/v1/hr/employees/{id}            - Get employee
    |   PATCH  /api/v1/hr/employees/{id}            - Update employee
    |   DELETE /api/v1/hr/employees/{id}            - Terminate employee
    |   PATCH  /api/v1/hr/employees/{id}/status     - Update employee status
    |
    | Notes:
    |   GET    /api/v1/hr/notes                     - List notes
    |   GET    /api/v1/hr/notes/{id}                - Get note
    |   POST   /api/v1/hr/notes                     - Create note
    |   PATCH  /api/v1/hr/notes/{id}                - Update note
    |   DELETE /api/v1/hr/notes/{id}                - Delete note
    |
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class, CacheResponse::class . ':hr,120'])->prefix('hr')->group(function () {

        // --- Departments ---
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':hr.departments.view')
            ->name('hr.departments.index');
        Route::get('/departments/{id}', [DepartmentController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':hr.departments.view')
            ->name('hr.departments.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':hr'])->group(function () {
            Route::post('/departments', [DepartmentController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':hr.departments.create')
                ->name('hr.departments.store');
            Route::patch('/departments/{id}', [DepartmentController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':hr.departments.edit')
                ->name('hr.departments.update');
            Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':hr.departments.delete')
                ->name('hr.departments.destroy');
        });

        // --- Designations ---
        Route::get('/designations', [DesignationController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':hr.designations.view')
            ->name('hr.designations.index');
        Route::get('/designations/{id}', [DesignationController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':hr.designations.view')
            ->name('hr.designations.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':hr'])->group(function () {
            Route::post('/designations', [DesignationController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':hr.designations.create')
                ->name('hr.designations.store');
            Route::patch('/designations/{id}', [DesignationController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':hr.designations.edit')
                ->name('hr.designations.update');
            Route::delete('/designations/{id}', [DesignationController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':hr.designations.delete')
                ->name('hr.designations.destroy');
        });

        // --- Employees ---
        Route::get('/employees/stats', [EmployeeController::class, 'stats'])
            ->middleware(PermissionMiddleware::class . ':hr.employees.view')
            ->name('hr.employees.stats');
        Route::get('/employees', [EmployeeController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':hr.employees.view')
            ->name('hr.employees.index');
        Route::get('/employees/{id}', [EmployeeController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':hr.employees.view')
            ->name('hr.employees.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':hr'])->group(function () {
            Route::post('/employees', [EmployeeController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':hr.employees.create')
                ->name('hr.employees.store');
            Route::patch('/employees/{id}', [EmployeeController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':hr.employees.edit')
                ->name('hr.employees.update');
            Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':hr.employees.delete')
                ->name('hr.employees.destroy');
            Route::patch('/employees/{id}/status', [EmployeeController::class, 'updateStatus'])
                ->middleware(PermissionMiddleware::class . ':hr.employees.manage_status')
                ->name('hr.employees.status');
        });

        // --- Notes ---
        Route::get('/notes', [NoteController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':hr.notes.view')
            ->name('hr.notes.index');
        Route::get('/notes/{id}', [NoteController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':hr.notes.view')
            ->name('hr.notes.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':hr'])->group(function () {
            Route::post('/notes', [NoteController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':hr.notes.create')
                ->name('hr.notes.store');
            Route::patch('/notes/{id}', [NoteController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':hr.notes.edit')
                ->name('hr.notes.update');
            Route::delete('/notes/{id}', [NoteController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':hr.notes.delete')
                ->name('hr.notes.destroy');
        });

        // --- Leave Types ---
        Route::get('/leave-types', [LeaveTypeController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':hr.leave_types.view')
            ->name('hr.leave-types.index');
        Route::get('/leave-types/{id}', [LeaveTypeController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':hr.leave_types.view')
            ->name('hr.leave-types.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':hr'])->group(function () {
            Route::post('/leave-types', [LeaveTypeController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':hr.leave_types.create')
                ->name('hr.leave-types.store');
            Route::patch('/leave-types/{id}', [LeaveTypeController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':hr.leave_types.edit')
                ->name('hr.leave-types.update');
            Route::delete('/leave-types/{id}', [LeaveTypeController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':hr.leave_types.delete')
                ->name('hr.leave-types.destroy');
        });

        // --- Holidays ---
        Route::get('/holidays', [HolidayController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':hr.holidays.view')
            ->name('hr.holidays.index');
        Route::get('/holidays/{id}', [HolidayController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':hr.holidays.view')
            ->name('hr.holidays.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':hr'])->group(function () {
            Route::post('/holidays', [HolidayController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':hr.holidays.create')
                ->name('hr.holidays.store');
            Route::patch('/holidays/{id}', [HolidayController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':hr.holidays.edit')
                ->name('hr.holidays.update');
            Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':hr.holidays.delete')
                ->name('hr.holidays.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Permissions & Settings Routes
    |--------------------------------------------------------------------------
    |
    |   GET    /api/v1/auth/permissions             - Current user's permission map
    |   GET    /api/v1/settings/permissions          - Full permissions matrix (super_admin)
    |   PATCH  /api/v1/settings/permissions/{id}     - Toggle permission (super_admin)
    |   PATCH  /api/v1/settings/permissions/bulk     - Bulk toggle permissions (super_admin)
    |
    | System Settings:
    |   GET    /api/v1/settings/general/public        - Public settings (no auth)
    |   GET    /api/v1/settings/general               - All settings (super_admin)
    |   GET    /api/v1/settings/general/{key}          - Single setting (super_admin)
    |   PATCH  /api/v1/settings/general/{key}          - Update setting (super_admin)
    |   PATCH  /api/v1/settings/general/bulk           - Bulk update settings (super_admin)
    |
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class])->group(function () {

        // Current user's permissions (any authenticated user)
        Route::get('/auth/permissions', [PermissionsController::class, 'myPermissions'])
            ->name('auth.permissions');

        // Settings management (super_admin only)
        Route::middleware([RoleMiddleware::class . ':super_admin'])->prefix('settings')->group(function () {
            Route::get('/permissions', [PermissionsController::class, 'index'])
                ->name('settings.permissions.index');
            Route::middleware(['throttle:60,1'])->group(function () {
                Route::patch('/permissions/bulk', [PermissionsController::class, 'bulkUpdate'])
                    ->name('settings.permissions.bulk');
                Route::patch('/permissions/{id}', [PermissionsController::class, 'update'])
                    ->name('settings.permissions.update');
            });

            // System settings
            Route::get('/general', [SystemSettingsController::class, 'index'])
                ->name('settings.general.index');
            Route::get('/general/{key}', [SystemSettingsController::class, 'show'])
                ->name('settings.general.show');
            Route::middleware(['throttle:60,1'])->group(function () {
                Route::patch('/general/bulk', [SystemSettingsController::class, 'bulkUpdate'])
                    ->name('settings.general.bulk');
                Route::patch('/general/{key}', [SystemSettingsController::class, 'update'])
                    ->name('settings.general.update');
            });

            // Audit Log
            Route::get('/audit-log', [AuditLogController::class, 'index'])
                ->name('settings.audit-log.index');

            // Roles
            Route::get('/roles', [RolesController::class, 'index'])
                ->name('settings.roles.index');
            Route::get('/roles/{slug}', [RolesController::class, 'show'])
                ->name('settings.roles.show');

            // Data & Backup
            Route::get('/export', [DataManagementController::class, 'export'])
                ->name('settings.export');
            Route::post('/import', [DataManagementController::class, 'import'])
                ->name('settings.import');
            Route::post('/backup', [DataManagementController::class, 'backup'])
                ->name('settings.backup');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Public Settings (no auth required)
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/general/public', [SystemSettingsController::class, 'publicSettings'])
        ->name('settings.general.public');

    /*
    |--------------------------------------------------------------------------
    | Procurement Module Routes (Authenticated, Permission-Controlled)
    |--------------------------------------------------------------------------
    |
    | Vendors:
    |   GET    /api/v1/procurement/vendors                              - List vendors
    |   GET    /api/v1/procurement/vendors/{id}                         - Get vendor
    |   POST   /api/v1/procurement/vendors                              - Create vendor
    |   PATCH  /api/v1/procurement/vendors/{id}                         - Update vendor
    |   DELETE /api/v1/procurement/vendors/{id}                         - Delete vendor
    |
    | Purchase Orders:
    |   GET    /api/v1/procurement/purchase-orders                      - List purchase orders
    |   GET    /api/v1/procurement/purchase-orders/{id}                 - Get purchase order
    |   POST   /api/v1/procurement/purchase-orders                      - Create purchase order
    |   PATCH  /api/v1/procurement/purchase-orders/{id}                 - Update purchase order
    |   DELETE /api/v1/procurement/purchase-orders/{id}                 - Cancel purchase order
    |   POST   /api/v1/procurement/purchase-orders/{id}/submit          - Submit for approval
    |   PATCH  /api/v1/procurement/purchase-orders/{id}/approval        - Approve/reject current step
    |   GET    /api/v1/procurement/purchase-orders/{id}/approval-status - Approval steps status
    |   GET    /api/v1/procurement/purchase-orders/{id}/disbursements   - List disbursements
    |   POST   /api/v1/procurement/purchase-orders/{id}/disbursements   - Record disbursement
    |
    | Inventory:
    |   GET    /api/v1/procurement/inventory                            - List inventory items
    |   GET    /api/v1/procurement/inventory/{id}                       - Get inventory item
    |   POST   /api/v1/procurement/inventory                            - Create inventory item
    |   PATCH  /api/v1/procurement/inventory/{id}                       - Update inventory item
    |   DELETE /api/v1/procurement/inventory/{id}                       - Deactivate inventory item
    |
    | Requisitions:
    |   GET    /api/v1/procurement/requisitions                         - List requisitions
    |   GET    /api/v1/procurement/requisitions/{id}                    - Get requisition
    |   POST   /api/v1/procurement/requisitions                         - Create requisition
    |   PATCH  /api/v1/procurement/requisitions/{id}                    - Update requisition
    |   DELETE /api/v1/procurement/requisitions/{id}                    - Cancel requisition
    |   POST   /api/v1/procurement/requisitions/{id}/submit             - Submit for approval
    |   PATCH  /api/v1/procurement/requisitions/{id}/approval           - Approve/reject current step
    |   GET    /api/v1/procurement/requisitions/{id}/approval-status    - Approval steps status
    |
    | BOQ:
    |   POST   /api/v1/procurement/boq/{id}/submit                      - Submit BOQ for approval
    |   PATCH  /api/v1/procurement/boq/{id}/approval                    - Approve/revise/reject BOQ
    |   GET    /api/v1/procurement/boq/{id}/audit-log                   - BOQ state-transition trail
    |
    | Cross-cutting:
    |   GET    /api/v1/procurement/pending-approvals                    - Items pending user's approval
    |
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class, CacheResponse::class . ':procurement,60'])->prefix('procurement')->group(function () {

        // --- Vendors ---
        Route::get('/vendors', [VendorController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.vendors.view')
            ->name('procurement.vendors.index');
        Route::get('/vendors/{id}', [VendorController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.vendors.view')
            ->name('procurement.vendors.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/vendors', [VendorController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.vendors.create')
                ->name('procurement.vendors.store');
            Route::patch('/vendors/{id}', [VendorController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.vendors.edit')
                ->name('procurement.vendors.update');
            Route::delete('/vendors/{id}', [VendorController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.vendors.delete')
                ->name('procurement.vendors.destroy');
        });

        // --- Purchase Orders ---
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.view')
            ->name('procurement.purchase-orders.index');
        Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.view')
            ->name('procurement.purchase-orders.show');
        Route::get('/purchase-orders/{id}/approval-status', [PurchaseOrderController::class, 'approvalStatus'])
            ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.view')
            ->name('procurement.purchase-orders.approval-status');
        Route::get('/purchase-orders/{id}/disbursements', [DisbursementController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.disbursements.view')
            ->name('procurement.purchase-orders.disbursements.index');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.create')
                ->name('procurement.purchase-orders.store');
            Route::patch('/purchase-orders/{id}', [PurchaseOrderController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.edit')
                ->name('procurement.purchase-orders.update');
            Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.delete')
                ->name('procurement.purchase-orders.destroy');
            Route::post('/purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit'])
                ->middleware(PermissionMiddleware::class . ':procurement.purchase_orders.create')
                ->name('procurement.purchase-orders.submit');
            Route::patch('/purchase-orders/{id}/approval', [ApprovalController::class, 'processPurchaseOrder'])
                ->name('procurement.purchase-orders.approval');
            Route::post('/purchase-orders/{id}/disbursements', [DisbursementController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.disbursements.create')
                ->name('procurement.purchase-orders.disbursements.store');
        });

        // --- Inventory ---
        Route::get('/inventory', [InventoryItemController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.inventory.view')
            ->name('procurement.inventory.index');
        Route::get('/inventory/{id}', [InventoryItemController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.inventory.view')
            ->name('procurement.inventory.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/inventory', [InventoryItemController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.inventory.create')
                ->name('procurement.inventory.store');
            Route::patch('/inventory/{id}', [InventoryItemController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.inventory.edit')
                ->name('procurement.inventory.update');
            Route::delete('/inventory/{id}', [InventoryItemController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.inventory.delete')
                ->name('procurement.inventory.destroy');
        });

        // --- Requisitions ---
        Route::get('/requisitions', [RequisitionController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.requisitions.view')
            ->name('procurement.requisitions.index');
        Route::get('/requisitions/{id}', [RequisitionController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.requisitions.view')
            ->name('procurement.requisitions.show');
        Route::get('/requisitions/{id}/approval-status', [RequisitionController::class, 'approvalStatus'])
            ->middleware(PermissionMiddleware::class . ':procurement.requisitions.view')
            ->name('procurement.requisitions.approval-status');
        Route::get('/requisitions/{id}/audit-log', [RequisitionController::class, 'auditLog'])
            ->middleware(PermissionMiddleware::class . ':procurement.requisitions.view')
            ->name('procurement.requisitions.audit-log');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/requisitions', [RequisitionController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.requisitions.create')
                ->name('procurement.requisitions.store');
            Route::patch('/requisitions/{id}', [RequisitionController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.requisitions.edit')
                ->name('procurement.requisitions.update');
            Route::delete('/requisitions/{id}', [RequisitionController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.requisitions.delete')
                ->name('procurement.requisitions.destroy');
            Route::post('/requisitions/{id}/submit', [RequisitionController::class, 'submit'])
                ->middleware(PermissionMiddleware::class . ':procurement.requisitions.create')
                ->name('procurement.requisitions.submit');
            Route::patch('/requisitions/{id}/approval', [ApprovalController::class, 'processRequisition'])
                ->name('procurement.requisitions.approval');
        });

        // --- Pending Approvals (cross-cutting dashboard) ---
        Route::get('/pending-approvals', [ApprovalController::class, 'pendingApprovals'])
            ->name('procurement.pending-approvals');

        // --- Procurement Stats ---
        Route::get('/stats', [ProcurementStatsController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.vendors.view')
            ->name('procurement.stats');

        // --- Vendor Categories ---
        Route::get('/vendor-categories', [VendorCategoryController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.vendor_categories.view')
            ->name('procurement.vendor-categories.index');
        Route::get('/vendor-categories/{id}', [VendorCategoryController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.vendor_categories.view')
            ->name('procurement.vendor-categories.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/vendor-categories', [VendorCategoryController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.vendor_categories.create')
                ->name('procurement.vendor-categories.store');
            Route::patch('/vendor-categories/{id}', [VendorCategoryController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.vendor_categories.edit')
                ->name('procurement.vendor-categories.update');
            Route::delete('/vendor-categories/{id}', [VendorCategoryController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.vendor_categories.delete')
                ->name('procurement.vendor-categories.destroy');
        });

        // --- BOQ (Bill of Quantities) ---
        Route::get('/boq', [BoqController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.boq.view')
            ->name('procurement.boq.index');
        Route::get('/boq/{id}', [BoqController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.boq.view')
            ->name('procurement.boq.show');
        Route::get('/boq/{id}/audit-log', [BoqController::class, 'auditLog'])
            ->middleware(PermissionMiddleware::class . ':procurement.boq.view')
            ->name('procurement.boq.audit-log');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/boq', [BoqController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.boq.create')
                ->name('procurement.boq.store');
            Route::patch('/boq/{id}', [BoqController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.boq.edit')
                ->name('procurement.boq.update');
            Route::delete('/boq/{id}', [BoqController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.boq.delete')
                ->name('procurement.boq.destroy');
            Route::post('/boq/{id}/submit', [BoqController::class, 'submit'])
                ->middleware(PermissionMiddleware::class . ':procurement.boq.edit')
                ->name('procurement.boq.submit');
            Route::patch('/boq/{id}/approval', [BoqController::class, 'approval'])
                ->middleware(PermissionMiddleware::class . ':procurement.boq.approve')
                ->name('procurement.boq.approval');
        });

        // --- RFQ (Request for Quotation) ---
        Route::get('/rfq', [RfqController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.rfq.view')
            ->name('procurement.rfq.index');
        Route::get('/rfq/{id}', [RfqController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.rfq.view')
            ->name('procurement.rfq.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/rfq', [RfqController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.rfq.create')
                ->name('procurement.rfq.store');
            Route::patch('/rfq/{id}', [RfqController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.rfq.edit')
                ->name('procurement.rfq.update');
            Route::delete('/rfq/{id}', [RfqController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.rfq.delete')
                ->name('procurement.rfq.destroy');
        });

        // --- GRN (Goods Received Note) ---
        Route::get('/grn', [GrnController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.grn.view')
            ->name('procurement.grn.index');
        Route::get('/grn/{id}', [GrnController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.grn.view')
            ->name('procurement.grn.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/grn', [GrnController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.grn.create')
                ->name('procurement.grn.store');
            Route::patch('/grn/{id}', [GrnController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.grn.edit')
                ->name('procurement.grn.update');
            Route::delete('/grn/{id}', [GrnController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.grn.delete')
                ->name('procurement.grn.destroy');
        });

        // --- RFP (Request for Payment) ---
        Route::get('/rfp', [RfpController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':procurement.rfp.view')
            ->name('procurement.rfp.index');
        Route::get('/rfp/{id}', [RfpController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':procurement.rfp.view')
            ->name('procurement.rfp.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':procurement'])->group(function () {
            Route::post('/rfp', [RfpController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':procurement.rfp.create')
                ->name('procurement.rfp.store');
            Route::patch('/rfp/{id}', [RfpController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':procurement.rfp.edit')
                ->name('procurement.rfp.update');
            Route::delete('/rfp/{id}', [RfpController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':procurement.rfp.delete')
                ->name('procurement.rfp.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Projects Module
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class, CacheResponse::class . ':projects,60'])->prefix('projects')->group(function () {
        // Budget Categories (super_admin only for CUD, view for permitted users)
        Route::get('/budget-categories', [BudgetCategoryController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':projects.budget_categories.view');
        Route::get('/budget-categories/{id}', [BudgetCategoryController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':projects.budget_categories.view');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':projects'])->group(function () {
            Route::post('/budget-categories', [BudgetCategoryController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.budget_categories.create');
            Route::patch('/budget-categories/{id}', [BudgetCategoryController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.budget_categories.edit');
            Route::delete('/budget-categories/{id}', [BudgetCategoryController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.budget_categories.delete');
        });

        // Project Stats
        Route::get('/stats', [ProjectController::class, 'stats'])
            ->middleware(PermissionMiddleware::class . ':projects.projects.view');

        // Projects CRUD
        Route::get('/', [ProjectController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':projects.projects.view');
        Route::get('/{id}', [ProjectController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':projects.projects.view');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':projects'])->group(function () {
            Route::post('/', [ProjectController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.create');
            Route::patch('/{id}', [ProjectController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::delete('/{id}', [ProjectController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.delete');

            // Project Donors
            Route::get('/{projectId}/donors', [ProjectDonorController::class, 'index'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.view');
            Route::post('/{projectId}/donors', [ProjectDonorController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::patch('/{projectId}/donors/{donorId}', [ProjectDonorController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::delete('/{projectId}/donors/{donorId}', [ProjectDonorController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');

            // Project Partners
            Route::get('/{projectId}/partners', [ProjectPartnerController::class, 'index'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.view');
            Route::post('/{projectId}/partners', [ProjectPartnerController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::patch('/{projectId}/partners/{partnerId}', [ProjectPartnerController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::delete('/{projectId}/partners/{partnerId}', [ProjectPartnerController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');

            // Project Team Members
            Route::get('/{projectId}/team', [ProjectTeamMemberController::class, 'index'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.view');
            Route::post('/{projectId}/team', [ProjectTeamMemberController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::patch('/{projectId}/team/{memberId}', [ProjectTeamMemberController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');
            Route::delete('/{projectId}/team/{memberId}', [ProjectTeamMemberController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.projects.edit');

            // Project Activities
            Route::get('/{projectId}/activities', [ProjectActivityController::class, 'index'])
                ->middleware(PermissionMiddleware::class . ':projects.activities.view');
            Route::post('/{projectId}/activities', [ProjectActivityController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.activities.create');
            Route::patch('/{projectId}/activities/{activityId}', [ProjectActivityController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.activities.edit');
            Route::delete('/{projectId}/activities/{activityId}', [ProjectActivityController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.activities.delete');

            // Project Budget Lines
            Route::get('/{projectId}/budget-lines', [ProjectBudgetLineController::class, 'index'])
                ->middleware(PermissionMiddleware::class . ':projects.budget.view');
            Route::post('/{projectId}/budget-lines', [ProjectBudgetLineController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.budget.create');
            Route::patch('/{projectId}/budget-lines/{lineId}', [ProjectBudgetLineController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.budget.edit');
            Route::delete('/{projectId}/budget-lines/{lineId}', [ProjectBudgetLineController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.budget.delete');

            // Project Notes
            Route::get('/{projectId}/notes', [ProjectNoteController::class, 'index'])
                ->middleware(PermissionMiddleware::class . ':projects.notes.view');
            Route::post('/{projectId}/notes', [ProjectNoteController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':projects.notes.create');
            Route::patch('/{projectId}/notes/{noteId}', [ProjectNoteController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':projects.notes.edit');
            Route::delete('/{projectId}/notes/{noteId}', [ProjectNoteController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':projects.notes.delete');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Communication Module Routes (Authenticated, Permission-Controlled)
    |--------------------------------------------------------------------------
    |
    | Messages (1-on-1 with threading):
    |   GET    /api/v1/communication/messages                                  - List threads (inbox/sent)
    |   GET    /api/v1/communication/messages/unread-count                     - Unread badge count
    |   GET    /api/v1/communication/messages/{threadId}                       - View thread + replies
    |   POST   /api/v1/communication/messages                                 - Send message / reply
    |   DELETE /api/v1/communication/messages/{id}                             - Delete message (for self)
    |
    | Forums:
    |   GET    /api/v1/communication/forums                                    - List accessible forums
    |   GET    /api/v1/communication/forums/{id}                               - View forum details
    |   POST   /api/v1/communication/forums                                    - Create forum (admin)
    |   PATCH  /api/v1/communication/forums/{id}                               - Update forum (admin)
    |   DELETE /api/v1/communication/forums/{id}                               - Archive forum (admin)
    |   GET    /api/v1/communication/forums/{forumId}/messages                 - List forum messages
    |   GET    /api/v1/communication/forums/{forumId}/messages/{id}/replies    - List replies to a message
    |   POST   /api/v1/communication/forums/{forumId}/messages                 - Post in forum
    |   DELETE /api/v1/communication/forums/{forumId}/messages/{id}            - Delete forum message
    |
    | Notices:
    |   GET    /api/v1/communication/notices/stats                             - Notice stats (admin)
    |   GET    /api/v1/communication/notices                                   - List notices
    |   GET    /api/v1/communication/notices/{id}                              - View notice (auto-marks read)
    |   POST   /api/v1/communication/notices                                   - Create notice
    |   PATCH  /api/v1/communication/notices/{id}                              - Update notice
    |   DELETE /api/v1/communication/notices/{id}                              - Delete notice
    |   GET    /api/v1/communication/notices/{id}/reads                        - Who read this notice (admin)
    |
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class, CacheResponse::class . ':communication,30'])->prefix('communication')->group(function () {

        // --- Messages (1-on-1 with threading) ---
        Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])
            ->middleware(PermissionMiddleware::class . ':communication.messages.view');
        Route::get('/messages', [MessageController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':communication.messages.view');
        Route::get('/messages/{threadId}', [MessageController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':communication.messages.view');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':communication'])->group(function () {
            Route::post('/messages', [MessageController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':communication.messages.create');
            Route::delete('/messages/{id}', [MessageController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':communication.messages.delete');
        });

        // --- Forums ---
        Route::get('/forums', [ForumController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':communication.forums.view');
        Route::get('/forums/{id}', [ForumController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':communication.forums.view');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':communication'])->group(function () {
            Route::post('/forums', [ForumController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':communication.forums.manage');
            Route::patch('/forums/{id}', [ForumController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':communication.forums.manage');
            Route::delete('/forums/{id}', [ForumController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':communication.forums.manage');
        });

        // --- Forum Messages ---
        Route::get('/forums/{forumId}/messages', [ForumMessageController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':communication.forums.view');
        Route::get('/forums/{forumId}/messages/{messageId}/replies', [ForumMessageController::class, 'replies'])
            ->middleware(PermissionMiddleware::class . ':communication.forums.view');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':communication'])->group(function () {
            Route::post('/forums/{forumId}/messages', [ForumMessageController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':communication.forums.create');
            Route::delete('/forums/{forumId}/messages/{messageId}', [ForumMessageController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':communication.forums.create');
        });

        // --- Notices ---
        Route::get('/notices/stats', [NoticeController::class, 'stats'])
            ->middleware(PermissionMiddleware::class . ':communication.notices.view');
        Route::get('/notices', [NoticeController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':communication.notices.view');
        Route::get('/notices/{id}', [NoticeController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':communication.notices.view');
        Route::get('/notices/{id}/reads', [NoticeController::class, 'reads'])
            ->middleware(PermissionMiddleware::class . ':communication.notices.view');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':communication'])->group(function () {
            Route::post('/notices', [NoticeController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':communication.notices.create');
            Route::patch('/notices/{id}', [NoticeController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':communication.notices.edit');
            Route::delete('/notices/{id}', [NoticeController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':communication.notices.delete');
        });

        // --- Emails ---
        Route::get('/emails', [EmailController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':communication.emails.view')
            ->name('communication.emails.index');
        Route::middleware(['throttle:30,1', InvalidateCache::class . ':communication'])->group(function () {
            Route::post('/emails', [EmailController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':communication.emails.create')
                ->name('communication.emails.store');
            Route::delete('/emails/{id}', [EmailController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':communication.emails.delete')
                ->name('communication.emails.destroy');
        });

        // --- SMS ---
        Route::get('/sms', [SmsController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':communication.sms.view')
            ->name('communication.sms.index');
        Route::middleware(['throttle:30,1', InvalidateCache::class . ':communication'])->group(function () {
            Route::post('/sms', [SmsController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':communication.sms.create')
                ->name('communication.sms.store');
            Route::delete('/sms/{id}', [SmsController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':communication.sms.delete')
                ->name('communication.sms.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Reports Module (Authenticated, Permission-Controlled)
    |--------------------------------------------------------------------------
    |
    | HR Reports:
    |   GET /api/v1/reports/hr/employees                 - Employee directory report
    |   GET /api/v1/reports/hr/departments               - Department summary report
    |   GET /api/v1/reports/hr/designations              - Designation summary report
    |
    | Procurement Reports:
    |   GET /api/v1/reports/procurement/purchase-orders   - Purchase orders report
    |   GET /api/v1/reports/procurement/requisitions      - Requisitions report
    |   GET /api/v1/reports/procurement/vendors           - Vendor summary report
    |   GET /api/v1/reports/procurement/inventory         - Inventory report
    |   GET /api/v1/reports/procurement/disbursements     - Disbursements report
    |
    | Project Reports:
    |   GET /api/v1/reports/projects/summary              - Projects summary report
    |   GET /api/v1/reports/projects/{id}/detail          - Full project detail download
    |   GET /api/v1/reports/projects/budget-utilization   - Budget utilization report
    |   GET /api/v1/reports/projects/activity-progress    - Activity progress report
    |
    | Communication Reports:
    |   GET /api/v1/reports/communication/notices         - Notices report
    |   GET /api/v1/reports/communication/forum-activity  - Forum activity report
    |
    | Finance Reports:
    |   GET /api/v1/reports/finance/overview              - Cross-module financial overview
    |
    | Audit Reports:
    |   GET /api/v1/reports/audit/logs                    - System audit log report
    |   GET /api/v1/reports/audit/login-history           - Login history report
    |
    | Query Parameters (all endpoints):
    |   ?format=csv|excel|pdf   - Download in specified format
    |   (no format)             - JSON preview with pagination
    |
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class, CacheResponse::class . ':reports,180'])->prefix('reports')->group(function () {

        // --- HR Reports ---
        Route::prefix('hr')->group(function () {
            Route::get('/employees', [HRReportController::class, 'employees'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.hr.employees');
            Route::get('/departments', [HRReportController::class, 'departments'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.hr.departments');
            Route::get('/designations', [HRReportController::class, 'designations'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.hr.designations');
        });

        // --- Procurement Reports ---
        Route::prefix('procurement')->group(function () {
            Route::get('/purchase-orders', [ProcurementReportController::class, 'purchaseOrders'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.procurement.purchase-orders');
            Route::get('/requisitions', [ProcurementReportController::class, 'requisitions'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.procurement.requisitions');
            Route::get('/vendors', [ProcurementReportController::class, 'vendors'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.procurement.vendors');
            Route::get('/inventory', [ProcurementReportController::class, 'inventory'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.procurement.inventory');
            Route::get('/disbursements', [ProcurementReportController::class, 'disbursements'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.procurement.disbursements');
        });

        // --- Project Reports ---
        Route::prefix('projects')->group(function () {
            Route::get('/summary', [ProjectReportController::class, 'summary'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.projects.summary');
            Route::get('/{id}/detail', [ProjectReportController::class, 'detail'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.download')
                ->name('reports.projects.detail');
            Route::get('/budget-utilization', [ProjectReportController::class, 'budgetUtilization'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.projects.budget-utilization');
            Route::get('/activity-progress', [ProjectReportController::class, 'activityProgress'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.projects.activity-progress');
        });

        // --- Communication Reports ---
        Route::prefix('communication')->group(function () {
            Route::get('/notices', [CommunicationReportController::class, 'notices'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.communication.notices');
            Route::get('/forum-activity', [CommunicationReportController::class, 'forumActivity'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.communication.forum-activity');
        });

        // --- Finance Reports ---
        Route::prefix('finance')->group(function () {
            Route::get('/overview', [FinanceReportController::class, 'overview'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.view')
                ->name('reports.finance.overview');
        });

        // --- Audit Reports ---
        Route::prefix('audit')->group(function () {
            Route::get('/logs', [AuditReportController::class, 'logs'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.audit')
                ->name('reports.audit.logs');
            Route::get('/login-history', [AuditReportController::class, 'loginHistory'])
                ->middleware(PermissionMiddleware::class . ':reports.reports.audit')
                ->name('reports.audit.login-history');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Programs Module Routes (Authenticated, Permission-Controlled)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class, CacheResponse::class . ':programs,60'])->prefix('programs')->group(function () {

        // --- Beneficiaries ---
        Route::get('/beneficiaries', [BeneficiaryController::class, 'index'])
            ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.view')
            ->name('programs.beneficiaries.index');
        Route::get('/beneficiaries/{id}', [BeneficiaryController::class, 'show'])
            ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.view')
            ->name('programs.beneficiaries.show');
        Route::middleware(['throttle:60,1', InvalidateCache::class . ':programs'])->group(function () {
            Route::post('/beneficiaries', [BeneficiaryController::class, 'store'])
                ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.create')
                ->name('programs.beneficiaries.store');
            Route::patch('/beneficiaries/{id}', [BeneficiaryController::class, 'update'])
                ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.edit')
                ->name('programs.beneficiaries.update');
            Route::delete('/beneficiaries/{id}', [BeneficiaryController::class, 'destroy'])
                ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.delete')
                ->name('programs.beneficiaries.destroy');
        });

        // --- Program Reports ---
        Route::get('/reports/summary', [ProgramReportsController::class, 'summary'])
            ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.view')
            ->name('programs.reports.summary');
        Route::get('/reports/export', [ProgramReportsController::class, 'export'])
            ->middleware(PermissionMiddleware::class . ':programs.beneficiaries.view')
            ->name('programs.reports.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Help Center Routes (Authenticated)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', ForcePasswordChange::class])->prefix('help')->group(function () {
        Route::get('/articles', [HelpCenterController::class, 'articles'])
            ->name('help.articles.index');
        Route::get('/articles/{id}', [HelpCenterController::class, 'showArticle'])
            ->name('help.articles.show');
        Route::post('/tickets', [HelpCenterController::class, 'submitTicket'])
            ->middleware('throttle:10,1')
            ->name('help.tickets.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    */
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'CASI360 API is running',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
        ]);
    })->name('health');
});
