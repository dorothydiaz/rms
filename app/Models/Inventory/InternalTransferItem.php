<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalTransferItem extends Model
{
    protected $table = 'stock_transfer_lines';

    protected $fillable = [
        'internal_transfer_id',
        'inventory_item_id',
        'sku',
        'item_name',
        'category',
        'uom',
        'uom_multiplier',
        'base_uom',
        'source_available_stock',
        'requested_qty',
        'approved_qty',
        'reserved_qty',
        'transferred_qty',
        'received_qty',
        'variance_qty',
        'variance_reason',
        'unit_cost',
        'total_cost',
        'internal_unit_price',
        'internal_line_total',
        'batch_lot_no',
        'storage_location',
        'notes',
    ];

    protected $casts = [
        'uom_multiplier' => 'decimal:4',
        'source_available_stock' => 'decimal:2',
        'requested_qty' => 'decimal:2',
        'approved_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'transferred_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'variance_qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'internal_unit_price' => 'decimal:4',
        'internal_line_total' => 'decimal:2',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InternalTransfer::class, 'internal_transfer_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function getBacklogQtyAttribute(): float
    {
        return max(0.0, (float)$this->requested_qty - (float)$this->transferred_qty);
    }
}
