<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VendorBillHeader extends Model
{
    protected $table = 'vendor_bill_header';
    protected $primaryKey = 'vendor_bill_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // Uses custom vendor_bill_created_at / vendor_bill_updated_at

    protected $fillable = [
        'vendor_bill_id',
        'vendor_bill_number',
        'vendor_invoice_number',
        'company_id',
        'branch_id',
        'vendor_id',
        'vendor_bank_id',
        'purchase_order_id',
        'vendor_invoice_date',
        'bill_received_date',
        'bill_due_date',
        'bill_payment_terms_code',
        'bill_currency_code',
        'bill_exchange_rate',
        'bill_subtotal_amount',
        'bill_tax_amount',
        'bill_shipping_amount',
        'bill_total_amount',
        'bill_paid_amount',
        'bill_match_status',
        'vendor_bill_status',
        'vendor_bill_approval_status',
        'approval_request_id',
        'vendor_bill_posted_at',
        'vendor_bill_posted_by',
        'vendor_bill_notes',
        'vendor_bill_created_at',
        'vendor_bill_created_by',
        'vendor_bill_updated_at',
        'vendor_bill_updated_by',
    ];

    protected function casts(): array
    {
        return [
            'vendor_invoice_date' => 'date',
            'bill_received_date' => 'date',
            'bill_due_date' => 'date',
            'bill_exchange_rate' => 'decimal:6',
            'bill_subtotal_amount' => 'decimal:4',
            'bill_tax_amount' => 'decimal:4',
            'bill_shipping_amount' => 'decimal:4',
            'bill_total_amount' => 'decimal:4',
            'bill_paid_amount' => 'decimal:4',
            'vendor_bill_posted_at' => 'datetime',
            'vendor_bill_created_at' => 'datetime',
            'vendor_bill_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VendorBillHeader $bill) {
            if (empty($bill->vendor_bill_id)) {
                $bill->vendor_bill_id = (string) Str::uuid();
            }
            if (empty($bill->vendor_bill_created_at)) {
                $bill->vendor_bill_created_at = now();
            }
            $bill->vendor_bill_updated_at = now();
        });

        static::updating(function (VendorBillHeader $bill) {
            $bill->vendor_bill_updated_at = now();
            $originalStatus = strtoupper((string) $bill->getOriginal('vendor_bill_status'));
            if (in_array($originalStatus, ['POSTED', 'PAID'], true)) {
                // Cannot alter amounts or dates on posted/paid bills; only allow status updates (e.g. PAID/PARTIAL) or payments
                if ($bill->isDirty(['bill_subtotal_amount', 'bill_tax_amount', 'bill_total_amount', 'vendor_invoice_number', 'vendor_id', 'purchase_order_id'])) {
                    throw new DomainException("Immutable Posted Record: Cannot modify header amounts of {$originalStatus} bill [{$bill->vendor_bill_number}].");
                }
            }
        });

        static::deleting(function (VendorBillHeader $bill) {
            $status = strtoupper((string) $bill->getOriginal('vendor_bill_status'));
            if (in_array($status, ['POSTED', 'PAID', 'PARTIAL'], true)) {
                throw new DomainException("Immutable Posted Record: Cannot delete a {$status} vendor bill [{$bill->vendor_bill_number}].");
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorBillLine::class, 'vendor_bill_id', 'vendor_bill_id')->orderBy('line_number');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(AcctVendorPaymentLine::class, 'vendor_bill_id', 'vendor_bill_id');
    }
}

