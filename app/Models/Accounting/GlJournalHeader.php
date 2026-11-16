<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GlJournalHeader extends Model
{
    protected $table = 'gl_journal_headers';
    protected $primaryKey = 'journal_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUSES = ['DRAFT', 'APPROVED', 'POSTED', 'REVERSED'];

    public const JOURNAL_TYPES = [
        'MANUAL',
        'PAYROLL',
        'ACCRUAL',
        'RECURRING',
        'ADJUSTING',
        'OPENING_BALANCE',
        'CLOSING',
        'SYSTEM_AP',
        'SYSTEM_AR',
        'SYSTEM_INVENTORY',
        'SYSTEM_DEPRECIATION',
        'SYSTEM_LOAN',
        'REVERSAL',
    ];

    protected $fillable = [
        'journal_id',
        'journal_number',
        'company_id',
        'branch_id',
        'fiscal_period_id',
        'journal_date',
        'journal_type',
        'source_module',
        'source_document_type',
        'source_document_id',
        'source_document_number',
        'currency_code',
        'exchange_rate',
        'total_transaction_debit',
        'total_transaction_credit',
        'total_debit',
        'total_credit',
        'status',
        'is_system_generated',
        'reversed_from_journal_id',
        'reversal_journal_id',
        'reversal_reason',
        'description',
        'approval_request_id',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date:Y-m-d',
            'exchange_rate' => 'decimal:6',
            'total_transaction_debit' => 'decimal:4',
            'total_transaction_credit' => 'decimal:4',
            'total_debit' => 'decimal:4',
            'total_credit' => 'decimal:4',
            'is_system_generated' => 'boolean',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GlJournalHeader $header) {
            if (empty($header->journal_id)) {
                $header->journal_id = (string) Str::uuid();
            }
        });

        static::updating(function (GlJournalHeader $header) {
            $originalStatus = strtoupper((string) $header->getOriginal('status'));
            if ($originalStatus === 'REVERSED') {
                throw new DomainException("Immutable Posted Record: Reversed journal [{$header->journal_number}] cannot be modified.");
            }
            if ($originalStatus === 'POSTED') {
                $isValidReversalTransition = $header->status === 'REVERSED'
                    && !$header->isDirty(['total_debit', 'total_credit', 'journal_date', 'journal_number', 'journal_type', 'company_id']);
                if (!$isValidReversalTransition) {
                    throw new DomainException("Immutable Posted Record: Posted journal [{$header->journal_number}] cannot be edited; use a REVERSAL entry instead.");
                }
            }
        });

        static::deleting(function (GlJournalHeader $header) {
            $status = strtoupper((string) $header->getOriginal('status'));
            if (in_array($status, ['POSTED', 'REVERSED'], true)) {
                throw new DomainException("Immutable Posted Record: Cannot delete a {$status} journal [{$header->journal_number}].");
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GlJournalLine::class, 'journal_id', 'journal_id')->orderBy('line_number');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcctFiscalPeriod::class, 'fiscal_period_id', 'period_id');
    }

    public function reversedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_from_journal_id', 'journal_id');
    }

    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_journal_id', 'journal_id');
    }
}

