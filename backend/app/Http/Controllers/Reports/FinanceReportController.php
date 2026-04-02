<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\PurchaseOrder;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
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
