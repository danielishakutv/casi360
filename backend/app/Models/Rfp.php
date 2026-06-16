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
        'invoice_id',
        'po_reference',
        'grn_reference',
        'pr_references',
        'po_references',
        'grn_references',
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
        'procurement_compliance',
        'compliance_justification',
        'compliance_document_url',
        'compliance_confirmed_by',
        'compliance_confirmed_at',
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
        'pr_references' => 'array',
        'po_references' => 'array',
        'grn_references' => 'array',
        'compliance_confirmed_at' => 'datetime',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(RfpItem::class);
    }

    /** Payment approval chain (programme_manager → finance → final_approver). */
    public function approvals()
    {
        return $this->hasMany(RfpApproval::class)->orderBy('stage_order');
    }

    /* ----------------------------------------------------------------
     * Approval chain — accessors & creation (v2 §3.3)
     * ---------------------------------------------------------------- */

    public function getActiveStageAttribute(): ?string
    {
        if ($this->relationLoaded('approvals')) {
            return $this->approvals->firstWhere('status', 'pending')?->stage;
        }

        return $this->approvals()->where('status', 'pending')->value('stage');
    }

    public function getApprovalProgressAttribute(): array
    {
        $approvals = $this->relationLoaded('approvals') ? $this->approvals : $this->approvals()->get();

        if ($approvals->isEmpty()) {
            return ['completed' => 0, 'total' => 0];
        }

        return [
            'completed' => $approvals->whereIn('status', ['approved', 'skipped'])->count(),
            'total'     => $approvals->count(),
        ];
    }

    /**
     * Create (or reset) the 3-stage payment approval chain:
     * Programme Manager → Finance → Final Approver. The final stage label
     * follows the configured Final Approver (Country Director by default).
     */
    public function createApprovalChain(): void
    {
        $this->approvals()->delete();

        $finalRole  = config('procurement.payment_final_approver', 'country_director');
        $finalLabel = $finalRole === 'operations' ? 'Operations Manager' : 'Country Director';

        $this->approvals()->createMany([
            ['stage' => 'programme_manager', 'stage_order' => 1, 'stage_label' => 'Programme Manager', 'status' => 'pending'],
            ['stage' => 'finance',           'stage_order' => 2, 'stage_label' => 'Finance',           'status' => 'waiting'],
            ['stage' => 'final_approver',    'stage_order' => 3, 'stage_label' => $finalLabel,         'status' => 'waiting'],
        ]);
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
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'po_reference' => $this->po_reference,
            'grn_reference' => $this->grn_reference,
            'pr_references' => $this->pr_references ?? [],
            'po_references' => $this->po_references ?? [],
            'grn_references' => $this->grn_references ?? [],
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
            'procurement_compliance' => $this->procurement_compliance,
            'compliance_justification' => $this->compliance_justification,
            'compliance_document_url' => $this->compliance_document_url,
            'compliance_confirmed_by' => $this->compliance_confirmed_by,
            'compliance_confirmed_at' => $this->compliance_confirmed_at?->toIso8601String(),
            'item_count' => $this->items()->count(),
            'active_stage' => $this->active_stage,
            'approval_progress' => $this->approval_progress,
            'approval_chain' => $this->approvals->map->toApiArray(false)->toArray(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'items'          => $this->items->map->toApiArray()->toArray(),
            'approval_chain' => $this->approvals->map->toApiArray(true)->toArray(),
            'audit_log'      => AuditLog::trailFor('rfp', $this->id),
        ]);
    }
}
