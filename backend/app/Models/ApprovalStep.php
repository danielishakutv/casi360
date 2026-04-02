<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    use HasUuids;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'step_order',
        'step_type',
        'step_label',
        'status',
        'acted_by',
        'acted_at',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('step_type', $type);
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function approvable()
    {
        return $this->morphTo('approvable', 'approvable_type', 'approvable_id')->withDefault();
    }

    public function actedBy()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    public function resolveApprovable(): Model|null
    {
        return match ($this->approvable_type) {
            'purchase_order' => PurchaseOrder::find($this->approvable_id),
            'requisition' => Requisition::find($this->approvable_id),
            default => null,
        };
    }

    public function getPermissionKeyAttribute(): string
    {
        return 'procurement.approval.' . $this->step_type;
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'approvable_type' => $this->approvable_type,
            'approvable_id' => $this->approvable_id,
            'step_order' => $this->step_order,
            'step_type' => $this->step_type,
            'step_label' => $this->step_label,
            'status' => $this->status,
            'acted_by' => $this->acted_by,
            'acted_by_name' => $this->actedBy?->name,
            'acted_at' => $this->acted_at?->toISOString(),
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
