<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreForumMessageRequest;
use App\Models\AuditLog;
use App\Models\Forum;
use App\Models\ForumMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumMessageController extends Controller
{
    /**
     * GET /communication/forums/{forumId}/messages
     * List messages in a forum.
     */
    public function index(Request $request, string $forumId): JsonResponse
    {
        $user = auth()->user();
        $forum = Forum::active()->accessibleBy($user)->findOrFail($forumId);

        $query = $forum->messages()->with('user');

        // Only top-level messages by default; replies loaded separately
        $showReplies = filter_var($request->input('include_replies'), FILTER_VALIDATE_BOOLEAN);
        if (!$showReplies) {
            $query->whereNull('reply_to_id');
        }

        if ($request->filled('search')) {
            $query->where('body', 'like', "%{$request->search}%");
        }

        $perPage = min((int) $request->input('per_page', 50), 100);
        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->success([
            'messages' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /communication/forums/{forumId}/messages/{messageId}/replies
     * List replies to a specific forum message.
     */
    public function replies(string $forumId, string $messageId): JsonResponse
    {
        $user = auth()->user();
        Forum::active()->accessibleBy($user)->findOrFail($forumId);

        $message = ForumMessage::where('forum_id', $forumId)->findOrFail($messageId);
        $replies = $message->replies()->with('user')->orderBy('created_at', 'asc')->get();

        return $this->success([
            'parent' => $message->toApiArray(),
            'replies' => $replies->map->toApiArray(),
        ]);
    }

    /**
     * POST /communication/forums/{forumId}/messages
     * Post a message in a forum.
     */
    public function store(StoreForumMessageRequest $request, string $forumId): JsonResponse
    {
        $user = auth()->user();
        $forum = Forum::active()->accessibleBy($user)->findOrFail($forumId);

        // If replying, verify the parent message belongs to this forum
        if ($request->filled('reply_to_id')) {
            ForumMessage::where('forum_id', $forumId)->findOrFail($request->reply_to_id);
        }

        return DB::transaction(function () use ($request, $forum) {
            $message = $forum->messages()->create([
                'user_id' => auth()->id(),
                'body' => $request->body,
                'reply_to_id' => $request->reply_to_id,
            ]);

            $message->load('user');

            AuditLog::record(
                auth()->id(),
                'forum_message_posted',
                'forum',
                $forum->id,
                null,
                ['message_id' => $message->id, 'forum' => $forum->name]
            );

            return $this->success([
                'message' => $message->toApiArray(),
            ], 'Message posted successfully', 201);
        });
    }

    /**
     * DELETE /communication/forums/{forumId}/messages/{messageId}
     * Delete a forum message (own message or admin).
     */
    public function destroy(string $forumId, string $messageId): JsonResponse
    {
        $user = auth()->user();
        Forum::active()->accessibleBy($user)->findOrFail($forumId);

        $message = ForumMessage::where('forum_id', $forumId)->findOrFail($messageId);

        // Only the author or admins can delete
        if ($message->user_id !== $user->id && !in_array($user->role, ['super_admin', 'admin'])) {
            return $this->error('You can only delete your own messages.', 403);
        }

        return DB::transaction(function () use ($message, $forumId) {
            $messageData = $message->toApiArray();
            $message->delete();

            AuditLog::record(
                auth()->id(),
                'forum_message_deleted',
                'forum',
                $forumId,
                $messageData,
                null
            );

            return $this->success(null, 'Message deleted successfully');
        });
    }
}
