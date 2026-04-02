<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'thread_id',
        'subject',
        'body',
        'read_at',
        'sender_deleted_at',
        'recipient_deleted_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'sender_deleted_at' => 'datetime',
        'recipient_deleted_at' => 'datetime',
    ];

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->whereNull('sender_deleted_at');
        })->orWhere(function ($q) use ($userId) {
            $q->where('recipient_id', $userId)->whereNull('recipient_deleted_at');
        });
    }

    public function scopeInbox($query, string $userId)
    {
        return $query->where('recipient_id', $userId)->whereNull('recipient_deleted_at');
    }

    public function scopeSent($query, string $userId)
    {
        return $query->where('sender_id', $userId)->whereNull('sender_deleted_at');
    }

    public function scopeRootMessages($query)
    {
        return $query->whereNull('thread_id');
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function thread()
    {
        return $this->belongsTo(self::class, 'thread_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'thread_id');
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'recipient_id' => $this->recipient_id,
            'recipient_name' => $this->recipient?->name,
            'thread_id' => $this->thread_id,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public function toThreadArray(): array
    {
        return array_merge($this->toApiArray(), [
            'reply_count' => $this->replies()->count(),
            'latest_reply_at' => $this->replies()->latest()->first()?->created_at?->toISOString(),
        ]);
    }
}
