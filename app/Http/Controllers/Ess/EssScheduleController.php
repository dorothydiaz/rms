<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\EssRequest;
use App\Models\Hr\EssRequestLog;
use App\Models\Hr\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EssScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return view('ess.no-employee-profile', compact('user'));
        }

        $weekStart = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfWeek() : now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $schedules = EmployeeSchedule::with('shiftTemplate')
            ->where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->employee_id)
                  ->orWhere('employee_id', $employee->id);
            })
            ->whereBetween('schedule_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('schedule_date', 'asc')
            ->get();

        $shifts = ShiftTemplate::all();

        return view('ess.schedule', compact('user', 'employee', 'schedules', 'weekStart', 'weekEnd', 'shifts'));
    }

    public function storeAdjustmentRequest(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'request_type' => 'required|in:SHIFT_SWAP,SCHEDULE_ADJUSTMENT',
            'reason' => 'required|string|max:500',
            'target_shift_id' => 'nullable|exists:hr_shift_templates,id',
            'swap_with_employee_id' => 'nullable|string|max:50',
        ]);

        $reqNumber = EssRequest::generateRequestNumber('SCH');

        $essReq = EssRequest::create([
            'request_number' => $reqNumber,
            'employee_id' => $employee->employee_id,
            'request_type' => $validated['request_type'],
            'status' => 'PENDING_APPROVAL',
            'start_date' => $validated['date'],
            'reason' => $validated['reason'],
            'metadata' => [
                'target_shift_id' => $validated['target_shift_id'] ?? null,
                'swap_with_employee_id' => $validated['swap_with_employee_id'] ?? null,
            ],
            'current_approver_id' => $employee->supervisor?->user_id,
        ]);

        EssRequestLog::create([
            'ess_request_id' => $essReq->id,
            'action' => 'SUBMITTED',
            'acted_by' => $user->id,
            'remarks' => "Submitted {$validated['request_type']} request for {$validated['date']}",
        ]);

        return redirect()->back()->with('success', "Schedule adjustment request #{$reqNumber} submitted.");
    }
}
