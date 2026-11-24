<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SalesPromoAuditLog extends Model
{
    protected $table = 'sales_promo_audit_logs';
    protected $primaryKey = 'audit_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'audit_id',
        'company_id',
        'promo_module',
        'promo_id',
        'promo_code',
        'promo_name',
        'action',
        'summary',
        'changes_json',
        'acted_by',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'changes_json' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesPromoAuditLog $log) {
            if (empty($log->audit_id)) {
                $log->audit_id = (string) Str::uuid();
            }
            if (empty($log->acted_at)) {
                $log->acted_at = now();
            }
        });
    }
}
