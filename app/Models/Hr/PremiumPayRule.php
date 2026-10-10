<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PremiumPayRule extends Model
{
    use HasFactory;

    protected $table = 'hr_premium_pay_rules';

    protected $fillable = [
        'rule_code',
        'name',
        'category',
        'base_multiplier',
        'holiday_multiplier',
        'rest_day_multiplier',
        'overtime_multiplier',
        'night_diff_multiplier',
        'effective_from',
        'effective_to',
        'rule_type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'base_multiplier' => 'decimal:2',
        'holiday_multiplier' => 'decimal:2',
        'rest_day_multiplier' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'night_diff_multiplier' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(PremiumPayRuleVersion::class, 'premium_pay_rule_id');
    }
}
