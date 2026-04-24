<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\Project;
use App\Models\ProjectBudgetLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Requisition;
use App\Models\RequisitionAuditLog;
use App\Models\RequisitionItem;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    /**
     * GET /api/v1/reports/finance/overview
     *
     * Cross-module financial overview: spending by department, by period.
     */
    public function overview(Request $request)
    {
        $request->validate([
            'format'        => 'nullable|in:csv,excel,pdf',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date|after_or_equal:date_from',
        ]);

        // ── Summary stats (always returned) ──
        $poQuery = PurchaseOrder::query();
        $disbQuery = Disbursement::query();

        if ($request->filled('department_id')) {
            $poQuery->where('department_id', $request->department_id);
            $disbQuery->whereHas('purchaseOrder', fn ($q) => $q->where('department_id', $request->department_id));
        }
        if ($request->filled('date_from')) {
            $poQuery->whereDate('order_date', '>=', $request->date_from);
            $disbQuery->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $poQuery->whereDate('order_date', '<=', $request->date_to);
            $disbQuery->whereDate('payment_date', '<=', $request->date_to);
        }

        // ── Department breakdown ──
        $deptBreakdown = (clone $poQuery)
            ->select('department_id', DB::raw('COUNT(*) as po_count'), DB::raw('SUM(total_amount) as total_value'))
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get();

        $rows = $deptBreakdown->map(fn ($row) => [
            'department'    => $row->department?->name ?? '—',
            'po_count'      => $row->po_count,
            'total_po_value'=> number_format($row->total_value, 2),
        ]);

        // Append totals row
        $totalPOs   = $rows->sum(fn ($r) => $r['po_count']);
        $totalValue = $deptBreakdown->sum('total_value');
        $totalDisbursed = $disbQuery->sum('amount');

        // ── Payment status breakdown ──
        $paymentBreakdown = (clone $poQuery)
            ->select('payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status')
            ->map(fn ($v) => number_format($v, 2));

        if ($request->filled('format')) {
            $this->logDownload('finance_overview', $request);

            // For export, include a summary section + dept breakdown
            $exportRows = $deptBreakdown->map(fn ($row) => [
                'department'     => $row->department?->name ?? '—',
                'purchase_orders'=> $row->po_count,
                'total_value'    => number_format($row->total_value, 2),
            ]);

            // Add totals row
            $exportRows->push([
                'department'      => 'TOTAL',
                'purchase_orders' => $totalPOs,
                'total_value'     => number_format($totalValue, 2),
            ]);

            return $this->exportService->export(
                'Financial Overview',
                ['Department', 'Purchase Orders', 'Total Value'],
                $exportRows,
                $request->format,
                array_merge(
                    $this->buildMeta($request, ['department_id', 'date_from', 'date_to']),
                    [
                        'Total PO Value'    => number_format($totalValue, 2),
                        'Total Disbursed'   => number_format($totalDisbursed, 2),
                    ]
                )
            );
        }

        return $this->success([
            'summary' => [
                'total_purchase_orders' => $totalPOs,
                'total_po_value'        => round($totalValue, 2),
                'total_disbursed'       => round($totalDisbursed, 2),
                'outstanding'           => round($totalValue - $totalDisbursed, 2),
            ],
            'by_department'    => $rows,
            'by_payment_status'=> $paymentBreakdown,
        ]);
    }

    public function stats(Request $request)
    {
        $activeProjects = Project::active();
        $projectIds = $activeProjects->pluck('id')->toArray();

        if (empty($projectIds)) {
            return $this->success([
                'total_allocated' => 0,
                'total_committed' => 0,
                'total_actual_spent' => 0,
                'total_pending_request_amount' => 0,
                'total_available' => 0,
                'total_projects' => 0,
                'total_budget_lines' => 0,
                'flagged_lines_count' => 0,
                'pending_approvals_count' => 0,
                'pending_approvals_amount' => 0,
                'approved_finance_count' => 0,
                'rejected_finance_count' => 0,
            ]);
        }

        $totalAllocated = ProjectBudgetLine::whereIn('project_id', $projectIds)->sum('total_cost');
        $totalBudgetLines = ProjectBudgetLine::whereIn('project_id', $projectIds)->count();
        $totalProjects = count($projectIds);

        $totalCommitted = PurchaseOrder::whereIn('status', ['approved', 'ordered', 'partially_received', 'received', 'disbursed'])
            ->whereHas('requisitions', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->sum('total_amount');

        $totalActualSpent = Disbursement::whereHas('purchaseOrder.requisitions', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->sum('amount');

        $totalPendingRequestAmount = Requisition::where('status', 'pending_approval')
            ->whereIn('project_id', $projectIds)
            ->sum('estimated_cost');

        $pendingApprovalsCount = Requisition::where('status', 'pending_approval')
            ->whereHas('approvals', fn ($q) => $q->where('stage', 'finance')->where('status', 'pending'))
            ->count();

        $pendingApprovalsAmount = Requisition::where('status', 'pending_approval')
            ->whereHas('approvals', fn ($q) => $q->where('stage', 'finance')->where('status', 'pending'))
            ->sum('estimated_cost');

        $approvedFinanceCount = RequisitionAuditLog::where('stage', 'finance')
            ->where('action', 'approved')
            ->whereHas('requisition', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->count();

        $rejectedFinanceCount = RequisitionAuditLog::where('stage', 'finance')
            ->where('action', 'rejected')
            ->whereHas('requisition', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->count();

        $totalAvailable = $totalAllocated - $totalCommitted - $totalActualSpent - $totalPendingRequestAmount;

        return $this->success([
            'total_allocated' => (float) round($totalAllocated, 2),
            'total_committed' => (float) round($totalCommitted, 2),
            'total_actual_spent' => (float) round($totalActualSpent, 2),
            'total_pending_request_amount' => (float) round($totalPendingRequestAmount, 2),
            'total_available' => (float) round($totalAvailable, 2),
            'total_projects' => $totalProjects,
            'total_budget_lines' => $totalBudgetLines,
            'flagged_lines_count' => $this->getFlaggedBudgetLineCount($projectIds),
            'pending_approvals_count' => $pendingApprovalsCount,
            'pending_approvals_amount' => (float) round($pendingApprovalsAmount, 2),
            'approved_finance_count' => $approvedFinanceCount,
            'rejected_finance_count' => $rejectedFinanceCount,
        ]);
    }

    public function flaggedBudgetLines(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 25), 0), 100);
        $page = max((int) $request->input('page', 1), 1);
        $allowedStatuses = ['low', 'critical', 'overdrawn'];
        $statusFilter = collect(explode(',', $request->input('status', '')))
            ->map(fn ($status) => trim(strtolower($status)))
            ->filter(fn ($status) => in_array($status, $allowedStatuses))
            ->values()
            ->all();

        $query = ProjectBudgetLine::with(['project', 'budgetCategory'])
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', ['closed']));

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $lines = $query->orderBy('project_id')->orderBy('description')->get();
        $metrics = $this->buildBudgetLineMetrics($lines);

        $budgetLines = $lines->map(function (ProjectBudgetLine $line) use ($metrics) {
            $key = $this->budgetLineKey($line->project?->project_code, $line->description);
            $row = $this->serializeBudgetLine($line, $metrics[$key] ?? []);
            return $row;
        });

        if (!empty($statusFilter)) {
            $budgetLines = $budgetLines->filter(fn ($row) => in_array($row['status'], $statusFilter));
        }

        $budgetLines = $budgetLines->values();

        if ($perPage === 0) {
            return $this->success([
                'budget_lines' => $budgetLines,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 0,
                    'total' => $budgetLines->count(),
                ],
            ]);
        }

        $paginated = new LengthAwarePaginator(
            $budgetLines->forPage($page, $perPage)->values(),
            $budgetLines->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->success([
            'budget_lines' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function recentActions(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $allowedActions = ['approved', 'rejected', 'forwarded', 'revision'];
        $actions = collect(explode(',', $request->input('action', '')))
            ->map(fn ($action) => trim(strtolower($action)))
            ->filter(fn ($action) => in_array($action, $allowedActions))
            ->values()
            ->all();

        $query = RequisitionAuditLog::with(['requisition.project', 'requisition.items'])
            ->where('stage', 'finance');

        if (!empty($actions)) {
            $query->whereIn('action', $actions);
        }

        $paginated = $query->orderByDesc('created_at')->paginate($perPage);

        $rows = collect($paginated->items())->map(function (RequisitionAuditLog $log) {
            $requisition = $log->requisition;
            return [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->actor_name,
                'actor_id' => $log->actor_id,
                'requisition_id' => $log->requisition_id,
                'requisition_number' => $requisition?->requisition_number,
                'project_name' => $requisition?->project?->name,
                'project_code' => $requisition?->project_code,
                'budget_line_description' => $requisition?->items->first()?->budget_line,
                'amount' => $requisition?->estimated_cost ? (float) $requisition->estimated_cost : 0,
                'comments' => $log->comments,
                'created_at' => $log->created_at?->toISOString(),
            ];
        });

        return $this->success([
            'actions' => $rows,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    private function budgetLineKey(?string $projectCode, ?string $budgetLine): string
    {
        return trim(strtolower((string) $projectCode)) . '||' . trim(strtolower((string) $budgetLine));
    }

    private function normalizeKey(?string $value): string
    {
        return trim(strtolower((string) $value));
    }

    private function deriveBudgetLineStatus(float $utilization): string
    {
        if ($utilization > 100) {
            return 'overdrawn';
        }
        if ($utilization >= 90) {
            return 'critical';
        }
        if ($utilization >= 70) {
            return 'low';
        }
        return 'healthy';
    }

    private function getFlaggedBudgetLineCount(array $projectIds): int
    {
        $lines = ProjectBudgetLine::with('project')
            ->whereIn('project_id', $projectIds)
            ->get();

        $metrics = $this->buildBudgetLineMetrics($lines);

        return collect($metrics)->filter(fn ($metric) => in_array($metric['status'], ['low', 'critical', 'overdrawn']))->count();
    }

    private function buildBudgetLineMetrics(Collection $lines): array
    {
        $metrics = [];
        $projectIds = $lines->pluck('project_id')->unique()->filter()->values()->all();
        $projectCodes = $lines->map(fn (ProjectBudgetLine $line) => $line->project?->project_code)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $validPurchaseOrderIds = PurchaseOrder::whereIn('status', ['approved', 'ordered', 'partially_received', 'received', 'disbursed'])
            ->whereHas('requisitions', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->pluck('id')
            ->toArray();

        $pendingRequests = RequisitionItem::selectRaw('LOWER(TRIM(requisition_items.budget_line)) as line_key, requisition_items.project_code, SUM(requisition_items.estimated_total_cost) as total')
            ->join('requisitions', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->where('requisitions.status', 'pending_approval')
            ->whereIn('requisitions.project_id', $projectIds)
            ->whereNotNull('requisition_items.budget_line')
            ->groupBy('requisition_items.project_code', 'line_key')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $this->budgetLineKey($row->project_code, $row->line_key) => (float) $row->total,
            ])->toArray();

        $committed = PurchaseOrderItem::selectRaw('LOWER(TRIM(budget_line)) as line_key, project_code, SUM(total_price) as total')
            ->whereIn('purchase_order_id', $validPurchaseOrderIds)
            ->whereNotNull('budget_line')
            ->groupBy('project_code', 'line_key')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $this->budgetLineKey($row->project_code, $row->line_key) => (float) $row->total,
            ])->toArray();

        $poTotals = PurchaseOrderItem::selectRaw('purchase_order_id, SUM(total_price) as total')
            ->whereIn('purchase_order_id', $validPurchaseOrderIds)
            ->groupBy('purchase_order_id')
            ->pluck('total', 'purchase_order_id')
            ->toArray();

        $disbursementTotals = Disbursement::selectRaw('purchase_order_id, SUM(amount) as total')
            ->whereIn('purchase_order_id', $validPurchaseOrderIds)
            ->groupBy('purchase_order_id')
            ->pluck('total', 'purchase_order_id')
            ->toArray();

        $actual = [];
        $poLineGroups = PurchaseOrderItem::selectRaw('purchase_order_id, project_code, LOWER(TRIM(budget_line)) as line_key, SUM(total_price) as line_total')
            ->whereIn('purchase_order_id', $validPurchaseOrderIds)
            ->whereNotNull('budget_line')
            ->groupBy('purchase_order_id', 'project_code', 'line_key')
            ->get();

        foreach ($poLineGroups as $row) {
            $poId = $row->purchase_order_id;
            $poTotal = $poTotals[$poId] ?? 0;
            $disbursementValue = $disbursementTotals[$poId] ?? 0;
            if ($poTotal <= 0) {
                continue;
            }
            $share = (float) $row->line_total / $poTotal;
            $key = $this->budgetLineKey($row->project_code, $row->line_key);
            $actual[$key] = ($actual[$key] ?? 0) + ($disbursementValue * $share);
        }

        $lastActivity = [];

        $requisitionActivity = RequisitionItem::selectRaw('LOWER(TRIM(requisition_items.budget_line)) as line_key, requisition_items.project_code, MAX(requisition_items.updated_at) as last_activity')
            ->join('requisitions', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->whereIn('requisitions.project_id', $projectIds)
            ->whereNotNull('requisition_items.budget_line')
            ->groupBy('requisition_items.project_code', 'line_key')
            ->get();

        foreach ($requisitionActivity as $row) {
            $key = $this->budgetLineKey($row->project_code, $row->line_key);
            $lastActivity[$key] = $row->last_activity;
        }

        $purchaseOrderActivity = PurchaseOrderItem::selectRaw('project_code, LOWER(TRIM(budget_line)) as line_key, MAX(updated_at) as last_activity')
            ->whereIn('purchase_order_id', $validPurchaseOrderIds)
            ->whereNotNull('budget_line')
            ->groupBy('project_code', 'line_key')
            ->get();

        foreach ($purchaseOrderActivity as $row) {
            $key = $this->budgetLineKey($row->project_code, $row->line_key);
            $existing = $lastActivity[$key] ?? null;
            $current = $row->last_activity;
            if (!$existing || $current > $existing) {
                $lastActivity[$key] = $current;
            }
        }

        foreach ($lines as $line) {
            $key = $this->budgetLineKey($line->project?->project_code, $line->description);
            $allocated = (float) $line->total_cost;
            $committedAmount = $committed[$key] ?? 0;
            $pendingAmount = $pendingRequests[$key] ?? 0;
            $actualAmount = $actual[$key] ?? 0;
            $utilization = $allocated > 0 ? (($committedAmount + $actualAmount + $pendingAmount) / $allocated) * 100 : 0;

            $metrics[$key] = [
                'allocated_amount' => $allocated,
                'committed_amount' => round($committedAmount, 2),
                'actual_spent_amount' => round($actualAmount, 2),
                'pending_request_amount' => round($pendingAmount, 2),
                'available_amount' => round($allocated - $committedAmount - $actualAmount - $pendingAmount, 2),
                'utilization_percent' => (int) round($utilization),
                'status' => $this->deriveBudgetLineStatus($utilization),
                'last_activity_at' => isset($lastActivity[$key]) ? Carbon::parse($lastActivity[$key])->toISOString() : null,
            ];
        }

        return $metrics;
    }

    private function serializeBudgetLine(ProjectBudgetLine $line, array $metrics): array
    {
        return array_merge($line->toApiArray(), [
            'project_name' => $line->project?->name,
            'project_code' => $line->project?->project_code,
            'budget_category' => $line->budgetCategory?->name,
            'allocated_amount' => $metrics['allocated_amount'] ?? (float) $line->total_cost,
            'committed_amount' => $metrics['committed_amount'] ?? 0,
            'actual_spent_amount' => $metrics['actual_spent_amount'] ?? 0,
            'pending_request_amount' => $metrics['pending_request_amount'] ?? 0,
            'available_amount' => $metrics['available_amount'] ?? ((float) $line->total_cost - ($metrics['committed_amount'] ?? 0) - ($metrics['actual_spent_amount'] ?? 0) - ($metrics['pending_request_amount'] ?? 0)),
            'utilization_percent' => $metrics['utilization_percent'] ?? 0,
            'status' => $metrics['status'] ?? 'healthy',
            'last_activity_at' => $metrics['last_activity_at'] ?? $line->updated_at?->toISOString(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────

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
