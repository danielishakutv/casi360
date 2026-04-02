<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreDepartmentRequest;
use App\Http\Requests\HR\UpdateDepartmentRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Forum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * GET /api/v1/hr/departments
     * 
     * List all departments. Supports filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Department::query();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('head', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'head', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination (pass per_page=0 to get all without pagination, capped at 100)
        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $departments = $query->get();
            return $this->success([
                'departments' => $departments->map->toApiArray(),
                'meta' => [
                    'total' => $departments->count(),
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'departments' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/hr/departments
     * 
     * Create a new department.
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $department = Department::create($request->validated());

            AuditLog::record(
                auth()->id(),
                'department_created',
                'department',
                $department->id,
                null,
                $department->toApiArray()
            );

            // Auto-create a department forum
            Forum::create([
                'name'          => $department->name,
                'description'   => "Discussion forum for the {$department->name} department.",
                'type'          => 'department',
                'department_id' => $department->id,
                'status'        => 'active',
            ]);

            return $this->success([
                'department' => $department->toApiArray(),
            ], 'Department created successfully', 201);
        });
    }

    /**
     * GET /api/v1/hr/departments/{id}
     * 
     * Get a specific department.
     */
    public function show(string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        return $this->success([
            'department' => $department->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/hr/departments/{id}
     * 
     * Update a department.
     */
    public function update(UpdateDepartmentRequest $request, string $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $oldValues = $department->toApiArray();

        return DB::transaction(function () use ($request, $department, $oldValues) {
            $department->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'department_updated',
                'department',
                $department->id,
                $oldValues,
                $department->fresh()->toApiArray()
            );

            return $this->success([
                'department' => $department->fresh()->toApiArray(),
            ], 'Department updated successfully');
        });
    }

    /**
     * DELETE /api/v1/hr/departments/{id}
     * 
     * Delete a department. Fails if department has active employees.
     */
    public function destroy(string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        // Prevent deletion if employees exist
        $activeEmployees = $department->employees()->where('status', '!=', 'terminated')->count();
        if ($activeEmployees > 0) {
            return $this->error(
                'Cannot delete department with active employees. Reassign or terminate them first.',
                422
            );
        }

        return DB::transaction(function () use ($department, $id) {
            $departmentData = $department->toApiArray();
            $department->delete();

            AuditLog::record(
                auth()->id(),
                'department_deleted',
                'department',
                $id,
                $departmentData,
                null
            );

            return $this->success(null, 'Department deleted successfully');
        });
    }
}
