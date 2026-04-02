<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RfpItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'rfp_id',
        'description',
        'project_code',
        'budget_line',
        'quantity',
        'unit_cost',
        'dept',
        'total',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function rfp()
    {
        return $this->belongsTo(Rfp::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'rfp_id' => $this->rfp_id,
            'description' => $this->description,
            'project_code' => $this->project_code,
            'budget_line' => $this->budget_line,
            'quantity' => (int) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'dept' => $this->dept,
            'total' => (float) $this->total,
        ];
    }
}
