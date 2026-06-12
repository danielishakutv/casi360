<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\Access\DepartmentScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Staff attendance — daily sign in / sign out.
 *
 * Clock in/out is self-service (any authenticated user, for their own employee
 * record). Viewing everyone's attendance requires hr.attendance.view_all;
 * adjusting records requires hr.attendance.manage (enforced at the route).
 */
class AttendanceController extends Controller
{
    /** Organisation timezone for the working day + lateness check. */
    private const TZ = 'Africa/Lagos';

    /** Sign-ins after this local time are flagged 'late'. */
    private const LATE_AFTER = '09:15';

    public function __construct(private DepartmentScope $scope)
    {
    }

    /* ----------------------------------------------------------------
     * Self-service: sign in / out / my records
     * ---------------------------------------------------------------- */

    public function clockIn(Request $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);
        if (!$employee) {
            return $this->error('No employee record is linked to your account. Please contact HR.', 422);
        }

        $now   = Carbon::now(self::TZ);
        $today = $now->toDateString();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'date'        => $today,
        ]);

        if ($attendance->clock_in) {
            return $this->error('You have already signed in today at ' . $attendance->clock_in->timezone(self::TZ)->format('g:i A') . '.', 422);
        }

        $attendance->clock_in    = $now;
        $attendance->status      = $now->format('H:i') > self::LATE_AFTER ? 'late' : 'present';
        $attendance->recorded_by = $request->user()->id;
        $attendance->save();

        return $this->success(['attendance' => $attendance->toApiArray()], 'Signed in successfully.');
    }

    public function clockOut(Request $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);
        if (!$employee) {
            return $this->error('No employee record is linked to your account. Please contact HR.', 422);
        }

        $today = Carbon::now(self::TZ)->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return $this->error('You have not signed in today.', 422);
        }

        if ($attendance->clock_out) {
            return $this->error('You have already signed out today at ' . $attendance->clock_out->timezone(self::TZ)->format('g:i A') . '.', 422);
        }

        $attendance->clock_out  = Carbon::now(self::TZ);
        $attendance->work_hours = $attendance->computeWorkHours();
        $attendance->save();

        return $this->success(['attendance' => $attendance->toApiArray()], 'Signed out successfully.');
    }

    /**
     * The current user's own attendance: today's record (for the sign in/out
     * widget) plus recent history (optionally a given ?month=YYYY-MM).
     */
    public function me(Request $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);
        if (!$employee) {
            return $this->success(['today' => null, 'records' => []]);
        }

        $today = Carbon::now(self::TZ)->toDateString();
        $todayRecord = Attendance::where('employee_id', $employee->id)->where('date', $today)->first();

        $query = Attendance::where('employee_id', $employee->id);
        $this->applyMonthFilter($query, $request->input('month'));

        $records = $query->orderByDesc('date')->limit(60)->get()->map->toApiArray()->values();

        return $this->success([
            'today'   => $todayRecord?->toApiArray(),
            'records' => $records,
        ]);
    }

    /* ----------------------------------------------------------------
     * HR views
     * ---------------------------------------------------------------- */

    /**
     * Today's attendance across all staff — for the HR dashboard.
     * Requires hr.attendance.view_all (route-enforced).
     */
    public function today(Request $request): JsonResponse
    {
        $today = Carbon::now(self::TZ)->toDateString();

        $records = Attendance::with('employee.department')
            ->where('date', $today)
            ->get();

        $activeStaff = Employee::where('status', 'active')->count();
        $signedIn    = $records->whereNotNull('clock_in')->count();
        $signedOut   = $records->whereNotNull('clock_out')->count();
        $late        = $records->where('status', 'late')->count();

        return $this->success([
            'date'    => $today,
            'summary' => [
                'active_staff'    => $activeStaff,
                'signed_in'       => $signedIn,
                'signed_out'      => $signedOut,
                'late'            => $late,
                'not_signed_in'   => max(0, $activeStaff - $signedIn),
                'still_clocked_in' => max(0, $signedIn - $signedOut),
            ],
            'records' => $records->map->toApiArray()->values(),
        ]);
    }

    /**
     * Attendance list with filters. Users without hr.attendance.view_all are
     * restricted to their own record (defence-in-depth — the route also
     * exposes /me for the common case).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::with('employee.department');

        if (!$this->canViewAll($request->user())) {
            $employee = $this->currentEmployee($request);
            $query->where('employee_id', $employee?->id ?? '00000000-0000-0000-0000-000000000000');
        } elseif ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $this->applyMonthFilter($query, $request->input('month'));

        $perPage = min((int) $request->input('per_page', 30), 200);
        $paginated = $query->orderByDesc('date')->paginate($perPage);

        return $this->success([
            'attendance' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * HR adjustment of a record (status, times, notes). Requires
     * hr.attendance.manage (route-enforced).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $attendance = Attendance::findOrFail($id);

        $data = $request->validate([
            'status'    => ['nullable', 'in:present,late,absent,on_leave,holiday'],
            'clock_in'  => ['nullable', 'date'],
            'clock_out' => ['nullable', 'date'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->fill($data);
        $attendance->work_hours = $attendance->computeWorkHours();
        $attendance->recorded_by = $request->user()->id;
        $attendance->save();

        return $this->success(['attendance' => $attendance->fresh()->toApiArray()], 'Attendance updated.');
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    private function currentEmployee(Request $request): ?Employee
    {
        return $request->user()?->employee;
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

    private function applyMonthFilter($query, ?string $month): void
    {
        if (!$month) {
            return;
        }
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $query->whereBetween('date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        } catch (\Throwable $e) {
            // Ignore an unparseable month rather than erroring.
        }
    }
}
