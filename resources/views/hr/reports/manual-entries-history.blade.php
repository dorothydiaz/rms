@extends('layouts.app')

@section('title', 'Manual Time Entries History Report - Reports & Analytics')

@section('content')
<x-report-header 
    title="Manual Time Entries History & Audit Report" 
    breadcrumb="Manual Time Entries History"
    subtitle="Historical manual time entries, punch adjustments, administrative corrections, and change tracking">
    <a href="{{ route('hr.reports.export.manual-entries-history', request()->query()) }}" class="hr-btn hr-btn-secondary">
        <i class="ph ph-download-simple"></i>
        <span>Export CSV</span>
    </a>
    <a href="{{ route('hr.attendance.corrections') }}" class="hr-btn hr-btn-primary">
        <i class="ph ph-plus-circle"></i>
        <span>Manual Time Entries</span>
    </a>
</x-report-header>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.manual-entries-history')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :startDate="request('date_from')"
    :endDate="request('date_to')"
    :showStatus="true"
    :showSource="true"
/>

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
