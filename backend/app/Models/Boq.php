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
        'currency',
        'exchange_rate',
        'delivery_location',
        'prepared_by',
        'budget_holder_id',
        'created_by',
        'status',
        'date',
        'notes',
        'signoffs',
    ];

    protected $casts = [
        'date' => 'date',
        'exchange_rate' => 'decimal:4',
        'signoffs' => 'array',
    ];

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function items()
    {
        return $this->hasMany(BoqItem::class);
    }

    public function budgetHolder()
    {
        return $this->belongsTo(Employee::class, 'budget_holder_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Approval chain (budget_holder → finance → procurement → operations). */
    public function approvals()
    {
        return $this->hasMany(BoqApproval::class)->orderBy('stage_order');
    }

    /* ----------------------------------------------------------------
     * Approval chain — accessors & creation (ED process §1)
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
     * Create (or reset) the fixed 4-stage approval chain:
     * Budget Holder → Finance → Procurement → Operations (final).
     *
     * When $skipBudgetHolder is true (originator-skip — the preparer IS the
     * budget holder), the Budget Holder stage is recorded as 'skipped' and the
     * chain opens at Finance instead.
     */
    public function createApprovalChain(bool $skipBudgetHolder = false): void
    {
        $this->approvals()->delete();

        $budgetHolderRow = [
            'stage'       => 'budget_holder',
            'stage_order' => 1,
            'stage_label' => 'Budget Holder',
            'status'      => 'pending',
        ];

        $financeStatus = 'waiting';

        if ($skipBudgetHolder) {
            $budgetHolderRow['status']         = 'skipped';
            $budgetHolderRow['actor_name']     = 'System (originator auto-skip)';
            $budgetHolderRow['actor_position'] = 'Automated';
            $budgetHolderRow['comments']       = 'Auto-skipped: the budget holder raised this BOQ (segregation of duties).';
            $budgetHolderRow['decided_at']     = now();
            $financeStatus = 'pending';
        }

        $this->approvals()->createMany([
            $budgetHolderRow,
            ['stage' => 'finance',     'stage_order' => 2, 'stage_label' => 'Finance',     'status' => $financeStatus],
            ['stage' => 'procurement', 'stage_order' => 3, 'stage_label' => 'Procurement', 'status' => 'waiting'],
            ['stage' => 'operations',  'stage_order' => 4, 'stage_label' => 'Operations',  'status' => 'waiting'],
        ]);
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
            'currency' => $this->currency ?? 'USD',
            'exchange_rate' => $this->exchange_rate ? (float) $this->exchange_rate : null,
            'prepared_by' => $this->prepared_by,
            'budget_holder_id' => $this->budget_holder_id,
            'budget_holder_name' => $this->budgetHolder?->name,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'active_stage' => $this->active_stage,
            'approval_progress' => $this->approval_progress,
            'approval_chain' => $this->approvals->map->toApiArray(false)->toArray(),
            'date' => $this->date?->toDateString(),
            'notes' => $this->notes,
            'signoffs' => $this->signoffs,
            // Amounts are stored in the document currency (USD by default).
            // grand_total_ngn is the derived Naira equivalent using the budget
            // exchange rate, for display alongside the USD figure.
            'grand_total' => (float) $this->items()->sum('total'),
            'grand_total_ngn' => $this->exchange_rate
                ? round((float) $this->items()->sum('total') * (float) $this->exchange_rate, 2)
                : null,
            'item_count' => $this->items()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'items'          => $this->items->map->toApiArray()->toArray(),
            'approval_chain' => $this->approvals->map->toApiArray(true)->toArray(),
        ]);
    }
}
