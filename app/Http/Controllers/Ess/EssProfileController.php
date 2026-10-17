<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmergencyContact;
use App\Models\Hr\EssRequest;
use App\Models\Hr\EssRequestLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EssProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return view('ess.no-employee-profile', compact('user'));
        }

        $employee->load([
            'department',
            'branch',
            'company',
            'position',
            'supervisor',
            'emergencyContacts',
            'documents',
            'employmentHistories',
        ]);

        return view('ess.profile', compact('user', 'employee'));
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'mobile_number' => 'nullable|string|max:30',
            'telephone_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'permanent_address' => 'nullable|string|max:255',
        ]);

        $employee->update($validated);

        return redirect()->back()->with('success', 'Contact information updated successfully.');
    }

    public function storeEmergencyContact(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'relationship' => 'required|string|max:50',
            'phone' => 'required|string|max:30',
            'address' => 'nullable|string|max:255',
        ]);

        $validated['employee_id'] = $employee->employee_id;
        EmergencyContact::create($validated);

        return redirect()->back()->with('success', 'Emergency contact added successfully.');
    }

    public function destroyEmergencyContact(int $id): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $contact = EmergencyContact::where('id', $id)
            ->where('employee_id', $employee->employee_id)
            ->firstOrFail();

        $contact->delete();

        return redirect()->back()->with('success', 'Emergency contact removed.');
    }

    public function requestCoe(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'No employee record linked.');
        }

        $validated = $request->validate([
            'purpose' => 'required|string|max:255',
            'with_compensation' => 'nullable|boolean',
        ]);

        $reqNumber = EssRequest::generateRequestNumber('COE');

        $essReq = EssRequest::create([
            'request_number' => $reqNumber,
            'employee_id' => $employee->employee_id,
            'request_type' => 'COE_REQUEST',
            'status' => 'PENDING_APPROVAL',
            'start_date' => now()->toDateString(),
            'reason' => $validated['purpose'],
            'metadata' => [
                'with_compensation' => $request->boolean('with_compensation'),
            ],
            'current_approver_id' => $employee->supervisor?->user_id,
        ]);

        EssRequestLog::create([
            'ess_request_id' => $essReq->id,
            'action' => 'SUBMITTED',
            'acted_by' => $user->id,
            'remarks' => "Requested Certificate of Employment (Purpose: {$validated['purpose']})",
        ]);

        return redirect()->back()->with('success', "Certificate of Employment request #{$reqNumber} submitted.");
    }
}
