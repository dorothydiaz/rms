<?php

namespace Tests\Feature;

use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduleMatrixPlannerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schedule_planner_table_fits_screen_and_renders_colored_cards_with_details(): void
    {
        $admin = User::where('username', 'peter')->first();
        $this->actingAs($admin);

        $response = $this->get(route('hr.attendance.schedules', ['week_start' => '2026-09-28']));

        $response->assertStatus(200);
        $response->assertSee('table-layout: fixed', false);
        $response->assertSee('sched-col-week', false);
        $response->assertSee('sched-theme-o', false);
        $response->assertSee('sched-card-detail-box', false);
        $response->assertSee('sched-preset-grid', false);
        $response->assertSee('height: calc(100vh - 64px) !important', false);
        $response->assertSee('overflow: hidden !important', false);
        $response->assertSee('border: 1.5px dashed #93c5fd', false);
    }
}
