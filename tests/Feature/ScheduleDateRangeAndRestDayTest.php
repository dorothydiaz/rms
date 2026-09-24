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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleDateRangeAndRestDayTest extends TestCase
{
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
            'employee_id' => 'EMP-TEST-001',
            'first_name' => 'Test',
            'last_name' => 'Worker',
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

        $this->shift = ShiftTemplate::where('code', '0600')->first() ?? ShiftTemplate::create([
            'name' => '0600 = 6AM - 3PM',
            'code' => '0600',
            'start_time' => '06:00:00',
            'end_time' => '15:00:00',
            'is_overnight' => false,
            'break_minutes' => 60,
        ]);
    }

    public function test_schedules_page_renders_with_strict_shift_format(): void
    {
        $response = $this->actingAs($this->hrAdmin)->get(route('hr.attendance.schedules'));

        $response->assertStatus(200);
        $response->assertSee('0600 = 6AM - 3PM');
        $response->assertDontSee('Morning Shift (FOH/Kitchen)');
        $response->assertDontSee('Afternoon/Closing Shift');
        $response->assertDontSee('Overnight Prep Shift');
    }

    public function test_assign_schedule_across_date_range_with_rest_days(): void
    {
        // 2026-09-21 is Monday, 2026-09-27 is Sunday (full week)
        $startDate = '2026-09-21';
        $endDate = '2026-09-27';

        // Rest days: Saturday and Sunday
        $response = $this->actingAs($this->hrAdmin)->post(route('hr.attendance.schedules.store'), [
            'employee_id' => $this->employee->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'shift_template_id' => $this->shift->id,
            'rest_days' => ['Sat', 'Sun'],
            'apply_mode' => 'standard',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check Monday (2026-09-21): should be working shift
        $mon = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', '2026-09-21')
            ->first();
        $this->assertNotNull($mon);
        $this->assertFalse((bool) $mon->is_rest_day);
        $this->assertEquals($this->shift->id, $mon->shift_template_id);

        // Check Friday (2026-09-25): should be working shift
        $fri = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', '2026-09-25')
            ->first();
        $this->assertNotNull($fri);
        $this->assertFalse((bool) $fri->is_rest_day);
        $this->assertEquals($this->shift->id, $fri->shift_template_id);

        // Check Saturday (2026-09-26): should be Rest Day
        $sat = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', '2026-09-26')
            ->first();
        $this->assertNotNull($sat);
        $this->assertTrue((bool) $sat->is_rest_day);
        $this->assertNull($sat->shift_template_id);

        // Check Sunday (2026-09-27): should be Rest Day
        $sun = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('schedule_date', '2026-09-27')
            ->first();
        $this->assertNotNull($sun);
        $this->assertTrue((bool) $sun->is_rest_day);
        $this->assertNull($sun->shift_template_id);
    }

    public function test_assign_entire_date_range_as_rest_day(): void
    {
        $startDate = '2026-10-01';
        $endDate = '2026-10-03';

        $response = $this->actingAs($this->hrAdmin)->post(route('hr.attendance.schedules.store'), [
            'employee_id' => $this->employee->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_rest_day' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $schedules = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->get();

        $this->assertCount(3, $schedules);
        foreach ($schedules as $sched) {
            $this->assertTrue((bool) $sched->is_rest_day);
            $this->assertNull($sched->shift_template_id);
        }
    }
}
