<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'schedule_id',
        'branch_id',
        'date',
        'in_1',
        'out_1',
        'in_2',
        'out_2',
        'in_3',
        'out_3',
        'time_in',
        'break_out',
        'break_in',
        'coffee_break_out',
        'coffee_break_in',
        'time_out',
        'total_hours',
        'regular_hours',
        'late_minutes',
        'undertime_minutes',
        'overtime_hours',
        'absence_days',
        'night_diff_hours',
        'holiday_type',
        'is_rest_day',
        'status',
        'dtr_remarks',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_rest_day' => 'boolean',
            'total_hours' => 'decimal:2',
            'regular_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'night_diff_hours' => 'decimal:2',
            'late_minutes' => 'integer',
            'undertime_minutes' => 'integer',
        ];
    }

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'schedule_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function getInAttribute(): ?string
    {
        return $this->time_in ?? $this->in_1;
    }

    public function getFinalOutAttribute(): ?string
    {
        return $this->time_out ?? $this->out_3;
    }
}
