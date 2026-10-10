<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatutoryContributionRule extends Model
{
    use HasFactory;

    protected $table = 'hr_statutory_contribution_rules';

    protected $fillable = [
        'rule_name',
        'rule_type',
        'rate',
        'min_salary',
        'max_salary',
        'employee_share',
        'employer_share',
        'fixed_amount',
        'bracket_json',
        'effective_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'employee_share' => 'decimal:4',
        'employer_share' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'bracket_json' => 'array',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
}
