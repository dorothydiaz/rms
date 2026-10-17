<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Hr\Employee;

class EssAuthAndSecurityTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/ess/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_employee_can_access_ess_dashboard(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $this->assertNotNull($employee, 'Seeded employee with user required');

        $response = $this->actingAs($employee->user)->get('/ess/dashboard');
        $response->assertStatus(200);
        $response->assertSee($employee->first_name);
        $response->assertSee('Employee Self-Service');
    }

    public function test_user_without_employee_profile_is_blocked_safely(): void
    {
        $user = User::create([
            'username' => 'test_unlinked_' . uniqid(),
            'full_name' => 'Unlinked Test User',
            'email' => 'unlinked_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'Staff',
            'status' => 'Active',
        ]);
        $response = $this->actingAs($user)->get('/ess/dashboard');
        $response->assertStatus(403);
    }
}
