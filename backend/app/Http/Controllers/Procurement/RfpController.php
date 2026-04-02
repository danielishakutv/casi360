<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreRfpRequest;
use App\Http\Requests\Procurement\UpdateRfpRequest;
use App\Models\AuditLog;
use App\Models\Rfp;
use App\Models\RfpItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RfpController extends Controller
{
    /**
     * GET /api/v1/procurement/rfp
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rfp::with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('po_reference')) {
            $query->where('po_reference', $request->po_reference);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rfp_number', 'like', "%{$search}%")
                  ->orWhere('po_reference', 'like', "%{$search}%")
                  ->orWhere('grn_reference', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['rfp_number', 'status', 'payment_date', 'total_amount', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $rfps = $query->get();
            return $this->success([
                'rfps' => $rfps->map->toApiArray(),
                'meta' => ['total' => $rfps->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'rfps' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/rfp
     */
    public function store(StoreRfpRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['rfp_number'] = Rfp::generateRfpNumber();
            $data['status'] = $data['status'] ?? 'draft';

            // If line items provided, compute subtotal from them
            if (!empty($items)) {
                $subtotal = 0;
                foreach ($items as &$item) {
                    $item['total'] = $item['quantity'] * $item['unit_cost'];
                    $subtotal += $item['total'];
                }
                unset($item);
                $data['subtotal'] = $subtotal;
            }

            // Auto-calculate tax
            $subtotal = (float) ($data['subtotal'] ?? 0);
            $taxRate = (float) ($data['tax_rate'] ?? 5);
            $data['tax_rate'] = $taxRate;
            $data['tax_amount'] = round($subtotal * ($taxRate / 100), 2);
            $data['total_amount'] = $subtotal + $data['tax_amount'];

            $rfp = Rfp::create($data);

            foreach ($items as $item) {
                $item['rfp_id'] = $rfp->id;
                RfpItem::create($item);
            }

            $rfp->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfp_created',
                'rfp',
                $rfp->id,
                null,
                $rfp->toApiArray()
            );

            return $this->success([
                'rfp' => $rfp->toDetailArray(),
            ], 'RFP created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/rfp/{id}
     */
    public function show(string $id): JsonResponse
    {
        $rfp = Rfp::with(['vendor', 'items'])->findOrFail($id);

        return $this->success([
            'rfp' => $rfp->toDetailArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/rfp/{id}
     */
    public function update(UpdateRfpRequest $request, string $id): JsonResponse
    {
        $rfp = Rfp::with(['vendor', 'items'])->findOrFail($id);

        if (!in_array($rfp->status, ['draft', 'pending', 'submitted'])) {
            return $this->error('Only draft, pending or submitted RFPs can be edited.', 422);
        }

        $oldValues = $rfp->toApiArray();

        return DB::transaction(function () use ($request, $rfp, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $rfp->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['total'] = $item['quantity'] * $item['unit_cost'];

                    if (!empty($item['id'])) {
                        $rfpItem = RfpItem::where('id', $item['id'])
                            ->where('rfp_id', $rfp->id)
                            ->first();
                        if ($rfpItem) {
                            $rfpItem->update($item);
                            $existingIds[] = $rfpItem->id;
                        }
                    } else {
                        $item['rfp_id'] = $rfp->id;
                        $newItem = RfpItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                RfpItem::where('rfp_id', $rfp->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            // Recalculate totals
            $rfp->refresh();
            $rfp->recalculateTotals();

            $rfp->refresh();
            $rfp->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfp_updated',
                'rfp',
                $rfp->id,
                $oldValues,
                $rfp->toApiArray()
            );

            return $this->success([
                'rfp' => $rfp->toDetailArray(),
            ], 'RFP updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/rfp/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $rfp = Rfp::findOrFail($id);

        if ($rfp->status === 'paid') {
            return $this->error('Cannot delete a paid RFP.', 422);
        }

        return DB::transaction(function () use ($rfp, $id) {
            $rfpData = $rfp->toApiArray();
            $rfp->update(['status' => 'rejected']);

            AuditLog::record(
                auth()->id(),
                'rfp_deleted',
                'rfp',
                $id,
                $rfpData,
                ['status' => 'rejected']
            );

            return $this->success(null, 'RFP rejected/deleted successfully');
        });
    }
}
