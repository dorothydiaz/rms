<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Branch extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Branch $branch) {
            if (Schema::hasTable('hr_branches')) {
                DB::table('hr_branches')->updateOrInsert(
                    ['branch_id' => $branch->branch_id],
                    [
                        'branch_company_id' => $branch->company_id,
                        'branch_name' => $branch->branch_name,
                        'branch_code' => $branch->branch_code,
                        'branch_address' => $branch->branch_address_line_1,
                        'branch_phone_number' => $branch->branch_phone_number,
                        'branch_email_address' => $branch->branch_email_address,
                        'branch_is_active' => $branch->branch_is_active ? 1 : 0,
                        'branch_created_at' => $branch->branch_created_at ?? now(),
                        'branch_updated_at' => $branch->branch_updated_at ?? now(),
                    ]
                );
            }
        });

        static::deleted(function (Branch $branch) {
            if (Schema::hasTable('hr_branches')) {
                DB::table('hr_branches')->where('branch_id', $branch->branch_id)->delete();
            }
        });
    }

    protected $table = 'branches';
    protected $primaryKey = 'branch_id';

    const CREATED_AT = 'branch_created_at';
    const UPDATED_AT = 'branch_updated_at';

    protected $fillable = [
        'company_id',
        'branch_code',
        'branch_name',
        'branch_tax_code',
        'branch_type',
        'branch_address_line_1',
        'branch_address_line_2',
        'branch_city',
        'branch_state_province_code',
        'branch_postal_code',
        'branch_country_code',
        'branch_contact_person',
        'branch_phone_number',
        'branch_email_address',
        'operating_hours',
        'branch_is_active',
        'branch_created_by',
        'branch_updated_by',
        'branch_role',
        'default_source_branch_id',
        'can_supply_branches',
        'allow_peer_transfers',
        'can_direct_procure',
        'internal_pricing_mode',
        'internal_markup_pct',
        'auto_post_interbranch_gl',
    ];

    protected $casts = [
        'branch_is_active' => 'boolean',
        'default_source_branch_id' => 'integer',
        'can_supply_branches' => 'boolean',
        'allow_peer_transfers' => 'boolean',
        'can_direct_procure' => 'boolean',
        'internal_markup_pct' => 'decimal:4',
        'auto_post_interbranch_gl' => 'boolean',
    ];

    protected $appends = [
        'name',
        'full_address',
        'display_title',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function defaultSourceBranch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'default_source_branch_id', 'branch_id');
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(BusinessLegalDocument::class, 'documentable_id', 'branch_id')
            ->where('documentable_type', self::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches', 'branch_id', 'user_id');
    }

    public function getNameAttribute(): string
    {
        return $this->branch_name ?? '';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->branch_address_line_1,
            $this->branch_address_line_2,
            $this->branch_city,
            $this->branch_state_province_code,
            $this->branch_postal_code,
            $this->branch_country_code,
        ]);
        return implode(', ', $parts);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->branch_name . ($this->branch_code ? " ({$this->branch_code})" : '');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('branch_is_active', true);
    }

    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
