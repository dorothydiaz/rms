<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctCollectionReceipt extends Model
{
    protected $table = 'acct_collection_receipts';
    protected $primaryKey = 'receipt_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'receipt_id',
        'receipt_number',
        'company_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'receipt_date',
        'payment_method',
        'deposit_account_id',
        'currency_code',
        'exchange_rate',
        'gross_amount',
        'cwt_amount',
        'net_amount',
        'total_amount',
        'total_cwt_amount',
        'total_net_amount',
        'check_number',
        'check_date',
        'check_maturity_date',
        'bank_name',
        'reference_number',
        'is_deposit_mode',
        'status',
        'journal_id',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'check_date' => 'date',
            'check_maturity_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'gross_amount' => 'decimal:4',
            'cwt_amount' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'total_cwt_amount' => 'decimal:4',
            'total_net_amount' => 'decimal:4',
            'is_deposit_mode' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCollectionReceipt $receipt) {
            if (empty($receipt->receipt_id)) {
                $receipt->receipt_id = (string) Str::uuid();
            }
        });

        static::saving(function (AcctCollectionReceipt $receipt) {
            if ((empty($receipt->gross_amount) || (float) $receipt->gross_amount === 0.0) && !empty($receipt->total_amount)) {
                $receipt->gross_amount = $receipt->total_amount;
            }
            if ((empty($receipt->total_amount) || (float) $receipt->total_amount === 0.0) && !empty($receipt->gross_amount)) {
                $receipt->total_amount = $receipt->gross_amount;
            }
            if ((empty($receipt->cwt_amount) || (float) $receipt->cwt_amount === 0.0) && !empty($receipt->total_cwt_amount)) {
                $receipt->cwt_amount = $receipt->total_cwt_amount;
            }
            if ((empty($receipt->total_cwt_amount) || (float) $receipt->total_cwt_amount === 0.0) && !empty($receipt->cwt_amount)) {
                $receipt->total_cwt_amount = $receipt->cwt_amount;
            }
            if ((empty($receipt->net_amount) || (float) $receipt->net_amount === 0.0) && !empty($receipt->total_net_amount)) {
                $receipt->net_amount = $receipt->total_net_amount;
            }
            if ((empty($receipt->total_net_amount) || (float) $receipt->total_net_amount === 0.0) && !empty($receipt->net_amount)) {
                $receipt->total_net_amount = $receipt->net_amount;
            }
        });

        static::updating(function (AcctCollectionReceipt $receipt) {
            $original = strtoupper((string) $receipt->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($receipt->isDirty(['gross_amount', 'cwt_amount', 'net_amount', 'receipt_date', 'customer_id', 'deposit_account_id'])) {
                    throw new DomainException("Immutable Posted Record: Cannot alter financial values of posted collection receipt [{$receipt->receipt_number}].");
                }
            }
        });

        static::deleting(function (AcctCollectionReceipt $receipt) {
            $status = strtoupper((string) $receipt->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Posted Record: Cannot delete posted collection receipt [{$receipt->receipt_number}].");
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AcctCollectionReceiptLine::class, 'receipt_id', 'receipt_id');
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->attributes['total_amount'] ?? $this->attributes['gross_amount'] ?? 0.0);
    }

    public function getTotalCwtAmountAttribute(): float
    {
        return (float) ($this->attributes['total_cwt_amount'] ?? $this->attributes['cwt_amount'] ?? 0.0);
    }

    public function getTotalNetAmountAttribute(): float
    {
        return (float) ($this->attributes['total_net_amount'] ?? $this->attributes['net_amount'] ?? 0.0);
    }
}
