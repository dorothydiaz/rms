@extends('layouts.app')

@section('title', 'User Accounts - Administration')

@push('styles')
    @vite(['resources/css/modules/hr-users.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-users.js'])
@endpush

@section('content')
<x-hr-tabs parent="system-administration">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addUserModal')">
            <i class="ph ph-user-plus"></i>
            <span>Add User</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.admin.users') }}" style="display: flex; gap: 8px; width: 100%; align-items: center; flex-wrap: wrap;">
        <select name="role" class="hr-select" style="max-width: 200px;">
            <option value="">-- All Roles --</option>
            @foreach($roles as $r)
                <option value="{{ $r->slug }}" {{ request('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>
        <select name="branch_id" class="hr-select" style="max-width: 220px;">
            <option value="">-- All Branches --</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i> Filter
        </button>
        @if(request()->hasAny(['role', 'branch_id']))
            <a href="{{ route('hr.admin.users') }}" class="hr-btn hr-btn-secondary">Reset</a>
        @endif
    </form>
</div>

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper" id="usersTableWrapper" style="overflow-y:auto; overflow-x:auto;">
        <table class="hr-table">
            <thead>
                <tr>
                    <th style="white-space: nowrap;">Username / Name</th>
                    <th style="white-space: nowrap;">Email</th>
                    <th style="white-space: nowrap;">Assigned Role</th>
                    <th style="white-space: nowrap;">Linked Employee Profile</th>
                    <th style="white-space: nowrap;">Branch Access Scope</th>
                    <th style="white-space: nowrap;">Status</th>
                    <th style="white-space: nowrap;">Last Active</th>
                    <th style="text-align: right; white-space: nowrap;">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                @forelse($users as $u)
                    <tr class="user-row">
                        <td style="white-space: nowrap;">
                            <strong>{{ $u->full_name }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">@ {{ $u->username }}</div>
                        </td>
                        <td style="white-space: nowrap;">{{ $u->email }}</td>
                        <td style="white-space: nowrap;">
                            <div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                                @forelse($u->roles as $role)
                                    <span class="hr-badge {{ $role->slug === 'super-admin' ? 'hr-badge-purple' : ($role->slug === 'hr-admin' ? 'hr-badge-blue' : 'hr-badge-warning') }}" style="white-space: nowrap;">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span style="color: #94a3b8; font-size: 12px; font-style: italic;">No Role</span>
                                @endforelse
                            </div>
                        </td>
                        <td style="white-space: nowrap;">
                            @if($u->employee)
                                <a href="{{ route('hr.people.employees.show', $u->employee->id) }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 7px; padding: 4px 10px 4px 7px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; transition: all 0.15s ease; white-space: nowrap;" onmouseover="this.style.background='#faf5ff'; this.style.borderColor='#c084fc'" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'" title="View {{ $u->employee->full_name }} 201 File">
                                    <span style="width: 22px; height: 22px; border-radius: 50%; background: #f3e8ff; color: #7c3aed; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0;">
                                        <i class="ph ph-identification-card"></i>
                                    </span>
                                    <span style="font-size: 12.5px; font-weight: 600; color: #0f172a; white-space: nowrap;">{{ $u->employee->full_name }}</span>
                                    <span style="font-size: 10.5px; color: #64748b; font-family: monospace; background: #ffffff; border: 1px solid #e2e8f0; padding: 1.5px 6px; border-radius: 10px; font-weight: 500; white-space: nowrap;">{{ $u->employee->employee_id }}</span>
                                </a>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 5px; color: #94a3b8; font-size: 12px; font-style: italic; white-space: nowrap;">
                                    <i class="ph ph-user-minus"></i> No Employee Linked
                                </span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            @if($u->branch)
                                <span class="hr-badge hr-badge-neutral" style="white-space: nowrap;"><i class="ph ph-storefront"></i> {{ $u->branch->name }}</span>
                            @else
                                <span class="hr-badge hr-badge-success" style="white-space: nowrap;"><i class="ph ph-globe"></i> All Branches</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            <span class="hr-badge {{ $u->status === 'Active' ? 'hr-badge-success' : 'hr-badge-danger' }}" style="white-space: nowrap;">
                                {{ $u->status }}
                            </span>
                        </td>
                        <td style="color: #94a3b8; font-size: 12px; white-space: nowrap;">{{ $u->updated_at ? $u->updated_at->diffForHumans() : '—' }}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <button class="icon-btn" title="Edit User" onclick="editUser({{ json_encode($u) }})">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button class="icon-btn" title="Reset Password" onclick="resetPassword({{ $u->id }}, '{{ $u->username }}')">
                                    <i class="ph ph-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No user accounts matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="usersPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="usersPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="usersPerPageSelect" onchange="usersChangePerPage()" aria-label="Rows per page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="usersPageNav"></nav>
    </div>
</div>

<!-- Modal: Add User -->
<div id="addUserModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-user-plus"></i> Create User Account</span>
            <button class="icon-btn" onclick="closeModal('addUserModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.admin.users.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Username *</label>
                        <input type="text" name="username" class="hr-input" required placeholder="e.g. jdelacruz">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Full Name *</label>
                        <input type="text" name="full_name" class="hr-input" required placeholder="e.g. Juan Dela Cruz">
                    </div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email Address *</label>
                        <input type="email" name="email" class="hr-input" required placeholder="e.g. juan@restaurant.ph">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Initial Password *</label>
                        <input type="password" name="password" class="hr-input" required minlength="8" placeholder="At least 8 characters">
                    </div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Role *</label>
                        <select name="role_id" class="hr-select" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->description }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Branch Access Scope</label>
                        <select name="branch_id" class="hr-select">
                            <option value="">All Branches (HQ / Full Access)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} Only</option>
                            @endforeach
                        </select>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Restaurant Managers should be scoped to their specific branch.</div>
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Link to Database Employee Profile</label>
                    <select name="employee_id" class="hr-select">
                        <option value="">-- None (Standalone Account) --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_id }}) - {{ $emp->branch?->name ?? 'Universal' }}</option>
                        @endforeach
                    </select>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Connects this user login with their employee master record in the database.</div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Account Status *</label>
                    <select name="status" class="hr-select" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit User -->
