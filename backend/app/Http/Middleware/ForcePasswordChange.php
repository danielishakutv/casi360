<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force user to change password if flagged.
 * Only allows password change and logout endpoints.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->force_password_change) {
            // Allow only password change and logout
            $allowedRoutes = [
                'api/v1/auth/change-password',
                'api/v1/auth/logout',
                'api/v1/auth/session',
            ];

            $currentPath = $request->path();

            if (!in_array($currentPath, $allowedRoutes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must change your password before continuing.',
                    'code' => 'FORCE_PASSWORD_CHANGE',
                ], 403);
            }
        }

        return $next($request);
    }
}
