<?php

$plan = json_decode(file_get_contents('scratch/full_hr_schema_key_plan.json'), true);

$moduleMap = [
    'hr_applicants' => [
        'module' => 'Talent Acquisition',
        'submodule' => 'Applicants',
        'tab' => 'Applicant Directory & Onboarding Pipeline',
    ],
    'hr_assessments' => [
        'module' => 'Talent Acquisition',
        'submodule' => 'Applicants',
        'tab' => 'Applicant Assessments & Candidate Screening',
    ],
    'hr_attendance_action_logs' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Time-Keeping',
        'tab' => 'Action Logs & DTR Audit Trail',
    ],
    'hr_attendance_corrections' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Manual Time Entries',
        'tab' => 'Attendance Dispute & Time Corrections',
    ],
    'hr_attendance_records' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Time-Keeping / Overtime / Undertime',
        'tab' => 'Biometric DTR Logs, Overtime & Undertime Tracking',
    ],
    'hr_branches' => [
        'module' => 'Employee Management',
        'submodule' => 'Organization',
        'tab' => 'Branches',
    ],
    'hr_companies' => [
        'module' => 'Employee Management',
        'submodule' => 'Organization',
        'tab' => 'Company / Agency',
    ],
    'hr_departments' => [
        'module' => 'Employee Management',
        'submodule' => 'Organization',
        'tab' => 'Departments',
    ],
    'hr_dtr_absence_codes' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Manual Time Entries',
        'tab' => 'DTR Absence Codes Configuration',
    ],
    'hr_emergency_contacts' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => '201 File & Emergency Contacts',
    ],
    'hr_employee_clearance' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => 'Offboarding & Clearance Checklist',
    ],
    'hr_employee_disciplinary' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => '201 File & Disciplinary Actions / NTE',
    ],
    'hr_employee_documents' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => '201 File & Digital Document Vault',
    ],
    'hr_employee_education' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => '201 File & Educational Attainment',
    ],
    'hr_employee_family' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => '201 File & Family / Beneficiaries',
    ],
    'hr_employee_permissions' => [
        'module' => 'System Administration',
        'submodule' => 'Role & Permission',
        'tab' => 'User Account Granular Overrides',
    ],
    'hr_employee_schedules' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Schedules',
        'tab' => 'Roster Scheduling & Shift Assignments',
    ],
    'hr_employees' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => 'Workforce Directory & Complete 201 File',
    ],
    'hr_employment_histories' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => 'Career Movement & Promotion History',
    ],
    'hr_holiday_locations' => [
        'module' => 'Payroll',
        'submodule' => 'Holidays',
        'tab' => 'Regional / Branch Holiday Jurisdiction',
    ],
    'hr_holiday_pay_rules' => [
        'module' => 'Payroll',
        'submodule' => 'Holidays & Premium Pay',
        'tab' => 'DOLE Statutory Holiday Multipliers',
    ],
    'hr_holidays' => [
        'module' => 'Payroll',
        'submodule' => 'Holidays',
        'tab' => 'Philippine Holiday Calendar',
    ],
    'hr_interviews' => [
        'module' => 'Talent Acquisition',
        'submodule' => 'Interviews',
        'tab' => 'Interview Schedules & Scorecards',
    ],
    'hr_job_levels' => [
        'module' => 'Employee Management',
        'submodule' => 'Organization',
        'tab' => 'Job Grading & Corporate Ranks',
    ],
    'hr_job_vacancies' => [
        'module' => 'Talent Acquisition',
        'submodule' => 'Job Vacancies',
        'tab' => 'Job Requisition Builder & Open Positions',
    ],
    'hr_leave_balances' => [
        'module' => 'Leave & Absences',
        'submodule' => 'Leave Credits',
        'tab' => 'Annual Leave Ledger & Balances',
    ],
    'hr_leave_requests' => [
        'module' => 'Leave & Absences',
        'submodule' => 'Leave Requests',
        'tab' => 'Leave Applications & Approvals',
    ],
    'hr_leave_types' => [
        'module' => 'Leave & Absences',
        'submodule' => 'Leave Types',
        'tab' => 'Statutory & Company Leave Entitlements',
    ],
    'hr_payroll_adjustments' => [
        'module' => 'Payroll',
        'submodule' => 'Process Payroll / Payslips',
        'tab' => 'Ad-Hoc Earnings, Allowances & Deductions',
    ],
    'hr_payroll_periods' => [
        'module' => 'Payroll',
        'submodule' => 'Payroll Periods',
        'tab' => 'Semi-Monthly Cut-off Periods',
    ],
    'hr_payroll_records' => [
        'module' => 'Payroll',
        'submodule' => 'Payroll Register / Payslips',
        'tab' => 'Final Computed Payroll Register & Employee Payslips',
    ],
    'hr_performance_criteria' => [
        'module' => 'Performance Management',
        'submodule' => 'Criteria',
        'tab' => 'KPI Evaluation Metrics & Weights',
    ],
    'hr_performance_evaluations' => [
        'module' => 'Performance Management',
        'submodule' => 'Evaluations',
        'tab' => 'Staff Appraisal Reviews & Scorecards',
    ],
    'hr_performance_periods' => [
        'module' => 'Performance Management',
        'submodule' => 'Evaluation Periods',
        'tab' => 'Quarterly / Annual Review Cycles',
    ],
    'hr_performance_ratings' => [
        'module' => 'Performance Management',
        'submodule' => 'Evaluations',
        'tab' => 'Individual Criterion Performance Ratings',
    ],
    'hr_positions' => [
        'module' => 'Employee Management',
        'submodule' => 'Organization',
        'tab' => 'Positions',
    ],
    'hr_premium_pay_items' => [
        'module' => 'Payroll',
        'submodule' => 'Premium Pay',
        'tab' => 'Calculated Premium Pay Lines & Audit Trail',
    ],
    'hr_premium_pay_rule_versions' => [
        'module' => 'Payroll',
        'submodule' => 'Premium Pay',
        'tab' => 'Premium Pay Rule Versions & Snapshot History',
    ],
    'hr_premium_pay_rules' => [
        'module' => 'Payroll',
        'submodule' => 'Premium Pay',
        'tab' => 'Special / Regular Premium Pay Engine Rules',
    ],
    'hr_salary_history' => [
        'module' => 'Payroll / Employee Management',
        'submodule' => 'Wage Distortion Converter / 201 File',
        'tab' => 'Salary Adjustments & Wage Distortion Compliance',
    ],
    'hr_schedule_change_logs' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Schedules',
        'tab' => 'Shift Change History & Audit Logs',
    ],
    'hr_separation' => [
        'module' => 'Employee Management',
        'submodule' => 'Employee Management',
        'tab' => 'Employee Exit, Resignation & Separation Records',
    ],
    'hr_shift_templates' => [
        'module' => 'Time & Attendance',
        'submodule' => 'Schedules',
        'tab' => 'Shift Templates & Work Patterns',
    ],
    'hr_statutory_contribution_rules' => [
        'module' => 'Payroll',
        'submodule' => 'Statutory Rules',
        'tab' => 'SSS, PhilHealth & Pag-IBIG Contribution Tables',
    ],
    'hr_training_enrollments' => [
        'module' => 'Learning & Development',
        'submodule' => 'Training Records',
        'tab' => 'Staff Enrollment, Certificates & Completion Status',
    ],
    'hr_training_programs' => [
        'module' => 'Learning & Development',
        'submodule' => 'Training Programs',
        'tab' => 'Curriculum Catalogs & Training Modules',
    ],
];

// Enrich plan
foreach ($plan as $tableName => &$cols) {
    $info = $moduleMap[$tableName] ?? [
        'module' => 'HR Operations',
        'submodule' => 'General',
        'tab' => 'Operations',
    ];

    foreach ($cols as &$c) {
        $c['module'] = $info['module'];
        $c['submodule'] = $info['submodule'];
        $c['tab'] = $info['tab'];
    }
    unset($c);
}
unset($cols);

file_put_contents('scratch/full_hr_schema_key_plan.json', json_encode($plan, JSON_PRETTY_PRINT));
echo "Successfully enriched plan with module, submodule, and tab metadata.\n";
