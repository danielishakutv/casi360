<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Forum;
use App\Models\ForumMessage;
use App\Models\Notice;
use App\Models\NoticeAudience;
use App\Models\NoticeRead;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunicationReportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    /**
     * GET /api/v1/reports/communication/notices
     */
    public function notices(Request $request)
    {
        $request->validate([
            'format'    => 'nullable|in:csv,excel,pdf',
            'status'    => 'nullable|in:draft,published,archived',
            'priority'  => 'nullable|in:normal,important,critical',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Notice::with('author:id,name')
            ->withCount('reads');

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('priority'))  $query->where('priority', $request->priority);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $query->orderByDesc('created_at');

        $mapRow = fn (Notice $n) => [
            'title'        => $n->title,
            'author'       => $n->author?->name ?? '—',
            'priority'     => ucfirst($n->priority),
            'status'       => ucfirst($n->status),
            'publish_date' => $n->publish_date ?? '—',
            'expiry_date'  => $n->expiry_date ?? '—',
            'read_count'   => $n->reads_count,
            'created_at'   => $n->created_at->format('Y-m-d'),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('communication_notices', $request);

            return $this->exportService->export(
                'Notices Report',
                ['Title', 'Author', 'Priority', 'Status', 'Publish Date', 'Expiry Date', 'Reads', 'Created'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status', 'priority', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/communication/forum-activity
     */
    public function forumActivity(Request $request)
    {
        $request->validate([
            'format'    => 'nullable|in:csv,excel,pdf',
            'forum_id'  => 'nullable|uuid|exists:forums,id',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Forum::withCount('messages');

        if ($request->filled('forum_id')) $query->where('id', $request->forum_id);

        // Apply date filters to the message count
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->withCount(['messages as messages_count' => function ($q) use ($request) {
                if ($request->filled('date_from')) $q->whereDate('created_at', '>=', $request->date_from);
                if ($request->filled('date_to'))   $q->whereDate('created_at', '<=', $request->date_to);
            }]);
        }

        $query->orderBy('name');

        // Get active user counts per forum
        $activeUsersQuery = ForumMessage::select('forum_id', DB::raw('COUNT(DISTINCT user_id) as active_users'))
            ->groupBy('forum_id');

        if ($request->filled('date_from')) $activeUsersQuery->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $activeUsersQuery->whereDate('created_at', '<=', $request->date_to);

        $activeUsers = $activeUsersQuery->pluck('active_users', 'forum_id');

        $mapRow = fn (Forum $f) => [
            'name'            => $f->name,
            'type'            => ucfirst($f->type),
            'status'          => ucfirst($f->status),
            'total_messages'  => $f->messages_count,
            'active_users'    => $activeUsers[$f->id] ?? 0,
            'last_activity'   => $f->messages()->latest()->value('created_at')?->format('Y-m-d H:i') ?? '—',
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('communication_forum_activity', $request);

            return $this->exportService->export(
                'Forum Activity Report',
                ['Forum', 'Type', 'Status', 'Total Messages', 'Active Users', 'Last Activity'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['forum_id', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    // ── Shared Helpers ──────────────────────────────────────

    private function paginated($query, callable $mapRow, Request $request)
    {
        $paginated = $query->paginate(min((int) $request->input('per_page', 25), 100));

        return $this->success([
            'rows' => collect($paginated->items())->map($mapRow),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    private function logDownload(string $report, Request $request): void
    {
        AuditLog::record(
            auth()->id(),
            'report_downloaded',
            'report',
            null,
            null,
            ['report' => $report, 'format' => $request->format, 'filters' => $request->except(['format', 'page', 'per_page'])]
        );
    }

    private function buildMeta(Request $request, array $filterKeys): array
    {
        $meta = ['Generated' => now()->format('d M Y, H:i')];
        foreach ($filterKeys as $key) {
            if ($request->filled($key)) {
                $meta[str_replace('_', ' ', ucfirst($key))] = $request->input($key);
            }
        }
        return $meta;
    }
}
