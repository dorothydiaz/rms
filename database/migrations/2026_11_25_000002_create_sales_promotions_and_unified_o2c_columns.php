<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_discounts')) {
            Schema::create('sales_discounts', function (Blueprint $table) {
                $table->uuid('discount_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('discount_code', 40);
                $table->string('discount_name', 180);
                $table->string('discount_category', 30)->default('COMMERCIAL');
                $table->string('discount_type', 20)->default('PERCENT');
                $table->decimal('discount_value', 18, 4)->default(0.0000);
                $table->decimal('min_order_amount', 18, 4)->default(0.0000);
                $table->decimal('max_discount_cap', 18, 4)->nullable();
                $table->string('applicable_customer_tier', 30)->default('ALL');
                $table->boolean('vat_exempt_override')->default(false);
                $table->boolean('requires_manager_approval')->default(false);
                $table->boolean('stackable')->default(false);
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'discount_code'], 'uq_sales_discount_co_code');
            });
        }

        if (!Schema::hasTable('sales_vouchers')) {
            Schema::create('sales_vouchers', function (Blueprint $table) {
                $table->uuid('voucher_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('voucher_code', 50);
                $table->string('voucher_name', 180);
                $table->string('discount_type', 20)->default('FIXED');
                $table->decimal('discount_value', 18, 4)->default(0.0000);
                $table->decimal('min_spend_amount', 18, 4)->default(0.0000);
                $table->decimal('max_discount_amount', 18, 4)->nullable();
                $table->unsignedInteger('max_total_uses')->nullable();
                $table->unsignedInteger('max_uses_per_customer')->default(1);
                $table->unsignedInteger('current_uses_count')->default(0);
                $table->uuid('customer_id')->nullable()->index();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->string('status', 20)->default('ACTIVE')->index();
                $table->text('notes')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'voucher_code'], 'uq_sales_voucher_co_code');
            });
        }

        if (!Schema::hasTable('sales_voucher_redemptions')) {
            Schema::create('sales_voucher_redemptions', function (Blueprint $table) {
                $table->uuid('redemption_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->uuid('voucher_id')->index();
                $table->uuid('sales_order_id')->index();
                $table->uuid('customer_id')->index();
                $table->decimal('discount_applied', 18, 4)->default(0.0000);
                $table->string('status', 20)->default('REDEEMED')->index();
                $table->timestamp('redeemed_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->index(['voucher_id', 'customer_id', 'status'], 'idx_voucher_cust_status');
            });
        }

        if (!Schema::hasTable('sales_bundles')) {
            Schema::create('sales_bundles', function (Blueprint $table) {
                $table->uuid('bundle_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('bundle_code', 50);
                $table->string('bundle_name', 200);
                $table->text('description')->nullable();
                $table->decimal('bundle_price', 18, 4)->default(0.0000);
                $table->string('tax_code', 30)->default('VAT12_OUT');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->string('created_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'bundle_code'], 'uq_sales_bundle_co_code');
            });
        }

        if (!Schema::hasTable('sales_bundle_items')) {
            Schema::create('sales_bundle_items', function (Blueprint $table) {
                $table->uuid('bundle_item_id')->primary();
                $table->uuid('bundle_id')->index();
                $table->unsignedBigInteger('inventory_item_id')->nullable()->index();
                $table->string('sku', 100)->nullable();
                $table->string('item_name', 255);
                $table->decimal('quantity', 18, 4)->default(1.0000);
                $table->string('uom', 30)->default('PCS');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_orders', 'document_stage')) {
                    $table->string('document_stage', 20)->default('ORDER')->index();
                }
                if (!Schema::hasColumn('sales_orders', 'valid_until_date')) {
                    $table->date('valid_until_date')->nullable();
                }
                if (!Schema::hasColumn('sales_orders', 'revision_no')) {
                    $table->unsignedSmallInteger('revision_no')->default(1);
                }
                if (!Schema::hasColumn('sales_orders', 'quote_group_id')) {
                    $table->uuid('quote_group_id')->nullable()->index();
                }
                if (!Schema::hasColumn('sales_orders', 'confirmed_at')) {
                    $table->timestamp('confirmed_at')->nullable();
                }
                if (!Schema::hasColumn('sales_orders', 'confirmed_by')) {
                    $table->string('confirmed_by', 100)->nullable();
                }
                if (!Schema::hasColumn('sales_orders', 'discount_id')) {
                    $table->uuid('discount_id')->nullable()->index();
                }
                if (!Schema::hasColumn('sales_orders', 'discount_code')) {
                    $table->string('discount_code', 40)->nullable();
                }
                if (!Schema::hasColumn('sales_orders', 'voucher_id')) {
                    $table->uuid('voucher_id')->nullable()->index();
                }
                if (!Schema::hasColumn('sales_orders', 'voucher_code')) {
                    $table->string('voucher_code', 50)->nullable();
                }
                if (!Schema::hasColumn('sales_orders', 'voucher_discount_amount')) {
                    $table->decimal('voucher_discount_amount', 18, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('sales_orders', 'salesperson_employee_id')) {
                    $table->string('salesperson_employee_id', 64)->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('sales_order_lines')) {
            Schema::table('sales_order_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_order_lines', 'bundle_id')) {
                    $table->uuid('bundle_id')->nullable()->index();
                }
                if (!Schema::hasColumn('sales_order_lines', 'bundle_code')) {
                    $table->string('bundle_code', 50)->nullable();
                }
                if (!Schema::hasColumn('sales_order_lines', 'bundle_name')) {
                    $table->string('bundle_name', 200)->nullable();
                }
            });
        }

        if (Schema::hasTable('acct_sales_invoices')) {
            Schema::table('acct_sales_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_sales_invoices', 'discount_amount')) {
                    $table->decimal('discount_amount', 18, 4)->default(0.0000);
                }
            });
        }
    }

    public function down(): void
    {
        $dropCols = function (string $table, array $cols) {
            if (!Schema::hasTable($table)) {
                return;
            }
            foreach ($cols as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    continue;
                }
                try {
                    Schema::table($table, function (Blueprint $bp) use ($col) {
                        $bp->dropIndex([$col]);
                    });
                } catch (\Throwable $e) {
                }
                Schema::table($table, function (Blueprint $bp) use ($col) {
                    $bp->dropColumn($col);
                });
            }
        };

        $dropCols('acct_sales_invoices', ['discount_amount']);
        $dropCols('sales_order_lines', ['bundle_id', 'bundle_code', 'bundle_name']);
        $dropCols('sales_orders', [
            'document_stage', 'valid_until_date', 'revision_no', 'quote_group_id',
            'confirmed_at', 'confirmed_by', 'discount_id', 'discount_code',
            'voucher_id', 'voucher_code', 'voucher_discount_amount', 'salesperson_employee_id',
        ]);

        Schema::dropIfExists('sales_bundle_items');
        Schema::dropIfExists('sales_bundles');
        Schema::dropIfExists('sales_voucher_redemptions');
        Schema::dropIfExists('sales_vouchers');
        Schema::dropIfExists('sales_discounts');
    }
};
