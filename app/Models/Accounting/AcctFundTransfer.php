<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctFundTransfer extends Model
{
    protected $table = 'acct_fund_transfers';
    protected $primaryKey = 'transfer_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'transfer_id',
        'transfer_number',
        'company_id',
        'transfer_date',
        'source_bank_account_id',
        'destination_bank_account_id',
        'amount',
        'fee_amount',
        'reference_number',
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
            'transfer_date' => 'date',
            'amount' => 'decimal:4',
            'fee_amount' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctFundTransfer $trf) {
            if (empty($trf->transfer_id)) {
                $trf->transfer_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctFundTransfer $trf) {
            $original = strtoupper((string) $trf->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($trf->isDirty(['amount', 'fee_amount', 'transfer_date', 'source_bank_account_id', 'destination_bank_account_id'])) {
                    throw new DomainException("Immutable Posted Record: Cannot modify posted fund transfer [{$trf->transfer_number}].");
                }
            }
        });

        static::deleting(function (AcctFundTransfer $trf) {
            $status = strtoupper((string) $trf->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Posted Record: Cannot delete posted fund transfer [{$trf->transfer_number}].");
            }
        });
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(AcctBankAccount::class, 'source_bank_account_id', 'bank_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(AcctBankAccount::class, 'destination_bank_account_id', 'bank_account_id');
    }
}

