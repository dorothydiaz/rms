<?php

namespace App\Models\Accounting;

use App\Models\Sales\SalesOrder;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctSalesInvoice extends Model
{
    protected $table = 'acct_sales_invoices';
    protected $primaryKey = 'invoice_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'invoice_id',
        'invoice_number',
        'company_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'invoice_date',
        'due_date',
        'payment_terms_code',
        'currency_code',
        'exchange_rate',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'stock_out_id',
        'stock_out_number',
        'sales_order_id',
        'sales_order_number',
        'stock_out_order_ref_id',
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
            'invoice_date' => 'date',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'subtotal_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'balance_due' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctSalesInvoice $inv) {
            if (empty($inv->invoice_id)) {
                $inv->invoice_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctSalesInvoice $inv) {
            $original = strtoupper((string) $inv->getOriginal('status'));
            if (in_array($original, ['POSTED', 'PAID'], true)) {
                if ($inv->isDirty(['subtotal_amount', 'tax_amount', 'total_amount', 'invoice_date', 'customer_id'])) {
                    throw new DomainException("Immutable Posted Record: Cannot modify financial attributes of {$original} sales invoice [{$inv->invoice_number}].");
                }
            }
        });

        static::deleting(function (AcctSalesInvoice $inv) {
            $status = strtoupper((string) $inv->getOriginal('status'));
            if (in_array($status, ['POSTED', 'PAID', 'PARTIAL'], true)) {
                throw new DomainException("Immutable Posted Record: Cannot delete a {$status} sales invoice [{$inv->invoice_number}].");
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AcctSalesInvoiceLine::class, 'invoice_id', 'invoice_id')->orderBy('line_number');
    }

    public function receiptAllocations(): HasMany
    {
        return $this->hasMany(AcctCollectionReceiptLine::class, 'sales_invoice_id', 'invoice_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'sales_order_id');
    }
}

