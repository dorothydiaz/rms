<?php

namespace Tests\Unit;

use App\Models\Hr\Employee;
use App\Services\WageDistortionService;
use Tests\TestCase;

class WageDistortionServiceTest extends TestCase
{
    protected WageDistortionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WageDistortionService();
    }

    public function test_formula_definitions_contains_all_seven_formulas(): void
    {
        $definitions = WageDistortionService::getFormulaDefinitions();

        $this->assertCount(7, $definitions);
        $this->assertArrayHasKey('pineda', $definitions);
        $this->assertArrayHasKey('pineda_cruz_so', $definitions);
        $this->assertArrayHasKey('percentile_carian', $definitions);
        $this->assertArrayHasKey('pcs', $definitions);
        $this->assertArrayHasKey('joda', $definitions);
        $this->assertArrayHasKey('bagtas', $definitions);
        $this->assertArrayHasKey('wirerope', $definitions);
    }

    public function test_pineda_formula_calculation(): void
    {
        // Mock Employee with 26,083.30 monthly salary => daily wage = 1,000.00 (with divisor 26.0833)
        $employee = new Employee();
        $employee->id = 101;
        $employee->employee_id = 'EMP-TEST-01';
        $employee->first_name = 'Juan';
        $employee->last_name = 'Dela Cruz';
        $employee->basic_salary = 26083.30;
        $employee->salary_type = 'Monthly';

        // Pineda formula: (Wa / We) * mandated_increase
        // Wa = 610.00, We = 1000.00, mandated_increase = 35.00
        // Daily Adjustment = (610 / 1000) * 35 = 21.35
        $res = $this->service->calculateEmployee($employee, 'pineda', [
            'previous_min_wage' => 610.00,
            'mandated_increase' => 35.00,
        ], 26.0833);

        $this->assertEquals(21.35, $res['daily_adjustment']);
        $this->assertGreaterThan(0, $res['monthly_adjustment']);
        $this->assertGreaterThan($employee->basic_salary, $res['new_monthly_salary']);
    }

    public function test_pineda_cruz_so_formula_calculation(): void
    {
        $employee = new Employee();
        $employee->id = 102;
        $employee->basic_salary = 26083.30; // We = 1000.00 daily
        $employee->salary_type = 'Monthly';

        // (610 / 1000)^1.2 * 35 = (0.61)^1.2 * 35 = 0.552485 * 35 = 19.34
        $res = $this->service->calculateEmployee($employee, 'pineda_cruz_so', [
            'previous_min_wage' => 610.00,
            'mandated_increase' => 35.00,
            'exponent' => 1.20,
        ], 26.0833);

        $this->assertEquals(19.34, $res['daily_adjustment']);
    }

    public function test_percentile_carian_formula_calculation(): void
    {
        $employee = new Employee();
        $employee->id = 103;
        $employee->basic_salary = 20000.00;
        $employee->salary_type = 'Monthly';

        // 85% * 35 = 29.75
        $res = $this->service->calculateEmployee($employee, 'percentile_carian', [
            'percentile_weight' => 85.00,
            'mandated_increase' => 35.00,
        ], 26.0833);

        $this->assertEquals(29.75, $res['daily_adjustment']);
    }

    public function test_pcs_formula_calculation(): void
    {
        $employee = new Employee();
        $employee->id = 104;
        $employee->basic_salary = 25000.00;
        $employee->salary_type = 'Monthly';

        // (695 / 750) * 35 = 0.9267 * 35 = 32.43
        $res = $this->service->calculateEmployee($employee, 'pcs', [
            'existing_min_wage' => 695.00,
            'formula_base_range' => 750.00,
            'mandated_increase' => 35.00,
        ], 26.0833);

        $this->assertEquals(32.43, $res['daily_adjustment']);
    }

    public function test_joda_formula_calculation(): void
    {
        $employee = new Employee();
        $employee->id = 105;
        $employee->basic_salary = 26083.30; // We = 1000.00
        $employee->salary_type = 'Monthly';

        // (1000.00 - 610.00) / 2 = 390.00 / 2 = 195.00
        $res = $this->service->calculateEmployee($employee, 'joda', [
            'previous_min_wage' => 610.00,
        ], 26.0833);

        $this->assertEquals(195.00, $res['daily_adjustment']);
    }

    public function test_bagtas_formula_calculation(): void
    {
        $employee = new Employee();
        $employee->id = 106;
        $employee->basic_salary = 26083.30; // We = 1000.00
        $employee->salary_type = 'Monthly';

        // (35.00 * 1000.00) / 695.00 = 35000 / 695 = 50.36
        $res = $this->service->calculateEmployee($employee, 'bagtas', [
            'mandated_increase' => 35.00,
            'existing_min_wage' => 695.00,
        ], 26.0833);

        $this->assertEquals(50.36, $res['daily_adjustment']);
    }

    public function test_wirerope_formula_calculation(): void
    {
        $employee = new Employee();
        $employee->id = 107;
        $employee->basic_salary = 26083.30; // We = 1000.00
        $employee->salary_type = 'Monthly';

        // (695 / 1000) * (35 / 10) = 0.695 * 3.5 = 2.43
        $res = $this->service->calculateEmployee($employee, 'wirerope', [
            'existing_min_wage' => 695.00,
            'mandated_increase' => 35.00,
            'creditable_increase' => 10.00,
        ], 26.0833);

        $this->assertEquals(2.43, $res['daily_adjustment']);
    }
}
