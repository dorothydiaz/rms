<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\StockOutOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderReservation;
use App\Models\Sales\SalesQuote;
use App\Models\User;
use App\Services\Accounting\AccountingSeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderToCashIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AcctCustomer $creditCustomer;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        app(AccountingSeedService::class)->ensureSeeded('1');

        $this->creditCustomer = AcctCustomer::create([
            'company_id' => '1',
            'customer_code' => 'CUST-B2B-01',
            'customer_name' => 'Manila Grand Catering Corp.',
            'trade_name' => 'Manila Grand',
            'tin' => '111-222-333-000',
            'address' => 'BGC Taguig, Metro Manila',
            'shipping_address' => 'BGC Kitchen Hub, Taguig',
            'contact_person' => 'Elena Reyes',
            'email' => 'elena@manilagrand.ph',
            'phone' => '+63 917 555 0101',
            'payment_terms_code' => 'NET_30',
            'credit_limit' => 50000.00,
            'current_balance' => 0.00,
            'credit_hold' => false,
            'credit_grace_days' => 15,
            'is_active' => true,
        ]);

        $this->item = InventoryItem::create([
            'sku' => 'FG-TRUFFLE-BOX',
            'name' => 'Gourmet Truffle Pasta Tray',
            'category' => 'Finished Goods',
            'uom' => 'PCS',
            'cost_price' => 400.00,
            'selling_price' => 1000.00,
            'current_stock' => 100.00,
            'min_stock' => 10.00,
            'max_stock' => 500.00,
            'storage_location' => 'WH-MAIN',
            'is_active' => true,
        ]);
    }

    public function test_sales_quote_lifecycle_revision_expiration_and_conversion_to_sales_order(): void
    {
        // 1. Save Draft Quote (SQ-YYYY-XXXX)
        $draftRes = $this->actingAs($this->user)->postJson('/sales/api/quotes/save-draft', [
            'customer_id' => $this->creditCustomer->customer_id,
            'valid_until_date' => now()->subDays(1)->toDateString(), // Expired date to test expiry guard
            'payment_terms_code' => 'NET_30',
            'customer_reference' => 'EVENT-2026-88',
            'lines' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'sku' => $this->item->sku,
                    'item_name' => $this->item->name,
                    'quantity' => 10,
                    'uom' => 'PCS',
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT12_OUT',
                ],
            ],
        ]);

        $draftRes->assertStatus(200)->assertJsonPath('success', true);
        $quoteId = $draftRes->json('data.quote_id');
        $this->assertStringStartsWith('SQ-', $draftRes->json('data.quote_number'));

        // 2. Send Quote
        $this->actingAs($this->user)->postJson('/sales/api/quotes/transition-status', [
            'quote_id' => $quoteId,
            'target_status' => 'SENT',
        ])->assertStatus(200)->assertJsonPath('data.status', 'SENT');

        // 3. Attempting to confirm expired quote must fail with 422
        $this->actingAs($this->user)->postJson('/sales/api/quotes/transition-status', [
            'quote_id' => $quoteId,
            'target_status' => 'CONFIRMED',
        ])->assertStatus(422);

        // 4. Clone Revision (v2) with fresh validity date
        $revRes = $this->actingAs($this->user)->postJson('/sales/api/quotes/revise', [
            'quote_id' => $quoteId,
        ]);
        $revRes->assertStatus(200)->assertJsonPath('data.revision_no', 2);
        $revId = $revRes->json('data.quote_id');

        // 5. Confirm Revision v2
        $this->actingAs($this->user)->postJson('/sales/api/quotes/transition-status', [
            'quote_id' => $revId,
            'target_status' => 'CONFIRMED',
        ])->assertStatus(200)->assertJsonPath('data.status', 'CONFIRMED');

        // 6. Convert Confirmed Quote to Sales Order (SLS-YYYY-XXXX)
        $convRes = $this->actingAs($this->user)->postJson('/sales/api/quotes/convert-order', [
            'quote_id' => $revId,
        ]);
        $convRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertStringStartsWith('SLS-', $convRes->json('data.order_number'));
        $this->assertEquals(11200.00, (float) $convRes->json('data.gross_total'));

        $this->assertDatabaseHas('sales_quotes', [
            'quote_id' => $revId,
            'status' => 'CONVERTED',
            'sales_order_id' => $convRes->json('data.sales_order_id'),
        ]);
    }

    public function test_strict_credit_review_for_credit_terms_and_cod_auto_clear_with_atp_reservation(): void
    {
        // A. COD Order should auto-clear without Accounting hold and reserve ATP
        $codDraft = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'customer_id' => $this->creditCustomer->customer_id,
            'payment_terms_code' => 'COD',
            'lines' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'sku' => $this->item->sku,
                    'item_name' => $this->item->name,
                    'quantity' => 15,
                    'uom' => 'PCS',
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT12_OUT',
                ],
            ],
        ])->assertStatus(200);

        $codOrderId = $codDraft->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', [
            'sales_order_id' => $codOrderId,
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.credit_review_status', 'AUTO_CLEARED');

        // Stock on-hand untouched (100), reservation active (15)
        $this->item->refresh();
        $this->assertEquals(100.0, (float) $this->item->current_stock);
        $this->assertDatabaseHas('sales_order_reservations', [
            'sales_order_id' => $codOrderId,
            'reserved_qty' => 15.0,
            'status' => 'ACTIVE',
        ]);

        // B. Credit-Term Order WITHIN limit STILL enters CREDIT_HOLD per Strict Manual Review policy
        $netDraft = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'customer_id' => $this->creditCustomer->customer_id,
            'payment_terms_code' => 'NET_30',
            'lines' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'sku' => $this->item->sku,
                    'item_name' => $this->item->name,
                    'quantity' => 10, // 11,200 gross <= 50,000 limit
                    'uom' => 'PCS',
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT12_OUT',
                ],
            ],
        ])->assertStatus(200);

        $netOrderId = $netDraft->json('data.sales_order_id');
        $confirmNet = $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', [
            'sales_order_id' => $netOrderId,
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'CREDIT_HOLD')
            ->assertJsonPath('data.credit_review_status', 'PENDING_REVIEW');

        $this->assertNotEmpty($confirmNet->json('data.approval_request_id'));
        $this->assertDatabaseHas('approval_requests', [
            'approval_document_type' => 'SALES_ORDER_CREDIT',
            'approval_document_id' => $netOrderId,
            'approval_request_status' => 'PENDING',
        ]);
    }

    public function test_credit_limit_breach_blocks_delivery_requires_override_remarks_and_releases_on_accounting_approval(): void
    {
        // Order exceeds 50,000 limit (50 * 1,000 * 1.12 = 56,000)
        $draft = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'customer_id' => $this->creditCustomer->customer_id,
            'payment_terms_code' => 'NET_30',
            'lines' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'sku' => $this->item->sku,
                    'item_name' => $this->item->name,
                    'quantity' => 50,
                    'uom' => 'PCS',
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT12_OUT',
                ],
            ],
        ])->assertStatus(200);

        $orderId = $draft->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', [
            'sales_order_id' => $orderId,
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'CREDIT_HOLD');

        // Warehouse cannot deliver while in CREDIT_HOLD
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-delivery", [
            'ship_immediately' => true,
        ])->assertStatus(422);

        // Over-limit approval without remarks fails (422)
        $this->actingAs($this->user)->postJson("/accounting/api/ar/credit-review/{$orderId}", [
            'decision' => 'APPROVE_OVERRIDE',
            'remarks' => '',
        ])->assertStatus(422);

        // Accounting approves override with remarks
        $this->actingAs($this->user)->postJson("/accounting/api/ar/credit-review/{$orderId}", [
            'decision' => 'APPROVE_OVERRIDE',
            'remarks' => 'CEO approved seasonal catering credit extension',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.credit_review_status', 'APPROVED_OVERRIDE');

        // ATP reservation is now active
        $this->assertDatabaseHas('sales_order_reservations', [
            'sales_order_id' => $orderId,
            'reserved_qty' => 50.0,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_full_o2c_delivery_posts_cogs_gl_creates_draft_bill_and_collection_closes_order(): void
    {
        // Create & approve a 10-unit order (Subtotal 10,000, VAT 1,200, Gross 11,200; COGS = 10 * 400 = 4,000)
        $draft = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'customer_id' => $this->creditCustomer->customer_id,
            'payment_terms_code' => 'NET_30',
            'lines' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'sku' => $this->item->sku,
                    'item_name' => $this->item->name,
                    'quantity' => 10,
                    'uom' => 'PCS',
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT12_OUT',
                ],
            ],
        ])->assertStatus(200);

        $orderId = $draft->json('data.sales_order_id');
        $soLineId = $draft->json('data.lines.0.sales_order_line_id');

        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $orderId])->assertStatus(200);
        $this->actingAs($this->user)->postJson("/accounting/api/ar/credit-review/{$orderId}", [
            'decision' => 'APPROVE',
            'remarks' => 'Within credit limit',
        ])->assertStatus(200)->assertJsonPath('data.status', 'APPROVED');

        // 1. Create Draft Delivery Order (6 units partial) -> verify Inventory Stock-Out confirm-ship hook!
        $delDraft = $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-delivery", [
            'ship_immediately' => false,
            'lines' => [['sales_order_line_id' => $soLineId, 'qty' => 6]],
        ])->assertStatus(200);

        $doId = $delDraft->json('data.delivery.id');
        $this->assertStringStartsWith('DO-', $delDraft->json('data.delivery.order_number'));

        // Ship via InventoryController::apiConfirmShipStockOut
        $shipRes = $this->actingAs($this->user)->postJson('/inventory/api/stock-out/confirm-ship', [
            'order_id' => $doId,
            'picker_name' => 'Warehouse Dispatch',
        ])->assertStatus(200)->assertJsonPath('success', true);

        // Verify stock deducted (100 -> 94), StockLedger created, SO partially delivered, COGS GL posted (2,400)
        $this->item->refresh();
        $this->assertEquals(94.0, (float) $this->item->current_stock);
        $this->assertDatabaseHas('stock_ledger', [
            'reference_id' => $doId,
            'transaction_type' => 'STOCK_OUT',
            'quantity_change' => -6.0,
        ]);

        $doRecord = StockOutOrder::find($doId);
        $this->assertNotEmpty($doRecord->cogs_journal_id);
        $cogsJournal = GlJournalHeader::with('lines')->where('journal_id', $doRecord->cogs_journal_id)->first();
        $this->assertNotNull($cogsJournal);
        $this->assertEquals('POSTED', $cogsJournal->status);
        $this->assertEquals(2400.0, (float) $cogsJournal->total_debit);

        // Verify DRAFT Invoice auto-created (6 units: 6,000 + 720 VAT = 6,720)
        $draftInvId = $shipRes->json('data.sales_sync.draft_invoice.invoice_id');
        $this->assertNotEmpty($draftInvId);
        $this->assertDatabaseHas('acct_sales_invoices', [
            'invoice_id' => $draftInvId,
            'sales_order_id' => $orderId,
            'status' => 'DRAFT',
            'total_amount' => 6720.0,
        ]);

        // 2. Ship remaining 4 units immediately via Sales Order fast ship
        $ship2 = $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-delivery", [
            'ship_immediately' => true,
            'lines' => [['sales_order_line_id' => $soLineId, 'qty' => 4]],
        ])->assertStatus(200);
        $draftInvId2 = $ship2->json('data.sales_sync.draft_invoice.invoice_id');

        $this->item->refresh();
        $this->assertEquals(90.0, (float) $this->item->current_stock);
        $this->assertDatabaseHas('sales_orders', [
            'sales_order_id' => $orderId,
            'fulfillment_status' => 'DELIVERED',
        ]);
        $this->assertDatabaseHas('sales_order_reservations', [
            'sales_order_line_id' => $soLineId,
            'status' => 'FULFILLED',
        ]);

        // 3. Post both Draft Customer Bills (1-click Post Bill)
        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-bill", [
            'invoice_id' => $draftInvId,
            'target_status' => 'POSTED',
        ])->assertStatus(200)->assertJsonPath('data.invoice.status', 'POSTED');

        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/create-bill", [
            'invoice_id' => $draftInvId2,
            'target_status' => 'POSTED',
        ])->assertStatus(200)->assertJsonPath('data.invoice.status', 'POSTED');

        $orderAfterBill = SalesOrder::find($orderId);
        $this->assertEquals(11200.0, (float) $orderAfterBill->amount_billed);
        $this->assertEquals('BILLED', $orderAfterBill->status);
        $this->assertEquals('UNPAID', $orderAfterBill->payment_status);

        // 4. Record AR Collection Receipt paying both invoices in full -> Sales Order auto-transitions to CLOSED!
        $cashBankGl = \App\Models\Accounting\ChartOfAccount::where('company_id', '1')
            ->where('system_role', 'CASH_IN_BANK')
            ->firstOrFail();
        $this->actingAs($this->user)->postJson('/accounting/api/ar/receipts/save', [
            'receipt' => [
                'customer_id' => $this->creditCustomer->customer_id,
                'receipt_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'deposit_account_id' => $cashBankGl->account_id,
            ],
            'allocations' => [
                ['sales_invoice_id' => $draftInvId, 'allocated_amount' => 6720.00],
                ['sales_invoice_id' => $draftInvId2, 'allocated_amount' => 4480.00],
            ],
        ])->assertStatus(200);

        $closedOrder = SalesOrder::find($orderId);
        $this->assertEquals(11200.0, (float) $closedOrder->amount_paid);
        $this->assertEquals(0.0, (float) $closedOrder->balance_due);
        $this->assertEquals('PAID', $closedOrder->payment_status);
        $this->assertEquals('CLOSED', $closedOrder->status);
    }

    public function test_cancelling_approved_sales_order_releases_atp_reservations(): void
    {
        $draft = $this->actingAs($this->user)->postJson('/sales/api/orders/save-draft', [
            'customer_id' => $this->creditCustomer->customer_id,
            'payment_terms_code' => 'COD',
            'lines' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'sku' => $this->item->sku,
                    'item_name' => $this->item->name,
                    'quantity' => 20,
                    'uom' => 'PCS',
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT12_OUT',
                ],
            ],
        ])->assertStatus(200);

        $orderId = $draft->json('data.sales_order_id');
        $this->actingAs($this->user)->postJson('/sales/api/orders/confirm', ['sales_order_id' => $orderId])->assertStatus(200);

        $this->assertDatabaseHas('sales_order_reservations', [
            'sales_order_id' => $orderId,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->user)->postJson("/sales/api/orders/{$orderId}/cancel", [
            'reason' => 'Customer postponed event',
        ])->assertStatus(200)->assertJsonPath('data.status', 'CANCELLED');

        $this->assertDatabaseMissing('sales_order_reservations', [
            'sales_order_id' => $orderId,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_sales_and_ar_credit_review_views_render_rfq_design_classes_and_uom_dropdown(): void
    {
        $this->actingAs($this->user)->get('/sales/dashboard')
            ->assertStatus(200)
            ->assertSee('rfq-kpi-card');

        $this->actingAs($this->user)->get('/sales/quotes')
            ->assertStatus(200)
            ->assertSee('rfq-chevron-pipeline')
            ->assertSee('uom-registry.js')
            ->assertSee('sales-orders');

        $this->actingAs($this->user)->get('/sales/orders')
            ->assertStatus(200)
            ->assertSee('rfq-chevron-pipeline')
            ->assertSee('uom-registry.js')
            ->assertSee('sales-orders');

        $this->actingAs($this->user)->get('/sales/customer-masterlist')
            ->assertStatus(200)
            ->assertSee('vendor-kanban-grid');

        $this->actingAs($this->user)->get('/accounting/ar?tab=credit-review')
            ->assertStatus(200)
            ->assertSee('Credit Review Queue');
    }
}
