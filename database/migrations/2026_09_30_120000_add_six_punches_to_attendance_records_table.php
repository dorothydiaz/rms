<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'in_1')) {
                $table->time('in_1')->nullable()->after('date');
            }
            if (!Schema::hasColumn('attendance_records', 'out_1')) {
                $table->time('out_1')->nullable()->after('in_1');
            }
            if (!Schema::hasColumn('attendance_records', 'in_2')) {
                $table->time('in_2')->nullable()->after('out_1');
            }
            if (!Schema::hasColumn('attendance_records', 'out_2')) {
                $table->time('out_2')->nullable()->after('in_2');
            }
            if (!Schema::hasColumn('attendance_records', 'in_3')) {
                $table->time('in_3')->nullable()->after('out_2');
            }
            if (!Schema::hasColumn('attendance_records', 'out_3')) {
                $table->time('out_3')->nullable()->after('in_3');
            }
        });

        // Sync existing punches to the 6-punch columns if they are not already set
        DB::statement("UPDATE attendance_records SET in_1 = time_in WHERE in_1 IS NULL AND time_in IS NOT NULL");
        DB::statement("UPDATE attendance_records SET out_1 = break_out WHERE out_1 IS NULL AND break_out IS NOT NULL");
        DB::statement("UPDATE attendance_records SET in_2 = break_in WHERE in_2 IS NULL AND break_in IS NOT NULL");
        DB::statement("UPDATE attendance_records SET out_2 = coffee_break_out WHERE out_2 IS NULL AND coffee_break_out IS NOT NULL");
        DB::statement("UPDATE attendance_records SET in_3 = coffee_break_in WHERE in_3 IS NULL AND coffee_break_in IS NOT NULL");
        DB::statement("UPDATE attendance_records SET out_3 = time_out WHERE out_3 IS NULL AND time_out IS NOT NULL");

        // Normalize dates to YYYY-MM-DD
        DB::statement("UPDATE attendance_records SET date = substr(date, 1, 10) WHERE length(date) > 10");
        DB::statement("UPDATE employee_schedules SET schedule_date = substr(schedule_date, 1, 10) WHERE length(schedule_date) > 10");
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $cols = ['in_1', 'out_1', 'in_2', 'out_2', 'in_3', 'out_3'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('attendance_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
