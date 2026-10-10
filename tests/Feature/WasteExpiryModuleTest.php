<?php

namespace Tests\Feature;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\WasteRecord;
use App\Models\Inventory\WasteRecordItem;
use App\Models\User;
use Tests\TestCase;

class WasteExpiryModuleTest extends TestCase
{
    protected User $user;
    protected InventoryItem $testItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['username' => 'auditor.waste'],
            [
                'full_name' => 'Auditor Vance / Kitchen Ops',
                'email' => 'auditor.waste@rms-test.com',
                'password' => bcrypt('secret123'),
                'role' => 'Manager',
                'status' => 'Active',
            ]
        );

        InventoryCategory::firstOrCreate(
            ['slug' => 'dairy'],
            ['name' => 'Dairy', 'code' => 'DRY', 'badge_color' => 'amber']
        );

        $this->testItem = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-WST-MILK'],
            [
                'name' => 'Fresh Full Cream Milk Barista Blend 1L',
                'category' => 'Dairy',
                'uom' => 'Liter',
                'cost_price' => 85.00,
                'selling_price' => 120.00,
                'current_stock' => 50.00,
                'min_stock' => 10.00,
                'max_stock' => 100.00,
                'storage_location' => 'Chiller 1',
                'is_active' => true,
            ]
        );
        // Reset stock to predictable baseline
        $this->testItem->current_stock = 50.00;
        $this->testItem->save();
    }

    /**
     * Test 1: Waste & Expiry page renders with hydrated data and HR theme tokens
     */
    public function test_waste_expiry_page_renders_with_hydrated_data(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.waste-expiry'));

        $response->assertStatus(200);
        $response->assertSee('Waste, Defect & Expiry Tracking', false);
        $response->assertSee('Total Waste Valuation');
        $response->assertSee('Waste & Defect Logs', false);
        $response->assertSee('Log Waste / Defect Entry', false);
        $response->assertSee('Expiry & At-Risk Watchlist', false);
        $response->assertSee('TEST-WST-MILK');
    }

    /**
     * Test 2: Can fetch waste data via API
     */
    public function test_can_get_waste_data_via_api(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('inventory.api.waste-expiry-data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'wasteRecords',
                'products',
                'stats' => [
                    'totalWasteValue',
                    'totalWasteItemsCount',
                ],
            ],
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Test 3: Can log waste record with atomic stock decrement and ledger audit
     */
    public function test_can_create_waste_record_with_stock_deduction_and_ledger(): void
    {
        $initialStock = (float) $this->testItem->current_stock;
        $writeOffQty = 5.00;

        $payload = [
            'waste_type' => 'SPOILAGE',
            'branch_name' => 'Central Commissary',
            'storage_location' => 'Chiller 1',
            'disposal_method' => 'Discarded / Trashed',
            'reported_by' => 'Chef Marco',
            'waste_date' => now()->format('Y-m-d'),
            'notes' => 'Curdled milk due to temperature rise',
            'items' => [
                [
                    'inventory_item_id' => $this->testItem->id,
                    'quantity' => $writeOffQty,
                    'reason_code' => 'Cold Chain Temperature Breach',
                    'batch_lot_no' => 'LOT-MILK-202610-01',
                    'expiry_date' => now()->addDays(2)->format('Y-m-d'),
                    'action_taken' => 'Discarded',
                    'notes' => 'Discarded to drain',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('inventory.api.create-waste-record'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify stock deducted
        $this->testItem->refresh();
        $this->assertEquals($initialStock - $writeOffQty, (float) $this->testItem->current_stock);

        // Verify waste record in DB
        $wasteRecordId = $response->json('data.wasteRecord.id');
        $this->assertDatabaseHas('waste_records', [
            'id' => $wasteRecordId,
            'waste_type' => 'SPOILAGE',
            'total_cost' => $writeOffQty * $this->testItem->cost_price,
        ]);

        $this->assertDatabaseHas('waste_record_items', [
            'waste_record_id' => $wasteRecordId,
            'sku' => $this->testItem->sku,
            'quantity' => $writeOffQty,
            'reason_code' => 'Cold Chain Temperature Breach',
        ]);

        // Verify immutable Stock Ledger entry
        $this->assertDatabaseHas('stock_ledger', [
            'sku' => $this->testItem->sku,
            'transaction_type' => 'WASTE',
            'reference_type' => 'waste_records',
            'reference_id' => $wasteRecordId,
            'quantity_change' => -$writeOffQty,
        ]);
    }

    /**
     * Test 4: Quantity validation prevents writing off more than available stock without override
     */
    public function test_stock_shortage_prevents_excessive_write_off_without_override(): void
    {
        $currentStock = (float) $this->testItem->current_stock;
        $excessiveQty = $currentStock + 50.00;

        $payload = [
            'waste_type' => 'EXPIRED',
            'disposal_method' => 'Composted',
            'reported_by' => 'Chef Marco',
            'waste_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->testItem->id,
                    'quantity' => $excessiveQty,
                    'reason_code' => 'Expired on Shelf',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('inventory.api.create-waste-record'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Insufficient physical stock', $response->json('message'));
    }

    /**
     * Test 5: Can override stock shortage when force_override flag is set
     */
    public function test_can_override_stock_shortage_with_flag(): void
    {
        $currentStock = (float) $this->testItem->current_stock;
        $excessiveQty = $currentStock + 10.00;

        $payload = [
            'waste_type' => 'DAMAGED',
            'disposal_method' => 'Discarded / Trashed',
            'reported_by' => 'Chef Marco',
            'waste_date' => now()->format('Y-m-d'),
            'force_override' => true,
            'items' => [
                [
                    'inventory_item_id' => $this->testItem->id,
                    'quantity' => $excessiveQty,
                    'reason_code' => 'Physical Handling Damage',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson(route('inventory.api.create-waste-record'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Stock goes to 0 (floor at 0)
        $this->testItem->refresh();
        $this->assertEquals(0, (float) $this->testItem->current_stock);
    }

    /**
     * Test 6: Cancelling a waste record restores on-hand stock and logs reversal
     */
    public function test_cancelling_waste_record_restores_stock(): void
    {
        $this->testItem->current_stock = 40.00;
        $this->testItem->save();

        // Create waste record
        $payload = [
            'waste_type' => 'DEFECTIVE',
            'disposal_method' => 'Discarded',
            'reported_by' => 'Staff A',
            'waste_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->testItem->id,
                    'quantity' => 10.00,
                    'reason_code' => 'Packaging Defect',
                ],
            ],
        ];

        $createRes = $this->actingAs($this->user)->postJson(route('inventory.api.create-waste-record'), $payload);
        $createRes->assertStatus(200);
        $recordId = $createRes->json('data.wasteRecord.id');

        $this->testItem->refresh();
        $this->assertEquals(30.00, (float) $this->testItem->current_stock);

        // Now cancel it
        $cancelRes = $this->actingAs($this->user)->postJson(route('inventory.api.update-waste-status'), [
            'waste_record_id' => $recordId,
            'status' => 'CANCELLED',
            'notes' => 'Auditor mistake, items were intact',
        ]);

        $cancelRes->assertStatus(200);
        $cancelRes->assertJsonPath('success', true);

        // Stock restored back to 40
        $this->testItem->refresh();
        $this->assertEquals(40.00, (float) $this->testItem->current_stock);

        // Reversal logged in Stock Ledger
        $this->assertDatabaseHas('stock_ledger', [
            'sku' => $this->testItem->sku,
            'transaction_type' => 'WASTE_REVERSAL',
            'reference_id' => $recordId,
            'quantity_change' => 10.00,
        ]);
    }

    /**
     * Test 7: Stocks Overview API synchronization incorporates waste metrics
     */
    public function test_stocks_overview_synchronizes_waste_metrics(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('inventory.api.stocks-overview-data'));

        $response->assertStatus(200);
        $products = $response->json('data.products');
        $this->assertIsArray($products);

        $matched = collect($products)->firstWhere('sku', $this->testItem->sku);
        $this->assertNotNull($matched);
        $this->assertArrayHasKey('waste_total', $matched);
        $this->assertArrayHasKey('waste_expired', $matched);
        $this->assertArrayHasKey('waste_damaged', $matched);
    }
}
