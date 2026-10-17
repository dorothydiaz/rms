<?php

namespace Tests\Feature;

use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqAlternativeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $vendorA;
    protected $vendorB;
    protected $vendorC;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->user = User::first();

        // Ensure vendors exist
        $this->vendorA = ProcurementVendor::firstOrCreate(
            ['vendor_code' => 'VND-SAN-002'],
            ['legal_name' => 'San Miguel Pure Foods Company Inc.', 'trade_name' => 'San Miguel Foods', 'is_active' => true]
        );
        $this->vendorB = ProcurementVendor::firstOrCreate(
            ['vendor_code' => 'VND-ROB-003'],
            ['legal_name' => 'Universal Robina Corporation', 'trade_name' => 'URC Agro-Industrial', 'is_active' => true]
        );
        $this->vendorC = ProcurementVendor::firstOrCreate(
            ['vendor_code' => 'VND-CEN-004'],
            ['legal_name' => 'Century Pacific Food Inc.', 'trade_name' => 'Century Pacific', 'is_active' => true]
        );
    }

    private function createBaseRfqWithItems(int $itemCount = 2): RfqHeader
    {
        $baseRfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => 'RFQ-BASE-' . strtoupper(Str::random(6)),
            'vendor_id' => $this->vendorA->id,
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'DRAFT',
            'rfq_approval_status' => 'PENDING',
            'rfq_currency_code' => 'PHP',
            'rfq_estimated_total_amount' => 5000.00,
        ]);

        for ($i = 1; $i <= $itemCount; $i++) {
            RfqLine::create([
                'rfq_line_id' => (string) Str::uuid(),
                'rfq_id' => $baseRfq->rfq_id,
                'line_number' => $i,
                'item_id' => 'ITEM-00' . $i,
                'requested_quantity' => 10 * $i,
                'purchase_uom_code' => 'KG',
                'target_unit_price' => 100.00,
                'quoted_unit_price' => 0.00,
                'quoted_line_total' => 0.00,
                'lead_time_days' => 0,
                'is_awarded' => false,
            ]);
        }

        return $baseRfq;
    }

    public function test_can_create_draft_rfq_alternatives_with_cloned_lines()
    {
        $baseRfq = $this->createBaseRfqWithItems(3);

        $payload = [
            'base_rfq_number' => $baseRfq->rfq_number,
            'vendor_ids' => [$this->vendorB->id, $this->vendorC->id],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify base RFQ was assigned rfq_group_id
        $baseRfq->refresh();
        $this->assertNotNull($baseRfq->rfq_group_id);
        $this->assertEquals('A', $baseRfq->alternative_label);

        // Verify siblings in database
        $siblings = RfqHeader::where('rfq_group_id', $baseRfq->rfq_group_id)
            ->where('is_alternative', true)
            ->get();

        $this->assertCount(2, $siblings);

        foreach ($siblings as $sibling) {
            $this->assertEquals('DRAFT', $sibling->rfq_status);
            $this->assertEquals($baseRfq->rfq_id, $sibling->parent_rfq_id);
            $this->assertCount(3, $sibling->lines);

            // Verify lines cloned with blank/zero quotes
            foreach ($sibling->lines as $line) {
                $this->assertEquals(0.00, (float) $line->quoted_unit_price);
                $this->assertNull($line->quoted_quantity);
                $this->assertFalse((bool) $line->is_awarded);
            }
        }
    }

    public function test_cannot_create_alternatives_for_empty_rfq()
    {
        $emptyRfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => 'RFQ-EMPTY-' . strtoupper(Str::random(6)),
            'vendor_id' => $this->vendorA->id,
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'DRAFT',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', [
                'base_rfq_number' => $emptyRfq->rfq_number,
                'vendor_ids' => [$this->vendorB->id],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_vendor_uniqueness_prevents_duplicate_vendor_in_same_group()
    {
        $baseRfq = $this->createBaseRfqWithItems(1);

        // First creation with vendor B
        $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', [
                'base_rfq_number' => $baseRfq->rfq_number,
                'vendor_ids' => [$this->vendorB->id],
            ]);

        // Attempt second creation with base vendor A and already added vendor B
        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', [
                'base_rfq_number' => $baseRfq->rfq_number,
                'vendor_ids' => [$this->vendorA->id, $this->vendorB->id],
            ]);

        $response->assertStatus(200);
        // Data should have 0 new siblings created since both vendors were skipped as duplicates
        $response->assertJsonPath('data', []);
    }

    public function test_comparison_matrix_computes_lowest_price_and_fastest_lead_time()
    {
        $baseRfq = $this->createBaseRfqWithItems(2);

        // Create sibling RFQ for Vendor B
        $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', [
                'base_rfq_number' => $baseRfq->rfq_number,
                'vendor_ids' => [$this->vendorB->id],
            ]);

        $baseRfq->refresh();
        $sibling = RfqHeader::where('rfq_group_id', $baseRfq->rfq_group_id)
            ->where('is_alternative', true)
            ->first();

        // Vendor A quotes: Item 1 = ₱80 (3 days), Item 2 = ₱150 (5 days)
        $baseLines = $baseRfq->lines;
        $baseLines[0]->update(['quoted_unit_price' => 80.00, 'quoted_line_total' => 800.00, 'lead_time_days' => 3]);
        $baseLines[1]->update(['quoted_unit_price' => 150.00, 'quoted_line_total' => 3000.00, 'lead_time_days' => 5]);

        // Vendor B quotes: Item 1 = ₱95 (1 day), Item 2 = ₱120 (2 days)
        $siblingLines = $sibling->lines;
        $siblingLines[0]->update(['quoted_unit_price' => 95.00, 'quoted_line_total' => 950.00, 'lead_time_days' => 1]);
        $siblingLines[1]->update(['quoted_unit_price' => 120.00, 'quoted_line_total' => 2400.00, 'lead_time_days' => 2]);

        $response = $this->actingAs($this->user)
            ->getJson("/purchase/api/rfq/group/{$baseRfq->rfq_group_id}/bids");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $matrixItems = $response->json('data.matrix_items');
        $this->assertCount(2, $matrixItems);

        // Item 1: Vendor A has Lowest Price (₱80), Vendor B has Fastest Delivery (1 day)
        $item1Quotes = $matrixItems[0]['quotes'];
        $this->assertTrue($item1Quotes[$baseRfq->rfq_id]['is_lowest_price']);
        $this->assertFalse($item1Quotes[$sibling->rfq_id]['is_lowest_price']);
        $this->assertTrue($item1Quotes[$sibling->rfq_id]['is_fastest_delivery']);

        // Item 2: Vendor B has Lowest Price (₱120) and Fastest Delivery (2 days)
        $item2Quotes = $matrixItems[1]['quotes'];
        $this->assertTrue($item2Quotes[$sibling->rfq_id]['is_lowest_price']);
        $this->assertTrue($item2Quotes[$sibling->rfq_id]['is_fastest_delivery']);
    }

    public function test_full_award_creates_single_po_and_closes_losing_rfqs()
    {
        $baseRfq = $this->createBaseRfqWithItems(2);

        $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', [
                'base_rfq_number' => $baseRfq->rfq_number,
                'vendor_ids' => [$this->vendorB->id],
            ]);

        $baseRfq->refresh();
        $sibling = RfqHeader::where('rfq_group_id', $baseRfq->rfq_group_id)
            ->where('is_alternative', true)
            ->first();

        // Award ALL items to Vendor B
        $payload = [
            'rfq_group_id' => $baseRfq->rfq_group_id,
            'award_type' => 'FULL',
            'awards' => [
                ['item_id' => 'ITEM-001', 'rfq_id' => $sibling->rfq_id],
                ['item_id' => 'ITEM-002', 'rfq_id' => $sibling->rfq_id],
            ],
            'po_status' => 'Approved / Issued',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/group/award', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.total_awarded_pos', 1);

        // Verify Sibling RFQ is AWARDED and linked to PO
        $sibling->refresh();
        $this->assertEquals('AWARDED', $sibling->rfq_status);
        $this->assertNotNull($sibling->purchase_order_id);

        // Verify Base RFQ is CLOSED
        $baseRfq->refresh();
        $this->assertEquals('CLOSED', $baseRfq->rfq_status);

        // Verify Purchase Order exists in database
        $this->assertDatabaseHas('purchase_order_header', [
            'id' => $sibling->purchase_order_id,
            'status' => 'Approved / Issued',
        ]);
        $this->assertEquals(2, PurchaseOrderItem::where('purchase_order_id', $sibling->purchase_order_id)->count());
    }

    public function test_split_award_creates_multiple_pos_per_winning_vendor()
    {
        $baseRfq = $this->createBaseRfqWithItems(2);

        $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/create-alternatives', [
                'base_rfq_number' => $baseRfq->rfq_number,
                'vendor_ids' => [$this->vendorB->id],
            ]);

        $baseRfq->refresh();
        $sibling = RfqHeader::where('rfq_group_id', $baseRfq->rfq_group_id)
            ->where('is_alternative', true)
            ->first();

        // Split Award: Item 1 to Base Vendor A, Item 2 to Sibling Vendor B
        $payload = [
            'rfq_group_id' => $baseRfq->rfq_group_id,
            'award_type' => 'SPLIT',
            'awards' => [
                ['item_id' => 'ITEM-001', 'rfq_id' => $baseRfq->rfq_id],
                ['item_id' => 'ITEM-002', 'rfq_id' => $sibling->rfq_id],
            ],
            'po_status' => 'Approved / Issued',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/group/award', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.total_awarded_pos', 2);

        $baseRfq->refresh();
        $sibling->refresh();

        $this->assertEquals('AWARDED', $baseRfq->rfq_status);
        $this->assertEquals('AWARDED', $sibling->rfq_status);

        // Verify each PO has exactly 1 item
        $this->assertEquals(1, PurchaseOrderItem::where('purchase_order_id', $baseRfq->purchase_order_id)->count());
        $this->assertEquals(1, PurchaseOrderItem::where('purchase_order_id', $sibling->purchase_order_id)->count());

        // Verify line 1 is awarded on base, line 2 is awarded on sibling
        $this->assertTrue((bool) RfqLine::where('rfq_id', $baseRfq->rfq_id)->where('item_id', 'ITEM-001')->first()->is_awarded);
        $this->assertFalse((bool) RfqLine::where('rfq_id', $baseRfq->rfq_id)->where('item_id', 'ITEM-002')->first()->is_awarded);
        $this->assertFalse((bool) RfqLine::where('rfq_id', $sibling->rfq_id)->where('item_id', 'ITEM-001')->first()->is_awarded);
        $this->assertTrue((bool) RfqLine::where('rfq_id', $sibling->rfq_id)->where('item_id', 'ITEM-002')->first()->is_awarded);
    }
}
