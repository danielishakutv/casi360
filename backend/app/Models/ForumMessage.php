<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumMessage extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'forum_id',
        'user_id',
        'body',
        'reply_to_id',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function forum()
    {
        return $this->belongsTo(Forum::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'forum_id' => $this->forum_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'body' => $this->body,
            'reply_to_id' => $this->reply_to_id,
            'reply_count' => $this->replies()->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
