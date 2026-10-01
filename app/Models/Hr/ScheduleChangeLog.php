<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleChangeLog extends Model
{
    use HasFactory;

    protected $table = 'hr_schedule_change_logs';

    protected $fillable = [
        'employee_id',
        'schedule_date',
        'previous_shift_template_id',
        'new_shift_template_id',
        'previous_time_range',
        'new_time_range',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function previousShiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'previous_shift_template_id');
    }

    public function newShiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'new_shift_template_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
