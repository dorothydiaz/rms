@extends('layouts.app')

@section('title', 'User Accounts - Administration')

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

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper" id="usersTableWrapper" style="overflow-y:auto; overflow-x:auto;">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Username / Name</th>
                    <th>Email</th>
                    <th>Assigned Role</th>
                    <th>Linked Employee Profile</th>
                    <th>Branch Access Scope</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                @forelse($users as $u)
                    <tr class="user-row">
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
                            @if($u->employee)
                                <a href="{{ route('hr.people.employees.show', $u->employee->id) }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600;">
                                        <i class="ph ph-identification-card" style="color: #7c3aed;"></i> {{ $u->employee->full_name }}
                                    </span>
                                    <span style="font-size: 11px; color: #64748b; font-family: monospace;">{{ $u->employee->employee_id }}</span>
                                </a>
                            @else
                                <span style="color: #94a3b8; font-size: 12px; font-style: italic;">No Employee Linked</span>
                            @endif
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
                    <option value="15" selected>15</option>
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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Users
// =========================================================================
let _usersPage = 1, _usersRows = [];
function usersGetPerPage() { return parseInt(document.getElementById('usersPerPageSelect')?.value || '15', 10); }
function usersChangePerPage() { _usersPage = 1; renderUsersPage(); }
function usersGoToPage(p) { _usersPage = p; renderUsersPage(); const w = document.getElementById('usersTableWrapper'); if(w) w.scrollTop = 0; }

function renderUsersPage() {
    const pp = usersGetPerPage(), total = _usersRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_usersPage > totalPages) _usersPage = totalPages;
    const start = (_usersPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#usersTableBody tr.user-row').forEach(r => r.style.display = 'none');
    _usersRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });


    const bar = document.getElementById('usersPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('usersPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start+1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> users`;
    const nav = document.getElementById('usersPageNav');
    if (!nav) return;
    let h = _usersPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="usersGoToPage(${_usersPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _usersPage-2), pe = Math.min(totalPages, _usersPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="usersGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _usersPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="usersGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="usersGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _usersPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="usersGoToPage(${_usersPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

document.addEventListener('DOMContentLoaded', () => {
    _usersRows = Array.from(document.querySelectorAll('#usersTableBody tr.user-row'));
    renderUsersPage();
});

function editUser(user) {
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_branch_id').value = user.branch_id || '';
    document.getElementById('edit_status').value = user.status;
    document.getElementById('edit_employee_id').value = user.employee ? user.employee.id : '';
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
