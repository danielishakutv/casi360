<?php

namespace App\Http\Middleware;

use App\Models\RolePermission;
use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: PermissionMiddleware::class . ':hr.notes.create'
     *
     * Super admin always bypasses permission checks.
     * Permission lookups are cached for 5 minutes per role+permission.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Super admin bypasses all permission checks
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        $cacheKey = CacheService::permissionKey($user->role, $permission);
        CacheService::register('permissions', $cacheKey);

        $allowed = Cache::remember($cacheKey, CacheService::ttl('permission'), function () use ($user, $permission) {
            return RolePermission::where('role', $user->role)
                ->whereHas('permission', function ($q) use ($permission) {
                    $q->where('key', $permission);
                })
                ->where('allowed', true)
                ->exists();
        });

        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
