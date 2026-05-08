<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreRfqRequest;
use App\Http\Requests\Procurement\UpdateRfqRequest;
use App\Models\AuditLog;
use App\Models\Requisition;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Services\Procurement\ApprovalAuthorizer;
use App\Services\Procurement\DocumentChainResolver;
use App\Services\Procurement\DocumentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    public function __construct(
        private ApprovalAuthorizer $authorizer,
        private DocumentScopeService $scopeService,
        private DocumentChainResolver $chainResolver,
    ) {
    }

    /**
     * GET /api/v1/procurement/rfq
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rfq::with('vendor')->withCount('vendors');

        if ($this->scopeService->shouldScope($request->user(), 'procurement.rfq.view_all', $request)) {
            $this->scopeService->applyToRfqs($query, $request->user());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rfq_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['rfq_number', 'title', 'status', 'issue_date', 'deadline', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $rfqs = $query->get();
            return $this->success([
                'rfqs' => $rfqs->map->toApiArray(),
                'meta' => ['total' => $rfqs->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'rfqs' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/rfq
     */
    public function store(StoreRfqRequest $request): JsonResponse
    {
        // Only Procurement managers (or admins) may raise RFQs
        if (!$this->authorizer->canManageRfqs($request->user())) {
            return $this->error(
                'Only a manager in the Procurement department (or an administrator) can create a Request for Quotation.',
                403
            );
        }

        // If linked to a Purchase Request, it must be fully approved
        $prRef = $request->input('pr_reference');
        if ($prRef) {
            $pr = Requisition::where('requisition_number', $prRef)->first();
            if (!$pr) {
                return $this->error("No purchase request found with number {$prRef}.", 422);
            }
            if ($pr->status !== 'approved') {
                return $this->error(
                    "Purchase request {$prRef} is not yet approved (current status: {$pr->status}). RFQs can only be created from approved purchase requests.",
                    422
                );
            }
        }

        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Multi-vendor + scope handling. The form sends `vendor_ids` as
            // the source of truth; `vendor_id` is kept as the "primary"
            // recipient for backward compatibility with downstream code
            // (PO creation reads from it).
            $vendorIds = array_values(array_unique($data['vendor_ids'] ?? []));
            unset($data['vendor_ids']);

            $scope = $data['scope'] ?? 'targeted';
            $data['scope'] = $scope;

            if ($scope === 'targeted') {
                if (empty($vendorIds) && empty($data['vendor_id'] ?? null)) {
                    return $this->error('Select at least one vendor for a targeted RFQ.', 422);
                }
                if (empty($data['vendor_id']) && !empty($vendorIds)) {
                    $data['vendor_id'] = $vendorIds[0];
                }
                // Open-call-only field is irrelevant when targeting vendors.
                $data['advertised_on'] = null;
            } else { // open
                if (!empty($vendorIds)) {
                    return $this->error('Open-call RFQs do not pin specific vendors. Switch to targeted to pick vendors.', 422);
                }
                $data['vendor_id'] = null;
                $vendorIds = [];
            }

            $data['rfq_number'] = Rfq::generateRfqNumber();
            // RFQs land in 'open' state by default — once the form is
            // saved, the document is ready to be downloaded and sent to
            // vendors. Drafts remain available if the caller asks for one.
            $data['status'] = $data['status'] ?? 'open';
            $data['created_by'] = $request->user()->id;

            $rfq = Rfq::create($data);

            if (!empty($vendorIds)) {
                $rfq->vendors()->sync($vendorIds);
            }

            foreach ($items as $item) {
                $item['rfq_id'] = $rfq->id;
                if (isset($item['unit_cost']) && isset($item['quantity'])) {
                    $item['total'] = $item['quantity'] * $item['unit_cost'];
                }
                if (isset($item['vendor_unit_price']) && isset($item['quantity'])) {
                    $item['vendor_total'] = $item['quantity'] * $item['vendor_unit_price'];
                }
                RfqItem::create($item);
            }

            $rfq->refresh();
            $rfq->load(['vendor', 'vendors', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfq_created',
                'rfq',
                $rfq->id,
                null,
                $rfq->toApiArray()
            );

            return $this->success([
                'rfq' => $rfq->toDetailArray(),
            ], 'RFQ created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/rfq/{id}
     */
    public function show(string $id): JsonResponse
    {
        $rfq = Rfq::with(['vendor', 'vendors', 'items'])->findOrFail($id);

        return $this->success([
            'rfq' => array_merge(
                $rfq->toDetailArray(),
                ['chain' => $this->chainResolver->forRfq($rfq)]
            ),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/rfq/{id}
     */
    public function update(UpdateRfqRequest $request, string $id): JsonResponse
    {
        $rfq = Rfq::with(['vendor', 'vendors', 'items'])->findOrFail($id);

        if (!in_array($rfq->status, ['draft', 'open', 'closed'])) {
            return $this->error('Only draft, open or closed RFQs can be edited. Awarded or cancelled RFQs are locked.', 422);
        }

        $oldValues = $rfq->toApiArray();

        return DB::transaction(function () use ($request, $rfq, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            // Same scope/vendor logic as create — see store() for context.
            $vendorIdsProvided = array_key_exists('vendor_ids', $data);
            $vendorIds = $vendorIdsProvided
                ? array_values(array_unique($data['vendor_ids'] ?? []))
                : null;
            unset($data['vendor_ids']);

            $scope = $data['scope'] ?? $rfq->scope ?? 'targeted';
            $data['scope'] = $scope;

            if ($scope === 'targeted') {
                if ($vendorIdsProvided && empty($vendorIds) && empty($data['vendor_id'] ?? $rfq->vendor_id)) {
                    return $this->error('Select at least one vendor for a targeted RFQ.', 422);
                }
                if ($vendorIdsProvided && empty($data['vendor_id']) && !empty($vendorIds)) {
                    $data['vendor_id'] = $vendorIds[0];
                }
                $data['advertised_on'] = $data['advertised_on'] ?? null;
            } else { // open
                if (!empty($vendorIds)) {
                    return $this->error('Open-call RFQs do not pin specific vendors. Switch to targeted to pick vendors.', 422);
                }
                $data['vendor_id'] = null;
                $vendorIds = [];
                $vendorIdsProvided = true; // we want to clear the pivot
            }

            $rfq->update($data);

            if ($vendorIdsProvided) {
                $rfq->vendors()->sync($vendorIds ?? []);
            }

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    if (isset($item['unit_cost']) && isset($item['quantity'])) {
                        $item['total'] = $item['quantity'] * $item['unit_cost'];
                    }
                    if (isset($item['vendor_unit_price']) && isset($item['quantity'])) {
                        $item['vendor_total'] = $item['quantity'] * $item['vendor_unit_price'];
                    }

                    if (!empty($item['id'])) {
                        $rfqItem = RfqItem::where('id', $item['id'])
                            ->where('rfq_id', $rfq->id)
                            ->first();
                        if ($rfqItem) {
                            $rfqItem->update($item);
                            $existingIds[] = $rfqItem->id;
                        }
                    } else {
                        $item['rfq_id'] = $rfq->id;
                        $newItem = RfqItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                RfqItem::where('rfq_id', $rfq->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            $rfq->refresh();
            $rfq->load(['vendor', 'vendors', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfq_updated',
                'rfq',
                $rfq->id,
                $oldValues,
                $rfq->toApiArray()
            );

            return $this->success([
                'rfq' => $rfq->toDetailArray(),
            ], 'RFQ updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/rfq/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $rfq = Rfq::findOrFail($id);

        if ($rfq->status === 'awarded') {
            return $this->error('Cannot delete an awarded RFQ.', 422);
        }

        return DB::transaction(function () use ($rfq, $id) {
            $rfqData = $rfq->toApiArray();
            $rfq->update(['status' => 'cancelled']);

            AuditLog::record(
                auth()->id(),
                'rfq_deleted',
                'rfq',
                $id,
                $rfqData,
                ['status' => 'cancelled']
            );

            return $this->success(null, 'RFQ cancelled successfully');
        });
    }

    /**
     * POST /api/v1/procurement/rfq/{id}/submit
     *
     * Transitions a draft RFQ to "open" — the document has been finalised
     * and is ready to be downloaded and shared with the vendor (off-app,
     * via email/print). Restricted to procurement managers and admins.
     */
    public function submit(string $id): JsonResponse
    {
        $rfq = Rfq::with('items')->findOrFail($id);

        if (!$this->authorizer->canManageRfqs(auth()->user())) {
            return $this->error(
                'Only a manager in the Procurement department (or an administrator) can send a Request for Quotation to vendors.',
                403
            );
        }

        if ($rfq->status !== 'draft') {
            return $this->error(
                "Only draft RFQs can be opened (current status: {$rfq->status}).",
                422
            );
        }

        if ($rfq->items()->count() === 0) {
            return $this->error('Cannot open an RFQ with no items.', 422);
        }

        return DB::transaction(function () use ($rfq) {
            $oldStatus = $rfq->status;
            $rfq->update(['status' => 'open']);
            $rfq->refresh();
            $rfq->load(['vendor', 'vendors', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfq_opened',
                'rfq',
                $rfq->id,
                ['status' => $oldStatus],
                ['status' => 'open']
            );

            return $this->success([
                'rfq' => $rfq->toDetailArray(),
            ], 'RFQ marked as open and ready to share with vendor');
        });
    }
}
