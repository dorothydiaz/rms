<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveType;
use App\Models\Hr\LeaveRequest;

class EssPortalIntegrationTest extends TestCase
{
    public function test_full_employee_portal_navigation_and_features_accessible(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $this->assertNotNull($employee, 'Must have at least one employee linked to a user.');

        // Test all 8 dedicated DOLE ESS Portal views
        $endpoints = [
            '/ess/dashboard',
            '/ess/dtr',
            '/ess/schedule',
            '/ess/leave',
            '/ess/requests',
            '/ess/payroll',
            '/ess/profile',
            '/ess/helpdesk',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($employee->user)->get($endpoint);
            $response->assertStatus(200);
        }
    }

    public function test_end_to_end_leave_lifecycle_in_ess(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $leaveType = LeaveType::first();

        // 1. Submit leave request
        $response = $this->actingAs($employee->user)->post('/ess/leave/store', [
            'leave_type_id' => $leaveType->leave_type_id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'duration_type' => 'Full Day',
            'reason' => 'Annual DOLE Service Incentive Leave (SIL)',
        ]);

        $response->assertRedirect();

        $leaveRequest = LeaveRequest::where('leave_request_employee_id', $empId)
            ->latest('leave_request_id')
            ->first();

        $this->assertNotNull($leaveRequest);
        $this->assertEquals('Pending', $leaveRequest->status);

        // 2. Employee cancels own pending leave request
        $cancelResponse = $this->actingAs($employee->user)->post("/ess/leave/{$leaveRequest->leave_request_id}/cancel");
        $cancelResponse->assertRedirect();

        $leaveRequest->refresh();
        $this->assertEquals('Cancelled', $leaveRequest->status);
    }
}
