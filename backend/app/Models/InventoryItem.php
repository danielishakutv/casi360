<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'description',
        'unit',
        'quantity_in_stock',
        'reorder_level',
        'unit_cost',
        'location',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_in_stock' => 'integer',
            'reorder_level' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_in_stock', '<=', 'reorder_level')
                     ->where('reorder_level', '>', 0);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'category' => $this->category,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity_in_stock' => $this->quantity_in_stock,
            'reorder_level' => $this->reorder_level,
            'unit_cost' => (float) $this->unit_cost,
            'location' => $this->location,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
