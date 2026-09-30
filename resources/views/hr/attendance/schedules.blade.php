@extends('layouts.app')

@section('title', 'Work Schedule Management - Attendance')

@section('content')
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

<x-hr-tabs parent="time-attendance" />

<!-- Main Accelerated Weekly Schedule Planner Matrix Card -->
<div class="hr-table-card" id="schedPlannerCard">
    <!-- Top Controller Toolbar -->
    <div class="hr-table-header" style="flex-wrap: nowrap; gap: 12px; padding: 8px 16px; border-bottom: 1.5px solid #e2e8f0; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
        <!-- Title & Subtitle -->
        <div style="display: flex; align-items: center; gap: 9px; flex-shrink: 0;">
            <span class="hr-table-title" style="font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-calendar-check" style="color: #7c3aed; font-size: 21px;"></i> 
                Shift Schedule Planner
            </span>
            <span class="hr-badge hr-badge-neutral" id="empCountBadge" style="font-size: 12px; font-weight: 800; padding: 2px 9px;">
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
                <button type="button" class="sched-top-btn sched-top-btn-secondary" onclick="openShiftMasterModal()" title="Shift Settings">
                    <i class="ph ph-sliders" style="color: #64748b; font-size: 15px;"></i>
                    <span>Shift Settings</span>
                </button>
                <button type="button" class="sched-top-btn sched-top-btn-save" id="btnSaveGrid" onclick="saveDraftSchedules()" title="Save Grid Schedules">
                    <i class="ph ph-floppy-disk" style="font-size: 15px;"></i>
                    <span id="saveBtnLabel">Save</span>
                    <span id="saveBtnBadge" class="sched-badge-count" style="display: none; background: #ffffff; color: #7c3aed; font-weight: 800; margin-left: 5px; padding: 1px 7px; border-radius: 999px; font-size: 10.5px;">0</span>
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
                            <h4 style="font-size: 12px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase;">Select Employees to Schedule</h4>
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
            <span style="font-size: 11px; font-weight: 800; color: #64748b; display: flex; align-items: center; gap: 4px; text-transform: uppercase;">
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
        <div class="sched-live-search-wrap">
            <i class="ph ph-magnifying-glass sched-live-search-icon"></i>
            <input type="text" id="liveEmployeeSearch" class="sched-live-search-input" placeholder="Search visible rows..." 
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
        <table class="hr-table sched-matrix-table" id="schedMatrixTable" role="grid" style="border-collapse: separate; border-spacing: 0; table-layout: fixed; width: 100%; min-width: 1600px;">
            <colgroup>
                <col class="sched-col-emp" style="width: 220px; min-width: 220px;">
                <col class="sched-col-week" style="width: 80px; min-width: 80px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-day" style="width: 180px; min-width: 175px;">
                <col class="sched-col-action" style="width: 46px; min-width: 44px;">
            </colgroup>
            <thead>
                <tr>
                    <!-- Sticky Left Column: Employee Identity & Row Quick Actions -->
                    <th class="sched-sticky-col sched-sticky-th" style="z-index: 30;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 800;">
                                <i class="ph ph-user" style="font-size: 15px;"></i> Employee
                            </span>
                            <span style="font-size: 10px; font-weight: 800; color: #7c3aed; background: #ede9fe; padding: 2.5px 7px; border-radius: 5px; text-transform: uppercase;">
                                Branch &darr;
                            </span>
                        </div>
                    </th>

                    <!-- Week Column matching Picture 2 -->
                    <th class="sched-col-week" style="text-align: center;">
                        <div style="font-weight: 800; font-size: 12.5px; color: #475569; display: flex; align-items: center; justify-content: center; gap: 4px;">
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
                            <div style="font-weight: 800; font-size: 13px; color: {{ $isToday ? '#7c3aed' : '#0f172a' }}; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <i class="ph ph-calendar-blank" style="font-size: 13px;"></i>
                                {{ $cDate->format('D') }}
                                @if($isToday)
                                    <span class="hr-badge hr-badge-primary" style="font-size: 9px; font-weight: 800; padding: 1.5px 5px; vertical-align: middle;">TODAY</span>
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
                            'management' => '#8b5cf6',
                            'front of house' => '#3b82f6',
                            'back of house' => '#10b981',
                            'finance & admin' => '#f59e0b',
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
                                    <div style="font-weight: 800; color: #0f172a; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25;" title="{{ $emp->full_name }}">
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
                                </div>

                                <!-- ⚡ Quick Fill Menu for this row -->
                                <div class="sched-row-action-menu" style="position: relative;">
                                    <button type="button" class="sched-row-btn" onclick="toggleRowMenu({{ $emp->id }}, this)" title="Quick fill week for {{ $emp->full_name }}">
                                        <i class="ph ph-lightning"></i>
                                    </button>
                                    
                                    <div class="sched-row-dropdown" id="rowMenu_{{ $emp->id }}" style="display: none;">
                                        <div style="padding: 6px 10px; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
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
                        <td class="sched-col-week" style="text-align: center; vertical-align: middle; color: #64748b; font-size: 12px; font-weight: 700; font-family: monospace;">
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

                                        // Theme & Code Classification: Opening should ALWAYS be Green (sched-theme-o)
                                        if ($isRest) {
                                            $sCode = 'OFF';
                                            $sLabel = 'RESTDAY';
                                            $themeClass = 'sched-card-rest';
                                        } elseif ($rawCode === 'O' || str_contains($rawCode, 'OPEN') || str_contains($tmplName, 'OPEN') || ($startTime && $startTime <= '10:30') || in_array($rawCode, ['0600', '0700', '0800', '0900', '1000'])) {
                                            $sCode = 'O';
                                            $sLabel = 'OPENING';
                                            $themeClass = 'sched-theme-o';
                                        } elseif ($rawCode === 'MD' || str_contains($rawCode, 'MID') || str_contains($tmplName, 'MID') || ($startTime && $startTime > '10:30' && $startTime <= '13:30') || in_array($rawCode, ['1100', '1200', '1300', '1400'])) {
                                            $sCode = 'MD';
                                            $sLabel = 'MID DAY';
                                            $themeClass = 'sched-theme-md';
                                        } elseif ($rawCode === 'LD' || str_contains($rawCode, 'LATE') || str_contains($tmplName, 'LATE') || ($startTime && $startTime > '13:30' && $startTime <= '16:30') || in_array($rawCode, ['1500', '1600', '1700'])) {
                                            $sCode = 'LD';
                                            $sLabel = 'LATE DAY';
                                            $themeClass = 'sched-theme-ld';
                                        } elseif ($rawCode === 'C' || str_contains($rawCode, 'CLOS') || str_contains($tmplName, 'CLOS') || ($startTime && $startTime > '16:30') || in_array($rawCode, ['1800', '1900', '2000', '2100', '2200', '2300'])) {
                                            $sCode = 'C';
                                            $sLabel = 'CLOSING';
                                            $themeClass = 'sched-theme-c';
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
                <select name="shift_template_id" id="schedShiftTemplateIdModal" class="sched-input-text-sm" style="font-weight: 700;">
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
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">Break (Min)</label>
                        <input type="number" name="break_minutes" class="sched-input-text-sm" required value="60" min="0" style="height: 32px; font-size: 11.5px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">Badge Color</label>
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
                    <h3 style="font-size: 15px; font-weight: 800; color: #1e1b4b; margin: 0; letter-spacing: -0.2px;">Fill Schedule Grid</h3>
                    <p style="font-size: 11.5px; color: #6b21a8; margin: 0; font-weight: 600;">Select multiple or all employees to fill shifts as draft</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('batchFillGridModal')" title="Close"><i class="ph ph-x"></i></button>
        </div>

        <div class="hr-modal-body" style="padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; max-height: calc(85vh - 120px); overflow-y: auto;">
            
            <!-- Employee Scope Selection: All vs Multiple Specific -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <label style="font-size: 11px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin: 0; letter-spacing: 0.3px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-users" style="color: #7c3aed; font-size: 14px;"></i>
                        Target Employees *
                    </label>
                    <span id="batchEmpSelectedSummaryBadge" class="sched-badge-count" style="background: #ede9fe; color: #6d28d9; font-weight: 800; padding: 2px 8px; border-radius: 999px; font-size: 11px;">
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
                    <label class="hr-form-label" style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 0; text-transform: uppercase;">
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
                        <small style="font-size: 9.5px; color: #64748b; font-weight: 700; display: block; margin-bottom: 2px;">Start Date</small>
                        <input type="date" id="batchSchedStartDate" class="sched-input-text-sm" required value="{{ $weekStart }}" onchange="syncBatchDateRange()">
                    </div>
                    <div>
                        <small style="font-size: 9.5px; color: #64748b; font-weight: 700; display: block; margin-bottom: 2px;">End Date</small>
                        <input type="date" id="batchSchedEndDate" class="sched-input-text-sm" required value="{{ end($dates) }}">
                    </div>
                </div>
            </div>

            <!-- Shift Format Selection -->
            <div class="hr-form-group" style="margin-top: 0; margin-bottom: 0;">
                <label class="hr-form-label" style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 3px; text-transform: uppercase;">
                    Shift Format *
                </label>
                <select id="batchSchedShiftTemplateId" class="sched-input-text-sm" style="font-weight: 700; height: 36px;">
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
                    <div style="font-size: 10.5px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
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
                    <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.2px;">Edit Shift Template</h3>
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
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Start Time (24h) *</label>
                        <input type="time" name="start_time" id="editShiftStartModal" class="sched-input-time" required onchange="updateEditShiftPreviewModal()" style="height: 36px; font-size: 12px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">End Time (24h) *</label>
                        <input type="time" name="end_time" id="editShiftEndModal" class="sched-input-time" required onchange="updateEditShiftPreviewModal()" style="height: 36px; font-size: 12px;">
                    </div>
                </div>

                <!-- Live Auto-preview Badge -->
                <div>
                    <div id="editShiftFormatPreviewModal" style="font-family: monospace; font-size: 13.5px; font-weight: 800; color: #7e22ce; padding: 9px 12px; background: rgba(168, 85, 247, 0.1); border: 1.5px solid rgba(168, 85, 247, 0.25); border-radius: 8px; text-align: center;">
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
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Break (Min)</label>
                        <input type="number" name="break_minutes" id="editShiftBreakModal" class="sched-input-text-sm" required min="0" style="height: 36px; font-size: 12px;">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">Badge Color</label>
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
                    <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.2px;">Shift Master Settings by Department</h3>
                    <p style="font-size: 11px; color: #64748b; margin: 0;">Configure default shift hours for each Department from the Department tab.</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('shiftMasterSettingsModal')"><i class="ph ph-x"></i></button>
        </div>

        <div class="hr-modal-body" style="padding: 16px 18px;">
            <!-- Department Tabs (Connected to Department Tab in People > Departments) -->
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0;">
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
                        <div style="font-weight: 800; font-size: 12px; color: #0f172a;" id="lblShift_O">OPENING</div>
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
                        <div style="font-weight: 800; font-size: 12px; color: #0f172a;" id="lblShift_MD">MID DAY</div>
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
                        <div style="font-weight: 800; font-size: 12px; color: #0f172a;" id="lblShift_LD">LATE DAY</div>
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
                        <div style="font-weight: 800; font-size: 12px; color: #0f172a;" id="lblShift_C">CLOSING</div>
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
                        <div style="font-weight: 800; font-size: 12px; color: #475569;">RESTDAY</div>
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

@push('styles')
<style>
/* 1. Viewport & Container Lock: Fit Weekly Table to Screen with Zero Page Scroll */
.content-area {
    height: calc(100vh - 64px) !important;
    max-height: calc(100vh - 64px) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 14px 20px 12px 20px !important;
    box-sizing: border-box !important;
}

/* Spacious Parent Header matching standard Time & Attendance (Picture 1) */
.hr-parent-header {
    margin-bottom: 12px !important;
    padding-bottom: 0 !important;
    flex-shrink: 0 !important;
}
.hr-parent-title-row {
    margin-bottom: 8px !important;
    gap: 16px !important;
}
.hr-parent-title {
    font-size: 23px !important;
    font-weight: 800 !important;
    gap: 12px !important;
}
.hr-parent-title i {
    width: 40px !important;
    height: 40px !important;
    font-size: 21px !important;
    border-radius: 12px !important;
}
.hr-parent-subtitle {
    display: block !important;
    font-size: 13px !important;
    color: #64748b !important;
    margin-top: 4px !important;
    font-weight: 400 !important;
}
.hr-tabs-wrapper {
    margin-top: 10px !important;
    margin-bottom: 12px !important;
    padding: 6px !important;
    border-radius: 14px !important;
    flex-shrink: 0 !important;
}
.hr-tab-item {
    padding: 8px 18px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    border-radius: 10px !important;
}

/* Schedule Planner Card: Full Flex Column */
#schedPlannerCard {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    margin-bottom: 0 !important;
    overflow: hidden !important;
    border-radius: 12px !important;
}

#schedPlannerCard .hr-table-wrapper {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    min-width: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    max-height: none !important;
    overflow-y: auto !important;
    overflow-x: auto !important;
    position: relative !important;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

#schedPlannerCard .hr-table-wrapper::-webkit-scrollbar {
    width: 8px !important;
    height: 10px !important;
}
#schedPlannerCard .hr-table-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9 !important;
    border-radius: 6px;
}
#schedPlannerCard .hr-table-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1 !important;
    border-radius: 6px;
    border: 2px solid #f1f5f9;
}
#schedPlannerCard .hr-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8 !important;
}

/* Sticky thead for matrix table (Spacious padding) */
.sched-matrix-table thead th {
    position: sticky !important;
    top: 0 !important;
    background: #f8fafc !important;
    z-index: 20 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    padding: 8px 6px !important;
}
.sched-matrix-table thead th.sched-sticky-th {
    z-index: 35 !important;
    left: 0 !important;
}

/* Sub Toolbar (Add Employee Row + Category Checkboxes + Search + Clear) */
.sched-sub-toolbar {
    padding: 6px 16px;
    background: #f8fafc;
    border-bottom: 1.5px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    position: relative;
    z-index: 25;
}

.sched-add-emp-wrap {
    position: relative;
}

.sched-btn-add-emp-toggle {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.15s ease;
}
.sched-btn-add-emp-toggle:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}

.sched-badge-count {
    background: #7c3aed;
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 10px;
}

/* Dropdown Panel */
.sched-emp-dropdown-panel {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 580px;
    max-width: 92vw;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    z-index: 100;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sched-dropdown-header {
    padding: 12px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sched-dropdown-filter-bar {
    padding: 10px 14px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sched-input-search {
    width: 100%;
    font-size: 12px;
    padding: 7px 28px 7px 30px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    outline: none;
}
.sched-input-search:focus {
    border-color: #7c3aed;
}

.sched-select-filter {
    flex: 1;
    font-size: 11.5px;
    padding: 5px 8px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    background: #f8fafc;
    color: #334155;
    outline: none;
    font-weight: 600;
}

.sched-btn-clear-filters {
    font-size: 11px;
    padding: 5px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #f1f5f9;
    color: #475569;
    cursor: pointer;
    font-weight: 600;
    white-space: nowrap;
}
.sched-btn-clear-filters:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}

.sched-dropdown-list {
    max-height: 260px;
    overflow-y: auto;
    padding: 8px 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: #fafafa;
}

.sched-emp-row-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: all 0.15s ease;
    cursor: pointer;
}
.sched-emp-row-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
}
.sched-emp-row-item.selected {
    background: #f5f3ff;
    border-color: #c4b5fd;
}
.sched-emp-row-item.already-added {
    opacity: 0.65;
    background: #f1f5f9;
}

