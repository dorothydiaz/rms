<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Company extends Model
{
    use HasFactory;

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
        'company_branch_code',
        'company_email_domain',
        'company_brand_color',
        'company_external_code',
        'company_is_vat_registered',
        'company_is_active',
        'company_created_by',
        'company_updated_by',
    ];

    protected $casts = [
        'company_is_active' => 'boolean',
        'company_is_vat_registered' => 'boolean',
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
        'tax_id_full',
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

    public function getTaxIdFullAttribute(): string
    {
        $tin = $this->company_tax_id ?: '';
        if (!$tin) return '';
        $branchCode = $this->company_branch_code ?: '00000';
        return str_contains($tin, '-') && strlen($tin) > 11 ? $tin : "{$tin}-{$branchCode}";
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
