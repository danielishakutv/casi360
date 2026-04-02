<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'grn_id',
        'description',
        'ordered_qty',
        'received_qty',
        'quality_status',
        'accepted_qty',
        'rejected_qty',
        'rejection_reason',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'grn_id' => $this->grn_id,
            'description' => $this->description,
            'ordered_qty' => (int) $this->ordered_qty,
            'received_qty' => (int) $this->received_qty,
            'quality_status' => $this->quality_status,
            'accepted_qty' => (int) $this->accepted_qty,
            'rejected_qty' => (int) $this->rejected_qty,
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
