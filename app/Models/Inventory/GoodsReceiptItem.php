<?php

namespace App\Models\Inventory;

use App\Models\Purchase\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $table = 'stock_in_lines';

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'sku',
        'item_name',
        'uom',
        'purchasing_uom',
        'uom_multiplier',
        'received_qty',
        'base_qty',
        'unit_cost',
        'total_cost',
        'lot_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'uom_multiplier' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'base_qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }
}
