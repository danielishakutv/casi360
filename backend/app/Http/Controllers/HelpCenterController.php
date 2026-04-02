<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HelpArticle;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
    public function articles(Request $request): JsonResponse
    {
        $query = HelpArticle::where('status', 'published');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $query->orderBy('sort_order')->orderBy('title');

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'articles' => $items->map->toApiArray(),
                'meta' => ['total' => $items->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'articles' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function showArticle(string $id): JsonResponse
    {
        $article = HelpArticle::findOrFail($id);

        return $this->success([
            'article' => $article->toApiArray(),
        ]);
    }

    public function submitTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
        ]);

        $data['user_id'] = auth()->id();
        $data['status'] = 'open';
        $data['priority'] = $data['priority'] ?? 'medium';

        $ticket = SupportTicket::create($data);
        $ticket->load('user');

        AuditLog::record(
            auth()->id(),
            'support_ticket_created',
            'support_ticket',
            $ticket->id,
            null,
            $ticket->toApiArray()
        );

        return $this->success([
            'ticket' => $ticket->toApiArray(),
        ], 'Support ticket submitted successfully', 201);
    }
}
