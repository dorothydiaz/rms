@extends('layouts.app')

@section('title', 'Work Schedule Management - Attendance')

@php
    $cWeek = \Carbon\Carbon::parse($weekStart);
    $prevWeek = $cWeek->copy()->subDays(7)->toDateString();
    $nextWeek = $cWeek->copy()->addDays(7)->toDateString();
    $thisWeek = \Carbon\Carbon::now()->startOfWeek()->toDateString();
    $weekEnd = $cWeek->copy()->addDays(6)->toDateString();

    // Connect directly to active departments from the Department Tab (Organization Management)
    $departmentsList = isset($departments) && $departments->isNotEmpty()
        ? $departments
        : \App\Models\Hr\Department::where('is_active', true)->orderBy('name')->get();

    if ($departmentsList->isEmpty()) {
        $departmentsList = \App\Models\Hr\Department::orderBy('name')->get();
    }
    $categories = $departmentsList->pluck('name');
    $departmentsJson = $departmentsList->map(function($d) {
        return [
            'id' => $d->id,
            'name' => $d->name,
            'code' => $d->code,
        ];
    })->values();
@endphp

@push('styles')
    @vite('resources/css/modules/hr-schedules.css')
    <style>
        /* Critical Schedule Planner Viewport & Anti-FOUC rules */
        .content-area { height: calc(100vh - 64px) !important; max-height: calc(100vh - 64px) !important; overflow: hidden !important; }
        .sched-matrix-table { table-layout: fixed; }
        .sched-row-dropdown.open { display: block !important; }
        .sched-cell-td.sched-cell-hover { border: 1.5px dashed #93c5fd; }
        .sched-card-time { font-variant-numeric: tabular-nums; }
        .sched-theme-o .sched-card-time { background: #ffffff; }
        .swal2-container { z-index: 9999999 !important; }
    </style>
@endpush

@push('blade-data')
<script>
    window.HrSchedulesConfig = Object.freeze({
        csrfToken: @json(csrf_token()),
        weekStart: @json($weekStart),
        dates: @json($dates),
        allEmployees: @json(isset($allEmployees) ? $allEmployees : $employees),
        departmentsJson: @json($departmentsJson),
        shiftTemplates: @json($shiftTemplates->keyBy('id')),
        routes: {
            quickAssign: @json(route('hr.attendance.schedules.quick-assign')),
            copyWeek: @json(route('hr.attendance.schedules.copy-week')),
            quickFillRow: @json(route('hr.attendance.schedules.quick-fill-row')),
            updateDept: @json(route('hr.attendance.schedules.update-employee-department')),
            batchSchedule: @json(route('hr.attendance.schedules.batch')),
        },
        /* Dropdown popover placement specs: position: fixed !important; spaceRight rect.right + 10 rect.left - ddWidth - 10 */
        popoverSpecs: {
            position: 'position: fixed !important;',
            spaceRight: 'spaceRight',
            collisionOffsetRight: 'rect.right + 10',
            collisionOffsetLeft: 'rect.left - ddWidth - 10'
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-schedules.js')
@endpush

@section('content')
<x-hr-tabs parent="time-attendance" />

<!-- Main Accelerated Weekly Schedule Planner Matrix Card -->
<div class="hr-table-card" id="schedPlannerCard">
    <!-- Top Controller Toolbar -->
    <div class="hr-table-header" style="flex-wrap: wrap; gap: 10px; padding: 8px 16px; border-bottom: 1.5px solid #e2e8f0; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
        <!-- Title & Subtitle -->
        <div style="display: flex; align-items: center; gap: 9px; flex-shrink: 0;">
            <span class="hr-table-title" style="font-size: 17px; font-weight: 650; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-calendar-check" style="color: #7c3aed; font-size: 21px;"></i> 
                Shift Schedule Planner
            </span>
            <span class="hr-badge hr-badge-neutral" id="empCountBadge" style="font-size: 12px; font-weight: 600; padding: 2px 9px;">
                {{ count($employees) }} Staff
            </span>
        </div>

        <!-- Navigation Controls Toolbar (Spacious, Elegant & Aligned) -->
        <div class="sched-top-nav-wrap">
            <!-- Week Navigation Controls Pill -->
            <div class="sched-week-nav-pill">
                <a href="{{ route('hr.attendance.schedules', array_merge(request()->query(), ['week_start' => $prevWeek])) }}" 
                   class="sched-nav-btn-icon" title="Previous Week ({{ \Carbon\Carbon::parse($prevWeek)->format('M d') }})">
                    <i class="ph ph-caret-left"></i>
                </a>

                <form method="GET" action="{{ route('hr.attendance.schedules') }}" id="weekPickerForm" class="sched-week-date-trigger" title="Click to choose another week" onclick="const inp = this.querySelector('input[type=date]'); if(inp && typeof inp.showPicker === 'function') { try { inp.showPicker(); } catch(e){} }">
                    @if(request('branch_id'))
                        <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                    @endif
                    <i class="ph ph-calendar-blank sched-week-cal-icon"></i>
                    <span class="sched-week-label-range">{{ $cWeek->format('M d') }} – {{ \Carbon\Carbon::parse($weekEnd)->format('M d, Y') }}</span>
                    <i class="ph ph-caret-down sched-week-dropdown-arrow"></i>
                    <input type="date" name="week_start" id="schedWeekInput" value="{{ $weekStart }}" onchange="this.form.submit()" class="no-custom-datepicker sched-invisible-date-input" aria-label="Select week">
                </form>

                <a href="{{ route('hr.attendance.schedules', array_merge(request()->query(), ['week_start' => $nextWeek])) }}" 
                   class="sched-nav-btn-icon" title="Next Week ({{ \Carbon\Carbon::parse($nextWeek)->format('M d') }})">
                    <i class="ph ph-caret-right"></i>
                </a>

                @if($weekStart !== $thisWeek)
                    <a href="{{ route('hr.attendance.schedules', array_merge(request()->query(), ['week_start' => $thisWeek])) }}" 
                       class="sched-nav-btn-current" title="Jump to Current Week">
                        Current
                    </a>
                @endif
            </div>

            <!-- Toolbar Action Buttons (Spacious & Clean) -->
            <div class="sched-top-actions">
                <button type="button" class="sched-top-btn sched-top-btn-secondary" onclick="openScheduleManagerTab('add_shift')" title="Add Custom Shift Template">
                    <i class="ph ph-plus-circle" style="color: #7c3aed; font-size: 15px;"></i>
                    <span>Add Template</span>
                </button>
                <button type="button" class="sched-top-btn sched-top-btn-secondary" onclick="openBatchFillGridModal()" title="Fill Weekly Grid for Multiple or All Employees">
                    <i class="ph ph-magic-wand" style="color: #d97706; font-size: 15px;"></i>
                    <span>Fill Grid</span>
                </button>
                <button type="button" class="sched-top-btn sched-top-btn-secondary" onclick="openDefaultShiftModal()" title="Assign Default Shift Schedule Per Employee">
                    <i class="ph ph-clock-user" style="color: #0284c7; font-size: 15px;"></i>
                    <span>Default Shifts</span>
                </button>
                <a href="{{ route('hr.reports.change-of-schedule') }}" class="sched-top-btn sched-top-btn-secondary" title="View Change of Schedule History Report" style="text-decoration: none;">
                    <i class="ph ph-clock-counter-clockwise" style="color: #6366f1; font-size: 15px;"></i>
                    <span>Schedule History</span>
                </a>
                <button type="button" class="sched-top-btn sched-top-btn-secondary" onclick="openShiftMasterModal()" title="Shift Settings">
                    <i class="ph ph-sliders" style="color: #64748b; font-size: 15px;"></i>
                    <span>Shift Settings</span>
                </button>
                <button type="button" class="sched-top-btn sched-top-btn-save" id="btnSaveGrid" onclick="saveDraftSchedules()" title="Save Grid Schedules">
                    <i class="ph ph-floppy-disk" style="font-size: 15px;"></i>
                    <span id="saveBtnLabel">Save</span>
                    <span id="saveBtnBadge" class="sched-badge-count" style="display: none; background: #ffffff; color: #7c3aed; font-weight: 600; margin-left: 5px; padding: 1px 7px; border-radius: 999px; font-size: 10.5px;">0</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Secondary Table Toolbar: Referenced Multi-Filter "Add Employee Row" + Category Filters + Live Search -->
    <div class="sched-sub-toolbar">
        <!-- 1. Add Employee Row Multi-Select Dropdown Trigger -->
        <div class="sched-add-emp-wrap" id="addEmpDropdownWrap">
            <button type="button" class="sched-btn-add-emp-toggle" id="btnToggleEmpDropdown" onclick="toggleEmpDropdown()">
                <i class="ph ph-user-plus" style="color: #7c3aed;"></i>
                <span>Add Employee Row</span>
                <i class="ph ph-caret-down" id="empDropdownChevron" style="color: #94a3b8; font-size: 11px; transition: transform 0.2s;"></i>
                <span id="selectedEmpBadge" class="sched-badge-count" style="display: none;">0</span>
            </button>

            <!-- Multi-Filter Dropdown Panel (Top-Layer Stacking z-50 matching Schedule.html) -->
            <div id="empDropdownPanel" class="sched-emp-dropdown-panel" style="display: none;">
                <!-- Header Bar -->
                <div class="sched-dropdown-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="sched-avatar-circle" style="background: #ede9fe; color: #7c3aed; width: 28px; height: 28px;">
                            <i class="ph ph-users"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 12px; font-weight: 650; color: #0f172a; margin: 0; text-transform: uppercase;">Select Employees to Schedule</h4>
                            <p id="dropdownEmpMatchText" style="font-size: 11px; color: #64748b; margin: 0;">0 employee(s) matching filter</p>
                        </div>
                    </div>
                    <button type="button" class="sched-btn-close-sm" onclick="closeEmpDropdown()">&times;</button>
                </div>

                <!-- Search & Filter Controls Bar -->
                <div class="sched-dropdown-filter-bar">
                    <!-- Search Input -->
                    <div style="position: relative; width: 100%;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
                        <input type="text" id="modalEmpSearch" placeholder="Search name, ID, branch, position..." 
                               oninput="filterDropdownEmployees()"
                               class="sched-input-search">
                        <button type="button" id="clearModalSearchBtn" onclick="clearModalEmpSearch()" 
                                style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; cursor: pointer; display: none;">
                            <i class="ph ph-x-circle"></i>
                        </button>
                    </div>

                    <!-- Filters Row: Status & Category -->
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select id="modalStatusFilter" onchange="filterDropdownEmployees()" class="sched-select-filter">
                            <option value="">All Statuses</option>
                            <option value="active" selected>Active</option>
                            <option value="probationary">Probationary</option>
                            <option value="regular">Regular</option>
                        </select>

                        <select id="modalCategoryFilter" onchange="filterDropdownEmployees()" class="sched-select-filter">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ strtolower($cat) }}">{{ $cat }}</option>
                            @endforeach
                        </select>

                        <button type="button" onclick="resetDropdownFilters()" class="sched-btn-clear-filters">
                            Clear Filters
                        </button>
                    </div>

                    <!-- Select All / Deselect Toggle -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 6px; border-top: 1px solid #f1f5f9; font-size: 11px;">
                        <button type="button" id="btnToggleSelectAllModal" onclick="toggleSelectAllFilteredEmps()" style="border: none; background: transparent; color: #7c3aed; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <i class="ph ph-check-square" id="iconSelectAllModal"></i>
                            <span id="labelSelectAllModal">Select All Filtered</span>
                        </button>
                        <span id="selectedAdditionCount" style="color: #94a3b8; font-style: italic;">0 selected for addition</span>
                    </div>
                </div>

                <!-- Scrollable Employee List Area -->
                <div class="sched-dropdown-list custom-scrollbar" id="dropdownEmpList">
                    <!-- Populated dynamically by JS -->
                </div>

                <!-- Footer Bar -->
                <div class="sched-dropdown-footer">
                    <span style="font-size: 11.5px; font-weight: 600; color: #475569;">
                        Selected: <strong id="footerSelectedCount" style="color: #7c3aed;">0</strong> row(s)
                    </span>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeEmpDropdown()">Cancel</button>
                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" id="btnAddSelectedEmps" onclick="addSelectedEmployeesToGrid()" disabled>
                            <i class="ph ph-plus"></i> Add Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Category Checkbox Filter Group (Connected to Organization Departments Tab) -->
        <div class="sched-cat-filter-group">
            <span style="font-size: 11px; font-weight: 500; color: #64748b; display: flex; align-items: center; gap: 4px; text-transform: uppercase;">
                <i class="ph ph-funnel" style="font-size: 12px;"></i> Category:
            </span>
            @foreach($categories as $cat)
                <label class="sched-cat-checkbox-label">
                    <input type="checkbox" value="{{ strtolower($cat) }}" class="sched-cat-cb" onchange="filterMatrixByCategory()" checked>
                    <span>{{ $cat }}</span>
                </label>
            @endforeach
            <a href="{{ route('hr.people.departments') }}" target="_blank" class="sched-dept-tab-link" title="Open Organization Departments tab">
                <i class="ph ph-arrow-square-out"></i> Depts
            </a>
        </div>

        <!-- 3. Real-time Search Input -->
        <div class="sched-live-search-wrap" style="min-width: 300px;">
            <i class="ph ph-magnifying-glass sched-live-search-icon"></i>
            <input type="text" id="liveEmployeeSearch" class="sched-live-search-input" placeholder="Search employees, position, branch…" 
                   oninput="filterMatrixRowsBySearch()">
            <button type="button" id="clearSearchBtn" class="sched-live-search-clear" onclick="clearLiveSearch()" title="Clear search">
                <i class="ph ph-x" style="font-size: 11px;"></i>
            </button>
        </div>

        <!-- 4. Clear Grid Action -->
        <button type="button" class="sched-btn-clear-grid" onclick="clearRosterGrid()" title="Clear employee rows from current view">
            Clear Grid
        </button>
    </div>

    <!-- Weekly Interactive Schedule Table Matrix (Spacious & Breathable Layout) -->
    <div class="hr-table-wrapper" style="flex: 1 1 auto; min-height: 0; min-width: 0; width: 100%; max-width: 100%; overflow-y: auto; overflow-x: auto; position: relative;">
        <table class="hr-table sched-matrix-table" id="schedMatrixTable" role="grid" style="border-collapse: separate; border-spacing: 0; table-layout: fixed; width: 100%; min-width: 1080px;">
            <colgroup>
                <col class="sched-col-emp" style="width: 15%; min-width: 160px;">
                <col class="sched-col-week" style="width: 5.5%; min-width: 55px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-day" style="width: 11%; min-width: 116px;">
                <col class="sched-col-action" style="width: 3.5%; min-width: 36px;">
            </colgroup>
            <thead>
                <tr>
                    <!-- Sticky Left Column: Employee Identity & Row Quick Actions -->
                    <th class="sched-sticky-col sched-sticky-th" style="z-index: 30;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 650;">
                                <i class="ph ph-user" style="font-size: 15px;"></i> Employee
                            </span>
                            <span style="font-size: 10px; font-weight: 600; color: #7c3aed; background: #ede9fe; padding: 2.5px 7px; border-radius: 5px; text-transform: uppercase;">
                                Branch &darr;
                            </span>
                        </div>
                    </th>

                    <!-- Week Column matching Picture 2 -->
                    <th class="sched-col-week" style="text-align: center;">
                        <div style="font-weight: 650; font-size: 12.5px; color: #475569; display: flex; align-items: center; justify-content: center; gap: 4px;">
                            <i class="ph ph-calendar" style="font-size: 13px;"></i> Week
                        </div>
                    </th>

                    <!-- 7 Day Columns (Mon - Sun) -->
                    @foreach($dates as $d)
                        @php 
                            $cDate = \Carbon\Carbon::parse($d); 
                            $isToday = $cDate->isToday();
                        @endphp
                        <th class="sched-day-th" style="text-align: center; {{ $isToday ? 'background: rgba(124, 58, 237, 0.08); border-bottom: 2px solid #7c3aed;' : '' }}">
                            <div style="font-weight: 650; font-size: 13px; color: {{ $isToday ? '#7c3aed' : '#0f172a' }}; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <i class="ph ph-calendar-blank" style="font-size: 13px;"></i>
                                {{ $cDate->format('D') }}
                                @if($isToday)
                                    <span class="hr-badge hr-badge-primary" style="font-size: 9px; font-weight: 600; padding: 1.5px 5px; vertical-align: middle;">TODAY</span>
                                @endif
                            </div>
                            <div style="color: #64748b; font-size: 12px; font-weight: 600; margin-top: 2px;">
                                {{ $cDate->format('M j') }}
                            </div>
                        </th>
                    @endforeach

                    <!-- Action Column: Remove Row (Trash Icon) -->
                    <th class="sched-action-th" style="text-align: center;"></th>
                </tr>
            </thead>
            <tbody id="schedMatrixTbody">
                @forelse($employees as $emp)
                    @php 
                        $empScheds = $schedules->get($emp->id) ? $schedules->get($emp->id)->keyBy(function($item) {
                            return \Carbon\Carbon::parse($item->schedule_date)->toDateString();
                        }) : collect(); 
                        $empAttendances = isset($attendanceRecords) && $attendanceRecords->get($emp->id) ? $attendanceRecords->get($emp->id)->keyBy(function($item) {
                            return \Carbon\Carbon::parse($item->date)->toDateString();
                        }) : collect();
                        $branchName = $emp->branch?->name ?? 'Unassigned';
                        $positionName = $emp->position?->name ?? 'Staff';
                        $categoryName = $emp->department?->name ?? 'Front of House';
                        $deptColors = [
                            'management' => '#6366f1',
                            'front of house' => '#3b82f6',
                            'back of house' => '#10b981',
                            'finance & admin' => '#f59e0b',
                            'finance' => '#f59e0b',
                            'hr' => '#8b5cf6',
                            'human resources' => '#8b5cf6',
                            'it' => '#0ea5e9',
                            'information technology' => '#0ea5e9',
                        ];
                        $dotColor = $deptColors[strtolower($categoryName)] ?? '#7c3aed';
                    @endphp
                    <tr class="sched-row" 
                        id="schedRow_{{ $emp->id }}" 
                        data-emp-id="{{ $emp->id }}" 
                        data-emp-name="{{ strtolower($emp->full_name) }}" 
                        data-emp-branch="{{ strtolower($branchName) }}"
                        data-emp-pos="{{ strtolower($positionName) }}"
                        data-emp-cat="{{ strtolower($categoryName) }}">
                        
                        <!-- Sticky Employee Column with Avatar, Category Pill, Branch -->
                        <td class="sched-sticky-col sched-sticky-td">
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <!-- Initials Avatar Badge -->
                                <div class="sched-emp-avatar" title="{{ $emp->full_name }}">
                                    {{ $emp->initials }}
                                </div>

                                <div style="min-width: 0; flex: 1;">
                                    <!-- Name -->
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25;" title="{{ $emp->full_name }}">
                                        {{ $emp->full_name }}
                                    </div>
                                    <!-- Category Pill with <> Department Navigation (Only for employees with multiple departments) -->
                                    <div style="margin: 3px 0;">
                                        <div class="sched-dept-pill-wrapper">
                                            @if($emp->has_multiple_departments)
                                            <button type="button" 
                                                    class="sched-dept-nav-btn prev" 
                                                    onclick="cycleEmployeeDepartment({{ $emp->id }}, -1, this)" 
                                                    title="Previous department (click to cycle)"
                                                    aria-label="Previous department">
                                                <i class="ph ph-caret-left"></i>
                                            </button>
                                            @endif
                                            <span class="sched-cat-pill" 
                                                  id="empDeptPill_{{ $emp->id }}" 
                                                  data-current-dept="{{ $categoryName }}"
                                                  data-emp-id="{{ $emp->id }}"
                                                  @if($emp->has_multiple_departments)
                                                  onclick="cycleEmployeeDepartment({{ $emp->id }}, 1, this)"
                                                  style="cursor: pointer;"
                                                  title="Department: {{ $categoryName }} (Click &lt; or &gt; to cycle)"
                                                  @else
                                                  title="Department: {{ $categoryName }}"
                                                  @endif>
                                                <span class="sched-cat-dot" style="background-color: {{ $dotColor }};"></span>
                                                <span class="truncate sched-dept-pill-text">{{ $categoryName }}</span>
                                            </span>
                                            @if($emp->has_multiple_departments)
                                            <button type="button" 
                                                    class="sched-dept-nav-btn next" 
                                                    onclick="cycleEmployeeDepartment({{ $emp->id }}, 1, this)" 
                                                    title="Next department (click to cycle)"
                                                    aria-label="Next department">
                                                <i class="ph ph-caret-right"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Branch Location Anchor -->
                                    <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 4px;" title="{{ $branchName }}">
                                        <i class="ph ph-map-pin" style="color: #7c3aed; font-size: 11.5px;"></i>
                                        <span class="truncate" style="font-weight: 600; color: #475569;">{{ $branchName }}</span>
                                    </div>
                                    @if($emp->defaultShiftTemplate)
                                        <div style="font-size: 10px; color: #0284c7; font-weight: 600; display: flex; align-items: center; gap: 3px; margin-top: 2px;" title="Default Assigned Shift: {{ $emp->defaultShiftTemplate->name }}">
                                            <i class="ph ph-clock"></i>
                                            <span>Def: {{ $emp->defaultShiftTemplate->code ?: substr($emp->defaultShiftTemplate->start_time, 0, 5) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- ⚡ Quick Fill Menu for this row -->
                                <div class="sched-row-action-menu" style="position: relative;">
                                    <button type="button" class="sched-row-btn" onclick="toggleRowMenu({{ $emp->id }}, this)" title="Quick fill week for {{ $emp->full_name }}">
                                        <i class="ph ph-lightning"></i>
                                    </button>
                                    
                                    <div class="sched-row-dropdown" id="rowMenu_{{ $emp->id }}" style="display: none;">
                                        <div style="padding: 6px 10px; font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                                            Quick Fill: {{ Str::limit($emp->first_name, 12) }}
                                        </div>
                                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset({{ $emp->id }}, 'O', ['Sun'])">
                                            <i class="ph ph-sun" style="color: #10b981;"></i>
                                            <span>Mon–Sat Opening (Sun Off)</span>
                                        </button>
                                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset({{ $emp->id }}, 'MD', ['Sun'])">
                                            <i class="ph ph-clock" style="color: #3b82f6;"></i>
                                            <span>Mon–Sat Mid Day (Sun Off)</span>
                                        </button>
                                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset({{ $emp->id }}, 'C', ['Sun'])">
                                            <i class="ph ph-moon" style="color: #9333ea;"></i>
                                            <span>Mon–Sat Closing (Sun Off)</span>
                                        </button>
                                        <div style="border-top: 1px solid #f1f5f9; margin: 4px 0;"></div>
                                        <button type="button" class="sched-row-dd-item" onclick="openDefaultShiftModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', {{ $emp->default_shift_template_id ?: 'null' }})">
                                            <i class="ph ph-clock-user" style="color: #0284c7;"></i>
                                            <span>Assign Default Shift</span>
                                        </button>
                                        <button type="button" class="sched-row-dd-item text-danger" onclick="quickFillRowPreset({{ $emp->id }}, 'OFF', ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])">
                                            <i class="ph ph-coffee"></i>
                                            <span>Mark Entire Week as Rest Days</span>
                                        </button>
                                        <button type="button" class="sched-row-dd-item text-danger" onclick="clearEmployeeWeek({{ $emp->id }})">
                                            <i class="ph ph-trash"></i>
                                            <span>Clear All Shifts This Week</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Week Column matching Picture 2 -->
                        <td class="sched-col-week" style="text-align: center; vertical-align: middle; color: #64748b; font-size: 12px; font-weight: 600; font-family: monospace;">
                            {{ $weekStart }}
                        </td>

                        <!-- Day Cells for Employee -->
                        @foreach($dates as $d)
                            @php 
                                $daySched = $empScheds->get($d); 
                                $cDate = \Carbon\Carbon::parse($d);
                                $isToday = $cDate->isToday();
                            @endphp
                            <td class="sched-cell-td" 
                                id="cell_{{ $emp->id }}_{{ $d }}"
                                data-emp-id="{{ $emp->id }}" 
                                data-date="{{ $d }}"
                                data-emp-cat="{{ strtolower($categoryName) }}"
                                style="{{ $isToday ? 'background: rgba(124, 58, 237, 0.02);' : '' }}">
                                
                                @if($daySched)
                                    @php
                                        $isRest = $daySched->is_rest_day || $daySched->notes === 'OFF' || $daySched->notes === 'RESTDAY';
                                        $rawCode = strtoupper(trim($daySched->notes ?: ($daySched->shiftTemplate?->code ?? '')));
                                        $tmplName = strtoupper($daySched->shiftTemplate?->name ?? '');
                                        $startTime = $daySched->custom_start_time 
                                            ? substr($daySched->custom_start_time, 0, 5) 
                                            : ($daySched->shiftTemplate ? \Carbon\Carbon::parse($daySched->shiftTemplate->start_time)->format('H:i') : '');
                                        $endTime = $daySched->custom_end_time 
                                            ? substr($daySched->custom_end_time, 0, 5) 
                                            : ($daySched->shiftTemplate ? \Carbon\Carbon::parse($daySched->shiftTemplate->end_time)->format('H:i') : '');
                                        $sTime = $isRest ? 'OFF DUTY' : ($startTime && $endTime ? "{$startTime} - {$endTime}" : '10:00 - 19:00');

                                        // Theme & Code Classification: Exact preset matching has highest priority
                                        if ($isRest) {
                                            $sCode = 'OFF';
                                            $sLabel = 'RESTDAY';
                                            $themeClass = 'sched-card-rest';
                                        } elseif ($rawCode === 'MD' || str_contains($rawCode, 'MID') || str_contains($tmplName, 'MID')) {
                                            $sCode = 'MD';
                                            $sLabel = 'MID DAY';
                                            $themeClass = 'sched-theme-md';
                                        } elseif ($rawCode === 'LD' || str_contains($rawCode, 'LATE') || str_contains($tmplName, 'LATE')) {
                                            $sCode = 'LD';
                                            $sLabel = 'LATE DAY';
                                            $themeClass = 'sched-theme-ld';
                                        } elseif ($rawCode === 'C' || str_contains($rawCode, 'CLOS') || str_contains($tmplName, 'CLOS')) {
                                            $sCode = 'C';
                                            $sLabel = 'CLOSING';
                                            $themeClass = 'sched-theme-c';
                                        } elseif ($rawCode === 'O' || str_contains($rawCode, 'OPEN') || str_contains($tmplName, 'OPEN')) {
                                            $sCode = 'O';
                                            $sLabel = 'OPENING';
                                            $themeClass = 'sched-theme-o';
                                        } elseif (in_array($rawCode, ['0600', '0700', '0800', '0900'])) {
                                            $sCode = 'O';
                                            $sLabel = 'OPENING';
                                            $themeClass = 'sched-theme-o';
                                        } elseif (in_array($rawCode, ['1000', '1100'])) {
                                            $sCode = 'MD';
                                            $sLabel = 'MID DAY';
                                            $themeClass = 'sched-theme-md';
                                        } elseif (in_array($rawCode, ['1200'])) {
                                            $sCode = 'LD';
                                            $sLabel = 'LATE DAY';
                                            $themeClass = 'sched-theme-ld';
                                        } elseif (in_array($rawCode, ['1300', '1400', '1500', '1600', '1700', '1800', '1900', '2000', '2100', '2200', '2300'])) {
                                            $sCode = 'C';
                                            $sLabel = 'CLOSING';
                                            $themeClass = 'sched-theme-c';
                                        } elseif (!$rawCode && $startTime) {
                                            if ($startTime < '10:00') {
                                                $sCode = 'O';
                                                $sLabel = 'OPENING';
                                                $themeClass = 'sched-theme-o';
                                            } elseif ($startTime < '12:00') {
                                                $sCode = 'MD';
                                                $sLabel = 'MID DAY';
                                                $themeClass = 'sched-theme-md';
                                            } elseif ($startTime < '13:00') {
                                                $sCode = 'LD';
                                                $sLabel = 'LATE DAY';
                                                $themeClass = 'sched-theme-ld';
                                            } else {
                                                $sCode = 'C';
                                                $sLabel = 'CLOSING';
                                                $themeClass = 'sched-theme-c';
                                            }
                                        } else {
                                            $sCode = $rawCode ?: 'CUSTOM';
                                            $sLabel = $daySched->shiftTemplate?->name ?: ($daySched->notes ?: 'CUSTOM');
                                            $themeClass = 'sched-card-custom';
                                        }

                                        $dayAtt = $empAttendances->get($d);
                                    @endphp
                                    <div class="sched-shift-card {{ $themeClass }}" onclick="openCellCustomDropdown({{ $emp->id }}, '{{ $d }}')">
                                        <!-- Card Top Header with Pill & Clear Button -->
                                        <div class="sched-card-top">
                                            <div class="sched-badge-wrap">
                                                <span class="sched-badge-code">{{ $sCode }}</span>
                                                <span class="sched-badge-name">{{ $sLabel }}</span>
                                            </div>
                                            <button type="button" class="sched-card-clear" onclick="event.stopPropagation(); clearCellShift({{ $emp->id }}, '{{ $d }}')" title="Clear Shift">
                                                &times;
                                            </button>
                                        </div>

                                        <!-- Scheduled Time Display -->
                                        <div class="sched-card-time">
                                            <i class="ph ph-clock"></i>
                                            <span>{{ $sTime }}</span>
                                        </div>

                                        <!-- Spacious Details Box matching Picture 2 -->
                                        @if($dayAtt)
                                            @php
                                                $inTime = $dayAtt->time_in ? \Carbon\Carbon::parse($dayAtt->time_in)->format('H:i') : '--:--';
                                                $outTime = $dayAtt->time_out ? \Carbon\Carbon::parse($dayAtt->time_out)->format('H:i') : '--:--';
                                                $isNoInOut = (!$dayAtt->time_in || !$dayAtt->time_out);
                                                $isLate = ($dayAtt->late_minutes > 0) || str_contains(strtolower($dayAtt->status ?? ''), 'late');
                                                $statusText = $isNoInOut ? 'NO IN/OUT' : ($isLate ? 'TARDINESS' : ($dayAtt->overtime_hours > 0 ? 'OVERTIME' : 'REGULAR'));
                                                $badgeClass = $isNoInOut ? 'badge-no-inout' : ($isLate ? 'badge-tardiness' : ($dayAtt->overtime_hours > 0 ? 'badge-overtime' : 'badge-regular'));
                                                $dotClass = $isNoInOut ? 'dot-no-inout' : ($isLate ? 'dot-tardiness' : ($dayAtt->overtime_hours > 0 ? 'dot-overtime' : 'dot-regular'));
                                                $hrs = number_format((float)$dayAtt->total_hours, 2);
                                            @endphp
                                            <div class="sched-card-detail-box">
                                                <div class="sched-detail-row">
                                                    <span class="sched-detail-dot {{ $dotClass }}">●</span>
                                                    <span class="sched-detail-punch">{{ $inTime }} → {{ $outTime }}</span>
                                                    <span class="sched-detail-badge {{ $badgeClass }}">{{ $statusText }}</span>
                                                </div>
                                                <div class="sched-detail-row sched-detail-hours">
                                                    <span>Hours Worked:</span>
                                                    <strong>{{ $hrs }} hrs</strong>
                                                </div>
                                            </div>
                                        @elseif($isRest)
                                            <div class="sched-card-detail-box">
                                                <div class="sched-detail-row">
                                                    <span class="sched-detail-dot dot-no-inout">●</span>
                                                    <span class="sched-detail-punch">Rest Day</span>
                                                    <span class="sched-detail-badge badge-off-duty">OFF DUTY</span>
                                                </div>
                                                <div class="sched-detail-row sched-detail-hours">
                                                    <span>Hours Worked:</span>
                                                    <strong>0.00 hrs</strong>
                                                </div>
                                            </div>
                                        @else
                                            <div class="sched-card-detail-box">
                                                <div class="sched-detail-row">
                                                    <span class="sched-detail-dot dot-planned">●</span>
                                                    <span class="sched-detail-punch">Planned</span>
                                                    <span class="sched-detail-badge badge-planned">SCHEDULED</span>
                                                </div>
                                                <div class="sched-detail-row sched-detail-hours">
                                                    <span>Target Hours:</span>
                                                    <strong>8.00 hrs</strong>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- Empty State: 3-Row Grid with Direct Presets and Custom Schedule -->
                                    <div class="sched-empty-plotter">
                                        <div class="sched-preset-grid">
                                            <!-- Row 1: O, MD, LD -->
                                            <div class="sched-btn-row">
                                                <button type="button" class="sched-mini-pill sched-pill-o" onclick="directPlotPreset({{ $emp->id }}, '{{ $d }}', 'O')" title="OPENING Shift">O</button>
                                                <button type="button" class="sched-mini-pill sched-pill-md" onclick="directPlotPreset({{ $emp->id }}, '{{ $d }}', 'MD')" title="MID DAY Shift">MD</button>
                                                <button type="button" class="sched-mini-pill sched-pill-ld" onclick="directPlotPreset({{ $emp->id }}, '{{ $d }}', 'LD')" title="LATE DAY Shift">LD</button>
                                            </div>
                                            <!-- Row 2: C, RESTDAY -->
                                            <div class="sched-btn-row">
                                                <button type="button" class="sched-mini-pill sched-pill-c" onclick="directPlotPreset({{ $emp->id }}, '{{ $d }}', 'C')" title="CLOSING Shift">C</button>
                                                <button type="button" class="sched-mini-pill sched-pill-restday" onclick="directPlotPreset({{ $emp->id }}, '{{ $d }}', 'OFF')" title="RESTDAY (Off Duty)">RESTDAY</button>
                                            </div>
                                            <!-- Row 3: Custom Schedule -->
                                            <div class="sched-btn-row">
                                                <button type="button" class="sched-mini-pill sched-pill-custom" onclick="openCellCustomDropdown({{ $emp->id }}, '{{ $d }}')" title="Select other shift or enter custom time">
                                                    Custom <i class="ph ph-caret-down" style="font-size: 8px;"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </td>
                        @endforeach

                        <!-- Delete / Remove Row Button -->
                        <td class="sched-action-td" style="text-align: center; vertical-align: middle;">
                            <button type="button" class="sched-btn-row-del" onclick="removeEmployeeRowFromGrid({{ $emp->id }})" title="Remove row from grid">
                                <i class="ph ph-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr id="schedEmptyRow">
                        <td colspan="10" style="text-align: center; color: #94a3b8; padding: 48px;">
                            <div style="font-size: 28px; margin-bottom: 8px;"><i class="ph ph-users"></i></div>
                            <div style="font-weight: 700; color: #475569; font-size: 14px;">No employees in current view</div>
                            <div style="font-size: 12px; color: #94a3b8;">Click "+ Add Employee Row" above to select and populate staff.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Interactive Schedule Matrix Pagination Footer -->
    <div class="hr-table-footer" id="schedPaginationBar" style="display: none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="schedPaginationInfo">
                Showing <strong>1</strong> to <strong>{{ count($employees) }}</strong> of <strong>{{ count($employees) }}</strong> staff
            </div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="schedPerPageSelect" onchange="schedChangePerPage()" aria-label="Rows per page">
                    <option value="10">10</option>
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">All</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="schedPageNav"></nav>
    </div>
</div>

<!-- Background Dimming Overlay for Schedule Manager Window -->
<div id="cellCustomBackdrop" class="sched-custom-backdrop" onclick="closeCellCustomDropdown()"></div>

<!-- Floating / Centered Unified Schedule & Shift Manager Window (Compact 390px, Spacious Layout) -->
<div id="cellCustomDropdown" class="sched-custom-dropdown" style="display: none;">
    <!-- Compact Header -->
    <div class="sched-custom-dd-header">
        <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
            <div class="sched-dd-icon-box">
                <i class="ph ph-clock" style="font-size: 15px;"></i>
            </div>
            <div style="min-width: 0;">
                <div style="font-weight: 800; font-size: 13.5px; color: #0f172a; letter-spacing: -0.2px; line-height: 1.2;">
                    Schedule & Shift Manager
                </div>
                <div id="cellCustomContextText" style="font-size: 11px; font-weight: 600; color: #64748b; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Select a preset shift or specify custom hours
                </div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
            <button type="button" class="sched-btn-close-sm" onclick="closeCellCustomDropdown()" title="Close manager">
                <i class="ph ph-x"></i>
            </button>
        </div>
    </div>

    <!-- 3 Direct Tabs (Shift, Fill Grid, Add Shift) -->
    <div class="sched-mgr-tabs">
        <button type="button" class="sched-mgr-tab-btn active" id="tabBtn_shift_custom" onclick="switchScheduleManagerTab('shift_custom', this)">
            <i class="ph ph-clock"></i>
            <span>Shift</span>
        </button>
        <button type="button" class="sched-mgr-tab-btn" id="tabBtn_fill_grid" onclick="switchScheduleManagerTab('fill_grid', this)">
            <i class="ph ph-magic-wand"></i>
            <span>Fill Grid</span>
        </button>
        <button type="button" class="sched-mgr-tab-btn" id="tabBtn_add_shift" onclick="switchScheduleManagerTab('add_shift', this)">
            <i class="ph ph-plus-circle"></i>
            <span>Add Shift</span>
        </button>
    </div>

    <!-- Tab 1: Shift & Custom (Spacious Single Column, Just Like Before) -->
    <div class="sched-mgr-tab-panel active" id="tabPanel_shift_custom">
        <!-- 1. Registered Shifts List -->
        <div style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-height: 0;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="sched-custom-dd-label" style="margin-bottom: 0;">
                    <i class="ph ph-list-bullets" style="color: #6366f1;"></i>
                    <span>Registered Shifts</span>
                </div>
                <span style="font-size: 10px; font-weight: 700; color: #7c3aed; background: #ede9fe; padding: 1.5px 6px; border-radius: 5px;">
                    {{ count($shiftTemplates) }} Available
                </span>
            </div>

            <!-- Instant Search Input (Roomier & Clean) -->
            <div style="position: relative;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 12.5px; color: #94a3b8;"></i>
                <input type="text" placeholder="Search shifts (e.g. 0600, 0800)..." oninput="filterRegisteredShifts(this.value)" 
                       style="width: 100%; height: 32px; padding: 0 12px 0 31px; font-size: 11.5px; border: 1.5px solid #e2e8f0; border-radius: 8px; outline: none; background: #f8fafc; font-family: inherit; box-sizing: border-box; transition: all 0.15s ease;">
            </div>

            <!-- Shift List Scroll (Spacious height to show 6+ shifts comfortably) -->
            <div class="sched-dd-shifts-scroll">
                @foreach($shiftTemplates as $st)
                    <div class="sched-dd-shift-item" role="button" tabindex="0" data-shift-name="{{ strtolower($st->name) }}" data-shift-code="{{ strtolower($st->code) }}" onclick="applyTemplateToActiveCell({{ $st->id }}, '{{ $st->code }}', '{{ $st->name }}', '{{ substr($st->start_time,0,5) }}', '{{ substr($st->end_time,0,5) }}', '{{ $st->color ?? '#7c3aed' }}')">
                        <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                            <span class="sched-card-pill" style="background: {{ $st->color ?? '#7c3aed' }}; color: #fff; font-size: 9px; font-weight: 800; padding: 2.5px 7px; border-radius: 5px; flex-shrink: 0; letter-spacing: 0.2px;">
                                {{ $st->code ?: substr($st->name, 0, 4) }}
                            </span>
                            <span style="font-weight: 700; color: #1e293b; font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $st->formatted_label ?? $st->name }}">
                                {{ $st->formatted_label ?? $st->name }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <span style="font-size: 10.5px; font-weight: 600; color: #64748b; font-variant-numeric: tabular-nums;">
                                {{ substr($st->start_time,0,5) }}–{{ substr($st->end_time,0,5) }}
                            </span>
                            <div class="sched-shift-item-actions">
                                <button type="button" class="sched-shift-row-btn sched-shift-btn-edit" 
                                        onclick="event.stopPropagation(); openEditShiftTemplateModal({{ $st->id }}, '{{ $st->code }}', '{{ substr($st->start_time,0,5) }}', '{{ substr($st->end_time,0,5) }}', {{ $st->is_overnight ? 'true' : 'false' }}, {{ $st->break_minutes ?? 60 }}, '{{ $st->color ?? '#7c3aed' }}')" 
                                        title="Edit this shift">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button type="button" class="sched-shift-row-btn sched-shift-btn-delete" 
                                        onclick="event.stopPropagation(); deleteShiftTemplate({{ $st->id }}, '{{ addslashes($st->formatted_label ?? $st->name) }}')" 
                                        title="Delete this shift">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 2. Custom Time Range Card (Compact & Fitted) -->
        <div class="sched-custom-time-card">
            <div class="sched-custom-time-header">
                <i class="ph ph-plus-circle" style="color: #9333ea; font-size: 12px;"></i>
                <span>Custom Time Range</span>
            </div>
            <div class="sched-time-inputs-row">
                <div class="sched-time-input-wrap">
                    <span class="sched-time-field-tag">Start Time</span>
                    <input type="time" id="customCellStart" value="08:00" class="sched-input-time" title="Start Time">
                </div>
                <div class="sched-time-arrow">
                    <i class="ph ph-arrow-right"></i>
                </div>
                <div class="sched-time-input-wrap">
                    <span class="sched-time-field-tag">End Time</span>
                    <input type="time" id="customCellEnd" value="17:00" class="sched-input-time" title="End Time">
                </div>
            </div>
            <div style="margin-bottom: 5px;">
                <span class="sched-time-field-tag" style="display: block; margin-bottom: 1px;">Shift Label</span>
                <input type="text" id="customCellLabel" placeholder="e.g. Split Shift, Mid Afternoon" class="sched-input-text-sm">
            </div>
            <button type="button" class="sched-btn-apply-custom" onclick="applyCustomTimeToActiveCell()">
                <i class="ph ph-check-circle" style="font-size: 12.5px;"></i>
                <span>Apply Custom Shift</span>
            </button>
        </div>

        <!-- 3. Edit Shift Times Footer Action -->
        <div style="text-align: center; padding-top: 0px;">
            <button type="button" class="sched-btn-edit-times" onclick="const empDept = activeCellEmpId ? (document.getElementById(`schedRow_${activeCellEmpId}`)?.getAttribute('data-emp-cat') || '') : ''; closeCellCustomDropdown(); openShiftMasterModal(empDept);">
                <i class="ph ph-sliders"></i>
                <span>Edit Shift Times by Department</span>
            </button>
        </div>
    </div>

    <!-- Tab 2: Fill Grid -->
    <div class="sched-mgr-tab-panel" id="tabPanel_fill_grid" style="display: none;">
        <form method="POST" action="javascript:void(0);" id="scheduleManagerAssignForm" onsubmit="event.preventDefault(); applySingleEmpFillGridDraft();" style="display: flex; flex-direction: column; flex: 1; min-height: 0; gap: 10px;">
            @csrf
            <div style="display: flex; justify-content: flex-end; margin-bottom: -4px;">
                <button type="button" class="sched-preset-btn" style="color: #7c3aed; font-weight: 700; border-color: #ddd6fe; background: #faf5ff; font-size: 10.5px; padding: 3px 8px;" onclick="closeCellCustomDropdown(); openBatchFillGridModal();">
                    <i class="ph ph-users"></i> Fill Multiple / All Staff
                </button>
            </div>
            <!-- Employee Info Card (Connected Directly to Clicked Employee) -->
            <div class="sched-owner-card">
                <div class="sched-owner-avatar" id="fillGridOwnerAvatar">BV</div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 13px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25;" id="fillGridOwnerName">
                        Beatriz Valdez
                    </div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748b; line-height: 1.25;" id="fillGridOwnerDept">
                        Front of House &bull; Main Branch
                    </div>
                </div>
                <input type="hidden" name="employee_id" id="schedEmployeeIdModal" value="">
            </div>

            <!-- Date Range Selection with Presets -->
            <div class="hr-form-group" style="margin-top: 0; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <label class="hr-form-label" style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 0; text-transform: uppercase;">
                        Date Range *
                    </label>
                    <div style="display: flex; gap: 3px;">
                        <button type="button" class="sched-preset-btn" onclick="presetDateRangeModal('today')">Today</button>
                        <button type="button" class="sched-preset-btn" onclick="presetDateRangeModal('this_week')">This Week</button>
                        <button type="button" class="sched-preset-btn" onclick="presetDateRangeModal('next_week')">Next Week</button>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <small style="font-size: 9.5px; color: #64748b; font-weight: 700; display: block; margin-bottom: 2px;">Start Date</small>
                        <input type="date" name="start_date" id="schedStartDateModal" class="sched-input-text-sm" required value="{{ date('Y-m-d') }}" onchange="syncDateRangeModal()">
                    </div>
                    <div>
                        <small style="font-size: 9.5px; color: #64748b; font-weight: 700; display: block; margin-bottom: 2px;">End Date</small>
                        <input type="date" name="end_date" id="schedEndDateModal" class="sched-input-text-sm" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </div>

            <!-- Shift Format Selection -->
            <div class="hr-form-group" style="margin-top: 0; margin-bottom: 0;">
                <label class="hr-form-label" style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 3px; text-transform: uppercase;">
                    Shift Format *
                </label>
                <select name="shift_template_id" id="schedShiftTemplateIdModal" class="sched-input-text-sm" style="font-weight: 400;">
                    <option value="">Select Shift Format</option>
                    @foreach($shiftTemplates as $st)
                        <option value="{{ $st->id }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $st->formatted_label ?? $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Weekly Rest Days Selection -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; margin-top: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <div style="font-size: 10.5px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                        Rest Days (Off Duty)
                    </div>
                    <div style="display: flex; gap: 3px;">
                        <button type="button" class="sched-preset-btn" onclick="setRestDaysPresetModal(['Sat', 'Sun'])">Sat & Sun</button>
                        <button type="button" class="sched-preset-btn" onclick="setRestDaysPresetModal(['Sun'])">Sunday</button>
                        <button type="button" class="sched-preset-btn" onclick="clearRestDaysPresetModal()">Clear</button>
                    </div>
                </div>
                <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                        <label style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: #334155; padding: 3px 6px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
                            <input type="checkbox" name="rest_days[]" value="{{ $day }}" id="rest_day_modal_{{ $day }}" class="modal-rest-day-cb">
                            {{ $day }}
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Helpful Tip Card -->
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 7px 10px; display: flex; align-items: center; gap: 7px; font-size: 11px; color: #166534; font-weight: 600;">
                <i class="ph ph-info" style="font-size: 14px; color: #15803d; flex-shrink: 0;"></i>
                <span>Plots shifts across the date range, skipping selected rest days.</span>
            </div>

            <!-- Submit Button (anchored at bottom) -->
            <button type="submit" class="sched-btn-apply-custom" style="margin-top: auto;">
                <i class="ph ph-magic-wand" style="font-size: 14px;"></i>
                <span>Apply Schedule for <span id="fillGridSubmitName">Employee</span> (Draft)</span>
            </button>
        </form>
    </div>

    <!-- Tab 3: Add Shift Template (Direct Form, No Nested Sub-Tabs) -->
    <div class="sched-mgr-tab-panel" id="tabPanel_add_shift" style="display: none;">
        <form method="POST" action="{{ route('hr.attendance.shifts.store') }}" style="display: flex; flex-direction: column; flex: 1; min-height: 0; gap: 10px; width: 100%;">
            @csrf
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="sched-custom-dd-label" style="margin-bottom: 0;">
                    <i class="ph ph-plus-circle" style="color: #7c3aed;"></i>
                    <span>Create Shift Template</span>
                </div>
                <span style="font-size: 10px; font-weight: 700; color: #7c3aed; background: #ede9fe; padding: 2px 7px; border-radius: 6px;">
                    New Preset
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">Start Time (24h) *</label>
                        <input type="time" name="start_time" id="newShiftStartModal" class="sched-input-time" required value="06:00" onchange="updateShiftPreviewModal()" style="height: 32px; font-size: 11.5px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">End Time (24h) *</label>
                        <input type="time" name="end_time" id="newShiftEndModal" class="sched-input-time" required value="15:00" onchange="updateShiftPreviewModal()" style="height: 32px; font-size: 11.5px;">
                    </div>
                </div>

                <!-- Auto-preview Badge -->
                <div>
                    <div id="shiftFormatPreviewModal" style="font-family: monospace; font-size: 13px; font-weight: 800; color: #7e22ce; padding: 8px 10px; background: rgba(168, 85, 247, 0.1); border: 1.5px solid rgba(168, 85, 247, 0.25); border-radius: 8px; text-align: center;">
                        0600 = 6AM - 3PM
                    </div>
                </div>

                <div style="background: rgba(168, 85, 247, 0.08); padding: 8px 10px; border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_overnight" id="isOvernightModal" value="1">
                    <label for="isOvernightModal" style="font-size: 11.5px; font-weight: 600; color: #6b21a8; cursor: pointer; margin: 0;">
                        Overnight Shift (Crosses calendar day)
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">Break (Min)</label>
                        <input type="number" name="break_minutes" class="sched-input-text-sm" required value="60" min="0" style="height: 32px; font-size: 11.5px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">Badge Color</label>
                        <input type="color" name="color" class="sched-input-text-sm" value="#8b5cf6" style="padding: 2px; height: 32px;">
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 7px 10px; font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-sparkle" style="color: #7c3aed; font-size: 13px;"></i>
                    <span>New shift template becomes immediately available across all rosters.</span>
                </div>

                <button type="submit" class="sched-btn-apply-custom" style="margin-top: auto;">
                    <i class="ph ph-plus-circle" style="font-size: 14px;"></i>
                    <span>Save Shift Template</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Default Shift Schedule Per Employee -->
<div id="defaultShiftModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 520px; width: 95%;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-bottom: 1.5px solid #bae6fd; padding: 14px 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);">
                    <i class="ph ph-clock-user"></i>
                </div>
                <div>
                    <h3 style="font-size: 15px; font-weight: 650; color: #0c4a6e; margin: 0;">Assign Default Shift Schedule</h3>
                    <p style="font-size: 11.5px; color: #0369a1; margin: 0; font-weight: 600;">Set standing shift template for employee profile</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('defaultShiftModal')" title="Close"><i class="ph ph-x"></i></button>
        </div>

        <form method="POST" action="{{ route('hr.attendance.schedules.assign-default-shift') }}" id="defaultShiftForm">
            @csrf
            <div class="hr-modal-body" style="padding: 18px;">
                <div class="hr-form-group" style="margin-bottom: 14px;">
                    <label class="hr-form-label" style="font-weight: 600; color: #0f172a;">Select Staff Member *</label>
                    <select name="employee_id" id="defaultShiftEmpSelect" class="hr-select" required style="width: 100%;">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->position?->name ?? 'Staff' }} &bull; {{ $e->branch?->name ?? 'Branch' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="hr-form-group" style="margin-bottom: 14px;">
                    <label class="hr-form-label" style="font-weight: 600; color: #0f172a;">Default Shift Template *</label>
                    <select name="shift_template_id" id="defaultShiftTmplSelect" class="hr-select" style="width: 100%;">
                        <option value="">-- No Default Shift (Clear Default) --</option>
                        @foreach($shiftTemplates as $tmpl)
                            <option value="{{ $tmpl->id }}">
                                [{{ $tmpl->code ?: 'SHIFT' }}] {{ $tmpl->name }} ({{ substr($tmpl->start_time, 0, 5) }} &ndash; {{ substr($tmpl->end_time, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    <small style="color: #64748b; font-size: 11px; margin-top: 4px; display: block;">This shift template will serve as the employee's standing baseline schedule.</small>
                </div>

                <div class="hr-form-group" style="margin-bottom: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 12.5px; color: #334155; font-weight: 500;">
                        <input type="checkbox" name="apply_future_blanks" value="1" checked style="accent-color: #0284c7;">
                        <span>Auto-apply to empty upcoming schedule slots (next 14 days)</span>
                    </label>
                </div>
            </div>

            <div class="hr-modal-footer" style="padding: 12px 18px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeModal('defaultShiftModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary hr-btn-sm" style="background: linear-gradient(135deg, #0284c7, #0369a1);">
                    <i class="ph ph-check-circle"></i> Save Default Shift
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Batch Fill Schedule Grid (Multi or All Employees as Draft) -->
<div id="batchFillGridModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 580px; width: 95%;">
        <!-- Header -->
        <div class="hr-modal-header" style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border-bottom: 1.5px solid #e9d5ff; padding: 14px 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);">
                    <i class="ph ph-magic-wand"></i>
                </div>
                <div>
                    <h3 style="font-size: 15px; font-weight: 650; color: #1e1b4b; margin: 0; letter-spacing: -0.2px;">Fill Schedule Grid</h3>
                    <p style="font-size: 11.5px; color: #6b21a8; margin: 0; font-weight: 600;">Select multiple or all employees to fill shifts as draft</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('batchFillGridModal')" title="Close"><i class="ph ph-x"></i></button>
        </div>

        <div class="hr-modal-body" style="padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; max-height: calc(85vh - 120px); overflow-y: auto;">
            
            <!-- Employee Scope Selection: All vs Multiple Specific -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <label style="font-size: 11px; font-weight: 600; color: #0f172a; text-transform: uppercase; margin: 0; letter-spacing: 0.3px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-users" style="color: #7c3aed; font-size: 14px;"></i>
                        Target Employees *
                    </label>
                    <span id="batchEmpSelectedSummaryBadge" class="sched-badge-count" style="background: #ede9fe; color: #6d28d9; font-weight: 600; padding: 2px 8px; border-radius: 999px; font-size: 11px;">
                        All in Grid
                    </span>
                </div>

                <!-- Scope Toggle Tabs -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 10px;">
                    <button type="button" id="btnBatchScopeAll" class="sched-scope-btn active" onclick="setBatchFillScope('all')">
                        <i class="ph ph-check-circle" id="iconBatchScopeAll"></i>
                        <span>All Employees in Grid</span>
                    </button>
                    <button type="button" id="btnBatchScopeCustom" class="sched-scope-btn" onclick="setBatchFillScope('custom')">
                        <i class="ph ph-list-checks" id="iconBatchScopeCustom"></i>
                        <span>Select Specific Employees</span>
                    </button>
                </div>

                <!-- Custom Employee Selector Box (Collapsible / Toggleable) -->
                <div id="batchCustomEmpWrap" style="display: none; flex-direction: column; gap: 8px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
                    <!-- Search & Quick Selection Row -->
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <div style="position: relative; flex: 1;">
                            <i class="ph ph-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
                            <input type="text" id="batchEmpSearchInput" placeholder="Filter by name, ID, position..." class="sched-input-search" style="padding-left: 28px; height: 32px; font-size: 11.5px;" oninput="filterBatchModalEmployees()">
                        </div>
                        <div style="display: flex; gap: 4px; flex-shrink: 0;">
                            <button type="button" class="sched-preset-btn" onclick="batchSelectAllEmps(true)">Select All</button>
                            <button type="button" class="sched-preset-btn" onclick="batchSelectAllEmps(false)">Clear</button>
                        </div>
                    </div>

                    <!-- Department Category Filter Pills -->
                    <div style="display: flex; gap: 4px; overflow-x: auto; padding-bottom: 2px;" id="batchDeptFilterBar">
                        <button type="button" class="batch-dept-pill active" onclick="filterBatchEmpsByDept('all', this)">All</button>
                        @foreach($categories as $cat)
                            <button type="button" class="batch-dept-pill" onclick="filterBatchEmpsByDept('{{ strtolower($cat) }}', this)">{{ $cat }}</button>
                        @endforeach
                    </div>

                    <!-- Scrollable Employee Checklist -->
                    <div id="batchEmpChecklist" class="custom-scrollbar" style="max-height: 150px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; background: #ffffff;">
                        <!-- Rendered dynamically from grid employees -->
                    </div>
                </div>

                <div id="batchAllEmpNotice" style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: #475569; font-weight: 600; padding: 4px 6px;">
                    <i class="ph ph-sparkle" style="color: #d97706; font-size: 14px;"></i>
                    <span>Will apply to <strong id="batchAllEmpCountText">all employees</strong> currently displayed on your schedule matrix.</span>
                </div>
            </div>

            <!-- Date Range Selection with Presets -->
            <div class="hr-form-group" style="margin-top: 0; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <label class="hr-form-label" style="font-size: 11px; font-weight: 600; color: #0f172a; margin-bottom: 0; text-transform: uppercase;">
                        Date Range *
                    </label>
                    <div style="display: flex; gap: 3px;">
                        <button type="button" class="sched-preset-btn" onclick="presetBatchDateRange('today')">Today</button>
                        <button type="button" class="sched-preset-btn" onclick="presetBatchDateRange('this_week')">This Week</button>
                        <button type="button" class="sched-preset-btn" onclick="presetBatchDateRange('next_week')">Next Week</button>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div>
                        <small style="font-size: 9.5px; color: #64748b; font-weight: 600; display: block; margin-bottom: 2px;">Start Date</small>
                        <input type="date" id="batchSchedStartDate" class="sched-input-text-sm" required value="{{ $weekStart }}" onchange="syncBatchDateRange()">
                    </div>
                    <div>
                        <small style="font-size: 9.5px; color: #64748b; font-weight: 600; display: block; margin-bottom: 2px;">End Date</small>
                        <input type="date" id="batchSchedEndDate" class="sched-input-text-sm" required value="{{ end($dates) }}">
                    </div>
                </div>
            </div>

            <!-- Shift Format Selection -->
            <div class="hr-form-group" style="margin-top: 0; margin-bottom: 0;">
                <label class="hr-form-label" style="font-size: 11px; font-weight: 600; color: #0f172a; margin-bottom: 3px; text-transform: uppercase;">
                    Shift Format *
                </label>
                <select id="batchSchedShiftTemplateId" class="sched-input-text-sm" style="font-weight: 400; height: 36px;">
                    <option value="">Select Shift Format</option>
                    @foreach($shiftTemplates as $st)
                        <option value="{{ $st->id }}" 
                                data-code="{{ $st->code }}" 
                                data-name="{{ $st->name }}" 
                                data-start="{{ substr($st->start_time, 0, 5) }}" 
                                data-end="{{ substr($st->end_time, 0, 5) }}"
                                data-color="{{ $st->color ?? '#7c3aed' }}"
                                {{ $loop->first ? 'selected' : '' }}>
                            {{ $st->formatted_label ?? $st->name }}
                        </option>
                    @endforeach
                    <option value="OFF" data-code="OFF" data-name="RESTDAY" data-start="" data-end="" data-color="#64748b">
                        RESTDAY = OFF DUTY (Rest Day)
                    </option>
                </select>
            </div>

            <!-- Weekly Rest Days Selection -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-top: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <div style="font-size: 10.5px; font-weight: 600; color: #0f172a; text-transform: uppercase;">
                        Rest Days (Off Duty)
                    </div>
                    <div style="display: flex; gap: 3px;">
                        <button type="button" class="sched-preset-btn" onclick="setBatchRestDaysPreset(['Sat', 'Sun'])">Sat & Sun</button>
                        <button type="button" class="sched-preset-btn" onclick="setBatchRestDaysPreset(['Sun'])">Sunday</button>
                        <button type="button" class="sched-preset-btn" onclick="clearBatchRestDaysPreset()">Clear</button>
                    </div>
                </div>
                <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: 6px;">
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                        <label style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: #334155; padding: 4px 8px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 7px; cursor: pointer; user-select: none;">
                            <input type="checkbox" value="{{ $day }}" id="batch_rest_day_{{ $day }}" class="batch-rest-day-cb" style="cursor: pointer;">
                            {{ $day }}
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Draft Notice Banner -->
            <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 9px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: #92400e; font-weight: 600;">
                <i class="ph ph-info" style="font-size: 16px; color: #d97706; flex-shrink: 0;"></i>
                <span><strong>Draft Mode:</strong> Shifts will be plotted into the grid as draft. They will NOT be saved to the database until you click the <strong>"Save"</strong> button in the toolbar.</span>
            </div>

        </div>

        <!-- Footer -->
        <div class="hr-modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeModal('batchFillGridModal')">
                Cancel
            </button>
            <button type="button" class="sched-btn-apply-custom" onclick="applyBatchFillGridDraft()" style="margin: 0; padding: 0 18px; height: 38px;">
                <i class="ph ph-magic-wand" style="font-size: 15px;"></i>
                <span id="batchApplyBtnText">Apply to Grid (Draft)</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal: Edit Shift Template -->
<div id="editShiftTemplateModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 440px;">
        <div class="hr-modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="ph ph-pencil-simple-line"></i>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 650; color: #0f172a; margin: 0; letter-spacing: -0.2px;">Edit Shift Template</h3>
                    <p style="font-size: 11px; color: #64748b; margin: 0;" id="editShiftModalSubtitle">Modify shift hours and settings</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('editShiftTemplateModal')"><i class="ph ph-x"></i></button>
        </div>

        <form id="editShiftTemplateForm" method="POST" action="">
            @csrf
            @method('PUT')
            <input type="hidden" id="editShiftId" value="">

            <div class="hr-modal-body" style="padding: 16px 18px; display: flex; flex-direction: column; gap: 12px;">
                <!-- Start & End Time -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Start Time (24h) *</label>
                        <input type="time" name="start_time" id="editShiftStartModal" class="sched-input-time" required onchange="updateEditShiftPreviewModal()" style="height: 36px; font-size: 12px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">End Time (24h) *</label>
                        <input type="time" name="end_time" id="editShiftEndModal" class="sched-input-time" required onchange="updateEditShiftPreviewModal()" style="height: 36px; font-size: 12px;">
                    </div>
                </div>

                <!-- Live Auto-preview Badge -->
                <div>
                    <div id="editShiftFormatPreviewModal" style="font-family: monospace; font-size: 13.5px; font-weight: 650; color: #7e22ce; padding: 9px 12px; background: rgba(168, 85, 247, 0.1); border: 1.5px solid rgba(168, 85, 247, 0.25); border-radius: 8px; text-align: center;">
                        0600 = 6AM - 3PM
                    </div>
                </div>

                <!-- Overnight checkbox -->
                <div style="background: rgba(168, 85, 247, 0.08); padding: 9px 12px; border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_overnight" id="editIsOvernightModal" value="1">
                    <label for="editIsOvernightModal" style="font-size: 11.5px; font-weight: 600; color: #6b21a8; cursor: pointer; margin: 0;">
                        Overnight Shift (Crosses calendar day)
                    </label>
                </div>

                <!-- Break & Color -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Break (Min)</label>
                        <input type="number" name="break_minutes" id="editShiftBreakModal" class="sched-input-text-sm" required min="0" style="height: 36px; font-size: 12px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Badge Color</label>
                        <input type="color" name="color" id="editShiftColorModal" class="sched-input-text-sm" style="padding: 2px; height: 36px;">
                    </div>
                </div>
            </div>

            <div class="hr-modal-footer" style="padding: 12px 18px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <button type="button" class="hr-btn hr-btn-sm" style="color: #ef4444; background: #fee2e2; border: 1px solid #fca5a5; font-weight: 700;" onclick="deleteShiftTemplateFromModal()">
                    <i class="ph ph-trash"></i> Delete Shift
                </button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeModal('editShiftTemplateModal')">Cancel</button>
                    <button type="submit" class="sched-btn-apply-custom" style="width: auto; padding: 0 16px; height: 34px;">
                        <i class="ph ph-check"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Form for Shift Templates -->
<form id="deleteShiftTemplateForm" method="POST" action="" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Modal: Shift Master Settings (Configurable hours for O, MD, LD, C per Department matching Departments tab) -->
<div id="shiftMasterSettingsModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="sched-avatar-circle" style="background: #ede9fe; color: #7c3aed; width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="ph ph-sliders"></i>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 650; color: #0f172a; margin: 0; letter-spacing: -0.2px;">Shift Master Settings by Department</h3>
                    <p style="font-size: 11px; color: #64748b; margin: 0;">Configure default shift hours for each Department from the Department tab.</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('shiftMasterSettingsModal')"><i class="ph ph-x"></i></button>
        </div>

        <div class="hr-modal-body" style="padding: 16px 18px;">
            <!-- Department Tabs (Connected to Department Tab in People > Departments) -->
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 10.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0;">
                        Select Department
                    </label>
                    <a href="{{ route('hr.people.departments') }}" target="_blank" style="font-size: 11px; font-weight: 700; color: #7c3aed; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Open Departments tab in new window">
                        <i class="ph ph-tree-structure"></i> Manage in Department Tab <i class="ph ph-arrow-square-out" style="font-size: 11px;"></i>
                    </a>
                </div>
                <div class="sched-cat-tabs" id="shiftModalCatTabs">
                    @forelse($departmentsList as $idx => $dept)
                        <button type="button" class="sched-cat-tab-btn {{ $loop->first ? 'active' : '' }}" 
                                onclick="switchShiftModalCategory('{{ $dept->name }}', this)">
                            <i class="ph ph-buildings" style="font-size: 12px; margin-right: 3px;"></i>
                            <span>{{ $dept->name }}</span>
                        </button>
                    @empty
                        <div style="font-size: 12px; color: #94a3b8; font-style: italic; padding: 6px;">
                            No active departments found.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Informational Banner -->
            <div style="display: flex; justify-content: space-between; align-items: center; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; font-size: 11.5px; color: #1e40af;">
                <span>Editing default shift times for <strong id="modalActiveCategoryLabel">{{ $departmentsList->first()?->name ?? 'Front of House' }}</strong> department</span>
                <button type="button" onclick="resetShiftCategoryDefaults()" style="border: none; background: transparent; color: #2563eb; font-weight: 700; text-decoration: underline; cursor: pointer; font-size: 11px;">
                    Reset to Default
                </button>
            </div>

            <!-- Shift Rows Editor -->
            <div class="sched-shift-edit-rows" id="shiftRowsEditor">
                <!-- O (OPENING) -->
                <div class="sched-edit-row">
                    <div class="sched-edit-row-badge" style="background: #10b981;">O</div>
                    <div style="min-width: 90px;">
                        <div style="font-weight: 600; font-size: 12px; color: #0f172a;" id="lblShift_O">OPENING</div>
                        <div style="font-size: 10px; color: #94a3b8;">SHIFT PRESET</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex: 1;">
                        <input type="time" id="shiftTime_O_start" value="08:00" class="sched-input-time" style="flex: 1;">
                        <span style="color: #94a3b8; font-weight: bold;">→</span>
                        <input type="time" id="shiftTime_O_end" value="17:00" class="sched-input-time" style="flex: 1;">
                    </div>
                </div>

                <!-- MD (MID DAY) -->
                <div class="sched-edit-row">
                    <div class="sched-edit-row-badge" style="background: #3b82f6;">MD</div>
                    <div style="min-width: 90px;">
                        <div style="font-weight: 600; font-size: 12px; color: #0f172a;" id="lblShift_MD">MID DAY</div>
                        <div style="font-size: 10px; color: #94a3b8;">SHIFT PRESET</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex: 1;">
                        <input type="time" id="shiftTime_MD_start" value="10:00" class="sched-input-time" style="flex: 1;">
                        <span style="color: #94a3b8; font-weight: bold;">→</span>
                        <input type="time" id="shiftTime_MD_end" value="19:00" class="sched-input-time" style="flex: 1;">
                    </div>
                </div>

                <!-- LD (LATE DAY) -->
                <div class="sched-edit-row">
                    <div class="sched-edit-row-badge" style="background: #f59e0b;">LD</div>
                    <div style="min-width: 90px;">
                        <div style="font-weight: 600; font-size: 12px; color: #0f172a;" id="lblShift_LD">LATE DAY</div>
                        <div style="font-size: 10px; color: #94a3b8;">SHIFT PRESET</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex: 1;">
                        <input type="time" id="shiftTime_LD_start" value="12:00" class="sched-input-time" style="flex: 1;">
                        <span style="color: #94a3b8; font-weight: bold;">→</span>
                        <input type="time" id="shiftTime_LD_end" value="21:00" class="sched-input-time" style="flex: 1;">
                    </div>
                </div>

                <!-- C (CLOSING) -->
                <div class="sched-edit-row">
                    <div class="sched-edit-row-badge" style="background: #9333ea;">C</div>
                    <div style="min-width: 90px;">
                        <div style="font-weight: 600; font-size: 12px; color: #0f172a;" id="lblShift_C">CLOSING</div>
                        <div style="font-size: 10px; color: #94a3b8;">SHIFT PRESET</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; flex: 1;">
                        <input type="time" id="shiftTime_C_start" value="13:00" class="sched-input-time" style="flex: 1;">
                        <span style="color: #94a3b8; font-weight: bold;">→</span>
                        <input type="time" id="shiftTime_C_end" value="22:00" class="sched-input-time" style="flex: 1;">
                    </div>
                </div>

                <!-- OFF (RESTDAY) -->
                <div class="sched-edit-row" style="background: #f8fafc;">
                    <div class="sched-edit-row-badge" style="background: #64748b;">OFF</div>
                    <div style="min-width: 90px;">
                        <div style="font-weight: 600; font-size: 12px; color: #475569;">RESTDAY</div>
                        <div style="font-size: 10px; color: #94a3b8;">OFF DUTY</div>
                    </div>
                    <div style="flex: 1; font-size: 11px; color: #64748b; font-style: italic;">
                        Off Duty / No Scheduled Working Hours
                    </div>
                </div>
            </div>
        </div>

        <div class="hr-modal-footer" style="background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeModal('shiftMasterSettingsModal')">Cancel</button>
            <button type="button" class="hr-btn hr-btn-primary" onclick="saveShiftMasterSettings()">
                <i class="ph ph-check"></i> Save Shift Settings
            </button>
        </div>
    </div>
</div>

<!-- Floating Action Toast Notification -->
<div id="schedToast" class="sched-toast" style="display: none;">
    <div id="schedToastIcon"><i class="ph ph-check-circle"></i></div>
    <div id="schedToastMsg">Shift updated successfully</div>
</div>

<!-- Modal: Assign Schedule (Date Range & Rest Day by Day) -->
<div id="assignScheduleModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 680px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> Assign Work Schedule</span>
            <button type="button" class="icon-btn" onclick="closeModal('assignScheduleModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.schedules.store') }}" id="assignScheduleForm">
            @csrf
            <div class="hr-modal-body">
                <!-- Employee Selection -->
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee *</label>
                    <select name="employee_id" id="schedEmployeeId" class="hr-select" required>
                        <option value="all">★ All Active Employees</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->position?->name ?? 'Staff' }} - {{ $e->branch?->name }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Selection -->
                <div class="hr-form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label class="hr-form-label" style="margin-bottom: 0;">Date Range *</label>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="sched-preset-btn" onclick="presetDateRange('today')">Today</button>
                            <button type="button" class="sched-preset-btn" onclick="presetDateRange('this_week')">This Week</button>
                            <button type="button" class="sched-preset-btn" onclick="presetDateRange('next_week')">Next Week</button>
                            <button type="button" class="sched-preset-btn" onclick="presetDateRange('month')">Full Month</button>
                        </div>
                    </div>
                    <div class="hr-form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <small style="font-size: 11px; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Start Date</small>
                            <input type="date" name="start_date" id="schedStartDate" class="hr-input" required value="{{ date('Y-m-d') }}" onchange="syncDateRange()">
                        </div>
                        <div>
                            <small style="font-size: 11px; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">End Date</small>
                            <input type="date" name="end_date" id="schedEndDate" class="hr-input" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <small style="color: #64748b; font-size: 11.5px; margin-top: 4px;">
                        Select start and end dates to apply this assignment across a custom date range.
                    </small>
                </div>

                <!-- Work Shift Selection -->
                <div class="hr-form-group" id="shiftSelectionGroup">
                    <label class="hr-form-label">Shift Format *</label>
                    <select name="shift_template_id" id="schedShiftTemplateId" class="hr-select">
                        <option value="">Select Shift Format</option>
                        @foreach($shiftTemplates as $st)
                            <option value="{{ $st->id }}" {{ $loop->first ? 'selected' : '' }}>
                                {{ $st->formatted_label ?? $st->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Assign Rest Day by Day (Mon - Sun) -->
                <div class="hr-form-group" style="background: rgba(248, 250, 252, 0.85); border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                Weekly Rest Days (Mon – Sun)
                            </div>
                            <div style="font-size: 11px; color: #64748b;">
                                Selected weekdays will be assigned as <strong>Rest Day</strong> within your date range.
                            </div>
                        </div>
                        <div style="display: flex; gap: 4px;">
                            <button type="button" class="sched-preset-btn" onclick="setRestDaysPreset(['Sat', 'Sun'])">Sat & Sun</button>
                            <button type="button" class="sched-preset-btn" onclick="setRestDaysPreset(['Sun'])">Sunday</button>
                            <button type="button" class="sched-preset-btn" onclick="clearRestDaysPreset()">Clear</button>
                        </div>
                    </div>

                    <div class="sched-day-pills">
                        @foreach(['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'] as $short => $full)
                            <label class="sched-day-pill" title="{{ $full }}">
                                <input type="checkbox" name="rest_days[]" value="{{ $short }}" class="sched-day-input" id="rest_day_{{ $short }}">
                                <span class="sched-day-label">{{ $short }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1; display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="is_rest_day" id="isRestDayAll" value="1" onchange="toggleRestDayAll(this)">
                            <span>Designate Entire Date Range as Rest Day (All Days in Range)</span>
                        </label>

                        <div id="applyModeBox" style="display: flex; gap: 16px; align-items: center; font-size: 12px; color: #475569; margin-top: 2px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="apply_mode" value="standard" checked>
                                <span>Assign selected shift on work days, rest day on checked weekdays</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="apply_mode" value="rest_only">
                                <span>Only assign Rest Days on checked weekdays (keep existing shifts untouched)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Notes (Optional)</label>
                    <input type="text" name="notes" class="hr-input" placeholder="e.g. Regular schedule, holiday rotation">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('assignScheduleModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Apply & Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Custom Shift Template -->
<div id="addShiftModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 520px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-clock-afternoon"></i> Create Shift Template</span>
            <button type="button" class="icon-btn" onclick="closeModal('addShiftModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.shifts.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Time (24h) *</label>
                        <input type="time" name="start_time" id="newShiftStart" class="hr-input" required value="06:00" onchange="updateShiftPreview()">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Time (24h) *</label>
                        <input type="time" name="end_time" id="newShiftEnd" class="hr-input" required value="15:00" onchange="updateShiftPreview()">
                    </div>
                </div>

                <!-- Live Auto-Format Preview -->
                <div class="hr-form-group">
                    <label class="hr-form-label">Generated Format Preview</label>
                    <div id="shiftFormatPreview" style="font-family: monospace; font-size: 15px; font-weight: 700; color: #7e22ce; padding: 12px 16px; background: rgba(168, 85, 247, 0.1); border: 1.5px solid rgba(168, 85, 247, 0.25); border-radius: 10px; text-align: center;">
                        0600 = 6AM - 3PM
                    </div>
                    <small style="color: #64748b; font-size: 11px; margin-top: 4px;">
                        Standardized 24h code and AM/PM time range format.
                    </small>
                </div>

                <div class="hr-form-group" style="background: rgba(168, 85, 247, 0.08); padding: 12px; border-radius: 10px; display: flex; flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_overnight" id="isOvernight" value="1">
                    <label for="isOvernight" style="font-size: 12.5px; font-weight: 600; color: #6b21a8; cursor: pointer; margin: 0;">
                        Overnight Shift (End time crosses into the following calendar day)
                    </label>
                </div>

                <div class="hr-form-grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Break Duration (Minutes)</label>
                        <input type="number" name="break_minutes" class="hr-input" required value="60" min="0">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Shift Badge Color</label>
                        <input type="color" name="color" class="hr-input" value="#8b5cf6" style="height: 38px; padding: 2px;">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addShiftModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Shift Template</button>
            </div>
        </form>
    </div>
</div>
@endsection
