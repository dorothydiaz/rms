<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Ess\EssRequest;

class EssPayrollPayslipTest extends TestCase
{
    public function test_employee_can_view_own_payslips_list_and_ytd_summary(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $period = PayrollPeriod::create([
            'name' => 'October 1-15, 2026 Cutoff',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-15',
            'payout_date' => '2026-10-20',
            'pay_frequency' => 'Semi-Monthly',
            'status' => 'Finalized',
        ]);

        $record = PayrollRecord::create([
            'payroll_period_id' => $period->payroll_period_id,
            'employee_id' => $empId,
            'basic_pay' => 15000.00,
            'total_work_days' => 11,
            'total_hours' => 88.0,
            'regular_hours_pay' => 15000.00,
            'overtime_hours' => 5.0,
            'overtime_pay' => 1171.88,
            'gross_pay' => 16171.88,
            'sss_employee' => 675.00,
            'philhealth_employee' => 375.00,
            'pagibig_employee' => 100.00,
            'withholding_tax' => 520.00,
            'total_deductions' => 1670.00,
            'net_pay' => 14501.88,
            'status' => 'Finalized',
        ]);

        $response = $this->actingAs($employee->user)->get('/ess/payroll');
        $response->assertStatus(200);
        $response->assertSee('October 1-15, 2026 Cutoff');
        $response->assertSee('14,501.88');
    }

    public function test_employee_cannot_view_or_export_another_employee_payslip(): void
    {
        $employeeA = Employee::whereNotNull('user_id')->first();
        $empAId = $employeeA->getRawOriginal('employee_id') ?? $employeeA->getKey();

        // Create or find a second employee
        $employeeB = Employee::where('employee_id', '!=', $empAId)->first();
        if (!$employeeB) {
            $employeeB = Employee::create([
                'employee_code' => 'EMP-TEST-PAY-B',
                'first_name' => 'Foreign',
                'last_name' => 'Worker',
                'work_email' => 'foreign.worker@stellarone.local',
                'employment_status' => 'Active',
            ]);
        }
        $empBId = $employeeB->getRawOriginal('employee_id') ?? $employeeB->getKey();

        $period = PayrollPeriod::create([
            'name' => 'Period Foreign Worker ' . uniqid(),
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-15',
            'payout_date' => '2026-11-20',
            'pay_frequency' => 'Semi-Monthly',
            'status' => 'Finalized',
        ]);

        $recordB = PayrollRecord::create([
            'payroll_period_id' => $period->payroll_period_id,
            'employee_id' => $empBId,
            'basic_pay' => 25000.00,
            'gross_pay' => 25000.00,
            'total_deductions' => 2000.00,
            'net_pay' => 23000.00,
            'status' => 'Finalized',
        ]);

        $response = $this->actingAs($employeeA->user)->get("/ess/payroll/records/{$recordB->payroll_record_id}/pdf");
        $response->assertStatus(403);
    }

    public function test_employee_can_file_payroll_dispute(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $period = PayrollPeriod::first();
        $record = PayrollRecord::where('employee_id', $empId)->first();

        $response = $this->actingAs($employee->user)->post('/ess/payroll/dispute', [
            'payroll_record_id' => $record->payroll_record_id,
            'dispute_category' => 'MISSING_OVERTIME',
            'details' => 'Overtime for October 5 (3 hours inventory) was omitted from payout calculation.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $empId,
            'request_type' => 'PAYROLL_DISPUTE',
            'status' => 'PENDING',
        ]);
    }
}
