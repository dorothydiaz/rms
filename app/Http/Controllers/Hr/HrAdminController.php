<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AuditLog;
use App\Models\Hr\Branch;
use App\Models\Hr\Company;
use App\Models\Hr\Employee;
use App\Models\Hr\InternalNotification;
use App\Models\Hr\Permission;
use App\Models\Hr\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HrAdminController extends Controller
{
    // ==========================================
    // 1. USER MANAGEMENT
    // ==========================================

    public function usersIndex(Request $request): View
    {
        $query = User::with(['roles', 'branch']);

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('slug', $request->role));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $perPage = (int) $request->get('per_page', 10);
        $users = $query->paginate($perPage)->withQueryString();
        $roles = Role::all();
        $branches = Branch::where('is_active', true)->get();

        return view('hr.admin.users', compact('users', 'roles', 'branches'));
    }

    public function userStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:Active,Inactive',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $legacyRole = in_array($role->name, ['Admin', 'Manager', 'Staff', 'Cashier', 'Kitchen']) ? $role->name : 'Staff';
        if ($role->slug === 'super-admin' || $role->slug === 'hr-admin') $legacyRole = 'Admin';
        if ($role->slug === 'restaurant-manager') $legacyRole = 'Manager';

        $user = User::create([
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $legacyRole,
            'branch_id' => $validated['branch_id'] ?? null,
            'status' => $validated['status'],
        ]);

        $user->roles()->sync([$role->id]);

        AuditLogger::log('Create', 'RBAC', $user->id, "Created administrative user {$user->username} with role {$role->name}");

        return redirect()->back()->with('success', "User '{$user->username}' created successfully.");
    }

    public function userUpdate(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $id,
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:Active,Inactive',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $legacyRole = in_array($role->name, ['Admin', 'Manager', 'Staff', 'Cashier', 'Kitchen']) ? $role->name : 'Staff';
        if ($role->slug === 'super-admin' || $role->slug === 'hr-admin') $legacyRole = 'Admin';
        if ($role->slug === 'restaurant-manager') $legacyRole = 'Manager';

        $user->update([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'role' => $legacyRole,
            'branch_id' => $validated['branch_id'] ?? null,
            'status' => $validated['status'],
        ]);

        $user->roles()->sync([$role->id]);

        AuditLogger::log('Update', 'RBAC', $user->id, "Updated user {$user->username} profile/role");

        return redirect()->back()->with('success', "User updated successfully.");
    }

    public function userPasswordReset(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        AuditLogger::log('Update', 'RBAC', $user->id, "Reset password for user {$user->username}");

        return redirect()->back()->with('success', "Password reset successfully for {$user->username}.");
    }

    // ==========================================
    // 2. ROLES & PERMISSIONS
    // ==========================================

    public function rolesIndex(Request $request): View
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('module');

        $employeeQuery = Employee::with([
            'user.roles',
            'permissions',
            'branch',
            'position',
            'department',
            'company',
        ])->orderBy('first_name')->orderBy('last_name');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $employeeQuery->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('employee_id', 'like', $term)
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('username', 'like', $term)->orWhere('email', 'like', $term);
                  });
            });
        }

        if ($request->filled('branch_id')) {
            $employeeQuery->where('branch_id', $request->branch_id);
        }

        $employees = $employeeQuery->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('hr.admin.roles', compact('roles', 'permissions', 'employees', 'branches'));
    }

    public function roleUpdatePermissions(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        $permissionIds = $request->input('permissions', []);

        $role->permissions()->sync($permissionIds);
        AuditLogger::log('Update', 'RBAC', $role->id, "Updated permission set for role '{$role->name}'");

        return redirect()->route('hr.admin.roles', ['tab' => 'roles'])->with('success', "Permissions updated for role '{$role->name}'.");
    }

    public function roleStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'slug' => 'nullable|string|max:50|unique:roles,slug',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = !empty($validated['slug']) 
            ? Str::slug($validated['slug']) 
            : Str::slug($validated['name']);

        $baseSlug = $slug;
        $counter = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        $permissionIds = $request->input('permissions', []);
        if (!empty($permissionIds)) {
            $role->permissions()->sync($permissionIds);
        }

        $count = count($permissionIds);
        AuditLogger::log('Create', 'RBAC', $role->id, "Created new role '{$role->name}' ({$slug}) with {$count} initial permissions");

        return redirect()->route('hr.admin.roles', ['tab' => 'roles'])->with('success', "Role '{$role->name}' created successfully with {$count} permissions.");
    }

    public function roleDestroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        $systemRoles = ['super-admin', 'hr-admin', 'restaurant-manager', 'staff', 'cashier', 'kitchen'];

        if (in_array($role->slug, $systemRoles)) {
            return redirect()->route('hr.admin.roles', ['tab' => 'roles'])->with('error', "System core role '{$role->name}' cannot be deleted.");
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        AuditLogger::log('Delete', 'RBAC', $id, "Deleted custom role '{$roleName}'");

        return redirect()->route('hr.admin.roles', ['tab' => 'roles'])->with('success', "Role '{$roleName}' deleted successfully.");
    }

    public function employeeUpdatePermissions(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $permissionIds = $request->input('permissions', []);

        $employee->permissions()->sync($permissionIds);

        if ($employee->user) {
            $employee->user->permissions()->sync($permissionIds);
        }

        $count = count($permissionIds);
        AuditLogger::log('Update', 'RBAC', $employee->id, "Updated permissions for employee {$employee->full_name} ({$count} permissions assigned)");

        return redirect()->route('hr.admin.roles', ['tab' => 'employees'])->with('success', "Permissions successfully updated for {$employee->full_name} ({$count} permissions assigned).");
    }

    // ==========================================
    // 3. SYSTEM SETTINGS
    // ==========================================

    public function settingsIndex(): View
    {
        $company = Company::first();
        return view('hr.admin.settings', compact('company'));
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $company = Company::first();
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50',
            'tin' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        if ($company) {
            $company->update($validated);
        } else {
            Company::create($validated);
        }

        AuditLogger::log('Update', 'Settings', $company?->id, "Updated company and system settings");

        return redirect()->back()->with('success', "System settings updated successfully.");
    }

    // ==========================================
    // 4. AUDIT LOGS
    // ==========================================

    public function auditLogsIndex(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = (int) $request->get('per_page', 10);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $users = User::all();

        return view('hr.admin.audit-logs', compact('logs', 'users'));
    }

    // ==========================================
    // 5. INTERNAL NOTIFICATIONS
    // ==========================================

    public function markNotificationRead(int $id): RedirectResponse
    {
        $notif = InternalNotification::findOrFail($id);
        $notif->is_read = true;
        $notif->read_at = now();
        $notif->save();

        if ($notif->link) {
            return redirect($notif->link);
        }
        return redirect()->back();
    }
}
