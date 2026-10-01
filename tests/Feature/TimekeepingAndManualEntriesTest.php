<?php

namespace Tests\Feature;

use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimekeepingAndManualEntriesTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('username', 'peter')->first() 
            ?? User::where('role', 'hr_admin')->first() 
            ?? User::first();

        $branch = Branch::first() ?? Branch::create(['name' => 'Main', 'code' => 'MAIN', 'is_active' => true]);
        $dept = Department::first() ?? Department::create(['name' => 'HR', 'code' => 'HR', 'branch_id' => $branch->id, 'is_active' => true]);
        $pos = Position::first() ?? Position::create(['name' => 'Staff', 'code' => 'STF', 'department_id' => $dept->id, 'job_grade' => 'JG-1', 'base_rate' => 610.00, 'is_active' => true]);

        $this->employee = Employee::first() ?? Employee::create([
            'employee_id' => 'EMP-TEST-TK01',
            'first_name' => 'Monitoring',
            'last_name' => 'Tester',
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'employment_status' => 'Active',
            'date_hired' => '2024-01-01',
        ]);
    }

    public function test_timekeeping_is_monitoring_only_without_punch_or_add_entry_features(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('hr.attendance.timekeeping'));

        $response->assertStatus(200);
        $response->assertSee('Staff Time Logs');
        $response->assertSee('Employee ID');
        $response->assertSee('Staff Name');
        $response->assertSee('Branch & Dept', false);
        $response->assertSee('In');
        $response->assertSee('Break Out');
        $response->assertSee('Break In');
        $response->assertSee('CB Out');
        $response->assertSee('CB In');
        $response->assertSee('Final Out');
        $response->assertSee('Total Hours');
        $response->assertSee('Status');

        // Verify NO punch or add time entries feature in Time-Keeping
        $response->assertDontSee('Encode Time Entry');
        $response->assertDontSee('Manual Punch');
        $response->assertDontSee('<button class="hr-btn hr-btn-secondary hr-btn-sm" onclick=\'openPunchModal', false);
    }

    public function test_manual_time_entries_only_shows_records_with_manual_source(): void
    {
        $testDate = '2026-10-20';

        // 1. Create a Biometric record (should NOT appear on manual time entries)
        $biometricRecord = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'branch_id' => $this->employee->branch_id,
            'date' => $testDate,
            'source' => 'Biometric',
            'time_in' => '08:00:00',
            'in_1' => '08:00:00',
            'time_out' => '17:00:00',
            'out_3' => '17:00:00',
            'total_hours' => 8.00,
            'status' => 'Present',
            'notes' => 'Biometric Scanner Station #1',
        ]);

        // 2. Create a Manual record (SHOULD appear on manual time entries)
        $manualRecord = AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'branch_id' => $this->employee->branch_id,
            'date' => '2026-10-21',
            'source' => 'Manual',
            'time_in' => '09:00:00',
            'in_1' => '09:00:00',
            'time_out' => '18:00:00',
            'out_3' => '18:00:00',
            'total_hours' => 8.00,
            'status' => 'Present',
            'notes' => 'Encoded Manual Time Log',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('hr.attendance.corrections'));

        $response->assertStatus(200);
        $response->assertSee('Encode Time Entry');
        $response->assertSee('Encoded Manual Time Log');
        $response->assertDontSee('Biometric Scanner Station #1');

        // Also test with date filter
        $responseDate = $this->actingAs($this->adminUser)->get(route('hr.attendance.corrections', ['date' => $testDate]));
        $responseDate->assertStatus(200);
        $responseDate->assertDontSee('Biometric Scanner Station #1');
    }

    public function test_can_encode_manual_time_entry_and_it_has_manual_source(): void
    {
        $testDate = '2026-10-22';

        $postData = [
            'employee_id' => $this->employee->id,
            'date' => $testDate,
            'time_in' => '08:30',
            'break_out' => '12:00',
            'break_in' => '13:00',
            'coffee_break_out' => '15:00',
            'coffee_break_in' => '15:15',
            'time_out' => '17:30',
            'status' => 'Present',
            'notes' => 'Manual supervisor entry test',
        ];

        $postResponse = $this->actingAs($this->adminUser)->post(route('hr.attendance.corrections.store'), $postData);
        $postResponse->assertRedirect();

        $saved = AttendanceRecord::where('employee_id', $this->employee->id)->where('date', $testDate)->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Manual', $saved->source);
        $this->assertEquals('08:30:00', $saved->time_in);
        $this->assertEquals('17:30:00', $saved->time_out);
        $this->assertEquals('Manual supervisor entry test', $saved->notes);

        // Verify it appears in the manual time entries list
        $viewResponse = $this->actingAs($this->adminUser)->get(route('hr.attendance.corrections'));
        $viewResponse->assertSee('Manual supervisor entry test');
    }
}
