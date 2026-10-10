<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PremiumPayItem extends Model
{
    use HasFactory;

    protected $table = 'hr_premium_pay_items';

    protected $fillable = [
        'payroll_period_id',
        'payroll_record_id',
        'employee_id',
        'work_date',
        'attendance_record_id',
        'holiday_id',
        'work_type',
        'holiday_type',
        'is_rest_day',
        'hours_worked',
        'regular_hours',
        'overtime_hours',
        'daily_rate',
        'hourly_rate',
        'applied_rate_multiplier',
        'regular_premium_pay',
        'overtime_premium_pay',
        'premium_amount',
        'calculation_breakdown',
        'rule_version',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'work_date' => 'date',
        'is_rest_day' => 'boolean',
        'hours_worked' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'applied_rate_multiplier' => 'decimal:2',
        'regular_premium_pay' => 'decimal:2',
        'overtime_premium_pay' => 'decimal:2',
        'premium_amount' => 'decimal:2',
        'calculation_breakdown' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class, 'payroll_record_id');
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class, 'holiday_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
