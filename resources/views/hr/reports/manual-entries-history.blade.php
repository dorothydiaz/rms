@extends('layouts.app')

@section('title', 'Manual Time Entries History Report - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Manual Time Entries History</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-pencil-line" style="color: #0284c7;"></i> Manual Time Entries History & Audit Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Historical manual time entries, punch adjustments, administrative corrections, and change tracking</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('hr.reports.export.manual-entries-history', request()->query()) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('hr.attendance.corrections') }}" class="hr-btn hr-btn-primary">
            <i class="ph ph-plus-circle"></i>
            <span>Manual Time Entries</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="hr-card" style="margin-bottom: 18px; padding: 14px 18px;">
    <form method="GET" action="{{ route('hr.reports.manual-entries-history') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
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
            <a href="{{ route('hr.reports.manual-entries-history') }}" class="hr-btn hr-btn-secondary" style="padding: 8px 12px;">
                Reset
            </a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Showing {{ $records->total() }} manual time entries
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Dept</th>
                    <th style="padding: 12px 16px;">Shift 1 Punches</th>
                    <th style="padding: 12px 16px;">Shift 2 Punches</th>
                    <th style="padding: 12px 16px; text-align: right;">Total Hours</th>
                    <th style="padding: 12px 16px;">Reason / Notes</th>
                    <th style="padding: 12px 16px;">Audit Log</th>
                    <th style="padding: 12px 16px;">Date Created</th>
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
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
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
                        <td style="padding: 12px 16px; font-size: 12px; color: #334155;">
                            @if($r->in_1 || $r->out_1)
                                <span style="font-family: monospace; font-size: 11.5px;">{{ $r->in_1 ? date('h:i A', strtotime($r->in_1)) : '--:--' }}</span> &rarr;
                                <span style="font-family: monospace; font-size: 11.5px;">{{ $r->out_1 ? date('h:i A', strtotime($r->out_1)) : '--:--' }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #334155;">
                            @if($r->in_2 || $r->out_2)
                                <span style="font-family: monospace; font-size: 11.5px;">{{ $r->in_2 ? date('h:i A', strtotime($r->in_2)) : '--:--' }}</span> &rarr;
                                <span style="font-family: monospace; font-size: 11.5px;">{{ $r->out_2 ? date('h:i A', strtotime($r->out_2)) : '--:--' }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #0284c7;">
                            {{ number_format($r->total_hours, 2) }} hrs
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #475569; max-width: 200px;">
                            {{ $r->notes ?: ($r->dtr_remarks ?: 'Manual timekeeping adjustment') }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 11.5px;">
                            @if($r->actionLogs->count())
                                @php $latestAction = $r->actionLogs->first(); @endphp
                                <div style="display: flex; align-items: center; gap: 4px; color: #475569;">
                                    <i class="ph ph-shield-check" style="color: #10b981;"></i>
                                    <span>{{ $latestAction->action }} by <strong>{{ $latestAction->actor?->name ?? 'Admin' }}</strong></span>
                                </div>
                            @else
                                <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">Manual Record</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #64748b; white-space: nowrap;">
                            {{ $r->created_at ? $r->created_at->format('M d, Y g:i A') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-clock-counter-clockwise" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No manual time entries found for the selected filter criteria.
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
