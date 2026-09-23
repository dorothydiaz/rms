<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_period_id',
        'employee_id',
        'evaluator_id',
        'overall_score',
        'status',
        'manager_comments',
        'hr_comments',
        'recommendation',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(PerformancePeriod::class, 'performance_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function ratings()
    {
        return $this->hasMany(PerformanceRating::class, 'evaluation_id');
    }
}
