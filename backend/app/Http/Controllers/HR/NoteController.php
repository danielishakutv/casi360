<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreNoteRequest;
use App\Http\Requests\HR\UpdateNoteRequest;
use App\Models\AuditLog;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     * GET /api/v1/hr/notes
     *
     * List notes with filtering, searching, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Note::with(['employee', 'creator']);

        // Filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['title', 'type', 'priority', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination (capped at 100)
        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->paginate($perPage);

        return $this->success([
            'notes' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * POST /api/v1/hr/notes
     *
     * Create a new note attached to an employee.
     */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = auth()->id();

            $note = Note::create($data);
            $note->load(['employee', 'creator']);

            AuditLog::record(
                auth()->id(),
                'note_created',
                'note',
                $note->id,
                null,
                $note->toApiArray()
            );

            return $this->success([
                'note' => $note->toApiArray(),
            ], 'Note created successfully', 201);
        });
    }

    /**
     * GET /api/v1/hr/notes/{id}
     *
     * Get a specific note.
     */
    public function show(string $id): JsonResponse
    {
        $note = Note::with(['employee', 'creator'])->findOrFail($id);

        return $this->success([
            'note' => $note->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/hr/notes/{id}
     *
     * Update a note.
     */
    public function update(UpdateNoteRequest $request, string $id): JsonResponse
    {
        $note = Note::with(['employee', 'creator'])->findOrFail($id);
        $oldValues = $note->toApiArray();

        return DB::transaction(function () use ($request, $note, $oldValues) {
            $note->update($request->validated());
            $note->load(['employee', 'creator']);

            AuditLog::record(
                auth()->id(),
                'note_updated',
                'note',
                $note->id,
                $oldValues,
                $note->fresh()->load(['employee', 'creator'])->toApiArray()
            );

            return $this->success([
                'note' => $note->fresh()->load(['employee', 'creator'])->toApiArray(),
            ], 'Note updated successfully');
        });
    }

    /**
     * DELETE /api/v1/hr/notes/{id}
     *
     * Hard-delete a note.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $note = Note::with(['employee', 'creator'])->findOrFail($id);
        $oldValues = $note->toApiArray();

        return DB::transaction(function () use ($request, $note, $oldValues) {
            $note->delete();

            AuditLog::record(
                auth()->id(),
                'note_deleted',
                'note',
                $note->id,
                $oldValues,
                null
            );

            return $this->success(null, 'Note deleted successfully');
        });
    }
}
