<?php

namespace App\Services;

use App\Models\Hr\Employee;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\Holiday;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PremiumPayRule;
use Carbon\Carbon;

class PremiumPayRuleEngine
{
    /**
     * Default rule version identifier.
     */
    public const RULE_VERSION = 'v1.2-DOLE-2026/2027';

    /**
     * Cache for active premium rules.
     */
    protected ?array $cachedRules = null;

    /**
     * Central calculate method matching architectural specifications.
     */
    public function calculate(
        ?float $dailyRate = null,
        float $hoursWorked = 8.0,
        float $overtimeHours = 0.0,
        bool $isRestDay = false,
        ?Holiday $holiday = null,
        ?Employee $employee = null,
        ?AttendanceRecord $attendance = null,
        ?EmployeeSchedule $schedule = null,
        ?PayrollPeriod $period = null
    ): array {
        $baseDailyRate = $dailyRate;
        if ($baseDailyRate === null && $employee) {
            $baseDailyRate = $this->calculateDailyRate($employee);
        }
        $baseDailyRate = $baseDailyRate ?? 800.00;
        $hourlyRate = round($baseDailyRate / 8.00, 2);

        // Classification
        $classification = $this->classifyWorkType($holiday, $isRestDay);
        $ruleConfig = $this->getRuleMultipliers($classification['key']);
        $baseMultiplier = (float) ($ruleConfig['base_multiplier'] ?? 100.00);
        $overtimeMultiplier = (float) ($ruleConfig['overtime_multiplier'] ?? 1.30);

        $ratePercentDecimal = $baseMultiplier / 100.00;
        $effectiveHourlyRate = round($hourlyRate * $ratePercentDecimal, 2);

        $regularHours = min($hoursWorked, 8.00);
        $otHours = max(0.0, $overtimeHours);

        // Regular Premium Pay
        $regularPayAmount = round($regularHours * $effectiveHourlyRate, 2);

        // Overtime Premium Pay (DOLE: strictly separated from regular hours)
        $effectiveOtHourlyRate = round($effectiveHourlyRate * $overtimeMultiplier, 2);
        $overtimePayAmount = round($otHours * $effectiveOtHourlyRate, 2);

        $totalPremiumAmount = round($regularPayAmount + $overtimePayAmount, 2);

        $breakdown = [
            'classification' => $classification['label'],
            'rule_key' => $classification['key'],
            'rule_version' => self::RULE_VERSION,
            'is_rest_day' => $isRestDay,
            'holiday_name' => $holiday?->name,
            'holiday_type' => $holiday?->holiday_type,
            'daily_rate' => $baseDailyRate,
            'hourly_rate' => $hourlyRate,
            'applied_rate' => $baseMultiplier . '%',
            'base_multiplier' => $baseMultiplier,
            'regular_hours' => $regularHours,
            'regular_premium_pay' => $regularPayAmount,
            'overtime_hours' => $otHours,
            'overtime_multiplier' => $overtimeMultiplier,
            'overtime_premium_pay' => $overtimePayAmount,
            'total_amount' => $totalPremiumAmount,
            'formula' => "₱{$baseDailyRate} × {$baseMultiplier}%" . ($otHours > 0 ? " + ({$otHours}h OT @ 130%)" : ''),
            'dole_notice' => 'DOLE Mandate: Never combine regular hours and overtime into one calculation.',
        ];

        return [
            'premium_type' => $classification['label'],
            'classification' => $classification['label'],
            'base_rate' => $baseDailyRate,
            'multiplier' => $ratePercentDecimal,
            'applied_multiplier' => $ratePercentDecimal,
            'regular_hours' => $regularHours,
            'overtime_hours' => $otHours,
            'regular_premium_pay' => $regularPayAmount,
            'overtime_premium_pay' => $overtimePayAmount,
            'premium_amount' => $totalPremiumAmount,
            'calculation_breakdown' => $breakdown,
            'breakdown' => $breakdown,
            'rule_version' => self::RULE_VERSION,
        ];
    }

