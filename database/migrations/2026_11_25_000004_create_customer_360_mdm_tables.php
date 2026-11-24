<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('acct_customers')) {
            Schema::table('acct_customers', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_customers', 'parent_customer_id')) {
                    $table->uuid('parent_customer_id')->nullable()->index();
                }
                if (!Schema::hasColumn('acct_customers', 'account_stage')) {
                    $table->string('account_stage', 20)->default('ACTIVE')->index(); // PROSPECT, ACTIVE, ARCHIVED
                }
                if (!Schema::hasColumn('acct_customers', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable();
                }
                if (!Schema::hasColumn('acct_customers', 'converted_at')) {
                    $table->timestamp('converted_at')->nullable();
                }
                if (!Schema::hasColumn('acct_customers', 'website')) {
                    $table->string('website', 200)->nullable();
                }
                if (!Schema::hasColumn('acct_customers', 'industry')) {
                    $table->string('industry', 100)->nullable();
                }
                if (!Schema::hasColumn('acct_customers', 'tax_exempt_cert_no')) {
                    $table->string('tax_exempt_cert_no', 100)->nullable();
                }
            });
        }

        if (!Schema::hasTable('acct_customer_contacts')) {
            Schema::create('acct_customer_contacts', function (Blueprint $table) {
                $table->uuid('contact_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('customer_id')->index();
                $table->string('full_name', 150);
                $table->string('role_title', 100)->nullable();
                $table->string('department', 100)->nullable(); // PURCHASING, AP, EXECUTIVE, RECEIVING
                $table->string('email', 150)->nullable();
                $table->string('phone', 60)->nullable();
                $table->boolean('is_primary')->default(false)->index();
                $table->boolean('receives_invoices')->default(false);
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'is_primary'], 'idx_cust_contacts_primary');
            });
        }

        if (!Schema::hasTable('acct_customer_addresses')) {
            Schema::create('acct_customer_addresses', function (Blueprint $table) {
                $table->uuid('address_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('customer_id')->index();
                $table->string('address_type', 20)->default('SHIPPING')->index(); // BILLING, SHIPPING, BOTH
                $table->string('label', 120);
                $table->string('address_line', 500);
                $table->string('city', 120)->nullable();
                $table->string('province', 120)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('receiving_contact', 150)->nullable();
                $table->string('receiving_phone', 60)->nullable();
                $table->string('delivery_window', 120)->nullable();
                $table->boolean('is_default')->default(false)->index();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_customer_price_lists')) {
            Schema::create('acct_customer_price_lists', function (Blueprint $table) {
                $table->uuid('price_list_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('customer_id')->index();
                $table->unsignedBigInteger('inventory_item_id')->nullable()->index();
                $table->string('internal_sku', 100)->nullable();
                $table->string('item_name', 255);
                $table->string('customer_sku', 100)->nullable();
                $table->string('uom', 30)->default('PCS');
                $table->decimal('standard_price', 18, 4)->default(0.0000);
                $table->decimal('contract_price', 18, 4)->default(0.0000);
                $table->decimal('discount_pct', 6, 2)->default(0.00);
                $table->decimal('min_qty', 18, 4)->default(1.0000);
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'inventory_item_id'], 'idx_cust_pricelist_item');
            });
        }

        if (!Schema::hasTable('acct_customer_documents')) {
            Schema::create('acct_customer_documents', function (Blueprint $table) {
                $table->uuid('document_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('customer_id')->index();
                $table->string('doc_type', 40)->default('CONTRACT')->index(); // BIR_2303_COR, TAX_EXEMPTION, CONTRACT, NDA, CREDIT_APP
                $table->string('document_title', 200);
                $table->string('reference_no', 120)->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable()->index();
                $table->string('file_path', 500)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->text('notes')->nullable();
                $table->string('uploaded_by', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('acct_customer_activities')) {
            Schema::create('acct_customer_activities', function (Blueprint $table) {
                $table->uuid('activity_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('customer_id')->index();
                $table->string('activity_type', 30)->default('NOTE')->index(); // CALL, EMAIL, MEETING, REMINDER, NOTE, SYSTEM
                $table->string('subject', 200);
                $table->text('description')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->string('acted_by', 100);
                $table->timestamp('acted_at')->index();
                $table->timestamps();

                $table->index(['customer_id', 'acted_at'], 'idx_cust_activity_time');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_customer_activities');
        Schema::dropIfExists('acct_customer_documents');
        Schema::dropIfExists('acct_customer_price_lists');
        Schema::dropIfExists('acct_customer_addresses');
        Schema::dropIfExists('acct_customer_contacts');

        if (Schema::hasTable('acct_customers')) {
            foreach (['parent_customer_id', 'account_stage', 'archived_at', 'converted_at', 'website', 'industry', 'tax_exempt_cert_no'] as $col) {
                if (!Schema::hasColumn('acct_customers', $col)) {
                    continue;
                }
                try {
                    Schema::table('acct_customers', function (Blueprint $t) use ($col) {
                        $t->dropIndex([$col]);
                    });
                } catch (\Throwable $e) {
                }
                Schema::table('acct_customers', function (Blueprint $t) use ($col) {
                    $t->dropColumn($col);
                });
            }
        }
    }
};
