<?php

namespace App\Services\Procurement;

use App\Models\Department;
use App\Models\Requisition;
use App\Models\User;

/**
 * Single source of truth for "can this user act on a given PR approval stage?".
 *
 * Business rules (defined by the organisation):
 *   - budget_holder : the employee explicitly set as the PR's budget_holder_id
 *                     (the requester picks this at creation; defaults to the
 *                     linked project's manager). For legacy PRs without an
 *                     explicit budget_holder_id, falls back to the linked
 *                     project's manager. Admins always bypass.
 *   - finance       : any manager in the Finance department (code=FINANCE), OR any admin
 *   - procurement   : any manager in the Procurement department (code=PROCUREMENT), OR any admin
 *
 * Admins ALWAYS bypass department/project checks — they can act on any stage.
 *
 * Department matching is by the canonical `code` column (bulletproof against
 * name changes). We also accept a match on the department's current name for
 * backward compatibility with users whose `department` string was saved before
 * the code column existed.
 */
class ApprovalAuthorizer
{
    public const FINANCE_CODE     = 'FINANCE';
    public const PROCUREMENT_CODE = 'PROCUREMENT';
    public const OPERATIONS_CODE  = 'OPERATIONS';

    /** In-request cache of Department lookups keyed by code. */
    private array $departmentByCode = [];

    /**
     * Can $user act on $stage of this $requisition right now?
     *
     * @return array{allowed:bool,reason:?string}
     */
    public function canActOnStage(User $user, Requisition $requisition, string $stage): array
    {
        if ($this->isAdmin($user)) {
            return ['allowed' => true, 'reason' => null];
        }

        return match ($stage) {
            'budget_holder' => $this->canActAsBudgetHolder($user, $requisition),
            'finance'       => $this->canActAsFinance($user),
            'procurement'   => $this->canActAsProcurement($user),
            'operations'    => $this->canActAsOperations($user),
            default         => ['allowed' => false, 'reason' => "Unknown approval stage: {$stage}."],
        };
    }

    /**
     * Stages the user is potentially eligible for (before PR-specific checks).
     * Used for dashboard filtering. Budget holder is PR-dependent, so it is
     * included here for every non-admin too — the per-PR check narrows later.
     */
    public function eligibleStagesFor(User $user): array
    {
        if ($this->isAdmin($user)) {
            return ['budget_holder', 'finance', 'procurement', 'operations'];
        }

        $stages = ['budget_holder']; // PR-specific; further filtered per requisition

        if ($this->isManagerInDepartment($user, self::FINANCE_CODE)) {
            $stages[] = 'finance';
        }

        if ($this->isManagerInDepartment($user, self::PROCUREMENT_CODE)) {
            $stages[] = 'procurement';
        }

        if ($this->isManagerInDepartment($user, self::OPERATIONS_CODE)) {
            $stages[] = 'operations';
        }

        return $stages;
    }

    /**
     * Can this user act as the Operations approver? Used for the PR Operations
     * stage AND for BOQ approval (Operations is the final approver on both).
     * Admins always may; otherwise the user must be a manager in Operations.
     */
    public function isOperationsApprover(User $user): bool
    {
        return $this->isAdmin($user) || $this->isManagerInDepartment($user, self::OPERATIONS_CODE);
    }

    /**
     * Can the user create / own RFQs? (Procurement managers and admins only.)
     */
    public function canManageRfqs(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isManagerInDepartment($user, self::PROCUREMENT_CODE);
    }

