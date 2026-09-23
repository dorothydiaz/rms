@extends('layouts.app')

@section('title', 'Applicants Pool - Recruitment')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-users-four"></i>
            Applicant Tracking & Hiring
        </h1>
        <p class="hr-page-subtitle">Track candidate applications, screening pipeline, interview progress, and hire conversion</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addApplicantModal')">
            <i class="ph ph-user-plus"></i>
            <span>Register Applicant</span>
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.recruitment.applicants') }}" class="hr-filter-form">
        <input type="text" name="search" class="hr-input" placeholder="Search applicant name, email, role..." value="{{ request('search') }}" style="flex: 1; min-width: 200px;">

        <select name="status" class="hr-select">
            <option value="">All Statuses</option>
            @foreach(['New', 'Screening', 'Interview', 'Assessment', 'Shortlisted', 'Job Offer', 'Hired', 'Rejected'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-magnifying-glass"></i>
            <span>Filter</span>
        </button>
    </form>
</div>

<!-- Applicants Table -->
<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Applicant Name</th>
                    <th>Applied Position</th>
                    <th>Contact Info</th>
                    <th>Source</th>
                    <th>Application Date</th>
                    <th>Pipeline Status</th>
                    <th style="text-align: right;">Hiring Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applicants as $app)
                    <tr>
                        <td>
                            <strong>{{ $app->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $app->address ?? 'No address' }}</small>
                        </td>
                        <td>
                            <strong>{{ $app->applied_position }}</strong><br>
                            <small style="color: #9333ea;">{{ $app->jobVacancy?->title ?? 'Direct Application' }}</small>
                        </td>
                        <td>
                            <div>{{ $app->contact_number ?? 'No phone' }}</div>
                            <small style="color: #64748b;">{{ $app->email ?? 'No email' }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $app->source }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($app->application_date)->format('M d, Y') }}</td>
                        <td>
                            @if($app->status === 'Hired')
                                <span class="hr-badge hr-badge-success">{{ $app->status }}</span>
                            @elseif($app->status === 'Rejected')
                                <span class="hr-badge hr-badge-danger">{{ $app->status }}</span>
                            @elseif(in_array($app->status, ['Job Offer', 'Shortlisted']))
                                <span class="hr-badge hr-badge-purple">{{ $app->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-info">{{ $app->status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($app->status === 'Hired' && $app->hiredAsEmployee)
                                <a href="{{ route('hr.people.employees.show', $app->hiredAsEmployee->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #059669;">
                                    <i class="ph ph-check"></i> Employee: {{ $app->hiredAsEmployee->employee_id }}
                                </a>
                            @else
                                <button class="hr-btn hr-btn-success hr-btn-sm" onclick="openConvertModal({{ $app->id }}, '{{ addslashes($app->full_name) }}', '{{ addslashes($app->applied_position) }}')">
                                    <i class="ph ph-user-check"></i> Hire as Employee
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">No applicants recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applicants->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $applicants->links() }}
        </div>
    @endif
</div>

<!-- Modal: Register Applicant -->
<div id="addApplicantModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-user-plus"></i> Register New Applicant</span>
            <button class="icon-btn" onclick="closeModal('addApplicantModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.applicants.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">First Name *</label>
                        <input type="text" name="first_name" class="hr-input" required placeholder="First name">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Last Name *</label>
                        <input type="text" name="last_name" class="hr-input" required placeholder="Last name">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="hr-input" placeholder="0917xxxxxxx">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email Address</label>
                        <input type="email" name="email" class="hr-input" placeholder="applicant@email.com">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Applied Position *</label>
                        <input type="text" name="applied_position" class="hr-input" required placeholder="e.g. Server, Cook">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Linked Vacancy</label>
                        <select name="job_vacancy_id" class="hr-select">
                            <option value="">General Application</option>
                            @foreach($vacancies as $v)
                                <option value="{{ $v->id }}">{{ $v->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Source *</label>
                        <select name="source" class="hr-select" required>
                            <option value="Walk-in">Walk-in</option>
                            <option value="Online">Online Job Board</option>
                            <option value="Referral">Employee Referral</option>
                            <option value="Job Fair">Job Fair</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Application Date *</label>
                        <input type="date" name="application_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Initial Status *</label>
                        <select name="status" class="hr-select" required>
                            <option value="New">New</option>
                            <option value="Screening">Screening</option>
                            <option value="Interview">Interview</option>
                        </select>
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Residential Address</label>
                    <input type="text" name="address" class="hr-input" placeholder="City / Barangay">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addApplicantModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Register Applicant</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Convert Applicant into Employee -->
<div id="convertModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 650px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-user-check"></i> Convert Applicant to Employee</span>
            <button class="icon-btn" onclick="closeModal('convertModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="convertForm" method="POST" action="">
            @csrf
            <div class="hr-modal-body">
                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 10px; padding: 12px 16px; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: #065f46;" id="convertApplicantName">Candidate Name</div>
                    <div style="font-size: 12px; color: #047857;" id="convertApplicantPos">Position</div>
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">New Employee ID *</label>
                        <input type="text" name="employee_id" class="hr-input" required value="EMP-2026-{{ str_pad(rand(10, 999), 3, '0', STR_PAD_LEFT) }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Assigned Branch *</label>
                        <select name="branch_id" class="hr-select" required>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Department</label>
                        <select name="department_id" class="hr-select">
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Position</label>
                        <select name="position_id" class="hr-select">
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date Hired *</label>
                        <input type="date" name="date_hired" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Status *</label>
                        <select name="employment_status" class="hr-select" required>
                            <option value="Probationary">Probationary</option>
                            <option value="Active">Active / Regular</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" class="hr-select" required>
                            <option value="Probationary">Probationary</option>
                            <option value="Regular">Regular</option>
                            <option value="Part-time">Part-time</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Basic Salary (PHP) *</label>
                        <input type="number" step="0.01" name="basic_salary" class="hr-input" required placeholder="18000.00" value="18000.00">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Type *</label>
                        <select name="salary_type" class="hr-select" required>
                            <option value="Monthly">Monthly</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency *</label>
                        <select name="pay_frequency" class="hr-select" required>
                            <option value="Semi-Monthly">Semi-Monthly</option>
                            <option value="Monthly">Monthly</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('convertModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-success">Finalize Hire & Create Employee</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openConvertModal(appId, name, position) {
    document.getElementById('convertApplicantName').innerText = name;
    document.getElementById('convertApplicantPos').innerText = 'Applied Position: ' + position;
    document.getElementById('convertForm').action = '/hr/recruitment/applicants/' + appId + '/convert';
    openModal('convertModal');
}
</script>
@endpush
@endsection
