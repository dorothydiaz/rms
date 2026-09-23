<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Branch;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Services\AuditLogger;
use App\Services\LeaveCalculationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveController extends Controller
{
    protected LeaveCalculationService $leaveService;

    public function __construct(LeaveCalculationService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    // ==========================================
    // 1. LEAVE REQUESTS
    // ==========================================

    public function requestsIndex(Request $request): View
    {
        $user = Auth::user();
        $query = LeaveRequest::with(['employee.branch', 'leaveType', 'approver']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);
        $leaveTypes = LeaveType::all();
        $employees = Employee::where('employment_status', 'Active')->get();

        return view('hr.leave.requests', compact('requests', 'leaveTypes', 'employees'));
    }

    public function requestStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'number_of_days' => 'required|numeric|min:0.5',
            'reason' => 'required|string',
            'supporting_document' => 'nullable|file|max:5120',
        ]);

        $year = Carbon::parse($validated['start_date'])->year;

        // Policy check: Sufficient credits
        if (!$this->leaveService->hasSufficientBalance((int) $validated['employee_id'], (int) $validated['leave_type_id'], (float) $validated['number_of_days'], $year)) {
            return redirect()->back()->with('error', 'Cannot file leave request: Insufficient remaining leave balance for this leave type.');
        }

        $docPath = null;
        if ($request->hasFile('supporting_document')) {
            $docPath = $request->file('supporting_document')->store('leave_documents', 'public');
        }

        $leaveReq = LeaveRequest::create([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'number_of_days' => $validated['number_of_days'],
            'reason' => $validated['reason'],
            'supporting_document' => $docPath,
            'status' => 'Pending',
        ]);

        AuditLogger::log('Create', 'Leave', $leaveReq->id, "Filed leave request for employee #{$leaveReq->employee_id} ({$leaveReq->number_of_days} days)");

        return redirect()->back()->with('success', 'Leave request submitted successfully.');
    }

    public function requestReview(Request $request, int $id): RedirectResponse
    {
        $leaveReq = LeaveRequest::with('employee')->findOrFail($id);
        $validated = $request->validate([
            'action' => 'required|in:Approved,Rejected,Cancelled',
            'rejection_reason' => 'nullable|string',
        ]);

        $user = Auth::user();
        if ($leaveReq->employee && !$user->canAccessBranch($leaveReq->employee->branch_id)) {
            abort(403, 'Unauthorized to review leave request for another branch.');
        }

        DB::transaction(function () use ($leaveReq, $validated, $user) {
            $prevStatus = $leaveReq->status;
            $newStatus = $validated['action'];

            $leaveReq->update([
                'status' => $newStatus,
                'approver_id' => $user->id,
                'approval_date' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);

            // If Approved, deduct credits from employee balance
            if ($newStatus === 'Approved') {
                $this->leaveService->deductCredits($leaveReq);
            } elseif ($prevStatus === 'Approved' && ($newStatus === 'Rejected' || $newStatus === 'Cancelled')) {
                // Restore credits if changing from Approved to Rejected/Cancelled
                $this->leaveService->restoreCredits($leaveReq);
            }

            AuditLogger::log(
                $newStatus === 'Approved' ? 'Approve' : 'Reject',
                'Leave',
                $leaveReq->id,
                "Leave request #{$leaveReq->id} {$newStatus} by {$user->full_name}"
            );
        });

        return redirect()->back()->with('success', "Leave request {$validated['action']}.");
    }

    // ==========================================
    // 2. LEAVE TYPES
    // ==========================================

    public function typesIndex(): View
    {
        $types = LeaveType::withCount('balances')->get();
        return view('hr.leave.types', compact('types'));
    }

    public function typeStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:leave_types,code',
            'description' => 'nullable|string',
            'is_paid' => 'boolean',
            'default_credits' => 'required|numeric|min:0',
            'is_cumulative' => 'boolean',
        ]);

        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['is_cumulative'] = $request->boolean('is_cumulative');

        $type = LeaveType::create($validated);
        AuditLogger::log('Create', 'Leave', $type->id, "Created leave type '{$type->name}'");

        return redirect()->back()->with('success', "Leave type '{$type->name}' created.");
    }

    public function typeUpdate(Request $request, int $id): RedirectResponse
    {
        $type = LeaveType::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:leave_types,code,' . $id,
            'description' => 'nullable|string',
            'is_paid' => 'boolean',
            'default_credits' => 'required|numeric|min:0',
            'is_cumulative' => 'boolean',
        ]);

        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['is_cumulative'] = $request->boolean('is_cumulative');

        $type->update($validated);
        AuditLogger::log('Update', 'Leave', $type->id, "Updated leave type '{$type->name}'");

        return redirect()->back()->with('success', "Leave type updated.");
    }

    // ==========================================
    // 3. LEAVE CREDITS / BALANCES
    // ==========================================

    public function creditsIndex(Request $request): View
    {
        $user = Auth::user();
        $year = (int) $request->get('year', Carbon::now()->year);

        $query = LeaveBalance::with(['employee.branch', 'leaveType'])
            ->where('year', $year);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $balances = $query->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::all();
        $employees = Employee::where('employment_status', 'Active')->get();

        return view('hr.leave.credits', compact('balances', 'year', 'leaveTypes', 'employees'));
    }

    public function creditsAdjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer',
            'beginning_balance' => 'required|numeric|min:0',
            'earned' => 'required|numeric|min:0',
            'used' => 'required|numeric|min:0',
            'encashed' => 'required|numeric|min:0',
        ]);

        $remaining = max(0.00, $validated['beginning_balance'] + $validated['earned'] - $validated['used'] - $validated['encashed']);

        $balance = LeaveBalance::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'year' => $validated['year'],
            ],
            array_merge($validated, ['remaining' => $remaining])
        );

        AuditLogger::log('Update', 'Leave', $balance->id, "Adjusted leave credits for employee #{$balance->employee_id}");

        return redirect()->back()->with('success', "Leave credits updated successfully.");
    }

    // ==========================================
    // 4. LEAVE REPORTS
    // ==========================================

    public function reportsIndex(Request $request): View
    {
        $year = (int) $request->get('year', Carbon::now()->year);
        $leaveTypes = LeaveType::with(['balances' => fn($q) => $q->where('year', $year)])->get();

        $utilizationData = [];
        foreach ($leaveTypes as $lt) {
            $allocated = $lt->balances->sum('beginning_balance') + $lt->balances->sum('earned');
            $used = $lt->balances->sum('used');
            $remaining = $lt->balances->sum('remaining');
            $utilizationData[] = [
                'name' => $lt->name,
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => $remaining,
                'utilization_rate' => $allocated > 0 ? round(($used / $allocated) * 100, 1) : 0,
            ];
        }

        return view('hr.leave.reports', compact('utilizationData', 'year'));
    }
}
