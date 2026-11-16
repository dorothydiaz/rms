<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctSalesInvoiceLine extends Model
{
    protected $table = 'acct_sales_invoice_lines';
    protected $primaryKey = 'invoice_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'invoice_line_id',
        'invoice_id',
        'line_number',
        'item_id',
        'item_code',
        'description',
        'quantity',
        'uom',
        'unit_price',
        'tax_code',
        'tax_amount',
        'subtotal',
        'total',
        'gl_revenue_account_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctSalesInvoiceLine $line) {
            if (empty($line->invoice_line_id)) {
                $line->invoice_line_id = (string) Str::uuid();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AcctSalesInvoice::class, 'invoice_id', 'invoice_id');
    }
}

