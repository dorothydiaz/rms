<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctBankAccount;
use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctCheckRegister;
use App\Models\Accounting\AcctCollectionReceipt;
use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctEmployeeAdvance;
use App\Models\Accounting\AcctFundTransfer;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\AcctVendorMemo;
use App\Models\Accounting\AcctVendorPayment;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Accounting\VendorBillLine;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\GoodsReceiptItem;
use App\Models\Purchase\ProcurementVendor;
use App\Models\User;
use App\Services\Accounting\AccountingApService;
use App\Services\Accounting\AccountingArService;
use App\Services\Accounting\AccountingCashBankService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingWave2ApArCashTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AccountingSeedService $seedService;
    protected AccountingPostingService $postingService;
    protected AccountingApService $apService;
    protected AccountingArService $arService;
    protected AccountingCashBankService $cashBankService;
    protected string $companyId = '1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();

        $this->seedService = app(AccountingSeedService::class);
        $this->postingService = app(AccountingPostingService::class);
        $this->apService = app(AccountingApService::class);
        $this->arService = app(AccountingArService::class);
        $this->cashBankService = app(AccountingCashBankService::class);

        $this->seedService->ensureSeeded($this->companyId);
    }

    public function test_ap_3way_matching_and_purchase_price_variance_gl_posting(): void
    {
        // 1. Create Vendor
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-001',
            'legal_name' => 'Atlantic Food Supplies Inc.',
            'trade_name' => 'Atlantic Foods',
            'tax_id' => '123-456-789-000',
            'payment_terms' => 'NET30',
            'is_active' => true,
        ]);

        // 2. Simulate Goods Receipt (GRN) with line cost 100.00
        $grn = GoodsReceipt::create([
            'grn_number' => 'GRN-2026-0001',
            'po_number' => 'PO-2026-0001',
            'vendor_id' => (string) $vendor->id,
            'vendor_name' => $vendor->trade_name,
            'status' => 'RECEIVED',
            'received_at' => now(),
            'received_by' => 'Staff Receiver',
            'gross_total' => 1000.00,
        ]);

        $grnLine = GoodsReceiptItem::create([
            'goods_receipt_id' => $grn->id,
            'sku' => 'SKU-BEEF-01',
            'item_name' => 'Prime Beef Tenderloin',
            'uom' => 'KG',
            'received_qty' => 10.0,
            'unit_cost' => 100.00,
            'total_cost' => 1000.00,
        ]);

        // 3. Vendor Bill received at 105.00/unit (higher than GRN cost of 100.00 -> 5.00/unit PPV variance)
        $headerData = [
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'vendor_id' => (string) $vendor->id,
            'vendor_invoice_number' => 'INV-ATL-9901',
            'vendor_invoice_date' => now()->toDateString(),
            'bill_due_date' => now()->addDays(30)->toDateString(),
            'purchase_order_id' => 'PO-2026-0001',
            'bill_currency_code' => 'PHP',
            'bill_exchange_rate' => 1.0,
            'bill_shipping_amount' => 50.00,
        ];

        $linesData = [
            [
                'item_description' => 'Prime Beef Tenderloin',
                'quantity' => 10.0,
                'unit_price' => 105.00, // 1050.00 subtotal vs 1000.00 GRN -> 50.00 Purchase Price Variance
                'grn_unit_cost' => 100.00,
                'stock_in_line_id' => (string) $grnLine->id,
                'tax_code' => 'VAT_12', // 12% on 1050.00 = 126.00
                'uom' => 'KG',
            ],
        ];

        $bill = $this->apService->saveDraftOrPostBill($headerData, $linesData, 'POSTED');

        $this->assertEquals('POSTED', $bill->vendor_bill_status);
        $this->assertEquals('DISCREPANCY', $bill->bill_match_status); // Price variance detected
        $this->assertEquals(1050.00, (float) $bill->bill_subtotal_amount);
        $this->assertEquals(126.00, (float) $bill->bill_tax_amount);
        $this->assertEquals(1226.00, (float) $bill->bill_total_amount); // 1050 + 126 + 50 shipping
        $this->assertEquals(0.00, (float) $bill->bill_paid_amount);

        // Verify Journal Lines:
        // Debits: GRNI (1000.00), PPV (50.00), Input VAT (126.00), Shipping/Freight (50.00) = 1226.00
        // Credit: AP Trade = 1226.00
        $journal = GlJournalHeader::with('lines.account')
            ->where('source_document_id', $bill->vendor_bill_id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('POSTED', $journal->status);
        $this->assertEquals(1226.00, (float) $journal->total_debit);
        $this->assertEquals(1226.00, (float) $journal->total_credit);

        $lineRoles = $journal->lines->mapWithKeys(fn($l) => [$l->account->system_role ?? $l->description => (float) $l->base_debit - (float) $l->base_credit]);

        $this->assertEquals(1000.00, $lineRoles['GRNI_ACCRUED_PURCHASES'] ?? 0);
        $this->assertEquals(50.00, $lineRoles['PURCHASE_PRICE_VARIANCE'] ?? 0);
        $this->assertEquals(126.00, $lineRoles['INPUT_VAT'] ?? 0);
        $this->assertEquals(-1226.00, $lineRoles['ACCOUNTS_PAYABLE'] ?? 0);
    }

    public function test_ap_vendor_payment_voucher_with_ewt_and_bir_2307_issuance(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-002',
            'legal_name' => 'Metro Logistics Corp.',
            'trade_name' => 'Metro Logistics',
            'tax_id' => '987-654-321-000',
            'payment_terms' => 'NET15',
            'is_active' => true,
        ]);

        // Post a direct service bill for 10,000 + 12% VAT = 11,200.00
        $expAccount = ChartOfAccount::where('company_id', $this->companyId)->where('account_type', 'EXPENSE')->firstOrFail();

        $bill = $this->apService->saveDraftOrPostBill(
            [
                'company_id' => $this->companyId,
                'vendor_id' => (string) $vendor->id,
                'vendor_invoice_number' => 'INV-LOG-1002',
                'vendor_invoice_date' => now()->toDateString(),
                'bill_due_date' => now()->addDays(15)->toDateString(),
            ],
            [
                [
                    'item_description' => 'Delivery and Hauling Services',
                    'quantity' => 1,
                    'unit_price' => 10000.00,
                    'tax_code' => 'VAT_12',
                    'gl_expense_account_id' => $expAccount->account_id,
                ],
            ],
            'POSTED'
        );

        // Bank Account for disbursement
        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BDO Operating Checking',
            'bank_name' => 'BDO',
            'account_number' => '001-234-5678',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 100000.00,
            'is_active' => true,
        ]);

        // Pay full 11,200.00 bill with 2% EWT (200.00 on 10,000 subtotal) -> Net disbursement: 11,000.00
        $payment = $this->apService->recordVendorPayment(
            [
                'company_id' => $this->companyId,
                'vendor_id' => (string) $vendor->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'bank_account_id' => $bankAccount->bank_account_id,
                'reference_number' => 'TRF-ONLINE-9921',
                'total_amount' => 11200.00,
                'total_ewt_amount' => 200.00,
                'total_net_amount' => 11000.00,
            ],
            [
                [
                    'vendor_bill_id' => $bill->vendor_bill_id,
                    'allocated_amount' => 11200.00,
                    'ewt_amount' => 200.00,
                ],
            ]
        );

        $this->assertEquals('POSTED', $payment->status);
        $this->assertEquals(11200.00, (float) $payment->total_amount);
        $this->assertEquals(200.00, (float) $payment->total_ewt_amount);
        $this->assertEquals(11000.00, (float) $payment->total_net_amount);

        // Verify Bill is now marked PAID
        $bill->refresh();
        $this->assertEquals('PAID', $bill->vendor_bill_status);
        $this->assertEquals(11200.00, (float) $bill->bill_paid_amount);

        // Verify BIR Form 2307 certificate was automatically created
        $cert = AcctBir2307Certificate::where('vendor_payment_id', $payment->payment_id)->first();
        $this->assertNotNull($cert);
        $this->assertEquals($vendor->tax_id, $cert->payee_tin);
        $this->assertEquals(10000.00, (float) $cert->gross_income_payment);
        $this->assertEquals(200.00, (float) $cert->amount_withheld);

        // Verify Payment GL Journal:
        // Dr AP_TRADE 11,200.00
        // Cr EWT_PAYABLE 200.00
        // Cr CASH_IN_BANK 11,000.00
        $pmtJournal = GlJournalHeader::with('lines.account')
            ->where('source_document_id', $payment->payment_id)
            ->first();

        $this->assertNotNull($pmtJournal);
        $this->assertEquals(11200.00, (float) $pmtJournal->total_debit);
        $this->assertEquals(11200.00, (float) $pmtJournal->total_credit);

        $jRoles = $pmtJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(11200.00, $jRoles['ACCOUNTS_PAYABLE']);
        $this->assertEquals(-200.00, $jRoles['EWT_PAYABLE']);
        $this->assertEquals(-11000.00, $jRoles['CASH_IN_BANK']);
    }

    public function test_ap_vendor_debit_and_credit_memo_application(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-003',
            'legal_name' => 'San Juan Wholesale',
            'trade_name' => 'San Juan',
            'tax_id' => '111-222-333-000',
            'is_active' => true,
        ]);

        $memo = $this->apService->createVendorMemo([
            'company_id' => $this->companyId,
            'vendor_id' => (string) $vendor->id,
            'memo_type' => 'CREDIT_MEMO', // Vendor credit memo reduces company payable
            'memo_date' => now()->toDateString(),
            'amount' => 1500.00,
            'reason_code' => 'PRICE_DISCREPANCY',
            'reason_description' => 'Supplier discount applied after billing',
        ]);

        $this->assertEquals('POSTED', $memo->status);
        $this->assertEquals(1500.00, (float) $memo->amount);

        // Verify GL: Dr AP_TRADE 1500.00, Cr INVENTORY_ASSET 1500.00
        $memoJournal = GlJournalHeader::where('source_document_id', $memo->memo_id)->first();
        $this->assertNotNull($memoJournal);
        $this->assertEquals(1500.00, (float) $memoJournal->total_debit);
        $this->assertEquals(1500.00, (float) $memoJournal->total_credit);
    }

    public function test_ar_sales_invoice_creation_with_credit_limit_validation_and_output_vat(): void
    {
        $customer = AcctCustomer::create([
            'company_id' => $this->companyId,
            'customer_code' => 'CUST-001',
            'customer_name' => 'Gourmet Bistros Corp.',
            'tin' => '555-666-777-000',
            'credit_limit' => 20000.00,
            'current_balance' => 15000.00, // Available credit: 5000.00
            'is_active' => true,
        ]);

        // Attempting to post an invoice that exceeds credit limit (e.g. 6,000 > 5,000 available credit)
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/exceeds customer credit limit/i');

        $this->arService->saveDraftOrPostInvoice(
            [
                'company_id' => $this->companyId,
                'customer_id' => $customer->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
            ],
            [
                [
                    'description' => 'Catering Food Supplies',
                    'quantity' => 1,
                    'unit_price' => 6000.00,
                    'tax_code' => 'EXEMPT',
                ],
            ],
            'POSTED'
        );
    }

    public function test_ar_sales_invoice_posting_and_collection_receipt_with_cwt_2307(): void
    {
        $customer = AcctCustomer::create([
            'company_id' => $this->companyId,
            'customer_code' => 'CUST-002',
            'customer_name' => 'Metro Fine Dining',
            'tin' => '777-888-999-000',
            'credit_limit' => 50000.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        // Post Sales Invoice: 10,000.00 + 12% Output VAT = 11,200.00
        $invoice = $this->arService->saveDraftOrPostInvoice(
            [
                'company_id' => $this->companyId,
                'customer_id' => $customer->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
            ],
            [
                [
                    'description' => 'Packaged Ready-to-Cook Sauces',
                    'quantity' => 10,
                    'unit_price' => 1000.00,
                    'tax_code' => 'VAT_12',
                ],
            ],
            'POSTED'
        );

        $this->assertEquals('POSTED', $invoice->status);
        $this->assertEquals(11200.00, (float) $invoice->total_amount);
        $this->assertEquals(11200.00, (float) $invoice->balance_due);

        // Verify Customer balance updated
        $customer->refresh();
        $this->assertEquals(11200.00, (float) $customer->current_balance);

        // Verify Invoice GL:
        // Dr ACCOUNTS_RECEIVABLE 11,200.00
        // Cr SALES_REVENUE 10,000.00
        // Cr OUTPUT_VAT 1,200.00
        $invJournal = GlJournalHeader::with('lines.account')->where('source_document_id', $invoice->invoice_id)->first();
        $this->assertNotNull($invJournal);
        $jRoles = $invJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(11200.00, $jRoles['ACCOUNTS_RECEIVABLE']);
        $this->assertEquals(-10000.00, $jRoles['SALES_REVENUE']);
        $this->assertEquals(-1200.00, $jRoles['OUTPUT_VAT']);

        // Collect payment with 1% CWT 2307 received deduction (100.00 on 10,000 subtotal)
        // Net Cash Received: 11,100.00
        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BPI Operations',
            'bank_name' => 'BPI',
            'account_number' => '998-001-2233',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);

        $receipt = $this->arService->recordCollectionReceipt(
            [
                'company_id' => $this->companyId,
                'customer_id' => $customer->customer_id,
                'receipt_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'bank_account_id' => $bankAccount->bank_account_id,
                'reference_number' => 'DEP-BPI-5512',
                'bir_2307_form_number' => '2307-CUST-Q4-01',
                'bir_2307_quarter' => 'Q4',
                'total_amount' => 11200.00,
                'total_cwt_amount' => 100.00,
                'total_net_amount' => 11100.00,
            ],
            [
                [
                    'sales_invoice_id' => $invoice->invoice_id,
                    'allocated_amount' => 11200.00,
                    'cwt_amount' => 100.00,
                ],
            ]
        );

        $this->assertEquals('POSTED', $receipt->status);
        $this->assertEquals(11200.00, (float) $receipt->total_amount);
        $this->assertEquals(100.00, (float) $receipt->total_cwt_amount);
        $this->assertEquals(11100.00, (float) $receipt->total_net_amount);

        // Verify invoice balance is now 0.00 and status is PAID
        $invoice->refresh();
        $this->assertEquals('PAID', $invoice->status);
        $this->assertEquals(0.00, (float) $invoice->balance_due);

        // Verify Customer balance decremented back to 0.00
        $customer->refresh();
        $this->assertEquals(0.00, (float) $customer->current_balance);

        // Verify Receipt GL:
        // Dr CASH_IN_BANK 11,100.00
        // Dr CWT_RECEIVABLE 100.00
        // Cr ACCOUNTS_RECEIVABLE 11,200.00
        $rcpJournal = GlJournalHeader::with('lines.account')->where('source_document_id', $receipt->receipt_id)->first();
        $this->assertNotNull($rcpJournal);
        $rRoles = $rcpJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(11100.00, $rRoles['CASH_IN_BANK']);
        $this->assertEquals(100.00, $rRoles['CWT_RECEIVABLE']);
        $this->assertEquals(-11200.00, $rRoles['ACCOUNTS_RECEIVABLE']);
    }

    public function test_ar_daif_bounced_check_reversal_reopens_invoice_and_records_bank_penalty(): void
    {
        $customer = AcctCustomer::create([
            'company_id' => $this->companyId,
            'customer_code' => 'CUST-003',
            'customer_name' => 'Royal Plaza Hotel',
            'tin' => '333-444-555-000',
            'credit_limit' => 100000.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $invoice = $this->arService->saveDraftOrPostInvoice(
            [
                'company_id' => $this->companyId,
                'customer_id' => $customer->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
            ],
            [
                [
                    'description' => 'Hotel Banquet Condiments',
                    'quantity' => 1,
                    'unit_price' => 20000.00,
                    'tax_code' => 'EXEMPT',
                ],
            ],
            'POSTED'
        );

        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'Metrobank Operating',
            'bank_name' => 'Metrobank',
            'account_number' => '444-555-666',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);

        // Customer pays via Check
        $receipt = $this->arService->recordCollectionReceipt(
            [
                'company_id' => $this->companyId,
                'customer_id' => $customer->customer_id,
                'receipt_date' => now()->toDateString(),
                'payment_method' => 'CHECK',
                'bank_account_id' => $bankAccount->bank_account_id,
                'check_number' => 'CHK-880011',
                'check_bank_name' => 'Security Bank',
                'check_date' => now()->toDateString(),
                'total_amount' => 20000.00,
                'total_cwt_amount' => 0.00,
                'total_net_amount' => 20000.00,
            ],
            [
                [
                    'sales_invoice_id' => $invoice->invoice_id,
                    'allocated_amount' => 20000.00,
                    'cwt_amount' => 0.00,
                ],
            ]
        );

        $check = AcctCheckRegister::where('source_document_id', $receipt->receipt_id)->firstOrFail();
        $this->assertContains($check->status, ['RECEIVED', 'DEPOSITED']);

        // Check bounces with DAIF (Drawn Against Insufficient Funds)
        $bouncedCheck = $this->arService->handleBouncedCheck($check->check_id, 'DAIF - Insufficient Funds', 500.00);

        $this->assertEquals('BOUNCED_DAIF', $bouncedCheck->status);
        $this->assertEquals(500.00, (float) $bouncedCheck->bounce_penalty_amount);

        // Verify Sales Invoice is REOPENED with balance due restored!
        $invoice->refresh();
        $this->assertContains($invoice->status, ['POSTED', 'PARTIAL']);
        $this->assertEquals(20000.00, (float) $invoice->balance_due);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);

        // Verify Customer balance restored
        $customer->refresh();
        $this->assertEquals(20000.00, (float) $customer->current_balance);

        // Verify Reversal GL Journal:
        // Dr ACCOUNTS_RECEIVABLE 20,000.00
        // Dr BANK_PENALTY_EXPENSE 500.00
        // Cr CASH_IN_BANK 20,500.00
        $revJournal = GlJournalHeader::with('lines.account')->find($bouncedCheck->bounce_journal_id);
        $this->assertNotNull($revJournal);
        $this->assertEquals(20500.00, (float) $revJournal->total_debit);
        $this->assertEquals(20500.00, (float) $revJournal->total_credit);

        $jRoles = $revJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(20000.00, $jRoles['ACCOUNTS_RECEIVABLE']);
        $this->assertEquals(500.00, $jRoles['BANK_PENALTY_EXPENSE']);
        $this->assertEquals(-20500.00, $jRoles['CASH_IN_BANK']);
    }

    public function test_cash_bank_employee_advance_and_liquidation_isolated_from_ap_trade_aging(): void
    {
        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'Cash in Vault',
            'bank_name' => 'Petty Cash Custody',
            'account_number' => 'PETTY-01',
            'account_type' => 'PETTY_CASH',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_ON_HAND')->value('account_id'),
            'current_balance' => 20000.00,
            'is_active' => true,
        ]);

        // Disburse Advance of 5,000.00 to Employee
        $advance = $this->cashBankService->issueEmployeeAdvance([
            'company_id' => $this->companyId,
            'employee_id' => 'EMP-1001',
            'employee_name' => 'Juan dela Cruz',
            'advance_date' => now()->toDateString(),
            'settlement_due_date' => now()->addDays(15)->toDateString(),
            'amount' => 5000.00,
            'disbursement_account_id' => $bankAccount->bank_account_id,
            'purpose' => 'Warehouse Site Audit Travel Expense',
        ]);

        $this->assertEquals('DISBURSED', $advance->status);
        $this->assertEquals(5000.00, (float) $advance->amount);
        $this->assertEquals(5000.00, (float) $advance->balance_due);

        // Verify Disbursement GL: Dr EMPLOYEE_ADVANCES 5,000.00, Cr CASH_ON_HAND 5,000.00
        $disbJournal = GlJournalHeader::with('lines.account')->find($advance->disbursement_journal_id);
        $this->assertNotNull($disbJournal);
        $jRoles = $disbJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(5000.00, $jRoles['EMPLOYEE_ADVANCES']);
        $this->assertEquals(-5000.00, $jRoles['CASH_ON_HAND']);

        // Verify that AP Aging DOES NOT include this employee advance!
        $apAging = $this->apService->getVendorLedgerAndAging($this->companyId);
        $this->assertEquals(0, collect($apAging['vendor_balances'] ?? [])->where('vendor_code', 'EMP-1001')->count());

        // Employee liquidates: 4,200.00 travel expense + 800.00 refund cash returned to company
        $expAccount = ChartOfAccount::where('company_id', $this->companyId)->where('account_type', 'EXPENSE')->firstOrFail();

        $liq = $this->cashBankService->liquidateEmployeeAdvance(
            [
                'advance_id' => $advance->advance_id,
                'company_id' => $this->companyId,
                'liquidation_date' => now()->toDateString(),
                'refund_bank_account_id' => $bankAccount->bank_account_id,
                'cash_returned_amount' => 800.00,
                'notes' => 'Travel receipts verified',
            ],
            [
                [
                    'gl_expense_account_id' => $expAccount->account_id,
                    'expense_amount' => 4200.00,
                    'description' => 'Bus tickets and lodging',
                ],
            ]
        );

        $this->assertEquals('POSTED', $liq->status);
        $this->assertEquals(4200.00, (float) $liq->total_expense_amount);
        $this->assertEquals(800.00, (float) $liq->cash_returned_amount);

        // Verify Advance is now FULLY_LIQUIDATED
        $advance->refresh();
        $this->assertEquals('FULLY_LIQUIDATED', $advance->status);
        $this->assertEquals(5000.00, (float) $advance->liquidated_amount);
        $this->assertEquals(0.00, (float) $advance->balance_due);

        // Verify Liquidation GL:
        // Dr EXPENSE 4,200.00
        // Dr CASH_ON_HAND (refund) 800.00
        // Cr EMPLOYEE_ADVANCES 5,000.00
        $liqJournal = GlJournalHeader::with('lines.account')->find($liq->journal_id);
        $this->assertNotNull($liqJournal);
        $this->assertEquals(5000.00, (float) $liqJournal->total_debit);
        $this->assertEquals(5000.00, (float) $liqJournal->total_credit);

        $lRoles = $liqJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role ?? 'EXPENSE' => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(800.00, $lRoles['CASH_ON_HAND']);
        $this->assertEquals(-5000.00, $lRoles['EMPLOYEE_ADVANCES']);
    }

    public function test_cash_bank_fund_transfer_and_pdc_check_clearance(): void
    {
        $cashAcc = ChartOfAccount::where('system_role', 'CASH_IN_BANK')->firstOrFail();

        $bankA = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BDO Main',
            'bank_name' => 'BDO',
            'account_number' => '111-222',
            'account_type' => 'CHECKING',
            'gl_account_id' => $cashAcc->account_id,
            'current_balance' => 100000.00,
            'is_active' => true,
        ]);

        $bankB = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BPI Payroll',
            'bank_name' => 'BPI',
            'account_number' => '333-444',
            'account_type' => 'CHECKING',
            'gl_account_id' => $cashAcc->account_id,
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);

        // Transfer 25,000 with 50.00 bank fee
        $transfer = $this->cashBankService->transferFunds([
            'company_id' => $this->companyId,
            'source_bank_account_id' => $bankA->bank_account_id,
            'destination_bank_account_id' => $bankB->bank_account_id,
            'transfer_date' => now()->toDateString(),
            'amount' => 25000.00,
            'fee_amount' => 50.00,
            'reference_number' => 'TRF-INSTAPAY-1234',
        ]);

        $this->assertEquals('POSTED', $transfer->status);
        $this->assertEquals(25000.00, (float) $transfer->amount);
        $this->assertEquals(50.00, (float) $transfer->fee_amount);

        $bankA->refresh();
        $bankB->refresh();
        $this->assertEquals(74950.00, (float) $bankA->current_balance);
        $this->assertEquals(35000.00, (float) $bankB->current_balance);

        // Test PDC Clearance
        $pdcCheck = AcctCheckRegister::create([
            'company_id' => $this->companyId,
            'direction' => 'OUTGOING',
            'party_type' => 'VENDOR',
            'party_id' => 'VEND-001',
            'party_name' => 'Atlantic Foods',
            'bank_name' => 'BDO',
            'check_number' => 'PDC-99001',
            'check_date' => now()->subDays(10)->toDateString(),
            'maturity_date' => now()->toDateString(),
            'amount' => 15000.00,
            'source_document_type' => 'VENDOR_PAYMENT',
            'source_document_id' => 'VPMT-001',
            'status' => 'ISSUED',
        ]);

        $cleared = $this->cashBankService->clearPdcCheck($pdcCheck->check_id);
        $this->assertEquals('CLEARED', $cleared->status);
        $this->assertNotNull($cleared->cleared_at);

        // PDC clearance GL: Dr PDC_PAYABLE 15,000, Cr CASH_IN_BANK 15,000
        $clearJournal = GlJournalHeader::find($cleared->clearance_journal_id);
        $this->assertNotNull($clearJournal);
        $this->assertEquals(15000.00, (float) $clearJournal->total_debit);
        $this->assertEquals(15000.00, (float) $clearJournal->total_credit);
    }

    public function test_ap_ar_cash_controller_api_endpoints(): void
    {
        // 1. AP Bill Save API
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-API-01',
            'legal_name' => 'API Vendor Supplier',
            'trade_name' => 'API Vendor',
            'tax_id' => '999-111-222-000',
            'is_active' => true,
        ]);

        $resBill = $this->actingAs($this->user)->postJson(route('accounting.api.ap.bills.save'), [
            'header' => [
                'vendor_id' => (string) $vendor->id,
                'vendor_invoice_number' => 'SI-API-001',
                'vendor_invoice_date' => now()->toDateString(),
                'bill_due_date' => now()->addDays(30)->toDateString(),
            ],
            'lines' => [
                [
                    'bill_line_description' => 'Office Supplies Intake',
                    'quantity' => 2,
                    'unit_price' => 500.00,
                    'tax_code' => 'VAT_12',
                ],
            ],
            'action' => 'DRAFT',
        ]);
        $resBill->assertStatus(200);
        $resBill->assertJsonPath('success', true);
        $this->assertDatabaseHas('vendor_bill_header', ['vendor_invoice_number' => 'SI-API-001', 'vendor_bill_status' => 'DRAFT']);

        // 2. AR Customer Save API
        $resCust = $this->actingAs($this->user)->postJson(route('accounting.api.ar.customers.save'), [
            'customer_code' => 'CUST-API-01',
            'customer_name' => 'API Customer Enterprises',
            'credit_limit' => 75000.00,
            'payment_terms_code' => 'NET30',
        ]);
        $resCust->assertStatus(200);
        $resCust->assertJsonPath('success', true);
        $this->assertDatabaseHas('acct_customers', ['customer_code' => 'CUST-API-01']);

        // 3. Cash Bank Save Account API
        $resBank = $this->actingAs($this->user)->postJson(route('accounting.api.cash.banks.save'), [
            'account_name' => 'UnionBank Treasury Account',
            'bank_name' => 'UnionBank',
            'account_number' => 'UBP-889900',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'opening_balance' => 250000.00,
        ]);
        $resBank->assertStatus(200);
        $resBank->assertJsonPath('success', true);
        $this->assertDatabaseHas('acct_bank_accounts', ['account_name' => 'UnionBank Treasury Account']);

        // 4. AP Vendor Payment Voucher Save API
        $postedBill = $this->apService->saveDraftOrPostBill([
            'company_id' => '1',
            'branch_id' => 'BR-01',
            'vendor_id' => (string) $vendor->id,
            'vendor_invoice_number' => 'INV-API-PAY-01',
            'vendor_invoice_date' => now()->toDateString(),
            'bill_due_date' => now()->addDays(30)->toDateString(),
            'acted_by' => $this->user->name,
        ], [
            [
                'bill_line_description' => 'Raw Meat Supply',
                'quantity' => 10,
                'unit_price' => 500.00,
                'tax_code' => 'VAT_EXEMPT',
            ]
        ], 'POSTED');

        $resPmt = $this->actingAs($this->user)->postJson(route('accounting.api.ap.payments.save'), [
            'payment' => [
                'vendor_id' => (string) $vendor->id,
                'vendor_name' => $vendor->trade_name,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'BANK_TRANSFER',
                'bank_reference' => 'REF-998877',
                'total_amount' => 5000.00,
                'total_ewt_amount' => 50.00,
                'total_net_amount' => 4950.00,
            ],
            'allocations' => [
                [
                    'vendor_bill_id' => $postedBill->vendor_bill_id,
                    'allocated_amount' => 5000.00,
                    'ewt_amount' => 50.00,
                    'ewt_rate_pct' => 1.0,
                    'ewt_tax_code' => 'WC158',
                ]
            ]
        ]);
        $resPmt->assertStatus(200);
        $resPmt->assertJsonPath('success', true);
        $this->assertDatabaseHas('acct_vendor_payments', [
            'vendor_id' => (string) $vendor->id,
            'bank_reference' => 'REF-998877',
            'status' => 'POSTED',
        ]);
    }
}
