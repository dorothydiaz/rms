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
            if (!Schema::hasColumn('companies', 'type')) {
                $table->string('type', 30)->default('Company')->after('name');
            }
            if (!Schema::hasColumn('companies', 'contact_person')) {
                $table->string('contact_person', 100)->nullable()->after('tin');
            }
            if (!Schema::hasColumn('companies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('logo');
            }
            if (!Schema::hasColumn('companies', 'notes')) {
                $table->text('notes')->nullable()->after('is_active');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('branch_id')->constrained('companies')->nullOnDelete();
            }
        });

        // Ensure default company exists and is typed 'Company'
        \Illuminate\Support\Facades\DB::table('companies')->whereNull('type')->orWhere('type', '')->update(['type' => 'Company']);

        // Insert default agencies if not yet present
        $agencies = [
            [
                'name' => 'ABC Manpower & Staffing Services',
                'code' => 'ABC-STAFF',
                'type' => 'Agency',
                'contact_person' => 'Ms. Jessica Cruz - Account Head',
                'tin' => '987-654-321-000',
                'email' => 'deployments@abcmanpower.ph',
                'phone' => '+63 2 8555 1234',
                'address' => 'Unit 402, Solid Gold Bldg., Buendia Ave., Makati City',
                'is_active' => true,
                'notes' => 'Contracted manpower partner for kitchen and front-of-house utility personnel.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Prime Hospitality Care Agency',
                'code' => 'PRIME-CARE',
                'type' => 'Agency',
                'contact_person' => 'Mr. Arthur Gonzales',
                'tin' => '456-789-123-000',
                'email' => 'partnerships@primecare.ph',
                'phone' => '+63 2 8777 9876',
                'address' => '12th Floor, Cyberpark Tower 1, Araneta City, Quezon City',
                'is_active' => true,
                'notes' => 'Certified staffing agency providing licensed baristas, cashiers, and banquet servers.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($agencies as $agencyData) {
            $exists = \Illuminate\Support\Facades\DB::table('companies')->where('name', $agencyData['name'])->exists();
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('companies')->insert($agencyData);
            }
        }

        // Link existing employees with company_id matching either company_name or agency_name
        $allCompanies = \Illuminate\Support\Facades\DB::table('companies')->get();
        foreach ($allCompanies as $comp) {
            \Illuminate\Support\Facades\DB::table('employees')
                ->where('company_name', $comp->name)
                ->orWhere('agency_name', $comp->name)
                ->orWhere('company_agency_name', $comp->name)
                ->update(['company_id' => $comp->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            $cols = ['type', 'contact_person', 'is_active', 'notes'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
