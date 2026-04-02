<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * GET /api/v1/procurement/purchase-orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['vendor', 'department', 'requestedBy', 'submittedBy']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['po_number', 'order_date', 'total_amount', 'status', 'payment_status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $orders = $query->get();
            return $this->success([
                'purchase_orders' => $orders->map->toApiArray(),
                'meta' => [
                    'total' => $orders->count(),
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return $this->success([
            'purchase_orders' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/purchase-orders
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['po_number'] = PurchaseOrder::generatePoNumber();
            $data['status'] = 'draft';

            $order = PurchaseOrder::create($data);

            foreach ($items as $item) {
                $item['purchase_order_id'] = $order->id;
                $item['total_price'] = $item['quantity'] * $item['unit_price'];
                PurchaseOrderItem::create($item);
            }

            $order->recalculateTotals();
            $order->refresh();
            $order->load(['vendor', 'department', 'requestedBy', 'submittedBy', 'items']);

            AuditLog::record(
                auth()->id(),
                'purchase_order_created',
                'purchase_order',
                $order->id,
                null,
                $order->toApiArray()
            );

            return $this->success([
                'purchase_order' => $order->toDetailArray(),
            ], 'Purchase order created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/purchase-orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        $order = PurchaseOrder::with([
            'vendor', 'department', 'requestedBy', 'submittedBy',
            'items.inventoryItem', 'approvalSteps', 'disbursements.disbursedBy',
        ])->findOrFail($id);

        return $this->success([
            'purchase_order' => $order->toDetailArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/purchase-orders/{id}
     */
    public function update(UpdatePurchaseOrderRequest $request, string $id): JsonResponse
    {
        $order = PurchaseOrder::with(['vendor', 'department', 'requestedBy', 'submittedBy', 'items'])
            ->findOrFail($id);

        if (!in_array($order->status, ['draft', 'revision'])) {
            return $this->error('Only draft or revision purchase orders can be edited.', 422);
        }

        $oldValues = $order->toApiArray();

        return DB::transaction(function () use ($request, $order, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $order->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['total_price'] = $item['quantity'] * $item['unit_price'];

                    if (!empty($item['id'])) {
                        $poItem = PurchaseOrderItem::where('id', $item['id'])
                            ->where('purchase_order_id', $order->id)
                            ->first();
                        if ($poItem) {
                            $poItem->update($item);
                            $existingIds[] = $poItem->id;
                        }
                    } else {
                        $item['purchase_order_id'] = $order->id;
                        $newItem = PurchaseOrderItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                PurchaseOrderItem::where('purchase_order_id', $order->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                $order->recalculateTotals();
            }

            $order->refresh();
            $order->load(['vendor', 'department', 'requestedBy', 'submittedBy', 'items']);

            AuditLog::record(
                auth()->id(),
                'purchase_order_updated',
                'purchase_order',
                $order->id,
                $oldValues,
                $order->toApiArray()
            );

            return $this->success([
                'purchase_order' => $order->toDetailArray(),
            ], 'Purchase order updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/purchase-orders/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $order = PurchaseOrder::findOrFail($id);

        if (in_array($order->status, ['received', 'partially_received', 'disbursed'])) {
            return $this->error(
                'Cannot delete a purchase order that has been received or disbursed. Cancel it instead.',
                422
            );
        }

        return DB::transaction(function () use ($order, $id) {
            $orderData = $order->toApiArray();
            $order->update(['status' => 'cancelled']);

            AuditLog::record(
                auth()->id(),
                'purchase_order_deleted',
                'purchase_order',
                $id,
                $orderData,
                ['status' => 'cancelled']
            );

            return $this->success(null, 'Purchase order cancelled successfully');
        });
    }

    /**
     * POST /api/v1/procurement/purchase-orders/{id}/submit
     *
     * Submit a draft/revision PO for approval.
     * Auto-generates approval steps based on amount thresholds.
     */
    public function submit(string $id): JsonResponse
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);

        if (!in_array($order->status, ['draft', 'revision'])) {
            return $this->error('Only draft or revision purchase orders can be submitted for approval.', 422);
        }

        if ($order->items()->count() === 0) {
            return $this->error('Cannot submit a purchase order with no items.', 422);
        }

        return DB::transaction(function () use ($order) {
            $oldValues = $order->toApiArray();

            $order->update([
                'status' => 'submitted',
                'submitted_by' => auth()->id(),
            ]);

            $order->generateApprovalSteps();

            $order->refresh();
            $order->load(['vendor', 'department', 'requestedBy', 'submittedBy', 'items', 'approvalSteps']);

            AuditLog::record(
                auth()->id(),
                'purchase_order_submitted',
                'purchase_order',
                $order->id,
                $oldValues,
                $order->toApiArray()
            );

            return $this->success([
                'purchase_order' => $order->toDetailArray(),
            ], 'Purchase order submitted for approval');
        });
    }

    /**
     * GET /api/v1/procurement/purchase-orders/{id}/approval-status
     */
    public function approvalStatus(string $id): JsonResponse
    {
        $order = PurchaseOrder::with('approvalSteps.actedBy')->findOrFail($id);

        return $this->success([
            'purchase_order_id' => $order->id,
            'po_number' => $order->po_number,
            'status' => $order->status,
            'approval_steps' => $order->approvalSteps()
                ->orderBy('step_order')
                ->get()
                ->map->toApiArray(),
        ]);
    }
}
