<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Company extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Company $company) {
            if (Schema::hasTable('hr_companies')) {
                DB::table('hr_companies')->updateOrInsert(
                    ['company_id' => $company->company_id],
                    [
                        'company_legal_name' => $company->company_legal_name,
                        'company_code' => $company->company_code,
                        'company_tax_id' => $company->company_tax_id,
                        'company_email_address' => $company->company_email_address,
                        'company_phone_number' => $company->company_phone_number,
                        'company_address' => $company->company_address_line_1,
                        'company_logo' => $company->company_logo,
                        'company_created_at' => $company->company_created_at ?? now(),
                        'company_updated_at' => $company->company_updated_at ?? now(),
                        'company_type' => $company->company_type ?? 'Company',
                        'company_contact_person' => $company->company_contact_person,
                        'company_is_active' => $company->company_is_active ? 1 : 0,
                        'company_notes' => $company->company_notes,
                    ]
                );
            }
        });

        static::deleted(function (Company $company) {
            if (Schema::hasTable('hr_companies')) {
                DB::table('hr_companies')->where('company_id', $company->company_id)->delete();
            }
        });
    }

    protected $table = 'companies';
    protected $primaryKey = 'company_id';

    const CREATED_AT = 'company_created_at';
    const UPDATED_AT = 'company_updated_at';

    protected $fillable = [
        'company_code',
        'parent_company_id',
        'company_legal_name',
        'company_trade_name',
        'company_registration_no',
        'company_tax_id',
        'company_tax_type',
        'company_rdo_code',
        'company_base_currency_code',
        'company_timezone',
        'fiscal_year_start_month',
        'company_address_line_1',
        'company_address_line_2',
        'company_city',
        'company_state_province_code',
        'company_postal_code',
        'company_country_code',
        'company_phone_number',
        'company_email_address',
        'company_website_url',
        'company_contact_person',
        'company_logo',
        'company_favicon',
        'invoice_header_text',
        'invoice_footer_text',
        'bir_permit_number',
        'share_chart_of_accounts',
        'share_customer_vendor_directory',
        'share_product_catalog',
        'company_type',
        'company_notes',
        'company_is_active',
        'company_created_by',
        'company_updated_by',
    ];

    protected $casts = [
        'company_is_active' => 'boolean',
        'share_chart_of_accounts' => 'boolean',
        'share_customer_vendor_directory' => 'boolean',
        'share_product_catalog' => 'boolean',
        'fiscal_year_start_month' => 'integer',
    ];

    protected $appends = [
        'name',
        'trade_name',
        'full_address',
        'is_holding_group',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'company_id', 'company_id');
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id', 'company_id');
    }

    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id', 'company_id');
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(BusinessLegalDocument::class, 'documentable_id', 'company_id')
            ->where('documentable_type', self::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_companies', 'company_id', 'user_id');
    }

    public function getNameAttribute(): string
    {
        return $this->company_legal_name ?? '';
    }

    public function getTradeNameAttribute(): string
    {
        return $this->company_trade_name ?: ($this->company_legal_name ?? '');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->company_address_line_1,
            $this->company_address_line_2,
            $this->company_city,
            $this->company_state_province_code,
            $this->company_postal_code,
            $this->company_country_code,
        ]);
        return implode(', ', $parts);
    }

    public function getIsHoldingGroupAttribute(): bool
    {
        return is_null($this->parent_company_id) && $this->childCompanies()->exists();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->company_logo ? asset('storage/' . $this->company_logo) : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('company_is_active', true);
    }

    public function scopeCompanies(Builder $query): Builder
    {
        return $query->where('company_type', 'Company');
    }

    public function scopeHoldingGroups(Builder $query): Builder
    {
        return $query->whereNull('parent_company_id');
    }
}
