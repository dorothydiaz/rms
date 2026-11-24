<?php

namespace App\Models\Sales;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SalesOrderReservation extends Model
{
    protected $table = 'sales_order_reservations';
    protected $primaryKey = 'reservation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reservation_id',
        'company_id',
        'branch_id',
        'sales_order_id',
        'sales_order_line_id',
        'inventory_item_id',
        'sku',
        'warehouse_name',
        'reserved_qty',
        'fulfilled_qty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reserved_qty' => 'decimal:4',
            'fulfilled_qty' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesOrderReservation $res) {
            if (empty($res->reservation_id)) {
                $res->reservation_id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'sales_order_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class, 'sales_order_line_id', 'sales_order_line_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id', 'id');
    }
}
