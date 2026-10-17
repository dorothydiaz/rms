<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Ess\EssRequest;
use App\Models\Hr\EmployeeDocument;

class EssProfileController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee->load(['company', 'department', 'position']);
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $documents = EmployeeDocument::where('employee_document_employee_id', $empId)
            ->orderByDesc('employee_document_id')
            ->get();

        $coeRequests = EssRequest::where('employee_id', $empId)
            ->where('request_type', 'COE_REQUEST')
            ->orderByDesc('id')
            ->get();

        return view('ess.profile.index', compact('employee', 'documents', 'coeRequests'));
    }

    public function updateContact(Request $request)
    {
        $employee = $request->user()->employee;

        $validated = $request->validate([
            'phone_number' => 'nullable|string|max:20',
            'personal_email' => 'nullable|email|max:100',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'current_address' => 'nullable|string|max:255',
        ]);

        $employee->update($validated);

        return redirect()->route('ess.profile')->with('success', 'Your contact and emergency details have been updated successfully.');
    }

    public function coeRequest(Request $request)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $request->validate([
            'purpose' => 'required|string|max:255',
            'addressed_to' => 'nullable|string|max:255',
            'include_compensation' => 'nullable|in:0,1',
        ]);

        $essRequest = EssRequest::create([
            'request_number' => 'COE-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'employee_id' => $empId,
            'request_type' => 'COE_REQUEST',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => $request->purpose,
            'details' => [
                'purpose' => $request->purpose,
                'addressed_to' => $request->addressed_to ?? 'To Whom It May Concern',
                'include_compensation' => (bool)$request->include_compensation,
            ],
            'status' => 'PENDING',
        ]);

        return redirect()->route('ess.profile')->with('success', 'Certificate of Employment (COE) request #' . $essRequest->request_number . ' submitted successfully.');
    }

    public function uploadDocument(Request $request)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $request->validate([
            'document_type' => 'required|string|max:100',
            'document_name' => 'required|string|max:255',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('document_file');
        $fileName = 'doc_' . $empId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/ess_documents', $fileName, 'public');

        EmployeeDocument::create([
            'employee_document_employee_id' => $empId,
            'employee_document_document_type' => $request->document_type,
            'employee_document_document_name' => $request->document_name,
            'employee_document_file_path' => $path,
            'employee_document_category' => '201_FILE',
            'employee_document_issue_date' => now()->toDateString(),
            'employee_document_status' => 'Pending Verification',
            'employee_document_uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('ess.profile')->with('success', 'Document uploaded successfully to your 201 Digital File for HR verification.');
    }
}
