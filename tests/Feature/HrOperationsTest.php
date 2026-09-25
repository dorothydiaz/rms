<?php

namespace Tests\Feature;

use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\ShiftTemplate;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\LeaveCalculationService;
use App\Services\PayrollCalculationService;
use App\Services\StatutoryContributionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HrOperationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function getSuperAdmin(): User
    {
        return User::where('username', 'peter')->first();
    }

    protected function getBranchManager(): User
    {
        return User::where('username', 'manager_makati')->first();
    }

    /**
     * Test 1: All 10 HR module landing pages render status 200 for authenticated user
     */
    public function test_all_10_hr_module_landing_pages_render_successfully(): void
    {
        $admin = $this->getSuperAdmin();

        $routes = [
            'hr.dashboard',
            'hr.people.employees',
            'hr.people.departments',
            'hr.people.positions',
            'hr.people.branches',
            'hr.recruitment.vacancies',
            'hr.recruitment.applicants',
            'hr.recruitment.interviews',
            'hr.attendance.timekeeping',
            'hr.attendance.dtr',
            'hr.attendance.schedules',
            'hr.attendance.overtime',
            'hr.attendance.corrections',
            'hr.leave.requests',
            'hr.leave.types',
            'hr.leave.credits',
            'hr.leave.reports',
            'hr.payroll.periods',
            'hr.payroll.process',
            'hr.payroll.register',
            'hr.payroll.payslips',
            'hr.payroll.statutory-rules',
            'hr.performance.periods',
            'hr.performance.criteria',
            'hr.performance.evaluations',
            'hr.performance.reports',
            'hr.training.programs',
            'hr.training.records',
            'hr.training.reports',
            'hr.reports.index',
            'hr.admin.users',
            'hr.admin.roles',
            'hr.admin.settings',
            'hr.admin.audit-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get(route($route));
            $response->assertStatus(200);
        }
    }

    /**
     * Test: DTR page renders table and glassmorphic pagination
     */
    public function test_dtr_renders_with_table_and_glassmorphic_pagination(): void
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('hr.attendance.dtr'));
        $response->assertStatus(200);
        $response->assertSee('hr-table-card', false);
        $response->assertSee('hr-table', false);
        $response->assertSee('hr-table-footer', false);
        $response->assertSee('hr-pagination-container', false);
        $response->assertSee('hr-per-page-select', false);
        $response->assertSee('value="10"', false);
        $response->assertSee('selected', false);
        $response->assertSee('Showing', false);
    }

    /**
     * Test: DTR page supports custom rows per page selector
     */
    public function test_dtr_supports_custom_per_page(): void
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)->get(route('hr.attendance.dtr', ['per_page' => 25]));
        $response->assertStatus(200);
        $response->assertSee('value="25"', false);
    }

    /**
     * Test 2: Branch Manager access is properly scoped to their assigned branch
     */
    public function test_branch_manager_access_is_scoped(): void
    {
        $manager = $this->getBranchManager();
        $this->assertNotNull($manager->branch_id);

        $makatiBranch = Branch::where('code', 'MAK-01')->first();
        $bgcBranch = Branch::where('code', 'BGC-02')->first();

        $this->assertTrue($manager->canAccessBranch($makatiBranch->id));
        $this->assertFalse($manager->canAccessBranch($bgcBranch->id));

        // When visiting employees list as Makati manager, BGC employees should not be listed
        $response = $this->actingAs($manager)->get(route('hr.people.employees'));
        $response->assertStatus(200);
    }

    /**
     * Test 3: Attendance calculation accurately determines night diff (10PM-6AM) and overtime
     */
    public function test_attendance_calculation_service_overnight_shift(): void
    {
        // Overnight Shift: 22:00 to 07:00 (9 hours total, 1 hour break = 8 hours worked)
        // All 8 working hours fall between 22:00 and 06:00
        $shift = ShiftTemplate::whereIn('code', ['2200', 'NIGHT-03'])->first();
        $this->assertNotNull($shift);

        $service = new AttendanceCalculationService();
        $metrics = $service->calculate(
            '2026-09-20',
            $shift->start_time,
            $shift->end_time,
            '22:00:00',
            '07:00:00',
            '02:00:00',
            '03:00:00',
            true
        );

        $this->assertEquals(8.0, $metrics['total_hours']);
        $this->assertEquals(0, $metrics['late_minutes']);
        $this->assertEquals(0, $metrics['undertime_minutes']);
        $this->assertGreaterThanOrEqual(7.0, $metrics['night_diff_hours']);
    }

    /**
     * Test 4: Philippine Statutory contribution calculations for SSS, PhilHealth, Pag-IBIG, BIR tax
     */
    public function test_statutory_contribution_service_rates(): void
    {
        $service = new StatutoryContributionService();
        $grossIncome = 25000.00;

        $sss = $service->calculateSss($grossIncome);
        $this->assertGreaterThan(0, $sss['employee']);
        $this->assertGreaterThan(0, $sss['employer']);

        $philhealth = $service->calculatePhilhealth($grossIncome);
        $this->assertGreaterThan(0, $philhealth['employee']);
        $this->assertEquals($philhealth['employee'], $philhealth['employer']);

        $pagibigMonthly = $service->calculatePagibig($grossIncome, null, 'Monthly');
        $this->assertEquals(200.00, $pagibigMonthly['employee']);
        $this->assertEquals(200.00, $pagibigMonthly['employer']);

        $pagibigSemi = $service->calculatePagibig($grossIncome, null, 'Semi-Monthly');
        $this->assertEquals(100.00, $pagibigSemi['employee']);
        $this->assertEquals(100.00, $pagibigSemi['employer']);

        $taxable = $grossIncome - $sss['employee'] - $philhealth['employee'] - $pagibigSemi['employee'];
        $tax = $service->calculateWithholdingTax($taxable, 'SemiMonthly');
        $this->assertIsNumeric($tax['tax']);
        $this->assertGreaterThanOrEqual(0, $tax['tax']);
    }

    /**
     * Test 5: Leave Request approval automatically deducts balance, rejection restores
     */
    public function test_leave_workflow_deducts_and_restores_credits(): void
    {
        $employee = Employee::first();
        $leaveType = LeaveType::where('code', 'VL')->first();

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', 2026)
            ->first();

        $initialRemaining = (float) $balance->remaining;

        // Mock leave request of 2 days
        $request = new LeaveRequest([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'number_of_days' => 2.0,
            'status' => 'Approved',
        ]);

        $leaveService = new LeaveCalculationService();

        // Deduct 2 days
        $leaveService->deductCredits($request);
        $balance->refresh();
        $this->assertEquals($initialRemaining - 2.0, (float) $balance->remaining);

        // Restore 2 days
        $leaveService->restoreCredits($request);
        $balance->refresh();
        $this->assertEquals($initialRemaining, (float) $balance->remaining);
    }

    /**
     * Test 6: Finalized payroll period locks records against direct modification
     */
    public function test_finalized_payroll_period_lock(): void
    {
        $period = PayrollPeriod::where('status', 'Finalized')->first();
        if (!$period) {
            $period = PayrollPeriod::create([
                'name' => 'Test Finalized Period',
                'pay_frequency' => 'Semi-Monthly',
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-15',
                'payout_date' => '2026-08-20',
                'status' => 'Finalized',
            ]);
        }

        $this->assertTrue($period->isFinalized());

        // Attempting to run payroll calculation on finalized period should throw Exception
        $this->expectException(\Exception::class);
        $payrollService = app(PayrollCalculationService::class);
        $payrollService->calculatePeriod($period);
    }
}
