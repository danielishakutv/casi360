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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
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
     * Requisition approval  (new 3-stage chain)
     * ---------------------------------------------------------------- */

    /**
     * PATCH /api/v1/procurement/requisitions/{id}/approval
     */
    public function processRequisition(ProcessApprovalRequest $request, string $id): JsonResponse
    {
        $requisition = Requisition::with(['approvals', 'department', 'requestedBy', 'submittedBy', 'items'])
            ->findOrFail($id);

        if (!in_array($requisition->status, ['pending_approval', 'submitted'])) {
            return $this->error('This requisition is not awaiting approval.', 422);
        }

        $user = $request->user();
        $data = $request->validated();
        $action = $data['action'];

        // Find the currently active (pending) stage
        $activeApproval = $requisition->approvals->where('status', 'pending')->sortBy('stage_order')->first();

        if (!$activeApproval) {
            return $this->error('This approval stage is not currently awaiting action.', 422);
        }

        // Enforce stage ownership via permission
        if ($user->role !== 'super_admin') {
            $stagePermKey = 'procurement.approvals.' . $activeApproval->stage;
            $hasPermission = RolePermission::whereHas('permission', function ($q) use ($stagePermKey) {
                $q->where('key', $stagePermKey);
            })->where('role', $user->role)->where('allowed', true)->exists();

            if (!$hasPermission) {
                return $this->error('You are not authorised to approve at the current stage.', 403);
            }
        }

        // 'forward' is only valid at the finance stage
        if ($action === 'forward' && $activeApproval->stage !== 'finance') {
            return $this->error('Forward to Operations is only available at the Finance stage.', 422);
        }

        return DB::transaction(function () use ($data, $requisition, $activeApproval, $user) {
            $action      = $data['action'];
            $comments    = $data['comments'] ?? null;
            $now         = now();
            $stageLabel  = $activeApproval->stage_label;
            $fromStatus  = $requisition->status;

            // Snapshot actor details at decision time
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
                        // Finance approves final — skip operations
                        $activeApproval->update(array_merge($actorData, ['status' => 'approved']));
                        $requisition->approvals()->where('stage', 'operations')
                            ->update(['status' => 'skipped', 'updated_at' => $now]);
                        $requisition->update(['status' => 'approved']);

                    } elseif ($activeApproval->stage === 'operations') {
                        $activeApproval->update(array_merge($actorData, ['status' => 'approved']));
                        $requisition->update(['status' => 'approved']);
                    }
                    break;

                case 'forward':
                    // Finance forwards to Operations (does not close the chain)
                    $activeApproval->update(array_merge($actorData, ['status' => 'forwarded']));
                    $requisition->approvals()->where('stage', 'operations')
                        ->update(['status' => 'pending', 'updated_at' => $now]);
                    // Requisition status remains 'pending_approval'
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
            $requisition->load(['department', 'requestedBy', 'submittedBy', 'items', 'approvals']);

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
                'forward'  => "Purchase request forwarded to Operations by Finance",
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
     * ?scope=mine  (default) — items awaiting the authenticated user's action
     * ?scope=all             — all in-flight PRs at any stage
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $user  = $request->user();
        $scope = $request->input('scope', 'mine');

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

        /* ---- Requisitions (new requisition_approvals system) ---- */
        $requisitions = $this->getPendingRequisitions($user, $scope);

        return $this->success([
            'purchase_orders' => $purchaseOrders,
            'requisitions'    => $requisitions,
        ]);
    }

    /* ----------------------------------------------------------------
     * Private helpers
     * ---------------------------------------------------------------- */

    private function getPendingRequisitions($user, string $scope): \Illuminate\Support\Collection
    {
        $query = Requisition::with(['department', 'requestedBy', 'approvals'])
            ->whereIn('status', ['pending_approval', 'submitted']);

        if ($scope !== 'all') {
            // Only show PRs where a stage matching the user's permission is pending
            $userStages = $this->getUserApprovalStages($user);

            if (empty($userStages)) {
                return collect();
            }

            $query->whereHas('approvals', function ($q) use ($userStages) {
                $q->where('status', 'pending')->whereIn('stage', $userStages);
            });
        }

        return $query->orderByDesc('updated_at')->get()->map(function ($req) {
            return [
                'id'                  => $req->id,
                'requisition_number'  => $req->requisition_number,
                'title'               => $req->title,
                'requested_by_name'   => $req->requestedBy?->name,
                'department'          => $req->department?->name,
                'project_code'        => $req->project_code,
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
        });
    }

    /**
     * Stages the authenticated user is allowed to act on (new permission keys).
     */
    private function getUserApprovalStages($user): array
    {
        if ($user->role === 'super_admin') {
            return ['budget_holder', 'finance', 'operations'];
        }

        $stageMap = [
            'procurement.approvals.budget_holder' => 'budget_holder',
            'procurement.approvals.finance'       => 'finance',
            'procurement.approvals.operations'    => 'operations',
        ];

        $allowed = RolePermission::whereHas('permission', function ($q) {
            $q->where('key', 'like', 'procurement.approvals.%')
              ->whereNotIn('key', ['procurement.approvals.view']);
        })->where('role', $user->role)
          ->where('allowed', true)
          ->with('permission')
          ->get()
          ->pluck('permission.key')
          ->toArray();

        return array_values(array_filter(
            array_map(fn($key) => $stageMap[$key] ?? null, $allowed)
        ));
    }

    /**
     * Step types for legacy PO approvals (old permission keys).
     */
    private function getUserApprovalStepTypes($user): array
    {
        if ($user->role === 'super_admin') {
            return ['manager_review', 'finance_check', 'operations_approval', 'executive_approval'];
        }

        $mapping = [
            'procurement.approval.manager_review'   => 'manager_review',
            'procurement.approval.finance_check'    => 'finance_check',
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
