@extends('layouts.app')

@section('title', 'Timekeeping Station - Attendance Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-clock"></i>
            Restaurant Timekeeping Station
        </h1>
        <p class="hr-page-subtitle">Record and review Time In, Break Out, Break In, and Time Out for staff shifts</p>
    </div>
    <div class="hr-page-actions">
        <form method="GET" action="{{ route('hr.attendance.timekeeping') }}" style="display: flex; gap: 8px; align-items: center;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Select Date:</label>
            <input type="date" name="date" class="hr-input" value="{{ $date }}" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-header">
        <span class="hr-table-title"><i class="ph ph-calendar-blank"></i> Staff Time Logs for {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</span>
        <span class="hr-badge hr-badge-neutral">{{ count($employees) }} Scheduled Staff</span>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Staff Name</th>
                    <th>Branch & Dept</th>
                    <th>Time In</th>
                    <th>Break Out</th>
                    <th>Break In</th>
                    <th>Time Out</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                    <th style="text-align: right;">Manual Punch</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    @php $att = $attendanceMap->get($emp->id); @endphp
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $emp->employee_id }}</strong></td>
                        <td>
                            <strong>{{ $emp->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $emp->position?->name }}</small>
                        </td>
                        <td>
                            <div>{{ $emp->branch?->name }}</div>
                            <small style="color: #64748b;">{{ $emp->department?->name }}</small>
                        </td>
                        <td>
                            @if($att && $att->time_in)
                                <strong style="color: #059669;">{{ substr($att->time_in, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>
                        <td>
                            @if($att && $att->break_out)
                                <span>{{ substr($att->break_out, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>
                        <td>
                            @if($att && $att->break_in)
                                <span>{{ substr($att->break_in, 0, 5) }}</span>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>
                        <td>
                            @if($att && $att->time_out)
                                <strong style="color: #0284c7;">{{ substr($att->time_out, 0, 5) }}</strong>
                            @else
                                <span style="color: #cbd5e1;">--:--</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ number_format($att?->total_hours ?? 0, 2) }} hrs</strong>
                        </td>
                        <td>
                            @if($att)
                                @if($att->status === 'Present')
                                    <span class="hr-badge hr-badge-success">{{ $att->status }}</span>
                                @elseif($att->status === 'Late')
                                    <span class="hr-badge hr-badge-warning">{{ $att->status }} ({{ $att->late_minutes }}m)</span>
                                @elseif($att->status === 'Rest Day')
                                    <span class="hr-badge hr-badge-neutral">{{ $att->status }}</span>
                                @else
                                    <span class="hr-badge hr-badge-danger">{{ $att->status }}</span>
                                @endif
                            @else
                                <span class="hr-badge hr-badge-neutral">Pending</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPunchModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}')">
                                <i class="ph ph-fingerprint"></i> Punch
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 30px;">No employees active for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Record Time Punch -->
<div id="punchModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-clock"></i> Record Time Punch</span>
            <button class="icon-btn" onclick="closeModal('punchModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.timekeeping.store') }}">
            @csrf
            <input type="hidden" id="punchEmployeeId" name="employee_id" value="">
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="hr-modal-body">
                <div style="background: rgba(147, 51, 234, 0.08); border-radius: 10px; padding: 10px 14px; font-weight: 700; color: #6b21a8;" id="punchEmployeeName">
                    Employee Name
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Punch Type *</label>
                    <select name="punch_type" class="hr-select" required>
                        <option value="time_in">Time In (Clock In)</option>
                        <option value="break_out">Break Out (Lunch / Rest)</option>
                        <option value="break_in">Break In (Resume Shift)</option>
                        <option value="time_out">Time Out (Clock Out)</option>
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Time (24-Hour Format) *</label>
                    <input type="time" name="time" class="hr-input" required value="{{ date('H:i') }}">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Notes / Source</label>
                    <input type="text" name="notes" class="hr-input" placeholder="e.g. Biometric bypass, Manager manual entry">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('punchModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Record Punch</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openPunchModal(empId, empName) {
    document.getElementById('punchEmployeeId').value = empId;
    document.getElementById('punchEmployeeName').innerText = empName;
    openModal('punchModal');
}
</script>
@endpush
@endsection
