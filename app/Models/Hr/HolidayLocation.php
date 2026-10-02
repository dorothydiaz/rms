<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayLocation extends Model
{
    use HasFactory;

    protected $table = 'hr_holiday_locations';

    protected $fillable = [
        'holiday_id',
        'branch_id',
        'region',
        'province',
        'city_municipality',
    ];

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class, 'holiday_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
