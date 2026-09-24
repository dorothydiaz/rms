@extends('layouts.app')

@section('title', $employee->full_name . ' - Employee Profile')

@section('content')
<div class="hr-page-header">
    <div style="display: flex; align-items: center; gap: 20px;">
        @if($employee->photo_url)
            <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" class="hr-avatar-img-lg">
        @else
            <div class="hr-avatar-circle-lg">
                {{ $employee->initials }}
            </div>
        @endif
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <a href="{{ route('hr.people.employees') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                    <i class="ph ph-arrow-left"></i> Back to Directory
                </a>
                <span class="hr-badge hr-badge-neutral">{{ $employee->branch?->name }}</span>
                @if($employee->employment_status === 'Active')
                    <span class="hr-badge hr-badge-success">{{ $employee->employment_status }}</span>
                @elseif($employee->employment_status === 'Probationary')
                    <span class="hr-badge hr-badge-warning">{{ $employee->employment_status }}</span>
                @else
                    <span class="hr-badge hr-badge-danger">{{ $employee->employment_status }}</span>
                @endif
            </div>
            <h1 class="hr-page-title" style="margin-top: 2px;">
                {{ $employee->full_name }}
            </h1>
            <p class="hr-page-subtitle">{{ $employee->position?->name }} &bull; {{ $employee->department?->name }} &bull; ID: <strong>{{ $employee->employee_id }}</strong></p>
        </div>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('editEmployeeModal')">
            <i class="ph ph-pencil-simple"></i>
            <span>Edit Profile</span>
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Left Column: Details -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Card 1: Personal & Employment Information -->
        <div class="hr-table-card" style="margin-bottom: 0;">
            <div class="hr-table-header">
                <span class="hr-table-title"><i class="ph ph-identification-card" style="color: #9333ea;"></i> Personal & Employment Details</span>
            </div>
            <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Full Name</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ $employee->full_name }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Date of Birth</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('F d, Y') : 'Not set' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Gender / Civil Status</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ $employee->gender ?? 'N/A' }} / {{ $employee->civil_status }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Nationality</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ $employee->nationality }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Number</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ $employee->mobile_number ?? 'None' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Email Address</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ $employee->email ?? 'None' }}</div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Residential Address</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ $employee->address ?? 'None provided' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Date Hired</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">{{ \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Regularization Date</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $employee->date_of_regularization ? \Carbon\Carbon::parse($employee->date_of_regularization)->format('M d, Y') : 'Pending evaluation' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Sensitive Government & Compensation Information (RBAC Protected) -->
        <div class="hr-table-card" style="margin-bottom: 0;">
            <div class="hr-table-header">
                <span class="hr-table-title"><i class="ph ph-shield-check" style="color: #0284c7;"></i> Government & Compensation Data</span>
                @if(!$canViewSensitive)
                    <span class="hr-badge hr-badge-danger"><i class="ph ph-lock-key"></i> Restricted Access</span>
                @else
                    <span class="hr-badge hr-badge-success"><i class="ph ph-lock-key-open"></i> Authorized View</span>
                @endif
            </div>
            <div style="padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Basic Salary</div>
                    <div style="font-size: 15px; font-weight: 700; color: #059669; margin-top: 3px;">
                        {{ $canViewSensitive ? '₱ ' . number_format($employee->basic_salary, 2) : '••••••••' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Salary Type & Pay Frequency</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $employee->salary_type }} &bull; {{ $employee->pay_frequency }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Allowances</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $canViewSensitive ? '₱ ' . number_format($employee->allowances, 2) : '••••••••' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">SSS Number</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $canViewSensitive ? ($employee->sss_number ?? 'None') : '••••••••' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">PhilHealth Number</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $canViewSensitive ? ($employee->philhealth_number ?? 'None') : '••••••••' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pag-IBIG Number</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $canViewSensitive ? ($employee->pagibig_number ?? 'None') : '••••••••' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Tax Identification Number (TIN)</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        {{ $canViewSensitive ? ($employee->tin ?? 'None') : '••••••••' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Leave Balances for Current Year -->
        <div class="hr-table-card" style="margin-bottom: 0;">
            <div class="hr-table-header">
                <span class="hr-table-title"><i class="ph ph-airplane-in-flight" style="color: #ec4899;"></i> Leave Balances (Year {{ date('Y') }})</span>
            </div>
            <div class="hr-table-wrapper">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Allocated</th>
                            <th>Used</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->leaveBalances as $lb)
                            <tr>
                                <td><strong>{{ $lb->leaveType?->name }}</strong></td>
                                <td>{{ number_format($lb->beginning_balance + $lb->earned, 1) }}</td>
                                <td style="color: #ef4444;">{{ number_format($lb->used, 1) }}</td>
                                <td style="font-weight: 700; color: #059669;">{{ number_format($lb->remaining, 1) }} days</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">No leave credits initialized for this year.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Emergency Contact & Documents -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Emergency Contacts -->
        <div class="hr-table-card" style="margin-bottom: 0;">
            <div class="hr-table-header">
                <span class="hr-table-title"><i class="ph ph-phone-call" style="color: #e11d48;"></i> Emergency Contact</span>
            </div>
            <div style="padding: 18px;">
                @forelse($employee->emergencyContacts as $contact)
                    <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                        <div style="font-weight: 700; color: #0f172a;">{{ $contact->contact_name }}</div>
                        <div style="font-size: 12px; color: #9333ea; font-weight: 600;">{{ $contact->relationship }}</div>
                        <div style="font-size: 13px; color: #334155; margin-top: 4px;"><i class="ph ph-phone"></i> {{ $contact->contact_number }}</div>
                    </div>
                @empty
                    <div style="color: #94a3b8; font-size: 13px;">No emergency contacts listed.</div>
                @endforelse
            </div>
        </div>

        <!-- Quick Summary Card -->
        <div class="hr-table-card" style="margin-bottom: 0;">
            <div class="hr-table-header">
                <span class="hr-table-title"><i class="ph ph-info" style="color: #6366f1;"></i> Assignment</span>
            </div>
            <div style="padding: 18px; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <span style="color: #64748b;">Branch:</span>
                    <strong>{{ $employee->branch?->name }}</strong>
                </div>
                <div>
                    <span style="color: #64748b;">Supervisor:</span>
                    <strong>{{ $employee->supervisor?->full_name ?? 'None' }}</strong>
                </div>
                <div>
                    <span style="color: #64748b;">Contract Period:</span>
                    <strong>{{ $employee->contract_start_date ?? 'N/A' }} ~ {{ $employee->contract_end_date ?? 'Permanent' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="editEmployeeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 780px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-pencil"></i> Edit Employee Profile</span>
            <button class="icon-btn" onclick="closeModal('editEmployeeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.update', $employee->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="hr-modal-body">
                <!-- Employee Photo Upload with Preview -->
                <div class="hr-form-group" style="background: rgba(248, 250, 252, 0.85); border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 12px 16px; margin-bottom: 6px;">
                    <label class="hr-form-label" style="margin-bottom: 6px;">Profile Photo</label>
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div id="editPhotoPreviewWrap">
                            @if($employee->photo_url)
                                <img id="editPhotoPreviewImg" src="{{ $employee->photo_url }}" alt="Preview" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div id="editPhotoAvatarPlaceholder" style="display: none; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; box-shadow: 0 2px 8px rgba(168, 85, 247, 0.25);">
                                    {{ $employee->initials }}
                                </div>
                            @else
                                <img id="editPhotoPreviewImg" src="" alt="Preview" style="display: none; width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div id="editPhotoAvatarPlaceholder" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; box-shadow: 0 2px 8px rgba(168, 85, 247, 0.25);">
                                    {{ $employee->initials }}
                                </div>
                            @endif
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <input type="file" name="photo" id="editEmployeePhoto" class="hr-input" accept="image/*" onchange="previewEditPhoto(this)" style="padding: 7px 10px;">
                            <small style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                                Upload new photo (JPG, PNG, WEBP max 5MB).
                            </small>
                        </div>
                        @if($employee->photo)
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #ef4444; cursor: pointer; background: #fee2e2; padding: 6px 12px; border-radius: 6px;">
                                <input type="checkbox" name="remove_photo" value="1">
                                <span>Remove Photo</span>
                            </label>
                        @endif
                    </div>
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">First Name *</label>
                        <input type="text" name="first_name" class="hr-input" required value="{{ $employee->first_name }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="hr-input" value="{{ $employee->middle_name }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Last Name *</label>
                        <input type="text" name="last_name" class="hr-input" required value="{{ $employee->last_name }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Civil Status</label>
                        <select name="civil_status" class="hr-select">
                            @foreach(['Single', 'Married', 'Widowed', 'Divorced'] as $cs)
                                <option value="{{ $cs }}" {{ $employee->civil_status === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Nationality</label>
                        <input type="text" name="nationality" class="hr-input" value="{{ $employee->nationality }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="hr-input" value="{{ $employee->mobile_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email</label>
                        <input type="email" name="email" class="hr-input" value="{{ $employee->email }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Branch *</label>
                        <input type="hidden" name="branch_id" value="{{ $employee->branch_id }}">
                        <input type="text" class="hr-input" value="{{ $employee->branch?->name }}" disabled>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date Hired *</label>
                        <input type="date" name="date_hired" class="hr-input" required value="{{ $employee->date_hired }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Status *</label>
                        <select name="employment_status" class="hr-select" required>
                            @foreach(['Active', 'Probationary', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Retired'] as $es)
                                <option value="{{ $es }}" {{ $employee->employment_status === $es ? 'selected' : '' }}>{{ $es }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" class="hr-select" required>
                            @foreach(['Regular', 'Probationary', 'Part-time', 'Casual', 'Contractual'] as $et)
                                <option value="{{ $et }}" {{ $employee->employment_type === $et ? 'selected' : '' }}>{{ $et }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Source *</label>
                        <div style="display: flex; gap: 16px; align-items: center; margin-top: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="employment_source" value="Company" {{ ($employee->employment_source ?? 'Company') === 'Company' ? 'checked' : '' }} onchange="document.getElementById('edit_emp_agency_box').style.display='none'; document.getElementById('edit_emp_company_box').style.display='block';">
                                <span>Company</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="employment_source" value="Agency" {{ ($employee->employment_source ?? '') === 'Agency' ? 'checked' : '' }} onchange="document.getElementById('edit_emp_agency_box').style.display='block'; document.getElementById('edit_emp_company_box').style.display='none';">
                                <span>Agency</span>
                            </label>
                        </div>
                    </div>
                    <div class="hr-form-group" id="edit_emp_company_box" style="{{ ($employee->employment_source ?? 'Company') === 'Agency' ? 'display: none;' : '' }}">
                        <label class="hr-form-label">Company Name</label>
                        <input type="text" name="company_name" class="hr-input" value="{{ $employee->company_name ?? $employee->company_agency_name }}" placeholder="e.g. Bistro Hospitality Group Inc.">
                    </div>
                    <div class="hr-form-group" id="edit_emp_agency_box" style="{{ ($employee->employment_source ?? 'Company') === 'Agency' ? '' : 'display: none;' }}">
                        <label class="hr-form-label">Agency Name</label>
                        <input type="text" name="agency_name" class="hr-input" value="{{ $employee->agency_name ?? $employee->company_agency_name }}" placeholder="e.g. ABC Manpower & Staffing">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Basic Salary</label>
                        <input type="number" step="0.01" name="basic_salary" class="hr-input" value="{{ $employee->basic_salary }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Type</label>
                        <select name="salary_type" class="hr-select">
                            @foreach(['Monthly', 'Daily', 'Hourly'] as $st)
                                <option value="{{ $st }}" {{ $employee->salary_type === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency</label>
                        <select name="pay_frequency" class="hr-select">
                            @foreach(['Semi-Monthly', 'Monthly', 'Weekly'] as $pf)
                                <option value="{{ $pf }}" {{ $employee->pay_frequency === $pf ? 'selected' : '' }}>{{ $pf }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Allowances</label>
                        <input type="number" step="0.01" name="allowances" class="hr-input" value="{{ $employee->allowances }}">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editEmployeeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function previewEditPhoto(input) {
    var preview = document.getElementById('editPhotoPreviewImg');
    var placeholder = document.getElementById('editPhotoAvatarPlaceholder');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
