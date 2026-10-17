@extends('layouts.ess')

@section('title', 'My Profile & 201 Digital File')

@section('content')
<div class="ess-profile-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-identification-badge"></i>
                <span>Employee Profile & 201 Digital File</span>
            </h1>
            <p class="hr-page-subtitle">Personal data, government statutory IDs, digital 201 records, and official Certificate of Employment (COE) requests.</p>
        </div>
        <div class="hr-page-actions">
            <button type="button" class="hr-btn hr-btn-primary" onclick="openCoeModal()">
                <i class="ph ph-file-text"></i>
                <span>Request Certificate of Employment</span>
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 340px 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Left: Employee Identity Card -->
        <div>
            <div class="ess-card" style="text-align: center; padding: 24px 20px;">
                <div class="ess-card-accent-strip"></div>
                <div style="width: 88px; height: 88px; border-radius: 22px; background: var(--ess-primary-gradient); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; margin: 0 auto 16px auto; box-shadow: 0 8px 24px rgba(168, 85, 247, 0.32);">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <h2 style="font-family:'Outfit',sans-serif;font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
                    {{ $employee->first_name }} {{ $employee->last_name }}
                </h2>
                <div style="font-size: 13px; color: #9333ea; font-weight: 700; margin-bottom: 8px;">
                    {{ $employee->position->position_title ?? 'Employee' }}
                </div>
                <div>
                    <span class="ess-badge ess-badge-success">{{ $employee->employment_status ?? 'Active Regular' }}</span>
                </div>

                <div style="margin-top: 20px; text-align: left; font-size: 12.5px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                    <div style="margin-bottom: 10px;">
                        <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase; font-weight: 700;">Employee Code</span>
                        <strong style="color: #9333ea; font-family: monospace; font-size: 13px;">{{ $employee->employee_code }}</strong>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase; font-weight: 700;">Company</span>
                        <strong style="color: #0f172a;">{{ $employee->company->company_name ?? 'Stellar One' }}</strong>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase; font-weight: 700;">Department</span>
                        <strong style="color: #0f172a;">{{ $employee->department->department_name ?? 'Operations' }}</strong>
                    </div>
                    <div style="margin-bottom: 4px;">
                        <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase; font-weight: 700;">Date Hired</span>
                        <strong style="color: #0f172a;">{{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') : 'N/A' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Statutory ID Card -->
            <div class="ess-card" style="margin-top: 20px;">
                <div class="ess-card-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 class="ess-card-title" style="font-size: 14px;">
                        <i class="ph ph-shield-check" style="color: #9333ea;"></i> Philippine Statutory IDs
                    </h3>
                </div>
                <div style="padding-top: 12px; font-size: 12.5px;">
                    <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">SSS Number:</span>
                        <strong style="color: #0f172a; font-family: monospace;">{{ $employee->sss_number ?? '00-0000000-0' }}</strong>
                    </div>
                    <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">PhilHealth (PIN):</span>
                        <strong style="color: #0f172a; font-family: monospace;">{{ $employee->philhealth_number ?? '00-000000000-0' }}</strong>
                    </div>
                    <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Pag-IBIG (MID):</span>
                        <strong style="color: #0f172a; font-family: monospace;">{{ $employee->pagibig_number ?? '0000-0000-0000' }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">BIR TIN:</span>
                        <strong style="color: #0f172a; font-family: monospace;">{{ $employee->tin ?? '000-000-000-000' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Contact Update, 201 Files, COE History -->
        <div>
            <!-- Contact & Emergency Form -->
            <div class="ess-card" style="margin-bottom: 20px;">
                <div class="ess-card-header">
                    <h2 class="ess-card-title">
                        <i class="ph ph-phone" style="color: #9333ea;"></i> Contact & Emergency Information
                    </h2>
                </div>
                <form action="{{ route('ess.profile.update-contact') }}" method="POST">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Mobile Number</label>
                            <input type="text" name="phone_number" class="ess-form-control" style="width:100%;" value="{{ old('phone_number', $employee->phone_number) }}" placeholder="+63 9XX XXX XXXX">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Personal Email</label>
                            <input type="email" name="personal_email" class="ess-form-control" style="width:100%;" value="{{ old('personal_email', $employee->personal_email) }}" placeholder="personal@example.com">
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Current Residential Address</label>
                        <input type="text" name="current_address" class="ess-form-control" style="width:100%;" value="{{ old('current_address', $employee->current_address) }}" placeholder="House/Unit, Street, Barangay, City, Metro Manila">
                    </div>

                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin: 18px 0 10px 0; border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                        Emergency Contact Person
                    </div>
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="ess-form-control" style="width:100%;" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" placeholder="Full Name">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" class="ess-form-control" style="width:100%;" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" placeholder="+63 9XX XXX XXXX">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;">Relationship</label>
                            <input type="text" name="emergency_contact_relationship" class="ess-form-control" style="width:100%;" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}" placeholder="Spouse, Parent, Sibling">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="hr-btn hr-btn-primary">Save Contact Changes</button>
                    </div>
                </form>
            </div>

            <!-- 201 Digital File Documents -->
            <div class="ess-card" style="margin-bottom: 20px;">
                <div class="ess-card-header">
                    <h2 class="ess-card-title">
                        <i class="ph ph-folder-open" style="color: #9333ea;"></i> 201 Digital File Repository
                    </h2>
                    <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="openDocModal()">
                        <i class="ph ph-upload-simple"></i> Upload Document
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
                                    <strong style="color: #0f172a;">{{ $doc->employee_document_document_name }}</strong>
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
                                    <a href="{{ asset('storage/' . $doc->employee_document_file_path) }}" target="_blank" class="hr-btn hr-btn-secondary" style="padding: 4px 8px; font-size: 11.5px;">
                                        <i class="ph ph-eye"></i> View
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 32px 16px; color: #94a3b8;">
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
                    <h2 class="ess-card-title">
                        <i class="ph ph-certificate" style="color: #9333ea;"></i> Certificate of Employment (COE) Requests
                    </h2>
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
                                <td><strong style="font-family: monospace; color: #9333ea;">{{ $coe->request_number }}</strong></td>
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
                                <td colspan="5" style="text-align: center; padding: 32px 16px; color: #94a3b8;">
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
<div id="coeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 540px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-file-text"></i> Request Certificate of Employment</span>
            <button type="button" class="icon-btn" onclick="closeModal('coeModal')" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <form action="{{ route('ess.profile.coe-request') }}" method="POST">
            @csrf
            <div class="hr-modal-body">
                <div class="ess-form-group">
                    <label class="ess-form-label">Purpose of Request <span style="color:#dc2626;">*</span></label>
                    <select name="purpose" class="ess-form-control" style="width:100%;" required>
                        <option value="">Select purpose...</option>
                        <option value="Bank Loan / Mortgage Application">Bank Loan / Mortgage Application</option>
                        <option value="Credit Card Application">Credit Card Application</option>
                        <option value="Embassy Visa Application">Embassy Visa Application</option>
                        <option value="Pag-IBIG / SSS Housing Loan">Pag-IBIG / SSS Housing Loan</option>
                        <option value="General Employment Verification">General Employment Verification</option>
                        <option value="Personal Reference">Personal Reference</option>
                    </select>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Addressed To (Optional)</label>
                    <input type="text" name="addressed_to" class="ess-form-control" style="width:100%;" placeholder="e.g., The Visa Officer, Embassy of Japan / BDO Unibank">
                    <small style="color: #94a3b8; font-size: 11px; margin-top: 4px; display: block;">Leave blank for default: "To Whom It May Concern"</small>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--ess-border); border-radius: 10px; padding: 14px; margin-top: 6px;">
                    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="include_compensation" value="1" style="margin-top: 3px;">
                        <div>
                            <strong style="font-size: 13px; color: #0f172a;">Include Detailed Compensation Breakdown</strong>
                            <p style="font-size: 11.5px; color: #64748b; margin: 3px 0 0 0;">
                                Pursuant to the Data Privacy Act of 2012, your monthly basic salary and allowances will only be reflected upon your explicit consent.
                            </p>
                        </div>
                    </label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('coeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit COE Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Document Modal -->
<div id="docModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 540px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-upload-simple"></i> Upload 201 File Document</span>
            <button type="button" class="icon-btn" onclick="closeModal('docModal')" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <form action="{{ route('ess.profile.upload-document') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body">
                <div class="ess-form-group">
                    <label class="ess-form-label">Document Type <span style="color:#dc2626;">*</span></label>
                    <select name="document_type" class="ess-form-control" style="width:100%;" required>
                        <option value="">Select type...</option>
                        <option value="Valid Government ID">Valid Government ID (Passport/UMID/Driver's License)</option>
                        <option value="Medical Certificate / Fit to Work">Medical Certificate / Fit to Work</option>
                        <option value="NBI / Police Clearance">NBI / Police Clearance</option>
                        <option value="Proof of Address / Billing">Proof of Address / Billing</option>
                        <option value="Diploma / TOR / Certificate">Diploma / TOR / Professional Certificate</option>
                        <option value="Other Document">Other Document</option>
                    </select>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Document Description / Label <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="document_name" class="ess-form-control" style="width:100%;" placeholder="e.g. Passport Bio Page 2026" required>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Select File (PDF, PNG, JPG, max 5MB) <span style="color:#dc2626;">*</span></label>
                    <input type="file" name="document_file" class="ess-form-control" style="width:100%;" required>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('docModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCoeModal() {
    openModal('coeModal');
}
function closeCoeModal() {
    closeModal('coeModal');
}
function openDocModal() {
    openModal('docModal');
}
function closeDocModal() {
    closeModal('docModal');
}
</script>
@endsection
