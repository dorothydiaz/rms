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

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
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
            <tbody>
                @forelse($logs as $log)
                    <tr>
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
    @if($logs->hasPages())
        <div style="padding: 16px;">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
