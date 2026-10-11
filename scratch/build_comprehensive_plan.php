<?php

$dbData = json_decode(file_get_contents('scratch/db_fk_dump.json'), true);
$foreignKeys = $dbData['foreign_keys'] ?? [];
$tableColumns = $dbData['table_columns'] ?? [];
$proposedRenames = json_decode(file_get_contents('scratch/proposed_hr_column_renames.json'), true);

// Standard table entity resolvers for logical foreign keys
$logicalFkMap = [
    'user_id' => 'users(user_id)',
    'created_by' => 'users(user_id)',
    'updated_by' => 'users(user_id)',
    'recorded_by' => 'users(user_id)',
    'changed_by' => 'users(user_id)',
    'processed_by' => 'users(user_id)',
    'approved_by' => 'users(user_id)',
    'uploaded_by' => 'users(user_id)',
    'verified_by' => 'users(user_id)',
    'rejected_by' => 'users(user_id)',
    'evaluator_id' => 'users(user_id)',
    'interviewer_id' => 'users(user_id)',
    'approver_id' => 'users(user_id)',
    'supervisor_id' => 'hr_employees(employee_id)',
    'recruiter_id' => 'users(user_id)',
    'hiring_manager_id' => 'users(user_id)',
    'manager_id' => 'hr_employees(employee_id)',
    'hired_as_employee_id' => 'hr_employees(employee_id)',
    'default_shift_template_id' => 'hr_shift_templates(shift_template_id)',
    'previous_shift_template_id' => 'hr_shift_templates(shift_template_id)',
    'new_shift_template_id' => 'hr_shift_templates(shift_template_id)',
    'shift_template_id' => 'hr_shift_templates(shift_template_id)',
    'holiday_id' => 'hr_holidays(holiday_id)',
    'leave_type_id' => 'hr_leave_types(leave_type_id)',
    'payroll_period_id' => 'hr_payroll_periods(payroll_period_id)',
    'payroll_record_id' => 'hr_payroll_records(payroll_record_id)',
    'performance_period_id' => 'hr_performance_periods(performance_period_id)',
    'criterion_id' => 'hr_performance_criteria(performance_criteria_id)',
    'evaluation_id' => 'hr_performance_evaluations(performance_evaluation_id)',
    'training_program_id' => 'hr_training_programs(training_program_id)',
    'permission_id' => 'hr_permissions(permission_id)',
    'role_id' => 'hr_roles(role_id)',
    'job_vacancy_id' => 'hr_job_vacancies(job_vacancy_id)',
    'applicant_id' => 'hr_applicants(applicant_id)',
    'branch_id' => 'hr_branches(branch_id)',
    'department_id' => 'hr_departments(department_id)',
    'position_id' => 'hr_positions(position_id)',
    'company_id' => 'hr_companies(company_id)',
    'employee_id' => 'hr_employees(employee_id)',
];

$nonFkIds = [
    'philsys_id',
    'company_tax_id',
    'tax_id',
    'tin',
    'rdo_code',
    'sss_number',
    'philhealth_number',
    'pagibig_number',
    'other_gov_id_number',
];

$plan = [];

foreach ($tableColumns as $table => $cols) {
    $plan[$table] = [];

    foreach ($cols as $colName => $meta) {
        $keyType = 'ATTRIBUTE';
        $source = '-';

        // Check if PK
        if ($meta['key'] === 'PRI' || $colName === 'id' || str_ends_with($colName, '_id') && in_array($colName, [
            'applicant_id', 'assessment_id', 'attendance_action_log_id', 'attendance_correction_id',
            'attendance_record_id', 'branch_id', 'company_id', 'department_id', 'dtr_absence_code_id',
            'emergency_contact_id', 'employee_clearance_id', 'employee_disciplinary_id',
            'employee_document_id', 'employee_education_id', 'employee_family_id', 'employee_schedule_id',
            'employee_id', 'employment_history_id', 'holiday_location_id', 'holiday_pay_rule_id',
            'holiday_id', 'interview_id', 'job_level_id', 'job_vacancy_id', 'leave_balance_id',
            'leave_request_id', 'leave_type_id', 'payroll_adjustment_id', 'payroll_period_id',
            'payroll_record_id', 'performance_criteria_id', 'performance_evaluation_id',
            'performance_period_id', 'performance_rating_id', 'position_id', 'premium_pay_item_id',
            'premium_pay_rule_version_id', 'premium_pay_rule_id', 'salary_history_id',
            'schedule_change_log_id', 'separation_id', 'shift_template_id',
            'statutory_contribution_rule_id', 'training_enrollment_id', 'training_program_id'
        ]) && $meta['key'] === 'PRI') {
            $keyType = 'PRIMARY KEY (PK)';
            $source = 'Local Table (' . $table . ')';
        } 
        // Check explicit FK from DB constraints
        elseif (isset($foreignKeys[$table][$colName])) {
            $keyType = 'FOREIGN KEY (FK)';
            $source = $foreignKeys[$table][$colName]['ref_table'] . '(' . $foreignKeys[$table][$colName]['ref_column'] . ')';
        }
        // Check logical FK ending in _id
        elseif (isset($logicalFkMap[$colName])) {
            $keyType = 'FOREIGN KEY (FK)';
            $source = $logicalFkMap[$colName];
        }
        elseif (in_array($colName, $nonFkIds)) {
            $keyType = 'ATTRIBUTE';
            $source = '-';
        }
        elseif (str_ends_with($colName, '_id')) {
            // Infer referenced table: e.g. branch_id -> hr_branches(branch_id), department_id -> hr_departments(department_id)
            $entity = substr($colName, 0, -3);
            $candidateTable = 'hr_' . $entity . 's';
            if (isset($tableColumns[$candidateTable])) {
                $keyType = 'FOREIGN KEY (FK)';
                $source = $candidateTable . '(' . $colName . ')';
            } elseif (isset($tableColumns['hr_' . $entity])) {
                $keyType = 'FOREIGN KEY (FK)';
                $source = 'hr_' . $entity . '(' . $colName . ')';
            } elseif ($entity === 'company') {
                $keyType = 'FOREIGN KEY (FK)';
                $source = 'hr_companies(company_id)';
            } elseif ($entity === 'user') {
                $keyType = 'FOREIGN KEY (FK)';
                $source = 'users(user_id)';
            } else {
                $keyType = 'FOREIGN KEY (FK)';
                $source = 'Inferred: ' . $colName;
            }
        }
        elseif (in_array($colName, ['created_at', 'updated_at', 'deleted_at'])) {
            $keyType = 'AUDIT TIMESTAMP';
            $source = 'System / Framework';
        }

        // Determine proposed new column name
        $proposedName = $proposedRenames[$table][$colName] ?? $colName;

        $plan[$table][] = [
            'original_column' => $colName,
            'proposed_column' => $proposedName,
            'key_type' => $keyType,
            'source' => $source,
            'data_type' => $meta['type'],
        ];
    }
}

file_put_contents('scratch/full_hr_schema_key_plan.json', json_encode($plan, JSON_PRETTY_PRINT));
echo "Built full key plan for " . count($plan) . " tables.\n";
