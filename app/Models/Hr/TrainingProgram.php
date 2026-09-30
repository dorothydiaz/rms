<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $table = 'hr_training_programs';

    protected $fillable = [
        'name',
        'description',
        'trainer',
        'training_type',
        'location',
        'start_date',
        'end_date',
        'duration_hours',
        'cost',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
        'duration_hours' => 'integer',
    ];

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }
}
