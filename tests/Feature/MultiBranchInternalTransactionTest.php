<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\BranchStockBalance;
use App\Models\Inventory\InternalTransfer;
use App\Models\Inventory\InventoryItem;
use App\Models\ReorderingRule;
use App\Models\User;
use App\Services\CompanyBranchContext;
use App\Services\ReplenishmentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiBranchInternalTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $commissary;
    protected Branch $branchB;
    protected Branch $branchC;
    protected User $user;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'company_code' => 'COMP-MB-01',
            'company_legal_name' => 'Multi-Branch Foods Corp.',
            'company_trade_name' => 'Multi-Branch Foods',
            'company_is_active' => true,
        ]);

        $this->commissary = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'BR-COMM',
            'branch_name' => 'Central Commissary Hub',
            'branch_type' => 'Central Commissary',
            'branch_role' => 'CENTRAL_COMMISSARY',
            'can_supply_branches' => true,
            'allow_peer_transfers' => true,
            'internal_pricing_mode' => 'COST_PLUS_PCT',
            'internal_markup_pct' => 10.0000,
            'auto_post_interbranch_gl' => true,
            'branch_is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'BR-MAKATI',
            'branch_name' => 'Makati Flagship Store',
            'branch_type' => 'Store / Dine-In',
            'branch_role' => 'RETAIL_STORE',
            'default_source_branch_id' => $this->commissary->branch_id,
            'can_supply_branches' => true,
            'allow_peer_transfers' => false,
            'internal_pricing_mode' => 'AT_COST',
            'internal_markup_pct' => 0.0000,
            'auto_post_interbranch_gl' => true,
            'branch_is_active' => true,
        ]);

        $this->branchC = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'BR-QC',
            'branch_name' => 'Quezon City Store',
            'branch_type' => 'Store / Dine-In',
            'branch_role' => 'RETAIL_STORE',
            'default_source_branch_id' => $this->commissary->branch_id,
            'can_supply_branches' => false,
            'allow_peer_transfers' => false,
            'branch_is_active' => true,
        ]);

        $this->user = User::create([
            'username' => 'mb.admin',
            'full_name' => 'Multi-Branch Controller',
            'email' => 'mb@rms-test.com',
            'password' => bcrypt('password123'),
            'role' => 'Admin',
            'status' => 'Active',
            'default_company_id' => $this->company->company_id,
            'default_branch_id' => $this->commissary->branch_id,
        ]);

        $this->item = InventoryItem::create([
            'sku' => 'MB-CHKN-01',
            'name' => 'Marinated Chicken Thighs',
            'category' => 'Proteins',
            'uom' => 'Kg',
            'cost_price' => 500.00,
            'selling_price' => 800.00,
            'current_stock' => 105.00,
            'min_stock' => 15.00,
            'max_stock' => 45.00,
            'is_active' => true,
        ]);

        BranchStockBalance::create([
            'company_id' => $this->company->company_id,
            'branch_id' => $this->commissary->branch_id,
            'inventory_item_id' => $this->item->id,
            'sku' => $this->item->sku,
            'on_hand_qty' => 100.0000,
            'avg_unit_cost' => 500.0000,
        ]);

        BranchStockBalance::create([
            'company_id' => $this->company->company_id,
            'branch_id' => $this->branchB->branch_id,
            'inventory_item_id' => $this->item->id,
            'sku' => $this->item->sku,
            'on_hand_qty' => 5.0000,
            'avg_unit_cost' => 500.0000,
        ]);
    }

    public function test_ticking_branch_or_company_in_top_ribbon_switches_active_transaction_context(): void
    {
        $res = $this->actingAs($this->user)->postJson(route('context.scope'), [
            'company_ids' => [$this->company->company_id],
            'branch_ids' => [$this->commissary->branch_id, $this->branchB->branch_id],
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->branchB->branch_id,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('active_branch_id', $this->branchB->branch_id)
            ->assertJsonPath('active_company_id', $this->company->company_id)
            ->assertJsonPath('is_consolidated', false);

        $ctx = app(CompanyBranchContext::class);
        $this->assertEquals($this->branchB->branch_id, $ctx->getActiveBranch()->branch_id);
        $this->assertEquals([$this->commissary->branch_id, $this->branchB->branch_id], $ctx->getCheckedBranchIds());
    }

    public function test_branch_trading_policies_enforce_supply_capability_and_peer_transfer_rules(): void
    {
        // 1. Branch C has can_supply_branches = false -> blocked from supplying Branch B
        $this->actingAs($this->user)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->branchC->branch_id,
            'destination_branch_id' => $this->branchB->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 5]],
        ])->assertStatus(422);

        // 2. Branch B -> Branch C (both RETAIL_STORE, allow_peer_transfers = false) -> blocked
        $this->actingAs($this->user)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->branchB->branch_id,
            'destination_branch_id' => $this->branchC->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 2]],
        ])->assertStatus(422);

        // 3. Commissary -> Branch B succeeds
        $this->actingAs($this->user)->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->commissary->branch_id,
            'destination_branch_id' => $this->branchB->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 10]],
        ])->assertStatus(201);
    }

    public function test_cost_plus_internal_transfer_computes_markup_and_auto_posts_balanced_interbranch_gl_journals(): void
    {
        // 1. Branch B requests 10 Kg from Commissary (10% markup -> 550/Kg = 5,500 billed vs 5,000 base cost)
        $reqRes = $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->branchB->branch_id,
            'is_consolidated_view' => false,
        ])->postJson(route('inventory.api.create-transfer-request'), [
            'source_branch_id' => $this->commissary->branch_id,
            'destination_branch_id' => $this->branchB->branch_id,
            'items' => [['sku' => $this->item->sku, 'requested_qty' => 10]],
        ]);

        $reqRes->assertStatus(201)
            ->assertJsonPath('data.transfer.pricing_mode', 'COST_PLUS_PCT');
        $this->assertEquals(5500.0, (float) $reqRes->json('data.transfer.total_internal_billed'));
        $transferId = (int) $reqRes->json('data.transfer.id');

        // 2. Commissary dispatches -> posts IB-OUT GL Journal (DR 1230 In-Transit 5000 / CR 1210 Inventory 5000)
        $dispRes = $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->commissary->branch_id,
            'is_consolidated_view' => false,
        ])->postJson(route('inventory.api.dispatch-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'transferred_qty' => 10]],
        ]);
        $dispRes->assertStatus(200);
        $dispatchJournalId = $dispRes->json('data.transfer.dispatch_journal_id');
        $this->assertNotNull($dispatchJournalId);

        $dispJournal = GlJournalHeader::where('journal_id', $dispatchJournalId)->first();
        $this->assertEquals('POSTED', $dispJournal->status);
        $this->assertEquals(5000.0, (float) $dispJournal->total_debit);
        $this->assertEquals(5000.0, (float) $dispJournal->total_credit);

        // 3. Branch B accepts receipt -> posts IB-REC GL Journal (5500 Due To/Due From + 500 Inter-Branch Recovery)
        $recRes = $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->branchB->branch_id,
            'is_consolidated_view' => false,
        ])->postJson(route('inventory.api.receive-transfer'), [
            'transfer_id' => $transferId,
            'items' => [['sku' => $this->item->sku, 'received_qty' => 10]],
        ]);
        $recRes->assertStatus(200)
            ->assertJsonPath('data.transfer.settlement_status', 'POSTED_TO_GL');

        $receiptJournalId = $recRes->json('data.transfer.receipt_journal_id');
        $this->assertNotNull($receiptJournalId);
        $recJournal = GlJournalHeader::where('journal_id', $receiptJournalId)->first();
        $this->assertEquals('POSTED', $recJournal->status);
        $this->assertEquals(11000.0, (float) $recJournal->total_debit);
        $this->assertEquals(11000.0, (float) $recJournal->total_credit);

        // Verify 4030 Inter-Branch Recovery line = 500
        $recoveryLine = GlJournalLine::where('journal_id', $receiptJournalId)->where('account_code', '4030')->first();
        $this->assertNotNull($recoveryLine);
        $this->assertEquals(500.0, (float) $recoveryLine->transaction_credit);

        // 4. Settlement Matrix & 1-Click Settlement
        $matrixRes = $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->commissary->branch_id,
            'is_consolidated_view' => true,
        ])->getJson(route('inventory.api.transfer-settlement-matrix'));
        $matrixRes->assertStatus(200);
        $this->assertEquals(5500.0, (float) $matrixRes->json('data.totals.unsettled_amount'));

        $settleRes = $this->actingAs($this->user)->postJson(route('inventory.api.transfer-settle'), [
            'transfer_ids' => [$transferId],
        ]);
        $settleRes->assertStatus(200)
            ->assertJsonPath('data.settled_count', 1);
        $this->assertEquals(5500.0, (float) $settleRes->json('data.settled_amount'));
        $this->assertEquals('SETTLED', InternalTransfer::find($transferId)->settlement_status);
    }

    public function test_stocks_overview_and_operations_scope_to_active_and_checked_branches(): void
    {
        // Active Branch = Branch B (5 Kg)
        $resB = $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->branchB->branch_id,
            'checked_branch_ids' => [$this->branchB->branch_id],
            'is_consolidated_view' => false,
        ])->getJson(route('inventory.api.stocks-overview-data'));
        $resB->assertStatus(200);
        $this->assertEquals(5.0, (float) $resB->json('data.products.0.current_stock'));

        // Consolidated / Multi-Branch (100 + 5 = 105 Kg)
        $resCons = $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->commissary->branch_id,
            'is_consolidated_view' => true,
        ])->getJson(route('inventory.api.stocks-overview-data'));
        $resCons->assertStatus(200);
        $this->assertEquals(105.0, (float) $resCons->json('data.products.0.current_stock'));

        // Waste on Branch B deducts Branch B balance (5 - 2 = 3 Kg)
        $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->branchB->branch_id,
            'is_consolidated_view' => false,
        ])->postJson(route('inventory.api.create-waste-record'), [
            'waste_type' => 'SPOILAGE',
            'disposal_method' => 'Trash / Compost',
            'reported_by' => 'Branch B Chef',
            'waste_date' => now()->toDateString(),
            'items' => [[
                'inventory_item_id' => $this->item->id,
                'quantity' => 2,
                'reason_code' => 'Spoiled / Expired',
            ]],
        ])->assertStatus(200);

        $balB = BranchStockBalance::where('branch_id', $this->branchB->branch_id)->where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(3.0, (float) $balB->on_hand_qty);
    }

    public function test_replenishment_engine_uses_real_branch_stock_balances_and_default_source_branch(): void
    {
        $rule = ReorderingRule::create([
            'rule_code' => 'RULE-MB-01',
            'action_route' => 'internal_transfer',
            'branch_id' => $this->branchB->branch_id,
            'branch_name' => $this->branchB->branch_name,
            'product_id' => $this->item->id,
            'product_sku' => $this->item->sku,
            'product_name' => $this->item->name,
            'uom' => 'Kg',
            'min_stock' => 15.00,
            'max_stock' => 45.00,
            'safety_buffer' => 5.00,
            'moq' => 5.00,
            'lead_time_days' => 1,
            'source_warehouse_id' => 'WH-MAIN',
            'source_warehouse_name' => $this->commissary->branch_name,
            'is_active' => true,
        ]);

        /** @var ReplenishmentEngine $engine */
        $engine = app(ReplenishmentEngine::class);
        $sim = $engine->simulateRules([$rule->id], $this->branchB->branch_id);

        $this->assertCount(1, $sim['items']);
        // Uses real Branch B balance (5.00), NOT fake percentage of item_master (105 * 0.35)
        $this->assertEquals(5.0, (float) $sim['items'][0]['on_hand']);
        $this->assertEquals(40.0, (float) $sim['items'][0]['suggested_order_qty']);

        $batch = $engine->batchGenerateDrafts([$rule->id], 'System Auto');
        $this->assertCount(1, $batch['created_transfers']);

        $draft = InternalTransfer::find($batch['created_transfers'][0]['transfer_id']);
        $this->assertEquals($this->commissary->branch_id, $draft->source_branch_id);
        $this->assertEquals($this->branchB->branch_id, $draft->destination_branch_id);
    }
}
