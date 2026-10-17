<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveType;
use App\Models\Hr\LeaveRequest;

class EssLeaveManagementTest extends TestCase
{
    public function test_leave_index_displays_statutory_and_company_balances(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->get('/ess/leave');
        $response->assertStatus(200);
        $response->assertSee('Leave Management');
        $response->assertSee('Service Incentive Leave');
    }

    public function test_filing_leave_creates_pending_request(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $leaveType = LeaveType::first();
        if (!$leaveType) {
            $leaveType = LeaveType::create([
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'days_per_year' => 15,
                'is_paid' => true,
            ]);
        }

        $response = $this->actingAs($employee->user)->post('/ess/leave/store', [
            'leave_type_id' => $leaveType->getKey(),
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'duration_type' => 'Full Day',
            'reason' => 'Annual medical checkup',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hr_leave_requests', [
            'leave_request_employee_id' => $empIntId,
            'leave_request_leave_type_id' => $leaveType->getKey(),
            'leave_request_status' => 'Pending',
        ]);
    }

    public function test_employee_can_cancel_pending_leave(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $leaveType = LeaveType::first();
        $req = LeaveRequest::create([
            'employee_id' => $empIntId,
            'leave_type_id' => $leaveType->getKey(),
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'number_of_days' => 1.0,
            'reason' => 'Personal trip',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($employee->user)->post("/ess/leave/{$req->getKey()}/cancel");
        $response->assertRedirect();

        $req->refresh();
        $this->assertEquals('Cancelled', $req->status);
    }
}
