<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends Model
{
    protected $table = 'production_order_items';

    protected $fillable = [
        'production_order_id',
        'raw_item_id',
        'sku',
        'item_name',
        'uom',
        'bom_standard_qty',
        'required_qty',
        'actual_consumed_qty',
        'available_stock_before',
        'is_shortage',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'bom_standard_qty' => 'decimal:4',
        'required_qty' => 'decimal:4',
        'actual_consumed_qty' => 'decimal:4',
        'available_stock_before' => 'decimal:4',
        'is_shortage' => 'boolean',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function rawItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'raw_item_id');
    }
}
