<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_paid',
        'default_credits',
        'is_cumulative',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_cumulative' => 'boolean',
            'default_credits' => 'decimal:2',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
