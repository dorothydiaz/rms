<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Applicant;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Interview;
use App\Models\Hr\JobVacancy;
use App\Models\Hr\Position;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    // ==========================================
    // 1. JOB VACANCIES
    // ==========================================

    public function vacanciesIndex(Request $request): View
    {
        $query = JobVacancy::with(['position', 'department', 'branch'])->withCount('applicants');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $perPage = (int) $request->get('per_page', 10);
        $vacancies = $query->orderBy('opening_date', 'desc')->paginate($perPage)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $positions = Position::all();

        return view('hr.recruitment.vacancies', compact('vacancies', 'branches', 'departments', 'positions'));
    }

    public function vacancyStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'position_id' => 'nullable|exists:hr_positions,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'number_of_openings' => 'required|integer|min:1',
            'employment_type' => 'required|in:Regular,Probationary,Part-time,Casual,Contractual',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'job_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'opening_date' => 'required|date',
            'closing_date' => 'nullable|date|after_or_equal:opening_date',
            'status' => 'required|in:Draft,Open,On Hold,Closed,Filled',
        ]);

        $vacancy = JobVacancy::create($validated);
        AuditLogger::log('Create', 'Recruitment', $vacancy->id, "Created job vacancy '{$vacancy->title}'");

        return redirect()->back()->with('success', "Job vacancy '{$vacancy->title}' created.");
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
            'employment_type' => 'required|in:Regular,Probationary,Part-time,Casual,Contractual',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'job_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'opening_date' => 'required|date',
            'closing_date' => 'nullable|date',
            'status' => 'required|in:Draft,Open,On Hold,Closed,Filled',
        ]);

        $vacancy->update($validated);
        AuditLogger::log('Update', 'Recruitment', $vacancy->id, "Updated job vacancy '{$vacancy->title}'");

        return redirect()->back()->with('success', "Job vacancy updated successfully.");
    }

    // ==========================================
    // 2. APPLICANTS
    // ==========================================

    public function applicantsIndex(Request $request): View
    {
        $query = Applicant::with(['jobVacancy', 'interviews', 'hiredAsEmployee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('applied_position', 'like', $term);
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $applicants = $query->orderBy('application_date', 'desc')->paginate($perPage)->withQueryString();
        $vacancies = JobVacancy::whereIn('status', ['Open', 'Draft'])->get();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $positions = Position::all();

        return view('hr.recruitment.applicants', compact('applicants', 'vacancies', 'branches', 'departments', 'positions'));
    }

    public function applicantStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'job_vacancy_id' => 'nullable|exists:hr_job_vacancies,id',
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'contact_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'applied_position' => 'required|string|max:100',
            'source' => 'required|string|max:50',
            'application_date' => 'required|date',
            'status' => 'required|in:New,Screening,Interview,Assessment,Shortlisted,Job Offer,Hired,Rejected',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('resume')) {
            $validated['resume_path'] = $request->file('resume')->store('resumes', 'public');
        }

        $applicant = Applicant::create($validated);
        AuditLogger::log('Create', 'Recruitment', $applicant->id, "Created applicant record for {$applicant->full_name}");

        return redirect()->back()->with('success', "Applicant {$applicant->full_name} registered successfully.");
    }

    public function applicantUpdate(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'contact_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'applied_position' => 'required|string|max:100',
            'source' => 'required|string|max:50',
            'status' => 'required|in:New,Screening,Interview,Assessment,Shortlisted,Job Offer,Hired,Rejected',
        ]);

        $applicant->update($validated);
        AuditLogger::log('Update', 'Recruitment', $applicant->id, "Updated applicant {$applicant->full_name} status to {$applicant->status}");

        return redirect()->back()->with('success', "Applicant record updated.");
    }

    /**
     * Convert Hired Applicant into Employee record.
     */
    public function applicantConvertToEmployee(Request $request, int $id): RedirectResponse
    {
        $applicant = Applicant::findOrFail($id);
        if ($applicant->hired_as_employee_id) {
            return redirect()->back()->with('error', "Applicant already converted to Employee ID: {$applicant->hiredAsEmployee?->employee_id}.");
        }

        $validated = $request->validate([
            'employee_id' => 'required|string|max:30|unique:hr_employees,employee_id',
            'branch_id' => 'required|exists:hr_branches,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'position_id' => 'nullable|exists:hr_positions,id',
            'date_hired' => 'required|date',
            'employment_status' => 'required|in:Active,Probationary',
            'employment_type' => 'required|in:Regular,Probationary,Part-time,Casual,Contractual',
            'basic_salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:Monthly,Daily,Hourly',
            'pay_frequency' => 'required|in:Semi-Monthly,Monthly,Weekly',
        ]);

        $employee = Employee::create([
            'employee_id' => $validated['employee_id'],
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'email' => $applicant->email,
            'mobile_number' => $applicant->contact_number,
            'address' => $applicant->address,
            'branch_id' => $validated['branch_id'],
            'department_id' => $validated['department_id'],
            'position_id' => $validated['position_id'],
            'date_hired' => $validated['date_hired'],
            'employment_status' => $validated['employment_status'],
            'employment_type' => $validated['employment_type'],
            'basic_salary' => $validated['basic_salary'],
            'salary_type' => $validated['salary_type'],
            'pay_frequency' => $validated['pay_frequency'],
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
        ]);

        $applicant->update([
            'status' => 'Hired',
            'hired_as_employee_id' => $employee->id,
        ]);

        AuditLogger::log('Create', 'Employees', $employee->id, "Converted hired applicant {$applicant->full_name} to employee {$employee->employee_id}");

        return redirect()->route('hr.people.employees')->with('success', "Applicant {$applicant->full_name} successfully converted to Employee {$employee->employee_id}.");
    }

    // ==========================================
    // 3. INTERVIEWS
    // ==========================================

    public function interviewsIndex(Request $request): View
    {
        $query = Interview::with(['applicant.jobVacancy', 'interviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->get('per_page', 10);
        $interviews = $query->orderBy('interview_date', 'asc')->paginate($perPage)->withQueryString();
        $applicants = Applicant::whereNotIn('status', ['Hired', 'Rejected'])->get();
        $users = User::all();

        return view('hr.recruitment.interviews', compact('interviews', 'applicants', 'users'));
    }

    public function interviewStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:hr_applicants,id',
            'interview_date' => 'required|date',
            'interviewer_id' => 'nullable|exists:users,id',
            'interviewer_name' => 'nullable|string|max:100',
            'interview_type' => 'required|in:Initial Screening,Technical / Practical,Managerial,Final HR',
            'notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'recommendation' => 'nullable|string',
            'status' => 'required|in:Scheduled,Completed,Cancelled',
        ]);

        if (!empty($validated['interviewer_id'])) {
            $interviewerUser = User::find($validated['interviewer_id']);
            $validated['interviewer_name'] = $interviewerUser?->full_name;
        }

        $interview = Interview::create($validated);
        AuditLogger::log('Create', 'Recruitment', $interview->id, "Scheduled interview for applicant #{$interview->applicant_id}");

        return redirect()->back()->with('success', "Interview scheduled successfully.");
    }

    public function interviewUpdate(Request $request, int $id): RedirectResponse
    {
        $interview = Interview::findOrFail($id);
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'recommendation' => 'nullable|string',
            'status' => 'required|in:Scheduled,Completed,Cancelled',
        ]);

        $interview->update($validated);
        AuditLogger::log('Update', 'Recruitment', $interview->id, "Updated interview result #{$interview->id}");

        return redirect()->back()->with('success', "Interview record updated.");
    }
}
