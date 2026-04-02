<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Rfq extends Model
{
    use HasUuids;

    protected $table = 'rfqs';

    protected $fillable = [
        'rfq_number',
        'title',
        'pr_reference',
        'project_code',
        'structure',
        'currency',
        'request_types',
        'vendor_id',
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'supplier_email',
        'contact_person',
        'status',
        'issue_date',
        'deadline',
        'delivery_address',
        'delivery_date',
        'delivery_terms',
        'payment_terms',
        'notes',
        'signoffs',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'deadline' => 'date',
        'delivery_date' => 'date',
        'request_types' => 'array',
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
        return $this->hasMany(RfqItem::class);
    }

    /* ----------------------------------------------------------------
     * Auto-generate RFQ number
     * ---------------------------------------------------------------- */

    public static function generateRfqNumber(): string
    {
        $prefix = 'RFQ-' . date('Ym') . '-';

        $latest = self::where('rfq_number', 'like', "{$prefix}%")
            ->orderBy('rfq_number', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->rfq_number, strlen($prefix));
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
            'rfq_number' => $this->rfq_number,
            'title' => $this->title,
            'pr_reference' => $this->pr_reference,
            'project_code' => $this->project_code,
            'structure' => $this->structure,
            'currency' => $this->currency,
            'request_types' => $this->request_types,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor?->name,
            'supplier_name' => $this->supplier_name,
            'supplier_address' => $this->supplier_address,
            'supplier_phone' => $this->supplier_phone,
            'supplier_email' => $this->supplier_email,
            'contact_person' => $this->contact_person,
            'status' => $this->status,
            'issue_date' => $this->issue_date?->toDateString(),
            'deadline' => $this->deadline?->toDateString(),
            'delivery_address' => $this->delivery_address,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'delivery_terms' => $this->delivery_terms,
            'payment_terms' => $this->payment_terms,
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
