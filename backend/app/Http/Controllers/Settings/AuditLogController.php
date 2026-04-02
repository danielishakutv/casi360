<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 25), 100);

        $paginated = $query->paginate($perPage);

        return $this->success([
            'audit_logs' => collect($paginated->items())->map(function (AuditLog $log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user?->name ?? 'System',
                    'user_id' => $log->user_id,
                    'action' => $log->action,
                    'target' => $log->entity_type . ($log->entity_id ? " ({$log->entity_id})" : ''),
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'ip_address' => $log->ip_address,
                    'timestamp' => $log->created_at?->toIso8601String(),
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                ];
            }),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
