<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rfq_header')) {
            Schema::table('rfq_header', function (Blueprint $table) {
                if (!Schema::hasColumn('rfq_header', 'rfq_group_id')) {
                    $table->uuid('rfq_group_id')->nullable()->index()->after('rfq_number');
                }
                if (!Schema::hasColumn('rfq_header', 'parent_rfq_id')) {
                    $table->uuid('parent_rfq_id')->nullable()->index()->after('rfq_group_id');
                }
                if (!Schema::hasColumn('rfq_header', 'is_alternative')) {
                    $table->boolean('is_alternative')->default(false)->after('parent_rfq_id');
                }
                if (!Schema::hasColumn('rfq_header', 'alternative_label')) {
                    $table->string('alternative_label', 10)->nullable()->after('is_alternative'); // 'A', 'B', 'C'
                }
            });
        }

        if (Schema::hasTable('rfq_lines')) {
            Schema::table('rfq_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('rfq_lines', 'awarded_purchase_order_id')) {
                    $table->unsignedBigInteger('awarded_purchase_order_id')->nullable()->index()->after('is_awarded');
                }
                if (!Schema::hasColumn('rfq_lines', 'award_notes')) {
                    $table->string('award_notes', 255)->nullable()->after('awarded_purchase_order_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rfq_lines')) {
            Schema::table('rfq_lines', function (Blueprint $table) {
                if (Schema::hasColumn('rfq_lines', 'awarded_purchase_order_id')) {
                    $table->dropColumn('awarded_purchase_order_id');
                }
                if (Schema::hasColumn('rfq_lines', 'award_notes')) {
                    $table->dropColumn('award_notes');
                }
            });
        }

        if (Schema::hasTable('rfq_header')) {
            Schema::table('rfq_header', function (Blueprint $table) {
                if (Schema::hasColumn('rfq_header', 'rfq_group_id')) {
                    $table->dropColumn('rfq_group_id');
                }
                if (Schema::hasColumn('rfq_header', 'parent_rfq_id')) {
                    $table->dropColumn('parent_rfq_id');
                }
                if (Schema::hasColumn('rfq_header', 'is_alternative')) {
                    $table->dropColumn('is_alternative');
                }
                if (Schema::hasColumn('rfq_header', 'alternative_label')) {
                    $table->dropColumn('alternative_label');
                }
            });
        }
    }
};
