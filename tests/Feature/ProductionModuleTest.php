<?php

namespace Tests\Feature;

use App\Models\Inventory\BillOfMaterials;
use App\Models\Inventory\BillOfMaterialsItem;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\ProductionOrder;
use App\Models\Inventory\ProductionOrderItem;
use App\Models\Inventory\StockLedger;
use App\Models\User;
use Tests\TestCase;

class ProductionModuleTest extends TestCase
{
    protected User $user;
    protected InventoryItem $finishedItem;
    protected InventoryItem $rawCoffee;
    protected InventoryItem $rawSyrup;
    protected InventoryItem $rawCup;
    protected BillOfMaterials $bom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['username' => 'chef.production'],
            [
                'full_name' => 'Chef Mario / Production Lead',
                'email' => 'mario.prod@rms-test.com',
                'password' => bcrypt('secret123'),
                'role' => 'Manager',
                'status' => 'Active',
            ]
        );

        $catBev = InventoryCategory::firstOrCreate(
            ['slug' => 'beverages'],
            ['name' => 'Beverages', 'code' => 'BEV', 'badge_color' => 'sky']
        );

        $catRaw = InventoryCategory::firstOrCreate(
            ['slug' => 'raw-ingredients'],
            ['name' => 'Raw Ingredients', 'code' => 'RAW', 'badge_color' => 'purple']
        );

        // Finished item
        $this->finishedItem = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-PROD-LATTE'],
            [
                'name' => 'Signature Artisan Spanish Latte',
                'category' => 'Beverages',
                'uom' => 'Cup',
                'cost_price' => 45.00,
                'selling_price' => 150.00,
                'current_stock' => 20.00,
                'min_stock' => 10.00,
                'max_stock' => 100.00,
                'is_active' => true,
            ]
        );
        $this->finishedItem->update(['current_stock' => 20.00]);

        // Raw items
        $this->rawCoffee = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-RAW-COFFEE'],
            [
                'name' => 'Dark Roast Espresso Beans (1kg)',
                'category' => 'Raw Ingredients',
                'uom' => 'Kg',
                'cost_price' => 650.00,
                'selling_price' => 0.00,
                'current_stock' => 10.00, // 10 kg
                'min_stock' => 2.00,
                'max_stock' => 50.00,
                'is_active' => true,
            ]
        );
        $this->rawCoffee->update(['current_stock' => 10.00]);

        $this->rawSyrup = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-RAW-SYRUP'],
            [
                'name' => 'Caramel Vanilla Infused Syrup',
                'category' => 'Raw Ingredients',
                'uom' => 'Bottle',
                'cost_price' => 290.00,
                'selling_price' => 0.00,
                'current_stock' => 5.00, // 5 bottles
                'min_stock' => 1.00,
                'max_stock' => 20.00,
                'is_active' => true,
            ]
        );
        $this->rawSyrup->update(['current_stock' => 5.00]);

        $this->rawCup = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-RAW-CUP'],
            [
                'name' => 'Insulated Kraft Paper Cup (16oz)',
                'category' => 'Raw Ingredients',
                'uom' => 'Piece',
                'cost_price' => 8.50,
                'selling_price' => 0.00,
                'current_stock' => 200.00, // 200 pcs
                'min_stock' => 50.00,
                'max_stock' => 500.00,
                'is_active' => true,
            ]
        );
        $this->rawCup->update(['current_stock' => 200.00]);

        // Standard BOM: Yield = 1 Cup
        $this->bom = BillOfMaterials::firstOrCreate(
            ['bom_code' => 'BOM-TEST-LATTE-001'],
            [
                'finished_item_id' => $this->finishedItem->id,
                'sku' => $this->finishedItem->sku,
                'item_name' => $this->finishedItem->name,
                'category' => 'Beverages',
                'uom' => 'Cup',
                'yield_quantity' => 1.00,
                'labor_cost' => 10.00,
                'overhead_cost' => 5.00,
                'total_raw_cost' => 30.20,
                'total_cost' => 45.20,
                'unit_cost' => 45.20,
                'selling_price' => 150.00,
                'margin_percentage' => 69.87,
                'prep_time_minutes' => 5,
                'shelf_life_days' => 2,
                'instructions' => 'Grind beans, pull espresso shot, froth milk with syrup.',
                'is_active' => true,
                'created_by' => 'Chef Test Lead',
            ]
        );

        BillOfMaterialsItem::firstOrCreate(
            ['bill_of_materials_id' => $this->bom->id, 'raw_item_id' => $this->rawCoffee->id],
            [
                'sku' => $this->rawCoffee->sku,
                'raw_item_name' => $this->rawCoffee->name,
                'uom' => 'Kg',
                'quantity' => 0.0200, // 20g per cup
                'unit_cost' => 650.00,
                'total_cost' => 13.00,
            ]
        );

        BillOfMaterialsItem::firstOrCreate(
            ['bill_of_materials_id' => $this->bom->id, 'raw_item_id' => $this->rawSyrup->id],
            [
                'sku' => $this->rawSyrup->sku,
                'raw_item_name' => $this->rawSyrup->name,
                'uom' => 'Bottle',
                'quantity' => 0.0300,
                'unit_cost' => 290.00,
                'total_cost' => 8.70,
            ]
        );

        BillOfMaterialsItem::firstOrCreate(
            ['bill_of_materials_id' => $this->bom->id, 'raw_item_id' => $this->rawCup->id],
            [
                'sku' => $this->rawCup->sku,
                'raw_item_name' => $this->rawCup->name,
                'uom' => 'Piece',
                'quantity' => 1.0000,
                'unit_cost' => 8.50,
                'total_cost' => 8.50,
            ]
        );
    }

    /**
     * Test 1: Production Page renders with hydrated data and BOM tabs
     */
    public function test_production_page_renders_with_hydrated_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.production'));

        $response->assertStatus(200);
        $response->assertSee('Production');
        $response->assertSee('Kitchen Assembly');
        $response->assertSee('New Production Batch');
        $response->assertSee('BOM Recipe Reference Catalog');
        $response->assertSee('Signature Artisan Spanish Latte');
    }

    /**
     * Test 2: Can fetch live production payload via API
     */
    public function test_can_get_production_data_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('inventory.api.production-data'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data.boms'));
        $this->assertArrayHasKey('stats', $response->json('data'));
    }

    /**
     * Test 3: Can create and complete a production batch with stock addition and raw material deduction
     */
    public function test_can_create_and_complete_production_batch_with_stock_addition_and_subtraction(): void
    {
        $this->finishedItem->update(['current_stock' => 20.00]);
        $this->rawCoffee->update(['current_stock' => 10.00]);
        $this->rawSyrup->update(['current_stock' => 5.00]);
        $this->rawCup->update(['current_stock' => 200.00]);

        $initialFinStock = (float) $this->finishedItem->current_stock; // 20
        $initialCoffeeStock = (float) $this->rawCoffee->current_stock; // 10
        $initialSyrupStock = (float) $this->rawSyrup->current_stock;   // 5
        $initialCupStock = (float) $this->rawCup->current_stock;       // 200

        $batchQty = 50.0; // Produce 50 cups

        $payload = [
            'bill_of_materials_id' => $this->bom->id,
            'planned_quantity' => $batchQty,
            'actual_quantity' => $batchQty,
            'production_date' => date('Y-m-d'),
            'kitchen_station' => 'Beverage & Barista Station',
            'produced_by' => 'Chef Barista Mario',
            'verified_by' => 'Sous Chef Anna',
            'proceed_with_shortage' => false,
            'quality_notes' => 'Tasting temperature at 65C, crema thick and balanced.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-production-batch'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify Finished Item ADDITION (+)
        $this->finishedItem->refresh();
        $this->assertEquals($initialFinStock + $batchQty, (float) $this->finishedItem->current_stock);

        // Verify Raw Materials SUBTRACTION (-)
        // 50 cups * 0.0200 kg = 1.0000 kg coffee deducted
        $this->rawCoffee->refresh();
        $this->assertEquals($initialCoffeeStock - 1.0, (float) $this->rawCoffee->current_stock);

        // 50 cups * 0.0300 bottle = 1.5000 bottle syrup deducted
        $this->rawSyrup->refresh();
        $this->assertEquals($initialSyrupStock - 1.5, (float) $this->rawSyrup->current_stock);

        // 50 cups * 1.0000 piece = 50 cups deducted
        $this->rawCup->refresh();
        $this->assertEquals($initialCupStock - 50.0, (float) $this->rawCup->current_stock);

        // Verify Immutable Stock Ledger records
        $prodInLedger = StockLedger::where('sku', $this->finishedItem->sku)
            ->where('transaction_type', 'PRODUCTION_IN')
            ->latest('id')
            ->first();
        $this->assertNotNull($prodInLedger);
        $this->assertEquals($batchQty, (float) $prodInLedger->quantity_change);

        $prodOutCoffeeLedger = StockLedger::where('sku', $this->rawCoffee->sku)
            ->where('transaction_type', 'PRODUCTION_OUT')
            ->latest('id')
            ->first();
        $this->assertNotNull($prodOutCoffeeLedger);
        $this->assertEquals(-1.0, (float) $prodOutCoffeeLedger->quantity_change);

        // Verify Production Order and Items records in DB
        $order = ProductionOrder::where('production_number', $response->json('data.order.production_number'))->first();
        $this->assertNotNull($order);
        $this->assertEquals('COMPLETED', $order->status);
        $this->assertCount(3, $order->items);
    }

    /**
     * Test 4: Shortage detection triggers 422 validation error when raw materials are insufficient
     */
    public function test_shortage_detection_triggers_validation_error_when_stock_insufficient(): void
    {
        $this->rawCoffee->update(['current_stock' => 1.00]); // Only 1kg (can only do 50 cups)

        // Try to produce 500 cups
        $payload = [
            'bill_of_materials_id' => $this->bom->id,
            'planned_quantity' => 500.0,
            'actual_quantity' => 500.0,
            'production_date' => date('Y-m-d'),
            'kitchen_station' => 'Beverage & Barista Station',
            'produced_by' => 'Chef Barista Mario',
            'proceed_with_shortage' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-production-batch'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('has_shortage', true);
        $this->assertNotEmpty($response->json('shortages'));
    }

    /**
     * Test 5: Production can proceed with explicit shortage override flag
     */
    public function test_shortage_can_proceed_with_override_flag(): void
    {
        $this->rawCoffee->update(['current_stock' => 1.00]);

        $payload = [
            'bill_of_materials_id' => $this->bom->id,
            'planned_quantity' => 500.0,
            'actual_quantity' => 500.0,
            'production_date' => date('Y-m-d'),
            'kitchen_station' => 'Beverage & Barista Station',
            'produced_by' => 'Chef Barista Mario',
            'proceed_with_shortage' => true, // Explicit override allowed by user popup confirmation
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-production-batch'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $order = ProductionOrder::where('production_number', $response->json('data.order.production_number'))->first();
        $this->assertTrue((bool) $order->proceed_with_shortage);
        $this->assertNotEmpty($order->shortage_notes);
    }

    /**
     * Test 6: Stocks Overview API reflects Production Movements (Produced + / Consumed -)
     */
    public function test_stocks_overview_api_synchronization_and_production_column(): void
    {
        $this->rawCoffee->update(['current_stock' => 10.00]);
        $this->rawSyrup->update(['current_stock' => 5.00]);
        $this->rawCup->update(['current_stock' => 200.00]);

        // Execute a small batch of 10 cups
        $res = $this->actingAs($this->user)->postJson(route('inventory.api.create-production-batch'), [
            'bill_of_materials_id' => $this->bom->id,
            'planned_quantity' => 10.0,
            'actual_quantity' => 10.0,
            'production_date' => date('Y-m-d'),
            'kitchen_station' => 'Beverage & Barista Station',
            'produced_by' => 'Chef Barista Mario',
            'proceed_with_shortage' => false,
        ]);
        $res->assertStatus(200);

        $response = $this->actingAs($this->user)
            ->getJson(route('inventory.api.stocks-overview-data'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify production movements in response
        $this->assertArrayHasKey('productionSummary', $response->json('data'));
        $producedMap = $response->json('data.productionSummary.produced');
        $consumedMap = $response->json('data.productionSummary.consumed');

        $this->assertGreaterThanOrEqual(10, (float) ($producedMap[$this->finishedItem->sku] ?? 0));
        // Coffee consumed >= 10 * 0.02 = 0.2
        $this->assertGreaterThanOrEqual(0.2, (float) ($consumedMap[$this->rawCoffee->sku] ?? 0));
    }
}
