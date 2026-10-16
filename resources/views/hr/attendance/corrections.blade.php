@extends('layouts.app')

@section('title', 'Manual Time Entries - Time & Attendance Management')

@push('styles')
    @vite('resources/css/modules/hr-corrections.css')
@endpush

@push('blade-data')
<script>
    window.HrCorrectionsConfig = Object.freeze({
        routes: {
            lookup: @json(route('hr.attendance.corrections.lookup')),
            corrections: @json(route('hr.attendance.corrections')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-corrections.js')
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
<div class="hr-filter-bar" style="margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: nowrap;">
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; flex: 1;">
        <!-- Real-Time Text Search -->
        <div style="position: relative; width: 220px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
            <input type="text" id="manualSearchInput" class="hr-input" placeholder="Search staff, ID, branch, notes..." oninput="filterManualTable()" style="width: 100%; box-sizing: border-box; padding-left: 28px; height: 31px; font-size: 12px;">
        </div>

        <!-- Chosen Date Filter -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <input type="date" id="manualDateFilter" class="hr-input" value="{{ request('date') }}" onchange="applyServerDateFilter(this.value)" style="height: 31px; width: 140px; font-size: 12px;" title="Filter by specific chosen date">
            @if(request('date'))
                <a href="{{ route('hr.attendance.corrections') }}" class="hr-btn hr-btn-secondary" style="height: 31px; padding: 0 8px; font-size: 11.5px;" title="Clear date filter">
                    <i class="ph ph-x"></i> Clear
                </a>
            @endif
        </div>

        <!-- Branch Filter -->
        <select id="manualBranchFilter" class="hr-select" style="height: 31px; max-width: 155px; font-size: 12px;" onchange="filterManualTable()">
            <option value="">-- All Branches --</option>
            @foreach($branches as $b)
                <option value="{{ $b->name }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>

        <!-- Status Filter -->
        <select id="manualStatusFilter" class="hr-select" style="height: 31px; max-width: 145px; font-size: 12px;" onchange="filterManualTable()">
            <option value="">-- All Statuses --</option>
            <option value="Present">Present</option>
            <option value="Late">Late</option>
            <option value="Half Day">Half Day</option>
            <option value="Rest Day">Rest Day</option>
            <option value="Overtime">Overtime</option>
        </select>

        <!-- Quick Reset -->
        <button type="button" class="hr-btn hr-btn-secondary" style="height: 31px; font-size: 12px;" onclick="resetManualFilters()">
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

                <!-- Step 2: Time Punches Encoding Grid (Date & Time Pickers) -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-fingerprint"></i> 2. Encode Time Punches (Date & Time Format)
                            </div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                Type military time like <strong style="color: #475569;">18</strong> (converts to 6:00 PM) or <strong style="color: #475569;">1830</strong> (converts to 6:30 PM) and press <kbd style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px; font-size: 10px;">Tab</kbd>
                            </div>
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
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #059669; display: flex; align-items: center; justify-content: space-between;">
                                <span>1. Shift In</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 600;" onclick="setNowPunch('encTimeIn')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="time_in" id="encTimeIn" class="hr-input enc-punch-input" onchange="syncDateFromPunches(); lookupPunchesForDate();" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 2. Break Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                                <span>2. Break Out</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 600;" onclick="setNowPunch('encBreakOut')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="break_out" id="encBreakOut" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 3. Break In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                                <span>3. Break In</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 600;" onclick="setNowPunch('encBreakIn')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="break_in" id="encBreakIn" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 4. Coffee Break Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #b45309; display: flex; align-items: center; justify-content: space-between;">
                                <span>4. Coffee Out</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 600;" onclick="setNowPunch('encCoffeeOut')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="coffee_break_out" id="encCoffeeOut" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 5. Coffee Break In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #b45309; display: flex; align-items: center; justify-content: space-between;">
                                <span>5. Coffee In</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 600;" onclick="setNowPunch('encCoffeeIn')">Now</span>
                            </label>
                            <input type="datetime-local" step="60" name="coffee_break_in" id="encCoffeeIn" class="hr-input enc-punch-input" onchange="syncDateFromPunches()" style="height: 38px; font-family: inherit; font-size: 13px; font-weight: 400; color: #1e293b;">
                        </div>

                        <!-- 6. Final Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #0284c7; display: flex; align-items: center; justify-content: space-between;">
                                <span>6. Final Out</span>
                                <span style="font-size: 10.5px; cursor: pointer; color: #7c3aed; font-weight: 600;" onclick="setNowPunch('encTimeOut')">Now</span>
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
@endsection
