<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcessApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\RequisitionAuditLog;
use App\Models\RolePermission;
use App\Services\Procurement\ApprovalAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalAuthorizer $authorizer)
    {
    }

    /* ----------------------------------------------------------------
     * Purchase Order approval  (unchanged — still uses ApprovalStep)
     * ---------------------------------------------------------------- */

    /**
     * PATCH /api/v1/procurement/purchase-orders/{id}/approval
     */
    public function processPurchaseOrder(ProcessApprovalRequest $request, string $id): JsonResponse
    {
        $order = PurchaseOrder::with('approvalSteps')->findOrFail($id);

        if (!in_array($order->status, ['submitted', 'pending_approval'])) {
            return $this->error('This purchase order is not awaiting approval.', 422);
        }

        return $this->processLegacyApproval($request, $order, 'purchase_order');
    }

    /* ----------------------------------------------------------------
     * Requisition approval  (budget_holder -> finance -> procurement)
     * ---------------------------------------------------------------- */

    /**
     * PATCH /api/v1/procurement/requisitions/{id}/approval
     */
    public function processRequisition(ProcessApprovalRequest $request, string $id): JsonResponse
    {
        $requisition = Requisition::with([
            'approvals', 'department', 'requestedBy', 'submittedBy', 'items',
            'project.projectManager',
        ])->findOrFail($id);

        if (!in_array($requisition->status, ['pending_approval', 'submitted'])) {
            return $this->error('This requisition is not awaiting approval.', 422);
        }

        $user   = $request->user();
        $data   = $request->validated();
        $action = $data['action'];

        // Find the currently active (pending) stage
        $activeApproval = $requisition->approvals->where('status', 'pending')->sortBy('stage_order')->first();

        if (!$activeApproval) {
            return $this->error('This approval stage is not currently awaiting action.', 422);
        }

        // Enforce stage ownership via the authorizer (project manager / dept+role rules)
        $auth = $this->authorizer->canActOnStage($user, $requisition, $activeApproval->stage);
        if (!$auth['allowed']) {
            return $this->error($auth['reason'] ?? 'You are not authorised to approve at the current stage.', 403);
        }

        // 'forward' is only meaningful at the finance stage (approve + advance to procurement)
        if ($action === 'forward' && $activeApproval->stage !== 'finance') {
            return $this->error('Forward to Procurement is only available at the Finance stage.', 422);
        }

        return DB::transaction(function () use ($data, $requisition, $activeApproval, $user) {
            $action     = $data['action'];
            $comments   = $data['comments'] ?? null;
            $now        = now();
            $stageLabel = $activeApproval->stage_label;
            $fromStatus = $requisition->status;

            // Snapshot actor details at decision time (so later renames don't alter history)
            $actorName     = $user->name;
            $actorPosition = $user->department ?? null;

            $actorData = [
                'actor_id'       => $user->id,
                'actor_name'     => $actorName,
                'actor_position' => $actorPosition,
                'comments'       => $comments,
                'decided_at'     => $now,
            ];

            switch ($action) {
                case 'approve':
                    if ($activeApproval->stage === 'budget_holder') {
                        $activeApproval->update(array_merge($actorData, ['status' => 'approved']));
                        $requisition->approvals()->where('stage', 'finance')
                            ->update(['status' => 'pending', 'updated_at' => $now]);

                    } elseif ($activeApproval->stage === 'finance') {
                        // Finance approves -> advances to Procurement (final stage)
                        $activeApproval->update(array_merge($actorData, ['status' => 'approved']));
                        $requisition->approvals()->where('stage', 'procurement')
                            ->update(['status' => 'pending', 'updated_at' => $now]);

                    } elseif ($activeApproval->stage === 'procurement') {
                        // Procurement is the final stage -> PR fully approved
                        $activeApproval->update(array_merge($actorData, ['status' => 'approved']));
                        $requisition->update(['status' => 'approved']);
                    }
                    break;

                case 'forward':
                    // Finance forwards -> marked as APPROVED by Finance and chain advances
                    $activeApproval->update(array_merge($actorData, ['status' => 'approved']));
                    $requisition->approvals()->where('stage', 'procurement')
                        ->update(['status' => 'pending', 'updated_at' => $now]);
                    break;

                case 'reject':
                    $activeApproval->update(array_merge($actorData, ['status' => 'rejected']));
                    $requisition->update(['status' => 'rejected']);
                    break;

                case 'revision':
                    $activeApproval->update(array_merge($actorData, ['status' => 'revision']));
                    $requisition->update(['status' => 'revision']);
                    break;
            }

            $requisition->refresh();
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items', 'approvals', 'project']);

            AuditLog::record(
                $user->id,
                'requisition_' . $action,
                'requisition',
                $requisition->id,
                ['status' => $fromStatus],
                ['status' => $requisition->status, 'stage' => $activeApproval->stage, 'action' => $action]
            );

            RequisitionAuditLog::write(
                $requisition->id,
                $user->id,
                $user->name,
                $action,
                $fromStatus,
                $requisition->status,
                $activeApproval->stage,
                $comments
            );

            $messages = [
                'approve'  => "Purchase request approved by {$stageLabel}",
                'forward'  => "Purchase request approved by Finance and forwarded to Procurement",
                'reject'   => "Purchase request rejected by {$stageLabel}",
                'revision' => "Purchase request sent for revision by {$stageLabel}",
            ];

            return $this->success([
                'requisition' => $requisition->toDetailArray(),
            ], $messages[$action]);
        });
    }

    /* ----------------------------------------------------------------
     * Pending approvals dashboard
     * ---------------------------------------------------------------- */

    /**
     * GET /api/v1/procurement/pending-approvals
     *
     * ?scope=mine    (default) — items the authenticated user can actually act on
     * ?scope=all               — all in-flight PRs at any stage
     * ?scope=history           — completed PRs (approved / rejected / revision / fulfilled / cancelled)
     *                            supports: ?search=, ?page=, ?per_page=
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $user  = $request->user();
        $scope = $request->input('scope', 'mine');

        /* ---- History scope — paginated completed PRs ---- */
        if ($scope === 'history') {
            $result = $this->getHistoryRequisitions($request);
            return $this->success([
                'purchase_orders' => [],
                'requisitions'    => $result['items'],
                'meta'            => $result['meta'],
            ]);
        }

        /* ---- Purchase Orders (legacy ApprovalStep system, unchanged) ---- */
        $allowedStepTypes = $this->getUserApprovalStepTypes($user);

        $purchaseOrders = collect();
        if (!empty($allowedStepTypes)) {
            $poIds = ApprovalStep::where('status', 'pending')
                ->whereIn('step_type', $allowedStepTypes)
                ->where('approvable_type', 'purchase_order')
                ->pluck('approvable_id')
                ->unique();

            if ($poIds->isNotEmpty()) {
                $purchaseOrders = PurchaseOrder::with(['vendor', 'department', 'requestedBy', 'submittedBy'])
                    ->whereIn('id', $poIds)
                    ->whereIn('status', ['submitted', 'pending_approval'])
                    ->get()
                    ->map->toApiArray();
            }
        }

        /* ---- Requisitions (authorizer-driven filtering) ---- */
        $requisitions = $this->getPendingRequisitions($user, $scope);

        return $this->success([
            'purchase_orders' => $purchaseOrders,
            'requisitions'    => $requisitions,
        ]);
    }

    /* ----------------------------------------------------------------
     * Private helpers
     * ---------------------------------------------------------------- */

    /**
     * Paginated, searchable history of completed requisitions.
     */
    private function getHistoryRequisitions(Request $request): array
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $search  = $request->input('search', '');

        $query = Requisition::with(['department', 'requestedBy', 'approvals', 'project'])
            ->whereIn('status', ['approved', 'rejected', 'revision', 'fulfilled', 'cancelled']);

        if ($search !== '') {
            $term = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('requisition_number', 'like', "%{$term}%")
                  ->orWhere('project_code', 'like', "%{$term}%");
            });
        }

        $paginated = $query->orderByDesc('updated_at')->paginate($perPage);

        $items = collect($paginated->items())->map(function ($req) {
            return [
                'id'                  => $req->id,
                'requisition_number'  => $req->requisition_number,
                'title'               => $req->title,
                'requested_by_name'   => $req->requestedBy?->name,
                'department'          => $req->department?->name,
                'project_id'          => $req->project_id,
                'project_code'        => $req->project_code,
                'project_name'        => $req->project?->name,
                'donor'               => $req->donor,
                'estimated_cost'      => (float) $req->estimated_cost,
                'priority'            => $req->priority,
                'status'              => $req->status,
                'needed_by'           => $req->needed_by?->toDateString(),
                'updated_at'          => $req->updated_at?->toISOString(),
                'active_stage'        => $req->active_stage,
                'approval_progress'   => $req->approval_progress,
                'approval_chain'      => $req->approvals->map->toApiArray(true)->toArray(),
            ];
        });

        return [
            'items' => $items,
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ];
    }

    /**
     * Pending requisitions for the dashboard, filtered via the authorizer.
     * 'all' scope returns every in-flight PR regardless of who can act on it.
     * 'mine' scope (default) returns only PRs the user can act on RIGHT NOW
     * at the currently pending stage.
     */
    private function getPendingRequisitions($user, string $scope): \Illuminate\Support\Collection
    {
        $query = Requisition::with([
            'department', 'requestedBy', 'approvals', 'project.projectManager',
        ])->whereIn('status', ['pending_approval', 'submitted']);

        if ($scope === 'all') {
            return $query->orderByDesc('updated_at')->get()->map(fn ($req) => $this->toPendingArray($req));
        }

        // 'mine' — narrow by stages the user is potentially eligible for, then
        // for each PR verify they can actually act on its current pending stage.
        $eligibleStages = $this->authorizer->eligibleStagesFor($user);

        if (empty($eligibleStages)) {
            return collect();
        }

        $query->whereHas('approvals', function ($q) use ($eligibleStages) {
            $q->where('status', 'pending')->whereIn('stage', $eligibleStages);
        });

        return $query->orderByDesc('updated_at')->get()
            ->filter(function ($req) use ($user) {
                $active = $req->approvals->firstWhere('status', 'pending');
                if (!$active) {
                    return false;
                }
                return $this->authorizer->canActOnStage($user, $req, $active->stage)['allowed'];
            })
            ->map(fn ($req) => $this->toPendingArray($req))
            ->values();
    }

    private function toPendingArray(Requisition $req): array
    {
        return [
            'id'                  => $req->id,
            'requisition_number'  => $req->requisition_number,
            'title'               => $req->title,
            'requested_by_name'   => $req->requestedBy?->name,
            'department'          => $req->department?->name,
            'project_id'          => $req->project_id,
            'project_code'        => $req->project_code,
            'project_name'        => $req->project?->name,
            'donor'               => $req->donor,
            'estimated_cost'      => (float) $req->estimated_cost,
            'priority'            => $req->priority,
            'status'              => $req->status,
            'needed_by'           => $req->needed_by?->toDateString(),
            'submitted_at'        => $req->updated_at?->toISOString(),
            'active_stage'        => $req->active_stage,
            'approval_progress'   => $req->approval_progress,
            'approval_chain'      => $req->approvals->map->toApiArray(false)->toArray(),
        ];
    }

    /**
     * Step types for legacy PO approvals (old permission keys, untouched).
     */
    private function getUserApprovalStepTypes($user): array
    {
        if ($user->role === 'super_admin') {
            return ['manager_review', 'finance_check', 'operations_approval', 'executive_approval'];
        }

        $mapping = [
            'procurement.approval.manager_review'      => 'manager_review',
            'procurement.approval.finance_check'       => 'finance_check',
            'procurement.approval.operations_approval' => 'operations_approval',
            'procurement.approval.executive_approval'  => 'executive_approval',
        ];

        $allowedKeys = RolePermission::whereHas('permission', function ($q) {
            $q->where('key', 'like', 'procurement.approval.%');
        })->where('role', $user->role)
          ->where('allowed', true)
          ->with('permission')
          ->get()
          ->pluck('permission.key')
          ->toArray();

        $stepTypes = [];
        foreach ($mapping as $permKey => $stepType) {
            if (in_array($permKey, $allowedKeys)) {
                $stepTypes[] = $stepType;
            }
        }

        return $stepTypes;
    }

    /**
     * Legacy approval logic for Purchase Orders (polymorphic ApprovalStep).
     * Kept intact — only approve/reject supported for POs.
     */
    private function processLegacyApproval(ProcessApprovalRequest $request, $approvable, string $type): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Reject 'forward' and 'revision' for POs (not supported)
        if (in_array($data['action'], ['forward', 'revision'])) {
            return $this->error("The '{$data['action']}' action is not supported for purchase orders.", 422);
        }

        $currentStep = $approvable->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if (!$currentStep) {
            return $this->error('No pending approval step found.', 422);
        }

        if ($user->role !== 'super_admin') {
            $permissionKey = 'procurement.approval.' . $currentStep->step_type;
            $hasPermission = RolePermission::whereHas('permission', function ($q) use ($permissionKey) {
                $q->where('key', $permissionKey);
            })->where('role', $user->role)->where('allowed', true)->exists();

            if (!$hasPermission) {
                return $this->error('You do not have permission to act on this approval step.', 403);
            }
        }

        return DB::transaction(function () use ($data, $approvable, $currentStep, $user, $type) {
            $oldValues = $approvable->toApiArray();

            if ($data['action'] === 'approve') {
                $currentStep->update([
                    'status'   => 'approved',
                    'acted_by' => $user->id,
                    'acted_at' => now(),
                    'comments' => $data['comments'] ?? null,
                ]);

                $pendingCount = $approvable->approvalSteps()->where('status', 'pending')->count();
                $approvable->update(['status' => $pendingCount === 0 ? 'approved' : 'pending_approval']);
                $auditAction = $pendingCount === 0 ? "{$type}_approved" : "{$type}_step_approved";

            } else {
                $currentStep->update([
                    'status'   => 'rejected',
                    'acted_by' => $user->id,
                    'acted_at' => now(),
                    'comments' => $data['comments'],
                ]);

                $approvable->approvalSteps()
                    ->where('status', 'pending')
                    ->where('step_order', '>', $currentStep->step_order)
                    ->update(['status' => 'skipped']);

                $approvable->update(['status' => 'revision']);
                $auditAction = "{$type}_rejected";
            }

            AuditLog::record(
                $user->id,
                $auditAction,
                $type,
                $approvable->id,
                $oldValues,
                $approvable->fresh()->toApiArray()
            );

            $approvable->refresh();
            $approvable->load('approvalSteps');

            $message = $data['action'] === 'approve'
                ? ucfirst(str_replace('_', ' ', $type)) . ' approval step processed'
                : ucfirst(str_replace('_', ' ', $type)) . ' rejected and sent for revision';

            return $this->success([
                $type => $approvable->toDetailArray(),
            ], $message);
        });
    }
}
