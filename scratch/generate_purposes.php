<?php

$plan = json_decode(file_get_contents('scratch/full_hr_schema_key_plan.json'), true);

function determinePurpose($tableName, $colName, $proposedName, $keyType, $source, $dataType) {
    // 1. Primary Key
    if (str_contains($keyType, 'PRIMARY KEY')) {
        $cleanEntity = str_replace('hr_', '', $tableName);
        $cleanEntity = str_replace('_', ' ', $cleanEntity);
        return "Primary key; unique identifier for " . rtrim($cleanEntity, 's');
    }

    // 2. Foreign Key
    if (str_contains($keyType, 'FOREIGN KEY')) {
        $refTable = explode('(', $source)[0] ?? '';
        $refCol = '';
        if (preg_match('/\((.*?)\)/', $source, $m)) {
            $refCol = $m[1];
        }
        $context = "";
        if (str_contains($colName, 'applicant')) $context = "the applicant candidate";
        elseif (str_contains($colName, 'employee')) $context = "the employee record";
        elseif (str_contains($colName, 'user')) $context = "the system user account";
        elseif (str_contains($colName, 'branch')) $context = "the assigned operating branch";
        elseif (str_contains($colName, 'company')) $context = "the parent corporate entity";
        elseif (str_contains($colName, 'department')) $context = "the organizational department";
        elseif (str_contains($colName, 'position')) $context = "the job position";
        elseif (str_contains($colName, 'job_vacancy')) $context = "the published job requisition";
        elseif (str_contains($colName, 'shift_template')) $context = "the standard work shift schedule";
        elseif (str_contains($colName, 'leave_type')) $context = "the leave entitlement category";
        elseif (str_contains($colName, 'payroll_period')) $context = "the target pay cycle";
        elseif (str_contains($colName, 'payroll_record')) $context = "the employee payroll payslip record";
        elseif (str_contains($colName, 'holiday')) $context = "the calendar holiday definition";
        elseif (str_contains($colName, 'training')) $context = "the training curriculum program";
        elseif (str_contains($colName, 'evaluation')) $context = "the performance evaluation cycle";
        elseif (str_contains($colName, 'criterion')) $context = "the appraisal grading criterion";
        elseif (str_contains($colName, 'supervisor')) $context = "the reporting supervisor / manager";
        elseif (str_contains($colName, 'recruiter') || str_contains($colName, 'hiring_manager')) $context = "the assigned talent acquisition staff";
        elseif (str_contains($colName, 'approved_by') || str_contains($colName, 'processed_by') || str_contains($colName, 'recorded_by') || str_contains($colName, 'changed_by') || str_contains($colName, 'created_by') || str_contains($colName, 'updated_by')) {
            $context = "the authorizing user account";
        } else {
            $context = "the parent entity";
        }

        return "FK referencing $refTable ($context)";
    }

    // 3. Audit Timestamps
    if ($colName === 'created_at') return "Record creation timestamp";
    if ($colName === 'updated_at') return "Record last modification timestamp";
    if ($colName === 'deleted_at') return "Soft deletion timestamp for audit trail preservation";

    // 4. Common Status & Boolean flags
    if ($colName === 'is_active') return "Operational status flag (1 = active, 0 = inactive)";
    if ($colName === 'status') return "Current lifecycle workflow status";

    // 5. Specific attribute rules
    $specifics = [
        // Names & Demographics
        'first_name' => 'Legal personal first name',
        'middle_name' => 'Legal middle name or initial',
        'last_name' => 'Legal surname / family name',
        'suffix' => 'Name generational suffix (Jr., Sr., III, etc.)',
        'preferred_name' => 'Informal preferred nickname or alias',
        'date_of_birth' => 'Date of birth for age verification and statutory benefits',
        'birth_place' => 'City or municipality of birth',
        'gender' => 'Gender identification for statutory reporting',
        'sex' => 'Biological sex',
        'civil_status' => 'Marital status (Single, Married, Widowed, Separated)',
        'nationality' => 'Citizenship / legal nationality (e.g., Filipino)',
        
        // Contacts & Address
        'email' => 'Primary personal or contact email address',
        'company_email' => 'Official corporate work email address',
        'contact_number' => 'Primary contact telephone or mobile number',
        'mobile_number' => 'Primary cellular mobile contact number',
        'telephone_number' => 'Landline or secondary telephone contact',
        'address' => 'Current residential street address',
        'permanent_address' => 'Official permanent home address',
        'city' => 'City or municipality',
        'province' => 'Province or state administrative jurisdiction',
        'postal_code' => 'ZIP or postal mail routing code',
        'country' => 'ISO country name or code',
        'photo' => 'Storage path or URL for identification photograph',
        
        // Employment & Workforce
        'employee_code' => 'Unique employee corporate ID badge number (e.g., EMP-2026-001)',
        'work_location' => 'Designated primary physical workplace location',
        'work_schedule' => 'Assigned working shift pattern description',
        'job_level' => 'Corporate grade tier or rank classification',
        'employment_status' => 'Workforce standing (Active, On Leave, Suspended, Terminated)',
        'employment_type' => 'Contract type (Regular, Probationary, Contractual, Part-Time)',
        'company_name' => 'Designated deploying company legal entity name',
        'agency_name' => 'Staffing vendor or subcontractor agency name (if outsourced)',
        'employment_source' => 'Talent acquisition channel (Direct Hire, Agency, Referral, Walk-in)',
        'date_hired' => 'Official employment start / hire date',
        'date_of_regularization' => 'Date of regular employment status conferment',
        'probation_end_date' => 'End of probationary evaluation period',
        'date_separated' => 'Official employment separation or exit date',
        'contract_start_date' => 'Employment agreement commencement date',
        'contract_end_date' => 'Fixed-term employment agreement expiration date',
        
        // Statutory & Government Numbers
        'sss_number' => 'Social Security System (SSS) 10-digit registration number',
        'sss_verified' => 'Verification flag confirming SSS credential validation',
        'sss_membership_status' => 'SSS active contribution status',
        'philhealth_number' => 'Philippine Health Insurance Corporation (PhilHealth) PIN',
        'philhealth_verified' => 'Verification flag confirming PhilHealth validation',
        'philhealth_membership_status' => 'PhilHealth active contribution status',
        'pagibig_number' => 'Home Development Mutual Fund (Pag-IBIG / HDMF) MID number',
        'pagibig_verified' => 'Verification flag confirming Pag-IBIG validation',
        'pagibig_membership_status' => 'Pag-IBIG active contribution status',
        'tin' => 'Bureau of Internal Revenue (BIR) Tax Identification Number',
        'tin_verified' => 'Verification flag confirming BIR TIN validation',
        'rdo_code' => 'BIR Revenue District Office jurisdiction code (e.g., 044)',
        'philsys_id' => 'Philippine Identification System (PhilSys National ID) number',
        'passport_number' => 'Official government passport travel identification number',
        'driver_license' => 'Land Transportation Office (LTO) driver license credential',
        
        // Compensation & Payroll
        'basic_salary' => 'Contracted base periodic compensation rate',
        'payroll_type' => 'Pay basis method (Monthly Salaried, Daily Wage)',
        'salary_type' => 'Remuneration structure model',
        'pay_frequency' => 'Disbursement cycle (Semi-Monthly, Bi-Weekly, Monthly, Weekly)',
        'allowances' => 'Standard non-taxable or recurring allowance stipend',
        'other_compensation' => 'Detailed JSON breakdown of stipends, commissions, and bonuses',
        'gross_pay' => 'Total earnings before statutory and voluntary deductions',
        'net_pay' => 'Final take-home payout after all deductions and taxes',
        'basic_pay' => 'Pro-rated basic salary earned for actual regular days worked',
        'regular_hours' => 'Total standard non-overtime working hours rendered',
        'regular_hours_pay' => 'Compensation earned for standard working hours',
        'overtime_hours' => 'Approved extra working hours rendered beyond shift schedule',
        'overtime_pay' => 'Calculated premium compensation for overtime hours rendered',
        'night_diff_hours' => 'Hours rendered between 10:00 PM and 6:00 AM',
        'night_diff_pay' => 'Mandatory 10% night shift differential compensation',
        'holiday_pay' => 'Calculated premium compensation for work rendered on holidays',
        'rest_day_pay' => 'Calculated premium compensation for work rendered on rest days',
        'withholding_tax' => 'BIR graduated income withholding tax deduction',
        'sss_employee' => 'Employee share deduction for SSS monthly contribution',
        'sss_employer' => 'Employer counterpart share for SSS monthly contribution',
        'philhealth_employee' => 'Employee share deduction for PhilHealth premium',
        'philhealth_employer' => 'Employer counterpart share for PhilHealth premium',
        'pagibig_employee' => 'Employee share deduction for Pag-IBIG monthly savings',
        'pagibig_employer' => 'Employer counterpart share for Pag-IBIG monthly contribution',
        'late_deduction' => 'Computed salary penalty for tardy clock-ins',
        'undertime_deduction' => 'Computed salary deduction for early departures',
        'absence_deduction' => 'Computed salary deduction for unexcused unpaid absences',
        'total_deductions' => 'Total sum of all taxes, statutory shares, loans, and penalties',

        // DTR & Attendance Punches
        'date' => 'Attendance calendar log date',
        'time_in' => 'Initial morning shift clock-in timestamp',
        'time_out' => 'Final shift clock-out timestamp',
        'break_out' => 'Lunch break departure clock-out timestamp',
        'break_in' => 'Lunch break return clock-in timestamp',
        'coffee_break_out' => 'Afternoon coffee break start timestamp',
        'coffee_break_in' => 'Afternoon coffee break return timestamp',
        'in_1' => 'Punch 1 clock-in timestamp (6-punch biometric profile)',
        'out_1' => 'Punch 1 clock-out timestamp',
        'in_2' => 'Punch 2 clock-in timestamp',
        'out_2' => 'Punch 2 clock-out timestamp',
        'in_3' => 'Punch 3 clock-in timestamp',
        'out_3' => 'Punch 3 clock-out timestamp',
        'total_hours' => 'Total net working hours rendered after break deductions',
        'late_minutes' => 'Minutes elapsed past scheduled start time',
        'undertime_minutes' => 'Minutes missing prior to scheduled shift end time',
        'undertime_status' => 'Approval standing for undertime departure (PENDING, APPROVED, REJECTED)',
        'overtime_status' => 'Approval standing for overtime request (PENDING, APPROVED, REJECTED)',

        // Leave & Absences
        'start_date' => 'Commencement date of the approved leave schedule',
        'end_date' => 'Concluding date of the approved leave schedule',
        'number_of_days' => 'Total working days deducted from leave balance',
        'reason' => 'Formal justification provided by the requesting employee',
        'supporting_document' => 'File path to medical certificate or travel documentation',
        'beginning_balance' => 'Annual opening leave credit balance',
        'earned' => 'Accrued leave credits earned to date',
        'used' => 'Total leave credits consumed to date',
        'remaining' => 'Available balance of leave credits ready for application',
        'encashed' => 'Leave credits converted to cash payout during year-end settlement',

        // Recruitment & Vacancies
        'title' => 'Official published job requisition position title',
        'number_of_openings' => 'Total headcount authorized for recruitment',
        'work_setup' => 'Workplace arrangement (On-Site, Remote, Hybrid)',
        'salary_range_min' => 'Minimum baseline salary budget for the role',
        'salary_range_max' => 'Maximum approved salary ceiling for the role',
        'job_description' => 'Comprehensive functional summary of duties',
        'responsibilities' => 'Detailed list of core operational responsibilities',
        'qualifications' => 'Required academic, professional, and experience criteria',
        'resume_path' => 'Secure file system path to candidate uploaded resume',
        'applied_position' => 'Job vacancy role targeted by the applicant',
        'desired_salary' => 'Candidate requested compensation expectations',
        'years_of_experience' => 'Candidate verified career professional experience duration',
        'interview_date' => 'Scheduled calendar date for candidate assessment interview',
        'interview_time' => 'Scheduled time for candidate interview',
        'score' => 'Evaluation numerical score attained',
        'passing_score' => 'Minimum threshold score required to qualify',
        'recommendation' => 'Formal evaluator decision (HIRE, REJECT, SECOND_INTERVIEW)',
    ];

    if (isset($specifics[$colName])) {
        return $specifics[$colName];
    }

    // Generic heuristic
    $cleanCol = str_replace('_', ' ', $colName);
    return ucfirst($cleanCol) . " of " . str_replace('hr_', '', $tableName);
}

// Generate updated plan
$detailedPlan = [];
foreach ($plan as $table => $cols) {
    $detailedPlan[$table] = [];
    foreach ($cols as $c) {
        $purpose = determinePurpose($table, $c['original_column'], $c['proposed_column'], $c['key_type'], $c['source'], $c['data_type']);
        $c['purpose'] = $purpose;
        $detailedPlan[$table][] = $c;
    }
}

file_put_contents('scratch/full_hr_schema_key_plan.json', json_encode($detailedPlan, JSON_PRETTY_PRINT));
echo "Successfully assigned Purpose to all columns in 46 tables.\n";
