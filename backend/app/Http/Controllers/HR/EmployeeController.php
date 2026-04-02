<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreEmployeeRequest;
use App\Http\Requests\HR\UpdateEmployeeRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * GET /api/v1/hr/employees
     * 
     * List all employees. Supports filtering, searching, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with(['department', 'designation']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'email', 'staff_id', 'status', 'join_date', 'salary', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination (capped at 100)
        $perPage = min((int) $request->input('per_page', 25), 100);
        $paginated = $query->paginate($perPage);

        return $this->success([
            'employees' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/hr/employees
     * 
     * Create a new employee (staff member).
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            // Auto-generate staff_id
            $data['staff_id'] = $this->generateStaffId();

            $employee = Employee::create($data);
            $employee->load(['department', 'designation']);

            AuditLog::record(
                auth()->id(),
                'employee_created',
                'employee',
                $employee->id,
                null,
                $employee->toApiArray()
            );

            return $this->success([
                'employee' => $employee->toApiArray(),
            ], 'Employee created successfully', 201);
        });
    }

    /**
     * GET /api/v1/hr/employees/{id}
     * 
     * Get a specific employee.
     */
    public function show(string $id): JsonResponse
    {
        $employee = Employee::with(['department', 'designation'])->findOrFail($id);

        return $this->success([
            'employee' => $employee->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/hr/employees/{id}
     * 
     * Update an employee.
     */
    public function update(UpdateEmployeeRequest $request, string $id): JsonResponse
    {
        $employee = Employee::with(['department', 'designation'])->findOrFail($id);
        $oldValues = $employee->toApiArray();

        return DB::transaction(function () use ($request, $employee, $oldValues) {
            $employee->update($request->validated());
            $employee->load(['department', 'designation']);

            AuditLog::record(
                auth()->id(),
                'employee_updated',
                'employee',
                $employee->id,
                $oldValues,
                $employee->fresh()->load(['department', 'designation'])->toApiArray()
            );

            return $this->success([
                'employee' => $employee->fresh()->load(['department', 'designation'])->toApiArray(),
            ], 'Employee updated successfully');
        });
    }

    /**
     * DELETE /api/v1/hr/employees/{id}
     * 
     * Soft-terminate an employee (sets status to 'terminated').
     */
    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        if ($employee->status === 'terminated') {
            return $this->error('Employee is already terminated.', 422);
        }

        $oldValues = $employee->toApiArray();

        return DB::transaction(function () use ($employee, $oldValues) {
            $employee->update([
                'status' => 'terminated',
                'termination_date' => now()->toDateString(),
            ]);

            AuditLog::record(
                auth()->id(),
                'employee_terminated',
                'employee',
                $employee->id,
                $oldValues,
                $employee->fresh()->load(['department', 'designation'])->toApiArray()
            );

            return $this->success([
                'employee' => $employee->fresh()->load(['department', 'designation'])->toApiArray(),
            ], 'Employee terminated successfully');
        });
    }

    /**
     * PATCH /api/v1/hr/employees/{id}/status
     * 
     * Update employee status (active, on_leave, terminated).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,on_leave,terminated',
        ]);

        $employee = Employee::findOrFail($id);
        $oldStatus = $employee->status;

        return DB::transaction(function () use ($request, $employee, $oldStatus) {
            $updateData = ['status' => $request->status];
            if ($request->status === 'terminated' && !$employee->termination_date) {
                $updateData['termination_date'] = now()->toDateString();
            }

            $employee->update($updateData);

            AuditLog::record(
                auth()->id(),
                'employee_status_changed',
                'employee',
                $employee->id,
                ['status' => $oldStatus],
                ['status' => $request->status]
            );

            return $this->success([
                'employee' => $employee->fresh()->load(['department', 'designation'])->toApiArray(),
            ], 'Employee status updated successfully');
        });
    }

    /**
     * GET /api/v1/hr/employees/stats
     * 
     * Get employee statistics overview.
     */
    public function stats(): JsonResponse
    {
        $total = Employee::count();
        $active = Employee::where('status', 'active')->count();
        $onLeave = Employee::where('status', 'on_leave')->count();
        $terminated = Employee::where('status', 'terminated')->count();

        $byDepartment = Employee::where('status', '!=', 'terminated')
            ->selectRaw('department_id, count(*) as count')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'department_id' => $item->department_id,
                    'department' => $item->department?->name,
                    'count' => $item->count,
                ];
            });

        return $this->success([
            'total' => $total,
            'active' => $active,
            'on_leave' => $onLeave,
            'terminated' => $terminated,
            'by_department' => $byDepartment,
        ]);
    }

    /**
     * Generate a unique staff ID with format CASI-XXXX.
     */
    private function generateStaffId(): string
    {
        $lastEmployee = Employee::orderBy('created_at', 'desc')->first();

        if ($lastEmployee && preg_match('/CASI-(\d+)/', $lastEmployee->staff_id, $matches)) {
            $nextNum = (int) $matches[1] + 1;
        } else {
            $nextNum = 1001;
        }

        $staffId = 'CASI-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while (Employee::where('staff_id', $staffId)->exists()) {
            $nextNum++;
            $staffId = 'CASI-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }

        return $staffId;
    }
}