<div id="editUserModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-pencil-simple"></i> Edit User Account</span>
            <button class="icon-btn" onclick="closeModal('editUserModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="editUserForm" method="POST" action="">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Full Name *</label>
                    <input type="text" id="edit_full_name" name="full_name" class="hr-input" required>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Email Address *</label>
                    <input type="email" id="edit_email" name="email" class="hr-input" required>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Role *</label>
                        <select id="edit_role_id" name="role_id" class="hr-select" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Branch Access Scope</label>
                        <select id="edit_branch_id" name="branch_id" class="hr-select">
                            <option value="">All Branches (HQ / Full Access)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} Only</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Link to Database Employee Profile</label>
                    <select id="edit_employee_id" name="employee_id" class="hr-select">
                        <option value="">-- None (Standalone Account) --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_id }}) - {{ $emp->branch?->name ?? 'Universal' }}</option>
                        @endforeach
                    </select>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Connects this user login with their employee master record in the database.</div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Account Status *</label>
                    <select id="edit_status" name="status" class="hr-select" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Password Reset -->
<div id="resetPasswordModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-key"></i> Reset User Password</span>
            <button class="icon-btn" onclick="closeModal('resetPasswordModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="resetPasswordForm" method="POST" action="">
            @csrf
            <div class="hr-modal-body">
                <p style="color: #475569; font-size: 13px; margin-bottom: 16px;">
                    Resetting password for user: <strong id="reset_username_label" style="color: #0f172a;"></strong>
                </p>
                <div class="hr-form-group">
                    <label class="hr-form-label">New Password *</label>
                    <input type="password" name="new_password" class="hr-input" required minlength="8" placeholder="At least 8 characters">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('resetPasswordModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save New Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
