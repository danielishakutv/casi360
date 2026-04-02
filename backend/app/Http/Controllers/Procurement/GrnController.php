<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreGrnRequest;
use App\Http\Requests\Procurement\UpdateGrnRequest;
use App\Models\AuditLog;
use App\Models\Grn;
use App\Models\GrnItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
{
    /**
     * GET /api/v1/procurement/grn
     */
    public function index(Request $request): JsonResponse
    {
        $query = Grn::with('vendor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('po_reference')) {
            $query->where('po_reference', $request->po_reference);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                  ->orWhere('delivery_note_no', 'like', "%{$search}%")
                  ->orWhere('received_by', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['grn_number', 'status', 'received_date', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $grns = $query->get();
            return $this->success([
                'grns' => $grns->map->toApiArray(),
                'meta' => ['total' => $grns->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'grns' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/grn
     */
    public function store(StoreGrnRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['grn_number'] = Grn::generateGrnNumber();
            $data['status'] = $data['status'] ?? 'draft';

            $grn = Grn::create($data);

            foreach ($items as $item) {
                $item['grn_id'] = $grn->id;
                $item['accepted_qty'] = $item['accepted_qty'] ?? $item['received_qty'];
                $item['rejected_qty'] = $item['rejected_qty'] ?? 0;
                GrnItem::create($item);
            }

            $grn->refresh();
            $grn->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'grn_created',
                'grn',
                $grn->id,
                null,
                $grn->toApiArray()
            );

            return $this->success([
                'grn' => $grn->toDetailArray(),
            ], 'GRN created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/grn/{id}
     */
    public function show(string $id): JsonResponse
    {
        $grn = Grn::with(['vendor', 'items'])->findOrFail($id);

        return $this->success([
            'grn' => $grn->toDetailArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/grn/{id}
     */
    public function update(UpdateGrnRequest $request, string $id): JsonResponse
    {
        $grn = Grn::with(['vendor', 'items'])->findOrFail($id);

        if (!in_array($grn->status, ['draft', 'inspected', 'partial'])) {
            return $this->error('Only draft, inspected or partial GRNs can be edited.', 422);
        }

        $oldValues = $grn->toApiArray();

        return DB::transaction(function () use ($request, $grn, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $grn->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['accepted_qty'] = $item['accepted_qty'] ?? $item['received_qty'];
                    $item['rejected_qty'] = $item['rejected_qty'] ?? 0;

                    if (!empty($item['id'])) {
                        $grnItem = GrnItem::where('id', $item['id'])
                            ->where('grn_id', $grn->id)
                            ->first();
                        if ($grnItem) {
                            $grnItem->update($item);
                            $existingIds[] = $grnItem->id;
                        }
                    } else {
                        $item['grn_id'] = $grn->id;
                        $newItem = GrnItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                GrnItem::where('grn_id', $grn->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            $grn->refresh();
            $grn->load(['vendor', 'items']);

            AuditLog::record(
                auth()->id(),
                'grn_updated',
                'grn',
                $grn->id,
                $oldValues,
                $grn->toApiArray()
            );

            return $this->success([
                'grn' => $grn->toDetailArray(),
            ], 'GRN updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/grn/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $grn = Grn::findOrFail($id);

        if ($grn->status === 'accepted') {
            return $this->error('Cannot delete an accepted GRN.', 422);
        }

        return DB::transaction(function () use ($grn, $id) {
            $grnData = $grn->toApiArray();
            $grn->items()->delete();
            $grn->delete();

            AuditLog::record(
                auth()->id(),
                'grn_deleted',
                'grn',
                $id,
                $grnData,
                null
            );

            return $this->success(null, 'GRN deleted successfully');
        });
    }
}
