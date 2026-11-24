<?php

namespace App\Models\Sales;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalesBundleItem extends Model
{
    protected $table = 'sales_bundle_items';
    protected $primaryKey = 'bundle_item_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'bundle_item_id',
        'bundle_id',
        'inventory_item_id',
        'sku',
        'item_name',
        'quantity',
        'uom',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesBundleItem $bi) {
            if (empty($bi->bundle_item_id)) {
                $bi->bundle_item_id = (string) Str::uuid();
            }
        });
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(SalesBundle::class, 'bundle_id', 'bundle_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id', 'id');
    }
}
