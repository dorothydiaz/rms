<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\Hr\PayrollRecord;
use App\Services\Hr\DoleComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EssPayrollController extends Controller
{
    public function __construct(
        protected DoleComplianceService $doleService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return view('ess.no-employee-profile', compact('user'));
        }

        $records = PayrollRecord::with('payrollPeriod')
            ->where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->employee_id)
                  ->orWhere('employee_id', $employee->id);
            })
            ->orderBy('id', 'desc')
            ->paginate(12);

        // YTD summary calculations
        $currentYear = date('Y');
        $ytdRecords = PayrollRecord::where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->employee_id)
                  ->orWhere('employee_id', $employee->id);
            })
            ->whereHas('payrollPeriod', function ($q) use ($currentYear) {
                $q->whereYear('end_date', $currentYear);
            })
            ->get();

        $ytdGross = $ytdRecords->sum('gross_pay');
        $ytdNet = $ytdRecords->sum('net_pay');
        $ytdDeductions = $ytdRecords->sum('total_deductions');
        $ytdTax = $ytdRecords->sum('withholding_tax');
        $ytdSss = $ytdRecords->sum('sss_employee');
        $ytdPhilhealth = $ytdRecords->sum('philhealth_employee');
        $ytdPagibig = $ytdRecords->sum('pagibig_employee');

        // DOLE 13th-month pay accrued: Total basic salary earned in the year / 12 (PD 851)
        $accrued13thMonth = round($ytdRecords->sum('basic_pay') / 12.0, 2);

        return view('ess.payroll', compact(
            'user',
            'employee',
            'records',
            'ytdGross',
            'ytdNet',
            'ytdDeductions',
            'ytdTax',
            'ytdSss',
            'ytdPhilhealth',
            'ytdPagibig',
            'accrued13thMonth'
        ));
    }

    public function showPayslip(int $id): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        $record = PayrollRecord::with(['payrollPeriod', 'employee.department', 'employee.branch', 'employee.company'])
            ->findOrFail($id);

        // IDOR Protection: Employee can strictly only view their own payslip
        if (!$employee || $record->employee_id !== $employee->employee_id) {
            abort(403, 'Unauthorized access to confidential payslip record.');
        }

        return view('ess.payslip-detail', compact('user', 'employee', 'record'));
    }

    public function downloadPayslipPdf(int $id): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        $record = PayrollRecord::with(['payrollPeriod', 'employee.department', 'employee.branch', 'employee.company'])
            ->findOrFail($id);

        // IDOR Protection
        if (!$employee || $record->employee_id !== $employee->employee_id) {
            abort(403, 'Unauthorized access to confidential payslip record.');
        }

        return view('ess.payslip-print', compact('user', 'employee', 'record'));
    }
}
