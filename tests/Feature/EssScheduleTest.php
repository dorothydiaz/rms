<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;

class EssScheduleTest extends TestCase
{
    public function test_schedule_page_renders_calendar_and_shifts(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $this->assertNotNull($employee);

        $response = $this->actingAs($employee->user)->get('/ess/schedule');
        $response->assertStatus(200);
        $response->assertSee('Work Schedule');
    }

    public function test_shift_swap_request_creation(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $coWorker = Employee::where('branch_id', $employee->branch_id)
            ->where('employee_id', '!=', $employee->employee_id)
            ->first();

        $this->assertNotNull($coWorker, 'Need co-worker in same branch');

        $response = $this->actingAs($employee->user)->post('/ess/schedule/swap', [
            'swap_with_employee_id' => $coWorker->employee_id,
            'my_shift_date' => now()->addDays(2)->toDateString(),
            'target_shift_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Family emergency on Friday shift',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->employee_id,
            'request_type' => 'SHIFT_SWAP',
            'status' => 'PENDING',
        ]);
    }
}
