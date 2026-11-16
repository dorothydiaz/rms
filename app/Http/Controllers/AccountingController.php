<?php

namespace App\Http\Controllers;

use App\Models\Accounting\AcctAuditLog;
use App\Models\Accounting\AcctBankAccount;
use App\Models\Accounting\AcctBankReconciliation;
use App\Models\Accounting\AcctBankStatementLine;
use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctCheckRegister;
use App\Models\Accounting\AcctCollectionReceipt;
use App\Models\Accounting\AcctCurrency;
use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctCustomerMemo;
use App\Models\Accounting\AcctDocumentAttachment;
use App\Models\Accounting\AcctEmployeeAdvance;
use App\Models\Accounting\AcctEmployeeAdvanceLiquidation;
use App\Models\Accounting\AcctExchangeRate;
use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\AcctFiscalYear;
use App\Models\Accounting\AcctFixedAsset;
use App\Models\Accounting\AcctFixedAssetDepreciation;
use App\Models\Accounting\AcctFixedAssetDisposal;
use App\Models\Accounting\AcctFundTransfer;
use App\Models\Accounting\AcctLoan;
use App\Models\Accounting\AcctLoanAmortization;
use App\Models\Accounting\AcctLoanPayment;
use App\Models\Accounting\AcctNumberingSeries;
use App\Models\Accounting\AcctPaymentTerm;
use App\Models\Accounting\AcctPeriodCloseChecklist;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\AcctTaxCode;
use App\Models\Accounting\AcctVendorMemo;
use App\Models\Accounting\AcctVendorPayment;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlRecurringTemplate;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Accounting\VendorBillLine;
use App\Models\Branch;
use App\Models\Hr\Department;
use App\Services\Accounting\AccountingApService;
use App\Services\Accounting\AccountingArService;
use App\Services\Accounting\AccountingCashBankService;
use App\Services\Accounting\AccountingFixedAssetService;
use App\Services\Accounting\AccountingGeneralLedgerService;
use App\Services\Accounting\AccountingLoanService;
use App\Services\Accounting\AccountingNumberingService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingReportService;
use App\Services\Accounting\AccountingSeedService;
use App\Services\Accounting\AccountingTaxReportService;
use App\Services\CompanyBranchContext;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class AccountingController extends Controller
{
    public function __construct(
        protected AccountingSeedService $seedService,
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService,
        protected AccountingApService $apService,
        protected AccountingArService $arService,
        protected AccountingCashBankService $cashBankService,
        protected AccountingFixedAssetService $fixedAssetService,
        protected AccountingLoanService $loanService,
        protected AccountingGeneralLedgerService $glService,
        protected AccountingTaxReportService $taxReportService,
        protected AccountingReportService $reportService,
        protected CompanyBranchContext $companyContext
    ) {
    }

    protected function resolveActiveCompanyId(): string
    {
        try {
            $company = $this->companyContext->getActiveCompany();
            if ($company && $company->company_id) {
                return (string) $company->company_id;
            }
        } catch (Throwable $e) {
            // Fall back cleanly when session context is unavailable in tests
        }

        return '1';
    }

    protected function buildWorkspacePayload(Request $request, string $activePage = 'dashboard'): array
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $periodFilter = $request->query('period_id');
        $branchFilter = $request->query('branch_id');

        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        $balances = $this->postingService->getAccountBalances($companyId, $periodFilter ?: null, $branchFilter ?: null);

        $enrichedAccounts = $accounts->map(function (ChartOfAccount $acc) use ($balances) {
            $row = $balances[$acc->account_id] ?? null;
            $debit = $row ? round((float) $row->total_debit, 2) : 0.0;
            $credit = $row ? round((float) $row->total_credit, 2) : 0.0;
            $net = $acc->normal_balance === 'CREDIT'
                ? round($credit - $debit, 2)
                : round($debit - $credit, 2);

            return array_merge($acc->toArray(), [
                'total_debit' => $debit,
                'total_credit' => $credit,
                'net_balance' => $net,
                'is_protected_role' => !empty($acc->system_role),
            ]);
        })->values();

        // KPI Aggregation from Posted GL Balances
        $byRole = [];
        $typeTotals = ['ASSET' => 0.0, 'LIABILITY' => 0.0, 'EQUITY' => 0.0, 'REVENUE' => 0.0, 'COGS' => 0.0, 'EXPENSE' => 0.0];
        foreach ($enrichedAccounts as $acc) {
            if (!empty($acc['system_role'])) {
                $byRole[$acc['system_role']] = ($byRole[$acc['system_role']] ?? 0.0) + $acc['net_balance'];
            }
            $t = $acc['account_type'];
            if (isset($typeTotals[$t])) {
                $typeTotals[$t] += $acc['net_balance'];
            }
        }

        $cashPosition = round(($byRole['CASH_ON_HAND'] ?? 0.0) + ($byRole['CASH_IN_BANK'] ?? 0.0), 2);
        $payables = round($byRole['ACCOUNTS_PAYABLE'] ?? 0.0, 2);
        $receivables = round($byRole['ACCOUNTS_RECEIVABLE'] ?? 0.0, 2);
        $netProfit = round($typeTotals['REVENUE'] - $typeTotals['COGS'] - $typeTotals['EXPENSE'], 2);

        $overdueBills = 0;
        if (Schema::hasTable('vendor_bill_header')) {
            $overdueBills = DB::table('vendor_bill_header')
                ->whereIn('vendor_bill_status', ['POSTED', 'APPROVED', 'PARTIAL'])
                ->whereDate('bill_due_date', '<', now()->toDateString())
                ->count();
        }

        $journals = GlJournalHeader::with('lines')
            ->where('company_id', $companyId)
            ->orderByDesc('journal_date')
            ->orderByDesc('created_at')
            ->take(100)
            ->get();

        $fiscalYears = AcctFiscalYear::where('company_id', $companyId)->orderByDesc('year_code')->get();
        $periods = AcctFiscalPeriod::where('company_id', $companyId)->orderBy('start_date')->get();
        $currencies = AcctCurrency::where('is_active', true)->orderByDesc('is_base_currency')->orderBy('currency_code')->get();
        $exchangeRates = AcctExchangeRate::where('company_id', $companyId)->orderByDesc('effective_date')->get();
        $paymentTerms = AcctPaymentTerm::where('company_id', $companyId)->orderBy('due_days')->get();
        $numberingSeries = AcctNumberingSeries::where('company_id', $companyId)->orderBy('module_code')->get();
        $taxCodes = AcctTaxCode::where('company_id', $companyId)->orderBy('tax_type')->orderBy('tax_code')->get();
        $recurringTemplates = GlRecurringTemplate::where('company_id', $companyId)->orderBy('template_code')->get();

        $branches = Schema::hasTable('branches')
            ? Branch::where('branch_is_active', true)->orderBy('branch_code')->get(['branch_id', 'branch_code', 'branch_name'])
            : collect([['branch_id' => 'BR-01', 'branch_code' => 'BR-01', 'branch_name' => 'Main Commissary & Store']]);
        if ($branches->isEmpty()) {
            $branches = collect([['branch_id' => 'BR-01', 'branch_code' => 'BR-01', 'branch_name' => 'Main Commissary & Store']]);
        }

        $departments = Schema::hasTable('hr_departments')
            ? Department::where('is_active', true)->orderBy('code')->get(['department_id', 'code', 'name'])
            : collect([]);

        $approvalWorkflows = Schema::hasTable('approval_workflows')
            ? DB::table('approval_workflows')->where('approval_document_type', 'like', '%')->orderBy('approval_workflow_code')->get()
            : collect([]);

        $pendingApprovals = Schema::hasTable('approval_requests')
            ? DB::table('approval_requests')
                ->where('approval_request_status', 'PENDING')
                ->whereIn('approval_document_type', ['GL_JOURNAL', 'VENDOR_BILL', 'VENDOR_PAYMENT', 'PERIOD_CLOSE'])
                ->orderByDesc('approval_request_created_at')
                ->get()
            : collect([]);

        $auditLogs = AcctAuditLog::where('company_id', $companyId)
            ->orderByDesc('acted_at')
            ->take(100)
            ->get();

        $attachments = AcctDocumentAttachment::where('company_id', $companyId)
            ->orderByDesc('uploaded_at')
            ->take(100)
            ->get();

        // Accounts Payable (AP) Data — auto-sync approved PO obligations into Vendor Bills tracker
        $this->apService->syncAllPurchaseOrderBills($companyId);

        $vendorBills = VendorBillHeader::with(['lines', 'paymentAllocations.payment'])
            ->where('company_id', $companyId)
            ->orderByDesc('vendor_bill_created_at')
            ->take(100)
            ->get();

        $billIdsForCredits = $vendorBills->pluck('vendor_bill_id')->filter()->all();
        $creditLogsByBill = !empty($billIdsForCredits)
            ? AcctAuditLog::where('company_id', $companyId)
                ->where('document_type', 'VENDOR_CREDIT_APPLIED')
                ->whereIn('document_id', $billIdsForCredits)
                ->orderBy('acted_at')
                ->get()
                ->groupBy('document_id')
            : collect([]);

        $vendorBills->each(function ($bill) use ($creditLogsByBill) {
            $credits = $creditLogsByBill->get($bill->vendor_bill_id, collect([]))->map(function ($log) {
                $diff = is_array($log->payload_diff_json) ? $log->payload_diff_json : [];
                return [
                    'memo_number' => $diff['memo_number'] ?? $log->document_number,
                    'applied_amount' => (float) ($diff['applied_amount'] ?? 0),
                    'acted_at' => $log->acted_at ? $log->acted_at->toDateString() : now()->toDateString(),
                    'acted_by' => $log->acted_by ?? 'AP Officer',
                    'summary' => $log->summary,
                ];
            })->values();
            $bill->setAttribute('credit_applications', $credits);
        });

        $vendorPayments = AcctVendorPayment::with('lines')
            ->where('company_id', $companyId)
            ->orderByDesc('payment_date')
            ->take(100)
            ->get();

        $poIdsWithApPayments = VendorBillHeader::where('company_id', $companyId)
            ->whereNotNull('purchase_order_id')
            ->whereHas('paymentAllocations')
            ->pluck('purchase_order_id')
            ->map(fn($id) => (string) $id)
            ->all();

        $poAdvancePayments = Schema::hasTable('purchase_order_header')
            ? DB::table('purchase_order_header')
                ->where('amount_paid', '>', 0)
                ->when(!empty($poIdsWithApPayments), function ($q) use ($poIdsWithApPayments) {
                    $numericIds = array_values(array_filter($poIdsWithApPayments, 'is_numeric'));
                    if (!empty($numericIds)) {
                        $q->whereNotIn('id', array_map('intval', $numericIds));
                    }
                })
                ->orderByDesc('payment_date')
                ->take(50)
                ->get([
                    'id', 'po_number', 'vendor_id', 'vendor_name', 'amount_paid',
                    'balance_due', 'payment_status', 'payment_method', 'payment_reference',
                    'payment_date', 'fund_source', 'payment_remarks', 'gross_total',
                ])
                ->map(function ($po) {
                    return (object) [
                        'payment_id' => 'PO-' . $po->id,
                        'payment_number' => 'PO-ADV-' . $po->po_number,
                        'payment_date' => $po->payment_date ?: now()->toDateString(),
                        'vendor_id' => $po->vendor_id,
                        'vendor_name' => $po->vendor_name,
                        'payment_method' => $po->payment_method ?: 'ADVANCE',
                        'reference_number' => $po->payment_reference ?: ('PO: ' . $po->po_number),
                        'check_number' => null,
                        'total_amount' => (float) $po->amount_paid,
                        'total_ewt_amount' => 0.00,
                        'total_net_amount' => (float) $po->amount_paid,
                        'status' => 'PO_ADVANCE',
                        'is_po_advance' => true,
                        'source_po_number' => $po->po_number,
                    ];
                })
            : collect([]);

        $allVendorPayments = $vendorPayments->concat($poAdvancePayments)->sortByDesc('payment_date')->values();

        $vendorMemos = AcctVendorMemo::where('company_id', $companyId)
            ->orderByDesc('memo_date')
            ->take(100)
            ->get();

        $vendorCertificates = AcctBir2307Certificate::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->take(100)
            ->get();

        $vendorAging = [];
        try {
            $vendorAging = $this->apService->getVendorLedgerAndAging($companyId);
        } catch (Throwable $e) {
        }

        $vendors = Schema::hasTable('vendor_master')
            ? DB::table('vendor_master')
                ->where('is_active', true)
                ->orderBy('trade_name')
                ->get(['id', 'vendor_code', 'trade_name', 'legal_name', 'tax_id', 'payment_terms'])
            : collect([]);

        $vendorLookupById = $vendors->keyBy(fn($v) => (string) $v->id);
        $vendorLookupByCode = $vendors->keyBy(fn($v) => (string) $v->vendor_code);

        $purchaseOrders = Schema::hasTable('purchase_order_header')
            ? DB::table('purchase_order_header')
                ->whereNotIn('status', ['Cancelled', 'CANCELLED', 'REFUSED', 'Archived'])
                ->orderByDesc('order_date')
                ->orderByDesc('id')
                ->take(100)
                ->get([
                    'id', 'po_number', 'vendor_id', 'vendor_name', 'order_date',
                    'status', 'subtotal', 'tax_amount', 'gross_total', 'amount_paid',
                    'balance_due', 'payment_status', 'payment_method', 'payment_reference',
                    'payment_date'
                ])
            : collect([]);

        $poIds = $purchaseOrders->pluck('id')->filter()->all();
        $poLines = (!empty($poIds) && Schema::hasTable('purchase_order_lines'))
            ? DB::table('purchase_order_lines')
                ->whereIn('purchase_order_id', $poIds)
                ->get(['id', 'purchase_order_id', 'sku', 'item_name', 'specs', 'category', 'uom', 'quantity', 'unit_price', 'total_amount', 'received_quantity', 'status'])
                ->groupBy('purchase_order_id')
            : collect([]);

        $purchaseOrders = $purchaseOrders->map(function ($po) use ($poLines, $vendorLookupById, $vendorLookupByCode, $vendors) {
            $po->lines = $poLines->get($po->id, collect([]))->values();
            $matchedVendor = $vendorLookupById->get((string) $po->vendor_id)
                ?: $vendorLookupByCode->get((string) $po->vendor_id)
                ?: $vendors->first(fn($v) => strcasecmp((string) ($v->trade_name ?: $v->legal_name), (string) $po->vendor_name) === 0 || strcasecmp((string) $v->legal_name, (string) $po->vendor_name) === 0);
            $po->resolved_vendor_id = $matchedVendor ? (string) $matchedVendor->id : (string) $po->vendor_id;
            return $po;
        });

        $goodsReceipts = Schema::hasTable('stock_in_header')
            ? DB::table('stock_in_header')
                ->orderByDesc('received_at')
                ->take(50)
                ->get(['id', 'grn_number', 'po_number', 'vendor_id', 'vendor_name', 'received_at', 'status'])
            : collect([]);

        // Accounts Receivable (AR) Data
        $customers = AcctCustomer::where('company_id', $companyId)
            ->orderBy('customer_name')
            ->get();

        $salesInvoices = AcctSalesInvoice::with('lines')
            ->where('company_id', $companyId)
            ->orderByDesc('invoice_date')
            ->take(100)
            ->get();

        $collectionReceipts = AcctCollectionReceipt::with('lines')
            ->where('company_id', $companyId)
            ->orderByDesc('receipt_date')
            ->take(100)
            ->get();

        $customerMemos = AcctCustomerMemo::where('company_id', $companyId)
            ->orderByDesc('memo_date')
            ->take(100)
            ->get();

        $customerAging = [];
        try {
            $customerAging = $this->arService->getCustomerLedgerAndAging($companyId);
        } catch (Throwable $e) {
        }

        // Cash & Bank Data
        $bankAccounts = AcctBankAccount::with('glAccount')
            ->where('company_id', $companyId)
            ->orderBy('account_name')
            ->get();

        $employeeAdvances = AcctEmployeeAdvance::with('liquidations')
            ->where('company_id', $companyId)
            ->orderByDesc('advance_date')
            ->take(100)
            ->get();

        $fundTransfers = AcctFundTransfer::where('company_id', $companyId)
            ->orderByDesc('transfer_date')
            ->take(100)
            ->get();

        $checkRegister = AcctCheckRegister::where('company_id', $companyId)
            ->orderByDesc('check_date')
            ->take(100)
            ->get();

        $bankReconciliations = AcctBankReconciliation::with('lines')
            ->where('company_id', $companyId)
            ->orderByDesc('statement_date')
            ->take(50)
            ->get();

        // Wave 3 Fixed Assets, Loans & Period Close
        $fixedAssets = AcctFixedAsset::with(['depreciations', 'disposal'])
            ->where('company_id', $companyId)
            ->orderByDesc('acquisition_date')
            ->take(100)
            ->get();

        $fixedAssetDepreciations = AcctFixedAssetDepreciation::with('asset')
            ->where('company_id', $companyId)
            ->orderByDesc('depreciation_date')
            ->take(100)
            ->get();

        $fixedAssetDisposals = AcctFixedAssetDisposal::with('asset')
            ->where('company_id', $companyId)
            ->orderByDesc('disposal_date')
            ->take(100)
            ->get();

        $loans = AcctLoan::with(['amortizations', 'payments'])
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->take(100)
            ->get();

        $loanPayments = AcctLoanPayment::with('loan')
            ->where('company_id', $companyId)
            ->orderByDesc('payment_date')
            ->take(100)
            ->get();

        $periodChecklists = AcctPeriodCloseChecklist::where('company_id', $companyId)->get();

        $employees = Schema::hasTable('hr_employees')
            ? DB::table('hr_employees')
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get(['employee_id', 'employee_number', 'first_name', 'last_name'])
            : collect([]);

        // Upcoming PDC maturities and unreconciled bank items for KPIs
        $upcomingPdcMaturities = AcctCheckRegister::where('company_id', $companyId)
            ->whereIn('status', ['ISSUED', 'RECEIVED'])
            ->whereDate('maturity_date', '>=', now()->toDateString())
            ->count();

        $unreconciledBankItems = AcctBankStatementLine::whereHas('reconciliation', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('is_matched', false)->count();

        $payablesTotal = $vendorBills->whereIn('vendor_bill_status', ['POSTED', 'PARTIAL'])->sum(function ($b) {
            return (float) $b->bill_total_amount - (float) $b->bill_paid_amount;
        });
        if ($payablesTotal > 0) {
            $payables = round($payablesTotal, 2);
        }

        $receivablesTotal = $salesInvoices->whereIn('status', ['POSTED', 'PARTIAL'])->sum(function ($inv) {
            return (float) $inv->total_amount - (float) $inv->paid_amount;
        });
        if ($receivablesTotal > 0) {
            $receivables = round($receivablesTotal, 2);
        }

        $activeCompanyObj = null;
        try {
            $activeCompanyObj = $this->companyContext->getActiveCompany();
        } catch (Throwable $e) {
        }

        return [
            'activePage' => $activePage,
            'companyId' => $companyId,
            'companyName' => $activeCompanyObj?->company_trade_name ?: ($activeCompanyObj?->company_legal_name ?: 'RMS Holdings Corp.'),
            'baseCurrency' => $activeCompanyObj?->company_base_currency_code ?: 'PHP',
            'systemRoles' => ChartOfAccount::SYSTEM_ROLES,
            'accountTypes' => ChartOfAccount::ACCOUNT_TYPES,
            'cashFlowCategories' => ChartOfAccount::CASH_FLOW_CATEGORIES,
            'journalTypes' => GlJournalHeader::JOURNAL_TYPES,
            'kpis' => [
                'cashPosition' => $cashPosition,
                'payables' => $payables,
                'receivables' => $receivables,
                'netProfit' => $netProfit,
                'overdueBills' => $overdueBills,
                'pendingApprovals' => $pendingApprovals->count(),
                'unreconciledBankItems' => $unreconciledBankItems,
                'openPeriods' => $periods->where('status', 'OPEN')->count(),
                'upcomingPdcMaturities' => $upcomingPdcMaturities,
            ],
            'accounts' => $enrichedAccounts,
            'journals' => $journals,
            'recurringTemplates' => $recurringTemplates,
            'fiscalYears' => $fiscalYears,
            'periods' => $periods,
            'currencies' => $currencies,
            'exchangeRates' => $exchangeRates,
            'paymentTerms' => $paymentTerms,
            'numberingSeries' => $numberingSeries,
            'taxCodes' => $taxCodes,
            'branches' => $branches,
            'departments' => $departments,
            'approvalWorkflows' => $approvalWorkflows,
            'pendingApprovals' => $pendingApprovals,
            'auditLogs' => $auditLogs,
            'attachments' => $attachments,
            // Wave 2 AP / AR / Cash & Bank
            'vendorBills' => $vendorBills,
            'vendorPayments' => $allVendorPayments,
            'poAdvancePayments' => $poAdvancePayments,
            'vendorMemos' => $vendorMemos,
            'vendorCertificates' => $vendorCertificates,
            'vendorAging' => $vendorAging,
            'vendors' => $vendors,
            'purchaseOrders' => $purchaseOrders,
            'goodsReceipts' => $goodsReceipts,
            'customers' => $customers,
            'salesInvoices' => $salesInvoices,
            'collectionReceipts' => $collectionReceipts,
            'customerMemos' => $customerMemos,
            'customerAging' => $customerAging,
            'bankAccounts' => $bankAccounts,
            'employeeAdvances' => $employeeAdvances,
            'fundTransfers' => $fundTransfers,
            'checkRegister' => $checkRegister,
            'bankReconciliations' => $bankReconciliations,
            'employees' => $employees,
            // Wave 3 Fixed Assets, Loans & Period Close
            'fixedAssets' => $fixedAssets,
            'fixedAssetDepreciations' => $fixedAssetDepreciations,
            'fixedAssetDisposals' => $fixedAssetDisposals,
            'loans' => $loans,
            'loanPayments' => $loanPayments,
            'periodChecklists' => $periodChecklists,
        ];
    }

    // =========================================================================
    // 10 SUBMODULE WORKSPACE VIEWS
    // =========================================================================

    public function dashboard(Request $request): View
    {
        return view('accounting.dashboard', ['accountingPayload' => $this->buildWorkspacePayload($request, 'dashboard')]);
    }

    public function accountsPayable(Request $request): View
    {
        return view('accounting.accounts-payable', ['accountingPayload' => $this->buildWorkspacePayload($request, 'ap')]);
    }

    public function accountsReceivable(Request $request): View
    {
        return view('accounting.accounts-receivable', ['accountingPayload' => $this->buildWorkspacePayload($request, 'ar')]);
    }

    public function cashAndBank(Request $request): View
    {
        return view('accounting.cash-bank', ['accountingPayload' => $this->buildWorkspacePayload($request, 'cash-bank')]);
    }

    public function generalLedger(Request $request): View
    {
        return view('accounting.general-ledger', ['accountingPayload' => $this->buildWorkspacePayload($request, 'gl')]);
    }

    public function fixedAssets(Request $request): View
    {
        return view('accounting.fixed-assets', ['accountingPayload' => $this->buildWorkspacePayload($request, 'fixed-assets')]);
    }

    public function loans(Request $request): View
    {
        return view('accounting.loans', ['accountingPayload' => $this->buildWorkspacePayload($request, 'loans')]);
    }

    public function tax(Request $request): View
    {
        return view('accounting.tax', ['accountingPayload' => $this->buildWorkspacePayload($request, 'tax')]);
    }

    public function financialReports(Request $request): View
    {
        return view('accounting.financial-reports', ['accountingPayload' => $this->buildWorkspacePayload($request, 'reports')]);
    }

    public function configuration(Request $request): View
    {
        return view('accounting.configuration', ['accountingPayload' => $this->buildWorkspacePayload($request, 'config')]);
    }

    // =========================================================================
    // CHART OF ACCOUNTS (PROTECTED SYSTEM_ROLE) APIs
    // =========================================================================

    public function apiSaveAccount(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'account_id' => 'nullable|string|max:60',
            'account_code' => 'required|string|max:30',
            'account_name' => 'required|string|max:150',
            'account_type' => 'required|in:' . implode(',', ChartOfAccount::ACCOUNT_TYPES),
            'normal_balance' => 'required|in:' . implode(',', ChartOfAccount::NORMAL_BALANCES),
            'cash_flow_category' => 'nullable|in:' . implode(',', ChartOfAccount::CASH_FLOW_CATEGORIES),
            'system_role' => 'nullable|string|max:60',
            'default_tax_code' => 'nullable|string|max:30',
            'allow_manual_journal' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        try {
            $account = DB::transaction(function () use ($validated, $companyId) {
                $existing = !empty($validated['account_id'])
                    ? ChartOfAccount::where('company_id', $companyId)->where('account_id', $validated['account_id'])->lockForUpdate()->firstOrFail()
                    : null;

                $payload = [
                    'company_id' => $companyId,
                    'account_code' => strtoupper(trim($validated['account_code'])),
                    'account_name' => trim($validated['account_name']),
                    'account_type' => strtoupper($validated['account_type']),
                    'normal_balance' => strtoupper($validated['normal_balance']),
                    'cash_flow_category' => strtoupper($validated['cash_flow_category'] ?? 'OPERATING'),
                    'system_role' => !empty($validated['system_role']) ? strtoupper(trim($validated['system_role'])) : null,
                    'default_tax_code' => $validated['default_tax_code'] ?? null,
                    'allow_manual_journal' => $validated['allow_manual_journal'] ?? true,
                    'is_active' => $validated['is_active'] ?? true,
                    'description' => $validated['description'] ?? null,
                ];

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                    $acc = $existing;
                } else {
                    $acc = ChartOfAccount::create($payload);
                }

                $this->postingService->recordAudit(
                    companyId: $companyId,
                    branchId: null,
                    module: 'GL',
                    documentType: 'COA_ACCOUNT',
                    documentId: $acc->account_id,
                    documentNumber: $acc->account_code,
                    action: $existing ? 'UPDATED' : 'CREATED',
                    summary: "Chart of Accounts [{$acc->account_code}] {$acc->account_name} saved."
                );

                return $acc;
            });

            return response()->json(['success' => true, 'message' => "Account [{$account->account_code}] saved.", 'data' => $account]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiDeleteAccount(string $accountId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $account = ChartOfAccount::where('company_id', $companyId)->where('account_id', $accountId)->first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        }

        try {
            if ($account->journalLines()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "Account [{$account->account_code}] has journal history and cannot be deleted; deactivate it instead.",
                ], 422);
            }

            $code = $account->account_code;
            $account->delete();

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: null,
                module: 'GL',
                documentType: 'COA_ACCOUNT',
                documentId: $accountId,
                documentNumber: $code,
                action: 'DELETED',
                summary: "Deleted Chart of Accounts entry [{$code}]."
            );

            return response()->json(['success' => true, 'message' => "Account [{$code}] deleted."]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // GENERAL LEDGER JOURNAL LIFECYCLE & IMMUTABILITY APIs
    // =========================================================================

    public function apiSaveJournal(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'journal_id' => 'nullable|string|max:60',
            'journal_date' => 'required|date',
            'journal_type' => 'required|in:' . implode(',', GlJournalHeader::JOURNAL_TYPES),
            'branch_id' => 'nullable|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'description' => 'nullable|string',
            'target_status' => 'nullable|in:DRAFT,APPROVED,POSTED',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'nullable|string|max:60',
            'lines.*.account_code' => 'nullable|string|max:30',
            'lines.*.system_role' => 'nullable|string|max:60',
            'lines.*.branch_id' => 'nullable|string|max:50',
            'lines.*.department_id' => 'nullable|string|max:50',
            'lines.*.project_code' => 'nullable|string|max:50',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.transaction_debit' => 'nullable|numeric|min:0',
            'lines.*.transaction_credit' => 'nullable|numeric|min:0',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.tax_code' => 'nullable|string|max:30',
        ]);

        try {
            $journal = $this->postingService->saveDraftOrPostJournal(
                array_merge($validated, ['company_id' => $companyId]),
                $validated['lines'],
                $validated['target_status'] ?? 'DRAFT'
            );

            // Queue into Approval Inbox when saved as Draft for maker-checker review
            if ($journal->status === 'DRAFT' && empty($journal->approval_request_id) && Schema::hasTable('approval_requests')) {
                $wf = DB::table('approval_workflows')->where('approval_workflow_code', 'ACCT_GL_JOURNAL')->first();
                if ($wf) {
                    $reqId = (string) Str::uuid();
                    DB::table('approval_requests')->insert([
                        'approval_request_id' => $reqId,
                        'approval_workflow_id' => $wf->approval_workflow_id,
                        'approval_document_type' => 'GL_JOURNAL',
                        'approval_document_id' => $journal->journal_id,
                        'approval_document_number' => $journal->journal_number,
                        'approval_amount' => $journal->total_debit,
                        'approval_currency_code' => $journal->currency_code,
                        'approval_submission_number' => 1,
                        'approval_current_step_number' => 1,
                        'approval_request_status' => 'PENDING',
                        'approval_request_created_at' => now(),
                        'approval_request_updated_at' => now(),
                    ]);
                    $journal->approval_request_id = $reqId;
                    $journal->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Journal {$journal->journal_number} saved as {$journal->status}.",
                'data' => $journal->load('lines'),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiTransitionJournal(Request $request, string $journalId): JsonResponse
    {
        $validated = $request->validate([
            'target_status' => 'required|in:DRAFT,APPROVED,POSTED',
        ]);

        try {
            $journal = $this->postingService->transitionJournalStatus($journalId, $validated['target_status']);
            if ($journal->approval_request_id && in_array($journal->status, ['APPROVED', 'POSTED'], true) && Schema::hasTable('approval_requests')) {
                DB::table('approval_requests')
                    ->where('approval_request_id', $journal->approval_request_id)
                    ->update([
                        'approval_request_status' => 'APPROVED',
                        'approval_completed_at' => now(),
                        'approval_request_updated_at' => now(),
                    ]);
            }
            return response()->json(['success' => true, 'message' => "Journal {$journal->journal_number} is now {$journal->status}.", 'data' => $journal]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiReverseJournal(Request $request, string $journalId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'reversal_date' => 'nullable|date',
        ]);

        try {
            $reversal = $this->postingService->reverseJournal($journalId, $validated['reason'], $validated['reversal_date'] ?? null);
            return response()->json([
                'success' => true,
                'message' => "Reversal journal {$reversal->journal_number} posted.",
                'data' => $reversal,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiDeleteJournal(string $journalId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $journal = GlJournalHeader::where('company_id', $companyId)->where('journal_id', $journalId)->first();
        if (!$journal) {
            return response()->json(['success' => false, 'message' => 'Journal not found.'], 404);
        }

        try {
            $number = $journal->journal_number;
            $status = strtoupper($journal->status);
            if (in_array($status, ['POSTED', 'REVERSED'], true)) {
                throw new DomainException("Immutable Posted Record: Cannot delete a {$status} journal [{$number}].");
            }

            DB::transaction(function () use ($journal, $companyId, $number) {
                $journal->lines()->delete();
                $journal->delete();
                $this->numberingService->releaseUnusedNumber($companyId, 'JE', $number);
                $this->postingService->recordAudit(
                    companyId: $companyId,
                    branchId: $journal->branch_id,
                    module: 'GL',
                    documentType: 'GL_JOURNAL',
                    documentId: $journal->journal_id,
                    documentNumber: $number,
                    action: 'DELETED_DRAFT',
                    summary: "Deleted draft journal {$number} and released number back to gap pool."
                );
            });

            return response()->json(['success' => true, 'message' => "Draft journal {$number} deleted."]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // RECURRING JOURNALS & PERIOD CLOSE APIs
    // =========================================================================

    public function apiExecuteRecurringTemplate(string $templateId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $tpl = GlRecurringTemplate::where('company_id', $companyId)->where('template_id', $templateId)->firstOrFail();

        try {
            $targetStatus = $tpl->auto_post ? 'POSTED' : 'DRAFT';
            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $tpl->default_branch_id ?: 'BR-01',
                'journal_date' => now()->toDateString(),
                'journal_type' => 'RECURRING',
                'source_module' => 'GL',
                'source_document_type' => 'RECURRING_TEMPLATE',
                'source_document_id' => $tpl->template_id,
                'source_document_number' => $tpl->template_code,
                'description' => "{$tpl->template_name} ({$tpl->frequency})",
            ], $tpl->lines_json ?: [], $targetStatus);

            $tpl->last_run_date = now()->toDateString();
            $tpl->next_run_date = match (strtoupper($tpl->frequency)) {
                'QUARTERLY' => now()->addMonths(3)->endOfMonth()->toDateString(),
                'ANNUALLY' => now()->addYear()->endOfMonth()->toDateString(),
                default => now()->addMonth()->endOfMonth()->toDateString(),
            };
            $tpl->save();

            return response()->json([
                'success' => true,
                'message' => "Generated {$journal->status} journal {$journal->journal_number} from {$tpl->template_code}.",
                'data' => $journal,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiTogglePeriodLock(Request $request, string $periodId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $period = AcctFiscalPeriod::where('company_id', $companyId)->where('period_id', $periodId)->firstOrFail();

        $validated = $request->validate([
            'target_status' => 'required|in:OPEN,LOCKED,CLOSED',
            'checklist' => 'nullable|array',
        ]);

        $actor = Auth::user()->name ?? 'Chief Accountant';
        $period->status = $validated['target_status'];
        if (isset($validated['checklist'])) {
            $period->checklist_json = $validated['checklist'];
        }
        $period->locked_by = $validated['target_status'] === 'OPEN' ? null : $actor;
        $period->locked_at = $validated['target_status'] === 'OPEN' ? null : now();
        $period->save();

        $this->postingService->recordAudit(
            companyId: $companyId,
            branchId: null,
            module: 'GL',
            documentType: 'FISCAL_PERIOD',
            documentId: $period->period_id,
            documentNumber: $period->period_code,
            action: 'PERIOD_' . $period->status,
            summary: "Fiscal period {$period->period_name} set to {$period->status}."
        );

        return response()->json(['success' => true, 'message' => "Period {$period->period_name} is now {$period->status}.", 'data' => $period]);
    }

    // =========================================================================
    // CONFIGURATION & SYSTEM-WIDE APPROVALS / ATTACHMENTS APIs
    // =========================================================================

    public function apiSaveConfiguration(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $section = $request->input('section');

        if ($section === 'payment_term') {
            $v = $request->validate([
                'term_code' => 'required|string|max:30',
                'term_name' => 'required|string|max:100',
                'due_days' => 'required|integer|min:0|max:365',
                'discount_pct' => 'nullable|numeric|min:0|max:100',
            ]);
            $term = AcctPaymentTerm::updateOrCreate(
                ['company_id' => $companyId, 'term_code' => strtoupper($v['term_code'])],
                [
                    'term_id' => (string) Str::uuid(),
                    'term_name' => $v['term_name'],
                    'due_days' => $v['due_days'],
                    'discount_pct' => $v['discount_pct'] ?? 0,
                    'is_active' => true,
                ]
            );
            return response()->json(['success' => true, 'message' => "Payment term {$term->term_code} saved.", 'data' => $term]);
        }

        if ($section === 'exchange_rate') {
            $v = $request->validate([
                'from_currency_code' => 'required|string|size:3',
                'exchange_rate' => 'required|numeric|min:0.0001',
                'effective_date' => 'required|date',
            ]);
            $rate = AcctExchangeRate::create([
                'company_id' => $companyId,
                'from_currency_code' => strtoupper($v['from_currency_code']),
                'to_currency_code' => 'PHP',
                'exchange_rate' => $v['exchange_rate'],
                'effective_date' => $v['effective_date'],
                'created_by' => Auth::user()->name ?? 'Finance Admin',
            ]);
            return response()->json(['success' => true, 'message' => "Exchange rate {$rate->from_currency_code}/PHP = {$rate->exchange_rate} saved.", 'data' => $rate]);
        }

        if ($section === 'tax_code') {
            $v = $request->validate([
                'tax_code' => 'required|string|max:30',
                'tax_name' => 'required|string|max:120',
                'tax_type' => 'required|in:VAT_INPUT,VAT_OUTPUT,EWT,CWT,EXEMPT,ZERO_RATED',
                'rate_pct' => 'required|numeric|min:0|max:100',
                'atc_code' => 'nullable|string|max:30',
                'bir_form' => 'nullable|string|max:30',
            ]);
            $tax = AcctTaxCode::updateOrCreate(
                ['company_id' => $companyId, 'tax_code' => strtoupper($v['tax_code'])],
                [
                    'tax_id' => (string) Str::uuid(),
                    'tax_name' => $v['tax_name'],
                    'tax_type' => $v['tax_type'],
                    'rate_pct' => $v['rate_pct'],
                    'atc_code' => $v['atc_code'] ?? null,
                    'bir_form' => $v['bir_form'] ?? null,
                    'is_active' => true,
                ]
            );
            return response()->json(['success' => true, 'message' => "Tax code {$tax->tax_code} saved.", 'data' => $tax]);
        }

        if ($section === 'numbering_series') {
            $v = $request->validate([
                'module_code' => 'required|string|max:30',
                'prefix' => 'required|string|max:20',
                'next_number' => 'required|integer|min:1',
                'padding' => 'required|integer|min:3|max:8',
            ]);
            $series = AcctNumberingSeries::where('company_id', $companyId)
                ->where('module_code', strtoupper($v['module_code']))
                ->firstOrFail();
            $series->update([
                'prefix' => strtoupper($v['prefix']),
                'next_number' => $v['next_number'],
                'padding' => $v['padding'],
            ]);
            return response()->json(['success' => true, 'message' => "Numbering series {$series->module_code} updated.", 'data' => $series]);
        }

        return response()->json(['success' => false, 'message' => 'Unsupported configuration section.'], 422);
    }

    public function apiActOnApproval(Request $request, string $requestId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $validated = $request->validate([
            'action' => 'required|in:APPROVED,REJECTED,RETURNED',
            'comments' => 'nullable|string|max:500',
        ]);

        if (!Schema::hasTable('approval_requests')) {
            return response()->json(['success' => false, 'message' => 'Approval workflow tables unavailable.'], 404);
        }

        $reqRow = DB::table('approval_requests')->where('approval_request_id', $requestId)->first();
        if (!$reqRow) {
            return response()->json(['success' => false, 'message' => 'Approval request not found.'], 404);
        }

        try {
            DB::transaction(function () use ($reqRow, $validated, $companyId) {
                $action = $validated['action'];
                DB::table('approval_requests')
                    ->where('approval_request_id', $reqRow->approval_request_id)
                    ->update([
                        'approval_request_status' => $action,
                        'approval_completed_at' => now(),
                        'approval_request_updated_at' => now(),
                    ]);

                if (Schema::hasTable('approval_actions')) {
                    DB::table('approval_actions')->insert([
                        'approval_action_id' => (string) Str::uuid(),
                        'approval_request_id' => $reqRow->approval_request_id,
                        'acted_by_user_id' => null,
                        'approval_action_type' => $action,
                        'approval_action_at' => now(),
                        'approval_action_comments' => $validated['comments'] ?? null,
                    ]);
                }

                if ($reqRow->approval_document_type === 'GL_JOURNAL') {
                    $journal = GlJournalHeader::find($reqRow->approval_document_id);
                    if ($journal && $journal->status === 'DRAFT' && $action === 'APPROVED') {
                        $this->postingService->transitionJournalStatus($journal->journal_id, 'POSTED');
                    }
                }

                $this->postingService->recordAudit(
                    companyId: $companyId,
                    branchId: null,
                    module: 'APPROVAL',
                    documentType: $reqRow->approval_document_type,
                    documentId: $reqRow->approval_document_id,
                    documentNumber: $reqRow->approval_document_number,
                    action: 'APPROVAL_' . $action,
                    summary: "{$reqRow->approval_document_number} marked {$action} in Approval Inbox."
                );
            });

            return response()->json(['success' => true, 'message' => "{$reqRow->approval_document_number} {$validated['action']}."]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiStoreAttachment(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_id' => 'required|string|max:60',
            'document_number' => 'nullable|string|max:50',
            'file_name' => 'required|string|max:255',
            'file_type' => 'nullable|string|max:80',
            'file_size_bytes' => 'nullable|integer|min:0',
        ]);

        $att = AcctDocumentAttachment::create([
            'company_id' => $companyId,
            'document_type' => $validated['document_type'],
            'document_id' => $validated['document_id'],
            'document_number' => $validated['document_number'] ?? $validated['document_id'],
            'file_name' => $validated['file_name'],
            'file_type' => $validated['file_type'] ?? 'application/pdf',
            'file_size_bytes' => $validated['file_size_bytes'] ?? 0,
            'uploaded_by' => Auth::user()->name ?? 'Accounting User',
        ]);

        $this->postingService->recordAudit(
            companyId: $companyId,
            branchId: null,
            module: 'ATTACHMENTS',
            documentType: $att->document_type,
            documentId: $att->document_id,
            documentNumber: $att->document_number,
            action: 'FILE_ATTACHED',
            summary: "Attached file [{$att->file_name}] to {$att->document_number}."
        );

        return response()->json(['success' => true, 'message' => 'Attachment recorded.', 'data' => $att]);
    }

    // =========================================================================
    // ACCOUNTS PAYABLE (AP) APIs
    // =========================================================================

    public function apiSaveVendorBill(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'header' => 'required|array',
            'lines' => 'required|array|min:1',
            'action' => 'nullable|string|in:DRAFT,POSTED',
        ]);

        try {
            $header = array_merge($validated['header'], [
                'company_id' => $companyId,
                'acted_by' => Auth::user()->name ?? 'Accounts Payable Specialist',
            ]);
            $action = $validated['action'] ?? 'DRAFT';
            $bill = $this->apService->saveDraftOrPostBill($header, $validated['lines'], $action);

            return response()->json([
                'success' => true,
                'message' => "Vendor Bill {$bill->vendor_bill_number} saved as {$bill->vendor_bill_status}.",
                'data' => $bill->load('lines'),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving vendor bill: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveVendorPayment(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'payment' => 'required|array',
            'allocations' => 'nullable|array',
        ]);

        try {
            $allocations = $validated['allocations'] ?? [];
            $totalGrossReq = (float) ($validated['payment']['total_amount'] ?? $validated['payment']['gross_amount'] ?? 0.0);
            if (empty($allocations) && $totalGrossReq <= 0.009) {
                return response()->json(['success' => false, 'message' => 'Select at least one PO/Bill allocation or enter a payment amount greater than zero.'], 422);
            }

            $paymentData = array_merge($validated['payment'], [
                'company_id' => $companyId,
                'acted_by' => Auth::user()->name ?? 'Disbursement Officer',
            ]);
            $payment = $this->apService->recordVendorPayment($paymentData, $allocations);

            $creditMemo = $payment->getAttribute('generated_credit_memo');
            $excessAmt = (float) ($payment->getAttribute('excess_credit_amount') ?? 0.0);
            $msg = "Vendor Payment Voucher {$payment->payment_number} posted successfully.";
            if ($creditMemo && $excessAmt > 0.009) {
                $msg .= ' Excess ₱' . number_format($excessAmt, 2) . " recorded as Vendor Credit Memo {$creditMemo->memo_number}.";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $payment->load('lines'),
                'generated_credit_memo' => $creditMemo,
                'excess_credit_amount' => $excessAmt,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error recording vendor payment: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveVendorMemo(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'memo' => 'required|array',
        ]);

        try {
            $memoData = array_merge($validated['memo'], [
                'company_id' => $companyId,
                'acted_by' => Auth::user()->name ?? 'AP Supervisor',
            ]);
            $memo = $this->apService->createVendorMemo($memoData);

            return response()->json([
                'success' => true,
                'message' => "Vendor {$memo->memo_type} {$memo->memo_number} posted successfully.",
                'data' => $memo,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error creating vendor memo: ' . $e->getMessage()], 500);
        }
    }

    public function apiGetVendorLedger(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $vendorId = $request->query('vendor_id');

        $result = $this->apService->getVendorLedgerAndAging($companyId, $vendorId);
        return response()->json(['success' => true, 'data' => $result]);
    }

    // =========================================================================
    // ACCOUNTS RECEIVABLE (AR) APIs
    // =========================================================================

    public function apiSaveCustomer(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'customer_id' => 'nullable|string|max:60',
            'customer_code' => 'required|string|max:50',
            'customer_name' => 'required|string|max:200',
            'trade_name' => 'nullable|string|max:200',
            'tin' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'payment_terms_code' => 'nullable|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $customer = AcctCustomer::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'customer_id' => $validated['customer_id'] ?? (string) Str::uuid(),
                ],
                [
                    'customer_code' => strtoupper(trim($validated['customer_code'])),
                    'customer_name' => trim($validated['customer_name']),
                    'trade_name' => $validated['trade_name'] ?? trim($validated['customer_name']),
                    'tin' => $validated['tin'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'contact_person' => $validated['contact_person'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'payment_terms_code' => $validated['payment_terms_code'] ?? 'NET30',
                    'credit_limit' => (float) ($validated['credit_limit'] ?? 0.0),
                    'is_active' => $validated['is_active'] ?? true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Customer [{$customer->customer_code}] saved.",
                'data' => $customer,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving customer: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveSalesInvoice(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'header' => 'required|array',
            'lines' => 'required|array|min:1',
            'action' => 'nullable|string|in:DRAFT,POSTED',
        ]);

        try {
            $header = array_merge($validated['header'], [
                'company_id' => $companyId,
                'acted_by' => Auth::user()->name ?? 'AR Specialist',
            ]);
            $action = $validated['action'] ?? 'DRAFT';
            $invoice = $this->arService->saveDraftOrPostInvoice($header, $validated['lines'], $action);

            return response()->json([
                'success' => true,
                'message' => "Sales Invoice {$invoice->invoice_number} saved as {$invoice->status}.",
                'data' => $invoice->load('lines'),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving sales invoice: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveCollectionReceipt(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'receipt' => 'required|array',
            'allocations' => 'required|array|min:1',
        ]);

        try {
            $receiptData = array_merge($validated['receipt'], [
                'company_id' => $companyId,
                'acted_by' => Auth::user()->name ?? 'Collection Cashier',
            ]);
            $receipt = $this->arService->recordCollectionReceipt($receiptData, $validated['allocations']);

            return response()->json([
                'success' => true,
                'message' => "Collection Receipt {$receipt->receipt_number} posted successfully.",
                'data' => $receipt->load('lines'),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error recording collection receipt: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveCustomerMemo(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'memo' => 'required|array',
        ]);

        try {
            $memoData = array_merge($validated['memo'], [
                'company_id' => $companyId,
                'acted_by' => Auth::user()->name ?? 'AR Supervisor',
            ]);
            $memo = $this->arService->createCustomerMemo($memoData);

            return response()->json([
                'success' => true,
                'message' => "Customer {$memo->memo_type} {$memo->memo_number} posted successfully.",
                'data' => $memo,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error creating customer memo: ' . $e->getMessage()], 500);
        }
    }

    public function apiHandleBouncedCheck(Request $request, string $checkId): JsonResponse
    {
        $validated = $request->validate([
            'bounce_reason' => 'required|string|max:200',
            'penalty_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            $penalty = (float) ($validated['penalty_fee'] ?? 500.0);
            $check = $this->arService->handleBouncedCheck($checkId, $validated['bounce_reason'], $penalty);

            return response()->json([
                'success' => true,
                'message' => "Check [{$check->check_number}] marked DAIF bounced. Associated invoice reopened and penalty posted.",
                'data' => $check,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error processing bounced check: ' . $e->getMessage()], 500);
        }
    }

    public function apiGetCustomerLedger(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $customerId = $request->query('customer_id');

        $result = $this->arService->getCustomerLedgerAndAging($companyId, $customerId);
        return response()->json(['success' => true, 'data' => $result]);
    }

    // =========================================================================
    // CASH & BANK APIs
    // =========================================================================

    public function apiSaveBankAccount(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'bank_account_id' => 'nullable|string|max:60',
            'account_name' => 'required|string|max:150',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:60',
            'branch_name' => 'nullable|string|max:100',
            'account_type' => 'required|in:CHECKING,SAVINGS,PETTY_CASH',
            'gl_account_id' => 'required|string|max:60',
            'currency_code' => 'nullable|string|max:10',
            'opening_balance' => 'nullable|numeric',
            'custodian_name' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $data = array_merge($validated, ['company_id' => $companyId]);
            $bank = $this->cashBankService->saveBankAccount($data);

            return response()->json([
                'success' => true,
                'message' => "Bank account [{$bank->account_name}] saved.",
                'data' => $bank->load('glAccount'),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving bank account: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveFundTransfer(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'source_bank_account_id' => 'required|string|max:60',
            'destination_bank_account_id' => 'required|string|max:60|different:source_bank_account_id',
            'amount' => 'required|numeric|min:0.01',
            'fee_amount' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:60',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|string|max:30',
        ]);

        try {
            $data = array_merge($validated, [
                'company_id' => $companyId,
                'created_by' => Auth::user()->name ?? 'Treasury Specialist',
            ]);
            $transfer = $this->cashBankService->transferFunds($data);

            return response()->json([
                'success' => true,
                'message' => "Fund Transfer {$transfer->transfer_number} posted successfully.",
                'data' => $transfer,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error transferring funds: ' . $e->getMessage()], 500);
        }
    }

    public function apiIssueEmployeeAdvance(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'employee_id' => 'required|string|max:60',
            'employee_name' => 'required|string|max:150',
            'advance_date' => 'required|date',
            'settlement_due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0.01',
            'disbursement_account_id' => 'required|string|max:60',
            'purpose' => 'required|string|max:255',
            'branch_id' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        try {
            $data = array_merge($validated, [
                'company_id' => $companyId,
                'created_by' => Auth::user()->name ?? 'Payroll / AP Officer',
            ]);
            $advance = $this->cashBankService->issueEmployeeAdvance($data);

            return response()->json([
                'success' => true,
                'message' => "Employee Advance {$advance->advance_number} disbursed.",
                'data' => $advance,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error issuing employee advance: ' . $e->getMessage()], 500);
        }
    }

    public function apiLiquidateEmployeeAdvance(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'advance_id' => 'required|string|max:60',
            'liquidation_date' => 'required|date',
            'refund_bank_account_id' => 'nullable|string|max:60',
            'reimbursement_bank_account_id' => 'nullable|string|max:60',
            'items' => 'required|array|min:1',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|string|max:30',
        ]);

        try {
            $data = [
                'advance_id' => $validated['advance_id'],
                'company_id' => $companyId,
                'liquidation_date' => $validated['liquidation_date'],
                'refund_bank_account_id' => $validated['refund_bank_account_id'] ?? null,
                'reimbursement_bank_account_id' => $validated['reimbursement_bank_account_id'] ?? null,
                'branch_id' => $validated['branch_id'] ?? 'BR-01',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::user()->name ?? 'AP Specialist',
            ];
            $liq = $this->cashBankService->liquidateEmployeeAdvance($data, $validated['items']);

            return response()->json([
                'success' => true,
                'message' => "Employee Advance Liquidation {$liq->liquidation_number} posted successfully.",
                'data' => $liq,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error liquidating advance: ' . $e->getMessage()], 500);
        }
    }

    public function apiClearPdcCheck(Request $request, string $checkId): JsonResponse
    {
        try {
            $check = $this->cashBankService->clearPdcCheck($checkId);

            return response()->json([
                'success' => true,
                'message' => "Check [{$check->check_number}] cleared into Cash in Bank.",
                'data' => $check,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error clearing check: ' . $e->getMessage()], 500);
        }
    }

    public function apiReconcileBankStatement(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'reconciliation_id' => 'nullable|string|max:60',
            'bank_account_id' => 'required|string|max:60',
            'statement_date' => 'required|date',
            'statement_ending_balance' => 'required|numeric',
            'statement_lines' => 'required|array',
        ]);

        try {
            $reconId = $validated['reconciliation_id'] ?? null;
            if (empty($reconId)) {
                $bank = AcctBankAccount::findOrFail($validated['bank_account_id']);
                $recon = AcctBankReconciliation::create([
                    'company_id' => $companyId,
                    'bank_account_id' => $bank->bank_account_id,
                    'statement_date' => $validated['statement_date'],
                    'statement_ending_balance' => (float) $validated['statement_ending_balance'],
                    'gl_book_balance' => (float) $bank->current_balance,
                    'reconciled_balance' => (float) $bank->current_balance,
                    'difference' => (float) $validated['statement_ending_balance'] - (float) $bank->current_balance,
                    'status' => 'IN_PROGRESS',
                ]);
                $reconId = $recon->reconciliation_id;
            }

            $recon = $this->cashBankService->reconcileBankStatement($reconId, $validated['statement_lines']);

            return response()->json([
                'success' => true,
                'message' => "Bank reconciliation updated (Status: {$recon->status}).",
                'data' => $recon->load('lines'),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error reconciling statement: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // WAVE 3: FIXED ASSETS API ENDPOINTS
    // =========================================================================

    public function apiSaveFixedAsset(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'asset_category' => 'nullable|string|max:50',
            'acquisition_date' => 'nullable|date',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'nullable|integer|min:1',
            'depreciation_method' => 'nullable|string|max:30',
            'branch_id' => 'nullable|string|max:50',
            'department_id' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:150',
            'serial_number' => 'nullable|string|max:100',
            'vendor_bill_id' => 'nullable|string|max:60',
            'notes' => 'nullable|string',
        ]);

        try {
            $data = array_merge($validated, ['company_id' => $companyId]);
            $asset = $this->fixedAssetService->createOrCapitalizeAsset($data, $data['vendor_bill_id'] ?? null);

            return response()->json([
                'success' => true,
                'message' => "Fixed Asset [{$asset->asset_code}] capitalized successfully.",
                'data' => $asset,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving fixed asset: ' . $e->getMessage()], 500);
        }
    }

    public function apiPostFixedAssetDepreciation(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'fiscal_period_id' => 'required|string|max:60',
            'asset_id' => 'nullable|string|max:60',
            'depreciation_date' => 'nullable|date',
        ]);

        try {
            $records = $this->fixedAssetService->postMonthlyDepreciation(
                companyId: $companyId,
                fiscalPeriodId: $validated['fiscal_period_id'],
                assetId: $validated['asset_id'] ?? null,
                depreciationDate: $validated['depreciation_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => sprintf('Monthly depreciation posted for %d asset(s).', count($records)),
                'data' => $records,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error posting depreciation: ' . $e->getMessage()], 500);
        }
    }

    public function apiDisposeFixedAsset(Request $request, string $assetId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'disposal_date' => 'nullable|date',
            'disposal_type' => 'required|string|in:SALE,SCRAP_RETIREMENT,CASUALTY_LOSS',
            'proceeds_amount' => 'nullable|numeric|min:0',
            'buyer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $disposal = $this->fixedAssetService->disposeAsset($assetId, $validated);

            return response()->json([
                'success' => true,
                'message' => "Asset disposal [{$disposal->disposal_number}] posted successfully (Gain/Loss: ₱" . number_format((float) $disposal->gain_loss_amount, 2) . ").",
                'data' => $disposal,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error disposing asset: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // WAVE 3: LOANS & DEBT FACILITIES API ENDPOINTS
    // =========================================================================

    public function apiSaveLoan(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'lender_name' => 'required|string|max:255',
            'lender_contact' => 'nullable|string|max:150',
            'loan_type' => 'nullable|string|max:30',
            'principal_amount' => 'required|numeric|min:0.01',
            'annual_interest_rate_pct' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'term_months' => 'required|integer|min:1',
            'payment_frequency' => 'nullable|string|max:20',
            'interest_method' => 'nullable|string|max:30',
            'disbursement_account_id' => 'nullable|string|max:60',
            'branch_id' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        try {
            $data = array_merge($validated, ['company_id' => $companyId]);
            $loan = $this->loanService->createLoan($data);

            return response()->json([
                'success' => true,
                'message' => "Loan facility [{$loan->loan_number}] registered and disbursed successfully.",
                'data' => $loan,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error creating loan facility: ' . $e->getMessage()], 500);
        }
    }

    public function apiRecordLoanPayment(Request $request, string $loanId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'principal_amount' => 'nullable|numeric|min:0',
            'interest_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:30',
            'disbursement_account_id' => 'nullable|string|max:60',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $payment = $this->loanService->recordLoanPayment($loanId, $validated);

            return response()->json([
                'success' => true,
                'message' => "Loan repayment voucher [{$payment->payment_number}] posted successfully.",
                'data' => $payment,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error recording loan repayment: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // WAVE 3: GL INQUIRY, RECURRING TEMPLATES & PERIOD CLOSE
    // =========================================================================

    public function apiGetGlInquiry(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'account_id' => 'required|string|max:60',
            'branch_id' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $inquiry = $this->glService->getGlInquiry(
                companyId: $companyId,
                accountId: $validated['account_id'],
                branchId: $validated['branch_id'] ?? null,
                startDate: $validated['start_date'] ?? null,
                endDate: $validated['end_date'] ?? null
            );

            return response()->json(['success' => true, 'data' => $inquiry]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching GL inquiry: ' . $e->getMessage()], 500);
        }
    }

    public function apiSaveRecurringTemplate(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'template_id' => 'nullable|string|max:60',
            'template_code' => 'nullable|string|max:30',
            'template_name' => 'required|string|max:150',
            'journal_type' => 'nullable|string|max:30',
            'frequency' => 'required|string|in:MONTHLY,QUARTERLY,ANNUALLY',
            'next_run_date' => 'required|date',
            'auto_post' => 'nullable|boolean',
            'default_branch_id' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'lines_json' => 'required|array|min:2',
        ]);

        try {
            $data = array_merge($validated, ['company_id' => $companyId]);
            $template = $this->glService->saveRecurringTemplate($data);

            return response()->json([
                'success' => true,
                'message' => "Recurring journal template [{$template->template_code}] saved successfully.",
                'data' => $template,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving recurring template: ' . $e->getMessage()], 500);
        }
    }

    public function apiRunRecurringTemplates(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        try {
            $runDate = $request->input('run_date', now()->toDateString());
            $journals = $this->glService->generateRecurringJournals($companyId, $runDate);

            return response()->json([
                'success' => true,
                'message' => sprintf('Executed recurring run: generated %d journal voucher(s).', count($journals)),
                'data' => $journals,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error executing recurring run: ' . $e->getMessage()], 500);
        }
    }

    public function apiUpdatePeriodChecklist(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'period_id' => 'required|string|max:60',
            'bank_reconciliation_done' => 'nullable|boolean',
            'depreciation_posted' => 'nullable|boolean',
            'inventory_reconciled' => 'nullable|boolean',
            'ap_ar_reconciled' => 'nullable|boolean',
            'recurring_posted' => 'nullable|boolean',
            'fx_revaluation_done' => 'nullable|boolean',
            'is_locked' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            $checklist = $this->glService->updatePeriodCloseChecklist($companyId, $validated['period_id'], $validated);

            return response()->json([
                'success' => true,
                'message' => 'Period close checklist and lock status updated.',
                'data' => $checklist,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error updating period checklist: ' . $e->getMessage()], 500);
        }
    }

    public function apiExecuteYearEndClosing(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'fiscal_year_id' => 'required|string|max:60',
        ]);

        try {
            $closingJournal = $this->glService->executeYearEndClosing($companyId, $validated['fiscal_year_id']);

            return response()->json([
                'success' => true,
                'message' => "Fiscal year closed successfully. Closing journal [{$closingJournal->journal_number}] posted to Retained Earnings.",
                'data' => $closingJournal,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error executing year-end closing: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // WAVE 4: TAX & FINANCIAL REPORTS APIs
    // =========================================================================

    public function apiGetTaxReport(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'report_type' => 'required|in:2550Q,SLSP,1601-EQ,QAP,SAWT',
            'year' => 'nullable|integer|min:2000|max:2099',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $quarter = (int) ($validated['quarter'] ?? ceil(now()->month / 3));

        try {
            $data = match ($validated['report_type']) {
                '2550Q' => $this->taxReportService->generate2550Q($companyId, $year, $quarter),
                'SLSP' => $this->taxReportService->generateSLSP($companyId, $year, $quarter),
                '1601-EQ' => $this->taxReportService->generate1601EQ($companyId, $year, $quarter),
                'QAP' => $this->taxReportService->generateQAP($companyId, $year, $quarter),
                'SAWT' => $this->taxReportService->generateSAWT($companyId, $year, $quarter),
            };

            return response()->json([
                'success' => true,
                'report_type' => $validated['report_type'],
                'data' => $data,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error generating tax report: ' . $e->getMessage()], 500);
        }
    }

    public function apiGetFinancialReport(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'report_type' => 'required|in:trial-balance,income-statement,balance-sheet,cash-flow,aging-ap,aging-ar,inv-recon',
            'as_of_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'branch_id' => 'nullable|string|max:60',
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->toDateString();
        $startDate = $validated['start_date'] ?? \Carbon\Carbon::parse($asOfDate)->startOfYear()->toDateString();
        $endDate = $validated['end_date'] ?? $asOfDate;
        $branchId = $validated['branch_id'] ?? null;

        try {
            $data = match ($validated['report_type']) {
                'trial-balance' => $this->reportService->generateTrialBalance($companyId, $asOfDate, $branchId),
                'income-statement' => $this->reportService->generateIncomeStatement($companyId, $startDate, $endDate, $branchId),
                'balance-sheet' => $this->reportService->generateBalanceSheet($companyId, $asOfDate, $branchId),
                'cash-flow' => $this->reportService->generateCashFlowStatement($companyId, $startDate, $endDate, $branchId),
                'aging-ap' => $this->reportService->generateApAging($companyId, $asOfDate, $branchId),
                'aging-ar' => $this->reportService->generateArAging($companyId, $asOfDate, $branchId),
                'inv-recon' => $this->reportService->generateInventoryGlReconciliation($companyId, $asOfDate),
            };

            return response()->json([
                'success' => true,
                'report_type' => $validated['report_type'],
                'data' => $data,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error generating financial report: ' . $e->getMessage()], 500);
        }
    }

    public function apiAllocateLandedCost(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'stock_in_id' => 'required|string|max:60',
            'branch_id' => 'nullable|string|max:60',
            'landed_costs' => 'required|array',
        ]);

        try {
            $result = $this->reportService->allocateLandedCostWithConsumedStock(
                $companyId,
                $validated['stock_in_id'],
                $validated['landed_costs'],
                $validated['branch_id'] ?? 'BR-01'
            );

            return response()->json([
                'success' => true,
                'message' => 'Landed cost split and capitalized successfully.',
                'data' => $result,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error allocating landed cost: ' . $e->getMessage()], 500);
        }
    }
}

