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
        // 1. Stock Out / Usage Orders Header
        if (!Schema::hasTable('stock_out_orders')) {
            Schema::create('stock_out_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number', 50)->unique();
                $table->string('order_type', 50)->default('KITCHEN_USAGE'); // KITCHEN_USAGE, BRANCH_TRANSFER, VENDOR_RETURN, SPOILAGE_DISPOSAL, STAFF_MEALS, SAMPLE_TASTING
                $table->string('status', 50)->default('DRAFT'); // DRAFT, PICKING, PACKED, SHIPPED, CANCELLED
                $table->string('priority', 50)->default('NORMAL'); // NORMAL, URGENT, EMERGENCY
                $table->string('destination_type', 50)->default('INTERNAL_KITCHEN'); // INTERNAL_KITCHEN, BRANCH, VENDOR, DISPOSAL
                $table->string('destination_location', 255)->default('Main Kitchen Line');
                $table->foreignId('vendor_id')->nullable()->constrained('procurement_vendors')->nullOnDelete();
                $table->string('vendor_name', 255)->nullable();
                $table->string('requested_by', 255)->default('Kitchen Operations');
                $table->string('department', 100)->default('Kitchen Operations');
                $table->string('approved_by', 255)->nullable();
                $table->string('picker_name', 255)->nullable();
                $table->string('carrier_name', 255)->nullable();
                $table->string('tracking_waybill', 100)->nullable();
                $table->string('reference_no', 100)->nullable();
                $table->dateTime('required_at')->nullable();
                $table->dateTime('dispatched_at')->nullable();
                $table->integer('total_items_count')->default(0);
                $table->decimal('total_requested_qty', 12, 2)->default(0.00);
                $table->decimal('total_packed_qty', 12, 2)->default(0.00);
                $table->decimal('total_cost_value', 15, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Stock Out Line Items
        if (!Schema::hasTable('stock_out_items')) {
            Schema::create('stock_out_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_out_order_id')->constrained('stock_out_orders')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('sku', 100);
                $table->string('item_name', 255);
                $table->string('category', 100)->nullable();
                $table->string('uom', 50)->default('Kg');
                $table->decimal('available_stock', 12, 2)->default(0.00);
                $table->decimal('requested_qty', 12, 2)->default(1.00);
                $table->decimal('packed_qty', 12, 2)->default(0.00);
                $table->decimal('unit_cost', 15, 2)->default(0.00);
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->string('batch_lot_no', 100)->nullable();
                $table->string('storage_location', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
        Schema::dropIfExists('stock_out_orders');
    }
};
