@extends('layouts.app')

@section('title', 'Daily Time Record (DTR) - Attendance Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-calendar-check"></i>
            Daily Time Record (DTR)
        </h1>
        <p class="hr-page-subtitle">Detailed breakdown of daily work hours, late deductions, undertime, overtime, and night shift differential (10 PM - 6 AM)</p>
    </div>
    <div class="hr-page-actions">
        <a href="{{ route('hr.reports.export.attendance', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export DTR CSV</span>
        </a>
    </div>
</div>

<!-- DTR Summary KPI Cards -->
<div class="hr-metrics-grid" style="margin-bottom: 20px;">
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ number_format($totals['hours'], 2) }}</span>
            <span class="hr-metric-label">Total Rendered Hours</span>
        </div>
        <div class="hr-metric-icon emerald"><i class="ph ph-clock"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #f59e0b;">{{ number_format($totals['late_min']) }} min</span>
            <span class="hr-metric-label">Accumulated Tardiness</span>
        </div>
        <div class="hr-metric-icon amber"><i class="ph ph-timer"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #6366f1;">{{ number_format($totals['ot_hours'], 2) }} hrs</span>
            <span class="hr-metric-label">Overtime Hours</span>
        </div>
        <div class="hr-metric-icon indigo"><i class="ph ph-trend-up"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #9333ea;">{{ number_format($totals['nd_hours'], 2) }} hrs</span>
            <span class="hr-metric-label">Night Differential (10PM-6AM)</span>
        </div>
        <div class="hr-metric-icon purple"><i class="ph ph-moon-stars"></i></div>
    </div>
</div>

<!-- Filter Bar -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.attendance.dtr') }}" class="hr-filter-form">
        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">From:</label>
            <input type="date" name="start_date" class="hr-input" value="{{ $startDate }}">
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">To:</label>
            <input type="date" name="end_date" class="hr-input" value="{{ $endDate }}">
        </div>

        @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
            <select name="branch_id" class="hr-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif

        <select name="employee_id" class="hr-select">
            <option value="">All Employees</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->full_name }}</option>
            @endforeach
        </select>

        <select name="status" class="hr-select">
            <option value="">All Statuses</option>
            @foreach(['Present', 'Late', 'Half Day', 'Absent', 'Rest Day', 'On Leave', 'Holiday'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-magnifying-glass"></i>
            <span>Filter</span>
        </button>
    </form>
</div>

<!-- DTR Grid Table -->
<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>In</th>
                    <th>Out</th>
                    <th>Total</th>
                    <th>Late</th>
                    <th>Under</th>
                    <th>OT</th>
                    <th>Night Diff</th>
                    <th>Type</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }}</strong><br>
                            <small style="color: #64748b;">{{ \Carbon\Carbon::parse($r->date)->format('l') }}</small>
                        </td>
                        <td>
                            <strong>{{ $r->employee?->full_name }}</strong><br>
                            <small style="color: #9333ea; font-family: monospace;">{{ $r->employee?->employee_id }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $r->employee?->branch?->name }}</span></td>
                        <td>{{ $r->time_in ? substr($r->time_in, 0, 5) : '--:--' }}</td>
                        <td>{{ $r->time_out ? substr($r->time_out, 0, 5) : '--:--' }}</td>
                        <td><strong>{{ number_format($r->total_hours, 2) }} hrs</strong></td>
                        <td>
                            @if($r->late_minutes > 0)
                                <span style="color: #f59e0b; font-weight: 600;">{{ $r->late_minutes }}m</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->undertime_minutes > 0)
                                <span style="color: #ef4444; font-weight: 600;">{{ $r->undertime_minutes }}m</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->overtime_hours > 0)
                                <span style="color: #6366f1; font-weight: 700;">+{{ number_format($r->overtime_hours, 2) }}</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->night_diff_hours > 0)
                                <span style="color: #9333ea; font-weight: 700;">{{ number_format($r->night_diff_hours, 2) }}h</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($r->is_rest_day)
                                <span class="hr-badge hr-badge-neutral">Rest Day</span>
                            @elseif($r->holiday_type !== 'None')
                                <span class="hr-badge hr-badge-purple">{{ $r->holiday_type }}</span>
                            @else
                                <span style="font-size: 11px; color: #64748b;">Regular</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($r->status === 'Present')
                                <span class="hr-badge hr-badge-success">{{ $r->status }}</span>
                            @elseif($r->status === 'Late')
                                <span class="hr-badge hr-badge-warning">{{ $r->status }}</span>
                            @elseif($r->status === 'Rest Day')
                                <span class="hr-badge hr-badge-neutral">{{ $r->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-danger">{{ $r->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="text-align: center; color: #94a3b8; padding: 30px;">No attendance records found for this date range.</td></tr>
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
@endsection
