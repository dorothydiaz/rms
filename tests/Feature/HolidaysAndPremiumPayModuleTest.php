<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Holiday;
use App\Models\Hr\HolidayPayRule;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\PremiumPayItem;
use App\Models\Hr\PremiumPayRule;
use App\Models\User;
use App\Services\PremiumPayRuleEngine;
use Tests\TestCase;

class HolidaysAndPremiumPayModuleTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::where('username', 'peter')->first() ?? User::first();
    }

    /**
     * Test Tab navigation renders all 8 tabs in the exact required order.
     */
    public function test_tabs_render_in_exact_order(): void
    {
        $response = $this->actingAs($this->user)->get(route('hr.payroll.holidays'));
        $response->assertStatus(200);

        $content = $response->getContent();
        $startPos = strpos($content, 'hr-tabs-wrapper');
        $tabsWrapper = $startPos !== false ? substr($content, $startPos, 3500) : $content;

        // Check tabs appear in sequence
        $posPeriods = strpos($tabsWrapper, 'Payroll Periods');
        $posProcess = strpos($tabsWrapper, 'Process Payroll');
        $posRegister = strpos($tabsWrapper, 'Payroll Register');
        $posPayslips = strpos($tabsWrapper, 'Payslips');
        $posStatutory = strpos($tabsWrapper, 'Statutory Rules');
        $posWageDistortion = strpos($tabsWrapper, 'Wage Distortion Converter');
        $posHolidays = strpos($tabsWrapper, 'Holidays');
        $posPremiumPay = strpos($tabsWrapper, 'Premium Pay');

        $this->assertNotFalse($posPeriods, 'Payroll Periods tab missing');
        $this->assertNotFalse($posProcess, 'Process Payroll tab missing');
        $this->assertNotFalse($posRegister, 'Payroll Register tab missing');
        $this->assertNotFalse($posPayslips, 'Payslips tab missing');
        $this->assertNotFalse($posStatutory, 'Statutory Rules tab missing');
        $this->assertNotFalse($posWageDistortion, 'Wage Distortion Converter tab missing');
        $this->assertNotFalse($posHolidays, 'Holidays tab missing');
        $this->assertNotFalse($posPremiumPay, 'Premium Pay tab missing');

        $this->assertTrue(
            $posPeriods < $posProcess &&
            $posProcess < $posRegister &&
            $posRegister < $posPayslips &&
            $posPayslips < $posStatutory &&
            $posStatutory < $posWageDistortion &&
            $posWageDistortion < $posHolidays &&
            $posHolidays < $posPremiumPay,
            'Tabs are not in the exact required sequence: Periods -> Process -> Register -> Payslips -> Statutory -> Wage Distortion -> Holidays -> Premium Pay'
        );
    }

    /**
     * Test Holidays view renders with header, summary cards, and year selector.
     */
    public function test_holidays_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('hr.payroll.holidays', ['year' => 2026]));
        $response->assertStatus(200);

        $response->assertSee('Holidays');
        $response->assertSee('Manage Philippine holidays, special working days, and holiday pay rules.');
        $response->assertSee('+ Add Holiday');
        $response->assertSee('Import Calendar');
        $response->assertSee('Export');
        $response->assertSee('Holiday Settings');

        // Year Selector
        $response->assertSee('2026 Calendar');

        // Summary Cards
        $response->assertSee('Total Holidays');
        $response->assertSee('Regular Holidays');
        $response->assertSee('Special Non-Working');
        $response->assertSee('Special Working');
    }

    /**
     * Test Holiday creation and validation.
     */
    public function test_can_create_and_manage_holiday(): void
    {
        $holidayName = 'Test Bonifacio Day ' . uniqid();
        $response = $this->actingAs($this->user)->post(route('hr.payroll.holidays.store'), [
            'name' => $holidayName,
            'year' => 2026,
            'date' => '2026-11-30',
            'holiday_type' => 'Regular Holiday',
            'scope' => 'Nationwide',
            'official_reference' => 'Proclamation No. 999',
            'payroll_treatment' => '100% unworked / 200% worked',
            'description' => 'Test national holiday',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hr_holidays', [
            'name' => $holidayName,
            'year' => 2026,
            'holiday_type' => 'Regular Holiday',
        ]);

        $holiday = Holiday::where('name', $holidayName)->first();

        // Update
        $updatedName = $holidayName . ' Updated';
        $updateResp = $this->actingAs($this->user)->put(route('hr.payroll.holidays.update', $holiday->id), [
            'name' => $updatedName,
            'year' => 2026,
            'date' => '2026-11-30',
            'holiday_type' => 'Regular Holiday',
            'scope' => 'Nationwide',
            'official_reference' => 'Proclamation No. 999-B',
            'payroll_treatment' => '100% unworked / 200% worked (+30% rest day)',
            'is_active' => '1',
        ]);
        $updateResp->assertRedirect();
        $this->assertDatabaseHas('hr_holidays', ['id' => $holiday->id, 'name' => $updatedName]);

        // Delete
        $delResp = $this->actingAs($this->user)->delete(route('hr.payroll.holidays.destroy', $holiday->id));
        $delResp->assertRedirect();
        $this->assertDatabaseMissing('hr_holidays', ['id' => $holiday->id]);
    }

    /**
     * Test Holiday Calendar Import.
     */
    public function test_can_import_holiday_calendar(): void
    {
        $response = $this->actingAs($this->user)->post(route('hr.payroll.holidays.import'), [
            'target_year' => 2028,
            'overwrite' => '1',
        ]);

        $response->assertRedirect();
        $count = Holiday::where('year', 2028)->count();
        $this->assertGreaterThanOrEqual(15, $count, 'Import should populate Philippine holidays for year');
    }

    /**
     * Test Holiday Export CSV.
     */
    public function test_can_export_holidays_csv(): void
    {
        $response = $this->actingAs($this->user)->get(route('hr.payroll.holidays.export', ['year' => 2026]));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test Premium Pay tab renders summary cards, filters, and records table.
     */
    public function test_premium_pay_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('hr.payroll.premium-pay'));
        $response->assertStatus(200);

        $response->assertSee('Premium Pay');
        $response->assertSee('Review and manage premium compensation for rest days, special days, and applicable work conditions.');
        $response->assertSee('Premium Pay Settings');

        // 4 Summary Cards
        $response->assertSee('Total Premium Pay');
        $response->assertSee('Premium Hours');
        $response->assertSee('Employees');
        $response->assertSee('Pending Approval');

        // Filters
        $response->assertSee('Search Employee...');
        $response->assertSee('Payroll Period (All)');
        $response->assertSee('Work / Premium Type (All)');

        // Table Header
        $response->assertSee('Work Type');
        $response->assertSee('Premium Pay');
    }

    /**
     * Test Premium Pay Rule Engine calculation classifications and DOLE formulas.
     */
    public function test_premium_pay_rule_engine_calculations(): void
    {
        $engine = new PremiumPayRuleEngine();

        // 1. Scheduled Rest Day (130%)
        $calcRestDay = $engine->calculate(
            dailyRate: 800.00,
            hoursWorked: 8.0,
            overtimeHours: 0.0,
            isRestDay: true,
            holiday: null
        );
        $this->assertEquals('Rest Day', $calcRestDay['classification']);
        $this->assertEquals(1.30, $calcRestDay['multiplier']);
        $this->assertEquals(1040.00, $calcRestDay['premium_amount']);

        // 2. Special Non-Working Day (130%)
        $specialHoliday = new Holiday([
            'name' => 'All Saints\' Day',
            'holiday_type' => 'Special Non-Working',
        ]);
        $calcSpecial = $engine->calculate(
            dailyRate: 800.00,
            hoursWorked: 8.0,
            overtimeHours: 0.0,
            isRestDay: false,
            holiday: $specialHoliday
        );
        $this->assertEquals('Special Non-Working Day', $calcSpecial['classification']);
        $this->assertEquals(1.30, $calcSpecial['multiplier']);
        $this->assertEquals(1040.00, $calcSpecial['premium_amount']);

        // 3. Special Non-Working + Rest Day (150%)
        $calcSpecialRest = $engine->calculate(
            dailyRate: 800.00,
            hoursWorked: 8.0,
            overtimeHours: 0.0,
            isRestDay: true,
            holiday: $specialHoliday
        );
        $this->assertEquals('Special Non-Working + Rest Day', $calcSpecialRest['classification']);
        $this->assertEquals(1.50, $calcSpecialRest['multiplier']);
        $this->assertEquals(1200.00, $calcSpecialRest['premium_amount']);

        // 4. Regular Holiday (200%)
        $regularHoliday = new Holiday([
            'name' => 'Independence Day',
            'holiday_type' => 'Regular Holiday',
        ]);
        $calcRegular = $engine->calculate(
            dailyRate: 800.00,
            hoursWorked: 8.0,
            overtimeHours: 0.0,
            isRestDay: false,
            holiday: $regularHoliday
        );
        $this->assertEquals('Regular Holiday', $calcRegular['classification']);
        $this->assertEquals(2.00, $calcRegular['multiplier']);
        $this->assertEquals(1600.00, $calcRegular['premium_amount']);

        // 5. Regular Holiday + Rest Day (200% * 130% = 260%)
        $calcRegRest = $engine->calculate(
            dailyRate: 800.00,
            hoursWorked: 8.0,
            overtimeHours: 0.0,
            isRestDay: true,
            holiday: $regularHoliday
        );
        $this->assertEquals('Regular Holiday + Rest Day', $calcRegRest['classification']);
        $this->assertEquals(2.60, $calcRegRest['multiplier']);
        $this->assertEquals(2080.00, $calcRegRest['premium_amount']);

        // 6. Overtime Separation (DOLE Mandate: Never combine regular hours and overtime)
        $calcWithOt = $engine->calculate(
            dailyRate: 800.00, // 100/hr
            hoursWorked: 10.0,
            overtimeHours: 2.0,
            isRestDay: true,
            holiday: $specialHoliday // 150% rate
        );
        $this->assertEquals(8.0, $calcWithOt['regular_hours']);
        $this->assertEquals(2.0, $calcWithOt['overtime_hours']);
        $this->assertEquals(1200.00, $calcWithOt['breakdown']['regular_premium_pay']); // 8h * 100 * 1.5
        $this->assertEquals(390.00, $calcWithOt['breakdown']['overtime_premium_pay']); // 2h * 100 * 1.5 * 1.3 = 390
        $this->assertEquals(1590.00, $calcWithOt['premium_amount']);
        $this->assertStringContainsString('Never combine regular hours and overtime', $calcWithOt['breakdown']['dole_notice']);
    }

    /**
     * Test Premium Pay Approval and Rejection flow.
     */
    public function test_premium_pay_approval_and_rejection(): void
    {
        $employee = Employee::first() ?? Employee::factory()->create();
        $period = PayrollPeriod::first();

        $item = PremiumPayItem::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period ? $period->id : null,
            'work_date' => '2026-10-31',
            'work_type' => 'Rest Day',
            'is_rest_day' => true,
            'hours_worked' => 8.0,
            'overtime_hours' => 0.0,
            'base_daily_rate' => 800.00,
            'base_hourly_rate' => 100.00,
            'applied_multiplier' => 1.30,
            'premium_amount' => 1040.00,
            'calculation_formula' => '₱800.00 × 130%',
            'status' => 'Pending',
        ]);

        // Approve item
        $approveResp = $this->actingAs($this->user)->post(route('hr.payroll.premium-pay.approve', $item->id));
        $approveResp->assertRedirect();
        $this->assertDatabaseHas('hr_premium_pay_items', [
            'id' => $item->id,
            'status' => 'Approved',
        ]);

        // Reject item with reason
        $rejectResp = $this->actingAs($this->user)->post(route('hr.payroll.premium-pay.reject', $item->id), [
            'reason' => 'Schedule shift was unapproved by branch supervisor',
        ]);
        $rejectResp->assertRedirect();
        $this->assertDatabaseHas('hr_premium_pay_items', [
            'id' => $item->id,
            'status' => 'Rejected',
            'rejection_reason' => 'Schedule shift was unapproved by branch supervisor',
        ]);
    }

    /**
     * Test Bulk Approval and Bulk Rejection of Premium Pay.
     */
    public function test_bulk_approve_and_bulk_reject(): void
    {
        $employee = Employee::first() ?? Employee::factory()->create();

        $item1 = PremiumPayItem::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-11-01',
            'work_type' => 'Special Non-Working Day',
            'hours_worked' => 8.0,
            'base_daily_rate' => 800.00,
            'applied_multiplier' => 1.30,
            'premium_amount' => 1040.00,
            'status' => 'Pending',
        ]);

        $item2 = PremiumPayItem::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-11-02',
            'work_type' => 'Special Non-Working Day',
            'hours_worked' => 8.0,
            'base_daily_rate' => 800.00,
            'applied_multiplier' => 1.30,
            'premium_amount' => 1040.00,
            'status' => 'Pending',
        ]);

        // Bulk Approve
        $bulkApproveResp = $this->actingAs($this->user)->post(route('hr.payroll.premium-pay.bulk-approve'), [
            'ids' => [$item1->id, $item2->id],
        ]);
        $bulkApproveResp->assertRedirect();
        $this->assertDatabaseHas('hr_premium_pay_items', ['id' => $item1->id, 'status' => 'Approved']);
        $this->assertDatabaseHas('hr_premium_pay_items', ['id' => $item2->id, 'status' => 'Approved']);

        // Bulk Reject
        $bulkRejectResp = $this->actingAs($this->user)->post(route('hr.payroll.premium-pay.bulk-reject'), [
            'ids' => [$item1->id, $item2->id],
            'reason' => 'Batch review: Store closed early',
        ]);
        $bulkRejectResp->assertRedirect();
        $this->assertDatabaseHas('hr_premium_pay_items', [
            'id' => $item1->id,
            'status' => 'Rejected',
            'rejection_reason' => 'Batch review: Store closed early',
        ]);
    }

    /**
     * Test Payslip detail shows distinct Premium Pay earning and breakdown.
     */
    public function test_payslip_detail_displays_separate_premium_pay(): void
    {
        $record = PayrollRecord::first();
        if (!$record) {
            $this->markTestSkipped('No payroll record found in test db.');
        }

        $response = $this->actingAs($this->user)->get(route('hr.payroll.payslips.show', $record->id));
        $response->assertStatus(200);

        // Check separate rows
        $response->assertSee('Holiday Pay');
        $response->assertSee('Premium Pay');
        $response->assertSee('Calculation Details');
        $response->assertSee('payslipPremiumPayModal');
    }
}
