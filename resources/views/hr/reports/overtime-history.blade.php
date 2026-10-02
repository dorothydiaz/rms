@extends('layouts.app')

@section('title', 'Overtime History & Approval Report - Reports & Analytics')

@section('content')
<x-report-header 
    title="Overtime History & Approval Report"
    breadcrumb="Overtime History & Approvals"
    subtitle="Historical overtime hours rendered, approval status, approving managers, and remarks for payroll integration"
>
    <a href="{{ route('hr.reports.export.overtime-history', request()->query()) }}" class="hr-btn hr-btn-secondary">
        <i class="ph ph-download-simple"></i>
        <span>Export CSV</span>
    </a>
    <a href="{{ route('hr.attendance.overtime') }}" class="hr-btn hr-btn-primary">
        <i class="ph ph-check-circle"></i>
        <span>Overtime Approvals</span>
    </a>
</x-report-header>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.overtime-history')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :startDate="request('date_from')"
    :endDate="request('date_to')"
    :showStatus="true"
    :showSource="true"
>
    <!-- Overtime Status Custom Slot -->
    <div style="min-width: 150px;">
        <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-check-circle"></i> OT Status
        </label>
        <select name="status" class="hr-select" style="padding: 6px 10px; font-size: 12.5px; height: 34px; width: 100%;">
            <option value="">All OT Statuses</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending Review</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected / Unauthorized</option>
        </select>
    </div>
</x-report-filters>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Total Records: {{ $records->total() }}
        </span>
    </div>
    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px;">Date</th>
                    <th style="padding: 12px 16px;">Employee</th>
                    <th style="padding: 12px 16px;">Branch / Dept</th>
                    <th style="padding: 12px 16px;">Shift Punches</th>
                    <th style="padding: 12px 16px; text-align: right;">Total Hours</th>
                    <th style="padding: 12px 16px; text-align: right;">OT Hours</th>
                    <th style="padding: 12px 16px; text-align: center;">Approval Status</th>
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
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;">
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
                        <td style="padding: 12px 16px; text-align: right; font-weight: 500; color: #475569;">
                            {{ number_format($r->total_hours, 2) }} hrs
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <span class="hr-badge hr-badge-purple" style="font-size: 12.5px; font-weight: 700; padding: 4px 10px;">
                                +{{ number_format($r->overtime_hours, 2) }} hrs
                            </span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($r->overtime_status === 'Approved')
                                <span class="hr-badge hr-badge-success" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-check"></i> Approved
                                </span>
                            @elseif($r->overtime_status === 'Rejected')
                                <span class="hr-badge hr-badge-danger" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-x"></i> Rejected
                                </span>
                            @else
                                <span class="hr-badge hr-badge-warning" style="font-size: 11.5px; padding: 4px 9px;">
                                    <i class="ph ph-hourglass"></i> Pending
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px;">
                            @if($r->overtimeApprover)
                                <div style="font-weight: 600; color: #1e293b;">{{ $r->overtimeApprover->name }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $r->overtime_approved_at ? date('M d, Y h:i A', strtotime($r->overtime_approved_at)) : '' }}</div>
                            @else
                                <span style="color: #94a3b8; font-style: italic;">—</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 12px; color: #64748b; max-width: 180px;">
                            {{ $r->overtime_remarks ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px; color: #94a3b8;">
                            <i class="ph ph-clock-countdown" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No overtime records found for the selected filter criteria.
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
