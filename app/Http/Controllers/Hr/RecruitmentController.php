<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Applicant;
use App\Models\Hr\Assessment;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDocument;
use App\Models\Hr\EmploymentHistory;
use App\Models\Hr\Interview;
use App\Models\Hr\JobVacancy;
use App\Models\Hr\Position;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    // ==========================================
    // 1. JOB VACANCIES
    // ==========================================

    public function vacanciesIndex(Request $request): View
    {
        $query = JobVacancy::with(['position', 'department', 'branch', 'recruiter', 'hiringManager'])
            ->withCount('applicants');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('job_description', 'like', $term)
                  ->orWhereHas('position', function ($pq) use ($term) {
                      $pq->where('name', 'like', $term);
                  })
                  ->orWhereHas('department', function ($dq) use ($term) {
                      $dq->where('name', 'like', $term);
                  });
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $vacancies = $query->orderBy('opening_date', 'desc')->paginate($perPage)->withQueryString();

        // Useful vacancy statistics
        $stats = [
            'open_positions' => JobVacancy::where('status', 'Open')->sum('number_of_openings') ?: JobVacancy::where('status', 'Open')->count(),
            'total_applicants' => Applicant::count(),
            'shortlisted' => Applicant::where('status', 'Shortlisted')->count(),
            'interviews' => Applicant::where('status', 'Interview')->count(),
            'offers' => Applicant::where('status', 'Offer')->count(),
            'hired' => Applicant::where('status', 'Hired')->count(),
        ];

        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $positions = Position::all();
        $users = User::all();

        return view('hr.recruitment.vacancies', compact(
            'vacancies',
            'branches',
            'departments',
            'positions',
            'users',
            'stats'
        ));
    }

    public function vacancyStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'position_id' => 'nullable|exists:hr_positions,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'number_of_openings' => 'required|integer|min:1',
            'employment_type' => 'required|string|max:50',
            'work_setup' => 'required|in:On-site,Hybrid,Remote',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'job_description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'preferred_skills' => 'nullable|string',
            'benefits' => 'nullable|string',
            'opening_date' => 'required|date',
            'closing_date' => 'nullable|date',
            'hiring_manager_id' => 'nullable|exists:users,id',
            'hiring_manager_name' => 'nullable|string|max:100',
            'recruiter_id' => 'nullable|exists:users,id',
            'recruiter_name' => 'nullable|string|max:100',
            'status' => 'required|in:Draft,Open,On Hold,Closed,Filled',
        ]);

        if (!empty($validated['hiring_manager_id']) && empty($validated['hiring_manager_name'])) {
            $mgr = User::find($validated['hiring_manager_id']);
            $validated['hiring_manager_name'] = $mgr?->full_name ?? $mgr?->name;
        }

        if (!empty($validated['recruiter_id']) && empty($validated['recruiter_name'])) {
            $rec = User::find($validated['recruiter_id']);
            $validated['recruiter_name'] = $rec?->full_name ?? $rec?->name;
        }

        $vacancy = JobVacancy::create($validated);
        AuditLogger::log('Create', 'Recruitment', $vacancy->id, "Created job vacancy '{$vacancy->title}'");

        return redirect()->back()->with('success', "Job vacancy '{$vacancy->title}' created successfully.");
    }

    public function vacancyUpdate(Request $request, int $id): RedirectResponse
    {
        $vacancy = JobVacancy::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'position_id' => 'nullable|exists:hr_positions,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'number_of_openings' => 'required|integer|min:1',
            'employment_type' => 'required|string|max:50',
            'work_setup' => 'required|in:On-site,Hybrid,Remote',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'job_description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'preferred_skills' => 'nullable|string',
            'benefits' => 'nullable|string',
            'opening_date' => 'required|date',
            'closing_date' => 'nullable|date',
            'hiring_manager_id' => 'nullable|exists:users,id',
            'hiring_manager_name' => 'nullable|string|max:100',
            'recruiter_id' => 'nullable|exists:users,id',
            'recruiter_name' => 'nullable|string|max:100',
            'status' => 'required|in:Draft,Open,On Hold,Closed,Filled',
        ]);

        if (!empty($validated['hiring_manager_id']) && empty($validated['hiring_manager_name'])) {
            $mgr = User::find($validated['hiring_manager_id']);
            $validated['hiring_manager_name'] = $mgr?->full_name ?? $mgr?->name;
        }

        if (!empty($validated['recruiter_id']) && empty($validated['recruiter_name'])) {
            $rec = User::find($validated['recruiter_id']);
            $validated['recruiter_name'] = $rec?->full_name ?? $rec?->name;
        }

        $vacancy->update($validated);
        AuditLogger::log('Update', 'Recruitment', $vacancy->id, "Updated job vacancy '{$vacancy->title}'");

        return redirect()->back()->with('success', "Job vacancy '{$vacancy->title}' updated successfully.");
    }

    public function vacancyPublish(int $id): RedirectResponse
    {
        $vacancy = JobVacancy::findOrFail($id);
        $vacancy->update(['status' => 'Open']);
        AuditLogger::log('Update', 'Recruitment', $vacancy->id, "Published vacancy '{$vacancy->title}'");
        return redirect()->back()->with('success', "Job vacancy published successfully.");
    }

    public function vacancyUnpublish(int $id): RedirectResponse
    {
        $vacancy = JobVacancy::findOrFail($id);
        $vacancy->update(['status' => 'Draft']);
        AuditLogger::log('Update', 'Recruitment', $vacancy->id, "Unpublished vacancy '{$vacancy->title}'");
        return redirect()->back()->with('success', "Job vacancy moved to Draft.");
    }

    public function vacancyClose(int $id): RedirectResponse
    {
        $vacancy = JobVacancy::findOrFail($id);
        $vacancy->update(['status' => 'Closed']);
        AuditLogger::log('Update', 'Recruitment', $vacancy->id, "Closed vacancy '{$vacancy->title}'");
        return redirect()->back()->with('success', "Job vacancy marked as Closed.");
    }

    public function vacancyDuplicate(int $id): RedirectResponse
    {
        $original = JobVacancy::findOrFail($id);
        $clone = $original->replicate(['applicants_count']);
        $clone->title = '[Copy] ' . $original->title;
        $clone->status = 'Draft';
        $clone->opening_date = now()->toDateString();
        $clone->save();

        AuditLogger::log('Create', 'Recruitment', $clone->id, "Duplicated vacancy '{$original->title}'");
        return redirect()->back()->with('success', "Job vacancy duplicated as '{$clone->title}'.");
    }

    // ==========================================
    // 2. APPLICANTS & TALENT PIPELINE
    // ==========================================

    public function applicantsIndex(Request $request): View
    {
        $query = Applicant::with(['jobVacancy', 'interviews.interviewer', 'assessments', 'hiredAsEmployee', 'recruiter']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('position')) {
            $query->where(function ($q) use ($request) {
                $q->where('applied_position', $request->position)
                  ->orWhereHas('jobVacancy', function ($vq) use ($request) {
                      $vq->where('title', $request->position)
                         ->orWhereHas('position', function ($pq) use ($request) {
                             $pq->where('name', $request->position);
                         });
                  });
            });
        }
        if ($request->filled('recruiter_id')) {
            $query->where('recruiter_id', $request->recruiter_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('application_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('application_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('middle_name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('contact_number', 'like', $term)
                  ->orWhere('applied_position', 'like', $term);
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $applicants = $query->orderBy('application_date', 'desc')->paginate($perPage)->withQueryString();

        // Pipeline Metrics for dashboard cards
        $metrics = [
            'total' => Applicant::count(),
            'screening' => Applicant::whereIn('status', ['New', 'Screening'])->count(),
            'shortlisted' => Applicant::where('status', 'Shortlisted')->count(),
            'interviewing' => Applicant::whereIn('status', ['Interview', 'Assessment'])->count(),
            'final_review' => Applicant::where('status', 'Final Review')->count(),
            'offers' => Applicant::where('status', 'Offer')->count(),
            'pre_employment' => Applicant::where('status', 'Pre-Employment')->count(),
            'hired' => Applicant::where('status', 'Hired')->count(),
        ];

        $vacancies = JobVacancy::whereIn('status', ['Open', 'Draft'])->get();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $positions = Position::all();
        $users = User::all();

        return view('hr.recruitment.applicants', compact(
            'applicants',
            'vacancies',
            'branches',
            'departments',
            'positions',
            'users',
            'metrics'
        ));
    }

    /**
     * Return complete JSON data for Applicant Drawer/Modal.
     */
    public function applicantData(int $id): JsonResponse
    {
        $applicant = Applicant::with(['jobVacancy.position', 'jobVacancy.department', 'interviews.interviewer', 'assessments', 'hiredAsEmployee', 'recruiter'])
            ->findOrFail($id);

        return response()->json([
            'applicant' => $applicant,
            'full_name' => $applicant->full_name,
            'pipeline_progress' => $applicant->pipeline_progress,
            'requirements_stats' => $applicant->requirements_stats,
            'preboarding_stats' => $applicant->preboarding_stats,
            'onboarding_progress' => $applicant->onboarding_progress,
            'interviews' => $applicant->interviews,
            'assessments' => $applicant->assessments,
            'activity_history' => $applicant->activity_history ?? [],
            'screening_data' => $applicant->screening_data ?? [],
            'final_review_data' => $applicant->final_review_data ?? [],
            'offer_data' => $applicant->offer_data ?? [],
            'pre_employment_requirements' => $applicant->pre_employment_requirements ?? $this->getDefaultPreEmploymentRequirements(),
            'preboarding_tasks' => $applicant->preboarding_tasks ?? $this->getDefaultPreboardingTasks(),
            'onboarding_data' => $applicant->onboarding_data ?? [],
            'hired_employee' => $applicant->hiredAsEmployee ? [
                'id' => $applicant->hiredAsEmployee->id,
                'employee_id' => $applicant->hiredAsEmployee->employee_id,
                'name' => $applicant->hiredAsEmployee->full_name,
                'position' => $applicant->hiredAsEmployee->position?->name,
                'department' => $applicant->hiredAsEmployee->department?->name,
                'date_hired' => $applicant->hiredAsEmployee->date_hired?->format('M d, Y'),
            ] : null,
        ]);
    }

    public function applicantStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Personal
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:15',
            'preferred_name' => 'nullable|string|max:60',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'civil_status' => 'nullable|string|max:30',
            'nationality' => 'nullable|string|max:50',

            // Contact
            'email' => 'nullable|email:rfc,filter|max:100',
            'contact_number' => 'nullable|string|regex:/^([+]?[\d\s\-()]{7,25})$/|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',

            // Professional
            'applied_position' => 'required|string|max:100',
            'desired_salary' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|string|max:50',
            'available_start_date' => 'nullable|date',
            'years_of_experience' => 'nullable|string|max:50',
            'work_setup_preference' => 'nullable|string|max:50',
            'source' => 'required|string|max:50',
            'job_vacancy_id' => 'nullable|exists:hr_job_vacancies,id',
            'application_date' => 'required|date',
            'status' => 'nullable|string|max:50',
            'recruiter_id' => 'nullable|exists:users,id',
            'recruiter_name' => 'nullable|string|max:100',

            // Education & Experience
            'school' => 'nullable|string|max:150',
            'degree' => 'nullable|string|max:100',
            'course' => 'nullable|string|max:100',
            'year_graduated' => 'nullable|string|max:20',
            'honors' => 'nullable|string|max:100',

            // Work Experience arrays
            'experience_companies' => 'nullable|array',
            'experience_positions' => 'nullable|array',
            'experience_start_dates' => 'nullable|array',
            'experience_end_dates' => 'nullable|array',
            'experience_responsibilities' => 'nullable|array',
            'experience_reasons_leaving' => 'nullable|array',

            // Skills & Questions
            'technical_skills' => 'nullable|string',
            'soft_skills' => 'nullable|string',
            'certifications_text' => 'nullable|string',
            'languages_text' => 'nullable|string',
            'q_why_join' => 'nullable|string',
            'q_relevant_exp' => 'nullable|string',
            'q_expected_salary' => 'nullable|string',
            'q_start_timing' => 'nullable|string',

            // Files
            'resume' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
            'portfolio' => 'nullable|file|mimes:pdf,zip,doc,docx|max:15360',
            'certificates_file' => 'nullable|file|mimes:pdf,zip,jpg,png|max:10240',
            'id_document_file' => 'nullable|file|mimes:pdf,jpg,png|max:10240',

            // Consents
            'privacy_consent' => 'nullable|boolean',
            'data_processing_consent' => 'nullable|boolean',
            'applicant_declaration' => 'nullable|boolean',
        ], [
            'email.email' => 'Please provide a valid email address (e.g., name@example.com).',
            'contact_number.regex' => 'The contact number format is invalid. Must contain 7-15 digits (e.g. 0917-123-4567 or +63 917 123 4567).',
        ]);

        // Build education JSON
        $education = [];
        if (!empty($validated['school']) || !empty($validated['degree'])) {
            $education[] = [
                'school' => $validated['school'] ?? '',
                'degree' => $validated['degree'] ?? '',
                'course' => $validated['course'] ?? '',
                'year_graduated' => $validated['year_graduated'] ?? '',
                'honors' => $validated['honors'] ?? '',
            ];
        }

        // Build work experience JSON
        $workExperience = [];
        if (!empty($validated['experience_companies'])) {
            foreach ($validated['experience_companies'] as $idx => $comp) {
                if (trim($comp) !== '') {
                    $workExperience[] = [
                        'company' => $comp,
                        'position' => $validated['experience_positions'][$idx] ?? '',
                        'start_date' => $validated['experience_start_dates'][$idx] ?? '',
                        'end_date' => $validated['experience_end_dates'][$idx] ?? '',
                        'responsibilities' => $validated['experience_responsibilities'][$idx] ?? '',
                        'reason_for_leaving' => $validated['experience_reasons_leaving'][$idx] ?? '',
                    ];
                }
            }
        }

        // Build skills JSON
        $skills = [
            'technical' => array_filter(array_map('trim', explode(',', $validated['technical_skills'] ?? ''))),
            'soft' => array_filter(array_map('trim', explode(',', $validated['soft_skills'] ?? ''))),
        ];

        $certifications = array_filter(array_map('trim', explode(',', $validated['certifications_text'] ?? '')));
        $languages = array_filter(array_map('trim', explode(',', $validated['languages_text'] ?? '')));

        // Build application answers
        $applicationAnswers = [
            'why_join' => $validated['q_why_join'] ?? '',
            'relevant_experience' => $validated['q_relevant_exp'] ?? '',
            'expected_salary' => $validated['q_expected_salary'] ?? '',
            'start_timing' => $validated['q_start_timing'] ?? '',
        ];

        // Handle file uploads
        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('recruitment/resumes', 'public');
        }

        $documents = [];
        if ($resumePath) {
            $documents['resume'] = $resumePath;
        }
        if ($request->hasFile('portfolio')) {
            $documents['portfolio'] = $request->file('portfolio')->store('recruitment/portfolios', 'public');
        }
        if ($request->hasFile('certificates_file')) {
            $documents['certificates'] = $request->file('certificates_file')->store('recruitment/certificates', 'public');
        }
        if ($request->hasFile('id_document_file')) {
            $documents['id_document'] = $request->file('id_document_file')->store('recruitment/ids', 'public');
        }

        if (!empty($validated['recruiter_id']) && empty($validated['recruiter_name'])) {
            $rec = User::find($validated['recruiter_id']);
            $validated['recruiter_name'] = $rec?->full_name ?? $rec?->name;
        }

        $applicant = new Applicant();
        $applicant->fill([
            'job_vacancy_id' => $validated['job_vacancy_id'] ?? null,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'preferred_name' => $validated['preferred_name'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'civil_status' => $validated['civil_status'] ?? 'Single',
            'nationality' => $validated['nationality'] ?? 'Filipino',
            'applied_position' => $validated['applied_position'],
            'desired_salary' => $validated['desired_salary'] ?? null,
            'employment_type' => $validated['employment_type'] ?? 'Regular',
            'available_start_date' => $validated['available_start_date'] ?? null,
            'years_of_experience' => $validated['years_of_experience'] ?? null,
            'work_setup_preference' => $validated['work_setup_preference'] ?? 'On-site',
            'source' => $validated['source'] ?? 'Walk-in',
            'application_date' => $validated['application_date'] ?? now()->toDateString(),
            'status' => !empty($validated['status']) ? $validated['status'] : 'New',
            'recruiter_id' => $validated['recruiter_id'] ?? Auth::id(),
            'recruiter_name' => $validated['recruiter_name'] ?? (Auth::user()?->full_name ?? 'HR Recruiter'),
            'education' => $education,
            'work_experience' => $workExperience,
            'skills' => $skills,
            'certifications' => $certifications,
            'languages' => $languages,
            'application_answers' => $applicationAnswers,
            'resume_path' => $resumePath,
            'documents' => $documents,
            'privacy_consent' => true,
            'data_processing_consent' => true,
            'applicant_declaration' => true,
            'pre_employment_requirements' => $this->getDefaultPreEmploymentRequirements(),
            'preboarding_tasks' => $this->getDefaultPreboardingTasks(),
        ]);

        $applicant->save();

        // Initial Activity Log
        $applicant->logActivity(
            'Application Submitted',
            "Registered candidate application for '{$applicant->applied_position}' via {$applicant->source}."
        );

        AuditLogger::log('Create', 'Recruitment', $applicant->id, "Registered applicant '{$applicant->full_name}' for {$applicant->applied_position}");

        return redirect()->back()->with('success', "Applicant {$applicant->full_name} registered successfully into pipeline.");
    }

    public function applicantUpdate(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:15',
            'contact_number' => 'nullable|string|regex:/^([+]?[\d\s\-()]{7,25})$/|max:30',
            'email' => 'nullable|email:rfc,filter|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'applied_position' => 'required|string|max:100',
            'desired_salary' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|string|max:50',
            'available_start_date' => 'nullable|date',
            'years_of_experience' => 'nullable|string|max:50',
            'source' => 'required|string|max:50',
            'recruiter_id' => 'nullable|exists:users,id',
            'recruiter_name' => 'nullable|string|max:100',
        ], [
            'email.email' => 'Please provide a valid email address.',
            'contact_number.regex' => 'The contact number format is invalid. Must contain 7-15 digits.',
        ]);

        if (!empty($validated['recruiter_id']) && empty($validated['recruiter_name'])) {
            $rec = User::find($validated['recruiter_id']);
            $validated['recruiter_name'] = $rec?->full_name ?? $rec?->name;
        }

        $applicant->update($validated);
        $applicant->logActivity('Profile Updated', 'Applicant profile information updated by HR.');

        AuditLogger::log('Update', 'Recruitment', $applicant->id, "Updated details for applicant '{$applicant->full_name}'");

        return redirect()->back()->with('success', "Applicant profile updated successfully.");
    }

    /**
     * Move applicant across pipeline stages:
     * New → Screening → Shortlisted → Interview → Assessment → Final Review → Offer → Pre-Employment → Hired
     * Or terminal statuses: Rejected, Withdrawn, Offer Declined, No Show, On Hold.
     */
    public function applicantChangeStage(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:100',
        ]);

        $prevStatus = $applicant->status;
        $newStatus = $validated['status'];

        $applicant->status = $newStatus;

        // If moving to Offer and no offer data exists, initialize default offer draft
        if ($newStatus === 'Offer' && empty($applicant->offer_data)) {
            $applicant->offer_data = [
                'position' => $applicant->applied_position,
                'department_id' => $applicant->jobVacancy?->department_id,
                'branch_id' => $applicant->jobVacancy?->branch_id,
                'employment_type' => $applicant->employment_type ?: 'Regular',
                'start_date' => $applicant->available_start_date ? $applicant->available_start_date->toDateString() : now()->addWeeks(2)->toDateString(),
                'probation_period' => '6 Months',
                'basic_salary' => $applicant->desired_salary ?: 25000,
                'allowances' => 2000,
                'benefits' => 'SSS, PhilHealth, Pag-IBIG, 13th Month Pay, Meal Allowance, Uniform, Health Card',
                'working_hours' => '8 Hours / Day',
                'work_schedule' => 'Rotating Shift',
                'work_location' => $applicant->jobVacancy?->branch?->name ?? 'Main Branch',
                'supervisor_id' => null,
                'offer_status' => 'Draft',
            ];
        }

        // If moving to Pre-Employment, ensure requirements checklist exists
        if ($newStatus === 'Pre-Employment' && empty($applicant->pre_employment_requirements)) {
            $applicant->pre_employment_requirements = $this->getDefaultPreEmploymentRequirements();
            $applicant->preboarding_tasks = $this->getDefaultPreboardingTasks();
        }

        // If Rejected, store reason
        if ($newStatus === 'Rejected' && !empty($validated['rejection_reason'])) {
            $screening = $applicant->screening_data ?? [];
            $screening['rejection_reason'] = $validated['rejection_reason'];
            $screening['decision'] = 'Reject';
            $screening['remarks'] = $validated['remarks'] ?? $validated['rejection_reason'];
            $applicant->screening_data = $screening;
        }

        $applicant->save();

        $detailText = "Pipeline stage moved from '{$prevStatus}' to '{$newStatus}'.";
        if (!empty($validated['remarks'])) {
            $detailText .= " Remarks: " . $validated['remarks'];
        }
        if (!empty($validated['rejection_reason'])) {
            $detailText .= " Reason: " . $validated['rejection_reason'];
        }

        $applicant->logActivity("Stage Changed: {$newStatus}", $detailText);
        AuditLogger::log('Update', 'Recruitment', $applicant->id, "Changed applicant stage from {$prevStatus} to {$newStatus}");

        return redirect()->back()->with('success', "Candidate moved to stage '{$newStatus}' successfully.");
    }

    /**
     * Submit Screening evaluation & recruiter decision.
     */
    public function applicantScreening(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'chk_education' => 'nullable|in:Pass,Fail,Pending',
            'chk_experience' => 'nullable|in:Pass,Fail,Pending',
            'chk_skills' => 'nullable|in:Pass,Fail,Pending',
            'chk_salary' => 'nullable|in:Pass,Fail,Pending',
            'chk_availability' => 'nullable|in:Pass,Fail,Pending',
            'chk_work_setup' => 'nullable|in:Pass,Fail,Pending',
            'chk_eligibility' => 'nullable|in:Pass,Fail,Pending',
            'decision' => 'required|in:Shortlist,Reject,Hold',
            'rejection_reason' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $screeningData = [
            'checklist' => [
                'education' => $validated['chk_education'] ?? 'Pass',
                'experience' => $validated['chk_experience'] ?? 'Pass',
                'required_skills' => $validated['chk_skills'] ?? 'Pass',
                'salary_expectation' => $validated['chk_salary'] ?? 'Pass',
                'availability' => $validated['chk_availability'] ?? 'Pass',
                'location_work_setup' => $validated['chk_work_setup'] ?? 'Pass',
                'employment_eligibility' => $validated['chk_eligibility'] ?? 'Pass',
            ],
            'decision' => $validated['decision'],
            'rejection_reason' => $validated['decision'] === 'Reject' ? ($validated['rejection_reason'] ?? 'Failed screening') : null,
            'remarks' => $validated['remarks'] ?? '',
            'screened_by' => Auth::user()?->full_name ?? Auth::user()?->name ?? 'HR Recruiter',
            'screened_at' => now()->format('M d, Y h:i A'),
        ];

        $applicant->screening_data = $screeningData;

        // Auto move stage based on decision
        $oldStatus = $applicant->status;
        if ($validated['decision'] === 'Shortlist') {
            $applicant->status = 'Shortlisted';
        } elseif ($validated['decision'] === 'Reject') {
            $applicant->status = 'Rejected';
        } elseif ($validated['decision'] === 'Hold') {
            $applicant->status = 'On Hold';
        }

        $applicant->save();

        $applicant->logActivity(
            "Screening Completed ({$validated['decision']})",
            "Screening decision: {$validated['decision']}." . ($validated['decision'] === 'Reject' ? " Reason: {$validated['rejection_reason']}" : "")
        );

        AuditLogger::log('Update', 'Recruitment', $applicant->id, "Screened applicant {$applicant->full_name} -> {$validated['decision']}");

        return redirect()->back()->with('success', "Screening evaluation recorded. Status updated to '{$applicant->status}'.");
    }

    /**
     * Submit Final Hiring Review summary and decision (Hire, Hold, Reject).
     */
    public function applicantFinalReview(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'recruiter_recommendation' => 'nullable|string|max:255',
            'hiring_manager_recommendation' => 'nullable|string|max:255',
            'reference_results' => 'nullable|string|max:255',
            'proposed_salary' => 'nullable|numeric|min:0',
            'decision' => 'required|in:Hire,Hold,Reject',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $finalReview = [
            'recruiter_recommendation' => $validated['recruiter_recommendation'] ?? 'Recommended for hire',
            'hiring_manager_recommendation' => $validated['hiring_manager_recommendation'] ?? 'Endorsed',
            'reference_results' => $validated['reference_results'] ?? 'Clear references',
            'proposed_salary' => $validated['proposed_salary'] ?? $applicant->desired_salary,
            'decision' => $validated['decision'],
            'remarks' => $validated['remarks'] ?? '',
            'reviewed_by' => Auth::user()?->full_name ?? Auth::user()?->name ?? 'Hiring Panel',
            'reviewed_at' => now()->format('M d, Y h:i A'),
        ];

        $applicant->final_review_data = $finalReview;

        if ($validated['decision'] === 'Hire') {
            $applicant->status = 'Offer';
            // Pre-seed offer data
            $applicant->offer_data = [
                'position' => $applicant->applied_position,
                'department_id' => $applicant->jobVacancy?->department_id,
                'branch_id' => $applicant->jobVacancy?->branch_id,
                'employment_type' => $applicant->employment_type ?: 'Regular',
                'start_date' => $applicant->available_start_date ? $applicant->available_start_date->toDateString() : now()->addWeeks(2)->toDateString(),
                'probation_period' => '6 Months',
                'basic_salary' => $validated['proposed_salary'] ?? ($applicant->desired_salary ?: 25000),
                'allowances' => 2000,
                'benefits' => 'SSS, PhilHealth, Pag-IBIG, 13th Month Pay, Meal Allowance, Uniform, Health Card',
                'working_hours' => '8 Hours / Day',
                'work_schedule' => 'Rotating Shift',
                'work_location' => $applicant->jobVacancy?->branch?->name ?? 'Main Branch',
                'supervisor_id' => null,
                'offer_status' => 'Draft',
            ];
        } elseif ($validated['decision'] === 'Reject') {
            $applicant->status = 'Rejected';
        } elseif ($validated['decision'] === 'Hold') {
            $applicant->status = 'On Hold';
        }

        $applicant->save();

        $applicant->logActivity(
            "Final Hiring Review ({$validated['decision']})",
            "Final hiring decision: {$validated['decision']}. Proposed Salary: ₱" . number_format($validated['proposed_salary'] ?? 0, 2)
        );

        AuditLogger::log('Update', 'Recruitment', $applicant->id, "Final review completed for {$applicant->full_name} -> {$validated['decision']}");

        return redirect()->back()->with('success', "Final Hiring Review submitted. Applicant moved to '{$applicant->status}'.");
    }

    /**
     * Save or update Job Offer details.
     */
    public function applicantOffer(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'position' => 'required|string|max:100',
            'department_id' => 'nullable|exists:hr_departments,id',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'employment_type' => 'required|string|max:50',
            'start_date' => 'required|date',
            'probation_period' => 'nullable|string|max:50',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'benefits' => 'nullable|string|max:500',
            'working_hours' => 'nullable|string|max:100',
            'work_schedule' => 'nullable|string|max:100',
            'work_location' => 'nullable|string|max:150',
            'supervisor_id' => 'nullable|exists:hr_employees,id',
            'offer_status' => 'required|in:Draft,Sent,Viewed,Accepted,Declined,Expired',
        ]);

        $offer = $applicant->offer_data ?? [];
        $offer = array_merge($offer, $validated);

        if ($validated['offer_status'] === 'Sent' && empty($offer['sent_at'])) {
            $offer['sent_at'] = now()->toIso8601String();
            $offer['sent_at_formatted'] = now()->format('M d, Y h:i A');
        }

        if ($validated['offer_status'] === 'Accepted') {
            $offer['accepted_at'] = now()->toIso8601String();
            $offer['accepted_at_formatted'] = now()->format('M d, Y h:i A');
            // Move to Pre-Employment automatically
            $applicant->status = 'Pre-Employment';
            if (empty($applicant->pre_employment_requirements)) {
                $applicant->pre_employment_requirements = $this->getDefaultPreEmploymentRequirements();
            }
            if (empty($applicant->preboarding_tasks)) {
                $applicant->preboarding_tasks = $this->getDefaultPreboardingTasks();
            }
        } elseif ($validated['offer_status'] === 'Declined') {
            $offer['declined_at'] = now()->toIso8601String();
            $applicant->status = 'Offer Declined';
        }

        $applicant->offer_data = $offer;
        $applicant->save();

        $applicant->logActivity(
            "Job Offer ({$validated['offer_status']})",
            "Offer for '{$validated['position']}' at ₱" . number_format($validated['basic_salary'], 2) . "/mo. Status: {$validated['offer_status']}."
        );

        AuditLogger::log('Update', 'Recruitment', $applicant->id, "Updated job offer for {$applicant->full_name} ({$validated['offer_status']})");

        return redirect()->back()->with('success', "Job offer updated. Status: '{$validated['offer_status']}'.");
    }

    /**
     * Record Offer Acceptance or Decline directly.
     */
    public function applicantOfferStatus(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $action = $request->input('action'); // 'accept' or 'decline' or 'send'

        $offer = $applicant->offer_data ?? [];

        if ($action === 'send') {
            $offer['offer_status'] = 'Sent';
            $offer['sent_at'] = now()->toIso8601String();
            $offer['sent_at_formatted'] = now()->format('M d, Y h:i A');
            $applicant->logActivity("Job Offer Sent", "Formal job offer document dispatched to candidate email ({$applicant->email}).");
        } elseif ($action === 'accept') {
            $offer['offer_status'] = 'Accepted';
            $offer['accepted_at'] = now()->toIso8601String();
            $offer['accepted_at_formatted'] = now()->format('M d, Y h:i A');
            $applicant->status = 'Pre-Employment';
            if (empty($applicant->pre_employment_requirements)) {
                $applicant->pre_employment_requirements = $this->getDefaultPreEmploymentRequirements();
            }
            if (empty($applicant->preboarding_tasks)) {
                $applicant->preboarding_tasks = $this->getDefaultPreboardingTasks();
            }
            $applicant->logActivity("Offer Accepted", "Candidate officially accepted job offer. Candidate automatically transitioned to Pre-Employment stage.");
        } elseif ($action === 'decline') {
            $offer['offer_status'] = 'Declined';
            $offer['declined_at'] = now()->toIso8601String();
            $offer['decline_reason'] = $request->input('reason', 'Candidate declined terms');
            $applicant->status = 'Offer Declined';
            $applicant->logActivity("Offer Declined", "Candidate declined the job offer. Reason: {$offer['decline_reason']}");
        }

        $applicant->offer_data = $offer;
        $applicant->save();

        return redirect()->back()->with('success', "Job offer action '{$action}' recorded successfully.");
    }

    /**
     * Update individual Pre-Employment Requirement item.
     */
    public function applicantRequirementUpdate(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'req_id' => 'required|string',
            'status' => 'required|in:Pending,Submitted,For Review,Approved,Rejected',
            'file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:10240',
            'remarks' => 'nullable|string|max:500',
            'expiration_date' => 'nullable|date',
        ]);

        $requirements = $applicant->pre_employment_requirements ?? $this->getDefaultPreEmploymentRequirements();
        $found = false;
        $itemName = '';

        foreach ($requirements as &$req) {
            if ($req['id'] === $validated['req_id']) {
                $req['status'] = $validated['status'];
                $req['remarks'] = $validated['remarks'] ?? $req['remarks'] ?? null;
                $req['expiration_date'] = $validated['expiration_date'] ?? $req['expiration_date'] ?? null;
                $req['date_reviewed'] = now()->format('M d, Y');
                $req['reviewer_name'] = Auth::user()?->full_name ?? Auth::user()?->name ?? 'HR Officer';
                $itemName = $req['name'];

                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('recruitment/requirements', 'public');
                    $req['file_path'] = $path;
                    $req['file_name'] = $request->file('file')->getClientOriginalName();
                    $req['date_submitted'] = now()->format('M d, Y');
                }
                $found = true;
                break;
            }
        }

        if ($found) {
            $applicant->pre_employment_requirements = $requirements;
            $applicant->save();

            $stats = $applicant->requirements_stats;
            $applicant->logActivity(
                "Requirement Updated: {$itemName}",
                "Status set to {$validated['status']} ({$stats['completed']}/{$stats['total']} requirements completed)."
            );

            return redirect()->back()->with('success', "Requirement '{$itemName}' updated to {$validated['status']}.");
        }

        return redirect()->back()->with('error', "Requirement item not found.");
    }

    /**
     * Update Preboarding Checklist Task.
     */
    public function applicantPreboardingUpdate(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'task_id' => 'required|string',
            'status' => 'required|in:Pending,In Progress,Completed',
            'assignee' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $tasks = $applicant->preboarding_tasks ?? $this->getDefaultPreboardingTasks();
        $taskName = '';

        foreach ($tasks as &$task) {
            if ($task['id'] === $validated['task_id']) {
                $task['status'] = $validated['status'];
                $task['assignee'] = $validated['assignee'] ?? $task['assignee'];
                $task['due_date'] = $validated['due_date'] ?? $task['due_date'];
                $task['remarks'] = $validated['remarks'] ?? $task['remarks'];
                if ($validated['status'] === 'Completed') {
                    $task['completion_date'] = now()->format('M d, Y');
                } else {
                    $task['completion_date'] = null;
                }
                $taskName = $task['task_name'];
                break;
            }
        }

        $applicant->preboarding_tasks = $tasks;
        $applicant->save();

        $stats = $applicant->preboarding_stats;
        $applicant->logActivity(
            "Preboarding Task: {$taskName}",
            "Status updated to {$validated['status']} ({$stats['completed']}/{$stats['total']} tasks completed)."
        );

        return redirect()->back()->with('success', "Preboarding task updated to '{$validated['status']}'.");
    }

    /**
     * Update Onboarding Record & Checklist.
     */
    public function applicantOnboardingUpdate(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'task_index' => 'nullable|integer',
            'task_status' => 'nullable|in:Pending,In Progress,Completed',
            'progress' => 'nullable|integer|min:0|max:100',
            'complete_onboarding' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:500',
        ]);

        $onboarding = $applicant->onboarding_data ?? [];

        if (isset($validated['task_index']) && isset($onboarding['tasks'][$validated['task_index']])) {
            $onboarding['tasks'][$validated['task_index']]['status'] = $validated['task_status'];
        }

        if (isset($validated['progress'])) {
            $onboarding['progress'] = $validated['progress'];
        }

        // If completed 100% or button clicked
        if (!empty($validated['complete_onboarding']) || ($validated['progress'] ?? 0) >= 100) {
            $onboarding['progress'] = 100;
            $onboarding['completed_at'] = now()->format('M d, Y h:i A');
            $onboarding['completed_by'] = Auth::user()?->full_name ?? Auth::user()?->name ?? 'HR Manager';

            // Mark all onboarding tasks completed
            if (!empty($onboarding['tasks'])) {
                foreach ($onboarding['tasks'] as &$t) {
                    $t['status'] = 'Completed';
                }
            }

            // Activate employee across HRIS suite!
            if ($applicant->hiredAsEmployee) {
                $applicant->hiredAsEmployee->update([
                    'employment_status' => 'Active',
                ]);
            }

            $applicant->logActivity(
                "Onboarding Completed (100%)",
                "Employee onboarding marked 100% complete by {$onboarding['completed_by']}. Staff profile fully activated across all HRIS modules."
            );
        }

        $applicant->onboarding_data = $onboarding;
        $applicant->save();

        return redirect()->back()->with('success', "Onboarding record updated successfully.");
    }

    /**
     * Convert Hired Applicant into Employee record without duplicate data entry.
     * Generates EMP-YYYY-XXX, transfers personal, contact, education, work history,
     * documents, and establishes Applicant -> Employee relationship.
     */
    public function applicantConvertToEmployee(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);

        if ($applicant->hired_as_employee_id) {
            return redirect()->back()->with('error', "Applicant already converted to Employee ID: {$applicant->hiredAsEmployee?->employee_id}.");
        }

        // Generate next automatic Employee ID if not supplied (e.g. EMP-2026-507)
        $currentYear = date('Y');
        $lastEmp = Employee::where('employee_id', 'like', "EMP-{$currentYear}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 501;
        if ($lastEmp && preg_match('/EMP-\d{4}-(\d+)/', $lastEmp->employee_id, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        $autoEmpId = "EMP-{$currentYear}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $offer = $applicant->offer_data ?? [];

        $validated = $request->validate([
            'employee_id' => 'nullable|string|max:30|unique:hr_employees,employee_id',
            'branch_id' => 'required|exists:hr_branches,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'position_id' => 'nullable|exists:hr_positions,id',
            'supervisor_id' => 'nullable|exists:hr_employees,id',
            'date_hired' => 'required|date',
            'employment_status' => 'required|in:Active,Probationary',
            'employment_type' => 'required|in:Regular,Probationary,Part-time,Casual,Contractual',
            'basic_salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:Monthly,Daily,Hourly',
            'pay_frequency' => 'required|in:Semi-Monthly,Monthly,Weekly',
            'allowances' => 'nullable|numeric|min:0',
            'work_location' => 'nullable|string|max:150',
            'work_schedule' => 'nullable|string|max:150',
        ]);

        $finalEmpId = !empty($validated['employee_id']) ? $validated['employee_id'] : $autoEmpId;

        // Verify uniqueness again
        if (Employee::where('employee_id', $finalEmpId)->exists()) {
            $finalEmpId = "EMP-{$currentYear}-" . str_pad($nextNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        DB::beginTransaction();
        try {
            // Create Employee Record with full data transfer
            $employee = Employee::create([
                'employee_id' => $finalEmpId,
                'first_name' => $applicant->first_name,
                'middle_name' => $applicant->middle_name,
                'last_name' => $applicant->last_name,
                'suffix' => $applicant->suffix,
                'date_of_birth' => $applicant->date_of_birth,
                'gender' => $applicant->gender,
                'civil_status' => $applicant->civil_status ?: 'Single',
                'nationality' => $applicant->nationality ?: 'Filipino',
                'email' => $applicant->email,
                'mobile_number' => $applicant->contact_number,
                'address' => $applicant->address,
                'present_address' => $applicant->address,
                'permanent_address' => ($applicant->city || $applicant->province) ? ($applicant->city . ', ' . $applicant->province) : $applicant->address,
                'branch_id' => $validated['branch_id'],
                'department_id' => $validated['department_id'],
                'position_id' => $validated['position_id'],
                'supervisor_id' => $validated['supervisor_id'] ?? ($offer['supervisor_id'] ?? null),
                'date_hired' => $validated['date_hired'],
                'employment_status' => $validated['employment_status'],
                'employment_type' => $validated['employment_type'],
                'basic_salary' => $validated['basic_salary'],
                'salary_type' => $validated['salary_type'],
                'pay_frequency' => $validated['pay_frequency'],
                'allowances' => $validated['allowances'] ?? ($offer['allowances'] ?? 0),
                'work_location' => $validated['work_location'] ?? ($offer['work_location'] ?? 'Main Branch'),
                'work_schedule' => $validated['work_schedule'] ?? ($offer['work_schedule'] ?? 'Standard Shift'),
                'education_history' => $applicant->education,
            ]);

            // Record initial employment movement history (Hiring)
            EmploymentHistory::create([
                'employee_id' => $employee->id,
                'action_type' => 'Hiring',
                'previous_value' => 'Applicant (' . $applicant->status . ')',
                'new_value' => $employee->position?->name ?? $applicant->applied_position,
                'remarks' => "Converted from Talent Acquisition applicant record #{$applicant->id}. Initial hire.",
                'effective_date' => $employee->date_hired,
                'recorded_by' => Auth::id(),
            ]);

            // Transfer applicant resume to employee 201 file documents
            if ($applicant->resume_path) {
                EmployeeDocument::create([
                    'employee_id' => $employee->id,
                    'document_type' => 'Resume / CV',
                    'document_name' => 'Candidate Resume - ' . $applicant->full_name,
                    'category' => 'Personal',
                    'file_path' => $applicant->resume_path,
                    'status' => 'Active',
                    'is_verified' => true,
                    'uploaded_by' => Auth::id(),
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                    'notes' => 'Imported automatically during Talent Acquisition hiring conversion',
                ]);
            }

            // Transfer any uploaded pre-employment requirement documents to employee documents
            if (!empty($applicant->pre_employment_requirements) && is_array($applicant->pre_employment_requirements)) {
                foreach ($applicant->pre_employment_requirements as $req) {
                    if (!empty($req['file_path'])) {
                        EmployeeDocument::create([
                            'employee_id' => $employee->id,
                            'document_type' => $req['name'] ?? 'Pre-Employment Document',
                            'document_name' => ($req['name'] ?? 'Document') . ' - ' . $applicant->full_name,
                            'category' => ($req['category'] ?? '') === 'Government' ? 'Statutory' : 'Personal',
                            'file_path' => $req['file_path'],
                            'status' => 'Active',
                            'is_verified' => ($req['status'] ?? '') === 'Approved',
                            'uploaded_by' => Auth::id(),
                            'verified_by' => ($req['status'] ?? '') === 'Approved' ? Auth::id() : null,
                            'verified_at' => ($req['status'] ?? '') === 'Approved' ? now() : null,
                            'notes' => $req['remarks'] ?? 'Pre-employment requirement submitted during onboarding',
                        ]);
                    }
                }
            }

            // Initialize comprehensive Onboarding record for employee
            $defaultOnboarding = [
                'progress' => 65,
                'employee_name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'position' => $employee->position?->name ?? $applicant->applied_position,
                'department' => $employee->department?->name ?? 'Operations',
                'start_date' => $employee->date_hired?->format('F d, Y') ?? date('F d, Y'),
                'manager' => $employee->supervisor?->full_name ?? 'Branch Manager',
                'tasks' => [
                    ['id' => 'onb_1', 'group' => 'HR', 'title' => 'Verify employee 201 masterfile & statutory registrations', 'status' => 'Completed', 'due' => 'Day 1'],
                    ['id' => 'onb_2', 'group' => 'HR', 'title' => 'Conduct company culture and HR policies orientation', 'status' => 'In Progress', 'due' => 'Day 1'],
                    ['id' => 'onb_3', 'group' => 'IT', 'title' => 'Create company email & issue biometric PIN', 'status' => 'Completed', 'due' => 'Day 1'],
                    ['id' => 'onb_4', 'group' => 'IT', 'title' => 'Configure POS & HRIS Self-Service system access', 'status' => 'In Progress', 'due' => 'Day 2'],
                    ['id' => 'onb_5', 'group' => 'Admin', 'title' => 'Issue official employee ID badge & uniform set', 'status' => 'Completed', 'due' => 'Day 1'],
                    ['id' => 'onb_6', 'group' => 'Admin', 'title' => 'Assign locker and premise access card', 'status' => 'In Progress', 'due' => 'Day 1'],
                    ['id' => 'onb_7', 'group' => 'Manager', 'title' => 'Introduce to team and assign workstation mentor', 'status' => 'In Progress', 'due' => 'Day 1'],
                    ['id' => 'onb_8', 'group' => 'Manager', 'title' => 'Review job responsibilities & first-week roster plan', 'status' => 'Pending', 'due' => 'Day 2'],
                    ['id' => 'onb_9', 'group' => 'Employee', 'title' => 'Review and sign employee handbook acknowledgment', 'status' => 'Pending', 'due' => 'Day 3'],
                    ['id' => 'onb_10', 'group' => 'Employee', 'title' => 'Complete food safety & hygiene training compliance', 'status' => 'Pending', 'due' => 'Week 1'],
                ],
                'training' => [
                    ['name' => 'Company Orientation & Culture', 'status' => 'Completed', 'trainer' => 'HR Operations', 'date' => now()->format('Y-m-d')],
                    ['name' => 'Company Policies & Anti-Harassment', 'status' => 'In Progress', 'trainer' => 'HR Operations', 'date' => now()->format('Y-m-d')],
                    ['name' => 'Food Safety & Restaurant Hygiene', 'status' => 'Pending', 'trainer' => 'Safety Officer', 'date' => null],
                    ['name' => 'Job-Specific Work Station Training', 'status' => 'Pending', 'trainer' => 'Shift Supervisor', 'date' => null],
                ],
                'access' => [
                    ['system' => 'Company Email', 'status' => 'Active', 'account' => strtolower($employee->first_name . '.' . $employee->last_name) . '@restaurant.com'],
                    ['system' => 'HRIS & Self-Service', 'status' => 'Active', 'account' => $employee->employee_id],
                    ['system' => 'Attendance Biometric / DTR', 'status' => 'Active', 'account' => 'Enrolled'],
                    ['system' => 'POS & Inventory Station', 'status' => 'Pending', 'account' => 'Provisioning'],
                ],
            ];

            // Update applicant: link to new employee record, mark Hired
            $applicant->update([
                'status' => 'Hired',
                'hired_as_employee_id' => $employee->id,
                'onboarding_data' => $defaultOnboarding,
            ]);

            $applicant->logActivity(
                "Converted to Employee: {$employee->employee_id}",
                "Successfully converted candidate to Employee '{$employee->full_name}' ({$employee->employee_id}) in Employee Management."
            );

            DB::commit();

            AuditLogger::log('Create', 'Employees', $employee->id, "Converted hired applicant {$applicant->full_name} to employee {$employee->employee_id}");

            return redirect()->route('hr.people.employees.show', $employee->id)
                ->with('success', "Applicant {$applicant->full_name} successfully converted to Employee {$employee->employee_id}. Onboarding workflow initialized.");
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Failed to convert applicant to employee: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return redirect()->back()->with('error', "Failed to convert applicant to employee: " . $e->getMessage());
        }
    }

    // ==========================================
    // 3. INTERVIEWS LIFECYCLE
    // ==========================================

    public function interviewsIndex(Request $request): View
    {
        $query = Interview::with(['applicant.jobVacancy', 'interviewer']);

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('applicant', function ($aq) use ($term) {
                    $aq->where('first_name', 'like', $term)
                       ->orWhere('last_name', 'like', $term)
                       ->orWhere('middle_name', 'like', $term)
                       ->orWhere('email', 'like', $term)
                       ->orWhere('applied_position', 'like', $term);
                })
                ->orWhere('interviewer_name', 'like', $term)
                ->orWhere('interview_stage', 'like', $term)
                ->orWhere('location_or_link', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('stage')) {
            $query->where('interview_stage', $request->stage);
        }
        if ($request->filled('type')) {
            $query->where('interview_type', $request->type);
        }
        if ($request->filled('interviewer_id')) {
            $query->where('interviewer_id', $request->interviewer_id);
        }
        if ($request->filled('date')) {
            $query->whereDate('interview_date', $request->date);
        }

        $perPage = (int) $request->get('per_page', 10);
        $interviews = $query->orderBy('interview_date', 'asc')->paginate($perPage)->withQueryString();

        // Candidates eligible for interview
        $applicants = Applicant::whereNotIn('status', ['Hired', 'Rejected', 'Withdrawn', 'Offer Declined'])->get();
        $users = User::all();

        // Interview statistics
        $interviewStats = [
            'scheduled' => Interview::where('status', 'Scheduled')->count(),
            'confirmed' => Interview::where('status', 'Confirmed')->count(),
            'completed' => Interview::where('status', 'Completed')->count(),
            'rescheduled' => Interview::where('status', 'Rescheduled')->count(),
            'cancelled' => Interview::where('status', 'Cancelled')->count(),
            'no_show' => Interview::where('status', 'No Show')->count(),
        ];

        return view('hr.recruitment.interviews', compact(
            'interviews',
            'applicants',
            'users',
            'interviewStats'
        ));
    }

    public function interviewStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'interview_date' => 'required|date',
            'interview_time' => 'nullable|string|max:20',
            'interviewer_id' => 'nullable|exists:users,id',
            'interviewer_name' => 'nullable|string|max:100',
            'interview_type' => 'required|string|max:50',
            'interview_stage' => 'required|string|max:50',
            'location_or_link' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'status' => 'required|in:Scheduled,Confirmed,Completed,Rescheduled,Cancelled,No Show',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['interviewer_id']) && empty($validated['interviewer_name'])) {
            $interviewerUser = User::find($validated['interviewer_id']);
            $validated['interviewer_name'] = $interviewerUser?->full_name ?? $interviewerUser?->name;
        }

        $interview = Interview::create($validated);
        $applicant = $interview->applicant;

        // Auto move applicant to Interview stage if still New/Screening
        if (in_array($applicant->status, ['New', 'Screening', 'Shortlisted'])) {
            $applicant->status = 'Interview';
            $applicant->save();
        }

        $applicant->logActivity(
            "Interview Scheduled: {$interview->interview_stage}",
            "Scheduled {$interview->interview_type} ({$interview->interview_stage}) with {$interview->interviewer_name} on " . \Carbon\Carbon::parse($interview->interview_date)->format('M d, Y') . " {$interview->interview_time}."
        );

        AuditLogger::log('Create', 'Recruitment', $interview->id, "Scheduled interview for applicant {$applicant->full_name}");

        return redirect()->back()->with('success', "Interview scheduled successfully for {$applicant->full_name}.");
    }

    public function interviewUpdate(Request $request, int $id): RedirectResponse
    {
        $interview = Interview::findOrFail($id);
        $validated = $request->validate([
            'interview_date' => 'required|date',
            'interview_time' => 'nullable|string|max:20',
            'interviewer_id' => 'nullable|exists:users,id',
            'interviewer_name' => 'nullable|string|max:100',
            'interview_type' => 'required|string|max:50',
            'interview_stage' => 'required|string|max:50',
            'location_or_link' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'status' => 'required|in:Scheduled,Confirmed,Completed,Rescheduled,Cancelled,No Show',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['interviewer_id']) && empty($validated['interviewer_name'])) {
            $interviewerUser = User::find($validated['interviewer_id']);
            $validated['interviewer_name'] = $interviewerUser?->full_name ?? $interviewerUser?->name;
        }

        $interview->update($validated);
        $interview->applicant?->logActivity(
            "Interview Updated: {$interview->interview_stage}",
            "Status: {$interview->status}. Date: " . \Carbon\Carbon::parse($interview->interview_date)->format('M d, Y') . " {$interview->interview_time}."
        );

        AuditLogger::log('Update', 'Recruitment', $interview->id, "Updated interview schedule #{$interview->id}");

        return redirect()->back()->with('success', "Interview schedule updated successfully.");
    }

    /**
     * Submit structured interview scorecard:
     * Communication (1-5), Technical Skills (1-5), Experience (1-5),
     * Problem Solving (1-5), Team Fit (1-5), Overall Rating (1-5), Recommendation.
     */
    public function interviewEvaluate(Request $request, int $id): RedirectResponse
    {
        $interview = Interview::findOrFail($id);
        $validated = $request->validate([
            'rating_communication' => 'required|integer|min:1|max:5',
            'rating_technical' => 'required|integer|min:1|max:5',
            'rating_experience' => 'required|integer|min:1|max:5',
            'rating_problem_solving' => 'required|integer|min:1|max:5',
            'rating_team_fit' => 'required|integer|min:1|max:5',
            'rating_overall' => 'required|integer|min:1|max:5',
            'recommendation' => 'required|in:Proceed,Hold,Reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        $scorecard = [
            'communication' => $validated['rating_communication'],
            'technical_skills' => $validated['rating_technical'],
            'experience' => $validated['rating_experience'],
            'problem_solving' => $validated['rating_problem_solving'],
            'team_fit' => $validated['rating_team_fit'],
            'overall' => $validated['rating_overall'],
            'recommendation' => $validated['recommendation'],
            'comments' => $validated['notes'] ?? '',
            'evaluated_by' => Auth::user()?->full_name ?? Auth::user()?->name ?? 'Interviewer',
            'evaluated_at' => now()->format('M d, Y h:i A'),
        ];

        $interview->update([
            'rating' => $validated['rating_overall'],
            'recommendation' => $validated['recommendation'],
            'notes' => $validated['notes'],
            'scorecard' => $scorecard,
            'status' => 'Completed',
        ]);

        $applicant = $interview->applicant;
        $applicant->logActivity(
            "Interview Evaluated: {$interview->interview_stage}",
            "Scorecard completed. Overall rating: {$validated['rating_overall']}/5. Recommendation: {$validated['recommendation']}."
        );

        AuditLogger::log('Update', 'Recruitment', $interview->id, "Evaluated interview scorecard for applicant #{$applicant->id}");

        return redirect()->back()->with('success', "Interview evaluation recorded with rating {$validated['rating_overall']}/5.");
    }

    public function interviewStatusUpdate(Request $request, int $id): RedirectResponse
    {
        $interview = Interview::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:Scheduled,Confirmed,Completed,Rescheduled,Cancelled,No Show',
        ]);

        $interview->update(['status' => $validated['status']]);
        $interview->applicant?->logActivity(
            "Interview Status: {$validated['status']}",
            "Interview #{$interview->id} status changed to {$validated['status']}."
        );

        return redirect()->back()->with('success', "Interview status updated to {$validated['status']}.");
    }

    // ==========================================
    // 4. ASSESSMENTS
    // ==========================================

    public function assessmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'assessment_type' => 'required|string|max:60',
            'title' => 'required|string|max:150',
            'assessment_date' => 'required|date',
            'score' => 'required|numeric|min:0',
            'passing_score' => 'required|numeric|min:0',
            'result' => 'required|in:Passed,Failed,Pending',
            'evaluator_id' => 'nullable|exists:users,id',
            'evaluator_name' => 'nullable|string|max:100',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,zip,jpg,png|max:10240',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['evaluator_id']) && empty($validated['evaluator_name'])) {
            $user = User::find($validated['evaluator_id']);
            $validated['evaluator_name'] = $user?->full_name ?? $user?->name;
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('recruitment/assessments', 'public');
        }

        $assessment = Assessment::create($validated);
        $applicant = $assessment->applicant;

        $applicant->logActivity(
            "Assessment Completed: {$assessment->title}",
            "Type: {$assessment->assessment_type}. Score: {$assessment->score}/{$assessment->passing_score} ({$assessment->result}). Evaluator: {$assessment->evaluator_name}."
        );

        AuditLogger::log('Create', 'Recruitment', $assessment->id, "Recorded assessment '{$assessment->title}' for applicant #{$applicant->id}");

        return redirect()->back()->with('success', "Assessment result recorded successfully.");
    }

    public function assessmentUpdate(Request $request, int $id): RedirectResponse
    {
        $assessment = Assessment::findOrFail($id);
        $validated = $request->validate([
            'assessment_type' => 'required|string|max:60',
            'title' => 'required|string|max:150',
            'assessment_date' => 'required|date',
            'score' => 'required|numeric|min:0',
            'passing_score' => 'required|numeric|min:0',
            'result' => 'required|in:Passed,Failed,Pending',
            'evaluator_name' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('recruitment/assessments', 'public');
        }

        $assessment->update($validated);

        return redirect()->back()->with('success', "Assessment record updated.");
    }

    public function assessmentDestroy(int $id): RedirectResponse
    {
        $assessment = Assessment::findOrFail($id);
        $applicantId = $assessment->applicant_id;
        $assessment->delete();

        AuditLogger::log('Delete', 'Recruitment', $id, "Deleted assessment record for applicant #{$applicantId}");

        return redirect()->back()->with('success', "Assessment record deleted.");
    }

    // ==========================================
    // DEFAULT TEMPLATES (Pre-Employment & Preboarding)
    // ==========================================

    /**
     * Default 14 Standard Philippine Pre-Employment Requirements.
     */
    protected function getDefaultPreEmploymentRequirements(): array
    {
        return [
            // Statutory / Government
            [
                'id' => 'req_govt_id',
                'category' => 'Government',
                'name' => 'Government ID',
                'description' => 'Valid government-issued photo ID (Passport, UMID, PhilSys National ID, or Driver\'s License)',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_psa_birth',
                'category' => 'Government',
                'name' => 'PSA Birth Certificate',
                'description' => 'Original or clear authenticated copy of PSA Birth Certificate',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_sss',
                'category' => 'Government',
                'name' => 'SSS Member Record (E-1 / Static Info)',
                'description' => 'SSS Form E-1, SSS ID card, or online static member information sheet',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_philhealth',
                'category' => 'Government',
                'name' => 'PhilHealth MDR / ID',
                'description' => 'PhilHealth Member Data Record (MDR) or Member ID card',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_pagibig',
                'category' => 'Government',
                'name' => 'Pag-IBIG Member ID (MDF)',
                'description' => 'Pag-IBIG Member Data Form (MDF) or Pag-IBIG Loyalty Card Plus',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_tin',
                'category' => 'Government',
                'name' => 'TIN / BIR Form 1902 / 1905',
                'description' => 'BIR Tax Identification Number (TIN) card or stamped Form 1902 / 1905',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_nbi',
                'category' => 'Government',
                'name' => 'NBI Clearance',
                'description' => 'Valid National Bureau of Investigation (NBI) Clearance (valid for at least 6 months)',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],

            // Employment Documents
            [
                'id' => 'req_signed_offer',
                'category' => 'Employment',
                'name' => 'Signed Job Offer',
                'description' => 'Official job offer letter counter-signed by the candidate',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_contract',
                'category' => 'Employment',
                'name' => 'Employment Contract',
                'description' => 'Formal employment agreement signed by employee and authorized company officer',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_nda',
                'category' => 'Employment',
                'name' => 'Non-Disclosure Agreement (NDA)',
                'description' => 'Company proprietary confidentiality and data protection agreement',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_medical',
                'category' => 'Employment',
                'name' => 'Medical Certificate (Fit-to-Work)',
                'description' => 'Pre-employment medical examination result and accredited fit-to-work clearance',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_previous_coe',
                'category' => 'Employment',
                'name' => 'Previous Employment COE / Clearance',
                'description' => 'Certificate of Employment and Certificate of Final Pay/Clearance from previous employer',
                'is_required' => false,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_diploma',
                'category' => 'Employment',
                'name' => 'Transcript / Diploma / Certificates',
                'description' => 'College Diploma, Transcript of Records (TOR), or TESDA NC-II vocational certificate',
                'is_required' => false,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'req_company_forms',
                'category' => 'Employment',
                'name' => 'Company 201 Personal Data Sheet',
                'description' => 'Filled-out employee information sheet, emergency contacts, and beneficiary forms',
                'is_required' => true,
                'status' => 'Pending',
                'file_path' => null,
                'file_name' => null,
                'date_submitted' => null,
                'date_reviewed' => null,
                'reviewer_name' => null,
                'expiration_date' => null,
                'remarks' => null,
            ],
        ];
    }

    /**
     * Default Preboarding Checklist Tasks across HR, IT, Admin, Manager.
     */
    protected function getDefaultPreboardingTasks(): array
    {
        return [
            // HR Tasks
            [
                'id' => 'pb_hr_1',
                'department' => 'HR',
                'task_name' => 'Create employee record',
                'description' => 'Prepare candidate masterfile and setup profile in HRIS',
                'assignee' => 'HR Operations',
                'status' => 'Pending',
                'due_date' => now()->addDays(2)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_hr_2',
                'department' => 'HR',
                'task_name' => 'Verify requirements',
                'description' => 'Review and approve statutory IDs and government credentials',
                'assignee' => 'HR Compliance',
                'status' => 'Pending',
                'due_date' => now()->addDays(4)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_hr_3',
                'department' => 'HR',
                'task_name' => 'Prepare contract',
                'description' => 'Draft and print formal employment contract & handbook',
                'assignee' => 'HR Operations',
                'status' => 'Pending',
                'due_date' => now()->addDays(3)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_hr_4',
                'department' => 'HR',
                'task_name' => 'Assign employee number',
                'description' => 'Generate and reserve unique employee ID code',
                'assignee' => 'HR Operations',
                'status' => 'Pending',
                'due_date' => now()->addDays(2)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],

            // IT Tasks
            [
                'id' => 'pb_it_1',
                'department' => 'IT',
                'task_name' => 'Create company email',
                'description' => 'Setup @restaurant.com mailbox and directory listing',
                'assignee' => 'IT Support',
                'status' => 'Pending',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_it_2',
                'department' => 'IT',
                'task_name' => 'Create system accounts',
                'description' => 'Provision HRIS self-service login and POS terminal operator ID',
                'assignee' => 'IT Support',
                'status' => 'Pending',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_it_3',
                'department' => 'IT',
                'task_name' => 'Prepare computer',
                'description' => 'Set up workstation, keyboard, monitor, or POS terminal assignment',
                'assignee' => 'IT Support',
                'status' => 'Pending',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_it_4',
                'department' => 'IT',
                'task_name' => 'Configure software access',
                'description' => 'Grant role-based permissions, POS register access, and network privileges',
                'assignee' => 'IT Support',
                'status' => 'Pending',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],

            // ADMIN Tasks
            [
                'id' => 'pb_adm_1',
                'department' => 'ADMIN',
                'task_name' => 'Employee ID',
                'description' => 'Print physical photo ID card and company lanyard',
                'assignee' => 'Admin Services',
                'status' => 'Pending',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_adm_2',
                'department' => 'ADMIN',
                'task_name' => 'Workstation',
                'description' => 'Assign locker, desk space, and kitchen station allocation',
                'assignee' => 'Admin Services',
                'status' => 'Pending',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_adm_3',
                'department' => 'ADMIN',
                'task_name' => 'Uniform',
                'description' => 'Issue restaurant chef coat, apron, cap, and name badge',
                'assignee' => 'Admin Services',
                'status' => 'Pending',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_adm_4',
                'department' => 'ADMIN',
                'task_name' => 'Access card',
                'description' => 'Enroll biometric fingerprint & issue branch entrance door card',
                'assignee' => 'Admin Services',
                'status' => 'Pending',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],

            // MANAGER Tasks
            [
                'id' => 'pb_mgr_1',
                'department' => 'MANAGER',
                'task_name' => 'Assign supervisor',
                'description' => 'Assign direct reporting supervisor and buddy mentor',
                'assignee' => 'Branch Manager',
                'status' => 'Pending',
                'due_date' => now()->addDays(3)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_mgr_2',
                'department' => 'MANAGER',
                'task_name' => 'Assign team',
                'description' => 'Place in kitchen / front-of-house shift unit roster',
                'assignee' => 'Branch Manager',
                'status' => 'Pending',
                'due_date' => now()->addDays(4)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_mgr_3',
                'department' => 'MANAGER',
                'task_name' => 'Prepare work schedule',
                'description' => 'Plot shift schedule in weekly timekeeping planner',
                'assignee' => 'Shift Supervisor',
                'status' => 'Pending',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
            [
                'id' => 'pb_mgr_4',
                'department' => 'MANAGER',
                'task_name' => 'Prepare first-week plan',
                'description' => 'Outline day 1 through day 7 onboarding milestones and stations',
                'assignee' => 'Branch Manager',
                'status' => 'Pending',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'completion_date' => null,
                'remarks' => null,
            ],
        ];
    }
}
