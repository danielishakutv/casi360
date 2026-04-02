<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'date',
        'type',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date?->toDateString(),
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
