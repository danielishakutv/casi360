<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

class AuditReportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    /**
     * GET /api/v1/reports/audit/logs
     *
     * Full system audit trail.
     */
    public function logs(Request $request)
    {
        $request->validate([
            'format'      => 'nullable|in:csv,excel,pdf',
            'user_id'     => 'nullable|uuid|exists:users,id',
            'action'      => 'nullable|string|max:100',
            'entity_type' => 'nullable|string|max:100',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = AuditLog::with('user:id,name');

        if ($request->filled('user_id'))     $query->where('user_id', $request->user_id);
        if ($request->filled('action'))      $query->where('action', $request->action);
        if ($request->filled('entity_type')) $query->where('entity_type', $request->entity_type);
        if ($request->filled('date_from'))   $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('created_at', '<=', $request->date_to);

        $query->orderByDesc('created_at');

        $mapRow = fn (AuditLog $a) => [
            'date'        => $a->created_at->format('Y-m-d H:i:s'),
            'user'        => $a->user?->name ?? 'System',
            'action'      => $a->action,
            'entity_type' => $a->entity_type ?? '—',
            'entity_id'   => $a->entity_id ? substr($a->entity_id, 0, 8) . '...' : '—',
            'ip_address'  => $a->ip_address ?? '—',
        ];

        if ($request->filled('format')) {
            $this->logDownload('audit_logs', $request);

            $rows = $query->get()->map($mapRow);

            return $this->exportService->export(
                'System Audit Log',
                ['Date/Time', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['user_id', 'action', 'entity_type', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/audit/login-history
     *
     * User login/logout history.
     */
    public function loginHistory(Request $request)
    {
        $request->validate([
            'format'    => 'nullable|in:csv,excel,pdf',
            'user_id'   => 'nullable|uuid|exists:users,id',
            'success'   => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = LoginHistory::with('user:id,name,email');

        if ($request->filled('user_id'))   $query->where('user_id', $request->user_id);
        if ($request->filled('success'))   $query->where('login_successful', filter_var($request->success, FILTER_VALIDATE_BOOLEAN));
        if ($request->filled('date_from')) $query->whereDate('login_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('login_at', '<=', $request->date_to);

        $query->orderByDesc('login_at');

        $mapRow = fn (LoginHistory $l) => [
            'login_at'   => $l->login_at?->format('Y-m-d H:i:s') ?? '—',
            'logout_at'  => $l->logout_at?->format('Y-m-d H:i:s') ?? '—',
            'user'       => $l->user?->name ?? '—',
            'email'      => $l->user?->email ?? '—',
            'ip_address' => $l->ip_address ?? '—',
            'status'     => $l->login_successful ? 'Success' : 'Failed',
            'reason'     => $l->failure_reason ?? '—',
        ];

        if ($request->filled('format')) {
            $this->logDownload('audit_login_history', $request);

            $rows = $query->get()->map($mapRow);

            return $this->exportService->export(
                'Login History',
                ['Login At', 'Logout At', 'User', 'Email', 'IP Address', 'Status', 'Failure Reason'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['user_id', 'success', 'date_from', 'date_to'])
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
