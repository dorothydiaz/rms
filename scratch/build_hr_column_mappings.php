<?php

$hrTables = json_decode(file_get_contents('scratch/current_hr_columns.json'), true);

// Table name to entity prefix mapping
$prefixMap = [
    'hr_applicants'                   => 'applicant_',
    'hr_assessments'                  => 'assessment_',
    'hr_attendance_action_logs'       => 'attendance_action_log_',
    'hr_attendance_corrections'       => 'attendance_correction_',
    'hr_attendance_records'           => 'attendance_record_',
    'hr_branches'                     => 'branch_',
    'hr_companies'                    => 'company_',
    'hr_departments'                  => 'department_',
    'hr_dtr_absence_codes'            => 'dtr_absence_code_',
    'hr_emergency_contacts'           => 'emergency_contact_',
    'hr_employee_clearance'           => 'employee_clearance_',
    'hr_employee_disciplinary'        => 'employee_disciplinary_',
    'hr_employee_documents'           => 'employee_document_',
    'hr_employee_education'           => 'employee_education_',
    'hr_employee_family'              => 'employee_family_',
    'hr_employee_permissions'         => 'employee_permission_',
    'hr_employee_schedules'           => 'employee_schedule_',
    'hr_employees'                    => 'employee_',
    'hr_employment_histories'         => 'employment_history_',
    'hr_holiday_locations'            => 'holiday_location_',
    'hr_holiday_pay_rules'            => 'holiday_pay_rule_',
    'hr_holidays'                     => 'holiday_',
    'hr_interviews'                   => 'interview_',
    'hr_job_levels'                   => 'job_level_',
    'hr_job_vacancies'                => 'job_vacancy_',
    'hr_leave_balances'               => 'leave_balance_',
    'hr_leave_requests'               => 'leave_request_',
    'hr_leave_types'                  => 'leave_type_',
    'hr_payroll_adjustments'          => 'payroll_adjustment_',
    'hr_payroll_periods'              => 'payroll_period_',
    'hr_payroll_records'              => 'payroll_record_',
    'hr_performance_criteria'         => 'performance_criteria_',
    'hr_performance_evaluations'      => 'performance_evaluation_',
    'hr_performance_periods'          => 'performance_period_',
    'hr_performance_ratings'          => 'performance_rating_',
    'hr_positions'                    => 'position_',
    'hr_premium_pay_items'            => 'premium_pay_item_',
    'hr_premium_pay_rule_versions'    => 'premium_pay_rule_version_',
    'hr_premium_pay_rules'            => 'premium_pay_rule_',
    'hr_salary_history'               => 'salary_history_',
    'hr_schedule_change_logs'         => 'schedule_change_log_',
    'hr_separation'                   => 'separation_',
    'hr_shift_templates'              => 'shift_template_',
    'hr_statutory_contribution_rules' => 'statutory_contribution_rule_',
    'hr_training_enrollments'         => 'training_enrollment_',
    'hr_training_programs'            => 'training_program_',
];

$renames = [];

foreach ($hrTables as $table => $columns) {
    if (!isset($prefixMap[$table])) continue;
    $prefix = $prefixMap[$table];
    $tableRenames = [];

    foreach ($columns as $col) {
        // If column already starts with entity prefix, no rename needed
        if (str_starts_with($col, $prefix)) {
            continue;
        }

        // Special primary key mapping check: e.g. 'id' -> prefix . 'id'
        if ($col === 'id') {
            $newCol = $prefix . 'id';
            $tableRenames[$col] = $newCol;
            continue;
        }

        // Prefix foreign keys and standard attributes with entity prefix
        $newCol = $prefix . $col;

        $tableRenames[$col] = $newCol;
    }

    if (!empty($tableRenames)) {
        $renames[$table] = $tableRenames;
    }
}

file_put_contents('scratch/proposed_hr_column_renames.json', json_encode($renames, JSON_PRETTY_PRINT));

echo "Generated proposed renames for " . count($renames) . " HR tables.\n";
$totalCols = 0;
foreach ($renames as $tbl => $cols) {
    $totalCols += count($cols);
    echo "$tbl: " . count($cols) . " columns to rename\n";
}
echo "Total columns to rename: $totalCols\n";
