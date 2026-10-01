@extends('layouts.app')

@section('title', $employee->full_name . ' - Employee Profile & 201 File')

@section('content')
@if($errors->any())
    <div style="background: #fee2e2; border: 1.5px solid #ef4444; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #991b1b; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15);">
        <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-warning-circle" style="font-size: 18px;"></i>
            <span>Unable to process action. Please review the following errors:</span>
        </div>
        <ul style="margin: 0; padding-left: 24px; font-size: 13px;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('success'))
    <div style="background: #dcfce7; border: 1.5px solid #22c55e; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #166534; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(34, 197, 94, 0.15);">
        <i class="ph ph-check-circle" style="font-size: 20px;"></i>
        <span style="font-weight: 600;">{{ session('success') }}</span>
    </div>
@endif

<!-- Breadcrumb Navigation -->
<div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 14px;">
    <a href="{{ route('hr.people.employees') }}" style="color: #7c3aed; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
        <i class="ph ph-users"></i> Employee Management
    </a>
    <span>/</span>
    <span style="color: #0f172a; font-weight: 600;">{{ $employee->full_name }} ({{ $employee->employee_id }})</span>
</div>

<!-- Strong Employee Profile Header -->
<div class="hr-emp-profile-header">
    <div class="hr-emp-header-left">
        @if($employee->profile_photo_url)
            <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->full_name }}" class="hr-emp-header-avatar">
        @else
            <div class="hr-emp-header-initials">
                {{ strtoupper(substr($employee->first_name ?? '', 0, 1) . substr($employee->last_name ?? '', 0, 1)) ?: 'EM' }}
            </div>
        @endif

        <div class="hr-emp-header-info">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                <span class="hr-badge hr-badge-neutral" style="font-family: monospace; font-size: 12px; font-weight: 700;">
                    {{ $employee->employee_id }}
                </span>
                @if($employee->employment_status === 'Active')
                    <span class="hr-badge hr-badge-success">Active</span>
                @elseif($employee->employment_status === 'Probationary')
                    <span class="hr-badge hr-badge-warning">Probationary</span>
                @elseif($employee->employment_status === 'On Leave')
                    <span class="hr-badge hr-badge-info">On Leave</span>
                @else
                    <span class="hr-badge hr-badge-danger">{{ $employee->employment_status }}</span>
                @endif
                <span class="hr-badge hr-badge-neutral">
                    {{ $employee->employment_type ?? 'Regular' }}
                </span>
                @if($employee->company_or_agency)
                    <span class="hr-badge" style="background: rgba(124, 58, 237, 0.08); color: #7c3aed; border: 1px solid rgba(124, 58, 237, 0.2);">
                        <i class="ph ph-buildings"></i> {{ $employee->company_or_agency }}
                    </span>
                @endif
            </div>

            <h1 class="hr-emp-name">{{ $employee->full_name }}</h1>

            <div class="hr-emp-meta">
                <span><i class="ph ph-briefcase"></i> <strong>{{ $employee->position?->name ?? 'Unassigned Position' }}</strong></span>
                <span class="meta-dot">&bull;</span>
                <span><i class="ph ph-tree-structure"></i> {{ $employee->department?->name ?? 'General Department' }}</span>
                <span class="meta-dot">&bull;</span>
                <span><i class="ph ph-storefront"></i> {{ $employee->branch?->name ?? 'Main Branch' }}</span>
                <span class="meta-dot">&bull;</span>
                <span><i class="ph ph-calendar"></i> Hired: <strong>{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</strong> ({{ $stats['years_of_service'] }})</span>
            </div>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="hr-emp-header-actions">
        <button type="button" class="hr-btn hr-btn-secondary" onclick="openModal('editEmployeeModal')">
            <i class="ph ph-pencil-simple"></i>
            <span>Edit Employee</span>
        </button>

        <button type="button" class="hr-btn hr-btn-secondary" onclick="openModal('generateCoeModal')">
            <i class="ph ph-certificate"></i>
            <span>Generate COE</span>
        </button>

        <a href="{{ route('hr.people.employees.print-201', $employee->id) }}" target="_blank" class="hr-btn hr-btn-secondary">
            <i class="ph ph-printer"></i>
            <span>Print 201 File</span>
        </a>

        <!-- More Actions Dropdown -->
        <div class="hr-action-menu-wrap" style="position: relative; display: inline-block;">
            <button type="button" class="hr-btn hr-btn-primary" onclick="toggleProfileMoreActions(event)">
                <span>More Actions</span>
                <i class="ph ph-caret-down"></i>
            </button>
            <div id="profileMoreActionsDropdown" class="hr-action-dropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 70; min-width: 210px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); padding: 6px; font-size: 13px;">
                <button type="button" class="hr-dropdown-item" onclick="openModal('editEmployeeModal')">
                    <i class="ph ph-pencil-simple"></i> Edit Employee
                </button>
                <button type="button" class="hr-dropdown-item" onclick="openModal('changePositionModal')">
                    <i class="ph ph-briefcase"></i> Change Position
                </button>
                <button type="button" class="hr-dropdown-item" onclick="openModal('transferModal')">
                    <i class="ph ph-arrows-left-right"></i> Transfer Department
                </button>
                <button type="button" class="hr-dropdown-item" onclick="openModal('transferModal')">
                    <i class="ph ph-storefront"></i> Transfer Branch
                </button>
                <button type="button" class="hr-dropdown-item" onclick="openModal('changeSalaryModal')">
                    <i class="ph ph-currency-dollar"></i> Change Salary
                </button>
                <button type="button" class="hr-dropdown-item" onclick="openModal('changeStatusModal')">
                    <i class="ph ph-arrows-clockwise"></i> Change Employment Status
                </button>
                <div style="height: 1px; background: #f1f5f9; margin: 4px 0;"></div>
                <a href="{{ route('hr.people.employees.coe', $employee->id) }}" target="_blank" class="hr-dropdown-item">
                    <i class="ph ph-certificate"></i> Generate COE
                </a>
                <a href="{{ route('hr.people.employees.print-201', $employee->id) }}" target="_blank" class="hr-dropdown-item">
                    <i class="ph ph-printer"></i> Print 201 File
                </a>
                <div style="height: 1px; background: #fee2e2; margin: 4px 0;"></div>
                <button type="button" class="hr-dropdown-item" style="color: #dc2626;" onclick="openModal('processSeparationModal')">
                    <i class="ph ph-user-minus" style="color: #dc2626;"></i> Process Separation
                </button>
                <button type="button" class="hr-dropdown-item" style="color: #dc2626;" onclick="openModal('archiveEmployeeModal')">
                    <i class="ph ph-archive" style="color: #dc2626;"></i> Archive Employee
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Compact Horizontal Profile Navigation (8 TABS ONLY) -->
<div class="hr-profile-nav-wrap">
    <div class="hr-profile-tabs" role="tablist">
        <button type="button" class="hr-tab-btn active" data-tab="overview" onclick="switchProfileTab('overview')">
            <i class="ph ph-squares-four"></i>
            <span>Overview</span>
        </button>
        <button type="button" class="hr-tab-btn" data-tab="personal" onclick="switchProfileTab('personal')">
            <i class="ph ph-user"></i>
            <span>Personal</span>
        </button>
        <button type="button" class="hr-tab-btn" data-tab="employment" onclick="switchProfileTab('employment')">
            <i class="ph ph-briefcase"></i>
            <span>Employment</span>
        </button>
        <button type="button" class="hr-tab-btn" data-tab="family-education" onclick="switchProfileTab('family-education')">
            <i class="ph ph-users-three"></i>
            <span>Family & Education</span>
        </button>
        <button type="button" class="hr-tab-btn" data-tab="government-ids" onclick="switchProfileTab('government-ids')">
            <i class="ph ph-identification-card"></i>
            <span>Government IDs</span>
        </button>
        <button type="button" class="hr-tab-btn" data-tab="documents" onclick="switchProfileTab('documents')">
            <i class="ph ph-folder-notch-open"></i>
            <span>Documents</span>
            @if($employee->documents->count() > 0)
                <span class="hr-tab-badge">{{ $employee->documents->count() }}</span>
            @endif
        </button>
        <button type="button" class="hr-tab-btn" data-tab="attendance-leave" onclick="switchProfileTab('attendance-leave')">
            <i class="ph ph-clock"></i>
            <span>Attendance & Leave</span>
        </button>
        <button type="button" class="hr-tab-btn" data-tab="payroll-performance" onclick="switchProfileTab('payroll-performance')">
            <i class="ph ph-currency-dollar"></i>
            <span>Payroll & Performance</span>
        </button>
    </div>
</div>

<!-- TAB PANELS CONTAINER -->
<div class="hr-tab-content-area" style="margin-top: 18px;">

    <!-- ========================================================================= -->
    <!-- TAB 1: OVERVIEW -->
    <!-- ========================================================================= -->
    <div id="tab-overview" class="hr-tab-pane active">
        <!-- Quick Statistics (5 Compact Cards) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
            <div class="hr-stat-card">
                <div class="hr-stat-icon-wrap" style="background: rgba(124, 58, 237, 0.08); color: #7c3aed;">
                    <i class="ph ph-coins"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Leave Balance</span>
                    <div class="hr-stat-value" style="color: #7c3aed;">{{ $stats['leave_balance'] }}</div>
                    <span class="hr-stat-sub">Available Credits</span>
                </div>
            </div>

            <div class="hr-stat-card">
                <div class="hr-stat-icon-wrap" style="background: rgba(16, 185, 129, 0.08); color: #10b981;">
                    <i class="ph ph-chart-line-up"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Attendance Rate</span>
                    <div class="hr-stat-value" style="color: #059669;">{{ $stats['attendance_rate'] }}</div>
                    <span class="hr-stat-sub">Present & On Duty</span>
                </div>
            </div>

            <div class="hr-stat-card">
                <div class="hr-stat-icon-wrap" style="background: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                    <i class="ph ph-file-check"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Documents Verified</span>
                    <div class="hr-stat-value" style="color: #2563eb;">{{ $stats['documents_verified'] }}</div>
                    <span class="hr-stat-sub">201 Compliance</span>
                </div>
            </div>

            <div class="hr-stat-card">
                <div class="hr-stat-icon-wrap" style="background: rgba(219, 39, 119, 0.08); color: #db2777;">
                    <i class="ph ph-wallet"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Current Salary</span>
                    <div class="hr-stat-value" style="color: #be185d;">
                        @if($canViewSensitive)
                            {{ $stats['current_salary'] }}
                        @else
                            ₱••••••
                        @endif
                    </div>
                    <span class="hr-stat-sub">{{ $employee->pay_frequency ?? 'Semi-monthly' }}</span>
                </div>
            </div>

            <div class="hr-stat-card">
                <div class="hr-stat-icon-wrap" style="background: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                    <i class="ph ph-hourglass-high"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Years of Service</span>
                    <div class="hr-stat-value" style="color: #d97706;">{{ $stats['years_of_service'] }}</div>
                    <span class="hr-stat-sub">Tenure with Org</span>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
            <!-- Left: Employment & Contact Summaries -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Employment Summary Card -->
                <div class="hr-card">
                    <div class="hr-card-header">
                        <span class="hr-card-title"><i class="ph ph-briefcase"></i> Employment Summary</span>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="switchProfileTab('employment')">
                            <span>View Full Employment</span>
                            <i class="ph ph-arrow-right"></i>
                        </button>
                    </div>
                    <div class="hr-detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Employee Number</span>
                            <span class="detail-val monospace">{{ $employee->employee_id }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Employment Type</span>
                            <span class="detail-val">{{ $employee->employment_type ?? 'Regular' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Employment Status</span>
                            <span class="detail-val">
                                <span class="hr-badge hr-badge-success" style="font-size: 11px;">{{ $employee->employment_status }}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Company / Sourcing</span>
                            <span class="detail-val">{{ $employee->company_or_agency ?? 'Direct Hire' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Branch</span>
                            <span class="detail-val">{{ $employee->branch?->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span class="detail-val">{{ $employee->department?->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Position</span>
                            <span class="detail-val"><strong>{{ $employee->position?->name ?? 'N/A' }}</strong></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Direct Supervisor</span>
                            <span class="detail-val">{{ $employee->supervisor?->full_name ?? 'None Assigned' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date Hired</span>
                            <span class="detail-val">{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date Regularized</span>
                            <span class="detail-val">{{ $employee->date_of_regularization ? \Carbon\Carbon::parse($employee->date_of_regularization)->format('M d, Y') : 'Pending evaluation' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Work Location</span>
                            <span class="detail-val">{{ $employee->work_location ?? $employee->branch?->name ?? 'On-Site' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Work Schedule</span>
                            <span class="detail-val">{{ $employee->work_schedule ?? 'Standard 8hr Restaurant Shift' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Summary Card -->
                <div class="hr-card">
                    <div class="hr-card-header">
                        <span class="hr-card-title"><i class="ph ph-address-book"></i> Contact Summary</span>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="switchProfileTab('personal')">
                            <span>Edit Contact Info</span>
                            <i class="ph ph-arrow-right"></i>
                        </button>
                    </div>
                    <div class="hr-detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Mobile Number</span>
                            <span class="detail-val">{{ $employee->mobile_number ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Company Email</span>
                            <span class="detail-val">{{ $employee->company_email ?? $employee->email ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Personal Email</span>
                            <span class="detail-val">{{ $employee->personal_email ?? $employee->email ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Current Address</span>
                            <span class="detail-val">{{ $employee->address ?? 'No address registered' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Recent Activity Stream -->
            <div>
                <div class="hr-card">
                    <div class="hr-card-header">
                        <span class="hr-card-title"><i class="ph ph-activity"></i> Recent Activity</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        @forelse($recentActivities as $act)
                            <div style="display: flex; gap: 12px; align-items: flex-start; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(124, 58, 237, 0.08); color: {{ $act['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                                    <i class="ph {{ $act['icon'] }}"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 13px; font-weight: 600; color: #0f172a;">{{ $act['title'] }}</div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 1px;">{!! $act['desc'] !!}</div>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">{{ $act['time'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; color: #94a3b8; padding: 24px 10px;">
                                <i class="ph ph-clock-counter-clockwise" style="font-size: 28px; display: block; margin-bottom: 6px;"></i>
                                No recent activity logged yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: PERSONAL -->
    <!-- ========================================================================= -->
    <div id="tab-personal" class="hr-tab-pane">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Basic Information -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <span class="hr-card-title"><i class="ph ph-user"></i> Basic Information</span>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('editEmployeeModal')">
                        <i class="ph ph-pencil-simple"></i> Edit Basic Info
                    </button>
                </div>
                <div class="hr-detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Employee Number</span>
                        <span class="detail-val monospace">{{ $employee->employee_id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">First Name</span>
                        <span class="detail-val">{{ $employee->first_name }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Middle Name</span>
                        <span class="detail-val">{{ $employee->middle_name ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Last Name</span>
                        <span class="detail-val">{{ $employee->last_name }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Suffix</span>
                        <span class="detail-val">{{ $employee->suffix ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Preferred Name / Nickname</span>
                        <span class="detail-val">{{ $employee->preferred_name ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date of Birth</span>
                        <span class="detail-val">{{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('F d, Y') : '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Place of Birth</span>
                        <span class="detail-val">{{ $employee->birth_place ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Sex / Gender</span>
                        <span class="detail-val">{{ $employee->gender ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Civil Status</span>
                        <span class="detail-val">{{ $employee->civil_status ?? 'Single' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Nationality</span>
                        <span class="detail-val">{{ $employee->nationality ?? 'Filipino' }}</span>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <span class="hr-card-title"><i class="ph ph-phone"></i> Contact Information</span>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('editEmployeeModal')">
                        <i class="ph ph-pencil-simple"></i> Edit Contacts
                    </button>
                </div>
                <div class="hr-detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Personal Email</span>
                        <span class="detail-val">{{ $employee->personal_email ?? $employee->email ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Company Email</span>
                        <span class="detail-val">{{ $employee->company_email ?? $employee->email ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Mobile Number</span>
                        <span class="detail-val">{{ $employee->mobile_number ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Telephone Number</span>
                        <span class="detail-val">{{ $employee->telephone_number ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Addresses: Current & Permanent -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Current Address -->
                <div class="hr-card">
                    <div class="hr-card-header">
                        <span class="hr-card-title"><i class="ph ph-map-pin"></i> Current Residential Address</span>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('editEmployeeModal')">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </button>
                    </div>
                    <div style="padding: 10px 0; font-size: 13.5px; color: #334155; line-height: 1.6;">
                        {{ $employee->address ?? 'No current address provided.' }}
                    </div>
                </div>

                <!-- Permanent Address -->
                <div class="hr-card">
                    <div class="hr-card-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="hr-card-title"><i class="ph ph-house"></i> Permanent Address</span>
                            @if($employee->permanent_address === $employee->address && !empty($employee->address))
                                <span class="hr-badge hr-badge-neutral" style="font-size: 10px;">Same as Current</span>
                            @endif
                        </div>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('editEmployeeModal')">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </button>
                    </div>
                    <div style="padding: 10px 0; font-size: 13.5px; color: #334155; line-height: 1.6;">
                        {{ $employee->permanent_address ?? $employee->address ?? 'No permanent address provided.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: EMPLOYMENT -->
    <!-- ========================================================================= -->
    <div id="tab-employment" class="hr-tab-pane">
        <div style="display: flex; flex-direction: column; gap: 22px;">
            <!-- 1. Current Employment -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <span class="hr-card-title"><i class="ph ph-identification-badge"></i> Current Employment Details</span>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('editEmployeeModal')">
                        <i class="ph ph-pencil-simple"></i> Edit Employment
                    </button>
                </div>
                <div class="hr-detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Employee Number</span>
                        <span class="detail-val monospace">{{ $employee->employee_id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Employment Type</span>
                        <span class="detail-val">{{ $employee->employment_type ?? 'Regular' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Employment Status</span>
                        <span class="detail-val">
                            <span class="hr-badge hr-badge-success">{{ $employee->employment_status }}</span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Company / Sourcing</span>
                        <span class="detail-val">{{ $employee->company_or_agency ?? 'Direct Hire' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Branch</span>
                        <span class="detail-val">{{ $employee->branch?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Department</span>
                        <span class="detail-val">{{ $employee->department?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Position</span>
                        <span class="detail-val"><strong>{{ $employee->position?->name ?? 'Unassigned' }}</strong></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Job Level</span>
                        <span class="detail-val">{{ $employee->job_level ?? 'Staff Level 1' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Direct Supervisor</span>
                        <span class="detail-val">{{ $employee->supervisor?->full_name ?? 'None Assigned' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date Hired</span>
                        <span class="detail-val">{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Probationary Start</span>
                        <span class="detail-val">{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Probation End</span>
                        <span class="detail-val">
                            {{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->addMonths(6)->format('M d, Y') : '—' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date Regularized</span>
                        <span class="detail-val">{{ $employee->date_of_regularization ? \Carbon\Carbon::parse($employee->date_of_regularization)->format('M d, Y') : 'Pending evaluation' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Work Location</span>
                        <span class="detail-val">{{ $employee->work_location ?? $employee->branch?->name ?? 'On-Site' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Work Schedule</span>
                        <span class="detail-val">{{ $employee->work_schedule ?? 'Standard Shift' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Basic Salary</span>
                        <span class="detail-val" style="color: #7c3aed; font-weight: 700;">
                            @if($canViewSensitive)
                                ₱{{ number_format($employee->basic_salary, 2) }}
                            @else
                                ₱••••••
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Salary Frequency</span>
                        <span class="detail-val">{{ $employee->pay_frequency ?? 'Semi-monthly' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Pay Type</span>
                        <span class="detail-val">{{ $employee->pay_type ?? 'Salaried Monthly / Fixed' }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Employment History (Do NOT overwrite historical records) -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-clock-counter-clockwise"></i> Employment History</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Preserved audit trail of promotions, transfers, and assignments</span>
                    </div>
                </div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Branch</th>
                                <th>Employment Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Reason / Movement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->employmentHistories as $h)
                                <tr>
                                    <td><strong>{{ $h->position?->name ?? 'N/A' }}</strong></td>
                                    <td>{{ $h->department?->name ?? 'N/A' }}</td>
                                    <td>{{ $h->branch?->name ?? 'N/A' }}</td>
                                    <td>{{ $h->employment_type ?? 'Regular' }}</td>
                                    <td>{{ $h->effective_date ? \Carbon\Carbon::parse($h->effective_date)->format('M d, Y') : '—' }}</td>
                                    <td>{{ $h->end_date ? \Carbon\Carbon::parse($h->end_date)->format('M d, Y') : 'Present' }}</td>
                                    <td>
                                        <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">
                                            {{ $h->reason ?? 'Internal Transition' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td><strong>{{ $employee->position?->name }}</strong></td>
                                    <td>{{ $employee->department?->name }}</td>
                                    <td>{{ $employee->branch?->name }}</td>
                                    <td>{{ $employee->employment_type ?? 'Regular' }}</td>
                                    <td>{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</td>
                                    <td>Present</td>
                                    <td><span class="hr-badge hr-badge-neutral">Initial Hire / Current Appointment</span></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Salary History -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-trend-up"></i> Salary History</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Compensation adjustment logs</span>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('changeSalaryModal')">
                        <i class="ph ph-plus"></i> Adjust Salary
                    </button>
                </div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Effective Date</th>
                                <th>Previous Salary</th>
                                <th>New Salary</th>
                                <th>Adjustment Type</th>
                                <th>Reason</th>
                                <th>Approved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $salaryLogs = is_array($employee->salary_history) ? $employee->salary_history : [];
                            @endphp
                            @forelse($salaryLogs as $sh)
                                <tr>
                                    <td>{{ $sh['effective_date'] ?? '—' }}</td>
                                    <td style="color: #64748b;">₱{{ number_format($sh['previous_salary'] ?? 0, 2) }}</td>
                                    <td style="color: #7c3aed; font-weight: 700;">₱{{ number_format($sh['new_salary'] ?? 0, 2) }}</td>
                                    <td><span class="hr-badge hr-badge-purple" style="font-size: 11px;">{{ $sh['adjustment_type'] ?? 'Merit Increase' }}</span></td>
                                    <td>{{ $sh['reason'] ?? 'Standard Revision' }}</td>
                                    <td>{{ $sh['approved_by'] ?? 'HR / Management' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>{{ $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : '—' }}</td>
                                    <td style="color: #64748b;">—</td>
                                    <td style="color: #7c3aed; font-weight: 700;">₱{{ number_format($employee->basic_salary, 2) }}</td>
                                    <td><span class="hr-badge hr-badge-neutral">Hiring Rate</span></td>
                                    <td>Initial compensation on appointment</td>
                                    <td>Management</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: FAMILY & EDUCATION -->
    <!-- ========================================================================= -->
    <div id="tab-family-education" class="hr-tab-pane">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- 1. Family / Dependents -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-users"></i> Family Members & Dependents</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Spouses, children, parents, and statutory dependents</span>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('addFamilyModal')">
                        <i class="ph ph-user-plus"></i> Add Family Member
                    </button>
                </div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Birth Date</th>
                                <th>Occupation</th>
                                <th>Contact Number</th>
                                <th>Dependent</th>
                                <th>Beneficiary</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $familyList = is_array($employee->family_dependents) ? $employee->family_dependents : [];
                            @endphp
                            @forelse($familyList as $fIdx => $fam)
                                <tr>
                                    <td><strong>{{ $fam['name'] ?? '—' }}</strong></td>
                                    <td>{{ $fam['relationship'] ?? '—' }}</td>
                                    <td>{{ !empty($fam['birth_date']) ? \Carbon\Carbon::parse($fam['birth_date'])->format('M d, Y') : '—' }}</td>
                                    <td>{{ $fam['occupation'] ?? '—' }}</td>
                                    <td>{{ $fam['contact_number'] ?? '—' }}</td>
                                    <td>
                                        @if(!empty($fam['is_dependent']))
                                            <span class="hr-badge hr-badge-success" style="font-size: 10.5px;">Yes</span>
                                        @else
                                            <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px;">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($fam['is_beneficiary']))
                                            <span class="hr-badge hr-badge-purple" style="font-size: 10.5px;">Yes</span>
                                        @else
                                            <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px;">No</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="{{ route('hr.people.employees.family.destroy', [$employee->id, $fIdx]) }}" onsubmit="return confirm('Delete this family member?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Remove">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 24px;">
                                        No family members or dependents registered.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Emergency Contacts -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-phone-call"></i> Emergency Contacts</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Designated point of contact for incidents and medical emergencies</span>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('addEmergencyModal')">
                        <i class="ph ph-plus"></i> Add Emergency Contact
                    </button>
                </div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Mobile Number</th>
                                <th>Address</th>
                                <th>Primary Contact</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->emergencyContacts as $ec)
                                <tr>
                                    <td><strong>{{ $ec->name }}</strong></td>
                                    <td>{{ $ec->relationship }}</td>
                                    <td>{{ $ec->mobile_number }}</td>
                                    <td>{{ $ec->address ?? '—' }}</td>
                                    <td>
                                        @if($ec->is_primary)
                                            <span class="hr-badge hr-badge-success" style="font-size: 11px;">
                                                <i class="ph ph-check"></i> Primary Contact
                                            </span>
                                        @else
                                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">Secondary</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="{{ route('hr.people.employees.emergency.destroy', [$employee->id, $ec->id]) }}" onsubmit="return confirm('Remove emergency contact {{ $ec->name }}?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Remove">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                        No emergency contacts on file. Click 'Add Emergency Contact' to add one.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Education -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-graduation-cap"></i> Educational Background</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Tertiary, secondary, vocational, and post-graduate attainment</span>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openModal('addEducationModal')">
                        <i class="ph ph-plus"></i> Add Education
                    </button>
                </div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Education Level</th>
                                <th>School / Institution</th>
                                <th>Course / Degree</th>
                                <th>Year Started</th>
                                <th>Year Completed</th>
                                <th>Graduation Status</th>
                                <th>Honors / Awards</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $eduList = is_array($employee->education_history) ? $employee->education_history : [];
                            @endphp
                            @forelse($eduList as $eIdx => $edu)
                                <tr>
                                    <td><span class="hr-badge hr-badge-neutral">{{ $edu['level'] ?? 'College' }}</span></td>
                                    <td><strong>{{ $edu['school'] ?? '—' }}</strong></td>
                                    <td>{{ $edu['course'] ?? '—' }}</td>
                                    <td>{{ $edu['year_started'] ?? '—' }}</td>
                                    <td>{{ $edu['year_completed'] ?? '—' }}</td>
                                    <td>
                                        <span class="hr-badge hr-badge-success" style="font-size: 10.5px;">{{ $edu['status'] ?? 'Graduated' }}</span>
                                    </td>
                                    <td>{{ $edu['honors'] ?? '—' }}</td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="{{ route('hr.people.employees.education.destroy', [$employee->id, $eIdx]) }}" onsubmit="return confirm('Delete this education entry?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Remove">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 24px;">
                                        No education records registered.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 5: GOVERNMENT IDs -->
    <!-- ========================================================================= -->
    <div id="tab-government-ids" class="hr-tab-pane">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-shield-check" style="color: #7c3aed; font-size: 22px;"></i>
                <div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Philippine Statutory Identification Numbers</div>
                    <div style="font-size: 12px; color: #64748b;">Masked by default for privacy & compliance. Authorized HR administrators can toggle view.</div>
                </div>
            </div>
            <div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="toggleMaskBtn" onclick="toggleGovIdMasking()">
                    <i class="ph ph-eye" id="maskIcon"></i>
                    <span id="maskBtnText">Show ID Numbers</span>
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
            <!-- SSS -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(59, 130, 246, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">SSS</div>
                        <span class="hr-card-title">Social Security System</span>
                    </div>
                    @if($employee->sss_verified)
                        <span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Verified</span>
                    @else
                        <span class="hr-badge hr-badge-warning">Pending Verification</span>
                    @endif
                </div>
                <div class="hr-detail-grid" style="grid-template-columns: 1fr;">
                    <div class="detail-item">
                        <span class="detail-label">SSS Number</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->sss_number ?? 'Not registered' }}" data-masked="{{ $employee->sss_number ? 'XXX-XX-' . substr($employee->sss_number, -4) : 'Not registered' }}">
                            {{ $employee->sss_number ? 'XXX-XX-' . substr($employee->sss_number, -4) : 'Not registered' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Membership Status</span>
                        <span class="detail-val">{{ $employee->sss_membership_status ?? ($employee->sss_number ? 'Regular Member' : 'Pending Enrollment') }}</span>
                    </div>
                </div>
            </div>

            <!-- PhilHealth -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(16, 185, 129, 0.1); color: #059669; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">PH</div>
                        <span class="hr-card-title">PhilHealth</span>
                    </div>
                    @if($employee->philhealth_verified)
                        <span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Verified</span>
                    @else
                        <span class="hr-badge hr-badge-warning">Pending Verification</span>
                    @endif
                </div>
                <div class="hr-detail-grid" style="grid-template-columns: 1fr;">
                    <div class="detail-item">
                        <span class="detail-label">PhilHealth Number</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->philhealth_number ?? 'Not registered' }}" data-masked="{{ $employee->philhealth_number ? 'XXX-XX-' . substr($employee->philhealth_number, -4) : 'Not registered' }}">
                            {{ $employee->philhealth_number ? 'XXX-XX-' . substr($employee->philhealth_number, -4) : 'Not registered' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Membership Status</span>
                        <span class="detail-val">{{ $employee->philhealth_membership_status ?? ($employee->philhealth_number ? 'Active Contributor' : 'Pending') }}</span>
                    </div>
                </div>
            </div>

            <!-- Pag-IBIG -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(234, 88, 12, 0.1); color: #ea580c; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">HDMF</div>
                        <span class="hr-card-title">Pag-IBIG Fund (HDMF)</span>
                    </div>
                    @if($employee->pagibig_verified)
                        <span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Verified</span>
                    @else
                        <span class="hr-badge hr-badge-warning">Pending Verification</span>
                    @endif
                </div>
                <div class="hr-detail-grid" style="grid-template-columns: 1fr;">
                    <div class="detail-item">
                        <span class="detail-label">MID Number</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->pagibig_number ?? 'Not registered' }}" data-masked="{{ $employee->pagibig_number ? 'XXXX-XXXX-' . substr($employee->pagibig_number, -4) : 'Not registered' }}">
                            {{ $employee->pagibig_number ? 'XXXX-XXXX-' . substr($employee->pagibig_number, -4) : 'Not registered' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Membership Status</span>
                        <span class="detail-val">{{ $employee->pagibig_membership_status ?? ($employee->pagibig_number ? 'Regular Member' : 'Pending') }}</span>
                    </div>
                </div>
            </div>

            <!-- BIR / TIN -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(220, 38, 38, 0.1); color: #dc2626; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">BIR</div>
                        <span class="hr-card-title">Bureau of Internal Revenue (TIN)</span>
                    </div>
                    @if($employee->tin_verified)
                        <span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Verified</span>
                    @else
                        <span class="hr-badge hr-badge-warning">Pending Verification</span>
                    @endif
                </div>
                <div class="hr-detail-grid" style="grid-template-columns: 1fr;">
                    <div class="detail-item">
                        <span class="detail-label">Taxpayer Identification Number (TIN)</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->tin_number ?? 'Not registered' }}" data-masked="{{ $employee->tin_number ? 'XXX-XXX-' . substr($employee->tin_number, -3) : 'Not registered' }}">
                            {{ $employee->tin_number ? 'XXX-XXX-' . substr($employee->tin_number, -3) : 'Not registered' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Revenue District Office (RDO)</span>
                        <span class="detail-val">{{ $employee->rdo_code ?? 'RDO 044 - Taguig / Regional' }}</span>
                    </div>
                </div>
            </div>

            <!-- Other IDs -->
            <div class="hr-card" style="grid-column: 1 / -1;">
                <div class="hr-card-header">
                    <span class="hr-card-title"><i class="ph ph-cards"></i> Other Government Issued Identifications</span>
                </div>
                <div class="hr-detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">PhilSys / National ID</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->philsys_id ?? 'Not provided' }}" data-masked="{{ $employee->philsys_id ? '••••••••' . substr($employee->philsys_id, -4) : 'Not provided' }}">
                            {{ $employee->philsys_id ? '••••••••' . substr($employee->philsys_id, -4) : 'Not provided' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Passport Number</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->passport_number ?? 'None' }}" data-masked="{{ $employee->passport_number ? '••••' . substr($employee->passport_number, -3) : 'None' }}">
                            {{ $employee->passport_number ? '••••' . substr($employee->passport_number, -3) : 'None' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Driver's License</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->driver_license ?? 'None' }}" data-masked="{{ $employee->driver_license ? '••••••' . substr($employee->driver_license, -3) : 'None' }}">
                            {{ $employee->driver_license ? '••••••' . substr($employee->driver_license, -3) : 'None' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">{{ $employee->other_gov_id_type ?? 'Other Government ID' }}</span>
                        <span class="detail-val gov-id-field" data-real="{{ $employee->other_gov_id_number ?? 'None' }}" data-masked="{{ $employee->other_gov_id_number ? '••••••' . substr($employee->other_gov_id_number, -3) : 'None' }}">
                            {{ $employee->other_gov_id_number ? '••••••' . substr($employee->other_gov_id_number, -3) : 'None' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 6: DOCUMENTS -->
    <!-- ========================================================================= -->
    <div id="tab-documents" class="hr-tab-pane">
        @php
            $expiringDocs = $employee->documents->filter(function($doc) {
                return $doc->expiry_date && \Carbon\Carbon::parse($doc->expiry_date)->isFuture() && \Carbon\Carbon::parse($doc->expiry_date)->diffInDays(now()) <= 30;
            });
        @endphp

        <!-- Expiration Warning Alert if Any -->
        @if($expiringDocs->count() > 0)
            <div style="background: #fffbeb; border: 1.5px solid #f59e0b; border-radius: 10px; padding: 12px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; color: #92400e;">
                <i class="ph ph-warning" style="font-size: 22px; color: #d97706; flex-shrink: 0;"></i>
                <div>
                    <div style="font-weight: 700; font-size: 13.5px;">Document Expiration Warning</div>
                    <div style="font-size: 12.5px;">
                        {{ $expiringDocs->count() }} document(s) expiring within the next 30 days: 
                        <strong>{{ $expiringDocs->pluck('document_name')->join(', ') }}</strong>. Please request renewals promptly.
                    </div>
                </div>
            </div>
        @endif

        <div class="hr-card">
            <div class="hr-card-header">
                <div>
                    <span class="hr-card-title"><i class="ph ph-folder-notch-open"></i> Employee 201 File Documents</span>
                    <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Documents are auto-linked exclusively to this employee</span>
                </div>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openModal('uploadDocModal')">
                    <i class="ph ph-upload-simple"></i> Upload Document
                </button>
            </div>

            <!-- Category Pills Filter Bar -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; padding: 4px 0;">
                <button type="button" class="hr-pill-btn active" onclick="filterDocCategory('all', this)">All</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Personal', this)">Personal</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Employment', this)">Employment</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Education', this)">Education</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Government', this)">Government</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Medical', this)">Medical</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Training', this)">Training</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Performance', this)">Performance</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Employee Relations', this)">Employee Relations</button>
                <button type="button" class="hr-pill-btn" onclick="filterDocCategory('Separation', this)">Separation</button>
            </div>

            <!-- Documents Table -->
            <div class="hr-table-wrapper" style="overflow-x: auto;">
                <table class="hr-table" id="empDocumentsTable">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Category</th>
                            <th>Date Uploaded</th>
                            <th>Expiration Date</th>
                            <th>Status</th>
                            <th>Uploaded By</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $doc)
                            @php
                                $isExpiring = $doc->expiry_date && \Carbon\Carbon::parse($doc->expiry_date)->isFuture() && \Carbon\Carbon::parse($doc->expiry_date)->diffInDays(now()) <= 30;
                                $isExpired = $doc->expiry_date && \Carbon\Carbon::parse($doc->expiry_date)->isPast();
                            @endphp
                            <tr class="doc-row" data-category="{{ $doc->category }}">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="ph ph-file-text" style="color: #7c3aed; font-size: 20px;"></i>
                                        <div>
                                            <div style="font-weight: 600; color: #0f172a;">{{ $doc->document_name }}</div>
                                            <div style="font-size: 11px; color: #64748b;">{{ $doc->file_size_formatted ?? 'PDF / Document' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="hr-badge hr-badge-neutral">{{ $doc->category }}</span>
                                </td>
                                <td>{{ $doc->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($doc->expiry_date)
                                        <span style="{{ $isExpired ? 'color: #dc2626; font-weight: 700;' : ($isExpiring ? 'color: #d97706; font-weight: 600;' : '') }}">
                                            {{ \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') }}
                                            @if($isExpired)
                                                (Expired)
                                            @elseif($isExpiring)
                                                <i class="ph ph-warning" title="Expiring soon"></i>
                                            @endif
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">No Expiration</span>
                                    @endif
                                </td>
                                <td>
                                    @if($doc->status === 'Verified')
                                        <span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Verified</span>
                                    @elseif($doc->status === 'Pending')
                                        <span class="hr-badge hr-badge-warning">Pending</span>
                                    @elseif($doc->status === 'Expired')
                                        <span class="hr-badge hr-badge-danger">Expired</span>
                                    @else
                                        <span class="hr-badge hr-badge-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ $doc->uploadedBy?->full_name ?? 'HR System' }}</td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <!-- View / Preview -->
                                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openDocPreview('{{ $doc->file_url ?? asset('storage/' . $doc->file_path) }}', '{{ addslashes($doc->document_name) }}')" title="Preview Document">
                                            <i class="ph ph-eye"></i>
                                        </button>

                                        <!-- Download -->
                                        <a href="{{ route('hr.people.documents.download', $doc->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="Download Document">
                                            <i class="ph ph-download-simple"></i>
                                        </a>

                                        <!-- Verify -->
                                        @if($doc->status !== 'Verified')
                                            <form method="POST" action="{{ route('hr.people.documents.verify', $doc->id) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="hr-btn hr-btn-success hr-btn-sm" title="Verify Document">
                                                    <i class="ph ph-seal-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Replace -->
                                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openReplaceDocModal({{ $doc->id }}, '{{ addslashes($doc->document_name) }}')" title="Replace File">
                                            <i class="ph ph-arrow-counter-clockwise"></i>
                                        </button>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('hr.people.documents.destroy', $doc->id) }}" onsubmit="return confirm('Delete document {{ $doc->document_name }}?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: #94a3b8; padding: 36px;">
                                    <i class="ph ph-folder-open" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                    No documents attached to this employee yet. Click 'Upload Document' to add.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 7: ATTENDANCE & LEAVE -->
    <!-- ========================================================================= -->
    <div id="tab-attendance-leave" class="hr-tab-pane">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Attendance Section -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-fingerprint"></i> Attendance Summary</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Employee-specific punch summary and metrics</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('hr.attendance.timekeeping') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                            <i class="ph ph-clock"></i>
                            <span>View Attendance</span>
                        </a>
                        <a href="{{ route('hr.reports.index') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                            <i class="ph ph-file-text"></i>
                            <span>View DTR</span>
                        </a>
                    </div>
                </div>

                <!-- 6 Metric Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 18px;">
                    <div class="hr-stat-card" style="padding: 10px 14px;">
                        <div class="hr-stat-content">
                            <span class="hr-stat-label">Attendance Rate</span>
                            <div class="hr-stat-value" style="font-size: 20px; color: #059669;">{{ $attendanceSummary['rate'] }}</div>
                        </div>
                    </div>
                    <div class="hr-stat-card" style="padding: 10px 14px;">
                        <div class="hr-stat-content">
                            <span class="hr-stat-label">Days Present</span>
                            <div class="hr-stat-value" style="font-size: 20px; color: #2563eb;">{{ $attendanceSummary['present'] }}</div>
                        </div>
                    </div>
                    <div class="hr-stat-card" style="padding: 10px 14px;">
                        <div class="hr-stat-content">
                            <span class="hr-stat-label">Days Absent</span>
                            <div class="hr-stat-value" style="font-size: 20px; color: #dc2626;">{{ $attendanceSummary['absent'] }}</div>
                        </div>
                    </div>
                    <div class="hr-stat-card" style="padding: 10px 14px;">
                        <div class="hr-stat-content">
                            <span class="hr-stat-label">Late Instances</span>
                            <div class="hr-stat-value" style="font-size: 20px; color: #d97706;">{{ $attendanceSummary['late'] }}</div>
                        </div>
                    </div>
                    <div class="hr-stat-card" style="padding: 10px 14px;">
                        <div class="hr-stat-content">
                            <span class="hr-stat-label">Undertime Hrs</span>
                            <div class="hr-stat-value" style="font-size: 20px; color: #ea580c;">{{ $attendanceSummary['undertime'] }}h</div>
                        </div>
                    </div>
                    <div class="hr-stat-card" style="padding: 10px 14px;">
                        <div class="hr-stat-content">
                            <span class="hr-stat-label">Overtime Hrs</span>
                            <div class="hr-stat-value" style="font-size: 20px; color: #7c3aed;">{{ $attendanceSummary['overtime'] }}h</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance Table -->
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Recent Time Logs</div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Break Out</th>
                                <th>Break In</th>
                                <th>Time Out</th>
                                <th>Total Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->attendanceRecords as $att)
                                <tr>
                                    <td><strong>{{ \Carbon\Carbon::parse($att->date)->format('M d, Y (D)') }}</strong></td>
                                    <td>{{ $att->time_in ? \Carbon\Carbon::parse($att->time_in)->format('h:i A') : '—' }}</td>
                                    <td>{{ $att->break_out ? \Carbon\Carbon::parse($att->break_out)->format('h:i A') : '—' }}</td>
                                    <td>{{ $att->break_in ? \Carbon\Carbon::parse($att->break_in)->format('h:i A') : '—' }}</td>
                                    <td>{{ $att->time_out ? \Carbon\Carbon::parse($att->time_out)->format('h:i A') : '—' }}</td>
                                    <td>{{ number_format($att->total_hours, 2) }} hrs</td>
                                    <td>
                                        @if($att->status === 'Present')
                                            <span class="hr-badge hr-badge-success">Present</span>
                                        @elseif($att->status === 'Late')
                                            <span class="hr-badge hr-badge-warning">Late</span>
                                        @elseif($att->status === 'Absent')
                                            <span class="hr-badge hr-badge-danger">Absent</span>
                                        @else
                                            <span class="hr-badge hr-badge-neutral">{{ $att->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 24px;">
                                        No recent attendance records for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leave Section -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-calendar-blank"></i> Leave Summary</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Available statutory and company leave balances</span>
                    </div>
                    <a href="{{ route('hr.leave.requests') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                        <i class="ph ph-calendar-plus"></i>
                        <span>View Leave History</span>
                    </a>
                </div>

                <!-- Leave Credits Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 18px;">
                    @forelse($employee->leaveBalances as $lb)
                        <div class="hr-stat-card" style="padding: 12px 16px;">
                            <div class="hr-stat-content">
                                <span class="hr-stat-label">{{ $lb->leaveType?->name ?? 'Leave' }}</span>
                                <div class="hr-stat-value" style="font-size: 22px; color: #7c3aed;">
                                    {{ number_format($lb->remaining_credits, 1) }}
                                </div>
                                <span class="hr-stat-sub">of {{ number_format($lb->allocated_credits, 1) }} credits remaining</span>
                            </div>
                        </div>
                    @empty
                        <div class="hr-stat-card" style="padding: 12px 16px;">
                            <div class="hr-stat-content">
                                <span class="hr-stat-label">Vacation Leave</span>
                                <div class="hr-stat-value" style="font-size: 22px; color: #7c3aed;">5.0</div>
                                <span class="hr-stat-sub">Standard SMB credits</span>
                            </div>
                        </div>
                        <div class="hr-stat-card" style="padding: 12px 16px;">
                            <div class="hr-stat-content">
                                <span class="hr-stat-label">Sick Leave</span>
                                <div class="hr-stat-value" style="font-size: 22px; color: #7c3aed;">5.0</div>
                                <span class="hr-stat-sub">Standard SMB credits</span>
                            </div>
                        </div>
                        <div class="hr-stat-card" style="padding: 12px 16px;">
                            <div class="hr-stat-content">
                                <span class="hr-stat-label">Service Incentive Leave (SIL)</span>
                                <div class="hr-stat-value" style="font-size: 22px; color: #7c3aed;">5.0</div>
                                <span class="hr-stat-sub">DOLE statutory</span>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- Recent Leave Applications Table -->
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Recent Leave Applications</div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Leave Type</th>
                                <th>Date / Range</th>
                                <th>Number of Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->leaveRequests as $lr)
                                <tr>
                                    <td><strong>{{ $lr->leaveType?->name ?? 'General Leave' }}</strong></td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($lr->start_date)->format('M d, Y') }}
                                        @if($lr->end_date && $lr->end_date !== $lr->start_date)
                                            &rarr; {{ \Carbon\Carbon::parse($lr->end_date)->format('M d, Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $lr->number_of_days }} day(s)</td>
                                    <td>{{ $lr->reason ?? '—' }}</td>
                                    <td>
                                        @if($lr->status === 'Approved')
                                            <span class="hr-badge hr-badge-success">Approved</span>
                                        @elseif($lr->status === 'Pending')
                                            <span class="hr-badge hr-badge-warning">Pending Review</span>
                                        @else
                                            <span class="hr-badge hr-badge-danger">{{ $lr->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 24px;">
                                        No recent leave requests filed.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 8: PAYROLL & PERFORMANCE -->
    <!-- ========================================================================= -->
    <div id="tab-payroll-performance" class="hr-tab-pane">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- 1. Payroll Section -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-receipt"></i> Payroll Summary & History</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Compensation breakdown, cut-off records, and payslips</span>
                    </div>
                </div>

                <!-- Payroll Summary Cards -->
                <div class="hr-detail-grid" style="margin-bottom: 20px;">
                    <div class="detail-item">
                        <span class="detail-label">Current Basic Salary</span>
                        <span class="detail-val" style="color: #7c3aed; font-size: 16px; font-weight: 700;">
                            @if($canViewSensitive)
                                ₱{{ number_format($employee->basic_salary, 2) }}
                            @else
                                ₱••••••
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Pay Frequency</span>
                        <span class="detail-val">{{ $employee->pay_frequency ?? 'Semi-monthly' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payroll Status</span>
                        <span class="detail-val"><span class="hr-badge hr-badge-success">Active on Payroll</span></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Current Pay Period</span>
                        <span class="detail-val">{{ date('M 01') }} &ndash; {{ date('M 15, Y') }}</span>
                    </div>
                </div>

                <!-- Payroll History Table -->
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Recent Payroll Registers & Payslips</div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Payroll Period</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                                <th style="text-align: right;">Payslip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->payrollRecords as $pr)
                                <tr>
                                    <td>
                                        <strong>
                                            {{ $pr->payrollPeriod ? \Carbon\Carbon::parse($pr->payrollPeriod->start_date)->format('M d') . ' - ' . \Carbon\Carbon::parse($pr->payrollPeriod->end_date)->format('M d, Y') : 'Cut-off' }}
                                        </strong>
                                    </td>
                                    <td>₱{{ number_format($pr->gross_pay, 2) }}</td>
                                    <td style="color: #dc2626;">-₱{{ number_format($pr->total_deductions, 2) }}</td>
                                    <td><strong style="color: #059669;">₱{{ number_format($pr->net_pay, 2) }}</strong></td>
                                    <td><span class="hr-badge hr-badge-success">Paid</span></td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('hr.payroll.payslips') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                                            <i class="ph ph-file-text"></i>
                                            <span>View Payslip</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                        No finalized payroll records available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Performance Section -->
            <div class="hr-card">
                <div class="hr-card-header">
                    <div>
                        <span class="hr-card-title"><i class="ph ph-star"></i> Performance Reviews & Appraisals</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Evaluation ratings, competencies, and review cycles</span>
                    </div>
                    <a href="{{ route('hr.performance.periods') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                        <i class="ph ph-chart-polar"></i>
                        <span>View Performance History</span>
                    </a>
                </div>

                @php
                    $latestEval = $employee->evaluations->first();
                @endphp

                @if($latestEval)
                    <!-- Latest Review Card -->
                    <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <span class="hr-badge hr-badge-purple" style="font-size: 11px;">Latest Appraisal Cycle</span>
                                <h4 style="margin: 4px 0 0 0; font-size: 15px; font-weight: 700; color: #0f172a;">
                                    {{ $latestEval->period?->name ?? 'Annual Appraisal Cycle' }}
                                </h4>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 24px; font-weight: 800; color: #7c3aed;">
                                    {{ number_format($latestEval->overall_rating ?? 4.5, 1) }} / 5.0
                                </div>
                                <span class="hr-badge hr-badge-success" style="font-size: 10.5px;">{{ $latestEval->status ?? 'Completed' }}</span>
                            </div>
                        </div>

                        <div class="hr-detail-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="detail-item">
                                <span class="detail-label">Reviewer</span>
                                <span class="detail-val">{{ $latestEval->evaluator?->full_name ?? $employee->supervisor?->full_name ?? 'Supervising Manager' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Review Period</span>
                                <span class="detail-val">{{ $latestEval->period?->start_date ? \Carbon\Carbon::parse($latestEval->period->start_date)->format('M Y') . ' - ' . \Carbon\Carbon::parse($latestEval->period->end_date)->format('M Y') : 'Current Year' }}</span>
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <span class="detail-label">Core Strengths</span>
                                <span class="detail-val">{{ $latestEval->strengths ?? 'Strong attention to detail, punctuality, and excellent restaurant service compliance.' }}</span>
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <span class="detail-label">Development Areas</span>
                                <span class="detail-val">{{ $latestEval->development_areas ?? 'Cross-training in shift scheduling and inventory tracking.' }}</span>
                            </div>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <span class="detail-label">Manager Comments</span>
                                <span class="detail-val" style="font-style: italic; color: #475569;">"{{ $latestEval->comments ?? 'Consistently meets performance standards and demonstrates good team cooperation.' }}"</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 20px;">
                        <i class="ph ph-star-half" style="font-size: 32px; color: #a855f7; display: block; margin-bottom: 6px;"></i>
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #0f172a;">No Completed Appraisals On Record</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">The employee is scheduled for the upcoming semi-annual performance appraisal evaluation cycle.</p>
                    </div>
                @endif

                <!-- Previous Performance Reviews Table -->
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Appraisal History</div>
                <div class="hr-table-wrapper" style="overflow-x: auto;">
                    <table class="hr-table">
                        <thead>
                            <tr>
                                <th>Review Period</th>
                                <th>Reviewer</th>
                                <th>Overall Rating</th>
                                <th>Status</th>
                                <th>Date Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->evaluations as $ev)
                                <tr>
                                    <td><strong>{{ $ev->period?->name ?? 'Appraisal Cycle' }}</strong></td>
                                    <td>{{ $ev->evaluator?->full_name ?? 'Manager' }}</td>
                                    <td><strong style="color: #7c3aed;">{{ number_format($ev->overall_rating ?? 0, 1) }} / 5.0</strong></td>
                                    <td><span class="hr-badge hr-badge-success">{{ $ev->status }}</span></td>
                                    <td>{{ $ev->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">
                                        No historical performance reviews available.
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

<!-- ========================================================================= -->
<!-- MODALS FOR PROFILE ACTIONS & 201 FILE MANAGEMENT -->
<!-- ========================================================================= -->

<!-- 1. Edit Employee Modal -->
<div id="editEmployeeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 800px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-pencil"></i> Edit Employee Profile</span>
            <button class="icon-btn" onclick="closeModal('editEmployeeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.update', $employee->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="hr-modal-body" style="max-height: 75vh; overflow-y: auto;">
                <h4 class="modal-sec-title">1. Basic Information</h4>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">First Name *</label>
                        <input type="text" name="first_name" class="hr-input" value="{{ $employee->first_name }}" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="hr-input" value="{{ $employee->middle_name }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Last Name *</label>
                        <input type="text" name="last_name" class="hr-input" value="{{ $employee->last_name }}" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Suffix</label>
                        <input type="text" name="suffix" class="hr-input" value="{{ $employee->suffix }}" placeholder="Jr., III">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Preferred Name / Nickname</label>
                        <input type="text" name="preferred_name" class="hr-input" value="{{ $employee->preferred_name }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="hr-input" value="{{ $employee->date_of_birth }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Place of Birth</label>
                        <input type="text" name="birth_place" class="hr-input" value="{{ $employee->birth_place }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Sex / Gender</label>
                        <select name="gender" class="hr-select">
                            <option value="Male" {{ $employee->gender === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ $employee->gender === 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ $employee->gender === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Civil Status</label>
                        <select name="civil_status" class="hr-select">
                            @foreach(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $cs)
                                <option value="{{ $cs }}" {{ $employee->civil_status === $cs ? 'selected' : '' }}>{{ $cs }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Nationality</label>
                        <input type="text" name="nationality" class="hr-input" value="{{ $employee->nationality ?? 'Filipino' }}">
                    </div>
                </div>

                <h4 class="modal-sec-title">2. Contact & Addresses</h4>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Personal Email</label>
                        <input type="email" name="personal_email" class="hr-input" value="{{ $employee->personal_email ?? $employee->email }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Company Email</label>
                        <input type="email" name="company_email" class="hr-input" value="{{ $employee->company_email ?? $employee->email }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="hr-input" value="{{ $employee->mobile_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Telephone Number</label>
                        <input type="text" name="telephone_number" class="hr-input" value="{{ $employee->telephone_number }}">
                    </div>
                    <div class="hr-form-group" style="grid-column: 1 / -1;">
                        <label class="hr-form-label">Current Address</label>
                        <input type="text" name="address" id="editEmpAddress" class="hr-input" value="{{ $employee->address }}">
                    </div>
                    <div class="hr-form-group" style="grid-column: 1 / -1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <label class="hr-form-label" style="margin-bottom: 0;">Permanent Address</label>
                            <label style="font-size: 12px; color: #7c3aed; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <input type="checkbox" id="sameAddressCb" onchange="if(this.checked){ document.getElementById('editEmpPermAddress').value = document.getElementById('editEmpAddress').value; }">
                                Same as Current Address
                            </label>
                        </div>
                        <input type="text" name="permanent_address" id="editEmpPermAddress" class="hr-input" value="{{ $employee->permanent_address ?? $employee->address }}">
                    </div>
                </div>

                <h4 class="modal-sec-title">3. Employment & Assignments</h4>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Branch *</label>
                        <select name="branch_id" class="hr-select" required>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $employee->branch_id === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Department *</label>
                        <select name="department_id" class="hr-select" required>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ $employee->department_id === $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Position *</label>
                        <select name="position_id" class="hr-select" required>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}" {{ $employee->position_id === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Job Level</label>
                        <input type="text" name="job_level" class="hr-input" value="{{ $employee->job_level ?? 'Staff' }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Supervisor</label>
                        <select name="supervisor_id" class="hr-select">
                            <option value="">-- No Direct Supervisor --</option>
                            @foreach($supervisors as $s)
                                <option value="{{ $s->id }}" {{ $employee->supervisor_id === $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" class="hr-select" required>
                            @foreach(['Regular', 'Probationary', 'Contractual', 'Part-time', 'Seasonal', 'Intern / OJT'] as $et)
                                <option value="{{ $et }}" {{ $employee->employment_type === $et ? 'selected' : '' }}>{{ $et }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Status *</label>
                        <select name="employment_status" class="hr-select" required>
                            @foreach(['Active', 'Probationary', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Retired'] as $es)
                                <option value="{{ $es }}" {{ $employee->employment_status === $es ? 'selected' : '' }}>{{ $es }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Work Location</label>
                        <input type="text" name="work_location" class="hr-input" value="{{ $employee->work_location }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Work Schedule</label>
                        <input type="text" name="work_schedule" class="hr-input" value="{{ $employee->work_schedule }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date Hired *</label>
                        <input type="date" name="date_hired" class="hr-input" value="{{ $employee->date_hired }}" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Regularization Date</label>
                        <input type="date" name="date_of_regularization" class="hr-input" value="{{ $employee->date_of_regularization }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Basic Salary (₱) *</label>
                        <input type="number" step="0.01" name="basic_salary" class="hr-input" value="{{ $employee->basic_salary }}" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency</label>
                        <select name="pay_frequency" class="hr-select">
                            <option value="Semi-monthly" {{ $employee->pay_frequency === 'Semi-monthly' ? 'selected' : '' }}>Semi-monthly</option>
                            <option value="Monthly" {{ $employee->pay_frequency === 'Monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="Weekly" {{ $employee->pay_frequency === 'Weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                    </div>
                </div>

                <h4 class="modal-sec-title">4. Statutory & Government IDs</h4>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">SSS Number</label>
                        <input type="text" name="sss_number" class="hr-input" value="{{ $employee->sss_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">PhilHealth Number</label>
                        <input type="text" name="philhealth_number" class="hr-input" value="{{ $employee->philhealth_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pag-IBIG Number</label>
                        <input type="text" name="pagibig_number" class="hr-input" value="{{ $employee->pagibig_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">TIN Number</label>
                        <input type="text" name="tin_number" class="hr-input" value="{{ $employee->tin_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">RDO Code</label>
                        <input type="text" name="rdo_code" class="hr-input" value="{{ $employee->rdo_code }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">PhilSys / National ID</label>
                        <input type="text" name="philsys_id" class="hr-input" value="{{ $employee->philsys_id }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Passport Number</label>
                        <input type="text" name="passport_number" class="hr-input" value="{{ $employee->passport_number }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Driver's License</label>
                        <input type="text" name="driver_license" class="hr-input" value="{{ $employee->driver_license }}">
                    </div>
                </div>

                <h4 class="modal-sec-title">5. Profile Photo</h4>
                <div class="hr-form-group">
                    <label class="hr-form-label">Upload New Photo</label>
                    <input type="file" name="photo" class="hr-input" accept="image/*">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editEmployeeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Change Position Modal -->
<div id="changePositionModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-briefcase"></i> Change Position</span>
            <button class="icon-btn" onclick="closeModal('changePositionModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.change-position', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Current Position</label>
                    <input type="text" class="hr-input" readonly value="{{ $employee->position?->name ?? 'None' }}" style="background: #f8fafc;">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">New Position <span class="text-danger">*</span></label>
                    <select name="position_id" class="hr-select" required>
                        <option value="">-- Select Position --</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}" {{ $employee->position_id === $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Reason / Justification</label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Promotion, Lateral Transfer"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('changePositionModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update Position</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Transfer Department / Branch Modal -->
<div id="transferModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-arrows-left-right"></i> Transfer Employee</span>
            <button class="icon-btn" onclick="closeModal('transferModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.transfer', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Current Placement</label>
                    <input type="text" class="hr-input" readonly value="{{ $employee->department?->name ?? 'Dept' }} &bull; {{ $employee->branch?->name ?? 'Branch' }}" style="background: #f8fafc;">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">New Branch</label>
                    <select name="branch_id" class="hr-select">
                        <option value="">-- Keep Current ({{ $employee->branch?->name ?? 'None' }}) --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">New Department</label>
                    <select name="department_id" class="hr-select">
                        <option value="">-- Keep Current ({{ $employee->department?->name ?? 'None' }}) --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Reason</label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Branch rebalancing, department reorganization"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('transferModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Apply Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Change Salary Modal -->
<div id="changeSalaryModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-currency-dollar"></i> Adjust Salary</span>
            <button class="icon-btn" onclick="closeModal('changeSalaryModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.change-salary', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Current Basic Salary</label>
                    <input type="text" class="hr-input" readonly value="₱{{ number_format($employee->basic_salary, 2) }}" style="background: #f8fafc; font-weight: 700; color: #7c3aed;">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">New Basic Salary (₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="new_salary" class="hr-input" placeholder="0.00" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Adjustment Type <span class="text-danger">*</span></label>
                    <select name="adjustment_type" class="hr-select" required>
                        <option value="Merit Increase">Merit Increase</option>
                        <option value="Promotion">Promotion</option>
                        <option value="Regularization Increment">Regularization Increment</option>
                        <option value="Annual Review">Annual Review</option>
                        <option value="Cost of Living Adjustment (COLA)">Cost of Living Adjustment (COLA)</option>
                        <option value="Other">Other Adjustment</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Reason / Justification</label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Completed probationary evaluation, annual performance score"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('changeSalaryModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Confirm Adjustment</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Change Status Modal -->
<div id="changeStatusModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-arrows-clockwise"></i> Change Employment Status</span>
            <button class="icon-btn" onclick="closeModal('changeStatusModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.change-status', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Current Status</label>
                    <input type="text" class="hr-input" readonly value="{{ $employee->employment_status }}" style="background: #f8fafc; font-weight: 600;">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">New Status <span class="text-danger">*</span></label>
                    <select name="employment_status" class="hr-select" required>
                        <option value="Active" {{ $employee->employment_status === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Probationary" {{ $employee->employment_status === 'Probationary' ? 'selected' : '' }}>Probationary</option>
                        <option value="On Leave" {{ $employee->employment_status === 'On Leave' ? 'selected' : '' }}>On Leave</option>
                        <option value="Suspended" {{ $employee->employment_status === 'Suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="Resigned" {{ $employee->employment_status === 'Resigned' ? 'selected' : '' }}>Resigned</option>
                        <option value="Terminated" {{ $employee->employment_status === 'Terminated' ? 'selected' : '' }}>Terminated</option>
                        <option value="Retired" {{ $employee->employment_status === 'Retired' ? 'selected' : '' }}>Retired</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Effective Date <span class="text-danger">*</span></label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Reason / Notes</label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Regularization, Medical leave, Resignation letter received"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('changeStatusModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Process Separation Modal -->
<div id="processSeparationModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 500px;">
        <div class="hr-modal-header" style="border-bottom-color: #fee2e2;">
            <span class="hr-modal-title" style="color: #dc2626;"><i class="ph ph-user-minus"></i> Process Employee Separation</span>
            <button class="icon-btn" onclick="closeModal('processSeparationModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.process-separation', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 10px 14px; font-size: 12.5px; color: #991b1b; margin-bottom: 14px;">
                    This sensitive action updates employee status, sets separation date, and logs an audit record.
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Separation Type <span class="text-danger">*</span></label>
                    <select name="separation_type" class="hr-select" required>
                        <option value="Resigned">Voluntary Resignation</option>
                        <option value="Terminated">Authorized Termination / Separation</option>
                        <option value="Retired">Retirement</option>
                        <option value="End of Contract">End of Contract</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Separation / Last Day Date <span class="text-danger">*</span></label>
                    <input type="date" name="separation_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Clearance & Exit Notes</label>
                    <textarea name="remarks" class="hr-input" rows="2" placeholder="e.g. Clearance processed, equipment surrendered"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('processSeparationModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-danger">Confirm Separation</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. Archive Employee Modal -->
<div id="archiveEmployeeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 450px;">
        <div class="hr-modal-header" style="border-bottom-color: #fee2e2;">
            <span class="hr-modal-title" style="color: #dc2626;"><i class="ph ph-archive"></i> Archive Employee</span>
            <button class="icon-btn" onclick="closeModal('archiveEmployeeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.archive', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <p style="font-size: 13.5px; color: #334155; margin: 0 0 12px 0;">
                    Are you sure you want to archive <strong>{{ $employee->full_name }}</strong>? The employee record will be marked inactive and moved to the archive index.
                </p>
                <div class="hr-form-group">
                    <label class="hr-form-label">Reason for Archiving</label>
                    <input type="text" name="reason" class="hr-input" placeholder="e.g. End of 201 Retention Cycle">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('archiveEmployeeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-danger">Archive Record</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. Generate COE Modal -->
<div id="generateCoeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-certificate"></i> Generate Certificate of Employment</span>
            <button class="icon-btn" onclick="closeModal('generateCoeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="GET" action="{{ route('hr.people.employees.coe', $employee->id) }}" target="_blank">
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee</label>
                    <input type="text" class="hr-input" readonly value="{{ $employee->full_name }}" style="background: #f8fafc; font-weight: 600;">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Stated Purpose <span class="text-danger">*</span></label>
                    <input type="text" name="purpose" class="hr-input" value="Bank Loan & Visa Application / Employment Verification" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Authorized Signatory Name</label>
                    <input type="text" name="signatory" class="hr-input" value="{{ Auth::user()->full_name ?? 'HR Administration' }}">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Signatory Job Title</label>
                    <input type="text" name="signatory_title" class="hr-input" value="Human Resources Director">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('generateCoeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" onclick="closeModal('generateCoeModal')">Preview & Print COE</button>
            </div>
        </form>
    </div>
</div>

<!-- 9. Add Family Member Modal -->
<div id="addFamilyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-user-plus"></i> Add Family Member / Dependent</span>
            <button class="icon-btn" onclick="closeModal('addFamilyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.family.store', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="hr-input" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Relationship <span class="text-danger">*</span></label>
                    <select name="relationship" class="hr-select" required>
                        <option value="Spouse">Spouse</option>
                        <option value="Child">Child / Dependent</option>
                        <option value="Parent">Parent (Father / Mother)</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Date of Birth</label>
                    <input type="date" name="birth_date" class="hr-input">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Occupation</label>
                    <input type="text" name="occupation" class="hr-input">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="hr-input">
                </div>
                <div style="display: flex; gap: 20px; margin-top: 14px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_dependent" value="1">
                        <span>Tax / Statutory Dependent</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_beneficiary" value="1">
                        <span>Insurance Beneficiary</span>
                    </label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addFamilyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Add Member</button>
            </div>
        </form>
    </div>
</div>

<!-- 10. Add Emergency Contact Modal -->
<div id="addEmergencyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-phone-plus"></i> Add Emergency Contact</span>
            <button class="icon-btn" onclick="closeModal('addEmergencyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.emergency.store', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Contact Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="hr-input" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Relationship <span class="text-danger">*</span></label>
                    <input type="text" name="relationship" class="hr-input" placeholder="e.g. Spouse, Mother, Sibling" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="mobile_number" class="hr-input" placeholder="0918xxxxxxx" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Address</label>
                    <input type="text" name="address" class="hr-input" placeholder="Residential address">
                </div>
                <div style="margin-top: 12px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_primary" value="1">
                        <span>Set as Primary Emergency Contact</span>
                    </label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addEmergencyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Contact</button>
            </div>
        </form>
    </div>
</div>

<!-- 11. Add Education Modal -->
<div id="addEducationModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-graduation-cap"></i> Add Education Record</span>
            <button class="icon-btn" onclick="closeModal('addEducationModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.education.store', $employee->id) }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Education Level <span class="text-danger">*</span></label>
                    <select name="level" class="hr-select" required>
                        <option value="College / Degree">College / Bachelor's Degree</option>
                        <option value="Post-Graduate / Master">Post-Graduate / Master</option>
                        <option value="Vocational / Technical">Vocational / Technical / TESDA</option>
                        <option value="Senior High School">Senior High School</option>
                        <option value="Junior High School">Junior High School</option>
                        <option value="Elementary">Elementary</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">School / Institution <span class="text-danger">*</span></label>
                    <input type="text" name="school" class="hr-input" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Course / Degree / Major</label>
                    <input type="text" name="course" class="hr-input" placeholder="e.g. BS Hospitality Management">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Year Started</label>
                        <input type="text" name="year_started" class="hr-input" placeholder="2018">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Year Completed</label>
                        <input type="text" name="year_completed" class="hr-input" placeholder="2022">
                    </div>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Graduation Status</label>
                    <select name="status" class="hr-select">
                        <option value="Graduated">Graduated</option>
                        <option value="Undergraduate">Undergraduate / In Progress</option>
                        <option value="Units Earned">Units Earned</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Honors / Academic Citations</label>
                    <input type="text" name="honors" class="hr-input" placeholder="e.g. Cum Laude, Dean's Lister">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addEducationModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Education</button>
            </div>
        </form>
    </div>
</div>

<!-- 12. Upload Document Modal (Auto-bound to this employee) -->
<div id="uploadDocModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 540px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-upload-simple"></i> Upload 201 Document</span>
            <button class="icon-btn" onclick="closeModal('uploadDocModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.documents.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Document Title / Name <span class="text-danger">*</span></label>
                    <input type="text" name="document_name" class="hr-input" placeholder="e.g. Signed Employment Contract, SSS E-1, Medical Certificate" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Document Category <span class="text-danger">*</span></label>
                    <select name="category" class="hr-select" required>
                        <option value="Personal">Personal (PSA Birth Cert, Marriage Cert, NBI / Police Clearance)</option>
                        <option value="Employment">Employment (Contract, Job Offer, Appointment Letter, Memo)</option>
                        <option value="Education">Education (Diploma, Transcript of Records, TESDA NC II)</option>
                        <option value="Government">Government (SSS, PhilHealth, Pag-IBIG, BIR Form 1902/2316)</option>
                        <option value="Medical">Medical (Health Card, Pre-Employment Medical Exam, Drug Test)</option>
                        <option value="Training">Training & Certifications (Food Safety, Hygiene, Barista)</option>
                        <option value="Performance">Performance (Appraisal Form, Commendation, KPI Signoff)</option>
                        <option value="Employee Relations">Employee Relations (NTE, Incident Report, Written Warning)</option>
                        <option value="Separation">Separation (Resignation Letter, Clearance, Exit Interview, COE)</option>
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Expiration Date (Optional)</label>
                    <input type="date" name="expiry_date" class="hr-input">
                    <span style="font-size: 11px; color: #64748b; margin-top: 2px; display: block;">For medical clearances, health cards, or periodic certifications</span>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Attach File (PDF, DOCX, JPG, PNG) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="hr-input" required>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('uploadDocModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Upload & Attach</button>
            </div>
        </form>
    </div>
</div>

<!-- 13. Replace Document Modal -->
<div id="replaceDocModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-arrow-counter-clockwise"></i> Replace Document File</span>
            <button class="icon-btn" onclick="closeModal('replaceDocModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="replaceDocForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Replacing Document</label>
                    <input type="text" id="replaceDocName" class="hr-input" readonly style="background: #f8fafc; font-weight: 600;">
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">New Document File <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="hr-input" required>
                </div>
                <div class="hr-form-group" style="margin-top: 12px;">
                    <label class="hr-form-label">Updated Expiration Date</label>
                    <input type="date" name="expiry_date" class="hr-input">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('replaceDocModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Upload Replacement</button>
            </div>
        </form>
    </div>
</div>

<!-- 14. Document Preview Modal -->
<div id="previewDocModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 840px; height: 85vh; display: flex; flex-direction: column;">
        <div class="hr-modal-header">
            <span class="hr-modal-title" id="previewDocTitle"><i class="ph ph-file-text"></i> Document Preview</span>
            <button class="icon-btn" onclick="closeModal('previewDocModal')"><i class="ph ph-x"></i></button>
        </div>
        <div class="hr-modal-body" style="flex: 1; padding: 0; overflow: hidden; background: #1e293b; display: flex; align-items: center; justify-content: center;">
            <iframe id="previewDocFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>

<style>
/* Clean SaaS Profile Header & Card Styles */
.hr-emp-profile-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    flex-wrap: wrap;
}
.hr-emp-header-left {
    display: flex;
    align-items: center;
    gap: 20px;
    min-width: 0;
}
.hr-emp-header-avatar {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
}
.hr-emp-header-initials {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 800;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
    flex-shrink: 0;
}
.hr-emp-name {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin: 0;
}
.hr-emp-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #64748b;
    margin-top: 5px;
    flex-wrap: wrap;
}
.hr-emp-meta i {
    color: #7c3aed;
}
.meta-dot {
    color: #cbd5e1;
}
.hr-emp-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* 8 Compact Horizontal Tabs Bar */
.hr-profile-nav-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 6px;
    overflow-x: auto;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.hr-profile-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: max-content;
}
.hr-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.hr-tab-btn i {
    font-size: 15px;
    color: #64748b;
}
.hr-tab-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.hr-tab-btn.active {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(124, 58, 237, 0.25);
}
.hr-tab-btn.active i {
    color: #ffffff;
}
.hr-tab-badge {
    background: rgba(255, 255, 255, 0.25);
    color: inherit;
    font-size: 10.5px;
    padding: 1px 6px;
    border-radius: 10px;
    font-weight: 700;
}

/* Tab Panels */
.hr-tab-pane {
    display: none;
}
.hr-tab-pane.active {
    display: block;
    animation: fadeIn 0.15s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(3px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Shared Card Details */
.hr-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.hr-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}
.hr-card-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.hr-card-title i {
    color: #7c3aed;
    font-size: 18px;
}
.hr-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px 18px;
}
.detail-item {
    display: flex;
    flex-direction: column;
}
.detail-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 3px;
}
.detail-val {
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
}
.detail-val.monospace {
    font-family: monospace;
    color: #7c3aed;
    font-size: 13px;
}

/* Category Filter Pills */
.hr-pill-btn {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.12s ease;
}
.hr-pill-btn:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}
.hr-pill-btn.active {
    background: #7c3aed;
    color: #ffffff;
    border-color: #7c3aed;
}

.modal-sec-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #7c3aed;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 16px 0 10px 0;
    padding-bottom: 4px;
    border-bottom: 1px solid #f1f5f9;
}
</style>

@push('scripts')
<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Tab Switching
function switchProfileTab(tabName) {
    // Buttons
    document.querySelectorAll('.hr-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });
    // Panes
    document.querySelectorAll('.hr-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === 'tab-' + tabName);
    });
    // Persist in URL hash
    window.location.hash = tabName;
}

// On load hash check
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('tab-' + hash)) {
        switchProfileTab(hash);
    }
    // Check for ?action=edit
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'edit') {
        openModal('editEmployeeModal');
    }
});

// Profile Header More Actions dropdown
function toggleProfileMoreActions(e) {
    e.stopPropagation();
    const d = document.getElementById('profileMoreActionsDropdown');
    if (!d) return;
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.hr-action-menu-wrap')) {
        const d = document.getElementById('profileMoreActionsDropdown');
        if (d) d.style.display = 'none';
    }
});

// Sensitive Gov ID Masking Toggle
let govIdMasked = true;
function toggleGovIdMasking() {
    govIdMasked = !govIdMasked;
    const btnText = document.getElementById('maskBtnText');
    const icon = document.getElementById('maskIcon');
    const fields = document.querySelectorAll('.gov-id-field');

    if (govIdMasked) {
        btnText.textContent = 'Show ID Numbers';
        icon.className = 'ph ph-eye';
        fields.forEach(f => f.textContent = f.getAttribute('data-masked'));
    } else {
        btnText.textContent = 'Mask ID Numbers';
        icon.className = 'ph ph-eye-slash';
        fields.forEach(f => f.textContent = f.getAttribute('data-real'));
    }
}

// Filter Documents by Category
function filterDocCategory(category, btn) {
    document.querySelectorAll('.hr-pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('#empDocumentsTable tbody tr.doc-row');
    rows.forEach(r => {
        const c = r.getAttribute('data-category');
        if (category === 'all' || c === category) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// Replace Doc Modal trigger
function openReplaceDocModal(docId, docName) {
    const form = document.getElementById('replaceDocForm');
    form.action = `/hr/documents/${docId}/replace`;
    document.getElementById('replaceDocName').value = docName;
    openModal('replaceDocModal');
}

// Document Preview
function openDocPreview(fileUrl, docTitle) {
    document.getElementById('previewDocTitle').innerHTML = '<i class="ph ph-file-text"></i> ' + docTitle;
    document.getElementById('previewDocFrame').src = fileUrl;
    openModal('previewDocModal');
}
</script>
@endpush
@endsection
