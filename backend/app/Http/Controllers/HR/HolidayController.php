<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreHolidayRequest;
use App\Http\Requests\HR\UpdateHolidayRequest;
use App\Models\AuditLog;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Holiday::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->input('sort_by', 'date');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'date', 'type', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'holidays' => $items->map->toApiArray(),
                'meta' => ['total' => $items->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'holidays' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = Holiday::create($request->validated());

        AuditLog::record(
            auth()->id(),
            'holiday_created',
            'holiday',
            $holiday->id,
            null,
            $holiday->toApiArray()
        );

        return $this->success([
            'holiday' => $holiday->toApiArray(),
        ], 'Holiday created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        return $this->success([
            'holiday' => $holiday->toApiArray(),
        ]);
    }

    public function update(UpdateHolidayRequest $request, string $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        $oldValues = $holiday->toApiArray();

        $holiday->update($request->validated());

        AuditLog::record(
            auth()->id(),
            'holiday_updated',
            'holiday',
            $holiday->id,
            $oldValues,
            $holiday->toApiArray()
        );

        return $this->success([
            'holiday' => $holiday->toApiArray(),
        ], 'Holiday updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        $data = $holiday->toApiArray();

        $holiday->delete();

        AuditLog::record(
            auth()->id(),
            'holiday_deleted',
            'holiday',
            $id,
            $data,
            null
        );

        return $this->success(null, 'Holiday deleted successfully');
    }
}
