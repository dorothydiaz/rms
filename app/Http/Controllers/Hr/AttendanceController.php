<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceCorrection;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\ShiftTemplate;
use App\Services\AttendanceCalculationService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    protected AttendanceCalculationService $calcService;

    public function __construct(AttendanceCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    // ==========================================
    // 1. TIMEKEEPING (Punch in/out/breaks)
    // ==========================================

    public function timekeepingIndex(Request $request): View
    {
        $user = Auth::user();
        $date = $request->get('date', Carbon::today()->toDateString());

        $query = Employee::where('employment_status', 'Active')->with(['branch', 'department']);
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->orderBy('first_name')->get();

        // Load today's attendance for these employees
        $empIds = $employees->pluck('id');
        $attendanceMap = AttendanceRecord::whereIn('employee_id', $empIds)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $branches = Branch::where('is_active', true)->get();

        return view('hr.attendance.timekeeping', compact('employees', 'attendanceMap', 'date', 'branches'));
    }

    public function timekeepingStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'punch_type' => 'required|in:time_in,break_out,break_in,time_out',
            'time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $timeStr = $validated['time'] . ':00';

        $record = AttendanceRecord::firstOrNew([
            'employee_id' => $employee->id,
            'date' => $validated['date'],
        ]);

        $record->branch_id = $employee->branch_id;
        $record->source = 'Manual';

        $punchType = $validated['punch_type'];
        $record->{$punchType} = $timeStr;
        if (!empty($validated['notes'])) {
            $record->notes = ($record->notes ? $record->notes . ' | ' : '') . $validated['notes'];
        }

        // Check if employee has a schedule for today
        $schedule = EmployeeSchedule::with('shiftTemplate')
            ->where('employee_id', $employee->id)
            ->where('schedule_date', $validated['date'])
            ->first();

        $shift = $schedule?->shiftTemplate;
        $schedStart = $schedule?->custom_start_time ?? $shift?->start_time;
        $schedEnd = $schedule?->custom_end_time ?? $shift?->end_time;
        $isOvernight = (bool) ($shift?->is_overnight ?? false);

        if ($record->time_in && $record->time_out) {
            $calc = $this->calcService->calculate(
                $validated['date'],
                $schedStart,
                $schedEnd,
                $record->time_in,
                $record->time_out,
                $record->break_out,
                $record->break_in,
                $isOvernight
            );

            $record->total_hours = $calc['total_hours'];
            $record->regular_hours = $calc['regular_hours'];
            $record->late_minutes = $calc['late_minutes'];
            $record->undertime_minutes = $calc['undertime_minutes'];
            $record->overtime_hours = $calc['overtime_hours'];
            $record->night_diff_hours = $calc['night_diff_hours'];
            $record->status = $calc['status'];
        }

        $record->save();

        AuditLogger::log(
            'Update',
            'Attendance',
            $record->id,
            "Recorded {$punchType} ({$timeStr}) for employee {$employee->full_name} on {$validated['date']}"
        );

        return redirect()->back()->with('success', "Punched {$punchType} for {$employee->full_name} at {$validated['time']}.");
    }

    // ==========================================
    // 2. DAILY TIME RECORD (DTR)
    // ==========================================

    public function dtrIndex(Request $request): View
    {
        $user = Auth::user();
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'schedule.shiftTemplate'])
            ->whereBetween('date', [$startDate, $endDate]);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('date', 'desc')->paginate(20)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $employees = Employee::where('employment_status', 'Active')->get();

        // Totals
        $totals = [
            'hours' => (clone $query)->sum('total_hours'),
            'late_min' => (clone $query)->sum('late_minutes'),
            'ot_hours' => (clone $query)->sum('overtime_hours'),
            'nd_hours' => (clone $query)->sum('night_diff_hours'),
        ];

        return view('hr.attendance.dtr', compact('records', 'startDate', 'endDate', 'branches', 'employees', 'totals'));
    }

    // ==========================================
    // 3. SCHEDULES & SHIFT TEMPLATES
    // ==========================================

    public function schedulesIndex(Request $request): View
    {
        $user = Auth::user();
        $weekStart = $request->get('week_start', Carbon::now()->startOfWeek()->toDateString());
        $start = Carbon::parse($weekStart);
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $start->copy()->addDays($i)->toDateString();
        }

        $empQuery = Employee::where('employment_status', 'Active')->with(['branch', 'department']);
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $empQuery->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $empQuery->where('branch_id', $request->branch_id);
        }

        $employees = $empQuery->orderBy('first_name')->get();
        $shiftTemplates = ShiftTemplate::all();

        // Load schedules for these employees for this week
        $schedules = EmployeeSchedule::whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('schedule_date', $dates)
            ->with('shiftTemplate')
            ->get()
            ->groupBy('employee_id');

        $branches = Branch::where('is_active', true)->get();

        return view('hr.attendance.schedules', compact('employees', 'dates', 'schedules', 'shiftTemplates', 'weekStart', 'branches'));
    }

    public function scheduleStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'schedule_date' => 'required|date',
            'shift_template_id' => 'nullable|exists:shift_templates,id',
            'is_rest_day' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $schedule = EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'schedule_date' => $validated['schedule_date'],
            ],
            [
                'branch_id' => $employee->branch_id,
                'shift_template_id' => $validated['shift_template_id'] ?? null,
                'is_rest_day' => $request->boolean('is_rest_day'),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        AuditLogger::log('Update', 'Schedules', $schedule->id, "Updated schedule for {$employee->full_name} on {$validated['schedule_date']}");

        return redirect()->back()->with('success', "Schedule updated.");
    }

    public function shiftTemplateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'code' => 'required|string|max:20|unique:shift_templates,code',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_overnight' => 'boolean',
            'break_minutes' => 'required|integer|min:0',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $validated['start_time'] .= ':00';
        $validated['end_time'] .= ':00';
        $validated['is_overnight'] = $request->boolean('is_overnight');

        $shift = ShiftTemplate::create($validated);
        AuditLogger::log('Create', 'Schedules', $shift->id, "Created shift template '{$shift->name}'");

        return redirect()->back()->with('success', "Shift template '{$shift->name}' created.");
    }

    // ==========================================
    // 4. OVERTIME MANAGEMENT
    // ==========================================

    public function overtimeIndex(Request $request): View
    {
        $user = Auth::user();
        $query = AttendanceRecord::with(['employee.branch', 'employee.department'])
            ->where('overtime_hours', '>', 0);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $records = $query->orderBy('date', 'desc')->paginate(15);
        return view('hr.attendance.overtime', compact('records'));
    }

    // ==========================================
    // 5. ATTENDANCE CORRECTIONS (Review Workflow)
    // ==========================================

    public function correctionsIndex(Request $request): View
    {
        $user = Auth::user();
        $query = AttendanceCorrection::with(['employee.branch', 'attendanceRecord', 'requester', 'reviewer']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $corrections = $query->orderBy('created_at', 'desc')->paginate(15);
        $employees = Employee::where('employment_status', 'Active')->get();

        return view('hr.attendance.corrections', compact('corrections', 'employees'));
    }

    public function correctionStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'requested_time_in' => 'nullable|date_format:H:i',
            'requested_time_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string',
            'supporting_document' => 'nullable|file|max:5120',
        ]);

        $att = AttendanceRecord::firstOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $validated['date']],
            ['branch_id' => Employee::find($validated['employee_id'])->branch_id, 'status' => 'Present']
        );

        $docPath = null;
        if ($request->hasFile('supporting_document')) {
            $docPath = $request->file('supporting_document')->store('corrections', 'public');
        }

        $correction = AttendanceCorrection::create([
            'attendance_record_id' => $att->id,
            'employee_id' => $validated['employee_id'],
            'requested_by' => Auth::id(),
            'original_time_in' => $att->time_in,
            'original_time_out' => $att->time_out,
            'requested_time_in' => $validated['requested_time_in'] ? $validated['requested_time_in'] . ':00' : null,
            'requested_time_out' => $validated['requested_time_out'] ? $validated['requested_time_out'] . ':00' : null,
            'reason' => $validated['reason'],
            'supporting_document' => $docPath,
            'status' => 'Pending',
        ]);

        AuditLogger::log('Create', 'Attendance', $correction->id, "Submitted attendance correction request for {$att->employee?->full_name} on {$validated['date']}");

        return redirect()->back()->with('success', "Attendance correction submitted for review.");
    }

    public function correctionReview(Request $request, int $id): RedirectResponse
    {
        $correction = AttendanceCorrection::with('attendanceRecord.employee')->findOrFail($id);
        $validated = $request->validate([
            'action' => 'required|in:Approved,Rejected',
            'reviewer_notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        if ($correction->attendanceRecord->employee && !$user->canAccessBranch($correction->attendanceRecord->employee->branch_id)) {
            abort(403, 'Unauthorized to review correction for another branch.');
        }

        DB::transaction(function () use ($correction, $validated, $user) {
            $status = $validated['action'];
            $correction->update([
                'status' => $status,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $validated['reviewer_notes'] ?? null,
            ]);

            // If Approved, update the original attendance record and recompute
            if ($status === 'Approved') {
                $att = $correction->attendanceRecord;
                $prev = $att->toArray();

                if ($correction->requested_time_in) {
                    $att->time_in = $correction->requested_time_in;
                }
                if ($correction->requested_time_out) {
                    $att->time_out = $correction->requested_time_out;
                }

                // Recompute metrics
                $calc = $this->calcService->calculate(
                    $att->date,
                    null,
                    null,
                    $att->time_in,
                    $att->time_out,
                    $att->break_out,
                    $att->break_in
                );
                $att->total_hours = $calc['total_hours'];
                $att->regular_hours = $calc['regular_hours'];
                $att->late_minutes = $calc['late_minutes'];
                $att->undertime_minutes = $calc['undertime_minutes'];
                $att->overtime_hours = $calc['overtime_hours'];
                $att->night_diff_hours = $calc['night_diff_hours'];
                $att->status = $calc['status'];
                $att->save();

                AuditLogger::log(
                    'Approve',
                    'Attendance',
                    $att->id,
                    "Approved correction request #{$correction->id}. Updated time in: {$att->time_in}, time out: {$att->time_out}",
                    $prev,
                    $att->toArray()
                );
            } else {
                AuditLogger::log('Reject', 'Attendance', $correction->id, "Rejected correction request #{$correction->id}");
            }
        });

        return redirect()->back()->with('success', "Correction request {$validated['action']}.");
    }
}
