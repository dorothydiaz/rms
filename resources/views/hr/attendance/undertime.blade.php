@extends('layouts.app')

@section('title', 'Undertime Records & Authorization - Attendance Management')

@section('content')
<x-hr-tabs parent="time-attendance" />

<!-- Metric Summary Cards -->
<div class="hr-emp-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 16px;">
    <div class="hr-stat-card hr-stat-card-blue">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-timer"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Total Undertime</span>
            <div class="hr-stat-value">
                {{ number_format(($totalUndertimeMinutes ?? 0) / 60, 1) }} <span style="font-size: 13px; font-weight: 500;">hrs</span>
                <span style="font-size: 11.5px; color: #64748b; font-weight: normal;">({{ number_format($totalUndertimeMinutes ?? 0) }} mins)</span>
            </div>
            <span class="hr-stat-sub">Early departures logged</span>
        </div>
    </div>

    <div class="hr-stat-card hr-stat-card-amber">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-hourglass-high"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Pending Review</span>
            <div class="hr-stat-value">{{ $pendingCount ?? 0 }}</div>
            <span class="hr-stat-sub">Awaiting authorization</span>
        </div>
    </div>

    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-seal-check"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Authorized Undertime</span>
            <div class="hr-stat-value">{{ $authorizedCount ?? 0 }}</div>
            <span class="hr-stat-sub">Excused / Approved departures</span>
        </div>
    </div>

    <div class="hr-stat-card hr-stat-card-rose">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-warning-octagon"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Unauthorized Undertime</span>
            <div class="hr-stat-value">{{ $unauthorizedCount ?? 0 }}</div>
            <span class="hr-stat-sub">Subject to salary deduction</span>
        </div>
    </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="hr-filter-bar" style="margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex: 1;">
        <!-- Real-Time Text Search -->
        <div style="position: relative; width: 220px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
            <input type="text" id="utSearchInput" class="hr-input" placeholder="Search staff, position…" oninput="filterUndertimeTable()" style="width: 100%; box-sizing: border-box; padding-left: 28px; height: 31px; font-size: 12px;">
        </div>

        <!-- Status Filter -->
        <select id="utStatusFilter" class="hr-select" style="height: 31px; width: 145px; font-size: 12px;" onchange="applyUtFilter('status', this.value)">
            <option value="">-- All Statuses --</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending Review</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Authorized Undertime</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Unauthorized Undertime</option>
        </select>

        <!-- Date Filter -->
        <div style="display: flex; align-items: center; gap: 6px;">
            <input type="date" id="utDateFilter" class="hr-input" value="{{ request('date') }}" onchange="applyUtFilter('date', this.value)" style="height: 31px; width: 140px; font-size: 12px;" title="Filter by date">
            @if(request('date') || request('status') || request('branch_id'))
                <a href="{{ route('hr.attendance.undertime') }}" class="hr-btn hr-btn-secondary" style="height: 31px; padding: 0 8px; font-size: 11.5px;" title="Clear filters">
                    <i class="ph ph-x"></i> Clear
                </a>
            @endif
        </div>

        <!-- Branch Filter -->
        @if(isset($branches) && $branches->isNotEmpty())
            <select id="utBranchFilter" class="hr-select" style="height: 31px; max-width: 155px; font-size: 12px;" onchange="applyUtFilter('branch_id', this.value)">
                <option value="">-- All Branches --</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="hr-btn hr-btn-primary" id="btnBatchAuthorize" onclick="submitBatchAuthorize()" style="display: none; height: 38px; background: linear-gradient(135deg, #10b981, #059669);">
            <i class="ph ph-seal-check"></i>
            <span>Authorize Selected (<span id="batchUtCount">0</span>)</span>
        </button>

        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; padding: 6px 12px;">
            Showing <strong id="utVisibleCount" style="color: #0f172a; margin: 0 3px;">{{ $records->count() }}</strong> of {{ $records->total() }} records
        </span>
    </div>
</div>

<form id="batchAuthorizeForm" method="POST" action="{{ route('hr.attendance.undertime.batch-authorize') }}" style="display: none;">
    @csrf
