<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\Hr\Permission;
use App\Models\Hr\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmployeePermissionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_employee_permissions_in_roles_tab(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('hr.admin.roles', ['tab' => 'employees']));

        $response->assertStatus(200);
        $response->assertSee('Employee Permissions');
        $response->assertSee('Role Permissions');
        $response->assertSee('Edit Permissions');
    }

    public function test_admin_can_update_direct_employee_permissions(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);
        $employee = Employee::first();
        $permissions = Permission::take(2)->get();
        $permissionIds = $permissions->pluck('id')->toArray();

        $response = $this->actingAs($admin)->post(route('hr.admin.roles.employee-permissions', $employee->id), [
            'permissions' => $permissionIds,
        ]);

        $response->assertRedirect(route('hr.admin.roles', ['tab' => 'employees']));
        $response->assertSessionHas('success');

        $this->assertEquals(2, $employee->fresh()->permissions()->count());
        $this->assertTrue($employee->fresh()->hasDirectPermission($permissionIds[0]));
    }

    public function test_user_evaluates_direct_employee_permission(): void
    {
        $user = User::create([
            'username' => 'teststaff_' . uniqid(),
            'full_name' => 'Test Staff',
            'email' => 'teststaff_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'Staff',
            'status' => 'Active',
        ]);

        $employee = Employee::first();
        $employee->user_id = $user->id;
        $employee->save();

        $permission = Permission::first();

        // Initially without direct permission
        $this->assertFalse($user->hasPermission($permission->slug));

        // Assign permission directly to employee
        $employee->permissions()->sync([$permission->id]);

        // User should now inherit this direct permission
        $this->assertTrue($user->fresh()->hasPermission($permission->slug));
    }

    public function test_admin_can_create_new_role_with_permissions(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);
        $permission = Permission::first();

        $response = $this->actingAs($admin)->post(route('hr.admin.roles.store'), [
            'name' => 'Shift Supervisor ' . uniqid(),
            'slug' => 'shift-sup-' . uniqid(),
            'description' => 'Test shift supervisor role',
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect(route('hr.admin.roles', ['tab' => 'roles']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'description' => 'Test shift supervisor role',
        ]);
    }

    public function test_admin_can_delete_custom_role(): void
    {
        $admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);
        $role = Role::create([
            'name' => 'Custom Role ' . uniqid(),
            'slug' => 'custom-role-' . uniqid(),
            'description' => 'To be deleted',
        ]);

        $response = $this->actingAs($admin)->delete(route('hr.admin.roles.destroy', $role->id));

        $response->assertRedirect(route('hr.admin.roles', ['tab' => 'roles']));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
