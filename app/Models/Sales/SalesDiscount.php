<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesDiscount extends Model
{
    protected $table = 'sales_discounts';
    protected $primaryKey = 'discount_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'discount_id',
        'company_id',
        'discount_code',
        'discount_name',
        'discount_category',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_cap',
        'applicable_customer_tier',
        'vat_exempt_override',
        'requires_manager_approval',
        'stackable',
        'valid_from',
        'valid_until',
        'duration_mode',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:4',
            'min_order_amount' => 'decimal:4',
            'max_discount_cap' => 'decimal:4',
            'vat_exempt_override' => 'boolean',
            'requires_manager_approval' => 'boolean',
            'stackable' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesDiscount $d) {
            if (empty($d->discount_id)) {
                $d->discount_id = (string) Str::uuid();
            }
        });
    }
}
