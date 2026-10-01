@extends('layouts.app')

@section('title', 'Individual Attendance Summary - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Individual Attendance Summary</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-user-list" style="color: #0ea5e9;"></i> Individual Attendance Summary Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">In-depth DTR statement, daily attendance log, and cumulative hours for an individual employee</p>
    </div>
    @if($employee)
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('hr.reports.export.individual-attendance-summary', ['employee_id' => $employee->id, 'date_from' => $startDate, 'date_to' => $endDate]) }}" class="hr-btn hr-btn-secondary">
                <i class="ph ph-download-simple"></i>
                <span>Export Individual CSV</span>
            </a>
            <a href="{{ route('hr.reports.employee-attendance-profile', ['employee_id' => $employee->id]) }}" class="hr-btn hr-btn-primary">
                <i class="ph ph-identification-card"></i>
                <span>Full Attendance Profile</span>
            </a>
        </div>
    @endif
</div>

<!-- Employee & Date Selection Card -->
<div class="hr-card" style="margin-bottom: 20px; padding: 16px 20px;">
    <form method="GET" action="{{ route('hr.reports.individual-attendance-summary') }}" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
        <div style="min-width: 280px; flex: 2;">
            <label class="hr-form-label" style="margin-bottom: 5px; font-size: 12.5px; font-weight: 600;">Select Employee *</label>
            <select name="employee_id" class="hr-select" required style="padding: 8px 12px; font-size: 13.5px;">
                <option value="">-- Choose Employee --</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ ($employee && $employee->id == $e->id) || request('employee_id') == $e->id ? 'selected' : '' }}>
                        {{ $e->full_name }} &bull; {{ $e->employee_id }} ({{ $e->branch?->name ?? 'All Branches' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 150px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 5px; font-size: 12px;">Start Date</label>
            <input type="date" name="date_from" value="{{ $startDate }}" class="hr-input" style="padding: 7px 10px; font-size: 13px;">
        </div>
        <div style="min-width: 150px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 5px; font-size: 12px;">End Date</label>
            <input type="date" name="date_to" value="{{ $endDate }}" class="hr-input" style="padding: 7px 10px; font-size: 13px;">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="hr-btn hr-btn-primary" style="padding: 8px 18px;">
                <i class="ph ph-magnifying-glass"></i> Generate Report
            </button>
        </div>
    </form>
</div>

@if($employee)
    <!-- Employee Header Profile Info -->
    <div class="hr-card" style="margin-bottom: 20px; padding: 18px 22px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #0284c7;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #0284c7; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px;">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">
                        {{ $employee->full_name }}
                    </h2>
                    <div style="font-size: 12.5px; color: #64748b; margin-top: 3px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <span><strong>ID:</strong> {{ $employee->employee_id }}</span>
                        <span><strong>Branch:</strong> {{ $employee->branch?->name ?? '—' }}</span>
                        <span><strong>Dept:</strong> {{ $employee->department?->name ?? '—' }}</span>
                        <span><strong>Position:</strong> {{ $employee->position?->name ?? 'Staff' }}</span>
                    </div>
                </div>
            </div>
            <div>
                <span class="hr-badge hr-badge-neutral" style="font-size: 12px; padding: 6px 12px; background: #ffffff; border: 1px solid #e2e8f0;">
                    <i class="ph ph-clock" style="color: #6366f1;"></i> Default Shift: <strong>{{ $employee->defaultShiftTemplate?->name ?? 'None Assigned' }}</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Stat Metric Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div class="hr-stat-card hr-stat-card-green">
            <div class="hr-stat-icon-wrap"><i class="ph ph-calendar-check"></i></div>
            <div class="hr-stat-content">
                <span class="hr-stat-label">Present Days</span>
                <div class="hr-stat-value">{{ $stats['present_days'] }} <span style="font-size: 12px; font-weight: 500;">/ {{ $stats['total_days'] }}</span></div>
                <span class="hr-stat-sub">{{ $stats['rest_days'] }} scheduled rest days</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card-purple">
            <div class="hr-stat-icon-wrap"><i class="ph ph-clock"></i></div>
            <div class="hr-stat-content">
                <span class="hr-stat-label">Total Rendered</span>
                <div class="hr-stat-value">{{ number_format($stats['total_hours'], 1) }} <span style="font-size: 12px; font-weight: 500;">hrs</span></div>
                <span class="hr-stat-sub">Across full period</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card-amber">
            <div class="hr-stat-icon-wrap"><i class="ph ph-alarm"></i></div>
            <div class="hr-stat-content">
                <span class="hr-stat-label">Total Late</span>
                <div class="hr-stat-value">{{ $stats['late_minutes'] }} <span style="font-size: 12px; font-weight: 500;">mins</span></div>
                <span class="hr-stat-sub">Tardiness accumulated</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card-amber">
            <div class="hr-stat-icon-wrap"><i class="ph ph-timer"></i></div>
            <div class="hr-stat-content">
                <span class="hr-stat-label">Total Undertime</span>
                <div class="hr-stat-value">{{ $stats['undertime_minutes'] }} <span style="font-size: 12px; font-weight: 500;">mins</span></div>
                <span class="hr-stat-sub">Early departures</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card-purple">
            <div class="hr-stat-icon-wrap"><i class="ph ph-clock-countdown"></i></div>
            <div class="hr-stat-content">
                <span class="hr-stat-label">Approved OT</span>
                <div class="hr-stat-value">{{ number_format($stats['overtime_hours'], 1) }} <span style="font-size: 12px; font-weight: 500;">hrs</span></div>
                <span class="hr-stat-sub">Overtime rendered</span>
            </div>
        </div>

        <div class="hr-stat-card hr-stat-card-rose">
            <div class="hr-stat-icon-wrap"><i class="ph ph-user-minus"></i></div>
            <div class="hr-stat-content">
                <span class="hr-stat-label">Absences</span>
                <div class="hr-stat-value">{{ $stats['absence_days'] }} <span style="font-size: 12px; font-weight: 500;">days</span></div>
                <span class="hr-stat-sub">Unauthorized / unexcused</span>
            </div>
        </div>
    </div>

    <!-- DTR Table Card -->
    <div class="hr-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 13.5px; font-weight: 600; color: #334155;">
                Detailed Daily Attendance Record ({{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }})
            </span>
        </div>
        <div class="hr-table-responsive" style="overflow-x: auto;">
            <table class="hr-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 12px 16px;">Date</th>
                        <th style="padding: 12px 16px;">Day</th>
                        <th style="padding: 12px 16px;">Time In</th>
                        <th style="padding: 12px 16px;">Time Out</th>
                        <th style="padding: 12px 16px; text-align: right;">Late</th>
                        <th style="padding: 12px 16px; text-align: right;">Undertime</th>
                        <th style="padding: 12px 16px; text-align: right;">Overtime</th>
                        <th style="padding: 12px 16px; text-align: right;">Total Hours</th>
                        <th style="padding: 12px 16px; text-align: center;">Status</th>
                        <th style="padding: 12px 16px;">Notes / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 12px 16px; font-weight: 600; color: #0f172a; white-space: nowrap;">
                                {{ $r->date ? $r->date->format('M d, Y') : '—' }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 12.5px; color: #64748b;">
                                {{ $r->date ? $r->date->format('l') : '—' }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 12.5px; font-weight: 500;">
                                {{ $r->time_in ? date('h:i A', strtotime($r->time_in)) : '—' }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 12.5px; font-weight: 500;">
                                {{ $r->time_out ? date('h:i A', strtotime($r->time_out)) : '—' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                @if($r->late_minutes > 0)
                                    <span class="hr-badge hr-badge-warning" style="font-size: 11.5px; padding: 2px 7px;">
                                        {{ $r->late_minutes }}m
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                @if($r->undertime_minutes > 0)
                                    <span style="color: #d97706; font-weight: 600; font-size: 12px;">{{ $r->undertime_minutes }}m</span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: right;">
                                @if($r->overtime_hours > 0)
                                    <span class="hr-badge hr-badge-purple" style="font-size: 11.5px; padding: 2px 7px;">
                                        +{{ number_format($r->overtime_hours, 1) }}h
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: #0f172a; font-size: 13px;">
                                {{ number_format($r->total_hours, 1) }} hrs
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if($r->is_rest_day)
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">Rest Day</span>
                                @elseif($r->status === 'Present' || $r->regular_hours > 0)
                                    <span class="hr-badge hr-badge-success" style="font-size: 11px;">Present</span>
                                @elseif($r->status === 'Absent')
                                    <span class="hr-badge hr-badge-danger" style="font-size: 11px;">Absent</span>
                                @else
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">{{ $r->status ?: 'Recorded' }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; font-size: 12px; color: #64748b; max-width: 200px;">
                                {{ $r->notes ?: ($r->dtr_remarks ?: '—') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 36px; color: #94a3b8;">
                                No attendance records logged for this employee in the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <!-- Empty State Prompt to Select Employee -->
    <div class="hr-card" style="text-align: center; padding: 48px 24px;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px auto;">
            <i class="ph ph-user-circle"></i>
        </div>
        <h3 style="font-family: var(--font-heading); font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
            Select an Employee to View Individual Attendance
        </h3>
        <p style="font-size: 13.5px; color: #64748b; max-width: 500px; margin: 0 auto 20px auto;">
            Choose an employee from the dropdown filter above to inspect their complete daily time record (DTR), late arrivals, overtime, and undertime hours.
        </p>
    </div>
@endif
@endsection
