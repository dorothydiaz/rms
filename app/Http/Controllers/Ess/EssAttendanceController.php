<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceCorrection;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\EssRequest;
use App\Models\Hr\EssRequestLog;
use App\Services\Hr\DoleComplianceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EssAttendanceController extends Controller
{
    public function __construct(
        protected DoleComplianceService $doleService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return view('ess.no-employee-profile', compact('user'));
        }

        $month = $request->input('month', now()->format('Y-m'));
        $dateObj = Carbon::createFromFormat('Y-m', $month) ?? now();
        $startDate = $dateObj->copy()->startOfMonth();
        $endDate = $dateObj->copy()->endOfMonth();

        $records = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Calculate DOLE compliance attributes for each record
        foreach ($records as $rec) {
            $rec->night_diff_hours = 0.0;
            if ($rec->time_in && $rec->time_out) {
                $rec->night_diff_hours = $this->doleService->calculateNightDiffHours(
                    $rec->date->format('Y-m-d') . ' ' . $rec->time_in,
                    $rec->date->format('Y-m-d') . ' ' . $rec->time_out
                );
            }
            $rec->ot_multiplier = $this->doleService->getOvertimeMultiplier(
                $rec->holiday_type ?: ($rec->is_rest_day ? 'REST_DAY' : 'ORDINARY'),
                (bool) $rec->is_rest_day
            );
        }

        $totalHours = $records->sum('regular_hours');
        $totalOt = $records->sum('overtime_hours');
        $totalLate = $records->sum('late_minutes');
        $totalUndertime = $records->sum('undertime_minutes');
        $totalNightDiff = $records->sum('night_diff_hours');

        return view('ess.attendance', compact(
            'user',
            'employee',
            'records',
            'month',
            'totalHours',
            'totalOt',
            'totalLate',
            'totalUndertime',
            'totalNightDiff'
        ));
    }

    public function exportDtrPdf(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $month = $request->input('month', now()->format('Y-m'));
        $dateObj = Carbon::createFromFormat('Y-m', $month) ?? now();
        $startDate = $dateObj->copy()->startOfMonth();
        $endDate = $dateObj->copy()->endOfMonth();

        $records = AttendanceRecord::where('employee_id', $employee->employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        return view('ess.dtr-print', compact('user', 'employee', 'records', 'month'));
    }

    public function storeCorrectionRequest(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'reason' => 'required|string|max:500',
        ]);

        $reqNumber = EssRequest::generateRequestNumber('CORR');

        $essReq = EssRequest::create([
            'request_number' => $reqNumber,
            'employee_id' => $employee->employee_id,
            'request_type' => 'ATTENDANCE_CORRECTION',
            'status' => 'PENDING_APPROVAL',
            'start_date' => $validated['date'],
            'start_time' => $validated['time_in'] ?? null,
            'end_time' => $validated['time_out'] ?? null,
            'reason' => $validated['reason'],
            'metadata' => [
                'target_date' => $validated['date'],
                'requested_time_in' => $validated['time_in'] ?? null,
                'requested_time_out' => $validated['time_out'] ?? null,
            ],
            'current_approver_id' => $employee->supervisor?->user_id,
        ]);

        // Also record in hr_attendance_corrections for compatibility with HR admin module
        AttendanceCorrection::create([
            'employee_id' => $employee->employee_id,
            'date' => $validated['date'],
            'requested_time_in' => $validated['time_in'] ?? null,
            'requested_time_out' => $validated['time_out'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'Pending',
        ]);

        EssRequestLog::create([
            'ess_request_id' => $essReq->id,
            'action' => 'SUBMITTED',
            'acted_by' => $user->id,
            'remarks' => "Submitted attendance correction for {$validated['date']}",
        ]);

        return redirect()->back()->with('success', "Attendance correction request #{$reqNumber} submitted.");
    }
}
