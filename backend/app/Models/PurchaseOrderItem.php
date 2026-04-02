<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'description',
        'quantity',
        'received_quantity',
        'unit',
        'unit_price',
        'total_price',
        'pr_no',
        'project_code',
        'budget_line',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'received_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
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
            'purchase_order_id' => $this->purchase_order_id,
            'inventory_item_id' => $this->inventory_item_id,
            'inventory_item' => $this->inventoryItem?->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'pr_no' => $this->pr_no,
            'project_code' => $this->project_code,
            'budget_line' => $this->budget_line,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
