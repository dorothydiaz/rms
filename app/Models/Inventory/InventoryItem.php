<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category',
        'description',
        'uom',
        'cost_price',
        'selling_price',
        'current_stock',
        'min_stock',
        'max_stock',
        'storage_location',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'inventory_item_id');
    }

    public function latestLedger()
    {
        return $this->hasOne(StockLedger::class, 'inventory_item_id')->latestOfMany('id');
    }

    public function billOfMaterials()
    {
        return $this->hasOne(BillOfMaterials::class, 'finished_item_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'finished_item_id');
    }

    public function wasteItems(): HasMany
    {
        return $this->hasMany(WasteRecordItem::class, 'inventory_item_id');
    }
}
