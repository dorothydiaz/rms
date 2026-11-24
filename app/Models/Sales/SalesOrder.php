<?php

namespace App\Models\Sales;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Inventory\StockOutOrder;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesOrder extends Model
{
    protected $table = 'sales_orders';
    protected $primaryKey = 'sales_order_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sales_order_id',
        'order_number',
        'quote_id',
        'quote_number',
        'company_id',
        'branch_id',
        'source_warehouse_id',
        'source_warehouse_name',
        'customer_id',
        'customer_name',
        'customer_trade_name',
        'customer_tin',
        'customer_contact',
        'customer_email',
        'customer_phone',
        'billing_address',
        'shipping_address',
        'customer_po_number',
        'order_date',
        'requested_delivery_date',
        'payment_terms_code',
        'currency_code',
        'exchange_rate',
        'subtotal_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'gross_total',
        'total_cost_snapshot',
        'amount_billed',
        'amount_paid',
        'balance_due',
        'payment_status',
        'fulfillment_status',
        'credit_review_status',
        'credit_limit_snapshot',
        'credit_exposure_snapshot',
        'credit_breach_amount',
        'credit_review_flags_json',
        'credit_reviewed_by',
        'credit_reviewed_at',
        'credit_review_remarks',
        'approval_request_id',
        'status',
        'document_stage',
        'valid_until_date',
        'revision_no',
        'quote_group_id',
        'confirmed_at',
        'confirmed_by',
        'discount_id',
        'discount_code',
        'voucher_id',
        'voucher_code',
        'voucher_discount_amount',
        'salesperson_employee_id',
        'salesperson_name',
        'special_notes',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'requested_delivery_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'subtotal_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'gross_total' => 'decimal:4',
            'total_cost_snapshot' => 'decimal:4',
            'amount_billed' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'balance_due' => 'decimal:4',
            'credit_limit_snapshot' => 'decimal:4',
            'credit_exposure_snapshot' => 'decimal:4',
            'credit_breach_amount' => 'decimal:4',
            'credit_review_flags_json' => 'array',
            'credit_reviewed_at' => 'datetime',
            'valid_until_date' => 'date',
            'confirmed_at' => 'datetime',
            'revision_no' => 'integer',
            'voucher_discount_amount' => 'decimal:4',
        ];
    }

    protected $appends = ['is_quote_expired'];

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order) {
            if (empty($order->sales_order_id)) {
                $order->sales_order_id = (string) Str::uuid();
            }
        });

        static::updating(function (SalesOrder $order) {
            $originalStatus = strtoupper((string) $order->getOriginal('status'));
            if (in_array($originalStatus, ['CLOSED', 'CANCELLED'], true)) {
                if ($order->isDirty(['subtotal_amount', 'tax_amount', 'gross_total', 'customer_id'])) {
                    throw new DomainException("Immutable Record: Cannot modify financial totals of {$originalStatus} Sales Order [{$order->order_number}].");
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(SalesQuote::class, 'quote_id', 'quote_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id', 'sales_order_id')->orderBy('line_number');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SalesOrderReservation::class, 'sales_order_id', 'sales_order_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(StockOutOrder::class, 'sales_order_id', 'sales_order_id')->orderByDesc('created_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(AcctSalesInvoice::class, 'sales_order_id', 'sales_order_id')->orderByDesc('created_at');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(SalesDiscount::class, 'discount_id', 'discount_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(SalesVoucher::class, 'voucher_id', 'voucher_id');
    }

    public function getIsQuoteExpiredAttribute(): bool
    {
        if (strtoupper((string) $this->document_stage) !== 'QUOTE') {
            return false;
        }
        if (in_array(strtoupper((string) $this->status), ['CANCELLED', 'ARCHIVED', 'CLOSED'], true)) {
            return false;
        }
        if (!$this->valid_until_date) {
            return false;
        }

        return \Carbon\Carbon::parse($this->valid_until_date)->lt(now()->startOfDay());
    }
}
