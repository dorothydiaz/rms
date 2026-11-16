<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctVendorPayment extends Model
{
    protected $table = 'acct_vendor_payments';
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'payment_id',
        'payment_number',
        'company_id',
        'branch_id',
        'vendor_id',
        'vendor_name',
        'payment_date',
        'payment_method',
        'disbursement_account_id',
        'currency_code',
        'exchange_rate',
        'gross_amount',
        'ewt_amount',
        'net_amount',
        'total_amount',
        'total_ewt_amount',
        'total_net_amount',
        'check_number',
        'check_date',
        'check_maturity_date',
        'bank_reference',
        'is_prepayment',
        'status',
        'journal_id',
        'approval_request_id',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'check_date' => 'date',
            'check_maturity_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'gross_amount' => 'decimal:4',
            'ewt_amount' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'total_ewt_amount' => 'decimal:4',
            'total_net_amount' => 'decimal:4',
            'is_prepayment' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctVendorPayment $payment) {
            if (empty($payment->payment_id)) {
                $payment->payment_id = (string) Str::uuid();
            }
        });

        static::saving(function (AcctVendorPayment $payment) {
            if ((empty($payment->gross_amount) || (float) $payment->gross_amount === 0.0) && !empty($payment->total_amount)) {
                $payment->gross_amount = $payment->total_amount;
            }
            if ((empty($payment->total_amount) || (float) $payment->total_amount === 0.0) && !empty($payment->gross_amount)) {
                $payment->total_amount = $payment->gross_amount;
            }
            if ((empty($payment->ewt_amount) || (float) $payment->ewt_amount === 0.0) && !empty($payment->total_ewt_amount)) {
                $payment->ewt_amount = $payment->total_ewt_amount;
            }
            if ((empty($payment->total_ewt_amount) || (float) $payment->total_ewt_amount === 0.0) && !empty($payment->ewt_amount)) {
                $payment->total_ewt_amount = $payment->ewt_amount;
            }
            if ((empty($payment->net_amount) || (float) $payment->net_amount === 0.0) && !empty($payment->total_net_amount)) {
                $payment->net_amount = $payment->total_net_amount;
            }
            if ((empty($payment->total_net_amount) || (float) $payment->total_net_amount === 0.0) && !empty($payment->net_amount)) {
                $payment->total_net_amount = $payment->net_amount;
            }
        });

        static::updating(function (AcctVendorPayment $payment) {
            $original = strtoupper((string) $payment->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($payment->isDirty(['gross_amount', 'ewt_amount', 'net_amount', 'payment_date', 'disbursement_account_id', 'vendor_id'])) {
                    throw new DomainException("Immutable Posted Record: Cannot alter financial attributes of posted payment [{$payment->payment_number}].");
                }
            }
        });

        static::deleting(function (AcctVendorPayment $payment) {
            $status = strtoupper((string) $payment->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Posted Record: Cannot delete posted payment [{$payment->payment_number}].");
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AcctVendorPaymentLine::class, 'payment_id', 'payment_id');
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->attributes['total_amount'] ?? $this->attributes['gross_amount'] ?? 0.0);
    }

    public function getTotalEwtAmountAttribute(): float
    {
        return (float) ($this->attributes['total_ewt_amount'] ?? $this->attributes['ewt_amount'] ?? 0.0);
    }

    public function getTotalNetAmountAttribute(): float
    {
        return (float) ($this->attributes['total_net_amount'] ?? $this->attributes['net_amount'] ?? 0.0);
    }
}
