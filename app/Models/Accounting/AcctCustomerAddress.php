<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctCustomerAddress extends Model
{
    protected $table = 'acct_customer_addresses';
    protected $primaryKey = 'address_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'address_id',
        'company_id',
        'customer_id',
        'address_type',
        'label',
        'address_line',
        'city',
        'province',
        'postal_code',
        'receiving_contact',
        'receiving_phone',
        'delivery_window',
        'is_default',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomerAddress $a) {
            if (empty($a->address_id)) {
                $a->address_id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }
}
