<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectBudgetLineRequest;
use App\Http\Requests\Projects\UpdateProjectBudgetLineRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectBudgetLine;
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
            'budget_lines' => $lines->map->toApiArray(),
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
}
