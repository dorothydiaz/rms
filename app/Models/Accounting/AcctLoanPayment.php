<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctLoanPayment extends Model
{
    protected $table = 'acct_loan_payments';
    protected $primaryKey = 'loan_payment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'loan_payment_id',
        'payment_number',
        'loan_id',
        'company_id',
        'branch_id',
        'payment_date',
        'principal_amount',
        'interest_amount',
        'penalty_amount',
        'total_paid',
        'payment_method',
        'disbursement_account_id',
        'journal_id',
        'status',
        'reference_number',
        'notes',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'principal_amount' => 'decimal:4',
            'interest_amount' => 'decimal:4',
            'penalty_amount' => 'decimal:4',
            'total_paid' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctLoanPayment $pmt) {
            if (empty($pmt->loan_payment_id)) {
                $pmt->loan_payment_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctLoanPayment $pmt) {
            $original = strtoupper((string) $pmt->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($pmt->isDirty(['principal_amount', 'interest_amount', 'disbursement_account_id', 'loan_id'])) {
                    throw new DomainException("Immutable Record: Cannot alter financial details of posted loan repayment [{$pmt->payment_number}].");
                }
            }
        });

        static::deleting(function (AcctLoanPayment $pmt) {
            $status = strtoupper((string) $pmt->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Record: Cannot delete posted loan repayment [{$pmt->payment_number}].");
            }
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(AcctLoan::class, 'loan_id', 'loan_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(GlJournalHeader::class, 'journal_id', 'journal_id');
    }

    public function disbursementAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'disbursement_account_id', 'account_id');
    }
}