    /**
     * True when the PR's Budget Holder is the same person who raised/submitted
     * it (matched by email). Drives the originator-skip rule (v2 §3.4) and the
     * segregation-of-duties principle at the Budget Holder stage: a requester
     * must not approve their own request.
     *
     * Role is irrelevant here — this is about identity, so admins are NOT
     * special-cased. The match is by email because the Budget Holder is an
     * Employee record while the requester/submitter are User records, and email
     * is the canonical link between the two (same convention as the per-stage
     * authorization above).
     */
    public function budgetHolderIsOriginator(Requisition $requisition): bool
    {
        $holderEmail = $this->budgetHolderEmail($requisition);
        if (!$holderEmail) {
            return false;
        }

        foreach ($this->originatorEmails($requisition) as $email) {
            if ($email !== '' && strcasecmp($email, $holderEmail) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the Budget Holder's email: the explicitly-assigned budget holder,
     * else the linked project's manager (legacy fallback). Null when neither
     * can be resolved.
     */
    private function budgetHolderEmail(Requisition $requisition): ?string
    {
        if ($requisition->budget_holder_id) {
            $requisition->loadMissing('budgetHolder');
            $email = $requisition->budgetHolder?->email;
            if ($email) {
                return $email;
            }
        }

        if ($requisition->project_id) {
            $requisition->loadMissing('project.projectManager');
            return $requisition->project?->projectManager?->email;
        }

        return null;
    }

    /**
     * Emails of the people who raised the PR — the requester and the submitter.
     *
     * @return string[]
     */
    private function originatorEmails(Requisition $requisition): array
    {
        $requisition->loadMissing(['requestedBy', 'submittedBy']);

        return array_values(array_filter([
            $requisition->requestedBy?->email,
            $requisition->submittedBy?->email,
        ]));
    }

    /* ----------------------------------------------------------------
     * Internals
     * ---------------------------------------------------------------- */

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin'], true);
    }

    /**
     * True when $user is role=manager and their stored department string
     * matches either the canonical department's code OR its current name.
     */
    private function isManagerInDepartment(User $user, string $code): bool
    {
        if ($user->role !== 'manager') {
            return false;
        }

        $userDept = is_string($user->department) ? trim($user->department) : '';
        if ($userDept === '') {
            return false;
        }

        // 1. Direct code match — most reliable and cheapest
        if (strcasecmp($userDept, $code) === 0) {
            return true;
        }

        // 2. Resolve canonical department by code, match its current name
        $department = $this->departmentFor($code);
        if ($department && strcasecmp($userDept, $department->name) === 0) {
            return true;
        }

        return false;
    }

    private function departmentFor(string $code): ?Department
    {
        if (!array_key_exists($code, $this->departmentByCode)) {
            $this->departmentByCode[$code] = Department::where('code', $code)->first();
        }

        return $this->departmentByCode[$code];
    }

    private function canActAsBudgetHolder(User $user, Requisition $requisition): array
    {
        // Preferred path: explicit budget holder set on the requisition.
        if ($requisition->budget_holder_id) {
            $requisition->loadMissing('budgetHolder');
            $holder = $requisition->budgetHolder;

            if (!$holder) {
                return [
                    'allowed' => false,
                    'reason'  => 'The selected budget holder no longer exists — please ask an administrator to reassign.',
                ];
            }

            if ($holder->email && strcasecmp($holder->email, $user->email) === 0) {
                return ['allowed' => true, 'reason' => null];
            }

            return [
                'allowed' => false,
                'reason'  => 'Only the assigned budget holder (or an administrator) can approve at the Budget Holder stage.',
            ];
        }

        // Legacy fallback: PRs created before budget_holder_id existed and that
        // the migration backfill couldn't fill (e.g. project had no manager
        // at the time). Defer to the linked project's current manager.
        if (!$requisition->project_id) {
            return [
                'allowed' => false,
                'reason'  => 'This purchase request has no budget holder and is not linked to a project — only an administrator can approve as Budget Holder.',
            ];
        }

        $requisition->loadMissing('project.projectManager');
        $pm = $requisition->project?->projectManager;

        if (!$pm) {
            return [
                'allowed' => false,
                'reason'  => 'The linked project has no assigned project manager — only an administrator can approve as Budget Holder.',
            ];
        }

        if ($pm->email && strcasecmp($pm->email, $user->email) === 0) {
            return ['allowed' => true, 'reason' => null];
        }

        return [
            'allowed' => false,
            'reason'  => 'Only the project manager of the linked project (or an administrator) can approve at the Budget Holder stage.',
        ];
    }

    private function canActAsFinance(User $user): array
    {
        return $this->isManagerInDepartment($user, self::FINANCE_CODE)
            ? ['allowed' => true, 'reason' => null]
            : ['allowed' => false, 'reason' => 'Only a manager in the Finance department (or an administrator) can approve at the Finance stage.'];
    }

    private function canActAsProcurement(User $user): array
    {
        return $this->isManagerInDepartment($user, self::PROCUREMENT_CODE)
            ? ['allowed' => true, 'reason' => null]
            : ['allowed' => false, 'reason' => 'Only a manager in the Procurement department (or an administrator) can approve at the Procurement stage.'];
    }

    private function canActAsOperations(User $user): array
    {
        return $this->isManagerInDepartment($user, self::OPERATIONS_CODE)
            ? ['allowed' => true, 'reason' => null]
            : ['allowed' => false, 'reason' => 'Only a manager in the Operations department (or an administrator) can approve at the Operations stage.'];
    }
}
