<?php

namespace App\Services\Procurement;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server-side filter that narrows procurement document list queries to
 * records that "concern" the current user. Used by the PR, PO, BOQ, RFQ,
 * RFP, and GRN list endpoints.
 *
 * Why a single service: the audit-log predicate ("the current user has
 * touched this document at least once") is identical for every document
 * type, so it lives once here. Document-specific extras (PR's department-
 * mate visibility, PO's approval-step actor, etc.) live in dedicated
 * private methods so each rule is auditable in isolation.
 *
 * Usage:
 *   if ($scopeService->shouldScope($user, 'procurement.boq.view_all', $request)) {
 *       $scopeService->applyToBoqs($query, $user);
 *   }
 *
 * Defense-in-depth: shouldScope() ignores ?mine=0 from a user without
 * the matching view_all permission. The client cannot widen visibility
 * beyond what the server allows.
 */
class DocumentScopeService
{
    /**
     * True when the controller should narrow results to "concerns me".
     *
     * Rule:
     *   - User lacks the view_all key  → always scope (regardless of ?mine).
     *   - User has it (or is super_admin) and explicitly sends ?mine=1 → scope.
     *   - User has it and ?mine is absent or "0"/"false" → no scope (org-wide).
     */
    public function shouldScope(?User $user, string $viewAllPermissionKey, Request $request): bool
    {
        if (!$user) {
            // Auth middleware should have caught this; treat as scoped just in case.
            return true;
        }

        $hasViewAll = $this->userHasPermission($user, $viewAllPermissionKey);
        $mineRequested = $request->boolean('mine');

        return !$hasViewAll || $mineRequested;
    }

    /* ----------------------------------------------------------------
     * Per-document scope appliers
     * ---------------------------------------------------------------- */

    public function applyToRequisitions(Builder $query, User $user): void
    {
        $userId = $user->id;
        $departmentName = $user->department;

        $query->where(function ($q) use ($userId, $departmentName) {
            $q->where('requisitions.requested_by', $userId)
              ->orWhere('requisitions.submitted_by', $userId)
              ->orWhere(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'requisition', 'requisitions.id'))
              ->orWhereExists(function ($sub) use ($userId) {
                  $sub->select(DB::raw(1))
                      ->from('requisition_audit_logs as ral')
                      ->whereColumn('ral.requisition_id', 'requisitions.id')
                      ->where('ral.actor_id', $userId);
              })
              ->orWhereExists(function ($sub) use ($userId) {
                  $sub->select(DB::raw(1))
                      ->from('requisition_approvals as ra')
                      ->whereColumn('ra.requisition_id', 'requisitions.id')
                      ->where('ra.actor_id', $userId);
              });

            // Department-mate visibility — requester is in the same department as the user
            if (!empty($departmentName)) {
                $q->orWhereExists(function ($sub) use ($departmentName) {
                    $sub->select(DB::raw(1))
                        ->from('users as req_user')
                        ->whereColumn('req_user.id', 'requisitions.requested_by')
                        ->where('req_user.department', $departmentName);
                });
            }
        });
    }

    public function applyToPurchaseOrders(Builder $query, User $user): void
    {
        $userId = $user->id;
        $departmentName = $user->department;

        $query->where(function ($q) use ($userId, $departmentName) {
            $q->where('purchase_orders.submitted_by', $userId)
              ->orWhere(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'purchase_order', 'purchase_orders.id'))
              // Approval-step actor (legacy polymorphic table — POs use ApprovalStep)
              ->orWhereExists(function ($sub) use ($userId) {
                  $sub->select(DB::raw(1))
                      ->from('approval_steps as ast')
                      ->whereColumn('ast.approvable_id', 'purchase_orders.id')
                      ->where('ast.approvable_type', 'purchase_order')
                      ->where('ast.acted_by', $userId);
              });

            // Department-mate visibility — PO's department matches the user's department
            if (!empty($departmentName)) {
                $q->orWhereExists(function ($sub) use ($departmentName) {
                    $sub->select(DB::raw(1))
                        ->from('departments as d')
                        ->whereColumn('d.id', 'purchase_orders.department_id')
                        ->where('d.name', $departmentName);
                });
            }
        });
    }

    public function applyToBoqs(Builder $query, User $user): void
    {
        $userId = $user->id;
        $departmentName = $user->department;

        $query->where(function ($q) use ($userId, $departmentName) {
            $q->where(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'boq', 'boqs.id'))
              ->orWhereExists(function ($sub) use ($userId) {
                  $sub->select(DB::raw(1))
                      ->from('boq_audit_logs as bal')
                      ->whereColumn('bal.boq_id', 'boqs.id')
                      ->where('bal.actor_id', $userId);
              });

            // Department-mate visibility — BOQ's department string matches the user's
            if (!empty($departmentName)) {
                $q->orWhere('boqs.department', $departmentName);
            }
        });
    }

    public function applyToRfqs(Builder $query, User $user): void
    {
        $userId = $user->id;

        $query->where(function ($q) use ($userId) {
            $q->where('rfqs.created_by', $userId)
              ->orWhere(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'rfq', 'rfqs.id'));
        });
    }

    public function applyToRfps(Builder $query, User $user): void
    {
        $userId = $user->id;
        $departmentName = $user->department;

        $query->where(function ($q) use ($userId, $departmentName) {
            $q->where(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'rfp', 'rfps.id'));

            if (!empty($departmentName)) {
                $q->orWhere('rfps.department', $departmentName);
            }
        });
    }

    public function applyToGrns(Builder $query, User $user): void
    {
        $userId = $user->id;

        $query->where(function ($q) use ($userId) {
            $q->where('grns.created_by', $userId)
              ->orWhere(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'grn', 'grns.id'));
        });
    }

    public function applyToInvoices(Builder $query, User $user): void
    {
        $userId = $user->id;
        $departmentName = $user->department;

        $query->where(function ($q) use ($userId, $departmentName) {
            $q->where('invoices.created_by', $userId)
              ->orWhere('invoices.submitted_by', $userId)
              ->orWhere('invoices.approved_by', $userId)
              ->orWhere(fn ($a) => $this->whereTouchedInGenericAuditLog($a, $userId, 'invoice', 'invoices.id'));

            // Department-mate visibility — invoice's PO sits in the same
            // department as the user. Mirrors the PO scope predicate so
            // a user who can see a PO can also see the invoices against it.
            if (!empty($departmentName)) {
                $q->orWhereExists(function ($sub) use ($departmentName) {
                    $sub->select(DB::raw(1))
                        ->from('purchase_orders as po')
                        ->join('departments as d', 'd.id', '=', 'po.department_id')
                        ->whereColumn('po.id', 'invoices.po_id')
                        ->where('d.name', $departmentName);
                });
            }
        });
    }

    /* ----------------------------------------------------------------
     * Internals
     * ---------------------------------------------------------------- */

    /**
     * Universal predicate: there exists an audit_logs row created by the
     * given user against the given entity_type for the document's id.
     *
     * @param  Builder|\Illuminate\Database\Query\Builder  $query
     */
    private function whereTouchedInGenericAuditLog($query, string $userId, string $entityType, string $idColumn): void
    {
        $query->whereExists(function ($sub) use ($userId, $entityType, $idColumn) {
            $sub->select(DB::raw(1))
                ->from('audit_logs as al')
                ->whereColumn('al.entity_id', $idColumn)
                ->where('al.entity_type', $entityType)
                ->where('al.user_id', $userId);
        });
    }

    /**
     * True when the user (or super_admin) holds the given permission key.
     * Mirrors ApprovalController::userHasPermission so behaviour is consistent.
     */
    private function userHasPermission(User $user, string $key): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return RolePermission::where('role', $user->role)
            ->whereHas('permission', fn ($q) => $q->where('key', $key))
            ->where('allowed', true)
            ->exists();
    }
}
