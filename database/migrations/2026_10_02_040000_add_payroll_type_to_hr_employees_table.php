<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['hr_employees', 'employees'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                if (!Schema::hasColumn($table, 'payroll_type')) {
                    Schema::table($table, function (Blueprint $tableBlueprint) {
                        $tableBlueprint->enum('payroll_type', ['Daily', 'Monthly'])->default('Monthly')->after('basic_salary');
                    });
                }

                // Synchronize existing salary_type to payroll_type
                if (Schema::hasColumn($table, 'salary_type')) {
                    DB::table($table)->where('salary_type', 'Daily')->update(['payroll_type' => 'Daily']);
                    DB::table($table)->where('salary_type', '!=', 'Daily')->update(['payroll_type' => 'Monthly']);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['hr_employees', 'employees'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'payroll_type')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropColumn('payroll_type');
                });
            }
        }
    }
};
