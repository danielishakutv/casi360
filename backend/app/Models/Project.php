<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'project_code',
        'name',
        'description',
        'objectives',
        'department_id',
        'project_manager_id',
        'start_date',
        'end_date',
        'location',
        'total_budget',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_budget' => 'decimal:2',
    ];

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['closed']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    public function donors()
    {
        return $this->hasMany(ProjectDonor::class);
    }

    public function partners()
    {
        return $this->hasMany(ProjectPartner::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function activities()
    {
        return $this->hasMany(ProjectActivity::class);
    }

    public function budgetLines()
    {
        return $this->hasMany(ProjectBudgetLine::class);
    }

    public function projectNotes()
    {
        return $this->hasMany(ProjectNote::class);
    }

    /* ----------------------------------------------------------------
     * Accessors
     * ---------------------------------------------------------------- */

    public function getDonorCountAttribute(): int
    {
        return $this->donors()->count();
    }

    public function getPartnerCountAttribute(): int
    {
        return $this->partners()->count();
    }

    public function getTeamMemberCountAttribute(): int
    {
        return $this->teamMembers()->count();
    }

    public function getActivityCountAttribute(): int
    {
        return $this->activities()->count();
    }

    public function getBudgetLineCountAttribute(): int
    {
        return $this->budgetLines()->count();
    }

    public function getNoteCountAttribute(): int
    {
        return $this->projectNotes()->count();
    }

    public function getActivityProgressAttribute(): array
    {
        $activities = $this->activities()->get();
        if ($activities->isEmpty()) {
            return ['total' => 0, 'completed' => 0, 'percentage' => 0];
        }

        $completed = $activities->where('status', 'completed')->count();
        return [
            'total' => $activities->count(),
            'completed' => $completed,
            'percentage' => round(($completed / $activities->count()) * 100),
        ];
    }

    /* ----------------------------------------------------------------
     * Auto-generate project code
     * ---------------------------------------------------------------- */

    public static function generateProjectCode(): string
    {
        $prefix = 'PRJ-' . now()->format('Ym');
        $latest = self::where('project_code', 'like', $prefix . '%')
            ->orderBy('project_code', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->project_code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Recalculate total budget from budget lines
     * ---------------------------------------------------------------- */

    public function recalculateTotalBudget(): void
    {
        $this->update([
            'total_budget' => $this->budgetLines()->sum('total_cost'),
        ]);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'name' => $this->name,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'project_manager_id' => $this->project_manager_id,
            'project_manager' => $this->projectManager?->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'location' => $this->location,
            'total_budget' => (float) $this->total_budget,
            'currency' => $this->currency,
            'status' => $this->status,
            'notes' => $this->notes,
            'donor_count' => $this->donor_count,
            'partner_count' => $this->partner_count,
            'team_member_count' => $this->team_member_count,
            'activity_count' => $this->activity_count,
            'budget_line_count' => $this->budget_line_count,
            'note_count' => $this->note_count,
            'activity_progress' => $this->activity_progress,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public function toDetailArray(): array
    {
        return array_merge($this->toApiArray(), [
            'donors' => $this->donors->map->toApiArray()->toArray(),
            'partners' => $this->partners->map->toApiArray()->toArray(),
            'team_members' => $this->teamMembers()->with('employee')->get()->map->toApiArray()->toArray(),
            'activities' => $this->activities()->orderBy('sort_order')->get()->map->toApiArray()->toArray(),
            'budget_lines' => $this->budgetLines()->with('budgetCategory')->get()->map->toApiArray()->toArray(),
            'notes' => $this->projectNotes()->with('creator')->orderBy('created_at', 'desc')->get()->map->toApiArray()->toArray(),
        ]);
    }
}
