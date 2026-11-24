<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_quotes')) {
            Schema::create('sales_quotes', function (Blueprint $table) {
                $table->uuid('quote_id')->primary();
                $table->string('quote_number', 50);
                $table->uuid('quote_group_id')->nullable()->index();
                $table->unsignedSmallInteger('revision_no')->default(1);
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('source_warehouse_id', 64)->nullable();
                $table->string('source_warehouse_name', 255)->nullable();
                $table->uuid('customer_id')->index();
                $table->string('customer_name', 255);
                $table->string('customer_contact', 150)->nullable();
                $table->string('customer_email', 150)->nullable();
                $table->string('customer_phone', 50)->nullable();
                $table->date('quote_date')->index();
                $table->date('valid_until_date')->nullable()->index();
                $table->date('requested_delivery_date')->nullable();
                $table->string('payment_terms_code', 30)->default('NET_30');
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('subtotal_amount', 18, 4)->default(0.0000);
                $table->string('discount_type', 20)->default('NONE');
                $table->decimal('discount_value', 18, 4)->default(0.0000);
                $table->decimal('discount_amount', 18, 4)->default(0.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('gross_total', 18, 4)->default(0.0000);
                $table->decimal('estimated_cost_total', 18, 4)->default(0.0000);
                $table->decimal('estimated_margin_pct', 8, 2)->default(0.00);
                $table->string('status', 30)->default('DRAFT')->index();
                $table->uuid('sales_order_id')->nullable()->index();
                $table->string('sales_order_number', 50)->nullable();
                $table->string('customer_reference', 120)->nullable();
                $table->text('notes')->nullable();
                $table->text('terms_and_conditions')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->string('confirmed_by', 100)->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'quote_number'], 'uq_sales_quote_company_no');
                $table->index(['company_id', 'status'], 'idx_sales_quotes_co_status');
                $table->index(['company_id', 'customer_id'], 'idx_sales_quotes_co_cust');
            });
        }

        if (!Schema::hasTable('sales_quote_lines')) {
            Schema::create('sales_quote_lines', function (Blueprint $table) {
                $table->uuid('quote_line_id')->primary();
                $table->uuid('quote_id')->index();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->unsignedBigInteger('inventory_item_id')->nullable()->index();
                $table->string('sku', 100)->nullable();
                $table->string('item_name', 255);
                $table->text('description')->nullable();
                $table->decimal('quantity', 18, 4)->default(1.0000);
                $table->string('uom', 30)->default('PCS');
                $table->decimal('uom_conversion_factor', 18, 6)->default(1.000000);
                $table->decimal('unit_cost_snapshot', 18, 4)->default(0.0000);
                $table->decimal('unit_price', 18, 4)->default(0.0000);
                $table->decimal('discount_pct', 8, 4)->default(0.0000);
                $table->decimal('discount_amount', 18, 4)->default(0.0000);
                $table->string('tax_code', 30)->default('VAT12_OUT');
                $table->decimal('tax_rate_pct', 8, 4)->default(12.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('line_subtotal', 18, 4)->default(0.0000);
                $table->decimal('line_total', 18, 4)->default(0.0000);
                $table->text('line_notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->uuid('sales_order_id')->primary();
                $table->string('order_number', 50);
                $table->uuid('quote_id')->nullable()->index();
                $table->string('quote_number', 50)->nullable();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->string('source_warehouse_id', 64)->nullable();
                $table->string('source_warehouse_name', 255)->nullable();
                $table->uuid('customer_id')->index();
                $table->string('customer_name', 255);
                $table->string('customer_trade_name', 255)->nullable();
                $table->string('customer_tin', 50)->nullable();
                $table->string('customer_contact', 150)->nullable();
                $table->string('customer_email', 150)->nullable();
                $table->string('customer_phone', 50)->nullable();
                $table->text('billing_address')->nullable();
                $table->text('shipping_address')->nullable();
                $table->string('customer_po_number', 120)->nullable();
                $table->date('order_date')->index();
                $table->date('requested_delivery_date')->nullable();
                $table->string('payment_terms_code', 30)->default('NET_30');
                $table->char('currency_code', 3)->default('PHP');
                $table->decimal('exchange_rate', 18, 6)->default(1.000000);
                $table->decimal('subtotal_amount', 18, 4)->default(0.0000);
                $table->string('discount_type', 20)->default('NONE');
                $table->decimal('discount_value', 18, 4)->default(0.0000);
                $table->decimal('discount_amount', 18, 4)->default(0.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('gross_total', 18, 4)->default(0.0000);
                $table->decimal('total_cost_snapshot', 18, 4)->default(0.0000);
                $table->decimal('amount_billed', 18, 4)->default(0.0000);
                $table->decimal('amount_paid', 18, 4)->default(0.0000);
                $table->decimal('balance_due', 18, 4)->default(0.0000);
                $table->string('payment_status', 30)->default('UNBILLED')->index();
                $table->string('fulfillment_status', 30)->default('UNFULFILLED')->index();
                $table->string('credit_review_status', 30)->default('NOT_EVALUATED')->index();
                $table->decimal('credit_limit_snapshot', 18, 4)->default(0.0000);
                $table->decimal('credit_exposure_snapshot', 18, 4)->default(0.0000);
                $table->decimal('credit_breach_amount', 18, 4)->default(0.0000);
                $table->json('credit_review_flags_json')->nullable();
                $table->string('credit_reviewed_by', 100)->nullable();
                $table->timestamp('credit_reviewed_at')->nullable();
                $table->text('credit_review_remarks')->nullable();
                $table->uuid('approval_request_id')->nullable()->index();
                $table->string('status', 30)->default('DRAFT')->index();
                $table->string('salesperson_name', 120)->nullable();
                $table->text('special_notes')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'order_number'], 'uq_sales_order_company_no');
                $table->index(['company_id', 'status'], 'idx_sales_orders_co_status');
                $table->index(['company_id', 'customer_id'], 'idx_sales_orders_co_cust');
            });
        }

        if (!Schema::hasTable('sales_order_lines')) {
            Schema::create('sales_order_lines', function (Blueprint $table) {
                $table->uuid('sales_order_line_id')->primary();
                $table->uuid('sales_order_id')->index();
                $table->uuid('quote_line_id')->nullable();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->unsignedBigInteger('inventory_item_id')->nullable()->index();
                $table->string('sku', 100)->nullable();
                $table->string('item_name', 255);
                $table->text('description')->nullable();
                $table->string('uom', 30)->default('PCS');
                $table->decimal('uom_conversion_factor', 18, 6)->default(1.000000);
                $table->decimal('qty_ordered', 18, 4)->default(1.0000);
                $table->decimal('qty_reserved', 18, 4)->default(0.0000);
                $table->decimal('qty_delivered', 18, 4)->default(0.0000);
                $table->decimal('qty_invoiced', 18, 4)->default(0.0000);
                $table->decimal('unit_cost_snapshot', 18, 4)->default(0.0000);
                $table->decimal('unit_price', 18, 4)->default(0.0000);
                $table->decimal('discount_pct', 8, 4)->default(0.0000);
                $table->decimal('discount_amount', 18, 4)->default(0.0000);
                $table->string('tax_code', 30)->default('VAT12_OUT');
                $table->decimal('tax_rate_pct', 8, 4)->default(12.0000);
                $table->decimal('tax_amount', 18, 4)->default(0.0000);
                $table->decimal('line_subtotal', 18, 4)->default(0.0000);
                $table->decimal('line_total', 18, 4)->default(0.0000);
                $table->text('line_notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sales_order_reservations')) {
            Schema::create('sales_order_reservations', function (Blueprint $table) {
                $table->uuid('reservation_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('branch_id', 50)->default('BR-01')->index();
                $table->uuid('sales_order_id')->index();
                $table->uuid('sales_order_line_id')->index();
                $table->unsignedBigInteger('inventory_item_id')->nullable()->index();
                $table->string('sku', 100)->index();
                $table->string('warehouse_name', 255)->nullable();
                $table->decimal('reserved_qty', 18, 4)->default(0.0000);
                $table->decimal('fulfilled_qty', 18, 4)->default(0.0000);
                $table->string('status', 20)->default('ACTIVE')->index();
                $table->timestamps();

                $table->index(['sales_order_id', 'status'], 'idx_sales_res_order_status');
            });
        }

        if (Schema::hasTable('acct_customers')) {
            Schema::table('acct_customers', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_customers', 'customer_tier')) {
                    $table->string('customer_tier', 30)->default('STANDARD');
                }
                if (!Schema::hasColumn('acct_customers', 'credit_hold')) {
                    $table->boolean('credit_hold')->default(false);
                }
                if (!Schema::hasColumn('acct_customers', 'credit_grace_days')) {
                    $table->unsignedSmallInteger('credit_grace_days')->default(15);
                }
                if (!Schema::hasColumn('acct_customers', 'default_tax_code')) {
                    $table->string('default_tax_code', 30)->default('VAT12_OUT');
                }
                if (!Schema::hasColumn('acct_customers', 'shipping_address')) {
                    $table->text('shipping_address')->nullable();
                }
                if (!Schema::hasColumn('acct_customers', 'sales_rep_name')) {
                    $table->string('sales_rep_name', 120)->nullable();
                }
                if (!Schema::hasColumn('acct_customers', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('stock_out_header')) {
            Schema::table('stock_out_header', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_out_header', 'sales_order_id')) {
                    $table->string('sales_order_id', 64)->nullable()->index();
                }
                if (!Schema::hasColumn('stock_out_header', 'sales_order_number')) {
                    $table->string('sales_order_number', 50)->nullable()->index();
                }
                if (!Schema::hasColumn('stock_out_header', 'customer_id')) {
                    $table->string('customer_id', 64)->nullable()->index();
                }
                if (!Schema::hasColumn('stock_out_header', 'customer_name')) {
                    $table->string('customer_name', 255)->nullable();
                }
                if (!Schema::hasColumn('stock_out_header', 'cogs_journal_id')) {
                    $table->string('cogs_journal_id', 64)->nullable();
                }
            });
        }

        if (Schema::hasTable('stock_out_lines')) {
            Schema::table('stock_out_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_out_lines', 'sales_order_line_id')) {
                    $table->string('sales_order_line_id', 64)->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('acct_sales_invoices')) {
            Schema::table('acct_sales_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_sales_invoices', 'sales_order_id')) {
                    $table->string('sales_order_id', 64)->nullable()->index();
                }
                if (!Schema::hasColumn('acct_sales_invoices', 'sales_order_number')) {
                    $table->string('sales_order_number', 50)->nullable()->index();
                }
                if (!Schema::hasColumn('acct_sales_invoices', 'stock_out_order_ref_id')) {
                    $table->unsignedBigInteger('stock_out_order_ref_id')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('acct_sales_invoice_lines')) {
            Schema::table('acct_sales_invoice_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_sales_invoice_lines', 'sales_order_line_id')) {
                    $table->string('sales_order_line_id', 64)->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        $dropIndexedColumns = function (string $table, array $cols) {
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

        $dropIndexedColumns('acct_sales_invoice_lines', ['sales_order_line_id']);
        $dropIndexedColumns('acct_sales_invoices', ['sales_order_id', 'sales_order_number', 'stock_out_order_ref_id']);
        $dropIndexedColumns('stock_out_lines', ['sales_order_line_id']);
        $dropIndexedColumns('stock_out_header', ['sales_order_id', 'sales_order_number', 'customer_id', 'customer_name', 'cogs_journal_id']);
        $dropIndexedColumns('acct_customers', ['customer_tier', 'credit_hold', 'credit_grace_days', 'default_tax_code', 'shipping_address', 'sales_rep_name', 'notes']);

        Schema::dropIfExists('sales_order_reservations');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_quote_lines');
        Schema::dropIfExists('sales_quotes');
    }
};
