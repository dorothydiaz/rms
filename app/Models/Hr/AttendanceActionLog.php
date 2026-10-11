<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceActionLog extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'attendance_action_log_id';
    use HasFactory;

    protected $table = 'hr_attendance_action_logs';

    protected $fillable = [
        'attendance_record_id',
        'employee_id',
        'action_type',
        'details',
        'notes',
        'action_by',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
