<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceCriterion extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'performance_criteria_id';
    use HasFactory;

    protected $table = 'hr_performance_criteria';

    protected $fillable = [
        'name',
        'description',
        'weight_percentage',
        'is_active',
    ];

    protected $casts = [
        'weight_percentage' => 'integer',
        'is_active' => 'boolean',
    ];
}
