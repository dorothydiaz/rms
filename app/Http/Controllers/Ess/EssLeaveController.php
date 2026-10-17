<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveType;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Services\DoleLaborStandardsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EssLeaveController extends Controller
{
    protected DoleLaborStandardsService $doleService;

    public function __construct(DoleLaborStandardsService $doleService)
    {
        $this->doleService = $doleService;
    }

    public function index(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        // 1. Fetch configured leave types
        $leaveTypes = LeaveType::all();

        // 2. Fetch or compute balances
        $balances = LeaveBalance::where('employee_id', $empIntId)->with('leaveType')->get();

        // Check SIL statutory tenure eligibility (>= 1 year / 365 days)
        $dateHired = $employee->date_hired ? Carbon::parse($employee->date_hired) : now()->subYears(2);
        $tenureDays = (int) $dateHired->diffInDays(now());
        $isEligibleForSil = $this->doleService->isEligibleForSil($tenureDays);
        $isEligibleForSoloParent = $this->doleService->isEligibleForSoloParent($tenureDays);

        // 3. Employee's leave request history
        $requests = LeaveRequest::where('employee_id', $empIntId)
            ->with(['leaveType', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ess.leave.index', compact(
            'employee',
            'leaveTypes',
            'balances',
            'requests',
            'isEligibleForSil',
            'isEligibleForSoloParent',
            'tenureDays'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:hr_leave_types,leave_type_id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:Full Day,Half Day,Half Day - Morning,Half Day - Afternoon',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        if (str_starts_with($validated['duration_type'], 'Half Day')) {
            $numDays = 0.5;
        } else {
            // Count weekdays (excluding Sundays/rest days by default)
            $numDays = max(1.0, (float) ($start->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isSunday();
            }, $end) + 1));
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        DB::transaction(function () use ($empIntId, $validated, $numDays, $attachmentPath) {
            LeaveRequest::create([
                'employee_id' => $empIntId,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'number_of_days' => $numDays,
                'reason' => $validated['reason'],
                'attachment' => $attachmentPath,
                'status' => 'Pending',
            ]);
        });

        return redirect()->back()->with('success', "Leave request of {$numDays} day(s) filed successfully for supervisor approval.");
    }

    public function cancel(int $id, Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $leaveReq = LeaveRequest::where('leave_request_id', $id)
            ->where('employee_id', $empIntId)
            ->firstOrFail();

        if ($leaveReq->status !== 'Pending') {
            return redirect()->back()->with('error', 'Only pending leave requests can be cancelled.');
        }

        $leaveReq->update([
            'status' => 'Cancelled',
        ]);

        return redirect()->back()->with('success', 'Leave request cancelled successfully.');
    }
}
