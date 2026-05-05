<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcessGrnConfirmationRequest;
use App\Http\Requests\Procurement\StoreGrnRequest;
use App\Http\Requests\Procurement\UpdateGrnRequest;
use App\Models\AuditLog;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Services\Procurement\DocumentChainResolver;
use App\Services\Procurement\DocumentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
{
    public function __construct(
        private DocumentScopeService $scopeService,
        private DocumentChainResolver $chainResolver,
    ) {
    }

    /**
     * GET /api/v1/procurement/grn
     */
    public function index(Request $request): JsonResponse
    {
        $query = Grn::with('vendor');

        if ($this->scopeService->shouldScope($request->user(), 'procurement.grn.view_all', $request)) {
            $this->scopeService->applyToGrns($query, $request->user());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('po_reference')) {
            $query->where('po_reference', $request->po_reference);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                  ->orWhere('delivery_note_no', 'like', "%{$search}%")
                  ->orWhere('received_by', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['grn_number', 'status', 'received_date', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $grns = $query->get();
            return $this->success([
                'grns' => $grns->map->toApiArray(),
                'meta' => ['total' => $grns->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'grns' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/grn
     */
    public function store(StoreGrnRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['grn_number'] = Grn::generateGrnNumber();
            $data['status'] = $data['status'] ?? 'draft';
            $data['created_by'] = $request->user()->id;

            $grn = Grn::create($data);

            foreach ($items as $item) {
                $item['grn_id'] = $grn->id;
                $item['accepted_qty'] = $item['accepted_qty'] ?? $item['received_qty'];
                $item['rejected_qty'] = $item['rejected_qty'] ?? 0;
                GrnItem::create($item);
            }

            $grn->refresh();
            $grn->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'grn_created',
                'grn',
                $grn->id,
                null,
                $grn->toApiArray()
            );

            return $this->success([
                'grn' => $grn->toDetailArray(),
            ], 'GRN created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/grn/{id}
     */
    public function show(string $id): JsonResponse
    {
        $grn = Grn::with(['vendor', 'items'])->findOrFail($id);

        return $this->success([
            'grn' => array_merge(
                $grn->toDetailArray(),
                ['chain' => $this->chainResolver->forGrn($grn)]
            ),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/grn/{id}
     */
    public function update(UpdateGrnRequest $request, string $id): JsonResponse
    {
        $grn = Grn::with(['vendor', 'items'])->findOrFail($id);

        if ($grn->status !== 'draft') {
            return $this->error('Only draft GRNs can be edited. Submitted GRNs are locked until the budget holder accepts or rejects them.', 422);
        }

        $oldValues = $grn->toApiArray();

        return DB::transaction(function () use ($request, $grn, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $grn->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['accepted_qty'] = $item['accepted_qty'] ?? $item['received_qty'];
                    $item['rejected_qty'] = $item['rejected_qty'] ?? 0;

                    if (!empty($item['id'])) {
                        $grnItem = GrnItem::where('id', $item['id'])
                            ->where('grn_id', $grn->id)
                            ->first();
                        if ($grnItem) {
                            $grnItem->update($item);
                            $existingIds[] = $grnItem->id;
                        }
                    } else {
                        $item['grn_id'] = $grn->id;
                        $newItem = GrnItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                GrnItem::where('grn_id', $grn->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            $grn->refresh();
            $grn->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'grn_updated',
                'grn',
                $grn->id,
                $oldValues,
                $grn->toApiArray()
            );

            return $this->success([
                'grn' => $grn->toDetailArray(),
            ], 'GRN updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/grn/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $grn = Grn::findOrFail($id);

        if ($grn->status === 'accepted') {
            return $this->error('Cannot delete an accepted GRN.', 422);
        }

        return DB::transaction(function () use ($grn, $id) {
            $grnData = $grn->toApiArray();
            $grn->items()->delete();
            $grn->delete();

            AuditLog::record(
                auth()->id(),
                'grn_deleted',
                'grn',
                $id,
                $grnData,
                null
            );

            return $this->success(null, 'GRN deleted successfully');
        });
    }

    /**
     * POST /api/v1/procurement/grn/{id}/submit
     *
     * Receiver hands the GRN to the budget holder for confirmation. Only
     * draft GRNs may be submitted; the row is then locked from edits
     * until the confirmer accepts, partially-accepts, or rejects it.
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $grn = Grn::findOrFail($id);

        if ($grn->status !== 'draft') {
            return $this->error('Only draft GRNs can be submitted for confirmation.', 422);
        }

        $oldValues = $grn->toApiArray();

        return DB::transaction(function () use ($request, $grn, $oldValues) {
            $grn->update([
                'status'       => 'pending_inspection',
                'submitted_at' => now(),
            ]);

            AuditLog::record(
                $request->user()?->id,
                'grn_submitted',
                'grn',
                $grn->id,
                $oldValues,
                $grn->fresh()->toApiArray()
            );

            return $this->success([
                'grn' => $grn->fresh()->load(['vendor', 'items'])->toDetailArray(),
            ], 'GRN submitted for budget-holder confirmation');
        });
    }

    /**
     * PATCH /api/v1/procurement/grn/{id}/confirmation
     *
     * Budget-holder stage of dual-confirmation. Three terminal outcomes:
     *   - accept  → status `accepted`
     *   - partial → status `partial`  (notes required)
     *   - reject  → status `rejected` (notes required)
     *
     * Only GRNs in `pending_inspection` may be confirmed. Notes are
     * persisted in confirmation_notes; the actor + timestamp are
     * captured in confirmed_by / confirmed_at for audit and display.
     */
    public function processConfirmation(ProcessGrnConfirmationRequest $request, string $id): JsonResponse
    {
        $grn = Grn::findOrFail($id);

        if ($grn->status !== 'pending_inspection') {
            return $this->error('Only GRNs awaiting confirmation can be accepted or rejected.', 422);
        }

        $data = $request->validated();
        $oldValues = $grn->toApiArray();
        $user = $request->user();
        $now = now();

        return DB::transaction(function () use ($grn, $data, $oldValues, $user, $now) {
            $newStatus = match ($data['action']) {
                'accept'  => 'accepted',
                'partial' => 'partial',
                'reject'  => 'rejected',
            };

            $grn->update([
                'status'             => $newStatus,
                'confirmed_by'       => $user?->id,
                'confirmed_at'       => $now,
                'confirmation_notes' => $data['notes'] ?? null,
            ]);

            $action = match ($data['action']) {
                'accept'  => 'grn_accepted',
                'partial' => 'grn_partial',
                'reject'  => 'grn_rejected',
            };
            $message = match ($data['action']) {
                'accept'  => 'GRN accepted',
                'partial' => 'GRN partially accepted',
                'reject'  => 'GRN rejected',
            };

            AuditLog::record(
                $user?->id,
                $action,
                'grn',
                $grn->id,
                $oldValues,
                $grn->fresh()->toApiArray()
            );

            return $this->success([
                'grn' => $grn->fresh()->load(['vendor', 'items'])->toDetailArray(),
            ], $message);
        });
    }
}
