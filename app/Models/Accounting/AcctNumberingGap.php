<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctNumberingGap extends Model
{
    protected $table = 'acct_numbering_gaps';
    protected $primaryKey = 'gap_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'gap_id',
        'series_id',
        'company_id',
        'module_code',
        'reserved_number',
        'released_at',
        'reused_at',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
            'reused_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctNumberingGap $gap) {
            if (empty($gap->gap_id)) {
                $gap->gap_id = (string) Str::uuid();
            }
        });
    }
}

