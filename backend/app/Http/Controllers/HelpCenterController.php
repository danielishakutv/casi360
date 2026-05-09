<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HelpArticle;
use App\Models\HelpArticleEditor;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
    /* ============================================================ */
    /*  Public (any authenticated user)                             */
    /* ============================================================ */

    public function articles(Request $request): JsonResponse
    {
        $query = HelpArticle::query();

        // Editors and super_admin can see drafts; everyone else sees published only.
        $canManage = HelpArticleEditor::userCanManage($request->user());
        if (!$canManage) {
            $query->where('status', 'published');
        }

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

        $perPage = min((int) $request->input('per_page', 25), 200);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'articles' => $items->map->toApiArray(),
                'meta' => [
                    'total' => $items->count(),
                    'can_manage' => $canManage,
                ],
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
                'can_manage' => $canManage,
            ],
        ]);
    }

    public function showArticle(Request $request, string $id): JsonResponse
    {
        $article = HelpArticle::findOrFail($id);

        if ($article->status !== 'published'
            && !HelpArticleEditor::userCanManage($request->user())) {
            return $this->error('Article not found.', 404);
        }

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

    /* ============================================================ */
    /*  Article CRUD (super_admin or allowlisted editors)           */
    /* ============================================================ */

    public function storeArticle(Request $request): JsonResponse
    {
        if (!HelpArticleEditor::userCanManage($request->user())) {
            return $this->error('Only authorized editors may manage knowledge base articles.', 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'content' => ['required', 'string'],
            'status' => ['nullable', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['status'] = $data['status'] ?? 'published';
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $article = HelpArticle::create($data);

        AuditLog::record(
            auth()->id(),
            'help_article_created',
            'help_article',
            $article->id,
            null,
            $article->toApiArray()
        );

        return $this->success([
            'article' => $article->toApiArray(),
        ], 'Article created.', 201);
    }

    public function updateArticle(Request $request, string $id): JsonResponse
    {
        if (!HelpArticleEditor::userCanManage($request->user())) {
            return $this->error('Only authorized editors may manage knowledge base articles.', 403);
        }

        $article = HelpArticle::findOrFail($id);
        $before = $article->toApiArray();

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'content' => ['sometimes', 'required', 'string'],
            'status' => ['nullable', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $article->fill($data)->save();

        AuditLog::record(
            auth()->id(),
            'help_article_updated',
            'help_article',
            $article->id,
            $before,
            $article->toApiArray()
        );

        return $this->success([
            'article' => $article->toApiArray(),
        ], 'Article updated.');
    }

    public function destroyArticle(Request $request, string $id): JsonResponse
    {
        if (!HelpArticleEditor::userCanManage($request->user())) {
            return $this->error('Only authorized editors may manage knowledge base articles.', 403);
        }

        $article = HelpArticle::findOrFail($id);
        $before = $article->toApiArray();
        $article->delete();

        AuditLog::record(
            auth()->id(),
            'help_article_deleted',
            'help_article',
            $id,
            $before,
            null
        );

        return $this->success([], 'Article deleted.');
    }

    /* ============================================================ */
    /*  Editor allowlist (super_admin only)                         */
    /* ============================================================ */

    public function listEditors(Request $request): JsonResponse
    {
        $editors = HelpArticleEditor::with(['user:id,name,email,role,department', 'addedBy:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'user' => $e->user ? [
                    'id' => $e->user->id,
                    'name' => $e->user->name,
                    'email' => $e->user->email,
                    'role' => $e->user->role,
                    'department' => $e->user->department,
                ] : null,
                'added_by' => $e->addedBy ? [
                    'id' => $e->addedBy->id,
                    'name' => $e->addedBy->name,
                ] : null,
                'added_at' => $e->created_at?->toIso8601String(),
            ]);

        return $this->success(['editors' => $editors]);
    }

    public function addEditor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $existing = HelpArticleEditor::where('user_id', $data['user_id'])->first();
        if ($existing) {
            return $this->error('User is already an editor.', 422);
        }

        $editor = HelpArticleEditor::create([
            'user_id' => $data['user_id'],
            'added_by' => auth()->id(),
        ]);
        $editor->load('user:id,name,email,role,department');

        AuditLog::record(
            auth()->id(),
            'help_editor_added',
            'help_article_editor',
            $editor->id,
            null,
            ['user_id' => $editor->user_id]
        );

        return $this->success([
            'editor' => [
                'id' => $editor->id,
                'user' => [
                    'id' => $editor->user->id,
                    'name' => $editor->user->name,
                    'email' => $editor->user->email,
                    'role' => $editor->user->role,
                    'department' => $editor->user->department,
                ],
                'added_at' => $editor->created_at->toIso8601String(),
            ],
        ], 'Editor added.', 201);
    }

    public function removeEditor(Request $request, string $id): JsonResponse
    {
        $editor = HelpArticleEditor::findOrFail($id);
        $userId = $editor->user_id;
        $editor->delete();

        AuditLog::record(
            auth()->id(),
            'help_editor_removed',
            'help_article_editor',
            $id,
            ['user_id' => $userId],
            null
        );

        return $this->success([], 'Editor removed.');
    }

    public function eligibleUsers(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $query = User::query()
            ->where('status', 'active')
            ->whereDoesntHave('helpArticleEditor');

        // Filter out super_admins (they always have access)
        $query->where('role', '!=', 'super_admin');

        if ($search !== '') {
            $term = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $users = $query->orderBy('name')->limit(20)->get(['id', 'name', 'email', 'role', 'department']);

        return $this->success([
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'department' => $u->department,
            ]),
        ]);
    }
}
