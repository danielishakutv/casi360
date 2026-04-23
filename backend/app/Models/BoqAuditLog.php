<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoqAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'boq_id',
        'actor_id',
        'actor_name',
        'action',
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

    public function boq()
    {
        return $this->belongsTo(Boq::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /* ----------------------------------------------------------------
     * Factory helper — call this everywhere a BOQ event occurs
     * ---------------------------------------------------------------- */

    public static function write(
        string $boqId,
        string $actorId,
        string $actorName,
        string $action,
        ?string $comments = null
    ): void {
        static::create([
            'boq_id'     => $boqId,
            'actor_id'   => $actorId,
            'actor_name' => $actorName,
            'action'     => $action,
            'comments'   => $comments,
        ]);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id'         => $this->id,
            'action'     => $this->action,
            'actor_id'   => $this->actor_id,
            'actor_name' => $this->actor_name,
            'comments'   => $this->comments,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
