<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main Employees table
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('employee_id', 30)->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // Personal Info
                $table->string('first_name', 60);
                $table->string('middle_name', 60)->nullable();
                $table->string('last_name', 60);
                $table->string('suffix', 15)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
                $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Divorced', 'Separated'])->default('Single');
                $table->string('nationality', 50)->default('Filipino');
                $table->string('mobile_number', 30)->nullable();
                $table->string('email', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('photo')->nullable();

                // Organization & Hierarchy
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
                $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();

                // Employment Details
                $table->date('date_hired');
                $table->enum('employment_status', [
                    'Active', 'Probationary', 'On Leave', 'Suspended', 'Resigned', 'Terminated', 'Retired'
                ])->default('Probationary');
                $table->enum('employment_type', [
                    'Regular', 'Probationary', 'Part-time', 'Casual', 'Contractual'
                ])->default('Probationary');
                $table->date('date_of_regularization')->nullable();
                $table->date('contract_start_date')->nullable();
                $table->date('contract_end_date')->nullable();

                // Government Information
                $table->string('sss_number', 30)->nullable();
                $table->string('philhealth_number', 30)->nullable();
                $table->string('pagibig_number', 30)->nullable();
                $table->string('tin', 30)->nullable();

                // Compensation Information
                $table->decimal('basic_salary', 12, 2)->default(0.00);
                $table->enum('salary_type', ['Monthly', 'Daily', 'Hourly'])->default('Monthly');
                $table->enum('pay_frequency', ['Semi-Monthly', 'Monthly', 'Weekly'])->default('Semi-Monthly');
                $table->decimal('allowances', 10, 2)->default(0.00);
                $table->text('other_compensation')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Emergency Contacts
        if (!Schema::hasTable('emergency_contacts')) {
            Schema::create('emergency_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('contact_name', 100);
                $table->string('relationship', 50);
                $table->string('contact_number', 30);
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        // Employee Documents
        if (!Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('document_type', 50); // e.g. Resume, Contract, NBI Clearance, Health Card, Certificate
                $table->string('document_name', 150);
                $table->string('file_path', 255);
                $table->date('expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Employment Histories
        if (!Schema::hasTable('employment_histories')) {
            Schema::create('employment_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('action_type', 50); // Hired, Promoted, Regularized, Transferred, Salary Adjustment, Status Change
                $table->text('previous_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('remarks')->nullable();
                $table->date('effective_date');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_histories');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('emergency_contacts');
        Schema::dropIfExists('employees');
    }
};
