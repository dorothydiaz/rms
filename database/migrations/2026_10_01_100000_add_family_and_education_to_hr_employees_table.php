<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employees', 'family_dependents')) {
                $table->json('family_dependents')->nullable()->after('permanent_address');
            }
            if (!Schema::hasColumn('hr_employees', 'education_history')) {
                $table->json('education_history')->nullable()->after('family_dependents');
            }
            if (!Schema::hasColumn('hr_employees', 'salary_history')) {
                $table->json('salary_history')->nullable()->after('education_history');
            }
            if (!Schema::hasColumn('hr_employees', 'rdo_code')) {
                $table->string('rdo_code', 20)->nullable()->after('tin_verified');
            }
            if (!Schema::hasColumn('hr_employees', 'philsys_id')) {
                $table->string('philsys_id', 50)->nullable()->after('rdo_code');
            }
            if (!Schema::hasColumn('hr_employees', 'passport_number')) {
                $table->string('passport_number', 50)->nullable()->after('philsys_id');
            }
            if (!Schema::hasColumn('hr_employees', 'driver_license')) {
                $table->string('driver_license', 50)->nullable()->after('passport_number');
            }
            if (!Schema::hasColumn('hr_employees', 'sss_membership_status')) {
                $table->string('sss_membership_status', 50)->default('Active')->after('sss_verified');
            }
            if (!Schema::hasColumn('hr_employees', 'philhealth_membership_status')) {
                $table->string('philhealth_membership_status', 50)->default('Active')->after('philhealth_verified');
            }
            if (!Schema::hasColumn('hr_employees', 'pagibig_membership_status')) {
                $table->string('pagibig_membership_status', 50)->default('Active')->after('pagibig_verified');
            }
            if (!Schema::hasColumn('hr_employees', 'work_location')) {
                $table->string('work_location', 150)->nullable()->after('assigned_branch_ids');
            }
            if (!Schema::hasColumn('hr_employees', 'work_schedule')) {
                $table->string('work_schedule', 150)->nullable()->after('work_location');
            }
            if (!Schema::hasColumn('hr_employees', 'job_level')) {
                $table->string('job_level', 50)->nullable()->after('work_schedule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $cols = [
                'family_dependents', 'education_history', 'salary_history',
                'rdo_code', 'philsys_id', 'passport_number', 'driver_license',
                'sss_membership_status', 'philhealth_membership_status', 'pagibig_membership_status',
                'work_location', 'work_schedule', 'job_level',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('hr_employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
