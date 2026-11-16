<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctTaxCode extends Model
{
    protected $table = 'acct_tax_codes';
    protected $primaryKey = 'tax_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tax_id',
        'company_id',
        'tax_code',
        'tax_name',
        'tax_type',
        'rate_pct',
        'atc_code',
        'bir_form',
        'system_role_link',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_pct' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctTaxCode $tax) {
            if (empty($tax->tax_id)) {
                $tax->tax_id = (string) Str::uuid();
            }
        });
    }
}

