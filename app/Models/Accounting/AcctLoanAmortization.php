<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctLoanAmortization extends Model
{
    protected $table = 'acct_loan_amortizations';
    protected $primaryKey = 'amortization_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'amortization_id',
        'loan_id',
        'period_number',
        'due_date',
        'beginning_principal',
        'principal_amount',
        'interest_amount',
        'total_installment',
        'ending_principal',
        'payment_id',
        'payment_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'payment_date' => 'date',
            'beginning_principal' => 'decimal:4',
            'principal_amount' => 'decimal:4',
            'interest_amount' => 'decimal:4',
            'total_installment' => 'decimal:4',
            'ending_principal' => 'decimal:4',
            'period_number' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctLoanAmortization $amort) {
            if (empty($amort->amortization_id)) {
                $amort->amortization_id = (string) Str::uuid();
            }
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(AcctLoan::class, 'loan_id', 'loan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AcctLoanPayment::class, 'payment_id', 'loan_payment_id');
    }
}

