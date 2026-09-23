@extends('layouts.app')

@section('title', 'User Accounts - Administration')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-users-three"></i>
            User Accounts & Administrative Access
        </h1>
        <p class="hr-page-subtitle">Manage Super Admins, HR Officers, and Branch Restaurant Managers</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addUserModal')">
            <i class="ph ph-user-plus"></i>
            <span>Add User</span>
        </button>
    </div>
</div>

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.admin.users') }}" style="display: flex; gap: 12px; width: 100%; align-items: center; flex-wrap: wrap;">
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

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Username / Name</th>
                    <th>Email</th>
                    <th>Assigned Role</th>
                    <th>Branch Access Scope</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <strong>{{ $u->full_name }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">@ {{ $u->username }}</div>
                        </td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @foreach($u->roles as $role)
                                <span class="hr-badge {{ $role->slug === 'super-admin' ? 'hr-badge-purple' : ($role->slug === 'hr-admin' ? 'hr-badge-blue' : 'hr-badge-warning') }}">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @if($u->branch)
                                <span class="hr-badge hr-badge-neutral"><i class="ph ph-storefront"></i> {{ $u->branch->name }}</span>
                            @else
                                <span class="hr-badge hr-badge-success"><i class="ph ph-globe"></i> All Branches</span>
                            @endif
                        </td>
                        <td>
                            <span class="hr-badge {{ $u->status === 'Active' ? 'hr-badge-success' : 'hr-badge-danger' }}">
                                {{ $u->status }}
                            </span>
                        </td>
                        <td style="color: #94a3b8; font-size: 12px;">{{ $u->updated_at ? $u->updated_at->diffForHumans() : '—' }}</td>
                        <td style="text-align: right;">
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
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No user accounts matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div style="padding: 16px;">
            {{ $users->links() }}
        </div>
    @endif
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
                <p style="color: #cbd5e1; font-size: 13px; margin-bottom: 16px;">
                    Resetting password for user: <strong id="reset_username_label" style="color: #fff;"></strong>
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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function editUser(user) {
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_branch_id').value = user.branch_id || '';
    document.getElementById('edit_status').value = user.status;
    if (user.roles && user.roles.length > 0) {
        document.getElementById('edit_role_id').value = user.roles[0].id;
    }
    document.getElementById('editUserForm').action = "/hr/admin/users/" + user.id;
    openModal('editUserModal');
}

function resetPassword(userId, username) {
    document.getElementById('reset_username_label').innerText = '@' + username;
    document.getElementById('resetPasswordForm').action = "/hr/admin/users/" + userId + "/reset-password";
    openModal('resetPasswordModal');
}
</script>
@endpush
@endsection
