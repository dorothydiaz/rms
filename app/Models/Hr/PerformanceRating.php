<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'criterion_id',
        'rating',
        'comments',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function evaluation()
    {
        return $this->belongsTo(PerformanceEvaluation::class, 'evaluation_id');
    }

    public function criterion()
    {
        return $this->belongsTo(PerformanceCriterion::class, 'criterion_id');
    }
}
