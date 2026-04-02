<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreDesignationRequest;
use App\Http\Requests\HR\UpdateDesignationRequest;
use App\Models\AuditLog;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    /**
     * GET /api/v1/hr/designations
     * 
     * List all designations. Supports filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Designation::with('department');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('department', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'title');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['title', 'level', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination (capped at 100)
        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $designations = $query->get();
            return $this->success([
                'designations' => $designations->map->toApiArray(),
                'meta' => [
                    'total' => $designations->count(),
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'designations' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/hr/designations
     * 
     * Create a new designation.
     */
    public function store(StoreDesignationRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $designation = Designation::create($request->validated());
            $designation->load('department');

            AuditLog::record(
                auth()->id(),
                'designation_created',
                'designation',
                $designation->id,
                null,
                $designation->toApiArray()
            );

            return $this->success([
                'designation' => $designation->toApiArray(),
            ], 'Designation created successfully', 201);
        });
    }

    /**
     * GET /api/v1/hr/designations/{id}
     * 
     * Get a specific designation.
     */
    public function show(string $id): JsonResponse
    {
        $designation = Designation::with('department')->findOrFail($id);

        return $this->success([
            'designation' => $designation->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/hr/designations/{id}
     * 
     * Update a designation.
     */
    public function update(UpdateDesignationRequest $request, string $id): JsonResponse
    {
        $designation = Designation::with('department')->findOrFail($id);
        $oldValues = $designation->toApiArray();

        return DB::transaction(function () use ($request, $designation, $oldValues) {
            $designation->update($request->validated());
            $designation->load('department');

            AuditLog::record(
                auth()->id(),
                'designation_updated',
                'designation',
                $designation->id,
                $oldValues,
                $designation->fresh()->load('department')->toApiArray()
            );

            return $this->success([
                'designation' => $designation->fresh()->load('department')->toApiArray(),
            ], 'Designation updated successfully');
        });
    }

    /**
     * DELETE /api/v1/hr/designations/{id}
     * 
     * Delete a designation. Fails if employees hold this designation.
     */
    public function destroy(string $id): JsonResponse
    {
        $designation = Designation::with('department')->findOrFail($id);

        // Prevent deletion if employees exist with this designation
        $activeEmployees = $designation->employees()->where('status', '!=', 'terminated')->count();
        if ($activeEmployees > 0) {
            return $this->error(
                'Cannot delete designation with active employees. Reassign them first.',
                422
            );
        }

        return DB::transaction(function () use ($designation, $id) {
            $designationData = $designation->toApiArray();
            $designation->delete();

            AuditLog::record(
                auth()->id(),
                'designation_deleted',
                'designation',
                $id,
                $designationData,
                null
            );

            return $this->success(null, 'Designation deleted successfully');
        });
    }
}
