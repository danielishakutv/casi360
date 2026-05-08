<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ----------------------------------------------------------------
     * Staff ID generator
     * ---------------------------------------------------------------- */

    /**
     * Pick the next CASI-NNNN id by reading the highest currently in the
     * table. Matches the seeder convention so the numbering stays unified
     * regardless of where the row was created (HR module, user-creation
     * hook, or seeder).
     */
    public static function generateStaffId(): string
    {
        $last = self::where('staff_id', 'like', 'CASI-%')
            ->orderByDesc('staff_id')
            ->value('staff_id');
        $lastNum = $last ? (int) substr($last, 5) : 1000;
        return 'CASI-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
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
