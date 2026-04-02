<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivity extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'target_date',
        'status',
        'completion_percentage',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_date' => 'date',
        'completion_percentage' => 'integer',
    ];

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'target_date' => $this->target_date?->toDateString(),
            'status' => $this->status,
            'completion_percentage' => $this->completion_percentage,
            'sort_order' => $this->sort_order,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
