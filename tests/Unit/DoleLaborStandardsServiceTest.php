<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DoleLaborStandardsService;

class DoleLaborStandardsServiceTest extends TestCase
{
    private DoleLaborStandardsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DoleLaborStandardsService();
    }

    public function test_overtime_pay_multipliers(): void
    {
        $hourlyRate = 100.00;
        // Ordinary OT (125%)
        $this->assertEquals(250.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Ordinary', false));
        // Rest Day OT (130% * 130% = 169%)
        $this->assertEquals(338.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Ordinary', true));
        // Regular Holiday OT (200% * 130% = 260%)
        $this->assertEquals(520.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Regular', false));
        // Regular Holiday on Rest Day OT (260% * 130% = 338%)
        $this->assertEquals(676.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Regular', true));
        // Special Holiday OT (130% * 130% = 169%)
        $this->assertEquals(338.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'SpecialNonWorking', false));
    }

    public function test_night_shift_differential_calculation(): void
    {
        $hourlyRate = 100.00;
        // 10:00 PM to 02:00 AM (4 hours in night window: 10pm - 6am)
        $res = $this->service->calculateNightShiftDifferential($hourlyRate, '22:00', '02:00', 'Ordinary');
        $this->assertEquals(4.0, $res['night_diff_hours']);
        $this->assertEquals(40.00, $res['night_diff_pay']); // 4 hrs * (100 * 0.10)
    }

    public function test_statutory_deductions_train_law(): void
    {
        // Monthly gross of 20,000 (below 20,833.33 taxable floor)
        $deductions = $this->service->calculateStatutoryDeductions(20000.00);
        $this->assertEquals(0.00, $deductions['withholding_tax']);
        $this->assertEquals(200.00, $deductions['pagibig']); // 200 cap
        $this->assertGreaterThan(0.00, $deductions['sss']);
        $this->assertEquals(500.00, $deductions['philhealth']); // 20,000 * 5% / 2 = 500
    }

    public function test_statutory_leave_entitlement_rules(): void
    {
        // SIL requires >= 1 year tenure
        $this->assertTrue($this->service->isEligibleForSil(365));
        $this->assertFalse($this->service->isEligibleForSil(180));

        // Solo parent requires >= 6 months tenure
        $this->assertTrue($this->service->isEligibleForSoloParent(185));
        $this->assertFalse($this->service->isEligibleForSoloParent(90));
    }
}
