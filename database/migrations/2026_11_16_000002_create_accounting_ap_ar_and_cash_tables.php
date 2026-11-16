<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Wave 2: Accounts Payable, Accounts Receivable, and Cash & Bank.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Vendor Payments & Disbursements (Payment Vouchers / Prepayments / Check-PDC)
        if (!Schema::hasTable('acct_vendor_payments')) {
            Schema::create('acct_vendor_payments', function (Blueprint $table) {
                $table->uuid('payment_id')->primary();
                $table->string('payment_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('vendor_id', 50)->index();
                $table->string('vendor_name', 255);
                $table->date('payment_date')->index();
                $table->string('payment_method', 30)->default('BANK_TRANSFER'); // CASH, BANK_TRANSFER, CHECK, PDC
                $table->uuid('disbursement_account_id')->nullable()->index(); // Bank / Cash / PDC_PAYABLE GL account
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('gross_amount', 18, 4)->default(0.0000);
                $table->decimal('ewt_amount', 18, 4)->default(0.0000);
                $table->decimal('net_amount', 18, 4)->default(0.0000);
                $table->decimal('total_amount', 18, 4)->default(0.0000);
                $table->decimal('total_ewt_amount', 18, 4)->default(0.0000);
                $table->decimal('total_net_amount', 18, 4)->default(0.0000);
                $table->string('check_number', 50)->nullable();
                $table->date('check_date')->nullable();
                $table->date('check_maturity_date')->nullable();
                $table->string('bank_reference', 100)->nullable();
                $table->boolean('is_prepayment')->default(false);
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, APPROVED, POSTED, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->uuid('approval_request_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_vendor_payment_lines')) {
            Schema::create('acct_vendor_payment_lines', function (Blueprint $table) {
                $table->uuid('payment_line_id')->primary();
                $table->uuid('payment_id')->index();
                $table->uuid('vendor_bill_id')->nullable()->index();
                $table->string('bill_number', 50)->nullable();
                $table->decimal('bill_amount', 18, 4)->default(0.0000);
                $table->decimal('balance_before', 18, 4)->default(0.0000);
                $table->decimal('allocated_amount', 18, 4)->default(0.0000);
                $table->decimal('ewt_withheld', 18, 4)->default(0.0000);
                $table->string('ewt_tax_code', 30)->nullable();
                $table->decimal('ewt_rate_pct', 8, 4)->default(0.0000);
                $table->boolean('is_prepayment')->default(false);
                $table->timestamps();
            });
        }

        // 2. Vendor Credit & Debit Memos
        if (!Schema::hasTable('acct_vendor_memos')) {
            Schema::create('acct_vendor_memos', function (Blueprint $table) {
                $table->uuid('memo_id')->primary();
                $table->string('memo_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('vendor_id', 50)->index();
                $table->string('vendor_name', 255);
                $table->string('memo_type', 20)->default('CREDIT'); // CREDIT, DEBIT
                $table->date('memo_date')->index();
                $table->uuid('vendor_bill_id')->nullable()->index();
                $table->string('vendor_bill_number', 50)->nullable();
                $table->uuid('stock_in_id')->nullable()->index();
                $table->string('grn_number', 50)->nullable();
                $table->decimal('amount', 18, 4)->default(0.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('total_amount', 18, 4)->default(0.0000);
                $table->decimal('unapplied_amount', 18, 4)->default(0.0000);
                $table->string('reason', 255);
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, POSTED, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. BIR Form 2307 Certificates (Issued to Vendors on AP / Received from Customers on AR)
        if (!Schema::hasTable('acct_bir_2307_certificates')) {
            Schema::create('acct_bir_2307_certificates', function (Blueprint $table) {
                $table->uuid('certificate_id')->primary();
                $table->string('certificate_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('party_type', 20)->default('VENDOR'); // VENDOR, CUSTOMER
                $table->string('party_id', 50)->index();
                $table->string('party_name', 255);
                $table->string('party_tin', 50)->nullable();
                $table->string('payee_tin', 50)->nullable();
                $table->text('party_address')->nullable();
                $table->uuid('vendor_payment_id')->nullable()->index();
                $table->string('source_document_type', 40); // VENDOR_PAYMENT, COLLECTION_RECEIPT, VENDOR_BILL
                $table->string('source_document_id', 60)->index();
                $table->string('source_document_number', 50);
                $table->date('period_from');
                $table->date('period_to');
                $table->string('tax_code', 30);
                $table->string('atc_code', 30);
                $table->decimal('income_payment_amount', 18, 4)->default(0.0000);
                $table->decimal('gross_income_payment', 18, 4)->default(0.0000);
                $table->decimal('tax_withheld_amount', 18, 4)->default(0.0000);
                $table->decimal('amount_withheld', 18, 4)->default(0.0000);
                $table->string('status', 20)->default('ISSUED'); // ISSUED, RECEIVED, CANCELLED
                $table->string('signed_by', 100)->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Customer Masterlist (AR)
        if (!Schema::hasTable('acct_customers')) {
            Schema::create('acct_customers', function (Blueprint $table) {
                $table->uuid('customer_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('customer_code', 30);
                $table->string('customer_name', 255);
                $table->string('trade_name', 255)->nullable();
                $table->string('tin', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('contact_person', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('payment_terms_code', 30)->default('NET_30');
                $table->decimal('credit_limit', 18, 4)->default(0.0000);
                $table->decimal('current_balance', 18, 4)->default(0.0000);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'customer_code'], 'uq_customer_company_code');
            });
        }

        // 5. Sales Invoices Header & Lines (AR)
        if (!Schema::hasTable('acct_sales_invoices')) {
            Schema::create('acct_sales_invoices', function (Blueprint $table) {
                $table->uuid('invoice_id')->primary();
                $table->string('invoice_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->uuid('customer_id')->index();
                $table->string('customer_name', 255);
                $table->date('invoice_date')->index();
                $table->date('due_date')->index();
                $table->string('payment_terms_code', 30)->default('NET_30');
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('subtotal_amount', 18, 4)->default(0.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('total_amount', 18, 4)->default(0.0000);
                $table->decimal('paid_amount', 18, 4)->default(0.0000);
                $table->decimal('balance_due', 18, 4)->default(0.0000);
                $table->uuid('stock_out_id')->nullable()->index();
                $table->string('stock_out_number', 50)->nullable();
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, APPROVED, POSTED, PAID, PARTIAL, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->uuid('approval_request_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_sales_invoice_lines')) {
            Schema::create('acct_sales_invoice_lines', function (Blueprint $table) {
                $table->uuid('invoice_line_id')->primary();
                $table->uuid('invoice_id')->index();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->uuid('item_id')->nullable()->index();
                $table->string('item_code', 50)->nullable();
                $table->string('description', 255);
                $table->decimal('quantity', 18, 4)->default(1.0000);
                $table->string('uom', 20)->default('Unit');
                $table->decimal('unit_price', 18, 4)->default(0.0000);
                $table->string('tax_code', 30)->default('VAT_12');
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('subtotal', 18, 4)->default(0.0000);
                $table->decimal('total', 18, 4)->default(0.0000);
                $table->uuid('gl_revenue_account_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 6. Collection Receipts Header & Lines (AR Collections & PDC Receipt)
        if (!Schema::hasTable('acct_collection_receipts')) {
            Schema::create('acct_collection_receipts', function (Blueprint $table) {
                $table->uuid('receipt_id')->primary();
                $table->string('receipt_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->uuid('customer_id')->index();
                $table->string('customer_name', 255);
                $table->date('receipt_date')->index();
                $table->string('payment_method', 30)->default('CASH'); // CASH, BANK_TRANSFER, CHECK, PDC
                $table->uuid('deposit_account_id')->nullable()->index(); // Bank / Cash / PDC_RECEIVABLE GL account
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('gross_amount', 18, 4)->default(0.0000);
                $table->decimal('cwt_amount', 18, 4)->default(0.0000); // 2307 CWT Received
                $table->decimal('net_amount', 18, 4)->default(0.0000);
                $table->decimal('total_amount', 18, 4)->default(0.0000);
                $table->decimal('total_cwt_amount', 18, 4)->default(0.0000);
                $table->decimal('total_net_amount', 18, 4)->default(0.0000);
                $table->string('check_number', 50)->nullable();
                $table->date('check_date')->nullable();
                $table->date('check_maturity_date')->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('reference_number', 100)->nullable();
                $table->boolean('is_deposit_mode')->default(false); // Customer Advance Deposit
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, POSTED, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_collection_receipt_lines')) {
            Schema::create('acct_collection_receipt_lines', function (Blueprint $table) {
                $table->uuid('receipt_line_id')->primary();
                $table->uuid('receipt_id')->index();
                $table->uuid('sales_invoice_id')->nullable()->index();
                $table->string('invoice_number', 50)->nullable();
                $table->decimal('invoice_amount', 18, 4)->default(0.0000);
                $table->decimal('balance_before', 18, 4)->default(0.0000);
                $table->decimal('allocated_amount', 18, 4)->default(0.0000);
                $table->decimal('cwt_withheld', 18, 4)->default(0.0000);
                $table->string('cwt_tax_code', 30)->nullable();
                $table->decimal('cwt_rate_pct', 8, 4)->default(0.0000);
                $table->timestamps();
            });
        }

        // 7. Customer Credit & Debit Memos
        if (!Schema::hasTable('acct_customer_memos')) {
            Schema::create('acct_customer_memos', function (Blueprint $table) {
                $table->uuid('memo_id')->primary();
                $table->string('memo_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->uuid('customer_id')->index();
                $table->string('customer_name', 255);
                $table->string('memo_type', 20)->default('CREDIT'); // CREDIT, DEBIT
                $table->date('memo_date')->index();
                $table->uuid('sales_invoice_id')->nullable()->index();
                $table->string('invoice_number', 50)->nullable();
                $table->decimal('amount', 18, 4)->default(0.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('total_amount', 18, 4)->default(0.0000);
                $table->decimal('unapplied_amount', 18, 4)->default(0.0000);
                $table->string('reason', 255);
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, POSTED, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        // 8. Bank Accounts & Petty Cash Custodians (Cash & Bank)
        if (!Schema::hasTable('acct_bank_accounts')) {
            Schema::create('acct_bank_accounts', function (Blueprint $table) {
                $table->uuid('bank_account_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('account_name', 150);
                $table->string('account_number', 60);
                $table->string('bank_name', 100);
                $table->string('branch_name', 100)->nullable();
                $table->string('account_type', 30)->default('CHECKING'); // CHECKING, SAVINGS, PETTY_CASH
                $table->uuid('gl_account_id')->index(); // points to chart_of_accounts CASH_IN_BANK or CASH_ON_HAND
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('opening_balance', 18, 4)->default(0.0000);
                $table->decimal('current_balance', 18, 4)->default(0.0000);
                $table->string('custodian_name', 150)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 9. Employee Advances & Liquidations (Separate Subledger, isolated from AP)
        if (!Schema::hasTable('acct_employee_advances')) {
            Schema::create('acct_employee_advances', function (Blueprint $table) {
                $table->uuid('advance_id')->primary();
                $table->string('advance_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('employee_id', 50)->index();
                $table->string('employee_name', 150);
                $table->date('request_date')->index();
                $table->date('disbursement_date')->nullable();
                $table->date('settlement_due_date')->index();
                $table->decimal('amount', 18, 4)->default(0.0000);
                $table->uuid('disbursement_account_id')->nullable()->index(); // Bank / Cash account
                $table->uuid('gl_advance_account_id')->nullable()->index(); // EMPLOYEE_ADVANCES
                $table->string('purpose', 255);
                $table->string('status', 30)->default('PENDING')->index(); // PENDING, DISBURSED, PARTIALLY_LIQUIDATED, FULLY_LIQUIDATED, CANCELLED
                $table->decimal('liquidated_amount', 18, 4)->default(0.0000);
                $table->decimal('balance_due', 18, 4)->default(0.0000);
                $table->uuid('disbursement_journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('approved_by', 100)->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_employee_advance_liquidations')) {
            Schema::create('acct_employee_advance_liquidations', function (Blueprint $table) {
                $table->uuid('liquidation_id')->primary();
                $table->string('liquidation_number', 50)->unique();
                $table->uuid('advance_id')->index();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->date('liquidation_date')->index();
                $table->decimal('total_expense_amount', 18, 4)->default(0.0000);
                $table->decimal('vat_input_amount', 18, 4)->default(0.0000);
                $table->decimal('cash_returned_amount', 18, 4)->default(0.0000);
                $table->decimal('reimbursement_due_amount', 18, 4)->default(0.0000);
                $table->json('expense_details_json')->nullable();
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, POSTED, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('submitted_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Fund Transfers (Bank-to-Bank and Petty Cash Replenishment)
        if (!Schema::hasTable('acct_fund_transfers')) {
            Schema::create('acct_fund_transfers', function (Blueprint $table) {
                $table->uuid('transfer_id')->primary();
                $table->string('transfer_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->date('transfer_date')->index();
                $table->uuid('source_bank_account_id')->index();
                $table->uuid('destination_bank_account_id')->index();
                $table->decimal('amount', 18, 4)->default(0.0000);
                $table->decimal('fee_amount', 18, 4)->default(0.0000);
                $table->string('reference_number', 100)->nullable();
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, POSTED, CANCELLED
                $table->uuid('journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        // 11. Check Register & PDC Monitoring (PDC Lifecycle & DAIF Bounced Check Handling)
        if (!Schema::hasTable('acct_check_register')) {
            Schema::create('acct_check_register', function (Blueprint $table) {
                $table->uuid('check_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('direction', 20)->default('OUTGOING'); // INCOMING (from Customer), OUTGOING (to Vendor)
                $table->string('party_type', 20)->default('VENDOR'); // VENDOR, CUSTOMER
                $table->string('party_id', 50)->index();
                $table->string('party_name', 255);
                $table->string('bank_name', 100);
                $table->string('check_number', 50)->index();
                $table->date('check_date');
                $table->date('maturity_date')->index();
                $table->decimal('amount', 18, 4)->default(0.0000);
                $table->string('source_document_type', 40)->nullable(); // VENDOR_PAYMENT, COLLECTION_RECEIPT
                $table->string('source_document_id', 60)->nullable()->index();
                $table->string('status', 25)->default('ISSUED')->index(); // RECEIVED, ISSUED, DEPOSITED, CLEARED, BOUNCED_DAIF, CANCELLED, REPLACED
                $table->timestamp('cleared_at')->nullable();
                $table->timestamp('bounced_at')->nullable();
                $table->string('bounce_reason', 255)->nullable();
                $table->decimal('bounce_penalty_amount', 18, 4)->default(0.0000);
                $table->uuid('clearance_journal_id')->nullable()->index();
                $table->uuid('bounce_journal_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 12. Bank Statement Reconciliation
        if (!Schema::hasTable('acct_bank_reconciliations')) {
            Schema::create('acct_bank_reconciliations', function (Blueprint $table) {
                $table->uuid('reconciliation_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('bank_account_id')->index();
                $table->date('statement_date')->index();
                $table->decimal('statement_ending_balance', 18, 4)->default(0.0000);
                $table->decimal('gl_book_balance', 18, 4)->default(0.0000);
                $table->decimal('reconciled_balance', 18, 4)->default(0.0000);
                $table->decimal('difference', 18, 4)->default(0.0000);
                $table->string('status', 20)->default('OPEN')->index(); // OPEN, COMPLETED
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_bank_statement_lines')) {
            Schema::create('acct_bank_statement_lines', function (Blueprint $table) {
                $table->uuid('statement_line_id')->primary();
                $table->uuid('reconciliation_id')->index();
                $table->date('transaction_date');
                $table->string('reference_number', 100)->nullable();
                $table->string('description', 255);
                $table->decimal('debit_amount', 18, 4)->default(0.0000); // Withdrawals
                $table->decimal('credit_amount', 18, 4)->default(0.0000); // Deposits
                $table->boolean('is_matched')->default(false);
                $table->uuid('matched_journal_line_id')->nullable()->index();
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('acct_bank_statement_lines');
        Schema::dropIfExists('acct_bank_reconciliations');
        Schema::dropIfExists('acct_check_register');
        Schema::dropIfExists('acct_fund_transfers');
        Schema::dropIfExists('acct_employee_advance_liquidations');
        Schema::dropIfExists('acct_employee_advances');
        Schema::dropIfExists('acct_bank_accounts');
        Schema::dropIfExists('acct_customer_memos');
        Schema::dropIfExists('acct_collection_receipt_lines');
        Schema::dropIfExists('acct_collection_receipts');
        Schema::dropIfExists('acct_sales_invoice_lines');
        Schema::dropIfExists('acct_sales_invoices');
        Schema::dropIfExists('acct_customers');
        Schema::dropIfExists('acct_bir_2307_certificates');
        Schema::dropIfExists('acct_vendor_memos');
        Schema::dropIfExists('acct_vendor_payment_lines');
        Schema::dropIfExists('acct_vendor_payments');
        Schema::enableForeignKeyConstraints();
    }
};
