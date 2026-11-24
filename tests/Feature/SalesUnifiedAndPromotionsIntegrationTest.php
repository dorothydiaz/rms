<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\AcctTaxCode;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\Inventory\InventoryItem;
use App\Models\Sales\SalesBundle;
use App\Models\Sales\SalesDiscount;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesVoucher;
use App\Models\User;
use App\Services\Accounting\AccountingArService;
use App\Services\Accounting\AccountingSeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesUnifiedAndPromotionsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AcctCustomer $customer;
    protected InventoryItem $itemA;
    protected InventoryItem $itemB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        app(AccountingSeedService::class)->ensureSeeded('1');

        $this->customer = AcctCustomer::create([
            'company_id' => '1',
            'customer_code' => 'CUST-UNIF-01',
            'customer_name' => 'Metro Hospitality Group',
            'trade_name' => 'Metro Hospitality',
            'tin' => '222-333-444-000',
            'address' => 'Makati Ave, Makati City',
            'shipping_address' => 'Makati Commissary Dock',
            'contact_person' => 'Marco Santos',
            'email' => 'marco@metrohosp.ph',
            'phone' => '+63 917 800 1000',
            'payment_terms_code' => 'NET_30',
            'credit_limit' => 250000.00,
            'current_balance' => 0.00,
            'credit_hold' => false,
            'is_active' => true,
        ]);

        $this->itemA = InventoryItem::create([
            'sku' => 'SKU-WAGYU-A',
            'name' => 'Wagyu Steak Platter',
            'category' => 'Finished Goods',
            'uom' => 'PCS',
            'cost_price' => 500.00,
            'selling_price' => 1200.00,
            'current_stock' => 50.00,
            'min_stock' => 5.00,
            'max_stock' => 200.00,
            'storage_location' => 'WH-MAIN',
            'is_active' => true,
        ]);

        $this->itemB = InventoryItem::create([
            'sku' => 'SKU-WINE-B',
            'name' => 'House Red Reserve',
            'category' => 'Beverages',
            'uom' => 'BOTTLE',
            'cost_price' => 300.00,
            'selling_price' => 800.00,
            'current_stock' => 30.00,
            'min_stock' => 5.00,
            'max_stock' => 100.00,
            'storage_location' => 'WH-MAIN',
            'is_active' => true,
        ]);
    }

    public function test_unified_quote_to_sales_order_credit_delivery_bill_and_collection(): void
    {
        // 1. Create Quote inside unified workspace
        $saveRes = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'QUOTE',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'NET_30',
            'valid_until_date' => now()->addDays(10)->toDateString(),
            'lines' => [[
                'inventory_item_id' => $this->itemA->id,
                'sku' => $this->itemA->sku,
                'item_name' => $this->itemA->name,
                'quantity' => 5,
                'uom' => 'PCS',
                'unit_price' => 1200.00,
                'tax_code' => 'VAT12_OUT',
            ]],
        ]);
        $saveRes->assertStatus(200)->assertJsonPath('data.status', 'QUOTE_DRAFT');
        $orderId = $saveRes->json('data.sales_order_id');
        $this->assertStringStartsWith('SQ-', $saveRes->json('data.order_number'));

        // 2. Transition Quote: QUOTE_SENT -> QUOTE_CONFIRMED
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/quote-stage", [
            'target_status' => 'QUOTE_SENT',
        ])->assertStatus(200)->assertJsonPath('data.status', 'QUOTE_SENT');

        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/quote-stage", [
            'target_status' => 'QUOTE_CONFIRMED',
        ])->assertStatus(200)->assertJsonPath('data.status', 'QUOTE_CONFIRMED');

        // 3. Promote to SO & Confirm -> enters CREDIT_HOLD (NET_30 strict review) with SLS- number
        $confirmRes = $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', [
            'sales_order_id' => $orderId,
        ]);
        $confirmRes->assertStatus(200)->assertJsonPath('data.status', 'CREDIT_HOLD');
        $this->assertStringStartsWith('SLS-', $confirmRes->json('data.order_number'));

        // 4. Approve Credit Review
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/credit-review", [
            'decision' => 'APPROVE',
            'notes' => 'Verified credit limit.',
        ])->assertStatus(200)->assertJsonPath('data.status', 'APPROVED');

        // 5. Ship Delivery -> creates DRAFT SI & posts COGS
        $orderLineId = SalesOrder::with('lines')->findOrFail($orderId)->lines->first()->sales_order_line_id;
        $delRes = $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-delivery", [
            'ship_immediately' => true,
            'lines' => [['sales_order_line_id' => $orderLineId, 'qty' => 5]],
        ]);
        $delRes->assertStatus(200)->assertJsonPath('data.order.status', 'DELIVERED');
        $draftInvId = $delRes->json('data.order.invoices.0.invoice_id');
        $this->assertNotNull($draftInvId);

        // 6. Reconciliation catches Delivered-But-Unbilled BEFORE bill is posted
        $reconBefore = $this->actingAs($this->user)->getJson('/sales/api/reconciliations');
        $reconBefore->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $reconBefore->json('data.kpis.delivered_unbilled_count'));

        // 7. Post Draft Bill from O2C
        $billRes = $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-bill", [
            'invoice_id' => $draftInvId,
            'target_status' => 'POSTED',
        ]);
        $billRes->assertStatus(200)->assertJsonPath('data.invoice.status', 'POSTED');

        // 8. Record AR Collection Receipt -> closes SO
        $inv = AcctSalesInvoice::findOrFail($draftInvId);
        app(AccountingArService::class)->recordCollectionReceipt([
            'company_id' => '1',
            'branch_id' => 'BR-01',
            'customer_id' => $this->customer->customer_id,
            'receipt_date' => now()->toDateString(),
            'payment_method' => 'BANK_TRANSFER',
            'reference_number' => 'INSTA-9012',
            'acted_by' => 'AR Collector',
        ], [[
            'sales_invoice_id' => $inv->invoice_id,
            'allocated_amount' => (float) $inv->total_amount,
            'cwt_amount' => 100.00,
        ]]);

        $this->assertSame('CLOSED', SalesOrder::findOrFail($orderId)->status);

        // 9. Daily Sales & Payment reports reflect live posted transaction
        $dailyRes = $this->actingAs($this->user)->getJson('/sales/api/reports/daily-sales?from=' . now()->toDateString() . '&to=' . now()->toDateString());
        $dailyRes->assertStatus(200);
        $this->assertEquals(6000.00, (float) $dailyRes->json('data.kpis.net_sales'));
        $this->assertEquals(2500.00, (float) $dailyRes->json('data.kpis.total_cogs'));

        $payRes = $this->actingAs($this->user)->getJson('/sales/api/reports/payments?from=' . now()->toDateString() . '&to=' . now()->toDateString());
        $payRes->assertStatus(200);
        $this->assertEquals(100.00, (float) $payRes->json('data.kpis.total_cwt_2307'));
    }

    public function test_bir_vat_exempt_discount_and_contra_revenue_gl_and_voucher_atomic_release(): void
    {
        // 1. Save BIR Senior Citizen VAT-Exempt Discount Rule
        $discRes = $this->actingAs($this->user)->postJson('/sales/api/discounts/save', [
            'discount_code' => 'SC20',
            'discount_name' => 'Senior Citizen 20% VAT-Exempt',
            'discount_category' => 'SENIOR_CITIZEN',
            'discount_type' => 'PERCENT',
            'discount_value' => 20,
            'vat_exempt_override' => true,
        ]);
        $discRes->assertStatus(200);
        $discountId = $discRes->json('data.discount_id');

        // 2. Save Voucher with max_total_uses = 1
        $vchRes = $this->actingAs($this->user)->postJson('/sales/api/vouchers/save', [
            'voucher_code' => 'ONCE200',
            'voucher_name' => 'One-Time 200 Off',
            'discount_type' => 'FIXED',
            'discount_value' => 200,
            'max_total_uses' => 1,
            'max_uses_per_customer' => 1,
        ]);
        $vchRes->assertStatus(200);

        // 3. Create COD Sales Order applying Discount + Voucher
        $soRes = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'COD',
            'discount_id' => $discountId,
            'voucher_code' => 'ONCE200',
            'lines' => [[
                'inventory_item_id' => $this->itemA->id,
                'sku' => $this->itemA->sku,
                'item_name' => $this->itemA->name,
                'quantity' => 1,
                'uom' => 'PCS',
                'unit_price' => 1000.00,
                'tax_code' => 'VAT12_OUT', // Overridden to VAT_EXEMPT by SC20
            ]],
        ]);
        $soRes->assertStatus(200);
        $soId = $soRes->json('data.sales_order_id');
        // Gross line 1000 - 20% (200) - 200 voucher = 600 net, 0 tax
        $this->assertEquals(0.00, (float) $soRes->json('data.tax_amount'));
        $this->assertEquals(600.00, (float) $soRes->json('data.gross_total'));

        // 4. Confirm COD order -> redeems voucher atomically & auto-approves
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $soId])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'APPROVED');
        $this->assertEquals(1, SalesVoucher::where('voucher_code', 'ONCE200')->value('current_uses_count'));

        // 5. Attempting to validate ONCE200 again fails because max_total_uses = 1
        $this->actingAs($this->user)->postJson('/sales/api/vouchers/validate', [
            'voucher_code' => 'ONCE200',
            'customer_id' => $this->customer->customer_id,
            'order_subtotal' => 1000,
        ])->assertStatus(422);

        // 6. Cancel SO -> releases voucher quota back to 0!
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/cancel", ['reason' => 'Test release'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED');
        $this->assertEquals(0, SalesVoucher::where('voucher_code', 'ONCE200')->value('current_uses_count'));

        // 7. Create a second COD order with SC20 discount, ship, and post Bill -> verify GL 4090 Contra-Revenue
        $so2Res = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'COD',
            'discount_id' => $discountId,
            'lines' => [[
                'inventory_item_id' => $this->itemA->id,
                'sku' => $this->itemA->sku,
                'item_name' => $this->itemA->name,
                'quantity' => 1,
                'uom' => 'PCS',
                'unit_price' => 1000.00,
                'tax_code' => 'VAT12_OUT',
            ]],
        ]);
        $so2Id = $so2Res->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $so2Id])->assertStatus(200);
        $so2LineId = SalesOrder::with('lines')->findOrFail($so2Id)->lines->first()->sales_order_line_id;
        $del2 = $this->actingAs($this->user)->postJson("/sales/api/orders/{$so2Id}/create-delivery", [
            'ship_immediately' => true,
            'lines' => [['sales_order_line_id' => $so2LineId, 'qty' => 1]],
        ]);
        $inv2Id = $del2->json('data.order.invoices.0.invoice_id');
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$so2Id}/create-bill", [
            'invoice_id' => $inv2Id,
            'target_status' => 'POSTED',
        ])->assertStatus(200);

        $discAcctId = ChartOfAccount::where('company_id', '1')->where('system_role', 'SALES_DISCOUNTS')->value('account_id');
        $this->assertNotNull($discAcctId);
        $discLine = GlJournalLine::where('account_id', $discAcctId)->first();
        $this->assertNotNull($discLine);
        $this->assertEquals(200.00, (float) $discLine->base_debit);
    }

    public function test_bundle_promotion_atp_explosion_and_stock_out(): void
    {
        // Bundle: 2x Steak (50 on hand -> 25 bundles) + 5x Wine (30 on hand -> 6 bundles) => Bundle ATP = 6
        $bndRes = $this->actingAs($this->user)->postJson('/sales/api/bundles/save', [
            'bundle_code' => 'FEAST-6',
            'bundle_name' => 'Wagyu & Wine Feast',
            'bundle_price' => 5000.00,
            'tax_code' => 'VAT12_OUT',
            'items' => [
                ['inventory_item_id' => $this->itemA->id, 'quantity' => 2, 'uom' => 'PCS'],
                ['inventory_item_id' => $this->itemB->id, 'quantity' => 5, 'uom' => 'BOTTLE'],
            ],
        ]);
        $bndRes->assertStatus(200);
        $this->assertEquals(6.0, (float) $bndRes->json('data.bundle_atp'));
        $bundleId = $bndRes->json('data.bundle_id');

        // Explode 2 bundles -> component lines total exactly 10,000.00 and carry bundle metadata
        $expRes = $this->actingAs($this->user)->postJson("/sales/api/bundles/{$bundleId}/explode", ['quantity' => 2]);
        $expRes->assertStatus(200);
        $lines = $expRes->json('data.lines');
        $this->assertCount(2, $lines);
        $this->assertEquals('FEAST-6', $lines[0]['bundle_code']);

        // Save COD order using exploded lines & confirm -> reserves 4 Steak + 10 Wine -> new Bundle ATP = 4
        $soRes = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'COD',
            'lines' => $lines,
        ])->assertStatus(200);
        $soId = $soRes->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $soId])->assertStatus(200);

        $so = SalesOrder::with('lines')->findOrFail($soId);
        $delLines = $so->lines->map(fn ($l) => ['sales_order_line_id' => $l->sales_order_line_id, 'qty' => (float) $l->qty_ordered])->all();
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/create-delivery", [
            'ship_immediately' => true,
            'lines' => $delLines,
        ])->assertStatus(200)->assertJsonPath('data.order.status', 'DELIVERED');

        $this->assertEquals(46.00, (float) $this->itemA->fresh()->current_stock);
        $this->assertEquals(20.00, (float) $this->itemB->fresh()->current_stock);
    }

    public function test_all_sales_web_routes_render_and_builder_pane_starts_hidden(): void
    {
        $routes = [
            '/sales/dashboard',
            '/sales/quotes',
            '/sales/orders',
            '/sales/daily-sales',
            '/sales/payment-report',
            '/sales/reconciliations',
            '/sales/discount-config',
            '/sales/voucher-config',
            '/sales/bundle-promotions',
            '/sales/customer-masterlist',
        ];

        foreach ($routes as $uri) {
            $res = $this->actingAs($this->user)->get($uri);
            $res->assertStatus(200);
        }

        // Verify Builder/Tracker strict separation in DOM initial state
        $ordersHtml = $this->actingAs($this->user)->get('/sales/orders');
        $ordersHtml->assertSee('id="so-pane-builder" style="display:none;"', false);
        $ordersHtml->assertSee('id="so-pane-list" style="display:flex;"', false);
    }

    public function test_promo_drawer_duration_windows_audit_trail_and_per_module_usage_telemetry(): void
    {
        // 1. Validation rejects valid_until < valid_from
        $this->actingAs($this->user)->postJson('/sales/api/discounts/save', [
            'discount_code' => 'BADWIN',
            'discount_name' => 'Invalid Window',
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'duration_mode' => 'SCHEDULED',
            'valid_from' => now()->addDays(5)->toDateString(),
            'valid_until' => now()->addDays(2)->toDateString(),
        ])->assertStatus(422);

        // 2. Create Scheduled Discount Rule (10 days) -> duration_days=10, schedule_status=ACTIVE_NOW, audit=CREATED
        $fromDate = now()->toDateString();
        $untilDate = now()->addDays(9)->toDateString();
        $createRes = $this->actingAs($this->user)->postJson('/sales/api/discounts/save', [
            'discount_code' => 'WEEK10',
            'discount_name' => '10-Day Scheduled Promo',
            'discount_type' => 'PERCENT',
            'discount_value' => 15,
            'duration_mode' => 'SCHEDULED',
            'valid_from' => $fromDate,
            'valid_until' => $untilDate,
        ])->assertStatus(200);

        $discId = $createRes->json('data.discount_id');
        $origCreator = $createRes->json('data.created_by');
        $this->assertEquals('SCHEDULED', $createRes->json('data.duration_mode'));
        $this->assertEquals(10, $createRes->json('data.duration_days'));
        $this->assertEquals('ACTIVE_NOW', $createRes->json('data.schedule_status'));

        // 3. Edit by a different user switching to ALWAYS -> preserves created_by, sets updated_by, clears dates, logs UPDATED
        $secondUser = User::factory()->create(['full_name' => 'Promo Reviewer']);
        $editRes = $this->actingAs($secondUser)->postJson('/sales/api/discounts/save', [
            'discount_id' => $discId,
            'discount_code' => 'WEEK10',
            'discount_name' => 'Always On Promo',
            'discount_type' => 'PERCENT',
            'discount_value' => 12,
            'duration_mode' => 'ALWAYS',
            'valid_from' => $fromDate,
            'valid_until' => $untilDate,
        ])->assertStatus(200);

        $this->assertSame($origCreator, $editRes->json('data.created_by'));
        $this->assertSame('Promo Reviewer', $editRes->json('data.updated_by'));
        $this->assertEquals('ALWAYS', $editRes->json('data.duration_mode'));
        $this->assertNull($editRes->json('data.valid_from'));
        $this->assertNull($editRes->json('data.valid_until'));

        // 4. Toggle Disable + Activate logs DISABLED / ACTIVATED
        $this->actingAs($secondUser)->postJson("/sales/api/discounts/{$discId}/toggle")->assertStatus(200);
        $this->actingAs($secondUser)->postJson("/sales/api/discounts/{$discId}/toggle")->assertStatus(200);

        // 5. Create Voucher + Bundle and confirm an Order using Discount + Voucher + Bundle
        $vchRes = $this->actingAs($this->user)->postJson('/sales/api/vouchers/save', [
            'voucher_code' => 'PROMO100',
            'voucher_name' => 'Audit Tracked Voucher',
            'discount_type' => 'FIXED',
            'discount_value' => 100,
            'duration_mode' => 'SCHEDULED',
            'valid_from' => $fromDate,
            'valid_until' => $untilDate,
        ])->assertStatus(200);
        $voucherId = $vchRes->json('data.voucher_id');

        $bndRes = $this->actingAs($this->user)->postJson('/sales/api/bundles/save', [
            'bundle_code' => 'BND-AUDIT',
            'bundle_name' => 'Audit Tracked Bundle',
            'bundle_price' => 2500,
            'duration_mode' => 'ALWAYS',
            'items' => [['inventory_item_id' => $this->itemA->id, 'quantity' => 2, 'uom' => 'PCS']],
        ])->assertStatus(200);
        $bundleId = $bndRes->json('data.bundle_id');

        $expLines = $this->actingAs($this->user)->postJson("/sales/api/bundles/{$bundleId}/explode", ['quantity' => 1])->json('data.lines');
        $soRes = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'COD',
            'discount_id' => $discId,
            'voucher_code' => 'PROMO100',
            'lines' => $expLines,
        ])->assertStatus(200);
        $soId = $soRes->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $soId])->assertStatus(200);

        // 6. Verify per-module telemetry APIs report usage counts, usage logs, and audit trails cleanly
        $discTel = $this->actingAs($this->user)->getJson('/sales/api/promotions/discounts/telemetry')->assertStatus(200);
        $this->assertEquals(1, $discTel->json("data.usage_summary.{$discId}.uses_count"));
        $this->assertCount(1, $discTel->json('data.usage_logs'));
        $discActions = collect($discTel->json('data.audit_logs'))->pluck('action')->all();
        $this->assertContains('CREATED', $discActions);
        $this->assertContains('UPDATED', $discActions);
        $this->assertContains('DISABLED', $discActions);
        $this->assertContains('ACTIVATED', $discActions);
        $this->assertContains('APPLIED_TO_ORDER', $discActions);

        $vchTel = $this->actingAs($this->user)->getJson('/sales/api/promotions/vouchers/telemetry')->assertStatus(200);
        $this->assertEquals(1, $vchTel->json("data.usage_summary.{$voucherId}.uses_count"));
        $this->assertContains('REDEEMED', collect($vchTel->json('data.audit_logs'))->pluck('action')->all());

        // Cancelling releases voucher and logs RELEASED audit event
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/cancel", ['reason' => 'Cancel test'])->assertStatus(200);
        $vchTelAfterCancel = $this->actingAs($this->user)->getJson('/sales/api/promotions/vouchers/telemetry')->assertStatus(200);
        $this->assertContains('RELEASED', collect($vchTelAfterCancel->json('data.audit_logs'))->pluck('action')->all());

        // 7. Also verify Bundle Usage + Audit Trail with a fresh non-cancelled order
        $so2Res = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'COD',
            'lines' => $expLines,
        ])->assertStatus(200);
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $so2Res->json('data.sales_order_id')])->assertStatus(200);

        $bndTel = $this->actingAs($this->user)->getJson('/sales/api/promotions/bundles/telemetry')->assertStatus(200);
        $this->assertEquals(1, $bndTel->json("data.usage_summary.{$bundleId}.uses_count"));
        $bndActions = collect($bndTel->json('data.audit_logs'))->pluck('action')->all();
        $this->assertContains('CREATED', $bndActions);
        $this->assertContains('APPLIED_TO_ORDER', $bndActions);
    }

    public function test_promo_views_render_slide_over_drawer_and_usage_audit_tabs_without_popups(): void
    {
        foreach (['/sales/discount-config', '/sales/voucher-config', '/sales/bundle-promotions'] as $uri) {
            $res = $this->actingAs($this->user)->get($uri);
            $res->assertStatus(200);
            $res->assertSee('bom-drawer-container', false);
            $res->assertSee('id="sp-pane-catalog" style="display:flex;"', false);
            $res->assertSee('id="sp-pane-usage" style="display:none;"', false);
            $res->assertSee('id="sp-pane-audit" style="display:none;"', false);
            $res->assertSee('Usage per Module', false);
            $res->assertSee('Audit Trail', false);
            $res->assertDontSee('rfq-modal-backdrop', false);
        }
    }

    public function test_accounting_custom_tax_rate_and_payment_term_due_date_integration(): void
    {
        AcctTaxCode::create([
            'company_id' => '1',
            'tax_code' => 'OPT5_OUT',
            'tax_name' => '5% Special Output Tax',
            'tax_type' => 'VAT_OUTPUT',
            'rate_pct' => 5.0000,
            'atc_code' => 'PT-5',
            'bir_form' => '2551Q',
            'system_role_link' => 'OUTPUT_VAT',
            'is_active' => true,
        ]);

        $soRes = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'NET15',
            'lines' => [[
                'inventory_item_id' => $this->itemA->id,
                'sku' => $this->itemA->sku,
                'item_name' => $this->itemA->name,
                'quantity' => 1,
                'uom' => 'PCS',
                'unit_price' => 1000.00,
                'tax_code' => 'OPT5_OUT',
            ]],
        ])->assertStatus(200);

        $this->assertEquals(50.00, (float) $soRes->json('data.tax_amount'));
        $this->assertEquals(1050.00, (float) $soRes->json('data.gross_total'));

        $soId = $soRes->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $soId])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'CREDIT_HOLD');

        $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/credit-review", [
            'decision' => 'APPROVE',
            'remarks' => 'NET15 approved',
        ])->assertStatus(200)->assertJsonPath('data.status', 'APPROVED');

        $lineId = SalesOrder::with('lines')->findOrFail($soId)->lines->first()->sales_order_line_id;
        $delRes = $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/create-delivery", [
            'ship_immediately' => true,
            'lines' => [['sales_order_line_id' => $lineId, 'qty' => 1]],
        ])->assertStatus(200);

        $inv = AcctSalesInvoice::findOrFail($delRes->json('data.order.invoices.0.invoice_id'));
        $this->assertEquals(50.00, (float) $inv->tax_amount);
        $this->assertEquals(15, $inv->invoice_date->diffInDays($inv->due_date));
    }

    public function test_negative_stock_dispatch_guard_blocks_ship_immediately_but_allows_draft_queue(): void
    {
        // itemA current_stock = 50.00; order 60.00
        $soRes = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'document_stage' => 'ORDER',
            'customer_id' => $this->customer->customer_id,
            'payment_terms_code' => 'COD',
            'lines' => [[
                'inventory_item_id' => $this->itemA->id,
                'sku' => $this->itemA->sku,
                'item_name' => $this->itemA->name,
                'quantity' => 60,
                'uom' => 'PCS',
                'unit_price' => 1200.00,
                'tax_code' => 'VAT12_OUT',
            ]],
        ])->assertStatus(200);

        $soId = $soRes->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $soId])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'APPROVED');

        $lineId = SalesOrder::with('lines')->findOrFail($soId)->lines->first()->sales_order_line_id;

        // Immediate dispatch exceeding current_stock must fail cleanly with 422
        $failRes = $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/create-delivery", [
            'ship_immediately' => true,
            'lines' => [['sales_order_line_id' => $lineId, 'qty' => 60]],
        ]);
        $failRes->assertStatus(422);
        $this->assertStringContainsString('Insufficient on-hand stock', (string) $failRes->json('message'));
        $this->assertEquals(50.00, (float) $this->itemA->fresh()->current_stock);

        // Queueing Draft in Stock-Out (ship_immediately = false) succeeds cleanly
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$soId}/create-delivery", [
            'ship_immediately' => false,
            'lines' => [['sales_order_line_id' => $lineId, 'qty' => 60]],
        ])->assertStatus(200)->assertJsonPath('data.delivery.status', 'DRAFT');
    }

    public function test_o2c_revamp_renders_drawers_accounting_data_and_no_legacy_popup_modals(): void
    {
        $res = $this->actingAs($this->user)->get('/sales/orders');
        $res->assertStatus(200);
        $res->assertSee('id="so-pane-list" style="display:flex;"', false);
        $res->assertSee('id="so-pane-builder" style="display:none;"', false);
        $res->assertSee('soDeliveryDrawer', false);
        $res->assertSee('soItemPickerDrawer', false);
        $res->assertSee('soCreditReviewDrawer', false);
        $res->assertSee('soCancelDrawer', false);
        $res->assertSee('bom-drawer-container', false);
        $res->assertSee('initialPaymentTerms', false);
        $res->assertSee('initialTaxCodes', false);
        $res->assertSee('soCwtEstimateSelect', false);
        $res->assertDontSee('rfq-modal-backdrop', false);
    }
}
