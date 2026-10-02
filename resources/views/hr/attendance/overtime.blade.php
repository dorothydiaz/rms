@extends('layouts.app')

@section('title', 'Overtime Records & Approvals - Attendance Management')

@section('content')
<x-hr-tabs parent="time-attendance" />

<!-- Metric Summary Cards -->
<div class="hr-emp-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 16px;">
    <div class="hr-stat-card hr-stat-card-purple">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-clock-countdown"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Total Overtime</span>
            <div class="hr-stat-value">{{ number_format($totalOtHours ?? 0, 1) }} <span style="font-size: 13px; font-weight: 500;">hrs</span></div>
            <span class="hr-stat-sub">Across all staff</span>
        </div>
    </div>

    <div class="hr-stat-card hr-stat-card-amber">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-hourglass-high"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Pending Approval</span>
            <div class="hr-stat-value">{{ $pendingCount ?? 0 }}</div>
            <span class="hr-stat-sub">Awaiting management review</span>
        </div>
    </div>

    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-check-circle"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Approved Overtime</span>
            <div class="hr-stat-value">{{ $approvedCount ?? 0 }}</div>
            <span class="hr-stat-sub">Approved for payroll</span>
        </div>
    </div>

    <div class="hr-stat-card hr-stat-card-rose">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-x-circle"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Rejected Overtime</span>
            <div class="hr-stat-value">{{ $rejectedCount ?? 0 }}</div>
            <span class="hr-stat-sub">Unauthorized / unapproved</span>
        </div>
    </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="hr-filter-bar" style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
        <!-- Real-Time Text Search -->
        <div style="position: relative; width: 240px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
            <input type="text" id="otSearchInput" class="hr-input" placeholder="Search staff, position…" oninput="filterOvertimeTable()" style="width: 100%; box-sizing: border-box; padding-left: 36px; height: 38px;">
        </div>

        <!-- Status Filter -->
        <select id="otStatusFilter" class="hr-select" style="height: 38px; width: 150px;" onchange="applyOtFilter('status', this.value)">
            <option value="">-- All Statuses --</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <!-- Date Filter -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <input type="date" id="otDateFilter" class="hr-input" value="{{ request('date') }}" onchange="applyOtFilter('date', this.value)" style="height: 38px; width: 155px;" title="Filter by date">
            @if(request('date') || request('status') || request('branch_id'))
                <a href="{{ route('hr.attendance.overtime') }}" class="hr-btn hr-btn-secondary" style="height: 38px; padding: 0 10px;" title="Clear filters">
                    <i class="ph ph-x"></i> Clear
                </a>
            @endif
        </div>

        <!-- Branch Filter -->
        @if(isset($branches) && $branches->isNotEmpty())
            <select id="otBranchFilter" class="hr-select" style="height: 38px; max-width: 170px;" onchange="applyOtFilter('branch_id', this.value)">
                <option value="">-- All Branches --</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="hr-btn hr-btn-primary" id="btnBatchApprove" onclick="submitBatchApprove()" style="display: none; height: 38px; background: linear-gradient(135deg, #10b981, #059669);">
            <i class="ph ph-check-circle"></i>
            <span>Approve Selected (<span id="batchCount">0</span>)</span>
        </button>

        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; padding: 6px 12px;">
            Showing <strong id="otVisibleCount" style="color: #0f172a; margin: 0 3px;">{{ $records->count() }}</strong> of {{ $records->total() }} records
        </span>
    </div>
</div>

<form id="batchApproveForm" method="POST" action="{{ route('hr.attendance.overtime.batch-approve') }}" style="display: none;">
    @csrf
