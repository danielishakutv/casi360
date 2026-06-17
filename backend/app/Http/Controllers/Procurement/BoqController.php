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
use App\Services\NotificationService;
use App\Services\Procurement\ApprovalAuthorizer;
use App\Services\Procurement\DocumentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoqController extends Controller
{
    public function __construct(
        private DocumentScopeService $scopeService,
        private ApprovalAuthorizer $authorizer,
    ) {
    }

    /**
     * GET /api/v1/procurement/boq
     */
    public function index(Request $request): JsonResponse
    {
        $query = Boq::with(['approvals', 'budgetHolder']);

        if ($this->scopeService->shouldScope($request->user(), 'procurement.boq.view_all', $request)) {
            $this->scopeService->applyToBoqs($query, $request->user());
        }

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
            // Record the raiser for segregation of duties (v2 §4).
            $data['created_by'] = auth()->id();

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
     *
     * Returns the BOQ + line items + the full activity-log timeline so the
     * detail modal and PDF/CSV exports can render the audit trail without
     * a second round-trip.
     */
    public function show(string $id): JsonResponse
    {
        $boq = Boq::with(['items', 'approvals', 'budgetHolder'])->findOrFail($id);

        $auditLog = BoqAuditLog::where('boq_id', $id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map->toApiArray()
            ->values();

        return $this->success([
            'boq' => array_merge($boq->toDetailArray(), [
                'audit_log' => $auditLog,
            ]),
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

        if (!in_array($boq->status, ['draft', 'revised', 'rejected'], true)) {
            return $this->error('Only BOQs in draft, revised, or rejected status can be submitted.', 422);
        }

        // Stage 1 of the chain routes to the budget holder — block submission
        // until one is set so the chain is never created with a dead stage.
        if (empty($boq->budget_holder_id)) {
            return $this->error('Cannot submit: a budget holder must be set before this BOQ can enter the approval chain.', 422);
        }

        return DB::transaction(function () use ($boq) {
            $fromStatus = $boq->status;

            // Originator-skip (v2 §3.4): if the preparer is the budget holder,
            // auto-skip the Budget Holder stage so the chain opens at Finance.
            $skipBudgetHolder = config('procurement.originator_skip', true)
                && $this->authorizer->boqBudgetHolderIsOriginator($boq);

            $boq->update(['status' => 'pending_approval']);
            $boq->createApprovalChain($skipBudgetHolder);

            // Notify whoever now owns the first open stage.
            NotificationService::boqSubmitted($boq);

            $actor = auth()->user();
            BoqAuditLog::write($boq->id, $actor?->id, $actor?->name ?? 'System', 'submitted');
            AuditLog::record(
                $actor?->id,
                'boq_submitted',
                'boq',
                $boq->id,
                ['status' => $fromStatus],
                ['status' => 'pending_approval']
            );

            if ($skipBudgetHolder) {
                BoqAuditLog::write(
                    $boq->id,
                    $actor?->id,
                    'System (originator auto-skip)',
                    'budget_holder_auto_skip',
                    'Budget Holder stage auto-skipped: preparer is the budget holder (segregation of duties).'
                );
            }

            $boq->refresh();
            $boq->load(['items', 'approvals']);

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
        $boq = Boq::with('approvals')->findOrFail($id);

        if (!in_array($boq->status, ['pending_approval', 'submitted'], true)) {
            return $this->error('Only BOQs awaiting approval can be approved, revised, or rejected.', 422);
        }

        $user      = $request->user();
        $validated = $request->validated();
        $action    = $validated['action'];
        $comments  = $validated['comments'] ?? null;

        $activeApproval = $boq->approvals->where('status', 'pending')->sortBy('stage_order')->first();
        if (!$activeApproval) {
            return $this->error('This approval stage is not currently awaiting action.', 422);
        }

        // Stage ownership (Budget Holder / dept-manager rules).
        $auth = $this->authorizer->canActOnBoqStage($user, $boq, $activeApproval->stage);
        if (!$auth['allowed']) {
            return $this->error($auth['reason'] ?? 'You are not authorised to approve at the current stage.', 403);
        }

        // Segregation of duties (v2 §4): the raiser can't approve, and no one
        // may act on more than one stage of the same BOQ.
        $sod = $this->authorizer->passesSegregation(
            $user,
            [$boq->created_by],
            $boq->approvals->pluck('actor_id')->all()
        );
        if (!$sod['allowed']) {
            return $this->error($sod['reason'], 403);
        }

        return DB::transaction(function () use ($boq, $activeApproval, $action, $comments, $user) {
            $now        = now();
            $fromStatus = $boq->status;
            $stageLabel = $activeApproval->stage_label;

            $actorData = [
                'actor_id'       => $user->id,
                'actor_name'     => $user->name,
                'actor_position' => $user->department ?? null,
                'comments'       => $comments,
                'decided_at'     => $now,
            ];

            switch ($action) {
                case 'approve':
                    $activeApproval->update(array_merge($actorData, ['status' => 'approved']));

                    // Mirror the approval into the signoffs array (keyed by stage)
                    // so the BOQ preview/PDF sign-off blocks stay populated.
                    $signoffs = is_array($boq->signoffs) ? $boq->signoffs : [];
                    $signoffs = array_values(array_filter($signoffs, fn ($s) => ($s['type'] ?? null) !== $activeApproval->stage));
                    $signoffs[] = [
                        'type'     => $activeApproval->stage,
                        'name'     => $user->name,
                        'position' => $user->department ?: ($user->role ? ucwords(str_replace('_', ' ', $user->role)) : null),
                        'email'    => $user->email,
                        'date'     => $now->toDateString(),
                    ];
                    $boqUpdates = ['signoffs' => $signoffs];

                    $next = $boq->approvals->where('status', 'waiting')->sortBy('stage_order')->first();
                    if ($next) {
                        $next->update(['status' => 'pending', 'updated_at' => $now]);
                    } else {
                        $boqUpdates['status'] = 'approved';
                    }
                    $boq->update($boqUpdates);
                    break;

                case 'revision':
                    $activeApproval->update(array_merge($actorData, ['status' => 'revision']));
                    $boq->update(['status' => 'revised']);
                    break;

                case 'reject':
                    $activeApproval->update(array_merge($actorData, ['status' => 'rejected']));
                    $boq->update(['status' => 'rejected']);
                    break;
            }

            NotificationService::boqDecided($boq, $action);

            $auditAction = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'revision');
            BoqAuditLog::write($boq->id, $user->id, $user->name, $auditAction, $comments);
            AuditLog::record(
                $user->id,
                "boq_{$action}",
                'boq',
                $boq->id,
                ['status' => $fromStatus],
                ['status' => $boq->status, 'stage' => $activeApproval->stage, 'comments' => $comments]
            );

            $boq->refresh();
            $boq->load(['items', 'approvals']);

            $message = match ($action) {
                'approve'  => "BOQ approved by {$stageLabel}",
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
