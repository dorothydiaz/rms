<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\AcctSalesInvoiceLine;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Accounting\VendorBillLine;
use App\Models\Purchase\ProcurementVendor;
use App\Models\User;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingReportService;
use App\Services\Accounting\AccountingSeedService;
use App\Services\Accounting\AccountingTaxReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingWave4ReportsAndTaxTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AccountingSeedService $seedService;
    protected AccountingPostingService $postingService;
    protected AccountingTaxReportService $taxService;
    protected AccountingReportService $reportService;
    protected string $companyId = '1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        $this->actingAs($this->user);

        $this->seedService = app(AccountingSeedService::class);
        $this->postingService = app(AccountingPostingService::class);
        $this->taxService = app(AccountingTaxReportService::class);
        $this->reportService = app(AccountingReportService::class);

        $this->seedService->ensureSeeded($this->companyId);
        AcctFiscalPeriod::where('company_id', $this->companyId)->update(['status' => 'OPEN']);
    }

    public function test_bir_2550q_quarterly_vat_return_computation(): void
    {
        $customer = AcctCustomer::firstOrCreate(
            ['company_id' => $this->companyId, 'customer_code' => 'CUST-TAX01'],
            [
                'customer_name' => 'Metro Retail Corp.',
                'tin' => '123-456-789-000',
                'address' => 'Makati City',
                'credit_limit' => 500000.00,
            ]
        );

        // 1. Post Vatable Sales Invoice (Net 100,000 + 12,000 Output VAT)
        $invoice = AcctSalesInvoice::create([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'customer_id' => $customer->customer_id,
            'customer_name' => $customer->customer_name,
            'invoice_number' => 'INV-2026-TAX01',
            'invoice_date' => '2026-02-10',
            'due_date' => '2026-03-10',
            'subtotal_amount' => 100000.00,
            'tax_amount' => 12000.00,
            'total_amount' => 112000.00,
            'balance_due' => 112000.00,
            'status' => 'POSTED',
        ]);

        AcctSalesInvoiceLine::create([
            'invoice_id' => $invoice->invoice_id,
            'line_number' => 1,
            'description' => 'Catering Package Delivery',
            'quantity' => 1,
            'unit_price' => 100000.00,
            'tax_code' => 'VAT_12',
            'tax_amount' => 12000.00,
            'subtotal' => 100000.00,
            'total' => 112000.00,
        ]);

        // 2. Post Goods Vendor Bill (Net 40,000 + 4,800 Input VAT)
        $goodsBill = VendorBillHeader::create([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'vendor_id' => 'VEN-SUPPLIES',
            'vendor_name' => 'Kitchen Supplies Co.',
            'vendor_bill_number' => 'BILL-2026-G01',
            'vendor_invoice_number' => 'INV-KITCHEN-1',
            'vendor_invoice_date' => '2026-02-15',
            'bill_received_date' => '2026-02-15',
            'bill_due_date' => '2026-03-15',
            'bill_subtotal_amount' => 40000.00,
            'bill_tax_amount' => 4800.00,
            'bill_total_amount' => 44800.00,
            'vendor_bill_status' => 'POSTED',
        ]);

        VendorBillLine::create([
            'vendor_bill_id' => $goodsBill->vendor_bill_id,
            'item_id' => (string) \Illuminate\Support\Str::uuid(),
            'line_number' => 1,
            'purchase_uom_code' => 'KG',
            'bill_line_description' => 'Kitchen Food Ingredients',
            'bill_quantity' => 40,
            'bill_unit_price' => 1000.00,
            'bill_line_subtotal' => 40000.00,
            'bill_line_total' => 44800.00,
        ]);

        // 3. Post Capital Goods Vendor Bill (Net 50,000 + 6,000 Input VAT)
        $faClearAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'FIXED_ASSET_CLEARING')->firstOrFail();

        $capitalBill = VendorBillHeader::create([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'vendor_id' => 'VEN-CAPITAL',
            'vendor_name' => 'Metro Refrigeration Works',
            'vendor_bill_number' => 'BILL-2026-CAP01',
            'vendor_invoice_number' => 'INV-FRIDGE-99',
            'vendor_invoice_date' => '2026-02-20',
            'bill_received_date' => '2026-02-20',
            'bill_due_date' => '2026-03-20',
            'bill_subtotal_amount' => 50000.00,
            'bill_tax_amount' => 6000.00,
            'bill_total_amount' => 56000.00,
            'vendor_bill_status' => 'POSTED',
        ]);

        VendorBillLine::create([
            'vendor_bill_id' => $capitalBill->vendor_bill_id,
            'item_id' => (string) \Illuminate\Support\Str::uuid(),
            'line_number' => 1,
            'purchase_uom_code' => 'UNIT',
            'bill_line_description' => 'Walk-in Chiller Compressor Unit',
            'bill_quantity' => 1,
            'bill_unit_price' => 50000.00,
            'bill_line_subtotal' => 50000.00,
            'bill_line_total' => 56000.00,
            'gl_expense_account_id' => $faClearAccount->account_id,
        ]);

        // 4. Issue a Customer BIR 2307 Creditable Withholding Tax Certificate
        AcctBir2307Certificate::create([
            'certificate_number' => '2307-CUST-2026-001',
            'company_id' => $this->companyId,
            'party_type' => 'CUSTOMER',
            'party_id' => $customer->customer_id,
            'party_name' => $customer->customer_name,
            'party_tin' => $customer->tin,
            'source_document_type' => 'COLLECTION_RECEIPT',
            'source_document_id' => (string) \Illuminate\Support\Str::uuid(),
            'source_document_number' => 'CR-2026-0001',
            'period_from' => '2026-01-01',
            'period_to' => '2026-03-31',
            'tax_code' => 'CWT_1',
            'atc_code' => 'WC158',
            'gross_income_payment' => 100000.00,
            'amount_withheld' => 1000.00,
            'status' => 'RECEIVED',
        ]);

        // Execute 2550Q Generation for Q1 2026
        $report = $this->taxService->generate2550Q($this->companyId, 2026, 1);

        $part2 = $report['part2_computation'];

        // Output Tax: 12,000.00
        $this->assertEquals(100000.00, $part2['line_15a_vatable_sales']);
        $this->assertEquals(12000.00, $part2['line_15a_output_tax']);

        // Input Tax: 6,000 (Capital) + 4,800 (Goods) = 10,800.00
        $this->assertEquals(50000.00, $part2['line_16a_capital_goods_purchases']);
        $this->assertEquals(6000.00, $part2['line_16a_capital_goods_input_tax']);
        $this->assertEquals(40000.00, $part2['line_16b_goods_purchases']);
        $this->assertEquals(4800.00, $part2['line_16b_goods_input_tax']);
        $this->assertEquals(10800.00, $part2['line_16i_total_allowable_input_tax']);

        // Net VAT Payable = 12,000 - 10,800 = 1,200.00
        $this->assertEquals(1200.00, $part2['line_19_net_vat_payable']);

        // Less CWT 2307 = 1,000.00 -> Still Payable = 200.00
        $this->assertEquals(1000.00, $part2['line_20b_creditable_vat_withheld']);
        $this->assertEquals(200.00, $part2['line_22_total_tax_still_payable']);
    }

    public function test_slsp_summary_list_of_sales_and_purchases(): void
    {
        $slsp = $this->taxService->generateSLSP($this->companyId, 2026, 1);

        $this->assertArrayHasKey('sls', $slsp);
        $this->assertArrayHasKey('slp', $slsp);

        // SLS Totals
        $this->assertArrayHasKey('totals', $slsp['sls']);
        $this->assertArrayHasKey('totals', $slsp['slp']);
        $this->assertIsArray($slsp['sls']['rows']);
        $this->assertIsArray($slsp['slp']['rows']);
    }

    public function test_bir_1601eq_and_qap_generation_from_issued_2307(): void
    {
        // Issue 2 certificates to vendors:
        // 1. Goods 1% EWT (WC158)
        AcctBir2307Certificate::create([
            'certificate_number' => '2307-VEN-001',
            'company_id' => $this->companyId,
            'party_type' => 'VENDOR',
            'party_id' => (string) \Illuminate\Support\Str::uuid(),
            'party_name' => 'Agri Supplies Inc.',
            'party_tin' => '999-888-777-000',
            'source_document_type' => 'VENDOR_PAYMENT',
            'source_document_id' => (string) \Illuminate\Support\Str::uuid(),
            'source_document_number' => 'PV-2026-0001',
            'period_from' => '2026-01-01',
            'period_to' => '2026-03-31',
            'tax_code' => 'EWT_1',
            'atc_code' => 'WC158',
            'gross_income_payment' => 50000.00,
            'amount_withheld' => 500.00,
            'status' => 'ISSUED',
        ]);

        // 2. Rent 5% EWT (WI100)
        AcctBir2307Certificate::create([
            'certificate_number' => '2307-VEN-002',
            'company_id' => $this->companyId,
            'party_type' => 'VENDOR',
            'party_id' => (string) \Illuminate\Support\Str::uuid(),
            'party_name' => 'Prime Realty Group',
            'party_tin' => '111-222-333-000',
            'source_document_type' => 'VENDOR_PAYMENT',
            'source_document_id' => (string) \Illuminate\Support\Str::uuid(),
            'source_document_number' => 'PV-2026-0002',
            'period_from' => '2026-01-01',
            'period_to' => '2026-03-31',
            'tax_code' => 'EWT_5',
            'atc_code' => 'WI100',
            'gross_income_payment' => 40000.00,
            'amount_withheld' => 2000.00,
            'status' => 'ISSUED',
        ]);

        // Test 1601-EQ
        $eq = $this->taxService->generate1601EQ($this->companyId, 2026, 1);
        $this->assertEquals(2500.00, $eq['total_remittance_due']);
        $this->assertEquals(90000.00, $eq['total_tax_base']);
        $this->assertCount(2, $eq['schedule_1']);

        // Test QAP
        $qap = $this->taxService->generateQAP($this->companyId, 2026, 1);
        $this->assertEquals(2500.00, $qap['total_withheld']);
        $this->assertEquals(90000.00, $qap['total_gross']);
        $this->assertCount(2, $qap['payees']);
        $this->assertEquals('2307-VEN-001', $qap['payees'][0]['certificate_number'] ?? $qap['payees'][1]['certificate_number']);
    }

    public function test_sawt_summary_statement_for_customer_cwt_2307(): void
    {
        AcctBir2307Certificate::create([
            'certificate_number' => 'SAWT-CERT-01',
            'company_id' => $this->companyId,
            'party_type' => 'CUSTOMER',
            'party_id' => (string) \Illuminate\Support\Str::uuid(),
            'party_name' => 'Hotel Apex Manila',
            'party_tin' => '555-444-333-000',
            'source_document_type' => 'COLLECTION_RECEIPT',
            'source_document_id' => (string) \Illuminate\Support\Str::uuid(),
            'source_document_number' => 'CR-2026-0055',
            'period_from' => '2026-01-01',
            'period_to' => '2026-03-31',
            'tax_code' => 'CWT_1',
            'atc_code' => 'WC158',
            'gross_income_payment' => 80000.00,
            'amount_withheld' => 800.00,
            'status' => 'RECEIVED',
        ]);

        $sawt = $this->taxService->generateSAWT($this->companyId, 2026, 1);

        $this->assertGreaterThanOrEqual(800.00, $sawt['total_tax_withheld']);
        $this->assertGreaterThanOrEqual(80000.00, $sawt['total_gross']);
        $this->assertNotEmpty($sawt['customers']);
    }

    public function test_trial_balance_debits_equal_credits_and_balances(): void
    {
        $tb = $this->reportService->generateTrialBalance($this->companyId, '2026-12-31');

        $this->assertTrue($tb['is_balanced'], 'Trial balance must be balanced (Dr == Cr)');
        $this->assertEquals(0.00, $tb['difference']);
        $this->assertGreaterThan(0, $tb['total_debit']);
        $this->assertGreaterThan(0, $tb['total_credit']);
        $this->assertEquals($tb['total_debit'], $tb['total_credit']);
        $this->assertNotEmpty($tb['rows']);
    }

    public function test_income_statement_revenue_cogs_and_net_income(): void
    {
        $is = $this->reportService->generateIncomeStatement($this->companyId, '2026-01-01', '2026-12-31');

        $this->assertArrayHasKey('revenue', $is);
        $this->assertArrayHasKey('cogs', $is);
        $this->assertArrayHasKey('gross_profit', $is);
        $this->assertArrayHasKey('operating_expenses', $is);
        $this->assertArrayHasKey('net_income', $is);

        $expectedGrossProfit = round($is['revenue']['total'] - $is['cogs']['total'], 2);
        $this->assertEquals($expectedGrossProfit, $is['gross_profit']);

        $expectedNetIncome = round($is['gross_profit'] - $is['operating_expenses']['total'], 2);
        $this->assertEquals($expectedNetIncome, $is['net_income']);
    }

    public function test_balance_sheet_assets_equal_liabilities_plus_equity(): void
    {
        $bs = $this->reportService->generateBalanceSheet($this->companyId, '2026-12-31');

        $this->assertArrayHasKey('assets', $bs);
        $this->assertArrayHasKey('liabilities', $bs);
        $this->assertArrayHasKey('equity', $bs);
        $this->assertArrayHasKey('total_liabilities_and_equity', $bs);

        $this->assertTrue($bs['is_balanced'], "Balance Sheet must satisfy Assets = Liabilities + Equity (Diff: {$bs['difference']})");
        $this->assertEquals(0.00, $bs['difference']);
        $this->assertEquals($bs['assets']['total_assets'], $bs['total_liabilities_and_equity']);
    }

    public function test_cash_flow_statement_operating_investing_financing(): void
    {
        $cfs = $this->reportService->generateCashFlowStatement($this->companyId, '2026-01-01', '2026-12-31');

        $this->assertArrayHasKey('beginning_cash', $cfs);
        $this->assertArrayHasKey('operating_activities', $cfs);
        $this->assertArrayHasKey('investing_activities', $cfs);
        $this->assertArrayHasKey('financing_activities', $cfs);
        $this->assertArrayHasKey('net_cash_flow', $cfs);
        $this->assertArrayHasKey('ending_cash', $cfs);

        $expectedEnding = round($cfs['beginning_cash'] + $cfs['net_cash_flow'], 2);
        $this->assertEquals($expectedEnding, $cfs['ending_cash']);
    }

    public function test_landed_cost_split_consumed_portion_to_cogs_and_remaining_to_inventory(): void
    {
        // Architectural Rule: Allocate landed cost to remaining on-hand quantity; send consumed portion to COGS.
        // Simulate a stock receipt of 100 units, where 40% was already consumed (consumed_ratio = 0.40)
        $stockInId = (string) \Illuminate\Support\Str::uuid();

        $result = $this->reportService->allocateLandedCostWithConsumedStock(
            $this->companyId,
            $stockInId,
            [
                ['amount' => 10000.00, 'cost_type' => 'FREIGHT'],
                'consumed_ratio' => 0.40,
            ],
            'BR-01'
        );

        $this->assertEquals(10000.00, $result['total_landed_cost']);
        $this->assertEquals(0.60, $result['remaining_ratio']);
        $this->assertEquals(0.40, $result['consumed_ratio']);
        $this->assertEquals(6000.00, $result['inventory_capitalized_amount']);
        $this->assertEquals(4000.00, $result['cogs_expensed_amount']);

        // Verify GL Journal
        $journal = GlJournalHeader::with('lines.account')->findOrFail($result['journal_id']);
        $this->assertEquals('POSTED', $journal->status);
        $this->assertEquals(10000.00, (float) $journal->total_debit);
        $this->assertEquals(10000.00, (float) $journal->total_credit);

        // Lines check:
        // DR INVENTORY_ASSET 6,000.00
        // DR COGS 4,000.00
        // CR ACCOUNTS_PAYABLE 10,000.00
        $roleMap = $journal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(6000.00, $roleMap['INVENTORY_ASSET']);
        $this->assertEquals(4000.00, $roleMap['COGS']);
        $this->assertEquals(-10000.00, $roleMap['ACCOUNTS_PAYABLE']);
    }

    public function test_ap_and_ar_aging_buckets(): void
    {
        $apAging = $this->reportService->generateApAging($this->companyId);
        $this->assertArrayHasKey('vendors', $apAging);
        $this->assertArrayHasKey('summary', $apAging);
        $this->assertArrayHasKey('current', $apAging['summary']);
        $this->assertArrayHasKey('days_1_30', $apAging['summary']);
        $this->assertArrayHasKey('days_31_60', $apAging['summary']);
        $this->assertArrayHasKey('days_61_90', $apAging['summary']);
        $this->assertArrayHasKey('over_90', $apAging['summary']);

        $arAging = $this->reportService->generateArAging($this->companyId);
        $this->assertArrayHasKey('customers', $arAging);
        $this->assertArrayHasKey('summary', $arAging);
        $this->assertArrayHasKey('current', $arAging['summary']);
        $this->assertArrayHasKey('days_1_30', $arAging['summary']);
        $this->assertArrayHasKey('days_31_60', $arAging['summary']);
        $this->assertArrayHasKey('days_61_90', $arAging['summary']);
        $this->assertArrayHasKey('over_90', $arAging['summary']);
    }

    public function test_reports_and_tax_controller_api_endpoints(): void
    {
        // 1. GET Tax Report (2550Q)
        $taxRes = $this->getJson(route('accounting.api.reports.tax', [
            'report_type' => '2550Q',
            'year' => 2026,
            'quarter' => 1,
        ]));
        $taxRes->assertOk();
        $taxRes->assertJsonPath('success', true);
        $taxRes->assertJsonPath('report_type', '2550Q');

        // 2. GET Financial Report (trial-balance)
        $tbRes = $this->getJson(route('accounting.api.reports.financial', [
            'report_type' => 'trial-balance',
            'as_of_date' => '2026-12-31',
        ]));
        $tbRes->assertOk();
        $tbRes->assertJsonPath('success', true);
        $tbRes->assertJsonPath('data.is_balanced', true);

        // 3. POST Landed Cost Allocation
        $lcRes = $this->postJson(route('accounting.api.landed-cost.allocate'), [
            'stock_in_id' => (string) \Illuminate\Support\Str::uuid(),
            'branch_id' => 'BR-01',
            'landed_costs' => [
                ['amount' => 5000.00, 'cost_type' => 'CUSTOMS'],
                'consumed_ratio' => 0.20,
            ],
        ]);
        $lcRes->assertOk();
        $lcRes->assertJsonPath('success', true);
        $lcRes->assertJsonPath('data.total_landed_cost', 5000);
        $lcRes->assertJsonPath('data.inventory_capitalized_amount', 4000);
        $lcRes->assertJsonPath('data.cogs_expensed_amount', 1000);
    }
}
