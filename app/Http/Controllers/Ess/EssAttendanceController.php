<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\AttendanceCorrection;
use App\Models\Ess\EssRequest;
use App\Services\DoleLaborStandardsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EssAttendanceController extends Controller
{
    protected DoleLaborStandardsService $doleService;

    public function __construct(DoleLaborStandardsService $doleService)
    {
        $this->doleService = $doleService;
    }

    public function dtrIndex(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        // Period filter (default current month)
        $month = $request->input('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $records = AttendanceRecord::where(function ($q) use ($empIntId, $employee) {
                $q->where('employee_id', $empIntId)
                  ->orWhere('employee_id', $employee->employee_id);
            })
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        // Totals
        $totalRegHours = 0.0;
        $totalOtHours = 0.0;
        $totalNightDiffHours = 0.0;
        $totalLateMinutes = 0;
        $totalUndertimeMinutes = 0;

        foreach ($records as $r) {
            $totalRegHours += (float) ($r->regular_hours ?? 0);
            $totalOtHours += (float) ($r->overtime_hours ?? 0);
            $totalLateMinutes += (int) ($r->late_minutes ?? 0);
            $totalUndertimeMinutes += (int) ($r->undertime_minutes ?? 0);

            if (!empty($r->time_in) && !empty($r->time_out)) {
                $nsd = $this->doleService->calculateNightShiftDifferential(
                    $employee->basic_salary ? ($employee->basic_salary / 22 / 8) : 100.0,
                    $r->time_in,
                    $r->time_out
                );
                $r->calculated_nsd_hours = $nsd['night_diff_hours'];
                $totalNightDiffHours += $nsd['night_diff_hours'];
            }
        }

        return view('ess.attendance.dtr', compact(
            'employee',
            'records',
            'month',
            'totalRegHours',
            'totalOtHours',
            'totalNightDiffHours',
            'totalLateMinutes',
            'totalUndertimeMinutes'
        ));
    }

    public function submitCorrection(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $validated = $request->validate([
            'attendance_record_id' => 'required|exists:hr_attendance_records,attendance_record_id',
            'corrected_time_in' => 'required',
            'corrected_time_out' => 'required',
            'reason_category' => 'required|string',
            'explanation' => 'required|string',
        ]);

        $record = AttendanceRecord::where('attendance_record_id', $validated['attendance_record_id'])
            ->where(function ($q) use ($empIntId, $employee) {
                $q->where('employee_id', $empIntId)
                  ->orWhere('employee_id', $employee->employee_id);
            })
            ->firstOrFail();

        DB::transaction(function () use ($employee, $record, $validated, $empIntId) {
            $reqNumber = EssRequest::generateRequestNumber('CORR');

            // 1. Create ESS Request
            EssRequest::create([
                'request_number' => $reqNumber,
                'employee_id' => $employee->employee_id,
                'request_type' => 'ATTENDANCE_CORRECTION',
                'details' => [
                    'attendance_record_id' => $record->id,
                    'record_date' => $record->date?->toDateString(),
                    'original_time_in' => $record->time_in,
                    'original_time_out' => $record->time_out,
                    'corrected_time_in' => $validated['corrected_time_in'],
                    'corrected_time_out' => $validated['corrected_time_out'],
                    'reason_category' => $validated['reason_category'],
                ],
                'reason' => $validated['explanation'],
                'status' => 'PENDING',
                'approval_step' => 1,
            ]);

            // 2. Log in AttendanceCorrection
            AttendanceCorrection::create([
                'attendance_record_id' => $record->id,
                'employee_id' => $empIntId,
                'requested_by' => auth()->id() ?? 1,
                'original_time_in' => $record->time_in,
                'original_time_out' => $record->time_out,
                'requested_time_in' => $validated['corrected_time_in'],
                'requested_time_out' => $validated['corrected_time_out'],
                'reason' => "[{$validated['reason_category']}] " . $validated['explanation'],
                'status' => 'Pending',
            ]);
        });

        return redirect()->back()->with('success', 'Attendance correction request submitted for supervisor approval.');
    }

    public function exportPdf(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();
        $month = $request->input('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $records = AttendanceRecord::where(function ($q) use ($empIntId, $employee) {
                $q->where('employee_id', $empIntId)
                  ->orWhere('employee_id', $employee->employee_id);
            })
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        return view('ess.attendance.export_pdf', compact('employee', 'records', 'month'));
    }
}
