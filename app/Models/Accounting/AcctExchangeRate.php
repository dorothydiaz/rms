<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctExchangeRate extends Model
{
    protected $table = 'acct_exchange_rates';
    protected $primaryKey = 'rate_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rate_id',
        'company_id',
        'from_currency_code',
        'to_currency_code',
        'exchange_rate',
        'effective_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'effective_date' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctExchangeRate $rate) {
            if (empty($rate->rate_id)) {
                $rate->rate_id = (string) Str::uuid();
            }
        });
    }
}

