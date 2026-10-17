<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use Carbon\Carbon;

class DoleComplianceService
{
    /**
     * Get DOLE-mandated Overtime Multiplier based on day type and rest day status.
     * Labor Code Articles 87, 91, 92, 93, 94.
     */
    public function getOvertimeMultiplier(string $dayType, bool $isRestDay = false): float
    {
        $dayType = strtoupper(trim($dayType));

        if ($dayType === 'REGULAR_HOLIDAY' || $dayType === 'REGULAR') {
            // Art. 94: Regular Holiday (200% base). OT = 200% * 1.30 = 260%.
            // If on Rest Day (260% base), OT = 260% * 1.30 = 338%.
            return $isRestDay ? 3.38 : 2.60;
        }

        if ($dayType === 'SPECIAL_NON_WORKING' || $dayType === 'SPECIAL' || $dayType === 'SPECIAL_HOLIDAY') {
            // Art. 93: Special Day (130% base). OT = 130% * 1.30 = 169%.
            // If on Rest Day (150% base), OT = 150% * 1.30 = 195%.
            return $isRestDay ? 1.95 : 1.69;
        }

        if ($dayType === 'REST_DAY' || $isRestDay) {
            // Art. 93: Rest day base rate = 130%. OT beyond 8 hrs = 130% * 1.30 = 169%.
            return 1.69;
        }

        // Art. 87: Work on an ordinary day beyond 8 hours = regular wage + at least 25% (125%).
        return 1.25;
    }

    /**
     * Calculate Overtime Pay in PHP.
     */
    public function calculateOvertimePay(float $hourlyRate, float $otHours, string $dayType = 'ORDINARY', bool $isRestDay = false): float
    {
        $multiplier = $this->getOvertimeMultiplier($dayType, $isRestDay);
        return round($hourlyRate * $multiplier * $otHours, 2);
    }

    /**
     * Calculate Night Shift Differential hours worked between 10:00 PM (22:00) and 6:00 AM (06:00).
     * Labor Code Article 86.
     */
    public function calculateNightDiffHours(string|Carbon $timeIn, string|Carbon $timeOut): float
    {
        $in = is_string($timeIn) ? Carbon::parse($timeIn) : $timeIn->copy();
        $out = is_string($timeOut) ? Carbon::parse($timeOut) : $timeOut->copy();

        if ($out->lte($in)) {
            return 0.0;
        }

        $totalMinutes = 0;
        // Step minute by minute or in 15-minute segments across the interval
        $cursor = $in->copy()->minute((int) floor($in->minute / 5) * 5)->second(0);

        while ($cursor->lt($out)) {
            $hour = $cursor->hour;
            // 22:00 to 23:59 or 00:00 to 05:59
            if ($hour >= 22 || $hour < 6) {
                $totalMinutes += 5;
            }
            $cursor->addMinutes(5);
        }

        return round($totalMinutes / 60.0, 2);
    }

    /**
     * Calculate Night Shift Differential Pay in PHP.
     * 10% premium of regular hourly wage.
     */
    public function calculateNightDiffPay(float $hourlyRate, float $ndHours): float
    {
        return round($hourlyRate * 0.10 * $ndHours, 2);
    }

    /**
     * Check Statutory Leave Eligibility based on DOLE guidelines.
     */
    public function checkStatutoryLeaveEligibility(string $leaveCode, Employee $employee): array
    {
        $code = strtoupper(trim($leaveCode));
        $dateHired = $employee->date_hired ? Carbon::parse($employee->date_hired) : null;
        $serviceMonths = $dateHired ? $dateHired->diffInMonths(now()) : 0;
        $serviceYears = $serviceMonths / 12.0;

        switch ($code) {
            case 'SIL':
                // Art. 95: At least 1 year of continuous service
                $eligible = $serviceYears >= 1.0;
                return [
                    'eligible' => $eligible,
                    'statutory_days' => 5,
                    'law' => 'Labor Code Article 95 (Service Incentive Leave)',
                    'reason' => $eligible ? 'Qualified: 1+ year service' : 'Requires at least 1 year continuous service',
                ];

            case 'MATERNITY':
                // RA 11210: 105 days for female employees regardless of civil status
                $isFemale = strcasecmp($employee->gender ?? '', 'Female') === 0;
                return [
                    'eligible' => $isFemale,
                    'statutory_days' => 105,
                    'law' => 'RA 11210 (105-Day Expanded Maternity Leave Act)',
                    'reason' => $isFemale ? 'Qualified female employee' : 'Applicable to female employees only',
                ];

            case 'PATERNITY':
                // RA 8187: 7 days for married male employees
                $isMale = strcasecmp($employee->gender ?? '', 'Male') === 0;
                $isMarried = strcasecmp($employee->civil_status ?? '', 'Married') === 0;
                return [
                    'eligible' => $isMale && $isMarried,
                    'statutory_days' => 7,
                    'law' => 'RA 8187 (Paternity Leave Act of 1996)',
                    'reason' => ($isMale && $isMarried) ? 'Qualified married male employee' : 'Applicable to legally married male employees',
                ];

            case 'SOLO_PARENT':
                // RA 8972 / RA 11861: 7 days for qualified solo parents with at least 1 year service
                $eligible = $serviceYears >= 1.0;
                return [
                    'eligible' => $eligible,
                    'statutory_days' => 7,
                    'law' => 'RA 11861 (Expanded Solo Parents Welfare Act)',
                    'reason' => $eligible ? 'Qualified with 1+ year service' : 'Requires at least 1 year continuous service and Solo Parent ID',
                ];

            case 'VAWC':
                // RA 9262: 10 days for female victim
                $isFemale = strcasecmp($employee->gender ?? '', 'Female') === 0;
                return [
                    'eligible' => $isFemale,
                    'statutory_days' => 10,
                    'law' => 'RA 9262 (Anti-Violence Against Women and Their Children Act)',
                    'reason' => $isFemale ? 'Qualified female employee' : 'Applicable to female employees',
                ];

            case 'MAGNA_CARTA':
            case 'SPECIAL_LEAVE_WOMEN':
                // RA 9710: Up to 2 months following gynecological surgery, 6 months continuous service
                $isFemale = strcasecmp($employee->gender ?? '', 'Female') === 0;
                $eligible = $isFemale && ($serviceMonths >= 6);
                return [
                    'eligible' => $eligible,
                    'statutory_days' => 60,
                    'law' => 'RA 9710 (Magna Carta of Women)',
                    'reason' => $eligible ? 'Qualified female employee (6+ mos service)' : 'Requires female employee with at least 6 months continuous service',
                ];

            default:
                return [
                    'eligible' => true,
                    'statutory_days' => 0,
                    'law' => 'Company Policy',
                    'reason' => 'Subject to company leave credits and manager approval',
                ];
        }
    }
}
