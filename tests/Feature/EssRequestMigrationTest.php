<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\Hr\EssRequest;
use App\Models\Hr\EssRequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EssRequestMigrationTest extends TestCase
{
    public function test_ess_requests_and_logs_tables_exist_and_operate(): void
    {
        $this->assertTrue(Schema::hasTable('ess_requests'), 'ess_requests table should exist');
        $this->assertTrue(Schema::hasTable('ess_request_logs'), 'ess_request_logs table should exist');

        $emp = Employee::first();
        if (!$emp) {
            $emp = Employee::create([
                'employee_id' => 'EMP-TEST-001',
                'first_name' => 'Test',
                'last_name' => 'Worker',
                'employment_status' => 'Active',
            ]);
        }

        $user = User::first();

        $reqNum = 'REQ-TEST-' . uniqid();
        $req = EssRequest::create([
            'request_number' => $reqNum,
            'employee_id' => $emp->employee_id,
            'request_type' => 'OVERTIME',
            'status' => 'PENDING_APPROVAL',
            'start_date' => now()->toDateString(),
            'total_hours' => 2.50,
            'reason' => 'Peak hour kitchen support',
            'current_approver_id' => $user?->id,
        ]);

        $this->assertDatabaseHas('ess_requests', [
            'request_number' => $reqNum,
            'request_type' => 'OVERTIME',
        ]);

        $log = EssRequestLog::create([
            'ess_request_id' => $req->id,
            'action' => 'SUBMITTED',
            'acted_by' => $user?->id,
            'remarks' => 'Filed from ESS portal',
        ]);

        $this->assertCount(1, $req->fresh()->logs);
        $this->assertEquals('SUBMITTED', $req->fresh()->logs->first()->action);
    }
}
