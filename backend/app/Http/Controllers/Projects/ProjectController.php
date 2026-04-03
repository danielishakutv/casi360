<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['department', 'projectManager']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('project_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['name', 'project_code', 'status', 'start_date', 'end_date', 'total_budget', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->paginate($perPage);

        return $this->success([
            'projects' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['project_code'] = Project::generateProjectCode();

            $project = Project::create($data);
            $project->load(['department', 'projectManager']);

            AuditLog::record(
                auth()->id(),
                'project_created',
                'project',
                $project->id,
                null,
                $project->toApiArray()
            );

            return $this->success([
                'project' => $project->toApiArray(),
            ], 'Project created successfully', 201);
        });
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with(['department', 'projectManager'])->findOrFail($id);
        return $this->success(['project' => $project->toDetailArray()]);
    }

    public function update(UpdateProjectRequest $request, string $id): JsonResponse
    {
        $project = Project::with(['department', 'projectManager'])->findOrFail($id);
        $oldValues = $project->toApiArray();

        return DB::transaction(function () use ($request, $project, $oldValues) {
            $project->update($request->validated());
            $project->load(['department', 'projectManager']);

            AuditLog::record(
                auth()->id(),
                'project_updated',
                'project',
                $project->id,
                $oldValues,
                $project->fresh()->load(['department', 'projectManager'])->toApiArray()
            );

            return $this->success([
                'project' => $project->fresh()->load(['department', 'projectManager'])->toApiArray(),
            ], 'Project updated successfully');
        });
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        if ($project->status === 'closed') {
            return $this->error('Cannot delete a closed project.', 422);
        }

        $oldValues = $project->toApiArray();

        return DB::transaction(function () use ($project, $oldValues) {
            $project->update(['status' => 'closed']);

            AuditLog::record(
                auth()->id(),
                'project_closed',
                'project',
                $project->id,
                $oldValues,
                $project->fresh()->load(['department', 'projectManager'])->toApiArray()
            );

            return $this->success([
                'project' => $project->fresh()->load(['department', 'projectManager'])->toApiArray(),
            ], 'Project closed successfully');
        });
    }

    public function stats(): JsonResponse
    {
        $total = Project::count();
        $draft = Project::where('status', 'draft')->count();
        $active = Project::where('status', 'active')->count();
        $onHold = Project::where('status', 'on_hold')->count();
        $completed = Project::where('status', 'completed')->count();
        $closed = Project::where('status', 'closed')->count();
        $totalBudget = Project::whereNotIn('status', ['closed'])->sum('total_budget');

        $byDepartment = Project::whereNotIn('status', ['closed'])
            ->selectRaw('department_id, count(*) as count, sum(total_budget) as budget')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'department_id' => $item->department_id,
                    'department' => $item->department?->name ?? 'Unassigned',
                    'count' => $item->count,
                    'budget' => (float) $item->budget,
                ];
            });

        return $this->success([
            'total' => $total,
            'draft' => $draft,
            'active' => $active,
            'on_hold' => $onHold,
            'completed' => $completed,
            'closed' => $closed,
            'total_budget' => (float) $totalBudget,
            'by_department' => $byDepartment,
        ]);
    }
}
