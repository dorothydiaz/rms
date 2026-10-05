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
        // 1. Internal Transfers Header Table
        if (!Schema::hasTable('internal_transfers')) {
            Schema::create('internal_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number', 50)->unique();
                $table->string('transfer_type', 50)->default('CUSTOM_PUSH'); // BRANCH_REQUEST, CUSTOM_PUSH
                $table->string('status', 50)->default('PENDING'); // PENDING, APPROVED, IN_TRANSIT, COMPLETED, CANCELLED
                $table->string('priority', 50)->default('NORMAL'); // NORMAL, URGENT, EMERGENCY
                $table->string('source_location', 255)->default('Central Commissary Main Storage');
                $table->foreignId('source_branch_id')->nullable()->constrained('hr_branches')->nullOnDelete();
                $table->string('destination_location', 255);
                $table->foreignId('destination_branch_id')->nullable()->constrained('hr_branches')->nullOnDelete();
                $table->string('requisition_reference', 100)->nullable();
                $table->string('reason_code', 100)->default('Commissary Bulk Batch Push');
                $table->string('requested_by', 255)->nullable();
                $table->string('approved_by', 255)->nullable();
                $table->string('dispatched_by', 255)->nullable();
                $table->string('received_by', 255)->nullable();
                $table->string('carrier_name', 255)->nullable();
                $table->string('driver_plate', 100)->nullable();
                $table->string('waybill_number', 100)->nullable();
                $table->dateTime('required_at')->nullable();
                $table->dateTime('dispatched_at')->nullable();
                $table->dateTime('received_at')->nullable();
                $table->integer('total_items_count')->default(0);
                $table->decimal('total_requested_qty', 12, 2)->default(0.00);
                $table->decimal('total_transferred_qty', 12, 2)->default(0.00);
                $table->decimal('total_received_qty', 12, 2)->default(0.00);
                $table->decimal('total_valuation', 15, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Internal Transfer Line Items
        if (!Schema::hasTable('internal_transfer_items')) {
            Schema::create('internal_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('internal_transfer_id')->constrained('internal_transfers')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->string('sku', 100);
                $table->string('item_name', 255);
                $table->string('category', 100)->nullable();
                $table->string('uom', 50)->default('Kg');
                $table->decimal('source_available_stock', 12, 2)->default(0.00);
                $table->decimal('requested_qty', 12, 2)->default(0.00);
                $table->decimal('transferred_qty', 12, 2)->default(0.00);
                $table->decimal('received_qty', 12, 2)->default(0.00);
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
        Schema::dropIfExists('internal_transfer_items');
        Schema::dropIfExists('internal_transfers');
    }
};
