<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctCustomerDocument extends Model
{
    protected $table = 'acct_customer_documents';
    protected $primaryKey = 'document_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'document_id',
        'company_id',
        'customer_id',
        'doc_type',
        'document_title',
        'reference_no',
        'issue_date',
        'expiry_date',
        'file_path',
        'file_name',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomerDocument $d) {
            if (empty($d->document_id)) {
                $d->document_id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }
}
