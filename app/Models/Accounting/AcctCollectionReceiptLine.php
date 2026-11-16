<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctCollectionReceiptLine extends Model
{
    protected $table = 'acct_collection_receipt_lines';
    protected $primaryKey = 'receipt_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'receipt_line_id',
        'receipt_id',
        'sales_invoice_id',
        'invoice_number',
        'invoice_amount',
        'balance_before',
        'allocated_amount',
        'cwt_withheld',
        'cwt_tax_code',
        'cwt_rate_pct',
    ];

    protected function casts(): array
    {
        return [
            'invoice_amount' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'allocated_amount' => 'decimal:4',
            'cwt_withheld' => 'decimal:4',
            'cwt_rate_pct' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCollectionReceiptLine $line) {
            if (empty($line->receipt_line_id)) {
                $line->receipt_line_id = (string) Str::uuid();
            }
        });
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(AcctCollectionReceipt::class, 'receipt_id', 'receipt_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AcctSalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }
}

