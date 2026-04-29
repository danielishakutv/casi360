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
        'project_id',
        'budget_holder_id',
        'title',
        'date',
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
            'date'             => 'date',
            'needed_by'        => 'date',
            'estimated_cost'   => 'decimal:2',
            'logistics_involved' => 'boolean',
            'boq'              => 'boolean',
            'exchange_rate'    => 'decimal:4',
            'signoffs'         => 'array',
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
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function budgetHolder()
    {
        return $this->belongsTo(Employee::class, 'budget_holder_id');
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    /** New 3-stage approval chain (budget_holder → finance → procurement). */
    public function approvals()
    {
        return $this->hasMany(RequisitionApproval::class)->orderBy('stage_order');
    }

    public function auditLogs()
    {
        return $this->hasMany(RequisitionAuditLog::class);
    }

    /**
     * Legacy polymorphic steps — kept for PO compatibility; NOT used for
     * requisition approvals any more.
     */
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

    /**
     * Returns the stage key of the currently-pending approval stage, or null
     * if no stage is pending (fully approved / rejected / not yet submitted).
     */
    public function getActiveStageAttribute(): ?string
    {
        if ($this->relationLoaded('approvals')) {
            return $this->approvals->firstWhere('status', 'pending')?->stage;
        }

        return $this->approvals()->where('status', 'pending')->value('stage');
    }

    public function getApprovalProgressAttribute(): array
    {
        if ($this->relationLoaded('approvals')) {
            $approvals = $this->approvals;
        } else {
            $approvals = $this->approvals()->get();
        }

        if ($approvals->isEmpty()) {
            return ['completed' => 0, 'total' => 0];
        }

        $completed = $approvals->whereIn('status', ['approved', 'forwarded', 'skipped'])->count();

        return [
            'completed' => $completed,
            'total'     => $approvals->count(),
        ];
    }

    /* ----------------------------------------------------------------
     * Auto-generate requisition number  (PR-YYYY-NNNN)
     * ---------------------------------------------------------------- */

    public static function generateRequisitionNumber(): string
    {
        $prefix = 'PR-' . now()->format('Y');
        $latest = self::where('requisition_number', 'like', $prefix . '-%')
            ->orderBy('requisition_number', 'desc')
            ->first();

        $nextNumber = $latest
            ? ((int) substr($latest->requisition_number, -4)) + 1
            : 1;

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
     * Approval Chain — create / reset
     * ---------------------------------------------------------------- */

    /**
     * Create (or reset) the fixed 3-stage approval chain.
     * Called when a PR is submitted or re-submitted after revision.
     */
    public function createApprovalChain(): void
    {
        $this->approvals()->delete();

        $this->approvals()->createMany([
            [
                'stage'       => 'budget_holder',
                'stage_order' => 1,
                'stage_label' => 'Budget Holder',
                'status'      => 'pending',
            ],
            [
                'stage'       => 'finance',
                'stage_order' => 2,
                'stage_label' => 'Finance',
                'status'      => 'waiting',
            ],
            [
                'stage'       => 'procurement',
                'stage_order' => 3,
                'stage_label' => 'Procurement',
                'status'      => 'waiting',
            ],
        ]);
    }

    /* ----------------------------------------------------------------
     * Serialization helpers
     * ---------------------------------------------------------------- */

    /**
     * Compact chain for list / pending-approvals responses.
     * Includes actor_name + decided_at but omits actor_id, position, comments.
     */
    private function getApprovalChainCompact(): array
    {
        if ($this->relationLoaded('approvals')) {
            return $this->approvals->map->toApiArray(false)->toArray();
        }

        return $this->approvals()->get()->map->toApiArray(false)->toArray();
    }

    /**
     * Full chain for detail responses — includes all actor fields and comments.
     */
    private function getApprovalChainDetailed(): array
    {
        if ($this->relationLoaded('approvals')) {
            return $this->approvals->map->toApiArray(true)->toArray();
        }

        return $this->approvals()->get()->map->toApiArray(true)->toArray();
    }

    /* ----------------------------------------------------------------
     * API Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id'                    => $this->id,
            'requisition_number'    => $this->requisition_number,
            'title'                 => $this->title,
            'date'                  => $this->date?->toDateString(),
            'department_id'         => $this->department_id,
            'department'            => $this->department?->name,
            'requested_by'          => $this->requested_by,
            'requested_by_name'     => $this->requestedBy?->name,
            'submitted_by'          => $this->submitted_by,
            'submitted_by_name'     => $this->submittedBy?->name,
            'purchase_order_id'     => $this->purchase_order_id,
            'purchase_order_number' => $this->purchaseOrder?->po_number,
            'project_id'            => $this->project_id,
            'project_name'          => $this->project?->name,
            'project_manager_id'    => $this->project?->project_manager_id,
            'budget_holder_id'      => $this->budget_holder_id,
            'budget_holder_name'    => $this->budgetHolder?->name,
            'budget_holder_email'   => $this->budgetHolder?->email,
            'justification'         => $this->justification,
            'priority'              => $this->priority,
            'needed_by'             => $this->needed_by?->toDateString(),
            'estimated_cost'        => (float) $this->estimated_cost,
            'item_count'            => $this->item_count,
            'notes'                 => $this->notes,
            'delivery_location'     => $this->delivery_location,
            'purchase_scenario'     => $this->purchase_scenario,
            'logistics_involved'    => $this->logistics_involved,
            'boq'                   => $this->boq,
            'project_code'          => $this->project_code,
            'donor'                 => $this->donor,
            'currency'              => $this->currency,
            'exchange_rate'         => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'signoffs'              => $this->signoffs,
            'status'                => $this->status,
            'active_stage'          => $this->active_stage,
            'approval_progress'     => $this->approval_progress,
            'approval_chain'        => $this->getApprovalChainCompact(),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'items'          => $this->items->map->toApiArray()->toArray(),
            'approval_chain' => $this->getApprovalChainDetailed(),
        ]);
    }
}
