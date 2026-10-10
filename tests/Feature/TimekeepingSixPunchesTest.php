<?php

namespace Tests\Feature;

use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimekeepingSixPunchesTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::where('username', 'peter')->first() ?? User::first();
    }

    public function test_timekeeping_page_renders_with_six_punch_columns(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('hr.attendance.timekeeping'));

        $response->assertStatus(200);
        $response->assertSee('In');
        $response->assertSee('Break Out');
        $response->assertSee('Break In');
        $response->assertSee('Coffee Break Out');
        $response->assertSee('Coffee Break In');
        $response->assertSee('Final Out');
    }

    public function test_timekeeping_can_punch_all_six_entries_in_sequence(): void
    {
        $admin = $this->getAdmin();
        $employee = Employee::first();
        $testDate = '2026-10-15';

        // 1. In
        $res = $this->actingAs($admin)->post(route('hr.attendance.timekeeping.store'), [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'punch_type' => 'in',
            'time' => '08:00',
        ]);
        $res->assertSessionHasNoErrors();
        $record = AttendanceRecord::where('employee_id', $employee->id)->where('date', $testDate)->first();
        $this->assertNotNull($record);
        $this->assertEquals('08:00:00', $record->time_in);
        $this->assertEquals('08:00:00', $record->in_1);

        // 2. Break Out
        $res = $this->actingAs($admin)->post(route('hr.attendance.timekeeping.store'), [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'punch_type' => 'break_out',
            'time' => '12:00',
        ]);
        $res->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertEquals('12:00:00', $record->break_out);
        $this->assertEquals('12:00:00', $record->out_1);

        // 3. Break In
        $res = $this->actingAs($admin)->post(route('hr.attendance.timekeeping.store'), [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'punch_type' => 'break_in',
            'time' => '13:00',
        ]);
        $res->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertEquals('13:00:00', $record->break_in);
        $this->assertEquals('13:00:00', $record->in_2);

        // 4. Coffee Break Out
        $res = $this->actingAs($admin)->post(route('hr.attendance.timekeeping.store'), [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'punch_type' => 'coffee_break_out',
            'time' => '15:30',
        ]);
        $res->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertEquals('15:30:00', $record->coffee_break_out);
        $this->assertEquals('15:30:00', $record->out_2);

        // 5. Coffee Break In
        $res = $this->actingAs($admin)->post(route('hr.attendance.timekeeping.store'), [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'punch_type' => 'coffee_break_in',
            'time' => '15:45',
        ]);
        $res->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertEquals('15:45:00', $record->coffee_break_in);
        $this->assertEquals('15:45:00', $record->in_3);

        // 6. Final Out
        $res = $this->actingAs($admin)->post(route('hr.attendance.timekeeping.store'), [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'punch_type' => 'final_out',
            'time' => '17:00',
        ]);
        $res->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertEquals('17:00:00', $record->time_out);
        $this->assertEquals('17:00:00', $record->out_3);
        $this->assertGreaterThan(0, (float)$record->total_hours);
    }
}
