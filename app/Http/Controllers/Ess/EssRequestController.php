<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\EssRequest;
use App\Models\Hr\EssRequestLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EssRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return view('ess.no-employee-profile', compact('user'));
        }

        $typeFilter = $request->input('type');
        $statusFilter = $request->input('status');
        $search = $request->input('search');

        $query = EssRequest::with(['currentApprover', 'approver', 'logs.user'])
            ->forEmployee($employee->employee_id)
            ->orderBy('created_at', 'desc');

        if ($typeFilter) {
            $query->where('request_type', $typeFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => EssRequest::forEmployee($employee->employee_id)->count(),
            'pending' => EssRequest::forEmployee($employee->employee_id)->where('status', 'PENDING_APPROVAL')->count(),
            'approved' => EssRequest::forEmployee($employee->employee_id)->where('status', 'APPROVED')->count(),
            'rejected' => EssRequest::forEmployee($employee->employee_id)->where('status', 'REJECTED')->count(),
        ];

        return view('ess.requests', compact('user', 'employee', 'requests', 'stats', 'typeFilter', 'statusFilter', 'search'));
    }

    public function storeOvertime(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'total_hours' => 'required|numeric|min:0.5|max:16',
            'reason' => 'required|string|max:500',
        ]);

        $reqNumber = EssRequest::generateRequestNumber('OT');

        $essReq = EssRequest::create([
            'request_number' => $reqNumber,
            'employee_id' => $employee->employee_id,
            'request_type' => 'OVERTIME',
            'status' => 'PENDING_APPROVAL',
            'start_date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_hours' => $validated['total_hours'],
            'reason' => $validated['reason'],
            'current_approver_id' => $employee->supervisor?->user_id,
        ]);

        EssRequestLog::create([
            'ess_request_id' => $essReq->id,
            'action' => 'SUBMITTED',
            'acted_by' => $user->id,
            'remarks' => "Filed overtime of {$validated['total_hours']} hrs on {$validated['date']}",
        ]);

        return redirect()->back()->with('success', "Overtime request #{$reqNumber} submitted.");
    }

    public function storeOfficialBusiness(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'destination' => 'required|string|max:200',
            'purpose' => 'required|string|max:500',
        ]);

        $reqNumber = EssRequest::generateRequestNumber('OB');

        $essReq = EssRequest::create([
            'request_number' => $reqNumber,
            'employee_id' => $employee->employee_id,
            'request_type' => 'OFFICIAL_BUSINESS',
            'status' => 'PENDING_APPROVAL',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
            'reason' => $validated['purpose'],
            'metadata' => [
                'destination' => $validated['destination'],
            ],
            'current_approver_id' => $employee->supervisor?->user_id,
        ]);

        EssRequestLog::create([
            'ess_request_id' => $essReq->id,
            'action' => 'SUBMITTED',
            'acted_by' => $user->id,
            'remarks' => "Filed official business to {$validated['destination']}",
        ]);

        return redirect()->back()->with('success', "Official business request #{$reqNumber} submitted.");
    }

    public function cancel(int $id): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        $essReq = EssRequest::findOrFail($id);

        // IDOR Protection: Employee can strictly only cancel their own request
        if (!$employee || $essReq->employee_id !== $employee->employee_id) {
            abort(403, 'Unauthorized to cancel this request.');
        }

        if (!in_array($essReq->status, ['PENDING_APPROVAL', 'DRAFT', 'SUBMITTED'])) {
            return redirect()->back()->with('error', 'Only pending requests can be cancelled.');
        }

        $essReq->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
        ]);

        EssRequestLog::create([
            'ess_request_id' => $essReq->id,
            'action' => 'CANCELLED',
            'acted_by' => $user->id,
            'remarks' => 'Cancelled by employee',
        ]);

        return redirect()->back()->with('success', "Request #{$essReq->request_number} cancelled successfully.");
    }
}
