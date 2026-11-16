<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Fiscal Years
        if (!Schema::hasTable('acct_fiscal_years')) {
            Schema::create('acct_fiscal_years', function (Blueprint $table) {
                $table->uuid('fiscal_year_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('year_code', 20);
                $table->string('year_name', 100);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 20)->default('OPEN'); // OPEN, LOCKED, CLOSED
                $table->timestamps();

                $table->unique(['company_id', 'year_code'], 'uq_acct_fy_company_code');
            });
        }

        // 2. Fiscal Periods (Calendar Months)
        if (!Schema::hasTable('acct_fiscal_periods')) {
            Schema::create('acct_fiscal_periods', function (Blueprint $table) {
                $table->uuid('period_id')->primary();
                $table->uuid('fiscal_year_id')->index();
                $table->string('company_id', 50)->default('1')->index();
                $table->unsignedSmallInteger('period_number');
                $table->string('period_code', 20);
                $table->string('period_name', 100);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 20)->default('OPEN'); // OPEN, LOCKED, CLOSED
                $table->json('checklist_json')->nullable();
                $table->string('locked_by', 100)->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'period_code'], 'uq_acct_period_company_code');
                $table->index(['company_id', 'start_date', 'end_date'], 'idx_acct_period_dates');
            });
        }

        // 3. Currencies & Exchange Rates
        if (!Schema::hasTable('acct_currencies')) {
            Schema::create('acct_currencies', function (Blueprint $table) {
                $table->char('currency_code', 3)->primary();
                $table->string('currency_name', 80);
                $table->string('symbol', 10)->default('₱');
                $table->boolean('is_base_currency')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_exchange_rates')) {
            Schema::create('acct_exchange_rates', function (Blueprint $table) {
                $table->uuid('rate_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->char('from_currency_code', 3);
                $table->char('to_currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->date('effective_date');
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->index(['company_id', 'from_currency_code', 'effective_date'], 'idx_acct_fx_lookup');
            });
        }

        // 4. Payment Terms
        if (!Schema::hasTable('acct_payment_terms')) {
            Schema::create('acct_payment_terms', function (Blueprint $table) {
                $table->uuid('term_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('term_code', 30);
                $table->string('term_name', 100);
                $table->unsignedInteger('due_days')->default(30);
                $table->decimal('discount_pct', 6, 2)->default(0.00);
                $table->unsignedInteger('discount_days')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'term_code'], 'uq_acct_terms_company_code');
            });
        }

        // 5. Numbering Series & Gap Control
        if (!Schema::hasTable('acct_numbering_series')) {
            Schema::create('acct_numbering_series', function (Blueprint $table) {
                $table->uuid('series_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('module_code', 30); // JE, BILL, PV, VMEMO, SINV, CR, CMEMO, FT, ADV, FA, LOAN, LCV
                $table->string('module_name', 100);
                $table->string('prefix', 20);
                $table->unsignedBigInteger('next_number')->default(1);
                $table->unsignedTinyInteger('padding')->default(4);
                $table->string('reset_frequency', 20)->default('YEARLY'); // YEARLY, NEVER
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'module_code'], 'uq_acct_series_company_module');
            });
        }

        if (!Schema::hasTable('acct_numbering_gaps')) {
            Schema::create('acct_numbering_gaps', function (Blueprint $table) {
                $table->uuid('gap_id')->primary();
                $table->uuid('series_id')->index();
                $table->string('company_id', 50)->default('1');
                $table->string('module_code', 30);
                $table->string('reserved_number', 50);
                $table->timestamp('released_at')->nullable();
                $table->timestamp('reused_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'module_code', 'reused_at'], 'idx_acct_gap_reuse');
            });
        }

        // 6. Tax Codes (VAT & EWT/CWT Rules — Shared Across Wave 1/2/4)
        if (!Schema::hasTable('acct_tax_codes')) {
            Schema::create('acct_tax_codes', function (Blueprint $table) {
                $table->uuid('tax_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('tax_code', 30);
                $table->string('tax_name', 120);
                $table->string('tax_type', 30); // VAT_INPUT, VAT_OUTPUT, EWT, CWT, EXEMPT, ZERO_RATED
                $table->decimal('rate_pct', 8, 4)->default(0.0000);
                $table->string('atc_code', 30)->nullable();
                $table->string('bir_form', 30)->nullable(); // 2550Q, 1601-EQ, 2307
                $table->string('system_role_link', 60)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'tax_code'], 'uq_acct_tax_company_code');
            });
        }

        // 7. Chart of Accounts (Company-Scoped; Branch lives on transaction lines only)
        if (!Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->uuid('account_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('account_code', 30);
                $table->string('account_name', 150);
                $table->string('account_type', 30); // ASSET, LIABILITY, EQUITY, REVENUE, COGS, EXPENSE
                $table->string('normal_balance', 10); // DEBIT, CREDIT
                $table->string('cash_flow_category', 30)->default('OPERATING'); // OPERATING, INVESTING, FINANCING, CASH_EQUIVALENT, NONE
                $table->string('system_role', 60)->nullable()->index();
                // Enforces single active account per (company_id, system_role) across MySQL & SQLite
                $table->string('active_system_role_key', 120)->nullable()->unique('uq_coa_active_system_role');
                $table->uuid('parent_account_id')->nullable();
                $table->string('default_tax_code', 30)->nullable();
                $table->boolean('allow_manual_journal')->default(true);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'account_code'], 'uq_coa_company_code');
            });
        }

        // 8. General Ledger Journal Headers & Lines
        if (!Schema::hasTable('gl_journal_headers')) {
            Schema::create('gl_journal_headers', function (Blueprint $table) {
                $table->uuid('journal_id')->primary();
                $table->string('journal_number', 50)->unique();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->nullable()->index();
                $table->uuid('fiscal_period_id')->nullable()->index();
                $table->date('journal_date')->index();
                $table->string('journal_type', 30)->default('MANUAL');
                // MANUAL, PAYROLL, ACCRUAL, RECURRING, ADJUSTING, OPENING_BALANCE, CLOSING,
                // SYSTEM_AP, SYSTEM_AR, SYSTEM_INVENTORY, SYSTEM_DEPRECIATION, SYSTEM_LOAN, REVERSAL
                $table->string('source_module', 40)->nullable();
                $table->string('source_document_type', 50)->nullable();
                $table->string('source_document_id', 60)->nullable()->index();
                $table->string('source_document_number', 50)->nullable();
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('total_transaction_debit', 18, 4)->default(0.0000);
                $table->decimal('total_transaction_credit', 18, 4)->default(0.0000);
                $table->decimal('total_debit', 18, 4)->default(0.0000);
                $table->decimal('total_credit', 18, 4)->default(0.0000);
                $table->string('status', 20)->default('DRAFT')->index(); // DRAFT, APPROVED, POSTED, REVERSED
                $table->boolean('is_system_generated')->default(false);
                $table->uuid('reversed_from_journal_id')->nullable()->index();
                $table->uuid('reversal_journal_id')->nullable()->index();
                $table->text('reversal_reason')->nullable();
                $table->text('description')->nullable();
                $table->uuid('approval_request_id')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('approved_by', 100)->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('posted_by', 100)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->string('reversed_by', 100)->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('gl_journal_lines')) {
            Schema::create('gl_journal_lines', function (Blueprint $table) {
                $table->uuid('journal_line_id')->primary();
                $table->uuid('journal_id')->index();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->uuid('account_id')->index();
                $table->string('account_code', 30);
                $table->string('account_name', 150);
                $table->string('company_id', 50)->default('1');
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('department_id', 50)->nullable()->index();
                $table->string('project_code', 50)->nullable()->index();
                $table->string('partner_type', 30)->nullable();
                $table->string('partner_id', 60)->nullable();
                $table->string('partner_name', 150)->nullable();
                $table->string('tax_code', 30)->nullable();
                $table->string('description', 255)->nullable();
                $table->char('transaction_currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('transaction_debit', 18, 4)->default(0.0000);
                $table->decimal('transaction_credit', 18, 4)->default(0.0000);
                $table->decimal('base_debit', 18, 4)->default(0.0000);
                $table->decimal('base_credit', 18, 4)->default(0.0000);
                $table->boolean('is_fx_rounding_line')->default(false);
                $table->timestamps();
            });
        }

        // 9. Per-Period Account Balance Summary (Avoids hot-row COA lock contention)
        if (!Schema::hasTable('gl_account_period_balances')) {
            Schema::create('gl_account_period_balances', function (Blueprint $table) {
                $table->uuid('balance_id')->primary();
                $table->string('company_id', 50)->default('1');
                $table->uuid('fiscal_period_id')->index();
                $table->uuid('account_id')->index();
                $table->string('branch_id', 50)->default('ALL');
                $table->decimal('opening_debit', 18, 4)->default(0.0000);
                $table->decimal('opening_credit', 18, 4)->default(0.0000);
                $table->decimal('period_debit', 18, 4)->default(0.0000);
                $table->decimal('period_credit', 18, 4)->default(0.0000);
                $table->decimal('closing_debit', 18, 4)->default(0.0000);
                $table->decimal('closing_credit', 18, 4)->default(0.0000);
                $table->decimal('net_balance', 18, 4)->default(0.0000);
                $table->timestamps();

                $table->unique(
                    ['company_id', 'fiscal_period_id', 'account_id', 'branch_id'],
                    'uq_gl_period_bal_dim'
                );
            });
        }

        // 10. Recurring Journal Templates
        if (!Schema::hasTable('gl_recurring_templates')) {
            Schema::create('gl_recurring_templates', function (Blueprint $table) {
                $table->uuid('template_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('template_code', 30);
                $table->string('template_name', 150);
                $table->string('journal_type', 30)->default('RECURRING');
                $table->string('frequency', 20)->default('MONTHLY'); // MONTHLY, QUARTERLY, ANNUALLY
                $table->date('next_run_date');
                $table->date('last_run_date')->nullable();
                $table->boolean('auto_post')->default(false);
                $table->string('default_branch_id', 50)->default('BR-01');
                $table->text('description')->nullable();
                $table->json('lines_json');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'template_code'], 'uq_acct_rec_company_code');
            });
        }

        // 11. System-Wide Audit Log & Attachments
        if (!Schema::hasTable('acct_audit_logs')) {
            Schema::create('acct_audit_logs', function (Blueprint $table) {
                $table->uuid('audit_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->nullable();
                $table->string('module', 40)->index();
                $table->string('document_type', 50);
                $table->string('document_id', 60)->index();
                $table->string('document_number', 50)->nullable();
                $table->string('action', 40);
                $table->string('summary', 255);
                $table->json('payload_diff_json')->nullable();
                $table->string('acted_by', 100)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_document_attachments')) {
            Schema::create('acct_document_attachments', function (Blueprint $table) {
                $table->uuid('attachment_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('document_type', 50)->index();
                $table->string('document_id', 60)->index();
                $table->string('document_number', 50)->nullable();
                $table->string('file_name', 255);
                $table->string('file_type', 80)->nullable();
                $table->unsignedBigInteger('file_size_bytes')->default(0);
                $table->string('file_path', 255)->nullable();
                $table->string('uploaded_by', 100)->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();

        // 12. Database-Level Immutability Triggers (blocks mass/raw SQL edits & deletes on POSTED/REVERSED rows)
        $this->installImmutabilityTriggers();
    }

    protected function installImmutabilityTriggers(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_headers_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_headers_immutable_delete');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_lines_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_lines_immutable_delete');

            DB::unprepared("
                CREATE TRIGGER trg_gl_headers_immutable_update
                BEFORE UPDATE ON gl_journal_headers
                FOR EACH ROW
                WHEN OLD.status = 'REVERSED'
                    OR (
                        OLD.status = 'POSTED'
                        AND NOT (
                            NEW.status = 'REVERSED'
                            AND NEW.total_debit = OLD.total_debit
                            AND NEW.total_credit = OLD.total_credit
                            AND NEW.journal_date = OLD.journal_date
                            AND NEW.journal_number = OLD.journal_number
                            AND NEW.journal_type = OLD.journal_type
                            AND NEW.company_id = OLD.company_id
                        )
                    )
                BEGIN
                    SELECT RAISE(ABORT, 'Immutable Posted Record: Cannot modify a POSTED or REVERSED journal header.');
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER trg_gl_headers_immutable_delete
                BEFORE DELETE ON gl_journal_headers
                FOR EACH ROW
                WHEN OLD.status IN ('POSTED', 'REVERSED')
                BEGIN
                    SELECT RAISE(ABORT, 'Immutable Posted Record: Cannot delete a POSTED or REVERSED journal header.');
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER trg_gl_lines_immutable_update
                BEFORE UPDATE ON gl_journal_lines
                FOR EACH ROW
                WHEN (SELECT status FROM gl_journal_headers WHERE journal_id = OLD.journal_id) IN ('POSTED', 'REVERSED')
                BEGIN
                    SELECT RAISE(ABORT, 'Immutable Posted Record: Cannot modify lines of a POSTED or REVERSED journal.');
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER trg_gl_lines_immutable_delete
                BEFORE DELETE ON gl_journal_lines
                FOR EACH ROW
                WHEN (SELECT status FROM gl_journal_headers WHERE journal_id = OLD.journal_id) IN ('POSTED', 'REVERSED')
                BEGIN
                    SELECT RAISE(ABORT, 'Immutable Posted Record: Cannot delete lines of a POSTED or REVERSED journal.');
                END;
            ");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_headers_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_headers_immutable_delete');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_lines_immutable_update');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_lines_immutable_delete');

            DB::unprepared("
                CREATE TRIGGER trg_gl_headers_immutable_update
                BEFORE UPDATE ON gl_journal_headers
                FOR EACH ROW
                BEGIN
                    IF OLD.status = 'REVERSED'
                       OR (
                           OLD.status = 'POSTED'
                           AND NOT (
                               NEW.status = 'REVERSED'
                               AND NEW.total_debit = OLD.total_debit
                               AND NEW.total_credit = OLD.total_credit
                               AND NEW.journal_date = OLD.journal_date
                               AND NEW.journal_number = OLD.journal_number
                               AND NEW.journal_type = OLD.journal_type
                               AND NEW.company_id = OLD.company_id
                           )
                       ) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable Posted Record: Cannot modify a POSTED or REVERSED journal header.';
                    END IF;
                END
            ");

            DB::unprepared("
                CREATE TRIGGER trg_gl_headers_immutable_delete
                BEFORE DELETE ON gl_journal_headers
                FOR EACH ROW
                BEGIN
                    IF OLD.status IN ('POSTED', 'REVERSED') THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable Posted Record: Cannot delete a POSTED or REVERSED journal header.';
                    END IF;
                END
            ");

            DB::unprepared("
                CREATE TRIGGER trg_gl_lines_immutable_update
                BEFORE UPDATE ON gl_journal_lines
                FOR EACH ROW
                BEGIN
                    DECLARE hdr_status VARCHAR(20);
                    SELECT status INTO hdr_status FROM gl_journal_headers WHERE journal_id = OLD.journal_id LIMIT 1;
                    IF hdr_status IN ('POSTED', 'REVERSED') THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable Posted Record: Cannot modify lines of a POSTED or REVERSED journal.';
                    END IF;
                END
            ");

            DB::unprepared("
                CREATE TRIGGER trg_gl_lines_immutable_delete
                BEFORE DELETE ON gl_journal_lines
                FOR EACH ROW
                BEGIN
                    DECLARE hdr_status VARCHAR(20);
                    SELECT status INTO hdr_status FROM gl_journal_headers WHERE journal_id = OLD.journal_id LIMIT 1;
                    IF hdr_status IN ('POSTED', 'REVERSED') THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable Posted Record: Cannot delete lines of a POSTED or REVERSED journal.';
                    END IF;
                END
            ");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_lines_immutable_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_lines_immutable_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_headers_immutable_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_gl_headers_immutable_update');

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('acct_document_attachments');
        Schema::dropIfExists('acct_audit_logs');
        Schema::dropIfExists('gl_recurring_templates');
        Schema::dropIfExists('gl_account_period_balances');
        Schema::dropIfExists('gl_journal_lines');
        Schema::dropIfExists('gl_journal_headers');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('acct_tax_codes');
        Schema::dropIfExists('acct_numbering_gaps');
        Schema::dropIfExists('acct_numbering_series');
        Schema::dropIfExists('acct_payment_terms');
        Schema::dropIfExists('acct_exchange_rates');
        Schema::dropIfExists('acct_currencies');
        Schema::dropIfExists('acct_fiscal_periods');
        Schema::dropIfExists('acct_fiscal_years');
        Schema::enableForeignKeyConstraints();
    }
};

