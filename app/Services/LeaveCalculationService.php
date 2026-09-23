<?php

namespace App\Services;

use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use Carbon\Carbon;

class LeaveCalculationService
{
    /**
     * Check if employee has sufficient remaining leave balance for a given leave type and year.
     */
    public function hasSufficientBalance(int $employeeId, int $leaveTypeId, float $daysRequested, ?int $year = null): bool
    {
        $year = $year ?? now()->year;
        $leaveType = LeaveType::find($leaveTypeId);

        // If leave type is unpaid or does not track balance, always permit
        if (!$leaveType || !$leaveType->is_paid) {
            return true;
        }

        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return false;
        }

        return (float) $balance->remaining >= $daysRequested;
    }

    /**
     * Deduct leave credits upon approval of a leave request.
     */
    public function deductCredits(LeaveRequest $request): void
    {
        $year = Carbon::parse($request->start_date)->year;
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'year' => $year,
            ],
            [
                'beginning_balance' => 0.00,
                'earned' => 0.00,
                'used' => 0.00,
                'remaining' => 0.00,
                'encashed' => 0.00,
            ]
        );

        $days = (float) $request->number_of_days;
        $balance->used += $days;
        $balance->remaining = max(0.00, $balance->beginning_balance + $balance->earned - $balance->used - $balance->encashed);
        $balance->save();
    }

    /**
     * Restore leave credits if an approved leave is cancelled or rejected.
     */
    public function restoreCredits(LeaveRequest $request): void
    {
        $year = Carbon::parse($request->start_date)->year;
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            $days = (float) $request->number_of_days;
            $balance->used = max(0.00, $balance->used - $days);
            $balance->remaining = max(0.00, $balance->beginning_balance + $balance->earned - $balance->used - $balance->encashed);
            $balance->save();
        }
    }

    /**
     * Initialize balances for active employees for a given calendar year based on default credits.
     */
    public function initializeYearlyBalances(int $year): int
    {
        $employees = Employee::where('employment_status', 'Active')->get();
        $leaveTypes = LeaveType::all();
        $count = 0;

        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                $exists = LeaveBalance::where('employee_id', $emp->id)
                    ->where('leave_type_id', $lt->id)
                    ->where('year', $year)
                    ->exists();

                if (!$exists) {
                    $default = (float) $lt->default_credits;
                    LeaveBalance::create([
                        'employee_id' => $emp->id,
                        'leave_type_id' => $lt->id,
                        'year' => $year,
                        'beginning_balance' => $default,
                        'earned' => 0.00,
                        'used' => 0.00,
                        'remaining' => $default,
                        'encashed' => 0.00,
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }
}
