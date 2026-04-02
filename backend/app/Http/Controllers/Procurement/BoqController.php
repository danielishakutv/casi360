<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreBoqRequest;
use App\Http\Requests\Procurement\UpdateBoqRequest;
use App\Models\AuditLog;
use App\Models\Boq;
use App\Models\BoqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoqController extends Controller
{
    /**
     * GET /api/v1/procurement/boq
     */
    public function index(Request $request): JsonResponse
    {
        $query = Boq::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('pr_reference')) {
            $query->where('pr_reference', $request->pr_reference);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('boq_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('prepared_by', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['boq_number', 'title', 'status', 'date', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        if ($perPage == 0) {
            $boqs = $query->get();
            return $this->success([
                'boqs' => $boqs->map->toApiArray(),
                'meta' => ['total' => $boqs->count()],
            ]);
        }

        $paginated = $query->paginate($perPage);

        return $this->success([
            'boqs' => collect($paginated->items())->map->toApiArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/procurement/boq
     */
    public function store(StoreBoqRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['boq_number'] = Boq::generateBoqNumber();
            $data['status'] = $data['status'] ?? 'draft';

            $boq = Boq::create($data);

            foreach ($items as $item) {
                $item['boq_id'] = $boq->id;
                $item['total'] = $item['quantity'] * $item['unit_rate'];
                BoqItem::create($item);
            }

            $boq->refresh();
            $boq->load('items');

            AuditLog::record(
                auth()->id(),
                'boq_created',
                'boq',
                $boq->id,
                null,
                $boq->toApiArray()
            );

            return $this->success([
                'boq' => $boq->toDetailArray(),
            ], 'BOQ created successfully', 201);
        });
    }

    /**
     * GET /api/v1/procurement/boq/{id}
     */
    public function show(string $id): JsonResponse
    {
        $boq = Boq::with('items')->findOrFail($id);

        return $this->success([
            'boq' => $boq->toDetailArray(),
        ]);
    }

    /**
     * PATCH /api/v1/procurement/boq/{id}
     */
    public function update(UpdateBoqRequest $request, string $id): JsonResponse
    {
        $boq = Boq::with('items')->findOrFail($id);

        if (!in_array($boq->status, ['draft', 'revised'])) {
            return $this->error('Only draft or revised BOQs can be edited.', 422);
        }

        $oldValues = $boq->toApiArray();

        return DB::transaction(function () use ($request, $boq, $oldValues) {
            $data = $request->validated();
            $items = $data['items'] ?? null;
            unset($data['items']);

            $boq->update($data);

            if ($items !== null) {
                $existingIds = [];

                foreach ($items as $item) {
                    $item['total'] = $item['quantity'] * $item['unit_rate'];

                    if (!empty($item['id'])) {
                        $boqItem = BoqItem::where('id', $item['id'])
                            ->where('boq_id', $boq->id)
                            ->first();
                        if ($boqItem) {
                            $boqItem->update($item);
                            $existingIds[] = $boqItem->id;
                        }
                    } else {
                        $item['boq_id'] = $boq->id;
                        $newItem = BoqItem::create($item);
                        $existingIds[] = $newItem->id;
                    }
                }

                BoqItem::where('boq_id', $boq->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }

            $boq->refresh();
            $boq->load('items');

            AuditLog::record(
                auth()->id(),
                'boq_updated',
                'boq',
                $boq->id,
                $oldValues,
                $boq->toApiArray()
            );

            return $this->success([
                'boq' => $boq->toDetailArray(),
            ], 'BOQ updated successfully');
        });
    }

    /**
     * DELETE /api/v1/procurement/boq/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $boq = Boq::findOrFail($id);

        if ($boq->status === 'approved') {
            return $this->error('Cannot delete an approved BOQ.', 422);
        }

        return DB::transaction(function () use ($boq, $id) {
            $boqData = $boq->toApiArray();
            $boq->items()->delete();
            $boq->delete();

            AuditLog::record(
                auth()->id(),
                'boq_deleted',
                'boq',
                $id,
                $boqData,
                null
            );

            return $this->success(null, 'BOQ deleted successfully');
        });
    }
}
