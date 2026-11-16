<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctNumberingSeries extends Model
{
    protected $table = 'acct_numbering_series';
    protected $primaryKey = 'series_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'series_id',
        'company_id',
        'module_code',
        'module_name',
        'prefix',
        'next_number',
        'padding',
        'reset_frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'padding' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctNumberingSeries $series) {
            if (empty($series->series_id)) {
                $series->series_id = (string) Str::uuid();
            }
        });
    }
}