</form>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table" id="overtimeTable">
            <thead>
                <tr>
                    <th style="width: 36px; text-align: center;">
                        <input type="checkbox" id="selectAllOt" onclick="toggleSelectAll(this)" style="cursor: pointer;">
                    </th>
                    <th>Date</th>
                    <th>Staff Member</th>
                    <th>Branch & Dept</th>
                    <th>Time In / Out</th>
                    <th>Total Hours</th>
                    <th>Overtime Rendered</th>
                    <th>Approval Status</th>
                    <th style="text-align: right; width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody id="overtimeTableBody">
                @forelse($records as $rec)
                    @php
                        $staffName = $rec->employee?->full_name ?? 'Staff';
                        $branchName = $rec->employee?->branch?->name ?? 'Unassigned';
                        $deptName = $rec->employee?->department?->name ?? 'Front of House';
                        $posName = $rec->employee?->position?->name ?? 'Staff';
                        $dateStr = \Carbon\Carbon::parse($rec->date)->format('Y-m-d');
                        $status = $rec->overtime_status ?: 'Pending';

                        $deptUpper = strtoupper(trim($deptName));
                        $pillClass = 'hr-dept-pill-neutral';
                        $pillIcon = 'ph-buildings';
                        if (str_contains($deptUpper, 'HR') || str_contains($deptUpper, 'HUMAN')) {
                            $pillClass = 'hr-dept-pill-hr';
                            $pillIcon = 'ph-users';
                        } elseif (str_contains($deptUpper, 'IT') || str_contains($deptUpper, 'TECH')) {
                            $pillClass = 'hr-dept-pill-it';
                            $pillIcon = 'ph-cpu';
                        } elseif (str_contains($deptUpper, 'FINANCE') || str_contains($deptUpper, 'FIN') || str_contains($deptUpper, 'ADMIN')) {
                            $pillClass = 'hr-dept-pill-fin';
                            $pillIcon = 'ph-coins';
                        } elseif (str_contains($deptUpper, 'FRONT') || str_contains($deptUpper, 'FOH')) {
                            $pillClass = 'hr-dept-pill-foh';
                            $pillIcon = 'ph-storefront';
                        } elseif (str_contains($deptUpper, 'BACK') || str_contains($deptUpper, 'BOH') || str_contains($deptUpper, 'KITCHEN')) {
                            $pillClass = 'hr-dept-pill-boh';
                            $pillIcon = 'ph-cooking-pot';
                        } elseif (str_contains($deptUpper, 'MANAGE')) {
                            $pillClass = 'hr-dept-pill-mgmt';
                            $pillIcon = 'ph-briefcase';
                        }
                    @endphp
                    <tr class="overtime-row"
                        data-search="{{ strtolower($staffName . ' ' . $branchName . ' ' . $deptName . ' ' . $posName) }}"
                        data-branch="{{ $branchName }}"
                        data-date="{{ $dateStr }}"
                        data-status="{{ $status }}">
                        <td style="text-align: center;">
                            @if($status === 'Pending')
                                <input type="checkbox" class="ot-record-checkbox" value="{{ $rec->id }}" onchange="updateBatchState()" style="cursor: pointer;">
                            @else
                                <span style="color: #cbd5e1;">-</span>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-size: 12.5px;">{{ \Carbon\Carbon::parse($rec->date)->format('M d, Y') }}</strong>
                            <div style="font-size: 11px; color: #64748b;">{{ \Carbon\Carbon::parse($rec->date)->format('l') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a; font-size: 13px;">{{ $staffName }}</div>
                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">{{ $rec->employee?->employee_id }} &bull; {{ $posName }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #1e293b; font-size: 11.5px; margin-bottom: 3px;">
                                <i class="ph ph-storefront" style="color: #7c3aed; font-size: 12px;"></i> {{ $branchName }}
                            </div>
                            <span class="hr-dept-pill {{ $pillClass }}" style="font-size: 9.5px; padding: 1.5px 7px;">
                                <i class="ph {{ $pillIcon }}"></i>
                                <span>{{ $deptName }}</span>
                            </span>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-size: 12px; color: #0f172a;">
                                {{ $rec->time_in ? substr($rec->time_in, 0, 5) : '--:--' }} &rarr; {{ $rec->time_out ? substr($rec->time_out, 0, 5) : '--:--' }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-size: 12.5px;">{{ number_format($rec->total_hours, 2) }} hrs</strong>
                        </td>
                        <td>
                            <span style="color: #7c3aed; font-size: 13px; font-weight: 500;">
                                +{{ number_format($rec->overtime_hours, 2) }} hrs
                            </span>
                        </td>
                        <td>
                            @if($status === 'Approved')
                                <span class="hr-status-indicator is-approved" title="Approved by {{ $rec->overtimeApprover?->name ?? 'Manager' }} on {{ $rec->overtime_approved_at ? \Carbon\Carbon::parse($rec->overtime_approved_at)->format('M d, Y h:i A') : '' }}">
                                    <span class="status-dot"></span> Approved
                                </span>
                            @elseif($status === 'Rejected')
                                <span class="hr-status-indicator is-rejected" title="{{ $rec->overtime_remarks ?? 'Rejected' }}">
                                    <span class="status-dot"></span> Rejected
                                </span>
                            @else
                                <span class="hr-status-indicator is-pending">
                                    <span class="status-dot"></span> Pending Review
                                </span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div class="hr-toggle-cell">
                                @if($status === 'Approved')
                                    <form method="POST" action="{{ route('hr.attendance.overtime.reject', $rec->id) }}" style="display: inline-block; margin: 0;" onsubmit="return confirm('Reject this overtime record?')">
                                        @csrf
                                        <button type="submit" class="hr-approval-toggle-btn state-approved hr-toggle-tooltip" data-tooltip="Reject">
                                            <span class="toggle-track-icon"><i class="ph-bold ph-check"></i></span>
                                            <span class="toggle-thumb"><i class="ph-bold ph-check"></i></span>
                                        </button>
                                    </form>
                                @elseif($status === 'Rejected')
                                    <form method="POST" action="{{ route('hr.attendance.overtime.approve', $rec->id) }}" style="display: inline-block; margin: 0;">
                                        @csrf
                                        <button type="submit" class="hr-approval-toggle-btn state-rejected hr-toggle-tooltip" data-tooltip="Approve">
                                            <span class="toggle-thumb"><i class="ph-bold ph-x"></i></span>
                                        </button>
                                    </form>
                                @else
                                    <div class="hr-approval-pending-pill">
                                        <form method="POST" action="{{ route('hr.attendance.overtime.approve', $rec->id) }}" style="display: inline-block; margin: 0;">
                                            @csrf
                                            <button type="submit" class="hr-pending-action-btn btn-approve hr-toggle-tooltip" data-tooltip="Approve">
                                                <i class="ph-bold ph-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('hr.attendance.overtime.reject', $rec->id) }}" style="display: inline-block; margin: 0;" onsubmit="return confirm('Reject this overtime record?')">
                                            @csrf
                                            <button type="submit" class="hr-pending-action-btn btn-reject hr-toggle-tooltip" data-tooltip="Reject">
                                                <i class="ph-bold ph-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyOtRow"><td colspan="9" style="text-align: center; color: #94a3b8; padding: 36px;">No overtime hours logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->total() > 0)
        <div class="hr-table-footer">
            {{ $records->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function filterOvertimeTable() {
    const q = (document.getElementById('otSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.overtime-row');
    let visible = 0;

    rows.forEach(r => {
        const search = r.getAttribute('data-search') || '';
        const matchQ = !q || search.includes(q);

        if (matchQ) {
            r.style.display = '';
            visible++;
        } else {
            r.style.display = 'none';
        }
    });

    const badge = document.getElementById('otVisibleCount');
    if (badge) badge.innerText = visible;

    const empty = document.getElementById('emptyOtRow');
    if (empty) {
        empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyOtFilter(key, val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set(key, val);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.ot-record-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
    updateBatchState();
}

function updateBatchState() {
    const checked = document.querySelectorAll('.ot-record-checkbox:checked');
    const btn = document.getElementById('btnBatchApprove');
    const countSpan = document.getElementById('batchCount');
    if (btn && countSpan) {
        countSpan.innerText = checked.length;
        btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    }
}

function submitBatchApprove() {
    const checked = document.querySelectorAll('.ot-record-checkbox:checked');
    if (checked.length === 0) return;

    if (!confirm(`Approve ${checked.length} selected overtime record(s)?`)) return;

    const form = document.getElementById('batchApproveForm');
    form.innerHTML = '@csrf';
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'record_ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });
    form.submit();
}
</script>
@endpush
@endsection
