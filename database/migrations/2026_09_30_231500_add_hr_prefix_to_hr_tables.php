<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of old HR table names to new prefixed hr_ table names.
     */
    protected array $tables = [
        'applicants' => 'hr_applicants',
        'attendance_corrections' => 'hr_attendance_corrections',
        'attendance_records' => 'hr_attendance_records',
        'branches' => 'hr_branches',
        'companies' => 'hr_companies',
        'departments' => 'hr_departments',
        'dtr_absence_codes' => 'hr_dtr_absence_codes',
        'emergency_contacts' => 'hr_emergency_contacts',
        'employees' => 'hr_employees',
        'employee_documents' => 'hr_employee_documents',
        'employee_permissions' => 'hr_employee_permissions',
        'employee_schedules' => 'hr_employee_schedules',
        'employment_histories' => 'hr_employment_histories',
        'interviews' => 'hr_interviews',
        'job_levels' => 'hr_job_levels',
        'job_vacancies' => 'hr_job_vacancies',
        'leave_balances' => 'hr_leave_balances',
        'leave_requests' => 'hr_leave_requests',
        'leave_types' => 'hr_leave_types',
        'payroll_adjustments' => 'hr_payroll_adjustments',
        'payroll_periods' => 'hr_payroll_periods',
        'payroll_records' => 'hr_payroll_records',
        'performance_criteria' => 'hr_performance_criteria',
        'performance_evaluations' => 'hr_performance_evaluations',
        'performance_periods' => 'hr_performance_periods',
        'performance_ratings' => 'hr_performance_ratings',
        'positions' => 'hr_positions',
        'shift_templates' => 'hr_shift_templates',
        'statutory_contribution_rules' => 'hr_statutory_contribution_rules',
        'training_enrollments' => 'hr_training_enrollments',
        'training_programs' => 'hr_training_programs',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->tables as $old => $new) {
                if (Schema::hasTable($old) && !Schema::hasTable($new)) {
                    Schema::rename($old, $new);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->tables as $old => $new) {
                if (Schema::hasTable($new) && !Schema::hasTable($old)) {
                    Schema::rename($new, $old);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
