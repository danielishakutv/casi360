<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * The single definition of what makes an acceptable password.
 *
 * Every endpoint that sets one — registration, change-password,
 * force-change, and reset — validates through here, so the rules cannot
 * drift apart between them. The frontend mirrors these checks live in
 * src/utils/password.js (except uncompromised(), which needs the network).
 */
class PasswordPolicy
{
    /**
     * Minimum length, floored at 8.
     *
     * Read from config rather than env() directly: env() only sees the .env
     * file when the config cache is cold. Under `php artisan config:cache`
     * Laravel skips loading .env entirely, so a bare
     * env('PASSWORD_MIN_LENGTH') would return null — and (int) null is 0,
     * silently dropping the minimum length to nothing. The floor makes that
     * unreachable even if the config value is missing or nonsense.
     */
    public static function minLength(): int
    {
        return max(8, (int) config('auth.password_min_length', 8));
    }

    /**
     * The validation rule to apply to any new password.
     *
     * uncompromised() checks the password against the HaveIBeenPwned corpus
     * over the network. Laravel's verifier fails open — if the API is
     * unreachable the password is accepted — so an outage degrades this to
     * the other four checks rather than locking everyone out.
     */
    public static function rule(): Password
    {
        return Password::min(self::minLength())
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }
}
