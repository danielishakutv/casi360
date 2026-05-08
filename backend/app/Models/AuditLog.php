<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create an audit log entry.
     */
    public static function record(
        ?string $userId,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Build a chronological activity-log trail for a procurement document
     * (or anything else recorded with `AuditLog::record`). Used by detail
     * endpoints that don't have a dedicated audit-log table — e.g. RFQ,
     * Purchase Order, GRN, Invoice, RFP — so the UI and PDF/CSV exports
     * can render the same timeline shape that BoqAuditLog produces.
     *
     * Action strings are stripped of their entity prefix so a row like
     *   ('rfq_submitted', entity_type=rfq) becomes action='submitted'.
     * That way the frontend renders 'Created / Submitted / Approved'
     * uniformly across doc types.
     */
    public static function trailFor(string $entityType, string $entityId): array
    {
        return self::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (self $log) use ($entityType) {
                $action = $log->action;
                $prefix = $entityType . '_';
                if (str_starts_with($action, $prefix)) {
                    $action = substr($action, strlen($prefix));
                }

                $comments = null;
                $meta = is_array($log->metadata) ? $log->metadata : [];
                if (isset($meta['comments'])) {
                    $comments = $meta['comments'];
                } elseif (is_array($log->new_values) && isset($log->new_values['comments'])) {
                    $comments = $log->new_values['comments'];
                }

                return [
                    'id'         => $log->id,
                    'action'     => $action,
                    'actor_id'   => $log->user_id,
                    'actor_name' => $log->user?->name ?? 'System',
                    'comments'   => $comments,
                    'created_at' => $log->created_at?->toISOString(),
                ];
            })
            ->values()
            ->toArray();
    }
}
