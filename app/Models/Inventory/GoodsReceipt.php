<?php

namespace App\Models\Inventory;

use App\Models\Purchase\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $table = 'goods_receipts';

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'po_number',
        'vendor_id',
        'vendor_name',
        'vendor_trade_name',
        'delivery_slip_no',
        'carrier_name',
        'reason_code',
        'delivery_location',
        'settlement_mode',
        'payment_method',
        'payment_ref',
        'freight_cost',
        'customs_cost',
        'handling_cost',
        'total_landed_costs',
        'items_subtotal',
        'gross_total',
        'received_at',
        'received_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'freight_cost' => 'decimal:2',
        'customs_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'total_landed_costs' => 'decimal:2',
        'items_subtotal' => 'decimal:2',
        'gross_total' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id');
    }
}
