<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            HrDatabaseSeeder::class,
            HolidaysAndPremiumPaySeeder::class,
            UomCatalogSeeder::class,
            InventoryAndProcurementSeeder::class,
            BillOfMaterialsSeeder::class,
            WasteAndExpirySeeder::class,
        ]);
    }
}
