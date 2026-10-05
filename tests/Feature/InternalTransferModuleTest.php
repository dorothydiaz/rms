<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\User;
use Tests\TestCase;

class InternalTransferModuleTest extends TestCase
{
    protected User $user;
    protected Branch $sourceBranch;
    protected Branch $destinationBranch;
    protected InventoryItem $testProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['username' => 'commissary.lead'],
            [
                'full_name' => 'Chef Arthur / Commissary Logistics Manager',
                'email' => 'commissary.lead@rms-test.com',
                'password' => bcrypt('password123'),
                'role' => 'Manager',
                'status' => 'Active',
            ]
        );

        $this->sourceBranch = Branch::firstOrCreate(
            ['code' => 'COMM-HQ'],
            [
                'name' => 'Central Commissary Main Storage',
                'address' => 'Pasig Central Hub',
                'is_active' => true,
            ]
        );

        $this->destinationBranch = Branch::firstOrCreate(
            ['code' => 'BR-MAKATI'],
            [
                'name' => 'Makati Flagship Branch',
                'address' => 'Ayala Ave, Makati City',
                'is_active' => true,
            ]
        );

        $cat = InventoryCategory::firstOrCreate(
            ['slug' => 'dairy-and-sauces'],
            [
                'name' => 'Dairy & Sauces',
                'code' => 'DRY',
                'badge_color' => 'blue',
            ]
        );

        $this->testProduct = InventoryItem::firstOrCreate(
            ['sku' => 'SAUCE-TRF-01'],
            [
                'name' => 'Artisan Signature Truffle White Sauce',
                'category' => 'Dairy & Sauces',
                'uom' => 'Liters',
                'cost_price' => 320.00,
                'selling_price' => 0.00,
                'current_stock' => 40.00, // 40 Liters available
                'min_stock' => 10.00,
                'max_stock' => 100.00,
                'storage_location' => 'Cold Walk-In Vault A',
                'is_active' => true,
            ]
        );
        $this->testProduct->update(['current_stock' => 40.00]);
    }

    /**
     * Test 1: Page renders with the 3 tabs and hydrated data
     */
    public function test_internal_transfer_page_renders_with_hydrated_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.internal-transfer'));

        $response->assertStatus(200);
        $response->assertSee('Internal Transfer');
        $response->assertSee('Transfer Register');
        $response->assertSee('Pending Branch Requests');
        $response->assertSee('Direct Push Transfer');
        $response->assertSee('Artisan Signature Truffle White Sauce');
    }

    /**
     * Test 2: Can create a Custom Transfer (Direct Push delivery that doesn't need branch request)
     */
    public function test_can_create_custom_transfer_direct_push(): void
    {
        $payload = [
            'transfer_type' => 'CUSTOM_PUSH',
            'source_location' => 'Central Commissary Main Storage',
            'destination_branch_id' => $this->destinationBranch->id,
            'destination_location' => $this->destinationBranch->name,
            'reason_code' => 'Commissary Bulk Batch Push',
            'dispatched_by' => 'Chef Arthur',
            'carrier_name' => 'Refrigerated Van NDF-309',
            'driver_plate' => 'NDF 3099',
            'waybill_number' => 'WB-TRF-2026-001',
            'priority' => 'NORMAL',
            'notes' => 'Fresh weekly sauce replenishment',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'inventory_item_id' => $this->testProduct->id,
                    'item_name' => $this->testProduct->name,
                    'category' => $this->testProduct->category,
                    'uom' => $this->testProduct->uom,
                    'transferred_qty' => 15.00,
                    'unit_cost' => 320.00,
                    'storage_location' => $this->testProduct->storage_location,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-custom-transfer'), $payload);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Assert created in database with IN_TRANSIT status and stock deducted
        $this->assertDatabaseHas('internal_transfers', [
            'transfer_type' => 'CUSTOM_PUSH',
            'destination_location' => 'Makati Flagship Branch',
            'status' => 'IN_TRANSIT',
            'total_items_count' => 1,
            'total_transferred_qty' => 15.00,
        ]);

        // Assert inventory was decremented from 40.00 to 25.00
        $this->testProduct->refresh();
        $this->assertEquals(25.00, (float)$this->testProduct->current_stock);

        // Assert StockLedger was written
        $this->assertDatabaseHas('stock_ledger', [
            'sku' => $this->testProduct->sku,
            'transaction_type' => 'STOCK_OUT',
            'before_quantity' => 40.00,
            'quantity_change' => -15.00,
            'after_quantity' => 25.00,
        ]);
    }

    /**
     * Test 3: Quantity validation prevents transferring more than available stock
     */
    public function test_quantity_validation_prevents_transferring_more_than_available_stock(): void
    {
        $payload = [
            'transfer_type' => 'CUSTOM_PUSH',
            'source_location' => 'Central Commissary Main Storage',
            'destination_branch_id' => $this->destinationBranch->id,
            'destination_location' => $this->destinationBranch->name,
            'dispatched_by' => 'Chef Arthur',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'item_name' => $this->testProduct->name,
                    'transferred_qty' => 999.00, // Exceeds 40.00 available stock!
                    'unit_cost' => 320.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-custom-transfer'), $payload);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        // Stock must remain unchanged
        $this->testProduct->refresh();
        $this->assertEquals(40.00, (float)$this->testProduct->current_stock);
    }

    /**
     * Test 4: Can fulfill and dispatch a Pending Transfer Request
     */
    public function test_can_approve_and_dispatch_pending_transfer_request(): void
    {
        // Seed a pending branch request
        $trfNum = 'REQ-TRF-' . uniqid();
        $transfer = \App\Models\Inventory\InternalTransfer::create([
            'transfer_number' => $trfNum,
            'transfer_type' => 'BRANCH_REQUEST',
            'status' => 'PENDING',
            'source_location' => 'Central Commissary Main Storage',
            'destination_branch_id' => $this->destinationBranch->id,
            'destination_location' => $this->destinationBranch->name,
            'requisition_reference' => 'B-REQ-0042',
            'requested_by' => 'Makati Kitchen Supervisor',
            'total_items_count' => 1,
            'total_requested_qty' => 10.00,
            'total_transferred_qty' => 0.00,
            'total_valuation' => 3200.00,
        ]);

        \App\Models\Inventory\InternalTransferItem::create([
            'internal_transfer_id' => $transfer->id,
            'inventory_item_id' => $this->testProduct->id,
            'sku' => $this->testProduct->sku,
            'item_name' => $this->testProduct->name,
            'uom' => $this->testProduct->uom,
            'source_available_stock' => 40.00,
            'requested_qty' => 10.00,
            'transferred_qty' => 0.00,
            'unit_cost' => 320.00,
            'total_cost' => 3200.00,
        ]);

        // Now approve and dispatch
        $dispatchPayload = [
            'transfer_id' => $transfer->id,
            'dispatched_by' => 'Chef Arthur',
            'carrier_name' => 'Logistics Truck 4',
            'driver_plate' => 'WTY 8821',
            'waybill_number' => 'WB-8821',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'transferred_qty' => 10.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.dispatch-transfer'), $dispatchPayload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('internal_transfers', [
            'id' => $transfer->id,
            'status' => 'IN_TRANSIT',
            'total_transferred_qty' => 10.00,
        ]);

        $this->testProduct->refresh();
        $this->assertEquals(30.00, (float)$this->testProduct->current_stock);
    }

    /**
     * Test 5: Can confirm receipt of transfer at destination branch
     */
    public function test_can_receive_transfer_at_destination_branch(): void
    {
        $transfer = \App\Models\Inventory\InternalTransfer::create([
            'transfer_number' => 'DIR-TRF-' . uniqid(),
            'transfer_type' => 'CUSTOM_PUSH',
            'status' => 'IN_TRANSIT',
            'source_location' => 'Central Commissary Main Storage',
            'destination_branch_id' => $this->destinationBranch->id,
            'destination_location' => $this->destinationBranch->name,
            'dispatched_by' => 'Chef Arthur',
            'dispatched_at' => now(),
            'total_items_count' => 1,
            'total_requested_qty' => 5.00,
            'total_transferred_qty' => 5.00,
            'total_valuation' => 1600.00,
        ]);

        \App\Models\Inventory\InternalTransferItem::create([
            'internal_transfer_id' => $transfer->id,
            'inventory_item_id' => $this->testProduct->id,
            'sku' => $this->testProduct->sku,
            'item_name' => $this->testProduct->name,
            'uom' => $this->testProduct->uom,
            'source_available_stock' => 40.00,
            'requested_qty' => 5.00,
            'transferred_qty' => 5.00,
            'received_qty' => 0.00,
            'unit_cost' => 320.00,
            'total_cost' => 1600.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('inventory.api.receive-transfer'), [
                'transfer_id' => $transfer->id,
                'received_by' => 'Makati Receiving Supervisor',
                'notes' => 'All items received in good refrigerated condition',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('internal_transfers', [
            'id' => $transfer->id,
            'status' => 'COMPLETED',
            'received_by' => 'Makati Receiving Supervisor',
            'total_received_qty' => 5.00,
        ]);
    }

    /**
     * Test 6: Stocks Overview API reflects updated central stock after transfer
     */
    public function test_stocks_overview_synchronization_after_internal_transfer(): void
    {
        $stockBefore = (float) $this->testProduct->current_stock;

        $payload = [
            'transfer_type' => 'CUSTOM_PUSH',
            'source_location' => 'Central Commissary Main Storage',
            'destination_branch_id' => $this->destinationBranch->id,
            'destination_location' => $this->destinationBranch->name,
            'dispatched_by' => 'Chef Arthur',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'item_name' => $this->testProduct->name,
                    'transferred_qty' => 8.00,
                    'unit_cost' => 320.00,
                ]
            ]
        ];

        $this->actingAs($this->user)
            ->postJson(route('inventory.api.create-custom-transfer'), $payload);

        // Fetch Stocks Overview API
        $overviewResponse = $this->actingAs($this->user)
            ->getJson(route('inventory.api.stocks-overview-data'));

        $overviewResponse->assertStatus(200);
        $products = collect($overviewResponse->json('data.products'));
        $targetProduct = $products->firstWhere('sku', $this->testProduct->sku);

        $this->assertNotNull($targetProduct);
        $this->assertEquals($stockBefore - 8.00, (float)$targetProduct['current_stock']);
    }
}
