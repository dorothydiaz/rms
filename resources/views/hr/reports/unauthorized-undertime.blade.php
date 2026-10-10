@extends('layouts.app')

@section('title', 'Unauthorized Undertime Report - Reports & Analytics')

@section('content')
<x-report-header 
    title="Unauthorized Undertime"
    icon="ph-hourglass"
    subtitle="Unapproved early departures & salary deductions"
    description="Tracks employees leaving early without approved supervisor permits, unexcused shifts, and salary deduction metrics."
>
    <a href="{{ route('hr.reports.export.unauthorized-undertime', request()->query()) }}" class="hr-btn hr-btn-secondary" title="Export to CSV">
        <i class="ph ph-download-simple" style="color: #e11d48;"></i>
        <span>Export CSV</span>
    </a>
    <a href="{{ route('hr.reports.authorized-undertime') }}" class="hr-btn hr-btn-secondary" style="border-color: #bbf7d0; color: #15803d;" title="View Authorized Undertime Report">
        <i class="ph ph-check-circle"></i>
        <span>Authorized Undertime Report</span>
    </a>
    <a href="{{ route('hr.attendance.undertime') }}" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
        <i class="ph ph-shield-warning"></i>
        <span>Undertime Portal</span>
    </a>
</x-report-header>

<!-- KPI Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- 1. Total Unauthorized Incidents -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #ffe4e6; color: #e11d48; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-warning-circle"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Unauthorized Incidents</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ number_format($totalRecords ?? $records->total()) }}</div>
            <div style="font-size: 11px; color: #e11d48; font-weight: 500;">Unapproved Departures</div>
        </div>
    </div>

    <!-- 2. Total Unexcused Minutes Lost -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-clock-countdown"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Unexcused Minutes</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ number_format($totalMinutes ?? 0) }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">mins</span></div>
            <div style="font-size: 11px; color: #dc2626; font-weight: 500;">~{{ number_format(($totalMinutes ?? 0) / 60, 1) }} Hours Lost</div>
        </div>
    </div>

    <!-- 3. Staff with Infractions -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-users-three"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Staff Involved</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ number_format($totalStaff ?? 0) }}</div>
            <div style="font-size: 11px; color: #b45309; font-weight: 500;">Unique Employees</div>
        </div>
    </div>

    <!-- 4. Review Breakdown -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-receipt"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Payroll Deductions</div>
            <div style="font-size: 16px; font-weight: 800; color: #e11d48; line-height: 1.3;">
                {{ $unexcusedCount ?? 0 }} <span style="font-size: 11.5px; font-weight: 600; color: #dc2626;">Rejected</span> &bull; 
                {{ $pendingCount ?? 0 }} <span style="font-size: 11.5px; font-weight: 600; color: #b45309;">Pending</span>
            </div>
            <div style="font-size: 11px; color: #64748b; font-weight: 500;">Deductible Incidents</div>
        </div>
    </div>
</div>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.unauthorized-undertime')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :positions="$positions ?? []"
    :startDate="request('date_from')"
    :endDate="request('date_to')"
/>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden; width: 100%; box-sizing: border-box;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <span style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span>Unauthorized Early Departures & Deductions</span>
            <span class="hr-badge hr-badge-danger" style="font-size: 11px;">{{ $records->total() }} Records</span>
        </span>
        <span style="font-size: 12px; color: #64748b;">Subject to salary deduction under DOLE attendance guidelines</span>
    </div>
    <div class="hr-table-wrapper" style="overflow-x: auto; width: 100%;">
        <table class="hr-table" style="width: 100%; min-width: 950px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Department</th>
                    <th style="padding: 12px 16px;">Company / Agency</th>
                    <th style="padding: 12px 16px;">Time In & Out</th>
                    <th style="padding: 12px 16px; text-align: right;">Undertime</th>
                    <th style="padding: 12px 16px; text-align: center;">Infraction Status</th>
                    <th style="padding: 12px 16px; text-align: center;">Payroll Impact</th>
                    <th style="padding: 12px 16px;">Reason / Disciplinary Notes</th>
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
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #ffe4e6; color: #e11d48; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
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
                            {{ $r->employee?->company_or_agency ?? 'Direct' }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #475569; white-space: nowrap;">
                            {{ $r->time_in ? date('h:i A', strtotime($r->time_in)) : '—' }} &rarr; <strong style="color: #e11d48;">{{ $r->time_out ? date('h:i A', strtotime($r->time_out)) : '—' }}</strong>
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <span class="hr-badge hr-badge-danger" style="font-size: 12px; font-weight: 700; padding: 4px 9px;">
                                {{ $r->undertime_minutes }} mins
                                @if($r->undertime_minutes >= 60)
                                    ({{ number_format($r->undertime_minutes / 60, 1) }}h)
                                @endif
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($r->undertime_status === 'Rejected')
                                <span class="hr-badge hr-badge-danger" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-x-circle"></i> Unauthorized / Rejected
                                </span>
                            @else
                                <span class="hr-badge hr-badge-warning" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-clock-countdown"></i> Unapproved (Pending)
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span class="hr-badge" style="font-size: 11px; padding: 3px 8px; background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;">
                                <i class="ph ph-receipt"></i> Deductible ({{ $r->undertime_minutes }}m)
                            </span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #475569; max-width: 220px;">
                            {{ $r->undertime_remarks ?: 'Unapproved Early Departure without Official Gate Pass' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-check-circle" style="font-size: 32px; display: block; margin-bottom: 8px; color: #10b981;"></i>
                            No unauthorized undertime records found for the selected filter criteria.
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
