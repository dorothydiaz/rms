<?php

namespace App\Models\Hr;

use App\Models\User;
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
        'work_setup',
        'salary_range_min',
        'salary_range_max',
        'job_description',
        'responsibilities',
        'qualifications',
        'required_skills',
        'preferred_skills',
        'benefits',
        'opening_date',
        'closing_date',
        'hiring_manager_id',
        'hiring_manager_name',
        'recruiter_id',
        'recruiter_name',
        'application_questions',
        'status',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'closing_date' => 'date',
        'salary_range_min' => 'decimal:2',
        'salary_range_max' => 'decimal:2',
        'number_of_openings' => 'integer',
        'application_questions' => 'array',
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

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function hiringManager()
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }

    public function getShortlistedCountAttribute(): int
    {
        return $this->applicants()->where('status', 'Shortlisted')->count();
    }

    public function getInterviewsCountAttribute(): int
    {
        return $this->applicants()->where('status', 'Interview')->count();
    }

    public function getOffersCountAttribute(): int
    {
        return $this->applicants()->where('status', 'Offer')->count();
    }

    public function getHiredCountAttribute(): int
    {
        return $this->applicants()->where('status', 'Hired')->count();
    }
}
