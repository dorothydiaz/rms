@extends('layouts.app')

@section('title', 'Work Schedule Management - Attendance')

@section('content')
@php
    $cWeek = \Carbon\Carbon::parse($weekStart);
    $prevWeek = $cWeek->copy()->subDays(7)->toDateString();
    $nextWeek = $cWeek->copy()->addDays(7)->toDateString();
    $thisWeek = \Carbon\Carbon::now()->startOfWeek()->toDateString();

    // Collect available category names from departments, branches, or positions
    $categories = collect();
    if (isset($departments) && $departments->isNotEmpty()) {
        $categories = $departments->pluck('name');
    } else {
        $categories = collect(['Power Mac Center', 'Kiosk', 'Head Office']);
    }
@endphp

<x-hr-tabs parent="time-attendance">
    <x-slot:actions>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openModal('addShiftModal')" style="padding: 6px 13px; font-size: 12.5px; font-weight: 700;">
                <i class="ph ph-clock-afternoon" style="font-size: 15px;"></i>
                <span>Add Shift Template</span>
            </button>
            <button type="button" class="hr-btn hr-btn-primary" onclick="openAssignModal()" style="padding: 6px 15px; font-size: 12.5px; font-weight: 700;">
                <i class="ph ph-calendar-plus" style="font-size: 15px;"></i>
                <span>Bulk Assign Schedule</span>
            </button>
        </div>
    </x-slot:actions>
