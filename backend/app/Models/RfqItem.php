<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RfqItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'rfq_id',
        'item_number',
        'description',
        'unit',
        'quantity',
        'unit_cost',
        'total',
        'vendor_unit_price',
        'vendor_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'vendor_unit_price' => 'decimal:2',
        'vendor_total' => 'decimal:2',
    ];

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'rfq_id' => $this->rfq_id,
            'item_number' => $this->item_number,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => (float) $this->quantity,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'total' => $this->total !== null ? (float) $this->total : null,
            'vendor_unit_price' => $this->vendor_unit_price !== null ? (float) $this->vendor_unit_price : null,
            'vendor_total' => $this->vendor_total !== null ? (float) $this->vendor_total : null,
        ];
    }
}
