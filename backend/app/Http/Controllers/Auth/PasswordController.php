<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    /**
     * POST /api/v1/auth/change-password
     * 
     * Authenticated user changes their own password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        // Ensure new password is different
        if (Hash::check($request->new_password, $user->password)) {
            return $this->error('New password must be different from current password.', 422);
        }

        return DB::transaction(function () use ($request, $user) {
            $user->update([
                'password' => Hash::make($request->new_password),
                'password_changed_at' => now(),
                'force_password_change' => false,
            ]);

            AuditLog::record(
                $user->id,
                'password_changed',
                'user',
                $user->id
            );

            return $this->success(null, 'Password changed successfully');
        });
    }

    /**
     * POST /api/v1/auth/forgot-password
     * 
     * Send password reset link to email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Always return success to prevent email enumeration
        AuditLog::record(
            null,
            'password_reset_requested',
            'user',
            null,
            null,
            null,
            ['email' => $request->email, 'ip' => $request->ip()]
        );

        return $this->success(null, 'If an account exists with that email, a password reset link has been sent.');
    }

    /**
     * POST /api/v1/auth/reset-password
     * 
     * Reset password using token from email.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_changed_at' => now(),
                    'force_password_change' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLog::record(
                    $user->id,
                    'password_reset_completed',
                    'user',
                    $user->id
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error('Invalid or expired reset token.', 422);
        }

        return $this->success(null, 'Password has been reset successfully. You can now log in.');
    }
}
