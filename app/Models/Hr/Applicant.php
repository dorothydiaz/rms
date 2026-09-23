<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_vacancy_id',
        'first_name',
        'last_name',
        'contact_number',
        'email',
        'address',
        'resume_path',
        'applied_position',
        'source',
        'application_date',
        'status',
        'hired_as_employee_id',
    ];

    protected $casts = [
        'application_date' => 'date',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function jobVacancy()
    {
        return $this->belongsTo(JobVacancy::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function hiredAsEmployee()
    {
        return $this->belongsTo(Employee::class, 'hired_as_employee_id');
    }
}
