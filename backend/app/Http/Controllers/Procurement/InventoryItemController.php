<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreInventoryItemRequest;
use App\Http\Requests\Procurement\UpdateInventoryItemRequest;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    /**
     * GET /api/v1/procurement/inventory
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('low_stock') && $request->low_stock === 'true') {
            $query->lowStock();
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['name', 'sku', 'category', 'quantity_in_stock', 'unit_cost', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $items = $query->get();
            return $this->success([
                'inventory_items' => $items->map->toApiArray(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'inventory_items' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/inventory
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $item = InventoryItem::create($request->validated());

            AuditLog::record(
                auth()->id(),
                'inventory_item_created',
                'inventory_item',
                $item->id,
                null,
                $item->toApiArray()
            );

            return $this->success([
                'inventory_item' => $item->toApiArray(),
            ], 'Inventory item created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/inventory/{id}
     */
    public function show(string $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        return $this->success([
            'inventory_item' => $item->toApiArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/inventory/{id}
     */
    public function update(UpdateInventoryItemRequest $request, string $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $oldValues = $item->toApiArray();

        return DB::transaction(function () use ($request, $item, $oldValues) {
            $item->update($request->validated());

            AuditLog::record(
                auth()->id(),
                'inventory_item_updated',
                'inventory_item',
                $item->id,
                $oldValues,
                $item->fresh()->toApiArray()
            );

            return $this->success([
                'inventory_item' => $item->fresh()->toApiArray(),
            ], 'Inventory item updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/inventory/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        return DB::transaction(function () use ($item, $id) {
            $itemData = $item->toApiArray();
            $item->update(['status' => 'inactive']);

            AuditLog::record(
                auth()->id(),
                'inventory_item_deleted',
                'inventory_item',
                $id,
                $itemData,
                ['status' => 'inactive']
            );

            return $this->success(null, 'Inventory item deactivated successfully');
        });
    }
}
