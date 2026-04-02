<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'staff_id',
        'name',
        'email',
        'phone',
        'department_id',
        'designation_id',
        'manager',
        'status',
        'join_date',
        'termination_date',
        'salary',
        'avatar',
        'address',
        'gender',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'termination_date' => 'date',
            'date_of_birth' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'designation_id' => $this->designation_id,
            'position' => $this->designation?->title,
            'manager' => $this->manager,
            'status' => $this->status,
            'join_date' => $this->join_date?->toDateString(),
            'termination_date' => $this->termination_date?->toDateString(),
            'salary' => (float) $this->salary,
            'avatar' => $this->avatar,
            'address' => $this->address,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
