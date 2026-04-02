<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreRequisitionRequest;
use App\Http\Requests\Procurement\UpdateRequisitionRequest;
use App\Models\AuditLog;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    /**
     * GET /api/v1/procurement/requisitions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Requisition::with(['department', 'requestedBy', 'submittedBy', 'purchaseOrder']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('requisition_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('justification', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['requisition_number', 'title', 'priority', 'estimated_cost', 'status', 'needed_by', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $requisitions = $query->get();
            return $this->success([
                'requisitions' => $requisitions->map->toApiArray(),
                'meta' => [
                    'total' => $requisitions->count(),
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'requisitions' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/requisitions
     */
    public function store(StoreRequisitionRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['requisition_number'] = Requisition::generateRequisitionNumber();
            $data['status'] = 'draft';

            $requisition = Requisition::create($data);

            foreach ($items as $item) {
                $item['requisition_id'] = $requisition->id;
                $item['estimated_total_cost'] = $item['quantity'] * $item['estimated_unit_cost'];
                RequisitionItem::create($item);
            }

            $requisition->recalculateEstimatedCost();
            $requisition->refresh();
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items']);

            AuditLog::record(
                auth()->id(),
                'requisition_created',
                'requisition',
                $requisition->id,
                null,
                $requisition->toApiArray()
            );

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], 'Requisition created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/requisitions/{id}
     */
    public function show(string $id): JsonResponse
    {
        $requisition = Requisition::with([
            'department', 'requestedBy', 'submittedBy', 'purchaseOrder',
            'items.inventoryItem', 'approvalSteps',
        ])->findOrFail($id);

        return $this->success([
            'requisition' => $requisition->toDetailArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/requisitions/{id}
     */
    public function update(UpdateRequisitionRequest $request, string $id): JsonResponse
    {
        $requisition = Requisition::with(['department', 'requestedBy', 'submittedBy', 'items'])
            ->findOrFail($id);

        if (!in_array($requisition->status, ['draft', 'revision'])) {
            return $this->error('Only draft or revision requisitions can be edited.', 422);
        }

        $oldValues = $requisition->toApiArray();

        return DB::transaction(function () use ($request, $requisition, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $requisition->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['estimated_total_cost'] = $item['quantity'] * $item['estimated_unit_cost'];

                    if (!empty($item['id'])) {
                        $reqItem = RequisitionItem::where('id', $item['id'])
                            ->where('requisition_id', $requisition->id)
                            ->first();
                        if ($reqItem) {
                            $reqItem->update($item);
                            $existingIds[] = $reqItem->id;
                        }
                    } else {
                        $item['requisition_id'] = $requisition->id;
                        $newItem = RequisitionItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                RequisitionItem::where('requisition_id', $requisition->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                $requisition->recalculateEstimatedCost();
            }

            $requisition->refresh();
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items']);

            AuditLog::record(
                auth()->id(),
                'requisition_updated',
                'requisition',
                $requisition->id,
                $oldValues,
                $requisition->toApiArray()
            );

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], 'Requisition updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/requisitions/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $requisition = Requisition::findOrFail($id);

        if (in_array($requisition->status, ['approved', 'fulfilled'])) {
            return $this->error(
                'Cannot delete an approved or fulfilled requisition. Cancel it instead.',
                422
            );
        }

        return DB::transaction(function () use ($requisition, $id) {
            $requisitionData = $requisition->toApiArray();
            $requisition->update(['status' => 'cancelled']);

            AuditLog::record(
                auth()->id(),
                'requisition_deleted',
                'requisition',
                $id,
                $requisitionData,
                ['status' => 'cancelled']
            );

            return $this->success(null, 'Requisition cancelled successfully');
        });
    }

    /**
     * POST /api/v1/procurement/requisitions/{id}/submit
     *
     * Submit a draft/revision requisition for approval.
     * Auto-generates approval steps based on estimated cost thresholds.
     */
    public function submit(string $id): JsonResponse
    {
        $requisition = Requisition::with('items')->findOrFail($id);

        if (!in_array($requisition->status, ['draft', 'revision'])) {
            return $this->error('Only draft or revision requisitions can be submitted for approval.', 422);
        }

        if ($requisition->items()->count() === 0) {
            return $this->error('Cannot submit a requisition with no items.', 422);
        }

        return DB::transaction(function () use ($requisition) {
            $oldValues = $requisition->toApiArray();

            $requisition->update([
                'status' => 'submitted',
                'submitted_by' => auth()->id(),
            ]);

            $requisition->generateApprovalSteps();

            $requisition->refresh();
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items', 'approvalSteps']);

            AuditLog::record(
                auth()->id(),
                'requisition_submitted',
                'requisition',
                $requisition->id,
                $oldValues,
                $requisition->toApiArray()
            );

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], 'Requisition submitted for approval');
        });
    }

    /**
     * GET /api/v1/procurement/requisitions/{id}/approval-status
     */
    public function approvalStatus(string $id): JsonResponse
    {
        $requisition = Requisition::with('approvalSteps.actedBy')->findOrFail($id);

        return $this->success([
            'requisition_id' => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'status' => $requisition->status,
            'approval_steps' => $requisition->approvalSteps()
                ->orderBy('step_order')
                ->get()
                ->map->toApiArray(),
        ]);
    }
}
