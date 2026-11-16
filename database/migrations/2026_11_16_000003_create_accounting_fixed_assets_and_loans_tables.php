<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Wave 3: Fixed Assets, Loans & Borrowings, and Period Close Checklists.
     */
    public function up(): void
    {
        // 1. Fixed Assets Register
        if (!Schema::hasTable('acct_fixed_assets')) {
            Schema::create('acct_fixed_assets', function (Blueprint $table) {
                $table->uuid('asset_id')->primary();
                $table->string('asset_code', 30);
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('department_id', 50)->nullable()->index();
                $table->string('asset_name', 255);
                $table->string('asset_category', 50)->default('EQUIPMENT'); // KITCHEN_EQUIPMENT, FURNITURE_FIXTURES, LEASEHOLD, VEHICLES, IT_EQUIPMENT
                $table->date('acquisition_date')->index();
                $table->decimal('acquisition_cost', 18, 4)->default(0.0000);
                $table->decimal('salvage_value', 18, 4)->default(0.0000);
                $table->decimal('depreciable_base', 18, 4)->default(0.0000);
                $table->unsignedSmallInteger('useful_life_months')->default(60); // 5 years default
                $table->string('depreciation_method', 30)->default('STRAIGHT_LINE');
                $table->uuid('asset_gl_account_id')->index(); // FIXED_ASSET_CLEARING or specific Asset account
                $table->uuid('accum_deprec_gl_account_id')->index(); // ACCUMULATED_DEPRECIATION
                $table->uuid('deprec_expense_gl_account_id')->index(); // DEPRECIATION_EXPENSE
                $table->uuid('vendor_bill_id')->nullable()->index(); // If purchased via AP Bill
                $table->uuid('vendor_bill_line_id')->nullable();
                $table->decimal('total_depreciated_amount', 18, 4)->default(0.0000);
                $table->decimal('net_book_value', 18, 4)->default(0.0000);
                $table->string('status', 20)->default('ACTIVE')->index(); // DRAFT, ACTIVE, FULLY_DEPRECIATED, DISPOSED
                $table->string('serial_number', 100)->nullable();
                $table->string('location', 150)->nullable();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'asset_code'], 'uq_acct_fa_company_code');
            });
        }

        // 2. Fixed Asset Monthly Depreciation Runs
        if (!Schema::hasTable('acct_fixed_asset_depreciations')) {
            Schema::create('acct_fixed_asset_depreciations', function (Blueprint $table) {
                $table->uuid('depreciation_id')->primary();
                $table->string('depreciation_number', 50)->unique();
                $table->uuid('asset_id')->index();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->uuid('fiscal_period_id')->nullable()->index();
                $table->date('depreciation_date')->index();
                $table->decimal('depreciation_amount', 18, 4)->default(0.0000);
                $table->decimal('book_value_before', 18, 4)->default(0.0000);
                $table->decimal('book_value_after', 18, 4)->default(0.0000);
                $table->uuid('journal_id')->nullable()->index();
                $table->string('status', 20)->default('POSTED')->index(); // DRAFT, POSTED, CANCELLED
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Fixed Asset Disposals & Gain/Loss
        if (!Schema::hasTable('acct_fixed_asset_disposals')) {
            Schema::create('acct_fixed_asset_disposals', function (Blueprint $table) {
                $table->uuid('disposal_id')->primary();
                $table->string('disposal_number', 50)->unique();
                $table->uuid('asset_id')->index();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->date('disposal_date')->index();
                $table->string('disposal_type', 30)->default('SALE'); // SALE, SCRAP_RETIREMENT, CASUALTY_LOSS
                $table->decimal('proceeds_amount', 18, 4)->default(0.0000);
                $table->decimal('cost_at_disposal', 18, 4)->default(0.0000);
                $table->decimal('accum_deprec_at_disposal', 18, 4)->default(0.0000);
                $table->decimal('book_value_at_disposal', 18, 4)->default(0.0000);
                $table->decimal('gain_loss_amount', 18, 4)->default(0.0000); // proceeds - book_value
                $table->string('buyer_name', 255)->nullable();
                $table->uuid('receipt_account_id')->nullable(); // Cash / Bank GL account for proceeds
                $table->uuid('gain_loss_gl_account_id')->nullable()->index(); // GAIN_LOSS_DISPOSAL
                $table->uuid('journal_id')->nullable()->index();
                $table->string('status', 20)->default('POSTED')->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();
            });
        }

        // 4. Loans & Debt Facilities
        if (!Schema::hasTable('acct_loans')) {
            Schema::create('acct_loans', function (Blueprint $table) {
                $table->uuid('loan_id')->primary();
                $table->string('loan_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('lender_name', 255);
                $table->string('lender_contact', 150)->nullable();
                $table->string('loan_type', 30)->default('BANK_LOAN'); // BANK_LOAN, EQUIPMENT_FINANCING, SHAREHOLDER_LOAN, CREDIT_LINE
                $table->decimal('principal_amount', 18, 4)->default(0.0000);
                $table->decimal('annual_interest_rate_pct', 8, 4)->default(0.0000);
                $table->date('start_date')->index();
                $table->unsignedSmallInteger('term_months')->default(12);
                $table->date('maturity_date')->nullable();
                $table->string('payment_frequency', 20)->default('MONTHLY');
                $table->string('interest_method', 30)->default('DIMINISHING_BALANCE'); // DIMINISHING_BALANCE, STRAIGHT_LINE
                $table->decimal('monthly_installment_amount', 18, 4)->default(0.0000);
                $table->decimal('total_interest_expected', 18, 4)->default(0.0000);
                $table->decimal('principal_paid', 18, 4)->default(0.0000);
                $table->decimal('interest_paid', 18, 4)->default(0.0000);
                $table->decimal('balance_remaining', 18, 4)->default(0.0000);
                $table->uuid('disbursement_account_id')->index(); // Bank/Cash GL where loan proceeded
                $table->uuid('loan_payable_account_id')->index(); // LOAN_PAYABLE
                $table->uuid('interest_expense_account_id')->index(); // INTEREST_EXPENSE
                $table->uuid('disbursement_journal_id')->nullable()->index();
                $table->string('status', 20)->default('ACTIVE')->index(); // DRAFT, ACTIVE, CLOSED, DEFAULTED
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();
            });
        }

        // 5. Loan Amortization Schedules
        if (!Schema::hasTable('acct_loan_amortizations')) {
            Schema::create('acct_loan_amortizations', function (Blueprint $table) {
                $table->uuid('amortization_id')->primary();
                $table->uuid('loan_id')->index();
                $table->unsignedSmallInteger('period_number')->default(1);
                $table->date('due_date')->index();
                $table->decimal('beginning_principal', 18, 4)->default(0.0000);
                $table->decimal('principal_amount', 18, 4)->default(0.0000);
                $table->decimal('interest_amount', 18, 4)->default(0.0000);
                $table->decimal('total_installment', 18, 4)->default(0.0000);
                $table->decimal('ending_principal', 18, 4)->default(0.0000);
                $table->uuid('payment_id')->nullable()->index();
                $table->date('payment_date')->nullable();
                $table->string('status', 20)->default('PENDING')->index(); // PENDING, PAID, OVERDUE
                $table->timestamps();
            });
        }

        // 6. Loan Payments
        if (!Schema::hasTable('acct_loan_payments')) {
            Schema::create('acct_loan_payments', function (Blueprint $table) {
                $table->uuid('loan_payment_id')->primary();
                $table->string('payment_number', 50)->unique();
                $table->uuid('loan_id')->index();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->date('payment_date')->index();
                $table->decimal('principal_amount', 18, 4)->default(0.0000);
                $table->decimal('interest_amount', 18, 4)->default(0.0000);
                $table->decimal('penalty_amount', 18, 4)->default(0.0000);
                $table->decimal('total_paid', 18, 4)->default(0.0000);
                $table->string('payment_method', 30)->default('BANK_TRANSFER');
                $table->uuid('disbursement_account_id')->index(); // Bank GL where money was paid from
                $table->uuid('journal_id')->nullable()->index();
                $table->string('status', 20)->default('POSTED')->index();
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        // 7. Period Close Checklists & Controls
        if (!Schema::hasTable('acct_period_close_checklists')) {
            Schema::create('acct_period_close_checklists', function (Blueprint $table) {
                $table->uuid('checklist_id')->primary();
                $table->uuid('fiscal_period_id')->index();
                $table->string('company_id', 50)->default('1')->index();
                $table->boolean('bank_reconciliation_done')->default(false);
                $table->boolean('depreciation_posted')->default(false);
                $table->boolean('inventory_reconciled')->default(false);
                $table->boolean('ap_ar_reconciled')->default(false);
                $table->boolean('recurring_posted')->default(false);
                $table->boolean('fx_revaluation_done')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->string('locked_by', 100)->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'fiscal_period_id'], 'uq_acct_period_checklist');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acct_period_close_checklists');
        Schema::dropIfExists('acct_loan_payments');
        Schema::dropIfExists('acct_loan_amortizations');
        Schema::dropIfExists('acct_loans');
        Schema::dropIfExists('acct_fixed_asset_disposals');
        Schema::dropIfExists('acct_fixed_asset_depreciations');
        Schema::dropIfExists('acct_fixed_assets');
    }
};

