<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Vendor;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

class ProcurementReportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    /**
     * GET /api/v1/reports/procurement/purchase-orders
     */
    public function purchaseOrders(Request $request)
    {
        $request->validate([
            'format'         => 'nullable|in:csv,excel,pdf',
            'status'         => 'nullable|in:draft,submitted,revision,pending_approval,approved,ordered,partially_received,received,disbursed,cancelled',
            'payment_status' => 'nullable|in:unpaid,partially_paid,paid',
            'vendor_id'      => 'nullable|uuid|exists:vendors,id',
            'department_id'  => 'nullable|uuid|exists:departments,id',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = PurchaseOrder::with(['vendor:id,name', 'department:id,name']);

        if ($request->filled('status'))         $query->where('status', $request->status);
        if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
        if ($request->filled('vendor_id'))      $query->where('vendor_id', $request->vendor_id);
        if ($request->filled('department_id'))  $query->where('department_id', $request->department_id);
        if ($request->filled('date_from'))      $query->whereDate('order_date', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('order_date', '<=', $request->date_to);

        $query->orderByDesc('order_date');

        $mapRow = fn (PurchaseOrder $po) => [
            'po_number'      => $po->po_number,
            'order_date'     => $po->order_date?->format('Y-m-d') ?? '—',
            'vendor'         => $po->vendor?->name ?? '—',
            'department'     => $po->department?->name ?? '—',
            'subtotal'       => number_format($po->subtotal, 2),
            'tax'            => number_format($po->tax_amount, 2),
            'total'          => number_format($po->total_amount, 2),
            'payment_status' => ucwords(str_replace('_', ' ', $po->payment_status ?? '—')),
            'status'         => ucwords(str_replace('_', ' ', $po->status)),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('procurement_purchase_orders', $request);

            return $this->exportService->export(
                'Purchase Orders Report',
                ['PO Number', 'Date', 'Vendor', 'Department', 'Subtotal', 'Tax', 'Total', 'Payment Status', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status', 'payment_status', 'vendor_id', 'department_id', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/procurement/requisitions
     */
    public function requisitions(Request $request)
    {
        $request->validate([
            'format'        => 'nullable|in:csv,excel,pdf',
            'status'        => 'nullable|in:draft,submitted,revision,pending_approval,approved,fulfilled,cancelled',
            'priority'      => 'nullable|in:low,medium,high,urgent',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Requisition::with(['department:id,name', 'requestedBy:id,name']);

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('priority'))      $query->where('priority', $request->priority);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('date_from'))     $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))       $query->whereDate('created_at', '<=', $request->date_to);

        $query->orderByDesc('created_at');

        $mapRow = fn (Requisition $r) => [
            'requisition_number' => $r->requisition_number,
            'title'              => $r->title,
            'date'               => $r->created_at->format('Y-m-d'),
            'department'         => $r->department?->name ?? '—',
            'requested_by'       => $r->requestedBy?->name ?? '—',
            'estimated_cost'     => number_format($r->estimated_cost, 2),
            'priority'           => ucfirst($r->priority),
            'status'             => ucwords(str_replace('_', ' ', $r->status)),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('procurement_requisitions', $request);

            return $this->exportService->export(
                'Requisitions Report',
                ['Req. No', 'Title', 'Date', 'Department', 'Requested By', 'Estimated Cost', 'Priority', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status', 'priority', 'department_id', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/procurement/vendors
     */
    public function vendors(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:csv,excel,pdf',
            'status' => 'nullable|in:active,inactive',
        ]);

        $query = Vendor::withCount('purchaseOrders')
            ->withSum('purchaseOrders', 'total_amount');

        if ($request->filled('status')) $query->where('status', $request->status);

        $query->orderBy('name');

        $mapRow = fn (Vendor $v) => [
            'name'           => $v->name,
            'contact_person' => $v->contact_person ?? '—',
            'email'          => $v->email ?? '—',
            'phone'          => $v->phone ?? '—',
            'city'           => $v->city ?? '—',
            'total_pos'      => $v->purchase_orders_count,
            'total_value'    => number_format($v->purchase_orders_sum_total_amount ?? 0, 2),
            'status'         => ucfirst($v->status),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('procurement_vendors', $request);

            return $this->exportService->export(
                'Vendor Summary',
                ['Vendor', 'Contact Person', 'Email', 'Phone', 'City', 'Total POs', 'Total Value', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/procurement/inventory
     */
    public function inventory(Request $request)
    {
        $request->validate([
            'format'   => 'nullable|in:csv,excel,pdf',
            'status'   => 'nullable|in:active,inactive,out_of_stock',
            'category' => 'nullable|string|max:100',
        ]);

        $query = InventoryItem::query();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('category')) $query->where('category', $request->category);

        $query->orderBy('name');

        $mapRow = fn (InventoryItem $i) => [
            'name'              => $i->name,
            'sku'               => $i->sku ?? '—',
            'category'          => $i->category ?? '—',
            'unit'              => $i->unit ?? '—',
            'quantity_in_stock' => $i->quantity_in_stock,
            'reorder_level'     => $i->reorder_level,
            'unit_cost'         => number_format($i->unit_cost, 2),
            'status'            => ucwords(str_replace('_', ' ', $i->status)),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('procurement_inventory', $request);

            return $this->exportService->export(
                'Inventory Report',
                ['Item', 'SKU', 'Category', 'Unit', 'Qty in Stock', 'Reorder Level', 'Unit Cost', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status', 'category'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/procurement/disbursements
     */
    public function disbursements(Request $request)
    {
        $request->validate([
            'format'         => 'nullable|in:csv,excel,pdf',
            'payment_method' => 'nullable|in:bank_transfer,cheque,cash,mobile_money',
            'vendor_id'      => 'nullable|uuid|exists:vendors,id',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Disbursement::with([
            'purchaseOrder:id,po_number,vendor_id',
            'purchaseOrder.vendor:id,name',
            'disbursedBy:id,name',
        ]);

        if ($request->filled('payment_method')) $query->where('payment_method', $request->payment_method);
        if ($request->filled('vendor_id'))      $query->whereHas('purchaseOrder', fn ($q) => $q->where('vendor_id', $request->vendor_id));
        if ($request->filled('date_from'))      $query->whereDate('payment_date', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('payment_date', '<=', $request->date_to);

        $query->orderByDesc('payment_date');

        $mapRow = fn (Disbursement $d) => [
            'payment_date'     => $d->payment_date?->format('Y-m-d') ?? '—',
            'po_number'        => $d->purchaseOrder?->po_number ?? '—',
            'vendor'           => $d->purchaseOrder?->vendor?->name ?? '—',
            'amount'           => number_format($d->amount, 2),
            'payment_method'   => ucwords(str_replace('_', ' ', $d->payment_method)),
            'reference'        => $d->payment_reference ?? '—',
            'disbursed_by'     => $d->disbursedBy?->name ?? '—',
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('procurement_disbursements', $request);

            return $this->exportService->export(
                'Disbursements Report',
                ['Date', 'PO Number', 'Vendor', 'Amount', 'Payment Method', 'Reference', 'Disbursed By'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['payment_method', 'vendor_id', 'date_from', 'date_to'])
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
