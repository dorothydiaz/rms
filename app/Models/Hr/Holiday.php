<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'hr_holidays';

    protected $fillable = [
        'name',
        'date',
        'year',
        'holiday_type',
        'scope',
        'region',
        'province',
        'city_municipality',
        'applicable_branches',
        'payroll_treatment',
        'official_reference',
        'source',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
        'applicable_branches' => 'array',
        'is_active' => 'boolean',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(HolidayLocation::class, 'holiday_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Determine if this holiday applies to a specific branch.
     */
    public function appliesToBranch(?int $branchId): bool
    {
        if ($this->scope === 'Nationwide') {
            return true;
        }

        if (empty($branchId)) {
            return false;
        }

        $branches = (array) $this->applicable_branches;
        if (empty($branches) || in_array('all', $branches) || in_array((string)$branchId, array_map('strval', $branches))) {
            return true;
        }

        return $this->locations()->where('branch_id', $branchId)->exists();
    }
}
