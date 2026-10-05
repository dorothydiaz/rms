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
        // 1. Waste Records Header
        if (!Schema::hasTable('waste_records')) {
            Schema::create('waste_records', function (Blueprint $table) {
                $table->id();
                $table->string('waste_number', 50)->unique();
                $table->string('waste_type', 50)->default('SPOILAGE'); // EXPIRED, SPOILAGE, DAMAGED, DEFECTIVE, PREP_FALLOUT, STORAGE_FAILURE, OTHER
                $table->foreignId('branch_id')->nullable()->constrained('hr_branches')->nullOnDelete();
                $table->string('branch_name', 100)->default('Central Commissary');
                $table->string('storage_location', 100)->default('Main Storage / Dry Warehouse');
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->integer('total_items_count')->default(1);
                $table->string('disposal_method', 100)->default('Discarded / Trashed');
                $table->string('status', 50)->default('APPROVED'); // PENDING_REVIEW, APPROVED, DISPOSED, CANCELLED
                $table->string('reported_by', 255);
                $table->string('approved_by', 255)->nullable();
                $table->date('waste_date');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Waste Record Line Items
        if (!Schema::hasTable('waste_record_items')) {
            Schema::create('waste_record_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('waste_record_id')->constrained('waste_records')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('sku', 100)->index();
                $table->string('item_name', 255);
                $table->string('category', 100)->nullable();
                $table->string('uom', 50)->default('Kg');
                $table->decimal('quantity', 12, 2)->default(1.00);
                $table->decimal('unit_cost', 15, 2)->default(0.00);
                $table->decimal('total_cost', 15, 2)->default(0.00);
                $table->string('reason_code', 100)->default('Mold / Spoilage');
                $table->string('batch_lot_no', 100)->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('action_taken', 100)->default('Disposed');
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
        Schema::dropIfExists('waste_record_items');
        Schema::dropIfExists('waste_records');
    }
};
