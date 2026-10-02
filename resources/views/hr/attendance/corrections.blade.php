@extends('layouts.app')

@section('title', 'Manual Time Entries - Time & Attendance Management')

@push('styles')
<style>
/* Responsive Manual Time Entries Matrix */
.hr-corrections-table {
    width: 100% !important;
    min-width: 980px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}

.hr-corrections-table th,
.hr-corrections-table td {
    padding: 9px 6px !important;
    font-size: 13px;
    vertical-align: middle;
}

.hr-corrections-table th {
    font-size: 11.5px !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    background: #f8fafc !important;
}

.hr-corrections-table .cor-col-date {
    width: 95px;
}

.hr-corrections-table .cor-col-staff {
    width: 140px;
}

.hr-corrections-table .cor-col-dept {
    width: 125px;
}

.hr-corrections-table .cor-col-punch-th {
    text-align: center !important;
    padding: 9px 2px !important;
    font-size: 11px !important;
    color: #475569;
    font-weight: 700;
    white-space: nowrap;
    width: 54px;
}

.hr-corrections-table .cor-col-punch-td {
    text-align: center !important;
    padding: 9px 2px !important;
    white-space: nowrap;
    font-size: 12.5px;
    width: 54px;
}

.hr-corrections-table .cor-col-hours {
    text-align: center !important;
    white-space: nowrap;
    width: 76px;
}

.hr-corrections-table .cor-col-status {
    text-align: center !important;
    white-space: nowrap;
    width: 82px;
}

.hr-corrections-table .cor-col-notes {
    width: 120px;
}

.hr-corrections-table .cor-col-action {
    text-align: center !important;
    white-space: nowrap;
    width: 74px;
    padding: 6px 2px !important;
}
</style>
@endpush

@section('content')
<x-hr-tabs parent="time-attendance">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openEncodeModal()">
            <i class="ph ph-plus-circle"></i>
            <span>Encode Time Entry</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Filter & Action Toolbar -->
<div class="hr-filter-bar" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
        <!-- Real-Time Text Search -->
        <div style="position: relative; width: 250px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
            <input type="text" id="manualSearchInput" class="hr-input" placeholder="Search staff, ID, branch, notes..." oninput="filterManualTable()" style="width: 100%; box-sizing: border-box; padding-left: 36px; height: 38px;">
        </div>

        <!-- Chosen Date Filter -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <input type="date" id="manualDateFilter" class="hr-input" value="{{ request('date') }}" onchange="applyServerDateFilter(this.value)" style="height: 38px; width: 160px;" title="Filter by specific chosen date">
            @if(request('date'))
                <a href="{{ route('hr.attendance.corrections') }}" class="hr-btn hr-btn-secondary" style="height: 38px; padding: 0 10px;" title="Clear date filter">
                    <i class="ph ph-x"></i> Clear
                </a>
            @endif
        </div>

        <!-- Branch Filter -->
        <select id="manualBranchFilter" class="hr-select" style="height: 38px; max-width: 170px;" onchange="filterManualTable()">
            <option value="">-- All Branches --</option>
            @foreach($branches as $b)
                <option value="{{ $b->name }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>

        <!-- Status Filter -->
        <select id="manualStatusFilter" class="hr-select" style="height: 38px; max-width: 150px;" onchange="filterManualTable()">
            <option value="">-- All Statuses --</option>
            <option value="Present">Present</option>
            <option value="Late">Late</option>
            <option value="Half Day">Half Day</option>
            <option value="Rest Day">Rest Day</option>
            <option value="Overtime">Overtime</option>
        </select>

        <!-- Quick Reset -->
        <button type="button" class="hr-btn hr-btn-secondary" style="height: 38px;" onclick="resetManualFilters()">
            <i class="ph ph-arrow-counter-clockwise"></i> Reset
        </button>
    </div>

    <div style="flex-shrink: 0;">
        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 8px; background: #f1f5f9; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
            Showing <strong id="visibleCount" style="color: #0f172a;">{{ $records->count() }}</strong> entries
        </span>
    </div>
</div>

