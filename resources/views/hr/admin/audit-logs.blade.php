@extends('layouts.app')

@section('title', 'Audit Logs - System Security')

@push('styles')
    @vite(['resources/css/modules/hr-audit-logs.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-audit-logs.js'])
@endpush

@section('content')
<x-hr-tabs parent="system-administration" />

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.admin.audit-logs') }}" style="display: flex; gap: 8px; width: 100%; align-items: center; flex-wrap: wrap;">
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
                    <th style="white-space: nowrap;">Timestamp</th>
                    <th style="white-space: nowrap;">User</th>
                    <th style="white-space: nowrap;">Module</th>
                    <th style="white-space: nowrap;">Action</th>
                    <th style="white-space: nowrap;">Record ID</th>
                    <th style="white-space: nowrap;">IP Address</th>
                    <th style="white-space: nowrap;">Activity Details</th>
                </tr>
            </thead>
            <tbody id="logsTableBody">
                @forelse($logs as $log)
                    <tr class="log-row">
                        <td style="white-space: nowrap; font-size: 12px; color: #64748b;">
                            {{ $log->created_at->format('M d, Y h:i:s A') }}
                        </td>
                        <td style="white-space: nowrap;">
                            <strong>{{ $log->user->full_name ?? 'System' }}</strong>
                            <div style="font-size: 11px; color: #64748b;">@ {{ $log->user->username ?? 'system' }}</div>
                        </td>
                        <td style="white-space: nowrap;">
                            <span class="hr-badge hr-badge-neutral" style="white-space: nowrap;">{{ $log->module }}</span>
                        </td>
                        <td style="white-space: nowrap;">
                            @php
                                $badgeClass = 'hr-badge-blue';
                                if (in_array($log->action, ['Create', 'Approve', 'Finalize', 'BulkApprove'])) $badgeClass = 'hr-badge-success';
                                elseif (in_array($log->action, ['Delete', 'Reject', 'BulkReject'])) $badgeClass = 'hr-badge-danger';
                                elseif ($log->action === 'Update') $badgeClass = 'hr-badge-warning';
                            @endphp
                            <span class="hr-badge {{ $badgeClass }}" style="white-space: nowrap;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 12px; color: #475569; font-weight: 600; white-space: nowrap;">#{{ $log->record_id ?: '—' }}</td>
                        <td style="font-family: monospace; font-size: 11.5px; color: #64748b; white-space: nowrap;">{{ $log->ip_address ?: '127.0.0.1' }}</td>
                        <td style="font-size: 12.5px; color: #334155; line-height: 1.45; word-break: break-word; min-width: 260px;">
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
                    <option value="10" selected>10</option>
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
@endsection