    /**
     * Evaluate an employee's work on a specific day and calculate premium pay.
     *
     * @param Employee $employee
     * @param AttendanceRecord|null $attendance
     * @param EmployeeSchedule|null $schedule
     * @param Holiday|null $holiday
     * @param PayrollPeriod|null $period
     * @param float|null $customDailyRate
     * @param float|null $customHoursWorked
     * @param float|null $customOvertimeHours
     * @param bool|null $isRestDayOverride
     * @return array
     */
    public function evaluateDay(
        Employee $employee,
        ?AttendanceRecord $attendance = null,
        ?EmployeeSchedule $schedule = null,
        ?Holiday $holiday = null,
        ?PayrollPeriod $period = null,
        ?float $customDailyRate = null,
        ?float $customHoursWorked = null,
        ?float $customOvertimeHours = null,
        ?bool $isRestDayOverride = null
    ): array {
        // 1. Determine Date
        $date = $attendance?->date 
            ? Carbon::parse($attendance->date) 
            : ($schedule?->date ? Carbon::parse($schedule->date) : now());

        // 2. Detect Holiday if not passed
        if (!$holiday) {
            $holiday = Holiday::where('is_active', true)
                ->where('date', $date->toDateString())
                ->first();
        }

        // 3. Determine Rest Day condition
        $isRestDay = $isRestDayOverride;
        if ($isRestDay === null) {
            if ($attendance && isset($attendance->is_rest_day)) {
                $isRestDay = (bool) $attendance->is_rest_day;
            } elseif ($schedule && isset($schedule->is_rest_day)) {
                $isRestDay = (bool) $schedule->is_rest_day;
            } else {
                // Default Sunday or 7th day check
                $isRestDay = ($date->dayOfWeek === Carbon::SUNDAY);
            }
        }

        // 4. Determine Hours Worked and Overtime
        $hoursWorked = $customHoursWorked !== null 
            ? (float) $customHoursWorked 
            : (float) ($attendance->total_hours ?? 8.00);

        $otHours = $customOvertimeHours !== null 
            ? (float) $customOvertimeHours 
            : (float) ($attendance->overtime_hours ?? max(0, $hoursWorked - 8.00));

        $regularHours = min($hoursWorked, 8.00);

        // 5. Determine Base Rates
        $dailyRate = $customDailyRate !== null 
            ? (float) $customDailyRate 
            : $this->calculateDailyRate($employee);
        $hourlyRate = round($dailyRate / 8.00, 2);

        // 6. Classify Work Type
        $classification = $this->classifyWorkType($holiday, $isRestDay);

        // 7. Get Multipliers from Configurable Rules
        $ruleConfig = $this->getRuleMultipliers($classification['key']);
        $baseMultiplier = (float) ($ruleConfig['base_multiplier'] ?? 100.00);
        $overtimeMultiplier = (float) ($ruleConfig['overtime_multiplier'] ?? 1.30); // 30% additional on top of daily hourly rate

        // 8. DOLE Premium Pay Computation
        // NOTE: Regular hours (up to 8) and overtime (beyond 8) are strictly calculated separately per DOLE guidelines!
        $ratePercentDecimal = $baseMultiplier / 100.00; // e.g. 1.30 for Rest Day, 1.50 for Special+Rest, 2.00 for Regular, 2.60 for Regular+Rest

        // Effective hourly rate for regular hours on this special/rest day
        $effectiveHourlyRate = round($hourlyRate * $ratePercentDecimal, 2);

        // Regular Premium / Day Pay:
        $regularPayAmount = round($regularHours * $effectiveHourlyRate, 2);

        // For Overtime on this day:
        // DOLE rule: Hourly rate for that day x Overtime Multiplier (1.30)
        // Rate for that day = (Hourly Rate * Base Multiplier / 100)
        $effectiveOtHourlyRate = round($effectiveHourlyRate * $overtimeMultiplier, 2);
        $overtimePayAmount = round($otHours * $effectiveOtHourlyRate, 2);

        $totalPayAmount = round($regularPayAmount + $overtimePayAmount, 2);

        // Premium component only (excess over standard 100% daily wage)
        $standardBasePay = round($regularHours * $hourlyRate, 2);
        $standardOtPay = round($otHours * ($hourlyRate * 1.25), 2);
        $standardTotal = $standardBasePay + $standardOtPay;
        $premiumAmount = max(0, round($totalPayAmount - $standardBasePay, 2));

        // 9. Structured Calculation Breakdown
        $breakdown = [
            'classification' => $classification['label'],
            'rule_key' => $classification['key'],
            'rule_version' => self::RULE_VERSION,
            'is_rest_day' => $isRestDay,
            'holiday_name' => $holiday?->name,
            'holiday_type' => $holiday?->holiday_type,
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'applied_rate' => $baseMultiplier . '%',
            'base_multiplier' => $baseMultiplier,
            'regular_hours' => $regularHours,
            'regular_rate_hourly' => $effectiveHourlyRate,
            'regular_pay' => $regularPayAmount,
            'regular_formula' => "₱{$dailyRate} / 8h = ₱{$hourlyRate}/hr × {$baseMultiplier}% = ₱{$effectiveHourlyRate}/hr × {$regularHours}h = ₱{$regularPayAmount}",
            'overtime_hours' => $otHours,
            'overtime_multiplier' => $overtimeMultiplier,
            'overtime_rate_hourly' => $effectiveOtHourlyRate,
            'overtime_pay' => $overtimePayAmount,
            'overtime_formula' => $otHours > 0 
                ? "₱{$effectiveHourlyRate}/hr × " . round($overtimeMultiplier * 100) . "% = ₱{$effectiveOtHourlyRate}/hr × {$otHours}h = ₱{$overtimePayAmount}" 
                : "No overtime worked",
            'total_day_pay' => $totalPayAmount,
            'premium_component' => $premiumAmount,
        ];

        return [
            'work_type' => $classification['label'],
            'rule_key' => $classification['key'],
            'holiday' => $holiday,
            'holiday_type' => $holiday?->holiday_type,
            'is_rest_day' => $isRestDay,
            'base_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'multiplier' => $baseMultiplier,
            'regular_hours' => $regularHours,
            'overtime_hours' => $otHours,
            'regular_premium_pay' => $regularPayAmount,
            'overtime_premium_pay' => $overtimePayAmount,
            'premium_amount' => $totalPayAmount, // DOLE total compensation for the special day / rest day
            'net_premium_differential' => $premiumAmount,
            'calculation_breakdown' => $breakdown,
            'rule_version' => self::RULE_VERSION,
        ];
    }

