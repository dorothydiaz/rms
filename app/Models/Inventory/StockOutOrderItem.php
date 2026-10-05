<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOutOrderItem extends Model
{
    protected $table = 'stock_out_items';

    protected $fillable = [
        'stock_out_order_id',
        'inventory_item_id',
        'sku',
        'item_name',
        'category',
        'uom',
        'available_stock',
        'requested_qty',
        'packed_qty',
        'unit_cost',
        'total_cost',
        'batch_lot_no',
        'storage_location',
        'notes',
    ];

    protected $casts = [
        'available_stock' => 'decimal:2',
        'requested_qty' => 'decimal:2',
        'packed_qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(StockOutOrder::class, 'stock_out_order_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->requested_qty <= 0) {
            return 0.0;
        }

        return round(($this->packed_qty / $this->requested_qty) * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->packed_qty <= 0) {
            return 'UNPICKED';
        }
        if ($this->packed_qty == $this->requested_qty) {
            return 'MATCHED';
        }
        if ($this->packed_qty > $this->requested_qty) {
            return 'EXCEEDS';
        }
        return 'IN_PROGRESS';
    }
}
