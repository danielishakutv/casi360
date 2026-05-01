<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuids;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'po_id',
        'vendor_id',
        'amount',
        'currency',
        'invoice_date',
        'due_date',
        'notes',
        'status',
        'created_by',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'approved_at'  => 'datetime',
        'amount'       => 'decimal:2',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rfps()
    {
        return $this->hasMany(Rfp::class);
    }

    /* ----------------------------------------------------------------
     * API serialisation
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id'              => $this->id,
            'invoice_number'  => $this->invoice_number,
            'po_id'           => $this->po_id,
            'po_number'       => $this->purchaseOrder?->po_number,
            'vendor_id'       => $this->vendor_id,
            'vendor_name'     => $this->vendor?->name,
            'amount'          => (float) $this->amount,
            'currency'        => $this->currency,
            'invoice_date'    => $this->invoice_date?->toDateString(),
            'due_date'        => $this->due_date?->toDateString(),
            'notes'           => $this->notes,
            'status'          => $this->status,
            'created_by'      => $this->created_by,
            'created_by_name' => $this->creator?->name,
            'approved_by'     => $this->approved_by,
            'approved_by_name'=> $this->approver?->name,
            'approved_at'     => $this->approved_at?->toIso8601String(),
            'rejected_reason' => $this->rejected_reason,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'rfp_count' => $this->rfps()->count(),
        ]);
    }
}
