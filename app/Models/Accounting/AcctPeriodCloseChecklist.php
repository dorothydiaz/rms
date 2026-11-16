<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctPeriodCloseChecklist extends Model
{
    protected $table = 'acct_period_close_checklists';
    protected $primaryKey = 'checklist_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'checklist_id',
        'fiscal_period_id',
        'company_id',
        'bank_reconciliation_done',
        'depreciation_posted',
        'inventory_reconciled',
        'ap_ar_reconciled',
        'recurring_posted',
        'fx_revaluation_done',
        'is_locked',
        'locked_by',
        'locked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bank_reconciliation_done' => 'boolean',
            'depreciation_posted' => 'boolean',
            'inventory_reconciled' => 'boolean',
            'ap_ar_reconciled' => 'boolean',
            'recurring_posted' => 'boolean',
            'fx_revaluation_done' => 'boolean',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctPeriodCloseChecklist $item) {
            if (empty($item->checklist_id)) {
                $item->checklist_id = (string) Str::uuid();
            }
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id', 'period_id');
    }
}

