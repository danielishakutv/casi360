<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreRequisitionRequest;
use App\Http\Requests\Procurement\UpdateRequisitionRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionAuditLog;
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
        $query = Requisition::with(['department', 'requestedBy', 'submittedBy', 'purchaseOrder', 'project', 'approvals']);

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

        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['requisition_number', 'title', 'priority', 'estimated_cost', 'status', 'needed_by', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage === 0) {
            $requisitions = $query->get();
            return $this->success([
                'requisitions' => $requisitions->map->toApiArray(),
                'meta'         => ['total' => $requisitions->count()],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'requisitions' => collect($paginated->items())->map->toApiArray(),
            'meta'         => [
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
     * POST /api/v1/procurement/requisitions
     */
    public function store(StoreRequisitionRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data  = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Always set requested_by from the authenticated session — never trust client value
            $data['requested_by']       = auth()->id();
            $data['requisition_number'] = Requisition::generateRequisitionNumber();
            $data['status']            = 'draft';

            // If a project is linked, sync project_code from the canonical source
            if (!empty($data['project_id'])) {
                $project = Project::find($data['project_id']);
                if ($project) {
                    $data['project_code'] = $project->project_code;
                }
            }

            $requisition = Requisition::create($data);

            foreach ($items as $item) {
                if (empty(trim($item['description'] ?? ''))) {
                    continue;
                }
                $item['requisition_id']      = $requisition->id;
                $item['estimated_total_cost'] = ($item['quantity'] ?? 1) * ($item['estimated_unit_cost'] ?? 0);
                RequisitionItem::create($item);
            }

            $requisition->recalculateEstimatedCost();
            $requisition->refresh();
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items', 'approvals', 'project']);

            AuditLog::record(
                auth()->id(),
                'requisition_created',
                'requisition',
                $requisition->id,
                null,
                $requisition->toApiArray()
            );

            RequisitionAuditLog::write(
                $requisition->id,
                auth()->id(),
                auth()->user()->name,
                'created',
                null,
                'draft'
            );

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], 'Purchase request created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/requisitions/{id}
     */
    public function show(string $id): JsonResponse
    {
        $requisition = Requisition::with([
            'department', 'requestedBy', 'submittedBy', 'purchaseOrder', 'project.projectManager',
            'items.inventoryItem', 'approvals',
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
        $requisition = Requisition::with(['department', 'requestedBy', 'submittedBy', 'items', 'approvals', 'project'])
            ->findOrFail($id);

        if (!in_array($requisition->status, ['draft', 'revision', 'rejected'])) {
            return $this->error('Only draft, revision, or rejected purchase requests can be edited.', 422);
        }

        $oldValues = $requisition->toApiArray();

        return DB::transaction(function () use ($request, $requisition, $oldValues) {
            $data  = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            // Keep project_code in sync with the selected project
            if (array_key_exists('project_id', $data)) {
                if (!empty($data['project_id'])) {
                    $project = Project::find($data['project_id']);
                    if ($project) {
                        $data['project_code'] = $project->project_code;
                    }
                } else {
                    $data['project_code'] = null;
                }
            }

            $requisition->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    if (empty(trim($item['description'] ?? ''))) {
                        continue;
                    }
                    $item['estimated_total_cost'] = ($item['quantity'] ?? 1) * ($item['estimated_unit_cost'] ?? 0);

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
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items', 'approvals', 'project']);

            AuditLog::record(
                auth()->id(),
                'requisition_updated',
                'requisition',
                $requisition->id,
                $oldValues,
                $requisition->toApiArray()
            );

            RequisitionAuditLog::write(
                $requisition->id,
                auth()->id(),
                auth()->user()->name,
                'updated',
                $oldValues['status'] ?? null,
                $requisition->status
            );

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], 'Purchase request updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/requisitions/{id}
     *
     * Soft-cancel — sets status to 'cancelled'. No hard delete.
     */
    public function destroy(string $id): JsonResponse
    {
        $requisition = Requisition::findOrFail($id);

        if (in_array($requisition->status, ['approved', 'fulfilled'])) {
            return $this->error(
                'Cannot cancel an approved or fulfilled purchase request.',
                422
            );
        }

        return DB::transaction(function () use ($requisition, $id) {
            $requisitionData = $requisition->toApiArray();
            $requisition->update(['status' => 'cancelled']);

            AuditLog::record(
                auth()->id(),
                'requisition_cancelled',
                'requisition',
                $id,
                $requisitionData,
                ['status' => 'cancelled']
            );

            return $this->success(null, 'Purchase request cancelled successfully');
        });
    }

    /**
     * POST /api/v1/procurement/requisitions/{id}/submit
     *
     * Submits a draft or revision PR for approval.
     * Creates (or resets) the 3-stage approval chain immediately.
     */
    public function submit(string $id): JsonResponse
    {
        $requisition = Requisition::with(['items', 'approvals'])->findOrFail($id);

        if (!in_array($requisition->status, ['draft', 'revision', 'rejected'])) {
            return $this->error('Only draft, revision, or rejected purchase requests can be submitted for approval.', 422);
        }

        if ($requisition->items()->count() === 0) {
            return $this->error('Cannot submit a purchase request with no items.', 422);
        }

        return DB::transaction(function () use ($requisition) {
            $fromStatus = $requisition->status;

            $requisition->update([
                'status'       => 'pending_approval',
                'submitted_by' => auth()->id(),
            ]);

            // Reset (or create) the fixed 3-stage approval chain — always starts at budget_holder
            $requisition->createApprovalChain();

            $requisition->refresh();
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items', 'approvals', 'project']);

            AuditLog::record(
                auth()->id(),
                'requisition_submitted',
                'requisition',
                $requisition->id,
                ['status' => $fromStatus],
                $requisition->toApiArray()
            );

            RequisitionAuditLog::write(
                $requisition->id,
                auth()->id(),
                auth()->user()->name,
                'submitted',
                $fromStatus,
                'pending_approval'
            );

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], 'Purchase request submitted for approval');
        });
    }

    /**
     * GET /api/v1/procurement/requisitions/{id}/approval-status
     */
    public function approvalStatus(string $id): JsonResponse
    {
        $requisition = Requisition::with('approvals')->findOrFail($id);

        return $this->success([
            'requisition_id'     => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'current_status'     => $requisition->status,
            'active_stage'       => $requisition->active_stage,
            'approval_chain'     => $requisition->approvals->map->toApiArray(true)->toArray(),
        ]);
    }

    /**
     * GET /api/v1/procurement/requisitions/{id}/audit-log
     */
    public function auditLog(string $id): JsonResponse
    {
        $requisition = Requisition::findOrFail($id);

        $log = RequisitionAuditLog::where('requisition_id', $requisition->id)
            ->orderByDesc('created_at')
            ->get()
            ->map->toApiArray();

        return $this->success([
            'audit_log' => $log,
        ]);
    }
}
