<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable Philippine Statutory Rules (SSS, PhilHealth, Pag-IBIG, BIR Withholding Tax)
        if (!Schema::hasTable('statutory_contribution_rules')) {
            Schema::create('statutory_contribution_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rule_name', 100);
                $table->enum('rule_type', ['SSS', 'PhilHealth', 'PagIBIG', 'BIR_Tax']);
                $table->decimal('rate', 6, 4)->default(0.0000); // e.g. 0.0500 for 5%
                $table->decimal('min_salary', 10, 2)->default(0.00);
                $table->decimal('max_salary', 10, 2)->default(0.00);
                $table->decimal('employee_share', 6, 4)->default(0.0000);
                $table->decimal('employer_share', 6, 4)->default(0.0000);
                $table->decimal('fixed_amount', 10, 2)->default(0.00);
                $table->json('bracket_json')->nullable(); // For graduated tax brackets or custom ranges
                $table->date('effective_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Payroll Periods
        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100); // e.g., "September 2026 - 1st Half"
                $table->date('start_date');
                $table->date('end_date');
                $table->date('payout_date');
                $table->enum('pay_frequency', ['Semi-Monthly', 'Monthly', 'Weekly'])->default('Semi-Monthly');
                $table->enum('status', ['Draft', 'Review', 'Approved', 'Finalized'])->default('Draft');
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();
            });
        }

        // Payroll Records (Payslip calculation data per employee)
        if (!Schema::hasTable('payroll_records')) {
            Schema::create('payroll_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

                // Base Earnings
                $table->decimal('basic_pay', 12, 2)->default(0.00);
                $table->decimal('total_work_days', 4, 1)->default(0.0);
                $table->decimal('total_hours', 6, 2)->default(0.00);
                $table->decimal('regular_hours_pay', 12, 2)->default(0.00);

                // Additions / Premiums
                $table->decimal('overtime_hours', 5, 2)->default(0.00);
                $table->decimal('overtime_pay', 10, 2)->default(0.00);
                $table->decimal('night_diff_hours', 5, 2)->default(0.00);
                $table->decimal('night_diff_pay', 10, 2)->default(0.00);
                $table->decimal('holiday_pay', 10, 2)->default(0.00);
                $table->decimal('rest_day_pay', 10, 2)->default(0.00);
                $table->decimal('allowances', 10, 2)->default(0.00);
                $table->decimal('bonuses', 10, 2)->default(0.00);
                $table->decimal('other_earnings', 10, 2)->default(0.00);

                // Gross
                $table->decimal('gross_pay', 12, 2)->default(0.00);

                // Attendance Deductions
                $table->decimal('late_deduction', 10, 2)->default(0.00);
                $table->decimal('undertime_deduction', 10, 2)->default(0.00);
                $table->decimal('absence_deduction', 10, 2)->default(0.00);

                // Statutory Deductions (Employee Share)
                $table->decimal('sss_employee', 10, 2)->default(0.00);
                $table->decimal('sss_employer', 10, 2)->default(0.00);
                $table->decimal('philhealth_employee', 10, 2)->default(0.00);
                $table->decimal('philhealth_employer', 10, 2)->default(0.00);
                $table->decimal('pagibig_employee', 10, 2)->default(0.00);
                $table->decimal('pagibig_employer', 10, 2)->default(0.00);
                $table->decimal('withholding_tax', 10, 2)->default(0.00);

                // Loans & Advances
                $table->decimal('salary_advance', 10, 2)->default(0.00);
                $table->decimal('employee_loan', 10, 2)->default(0.00);
                $table->decimal('other_deductions', 10, 2)->default(0.00);

                // Totals
                $table->decimal('total_deductions', 12, 2)->default(0.00);
                $table->decimal('net_pay', 12, 2)->default(0.00);

                $table->enum('status', ['Draft', 'Approved', 'Finalized'])->default('Draft');
                $table->timestamps();

                $table->unique(['payroll_period_id', 'employee_id']);
            });
        }

        // Payroll Adjustments (For auditable changes after review)
        if (!Schema::hasTable('payroll_adjustments')) {
            Schema::create('payroll_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
                $table->enum('adjustment_type', ['Earning', 'Deduction']);
                $table->string('name', 100);
                $table->decimal('amount', 10, 2);
                $table->text('remarks')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payroll_records');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('statutory_contribution_rules');
    }
};
