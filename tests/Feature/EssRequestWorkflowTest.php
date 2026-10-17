<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Ess\EssRequest;

class EssRequestWorkflowTest extends TestCase
{
    public function test_overtime_request_submission_and_tracking(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->post('/ess/requests/overtime', [
            'ot_date' => now()->addDays(1)->toDateString(),
            'start_time' => '17:00',
            'end_time' => '20:00',
            'hours' => 3.0,
            'reason' => 'Store inventory physical audit',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->employee_id,
            'request_type' => 'OVERTIME',
            'status' => 'PENDING',
        ]);
    }

    public function test_employee_can_cancel_own_pending_request(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $req = EssRequest::create([
            'request_number' => 'REQ-TEST-CANCEL-' . uniqid(),
            'employee_id' => $employee->employee_id,
            'request_type' => 'OFFICIAL_BUSINESS',
            'details' => ['destination' => 'Makati City Hall'],
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($employee->user)->post("/ess/requests/{$req->id}/cancel");
        $response->assertRedirect();

        $req->refresh();
        $this->assertEquals('CANCELLED', $req->status);
    }

    public function test_official_business_submission(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->post('/ess/requests/official-business', [
            'ob_date' => now()->addDays(2)->toDateString(),
            'destination' => 'Bureau of Internal Revenue RDO 47',
            'purpose' => 'Submit monthly VAT declarations and documentary stamps',
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->employee_id,
            'request_type' => 'OFFICIAL_BUSINESS',
            'status' => 'PENDING',
        ]);
    }
}
