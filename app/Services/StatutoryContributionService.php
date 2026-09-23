<?php

namespace App\Services;

use App\Models\Hr\StatutoryContributionRule;
use Carbon\Carbon;

class StatutoryContributionService
{
    /**
     * Get the active rule for a specific statutory type and date.
     */
    public function getActiveRule(string $ruleType, ?string $date = null): ?StatutoryContributionRule
    {
        $evalDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return StatutoryContributionRule::where('rule_type', $ruleType)
            ->where('is_active', true)
            ->where('effective_date', '<=', $evalDate)
            ->where(function ($q) use ($evalDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $evalDate);
            })
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Calculate SSS contributions based on monthly salary and active rule.
     *
     * @param float $monthlySalary
     * @param string|null $date
     * @param string $frequency ('Semi-Monthly', 'Monthly', etc.)
     * @return array ['employee' => float, 'employer' => float, 'rule_id' => int|null]
     */
    public function calculateSss(float $monthlySalary, ?string $date = null, string $frequency = 'Semi-Monthly'): array
    {
        $rule = $this->getActiveRule('SSS', $date);
        $divisor = ($frequency === 'Semi-Monthly') ? 2 : 1;

        if (!$rule) {
            // Fallback default
            return [
                'employee' => round(min(1350, max(180, $monthlySalary * 0.045)) / $divisor, 2),
                'employer' => round(min(2850, max(380, $monthlySalary * 0.095)) / $divisor, 2),
                'rule_id' => null,
            ];
        }

        // If bracket JSON is configured in database
        if (!empty($rule->bracket_json) && is_array($rule->bracket_json)) {
            foreach ($rule->bracket_json as $bracket) {
                $min = (float) ($bracket['min'] ?? 0);
                $max = (float) ($bracket['max'] ?? PHP_INT_MAX);
                if ($monthlySalary >= $min && $monthlySalary <= $max) {
                    return [
                        'employee' => round(($bracket['ee_share'] ?? ($monthlySalary * ($rule->employee_share ?: 0.045))) / $divisor, 2),
                        'employer' => round(($bracket['er_share'] ?? ($monthlySalary * ($rule->employer_share ?: 0.095))) / $divisor, 2),
                        'rule_id' => $rule->id,
                    ];
                }
            }
        }

        // Formula based on min/max salary and rates
        $salaryBase = max((float)$rule->min_salary, min((float)$rule->max_salary, $monthlySalary));
        $eeRate = (float)$rule->employee_share > 0 ? (float)$rule->employee_share : 0.045;
        $erRate = (float)$rule->employer_share > 0 ? (float)$rule->employer_share : 0.095;

        return [
            'employee' => round(($salaryBase * $eeRate) / $divisor, 2),
            'employer' => round(($salaryBase * $erRate) / $divisor, 2),
            'rule_id' => $rule->id,
        ];
    }

    /**
     * Calculate PhilHealth premium based on monthly salary and active rule.
     */
    public function calculatePhilHealth(float $monthlySalary, ?string $date = null, string $frequency = 'Semi-Monthly'): array
    {
        $rule = $this->getActiveRule('PhilHealth', $date);
        $divisor = ($frequency === 'Semi-Monthly') ? 2 : 1;

        $rate = $rule && $rule->rate > 0 ? (float) $rule->rate : 0.0500;
        $minSalary = $rule && $rule->min_salary > 0 ? (float) $rule->min_salary : 10000.00;
        $maxSalary = $rule && $rule->max_salary > 0 ? (float) $rule->max_salary : 100000.00;

        $baseSalary = max($minSalary, min($maxSalary, $monthlySalary));
        $totalPremium = $baseSalary * $rate;

        $eeShareRatio = $rule && $rule->employee_share > 0 ? (float) $rule->employee_share : 0.50;
        $erShareRatio = $rule && $rule->employer_share > 0 ? (float) $rule->employer_share : 0.50;

        return [
            'employee' => round(($totalPremium * $eeShareRatio) / $divisor, 2),
            'employer' => round(($totalPremium * $erShareRatio) / $divisor, 2),
            'rule_id' => $rule?->id,
        ];
    }

