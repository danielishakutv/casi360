<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'department_id',
        'status',
    ];

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeGeneral($query)
    {
        return $query->where('type', 'general');
    }

    public function scopeDepartment($query)
    {
        return $query->where('type', 'department');
    }

    /**
     * Forums accessible to a user based on their department and role.
     */
    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->role === 'super_admin') {
            return $query;
        }

        $departmentId = $user->employee?->department_id;

        return $query->where(function ($q) use ($departmentId) {
            $q->where('type', 'general');
            if ($departmentId) {
                $q->orWhere(function ($sub) use ($departmentId) {
                    $sub->where('type', 'department')
                        ->where('department_id', $departmentId);
                });
            }
        });
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function messages()
    {
        return $this->hasMany(ForumMessage::class);
    }

    /* ----------------------------------------------------------------
     * Accessors
     * ---------------------------------------------------------------- */

    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }

    public function getLastActivityAtAttribute(): ?string
    {
        return $this->messages()->latest()->first()?->created_at?->toISOString();
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'status' => $this->status,
            'message_count' => $this->message_count,
            'last_activity_at' => $this->last_activity_at,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
