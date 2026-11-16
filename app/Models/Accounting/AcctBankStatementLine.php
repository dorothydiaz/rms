<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctBankStatementLine extends Model
{
    protected $table = 'acct_bank_statement_lines';
    protected $primaryKey = 'statement_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'statement_line_id',
        'reconciliation_id',
        'transaction_date',
        'reference_number',
        'description',
        'debit_amount',
        'credit_amount',
        'is_matched',
        'matched_journal_line_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'debit_amount' => 'decimal:4',
            'credit_amount' => 'decimal:4',
            'is_matched' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctBankStatementLine $line) {
            if (empty($line->statement_line_id)) {
                $line->statement_line_id = (string) Str::uuid();
            }
        });
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(AcctBankReconciliation::class, 'reconciliation_id', 'reconciliation_id');
    }
}

