<?php

namespace App\Models\Purchase;

use App\Models\Inventory\GoodsReceipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number',
        'po_type',
        'rfq_reference',
        'vendor_id',
        'vendor_name',
        'vendor_trade_name',
        'vendor_contact_person',
        'vendor_phone',
        'vendor_email',
        'vendor_address',
        'order_date',
        'expected_delivery',
        'delivery_location',
        'special_notes',
        'payment_status',
        'payment_method',
        'amount_paid',
        'balance_due',
        'payment_reference',
        'payment_date',
        'fund_source',
        'payment_remarks',
        'approval_notes',
        'approved_by',
        'status',
        'subtotal',
        'tax_amount',
        'gross_total',
        'created_by_user',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery' => 'date',
        'payment_date' => 'date',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'gross_total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ProcurementVendor::class, 'vendor_id', 'vendor_code');
    }
}
