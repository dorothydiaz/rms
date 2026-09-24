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
                @if($employee->employment_source === 'Agency')
                    <span class="hr-badge" style="background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25);">
                        <i class="ph ph-handshake"></i> Agency: {{ $employee->company_or_agency }}
                    </span>
                @else
                    <span class="hr-badge" style="background: rgba(59, 130, 246, 0.12); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.25);">
                        <i class="ph ph-buildings"></i> Company: {{ $employee->company_or_agency }}
                    </span>
                @endif
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
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Employment Sourcing</div>
                    <div style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 3px;">
                        @if($employee->employment_source === 'Agency')
                            <span class="hr-badge" style="background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25);">
                                <i class="ph ph-handshake"></i> Staffing Agency
                            </span>
                        @else
                            <span class="hr-badge" style="background: rgba(59, 130, 246, 0.12); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.25);">
                                <i class="ph ph-buildings"></i> Direct Company
                            </span>
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                        {{ $employee->employment_source === 'Agency' ? 'Agency Partner' : 'Corporate Entity' }}
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 3px;">
                        {{ $employee->company_or_agency }}
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

        <!-- Card 4: Employee Documents Repository (Directly Linked to Employee) -->
        <div class="hr-table-card" style="margin-bottom: 0;" id="employeeDocumentsSection">
            <div class="hr-table-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="hr-table-title">
                        <i class="ph ph-folder-notch-open" style="color: #7c3aed;"></i>
                        Employee Documents Repository
                    </span>
                    <span class="hr-badge hr-badge-neutral">{{ $employee->documents->count() }} {{ Str::plural('File', $employee->documents->count()) }}</span>
                </div>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openModal('uploadDocModal')">
                    <i class="ph ph-upload-simple"></i>
                    <span>Upload Document</span>
                </button>
            </div>
            <div class="hr-table-wrapper">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>Document Title</th>
                            <th>Type</th>
                            <th>Uploaded</th>
                            <th>Expiry / Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $doc)
                            @php
                                $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                                $iconClass = 'ph-file-text';
                                $iconColor = '#6366f1';
                                if (in_array($ext, ['pdf'])) {
                                    $iconClass = 'ph-file-pdf';
                                    $iconColor = '#ef4444';
                                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                                    $iconClass = 'ph-file-image';
                                    $iconColor = '#10b981';
                                } elseif (in_array($ext, ['doc', 'docx'])) {
                                    $iconClass = 'ph-file-doc';
                                    $iconColor = '#2563eb';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(147, 51, 234, 0.08); border: 1px solid rgba(147, 51, 234, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="ph {{ $iconClass }}" style="font-size: 19px; color: {{ $iconColor }};"></i>
                                        </div>
                                        <div>
                                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" style="font-weight: 700; color: #0f172a; text-decoration: none;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#0f172a'">
                                                {{ $doc->title }}
                                            </a>
                                            <div style="font-size: 11.5px; color: #64748b; margin-top: 1px;">
                                                {{ $doc->file_name }}
                                                @if($doc->notes)
                                                    &bull; <span style="font-style: italic;">{{ Str::limit($doc->notes, 32) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="hr-badge hr-badge-neutral">{{ $doc->document_type }}</span>
                                </td>
                                <td style="font-size: 12.5px; color: #475569;">
                                    {{ $doc->created_at ? $doc->created_at->format('M d, Y') : '—' }}
                                </td>
                                <td>
                                    @if($doc->expiry_date)
                                        @php
                                            $isExpired = $doc->expiry_date->isPast();
                                            $isExpiring = !$isExpired && $doc->expiry_date->diffInDays(now()) <= 30;
                                        @endphp
                                        @if($isExpired)
                                            <span class="hr-badge hr-badge-danger" title="Expired on {{ $doc->expiry_date->format('M d, Y') }}">
                                                <i class="ph ph-warning-circle"></i> Expired ({{ $doc->expiry_date->format('M d, Y') }})
                                            </span>
                                        @elseif($isExpiring)
                                            <span class="hr-badge hr-badge-warning" title="Expiring soon">
                                                <i class="ph ph-clock"></i> Expires: {{ $doc->expiry_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span style="font-size: 12.5px; font-weight: 600; color: #0f172a;">
                                                {{ $doc->expiry_date->format('M d, Y') }}
                                            </span>
                                        @endif
                                    @else
                                        <span style="color: #94a3b8; font-size: 12px;">No Expiry Date</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Document in New Tab">
                                            <i class="ph ph-eye"></i>
                                        </a>
                                        <a href="{{ route('hr.people.documents.download', $doc->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="Download Document">
                                            <i class="ph ph-download-simple"></i>
                                        </a>
                                        <form method="POST" action="{{ route('hr.people.documents.destroy', $doc->id) }}" onsubmit="return confirm('Are you sure you want to delete this document: {{ addslashes($doc->title) }}?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete Document">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 36px 20px;">
                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 10px;">
                                        <i class="ph ph-folder-open"></i>
                                    </div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 14px;">No documents uploaded yet</div>
                                    <p style="color: #64748b; font-size: 12.5px; margin: 4px auto 14px; max-width: 420px;">
                                        Attach health certificates, sanitary permits, employment contracts, government IDs, and clearances for <strong>{{ $employee->full_name }}</strong>.
                                    </p>
                                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openModal('uploadDocModal')">
                                        <i class="ph ph-upload-simple"></i> Upload First Document
                                    </button>
                                </td>
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
                    <span style="color: #64748b;">Sourcing:</span>
                    <strong style="color: {{ $employee->employment_source === 'Agency' ? '#7e22ce' : '#1d4ed8' }};">
                        {{ $employee->employment_source ?? 'Company' }}
                    </strong>
                </div>
                <div>
                    <span style="color: #64748b;">{{ $employee->employment_source === 'Agency' ? 'Agency:' : 'Company:' }}</span>
                    <strong>{{ $employee->company_or_agency }}</strong>
                </div>
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

        <!-- Document Compliance & Health Card Status -->
        <div class="hr-table-card" style="margin-bottom: 0;">
            <div class="hr-table-header">
                <span class="hr-table-title"><i class="ph ph-files" style="color: #7c3aed;"></i> Documents Compliance</span>
            </div>
            <div style="padding: 18px; font-size: 13px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #64748b;">Total Filed Documents</span>
                    <strong style="color: #0f172a; font-size: 14px;">{{ $employee->documents->count() }}</strong>
                </div>
                @php
                    $healthDocs = $employee->documents->whereIn('document_type', ['Health Permit', 'Food Handler Certificate']);
                    $hasExpired = $employee->documents->filter(fn($d) => $d->expiry_date && $d->expiry_date->isPast())->count();
                @endphp
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #64748b;">Health & Food Clearances</span>
                    @if($healthDocs->count() > 0)
                        <span class="hr-badge hr-badge-success">{{ $healthDocs->count() }} Attached</span>
                    @else
                        <span class="hr-badge hr-badge-warning">Missing</span>
                    @endif
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #64748b;">Expired Certificates</span>
                    @if($hasExpired > 0)
                        <span class="hr-badge hr-badge-danger">{{ $hasExpired }} Expired</span>
                    @else
                        <span class="hr-badge hr-badge-neutral">0 Expired</span>
                    @endif
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="width: 100%; margin-top: 4px;" onclick="openModal('uploadDocModal')">
                    <i class="ph ph-plus-circle"></i> Add New Document
                </button>
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
                        <label class="hr-form-label">Company Name *</label>
                        <select name="company_name" class="hr-select">
                            @foreach($companies as $c)
                                <option value="{{ $c->name }}" {{ ($employee->company_name === $c->name || $employee->company_id === $c->id) ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->code }})
                                </option>
                            @endforeach
                            @if($employee->company_name && !$companies->contains('name', $employee->company_name))
                                <option value="{{ $employee->company_name }}" selected>{{ $employee->company_name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="hr-form-group" id="edit_emp_agency_box" style="{{ ($employee->employment_source ?? 'Company') === 'Agency' ? '' : 'display: none;' }}">
                        <label class="hr-form-label">Agency Name *</label>
                        <select name="agency_name" class="hr-select">
                            @foreach($agencies as $a)
                                <option value="{{ $a->name }}" {{ ($employee->agency_name === $a->name || $employee->company_id === $a->id) ? 'selected' : '' }}>
                                    {{ $a->name }} ({{ $a->code }})
                                </option>
                            @endforeach
                            @if($employee->agency_name && !$agencies->contains('name', $employee->agency_name))
                                <option value="{{ $employee->agency_name }}" selected>{{ $employee->agency_name }}</option>
                            @endif
                        </select>
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

<!-- Upload Employee Document Modal (Pre-Linked to Current Employee) -->
<div id="uploadDocModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title">
                <i class="ph ph-file-arrow-up" style="color: #7c3aed;"></i>
                Upload Employee Document
            </span>
            <button type="button" class="icon-btn" onclick="closeModal('uploadDocModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.documents.store') }}" enctype="multipart/form-data">
            @csrf
            <!-- Pre-linked Employee Hidden Input -->
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

            <div class="hr-modal-body">
                <!-- Pre-Linked Employee Banner -->
                <div style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.06), rgba(99, 102, 241, 0.08)); border: 1.5px solid rgba(124, 58, 237, 0.22); border-radius: 14px; padding: 14px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        @if($employee->photo_url)
                            <img src="{{ $employee->photo_url }}" alt="{{ $employee->full_name }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                        @else
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; flex-shrink: 0; box-shadow: 0 2px 8px rgba(168, 85, 247, 0.3);">
                                {{ $employee->initials }}
                            </div>
                        @endif
                        <div style="min-width: 0;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 14px; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $employee->full_name }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span><code style="background: rgba(15, 23, 42, 0.06); padding: 2px 6px; border-radius: 4px; font-size: 11px;">{{ $employee->employee_id }}</code></span>
                                <span>&bull;</span>
                                <span>{{ $employee->position?->name ?? 'Employee' }}</span>
                                <span>&bull;</span>
                                <span>{{ $employee->branch?->name }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="hr-badge hr-badge-success" style="flex-shrink: 0; font-size: 11.5px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="ph ph-link"></i> Auto-Linked
                    </span>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Document Category *</label>
                    <select name="document_type" class="hr-select" required>
                        <option value="Health Permit">Health Permit / Sanitary Card</option>
                        <option value="Food Handler Certificate">Food Handler Certificate</option>
                        <option value="Employment Contract">Employment Contract</option>
                        <option value="Government ID">Government ID (SSS, TIN, PhilHealth, Pag-IBIG)</option>
                        <option value="NBI Clearance">NBI / Police Clearance</option>
                        <option value="Barista / Chef Certification">Barista / Culinary Certification</option>
                        <option value="Performance Review">Performance Evaluation Document</option>
                        <option value="Medical Certificate">Medical / Fit-to-Work Clearance</option>
                        <option value="Other">Other Operational Record</option>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Document Title *</label>
                    <input type="text" name="title" class="hr-input" required placeholder="e.g. 2026 City Health Certificate, Employment Contract">
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Attach File * (Max 10MB)</label>
                    <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 18px 16px; text-align: center; background: #f8fafc; transition: all 0.2s ease;">
                        <i class="ph ph-file-arrow-up" style="font-size: 32px; color: #7c3aed; margin-bottom: 6px; display: inline-block;"></i>
                        <div style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 2px;">Select document to upload</div>
                        <div style="font-size: 11.5px; color: #64748b; margin-bottom: 12px;">Accepted: PDF, PNG, JPG, JPEG, WEBP, DOC, DOCX up to 10MB</div>
                        <input type="file" name="file" class="hr-input" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" style="padding: 7px 10px; max-width: 400px; margin: 0 auto; display: block;">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">
                        Expiry Date
                        <span style="font-weight: 400; font-size: 12px; color: #64748b;">(Required for Health & Sanitary permits)</span>
                    </label>
                    <input type="date" name="expiry_date" class="hr-input">
                    <small style="font-size: 11.5px; color: #64748b; margin-top: 4px; display: block;">
                        Leave empty for documents that do not expire (e.g. permanent contracts or birth certificates).
                    </small>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">
                        Notes / Remarks
                        <span style="font-weight: 400; font-size: 12px; color: #64748b;">(Optional)</span>
                    </label>
                    <textarea name="notes" class="hr-input" rows="2" placeholder="e.g. Issued by Manila City Health Office; annual renewal required." style="resize: vertical;"></textarea>
                </div>
            </div>

            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('uploadDocModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-upload-simple"></i> Upload to Repository
                </button>
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
