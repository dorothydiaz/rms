<?php

namespace App\Models\Accounting;

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
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
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
}

