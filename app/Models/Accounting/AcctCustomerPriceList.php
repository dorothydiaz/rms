<?php

namespace App\Models\Accounting;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctCustomerPriceList extends Model
{
    protected $table = 'acct_customer_price_lists';
    protected $primaryKey = 'price_list_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'price_list_id',
        'company_id',
        'customer_id',
        'inventory_item_id',
        'internal_sku',
        'item_name',
        'customer_sku',
        'uom',
        'standard_price',
        'contract_price',
        'discount_pct',
        'min_qty',
        'valid_from',
        'valid_until',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'standard_price' => 'decimal:4',
            'contract_price' => 'decimal:4',
            'discount_pct' => 'decimal:2',
            'min_qty' => 'decimal:4',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomerPriceList $p) {
            if (empty($p->price_list_id)) {
                $p->price_list_id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id', 'id');
    }
}