</x-hr-tabs>

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

        <!-- Navigation Controls Toolbar -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; margin-left: auto;">
            <!-- Week Navigation Controls -->
            <div style="display: flex; align-items: center; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 9px; padding: 3px 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <a href="{{ route('hr.attendance.schedules', array_merge(request()->query(), ['week_start' => $prevWeek])) }}" 
                   class="sched-nav-btn" title="Previous Week ({{ \Carbon\Carbon::parse($prevWeek)->format('M d') }})" style="padding: 3px 6px; font-size: 13px;">
                    <i class="ph ph-caret-left"></i>
                </a>

                <form method="GET" action="{{ route('hr.attendance.schedules') }}" id="weekPickerForm" style="display: inline-flex; align-items: center; margin: 0 4px;">
                    @if(request('branch_id'))
                        <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                    @endif
                    <label for="schedWeekInput" style="font-size: 12px; font-weight: 700; color: #475569; margin-right: 5px; cursor: pointer;">
                        Week:
                    </label>
                    <input type="date" name="week_start" id="schedWeekInput" value="{{ $weekStart }}" onchange="this.form.submit()" 
                           style="border: none; background: transparent; font-size: 12.5px; font-weight: 800; color: #0f172a; outline: none; cursor: pointer; padding: 2px;">
                </form>

                <a href="{{ route('hr.attendance.schedules', array_merge(request()->query(), ['week_start' => $nextWeek])) }}" 
                   class="sched-nav-btn" title="Next Week ({{ \Carbon\Carbon::parse($nextWeek)->format('M d') }})" style="padding: 3px 6px; font-size: 13px;">
                    <i class="ph ph-caret-right"></i>
                </a>

                @if($weekStart !== $thisWeek)
                    <a href="{{ route('hr.attendance.schedules', array_merge(request()->query(), ['week_start' => $thisWeek])) }}" 
                       class="sched-preset-btn" style="margin-left: 5px; font-size: 11px; font-weight: 700; padding: 2px 7px;">
                        Current
                    </a>
                @endif
            </div>

            <!-- Toolbar buttons matching Picture 2 -->
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openModal('addShiftModal')" title="Add Custom Shift Template" style="font-size: 12px; font-weight: 700; padding: 6px 12px;">
                <i class="ph ph-clock-afternoon" style="color: #7c3aed; font-size: 14px;"></i>
                <span>Add Template</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openAssignModal()" title="Fill Weekly Grid" style="font-size: 12px; font-weight: 700; padding: 6px 12px;">
                <i class="ph ph-magic-wand" style="color: #d97706; font-size: 14px;"></i>
                <span>Fill Grid</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="triggerCopyPreviousWeek()" title="Copy schedules from previous week" style="font-size: 12px; font-weight: 700; padding: 6px 12px;">
                <i class="ph ph-copy" style="color: #2563eb; font-size: 14px;"></i>
                <span>Copy Prev</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openShiftMasterModal()" title="Shift Settings" style="font-size: 12px; font-weight: 700; padding: 6px 12px;">
                <i class="ph ph-sliders" style="color: #64748b; font-size: 14px;"></i>
                <span>Shift Settings</span>
            </button>
            <button type="button" class="hr-btn hr-btn-primary" onclick="showSchedToast('All shift changes saved and active!')" title="Save Grid" style="font-size: 12px; font-weight: 800; padding: 6px 14px;">
                <i class="ph ph-floppy-disk" style="font-size: 14px;"></i>
                <span>Save</span>
            </button>
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

        <!-- 2. Category Checkbox Filter Group -->
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
        </div>

        <!-- 3. Real-time Search Input -->
        <div style="position: relative; min-width: 180px; margin-left: auto;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
            <input type="text" id="liveEmployeeSearch" placeholder="Search visible rows..." 
                   oninput="filterMatrixRowsBySearch()"
                   style="width: 100%; font-size: 11.5px; padding: 5px 22px 5px 26px; border: 1.5px solid #e2e8f0; border-radius: 8px; outline: none;">
            <button type="button" id="clearSearchBtn" onclick="clearLiveSearch()" 
                    style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; cursor: pointer; display: none;">
                &times;
            </button>
        </div>

        <!-- 4. Clear Grid Action -->
        <button type="button" class="sched-btn-clear-grid" onclick="clearRosterGrid()" title="Clear employee rows from current view">
            Clear Grid
        </button>
    </div>

    <!-- Weekly Interactive Schedule Table Matrix (Spacious & Breathable Layout) -->
    <div class="hr-table-wrapper" style="flex: 1 1 0%; height: 100%; min-height: 0; max-height: none; overflow-y: auto; overflow-x: auto; width: 100%; position: relative;">
        <table class="hr-table sched-matrix-table" id="schedMatrixTable" role="grid" style="border-collapse: separate; border-spacing: 0; table-layout: fixed; width: 100%;">
            <colgroup>
                <col style="width: 17%; min-width: 200px;">
                <col class="sched-col-week" style="width: 7%; min-width: 85px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 10.4%; min-width: 140px;">
                <col style="width: 3.2%; min-width: 40px;">
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
                        <th style="text-align: center; {{ $isToday ? 'background: rgba(124, 58, 237, 0.08); border-bottom: 2px solid #7c3aed;' : '' }}">
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
                    <th style="text-align: center;"></th>
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
                                    <!-- Category Pill -->
                                    <div style="margin: 3px 0;">
                                        <span class="sched-cat-pill">
                                            <span class="sched-cat-dot"></span>
                                            <span class="truncate">{{ $categoryName }}</span>
                                        </span>
                                    </div>
                                    <!-- Branch Location Anchor -->
                                    <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 4px;" title="{{ $branchName }}">
                                        <i class="ph ph-map-pin" style="color: #7c3aed; font-size: 11.5px;"></i>
                                        <span class="truncate" style="font-weight: 600; color: #475569;">{{ $branchName }}</span>
                                    </div>
                                </div>

                                <!-- ⚡ Quick Fill Menu for this row -->
                                <div class="sched-row-action-menu" style="position: relative;">
                                    <button type="button" class="sched-row-btn" onclick="toggleRowMenu({{ $emp->id }})" title="Quick fill week for {{ $emp->full_name }}">
                                        <i class="ph ph-lightning"></i>
                                    </button>
                                    
                                    <div class="sched-row-dropdown" id="rowMenu_{{ $emp->id }}">
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
                                    <div class="sched-shift-card {{ $themeClass }}">
                                        <!-- Card Top Header with Pill & Clear Button -->
                                        <div class="sched-card-top">
                                            <div class="sched-badge-wrap">
                                                <span class="sched-badge-code">{{ $sCode }}</span>
                                                <span class="sched-badge-name">{{ $sLabel }}</span>
                                            </div>
                                            <button type="button" class="sched-card-clear" onclick="clearCellShift({{ $emp->id }}, '{{ $d }}')" title="Clear Shift">
                                                &times;
                                            </button>
                                        </div>

                                        <!-- Scheduled Time Display -->
                                        <div class="sched-card-time" onclick="openCellCustomDropdown({{ $emp->id }}, '{{ $d }}')">
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
                                                    <span class="sched-detail-punch">{{ $inTime }} - {{ $outTime }}</span>
                                                    <span class="sched-detail-badge {{ $badgeClass }}">{{ $statusText }}</span>
                                                </div>
                                                <div class="sched-detail-row sched-detail-hours">
                                                    <span>Worked:</span>
                                                    <strong>{{ $hrs }} hrs</strong>
                                                </div>
                                            </div>
                                        @elseif($isRest)
                                            <div class="sched-card-detail-box">
                                                <div class="sched-detail-row">
                                                    <span class="sched-detail-dot dot-no-inout">●</span>
                                                    <span class="sched-detail-punch">Rest Day</span>
                                                    <span class="sched-detail-badge" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;">OFF DUTY</span>
                                                </div>
                                                <div class="sched-detail-row sched-detail-hours">
                                                    <span>Hours:</span>
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
                                                    <span>Target:</span>
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
                        <td style="text-align: center; vertical-align: middle;">
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

