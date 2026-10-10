<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilterRedesignTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }
        return $user;
    }

    public function test_attendance_summary_report_renders_with_redesigned_filter_controls(): void
    {
        $user = $this->getAdminUser();

        $response = $this->actingAs($user)->get(route('hr.reports.attendance-summary'));

        $response->assertStatus(200);
        $response->assertSee('hr-report-filters-bar');
        $response->assertSee('Start Date');
        $response->assertSee('End Date');
        $response->assertSee('hrFilterSearchInput');
        $response->assertSee('Generate');
        $response->assertSee('hrFilterChipsRow');
    }

    public function test_report_filters_support_multiple_branches_and_tags(): void
    {
        $user = $this->getAdminUser();

        $tags = [
            ['type' => 'branch', 'label' => 'Branch: Valenzuela', 'value' => 'Valenzuela'],
            ['type' => 'branch', 'label' => 'Branch: Malabon', 'value' => 'Malabon'],
            ['type' => 'department', 'label' => 'Department: HR', 'value' => 'HR'],
            ['type' => 'employee_id', 'label' => 'Employee ID: 10234', 'value' => '10234'],
        ];

        $response = $this->actingAs($user)->get(route('hr.reports.attendance-summary', [
            'filter_tags' => json_encode($tags),
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Branch: Valenzuela');
        $response->assertSee('Branch: Malabon');
        $response->assertSee('Department: HR');
        $response->assertSee('Employee ID: 10234');
    }

    public function test_all_report_pages_render_cleanly_without_dropdowns(): void
    {
        $user = $this->getAdminUser();

        $routes = [
            'hr.reports.attendance-summary',
            'hr.reports.overtime-history',
            'hr.reports.authorized-undertime',
            'hr.reports.unauthorized-undertime',
            'hr.reports.authorized-leave-of-absence',
            'hr.reports.unauthorized-absences',
            'hr.reports.change-of-schedule',
            'hr.reports.manual-entries-history',
            'hr.reports.tardiness',
            'hr.reports.individual-attendance-summary',
            'hr.reports.employee-attendance-profile',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $response->assertStatus(200);
            $response->assertSee('hr-report-filters-bar');
            $response->assertSee('hrFilterSearchInput');
            $response->assertSee('Generate');
            $response->assertSee('All Active');
            $response->assertSee('All Employees');
        }
    }

    public function test_reports_hub_index_renders_export_cards_with_filter_format(): void
    {
        $user = $this->getAdminUser();

        $response = $this->actingAs($user)->get(route('hr.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Employee Directory & Masterlist', false);
        $response->assertSee('Attendance & DTR Time Log', false);
        $response->assertSee('Payroll Register & Statutory Summary', false);

        // All 3 cards use the redesigned filter bar with "All Active" and "All Employees"
        $response->assertSee('Download Employee CSV');
        $response->assertSee('Download Attendance CSV');
        $response->assertSee('Download Payroll CSV');
        $response->assertSee('All Active');
        $response->assertSee('All Employees');

        // Verify old traditional branch/department/status select dropdowns inside forms are eliminated from Employee and Attendance cards
        $response->assertDontSee('-- All Branches --');
        $response->assertDontSee('-- All Departments --');
    }

    public function test_all_active_and_all_employees_tag_filtering(): void
    {
        $user = $this->getAdminUser();

        // 1. All Active tag
        $responseActive = $this->actingAs($user)->get(route('hr.reports.attendance-summary', [
            'filter_tags' => json_encode([
                ['type' => 'status', 'label' => 'Status: All Active', 'value' => 'ACTIVE_ALL']
            ])
        ]));
        $responseActive->assertStatus(200);
        $responseActive->assertSee('Status: All Active');

        // 2. All Employees tag
        $responseAll = $this->actingAs($user)->get(route('hr.reports.attendance-summary', [
            'filter_tags' => json_encode([
                ['type' => 'scope', 'label' => 'Scope: All Employees', 'value' => 'ALL']
            ])
        ]));
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Scope: All Employees');
    }
}
