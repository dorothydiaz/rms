@extends('layouts.app')

@section('title', 'Tardiness Report - Reports & Analytics')

@section('content')
<x-report-header 
    title="Employee Tardiness"
    icon="ph-alarm"
    subtitle="Late arrivals & cumulative minutes delay"
    description="Comprehensive breakdown of employee late punch logs, cumulative tardiness minutes, and grace period exceedances."
>
    <a href="{{ route('hr.reports.export.tardiness', request()->query()) }}" class="hr-btn hr-btn-secondary">
        <i class="ph ph-download-simple"></i>
        <span>Export CSV</span>
    </a>
    <a href="{{ route('hr.attendance.timekeeping') }}" class="hr-btn hr-btn-primary">
        <i class="ph ph-fingerprint"></i>
        <span>Timekeeping DTR</span>
    </a>
</x-report-header>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.tardiness')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :startDate="$startDate"
    :endDate="$endDate"
    :showStatus="true"
    :showSource="true"
/>

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
