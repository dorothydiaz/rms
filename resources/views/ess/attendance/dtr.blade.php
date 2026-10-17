@extends('layouts.ess')

@section('title', 'Daily Time Record (DTR)')

@section('content')
<div class="ess-dtr-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-clock"></i>
                <span>Daily Time Record (DTR) & Biometrics</span>
            </h1>
            <p class="hr-page-subtitle">Official biometric punch log and attendance calculation verified under DOLE Labor Standards</p>
        </div>

        <div class="hr-page-actions">
            <form method="GET" action="{{ route('ess.dtr') }}" style="display:flex;align-items:center;gap:8px;margin:0;">
                <input type="month" name="month" value="{{ $month }}" class="ess-form-control" style="width:auto;padding:8px 12px;font-weight:600;" onchange="this.form.submit()">
            </form>

            <a href="{{ route('ess.dtr.export-pdf', ['month' => $month]) }}" target="_blank" class="hr-btn hr-btn-secondary" title="Print official DTR PDF">
                <i class="ph ph-printer"></i>
                <span>Print DTR</span>
            </a>
            <a href="{{ route('ess.requests') }}" class="hr-btn hr-btn-primary">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>File Correction</span>
            </a>
        </div>
    </div>

    <!-- Summary KPI Stat Cards -->
    <div class="ess-grid-4">
        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box green">
                <i class="ph ph-briefcase"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Regular Hours</span>
                <span class="ess-kpi-val">{{ number_format($totalRegHours, 1) }} hrs</span>
            </div>
        </div>

        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box purple">
                <i class="ph ph-clock-afternoon"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Overtime Hours</span>
                <span class="ess-kpi-val">{{ number_format($totalOtHours, 1) }} hrs</span>
            </div>
        </div>

        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box indigo">
                <i class="ph ph-moon"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Night Diff (10%)</span>
                <span class="ess-kpi-val">{{ number_format($totalNightDiffHours, 1) }} hrs</span>
            </div>
        </div>

        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box orange">
                <i class="ph ph-warning"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Tardiness / Undertime</span>
                <span class="ess-kpi-val" style="color:#ea580c;">{{ $totalLateMinutes + $totalUndertimeMinutes }} mins</span>
            </div>
        </div>
    </div>

    <!-- DTR Log Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h3 class="ess-card-title">
                <i class="ph ph-table" style="color:var(--ess-primary);"></i>
                Attendance Log Sheet for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
            </h3>
            <span style="font-size:12px;color:var(--ess-text-muted);">
                Employee ID: <strong>{{ $employee->employee_id }}</strong>
            </span>
        </div>

        <div class="ess-table-container">
            <table class="ess-table">
                <thead>
                    <tr>
                        <th>Date & Day</th>
                        <th>Status</th>
                        <th>In 1</th>
                        <th>Break Out</th>
                        <th>Break In</th>
                        <th>Out 2</th>
                        <th>Regular</th>
                        <th>Overtime</th>
                        <th>Night Diff</th>
                        <th>Late</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($rec->date)->format('M d') }}</strong>
                                <span style="font-size:11px;color:var(--ess-text-muted);margin-left:4px;">{{ \Carbon\Carbon::parse($rec->date)->format('D') }}</span>
                            </td>
                            <td>
                                @if($rec->status === 'Present')
                                    <span class="ess-badge ess-badge-success">Present</span>
                                @elseif($rec->status === 'Late')
                                    <span class="ess-badge ess-badge-warning">Late</span>
                                @elseif($rec->status === 'Absent')
                                    <span class="ess-badge ess-badge-danger">Absent</span>
                                @elseif($rec->status === 'Rest Day' || $rec->is_rest_day)
                                    <span class="ess-badge" style="background:#f1f5f9;color:#475569;">Rest Day</span>
                                @else
                                    <span class="ess-badge ess-badge-info">{{ $rec->status ?? 'Recorded' }}</span>
                                @endif
                            </td>
                            <td>{{ $rec->time_in ? \Carbon\Carbon::parse($rec->time_in)->format('h:i A') : '—' }}</td>
                            <td>{{ $rec->break_out ? \Carbon\Carbon::parse($rec->break_out)->format('h:i A') : '—' }}</td>
                            <td>{{ $rec->break_in ? \Carbon\Carbon::parse($rec->break_in)->format('h:i A') : '—' }}</td>
                            <td>{{ $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('h:i A') : '—' }}</td>
                            <td><strong>{{ number_format((float)($rec->regular_hours ?? 0), 1) }}</strong></td>
                            <td style="color:var(--ess-primary);font-weight:700;">{{ number_format((float)($rec->overtime_hours ?? 0), 1) }}</td>
                            <td style="color:#6366f1;font-weight:600;">{{ number_format((float)($rec->calculated_nsd_hours ?? 0), 1) }}</td>
                            <td style="{{ $rec->late_minutes > 0 ? 'color:var(--ess-danger);font-weight:700;' : '' }}">
                                {{ $rec->late_minutes > 0 ? $rec->late_minutes . 'm' : '—' }}
                            </td>
                            <td>
                                <button type="button" class="ess-badge ess-badge-warning" style="cursor:pointer;border:none;" onclick="openCorrectionModal('{{ $rec->id }}', '{{ \Carbon\Carbon::parse($rec->date)->format('Y-m-d') }}', '{{ $rec->time_in ?? '' }}', '{{ $rec->time_out ?? '' }}')">
                                    <i class="ph ph-pencil-simple"></i>
                                    <span>Correct</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align:center;padding:40px;color:var(--ess-text-muted);">
                                No attendance records found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance Correction Modal -->
