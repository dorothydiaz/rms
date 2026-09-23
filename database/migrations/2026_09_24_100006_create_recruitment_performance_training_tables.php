<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. RECRUITMENT: Job Vacancies
        if (!Schema::hasTable('job_vacancies')) {
            Schema::create('job_vacancies', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->unsignedInteger('number_of_openings')->default(1);
                $table->enum('employment_type', ['Regular', 'Probationary', 'Part-time', 'Casual', 'Contractual'])->default('Regular');
                $table->decimal('salary_range_min', 10, 2)->nullable();
                $table->decimal('salary_range_max', 10, 2)->nullable();
                $table->text('job_description')->nullable();
                $table->text('requirements')->nullable();
                $table->date('opening_date');
                $table->date('closing_date')->nullable();
                $table->enum('status', ['Draft', 'Open', 'On Hold', 'Closed', 'Filled'])->default('Open');
                $table->timestamps();
            });
        }

        // Applicants
        if (!Schema::hasTable('applicants')) {
            Schema::create('applicants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_vacancy_id')->nullable()->constrained('job_vacancies')->nullOnDelete();
                $table->string('first_name', 60);
                $table->string('last_name', 60);
                $table->string('contact_number', 30)->nullable();
                $table->string('email', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('resume_path')->nullable();
                $table->string('applied_position', 100);
                $table->string('source', 50)->default('Walk-in'); // Walk-in, Online, Referral, Job Fair
                $table->date('application_date');
                $table->enum('status', [
                    'New', 'Screening', 'Interview', 'Assessment', 'Shortlisted', 'Job Offer', 'Hired', 'Rejected'
                ])->default('New');
                $table->foreignId('hired_as_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Interviews
        if (!Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
                $table->dateTime('interview_date');
                $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('interviewer_name', 100)->nullable();
                $table->enum('interview_type', ['Initial Screening', 'Technical / Practical', 'Managerial', 'Final HR'])->default('Initial Screening');
                $table->text('notes')->nullable();
                $table->unsignedTinyInteger('rating')->nullable(); // 1 to 5
                $table->text('recommendation')->nullable(); // Proceed, Re-evaluate, Reject, Offer
                $table->enum('status', ['Scheduled', 'Completed', 'Cancelled'])->default('Scheduled');
                $table->timestamps();
            });
        }

        // 2. PERFORMANCE: Evaluation Periods
        if (!Schema::hasTable('performance_periods')) {
            Schema::create('performance_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100); // e.g. "Q3 2026 Restaurant Performance Evaluation"
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['Draft', 'Active', 'Closed'])->default('Active');
                $table->timestamps();
            });
        }

        // Evaluation Criteria
        if (!Schema::hasTable('performance_criteria')) {
            Schema::create('performance_criteria', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100); // Attendance, Food Safety, Customer Service, Teamwork, etc.
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('weight_percentage')->default(10);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Evaluations
        if (!Schema::hasTable('performance_evaluations')) {
            Schema::create('performance_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('performance_period_id')->constrained('performance_periods')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('overall_score', 4, 2)->default(0.00); // 1.00 to 5.00
                $table->enum('status', ['Draft', 'Submitted', 'HR_Reviewed', 'Final'])->default('Draft');
                $table->text('manager_comments')->nullable();
                $table->text('hr_comments')->nullable();
                $table->text('recommendation')->nullable(); // Regularization, Promotion, Wage Increase, Coaching
                $table->timestamps();

                $table->unique(['performance_period_id', 'employee_id']);
            });
        }

        // Criteria Ratings per evaluation
        if (!Schema::hasTable('performance_ratings')) {
            Schema::create('performance_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluation_id')->constrained('performance_evaluations')->cascadeOnDelete();
                $table->foreignId('criterion_id')->constrained('performance_criteria')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(3); // 1 to 5
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        // 3. TRAINING & DEVELOPMENT: Training Programs
        if (!Schema::hasTable('training_programs')) {
            Schema::create('training_programs', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150); // Food Safety, POS, Kitchen Safety, First Aid, etc.
                $table->text('description')->nullable();
                $table->string('trainer', 100)->nullable();
                $table->enum('training_type', ['Internal', 'External', 'Online'])->default('Internal');
                $table->string('location', 150)->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->unsignedSmallInteger('duration_hours')->default(8);
                $table->decimal('cost', 10, 2)->default(0.00);
                $table->enum('status', ['Scheduled', 'In Progress', 'Completed', 'Cancelled'])->default('Scheduled');
                $table->timestamps();
            });
        }

        // Employee Training Enrollments
        if (!Schema::hasTable('training_enrollments')) {
            Schema::create('training_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('enrollment_date');
                $table->enum('attendance_status', ['Present', 'Absent', 'Excused'])->default('Present');
                $table->enum('completion_status', ['Assigned', 'Scheduled', 'In Progress', 'Completed', 'Failed', 'Cancelled'])->default('Scheduled');
                $table->decimal('score', 5, 2)->nullable();
                $table->string('certificate_path')->nullable();
                $table->date('completion_date')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['training_program_id', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('performance_ratings');
        Schema::dropIfExists('performance_evaluations');
        Schema::dropIfExists('performance_criteria');
        Schema::dropIfExists('performance_periods');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('job_vacancies');
    }
};
