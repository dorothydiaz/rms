<?php

namespace App\Models\Sales;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalesOrderLine extends Model
{
    protected $table = 'sales_order_lines';
    protected $primaryKey = 'sales_order_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sales_order_line_id',
        'sales_order_id',
        'quote_line_id',
        'line_number',
        'inventory_item_id',
        'sku',
        'item_name',
        'description',
        'uom',
        'uom_conversion_factor',
        'qty_ordered',
        'qty_reserved',
        'qty_delivered',
        'qty_invoiced',
        'unit_cost_snapshot',
        'unit_price',
        'discount_pct',
        'discount_amount',
        'tax_code',
        'tax_rate_pct',
        'tax_amount',
        'line_subtotal',
        'line_total',
        'line_notes',
        'bundle_id',
        'bundle_code',
        'bundle_name',
    ];

    protected $appends = ['qty_remaining_delivery', 'qty_remaining_invoice'];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'uom_conversion_factor' => 'decimal:6',
            'qty_ordered' => 'decimal:4',
            'qty_reserved' => 'decimal:4',
            'qty_delivered' => 'decimal:4',
            'qty_invoiced' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_pct' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_rate_pct' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesOrderLine $line) {
            if (empty($line->sales_order_line_id)) {
                $line->sales_order_line_id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'sales_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id', 'id');
    }

    public function getQtyRemainingDeliveryAttribute(): float
    {
        return max(0.0, round((float) $this->qty_ordered - (float) $this->qty_delivered, 4));
    }

    public function getQtyRemainingInvoiceAttribute(): float
    {
        return max(0.0, round((float) $this->qty_delivered - (float) $this->qty_invoiced, 4));
    }
}
