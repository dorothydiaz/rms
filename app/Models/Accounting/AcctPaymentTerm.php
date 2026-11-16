<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctPaymentTerm extends Model
{
    protected $table = 'acct_payment_terms';
    protected $primaryKey = 'term_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'term_id',
        'company_id',
        'term_code',
        'term_name',
        'due_days',
        'discount_pct',
        'discount_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'due_days' => 'integer',
            'discount_pct' => 'decimal:2',
            'discount_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctPaymentTerm $term) {
            if (empty($term->term_id)) {
                $term->term_id = (string) Str::uuid();
            }
        });
    }
}

