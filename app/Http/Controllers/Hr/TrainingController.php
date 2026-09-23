<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\TrainingEnrollment;
use App\Models\Hr\TrainingProgram;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrainingController extends Controller
{
    // ==========================================
    // 1. TRAINING PROGRAMS
    // ==========================================

    public function programsIndex(Request $request): View
    {
        $query = TrainingProgram::withCount('enrollments');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $programs = $query->orderBy('start_date', 'desc')->paginate(10);
        return view('hr.training.programs', compact('programs'));
    }

    public function programStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'trainer' => 'nullable|string|max:100',
            'training_type' => 'required|in:Internal,External,Online',
            'location' => 'nullable|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration_hours' => 'required|integer|min:1',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:Scheduled,In Progress,Completed,Cancelled',
        ]);

        $program = TrainingProgram::create($validated);
        AuditLogger::log('Create', 'Training', $program->id, "Created training program '{$program->name}'");

        return redirect()->back()->with('success', "Training program '{$program->name}' created.");
    }

    public function programUpdate(Request $request, int $id): RedirectResponse
    {
        $program = TrainingProgram::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'trainer' => 'nullable|string|max:100',
            'training_type' => 'required|in:Internal,External,Online',
            'location' => 'nullable|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'duration_hours' => 'required|integer|min:1',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:Scheduled,In Progress,Completed,Cancelled',
        ]);

        $program->update($validated);
        AuditLogger::log('Update', 'Training', $program->id, "Updated training program '{$program->name}'");

        return redirect()->back()->with('success', "Training program updated.");
    }

    // ==========================================
    // 2. TRAINING RECORDS / ENROLLMENTS
    // ==========================================

    public function recordsIndex(Request $request): View
    {
        $user = Auth::user();
        $query = TrainingEnrollment::with(['program', 'employee.branch', 'employee.department']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($request->filled('training_program_id')) {
            $query->where('training_program_id', $request->training_program_id);
        }

        $records = $query->orderBy('enrollment_date', 'desc')->paginate(15);
        $programs = TrainingProgram::all();
        $employees = Employee::where('employment_status', 'Active')->get();

        return view('hr.training.records', compact('records', 'programs', 'employees'));
    }

    public function enrollmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_program_id' => 'required|exists:training_programs,id',
            'employee_id' => 'required|exists:employees,id',
            'enrollment_date' => 'required|date',
            'completion_status' => 'required|in:Assigned,Scheduled,In Progress,Completed,Failed,Cancelled',
            'score' => 'nullable|numeric|min:0|max:100',
            'remarks' => 'nullable|string',
        ]);

        $enrollment = TrainingEnrollment::updateOrCreate(
            [
                'training_program_id' => $validated['training_program_id'],
                'employee_id' => $validated['employee_id'],
            ],
            $validated
        );

        AuditLogger::log('Create', 'Training', $enrollment->id, "Enrolled employee #{$enrollment->employee_id} in training #{$enrollment->training_program_id}");

        return redirect()->back()->with('success', "Employee enrolled successfully.");
    }

    // ==========================================
    // 3. TRAINING REPORTS
    // ==========================================

    public function reportsIndex(Request $request): View
    {
        $programs = TrainingProgram::withCount('enrollments')->with('enrollments')->get();
        return view('hr.training.reports', compact('programs'));
    }
}
