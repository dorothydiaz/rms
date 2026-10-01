<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AttendanceActionLog;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Branch;
use App\Models\Hr\Company;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\ScheduleChangeLog;
use App\Models\Hr\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReportController extends Controller
{
    /**
     * Get common filter options for reports (branches, departments, companies, employees).
     */
    protected function getFilterOptions(): array
    {
        return [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'employee_id']),
        ];
    }

    /**
     * Apply multi-filtering (employee search, status, branch, company/agency, department, source)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param bool $isDirectEmployee True if the query is directly on Employee model
     * @param string $employeeRelation Name of relation pointing to Employee (default 'employee')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyEmployeeFilters($query, Request $request, bool $isDirectEmployee = false, string $employeeRelation = 'employee')
    {
        // 1. Search Individual Employee (by first name, last name, or employee_id)
        if ($request->filled('search')) {
            $search = trim($request->search);
            if ($isDirectEmployee) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
                });
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
                });
            }
        }

        // 2. Specific Employee ID Selection
        if ($request->filled('employee_id')) {
            if ($isDirectEmployee) {
                $query->where('id', $request->employee_id);
            } else {
                $query->where('employee_id', $request->employee_id);
            }
        }

        // 3. Branch Filter
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            if ($isDirectEmployee) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereJsonContains('assigned_branch_ids', (int) $branchId)
                      ->orWhereJsonContains('assigned_branch_ids', (string) $branchId);
                });
            } else {
                $table = $query->getModel()->getTable();
                if (Schema::hasColumn($table, 'branch_id')) {
                    $query->where(function ($q) use ($branchId, $employeeRelation) {
                        $q->where('branch_id', $branchId)
                          ->orWhereHas($employeeRelation, function ($eq) use ($branchId) {
                              $eq->where('branch_id', $branchId)
                                 ->orWhereJsonContains('assigned_branch_ids', (int) $branchId)
                                 ->orWhereJsonContains('assigned_branch_ids', (string) $branchId);
                          });
                    });
                } else {
                    $query->whereHas($employeeRelation, function ($eq) use ($branchId) {
                        $eq->where('branch_id', $branchId)
                           ->orWhereJsonContains('assigned_branch_ids', (int) $branchId)
                           ->orWhereJsonContains('assigned_branch_ids', (string) $branchId);
                    });
                }
            }
        }

        // 4. Company / Agency Filter
        if ($request->filled('company_id')) {
            $companyId = $request->company_id;
            if ($isDirectEmployee) {
                $query->where('company_id', $companyId);
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }
        }

        // 5. Department Filter
        if ($request->filled('department_id')) {
            $deptId = $request->department_id;
            if ($isDirectEmployee) {
                $query->where(function ($q) use ($deptId) {
                    $q->where('department_id', $deptId)
                      ->orWhereJsonContains('assigned_department_ids', (int) $deptId)
                      ->orWhereJsonContains('assigned_department_ids', (string) $deptId);
                });
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($deptId) {
                    $q->where('department_id', $deptId)
                      ->orWhereJsonContains('assigned_department_ids', (int) $deptId)
                      ->orWhereJsonContains('assigned_department_ids', (string) $deptId);
                });
            }
        }

        // 6. Employment Status Filter
        if ($request->filled('employment_status')) {
            $status = $request->employment_status;
            $statusClosure = function ($q) use ($status) {
                if ($status === 'ACTIVE_ALL') {
                    $q->whereNotIn('employment_status', ['Resigned', 'Terminated', 'Retired', 'Inactive']);
                } elseif ($status === 'INACTIVE_ALL') {
                    $q->whereIn('employment_status', ['Resigned', 'Terminated', 'Retired', 'Inactive']);
                } else {
                    $q->where('employment_status', $status);
                }
            };

            if ($isDirectEmployee) {
                $statusClosure($query);
            } else {
                $query->whereHas($employeeRelation, $statusClosure);
            }
        }

        // 7. Employment Source Filter (Direct Hire vs Agency)
        if ($request->filled('employment_source')) {
            $source = $request->employment_source;
            if ($isDirectEmployee) {
                $query->where('employment_source', $source);
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($source) {
                    $q->where('employment_source', $source);
                });
            }
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $payrollPeriods = PayrollPeriod::orderBy('start_date', 'desc')->get();
        $employees = Employee::activeWorkforce()->orderBy('first_name')->get();

        // High-level compliance stats
        $totalScheduleChanges = ScheduleChangeLog::count();
        $pendingOvertimeCount = AttendanceRecord::where('overtime_hours', '>', 0)->where('overtime_status', 'Pending')->count();
        $authorizedUndertimeCount = AttendanceRecord::where('undertime_minutes', '>', 0)->where('undertime_status', 'Approved')->count();
        $unauthorizedUndertimeCount = AttendanceRecord::where('undertime_minutes', '>', 0)->where('undertime_status', 'Rejected')->count();
        $tardyEmployeesCount = AttendanceRecord::where('late_minutes', '>', 0)->distinct('employee_id')->count('employee_id');
        $unauthorizedAbsencesCount = AttendanceRecord::where(function($q) {
            $q->where('status', 'Absent')->orWhere('absence_days', '>', 0);
        })->count();
        $authorizedLeaveCount = LeaveRequest::where('status', 'Approved')->count();
        $authorizedLeaveDays = (float) LeaveRequest::where('status', 'Approved')->sum('number_of_days');

        return view('hr.reports.index', compact(
            'branches', 'departments', 'companies', 'payrollPeriods', 'employees',
            'totalScheduleChanges', 'pendingOvertimeCount', 'authorizedUndertimeCount',
            'unauthorizedUndertimeCount', 'tardyEmployeesCount', 'unauthorizedAbsencesCount',
            'authorizedLeaveCount', 'authorizedLeaveDays'
        ));
    }

    /**
     * Export Employee Masterlist to CSV
     */
    public function exportEmployees(Request $request): StreamedResponse
    {
        $query = Employee::with(['branch', 'department', 'position', 'company']);
        $this->applyEmployeeFilters($query, $request, true);
        $employees = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="employee_masterlist_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Employee ID', 'Full Name', 'Company / Agency', 'Branch', 'Department', 'Position',
                'Status', 'Type', 'Source', 'Date Hired', 'Basic Salary', 'Pay Frequency',
                'SSS', 'PhilHealth', 'Pag-IBIG', 'TIN', 'Mobile', 'Email'
            ]);

            foreach ($employees as $e) {
                fputcsv($handle, [
                    $e->employee_id,
                    $e->full_name,
                    $e->company_or_agency,
                    $e->branch?->name,
                    $e->department?->name,
                    $e->position?->name,
                    $e->employment_status,
                    $e->employment_type,
                    $e->employment_source,
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
        $startDate = $request->get('start_date', $request->get('date_from', Carbon::now()->startOfMonth()->toDateString()));
        $endDate = $request->get('end_date', $request->get('date_to', Carbon::now()->toDateString()));

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company'])
            ->whereBetween('date', [$startDate, $endDate]);

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->orderBy('date', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="attendance_dtr_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Time In', 'Time Out',
                'Total Hours', 'Late (Min)', 'Undertime (Min)', 'Overtime (Hrs)', 'Night Diff (Hrs)', 'Status'
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date,
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
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
        $query = PayrollRecord::with(['employee.branch', 'employee.department', 'employee.company', 'payrollPeriod']);
        if ($periodId) {
            $query->where('payroll_period_id', $periodId);
        }
        $this->applyEmployeeFilters($query, $request, false);
        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="payroll_register_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'Period', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Basic Pay',
                'Overtime Pay', 'Night Diff Pay', 'Holiday Pay', 'Allowances', 'Gross Pay',
                'Late/Absence Ded', 'SSS (EE)', 'PhilHealth (EE)', 'Pag-IBIG (EE)',
                'Withholding Tax', 'Total Deductions', 'Net Pay'
            ]);

            foreach ($records as $p) {
                fputcsv($handle, [
                    $p->payrollPeriod?->name,
                    $p->employee?->employee_id,
                    $p->employee?->full_name,
                    $p->employee?->company_or_agency,
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

    // =========================================================================
    // 1. CHANGE OF SCHEDULE HISTORY REPORT
    // =========================================================================

    public function changeOfScheduleReport(Request $request): View
    {
        $query = ScheduleChangeLog::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'changer'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('date_from')) {
            $query->where('schedule_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('schedule_date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $logs = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.change-of-schedule', array_merge(['logs' => $logs], $filterOpts));
    }

    public function exportChangeOfSchedule(Request $request): StreamedResponse
    {
        $query = ScheduleChangeLog::with(['employee.branch', 'employee.department', 'employee.company', 'changer'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('date_from')) {
            $query->where('schedule_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('schedule_date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $logs = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="change_of_schedule_history_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Schedule Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Previous Schedule', 'New Schedule', 'Reason', 'Changed By', 'Timestamp']);

            foreach ($logs as $l) {
                fputcsv($handle, [
                    $l->schedule_date?->toDateString(),
                    $l->employee?->employee_id,
                    $l->employee?->full_name,
                    $l->employee?->company_or_agency,
                    $l->employee?->branch?->name,
                    $l->employee?->department?->name,
                    $l->previous_time_range,
                    $l->new_time_range,
                    $l->reason,
                    $l->changer?->name ?? 'System/Admin',
                    $l->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 2. OVERTIME HISTORY REPORT (WITH APPROVALS)
    // =========================================================================

    public function overtimeHistoryReport(Request $request): View
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'overtimeApprover'])
            ->where('overtime_hours', '>', 0)
            ->orderBy('date', 'desc');

        if ($request->filled('status')) {
            $query->where('overtime_status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.overtime-history', array_merge(['records' => $records], $filterOpts));
    }

    public function exportOvertimeHistory(Request $request): StreamedResponse
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company', 'overtimeApprover'])
            ->where('overtime_hours', '>', 0)
            ->orderBy('date', 'desc');

        if ($request->filled('status')) {
            $query->where('overtime_status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="overtime_history_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Time In', 'Time Out', 'Total Hours', 'Overtime Hours', 'Approval Status', 'Approved By', 'Approved At', 'Remarks']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $r->employee?->department?->name,
                    $r->time_in,
                    $r->time_out,
                    $r->total_hours,
                    $r->overtime_hours,
                    $r->overtime_status,
                    $r->overtimeApprover?->name,
                    $r->overtime_approved_at,
                    $r->overtime_remarks,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 3. MANUAL TIME ENTRIES HISTORY REPORT
    // =========================================================================

    public function manualEntriesHistoryReport(Request $request): View
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'actionLogs.actor'])
            ->where('source', 'Manual')
            ->orderBy('date', 'desc');

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.manual-entries-history', array_merge(['records' => $records], $filterOpts));
    }

    public function exportManualEntriesHistory(Request $request): StreamedResponse
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company'])
            ->where('source', 'Manual')
            ->orderBy('date', 'desc');

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="manual_time_entries_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Time In', 'Time Out', 'Total Hours', 'Late (Mins)', 'Undertime (Mins)', 'Remarks', 'Logged At']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $r->employee?->department?->name,
                    $r->time_in,
                    $r->time_out,
                    $r->total_hours,
                    $r->late_minutes,
                    $r->undertime_minutes,
                    $r->dtr_remarks,
                    $r->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 4. AUTHORIZED & UNAUTHORIZED UNDERTIME REPORT
    // =========================================================================

    public function authorizedUndertimeReport(Request $request): View
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0)
            ->orderBy('date', 'desc');

        $status = $request->get('status', 'Approved');
        if ($status && $status !== 'ALL') {
            $query->where('undertime_status', $status);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.authorized-undertime', array_merge(['records' => $records, 'status' => $status], $filterOpts));
    }

    public function exportAuthorizedUndertime(Request $request): StreamedResponse
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0)
            ->orderBy('date', 'desc');

        $status = $request->get('status', 'Approved');
        if ($status && $status !== 'ALL') {
            $query->where('undertime_status', $status);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="undertime_authorization_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Time In', 'Time Out', 'Undertime (Mins)', 'Total Hours', 'Authorization Status', 'Authorized By', 'Remarks']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $r->employee?->department?->name,
                    $r->time_in,
                    $r->time_out,
                    $r->undertime_minutes,
                    $r->total_hours,
                    $r->undertime_status === 'Approved' ? 'Authorized Undertime' : ($r->undertime_status === 'Rejected' ? 'Unauthorized Undertime' : 'Pending Review'),
                    $r->undertimeApprover?->name,
                    $r->undertime_remarks,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 5. UNAUTHORIZED LEAVE OF ABSENCES REPORT
    // =========================================================================

    public function unauthorizedAbsencesReport(Request $request): View
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('status', 'Absent')
                  ->orWhere('absence_days', '>', 0)
                  ->orWhere('dtr_remarks', 'like', '%UA%')
                  ->orWhere('dtr_remarks', 'like', '%AWOL%');
            })
            ->orderBy('date', 'desc');

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.unauthorized-absences', array_merge([
            'records' => $records,
            'startDate' => $startDate,
            'endDate' => $endDate
        ], $filterOpts));
    }

    public function exportUnauthorizedAbsences(Request $request): StreamedResponse
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('status', 'Absent')
                  ->orWhere('absence_days', '>', 0)
                  ->orWhere('dtr_remarks', 'like', '%UA%')
                  ->orWhere('dtr_remarks', 'like', '%AWOL%');
            })
            ->orderBy('date', 'desc');

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="unauthorized_absences_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Absence Days', 'Remarks', 'Classification']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $r->employee?->department?->name,
                    $r->absence_days ?: 1.0,
                    $r->dtr_remarks ?: 'Unauthorized Absence',
                    'Unauthorized Absence / AWOL',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 5B. AUTHORIZED LEAVE OF ABSENCE (ALOA) REPORT
    // =========================================================================

    public function authorizedLeaveOfAbsenceReport(Request $request): View
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = LeaveRequest::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'leaveType', 'approver'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->orderBy('start_date', 'desc');

        // Status filter: defaults to 'Approved' (Authorized ALOA), with option to view 'ALL'
        $status = $request->get('status', 'Approved');
        if ($status && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('is_paid')) {
            $isPaid = (bool) $request->is_paid;
            $query->whereHas('leaveType', function ($q) use ($isPaid) {
                $q->where('is_paid', $isPaid);
            });
        }

        $this->applyEmployeeFilters($query, $request, false);

        // Summary statistics for the filtered scope
        $summaryQuery = clone $query;
        $totalApprovedRequests = (clone $summaryQuery)->count();
        $totalLeaveDays = (float) (clone $summaryQuery)->sum('number_of_days');
        $totalPaidDays = (float) (clone $summaryQuery)->whereHas('leaveType', fn($q) => $q->where('is_paid', true))->sum('number_of_days');
        $totalUnpaidDays = (float) (clone $summaryQuery)->whereHas('leaveType', fn($q) => $q->where('is_paid', false))->sum('number_of_days');
        $uniqueEmployeesCount = (clone $summaryQuery)->distinct('employee_id')->count('employee_id');

        $records = $query->paginate(20)->withQueryString();
        $leaveTypes = LeaveType::all();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.authorized-leave-of-absence', array_merge([
            'records' => $records,
            'leaveTypes' => $leaveTypes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'totalApprovedRequests' => $totalApprovedRequests,
            'totalLeaveDays' => $totalLeaveDays,
            'totalPaidDays' => $totalPaidDays,
            'totalUnpaidDays' => $totalUnpaidDays,
            'uniqueEmployeesCount' => $uniqueEmployeesCount,
        ], $filterOpts));
    }

    public function exportAuthorizedLeaveOfAbsence(Request $request): StreamedResponse
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = LeaveRequest::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'leaveType', 'approver'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                  });
            })
            ->orderBy('start_date', 'desc');

        $status = $request->get('status', 'Approved');
        if ($status && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('is_paid')) {
            $isPaid = (bool) $request->is_paid;
            $query->whereHas('leaveType', fn($q) => $q->where('is_paid', $isPaid));
        }

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="authorized_leave_of_absence_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Start Date',
                'End Date',
                'Employee ID',
                'Employee Name',
                'Company / Agency',
                'Branch',
                'Department',
                'Position',
                'Leave Type',
                'Leave Code',
                'Number of Days',
                'Pay Classification',
                'Status',
                'Reason / Justification',
                'Authorized / Approved By',
                'Approval Timestamp',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->start_date?->format('Y-m-d'),
                    $r->end_date?->format('Y-m-d'),
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $r->employee?->department?->name,
                    $r->employee?->position?->name,
                    $r->leaveType?->name,
                    $r->leaveType?->code,
                    $r->number_of_days,
                    $r->leaveType?->is_paid ? 'Paid Leave' : 'Unpaid Leave',
                    $r->status,
                    $r->reason,
                    $r->approver?->full_name ?? ($r->approver?->name ?? 'System Administrator'),
                    $r->approval_date?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 6. ALL EMPLOYEES WITH TARDINESS REPORT
    // =========================================================================

    public function tardinessReport(Request $request): View
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('late_minutes', '>', 0);

        $this->applyEmployeeFilters($query, $request, false);

        // Summary ranking by employee
        $rankingQuery = clone $query;
        $tardySummary = $rankingQuery->select(
            'employee_id',
            DB::raw('count(*) as occurrence_count'),
            DB::raw('sum(late_minutes) as total_late_minutes'),
            DB::raw('avg(late_minutes) as avg_late_minutes')
        )
        ->groupBy('employee_id')
        ->orderByDesc('total_late_minutes')
        ->with('employee.branch', 'employee.position', 'employee.company')
        ->get();

        $records = $query->orderBy('date', 'desc')->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.tardiness', array_merge([
            'records' => $records,
            'tardySummary' => $tardySummary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ], $filterOpts));
    }

    public function exportTardiness(Request $request): StreamedResponse
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('late_minutes', '>', 0)
            ->orderBy('date', 'desc');

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tardiness_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Time In', 'Late (Minutes)', 'Status']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->employee?->employee_id,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $r->employee?->department?->name,
                    $r->time_in,
                    $r->late_minutes,
                    $r->status,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 7. ATTENDANCE SUMMARY REPORT
    // =========================================================================

    public function attendanceSummaryReport(Request $request): View
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = AttendanceRecord::whereBetween('date', [$startDate, $endDate]);

        $this->applyEmployeeFilters($query, $request, false);

        $summary = $query->select(
            'employee_id',
            DB::raw('count(*) as logged_days'),
            DB::raw('sum(CASE WHEN status = "Present" OR regular_hours > 0 THEN 1 ELSE 0 END) as present_days'),
            DB::raw('sum(CASE WHEN is_rest_day = 1 THEN 1 ELSE 0 END) as rest_days'),
            DB::raw('sum(CASE WHEN late_minutes > 0 THEN 1 ELSE 0 END) as late_occurrences'),
            DB::raw('sum(late_minutes) as total_late_minutes'),
            DB::raw('sum(undertime_minutes) as total_undertime_minutes'),
            DB::raw('sum(overtime_hours) as total_overtime_hours'),
            DB::raw('sum(absence_days) as total_absences'),
            DB::raw('sum(total_hours) as total_hours')
        )
        ->groupBy('employee_id')
        ->with('employee.branch', 'employee.department', 'employee.position', 'employee.company')
        ->paginate(20)
        ->withQueryString();

        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.attendance-summary', array_merge([
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ], $filterOpts));
    }

    public function exportAttendanceSummary(Request $request): StreamedResponse
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());

        $query = AttendanceRecord::whereBetween('date', [$startDate, $endDate]);

        $this->applyEmployeeFilters($query, $request, false);

        $summary = $query->select(
            'employee_id',
            DB::raw('count(*) as logged_days'),
            DB::raw('sum(CASE WHEN status = "Present" OR regular_hours > 0 THEN 1 ELSE 0 END) as present_days'),
            DB::raw('sum(CASE WHEN is_rest_day = 1 THEN 1 ELSE 0 END) as rest_days'),
            DB::raw('sum(CASE WHEN late_minutes > 0 THEN 1 ELSE 0 END) as late_occurrences'),
            DB::raw('sum(late_minutes) as total_late_minutes'),
            DB::raw('sum(undertime_minutes) as total_undertime_minutes'),
            DB::raw('sum(overtime_hours) as total_overtime_hours'),
            DB::raw('sum(absence_days) as total_absences'),
            DB::raw('sum(total_hours) as total_hours')
        )
        ->groupBy('employee_id')
        ->with('employee.branch', 'employee.department', 'employee.position', 'employee.company')
        ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="attendance_summary_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($summary, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Period', $startDate . ' to ' . $endDate]);
            fputcsv($handle, ['Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Position', 'Logged Days', 'Present Days', 'Rest Days', 'Late Occurrences', 'Total Late (Mins)', 'Undertime (Mins)', 'Overtime (Hrs)', 'Absences (Days)', 'Total Work Hours']);

            foreach ($summary as $s) {
                fputcsv($handle, [
                    $s->employee?->employee_id,
                    $s->employee?->full_name,
                    $s->employee?->company_or_agency,
                    $s->employee?->branch?->name,
                    $s->employee?->department?->name,
                    $s->employee?->position?->name,
                    $s->logged_days,
                    $s->present_days,
                    $s->rest_days,
                    $s->late_occurrences,
                    $s->total_late_minutes,
                    $s->total_undertime_minutes,
                    $s->total_overtime_hours,
                    $s->total_absences,
                    $s->total_hours,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 8. INDIVIDUAL ATTENDANCE SUMMARY REPORT
    // =========================================================================

    public function individualAttendanceSummaryReport(Request $request): View
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());
        $selectedEmpId = $request->get('employee_id');

        $empQuery = Employee::query();
        $this->applyEmployeeFilters($empQuery, $request, true);
        $employees = $empQuery->orderBy('first_name')->get();

        $employee = null;
        $records = collect();
        $stats = [
            'total_days' => 0,
            'present_days' => 0,
            'rest_days' => 0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_hours' => 0,
            'absence_days' => 0,
            'total_hours' => 0,
        ];

        if ($selectedEmpId) {
            $employee = Employee::with(['branch', 'department', 'position', 'company', 'defaultShiftTemplate'])->find($selectedEmpId);
            if ($employee) {
                $records = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->orderBy('date', 'desc')
                    ->get();

                $stats['total_days'] = $records->count();
                $stats['present_days'] = $records->filter(fn($r) => $r->status === 'Present' || $r->regular_hours > 0)->count();
                $stats['rest_days'] = $records->where('is_rest_day', true)->count();
                $stats['late_minutes'] = $records->sum('late_minutes');
                $stats['undertime_minutes'] = $records->sum('undertime_minutes');
                $stats['overtime_hours'] = $records->sum('overtime_hours');
                $stats['absence_days'] = $records->sum('absence_days');
                $stats['total_hours'] = $records->sum('total_hours');
            }
        }

        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.individual-attendance', array_merge([
            'employees' => $employees,
            'employee' => $employee,
            'records' => $records,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ], $filterOpts));
    }

    public function exportIndividualAttendanceSummary(Request $request): StreamedResponse
    {
        $startDate = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('date_to', Carbon::now()->toDateString());
        $employee = Employee::with(['branch', 'department', 'company'])->findOrFail($request->employee_id);

        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="individual_attendance_' . $employee->employee_id . '_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records, $employee, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Employee:', $employee->full_name, 'ID:', $employee->employee_id, 'Company / Agency:', $employee->company_or_agency]);
            fputcsv($handle, ['Branch:', $employee->branch?->name, 'Department:', $employee->department?->name, 'Period:', $startDate . ' to ' . $endDate]);
            fputcsv($handle, []);
            fputcsv($handle, ['Date', 'Day', 'Time In', 'Time Out', 'Total Hours', 'Late (Mins)', 'Undertime (Mins)', 'Overtime (Hrs)', 'Status', 'Notes']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->date?->format('l'),
                    $r->time_in,
                    $r->time_out,
                    $r->total_hours,
                    $r->late_minutes,
                    $r->undertime_minutes,
                    $r->overtime_hours,
                    $r->status,
                    $r->notes,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 9. EMPLOYEE ATTENDANCE PROFILE REPORT
    // =========================================================================

    public function employeeAttendanceProfileReport(Request $request): View
    {
        $selectedEmpId = $request->get('employee_id');
        $empQuery = Employee::query();
        $this->applyEmployeeFilters($empQuery, $request, true);
        $employees = $empQuery->orderBy('first_name')->get();

        $employee = null;
        $recentLogs = collect();
        $changeLogs = collect();
        $actionLogs = collect();

        if ($selectedEmpId) {
            $employee = Employee::with(['branch', 'department', 'position', 'company', 'defaultShiftTemplate'])->find($selectedEmpId);
            if ($employee) {
                $recentLogs = AttendanceRecord::where('employee_id', $employee->id)
                    ->orderBy('date', 'desc')
                    ->take(30)
                    ->get();

                $changeLogs = ScheduleChangeLog::where('employee_id', $employee->id)
                    ->orderBy('created_at', 'desc')
                    ->take(15)
                    ->get();

                $actionLogs = AttendanceActionLog::where('employee_id', $employee->id)
                    ->orderBy('created_at', 'desc')
                    ->take(15)
                    ->get();
            }
        }

        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.employee-profile', array_merge([
            'employees' => $employees,
            'employee' => $employee,
            'recentLogs' => $recentLogs,
            'changeLogs' => $changeLogs,
            'actionLogs' => $actionLogs,
        ], $filterOpts));
    }

    public function exportEmployeeAttendanceProfile(Request $request): StreamedResponse
    {
        $employee = Employee::with(['branch', 'department', 'position', 'company', 'defaultShiftTemplate'])->findOrFail($request->employee_id);
        $records = AttendanceRecord::where('employee_id', $employee->id)->orderBy('date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="employee_attendance_profile_' . $employee->employee_id . '.csv"',
        ];

        return response()->stream(function () use ($records, $employee) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Employee Attendance Profile', $employee->full_name]);
            fputcsv($handle, ['Employee ID', $employee->employee_id]);
            fputcsv($handle, ['Company / Agency', $employee->company_or_agency]);
            fputcsv($handle, ['Branch', $employee->branch?->name]);
            fputcsv($handle, ['Department', $employee->department?->name]);
            fputcsv($handle, ['Default Shift', $employee->defaultShiftTemplate?->name ?? 'None']);
            fputcsv($handle, []);
            fputcsv($handle, ['Date', 'Time In', 'Time Out', 'Total Hours', 'Late (Mins)', 'Undertime (Mins)', 'Overtime (Hrs)', 'OT Status', 'UT Status', 'Status']);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date?->toDateString(),
                    $r->time_in,
                    $r->time_out,
                    $r->total_hours,
                    $r->late_minutes,
                    $r->undertime_minutes,
                    $r->overtime_hours,
                    $r->overtime_status,
                    $r->undertime_status,
                    $r->status,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
