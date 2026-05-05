<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcessInvoiceApprovalRequest;
use App\Http\Requests\Procurement\StoreInvoiceRequest;
use App\Http\Requests\Procurement\UpdateInvoiceRequest;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Services\Procurement\DocumentChainResolver;
use App\Services\Procurement\DocumentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(
        private DocumentScopeService $scopeService,
        private DocumentChainResolver $chainResolver,
    ) {
    }

    /**
     * GET /api/v1/procurement/invoices
     *
     * Filters: ?status, ?po_id, ?vendor_id, ?search, ?mine
     * Pagination: ?page, ?per_page (default 25, max 100)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['vendor', 'purchaseOrder', 'creator', 'approver']);

        if ($this->scopeService->shouldScope($request->user(), 'procurement.invoices.view_all', $request)) {
            $this->scopeService->applyToInvoices($query, $request->user());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('po_id')) {
            $query->where('po_id', $request->string('po_id'));
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->string('vendor_id'));
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('purchaseOrder', fn ($pq) => $pq->where('po_number', 'like', "%{$search}%"));
            });
        }

        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['invoice_number', 'invoice_date', 'due_date', 'amount', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->paginate($perPage);

        return $this->success([
            'invoices' => collect($paginated->items())->map->toApiArray(),
            'meta'     => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/invoices
     *
     * Creates a new invoice in `pending` status. Vendor is denormalised
     * from the PO so list filtering doesn't require a JOIN.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            // Pull vendor from the PO so the invoice always lines up with
            // what the PO was actually issued against.
            $po = PurchaseOrder::findOrFail($data['po_id']);

            $invoice = Invoice::create([
                'invoice_number' => $data['invoice_number'],
                'po_id'          => $po->id,
                'vendor_id'      => $po->vendor_id,
                'amount'         => $data['amount'],
                'currency'       => $data['currency'] ?? $po->currency ?? 'NGN',
                'invoice_date'   => $data['invoice_date'],
                'due_date'       => $data['due_date'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'status'         => 'pending',
                'created_by'     => $request->user()->id,
                'submitted_by'   => $request->user()->id,
            ]);

            $invoice->load(['vendor', 'purchaseOrder', 'creator']);

            AuditLog::record(
                $request->user()->id,
                'invoice_created',
                'invoice',
                $invoice->id,
                null,
                $invoice->toApiArray()
            );

            return $this->success([
                'invoice' => $invoice->toDetailArray(),
            ], 'Invoice recorded successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/invoices/{id}
     */
    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with(['vendor', 'purchaseOrder', 'creator', 'approver'])
            ->findOrFail($id);

        return $this->success([
            'invoice' => array_merge(
                $invoice->toDetailArray(),
                ['chain' => $this->chainResolver->forInvoice($invoice)]
            ),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/invoices/{id}
     *
     * Only invoices that are still pending may be edited. Once Finance has
     * approved/rejected, the row is locked.
     */
    public function update(UpdateInvoiceRequest $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'pending') {
            return $this->error('Only pending invoices can be edited.', 422);
        }

        $oldValues = $invoice->toApiArray();

        return DB::transaction(function () use ($request, $invoice, $oldValues) {
            $data = $request->validated();

            // If the PO changes, re-derive the vendor from the new PO.
            if (isset($data['po_id']) && $data['po_id'] !== $invoice->po_id) {
                $po = PurchaseOrder::findOrFail($data['po_id']);
                $data['vendor_id'] = $po->vendor_id;
            }

            $invoice->update($data);
            $invoice->load(['vendor', 'purchaseOrder', 'creator', 'approver']);

            AuditLog::record(
                $request->user()->id,
                'invoice_updated',
                'invoice',
                $invoice->id,
                $oldValues,
                $invoice->toApiArray()
            );

            return $this->success([
                'invoice' => $invoice->toDetailArray(),
            ], 'Invoice updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/invoices/{id}
     *
     * Soft-cancels the invoice (status = cancelled). Hard delete is not
     * supported — invoices are financial records and must remain in the
     * audit trail. An invoice that has already been paid cannot be
     * cancelled; an invoice that has been approved but not paid can be,
     * with a clear cancellation in the audit log.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return $this->error('Paid invoices cannot be cancelled.', 422);
        }
        if ($invoice->status === 'cancelled') {
            return $this->error('This invoice is already cancelled.', 422);
        }

        // If any RFP already references this invoice, block — they must
        // unlink first.
        $linkedRfps = $invoice->rfps()->whereNotIn('status', ['rejected', 'cancelled'])->count();
        if ($linkedRfps > 0) {
            return $this->error('Cannot cancel an invoice referenced by an active payment request.', 422);
        }

        $oldValues = $invoice->toApiArray();

        return DB::transaction(function () use ($request, $invoice, $oldValues) {
            $invoice->update(['status' => 'cancelled']);

            AuditLog::record(
                $request->user()->id,
                'invoice_cancelled',
                'invoice',
                $invoice->id,
                $oldValues,
                $invoice->fresh()->toApiArray()
            );

            return $this->success(null, 'Invoice cancelled successfully');
        });
    }

    /**
     * PATCH /api/v1/procurement/invoices/{id}/approval
     *
     * Single-step Finance approval. Approve flips status to `approved`
     * and stamps approved_by + approved_at; reject flips to `rejected`
     * and records the reason. Once an invoice is approved, an RFP can
     * pay against it.
     */
    public function processApproval(ProcessInvoiceApprovalRequest $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'pending') {
            return $this->error('Only pending invoices can be approved or rejected.', 422);
        }

        $data = $request->validated();
        $oldValues = $invoice->toApiArray();
        $now = now();
        $user = $request->user();

        return DB::transaction(function () use ($invoice, $data, $oldValues, $now, $user) {
            if ($data['action'] === 'approve') {
                $invoice->update([
                    'status'          => 'approved',
                    'approved_by'     => $user->id,
                    'approved_at'     => $now,
                    'rejected_reason' => null,
                ]);
                $action = 'invoice_approved';
                $message = 'Invoice approved';
            } else {
                $invoice->update([
                    'status'          => 'rejected',
                    'approved_by'     => $user->id,
                    'approved_at'     => $now,
                    'rejected_reason' => $data['rejected_reason'] ?? null,
                ]);
                $action = 'invoice_rejected';
                $message = 'Invoice rejected';
            }

            $invoice->load(['vendor', 'purchaseOrder', 'creator', 'approver']);

            AuditLog::record(
                $user->id,
                $action,
                'invoice',
                $invoice->id,
                $oldValues,
                $invoice->toApiArray()
            );

            return $this->success([
                'invoice' => $invoice->toDetailArray(),
            ], $message);
        });
    }
}
