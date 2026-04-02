<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionsController extends Controller
{
    /**
     * GET /api/v1/auth/permissions
     *
     * Returns the current user's permission map.
     * Used by the frontend to show/hide UI elements.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super admin gets all permissions as true
        if ($user->role === 'super_admin') {
            $permissions = Permission::orderBy('module')
                ->orderBy('feature')
                ->orderBy('action')
                ->pluck('key')
                ->mapWithKeys(fn($key) => [$key => true]);

            return $this->success([
                'permissions' => $permissions,
            ]);
        }

        $allPermissions = Permission::orderBy('module')
            ->orderBy('feature')
            ->orderBy('action')
            ->get();

        $allowedIds = RolePermission::where('role', $user->role)
            ->where('allowed', true)
            ->pluck('permission_id')
            ->toArray();

        $permissions = $allPermissions->mapWithKeys(function ($perm) use ($allowedIds) {
            return [$perm->key => in_array($perm->id, $allowedIds)];
        });

        return $this->success([
            'permissions' => $permissions,
        ]);
    }

    /**
     * GET /api/v1/settings/permissions
     *
     * Returns all permissions with role mappings (for the settings page matrix).
     * Super admin only.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('module')
            ->orderBy('feature')
            ->orderBy('action')
            ->get();

        $rolePermissions = RolePermission::all()->groupBy('permission_id');
        $roles = ['admin', 'manager', 'staff'];

        $result = $permissions->map(function ($perm) use ($rolePermissions, $roles) {
            $permRoles = $rolePermissions->get($perm->id, collect());

            $rolesMap = [];
            foreach ($roles as $role) {
                $rp = $permRoles->firstWhere('role', $role);
                $rolesMap[$role] = $rp ? (bool) $rp->allowed : false;
            }

            return [
                'id' => $perm->id,
                'module' => $perm->module,
                'feature' => $perm->feature,
                'action' => $perm->action,
                'key' => $perm->key,
                'description' => $perm->description,
                'roles' => $rolesMap,
            ];
        });

        return $this->success([
            'permissions' => $result,
            'roles' => $roles,
        ]);
    }

    /**
     * PATCH /api/v1/settings/permissions/{id}
     *
     * Toggle a specific role's permission.
     * Super admin only.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:admin,manager,staff',
            'allowed' => 'required|boolean',
        ]);

        $permission = Permission::findOrFail($id);

        $response = DB::transaction(function () use ($request, $permission) {
            $oldRp = RolePermission::where('role', $request->role)
                ->where('permission_id', $permission->id)
                ->first();

            $oldAllowed = $oldRp ? $oldRp->allowed : null;

            $rolePermission = RolePermission::updateOrCreate(
                [
                    'role' => $request->role,
                    'permission_id' => $permission->id,
                ],
                [
                    'allowed' => $request->allowed,
                ]
            );

            AuditLog::record(
                $request->user()->id,
                'permission_updated',
                'role_permission',
                $rolePermission->id,
                ['role' => $request->role, 'permission' => $permission->key, 'allowed' => $oldAllowed],
                ['role' => $request->role, 'permission' => $permission->key, 'allowed' => $request->allowed]
            );

            return $this->success([
                'role_permission' => [
                    'id' => $rolePermission->id,
                    'role' => $rolePermission->role,
                    'permission_key' => $permission->key,
                    'allowed' => $rolePermission->allowed,
                ],
            ], 'Permission updated successfully');
        });

        CacheService::invalidatePermissions();

        return $response;
    }

    /**
     * PATCH /api/v1/settings/permissions/bulk
     *
     * Bulk update multiple role permissions at once.
     * Super admin only.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*.permission_id' => 'required|uuid|exists:permissions,id',
            'permissions.*.role' => 'required|in:admin,manager,staff',
            'permissions.*.allowed' => 'required|boolean',
        ]);

        $response = DB::transaction(function () use ($request) {
            $updated = [];

            foreach ($request->permissions as $item) {
                $rolePermission = RolePermission::updateOrCreate(
                    [
                        'role' => $item['role'],
                        'permission_id' => $item['permission_id'],
                    ],
                    [
                        'allowed' => $item['allowed'],
                    ]
                );

                $updated[] = [
                    'id' => $rolePermission->id,
                    'role' => $rolePermission->role,
                    'permission_id' => $rolePermission->permission_id,
                    'allowed' => $rolePermission->allowed,
                ];
            }

            AuditLog::record(
                $request->user()->id,
                'permissions_bulk_updated',
                'role_permission',
                null,
                null,
                ['count' => count($updated)]
            );

            return $this->success([
                'updated' => count($updated),
            ], 'Permissions updated successfully');
        });

        CacheService::invalidatePermissions();

        return $response;
    }
}
