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

<!-- Real-Time Filter & Search Bar (No Enter Key Required) -->
<div class="hr-filter-bar" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <!-- Filters & Search Controls Group -->
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
        <!-- Search Input -->
        <div style="position: relative; width: 260px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
            <input type="text" id="empSearchInput" class="hr-input" placeholder="Search name, ID, position..." oninput="filterEmployeesDirectory()" style="width: 100%; box-sizing: border-box; padding-left: 36px; height: 38px;">
        </div>

        @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
            <select id="empBranchFilter" class="hr-select" style="height: 38px; max-width: 190px;" onchange="filterEmployeesDirectory()">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->name }}">{{ $b->name }}</option>
                @endforeach
            </select>
        @endif

        <select id="empDeptFilter" class="hr-select" style="height: 38px; max-width: 180px;" onchange="filterEmployeesDirectory()">
            <option value="">All Departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->name }}">{{ $d->name }}</option>
            @endforeach
        </select>

        <select id="empStatusFilter" class="hr-select" style="height: 38px; max-width: 150px;" onchange="filterEmployeesDirectory()">
            <option value="">All Statuses</option>
            @foreach(['Active', 'Probationary', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Retired'] as $st)
                <option value="{{ $st }}">{{ $st }}</option>
            @endforeach
        </select>

        <!-- Sort By Options in Table Toolbar -->
        <select id="empSortSelect" class="hr-select" style="height: 38px; max-width: 200px;" onchange="applyEmpSortFromSelect(this.value)">
            <option value="">Sort By: Default</option>
            <option value="name_asc">Name (A &rarr; Z)</option>
            <option value="name_desc">Name (Z &rarr; A)</option>
            <option value="id_asc">Employee ID (Ascending)</option>
            <option value="id_desc">Employee ID (Descending)</option>
            <option value="branch_asc">Branch (A &rarr; Z)</option>
            <option value="dept_asc">Department / Position (A &rarr; Z)</option>
            <option value="type_asc">Employment Type (A &rarr; Z)</option>
            <option value="status_asc">Status (Active First)</option>
            <option value="date_desc">Date Hired (Newest First)</option>
            <option value="date_asc">Date Hired (Oldest First)</option>
        </select>

        <!-- Reset Filter Button -->
        <button type="button" class="hr-btn hr-btn-secondary" onclick="resetEmployeesDirectory()" title="Reset all filters" style="height: 38px; padding: 0 14px;">
            <i class="ph ph-arrows-counter-clockwise"></i>
            <span>Reset</span>
        </button>
    </div>

    <!-- Live Counter Badge -->
    <div style="flex-shrink: 0;">
        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 8px; background: #f1f5f9; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
            Showing <strong id="empVisibleCount" style="color: #0f172a;">{{ $employees->count() }}</strong> of {{ $employees->count() }} employees
        </span>
    </div>
</div>

<!-- Employees Data Table with Sticky Headers and Vertical Scrollbar -->
<div class="hr-table-card">
    <div class="hr-table-wrapper" id="employeesTableWrapper" style="max-height: 560px; overflow-y: auto; overflow-x: auto;">
        <table class="hr-table" id="employeesDirectoryTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="sortEmployeesDirectory(0, 'text')" title="Click to sort by Employee ID">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employee ID</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(1, 'text')" title="Click to sort by Full Name (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Full Name</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" style="min-width: 200px;" onclick="sortEmployeesDirectory(2, 'text')" title="Click to sort by Branch (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Branch & Company</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(3, 'text')" title="Click to sort by Department & Position">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Department & Position</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(4, 'text')" title="Click to sort by Employment Type">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employment Type</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(5, 'text')" title="Click to sort by Status">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Status</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(6, 'date')" title="Click to sort by Date Hired">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Date Hired</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th style="text-align: right; width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                @forelse($employees as $emp)
                    @php
                        $deptName = $emp->department?->name ?? 'General';
                        $posName = $emp->position?->name ?? 'N/A';
                        $branchName = $emp->branch?->name ?? 'Unassigned';
                        $dateHiredVal = $emp->date_hired ? \Carbon\Carbon::parse($emp->date_hired)->timestamp : 0;
                    @endphp
                    <tr class="emp-row" 
                        data-id="{{ strtolower($emp->employee_id) }}"
                        data-name="{{ strtolower($emp->full_name) }}"
                        data-email="{{ strtolower($emp->email ?? '') }}"
                        data-branch="{{ $branchName }}"
                        data-dept="{{ $deptName }}"
                        data-position="{{ strtolower($posName) }}"
                        data-type="{{ strtolower($emp->employment_type ?? '') }}"
                        data-status="{{ $emp->employment_status }}"
                        data-date="{{ $dateHiredVal }}">
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
                                    <div style="font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span>{{ $emp->full_name }}</span>
                                        @if($emp->user)
                                            <span class="hr-badge hr-badge-purple" style="font-size: 10.5px; padding: 1px 6px; font-weight: 600;" title="Linked User Account: @ {{ $emp->user->username }}">
                                                <i class="ph ph-user-check"></i> {{ '@' . $emp->user->username }}
                                            </span>
                                        @endif
                                    </div>
                                    <div style="font-size: 11.5px; color: #64748b;">{{ $emp->email ?? 'No email' }} &bull; {{ $emp->mobile_number ?? 'No phone' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="white-space: nowrap; min-width: 200px;">
                            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 5px;">
                                <div style="font-weight: 600; color: #0f172a; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="ph ph-storefront" style="color: #64748b; font-size: 15px;"></i>
                                    <span>{{ $branchName }}</span>
                                </div>
                                <div style="display: inline-flex; align-items: center;">
                                    @if($emp->employment_source === 'Agency')
                                        <span class="hr-badge hr-badge-purple" style="font-size: 10.5px; padding: 2.5px 8px; font-weight: 600;" title="Agency: {{ $emp->company_or_agency }}">
                                            <i class="ph ph-handshake"></i> {{ $emp->company_or_agency }}
                                        </span>
                                    @else
                                        <span class="hr-badge hr-badge-info" style="font-size: 10.5px; padding: 2.5px 8px; font-weight: 600;" title="Company: {{ $emp->company_or_agency }}">
                                            <i class="ph ph-buildings"></i> {{ $emp->company_or_agency }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $posName }}</div>
                            <div style="font-size: 11.5px; color: #64748b;">{{ $deptName }}</div>
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
                <tr id="noEmpResultsRow" style="display: none;">
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px;">
                        <i class="ph ph-magnifying-glass" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                        No employees match your filter criteria.
                    </td>
                </tr>
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
                        <label class="hr-form-label">Linked User Account (Optional)</label>
                        <select name="user_id" class="hr-select">
                            <option value="">-- No User Account --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->full_name }} (@ {{ $u->username }})</option>
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
                        <label class="hr-form-label">Company Name *</label>
                        <select name="company_name" class="hr-select">
                            @foreach($companies as $c)
                                <option value="{{ $c->name }}">{{ $c->name }} ({{ $c->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group" id="emp_agency_box" style="display: none;">
                        <label class="hr-form-label">Agency Name *</label>
                        <select name="agency_name" class="hr-select">
                            @foreach($agencies as $a)
                                <option value="{{ $a->name }}">{{ $a->name }} ({{ $a->code }})</option>
                            @endforeach
                        </select>
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

// =========================================================================
// Real-Time Table Filter (No Enter Key or Submit Button Required)
// =========================================================================
function filterEmployeesDirectory() {
    const searchVal = (document.getElementById('empSearchInput')?.value || '').toLowerCase().trim();
    const branchVal = (document.getElementById('empBranchFilter')?.value || '').trim();
    const deptVal = (document.getElementById('empDeptFilter')?.value || '').trim();
    const statusVal = (document.getElementById('empStatusFilter')?.value || '').trim();

    const rows = document.querySelectorAll('#employeesTableBody tr.emp-row');
    const noResultsRow = document.getElementById('noEmpResultsRow');
    let visibleCount = 0;

    rows.forEach(row => {
        const id = row.getAttribute('data-id') || '';
        const name = row.getAttribute('data-name') || '';
        const email = row.getAttribute('data-email') || '';
        const branch = row.getAttribute('data-branch') || '';
        const dept = row.getAttribute('data-dept') || '';
        const pos = row.getAttribute('data-position') || '';
        const type = row.getAttribute('data-type') || '';
        const status = row.getAttribute('data-status') || '';

        const matchesSearch = !searchVal || 
            id.includes(searchVal) || 
            name.includes(searchVal) || 
            email.includes(searchVal) || 
            pos.includes(searchVal) ||
            dept.toLowerCase().includes(searchVal) ||
            branch.toLowerCase().includes(searchVal);

        const matchesBranch = !branchVal || branch === branchVal;
        const matchesDept = !deptVal || dept === deptVal;
        const matchesStatus = !statusVal || status === statusVal;

        if (matchesSearch && matchesBranch && matchesDept && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('empVisibleCount');
    if (countElem) countElem.textContent = visibleCount;

    if (noResultsRow) {
        if (visibleCount === 0 && rows.length > 0) {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    }
}

function resetEmployeesDirectory() {
    const s = document.getElementById('empSearchInput');
    if (s) s.value = '';
    const b = document.getElementById('empBranchFilter');
    if (b) b.value = '';
    const d = document.getElementById('empDeptFilter');
    if (d) d.value = '';
    const st = document.getElementById('empStatusFilter');
    if (st) st.value = '';
    const sel = document.getElementById('empSortSelect');
    if (sel) sel.value = '';

    // Clear header sort classes
    document.querySelectorAll('#employeesDirectoryTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    empSortCol = -1;

    filterEmployeesDirectory();
}

// =========================================================================
// Real-Time Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let empSortCol = -1;
let empSortDir = 'asc';

function applyEmpSortFromSelect(val) {
    if (!val) {
        document.querySelectorAll('#employeesDirectoryTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        empSortCol = -1;
        return;
    }

    const sortMap = {
        'name_asc': [1, 'text', 'asc'],
        'name_desc': [1, 'text', 'desc'],
        'id_asc': [0, 'text', 'asc'],
        'id_desc': [0, 'text', 'desc'],
        'branch_asc': [2, 'text', 'asc'],
        'dept_asc': [3, 'text', 'asc'],
        'type_asc': [4, 'text', 'asc'],
        'status_asc': [5, 'text', 'asc'],
        'date_desc': [6, 'date', 'desc'],
        'date_asc': [6, 'date', 'asc'],
    };

    if (sortMap[val]) {
        sortEmployeesDirectory(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortEmployeesDirectory(colIndex, dataType, forceDir = null) {
    const tableBody = document.getElementById('employeesTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr.emp-row'));
    const headers = document.querySelectorAll('#employeesDirectoryTable thead th.sortable');

    if (forceDir) {
        empSortDir = forceDir;
        empSortCol = colIndex;
    } else {
        if (empSortCol === colIndex) {
            empSortDir = empSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            empSortCol = colIndex;
            empSortDir = 'asc';
        }
    }

    headers.forEach((th, idx) => {
        const icon = th.querySelector('.sort-icon i');
        if (idx === colIndex) {
            th.classList.remove('sorted-asc', 'sorted-desc');
            th.classList.add(empSortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) {
                icon.className = empSortDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
            }
        } else {
            th.classList.remove('sorted-asc', 'sorted-desc');
            if (icon) {
                icon.className = 'ph ph-arrows-down-up';
            }
        }
    });

    // Synchronize Toolbar Sort Dropdown
    const sortSelect = document.getElementById('empSortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'id_asc', '0_desc': 'id_desc',
            '1_asc': 'name_asc', '1_desc': 'name_desc',
            '2_asc': 'branch_asc', '2_desc': 'branch_asc',
            '3_asc': 'dept_asc', '3_desc': 'dept_asc',
            '4_asc': 'type_asc', '4_desc': 'type_asc',
            '5_asc': 'status_asc', '5_desc': 'status_asc',
            '6_asc': 'date_asc', '6_desc': 'date_desc'
        };
        sortSelect.value = keyMap[colIndex + '_' + empSortDir] || '';
    }

    rows.sort((a, b) => {
        let valA, valB;
        if (colIndex === 0) {
            valA = a.getAttribute('data-id') || '';
            valB = b.getAttribute('data-id') || '';
        } else if (colIndex === 1) {
            valA = a.getAttribute('data-name') || '';
            valB = b.getAttribute('data-name') || '';
        } else if (colIndex === 2) {
            valA = a.getAttribute('data-branch') || '';
            valB = b.getAttribute('data-branch') || '';
        } else if (colIndex === 3) {
            valA = (a.getAttribute('data-dept') || '') + ' ' + (a.getAttribute('data-position') || '');
            valB = (b.getAttribute('data-dept') || '') + ' ' + (b.getAttribute('data-position') || '');
        } else if (colIndex === 4) {
            valA = a.getAttribute('data-type') || '';
            valB = b.getAttribute('data-type') || '';
        } else if (colIndex === 5) {
            valA = a.getAttribute('data-status') || '';
            valB = b.getAttribute('data-status') || '';
        } else if (colIndex === 6) {
            valA = parseFloat(a.getAttribute('data-date')) || 0;
            valB = parseFloat(b.getAttribute('data-date')) || 0;
            return empSortDir === 'asc' ? valA - valB : valB - valA;
        }

        const cmp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
        return empSortDir === 'asc' ? cmp : -cmp;
    });

    const noResultsRow = document.getElementById('noEmpResultsRow');
    rows.forEach(r => tableBody.appendChild(r));
    if (noResultsRow) tableBody.appendChild(noResultsRow);
}
</script>
@endpush
@endsection
