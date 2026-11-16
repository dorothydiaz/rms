<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctCheckRegister extends Model
{
    protected $table = 'acct_check_register';
    protected $primaryKey = 'check_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'check_id',
        'company_id',
        'direction',
        'party_type',
        'party_id',
        'party_name',
        'bank_name',
        'check_number',
        'check_date',
        'maturity_date',
        'amount',
        'source_document_type',
        'source_document_id',
        'status',
        'cleared_at',
        'bounced_at',
        'bounce_reason',
        'bounce_penalty_amount',
        'clearance_journal_id',
        'bounce_journal_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'maturity_date' => 'date',
            'amount' => 'decimal:4',
            'bounce_penalty_amount' => 'decimal:4',
            'cleared_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCheckRegister $chk) {
            if (empty($chk->check_id)) {
                $chk->check_id = (string) Str::uuid();
            }
        });
    }
}

