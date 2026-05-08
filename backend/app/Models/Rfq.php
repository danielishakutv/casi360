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
        'created_by',
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
        'scope',
        'advertised_on',
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

    /**
     * Every vendor invited to quote on this RFQ. Targeted RFQs have one
     * or more rows here; open-call RFQs have none (the recipient set is
     * "anyone qualified" and only `advertised_on` is captured).
     */
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'rfq_vendors')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
        $vendors = $this->relationLoaded('vendors')
            ? $this->vendors->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'address' => $v->address,
                'phone' => $v->phone,
                'email' => $v->email,
                'contact_person' => $v->contact_person,
            ])->values()->toArray()
            : null;

        return [
            'id' => $this->id,
            'rfq_number' => $this->rfq_number,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator?->name,
            'title' => $this->title,
            'pr_reference' => $this->pr_reference,
            'project_code' => $this->project_code,
            'structure' => $this->structure,
            'currency' => $this->currency,
            'request_types' => $this->request_types,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor?->name,
            'vendors' => $vendors,
            'vendors_count' => $vendors !== null ? count($vendors) : ($this->vendors_count ?? null),
            'supplier_name' => $this->supplier_name,
            'supplier_address' => $this->supplier_address,
            'supplier_phone' => $this->supplier_phone,
            'supplier_email' => $this->supplier_email,
            'contact_person' => $this->contact_person,
            'status' => $this->status,
            'scope' => $this->scope ?? 'targeted',
            'advertised_on' => $this->advertised_on,
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
            'items'     => $this->items->map->toApiArray()->toArray(),
            'audit_log' => AuditLog::trailFor('rfq', $this->id),
        ]);
    }
}
