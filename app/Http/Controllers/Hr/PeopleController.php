<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Branch;
use App\Models\Hr\Company;
use App\Models\Hr\Department;
use App\Models\Hr\EmergencyContact;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDocument;
use App\Models\Hr\EmploymentHistory;
use App\Models\Hr\Position;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PeopleController extends Controller
{
    // ==========================================
    // 1. EMPLOYEES
    // ==========================================

    public function employeesIndex(Request $request): View
    {
        $user = Auth::user();
        $query = Employee::with(['branch', 'department', 'position', 'supervisor', 'user.roles', 'company']);

        // Branch scoping
        $baseQuery = Employee::query();
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $userBranchId = (int)$user->branch_id;
            $query->where(function ($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId)
                  ->orWhereJsonContains('assigned_branch_ids', $userBranchId);
            });
            $baseQuery->where(function ($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId)
                  ->orWhereJsonContains('assigned_branch_ids', $userBranchId);
            });
        } elseif ($request->filled('branch_id')) {
            $branchId = (int)$request->branch_id;
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereJsonContains('assigned_branch_ids', $branchId);
            });
        }

        // Summary Statistics Cards
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('employment_status', 'Active')->count(),
            'probationary' => (clone $baseQuery)->where('employment_status', 'Probationary')->count(),
            'on_leave' => (clone $baseQuery)->where('employment_status', 'On Leave')->count(),
            'separated' => (clone $baseQuery)->whereIn('employment_status', ['Resigned', 'Terminated', 'Retired'])->count(),
        ];

        if ($request->filled('department_id')) {
            $deptId = (int)$request->department_id;
            $query->where(function ($q) use ($deptId) {
                $q->where('department_id', $deptId)
                  ->orWhereJsonContains('assigned_department_ids', $deptId);
            });
        }

        if ($request->filled('position_id')) {
            $posId = (int)$request->position_id;
            $query->where(function ($q) use ($posId) {
                $q->where('position_id', $posId)
                  ->orWhereJsonContains('assigned_position_ids', $posId);
            });
        }

        if ($request->filled('company_id')) {
            $companyId = (int)$request->company_id;
            $comp = Company::find($companyId);
            $query->where(function ($q) use ($companyId, $comp) {
                $q->where('company_id', $companyId);
                if ($comp) {
                    $q->orWhere('company_name', $comp->name)
                      ->orWhere('agency_name', $comp->name)
                      ->orWhere('company_agency_name', $comp->name);
                }
            });
        } elseif ($request->filled('company_name')) {
            $cName = $request->company_name;
            $query->where(function ($q) use ($cName) {
                $q->where('company_name', $cName)
                  ->orWhere('agency_name', $cName)
                  ->orWhere('company_agency_name', $cName);
            });
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('employee_id', 'like', $term)
                  ->orWhere('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        // Active Filter Context for header alert/indicator
        $filterContext = null;
        if ($request->filled('department_id')) {
            $d = Department::find($request->department_id);
            if ($d) $filterContext = ['type' => 'Department', 'name' => $d->name, 'code' => $d->code, 'id' => $d->id];
        } elseif ($request->filled('position_id')) {
            $p = Position::find($request->position_id);
            if ($p) $filterContext = ['type' => 'Position', 'name' => $p->name, 'code' => $p->code, 'id' => $p->id];
        } elseif ($request->filled('branch_id')) {
            $b = Branch::find($request->branch_id);
            if ($b) $filterContext = ['type' => 'Branch', 'name' => $b->name, 'code' => $b->code, 'id' => $b->id];
        } elseif ($request->filled('company_id')) {
            $c = Company::find($request->company_id);
            if ($c) $filterContext = ['type' => $c->type ?? 'Company / Agency', 'name' => $c->name, 'code' => $c->code, 'id' => $c->id];
        }

        $perPage = (int) $request->get('per_page', 100);
        $employees = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $companies = Company::where('type', 'Company')->where('is_active', true)->orderBy('name')->get();
        $agencies = Company::where('type', 'Agency')->where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('full_name')->get();
        $supervisors = Employee::activeWorkforce()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_id']);
        $nextEmployeeId = Employee::generateNextEmployeeId();

        return view('hr.people.employees', compact('employees', 'branches', 'departments', 'positions', 'companies', 'agencies', 'users', 'supervisors', 'counts', 'filterContext', 'nextEmployeeId'));
    }

    public function employeeData(int $id): JsonResponse
    {
        $user = Auth::user();
        $employee = Employee::with(['branch', 'department', 'position', 'supervisor'])->findOrFail($id);
        if (!$user->canAccessBranch($employee->branch_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($employee);
    }

    public function employeeShow(int $id): View
    {
        $user = Auth::user();
        $employee = Employee::with([
            'branch.company', 'department', 'position', 'supervisor', 'user.roles', 'company',
            'emergencyContacts',
            'documents' => fn($q) => $q->orderBy('created_at', 'desc'),
            'employmentHistories' => fn($q) => $q->orderBy('effective_date', 'desc'),
            'leaveBalances.leaveType',
            'leaveRequests' => fn($q) => $q->with('leaveType')->orderBy('created_at', 'desc')->take(10),
            'attendanceRecords' => fn($q) => $q->orderBy('date', 'desc')->take(15),
            'payrollRecords' => fn($q) => $q->with('payrollPeriod')->orderBy('created_at', 'desc')->take(10),
            'evaluations' => fn($q) => $q->with('period')->orderBy('created_at', 'desc')->take(10),
        ])->findOrFail($id);

        if (!$user->canAccessBranch($employee->branch_id)) {
            abort(403, 'Unauthorized access to employee in another branch.');
        }

        $canViewSensitive = $user->isSuperAdmin() || $user->isHrAdmin() || $user->hasPermission('employees.sensitive');
        $companies = Company::where('type', 'Company')->where('is_active', true)->orderBy('name')->get();
        $agencies = Company::where('type', 'Agency')->where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $supervisors = Employee::where('id', '!=', $id)->activeWorkforce()->orderBy('first_name')->get();

        // 1. Quick Statistics
        $totalCredits = $employee->leaveBalances->sum('remaining_credits');
        $leaveBalance = $totalCredits > 0 ? number_format($totalCredits, 1) . ' days' : '15.0 days';

        $totalAtt = $employee->attendanceRecords->count();
        $presentAtt = $employee->attendanceRecords->whereIn('status', ['Present', 'Overtime'])->count();
        $attendanceRate = $totalAtt > 0 ? round(($presentAtt / $totalAtt) * 100, 1) . '%' : '98.5%';

        $verifiedDocs = $employee->documents->where('status', 'Verified')->count();
        $totalDocs = $employee->documents->count();
        $documentsVerified = "{$verifiedDocs} / {$totalDocs}";

        $currentSalary = '₱' . number_format($employee->basic_salary, 2);

        $hireDate = $employee->date_hired ? \Carbon\Carbon::parse($employee->date_hired) : now();
        $diff = $hireDate->diff(now());
        $yearsOfService = ($diff->y > 0 ? $diff->y . ' yr' . ($diff->y > 1 ? 's ' : ' ') : '') . $diff->m . ' mo' . ($diff->m > 1 ? 's' : '');
        if (empty(trim($yearsOfService))) $yearsOfService = '< 1 mo';

        $stats = [
            'leave_balance' => $leaveBalance,
            'attendance_rate' => $attendanceRate,
            'documents_verified' => $documentsVerified,
            'current_salary' => $currentSalary,
            'years_of_service' => $yearsOfService,
        ];

        // 2. Attendance Summary
        $daysPresent = $employee->attendanceRecords->whereIn('status', ['Present', 'Overtime'])->count();
        $daysAbsent = $employee->attendanceRecords->where('status', 'Absent')->count();
        $lateInstances = $employee->attendanceRecords->where('late_minutes', '>', 0)->count();
        $undertimeHours = round($employee->attendanceRecords->sum('undertime_minutes') / 60, 2);
        $overtimeHours = round($employee->attendanceRecords->sum('overtime_hours'), 2);

        $attendanceSummary = [
            'rate' => $attendanceRate,
            'present' => $daysPresent ?: max($totalAtt, 22),
            'absent' => $daysAbsent,
            'late' => $lateInstances,
            'undertime' => $undertimeHours,
            'overtime' => $overtimeHours,
        ];

        // 3. Recent Activities (Unified chronological stream)
        $recentActivities = collect();
        foreach ($employee->attendanceRecords->take(3) as $att) {
            $recentActivities->push([
                'icon' => 'ph-clock',
                'color' => '#7c3aed',
                'title' => 'Attendance Log: ' . ($att->status ?? 'Present'),
                'desc' => \Carbon\Carbon::parse($att->date)->format('M d, Y') . ' &bull; ' . number_format($att->total_hours, 2) . ' hrs',
                'time' => \Carbon\Carbon::parse($att->date)->diffForHumans(),
            ]);
        }
        foreach ($employee->leaveRequests->take(2) as $lr) {
            $recentActivities->push([
                'icon' => 'ph-calendar-blank',
                'color' => '#ec4899',
                'title' => 'Leave Request: ' . ($lr->leaveType?->name ?? 'Leave') . ' (' . $lr->status . ')',
                'desc' => $lr->number_of_days . ' day(s) &bull; ' . \Carbon\Carbon::parse($lr->start_date)->format('M d, Y'),
                'time' => $lr->created_at->diffForHumans(),
            ]);
        }
        foreach ($employee->payrollRecords->take(2) as $pr) {
            $recentActivities->push([
                'icon' => 'ph-money',
                'color' => '#059669',
                'title' => 'Payslip Processed: ₱' . number_format($pr->net_pay, 2),
                'desc' => ($pr->payrollPeriod?->name ?? 'Payroll Cut-off') . ' &bull; Net Pay',
                'time' => $pr->created_at->diffForHumans(),
            ]);
        }
        foreach ($employee->documents->take(2) as $doc) {
            $recentActivities->push([
                'icon' => 'ph-file-text',
                'color' => '#2563eb',
                'title' => 'Document Uploaded: ' . $doc->document_name,
                'desc' => ($doc->document_type ?? 'Document') . ' &bull; ' . ($doc->status ?? 'Verified'),
                'time' => $doc->created_at->diffForHumans(),
            ]);
        }

        return view('hr.people.employee-detail', compact(
            'employee', 'canViewSensitive', 'companies', 'agencies', 'departments', 'branches', 'positions', 'supervisors',
            'stats', 'attendanceSummary', 'recentActivities'
        ));
    }

    public function employeeStore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (empty($request->employee_id)) {
            $request->merge(['employee_id' => Employee::generateNextEmployeeId()]);
        }
        $validated = $request->validate([
            'employee_id' => 'required|string|max:30|unique:hr_employees,employee_id',
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:15',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other',
            'civil_status' => 'required|in:Single,Married,Widowed,Divorced,Separated',
            'nationality' => 'required|string|max:50',
            'mobile_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:hr_branches,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'position_id' => 'nullable|exists:hr_positions,id',
            'assigned_department_ids' => 'nullable|array',
            'assigned_department_ids.*' => 'exists:hr_departments,id',
            'assigned_branch_ids' => 'nullable|array',
            'assigned_branch_ids.*' => 'exists:hr_branches,id',
            'assigned_position_ids' => 'nullable|array',
            'assigned_position_ids.*' => 'exists:hr_positions,id',
            'supervisor_id' => 'nullable|exists:hr_employees,id',
            'date_hired' => 'required|date',
            'employment_status' => 'required|in:Active,Probationary,On Leave,Suspended,Resigned,Terminated,Retired',
            'employment_type' => 'required|in:Regular,Probationary,Part-time,Casual,Contractual',
            'employment_source' => 'nullable|in:Company,Agency',
            'company_name' => 'nullable|string|max:150',
            'agency_name' => 'nullable|string|max:150',
            'date_of_regularization' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'sss_number' => 'nullable|string|max:30',
            'philhealth_number' => 'nullable|string|max:30',
            'pagibig_number' => 'nullable|string|max:30',
            'tin' => 'nullable|string|max:30',
            'basic_salary' => 'nullable|numeric|min:0',
            'salary_type' => 'required|in:Monthly,Daily,Hourly',
            'pay_frequency' => 'required|in:Semi-Monthly,Monthly,Weekly',
            'allowances' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'emergency_contact_phone' => 'nullable|string|max:30',
        ]);

        if (!$user->canAccessBranch($validated['branch_id'])) {
            abort(403, 'Unauthorized to create employee in another branch.');
        }

        $deptIds = $request->input('assigned_department_ids', []);
        if (!empty($deptIds)) {
            $validated['assigned_department_ids'] = array_values(array_map('intval', $deptIds));
            if (!empty($validated['department_id'])) {
                if (!in_array((int)$validated['department_id'], $validated['assigned_department_ids'])) {
                    array_unshift($validated['assigned_department_ids'], (int)$validated['department_id']);
                }
            } else {
                $validated['department_id'] = $validated['assigned_department_ids'][0];
            }
        } elseif (!empty($validated['department_id'])) {
            $validated['assigned_department_ids'] = [(int) $validated['department_id']];
        }

        $branchIds = $request->input('assigned_branch_ids', []);
        if (!empty($branchIds)) {
            $validated['assigned_branch_ids'] = array_values(array_map('intval', $branchIds));
            if (!empty($validated['branch_id'])) {
                if (!in_array((int)$validated['branch_id'], $validated['assigned_branch_ids'])) {
                    array_unshift($validated['assigned_branch_ids'], (int)$validated['branch_id']);
                }
            } else {
                $validated['branch_id'] = $validated['assigned_branch_ids'][0];
            }
        } elseif (!empty($validated['branch_id'])) {
            $validated['assigned_branch_ids'] = [(int) $validated['branch_id']];
        }

        $posIds = $request->input('assigned_position_ids', []);
        if (!empty($posIds)) {
            $validated['assigned_position_ids'] = array_values(array_map('intval', $posIds));
            if (!empty($validated['position_id'])) {
                if (!in_array((int)$validated['position_id'], $validated['assigned_position_ids'])) {
                    array_unshift($validated['assigned_position_ids'], (int)$validated['position_id']);
                }
            } else {
                $validated['position_id'] = $validated['assigned_position_ids'][0];
            }
        } elseif (!empty($validated['position_id'])) {
            $validated['assigned_position_ids'] = [(int) $validated['position_id']];
        }

        if (!empty($validated['company_id'])) {
            $comp = Company::find($validated['company_id']);
            if ($comp) {
                if ($comp->type === 'Agency') {
                    $validated['employment_source'] = 'Agency';
                    $validated['agency_name'] = $comp->name;
                    $validated['company_agency_name'] = $comp->name;
                } else {
                    $validated['employment_source'] = 'Company';
                    $validated['company_name'] = $comp->name;
                    $validated['company_agency_name'] = $comp->name;
                }
            }
        } elseif (($validated['employment_source'] ?? '') === 'Agency') {
            $validated['company_agency_name'] = $validated['agency_name'] ?? null;
            if (!empty($validated['agency_name'])) {
                $comp = Company::where('name', $validated['agency_name'])->first();
                if ($comp) $validated['company_id'] = $comp->id;
            }
        } else {
            $validated['company_agency_name'] = $validated['company_name'] ?? null;
            if (!empty($validated['company_name'])) {
                $comp = Company::where('name', $validated['company_name'])->first();
                if ($comp) $validated['company_id'] = $comp->id;
            }
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees/photos', 'public');
            $validated['photo'] = $path;
        }

        $employee = Employee::create($validated);

        if (!empty($validated['emergency_contact_name'])) {
            EmergencyContact::create([
                'employee_id' => $employee->id,
                'contact_name' => $validated['emergency_contact_name'],
                'relationship' => $validated['emergency_contact_relationship'] ?? 'Family',
                'contact_number' => $validated['emergency_contact_phone'] ?? '',
                'address' => $validated['address'] ?? '',
            ]);
        }

        AuditLogger::log('Create', 'Employees', $employee->id, "Created employee record for {$employee->full_name}", null, $employee->toArray());

        return redirect()->route('hr.people.employees')->with('success', "Employee {$employee->full_name} created successfully.");
    }

    public function employeeUpdate(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::findOrFail($id);

        if (!$user->canAccessBranch($employee->branch_id)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:15',
            'preferred_name' => 'nullable|string|max:60',
            'date_of_birth' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'gender' => 'nullable|in:Male,Female,Other',
            'civil_status' => 'nullable|in:Single,Married,Widowed,Divorced,Separated',
            'nationality' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:30',
            'telephone_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'personal_email' => 'nullable|email|max:100',
            'company_email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_photo' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'position_id' => 'nullable|exists:hr_positions,id',
            'assigned_department_ids' => 'nullable|array',
            'assigned_department_ids.*' => 'exists:hr_departments,id',
            'assigned_branch_ids' => 'nullable|array',
            'assigned_branch_ids.*' => 'exists:hr_branches,id',
            'assigned_position_ids' => 'nullable|array',
            'assigned_position_ids.*' => 'exists:hr_positions,id',
            'job_level' => 'nullable|string|max:60',
            'supervisor_id' => 'nullable|exists:hr_employees,id',
            'date_hired' => 'nullable|date',
            'employment_status' => 'required|in:Active,Probationary,On Leave,Suspended,Resigned,Terminated,Retired',
            'employment_type' => 'required|in:Regular,Probationary,Part-time,Casual,Contractual,Seasonal,Intern / OJT',
            'employment_source' => 'nullable|in:Company,Agency',
            'company_name' => 'nullable|string|max:150',
            'agency_name' => 'nullable|string|max:150',
            'work_location' => 'nullable|string|max:100',
            'work_schedule' => 'nullable|string|max:100',
            'date_of_regularization' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'sss_number' => 'nullable|string|max:30',
            'philhealth_number' => 'nullable|string|max:30',
            'pagibig_number' => 'nullable|string|max:30',
            'tin' => 'nullable|string|max:30',
            'tin_number' => 'nullable|string|max:30',
            'rdo_code' => 'nullable|string|max:30',
            'philsys_id' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'driver_license' => 'nullable|string|max:50',
            'basic_salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:Monthly,Daily,Hourly',
            'pay_frequency' => 'nullable|in:Semi-Monthly,Semi-monthly,Monthly,Weekly',
            'allowances' => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('personal_email') && empty($validated['email'])) {
            $validated['email'] = $request->input('personal_email');
        }
        if ($request->filled('tin_number') && empty($validated['tin'])) {
            $validated['tin'] = $request->input('tin_number');
        }
        if (!empty($validated['pay_frequency']) && strtolower($validated['pay_frequency']) === 'semi-monthly') {
            $validated['pay_frequency'] = 'Semi-Monthly';
        }

        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = $employee->branch_id;
        }
        if (empty($validated['date_hired'])) {
            $validated['date_hired'] = $employee->getRawOriginal('date_hired') ?: date('Y-m-d');
        }
        if (empty($validated['civil_status'])) {
            $validated['civil_status'] = $employee->civil_status ?: 'Single';
        }
        if (empty($validated['nationality'])) {
            $validated['nationality'] = $employee->nationality ?: 'Filipino';
        }
        if (empty($validated['salary_type'])) {
            $validated['salary_type'] = $employee->salary_type ?: 'Monthly';
        }
        if (empty($validated['pay_frequency'])) {
            $validated['pay_frequency'] = $employee->pay_frequency ?: 'Semi-Monthly';
        }


        $deptIds = $request->input('assigned_department_ids', []);
        if (!empty($deptIds)) {
            $validated['assigned_department_ids'] = array_values(array_map('intval', $deptIds));
            if (!empty($validated['department_id'])) {
                if (!in_array((int)$validated['department_id'], $validated['assigned_department_ids'])) {
                    array_unshift($validated['assigned_department_ids'], (int)$validated['department_id']);
                }
            } else {
                $validated['department_id'] = $validated['assigned_department_ids'][0];
            }
        } elseif (!empty($validated['department_id'])) {
            $validated['assigned_department_ids'] = [(int) $validated['department_id']];
        }

        $branchIds = $request->input('assigned_branch_ids', []);
        if (!empty($branchIds)) {
            $validated['assigned_branch_ids'] = array_values(array_map('intval', $branchIds));
            if (!empty($validated['branch_id'])) {
                if (!in_array((int)$validated['branch_id'], $validated['assigned_branch_ids'])) {
                    array_unshift($validated['assigned_branch_ids'], (int)$validated['branch_id']);
                }
            } else {
                $validated['branch_id'] = $validated['assigned_branch_ids'][0];
            }
        } elseif (!empty($validated['branch_id'])) {
            $validated['assigned_branch_ids'] = [(int) $validated['branch_id']];
        }

        $posIds = $request->input('assigned_position_ids', []);
        if (!empty($posIds)) {
            $validated['assigned_position_ids'] = array_values(array_map('intval', $posIds));
            if (!empty($validated['position_id'])) {
                if (!in_array((int)$validated['position_id'], $validated['assigned_position_ids'])) {
                    array_unshift($validated['assigned_position_ids'], (int)$validated['position_id']);
                }
            } else {
                $validated['position_id'] = $validated['assigned_position_ids'][0];
            }
        } elseif (!empty($validated['position_id'])) {
            $validated['assigned_position_ids'] = [(int) $validated['position_id']];
        }

        if (!empty($request->input('company_id'))) {
            $validated['company_id'] = $request->input('company_id');
            $comp = Company::find($validated['company_id']);
            if ($comp) {
                if ($comp->type === 'Agency') {
                    $validated['employment_source'] = 'Agency';
                    $validated['agency_name'] = $comp->name;
                    $validated['company_agency_name'] = $comp->name;
                } else {
                    $validated['employment_source'] = 'Company';
                    $validated['company_name'] = $comp->name;
                    $validated['company_agency_name'] = $comp->name;
                }
            }
        } elseif (($validated['employment_source'] ?? '') === 'Agency') {
            $validated['company_agency_name'] = $validated['agency_name'] ?? null;
            if (!empty($validated['agency_name'])) {
                $comp = Company::where('name', $validated['agency_name'])->first();
                if ($comp) $validated['company_id'] = $comp->id;
            }
        } else {
            $validated['company_agency_name'] = $validated['company_name'] ?? null;
            if (!empty($validated['company_name'])) {
                $comp = Company::where('name', $validated['company_name'])->first();
                if ($comp) $validated['company_id'] = $comp->id;
            }
        }

        if ($request->boolean('remove_photo')) {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $validated['photo'] = null;
        } elseif ($request->hasFile('photo')) {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $path = $request->file('photo')->store('employees/photos', 'public');
            $validated['photo'] = $path;
        }

        $prev = $employee->toArray();
        $employee->update($validated);

        AuditLogger::log('Update', 'Employees', $employee->id, "Updated employee {$employee->full_name}", $prev, $employee->toArray());

        return redirect()->back()->with('success', "Employee record updated successfully.");
    }

    public function employeeDestroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isHrAdmin()) {
            abort(403, 'Unauthorized to delete employee record.');
        }

        $employee = Employee::findOrFail($id);
        $name = $employee->full_name;
        $employee->delete();

        AuditLogger::log('Delete', 'Employees', $id, "Deleted employee {$name}");

        return redirect()->route('hr.people.employees')->with('success', "Employee {$name} deleted successfully.");
    }

    // ==========================================
    // 1.1. EMPLOYEE PROFILE LIFECYCLE & 201 ACTIONS
    // ==========================================

    public function changePosition(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'position_id' => 'required|exists:hr_positions,id',
            'assigned_position_ids' => 'nullable|array',
            'assigned_position_ids.*' => 'exists:hr_positions,id',
            'effective_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldPos = $employee->position?->name ?? 'Unassigned';
        $newPos = Position::findOrFail($request->position_id)->name;

        $posIds = $request->input('assigned_position_ids');
        if ($posIds !== null) {
            $posIds = array_values(array_map('intval', $posIds));
            if (!in_array((int)$request->position_id, $posIds)) {
                array_unshift($posIds, (int)$request->position_id);
            }
        } else {
            $posIds = $employee->all_position_ids;
            if (!in_array((int)$request->position_id, $posIds)) {
                $posIds[] = (int)$request->position_id;
            }
        }

        EmploymentHistory::create([
            'employee_id' => $employee->id,
            'action_type' => 'Position Change',
            'previous_value' => $oldPos,
            'new_value' => $newPos,
            'remarks' => $request->remarks ?: ($request->reason ?: "Position updated from {$oldPos} to {$newPos}"),
            'effective_date' => $request->effective_date,
            'recorded_by' => Auth::id(),
        ]);

        $employee->update([
            'position_id' => $request->position_id,
            'assigned_position_ids' => $posIds,
        ]);
        AuditLogger::log('Update', 'Employees', $employee->id, "Position changed to {$newPos} for {$employee->full_name}");

        return redirect()->back()->with('success', "Position successfully changed to {$newPos}.");
    }

    public function transferEmployee(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'branch_id' => 'required|exists:hr_branches,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'assigned_branch_ids' => 'nullable|array',
            'assigned_branch_ids.*' => 'exists:hr_branches,id',
            'assigned_department_ids' => 'nullable|array',
            'assigned_department_ids.*' => 'exists:hr_departments,id',
            'effective_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldBranch = $employee->branch?->name ?? 'Unassigned';
        $newBranch = Branch::findOrFail($request->branch_id)->name;
        $oldDept = $employee->department?->name ?? 'None';
        $newDept = $request->department_id ? Department::findOrFail($request->department_id)->name : $oldDept;

        $branchIds = $request->input('assigned_branch_ids');
        if ($branchIds !== null) {
            $branchIds = array_values(array_map('intval', $branchIds));
            if (!in_array((int)$request->branch_id, $branchIds)) {
                array_unshift($branchIds, (int)$request->branch_id);
            }
        } else {
            $branchIds = $employee->all_branch_ids;
            if (!in_array((int)$request->branch_id, $branchIds)) {
                $branchIds[] = (int)$request->branch_id;
            }
        }

        $deptIds = $request->input('assigned_department_ids');
        if ($deptIds !== null) {
            $deptIds = array_values(array_map('intval', $deptIds));
            if ($request->department_id && !in_array((int)$request->department_id, $deptIds)) {
                array_unshift($deptIds, (int)$request->department_id);
            }
        } elseif ($request->department_id) {
            $deptIds = $employee->all_department_ids;
            if (!in_array((int)$request->department_id, $deptIds)) {
                $deptIds[] = (int)$request->department_id;
            }
        } else {
            $deptIds = $employee->all_department_ids;
        }

        EmploymentHistory::create([
            'employee_id' => $employee->id,
            'action_type' => 'Transferred',
            'previous_value' => "Branch: {$oldBranch}, Dept: {$oldDept}",
            'new_value' => "Branch: {$newBranch}, Dept: {$newDept}",
            'remarks' => $request->remarks ?: ($request->reason ?: "Transferred to {$newBranch} ({$newDept})"),
            'effective_date' => $request->effective_date,
            'recorded_by' => Auth::id(),
        ]);

        $employee->update([
            'branch_id' => $request->branch_id,
            'department_id' => $request->department_id ?: $employee->department_id,
            'assigned_branch_ids' => $branchIds,
            'assigned_department_ids' => $deptIds,
        ]);
        AuditLogger::log('Update', 'Employees', $employee->id, "Transferred {$employee->full_name} to {$newBranch}");

        return redirect()->back()->with('success', "Employee successfully transferred to {$newBranch}.");
    }

    public function changeSalary(Request $request, int $id): RedirectResponse
    {
        if (!$request->filled('basic_salary') && $request->filled('new_salary')) {
            $request->merge(['basic_salary' => $request->new_salary]);
        }

        $employee = Employee::findOrFail($id);
        $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'adjustment_type' => 'required|string|max:50',
            'effective_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldSalary = (float) $employee->basic_salary;
        $newSalary = (float) $request->basic_salary;

        $salHistory = $employee->salary_history ?? [];
        $salHistory[] = [
            'effective_date' => $request->effective_date,
            'previous_salary' => $oldSalary,
            'new_salary' => $newSalary,
            'adjustment_type' => $request->adjustment_type,
            'reason' => $request->reason ?? 'Periodic rate adjustment',
            'approved_by' => Auth::user()?->full_name ?? 'HR Executive',
        ];

        EmploymentHistory::create([
            'employee_id' => $employee->id,
            'action_type' => 'Salary Adjustment',
            'previous_value' => '₱' . number_format($oldSalary, 2),
            'new_value' => '₱' . number_format($newSalary, 2),
            'remarks' => "{$request->adjustment_type}: " . ($request->reason ?: 'Salary update'),
            'effective_date' => $request->effective_date,
            'recorded_by' => Auth::id(),
        ]);

        $employee->update([
            'basic_salary' => $newSalary,
            'allowances' => $request->filled('allowances') ? (float)$request->allowances : $employee->allowances,
            'salary_history' => $salHistory,
        ]);
        AuditLogger::log('Update', 'Employees', $employee->id, "Salary adjusted for {$employee->full_name} to ₱" . number_format($newSalary, 2));

        return redirect()->back()->with('success', "Salary adjusted to ₱" . number_format($newSalary, 2) . " successfully.");
    }

    public function changeStatus(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'employment_status' => 'required|in:Active,Probationary,On Leave,Suspended,Resigned,Terminated,Retired',
            'effective_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $oldStatus = $employee->employment_status;
        $newStatus = $request->employment_status;

        EmploymentHistory::create([
            'employee_id' => $employee->id,
            'action_type' => 'Status Change',
            'previous_value' => $oldStatus,
            'new_value' => $newStatus,
            'remarks' => $request->remarks ?: "Status changed from {$oldStatus} to {$newStatus}",
            'effective_date' => $request->effective_date,
            'recorded_by' => Auth::id(),
        ]);

        $employee->update(['employment_status' => $newStatus]);
        AuditLogger::log('Update', 'Employees', $employee->id, "Status changed to {$newStatus} for {$employee->full_name}");

        return redirect()->back()->with('success', "Employment status changed to {$newStatus}.");
    }

    public function processSeparation(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'separation_type' => 'required|in:Resigned,Terminated,Retired',
            'date_separated' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $oldStatus = $employee->employment_status;
        $newStatus = $request->separation_type;

        EmploymentHistory::create([
            'employee_id' => $employee->id,
            'action_type' => 'Separation',
            'previous_value' => $oldStatus,
            'new_value' => $newStatus,
            'remarks' => $request->remarks ?: "Separation processed as {$newStatus}",
            'effective_date' => $request->date_separated,
            'recorded_by' => Auth::id(),
        ]);

        $employee->update([
            'employment_status' => $newStatus,
            'date_separated' => $request->date_separated,
        ]);
        AuditLogger::log('Update', 'Employees', $employee->id, "Processed separation ({$newStatus}) for {$employee->full_name}");

        return redirect()->back()->with('success', "Employee separation processed successfully.");
    }

    public function archiveEmployee(int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $name = $employee->full_name;
        $employee->delete();
        AuditLogger::log('Delete', 'Employees', $id, "Archived employee record {$name}");

        return redirect()->route('hr.people.employees')->with('success', "Employee record {$name} archived.");
    }

    public function addFamilyMember(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:100',
            'relationship' => 'required|string|max:50',
            'birth_date' => 'nullable|date',
            'occupation' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:30',
        ]);

        $list = $employee->family_dependents ?? [];
        $list[] = [
            'name' => $request->name,
            'relationship' => $request->relationship,
            'birth_date' => $request->birth_date,
            'occupation' => $request->occupation,
            'contact_number' => $request->contact_number,
            'is_dependent' => $request->boolean('is_dependent'),
            'is_beneficiary' => $request->boolean('is_beneficiary'),
        ];

        $employee->update(['family_dependents' => $list]);
        return redirect()->back()->with('success', "Family member / dependent added.");
    }

    public function deleteFamilyMember(int $id, int $index): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $list = $employee->family_dependents ?? [];
        if (isset($list[$index])) {
            array_splice($list, $index, 1);
            $employee->update(['family_dependents' => array_values($list)]);
        }
        return redirect()->back()->with('success', "Family member record removed.");
    }

    public function addEmergencyContact(Request $request, int $id): RedirectResponse
    {
        if (!$request->filled('contact_name') && $request->filled('name')) {
            $request->merge(['contact_name' => $request->name]);
        }

        $request->validate([
            'contact_name' => 'required|string|max:100',
            'relationship' => 'required|string|max:50',
            'contact_number' => 'required|string|max:30',
            'address' => 'nullable|string|max:255',
        ]);

        if ($request->boolean('is_primary')) {
            EmergencyContact::where('employee_id', $id)->update(['is_primary' => false]);
        }

        EmergencyContact::create([
            'employee_id' => $id,
            'contact_name' => $request->contact_name,
            'relationship' => $request->relationship,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'is_primary' => $request->boolean('is_primary'),
        ]);

        return redirect()->back()->with('success', "Emergency contact added.");
    }

    public function deleteEmergencyContact(int $id, int $contactId): RedirectResponse
    {
        EmergencyContact::where('employee_id', $id)->where('id', $contactId)->delete();
        return redirect()->back()->with('success', "Emergency contact removed.");
    }

    public function addEducation(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $request->validate([
            'level' => 'required|string|max:50',
            'school' => 'required|string|max:150',
            'course' => 'nullable|string|max:150',
            'year_started' => 'nullable|numeric|digits:4',
            'year_completed' => 'nullable|numeric|digits:4',
            'status' => 'nullable|string|max:50',
            'honors' => 'nullable|string|max:100',
        ]);

        $list = $employee->education_history ?? [];
        $list[] = [
            'level' => $request->level,
            'school' => $request->school,
            'course' => $request->course ?? '',
            'year_started' => $request->year_started,
            'year_completed' => $request->year_completed,
            'status' => $request->status ?? 'Graduated',
            'honors' => $request->honors ?? '',
        ];

        $employee->update(['education_history' => $list]);
        return redirect()->back()->with('success', "Education record added.");
    }

    public function deleteEducation(int $id, int $index): RedirectResponse
    {
        $employee = Employee::findOrFail($id);
        $list = $employee->education_history ?? [];
        if (isset($list[$index])) {
            array_splice($list, $index, 1);
            $employee->update(['education_history' => array_values($list)]);
        }
        return redirect()->back()->with('success', "Education record removed.");
    }

    public function documentVerify(int $id): RedirectResponse
    {
        $doc = EmployeeDocument::findOrFail($id);
        $doc->update([
            'status' => 'Verified',
            'is_verified' => true,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);
        AuditLogger::log('Update', 'Employees', $doc->employee_id, "Verified document '{$doc->document_name}'");

        return redirect()->back()->with('success', "Document '{$doc->document_name}' marked as Verified.");
    }

    public function documentReplace(Request $request, int $id): RedirectResponse
    {
        $doc = EmployeeDocument::findOrFail($id);
        $request->validate([
            'file' => 'required|file|max:10240',
            'expiry_date' => 'nullable|date',
        ]);

        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $path = $request->file('file')->store('employee_documents', 'public');
        $updateData = [
            'file_path' => $path,
            'status' => 'Verified',
            'is_verified' => true,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ];
        if ($request->filled('expiry_date')) {
            $updateData['expiry_date'] = $request->expiry_date;
        }

        $doc->update($updateData);
        AuditLogger::log('Update', 'Employees', $doc->employee_id, "Replaced file for document '{$doc->document_name}'");

        return redirect()->back()->with('success', "Document replaced successfully.");
    }

    public function generateCoe(Request $request, int $id): View
    {
        $employee = Employee::with(['branch.company', 'company', 'position', 'department'])->findOrFail($id);
        $purpose = $request->get('purpose', 'Employment Verification & Reference');
        $signatory = $request->get('signatory', Auth::user()?->full_name ?? 'HR Administration');
        $signatoryTitle = $request->get('signatory_title', 'Human Resources Director');

        return view('hr.people.coe-printable', compact('employee', 'purpose', 'signatory', 'signatoryTitle'));
    }

    public function print201File(int $id): View
    {
        $employee = Employee::with([
            'branch.company', 'company', 'position', 'department', 'supervisor',
            'emergencyContacts',
            'documents',
            'employmentHistories',
            'leaveBalances.leaveType',
        ])->findOrFail($id);

        return view('hr.people.print-201', compact('employee'));
    }

    // ==========================================
    // 2. ORGANIZATION (DEPARTMENTS, POSITIONS, BRANCHES, COMPANIES)
    // ==========================================

    public function organizationIndex(): RedirectResponse
    {
        return redirect()->route('hr.people.departments');
    }

    public function getMembersModal(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $id = (int) $request->get('id');

        $title = 'Staff Members';
        $entityName = '';
        $query = Employee::with(['branch', 'department', 'position'])->orderBy('first_name')->orderBy('last_name');

        switch ($type) {
            case 'department':
                $dept = Department::findOrFail($id);
                $entityName = $dept->name;
                $title = "Department: {$dept->name}";
                $query->where(function($q) use ($id) {
                    $q->where('department_id', $id)
                      ->orWhereJsonContains('assigned_department_ids', $id);
                });
                break;

            case 'position':
                $pos = Position::findOrFail($id);
                $entityName = $pos->name;
                $title = "Position: {$pos->name}";
                $query->where(function($q) use ($id) {
                    $q->where('position_id', $id)
                      ->orWhereJsonContains('assigned_position_ids', $id);
                });
                break;

            case 'branch':
                $branch = Branch::findOrFail($id);
                $entityName = $branch->name;
                $title = "Branch: {$branch->name}";
                $query->where(function($q) use ($id) {
                    $q->where('branch_id', $id)
                      ->orWhereJsonContains('assigned_branch_ids', $id);
                });
                break;

            case 'company':
                $company = Company::findOrFail($id);
                $entityName = $company->name;
                $title = ($company->type === 'Agency' ? 'Agency: ' : 'Company: ') . $company->name;
                $query->where(function($q) use ($company) {
                    $q->where('company_id', $company->id)
                      ->orWhere('company_name', $company->name)
                      ->orWhere('agency_name', $company->name)
                      ->orWhere('company_agency_name', $company->name);
                });
                break;

            default:
                return response()->json(['error' => 'Invalid entity type'], 400);
        }

        $employees = $query->get()->map(function($emp) {
            $initials = strtoupper(substr($emp->first_name ?? '', 0, 1) . substr($emp->last_name ?? '', 0, 1)) ?: 'EM';
            
            $positions = $emp->assignedPositions()->map(function($p) use ($emp) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'is_primary' => $p->id === $emp->position_id,
                ];
            });
            if ($positions->isEmpty() && $emp->position) {
                $positions = collect([['id' => $emp->position->id, 'name' => $emp->position->name, 'is_primary' => true]]);
            }

            return [
                'id' => $emp->id,
                'employee_id' => $emp->employee_id,
                'full_name' => $emp->full_name,
                'profile_photo_url' => $emp->profile_photo_url,
                'initials' => $initials,
                'position' => $emp->position?->name ?? 'General Staff',
                'positions' => $positions,
                'department' => $emp->department?->name ?? 'Unassigned',
                'branch' => $emp->branch?->name ?? 'Unassigned',
                'employment_status' => $emp->employment_status,
                'employment_type' => $emp->employment_type ?? 'Regular',
                'company' => $emp->company_or_agency,
                'profile_url' => route('hr.people.employees.show', $emp->id),
            ];
        });

        return response()->json([
            'success' => true,
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'entity_name' => $entityName,
            'count' => $employees->count(),
            'members' => $employees,
        ]);
    }

    public function departmentsIndex(): View
    {
        $departments = Department::with('branch')->get();
        $allEmps = Employee::select('id', 'department_id', 'assigned_department_ids')->get();
        foreach ($departments as $dept) {
            $dept->employees_count = $allEmps->filter(fn($e) => in_array($dept->id, $e->all_department_ids))->count();
        }
        $branches = Branch::where('is_active', true)->get();
        return view('hr.people.departments', compact('departments', 'branches'));
    }

    public function departmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:hr_departments,code',
            'branch_id' => 'nullable|exists:hr_branches,id',
            'description' => 'nullable|string',
        ]);

        $dept = Department::create($validated);
        AuditLogger::log('Create', 'Organization', $dept->id, "Created department {$dept->name}");

        return redirect()->back()->with('success', "Department '{$dept->name}' created.");
    }

    public function departmentUpdate(Request $request, int $id): RedirectResponse
    {
        $dept = Department::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:hr_departments,code,' . $id,
            'branch_id' => 'nullable|exists:hr_branches,id',
            'description' => 'nullable|string',
        ]);

        $dept->update($validated);
        AuditLogger::log('Update', 'Organization', $dept->id, "Updated department {$dept->name}");

        return redirect()->back()->with('success', "Department '{$dept->name}' updated.");
    }

    // ==========================================
    // 3. POSITIONS
    // ==========================================

    public function positionsIndex(): View
    {
        $positions = Position::with(['department', 'employees'])->get();
        $allEmps = Employee::select('id', 'position_id', 'assigned_position_ids')->get();
        foreach ($positions as $pos) {
            $pos->employees_count = $allEmps->filter(fn($e) => in_array($pos->id, $e->all_position_ids))->count();
        }
        $departments = Department::all();
        return view('hr.people.positions', compact('positions', 'departments'));
    }

    public function positionStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:hr_positions,code',
            'department_id' => 'nullable|exists:hr_departments,id',
            'description' => 'nullable|string',
        ]);

        $pos = Position::create($validated);
        AuditLogger::log('Create', 'Organization', $pos->id, "Created position {$pos->name}");

        return redirect()->back()->with('success', "Position '{$pos->name}' created.");
    }

    public function positionUpdate(Request $request, int $id): RedirectResponse
    {
        $pos = Position::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:hr_positions,code,' . $id,
            'department_id' => 'nullable|exists:hr_departments,id',
            'description' => 'nullable|string',
        ]);

        $pos->update($validated);
        AuditLogger::log('Update', 'Organization', $pos->id, "Updated position {$pos->name}");

        return redirect()->back()->with('success', "Position '{$pos->name}' updated.");
    }

    // ==========================================
    // 4. BRANCHES
    // ==========================================

    public function branchesIndex(): View
    {
        $branches = Branch::with('company')->get();
        $allEmps = Employee::select('id', 'branch_id', 'assigned_branch_ids')->get();
        foreach ($branches as $branch) {
            $branch->employees_count = $allEmps->filter(fn($e) => in_array($branch->id, $e->all_branch_ids))->count();
        }
        $company = Company::first();
        return view('hr.people.branches', compact('branches', 'company'));
    }

    public function branchStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:hr_branches,code',
            'company_id' => 'nullable|exists:hr_companies,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);
        AuditLogger::log('Create', 'Organization', $branch->id, "Created branch {$branch->name}");

        return redirect()->back()->with('success', "Branch '{$branch->name}' created.");
    }

    public function branchUpdate(Request $request, int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:hr_branches,code,' . $id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);
        AuditLogger::log('Update', 'Organization', $branch->id, "Updated branch {$branch->name}");

        return redirect()->back()->with('success', "Branch '{$branch->name}' updated.");
    }

    // ==========================================
    // 4.1. AGENCIES & COMPANIES
    // ==========================================

    public function companiesIndex(Request $request): View
    {
        $query = Company::withCount('branches');

        if ($type = $request->get('type')) {
            if (in_array($type, ['Company', 'Agency'])) {
                $query->where('type', $type);
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tin', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('type', 'asc')->orderBy('name', 'asc')->get();

        $allEmps = Employee::select('id', 'company_id', 'company_name', 'agency_name', 'company_agency_name')->get();
        foreach ($companies as $comp) {
            $comp->employees_count = $allEmps->filter(fn($e) => 
                $e->company_id === $comp->id || 
                strcasecmp((string)$e->company_name, $comp->name) === 0 || 
                strcasecmp((string)$e->agency_name, $comp->name) === 0 ||
                strcasecmp((string)$e->company_agency_name, $comp->name) === 0
            )->count();
        }

        $totalCount = Company::count();
        $companyCount = Company::where('type', 'Company')->count();
        $agencyCount = Company::where('type', 'Agency')->count();
        $totalStaffCount = Employee::whereNotNull('company_id')->orWhereNotNull('company_name')->orWhereNotNull('agency_name')->count();

        return view('hr.people.companies', compact('companies', 'totalCount', 'companyCount', 'agencyCount', 'totalStaffCount'));
    }

    public function companyStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|in:Company,Agency',
            'code' => 'required|string|max:50|unique:hr_companies,code',
            'tin' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $entity = Company::create($validated);
        AuditLogger::log('Create', 'Organization', $entity->id, "Created {$entity->type} '{$entity->name}'");

        return redirect()->back()->with('success', "{$entity->type} '{$entity->name}' created successfully.");
    }

    public function companyUpdate(Request $request, int $id): RedirectResponse
    {
        $entity = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|in:Company,Agency',
            'code' => 'required|string|max:50|unique:hr_companies,code,' . $id,
            'tin' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $oldName = $entity->name;
        $entity->update($validated);

        if ($oldName !== $entity->name) {
            if ($entity->type === 'Agency') {
                Employee::where('company_id', $entity->id)->update(['agency_name' => $entity->name, 'company_agency_name' => $entity->name]);
            } else {
                Employee::where('company_id', $entity->id)->update(['company_name' => $entity->name, 'company_agency_name' => $entity->name]);
            }
        }

        AuditLogger::log('Update', 'Organization', $entity->id, "Updated {$entity->type} '{$entity->name}'");

        return redirect()->back()->with('success', "{$entity->type} '{$entity->name}' updated successfully.");
    }

    public function companyDestroy(int $id): RedirectResponse
    {
        $entity = Company::findOrFail($id);
        $empCount = Employee::where('company_id', $id)
            ->orWhere('company_name', $entity->name)
            ->orWhere('agency_name', $entity->name)
            ->count();

        if ($empCount > 0) {
            return redirect()->back()->with('error', "Cannot delete '{$entity->name}' because {$empCount} employee(s) are assigned to it. Please reassign the employees first.");
        }

        if ($entity->branches()->count() > 0) {
            return redirect()->back()->with('error', "Cannot delete '{$entity->name}' because it has restaurant branches attached.");
        }

        $name = $entity->name;
        $type = $entity->type;
        $entity->delete();

        AuditLogger::log('Delete', 'Organization', $id, "Deleted {$type} '{$name}'");

        return redirect()->back()->with('success', "{$type} '{$name}' deleted successfully.");
    }

    // ==========================================
    // 5. DOCUMENTS
    // ==========================================

    public function documentsIndex(Request $request): View
    {
        $user = Auth::user();
        $query = EmployeeDocument::with(['employee.branch']);

        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        $perPage = (int) $request->get('per_page', 50);
        $documents = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $employees = Employee::activeWorkforce()->orderBy('first_name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('hr.people.documents', compact('documents', 'employees', 'branches'));
    }

    public function documentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'document_type' => 'required|string|max:50',
            'title' => 'required|string|max:150',
            'file' => 'required|file|max:10240', // 10MB
            'expiry_date' => 'nullable|date',
        ]);

        $path = $request->file('file')->store('employee_documents', 'public');

        $doc = EmployeeDocument::create([
            'employee_id' => $validated['employee_id'],
            'document_type' => $validated['document_type'],
            'document_name' => $validated['title'] ?? $validated['document_name'] ?? $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'uploaded_by' => Auth::id(),
            'notes' => $request->input('notes'),
        ]);

        AuditLogger::log('Create', 'Employees', $doc->id, "Uploaded document '{$doc->title}' for employee #{$doc->employee_id}");

        return redirect()->back()->with('success', "Document uploaded successfully.");
    }

    public function documentDownload(int $id)
    {
        $doc = EmployeeDocument::findOrFail($id);
        if (!Storage::disk('public')->exists($doc->file_path)) {
            return redirect()->back()->with('error', 'File not found in storage.');
        }
        $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION);
        $filename = $doc->document_name . ($ext ? '.' . $ext : '');
        return Storage::disk('public')->download($doc->file_path, $filename);
    }

    public function documentDestroy(int $id): RedirectResponse
    {
        $doc = EmployeeDocument::findOrFail($id);
        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();

        AuditLogger::log('Delete', 'Employees', $id, "Deleted document #{$id}");

        return redirect()->back()->with('success', "Document deleted successfully.");
    }
}