<!-- Floating Cell Custom Schedule Dropdown Popover (Attached to active cell) -->
<div id="cellCustomDropdown" class="sched-custom-dropdown" style="display: none;">
    <div class="sched-custom-dd-header">
        <span style="font-weight: 800; font-size: 11px; color: #0f172a; text-transform: uppercase;">Other Schedule / Custom</span>
        <button type="button" class="sched-btn-close-sm" onclick="closeCellCustomDropdown()">&times;</button>
    </div>
    
    <!-- Standard Templates Quick List -->
    <div style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-size: 11px; max-height: 140px; overflow-y: auto;">
        <div style="font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Registered Shifts:</div>
        @foreach($shiftTemplates as $st)
            <button type="button" class="sched-dd-shift-item" onclick="applyTemplateToActiveCell({{ $st->id }}, '{{ $st->code }}', '{{ $st->name }}', '{{ substr($st->start_time,0,5) }}', '{{ substr($st->end_time,0,5) }}', '{{ $st->color ?? '#7c3aed' }}')">
                <span class="sched-card-pill" style="background: {{ $st->color ?? '#7c3aed' }}; color: #fff; font-size: 8.5px;">{{ $st->code ?: substr($st->name, 0, 4) }}</span>
                <span style="font-weight: 700; color: #1e293b; font-size: 11px;">{{ $st->formatted_label ?? $st->name }}</span>
            </button>
        @endforeach
    </div>

    <!-- Custom Time Inputs -->
    <div style="padding: 10px; background: #faf5ff; border-bottom: 1px solid #e9d5ff;">
        <div style="font-size: 10px; font-weight: 800; color: #7c3aed; text-transform: uppercase; margin-bottom: 6px;">
            + Custom Time Field:
        </div>
        <div style="display: flex; gap: 6px; align-items: center; margin-bottom: 6px;">
            <input type="time" id="customCellStart" value="08:00" class="sched-input-time" title="Start Time">
            <span style="font-size: 10px; color: #7c3aed; font-weight: bold;">→</span>
            <input type="time" id="customCellEnd" value="17:00" class="sched-input-time" title="End Time">
        </div>
        <input type="text" id="customCellLabel" placeholder="Label (e.g. Split Shift)" class="sched-input-text-sm" style="margin-bottom: 6px;">
        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" style="width: 100%; justify-content: center;" onclick="applyCustomTimeToActiveCell()">
            Apply Custom Shift
        </button>
    </div>

    <!-- Edit Shift Times Button -->
    <div style="padding: 8px 10px; background: #f8fafc; text-align: center;">
        <button type="button" class="sched-btn-edit-times" onclick="openShiftMasterModal(); closeCellCustomDropdown();">
            <i class="ph ph-sliders"></i> Edit Shift Times (O, MD, LD, C)
        </button>
    </div>
