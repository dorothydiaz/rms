<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceCorrection;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\Position;
use App\Models\Hr\ShiftTemplate;
use App\Models\Hr\ScheduleChangeLog;
use App\Models\Hr\AttendanceActionLog;
use App\Services\AttendanceCalculationService;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ZipArchive;

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

        $query = Employee::activeWorkforce()->with(['branch', 'department']);
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->orderBy('first_name')->get();

        // Load today's attendance for these employees
        $empIds = $employees->pluck('id');
        $attendanceMap = AttendanceRecord::whereIn('employee_id', $empIds)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        $branches = Branch::where('is_active', true)->get();

        return view('hr.attendance.timekeeping', compact('employees', 'attendanceMap', 'date', 'branches'));
    }

    public function timekeepingStore(Request $request): RedirectResponse
    {
        // If request is full manual time entries encoding (not a single punch_type), delegate to correctionStore
        if (!$request->filled('punch_type') && ($request->filled('time_in') || $request->filled('break_out') || $request->filled('break_in') || $request->filled('coffee_break_out') || $request->filled('coffee_break_in') || $request->filled('time_out') || $request->filled('status') || $request->filled('notes'))) {
            return $this->correctionStore($request);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'date' => 'required|date',
            'punch_type' => 'required|in:in,time_in,break_out,break_in,coffee_break_out,coffee_break_in,final_out,time_out',
            'time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $timeStr = $validated['time'] . ':00';

        $cleanDate = Carbon::parse($validated['date'])->toDateString();
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('date', $cleanDate)
            ->first() ?? new AttendanceRecord([
                'employee_id' => $employee->id,
                'date' => $cleanDate,
            ]);

        $record->branch_id = $employee->branch_id;
        $record->source = 'Manual';

        $punchType = $validated['punch_type'];
        $punchLabel = 'Time Punch';

        switch ($punchType) {
            case 'in':
            case 'time_in':
                $record->time_in = $timeStr;
                $record->in_1 = $timeStr;
                $punchLabel = 'In';
                break;
            case 'break_out':
                $record->break_out = $timeStr;
                $record->out_1 = $timeStr;
                $punchLabel = 'Break Out';
                break;
            case 'break_in':
                $record->break_in = $timeStr;
                $record->in_2 = $timeStr;
                $punchLabel = 'Break In';
                break;
            case 'coffee_break_out':
                $record->coffee_break_out = $timeStr;
                $record->out_2 = $timeStr;
                $punchLabel = 'Coffee Break Out';
                break;
            case 'coffee_break_in':
                $record->coffee_break_in = $timeStr;
                $record->in_3 = $timeStr;
                $punchLabel = 'Coffee Break In';
                break;
            case 'final_out':
            case 'time_out':
                $record->time_out = $timeStr;
                $record->out_3 = $timeStr;
                $punchLabel = 'Final Out';
                break;
        }

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
                $isOvernight,
                $record->coffee_break_out,
                $record->coffee_break_in
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
            "Recorded {$punchLabel} ({$timeStr}) for employee {$employee->full_name} on {$validated['date']}"
        );

        return redirect()->back()->with('success', "Recorded {$punchLabel} for {$employee->full_name} at {$validated['time']}.");
    }

    // ==========================================
    // 2. DAILY TIME RECORD (DTR)
    // ==========================================

    public function dtrIndex(Request $request): View
    {
        $user = Auth::user();
        $defaultEnd = Carbon::now();
        if (!$request->has('start_date') && !$request->has('end_date')) {
            $hasCurrentMonthRecords = AttendanceRecord::whereBetween('date', [$defaultEnd->copy()->startOfMonth()->toDateString(), $defaultEnd->toDateString()])->exists();
            if (!$hasCurrentMonthRecords) {
                $latestRecordDate = AttendanceRecord::max('date');
                if ($latestRecordDate) {
                    $defaultEnd = Carbon::parse($latestRecordDate);
                }
            }
        }
        $startDate = $request->get('start_date', $defaultEnd->copy()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', $defaultEnd->toDateString());

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

        $perPage = (int) $request->get('per_page', 10);
        $records = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $employees = Employee::activeWorkforce()->get();

        // Totals
        $totals = [
            'hours' => (clone $query)->sum('total_hours'),
            'late_min' => (clone $query)->sum('late_minutes'),
            'ot_hours' => (clone $query)->sum('overtime_hours'),
            'nd_hours' => (clone $query)->sum('night_diff_hours'),
        ];

        return view('hr.attendance.dtr', compact('records', 'startDate', 'endDate', 'branches', 'employees', 'totals'));
    }

    public function dtrTags(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Employee::activeWorkforce()
            ->with(['branch.company', 'department', 'position']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $employees = $query->orderBy('last_name')->get();

        // Build dynamic tags from database
        $tags = [];

        // 1. Employment Statuses & Types
        $statuses = Employee::select('employment_status')->whereNotNull('employment_status')->distinct()->pluck('employment_status');
        $types = Employee::select('employment_type')->whereNotNull('employment_type')->distinct()->pluck('employment_type');
        $allStatuses = $statuses->concat($types)->unique()->filter()->values();
        foreach ($allStatuses as $st) {
            $tags[] = [
                'category' => 'Employment Status',
                'type' => 'status',
                'label' => $st,
                'value' => $st,
            ];
        }

        // 2. Employment Source
        $sources = Employee::select('employment_source')->whereNotNull('employment_source')->distinct()->pluck('employment_source')->unique();
        if ($sources->isEmpty()) {
            $sources = collect(['Company', 'Agency']);
        }
        foreach ($sources as $src) {
            $tags[] = [
                'category' => 'Employment Source',
                'type' => 'source',
                'label' => $src,
                'value' => $src,
            ];
        }

        // 3. Company / Agency Names
        $companies = Employee::select('company_name')->whereNotNull('company_name')->where('company_name', '!=', '')->distinct()->pluck('company_name');
        $agencies = Employee::select('agency_name')->whereNotNull('agency_name')->where('agency_name', '!=', '')->distinct()->pluck('agency_name');
        $companyAgencies = Employee::select('company_agency_name')->whereNotNull('company_agency_name')->where('company_agency_name', '!=', '')->distinct()->pluck('company_agency_name');
        $allCoAgencies = $companies->concat($agencies)->concat($companyAgencies)->unique()->filter()->values();
        foreach ($allCoAgencies as $ca) {
            $tags[] = [
                'category' => 'Company / Agency',
                'type' => 'company_agency',
                'label' => $ca,
                'value' => $ca,
            ];
        }

        // 4. Branch
        $branchQuery = Branch::where('is_active', true);
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $branchQuery->where('id', $user->branch_id);
        }
        $branches = $branchQuery->orderBy('name')->get();
        foreach ($branches as $b) {
            $tags[] = [
                'category' => 'Branch',
                'type' => 'branch',
                'label' => $b->name,
                'value' => (string)$b->id,
            ];
        }

        // 5. Department
        $departments = Department::orderBy('name')->get();
        foreach ($departments as $d) {
            $tags[] = [
                'category' => 'Department',
                'type' => 'department',
                'label' => $d->name,
                'value' => (string)$d->id,
            ];
        }

        // 6. Position
        $positions = Position::orderBy('name')->get();
        foreach ($positions as $p) {
            $tags[] = [
                'category' => 'Position',
                'type' => 'position',
                'label' => $p->name,
                'value' => (string)$p->id,
            ];
        }

        // 7. Individual Employees (by ID & Name)
        foreach ($employees as $e) {
            $tags[] = [
                'category' => 'Employee',
                'type' => 'employee',
                'label' => "{$e->employee_id} - {$e->full_name}",
                'value' => (string)$e->id,
                'subtext' => $e->employee_id,
            ];
        }

        // Minimal employee map for instantaneous client-side count and filtering
        $employeeData = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'employee_id' => $e->employee_id,
                'name' => $e->full_name,
                'statuses' => array_values(array_filter([$e->employment_status, $e->employment_type])),
                'source' => $e->employment_source ?: 'Company',
                'companies' => array_values(array_filter([$e->company_name, $e->agency_name, $e->company_agency_name, $e->branch?->company?->name])),
                'branch_id' => (string)$e->branch_id,
                'branch_name' => $e->branch?->name,
                'department_id' => (string)$e->department_id,
                'department_name' => $e->department?->name,
                'position_id' => (string)$e->position_id,
                'position_name' => $e->position?->name,
            ];
        });

        return response()->json([
            'tags' => $tags,
            'employees' => $employeeData,
            'total_active' => $employees->count(),
        ]);
    }

    public function dtrExportPdf(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $scope = $request->input('scope', 'all'); // 'all' or 'filtered'
        $outputFormat = $request->input('output_format', 'individual'); // 'individual' or 'combined'
        $selectedTagsRaw = $request->input('tags', '[]');
        $selectedTags = is_array($selectedTagsRaw) ? $selectedTagsRaw : json_decode($selectedTagsRaw, true);
        if (!is_array($selectedTags)) {
            $selectedTags = [];
        }

        // Query active employees
        $empQuery = Employee::activeWorkforce()
            ->with(['branch.company', 'department', 'position']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $empQuery->where('branch_id', $user->branch_id);
        }

        $allEmployees = $empQuery->orderBy('last_name')->get();

        // Apply dynamic filter tags if scope is filtered
        if ($scope === 'filtered' && !empty($selectedTags)) {
            $groupedTags = [];
            foreach ($selectedTags as $tag) {
                if (isset($tag['type']) && isset($tag['value'])) {
                    $groupedTags[$tag['type']][] = (string)$tag['value'];
                }
            }

            $employees = $allEmployees->filter(function ($emp) use ($groupedTags) {
                foreach ($groupedTags as $type => $values) {
                    $match = false;
                    switch ($type) {
                        case 'status':
                            $match = in_array($emp->employment_status, $values) || in_array($emp->employment_type, $values);
                            break;
                        case 'source':
                            $match = in_array($emp->employment_source, $values);
                            break;
                        case 'company_agency':
                            $caList = array_map('strval', array_filter([$emp->company_name, $emp->agency_name, $emp->company_agency_name, $emp->branch?->company?->name]));
                            $match = !empty(array_intersect($values, $caList));
                            break;
                        case 'branch':
                            $match = in_array((string)$emp->branch_id, $values) || in_array($emp->branch?->name, $values);
                            break;
                        case 'department':
                            $match = in_array((string)$emp->department_id, $values) || in_array($emp->department?->name, $values);
                            break;
                        case 'position':
                            $match = in_array((string)$emp->position_id, $values) || in_array($emp->position?->name, $values);
                            break;
                        case 'employee':
                            $match = in_array((string)$emp->id, $values) || in_array($emp->employee_id, $values);
                            break;
                        default:
                            $match = true;
                    }
                    if (!$match) {
                        return false;
                    }
                }
                return true;
            })->values();
        } else {
            $employees = $allEmployees;
        }

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'No employees match the selected export criteria.');
        }

        // Build cutoff date range
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        if ($end->lt($start)) {
            $temp = $start;
            $start = $end;
            $end = $temp;
        }

        $dateList = [];
        $cur = $start->copy();
        while ($cur->lte($end)) {
            $dateList[] = $cur->toDateString();
            $cur->addDay();
        }

        // Preload attendance records and leave requests for all matching employees in date range
        $employeeIds = $employees->pluck('id');
        $attendanceMap = AttendanceRecord::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(function ($item) {
                $d = Carbon::parse($item->date)->toDateString();
                return $item->employee_id . '_' . $d;
            });

        $leaveMap = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'Approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start->toDateString())
                          ->where('end_date', '>=', $end->toDateString());
                  });
            })
            ->with('leaveType')
            ->get();

        $scheduleMap = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()])
            ->with('shiftTemplate')
            ->get()
            ->groupBy(function ($item) {
                $d = Carbon::parse($item->schedule_date)->toDateString();
                return $item->employee_id . '_' . $d;
            });

        $employeesData = [];
        $cutoffFormatted = $start->format('M j, Y') . ' to ' . $end->format('M j, Y');

        foreach ($employees as $emp) {
            $companyDisplay = $emp->employment_source === 'Agency'
                ? ($emp->agency_name ?: $emp->company_agency_name ?: 'AGENCY')
                : ($emp->company_name ?: $emp->company_agency_name ?: ($emp->branch?->company?->name ?? 'COMPANY'));

            $days = [];
            $totalRegHrs = 0.0;
            $totalLateMins = 0;
            $totalUtMins = 0;
            $totalAbsence = 0.0;
            $totalNet = 0.0;
            $totalOt = 0.0;

            foreach ($dateList as $dateStr) {
                $dateObj = Carbon::parse($dateStr);
                $key = $emp->id . '_' . $dateStr;
                $record = $attendanceMap->has($key) ? $attendanceMap->get($key)->first() : null;
                $sched = $scheduleMap->has($key) ? $scheduleMap->get($key)->first() : null;

                $isSunday = $dateObj->isSunday();
                $isRestDay = $sched !== null ? (bool)$sched->is_rest_day : ($record?->is_rest_day ?? $isSunday);

                // Shift ID: concise code e.g. 0800A, 0600A, or RD for rest days
                if ($isRestDay && (!$record || (!$record->time_in && !$record->in_1))) {
                    $shiftId = 'RD';
                } else {
                    $st = $sched?->shiftTemplate ?? $record?->schedule?->shiftTemplate;
                    if ($st) {
                        $code = trim($st->code ?: '');
                        if (!empty($code)) {
                            $shiftId = preg_match('/^\d{4}$/', $code) ? $code . 'A' : $code;
                        } else {
                            $shiftId = Carbon::parse($st->start_time)->format('Hi') . 'A';
                        }
                    } else {
                        $shiftId = '0800A';
                    }
                }

                // 6 Actual Time Entry Punches
                $in1 = $out1 = $in2 = $out2 = $in3 = $out3 = '';
                if ($record && $shiftId !== 'RD') {
                    $rawIn1 = $record->in_1 ?? $record->time_in;
                    $rawOut1 = $record->out_1 ?? $record->break_out;
                    $rawIn2 = $record->in_2 ?? $record->break_in;
                    $rawOut2 = $record->out_2 ?? $record->coffee_break_out;
                    $rawIn3 = $record->in_3 ?? $record->coffee_break_in;
                    $rawOut3 = $record->out_3 ?? $record->time_out;

                    $in1 = $rawIn1 ? Carbon::parse($rawIn1)->format('G:i') : '';
                    $out1 = $rawOut1 ? Carbon::parse($rawOut1)->format('G:i') : '';
                    $in2 = $rawIn2 ? Carbon::parse($rawIn2)->format('G:i') : '';
                    $out2 = $rawOut2 ? Carbon::parse($rawOut2)->format('G:i') : '';
                    $in3 = $rawIn3 ? Carbon::parse($rawIn3)->format('G:i') : '';
                    $out3 = $rawOut3 ? Carbon::parse($rawOut3)->format('G:i') : '';
                }

                // Leave check
                $leaveCode = null;
                $empLeave = $leaveMap->first(function ($l) use ($emp, $dateStr) {
                    return $l->employee_id == $emp->id && $dateStr >= $l->start_date && $dateStr <= $l->end_date;
                });
                if ($empLeave) {
                    $leaveCode = $empLeave->leaveType?->code ?: 'AA';
                }

                // Metrics calculation
                $regHrs = null;
                $lateMins = 0;
                $utMins = 0;
                $absence = 0.0;
                $net = null;
                $ot = 0.0;
                $remarks = '';

                if ($shiftId === 'RD') {
                    $regHrs = null;
                    $lateMins = 0;
                    $utMins = 0;
                    $absence = 0.0;
                    $net = null;
                    $ot = 0.0;
                    $remarks = '';
                } elseif ($record) {
                    $regHrs = 8.00;
                    $lateMins = (int)$record->late_minutes;
                    $utMins = (int)$record->undertime_minutes;
                    $ot = (float)$record->overtime_hours;

                    if ($record->status === 'Absent' || (float)$record->absence_days > 0) {
                        $absence = (float)($record->absence_days > 0 ? $record->absence_days : 1.00);
                        $net = null;
                        if ($record->dtr_remarks) {
                            $remarks = $record->dtr_remarks;
                        } elseif ($record->holiday_type === 'Regular') {
                            $remarks = 'LH';
                        } elseif ($record->holiday_type === 'SpecialNonWorking') {
                            $remarks = 'UA SH';
                        } else {
                            $remarks = $leaveCode ?: 'UA';
                        }
                    } else {
                        $net = (float)($record->regular_hours > 0 ? $record->regular_hours : 8.00);
                        if ($record->dtr_remarks) {
                            $remarks = $record->dtr_remarks;
                        } elseif ($record->holiday_type === 'Regular') {
                            $remarks = 'LH';
                        } elseif ($record->holiday_type === 'SpecialNonWorking') {
                            $remarks = 'SH';
                        }
                    }
                } else {
                    // No record
                    if ($dateObj->lte(Carbon::today())) {
                        $regHrs = 8.00;
                        $absence = 1.00;
                        $net = null;
                        $remarks = $leaveCode ?: 'UA';
                    }
                }

                if ($regHrs !== null) {
                    $totalRegHrs += (float)$regHrs;
                }
                $totalLateMins += $lateMins;
                $totalUtMins += $utMins;
                $totalAbsence += $absence;
                if ($net !== null) {
                    $totalNet += (float)$net;
                }
                $totalOt += $ot;

                $days[] = [
                    'date_formatted' => $dateObj->format('M j'),
                    'day_short' => $dateObj->format('D'),
                    'shift_id' => $shiftId,
                    'in_1' => $in1,
                    'out_1' => $out1,
                    'in_2' => $in2,
                    'out_2' => $out2,
                    'in_3' => $in3,
                    'out_3' => $out3,
                    'reg_hrs' => $regHrs,
                    'late_mins' => $lateMins,
                    'ut_mins' => $utMins,
                    'absence' => $absence,
                    'net' => $net,
                    'ot' => $ot,
                    'remarks' => $remarks,
                ];
            }

            $employeesData[] = [
                'employee_id' => $emp->employee_id,
                'employee_name' => mb_strtoupper($emp->last_name . ', ' . $emp->first_name . ($emp->middle_name ? ' ' . mb_substr($emp->middle_name, 0, 1) . '.' : '') . ($emp->suffix ? ' ' . $emp->suffix : '')),
                'company' => mb_strtoupper($companyDisplay),
                'branch' => mb_strtoupper($emp->branch?->name ?? 'MAIN'),
                'department' => mb_strtoupper($emp->department?->name ?? 'GENERAL'),
                'cutoff' => $cutoffFormatted,
                'days' => $days,
                'totals' => [
                    'reg_hrs' => $totalRegHrs,
                    'late_mins' => $totalLateMins,
                    'ut_mins' => $totalUtMins,
                    'absence' => $totalAbsence,
                    'net' => $totalNet,
                    'ot' => $totalOt,
                ],
            ];
        }

        AuditLogger::log('Export', 'Attendance', null, "Exported DTR PDF for " . count($employeesData) . " employees ({$startDate} to {$endDate})");

        $runDate = Carbon::now()->format('m/d/y');
        $runTime = Carbon::now()->format('H:i:s');

        if (!is_dir(storage_path('fonts'))) {
            @mkdir(storage_path('fonts'), 0777, true);
        }

        // Check output format
        if ($outputFormat === 'individual' && count($employeesData) > 1) {
            $zipName = "DTR_Individual_PDFs_{$startDate}_{$endDate}_" . uniqid() . ".zip";
            $zipPath = storage_path('app/' . $zipName);
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($employeesData as $empData) {
                    $singlePdf = Pdf::loadView('hr.attendance.pdf.dtr_sheet', [
                        'employeesData' => [$empData],
                        'runDate' => $runDate,
                        'runTime' => $runTime,
                    ])->setPaper('letter', 'portrait')
                      ->setOption('isRemoteEnabled', true);

                    $cleanEmpId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empData['employee_id']);
                    $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empData['employee_name']);
                    $zip->addFromString("DTR_{$cleanEmpId}_{$cleanName}_{$startDate}_{$endDate}.pdf", $singlePdf->output());
                }
                $zip->close();
                return response()->download($zipPath, "DTR_Individual_PDFs_{$startDate}_{$endDate}.zip")->deleteFileAfterSend(true);
            }
        }

        // Single employee individual or Combined PDF
        $pdf = Pdf::loadView('hr.attendance.pdf.dtr_sheet', [
            'employeesData' => $employeesData,
            'runDate' => $runDate,
            'runTime' => $runTime,
        ])->setPaper('letter', 'portrait')
          ->setOption('isRemoteEnabled', true);

        if (count($employeesData) === 1) {
            $cleanEmpId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $employeesData[0]['employee_id']);
            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $employeesData[0]['employee_name']);
            return $pdf->download("DTR_{$cleanEmpId}_{$cleanName}_{$startDate}_{$endDate}.pdf");
        }

        return $pdf->download("DTR_Combined_{$startDate}_{$endDate}.pdf");
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

        $empQuery = Employee::activeWorkforce()->with(['branch', 'department', 'position']);
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $empQuery->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $empQuery->where('branch_id', $request->branch_id);
        }

        $employees = $empQuery->orderBy('first_name')->get();
        $shiftTemplates = ShiftTemplate::orderBy('code')->get();

        // Load schedules for these employees for this week
        $schedules = EmployeeSchedule::whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('schedule_date', $dates)
            ->with('shiftTemplate')
            ->get()
            ->groupBy('employee_id');

        // Load attendance records for these employees for this week
        $attendanceRecords = AttendanceRecord::whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('date', $dates)
            ->get()
            ->groupBy('employee_id');

        $branches = Branch::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $allEmployees = Employee::activeWorkforce()->with(['branch', 'department', 'position'])->orderBy('first_name')->get();

        return view('hr.attendance.schedules', compact('employees', 'dates', 'schedules', 'attendanceRecords', 'shiftTemplates', 'weekStart', 'branches', 'departments', 'allEmployees'));
    }

    public function scheduleStore(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'employee_id' => 'nullable',
            'employee_ids' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'schedule_date' => 'nullable|date',
            'shift_template_id' => 'nullable|exists:hr_shift_templates,id',
            'is_rest_day' => 'nullable|boolean',
            'rest_days' => 'nullable|array',
            'apply_mode' => 'nullable|string',
            'notes' => 'nullable|string|max:255',
        ]);

        // Resolve dates
        $startDateStr = $request->input('start_date') ?: $request->input('schedule_date') ?: Carbon::today()->toDateString();
        $endDateStr = $request->input('end_date') ?: $startDateStr;

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->startOfDay();

        if ($endDate->lt($startDate)) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        // Resolve target employees
        $employeeIds = [];
        if ($request->input('employee_id') === 'all') {
            $empQuery = Employee::activeWorkforce();
            if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
                $empQuery->where('branch_id', $user->branch_id);
            } elseif ($request->filled('branch_id')) {
                $empQuery->where('branch_id', $request->input('branch_id'));
            }
            $employeeIds = $empQuery->pluck('id')->toArray();
        } elseif ($request->filled('employee_id')) {
            $employeeIds = [(int) $request->input('employee_id')];
        } elseif ($request->has('employee_ids') && is_array($request->input('employee_ids'))) {
            $employeeIds = array_map('intval', $request->input('employee_ids'));
        }

        if (empty($employeeIds)) {
            return redirect()->back()->with('error', 'Please select at least one employee.');
        }

        // Rest days by day of week (Mon - Sun)
        $rawRestDays = $request->input('rest_days', []);
        if (!is_array($rawRestDays)) {
            $rawRestDays = [];
        }
        $restDays = array_map(function ($d) {
            return ucfirst(strtolower(substr(trim($d), 0, 3)));
        }, $rawRestDays);

        $isAllRestDay = $request->boolean('is_rest_day');
        $shiftTemplateId = $request->input('shift_template_id');
        $applyMode = $request->input('apply_mode', 'standard'); // 'standard' or 'rest_only'
        $notes = $request->input('notes');

        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        $totalAssigned = 0;

        DB::transaction(function () use ($employees, $startDate, $endDate, $restDays, $isAllRestDay, $shiftTemplateId, $applyMode, $notes, &$totalAssigned) {
            $curr = $startDate->copy();
            while ($curr->lte($endDate)) {
                $dateStr = $curr->toDateString();
                $dayOfWeek = $curr->format('D'); // e.g. Mon, Tue, Wed, Thu, Fri, Sat, Sun

                $isRestDayOnThisDate = $isAllRestDay || in_array($dayOfWeek, $restDays);

                // If 'rest_only' mode is chosen, skip dates that are not designated as rest days
                if ($applyMode === 'rest_only' && !$isRestDayOnThisDate) {
                    $curr->addDay();
                    continue;
                }

                foreach ($employees as $emp) {
                    EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $emp->id,
                            'schedule_date' => $dateStr,
                        ],
                        [
                            'branch_id' => $emp->branch_id,
                            'shift_template_id' => $isRestDayOnThisDate ? null : $shiftTemplateId,
                            'is_rest_day' => $isRestDayOnThisDate,
                            'notes' => $notes,
                        ]
                    );
                    $totalAssigned++;
                }
                $curr->addDay();
            }
        });

        $dateRangeText = $startDate->toDateString() === $endDate->toDateString()
            ? $startDate->toDateString()
            : "{$startDate->toDateString()} to {$endDate->toDateString()}";

        $empCount = count($employeeIds);
        AuditLogger::log('Update', 'Schedules', 0, "Assigned schedules for {$empCount} employee(s) across date range {$dateRangeText}");

        return redirect()->back()->with('success', "Schedule assigned successfully ({$dateRangeText}) for {$empCount} employee(s).");
    }

    public function scheduleQuickAssign(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'date' => 'required|date',
            'shift_template_id' => 'nullable|exists:hr_shift_templates,id',
            'is_rest_day' => 'nullable|boolean',
            'clear' => 'nullable|boolean',
            'custom_start_time' => 'nullable|string',
            'custom_end_time' => 'nullable|string',
            'shift_code' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $empId = (int) $request->input('employee_id');
        $date = Carbon::parse($request->input('date'))->toDateString();
        $isClear = $request->boolean('clear');

        $existingSched = EmployeeSchedule::with('shiftTemplate')
            ->where('employee_id', $empId)
            ->where('schedule_date', $date)
            ->first();

        $prevRange = $existingSched 
            ? ($existingSched->is_rest_day ? 'RESTDAY' : ($existingSched->custom_start_time ? substr($existingSched->custom_start_time, 0, 5) . ' - ' . substr($existingSched->custom_end_time, 0, 5) : ($existingSched->shiftTemplate ? $existingSched->shiftTemplate->name . ' (' . substr($existingSched->shiftTemplate->start_time, 0, 5) . '-' . substr($existingSched->shiftTemplate->end_time, 0, 5) . ')' : 'Scheduled')))
            : 'Unassigned';

        if ($isClear) {
            EmployeeSchedule::where('employee_id', $empId)
                ->where('schedule_date', $date)
                ->delete();

            ScheduleChangeLog::create([
                'employee_id' => $empId,
                'schedule_date' => $date,
                'previous_shift_template_id' => $existingSched?->shift_template_id,
                'new_shift_template_id' => null,
                'previous_time_range' => $prevRange,
                'new_time_range' => 'Cleared / Unassigned',
                'reason' => 'Schedule slot cleared',
                'changed_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Schedule cleared successfully.',
                'cleared' => true,
                'employee_id' => $empId,
                'date' => $date,
            ]);
        }

        $emp = Employee::findOrFail($empId);
        $isRestDay = $request->boolean('is_rest_day');
        $shiftTemplateId = $isRestDay ? null : $request->input('shift_template_id');
        $customStart = $isRestDay ? null : $request->input('custom_start_time');
        $customEnd = $isRestDay ? null : $request->input('custom_end_time');
        $shiftCode = $request->input('shift_code');
        $notes = $isRestDay ? 'RESTDAY' : ($request->input('notes') ?: $shiftCode);

        // If shift_template_id not passed but shift_code matches a template
        if (!$isRestDay && !$shiftTemplateId && $shiftCode) {
            $matchedTmpl = ShiftTemplate::where('code', $shiftCode)->first();
            if ($matchedTmpl) {
                $shiftTemplateId = $matchedTmpl->id;
            }
        }

        $updateData = [
            'branch_id' => $emp->branch_id,
            'shift_template_id' => $shiftTemplateId,
            'is_rest_day' => $isRestDay,
            'notes' => $notes,
        ];

        if ($customStart !== null) {
            $updateData['custom_start_time'] = strlen($customStart) === 5 ? $customStart . ':00' : $customStart;
        }
        if ($customEnd !== null) {
            $updateData['custom_end_time'] = strlen($customEnd) === 5 ? $customEnd . ':00' : $customEnd;
        }

        $sched = EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $empId,
                'schedule_date' => $date,
            ],
            $updateData
        );

        $sched->load('shiftTemplate');

        $newRange = $isRestDay 
            ? 'RESTDAY' 
            : ($customStart ? substr($customStart, 0, 5) . ' - ' . substr($customEnd, 0, 5) : ($sched->shiftTemplate ? $sched->shiftTemplate->name . ' (' . substr($sched->shiftTemplate->start_time, 0, 5) . '-' . substr($sched->shiftTemplate->end_time, 0, 5) . ')' : 'Assigned'));

        ScheduleChangeLog::create([
            'employee_id' => $empId,
            'schedule_date' => $date,
            'previous_shift_template_id' => $existingSched?->shift_template_id,
            'new_shift_template_id' => $sched->shift_template_id,
            'previous_time_range' => $prevRange,
            'new_time_range' => $newRange,
            'reason' => $isRestDay ? 'Assigned Rest Day' : 'Assigned Shift',
            'changed_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isRestDay ? 'Rest day assigned.' : 'Shift assigned.',
            'data' => [
                'id' => $sched->id,
                'employee_id' => $empId,
                'date' => $date,
                'is_rest_day' => (bool) $sched->is_rest_day,
                'custom_start_time' => $sched->custom_start_time ? substr($sched->custom_start_time, 0, 5) : null,
                'custom_end_time' => $sched->custom_end_time ? substr($sched->custom_end_time, 0, 5) : null,
                'shift_code' => $shiftCode ?: ($sched->shiftTemplate?->code ?? ($isRestDay ? 'OFF' : 'CUSTOM')),
                'notes' => $sched->notes,
                'shift' => $sched->shiftTemplate ? [
                    'id' => $sched->shiftTemplate->id,
                    'name' => $sched->shiftTemplate->name,
                    'code' => $sched->shiftTemplate->code,
                    'start_time' => substr($sched->shiftTemplate->start_time, 0, 5),
                    'end_time' => substr($sched->shiftTemplate->end_time, 0, 5),
                    'color' => $sched->shiftTemplate->color ?? '#8b5cf6',
                    'label' => $sched->shiftTemplate->formatted_label ?? $sched->shiftTemplate->name,
                ] : null,
            ],
        ]);
    }

    public function scheduleCopyWeek(Request $request): JsonResponse
    {
        $request->validate([
            'current_week_start' => 'required|date',
            'branch_id' => 'nullable',
        ]);

        $currStart = Carbon::parse($request->input('current_week_start'))->startOfDay();
        $prevStart = $currStart->copy()->subDays(7);

        $prevDates = [];
        for ($i = 0; $i < 7; $i++) {
            $prevDates[] = $prevStart->copy()->addDays($i)->toDateString();
        }

        $user = Auth::user();
        $query = EmployeeSchedule::whereIn('schedule_date', $prevDates);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $prevSchedules = $query->get();

        if ($prevSchedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No schedules found in the previous week to copy.',
                'copied_count' => 0,
            ], 422);
        }

        $copiedCount = 0;
        DB::transaction(function () use ($prevSchedules, &$copiedCount) {
            foreach ($prevSchedules as $prev) {
                $targetDate = Carbon::parse($prev->schedule_date)->addDays(7)->toDateString();

                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $prev->employee_id,
                        'schedule_date' => $targetDate,
                    ],
                    [
                        'branch_id' => $prev->branch_id,
                        'shift_template_id' => $prev->shift_template_id,
                        'custom_start_time' => $prev->custom_start_time,
                        'custom_end_time' => $prev->custom_end_time,
                        'is_rest_day' => $prev->is_rest_day,
                        'notes' => $prev->notes,
                    ]
                );
                $copiedCount++;
            }
        });

        AuditLogger::log('Create', 'Schedules', 0, "Copied {$copiedCount} schedule entries from week of {$prevStart->toDateString()} to {$currStart->toDateString()}");

        return response()->json([
            'success' => true,
            'message' => "Successfully copied {$copiedCount} schedule entries from previous week.",
            'copied_count' => $copiedCount,
        ]);
    }

    public function scheduleQuickFillRow(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'week_start' => 'required|date',
            'shift_template_id' => 'required|exists:hr_shift_templates,id',
            'rest_days' => 'nullable|array',
        ]);

        $empId = (int) $request->input('employee_id');
        $emp = Employee::findOrFail($empId);
        $shiftId = (int) $request->input('shift_template_id');

        $rawRest = $request->input('rest_days', ['Sun']);
        $restDays = array_map(function ($d) {
            return ucfirst(strtolower(substr(trim($d), 0, 3)));
        }, $rawRest);

        $start = Carbon::parse($request->input('week_start'))->startOfDay();
        $updatedCount = 0;

        DB::transaction(function () use ($emp, $shiftId, $restDays, $start, &$updatedCount) {
            for ($i = 0; $i < 7; $i++) {
                $curr = $start->copy()->addDays($i);
                $dateStr = $curr->toDateString();
                $dayOfWeek = $curr->format('D'); // Mon, Tue...

                $isRest = in_array($dayOfWeek, $restDays);

                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'schedule_date' => $dateStr,
                    ],
                    [
                        'branch_id' => $emp->branch_id,
                        'shift_template_id' => $isRest ? null : $shiftId,
                        'is_rest_day' => $isRest,
                    ]
                );
                $updatedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Populated 7 days schedule for {$emp->full_name}.",
            'updated_count' => $updatedCount,
        ]);
    }

    public function scheduleBatchStore(Request $request): JsonResponse
    {
        $request->validate([
            'schedules' => 'required|array|min:1',
            'schedules.*.employee_id' => 'required|exists:hr_employees,id',
            'schedules.*.date' => 'required|date',
            'schedules.*.shift_template_id' => 'nullable|exists:hr_shift_templates,id',
            'schedules.*.custom_start_time' => 'nullable|string',
            'schedules.*.custom_end_time' => 'nullable|string',
            'schedules.*.notes' => 'nullable|string',
            'schedules.*.is_rest_day' => 'nullable|boolean',
            'schedules.*.clear' => 'nullable|boolean',
        ]);

        $entries = $request->input('schedules');
        $employeeIds = array_unique(array_column($entries, 'employee_id'));
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        $savedCount = 0;
        DB::transaction(function () use ($entries, $employees, &$savedCount) {
            foreach ($entries as $item) {
                $empId = (int) $item['employee_id'];
                $emp = $employees->get($empId);
                if (!$emp) continue;

                $date = Carbon::parse($item['date'])->toDateString();

                if (!empty($item['clear'])) {
                    EmployeeSchedule::where('employee_id', $empId)
                        ->where('schedule_date', $date)
                        ->delete();
                    $savedCount++;
                    continue;
                }

                $isRest = !empty($item['is_rest_day']);
                $shiftId = $isRest ? null : ($item['shift_template_id'] ?? null);

                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $empId,
                        'schedule_date' => $date,
                    ],
                    [
                        'branch_id' => $emp->branch_id,
                        'shift_template_id' => $shiftId,
                        'custom_start_time' => $isRest ? null : ($item['custom_start_time'] ?? null),
                        'custom_end_time' => $isRest ? null : ($item['custom_end_time'] ?? null),
                        'notes' => $isRest ? 'RESTDAY' : ($item['notes'] ?? null),
                        'is_rest_day' => $isRest,
                    ]
                );
                $savedCount++;
            }
        });

        AuditLogger::log('Update', 'Schedules', 0, "Batch saved {$savedCount} schedule entries.");

        return response()->json([
            'success' => true,
            'message' => "Saved {$savedCount} schedule entries successfully.",
            'saved_count' => $savedCount,
        ]);
    }

    public function updateEmployeeDepartment(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'department_name' => 'nullable|string',
        ]);

        $emp = Employee::findOrFail($request->input('employee_id'));

        $departmentId = $request->input('department_id');
        if (!$departmentId && $request->filled('department_name')) {
            $dept = Department::where('name', $request->input('department_name'))->first();
            if ($dept) {
                $departmentId = $dept->id;
            }
        }

        $emp->department_id = $departmentId;
        if ($departmentId) {
            $assigned = is_array($emp->assigned_department_ids) ? $emp->assigned_department_ids : [];
            if (!in_array((int) $departmentId, $assigned)) {
                $assigned[] = (int) $departmentId;
                $emp->assigned_department_ids = array_values(array_unique($assigned));
            }
        }
        $emp->save();

        $emp->refresh();
        $deptName = $emp->department?->name ?? 'Front of House';

        AuditLogger::log('Update', 'Employees', $emp->id, "Updated department for employee {$emp->full_name} to {$deptName}");

        return response()->json([
            'success' => true,
            'message' => "Department for {$emp->full_name} updated to {$deptName}.",
            'employee_id' => $emp->id,
            'department_id' => $emp->department_id,
            'department_name' => $deptName,
        ]);
    }

    public function assignDefaultShift(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'shift_template_id' => 'nullable|exists:hr_shift_templates,id',
            'apply_future_blanks' => 'nullable|boolean',
        ]);

        $emp = Employee::findOrFail($request->employee_id);
        $prevTmpl = $emp->defaultShiftTemplate;
        $newTmplId = $request->shift_template_id ? (int) $request->shift_template_id : null;
        $newTmpl = $newTmplId ? ShiftTemplate::find($newTmplId) : null;

        $emp->update([
            'default_shift_template_id' => $newTmplId,
        ]);

        // Log schedule default assignment
        ScheduleChangeLog::create([
            'employee_id' => $emp->id,
            'schedule_date' => now()->toDateString(),
            'previous_shift_template_id' => $prevTmpl?->id,
            'new_shift_template_id' => $newTmplId,
            'previous_time_range' => $prevTmpl ? "Default: {$prevTmpl->name} (" . substr($prevTmpl->start_time, 0, 5) . "-" . substr($prevTmpl->end_time, 0, 5) . ")" : "No Default Shift",
            'new_time_range' => $newTmpl ? "Default: {$newTmpl->name} (" . substr($newTmpl->start_time, 0, 5) . "-" . substr($newTmpl->end_time, 0, 5) . ")" : "Removed Default Shift",
            'reason' => 'Default shift schedule assigned to employee profile',
            'changed_by' => Auth::id(),
        ]);

        $appliedCount = 0;
        if ($request->boolean('apply_future_blanks') && $newTmplId) {
            $futureDates = [];
            for ($i = 0; $i < 14; $i++) {
                $futureDates[] = now()->addDays($i)->toDateString();
            }

            foreach ($futureDates as $fDate) {
                $exists = EmployeeSchedule::where('employee_id', $emp->id)->where('schedule_date', $fDate)->exists();
                if (!$exists) {
                    EmployeeSchedule::create([
                        'employee_id' => $emp->id,
                        'branch_id' => $emp->branch_id,
                        'schedule_date' => $fDate,
                        'shift_template_id' => $newTmplId,
                        'is_rest_day' => false,
                        'notes' => 'Applied from Default Shift',
                    ]);
                    $appliedCount++;
                }
            }
        }

        $msg = "Default shift updated for {$emp->full_name}" . ($appliedCount > 0 ? " and applied to {$appliedCount} upcoming date(s)." : ".");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'default_shift_template_id' => $newTmplId,
                'shift_name' => $newTmpl?->name ?? 'None',
                'shift_code' => $newTmpl?->code ?? '',
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function shiftTemplateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_overnight' => 'nullable|boolean',
            'break_minutes' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:20',
        ]);

        $startTime = $validated['start_time'] . ':00';
        $endTime = $validated['end_time'] . ':00';
        $code = Carbon::parse($startTime)->format('Hi');
        $formattedName = ShiftTemplate::formatShiftLabel($startTime, $endTime);

        $isOvernight = $request->boolean('is_overnight');
        if (!$isOvernight && Carbon::parse($startTime)->gt(Carbon::parse($endTime))) {
            $isOvernight = true;
        }

        $shift = ShiftTemplate::updateOrCreate(
            ['code' => $code],
            [
                'name' => $formattedName,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_overnight' => $isOvernight,
                'break_minutes' => $validated['break_minutes'] ?? 60,
                'color' => $validated['color'] ?? '#8b5cf6',
                'description' => null,
            ]
        );

        AuditLogger::log('Create', 'Schedules', $shift->id, "Created shift template '{$shift->name}'");

        return redirect()->back()->with('success', "Shift template '{$shift->name}' saved.");
    }

    public function shiftTemplateUpdate(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $shift = ShiftTemplate::findOrFail($id);

        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_overnight' => 'nullable|boolean',
            'break_minutes' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:20',
        ]);

        $startTime = $validated['start_time'] . ':00';
        $endTime = $validated['end_time'] . ':00';
        $code = Carbon::parse($startTime)->format('Hi');
        $formattedName = ShiftTemplate::formatShiftLabel($startTime, $endTime);

        // Check if code is already used by another shift template
        $existing = ShiftTemplate::where('code', $code)->where('id', '!=', $shift->id)->first();
        if ($existing) {
            $msg = "A shift template with code '{$code}' ({$existing->name}) already exists.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $isOvernight = $request->boolean('is_overnight');
        if (!$isOvernight && Carbon::parse($startTime)->gt(Carbon::parse($endTime))) {
            $isOvernight = true;
        }

        $shift->update([
            'code' => $code,
            'name' => $formattedName,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_overnight' => $isOvernight,
            'break_minutes' => $validated['break_minutes'] ?? 60,
            'color' => $validated['color'] ?? '#8b5cf6',
        ]);

        AuditLogger::log('Update', 'Schedules', $shift->id, "Updated shift template '{$shift->name}'");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Shift template '{$shift->name}' updated successfully.",
                'shift' => $shift,
            ]);
        }

        return redirect()->back()->with('success', "Shift template '{$shift->name}' updated successfully.");
    }

    public function shiftTemplateDestroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $shift = ShiftTemplate::findOrFail($id);
        $shiftName = $shift->name;

        // Disassociate any schedules pointing to this template so schedules keep their times
        EmployeeSchedule::where('shift_template_id', $shift->id)->update(['shift_template_id' => null]);

        $shift->delete();

        AuditLogger::log('Delete', 'Schedules', $id, "Deleted shift template '{$shiftName}'");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Shift template '{$shiftName}' deleted successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Shift template '{$shiftName}' deleted successfully.");
    }

    // ==========================================
    // 4. OVERTIME MANAGEMENT
    // ==========================================

    public function overtimeIndex(Request $request): View
    {
        $user = Auth::user();
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'overtimeApprover'])
            ->where('overtime_hours', '>', 0);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('overtime_status', $request->status);
        }
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhereHas('employee.branch', function ($bq) use ($search) {
                    $bq->where('name', 'like', "%{$search}%");
                })->orWhereHas('employee.department', function ($dq) use ($search) {
                    $dq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $metricsQuery = clone $query;
        $totalOtHours = (clone $metricsQuery)->sum('overtime_hours');
        $pendingCount = (clone $metricsQuery)->where('overtime_status', 'Pending')->count();
        $approvedCount = (clone $metricsQuery)->where('overtime_status', 'Approved')->count();
        $rejectedCount = (clone $metricsQuery)->where('overtime_status', 'Rejected')->count();

        $perPage = (int) $request->get('per_page', 10);
        $records = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        return view('hr.attendance.overtime', compact(
            'records', 'branches', 'totalOtHours', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    public function overtimeApprove(int $id, Request $request): RedirectResponse
    {
        $rec = AttendanceRecord::findOrFail($id);
        $user = Auth::user();

        $prevStatus = $rec->overtime_status;
        $rec->update([
            'overtime_status' => 'Approved',
            'overtime_approved_by' => $user?->id,
            'overtime_approved_at' => now(),
            'overtime_remarks' => $request->input('remarks', $rec->overtime_remarks),
        ]);

        AttendanceActionLog::create([
            'attendance_record_id' => $rec->id,
            'employee_id' => $rec->employee_id,
            'action_type' => 'overtime_approved',
            'details' => [
                'date' => $rec->date?->toDateString(),
                'overtime_hours' => $rec->overtime_hours,
                'previous_status' => $prevStatus,
                'new_status' => 'Approved',
                'remarks' => $request->input('remarks'),
            ],
            'notes' => "Overtime of {$rec->overtime_hours} hrs on {$rec->date?->toDateString()} approved by " . ($user?->name ?? 'Admin'),
            'action_by' => $user?->id,
        ]);

        AuditLogger::log('Update', 'Overtime', $rec->id, "Approved overtime of {$rec->overtime_hours} hrs for employee #{$rec->employee_id} on {$rec->date?->toDateString()}");

        return redirect()->back()->with('success', "Overtime of {$rec->overtime_hours} hrs approved successfully.");
    }

    public function overtimeReject(int $id, Request $request): RedirectResponse
    {
        $rec = AttendanceRecord::findOrFail($id);
        $user = Auth::user();

        $prevStatus = $rec->overtime_status;
        $rec->update([
            'overtime_status' => 'Rejected',
            'overtime_approved_by' => $user?->id,
            'overtime_approved_at' => now(),
            'overtime_remarks' => $request->input('remarks', 'Overtime rejected by manager/HR'),
        ]);

        AttendanceActionLog::create([
            'attendance_record_id' => $rec->id,
            'employee_id' => $rec->employee_id,
            'action_type' => 'overtime_rejected',
            'details' => [
                'date' => $rec->date?->toDateString(),
                'overtime_hours' => $rec->overtime_hours,
                'previous_status' => $prevStatus,
                'new_status' => 'Rejected',
                'remarks' => $request->input('remarks'),
            ],
            'notes' => "Overtime of {$rec->overtime_hours} hrs on {$rec->date?->toDateString()} rejected by " . ($user?->name ?? 'Admin'),
            'action_by' => $user?->id,
        ]);

        AuditLogger::log('Update', 'Overtime', $rec->id, "Rejected overtime of {$rec->overtime_hours} hrs for employee #{$rec->employee_id} on {$rec->date?->toDateString()}");

        return redirect()->back()->with('success', "Overtime rejected successfully.");
    }

    public function overtimeBatchApprove(Request $request): RedirectResponse
    {
        $ids = $request->input('record_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', "No overtime records selected.");
        }

        $user = Auth::user();
        $records = AttendanceRecord::whereIn('id', $ids)->where('overtime_hours', '>', 0)->get();

        foreach ($records as $rec) {
            $prevStatus = $rec->overtime_status;
            $rec->update([
                'overtime_status' => 'Approved',
                'overtime_approved_by' => $user?->id,
                'overtime_approved_at' => now(),
            ]);

            AttendanceActionLog::create([
                'attendance_record_id' => $rec->id,
                'employee_id' => $rec->employee_id,
                'action_type' => 'overtime_approved',
                'details' => [
                    'date' => $rec->date?->toDateString(),
                    'overtime_hours' => $rec->overtime_hours,
                    'previous_status' => $prevStatus,
                    'new_status' => 'Approved',
                    'batch' => true,
                ],
                'notes' => "Batch approved overtime by " . ($user?->name ?? 'Admin'),
                'action_by' => $user?->id,
            ]);
        }

        $count = count($records);
        return redirect()->back()->with('success', "Successfully batch approved {$count} overtime record(s).");
    }

    // ==========================================
    // 4.5. UNDERTIME MANAGEMENT
    // ==========================================

    public function undertimeIndex(Request $request): View
    {
        $user = Auth::user();
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('undertime_status', $request->status);
        }
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhereHas('employee.branch', function ($bq) use ($search) {
                    $bq->where('name', 'like', "%{$search}%");
                })->orWhereHas('employee.department', function ($dq) use ($search) {
                    $dq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $metricsQuery = clone $query;
        $totalUndertimeMinutes = (clone $metricsQuery)->sum('undertime_minutes');
        $pendingCount = (clone $metricsQuery)->where('undertime_status', 'Pending')->count();
        $authorizedCount = (clone $metricsQuery)->where('undertime_status', 'Approved')->count();
        $unauthorizedCount = (clone $metricsQuery)->where('undertime_status', 'Rejected')->count();

        $perPage = (int) $request->get('per_page', 10);
        $records = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
        $branches = Branch::where('is_active', true)->get();

        return view('hr.attendance.undertime', compact(
            'records', 'branches', 'totalUndertimeMinutes', 'pendingCount', 'authorizedCount', 'unauthorizedCount'
        ));
    }

    public function undertimeAuthorize(int $id, Request $request): RedirectResponse
    {
        $rec = AttendanceRecord::findOrFail($id);
        $user = Auth::user();

        $prevStatus = $rec->undertime_status;
        $rec->update([
            'undertime_status' => 'Approved',
            'undertime_approved_by' => $user?->id,
            'undertime_approved_at' => now(),
            'undertime_remarks' => $request->input('remarks', $rec->undertime_remarks),
        ]);

        AttendanceActionLog::create([
            'attendance_record_id' => $rec->id,
            'employee_id' => $rec->employee_id,
            'action_type' => 'undertime_authorized',
            'details' => [
                'date' => $rec->date?->toDateString(),
                'undertime_minutes' => $rec->undertime_minutes,
                'previous_status' => $prevStatus,
                'new_status' => 'Approved',
                'remarks' => $request->input('remarks'),
            ],
            'notes' => "Undertime of {$rec->undertime_minutes} mins on {$rec->date?->toDateString()} authorized by " . ($user?->name ?? 'Admin'),
            'action_by' => $user?->id,
        ]);

        AuditLogger::log('Update', 'Undertime', $rec->id, "Authorized undertime of {$rec->undertime_minutes} mins for employee #{$rec->employee_id} on {$rec->date?->toDateString()}");

        return redirect()->back()->with('success', "Undertime of {$rec->undertime_minutes} mins authorized successfully.");
    }

    public function undertimeReject(int $id, Request $request): RedirectResponse
    {
        $rec = AttendanceRecord::findOrFail($id);
        $user = Auth::user();

        $prevStatus = $rec->undertime_status;
        $rec->update([
            'undertime_status' => 'Rejected',
            'undertime_approved_by' => $user?->id,
            'undertime_approved_at' => now(),
            'undertime_remarks' => $request->input('remarks', 'Marked as unauthorized undertime'),
        ]);

        AttendanceActionLog::create([
            'attendance_record_id' => $rec->id,
            'employee_id' => $rec->employee_id,
            'action_type' => 'undertime_rejected',
            'details' => [
                'date' => $rec->date?->toDateString(),
                'undertime_minutes' => $rec->undertime_minutes,
                'previous_status' => $prevStatus,
                'new_status' => 'Rejected',
                'remarks' => $request->input('remarks'),
            ],
            'notes' => "Undertime of {$rec->undertime_minutes} mins on {$rec->date?->toDateString()} marked unauthorized by " . ($user?->name ?? 'Admin'),
            'action_by' => $user?->id,
        ]);

        AuditLogger::log('Update', 'Undertime', $rec->id, "Marked undertime of {$rec->undertime_minutes} mins as unauthorized for employee #{$rec->employee_id} on {$rec->date?->toDateString()}");

        return redirect()->back()->with('success', "Undertime marked as unauthorized.");
    }

    public function undertimeBatchAuthorize(Request $request): RedirectResponse
    {
        $ids = $request->input('record_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', "No undertime records selected.");
        }

        $user = Auth::user();
        $records = AttendanceRecord::whereIn('id', $ids)->where('undertime_minutes', '>', 0)->get();

        foreach ($records as $rec) {
            $prevStatus = $rec->undertime_status;
            $rec->update([
                'undertime_status' => 'Approved',
                'undertime_approved_by' => $user?->id,
                'undertime_approved_at' => now(),
            ]);

            AttendanceActionLog::create([
                'attendance_record_id' => $rec->id,
                'employee_id' => $rec->employee_id,
                'action_type' => 'undertime_authorized',
                'details' => [
                    'date' => $rec->date?->toDateString(),
                    'undertime_minutes' => $rec->undertime_minutes,
                    'previous_status' => $prevStatus,
                    'new_status' => 'Approved',
                    'batch' => true,
                ],
                'notes' => "Batch authorized undertime by " . ($user?->name ?? 'Admin'),
                'action_by' => $user?->id,
            ]);
        }

        $count = count($records);
        return redirect()->back()->with('success', "Successfully authorized {$count} undertime record(s).");
    }

    // ==========================================
    // 5. MANUAL TIME ENTRIES (Direct Encoding & Adjustment Workflow)
    // ==========================================

    public function correctionsIndex(Request $request): View
    {
        $user = Auth::user();

        // Query attendance records strictly for manual time entries
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'corrections.requester'])
            ->where('source', 'Manual');

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('employee.branch', function ($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        $records = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();

        // Corrections collection for backwards compatibility
        $corrections = AttendanceCorrection::with(['employee.branch', 'attendanceRecord', 'requester', 'reviewer'])
            ->orderBy('created_at', 'desc')->paginate(10);

        $employees = Employee::activeWorkforce()
            ->when(!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('first_name')
            ->get();

        $branches = Branch::where('is_active', true)->get();

        return view('hr.attendance.corrections', compact('records', 'corrections', 'employees', 'branches'));
    }

    public function lookupAttendance(Request $request): JsonResponse
    {
        $employeeId = $request->get('employee_id');
        $date = $request->get('date');

        if (!$employeeId || !$date) {
            return response()->json(['exists' => false]);
        }

        $cleanDate = Carbon::parse($date)->toDateString();
        $att = AttendanceRecord::where('employee_id', $employeeId)
            ->whereDate('date', $cleanDate)
            ->first();

        if (!$att) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'id' => $att->id,
            'time_in' => $att->time_in ? substr($att->time_in, 0, 5) : '',
            'break_out' => $att->break_out ? substr($att->break_out, 0, 5) : '',
            'break_in' => $att->break_in ? substr($att->break_in, 0, 5) : '',
            'coffee_break_out' => $att->coffee_break_out ? substr($att->coffee_break_out, 0, 5) : '',
            'coffee_break_in' => $att->coffee_break_in ? substr($att->coffee_break_in, 0, 5) : '',
            'time_out' => $att->time_out ? substr($att->time_out, 0, 5) : '',
            'status' => $att->status ?? 'Present',
            'notes' => $att->notes ?? '',
            'total_hours' => number_format($att->total_hours ?? 0, 2),
        ]);
    }

    public function correctionStore(Request $request): RedirectResponse
    {
        // Resolve date: from explicit input, or auto-derived from any datetime punch, or today
        $dateInput = $request->input('date');
        if (empty($dateInput)) {
            foreach (['time_in', 'requested_time_in', 'in_1', 'time_out', 'requested_time_out', 'break_out', 'break_in', 'coffee_break_out', 'coffee_break_in'] as $f) {
                if ($request->filled($f)) {
                    try {
                        $dateInput = Carbon::parse($request->input($f))->toDateString();
                        break;
                    } catch (\Exception $e) {}
                }
            }
            if (empty($dateInput)) {
                $dateInput = Carbon::today()->toDateString();
            }
            $request->merge(['date' => $dateInput]);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'date' => 'required|date',
            'time_in' => 'nullable',
            'break_out' => 'nullable',
            'break_in' => 'nullable',
            'coffee_break_out' => 'nullable',
            'coffee_break_in' => 'nullable',
            'time_out' => 'nullable',
            'requested_time_in' => 'nullable',
            'requested_time_out' => 'nullable',
            'status' => 'nullable|string',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'supporting_document' => 'nullable|file|max:10240',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $date = $validated['date'];

        $timeIn = $request->input('time_in') ?: $request->input('requested_time_in');
        $timeOut = $request->input('time_out') ?: $request->input('requested_time_out');
        $breakOut = $request->input('break_out');
        $breakIn = $request->input('break_in');
        $coffeeBreakOut = $request->input('coffee_break_out');
        $coffeeBreakIn = $request->input('coffee_break_in');
        $notes = $request->input('notes') ?: ($request->input('reason') ?: 'Manual time entry');

        $formatTime = function ($val) {
            if (!$val) return null;
            $val = trim($val);
            try {
                // If it contains date or T (datetime-local format), extract time part H:i:s
                if (str_contains($val, 'T') || str_contains($val, '-') || strlen($val) > 8) {
                    return Carbon::parse($val)->format('H:i:s');
                }
                if (strlen($val) === 5) {
                    return $val . ':00';
                }
                return $val;
            } catch (\Exception $e) {
                return null;
            }
        };

        // If timeIn contains a date, use that as the primary attendance date
        if ($timeIn && (str_contains($timeIn, 'T') || str_contains($timeIn, '-'))) {
            try {
                $date = Carbon::parse($timeIn)->toDateString();
            } catch (\Exception $e) {}
        }

        $timeInFmt = $formatTime($timeIn);
        $timeOutFmt = $formatTime($timeOut);
        $breakOutFmt = $formatTime($breakOut);
        $breakInFmt = $formatTime($breakIn);
        $coffeeOutFmt = $formatTime($coffeeBreakOut);
        $coffeeInFmt = $formatTime($coffeeBreakIn);

        $cleanDate = Carbon::parse($date)->toDateString();
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('date', $cleanDate)
            ->first() ?? new AttendanceRecord([
                'employee_id' => $employee->id,
                'date' => $cleanDate,
            ]);

        $prev = $record->toArray();
        $record->branch_id = $employee->branch_id;
        $record->source = 'Manual';

        if ($timeInFmt !== null) {
            $record->time_in = $timeInFmt;
            $record->in_1 = $timeInFmt;
        }
        if ($breakOutFmt !== null) {
            $record->break_out = $breakOutFmt;
            $record->out_1 = $breakOutFmt;
        }
        if ($breakInFmt !== null) {
            $record->break_in = $breakInFmt;
            $record->in_2 = $breakInFmt;
        }
        if ($coffeeOutFmt !== null) {
            $record->coffee_break_out = $coffeeOutFmt;
            $record->out_2 = $coffeeOutFmt;
        }
        if ($coffeeInFmt !== null) {
            $record->coffee_break_in = $coffeeInFmt;
            $record->in_3 = $coffeeInFmt;
        }
        if ($timeOutFmt !== null) {
            $record->time_out = $timeOutFmt;
            $record->out_3 = $timeOutFmt;
        }

        $record->notes = $notes;

        // Recalculate metrics if time_in and time_out are provided
        $schedule = EmployeeSchedule::with('shiftTemplate')
            ->where('employee_id', $employee->id)
            ->where('schedule_date', $date)
            ->first();

        $shift = $schedule?->shiftTemplate;
        $schedStart = $schedule?->custom_start_time ?? $shift?->start_time;
        $schedEnd = $schedule?->custom_end_time ?? $shift?->end_time;
        $isOvernight = (bool) ($shift?->is_overnight ?? false);

        // Auto-detect overnight shift if datetime punches span into the next day
        if ($timeIn && $timeOut && (str_contains($timeIn, 'T') || str_contains($timeIn, '-')) && (str_contains($timeOut, 'T') || str_contains($timeOut, '-'))) {
            try {
                $dtIn = Carbon::parse($timeIn);
                $dtOut = Carbon::parse($timeOut);
                if ($dtOut->gt($dtIn) && $dtOut->toDateString() !== $dtIn->toDateString()) {
                    $isOvernight = true;
                }
            } catch (\Exception $e) {}
        }

        if ($record->time_in && $record->time_out) {
            $calc = $this->calcService->calculate(
                $date,
                $schedStart,
                $schedEnd,
                $record->time_in,
                $record->time_out,
                $record->break_out,
                $record->break_in,
                $isOvernight,
                $record->coffee_break_out,
                $record->coffee_break_in
            );

            $record->total_hours = $calc['total_hours'];
            $record->regular_hours = $calc['regular_hours'];
            $record->late_minutes = $calc['late_minutes'];
            $record->undertime_minutes = $calc['undertime_minutes'];
            $record->overtime_hours = $calc['overtime_hours'];
            $record->night_diff_hours = $calc['night_diff_hours'];
            $record->status = (!empty($validated['status']) && $validated['status'] !== 'Auto') 
                ? $validated['status'] 
                : $calc['status'];
        } elseif (!empty($validated['status']) && $validated['status'] !== 'Auto') {
            $record->status = $validated['status'];
        } else {
            $record->status = 'Present';
        }

        $record->save();

        // Also record/sync in attendance_corrections table for permanent audit
        $docPath = null;
        if ($request->hasFile('supporting_document')) {
            $docPath = $request->file('supporting_document')->store('corrections', 'public');
        }

        AttendanceCorrection::updateOrCreate(
            [
                'attendance_record_id' => $record->id,
                'employee_id' => $employee->id,
            ],
            [
                'requested_by' => Auth::id() ?? 1,
                'reviewed_by' => Auth::id() ?? 1,
                'reviewed_at' => now(),
                'original_time_in' => $prev['time_in'] ?? null,
                'original_time_out' => $prev['time_out'] ?? null,
                'requested_time_in' => $record->time_in,
                'requested_time_out' => $record->time_out,
                'reason' => $notes,
                'supporting_document' => $docPath,
                'status' => 'Approved',
                'reviewer_notes' => 'Directly encoded manual entry',
            ]
        );

        AttendanceActionLog::create([
            'attendance_record_id' => $record->id,
            'employee_id' => $record->employee_id,
            'action_type' => 'manual_entry_saved',
            'details' => [
                'date' => $cleanDate,
                'time_in' => $record->time_in,
                'time_out' => $record->time_out,
                'total_hours' => $record->total_hours,
                'status' => $record->status,
                'notes' => $notes,
            ],
            'notes' => "Manual time entry encoded/updated by " . (Auth::user()?->name ?? 'Admin'),
            'action_by' => Auth::id(),
        ]);

        AuditLogger::log(
            'ManualEntry',
            'Attendance',
            $record->id,
            "Encoded manual time entry for {$employee->full_name} on {$date} (In: {$record->time_in}, Out: {$record->time_out})"
        );

        return redirect()->back()->with('success', "Manual time entry saved successfully for {$employee->full_name} on {$date}.");
    }

    public function manualEntryDestroy(int $id): RedirectResponse
    {
        $record = AttendanceRecord::findOrFail($id);
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id && $record->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to delete manual entry for another branch.');
        }

        AttendanceCorrection::where('attendance_record_id', $record->id)->delete();

        $empId = $record->employee_id;
        $empName = $record->employee?->full_name ?? "Employee #{$record->employee_id}";
        $cleanDate = $record->date ? \Carbon\Carbon::parse($record->date)->toDateString() : null;
        $date = $record->date ? \Carbon\Carbon::parse($record->date)->format('M d, Y') : 'N/A';

        AttendanceActionLog::create([
            'attendance_record_id' => null,
            'employee_id' => $empId,
            'action_type' => 'manual_entry_deleted',
            'details' => [
                'date' => $cleanDate,
                'time_in' => $record->time_in,
                'time_out' => $record->time_out,
                'total_hours' => $record->total_hours,
            ],
            'notes' => "Manual time entry for {$empName} on {$date} deleted by " . (Auth::user()?->name ?? 'Admin'),
            'action_by' => Auth::id(),
        ]);

        $record->delete();

        AuditLogger::log('Delete', 'Attendance', $id, "Deleted manual time entry for {$empName} on {$date}");

        return redirect()->back()->with('success', "Manual time entry for {$empName} on {$date} deleted successfully.");
    }

    public function correctionReview(Request $request, int $id): RedirectResponse
    {
        $correction = AttendanceCorrection::with('attendanceRecord.employee')->findOrFail($id);
        $validated = $request->validate([
            'action' => 'required|in:Approved,Rejected',
            'reviewer_notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        if ($correction->attendanceRecord && $correction->attendanceRecord->employee && !$user->canAccessBranch($correction->attendanceRecord->employee->branch_id)) {
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

            if ($status === 'Approved' && $correction->attendanceRecord) {
                $att = $correction->attendanceRecord;
                $prev = $att->toArray();

                if ($correction->requested_time_in) {
                    $att->time_in = $correction->requested_time_in;
                }
                if ($correction->requested_time_out) {
                    $att->time_out = $correction->requested_time_out;
                }

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

                AttendanceActionLog::create([
                    'attendance_record_id' => $att->id,
                    'employee_id' => $att->employee_id,
                    'action_type' => 'manual_entry_reviewed',
                    'details' => [
                        'status' => 'Approved',
                        'time_in' => $att->time_in,
                        'time_out' => $att->time_out,
                        'reviewer_notes' => $validated['reviewer_notes'] ?? null,
                    ],
                    'notes' => "Correction request #{$correction->id} approved by " . ($user?->name ?? 'Admin'),
                    'action_by' => $user?->id,
                ]);

                AuditLogger::log(
                    'Approve',
                    'Attendance',
                    $att->id,
                    "Approved correction request #{$correction->id}. Updated time in: {$att->time_in}, time out: {$att->time_out}",
                    $prev,
                    $att->toArray()
                );
            } else {
                AttendanceActionLog::create([
                    'attendance_record_id' => $correction->attendance_record_id,
                    'employee_id' => $correction->employee_id,
                    'action_type' => 'manual_entry_reviewed',
                    'details' => [
                        'status' => 'Rejected',
                        'reviewer_notes' => $validated['reviewer_notes'] ?? null,
                    ],
                    'notes' => "Correction request #{$correction->id} rejected by " . ($user?->name ?? 'Admin'),
                    'action_by' => $user?->id,
                ]);

                AuditLogger::log('Reject', 'Attendance', $correction->id, "Rejected correction request #{$correction->id}");
            }
        });

        return redirect()->back()->with('success', "Correction request {$validated['action']}.");
    }
}
