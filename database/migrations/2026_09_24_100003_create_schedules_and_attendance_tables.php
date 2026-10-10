<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shift Templates
        if (!Schema::hasTable('shift_templates')) {
            Schema::create('shift_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 60); // Morning, Afternoon, Night Overnight
                $table->string('code', 20)->unique();
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_overnight')->default(false); // End time is next calendar day
                $table->unsignedInteger('break_minutes')->default(60);
                $table->string('color', 20)->default('#a855f7');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Employee Schedules
        if (!Schema::hasTable('employee_schedules')) {
            Schema::create('employee_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('shift_template_id')->nullable()->constrained('shift_templates')->nullOnDelete();
                $table->date('schedule_date');
                $table->time('custom_start_time')->nullable();
                $table->time('custom_end_time')->nullable();
                $table->boolean('is_rest_day')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'schedule_date']);
            });
        }

        // Attendance Records
        if (!Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->date('date');

                // Timepunches
                $table->time('time_in')->nullable();
                $table->time('break_out')->nullable();
                $table->time('break_in')->nullable();
                $table->time('time_out')->nullable();

                // Calculated Metrics
                $table->decimal('total_hours', 5, 2)->default(0.00);
                $table->decimal('regular_hours', 5, 2)->default(0.00);
                $table->unsignedInteger('late_minutes')->default(0);
                $table->unsignedInteger('undertime_minutes')->default(0);
                $table->decimal('overtime_hours', 5, 2)->default(0.00);
                $table->decimal('night_diff_hours', 5, 2)->default(0.00); // 10:00 PM to 6:00 AM

                // Day classification
                $table->enum('holiday_type', ['None', 'Regular', 'SpecialNonWorking'])->default('None');
                $table->boolean('is_rest_day')->default(false);
                $table->enum('status', [
                    'Present', 'Absent', 'Late', 'Half Day', 'On Leave', 'Rest Day', 'Holiday'
                ])->default('Present');
                $table->enum('source', ['Web', 'Manual', 'Biometric'])->default('Web');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'date']);
            });
        }

        // Attendance Corrections
        if (!Schema::hasTable('attendance_corrections')) {
            Schema::create('attendance_corrections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

                $table->time('original_time_in')->nullable();
                $table->time('original_time_out')->nullable();
                $table->time('requested_time_in')->nullable();
                $table->time('requested_time_out')->nullable();

                $table->text('reason');
                $table->string('supporting_document')->nullable();
                $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('reviewer_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('shift_templates');
    }
};
