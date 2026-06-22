<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Monthly timesheets — derived entirely from attendance records (no separate
 * data entry). A timesheet is the per-employee monthly summary of sign-ins:
 * days present, days late, days on leave, and total hours worked.
 */
class TimesheetController extends Controller
{
    private const TZ = 'Africa/Lagos';

    /**
     * GET /hr/timesheets?month=YYYY-MM
     * Monthly summary for every active employee. Requires hr.attendance.view_all.
     */
    public function monthly(Request $request): JsonResponse
    {
        [$start, $end, $month] = $this->monthRange($request->input('month'));

        $employees = Employee::with(['department', 'user:id,department'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $byEmployee = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->map(function (Employee $emp) use ($byEmployee) {
            return $this->summaryRow($emp, $byEmployee->get($emp->id, collect()));
        })->values();

        return $this->success([
            'month'        => $month,
            'working_days' => $this->workingDays($start, $end),
            'timesheets'   => $rows,
        ]);
    }

    /**
     * GET /hr/timesheets/{employeeId}?month=YYYY-MM
     * One employee's monthly timesheet: summary + daily attendance rows.
     * view_all holders see anyone; others only their own.
     */
    public function employee(Request $request, string $employeeId): JsonResponse
    {
        if (!$this->canViewAll($request->user())) {
            $ownId = $request->user()?->employee?->id;
            if ($ownId !== $employeeId) {
                return $this->error('You can only view your own timesheet.', 403);
            }
        }

        $employee = Employee::with('department')->findOrFail($employeeId);
        [$start, $end, $month] = $this->monthRange($request->input('month'));

        $records = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        return $this->success([
            'month'      => $month,
            'working_days' => $this->workingDays($start, $end),
            'summary'    => $this->summaryRow($employee, $records),
            'days'       => $records->map->toApiArray()->values(),
        ]);
    }

    /**
     * GET /hr/timesheets/mine?month=YYYY-MM — the current user's own timesheet.
     */
    public function mine(Request $request): JsonResponse
    {
        $employee = $request->user()?->employee;
        if (!$employee) {
            return $this->success(['month' => null, 'summary' => null, 'days' => []]);
        }

        return $this->employee($request, $employee->id);
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    private function summaryRow(Employee $emp, $records): array
    {
        $present = $records->whereNotNull('clock_in')->count();
        $late    = $records->where('status', 'late')->count();
        $onLeave = $records->where('status', 'on_leave')->count();
        $hours   = round($records->sum(fn ($r) => (float) $r->work_hours), 2);

        return [
            'employee_id'   => $emp->id,
            'employee_name' => $emp->name,
            'department'    => $emp->department?->name,
            'days_present'  => $present,
            'days_late'     => $late,
            'days_on_leave' => $onLeave,
            'total_hours'   => $hours,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} [start, end, 'YYYY-MM'] */
    private function monthRange(?string $month): array
    {
        try {
            $start = $month
                ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
                : Carbon::now(self::TZ)->startOfMonth();
        } catch (\Throwable $e) {
            $start = Carbon::now(self::TZ)->startOfMonth();
        }

        return [$start, $start->copy()->endOfMonth(), $start->format('Y-m')];
    }

    /** Weekdays (Mon–Fri) in the range — a simple working-day reference. */
    private function workingDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (!$cursor->isWeekend()) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function canViewAll($user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->role === 'super_admin') {
            return true;
        }

        return \App\Models\RolePermission::where('role', $user->role)
            ->whereHas('permission', fn ($q) => $q->where('key', 'hr.attendance.view_all'))
            ->where('allowed', true)
            ->exists();
    }
}
