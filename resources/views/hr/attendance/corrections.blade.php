@extends('layouts.app')

@section('title', 'Manual Time Entries - Time & Attendance Management')

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
<div class="hr-table-card">
    <div class="hr-table-wrapper" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
        <table class="hr-table" id="manualEntriesTable">
            <thead>
                <tr>
                    <th style="min-width: 120px;">Date</th>
                    <th style="min-width: 180px;">Staff Member</th>
                    <th style="min-width: 160px;">Branch & Dept</th>
                    <th style="text-align: center; min-width: 75px;">1. In</th>
                    <th style="text-align: center; min-width: 75px;">2. Break Out</th>
                    <th style="text-align: center; min-width: 75px;">3. Break In</th>
                    <th style="text-align: center; min-width: 75px;">4. Coffee Out</th>
                    <th style="text-align: center; min-width: 75px;">5. Coffee In</th>
                    <th style="text-align: center; min-width: 75px;">6. Final Out</th>
                    <th style="min-width: 95px;">Total Hours</th>
                    <th style="min-width: 100px;">Status</th>
                    <th style="min-width: 180px;">Notes / Purpose</th>
                    <th style="text-align: right; min-width: 130px;">Action</th>
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
                        <td>
                            <strong style="color: #0f172a;">{{ $displayDate }}</strong>
                            <div style="font-size: 11px; color: #64748b;">{{ \Carbon\Carbon::parse($r->date)->format('l') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $empName }}</div>
                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">{{ $empId }} &bull; {{ $posName }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #334155; font-size: 12.5px;">
                                <i class="ph ph-storefront" style="color: #64748b;"></i> {{ $branchName }}
                            </div>
                            <div style="font-size: 11px; color: #64748b;">{{ $deptName }}</div>
                        </td>

                        <!-- 1. In -->
                        <td style="text-align: center;">
                            @if($pIn)
                                <strong style="color: #059669; font-family: monospace; font-size: 13px;">{{ substr($pIn, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace;">--:--</span>
                            @endif
                        </td>

                        <!-- 2. Break Out -->
                        <td style="text-align: center;">
                            @if($pBreakOut)
                                <span style="color: #334155; font-family: monospace; font-size: 12.5px;">{{ substr($pBreakOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace;">--:--</span>
                            @endif
                        </td>

                        <!-- 3. Break In -->
                        <td style="text-align: center;">
                            @if($pBreakIn)
                                <span style="color: #334155; font-family: monospace; font-size: 12.5px;">{{ substr($pBreakIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace;">--:--</span>
                            @endif
                        </td>

                        <!-- 4. Coffee Out -->
                        <td style="text-align: center;">
                            @if($pCoffeeOut)
                                <span style="color: #b45309; font-family: monospace; font-size: 12.5px;">{{ substr($pCoffeeOut, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace;">--:--</span>
                            @endif
                        </td>

                        <!-- 5. Coffee In -->
                        <td style="text-align: center;">
                            @if($pCoffeeIn)
                                <span style="color: #b45309; font-family: monospace; font-size: 12.5px;">{{ substr($pCoffeeIn, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace;">--:--</span>
                            @endif
                        </td>

                        <!-- 6. Final Out -->
                        <td style="text-align: center;">
                            @if($pFinalOut)
                                <strong style="color: #0284c7; font-family: monospace; font-size: 13px;">{{ substr($pFinalOut, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1; font-family: monospace;">--:--</span>
                            @endif
                        </td>

                        <!-- Total Hours -->
                        <td>
                            <strong style="color: #0f172a; font-size: 13px;">{{ number_format($r->total_hours ?? 0, 2) }} hrs</strong>
                        </td>

                        <!-- Status Badge -->
                        <td>
                            @if($r->status === 'Present')
                                <span class="hr-badge hr-badge-success">{{ $r->status }}</span>
                            @elseif($r->status === 'Late')
                                <span class="hr-badge hr-badge-warning">{{ $r->status }}</span>
                            @elseif($r->status === 'Rest Day')
                                <span class="hr-badge hr-badge-neutral">{{ $r->status }}</span>
                            @elseif($r->status === 'Overtime')
                                <span class="hr-badge hr-badge-purple">{{ $r->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-danger">{{ $r->status }}</span>
                            @endif
                        </td>

                        <!-- Notes & Source -->
                        <td>
                            <div style="font-size: 12px; color: #334155;">{{ $r->notes ?: 'Manual time entry' }}</div>
                            <span class="hr-badge hr-badge-info" style="font-size: 9.5px; padding: 1px 6px; margin-top: 3px;">
                                <i class="ph ph-hand-pointing"></i> {{ $r->source ?? 'Manual' }}
                            </span>
                        </td>

                        <!-- Action Buttons -->
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick='editManualEntry({{ $r->id }}, {{ $r->employee_id }}, "{{ addslashes($empName) }}", "{{ $dateStr }}", {{ json_encode($punchesData) }})' title="Edit/re-encode punches for this date">
                                <i class="ph ph-pencil-simple"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('hr.attendance.corrections.destroy', $r->id) }}" onsubmit="return confirm('Are you sure you want to delete this manual time entry for {{ addslashes($empName) }} on {{ $displayDate }}?');" style="display: inline-block; margin-left: 4px;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete manual entry">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </form>
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

    @if($records->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
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
                
                <!-- Step 1: Staff & Chosen Date Selection -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-user"></i> 1. Select Staff & Chosen Date
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #0f172a;">Staff Member *</label>
                            <select name="employee_id" id="encEmployeeId" class="hr-select" required onchange="lookupPunchesForDate()" style="height: 38px;">
                                <option value="">-- Select Employee --</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_id }}) - {{ $e->branch?->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12.5px; font-weight: 600; color: #0f172a;">Chosen Attendance Date *</label>
                            <input type="date" name="date" id="encDate" class="hr-input" required value="{{ date('Y-m-d') }}" onchange="lookupPunchesForDate()">
                        </div>
                    </div>

                    <!-- Live Date/Record Lookup Status Alert -->
                    <div id="lookupAlert" style="display: none; margin-top: 12px; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;"></div>
                </div>

                <!-- Step 2: Time Punches Encoding Grid -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <i class="ph ph-fingerprint"></i> 2. Encode Time Punches (24-Hour Format)
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

                    <!-- 6-Punch Responsive Grid -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <!-- 1. Shift In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #059669; display: flex; align-items: center; justify-content: space-between;">
                                <span>1. Shift In</span>
                                <span style="font-size: 10px; cursor: pointer; color: #7c3aed;" onclick="setNowPunch('encTimeIn')">Now</span>
                            </label>
                            <input type="time" name="time_in" id="encTimeIn" class="hr-input" style="height: 38px; font-family: monospace; font-size: 14px; font-weight: 600;">
                        </div>

                        <!-- 2. Break Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                                <span>2. Break Out</span>
                                <span style="font-size: 10px; cursor: pointer; color: #7c3aed;" onclick="setNowPunch('encBreakOut')">Now</span>
                            </label>
                            <input type="time" name="break_out" id="encBreakOut" class="hr-input" style="height: 38px; font-family: monospace; font-size: 14px;">
                        </div>

                        <!-- 3. Break In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #334155; display: flex; align-items: center; justify-content: space-between;">
                                <span>3. Break In</span>
                                <span style="font-size: 10px; cursor: pointer; color: #7c3aed;" onclick="setNowPunch('encBreakIn')">Now</span>
                            </label>
                            <input type="time" name="break_in" id="encBreakIn" class="hr-input" style="height: 38px; font-family: monospace; font-size: 14px;">
                        </div>

                        <!-- 4. Coffee Break Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #b45309; display: flex; align-items: center; justify-content: space-between;">
                                <span>4. Coffee Out</span>
                                <span style="font-size: 10px; cursor: pointer; color: #7c3aed;" onclick="setNowPunch('encCoffeeOut')">Now</span>
                            </label>
                            <input type="time" name="coffee_break_out" id="encCoffeeOut" class="hr-input" style="height: 38px; font-family: monospace; font-size: 14px;">
                        </div>

                        <!-- 5. Coffee Break In -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #b45309; display: flex; align-items: center; justify-content: space-between;">
                                <span>5. Coffee In</span>
                                <span style="font-size: 10px; cursor: pointer; color: #7c3aed;" onclick="setNowPunch('encCoffeeIn')">Now</span>
                            </label>
                            <input type="time" name="coffee_break_in" id="encCoffeeIn" class="hr-input" style="height: 38px; font-family: monospace; font-size: 14px;">
                        </div>

                        <!-- 6. Final Out -->
                        <div class="hr-form-group" style="margin-bottom: 0;">
                            <label class="hr-form-label" style="font-size: 12px; font-weight: 600; color: #0284c7; display: flex; align-items: center; justify-content: space-between;">
                                <span>6. Final Out</span>
                                <span style="font-size: 10px; cursor: pointer; color: #7c3aed;" onclick="setNowPunch('encTimeOut')">Now</span>
                            </label>
                            <input type="time" name="time_out" id="encTimeOut" class="hr-input" style="height: 38px; font-family: monospace; font-size: 14px; font-weight: 600;">
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
    document.getElementById('encDate').value = '{{ date("Y-m-d") }}';
    clearAllPunches();
    document.getElementById('encStatus').value = 'Auto';
    document.getElementById('encNotes').value = '';
    hideLookupAlert();
    openModal('encodeModal');
}

// Edit existing manual entry
function editManualEntry(recordId, empId, empName, dateStr, punches) {
    document.getElementById('modalTitle').innerText = 'Edit Manual Time Entry - ' + empName;
    document.getElementById('encEmployeeId').value = empId;
    document.getElementById('encDate').value = dateStr;

    document.getElementById('encTimeIn').value = punches.time_in || '';
    document.getElementById('encBreakOut').value = punches.break_out || '';
    document.getElementById('encBreakIn').value = punches.break_in || '';
    document.getElementById('encCoffeeOut').value = punches.coffee_break_out || '';
    document.getElementById('encCoffeeIn').value = punches.coffee_break_in || '';
    document.getElementById('encTimeOut').value = punches.time_out || '';

    document.getElementById('encStatus').value = punches.status || 'Auto';
    document.getElementById('encNotes').value = punches.notes || '';

    showLookupAlert('Editing existing attendance record for ' + empName + ' on ' + dateStr, 'info');
    openModal('encodeModal');
}

// Set time to current clock
function setNowPunch(inputId) {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const input = document.getElementById(inputId);
    if (input) { input.value = `${hh}:${mm}`; }
}

// Preset standard 8-hr day
function applyStandardShiftPreset() {
    document.getElementById('encTimeIn').value = '08:00';
    document.getElementById('encBreakOut').value = '12:00';
    document.getElementById('encBreakIn').value = '13:00';
    document.getElementById('encCoffeeOut').value = '';
    document.getElementById('encCoffeeIn').value = '';
    document.getElementById('encTimeOut').value = '17:00';
}

function clearAllPunches() {
    document.getElementById('encTimeIn').value = '';
    document.getElementById('encBreakOut').value = '';
    document.getElementById('encBreakIn').value = '';
    document.getElementById('encCoffeeOut').value = '';
    document.getElementById('encCoffeeIn').value = '';
    document.getElementById('encTimeOut').value = '';
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

// Dynamic lookup when employee or date changes in modal
async function lookupPunchesForDate() {
    const empId = document.getElementById('encEmployeeId').value;
    const dateVal = document.getElementById('encDate').value;

    if (!empId || !dateVal) {
        hideLookupAlert();
        return;
    }

    try {
        const res = await fetch(`{{ route('hr.attendance.corrections.lookup') }}?employee_id=${empId}&date=${dateVal}`);
        const data = await res.json();

        if (data.exists) {
            document.getElementById('encTimeIn').value = data.time_in || '';
            document.getElementById('encBreakOut').value = data.break_out || '';
            document.getElementById('encBreakIn').value = data.break_in || '';
            document.getElementById('encCoffeeOut').value = data.coffee_break_out || '';
            document.getElementById('encCoffeeIn').value = data.coffee_break_in || '';
            document.getElementById('encTimeOut').value = data.time_out || '';
            if (data.status) document.getElementById('encStatus').value = data.status;
            if (data.notes) document.getElementById('encNotes').value = data.notes;

            showLookupAlert(`Found existing record for this date (${data.total_hours} hrs, Status: ${data.status}). You can edit or adjust punches directly.`, 'found');
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
</script>
@endpush
@endsection
