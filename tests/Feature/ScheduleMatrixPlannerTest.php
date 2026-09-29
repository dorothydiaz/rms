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

        // Verify anti-FOUC: CSS is pushed to <head> before closing </head>
        $content = $response->getContent();
        $headPos = strpos($content, '</head>');
        $stylePos = strpos($content, '.sched-matrix-table');
        $this->assertNotFalse($headPos, 'Closing head tag exists');
        $this->assertNotFalse($stylePos, 'Schedule CSS exists in response');
        $this->assertTrue($stylePos < $headPos, 'Schedule CSS is loaded in <head> to prevent FOUC');

        // Verify dropdowns default to hidden to prevent flashing open during load
        $response->assertSee('class="sched-row-dropdown" id="rowMenu_', false);
        $this->assertMatchesRegularExpression(
            '/class="sched-row-dropdown"[^>]*style="[^"]*display:\s*none;?[^"]*"/',
            $content,
            'Dropdowns have inline display:none to prevent unstyled flash'
        );
        $response->assertSee('.sched-row-dropdown.open { display: block !important; }', false);

        // Verify custom dropdown popover uses fixed viewport positioning and smart collision detection
        $response->assertSee('position: fixed !important;', false);
        $response->assertSee('spaceBelow < ddHeight', false);
        $response->assertSee('rect.top - ddHeight', false);

        // Verify entire shift card is clickable to open custom options
        $response->assertSee('sched-shift-card sched-theme-o" onclick="openCellCustomDropdown(', false);
        $response->assertSee('event.stopPropagation(); clearCellShift(', false);
    }
}
