<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AcctCustomerMemo extends Model
{
    protected $table = 'acct_customer_memos';
    protected $primaryKey = 'memo_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'memo_id',
        'memo_number',
        'company_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'memo_type',
        'memo_date',
        'sales_invoice_id',
        'invoice_number',
        'amount',
        'tax_amount',
        'total_amount',
        'unapplied_amount',
        'reason',
        'status',
        'journal_id',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'memo_date' => 'date',
            'amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'unapplied_amount' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomerMemo $memo) {
            if (empty($memo->memo_id)) {
                $memo->memo_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctCustomerMemo $memo) {
            $original = strtoupper((string) $memo->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($memo->isDirty(['amount', 'tax_amount', 'total_amount', 'memo_date', 'customer_id', 'memo_type'])) {
                    throw new DomainException("Immutable Posted Record: Cannot modify financial values of posted customer memo [{$memo->memo_number}].");
                }
            }
        });

        static::deleting(function (AcctCustomerMemo $memo) {
            $status = strtoupper((string) $memo->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Posted Record: Cannot delete posted customer memo [{$memo->memo_number}].");
            }
        });
    }
}

