<?php

namespace App\Services\Access;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Central authority for "what can this user see across the organisation?".
 *
 * Visibility tiers:
 *   - Org-wide  : super_admin, admin, country_director, AND any manager whose
 *                 department is Operations (the Operations Director / leads).
 *                 These users see every department's data on dashboards and
 *                 cross-department lists.
 *   - Own dept  : everyone else — scoped to their own department only.
 *
 * Department matching mirrors {@see \App\Services\Procurement\ApprovalAuthorizer}:
 * the user's stored `department` string is matched against the canonical
 * Department `code` first, then its current `name` (case-insensitive) for
 * backward compatibility with users saved before codes existed.
 *
 * Registered as a singleton (see AppServiceProvider) so the tiny department
 * lookups are resolved once per request.
 */
class DepartmentScope
{
    public const OPERATIONS_CODE = 'OPERATIONS';

    /** Roles that always see the whole organisation, regardless of department. */
    public const ORG_WIDE_ROLES = ['super_admin', 'admin', 'country_director'];

    /** In-request cache: uppercased-code => Department|null. */
    private array $byCode = [];

    /** In-request cache: lowercased-name => code|null. */
    private array $codeByName = [];

    /**
     * Does this user see data across all departments?
     */
    public function canSeeAllDepartments(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (in_array($user->role, self::ORG_WIDE_ROLES, true)) {
            return true;
        }

        // Operations managers/leads (e.g. the Operations Director) get the
        // org-wide view so they can monitor every department's activity.
        return $this->isManagerInDepartment($user, self::OPERATIONS_CODE);
    }

    /**
     * Can this user act as the Operations approver (final stage on PR, and the
     * approver on BOQ/RFQ)? Admins included; otherwise an Operations manager.
     */
    public function isOperationsApprover(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user->role, ['super_admin', 'admin'], true)) {
            return true;
        }
        return $this->isManagerInDepartment($user, self::OPERATIONS_CODE);
    }

    /**
     * Is the user a manager in the department identified by $code?
     */
    public function isManagerInDepartment(?User $user, string $code): bool
    {
        if (!$user || $user->role !== 'manager') {
            return false;
        }

        return $this->userDepartmentCode($user) === strtoupper(trim($code));
    }

    /**
     * Resolve the user's stored department string to a canonical department
     * CODE (uppercase), or null if unset / unmatched.
     */
    public function userDepartmentCode(?User $user): ?string
    {
        $dept = $user && is_string($user->department) ? trim($user->department) : '';
        if ($dept === '') {
            return null;
        }

        // 1. Direct code match (cheapest, most reliable).
        $upper = strtoupper($dept);
        if ($this->departmentByCode($upper)) {
            return $upper;
        }

        // 2. Match by the department's current name.
        return $this->codeForName($dept);
    }

    /**
     * Resolve the user's department to the departments table primary key
     * (UUID), or null if unset / unmatched. Handy for scoping tables that
     * store a `department_id` foreign key.
     */
    public function userDepartmentId(?User $user): ?string
    {
        $code = $this->userDepartmentCode($user);

        return $code ? $this->departmentByCode($code)?->id : null;
    }

    /**
     * Scope a query to the user's department unless they have org-wide access.
     * For tables keyed by a `department_id` foreign key.
     *
     * A user with org-wide access is unfiltered. A scoped user with no
     * resolvable department sees nothing (fail closed).
     */
    public function scopeByDepartmentId(Builder $query, ?User $user, string $column = 'department_id'): Builder
    {
        if ($this->canSeeAllDepartments($user)) {
            return $query;
        }

        $id = $this->userDepartmentId($user);

        return $id === null ? $query->whereRaw('1 = 0') : $query->where($column, $id);
    }

    /* ---------------------------------------------------------------- */

    private function departmentByCode(string $code): ?Department
    {
        $code = strtoupper(trim($code));
        if (!array_key_exists($code, $this->byCode)) {
            $this->byCode[$code] = Department::where('code', $code)->first();
        }

        return $this->byCode[$code];
    }

    private function codeForName(string $name): ?string
    {
        $key = mb_strtolower(trim($name));
        if (!array_key_exists($key, $this->codeByName)) {
            $dept = Department::whereRaw('LOWER(name) = ?', [$key])->first();
            $this->codeByName[$key] = $dept?->code;
        }

        return $this->codeByName[$key];
    }
}
