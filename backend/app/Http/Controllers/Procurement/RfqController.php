<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreRfqRequest;
use App\Http\Requests\Procurement\UpdateRfqRequest;
use App\Models\AuditLog;
use App\Models\Rfq;
use App\Models\RfqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    /**
     * GET /api/v1/procurement/rfq
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rfq::with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rfq_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['rfq_number', 'title', 'status', 'issue_date', 'deadline', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $rfqs = $query->get();
            return $this->success([
                'rfqs' => $rfqs->map->toApiArray(),
                'meta' => ['total' => $rfqs->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'rfqs' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/rfq
     */
    public function store(StoreRfqRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['rfq_number'] = Rfq::generateRfqNumber();
            $data['status'] = $data['status'] ?? 'draft';

            $rfq = Rfq::create($data);

            foreach ($items as $item) {
                $item['rfq_id'] = $rfq->id;
                if (isset($item['unit_cost']) && isset($item['quantity'])) {
                    $item['total'] = $item['quantity'] * $item['unit_cost'];
                }
                if (isset($item['vendor_unit_price']) && isset($item['quantity'])) {
                    $item['vendor_total'] = $item['quantity'] * $item['vendor_unit_price'];
                }
                RfqItem::create($item);
            }

            $rfq->refresh();
            $rfq->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfq_created',
                'rfq',
                $rfq->id,
                null,
                $rfq->toApiArray()
            );

            return $this->success([
                'rfq' => $rfq->toDetailArray(),
            ], 'RFQ created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/rfq/{id}
     */
    public function show(string $id): JsonResponse
    {
        $rfq = Rfq::with(['vendor', 'items'])->findOrFail($id);

        return $this->success([
            'rfq' => $rfq->toDetailArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/rfq/{id}
     */
    public function update(UpdateRfqRequest $request, string $id): JsonResponse
    {
        $rfq = Rfq::with(['vendor', 'items'])->findOrFail($id);

        if (!in_array($rfq->status, ['draft', 'sent', 'received'])) {
            return $this->error('Only draft, sent or received RFQs can be edited.', 422);
        }

        $oldValues = $rfq->toApiArray();

        return DB::transaction(function () use ($request, $rfq, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $rfq->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    if (isset($item['unit_cost']) && isset($item['quantity'])) {
                        $item['total'] = $item['quantity'] * $item['unit_cost'];
                    }
                    if (isset($item['vendor_unit_price']) && isset($item['quantity'])) {
                        $item['vendor_total'] = $item['quantity'] * $item['vendor_unit_price'];
                    }

                    if (!empty($item['id'])) {
                        $rfqItem = RfqItem::where('id', $item['id'])
                            ->where('rfq_id', $rfq->id)
                            ->first();
                        if ($rfqItem) {
                            $rfqItem->update($item);
                            $existingIds[] = $rfqItem->id;
                        }
                    } else {
                        $item['rfq_id'] = $rfq->id;
                        $newItem = RfqItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                RfqItem::where('rfq_id', $rfq->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            $rfq->refresh();
            $rfq->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'rfq_updated',
                'rfq',
                $rfq->id,
                $oldValues,
                $rfq->toApiArray()
            );

            return $this->success([
                'rfq' => $rfq->toDetailArray(),
            ], 'RFQ updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/rfq/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $rfq = Rfq::findOrFail($id);

        if ($rfq->status === 'evaluated') {
            return $this->error('Cannot delete an evaluated RFQ.', 422);
        }

        return DB::transaction(function () use ($rfq, $id) {
            $rfqData = $rfq->toApiArray();
            $rfq->update(['status' => 'cancelled']);

            AuditLog::record(
                auth()->id(),
                'rfq_deleted',
                'rfq',
                $id,
                $rfqData,
                ['status' => 'cancelled']
            );

            return $this->success(null, 'RFQ cancelled successfully');
        });
    }
}