    /**
     * Classify the day based on holiday status and rest day.
     */
    public function classifyWorkType(?Holiday $holiday, bool $isRestDay): array
    {
        if ($holiday) {
            $hType = $holiday->holiday_type;

            if ($hType === 'Regular Holiday') {
                if ($isRestDay) {
                    return [
                        'key' => 'REGULAR_HOLIDAY_REST_DAY',
                        'label' => 'Regular Holiday + Rest Day',
                        'base_multiplier' => 260.00, // 200% x 130%
                    ];
                }
                return [
                    'key' => 'REGULAR_HOLIDAY',
                    'label' => 'Regular Holiday',
                    'base_multiplier' => 200.00,
                ];
            }

            if ($hType === 'Special Non-Working') {
                if ($isRestDay) {
                    return [
                        'key' => 'SPECIAL_NON_WORKING_REST_DAY',
                        'label' => 'Special Non-Working + Rest Day',
                        'base_multiplier' => 150.00,
                    ];
                }
                return [
                    'key' => 'SPECIAL_NON_WORKING',
                    'label' => 'Special Non-Working Day',
                    'base_multiplier' => 130.00,
                ];
            }

            if ($hType === 'Special Working') {
                if ($isRestDay) {
                    return [
                        'key' => 'REST_DAY',
                        'label' => 'Rest Day (Special Working)',
                        'base_multiplier' => 130.00,
                    ];
                }
                return [
                    'key' => 'SPECIAL_WORKING',
                    'label' => 'Special Working Day',
                    'base_multiplier' => 100.00,
                ];
            }

            // Local Holiday / Company Holiday
            if ($isRestDay) {
                return [
                    'key' => 'SPECIAL_NON_WORKING_REST_DAY',
                    'label' => "{$hType} + Rest Day",
                    'base_multiplier' => 150.00,
                ];
            }
            return [
                'key' => 'SPECIAL_NON_WORKING',
                'label' => $hType,
                'base_multiplier' => 130.00,
            ];
        }

        // No holiday
        if ($isRestDay) {
            return [
                'key' => 'REST_DAY',
                'label' => 'Rest Day',
                'base_multiplier' => 130.00,
            ];
        }

        return [
            'key' => 'REGULAR_WORKDAY',
            'label' => 'Regular Work Day',
            'base_multiplier' => 100.00,
        ];
    }

    /**
     * Fetch rule multipliers from database or fallback to statutory DOLE defaults.
     */
    public function getRuleMultipliers(string $ruleKey): array
    {
        if ($this->cachedRules === null) {
            $this->cachedRules = [];
            $rules = PremiumPayRule::where('is_active', true)->get();
            foreach ($rules as $r) {
                $this->cachedRules[$r->rule_code] = [
                    'base_multiplier' => (float) $r->base_multiplier,
                    'overtime_multiplier' => (float) ($r->overtime_multiplier ?? 1.30),
                    'holiday_multiplier' => (float) ($r->holiday_multiplier ?? 100.00),
                    'rest_day_multiplier' => (float) ($r->rest_day_multiplier ?? 130.00),
                    'night_diff_multiplier' => (float) ($r->night_diff_multiplier ?? 1.10),
                    'rule_type' => $r->rule_type,
                ];
            }
        }

        if (isset($this->cachedRules[$ruleKey])) {
            return $this->cachedRules[$ruleKey];
        }

        // DOLE Statutory Defaults
        return match ($ruleKey) {
            'REST_DAY' => ['base_multiplier' => 130.00, 'overtime_multiplier' => 1.30],
            'SPECIAL_NON_WORKING' => ['base_multiplier' => 130.00, 'overtime_multiplier' => 1.30],
            'SPECIAL_NON_WORKING_REST_DAY' => ['base_multiplier' => 150.00, 'overtime_multiplier' => 1.30],
            'REGULAR_HOLIDAY' => ['base_multiplier' => 200.00, 'overtime_multiplier' => 1.30],
            'REGULAR_HOLIDAY_REST_DAY' => ['base_multiplier' => 260.00, 'overtime_multiplier' => 1.30],
            default => ['base_multiplier' => 100.00, 'overtime_multiplier' => 1.25],
        };
    }

    /**
     * Compute daily rate from employee compensation data.
     */
    public function calculateDailyRate(Employee $employee): float
    {
        $basicSalary = (float) ($employee->basic_salary ?? 0.00);
        $salaryType = $employee->payroll_type ?? $employee->salary_type ?? 'Monthly';

        if ($salaryType === 'Daily') {
            return $basicSalary;
        }

        // Monthly salary: using standard 26 days/month (313-day divisor / 12 = 26.08)
        return round($basicSalary / 26.0, 2);
    }
}
