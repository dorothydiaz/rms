<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctBankAccount extends Model
{
    protected $table = 'acct_bank_accounts';
    protected $primaryKey = 'bank_account_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'bank_account_id',
        'company_id',
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'account_type',
        'gl_account_id',
        'currency_code',
        'opening_balance',
        'current_balance',
        'custodian_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctBankAccount $acc) {
            if (empty($acc->bank_account_id)) {
                $acc->bank_account_id = (string) Str::uuid();
            }
        });
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id', 'account_id');
    }
}