</div>

<!-- Modal: Shift Master Settings (Configurable hours for O, MD, LD, C per Category matching Schedule.html) -->
<div id="shiftMasterSettingsModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header" style="background: #f8fafc;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="sched-avatar-circle" style="background: #ede9fe; color: #7c3aed; width: 32px; height: 32px;">
                    <i class="ph ph-clock"></i>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Shift Master Settings</h3>
                    <p style="font-size: 11px; color: #64748b; margin: 0;">Configure default shift hours for each Position Category.</p>
                </div>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('shiftMasterSettingsModal')"><i class="ph ph-x"></i></button>
        </div>

        <div class="hr-modal-body" style="padding: 16px;">
            <!-- Category Tabs -->
            <div style="margin-bottom: 12px;">
                <label style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; display: block;">
                    Select Position Category
                </label>
                <div class="sched-cat-tabs" id="shiftModalCatTabs">
                    @foreach($categories as $idx => $cat)
                        <button type="button" class="sched-cat-tab-btn {{ $loop->first ? 'active' : '' }}" 
                                onclick="switchShiftModalCategory('{{ $cat }}', this)">
                            <span>{{ $cat }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Informational Banner -->
            <div style="display: flex; justify-content: space-between; align-items: center; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; font-size: 11.5px; color: #1e40af;">
                <span>Editing shift times for <strong id="modalActiveCategoryLabel">{{ $categories->first() ?? 'Default' }}</strong></span>
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

<style>
/* 1. Viewport & Container Lock: Fit Weekly Table to Screen with Zero Page Scroll */
.content-area {
    height: calc(100vh - 64px) !important;
    max-height: calc(100vh - 64px) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 8px 16px 6px 16px !important;
    box-sizing: border-box !important;
}

/* Compact Parent Header on Schedule Planner */
.hr-parent-header {
    margin-bottom: 8px !important;
    padding-bottom: 0 !important;
    flex-shrink: 0 !important;
}
.hr-parent-title-row {
    margin-bottom: 4px !important;
    gap: 10px !important;
}
.hr-parent-title {
    font-size: 19px !important;
    gap: 10px !important;
}
.hr-parent-title i {
    width: 32px !important;
    height: 32px !important;
    font-size: 17px !important;
    border-radius: 9px !important;
}
.hr-parent-subtitle {
    display: none !important;
}
.hr-tabs-wrapper {
    margin-top: 4px !important;
    margin-bottom: 8px !important;
    padding: 4px 6px !important;
    border-radius: 12px !important;
    flex-shrink: 0 !important;
}
.hr-tab-item {
    padding: 6px 13px !important;
    font-size: 12.5px !important;
    border-radius: 8px !important;
}

/* Schedule Planner Card: Full Flex Column */
#schedPlannerCard {
    flex: 1 1 0% !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    margin-bottom: 0 !important;
    overflow: hidden !important;
    border-radius: 12px !important;
}

#schedPlannerCard .hr-table-wrapper {
    flex: 1 1 0% !important;
    height: 100% !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow-y: auto !important;
    overflow-x: auto !important;
    position: relative !important;
}

/* Sticky thead for matrix table (Spacious padding) */
.sched-matrix-table thead th {
    position: sticky !important;
    top: 0 !important;
    background: #f8fafc !important;
    z-index: 20 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    padding: 10px 8px !important;
}
.sched-matrix-table thead th.sched-sticky-th {
    z-index: 35 !important;
    left: 0 !important;
}

