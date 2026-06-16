<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per stage of a Payment Request (RFP) approval chain:
 * Programme Manager → Finance → Final Approver (v2 §3.3). Mirrors
 * RequisitionApproval.
 */
class RfpApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'rfp_id',
        'stage',
        'stage_order',
        'stage_label',
        'status',
        'actor_id',
        'actor_name',
        'actor_position',
        'comments',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'stage_order' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function rfp()
    {
        return $this->belongsTo(Rfp::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function toApiArray(bool $detailed = false): array
    {
        $base = [
            'stage'       => $this->stage,
            'stage_label' => $this->stage_label,
            'stage_order' => $this->stage_order,
            'status'      => $this->status,
            'actor_name'  => $this->actor_name,
            'decided_at'  => $this->decided_at?->toISOString(),
        ];

        if ($detailed) {
            $base['actor_id']       = $this->actor_id;
            $base['actor_position'] = $this->actor_position;
            $base['comments']       = $this->comments;
        }

        return $base;
    }
}
