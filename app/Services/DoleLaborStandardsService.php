<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Hr\Employee;

class DoleLaborStandardsService
{
    /**
     * Calculate Overtime Pay based on Philippine Labor Code standards.
     *
     * Multipliers:
     * - Ordinary Day OT: Hourly Rate * 1.25 * OT Hours
     * - Rest Day OT: Hourly Rate * 1.30 * 1.30 = 1.69 * OT Hours
     * - Special Non-Working Holiday OT: Hourly Rate * 1.30 * 1.30 = 1.69 * OT Hours
     * - Special Holiday on Rest Day OT: Hourly Rate * 1.50 * 1.30 = 1.95 * OT Hours
     * - Regular Holiday OT: Hourly Rate * 2.00 * 1.30 = 2.60 * OT Hours
     * - Regular Holiday on Rest Day OT: Hourly Rate * 2.60 * 1.30 = 3.38 * OT Hours
     * - Double Holiday OT: Hourly Rate * 3.00 * 1.30 = 3.90 * OT Hours
     */
    public function calculateOvertimePay(float $hourlyRate, float $otHours, string $dayType = 'Ordinary', bool $isRestDay = false): float
    {
        $multiplier = 1.25;

        if ($dayType === 'Regular') {
            $multiplier = $isRestDay ? (2.60 * 1.30) : (2.00 * 1.30); // 3.38x or 2.60x
        } elseif ($dayType === 'SpecialNonWorking' || $dayType === 'Special') {
            $multiplier = $isRestDay ? (1.50 * 1.30) : (1.30 * 1.30); // 1.95x or 1.69x
        } elseif ($dayType === 'DoubleHoliday') {
            $multiplier = $isRestDay ? (3.90 * 1.30) : (3.00 * 1.30);
        } else {
            // Ordinary Day
            $multiplier = $isRestDay ? (1.30 * 1.30) : 1.25; // 1.69x or 1.25x
        }

        return round($hourlyRate * $multiplier * $otHours, 2);
    }

    /**
     * Calculate Night Shift Differential (NSD).
     * DOLE standard: 10:00 PM to 6:00 AM qualifies for +10% of base hourly rate.
     */
    public function calculateNightShiftDifferential(float $hourlyRate, string $timeIn, string $timeOut, string $dayType = 'Ordinary', bool $isRestDay = false): array
    {
        $today = Carbon::today();
        $in = Carbon::parse($timeIn);
        $out = Carbon::parse($timeOut);

        // Normalize dates
        $start = $today->copy()->setTime($in->hour, $in->minute, $in->second);
        $end = $today->copy()->setTime($out->hour, $out->minute, $out->second);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $nightStart = $today->copy()->setTime(22, 0, 0);
        $nightEnd = $today->copy()->addDay()->setTime(6, 0, 0);

        // Calculate overlap with 22:00 -> 06:00 window
        $overlapStart = $start->greaterThan($nightStart) ? $start : $nightStart;
        $overlapEnd = $end->lessThan($nightEnd) ? $end : $nightEnd;

        $diffHours = 0.0;
        if ($overlapEnd->greaterThan($overlapStart)) {
            $diffHours = round($overlapStart->diffInMinutes($overlapEnd) / 60.0, 2);
        }

        // Base rate adjustment for holiday/rest day if applicable
        $rateFactor = 1.0;
        if ($dayType === 'Regular') {
            $rateFactor = $isRestDay ? 2.60 : 2.00;
        } elseif ($dayType === 'SpecialNonWorking') {
            $rateFactor = $isRestDay ? 1.50 : 1.30;
        } elseif ($isRestDay) {
            $rateFactor = 1.30;
        }

        $effectiveHourly = $hourlyRate * $rateFactor;
        $nsdPay = round($diffHours * ($effectiveHourly * 0.10), 2);

        return [
            'night_diff_hours' => $diffHours,
            'night_diff_pay' => $nsdPay,
        ];
    }

    /**
     * Compute Holiday Pay for working / non-working unworked holidays.
     */
    public function calculateHolidayPay(float $dailyRate, string $holidayType, bool $worked, bool $isRestDay = false): float
    {
        if ($holidayType === 'Regular') {
            if (!$worked) {
                return round($dailyRate * 1.00, 2); // 100% paid even if unworked
            }
            return round($dailyRate * ($isRestDay ? 2.60 : 2.00), 2);
        }

        if ($holidayType === 'SpecialNonWorking') {
            if (!$worked) {
                return 0.00; // No work, no pay unless company policy
            }
            return round($dailyRate * ($isRestDay ? 1.50 : 1.30), 2);
        }

        return $worked ? round($dailyRate * ($isRestDay ? 1.30 : 1.00), 2) : 0.00;
    }

    /**
     * Compute statutory contributions and TRAIN Law withholding tax.
     */
    public function calculateStatutoryDeductions(float $monthlyGross): array
    {
        // 1. Pag-IBIG: 2% of MSC up to ₱10,000 max MSC => ₱200 max employee share
        $pagibig = min(round($monthlyGross * 0.02, 2), 200.00);

        // 2. PhilHealth: 5.0% total premium rate split 50/50 (2.5% employee share)
        // Floor ₱10,000, Ceiling ₱100,000
        $phBase = max(10000.00, min($monthlyGross, 100000.00));
        $philhealth = round($phBase * 0.025, 2);

        // 3. SSS: 2025/2026 Table (4.5% employee share up to ₱30,000 MSC + WISP)
        $sssBase = max(5000.00, min($monthlyGross, 35000.00));
        $sss = round($sssBase * 0.045, 2);

        // 4. BIR Withholding Tax (TRAIN Law RA 10963 monthly schedule)
        // Taxable income = Gross - Statutory (SSS, PhilHealth, Pag-IBIG)
        $taxable = max(0.00, $monthlyGross - ($sss + $philhealth + $pagibig));
        $tax = 0.00;

        if ($taxable <= 20833.33) {
            $tax = 0.00;
        } elseif ($taxable <= 33333.33) {
            $tax = round(($taxable - 20833.33) * 0.15, 2);
        } elseif ($taxable <= 66666.67) {
            $tax = round(1875.00 + (($taxable - 33333.33) * 0.20), 2);
        } elseif ($taxable <= 166666.67) {
            $tax = round(8541.67 + (($taxable - 66666.67) * 0.25), 2);
        } elseif ($taxable <= 666666.67) {
            $tax = round(33541.67 + (($taxable - 166666.67) * 0.30), 2);
        } else {
            $tax = round(183541.67 + (($taxable - 666666.67) * 0.35), 2);
        }

        return [
            'pagibig' => $pagibig,
            'philhealth' => $philhealth,
            'sss' => $sss,
            'withholding_tax' => $tax,
            'total_deductions' => round($pagibig + $philhealth + $sss + $tax, 2),
        ];
    }

    /**
     * Check Service Incentive Leave (SIL) eligibility: at least 1 year (365 days) of service.
     */
    public function isEligibleForSil(int $tenureDays): bool
    {
        return $tenureDays >= 365;
    }

    /**
     * Check Solo Parent Leave eligibility: at least 6 months (180 days) of service.
     */
    public function isEligibleForSoloParent(int $tenureDays): bool
    {
        return $tenureDays >= 180;
    }
}
