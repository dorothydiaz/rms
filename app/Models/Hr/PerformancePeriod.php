<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformancePeriod extends Model
{
    use HasFactory;

    protected $table = 'hr_performance_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function evaluations()
    {
        return $this->hasMany(PerformanceEvaluation::class);
    }
}
