<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_id',
        'employee_id',
        'enrollment_date',
        'attendance_status',
        'completion_status',
        'score',
        'certificate_path',
        'completion_date',
        'remarks',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'score' => 'decimal:2',
    ];

    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
