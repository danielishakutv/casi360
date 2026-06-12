<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The current user's in-app notifications (top-bar bell). Each user only ever
 * sees and acts on their own rows — no special permission required.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $perPage = min((int) $request->input('per_page', 20), 50);

        $query = Notification::forUser($userId);
        if ($request->boolean('unread')) {
            $query->unread();
        }

        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success([
            'notifications' => collect($paginated->items())->map->toApiArray(),
            'unread_count'  => Notification::forUser($userId)->unread()->count(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'unread_count' => Notification::forUser($request->user()->id)->unread()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)->findOrFail($id);
        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $this->success(['notification' => $notification->toApiArray()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)->unread()->update(['read_at' => now()]);

        return $this->success(['unread_count' => 0], 'All notifications marked as read.');
    }
}
