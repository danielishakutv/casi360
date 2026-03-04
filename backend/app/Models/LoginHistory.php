<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'login_successful',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
            'login_successful' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailure(string $email, string $ip, string $userAgent, string $reason = 'invalid_credentials'): self
    {
        return self::create([
            'user_id' => null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'login_at' => now(),
            'login_successful' => false,
            'failure_reason' => $reason . '|email:' . $email,
        ]);
    }
}
