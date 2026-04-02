<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\StoreBeneficiaryRequest;
use App\Http\Requests\Programs\UpdateBeneficiaryRequest;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Beneficiary::with('project:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'enrollment_date', 'status', 'location', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'beneficiaries' => $items->map->toApiArray(),
                'meta' => ['total' => $items->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'beneficiaries' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function store(StoreBeneficiaryRequest $request): JsonResponse
    {
        $beneficiary = Beneficiary::create($request->validated());
        $beneficiary->load('project:id,name');

        AuditLog::record(
            auth()->id(),
            'beneficiary_created',
            'beneficiary',
            $beneficiary->id,
            null,
            $beneficiary->toApiArray()
        );

        return $this->success([
            'beneficiary' => $beneficiary->toApiArray(),
        ], 'Beneficiary created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $beneficiary = Beneficiary::with('project:id,name')->findOrFail($id);

        return $this->success([
            'beneficiary' => $beneficiary->toApiArray(),
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, string $id): JsonResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);
        $oldValues = $beneficiary->toApiArray();

        $beneficiary->update($request->validated());
        $beneficiary->load('project:id,name');

        AuditLog::record(
            auth()->id(),
            'beneficiary_updated',
            'beneficiary',
            $beneficiary->id,
            $oldValues,
            $beneficiary->toApiArray()
        );

        return $this->success([
            'beneficiary' => $beneficiary->toApiArray(),
        ], 'Beneficiary updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);
        $data = $beneficiary->toApiArray();

        $beneficiary->delete();

        AuditLog::record(
            auth()->id(),
            'beneficiary_deleted',
            'beneficiary',
            $id,
            $data,
            null
        );

        return $this->success(null, 'Beneficiary deleted successfully');
    }
}
