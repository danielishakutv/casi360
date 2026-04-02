<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'requisition_number',
        'department_id',
        'requested_by',
        'submitted_by',
        'purchase_order_id',
        'title',
        'justification',
        'priority',
        'needed_by',
        'estimated_cost',
        'notes',
        'delivery_location',
        'purchase_scenario',
        'logistics_involved',
        'boq',
        'project_code',
        'donor',
        'currency',
        'exchange_rate',
        'signoffs',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'needed_by' => 'date',
            'estimated_cost' => 'decimal:2',
            'logistics_involved' => 'boolean',
            'boq' => 'boolean',
            'exchange_rate' => 'decimal:4',
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

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function approvalSteps()
    {
        return $this->morphMany(ApprovalStep::class, 'approvable');
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
     * Auto-generate requisition number
     * ---------------------------------------------------------------- */

    public static function generateRequisitionNumber(): string
    {
        $prefix = 'REQ-' . now()->format('Ym');
        $latest = self::where('requisition_number', 'like', $prefix . '%')
            ->orderBy('requisition_number', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->requisition_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Recalculate estimated cost from items
     * ---------------------------------------------------------------- */

    public function recalculateEstimatedCost(): void
    {
        $this->update([
            'estimated_cost' => $this->items()->sum('estimated_total_cost'),
        ]);
    }

    /* ----------------------------------------------------------------
     * Approval Workflow
     * ---------------------------------------------------------------- */

    public function generateApprovalSteps(): void
    {
        $this->approvalSteps()->delete();

        $amount = (float) $this->estimated_cost;
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
                    'approvable_type' => 'requisition',
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
            'requisition_number' => $this->requisition_number,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'requested_by' => $this->requested_by,
            'requested_by_name' => $this->requestedBy?->name,
            'submitted_by' => $this->submitted_by,
            'submitted_by_name' => $this->submittedBy?->name,
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order_number' => $this->purchaseOrder?->po_number,
            'title' => $this->title,
            'justification' => $this->justification,
            'priority' => $this->priority,
            'needed_by' => $this->needed_by?->toDateString(),
            'estimated_cost' => (float) $this->estimated_cost,
            'notes' => $this->notes,
            'delivery_location' => $this->delivery_location,
            'purchase_scenario' => $this->purchase_scenario,
            'logistics_involved' => $this->logistics_involved,
            'boq' => $this->boq,
            'project_code' => $this->project_code,
            'donor' => $this->donor,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'signoffs' => $this->signoffs,
            'item_count' => $this->item_count,
            'status' => $this->status,
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
        ]);
    }
}
