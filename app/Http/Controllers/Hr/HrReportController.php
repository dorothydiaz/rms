<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\TrainingProgram;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReportController extends Controller
{
    public function index(Request $request): View
    {
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $payrollPeriods = PayrollPeriod::orderBy('start_date', 'desc')->get();

        return view('hr.reports.index', compact('branches', 'departments', 'payrollPeriods'));
    }

    /**
     * Export Employee Masterlist to CSV
     */
    public function exportEmployees(Request $request): StreamedResponse
    {
        $query = Employee::with(['branch', 'department', 'position']);
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        $employees = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_masterlist_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee ID', 'Full Name', 'Branch', 'Department', 'Position',
                'Status', 'Type', 'Date Hired', 'Basic Salary', 'Pay Frequency',
                'SSS', 'PhilHealth', 'Pag-IBIG', 'TIN', 'Mobile', 'Email'
            ]);

            foreach ($employees as $e) {
                fputcsv($handle, [
                    $e->employee_id,
                    $e->full_name,
                    $e->branch?->name,
                    $e->department?->name,
                    $e->position?->name,
                    $e->employment_status,
                    $e->employment_type,
                    $e->date_hired,
                    $e->basic_salary,
                    $e->pay_frequency,
                    $e->sss_number,
                    $e->philhealth_number,
                    $e->pagibig_number,
                    $e->tin,
                    $e->mobile_number,
                    $e->email,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export DTR to CSV
     */
    public function exportAttendance(Request $request): StreamedResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $query = AttendanceRecord::with(['employee.branch'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $records = $query->orderBy('date', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_dtr_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date', 'Employee ID', 'Employee Name', 'Branch', 'Time In', 'Time Out',
                'Total Hours', 'Late (Min)', 'Undertime (Min)', 'Overtime (Hrs)', 'Night Diff (Hrs)', 'Status'
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date,
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->branch?->name,
                    $r->time_in,
                    $r->time_out,
                    $r->total_hours,
                    $r->late_minutes,
                    $r->undertime_minutes,
                    $r->overtime_hours,
                    $r->night_diff_hours,
                    $r->status,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Payroll Register to CSV
     */
    public function exportPayroll(Request $request): StreamedResponse
    {
        $periodId = $request->get('payroll_period_id');
        $query = PayrollRecord::with(['employee.branch', 'payrollPeriod']);
        if ($periodId) {
            $query->where('payroll_period_id', $periodId);
        }
        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payroll_register_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Period', 'Employee ID', 'Employee Name', 'Branch', 'Basic Pay',
                'Overtime Pay', 'Night Diff Pay', 'Holiday Pay', 'Allowances', 'Gross Pay',
                'Late/Absence Ded', 'SSS (EE)', 'PhilHealth (EE)', 'Pag-IBIG (EE)',
                'Withholding Tax', 'Total Deductions', 'Net Pay'
            ]);

            foreach ($records as $p) {
                fputcsv($handle, [
                    $p->payrollPeriod?->name,
                    $p->employee?->employee_id,
                    $p->employee?->full_name,
                    $p->employee?->branch?->name,
                    $p->basic_pay,
                    $p->overtime_pay,
                    $p->night_diff_pay,
                    $p->holiday_pay,
                    $p->allowances,
                    $p->gross_pay,
                    $p->late_deduction + $p->undertime_deduction + $p->absence_deduction,
                    $p->sss_employee,
                    $p->philhealth_employee,
                    $p->pagibig_employee,
                    $p->withholding_tax,
                    $p->total_deductions,
                    $p->net_pay,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
