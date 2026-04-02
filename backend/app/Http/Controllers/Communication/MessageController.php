<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreMessageRequest;
use App\Models\AuditLog;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * GET /communication/messages
     * List conversations (inbox view) — grouped threads.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $box = $request->input('box', 'inbox'); // inbox, sent

        // Get root messages (threads) relevant to the user
        $query = Message::rootMessages()->with(['sender', 'recipient']);

        if ($box === 'sent') {
            $query->sent($userId);
        } else {
            $query->inbox($userId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $unreadOnly = filter_var($request->input('unread'), FILTER_VALIDATE_BOOLEAN);
        if ($unreadOnly && $box === 'inbox') {
            $query->whereNull('read_at');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add reply info to each thread
        $threads = collect($paginated->items())->map(function (Message $msg) use ($userId) {
            $data = $msg->toThreadArray();
            $data['unread_replies'] = $msg->replies()
                ->where('recipient_id', $userId)
                ->whereNull('read_at')
                ->whereNull('recipient_deleted_at')
                ->count();
            return $data;
        });

        $unreadCount = Message::inbox($userId)->rootMessages()->whereNull('read_at')->count();

        return $this->success([
            'threads' => $threads,
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /communication/messages/{threadId}
     * View a full thread (root message + all replies).
     */
    public function show(string $threadId): JsonResponse
    {
        $userId = auth()->id();

        $rootMessage = Message::with(['sender', 'recipient'])->findOrFail($threadId);

        // Verify user is a participant
        if ($rootMessage->sender_id !== $userId && $rootMessage->recipient_id !== $userId) {
            return $this->error('You are not a participant in this conversation.', 403);
        }

        // Mark root as read if recipient is viewing
        if ($rootMessage->recipient_id === $userId && !$rootMessage->read_at) {
            $rootMessage->markAsRead();
        }

        // Get all replies in the thread
        $replies = Message::where('thread_id', $threadId)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark unread replies as read
        $replies->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->each(fn (Message $r) => $r->markAsRead());

        return $this->success([
            'thread' => $rootMessage->toApiArray(),
            'replies' => $replies->map->toApiArray(),
        ]);
    }

    /**
     * POST /communication/messages
     * Send a new message or reply to a thread.
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $senderId = auth()->id();

        // Prevent sending to yourself
        if ($request->recipient_id === $senderId) {
            return $this->error('You cannot send a message to yourself.', 422);
        }

        return DB::transaction(function () use ($request, $senderId) {
            $data = $request->validated();
            $data['sender_id'] = $senderId;

            // If replying, verify the sender is a participant of the thread
            if (!empty($data['thread_id'])) {
                $thread = Message::findOrFail($data['thread_id']);
                if ($thread->sender_id !== $senderId && $thread->recipient_id !== $senderId) {
                    return $this->error('You are not a participant in this thread.', 403);
                }
                // Reply inherits thread subject, recipient is the other person
                $data['subject'] = null;
                $data['recipient_id'] = $thread->sender_id === $senderId
                    ? $thread->recipient_id
                    : $thread->sender_id;
            }

            $message = Message::create($data);
            $message->load(['sender', 'recipient']);

            AuditLog::record(
                $senderId,
                'message_sent',
                'message',
                $message->id,
                null,
                ['recipient_id' => $message->recipient_id, 'subject' => $message->subject ?? '(reply)']
            );

            return $this->success([
                'message' => $message->toApiArray(),
            ], 'Message sent successfully', 201);
        });
    }

    /**
     * DELETE /communication/messages/{id}
     * Soft-delete a message for the current user.
     */
    public function destroy(string $id): JsonResponse
    {
        $userId = auth()->id();
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $userId && $message->recipient_id !== $userId) {
            return $this->error('You are not a participant in this message.', 403);
        }

        if ($message->sender_id === $userId) {
            $message->update(['sender_deleted_at' => now()]);
        }
        if ($message->recipient_id === $userId) {
            $message->update(['recipient_deleted_at' => now()]);
        }

        return $this->success(null, 'Message deleted successfully');
    }

    /**
     * GET /communication/messages/unread-count
     * Quick unread count for badge display.
     */
    public function unreadCount(): JsonResponse
    {
        $count = Message::inbox(auth()->id())->whereNull('read_at')->count();
        return $this->success(['unread_count' => $count]);
    }
}
