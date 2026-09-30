<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
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