<div id="dtrCorrectionModal" class="ess-modal-backdrop">
    <div class="ess-modal">
        <form method="POST" action="{{ route('ess.dtr.correction') }}">
            @csrf
            <div class="ess-modal-header">
                <h3 class="ess-modal-title">File Attendance Correction</h3>
                <button type="button" class="ess-modal-close" onclick="closeCorrectionModal()">&times;</button>
            </div>
            <div class="ess-modal-body">
                <input type="hidden" name="attendance_record_id" id="modalRecordId">

                <div class="ess-form-group">
                    <label class="ess-form-label">Record Date</label>
                    <input type="text" id="modalRecordDate" class="ess-form-control" readonly style="background:#f8fafc;">
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div class="ess-form-group">
                        <label class="ess-form-label">Corrected Time-In *</label>
                        <input type="time" name="corrected_time_in" id="modalTimeIn" class="ess-form-control" required>
                    </div>
                    <div class="ess-form-group">
                        <label class="ess-form-label">Corrected Time-Out *</label>
                        <input type="time" name="corrected_time_out" id="modalTimeOut" class="ess-form-control" required>
                    </div>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Reason Category *</label>
                    <select name="reason_category" class="ess-form-control" required>
                        <option value="Biometric Machine Error">Biometric Machine Error / Fingerprint Failure</option>
                        <option value="Official Business / Field Work">Official Business / Field Work Offsite</option>
                        <option value="Forgot to Punch">Forgot to Punch In / Out</option>
                        <option value="Power Outage / Network Offline">Power Outage / Device Offline</option>
                        <option value="Other Legitimate Reason">Other Legitimate Reason</option>
                    </select>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Detailed Explanation *</label>
                    <textarea name="explanation" class="ess-form-control" rows="3" placeholder="State circumstances for missing or inaccurate punch..." required></textarea>
                </div>
            </div>
            <div class="ess-modal-footer">
                <button type="button" class="ess-form-control" style="width:auto;cursor:pointer;" onclick="closeCorrectionModal()">Cancel</button>
                <button type="submit" class="ess-quick-btn">Submit Request</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCorrectionModal(id, date, inTime, outTime) {
        document.getElementById('modalRecordId').value = id;
        document.getElementById('modalRecordDate').value = date;
        document.getElementById('modalTimeIn').value = inTime ? inTime.substring(0, 5) : '08:00';
        document.getElementById('modalTimeOut').value = outTime ? outTime.substring(0, 5) : '17:00';
        document.getElementById('dtrCorrectionModal').classList.add('open');
    }
    function closeCorrectionModal() {
        document.getElementById('dtrCorrectionModal').classList.remove('open');
    }
</script>
@endpush
@endsection
