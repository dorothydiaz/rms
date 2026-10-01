@extends('layouts.app')

@section('title', 'Unauthorized Leave of Absences Report - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Unauthorized Leave of Absences</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-user-minus" style="color: #ef4444;"></i> Unauthorized Leave of Absences Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Monitoring unapproved absences, AWOL occurrences, and attendance compliance violations</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('hr.reports.export.unauthorized-absences', request()->query()) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('hr.attendance.timekeeping') }}" class="hr-btn hr-btn-primary">
            <i class="ph ph-calendar-check"></i>
            <span>Timekeeping DTR</span>
        </a>
    </div>
</div>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.unauthorized-absences')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :startDate="$startDate"
    :endDate="$endDate"
    :showStatus="true"
    :showSource="true"
/>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Total Unauthorized Absences: {{ $records->total() }} incidents recorded between {{ date('M d, Y', strtotime($startDate)) }} and {{ date('M d, Y', strtotime($endDate)) }}
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Absence Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Dept</th>
                    <th style="padding: 12px 16px; text-align: center;">Absence Duration</th>
                    <th style="padding: 12px 16px;">Classification</th>
                    <th style="padding: 12px 16px;">DTR Remarks / Incident Details</th>
                    <th style="padding: 12px 16px;">Payroll Impact</th>
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
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                    {{ strtoupper(substr($r->employee?->first_name ?? 'E', 0, 1) . substr($r->employee?->last_name ?? 'M', 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">{{ $r->employee?->full_name ?? '—' }}</div>
                                    <div style="font-size: 11.5px; color: #64748b;">ID: {{ $r->employee?->employee_id ?? '—' }} &bull; {{ $r->employee?->position?->name ?? 'Staff' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12.5px;">
                            <div style="font-weight: 500; color: #334155;">{{ $r->employee?->branch?->name ?? '—' }}</div>
                            <div style="color: #64748b; font-size: 11.5px;">{{ $r->employee?->department?->name ?? '—' }}</div>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span class="hr-badge hr-badge-danger" style="font-size: 12px; font-weight: 700; padding: 4px 9px;">
                                {{ $r->absence_days ?: 1.0 }} Day(s)
                            </span>
                        </td>
                        <td style="padding: 12px 16px;">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11.5px; background: #fff1f2; color: #e11d48; border-color: #fecdd3; font-weight: 600;">
                                <i class="ph ph-warning-circle"></i> Unauthorized Absence / AWOL
                            </span>
                        </td>
                        <td style="padding: 12px 16px; font-size: 12.5px; color: #475569; max-width: 250px;">
                            {{ $r->dtr_remarks ?: 'No call, no show; unapproved leave' }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #dc2626; font-weight: 600;">
                            Subject to deduction
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-check-circle" style="font-size: 32px; color: #10b981; display: block; margin-bottom: 8px;"></i>
                            No unauthorized absences recorded in this period. Great job!
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
