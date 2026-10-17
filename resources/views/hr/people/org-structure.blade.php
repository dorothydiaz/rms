@extends('layouts.app')

@section('title', 'Organization Structure - Workforce Hierarchy')

@push('styles')
    @vite('resources/css/modules/hr-org-structure.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-org-structure.js')
@endpush

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openReassignManagerModal('', '', '')">
            <i class="ph ph-user-switch"></i>
            <span>Reassign Manager</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- 1. KPI Statistics Bar -->
<div class="org-kpi-grid">
    <div class="org-kpi-card">
        <div class="org-kpi-icon purple">
            <i class="ph ph-users-three"></i>
        </div>
        <div class="org-kpi-info">
            <span class="org-kpi-val">{{ $stats['total'] ?? $employees->count() }}</span>
            <span class="org-kpi-label">Total Workforce</span>
        </div>
    </div>

    <div class="org-kpi-card">
        <div class="org-kpi-icon blue">
            <i class="ph ph-crown"></i>
        </div>
        <div class="org-kpi-info">
            <span class="org-kpi-val">{{ $stats['top_executives'] ?? 1 }}</span>
            <span class="org-kpi-label">Summit Leader (Apex)</span>
        </div>
    </div>

    <div class="org-kpi-card">
        <div class="org-kpi-icon green">
            <i class="ph ph-buildings"></i>
        </div>
        <div class="org-kpi-info">
            <span class="org-kpi-val">{{ $stats['department_heads'] ?? $departments->filter(fn($d) => !empty($d->head_employee_id))->count() }}</span>
            <span class="org-kpi-label">Department Heads</span>
        </div>
    </div>

    <div class="org-kpi-card">
        <div class="org-kpi-icon {{ ($stats['unassigned'] ?? 0) > 0 ? 'amber' : 'green' }}">
            <i class="ph {{ ($stats['unassigned'] ?? 0) > 0 ? 'ph-warning-circle' : 'ph-check-circle' }}"></i>
        </div>
        <div class="org-kpi-info">
            <span class="org-kpi-val">{{ $stats['unassigned'] ?? 0 }}</span>
            <span class="org-kpi-label">Unassigned Staff</span>
        </div>
    </div>
</div>

<!-- 2. Action Toolbar -->
<div class="org-toolbar-card">
    <div class="org-toolbar-left">
        <!-- Search -->
        <div class="org-search-box">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="orgSearchInput" class="org-search-input" placeholder="Search staff, position, or dept...">
        </div>

        <!-- Department Filter -->
        <select id="orgDeptFilter" class="org-select-filter">
            <option value="">All Departments ({{ $departments->count() }})</option>
            @foreach($departments as $dept)
                <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
            @endforeach
        </select>

        <!-- Branch Filter -->
        <select id="orgBranchFilter" class="org-select-filter">
            <option value="">All Branches ({{ $branches->count() }})</option>
            @foreach($branches as $br)
                <option value="{{ strtolower($br->name) }}">{{ $br->name }}</option>
            @endforeach
        </select>

        <!-- Company Filter -->
        <select id="orgCompanyFilter" class="org-select-filter">
            <option value="">All Companies ({{ $companies->count() }})</option>
            @foreach($companies as $company)
                <option value="{{ strtolower($company) }}">{{ $company }}</option>
            @endforeach
        </select>
    </div>

    <div class="org-toolbar-right">
        <!-- View Toggle -->
        <div class="org-view-toggle">
            <button type="button" class="org-view-btn active" data-view="tree" title="Interactive Hierarchy Tree">
                <i class="ph ph-tree-structure"></i>
                <span>Tree View</span>
            </button>
            <button type="button" class="org-view-btn" data-view="grid" title="Department Matrix Grid">
                <i class="ph ph-grid-four"></i>
                <span>Department Grid</span>
            </button>
            <button type="button" class="org-view-btn" data-view="table" title="Hierarchical Table Directory">
                <i class="ph ph-list-dashes"></i>
                <span>Table Directory</span>
            </button>
        </div>

        <!-- Zoom Controls (For Tree View) -->
        <div class="org-zoom-group" id="orgZoomControls">
            <button type="button" class="org-zoom-btn" id="orgZoomOutBtn" title="Zoom Out">
                <i class="ph ph-minus"></i>
            </button>
            <span class="org-zoom-level" id="orgZoomLevel">100%</span>
            <button type="button" class="org-zoom-btn" id="orgZoomInBtn" title="Zoom In">
                <i class="ph ph-plus"></i>
            </button>
            <button type="button" class="org-zoom-btn" id="orgZoomResetBtn" title="Reset View">
                <i class="ph ph-arrows-counter-clockwise"></i>
            </button>
            <button type="button" class="org-zoom-btn" id="orgZoomFitBtn" title="Fit to Screen">
                <i class="ph ph-corners-out"></i>
            </button>
        </div>
    </div>
