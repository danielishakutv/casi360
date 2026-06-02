<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Requests\Auth\UpdateUserRoleRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * The password every user is reset to when an admin clicks the
     * "Reset Password" action. Stable, easy to communicate, and paired
     * with force_password_change=true so the user must replace it on
     * their next login. Override via APP_DEFAULT_USER_PASSWORD if a
     * deployment wants something organisation-specific.
     */
    private function defaultPassword(): string
    {
        return (string) (env('APP_DEFAULT_USER_PASSWORD') ?: 'Casi360@Reset');
    }


    /**
     * GET /api/v1/auth/users
     * 
     * List all users (admin only). Paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination (pass per_page=0 to get all without pagination, capped at 100)
        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $users = $query->orderBy('name')->get();
            return $this->success([
                'users' => $users->map->toAuthArray(),
                'meta' => [
                    'total' => $users->count(),
                ],
            ]);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'users' => collect($users->items())->map->toAuthArray(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/auth/users/{id}
     */
    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return $this->success([
            'user' => $user->toAuthArray(),
        ]);
    }

    /**
     * PATCH /api/v1/auth/users/{id}
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $oldValues = $user->only(['name', 'email', 'phone', 'department', 'status']);

        return DB::transaction(function () use ($request, $user, $oldValues) {
            $updateData = $request->only(['name', 'email', 'phone', 'department', 'status']);
            $user->update(array_filter($updateData));

            AuditLog::record(
                auth()->id(),
                'user_updated',
                'user',
                $user->id,
                $oldValues,
                $user->fresh()->only(['name', 'email', 'phone', 'department', 'status'])
            );

            return $this->success([
                'user' => $user->fresh()->toAuthArray(),
            ], 'User updated successfully');
        });
    }

    /**
     * DELETE /api/v1/auth/users/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Cannot delete super admin
        if ($user->is_super_admin) {
            return $this->error('Super admin accounts cannot be deleted.', 403);
        }

        // Cannot delete yourself
        if ($user->id === auth()->id()) {
            return $this->error('You cannot delete your own account from here.', 403);
        }

        return DB::transaction(function () use ($user) {
            $user->update(['status' => 'inactive']);

            AuditLog::record(
                auth()->id(),
                'user_deactivated',
                'user',
                $user->id,
                ['status' => 'active'],
                ['status' => 'inactive']
            );

            return $this->success(null, 'User deactivated successfully');
        });
    }

    /**
     * PATCH /api/v1/auth/users/{id}/role
     */
    public function updateRole(UpdateUserRoleRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $oldRole = $user->role;

        // Only super_admin can assign super_admin role
        if ($request->role === 'super_admin' && !auth()->user()->is_super_admin) {
            return $this->error('Only super admins can assign the super admin role.', 403);
        }

        // Cannot change own role
        if ($user->id === auth()->id()) {
            return $this->error('You cannot change your own role.', 403);
        }

        return DB::transaction(function () use ($request, $user, $oldRole) {
            $user->update(['role' => $request->role]);

            AuditLog::record(
                auth()->id(),
                'role_changed',
                'user',
                $user->id,
                ['role' => $oldRole],
                ['role' => $request->role]
            );

            return $this->success([
                'user' => $user->fresh()->toAuthArray(),
            ], "User role updated from {$oldRole} to {$request->role}");
        });
    }

    /**
     * POST /api/v1/auth/users/{id}/reset-password
     *
     * Reset a user's password to the platform default and force them
     * to choose a new one on next login. Returns the default password
     * in the response so the admin can read it out to the user.
     *
     * Guards:
     *   - Cannot reset your own password (use the profile flow).
     *   - A super_admin's password can only be reset by another super_admin
     *     — admins shouldn't be able to lock super admins out.
     */
    public function resetPassword(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $actor = auth()->user();

        if ($user->id === $actor->id) {
            return $this->error('Use Change Password from your profile to reset your own password.', 403);
        }

        if ($user->is_super_admin && !$actor->is_super_admin) {
            return $this->error('Only a super admin can reset another super admin\'s password.', 403);
        }

        $defaultPassword = $this->defaultPassword();

        return DB::transaction(function () use ($user, $actor, $defaultPassword) {
            $user->update([
                'password'              => Hash::make($defaultPassword),
                'force_password_change' => true,
                'password_changed_at'   => null,
            ]);

            AuditLog::record(
                $actor->id,
                'user_password_reset',
                'user',
                $user->id,
                null,
                ['force_password_change' => true]
            );

            return $this->success([
                'user'             => $user->fresh()->toAuthArray(),
                'default_password' => $defaultPassword,
            ], 'Password reset. The user must change it on next login.');
        });
    }

    /**
     * PATCH /api/v1/auth/users/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::findOrFail($id);
        $oldStatus = $user->status;

        if ($user->id === auth()->id()) {
            return $this->error('You cannot change your own status.', 403);
        }

        return DB::transaction(function () use ($request, $user, $oldStatus) {
            $user->update(['status' => $request->status]);

            AuditLog::record(
                auth()->id(),
                'user_status_changed',
                'user',
                $user->id,
                ['status' => $oldStatus],
                ['status' => $request->status]
            );

            return $this->success([
                'user' => $user->fresh()->toAuthArray(),
            ], "User status changed to {$request->status}");
        });
    }
}
