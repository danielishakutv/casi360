<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeAudience extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'notice_id',
        'audience_type',
        'audience_id',
        'audience_role',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        $label = match ($this->audience_type) {
            'all' => 'Everyone',
            'department' => Department::find($this->audience_id)?->name ?? 'Unknown Department',
            'role' => ucfirst(str_replace('_', ' ', $this->audience_role ?? '')),
            default => 'Unknown',
        };

        return [
            'id' => $this->id,
            'audience_type' => $this->audience_type,
            'audience_id' => $this->audience_id,
            'audience_role' => $this->audience_role,
            'label' => $label,
        ];
    }
}
