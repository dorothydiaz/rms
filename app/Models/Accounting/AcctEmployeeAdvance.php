<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctEmployeeAdvance extends Model
{
    protected $table = 'acct_employee_advances';
    protected $primaryKey = 'advance_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'advance_id',
        'advance_number',
        'company_id',
        'branch_id',
        'employee_id',
        'employee_name',
        'request_date',
        'disbursement_date',
        'settlement_due_date',
        'amount',
        'disbursement_account_id',
        'gl_advance_account_id',
        'purpose',
        'status',
        'liquidated_amount',
        'balance_due',
        'disbursement_journal_id',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'disbursement_date' => 'date',
            'settlement_due_date' => 'date',
            'amount' => 'decimal:4',
            'liquidated_amount' => 'decimal:4',
            'balance_due' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctEmployeeAdvance $adv) {
            if (empty($adv->advance_id)) {
                $adv->advance_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctEmployeeAdvance $adv) {
            $original = strtoupper((string) $adv->getOriginal('status'));
            if (in_array($original, ['DISBURSED', 'FULLY_LIQUIDATED'], true)) {
                if ($adv->isDirty(['amount', 'request_date', 'employee_id'])) {
                    throw new DomainException("Immutable Record: Cannot alter amount or employee of disbursed advance [{$adv->advance_number}].");
                }
            }
        });

        static::deleting(function (AcctEmployeeAdvance $adv) {
            $status = strtoupper((string) $adv->getOriginal('status'));
            if ($status !== 'PENDING') {
                throw new DomainException("Cannot delete advance [{$adv->advance_number}] with status {$status}.");
            }
        });
    }

    public function liquidations(): HasMany
    {
        return $this->hasMany(AcctEmployeeAdvanceLiquidation::class, 'advance_id', 'advance_id');
    }
}