</form>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table" id="undertimeTable">
            <thead>
                <tr>
                    <th style="width: 36px; text-align: center;">
                        <input type="checkbox" id="selectAllUt" onclick="toggleSelectAllUt(this)" style="cursor: pointer;">
                    </th>
                    <th>Date</th>
                    <th>Staff Member</th>
                    <th>Branch & Dept</th>
                    <th>Time In / Out</th>
                    <th>Undertime</th>
                    <th>Total Rendered</th>
                    <th>Authorization Status</th>
                    <th style="text-align: right; width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody id="undertimeTableBody">
                @forelse($records as $rec)
                    @php
                        $staffName = $rec->employee?->full_name ?? 'Staff';
                        $branchName = $rec->employee?->branch?->name ?? 'Unassigned';
                        $deptName = $rec->employee?->department?->name ?? 'Front of House';
                        $posName = $rec->employee?->position?->name ?? 'Staff';
                        $dateStr = \Carbon\Carbon::parse($rec->date)->format('Y-m-d');
                        $status = $rec->undertime_status ?: 'Pending';

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

                        $mins = (int) $rec->undertime_minutes;
                        $hours = floor($mins / 60);
                        $remMins = $mins % 60;
                        $formattedDuration = $hours > 0 ? "{$hours}h {$remMins}m" : "{$remMins} mins";
                    @endphp
                    <tr class="undertime-row"
                        data-search="{{ strtolower($staffName . ' ' . $branchName . ' ' . $deptName . ' ' . $posName) }}"
                        data-branch="{{ $branchName }}"
                        data-date="{{ $dateStr }}"
                        data-status="{{ $status }}">
                        <td style="text-align: center;">
                            @if($status === 'Pending')
                                <input type="checkbox" class="ut-record-checkbox" value="{{ $rec->id }}" onchange="updateBatchUtState()" style="cursor: pointer;">
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
                            <span style="color: #e11d48; font-size: 13px; font-weight: 500;">
                                -{{ $formattedDuration }}
                            </span>
                        </td>
                        <td>
                            <span style="color: #334155; font-size: 12px;">{{ number_format($rec->total_hours, 2) }} hrs</span>
                        </td>
                        <td>
                            @if($status === 'Approved')
                                <span class="hr-status-indicator is-approved" title="Authorized by {{ $rec->undertimeApprover?->name ?? 'Manager' }} on {{ $rec->undertime_approved_at ? \Carbon\Carbon::parse($rec->undertime_approved_at)->format('M d, Y h:i A') : '' }}">
                                    <span class="status-dot"></span> Authorized
                                </span>
                            @elseif($status === 'Rejected')
                                <span class="hr-status-indicator is-rejected" title="{{ $rec->undertime_remarks ?? 'Unauthorized Undertime' }}">
                                    <span class="status-dot"></span> Unauthorized
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
                                    <form method="POST" action="{{ route('hr.attendance.undertime.reject', $rec->id) }}" style="display: inline-block; margin: 0;" onsubmit="return confirm('Mark this undertime as unauthorized deduction?')">
                                        @csrf
                                        <button type="submit" class="hr-approval-toggle-btn state-approved hr-toggle-tooltip" data-tooltip="Reject">
                                            <span class="toggle-track-icon"><i class="ph-bold ph-check"></i></span>
                                            <span class="toggle-thumb"><i class="ph-bold ph-check"></i></span>
                                        </button>
                                    </form>
                                @elseif($status === 'Rejected')
                                    <form method="POST" action="{{ route('hr.attendance.undertime.authorize', $rec->id) }}" style="display: inline-block; margin: 0;">
                                        @csrf
                                        <button type="submit" class="hr-approval-toggle-btn state-rejected hr-toggle-tooltip" data-tooltip="Approve">
                                            <span class="toggle-thumb"><i class="ph-bold ph-x"></i></span>
                                        </button>
                                    </form>
                                @else
                                    <div class="hr-approval-pending-pill">
                                        <form method="POST" action="{{ route('hr.attendance.undertime.authorize', $rec->id) }}" style="display: inline-block; margin: 0;">
                                            @csrf
                                            <button type="submit" class="hr-pending-action-btn btn-approve hr-toggle-tooltip" data-tooltip="Approve">
                                                <i class="ph-bold ph-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('hr.attendance.undertime.reject', $rec->id) }}" style="display: inline-block; margin: 0;" onsubmit="return confirm('Mark this undertime as unauthorized deduction?')">
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
                    <tr id="emptyUtRow"><td colspan="9" style="text-align: center; color: #94a3b8; padding: 36px;">No undertime hours logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="hr-table-footer" id="utPaginationBar">
        {{ $records->links() }}
    </div>
</div>

@push('scripts')
<script>
function filterUndertimeTable() {
    const q = (document.getElementById('utSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.undertime-row');
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

    const badge = document.getElementById('utVisibleCount');
    if (badge) badge.innerText = visible;

    const pagInfo = document.querySelector('#utPaginationBar .hr-pagination-info');
    if (pagInfo) {
        if (q) {
            pagInfo.innerHTML = `Showing <strong>${visible === 0 ? 0 : 1}</strong> to <strong>${visible}</strong> of <strong>${visible}</strong> filtered records`;
        } else {
            pagInfo.innerHTML = `Showing <strong>{{ $records->firstItem() ?? ($records->total() > 0 ? 1 : 0) }}</strong> to <strong>{{ $records->lastItem() ?? $records->total() }}</strong> of <strong>{{ $records->total() }}</strong> records`;
        }
    }

    const empty = document.getElementById('emptyUtRow');
    if (empty) {
        empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyUtFilter(key, val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set(key, val);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function toggleSelectAllUt(master) {
    const checkboxes = document.querySelectorAll('.ut-record-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
    updateBatchUtState();
}

function updateBatchUtState() {
    const checked = document.querySelectorAll('.ut-record-checkbox:checked');
    const btn = document.getElementById('btnBatchAuthorize');
    const countSpan = document.getElementById('batchUtCount');
    if (btn && countSpan) {
        countSpan.innerText = checked.length;
        btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    }
}

function submitBatchAuthorize() {
    const checked = document.querySelectorAll('.ut-record-checkbox:checked');
    if (checked.length === 0) return;

    if (!confirm(`Authorize ${checked.length} selected undertime record(s)?`)) return;

    const form = document.getElementById('batchAuthorizeForm');
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
