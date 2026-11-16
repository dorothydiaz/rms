<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctFixedAssetDepreciation extends Model
{
    protected $table = 'acct_fixed_asset_depreciations';
    protected $primaryKey = 'depreciation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'depreciation_id',
        'depreciation_number',
        'asset_id',
        'company_id',
        'branch_id',
        'fiscal_period_id',
        'depreciation_date',
        'depreciation_amount',
        'book_value_before',
        'book_value_after',
        'journal_id',
        'status',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'depreciation_date' => 'date',
            'depreciation_amount' => 'decimal:4',
            'book_value_before' => 'decimal:4',
            'book_value_after' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctFixedAssetDepreciation $deprec) {
            if (empty($deprec->depreciation_id)) {
                $deprec->depreciation_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctFixedAssetDepreciation $deprec) {
            $original = strtoupper((string) $deprec->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($deprec->isDirty(['depreciation_amount', 'asset_id', 'journal_id'])) {
                    throw new DomainException("Immutable Record: Cannot modify posted depreciation [{$deprec->depreciation_number}].");
                }
            }
        });

        static::deleting(function (AcctFixedAssetDepreciation $deprec) {
            $status = strtoupper((string) $deprec->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Record: Cannot delete posted depreciation [{$deprec->depreciation_number}].");
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AcctFixedAsset::class, 'asset_id', 'asset_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(GlJournalHeader::class, 'journal_id', 'journal_id');
    }
}

