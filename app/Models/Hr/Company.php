<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'company_id';
    use HasFactory;

    protected $table = 'hr_companies';

    protected $fillable = [
        'name',
        'type',
        'code',
        'tin',
        'contact_person',
        'email',
        'phone',
        'address',
        'logo',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function getColumnAliases(): array
    {
        return [
            'name' => 'company_legal_name',
            'tin' => 'company_tax_id',
            'email' => 'company_email_address',
            'phone' => 'company_phone_number',
        ];
    }

    public function getNameAttribute() { return $this->attributes['company_legal_name'] ?? null; }
    public function setNameAttribute($v) { $this->attributes['company_legal_name'] = $v; }
    public function getCodeAttribute() { return $this->attributes['company_code'] ?? null; }
    public function setCodeAttribute($v) { $this->attributes['company_code'] = $v; }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeCompanies(Builder $query): Builder
    {
        return $query->where('type', 'Company');
    }

    public function scopeAgencies(Builder $query): Builder
    {
        return $query->where('type', 'Agency');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