.sched-dropdown-footer {
    padding: 10px 14px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Category Filter Checkboxes */
.sched-cat-filter-group {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 5px 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.sched-cat-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    user-select: none;
}

.sched-btn-clear-grid {
    font-size: 12.5px;
    font-weight: 700;
    color: #ef4444;
    border: 1px solid #fecaca;
    background: #fff;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}
.sched-btn-clear-grid:hover {
    background: #fef2f2;
}

/* Real-time Search Input (Prominent, High-Contrast & Standout) */
.sched-live-search-wrap {
    position: relative;
    min-width: 260px;
    max-width: 380px;
    margin-left: auto;
    display: flex;
    align-items: center;
}

.sched-live-search-input {
    width: 100%;
    height: 38px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    padding: 0 34px 0 38px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 9px;
    outline: none;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
}

.sched-live-search-input::placeholder {
    color: #64748b;
    font-weight: 500;
    font-size: 12.5px;
}

.sched-live-search-wrap:hover .sched-live-search-input {
    border-color: #8b5cf6;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.12);
}

.sched-live-search-input:focus {
    border-color: #7c3aed;
    background: #ffffff;
    box-shadow: 0 0 0 3.5px rgba(124, 58, 237, 0.18), 0 2px 8px rgba(124, 58, 237, 0.1);
}

.sched-live-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #7c3aed;
    font-size: 16px;
    pointer-events: none;
    transition: color 0.15s ease;
}

.sched-live-search-input:focus ~ .sched-live-search-icon {
    color: #6d28d9;
}

.sched-live-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    line-height: 1;
    transition: all 0.15s ease;
    padding: 0;
}

.sched-live-search-clear:hover {
    background: #fee2e2;
    color: #ef4444;
}

.sched-btn-row-del {
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    font-size: 13px;
    padding: 4px;
    border-radius: 4px;
    transition: color 0.15s, transform 0.15s;
}
.sched-btn-row-del:hover {
    color: #ef4444;
    transform: scale(1.15);
}

/* Matrix Table Fixed Layout (Spacious, Breathable & Fits Cleanly with Horizontal Scroll) */
.sched-matrix-table {
    width: 100% !important;
    min-width: 1600px !important;
    table-layout: fixed !important;
    border-collapse: separate !important;
    margin: 0 !important;
}

/* Enforce Column Widths so table never shrinks when sidebar is opened */
.sched-col-emp,
.sched-sticky-th,
.sched-sticky-td {
    width: 220px !important;
    min-width: 220px !important;
}

.sched-col-week {
    width: 80px !important;
    min-width: 80px !important;
    text-align: center !important;
}

.sched-col-day,
.sched-day-th,
.sched-cell-td {
    width: 180px !important;
    min-width: 175px !important;
}

.sched-col-action,
.sched-action-th,
.sched-action-td {
    width: 46px !important;
    min-width: 44px !important;
    text-align: center !important;
}

.sched-sticky-th {
    position: sticky !important;
    left: 0;
    background: #f8fafc !important;
    border-right: 2px solid #e2e8f0 !important;
    box-shadow: 3px 0 6px rgba(0,0,0,0.03);
    overflow: hidden;
    padding: 8px 12px !important;
}
.sched-sticky-td {
    position: sticky !important;
    left: 0;
    background: #ffffff !important;
    border-right: 2px solid #e2e8f0 !important;
    box-shadow: 3px 0 6px rgba(0,0,0,0.02);
    z-index: 10;
    overflow: visible !important;
    padding: 7px 12px !important;
}
.sched-row {
    height: 104px !important;
}
.sched-row td {
    height: 104px;
}
.sched-row:hover .sched-sticky-td {
    background: #f8fafc !important;
}

.sched-emp-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1.5px solid #e2e8f0;
    color: #1e293b;
    font-weight: 800;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sched-dept-pill-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 2.5px;
    max-width: 100%;
    vertical-align: middle;
}

.sched-dept-nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 17px;
    height: 17px;
    padding: 0;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    background: #ffffff;
    color: #64748b;
    font-size: 11px;
    line-height: 1;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    flex-shrink: 0;
    user-select: none;
}

.sched-dept-nav-btn:hover {
    background: #7c3aed;
    color: #ffffff;
    border-color: #7c3aed;
    box-shadow: 0 1px 3px rgba(124, 58, 237, 0.25);
    transform: scale(1.08);
}

.sched-dept-nav-btn:active {
    transform: scale(0.92);
}

.sched-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    max-width: 112px;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}

.sched-cat-pill:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.sched-cat-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #7c3aed;
    flex-shrink: 0;
    transition: background-color 0.2s ease;
}

.sched-dept-tab-link {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 700;
    color: #7c3aed;
    text-decoration: none;
    padding: 2px 6px;
    border-radius: 4px;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    margin-left: 2px;
    transition: all 0.15s;
    user-select: none;
}

.sched-dept-tab-link:hover {
    background: #ede9fe;
    color: #6d28d9;
    border-color: #c4b5fd;
}

/* Cell Preset Buttons (Spacious & Breathable Grid) */
.sched-cell-td {
    padding: 4px 3px !important;
    vertical-align: middle !important;
    text-align: center;
    position: relative;
    min-height: 104px !important;
    box-sizing: border-box;
}

.sched-empty-plotter {
    display: flex;
    align-items: stretch;
    justify-content: center;
    height: 100%;
    width: 100%;
    padding: 0;
    box-sizing: border-box;
}

.sched-preset-grid {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 3px;
    width: 100%;
    height: 100%;
    min-height: 100px;
    margin: 0;
    border: 1.5px dashed #93c5fd;
    border-radius: 8px;
    padding: 4px 5px;
    background: #ffffff;
    box-sizing: border-box;
    transition: all 0.15s ease;
}

.sched-preset-grid:hover,
.sched-cell-td:hover .sched-preset-grid {
    border-color: #3b82f6;
    background: #f8fafc;
}

.sched-btn-row {
    display: flex;
    gap: 3.5px;
    width: 100%;
    flex: 1;
    min-height: 25px;
}

