<?php

namespace Database\Seeders;

use App\Models\Hr\Applicant;
use App\Models\Hr\AttendanceCorrection;
use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\AuditLog;
use App\Models\Hr\Branch;
use App\Models\Hr\Company;
use App\Models\Hr\Department;
use App\Models\Hr\EmergencyContact;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\InternalNotification;
use App\Models\Hr\Interview;
use App\Models\Hr\JobLevel;
use App\Models\Hr\JobVacancy;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PerformanceCriterion;
use App\Models\Hr\PerformancePeriod;
use App\Models\Hr\Permission;
use App\Models\Hr\Position;
use App\Models\Hr\Role;
use App\Models\Hr\ShiftTemplate;
use App\Models\Hr\StatutoryContributionRule;
use App\Models\Hr\TrainingEnrollment;
use App\Models\Hr\TrainingProgram;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\PayrollCalculationService;
use App\Services\StatutoryContributionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HrDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full system access and configurations']
        );
        $hrAdminRole = Role::updateOrCreate(
            ['slug' => 'hr-admin'],
            ['name' => 'HR/Admin', 'description' => 'HR operational administration across all branches']
        );
        $managerRole = Role::updateOrCreate(
            ['slug' => 'restaurant-manager'],
            ['name' => 'Restaurant Manager', 'description' => 'Branch-scoped operational management']
        );

        // 2. Permissions
        $permissionDefs = [
            'system.settings' => 'Manage system settings',
            'users.manage' => 'Manage user accounts and passwords',
            'roles.manage' => 'Manage roles and permissions',
            'audit.view' => 'View system audit trails',
            'company.manage' => 'Manage company and branches',
            'employees.view' => 'View employee records',
            'employees.manage' => 'Create and modify employee records',
            'employees.sensitive' => 'View sensitive compensation and statutory info',
            'recruitment.manage' => 'Manage job vacancies and applicants',
            'schedules.manage' => 'Manage shift templates and work schedules',
            'attendance.view' => 'View timekeeping and DTR',
            'attendance.manage' => 'Record attendance and time logs',
            'attendance.approve' => 'Approve or reject attendance corrections',
            'leave.view' => 'View leave records and balances',
            'leave.manage' => 'File leave requests for employees',
            'leave.approve' => 'Approve or reject leave requests',
            'payroll.view' => 'View payroll records and payslips',
            'payroll.manage' => 'Process and calculate payroll',
            'payroll.approve' => 'Approve payroll drafts',
            'payroll.finalize' => 'Finalize and lock payroll periods',
            'performance.view' => 'View evaluations and ratings',
            'performance.evaluate' => 'Submit performance reviews for branch staff',
            'performance.manage' => 'Configure evaluation criteria and periods',
            'training.manage' => 'Manage training programs and enrollments',
            'reports.view' => 'View and export management reports',
        ];

        $allPermissionIds = [];
        $hrPermissionIds = [];
        $managerPermissionIds = [];

        $managerPermKeys = [
            'employees.view',
            'attendance.view',
            'attendance.manage',
            'attendance.approve',
            'leave.view',
            'leave.manage',
            'leave.approve',
            'performance.view',
            'performance.evaluate',
            'reports.view',
        ];

        $hrExcludedKeys = ['system.settings', 'roles.manage', 'users.manage'];

        foreach ($permissionDefs as $slug => $name) {
            $perm = Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'module' => explode('.', $slug)[0]]
            );
            $allPermissionIds[] = $perm->id;

            if (!in_array($slug, $hrExcludedKeys)) {
                $hrPermissionIds[] = $perm->id;
            }
            if (in_array($slug, $managerPermKeys)) {
                $managerPermissionIds[] = $perm->id;
            }
        }

        $superAdminRole->permissions()->sync($allPermissionIds);
        $hrAdminRole->permissions()->sync($hrPermissionIds);
        $managerRole->permissions()->sync($managerPermissionIds);

        // 3. Company
        $company = Company::updateOrCreate(
            ['code' => 'BHG-01'],
            [
                'name' => 'Bistro Hospitality Group Inc.',
                'tin' => '123-456-789-000',
                'email' => 'admin@rusticrestaurant.ph',
                'phone' => '+63 2 8123 4567',
                'address' => 'Ground Floor, Greenbelt 5, Ayala Center, Makati City',
            ]
        );

        // 4. Branches
        $branchMakati = Branch::updateOrCreate(
            ['code' => 'MAK-01'],
            [
                'company_id' => $company->id,
                'name' => 'Makati Flagship Branch',
                'address' => 'Greenbelt 5, Ayala Center, Makati City',
                'phone' => '+63 2 8888 1111',
                'email' => 'makati@rusticrestaurant.ph',
                'is_active' => true,
            ]
        );

        $branchBgc = Branch::updateOrCreate(
            ['code' => 'BGC-02'],
            [
                'company_id' => $company->id,
                'name' => 'BGC Taguig Branch',
                'address' => 'Bonifacio High Street Central, BGC, Taguig City',
                'phone' => '+63 2 8888 2222',
                'email' => 'bgc@rusticrestaurant.ph',
                'is_active' => true,
            ]
        );

        $branchQc = Branch::updateOrCreate(
            ['code' => 'QC-03'],
            [
                'company_id' => $company->id,
                'name' => 'Quezon City Branch',
                'address' => 'Tomas Morato Ave cor Scout Gandia, Quezon City',
                'phone' => '+63 2 8888 3333',
                'email' => 'qc@rusticrestaurant.ph',
                'is_active' => true,
            ]
        );

        // 5. Departments
        $deptMgmt = Department::updateOrCreate(
            ['code' => 'MGMT'],
            ['branch_id' => $branchMakati->id, 'name' => 'Management', 'description' => 'Restaurant operational management']
        );
        $deptFoh = Department::updateOrCreate(
            ['code' => 'FOH'],
            ['branch_id' => $branchMakati->id, 'name' => 'Front of House', 'description' => 'Dining, service, cashiering, and hosting']
        );
        $deptBoh = Department::updateOrCreate(
            ['code' => 'BOH'],
            ['branch_id' => $branchMakati->id, 'name' => 'Back of House', 'description' => 'Kitchen operations, culinary and dishwashing']
        );
        $deptFin = Department::updateOrCreate(
            ['code' => 'FIN'],
            ['branch_id' => $branchMakati->id, 'name' => 'Finance & Admin', 'description' => 'Accounting and HR operations']
        );

        // 6. Job Levels
        $levelExec = JobLevel::updateOrCreate(['name' => 'Executive / GM'], ['level' => 1, 'description' => 'Top restaurant management']);
        $levelSup = JobLevel::updateOrCreate(['name' => 'Supervisory / Shift Lead'], ['level' => 2, 'description' => 'Shift supervisors and lead cooks']);
        $levelStaff = JobLevel::updateOrCreate(['name' => 'Rank and File'], ['level' => 3, 'description' => 'Kitchen helpers, servers, cashiers']);

        // 7. Positions
        $posManager = Position::updateOrCreate(['code' => 'RM-01'], ['department_id' => $deptMgmt->id, 'name' => 'Restaurant Manager']);
        $posAsstMgr = Position::updateOrCreate(['code' => 'AM-02'], ['department_id' => $deptMgmt->id, 'name' => 'Assistant Manager']);
        $posHeadCook = Position::updateOrCreate(['code' => 'HC-03'], ['department_id' => $deptBoh->id, 'name' => 'Head Cook']);
        $posCook = Position::updateOrCreate(['code' => 'CK-04'], ['department_id' => $deptBoh->id, 'name' => 'Cook']);
        $posHelper = Position::updateOrCreate(['code' => 'KH-05'], ['department_id' => $deptBoh->id, 'name' => 'Kitchen Helper']);
        $posDishwasher = Position::updateOrCreate(['code' => 'DW-06'], ['department_id' => $deptBoh->id, 'name' => 'Dishwasher']);
        $posCashier = Position::updateOrCreate(['code' => 'CS-07'], ['department_id' => $deptFoh->id, 'name' => 'Cashier']);
        $posServer = Position::updateOrCreate(['code' => 'SV-08'], ['department_id' => $deptFoh->id, 'name' => 'Server']);
        $posHost = Position::updateOrCreate(['code' => 'HT-09'], ['department_id' => $deptFoh->id, 'name' => 'Host']);

        // 8. Shift Templates
        $shiftMorning = ShiftTemplate::updateOrCreate(
            ['code' => '0600'],
            [
                'name' => '0600 = 6AM - 3PM',
                'start_time' => '06:00:00',
                'end_time' => '15:00:00',
                'break_minutes' => 60,
                'color' => '#10b981',
                'description' => null,
                'is_overnight' => false,
            ]
        );

        $shiftAfternoon = ShiftTemplate::updateOrCreate(
            ['code' => '1400'],
            [
                'name' => '1400 = 2PM - 11PM',
                'start_time' => '14:00:00',
                'end_time' => '23:00:00',
                'break_minutes' => 60,
                'color' => '#f97316',
                'description' => null,
                'is_overnight' => false,
            ]
        );

        $shiftOvernight = ShiftTemplate::updateOrCreate(
            ['code' => '2200'],
            [
                'name' => '2200 = 10PM - 7AM',
                'start_time' => '22:00:00',
                'end_time' => '07:00:00',
                'break_minutes' => 60,
                'color' => '#8b5cf6',
                'description' => null,
                'is_overnight' => true,
            ]
        );

        // 9. Statutory Rules (Philippine SSS, PhilHealth, PagIBIG, BIR Tax)
        StatutoryContributionRule::updateOrCreate(
            ['rule_name' => 'SSS Standard Table 2024-2026'],
            [
                'rule_type' => 'SSS',
                'rate' => 0.1400,
                'min_salary' => 4000.00,
                'max_salary' => 30000.00,
                'employee_share' => 0.0450,
                'employer_share' => 0.0950,
                'effective_date' => '2024-01-01',
                'is_active' => true,
            ]
        );

        StatutoryContributionRule::updateOrCreate(
            ['rule_name' => 'PhilHealth Universal Health Care (5%)'],
            [
                'rule_type' => 'PhilHealth',
                'rate' => 0.0500,
                'min_salary' => 10000.00,
                'max_salary' => 100000.00,
                'employee_share' => 0.5000,
                'employer_share' => 0.5000,
                'effective_date' => '2024-01-01',
                'is_active' => true,
            ]
        );

        StatutoryContributionRule::updateOrCreate(
            ['rule_name' => 'Pag-IBIG Fund Modern Maximum'],
            [
                'rule_type' => 'PagIBIG',
                'fixed_amount' => 200.00,
                'employee_share' => 0.0000,
                'employer_share' => 0.0000,
                'effective_date' => '2024-02-01',
                'is_active' => true,
            ]
        );

        StatutoryContributionRule::updateOrCreate(
            ['rule_name' => 'BIR Revised Withholding Tax (TRAIN Law)'],
            [
                'rule_type' => 'BIR_Tax',
                'effective_date' => '2023-01-01',
                'is_active' => true,
            ]
        );

        // 10. Leave Types
        $leaveTypes = [
            ['code' => 'VL', 'name' => 'Vacation Leave', 'is_paid' => true, 'default_credits' => 15.0],
            ['code' => 'SL', 'name' => 'Sick Leave', 'is_paid' => true, 'default_credits' => 15.0],
            ['code' => 'EL', 'name' => 'Emergency Leave', 'is_paid' => true, 'default_credits' => 5.0],
            ['code' => 'SIL', 'name' => 'Service Incentive Leave', 'is_paid' => true, 'default_credits' => 5.0],
            ['code' => 'ML', 'name' => 'Maternity Leave', 'is_paid' => true, 'default_credits' => 105.0],
            ['code' => 'PL', 'name' => 'Paternity Leave', 'is_paid' => true, 'default_credits' => 7.0],
            ['code' => 'SPL', 'name' => 'Solo Parent Leave', 'is_paid' => true, 'default_credits' => 7.0],
            ['code' => 'BL', 'name' => 'Bereavement Leave', 'is_paid' => true, 'default_credits' => 3.0],
            ['code' => 'OCL', 'name' => 'Other Company Leave', 'is_paid' => false, 'default_credits' => 0.0],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::updateOrCreate(['code' => $lt['code']], $lt);
        }

        // 11. Performance Criteria
        $criteria = [
            'Attendance' => 10,
            'Punctuality' => 10,
            'Work Quality' => 15,
            'Customer Service' => 15,
            'Teamwork' => 10,
            'Productivity' => 10,
            'Communication' => 5,
            'Cleanliness' => 10,
            'Food Safety Compliance' => 10,
            'Following Procedures' => 5,
            'Initiative' => 5,
        ];
        foreach ($criteria as $cName => $weight) {
            PerformanceCriterion::updateOrCreate(
                ['name' => $cName],
                ['weight_percentage' => $weight, 'is_active' => true]
            );
        }

        PerformancePeriod::updateOrCreate(
            ['name' => 'Q3 2026 Restaurant Performance Review'],
            ['start_date' => '2026-07-01', 'end_date' => '2026-09-30', 'status' => 'Active']
        );

        // 12. Training Programs
        $progFoodSafety = TrainingProgram::updateOrCreate(
            ['name' => 'HACCP & Restaurant Food Safety Standards'],
            [
                'description' => 'Mandatory sanitation, temperature logs, allergen management and cross-contamination prevention.',
                'trainer' => 'Chef Anthony Rivera (Certified Food Safety Specialist)',
                'training_type' => 'Internal',
                'location' => 'Makati Training Kitchen',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-11',
                'duration_hours' => 8,
                'cost' => 5000.00,
                'status' => 'Completed',
            ]
        );

        TrainingProgram::updateOrCreate(
            ['name' => 'Hospitality & Guest Experience Mastery'],
            [
                'description' => 'Service standards, customer conflict resolution, wine and menu pairing.',
                'trainer' => 'Maria Santos (F&B Consultant)',
                'training_type' => 'Internal',
                'location' => 'BGC Dining Hall',
                'start_date' => '2026-09-28',
                'end_date' => '2026-09-29',
                'duration_hours' => 6,
                'cost' => 4500.00,
                'status' => 'Scheduled',
            ]
        );

        // 13. System Users & Roles
        $adminUser = User::where('username', 'peter')->first() ?? User::first();
        if ($adminUser) {
            $adminUser->branch_id = null; // Full multi-branch access
            $adminUser->save();
            $adminUser->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        $hrUser = User::updateOrCreate(
            ['username' => 'hr_admin'],
            [
                'full_name' => 'Elena Reyes (HR Manager)',
                'email' => 'hr@rusticrestaurant.ph',
                'password' => Hash::make('HrAdmin@12345'),
                'role' => 'Admin',
                'status' => 'Active',
                'branch_id' => null, // Multi-branch HR
            ]
        );
        $hrUser->roles()->sync([$hrAdminRole->id]);

        $mgrMakati = User::updateOrCreate(
            ['username' => 'manager_makati'],
            [
                'full_name' => 'Carlos Mendoza (Makati RM)',
                'email' => 'carlos.mendoza@rusticrestaurant.ph',
                'password' => Hash::make('Manager@12345'),
                'role' => 'Manager',
                'status' => 'Active',
                'branch_id' => $branchMakati->id,
            ]
        );
        $mgrMakati->roles()->sync([$managerRole->id]);

        $mgrBgc = User::updateOrCreate(
            ['username' => 'manager_bgc'],
            [
                'full_name' => 'Beatriz Valdez (BGC RM)',
                'email' => 'beatriz.valdez@rusticrestaurant.ph',
                'password' => Hash::make('Manager@12345'),
                'role' => 'Manager',
                'status' => 'Active',
                'branch_id' => $branchBgc->id,
            ]
        );
        $mgrBgc->roles()->sync([$managerRole->id]);

        // 14. Seed Realistic Restaurant Employees
        $sampleEmployees = [
            [
                'employee_id' => 'EMP-2026-001',
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'date_of_birth' => '1988-04-12',
                'gender' => 'Male',
                'civil_status' => 'Married',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234501',
                'email' => 'carlos.mendoza@rusticrestaurant.ph',
                'address' => 'Poblacion, Makati City',
                'branch_id' => $branchMakati->id,
                'department_id' => $deptMgmt->id,
                'position_id' => $posManager->id,
                'user_id' => $mgrMakati->id,
                'date_hired' => '2023-01-15',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2023-07-15',
                'sss_number' => '34-1234567-8',
                'philhealth_number' => '12-345678901-2',
                'pagibig_number' => '1210-9876-5432',
                'tin' => '234-567-890-000',
                'basic_salary' => 45000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 5000.00,
            ],
            [
                'employee_id' => 'EMP-2026-002',
                'first_name' => 'Rodrigo',
                'last_name' => 'Santos',
                'date_of_birth' => '1992-08-20',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234502',
                'email' => 'rodrigo.santos@rusticrestaurant.ph',
                'address' => 'Guadalupe Nuevo, Makati City',
                'branch_id' => $branchMakati->id,
                'department_id' => $deptBoh->id,
                'position_id' => $posHeadCook->id,
                'date_hired' => '2023-03-01',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2023-09-01',
                'sss_number' => '34-2345678-9',
                'philhealth_number' => '12-456789012-3',
                'pagibig_number' => '1210-8765-4321',
                'tin' => '345-678-901-000',
                'basic_salary' => 32000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 3000.00,
            ],
            [
                'employee_id' => 'EMP-2026-003',
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'date_of_birth' => '1998-11-05',
                'gender' => 'Female',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234503',
                'email' => 'maria.garcia@rusticrestaurant.ph',
                'address' => 'San Antonio Village, Makati City',
                'branch_id' => $branchMakati->id,
                'department_id' => $deptFoh->id,
                'position_id' => $posCashier->id,
                'date_hired' => '2024-02-15',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2024-08-15',
                'sss_number' => '34-3456789-0',
                'philhealth_number' => '12-567890123-4',
                'pagibig_number' => '1210-7654-3210',
                'tin' => '456-789-012-000',
                'basic_salary' => 20000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 1500.00,
            ],
            [
                'employee_id' => 'EMP-2026-004',
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'date_of_birth' => '2001-02-14',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234504',
                'email' => 'juan.delacruz@rusticrestaurant.ph',
                'address' => 'Pio Del Pilar, Makati City',
                'branch_id' => $branchMakati->id,
                'department_id' => $deptFoh->id,
                'position_id' => $posServer->id,
                'date_hired' => '2026-04-01',
                'employment_status' => 'Probationary',
                'employment_type' => 'Probationary',
                'contract_start_date' => '2026-04-01',
                'contract_end_date' => '2026-10-01',
                'sss_number' => '34-4567890-1',
                'philhealth_number' => '12-678901234-5',
                'pagibig_number' => '1210-6543-2109',
                'tin' => '567-890-123-000',
                'basic_salary' => 17000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 1000.00,
            ],
            [
                'employee_id' => 'EMP-2026-005',
                'first_name' => 'Beatriz',
                'last_name' => 'Valdez',
                'date_of_birth' => '1990-07-22',
                'gender' => 'Female',
                'civil_status' => 'Married',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234505',
                'email' => 'beatriz.valdez@rusticrestaurant.ph',
                'address' => 'Pembo, Taguig City',
                'branch_id' => $branchBgc->id,
                'department_id' => $deptMgmt->id,
                'position_id' => $posManager->id,
                'user_id' => $mgrBgc->id,
                'date_hired' => '2023-06-01',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2023-12-01',
                'sss_number' => '34-5678901-2',
                'philhealth_number' => '12-789012345-6',
                'pagibig_number' => '1210-5432-1098',
                'tin' => '678-901-234-000',
                'basic_salary' => 45000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 5000.00,
            ],
            [
                'employee_id' => 'EMP-2026-006',
                'first_name' => 'Gabriel',
                'last_name' => 'Tan',
                'date_of_birth' => '1995-09-30',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234506',
                'email' => 'gabriel.tan@rusticrestaurant.ph',
                'address' => 'Comembo, Taguig City',
                'branch_id' => $branchBgc->id,
                'department_id' => $deptBoh->id,
                'position_id' => $posCook->id,
                'date_hired' => '2024-01-10',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2024-07-10',
                'sss_number' => '34-6789012-3',
                'philhealth_number' => '12-890123456-7',
                'pagibig_number' => '1210-4321-0987',
                'tin' => '789-012-345-000',
                'basic_salary' => 22000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 1500.00,
            ],
            [
                'employee_id' => 'EMP-2026-007',
                'first_name' => 'Krizza',
                'last_name' => 'Navarro',
                'date_of_birth' => '2000-05-18',
                'gender' => 'Female',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234507',
                'email' => 'krizza.navarro@rusticrestaurant.ph',
                'address' => 'East Rembo, Taguig City',
                'branch_id' => $branchBgc->id,
                'department_id' => $deptFoh->id,
                'position_id' => $posHost->id,
                'date_hired' => '2026-05-01',
                'employment_status' => 'Probationary',
                'employment_type' => 'Probationary',
                'contract_start_date' => '2026-05-01',
                'contract_end_date' => '2026-11-01',
                'sss_number' => '34-7890123-4',
                'philhealth_number' => '12-901234567-8',
                'pagibig_number' => '1210-3210-9876',
                'tin' => '890-123-456-000',
                'basic_salary' => 18000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 1000.00,
            ],
            [
                'employee_id' => 'EMP-2026-008',
                'first_name' => 'Eduardo',
                'last_name' => 'Ramos',
                'date_of_birth' => '1993-12-08',
                'gender' => 'Male',
                'civil_status' => 'Married',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234508',
                'email' => 'eduardo.ramos@rusticrestaurant.ph',
                'address' => 'Kamuning, Quezon City',
                'branch_id' => $branchQc->id,
                'department_id' => $deptBoh->id,
                'position_id' => $posHeadCook->id,
                'date_hired' => '2023-08-01',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2024-02-01',
                'sss_number' => '34-8901234-5',
                'philhealth_number' => '12-012345678-9',
                'pagibig_number' => '1210-2109-8765',
                'tin' => '901-234-567-000',
                'basic_salary' => 30000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 2500.00,
            ],
            [
                'employee_id' => 'EMP-2026-009',
                'first_name' => 'Patricia',
                'last_name' => 'Lim',
                'date_of_birth' => '1999-03-25',
                'gender' => 'Female',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234509',
                'email' => 'patricia.lim@rusticrestaurant.ph',
                'address' => 'Scout Rallos, Quezon City',
                'branch_id' => $branchQc->id,
                'department_id' => $deptFoh->id,
                'position_id' => $posServer->id,
                'date_hired' => '2024-04-10',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2024-10-10',
                'sss_number' => '34-9012345-6',
                'philhealth_number' => '12-123450987-1',
                'pagibig_number' => '1210-1098-7654',
                'tin' => '012-345-678-000',
                'basic_salary' => 17500.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 1000.00,
            ],
            [
                'employee_id' => 'EMP-2026-010',
                'first_name' => 'Mark',
                'last_name' => 'Villanueva',
                'date_of_birth' => '1997-06-15',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'nationality' => 'Filipino',
                'mobile_number' => '09171234510',
                'email' => 'mark.villanueva@rusticrestaurant.ph',
                'address' => 'Teachers Village, Quezon City',
                'branch_id' => $branchQc->id,
                'department_id' => $deptBoh->id,
                'position_id' => $posDishwasher->id,
                'date_hired' => '2025-01-15',
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'date_of_regularization' => '2025-07-15',
                'sss_number' => '34-0123456-7',
                'philhealth_number' => '12-234561098-2',
                'pagibig_number' => '1210-0987-6543',
                'tin' => '123-789-456-000',
                'basic_salary' => 16000.00,
                'salary_type' => 'Monthly',
                'pay_frequency' => 'Semi-Monthly',
                'allowances' => 1000.00,
            ],
        ];

        $createdEmployees = [];
        foreach ($sampleEmployees as $empData) {
            $emp = Employee::updateOrCreate(
                ['employee_id' => $empData['employee_id']],
                $empData
            );
            $createdEmployees[] = $emp;

            // Emergency contact
            EmergencyContact::updateOrCreate(
                ['employee_id' => $emp->id],
                [
                    'contact_name' => 'Family Contact of ' . $emp->first_name,
                    'relationship' => 'Parent / Spouse',
                    'contact_number' => '09189998888',
                    'address' => $emp->address,
                ]
            );

            // Leave balances for year 2026
            $dbLeaveTypes = LeaveType::all();
            foreach ($dbLeaveTypes as $lt) {
                LeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'leave_type_id' => $lt->id,
                        'year' => 2026,
                    ],
                    [
                        'beginning_balance' => $lt->default_credits,
                        'earned' => 0.00,
                        'used' => ($lt->code === 'VL' && $emp->id % 2 === 0) ? 2.0 : 0.0,
                        'remaining' => ($lt->code === 'VL' && $emp->id % 2 === 0) ? ($lt->default_credits - 2.0) : $lt->default_credits,
                        'encashed' => 0.00,
                    ]
                );
            }

            // Training enrollment in Food Safety
            TrainingEnrollment::updateOrCreate(
                [
                    'training_program_id' => $progFoodSafety->id,
                    'employee_id' => $emp->id,
                ],
                [
                    'enrollment_date' => '2026-09-05',
                    'attendance_status' => 'Present',
                    'completion_status' => 'Completed',
                    'score' => 95.0,
                    'completion_date' => '2026-09-11',
                    'remarks' => 'Successfully passed restaurant food sanitation exam.',
                ]
            );
        }

        // 15. Seed Leave Requests
        $vlType = LeaveType::where('code', 'VL')->first();
        if ($vlType && count($createdEmployees) >= 4) {
            LeaveRequest::updateOrCreate(
                [
                    'employee_id' => $createdEmployees[2]->id,
                    'start_date' => '2026-09-22',
                    'end_date' => '2026-09-23',
                ],
                [
                    'leave_type_id' => $vlType->id,
                    'number_of_days' => 2.0,
                    'reason' => 'Annual family vacation leave',
                    'status' => 'Approved',
                    'approver_id' => $mgrMakati->id,
                    'approval_date' => '2026-09-20 14:00:00',
                ]
            );

            LeaveRequest::updateOrCreate(
                [
                    'employee_id' => $createdEmployees[3]->id,
                    'start_date' => '2026-09-27',
                    'end_date' => '2026-09-27',
                ],
                [
                    'leave_type_id' => $vlType->id,
                    'number_of_days' => 1.0,
                    'reason' => 'Personal errand and rest',
                    'status' => 'Pending',
                ]
            );
        }

        // 16. Seed Shift Schedules and Attendance Records
        $attCalcService = new AttendanceCalculationService();
        $today = Carbon::today();

        foreach ($createdEmployees as $idx => $emp) {
            $assignedShift = ($idx % 3 === 0) ? $shiftMorning : (($idx % 3 === 1) ? $shiftAfternoon : $shiftOvernight);

            // Generate past 7 days of schedules & attendance
            for ($daysAgo = 7; $daysAgo >= 0; $daysAgo--) {
                $targetDate = $today->copy()->subDays($daysAgo)->toDateString();
                $isRestDay = ($daysAgo === 1);

                // Create schedule
                $schedule = EmployeeSchedule::updateOrCreate(
                    ['employee_id' => $emp->id, 'schedule_date' => $targetDate],
                    [
                        'branch_id' => $emp->branch_id,
                        'shift_template_id' => $assignedShift->id,
                        'custom_start_time' => $assignedShift->start_time,
                        'custom_end_time' => $assignedShift->end_time,
                        'is_rest_day' => $isRestDay,
                        'notes' => $isRestDay ? 'Scheduled rest day' : 'Regular restaurant shift',
                    ]
                );

                if ($isRestDay) {
                    AttendanceRecord::updateOrCreate(
                        ['employee_id' => $emp->id, 'date' => $targetDate],
                        [
                            'schedule_id' => $schedule->id,
                            'branch_id' => $emp->branch_id,
                            'status' => 'Rest Day',
                            'is_rest_day' => true,
                            'source' => 'Web',
                        ]
                    );
                    continue;
                }

                // Simulate realistic clock times
                $timeIn = $assignedShift->start_time;
                $timeOut = $assignedShift->end_time;

                if ($daysAgo === 2 && $idx % 2 === 0) {
                    $timeIn = Carbon::parse($timeIn)->addMinutes(25)->toTimeString();
                }
                if ($daysAgo === 3 && $idx % 2 === 1) {
                    $timeOut = Carbon::parse($timeOut)->addHours(2)->toTimeString();
                }

                $calc = $attCalcService->calculate(
                    $targetDate,
                    $assignedShift->start_time,
                    $assignedShift->end_time,
                    $timeIn,
                    $timeOut,
                    null,
                    null,
                    (bool) $assignedShift->is_overnight
                );

                AttendanceRecord::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => $targetDate],
                    [
                        'schedule_id' => $schedule->id,
                        'branch_id' => $emp->branch_id,
                        'time_in' => $timeIn,
                        'time_out' => $timeOut,
                        'break_out' => '12:00:00',
                        'break_in' => '13:00:00',
                        'total_hours' => $calc['total_hours'],
                        'regular_hours' => $calc['regular_hours'],
                        'late_minutes' => $calc['late_minutes'],
                        'undertime_minutes' => $calc['undertime_minutes'],
                        'overtime_hours' => $calc['overtime_hours'],
                        'night_diff_hours' => $calc['night_diff_hours'],
                        'holiday_type' => 'None',
                        'is_rest_day' => false,
                        'status' => $calc['status'],
                        'source' => 'Biometric',
                        'notes' => 'Logged via biometric station',
                    ]
                );
            }
        }

        // 17. Seed Sample Attendance Correction Request
        if (count($createdEmployees) > 1) {
            $sampleAtt = AttendanceRecord::where('employee_id', $createdEmployees[1]->id)->latest('date')->first();
            if ($sampleAtt) {
                AttendanceCorrection::updateOrCreate(
                    ['attendance_record_id' => $sampleAtt->id],
                    [
                        'employee_id' => $sampleAtt->employee_id,
                        'requested_by' => $mgrMakati->id,
                        'original_time_in' => $sampleAtt->time_in,
                        'original_time_out' => $sampleAtt->time_out,
                        'requested_time_in' => Carbon::parse($sampleAtt->time_in)->subMinutes(20)->toTimeString(),
                        'requested_time_out' => $sampleAtt->time_out,
                        'reason' => 'Biometric machine scanner delayed clock-in during peak lunch prep.',
                        'status' => 'Pending',
                    ]
                );
            }
        }

        // 18. Seed Recruitment (Job Vacancies, Applicants, Interviews)
        $vacLineCook = JobVacancy::updateOrCreate(
            ['title' => 'Line Cook / Griller'],
            [
                'position_id' => $posCook->id,
                'department_id' => $deptBoh->id,
                'branch_id' => $branchBgc->id,
                'number_of_openings' => 2,
                'employment_type' => 'Regular',
                'salary_range_min' => 20000.00,
                'salary_range_max' => 25000.00,
                'job_description' => 'Responsible for line preparation, grilling, and compliance with kitchen ticket times.',
                'requirements' => 'Minimum 1 year culinary experience in commercial restaurants. NC II Cookery preferred.',
                'opening_date' => '2026-09-01',
                'status' => 'Open',
            ]
        );

        $applicant1 = Applicant::updateOrCreate(
            ['email' => 'miguel.torres@email.com'],
            [
                'job_vacancy_id' => $vacLineCook->id,
                'first_name' => 'Miguel',
                'last_name' => 'Torres',
                'contact_number' => '09175551234',
                'address' => 'Signal Village, Taguig City',
                'applied_position' => 'Line Cook / Griller',
                'source' => 'Walk-in',
                'application_date' => '2026-09-15',
                'status' => 'Interview',
            ]
        );

        Interview::updateOrCreate(
            ['applicant_id' => $applicant1->id],
            [
                'interview_date' => '2026-09-25 15:00:00',
                'interviewer_id' => $mgrBgc->id,
                'interviewer_name' => 'Beatriz Valdez',
                'interview_type' => 'Technical / Practical',
                'notes' => 'Practical cooking demonstration (knife skills and line coordination).',
                'status' => 'Scheduled',
            ]
        );

        // 19. Seed Active Semi-Monthly Payroll Period and Process Records
        $payrollPeriod = PayrollPeriod::updateOrCreate(
            ['name' => 'September 2026 - 1st Half'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-15',
                'payout_date' => '2026-09-20',
                'pay_frequency' => 'Semi-Monthly',
                'status' => 'Approved',
                'processed_by' => $hrUser->id,
                'approved_by' => $adminUser?->id,
            ]
        );

        // Run payroll calculations for this seeded period
        $statutoryService = new StatutoryContributionService();
        $payrollService = new PayrollCalculationService($statutoryService);
        $payrollService->calculatePeriod($payrollPeriod);

        // 20. Seed Internal Administrative Notifications
        InternalNotification::updateOrCreate(
            ['title' => 'Leave Request Pending Review'],
            [
                'user_id' => $mgrMakati->id,
                'role_target' => 'Restaurant Manager',
                'branch_id' => $branchMakati->id,
                'message' => 'Server Juan Dela Cruz submitted a 1-day Vacation Leave request for Sep 27, 2026.',
                'type' => 'leave',
                'link' => '/hr/leave/requests',
                'is_read' => false,
            ]
        );

        InternalNotification::updateOrCreate(
            ['title' => 'Upcoming Contract Regularization'],
            [
                'user_id' => $hrUser->id,
                'role_target' => 'HR/Admin',
                'message' => 'Employee Juan Dela Cruz reaches 6-month probationary evaluation next month.',
                'type' => 'regularization',
                'link' => '/hr/people/employees',
                'is_read' => false,
            ]
        );

        // 21. Initial Audit Log
        AuditLog::create([
            'user_id' => $adminUser?->id,
            'action' => 'Create',
            'module' => 'Settings',
            'record_id' => 'INIT-HRIS',
            'ip_address' => '127.0.0.1',
            'details' => 'Initialized restaurant HR operations structure, branches, roles, statutory rules, and initial workforce.',
            'created_at' => now(),
        ]);
    }
}
