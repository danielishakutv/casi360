<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'requisition_id',
        'actor_id',
        'actor_name',
        'action',
        'from_status',
        'to_status',
        'stage',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /* ----------------------------------------------------------------
     * Factory helper — call this everywhere an event occurs
     * ---------------------------------------------------------------- */

    public static function write(
        string $requisitionId,
        string $actorId,
        string $actorName,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $stage = null,
        ?string $comments = null
    ): void {
        static::create([
            'requisition_id' => $requisitionId,
            'actor_id'       => $actorId,
            'actor_name'     => $actorName,
            'action'         => $action,
            'from_status'    => $fromStatus,
            'to_status'      => $toStatus,
            'stage'          => $stage,
            'comments'       => $comments,
        ]);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id'          => $this->id,
            'action'      => $this->action,
            'from_status' => $this->from_status,
            'to_status'   => $this->to_status,
            'stage'       => $this->stage,
            'actor_name'  => $this->actor_name,
            'comments'    => $this->comments,
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
