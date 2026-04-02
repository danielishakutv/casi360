<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'department_id',
        'requested_by',
        'submitted_by',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'notes',
        'pr_reference',
        'rfq_reference',
        'deliver_name',
        'deliver_address',
        'deliver_position',
        'deliver_contact',
        'payment_terms',
        'delivery_terms',
        'remarks',
        'delivery_charges',
        'signoffs',
        'status',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivery_charges' => 'decimal:2',
            'payment_terms' => 'array',
            'signoffs' => 'array',
        ];
    }

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }

    public function approvalSteps()
    {
        return $this->morphMany(ApprovalStep::class, 'approvable');
    }

    public function disbursements()
    {
        return $this->hasMany(Disbursement::class);
    }

    /* ----------------------------------------------------------------
     * Accessors
     * ---------------------------------------------------------------- */

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getCurrentApprovalStepAttribute(): ?ApprovalStep
    {
        return $this->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    public function getApprovalProgressAttribute(): array
    {
        $steps = $this->approvalSteps()->orderBy('step_order')->get();
        if ($steps->isEmpty()) {
            return ['total' => 0, 'completed' => 0, 'current_step' => null];
        }

        return [
            'total' => $steps->count(),
            'completed' => $steps->where('status', 'approved')->count(),
            'current_step' => $steps->firstWhere('status', 'pending')?->toApiArray(),
        ];
    }

    /* ----------------------------------------------------------------
     * Auto-generate PO number
     * ---------------------------------------------------------------- */

    public static function generatePoNumber(): string
    {
        $prefix = 'PO-' . now()->format('Ym');
        $latest = self::where('po_number', 'like', $prefix . '%')
            ->orderBy('po_number', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->po_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Recalculate totals from items
     * ---------------------------------------------------------------- */

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + (float) $this->tax_amount + (float) $this->delivery_charges - (float) $this->discount_amount,
        ]);
    }

    /* ----------------------------------------------------------------
     * Approval Workflow
     * ---------------------------------------------------------------- */

    public function generateApprovalSteps(): void
    {
        $this->approvalSteps()->delete();

        $amount = (float) $this->total_amount;
        $order = 1;

        $steps = [
            [
                'step_type' => 'manager_review',
                'step_label' => 'Manager Review',
                'required' => true,
            ],
            [
                'step_type' => 'finance_check',
                'step_label' => 'Finance Verification',
                'required' => true,
            ],
            [
                'step_type' => 'operations_approval',
                'step_label' => 'Operations Approval',
                'required' => $amount > (float) SystemSetting::getValue('procurement.approval.operations_threshold', 500000),
            ],
            [
                'step_type' => 'executive_approval',
                'step_label' => 'Executive Director Approval',
                'required' => $amount > (float) SystemSetting::getValue('procurement.approval.executive_threshold', 1000000),
            ],
        ];

        foreach ($steps as $step) {
            if ($step['required']) {
                $this->approvalSteps()->create([
                    'approvable_type' => 'purchase_order',
                    'step_order' => $order++,
                    'step_type' => $step['step_type'],
                    'step_label' => $step['step_label'],
                    'status' => 'pending',
                ]);
            }
        }
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'vendor_id' => $this->vendor_id,
            'vendor' => $this->vendor?->name,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'requested_by' => $this->requested_by,
            'requested_by_name' => $this->requestedBy?->name,
            'submitted_by' => $this->submitted_by,
            'submitted_by_name' => $this->submittedBy?->name,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'actual_delivery_date' => $this->actual_delivery_date?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'pr_reference' => $this->pr_reference,
            'rfq_reference' => $this->rfq_reference,
            'deliver_name' => $this->deliver_name,
            'deliver_address' => $this->deliver_address,
            'deliver_position' => $this->deliver_position,
            'deliver_contact' => $this->deliver_contact,
            'payment_terms' => $this->payment_terms,
            'delivery_terms' => $this->delivery_terms,
            'remarks' => $this->remarks,
            'delivery_charges' => (float) $this->delivery_charges,
            'signoffs' => $this->signoffs,
            'item_count' => $this->item_count,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'approval_progress' => $this->approval_progress,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'items' => $this->items->map->toApiArray()->toArray(),
            'approval_steps' => $this->approvalSteps()->orderBy('step_order')->get()->map->toApiArray()->toArray(),
            'disbursements' => $this->disbursements->map->toApiArray()->toArray(),
        ]);
    }
}
