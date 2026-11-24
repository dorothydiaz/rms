<?php

namespace App\Models\Sales;

use App\Models\Accounting\AcctCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesQuote extends Model
{
    protected $table = 'sales_quotes';
    protected $primaryKey = 'quote_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'quote_id',
        'quote_number',
        'quote_group_id',
        'revision_no',
        'company_id',
        'branch_id',
        'source_warehouse_id',
        'source_warehouse_name',
        'customer_id',
        'customer_name',
        'customer_contact',
        'customer_email',
        'customer_phone',
        'quote_date',
        'valid_until_date',
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
        'estimated_cost_total',
        'estimated_margin_pct',
        'status',
        'sales_order_id',
        'sales_order_number',
        'customer_reference',
        'notes',
        'terms_and_conditions',
        'rejection_reason',
        'confirmed_at',
        'confirmed_by',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['is_expired'];

    protected function casts(): array
    {
        return [
            'revision_no' => 'integer',
            'quote_date' => 'date',
            'valid_until_date' => 'date',
            'requested_delivery_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'subtotal_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'gross_total' => 'decimal:4',
            'estimated_cost_total' => 'decimal:4',
            'estimated_margin_pct' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesQuote $quote) {
            if (empty($quote->quote_id)) {
                $quote->quote_id = (string) Str::uuid();
            }
            if (empty($quote->quote_group_id)) {
                $quote->quote_group_id = $quote->quote_id;
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesQuoteLine::class, 'quote_id', 'quote_id')->orderBy('line_number');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'sales_order_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (in_array(strtoupper((string) $this->status), ['CONVERTED', 'CANCELLED', 'ARCHIVED'], true)) {
            return false;
        }
        if (!$this->valid_until_date) {
            return false;
        }

        return Carbon::parse($this->valid_until_date)->lt(now()->startOfDay());
    }
}
