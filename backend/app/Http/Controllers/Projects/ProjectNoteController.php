<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectNoteRequest;
use App\Http\Requests\Projects\UpdateProjectNoteRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectNoteController extends Controller
{
    public function index(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $query = $project->projectNotes()->with('creator');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $notes = $query->orderBy('created_at', 'desc')->get();

        return $this->success([
            'notes' => $notes->map->toApiArray(),
            'total' => $notes->count(),
        ]);
    }

    public function store(StoreProjectNoteRequest $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        return DB::transaction(function () use ($request, $project) {
            $note = $project->projectNotes()->create(array_merge(
                $request->validated(),
                ['created_by' => auth()->id()]
            ));

            $note->load('creator');

            AuditLog::record(
                auth()->id(),
                'project_note_created',
                'project',
                $project->id,
                null,
                $note->toApiArray()
            );

            return $this->success([
                'note' => $note->toApiArray(),
            ], 'Note added successfully', 201);
        });
    }

    public function update(UpdateProjectNoteRequest $request, string $projectId, string $noteId): JsonResponse
    {
        Project::findOrFail($projectId);
        $note = ProjectNote::where('project_id', $projectId)->findOrFail($noteId);
        $oldValues = $note->toApiArray();

        return DB::transaction(function () use ($request, $note, $oldValues) {
            $note->update($request->validated());
            $note->load('creator');

            AuditLog::record(
                auth()->id(),
                'project_note_updated',
                'project',
                $note->project_id,
                $oldValues,
                $note->fresh()->load('creator')->toApiArray()
            );

            return $this->success([
                'note' => $note->fresh()->load('creator')->toApiArray(),
            ], 'Note updated successfully');
        });
    }

    public function destroy(string $projectId, string $noteId): JsonResponse
    {
        Project::findOrFail($projectId);
        $note = ProjectNote::where('project_id', $projectId)->findOrFail($noteId);

        return DB::transaction(function () use ($note, $projectId) {
            $noteData = $note->toApiArray();
            $note->delete();

            AuditLog::record(
                auth()->id(),
                'project_note_deleted',
                'project',
                $projectId,
                $noteData,
                null
            );

            return $this->success(null, 'Note deleted successfully');
        });
    }
}
