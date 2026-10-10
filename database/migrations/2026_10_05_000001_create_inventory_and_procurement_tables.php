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
        // 1. Procurement Vendors Masterlist
        if (!Schema::hasTable('procurement_vendors')) {
            Schema::create('procurement_vendors', function (Blueprint $table) {
                $table->id();
                $table->string('vendor_code', 50)->unique();
                $table->string('legal_name', 255);
                $table->string('trade_name', 255)->nullable();
                $table->string('category', 100)->default('General Supplier');
                $table->string('contact_person', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('phone', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('tax_id', 100)->nullable();
                $table->string('payment_terms', 100)->default('Net 30 Days');
                $table->string('sourcing_channel', 50)->default('commercial'); // commercial, wet_market, ad_hoc
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Inventory Product Categories
        if (!Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('slug', 100)->unique();
                $table->string('code', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('badge_color', 50)->default('purple');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Inventory Items / Product Master
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->string('sku', 100)->unique();
                $table->string('barcode', 100)->nullable()->index();
                $table->string('name', 255);
                $table->string('category', 100)->default('Raw Ingredients');
                $table->text('description')->nullable();
                $table->string('uom', 50)->default('Kg'); // Unit of Measure
                $table->decimal('cost_price', 15, 2)->default(0.00);
                $table->decimal('selling_price', 15, 2)->default(0.00);
                $table->decimal('current_stock', 15, 2)->default(0.00);
                $table->decimal('min_stock', 15, 2)->default(0.00);
                $table->decimal('max_stock', 15, 2)->default(0.00);
                $table->string('storage_location', 100)->default('Main Storage / Dry Warehouse');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Purchase Orders
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('po_number', 50)->unique();
                $table->string('po_type', 50)->default('vendor'); // vendor, wet_market, ad_hoc
                $table->string('rfq_reference', 100)->nullable();
                $table->string('vendor_id', 100)->nullable();
                $table->string('vendor_name', 255);
                $table->string('vendor_trade_name', 255)->nullable();
                $table->string('vendor_contact_person', 255)->nullable();
                $table->string('vendor_phone', 100)->nullable();
                $table->string('vendor_email', 255)->nullable();
                $table->text('vendor_address')->nullable();
                $table->date('order_date')->nullable();
                $table->date('expected_delivery')->nullable();
                $table->string('delivery_location', 255)->default('Central Commissary - Receiving Dock 1');
                $table->text('special_notes')->nullable();
                $table->string('payment_status', 100)->default('Unpaid / Credit');
                $table->string('payment_method', 100)->default('Trade Credit (Net 30/15)');
                $table->decimal('amount_paid', 15, 2)->default(0.00);
                $table->decimal('balance_due', 15, 2)->default(0.00);
                $table->string('payment_reference', 100)->nullable();
                $table->date('payment_date')->nullable();
                $table->string('fund_source', 100)->default('Operating Account');
                $table->text('payment_remarks')->nullable();
                $table->text('approval_notes')->nullable();
                $table->string('approved_by', 255)->nullable();
                $table->string('status', 50)->default('Approved / Issued'); // Draft PO, Approved / Issued, Partially Received, Fully Received, Cancelled, Short-Closed
                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->decimal('gross_total', 15, 2)->default(0.00);
                $table->string('created_by_user', 255)->nullable();
                $table->timestamps();
            });
        }

        // 5. Purchase Order Line Items
        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->string('sku', 100)->nullable();
                $table->string('item_name', 255);
                $table->text('specs')->nullable();
                $table->string('category', 100)->nullable();
                $table->string('uom', 50)->default('Unit');
                $table->decimal('quantity', 12, 2)->default(1.00);
                $table->decimal('unit_price', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->decimal('received_quantity', 12, 2)->default(0.00);
                $table->string('status', 50)->default('PENDING'); // PENDING, PARTIAL, FULFILLED
                $table->timestamps();
            });
        }

        // 6. Goods Receipts (Stock In Inbound Header)
        if (!Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('grn_number', 50)->unique();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->string('po_number', 50)->nullable()->index();
                $table->string('vendor_id', 100)->nullable();
                $table->string('vendor_name', 255);
                $table->string('vendor_trade_name', 255)->nullable();
                $table->string('delivery_slip_no', 100)->nullable();
                $table->string('carrier_name', 255)->nullable();
                $table->string('reason_code', 100)->default('Standard PO Delivery');
                $table->string('delivery_location', 255)->default('Central Commissary - Receiving Dock 1');
                $table->string('settlement_mode', 100)->default('PURCHASE_ORDER');
                $table->string('payment_method', 100)->default('Trade Credit (Net 30/15)');
                $table->string('payment_ref', 100)->nullable();
                $table->decimal('freight_cost', 15, 2)->default(0.00);
                $table->decimal('customs_cost', 15, 2)->default(0.00);
                $table->decimal('handling_cost', 15, 2)->default(0.00);
                $table->decimal('total_landed_costs', 15, 2)->default(0.00);
                $table->decimal('items_subtotal', 15, 2)->default(0.00);
                $table->decimal('gross_total', 15, 2)->default(0.00);
                $table->dateTime('received_at');
                $table->string('received_by', 255);
                $table->string('status', 50)->default('COMPLETED');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 7. Goods Receipt Line Items
        if (!Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
                $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
                $table->string('sku', 100);
                $table->string('item_name', 255);
                $table->string('uom', 50)->default('Kg');
                $table->string('purchasing_uom', 50)->default('Kg');
                $table->decimal('uom_multiplier', 8, 2)->default(1.00);
                $table->decimal('received_qty', 12, 2)->default(0.00);
                $table->decimal('base_qty', 12, 2)->default(0.00); // received_qty * uom_multiplier
                $table->decimal('unit_cost', 15, 2)->default(0.00);
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->string('lot_number', 100)->nullable();
                $table->date('expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 8. Immutable Master Stock Ledger (SYS_LEDGER architectural requirement)
        if (!Schema::hasTable('stock_ledger')) {
            Schema::create('stock_ledger', function (Blueprint $table) {
                $table->id();
                $table->uuid('transaction_uuid')->unique();
                $table->string('sku', 100)->index();
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('item_name', 255);
                $table->string('transaction_type', 50); // PURCHASE_RECEIPT, DIRECT_RECEIVING, STOCK_IN, STOCK_OUT, ADJUSTMENT, WASTE
                $table->string('reference_type', 100)->nullable(); // goods_receipts, purchase_orders, adjustments
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_no', 100)->index();
                $table->decimal('before_quantity', 15, 2);
                $table->decimal('quantity_change', 15, 2);
                $table->decimal('after_quantity', 15, 2);
                $table->decimal('unit_cost', 15, 2)->default(0.00);
                $table->decimal('total_value', 15, 2)->default(0.00);
                $table->string('batch_lot_no', 100)->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('storage_location', 100)->default('Main Storage / Dry Warehouse');
                $table->string('performed_by', 255);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['sku', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('procurement_vendors');
    }
};
