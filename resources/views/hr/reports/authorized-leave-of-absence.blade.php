@extends('layouts.app')

@section('title', 'Authorized Leave of Absence (ALOA) Report - Reports & Analytics')

@section('content')
<x-report-header 
    title="Authorized Leave of Absence (ALOA) Report"
    breadcrumb="Authorized Leave of Absence"
    subtitle="Official DOLE-compliant audit trail of approved employee leave applications, statutory leaves, and management authorizations"
>
    <a href="{{ route('hr.reports.export.authorized-leave-of-absence', request()->query()) }}" class="hr-btn hr-btn-secondary" title="Export to CSV / Excel">
        <i class="ph ph-download-simple" style="color: #059669;"></i>
        <span>Export CSV</span>
    </a>
    <button type="button" onclick="window.print()" class="hr-btn hr-btn-secondary" title="Print official report">
        <i class="ph ph-printer" style="color: #2563eb;"></i>
        <span>Print Report</span>
    </button>
    <a href="{{ route('hr.leave.requests') }}" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
        <i class="ph ph-calendar-plus"></i>
        <span>Leave Requests Portal</span>
    </a>
</x-report-header>

<!-- KPI Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 20px;">
    
    <!-- 1. Total Authorized Leaves -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-calendar-check"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Authorized Leaves</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ $totalApprovedRequests }}</div>
            <div style="font-size: 11px; color: #15803d; font-weight: 500;">Approved Applications</div>
        </div>
    </div>

    <!-- 2. Total Leave Days -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-hourglass-high"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Authorized Days</div>
            <div style="font-size: 22px; font-weight: 800; color: #7c3aed; line-height: 1.2;">{{ number_format($totalLeaveDays, 1) }}</div>
            <div style="font-size: 11px; color: #64748b;">Cumulative Days Granted</div>
        </div>
    </div>

    <!-- 3. Paid vs Unpaid Days -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-money"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Paid vs Unpaid Days</div>
            <div style="font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.2;">
                <span style="color: #059669;">{{ number_format($totalPaidDays, 1) }}</span> / <span style="color: #d97706;">{{ number_format($totalUnpaidDays, 1) }}</span>
            </div>
            <div style="font-size: 11px; color: #64748b;">With Pay vs Without Pay</div>
        </div>
    </div>

    <!-- 4. Unique Employees on Leave -->
    <div class="hr-card" style="padding: 16px; display: flex; align-items: center; gap: 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            <i class="ph ph-users-three"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Staff on Leave</div>
            <div style="font-size: 22px; font-weight: 800; color: #b45309; line-height: 1.2;">{{ $uniqueEmployeesCount }}</div>
            <div style="font-size: 11px; color: #64748b;">Unique Personnel</div>
        </div>
    </div>

</div>

<!-- Multi-Filter Bar -->
<x-report-filters 
    :action="route('hr.reports.authorized-leave-of-absence')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :startDate="$startDate"
    :endDate="$endDate"
    :showStatus="true"
    :showSource="true"
