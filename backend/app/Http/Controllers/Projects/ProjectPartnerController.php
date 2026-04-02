<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectPartnerRequest;
use App\Http\Requests\Projects\UpdateProjectPartnerRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectPartnerController extends Controller
{
    public function index(string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $partners = $project->partners()->orderBy('created_at', 'desc')->get();

        return $this->success([
            'partners' => $partners->map->toApiArray(),
        ]);
    }

    public function store(StoreProjectPartnerRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        return DB::transaction(function () use ($request, $project) {
            $partner = $project->partners()->create($request->validated());

            AuditLog::record(
                auth()->id(),
                'project_partner_added',
                'project',
                $project->id,
                null,
                $partner->toApiArray()
            );

            return $this->success([
                'partner' => $partner->toApiArray(),
            ], 'Partner added successfully', 201);
        });
    }

    public function update(UpdateProjectPartnerRequest $request, string $projectId, string $partnerId): JsonResponse
    {
        Project::findOrFail($projectId);
        $partner = ProjectPartner::where('project_id', $projectId)->findOrFail($partnerId);
        $oldValues = $partner->toApiArray();

        return DB::transaction(function () use ($request, $partner, $oldValues) {
            $partner->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'project_partner_updated',
                'project',
                $partner->project_id,
                $oldValues,
                $partner->fresh()->toApiArray()
            );

            return $this->success([
                'partner' => $partner->fresh()->toApiArray(),
            ], 'Partner updated successfully');
        });
    }

    public function destroy(string $projectId, string $partnerId): JsonResponse
    {
        Project::findOrFail($projectId);
        $partner = ProjectPartner::where('project_id', $projectId)->findOrFail($partnerId);

        return DB::transaction(function () use ($partner, $projectId) {
            $partnerData = $partner->toApiArray();
            $partner->delete();

            AuditLog::record(
                auth()->id(),
                'project_partner_removed',
                'project',
                $projectId,
                $partnerData,
                null
            );

            return $this->success(null, 'Partner removed successfully');
        });
    }
}
