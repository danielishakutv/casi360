<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticleEditor extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'added_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public static function userCanManage(?User $user): bool
    {
        if (!$user) return false;
        if ($user->role === 'super_admin') return true;
        return static::where('user_id', $user->id)->exists();
    }
}