</div>

<!-- 3. VIEW 1: Interactive Hierarchy Tree View -->
<div id="orgTreeView" class="org-view-container">
    <div id="orgCanvasViewport" class="org-canvas-viewport">
        <div id="orgCanvasContent" class="org-canvas-content">
            @php
                $rootEmployees = $employees->filter(fn($e) => $e->isTopLevelLeader());
                if ($rootEmployees->isEmpty() && $employees->isNotEmpty()) {
                    $rootEmployees = collect([$employees->firstWhere('id', 1) ?: $employees->first()]);
                }
                $deptHeadsMap = $departments->pluck('head_employee_id', 'id')->toArray();
            @endphp

            <div class="org-tree">
                <ul>
                    @foreach($rootEmployees as $root)
                        @include('hr.people.partials.org-tree-node', ['node' => $root, 'deptHeadsMap' => $deptHeadsMap])
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Floating Drawer for Unassigned Employees (if any) -->
        @php
            $unassignedList = $employees->filter(fn($e) => $e->isUnassigned());
        @endphp
        @if($unassignedList->isNotEmpty())
            <div class="org-unassigned-drawer">
                <div class="org-unassigned-header">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-warning-circle"></i>
                        <span>Unassigned Staff ({{ $unassignedList->count() }})</span>
                    </div>
                </div>
                <p style="font-size: 11px; color: #64748b; margin-bottom: 8px;">
                    These staff members have no assigned line manager.
                </p>
                <div style="max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                    @foreach($unassignedList as $unassigned)
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #fffbeb; padding: 4px 8px; border-radius: 6px; font-size: 11.5px;">
                            <span><strong>{{ $unassigned->full_name }}</strong> ({{ $unassigned->position?->name ?? 'Staff' }})</span>
                            <button type="button" class="hr-btn hr-btn-secondary" style="padding: 2px 6px; font-size: 10.5px;" onclick="openReassignManagerModal({{ $unassigned->id }}, '{{ addslashes($unassigned->full_name) }}', '')">
                                Assign
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<!-- 4. VIEW 2: Department Matrix Grid View -->
<div id="orgGridView" class="org-grid-view" style="display: none;">
    @foreach($departments as $dept)
        <div class="org-grid-dept-box" data-dept-name="{{ $dept->name }}">
            <div class="org-grid-dept-header">
                <div class="org-grid-dept-title">
                    <i class="ph ph-buildings" style="color: #7c3aed;"></i>
                    <span>{{ $dept->name }}</span>
                </div>
                <button type="button" class="hr-badge hr-badge-purple" style="cursor: pointer;" onclick="openSetDeptHeadModal({{ $dept->id }}, '{{ addslashes($dept->name) }}', {{ $dept->head_employee_id ?? 'null' }})">
                    Head: {{ $dept->head?->full_name ?? 'Set Head' }}
                </button>
            </div>
            <div class="org-grid-dept-body">
                @php
                    $deptStaff = $employees->filter(fn($e) => (int)$e->department_id === (int)$dept->id || in_array((int)$dept->id, $e->all_department_ids ?? []));
                @endphp
                @forelse($deptStaff as $staff)
                    <div class="org-grid-staff-item" 
                         data-name="{{ $staff->full_name }}" 
                         data-department="{{ strtolower($dept->name) }}" 
                         data-branch="{{ strtolower($staff->branch?->name ?? '') }}" 
                         data-company="{{ strtolower($staff->company_or_agency ?? '') }}">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($staff->photo_url)
                                <img src="{{ $staff->photo_url }}" class="org-card-avatar" style="width: 30px; height: 30px;" alt="{{ $staff->full_name }}">
                            @else
                                <div class="org-card-initials" style="width: 30px; height: 30px; font-size: 11px;">{{ $staff->initials }}</div>
                            @endif
                            <div>
                                <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;">{{ $staff->full_name }}</div>
                                <div style="font-size: 11px; color: #64748b;">
                                    {{ $staff->position?->name ?? 'Staff' }} • <span style="color: #7c3aed; font-weight: 500;">{{ $staff->company_or_agency ?? 'Company' }}</span> • Reports to: <strong>{{ $staff->supervisor?->full_name ?? 'Top Leader' }}</strong>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="hr-btn hr-btn-secondary" style="padding: 3px 8px; font-size: 11px;" onclick="openReassignManagerModal({{ $staff->id }}, '{{ addslashes($staff->full_name) }}', {{ $staff->supervisor_id ?? 'null' }})">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                    </div>
                @empty
                    <div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 12px;">No staff assigned to this department.</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

