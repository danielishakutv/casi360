<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcessApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\RolePermission;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    /**
     * PATCH /api/v1/procurement/purchase-orders/{id}/approval
     *
     * Process the current approval step for a purchase order.
     */
    public function processPurchaseOrder(ProcessApprovalRequest $request, string $id): JsonResponse
    {
        $order = PurchaseOrder::with('approvalSteps')->findOrFail($id);

        if (!in_array($order->status, ['submitted', 'pending_approval'])) {
            return $this->error('This purchase order is not awaiting approval.', 422);
        }

        return $this->processApproval($request, $order, 'purchase_order');
    }

    /**
     * PATCH /api/v1/procurement/requisitions/{id}/approval
     *
     * Process the current approval step for a requisition.
     */
    public function processRequisition(ProcessApprovalRequest $request, string $id): JsonResponse
    {
        $requisition = Requisition::with('approvalSteps')->findOrFail($id);

        if (!in_array($requisition->status, ['submitted', 'pending_approval'])) {
            return $this->error('This requisition is not awaiting approval.', 422);
        }

        return $this->processApproval($request, $requisition, 'requisition');
    }

    /**
     * GET /api/v1/procurement/pending-approvals
     *
     * List all POs and requisitions that have a pending step matching
     * the current user's approval permissions.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedStepTypes = $this->getUserApprovalStepTypes($user);

        if (empty($allowedStepTypes)) {
            return $this->success([
                'purchase_orders' => [],
                'requisitions' => [],
            ]);
        }

        $pendingSteps = ApprovalStep::where('status', 'pending')
            ->whereIn('step_type', $allowedStepTypes)
            ->get()
            ->groupBy('approvable_type');

        $poIds = ($pendingSteps->get('purchase_order') ?? collect())->pluck('approvable_id')->unique();
        $reqIds = ($pendingSteps->get('requisition') ?? collect())->pluck('approvable_id')->unique();

        $purchaseOrders = $poIds->isNotEmpty()
            ? PurchaseOrder::with(['vendor', 'department', 'requestedBy', 'submittedBy'])
                ->whereIn('id', $poIds)
                ->whereIn('status', ['submitted', 'pending_approval'])
                ->get()
                ->map->toApiArray()
            : collect();

        $requisitions = $reqIds->isNotEmpty()
            ? Requisition::with(['department', 'requestedBy', 'submittedBy'])
                ->whereIn('id', $reqIds)
                ->whereIn('status', ['submitted', 'pending_approval'])
                ->get()
                ->map->toApiArray()
            : collect();

        return $this->success([
            'purchase_orders' => $purchaseOrders,
            'requisitions' => $requisitions,
        ]);
    }

    /* ----------------------------------------------------------------
     * Shared Approval Logic
     * ---------------------------------------------------------------- */

    private function processApproval(ProcessApprovalRequest $request, $approvable, string $type): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Find the current pending step (lowest step_order)
        $currentStep = $approvable->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if (!$currentStep) {
            return $this->error('No pending approval step found.', 422);
        }

        // Check user has the permission for this step type
        if ($user->role !== 'super_admin') {
            $permissionKey = 'procurement.approval.' . $currentStep->step_type;
            $hasPermission = RolePermission::whereHas('permission', function ($q) use ($permissionKey) {
                $q->where('key', $permissionKey);
            })->where('role', $user->role)->where('allowed', true)->exists();

            if (!$hasPermission) {
                return $this->error('You do not have permission to act on this approval step.', 403);
            }
        }

        // Self-approval check
        $blockSelfApproval = SystemSetting::getValue('procurement.approval.block_self_approval', true);
        if ($blockSelfApproval && $approvable->submitted_by === $user->id) {
            // Check if user has explicit self_approve permission
            if ($user->role !== 'super_admin') {
                $canSelfApprove = RolePermission::whereHas('permission', function ($q) {
                    $q->where('key', 'procurement.approval.self_approve');
                })->where('role', $user->role)->where('allowed', true)->exists();

                if (!$canSelfApprove) {
                    return $this->error('You cannot approve your own submission.', 403);
                }
            }
        }

        return DB::transaction(function () use ($data, $approvable, $currentStep, $user, $type) {
            $oldValues = $approvable->toApiArray();

            if ($data['action'] === 'approve') {
                $currentStep->update([
                    'status' => 'approved',
                    'acted_by' => $user->id,
                    'acted_at' => now(),
                    'comments' => $data['comments'] ?? null,
                ]);

                // Check if all steps are now approved
                $pendingCount = $approvable->approvalSteps()
                    ->where('status', 'pending')
                    ->count();

                if ($pendingCount === 0) {
                    $approvable->update(['status' => 'approved']);
                    $auditAction = "{$type}_approved";
                } else {
                    $approvable->update(['status' => 'pending_approval']);
                    $auditAction = "{$type}_step_approved";
                }
            } else {
                // Reject
                $currentStep->update([
                    'status' => 'rejected',
                    'acted_by' => $user->id,
                    'acted_at' => now(),
                    'comments' => $data['comments'],
                ]);

                // Skip remaining steps
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

    /**
     * Get the step types a user can act on based on their role permissions.
     */
    private function getUserApprovalStepTypes($user): array
    {
        if ($user->role === 'super_admin') {
            return ['manager_review', 'finance_check', 'operations_approval', 'executive_approval'];
        }

        $stepTypes = [];
        $mapping = [
            'procurement.approval.manager_review' => 'manager_review',
            'procurement.approval.finance_check' => 'finance_check',
            'procurement.approval.operations_approval' => 'operations_approval',
            'procurement.approval.executive_approval' => 'executive_approval',
        ];

        $allowedKeys = RolePermission::whereHas('permission', function ($q) {
            $q->where('key', 'like', 'procurement.approval.%');
        })->where('role', $user->role)->where('allowed', true)
          ->with('permission')
          ->get()
          ->pluck('permission.key')
          ->toArray();

        foreach ($mapping as $permKey => $stepType) {
            if (in_array($permKey, $allowedKeys)) {
                $stepTypes[] = $stepType;
            }
        }

        return $stepTypes;
    }
}
