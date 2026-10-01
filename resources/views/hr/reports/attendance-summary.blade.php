@extends('layouts.app')

@section('title', 'Attendance Summary Report - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Attendance Summary</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-table" style="color: #10b981;"></i> Attendance Summary Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Aggregated attendance metrics, present days, tardiness, undertime, overtime, and work hours by employee</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('hr.reports.export.attendance-summary', request()->query()) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('hr.attendance.timekeeping') }}" class="hr-btn hr-btn-primary">
            <i class="ph ph-calendar-check"></i>
            <span>Timekeeping DTR</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="hr-card" style="margin-bottom: 18px; padding: 14px 18px;">
    <form method="GET" action="{{ route('hr.reports.attendance-summary') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div style="min-width: 150px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 4px; font-size: 12px;">Date From</label>
            <input type="date" name="date_from" value="{{ $startDate }}" class="hr-input" style="padding: 7px 10px; font-size: 13px;">
        </div>
        <div style="min-width: 150px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 4px; font-size: 12px;">Date To</label>
            <input type="date" name="date_to" value="{{ $endDate }}" class="hr-input" style="padding: 7px 10px; font-size: 13px;">
        </div>
        <div style="min-width: 180px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 4px; font-size: 12px;">Branch</label>
            <select name="branch_id" class="hr-select" style="padding: 7px 10px; font-size: 13px;">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 200px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 4px; font-size: 12px;">Employee</label>
            <select name="employee_id" class="hr-select" style="padding: 7px 10px; font-size: 13px;">
                <option value="">All Employees</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->full_name }} ({{ $e->employee_id }})</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="hr-btn hr-btn-primary" style="padding: 8px 16px;">
                <i class="ph ph-funnel"></i> Filter
            </button>
            <a href="{{ route('hr.reports.attendance-summary') }}" class="hr-btn hr-btn-secondary" style="padding: 8px 12px;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Period: {{ date('M d, Y', strtotime($startDate)) }} &mdash; {{ date('M d, Y', strtotime($endDate)) }} &bull; Employees: {{ $summary->total() }}
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 14px;">Employee</th>
                    <th style="padding: 12px 14px;">Branch / Position</th>
                    <th style="padding: 12px 14px; text-align: center;">Days Logged</th>
                    <th style="padding: 12px 14px; text-align: center;">Present</th>
                    <th style="padding: 12px 14px; text-align: center;">Late</th>
                    <th style="padding: 12px 14px; text-align: right;">Undertime</th>
                    <th style="padding: 12px 14px; text-align: right;">Overtime</th>
                    <th style="padding: 12px 14px; text-align: center;">Absences</th>
                    <th style="padding: 12px 14px; text-align: right;">Total Hours</th>
                    <th style="padding: 12px 14px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summary as $s)
                    <tr style="border-bottom: 1px solid #f8fafc;">
                        <td style="padding: 12px 14px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #ecfdf5; color: #047857; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                    {{ strtoupper(substr($s->employee?->first_name ?? 'E', 0, 1) . substr($s->employee?->last_name ?? 'M', 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">{{ $s->employee?->full_name ?? '—' }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">ID: {{ $s->employee?->employee_id ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 14px; font-size: 12px;">
                            <div style="font-weight: 500; color: #334155;">{{ $s->employee?->branch?->name ?? '—' }}</div>
                            <div style="color: #64748b; font-size: 11px;">{{ $s->employee?->position?->name ?? 'Staff' }}</div>
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-weight: 500; font-size: 12.5px;">
                            {{ $s->logged_days }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span class="hr-badge hr-badge-success" style="font-size: 11.5px; padding: 3px 8px;">
                                {{ $s->present_days }} d
                            </span>
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: 12px;">
                            @if($s->late_occurrences > 0)
                                <span class="hr-badge hr-badge-warning" style="font-size: 11px; padding: 2px 6px;">
                                    {{ $s->late_occurrences }}x ({{ $s->total_late_minutes }}m)
                                </span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-size: 12px;">
                            @if($s->total_undertime_minutes > 0)
                                <span style="color: #d97706; font-weight: 600;">{{ $s->total_undertime_minutes }} mins</span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-size: 12px;">
                            @if($s->total_overtime_hours > 0)
                                <span class="hr-badge hr-badge-purple" style="font-size: 11px; padding: 2px 7px;">
                                    +{{ number_format($s->total_overtime_hours, 1) }}h
                                </span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-size: 12px;">
                            @if($s->total_absences > 0)
                                <span class="hr-badge hr-badge-danger" style="font-size: 11px; padding: 2px 6px;">
                                    {{ $s->total_absences }} d
                                </span>
                            @else
                                <span style="color: #94a3b8;">0</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: #0f172a; font-size: 13px;">
                            {{ number_format($s->total_hours, 1) }} hrs
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <div style="display: flex; gap: 4px; justify-content: center;">
                                <a href="{{ route('hr.reports.individual-attendance-summary', ['employee_id' => $s->employee_id, 'date_from' => $startDate, 'date_to' => $endDate]) }}" class="hr-btn hr-btn-secondary" style="padding: 4px 8px; font-size: 11px;" title="View Individual DTR">
                                    <i class="ph ph-user"></i> DTR
                                </a>
                                <a href="{{ route('hr.reports.employee-attendance-profile', ['employee_id' => $s->employee_id]) }}" class="hr-btn hr-btn-secondary" style="padding: 4px 8px; font-size: 11px;" title="View Profile">
                                    <i class="ph ph-identification-badge"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-table" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No attendance records found for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($summary->hasPages())
        <div style="padding: 12px 18px; border-top: 1px solid #f1f5f9;">
            {{ $summary->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>
@endsection