    /**
     * Calculate Pag-IBIG (HDMF) contribution based on active rule.
     */
    public function calculatePagIbig(float $monthlySalary, ?string $date = null, string $frequency = 'Semi-Monthly'): array
    {
        $rule = $this->getActiveRule('PagIBIG', $date);
        $divisor = ($frequency === 'Semi-Monthly') ? 2 : 1;

        $fixedAmount = $rule && $rule->fixed_amount > 0 ? (float) $rule->fixed_amount : 200.00;
        $eeFixed = $rule && $rule->employee_share > 0 ? (float) $rule->employee_share : $fixedAmount;
        $erFixed = $rule && $rule->employer_share > 0 ? (float) $rule->employer_share : $fixedAmount;

        return [
            'employee' => round($eeFixed / $divisor, 2),
            'employer' => round($erFixed / $divisor, 2),
            'rule_id' => $rule?->id,
        ];
    }

    /**
     * Calculate Philippine BIR Withholding Tax based on taxable income and active brackets.
     */
    public function calculateWithholdingTax(float $taxableIncome, string $frequency = 'Semi-Monthly', ?string $date = null): array
    {
        $rule = $this->getActiveRule('BIR_Tax', $date);
        $tax = 0.0;

        if ($taxableIncome <= 0) {
            return ['tax' => 0.00, 'rule_id' => $rule?->id];
        }

        // If bracket JSON is available from rule in database
        if ($rule && !empty($rule->bracket_json) && is_array($rule->bracket_json)) {
            $brackets = $rule->bracket_json[$frequency] ?? $rule->bracket_json;
            foreach ($brackets as $b) {
                $min = (float) ($b['min'] ?? 0);
                $max = (float) ($b['max'] ?? PHP_INT_MAX);
                if ($taxableIncome >= $min && $taxableIncome <= $max) {
                    $baseTax = (float) ($b['base_tax'] ?? 0);
                    $rate = (float) ($b['percentage'] ?? 0);
                    $excess = max(0, $taxableIncome - $min);
                    $tax = $baseTax + ($excess * $rate);
                    return ['tax' => round($tax, 2), 'rule_id' => $rule->id];
                }
            }
        }

        // Standard Revised Philippine Withholding Tax Table (TRAIN Law)
        if ($frequency === 'Semi-Monthly') {
            if ($taxableIncome <= 10417) {
                $tax = 0.00;
            } elseif ($taxableIncome <= 16666) {
                $tax = ($taxableIncome - 10417) * 0.15;
            } elseif ($taxableIncome <= 33332) {
                $tax = 937.50 + (($taxableIncome - 16667) * 0.20);
            } elseif ($taxableIncome <= 83332) {
                $tax = 4270.70 + (($taxableIncome - 33333) * 0.25);
            } elseif ($taxableIncome <= 333332) {
                $tax = 16770.70 + (($taxableIncome - 83333) * 0.30);
            } else {
                $tax = 91770.70 + (($taxableIncome - 333333) * 0.35);
            }
        } else {
            // Monthly brackets
            if ($taxableIncome <= 20833) {
                $tax = 0.00;
            } elseif ($taxableIncome <= 33332) {
                $tax = ($taxableIncome - 20833) * 0.15;
            } elseif ($taxableIncome <= 66666) {
                $tax = 1875.00 + (($taxableIncome - 33333) * 0.20);
            } elseif ($taxableIncome <= 166666) {
                $tax = 8541.80 + (($taxableIncome - 66667) * 0.25);
            } elseif ($taxableIncome <= 666666) {
                $tax = 33541.80 + (($taxableIncome - 166667) * 0.30);
            } else {
                $tax = 183541.80 + (($taxableIncome - 666667) * 0.35);
            }
        }

        return [
            'tax' => round(max(0, $tax), 2),
            'rule_id' => $rule?->id,
        ];
    }
}
