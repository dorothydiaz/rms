<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctVendorPaymentLine extends Model
{
    protected $table = 'acct_vendor_payment_lines';
    protected $primaryKey = 'payment_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'payment_line_id',
        'payment_id',
        'vendor_bill_id',
        'bill_number',
        'bill_amount',
        'balance_before',
        'allocated_amount',
        'ewt_withheld',
        'ewt_tax_code',
        'ewt_rate_pct',
        'is_prepayment',
    ];

    protected function casts(): array
    {
        return [
            'bill_amount' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'allocated_amount' => 'decimal:4',
            'ewt_withheld' => 'decimal:4',
            'ewt_rate_pct' => 'decimal:4',
            'is_prepayment' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctVendorPaymentLine $line) {
            if (empty($line->payment_line_id)) {
                $line->payment_line_id = (string) Str::uuid();
            }
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AcctVendorPayment::class, 'payment_id', 'payment_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(VendorBillHeader::class, 'vendor_bill_id', 'vendor_bill_id');
    }
}

