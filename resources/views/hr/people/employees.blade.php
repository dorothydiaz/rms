@extends('layouts.app')

@section('title', 'Employee Management - Restaurant Management System')

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <a href="{{ route('hr.reports.export.employees') }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export</span>
        </a>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addEmployeeModal')">
            <i class="ph ph-user-plus"></i>
            <span>Add Employee</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- 5 Compact Summary Cards -->
<div class="hr-emp-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-bottom: 8px;">
    <!-- Total Employees -->
    <div class="hr-stat-card hr-stat-card-purple">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-users"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Total Employees</span>
            <div class="hr-stat-value">{{ $counts['total'] ?? $employees->total() }}</div>
            <span class="hr-stat-sub">Active Workforce</span>
        </div>
    </div>

    <!-- Active -->
    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-check-circle"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Active</span>
            <div class="hr-stat-value">{{ $counts['active'] ?? 0 }}</div>
            <span class="hr-stat-sub">Regular & Duty</span>
        </div>
    </div>

    <!-- Probationary -->
    <div class="hr-stat-card hr-stat-card-amber">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-hourglass-medium"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Probationary</span>
            <div class="hr-stat-value">{{ $counts['probationary'] ?? 0 }}</div>
            <span class="hr-stat-sub">Under Evaluation</span>
        </div>
    </div>

    <!-- On Leave -->
    <div class="hr-stat-card hr-stat-card-blue">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-calendar-blank"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">On Leave</span>
            <div class="hr-stat-value">{{ $counts['on_leave'] ?? 0 }}</div>
            <span class="hr-stat-sub">Authorized Leave</span>
        </div>
    </div>

    <!-- Separated -->
    <div class="hr-stat-card hr-stat-card-rose">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-user-minus"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Separated</span>
            <div class="hr-stat-value">{{ $counts['separated'] ?? 0 }}</div>
            <span class="hr-stat-sub">Resigned / Inactive</span>
        </div>
    </div>
</div>

@php
    $activeDept = request('department_id') ? ($departments->firstWhere('id', request('department_id'))?->name ?? request('department')) : request('department');
    $activeBranch = request('branch_id') ? ($branches->firstWhere('id', request('branch_id'))?->name ?? request('branch')) : request('branch');
    $activePos = request('position_id') ? ($positions->firstWhere('id', request('position_id'))?->name ?? request('position')) : request('position');
    $activeComp = request('company_id') ? ($companies->firstWhere('id', request('company_id'))?->name ?? $agencies->firstWhere('id', request('company_id'))?->name ?? request('company_name')) : request('company_name');
@endphp

@if(isset($filterContext) && $filterContext)
    <div class="hr-active-filter-banner" style="margin-bottom: 14px; background: linear-gradient(135deg, rgba(124, 58, 237, 0.08) 0%, rgba(219, 39, 119, 0.06) 100%); border: 1.5px solid rgba(124, 58, 237, 0.25); border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: 0 2px 6px rgba(124, 58, 237, 0.05);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: #7c3aed; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                <i class="ph ph-users"></i>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.5px;">Filtered Member View</div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                    {{ $filterContext['type'] }}: <span style="color: #7c3aed;">{{ $filterContext['name'] }}</span>
                    <span style="font-size: 12.5px; font-weight: 600; color: #64748b; margin-left: 6px;">({{ $employees->total() }} {{ Str::plural('member', $employees->total()) }} found)</span>
                </div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="{{ route('hr.people.employees') }}" class="hr-btn hr-btn-secondary" style="height: 32px; padding: 0 12px; font-size: 12px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569; text-decoration: none;" title="Clear filter to view all employees">
                <i class="ph ph-x"></i>
                <span>Clear Filter</span>
            </a>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="window.close()" style="height: 32px; padding: 0 12px; font-size: 12px; background: #ffffff; border: 1px solid #cbd5e1; color: #475569;" title="Close this window">
                <i class="ph ph-x-circle"></i>
                <span>Close Window</span>
            </button>
        </div>
    </div>
@endif

