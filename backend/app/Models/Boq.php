<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Boq extends Model
{
    use HasUuids;

    protected $table = 'boqs';

    protected $fillable = [
        'boq_number',
        'title',
        'pr_reference',
        'project_code',
        'department',
        'category',
        'delivery_location',
        'prepared_by',
        'status',
        'date',
        'notes',
        'signoffs',
    ];

    protected $casts = [
        'date' => 'date',
        'signoffs' => 'array',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function items()
    {
        return $this->hasMany(BoqItem::class);
    }

    /* ----------------------------------------------------------------
     * Auto-generate BOQ number
     * ---------------------------------------------------------------- */

    public static function generateBoqNumber(): string
    {
        $year = date('Y');
        $prefix = "BOQ-{$year}-";

        $latest = self::where('boq_number', 'like', "{$prefix}%")
            ->orderBy('boq_number', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->boq_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Recalculate grand total
     * ---------------------------------------------------------------- */

    public function recalculateTotal(): void
    {
        // Individual item totals are stored on the item rows;
        // no header-level total column, so this is a no-op placeholder.
    }

    /* ----------------------------------------------------------------
     * API serialisation
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'boq_number' => $this->boq_number,
            'title' => $this->title,
            'pr_reference' => $this->pr_reference,
            'project_code' => $this->project_code,
            'department' => $this->department,
            'delivery_location' => $this->delivery_location,
            'category' => $this->category,
            'prepared_by' => $this->prepared_by,
            'status' => $this->status,
            'date' => $this->date?->toDateString(),
            'notes' => $this->notes,
            'signoffs' => $this->signoffs,
            'grand_total' => (float) $this->items()->sum('total'),
            'item_count' => $this->items()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'items' => $this->items->map->toApiArray()->toArray(),
        ]);
    }
}
