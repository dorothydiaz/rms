<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Extend hr_attendance_records with Overtime and Undertime approval workflows
        Schema::table('hr_attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_attendance_records', 'overtime_status')) {
                $table->string('overtime_status', 25)->default('Pending')->after('overtime_hours');
            }
            if (!Schema::hasColumn('hr_attendance_records', 'overtime_approved_by')) {
                $table->unsignedBigInteger('overtime_approved_by')->nullable()->after('overtime_status');
            }
            if (!Schema::hasColumn('hr_attendance_records', 'overtime_approved_at')) {
                $table->timestamp('overtime_approved_at')->nullable()->after('overtime_approved_by');
            }
            if (!Schema::hasColumn('hr_attendance_records', 'overtime_remarks')) {
                $table->string('overtime_remarks', 255)->nullable()->after('overtime_approved_at');
            }

            if (!Schema::hasColumn('hr_attendance_records', 'undertime_status')) {
                $table->string('undertime_status', 25)->default('Pending')->after('undertime_minutes');
            }
            if (!Schema::hasColumn('hr_attendance_records', 'undertime_approved_by')) {
                $table->unsignedBigInteger('undertime_approved_by')->nullable()->after('undertime_status');
            }
            if (!Schema::hasColumn('hr_attendance_records', 'undertime_approved_at')) {
                $table->timestamp('undertime_approved_at')->nullable()->after('undertime_approved_by');
            }
            if (!Schema::hasColumn('hr_attendance_records', 'undertime_remarks')) {
                $table->string('undertime_remarks', 255)->nullable()->after('undertime_approved_at');
            }
        });

        // 2. Add default shift schedule to hr_employees
        Schema::table('hr_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employees', 'default_shift_template_id')) {
                $table->unsignedBigInteger('default_shift_template_id')->nullable()->after('work_schedule');
            }
        });

        // 3. Create Change of Schedule History Table
        if (!Schema::hasTable('hr_schedule_change_logs')) {
            Schema::create('hr_schedule_change_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->date('schedule_date');
                $table->unsignedBigInteger('previous_shift_template_id')->nullable();
                $table->unsignedBigInteger('new_shift_template_id')->nullable();
                $table->string('previous_time_range', 100)->nullable();
                $table->string('new_time_range', 100)->nullable();
                $table->string('reason', 255)->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 4. Create Attendance Action / Audit History Table (Overtime, Undertime, Manual Time Entries)
        if (!Schema::hasTable('hr_attendance_action_logs')) {
            Schema::create('hr_attendance_action_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_record_id')->nullable()->constrained('hr_attendance_records')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->string('action_type', 60); // e.g. overtime_approved, undertime_authorized, manual_entry_created, manual_entry_updated
                $table->json('details')->nullable();
                $table->string('notes', 255)->nullable();
                $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Initialize existing overtime records to Approved for seamless backward compatibility
        try {
            DB::table('hr_attendance_records')
                ->where('overtime_hours', '>', 0)
                ->where('overtime_status', 'Pending')
                ->update(['overtime_status' => 'Approved']);
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_action_logs');
        Schema::dropIfExists('hr_schedule_change_logs');

        Schema::table('hr_employees', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employees', 'default_shift_template_id')) {
                $table->dropColumn('default_shift_template_id');
            }
        });

        Schema::table('hr_attendance_records', function (Blueprint $table) {
            $cols = [
                'overtime_status', 'overtime_approved_by', 'overtime_approved_at', 'overtime_remarks',
                'undertime_status', 'undertime_approved_by', 'undertime_approved_at', 'undertime_remarks',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('hr_attendance_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
