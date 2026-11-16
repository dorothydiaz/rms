<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctDocumentAttachment extends Model
{
    protected $table = 'acct_document_attachments';
    protected $primaryKey = 'attachment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'attachment_id',
        'company_id',
        'document_type',
        'document_id',
        'document_number',
        'file_name',
        'file_type',
        'file_size_bytes',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctDocumentAttachment $att) {
            if (empty($att->attachment_id)) {
                $att->attachment_id = (string) Str::uuid();
            }
            if (empty($att->uploaded_at)) {
                $att->uploaded_at = now();
            }
        });
    }
}

