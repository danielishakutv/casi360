<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreVendorRequest;
use App\Http\Requests\Procurement\UpdateVendorRequest;
use App\Models\AuditLog;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    /**
     * GET /api/v1/procurement/vendors
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'contact_person', 'email', 'city', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $vendors = $query->get();
            return $this->success([
                'vendors' => $vendors->map->toApiArray(),
                'meta' => [
                    'total' => $vendors->count(),
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'vendors' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/vendors
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['vendor_code'] = Vendor::generateVendorCode();
            $vendor = Vendor::create($data);

            AuditLog::record(
                auth()->id(),
                'vendor_created',
                'vendor',
                $vendor->id,
                null,
                $vendor->toApiArray()
            );

            return $this->success([
                'vendor' => $vendor->toApiArray(),
            ], 'Vendor created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/vendors/{id}
     */
    public function show(string $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        return $this->success([
            'vendor' => $vendor->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/vendors/{id}
     */
    public function update(UpdateVendorRequest $request, string $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);
        $oldValues = $vendor->toApiArray();

        return DB::transaction(function () use ($request, $vendor, $oldValues) {
            $vendor->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'vendor_updated',
                'vendor',
                $vendor->id,
                $oldValues,
                $vendor->fresh()->toApiArray()
            );

            return $this->success([
                'vendor' => $vendor->fresh()->toApiArray(),
            ], 'Vendor updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/vendors/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $vendor = Vendor::findOrFail($id);

        // Prevent deletion if vendor has active purchase orders
        $activePOs = $vendor->purchaseOrders()
            ->whereNotIn('status', ['cancelled', 'received'])
            ->count();
        if ($activePOs > 0) {
            return $this->error(
                'Cannot delete vendor with active purchase orders. Complete or cancel them first.',
                422
            );
        }

        return DB::transaction(function () use ($vendor, $id) {
            $vendorData = $vendor->toApiArray();
            $vendor->update(['status' => 'inactive']);

            AuditLog::record(
                auth()->id(),
                'vendor_deleted',
                'vendor',
                $id,
                $vendorData,
                ['status' => 'inactive']
            );

            return $this->success(null, 'Vendor deactivated successfully');
        });
    }
}
