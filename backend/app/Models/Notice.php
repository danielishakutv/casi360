<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'author_id',
        'title',
        'body',
        'priority',
        'status',
        'publish_date',
        'expiry_date',
        'is_pinned',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'expiry_date' => 'date',
        'is_pinned' => 'boolean',
    ];

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('publish_date')
                  ->orWhere('publish_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now()->toDateString());
            });
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Notices visible to a given user based on audience targeting.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->role === 'super_admin') {
            return $query;
        }

        $departmentId = $user->employee?->department_id;
        $userRole = $user->role;

        return $query->where(function ($q) use ($departmentId, $userRole) {
            $q->whereHas('audiences', function ($aq) use ($departmentId, $userRole) {
                $aq->where('audience_type', 'all')
                   ->orWhere(function ($sub) use ($departmentId) {
                       $sub->where('audience_type', 'department')
                           ->where('audience_id', $departmentId);
                   })
                   ->orWhere(function ($sub) use ($userRole) {
                       $sub->where('audience_type', 'role')
                           ->where('audience_role', $userRole);
                   });
            });
        });
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function audiences()
    {
        return $this->hasMany(NoticeAudience::class);
    }

    public function reads()
    {
        return $this->hasMany(NoticeRead::class);
    }

    /* ----------------------------------------------------------------
     * Accessors
     * ---------------------------------------------------------------- */

    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    /* ----------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    public function isReadBy(string $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'author_id' => $this->author_id,
            'author_name' => $this->author?->name,
            'title' => $this->title,
            'body' => $this->body,
            'priority' => $this->priority,
            'status' => $this->status,
            'publish_date' => $this->publish_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_pinned' => $this->is_pinned,
            'read_count' => $this->read_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public function toDetailArray(string $currentUserId = null): array
    {
        return array_merge($this->toApiArray(), [
            'audiences' => $this->audiences->map->toApiArray()->toArray(),
            'is_read' => $currentUserId ? $this->isReadBy($currentUserId) : null,
        ]);
    }
}
