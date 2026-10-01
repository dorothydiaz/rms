@extends('layouts.app')

@section('title', 'Undertime Authorization Report - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Authorized & Unauthorized Undertime</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-timer" style="color: #f59e0b;"></i> Undertime Authorization Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Comprehensive breakdown of staff undertime hours, authorization status, management approvals, and deductions</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('hr.reports.export.authorized-undertime', request()->query()) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('hr.attendance.undertime') }}" class="hr-btn hr-btn-primary">
            <i class="ph ph-shield-check"></i>
            <span>Undertime Approvals</span>
        </a>
    </div>
</div>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.authorized-undertime')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :startDate="request('date_from')"
    :endDate="request('date_to')"
    :showStatus="true"
    :showSource="true"
>
    <!-- Authorization Status Custom Slot -->
    <div style="min-width: 160px;">
        <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-shield-check"></i> Auth Status
        </label>
        <select name="status" class="hr-select" style="padding: 6px 10px; font-size: 12.5px; height: 34px; width: 100%;">
            <option value="">All Statuses</option>
            <option value="Approved" {{ ($status ?? request('status')) === 'Approved' ? 'selected' : '' }}>Authorized Undertime</option>
            <option value="Pending" {{ ($status ?? request('status')) === 'Pending' ? 'selected' : '' }}>Pending Review</option>
            <option value="Rejected" {{ ($status ?? request('status')) === 'Rejected' ? 'selected' : '' }}>Unauthorized Undertime</option>
        </select>
    </div>
</x-report-filters>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Total Undertime Logs: {{ $records->total() }}
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Dept</th>
                    <th style="padding: 12px 16px;">Time In & Out</th>
                    <th style="padding: 12px 16px; text-align: right;">Undertime</th>
                    <th style="padding: 12px 16px; text-align: right;">Total Hours</th>
                    <th style="padding: 12px 16px; text-align: center;">Classification / Status</th>
                    <th style="padding: 12px 16px;">Authorized By</th>
                    <th style="padding: 12px 16px;">Remarks</th>
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
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
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
                        <td style="padding: 12px 16px; font-size: 12px; color: #475569;">
                            {{ $r->time_in ? date('h:i A', strtotime($r->time_in)) : '—' }} &rarr; {{ $r->time_out ? date('h:i A', strtotime($r->time_out)) : '—' }}
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <span class="hr-badge hr-badge-warning" style="font-size: 12px; font-weight: 700; padding: 4px 9px;">
                                {{ $r->undertime_minutes }} mins
                                @if($r->undertime_minutes >= 60)
                                    ({{ number_format($r->undertime_minutes / 60, 1) }}h)
                                @endif
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: right; font-weight: 500; color: #475569;">
                            {{ number_format($r->total_hours, 2) }} hrs
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($r->undertime_status === 'Approved')
                                <span class="hr-badge hr-badge-success" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-check"></i> Authorized Undertime
                                </span>
                            @elseif($r->undertime_status === 'Rejected')
                                <span class="hr-badge hr-badge-danger" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-x"></i> Unauthorized Undertime
                                </span>
                            @else
                                <span class="hr-badge hr-badge-neutral" style="font-size: 11.5px; padding: 4px 9px; background: #fef3c7; color: #b45309; border-color: #fde68a;">
                                    <i class="ph ph-hourglass"></i> Pending Authorization
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px;">
                            @if($r->undertimeApprover)
                                <div style="font-weight: 600; color: #1e293b;">{{ $r->undertimeApprover->name }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $r->undertime_approved_at ? date('M d, Y h:i A', strtotime($r->undertime_approved_at)) : '' }}</div>
                            @else
                                <span style="color: #94a3b8; font-style: italic;">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #64748b; max-width: 180px;">
                            {{ $r->undertime_remarks ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-timer" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No undertime records found for the selected filter criteria.
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
