<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctAuditLog extends Model
{
    protected $table = 'acct_audit_logs';
    protected $primaryKey = 'audit_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'audit_id',
        'company_id',
        'branch_id',
        'module',
        'document_type',
        'document_id',
        'document_number',
        'action',
        'summary',
        'payload_diff_json',
        'acted_by',
        'ip_address',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_diff_json' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctAuditLog $log) {
            if (empty($log->audit_id)) {
                $log->audit_id = (string) Str::uuid();
            }
            if (empty($log->acted_at)) {
                $log->acted_at = now();
            }
        });
    }
}

