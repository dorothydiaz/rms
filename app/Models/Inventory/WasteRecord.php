<?php

namespace App\Models\Inventory;

use App\Models\Hr\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteRecord extends Model
{
    protected $table = 'waste_records';

    protected $fillable = [
        'waste_number',
        'waste_type',
        'branch_id',
        'branch_name',
        'storage_location',
        'total_cost',
        'total_items_count',
        'disposal_method',
        'status',
        'reported_by',
        'approved_by',
        'waste_date',
        'notes',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'total_items_count' => 'integer',
        'waste_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WasteRecordItem::class, 'waste_record_id');
    }
}
