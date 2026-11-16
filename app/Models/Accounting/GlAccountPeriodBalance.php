<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GlAccountPeriodBalance extends Model
{
    protected $table = 'gl_account_period_balances';
    protected $primaryKey = 'balance_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'balance_id',
        'company_id',
        'fiscal_period_id',
        'account_id',
        'branch_id',
        'opening_debit',
        'opening_credit',
        'period_debit',
        'period_credit',
        'closing_debit',
        'closing_credit',
        'net_balance',
    ];

    protected function casts(): array
    {
        return [
            'opening_debit' => 'decimal:4',
            'opening_credit' => 'decimal:4',
            'period_debit' => 'decimal:4',
            'period_credit' => 'decimal:4',
            'closing_debit' => 'decimal:4',
            'closing_credit' => 'decimal:4',
            'net_balance' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GlAccountPeriodBalance $bal) {
            if (empty($bal->balance_id)) {
                $bal->balance_id = (string) Str::uuid();
            }
        });
    }
}

