<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_promo_audit_logs')) {
            Schema::create('sales_promo_audit_logs', function (Blueprint $table) {
                $table->uuid('audit_id')->primary();
                $table->string('company_id', 50)->default('1')->index();
                $table->string('promo_module', 20)->index(); // DISCOUNT, VOUCHER, BUNDLE
                $table->uuid('promo_id')->index();
                $table->string('promo_code', 60)->index();
                $table->string('promo_name', 200);
                $table->string('action', 40)->index(); // CREATED, UPDATED, ACTIVATED, DISABLED, APPLIED_TO_ORDER, REDEEMED, RELEASED
                $table->text('summary');
                $table->json('changes_json')->nullable();
                $table->string('acted_by', 100);
                $table->timestamp('acted_at')->index();
                $table->timestamps();

                $table->index(['company_id', 'promo_module', 'acted_at'], 'idx_promo_audit_co_mod_at');
            });
        }

        foreach (['sales_discounts', 'sales_vouchers', 'sales_bundles'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'duration_mode')) {
                    $table->string('duration_mode', 20)->default('ALWAYS');
                }
                if (!Schema::hasColumn($tableName, 'updated_by')) {
                    $table->string('updated_by', 100)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['sales_discounts', 'sales_vouchers', 'sales_bundles'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            foreach (['duration_mode', 'updated_by'] as $col) {
                if (Schema::hasColumn($tableName, $col)) {
                    Schema::table($tableName, function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        }

        Schema::dropIfExists('sales_promo_audit_logs');
    }
};
