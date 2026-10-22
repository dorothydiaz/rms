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
        $table = Schema::hasTable('hr_attendance_records')
            ? 'hr_attendance_records'
            : (Schema::hasTable('attendance_records') ? 'attendance_records' : null);

        if (!$table) {
            return;
        }

        $isPrefixed = str_starts_with($table, 'hr_');
        $absenceCol = $isPrefixed ? 'attendance_record_absence_days' : 'absence_days';
        $dtrCol = $isPrefixed ? 'attendance_record_dtr_remarks' : 'dtr_remarks';

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table, $absenceCol, $dtrCol, $isPrefixed) {
            if (!Schema::hasColumn($table, $absenceCol)) {
                if ($isPrefixed && Schema::hasColumn($table, 'absence_days')) {
                    $tableBlueprint->renameColumn('absence_days', $absenceCol);
                } else {
                    $afterCol = $isPrefixed ? 'attendance_record_overtime_remarks' : 'overtime_remarks';
                    if (Schema::hasColumn($table, $afterCol)) {
                        $tableBlueprint->decimal($absenceCol, 4, 2)->default(0.00)->after($afterCol);
                    } else {
                        $tableBlueprint->decimal($absenceCol, 4, 2)->default(0.00);
                    }
                }
            }

            if (!Schema::hasColumn($table, $dtrCol)) {
                if ($isPrefixed && Schema::hasColumn($table, 'dtr_remarks')) {
                    $tableBlueprint->renameColumn('dtr_remarks', $dtrCol);
                } else {
                    $afterCol = $isPrefixed ? 'attendance_record_status' : 'status';
                    if (Schema::hasColumn($table, $afterCol)) {
                        $tableBlueprint->string($dtrCol, 50)->nullable()->after($afterCol);
                    } else {
                        $tableBlueprint->string($dtrCol, 50)->nullable();
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = Schema::hasTable('hr_attendance_records')
            ? 'hr_attendance_records'
            : (Schema::hasTable('attendance_records') ? 'attendance_records' : null);

        if (!$table) {
            return;
        }

        $isPrefixed = str_starts_with($table, 'hr_');
        $absenceCol = $isPrefixed ? 'attendance_record_absence_days' : 'absence_days';
        $dtrCol = $isPrefixed ? 'attendance_record_dtr_remarks' : 'dtr_remarks';

        Schema::table($table, function (Blueprint $tableBlueprint) use ($table, $absenceCol, $dtrCol) {
            $drops = [];
            if (Schema::hasColumn($table, $absenceCol)) {
                $drops[] = $absenceCol;
            }
            if (Schema::hasColumn($table, $dtrCol)) {
                $drops[] = $dtrCol;
            }
            if (!empty($drops)) {
                $tableBlueprint->dropColumn($drops);
            }
        });
    }
};
