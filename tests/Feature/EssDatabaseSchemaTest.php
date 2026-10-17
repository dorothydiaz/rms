<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Ess\EssRequest;
use App\Models\Ess\EssHelpdeskTicket;
use App\Models\Ess\EssHelpdeskMessage;
use App\Models\Ess\EssAnnouncement;
use App\Models\Hr\Employee;

class EssDatabaseSchemaTest extends TestCase
{
    public function test_ess_request_can_be_created_and_queried(): void
    {
        $employee = Employee::first();
        $this->assertNotNull($employee, 'Seeded employee required');

        $req = EssRequest::create([
            'request_number' => 'REQ-2026-0001',
            'employee_id' => $employee->employee_id,
            'request_type' => 'OVERTIME',
            'details' => ['ot_date' => '2026-10-18', 'hours' => 2.5, 'type' => 'PostShift'],
            'reason' => 'Quarter-end audit closing backlog',
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('ess_requests', [
            'request_number' => 'REQ-2026-0001',
            'status' => 'PENDING',
        ]);

        $this->assertEquals($employee->employee_id, $req->employee->employee_id);
    }

    public function test_ess_helpdesk_ticket_and_messages_relation(): void
    {
        $employee = Employee::first();
        $this->assertNotNull($employee);

        $ticket = EssHelpdeskTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'employee_id' => $employee->employee_id,
            'category' => 'Payroll & Wages',
            'subject' => 'Tax computation inquiry',
            'priority' => 'Normal',
            'status' => 'OPEN',
        ]);

        $this->assertDatabaseHas('ess_helpdesk_tickets', [
            'ticket_number' => 'TKT-2026-0001',
            'status' => 'OPEN',
        ]);

        $msg = EssHelpdeskMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $employee->user_id ?? 1,
            'message' => 'Please explain withholding tax deduction for October 1-15 period.',
        ]);

        $this->assertCount(1, $ticket->messages);
    }

    public function test_ess_announcement_can_be_created(): void
    {
        $ann = EssAnnouncement::create([
            'title' => 'Upcoming Holiday: Bonifacio Day',
            'content' => 'Please note that November 30 is a Regular Holiday. Work rendered will be compensated at 200%.',
            'category' => 'Holiday',
            'priority' => 'High',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('ess_announcements', [
            'title' => 'Upcoming Holiday: Bonifacio Day',
            'is_active' => true,
        ]);
    }
}
