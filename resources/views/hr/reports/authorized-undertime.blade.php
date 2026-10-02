@extends('layouts.app')

@section('title', 'Authorized Undertime Report - Reports & Analytics')

@section('content')
<x-report-header 
    title="Authorized Undertime Report"
    breadcrumb="Authorized Undertime"
    subtitle="Official audit trail of approved early departures, gate pass permits, manager authorizations, and excused undertime"
>
    <a href="{{ route('hr.reports.export.authorized-undertime', request()->query()) }}" class="hr-btn hr-btn-secondary" title="Export to CSV">
        <i class="ph ph-download-simple" style="color: #059669;"></i>
        <span>Export CSV</span>
    </a>
    <a href="{{ route('hr.reports.unauthorized-undertime') }}" class="hr-btn hr-btn-secondary" style="border-color: #fecdd3; color: #e11d48;" title="View Unauthorized Undertime Report">
        <i class="ph ph-warning-circle"></i>
        <span>Unauthorized Undertime Report</span>
    </a>
    <a href="{{ route('hr.attendance.undertime') }}" class="hr-btn hr-btn-primary">
        <i class="ph ph-shield-check"></i>
        <span>Undertime Portal</span>
    </a>
</x-report-header>

<!-- KPI Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- 1. Total Authorized Incidents -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-check-circle"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Authorized Incidents</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ number_format($totalRecords ?? $records->total()) }}</div>
            <div style="font-size: 11px; color: #15803d; font-weight: 500;">Approved Early Outs</div>
        </div>
    </div>

    <!-- 2. Total Authorized Minutes -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-clock"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Approved Minutes</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ number_format($totalMinutes ?? 0) }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">mins</span></div>
            <div style="font-size: 11px; color: #7c3aed; font-weight: 500;">~{{ number_format(($totalMinutes ?? 0) / 60, 1) }} Total Hours</div>
        </div>
    </div>

    <!-- 3. Staff Covered -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-users"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Covered Staff</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ number_format($totalStaff ?? 0) }}</div>
            <div style="font-size: 11px; color: #0369a1; font-weight: 500;">Unique Employees</div>
        </div>
    </div>

    <!-- 4. Average Early Out -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 0;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-hourglass-medium"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Average Duration</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ $avgMinutes ?? 0 }} <span style="font-size: 13px; font-weight: 600; color: #64748b;">mins</span></div>
            <div style="font-size: 11px; color: #b45309; font-weight: 500;">Per Early Departure</div>
        </div>
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
/>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden; width: 100%; box-sizing: border-box;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <span style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span>Authorized Early Departures</span>
            <span class="hr-badge hr-badge-success" style="font-size: 11px;">{{ $records->total() }} Records</span>
        </span>
        <span style="font-size: 12px; color: #64748b;">Showing official gate pass & manager approved undertime logs</span>
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
                    <th style="padding: 12px 16px; text-align: center;">Status</th>
                    <th style="padding: 12px 16px;">Authorized By</th>
                    <th style="padding: 12px 16px;">Gate Pass / Justification</th>
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
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
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
                            {{ $r->time_in ? date('h:i A', strtotime($r->time_in)) : '—' }} &rarr; <strong style="color: #0f172a;">{{ $r->time_out ? date('h:i A', strtotime($r->time_out)) : '—' }}</strong>
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <span class="hr-badge hr-badge-success" style="font-size: 12px; font-weight: 700; padding: 4px 9px;">
                                {{ $r->undertime_minutes }} mins
                                @if($r->undertime_minutes >= 60)
                                    ({{ number_format($r->undertime_minutes / 60, 1) }}h)
                                @endif
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span class="hr-badge hr-badge-success" style="font-size: 11.5px; padding: 4px 9px;">
                                <i class="ph ph-check-circle"></i> Authorized
                            </span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px;">
                            @if($r->undertimeApprover)
                                <div style="font-weight: 600; color: #1e293b;">{{ $r->undertimeApprover->name }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $r->undertime_approved_at ? date('M d, Y h:i A', strtotime($r->undertime_approved_at)) : 'Approved' }}</div>
                            @else
                                <span style="color: #059669; font-weight: 500;">Approved</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #475569; max-width: 200px;">
                            {{ $r->undertime_remarks ?: 'Excused by Manager / Gate Pass Verified' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-check-circle" style="font-size: 32px; display: block; margin-bottom: 8px; color: #10b981;"></i>
                            No authorized undertime records found for the selected filter criteria.
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
