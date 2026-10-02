<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class PremiumPayRuleVersion extends Model
{
    use HasFactory;

    protected $table = 'hr_premium_pay_rule_versions';

    protected $fillable = [
        'premium_pay_rule_id',
        'version_number',
        'version_code',
        'effective_date',
        'configuration_snapshot',
        'created_by',
        'change_reason',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'effective_date' => 'date',
        'configuration_snapshot' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PremiumPayRule::class, 'premium_pay_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
