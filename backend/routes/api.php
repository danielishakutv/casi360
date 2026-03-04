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
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Apply security headers to all API routes
|--------------------------------------------------------------------------
*/
Route::middleware([SecurityHeaders::class])->prefix('v1')->group(function () {

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
            Route::middleware([RoleMiddleware::class . ':super_admin,admin'])->group(function () {

                Route::post('/register', [RegisterController::class, 'register'])
                    ->middleware('throttle:' . env('REGISTER_RATE_LIMIT', 3) . ',1')
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
