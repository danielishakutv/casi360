<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcessBoqApprovalRequest;
use App\Http\Requests\Procurement\StoreBoqRequest;
use App\Http\Requests\Procurement\UpdateBoqRequest;
use App\Models\AuditLog;
use App\Models\Boq;
use App\Models\BoqAuditLog;
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

            $actor = auth()->user();
            AuditLog::record(
                $actor?->id,
                'boq_created',
                'boq',
                $boq->id,
                null,
                $boq->toApiArray()
            );
            BoqAuditLog::write(
                $boq->id,
                $actor?->id,
                $actor?->name ?? 'System',
                'created'
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

            $actor = auth()->user();
            AuditLog::record(
                $actor?->id,
                'boq_updated',
                'boq',
                $boq->id,
                $oldValues,
                $boq->toApiArray()
            );
            BoqAuditLog::write(
                $boq->id,
                $actor?->id,
                $actor?->name ?? 'System',
                'updated'
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

    /**
     * POST /api/v1/procurement/boq/{id}/submit
     *
     * Move a BOQ from draft|revised → submitted so Procurement can act on it.
     * The route middleware already enforces procurement.boq.edit, so owners
     * and permitted editors are the only ones who reach this method.
     */
    public function submit(string $id): JsonResponse
    {
        $boq = Boq::findOrFail($id);

        if (!in_array($boq->status, ['draft', 'revised'], true)) {
            return $this->error('Only BOQs in draft or revised status can be submitted.', 422);
        }

        return DB::transaction(function () use ($boq) {
            $fromStatus = $boq->status;
            $boq->update(['status' => 'submitted']);

            $actor = auth()->user();
            BoqAuditLog::write(
                $boq->id,
                $actor?->id,
                $actor?->name ?? 'System',
                'submitted'
            );
            AuditLog::record(
                $actor?->id,
                'boq_submitted',
                'boq',
                $boq->id,
                ['status' => $fromStatus],
                ['status' => 'submitted']
            );

            $boq->refresh();
            $boq->load('items');

            return $this->success([
                'boq' => $boq->toDetailArray(),
            ], 'BOQ submitted for approval.');
        });
    }

    /**
     * PATCH /api/v1/procurement/boq/{id}/approval
     *
     * Route middleware enforces procurement.boq.approve. We still check the
     * status precondition so only submitted BOQs can be acted on.
     *
     * Actions:
     *   - approve  → status = approved
     *   - revision → status = revised (comments required)
     *   - reject   → status = rejected (comments required)
     */
    public function approval(ProcessBoqApprovalRequest $request, string $id): JsonResponse
    {
        $boq = Boq::findOrFail($id);

        if ($boq->status !== 'submitted') {
            return $this->error('Only submitted BOQs can be approved, revised, or rejected.', 422);
        }

        $validated = $request->validated();
        $action    = $validated['action'];
        $comments  = $validated['comments'] ?? null;

        $nextStatus = match ($action) {
            'approve'  => 'approved',
            'revision' => 'revised',
            'reject'   => 'rejected',
        };

        // Normalise verb → past-tense audit action so the timeline reads
        // "approved / revision / rejected" (matches the spec exactly).
        $auditAction = match ($action) {
            'approve'  => 'approved',
            'revision' => 'revision',
            'reject'   => 'rejected',
        };

        return DB::transaction(function () use ($boq, $action, $auditAction, $comments, $nextStatus) {
            $fromStatus = $boq->status;
            $boq->update(['status' => $nextStatus]);

            $actor = auth()->user();
            BoqAuditLog::write(
                $boq->id,
                $actor?->id,
                $actor?->name ?? 'System',
                $auditAction,
                $comments
            );
            AuditLog::record(
                $actor?->id,
                "boq_{$action}",
                'boq',
                $boq->id,
                ['status' => $fromStatus],
                ['status' => $nextStatus, 'comments' => $comments]
            );

            $boq->refresh();
            $boq->load('items');

            $message = match ($action) {
                'approve'  => 'BOQ approved successfully.',
                'revision' => 'BOQ sent back for revision.',
                'reject'   => 'BOQ rejected.',
            };

            return $this->success([
                'boq' => $boq->toDetailArray(),
            ], $message);
        });
    }

    /**
     * GET /api/v1/procurement/boq/{id}/audit-log
     *
     * Ordered oldest-first so the UI can play the trail as a timeline.
     */
    public function auditLog(string $id): JsonResponse
    {
        // findOrFail confirms the BOQ exists and the viewer has view permission
        // (enforced at the route level before reaching here).
        Boq::findOrFail($id);

        $entries = BoqAuditLog::where('boq_id', $id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->success([
            'audit_log' => $entries->map->toApiArray()->values(),
        ]);
    }
}
