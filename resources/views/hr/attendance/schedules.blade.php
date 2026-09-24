@extends('layouts.app')

@section('title', 'Work Schedule Management - Attendance')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-calendar"></i>
            Work Schedule Management
        </h1>
        <p class="hr-page-subtitle">Assign work schedules across flexible date ranges and configure weekly rest days (Mon – Sun)</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-secondary" onclick="openModal('addShiftModal')">
            <i class="ph ph-clock-afternoon"></i>
            <span>Add Shift Template</span>
        </button>
        <button class="hr-btn hr-btn-primary" onclick="openAssignModal()">
            <i class="ph ph-calendar-plus"></i>
            <span>Assign Schedule</span>
        </button>
    </div>
</div>

<!-- Weekly Schedule Matrix -->
<div class="hr-table-card">
    <div class="hr-table-header">
        <div>
            <span class="hr-table-title"><i class="ph ph-table"></i> Weekly Shift Roster</span>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                Click any cell or "+ Assign" to schedule shifts across custom date ranges and assign rest days.
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <form method="GET" action="{{ route('hr.attendance.schedules') }}" style="display: flex; align-items: center; gap: 8px;">
                @if(request('branch_id'))
                    <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                @endif
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Week of:</label>
                <input type="date" name="week_start" class="hr-input" value="{{ $weekStart }}" onchange="this.form.submit()" style="padding: 6px 10px; font-size: 12.5px;">
            </form>
        </div>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th style="min-width: 200px;">Staff Member</th>
                    @foreach($dates as $d)
                        @php $cDate = \Carbon\Carbon::parse($d); @endphp
                        <th style="text-align: center; min-width: 130px; {{ $cDate->isToday() ? 'background: rgba(168, 85, 247, 0.08);' : '' }}">
                            <div style="font-weight: 700; color: {{ $cDate->isToday() ? '#7e22ce' : '#0f172a' }};">
                                {{ $cDate->format('D') }}
                            </div>
                            <small style="color: #64748b; font-size: 11px;">{{ $cDate->format('M d') }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    @php $empScheds = $schedules->get($emp->id) ? $schedules->get($emp->id)->keyBy('schedule_date') : collect(); @endphp
                    <tr>
                        <td>
                            <div class="hr-emp-avatar-wrap">
                                @if($emp->photo_url)
                                    <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" class="hr-avatar-img-sm">
                                @else
                                    <div class="hr-avatar-circle-sm">
                                        {{ $emp->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight: 700; color: #0f172a;">{{ $emp->full_name }}</div>
                                    <div style="font-size: 11px; color: #64748b;">
                                        {{ $emp->position?->name ?? 'Staff' }} &bull; {{ $emp->branch?->name ?? 'Main' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        @foreach($dates as $d)
                            @php 
                                $daySched = $empScheds->get($d); 
                                $cDate = \Carbon\Carbon::parse($d);
                            @endphp
                            <td style="text-align: center; vertical-align: middle; {{ $cDate->isToday() ? 'background: rgba(168, 85, 247, 0.03);' : '' }}">
                                @if($daySched)
                                    @if($daySched->is_rest_day)
                                        <div onclick="quickAssign({{ $emp->id }}, '{{ $d }}', true)" style="cursor: pointer;" title="Click to edit schedule for {{ $emp->full_name }}">
                                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px;">
                                                Rest Day
                                            </span>
                                        </div>
                                    @elseif($daySched->shiftTemplate)
                                        <div onclick="quickAssign({{ $emp->id }}, '{{ $d }}', false, {{ $daySched->shift_template_id }})" style="cursor: pointer;" title="Click to edit schedule for {{ $emp->full_name }}">
                                            <div style="background: rgba(168, 85, 247, 0.12); border: 1.5px solid rgba(168, 85, 247, 0.35); border-radius: 8px; padding: 5px 8px; font-size: 11.5px; font-weight: 700; color: #6b21a8; font-family: monospace; line-height: 1.25;">
                                                {{ $daySched->shiftTemplate->name }}
                                            </div>
                                        </div>
                                    @else
                                        <div onclick="quickAssign({{ $emp->id }}, '{{ $d }}')" style="cursor: pointer;" title="Click to edit schedule">
                                            <span style="font-size: 11px; color: #334155; font-weight: 600;">Custom Shift</span>
                                        </div>
                                    @endif
                                @else
                                    <button class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 10.5px; padding: 3px 8px;" onclick="quickAssign({{ $emp->id }}, '{{ $d }}')">
                                        + Assign
                                    </button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 36px;">No active employees found for this schedule roster.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign Schedule (Date Range & Rest Day by Day) -->
<div id="assignScheduleModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 680px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> Assign Work Schedule</span>
            <button class="icon-btn" onclick="closeModal('assignScheduleModal')"><i class="ph ph-x"></i></button>
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
                                {{ $st->name }}
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
            <button class="icon-btn" onclick="closeModal('addShiftModal')"><i class="ph ph-x"></i></button>
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
.sched-day-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.sched-day-pill {
    cursor: pointer;
    margin: 0;
}
.sched-day-input {
    display: none;
}
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
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
}
.sched-day-pill:hover .sched-day-label {
    border-color: #9333ea;
}
.sched-preset-btn {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}
.sched-preset-btn:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
}
</style>

@push('scripts')
<script>
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

function quickAssign(empId, date, isRestDay = false, shiftTemplateId = null) {
    document.getElementById('schedEmployeeId').value = empId;
    document.getElementById('schedStartDate').value = date;
    document.getElementById('schedEndDate').value = date;

    clearRestDaysPreset();

    if (isRestDay) {
        document.getElementById('isRestDayAll').checked = true;
    } else {
        document.getElementById('isRestDayAll').checked = false;
        if (shiftTemplateId) {
            document.getElementById('schedShiftTemplateId').value = shiftTemplateId;
        }
    }
    toggleRestDayAll(document.getElementById('isRestDayAll'));
    openModal('assignScheduleModal');
}

function syncDateRange() {
    var start = document.getElementById('schedStartDate').value;
    var end = document.getElementById('schedEndDate').value;
    if (!end || end < start) {
        document.getElementById('schedEndDate').value = start;
    }
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
        // already today
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay(); // 0 is Sun, 1 is Mon
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

function clearRestDaysPreset() {
    setRestDaysPreset([]);
}

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

    var label = code + ' = ' + startFmt + ' - ' + endFmt;
    document.getElementById('shiftFormatPreview').textContent = label;

    var overnightCheckbox = document.getElementById('isOvernight');
    if (start > end) {
        overnightCheckbox.checked = true;
    }
}
</script>
@endpush
@endsection
