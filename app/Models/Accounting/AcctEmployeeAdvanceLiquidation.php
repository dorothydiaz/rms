<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctEmployeeAdvanceLiquidation extends Model
{
    protected $table = 'acct_employee_advance_liquidations';
    protected $primaryKey = 'liquidation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'liquidation_id',
        'liquidation_number',
        'advance_id',
        'company_id',
        'branch_id',
        'liquidation_date',
        'total_expense_amount',
        'vat_input_amount',
        'cash_returned_amount',
        'reimbursement_due_amount',
        'expense_details_json',
        'status',
        'journal_id',
        'notes',
        'submitted_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'liquidation_date' => 'date',
            'total_expense_amount' => 'decimal:4',
            'vat_input_amount' => 'decimal:4',
            'cash_returned_amount' => 'decimal:4',
            'reimbursement_due_amount' => 'decimal:4',
            'expense_details_json' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctEmployeeAdvanceLiquidation $liq) {
            if (empty($liq->liquidation_id)) {
                $liq->liquidation_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctEmployeeAdvanceLiquidation $liq) {
            $original = strtoupper((string) $liq->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($liq->isDirty(['total_expense_amount', 'vat_input_amount', 'cash_returned_amount', 'liquidation_date'])) {
                    throw new DomainException("Immutable Posted Record: Cannot modify posted liquidation [{$liq->liquidation_number}].");
                }
            }
        });

        static::deleting(function (AcctEmployeeAdvanceLiquidation $liq) {
            $status = strtoupper((string) $liq->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Posted Record: Cannot delete posted liquidation [{$liq->liquidation_number}].");
            }
        });
    }

    public function advance(): BelongsTo
    {
        return $this->belongsTo(AcctEmployeeAdvance::class, 'advance_id', 'advance_id');
    }
}

