<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RfqHeader extends Model
{
    use HasFactory;

    protected $table = 'rfq_header';
    protected $primaryKey = 'rfq_id';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'rfq_created_at';
    const UPDATED_AT = 'rfq_updated_at';

    protected $fillable = [
        'rfq_id',
        'rfq_number',
        'rfq_group_id',
        'parent_rfq_id',
        'is_alternative',
        'alternative_label',
        'company_id',
        'branch_id',
        'ship_to_warehouse_id',
        'vendor_id',
        'vendor_contact_id',
        'rfq_date',
        'submission_deadline',
        'quote_valid_until_date',
        'rfq_requested_delivery_date',
        'rfq_currency_code',
        'rfq_payment_terms_code',
        'rfq_incoterms_code',
        'rfq_estimated_total_amount',
        'rfq_quoted_total_amount',
        'rfq_status',
        'rfq_notes',
        'rejection_reason',
        'revision_notes',
        'rfq_approval_status',
        'approval_request_id',
        'rfq_award_approval_status',
        'award_approval_request_id',
        'purchase_order_id',
        'rfq_created_by',
        'rfq_updated_by',
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'submission_deadline' => 'date',
        'quote_valid_until_date' => 'date',
        'rfq_requested_delivery_date' => 'date',
        'rfq_estimated_total_amount' => 'decimal:4',
        'rfq_quoted_total_amount' => 'decimal:4',
        'is_alternative' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->rfq_id)) {
                $model->rfq_id = (string) Str::uuid();
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RfqLine::class, 'rfq_id', 'rfq_id')->orderBy('line_number');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ProcurementVendor::class, 'vendor_id', 'id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function parentRfq(): BelongsTo
    {
        return $this->belongsTo(RfqHeader::class, 'parent_rfq_id', 'rfq_id');
    }

    public function alternatives(): HasMany
    {
        return $this->hasMany(RfqHeader::class, 'parent_rfq_id', 'rfq_id');
    }

    public function groupRfqs(): HasMany
    {
        return $this->hasMany(RfqHeader::class, 'rfq_group_id', 'rfq_group_id');
    }
}

