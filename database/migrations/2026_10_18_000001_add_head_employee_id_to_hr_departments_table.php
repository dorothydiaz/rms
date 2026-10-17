<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = Schema::hasTable('hr_departments') ? 'hr_departments' : (Schema::hasTable('departments') ? 'departments' : null);

        if ($table && !Schema::hasColumn($table, 'department_head_employee_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('department_head_employee_id')->nullable()->after('department_branch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = Schema::hasTable('hr_departments') ? 'hr_departments' : (Schema::hasTable('departments') ? 'departments' : null);

        if ($table && Schema::hasColumn($table, 'department_head_employee_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('department_head_employee_id');
            });
        }
    }
};
