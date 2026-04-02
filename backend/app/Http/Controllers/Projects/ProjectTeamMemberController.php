<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectTeamMemberRequest;
use App\Http\Requests\Projects\UpdateProjectTeamMemberRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectTeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectTeamMemberController extends Controller
{
    public function index(string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $members = $project->teamMembers()->with('employee')->orderBy('created_at', 'desc')->get();

        return $this->success([
            'team_members' => $members->map->toApiArray(),
        ]);
    }

    public function store(StoreProjectTeamMemberRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        return DB::transaction(function () use ($request, $project) {
            $member = $project->teamMembers()->create($request->validated());
            $member->load('employee');

            AuditLog::record(
                auth()->id(),
                'project_team_member_added',
                'project',
                $project->id,
                null,
                $member->toApiArray()
            );

            return $this->success([
                'team_member' => $member->toApiArray(),
            ], 'Team member added successfully', 201);
        });
    }

    public function update(UpdateProjectTeamMemberRequest $request, string $projectId, string $memberId): JsonResponse
    {
        Project::findOrFail($projectId);
        $member = ProjectTeamMember::where('project_id', $projectId)->findOrFail($memberId);
        $oldValues = $member->toApiArray();

        return DB::transaction(function () use ($request, $member, $oldValues) {
            $member->update($request->validated());
            $member->load('employee');

            AuditLog::record(
                auth()->id(),
                'project_team_member_updated',
                'project',
                $member->project_id,
                $oldValues,
                $member->fresh()->load('employee')->toApiArray()
            );

            return $this->success([
                'team_member' => $member->fresh()->load('employee')->toApiArray(),
            ], 'Team member updated successfully');
        });
    }

    public function destroy(string $projectId, string $memberId): JsonResponse
    {
        Project::findOrFail($projectId);
        $member = ProjectTeamMember::where('project_id', $projectId)->findOrFail($memberId);

        return DB::transaction(function () use ($member, $projectId) {
            $memberData = $member->toApiArray();
            $member->delete();

            AuditLog::record(
                auth()->id(),
                'project_team_member_removed',
                'project',
                $projectId,
                $memberData,
                null
            );

            return $this->success(null, 'Team member removed successfully');
        });
    }
}