>
    <!-- Leave Type -->
    <div style="min-width: 160px;">
        <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-tag"></i> Leave Type
        </label>
        <select name="leave_type_id" class="hr-select" style="padding: 6px 10px; font-size: 12.5px; height: 34px; width: 100%;">
            <option value="">All Leave Types</option>
            @foreach($leaveTypes as $lt)
                <option value="{{ $lt->id }}" {{ (string) request('leave_type_id') === (string) $lt->id ? 'selected' : '' }}>
                    {{ $lt->name }} ({{ $lt->code }})
                </option>
            @endforeach
        </select>
    </div>

    <!-- Pay Status -->
    <div style="min-width: 140px;">
        <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-currency-circle-dollar"></i> Pay Status
        </label>
        <select name="is_paid" class="hr-select" style="padding: 6px 10px; font-size: 12.5px; height: 34px; width: 100%;">
            <option value="">All (Paid & Unpaid)</option>
            <option value="1" {{ request('is_paid') === '1' ? 'selected' : '' }}>Paid Leave</option>
            <option value="0" {{ request('is_paid') === '0' ? 'selected' : '' }}>Unpaid Leave</option>
        </select>
    </div>

    <!-- Authorization Status -->
    <div style="min-width: 150px;">
        <label class="hr-form-label" style="margin-bottom: 4px; font-size: 11.5px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-shield-check"></i> Auth Status
        </label>
        <select name="status" class="hr-select" style="padding: 6px 10px; font-size: 12.5px; height: 34px; width: 100%;">
            <option value="Approved" {{ ($status ?? request('status', 'Approved')) === 'Approved' ? 'selected' : '' }}>Approved (Authorized)</option>
            <option value="ALL" {{ ($status ?? request('status')) === 'ALL' ? 'selected' : '' }}>All Statuses</option>
            <option value="Pending" {{ ($status ?? request('status')) === 'Pending' ? 'selected' : '' }}>Pending Review</option>
            <option value="Rejected" {{ ($status ?? request('status')) === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </div>
</x-report-filters>

<!-- Table Card -->
<div class="hr-card" style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
    <div style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #ffffff; flex-wrap: wrap; gap: 8px;">
        <span style="font-size: 13px; font-weight: 600; color: #334155;">
            Total Authorized Leaves: <strong>{{ $records->total() }}</strong> records between <strong>{{ date('M d, Y', strtotime($startDate)) }}</strong> and <strong>{{ date('M d, Y', strtotime($endDate)) }}</strong>
        </span>
        <span style="font-size: 12px; color: #64748b;">
            Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }} entries
        </span>
    </div>

    <div class="hr-table-responsive" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
                    <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Inclusive Dates</th>
                    <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Employee</th>
                    <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Branch & Dept</th>
                    <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Leave Type</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Duration</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Pay Status</th>
                    <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Purpose / Justification</th>
                    <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Authorized By</th>
                    <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    @php
                        $emp = $r->employee;
                        $initials = $emp ? strtoupper(substr($emp->first_name ?? '', 0, 1) . substr($emp->last_name ?? '', 0, 1)) : 'EM';
                        $avatar = ($emp && $emp->photo_url)
                            ? '<img src="' . $emp->photo_url . '" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">'
                            : '<div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">' . $initials . '</div>';

                        $isPaid = $r->leaveType?->is_paid;
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                        
                        <!-- Inclusive Dates -->
                        <td style="padding: 12px 16px; font-weight: 600; color: #0f172a; white-space: nowrap;">
                            <div style="font-size: 13px; color: #0f172a;">
                                {{ $r->start_date ? $r->start_date->format('M d, Y') : '—' }}
                                @if($r->end_date && $r->end_date->ne($r->start_date))
                                    <span style="color: #94a3b8;">→</span> {{ $r->end_date->format('M d, Y') }}
                                @endif
                            </div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 500;">
                                Filed: {{ $r->created_at ? $r->created_at->format('M d, Y') : '—' }}
                            </div>
                        </td>

                        <!-- Employee -->
                        <td style="padding: 12px 16px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                {!! $avatar !!}
                                <div>
                                    <div style="font-weight: 600; color: #0f172a; font-size: 13px;">{{ $emp?->full_name ?? 'Unknown Staff' }}</div>
                                    <div style="font-family: monospace; font-size: 11px; color: #059669;">{{ $emp?->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Branch & Department -->
                        <td style="padding: 12px 16px; font-size: 12.5px;">
                            <div style="font-weight: 500; color: #334155;">{{ $emp?->branch?->name ?? 'Head Office' }}</div>
                            <div style="font-size: 11.5px; color: #64748b;">{{ $emp?->department?->name ?? 'Unassigned' }}</div>
                            @if($emp?->company_or_agency)
                                <div style="font-size: 10.5px; color: #6366f1; font-weight: 500;">{{ $emp->company_or_agency }}</div>
                            @endif
                        </td>

                        <!-- Leave Type -->
                        <td style="padding: 12px 16px;">
                            <span class="hr-badge hr-badge-purple" style="font-size: 11px; font-weight: 600;">
                                <i class="ph ph-tag"></i> {{ $r->leaveType?->name ?? 'Standard Leave' }}
                            </span>
                        </td>

                        <!-- Duration -->
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="font-weight: 800; color: #0f172a; font-size: 13px;">
                                {{ number_format($r->number_of_days, 1) }}
                            </span>
                            <span style="font-size: 11px; color: #64748b; display: block;">{{ $r->number_of_days > 1 ? 'Days' : 'Day' }}</span>
                        </td>

                        <!-- Pay Status -->
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($isPaid)
                                <span class="hr-badge hr-badge-success" style="font-size: 11px;">
                                    <i class="ph ph-check-circle"></i> With Pay
                                </span>
                            @else
                                <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">
                                    <i class="ph ph-minus-circle"></i> Without Pay
                                </span>
                            @endif
                        </td>

                        <!-- Purpose / Justification -->
                        <td style="padding: 12px 16px; font-size: 12.5px; color: #334155; max-width: 240px;">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: normal; line-height: 1.4;">
                                {{ $r->reason ?: 'No justification stated' }}
                            </div>
                            @if($r->supporting_document)
                                <div style="margin-top: 4px;">
                                    <a href="{{ asset('storage/' . $r->supporting_document) }}" target="_blank" style="font-size: 11px; color: #0284c7; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                                        <i class="ph ph-paperclip"></i> Attachment
                                    </a>
                                </div>
                            @endif
                        </td>

                        <!-- Authorized By -->
                        <td style="padding: 12px 16px; font-size: 12px;">
                            @if($r->status === 'Approved')
                                <div style="font-weight: 600; color: #059669; display: flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-shield-check"></i>
                                    <span>{{ $r->approver?->full_name ?? ($r->approver?->name ?? 'System Administrator') }}</span>
                                </div>
                                <div style="font-size: 11px; color: #64748b;">
                                    {{ $r->approval_date ? $r->approval_date->format('M d, Y h:i A') : 'Recorded' }}
                                </div>
                            @elseif($r->status === 'Rejected')
                                <div style="font-weight: 600; color: #dc2626;">Rejected</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $r->rejection_reason ?: 'Disapproved by manager' }}</div>
                            @else
                                <span class="hr-badge hr-badge-warning" style="font-size: 11px;">
                                    <i class="ph ph-clock"></i> Pending Review
                                </span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($r->status === 'Approved')
                                <span class="hr-badge hr-badge-success" style="font-size: 11px; font-weight: 700;">
                                    <i class="ph ph-check"></i> Authorized
                                </span>
                            @elseif($r->status === 'Rejected')
                                <span class="hr-badge hr-badge-danger" style="font-size: 11px;">
                                    <i class="ph ph-x"></i> Rejected
                                </span>
                            @else
                                <span class="hr-badge hr-badge-warning" style="font-size: 11px;">
                                    {{ $r->status }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 48px 20px; color: #94a3b8;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                                <i class="ph ph-calendar-blank"></i>
                            </div>
                            <h4 style="margin: 0 0 4px 0; font-size: 14.5px; font-weight: 700; color: #334155;">No Authorized Leaves Found</h4>
                            <p style="margin: 0; font-size: 12px; color: #64748b;">No leave records matched the specified date range, branch, or leave type filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #f1f5f9; background: #ffffff;">
            {{ $records->links() }}
        </div>
    @endif
</div>

<!-- Printable Report Header & Formatting -->
<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    .hr-card, .hr-card * {
        visibility: visible !important;
    }
    .hr-page-header, .hr-card:has(form), .hr-btn, a {
        display: none !important;
    }
    .hr-card:has(table) {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    table {
        width: 100% !important;
        font-size: 10px !important;
    }
    th, td {
        padding: 4px 6px !important;
        border: 1px solid #ccc !important;
    }
}
</style>
@endsection
