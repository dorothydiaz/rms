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
        // 1. Bill of Materials (BOM) / Recipe Header Table
        if (!Schema::hasTable('bill_of_materials')) {
            Schema::create('bill_of_materials', function (Blueprint $table) {
                $table->id();
                $table->string('bom_code', 50)->unique();
                $table->foreignId('finished_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('sku', 100)->index();
                $table->string('item_name', 255);
                $table->string('category', 100)->default('House Recipe');
                $table->string('uom', 50)->default('Portion');
                $table->decimal('yield_quantity', 12, 2)->default(1.00); // Standard output yield per batch
                $table->decimal('labor_cost', 15, 2)->default(0.00);
                $table->decimal('overhead_cost', 15, 2)->default(0.00);
                $table->decimal('total_raw_cost', 15, 2)->default(0.00);
                $table->decimal('total_cost', 15, 2)->default(0.00); // raw + labor + overhead
                $table->decimal('unit_cost', 15, 2)->default(0.00); // total_cost / yield_quantity
                $table->decimal('selling_price', 15, 2)->default(0.00);
                $table->decimal('margin_percentage', 8, 2)->default(0.00);
                $table->integer('prep_time_minutes')->default(30);
                $table->integer('shelf_life_days')->default(7);
                $table->text('instructions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('created_by', 255)->nullable();
                $table->timestamps();
            });
        }

        // 2. Bill of Materials Line Items (Raw Ingredients & Packaging)
        if (!Schema::hasTable('bill_of_materials_items')) {
            Schema::create('bill_of_materials_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_of_materials_id')->constrained('bill_of_materials')->cascadeOnDelete();
                $table->foreignId('raw_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('sku', 100);
                $table->string('raw_item_name', 255);
                $table->string('uom', 50)->default('Kg');
                $table->decimal('quantity', 12, 4)->default(1.0000); // Required for standard batch yield
                $table->decimal('unit_cost', 15, 2)->default(0.00);
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->decimal('waste_percentage', 6, 2)->default(0.00); // Trimming / shrinkage loss allowance
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Production Orders / Batch Runs Header Table
        if (!Schema::hasTable('production_orders')) {
            Schema::create('production_orders', function (Blueprint $table) {
                $table->id();
                $table->string('production_number', 50)->unique();
                $table->string('batch_lot_number', 100)->unique();
                $table->foreignId('finished_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('bill_of_materials_id')->constrained('bill_of_materials')->cascadeOnDelete();
                $table->string('sku', 100);
                $table->string('item_name', 255);
                $table->string('uom', 50)->default('Portion');
                $table->string('status', 50)->default('COMPLETED'); // DRAFT, PLANNED, IN_PROGRESS, COMPLETED, CANCELLED
                $table->string('kitchen_station', 100)->default('Central Commissary - Prep Kitchen');
                $table->decimal('planned_quantity', 12, 2)->default(1.00);
                $table->decimal('actual_quantity', 12, 2)->default(1.00);
                $table->decimal('yield_efficiency_percent', 8, 2)->default(100.00);
                $table->decimal('total_raw_cost', 15, 2)->default(0.00);
                $table->decimal('labor_cost', 15, 2)->default(0.00);
                $table->decimal('overhead_cost', 15, 2)->default(0.00);
                $table->decimal('total_production_cost', 15, 2)->default(0.00);
                $table->decimal('unit_production_cost', 15, 2)->default(0.00);
                $table->date('production_date');
                $table->date('expiry_date')->nullable();
                $table->string('produced_by', 255);
                $table->string('verified_by', 255)->nullable();
                $table->boolean('proceed_with_shortage')->default(false);
                $table->text('shortage_notes')->nullable();
                $table->text('quality_notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. Production Order Consumed Items
        if (!Schema::hasTable('production_order_items')) {
            Schema::create('production_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('raw_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('sku', 100);
                $table->string('item_name', 255);
                $table->string('uom', 50)->default('Kg');
                $table->decimal('bom_standard_qty', 12, 4)->default(1.0000);
                $table->decimal('required_qty', 12, 4)->default(1.0000);
                $table->decimal('actual_consumed_qty', 12, 4)->default(1.0000);
                $table->decimal('available_stock_before', 12, 4)->default(0.0000);
                $table->boolean('is_shortage')->default(false);
                $table->decimal('unit_cost', 15, 2)->default(0.00);
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bill_of_materials_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
