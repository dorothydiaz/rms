<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Applicant extends Model
{
    use HasFactory;

    protected $table = 'hr_applicants';

    protected $fillable = [
        'job_vacancy_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'preferred_name',
        'contact_number',
        'email',
        'address',
        'city',
        'province',
        'date_of_birth',
        'gender',
        'civil_status',
        'nationality',
        'applied_position',
        'desired_salary',
        'employment_type',
        'available_start_date',
        'years_of_experience',
        'work_setup_preference',
        'source',
        'application_date',
        'status',
        'recruiter_id',
        'recruiter_name',
        'hiring_manager_id',
        'hiring_manager_name',
        'education',
        'work_experience',
        'skills',
        'certifications',
        'languages',
        'application_answers',
        'resume_path',
        'documents',
        'privacy_consent',
        'data_processing_consent',
        'applicant_declaration',
        'screening_data',
        'final_review_data',
        'offer_data',
        'pre_employment_requirements',
        'preboarding_tasks',
        'onboarding_data',
        'activity_history',
        'hired_as_employee_id',
    ];

    protected $casts = [
        'application_date' => 'date',
        'date_of_birth' => 'date',
        'available_start_date' => 'date',
        'desired_salary' => 'decimal:2',
        'education' => 'array',
        'work_experience' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
        'languages' => 'array',
        'application_answers' => 'array',
        'documents' => 'array',
        'privacy_consent' => 'boolean',
        'data_processing_consent' => 'boolean',
        'applicant_declaration' => 'boolean',
        'screening_data' => 'array',
        'final_review_data' => 'array',
        'offer_data' => 'array',
        'pre_employment_requirements' => 'array',
        'preboarding_tasks' => 'array',
        'onboarding_data' => 'array',
        'activity_history' => 'array',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->suffix]);
        return count($parts) ? implode(' ', $parts) : ($this->first_name . ' ' . $this->last_name);
    }

    public function jobVacancy()
    {
        return $this->belongsTo(JobVacancy::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class)->orderBy('interview_date', 'asc');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class)->orderBy('created_at', 'desc');
    }

    public function hiredAsEmployee()
    {
        return $this->belongsTo(Employee::class, 'hired_as_employee_id');
    }

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function hiringManager()
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    /**
     * Add an activity history event to the chronological timeline.
     */
    public function logActivity(string $action, string $details = '', ?string $userName = null): void
    {
        $history = $this->activity_history ?? [];
        if (!is_array($history)) {
            $history = [];
        }

        $user = Auth::user();
        $author = $userName ?: ($user ? ($user->full_name ?? $user->name ?? $user->username) : 'System');

        $history[] = [
            'action' => $action,
            'details' => $details,
            'performed_by' => $author,
            'timestamp' => now()->toIso8601String(),
            'formatted_time' => now()->format('M d, Y h:i A'),
        ];

        $this->activity_history = $history;
        $this->saveQuietly();
    }

    /**
     * Calculate recruitment pipeline progress percentage.
     */
    public function getPipelineProgressAttribute(): int
    {
        $stages = [
            'New' => 10,
            'Screening' => 20,
            'Shortlisted' => 35,
            'Interview' => 50,
            'Assessment' => 65,
            'Final Review' => 75,
            'Offer' => 85,
            'Pre-Employment' => 92,
            'Hired' => 100,
        ];

        if (isset($stages[$this->status])) {
            return $stages[$this->status];
        }

        // Terminal statuses
        if (in_array($this->status, ['Rejected', 'Withdrawn', 'Offer Declined', 'No Show'])) {
            return 100;
        }

        return 15;
    }

    /**
     * Pre-employment requirement stats: [completed, total, percentage]
     */
    public function getRequirementsStatsAttribute(): array
    {
        $reqs = $this->pre_employment_requirements;
        if (!is_array($reqs) || empty($reqs)) {
            // Default 10 requirements
            return ['completed' => 0, 'total' => 10, 'percentage' => 0];
        }

        $total = count($reqs);
        $completed = 0;
        foreach ($reqs as $r) {
            if (($r['status'] ?? '') === 'Approved' || ($r['status'] ?? '') === 'Submitted') {
                $completed++;
            }
        }

        $pct = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        return ['completed' => $completed, 'total' => $total, 'percentage' => $pct];
    }

    /**
     * Preboarding tasks stats: [completed, total, percentage]
     */
    public function getPreboardingStatsAttribute(): array
    {
        $tasks = $this->preboarding_tasks;
        if (!is_array($tasks) || empty($tasks)) {
            return ['completed' => 0, 'total' => 12, 'percentage' => 0];
        }

        $total = count($tasks);
        $completed = 0;
        foreach ($tasks as $t) {
            if (($t['status'] ?? '') === 'Completed') {
                $completed++;
            }
        }

        $pct = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        return ['completed' => $completed, 'total' => $total, 'percentage' => $pct];
    }

    /**
     * Onboarding progress percentage (0 - 100)
     */
    public function getOnboardingProgressAttribute(): int
    {
        $onboarding = $this->onboarding_data;
        if (is_array($onboarding) && isset($onboarding['progress'])) {
            return (int) $onboarding['progress'];
        }

        // Check tasks inside onboarding_data
        if (is_array($onboarding) && !empty($onboarding['tasks'])) {
            $tasks = $onboarding['tasks'];
            $total = count($tasks);
            $done = 0;
            foreach ($tasks as $t) {
                if (($t['status'] ?? '') === 'Completed') $done++;
            }
            return $total > 0 ? (int) round(($done / $total) * 100) : 0;
        }

        return $this->status === 'Hired' ? 65 : 0;
    }
}
