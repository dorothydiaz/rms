<?php

namespace Tests\Feature;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Purchase\ProcurementVendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOutUsageModuleTest extends TestCase
{
    protected User $user;
    protected InventoryItem $testProduct;
    protected ProcurementVendor $testVendor;

    protected function setUp(): void
    {
        parent::setUp();

        // Authenticated user
        $this->user = User::firstOrCreate(
            ['username' => 'chef.marco'],
            [
                'full_name' => 'Chef Marco / Lead Dispatcher',
                'email' => 'warehouse.lead@rms-test.com',
                'password' => bcrypt('password123'),
                'role' => 'Manager',
                'status' => 'Active',
            ]
        );

        // Category
        $cat = InventoryCategory::firstOrCreate(
            ['slug' => 'raw-kitchen-meats'],
            [
                'name' => 'Raw Kitchen Meats',
                'code' => 'RKM',
                'badge_color' => 'purple',
            ]
        );

        // Product in Item Master with stock
        $this->testProduct = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-BEEF-01'],
            [
                'name' => 'Prime Australian Beef Striploin',
                'category' => 'Raw Kitchen Meats',
                'uom' => 'Kg',
                'cost_price' => 750.00,
                'selling_price' => 0.00,
                'current_stock' => 50.00,
                'min_stock' => 10.00,
                'max_stock' => 100.00,
                'storage_location' => 'Meat Chiller B-1',
                'is_active' => true,
            ]
        );
        $this->testProduct->update(['current_stock' => 50.00]);

