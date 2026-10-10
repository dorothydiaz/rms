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
        // 1. Upgrade hr_job_vacancies
        Schema::table('hr_job_vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_job_vacancies', 'work_setup')) {
                $table->string('work_setup', 30)->default('On-site')->after('employment_type');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'responsibilities')) {
                $table->text('responsibilities')->nullable()->after('job_description');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'qualifications')) {
                $table->text('qualifications')->nullable()->after('responsibilities');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'required_skills')) {
                $table->text('required_skills')->nullable()->after('qualifications');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'preferred_skills')) {
                $table->text('preferred_skills')->nullable()->after('required_skills');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'benefits')) {
                $table->text('benefits')->nullable()->after('preferred_skills');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'hiring_manager_id')) {
                $table->unsignedBigInteger('hiring_manager_id')->nullable()->after('closing_date');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'hiring_manager_name')) {
                $table->string('hiring_manager_name', 100)->nullable()->after('hiring_manager_id');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'recruiter_id')) {
                $table->unsignedBigInteger('recruiter_id')->nullable()->after('hiring_manager_name');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'recruiter_name')) {
                $table->string('recruiter_name', 100)->nullable()->after('recruiter_id');
            }
            if (!Schema::hasColumn('hr_job_vacancies', 'application_questions')) {
                $table->json('application_questions')->nullable()->after('recruiter_name');
            }
        });

        // 2. Upgrade hr_applicants: Modify status column to varchar(50) so all pipeline & terminal statuses are supported
        try {
            DB::statement("ALTER TABLE `hr_applicants` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'New'");
        } catch (\Throwable $e) {
            // In case of non-MySQL or fallback
        }

        Schema::table('hr_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_applicants', 'middle_name')) {
                $table->string('middle_name', 60)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('hr_applicants', 'suffix')) {
                $table->string('suffix', 15)->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('hr_applicants', 'preferred_name')) {
                $table->string('preferred_name', 60)->nullable()->after('suffix');
            }
            if (!Schema::hasColumn('hr_applicants', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('preferred_name');
            }
            if (!Schema::hasColumn('hr_applicants', 'gender')) {
                $table->string('gender', 20)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('hr_applicants', 'civil_status')) {
                $table->string('civil_status', 30)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('hr_applicants', 'nationality')) {
                $table->string('nationality', 50)->default('Filipino')->after('civil_status');
            }
            if (!Schema::hasColumn('hr_applicants', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('hr_applicants', 'province')) {
                $table->string('province', 100)->nullable()->after('city');
            }
            if (!Schema::hasColumn('hr_applicants', 'desired_salary')) {
                $table->decimal('desired_salary', 12, 2)->nullable()->after('applied_position');
            }
            if (!Schema::hasColumn('hr_applicants', 'employment_type')) {
                $table->string('employment_type', 50)->nullable()->after('desired_salary');
            }
            if (!Schema::hasColumn('hr_applicants', 'available_start_date')) {
                $table->date('available_start_date')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('hr_applicants', 'years_of_experience')) {
                $table->string('years_of_experience', 50)->nullable()->after('available_start_date');
            }
            if (!Schema::hasColumn('hr_applicants', 'work_setup_preference')) {
                $table->string('work_setup_preference', 50)->nullable()->after('years_of_experience');
            }
            if (!Schema::hasColumn('hr_applicants', 'recruiter_id')) {
                $table->unsignedBigInteger('recruiter_id')->nullable()->after('source');
            }
            if (!Schema::hasColumn('hr_applicants', 'recruiter_name')) {
                $table->string('recruiter_name', 100)->nullable()->after('recruiter_id');
            }
            if (!Schema::hasColumn('hr_applicants', 'hiring_manager_id')) {
                $table->unsignedBigInteger('hiring_manager_id')->nullable()->after('recruiter_name');
            }
            if (!Schema::hasColumn('hr_applicants', 'hiring_manager_name')) {
                $table->string('hiring_manager_name', 100)->nullable()->after('hiring_manager_id');
            }
            if (!Schema::hasColumn('hr_applicants', 'education')) {
                $table->json('education')->nullable()->after('hiring_manager_name');
            }
            if (!Schema::hasColumn('hr_applicants', 'work_experience')) {
                $table->json('work_experience')->nullable()->after('education');
            }
            if (!Schema::hasColumn('hr_applicants', 'skills')) {
                $table->json('skills')->nullable()->after('work_experience');
            }
            if (!Schema::hasColumn('hr_applicants', 'certifications')) {
                $table->json('certifications')->nullable()->after('skills');
            }
            if (!Schema::hasColumn('hr_applicants', 'languages')) {
                $table->json('languages')->nullable()->after('certifications');
            }
            if (!Schema::hasColumn('hr_applicants', 'application_answers')) {
                $table->json('application_answers')->nullable()->after('languages');
            }
            if (!Schema::hasColumn('hr_applicants', 'documents')) {
                $table->json('documents')->nullable()->after('application_answers');
            }
            if (!Schema::hasColumn('hr_applicants', 'privacy_consent')) {
                $table->boolean('privacy_consent')->default(true)->after('documents');
            }
            if (!Schema::hasColumn('hr_applicants', 'data_processing_consent')) {
                $table->boolean('data_processing_consent')->default(true)->after('privacy_consent');
            }
            if (!Schema::hasColumn('hr_applicants', 'applicant_declaration')) {
                $table->boolean('applicant_declaration')->default(true)->after('data_processing_consent');
            }
            if (!Schema::hasColumn('hr_applicants', 'screening_data')) {
                $table->json('screening_data')->nullable()->after('applicant_declaration');
            }
            if (!Schema::hasColumn('hr_applicants', 'final_review_data')) {
                $table->json('final_review_data')->nullable()->after('screening_data');
            }
            if (!Schema::hasColumn('hr_applicants', 'offer_data')) {
                $table->json('offer_data')->nullable()->after('final_review_data');
            }
            if (!Schema::hasColumn('hr_applicants', 'pre_employment_requirements')) {
                $table->json('pre_employment_requirements')->nullable()->after('offer_data');
            }
            if (!Schema::hasColumn('hr_applicants', 'preboarding_tasks')) {
                $table->json('preboarding_tasks')->nullable()->after('pre_employment_requirements');
            }
            if (!Schema::hasColumn('hr_applicants', 'onboarding_data')) {
                $table->json('onboarding_data')->nullable()->after('preboarding_tasks');
            }
            if (!Schema::hasColumn('hr_applicants', 'activity_history')) {
                $table->json('activity_history')->nullable()->after('onboarding_data');
            }
        });

        // 3. Upgrade hr_interviews
        try {
            DB::statement("ALTER TABLE `hr_interviews` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Scheduled'");
            DB::statement("ALTER TABLE `hr_interviews` MODIFY COLUMN `interview_type` VARCHAR(50) NOT NULL DEFAULT 'Phone'");
        } catch (\Throwable $e) {}

        Schema::table('hr_interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_interviews', 'interview_stage')) {
                $table->string('interview_stage', 50)->default('HR Interview')->after('interview_type');
            }
            if (!Schema::hasColumn('hr_interviews', 'interview_time')) {
                $table->string('interview_time', 20)->nullable()->after('interview_date');
            }
            if (!Schema::hasColumn('hr_interviews', 'location_or_link')) {
                $table->string('location_or_link', 255)->nullable()->after('interviewer_name');
            }
            if (!Schema::hasColumn('hr_interviews', 'instructions')) {
                $table->text('instructions')->nullable()->after('location_or_link');
            }
            if (!Schema::hasColumn('hr_interviews', 'scorecard')) {
                $table->json('scorecard')->nullable()->after('recommendation');
            }
        });

        // 4. Create hr_assessments table
        if (!Schema::hasTable('hr_assessments')) {
            Schema::create('hr_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('hr_applicants')->cascadeOnDelete();
                $table->string('assessment_type', 60);
                $table->string('title', 150);
                $table->date('assessment_date')->nullable();
                $table->decimal('score', 6, 2)->nullable();
                $table->decimal('passing_score', 6, 2)->nullable();
                $table->string('result', 30)->default('Pending');
                $table->unsignedBigInteger('evaluator_id')->nullable();
                $table->string('evaluator_name', 100)->nullable();
                $table->string('attachment_path', 255)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_assessments');
    }
};
