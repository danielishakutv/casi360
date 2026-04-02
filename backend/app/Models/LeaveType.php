<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'days_allowed',
        'carry_over_max',
        'paid',
        'requires_approval',
        'status',
        'description',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'days_allowed' => (int) $this->days_allowed,
            'carry_over_max' => $this->carry_over_max ? (int) $this->carry_over_max : null,
            'paid' => $this->paid,
            'requires_approval' => $this->requires_approval,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
