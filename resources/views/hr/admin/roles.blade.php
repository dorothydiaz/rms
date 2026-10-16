@extends('layouts.app')

@section('title', 'Roles & Permissions - Administration')

@push('blade-data')
<script>
    window.HrRolesConfig = Object.freeze({
        rolePermissionsMap: {
            @foreach($roles as $r)
                "{{ $r->slug }}": @json($r->permissions->pluck('id')->toArray()),
            @endforeach
        }
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/hr-roles.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-roles.js'])
@endpush

@section('content')
<x-hr-tabs parent="system-administration">
    <x-slot:actions>
        <button type="button" class="hr-btn hr-btn-primary" onclick="openModal('addRoleModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add New Role</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Sub-navigation Tabs: Roles vs Employee Permissions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 14px;">
    <div style="display: inline-flex; gap: 6px; background: rgba(255, 255, 255, 0.95); padding: 5px; border-radius: 12px; border: 1.5px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
        <button type="button" id="tabBtnRoles" class="role-tab-btn {{ request('tab', 'roles') === 'roles' ? 'active' : '' }}" onclick="switchSubTab('roles')">
            <i class="ph ph-shield-check"></i>
            <span>Role Permissions</span>
            <span class="role-tab-badge">{{ $roles->count() }}</span>
        </button>
        <button type="button" id="tabBtnEmployees" class="role-tab-btn {{ request('tab') === 'employees' ? 'active' : '' }}" onclick="switchSubTab('employees')">
            <i class="ph ph-users-three"></i>
            <span>Employee Permissions</span>
            <span class="role-tab-badge">{{ $employees->count() }}</span>
        </button>
    </div>

    <!-- Quick Stats -->
    <div style="display: flex; gap: 10px; align-items: center;">
        <span class="hr-badge hr-badge-purple" style="font-size: 12.5px; padding: 6px 12px;">
            <i class="ph ph-lock-key"></i> Total Capabilities: {{ $permissions->flatten()->count() }}
        </span>
        <span class="hr-badge hr-badge-neutral" style="font-size: 12.5px; padding: 6px 12px;">
            <i class="ph ph-buildings"></i> Branches: {{ $branches->count() }}
        </span>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. ROLE PERMISSIONS VIEW -->
<!-- ========================================================================= -->
<div id="viewRolesSection" style="{{ request('tab', 'roles') === 'roles' ? 'display: flex; flex-direction: column; gap: 24px;' : 'display: none; flex-direction: column; gap: 24px;' }}">
    <div style="background: rgba(255, 255, 255, 0.95); border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(124, 58, 237, 0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class="ph ph-shield-star"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Standard Role Templates</h4>
                <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;">Configure global default permissions granted to system roles. These serve as baseline capabilities for assigned users.</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="switchSubTab('employees')">
                <i class="ph ph-user-gear"></i>
                <span>Edit Individual Employee Permissions &rarr;</span>
            </button>
        </div>
    </div>

    @foreach($roles as $role)
        @php
            $isCoreRole = in_array($role->slug, ['super-admin', 'hr-admin', 'restaurant-manager', 'staff', 'cashier', 'kitchen']);
        @endphp
        <div class="hr-card" style="box-shadow: 0 4px 18px rgba(124, 58, 237, 0.05); border: 1.5px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="hr-badge {{ $role->slug === 'super-admin' ? 'hr-badge-purple' : ($role->slug === 'hr-admin' ? 'hr-badge-blue' : ($role->slug === 'restaurant-manager' ? 'hr-badge-warning' : 'hr-badge-neutral')) }}" style="font-size: 13.5px; font-weight: 700; padding: 6px 14px;">
                        <i class="ph ph-shield"></i> {{ $role->name }}
                    </span>
                    <span style="font-size: 13px; color: #64748b; font-weight: 400;">{{ $role->description ?: 'Custom defined operational role.' }}</span>
                    @if(!$isCoreRole)
                        <span class="hr-badge hr-badge-purple" style="font-size: 11px;">Custom Role</span>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="hr-badge hr-badge-neutral" style="font-size: 12px;">
                        Total Assigned: <strong style="color: #7c3aed; margin-left: 4px;">{{ $role->permissions->count() }}</strong> / {{ $permissions->flatten()->count() }}
                    </span>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleRoleCardAll('role-form-{{ $role->id }}', true)">
                        Select All
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleRoleCardAll('role-form-{{ $role->id }}', false)">
                        Deselect All
                    </button>
                    @if(!$isCoreRole)
                        <form method="POST" action="{{ route('hr.admin.roles.destroy', $role->id) }}" onsubmit="return confirm('Are you sure you want to delete role {{ addslashes($role->name) }}? Users assigned to this role will lose its default permissions.');" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete custom role">
                                <i class="ph ph-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <form id="role-form-{{ $role->id }}" method="POST" action="{{ route('hr.admin.roles.permissions', $role->id) }}">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 18px;">
                    @foreach($permissions as $moduleName => $perms)
                        @php
                            $assignedIds = $role->permissions->pluck('id')->toArray();
                            $moduleAssignedCount = $perms->whereIn('id', $assignedIds)->count();
                        @endphp
                        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); transition: all 0.2s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                                <h4 style="font-size: 12.5px; font-weight: 700; color: #7c3aed; margin: 0; text-transform: uppercase; letter-spacing: 0.6px; display: flex; align-items: center; gap: 7px;">
                                    <i class="ph ph-folder-open"></i> {{ $moduleName }}
                                </h4>
                                <span style="font-size: 11px; font-weight: 600; color: #94a3b8; background: #f8fafc; padding: 2px 7px; border-radius: 6px;">
                                    {{ $moduleAssignedCount }}/{{ $perms->count() }}
                                </span>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 9px;">
                                @foreach($perms as $p)
                                    <label style="display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: #334155; cursor: pointer; font-weight: 500; line-height: 1.35; padding: 4px 6px; border-radius: 6px; transition: background 0.15s;">
                                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}" {{ in_array($p->id, $assignedIds) ? 'checked' : '' }} style="accent-color: #7c3aed; width: 16px; height: 16px; margin-top: 1px; cursor: pointer;">
                                        <span>{{ $p->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top: 22px; display: flex; justify-content: flex-end; padding-top: 16px; border-top: 1.5px solid #f1f5f9;">
                    <button type="submit" class="hr-btn hr-btn-primary">
                        <i class="ph ph-floppy-disk"></i>
                        <span>Save Permissions for {{ $role->name }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endforeach
</div>

<!-- ========================================================================= -->
<!-- 2. INDIVIDUAL EMPLOYEE PERMISSIONS VIEW -->
<!-- ========================================================================= -->
<div id="viewEmployeesSection" style="{{ request('tab') === 'employees' ? 'display: block;' : 'display: none;' }}">
    <!-- Information Box -->
    <div style="background: rgba(255, 255, 255, 0.95); border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class="ph ph-user-gear"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Individual Employee Custom Permissions</h4>
                <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;">
                    Granular permissions assigned directly to employees. These override or extend standard role templates for special responsibilities.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="switchSubTab('roles')">
                <i class="ph ph-shield-check"></i>
                <span>&larr; View Role Templates</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="hr-filter-bar" style="margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: nowrap;">
        <!-- Filters & Search Controls Group -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; flex: 1;">
            <!-- Search Input -->
            <div style="position: relative; width: 220px; flex-shrink: 0;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
                <input type="text" id="employeeSearchInput" class="hr-input" placeholder="Search employee, ID, role..." oninput="filterEmployeeTable()" style="width: 100%; box-sizing: border-box; padding-left: 28px; height: 31px; font-size: 12px;">
            </div>

            <select id="branchFilterSelect" class="hr-select" style="height: 31px; max-width: 160px; font-size: 12px;" onchange="filterEmployeeTable()">
                <option value="">-- All Branches --</option>
                @foreach($branches as $b)
                    <option value="{{ $b->name }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>

            <select id="permStatusFilter" class="hr-select" style="height: 31px; max-width: 175px; font-size: 12px;" onchange="filterEmployeeTable()">
                <option value="">-- All Permission States --</option>
                <option value="custom">Has Custom Permissions</option>
                <option value="defaults">Role Defaults Only</option>
                <option value="has_user">Has System User</option>
            </select>

            <!-- Sort Options in Toolbar -->
            <select id="rolesSortSelect" class="hr-select" style="height: 31px; max-width: 175px; font-size: 12px;" onchange="applyRolesSortFromSelect(this.value)">
                <option value="">Sort By: Default</option>
                <option value="name_asc">Employee (A &rarr; Z)</option>
                <option value="name_desc">Employee (Z &rarr; A)</option>
                <option value="pos_asc">Position / Dept (A &rarr; Z)</option>
                <option value="branch_asc">Branch (A &rarr; Z)</option>
                <option value="role_asc">Role (A &rarr; Z)</option>
                <option value="perms_desc">Direct Permissions (Most First)</option>
                <option value="perms_asc">Direct Permissions (Least First)</option>
            </select>

            <button type="button" class="hr-btn hr-btn-secondary" onclick="resetEmployeeFilters()" title="Reset all filters" style="height: 38px; padding: 0 14px;">
                <i class="ph ph-arrows-counter-clockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- Live Counter Badge -->
        <div style="flex-shrink: 0;">
            <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 8px; background: #f1f5f9; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
                Showing <strong id="displayedEmpCount" style="color: #0f172a;">{{ $employees->count() }}</strong> employees
            </span>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="hr-table-card">
        <div class="hr-table-wrapper" style="max-height: 560px; overflow-y: auto; overflow-x: auto;">
            <table class="hr-table" id="employeePermissionsTable">
                <thead>
                    <tr>
                        <th class="sortable" onclick="sortEmployeesTable(0, 'text')" title="Click to sort by Employee Name (A-Z / Z-A)">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Employee</span>
                                <span style="display: inline-flex; align-items: center;">
                                    <span class="sort-badge asc">ASC</span>
                                    <span class="sort-badge desc">DESC</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                                </span>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortEmployeesTable(1, 'text')" title="Click to sort by Position & Department">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Position & Department</span>
                                <span style="display: inline-flex; align-items: center;">
                                    <span class="sort-badge asc">ASC</span>
                                    <span class="sort-badge desc">DESC</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                                </span>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortEmployeesTable(2, 'text')" title="Click to sort by Branch (A-Z / Z-A)">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Branch</span>
                                <span style="display: inline-flex; align-items: center;">
                                    <span class="sort-badge asc">ASC</span>
                                    <span class="sort-badge desc">DESC</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                                </span>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortEmployeesTable(3, 'text')" title="Click to sort by System User & Role">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>System User & Role</span>
                                <span style="display: inline-flex; align-items: center;">
                                    <span class="sort-badge asc">ASC</span>
                                    <span class="sort-badge desc">DESC</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                                </span>
                            </div>
                        </th>
                        <th class="sortable" onclick="sortEmployeesTable(4, 'number')" title="Click to sort by Direct Permissions Count">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Direct Permissions</span>
                                <span style="display: inline-flex; align-items: center;">
                                    <span class="sort-badge asc">ASC</span>
                                    <span class="sort-badge desc">DESC</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                                </span>
                            </div>
                        </th>
                        <th style="text-align: right; width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        @php
                            $role = $emp->assigned_role;
                            $roleName = $role ? $role->name : 'Staff';
                            $roleSlug = $role ? $role->slug : 'staff';
                            $customCount = $emp->permissions->count();
                            $hasCustom = $customCount > 0;
                            $hasUser = !empty($emp->user);
                        @endphp
                        <tr class="emp-row" 
                            data-name="{{ strtolower($emp->full_name) }}" 
                            data-emp-id="{{ strtolower($emp->employee_id) }}" 
                            data-position="{{ strtolower($emp->position?->name ?? '') }}" 
                            data-branch="{{ $emp->branch?->name ?? '' }}" 
                            data-role="{{ strtolower($roleName) }}"
                            data-permissions-count="{{ $customCount }}"
                            data-has-custom="{{ $hasCustom ? 'true' : 'false' }}" 
                            data-has-user="{{ $hasUser ? 'true' : 'false' }}">
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    @if($emp->photo_url)
                                        <img src="{{ $emp->photo_url }}" alt="{{ $emp->full_name }}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid #cbd5e1;">
                                    @else
                                        <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; border: 1.5px solid rgba(124, 58, 237, 0.2);">
                                            {{ $emp->initials }}
                                        </div>
                                    @endif
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">{{ $emp->full_name }}</div>
                                        <div style="font-size: 11.5px; color: #64748b; font-family: monospace;">{{ $emp->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #334155; font-size: 13px;">{{ $emp->position?->name ?? 'Staff' }}</div>
                                <div style="font-size: 11.5px; color: #64748b;">{{ $emp->department?->name ?? 'Operations' }}</div>
                            </td>
                            <td>
                                <span class="hr-badge hr-badge-neutral" style="font-size: 12px;">
                                    <i class="ph ph-storefront"></i> {{ $emp->branch?->name ?? 'Universal Access' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <div>
                                        <span class="hr-badge {{ $roleSlug === 'super-admin' ? 'hr-badge-purple' : ($roleSlug === 'hr-admin' ? 'hr-badge-blue' : ($roleSlug === 'restaurant-manager' ? 'hr-badge-warning' : 'hr-badge-neutral')) }}" style="font-size: 11.5px;">
                                            {{ $roleName }}
                                        </span>
                                    </div>
                                    @if($hasUser)
                                        <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                                            <i class="ph ph-user-check" style="color: #10b981;"></i> @ {{ $emp->user->username }}
                                        </div>
                                    @else
                                        <div style="font-size: 11.5px; color: #94a3b8; font-style: italic;">
                                            No login account
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($hasCustom)
                                    <span class="hr-badge hr-badge-purple" style="font-size: 12px; font-weight: 700;">
                                        <i class="ph ph-check-circle"></i> {{ $customCount }} Direct Permissions
                                    </span>
                                @else
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 12px; color: #64748b;">
                                        <i class="ph ph-shield"></i> Role Defaults Only
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button type="button" 
                                    class="hr-btn hr-btn-primary hr-btn-sm" 
                                    data-emp-id="{{ $emp->id }}"
                                    data-emp-name="{{ e($emp->full_name) }}"
                                    data-emp-code="{{ e($emp->employee_id) }}"
                                    data-emp-position="{{ e($emp->position?->name ?? 'Staff') }}"
                                    data-emp-branch="{{ e($emp->branch?->name ?? 'Universal Access') }}"
                                    data-emp-role-name="{{ e($roleName) }}"
                                    data-emp-role-slug="{{ e($roleSlug) }}"
                                    data-emp-initials="{{ e($emp->initials) }}"
                                    data-emp-photo="{{ e($emp->photo_url ?? '') }}"
                                    data-emp-has-user="{{ $hasUser ? '1' : '0' }}"
                                    data-emp-username="{{ e($emp->user?->username ?? '') }}"
                                    data-emp-perms="{{ json_encode($emp->permissions->pluck('id')->toArray()) }}"
                                    onclick="openEmployeePermissionsFromBtn(this)">
                                    <i class="ph ph-sliders"></i>
                                    <span>Edit Permissions</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 40px;">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. SLIDE-OVER RIGHT MODAL: EDIT EMPLOYEE PERMISSIONS -->
<!-- ========================================================================= -->
<div class="hr-modal-overlay" id="editEmployeePermissionsModal">
    <div class="hr-modal" style="max-width: 640px; background: #ffffff !important; border-left: 2px solid #cbd5e1; box-shadow: -10px 0 35px rgba(0, 0, 0, 0.15);">
        <!-- Modal Header -->
        <div class="hr-modal-header" style="background: #ffffff !important; border-bottom: 1.5px solid #e2e8f0; padding: 18px 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 9px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="ph ph-lock-key-open"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Edit Employee Permissions</span>
                        <div style="font-size: 12px; color: #64748b;">Configure individual capabilities and access overrides</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('editEmployeePermissionsModal')" style="font-size: 18px;">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <form id="employeePermissionsForm" method="POST" action="">
            @csrf
            <div class="hr-modal-body" style="padding: 20px 24px; background: #ffffff !important;">
                <!-- Employee Info Card -->
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div id="modalEmpAvatarWrap">
                            <div id="modalEmpInitials" style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px;">
                                EM
                            </div>
                        </div>
                        <div>
                            <div id="modalEmpName" style="font-size: 15px; font-weight: 700; color: #0f172a;">Employee Name</div>
                            <div style="font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                <span id="modalEmpId" style="font-family: monospace; font-weight: 600; color: #7c3aed;">EMP-000</span>
                                <span>&bull;</span>
                                <span id="modalEmpPosition">Position</span>
                                <span>&bull;</span>
                                <span id="modalEmpBranch">Branch</span>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span id="modalEmpRoleBadge" class="hr-badge hr-badge-purple" style="font-size: 12px;">
                            Staff
                        </span>
                        <div id="modalEmpUserBadge" style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            No User
                        </div>
                    </div>
                </div>

                <!-- Explanation Banner -->
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: 12.5px; color: #1e40af; line-height: 1.4; display: flex; gap: 10px;">
                    <i class="ph ph-info" style="font-size: 18px; color: #3b82f6; flex-shrink: 0; margin-top: 1px;"></i>
                    <div>
                        Checked permissions below will be <strong>directly assigned</strong> to this employee. Permissions with the <span style="display: inline-block; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-size: 11px; font-weight: 600;"><i class="ph ph-shield"></i> Role Default</span> badge are inherited from their default role.
                    </div>
                </div>

                <!-- Permission Controls & Filter -->
                <div style="display: flex; gap: 8px; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap;">
                    <div style="position: relative; flex: 1; min-width: 200px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" id="modalPermissionSearch" class="hr-input" placeholder="Search permissions..." oninput="filterModalPermissions(this.value)" style="padding-left: 32px; font-size: 12.5px; height: 34px;">
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="checkAllModalPermissions(true)" title="Check all permissions">
                            Check All
                        </button>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="checkAllModalPermissions(false)" title="Clear all checkboxes">
                            Clear All
                        </button>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnApplyRoleDefaults" onclick="applyRoleDefaultsToModal()" style="color: #7c3aed; border-color: rgba(124, 58, 237, 0.3);" title="Set checkboxes matching employee's role default">
                            <i class="ph ph-arrow-counter-clockwise"></i> Role Defaults
                        </button>
                    </div>
                </div>

                <!-- Grouped Permissions List -->
                <div id="modalPermissionsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                    @foreach($permissions as $moduleName => $perms)
                        <div class="modal-module-card" data-module="{{ strtolower($moduleName) }}" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9;">
                                <h4 style="font-size: 12px; font-weight: 700; color: #7c3aed; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                    <i class="ph ph-folder-open"></i> {{ $moduleName }}
                                </h4>
                                <span class="module-checked-badge" data-module="{{ $moduleName }}" style="font-size: 11px; color: #64748b; font-weight: 600; background: #f8fafc; padding: 2px 7px; border-radius: 5px;">
                                    0/{{ $perms->count() }}
                                </span>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                @foreach($perms as $p)
                                    <label class="modal-perm-item" data-perm-name="{{ strtolower($p->name) }}" data-perm-id="{{ $p->id }}" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; font-size: 13px; color: #334155; cursor: pointer; padding: 6px 8px; border-radius: 6px; border: 1px solid transparent; transition: all 0.15s;">
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <input type="checkbox" name="permissions[]" value="{{ $p->id }}" class="emp-perm-chk" data-perm-id="{{ $p->id }}" onchange="updateModalCounts()" style="accent-color: #7c3aed; width: 16px; height: 16px; margin-top: 1px; cursor: pointer;">
                                            <span style="line-height: 1.35; font-weight: 500;">{{ $p->name }}</span>
                                        </div>
                                        <span class="role-default-pill" data-perm-id="{{ $p->id }}" style="display: none; font-size: 10.5px; font-weight: 600; color: #4338ca; background: #e0e7ff; padding: 2px 6px; border-radius: 4px; white-space: nowrap;">
                                            <i class="ph ph-shield"></i> Role
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="hr-modal-footer" style="background: #ffffff !important; border-top: 1.5px solid #e2e8f0; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12.5px; color: #64748b;">
                    Total Granted: <strong id="modalGrantedCount" style="color: #7c3aed; font-size: 14px;">0</strong> / {{ $permissions->flatten()->count() }}
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editEmployeePermissionsModal')">Cancel</button>
                    <button type="submit" class="hr-btn hr-btn-primary">
                        <i class="ph ph-check-circle"></i> Save Employee Permissions
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. SLIDE-OVER RIGHT MODAL: CREATE NEW ROLE -->
<!-- ========================================================================= -->
<div class="hr-modal-overlay" id="addRoleModal">
    <div class="hr-modal" style="max-width: 640px; background: #ffffff !important; border-left: 2px solid #cbd5e1; box-shadow: -10px 0 35px rgba(0, 0, 0, 0.15);">
        <!-- Modal Header -->
        <div class="hr-modal-header" style="background: #ffffff !important; border-bottom: 1.5px solid #e2e8f0; padding: 18px 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 9px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="ph ph-shield-plus"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Create New Role</span>
                        <div style="font-size: 12px; color: #64748b;">Define a new system role and assign baseline capabilities</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('addRoleModal')" style="font-size: 18px;">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('hr.admin.roles.store') }}">
            @csrf
            <div class="hr-modal-body" style="padding: 20px 24px; background: #ffffff !important;">
                <div class="hr-form-group" style="margin-bottom: 16px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">
                        Role Name *
                    </label>
                    <input type="text" name="name" id="newRoleName" class="hr-input" required placeholder="e.g. Inventory Supervisor, Shift Lead, Assistant Manager" oninput="autoGenerateSlug(this.value)">
                </div>

                <div class="hr-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">
                            Role Key / Slug <span style="font-weight: 400; color: #64748b;">(Auto-generated)</span>
                        </label>
                        <input type="text" name="slug" id="newRoleSlug" class="hr-input" placeholder="e.g. inventory-supervisor" style="font-family: monospace;">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">
                            Role Scope
                        </label>
                        <input type="text" class="hr-input" value="Custom Operational Role" readonly style="background: #f8fafc; color: #64748b;">
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 18px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">
                        Description / Responsibilities
                    </label>
                    <textarea name="description" class="hr-input" rows="2" placeholder="e.g. Oversees daily stock levels, reviews wastage logs, and approves inventory transfers."></textarea>
                </div>

                <!-- Initial Permissions Section -->
                <div style="border-top: 1.5px solid #e2e8f0; padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: #0f172a;">Assign Initial Permissions</h4>
                            <div style="font-size: 11.5px; color: #64748b;">Select the capabilities this role should have by default</div>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleNewRolePerms(true)">Select All</button>
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleNewRolePerms(false)">Clear All</button>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; max-height: 380px; overflow-y: auto; padding-right: 4px;">
                        @foreach($permissions as $moduleName => $perms)
                            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #f1f5f9;">
                                    <h5 style="font-size: 12px; font-weight: 700; color: #7c3aed; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                        <i class="ph ph-folder-open"></i> {{ $moduleName }}
                                    </h5>
                                    <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">{{ $perms->count() }} items</span>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 7px;">
                                    @foreach($perms as $p)
                                        <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: #334155; cursor: pointer; padding: 4px 6px; border-radius: 5px;">
                                            <input type="checkbox" name="permissions[]" value="{{ $p->id }}" class="new-role-perm-chk" style="accent-color: #7c3aed; width: 15px; height: 15px; margin-top: 1px; cursor: pointer;">
                                            <span style="line-height: 1.35; font-weight: 500;">{{ $p->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="hr-modal-footer" style="background: #ffffff !important; border-top: 1.5px solid #e2e8f0; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addRoleModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-plus-circle"></i> Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
