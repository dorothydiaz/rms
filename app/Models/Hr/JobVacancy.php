<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobVacancy extends Model
{
    use HasFactory;

    protected $table = 'hr_job_vacancies';

    protected $fillable = [
        'title',
        'position_id',
        'department_id',
        'branch_id',
        'number_of_openings',
        'employment_type',
        'salary_range_min',
        'salary_range_max',
        'job_description',
        'requirements',
        'opening_date',
        'closing_date',
        'status',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
        'salary_range_min' => 'decimal:2',
        'salary_range_max' => 'decimal:2',
        'number_of_openings' => 'integer',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