<!-- 5. VIEW 3: Hierarchical Table Directory -->
<div id="orgTableView" class="org-table-view" style="display: none;">
    <table class="org-dir-table">
        <thead>
            <tr>
                <th>Employee / Position</th>
                <th>Department</th>
                <th>Company / Agency</th>
                <th>Branch</th>
                <th>Immediate Manager</th>
                <th>Level</th>
                <th>Subordinates</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
                @php
                    $isSummit = $emp->isTopLevelLeader();
                    $isDeptHead = $departments->pluck('head_employee_id')->contains($emp->id);
                    $subCount = $emp->subordinates->count();
                @endphp
                <tr class="org-dir-row" 
                    data-name="{{ $emp->full_name }}" 
                    data-department="{{ strtolower($emp->department?->name ?? '') }}" 
                    data-branch="{{ strtolower($emp->branch?->name ?? '') }}"
                    data-company="{{ strtolower($emp->company_or_agency ?? '') }}">
                    <td>
                        <div class="org-table-name-cell">
                            @if($emp->photo_url)
                                <img src="{{ $emp->photo_url }}" class="org-card-avatar" style="width: 28px; height: 28px;" alt="{{ $emp->full_name }}">
                            @else
                                <div class="org-card-initials" style="width: 28px; height: 28px; font-size: 10px;">{{ $emp->initials }}</div>
                            @endif
                            <div>
                                <a href="{{ route('hr.people.employees.show', $emp->id) }}" style="font-weight: 700; color: #1e293b; text-decoration: none;">{{ $emp->full_name }}</a>
                                <div style="font-size: 11px; color: #64748b;">{{ $emp->position?->name ?? 'Staff' }} ({{ $emp->employee_code ?? "EMP-{$emp->id}" }})</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="org-pill {{ str_contains(strtolower($emp->department?->name ?? ''), 'management') ? 'dept-mgmt' : (str_contains(strtolower($emp->department?->name ?? ''), 'front') ? 'dept-foh' : (str_contains(strtolower($emp->department?->name ?? ''), 'back') ? 'dept-boh' : 'dept-fin')) }}">
                            {{ $emp->department?->name ?? 'General' }}
                        </span>
                    </td>
                    <td>
                        <span class="org-pill company">{{ $emp->company_or_agency ?? 'Direct' }}</span>
                    </td>
                    <td>
                        <span class="org-pill branch">{{ $emp->branch?->name ?? 'Main Branch' }}</span>
                    </td>
                    <td>
                        @if($emp->supervisor)
                            <span style="font-weight: 600; color: #334155;">{{ $emp->supervisor->full_name }}</span>
                        @elseif($isSummit)
                            <span class="org-badge-role summit"><i class="ph ph-crown"></i> Summit Apex</span>
                        @else
                            <span style="color: #d97706; font-style: italic;">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        @if($isSummit)
                            <span class="hr-badge hr-badge-purple">Level 1 (Top)</span>
                        @elseif($isDeptHead)
                            <span class="hr-badge hr-badge-green">Level 2 (Dept Head)</span>
                        @elseif($subCount > 0)
                            <span class="hr-badge hr-badge-warning">Level 3 (Lead)</span>
                        @else
                            <span class="hr-badge hr-badge-secondary">Level 4 (Staff)</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $subCount }}</strong> {{ Str::plural('report', $subCount) }}
                    </td>
                    <td style="text-align: right;">
                        <button type="button" class="hr-btn hr-btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="openReassignManagerModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', {{ $emp->supervisor_id ?? 'null' }})">
                            <i class="ph ph-user-switch"></i> Change Manager
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: Reassign Manager Modal                                           -->
<!-- ========================================================================= -->
<div id="reassignManagerModal" class="hr-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 100%; max-width: 480px; border-radius: 14px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">
        <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="ph ph-user-switch"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Reassign Direct Manager</h3>
                    <p style="margin: 0; font-size: 11.5px; color: #64748b;">Update line of command and sequential approval hierarchy</p>
                </div>
            </div>
            <button type="button" onclick="closeReassignManagerModal()" style="border: none; background: transparent; font-size: 18px; color: #94a3b8; cursor: pointer;">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <form onsubmit="submitReassignManager(event)" style="padding: 20px;">
            <input type="hidden" id="reassign_emp_id" name="employee_id">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Staff Member</label>
                <div style="padding: 8px 12px; background: #f1f5f9; border-radius: 8px; font-size: 13.5px; font-weight: 700; color: #0f172a;" id="reassign_emp_name">
                    Loading...
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="reassign_supervisor_select" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                    Reports To (Direct Immediate Manager) *
                </label>
                <select id="reassign_supervisor_select" name="supervisor_id" class="hr-select" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 13px;" required>
                    <option value="">-- Set as Top of Organization (No Supervisor) --</option>
                    @foreach($employees as $mgr)
                        <option value="{{ $mgr->id }}" data-name="{{ $mgr->full_name }}">
                            {{ $mgr->full_name }} — {{ $mgr->position?->name ?? 'Staff' }} ({{ $mgr->department?->name ?? 'General' }})
                        </option>
                    @endforeach
                </select>
                <p style="margin: 6px 0 0; font-size: 11px; color: #64748b;">
                    <i class="ph ph-shield-check" style="color: #10b981;"></i> Circular loops are blocked: An employee cannot report to their subordinate.
                </p>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeReassignManagerModal()">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: Set Department Head Modal                                        -->