/* Sub Toolbar (Add Employee Row + Category Checkboxes + Search + Clear) */
.sched-sub-toolbar {
    padding: 7px 16px;
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

/* Matrix Table Fixed Layout (Spacious, Breathable & Fits Cleanly) */
.sched-matrix-table {
    width: 100% !important;
    min-width: 1280px !important;
    table-layout: fixed !important;
    border-collapse: separate !important;
    margin: 0 !important;
}

.sched-sticky-th {
    position: sticky !important;
    left: 0;
    background: #f8fafc !important;
    border-right: 2px solid #e2e8f0 !important;
    box-shadow: 3px 0 6px rgba(0,0,0,0.03);
    overflow: hidden;
    padding: 11px 12px !important;
}
.sched-sticky-td {
    position: sticky !important;
    left: 0;
    background: #ffffff !important;
    border-right: 2px solid #e2e8f0 !important;
    box-shadow: 3px 0 6px rgba(0,0,0,0.02);
    z-index: 10;
    overflow: hidden;
    padding: 10px 14px !important;
}
.sched-row:hover .sched-sticky-td {
    background: #f8fafc !important;
}

.sched-col-week {
    width: 7% !important;
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

.sched-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 2.5px 9px;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    max-width: 100%;
}
.sched-cat-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #7c3aed;
    flex-shrink: 0;
}

/* Cell Preset Buttons (Spacious & Breathable Grid) */
.sched-cell-td {
    padding: 6px 5px !important;
    vertical-align: middle !important;
    text-align: center;
    position: relative;
    min-height: 108px !important;
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
    gap: 3.5px;
    width: 100%;
    height: 100%;
    min-height: 96px;
    max-height: 102px;
    margin: 0;
    border: 1.5px dashed #93c5fd;
    border-radius: 9px;
    padding: 5px 6px;
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
    border-radius: 9px;
    padding: 7px 9px;
    transition: all 0.15s ease;
    cursor: pointer;
    text-align: left;
    height: 100%;
    min-height: 96px;
    max-height: 102px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 4px;
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
    gap: 5px;
    border-radius: 6px;
    padding: 2px 7px 2px 2.5px;
    font-size: 10.5px;
    line-height: 1;
    max-width: calc(100% - 22px);
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
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
    font-size: 11.5px;
    font-weight: 700;
    font-family: monospace;
    display: flex;
    align-items: center;
    gap: 4.5px;
    line-height: 1.2;
    padding: 1px 1px;
}
.sched-card-time i {
    font-size: 12.5px;
    opacity: 0.9;
}

/* Detail box inside card (Attendance Variance / Planned Hours - Spacious & Clean) */
.sched-card-detail-box {
    background: #ffffff;
    border-radius: 6px;
    padding: 4.5px 7.5px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin-top: 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.sched-detail-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 5px;
    font-size: 9.5px;
    line-height: 1.25;
}

