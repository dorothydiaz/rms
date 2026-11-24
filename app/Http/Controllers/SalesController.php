<?php

namespace App\Http\Controllers;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctPaymentTerm;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\AcctTaxCode;
use App\Models\Branch;
use App\Models\Hr\Employee;
use App\Models\Inventory\BillOfMaterials;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockOutOrder;
use App\Models\Sales\SalesBundle;
use App\Models\Sales\SalesBundleItem;
use App\Models\Sales\SalesDiscount;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesQuote;
use App\Models\Sales\SalesVoucher;
use App\Services\Accounting\AccountingArService;
use App\Services\Accounting\AccountingNumberingService;
use App\Services\Accounting\AccountingSeedService;
use App\Services\CompanyBranchContext;
use App\Services\Sales\SalesCreditControlService;
use App\Services\Sales\SalesInventoryService;
use App\Services\Sales\SalesOrderService;
use App\Services\Sales\SalesPromotionService;
use App\Services\Sales\SalesReportingService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class SalesController extends Controller
{
    public function __construct(
        protected SalesOrderService $orderService,
        protected SalesCreditControlService $creditService,
        protected SalesInventoryService $inventoryService,
        protected SalesPromotionService $promotionService,
        protected SalesReportingService $reportingService,
        protected AccountingSeedService $seedService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    protected function resolveActiveCompanyId(): string
    {
        try {
            $company = app(CompanyBranchContext::class)->getActiveCompany();
            if ($company && !empty($company->company_id)) {
                return (string) $company->company_id;
            }
        } catch (Throwable $e) {
        }

        return '1';
    }

    protected function resolveActiveBranchId(): string
    {
        try {
            $branch = app(CompanyBranchContext::class)->getActiveBranch();
            if ($branch && !empty($branch->branch_id)) {
                return (string) $branch->branch_id;
            }
        } catch (Throwable $e) {
        }

        return 'BR-01';
    }

    protected function resolveActorName(): string
    {
        return Auth::user()?->full_name
            ?? Auth::user()?->name
            ?? Auth::user()?->username
            ?? 'Sales Specialist';
    }

    protected function buildEnrichedCustomers(string $companyId)
    {
        $customers = AcctCustomer::where('company_id', $companyId)
            ->orderBy('customer_name')
            ->get();

        $openOrdersByCustomer = Schema::hasTable('sales_orders')
            ? SalesOrder::where('company_id', $companyId)
                ->whereIn('status', ['APPROVED', 'PARTIALLY_DELIVERED', 'DELIVERED'])
                ->get(['customer_id', 'gross_total', 'amount_billed'])
                ->groupBy('customer_id')
            : collect();

        $creditHoldCounts = Schema::hasTable('sales_orders')
            ? SalesOrder::where('company_id', $companyId)
                ->where('status', 'CREDIT_HOLD')
                ->select('customer_id', DB::raw('count(*) as cnt'))
                ->groupBy('customer_id')
                ->pluck('cnt', 'customer_id')
            : collect();

        return $customers->map(function (AcctCustomer $customer) use ($openOrdersByCustomer, $creditHoldCounts) {
            $openOrdersAmt = (float) $openOrdersByCustomer->get($customer->customer_id, collect())->sum(
                fn ($so) => max(0.0, (float) $so->gross_total - (float) $so->amount_billed)
            );
            $arBalance = (float) $customer->current_balance;
            $limit = (float) $customer->credit_limit;
            $exposure = round($arBalance + $openOrdersAmt, 4);
            $available = max(0.0, round($limit - $exposure, 4));
            $utilPct = $limit > 0.0001 ? min(100.0, round(($exposure / $limit) * 100, 1)) : 0.0;

            $customer->setAttribute('open_orders_exposure', round($openOrdersAmt, 2));
            $customer->setAttribute('total_exposure', round($exposure, 2));
            $customer->setAttribute('available_credit', round($available, 2));
            $customer->setAttribute('credit_utilization_pct', $utilPct);
            $customer->setAttribute('credit_hold_orders_count', (int) ($creditHoldCounts->get($customer->customer_id) ?? 0));

            return $customer;
        })->values();
    }

    protected function buildEnrichedItems(): \Illuminate\Support\Collection
    {
        $items = InventoryItem::where('is_active', true)->orderBy('name')->get();
        $atpMap = $this->inventoryService->getAtpMap($items->pluck('id')->all());

        return $items->map(function (InventoryItem $item) use ($atpMap) {
            $atp = $atpMap[$item->id] ?? ['on_hand' => (float) $item->current_stock, 'reserved_qty' => 0.0, 'atp_qty' => (float) $item->current_stock];
            $item->setAttribute('reserved_qty', $atp['reserved_qty']);
            $item->setAttribute('atp_qty', $atp['atp_qty']);

            return $item;
        })->values();
    }

    protected function buildTaxCodes(string $companyId)
    {
        $this->seedService->ensureSeeded($companyId);

        return AcctTaxCode::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('tax_type', ['VAT_OUTPUT', 'EXEMPT', 'ZERO_RATED', 'CWT'])
            ->orderBy('tax_type')
            ->orderBy('tax_code')
            ->get();
    }

    protected function buildPaymentTerms(string $companyId)
    {
        $this->seedService->ensureSeeded($companyId);

        return Schema::hasTable('acct_payment_terms')
            ? AcctPaymentTerm::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('due_days')
                ->orderBy('term_code')
                ->get(['term_id', 'term_code', 'term_name', 'due_days', 'discount_pct'])
            : collect();
    }

    // =========================================================================
    // PAGE VIEWS
    // =========================================================================

    public function dashboard(): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $quotes = Schema::hasTable('sales_quotes')
            ? SalesQuote::where('company_id', $companyId)->whereNotIn('status', ['ARCHIVED'])->orderByDesc('created_at')->get()
            : collect();

        $orders = Schema::hasTable('sales_orders')
            ? SalesOrder::where('company_id', $companyId)->whereNotIn('status', ['ARCHIVED'])->orderByDesc('created_at')->get()
            : collect();

        $draftInvoices = Schema::hasTable('acct_sales_invoices')
            ? AcctSalesInvoice::where('company_id', $companyId)
                ->whereNotNull('sales_order_id')
                ->where('status', 'DRAFT')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $mtdRevenue = Schema::hasTable('acct_sales_invoices')
            ? (float) AcctSalesInvoice::where('company_id', $companyId)
                ->whereIn('status', ['POSTED', 'PARTIAL', 'PAID'])
                ->whereMonth('invoice_date', now()->month)
                ->whereYear('invoice_date', now()->year)
                ->sum('subtotal_amount')
            : 0.0;

        $kpis = [
            'activeQuotes' => $quotes->whereIn('status', ['DRAFT', 'SENT', 'CONFIRMED'])->count(),
            'confirmedQuotes' => $quotes->where('status', 'CONFIRMED')->count(),
            'totalOrders' => $orders->where('status', '!=', 'CANCELLED')->count(),
            'creditHoldCount' => $orders->where('status', 'CREDIT_HOLD')->count(),
            'readyToDeliverCount' => $orders->whereIn('status', ['APPROVED', 'PARTIALLY_DELIVERED'])->count(),
            'unbilledDraftCount' => $draftInvoices->count(),
            'mtdRevenue' => round($mtdRevenue, 2),
            'openReceivables' => Schema::hasTable('acct_customers')
                ? round((float) AcctCustomer::where('company_id', $companyId)->sum('current_balance'), 2)
                : 0.0,
        ];

        return view('sales.dashboard', [
            'kpis' => $kpis,
            'creditHoldOrders' => $orders->where('status', 'CREDIT_HOLD')->take(8)->values(),
            'readyOrders' => $orders->whereIn('status', ['APPROVED', 'PARTIALLY_DELIVERED'])->take(8)->values(),
            'draftInvoices' => $draftInvoices->take(8)->values(),
            'recentQuotes' => $quotes->take(8)->values(),
        ]);
    }

    protected function buildEmployeesList(): array
    {
        if (!Schema::hasTable('hr_employees')) {
            return [];
        }

        return Employee::query()
            ->whereNull('employee_deleted_at')
            ->orderBy('employee_first_name')
            ->get(['employee_id', 'employee_code', 'employee_first_name', 'employee_last_name'])
            ->map(fn ($e) => [
                'employee_id' => (string) $e->employee_id,
                'employee_code' => $e->employee_code,
                'full_name' => trim("{$e->employee_first_name} {$e->employee_last_name}"),
            ])
            ->values()
            ->all();
    }

    protected function renderUnifiedOrdersWorkspace(string $initialStageFilter = 'all'): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $orders = SalesOrder::with(['lines', 'customer', 'deliveries.items', 'invoices.lines', 'invoices.receiptAllocations.receipt'])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->take(80)
            ->get();

        $discounts = Schema::hasTable('sales_discounts')
            ? SalesDiscount::where('company_id', $companyId)->where('is_active', true)->orderBy('discount_name')->get()
            : collect();

        $vouchers = Schema::hasTable('sales_vouchers')
            ? SalesVoucher::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('voucher_code')->get()
            : collect();

        $bundles = $this->promotionService->getEnrichedBundles($companyId)->where('is_active', true)->values();

        $bomItemIds = Schema::hasTable('bom_header')
            ? BillOfMaterials::where('is_active', true)->pluck('bom_code', 'finished_item_id')->toArray()
            : [];

        return view('sales.orders', [
            'initialStageFilter' => $initialStageFilter,
            'initialOrders' => $orders,
            'initialCustomers' => $this->buildEnrichedCustomers($companyId),
            'initialItems' => $this->buildEnrichedItems(),
            'initialWarehouses' => InventoryController::getDefaultWarehousesPayload(),
            'initialTaxCodes' => $this->buildTaxCodes($companyId),
            'initialPaymentTerms' => $this->buildPaymentTerms($companyId),
            'initialDiscounts' => $discounts,
            'initialVouchers' => $vouchers,
            'initialBundles' => $bundles,
            'initialEmployees' => $this->buildEmployeesList(),
            'bomItemIds' => $bomItemIds,
        ]);
    }

    public function quotes(): View
    {
        return $this->renderUnifiedOrdersWorkspace('QUOTE');
    }

    public function orders(Request $request): View
    {
        $stage = strtoupper(trim((string) $request->query('stage', 'all')));
        return $this->renderUnifiedOrdersWorkspace($stage === 'QUOTE' ? 'QUOTE' : 'all');
    }

    public function deliveries(): RedirectResponse
    {
        return redirect()->route('inventory.stock-out', ['order_type' => 'SALES_DELIVERY']);
    }

    public function bills(): RedirectResponse
    {
        return redirect()->route('accounting.ar', ['tab' => 'invoices']);
    }

    public function customerMasterlist(): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        return view('sales.customer-masterlist', [
            'initialCustomers' => $this->buildEnrichedCustomers($companyId),
            'initialTaxCodes' => $this->buildTaxCodes($companyId),
        ]);
    }

    public function dailySales(Request $request): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $branchId = $request->query('branch_id');
        $salesperson = $request->query('salesperson');

        $branches = Schema::hasTable('branches')
            ? Branch::where('branch_is_active', true)->orderBy('branch_code')->get(['branch_id', 'branch_code', 'branch_name'])
            : collect();

        return view('sales.daily-sales', [
            'report' => $this->reportingService->getDailySalesReport($companyId, $from, $to, $branchId, $salesperson),
            'branches' => $branches,
            'employees' => $this->buildEmployeesList(),
        ]);
    }

    public function paymentReport(Request $request): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $method = $request->query('method');

        return view('sales.payment-report', [
            'report' => $this->reportingService->getPaymentReport($companyId, $from, $to, $method),
        ]);
    }

    public function reconciliations(): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        return view('sales.reconciliations', [
            'report' => $this->reportingService->getReconciliationReport($companyId),
        ]);
    }

    public function discountConfig(): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);
        $discounts = Schema::hasTable('sales_discounts')
            ? SalesDiscount::where('company_id', $companyId)->orderByDesc('created_at')->get()
            : collect();
        $telemetry = $this->promotionService->getDiscountModuleTelemetry($companyId);

        return view('sales.discount-config', [
            'initialDiscounts' => $this->promotionService->enrichDiscounts($discounts, $telemetry),
            'initialTelemetry' => $telemetry,
        ]);
    }

    public function voucherConfig(): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);
        $vouchers = Schema::hasTable('sales_vouchers')
            ? SalesVoucher::with('customer')->where('company_id', $companyId)->orderByDesc('created_at')->get()
            : collect();
        $telemetry = $this->promotionService->getVoucherModuleTelemetry($companyId);

        return view('sales.voucher-config', [
            'initialVouchers' => $this->promotionService->enrichVouchers($vouchers, $telemetry),
            'initialCustomers' => $this->buildEnrichedCustomers($companyId),
            'initialTelemetry' => $telemetry,
        ]);
    }

    public function bundlePromotions(): View
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        return view('sales.bundle-promotions', [
            'initialBundles' => $this->promotionService->getEnrichedBundles($companyId),
            'initialItems' => $this->buildEnrichedItems(),
            'initialTelemetry' => $this->promotionService->getBundleModuleTelemetry($companyId),
        ]);
    }

    // =========================================================================
    // CUSTOMER MASTERLIST APIs (Single Source of Truth: acct_customers)
    // =========================================================================

    public function apiGetCustomers(): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        return response()->json([
            'success' => true,
            'data' => $this->buildEnrichedCustomers($companyId),
        ]);
    }

    public function apiSaveCustomer(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $this->seedService->ensureSeeded($companyId);

        $validated = $request->validate([
            'customer_id' => 'nullable|string|max:60',
            'customer_code' => 'nullable|string|max:30',
            'customer_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'tin' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'payment_terms_code' => 'nullable|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_hold' => 'nullable|boolean',
            'credit_grace_days' => 'nullable|integer|min:0|max:365',
            'customer_tier' => 'nullable|string|max:30',
            'default_tax_code' => 'nullable|string|max:30',
            'sales_rep_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $customer = null;
            if (!empty($validated['customer_id'])) {
                $customer = AcctCustomer::where('company_id', $companyId)
                    ->where('customer_id', $validated['customer_id'])
                    ->first();
            }

            $code = !empty($validated['customer_code'])
                ? strtoupper(trim($validated['customer_code']))
                : ($customer?->customer_code ?? ('CUST-' . strtoupper(Str::random(5))));

            if ($customer) {
                $customer->fill([
                    'customer_code' => $code,
                    'customer_name' => trim($validated['customer_name']),
                    'trade_name' => $validated['trade_name'] ?? trim($validated['customer_name']),
                    'tin' => $validated['tin'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'shipping_address' => $validated['shipping_address'] ?? ($validated['address'] ?? null),
                    'contact_person' => $validated['contact_person'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'payment_terms_code' => $validated['payment_terms_code'] ?? 'NET_30',
                    'credit_limit' => (float) ($validated['credit_limit'] ?? 0.0),
                    'credit_hold' => (bool) ($validated['credit_hold'] ?? false),
                    'credit_grace_days' => (int) ($validated['credit_grace_days'] ?? 15),
                    'customer_tier' => $validated['customer_tier'] ?? 'STANDARD',
                    'default_tax_code' => $validated['default_tax_code'] ?? 'VAT12_OUT',
                    'sales_rep_name' => $validated['sales_rep_name'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]);
                $customer->save();
            } else {
                $customer = AcctCustomer::create([
                    'customer_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'customer_code' => $code,
                    'customer_name' => trim($validated['customer_name']),
                    'trade_name' => $validated['trade_name'] ?? trim($validated['customer_name']),
                    'tin' => $validated['tin'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'shipping_address' => $validated['shipping_address'] ?? ($validated['address'] ?? null),
                    'contact_person' => $validated['contact_person'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'payment_terms_code' => $validated['payment_terms_code'] ?? 'NET_30',
                    'credit_limit' => (float) ($validated['credit_limit'] ?? 0.0),
                    'current_balance' => 0.0,
                    'credit_hold' => (bool) ($validated['credit_hold'] ?? false),
                    'credit_grace_days' => (int) ($validated['credit_grace_days'] ?? 15),
                    'customer_tier' => $validated['customer_tier'] ?? 'STANDARD',
                    'default_tax_code' => $validated['default_tax_code'] ?? 'VAT12_OUT',
                    'sales_rep_name' => $validated['sales_rep_name'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Customer [{$customer->customer_code}] saved.",
                'data' => $this->buildEnrichedCustomers($companyId)->firstWhere('customer_id', $customer->customer_id) ?? $customer,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving customer: ' . $e->getMessage()], 500);
        }
    }

    public function apiToggleCustomerHold(string $customerId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $customer = AcctCustomer::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $customer->credit_hold = !$customer->credit_hold;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => $customer->credit_hold
                ? "Customer [{$customer->customer_name}] placed on Credit Hold."
                : "Credit Hold lifted for [{$customer->customer_name}].",
            'data' => $customer,
        ]);
    }

    // =========================================================================
    // SALES QUOTES APIs
    // =========================================================================

    public function apiGetQuotes(): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $quotes = SalesQuote::with(['lines', 'customer'])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $quotes]);
    }

    public function apiSaveQuoteDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => 'nullable|string|max:64',
            'quote_number' => 'nullable|string|max:50',
            'customer_id' => 'required|string|max:64',
            'source_warehouse_id' => 'nullable|string|max:64',
            'source_warehouse_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:150',
            'customer_email' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'quote_date' => 'nullable|date',
            'valid_until_date' => 'nullable|date',
            'requested_delivery_date' => 'nullable|date',
            'payment_terms_code' => 'nullable|string|max:30',
            'currency_code' => 'nullable|string|max:10',
            'discount_type' => 'nullable|string|in:NONE,PERCENT,FIXED',
            'discount_value' => 'nullable|numeric|min:0',
            'customer_reference' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
            'terms_and_conditions' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.inventory_item_id' => 'nullable|integer',
            'lines.*.sku' => 'nullable|string|max:100',
            'lines.*.item_name' => 'required|string|max:255',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.uom' => 'nullable|string|max:30',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_code' => 'nullable|string|max:30',
            'lines.*.line_notes' => 'nullable|string|max:500',
        ]);

        try {
            $validated['company_id'] = $this->resolveActiveCompanyId();
            $validated['branch_id'] = $this->resolveActiveBranchId();
            $quote = $this->orderService->saveQuoteDraft($validated, $this->resolveActorName());

            return response()->json([
                'success' => true,
                'message' => "Sales Quote {$quote->quote_number} saved.",
                'data' => $quote,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving quote: ' . $e->getMessage()], 500);
        }
    }

    public function apiTransitionQuoteStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => 'required|string|max:64',
            'target_status' => 'required|string|in:DRAFT,SENT,CONFIRMED,EXPIRED,CANCELLED,ARCHIVED',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        try {
            $quote = $this->orderService->transitionQuoteStatus(
                $validated['quote_id'],
                $validated['target_status'],
                $validated,
                $this->resolveActorName()
            );

            return response()->json([
                'success' => true,
                'message' => "Quote {$quote->quote_number} updated to {$quote->status}.",
                'data' => $quote,
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiReviseQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => 'required|string|max:64',
        ]);

        try {
            $revision = $this->orderService->createQuoteRevision($validated['quote_id'], $this->resolveActorName());

            return response()->json([
                'success' => true,
                'message' => "Created revision v{$revision->revision_no} ({$revision->quote_number}).",
                'data' => $revision,
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiConvertQuoteToOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => 'required|string|max:64',
            'auto_confirm' => 'nullable|boolean',
            'customer_po_number' => 'nullable|string|max:120',
            'requested_delivery_date' => 'nullable|date',
            'shipping_address' => 'nullable|string|max:500',
            'special_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $order = $this->orderService->convertQuoteToSalesOrder(
                $validated['quote_id'],
                $validated,
                $this->resolveActorName(),
                (bool) ($validated['auto_confirm'] ?? false)
            );

            return response()->json([
                'success' => true,
                'message' => "Sales Order {$order->order_number} created from Quote.",
                'data' => $order,
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiBatchArchiveQuotes(Request $request): JsonResponse
    {
        $validated = $request->validate(['quote_ids' => 'required|array|min:1']);
        $updated = SalesQuote::whereIn('quote_id', $validated['quote_ids'])
            ->whereNotIn('status', ['CONVERTED'])
            ->update(['status' => 'ARCHIVED', 'updated_by' => $this->resolveActorName()]);

        return response()->json(['success' => true, 'message' => "{$updated} quote(s) archived."]);
    }

    public function apiBatchDeleteQuotes(Request $request): JsonResponse
    {
        $validated = $request->validate(['quote_ids' => 'required|array|min:1']);
        $quotes = SalesQuote::whereIn('quote_id', $validated['quote_ids'])
            ->whereIn('status', ['DRAFT', 'CANCELLED', 'EXPIRED'])
            ->get();

        foreach ($quotes as $q) {
            $q->lines()->delete();
            $q->delete();
        }

        return response()->json(['success' => true, 'message' => "{$quotes->count()} draft/cancelled quote(s) deleted."]);
    }

    // =========================================================================
    // SALES ORDERS APIs
    // =========================================================================

    public function apiGetOrders(): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $orders = SalesOrder::with(['lines', 'customer', 'deliveries.items', 'invoices.lines', 'invoices.receiptAllocations.receipt'])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function apiSaveOrderDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales_order_id' => 'nullable|string|max:64',
            'order_number' => 'nullable|string|max:50',
            'quote_number' => 'nullable|string|max:50',
            'document_stage' => 'nullable|string|in:QUOTE,ORDER',
            'customer_id' => 'required|string|max:64',
            'source_warehouse_id' => 'nullable|string|max:64',
            'source_warehouse_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:150',
            'customer_email' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'customer_po_number' => 'nullable|string|max:120',
            'order_date' => 'nullable|date',
            'valid_until_date' => 'nullable|date',
            'requested_delivery_date' => 'nullable|date',
            'payment_terms_code' => 'nullable|string|max:30',
            'currency_code' => 'nullable|string|max:10',
            'discount_id' => 'nullable|string|max:64',
            'voucher_code' => 'nullable|string|max:50',
            'discount_type' => 'nullable|string|in:NONE,PERCENT,FIXED',
            'discount_value' => 'nullable|numeric|min:0',
            'salesperson_employee_id' => 'nullable|string|max:64',
            'salesperson_name' => 'nullable|string|max:120',
            'special_notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.inventory_item_id' => 'nullable|integer',
            'lines.*.sku' => 'nullable|string|max:100',
            'lines.*.item_name' => 'required|string|max:255',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.uom' => 'nullable|string|max:30',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_code' => 'nullable|string|max:30',
            'lines.*.line_notes' => 'nullable|string|max:500',
            'lines.*.bundle_id' => 'nullable|string|max:64',
            'lines.*.bundle_code' => 'nullable|string|max:50',
            'lines.*.bundle_name' => 'nullable|string|max:200',
        ]);

        try {
            $validated['company_id'] = $this->resolveActiveCompanyId();
            $validated['branch_id'] = $this->resolveActiveBranchId();
            $order = $this->orderService->saveSalesOrderDraft($validated, $this->resolveActorName());

            $msg = strtoupper((string) $order->document_stage) === 'QUOTE'
                ? "Sales Quote {$order->order_number} saved."
                : "Sales Order {$order->order_number} saved as DRAFT.";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $order->load(['lines', 'customer', 'deliveries.items', 'invoices.lines']),
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error saving sales order: ' . $e->getMessage()], 500);
        }
    }

    public function apiConfirmOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|string|max:64',
        ]);

        try {
            $order = $this->orderService->confirmSalesOrder($validated['sales_order_id'], $this->resolveActorName());
            $msg = $order->status === 'CREDIT_HOLD'
                ? "Sales Order {$order->order_number} routed to Accounting Credit Review."
                : "Sales Order {$order->order_number} approved & ATP inventory reserved.";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $order->load(['lines', 'customer', 'reservations', 'deliveries.items', 'invoices.lines']),
                'atp_map' => $this->inventoryService->getAtpMap(),
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiResolveCreditReview(Request $request, string $salesOrderId): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|string|in:APPROVE,APPROVE_OVERRIDE,RETURN_TO_DRAFT,REJECT',
            'remarks' => 'nullable|string|max:500',
            'new_credit_limit' => 'nullable|numeric|min:0',
            'clear_customer_hold' => 'nullable|boolean',
        ]);

        try {
            $order = $this->creditService->resolveCreditReview(
                $salesOrderId,
                $validated['decision'],
                $this->resolveActorName(),
                $validated['remarks'] ?? null,
                isset($validated['new_credit_limit']) ? (float) $validated['new_credit_limit'] : null,
                (bool) ($validated['clear_customer_hold'] ?? false)
            );

            return response()->json([
                'success' => true,
                'message' => "Credit review resolved for {$order->order_number} ({$order->status}).",
                'data' => $order->load(['lines', 'customer', 'reservations', 'deliveries.items', 'invoices.lines']),
                'atp_map' => $this->inventoryService->getAtpMap(),
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiCheckCustomerCredit(Request $request, string $customerId): JsonResponse
    {
        $customer = AcctCustomer::where('customer_id', $customerId)->firstOrFail();
        $grossTotal = max(0.0, (float) $request->query('gross_total', 0));
        $terms = (string) $request->query('payment_terms_code', $customer->payment_terms_code ?: 'NET_30');
        $excludeOrderId = $request->query('exclude_sales_order_id');

        return response()->json([
            'success' => true,
            'data' => $this->creditService->evaluateCustomerCredit($customer, $grossTotal, $terms, $excludeOrderId),
        ]);
    }

    public function apiCreateDelivery(Request $request, string $salesOrderId): JsonResponse
    {
        $validated = $request->validate([
            'ship_immediately' => 'nullable|boolean',
            'shipping_address' => 'nullable|string|max:500',
            'picker_name' => 'nullable|string|max:150',
            'carrier_name' => 'nullable|string|max:150',
            'tracking_waybill' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'lines' => 'nullable|array',
            'lines.*.sales_order_line_id' => 'required_with:lines|string|max:64',
            'lines.*.qty' => 'required_with:lines|numeric|min:0',
            'lines.*.batch_lot_no' => 'nullable|string|max:100',
        ]);

        try {
            $result = $this->inventoryService->createDeliveryOrderFromSalesOrder(
                $salesOrderId,
                $validated['lines'] ?? [],
                $validated,
                $this->resolveActorName(),
                (bool) ($validated['ship_immediately'] ?? false)
            );

            $order = SalesOrder::with(['lines', 'customer', 'deliveries.items', 'invoices.lines', 'invoices.receiptAllocations.receipt'])
                ->where('sales_order_id', $salesOrderId)
                ->first();

            $atpMap = $this->inventoryService->getAtpMap();

            return response()->json([
                'success' => true,
                'message' => ($validated['ship_immediately'] ?? false)
                    ? "Delivery {$result['delivery']->order_number} shipped, COGS posted & Draft Bill prepared."
                    : "Delivery Order {$result['delivery']->order_number} queued in Inventory Stock-Out.",
                'data' => [
                    'delivery' => $result['delivery'],
                    'sales_sync' => $result['sales_sync'],
                    'order' => $order,
                    'atp_map' => $atpMap,
                ],
                'atp_map' => $atpMap,
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error creating delivery: ' . $e->getMessage()], 500);
        }
    }

    public function apiCreateOrPostBill(Request $request, string $salesOrderId): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'nullable|string|max:64',
            'target_status' => 'nullable|string|in:DRAFT,POSTED',
            'bill_mode' => 'nullable|string|in:delivered,full_order',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $invoice = $this->orderService->createOrPostBillForOrder(
                $salesOrderId,
                $validated['invoice_id'] ?? null,
                $validated['target_status'] ?? 'POSTED',
                $validated,
                $this->resolveActorName()
            );

            $order = SalesOrder::with(['lines', 'customer', 'deliveries.items', 'invoices.lines', 'invoices.receiptAllocations.receipt'])
                ->where('sales_order_id', $salesOrderId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => "Customer Bill {$invoice->invoice_number} ({$invoice->status}) processed.",
                'data' => [
                    'invoice' => $invoice,
                    'order' => $order,
                ],
            ]);
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error billing sales order: ' . $e->getMessage()], 500);
        }
    }

    public function apiCancelOrder(Request $request, string $salesOrderId): JsonResponse
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $order = $this->orderService->cancelSalesOrder(
                $salesOrderId,
                $validated['reason'] ?? null,
                $this->resolveActorName()
            );

            return response()->json([
                'success' => true,
                'message' => "Sales Order {$order->order_number} cancelled & reservations released.",
                'data' => $order,
                'atp_map' => $this->inventoryService->getAtpMap(),
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiBatchArchiveOrders(Request $request): JsonResponse
    {
        $validated = $request->validate(['sales_order_ids' => 'required|array|min:1']);
        $updated = SalesOrder::whereIn('sales_order_id', $validated['sales_order_ids'])
            ->whereIn('status', ['DRAFT', 'CANCELLED', 'CLOSED'])
            ->update(['status' => 'ARCHIVED', 'updated_by' => $this->resolveActorName()]);

        return response()->json(['success' => true, 'message' => "{$updated} sales order(s) archived."]);
    }

    // =========================================================================
    // UNIFIED QUOTE-STAGE ACTIONS ON SALES ORDERS
    // =========================================================================

    public function apiUnifiedQuoteStage(Request $request, string $salesOrderId): JsonResponse
    {
        $validated = $request->validate([
            'target_status' => 'required|string|in:QUOTE_DRAFT,QUOTE_SENT,QUOTE_CONFIRMED',
        ]);

        try {
            $order = $this->orderService->transitionUnifiedQuoteStage(
                $salesOrderId,
                $validated['target_status'],
                $this->resolveActorName()
            );

            return response()->json([
                'success' => true,
                'message' => "Quote {$order->quote_number} updated to {$order->status}.",
                'data' => $order->load(['lines', 'customer', 'deliveries.items', 'invoices.lines']),
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiUnifiedQuoteRevise(string $salesOrderId): JsonResponse
    {
        try {
            $clone = $this->orderService->cloneUnifiedQuoteRevision($salesOrderId, $this->resolveActorName());

            return response()->json([
                'success' => true,
                'message' => "Cloned Quote revision v{$clone->revision_no} ({$clone->quote_number}).",
                'data' => $clone->load(['lines', 'customer', 'deliveries.items', 'invoices.lines']),
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // PROMOTIONS APIs (DISCOUNTS, VOUCHERS, BUNDLES)
    // =========================================================================

    public function apiSaveDiscount(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $actor = $this->resolveActorName();
        $validated = $request->validate([
            'discount_id' => 'nullable|string|max:64',
            'discount_code' => 'required|string|max:40',
            'discount_name' => 'required|string|max:180',
            'discount_category' => 'nullable|string|max:30',
            'discount_type' => 'required|string|in:PERCENT,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_cap' => 'nullable|numeric|min:0',
            'applicable_customer_tier' => 'nullable|string|max:30',
            'vat_exempt_override' => 'nullable|boolean',
            'requires_manager_approval' => 'nullable|boolean',
            'duration_mode' => 'nullable|string|in:ALWAYS,SCHEDULED',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'notes' => 'nullable|string|max:1000',
        ]);

        $durationMode = strtoupper((string) ($validated['duration_mode'] ?? ((!empty($validated['valid_from']) || !empty($validated['valid_until'])) ? 'SCHEDULED' : 'ALWAYS')));
        $validFrom = $durationMode === 'SCHEDULED' ? ($validated['valid_from'] ?? null) : null;
        $validUntil = $durationMode === 'SCHEDULED' ? ($validated['valid_until'] ?? null) : null;

        $existing = !empty($validated['discount_id'])
            ? SalesDiscount::where('company_id', $companyId)->where('discount_id', $validated['discount_id'])->first()
            : null;
        $isUpdate = $existing !== null;

        $attrs = [
            'company_id' => $companyId,
            'discount_code' => strtoupper(trim($validated['discount_code'])),
            'discount_name' => trim($validated['discount_name']),
            'discount_category' => strtoupper((string) ($validated['discount_category'] ?? 'COMMERCIAL')),
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_order_amount' => (float) ($validated['min_order_amount'] ?? 0),
            'max_discount_cap' => isset($validated['max_discount_cap']) ? (float) $validated['max_discount_cap'] : null,
            'applicable_customer_tier' => strtoupper((string) ($validated['applicable_customer_tier'] ?? 'ALL')),
            'vat_exempt_override' => (bool) ($validated['vat_exempt_override'] ?? false),
            'requires_manager_approval' => (bool) ($validated['requires_manager_approval'] ?? false),
            'duration_mode' => $durationMode,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $isUpdate ? $existing->is_active : true,
            'updated_by' => $actor,
        ];

        if ($isUpdate) {
            $existing->fill($attrs);
            $existing->save();
            $discount = $existing;
        } else {
            $attrs['discount_id'] = (string) Str::uuid();
            $attrs['created_by'] = $actor;
            $discount = SalesDiscount::create($attrs);
        }

        $windowDesc = $durationMode === 'SCHEDULED'
            ? ('Window: ' . ($validFrom ?: 'Any') . ' → ' . ($validUntil ?: 'Open'))
            : 'Always Active';
        $valDesc = $discount->discount_type === 'PERCENT'
            ? ($discount->discount_value . '%')
            : ('₱' . number_format((float) $discount->discount_value, 2));

        $this->promotionService->logAudit(
            $companyId,
            'DISCOUNT',
            $discount->discount_id,
            $discount->discount_code,
            $discount->discount_name,
            $isUpdate ? 'UPDATED' : 'CREATED',
            ($isUpdate ? 'Updated' : 'Created') . " discount rule ({$valDesc}, {$windowDesc}).",
            $actor,
            ['duration_mode' => $durationMode, 'valid_from' => $validFrom, 'valid_until' => $validUntil, 'discount_value' => (float) $discount->discount_value]
        );

        $telemetry = $this->promotionService->getDiscountModuleTelemetry($companyId);
        $enriched = $this->promotionService->enrichDiscounts(collect([$discount]), $telemetry)->first();

        return response()->json([
            'success' => true,
            'message' => "Discount [{$discount->discount_code}] saved.",
            'data' => $enriched,
            'telemetry' => $telemetry,
        ]);
    }

    public function apiToggleDiscount(string $discountId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $actor = $this->resolveActorName();
        $d = SalesDiscount::where('company_id', $companyId)->where('discount_id', $discountId)->firstOrFail();
        $d->is_active = !$d->is_active;
        $d->updated_by = $actor;
        $d->save();

        $action = $d->is_active ? 'ACTIVATED' : 'DISABLED';
        $this->promotionService->logAudit(
            $companyId,
            'DISCOUNT',
            $d->discount_id,
            $d->discount_code,
            $d->discount_name,
            $action,
            "Discount rule marked as " . ($d->is_active ? 'ACTIVE' : 'DISABLED') . '.',
            $actor
        );

        $telemetry = $this->promotionService->getDiscountModuleTelemetry($companyId);
        $enriched = $this->promotionService->enrichDiscounts(collect([$d]), $telemetry)->first();

        return response()->json([
            'success' => true,
            'message' => "Discount [{$d->discount_code}] " . ($d->is_active ? 'activated' : 'disabled') . '.',
            'data' => $enriched,
            'telemetry' => $telemetry,
        ]);
    }

    public function apiSaveVoucher(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $actor = $this->resolveActorName();
        $validated = $request->validate([
            'voucher_id' => 'nullable|string|max:64',
            'voucher_code' => 'required|string|max:50',
            'voucher_name' => 'required|string|max:180',
            'discount_type' => 'required|string|in:PERCENT,FIXED',
            'discount_value' => 'required|numeric|min:0.01',
            'min_spend_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'max_total_uses' => 'nullable|integer|min:1',
            'max_uses_per_customer' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|string|max:64',
            'duration_mode' => 'nullable|string|in:ALWAYS,SCHEDULED',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'notes' => 'nullable|string|max:1000',
        ]);

        $durationMode = strtoupper((string) ($validated['duration_mode'] ?? ((!empty($validated['valid_from']) || !empty($validated['valid_until'])) ? 'SCHEDULED' : 'ALWAYS')));
        $validFrom = $durationMode === 'SCHEDULED' ? ($validated['valid_from'] ?? null) : null;
        $validUntil = $durationMode === 'SCHEDULED' ? ($validated['valid_until'] ?? null) : null;

        $existing = !empty($validated['voucher_id'])
            ? SalesVoucher::where('company_id', $companyId)->where('voucher_id', $validated['voucher_id'])->first()
            : null;
        $isUpdate = $existing !== null;

        $attrs = [
            'company_id' => $companyId,
            'voucher_code' => strtoupper(trim($validated['voucher_code'])),
            'voucher_name' => trim($validated['voucher_name']),
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_spend_amount' => (float) ($validated['min_spend_amount'] ?? 0),
            'max_discount_amount' => isset($validated['max_discount_amount']) ? (float) $validated['max_discount_amount'] : null,
            'max_total_uses' => $validated['max_total_uses'] ?? null,
            'max_uses_per_customer' => $validated['max_uses_per_customer'] ?? 1,
            'customer_id' => !empty($validated['customer_id']) ? $validated['customer_id'] : null,
            'duration_mode' => $durationMode,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'status' => $isUpdate ? $existing->status : 'ACTIVE',
            'notes' => $validated['notes'] ?? null,
            'updated_by' => $actor,
        ];

        if ($isUpdate) {
            $existing->fill($attrs);
            $existing->save();
            $voucher = $existing;
        } else {
            $attrs['voucher_id'] = (string) Str::uuid();
            $attrs['created_by'] = $actor;
            $voucher = SalesVoucher::create($attrs);
        }

        $windowDesc = $durationMode === 'SCHEDULED'
            ? ('Window: ' . ($validFrom ?: 'Any') . ' → ' . ($validUntil ?: 'Open'))
            : 'Always Active';

        $this->promotionService->logAudit(
            $companyId,
            'VOUCHER',
            $voucher->voucher_id,
            $voucher->voucher_code,
            $voucher->voucher_name,
            $isUpdate ? 'UPDATED' : 'CREATED',
            ($isUpdate ? 'Updated' : 'Created') . " voucher campaign ({$windowDesc}, Quota: " . ($voucher->max_total_uses ?? 'Unlimited') . ').',
            $actor,
            ['duration_mode' => $durationMode, 'valid_from' => $validFrom, 'valid_until' => $validUntil, 'max_total_uses' => $voucher->max_total_uses]
        );

        $telemetry = $this->promotionService->getVoucherModuleTelemetry($companyId);
        $enriched = $this->promotionService->enrichVouchers(collect([$voucher->load('customer')]), $telemetry)->first();

        return response()->json([
            'success' => true,
            'message' => "Voucher [{$voucher->voucher_code}] saved.",
            'data' => $enriched,
            'telemetry' => $telemetry,
        ]);
    }

    public function apiToggleVoucher(string $voucherId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $actor = $this->resolveActorName();
        $v = SalesVoucher::where('company_id', $companyId)->where('voucher_id', $voucherId)->firstOrFail();
        $v->status = $v->status === 'ACTIVE' ? 'DISABLED' : 'ACTIVE';
        $v->updated_by = $actor;
        $v->save();

        $action = $v->status === 'ACTIVE' ? 'ACTIVATED' : 'DISABLED';
        $this->promotionService->logAudit(
            $companyId,
            'VOUCHER',
            $v->voucher_id,
            $v->voucher_code,
            $v->voucher_name,
            $action,
            "Voucher status changed to {$v->status}.",
            $actor
        );

        $telemetry = $this->promotionService->getVoucherModuleTelemetry($companyId);
        $enriched = $this->promotionService->enrichVouchers(collect([$v->load('customer')]), $telemetry)->first();

        return response()->json([
            'success' => true,
            'message' => "Voucher [{$v->voucher_code}] set to {$v->status}.",
            'data' => $enriched,
            'telemetry' => $telemetry,
        ]);
    }

    public function apiValidateVoucher(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $validated = $request->validate([
            'voucher_code' => 'required|string|max:50',
            'customer_id' => 'nullable|string|max:64',
            'order_subtotal' => 'nullable|numeric|min:0',
            'exclude_sales_order_id' => 'nullable|string|max:64',
        ]);

        try {
            $customer = !empty($validated['customer_id']) ? AcctCustomer::where('customer_id', $validated['customer_id'])->first() : null;
            $eval = $this->promotionService->validateVoucher(
                $companyId,
                $validated['voucher_code'],
                $customer,
                (float) ($validated['order_subtotal'] ?? 0),
                $validated['exclude_sales_order_id'] ?? null
            );

            return response()->json(['success' => true, 'message' => "Voucher [{$eval['voucher_code']}] valid (-₱" . number_format($eval['discount_amount'], 2) . ").", 'data' => $eval]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function apiSaveBundle(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $actor = $this->resolveActorName();
        $validated = $request->validate([
            'bundle_id' => 'nullable|string|max:64',
            'bundle_code' => 'required|string|max:50',
            'bundle_name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'bundle_price' => 'required|numeric|min:0.01',
            'tax_code' => 'nullable|string|max:30',
            'duration_mode' => 'nullable|string|in:ALWAYS,SCHEDULED',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.uom' => 'nullable|string|max:30',
        ]);

        $durationMode = strtoupper((string) ($validated['duration_mode'] ?? ((!empty($validated['valid_from']) || !empty($validated['valid_until'])) ? 'SCHEDULED' : 'ALWAYS')));
        $validFrom = $durationMode === 'SCHEDULED' ? ($validated['valid_from'] ?? null) : null;
        $validUntil = $durationMode === 'SCHEDULED' ? ($validated['valid_until'] ?? null) : null;

        $existing = !empty($validated['bundle_id'])
            ? SalesBundle::where('company_id', $companyId)->where('bundle_id', $validated['bundle_id'])->first()
            : null;
        $isUpdate = $existing !== null;

        $bundle = DB::transaction(function () use ($companyId, $validated, $durationMode, $validFrom, $validUntil, $existing, $isUpdate, $actor) {
            $attrs = [
                'company_id' => $companyId,
                'bundle_code' => strtoupper(trim($validated['bundle_code'])),
                'bundle_name' => trim($validated['bundle_name']),
                'description' => $validated['description'] ?? null,
                'bundle_price' => (float) $validated['bundle_price'],
                'tax_code' => $validated['tax_code'] ?? 'VAT12_OUT',
                'duration_mode' => $durationMode,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'is_active' => $isUpdate ? $existing->is_active : true,
                'updated_by' => $actor,
            ];

            if ($isUpdate) {
                $existing->fill($attrs);
                $existing->save();
                $b = $existing;
            } else {
                $attrs['bundle_id'] = (string) Str::uuid();
                $attrs['created_by'] = $actor;
                $b = SalesBundle::create($attrs);
            }

            SalesBundleItem::where('bundle_id', $b->bundle_id)->delete();
            $invById = InventoryItem::whereIn('id', collect($validated['items'])->pluck('inventory_item_id')->all())->get()->keyBy('id');

            foreach ($validated['items'] as $row) {
                $inv = $invById->get((int) $row['inventory_item_id']);
                if (!$inv) continue;
                SalesBundleItem::create([
                    'bundle_id' => $b->bundle_id,
                    'inventory_item_id' => $inv->id,
                    'sku' => $inv->sku,
                    'item_name' => $inv->name,
                    'quantity' => (float) $row['quantity'],
                    'uom' => $row['uom'] ?? ($inv->uom ?: 'PCS'),
                ]);
            }

            return $b;
        });

        $windowDesc = $durationMode === 'SCHEDULED'
            ? ('Window: ' . ($validFrom ?: 'Any') . ' → ' . ($validUntil ?: 'Open'))
            : 'Always Active';

        $this->promotionService->logAudit(
            $companyId,
            'BUNDLE',
            $bundle->bundle_id,
            $bundle->bundle_code,
            $bundle->bundle_name,
            $isUpdate ? 'UPDATED' : 'CREATED',
            ($isUpdate ? 'Updated' : 'Created') . ' bundle @ ₱' . number_format((float) $bundle->bundle_price, 2) . " ({$windowDesc}).",
            $actor,
            ['duration_mode' => $durationMode, 'valid_from' => $validFrom, 'valid_until' => $validUntil, 'bundle_price' => (float) $bundle->bundle_price, 'items_count' => count($validated['items'])]
        );

        $enriched = $this->promotionService->getEnrichedBundles($companyId)->firstWhere('bundle_id', $bundle->bundle_id);
        $telemetry = $this->promotionService->getBundleModuleTelemetry($companyId);

        return response()->json([
            'success' => true,
            'message' => "Bundle [{$bundle->bundle_code}] saved.",
            'data' => $enriched,
            'telemetry' => $telemetry,
        ]);
    }

    public function apiToggleBundle(string $bundleId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $actor = $this->resolveActorName();
        $b = SalesBundle::where('company_id', $companyId)->where('bundle_id', $bundleId)->firstOrFail();
        $b->is_active = !$b->is_active;
        $b->updated_by = $actor;
        $b->save();

        $action = $b->is_active ? 'ACTIVATED' : 'DISABLED';
        $this->promotionService->logAudit(
            $companyId,
            'BUNDLE',
            $b->bundle_id,
            $b->bundle_code,
            $b->bundle_name,
            $action,
            'Bundle marked as ' . ($b->is_active ? 'ACTIVE' : 'DISABLED') . '.',
            $actor
        );

        $enriched = $this->promotionService->getEnrichedBundles($companyId)->firstWhere('bundle_id', $b->bundle_id);
        $telemetry = $this->promotionService->getBundleModuleTelemetry($companyId);

        return response()->json([
            'success' => true,
            'message' => "Bundle [{$b->bundle_code}] " . ($b->is_active ? 'activated' : 'disabled') . '.',
            'data' => $enriched,
            'telemetry' => $telemetry,
        ]);
    }

    public function apiGetPromotionTelemetry(string $module): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $mod = strtolower(trim($module));

        $data = match ($mod) {
            'discounts', 'discount' => $this->promotionService->getDiscountModuleTelemetry($companyId),
            'vouchers', 'voucher' => $this->promotionService->getVoucherModuleTelemetry($companyId),
            'bundles', 'bundle' => $this->promotionService->getBundleModuleTelemetry($companyId),
            default => null,
        };

        if ($data === null) {
            return response()->json(['success' => false, 'message' => "Unknown promotion module [{$module}]."], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiExplodeBundle(Request $request, string $bundleId): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $qty = max(0.01, (float) $request->input('quantity', 1));
        try {
            $result = $this->promotionService->explodeBundleToOrderLines($companyId, $bundleId, $qty);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // =========================================================================
    // REPORTS & 3-WAY O2C RECONCILIATION APIs
    // =========================================================================

    public function apiGetDailySalesReport(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        return response()->json([
            'success' => true,
            'data' => $this->reportingService->getDailySalesReport($companyId, $from, $to, $request->query('branch_id'), $request->query('salesperson')),
        ]);
    }

    public function apiGetPaymentReport(Request $request): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        return response()->json([
            'success' => true,
            'data' => $this->reportingService->getPaymentReport($companyId, $from, $to, $request->query('method')),
        ]);
    }

    public function apiGetReconciliations(): JsonResponse
    {
        $companyId = $this->resolveActiveCompanyId();
        return response()->json([
            'success' => true,
            'data' => $this->reportingService->getReconciliationReport($companyId),
        ]);
    }

    public function apiResyncOrderState(string $salesOrderId): JsonResponse
    {
        app(AccountingArService::class)->syncSalesOrderPaymentState($salesOrderId);
        return response()->json(['success' => true, 'message' => 'Sales Order AR billing & payment state resynchronized.']);
    }
}
