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
use App\Models\Hr\Position;
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
     * Get common filter options for reports (branches, departments, companies, employees, positions).
     */
    protected function getFilterOptions(): array
    {
        return [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'employee_id']),
            'positions' => Position::orderBy('name')->get(),
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
        $branchIds = [];
        $deptIds = [];
        $posIds = [];
        $empIds = [];
        $empCodeIds = [];
        $empNames = [];
        $statuses = [];
        $companyIds = [];
        $searchTerms = [];

        $includeAllEmployees = false;

        // 1. Parse structured filter_tags JSON if present
        if ($request->filled('filter_tags')) {
            $tags = is_string($request->filter_tags) ? json_decode($request->filter_tags, true) : (is_array($request->filter_tags) ? $request->filter_tags : []);
            if (is_array($tags)) {
                foreach ($tags as $t) {
                    $type = strtolower($t['type'] ?? '');
                    $val = trim($t['value'] ?? '');
                    $id = $t['id'] ?? null;
                    $empid = $t['empid'] ?? null;

                    if ($type === 'branch') {
                        if ($id) {
                            $branchIds[] = (int) $id;
                        } elseif ($val !== '') {
                            $bMatch = Branch::where('name', 'like', "%{$val}%")->orWhere('code', 'like', "%{$val}%")->pluck('id')->toArray();
                            $branchIds = array_merge($branchIds, $bMatch);
                        }
                    } elseif ($type === 'department') {
                        if ($id) {
                            $deptIds[] = (int) $id;
                        } elseif ($val !== '') {
                            $dMatch = Department::where('name', 'like', "%{$val}%")->pluck('id')->toArray();
                            $deptIds = array_merge($deptIds, $dMatch);
                        }
                    } elseif ($type === 'position') {
                        if ($id) {
                            $posIds[] = (int) $id;
                        } elseif ($val !== '') {
                            $pMatch = Position::where('name', 'like', "%{$val}%")->pluck('id')->toArray();
                            $posIds = array_merge($posIds, $pMatch);
                        }
                    } elseif ($type === 'scope' || $type === 'all') {
                        if (strcasecmp($val, 'ALL') === 0 || strcasecmp($val, 'All Employees') === 0) {
                            $includeAllEmployees = true;
                        }
                    } elseif ($type === 'employee') {
                        if (strcasecmp($val, 'All Employees') === 0 || strcasecmp($val, 'ALL') === 0) {
                            $includeAllEmployees = true;
                        } else {
                            if ($id) {
                                $empIds[] = (int) $id;
                            }
                            if ($empid) {
                                $empCodeIds[] = (string) $empid;
                            }
                            if ($val !== '') {
                                $empNames[] = $val;
                            }
                        }
                    } elseif ($type === 'employee_id') {
                        if ($val !== '') {
                            $empCodeIds[] = (string) $val;
                        }
                    } elseif ($type === 'status') {
                        if ($val !== '') {
                            if (strcasecmp($val, 'All Active') === 0 || strcasecmp($val, 'ACTIVE_ALL') === 0 || strcasecmp($val, 'Active Staff Only') === 0) {
                                $statuses[] = 'ACTIVE_ALL';
                            } elseif (strcasecmp($val, 'All Employees') === 0 || strcasecmp($val, 'ALL') === 0) {
                                $includeAllEmployees = true;
                            } else {
                                $statuses[] = $val;
                            }
                        }
                    } elseif ($type === 'company') {
                        if ($id) {
                            $companyIds[] = (int) $id;
                        } elseif ($val !== '') {
                            $cMatch = Company::where('name', 'like', "%{$val}%")->pluck('id')->toArray();
                            $companyIds = array_merge($companyIds, $cMatch);
                        }
                    } elseif ($type === 'search') {
                        if ($val !== '') {
                            $searchTerms[] = $val;
                        }
                    }
                }
            }
        }

        // 2. Fallbacks & additions from URL query arrays or single parameters
        // Branches
        if ($request->has('branches')) {
            $bInputs = (array) $request->input('branches');
            foreach ($bInputs as $b) {
                if (is_numeric($b)) {
                    $branchIds[] = (int) $b;
                } else {
                    $bMatch = Branch::where('name', 'like', "%{$b}%")->pluck('id')->toArray();
                    $branchIds = array_merge($branchIds, $bMatch);
                }
            }
        }
        if ($request->filled('branch_id')) {
            $branchIds[] = (int) $request->branch_id;
        }

        // Departments
        if ($request->has('departments')) {
            $dInputs = (array) $request->input('departments');
            foreach ($dInputs as $d) {
                if (is_numeric($d)) {
                    $deptIds[] = (int) $d;
                } else {
                    $dMatch = Department::where('name', 'like', "%{$d}%")->pluck('id')->toArray();
                    $deptIds = array_merge($deptIds, $dMatch);
                }
            }
        }
        if ($request->filled('department_id')) {
            $deptIds[] = (int) $request->department_id;
        }

        // Positions
        if ($request->has('positions')) {
            $pInputs = (array) $request->input('positions');
            foreach ($pInputs as $p) {
                if (is_numeric($p)) {
                    $posIds[] = (int) $p;
                } else {
                    $pMatch = Position::where('name', 'like', "%{$p}%")->pluck('id')->toArray();
                    $posIds = array_merge($posIds, $pMatch);
                }
            }
        }
        if ($request->filled('position_id')) {
            $posIds[] = (int) $request->position_id;
        }

        // Employees & Employee IDs
        if ($request->has('employees')) {
            $eInputs = (array) $request->input('employees');
            foreach ($eInputs as $e) {
                if (is_numeric($e)) {
                    $empIds[] = (int) $e;
                } else {
                    $empNames[] = $e;
                }
            }
        }
        if ($request->has('employee_ids')) {
            $empCodeIds = array_merge($empCodeIds, (array) $request->input('employee_ids'));
        }
        if ($request->filled('employee_id')) {
            if (is_numeric($request->employee_id)) {
                $empIds[] = (int) $request->employee_id;
            } else {
                $empCodeIds[] = (string) $request->employee_id;
            }
        }

        // Statuses
        if ($request->has('statuses')) {
            $statuses = array_merge($statuses, (array) $request->input('statuses'));
        }
        if ($request->filled('employment_status')) {
            $statuses[] = $request->employment_status;
        }
        if ($request->filled('status')) {
            $statuses[] = $request->status;
        }
        if ($request->has('all_employees') || $request->input('scope') === 'ALL' || in_array('ALL', $statuses) || in_array('All Employees', $statuses)) {
            $includeAllEmployees = true;
        }

        // Companies
        if ($request->filled('company_id')) {
            $companyIds[] = (int) $request->company_id;
        }

        // General search
        if ($request->filled('search')) {
            $searchTerms[] = $request->search;
        }

        $branchIds = array_unique(array_filter($branchIds));
        $deptIds = array_unique(array_filter($deptIds));
        $posIds = array_unique(array_filter($posIds));
        $empIds = array_unique(array_filter($empIds));
        $empCodeIds = array_unique(array_filter($empCodeIds));
        
        // Remove pseudo-names like 'all', 'all employees', 'all active'
        $empNames = array_unique(array_filter($empNames, function($n) {
            return !in_array(strtolower(trim($n)), ['all', 'all employees', 'active staff only', 'all active']);
        }));

        $statuses = array_unique(array_filter($statuses));
        if ($includeAllEmployees) {
            $statuses = array_values(array_filter($statuses, fn($s) => !in_array(strtoupper($s), ['ALL', 'ALL EMPLOYEES'])));
        }
        $companyIds = array_unique(array_filter($companyIds));
        $searchTerms = array_unique(array_filter($searchTerms));

        // 3. Apply Branches Filter (Supports multiple branches simultaneously)
        if (!empty($branchIds)) {
            if ($isDirectEmployee) {
                $query->where(function ($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                    foreach ($branchIds as $bId) {
                        $q->orWhereJsonContains('assigned_branch_ids', (int) $bId)
                          ->orWhereJsonContains('assigned_branch_ids', (string) $bId);
                    }
                });
            } else {
                $table = $query->getModel()->getTable();
                $query->where(function ($q) use ($branchIds, $employeeRelation, $table) {
                    if (Schema::hasColumn($table, 'branch_id')) {
                        $q->whereIn('branch_id', $branchIds);
                    }
                    $q->orWhereHas($employeeRelation, function ($eq) use ($branchIds) {
                        $eq->whereIn('branch_id', $branchIds);
                        foreach ($branchIds as $bId) {
                            $eq->orWhereJsonContains('assigned_branch_ids', (int) $bId)
                               ->orWhereJsonContains('assigned_branch_ids', (string) $bId);
                        }
                    });
                });
            }
        }

        // 4. Apply Departments Filter (Supports multiple departments simultaneously)
        if (!empty($deptIds)) {
            if ($isDirectEmployee) {
                $query->where(function ($q) use ($deptIds) {
                    $q->whereIn('department_id', $deptIds);
                    foreach ($deptIds as $dId) {
                        $q->orWhereJsonContains('assigned_department_ids', (int) $dId)
                          ->orWhereJsonContains('assigned_department_ids', (string) $dId);
                    }
                });
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($deptIds) {
                    $q->whereIn('department_id', $deptIds);
                    foreach ($deptIds as $dId) {
                        $q->orWhereJsonContains('assigned_department_ids', (int) $dId)
                          ->orWhereJsonContains('assigned_department_ids', (string) $dId);
                    }
                });
            }
        }

        // 5. Apply Positions Filter (Supports multiple positions simultaneously)
        if (!empty($posIds)) {
            if ($isDirectEmployee) {
                $query->where(function ($q) use ($posIds) {
                    $q->whereIn('position_id', $posIds);
                    foreach ($posIds as $pId) {
                        $q->orWhereJsonContains('assigned_position_ids', (int) $pId)
                          ->orWhereJsonContains('assigned_position_ids', (string) $pId);
                    }
                });
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($posIds) {
                    $q->whereIn('position_id', $posIds);
                    foreach ($posIds as $pId) {
                        $q->orWhereJsonContains('assigned_position_ids', (int) $pId)
                          ->orWhereJsonContains('assigned_position_ids', (string) $pId);
                    }
                });
            }
        }

        // 6. Apply Employees / Employee IDs Filter
        if (!empty($empIds) || !empty($empNames) || !empty($empCodeIds)) {
            $empClosure = function ($q) use ($empIds, $empNames, $empCodeIds) {
                $q->where(function ($sub) use ($empIds, $empNames, $empCodeIds) {
                    if (!empty($empIds)) {
                        $sub->whereIn('id', $empIds);
                    }
                    if (!empty($empCodeIds)) {
                        $sub->orWhereIn('employee_id', $empCodeIds);
                    }
                    foreach ($empNames as $name) {
                        $sub->orWhere('first_name', 'like', "%{$name}%")
                            ->orWhere('last_name', 'like', "%{$name}%")
                            ->orWhere(DB::raw("CONCAT(employee_first_name, ' ', employee_last_name)"), 'like', "%{$name}%");
                    }
                });
            };

            if ($isDirectEmployee) {
                $empClosure($query);
            } else {
                $query->whereHas($employeeRelation, $empClosure);
            }
        }

        // 7. Apply Companies Filter
        if (!empty($companyIds)) {
            if ($isDirectEmployee) {
                $query->whereIn('company_id', $companyIds);
            } else {
                $query->whereHas($employeeRelation, function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds);
                });
            }
        }

        // 8. Apply Statuses Filter
        if (!empty($statuses) && !$includeAllEmployees) {
            $statusClosure = function ($q) use ($statuses) {
                $q->where(function ($sub) use ($statuses) {
                    foreach ($statuses as $st) {
                        if ($st === 'ACTIVE_ALL' || strcasecmp($st, 'All Active') === 0 || strcasecmp($st, 'Active Staff Only') === 0) {
                            $sub->orWhereNotIn('employment_status', ['Resigned', 'Terminated', 'Retired', 'Inactive']);
                        } elseif ($st === 'INACTIVE_ALL') {
                            $sub->orWhereIn('employment_status', ['Resigned', 'Terminated', 'Retired', 'Inactive']);
                        } elseif ($st !== 'ALL' && strcasecmp($st, 'All Employees') !== 0) {
                            $sub->orWhere('employment_status', $st);
                        }
                    }
                });
            };

            if ($isDirectEmployee) {
                $statusClosure($query);
            } else {
                $table = $query->getModel()->getTable();
                $hasCustomStatus = false;
                $query->where(function ($q) use ($statuses, $employeeRelation, $statusClosure, $table, &$hasCustomStatus) {
                    if (Schema::hasColumn($table, 'overtime_status')) {
                        $q->whereIn('overtime_status', $statuses);
                        $hasCustomStatus = true;
                    } elseif (Schema::hasColumn($table, 'undertime_status')) {
                        $q->whereIn('undertime_status', $statuses);
                        $hasCustomStatus = true;
                    } elseif (Schema::hasColumn($table, 'status')) {
                        $q->whereIn('status', $statuses);
                        $hasCustomStatus = true;
                    }
                    if ($hasCustomStatus) {
                        $q->orWhereHas($employeeRelation, $statusClosure);
                    } else {
                        $q->whereHas($employeeRelation, $statusClosure);
                    }
                });
            }
        }

        // 9. Employment Source Filter (Direct vs Agency)
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

        // 10. General Search Terms
        if (!empty($searchTerms)) {
            foreach ($searchTerms as $search) {
                $search = trim($search);
                if ($search === '') continue;
                if ($isDirectEmployee) {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('employee_id', 'like', "%{$search}%")
                          ->orWhere(DB::raw("CONCAT(employee_first_name, ' ', employee_last_name)"), 'like', "%{$search}%")
                          ->orWhereHas('position', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('branch', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
                    });
                } else {
                    $query->whereHas($employeeRelation, function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('employee_id', 'like', "%{$search}%")
                          ->orWhere(DB::raw("CONCAT(employee_first_name, ' ', employee_last_name)"), 'like', "%{$search}%")
                          ->orWhereHas('position', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('branch', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
                    });
                }
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
     * Export Employee Masterlist to CSV or Formatted Excel
     */
    public function exportEmployees(Request $request): StreamedResponse
    {
        if ($request->get('format') === 'excel' || $request->get('format') === 'xls') {
            return $this->exportEmployeesExcel($request);
        }

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
                $empCode = $e->employee_code ?: ('EMP-' . str_pad($e->employee_id, 4, '0', STR_PAD_LEFT));
                $dateHired = $e->date_hired ? \Carbon\Carbon::parse($e->date_hired)->format('Y-m-d') : '';
                $salary = is_numeric($e->basic_salary) ? number_format((float)$e->basic_salary, 2, '.', '') : '0.00';

                fputcsv($handle, [
                    $empCode,
                    $e->full_name,
                    $e->company_or_agency,
                    $e->branch?->name,
                    $e->department?->name,
                    $e->position?->name,
                    $e->employment_status,
                    $e->employment_type,
                    $e->employment_source,
                    $dateHired,
                    $salary,
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
     * Export Employee Masterlist to Formatted Excel (.xls / SpreadsheetML)
     */
    public function exportEmployeesExcel(Request $request): StreamedResponse
    {
        $query = Employee::with(['branch', 'department', 'position', 'company']);
        $this->applyEmployeeFilters($query, $request, true);
        $employees = $query->get();

        $filename = 'employee_masterlist_' . date('Ymd_His') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            $escape = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');

            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($handle, '<?mso-application progid="Excel.Sheet"?>' . "\n");
            fwrite($handle, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n");
            fwrite($handle, ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n");
            fwrite($handle, ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n");
            fwrite($handle, ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n");
            fwrite($handle, ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n");

            // Document Properties
            fwrite($handle, ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n");
            fwrite($handle, '  <Title>Employee Directory &amp; Masterlist</Title>' . "\n");
            fwrite($handle, '  <Subject>RMS Human Resources Management</Subject>' . "\n");
            fwrite($handle, '  <Author>RMS HR System</Author>' . "\n");
            fwrite($handle, '  <Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>' . "\n");
            fwrite($handle, '  <Company>RMS</Company>' . "\n");
            fwrite($handle, ' </DocumentProperties>' . "\n");

            // Styles
            fwrite($handle, ' <Styles>' . "\n");
            fwrite($handle, '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#1E293B"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#0F172A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#334155"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#334155"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/></Borders><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#FFFFFF" ss:Bold="1"/><Interior ss:Color="#0F172A" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="DataCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Vertical="Center"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="DataCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Vertical="Center"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="CenterCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="CenterCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="DateCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="yyyy-mm-dd"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="DateCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="yyyy-mm-dd"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="TextCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="@"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="TextCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="@"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="CurrencyCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="&quot;PHP &quot;#,##0.00;[Red]\(&quot;PHP &quot;#,##0.00\);&quot;PHP &quot;0.00"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="CurrencyCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="&quot;PHP &quot;#,##0.00;[Red]\(&quot;PHP &quot;#,##0.00\);&quot;PHP &quot;0.00"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");
            fwrite($handle, ' </Styles>' . "\n");

            // Worksheet
            fwrite($handle, ' <Worksheet ss:Name="Employee Masterlist">' . "\n");
            fwrite($handle, '  <Table ss:DefaultRowHeight="20">' . "\n");

            // Defined column widths
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Employee ID
            fwrite($handle, '   <Column ss:Width="175"/>' . "\n"); // Full Name
            fwrite($handle, '   <Column ss:Width="140"/>' . "\n"); // Company / Agency
            fwrite($handle, '   <Column ss:Width="130"/>' . "\n"); // Branch
            fwrite($handle, '   <Column ss:Width="130"/>' . "\n"); // Department
            fwrite($handle, '   <Column ss:Width="140"/>' . "\n"); // Position
            fwrite($handle, '   <Column ss:Width="90"/>' . "\n");  // Status
            fwrite($handle, '   <Column ss:Width="95"/>' . "\n");  // Type
            fwrite($handle, '   <Column ss:Width="90"/>' . "\n");  // Source
            fwrite($handle, '   <Column ss:Width="95"/>' . "\n");  // Date Hired
            fwrite($handle, '   <Column ss:Width="120"/>' . "\n"); // Basic Salary
            fwrite($handle, '   <Column ss:Width="100"/>' . "\n"); // Pay Frequency
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // SSS
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // PhilHealth
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Pag-IBIG
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // TIN
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Mobile
            fwrite($handle, '   <Column ss:Width="180"/>' . "\n"); // Email

            // Header row
            fwrite($handle, '   <Row ss:Height="26" ss:StyleID="Header">' . "\n");
            $headerColumns = [
                'Employee ID', 'Full Name', 'Company / Agency', 'Branch', 'Department', 'Position',
                'Status', 'Type', 'Source', 'Date Hired', 'Basic Salary', 'Pay Frequency',
                'SSS', 'PhilHealth', 'Pag-IBIG', 'TIN', 'Mobile', 'Email'
            ];
            foreach ($headerColumns as $h) {
                fwrite($handle, '    <Cell><Data ss:Type="String">' . $escape($h) . '</Data></Cell>' . "\n");
            }
            fwrite($handle, '   </Row>' . "\n");

            // Data rows
            $rowIndex = 0;
            foreach ($employees as $e) {
                $isZebra = ($rowIndex % 2 === 1);
                $dStyle = $isZebra ? 'DataCellZebra' : 'DataCell';
                $cStyle = $isZebra ? 'CenterCellZebra' : 'CenterCell';
                $dateStyle = $isZebra ? 'DateCellZebra' : 'DateCell';
                $textStyle = $isZebra ? 'TextCellZebra' : 'TextCell';
                $currStyle = $isZebra ? 'CurrencyCellZebra' : 'CurrencyCell';

                $empCode = $e->employee_code ?: ('EMP-' . str_pad($e->employee_id, 4, '0', STR_PAD_LEFT));
                $dateHired = $e->date_hired ? \Carbon\Carbon::parse($e->date_hired)->format('Y-m-d') : '';
                $salary = is_numeric($e->basic_salary) ? (float)$e->basic_salary : 0.00;

                fwrite($handle, '   <Row ss:Height="20">' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($empCode) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($e->full_name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($e->company_or_agency) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($e->branch?->name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($e->department?->name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($e->position?->name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($e->employment_status) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($e->employment_type) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($e->employment_source) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dateStyle . '"><Data ss:Type="String">' . $escape($dateHired) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . $salary . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($e->pay_frequency) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($e->sss_number) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($e->philhealth_number) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($e->pagibig_number) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($e->tin) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($e->mobile_number) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($e->email) . '</Data></Cell>' . "\n");
                fwrite($handle, '   </Row>' . "\n");

                $rowIndex++;
            }

            fwrite($handle, '  </Table>' . "\n");
            fwrite($handle, '  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n");
            fwrite($handle, '   <FreezePanes/>' . "\n");
            fwrite($handle, '   <FrozenNoSplit/>' . "\n");
            fwrite($handle, '   <SplitHorizontal>1</SplitHorizontal>' . "\n");
            fwrite($handle, '   <TopRowBottomPane>1</TopRowBottomPane>' . "\n");
            fwrite($handle, '   <ActivePane>2</ActivePane>' . "\n");
            fwrite($handle, '  </WorksheetOptions>' . "\n");
            fwrite($handle, ' </Worksheet>' . "\n");
            fwrite($handle, '</Workbook>' . "\n");

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export DTR to CSV or Formatted Excel
     */
    public function exportAttendance(Request $request): StreamedResponse
    {
        if ($request->get('format') === 'excel' || $request->get('format') === 'xls') {
            return $this->exportAttendanceExcel($request);
        }

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
                $empCode = $r->employee?->employee_code ?: ('EMP-' . str_pad($r->employee?->employee_id ?? 0, 4, '0', STR_PAD_LEFT));
                $date = $r->date ? \Carbon\Carbon::parse($r->date)->format('Y-m-d') : '';
                $timeIn = $r->time_in ? date('h:i A', strtotime($r->time_in)) : '';
                $timeOut = $r->time_out ? date('h:i A', strtotime($r->time_out)) : '';

                fputcsv($handle, [
                    $date,
                    $empCode,
                    $r->employee?->full_name,
                    $r->employee?->company_or_agency,
                    $r->employee?->branch?->name,
                    $timeIn,
                    $timeOut,
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
     * Export Attendance / DTR to Formatted Excel (.xls / SpreadsheetML)
     */
    public function exportAttendanceExcel(Request $request): StreamedResponse
    {
        $startDate = $request->get('start_date', $request->get('date_from', Carbon::now()->startOfMonth()->toDateString()));
        $endDate = $request->get('end_date', $request->get('date_to', Carbon::now()->toDateString()));

        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company'])
            ->whereBetween('date', [$startDate, $endDate]);

        $this->applyEmployeeFilters($query, $request, false);

        $records = $query->orderBy('date', 'asc')->get();

        $filename = 'attendance_dtr_' . date('Ymd_His') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            $escape = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');

            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($handle, '<?mso-application progid="Excel.Sheet"?>' . "\n");
            fwrite($handle, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n");
            fwrite($handle, ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n");
            fwrite($handle, ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n");
            fwrite($handle, ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n");
            fwrite($handle, ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n");

            // Document Properties
            fwrite($handle, ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n");
            fwrite($handle, '  <Title>Attendance &amp; DTR Time Log</Title>' . "\n");
            fwrite($handle, '  <Subject>RMS Timekeeping &amp; Attendance</Subject>' . "\n");
            fwrite($handle, '  <Author>RMS HR System</Author>' . "\n");
            fwrite($handle, '  <Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>' . "\n");
            fwrite($handle, '  <Company>RMS</Company>' . "\n");
            fwrite($handle, ' </DocumentProperties>' . "\n");

            // Styles
            fwrite($handle, ' <Styles>' . "\n");
            fwrite($handle, '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#1E293B"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#0F172A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#334155"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#334155"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/></Borders><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#FFFFFF" ss:Bold="1"/><Interior ss:Color="#0F172A" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="DataCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Vertical="Center"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="DataCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Vertical="Center"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="CenterCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="CenterCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="DateCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="yyyy-mm-dd"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="DateCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="yyyy-mm-dd"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="TextCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="@"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="TextCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="@"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="DecimalCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="0.00"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="DecimalCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="0.00"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="IntegerCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="#,##0"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="IntegerCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="#,##0"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");
            fwrite($handle, ' </Styles>' . "\n");

            // Worksheet
            fwrite($handle, ' <Worksheet ss:Name="Attendance DTR">' . "\n");
            fwrite($handle, '  <Table ss:DefaultRowHeight="20">' . "\n");

            // Column Widths
            fwrite($handle, '   <Column ss:Width="95"/>' . "\n");  // Date
            fwrite($handle, '   <Column ss:Width="115"/>' . "\n"); // Employee ID
            fwrite($handle, '   <Column ss:Width="175"/>' . "\n"); // Employee Name
            fwrite($handle, '   <Column ss:Width="140"/>' . "\n"); // Company / Agency
            fwrite($handle, '   <Column ss:Width="130"/>' . "\n"); // Branch
            fwrite($handle, '   <Column ss:Width="85"/>' . "\n");  // Time In
            fwrite($handle, '   <Column ss:Width="85"/>' . "\n");  // Time Out
            fwrite($handle, '   <Column ss:Width="90"/>' . "\n");  // Total Hours
            fwrite($handle, '   <Column ss:Width="85"/>' . "\n");  // Late (Min)
            fwrite($handle, '   <Column ss:Width="95"/>' . "\n");  // Undertime (Min)
            fwrite($handle, '   <Column ss:Width="95"/>' . "\n");  // Overtime (Hrs)
            fwrite($handle, '   <Column ss:Width="95"/>' . "\n");  // Night Diff (Hrs)
            fwrite($handle, '   <Column ss:Width="90"/>' . "\n");  // Status

            // Header Row
            fwrite($handle, '   <Row ss:Height="26" ss:StyleID="Header">' . "\n");
            $headers = [
                'Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Time In', 'Time Out',
                'Total Hours', 'Late (Min)', 'Undertime (Min)', 'Overtime (Hrs)', 'Night Diff (Hrs)', 'Status'
            ];
            foreach ($headers as $h) {
                fwrite($handle, '    <Cell><Data ss:Type="String">' . $escape($h) . '</Data></Cell>' . "\n");
            }
            fwrite($handle, '   </Row>' . "\n");

            // Data Rows
            $rowIndex = 0;
            foreach ($records as $r) {
                $isZebra = ($rowIndex % 2 === 1);
                $dStyle = $isZebra ? 'DataCellZebra' : 'DataCell';
                $cStyle = $isZebra ? 'CenterCellZebra' : 'CenterCell';
                $dateStyle = $isZebra ? 'DateCellZebra' : 'DateCell';
                $textStyle = $isZebra ? 'TextCellZebra' : 'TextCell';
                $decStyle = $isZebra ? 'DecimalCellZebra' : 'DecimalCell';
                $intStyle = $isZebra ? 'IntegerCellZebra' : 'IntegerCell';

                $empCode = $r->employee?->employee_code ?: ('EMP-' . str_pad($r->employee?->employee_id ?? 0, 4, '0', STR_PAD_LEFT));
                $date = $r->date ? \Carbon\Carbon::parse($r->date)->format('Y-m-d') : '';
                $timeIn = $r->time_in ? date('h:i A', strtotime($r->time_in)) : '—';
                $timeOut = $r->time_out ? date('h:i A', strtotime($r->time_out)) : '—';

                fwrite($handle, '   <Row ss:Height="20">' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dateStyle . '"><Data ss:Type="String">' . $escape($date) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($empCode) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($r->employee?->full_name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($r->employee?->company_or_agency) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($r->employee?->branch?->name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($timeIn) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($timeOut) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $decStyle . '"><Data ss:Type="Number">' . (float)$r->total_hours . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $intStyle . '"><Data ss:Type="Number">' . (int)$r->late_minutes . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $intStyle . '"><Data ss:Type="Number">' . (int)$r->undertime_minutes . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $decStyle . '"><Data ss:Type="Number">' . (float)$r->overtime_hours . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $decStyle . '"><Data ss:Type="Number">' . (float)$r->night_diff_hours . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $escape($r->status) . '</Data></Cell>' . "\n");
                fwrite($handle, '   </Row>' . "\n");

                $rowIndex++;
            }

            fwrite($handle, '  </Table>' . "\n");
            fwrite($handle, '  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n");
            fwrite($handle, '   <FreezePanes/>' . "\n");
            fwrite($handle, '   <FrozenNoSplit/>' . "\n");
            fwrite($handle, '   <SplitHorizontal>1</SplitHorizontal>' . "\n");
            fwrite($handle, '   <TopRowBottomPane>1</TopRowBottomPane>' . "\n");
            fwrite($handle, '   <ActivePane>2</ActivePane>' . "\n");
            fwrite($handle, '  </WorksheetOptions>' . "\n");
            fwrite($handle, ' </Worksheet>' . "\n");
            fwrite($handle, '</Workbook>' . "\n");

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Payroll Register to CSV or Formatted Excel
     */
    public function exportPayroll(Request $request): StreamedResponse
    {
        if ($request->get('format') === 'excel' || $request->get('format') === 'xls') {
            return $this->exportPayrollExcel($request);
        }

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
                $empCode = $p->employee?->employee_code ?: ('EMP-' . str_pad($p->employee?->employee_id ?? 0, 4, '0', STR_PAD_LEFT));
                $lateAbsence = (float)($p->late_deduction + $p->undertime_deduction + $p->absence_deduction);

                fputcsv($handle, [
                    $p->payrollPeriod?->name,
                    $empCode,
                    $p->employee?->full_name,
                    $p->employee?->company_or_agency,
                    $p->employee?->branch?->name,
                    number_format((float)$p->basic_pay, 2, '.', ''),
                    number_format((float)$p->overtime_pay, 2, '.', ''),
                    number_format((float)$p->night_diff_pay, 2, '.', ''),
                    number_format((float)$p->holiday_pay, 2, '.', ''),
                    number_format((float)$p->allowances, 2, '.', ''),
                    number_format((float)$p->gross_pay, 2, '.', ''),
                    number_format($lateAbsence, 2, '.', ''),
                    number_format((float)$p->sss_employee, 2, '.', ''),
                    number_format((float)$p->philhealth_employee, 2, '.', ''),
                    number_format((float)$p->pagibig_employee, 2, '.', ''),
                    number_format((float)$p->withholding_tax, 2, '.', ''),
                    number_format((float)$p->total_deductions, 2, '.', ''),
                    number_format((float)$p->net_pay, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Payroll Register to Formatted Excel (.xls / SpreadsheetML)
     */
    public function exportPayrollExcel(Request $request): StreamedResponse
    {
        $periodId = $request->get('payroll_period_id');
        $query = PayrollRecord::with(['employee.branch', 'employee.department', 'employee.company', 'payrollPeriod']);
        if ($periodId) {
            $query->where('payroll_period_id', $periodId);
        }
        $this->applyEmployeeFilters($query, $request, false);
        $records = $query->get();

        $filename = 'payroll_register_' . date('Ymd_His') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            $escape = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');

            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($handle, '<?mso-application progid="Excel.Sheet"?>' . "\n");
            fwrite($handle, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n");
            fwrite($handle, ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n");
            fwrite($handle, ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n");
            fwrite($handle, ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n");
            fwrite($handle, ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n");

            // Document Properties
            fwrite($handle, ' <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n");
            fwrite($handle, '  <Title>Payroll Register &amp; Statutory Summary</Title>' . "\n");
            fwrite($handle, '  <Subject>RMS Compensation &amp; Payroll</Subject>' . "\n");
            fwrite($handle, '  <Author>RMS HR System</Author>' . "\n");
            fwrite($handle, '  <Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>' . "\n");
            fwrite($handle, '  <Company>RMS</Company>' . "\n");
            fwrite($handle, ' </DocumentProperties>' . "\n");

            // Styles
            fwrite($handle, ' <Styles>' . "\n");
            fwrite($handle, '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#1E293B"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#0F172A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#334155"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#334155"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/></Borders><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#FFFFFF" ss:Bold="1"/><Interior ss:Color="#0F172A" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="DataCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Vertical="Center"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="DataCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Vertical="Center"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="TextCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="@"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="TextCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="@"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="CurrencyCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="&quot;PHP &quot;#,##0.00;[Red]\(&quot;PHP &quot;#,##0.00\);&quot;PHP &quot;0.00"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="CurrencyCellZebra"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><NumberFormat ss:Format="&quot;PHP &quot;#,##0.00;[Red]\(&quot;PHP &quot;#,##0.00\);&quot;PHP &quot;0.00"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>' . "\n");

            fwrite($handle, '  <Style ss:ID="CurrencyGross"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#0F172A" ss:Bold="1"/><NumberFormat ss:Format="&quot;PHP &quot;#,##0.00;[Red]\(&quot;PHP &quot;#,##0.00\);&quot;PHP &quot;0.00"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/></Style>' . "\n");
            fwrite($handle, '  <Style ss:ID="CurrencyNet"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#059669"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" x:Family="Swiss" ss:Size="10" ss:Color="#047857" ss:Bold="1"/><NumberFormat ss:Format="&quot;PHP &quot;#,##0.00;[Red]\(&quot;PHP &quot;#,##0.00\);&quot;PHP &quot;0.00"/><Interior ss:Color="#ECFDF5" ss:Pattern="Solid"/></Style>' . "\n");
            fwrite($handle, ' </Styles>' . "\n");

            // Worksheet
            fwrite($handle, ' <Worksheet ss:Name="Payroll Register">' . "\n");
            fwrite($handle, '  <Table ss:DefaultRowHeight="20">' . "\n");

            // Column Widths
            fwrite($handle, '   <Column ss:Width="130"/>' . "\n"); // Period
            fwrite($handle, '   <Column ss:Width="115"/>' . "\n"); // Employee ID
            fwrite($handle, '   <Column ss:Width="175"/>' . "\n"); // Employee Name
            fwrite($handle, '   <Column ss:Width="140"/>' . "\n"); // Company / Agency
            fwrite($handle, '   <Column ss:Width="130"/>' . "\n"); // Branch
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Basic Pay
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Overtime Pay
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Night Diff Pay
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Holiday Pay
            fwrite($handle, '   <Column ss:Width="110"/>' . "\n"); // Allowances
            fwrite($handle, '   <Column ss:Width="120"/>' . "\n"); // Gross Pay
            fwrite($handle, '   <Column ss:Width="120"/>' . "\n"); // Late/Absence Ded
            fwrite($handle, '   <Column ss:Width="105"/>' . "\n"); // SSS (EE)
            fwrite($handle, '   <Column ss:Width="105"/>' . "\n"); // PhilHealth (EE)
            fwrite($handle, '   <Column ss:Width="105"/>' . "\n"); // Pag-IBIG (EE)
            fwrite($handle, '   <Column ss:Width="115"/>' . "\n"); // Withholding Tax
            fwrite($handle, '   <Column ss:Width="120"/>' . "\n"); // Total Deductions
            fwrite($handle, '   <Column ss:Width="125"/>' . "\n"); // Net Pay

            // Header Row
            fwrite($handle, '   <Row ss:Height="26" ss:StyleID="Header">' . "\n");
            $headers = [
                'Period', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Basic Pay',
                'Overtime Pay', 'Night Diff Pay', 'Holiday Pay', 'Allowances', 'Gross Pay',
                'Late/Absence Ded', 'SSS (EE)', 'PhilHealth (EE)', 'Pag-IBIG (EE)',
                'Withholding Tax', 'Total Deductions', 'Net Pay'
            ];
            foreach ($headers as $h) {
                fwrite($handle, '    <Cell><Data ss:Type="String">' . $escape($h) . '</Data></Cell>' . "\n");
            }
            fwrite($handle, '   </Row>' . "\n");

            // Data Rows
            $rowIndex = 0;
            foreach ($records as $p) {
                $isZebra = ($rowIndex % 2 === 1);
                $dStyle = $isZebra ? 'DataCellZebra' : 'DataCell';
                $textStyle = $isZebra ? 'TextCellZebra' : 'TextCell';
                $currStyle = $isZebra ? 'CurrencyCellZebra' : 'CurrencyCell';

                $empCode = $p->employee?->employee_code ?: ('EMP-' . str_pad($p->employee?->employee_id ?? 0, 4, '0', STR_PAD_LEFT));
                $periodName = $p->payrollPeriod?->name ?: '—';
                $lateAbsence = (float)($p->late_deduction + $p->undertime_deduction + $p->absence_deduction);

                fwrite($handle, '   <Row ss:Height="20">' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($periodName) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $textStyle . '"><Data ss:Type="String">' . $escape($empCode) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($p->employee?->full_name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($p->employee?->company_or_agency) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $dStyle . '"><Data ss:Type="String">' . $escape($p->employee?->branch?->name) . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->basic_pay . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->overtime_pay . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->night_diff_pay . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->holiday_pay . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->allowances . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="CurrencyGross"><Data ss:Type="Number">' . (float)$p->gross_pay . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . $lateAbsence . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->sss_employee . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->philhealth_employee . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->pagibig_employee . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->withholding_tax . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="' . $currStyle . '"><Data ss:Type="Number">' . (float)$p->total_deductions . '</Data></Cell>' . "\n");
                fwrite($handle, '    <Cell ss:StyleID="CurrencyNet"><Data ss:Type="Number">' . (float)$p->net_pay . '</Data></Cell>' . "\n");
                fwrite($handle, '   </Row>' . "\n");

                $rowIndex++;
            }

            fwrite($handle, '  </Table>' . "\n");
            fwrite($handle, '  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' . "\n");
            fwrite($handle, '   <FreezePanes/>' . "\n");
            fwrite($handle, '   <FrozenNoSplit/>' . "\n");
            fwrite($handle, '   <SplitHorizontal>1</SplitHorizontal>' . "\n");
            fwrite($handle, '   <TopRowBottomPane>1</TopRowBottomPane>' . "\n");
            fwrite($handle, '   <ActivePane>2</ActivePane>' . "\n");
            fwrite($handle, '  </WorksheetOptions>' . "\n");
            fwrite($handle, ' </Worksheet>' . "\n");
            fwrite($handle, '</Workbook>' . "\n");

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
    // 4. AUTHORIZED UNDERTIME REPORT (APPROVED / EXCUSED)
    // =========================================================================

    public function authorizedUndertimeReport(Request $request): View
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0)
            ->where('undertime_status', 'Approved')
            ->orderBy('date', 'desc');

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        // Summary KPI Metrics
        $statsQuery = clone $query;
        $totalRecords = $statsQuery->count();
        $totalMinutes = (int) $statsQuery->sum('undertime_minutes');
        $totalStaff = (int) $statsQuery->distinct('employee_id')->count('employee_id');
        $avgMinutes = $totalRecords > 0 ? round($totalMinutes / $totalRecords, 1) : 0;

        $records = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.authorized-undertime', array_merge([
            'records' => $records,
            'totalRecords' => $totalRecords,
            'totalMinutes' => $totalMinutes,
            'totalStaff' => $totalStaff,
            'avgMinutes' => $avgMinutes,
        ], $filterOpts));
    }

    public function exportAuthorizedUndertime(Request $request): StreamedResponse
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0)
            ->where('undertime_status', 'Approved')
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
            'Content-Disposition' => 'attachment; filename="authorized_undertime_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Time In', 'Time Out', 'Authorized Undertime (Mins)', 'Total Work Hours', 'Status', 'Authorized By', 'Approval Date', 'Gate Pass / Remarks']);

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
                    'Authorized Undertime',
                    $r->undertimeApprover?->name,
                    $r->undertime_approved_at ? date('Y-m-d H:i', strtotime($r->undertime_approved_at)) : '',
                    $r->undertime_remarks ?: 'Excused by Management',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    // =========================================================================
    // 4B. UNAUTHORIZED UNDERTIME REPORT (UNEXCUSED / DEDUCTIBLE)
    // =========================================================================

    public function unauthorizedUndertimeReport(Request $request): View
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.position', 'employee.company', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0)
            ->where(function ($q) {
                $q->where('undertime_status', 'Rejected')
                  ->orWhere('undertime_status', 'Pending')
                  ->orWhereNull('undertime_status');
            })
            ->orderBy('date', 'desc');

        $status = $request->get('status');
        if ($status === 'Rejected') {
            $query->where('undertime_status', 'Rejected');
        } elseif ($status === 'Pending') {
            $query->where(function ($q) {
                $q->where('undertime_status', 'Pending')->orWhereNull('undertime_status');
            });
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $this->applyEmployeeFilters($query, $request, false);

        // Summary KPI Metrics
        $statsQuery = clone $query;
        $totalRecords = $statsQuery->count();
        $totalMinutes = (int) $statsQuery->sum('undertime_minutes');
        $totalStaff = (int) $statsQuery->distinct('employee_id')->count('employee_id');
        $unexcusedCount = (clone $statsQuery)->where('undertime_status', 'Rejected')->count();
        $pendingCount = (clone $statsQuery)->where(function($q) {
            $q->where('undertime_status', 'Pending')->orWhereNull('undertime_status');
        })->count();

        $records = $query->paginate(20)->withQueryString();
        $filterOpts = $this->getFilterOptions();

        return view('hr.reports.unauthorized-undertime', array_merge([
            'records' => $records,
            'status' => $status,
            'totalRecords' => $totalRecords,
            'totalMinutes' => $totalMinutes,
            'totalStaff' => $totalStaff,
            'unexcusedCount' => $unexcusedCount,
            'pendingCount' => $pendingCount,
        ], $filterOpts));
    }

    public function exportUnauthorizedUndertime(Request $request): StreamedResponse
    {
        $query = AttendanceRecord::with(['employee.branch', 'employee.department', 'employee.company', 'undertimeApprover'])
            ->where('undertime_minutes', '>', 0)
            ->where(function ($q) {
                $q->where('undertime_status', 'Rejected')
                  ->orWhere('undertime_status', 'Pending')
                  ->orWhereNull('undertime_status');
            })
            ->orderBy('date', 'desc');

        $status = $request->get('status');
        if ($status === 'Rejected') {
            $query->where('undertime_status', 'Rejected');
        } elseif ($status === 'Pending') {
            $query->where(function ($q) {
                $q->where('undertime_status', 'Pending')->orWhereNull('undertime_status');
            });
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
            'Content-Disposition' => 'attachment; filename="unauthorized_undertime_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Employee ID', 'Employee Name', 'Company / Agency', 'Branch', 'Department', 'Time In', 'Time Out', 'Unauthorized Undertime (Mins)', 'Total Work Hours', 'Infraction Status', 'Payroll Deduction Status', 'Remarks / Reason']);

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
                    $r->undertime_status === 'Rejected' ? 'Unauthorized / Unexcused' : 'Unapproved (Pending Review)',
                    'Deductible from Payroll',
                    $r->undertime_remarks ?: 'Unapproved Early Departure',
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
            'attendance_record_employee_id as employee_id',
            DB::raw('count(*) as occurrence_count'),
            DB::raw('sum(attendance_record_late_minutes) as total_late_minutes'),
            DB::raw('avg(attendance_record_late_minutes) as avg_late_minutes')
        )
        ->groupBy('attendance_record_employee_id')
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
            'attendance_record_employee_id',
            DB::raw('count(*) as logged_days'),
            DB::raw('sum(CASE WHEN attendance_record_status = "Present" OR attendance_record_regular_hours > 0 THEN 1 ELSE 0 END) as present_days'),
            DB::raw('sum(CASE WHEN attendance_record_is_rest_day = 1 THEN 1 ELSE 0 END) as rest_days'),
            DB::raw('sum(CASE WHEN attendance_record_late_minutes > 0 THEN 1 ELSE 0 END) as late_occurrences'),
            DB::raw('sum(attendance_record_late_minutes) as total_late_minutes'),
            DB::raw('sum(attendance_record_undertime_minutes) as total_undertime_minutes'),
            DB::raw('sum(attendance_record_overtime_hours) as total_overtime_hours'),
            DB::raw('sum(attendance_record_absence_days) as total_absences'),
            DB::raw('sum(attendance_record_total_hours) as total_hours')
        )
        ->groupBy('attendance_record_employee_id')
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
            'attendance_record_employee_id',
            DB::raw('count(*) as logged_days'),
            DB::raw('sum(CASE WHEN attendance_record_status = "Present" OR attendance_record_regular_hours > 0 THEN 1 ELSE 0 END) as present_days'),
            DB::raw('sum(CASE WHEN attendance_record_is_rest_day = 1 THEN 1 ELSE 0 END) as rest_days'),
            DB::raw('sum(CASE WHEN attendance_record_late_minutes > 0 THEN 1 ELSE 0 END) as late_occurrences'),
            DB::raw('sum(attendance_record_late_minutes) as total_late_minutes'),
            DB::raw('sum(attendance_record_undertime_minutes) as total_undertime_minutes'),
            DB::raw('sum(attendance_record_overtime_hours) as total_overtime_hours'),
            DB::raw('sum(attendance_record_absence_days) as total_absences'),
            DB::raw('sum(attendance_record_total_hours) as total_hours')
        )
        ->groupBy('attendance_record_employee_id')
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

        if (!$selectedEmpId && $employees->count() === 1) {
            $selectedEmpId = $employees->first()->id;
        }

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

        if (!$selectedEmpId && $employees->count() === 1) {
            $selectedEmpId = $employees->first()->id;
        }

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
