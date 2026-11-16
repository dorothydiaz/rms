<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AcctCurrency extends Model
{
    protected $table = 'acct_currencies';
    protected $primaryKey = 'currency_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'currency_code',
        'currency_name',
        'symbol',
        'is_base_currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_base_currency' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

