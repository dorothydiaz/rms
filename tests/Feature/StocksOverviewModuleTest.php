<?php

namespace Tests\Feature;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\User;
use Tests\TestCase;

class StocksOverviewModuleTest extends TestCase
{
    protected User $user;
    protected InventoryItem $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['username' => 'inventory.auditor'],
            [
                'full_name' => 'Inventory Operations Auditor',
                'email' => 'auditor@rms-test.com',
                'password' => bcrypt('password123'),
                'role' => 'Manager',
                'status' => 'Active',
            ]
        );

        $cat = InventoryCategory::firstOrCreate(
            ['slug' => 'test-audit-cat'],
            [
                'name' => 'Audit Category',
                'code' => 'AUD',
                'badge_color' => 'emerald',
            ]
        );

        $this->product = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-AUD-ITEM-01'],
            [
                'name' => 'Artisan Coffee Roasters Premium',
                'category' => 'Audit Category',
                'uom' => 'Bag',
                'cost_price' => 150.00,
                'selling_price' => 300.00,
                'current_stock' => 25.00,
                'min_stock' => 5.00,
                'max_stock' => 50.00,
                'storage_location' => 'Main Warehouse Rack A',
                'is_active' => true,
            ]
        );
    }

    public function test_stocks_overview_renders_with_hydrated_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.stocks-overview'));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.stocks-overview');
        $response->assertViewHas('initialProducts');
        $response->assertViewHas('initialCategories');
        $response->assertViewHas('initialLedger');

        // Verify product name and SKU are in the rendered view
        $response->assertSee('TEST-AUD-ITEM-01');
        $response->assertSee('Artisan Coffee Roasters Premium');
    }

    public function test_stocks_overview_contains_production_and_waste_aggregations(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.stocks-overview'));

        $initialProducts = $response->viewData('initialProducts');
        $this->assertNotEmpty($initialProducts);

        $testItem = $initialProducts->firstWhere('sku', 'TEST-AUD-ITEM-01');
        $this->assertTrue(isset($testItem->production_produced));
        $this->assertTrue(isset($testItem->production_consumed));
        $this->assertTrue(isset($testItem->waste_total));
    }
}
