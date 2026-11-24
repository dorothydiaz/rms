<?php

namespace App\Models\Sales;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalesQuoteLine extends Model
{
    protected $table = 'sales_quote_lines';
    protected $primaryKey = 'quote_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'quote_line_id',
        'quote_id',
        'line_number',
        'inventory_item_id',
        'sku',
        'item_name',
        'description',
        'quantity',
        'uom',
        'uom_conversion_factor',
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
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'uom_conversion_factor' => 'decimal:6',
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
        static::creating(function (SalesQuoteLine $line) {
            if (empty($line->quote_line_id)) {
                $line->quote_line_id = (string) Str::uuid();
            }
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(SalesQuote::class, 'quote_id', 'quote_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id', 'id');
    }
}
