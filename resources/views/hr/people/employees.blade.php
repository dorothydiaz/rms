@extends('layouts.app')

@section('title', 'Employees Directory - People Administration - Restaurant Management System')

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <a href="{{ route('hr.reports.export.employees') }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addEmployeeModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add New Employee</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Filter Bar -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.people.employees') }}" class="hr-filter-form">
        <input type="text" name="search" class="hr-input" placeholder="Search by name, ID, email..." value="{{ request('search') }}" style="flex: 1; min-width: 200px;">

        @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
            <select name="branch_id" class="hr-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif

        <select name="department_id" class="hr-select">
            <option value="">All Departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
            @endforeach
        </select>

        <select name="employment_status" class="hr-select">
            <option value="">All Statuses</option>
            @foreach(['Active', 'Probationary', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Retired'] as $st)
                <option value="{{ $st }}" {{ request('employment_status') == $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-magnifying-glass"></i>
            <span>Filter</span>
        </button>
        @if(request()->hasAny(['search', 'branch_id', 'department_id', 'employment_status']))
            <a href="{{ route('hr.people.employees') }}" class="hr-btn hr-btn-secondary" title="Clear Filters">
                <i class="ph ph-x"></i>
            </a>
        @endif
    </form>
</div>

<!-- Employees Data Table -->
<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Branch</th>
                    <th>Department & Position</th>
                    <th>Employment Type</th>
                    <th>Status</th>
                    <th>Date Hired</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                        <td>
                            <strong style="color: #9333ea; font-family: monospace;">{{ $emp->employee_id }}</strong>
                        </td>
                        <td>
                            <div class="hr-emp-avatar-wrap">
                                @if($emp->photo_url)
                                    <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="hr-avatar-img">
                                @else
                                    <div class="hr-avatar-circle">
                                        {{ $emp->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight: 600; color: #0f172a;">{{ $emp->full_name }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">{{ $emp->email ?? 'No email' }} &bull; {{ $emp->mobile_number ?? 'No phone' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="hr-badge hr-badge-neutral">{{ $emp->branch?->name ?? 'Unassigned' }}</span>
                        </td>
                        <td>
                            <div>{{ $emp->position?->name ?? 'N/A' }}</div>
                            <div style="font-size: 11.5px; color: #64748b;">{{ $emp->department?->name ?? 'General' }}</div>
                        </td>
                        <td>{{ $emp->employment_type }}</td>
                        <td>
                            @if($emp->employment_status === 'Active')
                                <span class="hr-badge hr-badge-success">{{ $emp->employment_status }}</span>
                            @elseif($emp->employment_status === 'Probationary')
                                <span class="hr-badge hr-badge-warning">{{ $emp->employment_status }}</span>
                            @elseif($emp->employment_status === 'On Leave')
                                <span class="hr-badge hr-badge-info">{{ $emp->employment_status }}</span>
                            @else
                                <span class="hr-badge hr-badge-danger">{{ $emp->employment_status }}</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($emp->date_hired)->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('hr.people.employees.show', $emp->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Full Profile">
                                    <i class="ph ph-eye"></i> View
                                </a>
                                @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
                                    <form method="POST" action="{{ route('hr.people.employees.destroy', $emp->id) }}" onsubmit="return confirm('Are you sure you want to delete employee {{ $emp->full_name }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete Employee">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="ph ph-users" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No employees found matching the given filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($employees->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $employees->links() }}
        </div>
    @endif
</div>

<!-- Modal: Add New Employee -->
<div id="addEmployeeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 780px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-user-plus"></i> Add New Employee</span>
            <button class="icon-btn" onclick="closeModal('addEmployeeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.employees.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body">
                <h4 style="font-size: 13px; text-transform: uppercase; color: #9333ea; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px;">
                    1. Personal Information
                </h4>

                <!-- Employee Photo Upload with Avatar Preview -->
                <div class="hr-form-group" style="background: rgba(248, 250, 252, 0.85); border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 12px 16px; margin-bottom: 4px;">
                    <label class="hr-form-label" style="margin-bottom: 6px;">Profile Photo</label>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div id="addPhotoPreviewWrap">
                            <img id="addPhotoPreviewImg" src="" alt="Preview" style="display: none; width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div id="addPhotoAvatarPlaceholder" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; box-shadow: 0 2px 8px rgba(168, 85, 247, 0.25);">
                                <i class="ph ph-user"></i>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" name="photo" id="addEmployeePhoto" class="hr-input" accept="image/*" onchange="previewAddPhoto(this)" style="padding: 7px 10px;">
                            <small style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                                Upload a photo (JPEG, PNG, WEBP up to 5MB). If omitted, an avatar will be generated automatically.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employee ID *</label>
                        <input type="text" name="employee_id" class="hr-input" required placeholder="EMP-2026-011" value="EMP-2026-{{ str_pad(rand(11, 999), 3, '0', STR_PAD_LEFT) }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">First Name *</label>
                        <input type="text" name="first_name" class="hr-input" required placeholder="First name">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="hr-input" placeholder="Middle name">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Last Name *</label>
                        <input type="text" name="last_name" class="hr-input" required placeholder="Last name">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Gender</label>
                        <select name="gender" class="hr-select">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Civil Status *</label>
                        <select name="civil_status" class="hr-select" required>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Nationality *</label>
                        <input type="text" name="nationality" class="hr-input" value="Filipino" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="hr-input" placeholder="0917xxxxxxx">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email Address</label>
                        <input type="email" name="email" class="hr-input" placeholder="employee@restaurant.ph">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Residential Address</label>
                    <input type="text" name="address" class="hr-input" placeholder="Street, Barangay, City">
                </div>

                <h4 style="font-size: 13px; text-transform: uppercase; color: #9333ea; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; margin-top: 10px;">
                    2. Employment & Organizational Information
                </h4>
                <div class="hr-form-grid">
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
                            <option value="">Select Department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Position</label>
                        <select name="position_id" class="hr-select">
                            <option value="">Select Position</option>
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
                        <label class="hr-form-label">Employment Status *</label>
                        <select name="employment_status" class="hr-select" required>
                            <option value="Active">Active</option>
                            <option value="Probationary">Probationary</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" class="hr-select" required>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Casual">Casual</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Source *</label>
                        <div style="display: flex; gap: 16px; align-items: center; margin-top: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="employment_source" value="Company" checked onchange="document.getElementById('emp_agency_box').style.display='none'; document.getElementById('emp_company_box').style.display='block';">
                                <span>Company</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="employment_source" value="Agency" onchange="document.getElementById('emp_agency_box').style.display='block'; document.getElementById('emp_company_box').style.display='none';">
                                <span>Agency</span>
                            </label>
                        </div>
                    </div>
                    <div class="hr-form-group" id="emp_company_box">
                        <label class="hr-form-label">Company Name</label>
                        <input type="text" name="company_name" class="hr-input" placeholder="e.g. Bistro Hospitality Group Inc.">
                    </div>
                    <div class="hr-form-group" id="emp_agency_box" style="display: none;">
                        <label class="hr-form-label">Agency Name *</label>
                        <input type="text" name="agency_name" class="hr-input" placeholder="e.g. ABC Manpower & Staffing">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="hr-input">
                    </div>
                </div>

                <h4 style="font-size: 13px; text-transform: uppercase; color: #9333ea; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; margin-top: 10px;">
                    3. Compensation & Government Numbers (Protected)
                </h4>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Basic Salary (PHP)</label>
                        <input type="number" step="0.01" name="basic_salary" class="hr-input" placeholder="20000.00" value="18000.00">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Type *</label>
                        <select name="salary_type" class="hr-select" required>
                            <option value="Monthly">Monthly</option>
                            <option value="Daily">Daily</option>
                            <option value="Hourly">Hourly</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency *</label>
                        <select name="pay_frequency" class="hr-select" required>
                            <option value="Semi-Monthly">Semi-Monthly (15th & 30th)</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Allowances (PHP)</label>
                        <input type="number" step="0.01" name="allowances" class="hr-input" placeholder="1000.00" value="0.00">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">SSS Number</label>
                        <input type="text" name="sss_number" class="hr-input" placeholder="00-0000000-0">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">PhilHealth Number</label>
                        <input type="text" name="philhealth_number" class="hr-input" placeholder="00-000000000-0">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pag-IBIG Number</label>
                        <input type="text" name="pagibig_number" class="hr-input" placeholder="0000-0000-0000">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">TIN Number</label>
                        <input type="text" name="tin" class="hr-input" placeholder="000-000-000-000">
                    </div>
                </div>

                <h4 style="font-size: 13px; text-transform: uppercase; color: #9333ea; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; margin-top: 10px;">
                    4. Emergency Contact
                </h4>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="hr-input" placeholder="Full name">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Relationship</label>
                        <input type="text" name="emergency_contact_relationship" class="hr-input" placeholder="Spouse / Parent / Sibling">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Emergency Phone</label>
                        <input type="text" name="emergency_contact_phone" class="hr-input" placeholder="0918xxxxxxx">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addEmployeeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Employee Record</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
function previewAddPhoto(input) {
    var preview = document.getElementById('addPhotoPreviewImg');
    var placeholder = document.getElementById('addPhotoAvatarPlaceholder');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = 'flex';
    }
}
</script>
@endpush
@endsection
