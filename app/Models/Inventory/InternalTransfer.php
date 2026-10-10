<?php

namespace App\Models\Inventory;

use App\Models\Hr\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalTransfer extends Model
{
    protected $table = 'stock_transfer_header';

    protected $fillable = [
        'transfer_number',
        'transfer_type',
        'status',
        'priority',
        'source_location',
        'source_branch_id',
        'destination_location',
        'destination_branch_id',
        'requisition_reference',
        'reason_code',
        'requested_by',
        'approved_by',
        'dispatched_by',
        'received_by',
        'carrier_name',
        'driver_plate',
        'waybill_number',
        'required_at',
        'dispatched_at',
        'received_at',
        'total_items_count',
        'total_requested_qty',
        'total_transferred_qty',
        'total_received_qty',
        'total_valuation',
        'notes',
    ];

    protected $casts = [
        'required_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'total_items_count' => 'integer',
        'total_requested_qty' => 'decimal:2',
        'total_transferred_qty' => 'decimal:2',
        'total_received_qty' => 'decimal:2',
        'total_valuation' => 'decimal:2',
    ];

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InternalTransferItem::class, 'internal_transfer_id');
    }

    public function getFulfillmentPercentageAttribute(): float
    {
        if ($this->total_requested_qty <= 0) {
            return 100.0;
        }

        return round(min(100.0, ($this->total_transferred_qty / $this->total_requested_qty) * 100), 1);
    }
}
