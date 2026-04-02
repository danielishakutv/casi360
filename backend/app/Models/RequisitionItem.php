<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionItem extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'requisition_id',
        'inventory_item_id',
        'description',
        'quantity',
        'unit',
        'estimated_unit_cost',
        'estimated_total_cost',
        'project_code',
        'budget_line',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'estimated_unit_cost' => 'decimal:2',
            'estimated_total_cost' => 'decimal:2',
        ];
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'requisition_id' => $this->requisition_id,
            'inventory_item_id' => $this->inventory_item_id,
            'inventory_item' => $this->inventoryItem?->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'estimated_unit_cost' => (float) $this->estimated_unit_cost,
            'estimated_total_cost' => (float) $this->estimated_total_cost,
            'project_code' => $this->project_code,
            'budget_line' => $this->budget_line,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
