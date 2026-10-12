<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'branch_is_active' => 'boolean',
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
