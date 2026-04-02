<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectDonorRequest;
use App\Http\Requests\Projects\UpdateProjectDonorRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectDonor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectDonorController extends Controller
{
    public function index(string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $donors = $project->donors()->orderBy('created_at', 'desc')->get();

        return $this->success([
            'donors' => $donors->map->toApiArray(),
            'total_contributions' => (float) $donors->sum('contribution_amount'),
        ]);
    }

    public function store(StoreProjectDonorRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        return DB::transaction(function () use ($request, $project) {
            $donor = $project->donors()->create($request->validated());

            AuditLog::record(
                auth()->id(),
                'project_donor_added',
                'project',
                $project->id,
                null,
                $donor->toApiArray()
            );

            return $this->success([
                'donor' => $donor->toApiArray(),
            ], 'Donor added successfully', 201);
        });
    }

    public function update(UpdateProjectDonorRequest $request, string $projectId, string $donorId): JsonResponse
    {
        Project::findOrFail($projectId);
        $donor = ProjectDonor::where('project_id', $projectId)->findOrFail($donorId);
        $oldValues = $donor->toApiArray();

        return DB::transaction(function () use ($request, $donor, $oldValues) {
            $donor->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'project_donor_updated',
                'project',
                $donor->project_id,
                $oldValues,
                $donor->fresh()->toApiArray()
            );

            return $this->success([
                'donor' => $donor->fresh()->toApiArray(),
            ], 'Donor updated successfully');
        });
    }

    public function destroy(string $projectId, string $donorId): JsonResponse
    {
        Project::findOrFail($projectId);
        $donor = ProjectDonor::where('project_id', $projectId)->findOrFail($donorId);

        return DB::transaction(function () use ($donor, $projectId, $donorId) {
            $donorData = $donor->toApiArray();
            $donor->delete();

            AuditLog::record(
                auth()->id(),
                'project_donor_removed',
                'project',
                $projectId,
                $donorData,
                null
            );

            return $this->success(null, 'Donor removed successfully');
        });
    }
}
