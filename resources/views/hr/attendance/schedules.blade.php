@extends('layouts.app')

@section('title', 'Work Schedule Management - Attendance')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-calendar"></i>
            Restaurant Work Schedules & Shift Planning
        </h1>
        <p class="hr-page-subtitle">Schedule kitchen, dining, closing, and overnight prep shifts across restaurant branches</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-secondary" onclick="openModal('addShiftModal')">
            <i class="ph ph-clock-afternoon"></i>
            <span>Add Shift Template</span>
        </button>
        <button class="hr-btn hr-btn-primary" onclick="openModal('assignScheduleModal')">
            <i class="ph ph-calendar-plus"></i>
            <span>Assign Shift</span>
        </button>
    </div>
</div>

<!-- Active Shift Templates -->
<div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
    @foreach($shiftTemplates as $tmpl)
        <div style="background: rgba(255, 255, 255, 0.85); border-left: 4px solid {{ $tmpl->color ?? '#a855f7' }}; border-radius: 10px; padding: 10px 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); font-size: 13px;">
            <div style="font-weight: 700; color: #0f172a;">{{ $tmpl->name }}</div>
            <div style="color: #64748b; font-size: 11.5px; margin-top: 2px;">
                {{ substr($tmpl->start_time, 0, 5) }} – {{ substr($tmpl->end_time, 0, 5) }}
                @if($tmpl->is_overnight)
                    <span class="hr-badge hr-badge-purple" style="font-size: 9.5px; margin-left: 4px;">Overnight (+1 Day)</span>
                @endif
            </div>
        </div>
    @endforeach
</div>

<!-- Weekly Schedule Matrix -->
<div class="hr-table-card">
    <div class="hr-table-header">
        <span class="hr-table-title"><i class="ph ph-table"></i> Weekly Shift Roster</span>
        <div style="display: flex; align-items: center; gap: 10px;">
            <form method="GET" action="{{ route('hr.attendance.schedules') }}" style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Week of:</label>
                <input type="date" name="week_start" class="hr-input" value="{{ $weekStart }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th style="min-width: 180px;">Staff Member</th>
                    @foreach($dates as $d)
                        <th style="text-align: center; min-width: 120px;">
                            {{ \Carbon\Carbon::parse($d)->format('D') }}<br>
                            <small style="color: #94a3b8;">{{ \Carbon\Carbon::parse($d)->format('M d') }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    @php $empScheds = $schedules->get($emp->id) ? $schedules->get($emp->id)->keyBy('schedule_date') : collect(); @endphp
                    <tr>
                        <td>
                            <strong>{{ $emp->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $emp->position?->name }} ({{ $emp->branch?->name }})</small>
                        </td>
                        @foreach($dates as $d)
                            @php $daySched = $empScheds->get($d); @endphp
                            <td style="text-align: center; vertical-align: middle;">
                                @if($daySched)
                                    @if($daySched->is_rest_day)
                                        <span class="hr-badge hr-badge-neutral" style="font-size: 10px;">Rest Day</span>
                                    @elseif($daySched->shiftTemplate)
                                        <div style="background: rgba(168, 85, 247, 0.1); border-radius: 6px; padding: 4px 6px; font-size: 11px; font-weight: 600; color: #7e22ce;">
                                            {{ substr($daySched->shiftTemplate->name, 0, 10) }}<br>
                                            <small style="color: #64748b;">{{ substr($daySched->shiftTemplate->start_time, 0, 5) }} - {{ substr($daySched->shiftTemplate->end_time, 0, 5) }}</small>
                                        </div>
                                    @else
                                        <span style="font-size: 11px; color: #334155;">Custom Shift</span>
                                    @endif
                                @else
                                    <button class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 10px; padding: 2px 6px;" onclick="quickAssign({{ $emp->id }}, '{{ $d }}')">
                                        + Assign
                                    </button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">No employees active for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign Schedule -->
<div id="assignScheduleModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> Assign Employee Shift</span>
            <button class="icon-btn" onclick="closeModal('assignScheduleModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.schedules.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee *</label>
                    <select name="employee_id" id="schedEmployeeId" class="hr-select" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->position?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Schedule Date *</label>
                    <input type="date" name="schedule_date" id="schedDate" class="hr-input" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Shift Template</label>
                    <select name="shift_template_id" class="hr-select">
                        <option value="">Select Shift</option>
                        @foreach($shiftTemplates as $st)
                            <option value="{{ $st->id }}">
                                {{ $st->name }} ({{ substr($st->start_time, 0, 5) }} - {{ substr($st->end_time, 0, 5) }}) {{ $st->is_overnight ? '[Overnight]' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_rest_day" id="isRestDay" value="1">
                    <label for="isRestDay" style="font-size: 13px; font-weight: 600; color: #475569; cursor: pointer;">Designate as Rest Day</label>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Notes</label>
                    <input type="text" name="notes" class="hr-input" placeholder="e.g. Closing kitchen crew, VIP dinner coverage">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('assignScheduleModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Custom Shift Template (Supports Overnight) -->
<div id="addShiftModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-clock-afternoon"></i> Create Custom Shift Template</span>
            <button class="icon-btn" onclick="closeModal('addShiftModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.shifts.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Shift Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Night Closing Shift">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Shift Code *</label>
                    <input type="text" name="code" class="hr-input" required placeholder="e.g. NIGHT-04">
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Time (24h) *</label>
                        <input type="time" name="start_time" class="hr-input" required value="22:00">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Time (24h) *</label>
                        <input type="time" name="end_time" class="hr-input" required value="07:00">
                    </div>
                </div>
                <div class="hr-form-group" style="background: rgba(168, 85, 247, 0.08); padding: 12px; border-radius: 8px; flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_overnight" id="isOvernight" value="1" checked>
                    <label for="isOvernight" style="font-size: 13px; font-weight: 600; color: #6b21a8; cursor: pointer;">
                        Overnight Shift (End time occurs on the following calendar day)
                    </label>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Break Duration (Minutes)</label>
                        <input type="number" name="break_minutes" class="hr-input" required value="60">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Badge Color</label>
                        <input type="color" name="color" class="hr-input" value="#8b5cf6" style="height: 38px; padding: 2px;">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addShiftModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Create Shift Template</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function quickAssign(empId, date) {
    document.getElementById('schedEmployeeId').value = empId;
    document.getElementById('schedDate').value = date;
    openModal('assignScheduleModal');
}
</script>
@endpush
@endsection
