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
        if (Schema::hasTable('rfq_header')) {
            Schema::table('rfq_header', function (Blueprint $table) {
                if (!Schema::hasColumn('rfq_header', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('rfq_notes');
                }
                if (!Schema::hasColumn('rfq_header', 'revision_notes')) {
                    $table->text('revision_notes')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn('rfq_header', 'purchase_order_id')) {
                    $table->uuid('purchase_order_id')->nullable()->index()->after('award_approval_request_id');
                }
            });
        }

        if (Schema::hasTable('rfq_lines')) {
            Schema::table('rfq_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('rfq_lines', 'quoted_quantity')) {
                    $table->decimal('quoted_quantity', 18, 4)->nullable()->after('requested_quantity');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rfq_lines')) {
            Schema::table('rfq_lines', function (Blueprint $table) {
                if (Schema::hasColumn('rfq_lines', 'quoted_quantity')) {
                    $table->dropColumn('quoted_quantity');
                }
            });
        }

        if (Schema::hasTable('rfq_header')) {
            Schema::table('rfq_header', function (Blueprint $table) {
                if (Schema::hasColumn('rfq_header', 'rejection_reason')) {
                    $table->dropColumn('rejection_reason');
                }
                if (Schema::hasColumn('rfq_header', 'revision_notes')) {
                    $table->dropColumn('revision_notes');
                }
                if (Schema::hasColumn('rfq_header', 'purchase_order_id')) {
                    $table->dropColumn('purchase_order_id');
                }
            });
        }
    }
};
