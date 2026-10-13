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
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'company_branch_code')) {
                $table->string('company_branch_code', 20)->nullable()->default('00000');
            }
            if (!Schema::hasColumn('companies', 'company_email_domain')) {
                $table->string('company_email_domain', 150)->nullable();
            }
            if (!Schema::hasColumn('companies', 'company_brand_color')) {
                $table->string('company_brand_color', 30)->nullable()->default('#ef4444');
            }
            if (!Schema::hasColumn('companies', 'company_external_code')) {
                $table->string('company_external_code', 100)->nullable();
            }
            if (!Schema::hasColumn('companies', 'company_is_vat_registered')) {
                $table->boolean('company_is_vat_registered')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $colsToDrop = [];
            foreach (['company_branch_code', 'company_email_domain', 'company_brand_color', 'company_external_code', 'company_is_vat_registered'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }
};
