<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctFiscalPeriod extends Model
{
    protected $table = 'acct_fiscal_periods';
    protected $primaryKey = 'period_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'period_id',
        'fiscal_year_id',
        'company_id',
        'period_number',
        'period_code',
        'period_name',
        'start_date',
        'end_date',
        'status',
        'checklist_json',
        'locked_by',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'checklist_json' => 'array',
            'locked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctFiscalPeriod $p) {
            if (empty($p->period_id)) {
                $p->period_id = (string) Str::uuid();
            }
        });
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(AcctFiscalYear::class, 'fiscal_year_id', 'fiscal_year_id');
    }
}

