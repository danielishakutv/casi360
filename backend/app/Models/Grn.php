<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Grn extends Model
{
    use HasUuids;

    protected $table = 'grns';

    protected $fillable = [
        'grn_number',
        'po_reference',
        'vendor_id',
        'office',
        'received_by',
        'status',
        'received_date',
        'delivery_note_no',
        'notes',
        'signoffs',
    ];

    protected $casts = [
        'received_date' => 'date',
        'signoffs' => 'array',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class);
    }

    /* ----------------------------------------------------------------
     * Auto-generate GRN number
     * ---------------------------------------------------------------- */

    public static function generateGrnNumber(): string
    {
        $prefix = 'GRN-' . date('Ym') . '-';

        $latest = self::where('grn_number', 'like', "{$prefix}%")
            ->orderBy('grn_number', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->grn_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * API serialisation
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'grn_number' => $this->grn_number,
            'po_reference' => $this->po_reference,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor?->name,
            'office' => $this->office,
            'received_by' => $this->received_by,
            'status' => $this->status,
            'received_date' => $this->received_date?->toDateString(),
            'delivery_note_no' => $this->delivery_note_no,
            'notes' => $this->notes,
            'signoffs' => $this->signoffs,
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
