<?php

namespace App\Models\Purchase;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RfqLine extends Model
{
    use HasFactory;

    protected $table = 'rfq_lines';
    protected $primaryKey = 'rfq_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'rfq_line_created_at';
    const UPDATED_AT = 'rfq_line_updated_at';

    protected $fillable = [
        'rfq_line_id',
        'rfq_id',
        'line_number',
        'item_id',
        'requested_quantity',
        'quoted_quantity',
        'purchase_uom_code',
        'target_unit_price',
        'quoted_unit_price',
        'quoted_line_total',
        'lead_time_days',
        'is_awarded',
        'rfq_line_notes',
        'rfq_line_created_by',
        'rfq_line_updated_by',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:4',
        'quoted_quantity' => 'decimal:4',
        'target_unit_price' => 'decimal:4',
        'quoted_unit_price' => 'decimal:4',
        'quoted_line_total' => 'decimal:4',
        'lead_time_days' => 'integer',
        'is_awarded' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->rfq_line_id)) {
                $model->rfq_line_id = (string) Str::uuid();
            }
        });
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(RfqHeader::class, 'rfq_id', 'rfq_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }
}
