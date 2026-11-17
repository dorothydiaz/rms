<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctBankAccount;
use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctVendorMemo;
use App\Models\Accounting\AcctVendorPayment;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Purchase\ProcurementVendor;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\User;
use App\Services\Accounting\AccountingSeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApPurchaseOrderPaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $companyId = '1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();
        app(AccountingSeedService::class)->ensureSeeded($this->companyId);
    }

    public function test_po_payment_and_vendor_credit_application_sync_to_accounting_ap(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-INT-01',
            'legal_name' => 'Pacific Culinary Imports Inc.',
            'trade_name' => 'Pacific Culinary',
            'tax_id' => '123-999-888-000',
            'payment_terms' => 'NET30',
            'is_active' => true,
        ]);

        // 1. Create Credit Memo via AP API (using CREDIT_MEMO from UI)
        $resMemo = $this->actingAs($this->user)->postJson(route('accounting.api.ap.memos.save'), [
            'memo' => [
                'vendor_id' => (string) $vendor->id,
                'memo_type' => 'CREDIT_MEMO',
                'memo_date' => now()->toDateString(),
                'amount' => 1500.00,
                'reason_description' => 'Damaged packaging credit',
            ],
        ]);
        $resMemo->assertStatus(200)->assertJsonPath('success', true);

        $memo = AcctVendorMemo::firstOrFail();
        $this->assertEquals('CREDIT', $memo->memo_type);
        $this->assertEquals(1500.00, (float) $memo->unapplied_amount);

        // 2. Create a Purchase Order referencing vendor_code (how PO Builder stores it)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-9001',
            'po_type' => 'vendor',
            'vendor_id' => $vendor->vendor_code,
            'vendor_name' => $vendor->legal_name,
            'vendor_trade_name' => $vendor->trade_name,
            'order_date' => now()->toDateString(),
            'status' => 'Approved / Issued',
            'subtotal' => 5000.00,
            'gross_total' => 5000.00,
            'amount_paid' => 0.00,
            'balance_due' => 5000.00,
            'payment_status' => 'Unpaid / Credit',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'sku' => 'SKU-901',
            'item_name' => 'Imported Olive Oil 5L',
            'uom' => 'CAN',
            'quantity' => 5,
            'unit_price' => 1000.00,
            'total_amount' => 5000.00,
            'status' => 'PENDING',
        ]);

        // 3. Verify PO API exposes the available AP credit balance
        $resPoList = $this->actingAs($this->user)->getJson(route('purchase.api.orders'));
        $resPoList->assertStatus(200);
        $credits = $resPoList->json('data.vendorCredits');
        $this->assertNotEmpty($credits);
        $this->assertEquals(1500.00, (float) $credits[0]['unapplied_amount']);

        // 4. Apply Vendor Credit (1,500) from PO Payment Summary / Settlement
        $resApplyCredit = $this->actingAs($this->user)->postJson(route('purchase.api.orders.record-payment'), [
            'po_number' => $po->po_number,
            'purchase_order_id' => $po->id,
            'amount' => 1500.00,
            'tranche_type' => 'Vendor Credit Application',
            'payment_method' => 'VENDOR_CREDIT',
            'is_credit_application' => true,
            'credit_amount' => 1500.00,
            'payment_date' => now()->toDateString(),
        ]);
        $resApplyCredit->assertStatus(200)->assertJsonPath('success', true);

        $memo->refresh();
        $po->refresh();
        $bill = VendorBillHeader::where('purchase_order_id', (string) $po->id)->firstOrFail();

        $this->assertEquals(0.00, (float) $memo->unapplied_amount);
        $this->assertEquals(5000.00, (float) $bill->bill_total_amount);
        $this->assertEquals(1500.00, (float) $bill->bill_paid_amount);
        $this->assertEquals('PARTIAL', $bill->vendor_bill_status);
        $this->assertEquals(1500.00, (float) $po->amount_paid);
        $this->assertEquals(3500.00, (float) $po->balance_due);
        $this->assertEquals('Partial Payment', $po->payment_status);

        // 5. Record remaining cash payment (3,500) via PO Payment Settlement
        $resCashPay = $this->actingAs($this->user)->postJson(route('purchase.api.orders.record-payment'), [
            'po_number' => $po->po_number,
            'purchase_order_id' => $po->id,
            'amount' => 3500.00,
            'tranche_type' => 'Final Settlement',
            'payment_method' => 'Bank Transfer / Electronic',
            'reference' => 'TRF-FINAL-9001',
            'payment_date' => now()->toDateString(),
        ]);
        $resCashPay->assertStatus(200)->assertJsonPath('success', true);

        $po->refresh();
        $bill->refresh();
        $this->assertEquals('PAID', $bill->vendor_bill_status);
        $this->assertEquals(5000.00, (float) $bill->bill_paid_amount);
        $this->assertEquals(5000.00, (float) $po->amount_paid);
        $this->assertEquals(0.00, (float) $po->balance_due);
        $this->assertEquals('Paid in Full / Cash Out', $po->payment_status);

        $this->assertDatabaseHas('acct_vendor_payments', [
            'vendor_id' => (string) $vendor->id,
            'total_amount' => 3500.00,
            'status' => 'POSTED',
        ]);
    }

    public function test_accounting_ap_payment_updates_linked_purchase_order_balance(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-INT-02',
            'legal_name' => 'Manila Packaging Corp.',
            'trade_name' => 'Manila Packaging',
            'tax_id' => '222-333-444-000',
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-9002',
            'po_type' => 'vendor',
            'vendor_id' => (string) $vendor->id,
            'vendor_name' => $vendor->legal_name,
            'order_date' => now()->toDateString(),
            'status' => 'Approved / Issued',
            'subtotal' => 2000.00,
            'gross_total' => 2000.00,
            'amount_paid' => 0.00,
            'balance_due' => 2000.00,
            'payment_status' => 'Unpaid / Credit',
        ]);

        $resBill = $this->actingAs($this->user)->postJson(route('accounting.api.ap.bills.save'), [
            'header' => [
                'vendor_id' => (string) $vendor->id,
                'purchase_order_id' => (string) $po->id,
                'vendor_invoice_number' => 'SI-MP-9002',
                'vendor_invoice_date' => now()->toDateString(),
                'bill_due_date' => now()->addDays(30)->toDateString(),
            ],
            'lines' => [
                [
                    'bill_line_description' => 'Kraft Takeout Boxes',
                    'quantity' => 200,
                    'uom' => 'PCS',
                    'bill_unit_price' => 10.00,
                    'tax_code' => 'VAT_EXEMPT',
                ],
            ],
            'action' => 'POSTED',
        ]);
        $resBill->assertStatus(200);
        $billId = $resBill->json('data.vendor_bill_id');

        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BPI Operating',
            'bank_name' => 'BPI',
            'account_number' => '111-222-333',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);

        $resPay = $this->actingAs($this->user)->postJson(route('accounting.api.ap.payments.save'), [
            'payment' => [
                'vendor_id' => (string) $vendor->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'bank_account_id' => $bankAccount->bank_account_id,
                'reference_number' => 'TRF-AP-9002',
                'total_amount' => 2000.00,
                'total_ewt_amount' => 0.00,
                'total_net_amount' => 2000.00,
            ],
            'allocations' => [
                [
                    'vendor_bill_id' => $billId,
                    'allocated_amount' => 2000.00,
                    'ewt_amount' => 0.00,
                    'ewt_rate_pct' => 0.00,
                ],
            ],
        ]);
        $resPay->assertStatus(200);

        $po->refresh();
        $this->assertEquals(2000.00, (float) $po->amount_paid);
        $this->assertEquals(0.00, (float) $po->balance_due);
        $this->assertEquals('Paid in Full / Cash Out', $po->payment_status);
    }

    public function test_ap_and_po_views_render_rms_drawer_rfq_table_add_row_and_credit_ui(): void
    {
        $resAp = $this->actingAs($this->user)->get(route('accounting.ap'));
        $resAp->assertStatus(200);
        $resAp->assertSee('id="apBillsRegisterTable"', false);
        $resAp->assertSee('id="apBillsTableSettingsDropdown"', false);
        $resAp->assertSee('id="apBillDrawerPanel"', false);
        $resAp->assertSee('id="apPaymentDrawerPanel"', false);
        $resAp->assertSee('id="apPmtPoQuickSelect"', false);
        $resAp->assertSee('id="apPmtExcessBanner"', false);
        $resAp->assertSee('class="rms-drawer-panel"', false);
        $resAp->assertSee('id="btnApAddBillLineTop"', false);
        $resAp->assertSee('id="btnApAddBillLine"', false);
        $resAp->assertSee('Add Row', false);
        $resAp->assertSee('EWT Withheld (BIR 2307)', false);
        $resAp->assertSee('Unapplied Vendor Credits', false);
        $resAp->assertDontSee('id="ap2307RegisterTable"', false);
        $resAp->assertSee('id="btnApNewSeparate2307"', false);
        $resAp->assertSee('id="apPmtSettlementMode"', false);
        $resAp->assertSee('class="acct-ledger-split-20-80"', false);
        $resAp->assertSee('id="apAgingSchedulePanel"', false);
        $resAp->assertSee('id="apVendorOpenBalancesTable"', false);
        $resAp->assertSee('id="apVendorBalancesTableSettingsDropdown"', false);
        $resAp->assertSee('id="apVendorBalancesBody"', false);

        $resPo = $this->actingAs($this->user)->get(route('purchase.purchase-orders'));
        $resPo->assertStatus(200);
        $resPo->assertSee('id="summaryPoVendorCreditRow"', false);
        $resPo->assertSee('id="payModalCreditBtn"', false);
        $resPo->assertSee('id="newPayFundSource"', false);
        $resPo->assertSee('id="newPayEwtRule"', false);
        $resPo->assertSee('id="newPayExcessCreditBanner"', false);
    }

    public function test_approved_po_auto_generates_bill_and_overpayment_creates_vendor_credit_memo(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-AUTO-100',
            'legal_name' => 'Manila Commissary Supply Inc.',
            'trade_name' => 'Manila Supply',
            'tax_id' => '321-654-987-000',
            'is_active' => true,
        ]);

        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'Metrobank Operating Checking',
            'bank_name' => 'Metrobank',
            'account_number' => '888-000-111',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 100000.00,
            'is_active' => true,
        ]);

        // 1. Issue an approved PO via endpoint -> VendorBillHeader must auto-generate
        $resPo = $this->actingAs($this->user)->postJson(route('purchase.api.orders.create'), [
            'vendor_id' => (string) $vendor->id,
            'vendor_name' => $vendor->legal_name,
            'vendor_trade_name' => $vendor->trade_name,
            'status' => 'Approved / Issued',
            'items' => [
                [
                    'sku' => 'RAW-AUTO-1',
                    'name' => 'Specialty Espresso Beans',
                    'unit' => 'KG',
                    'quantity' => 2,
                    'unitPrice' => 500.00,
                ],
            ],
        ]);
        $resPo->assertStatus(200);
        $poId = $resPo->json('data.po.id');
        $poNumber = $resPo->json('data.po_number');

        $bill = VendorBillHeader::where('purchase_order_id', (string) $poId)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('POSTED', $bill->vendor_bill_status);
        $this->assertEquals(1000.00, (float) $bill->bill_total_amount);

        // 2. Overpay the PO via PO Payment Settlement with Bank Account + 1% EWT (1500.00 against 1000.00 balance)
        $resOverpay = $this->actingAs($this->user)->postJson(route('purchase.api.orders.record-payment'), [
            'po_number' => $poNumber,
            'purchase_order_id' => $poId,
            'amount' => 1500.00,
            'tranche_type' => 'Full Spot Payment',
            'payment_method' => 'Bank Transfer / Electronic',
            'bank_account_id' => $bankAccount->bank_account_id,
            'ewt_rate_pct' => 1.0,
            'ewt_tax_code' => 'WC158',
            'ewt_amount' => 15.00,
            'reference' => 'TRF-OVER-1500',
            'payment_date' => now()->toDateString(),
        ]);
        $resOverpay->assertStatus(200);
        $resOverpay->assertJsonPath('success', true);
        $resOverpay->assertJsonPath('data.excess_credit_amount', 500);

        $bill->refresh();
        $this->assertEquals('PAID', $bill->vendor_bill_status);
        $this->assertEquals(1000.00, (float) $bill->bill_paid_amount);

        $po = PurchaseOrder::findOrFail($poId);
        $this->assertEquals(1000.00, (float) $po->amount_paid);
        $this->assertEquals(0.00, (float) $po->balance_due);
        $this->assertEquals('Paid in Full / Cash Out', $po->payment_status);

        // 3. Verify 500.00 Overpayment Credit Memo + BIR Form 2307 Certificate exist
        $creditMemo = AcctVendorMemo::where('vendor_id', (string) $vendor->id)
            ->where('memo_type', 'CREDIT')
            ->where('status', 'POSTED')
            ->first();
        $this->assertNotNull($creditMemo);
        $this->assertEquals(500.00, (float) $creditMemo->unapplied_amount);

        $pmtId = $resOverpay->json('data.payment.payment_id');
        $cert = AcctBir2307Certificate::where('vendor_payment_id', $pmtId)->first();
        $this->assertNotNull($cert);
        $this->assertEquals('WC158', $cert->atc_code);
        $this->assertEquals(15.00, (float) $cert->amount_withheld);
    }

    public function test_separate_bill_only_and_ewt_2307_payments_and_ledger_pending_pos(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-SEP-2307',
            'legal_name' => 'Cebu Spice & Packaging Corp.',
            'trade_name' => 'Cebu Spice',
            'tax_id' => '456-789-123-000',
            'is_active' => true,
        ]);

        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'UnionBank Corporate Checking',
            'bank_name' => 'UnionBank',
            'account_number' => '777-222-999',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 100000.00,
            'is_active' => true,
        ]);

        $resPo = $this->actingAs($this->user)->postJson(route('purchase.api.orders.create'), [
            'vendor_id' => (string) $vendor->id,
            'vendor_name' => $vendor->legal_name,
            'vendor_trade_name' => $vendor->trade_name,
            'status' => 'Approved / Issued',
            'items' => [
                [
                    'sku' => 'SPICE-2307-1',
                    'name' => 'Whole Black Peppercorns',
                    'unit' => 'KG',
                    'quantity' => 2,
                    'unitPrice' => 500.00,
                ],
            ],
        ]);
        $resPo->assertStatus(200);
        $poId = $resPo->json('data.po.id');
        $poNumber = $resPo->json('data.po_number');

        $bill = VendorBillHeader::where('purchase_order_id', (string) $poId)->firstOrFail();
        $this->assertEquals('POSTED', $bill->vendor_bill_status);
        $this->assertEquals(1000.00, (float) $bill->bill_total_amount);

        // Verify Tab 4 Vendor Open Balances renders expandable pending PO row for this vendor
        $resViewBefore = $this->actingAs($this->user)->get(route('accounting.ap'));
        $resViewBefore->assertStatus(200);
        $resViewBefore->assertSee('btnApExpandVendorPos', false);
        $resViewBefore->assertSee('ap-vendor-pos-detail-row', false);
        $resViewBefore->assertSee($poNumber, false);

        // 1. Pay bill principal only (BILL_ONLY) -> zero EWT, no BIR 2307 issued yet
        $resBillOnly = $this->actingAs($this->user)->postJson(route('accounting.api.ap.payments.save'), [
            'payment' => [
                'vendor_id' => (string) $vendor->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'settlement_mode' => 'BILL_ONLY',
                'bank_account_id' => $bankAccount->bank_account_id,
                'reference_number' => 'TRF-BILL-ONLY-1000',
                'total_amount' => 1000.00,
                'total_ewt_amount' => 0.00,
                'total_net_amount' => 1000.00,
            ],
            'allocations' => [
                [
                    'vendor_bill_id' => $bill->vendor_bill_id,
                    'allocated_amount' => 1000.00,
                    'ewt_amount' => 0.00,
                    'ewt_rate_pct' => 0.00,
                ],
            ],
        ]);
        $resBillOnly->assertStatus(200)->assertJsonPath('success', true);

        $bill->refresh();
        $this->assertEquals('PAID', $bill->vendor_bill_status);
        $this->assertEquals(1000.00, (float) $bill->bill_paid_amount);

        $billOnlyPmt = AcctVendorPayment::where('vendor_id', (string) $vendor->id)
            ->orderBy('payment_number')
            ->firstOrFail();
        $this->assertEquals(0.00, (float) $billOnlyPmt->ewt_amount);
        $this->assertSame(
            0,
            AcctBir2307Certificate::where('vendor_payment_id', $billOnlyPmt->payment_id)->count()
        );

        $resViewMid = $this->actingAs($this->user)->get(route('accounting.ap'));
        $resViewMid->assertStatus(200);
        $resViewMid->assertSee('btnApSeparate2307', false);

        // 2. Issue separate EWT / BIR 2307 voucher (EWT_2307_ONLY) on already-paid bill
        $resEwtOnly = $this->actingAs($this->user)->postJson(route('accounting.api.ap.payments.save'), [
            'payment' => [
                'vendor_id' => (string) $vendor->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'settlement_mode' => 'EWT_2307_ONLY',
                'bank_account_id' => $bankAccount->bank_account_id,
                'reference_number' => 'BIR-2307-ONLY-10',
                'total_amount' => 10.00,
                'total_ewt_amount' => 10.00,
                'total_net_amount' => 0.00,
            ],
            'allocations' => [
                [
                    'vendor_bill_id' => $bill->vendor_bill_id,
                    'allocated_amount' => 10.00,
                    'ewt_amount' => 10.00,
                    'ewt_rate_pct' => 1.0,
                    'ewt_tax_code' => 'WC158',
                ],
            ],
        ]);
        $resEwtOnly->assertStatus(200)->assertJsonPath('success', true);

        $ewtOnlyPmt = AcctVendorPayment::where('vendor_id', (string) $vendor->id)
            ->where('payment_id', '!=', $billOnlyPmt->payment_id)
            ->firstOrFail();

        $cert = AcctBir2307Certificate::where('vendor_payment_id', $ewtOnlyPmt->payment_id)->first();
        $this->assertNotNull($cert);
        $this->assertEquals('WC158', $cert->atc_code);
        $this->assertEquals(10.00, (float) $cert->amount_withheld);
        $this->assertEquals(1000.00, (float) $cert->gross_income_payment);

        // Must NOT falsely generate an overpayment vendor credit memo under EWT_2307_ONLY
        $this->assertSame(
            0,
            AcctVendorMemo::where('vendor_id', (string) $vendor->id)
                ->where('memo_type', 'CREDIT')
                ->count()
        );

        $resViewAfter = $this->actingAs($this->user)->get(route('accounting.ap'));
        $resViewAfter->assertStatus(200);
        $resViewAfter->assertSee('btnApView2307', false);
    }
}

