<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Rfp extends Model
{
    use HasUuids;

    protected $table = 'rfps';

    protected $fillable = [
        'rfp_number',
        'po_reference',
        'grn_reference',
        'project_code',
        'vendor_id',
        'payee',
        'currency',
        'exchange_rate',
        'department',
        'budget_line',
        'date',
        'status',
        'payment_date',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'total_amount',
        'payment_method',
        'bank_details',
        'notes',
        'signoffs',
        'supporting_docs',
    ];

    protected $casts = [
        'date' => 'date',
        'payment_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'signoffs' => 'array',
        'supporting_docs' => 'array',
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
        return $this->hasMany(RfpItem::class);
    }

    /* ----------------------------------------------------------------
     * Auto-generate RFP number
     * ---------------------------------------------------------------- */

    public static function generateRfpNumber(): string
    {
        $prefix = 'RFP-' . date('Ym') . '-';

        $latest = self::where('rfp_number', 'like', "{$prefix}%")
            ->orderBy('rfp_number', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->rfp_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Recalculate totals
     * ---------------------------------------------------------------- */

    public function recalculateTotals(): void
    {
        // If line items exist, sum their totals as subtotal
        if ($this->items()->exists()) {
            $subtotal = (float) $this->items()->sum('total');
            $taxAmount = round($subtotal * ((float) $this->tax_rate / 100), 2);
            $this->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);
        } else {
            $taxAmount = round((float) $this->subtotal * ((float) $this->tax_rate / 100), 2);
            $this->update([
                'tax_amount' => $taxAmount,
                'total_amount' => (float) $this->subtotal + $taxAmount,
            ]);
        }
    }

    /* ----------------------------------------------------------------
     * API serialisation
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'rfp_number' => $this->rfp_number,
            'po_reference' => $this->po_reference,
            'grn_reference' => $this->grn_reference,
            'project_code' => $this->project_code,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor?->name,
            'payee' => $this->payee,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'department' => $this->department,
            'budget_line' => $this->budget_line,
            'date' => $this->date?->toDateString(),
            'status' => $this->status,
            'payment_date' => $this->payment_date?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'tax_rate' => (float) $this->tax_rate,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'bank_details' => $this->bank_details,
            'notes' => $this->notes,
            'signoffs' => $this->signoffs,
            'supporting_docs' => $this->supporting_docs,
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
