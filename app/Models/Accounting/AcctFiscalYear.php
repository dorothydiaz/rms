<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctFiscalYear extends Model
{
    protected $table = 'acct_fiscal_years';
    protected $primaryKey = 'fiscal_year_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'fiscal_year_id',
        'company_id',
        'year_code',
        'year_name',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctFiscalYear $fy) {
            if (empty($fy->fiscal_year_id)) {
                $fy->fiscal_year_id = (string) Str::uuid();
            }
        });
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcctFiscalPeriod::class, 'fiscal_year_id', 'fiscal_year_id')->orderBy('period_number');
    }
}

