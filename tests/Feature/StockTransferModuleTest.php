<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\BranchStockBalance;
use App\Models\Inventory\InternalTransfer;
use App\Models\Inventory\InternalTransferItem;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockTransferModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $sourceBranch;
    protected Branch $destBranch;
    protected User $user;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'company_code' => 'COMP-TRF-01',
            'company_legal_name' => 'RMS Commissary Holdings Inc.',
            'company_trade_name' => 'RMS Holdings',
            'company_is_active' => true,
        ]);

        $this->sourceBranch = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'BR-COMM',
            'branch_name' => 'Central Commissary Main',
            'branch_type' => 'Commissary',
            'branch_is_active' => true,
        ]);

        $this->destBranch = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'BR-BGC',
            'branch_name' => 'BGC Retail Kitchen',
            'branch_type' => 'Store / Dine-In',
            'branch_is_active' => true,
        ]);

        $this->user = User::create([
            'username' => 'transfer.supervisor',
            'full_name' => 'Transfer Operations Lead',
            'email' => 'transfer@rms-test.com',
            'password' => bcrypt('password123'),
            'role' => 'Admin',
            'status' => 'Active',
            'default_company_id' => $this->company->company_id,
            'default_branch_id' => $this->sourceBranch->branch_id,
        ]);

        $this->item = InventoryItem::create([
            'sku' => 'TRF-BEEF-01',
            'barcode' => '4800011223344',
            'name' => 'Wagyu Ribeye Slab',
            'category' => 'Proteins & Meat',
            'uom' => 'Kg',
            'cost_price' => 450.00,
            'selling_price' => 750.00,
            'current_stock' => 105.00,
            'min_stock' => 15.00,
            'max_stock' => 50.00,
            'storage_location' => 'Cold Chiller A',
            'is_active' => true,
        ]);

        BranchStockBalance::create([
            'company_id' => $this->company->company_id,
            'branch_id' => $this->sourceBranch->branch_id,
            'inventory_item_id' => $this->item->id,
            'sku' => $this->item->sku,
            'on_hand_qty' => 100.0000,
            'reserved_qty' => 0.0000,
            'avg_unit_cost' => 450.0000,
        ]);

        BranchStockBalance::create([
            'company_id' => $this->company->company_id,
            'branch_id' => $this->destBranch->branch_id,
            'inventory_item_id' => $this->item->id,
            'sku' => $this->item->sku,
            'on_hand_qty' => 5.0000,
            'reserved_qty' => 0.0000,
            'avg_unit_cost' => 450.0000,
        ]);
    }

    protected function actingAsConsolidated(): self
    {
        return $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->sourceBranch->branch_id,
            'is_consolidated_view' => true,
        ]);
    }

    protected function actingAsBranch(Branch $branch): self
    {
        return $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $branch->branch_id,
            'is_consolidated_view' => false,
        ]);
    }

    public function test_internal_transfer_page_renders_with_stock_in_theme_and_paginated_payload(): void
    {
        $res = $this->actingAsConsolidated()->get(route('inventory.internal-transfer'));

        $res->assertStatus(200);
        $res->assertSee('id="trfAppRoot"', false);
        $res->assertSee('Stock Transfer &amp; Branch Requests', false);
        $res->assertSee('Request Stock');
        $res->assertSee('Direct Transfer');
    }

    public function test_branch_request_full_pull_lifecycle_conserves_inventory_mass(): void
    {
        // 1. Destination Branch submits Stock Request (Pull) for 20 Kg
        $reqRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'priority' => 'URGENT',
            'reason_code' => 'Branch Replenishment Request',
            'idempotency_key' => (string) Str::uuid(),
            'items' => [
                ['sku' => $this->item->sku, 'requested_qty' => 20],
            ],
        ]);

        $reqRes->assertStatus(201)->assertJsonPath('success', true);
        $transferId = (int) $reqRes->json('data.transfer.id');
        $this->assertEquals('REQUESTED', $reqRes->json('data.transfer.status'));
        $this->assertStringStartsWith('REQ-TRF-', (string) $reqRes->json('data.transfer.transfer_number'));

        // 2. Source Branch Approves (Reserves 20 Kg; on-hand unchanged)
        $appRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.approve-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'approved_qty' => 20]],
        ]);
        $appRes->assertStatus(200)->assertJsonPath('data.transfer.status', 'APPROVED');

        $srcBal = BranchStockBalance::where('branch_id', $this->sourceBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(100.0, (float) $srcBal->on_hand_qty);
        $this->assertEquals(20.0, (float) $srcBal->reserved_qty);
        $this->assertEquals(80.0, (float) $srcBal->available_qty);

        // 3. Source Branch Dispatches -> IN_TRANSIT (Deducts 20, clears reserve, sets in_transit, logs TRANSFER_OUT)
        $dispRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.dispatch-transfer'), [
            'transfer_id' => $transferId,
            'carrier_name' => 'Cold Van 1',
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 20]],
        ]);
        $dispRes->assertStatus(200)->assertJsonPath('data.transfer.status', 'IN_TRANSIT');

        $srcBal->refresh();
        $dstBal = BranchStockBalance::where('branch_id', $this->destBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(80.0, (float) $srcBal->on_hand_qty);
        $this->assertEquals(0.0, (float) $srcBal->reserved_qty);
        $this->assertEquals(20.0, (float) $srcBal->in_transit_out_qty);
        $this->assertEquals(20.0, (float) $dstBal->in_transit_in_qty);
        $this->assertEquals(5.0, (float) $dstBal->on_hand_qty);

        $this->assertDatabaseHas('inventory_ledger', [
            'branch_id' => $this->sourceBranch->branch_id,
            'transaction_type' => 'TRANSFER_OUT',
            'quantity_change' => -20.00,
            'reference_id' => $transferId,
        ]);

        // 4. Destination Branch Accepts Receipt -> COMPLETED (Credits 20 to dest, clears transit, logs TRANSFER_IN)
        $recRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.receive-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'received_qty' => 20, 'variance_qty' => 0]],
        ]);
        $recRes->assertStatus(200)->assertJsonPath('data.transfer.status', 'COMPLETED');
        $this->assertStringStartsWith('TRF-REC-', (string) $recRes->json('data.transfer.receipt_number'));

        $srcBal->refresh();
        $dstBal->refresh();
        $this->item->refresh();

        $this->assertEquals(80.0, (float) $srcBal->on_hand_qty);
        $this->assertEquals(0.0, (float) $srcBal->in_transit_out_qty);
        $this->assertEquals(25.0, (float) $dstBal->on_hand_qty);
        $this->assertEquals(0.0, (float) $dstBal->in_transit_in_qty);
        // Total mass conserved: 80 + 25 = 105
        $this->assertEquals(105.0, (float) $srcBal->on_hand_qty + (float) $dstBal->on_hand_qty);
        $this->assertEquals(105.0, (float) $this->item->current_stock);

        $this->assertDatabaseHas('inventory_ledger', [
            'branch_id' => $this->destBranch->branch_id,
            'transaction_type' => 'TRANSFER_IN',
            'quantity_change' => 20.00,
            'reference_id' => $transferId,
        ]);
    }

    public function test_source_approve_and_dispatch_now_stops_at_in_transit_requiring_destination_receipt(): void
    {
        $reqRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 10]],
        ]);
        $transferId = (int) $reqRes->json('data.transfer.id');

        $quickRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.approve-transfer'), [
            'transfer_id' => $transferId,
            'dispatch_immediately' => true,
            'items' => [['sku' => $this->item->sku, 'approved_qty' => 10]],
        ]);

        $quickRes->assertStatus(200)->assertJsonPath('data.transfer.status', 'IN_TRANSIT');
        $dstBal = BranchStockBalance::where('branch_id', $this->destBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(5.0, (float) $dstBal->on_hand_qty);
        $this->assertEquals(10.0, (float) $dstBal->in_transit_in_qty);
    }

    public function test_custody_guard_blocks_wrong_branch_from_dispatching_or_receiving(): void
    {
        $reqRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 10]],
        ]);
        $transferId = (int) $reqRes->json('data.transfer.id');

        // Destination cannot approve/dispatch
        $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.approve-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'approved_qty' => 10]],
        ])->assertStatus(403);

        // Source dispatches
        $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.dispatch-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 10]],
        ])->assertStatus(200);

        // Source cannot accept receipt on behalf of destination
        $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.receive-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'received_qty' => 10]],
        ])->assertStatus(403);

        // Destination accepts receipt
        $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.receive-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'received_qty' => 10]],
        ])->assertStatus(200)->assertJsonPath('data.transfer.status', 'COMPLETED');
    }

    public function test_direct_push_transfer_deducts_source_and_requires_destination_receipt(): void
    {
        $pushRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.create-custom-transfer'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'reason_code' => 'Commissary Bulk Batch Push',
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 15]],
        ]);

        $pushRes->assertStatus(201)->assertJsonPath('data.transfer.status', 'IN_TRANSIT');
        $transferId = (int) $pushRes->json('data.transfer.id');

        $srcBal = BranchStockBalance::where('branch_id', $this->sourceBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(85.0, (float) $srcBal->on_hand_qty);
        $this->assertEquals(15.0, (float) $srcBal->in_transit_out_qty);

        // Destination receives
        $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.receive-transfer'), [
            'transfer_id' => $transferId,
        ])->assertStatus(200)->assertJsonPath('data.transfer.status', 'COMPLETED');

        $dstBal = BranchStockBalance::where('branch_id', $this->destBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(20.0, (float) $dstBal->on_hand_qty);
    }

    public function test_partial_dispatch_creates_child_backorder_request(): void
    {
        $reqRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 50]],
        ]);
        $transferId = (int) $reqRes->json('data.transfer.id');

        $dispRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.dispatch-transfer'), [
            'transfer_id' => $transferId,
            'create_backorder' => true,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 30]],
        ]);

        $dispRes->assertStatus(200)
            ->assertJsonPath('data.transfer.status', 'IN_TRANSIT')
            ->assertJsonPath('data.backorder.transfer_type', 'BACKORDER_REQUEST')
            ->assertJsonPath('data.backorder.status', 'REQUESTED');

        $this->assertEquals(20.0, (float) $dispRes->json('data.backorder.total_requested_qty'));
        $this->assertEquals($transferId, (int) $dispRes->json('data.backorder.parent_transfer_id'));
    }

    public function test_receiving_with_shortage_logs_transfer_variance_and_marks_discrepancy(): void
    {
        $pushRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.create-custom-transfer'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 30]],
        ]);
        $transferId = (int) $pushRes->json('data.transfer.id');

        $recRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.receive-transfer'), [
            'transfer_id' => $transferId,
            'items' => [[
                'sku' => $this->item->sku,
                'received_qty' => 27,
                'variance_qty' => 3,
                'variance_reason' => 'DAMAGED_IN_TRANSIT',
            ]],
        ]);

        $recRes->assertStatus(200)
            ->assertJsonPath('data.transfer.status', 'DISCREPANCY')
            ->assertJsonPath('data.transfer.has_discrepancy', true);

        $dstBal = BranchStockBalance::where('branch_id', $this->destBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $srcBal = BranchStockBalance::where('branch_id', $this->sourceBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(32.0, (float) $dstBal->on_hand_qty); // 5 + 27
        $this->assertEquals(0.0, (float) $dstBal->in_transit_in_qty);
        $this->assertEquals(0.0, (float) $srcBal->in_transit_out_qty);

        $this->assertDatabaseHas('inventory_ledger', [
            'branch_id' => $this->destBranch->branch_id,
            'transaction_type' => 'TRANSFER_VARIANCE',
            'quantity_change' => -3.00,
            'reference_id' => $transferId,
        ]);
    }

    public function test_cancelling_approved_transfer_releases_reserved_stock_and_blocks_in_transit(): void
    {
        $reqRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 25]],
        ]);
        $transferId = (int) $reqRes->json('data.transfer.id');

        $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.approve-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'approved_qty' => 25]],
        ])->assertStatus(200);

        $this->actingAsConsolidated()->postJson(route('inventory.api.cancel-transfer'), [
            'transfer_id' => $transferId,
            'reason' => 'No longer needed',
        ])->assertStatus(200)->assertJsonPath('data.transfer.status', 'CANCELLED');

        $srcBal = BranchStockBalance::where('branch_id', $this->sourceBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(0.0, (float) $srcBal->reserved_qty);
        $this->assertEquals(100.0, (float) $srcBal->available_qty);

        // Cannot cancel an IN_TRANSIT push
        $pushRes = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.create-custom-transfer'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 10]],
        ]);
        $pushId = (int) $pushRes->json('data.transfer.id');

        $this->actingAsConsolidated()->postJson(route('inventory.api.cancel-transfer'), [
            'transfer_id' => $pushId,
            'reason' => 'Attempt cancel',
        ])->assertStatus(422);
    }

    public function test_boundary_guards_block_self_transfer_insufficient_stock_and_idempotent_replay(): void
    {
        // 1. Self-transfer blocked
        $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->destBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 5]],
        ])->assertStatus(422);

        // 2. Insufficient available stock blocked (even accounting for reserved_qty)
        $reqRes = $this->actingAsBranch($this->destBranch)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 70]],
        ]);
        $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.approve-transfer'), [
            'transfer_id' => (int) $reqRes->json('data.transfer.id'),
            'items' => [['sku' => $this->item->sku, 'approved_qty' => 70]],
        ])->assertStatus(200);

        // Only 30 available now; pushing 40 must fail
        $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.create-custom-transfer'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 40]],
        ])->assertStatus(422);

        // 3. Idempotent replay on Direct Push returns existing transfer without double-deducting
        $idemKey = 'idem-push-test-' . Str::uuid();
        $first = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.create-custom-transfer'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'idempotency_key' => $idemKey,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 10]],
        ])->assertStatus(201);

        $second = $this->actingAsBranch($this->sourceBranch)->postJson(route('inventory.api.create-custom-transfer'), [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
            'idempotency_key' => $idemKey,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 10]],
        ])->assertStatus(201);

        $this->assertEquals($first->json('data.transfer.id'), $second->json('data.transfer.id'));
        $srcBal = BranchStockBalance::where('branch_id', $this->sourceBranch->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(90.0, (float) $srcBal->on_hand_qty); // Deducted only once
    }

    public function test_register_endpoint_uses_bounded_queries_without_n_plus_one(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $t = InternalTransfer::create([
                'company_id' => $this->company->company_id,
                'transfer_number' => sprintf('REQ-TRF-2026-%04d', 900 + $i),
                'transfer_type' => 'BRANCH_REQUEST',
                'status' => 'REQUESTED',
                'priority' => 'NORMAL',
                'source_location' => $this->sourceBranch->branch_name,
                'source_branch_id' => $this->sourceBranch->branch_id,
                'destination_location' => $this->destBranch->branch_name,
                'destination_branch_id' => $this->destBranch->branch_id,
                'total_items_count' => 1,
                'total_requested_qty' => 5,
                'total_valuation' => 2250,
            ]);
            InternalTransferItem::create([
                'internal_transfer_id' => $t->id,
                'inventory_item_id' => $this->item->id,
                'sku' => $this->item->sku,
                'item_name' => $this->item->name,
                'uom' => 'Kg',
                'requested_qty' => 5,
                'unit_cost' => 450,
                'total_cost' => 2250,
            ]);
        }

        $this->actingAsConsolidated();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $res = $this->getJson(route('inventory.api.internal-transfer-data'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $res->assertStatus(200)->assertJsonPath('data.pagination.total', 15);
        $this->assertLessThanOrEqual(10, count($queries), 'Register endpoint exceeded bounded query budget.');
    }

    public function test_low_stock_suggestions_and_replenishment_draft_compatibility(): void
    {
        // Destination has 5 Kg vs min_stock 15 Kg, Source has 100 Kg -> suggests pull
        $sugRes = $this->actingAsConsolidated()->getJson(route('inventory.api.transfer-low-stock', [
            'source_branch_id' => $this->sourceBranch->branch_id,
            'destination_branch_id' => $this->destBranch->branch_id,
        ]));
        $sugRes->assertStatus(200);
        $this->assertCount(1, $sugRes->json('data.suggestions'));
        $this->assertEquals(45.0, (float) $sugRes->json('data.suggestions.0.suggested_qty')); // max_stock 50 - 5

        // ReplenishmentEngine-style draft with null source_branch_id can be approved by passing source_branch_id
        $draft = InternalTransfer::create([
            'transfer_number' => 'TRF-AUTO-TEST01',
            'transfer_type' => 'COMMISSARY_REPLENISHMENT',
            'status' => 'DRAFT',
            'priority' => 'HIGH',
            'source_location' => 'Central Commissary',
            'source_branch_id' => null,
            'destination_location' => $this->destBranch->branch_name,
            'destination_branch_id' => $this->destBranch->branch_id,
            'total_items_count' => 1,
            'total_requested_qty' => 12,
            'total_valuation' => 5400,
        ]);
        InternalTransferItem::create([
            'internal_transfer_id' => $draft->id,
            'inventory_item_id' => $this->item->id,
            'sku' => $this->item->sku,
            'item_name' => $this->item->name,
            'uom' => 'Kg',
            'requested_qty' => 12,
            'unit_cost' => 450,
            'total_cost' => 5400,
        ]);

        $this->actingAsConsolidated()->postJson(route('inventory.api.approve-transfer'), [
            'transfer_id' => $draft->id,
            'source_branch_id' => $this->sourceBranch->branch_id,
            'items' => [['sku' => $this->item->sku, 'approved_qty' => 12]],
        ])->assertStatus(200)->assertJsonPath('data.transfer.status', 'APPROVED');
    }
}
