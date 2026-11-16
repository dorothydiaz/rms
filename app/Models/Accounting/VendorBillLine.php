<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorBillLine extends Model
{
    protected $table = 'vendor_bill_lines';
    protected $primaryKey = 'vendor_bill_line_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'vendor_bill_line_id',
        'vendor_bill_id',
        'line_number',
        'item_id',
        'po_line_id',
        'stock_in_line_id',
        'bill_line_description',
        'bill_quantity',
        'purchase_uom_code',
        'bill_unit_price',
        'tax_code',
        'bill_line_tax_amount',
        'bill_line_subtotal',
        'bill_line_total',
        'gl_expense_account_id',
        'vendor_bill_line_notes',
        'vendor_bill_line_created_at',
        'vendor_bill_line_created_by',
        'vendor_bill_line_updated_at',
        'vendor_bill_line_updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_quantity' => 'decimal:4',
            'bill_unit_price' => 'decimal:4',
            'bill_line_tax_amount' => 'decimal:4',
            'bill_line_subtotal' => 'decimal:4',
            'bill_line_total' => 'decimal:4',
            'vendor_bill_line_created_at' => 'datetime',
            'vendor_bill_line_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VendorBillLine $line) {
            if (empty($line->vendor_bill_line_id)) {
                $line->vendor_bill_line_id = (string) Str::uuid();
            }
            if (empty($line->vendor_bill_line_created_at)) {
                $line->vendor_bill_line_created_at = now();
            }
            $line->vendor_bill_line_updated_at = now();
        });

        static::updating(function (VendorBillLine $line) {
            $line->vendor_bill_line_updated_at = now();
        });
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(VendorBillHeader::class, 'vendor_bill_id', 'vendor_bill_id');
    }
}

