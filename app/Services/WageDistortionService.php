<?php

namespace App\Services;

use App\Models\Hr\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WageDistortionService
{
    /**
     * Standard working days divisor per month (313 days / 12 months = 26.0833 for 6-day workweek)
     */
    public const DEFAULT_MONTHLY_DIVISOR = 26.0833;

    /**
     * Metadata and definitions for all 7 DOLE / NWPC Philippine Wage Distortion Formulas
     */
    public static function getFormulaDefinitions(): array
    {
        return [
            'pineda' => [
                'id' => 'pineda',
                'name' => 'Pineda Formula',
                'badge' => 'DOLE Standard',
                'category' => 'Proportional Tapering',
                'description' => 'Adjusts wages above the minimum in decreasing proportions based on the distance between the employee\'s current wage and the previous minimum wage.',
                'latex' => '(Previous Minimum Wage ÷ Employee Current Wage) × Mandated Wage Increase',
                'visual_formula' => '(Wa ÷ We) × ΔW = Wage Distortion Adjustment',
                'fields' => [
                    'previous_min_wage' => [
                        'label' => 'Previous Minimum Wage',
                        'type' => 'currency',
                        'default' => 610.00,
                        'placeholder' => '610.00',
                        'description' => 'Regional statutory minimum wage prior to the new wage order.',
                    ],
                    'mandated_increase' => [
                        'label' => 'Mandated Wage Increase',
                        'type' => 'currency',
                        'default' => 35.00,
                        'placeholder' => '35.00',
                        'description' => 'Cost-of-living allowance (COLA) or basic daily increase mandated by the wage order.',
                    ],
                ],
                'notes' => 'DOLE / NWPC Benchmark Formula. Tapers adjustment proportionally as employee wage increases above the previous floor.',
            ],
            'pineda_cruz_so' => [
                'id' => 'pineda_cruz_so',
                'name' => 'Pineda-Cruz-So Formula',
                'badge' => 'Exponent Tapered',
                'category' => 'Controlled Decay',
                'description' => 'Variation of the Pineda Formula using an exponent (n) to accelerate or decelerate the tapering rate for higher salary brackets.',
                'latex' => '(Previous Minimum Wageⁿ ÷ Employee Current Wageⁿ) × Mandated Wage Increase',
                'visual_formula' => '(Wa ÷ We)ⁿ × ΔW = Wage Distortion Adjustment',
                'fields' => [
                    'previous_min_wage' => [
                        'label' => 'Previous Minimum Wage',
                        'type' => 'currency',
                        'default' => 610.00,
                        'placeholder' => '610.00',
                        'description' => 'Regional daily minimum wage prior to the new wage order.',
                    ],
                    'mandated_increase' => [
                        'label' => 'Mandated Wage Increase',
                        'type' => 'currency',
                        'default' => 35.00,
                        'placeholder' => '35.00',
                        'description' => 'Mandated daily wage increase from the Regional Tripartite Wages and Productivity Board.',
                    ],
                    'exponent' => [
                        'label' => 'Exponent (n)',
                        'type' => 'number',
                        'step' => '0.05',
                        'default' => 1.20,
                        'placeholder' => '1.20',
                        'description' => 'Damping parameter: n > 1 tapers higher brackets faster; n < 1 flattens the curve; n = 1 equals standard Pineda.',
                    ],
                ],
                'notes' => 'Allows HR to calibrate the compression decay slope using an adjustable mathematical damping factor.',
            ],
            'percentile_carian' => [
                'id' => 'percentile_carian',
                'name' => 'Percentile Approach / Carian Formula',
                'badge' => 'Hierarchy Weighted',
                'category' => 'Structural Weighting',
                'description' => 'Calculates adjustments by applying a percentile weight or pay-scale hierarchy factor against the mandated wage increase.',
                'latex' => 'Percentile Weight of Pay Group × Mandated Wage Increase',
                'visual_formula' => 'Weight (%) × ΔW = Wage Distortion Adjustment',
                'fields' => [
                    'percentile_weight' => [
                        'label' => 'Percentile Weight',
                        'type' => 'percentage',
                        'step' => '0.01',
                        'default' => 85.00,
                        'placeholder' => '85 or 0.85',
                        'description' => 'Pay group hierarchy weight (enter as percentage e.g. 85% or decimal e.g. 0.85).',
                    ],
                    'mandated_increase' => [
                        'label' => 'Mandated Wage Increase',
                        'type' => 'currency',
                        'default' => 35.00,
                        'placeholder' => '35.00',
                        'description' => 'Mandated daily wage increase.',
                    ],
                ],
                'notes' => 'Ideal for organizations structured with defined salary percentiles, salary grades, or step increments.',
            ],
            'pcs' => [
                'id' => 'pcs',
                'name' => 'Philippine Construction Supply (PCS) Formula',
                'badge' => 'FBR Benchmark',
                'category' => 'Benchmark Range',
                'description' => 'Utilizes an established Formula Base Range (FBR) combining the actual wage rate and agreed adjustments to correct distortion.',
                'latex' => '(Existing Minimum Wage ÷ Formula Base Range) × Mandated Wage Increase',
                'visual_formula' => '(MinWage ÷ FBR) × ΔW = Wage Distortion Adjustment',
                'fields' => [
                    'existing_min_wage' => [
                        'label' => 'Existing Minimum Wage',
                        'type' => 'currency',
                        'default' => 695.00,
                        'placeholder' => '695.00',
                        'description' => 'New statutory daily minimum wage under the wage order.',
                    ],
                    'formula_base_range' => [
                        'label' => 'Formula Base Range (FBR)',
                        'type' => 'currency',
                        'default' => 750.00,
                        'placeholder' => '750.00',
                        'description' => 'Agreed labor-management base range representing the benchmark salary threshold.',
                    ],
                    'mandated_increase' => [
                        'label' => 'Mandated Wage Increase',
                        'type' => 'currency',
                        'default' => 35.00,
                        'placeholder' => '35.00',
                        'description' => 'Mandated wage increase under the order.',
                    ],
                ],
                'notes' => 'Popularized in collective bargaining agreements and construction/manufacturing sector wage dispute settlements.',
            ],
            'joda' => [
                'id' => 'joda',
                'name' => 'Jimenez-Ofreneo-De Las Alas (JODA) Formula',
                'badge' => 'Spread Split',
                'category' => 'Bracket Arbitrage',
                'description' => 'Computes adjustments relative to wage brackets based on half the difference between current wage and old minimum wage.',
                'latex' => '(Employee Current Wage [Wb] – Previous Minimum Wage [Wa]) ÷ 2',
                'visual_formula' => '(We – Wa) ÷ 2 = Wage Distortion Adjustment',
                'fields' => [
                    'previous_min_wage' => [
                        'label' => 'Previous Minimum Wage (Wa)',
                        'type' => 'currency',
                        'default' => 610.00,
                        'placeholder' => '610.00',
                        'description' => 'Old minimum wage floor (Wa) before the wage order.',
                    ],
                ],
                'notes' => 'Formulated by noted labor law arbitrators. Directly splits the differential bracket above the old statutory minimum wage.',
            ],
            'bagtas' => [
                'id' => 'bagtas',
                'name' => 'Bagtas Formula',
                'badge' => 'Direct Proportional',
                'category' => 'Ratio Scaling',
                'description' => 'Relates the employee\'s present wage with the existing minimum wage and mandated increase.',
                'latex' => '(Mandated Wage Increase × Present Wage of Employee) ÷ Existing Minimum Wage',
                'visual_formula' => '(ΔW × We) ÷ ExistingMinWage = Wage Distortion Adjustment',
                'fields' => [
                    'mandated_increase' => [
                        'label' => 'Mandated Wage Increase',
                        'type' => 'currency',
                        'default' => 35.00,
                        'placeholder' => '35.00',
                        'description' => 'Mandated daily wage increase.',
                    ],
                    'existing_min_wage' => [
                        'label' => 'Existing Minimum Wage',
                        'type' => 'currency',
                        'default' => 695.00,
                        'placeholder' => '695.00',
                        'description' => 'New statutory daily minimum wage under the wage order.',
                    ],
                ],
                'notes' => 'Widely used union formula that scales adjustments directly in proportion to present employee tenure and salary.',
            ],
            'wirerope' => [
                'id' => 'wirerope',
                'name' => 'WireRope Formula',
                'badge' => 'Creditable Factor',
                'category' => 'Corporate Credit Offset',
                'description' => 'Factors in the existing minimum wage, mandated increase, and company-implemented creditable increases.',
                'latex' => '(Existing Minimum Wage ÷ Employee Wage) × (Mandated Increase ÷ Creditable Increase)',
                'visual_formula' => '(ExistingMinWage ÷ We) × (ΔW ÷ CreditableIncrease) = Adjustment',
                'fields' => [
                    'existing_min_wage' => [
                        'label' => 'Existing Minimum Wage',
                        'type' => 'currency',
                        'default' => 695.00,
                        'placeholder' => '695.00',
                        'description' => 'New statutory daily minimum wage under the wage order.',
                    ],
                    'mandated_increase' => [
                        'label' => 'Mandated Wage Increase',
                        'type' => 'currency',
                        'default' => 35.00,
                        'placeholder' => '35.00',
                        'description' => 'Mandated daily increase from the regional wage board.',
                    ],
                    'creditable_increase' => [
                        'label' => 'Creditable Increase',
                        'type' => 'currency',
                        'default' => 10.00,
                        'placeholder' => '10.00',
                        'description' => 'Previously implemented company-level wage or merit increase creditable towards statutory compliance.',
                    ],
                ],
                'notes' => 'Derived from the Wire Rope Corp Supreme Court landmark ruling, specifically balancing prior unilateral company raises.',
            ],
        ];
    }

    /**
     * Compute single employee wage distortion adjustment
     */
    public function calculateEmployee(
        Employee $employee,
        string $formulaKey,
        array $params,
        float $monthlyDivisor = self::DEFAULT_MONTHLY_DIVISOR
    ): array {
        $definitions = self::getFormulaDefinitions();
        if (!isset($definitions[$formulaKey])) {
            throw new \InvalidArgumentException("Unsupported formula key: {$formulaKey}");
        }

        $formula = $definitions[$formulaKey];
        $currentMonthlySalary = (float) ($employee->basic_salary ?? 0.0);
        $divisor = ($monthlyDivisor > 0) ? $monthlyDivisor : self::DEFAULT_MONTHLY_DIVISOR;

        // Daily equivalent of employee's basic salary
        $dailyWage = ($employee->salary_type === 'Daily')
            ? $currentMonthlySalary
            : ($divisor > 0 ? $currentMonthlySalary / $divisor : 0.0);

        // Sanitize & resolve inputs
        $prevMinWage = (float) ($params['previous_min_wage'] ?? 610.00);
        $mandatedInc = (float) ($params['mandated_increase'] ?? 35.00);
        $existingMinWage = (float) ($params['existing_min_wage'] ?? 695.00);
        $creditableInc = (float) ($params['creditable_increase'] ?? 10.00);
        $fbr = (float) ($params['formula_base_range'] ?? 750.00);
        $exponent = (float) ($params['exponent'] ?? 1.20);
        $rawPercentile = (float) ($params['percentile_weight'] ?? 85.00);

        // Allow either percentage (85) or decimal (0.85)
        $percentileWeight = ($rawPercentile > 1.0) ? ($rawPercentile / 100.0) : $rawPercentile;

        $dailyAdjustment = 0.0;
        $equationBreakdown = '';

        switch ($formulaKey) {
            case 'pineda':
                // (Previous Minimum Wage ÷ Employee Current Wage) × Mandated Wage Increase
                if ($dailyWage > 0) {
                    $ratio = $prevMinWage / $dailyWage;
                    $dailyAdjustment = $ratio * $mandatedInc;
                    $equationBreakdown = sprintf(
                        '(₱%s ÷ ₱%s) × ₱%s = ₱%s/day',
                        number_format($prevMinWage, 2),
                        number_format($dailyWage, 2),
                        number_format($mandatedInc, 2),
                        number_format($dailyAdjustment, 2)
                    );
                }
                break;

            case 'pineda_cruz_so':
                // (Previous Minimum Wage ÷ Employee Current Wage)^n × Mandated Wage Increase
                if ($dailyWage > 0 && $exponent > 0) {
                    $ratio = $prevMinWage / $dailyWage;
                    $decayRatio = pow($ratio, $exponent);
                    $dailyAdjustment = $decayRatio * $mandatedInc;
                    $equationBreakdown = sprintf(
                        '(₱%s ÷ ₱%s)^%s × ₱%s = ₱%s/day',
                        number_format($prevMinWage, 2),
                        number_format($dailyWage, 2),
                        number_format($exponent, 2),
                        number_format($mandatedInc, 2),
                        number_format($dailyAdjustment, 2)
                    );
                }
                break;

            case 'percentile_carian':
                // Percentile Weight × Mandated Wage Increase
                $dailyAdjustment = $percentileWeight * $mandatedInc;
                $equationBreakdown = sprintf(
                    '%s%% × ₱%s = ₱%s/day',
                    number_format($percentileWeight * 100, 1),
                    number_format($mandatedInc, 2),
                    number_format($dailyAdjustment, 2)
                );
                break;

            case 'pcs':
                // (Existing Minimum Wage ÷ Formula Base Range) × Mandated Wage Increase
                if ($fbr > 0) {
                    $dailyAdjustment = ($existingMinWage / $fbr) * $mandatedInc;
                    $equationBreakdown = sprintf(
                        '(₱%s ÷ ₱%s) × ₱%s = ₱%s/day',
                        number_format($existingMinWage, 2),
                        number_format($fbr, 2),
                        number_format($mandatedInc, 2),
                        number_format($dailyAdjustment, 2)
                    );
                }
                break;

            case 'joda':
                // (Employee Current Wage [Wb] – Previous Minimum Wage [Wa]) ÷ 2
                $diff = max(0.0, $dailyWage - $prevMinWage);
                $dailyAdjustment = $diff / 2.0;
                $equationBreakdown = sprintf(
                    '(₱%s – ₱%s) ÷ 2 = ₱%s/day',
                    number_format($dailyWage, 2),
                    number_format($prevMinWage, 2),
                    number_format($dailyAdjustment, 2)
                );
                break;

            case 'bagtas':
                // (Mandated Wage Increase × Present Wage of Employee) ÷ Existing Minimum Wage
                if ($existingMinWage > 0) {
                    $dailyAdjustment = ($mandatedInc * $dailyWage) / $existingMinWage;
                    $equationBreakdown = sprintf(
                        '(₱%s × ₱%s) ÷ ₱%s = ₱%s/day',
                        number_format($mandatedInc, 2),
                        number_format($dailyWage, 2),
                        number_format($existingMinWage, 2),
                        number_format($dailyAdjustment, 2)
                    );
                }
                break;

            case 'wirerope':
                // (Existing Minimum Wage ÷ Employee Wage) × (Mandated Increase ÷ Creditable Increase)
                if ($dailyWage > 0 && $creditableInc > 0) {
                    $dailyAdjustment = ($existingMinWage / $dailyWage) * ($mandatedInc / $creditableInc);
                    $equationBreakdown = sprintf(
                        '(₱%s ÷ ₱%s) × (₱%s ÷ ₱%s) = ₱%s/day',
                        number_format($existingMinWage, 2),
                        number_format($dailyWage, 2),
                        number_format($mandatedInc, 2),
                        number_format($creditableInc, 2),
                        number_format($dailyAdjustment, 2)
                    );
                }
                break;
        }

        // Avoid negative adjustment
        $dailyAdjustment = max(0.0, round($dailyAdjustment, 2));

        // Monthly adjustment equivalent
        $monthlyAdjustment = ($employee->salary_type === 'Daily')
            ? $dailyAdjustment
            : round($dailyAdjustment * $divisor, 2);

        $newMonthlySalary = round($currentMonthlySalary + $monthlyAdjustment, 2);
        $newDailyWage = round($dailyWage + $dailyAdjustment, 2);

        $percentIncrease = ($currentMonthlySalary > 0)
            ? round(($monthlyAdjustment / $currentMonthlySalary) * 100, 2)
            : 0.0;

        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_id,
            'employee_name' => $employee->full_name,
            'department_name' => $employee->department?->name ?? 'Unassigned',
            'position_name' => $employee->position?->name ?? 'Staff',
            'branch_name' => $employee->branch?->name ?? 'Head Office',
            'employment_status' => $employee->employment_status ?? 'Active',
            'employment_type' => $employee->employment_type ?? 'Regular',
            'salary_type' => $employee->salary_type ?? 'Monthly',
            'job_level' => $employee->job_level ?? 'Standard',
            'current_monthly_salary' => $currentMonthlySalary,
            'current_daily_wage' => round($dailyWage, 2),
            'formula_key' => $formulaKey,
            'formula_name' => $formula['name'],
            'daily_adjustment' => $dailyAdjustment,
            'monthly_adjustment' => $monthlyAdjustment,
            'new_monthly_salary' => $newMonthlySalary,
            'new_daily_wage' => $newDailyWage,
            'percent_increase' => $percentIncrease,
            'equation_breakdown' => $equationBreakdown,
            'status' => 'Distortion Resolved',
        ];
    }

    /**
     * Batch calculate multiple employees
     */
    public function calculateBatch(
        $employees,
        string $formulaKey,
        array $params,
        float $monthlyDivisor = self::DEFAULT_MONTHLY_DIVISOR
    ): array {
        $results = [];
        $totalPreviousMonthly = 0.0;
        $totalMonthlyAdjustment = 0.0;
        $totalNewMonthly = 0.0;

        foreach ($employees as $employee) {
            $item = $this->calculateEmployee($employee, $formulaKey, $params, $monthlyDivisor);
            $results[] = $item;

            $totalPreviousMonthly += $item['current_monthly_salary'];
            $totalMonthlyAdjustment += $item['monthly_adjustment'];
            $totalNewMonthly += $item['new_monthly_salary'];
        }

        $count = count($results);
        $avgAdjustment = $count > 0 ? round($totalMonthlyAdjustment / $count, 2) : 0.0;
        $totalPercentChange = $totalPreviousMonthly > 0
            ? round(($totalMonthlyAdjustment / $totalPreviousMonthly) * 100, 2)
            : 0.0;

        return [
            'formula' => self::getFormulaDefinitions()[$formulaKey] ?? null,
            'params' => $params,
            'monthly_divisor' => $monthlyDivisor,
            'count' => $count,
            'totals' => [
                'previous_monthly_payroll' => round($totalPreviousMonthly, 2),
                'total_monthly_adjustment' => round($totalMonthlyAdjustment, 2),
                'new_monthly_payroll' => round($totalNewMonthly, 2),
                'average_monthly_adjustment' => $avgAdjustment,
                'percent_payroll_increase' => $totalPercentChange,
            ],
            'items' => $results,
        ];
    }

    /**
     * Save / Apply wage distortion adjustments to employee records and audit history
     */
    public function applyAdjustments(
        array $selectedEmployeeAdjustments,
        string $formulaKey,
        array $params,
        ?string $effectiveDate = null,
        ?string $reason = null
    ): int {
        $effective = $effectiveDate ? Carbon::parse($effectiveDate)->format('Y-m-d') : now()->format('Y-m-d');
        $formulaInfo = self::getFormulaDefinitions()[$formulaKey] ?? ['name' => 'Wage Distortion Converter'];
        $user = Auth::user();
        $userName = $user ? $user->name : 'Payroll Administrator';
        $appliedCount = 0;

        DB::transaction(function () use ($selectedEmployeeAdjustments, $formulaInfo, $effective, $reason, $userName, &$appliedCount) {
            foreach ($selectedEmployeeAdjustments as $item) {
                $employeeId = $item['employee_id'] ?? null;
                $newSalary = (float) ($item['new_monthly_salary'] ?? 0.0);
                $adjustmentAmount = (float) ($item['monthly_adjustment'] ?? 0.0);
                $oldSalary = (float) ($item['current_monthly_salary'] ?? 0.0);

                if (!$employeeId || $adjustmentAmount <= 0) {
                    continue;
                }

                $employee = Employee::find($employeeId);
                if (!$employee) {
                    continue;
                }

                $history = is_array($employee->salary_history) ? $employee->salary_history : [];

                $history[] = [
                    'effective_date' => $effective,
                    'previous_salary' => $oldSalary,
                    'new_salary' => $newSalary,
                    'adjustment_amount' => $adjustmentAmount,
                    'adjustment_type' => 'Wage Distortion Adjustment',
                    'formula' => $formulaInfo['name'],
                    'reason' => $reason ?: "Statutory Wage Distortion Adjustment via {$formulaInfo['name']}",
                    'approved_by' => $userName,
                    'applied_at' => now()->toIso8601String(),
                ];

                $employee->salary_history = $history;
                $employee->basic_salary = $newSalary;
                $employee->save();

                AuditLogger::log(
                    'Update',
                    'Payroll',
                    $employee->id,
                    "Applied wage distortion adjustment of PHP " . number_format($adjustmentAmount, 2) . " ({$formulaInfo['name']}) to {$employee->full_name}. New Basic Salary: PHP " . number_format($newSalary, 2)
                );

                $appliedCount++;
            }
        });

        return $appliedCount;
    }
}
