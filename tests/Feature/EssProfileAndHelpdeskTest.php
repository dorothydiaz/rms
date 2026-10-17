<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Ess\EssHelpdeskTicket;
use App\Models\Ess\EssHelpdeskMessage;

class EssProfileAndHelpdeskTest extends TestCase
{
    public function test_employee_can_view_profile_and_statutory_ids(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->get('/ess/profile');
        $response->assertStatus(200);
        $response->assertSee($employee->employee_code);
    }

    public function test_employee_can_request_coe(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $response = $this->actingAs($employee->user)->post('/ess/profile/coe-request', [
            'purpose' => 'Housing Loan Application with Pag-IBIG',
            'include_compensation' => '1',
            'addressed_to' => 'Pag-IBIG Fund NCR South Branch',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $empId,
            'request_type' => 'COE_REQUEST',
            'status' => 'PENDING',
        ]);
    }

    public function test_employee_can_create_helpdesk_ticket_and_reply(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        // 1. Submit ticket
        $response = $this->actingAs($employee->user)->post('/ess/helpdesk/ticket', [
            'category' => 'BENEFITS_STATUTORY',
            'subject' => 'Inquiry regarding PhilHealth MDR Update',
            'description' => 'I would like to request an updated Member Data Record (MDR) for my dependent.',
            'priority' => 'MEDIUM',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_helpdesk_tickets', [
            'employee_id' => $empId,
            'subject' => 'Inquiry regarding PhilHealth MDR Update',
            'status' => 'OPEN',
        ]);

        $ticket = EssHelpdeskTicket::where('employee_id', $empId)->latest('id')->first();

        // 2. Reply to ticket
        $replyResponse = $this->actingAs($employee->user)->post("/ess/helpdesk/ticket/{$ticket->id}/reply", [
            'message' => 'Attaching dependent birth certificate photocopy for reference.',
        ]);

        $replyResponse->assertRedirect();
        $this->assertDatabaseHas('ess_helpdesk_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $employee->user_id,
        ]);
    }

    public function test_employee_cannot_reply_to_another_employee_ticket(): void
    {
        $employeeA = Employee::whereNotNull('user_id')->first();
        $empAId = $employeeA->getRawOriginal('employee_id') ?? $employeeA->getKey();

        $employeeB = Employee::where('employee_id', '!=', $empAId)->first();
        $empBId = $employeeB->getRawOriginal('employee_id') ?? $employeeB->getKey();

        $ticketB = EssHelpdeskTicket::create([
            'ticket_number' => 'TCK-FOREIGN-' . uniqid(),
            'employee_id' => $empBId,
            'category' => 'WORKPLACE_SAFETY',
            'subject' => 'Confidential Safety Report',
            'description' => 'Private report details',
            'priority' => 'HIGH',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($employeeA->user)->post("/ess/helpdesk/ticket/{$ticketB->id}/reply", [
            'message' => 'Attempting unauthorized response.',
        ]);

        $response->assertStatus(403);
    }
}
