<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayPayRule extends Model
{
    use HasFactory;

    protected $table = 'hr_holiday_pay_rules';

    protected $fillable = [
        'holiday_type',
        'year',
        'effective_from',
        'effective_to',
        'unworked_rate',
        'worked_rate',
        'rest_day_worked_rate',
        'overtime_multiplier',
        'rest_day_overtime_multiplier',
        'calculation_rule_description',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'unworked_rate' => 'decimal:2',
        'worked_rate' => 'decimal:2',
        'rest_day_worked_rate' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'rest_day_overtime_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
