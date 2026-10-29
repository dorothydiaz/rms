<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add missing performance composite indexes.
     */
    public function up(): void
    {
        // 1. Attendance Records Performance Indexes
        if (Schema::hasTable('hr_attendance_records')) {
            Schema::table('hr_attendance_records', function (Blueprint $table) {
                // High-cardinality date and branch filters
                $table->index(['attendance_record_date', 'attendance_record_branch_id'], 'idx_att_date_branch');
                $table->index(['attendance_record_date', 'attendance_record_status'], 'idx_att_date_status');
                $table->index('attendance_record_date', 'idx_att_date');
            });
        }

        // 2. Purchase Orders Performance Indexes
        if (Schema::hasTable('purchase_order_header')) {
            Schema::table('purchase_order_header', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_po_status_created');
                $table->index(['vendor_id', 'created_at'], 'idx_po_vendor_created');
            });
        }

        // 3. Inventory Ledger Performance Indexes
        if (Schema::hasTable('inventory_ledger')) {
            Schema::table('inventory_ledger', function (Blueprint $table) {
                $table->index(['transaction_type', 'created_at'], 'idx_ledger_type_created');
            });
        }

        // 4. RFQ Header Performance Indexes
        if (Schema::hasTable('rfq_header')) {
            Schema::table('rfq_header', function (Blueprint $table) {
                $table->index(['rfq_status', 'rfq_created_at'], 'idx_rfq_status_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hr_attendance_records')) {
            Schema::table('hr_attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_att_date_branch');
                $table->dropIndex('idx_att_date_status');
                $table->dropIndex('idx_att_date');
            });
        }

        if (Schema::hasTable('purchase_order_header')) {
            Schema::table('purchase_order_header', function (Blueprint $table) {
                $table->dropIndex('idx_po_status_created');
                $table->dropIndex('idx_po_vendor_created');
            });
        }

        if (Schema::hasTable('inventory_ledger')) {
            Schema::table('inventory_ledger', function (Blueprint $table) {
                $table->dropIndex('idx_ledger_type_created');
            });
        }

        if (Schema::hasTable('rfq_header')) {
            Schema::table('rfq_header', function (Blueprint $table) {
                $table->dropIndex('idx_rfq_status_created');
            });
        }
    }
};
