<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'employment_source')) {
                $table->string('employment_source', 50)->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('employees', 'company_name')) {
                $table->string('company_name', 150)->nullable()->after('employment_source');
            }
            if (!Schema::hasColumn('employees', 'agency_name')) {
                $table->string('agency_name', 150)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('employees', 'company_agency_name')) {
                $table->string('company_agency_name', 150)->nullable()->after('agency_name');
            }
        });

        // Sync existing data from company_agency_name if present
        if (Schema::hasColumn('employees', 'company_agency_name')) {
            DB::statement("UPDATE employees SET company_name = company_agency_name WHERE employment_source = 'Company' AND (company_name IS NULL OR company_name = '')");
            DB::statement("UPDATE employees SET agency_name = company_agency_name WHERE employment_source = 'Agency' AND (agency_name IS NULL OR agency_name = '')");
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'company_agency_name')) {
                $table->dropColumn('company_agency_name');
            }
            if (Schema::hasColumn('employees', 'agency_name')) {
                $table->dropColumn('agency_name');
            }
            if (Schema::hasColumn('employees', 'company_name')) {
                $table->dropColumn('company_name');
            }
            if (Schema::hasColumn('employees', 'employment_source')) {
                $table->dropColumn('employment_source');
            }
        });
    }
};
