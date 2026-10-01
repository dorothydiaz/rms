<?php

namespace Tests\Feature;

use App\Models\Hr\AttendanceCorrection;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManualTimeEntriesTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::where('username', 'peter')->first() ?? User::first();
    }

    public function test_manual_time_entries_page_renders_with_tabs_and_controls(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('hr.attendance.corrections'));

        $response->assertStatus(200);
        $response->assertSee('Manual Time Entries');
        $response->assertSee('Encode Time Entry');
        $response->assertSee('Staff Member');
        $response->assertSee('Total Hours');
    }

    public function test_can_freely_encode_six_punches_on_chosen_date(): void
    {
        $admin = $this->getAdmin();
        $employee = Employee::first();
        $chosenDate = '2026-11-20';

        $payload = [
            'employee_id' => $employee->id,
            'date' => $chosenDate,
            'time_in' => '08:00',
            'break_out' => '12:00',
            'break_in' => '13:00',
            'coffee_break_out' => '15:00',
            'coffee_break_in' => '15:15',
            'time_out' => '17:00',
            'status' => 'Present',
            'notes' => 'Encoded by manager for offsite assignment',
        ];

        $res = $this->actingAs($admin)->post(route('hr.attendance.corrections.store'), $payload);
        $res->assertSessionHasNoErrors();
        $res->assertRedirect();

        $record = AttendanceRecord::where('employee_id', $employee->id)->where('date', $chosenDate)->first();
        $this->assertNotNull($record);
        $this->assertEquals('08:00:00', $record->time_in);
        $this->assertEquals('12:00:00', $record->break_out);
        $this->assertEquals('13:00:00', $record->break_in);
        $this->assertEquals('15:00:00', $record->coffee_break_out);
        $this->assertEquals('15:15:00', $record->coffee_break_in);
        $this->assertEquals('17:00:00', $record->time_out);
        $this->assertEquals('Manual', $record->source);
        $this->assertEquals(8.00, (float)$record->regular_hours);
        $this->assertGreaterThanOrEqual(7.75, (float)$record->total_hours);

        // Check audit record
        $correction = AttendanceCorrection::where('attendance_record_id', $record->id)->first();
        $this->assertNotNull($correction);
        $this->assertEquals('Approved', $correction->status);
    }

    public function test_can_encode_manual_time_entry_using_datetime_format_without_date_picker(): void
    {
        $admin = $this->getAdmin();
        $employee = Employee::first();

        // Datetime-local inputs without explicit date field
        $payload = [
            'employee_id' => $employee->id,
            'time_in' => '2026-11-25T08:30',
            'break_out' => '2026-11-25T12:00',
            'break_in' => '2026-11-25T13:00',
            'coffee_break_out' => '2026-11-25T15:00',
            'coffee_break_in' => '2026-11-25T15:15',
            'time_out' => '2026-11-25T17:30',
            'status' => 'Present',
            'notes' => 'Encoded with datetime format',
        ];

        $res = $this->actingAs($admin)->post(route('hr.attendance.corrections.store'), $payload);
        $res->assertSessionHasNoErrors();
        $res->assertRedirect();

        $record = AttendanceRecord::where('employee_id', $employee->id)->where('date', '2026-11-25')->first();
        $this->assertNotNull($record);
        $this->assertEquals('08:30:00', $record->time_in);
        $this->assertEquals('12:00:00', $record->break_out);
        $this->assertEquals('13:00:00', $record->break_in);
        $this->assertEquals('15:00:00', $record->coffee_break_out);
        $this->assertEquals('15:15:00', $record->coffee_break_in);
        $this->assertEquals('17:30:00', $record->time_out);
        $this->assertEquals('Manual', $record->source);
    }

    public function test_lookup_endpoint_returns_punches_for_chosen_date(): void
    {
        $admin = $this->getAdmin();
        $employee = Employee::first();
        $chosenDate = '2026-11-21';

        // Create record
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'date' => $chosenDate,
            'time_in' => '08:30:00',
            'time_out' => '17:30:00',
            'source' => 'Manual',
            'status' => 'Present',
            'total_hours' => 8.0,
        ]);

        $res = $this->actingAs($admin)->get(route('hr.attendance.corrections.lookup', [
            'employee_id' => $employee->id,
            'date' => $chosenDate,
        ]));

        $res->assertStatus(200);
        $res->assertJson([
            'exists' => true,
            'time_in' => '08:30',
            'time_out' => '17:30',
        ]);
    }

    public function test_can_delete_manual_time_entry(): void
    {
        $admin = $this->getAdmin();
        $employee = Employee::first();
        $chosenDate = '2026-11-22';

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'date' => $chosenDate,
            'time_in' => '09:00:00',
            'time_out' => '18:00:00',
            'source' => 'Manual',
            'status' => 'Present',
        ]);

        $res = $this->actingAs($admin)->delete(route('hr.attendance.corrections.destroy', $record->id));
        $res->assertSessionHasNoErrors();
        $res->assertRedirect();

        $this->assertNull(AttendanceRecord::find($record->id));
    }
}
