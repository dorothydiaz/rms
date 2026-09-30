@extends('layouts.app')

@section('title', 'Audit Logs - System Security')

@section('content')
<x-hr-tabs parent="system-administration" />

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.admin.audit-logs') }}" style="display: flex; gap: 12px; width: 100%; align-items: center; flex-wrap: wrap;">
        <select name="module" class="hr-select" style="max-width: 180px;">
            <option value="">-- All Modules --</option>
            <option value="Authentication" {{ request('module') === 'Authentication' ? 'selected' : '' }}>Authentication</option>
            <option value="People" {{ request('module') === 'People' ? 'selected' : '' }}>People</option>
            <option value="Attendance" {{ request('module') === 'Attendance' ? 'selected' : '' }}>Attendance</option>
            <option value="Leave" {{ request('module') === 'Leave' ? 'selected' : '' }}>Leave</option>
            <option value="Payroll" {{ request('module') === 'Payroll' ? 'selected' : '' }}>Payroll</option>
            <option value="Recruitment" {{ request('module') === 'Recruitment' ? 'selected' : '' }}>Recruitment</option>
            <option value="Performance" {{ request('module') === 'Performance' ? 'selected' : '' }}>Performance</option>
            <option value="Training" {{ request('module') === 'Training' ? 'selected' : '' }}>Training</option>
            <option value="RBAC" {{ request('module') === 'RBAC' ? 'selected' : '' }}>RBAC</option>
            <option value="Settings" {{ request('module') === 'Settings' ? 'selected' : '' }}>Settings</option>
        </select>

        <select name="action" class="hr-select" style="max-width: 160px;">
            <option value="">-- All Actions --</option>
            <option value="Create" {{ request('action') === 'Create' ? 'selected' : '' }}>Create</option>
            <option value="Update" {{ request('action') === 'Update' ? 'selected' : '' }}>Update</option>
            <option value="Delete" {{ request('action') === 'Delete' ? 'selected' : '' }}>Delete</option>
            <option value="Approve" {{ request('action') === 'Approve' ? 'selected' : '' }}>Approve</option>
            <option value="Reject" {{ request('action') === 'Reject' ? 'selected' : '' }}>Reject</option>
            <option value="Finalize" {{ request('action') === 'Finalize' ? 'selected' : '' }}>Finalize</option>
        </select>

        <select name="user_id" class="hr-select" style="max-width: 200px;">
            <option value="">-- All Users --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->full_name }} (@ {{ $u->username }})</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i> Filter
        </button>

        @if(request()->hasAny(['module', 'action', 'user_id']))
            <a href="{{ route('hr.admin.audit-logs') }}" class="hr-btn hr-btn-secondary">Reset</a>
        @endif
    </form>
</div>

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper" id="logsTableWrapper" style="overflow-y:auto; overflow-x:auto;">
        <table class="hr-table" id="logsTable">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Record ID</th>
                    <th>IP Address</th>
                    <th>Activity Details</th>
                </tr>
            </thead>
            <tbody id="logsTableBody">
                @forelse($logs as $log)
                    <tr class="log-row">
                        <td style="white-space: nowrap; font-size: 12px; color: #94a3b8;">
                            {{ $log->created_at->format('M d, Y h:i:s A') }}
                        </td>
                        <td>
                            <strong>{{ $log->user->full_name ?? 'System' }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">@ {{ $log->user->username ?? 'system' }}</div>
                        </td>
                        <td>
                            <span class="hr-badge hr-badge-neutral">{{ $log->module }}</span>
                        </td>
                        <td>
                            @php
                                $badgeClass = 'hr-badge-blue';
                                if (in_array($log->action, ['Create', 'Approve', 'Finalize'])) $badgeClass = 'hr-badge-success';
                                elseif (in_array($log->action, ['Delete', 'Reject'])) $badgeClass = 'hr-badge-danger';
                                elseif ($log->action === 'Update') $badgeClass = 'hr-badge-warning';
                            @endphp
                            <span class="hr-badge {{ $badgeClass }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>#{{ $log->record_id ?: '—' }}</td>
                        <td style="font-family: monospace; font-size: 11px; color: #94a3b8;">{{ $log->ip_address ?: '127.0.0.1' }}</td>
                        <td style="font-size: 12px; color: #e2e8f0; max-width: 380px;">
                            {{ $log->details ?: 'No additional metadata' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No audit log events found matching the specified filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="logsPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="logsPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="logsPerPageSelect" onchange="logsChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="logsPageNav"></nav>
    </div>
</div>

@push('scripts')
<script>
// =========================================================================
// Client-Side Pagination — Audit Logs
// =========================================================================
let _logsPage = 1, _logsRows = [];
function logsGetPerPage() { return parseInt(document.getElementById('logsPerPageSelect')?.value || '15', 10); }
function logsChangePerPage() { _logsPage = 1; renderLogsPage(); }
function logsGoToPage(p) { _logsPage = p; renderLogsPage(); const w = document.getElementById('logsTableWrapper'); if(w) w.scrollTop = 0; }

function renderLogsPage() {
    const pp = logsGetPerPage(), total = _logsRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_logsPage > totalPages) _logsPage = totalPages;
    const start = (_logsPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#logsTableBody tr.log-row').forEach(r => r.style.display = 'none');
    _logsRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });


    const bar = document.getElementById('logsPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('logsPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start+1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> log entries`;
    const nav = document.getElementById('logsPageNav');
    if (!nav) return;
    let h = _logsPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="logsGoToPage(${_logsPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _logsPage-2), pe = Math.min(totalPages, _logsPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="logsGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _logsPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="logsGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="logsGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _logsPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="logsGoToPage(${_logsPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshLogsPage = function() {
    _logsRows = Array.from(document.querySelectorAll('#logsTableBody tr.log-row'));
    renderLogsPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshLogsPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshLogsPage();
});
</script>
@endpush
@endsection
