@extends('layouts.app')

@section('title', 'Change of Schedule History Report - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Change of Schedule History</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-calendar-blank" style="color: #3b82f6;"></i> Change of Schedule History Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Comprehensive audit trail of employee shift adjustments, default schedule assignments, and schedule swaps</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('hr.reports.export.change-of-schedule', request()->query()) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('hr.attendance.schedules') }}" class="hr-btn hr-btn-primary">
            <i class="ph ph-calendar"></i>
            <span>Manage Schedules</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="hr-card" style="margin-bottom: 18px; padding: 14px 18px;">
    <form method="GET" action="{{ route('hr.reports.change-of-schedule') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div style="min-width: 150px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 4px; font-size: 12px;">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="hr-input" style="padding: 7px 10px; font-size: 13px;">
        </div>
        <div style="min-width: 150px; flex: 1;">
            <label class="hr-form-label" style="margin-bottom: 4px; font-size: 12px;">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="hr-input" style="padding: 7px 10px; font-size: 13px;">
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
            <a href="{{ route('hr.reports.change-of-schedule') }}" class="hr-btn hr-btn-secondary" style="padding: 8px 12px;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Showing {{ $logs->total() }} schedule change log entries
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Schedule Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Dept</th>
                    <th style="padding: 12px 16px;">Previous Schedule</th>
                    <th style="padding: 12px 16px;">New Schedule</th>
                    <th style="padding: 12px 16px;">Reason / Type</th>
                    <th style="padding: 12px 16px;">Changed By</th>
                    <th style="padding: 12px 16px;">Date & Time Modified</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $l)
                    <tr style="border-bottom: 1px solid #f8fafc;">
                        <td style="padding: 12px 16px; font-weight: 600; color: #0f172a; white-space: nowrap;">
                            {{ $l->schedule_date ? $l->schedule_date->format('M d, Y (D)') : 'Recurring Default' }}
                        </td>
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                    {{ strtoupper(substr($l->employee?->first_name ?? 'E', 0, 1) . substr($l->employee?->last_name ?? 'M', 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">{{ $l->employee?->full_name ?? '—' }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">ID: {{ $l->employee?->employee_id ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12.5px;">
                            <div style="font-weight: 500; color: #334155;">{{ $l->employee?->branch?->name ?? '—' }}</div>
                            <div style="color: #64748b; font-size: 11.5px;">{{ $l->employee?->department?->name ?? '—' }}</div>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 12px; padding: 3px 8px;">
                                {{ $l->previous_time_range ?: 'None / Unassigned' }}
                            </span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="hr-badge hr-badge-success" style="font-size: 12px; padding: 3px 8px; font-weight: 600;">
                                {{ $l->new_time_range ?: 'Off / Unassigned' }}
                            </span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12.5px; color: #475569; max-width: 200px;">
                            {{ $l->reason ?: 'Schedule Update' }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-user-circle" style="color: #6366f1; font-size: 16px;"></i>
                                <span style="font-weight: 600; color: #1e293b;">{{ $l->changer?->name ?? 'Administrator' }}</span>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #64748b; white-space: nowrap;">
                            {{ $l->created_at ? $l->created_at->format('M d, Y g:i A') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-calendar-x" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No schedule change history records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div style="padding: 12px 18px; border-top: 1px solid #f1f5f9;">
            {{ $logs->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>
@endsection
