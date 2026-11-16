<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GlRecurringTemplate extends Model
{
    protected $table = 'gl_recurring_templates';
    protected $primaryKey = 'template_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'template_id',
        'company_id',
        'template_code',
        'template_name',
        'journal_type',
        'frequency',
        'next_run_date',
        'last_run_date',
        'auto_post',
        'default_branch_id',
        'description',
        'lines_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_run_date' => 'date',
            'last_run_date' => 'date',
            'auto_post' => 'boolean',
            'is_active' => 'boolean',
            'lines_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GlRecurringTemplate $tmpl) {
            if (empty($tmpl->template_id)) {
                $tmpl->template_id = (string) Str::uuid();
            }
        });
    }
}
