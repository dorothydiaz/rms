<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of HR tables and their primary key columns to create virtual 'id' columns.
     */
    protected array $tables = [
        'hr_applicants' => 'applicant_id',
        'hr_assessments' => 'assessment_id',
        'hr_attendance_action_logs' => 'attendance_action_log_id',
        'hr_attendance_corrections' => 'attendance_correction_id',
        'hr_attendance_records' => 'attendance_record_id',
        'hr_branches' => 'branch_id',
        'hr_companies' => 'company_id',
        'hr_departments' => 'department_id',
        'hr_dtr_absence_codes' => 'dtr_absence_code_id',
        'hr_emergency_contacts' => 'emergency_contact_id',
        'hr_employee_clearances' => 'employee_clearance_id',
        'hr_employee_disciplinaries' => 'employee_disciplinary_id',
        'hr_employee_documents' => 'employee_document_id',
        'hr_employee_educations' => 'employee_education_id',
        'hr_employee_families' => 'employee_family_id',
        'hr_employee_schedules' => 'employee_schedule_id',
        'hr_employees' => 'employee_id',
        'hr_employment_histories' => 'employment_history_id',
        'hr_holiday_locations' => 'holiday_location_id',
        'hr_holiday_pay_rules' => 'holiday_pay_rule_id',
        'hr_holidays' => 'holiday_id',
        'hr_interviews' => 'interview_id',
        'hr_job_levels' => 'job_level_id',
        'hr_job_vacancies' => 'job_vacancy_id',
        'hr_leave_balances' => 'leave_balance_id',
        'hr_leave_requests' => 'leave_request_id',
        'hr_leave_types' => 'leave_type_id',
        'hr_payroll_adjustments' => 'payroll_adjustment_id',
        'hr_payroll_periods' => 'payroll_period_id',
        'hr_payroll_records' => 'payroll_record_id',
        'hr_performance_criteria' => 'performance_criteria_id',
        'hr_performance_evaluations' => 'performance_evaluation_id',
        'hr_performance_periods' => 'performance_period_id',
        'hr_performance_ratings' => 'performance_rating_id',
        'hr_positions' => 'position_id',
        'hr_premium_pay_items' => 'premium_pay_item_id',
        'hr_premium_pay_rule_versions' => 'premium_pay_rule_version_id',
        'hr_premium_pay_rules' => 'premium_pay_rule_id',
        'hr_salary_histories' => 'salary_history_id',
        'hr_schedule_change_logs' => 'schedule_change_log_id',
        'hr_separations' => 'separation_id',
        'hr_shift_templates' => 'shift_template_id',
        'hr_statutory_contribution_rules' => 'statutory_contribution_rule_id',
        'hr_training_enrollments' => 'training_enrollment_id',
        'hr_training_programs' => 'training_program_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        foreach ($this->tables as $tableName => $pkCol) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'id') && Schema::hasColumn($tableName, $pkCol)) {
                if ($isSqlite) {
                    DB::statement("ALTER TABLE \"{$tableName}\" ADD COLUMN id INTEGER GENERATED ALWAYS AS ({$pkCol}) VIRTUAL");
                } else {
                    DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `id` BIGINT UNSIGNED GENERATED ALWAYS AS (`{$pkCol}`) VIRTUAL");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        foreach ($this->tables as $tableName => $pkCol) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'id')) {
                if ($isSqlite) {
                    try {
                        DB::statement("ALTER TABLE \"{$tableName}\" DROP COLUMN id");
                    } catch (\Throwable $e) {
                        // Older SQLite doesn't support drop column
                    }
                } else {
                    DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `id`");
                }
            }
        }
    }
};

