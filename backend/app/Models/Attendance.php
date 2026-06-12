<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'work_hours',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date'       => 'date',
        'clock_in'   => 'datetime',
        'clock_out'  => 'datetime',
        'work_hours' => 'decimal:2',
    ];

    /* ---------------------------------------------------------------- */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /* ---------------------------------------------------------------- */

    /**
     * Hours between clock_in and clock_out, rounded to 2dp, or null if the
     * day isn't complete.
     */
    public function computeWorkHours(): ?float
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }

        return round($this->clock_out->floatDiffInHours($this->clock_in), 2);
    }

    public function toApiArray(): array
    {
        $this->loadMissing('employee.department');

        return [
            'id'              => $this->id,
            'employee_id'     => $this->employee_id,
            'employee_name'   => $this->employee?->name,
            'department'      => $this->employee?->department?->name,
            'date'            => $this->date?->toDateString(),
            'clock_in'        => $this->clock_in?->toISOString(),
            'clock_out'       => $this->clock_out?->toISOString(),
            'status'          => $this->status,
            'work_hours'      => $this->work_hours !== null ? (float) $this->work_hours : null,
            'notes'           => $this->notes,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
