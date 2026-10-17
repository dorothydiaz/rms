@extends('layouts.ess')

@section('title', 'My Profile & 201 Digital File')

@section('content')
<div class="ess-profile-container">
    <div class="ess-page-header">
        <div>
            <h1 class="ess-page-title">Employee Profile & 201 Digital File</h1>
            <p class="ess-page-subtitle">Personal data, government statutory IDs, digital 201 records, and official Certificate of Employment (COE) requests.</p>
        </div>
        <div class="ess-page-actions">
            <button type="button" class="ess-btn ess-btn-primary" onclick="openCoeModal()">
                <i class="bi bi-file-earmark-text"></i> Request Certificate of Employment
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Left: Employee Identity Card -->
        <div>
            <div class="ess-card" style="text-align: center; padding: 2rem 1.5rem;">
                <div style="width: 96px; height: 96px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.25rem; font-weight: 700; margin: 0 auto 1rem auto; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem 0;">
                    {{ $employee->first_name }} {{ $employee->last_name }}
                </h2>
                <div style="font-size: 0.875rem; color: #2563eb; font-weight: 600; margin-bottom: 0.5rem;">
                    {{ $employee->position->position_title ?? 'Employee' }}
                </div>
                <div>
                    <span class="ess-badge ess-badge-success">{{ $employee->employment_status ?? 'Active Regular' }}</span>
                </div>

                <div style="margin-top: 1.5rem; text-align: left; font-size: 0.85rem; border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                    <div style="margin-bottom: 0.65rem;">
                        <span style="color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase;">Employee Code</span>
                        <strong style="color: #1e293b;">{{ $employee->employee_code }}</strong>
                    </div>
                    <div style="margin-bottom: 0.65rem;">
                        <span style="color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase;">Company</span>
                        <strong style="color: #1e293b;">{{ $employee->company->company_name ?? 'Stellar One' }}</strong>
                    </div>
                    <div style="margin-bottom: 0.65rem;">
                        <span style="color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase;">Department</span>
                        <strong style="color: #1e293b;">{{ $employee->department->department_name ?? 'Operations' }}</strong>
                    </div>
                    <div style="margin-bottom: 0.65rem;">
                        <span style="color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase;">Date Hired</span>
                        <strong style="color: #1e293b;">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') : 'N/A' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Statutory ID Card -->
            <div class="ess-card" style="margin-top: 1.5rem;">
                <div class="ess-card-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
                    <h3 class="ess-card-title" style="font-size: 0.95rem;">
                        <i class="bi bi-shield-lock text-primary me-1"></i> Philippine Statutory IDs
                    </h3>
                </div>
                <div style="padding-top: 1rem; font-size: 0.85rem;">
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">SSS Number:</span>
                        <strong style="color: #1e293b; font-family: monospace;">{{ $employee->sss_number ?? '00-0000000-0' }}</strong>
                    </div>
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">PhilHealth (PIN):</span>
                        <strong style="color: #1e293b; font-family: monospace;">{{ $employee->philhealth_number ?? '00-000000000-0' }}</strong>
                    </div>
                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Pag-IBIG (MID):</span>
                        <strong style="color: #1e293b; font-family: monospace;">{{ $employee->pagibig_number ?? '0000-0000-0000' }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">BIR TIN:</span>
                        <strong style="color: #1e293b; font-family: monospace;">{{ $employee->tin ?? '000-000-000-000' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Contact Update, 201 Files, COE History -->
        <div>
            <!-- Contact & Emergency Form -->
            <div class="ess-card" style="margin-bottom: 1.5rem;">
                <div class="ess-card-header">
                    <h2 class="ess-card-title"><i class="bi bi-telephone"></i> Contact & Emergency Information</h2>
                </div>
                <form action="{{ route('ess.profile.update-contact') }}" method="POST">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="ess-form-group">
                            <label class="ess-form-label">Mobile Number</label>
                            <input type="text" name="phone_number" class="ess-form-control" value="{{ old('phone_number', $employee->phone_number) }}" placeholder="+63 9XX XXX XXXX">
                        </div>
                        <div class="ess-form-group">
                            <label class="ess-form-label">Personal Email</label>
                            <input type="email" name="personal_email" class="ess-form-control" value="{{ old('personal_email', $employee->personal_email) }}" placeholder="personal@example.com">
                        </div>
                    </div>

                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Current Residential Address</label>
                        <input type="text" name="current_address" class="ess-form-control" value="{{ old('current_address', $employee->current_address) }}" placeholder="House/Unit, Street, Barangay, City, Metro Manila">
                    </div>

                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 1.25rem 0 0.75rem 0; border-top: 1px dashed #e2e8f0; padding-top: 1rem;">
                        Emergency Contact Person
                    </h3>
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="ess-form-group">
                            <label class="ess-form-label">Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="ess-form-control" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" placeholder="Full Name">
                        </div>
                        <div class="ess-form-group">
                            <label class="ess-form-label">Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" class="ess-form-control" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" placeholder="+63 9XX XXX XXXX">
                        </div>
                        <div class="ess-form-group">
                            <label class="ess-form-label">Relationship</label>
                            <input type="text" name="emergency_contact_relationship" class="ess-form-control" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}" placeholder="Spouse, Parent, Sibling">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="ess-btn ess-btn-primary">Save Contact Changes</button>
                    </div>
                </form>
            </div>

            <!-- 201 Digital File Documents -->
            <div class="ess-card" style="margin-bottom: 1.5rem;">
                <div class="ess-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="ess-card-title"><i class="bi bi-folder2-open"></i> 201 Digital File Repository</h2>
                    <button type="button" class="ess-btn ess-btn-secondary" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;" onclick="openDocModal()">
                        <i class="bi bi-cloud-arrow-up"></i> Upload Document
                    </button>
                </div>

                <div class="ess-table-container">
                    <table class="ess-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Issue / Upload Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $doc)
                            <tr>
                                <td>
                                    <strong style="color: #1e293b;">{{ $doc->employee_document_document_name }}</strong>
                                </td>
                                <td><span class="ess-badge ess-badge-neutral">{{ $doc->employee_document_document_type }}</span></td>
                                <td>{{ $doc->employee_document_issue_date ?? $doc->created_at?->format('M d, Y') }}</td>
                                <td>
                                    <span class="ess-badge {{ $doc->employee_document_status === 'Verified' ? 'ess-badge-success' : 'ess-badge-warning' }}">
                                        {{ $doc->employee_document_status ?? 'Pending Verification' }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    @if($doc->employee_document_file_path)
                                    <a href="{{ asset('storage/' . $doc->employee_document_file_path) }}" target="_blank" class="ess-btn ess-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                    No documents uploaded to your digital 201 folder yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- COE Requests History -->
            <div class="ess-card">
                <div class="ess-card-header">
                    <h2 class="ess-card-title"><i class="bi bi-award"></i> Certificate of Employment (COE) Requests</h2>
                </div>
                <div class="ess-table-container">
                    <table class="ess-table">
                        <thead>
                            <tr>
                                <th>Request Number</th>
                                <th>Purpose / Addressed To</th>
                                <th>Compensation Included</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coeRequests as $coe)
                            <tr>
                                <td><strong style="font-family: monospace; color: #1e293b;">{{ $coe->request_number }}</strong></td>
                                <td>
                                    <div>{{ $coe->details['purpose'] ?? $coe->reason }}</div>
                                    <small style="color: #64748b;">To: {{ $coe->details['addressed_to'] ?? 'To Whom It May Concern' }}</small>
                                </td>
                                <td>
                                    <span class="ess-badge {{ !empty($coe->details['include_compensation']) ? 'ess-badge-success' : 'ess-badge-neutral' }}">
                                        {{ !empty($coe->details['include_compensation']) ? 'Yes (With Salary)' : 'No (Tenure Only)' }}
                                    </span>
                                </td>
                                <td>{{ $coe->created_at?->format('M d, Y') }}</td>
                                <td>
                                    <span class="ess-badge {{ $coe->status === 'APPROVED' ? 'ess-badge-success' : ($coe->status === 'REJECTED' ? 'ess-badge-danger' : 'ess-badge-warning') }}">
                                        {{ $coe->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                    No Certificate of Employment requests filed yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request COE Modal -->
<div class="modal fade" id="coeModal" tabindex="-1" style="display: none; background: rgba(15,23,42,0.6); position: fixed; inset: 0; z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px; width: 100%; margin: 1.5rem auto;">
        <div class="modal-content" style="background: white; border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <form action="{{ route('ess.profile.coe-request') }}" method="POST">
                @csrf
                <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                        <i class="bi bi-file-earmark-text text-primary me-1"></i> Request Certificate of Employment (COE)
                    </h5>
                    <button type="button" class="btn-close" onclick="closeCoeModal()"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Purpose of Request <span class="text-danger">*</span></label>
                        <select name="purpose" class="ess-form-control" required>
                            <option value="">Select purpose...</option>
                            <option value="Bank Loan / Mortgage Application">Bank Loan / Mortgage Application</option>
                            <option value="Credit Card Application">Credit Card Application</option>
                            <option value="Embassy Visa Application">Embassy Visa Application</option>
                            <option value="Pag-IBIG / SSS Housing Loan">Pag-IBIG / SSS Housing Loan</option>
                            <option value="General Employment Verification">General Employment Verification</option>
                            <option value="Personal Reference">Personal Reference</option>
                        </select>
                    </div>

                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Addressed To (Optional)</label>
                        <input type="text" name="addressed_to" class="ess-form-control" placeholder="e.g., The Visa Officer, Embassy of Japan / BDO Unibank">
                        <small style="color: #64748b; font-size: 0.75rem;">Leave blank for default: "To Whom It May Concern"</small>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="include_compensation" value="1" style="margin-top: 0.25rem;">
                            <div>
                                <strong style="font-size: 0.875rem; color: #1e293b;">Include Detailed Compensation Breakdown</strong>
                                <p style="font-size: 0.75rem; color: #64748b; margin: 0.15rem 0 0 0;">
                                    Pursuant to the Data Privacy Act of 2012, your monthly basic salary and allowances will only be reflected upon your explicit consent.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="ess-btn ess-btn-secondary" onclick="closeCoeModal()">Cancel</button>
                    <button type="submit" class="ess-btn ess-btn-primary">Submit COE Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="docModal" tabindex="-1" style="display: none; background: rgba(15,23,42,0.6); position: fixed; inset: 0; z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px; width: 100%; margin: 1.5rem auto;">
        <div class="modal-content" style="background: white; border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <form action="{{ route('ess.profile.upload-document') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                        <i class="bi bi-cloud-arrow-up text-primary me-1"></i> Upload 201 File Document
                    </h5>
                    <button type="button" class="btn-close" onclick="closeDocModal()"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="ess-form-control" required>
                            <option value="">Select type...</option>
                            <option value="Valid Government ID">Valid Government ID (Passport/UMID/Driver's License)</option>
                            <option value="Medical Certificate / Fit to Work">Medical Certificate / Fit to Work</option>
                            <option value="NBI / Police Clearance">NBI / Police Clearance</option>
                            <option value="Proof of Address / Billing">Proof of Address / Billing</option>
                            <option value="Diploma / TOR / Certificate">Diploma / TOR / Professional Certificate</option>
                            <option value="Other Document">Other Document</option>
                        </select>
                    </div>

                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Document Description / Label <span class="text-danger">*</span></label>
                        <input type="text" name="document_name" class="ess-form-control" placeholder="e.g. Passport Bio Page 2026" required>
                    </div>

                    <div class="ess-form-group">
                        <label class="ess-form-label">Select File (PDF, PNG, JPG, max 5MB) <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="ess-form-control" required>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="ess-btn ess-btn-secondary" onclick="closeDocModal()">Cancel</button>
                    <button type="submit" class="ess-btn ess-btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCoeModal() {
    document.getElementById('coeModal').style.display = 'flex';
}
function closeCoeModal() {
    document.getElementById('coeModal').style.display = 'none';
}
function openDocModal() {
    document.getElementById('docModal').style.display = 'flex';
}
function closeDocModal() {
    document.getElementById('docModal').style.display = 'none';
}
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>
@endsection
