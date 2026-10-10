<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterialsItem extends Model
{
    protected $table = 'bom_lines';

    protected $fillable = [
        'bill_of_materials_id',
        'raw_item_id',
        'sku',
        'raw_item_name',
        'uom',
        'quantity',
        'unit_cost',
        'total_cost',
        'waste_percentage',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'waste_percentage' => 'decimal:2',
    ];

    public function billOfMaterials(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterials::class, 'bill_of_materials_id');
    }

    public function rawItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'raw_item_id');
    }
}
