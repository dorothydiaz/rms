<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteRecordItem extends Model
{
    protected $table = 'stock_waste_lines';

    protected $fillable = [
        'waste_record_id',
        'inventory_item_id',
        'sku',
        'item_name',
        'category',
        'uom',
        'quantity',
        'unit_cost',
        'total_cost',
        'reason_code',
        'batch_lot_no',
        'expiry_date',
        'action_taken',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function wasteRecord(): BelongsTo
    {
        return $this->belongsTo(WasteRecord::class, 'waste_record_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