.sched-detail-dot {
    font-size: 7.5px;
    line-height: 1;
    flex-shrink: 0;
}
.dot-regular { color: #10b981; }
.dot-tardiness { color: #f59e0b; }
.dot-no-inout { color: #ef4444; }
.dot-overtime { color: #8b5cf6; }
.dot-planned { color: #059669; }

.sched-detail-punch {
    font-family: monospace;
    font-weight: 700;
    font-size: 10px;
    color: #1e293b;
    white-space: nowrap;
    letter-spacing: -0.2px;
    flex: 1;
}

.sched-detail-badge {
    font-size: 8.5px;
    font-weight: 800;
    padding: 1.5px 5px;
    border-radius: 3.5px;
    text-transform: uppercase;
    letter-spacing: 0.25px;
    white-space: nowrap;
    flex-shrink: 0;
}
.badge-regular { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-tardiness { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-no-inout { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-overtime { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.badge-planned { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

.sched-detail-hours {
    color: #64748b;
    font-size: 9.5px;
    font-weight: 600;
    padding-top: 1px;
}
.sched-detail-hours strong {
    font-family: monospace;
    font-weight: 800;
    font-size: 10px;
}

/* Theme Variations */
/* OPENING - GREEN (Per User Directive) */
.sched-theme-o { border: 1.5px solid #86efac; background: #f0fdf4; }
.sched-theme-o .sched-badge-wrap { background: #dcfce7; border: 1px solid #86efac; }
.sched-theme-o .sched-badge-code { background: #059669; color: #ffffff; box-shadow: 0 1px 2px rgba(5, 150, 105, 0.25); }
.sched-theme-o .sched-badge-name { color: #065f46; }
.sched-theme-o .sched-card-time { color: #047857; }
.sched-theme-o .sched-card-detail-box { border: 1px solid #bbf7d0; background: #ffffff; }
.sched-theme-o .sched-detail-hours strong { color: #047857; }

/* MID DAY - BLUE */
.sched-theme-md { border: 1.5px solid #93c5fd; background: #f0f7ff; }
.sched-theme-md .sched-badge-wrap { background: #dbeafe; border: 1px solid #93c5fd; }
.sched-theme-md .sched-badge-code { background: #2563eb; color: #ffffff; box-shadow: 0 1px 2px rgba(37, 99, 235, 0.25); }
.sched-theme-md .sched-badge-name { color: #1e40af; }
.sched-theme-md .sched-card-time { color: #1d4ed8; }
.sched-theme-md .sched-card-detail-box { border: 1px solid #bfdbfe; background: #ffffff; }
.sched-theme-md .sched-detail-hours strong { color: #1d4ed8; }

/* LATE DAY - AMBER */
.sched-theme-ld { border: 1.5px solid #fcd34d; background: #fffdf5; }
.sched-theme-ld .sched-badge-wrap { background: #fef3c7; border: 1px solid #fcd34d; }
.sched-theme-ld .sched-badge-code { background: #d97706; color: #ffffff; box-shadow: 0 1px 2px rgba(217, 119, 6, 0.25); }
.sched-theme-ld .sched-badge-name { color: #92400e; }
.sched-theme-ld .sched-card-time { color: #b45309; }
.sched-theme-ld .sched-card-detail-box { border: 1px solid #fde68a; background: #ffffff; }
.sched-theme-ld .sched-detail-hours strong { color: #b45309; }

/* CLOSING - PURPLE */
.sched-theme-c { border: 1.5px solid #d8b4fe; background: #faf5ff; }
.sched-theme-c .sched-badge-wrap { background: #f3e8ff; border: 1px solid #d8b4fe; }
.sched-theme-c .sched-badge-code { background: #9333ea; color: #ffffff; box-shadow: 0 1px 2px rgba(147, 51, 234, 0.25); }
.sched-theme-c .sched-badge-name { color: #6b21a8; }
.sched-theme-c .sched-card-time { color: #7e22ce; }
.sched-theme-c .sched-card-detail-box { border: 1px solid #e9d5ff; background: #ffffff; }
.sched-theme-c .sched-detail-hours strong { color: #7e22ce; }

/* RESTDAY - SLATE */
.sched-card-rest { border: 1.5px solid #cbd5e1; background: #f8fafc; }
.sched-card-rest .sched-badge-wrap { background: #e2e8f0; border: 1px solid #cbd5e1; }
.sched-card-rest .sched-badge-code { background: #64748b; color: #ffffff; }
.sched-card-rest .sched-badge-name { color: #334155; }
.sched-card-rest .sched-card-time { color: #64748b; }
.sched-card-rest .sched-card-detail-box { border: 1px solid #e2e8f0; background: #ffffff; }
.sched-card-rest .sched-detail-hours strong { color: #64748b; }

/* CUSTOM - VIOLET */
.sched-card-custom { border: 1.5px solid #c4b5fd; background: #f5f3ff; }
.sched-card-custom .sched-badge-wrap { background: #ede9fe; border: 1px solid #c4b5fd; }
.sched-card-custom .sched-badge-code { background: #7c3aed; color: #ffffff; }
.sched-card-custom .sched-badge-name { color: #5b21b6; }
.sched-card-custom .sched-card-time { color: #6d28d9; }
.sched-card-custom .sched-card-detail-box { border: 1px solid #ddd6fe; background: #ffffff; }
.sched-card-custom .sched-detail-hours strong { color: #6d28d9; }

/* Custom background inline fallback */
.sched-badge-wrap[style*="background"] {
    border: none !important;
}
.sched-badge-wrap[style*="background"] .sched-badge-code {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}
.sched-badge-wrap[style*="background"] .sched-badge-name {
    color: #ffffff !important;
}

/* Floating Cell Custom Dropdown */
.sched-custom-dropdown {
    position: absolute;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.18);
    width: 250px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.sched-custom-dd-header {
    padding: 8px 10px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sched-dd-shift-item {
    width: 100%;
    text-align: left;
    padding: 5px 6px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    border-radius: 6px;
    transition: background 0.1s;
}
.sched-dd-shift-item:hover {
    background: #f1f5f9;
}

.sched-input-time {
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px 6px;
    font-size: 11px;
    font-weight: 700;
    font-family: monospace;
    outline: none;
}
.sched-input-text-sm {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 11px;
    outline: none;
}

.sched-btn-edit-times {
    border: none;
    background: transparent;
    color: #7c3aed;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.sched-btn-edit-times:hover {
    text-decoration: underline;
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
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 4px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    min-width: 220px;
    z-index: 100;
    display: none;
    padding: 4px 0;
}
.sched-row-dropdown.open { display: block; }
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

@push('scripts')
<script>
// -------------------------------------------------------------
// Constants and Client Memory
// -------------------------------------------------------------
const CSRF_TOKEN = '{{ csrf_token() }}';
const QUICK_ASSIGN_URL = '{{ route('hr.attendance.schedules.quick-assign') }}';
const COPY_WEEK_URL = '{{ route('hr.attendance.schedules.copy-week') }}';
const QUICK_FILL_ROW_URL = '{{ route('hr.attendance.schedules.quick-fill-row') }}';
const ALL_EMPLOYEES = @json(isset($allEmployees) ? $allEmployees : $employees);
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
// Floating Cell Custom Schedule Popover
// -------------------------------------------------------------
let activeCellEmpId = null;
let activeCellDate = null;

function openCellCustomDropdown(empId, date) {
    activeCellEmpId = empId;
    activeCellDate = date;

    const cell = document.getElementById(`cell_${empId}_${date}`);
    const dd = document.getElementById('cellCustomDropdown');
    if (!cell || !dd) return;

    const rect = cell.getBoundingClientRect();
    dd.style.display = 'flex';
    dd.style.top = `${rect.bottom + window.scrollY + 4}px`;
    dd.style.left = `${Math.min(rect.left + window.scrollX, window.innerWidth - 270)}px`;
}

function closeCellCustomDropdown() {
    const dd = document.getElementById('cellCustomDropdown');
    if (dd) dd.style.display = 'none';
    activeCellEmpId = null;
    activeCellDate = null;
}

document.addEventListener('click', function(e) {
    const dd = document.getElementById('cellCustomDropdown');
    if (dd && dd.style.display !== 'none') {
        if (!e.target.closest('#cellCustomDropdown') && !e.target.closest('.sched-pill-custom') && !e.target.closest('.sched-card-body') && !e.target.closest('.sched-card-time')) {
            closeCellCustomDropdown();
        }
    }
});

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
    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell) return;

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
function renderAssignedCardHTML(cell, empId, date, code, label, time, themeClass, customColor = null) {
    const colorStyle = customColor ? `style="background: ${customColor}; color: #fff;"` : '';
    const upperCode = (code || '').toUpperCase();
    const upperLabel = (label || '').toUpperCase();
    
    // Ensure Opening shifts are always Green (sched-theme-o)
    let finalTheme = themeClass;
    if (upperCode === 'O' || upperLabel.includes('OPEN') || time.startsWith('07:') || time.startsWith('06:') || time.startsWith('08:') || time.startsWith('10:00')) {
        finalTheme = 'sched-theme-o';
    }

    let detailBoxHTML = '';
    if (upperCode === 'OFF' || upperLabel === 'RESTDAY') {
        detailBoxHTML = `
            <div class="sched-card-detail-box">
                <div class="sched-detail-row">
                    <span class="sched-detail-dot dot-no-inout">●</span>
                    <span class="sched-detail-punch">Rest Day</span>
                    <span class="sched-detail-badge" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;">OFF DUTY</span>
                </div>
                <div class="sched-detail-row sched-detail-hours">
                    <span>Hours:</span>
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
                    <span>Target:</span>
                    <strong>8.00 hrs</strong>
                </div>
            </div>
        `;
    }

    cell.innerHTML = `
        <div class="sched-shift-card ${finalTheme}">
            <div class="sched-card-top">
                <div class="sched-badge-wrap" ${colorStyle}>
                    <span class="sched-badge-code">${escapeHtml(code)}</span>
                    <span class="sched-badge-name">${escapeHtml(label)}</span>
                </div>
                <button type="button" class="sched-card-clear" onclick="clearCellShift(${empId}, '${date}')" title="Clear Shift">
                    &times;
                </button>
            </div>
            <div class="sched-card-time" onclick="openCellCustomDropdown(${empId}, '${date}')">
                <i class="ph ph-clock"></i>
                <span>${escapeHtml(time)}</span>
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

function openShiftMasterModal() {
    activeModalCat = 'default';
    const firstTab = document.querySelector('.sched-cat-tab-btn');
    if (firstTab) {
        document.querySelectorAll('.sched-cat-tab-btn').forEach(b => b.classList.remove('active'));
        firstTab.classList.add('active');
        activeModalCat = firstTab.textContent.trim().toLowerCase();
        document.getElementById('modalActiveCategoryLabel').textContent = firstTab.textContent.trim();
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
                        <span class="sched-cat-pill">
                            <span class="sched-cat-dot"></span>
                            <span class="truncate">${escapeHtml(cat)}</span>
                        </span>
                    </div>
                    <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-map-pin" style="color: #7c3aed; font-size: 11.5px;"></i>
                        <span class="truncate" style="font-weight: 600; color: #475569;">${escapeHtml(branch)}</span>
                    </div>
                </div>
                <div class="sched-row-action-menu" style="position: relative;">
                    <button type="button" class="sched-row-btn" onclick="toggleRowMenu(${emp.id})" title="Quick fill week">
                        <i class="ph ph-lightning"></i>
                    </button>
                    <div class="sched-row-dropdown" id="rowMenu_${emp.id}">
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
        <td style="text-align: center; vertical-align: middle;">
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
    if (!confirm('Clear all employee rows from the current schedule view? (Saved schedules in database are preserved)')) return;
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
    if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

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
function toggleRowMenu(empId) {
    document.querySelectorAll('.sched-row-dropdown').forEach(d => {
        if (d.id !== `rowMenu_${empId}`) d.classList.remove('open');
    });
    const dd = document.getElementById(`rowMenu_${empId}`);
    if (dd) dd.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.sched-row-action-menu')) {
        document.querySelectorAll('.sched-row-dropdown').forEach(d => d.classList.remove('open'));
    }
});

function quickFillRowPreset(empId, shiftCode, restDays) {
    const weekStart = document.getElementById('schedWeekInput').value;
    document.querySelectorAll('.sched-row-dropdown').forEach(d => d.classList.remove('open'));

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
    if (!confirm('Clear all shifts for this employee this week?')) return;
    document.querySelectorAll('.sched-row-dropdown').forEach(d => d.classList.remove('open'));

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
}

// -------------------------------------------------------------
// Copy Previous Week Schedules Handler
// -------------------------------------------------------------
function triggerCopyPreviousWeek() {
    if (!confirm('Copy all shift schedules from the previous week into the current week?')) return;
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
            showSchedToast(data.message);
            setTimeout(() => { window.location.reload(); }, 800);
        } else {
            showSchedToast(data.message || 'No schedules found to copy', false);
        }
    })
    .catch(() => showSchedToast('Error copying previous week schedules', false));
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
</script>
@endpush
@endsection
