<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectActivityRequest;
use App\Http\Requests\Projects\UpdateProjectActivityRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectActivityController extends Controller
{
    public function index(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $query = $project->activities();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $activities = $query->orderBy('sort_order')->orderBy('created_at')->get();

        $summary = [
            'total' => $activities->count(),
            'not_started' => $activities->where('status', 'not_started')->count(),
            'in_progress' => $activities->where('status', 'in_progress')->count(),
            'completed' => $activities->where('status', 'completed')->count(),
            'delayed' => $activities->where('status', 'delayed')->count(),
            'cancelled' => $activities->where('status', 'cancelled')->count(),
        ];

        return $this->success([
            'activities' => $activities->map->toApiArray(),
            'summary' => $summary,
        ]);
    }

    public function store(StoreProjectActivityRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        return DB::transaction(function () use ($request, $project) {
            $activity = $project->activities()->create($request->validated());

            AuditLog::record(
                auth()->id(),
                'project_activity_created',
                'project',
                $project->id,
                null,
                $activity->toApiArray()
            );

            return $this->success([
                'activity' => $activity->toApiArray(),
            ], 'Activity created successfully', 201);
        });
    }

    public function update(UpdateProjectActivityRequest $request, string $projectId, string $activityId): JsonResponse
    {
        Project::findOrFail($projectId);
        $activity = ProjectActivity::where('project_id', $projectId)->findOrFail($activityId);
        $oldValues = $activity->toApiArray();

        return DB::transaction(function () use ($request, $activity, $oldValues) {
            $activity->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'project_activity_updated',
                'project',
                $activity->project_id,
                $oldValues,
                $activity->fresh()->toApiArray()
            );

            return $this->success([
                'activity' => $activity->fresh()->toApiArray(),
            ], 'Activity updated successfully');
        });
    }

    public function destroy(string $projectId, string $activityId): JsonResponse
    {
        Project::findOrFail($projectId);
        $activity = ProjectActivity::where('project_id', $projectId)->findOrFail($activityId);

        return DB::transaction(function () use ($activity, $projectId) {
            $activityData = $activity->toApiArray();
            $activity->delete();

            AuditLog::record(
                auth()->id(),
                'project_activity_deleted',
                'project',
                $projectId,
                $activityData,
                null
            );

            return $this->success(null, 'Activity deleted successfully');
        });
    }
}
