<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/v1/auth/login
     * 
     * Authenticate user with email + password.
     * Uses Sanctum cookie-based session auth for the SPA.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $user = User::where('email', $request->email)->first();

        // Check if user exists and password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request), 300); // 5 min decay

            // Record failed login
            LoginHistory::recordFailure(
                $request->email,
                $request->ip(),
                $request->userAgent()
            );

            AuditLog::record(
                null,
                'login_failed',
                'user',
                null,
                null,
                null,
                ['email' => $request->email, 'ip' => $request->ip()]
            );

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if account is active
        if ($user->status !== 'active') {
            AuditLog::record(
                $user->id,
                'login_blocked_inactive',
                'user',
                $user->id
            );

            return $this->error('Your account has been deactivated. Contact an administrator.', 403);
        }

        // Clear rate limiter on success
        RateLimiter::clear($this->throttleKey($request));

        // Regenerate session to prevent session fixation (only for stateful/SPA requests)
        if ($request->hasSession()) {
            $request->session()->regenerate();
            // Log the user in via session (stateful SPA requests)
            Auth::guard('web')->login($user, $request->boolean('remember', false));
        }

        // Record successful login
        $user->recordLogin($request->ip(), $request->userAgent());

        AuditLog::record(
            $user->id,
            'login_success',
            'user',
            $user->id,
            null,
            null,
            ['ip' => $request->ip()]
        );

        return $this->success([
            'user' => $user->toAuthArray(),
        ], 'Login successful');
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $userId = Auth::id();

        AuditLog::record($userId, 'logout', 'user', $userId);

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Rate limiting logic.
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        $maxAttempts = (int) env('LOGIN_RATE_LIMIT', 5);

        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [
                "Too many login attempts. Please try again in {$seconds} seconds.",
            ],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }
}
