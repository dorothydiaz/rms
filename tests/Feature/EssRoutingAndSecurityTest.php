<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\Hr\EssRequest;
use App\Models\User;
use Tests\TestCase;

class EssRoutingAndSecurityTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/ess/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_employee_can_access_ess_routes(): void
    {
        $user = User::whereHas('employee')->first();
        if (!$user) {
            $user = User::first();
            $emp = Employee::first();
            if ($emp && $user) {
                $emp->update(['user_id' => $user->id]);
            }
        }

        $this->actingAs($user);

        $response = $this->get('/ess/dashboard');
        $response->assertStatus(200);

        $response = $this->get('/ess/profile');
        $response->assertStatus(200);

        $response = $this->get('/ess/attendance');
        $response->assertStatus(200);

        $response = $this->get('/ess/schedule');
        $response->assertStatus(200);

        $response = $this->get('/ess/leave');
        $response->assertStatus(200);

        $response = $this->get('/ess/requests');
        $response->assertStatus(200);

        $response = $this->get('/ess/payroll');
        $response->assertStatus(200);
    }

    public function test_idor_protection_prevents_canceling_another_employees_request(): void
    {
        $user1 = User::first();
        $emp1 = Employee::first();

        // Create an ESS request belonging to someone else
        $otherReq = EssRequest::create([
            'request_number' => 'REQ-OTHER-' . uniqid(),
            'employee_id' => 'EMP-OTHER-999',
            'request_type' => 'OVERTIME',
            'status' => 'PENDING_APPROVAL',
            'start_date' => now()->toDateString(),
            'total_hours' => 2.0,
            'reason' => 'Unauthorized test',
        ]);

        $this->actingAs($user1);

        $response = $this->post("/ess/requests/cancel/{$otherReq->id}");
        $response->assertStatus(403);
    }
}
