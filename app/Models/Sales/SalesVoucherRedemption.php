<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalesVoucherRedemption extends Model
{
    protected $table = 'sales_voucher_redemptions';
    protected $primaryKey = 'redemption_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'redemption_id',
        'company_id',
        'voucher_id',
        'sales_order_id',
        'customer_id',
        'discount_applied',
        'status',
        'redeemed_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'decimal:4',
            'redeemed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesVoucherRedemption $r) {
            if (empty($r->redemption_id)) {
                $r->redemption_id = (string) Str::uuid();
            }
        });
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(SalesVoucher::class, 'voucher_id', 'voucher_id');
    }
}