        // Vendor
        $this->testVendor = ProcurementVendor::firstOrCreate(
            ['vendor_code' => 'VND-MEAT-TEST'],
            [
                'legal_name' => 'Artisan Meats Supply Corp',
                'trade_name' => 'Artisan Butchery',
                'category' => 'Meats & Poultry Supplier',
                'contact_person' => 'Dennis Mendoza',
                'email' => 'orders@artisanmeats.test',
                'phone' => '+63 917 555 0192',
                'address' => 'Pasig City Food Terminal',
                'sourcing_channel' => 'commercial',
                'is_active' => true,
            ]
        );
    }

    /**
     * Test 1: Stock out view renders successfully with authenticated user
     */
    public function test_stock_out_page_renders_with_hydrated_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.stock-out'));

        $response->assertStatus(200);
        $response->assertSee('Stock Out / Usage');
        $response->assertSee('List of Dispatches');
        $response->assertSee('Create Draft Requisition');
        $response->assertSee('Pick Pack Ship');
        $response->assertSee('Prime Australian Beef Striploin');
    }

    /**
     * Test 2: Can create a Draft Requisition via API
     */
    public function test_can_create_stock_out_draft_order(): void
    {
        $payload = [
            'order_type' => 'KITCHEN_USAGE',
            'destination_type' => 'INTERNAL_KITCHEN',
            'destination_location' => 'Main Kitchen - Hot Line',
            'department' => 'Kitchen Operations',
            'requested_by' => 'Chef Marco',
            'priority' => 'NORMAL',
            'reference_no' => 'REQ-KT-901',
            'notes' => 'For evening dinner service banquet',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'inventory_item_id' => $this->testProduct->id,
                    'item_name' => $this->testProduct->name,
                    'category' => $this->testProduct->category,
                    'uom' => $this->testProduct->uom,
                    'requested_qty' => 12.50,
                    'unit_cost' => 750.00,
                    'storage_location' => $this->testProduct->storage_location,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-stock-out-draft'), $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('stock_out_orders', [
            'order_type' => 'KITCHEN_USAGE',
            'destination_location' => 'Main Kitchen - Hot Line',
            'status' => 'DRAFT',
            'total_items_count' => 1,
            'total_requested_qty' => 12.50,
        ]);

        $this->assertDatabaseHas('stock_out_items', [
            'sku' => 'TEST-BEEF-01',
            'requested_qty' => 12.50,
            'packed_qty' => 0.00,
        ]);
    }

    /**
     * Test 3: Can update packed quantity during Pick Pack Ship phase
     */
    public function test_can_update_pack_quantities_in_pick_pack_ship(): void
    {
        // First create draft
        $draftPayload = [
            'order_type' => 'BRANCH_TRANSFER',
            'destination_type' => 'BRANCH',
            'destination_location' => 'Branch 1 - Makati Bistro',
            'requested_by' => 'Store Manager',
            'priority' => 'URGENT',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'inventory_item_id' => $this->testProduct->id,
                    'item_name' => $this->testProduct->name,
                    'requested_qty' => 10.00,
                    'unit_cost' => 750.00,
                ]
            ]
        ];

        $draftResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-stock-out-draft'), $draftPayload);

        $orderId = $draftResponse->json('data.order.id');

        // Now update pack quantity
        $packPayload = [
            'order_id' => $orderId,
            'picker_name' => 'Alex Warehouse',
            'carrier_name' => 'Logistics Van NDF-881',
            'tracking_waybill' => 'WB-2026-991',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'packed_qty' => 10.00, // Exactly matched!
                ]
            ]
        ];

        $packResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.update-stock-out-pack'), $packPayload);

        $packResponse->assertStatus(200);
        $packResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_out_orders', [
            'id' => $orderId,
            'status' => 'PACKED',
            'total_packed_qty' => 10.00,
            'picker_name' => 'Alex Warehouse',
        ]);
    }

    /**
     * Test 4: Confirming Ship deducts stock from Item Master and creates StockLedger record
     */
    public function test_confirming_ship_deducts_inventory_and_records_stock_ledger(): void
    {
        $initialStock = $this->testProduct->current_stock; // 50.00

        // Create draft
        $draftPayload = [
            'order_type' => 'VENDOR_RETURN',
            'destination_type' => 'VENDOR',
            'vendor_id' => $this->testVendor->id,
            'vendor_name' => $this->testVendor->legal_name,
            'destination_location' => $this->testVendor->address,
            'requested_by' => 'Procurement Officer',
            'reference_no' => 'RMA-2026-004',
            'notes' => 'Damaged outer seal upon delivery',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'inventory_item_id' => $this->testProduct->id,
                    'item_name' => $this->testProduct->name,
                    'requested_qty' => 5.00,
                    'unit_cost' => 750.00,
                ]
            ]
        ];

        $draftResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-stock-out-draft'), $draftPayload);

        $orderId = $draftResponse->json('data.order.id');

        // Confirm Ship with packed qty 5.00
        $shipPayload = [
            'order_id' => $orderId,
            'picker_name' => 'Dispatch Lead Ray',
            'carrier_name' => 'Vendor RMA Pickup Vehicle',
            'tracking_waybill' => 'RMA-RECEIPT-551',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'packed_qty' => 5.00,
                ]
            ]
        ];

        $shipResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.confirm-ship-stock-out'), $shipPayload);

        $shipResponse->assertStatus(200);
        $shipResponse->assertJson(['success' => true]);

        // Verify Order status is SHIPPED
        $this->assertDatabaseHas('stock_out_orders', [
            'id' => $orderId,
            'status' => 'SHIPPED',
            'total_packed_qty' => 5.00,
        ]);

        // Verify inventory_items.current_stock is decremented by 5.00
        $this->testProduct->refresh();
        $this->assertEquals(45.00, (float)$this->testProduct->current_stock);

        // Verify immutable StockLedger entry exists
        $this->assertDatabaseHas('stock_ledger', [
            'sku' => $this->testProduct->sku,
            'transaction_type' => 'STOCK_OUT',
            'before_quantity' => 50.00,
            'quantity_change' => -5.00,
            'after_quantity' => 45.00,
        ]);
    }

    /**
     * Test 5: Cannot confirm ship an order with 0 packed items
     */
    public function test_cannot_ship_order_with_zero_packed_items(): void
    {
        $draftPayload = [
            'order_type' => 'KITCHEN_USAGE',
            'destination_location' => 'Main Kitchen Line',
            'requested_by' => 'Chef Marco',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'item_name' => $this->testProduct->name,
                    'requested_qty' => 5.00,
                    'unit_cost' => 750.00,
                ]
            ]
        ];

        $draftResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-stock-out-draft'), $draftPayload);

        $orderId = $draftResponse->json('data.order.id');

        // Attempt to confirm ship without packing any quantity
        $shipResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.confirm-ship-stock-out'), [
                'order_id' => $orderId,
            ]);

        $shipResponse->assertStatus(422);
        $shipResponse->assertJson(['success' => false]);
    }

    /**
     * Test 6: Line item progress percentage and match/exceeds status logic
     */
    public function test_line_item_progress_and_match_status_states(): void
    {
        $draftPayload = [
            'order_type' => 'KITCHEN_USAGE',
            'destination_location' => 'Bakery Station',
            'requested_by' => 'Pastry Chef',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'item_name' => $this->testProduct->name,
                    'requested_qty' => 10.00,
                    'unit_cost' => 750.00,
                ]
            ]
        ];

        $draftResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-stock-out-draft'), $draftPayload);

        $orderId = $draftResponse->json('data.order.id');
        $order = \App\Models\Inventory\StockOutOrder::with('items')->find($orderId);
        $item = $order->items->first();

        // 1. Initial State: 0 packed -> UNPICKED
        $this->assertEquals(0.0, $item->progress_percent);
        $this->assertEquals('UNPICKED', $item->status_label);

        // 2. Partial State: 6 packed -> IN_PROGRESS, 60%
        $item->packed_qty = 6.00;
        $this->assertEquals(60.0, $item->progress_percent);
        $this->assertEquals('IN_PROGRESS', $item->status_label);

        // 3. Matched State: 10 packed -> MATCHED, 100%
        $item->packed_qty = 10.00;
        $this->assertEquals(100.0, $item->progress_percent);
        $this->assertEquals('MATCHED', $item->status_label);

        // 4. Exceeds State: 12.5 packed -> EXCEEDS, 125%
        $item->packed_qty = 12.50;
        $this->assertEquals(125.0, $item->progress_percent);
        $this->assertEquals('EXCEEDS', $item->status_label);
    }

    /**
     * Test 7: Stocks Overview API reflects deducted stock after Stock Out is completed
     */
    public function test_stocks_overview_api_synchronization(): void
    {
        $currentStockBefore = (float) $this->testProduct->current_stock;

        $draftPayload = [
            'order_type' => 'KITCHEN_USAGE',
            'destination_location' => 'Main Kitchen Line',
            'requested_by' => 'Chef Marco',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'item_name' => $this->testProduct->name,
                    'requested_qty' => 3.00,
                    'unit_cost' => 750.00,
                ]
            ]
        ];

        $draftResponse = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-stock-out-draft'), $draftPayload);

        $orderId = $draftResponse->json('data.order.id');

        $this->actingAs($this->user)
            ->postJson(route('inventory.api.confirm-ship-stock-out'), [
                'order_id' => $orderId,
                'items' => [
                    [
                        'sku' => $this->testProduct->sku,
                        'packed_qty' => 3.00,
                    ]
                ]
            ]);

        // Now fetch Stocks Overview API
        $overviewResponse = $this->actingAs($this->user)
            ->getJson(route('inventory.api.stocks-overview-data'));

        $overviewResponse->assertStatus(200);
        $overviewResponse->assertJson(['success' => true]);

        $products = collect($overviewResponse->json('data.products'));
        $targetProduct = $products->firstWhere('sku', $this->testProduct->sku);

        $this->assertNotNull($targetProduct);
        $this->assertEquals($currentStockBefore - 3.00, (float) $targetProduct['current_stock']);
    }
}
