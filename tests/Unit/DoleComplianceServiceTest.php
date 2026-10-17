<?php

namespace Tests\Unit;

use App\Models\Hr\Employee;
use App\Services\Hr\DoleComplianceService;
use Carbon\Carbon;
use Tests\TestCase;

class DoleComplianceServiceTest extends TestCase
{
    private DoleComplianceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DoleComplianceService();
    }

    public function test_overtime_multipliers_align_with_dole_labor_code(): void
    {
        // Art. 87: Ordinary day OT = 125%
        $this->assertEquals(1.25, $this->service->getOvertimeMultiplier('ORDINARY', false));

        // Art. 93: Rest day or Special Non-Working day first 8 hrs rate is 130%, OT beyond 8h is 130% * 1.30 = 169%
        $this->assertEquals(1.69, $this->service->getOvertimeMultiplier('REST_DAY', true));
        $this->assertEquals(1.69, $this->service->getOvertimeMultiplier('SPECIAL_NON_WORKING', false));

        // Special day falling on a Rest Day OT: 150% * 1.30 = 195%
        $this->assertEquals(1.95, $this->service->getOvertimeMultiplier('SPECIAL_NON_WORKING', true));

        // Art. 94: Regular Holiday OT: 200% * 1.30 = 260%
        $this->assertEquals(2.60, $this->service->getOvertimeMultiplier('REGULAR_HOLIDAY', false));

        // Regular Holiday falling on Rest Day OT: 260% * 1.30 = 338%
        $this->assertEquals(3.38, $this->service->getOvertimeMultiplier('REGULAR_HOLIDAY', true));
    }

    public function test_night_shift_differential_hours_and_pay(): void
    {
        // Shift from 18:00 (6 PM) to 03:00 (3 AM): Night diff window is 22:00 to 03:00 = 5.0 hours
        $timeIn = '2026-10-17 18:00:00';
        $timeOut = '2026-10-18 03:00:00';

        $ndHours = $this->service->calculateNightDiffHours($timeIn, $timeOut);
        $this->assertEquals(5.0, $ndHours);

        // Hourly rate of 100 PHP: 10% premium is 10 PHP/hr * 5 hrs = 50 PHP
        $ndPay = $this->service->calculateNightDiffPay(100.00, $ndHours);
        $this->assertEquals(50.00, $ndPay);
    }

    public function test_service_incentive_leave_eligibility(): void
    {
        // Art. 95: At least 1 year of service
        $empEligible = new Employee([
            'date_hired' => Carbon::now()->subMonths(14),
            'employment_status' => 'Active',
        ]);
        $result = $this->service->checkStatutoryLeaveEligibility('SIL', $empEligible);
        $this->assertTrue($result['eligible']);
        $this->assertEquals(5, $result['statutory_days']);

        $empNew = new Employee([
            'date_hired' => Carbon::now()->subMonths(6),
            'employment_status' => 'Probationary',
        ]);
        $resultNew = $this->service->checkStatutoryLeaveEligibility('SIL', $empNew);
        $this->assertFalse($resultNew['eligible']);
    }
}
