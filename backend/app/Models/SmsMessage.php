<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'sent_by',
        'message',
        'audience',
        'recipient_ids',
        'department_ids',
        'recipient_count',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'department_ids' => 'array',
        'sent_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'sent_by' => $this->sent_by,
            'sender_name' => $this->sender?->name,
            'message' => $this->message,
            'audience' => $this->audience,
            'recipient_ids' => $this->recipient_ids,
            'department_ids' => $this->department_ids,
            'recipient_count' => (int) $this->recipient_count,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
