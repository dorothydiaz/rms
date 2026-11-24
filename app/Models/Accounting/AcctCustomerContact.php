<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctCustomerContact extends Model
{
    protected $table = 'acct_customer_contacts';
    protected $primaryKey = 'contact_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'contact_id',
        'company_id',
        'customer_id',
        'full_name',
        'role_title',
        'department',
        'email',
        'phone',
        'is_primary',
        'receives_invoices',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'receives_invoices' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomerContact $c) {
            if (empty($c->contact_id)) {
                $c->contact_id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }
}
