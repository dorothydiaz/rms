<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctCurrency;
use App\Models\Accounting\AcctExchangeRate;
use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\AcctFiscalYear;
use App\Models\Accounting\AcctNumberingSeries;
use App\Models\Accounting\AcctPaymentTerm;
use App\Models\Accounting\AcctTaxCode;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlRecurringTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountingSeedService
{
    public function __construct(
        protected AccountingPostingService $postingService
    ) {
    }

    public function ensureSeeded(string $companyId = '1'): void
    {
        if (ChartOfAccount::where('company_id', $companyId)->exists()) {
            $this->seedNumberingSeries($companyId);
            $this->seedApprovalWorkflows($companyId);
            $this->ensureSalesDiscountAccount($companyId);
            return;
        }

        $this->seedFiscalCalendar($companyId);
        $this->seedCurrenciesAndRates($companyId);
        $this->seedPaymentTerms($companyId);
        $this->seedNumberingSeries($companyId);
        $this->seedTaxCodes($companyId);
        $this->seedChartOfAccounts($companyId);
        $this->seedApprovalWorkflows($companyId);
        $this->seedRecurringTemplates($companyId);
        $this->seedSampleJournals($companyId);
    }

    protected function seedFiscalCalendar(string $companyId): void
    {
        $year = date('Y');
        $fy = AcctFiscalYear::firstOrCreate(
            ['company_id' => $companyId, 'year_code' => "FY-{$year}"],
            [
                'fiscal_year_id' => (string) Str::uuid(),
                'year_name' => "Fiscal Year {$year} (Calendar)",
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'status' => 'OPEN',
            ]
        );

        $currentMonth = (int) date('n');
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create((int) $year, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $code = sprintf('%s-%02d', $year, $m);

            AcctFiscalPeriod::firstOrCreate(
                ['company_id' => $companyId, 'period_code' => $code],
                [
                    'period_id' => (string) Str::uuid(),
                    'fiscal_year_id' => $fy->fiscal_year_id,
                    'period_number' => $m,
                    'period_name' => $start->format('F Y'),
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => $m < ($currentMonth - 1) ? 'LOCKED' : 'OPEN',
                    'checklist_json' => [
                        'bank_reconciled' => $m < ($currentMonth - 1),
                        'ap_ar_cutoff' => $m < ($currentMonth - 1),
                        'depreciation_posted' => $m < ($currentMonth - 1),
                        'fx_revalued' => $m < ($currentMonth - 1),
                        'tax_verified' => $m < ($currentMonth - 1),
                    ],
                ]
            );
        }
    }

    protected function seedCurrenciesAndRates(string $companyId): void
    {
        $currencies = [
            ['currency_code' => 'PHP', 'currency_name' => 'Philippine Peso', 'symbol' => '₱', 'is_base_currency' => true],
            ['currency_code' => 'USD', 'currency_name' => 'US Dollar', 'symbol' => '$', 'is_base_currency' => false],
            ['currency_code' => 'EUR', 'currency_name' => 'Euro', 'symbol' => '€', 'is_base_currency' => false],
            ['currency_code' => 'SGD', 'currency_name' => 'Singapore Dollar', 'symbol' => 'S$', 'is_base_currency' => false],
            ['currency_code' => 'JPY', 'currency_name' => 'Japanese Yen', 'symbol' => '¥', 'is_base_currency' => false],
        ];
        foreach ($currencies as $c) {
            AcctCurrency::firstOrCreate(['currency_code' => $c['currency_code']], $c + ['is_active' => true]);
        }

        $rates = [
            ['from' => 'USD', 'rate' => 56.250000],
            ['from' => 'EUR', 'rate' => 61.100000],
            ['from' => 'SGD', 'rate' => 42.150000],
            ['from' => 'JPY', 'rate' => 0.375000],
        ];
        foreach ($rates as $r) {
            AcctExchangeRate::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'from_currency_code' => $r['from'],
                    'to_currency_code' => 'PHP',
                    'effective_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'rate_id' => (string) Str::uuid(),
                    'exchange_rate' => $r['rate'],
                    'created_by' => 'System Seed',
                ]
            );
        }
    }

    protected function seedPaymentTerms(string $companyId): void
    {
        $terms = [
            ['term_code' => 'COD', 'term_name' => 'Cash on Delivery (COD)', 'due_days' => 0, 'discount_pct' => 0, 'discount_days' => 0],
            ['term_code' => 'NET7', 'term_name' => 'Net 7 Days', 'due_days' => 7, 'discount_pct' => 0, 'discount_days' => 0],
            ['term_code' => 'NET15', 'term_name' => 'Net 15 Days', 'due_days' => 15, 'discount_pct' => 0, 'discount_days' => 0],
            ['term_code' => 'NET30', 'term_name' => 'Net 30 Days', 'due_days' => 30, 'discount_pct' => 0, 'discount_days' => 0],
            ['term_code' => 'NET60', 'term_name' => 'Net 60 Days', 'due_days' => 60, 'discount_pct' => 0, 'discount_days' => 0],
            ['term_code' => '2/10_NET30', 'term_name' => '2% 10, Net 30 Days', 'due_days' => 30, 'discount_pct' => 2.00, 'discount_days' => 10],
        ];
        foreach ($terms as $t) {
            AcctPaymentTerm::firstOrCreate(
                ['company_id' => $companyId, 'term_code' => $t['term_code']],
                $t + ['term_id' => (string) Str::uuid(), 'is_active' => true]
            );
        }
    }

    protected function seedNumberingSeries(string $companyId): void
    {
        foreach (AccountingNumberingService::DEFAULT_SERIES as $code => $meta) {
            AcctNumberingSeries::firstOrCreate(
                ['company_id' => $companyId, 'module_code' => $code],
                [
                    'series_id' => (string) Str::uuid(),
                    'module_name' => $meta['name'],
                    'prefix' => $meta['prefix'],
                    'next_number' => 1,
                    'padding' => 4,
                    'reset_frequency' => 'YEARLY',
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedTaxCodes(string $companyId): void
    {
        $taxes = [
            ['tax_code' => 'VAT12_IN', 'tax_name' => '12% Input VAT (Goods & Services)', 'tax_type' => 'VAT_INPUT', 'rate_pct' => 12.0000, 'atc_code' => 'VAT-IN', 'bir_form' => '2550Q', 'system_role_link' => 'INPUT_VAT'],
            ['tax_code' => 'VAT12_OUT', 'tax_name' => '12% Output VAT (Sales)', 'tax_type' => 'VAT_OUTPUT', 'rate_pct' => 12.0000, 'atc_code' => 'VAT-OUT', 'bir_form' => '2550Q', 'system_role_link' => 'OUTPUT_VAT'],
            ['tax_code' => 'VAT_EXEMPT', 'tax_name' => 'VAT-Exempt Purchase / Sale', 'tax_type' => 'EXEMPT', 'rate_pct' => 0.0000, 'atc_code' => null, 'bir_form' => '2550Q', 'system_role_link' => null],
            ['tax_code' => 'VAT_ZERO', 'tax_name' => '0% Zero-Rated VAT', 'tax_type' => 'ZERO_RATED', 'rate_pct' => 0.0000, 'atc_code' => null, 'bir_form' => '2550Q', 'system_role_link' => null],
            ['tax_code' => 'EWT_1_GOODS', 'tax_name' => '1% EWT — Local Goods Supplier (WC158)', 'tax_type' => 'EWT', 'rate_pct' => 1.0000, 'atc_code' => 'WC158', 'bir_form' => '1601-EQ / 2307', 'system_role_link' => 'EWT_PAYABLE'],
            ['tax_code' => 'EWT_2_SERVICES', 'tax_name' => '2% EWT — General Services / Contractors (WC160)', 'tax_type' => 'EWT', 'rate_pct' => 2.0000, 'atc_code' => 'WC160', 'bir_form' => '1601-EQ / 2307', 'system_role_link' => 'EWT_PAYABLE'],
            ['tax_code' => 'EWT_5_RENT', 'tax_name' => '5% EWT — Commercial Rentals (WC100)', 'tax_type' => 'EWT', 'rate_pct' => 5.0000, 'atc_code' => 'WC100', 'bir_form' => '1601-EQ / 2307', 'system_role_link' => 'EWT_PAYABLE'],
            ['tax_code' => 'EWT_10_PROF', 'tax_name' => '10% EWT — Professional & Consultancy Fees (WI010)', 'tax_type' => 'EWT', 'rate_pct' => 10.0000, 'atc_code' => 'WI010', 'bir_form' => '1601-EQ / 2307', 'system_role_link' => 'EWT_PAYABLE'],
            ['tax_code' => 'CWT_1', 'tax_name' => '1% Creditable Withholding Tax Received (2307)', 'tax_type' => 'CWT', 'rate_pct' => 1.0000, 'atc_code' => 'WC158', 'bir_form' => 'SAWT / 2307', 'system_role_link' => 'CWT_RECEIVABLE'],
            ['tax_code' => 'CWT_2', 'tax_name' => '2% Creditable Withholding Tax Received (2307)', 'tax_type' => 'CWT', 'rate_pct' => 2.0000, 'atc_code' => 'WC160', 'bir_form' => 'SAWT / 2307', 'system_role_link' => 'CWT_RECEIVABLE'],
        ];

        foreach ($taxes as $tax) {
            AcctTaxCode::firstOrCreate(
                ['company_id' => $companyId, 'tax_code' => $tax['tax_code']],
                $tax + ['tax_id' => (string) Str::uuid(), 'is_active' => true]
            );
        }
    }

    protected function seedChartOfAccounts(string $companyId): void
    {
        $accounts = [
            // ASSETS (1000–1999)
            ['code' => '1010', 'name' => 'Cash on Hand & Petty Cash Fund', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'CASH_EQUIVALENT', 'role' => 'CASH_ON_HAND'],
            ['code' => '1020', 'name' => 'Cash in Bank — Primary Operating Account', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'CASH_EQUIVALENT', 'role' => 'CASH_IN_BANK'],
            ['code' => '1110', 'name' => 'Accounts Receivable — Trade', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'ACCOUNTS_RECEIVABLE'],
            ['code' => '1120', 'name' => 'Post-Dated Checks (PDC) Receivable', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'PDC_RECEIVABLE'],
            ['code' => '1130', 'name' => 'Creditable Withholding Tax (2307) Receivable', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'CWT_RECEIVABLE'],
            ['code' => '1140', 'name' => 'Employee Advances & Petty Liquidations', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'EMPLOYEE_ADVANCES'],
            ['code' => '1150', 'name' => 'Advances to Suppliers / Vendor Prepayments', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'VENDOR_PREPAYMENTS'],
            ['code' => '1160', 'name' => 'Input VAT Receivable', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'INPUT_VAT'],
            ['code' => '1210', 'name' => 'Inventory — Raw Ingredients & Commissary Stock', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'INVENTORY_ASSET'],
            ['code' => '1220', 'name' => 'Landed Cost Clearing Account', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'LANDED_COST_CLEARING'],
            ['code' => '1510', 'name' => 'Kitchen Equipment & Store Fixed Assets', 'type' => 'ASSET', 'norm' => 'DEBIT', 'cf' => 'INVESTING', 'role' => 'FIXED_ASSET_CLEARING'],
            ['code' => '1590', 'name' => 'Accumulated Depreciation — Equipment & Leasehold', 'type' => 'ASSET', 'norm' => 'CREDIT', 'cf' => 'NONE', 'role' => 'ACCUMULATED_DEPRECIATION'],

            // LIABILITIES (2000–2999)
            ['code' => '2010', 'name' => 'Accounts Payable — Trade Vendors', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'ACCOUNTS_PAYABLE'],
            ['code' => '2020', 'name' => 'GRNI / Accrued Purchases (Unbilled Receipts)', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'GRNI_ACCRUED_PURCHASES'],
            ['code' => '2030', 'name' => 'Post-Dated Checks (PDC) Payable', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'PDC_PAYABLE'],
            ['code' => '2040', 'name' => 'Customer Deposits & Unearned Catering Revenue', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'CUSTOMER_DEPOSITS'],
            ['code' => '2110', 'name' => 'Output VAT Payable', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'OUTPUT_VAT'],
            ['code' => '2120', 'name' => 'Expanded Withholding Tax (EWT) Payable', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'EWT_PAYABLE'],
            ['code' => '2130', 'name' => 'Accrued Payroll & Statutory Remittances', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => null],
            ['code' => '2510', 'name' => 'Loans & Equipment Financing Payable', 'type' => 'LIABILITY', 'norm' => 'CREDIT', 'cf' => 'FINANCING', 'role' => 'LOAN_PAYABLE'],

            // EQUITY (3000–3999)
            ['code' => '3010', 'name' => 'Share Capital / Owner Equity', 'type' => 'EQUITY', 'norm' => 'CREDIT', 'cf' => 'FINANCING', 'role' => null],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'EQUITY', 'norm' => 'CREDIT', 'cf' => 'NONE', 'role' => 'RETAINED_EARNINGS'],

            // REVENUE (4000–4999)
            ['code' => '4010', 'name' => 'Food & Beverage Sales Revenue', 'type' => 'REVENUE', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => 'SALES_REVENUE'],
            ['code' => '4020', 'name' => 'Delivery & Catering Revenue', 'type' => 'REVENUE', 'norm' => 'CREDIT', 'cf' => 'OPERATING', 'role' => null],
            ['code' => '4090', 'name' => 'Sales Discounts & Allowances', 'type' => 'REVENUE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'SALES_DISCOUNTS'],

            // COGS (5000–5999)
            ['code' => '5010', 'name' => 'Cost of Goods Sold — Food & Packaging', 'type' => 'COGS', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'COGS'],
            ['code' => '5020', 'name' => 'Purchase Price Variance (PPV)', 'type' => 'COGS', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'PURCHASE_PRICE_VARIANCE'],

            // EXPENSES (6000–7999)
            ['code' => '6010', 'name' => 'Salaries, Wages & Store Benefits', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => null],
            ['code' => '6020', 'name' => 'Store & Commissary Rent Expense', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => null],
            ['code' => '6030', 'name' => 'Utilities & Cold-Chain Power', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => null],
            ['code' => '6040', 'name' => 'Insurance & Permits Amortization', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => null],
            ['code' => '6100', 'name' => 'Depreciation Expense', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'NONE', 'role' => 'DEPRECIATION_EXPENSE'],
            ['code' => '6200', 'name' => 'Interest Expense on Loans & Leases', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'INTEREST_EXPENSE'],
            ['code' => '6210', 'name' => 'Bank Charges & DAIF Bounce Penalties', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'BANK_PENALTY_EXPENSE'],
            ['code' => '6220', 'name' => 'Bad Debt & Receivables Write-Off Expense', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'NONE', 'role' => 'BAD_DEBT_EXPENSE'],
            ['code' => '7010', 'name' => 'Realized Foreign Exchange Gain / Loss', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'OPERATING', 'role' => 'FX_GAIN_LOSS_REALIZED'],
            ['code' => '7020', 'name' => 'Unrealized Foreign Exchange Gain / Loss', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'NONE', 'role' => 'FX_GAIN_LOSS_UNREALIZED'],
            ['code' => '7030', 'name' => 'Gain / Loss on Asset Disposal', 'type' => 'EXPENSE', 'norm' => 'DEBIT', 'cf' => 'NONE', 'role' => 'GAIN_LOSS_DISPOSAL'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::create([
                'account_id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'account_code' => $acc['code'],
                'account_name' => $acc['name'],
                'account_type' => $acc['type'],
                'normal_balance' => $acc['norm'],
                'cash_flow_category' => $acc['cf'],
                'system_role' => $acc['role'],
                'allow_manual_journal' => $acc['role'] === null || in_array($acc['role'], ['CASH_IN_BANK', 'CASH_ON_HAND', 'RETAINED_EARNINGS'], true),
                'is_active' => true,
            ]);
        }
    }

    protected function ensureSalesDiscountAccount(string $companyId): void
    {
        ChartOfAccount::firstOrCreate(
            ['company_id' => $companyId, 'system_role' => 'SALES_DISCOUNTS'],
            [
                'account_id' => (string) Str::uuid(),
                'account_code' => '4090',
                'account_name' => 'Sales Discounts & Allowances',
                'account_type' => 'REVENUE',
                'normal_balance' => 'DEBIT',
                'cash_flow_category' => 'OPERATING',
                'allow_manual_journal' => false,
                'is_active' => true,
            ]
        );
    }

    protected function seedApprovalWorkflows(string $companyId): void
    {
        if (!Schema::hasTable('approval_workflows')) {
            return;
        }

        $workflows = [
            ['code' => 'ACCT_GL_JOURNAL', 'name' => 'GL Manual & Adjusting Journal Approval', 'doc' => 'GL_JOURNAL', 'min' => 0, 'step' => 'Accounting Controller Sign-Off'],
            ['code' => 'ACCT_VENDOR_BILL', 'name' => 'Vendor Bill & 3-Way Match Approval', 'doc' => 'VENDOR_BILL', 'min' => 0, 'step' => 'AP Supervisor Review'],
            ['code' => 'ACCT_PAYMENT_VOUCHER', 'name' => 'Vendor Payment & Check Release', 'doc' => 'VENDOR_PAYMENT', 'min' => 10000, 'step' => 'Finance Manager Approval'],
            ['code' => 'ACCT_PERIOD_CLOSE', 'name' => 'Fiscal Period Lock & Closing Sign-Off', 'doc' => 'PERIOD_CLOSE', 'min' => 0, 'step' => 'Chief Accountant Sign-Off'],
            ['code' => 'ACCT_SALES_ORDER_CREDIT', 'name' => 'Sales Order Credit Limit Review', 'doc' => 'SALES_ORDER_CREDIT', 'min' => 0, 'step' => 'AR Credit Officer Review'],
        ];

        foreach ($workflows as $wf) {
            $exists = DB::table('approval_workflows')->where('approval_workflow_code', $wf['code'])->first();
            if (!$exists) {
                $wfId = (string) Str::uuid();
                DB::table('approval_workflows')->insert([
                    'approval_workflow_id' => $wfId,
                    'approval_workflow_code' => $wf['code'],
                    'approval_workflow_name' => $wf['name'],
                    'approval_document_type' => $wf['doc'],
                    'company_id' => null,
                    'branch_id' => null,
                    'approval_min_amount' => $wf['min'],
                    'approval_currency_code' => 'PHP',
                    'approval_priority' => 1,
                    'approval_workflow_is_active' => true,
                    'approval_workflow_created_at' => now(),
                    'approval_workflow_updated_at' => now(),
                ]);

                if (Schema::hasTable('approval_workflow_steps')) {
                    DB::table('approval_workflow_steps')->insert([
                        'approval_step_id' => (string) Str::uuid(),
                        'approval_workflow_id' => $wfId,
                        'approval_step_number' => 1,
                        'approval_step_name' => $wf['step'],
                        'approver_type' => 'ROLE',
                        'approval_mode' => 'ANY_ONE',
                        'approval_min_approvals' => 1,
                        'is_self_approval_allowed' => false,
                        'approval_sla_hours' => 24,
                        'approval_step_is_active' => true,
                        'approval_step_created_at' => now(),
                        'approval_step_updated_at' => now(),
                    ]);
                }
            }
        }
    }

    protected function seedRecurringTemplates(string $companyId): void
    {
        $rentExp = ChartOfAccount::where('company_id', $companyId)->where('account_code', '6020')->first();
        $apAcc = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'ACCOUNTS_PAYABLE')->first();
        $insExp = ChartOfAccount::where('company_id', $companyId)->where('account_code', '6040')->first();
        $cashBank = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'CASH_IN_BANK')->first();

        if ($rentExp && $apAcc) {
            GlRecurringTemplate::firstOrCreate(
                ['company_id' => $companyId, 'template_code' => 'REC-RENT-01'],
                [
                    'template_id' => (string) Str::uuid(),
                    'template_name' => 'Monthly Central Commissary Lease Accrual',
                    'journal_type' => 'RECURRING',
                    'frequency' => 'MONTHLY',
                    'next_run_date' => now()->endOfMonth()->toDateString(),
                    'auto_post' => false,
                    'default_branch_id' => 'BR-01',
                    'description' => 'Monthly base rent accrual for Commissary & Main Store.',
                    'lines_json' => [
                        ['account_id' => $rentExp->account_id, 'account_code' => $rentExp->account_code, 'account_name' => $rentExp->account_name, 'branch_id' => 'BR-01', 'department_id' => 'OPS', 'debit' => 85000, 'credit' => 0, 'description' => 'Monthly Commissary Rent'],
                        ['account_id' => $apAcc->account_id, 'account_code' => $apAcc->account_code, 'account_name' => $apAcc->account_name, 'branch_id' => 'BR-01', 'department_id' => 'OPS', 'debit' => 0, 'credit' => 85000, 'description' => 'Accrued Lease Liability'],
                    ],
                ]
            );
        }

        if ($insExp && $cashBank) {
            GlRecurringTemplate::firstOrCreate(
                ['company_id' => $companyId, 'template_code' => 'REC-INS-02'],
                [
                    'template_id' => (string) Str::uuid(),
                    'template_name' => 'Cold-Storage & Fire Insurance Amortization',
                    'journal_type' => 'RECURRING',
                    'frequency' => 'MONTHLY',
                    'next_run_date' => now()->endOfMonth()->toDateString(),
                    'auto_post' => true,
                    'default_branch_id' => 'BR-01',
                    'description' => 'Monthly commercial cold-storage insurance expense.',
                    'lines_json' => [
                        ['account_id' => $insExp->account_id, 'account_code' => $insExp->account_code, 'account_name' => $insExp->account_name, 'branch_id' => 'BR-01', 'department_id' => 'LOGISTICS', 'debit' => 12500, 'credit' => 0, 'description' => 'Monthly Insurance Expense'],
                        ['account_id' => $cashBank->account_id, 'account_code' => $cashBank->account_code, 'account_name' => $cashBank->account_name, 'branch_id' => 'BR-01', 'department_id' => 'LOGISTICS', 'debit' => 0, 'credit' => 12500, 'description' => 'Auto-debit Insurance Policy'],
                    ],
                ]
            );
        }
    }

    protected function seedSampleJournals(string $companyId): void
    {
        if (GlJournalHeader::where('company_id', $companyId)->exists()) {
            return;
        }

        $today = now()->toDateString();
        $cashBank = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'CASH_IN_BANK')->first();
        $equity = ChartOfAccount::where('company_id', $companyId)->where('account_code', '3010')->first();
        $invAsset = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'INVENTORY_ASSET')->first();
        $grni = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'GRNI_ACCRUED_PURCHASES')->first();
        $inputVat = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'INPUT_VAT')->first();
        $ewtPayable = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'EWT_PAYABLE')->first();
        $apAcc = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'ACCOUNTS_PAYABLE')->first();
        $salesRev = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'SALES_REVENUE')->first();
        $outputVat = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'OUTPUT_VAT')->first();
        $arAcc = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'ACCOUNTS_RECEIVABLE')->first();
        $cogs = ChartOfAccount::where('company_id', $companyId)->where('system_role', 'COGS')->first();
        $rentExp = ChartOfAccount::where('company_id', $companyId)->where('account_code', '6020')->first();

        // 1. Opening Balance (Posted)
        if ($cashBank && $equity) {
            $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $today,
                'journal_type' => 'OPENING_BALANCE',
                'source_module' => 'GL',
                'description' => 'Opening Operating Bank Capital Funding',
                'acted_by' => 'System Setup',
            ], [
                ['account_id' => $cashBank->account_id, 'branch_id' => 'BR-01', 'debit' => 1500000, 'credit' => 0, 'description' => 'Initial Operating Cash'],
                ['account_id' => $equity->account_id, 'branch_id' => 'BR-01', 'debit' => 0, 'credit' => 1500000, 'description' => 'Paid-in Share Capital'],
            ], 'POSTED');
        }

        // 2. Inventory GRN Accrual (System Generated - Read Only)
        if ($invAsset && $grni) {
            $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $today,
                'journal_type' => 'SYSTEM_INVENTORY',
                'source_module' => 'INVENTORY',
                'source_document_type' => 'GOODS_RECEIPT',
                'source_document_id' => 'GRN-2026-0101',
                'source_document_number' => 'GRN-2026-0101',
                'is_system_generated' => true,
                'description' => 'Goods Receipt GRN-2026-0101 — Commissary Raw Ingredients',
                'acted_by' => 'Inventory Engine',
            ], [
                ['account_id' => $invAsset->account_id, 'branch_id' => 'BR-01', 'department_id' => 'COMMISSARY', 'debit' => 100000, 'credit' => 0, 'description' => 'Inventory Stock-In Valuation'],
                ['account_id' => $grni->account_id, 'branch_id' => 'BR-01', 'department_id' => 'COMMISSARY', 'debit' => 0, 'credit' => 100000, 'description' => 'GRNI Accrued Purchases'],
            ], 'POSTED');
        }

        // 3. AP Vendor Bill clearing GRNI + Input VAT + EWT (System Generated - Read Only)
        if ($grni && $inputVat && $ewtPayable && $apAcc) {
            $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $today,
                'journal_type' => 'SYSTEM_AP',
                'source_module' => 'AP',
                'source_document_type' => 'VENDOR_BILL',
                'source_document_id' => 'BILL-2026-0001',
                'source_document_number' => 'BILL-2026-0001',
                'is_system_generated' => true,
                'description' => '3-Way Matched Vendor Bill BILL-2026-0001 (Clear GRNI + 12% VAT + 1% EWT)',
                'acted_by' => 'AP Posting Engine',
            ], [
                ['account_id' => $grni->account_id, 'branch_id' => 'BR-01', 'department_id' => 'COMMISSARY', 'partner_type' => 'VENDOR', 'partner_name' => 'Highland Fresh Dairy Cooperative', 'debit' => 100000, 'credit' => 0, 'description' => 'Clear GRNI-2026-0101'],
                ['account_id' => $inputVat->account_id, 'branch_id' => 'BR-01', 'tax_code' => 'VAT12_IN', 'debit' => 12000, 'credit' => 0, 'description' => '12% Input VAT'],
                ['account_id' => $ewtPayable->account_id, 'branch_id' => 'BR-01', 'tax_code' => 'EWT_1_GOODS', 'debit' => 0, 'credit' => 1000, 'description' => '1% EWT Withheld (WC158)'],
                ['account_id' => $apAcc->account_id, 'branch_id' => 'BR-01', 'partner_type' => 'VENDOR', 'partner_name' => 'Highland Fresh Dairy Cooperative', 'debit' => 0, 'credit' => 111000, 'description' => 'Net Payable to Vendor'],
            ], 'POSTED');
        }

        // 4. AR Corporate Catering Sales Invoice (System Generated - Read Only)
        if ($arAcc && $salesRev && $outputVat && $cogs && $invAsset) {
            $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $today,
                'journal_type' => 'SYSTEM_AR',
                'source_module' => 'AR',
                'source_document_type' => 'SALES_INVOICE',
                'source_document_id' => 'SI-2026-0001',
                'source_document_number' => 'SI-2026-0001',
                'is_system_generated' => true,
                'description' => 'Sales Invoice SI-2026-0001 — Corporate Catering & Store Sales',
                'acted_by' => 'AR Posting Engine',
            ], [
                ['account_id' => $arAcc->account_id, 'branch_id' => 'BR-01', 'department_id' => 'SALES', 'partner_type' => 'CUSTOMER', 'partner_name' => 'BGC Corporate Hub Client', 'debit' => 280000, 'credit' => 0, 'description' => 'Receivable Gross Sales'],
                ['account_id' => $salesRev->account_id, 'branch_id' => 'BR-01', 'department_id' => 'SALES', 'debit' => 0, 'credit' => 250000, 'description' => 'Net Sales Revenue'],
                ['account_id' => $outputVat->account_id, 'branch_id' => 'BR-01', 'tax_code' => 'VAT12_OUT', 'debit' => 0, 'credit' => 30000, 'description' => '12% Output VAT'],
                ['account_id' => $cogs->account_id, 'branch_id' => 'BR-01', 'department_id' => 'KITCHEN', 'debit' => 75000, 'credit' => 0, 'description' => 'COGS Stock Out'],
                ['account_id' => $invAsset->account_id, 'branch_id' => 'BR-01', 'department_id' => 'KITCHEN', 'debit' => 0, 'credit' => 75000, 'description' => 'Inventory Reduction'],
            ], 'POSTED');
        }

        // 5. Draft Adjusting Journal + Approval Queue Request
        if ($rentExp && $apAcc) {
            $draftJe = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => 'BR-01',
                'journal_date' => $today,
                'journal_type' => 'ADJUSTING',
                'source_module' => 'GL',
                'description' => 'Pending Accrual — Cold-Chain Storage Maintenance Fee',
                'acted_by' => 'Junior Accountant',
            ], [
                ['account_id' => $rentExp->account_id, 'branch_id' => 'BR-01', 'department_id' => 'LOGISTICS', 'project_code' => 'COLD-MAINT', 'debit' => 18500, 'credit' => 0, 'description' => 'Cold Storage Maintenance Accrual'],
                ['account_id' => $apAcc->account_id, 'branch_id' => 'BR-01', 'department_id' => 'LOGISTICS', 'project_code' => 'COLD-MAINT', 'debit' => 0, 'credit' => 18500, 'description' => 'Accrued Vendor Payable'],
            ], 'DRAFT');

            if (Schema::hasTable('approval_requests')) {
                $wf = DB::table('approval_workflows')->where('approval_workflow_code', 'ACCT_GL_JOURNAL')->first();
                if ($wf) {
                    $reqId = (string) Str::uuid();
                    DB::table('approval_requests')->insert([
                        'approval_request_id' => $reqId,
                        'approval_workflow_id' => $wf->approval_workflow_id,
                        'approval_document_type' => 'GL_JOURNAL',
                        'approval_document_id' => $draftJe->journal_id,
                        'approval_document_number' => $draftJe->journal_number,
                        'approval_amount' => 18500,
                        'approval_currency_code' => 'PHP',
                        'approval_submission_number' => 1,
                        'approval_current_step_number' => 1,
                        'approval_request_status' => 'PENDING',
                        'approval_request_created_at' => now(),
                        'approval_request_updated_at' => now(),
                    ]);
                    $draftJe->approval_request_id = $reqId;
                    $draftJe->save();
                }
            }
        }
    }
}

