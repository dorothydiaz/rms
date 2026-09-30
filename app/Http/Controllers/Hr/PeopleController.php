<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Branch;
use App\Models\Hr\Company;
use App\Models\Hr\Department;
use App\Models\Hr\EmergencyContact;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDocument;
use App\Models\Hr\Position;
use App\Models\User;
use App\Services\AuditLogger;
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
        if (!$user->isSuperAdmin() && !$user->isHrAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
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

        $perPage = (int) $request->get('per_page', 100);
        $employees = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $branches = Branch::where('is_active', true)->get();
        $departments = Department::all();
        $positions = Position::all();
        $companies = Company::where('type', 'Company')->where('is_active', true)->get();
        $agencies = Company::where('type', 'Agency')->where('is_active', true)->get();
        $users = User::orderBy('full_name')->get();

        return view('hr.people.employees', compact('employees', 'branches', 'departments', 'positions', 'companies', 'agencies', 'users'));
    }

    public function employeeShow(int $id): View
    {
        $user = Auth::user();
        $employee = Employee::with([
            'branch', 'department', 'position', 'supervisor',
            'emergencyContacts',
            'documents' => fn($q) => $q->orderBy('created_at', 'desc'),
            'employmentHistories',
            'leaveBalances.leaveType'
        ])->findOrFail($id);

        if (!$user->canAccessBranch($employee->branch_id)) {
            abort(403, 'Unauthorized access to employee in another branch.');
        }

        $canViewSensitive = $user->isSuperAdmin() || $user->isHrAdmin() || $user->hasPermission('employees.sensitive');
        $companies = Company::where('type', 'Company')->where('is_active', true)->get();
        $agencies = Company::where('type', 'Agency')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('hr.people.employee-detail', compact('employee', 'canViewSensitive', 'companies', 'agencies', 'departments', 'branches', 'positions'));
    }

    public function employeeStore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'employee_id' => 'required|string|max:30|unique:employees,employee_id',
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
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'assigned_department_ids' => 'nullable|array',
            'assigned_department_ids.*' => 'exists:departments,id',
            'assigned_branch_ids' => 'nullable|array',
            'assigned_branch_ids.*' => 'exists:branches,id',
            'assigned_position_ids' => 'nullable|array',
            'assigned_position_ids.*' => 'exists:positions,id',
            'supervisor_id' => 'nullable|exists:employees,id',
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
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other',
            'civil_status' => 'required|in:Single,Married,Widowed,Divorced,Separated',
            'nationality' => 'required|string|max:50',
            'mobile_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'remove_photo' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'assigned_department_ids' => 'nullable|array',
            'assigned_department_ids.*' => 'exists:departments,id',
            'assigned_branch_ids' => 'nullable|array',
            'assigned_branch_ids.*' => 'exists:branches,id',
            'assigned_position_ids' => 'nullable|array',
            'assigned_position_ids.*' => 'exists:positions,id',
            'supervisor_id' => 'nullable|exists:employees,id',
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
        ]);

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
    // 2. DEPARTMENTS
    // ==========================================

    public function departmentsIndex(): View
    {
        $departments = Department::withCount('employees')->with('branch')->get();
        $branches = Branch::where('is_active', true)->get();
        return view('hr.people.departments', compact('departments', 'branches'));
    }

    public function departmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:departments,code',
            'branch_id' => 'nullable|exists:branches,id',
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
            'code' => 'required|string|max:30|unique:departments,code,' . $id,
            'branch_id' => 'nullable|exists:branches,id',
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
        $positions = Position::with(['department', 'employees'])->withCount('employees')->get();
        $departments = Department::all();
        return view('hr.people.positions', compact('positions', 'departments'));
    }

    public function positionStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:positions,code',
            'department_id' => 'nullable|exists:departments,id',
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
            'code' => 'required|string|max:30|unique:positions,code,' . $id,
            'department_id' => 'nullable|exists:departments,id',
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
        $branches = Branch::withCount('employees')->with('company')->get();
        $company = Company::first();
        return view('hr.people.branches', compact('branches', 'company'));
    }

    public function branchStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:branches,code',
            'company_id' => 'nullable|exists:companies,id',
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
            'code' => 'required|string|max:30|unique:branches,code,' . $id,
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
        $query = Company::withCount('employees')->withCount('branches');

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
            'code' => 'required|string|max:50|unique:companies,code',
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
            'code' => 'required|string|max:50|unique:companies,code,' . $id,
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
        $employees = Employee::where('employment_status', 'Active')->orderBy('first_name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('hr.people.documents', compact('documents', 'employees', 'branches'));
    }

    public function documentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
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
