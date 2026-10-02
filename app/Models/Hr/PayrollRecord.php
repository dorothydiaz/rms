<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $table = 'hr_payroll_records';

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_pay',
        'total_work_days',
        'total_hours',
        'regular_hours_pay',
        'overtime_hours',
        'overtime_pay',
        'night_diff_hours',
        'night_diff_pay',
        'holiday_pay',
        'rest_day_pay',
        'premium_pay',
        'premium_pay_details',
        'allowances',
        'bonuses',
        'other_earnings',
        'gross_pay',
        'late_deduction',
        'undertime_deduction',
        'absence_deduction',
        'sss_employee',
        'sss_employer',
        'philhealth_employee',
        'philhealth_employer',
        'pagibig_employee',
        'pagibig_employer',
        'withholding_tax',
        'salary_advance',
        'employee_loan',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
    ];

    protected $casts = [
        'basic_pay' => 'decimal:2',
        'total_work_days' => 'decimal:1',
        'total_hours' => 'decimal:2',
        'regular_hours_pay' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'night_diff_hours' => 'decimal:2',
        'night_diff_pay' => 'decimal:2',
        'holiday_pay' => 'decimal:2',
        'rest_day_pay' => 'decimal:2',
        'premium_pay' => 'decimal:2',
        'premium_pay_details' => 'array',
        'allowances' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'undertime_deduction' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'sss_employee' => 'decimal:2',
        'sss_employer' => 'decimal:2',
        'philhealth_employee' => 'decimal:2',
        'philhealth_employer' => 'decimal:2',
        'pagibig_employee' => 'decimal:2',
        'pagibig_employer' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'salary_advance' => 'decimal:2',
        'employee_loan' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function adjustments()
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function premiumPayItems()
    {
        return $this->hasMany(PremiumPayItem::class, 'payroll_record_id');
    }
}
