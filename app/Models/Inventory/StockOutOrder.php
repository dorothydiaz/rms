<?php

namespace App\Models\Inventory;

use App\Models\Purchase\ProcurementVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOutOrder extends Model
{
    protected $table = 'stock_out_header';

    protected $fillable = [
        'order_number',
        'order_type',
        'status',
        'priority',
        'destination_type',
        'destination_location',
        'vendor_id',
        'vendor_name',
        'requested_by',
        'department',
        'approved_by',
        'picker_name',
        'carrier_name',
        'tracking_waybill',
        'reference_no',
        'required_at',
        'dispatched_at',
        'total_items_count',
        'total_requested_qty',
        'total_packed_qty',
        'total_cost_value',
        'notes',
    ];

    protected $casts = [
        'required_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'total_items_count' => 'integer',
        'total_requested_qty' => 'decimal:2',
        'total_packed_qty' => 'decimal:2',
        'total_cost_value' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ProcurementVendor::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOutOrderItem::class, 'stock_out_order_id');
    }

    public function getFulfillmentPercentageAttribute(): float
    {
        if ($this->total_requested_qty <= 0) {
            return 0.0;
        }

        return round(min(100.0, ($this->total_packed_qty / $this->total_requested_qty) * 100), 1);
    }
}
