<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctBir2307Certificate extends Model
{
    protected $table = 'acct_bir_2307_certificates';
    protected $primaryKey = 'certificate_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'certificate_id',
        'certificate_number',
        'company_id',
        'party_type',
        'party_id',
        'party_name',
        'party_tin',
        'payee_tin',
        'party_address',
        'vendor_payment_id',
        'source_document_type',
        'source_document_id',
        'source_document_number',
        'period_from',
        'period_to',
        'tax_code',
        'atc_code',
        'income_payment_amount',
        'gross_income_payment',
        'tax_withheld_amount',
        'amount_withheld',
        'status',
        'signed_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'income_payment_amount' => 'decimal:4',
            'gross_income_payment' => 'decimal:4',
            'tax_withheld_amount' => 'decimal:4',
            'amount_withheld' => 'decimal:4',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctBir2307Certificate $cert) {
            if (empty($cert->certificate_id)) {
                $cert->certificate_id = (string) Str::uuid();
            }
        });

        static::saving(function (AcctBir2307Certificate $cert) {
            if (empty($cert->payee_tin) && !empty($cert->party_tin)) {
                $cert->payee_tin = $cert->party_tin;
            }
            if (empty($cert->party_tin) && !empty($cert->payee_tin)) {
                $cert->party_tin = $cert->payee_tin;
            }
            if ((empty($cert->gross_income_payment) || (float) $cert->gross_income_payment === 0.0) && !empty($cert->income_payment_amount)) {
                $cert->gross_income_payment = $cert->income_payment_amount;
            }
            if ((empty($cert->income_payment_amount) || (float) $cert->income_payment_amount === 0.0) && !empty($cert->gross_income_payment)) {
                $cert->income_payment_amount = $cert->gross_income_payment;
            }
            if ((empty($cert->amount_withheld) || (float) $cert->amount_withheld === 0.0) && !empty($cert->tax_withheld_amount)) {
                $cert->amount_withheld = $cert->tax_withheld_amount;
            }
            if ((empty($cert->tax_withheld_amount) || (float) $cert->tax_withheld_amount === 0.0) && !empty($cert->amount_withheld)) {
                $cert->tax_withheld_amount = $cert->amount_withheld;
            }
        });
    }

    public function getPayeeTinAttribute(): ?string
    {
        return $this->attributes['payee_tin'] ?? $this->attributes['party_tin'] ?? null;
    }

    public function getGrossIncomePaymentAttribute(): float
    {
        return (float) ($this->attributes['gross_income_payment'] ?? $this->attributes['income_payment_amount'] ?? 0.0);
    }

    public function getAmountWithheldAttribute(): float
    {
        return (float) ($this->attributes['amount_withheld'] ?? $this->attributes['tax_withheld_amount'] ?? 0.0);
    }
}
