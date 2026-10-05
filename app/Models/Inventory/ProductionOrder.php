<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $table = 'production_orders';

    protected $fillable = [
        'production_number',
        'batch_lot_number',
        'finished_item_id',
        'bill_of_materials_id',
        'sku',
        'item_name',
        'uom',
        'status',
        'kitchen_station',
        'planned_quantity',
        'actual_quantity',
        'yield_efficiency_percent',
        'total_raw_cost',
        'labor_cost',
        'overhead_cost',
        'total_production_cost',
        'unit_production_cost',
        'production_date',
        'expiry_date',
        'produced_by',
        'verified_by',
        'proceed_with_shortage',
        'shortage_notes',
        'quality_notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'yield_efficiency_percent' => 'decimal:2',
        'total_raw_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'total_production_cost' => 'decimal:2',
        'unit_production_cost' => 'decimal:2',
        'production_date' => 'date',
        'expiry_date' => 'date',
        'proceed_with_shortage' => 'boolean',
    ];

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'finished_item_id');
    }

    public function billOfMaterials(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterials::class, 'bill_of_materials_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class, 'production_order_id');
    }
}
