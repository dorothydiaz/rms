@extends('layouts.app')

@section('title', 'Tardiness Report - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Employee Tardiness</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-alarm" style="color: #f97316;"></i> All Employees with Tardiness Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Comprehensive ranking and daily breakdown of late arrivals, total minutes lost, and frequency per employee</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('hr.reports.export.tardiness', request()->query()) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('hr.attendance.timekeeping') }}" class="hr-btn hr-btn-primary">
            <i class="ph ph-fingerprint"></i>
            <span>Timekeeping DTR</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="hr-card" style="margin-bottom: 18px; padding: 14px 18px;">
    <form method="GET" action="{{ route('hr.reports.tardiness') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
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
            <a href="{{ route('hr.reports.tardiness') }}" class="hr-btn hr-btn-secondary" style="padding: 8px 12px;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Employee Tardiness Summary Table -->
@if($tardySummary->isNotEmpty() && !request('employee_id'))
    <div class="hr-card" style="margin-bottom: 20px; padding: 18px;">
        <h3 style="font-family: var(--font-heading); font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-chart-bar" style="color: #f97316;"></i> Top Tardiness Summary by Staff ({{ date('M d', strtotime($startDate)) }} - {{ date('M d, Y', strtotime($endDate)) }})
        </h3>
        <div class="hr-table-responsive" style="overflow-x: auto;">
            <table class="hr-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="padding: 10px 14px;">Employee</th>
                        <th style="padding: 10px 14px;">Branch / Position</th>
                        <th style="padding: 10px 14px; text-align: center;">Late Incidents</th>
                        <th style="padding: 10px 14px; text-align: right;">Total Minutes Late</th>
                        <th style="padding: 10px 14px; text-align: right;">Average Late / Incident</th>
                        <th style="padding: 10px 14px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tardySummary->take(5) as $summary)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 14px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #ffedd5; color: #c2410c; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">
                                        {{ strtoupper(substr($summary->employee?->first_name ?? 'E', 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong style="color: #0f172a; font-size: 13px;">{{ $summary->employee?->full_name }}</strong>
                                        <div style="font-size: 11px; color: #64748b;">ID: {{ $summary->employee?->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 14px; font-size: 12px; color: #475569;">
                                {{ $summary->employee?->branch?->name ?? '—' }} &bull; {{ $summary->employee?->position?->name ?? 'Staff' }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <span class="hr-badge hr-badge-neutral" style="font-weight: 700; font-size: 12px;">
                                    {{ $summary->occurrence_count }} times
                                </span>
                            </td>
                            <td style="padding: 10px 14px; text-align: right;">
                                <span class="hr-badge hr-badge-warning" style="font-weight: 700; font-size: 12px;">
                                    {{ $summary->total_late_minutes }} mins
                                </span>
                            </td>
                            <td style="padding: 10px 14px; text-align: right; font-size: 12px; color: #475569;">
                                {{ number_format($summary->avg_late_minutes, 1) }} mins
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <a href="{{ route('hr.reports.tardiness', array_merge(request()->query(), ['employee_id' => $summary->employee_id])) }}" class="hr-btn hr-btn-secondary" style="padding: 4px 8px; font-size: 11.5px;">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Detailed Log Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Total Late Arrival Logs: {{ $records->total() }}
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Dept</th>
                    <th style="padding: 12px 16px;">Actual Time In</th>
                    <th style="padding: 12px 16px; text-align: right;">Late Minutes</th>
                    <th style="padding: 12px 16px; text-align: center;">Status</th>
                    <th style="padding: 12px 16px;">DTR Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr style="border-bottom: 1px solid #f8fafc;">
                        <td style="padding: 12px 16px; font-weight: 600; color: #0f172a; white-space: nowrap;">
                            {{ $r->date ? $r->date->format('M d, Y (D)') : '—' }}
                        </td>
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #ffedd5; color: #ea580c; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                    {{ strtoupper(substr($r->employee?->first_name ?? 'E', 0, 1) . substr($r->employee?->last_name ?? 'M', 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">{{ $r->employee?->full_name ?? '—' }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">ID: {{ $r->employee?->employee_id ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12.5px;">
                            <div style="font-weight: 500; color: #334155;">{{ $r->employee?->branch?->name ?? '—' }}</div>
                            <div style="color: #64748b; font-size: 11.5px;">{{ $r->employee?->department?->name ?? '—' }}</div>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12.5px; font-weight: 600; color: #0f172a;">
                            {{ $r->time_in ? date('h:i A', strtotime($r->time_in)) : '—' }}
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <span class="hr-badge hr-badge-warning" style="font-size: 12.5px; font-weight: 700; padding: 4px 9px;">
                                {{ $r->late_minutes }} mins
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11.5px; background: #fff7ed; color: #c2410c; border-color: #ffedd5;">
                                Late Arrival
                            </span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #64748b; max-width: 220px;">
                            {{ $r->dtr_remarks ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-check-circle" style="font-size: 32px; color: #10b981; display: block; margin-bottom: 8px;"></i>
                            No tardiness records found for the selected filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div style="padding: 12px 18px; border-top: 1px solid #f1f5f9;">
            {{ $records->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>
@endsection
