<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Models\Ess\EssRequest;
use App\Models\Hr\Employee;
use Illuminate\Support\Facades\DB;

class EssRequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->attributes->get('current_employee');
        $statusFilter = $request->input('status', 'ALL');
        $typeFilter = $request->input('type', 'ALL');

        $query = EssRequest::where('employee_id', $employee->employee_id)
            ->with(['approver'])
            ->orderBy('created_at', 'desc');

        if ($statusFilter !== 'ALL') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter !== 'ALL') {
            $query->where('request_type', $typeFilter);
        }

        $requests = $query->paginate(15);

        // Counts for tabs
        $counts = [
            'ALL' => EssRequest::where('employee_id', $employee->employee_id)->count(),
            'PENDING' => EssRequest::where('employee_id', $employee->employee_id)->where('status', 'PENDING')->count(),
            'APPROVED' => EssRequest::where('employee_id', $employee->employee_id)->where('status', 'APPROVED')->count(),
            'REJECTED' => EssRequest::where('employee_id', $employee->employee_id)->where('status', 'REJECTED')->count(),
        ];

        return view('ess.requests.index', compact('employee', 'requests', 'statusFilter', 'typeFilter', 'counts'));
    }

    public function storeOvertime(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');

        $validated = $request->validate([
            'ot_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'hours' => 'required|numeric|min:0.5|max:12',
            'reason' => 'required|string|max:1000',
        ]);

        EssRequest::create([
            'request_number' => EssRequest::generateRequestNumber('OT'),
            'employee_id' => $employee->employee_id,
            'request_type' => 'OVERTIME',
            'details' => [
                'ot_date' => $validated['ot_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'hours' => (float) $validated['hours'],
            ],
            'reason' => $validated['reason'],
            'status' => 'PENDING',
            'approval_step' => 1,
        ]);

        return redirect()->back()->with('success', 'Overtime authorization request submitted successfully.');
    }

    public function storeOfficialBusiness(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');

        $validated = $request->validate([
            'ob_date' => 'required|date',
            'destination' => 'required|string|max:255',
            'purpose' => 'required|string|max:1000',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        EssRequest::create([
            'request_number' => EssRequest::generateRequestNumber('OB'),
            'employee_id' => $employee->employee_id,
            'request_type' => 'OFFICIAL_BUSINESS',
            'details' => [
                'ob_date' => $validated['ob_date'],
                'destination' => $validated['destination'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ],
            'reason' => $validated['purpose'],
            'status' => 'PENDING',
            'approval_step' => 1,
        ]);

        return redirect()->back()->with('success', 'Official Business (OB) pass submitted successfully.');
    }

    public function storeUndertime(Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');

        $validated = $request->validate([
            'ut_date' => 'required|date',
            'departure_time' => 'required',
            'reason' => 'required|string|max:1000',
        ]);

        EssRequest::create([
            'request_number' => EssRequest::generateRequestNumber('UT'),
            'employee_id' => $employee->employee_id,
            'request_type' => 'UNDERTIME',
            'details' => [
                'ut_date' => $validated['ut_date'],
                'departure_time' => $validated['departure_time'],
            ],
            'reason' => $validated['reason'],
            'status' => 'PENDING',
            'approval_step' => 1,
        ]);

        return redirect()->back()->with('success', 'Early departure / undertime notice submitted.');
    }

    public function cancel(int $id, Request $request): RedirectResponse
    {
        $employee = $request->attributes->get('current_employee');

        $req = EssRequest::where('id', $id)
            ->where('employee_id', $employee->employee_id)
            ->firstOrFail();

        if ($req->status !== 'PENDING') {
            return redirect()->back()->with('error', 'Only pending requests can be cancelled.');
        }

        $req->update(['status' => 'CANCELLED']);

        return redirect()->back()->with('success', 'Request # ' . $req->request_number . ' cancelled.');
    }

    public function timeline(int $id, Request $request): JsonResponse
    {
        $employee = $request->attributes->get('current_employee');

        $req = EssRequest::where('id', $id)
            ->where('employee_id', $employee->employee_id)
            ->firstOrFail();

        $steps = [
            [
                'step' => 1,
                'role' => 'Immediate Supervisor',
                'status' => $req->approval_step > 1 || $req->status === 'APPROVED' ? 'Approved' : ($req->status === 'REJECTED' ? 'Rejected' : 'In Review'),
                'timestamp' => $req->approved_at ? $req->approved_at->format('M d, Y h:i A') : null,
                'remarks' => $req->status === 'REJECTED' ? $req->rejection_reason : 'Pending managerial review',
            ],
            [
                'step' => 2,
                'role' => 'Department Head / Branch Manager',
                'status' => $req->status === 'APPROVED' ? 'Approved' : 'Pending',
                'timestamp' => null,
                'remarks' => null,
            ],
            [
                'step' => 3,
                'role' => 'HR Operations',
                'status' => $req->status === 'APPROVED' ? 'Finalized' : 'Queued',
                'timestamp' => null,
                'remarks' => null,
            ]
        ];

        return response()->json([
            'request_number' => $req->request_number,
            'type' => $req->request_type,
            'status' => $req->status,
            'steps' => $steps,
        ]);
    }
}
