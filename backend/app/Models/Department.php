<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'head',
        'description',
        'color',
        'status',
    ];

    /**
     * Boot: normalise `code` to uppercase whenever it's set.
     */
    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            if (!empty($department->code)) {
                $department->code = strtoupper(trim($department->code));
            }
        });
    }

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /* ----------------------------------------------------------------
     * Accessors
     * ---------------------------------------------------------------- */

    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->where('status', '!=', 'terminated')->count();
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'head' => $this->head,
            'employee_count' => $this->employee_count,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
