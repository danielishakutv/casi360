<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/auth/session
     * 
     * Get current authenticated user session data.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('Not authenticated', 401);
        }

        return $this->success([
            'authenticated' => true,
            'user' => $user->toAuthArray(),
        ]);
    }

    /**
     * GET /api/v1/auth/profile
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $request->user()->toAuthArray(),
        ]);
    }

    /**
     * PATCH /api/v1/auth/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $oldValues = $user->only(['name', 'phone', 'department']);

        $user->update($request->only(['name', 'phone', 'department']));

        AuditLog::record(
            $user->id,
            'profile_updated',
            'user',
            $user->id,
            $oldValues,
            $user->only(['name', 'phone', 'department'])
        );

        return $this->success([
            'user' => $user->fresh()->toAuthArray(),
        ], 'Profile updated successfully');
    }

    /**
     * DELETE /api/v1/auth/account
     * 
     * Soft-deactivate account (not hard delete for audit trail).
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super admins cannot delete their own account
        if ($user->is_super_admin) {
            return $this->error('Super admin accounts cannot be self-deleted.', 403);
        }

        $user->update(['status' => 'inactive']);

        AuditLog::record(
            $user->id,
            'account_deactivated',
            'user',
            $user->id
        );

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Account deactivated successfully');
    }
}