<!-- Real-Time Filter & Search Bar -->
<div class="hr-filter-bar" style="margin-bottom: 8px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px 12px; display: flex; flex-direction: column; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none;">
        <!-- Filters & Search Controls Group -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; flex: 1;">
            <!-- Search Input -->
            <div style="position: relative; width: 220px; flex-shrink: 0;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; pointer-events: none;"></i>
                <input type="text" id="empSearchInput" class="hr-input" placeholder="Search employee..." oninput="filterEmployeesDirectory()" style="width: 100%; box-sizing: border-box; padding-left: 32px; height: 31px; font-size: 12.5px;">
            </div>

            <!-- Department Filter -->
            <select id="empDeptFilter" class="hr-select" style="height: 31px; max-width: 155px; font-size: 12.5px; padding: 2px 8px;" onchange="filterEmployeesDirectory()">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                    <option value="{{ $d->name }}" {{ $activeDept === $d->name ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>

            <!-- Branch Filter -->
            @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
                <select id="empBranchFilter" class="hr-select" style="height: 31px; max-width: 155px; font-size: 12.5px; padding: 2px 8px;" onchange="filterEmployeesDirectory()">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->name }}" {{ $activeBranch === $b->name ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif

            <!-- Position Filter -->
            <select id="empPositionFilter" class="hr-select" style="height: 31px; max-width: 155px; font-size: 12.5px; padding: 2px 8px;" onchange="filterEmployeesDirectory()">
                <option value="">All Positions</option>
                @foreach($positions as $p)
                    <option value="{{ $p->name }}" {{ $activePos === $p->name ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>

            <!-- Employment Type Filter -->
            <select id="empTypeFilter" class="hr-select" style="height: 31px; max-width: 135px; font-size: 12.5px; padding: 2px 8px;" onchange="filterEmployeesDirectory()">
                <option value="">All Types</option>
                @foreach(['Regular', 'Probationary', 'Contractual', 'Part-time', 'Seasonal', 'Intern / OJT'] as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select id="empStatusFilter" class="hr-select" style="height: 31px; max-width: 130px; font-size: 12.5px; padding: 2px 8px;" onchange="filterEmployeesDirectory()">
                <option value="">All Statuses</option>
                @foreach(['Active', 'Probationary', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Retired'] as $st)
                    <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </select>

            <!-- More Filters Toggle -->
            <button type="button" class="hr-btn hr-btn-secondary" onclick="toggleMoreFilters()" id="moreFiltersBtn" style="height: 31px; padding: 0 10px; font-size: 12px;">
                <i class="ph ph-funnel"></i>
                <span>More Filters</span>
            </button>

            <!-- Reset Filter Button -->
            <button type="button" class="hr-btn hr-btn-secondary" onclick="resetEmployeesDirectory()" title="Reset all filters" style="height: 31px; padding: 0 10px; font-size: 12px;">
                <i class="ph ph-arrows-counter-clockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- Live Counter Badge -->
        <div style="flex-shrink: 0;">
            <span class="hr-badge hr-badge-neutral" style="font-size: 11.5px; font-weight: 600; padding: 3px 9px; border-radius: 7px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
                Showing <strong id="empVisibleCount" style="color: #0f172a;">{{ $employees->count() }}</strong> of {{ $employees->count() }} employees
            </span>
        </div>
    </div>

    <!-- Collapsible More Filters Panel -->
    <div id="moreFiltersPanel" style="display: none; padding-top: 6px; border-top: 1px dashed #e2e8f0; align-items: center; gap: 10px; flex-wrap: wrap;">
        <!-- Company / Agency Filter -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b;">Source / Company:</span>
            <select id="empCompanyFilter" class="hr-select" style="height: 29px; font-size: 12px; padding: 2px 8px;" onchange="filterEmployeesDirectory()">
                <option value="">All Sources</option>
                @foreach($companies as $c)
                    <option value="{{ $c->name }}" {{ $activeComp === $c->name ? 'selected' : '' }}>{{ $c->name }} (Company)</option>
                @endforeach
                @foreach($agencies as $a)
                    <option value="{{ $a->name }}" {{ $activeComp === $a->name ? 'selected' : '' }}>{{ $a->name }} (Agency)</option>
                @endforeach
            </select>
        </div>

        <!-- Sort By Options in Table Toolbar -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b;">Sort By:</span>
            <select id="empSortSelect" class="hr-select" style="height: 29px; font-size: 12px; padding: 2px 8px;" onchange="applyEmpSortFromSelect(this.value)">
                <option value="">Default (Created Date)</option>
                <option value="name_asc">Name (A &rarr; Z)</option>
                <option value="name_desc">Name (Z &rarr; A)</option>
                <option value="id_asc">Employee ID (Ascending)</option>
                <option value="id_desc">Employee ID (Descending)</option>
                <option value="dept_asc">Department (A &rarr; Z)</option>
                <option value="pos_asc">Position (A &rarr; Z)</option>
                <option value="branch_asc">Branch (A &rarr; Z)</option>
                <option value="type_asc">Employment Type (A &rarr; Z)</option>
                <option value="status_asc">Status (Active First)</option>
                <option value="date_desc">Date Hired (Newest First)</option>
                <option value="date_asc">Date Hired (Oldest First)</option>
            </select>
        </div>
    </div>
</div>

<!-- Employee Directory Table -->
<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper" id="employeesTableWrapper" style="overflow-y: auto; overflow-x: auto;">
        <table class="hr-table" id="employeesDirectoryTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="sortEmployeesDirectory(0, 'text')" title="Click to sort by Employee ID" style="width: 120px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employee ID</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(1, 'text')" title="Click to sort by Employee Name" style="min-width: 220px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employee</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(2, 'text')" title="Click to sort by Department">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Department</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(3, 'text')" title="Click to sort by Position">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Position</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(4, 'text')" title="Click to sort by Branch">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Branch</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(5, 'text')" title="Click to sort by Employment Type">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employment Type</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(6, 'date')" title="Click to sort by Date Hired">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Date Hired</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortEmployeesDirectory(7, 'text')" title="Click to sort by Status" style="width: 110px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Status</span>
                            <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                        </div>
                    </th>
                    <th style="text-align: right; width: 80px;">Actions</th>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                @forelse($employees as $emp)
                    @php
                        $deptName = $emp->department?->name ?? 'Unassigned';
                        $posName = $emp->position?->name ?? 'General Staff';
                        $branchName = $emp->branch?->name ?? 'Unassigned';
                        $dateHiredVal = $emp->date_hired ? \Carbon\Carbon::parse($emp->date_hired)->timestamp : 0;
                        $initials = strtoupper(substr($emp->first_name ?? '', 0, 1) . substr($emp->last_name ?? '', 0, 1)) ?: 'EM';
                        $allDeptsStr = strtolower($emp->assignedDepartments()->pluck('name')->push($deptName)->unique()->implode('|'));
                        $allPosStr = strtolower($emp->assignedPositions()->pluck('name')->push($posName)->unique()->implode('|'));
                        $allBranchesStr = strtolower($emp->assignedBranches()->pluck('name')->push($branchName)->unique()->implode('|'));
                    @endphp
                    <tr class="emp-row" 
                        data-id="{{ strtolower($emp->employee_id) }}"
                        data-name="{{ strtolower($emp->full_name) }}"
                        data-email="{{ strtolower($emp->email ?? '') }}"
                        data-dept="{{ $deptName }}"
                        data-all-dept-names="{{ $allDeptsStr }}"
                        data-position="{{ $posName }}"
                        data-all-pos-names="{{ $allPosStr }}"
                        data-branch="{{ $branchName }}"
                        data-all-branch-names="{{ $allBranchesStr }}"
                        data-type="{{ $emp->employment_type ?? 'Regular' }}"
                        data-status="{{ $emp->employment_status }}"
                        data-company="{{ $emp->company_or_agency }}"
                        data-date="{{ $dateHiredVal }}"
                        onclick="if(!event.target.closest('.hr-action-menu-wrap, a, button, input')) window.location.href = '{{ route('hr.people.employees.show', $emp->id) }}';"
                        style="cursor: pointer;">
                        <td>
                            <strong style="color: #7c3aed; font-family: monospace; font-size: 13px;">{{ $emp->employee_id }}</strong>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                @if($emp->profile_photo_url)
                                    <img src="{{ $emp->profile_photo_url }}" alt="{{ $emp->full_name }}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
                                @else
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; box-shadow: 0 2px 6px rgba(124, 58, 237, 0.2);">
                                        {{ $initials }}
                                    </div>
                                @endif
                                <div style="min-width: 0;">
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <a href="{{ route('hr.people.employees.show', $emp->id) }}" style="color: inherit; text-decoration: none;" class="emp-name-link">
                                            {{ $emp->full_name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span style="font-weight: 500; color: #334155;">{{ $deptName }}</span>
                                @if(count($emp->all_department_ids) > 1)
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 10px; padding: 1px 5px;" title="Assigned Departments: {{ implode(', ', $emp->assigned_department_names) }}">
                                        +{{ count($emp->all_department_ids) - 1 }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span style="color: #0f172a; font-weight: 500;">{{ $posName }}</span>
                                @if(count($emp->all_position_ids) > 1)
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 10px; padding: 1px 5px;" title="Assigned Roles: {{ implode(', ', $emp->assignedPositions()->pluck('name')->toArray()) }}">
                                        +{{ count($emp->all_position_ids) - 1 }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px; color: #475569; flex-wrap: wrap;">
                                <i class="ph ph-storefront" style="color: #94a3b8; font-size: 14px;"></i>
                                <span>{{ $branchName }}</span>
                                @if(count($emp->all_branch_ids) > 1)
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 10px; padding: 1px 5px;" title="Assigned Branches: {{ implode(', ', $emp->assignedBranches()->pluck('name')->toArray()) }}">
                                        +{{ count($emp->all_branch_ids) - 1 }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px; padding: 2px 8px;">
                                {{ $emp->employment_type ?? 'Regular' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: #475569;">
                                {{ $emp->date_hired ? \Carbon\Carbon::parse($emp->date_hired)->format('M d, Y') : '—' }}
                            </span>
                        </td>
                        <td>
                            @if($emp->employment_status === 'Active')
                                <span class="hr-badge hr-badge-success" style="font-size: 11px; padding: 2px 8px;">Active</span>
                            @elseif($emp->employment_status === 'Probationary')
                                <span class="hr-badge hr-badge-warning" style="font-size: 11px; padding: 2px 8px;">Probationary</span>
                            @elseif($emp->employment_status === 'On Leave')
                                <span class="hr-badge hr-badge-info" style="font-size: 11px; padding: 2px 8px;">On Leave</span>
                            @else
                                <span class="hr-badge hr-badge-danger" style="font-size: 11px; padding: 2px 8px;">{{ $emp->employment_status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div class="hr-action-menu-wrap" style="position: relative; display: inline-block;">
                                <button type="button" class="hr-action-menu-btn" onclick="toggleEmpActionMenu(event, 'empMenu-{{ $emp->id }}')" title="Actions">
                                    <i class="ph ph-dots-three-vertical"></i>
                                </button>
                                <div id="empMenu-{{ $emp->id }}" class="hr-action-dropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 4px); z-index: 60; min-width: 175px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); padding: 6px; font-size: 12.5px; text-align: left;">
                                    <a href="{{ route('hr.people.employees.show', $emp->id) }}" class="hr-dropdown-item">
                                        <i class="ph ph-user"></i> View Profile
                                    </a>
                                    <button type="button" class="hr-dropdown-item" onclick="openEditEmployeeModal({{ $emp->id }})">
                                        <i class="ph ph-pencil-simple"></i> Edit Employee
                                    </button>
                                    <button type="button" class="hr-dropdown-item" onclick="openChangePositionModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', {{ $emp->position_id ?? 'null' }}, {{ json_encode($emp->all_position_ids) }})">
                                        <i class="ph ph-briefcase"></i> Change Position
                                    </button>
                                    <button type="button" class="hr-dropdown-item" onclick="openTransferModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', {{ $emp->department_id ?? 'null' }}, {{ $emp->branch_id ?? 'null' }}, {{ json_encode($emp->all_department_ids) }}, {{ json_encode($emp->all_branch_ids) }})">
                                        <i class="ph ph-arrows-left-right"></i> Transfer
                                    </button>
                                    <button type="button" class="hr-dropdown-item" onclick="openChangeStatusModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', '{{ $emp->employment_status }}')">
                                        <i class="ph ph-arrows-clockwise"></i> Change Status
                                    </button>
                                    <div style="height: 1px; background: #f1f5f9; margin: 4px 0;"></div>
                                    <a href="{{ route('hr.people.employees.coe', $emp->id) }}" class="hr-dropdown-item" target="_blank">
                                        <i class="ph ph-certificate"></i> Generate COE
                                    </a>
                                    <a href="{{ route('hr.people.employees.print-201', $emp->id) }}" class="hr-dropdown-item" target="_blank">
                                        <i class="ph ph-printer"></i> Print 201 File
                                    </a>
                                </div>
                                <script type="application/json" id="emp-json-{{ $emp->id }}">
                                {!! json_encode([
                                    'id' => $emp->id,
                                    'employee_id' => $emp->employee_id,
                                    'first_name' => $emp->first_name,
                                    'middle_name' => $emp->middle_name,
                                    'last_name' => $emp->last_name,
                                    'suffix' => $emp->suffix,
                                    'preferred_name' => $emp->preferred_name,
                                    'date_of_birth' => $emp->date_of_birth ? $emp->date_of_birth->format('Y-m-d') : '',
                                    'birth_place' => $emp->birth_place,
                                    'gender' => $emp->gender,
                                    'civil_status' => $emp->civil_status,
                                    'nationality' => $emp->nationality ?: 'Filipino',
                                    'email' => $emp->email,
                                    'personal_email' => $emp->email,
                                    'company_email' => $emp->company_email ?: $emp->email,
                                    'mobile_number' => $emp->mobile_number,
                                    'telephone_number' => $emp->telephone_number,
                                    'address' => $emp->address,
                                    'permanent_address' => $emp->permanent_address ?: $emp->address,
                                    'branch_id' => $emp->branch_id,
                                    'all_branch_ids' => $emp->all_branch_ids,
                                    'department_id' => $emp->department_id,
                                    'all_department_ids' => $emp->all_department_ids,
                                    'position_id' => $emp->position_id,
                                    'all_position_ids' => $emp->all_position_ids,
                                    'job_level' => $emp->job_level ?: 'Staff',
                                    'supervisor_id' => $emp->supervisor_id,
                                    'employment_type' => $emp->employment_type,
                                    'employment_status' => $emp->employment_status,
                                    'employment_source' => $emp->employment_source ?: 'Company',
                                    'company_name' => $emp->company_name,
                                    'agency_name' => $emp->agency_name,
                                    'company_id' => $emp->company_id,
                                    'work_location' => $emp->work_location,
                                    'work_schedule' => $emp->work_schedule,
                                    'date_hired' => $emp->date_hired ? $emp->date_hired->format('Y-m-d') : '',
                                    'date_of_regularization' => $emp->date_of_regularization ? $emp->date_of_regularization->format('Y-m-d') : '',
                                    'basic_salary' => $emp->basic_salary,
                                    'salary_type' => $emp->salary_type ?: 'Monthly',
                                    'payroll_type' => $emp->payroll_type,
                                    'pay_frequency' => $emp->pay_frequency ?: 'Semi-Monthly',
                                    'allowances' => $emp->allowances,
                                    'sss_number' => $emp->sss_number,
                                    'philhealth_number' => $emp->philhealth_number,
                                    'pagibig_number' => $emp->pagibig_number,
                                    'tin' => $emp->tin,
                                    'rdo_code' => $emp->rdo_code,
                                    'philsys_id' => $emp->philsys_id,
                                    'passport_number' => $emp->passport_number,
                                    'driver_license' => $emp->driver_license,
                                    'photo_url' => $emp->photo_url,
                                    'full_name' => $emp->full_name,
                                ]) !!}
                                </script>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="ph ph-users" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No employees found matching the given filters.
                        </td>
                    </tr>
                @endforelse
                <tr id="noEmpResultsRow" style="display: none;">
                    <td colspan="9" style="text-align: center; color: #94a3b8; padding: 40px;">
                        <i class="ph ph-magnifying-glass" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                        No employees match your filter criteria.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="empPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="empPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="empPerPageSelect" onchange="empChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="empPageNav"></nav>
    </div>
</div>

<style>
/* Compact Header & Navigation Margin Overrides */
.hr-parent-header {
    margin-bottom: 6px !important;
}
.hr-parent-header .hr-parent-title-row {
    margin-bottom: 4px !important;
}
.hr-parent-header .hr-parent-title {
    font-size: 20px !important;
    gap: 8px !important;
}
.hr-parent-header .hr-parent-title i {
    width: 32px !important;
    height: 32px !important;
    font-size: 17px !important;
    border-radius: 8px !important;
}
.hr-parent-header .hr-parent-subtitle {
    font-size: 12px !important;
    margin-top: 2px !important;
}
.hr-parent-header .hr-tabs-wrapper {
    margin-top: 6px !important;
    margin-bottom: 8px !important;
    padding: 3px 5px !important;
    gap: 4px !important;
    border-radius: 10px !important;
}
.hr-parent-header .hr-tab-item {
    padding: 5px 12px !important;
    font-size: 12.5px !important;
}



/* Compact Table Density for Higher Viewport Capacity */
#employeesDirectoryTable th {
    padding: 9px 12px !important;
    font-size: 11.5px !important;
}
#employeesDirectoryTable td {
    padding: 8px 12px !important;
    font-size: 12.5px !important;
}
#employeesTableWrapper {
    max-height: calc(100vh - 280px);
}
.emp-name-link:hover {
    color: #7c3aed !important;
    text-decoration: underline !important;
}
.hr-action-menu-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
}
.hr-action-menu-btn:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}
.hr-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 7px 10px;
    color: #334155;
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
    border: none;
    background: transparent;
    cursor: pointer;
    text-align: left;
    transition: background 0.12s ease, color 0.12s ease;
}
.hr-dropdown-item:hover {
    background: #f1f5f9;
    color: #7c3aed;
}
.hr-dropdown-item i {
    font-size: 14px;
    color: #64748b;
}
.hr-dropdown-item:hover i {
    color: #7c3aed;
}
.hr-role-pill-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 12px;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}
.hr-role-pill-label:hover {
    border-color: #a855f7;
    background: #faf5ff;
}
.hr-role-pill-label:has(input:checked) {
    background: #f5f3ff;
    border-color: #7c3aed;
    color: #6b21a8;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(124, 58, 237, 0.12);
}
.hr-custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.hr-custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.hr-custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.hr-custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.modal-sec-title {
    font-size: 12px;
    font-weight: 700;
    color: #7c3aed;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 18px 0 10px 0;
    padding-bottom: 4px;
    border-bottom: 1px solid #f1f5f9;
}
</style>

<!-- Quick Action Modal: Change Position -->
<div id="changePositionQuickModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 520px;">
        <form id="changePositionQuickForm" method="POST" action="">
            @csrf
            <div class="hr-modal-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 19px; border: 1px solid #ede9fe; flex-shrink: 0;">
                        <i class="ph ph-briefcase"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" style="font-size: 16px; font-weight: 650; color: #0f172a; margin: 0;">Change Position</span>
                        <div style="font-size: 12px; color: #64748b; margin-top: 1px;">Update primary position and assigned roles</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('changePositionQuickModal')" title="Close"><i class="ph ph-x"></i></button>
            </div>
            <div class="hr-modal-body">
                <!-- Employee Card -->
                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; flex-shrink: 0;" id="cpQuickEmpAvatar">
                        <i class="ph ph-user"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.04em;">Employee</div>
                        <div style="font-size: 14px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="cpQuickEmpNameDisplay">Employee Name</div>
                        <input type="hidden" id="cpQuickEmpName">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 600; color: #334155; margin-bottom: 6px;">
                        New Primary Position <span class="text-danger">*</span>
                    </label>
                    <select name="position_id" id="cpQuickPositionId" class="hr-select" required onchange="const cb = document.getElementById('cp_quick_pos_' + this.value); if(cb) { cb.checked = true; updateCpQuickCount(); }">
                        <option value="">-- Select Position --</option>
                        @foreach($positions->unique('name') as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="hr-form-group" style="background: #fafafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0; font-size: 12.5px;">
                            Assigned Positions & Roles
                        </label>
                        <span style="font-size: 11.5px; color: #7c3aed; font-weight: 600;" id="cpQuickSelectedCount">0 selected</span>
                    </div>
                    <div style="font-size: 11.5px; color: #64748b; margin-bottom: 10px;">Select all positions / roles this employee can perform:</div>
                    <div style="max-height: 140px; overflow-y: auto; padding-right: 4px; display: flex; flex-wrap: wrap; gap: 6px;" class="hr-custom-scrollbar">
                        @foreach($positions->unique('name') as $p)
                            <label class="hr-role-pill-label">
                                <input type="checkbox" name="assigned_position_ids[]" value="{{ $p->id }}" class="cp-quick-pos-cb" id="cp_quick_pos_{{ $p->id }}" onchange="updateCpQuickCount()">
                                <span>{{ $p->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Effective Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Reason / Notes
                    </label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Promotion, Lateral Reassignment"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('changePositionQuickModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);">
                    <i class="ph ph-check"></i> Update Position
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Action Modal: Transfer Branch / Department -->
<div id="transferQuickModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 540px;">
        <form id="transferQuickForm" method="POST" action="">
            @csrf
            <div class="hr-modal-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 19px; border: 1px solid #dbeafe; flex-shrink: 0;">
                        <i class="ph ph-arrows-left-right"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" style="font-size: 16px; font-weight: 650; color: #0f172a; margin: 0;">Transfer Employee</span>
                        <div style="font-size: 12px; color: #64748b; margin-top: 1px;">Reassign branch or department locations</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('transferQuickModal')" title="Close"><i class="ph ph-x"></i></button>
            </div>
            <div class="hr-modal-body">
                <!-- Employee Card -->
                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; flex-shrink: 0;" id="trQuickEmpAvatar">
                        <i class="ph ph-user"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.04em;">Employee</div>
                        <div style="font-size: 14px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="trQuickEmpNameDisplay">Employee Name</div>
                        <input type="hidden" id="trQuickEmpName">
                    </div>
                </div>

                <!-- Branch Assignment Card -->
                <div class="hr-form-group" style="background: #fafafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <label class="hr-form-label" style="font-weight: 600; color: #1e293b; margin-bottom: 6px; font-size: 12.5px;">
                        Primary Branch <span class="text-danger">*</span>
                    </label>
                    <select name="branch_id" id="trQuickBranchId" class="hr-select" required onchange="const cb = document.getElementById('tr_quick_branch_' + this.value); if(cb) { cb.checked = true; updateTrQuickCounts(); }">
                        <option value="">-- Select Primary Branch --</option>
                        @foreach($branches->unique('name') as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px; margin-bottom: 6px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: #475569;">Assigned Branches:</span>
                        <span style="font-size: 11.5px; color: #2563eb; font-weight: 600;" id="trQuickBranchesCount">0 selected</span>
                    </div>
                    <div style="max-height: 120px; overflow-y: auto; padding-right: 4px; display: flex; flex-wrap: wrap; gap: 6px;" class="hr-custom-scrollbar">
                        @foreach($branches->unique('name') as $b)
                            <label class="hr-role-pill-label">
                                <input type="checkbox" name="assigned_branch_ids[]" value="{{ $b->id }}" class="tr-quick-branch-cb" id="tr_quick_branch_{{ $b->id }}" onchange="updateTrQuickCounts()">
                                <span>{{ $b->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Department Assignment Card -->
                <div class="hr-form-group" style="background: #fafafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin-bottom: 6px; font-size: 12.5px;">
                        Primary Department
                    </label>
                    <select name="department_id" id="trQuickDeptId" class="hr-select" onchange="const cb = document.getElementById('tr_quick_dept_' + this.value); if(cb) { cb.checked = true; updateTrQuickCounts(); }">
                        <option value="">-- Keep Current Department --</option>
                        @foreach($departments->unique('name') as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px; margin-bottom: 6px;">
                        <span style="font-size: 11.5px; font-weight: 600; color: #475569;">Assigned Departments:</span>
                        <span style="font-size: 11.5px; color: #2563eb; font-weight: 600;" id="trQuickDeptsCount">0 selected</span>
                    </div>
                    <div style="max-height: 120px; overflow-y: auto; padding-right: 4px; display: flex; flex-wrap: wrap; gap: 6px;" class="hr-custom-scrollbar">
                        @foreach($departments->unique('name') as $d)
                            <label class="hr-role-pill-label">
                                <input type="checkbox" name="assigned_department_ids[]" value="{{ $d->id }}" class="tr-quick-dept-cb" id="tr_quick_dept_{{ $d->id }}" onchange="updateTrQuickCounts()">
                                <span>{{ $d->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Effective Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Reason / Justification
                    </label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Branch Rebalancing, Department Transfer"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('transferQuickModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                    <i class="ph ph-check"></i> Apply Transfer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Action Modal: Change Employment Status -->
<div id="changeStatusQuickModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <form id="changeStatusQuickForm" method="POST" action="">
            @csrf
            <div class="hr-modal-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 19px; border: 1px solid #fde68a; flex-shrink: 0;">
                        <i class="ph ph-arrows-clockwise"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" style="font-size: 16px; font-weight: 650; color: #0f172a; margin: 0;">Change Employment Status</span>
                        <div style="font-size: 12px; color: #64748b; margin-top: 1px;">Update status and transition history</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('changeStatusQuickModal')" title="Close"><i class="ph ph-x"></i></button>
            </div>
            <div class="hr-modal-body">
                <!-- Employee Card -->
                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; flex-shrink: 0;" id="csQuickEmpAvatar">
                        <i class="ph ph-user"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.04em;">Employee</div>
                        <div style="font-size: 14px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="csQuickEmpNameDisplay">Employee Name</div>
                        <input type="hidden" id="csQuickEmpName">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 600; color: #334155; margin-bottom: 6px;">
                        New Status <span class="text-danger">*</span>
                    </label>
                    <select name="employment_status" id="csQuickStatus" class="hr-select" required>
                        <option value="Active">Active</option>
                        <option value="Probationary">Probationary</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Suspended">Suspended</option>
                        <option value="Resigned">Resigned</option>
                        <option value="Terminated">Terminated</option>
                        <option value="Retired">Retired</option>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Effective Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="effective_date" class="hr-input" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Reason / Notes
                    </label>
                    <textarea name="reason" class="hr-input" rows="2" placeholder="e.g. Regularization, Leave of Absence, Resignation"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('changeStatusQuickModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
                    <i class="ph ph-check"></i> Update Status
                </button>
            </div>
        </form>
    </div>
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
                        <label class="hr-form-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employee ID <span class="text-danger">*</span></span>
                            <span style="font-size: 10.5px; font-weight: 600; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; padding: 1px 6px; border-radius: 4px;">
                                <i class="ph ph-lock-simple"></i> Auto-Generated
                            </span>
                        </label>
                        <input type="text" name="employee_id" class="hr-input" required readonly value="{{ $nextEmployeeId ?? 'EMP-' . date('Y') . '-011' }}" style="background: #f8fafc; color: #475569; cursor: not-allowed; font-weight: 600; border-color: #cbd5e1; user-select: none;">
                        <small style="font-size: 11px; color: #64748b; margin-top: 3px; display: block;">
                            Assigned automatically by the system. Cannot be edited.
                        </small>
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
                        <select name="nationality" class="hr-select" required>
                            @foreach(['Filipino', 'American', 'Australian', 'British', 'Canadian', 'Chinese', 'French', 'German', 'Indian', 'Indonesian', 'Irish', 'Italian', 'Japanese', 'Korean', 'Malaysian', 'New Zealander', 'Russian', 'Singaporean', 'Spanish', 'Swiss', 'Taiwanese', 'Thai', 'Vietnamese', 'Other'] as $nat)
                                <option value="{{ $nat }}" {{ $nat === 'Filipino' ? 'selected' : '' }}>{{ $nat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Mobile Number</label>
                        <input type="tel" name="mobile_number" class="hr-input" placeholder="0917-xxx-xxxx or +63 9xx xxx xxxx" pattern="[+]?[\d\s\-()]{7,25}">
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
                    <!-- Linked User Account -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Linked User Account (Optional)</label>
                        <select name="user_id" class="hr-select">
                            <option value="">-- No User Account --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->full_name }} (@ {{ $u->username }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Multi-Branch Assignment -->
                    <div class="hr-form-group" style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0;">
                                <i class="ph ph-map-pin" style="color: #6366f1;"></i> Branch Assignments *
                            </label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px; color: #64748b; font-weight: 600;">Primary Branch:</span>
                                <select name="branch_id" id="add_primary_branch_id" class="hr-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="syncAddPrimaryCheckbox('branch', this.value)" required>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Assign one or multiple branches this employee will work in:</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($branches as $b)
                                <label class="hr-role-pill-label">
                                    <input type="checkbox" name="assigned_branch_ids[]" value="{{ $b->id }}" id="cb_add_branch_{{ $b->id }}" {{ $loop->first ? 'checked' : '' }}>
                                    <span>{{ $b->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Multi-Department Assignment -->
                    <div class="hr-form-group" style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0;">
                                <i class="ph ph-buildings" style="color: #8b5cf6;"></i> Department Assignments
                            </label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px; color: #64748b; font-weight: 600;">Primary Dept:</span>
                                <select name="department_id" id="add_primary_department_id" class="hr-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="syncAddPrimaryCheckbox('dept', this.value)">
                                    <option value="">Select Primary</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Assign multiple departments to enable dynamic &lt;&gt; navigation on Schedule:</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($departments as $d)
                                <label class="hr-role-pill-label">
                                    <input type="checkbox" name="assigned_department_ids[]" value="{{ $d->id }}" id="cb_add_dept_{{ $d->id }}">
                                    <span>{{ $d->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Multi-Position Assignment -->
                    <div class="hr-form-group" style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0;">
                                <i class="ph ph-briefcase" style="color: #3b82f6;"></i> Position & Role Assignments
                            </label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px; color: #64748b; font-weight: 600;">Primary Position:</span>
                                <select name="position_id" id="add_primary_position_id" class="hr-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="syncAddPrimaryCheckbox('pos', this.value)">
                                    <option value="">Select Primary</option>
                                    @foreach($positions as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Assign multiple positions / roles that this employee can perform:</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($positions as $p)
                                <label class="hr-role-pill-label">
                                    <input type="checkbox" name="assigned_position_ids[]" value="{{ $p->id }}" id="cb_add_pos_{{ $p->id }}">
                                    <span>{{ $p->name }}</span>
                                </label>
                            @endforeach
                        </div>
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
                        <label class="hr-form-label">Payroll Type *</label>
                        <select name="salary_type" class="hr-select" required>
                            <option value="Daily">Daily</option>
                            <option value="Monthly" selected>Monthly</option>
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
                        <input type="tel" name="emergency_contact_phone" class="hr-input" placeholder="0918-xxx-xxxx or +63 9xx xxx xxxx" pattern="[+]?[\d\s\-()]{7,25}">
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

<!-- Modal: Edit Employee Profile -->
<div id="editEmployeeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 800px;">
        <div class="hr-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 9px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="ph ph-pencil-simple"></i>
                </div>
                <span class="hr-modal-title" style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Edit Employee Profile</span>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('editEmployeeModal')" title="Close"><i class="ph ph-x"></i></button>
        </div>
        <form id="editEmployeeForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="hr-modal-body hr-custom-scrollbar" style="max-height: calc(100vh - 145px); overflow-y: auto; padding: 20px 24px;">
                
                <h4 class="modal-sec-title" style="margin-top: 0;">1. Basic Information</h4>
                <div class="hr-form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">First Name *</label>
                        <input type="text" name="first_name" id="edit_emp_first_name" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Middle Name</label>
                        <input type="text" name="middle_name" id="edit_emp_middle_name" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Last Name *</label>
                        <input type="text" name="last_name" id="edit_emp_last_name" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Suffix</label>
                        <input type="text" name="suffix" id="edit_emp_suffix" class="hr-input" placeholder="Jr., III">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Preferred Name / Nickname</label>
                        <input type="text" name="preferred_name" id="edit_emp_preferred_name" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="edit_emp_date_of_birth" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Place of Birth</label>
                        <input type="text" name="birth_place" id="edit_emp_birth_place" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Sex / Gender</label>
                        <select name="gender" id="edit_emp_gender" class="hr-select">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Civil Status</label>
                        <select name="civil_status" id="edit_emp_civil_status" class="hr-select">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                    <div class="hr-form-group" style="grid-column: 1 / 2;">
                        <label class="hr-form-label">Nationality</label>
                        <select name="nationality" id="edit_emp_nationality" class="hr-select">
                            @foreach(['Filipino', 'American', 'Australian', 'British', 'Canadian', 'Chinese', 'French', 'German', 'Indian', 'Indonesian', 'Irish', 'Italian', 'Japanese', 'Korean', 'Malaysian', 'New Zealander', 'Russian', 'Singaporean', 'Spanish', 'Swiss', 'Taiwanese', 'Thai', 'Vietnamese', 'Other'] as $nat)
                                <option value="{{ $nat }}">{{ $nat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h4 class="modal-sec-title">2. Contact & Addresses</h4>
                <div class="hr-form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Personal Email</label>
                        <input type="email" name="personal_email" id="edit_emp_personal_email" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Company Email</label>
                        <input type="email" name="company_email" id="edit_emp_company_email" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Mobile Number</label>
                        <input type="tel" name="mobile_number" id="edit_emp_mobile_number" class="hr-input" placeholder="0917-xxx-xxxx or +63 9xx xxx xxxx" pattern="[+]?[\d\s\-()]{7,25}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Telephone Number</label>
                        <input type="tel" name="telephone_number" id="edit_emp_telephone_number" class="hr-input" placeholder="(02) 8xxx-xxxx" pattern="[+]?[\d\s\-()]{7,25}">
                    </div>
                    <div class="hr-form-group" style="grid-column: 1 / -1;">
                        <label class="hr-form-label">Current Address</label>
                        <input type="text" name="address" id="edit_emp_address" class="hr-input">
                    </div>
                    <div class="hr-form-group" style="grid-column: 1 / -1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <label class="hr-form-label" style="margin-bottom: 0;">Permanent Address</label>
                            <label style="font-size: 12px; color: #7c3aed; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <input type="checkbox" id="edit_sameAddressCb" onchange="if(this.checked){ document.getElementById('edit_emp_permanent_address').value = document.getElementById('edit_emp_address').value; }">
                                Same as Current Address
                            </label>
                        </div>
                        <input type="text" name="permanent_address" id="edit_emp_permanent_address" class="hr-input">
                    </div>
                </div>

                <h4 class="modal-sec-title">3. Employment & Assignments</h4>

                <!-- Multi-Branch Assignment -->
                <div class="hr-form-group" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0;">
                            <i class="ph ph-storefront" style="color: #7c3aed;"></i> Branch Assignments
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 11px; color: #64748b; font-weight: 600;">Primary Branch:</span>
                            <select name="branch_id" id="edit_primary_branch_id" class="hr-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="syncEditEmpPrimaryCheckbox('branch', this.value)" required>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Assign one or multiple branches this employee works in:</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($branches as $b)
                            <label class="hr-role-pill-label">
                                <input type="checkbox" name="assigned_branch_ids[]" value="{{ $b->id }}" id="cb_edit_emp_branch_{{ $b->id }}" class="edit-emp-branch-cb">
                                <span>{{ $b->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Multi-Department Assignment -->
                <div class="hr-form-group" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0;">
                            <i class="ph ph-buildings" style="color: #8b5cf6;"></i> Department Assignments
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 11px; color: #64748b; font-weight: 600;">Primary Dept:</span>
                            <select name="department_id" id="edit_primary_department_id" class="hr-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="syncEditEmpPrimaryCheckbox('dept', this.value)">
                                <option value="">Select Primary</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Assign one or multiple departments (enables multi-department schedule toggling):</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($departments as $d)
                            <label class="hr-role-pill-label">
                                <input type="checkbox" name="assigned_department_ids[]" value="{{ $d->id }}" id="cb_edit_emp_dept_{{ $d->id }}" class="edit-emp-dept-cb">
                                <span>{{ $d->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Multi-Position Assignment -->
                <div class="hr-form-group" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <label class="hr-form-label" style="font-weight: 700; color: #1e293b; margin: 0;">
                            <i class="ph ph-briefcase" style="color: #3b82f6;"></i> Position & Role Assignments
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 11px; color: #64748b; font-weight: 600;">Primary Position:</span>
                            <select name="position_id" id="edit_primary_position_id" class="hr-select" style="padding: 4px 8px; font-size: 12px; width: auto;" onchange="syncEditEmpPrimaryCheckbox('pos', this.value)">
                                <option value="">Select Primary</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Assign one or multiple positions / roles this employee can perform:</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($positions as $p)
                            <label class="hr-role-pill-label">
                                <input type="checkbox" name="assigned_position_ids[]" value="{{ $p->id }}" id="cb_edit_emp_pos_{{ $p->id }}" class="edit-emp-pos-cb">
                                <span>{{ $p->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hr-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Job Level</label>
                        <input type="text" name="job_level" id="edit_emp_job_level" class="hr-input" value="Staff">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Supervisor</label>
                        <select name="supervisor_id" id="edit_emp_supervisor_id" class="hr-select">
                            <option value="">-- No Direct Supervisor --</option>
                            @foreach($supervisors ?? [] as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" id="edit_emp_employment_type" class="hr-select" required>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Contractual">Contractual</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Casual">Casual</option>
                            <option value="Seasonal">Seasonal</option>
                            <option value="Intern / OJT">Intern / OJT</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Status *</label>
                        <select name="employment_status" id="edit_emp_employment_status" class="hr-select" required>
                            <option value="Active">Active</option>
                            <option value="Probationary">Probationary</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Suspended">Suspended</option>
                            <option value="Resigned">Resigned</option>
                            <option value="Terminated">Terminated</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>
                    <div class="hr-form-group" style="grid-column: 1 / -1;">
                        <label class="hr-form-label">Employment Source</label>
                        <div style="display: flex; gap: 16px; align-items: center; margin-top: 4px; margin-bottom: 8px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="employment_source" id="edit_emp_src_company" value="Company" onchange="toggleEditEmpSource('Company')">
                                <span>Company</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                                <input type="radio" name="employment_source" id="edit_emp_src_agency" value="Agency" onchange="toggleEditEmpSource('Agency')">
                                <span>Agency</span>
                            </label>
                        </div>
                        <div id="edit_emp_company_box">
                            <select name="company_name" id="edit_company_name" class="hr-select">
                                <option value="">-- Select Company --</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->name }}">{{ $c->name }} ({{ $c->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="edit_emp_agency_box" style="display: none;">
                            <select name="agency_name" id="edit_agency_name" class="hr-select">
                                <option value="">-- Select Agency --</option>
                                @foreach($agencies as $a)
                                    <option value="{{ $a->name }}">{{ $a->name }} ({{ $a->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Work Location</label>
                        <input type="text" name="work_location" id="edit_emp_work_location" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Work Schedule</label>
                        <input type="text" name="work_schedule" id="edit_emp_work_schedule" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date Hired *</label>
                        <input type="date" name="date_hired" id="edit_emp_date_hired" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Regularization Date</label>
                        <input type="date" name="date_of_regularization" id="edit_emp_date_of_regularization" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Basic Salary (₱) *</label>
                        <input type="number" step="0.01" name="basic_salary" id="edit_emp_basic_salary" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Payroll Type</label>
                        <select name="salary_type" id="edit_emp_salary_type" class="hr-select">
                            <option value="Daily">Daily</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Hourly">Hourly</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency</label>
                        <select name="pay_frequency" id="edit_emp_pay_frequency" class="hr-select">
                            <option value="Semi-Monthly">Semi-Monthly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Allowances (₱)</label>
                        <input type="number" step="0.01" name="allowances" id="edit_emp_allowances" class="hr-input" value="0.00">
                    </div>
                </div>

                <h4 class="modal-sec-title">4. Statutory & Government IDs</h4>
                <div class="hr-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">SSS Number</label>
                        <input type="text" name="sss_number" id="edit_emp_sss_number" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">PhilHealth Number</label>
                        <input type="text" name="philhealth_number" id="edit_emp_philhealth_number" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Pag-IBIG Number</label>
                        <input type="text" name="pagibig_number" id="edit_emp_pagibig_number" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">TIN Number</label>
                        <input type="text" name="tin" id="edit_emp_tin" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">RDO Code</label>
                        <input type="text" name="rdo_code" id="edit_emp_rdo_code" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">PhilSys / National ID</label>
                        <input type="text" name="philsys_id" id="edit_emp_philsys_id" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Passport Number</label>
                        <input type="text" name="passport_number" id="edit_emp_passport_number" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Driver's License</label>
                        <input type="text" name="driver_license" id="edit_emp_driver_license" class="hr-input">
                    </div>
                </div>

                <h4 class="modal-sec-title">5. Profile Photo</h4>
                <div class="hr-form-group" style="background: rgba(248, 250, 252, 0.85); border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 12px 16px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div id="editEmpPhotoPreviewWrap">
                            <img id="editEmpPhotoPreviewImg" src="" alt="Preview" style="display: none; width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div id="editEmpPhotoAvatarPlaceholder" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; box-shadow: 0 2px 8px rgba(168, 85, 247, 0.25);">
                                <i class="ph ph-user"></i>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <input type="file" name="photo" id="editEmpPhotoInput" class="hr-input" accept="image/*" onchange="previewEditEmpPhoto(this)" style="padding: 7px 10px;">
                            <small style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                                Upload a new photo (JPEG, PNG, WEBP up to 5MB) or leave blank to keep current photo.
                            </small>
                        </div>
                    </div>
                </div>

            </div>
            <div class="hr-modal-footer" style="padding: 16px 24px; background: #ffffff; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editEmployeeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); color: #ffffff; font-weight: 600; padding: 9px 20px; border-radius: 9px;">
                    Save Changes
                </button>
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
// Client-Side Pagination State
// =========================================================================
let _empCurrentPage  = 1;
let _empFilteredRows = []; // holds references to all currently-matching <tr> elements

function empGetPerPage() {
    return parseInt(document.getElementById('empPerPageSelect')?.value || '15', 10);
}

function empChangePerPage() {
    _empCurrentPage = 1;
    renderEmpPage();
}

/**
 * Renders the current page of _empFilteredRows into the tbody
 * and updates the pagination bar.
 */
function renderEmpPage() {
    const perPage    = empGetPerPage();
    const total      = _empFilteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (_empCurrentPage > totalPages) _empCurrentPage = totalPages;

    const start = (_empCurrentPage - 1) * perPage; // 0-based
    const end   = Math.min(start + perPage, total);

    // Show/hide rows
    const allRows = document.querySelectorAll('#employeesTableBody tr.emp-row');
    allRows.forEach(r => { r.style.display = 'none'; });
    _empFilteredRows.forEach((r, idx) => {
        r.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    // No-results row
    const noResultsRow = document.getElementById('noEmpResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = (total === 0) ? '' : 'none';
    }

    // Visible count badge
    const countElem = document.getElementById('empVisibleCount');
    if (countElem) countElem.textContent = total;

    // Pagination bar visibility
    const bar = document.getElementById('empPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';

    // Info text
    const info = document.getElementById('empPaginationInfo');
    if (info) {
        const from = total === 0 ? 0 : start + 1;
        info.innerHTML = `Showing <strong>${from}</strong> to <strong>${end}</strong> of <strong>${total}</strong> employees`;
    }

    // Build page-number nav
    const nav = document.getElementById('empPageNav');
    if (!nav) return;

    let html = '';

    // Prev button
    if (_empCurrentPage === 1) {
        html += `<span class="hr-page-btn disabled" aria-disabled="true"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    } else {
        html += `<span class="hr-page-btn" onclick="empGoToPage(${_empCurrentPage - 1})" style="cursor:pointer;"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    }

    // Page numbers (windowed: show up to 5 around current)
    html += `<div class="hr-page-numbers">`;
    const window_size = 2;
    let pageStart = Math.max(1, _empCurrentPage - window_size);
    let pageEnd   = Math.min(totalPages, _empCurrentPage + window_size);
    if (pageStart > 1) {
        html += `<span class="hr-page-num" onclick="empGoToPage(1)" style="cursor:pointer;">1</span>`;
        if (pageStart > 2) html += `<span class="hr-page-num dots">…</span>`;
    }
    for (let p = pageStart; p <= pageEnd; p++) {
        if (p === _empCurrentPage) {
            html += `<span class="hr-page-num active" aria-current="page">${p}</span>`;
        } else {
            html += `<span class="hr-page-num" onclick="empGoToPage(${p})" style="cursor:pointer;">${p}</span>`;
        }
    }
    if (pageEnd < totalPages) {
        if (pageEnd < totalPages - 1) html += `<span class="hr-page-num dots">…</span>`;
        html += `<span class="hr-page-num" onclick="empGoToPage(${totalPages})" style="cursor:pointer;">${totalPages}</span>`;
    }
    html += `</div>`;

    // Next button
    if (_empCurrentPage >= totalPages) {
        html += `<span class="hr-page-btn disabled" aria-disabled="true"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    } else {
        html += `<span class="hr-page-btn" onclick="empGoToPage(${_empCurrentPage + 1})" style="cursor:pointer;"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    }

    nav.innerHTML = html;
}

function empGoToPage(page) {
    _empCurrentPage = page;
    renderEmpPage();
    // Scroll table back to top on page change
    const wrapper = document.getElementById('employeesTableWrapper');
    if (wrapper) wrapper.scrollTop = 0;
}

// =========================================================================
// Real-Time Table Filter (No Enter Key or Submit Button Required)
// =========================================================================
function toggleMoreFilters() {
    const p = document.getElementById('moreFiltersPanel');
    const b = document.getElementById('moreFiltersBtn');
    if (!p) return;
    if (p.style.display === 'none' || !p.style.display) {
        p.style.display = 'flex';
        b.classList.add('active');
    } else {
        p.style.display = 'none';
        b.classList.remove('active');
    }
}

function filterEmployeesDirectory() {
    const searchVal  = (document.getElementById('empSearchInput')?.value || '').toLowerCase().trim();
    const branchVal  = (document.getElementById('empBranchFilter')?.value || '').trim();
    const deptVal    = (document.getElementById('empDeptFilter')?.value || '').trim();
    const posVal     = (document.getElementById('empPositionFilter')?.value || '').trim();
    const typeVal    = (document.getElementById('empTypeFilter')?.value || '').trim();
    const statusVal  = (document.getElementById('empStatusFilter')?.value || '').trim();
    const companyVal = (document.getElementById('empCompanyFilter')?.value || '').trim();

    const allRows = document.querySelectorAll('#employeesTableBody tr.emp-row');

    _empFilteredRows = Array.from(allRows).filter(row => {
        const id      = row.getAttribute('data-id')       || '';
        const name    = row.getAttribute('data-name')     || '';
        const email   = row.getAttribute('data-email')    || '';
        const branch  = row.getAttribute('data-branch')   || '';
        const dept    = row.getAttribute('data-dept')     || '';
        const pos     = row.getAttribute('data-position') || '';
        const type    = row.getAttribute('data-type')     || '';
        const status  = row.getAttribute('data-status')   || '';
        const company = row.getAttribute('data-company')  || '';

        const matchesSearch = !searchVal ||
            id.includes(searchVal) ||
            name.includes(searchVal) ||
            email.includes(searchVal) ||
            pos.toLowerCase().includes(searchVal) ||
            dept.toLowerCase().includes(searchVal) ||
            branch.toLowerCase().includes(searchVal);

        const allBranch = (row.getAttribute('data-all-branch-names') || branch).toLowerCase();
        const allDept   = (row.getAttribute('data-all-dept-names')   || dept).toLowerCase();
        const allPos    = (row.getAttribute('data-all-pos-names')    || pos).toLowerCase();

        const matchesBranch  = !branchVal  || branch.toLowerCase() === branchVal.toLowerCase() || allBranch.includes(branchVal.toLowerCase());
        const matchesDept    = !deptVal    || dept.toLowerCase() === deptVal.toLowerCase() || allDept.includes(deptVal.toLowerCase());
        const matchesPos     = !posVal     || pos.toLowerCase() === posVal.toLowerCase() || allPos.includes(posVal.toLowerCase());
        const matchesType    = !typeVal    || type.toLowerCase() === typeVal.toLowerCase();
        const matchesStatus  = !statusVal  || status.toLowerCase() === statusVal.toLowerCase();
        const matchesCompany = !companyVal || company.toLowerCase() === companyVal.toLowerCase();

        return matchesSearch && matchesBranch && matchesDept && matchesPos && matchesType && matchesStatus && matchesCompany;
    });

    _empCurrentPage = 1;
    renderEmpPage();
}

// Run pagination on page load
document.addEventListener('DOMContentLoaded', () => {
    const cVal = document.getElementById('empCompanyFilter')?.value;
    if (cVal) {
        const p = document.getElementById('moreFiltersPanel');
        const b = document.getElementById('moreFiltersBtn');
        if (p) { p.style.display = 'flex'; if (b) b.classList.add('active'); }
    }
    filterEmployeesDirectory();
});

function resetEmployeesDirectory() {
    const s = document.getElementById('empSearchInput');
    if (s) s.value = '';
    const b = document.getElementById('empBranchFilter');
    if (b) b.value = '';
    const d = document.getElementById('empDeptFilter');
    if (d) d.value = '';
    const p = document.getElementById('empPositionFilter');
    if (p) p.value = '';
    const t = document.getElementById('empTypeFilter');
    if (t) t.value = '';
    const st = document.getElementById('empStatusFilter');
    if (st) st.value = '';
    const c = document.getElementById('empCompanyFilter');
    if (c) c.value = '';
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
        'dept_asc': [2, 'text', 'asc'],
        'pos_asc': [3, 'text', 'asc'],
        'branch_asc': [4, 'text', 'asc'],
        'type_asc': [5, 'text', 'asc'],
        'date_desc': [6, 'date', 'desc'],
        'date_asc': [6, 'date', 'asc'],
        'status_asc': [7, 'text', 'asc'],
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
            '2_asc': 'dept_asc', '2_desc': 'dept_asc',
            '3_asc': 'pos_asc', '3_desc': 'pos_asc',
            '4_asc': 'branch_asc', '4_desc': 'branch_asc',
            '5_asc': 'type_asc', '5_desc': 'type_asc',
            '6_asc': 'date_asc', '6_desc': 'date_desc',
            '7_asc': 'status_asc', '7_desc': 'status_asc'
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
            valA = a.getAttribute('data-dept') || '';
            valB = b.getAttribute('data-dept') || '';
        } else if (colIndex === 3) {
            valA = a.getAttribute('data-position') || '';
            valB = b.getAttribute('data-position') || '';
        } else if (colIndex === 4) {
            valA = a.getAttribute('data-branch') || '';
            valB = b.getAttribute('data-branch') || '';
        } else if (colIndex === 5) {
            valA = a.getAttribute('data-type') || '';
            valB = b.getAttribute('data-type') || '';
        } else if (colIndex === 6) {
            valA = parseFloat(a.getAttribute('data-date')) || 0;
            valB = parseFloat(b.getAttribute('data-date')) || 0;
            return empSortDir === 'asc' ? valA - valB : valB - valA;
        } else if (colIndex === 7) {
            valA = a.getAttribute('data-status') || '';
            valB = b.getAttribute('data-status') || '';
        }

        const cmp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
        return empSortDir === 'asc' ? cmp : -cmp;
    });

    const noResultsRow = document.getElementById('noEmpResultsRow');
    rows.forEach(r => tableBody.appendChild(r));
    if (noResultsRow) tableBody.appendChild(noResultsRow);

    // Re-apply filter + pagination after sort reorders the DOM
    filterEmployeesDirectory();
}

function syncAddPrimaryCheckbox(type, id) {
    if (!id) return;
    const cb = document.getElementById('cb_add_' + type + '_' + id);
    if (cb) cb.checked = true;
}

// Action dropdown & quick modals
function toggleEmpActionMenu(event, menuId) {
    event.stopPropagation();
    event.preventDefault();
    const current = document.getElementById(menuId);
    if (!current) return;
    const isVisible = current.style.display === 'block';
    document.querySelectorAll('.hr-action-dropdown').forEach(d => d.style.display = 'none');
    if (!isVisible) {
        current.style.display = 'block';
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.hr-action-menu-wrap')) {
        document.querySelectorAll('.hr-action-dropdown').forEach(d => d.style.display = 'none');
    }
});

function updateCpQuickCount() {
    const checked = document.querySelectorAll('.cp-quick-pos-cb:checked').length;
    const badge = document.getElementById('cpQuickSelectedCount');
    if (badge) badge.innerText = `${checked} selected`;
}

function updateTrQuickCounts() {
    const bChecked = document.querySelectorAll('.tr-quick-branch-cb:checked').length;
    const bBadge = document.getElementById('trQuickBranchesCount');
    if (bBadge) bBadge.innerText = `${bChecked} selected`;

    const dChecked = document.querySelectorAll('.tr-quick-dept-cb:checked').length;
    const dBadge = document.getElementById('trQuickDeptsCount');
    if (dBadge) dBadge.innerText = `${dChecked} selected`;
}

function syncCpQuickPrimary(posId) {
    if (!posId) return;
    const cb = document.getElementById('cp_quick_pos_' + posId);
    if (cb) {
        cb.checked = true;
        updateCpQuickCount();
    }
}

function openChangePositionModal(empId, empName, currentPosId, assignedPosIds) {
    const form = document.getElementById('changePositionQuickForm');
    form.action = `/hr/people/employees/${empId}/change-position`;
    document.getElementById('cpQuickEmpName').value = empName;
    const display = document.getElementById('cpQuickEmpNameDisplay');
    if (display) display.innerText = empName;
    const avatar = document.getElementById('cpQuickEmpAvatar');
    if (avatar) {
        const inits = empName.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        avatar.innerText = inits || 'EM';
    }
    if (currentPosId) document.getElementById('cpQuickPositionId').value = currentPosId;

    const assigned = Array.isArray(assignedPosIds) ? assignedPosIds : (currentPosId ? [currentPosId] : []);
    document.querySelectorAll('.cp-quick-pos-cb').forEach(cb => {
        cb.checked = assigned.includes(parseInt(cb.value));
    });
    updateCpQuickCount();

    openModal('changePositionQuickModal');
}

function openTransferModal(empId, empName, currentDeptId, currentBranchId, assignedDeptIds, assignedBranchIds) {
    const form = document.getElementById('transferQuickForm');
    form.action = `/hr/people/employees/${empId}/transfer`;
    document.getElementById('trQuickEmpName').value = empName;
    const display = document.getElementById('trQuickEmpNameDisplay');
    if (display) display.innerText = empName;
    const avatar = document.getElementById('trQuickEmpAvatar');
    if (avatar) {
        const inits = empName.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        avatar.innerText = inits || 'EM';
    }
    if (currentDeptId) document.getElementById('trQuickDeptId').value = currentDeptId;
    if (currentBranchId) document.getElementById('trQuickBranchId').value = currentBranchId;

    const assignedBranches = Array.isArray(assignedBranchIds) ? assignedBranchIds : (currentBranchId ? [currentBranchId] : []);
    document.querySelectorAll('.tr-quick-branch-cb').forEach(cb => {
        cb.checked = assignedBranches.includes(parseInt(cb.value));
    });

    const assignedDepts = Array.isArray(assignedDeptIds) ? assignedDeptIds : (currentDeptId ? [currentDeptId] : []);
    document.querySelectorAll('.tr-quick-dept-cb').forEach(cb => {
        cb.checked = assignedDepts.includes(parseInt(cb.value));
    });
    updateTrQuickCounts();

    openModal('transferQuickModal');
}

function openChangeStatusModal(empId, empName, currentStatus) {
    const form = document.getElementById('changeStatusQuickForm');
    form.action = `/hr/people/employees/${empId}/change-status`;
    document.getElementById('csQuickEmpName').value = empName;
    const display = document.getElementById('csQuickEmpNameDisplay');
    if (display) display.innerText = empName;
    const avatar = document.getElementById('csQuickEmpAvatar');
    if (avatar) {
        const inits = empName.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        avatar.innerText = inits || 'EM';
    }
    if (currentStatus) document.getElementById('csQuickStatus').value = currentStatus;
    openModal('changeStatusQuickModal');
}

function openEditEmployeeModal(empId) {
    // Close any open action menus
    document.querySelectorAll('.hr-action-dropdown').forEach(d => d.style.display = 'none');

    const jsonEl = document.getElementById('emp-json-' + empId);
    if (jsonEl) {
        try {
            const emp = JSON.parse(jsonEl.textContent);
            populateEditEmployeeModal(emp);
            return;
        } catch (e) {
            console.error('Failed to parse employee JSON data:', e);
        }
    }

    // Fallback fetch from server
    fetch(`/hr/people/employees/${empId}/data`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(emp => {
        populateEditEmployeeModal(emp);
    })
    .catch(err => {
        console.error('Error fetching employee data:', err);
        alert('Could not load employee details. Please refresh and try again.');
    });
}

function populateEditEmployeeModal(emp) {
    const form = document.getElementById('editEmployeeForm');
    form.action = `/hr/people/employees/${emp.id}`;

    // 1. Basic Information
    document.getElementById('edit_emp_first_name').value = emp.first_name || '';
    document.getElementById('edit_emp_middle_name').value = emp.middle_name || '';
    document.getElementById('edit_emp_last_name').value = emp.last_name || '';
    document.getElementById('edit_emp_suffix').value = emp.suffix || '';
    document.getElementById('edit_emp_preferred_name').value = emp.preferred_name || '';
    document.getElementById('edit_emp_date_of_birth').value = emp.date_of_birth ? emp.date_of_birth.substring(0, 10) : '';
    document.getElementById('edit_emp_birth_place').value = emp.birth_place || '';
    document.getElementById('edit_emp_gender').value = emp.gender || 'Male';
    document.getElementById('edit_emp_civil_status').value = emp.civil_status || 'Single';
    const editEmpNat = document.getElementById('edit_emp_nationality');
    if (editEmpNat) {
        editEmpNat.value = emp.nationality || 'Filipino';
        editEmpNat.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // 2. Contact & Addresses
    document.getElementById('edit_emp_personal_email').value = emp.personal_email || emp.email || '';
    document.getElementById('edit_emp_company_email').value = emp.company_email || '';
    document.getElementById('edit_emp_mobile_number').value = emp.mobile_number || '';
    document.getElementById('edit_emp_telephone_number').value = emp.telephone_number || '';
    document.getElementById('edit_emp_address').value = emp.address || '';
    document.getElementById('edit_emp_permanent_address').value = emp.permanent_address || emp.address || '';
    const sameAddrCb = document.getElementById('edit_sameAddressCb');
    if (sameAddrCb) {
        sameAddrCb.checked = Boolean(emp.permanent_address && emp.address && emp.permanent_address === emp.address);
    }

    // 3. Employment & Assignments
    // Primary & multi branch
    const primaryBranch = document.getElementById('edit_primary_branch_id');
    if (primaryBranch) primaryBranch.value = emp.branch_id || '';
    const assignedBranches = Array.isArray(emp.all_branch_ids) ? emp.all_branch_ids : (emp.branch_id ? [emp.branch_id] : []);
    document.querySelectorAll('.edit-emp-branch-cb').forEach(cb => {
        cb.checked = assignedBranches.map(Number).includes(Number(cb.value));
    });

    // Primary & multi department
    const primaryDept = document.getElementById('edit_primary_department_id');
    if (primaryDept) primaryDept.value = emp.department_id || '';
    const assignedDepts = Array.isArray(emp.all_department_ids) ? emp.all_department_ids : (emp.department_id ? [emp.department_id] : []);
    document.querySelectorAll('.edit-emp-dept-cb').forEach(cb => {
        cb.checked = assignedDepts.map(Number).includes(Number(cb.value));
    });

    // Primary & multi position
    const primaryPos = document.getElementById('edit_primary_position_id');
    if (primaryPos) primaryPos.value = emp.position_id || '';
    const assignedPos = Array.isArray(emp.all_position_ids) ? emp.all_position_ids : (emp.position_id ? [emp.position_id] : []);
    document.querySelectorAll('.edit-emp-pos-cb').forEach(cb => {
        cb.checked = assignedPos.map(Number).includes(Number(cb.value));
    });

    // Job Level, Supervisor, Type, Status, Schedule
    document.getElementById('edit_emp_job_level').value = emp.job_level || 'Staff';
    document.getElementById('edit_emp_supervisor_id').value = emp.supervisor_id || '';
    document.getElementById('edit_emp_employment_type').value = emp.employment_type || 'Regular';
    document.getElementById('edit_emp_employment_status').value = emp.employment_status || 'Active';
    document.getElementById('edit_emp_work_location').value = emp.work_location || '';
    document.getElementById('edit_emp_work_schedule').value = emp.work_schedule || '';
    document.getElementById('edit_emp_date_hired').value = emp.date_hired ? emp.date_hired.substring(0, 10) : '';
    document.getElementById('edit_emp_date_of_regularization').value = emp.date_of_regularization ? emp.date_of_regularization.substring(0, 10) : '';
    document.getElementById('edit_emp_basic_salary').value = (emp.basic_salary !== null && emp.basic_salary !== undefined) ? emp.basic_salary : '';
    document.getElementById('edit_emp_salary_type').value = emp.payroll_type || emp.salary_type || 'Monthly';
    document.getElementById('edit_emp_pay_frequency').value = (emp.pay_frequency && emp.pay_frequency.toLowerCase() === 'semi-monthly') ? 'Semi-Monthly' : (emp.pay_frequency || 'Semi-Monthly');
    document.getElementById('edit_emp_allowances').value = (emp.allowances !== null && emp.allowances !== undefined) ? emp.allowances : '0.00';

    // Source (Company / Agency)
    const isAgency = (emp.employment_source === 'Agency') || (emp.agency_name && !emp.company_name);
    if (isAgency) {
        const agencyRadio = document.getElementById('edit_emp_src_agency');
        if (agencyRadio) agencyRadio.checked = true;
        toggleEditEmpSource('Agency');
        if (emp.agency_name) document.getElementById('edit_agency_name').value = emp.agency_name;
    } else {
        const companyRadio = document.getElementById('edit_emp_src_company');
        if (companyRadio) companyRadio.checked = true;
        toggleEditEmpSource('Company');
        if (emp.company_name) document.getElementById('edit_company_name').value = emp.company_name;
    }

    // 4. Statutory & Government IDs
    document.getElementById('edit_emp_sss_number').value = emp.sss_number || '';
    document.getElementById('edit_emp_philhealth_number').value = emp.philhealth_number || '';
    document.getElementById('edit_emp_pagibig_number').value = emp.pagibig_number || '';
    document.getElementById('edit_emp_tin').value = emp.tin || '';
    document.getElementById('edit_emp_rdo_code').value = emp.rdo_code || '';
    document.getElementById('edit_emp_philsys_id').value = emp.philsys_id || '';
    document.getElementById('edit_emp_passport_number').value = emp.passport_number || '';
    document.getElementById('edit_emp_driver_license').value = emp.driver_license || '';

    // 5. Photo
    const fileInput = document.getElementById('editEmpPhotoInput');
    if (fileInput) fileInput.value = '';
    const previewImg = document.getElementById('editEmpPhotoPreviewImg');
    const placeholder = document.getElementById('editEmpPhotoAvatarPlaceholder');
    if (emp.photo_url) {
        if (previewImg) {
            previewImg.src = emp.photo_url;
            previewImg.style.display = 'block';
        }
        if (placeholder) placeholder.style.display = 'none';
    } else {
        if (previewImg) previewImg.style.display = 'none';
        if (placeholder) {
            placeholder.style.display = 'flex';
            const name = (emp.first_name || '') + ' ' + (emp.last_name || '');
            const inits = name.trim().split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
            placeholder.innerHTML = inits ? `<span>${inits}</span>` : '<i class="ph ph-user"></i>';
        }
    }

    openModal('editEmployeeModal');
}

function syncEditEmpPrimaryCheckbox(type, val) {
    if (!val) return;
    const prefix = type === 'branch' ? 'cb_edit_emp_branch_' : (type === 'dept' ? 'cb_edit_emp_dept_' : 'cb_edit_emp_pos_');
    const cb = document.getElementById(prefix + val);
    if (cb) cb.checked = true;
}

function toggleEditEmpSource(type) {
    const compBox = document.getElementById('edit_emp_company_box');
    const agBox = document.getElementById('edit_emp_agency_box');
    if (type === 'Agency') {
        if (compBox) compBox.style.display = 'none';
        if (agBox) agBox.style.display = 'block';
    } else {
        if (compBox) compBox.style.display = 'block';
        if (agBox) agBox.style.display = 'none';
    }
}

function previewEditEmpPhoto(input) {
    const preview = document.getElementById('editEmpPhotoPreviewImg');
    const placeholder = document.getElementById('editEmpPhotoAvatarPlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endsection