<!-- Manual Time Entries Table -->
<div class="hr-table-card" style="max-width: 100%; overflow: hidden;">
    <div class="hr-table-wrapper" style="max-height: 600px; overflow-y: auto; overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch;">
        <table class="hr-table hr-corrections-table" id="manualEntriesTable">
            <thead>
                <tr>
                    <th class="cor-col-date">Date</th>
                    <th class="cor-col-staff">Staff Member</th>
                    <th class="cor-col-dept">Branch & Dept</th>
                    <th class="cor-col-punch-th">1. In</th>
                    <th class="cor-col-punch-th">2. Break Out</th>
                    <th class="cor-col-punch-th">3. Break In</th>
                    <th class="cor-col-punch-th">4. Coffee Out</th>
                    <th class="cor-col-punch-th">5. Coffee In</th>
                    <th class="cor-col-punch-th">6. Final Out</th>
                    <th class="cor-col-hours">Total Hours</th>
                    <th class="cor-col-status">Status</th>
                    <th class="cor-col-notes">Notes / Purpose</th>
                    <th class="cor-col-action" style="text-align: center !important;">Action</th>
                </tr>
            </thead>
            <tbody id="manualEntriesTableBody">
                @forelse($records as $r)
                    @php
                        $pIn = $r->time_in ?? $r->in_1;
                        $pBreakOut = $r->break_out ?? $r->out_1;
                        $pBreakIn = $r->break_in ?? $r->in_2;
                        $pCoffeeOut = $r->coffee_break_out ?? $r->out_2;
                        $pCoffeeIn = $r->coffee_break_in ?? $r->in_3;
                        $pFinalOut = $r->time_out ?? $r->out_3;

                        $empName = $r->employee?->full_name ?? 'Unknown Staff';
                        $empId = $r->employee?->employee_id ?? '';
                        $branchName = $r->employee?->branch?->name ?? 'Unassigned';
                        $deptName = $r->employee?->department?->name ?? '';
                        $posName = $r->employee?->position?->name ?? '';
                        $dateStr = \Carbon\Carbon::parse($r->date)->format('Y-m-d');
                        $displayDate = \Carbon\Carbon::parse($r->date)->format('M d, Y');

                        $punchesData = [
                            'time_in' => $pIn ? substr($pIn, 0, 5) : '',
                            'break_out' => $pBreakOut ? substr($pBreakOut, 0, 5) : '',
                            'break_in' => $pBreakIn ? substr($pBreakIn, 0, 5) : '',
                            'coffee_break_out' => $pCoffeeOut ? substr($pCoffeeOut, 0, 5) : '',
                            'coffee_break_in' => $pCoffeeIn ? substr($pCoffeeIn, 0, 5) : '',
                            'time_out' => $pFinalOut ? substr($pFinalOut, 0, 5) : '',
                            'status' => $r->status,
                            'notes' => $r->notes ?? '',
                        ];
                    @endphp
                    <tr class="manual-row"
                        data-search="{{ strtolower($empName . ' ' . $empId . ' ' . $branchName . ' ' . $deptName . ' ' . ($r->notes ?? '')) }}"
                        data-date="{{ $dateStr }}"
                        data-branch="{{ $branchName }}"
                        data-status="{{ $r->status }}">
                        <td class="cor-col-date">
                            <span style="color: #0f172a; font-weight: 600; font-size: 12.5px;">{{ $displayDate }}</span>
                            <div style="font-size: 11px; color: #64748b; font-weight: 400;">{{ \Carbon\Carbon::parse($r->date)->format('l') }}</div>
                        </td>
                        <td class="cor-col-staff">
                            <div style="font-weight: 600; color: #0f172a; font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $empName }}">{{ $empName }}</div>
                            <div style="font-size: 11px; color: #64748b; font-family: monospace; font-weight: 400; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $empId }} &bull; {{ $posName }}</div>
                        </td>
                        <td class="cor-col-dept">
                            <div style="font-weight: 500; color: #1e293b; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 3px;" title="{{ $branchName }}">
                                <i class="ph ph-storefront" style="color: #7c3aed; font-size: 12px;"></i> {{ $branchName }}
                            </div>
                            @php
                                $deptUpper = strtoupper(trim($deptName));
                                $pillClass = 'hr-dept-pill-neutral';
                                $pillIcon = 'ph-buildings';
                                if (str_contains($deptUpper, 'HR') || str_contains($deptUpper, 'HUMAN')) {
                                    $pillClass = 'hr-dept-pill-hr';
                                    $pillIcon = 'ph-users';
                                } elseif (str_contains($deptUpper, 'IT') || str_contains($deptUpper, 'TECH')) {
                                    $pillClass = 'hr-dept-pill-it';
                                    $pillIcon = 'ph-cpu';
                                } elseif (str_contains($deptUpper, 'FINANCE') || str_contains($deptUpper, 'FIN') || str_contains($deptUpper, 'ADMIN')) {
                                    $pillClass = 'hr-dept-pill-fin';
                                    $pillIcon = 'ph-coins';
                                } elseif (str_contains($deptUpper, 'FRONT') || str_contains($deptUpper, 'FOH')) {
                                    $pillClass = 'hr-dept-pill-foh';
                                    $pillIcon = 'ph-storefront';
                                } elseif (str_contains($deptUpper, 'BACK') || str_contains($deptUpper, 'BOH') || str_contains($deptUpper, 'KITCHEN')) {
                                    $pillClass = 'hr-dept-pill-boh';
                                    $pillIcon = 'ph-cooking-pot';
                                } elseif (str_contains($deptUpper, 'MANAGE')) {
                                    $pillClass = 'hr-dept-pill-mgmt';
                                    $pillIcon = 'ph-briefcase';
                                }
                            @endphp
                            <span class="hr-dept-pill {{ $pillClass }}" style="font-size: 10px; padding: 2px 7px; font-weight: 500;" title="{{ $deptName }}">
                                <i class="ph {{ $pillIcon }}"></i>
                                <span>{{ $deptName ?: 'General' }}</span>
                            </span>
                        </td>

                        <!-- 1. In -->
                        <td class="cor-col-punch-td">
                            @if($pIn)
                                <span style="color: #059669; font-family: monospace; font-size: 12.5px; font-weight: 600;">{{ substr($pIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace; font-weight: 400; font-size: 11.5px;">--:--</span>
                            @endif
                        </td>

                        <!-- 2. Break Out -->
                        <td class="cor-col-punch-td">
                            @if($pBreakOut)
                                <span style="color: #475569; font-family: monospace; font-size: 12px; font-weight: 500;">{{ substr($pBreakOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace; font-weight: 400; font-size: 11.5px;">--:--</span>
                            @endif
                        </td>

                        <!-- 3. Break In -->
                        <td class="cor-col-punch-td">
                            @if($pBreakIn)
                                <span style="color: #475569; font-family: monospace; font-size: 12px; font-weight: 500;">{{ substr($pBreakIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace; font-weight: 400; font-size: 11.5px;">--:--</span>
                            @endif
                        </td>

                        <!-- 4. Coffee Out -->
                        <td class="cor-col-punch-td">
                            @if($pCoffeeOut)
                                <span style="color: #b45309; font-family: monospace; font-size: 12px; font-weight: 500;">{{ substr($pCoffeeOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace; font-weight: 400; font-size: 11.5px;">--:--</span>
                            @endif
                        </td>

                        <!-- 5. Coffee In -->
                        <td class="cor-col-punch-td">
                            @if($pCoffeeIn)
                                <span style="color: #b45309; font-family: monospace; font-size: 12px; font-weight: 500;">{{ substr($pCoffeeIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace; font-weight: 400; font-size: 11.5px;">--:--</span>
                            @endif
                        </td>

                        <!-- 6. Final Out -->
                        <td class="cor-col-punch-td">
                            @if($pFinalOut)
                                <span style="color: #0284c7; font-family: monospace; font-size: 12.5px; font-weight: 600;">{{ substr($pFinalOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace; font-weight: 400; font-size: 11.5px;">--:--</span>
                            @endif
                        </td>

                        <!-- Total Hours -->
                        <td class="cor-col-hours">
                            <span style="color: #0f172a; font-weight: 600; font-size: 12.5px;">{{ number_format($r->total_hours ?? 0, 2) }} hrs</span>
                        </td>

                        <!-- Status Badge -->
                        <td class="cor-col-status">
                            @if($r->status === 'Present')
                                <span class="hr-badge hr-badge-success" style="font-size: 11px; padding: 3px 7px;">{{ $r->status }}</span>
                            @elseif($r->status === 'Late')
                                <span class="hr-badge hr-badge-warning" style="font-size: 11px; padding: 3px 7px;">{{ $r->status }}</span>
                            @elseif($r->status === 'Rest Day')
                                <span class="hr-badge hr-badge-neutral" style="font-size: 11px; padding: 3px 7px;">{{ $r->status }}</span>
                            @elseif($r->status === 'Overtime')
                                <span class="hr-badge hr-badge-purple" style="font-size: 11px; padding: 3px 7px;">{{ $r->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-danger" style="font-size: 11px; padding: 3px 7px;">{{ $r->status }}</span>
                            @endif
                        </td>

                        <!-- Notes & Source -->
                        <td class="cor-col-notes">
                            <div style="font-size: 12px; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $r->notes ?: 'Manual time entry' }}">{{ $r->notes ?: 'Manual time entry' }}</div>
                        </td>

                        <!-- Action Buttons -->
                        <td class="cor-col-action">
                            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                                <button type="button" class="hr-btn hr-btn-secondary" style="width: 32px; height: 32px; min-width: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; color: #475569;" onclick='editManualEntry({{ $r->id }}, {{ $r->employee_id }}, "{{ addslashes($empName) }}", "{{ $dateStr }}", {{ json_encode($punchesData) }})' title="Edit punches">
                                    <i class="ph ph-pencil-simple" style="font-size: 16px;"></i>
                                </button>
                                <form method="POST" action="{{ route('hr.attendance.corrections.destroy', $r->id) }}" onsubmit="return confirm('Are you sure you want to delete this manual time entry for {{ addslashes($empName) }} on {{ $displayDate }}?');" style="display: inline-block; margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hr-btn hr-btn-danger" style="width: 32px; height: 32px; min-width: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;" title="Delete manual entry">
                                        <i class="ph ph-trash" style="font-size: 16px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyTableRow">
                        <td colspan="13" style="text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="ph ph-calendar-plus" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                            No manual time entries recorded for this filter.<br>
                            Click <strong>"Encode Time Entry"</strong> to freely log time punches for any employee and date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->total() > 0)
        <div class="hr-table-footer">
            {{ $records->links() }}
        </div>
    @endif
</div>

<!-- ======================================================== -->
<!-- MODAL: Encode Manual Time Entry (Freely on Chosen Date)   -->
<!-- ======================================================== -->
<div id="encodeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px; background: #ffffff !important; border-left: 3px solid #7c3aed; box-shadow: -10px 0 35px rgba(0, 0, 0, 0.15);">
        <div class="hr-modal-header" style="background: #ffffff !important; border-bottom: 1.5px solid #e2e8f0; padding: 18px 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 9px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="ph ph-clock-counter-clockwise"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" id="modalTitle" style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Encode Manual Time Entry</span>
                        <div style="font-size: 12px; color: #64748b;">Freely record, adjust, and log official time punches for any chosen date</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('encodeModal')" style="font-size: 18px;"><i class="ph ph-x"></i></button>
            </div>
        </div>

        <form method="POST" action="{{ route('hr.attendance.corrections.store') }}" id="manualEntryForm" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1 1 auto; height: 100%; min-height: 0; margin: 0;">
            @csrf
            <div class="hr-modal-body" style="padding: 20px 24px; background: #ffffff !important; flex: 1 1 auto; overflow-y: auto; min-height: 0;">
                
                <!-- Step 1: Staff Member Selection -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-user"></i> 1. Select Staff Member
                    </div>

                    <div class="hr-form-group" style="margin-bottom: 0;">
                        <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #0f172a;">Staff Member *</label>
                        <select name="employee_id" id="encEmployeeId" class="hr-select" required onchange="lookupPunchesForDate()" style="height: 38px; width: 100%;">
                            <option value="">-- Select Employee --</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_id }}) - {{ $e->branch?->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hidden sync date input -->
                    <input type="hidden" name="date" id="encDate" value="{{ date('Y-m-d') }}">

                    <!-- Live Date/Record Lookup Status Alert -->
                    <div id="lookupAlert" style="display: none; margin-top: 12px; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;"></div>
                </div>

                <!-- Step 2: Time Punches Encoding Grid (Date & Time in All Fields) -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <i class="ph ph-fingerprint"></i> 2. Encode Time Punches (Date & Time Format)
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 3px 8px; height: auto;" onclick="applyStandardShiftPreset()">
                                <i class="ph ph-clock"></i> 8-Hr Preset (08:00 - 17:00)
                            </button>
                            <button type="button" class="hr-btn hr-btn-secondary" style="font-size: 11px; padding: 3px 8px; height: auto;" onclick="clearAllPunches()">
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- 6-Punch Responsive Grid with Date & Time in All Fields -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <!-- 1. Shift In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 500; color: #059669; display: flex; align-items: center; justify-content: space-between;">
                                <span>1. Shift In</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 500;" onclick="setNowPunch('encTimeIn')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="time_in" id="encTimeIn" class="hr-input enc-punch-input" onchange="syncDateFromPunches(); lookupPunchesForDate();" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 2. Break Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 500; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                                <span>2. Break Out</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 500;" onclick="setNowPunch('encBreakOut')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="break_out" id="encBreakOut" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 3. Break In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 500; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                                <span>3. Break In</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 500;" onclick="setNowPunch('encBreakIn')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="break_in" id="encBreakIn" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 4. Coffee Break Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 500; color: #b45309; display: flex; align-items: center; justify-content: space-between;">
                                <span>4. Coffee Out</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 500;" onclick="setNowPunch('encCoffeeOut')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="coffee_break_out" id="encCoffeeOut" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 5. Coffee Break In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 500; color: #b45309; display: flex; align-items: center; justify-content: space-between;">
                                <span>5. Coffee In</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 500;" onclick="setNowPunch('encCoffeeIn')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="coffee_break_in" id="encCoffeeIn" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 6. Final Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 500; color: #0284c7; display: flex; align-items: center; justify-content: space-between;">
                                <span>6. Final Out</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 500;" onclick="setNowPunch('encTimeOut')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="time_out" id="encTimeOut" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Status, Notes & Reason -->
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 16px;">
                    <div class="hr-form-group" style="margin-bottom: 0;">
                        <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #0f172a;">Status Override</label>
                        <select name="status" id="encStatus" class="hr-select" style="height: 38px;">
                            <option value="Auto">Auto-Compute from Punches</option>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Rest Day">Rest Day</option>
                            <option value="Overtime">Overtime</option>
                            <option value="Absent">Absent</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>

                    <div class="hr-form-group" style="margin-bottom: 0;">
                        <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #0f172a;">Purpose / Notes / Justification</label>
                        <input type="text" name="notes" id="encNotes" class="hr-input" placeholder="e.g. Biometric system offline, manager authorized manual log, offsite catering..." style="height: 38px;">
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 0;">
                    <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #0f172a;">Supporting Document (Optional - timesheet scan / photo)</label>
                    <input type="file" name="supporting_document" class="hr-input" accept=".pdf,.png,.jpg,.jpeg">
                </div>

            </div>
            <div class="hr-modal-footer" style="background: #ffffff !important; border-top: 1.5px solid #e2e8f0; padding: 18px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin-top: auto; flex-shrink: 0; position: sticky; bottom: 0; z-index: 10;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('encodeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-check-circle"></i> Save Manual Entry
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); }
};

window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); }
};

// Open New Manual Entry Modal
function openEncodeModal() {
    document.getElementById('modalTitle').innerText = 'Encode Manual Time Entry';
    document.getElementById('encEmployeeId').value = '';
    const today = new Date().toISOString().slice(0, 10);
    const encDate = document.getElementById('encDate');
    if (encDate) encDate.value = today;
    clearAllPunches();
    document.getElementById('encStatus').value = 'Auto';
    document.getElementById('encNotes').value = '';
    hideLookupAlert();
    openModal('encodeModal');
}

function getActivePunchDate() {
    const ids = ['encTimeIn', 'encTimeOut', 'encBreakOut', 'encBreakIn', 'encCoffeeOut', 'encCoffeeIn'];
    for (const id of ids) {
        let val = document.getElementById(id)?.value;
        if (val) {
            if (/^00([0-9]{2})-/.test(val)) {
                val = val.replace(/^00([0-9]{2})-/, '20$1-');
            }
            if (val.includes('T')) {
                return val.split('T')[0];
            }
            if (val.includes('-') && val.length >= 10) {
                return val.substring(0, 10);
            }
        }
    }
    let encDate = document.getElementById('encDate')?.value;
    if (encDate) {
        if (/^00([0-9]{2})-/.test(encDate)) {
            encDate = encDate.replace(/^00([0-9]{2})-/, '20$1-');
        }
        return encDate;
    }
    return new Date().toISOString().slice(0, 10);
}

function syncDateFromPunches() {
    const d = getActivePunchDate();
    const encDate = document.getElementById('encDate');
    if (encDate && d) {
        encDate.value = d;
    }
}

function toDateTimeInputValue(dateStr, timeStr) {
    if (!timeStr) return '';
    if (timeStr.includes('T')) return timeStr.substring(0, 16);
    if (timeStr.includes(' ') && timeStr.length >= 16) {
        const parts = timeStr.split(' ');
        return `${parts[0]}T${parts[1].substring(0, 5)}`;
    }
    const baseDate = dateStr || getActivePunchDate();
    const cleanTime = timeStr.substring(0, 5);
    return `${baseDate}T${cleanTime}`;
}

// Edit existing manual entry
function editManualEntry(recordId, empId, empName, dateStr, punches) {
    document.getElementById('modalTitle').innerText = 'Edit Manual Time Entry - ' + empName;
    document.getElementById('encEmployeeId').value = empId;
    const encDate = document.getElementById('encDate');
    if (encDate) encDate.value = dateStr;

    document.getElementById('encTimeIn').value = toDateTimeInputValue(dateStr, punches.time_in);
    document.getElementById('encBreakOut').value = toDateTimeInputValue(dateStr, punches.break_out);
    document.getElementById('encBreakIn').value = toDateTimeInputValue(dateStr, punches.break_in);
    document.getElementById('encCoffeeOut').value = toDateTimeInputValue(dateStr, punches.coffee_break_out);
    document.getElementById('encCoffeeIn').value = toDateTimeInputValue(dateStr, punches.coffee_break_in);
    document.getElementById('encTimeOut').value = toDateTimeInputValue(dateStr, punches.time_out);

    document.getElementById('encStatus').value = punches.status || 'Auto';
    document.getElementById('encNotes').value = punches.notes || '';

    showLookupAlert('Editing existing attendance record for ' + empName + ' on ' + dateStr, 'info');
    openModal('encodeModal');
}

function formatLocalDateTime(d) {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const hh = String(d.getHours()).padStart(2, '0');
    const min = String(d.getMinutes()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

// Set time to current clock (Date & Time format)
function setNowPunch(inputId) {
    const now = new Date();
    const input = document.getElementById(inputId);
    if (input) {
        input.value = formatLocalDateTime(now);
        syncDateFromPunches();
        lookupPunchesForDate();
    }
}

// Preset standard 8-hr day (Date & Time format)
function applyStandardShiftPreset() {
    const baseDate = getActivePunchDate();
    document.getElementById('encTimeIn').value = `${baseDate}T08:00`;
    document.getElementById('encBreakOut').value = `${baseDate}T12:00`;
    document.getElementById('encBreakIn').value = `${baseDate}T13:00`;
    document.getElementById('encCoffeeOut').value = '';
    document.getElementById('encCoffeeIn').value = '';
    document.getElementById('encTimeOut').value = `${baseDate}T17:00`;
    syncDateFromPunches();
    lookupPunchesForDate();
}

function clearAllPunches() {
    document.getElementById('encTimeIn').value = '';
    document.getElementById('encBreakOut').value = '';
    document.getElementById('encBreakIn').value = '';
    document.getElementById('encCoffeeOut').value = '';
    document.getElementById('encCoffeeIn').value = '';
    document.getElementById('encTimeOut').value = '';
    if (window.resetAllPunchBuffers) window.resetAllPunchBuffers();
}

function showLookupAlert(text, type) {
    const el = document.getElementById('lookupAlert');
    if (!el) return;
    el.style.display = 'block';
    el.innerText = text;
    if (type === 'found') {
        el.style.background = 'rgba(59, 130, 246, 0.12)';
        el.style.color = '#1d4ed8';
        el.style.border = '1px solid rgba(59, 130, 246, 0.3)';
    } else {
        el.style.background = 'rgba(16, 185, 129, 0.12)';
        el.style.color = '#047857';
        el.style.border = '1px solid rgba(16, 185, 129, 0.3)';
    }
}

function hideLookupAlert() {
    const el = document.getElementById('lookupAlert');
    if (el) el.style.display = 'none';
}

// Dynamic lookup when employee or punch date changes in modal
async function lookupPunchesForDate() {
    const empId = document.getElementById('encEmployeeId').value;
    const dateVal = getActivePunchDate();

    if (!empId || !dateVal) {
        hideLookupAlert();
        return;
    }

    try {
        const res = await fetch(`{{ route('hr.attendance.corrections.lookup') }}?employee_id=${empId}&date=${dateVal}`);
        const data = await res.json();

        if (data.exists) {
            if (!document.getElementById('encTimeIn').value && data.time_in) {
                document.getElementById('encTimeIn').value = toDateTimeInputValue(dateVal, data.time_in);
            }
            if (!document.getElementById('encBreakOut').value && data.break_out) {
                document.getElementById('encBreakOut').value = toDateTimeInputValue(dateVal, data.break_out);
            }
            if (!document.getElementById('encBreakIn').value && data.break_in) {
                document.getElementById('encBreakIn').value = toDateTimeInputValue(dateVal, data.break_in);
            }
            if (!document.getElementById('encCoffeeOut').value && data.coffee_break_out) {
                document.getElementById('encCoffeeOut').value = toDateTimeInputValue(dateVal, data.coffee_break_out);
            }
            if (!document.getElementById('encCoffeeIn').value && data.coffee_break_in) {
                document.getElementById('encCoffeeIn').value = toDateTimeInputValue(dateVal, data.coffee_break_in);
            }
            if (!document.getElementById('encTimeOut').value && data.time_out) {
                document.getElementById('encTimeOut').value = toDateTimeInputValue(dateVal, data.time_out);
            }
            if (data.status && document.getElementById('encStatus').value === 'Auto') {
                document.getElementById('encStatus').value = data.status;
            }
            if (data.notes && !document.getElementById('encNotes').value) {
                document.getElementById('encNotes').value = data.notes;
            }

            showLookupAlert(`Found existing record for ${dateVal} (${data.total_hours} hrs, Status: ${data.status}). Punches mapped.`, 'found');
        } else {
            showLookupAlert(`No previous attendance record on ${dateVal}. You are encoding a new time entry.`, 'new');
        }
    } catch (e) {
        console.warn('Lookup failed:', e);
    }
}

// Real-Time Table Filtering
function filterManualTable(dateTriggered = false) {
    const searchVal = (document.getElementById('manualSearchInput')?.value || '').toLowerCase().trim();
    const dateVal = (document.getElementById('manualDateFilter')?.value || '').trim();
    const branchVal = (document.getElementById('manualBranchFilter')?.value || '').trim();
    const statusVal = (document.getElementById('manualStatusFilter')?.value || '').trim();

    const rows = document.querySelectorAll('.manual-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const search = row.getAttribute('data-search') || '';
        const rDate = row.getAttribute('data-date') || '';
        const rBranch = row.getAttribute('data-branch') || '';
        const rStatus = row.getAttribute('data-status') || '';

        const matchesSearch = !searchVal || search.includes(searchVal);
        const matchesDate = !dateVal || rDate === dateVal;
        const matchesBranch = !branchVal || rBranch === branchVal;
        const matchesStatus = !statusVal || rStatus === statusVal;

        if (matchesSearch && matchesDate && matchesBranch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countBadge = document.getElementById('visibleCount');
    if (countBadge) countBadge.innerText = visibleCount;

    const emptyRow = document.getElementById('emptyTableRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyServerDateFilter(val) {
    if (val) {
        window.location.href = `{{ route('hr.attendance.corrections') }}?date=${val}`;
    } else {
        window.location.href = `{{ route('hr.attendance.corrections') }}`;
    }
}

function resetManualFilters() {
    const s = document.getElementById('manualSearchInput'); if (s) s.value = '';
    const b = document.getElementById('manualBranchFilter'); if (b) b.value = '';
    const st = document.getElementById('manualStatusFilter'); if (st) st.value = '';
    filterManualTable();
}

// Smart Tab Auto-Complete for Time Punches & 2-Digit Dates
// (e.g. typing 18 and pressing Tab -> 18:00, or dates like 10/21/01 -> 2001-10-21, 01-10 -> 2001-2010)
(function() {
    const punchInputIds = ['encTimeIn', 'encBreakOut', 'encBreakIn', 'encCoffeeOut', 'encCoffeeIn', 'encTimeOut'];
    const punchBuffers = {};

    function normalizePunchYear(input) {
        if (!input || !input.value) return;
        const val = input.value;
        const match = val.match(/^00([0-9]{2})-(.*)$/);
        if (match) {
            const yy = parseInt(match[1], 10);
            if (yy >= 0 && yy <= 99) {
                const fullY = 2000 + yy; // 01-10 -> 2001-2010, 00-99 -> 2000-2099
                input.value = `${fullY}-${match[2]}`;
                syncDateFromPunches();
            }
        }
    }

    window.resetAllPunchBuffers = function() {
        punchInputIds.forEach(id => {
            punchBuffers[id] = { digits: '', raw: '', colon: false };
        });
    };

    function initPunchInputs() {
        punchInputIds.forEach((id, idx) => {
            const input = document.getElementById(id);
            if (!input) return;

            punchBuffers[id] = { digits: '', raw: '', colon: false };

            input.addEventListener('focus', function() {
                punchBuffers[id] = { digits: '', raw: '', colon: false };
            });

            // Normalize whenever input or change fires (captures browser native 0001-0010 values)
            input.addEventListener('input', function() {
                normalizePunchYear(this);
            });
            input.addEventListener('change', function() {
                normalizePunchYear(this);
            });
            input.addEventListener('keyup', function() {
                normalizePunchYear(this);
            });

            input.addEventListener('keydown', function(e) {
                const buf = punchBuffers[id] || (punchBuffers[id] = { digits: '', raw: '', colon: false });

                if ((e.key >= '0' && e.key <= '9') || e.key === '/' || e.key === '-' || e.key === '.') {
                    buf.raw += e.key;
                    if (e.key >= '0' && e.key <= '9') {
                        buf.digits += e.key;
                    }
                    return;
                }
                if (e.key === ':') {
                    buf.colon = true;
                    buf.raw += ':';
                    return;
                }
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    buf.digits = '';
                    buf.raw = '';
                    buf.colon = false;
                    return;
                }

                if ((e.key === 'Tab' && !e.shiftKey) || e.key === 'Enter') {
                    // Check and fix if current value already has 2-digit year (0001-0010 -> 2001-2010)
                    normalizePunchYear(this);

                    let targetDate = null;
                    let targetTime = null;

                    // 1. Check slash/dash/dot format in raw buffer: e.g. "10/21/01", "10-21-01", "10/01/01"
                    const slashMatch = buf.raw.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{1,4})(?:[T\s](\d{1,2})(?::(\d{1,2}))?)?$/);
                    if (slashMatch) {
                        const m = parseInt(slashMatch[1], 10);
                        const d = parseInt(slashMatch[2], 10);
                        let y = parseInt(slashMatch[3], 10);
                        if (m >= 1 && m <= 12 && d >= 1 && d <= 31) {
                            if (y >= 0 && y <= 99) {
                                y = 2000 + y; // e.g. 01 -> 2001, 10 -> 2010
                            }
                            targetDate = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                            if (slashMatch[4]) {
                                const hh = String(parseInt(slashMatch[4], 10)).padStart(2, '0');
                                const mm = slashMatch[5] ? String(parseInt(slashMatch[5], 10)).padStart(2, '0') : '00';
                                targetTime = `${hh}:${mm}`;
                            }
                        }
                    }

                    // 2. Check numeric buffer:
                    // 6 digits: MMDDYY (e.g. "102101" -> 10/21/2001, "100101" -> 10/01/2001)
                    if (!targetDate && buf.digits.length === 6) {
                        const m = parseInt(buf.digits.substring(0, 2), 10);
                        const d = parseInt(buf.digits.substring(2, 4), 10);
                        let y = parseInt(buf.digits.substring(4, 6), 10);
                        if (m >= 1 && m <= 12 && d >= 1 && d <= 31) {
                            if (y >= 0 && y <= 99) {
                                y = 2000 + y; // 01 -> 2001, 10 -> 2010
                            }
                            targetDate = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                        }
                    }

                    // 8 digits: MMDDYYYY (e.g. "10212001") or MMDDYYHH (e.g. "10210118")
                    if (!targetDate && buf.digits.length === 8) {
                        const m = parseInt(buf.digits.substring(0, 2), 10);
                        const d = parseInt(buf.digits.substring(2, 4), 10);
                        const possibleY = parseInt(buf.digits.substring(4, 8), 10);
                        if (m >= 1 && m <= 12 && d >= 1 && d <= 31) {
                            if (possibleY >= 1970 && possibleY <= 2100) {
                                targetDate = `${possibleY}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                            } else {
                                let yy = parseInt(buf.digits.substring(4, 6), 10);
                                const hh = parseInt(buf.digits.substring(6, 8), 10);
                                if (yy >= 0 && yy <= 99 && hh >= 0 && hh <= 23) {
                                    yy = 2000 + yy;
                                    targetDate = `${yy}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                                    targetTime = `${String(hh).padStart(2, '0')}:00`;
                                }
                            }
                        }
                    }

                    // 10 digits: MMDDYYHHmm (e.g. "1021011800")
                    if (!targetDate && buf.digits.length === 10) {
                        const m = parseInt(buf.digits.substring(0, 2), 10);
                        const d = parseInt(buf.digits.substring(2, 4), 10);
                        let yy = parseInt(buf.digits.substring(4, 6), 10);
                        const hh = parseInt(buf.digits.substring(6, 8), 10);
                        const mm = parseInt(buf.digits.substring(8, 10), 10);
                        if (m >= 1 && m <= 12 && d >= 1 && d <= 31 && hh >= 0 && hh <= 23 && mm >= 0 && mm <= 59) {
                            if (yy >= 0 && yy <= 99) yy = 2000 + yy;
                            targetDate = `${yy}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                            targetTime = `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
                        }
                    }

                    // 12 digits: MMDDYYYYHHmm
                    if (!targetDate && buf.digits.length === 12) {
                        const m = parseInt(buf.digits.substring(0, 2), 10);
                        const d = parseInt(buf.digits.substring(2, 4), 10);
                        const y = parseInt(buf.digits.substring(4, 8), 10);
                        const hh = parseInt(buf.digits.substring(8, 10), 10);
                        const mm = parseInt(buf.digits.substring(10, 12), 10);
                        if (m >= 1 && m <= 12 && d >= 1 && d <= 31 && y >= 1970 && y <= 2100 && hh >= 0 && hh <= 23 && mm >= 0 && mm <= 59) {
                            targetDate = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                            targetTime = `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
                        }
                    }

                    // 3. Check time-only buffers:
                    if (!targetDate && !targetTime && buf.digits.length > 0) {
                        let hour = null;
                        let minute = '00';

                        // 1 or 2 digits: e.g. "18" -> 18:00
                        if (buf.digits.length === 1 || buf.digits.length === 2) {
                            hour = parseInt(buf.digits, 10);
                            minute = '00';
                        } else if (buf.digits.length === 3) {
                            // "830" -> 08:30
                            hour = parseInt(buf.digits.substring(0, 1), 10);
                            minute = buf.digits.substring(1, 3);
                        } else if (buf.digits.length === 4) {
                            // "1800" -> 18:00, "1830" -> 18:30
                            hour = parseInt(buf.digits.substring(0, 2), 10);
                            minute = buf.digits.substring(2, 4);
                        }
                        if (hour !== null && !isNaN(hour) && hour >= 0 && hour <= 23) {
                            targetTime = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
                        }
                    }

                    if (targetDate || targetTime) {
                        e.preventDefault();
                        const baseDate = targetDate || getActivePunchDate();
                        let finalTime = targetTime;
                        if (!finalTime) {
                            if (this.value && this.value.includes('T')) {
                                finalTime = this.value.split('T')[1].substring(0, 5);
                            } else {
                                finalTime = id === 'encTimeOut' ? '17:00' : '08:00';
                            }
                        }
                        this.value = `${baseDate}T${finalTime}`;
                        normalizePunchYear(this);

                        buf.digits = '';
                        buf.raw = '';
                        buf.colon = false;

                        syncDateFromPunches();
                        if (id === 'encTimeIn') {
                            lookupPunchesForDate();
                        }

                        // Advance focus to next punch input
                        if (idx < punchInputIds.length - 1) {
                            const nextInput = document.getElementById(punchInputIds[idx + 1]);
                            if (nextInput) {
                                nextInput.focus();
                                if (punchBuffers[punchInputIds[idx + 1]]) {
                                    punchBuffers[punchInputIds[idx + 1]].digits = '';
                                    punchBuffers[punchInputIds[idx + 1]].raw = '';
                                }
                            }
                        } else {
                            const statusSelect = document.getElementById('encStatus');
                            if (statusSelect) statusSelect.focus();
                        }
                        return;
                    }

                    buf.digits = '';
                    buf.raw = '';
                    buf.colon = false;
                }
            });

            input.addEventListener('blur', function() {
                normalizePunchYear(this);
                const buf = punchBuffers[id];
                if (buf) {
                    if (buf.digits.length === 1 || buf.digits.length === 2) {
                        const hour = parseInt(buf.digits, 10);
                        if (!isNaN(hour) && hour >= 0 && hour <= 23) {
                            const baseDate = getActivePunchDate();
                            const hh = String(hour).padStart(2, '0');
                            this.value = `${baseDate}T${hh}:00`;
                            normalizePunchYear(this);
                            syncDateFromPunches();
                        }
                    }
                    buf.digits = '';
                    buf.raw = '';
                    buf.colon = false;
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPunchInputs);
    } else {
        initPunchInputs();
    }
})();
</script>
@endpush
@endsection
