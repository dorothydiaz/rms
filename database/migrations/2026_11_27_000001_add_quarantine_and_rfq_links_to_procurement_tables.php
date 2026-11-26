<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_in_lines')) {
            Schema::table('stock_in_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_in_lines', 'accepted_qty')) {
                    $table->decimal('accepted_qty', 14, 4)->nullable()->after('received_qty');
                }
                if (!Schema::hasColumn('stock_in_lines', 'rejected_qty')) {
                    $table->decimal('rejected_qty', 14, 4)->default(0)->after('accepted_qty');
                }
                if (!Schema::hasColumn('stock_in_lines', 'rejection_reason')) {
                    $table->string('rejection_reason', 255)->nullable()->after('rejected_qty');
                }
                if (!Schema::hasColumn('stock_in_lines', 'quarantine_location')) {
                    $table->string('quarantine_location', 255)->nullable()->after('rejection_reason');
                }
            });
        }

        if (Schema::hasTable('purchase_order_lines')) {
            Schema::table('purchase_order_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_lines', 'rfq_line_id')) {
                    $table->string('rfq_line_id', 64)->nullable()->index()->after('purchase_order_id');
                }
                if (!Schema::hasColumn('purchase_order_lines', 'rejected_quantity')) {
                    $table->decimal('rejected_quantity', 14, 4)->default(0)->after('received_quantity');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_in_lines')) {
            Schema::table('stock_in_lines', function (Blueprint $table) {
                foreach (['quarantine_location', 'rejection_reason', 'rejected_qty', 'accepted_qty'] as $col) {
                    if (Schema::hasColumn('stock_in_lines', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_order_lines')) {
            Schema::table('purchase_order_lines', function (Blueprint $table) {
                foreach (['rejected_quantity', 'rfq_line_id'] as $col) {
                    if (Schema::hasColumn('purchase_order_lines', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

