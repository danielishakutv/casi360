<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\Boq;
use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Rfp;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/auth/session
     * 
     * Get current authenticated user session data.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('Not authenticated', 401);
        }

        return $this->success([
            'authenticated' => true,
            'user' => $user->toAuthArray(),
        ]);
    }

    /**
     * GET /api/v1/auth/profile
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $request->user()->toAuthArray(),
        ]);
    }

    /**
     * PATCH /api/v1/auth/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $oldValues = $user->only(['name', 'phone', 'department']);

        return DB::transaction(function () use ($request, $user, $oldValues) {
            $user->update($request->only(['name', 'phone', 'department']));

            AuditLog::record(
                $user->id,
                'profile_updated',
                'user',
                $user->id,
                $oldValues,
                $user->only(['name', 'phone', 'department'])
            );

            return $this->success([
                'user' => $user->fresh()->toAuthArray(),
            ], 'Profile updated successfully');
        });
    }

    /**
     * GET /api/v1/auth/activity
     *
     * Unified recent activity feed for the current user — every procurement
     * document they created, edited, deleted, approved, rejected, or
     * otherwise touched, in reverse-chronological order.
     *
     * Backed by the global audit_logs table (one row per write across the
     * six document types) joined to each entity for display details.
     * Bounded by ?per_page (default 10, max 50) and supports ?page.
     */
    public function activity(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);

        $entityTypes = ['requisition', 'purchase_order', 'boq', 'rfq', 'rfp', 'grn'];

        $paginated = AuditLog::where('user_id', $user->id)
            ->whereIn('entity_type', $entityTypes)
            ->whereNotNull('entity_id')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $logs = collect($paginated->items());

        // Batch-fetch the referenced entities so we don't issue one query per row.
        $idsByType = $logs->groupBy('entity_type')->map(fn ($group) => $group->pluck('entity_id')->unique()->all());

        $entityIndex = [
            'requisition'    => isset($idsByType['requisition'])    ? Requisition::whereIn('id', $idsByType['requisition'])->get()->keyBy('id')    : collect(),
            'purchase_order' => isset($idsByType['purchase_order']) ? PurchaseOrder::whereIn('id', $idsByType['purchase_order'])->get()->keyBy('id') : collect(),
            'boq'            => isset($idsByType['boq'])            ? Boq::whereIn('id', $idsByType['boq'])->get()->keyBy('id')                     : collect(),
            'rfq'            => isset($idsByType['rfq'])            ? Rfq::whereIn('id', $idsByType['rfq'])->get()->keyBy('id')                     : collect(),
            'rfp'            => isset($idsByType['rfp'])            ? Rfp::whereIn('id', $idsByType['rfp'])->get()->keyBy('id')                     : collect(),
            'grn'            => isset($idsByType['grn'])            ? Grn::whereIn('id', $idsByType['grn'])->get()->keyBy('id')                     : collect(),
        ];

        $activity = $logs->map(fn ($log) => $this->formatActivityItem($log, $entityIndex))->values();

        return $this->success([
            'activity' => $activity,
            'meta'     => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * Shape one audit-log row into a display-ready activity item. Resolves
     * the linked entity (if it still exists) for number/title/status; falls
     * back gracefully when the entity has been hard-deleted.
     */
    private function formatActivityItem(AuditLog $log, array $entityIndex): array
    {
        $entity = $entityIndex[$log->entity_type][$log->entity_id] ?? null;

        // Each entity exposes a different "human label" column. Map them here
        // so the frontend renders one consistent feed.
        $number = null;
        $title  = null;
        $status = null;
        if ($entity) {
            $status = $entity->status ?? null;
            switch ($log->entity_type) {
                case 'requisition':
                    $number = $entity->requisition_number;
                    $title  = $entity->title;
                    break;
                case 'purchase_order':
                    $number = $entity->po_number;
                    $title  = $entity->notes ?: null;
                    break;
                case 'boq':
                    $number = $entity->boq_number;
                    $title  = $entity->title;
                    break;
                case 'rfq':
                    $number = $entity->rfq_number;
                    $title  = $entity->title;
                    break;
                case 'rfp':
                    $number = $entity->rfp_number;
                    $title  = $entity->payee ?: null;
                    break;
                case 'grn':
                    $number = $entity->grn_number;
                    $title  = $entity->delivery_note_no ?: null;
                    break;
            }
        }

        return [
            'id'          => $log->id,
            'entity_type' => $log->entity_type,
            'entity_id'   => $log->entity_id,
            'action'      => $log->action,
            'occurred_at' => $log->created_at?->toISOString(),
            'number'      => $number,
            'title'       => $title,
            'status'      => $status,
            'deleted'     => $entity === null,
        ];
    }

    /**
     * DELETE /api/v1/auth/account
     *
     * Soft-deactivate account (not hard delete for audit trail).
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super admins cannot delete their own account
        if ($user->is_super_admin) {
            return $this->error('Super admin accounts cannot be self-deleted.', 403);
        }

        return DB::transaction(function () use ($request, $user) {
            $user->update(['status' => 'inactive']);

            AuditLog::record(
                $user->id,
                'account_deactivated',
                'user',
                $user->id
            );

            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $this->success(null, 'Account deactivated successfully');
        });
    }
}
