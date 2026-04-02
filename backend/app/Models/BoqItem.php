<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BoqItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'boq_id',
        'section',
        'description',
        'unit',
        'quantity',
        'unit_rate',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function boq()
    {
        return $this->belongsTo(Boq::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'boq_id' => $this->boq_id,
            'section' => $this->section,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => (float) $this->quantity,
            'unit_rate' => (float) $this->unit_rate,
            'total' => (float) $this->total,
        ];
    }
}
