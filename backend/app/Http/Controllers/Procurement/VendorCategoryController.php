<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreVendorCategoryRequest;
use App\Http\Requests\Procurement\UpdateVendorCategoryRequest;
use App\Models\AuditLog;
use App\Models\VendorCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorCategoryController extends Controller
{
    /**
     * GET /api/v1/procurement/vendor-categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = VendorCategory::query();

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
        $allowedSorts = ['name', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $categories = $query->get();
            return $this->success([
                'vendor_categories' => $categories->map->toApiArray(),
                'meta' => ['total' => $categories->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'vendor_categories' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/vendor-categories
     */
    public function store(StoreVendorCategoryRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $category = VendorCategory::create($request->validated());

            AuditLog::record(
                auth()->id(),
                'vendor_category_created',
                'vendor_category',
                $category->id,
                null,
                $category->toApiArray()
            );

            return $this->success([
                'vendor_category' => $category->toApiArray(),
            ], 'Vendor category created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/vendor-categories/{id}
     */
    public function show(string $id): JsonResponse
    {
        $category = VendorCategory::findOrFail($id);

        return $this->success([
            'vendor_category' => $category->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/vendor-categories/{id}
     */
    public function update(UpdateVendorCategoryRequest $request, string $id): JsonResponse
    {
        $category = VendorCategory::findOrFail($id);
        $oldValues = $category->toApiArray();

        return DB::transaction(function () use ($request, $category, $oldValues) {
            $category->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'vendor_category_updated',
                'vendor_category',
                $category->id,
                $oldValues,
                $category->fresh()->toApiArray()
            );

            return $this->success([
                'vendor_category' => $category->fresh()->toApiArray(),
            ], 'Vendor category updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/vendor-categories/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $category = VendorCategory::findOrFail($id);

        $activeVendors = $category->vendors()->where('status', 'active')->count();
        if ($activeVendors > 0) {
            return $this->error(
                'Cannot delete category with active vendors. Reassign them first.',
                422
            );
        }

        return DB::transaction(function () use ($category, $id) {
            $categoryData = $category->toApiArray();
            $category->update(['status' => 'inactive']);

            AuditLog::record(
                auth()->id(),
                'vendor_category_deleted',
                'vendor_category',
                $id,
                $categoryData,
                ['status' => 'inactive']
            );

            return $this->success(null, 'Vendor category deactivated successfully');
        });
    }
}
