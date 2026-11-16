<?php

namespace App\Models\Accounting;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcctFixedAssetDisposal extends Model
{
    protected $table = 'acct_fixed_asset_disposals';
    protected $primaryKey = 'disposal_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'disposal_id',
        'disposal_number',
        'asset_id',
        'company_id',
        'branch_id',
        'disposal_date',
        'disposal_type',
        'proceeds_amount',
        'cost_at_disposal',
        'accum_deprec_at_disposal',
        'book_value_at_disposal',
        'gain_loss_amount',
        'buyer_name',
        'receipt_account_id',
        'gain_loss_gl_account_id',
        'journal_id',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'proceeds_amount' => 'decimal:4',
            'cost_at_disposal' => 'decimal:4',
            'accum_deprec_at_disposal' => 'decimal:4',
            'book_value_at_disposal' => 'decimal:4',
            'gain_loss_amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AcctFixedAssetDisposal $disp) {
            if (empty($disp->disposal_id)) {
                $disp->disposal_id = (string) Str::uuid();
            }
        });

        static::updating(function (AcctFixedAssetDisposal $disp) {
            $original = strtoupper((string) $disp->getOriginal('status'));
            if ($original === 'POSTED') {
                if ($disp->isDirty(['proceeds_amount', 'gain_loss_amount', 'asset_id'])) {
                    throw new DomainException("Immutable Record: Cannot alter financial outcome of posted disposal [{$disp->disposal_number}].");
                }
            }
        });

        static::deleting(function (AcctFixedAssetDisposal $disp) {
            $status = strtoupper((string) $disp->getOriginal('status'));
            if ($status === 'POSTED') {
                throw new DomainException("Immutable Record: Cannot delete posted disposal [{$disp->disposal_number}].");
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

