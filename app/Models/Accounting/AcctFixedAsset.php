<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcctFixedAsset extends Model
{
    protected $table = 'acct_fixed_assets';
    protected $primaryKey = 'asset_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_id',
        'asset_code',
        'company_id',
        'branch_id',
        'department_id',
        'asset_name',
        'asset_category',
        'acquisition_date',
        'acquisition_cost',
        'salvage_value',
        'depreciable_base',
        'useful_life_months',
        'depreciation_method',
        'asset_gl_account_id',
        'accum_deprec_gl_account_id',
        'deprec_expense_gl_account_id',
        'vendor_bill_id',
        'vendor_bill_line_id',
        'total_depreciated_amount',
        'net_book_value',
        'status',
        'serial_number',
        'location',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:4',
            'salvage_value' => 'decimal:4',
            'depreciable_base' => 'decimal:4',
            'total_depreciated_amount' => 'decimal:4',
            'net_book_value' => 'decimal:4',
            'useful_life_months' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctFixedAsset $asset) {
            if (empty($asset->asset_id)) {
                $asset->asset_id = (string) Str::uuid();
            }
            if ($asset->depreciable_base <= 0 && $asset->acquisition_cost > 0) {
                $asset->depreciable_base = max(0.0, (float) $asset->acquisition_cost - (float) $asset->salvage_value);
            }
            if ($asset->net_book_value <= 0 && $asset->acquisition_cost > 0) {
                $asset->net_book_value = (float) $asset->acquisition_cost;
            }
        });

        static::updating(function (AcctFixedAsset $asset) {
            $originalStatus = strtoupper((string) $asset->getOriginal('status'));
            if ($originalStatus === 'DISPOSED') {
                if ($asset->isDirty(['acquisition_cost', 'salvage_value', 'useful_life_months', 'asset_gl_account_id'])) {
                    throw new DomainException("Immutable Record: Cannot alter financial parameters of disposed asset [{$asset->asset_code}].");
                }
            }
        });

        static::deleting(function (AcctFixedAsset $asset) {
            if ($asset->total_depreciated_amount > 0 || $asset->status === 'DISPOSED') {
                throw new DomainException("Cannot delete asset [{$asset->asset_code}] with posted depreciation or disposal records.");
            }
        });
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'asset_gl_account_id', 'account_id');
    }

    public function accumDeprecAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accum_deprec_gl_account_id', 'account_id');
    }

    public function deprecExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'deprec_expense_gl_account_id', 'account_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AcctFixedAssetDepreciation::class, 'asset_id', 'asset_id');
    }

    public function disposal(): HasMany
    {
        return $this->hasMany(AcctFixedAssetDisposal::class, 'asset_id', 'asset_id');
    }
}