<!-- ========================================================================= -->
<div id="setDeptHeadModal" class="hr-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 100%; max-width: 450px; border-radius: 14px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;">
        <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                    <i class="ph ph-buildings"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Appoint Department Head</h3>
                    <p style="margin: 0; font-size: 11.5px; color: #64748b;">Designates official leader for departmental budget approvals</p>
                </div>
            </div>
            <button type="button" onclick="closeSetDeptHeadModal()" style="border: none; background: transparent; font-size: 18px; color: #94a3b8; cursor: pointer;">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <form onsubmit="submitSetDeptHead(event)" style="padding: 20px;">
            <input type="hidden" id="dept_head_dept_id">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Department</label>
                <div style="padding: 8px 12px; background: #f1f5f9; border-radius: 8px; font-size: 13.5px; font-weight: 700; color: #0f172a;" id="dept_head_dept_name">
                    Loading...
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="dept_head_select" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                    Appointed Department Head
                </label>
                <select id="dept_head_select" name="head_employee_id" class="hr-select" style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 13px;">
                    <option value="">-- No Department Head (Unassigned) --</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">
                            {{ $e->full_name }} — {{ $e->position?->name ?? 'Staff' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeSetDeptHeadModal()">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Department Head</button>
            </div>
        </form>
    </div>
</div>
@endsection
