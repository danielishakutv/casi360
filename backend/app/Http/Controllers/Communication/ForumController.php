<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreForumRequest;
use App\Http\Requests\Communication\UpdateForumRequest;
use App\Models\AuditLog;
use App\Models\Forum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    /**
     * GET /communication/forums
     * List forums accessible to the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Forum::active()->accessibleBy($user)->with('department');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $forums = $query->orderByRaw("type = 'general' DESC")
            ->orderBy('name')
            ->get();

        return $this->success([
            'forums' => $forums->map->toApiArray(),
        ]);
    }

    /**
     * GET /communication/forums/{id}
     * View a single forum details.
     */
    public function show(string $id): JsonResponse
    {
        $user = auth()->user();
        $forum = Forum::accessibleBy($user)->with('department')->findOrFail($id);

        return $this->success([
            'forum' => $forum->toApiArray(),
        ]);
    }

    /**
     * POST /communication/forums
     * Create a new forum (admin only).
     */
    public function store(StoreForumRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $forum = Forum::create($request->validated());

            AuditLog::record(
                auth()->id(),
                'forum_created',
                'forum',
                $forum->id,
                null,
                $forum->toApiArray()
            );

            return $this->success([
                'forum' => $forum->toApiArray(),
            ], 'Forum created successfully', 201);
        });
    }

    /**
     * PATCH /communication/forums/{id}
     * Update a forum (admin only).
     */
    public function update(UpdateForumRequest $request, string $id): JsonResponse
    {
        $forum = Forum::findOrFail($id);
        $oldValues = $forum->toApiArray();

        return DB::transaction(function () use ($request, $forum, $oldValues) {
            $forum->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'forum_updated',
                'forum',
                $forum->id,
                $oldValues,
                $forum->fresh()->toApiArray()
            );

            return $this->success([
                'forum' => $forum->fresh()->toApiArray(),
            ], 'Forum updated successfully');
        });
    }

    /**
     * DELETE /communication/forums/{id}
     * Archive a forum (admin only). Cannot delete the General forum.
     */
    public function destroy(string $id): JsonResponse
    {
        $forum = Forum::findOrFail($id);

        if ($forum->type === 'general' && Forum::general()->count() <= 1) {
            return $this->error('The General forum cannot be deleted.', 422);
        }

        return DB::transaction(function () use ($forum) {
            $forumData = $forum->toApiArray();
            $forum->update(['status' => 'archived']);

            AuditLog::record(
                auth()->id(),
                'forum_archived',
                'forum',
                $forum->id,
                $forumData,
                null
            );

            return $this->success(null, 'Forum archived successfully');
        });
    }
}
