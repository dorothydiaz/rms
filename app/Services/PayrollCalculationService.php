<?php

namespace App\Services;

use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Services\AuditLogger;
use App\Services\StatutoryContributionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    protected StatutoryContributionService $statutoryService;

    public function __construct(StatutoryContributionService $statutoryService)
    {
        $this->statutoryService = $statutoryService;
    }

    /**
     * Calculate or recalculate payroll for an entire payroll period.
     * Must be wrapped in a database transaction.
     *
     * @param PayrollPeriod $period
     * @param int|null $branchId Optional branch scoping
     * @return int Number of records processed
     * @throws \Exception
     */
    public function calculatePeriod(PayrollPeriod $period, ?int $branchId = null): int
    {
        if ($period->status === 'Finalized') {
            throw new \Exception("Cannot recalculate finalized payroll period '{$period->name}'. Finalized records are permanently locked.");
        }

        return DB::transaction(function () use ($period, $branchId) {
            $employeesQuery = Employee::where('employment_status', 'Active');
            if ($branchId) {
                $employeesQuery->where('branch_id', $branchId);
            }
            $employees = $employeesQuery->get();

            $processedCount = 0;
            $frequency = $period->pay_frequency ?? 'Semi-Monthly';

            foreach ($employees as $employee) {
                $this->calculateEmployeePayroll($period, $employee, $frequency);
                $processedCount++;
            }

            AuditLogger::log(
                'Calculate',
                'Payroll',
                $period->id,
                "Calculated payroll for {$processedCount} employees in period '{$period->name}'"
            );

            return $processedCount;
        });
    }

    /**
     * Calculate individual employee payroll within a period.
     */
    public function calculateEmployeePayroll(PayrollPeriod $period, Employee $employee, string $frequency = 'Semi-Monthly'): PayrollRecord
    {
        $startDate = $period->start_date;
        $endDate = $period->end_date;

        // Determine rates
        $salaryType = $employee->salary_type ?? 'Monthly';
        $basicSalary = (float) ($employee->basic_salary ?? 0.00);

        if ($salaryType === 'Monthly') {
            $monthlySalary = $basicSalary;
            // 313 days factor for 6-day restaurant work week (or 26 days/month)
            $dailyRate = round($monthlySalary / 26, 2);
            $hourlyRate = round($dailyRate / 8, 2);
            $basePeriodPay = ($frequency === 'Semi-Monthly') ? round($monthlySalary / 2, 2) : $monthlySalary;
        } elseif ($salaryType === 'Daily') {
            $dailyRate = $basicSalary;
            $hourlyRate = round($dailyRate / 8, 2);
            $monthlySalary = round($dailyRate * 26, 2);
            $basePeriodPay = 0.00; // Will be computed based on work days
        } else {
            // Hourly
            $hourlyRate = $basicSalary;
            $dailyRate = round($hourlyRate * 8, 2);
            $monthlySalary = round($dailyRate * 26, 2);
            $basePeriodPay = 0.00;
        }

        // Fetch attendance records in period
        $attendances = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalWorkDays = 0.0;
        $totalHours = 0.0;
        $totalOtHours = 0.0;
        $totalNdHours = 0.0;
        $totalLateMinutes = 0;
        $totalUndertimeMinutes = 0;
        $totalAbsentDays = 0;
        $holidayPay = 0.00;
        $restDayPay = 0.00;

        foreach ($attendances as $att) {
            if (in_array($att->status, ['Present', 'Late'])) {
                $totalWorkDays += 1.0;
                $totalHours += (float) $att->total_hours;
                $totalOtHours += (float) $att->overtime_hours;
                $totalNdHours += (float) $att->night_diff_hours;
                $totalLateMinutes += (int) $att->late_minutes;
                $totalUndertimeMinutes += (int) $att->undertime_minutes;

                if ($att->holiday_type === 'Regular') {
                    $holidayPay += round($dailyRate * 1.0, 2); // 100% additional for regular holiday
                } elseif ($att->holiday_type === 'SpecialNonWorking') {
                    $holidayPay += round($dailyRate * 0.30, 2); // 30% additional for special non-working holiday
                }
                if ($att->is_rest_day) {
                    $restDayPay += round($dailyRate * 0.30, 2); // 30% premium for working on rest day
                }
            } elseif ($att->status === 'Half Day') {
                $totalWorkDays += 0.5;
                $totalHours += (float) $att->total_hours;
            } elseif ($att->status === 'Absent') {
                $totalAbsentDays += 1;
            }
        }

        // If daily/hourly, base pay is calculated from days/hours worked
        if ($salaryType === 'Daily') {
            $basePeriodPay = round($totalWorkDays * $dailyRate, 2);
        } elseif ($salaryType === 'Hourly') {
            $basePeriodPay = round($totalHours * $hourlyRate, 2);
        }

        // Overtime pay (1.25x hourly rate)
        $overtimePay = round($totalOtHours * $hourlyRate * 1.25, 2);

        // Night differential pay (10% additional)
        $nightDiffPay = round($totalNdHours * ($hourlyRate * 0.10), 2);

        // Allowances
        $allowanceAmount = (float) ($employee->allowances ?? 0.00);
        $periodAllowance = ($frequency === 'Semi-Monthly') ? round($allowanceAmount / 2, 2) : $allowanceAmount;

        // Deductions for attendance
        $lateDeduction = round(($totalLateMinutes / 60) * $hourlyRate, 2);
        $undertimeDeduction = round(($totalUndertimeMinutes / 60) * $hourlyRate, 2);
        $absenceDeduction = ($salaryType === 'Monthly') ? round($totalAbsentDays * $dailyRate, 2) : 0.00;

        // Gross Pay
        $grossPay = round(
            $basePeriodPay +
            $overtimePay +
            $nightDiffPay +
            $holidayPay +
            $restDayPay +
            $periodAllowance,
            2
        );

        // Statutory Contributions
        $periodDate = $period->end_date ? Carbon::parse($period->end_date)->toDateString() : now()->toDateString();
        $sss = $this->statutoryService->calculateSss($monthlySalary, $periodDate, $frequency);
        $philHealth = $this->statutoryService->calculatePhilHealth($monthlySalary, $periodDate, $frequency);
        $pagIbig = $this->statutoryService->calculatePagIbig($monthlySalary, $periodDate, $frequency);

        // Compute Taxable Income
        $statutoryTotalEe = $sss['employee'] + $philHealth['employee'] + $pagIbig['employee'];
        $attendanceDeductionsTotal = $lateDeduction + $undertimeDeduction + $absenceDeduction;
        $taxableIncome = max(0, $grossPay - $statutoryTotalEe - $attendanceDeductionsTotal);

        $taxCalc = $this->statutoryService->calculateWithholdingTax($taxableIncome, $frequency, $periodDate);
        $withholdingTax = $taxCalc['tax'];

        // Existing adjustments if any
        $existingRecord = PayrollRecord::where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->first();

        $adjustmentsSum = 0.0;
        if ($existingRecord) {
            foreach ($existingRecord->adjustments as $adj) {
                if ($adj->adjustment_type === 'Earning') {
                    $adjustmentsSum += (float) $adj->amount;
                } else {
                    $adjustmentsSum -= (float) $adj->amount;
                }
            }
        }

        $totalDeductions = round(
            $lateDeduction +
            $undertimeDeduction +
            $absenceDeduction +
            $sss['employee'] +
            $philHealth['employee'] +
            $pagIbig['employee'] +
            $withholdingTax,
            2
        );

        $netPay = round(max(0.00, $grossPay - $totalDeductions + $adjustmentsSum), 2);

        $recordData = [
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'basic_pay' => $basePeriodPay,
            'total_work_days' => $totalWorkDays,
            'total_hours' => $totalHours,
            'regular_hours_pay' => $basePeriodPay,
            'overtime_hours' => $totalOtHours,
            'overtime_pay' => $overtimePay,
            'night_diff_hours' => $totalNdHours,
            'night_diff_pay' => $nightDiffPay,
            'holiday_pay' => $holidayPay,
            'rest_day_pay' => $restDayPay,
            'allowances' => $periodAllowance,
            'bonuses' => 0.00,
            'other_earnings' => 0.00,
            'gross_pay' => $grossPay,
            'late_deduction' => $lateDeduction,
            'undertime_deduction' => $undertimeDeduction,
            'absence_deduction' => $absenceDeduction,
            'sss_employee' => $sss['employee'],
            'sss_employer' => $sss['employer'],
            'philhealth_employee' => $philHealth['employee'],
            'philhealth_employer' => $philHealth['employer'],
            'pagibig_employee' => $pagIbig['employee'],
            'pagibig_employer' => $pagIbig['employer'],
            'withholding_tax' => $withholdingTax,
            'salary_advance' => 0.00,
            'employee_loan' => 0.00,
            'other_deductions' => 0.00,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'status' => 'Draft',
        ];

        return PayrollRecord::updateOrCreate(
            ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
            $recordData
        );
    }
}
