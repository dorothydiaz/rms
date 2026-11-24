<?php

namespace App\Models\Sales;

use App\Models\Accounting\AcctCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalesVoucher extends Model
{
    protected $table = 'sales_vouchers';
    protected $primaryKey = 'voucher_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'voucher_id',
        'company_id',
        'voucher_code',
        'voucher_name',
        'discount_type',
        'discount_value',
        'min_spend_amount',
        'max_discount_amount',
        'max_total_uses',
        'max_uses_per_customer',
        'current_uses_count',
        'customer_id',
        'valid_from',
        'valid_until',
        'duration_mode',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:4',
            'min_spend_amount' => 'decimal:4',
            'max_discount_amount' => 'decimal:4',
            'max_total_uses' => 'integer',
            'max_uses_per_customer' => 'integer',
            'current_uses_count' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesVoucher $v) {
            if (empty($v->voucher_id)) {
                $v->voucher_id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(SalesVoucherRedemption::class, 'voucher_id', 'voucher_id');
    }
}
