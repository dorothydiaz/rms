<?php

namespace App\Models\Accounting;

use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesQuote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctCustomer extends Model
{
    protected $table = 'acct_customers';
    protected $primaryKey = 'customer_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'customer_id',
        'company_id',
        'customer_code',
        'customer_name',
        'trade_name',
        'tin',
        'address',
        'contact_person',
        'email',
        'phone',
        'payment_terms_code',
        'credit_limit',
        'current_balance',
        'is_active',
        'customer_tier',
        'credit_hold',
        'credit_grace_days',
        'default_tax_code',
        'shipping_address',
        'sales_rep_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
            'credit_hold' => 'boolean',
            'credit_grace_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomer $customer) {
            if (empty($customer->customer_id)) {
                $customer->customer_id = (string) Str::uuid();
            }
        });
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(AcctSalesInvoice::class, 'customer_id', 'customer_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(AcctCollectionReceipt::class, 'customer_id', 'customer_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id', 'customer_id');
    }

    public function salesQuotes(): HasMany
    {
        return $this->hasMany(SalesQuote::class, 'customer_id', 'customer_id');
    }
}

