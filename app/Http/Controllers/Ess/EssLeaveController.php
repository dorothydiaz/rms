<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\EssRequest;
use App\Models\Hr\EssRequestLog;
use App\Models\Hr\Holiday;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Services\Hr\DoleComplianceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EssLeaveController extends Controller
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

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->employee_id)
            ->get();

        $leaveTypes = LeaveType::all();

        // Evaluate DOLE statutory eligibility for key leaves
        $statutoryEligibility = [
            'SIL' => $this->doleService->checkStatutoryLeaveEligibility('SIL', $employee),
            'MATERNITY' => $this->doleService->checkStatutoryLeaveEligibility('MATERNITY', $employee),
            'PATERNITY' => $this->doleService->checkStatutoryLeaveEligibility('PATERNITY', $employee),
            'SOLO_PARENT' => $this->doleService->checkStatutoryLeaveEligibility('SOLO_PARENT', $employee),
            'VAWC' => $this->doleService->checkStatutoryLeaveEligibility('VAWC', $employee),
            'MAGNA_CARTA' => $this->doleService->checkStatutoryLeaveEligibility('MAGNA_CARTA', $employee),
        ];

        $myRequests = LeaveRequest::with(['leaveType', 'approver'])
            ->where('employee_id', $employee->employee_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $upcomingHolidays = Holiday::whereDate('date', '>=', now())
            ->orderBy('date', 'asc')
            ->take(5)
            ->get();

        return view('ess.leave', compact(
            'user',
            'employee',
            'balances',
            'leaveTypes',
            'statutoryEligibility',
            'myRequests',
            'upcomingHolidays'
        ));
    }

    public function storeLeaveRequest(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:hr_leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'reason' => 'required|string|max:500',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $isHalfDay = $request->boolean('is_half_day');

        $days = $isHalfDay ? 0.5 : ($startDate->diffInDays($endDate) + 1);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Check statutory eligibility if statutory code applies
        if (!empty($leaveType->code)) {
            $eligibility = $this->doleService->checkStatutoryLeaveEligibility($leaveType->code, $employee);
            if (!$eligibility['eligible'] && !in_array(strtoupper($leaveType->code), ['VL', 'SL', 'EL'])) {
                return redirect()->back()->with('error', "Ineligible for {$leaveType->name}: {$eligibility['reason']}");
            }
        }

        // Check leave balance
        $balance = LeaveBalance::where('employee_id', $employee->employee_id)
            ->where('leave_type_id', $leaveType->id)
            ->first();

        if ($balance && $balance->remaining_days < $days) {
            return redirect()->back()->with('error', "Insufficient leave balance. Available: {$balance->remaining_days} days, Requested: {$days} days.");
        }

        DB::transaction(function () use ($employee, $user, $validated, $leaveType, $days, $isHalfDay, $startDate, $endDate) {
            $leaveReq = LeaveRequest::create([
                'employee_id' => $employee->employee_id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'number_of_days' => $days,
                'is_half_day' => $isHalfDay,
                'reason' => $validated['reason'],
                'status' => 'Pending',
            ]);

            // Sync with universal ESS requests
            $reqNumber = EssRequest::generateRequestNumber('LV');
            $essReq = EssRequest::create([
                'request_number' => $reqNumber,
                'employee_id' => $employee->employee_id,
                'request_type' => 'LEAVE',
                'status' => 'PENDING_APPROVAL',
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_hours' => $days * 8.0,
                'reason' => $validated['reason'],
                'metadata' => [
                    'leave_request_id' => $leaveReq->id,
                    'leave_type_name' => $leaveType->name,
                    'days' => $days,
                ],
                'current_approver_id' => $employee->supervisor?->user_id,
            ]);

            EssRequestLog::create([
                'ess_request_id' => $essReq->id,
                'action' => 'SUBMITTED',
                'acted_by' => $user->id,
                'remarks' => "Filed {$days} day(s) {$leaveType->name}",
            ]);
        });

        return redirect()->back()->with('success', "Leave request submitted successfully for approval.");
    }

    public function cancelLeaveRequest(int $id): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $leaveReq = LeaveRequest::where('id', $id)
            ->where('employee_id', $employee->employee_id)
            ->firstOrFail();

        if ($leaveReq->status !== 'Pending') {
            return redirect()->back()->with('error', "Only Pending leave requests can be cancelled.");
        }

        $leaveReq->update(['status' => 'Cancelled']);

        return redirect()->back()->with('success', "Leave request cancelled.");
    }
}
