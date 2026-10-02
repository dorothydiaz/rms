<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = Schema::hasTable('hr_employees') ? 'hr_employees' : 'employees';

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
            // Personal & Profile fields
            if (!Schema::hasColumn($table, 'preferred_name')) {
                $tableBlueprint->string('preferred_name', 60)->nullable()->after('suffix');
            }
            if (!Schema::hasColumn($table, 'birth_place')) {
                $tableBlueprint->string('birth_place', 100)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn($table, 'telephone_number')) {
                $tableBlueprint->string('telephone_number', 30)->nullable()->after('mobile_number');
            }
            if (!Schema::hasColumn($table, 'company_email')) {
                $tableBlueprint->string('company_email', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn($table, 'permanent_address')) {
                $tableBlueprint->text('permanent_address')->nullable()->after('address');
            }

            // Family, Education & Compensation history
            if (!Schema::hasColumn($table, 'family_dependents')) {
                $tableBlueprint->json('family_dependents')->nullable()->after('permanent_address');
            }
            if (!Schema::hasColumn($table, 'education_history')) {
                $tableBlueprint->json('education_history')->nullable()->after('family_dependents');
            }
            if (!Schema::hasColumn($table, 'salary_history')) {
                $tableBlueprint->json('salary_history')->nullable()->after('education_history');
            }

            // Employment Dates
            if (!Schema::hasColumn($table, 'probation_end_date')) {
                $tableBlueprint->date('probation_end_date')->nullable()->after('date_of_regularization');
            }
            if (!Schema::hasColumn($table, 'date_separated')) {
                $tableBlueprint->date('date_separated')->nullable()->after('probation_end_date');
            }

            // Location, Schedule & Level
            if (!Schema::hasColumn($table, 'work_location')) {
                $tableBlueprint->string('work_location', 150)->nullable()->after('assigned_branch_ids');
            }
            if (!Schema::hasColumn($table, 'work_schedule')) {
                $tableBlueprint->string('work_schedule', 150)->nullable()->after('work_location');
            }
            if (!Schema::hasColumn($table, 'job_level')) {
                $tableBlueprint->string('job_level', 50)->nullable()->after('work_schedule');
            }

            // Government Verification & Status
            if (!Schema::hasColumn($table, 'sss_verified')) {
                $tableBlueprint->boolean('sss_verified')->default(false)->after('sss_number');
            }
            if (!Schema::hasColumn($table, 'sss_membership_status')) {
                $tableBlueprint->string('sss_membership_status', 50)->default('Active')->after('sss_verified');
            }
            if (!Schema::hasColumn($table, 'philhealth_verified')) {
                $tableBlueprint->boolean('philhealth_verified')->default(false)->after('philhealth_number');
            }
            if (!Schema::hasColumn($table, 'philhealth_membership_status')) {
                $tableBlueprint->string('philhealth_membership_status', 50)->default('Active')->after('philhealth_verified');
            }
            if (!Schema::hasColumn($table, 'pagibig_verified')) {
                $tableBlueprint->boolean('pagibig_verified')->default(false)->after('pagibig_number');
            }
            if (!Schema::hasColumn($table, 'pagibig_membership_status')) {
                $tableBlueprint->string('pagibig_membership_status', 50)->default('Active')->after('pagibig_verified');
            }
            if (!Schema::hasColumn($table, 'tin_verified')) {
                $tableBlueprint->boolean('tin_verified')->default(false)->after('tin');
            }
            if (!Schema::hasColumn($table, 'rdo_code')) {
                $tableBlueprint->string('rdo_code', 30)->nullable()->after('tin_verified');
            }
            if (!Schema::hasColumn($table, 'philsys_id')) {
                $tableBlueprint->string('philsys_id', 50)->nullable()->after('rdo_code');
            }
            if (!Schema::hasColumn($table, 'passport_number')) {
                $tableBlueprint->string('passport_number', 50)->nullable()->after('philsys_id');
            }
            if (!Schema::hasColumn($table, 'driver_license')) {
                $tableBlueprint->string('driver_license', 50)->nullable()->after('passport_number');
            }
            if (!Schema::hasColumn($table, 'other_gov_id_type')) {
                $tableBlueprint->string('other_gov_id_type', 50)->nullable()->after('driver_license');
            }
            if (!Schema::hasColumn($table, 'other_gov_id_number')) {
                $tableBlueprint->string('other_gov_id_number', 50)->nullable()->after('other_gov_id_type');
            }
            if (!Schema::hasColumn($table, 'other_gov_id_verified')) {
                $tableBlueprint->boolean('other_gov_id_verified')->default(false)->after('other_gov_id_number');
            }
        });
    }

    public function down(): void
    {
        $table = Schema::hasTable('hr_employees') ? 'hr_employees' : 'employees';

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
            $cols = [
                'preferred_name', 'birth_place', 'telephone_number', 'company_email',
                'permanent_address', 'family_dependents', 'education_history', 'salary_history',
                'probation_end_date', 'date_separated',
                'work_location', 'work_schedule', 'job_level',
                'sss_verified', 'sss_membership_status',
                'philhealth_verified', 'philhealth_membership_status',
                'pagibig_verified', 'pagibig_membership_status',
                'tin_verified', 'rdo_code', 'philsys_id', 'passport_number', 'driver_license',
                'other_gov_id_type', 'other_gov_id_number', 'other_gov_id_verified',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $tableBlueprint->dropColumn($col);
                }
            }
        });
    }
};
