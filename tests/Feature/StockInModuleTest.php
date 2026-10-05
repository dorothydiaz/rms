<?php

namespace Tests\Feature;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\StockLedger;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\User;
use Tests\TestCase;

class StockInModuleTest extends TestCase
{
    protected User $user;
    protected InventoryItem $testProduct;
    protected ProcurementVendor $testVendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['username' => 'warehouse.receiver'],
            [
                'full_name' => 'Warehouse Logistics Supervisor',
                'email' => 'dock.receiver@rms-test.com',
                'password' => bcrypt('password123'),
                'role' => 'Manager',
                'status' => 'Active',
            ]
        );

        $cat = InventoryCategory::firstOrCreate(
            ['slug' => 'test-dairy-goods'],
            [
                'name' => 'Test Dairy Goods',
                'code' => 'TDG',
                'badge_color' => 'purple',
            ]
        );

        $this->testProduct = InventoryItem::firstOrCreate(
            ['sku' => 'TEST-MILK-01'],
            [
                'name' => 'Barista Whole Fresh Milk 1L',
                'category' => 'Test Dairy Goods',
                'uom' => 'Liter',
                'cost_price' => 85.00,
                'selling_price' => 120.00,
                'current_stock' => 50.00,
                'min_stock' => 10.00,
                'max_stock' => 100.00,
                'storage_location' => 'Central Commissary - Cold Chiller',
                'is_active' => true,
            ]
        );

        $this->testVendor = ProcurementVendor::firstOrCreate(
            ['vendor_code' => 'VND-TEST-DAIRY'],
            [
                'legal_name' => 'Fresh Farm Dairy Corp.',
                'trade_name' => 'Fresh Farm Dairy',
                'category' => 'Dairy & Eggs',
                'contact_person' => 'Juan Dela Cruz',
                'email' => 'dairy@freshfarm.test',
                'phone' => '+63 917 123 4567',
                'is_active' => true,
            ]
        );
    }

    public function test_stock_in_page_renders_with_hydrated_data_and_cross_module_links(): void
    {
        $response = $this->actingAs($this->user)->get(route('inventory.stock-in'));

        $response->assertStatus(200);
        $response->assertSee('Stock In / Receiving (GRN)');
        $response->assertSee('Stocks Overview');
        $response->assertSee('Item Master');
        $response->assertSee('Purchase Orders');
    }

    public function test_can_get_stock_in_data_via_api(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('inventory.api.stock-in-data'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'purchaseOrders',
                'vendors',
                'products',
                'goodsReceipts',
            ]
        ]);
    }

    public function test_can_receive_direct_stock_and_update_ledger(): void
    {
        $this->testProduct->update(['current_stock' => 50.00]);
        $initialStock = 50.00;
        $qtyToReceive = 20.00;
        $unitCost = 88.00;
        $slipNo = 'DR-' . uniqid();

        $payload = [
            'vendor_name' => $this->testVendor->trade_name,
            'vendor_id' => $this->testVendor->vendor_code,
            'delivery_slip' => $slipNo,
            'carrier' => 'Lalamove Express',
            'delivery_location' => 'Central Commissary - Cold Chiller',
            'reason_code' => 'Direct Spot Purchase',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'name' => $this->testProduct->name,
                    'unit' => $this->testProduct->uom,
                    'uom_multiplier' => 1.0,
                    'received_qty' => $qtyToReceive,
                    'unit_cost' => $unitCost,
                    'lot_number' => 'LOT-DIRECT-01',
                    'notes' => 'Spot direct receiving test',
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson(route('inventory.api.receive-stock'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $freshProduct = $this->testProduct->fresh();
        $this->assertEquals($initialStock + $qtyToReceive, (float) $freshProduct->current_stock);
        $this->assertEquals($unitCost, (float) $freshProduct->cost_price);

        // Verify GoodsReceipt created
        $grn = GoodsReceipt::where('delivery_slip_no', $slipNo)->first();
        $this->assertNotNull($grn);
        $this->assertEquals('Direct inbound stock receiving', $grn->notes);

        // Verify StockLedger entry
        $ledger = StockLedger::where('reference_no', $grn->grnNumber ?? $grn->grn_number)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals('DIRECT_RECEIVING', $ledger->transaction_type);
        $this->assertEquals($qtyToReceive, (float) $ledger->quantity_change);
        $this->assertEquals($initialStock + $qtyToReceive, (float) $ledger->after_quantity);
    }

    public function test_can_fulfill_purchase_order_and_update_po_status(): void
    {
        $poNumber = 'PO-TEST-' . uniqid();
        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'po_type' => 'vendor',
            'vendor_id' => $this->testVendor->vendor_code,
            'vendor_name' => $this->testVendor->legal_name,
            'vendor_trade_name' => $this->testVendor->trade_name,
            'order_date' => now()->toDateString(),
            'expected_delivery' => now()->addDays(2)->toDateString(),
            'delivery_location' => 'Central Commissary - Cold Chiller',
            'status' => 'Approved / Issued',
            'gross_total' => 850.00,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'sku' => $this->testProduct->sku,
            'item_name' => $this->testProduct->name,
            'uom' => $this->testProduct->uom,
            'quantity' => 10.00,
            'unit_price' => 85.00,
            'total_amount' => 850.00,
            'received_quantity' => 0.00,
            'status' => 'PENDING',
        ]);

        $payload = [
            'po_number' => $poNumber,
            'vendor_name' => $this->testVendor->trade_name,
            'vendor_id' => $this->testVendor->vendor_code,
            'delivery_slip' => 'DR-PO-TEST-01',
            'delivery_location' => 'Central Commissary - Cold Chiller',
            'reason_code' => 'Standard PO Delivery',
            'items' => [
                [
                    'sku' => $this->testProduct->sku,
                    'name' => $this->testProduct->name,
                    'unit' => $this->testProduct->uom,
                    'received_qty' => 10.00,
                    'unit_cost' => 85.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->postJson(route('inventory.api.receive-stock'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify PO item fulfilled
        $poItem->refresh();
        $this->assertEquals(10.00, (float) $poItem->received_quantity);
        $this->assertEquals('FULFILLED', $poItem->status);

        // Verify PO header status
        $po->refresh();
        $this->assertEquals('Fully Received', $po->status);

        // Verify GoodsReceipt notes contains correct po_number without null/undefined
        $grn = GoodsReceipt::where('po_number', $poNumber)->first();
        $this->assertNotNull($grn);
        $this->assertEquals("Fulfillment for PO {$poNumber}", $grn->notes);
    }
}
