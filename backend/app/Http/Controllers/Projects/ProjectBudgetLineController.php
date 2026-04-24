<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectBudgetLineRequest;
use App\Http\Requests\Projects\UpdateProjectBudgetLineRequest;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\Project;
use App\Models\ProjectBudgetLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RequisitionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectBudgetLineController extends Controller
{
    public function index(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $query = $project->budgetLines()->with('budgetCategory');

        if ($request->filled('budget_category_id')) {
            $query->where('budget_category_id', $request->budget_category_id);
        }

        $lines = $query->orderBy('created_at')->get();
        $metrics = $this->buildProjectBudgetLineMetrics($projectId, $project->project_code);

        // Group by category for budget summary
        $byCategory = $lines->groupBy('budget_category_id')->map(function ($group) {
            return [
                'budget_category_id' => $group->first()->budget_category_id,
                'budget_category' => $group->first()->budgetCategory?->name,
                'line_count' => $group->count(),
                'subtotal' => (float) $group->sum('total_cost'),
            ];
        })->values();

        return $this->success([
            'budget_lines' => $lines->map(function (ProjectBudgetLine $line) use ($metrics, $project) {
                $lineKey = $this->budgetLineKey($project->project_code, $line->description);
                return array_merge($line->toApiArray(), $metrics[$lineKey] ?? [
                    'committed_amount' => 0,
                    'actual_spent_amount' => 0,
                    'pending_request_amount' => 0,
                    'available_amount' => (float) $line->total_cost,
                    'utilization_percent' => 0,
                    'status' => 'healthy',
                ]);
            })->toArray(),
            'by_category' => $byCategory,
            'total_budget' => (float) $lines->sum('total_cost'),
        ]);
    }

    public function store(StoreProjectBudgetLineRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        return DB::transaction(function () use ($request, $project) {
            $data = $request->validated();
            $data['total_cost'] = round((float) $data['quantity'] * (float) $data['unit_cost'], 2);

            $line = $project->budgetLines()->create($data);
            $line->load('budgetCategory');

            $project->recalculateTotalBudget();

            AuditLog::record(
                auth()->id(),
                'project_budget_line_added',
                'project',
                $project->id,
                null,
                $line->toApiArray()
            );

            return $this->success([
                'budget_line' => $line->toApiArray(),
                'project_total_budget' => (float) $project->fresh()->total_budget,
            ], 'Budget line added successfully', 201);
        });
    }

    public function update(UpdateProjectBudgetLineRequest $request, string $projectId, string $lineId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $line = ProjectBudgetLine::where('project_id', $projectId)->findOrFail($lineId);
        $oldValues = $line->toApiArray();

        return DB::transaction(function () use ($request, $project, $line, $oldValues) {
            $data = $request->validated();

            $quantity = $data['quantity'] ?? $line->quantity;
            $unitCost = $data['unit_cost'] ?? $line->unit_cost;
            $data['total_cost'] = round((float) $quantity * (float) $unitCost, 2);

            $line->update($data);
            $line->load('budgetCategory');

            $project->recalculateTotalBudget();

            AuditLog::record(
                auth()->id(),
                'project_budget_line_updated',
                'project',
                $project->id,
                $oldValues,
                $line->fresh()->load('budgetCategory')->toApiArray()
            );

            return $this->success([
                'budget_line' => $line->fresh()->load('budgetCategory')->toApiArray(),
                'project_total_budget' => (float) $project->fresh()->total_budget,
            ], 'Budget line updated successfully');
        });
    }

    public function destroy(string $projectId, string $lineId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $line = ProjectBudgetLine::where('project_id', $projectId)->findOrFail($lineId);

        return DB::transaction(function () use ($project, $line, $projectId) {
            $lineData = $line->toApiArray();
            $line->delete();

            $project->recalculateTotalBudget();

            AuditLog::record(
                auth()->id(),
                'project_budget_line_removed',
                'project',
                $projectId,
                $lineData,
                null
            );

            return $this->success([
                'project_total_budget' => (float) $project->fresh()->total_budget,
            ], 'Budget line removed successfully');
        });
    }

    private function buildProjectBudgetLineMetrics(string $projectId, ?string $projectCode): array
    {
        $poIds = PurchaseOrder::whereIn('status', ['approved', 'ordered', 'partially_received', 'received', 'disbursed'])
            ->whereHas('requisitions', fn ($q) => $q->where('project_id', $projectId))
            ->pluck('id')
            ->toArray();

        $committed = PurchaseOrderItem::selectRaw('LOWER(TRIM(budget_line)) as line_key, SUM(total_price) as total')
            ->whereIn('purchase_order_id', $poIds)
            ->whereNotNull('budget_line')
            ->groupBy('line_key')
            ->pluck('total', 'line_key')
            ->toArray();

        $poTotals = PurchaseOrderItem::selectRaw('purchase_order_id, SUM(total_price) as total')
            ->whereIn('purchase_order_id', $poIds)
            ->groupBy('purchase_order_id')
            ->pluck('total', 'purchase_order_id')
            ->toArray();

        $disbursementTotals = Disbursement::selectRaw('purchase_order_id, SUM(amount) as total')
            ->whereIn('purchase_order_id', $poIds)
            ->groupBy('purchase_order_id')
            ->pluck('total', 'purchase_order_id')
            ->toArray();

        $actual = [];
        $poLineGroups = PurchaseOrderItem::selectRaw('purchase_order_id, LOWER(TRIM(budget_line)) as line_key, SUM(total_price) as line_total')
            ->whereIn('purchase_order_id', $poIds)
            ->whereNotNull('budget_line')
            ->groupBy('purchase_order_id', 'line_key')
            ->get();

        foreach ($poLineGroups as $row) {
            $poId = $row->purchase_order_id;
            $poTotal = $poTotals[$poId] ?? 0;
            $disbursementValue = $disbursementTotals[$poId] ?? 0;
            if ($poTotal <= 0) {
                continue;
            }
            $share = (float) $row->line_total / $poTotal;
            $key = $this->budgetLineKey($projectCode, $row->line_key);
            $actual[$key] = ($actual[$key] ?? 0) + ($disbursementValue * $share);
        }

        $pendingRequests = RequisitionItem::selectRaw('LOWER(TRIM(budget_line)) as line_key, SUM(estimated_total_cost) as total')
            ->join('requisitions', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->where('requisitions.project_id', $projectId)
            ->where('requisitions.status', 'pending_approval')
            ->whereNotNull('requisition_items.budget_line')
            ->groupBy('line_key')
            ->pluck('total', 'line_key')
            ->toArray();

        $budgetLineData = ProjectBudgetLine::where('project_id', $projectId)->get();
        $metrics = [];

        foreach ($budgetLineData as $line) {
            $key = $this->budgetLineKey($projectCode, $line->description);
            $allocated = (float) $line->total_cost;
            $committedAmount = $committed[$key] ?? 0;
            $pendingAmount = $pendingRequests[$key] ?? 0;
            $actualAmount = $actual[$key] ?? 0;
            $utilization = $allocated > 0 ? (($committedAmount + $actualAmount + $pendingAmount) / $allocated) * 100 : 0;

            $metrics[$key] = [
                'committed_amount' => round($committedAmount, 2),
                'actual_spent_amount' => round($actualAmount, 2),
                'pending_request_amount' => round($pendingAmount, 2),
                'available_amount' => round($allocated - $committedAmount - $actualAmount - $pendingAmount, 2),
                'utilization_percent' => (int) round($utilization),
                'status' => $this->deriveBudgetLineStatus($utilization),
            ];
        }

        return $metrics;
    }

    private function budgetLineKey(?string $projectCode, ?string $budgetLine): string
    {
        return trim(strtolower((string) $projectCode)) . '||' . trim(strtolower((string) $budgetLine));
    }
}
