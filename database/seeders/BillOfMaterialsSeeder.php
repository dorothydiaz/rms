<?php

namespace Database\Seeders;

use App\Models\Inventory\BillOfMaterials;
use App\Models\Inventory\BillOfMaterialsItem;
use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Seeder;

class BillOfMaterialsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Signature Spanish Latte (BEV-001)
        $latte = InventoryItem::where('sku', 'BEV-001')->first();
        $coffeeBeans = InventoryItem::where('sku', 'RAW-COFFEE-01')->first();
        $syrup = InventoryItem::where('sku', 'SYR-601')->first();
        $cup = InventoryItem::where('sku', 'PKG-501')->first();

        if ($latte && $coffeeBeans && $syrup) {
            $bomLatte = BillOfMaterials::updateOrCreate(
                ['bom_code' => 'BOM-BEV-001'],
                [
                    'finished_item_id' => $latte->id,
                    'sku' => $latte->sku,
                    'item_name' => $latte->name,
                    'category' => 'Beverages',
                    'uom' => $latte->uom,
                    'yield_quantity' => 1.00,
                    'labor_cost' => 15.00,
                    'overhead_cost' => 5.00,
                    'total_raw_cost' => 25.00,
                    'total_cost' => 45.00,
                    'unit_cost' => 45.00,
                    'selling_price' => 150.00,
                    'margin_percentage' => 70.00,
                    'prep_time_minutes' => 5,
                    'shelf_life_days' => 1,
                    'instructions' => 'Grind 20g beans, pull double espresso, steam condensed & fresh milk, assemble in cup.',
                    'is_active' => true,
                    'created_by' => 'Chef Barista Lead',
                ]
            );

            // Raw Ingredients
            BillOfMaterialsItem::updateOrCreate(
                ['bill_of_materials_id' => $bomLatte->id, 'raw_item_id' => $coffeeBeans->id],
                [
                    'sku' => $coffeeBeans->sku,
                    'raw_item_name' => $coffeeBeans->name,
                    'uom' => 'Kg',
                    'quantity' => 0.0200, // 20g
                    'unit_cost' => (float) $coffeeBeans->cost_price,
                    'total_cost' => 0.0200 * (float) $coffeeBeans->cost_price,
                    'waste_percentage' => 2.00,
                    'notes' => 'Fine espresso grind',
                ]
            );

            BillOfMaterialsItem::updateOrCreate(
                ['bill_of_materials_id' => $bomLatte->id, 'raw_item_id' => $syrup->id],
                [
                    'sku' => $syrup->sku,
                    'raw_item_name' => $syrup->name,
                    'uom' => 'Bottle',
                    'quantity' => 0.0300,
                    'unit_cost' => (float) $syrup->cost_price,
                    'total_cost' => 0.0300 * (float) $syrup->cost_price,
                    'waste_percentage' => 0.00,
                    'notes' => 'Artisan syrup pump',
                ]
            );

            if ($cup) {
                BillOfMaterialsItem::updateOrCreate(
                    ['bill_of_materials_id' => $bomLatte->id, 'raw_item_id' => $cup->id],
                    [
                        'sku' => $cup->sku,
                        'raw_item_name' => $cup->name,
                        'uom' => 'Piece',
                        'quantity' => 1.0000,
                        'unit_cost' => (float) $cup->cost_price,
                        'total_cost' => 1.0000 * (float) $cup->cost_price,
                        'waste_percentage' => 1.00,
                        'notes' => 'Takeout / Dine-in cup',
                    ]
                );
            }
        }

        // 2. Truffle Mushroom Pasta (MNC-101)
        $pasta = InventoryItem::where('sku', 'MNC-101')->first();
        $sauce = InventoryItem::where('sku', 'SAUCE-TRF-01')->first();
        $onions = InventoryItem::where('sku', 'RAW-VEG-01')->first();
        $garlic = InventoryItem::where('sku', 'RAW-VEG-02')->first();
        $beef = InventoryItem::where('sku', 'TEST-BEEF-01')->first();

        if ($pasta && $sauce && $garlic) {
            $bomPasta = BillOfMaterials::updateOrCreate(
                ['bom_code' => 'BOM-MNC-101'],
                [
                    'finished_item_id' => $pasta->id,
                    'sku' => $pasta->sku,
                    'item_name' => $pasta->name,
                    'category' => 'Main Course',
                    'uom' => $pasta->uom,
                    'yield_quantity' => 1.00,
                    'labor_cost' => 35.00,
                    'overhead_cost' => 15.00,
                    'total_raw_cost' => 65.00,
                    'total_cost' => 115.00,
                    'unit_cost' => 115.00,
                    'selling_price' => 380.00,
                    'margin_percentage' => 69.74,
                    'prep_time_minutes' => 15,
                    'shelf_life_days' => 1,
                    'instructions' => 'Saute garlic and onions, sear beef bits, toss al dente fettuccine with truffle white sauce.',
                    'is_active' => true,
                    'created_by' => 'Executive Sous Chef',
                ]
            );

            BillOfMaterialsItem::updateOrCreate(
                ['bill_of_materials_id' => $bomPasta->id, 'raw_item_id' => $sauce->id],
                [
                    'sku' => $sauce->sku,
                    'raw_item_name' => $sauce->name,
                    'uom' => 'Liters',
                    'quantity' => 0.1500, // 150ml
                    'unit_cost' => (float) $sauce->cost_price,
                    'total_cost' => 0.1500 * (float) $sauce->cost_price,
                    'waste_percentage' => 1.50,
                    'notes' => 'Signature Truffle Sauce reduction',
                ]
            );

            if ($garlic) {
                BillOfMaterialsItem::updateOrCreate(
                    ['bill_of_materials_id' => $bomPasta->id, 'raw_item_id' => $garlic->id],
                    [
                        'sku' => $garlic->sku,
                        'raw_item_name' => $garlic->name,
                        'uom' => 'Kg',
                        'quantity' => 0.0200, // 20g
                        'unit_cost' => (float) $garlic->cost_price,
                        'total_cost' => 0.0200 * (float) $garlic->cost_price,
                        'waste_percentage' => 3.00,
                        'notes' => 'Minced garlic',
                    ]
                );
            }

            if ($onions) {
                BillOfMaterialsItem::updateOrCreate(
                    ['bill_of_materials_id' => $bomPasta->id, 'raw_item_id' => $onions->id],
                    [
                        'sku' => $onions->sku,
                        'raw_item_name' => $onions->name,
                        'uom' => 'Kg',
                        'quantity' => 0.0400, // 40g
                        'unit_cost' => (float) $onions->cost_price,
                        'total_cost' => 0.0400 * (float) $onions->cost_price,
                        'waste_percentage' => 4.00,
                        'notes' => 'Diced red onions',
                    ]
                );
            }

            if ($beef) {
                BillOfMaterialsItem::updateOrCreate(
                    ['bill_of_materials_id' => $bomPasta->id, 'raw_item_id' => $beef->id],
                    [
                        'sku' => $beef->sku,
                        'raw_item_name' => $beef->name,
                        'uom' => 'Kg',
                        'quantity' => 0.0800, // 80g
                        'unit_cost' => (float) $beef->cost_price,
                        'total_cost' => 0.0800 * (float) $beef->cost_price,
                        'waste_percentage' => 2.00,
                        'notes' => 'Striploin beef cubes',
                    ]
                );
            }
        }

        // 3. Artisan Signature Truffle White Sauce (Prep Batch Yield: 5 Liters)
        if ($sauce && $garlic && $onions) {
            $bomSauce = BillOfMaterials::updateOrCreate(
                ['bom_code' => 'BOM-SAUCE-TRF-01'],
                [
                    'finished_item_id' => $sauce->id,
                    'sku' => $sauce->sku,
                    'item_name' => $sauce->name,
                    'category' => 'Dairy & Sauces',
                    'uom' => 'Liters',
                    'yield_quantity' => 5.00, // 5-liter commissary bulk batch
                    'labor_cost' => 200.00,
                    'overhead_cost' => 100.00,
                    'total_raw_cost' => 1300.00,
                    'total_cost' => 1600.00,
                    'unit_cost' => 320.00, // 1600 / 5L = 320/L
                    'selling_price' => 550.00,
                    'margin_percentage' => 41.82,
                    'prep_time_minutes' => 60,
                    'shelf_life_days' => 10,
                    'instructions' => 'Sweat aromatics in butter, incorporate heavy cream and truffle puree, simmer until nappe.',
                    'is_active' => true,
                    'created_by' => 'Commissary Production Head',
                ]
            );

            BillOfMaterialsItem::updateOrCreate(
                ['bill_of_materials_id' => $bomSauce->id, 'raw_item_id' => $garlic->id],
                [
                    'sku' => $garlic->sku,
                    'raw_item_name' => $garlic->name,
                    'uom' => 'Kg',
                    'quantity' => 0.3000,
                    'unit_cost' => (float) $garlic->cost_price,
                    'total_cost' => 0.3000 * (float) $garlic->cost_price,
                    'waste_percentage' => 5.00,
                    'notes' => 'Finely grated garlic',
                ]
            );

            BillOfMaterialsItem::updateOrCreate(
                ['bill_of_materials_id' => $bomSauce->id, 'raw_item_id' => $onions->id],
                [
                    'sku' => $onions->sku,
                    'raw_item_name' => $onions->name,
                    'uom' => 'Kg',
                    'quantity' => 0.5000,
                    'unit_cost' => (float) $onions->cost_price,
                    'total_cost' => 0.5000 * (float) $onions->cost_price,
                    'waste_percentage' => 5.00,
                    'notes' => 'Finely diced onions',
                ]
            );
        }
    }
}
