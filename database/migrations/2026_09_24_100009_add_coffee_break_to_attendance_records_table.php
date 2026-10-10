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
            if (!Schema::hasColumn('attendance_records', 'coffee_break_out')) {
                $table->time('coffee_break_out')->nullable()->after('break_in');
            }
            if (!Schema::hasColumn('attendance_records', 'coffee_break_in')) {
                $table->time('coffee_break_in')->nullable()->after('coffee_break_out');
            }
        });

        // Sync existing out_2 and in_3 if populated
        if (Schema::hasColumn('attendance_records', 'out_2')) {
            DB::statement("UPDATE attendance_records SET coffee_break_out = out_2 WHERE coffee_break_out IS NULL AND out_2 IS NOT NULL");
        }
        if (Schema::hasColumn('attendance_records', 'in_3')) {
            DB::statement("UPDATE attendance_records SET coffee_break_in = in_3 WHERE coffee_break_in IS NULL AND in_3 IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'coffee_break_out')) {
                $table->dropColumn('coffee_break_out');
            }
            if (Schema::hasColumn('attendance_records', 'coffee_break_in')) {
                $table->dropColumn('coffee_break_in');
            }
        });
    }
};
