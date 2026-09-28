<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\Position;
use App\Models\Hr\ShiftTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FastSchedulePlotterTest extends TestCase
{
    use DatabaseTransactions;

    private User $hrAdmin;
    private Employee $employee;
    private ShiftTemplate $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrAdmin = User::where('username', 'peter')->first() ?? User::where('role', 'hr_admin')->first() ?? User::first();

        $branch = Branch::first();
        $dept = Department::first();
        $pos = Position::first();

        $this->employee = Employee::first() ?? Employee::create([
            'employee_id' => 'EMP-TEST-FAST-01',
            'first_name' => 'Fast',
            'last_name' => 'Plotter',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branch?->id,
            'department_id' => $dept?->id,
            'position_id' => $pos?->id,
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'employment_source' => 'Company',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'basic_salary' => 20000,
        ]);

        $this->shift = ShiftTemplate::first() ?? ShiftTemplate::create([
            'name' => '0800 = 8AM - 5PM',
            'code' => '0800',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_overnight' => false,
            'break_minutes' => 60,
            'color' => '#10b981',
        ]);
    }

    public function test_quick_assign_can_set_shift_directly(): void
    {
        $date = '2026-09-28';

        $response = $this->actingAs($this->hrAdmin)
            ->postJson(route('hr.attendance.schedules.quick-assign'), [
                'employee_id' => $this->employee->id,
                'date' => $date,
                'shift_template_id' => $this->shift->id,
                'is_rest_day' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $sched = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', $date)
            ->first();

        $this->assertNotNull($sched);
        $this->assertEquals($this->shift->id, $sched->shift_template_id);
        $this->assertFalse((bool) $sched->is_rest_day);
    }

    public function test_quick_assign_can_mark_rest_day(): void
    {
        $date = '2026-10-04';

        $response = $this->actingAs($this->hrAdmin)
            ->postJson(route('hr.attendance.schedules.quick-assign'), [
                'employee_id' => $this->employee->id,
                'date' => $date,
                'is_rest_day' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $sched = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', $date)
            ->first();

        $this->assertNotNull($sched);
        $this->assertTrue((bool) $sched->is_rest_day);
        $this->assertNull($sched->shift_template_id);
    }

    public function test_quick_assign_can_clear_shift(): void
    {
        $date = '2026-09-29';

        EmployeeSchedule::updateOrCreate(
            ['employee_id' => $this->employee->id, 'schedule_date' => $date],
            ['shift_template_id' => $this->shift->id, 'is_rest_day' => false]
        );

        $response = $this->actingAs($this->hrAdmin)
            ->postJson(route('hr.attendance.schedules.quick-assign'), [
                'employee_id' => $this->employee->id,
                'date' => $date,
                'clear' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('employee_schedules', [
            'employee_id' => $this->employee->id,
            'schedule_date' => $date,
        ]);
    }

    public function test_copy_previous_week_schedules(): void
    {
        $prevWeekMon = '2026-09-21';
        $currWeekMon = '2026-09-28';

        // Seed previous week
        EmployeeSchedule::updateOrCreate(
            ['employee_id' => $this->employee->id, 'schedule_date' => $prevWeekMon],
            ['shift_template_id' => $this->shift->id, 'is_rest_day' => false]
        );

        $response = $this->actingAs($this->hrAdmin)
            ->postJson(route('hr.attendance.schedules.copy-week'), [
                'current_week_start' => $currWeekMon,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $copied = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', $currWeekMon)
            ->first();

        $this->assertNotNull($copied);
        $this->assertEquals($this->shift->id, $copied->shift_template_id);
    }

    public function test_quick_fill_row_for_employee(): void
    {
        $weekStart = '2026-09-28';

        $response = $this->actingAs($this->hrAdmin)
            ->postJson(route('hr.attendance.schedules.quick-fill-row'), [
                'employee_id' => $this->employee->id,
                'week_start' => $weekStart,
                'shift_template_id' => $this->shift->id,
                'rest_days' => ['Sun'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Monday should be shift
        $mon = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', '2026-09-28')
            ->first();
        $this->assertNotNull($mon);
        $this->assertFalse((bool) $mon->is_rest_day);

        // Sunday should be rest day
        $sun = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', '2026-10-04')
            ->first();
        $this->assertNotNull($sun);
        $this->assertTrue((bool) $sun->is_rest_day);
    }

    public function test_quick_assign_with_custom_time_range_and_shift_code(): void
    {
        $date = '2026-09-30';

        $response = $this->actingAs($this->hrAdmin)
            ->postJson(route('hr.attendance.schedules.quick-assign'), [
                'employee_id' => $this->employee->id,
                'date' => $date,
                'shift_code' => 'O',
                'custom_start_time' => '07:30',
                'custom_end_time' => '16:30',
                'notes' => 'OPENING',
                'is_rest_day' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'shift_code' => 'O',
                'custom_start_time' => '07:30',
                'custom_end_time' => '16:30',
                'notes' => 'OPENING',
            ]
        ]);

        $sched = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', $date)
            ->first();

        $this->assertNotNull($sched);
        $this->assertEquals('07:30:00', $sched->custom_start_time);
        $this->assertEquals('16:30:00', $sched->custom_end_time);
        $this->assertEquals('OPENING', $sched->notes);
    }
}
