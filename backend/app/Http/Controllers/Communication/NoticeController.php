<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreNoticeRequest;
use App\Http\Requests\Communication\UpdateNoticeRequest;
use App\Models\AuditLog;
use App\Models\Notice;
use App\Models\NoticeRead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeController extends Controller
{
    /**
     * GET /communication/notices
     * List notices visible to the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Super admin sees all, others see published & targeted
        if ($user->role === 'super_admin') {
            $query = Notice::with('author');

            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }
        } else {
            $query = Notice::published()->visibleTo($user)->with('author');
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['title', 'priority', 'status', 'publish_date', 'created_at'];

        // Pinned notices always first
        $query->orderBy('is_pinned', 'desc');
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->paginate($perPage);

        $notices = collect($paginated->items())->map(function (Notice $notice) use ($user) {
            $data = $notice->toApiArray();
            $data['is_read'] = $notice->isReadBy($user->id);
            return $data;
        });

        return $this->success([
            'notices' => $notices,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /communication/notices/{id}
     * View a single notice. Auto-marks as read for the viewer.
     */
    public function show(string $id): JsonResponse
    {
        $user = auth()->user();

        if ($user->role === 'super_admin') {
            $notice = Notice::with(['author', 'audiences'])->findOrFail($id);
        } else {
            $notice = Notice::published()->visibleTo($user)->with(['author', 'audiences'])->findOrFail($id);
        }

        // Mark as read
        NoticeRead::firstOrCreate(
            ['notice_id' => $id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        $data = $notice->toDetailArray($user->id);

        return $this->success([
            'notice' => $data,
        ]);
    }

    /**
     * POST /communication/notices
     * Create a new notice.
     */
    public function store(StoreNoticeRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $audiences = $validated['audiences'];
            unset($validated['audiences']);

            $validated['author_id'] = auth()->id();
            $notice = Notice::create($validated);

            foreach ($audiences as $audience) {
                $notice->audiences()->create($audience);
            }

            $notice->load(['author', 'audiences']);

            AuditLog::record(
                auth()->id(),
                'notice_created',
                'notice',
                $notice->id,
                null,
                $notice->toApiArray()
            );

            return $this->success([
                'notice' => $notice->toDetailArray(),
            ], 'Notice created successfully', 201);
        });
    }

    /**
     * PATCH /communication/notices/{id}
     * Update a notice.
     */
    public function update(UpdateNoticeRequest $request, string $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $oldValues = $notice->toApiArray();

        return DB::transaction(function () use ($request, $notice, $oldValues) {
            $validated = $request->validated();

            // If audiences are provided, replace them
            if (isset($validated['audiences'])) {
                $audiences = $validated['audiences'];
                unset($validated['audiences']);

                $notice->audiences()->delete();
                foreach ($audiences as $audience) {
                    $notice->audiences()->create($audience);
                }
            }

            $notice->update($validated);
            $notice->load(['author', 'audiences']);

            AuditLog::record(
                auth()->id(),
                'notice_updated',
                'notice',
                $notice->id,
                $oldValues,
                $notice->fresh()->toApiArray()
            );

            return $this->success([
                'notice' => $notice->fresh()->load(['author', 'audiences'])->toDetailArray(),
            ], 'Notice updated successfully');
        });
    }

    /**
     * DELETE /communication/notices/{id}
     * Delete a notice.
     */
    public function destroy(string $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);

        return DB::transaction(function () use ($notice) {
            $noticeData = $notice->toApiArray();
            $notice->delete();

            AuditLog::record(
                auth()->id(),
                'notice_deleted',
                'notice',
                $notice->id,
                $noticeData,
                null
            );

            return $this->success(null, 'Notice deleted successfully');
        });
    }

    /**
     * GET /communication/notices/{id}/reads
     * View who has read a notice (super_admin / admin only).
     */
    public function reads(string $id): JsonResponse
    {
        $notice = Notice::findOrFail($id);
        $reads = $notice->reads()->with('user')->orderBy('read_at', 'desc')->get();

        // Get total targeted users for percentage calculation
        $totalUsers = User::where('status', 'active')->where('role', '!=', 'super_admin')->count();

        return $this->success([
            'notice_id' => $id,
            'title' => $notice->title,
            'total_reads' => $reads->count(),
            'total_users' => $totalUsers,
            'read_percentage' => $totalUsers > 0 ? round(($reads->count() / $totalUsers) * 100, 1) : 0,
            'reads' => $reads->map->toApiArray(),
        ]);
    }

    /**
     * GET /communication/notices/stats
     * Notice statistics for dashboard.
     */
    public function stats(): JsonResponse
    {
        return $this->success([
            'total' => Notice::count(),
            'published' => Notice::where('status', 'published')->count(),
            'draft' => Notice::where('status', 'draft')->count(),
            'archived' => Notice::where('status', 'archived')->count(),
            'pinned' => Notice::where('is_pinned', true)->count(),
        ]);
    }
}
