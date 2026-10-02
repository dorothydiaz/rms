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
        // 1. Holidays Table
        if (!Schema::hasTable('hr_holidays')) {
            Schema::create('hr_holidays', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->date('date');
                $table->unsignedSmallInteger('year')->index();
                $table->string('holiday_type', 50)->default('Regular Holiday'); // Regular Holiday, Special Non-Working, Special Working, Local Holiday, Company Holiday
                $table->string('scope', 50)->default('Nationwide'); // Nationwide, Regional, Provincial, City/Local, Company
                $table->string('region', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('city_municipality', 100)->nullable();
                $table->json('applicable_branches')->nullable();
                $table->string('payroll_treatment', 255)->nullable();
                $table->string('official_reference', 255)->nullable();
                $table->string('source', 255)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['year', 'holiday_type']);
                $table->index(['date', 'is_active']);
            });
        }

        // 2. Holiday Locations Table
        if (!Schema::hasTable('hr_holiday_locations')) {
            Schema::create('hr_holiday_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('holiday_id')->constrained('hr_holidays')->cascadeOnDelete();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('region', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('city_municipality', 100)->nullable();
                $table->timestamps();
            });
        }

        // 3. Holiday Pay Rules Table
        if (!Schema::hasTable('hr_holiday_pay_rules')) {
            Schema::create('hr_holiday_pay_rules', function (Blueprint $table) {
                $table->id();
                $table->string('holiday_type', 50);
                $table->unsignedSmallInteger('year')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->decimal('unworked_rate', 6, 2)->default(100.00); // 100%
                $table->decimal('worked_rate', 6, 2)->default(200.00); // 200%
                $table->decimal('rest_day_worked_rate', 6, 2)->default(260.00); // 260%
                $table->decimal('overtime_multiplier', 6, 2)->default(1.30); // +30%
                $table->decimal('rest_day_overtime_multiplier', 6, 2)->default(1.69);
                $table->text('calculation_rule_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Premium Pay Rules Table
        if (!Schema::hasTable('hr_premium_pay_rules')) {
            Schema::create('hr_premium_pay_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rule_code', 60)->unique();
                $table->string('name', 120);
                $table->string('category', 60); // Rest Day, Special Non-Working, Regular Holiday, Overtime, Night Shift
                $table->decimal('base_multiplier', 6, 2)->default(130.00);
                $table->decimal('holiday_multiplier', 6, 2)->nullable();
                $table->decimal('rest_day_multiplier', 6, 2)->nullable();
                $table->decimal('overtime_multiplier', 6, 2)->nullable();
                $table->decimal('night_diff_multiplier', 6, 2)->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->string('rule_type', 50)->default('Statutory Default'); // Statutory Default, Company Policy, CBA Override
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 5. Premium Pay Rule Versions Table
        if (!Schema::hasTable('hr_premium_pay_rule_versions')) {
            Schema::create('hr_premium_pay_rule_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('premium_pay_rule_id')->constrained('hr_premium_pay_rules')->cascadeOnDelete();
                $table->unsignedInteger('version_number')->default(1);
                $table->string('version_code', 50)->nullable();
                $table->date('effective_date');
                $table->json('configuration_snapshot');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('change_reason', 255)->nullable();
                $table->timestamps();
            });
        }

        // 6. Premium Pay Items Table
        if (!Schema::hasTable('hr_premium_pay_items')) {
            Schema::create('hr_premium_pay_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_period_id')->nullable();
                $table->unsignedBigInteger('payroll_record_id')->nullable();
                $table->unsignedBigInteger('employee_id');
                $table->date('work_date');
                $table->unsignedBigInteger('attendance_record_id')->nullable();
                $table->unsignedBigInteger('holiday_id')->nullable();
                $table->string('work_type', 60); // Rest Day, Special Non-Working, Special Non-Working + Rest Day, Regular Holiday, Regular Holiday + Rest Day
                $table->string('holiday_type', 60)->nullable();
                $table->boolean('is_rest_day')->default(false);
                $table->decimal('hours_worked', 5, 2)->default(8.00);
                $table->decimal('regular_hours', 5, 2)->default(8.00);
                $table->decimal('overtime_hours', 5, 2)->default(0.00);
                $table->decimal('daily_rate', 10, 2)->default(0.00);
                $table->decimal('hourly_rate', 10, 2)->default(0.00);
                $table->decimal('applied_rate_multiplier', 6, 2)->default(130.00); // 130%, 150%, 200%, 260%
                $table->decimal('regular_premium_pay', 10, 2)->default(0.00);
                $table->decimal('overtime_premium_pay', 10, 2)->default(0.00);
                $table->decimal('premium_amount', 10, 2)->default(0.00);
                $table->json('calculation_breakdown')->nullable();
                $table->string('rule_version', 50)->nullable()->default('v1.0-DOLE');
                $table->string('status', 30)->default('Pending'); // Pending, Approved, Rejected
                $table->string('rejection_reason', 255)->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'work_date']);
                $table->index(['payroll_period_id', 'status']);
            });
        }

        // 7. Update hr_payroll_records to include premium_pay & premium_pay_details
        $payrollRecordsTable = Schema::hasTable('hr_payroll_records') ? 'hr_payroll_records' : 'payroll_records';
        if (Schema::hasTable($payrollRecordsTable)) {
            Schema::table($payrollRecordsTable, function (Blueprint $table) use ($payrollRecordsTable) {
                if (!Schema::hasColumn($payrollRecordsTable, 'premium_pay')) {
                    $table->decimal('premium_pay', 10, 2)->default(0.00)->after('rest_day_pay');
                }
                if (!Schema::hasColumn($payrollRecordsTable, 'premium_pay_details')) {
                    $table->json('premium_pay_details')->nullable()->after('premium_pay');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $payrollRecordsTable = Schema::hasTable('hr_payroll_records') ? 'hr_payroll_records' : 'payroll_records';
        if (Schema::hasTable($payrollRecordsTable)) {
            Schema::table($payrollRecordsTable, function (Blueprint $table) use ($payrollRecordsTable) {
                if (Schema::hasColumn($payrollRecordsTable, 'premium_pay_details')) {
                    $table->dropColumn('premium_pay_details');
                }
                if (Schema::hasColumn($payrollRecordsTable, 'premium_pay')) {
                    $table->dropColumn('premium_pay');
                }
            });
        }

        Schema::dropIfExists('hr_premium_pay_items');
        Schema::dropIfExists('hr_premium_pay_rule_versions');
        Schema::dropIfExists('hr_premium_pay_rules');
        Schema::dropIfExists('hr_holiday_pay_rules');
        Schema::dropIfExists('hr_holiday_locations');
        Schema::dropIfExists('hr_holidays');
    }
};
