<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctLoan extends Model
{
    protected $table = 'acct_loans';
    protected $primaryKey = 'loan_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'loan_id',
        'loan_number',
        'company_id',
        'branch_id',
        'lender_name',
        'lender_contact',
        'loan_type',
        'principal_amount',
        'annual_interest_rate_pct',
        'start_date',
        'term_months',
        'maturity_date',
        'payment_frequency',
        'interest_method',
        'monthly_installment_amount',
        'total_interest_expected',
        'principal_paid',
        'interest_paid',
        'balance_remaining',
        'disbursement_account_id',
        'loan_payable_account_id',
        'interest_expense_account_id',
        'disbursement_journal_id',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'maturity_date' => 'date',
            'principal_amount' => 'decimal:4',
            'annual_interest_rate_pct' => 'decimal:4',
            'monthly_installment_amount' => 'decimal:4',
            'total_interest_expected' => 'decimal:4',
            'principal_paid' => 'decimal:4',
            'interest_paid' => 'decimal:4',
            'balance_remaining' => 'decimal:4',
            'term_months' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctLoan $loan) {
            if (empty($loan->loan_id)) {
                $loan->loan_id = (string) Str::uuid();
            }
            if ($loan->balance_remaining <= 0 && $loan->principal_amount > 0) {
                $loan->balance_remaining = (float) $loan->principal_amount;
            }
        });

        static::updating(function (AcctLoan $loan) {
            $original = strtoupper((string) $loan->getOriginal('status'));
            if (in_array($original, ['ACTIVE', 'CLOSED'], true)) {
                if ($loan->isDirty(['principal_amount', 'disbursement_account_id', 'loan_payable_account_id'])) {
                    throw new DomainException("Immutable Record: Cannot modify principal or GL mapping of active/closed loan [{$loan->loan_number}].");
                }
            }
        });

        static::deleting(function (AcctLoan $loan) {
            if ($loan->principal_paid > 0 || $loan->status === 'CLOSED') {
                throw new DomainException("Cannot delete loan [{$loan->loan_number}] with recorded repayments.");
            }
        });
    }

    public function amortizations(): HasMany
    {
        return $this->hasMany(AcctLoanAmortization::class, 'loan_id', 'loan_id')->orderBy('period_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AcctLoanPayment::class, 'loan_id', 'loan_id')->orderBy('payment_date');
    }

    public function loanPayableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'loan_payable_account_id', 'account_id');
    }

    public function interestExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'interest_expense_account_id', 'account_id');
    }

    public function disbursementAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'disbursement_account_id', 'account_id');
    }
}

