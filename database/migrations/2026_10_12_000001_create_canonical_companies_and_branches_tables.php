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
        Schema::disableForeignKeyConstraints();

        // 1. Create canonical 'companies' table if not exists
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id('company_id');
                $table->string('company_code', 50)->unique();
                $table->unsignedBigInteger('parent_company_id')->nullable()->index();
                $table->string('company_legal_name', 255);
                $table->string('company_trade_name', 255)->nullable();
                $table->string('company_registration_no', 100)->nullable();
                $table->string('company_tax_id', 50)->nullable();
                $table->string('company_tax_type', 50)->default('VAT Registered');
                $table->string('company_rdo_code', 20)->nullable();
                $table->char('company_base_currency_code', 3)->default('PHP');
                $table->string('company_timezone', 50)->default('Asia/Manila');
                $table->smallInteger('fiscal_year_start_month')->default(1);
                $table->string('company_address_line_1', 150)->nullable();
                $table->string('company_address_line_2', 150)->nullable();
                $table->string('company_city', 100)->nullable();
                $table->string('company_state_province_code', 50)->nullable();
                $table->string('company_postal_code', 20)->nullable();
                $table->char('company_country_code', 2)->default('PH');
                $table->string('company_phone_number', 30)->nullable();
                $table->string('company_email_address', 255)->nullable();
                $table->string('company_website_url', 255)->nullable();
                $table->string('company_contact_person', 100)->nullable();
                $table->string('company_logo', 255)->nullable();
                $table->string('company_favicon', 255)->nullable();
                $table->text('invoice_header_text')->nullable();
                $table->text('invoice_footer_text')->nullable();
                $table->string('bir_permit_number', 100)->nullable();
                $table->boolean('share_chart_of_accounts')->default(true);
                $table->boolean('share_customer_vendor_directory')->default(true);
                $table->boolean('share_product_catalog')->default(true);
                $table->string('company_type', 50)->default('Company');
                $table->text('company_notes')->nullable();
                $table->boolean('company_is_active')->default(true);
                $table->timestamp('company_created_at')->nullable();
                $table->unsignedBigInteger('company_created_by')->nullable();
                $table->timestamp('company_updated_at')->nullable();
                $table->unsignedBigInteger('company_updated_by')->nullable();
            });

            // Virtual column id for backward-compatibility
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('ALTER TABLE "companies" ADD COLUMN id INTEGER GENERATED ALWAYS AS (company_id) VIRTUAL');
            } else {
                DB::statement('ALTER TABLE `companies` ADD COLUMN `id` BIGINT UNSIGNED GENERATED ALWAYS AS (`company_id`) VIRTUAL');
            }
        }

        // 2. Create canonical 'branches' table if not exists
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id('branch_id');
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('branch_code', 50)->unique();
                $table->string('branch_name', 150);
                $table->string('branch_tax_code', 50)->nullable();
                $table->string('branch_type', 50)->default('Store / Dine-In');
                $table->string('branch_address_line_1', 150)->nullable();
                $table->string('branch_address_line_2', 150)->nullable();
                $table->string('branch_city', 100)->nullable();
                $table->string('branch_state_province_code', 50)->nullable();
                $table->string('branch_postal_code', 20)->nullable();
                $table->char('branch_country_code', 2)->default('PH');
                $table->string('branch_contact_person', 100)->nullable();
                $table->string('branch_phone_number', 30)->nullable();
                $table->string('branch_email_address', 255)->nullable();
                $table->string('operating_hours', 150)->nullable()->default('10:00 AM - 10:00 PM');
                $table->boolean('branch_is_active')->default(true);
                $table->timestamp('branch_created_at')->nullable();
                $table->unsignedBigInteger('branch_created_by')->nullable();
                $table->timestamp('branch_updated_at')->nullable();
                $table->unsignedBigInteger('branch_updated_by')->nullable();
            });

            // Virtual column id for backward-compatibility
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('ALTER TABLE "branches" ADD COLUMN id INTEGER GENERATED ALWAYS AS (branch_id) VIRTUAL');
            } else {
                DB::statement('ALTER TABLE `branches` ADD COLUMN `id` BIGINT UNSIGNED GENERATED ALWAYS AS (`branch_id`) VIRTUAL');
            }
        }

        // 3. Migrate initial data from hr_companies and hr_branches if they exist
        if (Schema::hasTable('hr_companies') && DB::table('companies')->count() === 0) {
            $hrCompanies = DB::table('hr_companies')->get();
            foreach ($hrCompanies as $hc) {
                DB::table('companies')->insert([
                    'company_id' => $hc->company_id ?? $hc->id,
                    'company_code' => $hc->company_code ?? ('COMP-' . $hc->id),
                    'parent_company_id' => null,
                    'company_legal_name' => $hc->company_legal_name ?? 'Company ' . $hc->id,
                    'company_trade_name' => $hc->company_legal_name ?? 'Company ' . $hc->id,
                    'company_registration_no' => 'SEC-2024-' . str_pad($hc->id, 5, '0', STR_PAD_LEFT),
                    'company_tax_id' => $hc->company_tax_id ?? '000-000-000-000',
                    'company_tax_type' => 'VAT Registered',
                    'company_rdo_code' => '047',
                    'company_base_currency_code' => 'PHP',
                    'company_timezone' => 'Asia/Manila',
                    'fiscal_year_start_month' => 1,
                    'company_address_line_1' => $hc->company_address ?? 'Manila, Philippines',
                    'company_phone_number' => $hc->company_phone_number ?? '+63 2 8000 0000',
                    'company_email_address' => $hc->company_email_address ?? 'info@company.ph',
                    'company_contact_person' => $hc->company_contact_person ?? 'Administrator',
                    'company_logo' => $hc->company_logo ?? null,
                    'company_type' => $hc->company_type ?? 'Company',
                    'company_notes' => $hc->company_notes ?? null,
                    'company_is_active' => $hc->company_is_active ?? 1,
                    'company_created_at' => $hc->company_created_at ?? now(),
                    'company_updated_at' => $hc->company_updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('hr_branches') && DB::table('branches')->count() === 0) {
            $hrBranches = DB::table('hr_branches')->get();
            foreach ($hrBranches as $hb) {
                DB::table('branches')->insert([
                    'branch_id' => $hb->branch_id ?? $hb->id,
                    'company_id' => $hb->branch_company_id ?? 3,
                    'branch_code' => $hb->branch_code ?? ('BR-' . $hb->id),
                    'branch_name' => $hb->branch_name ?? 'Branch ' . $hb->id,
                    'branch_tax_code' => '0000' . $hb->id,
                    'branch_type' => str_contains(strtolower($hb->branch_name ?? ''), 'commissary') ? 'Central Commissary' : 'Store / Dine-In',
                    'branch_address_line_1' => $hb->branch_address ?? 'Metro Manila',
                    'branch_phone_number' => $hb->branch_phone_number ?? null,
                    'branch_email_address' => $hb->branch_email_address ?? null,
                    'operating_hours' => '10:00 AM - 10:00 PM',
                    'branch_is_active' => $hb->branch_is_active ?? 1,
                    'branch_created_at' => $hb->branch_created_at ?? now(),
                    'branch_updated_at' => $hb->branch_updated_at ?? now(),
                ]);
            }
        }

        // 4. Create 'business_legal_documents' table (Document Vault)
        if (!Schema::hasTable('business_legal_documents')) {
            Schema::create('business_legal_documents', function (Blueprint $table) {
                $table->id();
                $table->string('documentable_type', 100)->default('App\\Models\\Company');
                $table->unsignedBigInteger('documentable_id')->index();
                $table->string('document_category', 100);
                $table->string('document_name', 150);
                $table->string('document_number', 100)->nullable();
                $table->string('issuing_authority', 150)->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiration_date')->nullable()->index();
                $table->string('file_path', 255);
                $table->string('file_type', 50)->nullable();
                $table->integer('file_size_kb')->nullable();
                $table->string('status', 50)->default('Active');
                $table->integer('renewal_reminder_days')->default(30);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        // 5. Create 'user_companies' and 'user_branches' pivot tables
        if (!Schema::hasTable('user_companies')) {
            Schema::create('user_companies', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id');
                $table->primary(['user_id', 'company_id']);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_branches')) {
            Schema::create('user_branches', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('branch_id');
                $table->primary(['user_id', 'branch_id']);
                $table->timestamps();
            });
        }

        // 6. Add default landing columns to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'default_company_id')) {
                    $table->unsignedBigInteger('default_company_id')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('users', 'default_branch_id')) {
                    $table->unsignedBigInteger('default_branch_id')->nullable()->after('default_company_id');
                }
            });
        }

        // 7. Seed default user assignments
        $firstCompany = DB::table('companies')->where('company_type', 'Company')->first() ?? DB::table('companies')->first();
        $firstBranch = DB::table('branches')->first();
        if ($firstCompany && $firstBranch) {
            $users = DB::table('users')->get();
            foreach ($users as $u) {
                DB::table('users')->where('id', $u->id)->update([
                    'default_company_id' => $firstCompany->company_id,
                    'default_branch_id' => $u->branch_id ?? $firstBranch->branch_id,
                ]);

                // Insert into user_companies & user_branches
                DB::statement('INSERT OR IGNORE INTO user_companies (user_id, company_id, created_at, updated_at) VALUES (?, ?, ?, ?)', [
                    $u->id, $firstCompany->company_id, now(), now()
                ]);
                DB::statement('INSERT OR IGNORE INTO user_branches (user_id, branch_id, created_at, updated_at) VALUES (?, ?, ?, ?)', [
                    $u->id, $u->branch_id ?? $firstBranch->branch_id, now(), now()
                ]);
            }
        }

        // 8. Backward-compatible physical tables and sync triggers for hr_companies and hr_branches
        // In SQLite, foreign keys cannot reference VIEWs, so hr_companies and hr_branches remain physical tables
        // synchronized bidirectionally with canonical companies and branches via database triggers.
        if (self::isView('hr_companies')) {
            DB::statement('DROP VIEW IF EXISTS "hr_companies"');
        }
        if (self::isView('hr_branches')) {
            DB::statement('DROP VIEW IF EXISTS "hr_branches"');
        }

        if (!Schema::hasTable('hr_companies')) {
            DB::statement('CREATE TABLE "hr_companies" (
                "company_id" integer primary key autoincrement not null,
                "company_legal_name" varchar not null,
                "company_code" varchar not null,
                "company_tax_id" varchar,
                "company_email_address" varchar,
                "company_phone_number" varchar,
                "company_address" text,
                "company_logo" varchar,
                "company_created_at" datetime,
                "company_updated_at" datetime,
                "company_type" varchar not null default (\'Company\'),
                "company_contact_person" varchar,
                "company_is_active" tinyint(1) not null default (1),
                "company_notes" text,
                "id" INTEGER GENERATED ALWAYS AS (company_id) VIRTUAL
            )');

            DB::statement('INSERT INTO hr_companies SELECT 
                company_id, company_legal_name, company_code, company_tax_id, company_email_address, company_phone_number,
                company_address_line_1, company_logo, company_created_at, company_updated_at, company_type, company_contact_person,
                company_is_active, company_notes
            FROM companies');
        }

        if (!Schema::hasTable('hr_branches')) {
            DB::statement('CREATE TABLE "hr_branches" (
                "branch_id" integer primary key autoincrement not null,
                "branch_company_id" integer not null default (1),
                "branch_name" varchar not null,
                "branch_code" varchar not null,
                "branch_address" text,
                "branch_phone_number" varchar,
                "branch_email_address" varchar,
                "branch_is_active" tinyint(1) not null default (1),
                "branch_created_at" datetime,
                "branch_updated_at" datetime,
                "id" INTEGER GENERATED ALWAYS AS (branch_id) VIRTUAL
            )');

            DB::statement('INSERT INTO hr_branches SELECT 
                branch_id, company_id, branch_name, branch_code, branch_address_line_1, branch_phone_number, branch_email_address,
                branch_is_active, branch_created_at, branch_updated_at
            FROM branches');
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS trg_branches_to_hr_insert');
            DB::statement('CREATE TRIGGER trg_branches_to_hr_insert AFTER INSERT ON branches
            BEGIN
                INSERT OR REPLACE INTO hr_branches (branch_id, branch_company_id, branch_name, branch_code, branch_address, branch_phone_number, branch_email_address, branch_is_active, branch_created_at, branch_updated_at)
                VALUES (NEW.branch_id, NEW.company_id, NEW.branch_name, NEW.branch_code, NEW.branch_address_line_1, NEW.branch_phone_number, NEW.branch_email_address, NEW.branch_is_active, NEW.branch_created_at, NEW.branch_updated_at);
            END;');

            DB::statement('DROP TRIGGER IF EXISTS trg_branches_to_hr_update');
            DB::statement('CREATE TRIGGER trg_branches_to_hr_update AFTER UPDATE ON branches
            BEGIN
                UPDATE hr_branches SET
                    branch_company_id = NEW.company_id,
                    branch_name = NEW.branch_name,
                    branch_code = NEW.branch_code,
                    branch_address = NEW.branch_address_line_1,
                    branch_phone_number = NEW.branch_phone_number,
                    branch_email_address = NEW.branch_email_address,
                    branch_is_active = NEW.branch_is_active,
                    branch_updated_at = NEW.branch_updated_at
                WHERE branch_id = NEW.branch_id;
            END;');

            DB::statement('DROP TRIGGER IF EXISTS trg_branches_to_hr_delete');
            DB::statement('CREATE TRIGGER trg_branches_to_hr_delete AFTER DELETE ON branches
            BEGIN
                DELETE FROM hr_branches WHERE branch_id = OLD.branch_id;
            END;');
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Check if a table is actually a view.
     */
    protected static function isView(string $name): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne("SELECT type FROM sqlite_master WHERE name = ?", [$name]);
            return $row && $row->type === 'view';
        }
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (self::isView('hr_companies')) {
            DB::statement('DROP VIEW IF EXISTS "hr_companies"');
        }
        if (self::isView('hr_branches')) {
            DB::statement('DROP VIEW IF EXISTS "hr_branches"');
        }

        Schema::dropIfExists('user_branches');
        Schema::dropIfExists('user_companies');
        Schema::dropIfExists('business_legal_documents');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'default_branch_id')) {
                    $table->dropColumn('default_branch_id');
                }
                if (Schema::hasColumn('users', 'default_company_id')) {
                    $table->dropColumn('default_company_id');
                }
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
