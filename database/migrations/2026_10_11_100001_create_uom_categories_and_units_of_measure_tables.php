<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. UOM Categories Table
        if (!Schema::hasTable('uom_categories')) {
            Schema::create('uom_categories', function (Blueprint $table) {
                $table->uuid('category_id')->primary();
                $table->string('category_code', 50)->unique();
                $table->string('category_name', 100);
                $table->text('category_description')->nullable();
                $table->string('base_uom_code', 20)->nullable();
                $table->boolean('category_is_active')->default(true);
                $table->timestamp('category_created_at')->nullable();
                $table->uuid('category_created_by')->nullable();
                $table->timestamp('category_updated_at')->nullable();
                $table->uuid('category_updated_by')->nullable();
            });
        }

        // 2. Units of Measure Table
        if (!Schema::hasTable('units_of_measure')) {
            Schema::create('units_of_measure', function (Blueprint $table) {
                $table->uuid('uom_id')->primary();
                $table->string('uom_code', 20)->unique();
                $table->string('uom_name', 100);
                $table->string('uom_category', 50)->index();
                $table->string('uom_type', 20)->default('REFERENCE'); // REFERENCE, GREATER, SMALLER
                $table->decimal('base_ratio', 18, 6)->default(1.000000);
                $table->boolean('is_base_unit')->default(false);
                $table->decimal('rounding_precision', 10, 4)->default(0.0100);
                $table->boolean('uom_is_active')->default(true);
                $table->timestamp('uom_created_at')->nullable();
                $table->uuid('uom_created_by')->nullable();
                $table->timestamp('uom_updated_at')->nullable();
                $table->uuid('uom_updated_by')->nullable();
            });
        }

        // Seed comprehensive industrial enterprise UoMs catalog from UomCatalogSeeder
        (new \Database\Seeders\UomCatalogSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('uom_categories');
    }
};
