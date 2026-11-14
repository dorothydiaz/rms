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
        if (!Schema::hasTable('reordering_rules')) {
            Schema::create('reordering_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rule_code', 50)->unique();
                $table->unsignedBigInteger('branch_id')->default(1)->index();
                $table->string('branch_name', 150)->default('Makati Flagship Branch');
                $table->unsignedBigInteger('product_id')->index();
                $table->string('product_sku', 100)->index();
                $table->string('product_name', 255);
                $table->string('category', 100)->default('Raw Ingredients');
                $table->string('uom', 50)->default('Kg');
                
                // Strategy: 'min_max', 'commissary_push', 'jit_back_to_back'
                $table->string('strategy', 50)->default('min_max');
                
                // Inventory Stock Parameters
                $table->decimal('min_stock', 15, 2)->default(0.00);
                $table->decimal('max_stock', 15, 2)->default(0.00);
                $table->decimal('reorder_point', 15, 2)->default(0.00);
                $table->decimal('moq', 15, 2)->default(1.00);
                $table->decimal('safety_buffer', 15, 2)->default(0.00);
                $table->integer('lead_time_days')->default(2);
                
                // Route & Fulfillment Action
                $table->string('action_route', 50)->default('purchase_order'); // purchase_order, internal_transfer
                $table->unsignedBigInteger('preferred_vendor_id')->nullable()->index();
                $table->string('preferred_vendor_name', 255)->nullable();
                $table->string('source_warehouse_id', 100)->nullable();
                $table->string('source_warehouse_name', 255)->nullable();
                $table->string('target_warehouse_id', 100)->nullable();
                
                // Automation & Status
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('auto_generate_draft')->default(false);
                $table->timestamp('last_evaluated_at')->nullable();
                $table->timestamp('last_triggered_at')->nullable();
                $table->decimal('last_order_qty', 15, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'product_id']);
                $table->index(['is_active', 'strategy']);
            });

            // Seed initial realistic enterprise rules
            $now = now();
            DB::table('reordering_rules')->insert([
                [
                    'rule_code' => 'RUL-MAK-001',
                    'branch_id' => 1,
                    'branch_name' => 'Makati Flagship Branch',
                    'product_id' => 1,
                    'product_sku' => 'RAW-308',
                    'product_name' => 'US Choice Ribeye Beef Primal',
                    'category' => 'Raw Meat & Poultry',
                    'uom' => 'Kg',
                    'strategy' => 'min_max',
                    'min_stock' => 15.00,
                    'max_stock' => 50.00,
                    'reorder_point' => 15.00,
                    'moq' => 5.00,
                    'safety_buffer' => 3.00,
                    'lead_time_days' => 2,
                    'action_route' => 'purchase_order',
                    'preferred_vendor_id' => 1,
                    'preferred_vendor_name' => 'San Miguel Pure Foods Company Inc.',
                    'source_warehouse_id' => null,
                    'source_warehouse_name' => null,
                    'target_warehouse_id' => 'WH-MAK-MAIN',
                    'is_active' => true,
                    'auto_generate_draft' => true,
                    'last_evaluated_at' => $now,
                    'last_triggered_at' => null,
                    'last_order_qty' => null,
                    'notes' => 'High-velocity beef primal. Keep buffer above 15kg.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rule_code' => 'RUL-MAK-002',
                    'branch_id' => 1,
                    'branch_name' => 'Makati Flagship Branch',
                    'product_id' => 5,
                    'product_sku' => 'RAW-COFFEE-01',
                    'product_name' => 'Espresso Roast Beans (1kg)',
                    'category' => 'Beverage Ingredients',
                    'uom' => 'Kg',
                    'strategy' => 'commissary_push',
                    'min_stock' => 12.00,
                    'max_stock' => 45.00,
                    'reorder_point' => 12.00,
                    'moq' => 6.00,
                    'safety_buffer' => 2.00,
                    'lead_time_days' => 1,
                    'action_route' => 'internal_transfer',
                    'preferred_vendor_id' => null,
                    'preferred_vendor_name' => null,
                    'source_warehouse_id' => 'WH-CENTRAL-COMM',
                    'source_warehouse_name' => 'Central Commissary Taguig Hub',
                    'target_warehouse_id' => 'WH-MAK-BAR',
                    'is_active' => true,
                    'auto_generate_draft' => true,
                    'last_evaluated_at' => $now,
                    'last_triggered_at' => null,
                    'last_order_qty' => null,
                    'notes' => 'Transfer from central roasting facility. Packaged in 6kg master cartons.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rule_code' => 'RUL-BGC-003',
                    'branch_id' => 2,
                    'branch_name' => 'BGC Taguig Branch',
                    'product_id' => 3,
                    'product_sku' => 'RAW-VEG-01',
                    'product_name' => 'Fresh Native Red Onions (Sibuyas Tagalog)',
                    'category' => 'Produce & Vegetables',
                    'uom' => 'Kg',
                    'strategy' => 'min_max',
                    'min_stock' => 20.00,
                    'max_stock' => 80.00,
                    'reorder_point' => 20.00,
                    'moq' => 10.00,
                    'safety_buffer' => 5.00,
                    'lead_time_days' => 1,
                    'action_route' => 'purchase_order',
                    'preferred_vendor_id' => 2,
                    'preferred_vendor_name' => 'Aling Nena Fresh Vegetables & Spices',
                    'source_warehouse_id' => null,
                    'source_warehouse_name' => null,
                    'target_warehouse_id' => 'WH-BGC-PRODUCE',
                    'is_active' => true,
                    'auto_generate_draft' => false,
                    'last_evaluated_at' => $now,
                    'last_triggered_at' => null,
                    'last_order_qty' => null,
                    'notes' => 'Daily produce replenishment. Minimum sack size is 10kg.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rule_code' => 'RUL-BGC-004',
                    'branch_id' => 2,
                    'branch_name' => 'BGC Taguig Branch',
                    'product_id' => 9,
                    'product_sku' => 'PKG-501',
                    'product_name' => 'Kraft Takeout Box (Medium)',
                    'category' => 'Packaging Materials',
                    'uom' => 'Piece',
                    'strategy' => 'min_max',
                    'min_stock' => 150.00,
                    'max_stock' => 600.00,
                    'reorder_point' => 150.00,
                    'moq' => 100.00,
                    'safety_buffer' => 50.00,
                    'lead_time_days' => 3,
                    'action_route' => 'purchase_order',
                    'preferred_vendor_id' => 4,
                    'preferred_vendor_name' => 'EcoPack Solutions & Paper Products Corp',
                    'source_warehouse_id' => null,
                    'source_warehouse_name' => null,
                    'target_warehouse_id' => 'WH-BGC-DRY',
                    'is_active' => true,
                    'auto_generate_draft' => true,
                    'last_evaluated_at' => $now,
                    'last_triggered_at' => null,
                    'last_order_qty' => null,
                    'notes' => 'Sold in bundles of 100 pieces.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rule_code' => 'RUL-QC-005',
                    'branch_id' => 3,
                    'branch_name' => 'Quezon City Branch',
                    'product_id' => 10,
                    'product_sku' => 'SYR-601',
                    'product_name' => 'Caramel Macchiato Artisan Syrup',
                    'category' => 'Beverage Ingredients',
                    'uom' => 'Bottle',
                    'strategy' => 'jit_back_to_back',
                    'min_stock' => 6.00,
                    'max_stock' => 24.00,
                    'reorder_point' => 6.00,
                    'moq' => 6.00,
                    'safety_buffer' => 2.00,
                    'lead_time_days' => 2,
                    'action_route' => 'purchase_order',
                    'preferred_vendor_id' => 3,
                    'preferred_vendor_name' => 'Barista Essentials Distribution Corp',
                    'source_warehouse_id' => null,
                    'source_warehouse_name' => null,
                    'target_warehouse_id' => 'WH-QC-BAR',
                    'is_active' => true,
                    'auto_generate_draft' => false,
                    'last_evaluated_at' => $now,
                    'last_triggered_at' => null,
                    'last_order_qty' => null,
                    'notes' => 'Ordered by case of 6 bottles.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reordering_rules');
    }
};

