<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\PayrollPeriod;
use App\Models\Ess\EssRequest;

class EssPayrollController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $records = PayrollRecord::with(['payrollPeriod', 'adjustments'])
            ->where('employee_id', $empId)
            ->orderByDesc('payroll_record_id')
            ->paginate(12);

        $latestPayslip = $records->first();

        // Calculate Year-to-Date (YTD) Summary for Form 2316 and DOLE Itemization
        $ytd = PayrollRecord::where('employee_id', $empId)
            ->whereYear('created_at', now()->year)
            ->selectRaw('
                COALESCE(SUM(payroll_record_gross_pay), 0) as total_gross,
                COALESCE(SUM(payroll_record_net_pay), 0) as total_net,
                COALESCE(SUM(payroll_record_withholding_tax), 0) as total_tax,
                COALESCE(SUM(payroll_record_sss_employee + payroll_record_philhealth_employee + payroll_record_pagibig_employee), 0) as total_statutory,
                COALESCE(SUM(payroll_record_total_deductions), 0) as total_deductions
            ')->first();

        return view('ess.payroll.index', compact('employee', 'records', 'latestPayslip', 'ytd'));
    }

    public function exportPdf(Request $request, $id)
    {
        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $record = PayrollRecord::with(['payrollPeriod', 'employee.company', 'employee.department', 'employee.position', 'adjustments'])
            ->where('payroll_record_id', $id)
            ->firstOrFail();

        // IDOR security check
        if ((int)$record->employee_id !== (int)$empId) {
            abort(403, 'Unauthorized access to payslip record.');
        }

        return view('ess.payroll.payslip_pdf', compact('employee', 'record'));
    }

    public function submitDispute(Request $request)
    {
        $request->validate([
            'payroll_record_id' => 'required|integer|exists:hr_payroll_records,payroll_record_id',
            'dispute_category' => 'required|string|max:100',
            'details' => 'required|string|max:2000',
        ]);

        $employee = $request->user()->employee;
        $empId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $record = PayrollRecord::with('payrollPeriod')->findOrFail($request->payroll_record_id);

        if ((int)$record->employee_id !== (int)$empId) {
            abort(403, 'Unauthorized payroll dispute submission.');
        }

        $essRequest = EssRequest::create([
            'request_number' => 'DISP-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
            'employee_id' => $empId,
            'request_type' => 'PAYROLL_DISPUTE',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => $request->details,
            'details' => [
                'payroll_record_id' => $record->payroll_record_id,
                'period_name' => $record->payrollPeriod?->name ?? 'Cutoff #' . $record->payroll_period_id,
                'dispute_category' => $request->dispute_category,
                'explanation' => $request->details,
            ],
            'status' => 'PENDING',
        ]);

        return redirect()->route('ess.payroll')->with('success', 'Payroll dispute inquiry #' . $essRequest->request_number . ' submitted successfully. Our payroll team will review the calculation.');
    }

    public function submitInquiry(Request $request, $id)
    {
        $request->merge(['payroll_record_id' => $id]);
        return $this->submitDispute($request);
    }
}
