<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GlJournalLine extends Model
{
    protected $table = 'gl_journal_lines';
    protected $primaryKey = 'journal_line_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'journal_line_id',
        'journal_id',
        'line_number',
        'account_id',
        'account_code',
        'account_name',
        'company_id',
        'branch_id',
        'department_id',
        'project_code',
        'partner_type',
        'partner_id',
        'partner_name',
        'tax_code',
        'description',
        'transaction_currency_code',
        'exchange_rate',
        'transaction_debit',
        'transaction_credit',
        'base_debit',
        'base_credit',
        'is_fx_rounding_line',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'transaction_debit' => 'decimal:4',
            'transaction_credit' => 'decimal:4',
            'base_debit' => 'decimal:4',
            'base_credit' => 'decimal:4',
            'is_fx_rounding_line' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GlJournalLine $line) {
            if (empty($line->journal_line_id)) {
                $line->journal_line_id = (string) Str::uuid();
            }
        });

        static::updating(function (GlJournalLine $line) {
            $header = GlJournalHeader::find($line->journal_id);
            if ($header && in_array(strtoupper((string) $header->status), ['POSTED', 'REVERSED'], true)) {
                throw new DomainException("Immutable Posted Record: Cannot modify lines of {$header->status} journal [{$header->journal_number}].");
            }
        });

        static::deleting(function (GlJournalLine $line) {
            $header = GlJournalHeader::find($line->journal_id);
            if ($header && in_array(strtoupper((string) $header->status), ['POSTED', 'REVERSED'], true)) {
                throw new DomainException("Immutable Posted Record: Cannot delete lines of {$header->status} journal [{$header->journal_number}].");
            }
        });
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(GlJournalHeader::class, 'journal_id', 'journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id', 'account_id');
    }
}

