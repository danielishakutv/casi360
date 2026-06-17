<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreRfpRequest;
use App\Http\Requests\Procurement\UpdateRfpRequest;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Rfp;
use App\Models\RfpItem;
use App\Services\NotificationService;
use App\Services\Procurement\DocumentChainResolver;
use App\Services\Procurement\DocumentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RfpController extends Controller
{
    public function __construct(
        private DocumentScopeService $scopeService,
        private DocumentChainResolver $chainResolver,
    ) {
    }

    /**
     * GET /api/v1/procurement/rfp
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rfp::with(['vendor', 'approvals']);

        if ($this->scopeService->shouldScope($request->user(), 'procurement.rfp.view_all', $request)) {
            $this->scopeService->applyToRfps($query, $request->user());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('po_reference')) {
            $query->where('po_reference', $request->po_reference);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rfp_number', 'like', "%{$search}%")
                  ->orWhere('po_reference', 'like', "%{$search}%")
                  ->orWhere('grn_reference', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['rfp_number', 'status', 'payment_date', 'total_amount', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $rfps = $query->get();
            return $this->success([
                'rfps' => $rfps->map->toApiArray(),
                'meta' => ['total' => $rfps->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'rfps' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/rfp
     */
    public function store(StoreRfpRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Gate: a payment request can only pay an *approved* invoice. We
        // accept null for backward compatibility, but if an invoice_id is
        // supplied it must be currently approved (not pending, rejected,
        // cancelled, or already paid).
        if (!empty($data['invoice_id'])) {
            $invoice = Invoice::find($data['invoice_id']);
            if (!$invoice) {
                return $this->error('Invoice not found.', 422);
            }
            if ($invoice->status !== 'approved') {
                return $this->error(
                    "Invoice {$invoice->invoice_number} is {$invoice->status}; only approved invoices can be paid.",
                    422
                );
            }
        }

        return DB::transaction(function () use ($request, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['rfp_number'] = Rfp::generateRfpNumber();
            // Record the raiser for segregation of duties (v2 §4) — they may
            // not later approve this payment request.
            $data['raised_by'] = auth()->id();
            // Raising a payment request enters the approval chain by default
            // (v2 §3.3). A caller may pass status=draft to stage one first.
            $data['status'] = $data['status'] ?? 'pending_approval';

            // v2 §3.2 — snapshot who affirmed the compliance checklist and when
            // (server-side, never trusted from the client). When procedures were
            // followed, clear any stray waiver evidence so it can't linger.
            if (!empty($data['procurement_compliance'])) {
                $data['compliance_confirmed_by'] = auth()->id();
                $data['compliance_confirmed_at'] = now();
                if ($data['procurement_compliance'] === 'followed') {
                    $data['compliance_justification'] = null;
                    $data['compliance_document_url'] = null;
                }
            }

            // Mirror the first selected PO/GRN reference onto the canonical
            // singular columns so the document-chain breadcrumb keeps resolving.
            if (empty($data['po_reference']) && !empty($data['po_references'][0])) {
                $data['po_reference'] = $data['po_references'][0];
            }
            if (empty($data['grn_reference']) && !empty($data['grn_references'][0])) {
                $data['grn_reference'] = $data['grn_references'][0];
            }

            // If line items provided, compute subtotal from them
            if (!empty($items)) {
                $subtotal = 0;
                foreach ($items as &$item) {
                    $item['total'] = $item['quantity'] * $item['unit_cost'];
                    $subtotal += $item['total'];
                }
                unset($item);
                $data['subtotal'] = $subtotal;
            }

            // Auto-calculate tax
            $subtotal = (float) ($data['subtotal'] ?? 0);
            $taxRate = (float) ($data['tax_rate'] ?? 5);
            $data['tax_rate'] = $taxRate;
            $data['tax_amount'] = round($subtotal * ($taxRate / 100), 2);
            $data['total_amount'] = $subtotal + $data['tax_amount'];

            $rfp = Rfp::create($data);

            foreach ($items as $item) {
                $item['rfp_id'] = $rfp->id;
                RfpItem::create($item);
            }

            // Open the payment approval chain unless this was saved as a draft.
            if ($rfp->status === 'pending_approval') {
                $rfp->createApprovalChain();
                NotificationService::rfpPending($rfp, 'programme_manager');
            }

            $rfp->load(['vendor', 'items', 'approvals']);

            AuditLog::record(
                auth()->id(),
                'rfp_created',
                'rfp',
                $rfp->id,
                null,
                $rfp->toApiArray()
            );

            return $this->success([
                'rfp' => $rfp->toDetailArray(),
            ], 'RFP created successfully', 201);
        });
    }

    /**
     * POST /api/v1/procurement/rfp/{id}/submit
     *
     * (Re)submit a draft / revision / rejected payment request for approval —
     * resets the chain to Programme Manager. The compliance checklist must be
     * satisfied first (v2 §3.2/§3.3).
     */
    public function submit(string $id): JsonResponse
    {
        $rfp = Rfp::with('approvals')->findOrFail($id);

        if (!in_array($rfp->status, ['draft', 'revision', 'rejected'], true)) {
            return $this->error('Only draft, revision, or rejected payment requests can be submitted for approval.', 422);
        }

        if (empty($rfp->procurement_compliance)) {
            return $this->error('Complete the procurement compliance checklist before submitting this payment request.', 422);
        }

        return DB::transaction(function () use ($rfp) {
            $rfp->update(['status' => 'pending_approval']);
            $rfp->createApprovalChain();
            NotificationService::rfpPending($rfp, 'programme_manager');
            $rfp->load(['vendor', 'items', 'approvals']);

            AuditLog::record(auth()->id(), 'rfp_submitted', 'rfp', $rfp->id, null, $rfp->toApiArray());

            return $this->success([
                'rfp' => $rfp->toDetailArray(),
            ], 'Payment request submitted for approval');
        });
    }

    /**
     * GET /api/v1/procurement/rfp/{id}
     */
    public function show(string $id): JsonResponse
    {
        $rfp = Rfp::with(['vendor', 'items', 'invoice', 'approvals'])->findOrFail($id);

        return $this->success([
            'rfp' => array_merge(
                $rfp->toDetailArray(),
                ['chain' => $this->chainResolver->forRfp($rfp)]
            ),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/rfp/{id}
     *
     * Editing windows:
     *   - draft / pending / submitted : freely editable
     *   - approved                    : ONLY allowed to transition to `paid`
     *                                   (so Finance can record the payment
     *                                   without a separate endpoint)
     *   - any other status            : locked
     */
    public function update(UpdateRfpRequest $request, string $id): JsonResponse
    {
        $rfp = Rfp::with(['vendor', 'items'])->findOrFail($id);

        $incomingStatus = $request->input('status');
        $isApprovedToPaid = $rfp->status === 'approved' && $incomingStatus === 'paid';
        $canEdit = in_array($rfp->status, ['draft', 'pending', 'submitted', 'revision'], true) || $isApprovedToPaid;

        if (!$canEdit) {
            return $this->error('Only draft, revision, or submitted payment requests can be edited (approved ones may only be marked as paid).', 422);
        }

        $oldValues = $rfp->toApiArray();
        // Capture intent before the model is mutated so the post-save logic
        // knows whether this PATCH was the "go to paid" transition.
        $becomingPaid = $incomingStatus === 'paid' && $rfp->status !== 'paid';

        return DB::transaction(function () use ($request, $rfp, $oldValues, $becomingPaid) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $rfp->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['total'] = $item['quantity'] * $item['unit_cost'];

                    if (!empty($item['id'])) {
                        $rfpItem = RfpItem::where('id', $item['id'])
                            ->where('rfp_id', $rfp->id)
                            ->first();
                        if ($rfpItem) {
                            $rfpItem->update($item);
                            $existingIds[] = $rfpItem->id;
                        }
                    } else {
                        $item['rfp_id'] = $rfp->id;
                        $newItem = RfpItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                RfpItem::where('rfp_id', $rfp->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            // Recalculate totals
            $rfp->refresh();
            $rfp->recalculateTotals();

            $rfp->refresh();
            $rfp->load(['vendor', 'items']);

            // If this PATCH flipped the RFP to `paid` AND it references an
            // approved invoice, flip that invoice to paid too. Defence:
            // never touch an invoice that is already paid, rejected, or
            // cancelled — the bookkeeper can correct the status manually
            // if something is amiss, but the cycle should not silently
            // overwrite their work.
            if ($becomingPaid && $rfp->invoice_id) {
                $invoice = Invoice::find($rfp->invoice_id);
                if ($invoice && $invoice->status === 'approved') {
                    $oldInvoiceValues = $invoice->toApiArray();
                    $invoice->update(['status' => 'paid']);

                    AuditLog::record(
                        auth()->id(),
                        'invoice_paid',
                        'invoice',
                        $invoice->id,
                        $oldInvoiceValues,
                        $invoice->fresh()->toApiArray()
                    );
                }
            }

            AuditLog::record(
                auth()->id(),
                'rfp_updated',
                'rfp',
                $rfp->id,
                $oldValues,
                $rfp->toApiArray()
            );

            return $this->success([
                'rfp' => $rfp->toDetailArray(),
            ], $becomingPaid ? 'RFP marked as paid' : 'RFP updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/rfp/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $rfp = Rfp::findOrFail($id);

        if ($rfp->status === 'paid') {
            return $this->error('Cannot delete a paid RFP.', 422);
        }

        return DB::transaction(function () use ($rfp, $id) {
            $rfpData = $rfp->toApiArray();
            $rfp->update(['status' => 'rejected']);

            AuditLog::record(
                auth()->id(),
                'rfp_deleted',
                'rfp',
                $id,
                $rfpData,
                ['status' => 'rejected']
            );

            return $this->success(null, 'RFP rejected/deleted successfully');
        });
    }
}
