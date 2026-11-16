<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctBankAccount;
use App\Models\Accounting\AcctFixedAsset;
use App\Models\Accounting\AcctFixedAssetDepreciation;
use App\Models\Accounting\AcctFixedAssetDisposal;
use App\Models\Accounting\AcctLoan;
use App\Models\Accounting\AcctLoanAmortization;
use App\Models\Accounting\AcctLoanPayment;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\AcctFiscalYear;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\Accounting\GlRecurringTemplate;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Accounting\VendorBillLine;
use App\Models\Purchase\ProcurementVendor;
use App\Models\User;
use App\Services\Accounting\AccountingFixedAssetService;
use App\Services\Accounting\AccountingGeneralLedgerService;
use App\Services\Accounting\AccountingLoanService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingWave3FixedAssetsAndLoansTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AccountingSeedService $seedService;
    protected AccountingPostingService $postingService;
    protected AccountingFixedAssetService $faService;
    protected AccountingLoanService $loanService;
    protected AccountingGeneralLedgerService $glService;
    protected string $companyId = '1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        $this->actingAs($this->user);

        $this->seedService = app(AccountingSeedService::class);
        $this->postingService = app(AccountingPostingService::class);
        $this->faService = app(AccountingFixedAssetService::class);
        $this->loanService = app(AccountingLoanService::class);
        $this->glService = app(AccountingGeneralLedgerService::class);

        $this->seedService->ensureSeeded($this->companyId);
        AcctFiscalPeriod::where('company_id', $this->companyId)->update(['status' => 'OPEN']);
    }

    public function test_fixed_asset_capitalization_and_metadata(): void
    {
        $assetAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'FIXED_ASSET_CLEARING')->firstOrFail();
        $accumAcc = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'ACCUMULATED_DEPRECIATION')->firstOrFail();
        $expAcc = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'DEPRECIATION_EXPENSE')->firstOrFail();

        $asset = $this->faService->createOrCapitalizeAsset([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'asset_name' => 'Commercial Espresso Machine Dual Group',
            'asset_category' => 'KITCHEN_EQUIPMENT',
            'acquisition_date' => '2026-01-15',
            'acquisition_cost' => 150000.00,
            'salvage_value' => 10000.00,
            'useful_life_months' => 60,
            'asset_gl_account_id' => $assetAccount->account_id,
            'accum_deprec_gl_account_id' => $accumAcc->account_id,
            'deprec_expense_gl_account_id' => $expAcc->account_id,
            'serial_number' => 'SN-EXPRESSO-9912',
            'location' => 'Main Commissary Coffee Station',
        ]);

        $this->assertNotNull($asset->asset_id);
        $this->assertStringStartsWith('FA', $asset->asset_code);
        $this->assertEquals(150000.00, (float) $asset->acquisition_cost);
        $this->assertEquals(10000.00, (float) $asset->salvage_value);
        $this->assertEquals(140000.00, (float) $asset->depreciable_base);
        $this->assertEquals(150000.00, (float) $asset->net_book_value);
        $this->assertEquals(0.00, (float) $asset->total_depreciated_amount);
        $this->assertEquals('ACTIVE', $asset->status);
    }

    public function test_fixed_asset_capitalization_from_vendor_bill_line(): void
    {
        $vendor = ProcurementVendor::create([
            'vendor_code' => 'VEND-FA-01',
            'legal_name' => 'Metro Kitchen Supplies Inc.',
            'trade_name' => 'Metro Kitchen',
            'tax_id' => '999-888-777-000',
            'is_active' => true,
        ]);

        $faClearAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'FIXED_ASSET_CLEARING')->firstOrFail();

        $bill = VendorBillHeader::create([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'vendor_id' => (string) $vendor->id,
            'vendor_name' => $vendor->trade_name,
            'vendor_bill_number' => 'BILL-2026-FA01',
            'vendor_invoice_number' => 'INV-METRO-4421',
            'vendor_invoice_date' => now()->toDateString(),
            'bill_received_date' => now()->toDateString(),
            'bill_due_date' => now()->addDays(30)->toDateString(),
            'bill_subtotal_amount' => 250000.00,
            'bill_tax_amount' => 0.00,
            'bill_total_amount' => 250000.00,
            'vendor_bill_status' => 'POSTED',
        ]);

        $billLine = VendorBillLine::create([
            'vendor_bill_id' => $bill->vendor_bill_id,
            'item_id' => (string) \Illuminate\Support\Str::uuid(),
            'line_number' => 1,
            'bill_line_description' => 'Industrial Walk-in Freezer System',
            'purchase_uom_code' => 'UNIT',
            'bill_quantity' => 1,
            'bill_unit_price' => 250000.00,
            'bill_line_subtotal' => 250000.00,
            'bill_line_total' => 250000.00,
            'gl_expense_account_id' => $faClearAccount->account_id,
        ]);

        $asset = $this->faService->createOrCapitalizeAsset([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'asset_name' => 'Walk-in Freezer Cold Storage',
            'asset_category' => 'KITCHEN_EQUIPMENT',
            'acquisition_date' => now()->toDateString(),
            'acquisition_cost' => 250000.00,
            'salvage_value' => 25000.00,
            'useful_life_months' => 120, // 10 years
        ], $bill->vendor_bill_id, $billLine->vendor_bill_line_id);

        $this->assertEquals($bill->vendor_bill_id, $asset->vendor_bill_id);
        $this->assertEquals(250000.00, (float) $asset->acquisition_cost);
        $this->assertEquals(225000.00, (float) $asset->depreciable_base);
    }

    public function test_fixed_asset_monthly_straight_line_depreciation_gl_posting(): void
    {
        $period = AcctFiscalPeriod::where('company_id', $this->companyId)->where('period_number', 1)->firstOrFail();

        // Asset cost: 120,000.00, salvage: 0.00, useful life: 12 months -> 10,000.00 / month
        $asset = $this->faService->createOrCapitalizeAsset([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'asset_name' => 'POS Terminal & Receipt Printer Bundle',
            'asset_category' => 'IT_EQUIPMENT',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 120000.00,
            'salvage_value' => 0.00,
            'useful_life_months' => 12,
        ]);

        $records = $this->faService->postMonthlyDepreciation(
            companyId: $this->companyId,
            fiscalPeriodId: $period->period_id,
            assetId: $asset->asset_id,
            depreciationDate: '2026-01-31'
        );

        $this->assertCount(1, $records);
        $deprec = $records[0];

        $this->assertEquals(10000.00, (float) $deprec->depreciation_amount);
        $this->assertEquals(120000.00, (float) $deprec->book_value_before);
        $this->assertEquals(110000.00, (float) $deprec->book_value_after);
        $this->assertEquals('POSTED', $deprec->status);

        // Verify Asset updated
        $asset->refresh();
        $this->assertEquals(10000.00, (float) $asset->total_depreciated_amount);
        $this->assertEquals(110000.00, (float) $asset->net_book_value);

        // Verify Depreciation GL Journal:
        // DR DEPRECIATION_EXPENSE 10,000.00
        // CR ACCUMULATED_DEPRECIATION 10,000.00
        $journal = GlJournalHeader::with('lines.account')->find($deprec->journal_id);
        $this->assertNotNull($journal);
        $this->assertEquals('SYSTEM_DEPRECIATION', $journal->journal_type);
        $this->assertEquals(10000.00, (float) $journal->total_debit);
        $this->assertEquals(10000.00, (float) $journal->total_credit);

        $jRoles = $journal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(10000.00, $jRoles['DEPRECIATION_EXPENSE']);
        $this->assertEquals(-10000.00, $jRoles['ACCUMULATED_DEPRECIATION']);
    }

    public function test_fixed_asset_disposal_with_gain_calculation_and_gl_derecognition(): void
    {
        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BDO Operating Checking',
            'bank_name' => 'BDO',
            'account_number' => '001-234-5678',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);

        // Asset cost: 100,000.00, accumulated depreciation: 40,000.00 -> Net Book Value: 60,000.00
        $asset = $this->faService->createOrCapitalizeAsset([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'asset_name' => 'Delivery Van 2020',
            'asset_category' => 'VEHICLES',
            'acquisition_date' => '2023-01-01',
            'acquisition_cost' => 100000.00,
            'salvage_value' => 0.00,
            'useful_life_months' => 60,
        ]);

        // Manually simulate 40k depreciation
        $asset->total_depreciated_amount = 40000.00;
        $asset->net_book_value = 60000.00;
        $asset->save();

        // Sold for 75,000.00 cash proceeds -> Gain on disposal: 15,000.00
        $disposal = $this->faService->disposeAsset($asset->asset_id, [
            'disposal_date' => now()->toDateString(),
            'disposal_type' => 'SALE',
            'proceeds_amount' => 75000.00,
            'buyer_name' => 'Auto Traders Manila',
            'receipt_account_id' => $bankAccount->gl_account_id,
            'notes' => 'Fleet upgrade replacement sale',
        ]);

        $this->assertEquals('POSTED', $disposal->status);
        $this->assertEquals(75000.00, (float) $disposal->proceeds_amount);
        $this->assertEquals(60000.00, (float) $disposal->book_value_at_disposal);
        $this->assertEquals(15000.00, (float) $disposal->gain_loss_amount);

        // Verify Asset status is updated to DISPOSED
        $asset->refresh();
        $this->assertEquals('DISPOSED', $asset->status);
        $this->assertEquals(0.00, (float) $asset->net_book_value);

        // Verify Disposal GL Journal:
        // DR CASH_IN_BANK 75,000.00
        // DR ACCUMULATED_DEPRECIATION 40,000.00
        // CR FIXED_ASSET_CLEARING 100,000.00
        // CR GAIN_LOSS_DISPOSAL 15,000.00
        $journal = GlJournalHeader::with('lines.account')->find($disposal->journal_id);
        $this->assertNotNull($journal);
        $this->assertEquals(115000.00, (float) $journal->total_debit);
        $this->assertEquals(115000.00, (float) $journal->total_credit);

        $jRoles = $journal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(75000.00, $jRoles['CASH_IN_BANK']);
        $this->assertEquals(40000.00, $jRoles['ACCUMULATED_DEPRECIATION']);
        $this->assertEquals(-100000.00, $jRoles['FIXED_ASSET_CLEARING']);
        $this->assertEquals(-15000.00, $jRoles['GAIN_LOSS_DISPOSAL']);
    }

    public function test_loan_facility_disbursement_gl_posting_and_amortization_generation(): void
    {
        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'Metrobank Treasury',
            'bank_name' => 'Metrobank',
            'account_number' => '554-112-9988',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 100000.00,
            'is_active' => true,
        ]);

        $loan = $this->loanService->createLoan([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'lender_name' => 'BDO Commercial Lending Group',
            'lender_contact' => 'Relationship Manager: Carlos Santos',
            'loan_type' => 'BANK_LOAN',
            'principal_amount' => 500000.00,
            'annual_interest_rate_pct' => 6.00,
            'start_date' => '2026-01-01',
            'term_months' => 12,
            'payment_frequency' => 'MONTHLY',
            'interest_method' => 'DIMINISHING_BALANCE',
            'disbursement_account_id' => $bankAccount->gl_account_id,
            'notes' => 'Kitchen commissary expansion term loan',
        ]);

        $this->assertNotNull($loan->loan_id);
        $this->assertEquals('ACTIVE', $loan->status);
        $this->assertEquals(500000.00, (float) $loan->principal_amount);
        $this->assertEquals(500000.00, (float) $loan->balance_remaining);

        // Verify Disbursement GL Journal:
        // DR CASH_IN_BANK 500,000.00
        // CR LOAN_PAYABLE 500,000.00
        $disbJournal = GlJournalHeader::with('lines.account')->find($loan->disbursement_journal_id);
        $this->assertNotNull($disbJournal);
        $this->assertEquals(500000.00, (float) $disbJournal->total_debit);
        $this->assertEquals(500000.00, (float) $disbJournal->total_credit);

        $jRoles = $disbJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(500000.00, $jRoles['CASH_IN_BANK']);
        $this->assertEquals(-500000.00, $jRoles['LOAN_PAYABLE']);

        // Verify 12 Amortization rows generated
        $amortizations = AcctLoanAmortization::where('loan_id', $loan->loan_id)->orderBy('period_number')->get();
        $this->assertCount(12, $amortizations);
        $this->assertEquals(1, $amortizations->first()->period_number);
        $this->assertEquals(500000.00, (float) $amortizations->first()->beginning_principal);
        $this->assertEquals(0.00, (float) $amortizations->last()->ending_principal);
    }

    public function test_loan_repayment_principal_and_interest_split_gl_posting(): void
    {
        $bankAccount = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'Metrobank Treasury',
            'bank_name' => 'Metrobank',
            'account_number' => '554-112-9988',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 200000.00,
            'is_active' => true,
        ]);

        $loan = $this->loanService->createLoan([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'lender_name' => 'Equipment Finance Corp.',
            'principal_amount' => 120000.00,
            'annual_interest_rate_pct' => 10.00,
            'start_date' => '2026-01-01',
            'term_months' => 12,
            'disbursement_account_id' => $bankAccount->gl_account_id,
        ]);

        // First month repayment: 10,000.00 principal + 1,000.00 interest = 11,000.00 total paid
        $payment = $this->loanService->recordLoanPayment($loan->loan_id, [
            'payment_date' => '2026-02-01',
            'principal_amount' => 10000.00,
            'interest_amount' => 1000.00,
            'penalty_amount' => 0.00,
            'payment_method' => 'BANK_TRANSFER',
            'disbursement_account_id' => $bankAccount->gl_account_id,
            'reference_number' => 'EFT-LOAN-P1',
        ]);

        $this->assertEquals('POSTED', $payment->status);
        $this->assertEquals(10000.00, (float) $payment->principal_amount);
        $this->assertEquals(1000.00, (float) $payment->interest_amount);
        $this->assertEquals(11000.00, (float) $payment->total_paid);

        // Verify loan balance reduced
        $loan->refresh();
        $this->assertEquals(10000.00, (float) $loan->principal_paid);
        $this->assertEquals(1000.00, (float) $loan->interest_paid);
        $this->assertEquals(110000.00, (float) $loan->balance_remaining);

        // Verify first amortization row marked PAID
        $firstAmort = AcctLoanAmortization::where('loan_id', $loan->loan_id)->orderBy('period_number')->first();
        $this->assertEquals('PAID', $firstAmort->status);
        $this->assertEquals($payment->loan_payment_id, $firstAmort->payment_id);

        // Verify Repayment GL Journal:
        // DR LOAN_PAYABLE 10,000.00
        // DR INTEREST_EXPENSE 1,000.00
        // CR CASH_IN_BANK 11,000.00
        $journal = GlJournalHeader::with('lines.account')->find($payment->journal_id);
        $this->assertNotNull($journal);
        $this->assertEquals(11000.00, (float) $journal->total_debit);
        $this->assertEquals(11000.00, (float) $journal->total_credit);

        $jRoles = $journal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(10000.00, $jRoles['LOAN_PAYABLE']);
        $this->assertEquals(1000.00, $jRoles['INTEREST_EXPENSE']);
        $this->assertEquals(-11000.00, $jRoles['CASH_IN_BANK']);
    }

    public function test_recurring_journal_template_creation_and_scheduled_execution(): void
    {
        $expAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'COGS')->firstOrFail();
        $bankAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'CASH_IN_BANK')->firstOrFail();

        GlRecurringTemplate::where('company_id', $this->companyId)->delete();

        $template = $this->glService->saveRecurringTemplate([
            'company_id' => $this->companyId,
            'template_name' => 'Monthly Commissary Facility Rent Amortization',
            'journal_type' => 'RECURRING',
            'frequency' => 'MONTHLY',
            'next_run_date' => now()->toDateString(),
            'auto_post' => true,
            'default_branch_id' => 'BR-01',
            'lines_json' => [
                [
                    'account_id' => $expAccount->account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => 45000.00,
                    'transaction_credit' => 0.0,
                    'description' => 'Monthly Commissary Rent Expense',
                ],
                [
                    'account_id' => $bankAccount->account_id,
                    'branch_id' => 'BR-01',
                    'transaction_debit' => 0.0,
                    'transaction_credit' => 45000.00,
                    'description' => 'Pre-authorized direct debit rent',
                ],
            ],
        ]);

        $this->assertNotNull($template->template_id);
        $this->assertEquals('MONTHLY', $template->frequency);

        // Run recurring engine
        $journals = $this->glService->generateRecurringJournals($this->companyId, now()->toDateString());
        $this->assertCount(1, $journals);
        $genJournal = $journals[0];

        $this->assertEquals('POSTED', $genJournal->status);
        $this->assertEquals(45000.00, (float) $genJournal->total_debit);
        $this->assertEquals('RECURRING', $genJournal->journal_type);

        // Verify template next_run_date was advanced by 1 month
        $template->refresh();
        $this->assertEquals(now()->toDateString(), $template->last_run_date->toDateString());
        $this->assertEquals(now()->addMonth()->toDateString(), $template->next_run_date->toDateString());
    }

    public function test_gl_inquiry_running_balance_and_statement_aggregation(): void
    {
        $cashAccount = ChartOfAccount::create([
            'company_id' => $this->companyId,
            'account_code' => '1099',
            'account_name' => 'Inquiry Test Cash',
            'account_type' => 'ASSET',
            'normal_balance' => 'DEBIT',
            'cash_flow_category' => 'CASH_EQUIVALENT',
            'is_active' => true,
        ]);
        $equityAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'RETAINED_EARNINGS')->firstOrFail();

        // Post Journal 1: +50,000 Debit (Cash increase)
        $this->postingService->saveDraftOrPostJournal([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'journal_date' => '2026-01-05',
            'journal_type' => 'MANUAL',
            'currency_code' => 'PHP',
            'exchange_rate' => 1.0,
            'description' => 'Capital injection 1',
        ], [
            ['account_id' => $cashAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 50000.00, 'transaction_credit' => 0.0],
            ['account_id' => $equityAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 0.0, 'transaction_credit' => 50000.00],
        ], 'POSTED');

        // Post Journal 2: -15,000 Credit (Cash decrease)
        $this->postingService->saveDraftOrPostJournal([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'journal_date' => '2026-01-10',
            'journal_type' => 'MANUAL',
            'currency_code' => 'PHP',
            'exchange_rate' => 1.0,
            'description' => 'Operating draw',
        ], [
            ['account_id' => $equityAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 15000.00, 'transaction_credit' => 0.0],
            ['account_id' => $cashAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 0.0, 'transaction_credit' => 15000.00],
        ], 'POSTED');

        $inquiry = $this->glService->getGlInquiry($this->companyId, $cashAccount->account_id);

        $this->assertEquals(0.00, (float) $inquiry['opening_balance']);
        $this->assertEquals(50000.00, (float) $inquiry['total_debit']);
        $this->assertEquals(15000.00, (float) $inquiry['total_credit']);
        $this->assertEquals(35000.00, (float) $inquiry['closing_balance']);
        $this->assertCount(2, $inquiry['lines']);
        $this->assertEquals(50000.00, (float) $inquiry['lines'][0]['running_balance']);
        $this->assertEquals(35000.00, (float) $inquiry['lines'][1]['running_balance']);
    }

    public function test_year_end_closing_clears_p_and_l_to_retained_earnings_and_locks_year(): void
    {
        $year = AcctFiscalYear::where('company_id', $this->companyId)->where('year_code', 'like', '%2026%')->firstOrFail();
        $revAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'SALES_REVENUE')->firstOrFail();
        $expAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'COGS')->firstOrFail();
        $cashAccount = ChartOfAccount::where('company_id', $this->companyId)->where('system_role', 'CASH_IN_BANK')->firstOrFail();

        // 1. Post Revenue: 200,000.00
        $this->postingService->saveDraftOrPostJournal([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'fiscal_year_id' => $year->fiscal_year_id,
            'journal_date' => '2026-06-30',
            'journal_type' => 'MANUAL',
            'currency_code' => 'PHP',
            'exchange_rate' => 1.0,
            'description' => 'H1 Sales Revenue',
        ], [
            ['account_id' => $cashAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 200000.00, 'transaction_credit' => 0.0],
            ['account_id' => $revAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 0.0, 'transaction_credit' => 200000.00],
        ], 'POSTED');

        // 2. Post COGS Expense: 80,000.00 -> Net Profit: 120,000.00
        $this->postingService->saveDraftOrPostJournal([
            'company_id' => $this->companyId,
            'branch_id' => 'BR-01',
            'fiscal_year_id' => $year->fiscal_year_id,
            'journal_date' => '2026-06-30',
            'journal_type' => 'MANUAL',
            'currency_code' => 'PHP',
            'exchange_rate' => 1.0,
            'description' => 'H1 Food Ingredients Cost',
        ], [
            ['account_id' => $expAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 80000.00, 'transaction_credit' => 0.0],
            ['account_id' => $cashAccount->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 0.0, 'transaction_credit' => 80000.00],
        ], 'POSTED');

        // Execute Year-End Closing
        $closingJournal = $this->glService->executeYearEndClosing($this->companyId, $year->fiscal_year_id);

        $this->assertNotNull($closingJournal);
        $this->assertEquals('CLOSING', $closingJournal->journal_type);
        $this->assertEquals('POSTED', $closingJournal->status);

        // Verify Closing Lines:
        // DR SALES_REVENUE 450,000.00 (250,000 seeded + 200,000 test)
        // CR COGS 155,000.00 (75,000 seeded + 80,000 test)
        // CR RETAINED_EARNINGS 295,000.00 (Net income closed to equity)
        $jRoles = $closingJournal->lines->mapWithKeys(fn($l) => [$l->account->system_role => (float) $l->base_debit - (float) $l->base_credit]);
        $this->assertEquals(450000.00, $jRoles['SALES_REVENUE']);
        $this->assertEquals(-155000.00, $jRoles['COGS']);
        $this->assertEquals(-295000.00, $jRoles['RETAINED_EARNINGS']);

        // Verify Fiscal Year is marked CLOSED
        $year->refresh();
        $this->assertEquals('CLOSED', $year->status);

        // Verify all periods in this year are CLOSED
        $openPeriods = AcctFiscalPeriod::where('fiscal_year_id', $year->fiscal_year_id)->where('status', '!=', 'CLOSED')->count();
        $this->assertEquals(0, $openPeriods);
    }

    public function test_fixed_assets_and_loans_controller_api_endpoints(): void
    {
        // 1. POST /accounting/api/fixed-assets/save
        $faRes = $this->postJson(route('accounting.api.fixed-assets.save'), [
            'asset_name' => 'Commercial Ice Maker Machine',
            'asset_category' => 'KITCHEN_EQUIPMENT',
            'acquisition_cost' => 85000.00,
            'salvage_value' => 5000.00,
            'useful_life_months' => 48,
        ]);
        $faRes->assertStatus(200)->assertJson(['success' => true]);
        $assetId = $faRes->json('data.asset_id');

        // 2. POST /accounting/api/loans/save
        $bank = AcctBankAccount::create([
            'company_id' => $this->companyId,
            'account_name' => 'BDO Cash Account',
            'bank_name' => 'BDO',
            'account_number' => '998-112-3344',
            'account_type' => 'CHECKING',
            'gl_account_id' => ChartOfAccount::where('system_role', 'CASH_IN_BANK')->value('account_id'),
            'current_balance' => 500000.00,
            'is_active' => true,
        ]);

        $loanRes = $this->postJson(route('accounting.api.loans.save'), [
            'lender_name' => 'Security Bank Corp.',
            'principal_amount' => 300000.00,
            'annual_interest_rate_pct' => 5.5,
            'start_date' => '2026-01-01',
            'term_months' => 24,
            'disbursement_account_id' => $bank->gl_account_id,
        ]);
        $loanRes->assertStatus(200)->assertJson(['success' => true]);
        $loanId = $loanRes->json('data.loan_id');

        // 3. POST /accounting/api/loans/{id}/payment
        $pmtRes = $this->postJson(route('accounting.api.loans.payment', ['loanId' => $loanId]), [
            'payment_date' => '2026-02-01',
            'principal_amount' => 12500.00,
            'interest_amount' => 1375.00,
            'disbursement_account_id' => $bank->gl_account_id,
        ]);
        $pmtRes->assertStatus(200)->assertJson(['success' => true]);

        // 4. GET /accounting/api/gl/inquiry
        $inqRes = $this->getJson(route('accounting.api.gl.inquiry', [
            'account_id' => $bank->gl_account_id,
        ]));
        $inqRes->assertStatus(200)->assertJson(['success' => true]);
    }
}
