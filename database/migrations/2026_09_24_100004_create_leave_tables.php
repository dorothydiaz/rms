<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable Leave Types
        if (!Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100); // Vacation, Sick, Emergency, Service Incentive, Maternity, etc.
                $table->string('code', 30)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_paid')->default(true);
                $table->decimal('default_credits', 5, 2)->default(0.00);
                $table->boolean('is_cumulative')->default(false);
                $table->timestamps();
            });
        }

        // Leave Balances per employee per calendar year
        if (!Schema::hasTable('leave_balances')) {
            Schema::create('leave_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->decimal('beginning_balance', 5, 2)->default(0.00);
                $table->decimal('earned', 5, 2)->default(0.00);
                $table->decimal('used', 5, 2)->default(0.00);
                $table->decimal('remaining', 5, 2)->default(0.00);
                $table->decimal('encashed', 5, 2)->default(0.00);
                $table->timestamps();

                $table->unique(['employee_id', 'leave_type_id', 'year']);
            });
        }

        // Leave Requests
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('number_of_days', 4, 1)->default(1.0);
                $table->text('reason');
                $table->string('supporting_document')->nullable();
                $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])->default('Pending');
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approval_date')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
