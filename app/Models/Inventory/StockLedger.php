<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    protected $table = 'stock_ledger';

    public $timestamps = false; // Uses custom created_at only (immutable append-only)

    protected $fillable = [
        'transaction_uuid',
        'sku',
        'inventory_item_id',
        'item_name',
        'transaction_type',
        'reference_type',
        'reference_id',
        'reference_no',
        'before_quantity',
        'quantity_change',
        'after_quantity',
        'unit_cost',
        'total_value',
        'batch_lot_no',
        'expiry_date',
        'storage_location',
        'performed_by',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'before_quantity' => 'decimal:2',
        'quantity_change' => 'decimal:2',
        'after_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
