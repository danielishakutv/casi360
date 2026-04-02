<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveTypeRequest;
use App\Http\Requests\HR\UpdateLeaveTypeRequest;
use App\Models\AuditLog;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeaveType::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'days_allowed', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'leave_types' => $items->map->toApiArray(),
                'meta' => ['total' => $items->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'leave_types' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = LeaveType::create($request->validated());

        AuditLog::record(
            auth()->id(),
            'leave_type_created',
            'leave_type',
            $leaveType->id,
            null,
            $leaveType->toApiArray()
        );

        return $this->success([
            'leave_type' => $leaveType->toApiArray(),
        ], 'Leave type created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        return $this->success([
            'leave_type' => $leaveType->toApiArray(),
        ]);
    }

    public function update(UpdateLeaveTypeRequest $request, string $id): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);
        $oldValues = $leaveType->toApiArray();

        $leaveType->update($request->validated());

        AuditLog::record(
            auth()->id(),
            'leave_type_updated',
            'leave_type',
            $leaveType->id,
            $oldValues,
            $leaveType->toApiArray()
        );

        return $this->success([
            'leave_type' => $leaveType->toApiArray(),
        ], 'Leave type updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);
        $data = $leaveType->toApiArray();

        $leaveType->delete();

        AuditLog::record(
            auth()->id(),
            'leave_type_deleted',
            'leave_type',
            $id,
            $data,
            null
        );

        return $this->success(null, 'Leave type deleted successfully');
    }
}