.sched-mini-pill {
    flex: 1;
    height: 100%;
    min-height: 25px;
    padding: 0 5px;
    font-size: 11px;
    font-weight: 800;
    font-family: var(--font-heading, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
    border-radius: 6px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.12s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    user-select: none;
    line-height: 1;
    box-sizing: border-box;
}

/* Preset Button Colors */
.sched-pill-o { color: #059669; border-color: #86efac; background: #ffffff; }
.sched-pill-o:hover { background: #059669; color: #fff; border-color: #059669; transform: translateY(-1px); }

.sched-pill-md { color: #2563eb; border-color: #93c5fd; background: #ffffff; }
.sched-pill-md:hover { background: #2563eb; color: #fff; border-color: #2563eb; transform: translateY(-1px); }

.sched-pill-ld { color: #d97706; border-color: #fde68a; background: #ffffff; }
.sched-pill-ld:hover { background: #d97706; color: #fff; border-color: #d97706; transform: translateY(-1px); }

.sched-pill-c { flex: 1; color: #9333ea; border-color: #d8b4fe; background: #ffffff; }
.sched-pill-c:hover { background: #9333ea; color: #fff; border-color: #9333ea; transform: translateY(-1px); }

.sched-pill-restday { flex: 2; color: #0f172a; border-color: #cbd5e1; background: #ffffff; font-size: 9.5px; font-weight: 800; letter-spacing: 0.2px; }
.sched-pill-restday:hover { background: #475569; color: #fff; border-color: #475569; transform: translateY(-1px); }

.sched-pill-custom {
    flex: 1;
    width: 100%;
    color: #475569;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    font-size: 10px;
    font-weight: 700;
    gap: 4px;
    font-family: inherit;
    border-radius: 6px;
    min-height: 25px;
}
.sched-pill-custom:hover {
    background: #6366f1;
    color: #ffffff;
    border: 1px solid #6366f1;
    transform: translateY(-1px);
}

/* Assigned Shift Card (Spacious, Airy, Rich in Details & Distinct Colors) */
.sched-shift-card {
    position: relative;
    border-radius: 8px;
    padding: 5px 6px 6px 6px;
    transition: all 0.15s ease;
    cursor: pointer;
    text-align: left;
    height: 100%;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 2.5px;
    box-sizing: border-box;
    width: 100%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.sched-shift-card:hover {
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.sched-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
}

.sched-badge-wrap {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    font-size: 11px;
    line-height: 1;
    max-width: calc(100% - 22px);
    transition: all 0.15s ease;
}

.sched-badge-code {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 19px;
    height: 18px;
    padding: 0 5px;
    border-radius: 4px;
    font-family: var(--font-heading, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
    font-weight: 900;
    font-size: 10.5px;
    line-height: 1;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    flex-shrink: 0;
}

.sched-badge-name {
    font-family: var(--font-heading, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: 0.4px;
    line-height: 1;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sched-card-clear {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.12);
    color: #475569;
    border: none;
    font-size: 12px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.sched-shift-card:hover .sched-card-clear {
    opacity: 1;
}
.sched-card-clear:hover {
    background: #ef4444;
    color: #ffffff;
    transform: scale(1.1);
}

.sched-card-time {
    font-size: 12px;
    font-weight: 800;
    font-family: var(--font-heading, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.25px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 5.5px;
    height: 24px;
    padding: 0 8px;
    border-radius: 6px;
    width: 100%;
    margin: 1px auto;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.sched-card-time i {
    font-size: 12.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    opacity: 1;
    flex-shrink: 0;
}
.sched-card-time span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    position: relative;
    top: 0.5px;
}

/* Detail box inside card (Integrated Hairline Layout matching Clean Image) */
.sched-card-detail-box {
    background: transparent !important;
    border: none !important;
    border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
    border-radius: 0 !important;
    padding: 4px 1px 2px 1px !important;
    display: flex;
    flex-direction: column;
    gap: 2.5px;
    margin-top: 1px;
    box-shadow: none !important;
    box-sizing: border-box;
    width: 100%;
}

.sched-detail-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 3px;
    font-size: 9.5px;
    line-height: 1.3;
    min-width: 0;
    width: 100%;
}

.sched-detail-dot {
    font-size: 7.5px;
    line-height: 1;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
}
.dot-regular { color: #059669; }
.dot-tardiness { color: #b45309; }
.dot-no-inout { color: #dc2626; }
.dot-overtime { color: #7c3aed; }
.dot-planned { color: #2563eb; }

.sched-detail-punch {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    font-size: 9.5px;
    color: #1e293b;
    white-space: nowrap;
    letter-spacing: -0.2px;
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: clip;
}

.sched-detail-badge {
    font-size: 9.5px;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    line-height: 1.25;
    white-space: nowrap;
    flex-shrink: 0;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff !important;
    border: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
}
.badge-regular { background: #059669 !important; color: #ffffff !important; }
.badge-tardiness { background: #b45309 !important; color: #ffffff !important; }
.badge-no-inout { background: #dc2626 !important; color: #ffffff !important; }
.badge-overtime { background: #7c3aed !important; color: #ffffff !important; }
.badge-planned { background: #2563eb !important; color: #ffffff !important; }
.badge-off-duty { background: #64748b !important; color: #ffffff !important; }

.sched-detail-hours {
    color: #475569;
    font-size: 10px;
    font-weight: 700;
    padding-top: 2px;
    padding-bottom: 1px;
    line-height: 1.25;
}
.sched-detail-hours strong {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-variant-numeric: tabular-nums;
    font-weight: 800;
    font-size: 10.5px;
}

/* Theme Variations - Clean Seamless Integrated Look */
/* OPENING - GREEN (Per User Directive) */
.sched-theme-o { border: 1.5px solid #86efac; background: #f0fdf4; }
.sched-theme-o .sched-badge-code { background: #059669; color: #ffffff; box-shadow: 0 1px 2px rgba(5, 150, 105, 0.25); }
.sched-theme-o .sched-badge-name { color: #065f46; font-size: 11px; font-weight: 800; }
.sched-theme-o .sched-card-time { background: #ffffff; border: 1px solid #86efac; color: #064e3b; box-shadow: 0 1px 2px rgba(5, 150, 105, 0.08); }
.sched-theme-o .sched-card-time i { color: #059669; }
.sched-theme-o .sched-card-detail-box { border-top: 1px solid #bbf7d0 !important; }
.sched-theme-o .sched-detail-punch { color: #065f46; }
.sched-theme-o .sched-detail-hours { color: #047857; }
.sched-theme-o .sched-detail-hours strong { color: #065f46; }

/* MID DAY - BLUE */
.sched-theme-md { border: 1.5px solid #93c5fd; background: #f0f7ff; }
.sched-theme-md .sched-badge-code { background: #2563eb; color: #ffffff; box-shadow: 0 1px 2px rgba(37, 99, 235, 0.25); }
.sched-theme-md .sched-badge-name { color: #1e40af; font-size: 11px; font-weight: 800; }
.sched-theme-md .sched-card-time { background: #ffffff; border: 1px solid #93c5fd; color: #1e3a8a; box-shadow: 0 1px 2px rgba(37, 99, 235, 0.08); }
.sched-theme-md .sched-card-time i { color: #2563eb; }
.sched-theme-md .sched-card-detail-box { border-top: 1px solid #bfdbfe !important; }
.sched-theme-md .sched-detail-punch { color: #1e40af; }
.sched-theme-md .sched-detail-hours { color: #1d4ed8; }
.sched-theme-md .sched-detail-hours strong { color: #1e40af; }

/* LATE DAY - AMBER */
.sched-theme-ld { border: 1.5px solid #fcd34d; background: #fffdf5; }
.sched-theme-ld .sched-badge-code { background: #d97706; color: #ffffff; box-shadow: 0 1px 2px rgba(217, 119, 6, 0.25); }
.sched-theme-ld .sched-badge-name { color: #92400e; font-size: 11px; font-weight: 800; }
.sched-theme-ld .sched-card-time { background: #ffffff; border: 1px solid #fcd34d; color: #78350f; box-shadow: 0 1px 2px rgba(217, 119, 6, 0.08); }
.sched-theme-ld .sched-card-time i { color: #d97706; }
.sched-theme-ld .sched-card-detail-box { border-top: 1px solid #fde68a !important; }
.sched-theme-ld .sched-detail-punch { color: #92400e; }
.sched-theme-ld .sched-detail-hours { color: #b45309; }
.sched-theme-ld .sched-detail-hours strong { color: #92400e; }

/* CLOSING - PURPLE */
.sched-theme-c { border: 1.5px solid #d8b4fe; background: #faf5ff; }
.sched-theme-c .sched-badge-code { background: #9333ea; color: #ffffff; box-shadow: 0 1px 2px rgba(147, 51, 234, 0.25); }
.sched-theme-c .sched-badge-name { color: #6b21a8; font-size: 11px; font-weight: 800; }
.sched-theme-c .sched-card-time { background: #ffffff; border: 1px solid #d8b4fe; color: #581c87; box-shadow: 0 1px 2px rgba(147, 51, 234, 0.08); }
.sched-theme-c .sched-card-time i { color: #9333ea; }
.sched-theme-c .sched-card-detail-box { border-top: 1px solid #e9d5ff !important; }
.sched-theme-c .sched-detail-punch { color: #6b21a8; }
.sched-theme-c .sched-detail-hours { color: #7e22ce; }
.sched-theme-c .sched-detail-hours strong { color: #6b21a8; }

/* RESTDAY - SLATE */
.sched-card-rest { border: 1.5px solid #cbd5e1; background: #f8fafc; }
.sched-card-rest .sched-badge-code { background: #64748b; color: #ffffff; }
.sched-card-rest .sched-badge-name { color: #334155; font-size: 11px; font-weight: 800; }
.sched-card-rest .sched-card-time { background: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; box-shadow: 0 1px 2px rgba(100, 116, 139, 0.08); }
.sched-card-rest .sched-card-time i { color: #64748b; }
.sched-card-rest .sched-card-detail-box { border-top: 1px solid #e2e8f0 !important; }
.sched-card-rest .sched-detail-punch { color: #475569; }
.sched-card-rest .sched-detail-hours { color: #475569; }
.sched-card-rest .sched-detail-hours strong { color: #1e293b; }

/* CUSTOM - VIOLET */
.sched-card-custom { border: 1.5px solid #c4b5fd; background: #f5f3ff; }
.sched-card-custom .sched-badge-code { background: #7c3aed; color: #ffffff; }
.sched-card-custom .sched-badge-name { color: #5b21b6; font-size: 11px; font-weight: 800; }
.sched-card-custom .sched-card-time { background: #ffffff; border: 1px solid #c4b5fd; color: #4c1d95; box-shadow: 0 1px 2px rgba(124, 58, 237, 0.08); }
.sched-card-custom .sched-card-time i { color: #7c3aed; }
.sched-card-custom .sched-card-detail-box { border-top: 1px solid #ddd6fe !important; }
.sched-card-custom .sched-detail-punch { color: #5b21b6; }
.sched-card-custom .sched-detail-hours { color: #6d28d9; }
.sched-card-custom .sched-detail-hours strong { color: #5b21b6; }

/* Custom badge code fallback */
.sched-badge-code[style*="background"] {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}

/* Top Controller Toolbar Layout */
.sched-top-nav-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto;
    flex-wrap: nowrap;
}

.sched-week-nav-pill {
    height: 38px;
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    gap: 4px;
    box-sizing: border-box;
}

.sched-nav-btn-icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.sched-nav-btn-icon:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.sched-week-date-trigger {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6.5px;
    margin: 0 1px;
    height: 28px;
    padding: 0 9px;
    border-radius: 6px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.sched-week-date-trigger:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.sched-week-date-trigger:focus-within {
    background: #ffffff;
    border-color: #7c3aed;
    box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.12);
}

.sched-week-cal-icon {
    color: #7c3aed;
    font-size: 14.5px;
    line-height: 1;
    flex-shrink: 0;
}

.sched-week-label-range {
    font-size: 12.5px;
    font-weight: 800;
    font-family: var(--font-heading, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.2px;
    color: #0f172a;
    white-space: nowrap;
    line-height: 1;
    user-select: none;
}

.sched-week-dropdown-arrow {
    color: #94a3b8;
    font-size: 11px;
    line-height: 1;
    margin-left: -1px;
    transition: transform 0.15s ease;
    flex-shrink: 0;
}
.sched-week-date-trigger:hover .sched-week-dropdown-arrow {
    color: #64748b;
}

.sched-invisible-date-input {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    opacity: 0 !important;
    cursor: pointer !important;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    z-index: 2;
}

.sched-nav-btn-current {
    margin-left: 2px;
    font-size: 11px;
    font-weight: 800;
    padding: 3.5px 8px;
    border-radius: 6px;
    background: #ede9fe;
    color: #7c3aed;
    text-decoration: none;
    transition: all 0.15s ease;
    line-height: 1;
    white-space: nowrap;
    flex-shrink: 0;
}
.sched-nav-btn-current:hover {
    background: #ddd6fe;
}

.sched-top-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sched-top-btn {
    height: 38px;
    padding: 0 13px;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    text-decoration: none;
}

.sched-top-btn-secondary {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #334155;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.sched-top-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.06);
}

.sched-top-btn-save {
    background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
    color: #ffffff;
    border: none;
    font-weight: 800;
    padding: 0 18px;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.35);
}
.sched-top-btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.45);
    filter: brightness(1.05);
}

/* Draft Mode & Batch Fill Styles */
.sched-shift-card.is-draft {
    border: 2px dashed #f59e0b !important;
    position: relative;
    background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(245, 158, 11, 0.04) 10px, rgba(245, 158, 11, 0.04) 20px) !important;
}
.sched-draft-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    font-size: 9px;
    font-weight: 800;
    padding: 1px 5px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    line-height: 1.2;
}
.sched-top-btn-save.has-drafts {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    border-color: #047857 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3), 0 4px 14px rgba(5, 150, 105, 0.35) !important;
    animation: schedSavePulse 2s infinite ease-in-out;
}
@keyframes schedSavePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}
.sched-scope-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    transition: all 0.15s ease;
}
.sched-scope-btn:hover {
    border-color: #cbd5e1;
    color: #1e293b;
}
.sched-scope-btn.active {
    background: #f5f3ff;
    border-color: #8b5cf6;
    color: #6d28d9;
    box-shadow: 0 1px 3px rgba(139, 92, 246, 0.15);
}
.batch-dept-pill {
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
}
.batch-dept-pill:hover {
    background: #f1f5f9;
}
.batch-dept-pill.active {
    background: #7c3aed;
    color: #ffffff;
    border-color: #7c3aed;
}

/* SweetAlert2 Popup must always appear in front of Schedule Manager Window and Overlays */
.swal2-container {
    z-index: 9999999 !important;
}

/* Floating / Centered Cell Custom Dropdown (Consistent 395px x 650px across all tabs) */
.sched-custom-dropdown {
    position: fixed !important;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 16px;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.25), 0 8px 18px -4px rgba(15, 23, 42, 0.08);
    width: 395px;
    max-width: min(410px, 95vw);
    height: min(650px, 95vh);
    max-height: 95vh;
    z-index: 99999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: schedPopoverFlyout 0.16s cubic-bezier(0.16, 1, 0.3, 1);
}

.sched-custom-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.25);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 99990;
    display: none;
}

@keyframes schedPopoverFlyout {
    from {
        opacity: 0;
        transform: scale(0.96);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.sched-custom-dd-header {
    padding: 10px 14px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

/* Reduced, Compact Segmented Tabs */
.sched-mgr-tabs {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    padding: 3px 6px;
    gap: 3px;
    border-radius: 10px;
    margin: 8px 12px 0 12px;
    flex-shrink: 0;
}

.sched-mgr-tab-btn {
    flex: 1;
    padding: 6px 4px;
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    border: none;
    background: transparent;
    border-radius: 7px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.sched-mgr-tab-btn:hover {
    color: #7c3aed;
    background: rgba(255, 255, 255, 0.5);
}
.sched-mgr-tab-btn.active {
    color: #7c3aed;
    background: #ffffff;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.sched-mgr-tab-panel {
    display: none;
    padding: 10px 14px 12px 14px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    box-sizing: border-box;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}
.sched-mgr-tab-panel::-webkit-scrollbar {
    width: 5px;
}
.sched-mgr-tab-panel::-webkit-scrollbar-track {
    background: #f8fafc;
}
.sched-mgr-tab-panel::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.sched-mgr-tab-panel.active {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
#tabPanel_shift_custom {
    overflow-y: hidden !important;
}

.sched-settings-sub-panel {
    display: none;
    flex: 1;
    min-height: 0;
    flex-direction: column;
}

/* Owner Card in Fill Grid */
.sched-owner-card {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 1.5px solid #ddd6fe;
    border-radius: 10px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sched-owner-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #7c3aed;
    color: #ffffff;
    font-weight: 800;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.sched-owner-badge {
    font-size: 9.5px;
    font-weight: 800;
    color: #7c3aed;
    background: #ffffff;
    border: 1px solid #c4b5fd;
    padding: 2px 6px;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    flex-shrink: 0;
}

.sched-dd-icon-box {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.sched-btn-close-sm {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.sched-btn-close-sm:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
}

.sched-custom-dd-section {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
}

.sched-custom-dd-label {
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.sched-dd-shifts-scroll {
    max-height: 240px;
    flex: 1;
    min-height: 140px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding-right: 3px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
}
.sched-dd-shifts-scroll::-webkit-scrollbar {
    width: 5px;
}
.sched-dd-shifts-scroll::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 4px;
}
.sched-dd-shifts-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.sched-dd-shifts-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.sched-dd-shift-item {
    width: 100%;
    text-align: left;
    padding: 7.5px 10px;
    border: 1px solid #f1f5f9;
    background: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 8px;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.sched-dd-shift-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
}

.sched-shift-item-actions {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: 2px;
}
.sched-shift-row-btn {
    width: 22px;
    height: 22px;
    border-radius: 5px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 11.5px;
    color: #64748b;
    padding: 0;
    transition: all 0.15s ease;
}
.sched-shift-row-btn:hover {
    transform: translateY(-1px);
}
.sched-shift-btn-edit:hover {
    color: #7c3aed;
    background: #ede9fe;
    border-color: #c4b5fd;
}
.sched-shift-btn-delete:hover {
    color: #ef4444;
    background: #fee2e2;
    border-color: #fca5a5;
}

#editShiftTemplateModal {
    z-index: 100050 !important;
    justify-content: center !important;
    align-items: center !important;
}
#editShiftTemplateModal .hr-modal {
    border-radius: 16px !important;
    max-height: 90vh;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
}

.sched-custom-time-card {
    padding: 7px 10px;
    background: #fdf4ff;
    border: 1.5px dashed #e879f9;
    border-radius: 9px;
    margin: 2px 0 0 0;
}

.sched-custom-time-header {
    font-size: 9.5px;
    font-weight: 800;
    color: #9333ea;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.sched-time-inputs-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 5px;
}

.sched-time-input-wrap {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.sched-time-field-tag {
    font-size: 8.5px;
    font-weight: 800;
    color: #a855f7;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.sched-input-time {
    width: 100%;
    height: 36px;
    border: 1.5px solid #e9d5ff;
    border-radius: 8px;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 700;
    color: #3b0764;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.sched-input-time:focus {
    border-color: #9333ea;
    box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.15);
}

/* Compact input sizing inside Custom Time Range card */
.sched-custom-time-card .sched-input-time {
    height: 28px;
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 11px;
}

.sched-time-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #c084fc;
    font-size: 14px;
    padding-top: 14px;
    flex-shrink: 0;
}
.sched-custom-time-card .sched-time-arrow {
    font-size: 11px;
    padding-top: 10px;
}

.sched-input-text-sm {
    width: 100%;
    height: 36px;
    border: 1.5px solid #e9d5ff;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
    color: #3b0764;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.sched-input-text-sm:focus {
    border-color: #9333ea;
    box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.15);
}

.sched-custom-time-card .sched-input-text-sm {
    height: 28px;
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 11px;
}

.sched-btn-apply-custom {
    width: 100%;
    height: 30px;
    border-radius: 6px;
    background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 11.5px;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.22);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.15s ease;
}
.sched-btn-apply-custom:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.32);
    filter: brightness(1.05);
}

.sched-custom-dd-footer {
    padding: 10px 14px;
    background: #f8fafc;
    border-top: 1.5px solid #f1f5f9;
    text-align: center;
    border-radius: 0 0 16px 16px;
}

.sched-btn-edit-times {
    border: 1.5px solid #c7d2fe;
    background: #eef2ff;
    color: #4f46e5;
    font-size: 10.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
}
.sched-btn-edit-times:hover {
    background: #e0e7ff;
    border-color: #a5b4fc;
    transform: translateY(-1px);
}

/* Master Settings Modal */
.sched-cat-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
}
.sched-cat-tab-btn {
    flex: 1;
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
    background: transparent;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.12s;
}
.sched-cat-tab-btn.active {
    background: #ffffff;
    color: #7c3aed;
    font-weight: 800;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.sched-shift-edit-rows {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.sched-edit-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
}
.sched-edit-row-badge {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: #ffffff;
    font-weight: 900;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Toast */
.sched-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
    font-weight: 600;
    z-index: 9999;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

/* Row Action Lightning Menu */
.sched-row-btn {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.sched-row-btn:hover { background: #fef3c7; border-color: #f59e0b; color: #b45309; }

.sched-row-dropdown {
    position: fixed !important;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.22), 0 6px 14px -2px rgba(15, 23, 42, 0.08);
    min-width: 230px;
    max-width: 280px;
    z-index: 999999;
    display: none;
    padding: 6px 0;
    animation: schedPopoverFlyout 0.15s cubic-bezier(0.16, 1, 0.3, 1);
}
.sched-row-dropdown.open { display: block !important; }
.sched-row-dd-item {
    width: 100%;
    text-align: left;
    padding: 6px 12px;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sched-row-dd-item:hover { background: #f8fafc; color: #7c3aed; }
.sched-row-dd-item.text-danger:hover { background: #fef2f2; color: #ef4444; }

/* Nav Buttons */
.sched-nav-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    text-decoration: none;
}
.sched-nav-btn:hover { background: #f1f5f9; color: #0f172a; }

.sched-preset-btn {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
}
.sched-preset-btn:hover { background: #0f172a; color: #ffffff; }

.sched-btn-close-sm {
    border: none;
    background: #f1f5f9;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    font-size: 13px;
}
.sched-btn-close-sm:hover { background: #e2e8f0; color: #0f172a; }

/* Modal Day Pills */
.sched-day-pills { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
.sched-day-pill { cursor: pointer; margin: 0; }
.sched-day-input { display: none; }
.sched-day-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 36px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
    transition: all 0.15s ease;
    user-select: none;
}
.sched-day-input:checked + .sched-day-label {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}
</style>
@endpush

@push('scripts')
<script>
// -------------------------------------------------------------
// Constants and Client Memory
// -------------------------------------------------------------
const CSRF_TOKEN = '{{ csrf_token() }}';
const QUICK_ASSIGN_URL = '{{ route('hr.attendance.schedules.quick-assign') }}';
const COPY_WEEK_URL = '{{ route('hr.attendance.schedules.copy-week') }}';
const QUICK_FILL_ROW_URL = '{{ route('hr.attendance.schedules.quick-fill-row') }}';
const UPDATE_DEPT_URL = '{{ route('hr.attendance.schedules.update-employee-department') }}';
const BATCH_SCHEDULE_URL = '{{ route('hr.attendance.schedules.batch') }}';
let draftSchedules = {}; // Key: "empId_date", Value: { employee_id, date, shift_template_id, is_rest_day, custom_start_time, custom_end_time, notes, is_draft }
let savedOriginalCells = {}; // Key: "empId_date", Value: original innerHTML before draft
let batchSelectedEmpIds = [];
let batchFillScope = 'all'; // 'all' or 'custom'
const ALL_EMPLOYEES = @json(isset($allEmployees) ? $allEmployees : $employees);
const ALL_DEPARTMENTS = @json($departmentsJson);
const DEPT_COLORS = {
    'management': '#8b5cf6',
    'front of house': '#3b82f6',
    'back of house': '#10b981',
    'finance & admin': '#f59e0b'
};
const SHIFT_TEMPLATES = @json($shiftTemplates->keyBy('id'));

// Default shift templates per category (matching Schedule.html)
const DEFAULT_SHIFT_TEMPLATES = {
    'default': {
        'O': { label: 'OPENING', start: '08:00', end: '17:00' },
        'MD': { label: 'MID DAY', start: '10:00', end: '19:00' },
        'LD': { label: 'LATE DAY', start: '12:00', end: '21:00' },
        'C': { label: 'CLOSING', start: '13:00', end: '22:00' },
        'OFF': { label: 'RESTDAY', start: 'OFF', end: 'OFF' }
    }
};

let shiftTemplatesStore = {};
function initShiftTemplatesStore() {
    try {
        const saved = localStorage.getItem('RMS_SHIFT_TEMPLATES');
        if (saved) {
            shiftTemplatesStore = JSON.parse(saved);
        } else {
            shiftTemplatesStore = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES));
        }
    } catch(e) {
        shiftTemplatesStore = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES));
    }
}
initShiftTemplatesStore();

function getShiftConfig(category, code) {
    const catKey = (category || 'default').toLowerCase();
    const config = shiftTemplatesStore[catKey] || shiftTemplatesStore['default'] || DEFAULT_SHIFT_TEMPLATES['default'];
    return config[code] || { label: code, start: '08:00', end: '17:00' };
}

// -------------------------------------------------------------
// Interactive Department Cycler for Employee Pill
// -------------------------------------------------------------
function cycleEmployeeDepartment(empId, direction, btn) {
    if (btn) {
        btn.disabled = true;
        setTimeout(() => { btn.disabled = false; }, 250);
    }
    const pill = document.getElementById(`empDeptPill_${empId}`);
    if (!pill || !ALL_DEPARTMENTS || ALL_DEPARTMENTS.length === 0) return;

    // Check employee's assigned departments
    const empObj = (typeof ALL_EMPLOYEES !== 'undefined' && Array.isArray(ALL_EMPLOYEES)) 
        ? ALL_EMPLOYEES.find(item => item.id == empId) 
        : null;

    let availableDepts = ALL_DEPARTMENTS;
    if (empObj && Array.isArray(empObj.all_department_ids) && empObj.all_department_ids.length > 0) {
        const allowedIds = empObj.all_department_ids.map(Number);
        const filtered = ALL_DEPARTMENTS.filter(d => allowedIds.includes(Number(d.id)));
        if (filtered.length > 0) {
            availableDepts = filtered;
        }
    }
    if (availableDepts.length <= 1) {
        return; // Only 1 or 0 departments assigned, no cycling
    }

    const currentDept = (pill.getAttribute('data-current-dept') || pill.querySelector('.sched-dept-pill-text')?.textContent || '').trim().toLowerCase();
    let currIdx = availableDepts.findIndex(d => d.name.trim().toLowerCase() === currentDept);
    if (currIdx === -1) currIdx = 0;

    let newIdx = (currIdx + direction + availableDepts.length) % availableDepts.length;
    const nextDept = availableDepts[newIdx];

    // 1. Immediately update UI
    pill.setAttribute('data-current-dept', nextDept.name);
    const textSpan = pill.querySelector('.sched-dept-pill-text');
    if (textSpan) textSpan.textContent = nextDept.name;
    pill.setAttribute('title', `Department: ${nextDept.name} (Click < or > to cycle)`);

    const dot = pill.querySelector('.sched-cat-dot');
    if (dot) {
        dot.style.backgroundColor = DEPT_COLORS[nextDept.name.toLowerCase()] || '#7c3aed';
    }

    // 2. Update Row and Cells attributes
    const row = document.getElementById(`schedRow_${empId}`);
    if (row) {
        row.setAttribute('data-emp-cat', nextDept.name.toLowerCase());
        row.querySelectorAll('.sched-cell-td').forEach(c => c.setAttribute('data-emp-cat', nextDept.name.toLowerCase()));
    }

    // 3. Update cached employee in ALL_EMPLOYEES array
    if (typeof ALL_EMPLOYEES !== 'undefined' && Array.isArray(ALL_EMPLOYEES)) {
        const e = ALL_EMPLOYEES.find(item => item.id == empId);
        if (e) {
            if (!e.department) e.department = {};
            e.department.id = nextDept.id;
            e.department.name = nextDept.name;
            e.department_id = nextDept.id;
        }
    }

    // 4. Auto-update list visibility if category filter or search is active!
    filterMatrixByCategory();
    if (typeof filterMatrixRowsBySearch === 'function') {
        filterMatrixRowsBySearch();
    }

    // 5. Toast feedback
    showSchedToast(`Department updated to ${nextDept.name}`);

    // 6. Persist to database via AJAX
    fetch(UPDATE_DEPT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            department_id: nextDept.id,
            department_name: nextDept.name
        })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            console.warn('Server warning updating department:', data);
        }
    })
    .catch(err => {
        console.error('Error updating department:', err);
    });
}

// -------------------------------------------------------------
// Floating Cell Custom Schedule Popover
// -------------------------------------------------------------
let activeCellEmpId = null;
let activeCellDate = null;

function switchScheduleManagerTab(tabKey, btnElement = null) {
    document.querySelectorAll('.sched-mgr-tab-panel').forEach(p => {
        p.classList.remove('active');
        p.style.display = 'none';
    });
    document.querySelectorAll('.sched-mgr-tab-btn').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(`tabPanel_${tabKey}`);
    if (panel) {
        panel.classList.add('active');
        panel.style.display = 'flex';
    }

    let btn = btnElement || document.getElementById(`tabBtn_${tabKey}`);
    if (btn) btn.classList.add('active');
}

function openScheduleManagerTab(tabKey) {
    if (tabKey === 'fill_grid' && !activeCellEmpId) {
        openBatchFillGridModal();
        return;
    }
    if (tabKey === 'shift_settings' || tabKey === 'category_defaults') {
        openShiftMasterModal();
        return;
    }
    const dd = document.getElementById('cellCustomDropdown');
    const backdrop = document.getElementById('cellCustomBackdrop');
    if (!dd) return;

    if (tabKey === 'add_template') {
        tabKey = 'add_shift';
    }

    switchScheduleManagerTab(tabKey);

    // Connect schedule owner if no active cell
    if (!activeCellEmpId) {
        const firstRow = document.querySelector('.sched-row[data-emp-id]');
        if (firstRow) {
            const fEmpId = firstRow.getAttribute('data-emp-id');
            const fName = firstRow.querySelector('.sched-emp-name-text')?.textContent || firstRow.getAttribute('data-emp-name') || 'Employee';
            const fDept = firstRow.getAttribute('data-emp-cat') || 'Department';
            const fBranch = firstRow.getAttribute('data-emp-branch') || 'Branch';
            const fInitials = firstRow.querySelector('.sched-emp-avatar')?.textContent?.trim() || fName.substring(0, 2).toUpperCase();

            const ownerAvatar = document.getElementById('fillGridOwnerAvatar');
            if (ownerAvatar) ownerAvatar.textContent = fInitials;
            const ownerName = document.getElementById('fillGridOwnerName');
            if (ownerName) ownerName.textContent = fName.trim();
            const ownerDept = document.getElementById('fillGridOwnerDept');
            if (ownerDept) ownerDept.innerHTML = `<span style="text-transform: capitalize;">${fDept}</span> &bull; <span style="text-transform: capitalize;">${fBranch}</span>`;
            const submitName = document.getElementById('fillGridSubmitName');
            if (submitName) submitName.textContent = fName.trim().split(' ')[0];
            const empHidden = document.getElementById('schedEmployeeIdModal');
            if (empHidden) empHidden.value = fEmpId;
        }
        const cText = document.getElementById('cellCustomContextText');
        if (cText) cText.textContent = 'Manage shifts, schedules, and templates';
    }

    dd.style.visibility = 'hidden';
    dd.style.display = 'flex';

    const ddHeight = dd.offsetHeight || 520;
    const ddWidth = dd.offsetWidth || 390;
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    const left = Math.max(12, Math.round((viewportWidth - ddWidth) / 2));
    const top = Math.max(12, Math.round((viewportHeight - ddHeight) / 2));

    dd.style.top = `${top}px`;
    dd.style.left = `${left}px`;
    dd.style.visibility = 'visible';

    if (backdrop) backdrop.style.display = 'block';
}

function openCellCustomDropdown(empId, date) {
    const dd = document.getElementById('cellCustomDropdown');
    const backdrop = document.getElementById('cellCustomBackdrop');
    // If clicking the same active cell that is already open, toggle it closed
    if (dd && dd.style.display !== 'none' && activeCellEmpId === empId && activeCellDate === date) {
        closeCellCustomDropdown();
        return;
    }

    activeCellEmpId = empId;
    activeCellDate = date;

    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell || !dd) return;

    // Default to first tab: shift_custom
    switchScheduleManagerTab('shift_custom');

    // Context text with employee name and date
    const row = document.getElementById(`schedRow_${empId}`);
    const empName = row ? (row.querySelector('.sched-emp-name-text')?.textContent || row.getAttribute('data-emp-name') || 'Employee') : 'Employee';
    const empDept = row ? (row.getAttribute('data-emp-cat') || 'Department') : 'Department';
    const empBranch = row ? (row.getAttribute('data-emp-branch') || 'Branch') : 'Branch';
    const initials = row ? (row.querySelector('.sched-emp-avatar')?.textContent?.trim() || empName.substring(0, 2).toUpperCase()) : 'EM';

    const cText = document.getElementById('cellCustomContextText');
    if (cText) {
        cText.innerHTML = `<span style="color:#7c3aed;font-weight:700;"><i class="ph ph-user"></i> ${empName.trim()}</span> &bull; <span>${date}</span>`;
    }

    // Connect schedule owner to Fill Grid tab (No option for All!)
    const ownerAvatar = document.getElementById('fillGridOwnerAvatar');
    if (ownerAvatar) ownerAvatar.textContent = initials;
    const ownerName = document.getElementById('fillGridOwnerName');
    if (ownerName) ownerName.textContent = empName.trim();
    const ownerDept = document.getElementById('fillGridOwnerDept');
    if (ownerDept) ownerDept.innerHTML = `<span style="text-transform: capitalize;">${empDept}</span> &bull; <span style="text-transform: capitalize;">${empBranch}</span>`;
    const submitName = document.getElementById('fillGridSubmitName');
    if (submitName) submitName.textContent = empName.trim().split(' ')[0];

    const empHidden = document.getElementById('schedEmployeeIdModal');
    if (empHidden) empHidden.value = empId;

    const startDateInput = document.getElementById('schedStartDateModal');
    if (startDateInput) {
        startDateInput.value = date;
        const endDateInput = document.getElementById('schedEndDateModal');
        if (endDateInput && (!endDateInput.value || endDateInput.value < date)) {
            endDateInput.value = date;
        }
    }

    // Show invisibly to measure real height and width
    dd.style.visibility = 'hidden';
    dd.style.display = 'flex';

    const rect = cell.getBoundingClientRect();
    const ddHeight = dd.offsetHeight || 520;
    const ddWidth = dd.offsetWidth || 390;
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    // Horizontal side placement: Open to the right of the cell by default, or flip to the left if near screen edge
    const spaceRight = viewportWidth - rect.right;
    const spaceLeft = rect.left;

    let left;
    if (spaceRight >= ddWidth + 10 || spaceRight >= spaceLeft) {
        // Sufficient room on right side of the cell
        left = rect.right + 10;
    } else {
        // Not enough room on the right -> flip to the left side of the cell
        left = rect.left - ddWidth - 10;
    }
    // Prevent clipping past viewport edges
    left = Math.max(12, Math.min(left, viewportWidth - ddWidth - 12));

    // Vertical placement: Align with cell top, clamped so window never overflows top/bottom of screen
    let top = rect.top - 6;
    if (top + ddHeight > viewportHeight - 12) {
        top = Math.max(12, viewportHeight - ddHeight - 12);
    }
    if (top < 12) {
        top = 12;
    }

    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.visibility = 'visible';

    if (backdrop) backdrop.style.display = 'block';
}

function closeCellCustomDropdown() {
    const dd = document.getElementById('cellCustomDropdown');
    const backdrop = document.getElementById('cellCustomBackdrop');
    if (dd) {
        dd.style.display = 'none';
        dd.style.visibility = 'hidden';
    }
    if (backdrop) {
        backdrop.style.display = 'none';
    }
    activeCellEmpId = null;
    activeCellDate = null;
}

function syncDateRangeModal() {
    var start = document.getElementById('schedStartDateModal')?.value;
    var end = document.getElementById('schedEndDateModal')?.value;
    if (start && (!end || end < start)) {
        document.getElementById('schedEndDateModal').value = start;
    }
}

function presetDateRangeModal(type) {
    var now = new Date();
    var formatDate = function(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    var start = new Date();
    var end = new Date();
    if (type === 'today') {
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'next_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'month') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }
    var sInput = document.getElementById('schedStartDateModal');
    var eInput = document.getElementById('schedEndDateModal');
    if (sInput) sInput.value = formatDate(start);
    if (eInput) eInput.value = formatDate(end);
}

function setRestDaysPresetModal(days) {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('rest_day_modal_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}

function clearRestDaysPresetModal() {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('rest_day_modal_' + d);
        if (cb) cb.checked = false;
    });
}

function filterRegisteredShifts(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.sched-dd-shift-item').forEach(btn => {
        const name = (btn.getAttribute('data-shift-name') || '').toLowerCase();
        const code = (btn.getAttribute('data-shift-code') || '').toLowerCase();
        if (!q || name.includes(q) || code.includes(q)) {
            btn.style.display = 'flex';
        } else {
            btn.style.display = 'none';
        }
    });
}

function updateShiftPreviewModal() {
    const start = document.getElementById('newShiftStartModal')?.value || '06:00';
    const end = document.getElementById('newShiftEndModal')?.value || '15:00';
    const code = start.replace(':', '');
    const to12h = (t) => {
        let [h, m] = t.split(':').map(Number);
        let ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return m === 0 ? `${h}${ampm}` : `${h}:${m < 10 ? '0' : ''}${m}${ampm}`;
    };
    const prev = document.getElementById('shiftFormatPreviewModal');
    if (prev) {
        prev.textContent = `${code} = ${to12h(start)} - ${to12h(end)}`;
    }
}

function openEditShiftTemplateModal(id, code, start, end, isOvernight, breakMins, color) {
    const modal = document.getElementById('editShiftTemplateModal');
    if (!modal) return;

    document.getElementById('editShiftId').value = id;
    document.getElementById('editShiftStartModal').value = start;
    document.getElementById('editShiftEndModal').value = end;
    document.getElementById('editIsOvernightModal').checked = !!isOvernight;
    document.getElementById('editShiftBreakModal').value = breakMins || 60;
    document.getElementById('editShiftColorModal').value = color || '#7c3aed';
    
    const subTitle = document.getElementById('editShiftModalSubtitle');
    if (subTitle) {
        subTitle.textContent = `Editing shift template code: ${code}`;
    }

    const form = document.getElementById('editShiftTemplateForm');
    if (form) {
        form.action = `/hr/attendance/shifts/${id}`;
    }

    updateEditShiftPreviewModal();
    openModal('editShiftTemplateModal');
}

function updateEditShiftPreviewModal() {
    const start = document.getElementById('editShiftStartModal')?.value || '06:00';
    const end = document.getElementById('editShiftEndModal')?.value || '15:00';
    const code = start.replace(':', '');
    const to12h = (t) => {
        let [h, m] = t.split(':').map(Number);
        let ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return m === 0 ? `${h}${ampm}` : `${h}:${m < 10 ? '0' : ''}${m}${ampm}`;
    };
    const prev = document.getElementById('editShiftFormatPreviewModal');
    if (prev) {
        prev.textContent = `${code} = ${to12h(start)} - ${to12h(end)}`;
    }
}

function deleteShiftTemplate(id, name) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Shift Template?',
            html: `Are you sure you want to delete shift template <strong>"${escapeHtml(name)}"</strong>?<br><br><span style="font-size: 12.5px; color: #64748b;">Existing schedules will keep their times, but this template preset will be removed.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ph ph-trash"></i> Yes, Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteShiftTemplateForm');
                if (form) {
                    form.action = `/hr/attendance/shifts/${id}`;
                    form.submit();
                }
            }
        });
    } else {
        if (!confirm(`Are you sure you want to delete shift template "${name}"?\n\nExisting schedules will keep their times, but this template preset will be removed.`)) {
            return;
        }
        const form = document.getElementById('deleteShiftTemplateForm');
        if (form) {
            form.action = `/hr/attendance/shifts/${id}`;
            form.submit();
        }
    }
}

function deleteShiftTemplateFromModal() {
    const id = document.getElementById('editShiftId')?.value;
    const name = document.getElementById('editShiftFormatPreviewModal')?.textContent || 'this shift';
    if (!id) return;
    closeModal('editShiftTemplateModal');
    deleteShiftTemplate(id, name);
}

document.addEventListener('click', function(e) {
    const dd = document.getElementById('cellCustomDropdown');
    if (dd && dd.style.display !== 'none') {
        if (!e.target.closest('#cellCustomDropdown') && !e.target.closest('.sched-pill-custom') && !e.target.closest('.sched-shift-card') && !e.target.closest('.sched-top-actions') && !e.target.closest('#editShiftTemplateModal')) {
            closeCellCustomDropdown();
        }
    }
});

window.addEventListener('resize', function() {
    closeCellCustomDropdown();
});

document.addEventListener('scroll', function(e) {
    const dd = document.getElementById('cellCustomDropdown');
    if (dd && dd.style.display !== 'none' && !e.target.closest('#cellCustomDropdown')) {
        closeCellCustomDropdown();
    }
}, true);

function applyCustomTimeToActiveCell() {
    if (!activeCellEmpId || !activeCellDate) return;
    const start = document.getElementById('customCellStart').value || '08:00';
    const end = document.getElementById('customCellEnd').value || '17:00';
    const label = document.getElementById('customCellLabel').value.trim() || 'CUSTOM';

    const empId = activeCellEmpId;
    const date = activeCellDate;
    closeCellCustomDropdown();

    const cell = document.getElementById(`cell_${empId}_${date}`);
    const prevHTML = cell.innerHTML;

    // Optimistic UI
    renderAssignedCardHTML(cell, empId, date, 'CUSTOM', label, `${start} - ${end}`, 'sched-card-custom');

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            custom_start_time: start,
            custom_end_time: end,
            notes: label,
            is_rest_day: false
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast(`Applied ${label} (${start} - ${end})`);
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message || 'Failed to apply custom shift', false);
        }
    })
    .catch(err => {
        cell.innerHTML = prevHTML;
        showSchedToast('Error saving custom shift', false);
    });
}

function applyTemplateToActiveCell(templateId, code, name, start, end, color) {
    if (!activeCellEmpId || !activeCellDate) return;
    const empId = activeCellEmpId;
    const date = activeCellDate;
    closeCellCustomDropdown();

    const cell = document.getElementById(`cell_${empId}_${date}`);
    const prevHTML = cell.innerHTML;

    renderAssignedCardHTML(cell, empId, date, code || 'TMPL', name, `${start} - ${end}`, 'sched-card-custom', color);

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            shift_template_id: templateId,
            is_rest_day: false
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast(`Assigned ${code || name} on ${date}`);
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message, false);
        }
    })
    .catch(() => {
        cell.innerHTML = prevHTML;
        showSchedToast('Network error saving shift', false);
    });
}

// -------------------------------------------------------------
// Direct 1-Click Preset Plotting (O, MD, LD, C, OFF)
// -------------------------------------------------------------
function directPlotPreset(empId, date, code) {
    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell) return;

    const prevHTML = cell.innerHTML;
    const empCat = cell.getAttribute('data-emp-cat') || 'default';
    const cfg = getShiftConfig(empCat, code);

    const isOff = code === 'OFF';
    const sLabel = isOff ? 'RESTDAY' : (cfg.label || code);
    const sTime = isOff ? 'OFF DUTY' : `${cfg.start} - ${cfg.end}`;
    const themeClass = isOff ? 'sched-card-rest' : `sched-theme-${code.toLowerCase()}`;

    // Render Optimistic Card
    renderAssignedCardHTML(cell, empId, date, code, sLabel, sTime, themeClass);

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            shift_code: code,
            custom_start_time: isOff ? null : cfg.start,
            custom_end_time: isOff ? null : cfg.end,
            notes: sLabel,
            is_rest_day: isOff
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast(`Assigned ${sLabel} (${sTime})`);
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message || 'Failed to plot shift', false);
        }
    })
    .catch(err => {
        cell.innerHTML = prevHTML;
        showSchedToast('Network error while plotting shift', false);
    });
}

function clearCellShift(empId, date) {
    const draftKey = `${empId}_${date}`;
    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell) return;

    if (typeof draftSchedules !== 'undefined' && draftSchedules[draftKey]) {
        delete draftSchedules[draftKey];
        if (typeof savedOriginalCells !== 'undefined' && savedOriginalCells[draftKey]) {
            cell.innerHTML = savedOriginalCells[draftKey];
            delete savedOriginalCells[draftKey];
        } else {
            renderEmptyCellHTML(cell, empId, date);
        }
        updateSaveButtonState();
        showSchedToast('Draft shift removed');
        return;
    }

    const prevHTML = cell.innerHTML;
    renderEmptyCellHTML(cell, empId, date);

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            clear: true
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast('Shift cleared successfully');
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message, false);
        }
    })
    .catch(() => {
        cell.innerHTML = prevHTML;
        showSchedToast('Network error clearing shift', false);
    });
}

// -------------------------------------------------------------
// Card & Empty Cell HTML Renderers
// -------------------------------------------------------------
function classifyShift(rawCode, rawLabel, timeStr) {
    const code = (rawCode || '').trim().toUpperCase();
    const label = (rawLabel || '').trim();
    const upperLabel = label.toUpperCase();

    // Extract start time "HH:MM"
    let startTime = '';
    if (timeStr && timeStr.includes('-')) {
        startTime = timeStr.split('-')[0].trim().substring(0, 5);
    } else if (timeStr && timeStr.includes(':')) {
        startTime = timeStr.trim().substring(0, 5);
    }

    if (code === 'OFF' || upperLabel === 'RESTDAY' || upperLabel.includes('REST') || upperLabel.includes('OFF')) {
        return { code: 'OFF', label: 'RESTDAY', theme: 'sched-card-rest', isCustom: false };
    }
    if (code === 'O' || code.includes('OPEN') || upperLabel.includes('OPEN') || (startTime && startTime <= '10:30') || ['0600', '0700', '0800', '0900', '1000'].includes(code)) {
        return { code: 'O', label: 'OPENING', theme: 'sched-theme-o', isCustom: false };
    }
    if (code === 'MD' || code.includes('MID') || upperLabel.includes('MID') || (startTime && startTime > '10:30' && startTime <= '13:30') || ['1100', '1200', '1300', '1400'].includes(code)) {
        return { code: 'MD', label: 'MID DAY', theme: 'sched-theme-md', isCustom: false };
    }
    if (code === 'LD' || code.includes('LATE') || upperLabel.includes('LATE') || (startTime && startTime > '13:30' && startTime <= '16:30') || ['1500', '1600', '1700'].includes(code)) {
        return { code: 'LD', label: 'LATE DAY', theme: 'sched-theme-ld', isCustom: false };
    }
    if (code === 'C' || code.includes('CLOS') || upperLabel.includes('CLOS') || (startTime && startTime > '16:30') || ['1800', '1900', '2000', '2100', '2200', '2300'].includes(code)) {
        return { code: 'C', label: 'CLOSING', theme: 'sched-theme-c', isCustom: false };
    }
    return { code: code || 'CUSTOM', label: label || 'CUSTOM', theme: 'sched-card-custom', isCustom: true };
}

function renderAssignedCardHTML(cell, empId, date, rawCode, rawLabel, time, themeClass = null, customColor = null) {
    const classified = classifyShift(rawCode, rawLabel, time);
    const code = classified.code;
    const label = classified.label;
    const finalTheme = classified.theme;
    const displayTime = (classified.code === 'OFF') ? 'OFF DUTY' : time;

    let codeStyle = '';
    let nameStyle = '';
    if (classified.isCustom && customColor) {
        codeStyle = `style="background: ${customColor}; color: #ffffff;"`;
        nameStyle = `style="color: ${customColor};"`;
    }

    let detailBoxHTML = '';
    if (classified.code === 'OFF') {
        detailBoxHTML = `
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
        `;
    } else {
        detailBoxHTML = `
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
        `;
    }

    cell.innerHTML = `
        <div class="sched-shift-card ${finalTheme}" onclick="openCellCustomDropdown(${empId}, '${date}')">
            <div class="sched-card-top">
                <div class="sched-badge-wrap">
                    <span class="sched-badge-code" ${codeStyle}>${escapeHtml(code)}</span>
                    <span class="sched-badge-name" ${nameStyle}>${escapeHtml(label)}</span>
                </div>
                <button type="button" class="sched-card-clear" onclick="event.stopPropagation(); clearCellShift(${empId}, '${date}')" title="Clear Shift">
                    &times;
                </button>
            </div>
            <div class="sched-card-time">
                <i class="ph ph-clock"></i>
                <span>${escapeHtml(displayTime)}</span>
            </div>
            ${detailBoxHTML}
        </div>
    `;
}

function renderEmptyCellHTML(cell, empId, date) {
    cell.innerHTML = `
        <div class="sched-empty-plotter">
            <div class="sched-preset-grid">
                <div class="sched-btn-row">
                    <button type="button" class="sched-mini-pill sched-pill-o" onclick="directPlotPreset(${empId}, '${date}', 'O')" title="OPENING Shift">O</button>
                    <button type="button" class="sched-mini-pill sched-pill-md" onclick="directPlotPreset(${empId}, '${date}', 'MD')" title="MID DAY Shift">MD</button>
                    <button type="button" class="sched-mini-pill sched-pill-ld" onclick="directPlotPreset(${empId}, '${date}', 'LD')" title="LATE DAY Shift">LD</button>
                </div>
                <div class="sched-btn-row">
                    <button type="button" class="sched-mini-pill sched-pill-c" onclick="directPlotPreset(${empId}, '${date}', 'C')" title="CLOSING Shift">C</button>
                    <button type="button" class="sched-mini-pill sched-pill-restday" onclick="directPlotPreset(${empId}, '${date}', 'OFF')" title="RESTDAY (Off Duty)">RESTDAY</button>
                </div>
                <div class="sched-btn-row">
                    <button type="button" class="sched-mini-pill sched-pill-custom" onclick="openCellCustomDropdown(${empId}, '${date}')" title="Select other shift or enter custom time">
                        Custom <i class="ph ph-caret-down" style="font-size: 8px;"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// -------------------------------------------------------------
// Shift Master Settings Modal Logic
// -------------------------------------------------------------
let activeModalCat = 'default';

function openShiftMasterModal(preferredDept = null) {
    let targetTab = null;
    const tabs = document.querySelectorAll('#shiftModalCatTabs .sched-cat-tab-btn');
    if (preferredDept) {
        for (const tab of tabs) {
            if (tab.textContent.trim().toLowerCase() === preferredDept.trim().toLowerCase()) {
                targetTab = tab;
                break;
            }
        }
    }
    if (!targetTab && tabs.length > 0) {
        targetTab = tabs[0];
    }
    if (targetTab) {
        tabs.forEach(b => b.classList.remove('active'));
        targetTab.classList.add('active');
        activeModalCat = targetTab.textContent.trim().toLowerCase();
        document.getElementById('modalActiveCategoryLabel').textContent = targetTab.textContent.trim();
    }
    loadShiftCategoryIntoModal(activeModalCat);
    openModal('shiftMasterSettingsModal');
}

function switchShiftModalCategory(catName, btn) {
    document.querySelectorAll('.sched-cat-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeModalCat = catName.toLowerCase();
    document.getElementById('modalActiveCategoryLabel').textContent = catName;
    loadShiftCategoryIntoModal(activeModalCat);
}

function loadShiftCategoryIntoModal(catKey) {
    const config = shiftTemplatesStore[catKey] || shiftTemplatesStore['default'] || DEFAULT_SHIFT_TEMPLATES['default'];
    ['O', 'MD', 'LD', 'C'].forEach(code => {
        const item = config[code] || DEFAULT_SHIFT_TEMPLATES['default'][code];
        if (item) {
            const startEl = document.getElementById(`shiftTime_${code}_start`);
            const endEl = document.getElementById(`shiftTime_${code}_end`);
            const lblEl = document.getElementById(`lblShift_${code}`);
            if (startEl) startEl.value = item.start;
            if (endEl) endEl.value = item.end;
            if (lblEl) lblEl.textContent = item.label;
        }
    });
}

function saveShiftMasterSettings() {
    if (!shiftTemplatesStore[activeModalCat]) {
        shiftTemplatesStore[activeModalCat] = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES['default']));
    }

    ['O', 'MD', 'LD', 'C'].forEach(code => {
        const startVal = document.getElementById(`shiftTime_${code}_start`)?.value || '08:00';
        const endVal = document.getElementById(`shiftTime_${code}_end`)?.value || '17:00';
        shiftTemplatesStore[activeModalCat][code] = {
            label: code === 'O' ? 'OPENING' : (code === 'MD' ? 'MID DAY' : (code === 'LD' ? 'LATE DAY' : 'CLOSING')),
            start: startVal,
            end: endVal
        };
    });

    try {
        localStorage.setItem('RMS_SHIFT_TEMPLATES', JSON.stringify(shiftTemplatesStore));
    } catch(e) {
        console.error(e);
    }

    closeModal('shiftMasterSettingsModal');
    showSchedToast('Shift Master Settings saved successfully!');
}

function resetShiftCategoryDefaults() {
    shiftTemplatesStore[activeModalCat] = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES['default']));
    loadShiftCategoryIntoModal(activeModalCat);
    showSchedToast(`Reset ${activeModalCat} to default shift times`);
}

// -------------------------------------------------------------
// Multi-Filter "Add Employee Row" Referenced Filtering Method
// -------------------------------------------------------------
let selectedEmpIdsForAddition = [];

function toggleEmpDropdown() {
    const p = document.getElementById('empDropdownPanel');
    const chevron = document.getElementById('empDropdownChevron');
    if (!p) return;
    if (p.style.display === 'none') {
        p.style.display = 'flex';
        chevron.style.transform = 'rotate(180deg)';
        renderDropdownEmployeeList();
    } else {
        closeEmpDropdown();
    }
}

function closeEmpDropdown() {
    const p = document.getElementById('empDropdownPanel');
    const chevron = document.getElementById('empDropdownChevron');
    if (p) p.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('addEmpDropdownWrap');
    if (wrap && !wrap.contains(e.target)) {
        closeEmpDropdown();
    }
});

function isEmpAlreadyInGrid(empId) {
    return document.getElementById(`schedRow_${empId}`) !== null;
}

function renderDropdownEmployeeList() {
    const listEl = document.getElementById('dropdownEmpList');
    if (!listEl) return;

    const q = (document.getElementById('modalEmpSearch')?.value || '').toLowerCase().trim();
    const statusVal = (document.getElementById('modalStatusFilter')?.value || '').toLowerCase().trim();
    const catVal = (document.getElementById('modalCategoryFilter')?.value || '').toLowerCase().trim();

    const filtered = ALL_EMPLOYEES.filter(emp => {
        const name = (emp.full_name || `${emp.first_name} ${emp.last_name}`).toLowerCase();
        const code = (emp.employee_id || '').toLowerCase();
        const branch = (emp.branch?.name || '').toLowerCase();
        const pos = (emp.position?.name || '').toLowerCase();
        const dept = (emp.department?.name || '').toLowerCase();
        const status = (emp.employment_status || '').toLowerCase();

        const matchQ = !q || name.includes(q) || code.includes(q) || branch.includes(q) || pos.includes(q) || dept.includes(q);
        const matchStatus = !statusVal || status === statusVal;
        const matchCat = !catVal || dept.includes(catVal) || branch.includes(catVal);

        return matchQ && matchStatus && matchCat;
    });

    document.getElementById('dropdownEmpMatchText').textContent = `${filtered.length} employee(s) matching filter`;

    if (filtered.length === 0) {
        listEl.innerHTML = `
            <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 12px;">
                <i class="ph ph-magnifying-glass" style="font-size: 22px; display: block; margin-bottom: 4px;"></i>
                No employees matching filter criteria
            </div>
        `;
        return;
    }

    let html = '';
    filtered.slice(0, 40).forEach(emp => {
        const inGrid = isEmpAlreadyInGrid(emp.id);
        const isSelected = selectedEmpIdsForAddition.includes(emp.id);
        const name = emp.full_name || `${emp.first_name} ${emp.last_name}`;
        const initials = emp.initials || name.substring(0, 2).toUpperCase();
        const branch = emp.branch?.name || 'Main';
        const pos = emp.position?.name || 'Staff';

        html += `
            <div class="sched-emp-row-item ${inGrid ? 'already-added' : ''} ${isSelected ? 'selected' : ''}" 
                 onclick="toggleEmpAdditionSelection(${emp.id})">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                    <input type="checkbox" ${inGrid ? 'checked disabled' : (isSelected ? 'checked' : '')} 
                           onclick="event.stopPropagation(); toggleEmpAdditionSelection(${emp.id})" 
                           style="cursor: pointer;">
                    <div class="sched-emp-avatar" style="width: 28px; height: 28px; font-size: 10px;">${initials}</div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-weight: 700; font-size: 12px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(name)} <small style="color: #94a3b8; font-family: monospace;">(${escapeHtml(emp.employee_id || '')})</small>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 6px; align-items: center; flex-shrink: 0;">
                    <span style="font-size: 10px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; padding: 1px 5px; display: flex; align-items: center; gap: 3px;">
                        <i class="ph ph-map-pin"></i> ${escapeHtml(branch)}
                    </span>
                    <span style="font-size: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px;">
                        ${escapeHtml(pos)}
                    </span>

                    ${inGrid ? `
                        <span style="font-size: 9.5px; font-weight: 800; background: #dcfce7; color: #15803d; border-radius: 4px; padding: 2px 6px;">
                            <i class="ph ph-check"></i> In Grid
                        </span>
                    ` : `
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 10px; padding: 2px 8px;" onclick="event.stopPropagation(); addSingleEmpToGrid(${emp.id})">
                            + Add
                        </button>
                    `}
                </div>
            </div>
        `;
    });

    listEl.innerHTML = html;
    updateAdditionFooterCounts();
}

function filterDropdownEmployees() {
    renderDropdownEmployeeList();
}

function clearModalEmpSearch() {
    const inp = document.getElementById('modalEmpSearch');
    if (inp) inp.value = '';
    renderDropdownEmployeeList();
}

function resetDropdownFilters() {
    const inp = document.getElementById('modalEmpSearch');
    if (inp) inp.value = '';
    const st = document.getElementById('modalStatusFilter');
    if (st) st.value = '';
    const ct = document.getElementById('modalCategoryFilter');
    if (ct) ct.value = '';
    renderDropdownEmployeeList();
}

function toggleEmpAdditionSelection(empId) {
    if (isEmpAlreadyInGrid(empId)) return;
    const idx = selectedEmpIdsForAddition.indexOf(empId);
    if (idx >= 0) {
        selectedEmpIdsForAddition.splice(idx, 1);
    } else {
        selectedEmpIdsForAddition.push(empId);
    }
    renderDropdownEmployeeList();
}

function toggleSelectAllFilteredEmps() {
    const unaddedFiltered = ALL_EMPLOYEES.filter(emp => !isEmpAlreadyInGrid(emp.id));
    const allSelected = unaddedFiltered.every(e => selectedEmpIdsForAddition.includes(e.id));

    if (allSelected) {
        selectedEmpIdsForAddition = [];
    } else {
        unaddedFiltered.forEach(e => {
            if (!selectedEmpIdsForAddition.includes(e.id)) {
                selectedEmpIdsForAddition.push(e.id);
            }
        });
    }
    renderDropdownEmployeeList();
}

function updateAdditionFooterCounts() {
    const count = selectedEmpIdsForAddition.length;
    const countBadge = document.getElementById('selectedEmpBadge');
    if (countBadge) {
        countBadge.style.display = count > 0 ? 'inline-block' : 'none';
        countBadge.textContent = count;
    }
    const footerCount = document.getElementById('footerSelectedCount');
    if (footerCount) footerCount.textContent = count;
    const addCountText = document.getElementById('selectedAdditionCount');
    if (addCountText) addCountText.textContent = `${count} selected for addition`;
    const btn = document.getElementById('btnAddSelectedEmps');
    if (btn) btn.disabled = count === 0;
}

function addSingleEmpToGrid(empId) {
    const emp = ALL_EMPLOYEES.find(e => e.id == empId);
    if (!emp || isEmpAlreadyInGrid(empId)) return;

    appendEmployeeRowToTable(emp);
    renderDropdownEmployeeList();
    showSchedToast(`Added ${emp.full_name || emp.first_name} to grid`);
}

function addSelectedEmployeesToGrid() {
    if (selectedEmpIdsForAddition.length === 0) return;
    let added = 0;
    selectedEmpIdsForAddition.forEach(id => {
        const emp = ALL_EMPLOYEES.find(e => e.id == id);
        if (emp && !isEmpAlreadyInGrid(id)) {
            appendEmployeeRowToTable(emp);
            added++;
        }
    });

    selectedEmpIdsForAddition = [];
    updateAdditionFooterCounts();
    closeEmpDropdown();
    showSchedToast(`Added ${added} employee(s) to grid`);
}

function appendEmployeeRowToTable(emp) {
    const tbody = document.getElementById('schedMatrixTbody');
    const emptyRow = document.getElementById('schedEmptyRow');
    if (emptyRow) emptyRow.style.display = 'none';

    const name = emp.full_name || `${emp.first_name} ${emp.last_name}`;
    const initials = emp.initials || name.substring(0, 2).toUpperCase();
    const branch = emp.branch?.name || 'Main';
    const pos = emp.position?.name || 'Staff';
    const cat = emp.department?.name || 'Front of House';
    const currentWeekVal = document.getElementById('schedWeekInput')?.value || '{{ $weekStart }}';

    const tr = document.createElement('tr');
    tr.className = 'sched-row';
    tr.id = `schedRow_${emp.id}`;
    tr.setAttribute('data-emp-id', emp.id);
    tr.setAttribute('data-emp-name', name.toLowerCase());
    tr.setAttribute('data-emp-branch', branch.toLowerCase());
    tr.setAttribute('data-emp-pos', pos.toLowerCase());
    tr.setAttribute('data-emp-cat', cat.toLowerCase());

    const dates = @json($dates);
    let cellsHTML = '';

    dates.forEach(d => {
        cellsHTML += `
            <td class="sched-cell-td" id="cell_${emp.id}_${d}" data-emp-id="${emp.id}" data-date="${d}" data-emp-cat="${cat.toLowerCase()}">
                <div class="sched-empty-plotter">
                    <div class="sched-preset-grid">
                        <div class="sched-btn-row">
                            <button type="button" class="sched-mini-pill sched-pill-o" onclick="directPlotPreset(${emp.id}, '${d}', 'O')" title="OPENING Shift">O</button>
                            <button type="button" class="sched-mini-pill sched-pill-md" onclick="directPlotPreset(${emp.id}, '${d}', 'MD')" title="MID DAY Shift">MD</button>
                            <button type="button" class="sched-mini-pill sched-pill-ld" onclick="directPlotPreset(${emp.id}, '${d}', 'LD')" title="LATE DAY Shift">LD</button>
                        </div>
                        <div class="sched-btn-row">
                            <button type="button" class="sched-mini-pill sched-pill-c" onclick="directPlotPreset(${emp.id}, '${d}', 'C')" title="CLOSING Shift">C</button>
                            <button type="button" class="sched-mini-pill sched-pill-restday" onclick="directPlotPreset(${emp.id}, '${d}', 'OFF')" title="RESTDAY (Off Duty)">RESTDAY</button>
                        </div>
                        <div class="sched-btn-row">
                            <button type="button" class="sched-mini-pill sched-pill-custom" onclick="openCellCustomDropdown(${emp.id}, '${d}')" title="Select other shift or enter custom time">
                                Custom <i class="ph ph-caret-down" style="font-size: 8px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        `;
    });

    tr.innerHTML = `
        <td class="sched-sticky-col sched-sticky-td">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <div class="sched-emp-avatar">${initials}</div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25;">
                        ${escapeHtml(name)}
                    </div>
                    <div style="margin: 3px 0;">
                        <div class="sched-dept-pill-wrapper">
                            ${(() => {
                                const hasMulti = !!(emp.has_multiple_departments || (Array.isArray(emp.all_department_ids) && emp.all_department_ids.length > 1));
                                if (hasMulti) {
                                    return `
                                        <button type="button" 
                                                class="sched-dept-nav-btn prev" 
                                                onclick="cycleEmployeeDepartment(${emp.id}, -1, this)" 
                                                title="Previous department (click to cycle)"
                                                aria-label="Previous department">
                                            <i class="ph ph-caret-left"></i>
                                        </button>
                                        <span class="sched-cat-pill" 
                                              id="empDeptPill_${emp.id}" 
                                              data-current-dept="${escapeHtml(cat)}" 
                                              data-emp-id="${emp.id}" 
                                              onclick="cycleEmployeeDepartment(${emp.id}, 1, this)" 
                                              style="cursor: pointer;"
                                              title="Department: ${escapeHtml(cat)} (Click &lt; or &gt; to cycle)">
                                            <span class="sched-cat-dot" style="background-color: ${DEPT_COLORS[cat.toLowerCase()] || '#7c3aed'};"></span>
                                            <span class="truncate sched-dept-pill-text">${escapeHtml(cat)}</span>
                                        </span>
                                        <button type="button" 
                                                class="sched-dept-nav-btn next" 
                                                onclick="cycleEmployeeDepartment(${emp.id}, 1, this)" 
                                                title="Next department (click to cycle)"
                                                aria-label="Next department">
                                            <i class="ph ph-caret-right"></i>
                                        </button>
                                    `;
                                } else {
                                    return `
                                        <span class="sched-cat-pill" 
                                              id="empDeptPill_${emp.id}" 
                                              data-current-dept="${escapeHtml(cat)}" 
                                              data-emp-id="${emp.id}" 
                                              title="Department: ${escapeHtml(cat)}">
                                            <span class="sched-cat-dot" style="background-color: ${DEPT_COLORS[cat.toLowerCase()] || '#7c3aed'};"></span>
                                            <span class="truncate sched-dept-pill-text">${escapeHtml(cat)}</span>
                                        </span>
                                    `;
                                }
                            })()}
                        </div>
                    </div>
                    <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-map-pin" style="color: #7c3aed; font-size: 11.5px;"></i>
                        <span class="truncate" style="font-weight: 600; color: #475569;">${escapeHtml(branch)}</span>
                    </div>
                </div>
                <div class="sched-row-action-menu" style="position: relative;">
                    <button type="button" class="sched-row-btn" onclick="toggleRowMenu(${emp.id}, this)" title="Quick fill week">
                        <i class="ph ph-lightning"></i>
                    </button>
                    <div class="sched-row-dropdown" id="rowMenu_${emp.id}" style="display: none;">
                        <div style="padding: 6px 10px; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                            Quick Fill: ${escapeHtml(emp.first_name || name)}
                        </div>
                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset(${emp.id}, 'O', ['Sun'])">
                            <i class="ph ph-sun" style="color: #10b981;"></i>
                            <span>Mon–Sat Opening (Sun Off)</span>
                        </button>
                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset(${emp.id}, 'MD', ['Sun'])">
                            <i class="ph ph-clock" style="color: #3b82f6;"></i>
                            <span>Mon–Sat Mid Day (Sun Off)</span>
                        </button>
                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset(${emp.id}, 'C', ['Sun'])">
                            <i class="ph ph-moon" style="color: #9333ea;"></i>
                            <span>Mon–Sat Closing (Sun Off)</span>
                        </button>
                        <div style="border-top: 1px solid #f1f5f9; margin: 4px 0;"></div>
                        <button type="button" class="sched-row-dd-item text-danger" onclick="quickFillRowPreset(${emp.id}, 'OFF', ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])">
                            <i class="ph ph-coffee"></i>
                            <span>Mark Entire Week as Rest Days</span>
                        </button>
                        <button type="button" class="sched-row-dd-item text-danger" onclick="clearEmployeeWeek(${emp.id})">
                            <i class="ph ph-trash"></i>
                            <span>Clear All Shifts This Week</span>
                        </button>
                    </div>
                </div>
            </div>
        </td>
        <td class="sched-col-week" style="text-align: center; vertical-align: middle; color: #64748b; font-size: 12px; font-weight: 700; font-family: monospace;">
            ${escapeHtml(currentWeekVal)}
        </td>
        ${cellsHTML}
        <td class="sched-action-td" style="text-align: center; vertical-align: middle;">
            <button type="button" class="sched-btn-row-del" onclick="removeEmployeeRowFromGrid(${emp.id})" title="Remove row from grid">
                <i class="ph ph-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    updateVisibleRosterCount();
}

function removeEmployeeRowFromGrid(empId) {
    const row = document.getElementById(`schedRow_${empId}`);
    if (row) {
        row.remove();
        updateVisibleRosterCount();
        showSchedToast('Employee removed from grid view');
    }
}

function clearRosterGrid() {
    const doClear = () => {
        const tbody = document.getElementById('schedMatrixTbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr id="schedEmptyRow">
                    <td colspan="9" style="text-align: center; color: #94a3b8; padding: 48px;">
                        <div style="font-size: 28px; margin-bottom: 8px;"><i class="ph ph-users"></i></div>
                        <div style="font-weight: 700; color: #475569; font-size: 14px;">Grid is cleared</div>
                        <div style="font-size: 12px; color: #94a3b8;">Click "+ Add Employee Row" above to begin scheduling.</div>
                    </td>
                </tr>
            `;
            updateVisibleRosterCount();
            showSchedToast('Grid cleared for current view');
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Clear Schedule Grid?',
            text: 'Clear all employee rows from the current schedule view? (Saved schedules in database are safely preserved)',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Clear Grid',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                doClear();
            }
        });
    } else {
        if (!confirm('Clear all employee rows from the current schedule view? (Saved schedules in database are preserved)')) return;
        doClear();
    }
}

function filterMatrixByCategory() {
    const checkedCats = Array.from(document.querySelectorAll('.sched-cat-cb:checked')).map(cb => cb.value);
    const rows = document.querySelectorAll('.sched-row');

    rows.forEach(r => {
        const cat = r.getAttribute('data-emp-cat') || '';
        if (checkedCats.length === 0 || checkedCats.includes(cat)) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });

    updateVisibleRosterCount();
}

function filterMatrixRowsBySearch() {
    const q = (document.getElementById('liveEmployeeSearch')?.value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) clearBtn.style.display = q ? 'inline-flex' : 'none';

    const checkedCats = Array.from(document.querySelectorAll('.sched-cat-cb:checked')).map(cb => cb.value);
    const rows = document.querySelectorAll('.sched-row');

    rows.forEach(r => {
        const name = r.getAttribute('data-emp-name') || '';
        const branch = r.getAttribute('data-emp-branch') || '';
        const pos = r.getAttribute('data-emp-pos') || '';
        const cat = r.getAttribute('data-emp-cat') || '';

        const matchCat = checkedCats.length === 0 || checkedCats.includes(cat);
        const matchQ = !q || name.includes(q) || branch.includes(q) || pos.includes(q);

        if (matchCat && matchQ) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });

    updateVisibleRosterCount();
}

function clearLiveSearch() {
    const inp = document.getElementById('liveEmployeeSearch');
    if (inp) inp.value = '';
    filterMatrixRowsBySearch();
}

function updateVisibleRosterCount() {
    const visible = Array.from(document.querySelectorAll('.sched-row')).filter(r => r.style.display !== 'none').length;
    const badge = document.getElementById('empCountBadge');
    if (badge) badge.textContent = `${visible} Staff`;
}

// -------------------------------------------------------------
// Toast Helper
// -------------------------------------------------------------
let toastTimer = null;
function showSchedToast(msg, isSuccess = true) {
    const toast = document.getElementById('schedToast');
    const toastMsg = document.getElementById('schedToastMsg');
    const toastIcon = document.getElementById('schedToastIcon');
    if (!toast) return;

    toastMsg.textContent = msg;
    if (isSuccess) {
        toast.style.background = '#0f172a';
        toastIcon.innerHTML = '<i class="ph ph-check-circle" style="color: #10b981; font-size: 18px;"></i>';
    } else {
        toast.style.background = '#ef4444';
        toastIcon.innerHTML = '<i class="ph ph-warning-circle" style="color: #ffffff; font-size: 18px;"></i>';
    }

    toast.style.display = 'flex';
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        setTimeout(() => { toast.style.display = 'none'; }, 200);
    }, 2800);
}

// -------------------------------------------------------------
// Row Lightning Quick Fill Logic
// -------------------------------------------------------------
function toggleRowMenu(empId, btnElement = null) {
    const dd = document.getElementById(`rowMenu_${empId}`);
    if (!dd) return;

    const isAlreadyOpen = dd.classList.contains('open') && dd.style.display !== 'none';

    // Close all open row dropdowns first
    document.querySelectorAll('.sched-row-dropdown').forEach(d => {
        d.classList.remove('open');
        d.style.display = 'none';
        d.style.visibility = 'hidden';
    });

    if (isAlreadyOpen) {
        return;
    }

    let btn = btnElement;
    if (!btn) {
        const row = document.getElementById(`schedRow_${empId}`);
        btn = row ? row.querySelector('.sched-row-btn') : dd.parentElement?.querySelector('.sched-row-btn');
    }
    if (!btn) return;

    // Attach to document.body so position: fixed coordinates are always 100% relative to viewport
    // and never affected by containing blocks, sticky columns, or table scroll
    if (dd.parentElement !== document.body) {
        document.body.appendChild(dd);
    }

    // Show invisibly to measure real dimensions
    dd.style.visibility = 'hidden';
    dd.style.display = 'block';
    dd.classList.add('open');

    const rect = btn.getBoundingClientRect();
    const ddWidth = dd.offsetWidth || 230;
    const ddHeight = dd.offsetHeight || 220;
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Horizontal placement: Open directly BESIDE the button (to the right of the button)
    const spaceRight = viewportWidth - rect.right;
    const spaceLeft = rect.left;

    let left;
    if (spaceRight >= ddWidth + 12 || spaceRight >= spaceLeft) {
        // Sufficient room on the right side of the button
        left = rect.right + 8;
    } else {
        // Not enough room on the right -> flip to the left side of the button
        left = rect.left - ddWidth - 8;
    }
    // Prevent clipping past viewport edges
    left = Math.max(12, Math.min(left, viewportWidth - ddWidth - 12));

    // Vertical placement: Align with the top of the button, clamped within viewport
    let top = rect.top - 6;
    if (top + ddHeight > viewportHeight - 12) {
        top = Math.max(12, viewportHeight - ddHeight - 12);
    }
    if (top < 12) {
        top = 12;
    }

    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.visibility = 'visible';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.sched-row-action-menu') && !e.target.closest('.sched-row-btn') && !e.target.closest('.sched-row-dropdown')) {
        document.querySelectorAll('.sched-row-dropdown').forEach(d => {
            d.classList.remove('open');
            d.style.display = 'none';
            d.style.visibility = 'hidden';
        });
    }
});

document.addEventListener('scroll', function(e) {
    if (!e.target.closest('.sched-row-dropdown')) {
        document.querySelectorAll('.sched-row-dropdown.open').forEach(d => {
            d.classList.remove('open');
            d.style.display = 'none';
            d.style.visibility = 'hidden';
        });
    }
}, true);

window.addEventListener('resize', function() {
    document.querySelectorAll('.sched-row-dropdown.open').forEach(d => {
        d.classList.remove('open');
        d.style.display = 'none';
        d.style.visibility = 'hidden';
    });
});

function quickFillRowPreset(empId, shiftCode, restDays) {
    const weekStart = document.getElementById('schedWeekInput').value;
    document.querySelectorAll('.sched-row-dropdown').forEach(d => {
        d.classList.remove('open');
        d.style.display = 'none';
    });

    const dates = @json($dates);
    const rowEl = document.getElementById(`schedRow_${empId}`);
    const empCat = rowEl ? (rowEl.getAttribute('data-emp-cat') || 'default') : 'default';
    const cfg = getShiftConfig(empCat, shiftCode);

    const promises = dates.map(d => {
        const cDate = new Date(d);
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const dayName = dayNames[cDate.getDay()];
        const isRest = restDays.includes(dayName) || shiftCode === 'OFF';

        return fetch(QUICK_ASSIGN_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                employee_id: empId,
                date: d,
                shift_code: isRest ? 'OFF' : shiftCode,
                custom_start_time: isRest ? null : cfg.start,
                custom_end_time: isRest ? null : cfg.end,
                notes: isRest ? 'RESTDAY' : (cfg.label || shiftCode),
                is_rest_day: isRest
            })
        });
    });

    Promise.all(promises).then(() => {
        showSchedToast(`Filled week schedule`);
        setTimeout(() => { window.location.reload(); }, 600);
    });
}

function clearEmployeeWeek(empId) {
    const doClearWeek = () => {
        document.querySelectorAll('.sched-row-dropdown').forEach(d => {
            d.classList.remove('open');
            d.style.display = 'none';
        });

        const dates = @json($dates);
        const promises = dates.map(d => {
            return fetch(QUICK_ASSIGN_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ employee_id: empId, date: d, clear: true })
            });
        });

        Promise.all(promises).then(() => {
            showSchedToast('Cleared employee week');
            setTimeout(() => { window.location.reload(); }, 600);
        });
    };

    const row = document.getElementById(`schedRow_${empId}`);
    const empName = row?.getAttribute('data-emp-name') || 'this employee';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Clear Week Schedule?',
            html: `Clear all shifts for <strong>${escapeHtml(empName)}</strong> this week?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Clear Week',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                doClearWeek();
            }
        });
    } else {
        if (!confirm('Clear all shifts for this employee this week?')) return;
        doClearWeek();
    }
}

// -------------------------------------------------------------
// Copy Previous Week Schedules Handler
// -------------------------------------------------------------
function triggerCopyPreviousWeek() {
    const doCopy = () => {
        const currentWeekStart = document.getElementById('schedWeekInput').value;

        fetch(COPY_WEEK_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ current_week_start: currentWeekStart })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Copied Successfully!',
                        text: data.message || 'Schedules copied from previous week.',
                        icon: 'success',
                        confirmButtonColor: '#7c3aed',
                        timer: 1800
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    showSchedToast(data.message);
                    setTimeout(() => { window.location.reload(); }, 800);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Notice',
                        text: data.message || 'No schedules found in previous week to copy.',
                        icon: 'info',
                        confirmButtonColor: '#7c3aed'
                    });
                } else {
                    showSchedToast(data.message || 'No schedules found to copy', false);
                }
            }
        })
        .catch(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'Error copying previous week schedules',
                    icon: 'error',
                    confirmButtonColor: '#7c3aed'
                });
            } else {
                showSchedToast('Error copying previous week schedules', false);
            }
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Copy Previous Week?',
            text: 'Copy all shift schedules from the previous week into the current week?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ph ph-copy"></i> Yes, Copy Schedules',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                doCopy();
            }
        });
    } else {
        if (!confirm('Copy all shift schedules from the previous week into the current week?')) return;
        doCopy();
    }
}

// -------------------------------------------------------------
// Backward Compatibility Modals
// -------------------------------------------------------------
function openModal(id) { 
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('open'); 
}
function closeModal(id) { 
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('open'); 
}
function openAssignModal() {
    clearRestDaysPreset();
    document.getElementById('isRestDayAll').checked = false;
    toggleRestDayAll(document.getElementById('isRestDayAll'));
    openModal('assignScheduleModal');
}
function syncDateRange() {
    var start = document.getElementById('schedStartDate').value;
    var end = document.getElementById('schedEndDate').value;
    if (!end || end < start) document.getElementById('schedEndDate').value = start;
}
function presetDateRange(type) {
    var now = new Date();
    var formatDate = function(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    var start = new Date();
    var end = new Date();
    if (type === 'today') {
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'next_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'month') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }
    document.getElementById('schedStartDate').value = formatDate(start);
    document.getElementById('schedEndDate').value = formatDate(end);
}
function setRestDaysPreset(days) {
    var all = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    all.forEach(function(d) {
        var cb = document.getElementById('rest_day_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}
function clearRestDaysPreset() { setRestDaysPreset([]); }
function toggleRestDayAll(cb) {
    var shiftGroup = document.getElementById('shiftSelectionGroup');
    var shiftSelect = document.getElementById('schedShiftTemplateId');
    if (cb.checked) {
        shiftSelect.disabled = true;
        shiftGroup.style.opacity = '0.5';
    } else {
        shiftSelect.disabled = false;
        shiftGroup.style.opacity = '1';
    }
}
function formatTimeToAMPM(timeStr) {
    if (!timeStr) return '';
    var parts = timeStr.split(':');
    var h = parseInt(parts[0], 10);
    var m = parts[1] || '00';
    var ampm = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12;
    if (h12 === 0) h12 = 12;
    return h12 + (m !== '00' ? ':' + m : '') + ampm;
}
function updateShiftPreview() {
    var start = document.getElementById('newShiftStart').value || '06:00';
    var end = document.getElementById('newShiftEnd').value || '15:00';
    var code = start.replace(':', '');
    var startFmt = formatTimeToAMPM(start);
    var endFmt = formatTimeToAMPM(end);
    document.getElementById('shiftFormatPreview').textContent = code + ' = ' + startFmt + ' - ' + endFmt;
    var overnightCheckbox = document.getElementById('isOvernight');
    if (start > end) overnightCheckbox.checked = true;
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// -------------------------------------------------------------
// Batch Fill Grid & Draft Schedules Engine
// -------------------------------------------------------------
function openBatchFillGridModal() {
    setBatchFillScope('all');

    const dates = @json($dates);
    if (dates && dates.length > 0) {
        const sInput = document.getElementById('batchSchedStartDate');
        const eInput = document.getElementById('batchSchedEndDate');
        if (sInput) sInput.value = dates[0];
        if (eInput) eInput.value = dates[dates.length - 1];
    }

    renderBatchEmpChecklist();
    openModal('batchFillGridModal');
}

function setBatchFillScope(scope) {
    batchFillScope = scope;
    const btnAll = document.getElementById('btnBatchScopeAll');
    const btnCustom = document.getElementById('btnBatchScopeCustom');
    const customWrap = document.getElementById('batchCustomEmpWrap');
    const noticeAll = document.getElementById('batchAllEmpNotice');
    const badge = document.getElementById('batchEmpSelectedSummaryBadge');
    const allEmpCountText = document.getElementById('batchAllEmpCountText');

    const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
    const allIds = Array.from(gridRows).map(r => parseInt(r.getAttribute('data-emp-id'))).filter(Boolean);

    if (allEmpCountText) {
        allEmpCountText.textContent = `all ${allIds.length} employees`;
    }

    if (scope === 'all') {
        if (btnAll) btnAll.classList.add('active');
        if (btnCustom) btnCustom.classList.remove('active');
        if (customWrap) customWrap.style.display = 'none';
        if (noticeAll) noticeAll.style.display = 'flex';
        
        batchSelectedEmpIds = [...allIds];
        if (badge) {
            badge.textContent = `All in Grid (${batchSelectedEmpIds.length})`;
            badge.style.background = '#ede9fe';
            badge.style.color = '#6d28d9';
        }
    } else {
        if (btnAll) btnAll.classList.remove('active');
        if (btnCustom) btnCustom.classList.add('active');
        if (customWrap) customWrap.style.display = 'flex';
        if (noticeAll) noticeAll.style.display = 'none';

        if (batchSelectedEmpIds.length === 0) {
            batchSelectedEmpIds = [...allIds];
        }

        if (badge) {
            badge.textContent = `${batchSelectedEmpIds.length} Selected`;
            badge.style.background = '#f1f5f9';
            badge.style.color = '#334155';
        }
        renderBatchEmpChecklist();
    }

    const applyText = document.getElementById('batchApplyBtnText');
    if (applyText) {
        applyText.textContent = `Apply to Grid (Draft)`;
    }
}

function renderBatchEmpChecklist() {
    const listEl = document.getElementById('batchEmpChecklist');
    if (!listEl) return;

    const q = (document.getElementById('batchEmpSearchInput')?.value || '').toLowerCase().trim();
    const activeDeptBtn = document.querySelector('#batchDeptFilterBar .batch-dept-pill.active');
    const activeDept = activeDeptBtn ? activeDeptBtn.textContent.trim().toLowerCase() : 'all';

    const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
    const gridEmps = [];
    gridRows.forEach(r => {
        const id = parseInt(r.getAttribute('data-emp-id'));
        const name = r.querySelector('.sched-emp-name-text')?.textContent || r.getAttribute('data-emp-name') || 'Employee';
        const dept = r.getAttribute('data-emp-cat') || 'General';
        const branch = r.getAttribute('data-emp-branch') || 'Branch';
        const initials = r.querySelector('.sched-emp-avatar')?.textContent?.trim() || name.substring(0, 2).toUpperCase();
        gridEmps.push({ id, name, dept, branch, initials });
    });

    const filtered = gridEmps.filter(emp => {
        const matchQ = !q || emp.name.toLowerCase().includes(q) || String(emp.id).includes(q) || emp.dept.toLowerCase().includes(q);
        const matchDept = (activeDept === 'all') || emp.dept.toLowerCase().includes(activeDept);
        return matchQ && matchDept;
    });

    if (filtered.length === 0) {
        listEl.innerHTML = `<div style="padding: 14px; text-align: center; color: #94a3b8; font-size: 11px;">No employees match filter</div>`;
        return;
    }

    let html = '';
    filtered.forEach(emp => {
        const isChecked = batchSelectedEmpIds.includes(emp.id);
        html += `
            <label style="display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; border-radius: 6px; background: ${isChecked ? '#f5f3ff' : '#f8fafc'}; border: 1px solid ${isChecked ? '#c4b5fd' : '#e2e8f0'}; cursor: pointer; user-select: none;">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                    <input type="checkbox" value="${emp.id}" ${isChecked ? 'checked' : ''} onchange="toggleBatchEmpSelection(${emp.id}, this.checked)" style="cursor: pointer;">
                    <div class="sched-emp-avatar" style="width: 22px; height: 22px; font-size: 9px;">${escapeHtml(emp.initials)}</div>
                    <div style="min-width: 0;">
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(emp.name)}</div>
                    </div>
                </div>
                <span style="font-size: 9.5px; font-weight: 700; color: #64748b; background: #e2e8f0; padding: 1px 6px; border-radius: 4px; text-transform: uppercase;">${escapeHtml(emp.dept)}</span>
            </label>
        `;
    });
    listEl.innerHTML = html;
}

function toggleBatchEmpSelection(empId, isChecked) {
    if (isChecked) {
        if (!batchSelectedEmpIds.includes(empId)) batchSelectedEmpIds.push(empId);
    } else {
        batchSelectedEmpIds = batchSelectedEmpIds.filter(id => id !== empId);
    }
    updateBatchEmpSelectionBadge();
}

function updateBatchEmpSelectionBadge() {
    const badge = document.getElementById('batchEmpSelectedSummaryBadge');
    if (badge) {
        badge.textContent = `${batchSelectedEmpIds.length} Selected`;
    }
    const applyText = document.getElementById('batchApplyBtnText');
    if (applyText) {
        applyText.textContent = `Apply to Grid (Draft)`;
    }
}

function batchSelectAllEmps(selectAll) {
    const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
    if (selectAll) {
        batchSelectedEmpIds = Array.from(gridRows).map(r => parseInt(r.getAttribute('data-emp-id'))).filter(Boolean);
    } else {
        batchSelectedEmpIds = [];
    }
    renderBatchEmpChecklist();
    updateBatchEmpSelectionBadge();
}

function filterBatchEmpsByDept(dept, btn) {
    document.querySelectorAll('#batchDeptFilterBar .batch-dept-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderBatchEmpChecklist();
}

function filterBatchModalEmployees() {
    renderBatchEmpChecklist();
}

function presetBatchDateRange(type) {
    var now = new Date();
    var formatDate = function(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    var start = new Date();
    var end = new Date();
    if (type === 'today') {
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'next_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    }
    var sInput = document.getElementById('batchSchedStartDate');
    var eInput = document.getElementById('batchSchedEndDate');
    if (sInput) sInput.value = formatDate(start);
    if (eInput) eInput.value = formatDate(end);
}

function syncBatchDateRange() {
    var start = document.getElementById('batchSchedStartDate')?.value;
    var end = document.getElementById('batchSchedEndDate')?.value;
    if (start && (!end || end < start)) {
        document.getElementById('batchSchedEndDate').value = start;
    }
}

function setBatchRestDaysPreset(days) {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('batch_rest_day_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}

function clearBatchRestDaysPreset() {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('batch_rest_day_' + d);
        if (cb) cb.checked = false;
    });
}

function applyBatchFillGridDraft() {
    let targetEmpIds = [];
    if (batchFillScope === 'all') {
        const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
        targetEmpIds = Array.from(gridRows).map(r => parseInt(r.getAttribute('data-emp-id'))).filter(Boolean);
    } else {
        targetEmpIds = [...batchSelectedEmpIds];
    }

    if (targetEmpIds.length === 0) {
        showSchedToast('Please select at least one employee to fill', false);
        return;
    }

    const startDate = document.getElementById('batchSchedStartDate')?.value;
    const endDate = document.getElementById('batchSchedEndDate')?.value;
    if (!startDate || !endDate) {
        showSchedToast('Please select valid start and end dates', false);
        return;
    }

    const templateSelect = document.getElementById('batchSchedShiftTemplateId');
    const selectedOpt = templateSelect?.selectedOptions[0];
    const val = templateSelect?.value;
    if (!val) {
        showSchedToast('Please select a shift format or rest day', false);
        return;
    }

    const isAllRest = val === 'OFF';
    const tmplCode = isAllRest ? 'OFF' : (selectedOpt?.getAttribute('data-code') || 'CUSTOM');
    const tmplName = isAllRest ? 'RESTDAY' : (selectedOpt?.getAttribute('data-name') || selectedOpt?.text || 'Shift');
    const tmplStart = selectedOpt?.getAttribute('data-start') || '';
    const tmplEnd = selectedOpt?.getAttribute('data-end') || '';
    const tmplColor = selectedOpt?.getAttribute('data-color') || '#7c3aed';
    const tmplId = isAllRest ? null : (parseInt(val) || null);

    const restDays = [];
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(d => {
        if (document.getElementById('batch_rest_day_' + d)?.checked) {
            restDays.push(d);
        }
    });

    const gridDates = @json($dates);
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let plottedCount = 0;

    targetEmpIds.forEach(empId => {
        gridDates.forEach(d => {
            if (d >= startDate && d <= endDate) {
                const dateObj = new Date(d + 'T00:00:00');
                const dayName = dayNames[dateObj.getDay()];
                const isRest = isAllRest || restDays.includes(dayName);

                const draftKey = `${empId}_${d}`;
                const cell = document.getElementById(`cell_${empId}_${d}`);
                if (cell) {
                    if (!savedOriginalCells[draftKey]) {
                        savedOriginalCells[draftKey] = cell.innerHTML;
                    }

                    const code = isRest ? 'OFF' : tmplCode;
                    const label = isRest ? 'RESTDAY' : tmplName;
                    const time = isRest ? 'OFF DUTY' : (tmplStart && tmplEnd ? `${tmplStart} - ${tmplEnd}` : '08:00 - 17:00');
                    const themeClass = isRest ? 'sched-card-rest' : `sched-theme-${code.toLowerCase()}`;

                    draftSchedules[draftKey] = {
                        employee_id: empId,
                        date: d,
                        shift_template_id: isRest ? null : tmplId,
                        is_rest_day: isRest,
                        custom_start_time: isRest ? null : tmplStart,
                        custom_end_time: isRest ? null : tmplEnd,
                        notes: isRest ? 'RESTDAY' : tmplName,
                        is_draft: true
                    };

                    renderAssignedCardHTML(cell, empId, d, code, label, time, themeClass, tmplColor);

                    const card = cell.querySelector('.sched-shift-card');
                    if (card) {
                        card.classList.add('is-draft');
                        const badgeWrap = card.querySelector('.sched-badge-wrap');
                        if (badgeWrap && !badgeWrap.querySelector('.sched-draft-pill')) {
                            const draftPill = document.createElement('span');
                            draftPill.className = 'sched-draft-pill';
                            draftPill.innerHTML = '<i class="ph ph-pencil-simple" style="font-size: 8px;"></i> Draft';
                            badgeWrap.appendChild(draftPill);
                        }
                        const detailBadge = card.querySelector('.sched-detail-badge');
                        if (detailBadge) {
                            detailBadge.className = 'sched-detail-badge badge-draft';
                            detailBadge.textContent = 'DRAFT';
                            detailBadge.style.cssText = 'background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; font-weight: 800;';
                        }
                    }
                    plottedCount++;
                }
            }
        });
    });

    closeModal('batchFillGridModal');
    updateSaveButtonState();
    showSchedToast(`Plotted ${plottedCount} shifts as draft across ${targetEmpIds.length} employee(s). Click "Save" to save.`);
}

function applySingleEmpFillGridDraft() {
    const empId = document.getElementById('schedEmployeeIdModal')?.value || activeCellEmpId;
    if (!empId) {
        showSchedToast('No employee selected', false);
        return;
    }

    const startDate = document.getElementById('schedStartDateModal')?.value;
    const endDate = document.getElementById('schedEndDateModal')?.value;
    if (!startDate || !endDate) {
        showSchedToast('Please select valid start and end dates', false);
        return;
    }

    const templateSelect = document.getElementById('schedShiftTemplateIdModal');
    const selectedOpt = templateSelect?.selectedOptions[0];
    const val = templateSelect?.value;
    if (!val) {
        showSchedToast('Please select a shift format', false);
        return;
    }

    const tmplCode = selectedOpt?.getAttribute('data-code') || (selectedOpt ? selectedOpt.text.split('=')[0].trim() : 'CUSTOM');
    const tmplName = selectedOpt?.getAttribute('data-name') || selectedOpt?.text || 'Shift';
    const tmplStart = selectedOpt?.getAttribute('data-start') || '';
    const tmplEnd = selectedOpt?.getAttribute('data-end') || '';
    const tmplColor = selectedOpt?.getAttribute('data-color') || '#7c3aed';
    const tmplId = parseInt(val) || null;

    const restDays = [];
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(d => {
        if (document.getElementById('rest_day_modal_' + d)?.checked) {
            restDays.push(d);
        }
    });

    const gridDates = @json($dates);
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let plottedCount = 0;

    gridDates.forEach(d => {
        if (d >= startDate && d <= endDate) {
            const dateObj = new Date(d + 'T00:00:00');
            const dayName = dayNames[dateObj.getDay()];
            const isRest = restDays.includes(dayName);

            const draftKey = `${empId}_${d}`;
            const cell = document.getElementById(`cell_${empId}_${d}`);
            if (cell) {
                if (!savedOriginalCells[draftKey]) {
                    savedOriginalCells[draftKey] = cell.innerHTML;
                }

                const code = isRest ? 'OFF' : tmplCode;
                const label = isRest ? 'RESTDAY' : tmplName;
                const time = isRest ? 'OFF DUTY' : (tmplStart && tmplEnd ? `${tmplStart} - ${tmplEnd}` : '08:00 - 17:00');
                const themeClass = isRest ? 'sched-card-rest' : `sched-theme-${code.toLowerCase()}`;

                draftSchedules[draftKey] = {
                    employee_id: parseInt(empId),
                    date: d,
                    shift_template_id: isRest ? null : tmplId,
                    is_rest_day: isRest,
                    custom_start_time: isRest ? null : tmplStart,
                    custom_end_time: isRest ? null : tmplEnd,
                    notes: isRest ? 'RESTDAY' : tmplName,
                    is_draft: true
                };

                renderAssignedCardHTML(cell, empId, d, code, label, time, themeClass, tmplColor);

                const card = cell.querySelector('.sched-shift-card');
                if (card) {
                    card.classList.add('is-draft');
                    const badgeWrap = card.querySelector('.sched-badge-wrap');
                    if (badgeWrap && !badgeWrap.querySelector('.sched-draft-pill')) {
                        const draftPill = document.createElement('span');
                        draftPill.className = 'sched-draft-pill';
                        draftPill.innerHTML = '<i class="ph ph-pencil-simple" style="font-size: 8px;"></i> Draft';
                        badgeWrap.appendChild(draftPill);
                    }
                    const detailBadge = card.querySelector('.sched-detail-badge');
                    if (detailBadge) {
                        detailBadge.className = 'sched-detail-badge badge-draft';
                        detailBadge.textContent = 'DRAFT';
                        detailBadge.style.cssText = 'background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; font-weight: 800;';
                    }
                }
                plottedCount++;
            }
        }
    });

    closeCellCustomDropdown();
    updateSaveButtonState();
    showSchedToast(`Plotted ${plottedCount} draft shifts for employee. Click "Save" to save.`);
}

function updateSaveButtonState() {
    const count = Object.keys(draftSchedules).length;
    const saveBtn = document.getElementById('btnSaveGrid');
    const badge = document.getElementById('saveBtnBadge');
    const label = document.getElementById('saveBtnLabel');
    if (!saveBtn) return;

    if (count > 0) {
        saveBtn.classList.add('has-drafts');
        if (badge) {
            badge.style.display = 'inline-flex';
            badge.textContent = count;
        }
        if (label) {
            label.textContent = `Save (${count})`;
        }
    } else {
        saveBtn.classList.remove('has-drafts');
        if (badge) {
            badge.style.display = 'none';
            badge.textContent = '0';
        }
        if (label) {
            label.textContent = 'Save';
        }
    }
}

function saveDraftSchedules() {
    const draftKeys = Object.keys(draftSchedules);
    if (draftKeys.length === 0) {
        showSchedToast('All shift changes are saved and active!');
        return;
    }

    const saveBtn = document.getElementById('btnSaveGrid');
    const saveLabel = document.getElementById('saveBtnLabel');
    
    if (saveBtn) {
        saveBtn.disabled = true;
    }
    if (saveLabel) {
        saveLabel.textContent = 'Saving...';
    }

    const payload = {
        schedules: Object.values(draftSchedules).map(item => ({
            employee_id: item.employee_id,
            date: item.date,
            shift_template_id: item.shift_template_id || null,
            is_rest_day: !!item.is_rest_day,
            custom_start_time: item.custom_start_time || null,
            custom_end_time: item.custom_end_time || null,
            notes: item.notes || null,
            clear: !!item.clear
        }))
    };

    fetch(BATCH_SCHEDULE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            draftKeys.forEach(key => {
                const parts = key.split('_');
                const empId = parts[0];
                const date = parts[1];
                const cell = document.getElementById(`cell_${empId}_${date}`);
                if (cell) {
                    const card = cell.querySelector('.sched-shift-card');
                    if (card) {
                        card.classList.remove('is-draft');
                        const draftPill = card.querySelector('.sched-draft-pill');
                        if (draftPill) draftPill.remove();
                        const draftBadge = card.querySelector('.badge-draft');
                        if (draftBadge) {
                            draftBadge.className = 'sched-detail-badge badge-planned';
                            draftBadge.textContent = 'SCHEDULED';
                            draftBadge.removeAttribute('style');
                        }
                    }
                }
            });

            const count = data.saved_count || draftKeys.length;
            draftSchedules = {};
            savedOriginalCells = {};
            updateSaveButtonState();
            showSchedToast(`Successfully saved ${count} schedule entries!`);
        } else {
            showSchedToast(data.message || 'Failed to save schedules', false);
            updateSaveButtonState();
        }
    })
    .catch(err => {
        console.error(err);
        showSchedToast('Network error while saving schedules', false);
        updateSaveButtonState();
    })
    .finally(() => {
        if (saveBtn) {
            saveBtn.disabled = false;
        }
        if (saveLabel && Object.keys(draftSchedules).length === 0) {
            saveLabel.textContent = 'Save';
        }
    });
}

window.addEventListener('beforeunload', function(e) {
    if (typeof draftSchedules !== 'undefined' && Object.keys(draftSchedules).length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
@endpush
@endsection
