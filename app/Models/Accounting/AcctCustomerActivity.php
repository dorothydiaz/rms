<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctCustomerActivity extends Model
{
    protected $table = 'acct_customer_activities';
    protected $primaryKey = 'activity_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'activity_id',
        'company_id',
        'customer_id',
        'activity_type',
        'subject',
        'description',
        'follow_up_date',
        'acted_by',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'acted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctCustomerActivity $a) {
            if (empty($a->activity_id)) {
                $a->activity_id = (string) Str::uuid();
            }
            if (empty($a->acted_at)) {
                $a->acted_at = now();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AcctCustomer::class, 'customer_id', 'customer_id');
    }
}
