<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalTransfer extends Model
{
    protected $table = 'stock_transfer_header';

    protected $fillable = [
        'company_id',
        'parent_transfer_id',
        'transfer_number',
        'receipt_number',
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
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'dispatched_by',
        'received_by',
        'cancelled_by',
        'cancelled_at',
        'carrier_name',
        'driver_plate',
        'waybill_number',
        'required_at',
        'dispatched_at',
        'received_at',
        'total_items_count',
        'total_requested_qty',
        'total_approved_qty',
        'total_transferred_qty',
        'total_received_qty',
        'total_variance_qty',
        'total_valuation',
        'pricing_mode',
        'markup_pct',
        'total_internal_billed',
        'settlement_status',
        'dispatch_journal_id',
        'receipt_journal_id',
        'settlement_journal_id',
        'settled_at',
        'settled_by',
        'has_discrepancy',
        'idempotency_key',
        'notes',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'parent_transfer_id' => 'integer',
        'source_branch_id' => 'integer',
        'destination_branch_id' => 'integer',
        'required_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'settled_at' => 'datetime',
        'total_items_count' => 'integer',
        'total_requested_qty' => 'decimal:2',
        'total_approved_qty' => 'decimal:2',
        'total_transferred_qty' => 'decimal:2',
        'total_received_qty' => 'decimal:2',
        'total_variance_qty' => 'decimal:2',
        'total_valuation' => 'decimal:2',
        'markup_pct' => 'decimal:4',
        'total_internal_billed' => 'decimal:2',
        'has_discrepancy' => 'boolean',
    ];

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id', 'branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id', 'branch_id');
    }

    public function parentTransfer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_transfer_id');
    }

    public function backorders(): HasMany
    {
        return $this->hasMany(self::class, 'parent_transfer_id');
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
