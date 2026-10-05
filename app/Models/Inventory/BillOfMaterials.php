<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterials extends Model
{
    protected $table = 'bill_of_materials';

    protected $fillable = [
        'bom_code',
        'finished_item_id',
        'sku',
        'item_name',
        'category',
        'uom',
        'yield_quantity',
        'labor_cost',
        'overhead_cost',
        'total_raw_cost',
        'total_cost',
        'unit_cost',
        'selling_price',
        'margin_percentage',
        'prep_time_minutes',
        'shelf_life_days',
        'instructions',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'yield_quantity' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'total_raw_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'prep_time_minutes' => 'integer',
        'shelf_life_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'finished_item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillOfMaterialsItem::class, 'bill_of_materials_id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bill_of_materials_id');
    }
}
