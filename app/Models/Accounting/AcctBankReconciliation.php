<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctBankReconciliation extends Model
{
    protected $table = 'acct_bank_reconciliations';
    protected $primaryKey = 'reconciliation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reconciliation_id',
        'company_id',
        'bank_account_id',
        'statement_date',
        'statement_ending_balance',
        'gl_book_balance',
        'reconciled_balance',
        'difference',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'statement_ending_balance' => 'decimal:4',
            'gl_book_balance' => 'decimal:4',
            'reconciled_balance' => 'decimal:4',
            'difference' => 'decimal:4',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctBankReconciliation $recon) {
            if (empty($recon->reconciliation_id)) {
                $recon->reconciliation_id = (string) Str::uuid();
            }
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(AcctBankAccount::class, 'bank_account_id', 'bank_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AcctBankStatementLine::class, 'reconciliation_id', 'reconciliation_id');
    }
}

