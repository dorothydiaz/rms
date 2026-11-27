<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchStockBalance extends Model
{
    protected $table = 'branch_stock_balances';

    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_item_id',
        'sku',
        'on_hand_qty',
        'reserved_qty',
        'in_transit_in_qty',
        'in_transit_out_qty',
        'avg_unit_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'branch_id' => 'integer',
        'inventory_item_id' => 'integer',
        'on_hand_qty' => 'decimal:4',
        'reserved_qty' => 'decimal:4',
        'in_transit_in_qty' => 'decimal:4',
        'in_transit_out_qty' => 'decimal:4',
        'avg_unit_cost' => 'decimal:4',
        'last_movement_at' => 'datetime',
    ];

    protected $appends = [
        'available_qty',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function getAvailableQtyAttribute(): float
    {
        return max(0.0, round((float) $this->on_hand_qty - (float) $this->reserved_qty, 4));
    }
}
