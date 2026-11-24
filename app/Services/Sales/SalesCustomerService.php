<?php

namespace App\Services\Sales;

use App\Models\Accounting\AcctCollectionReceipt;
use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctCustomerActivity;
use App\Models\Accounting\AcctCustomerAddress;
use App\Models\Accounting\AcctCustomerContact;
use App\Models\Accounting\AcctCustomerDocument;
use App\Models\Accounting\AcctCustomerMemo;
use App\Models\Accounting\AcctCustomerPriceList;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockOutOrder;
use App\Models\Sales\SalesOrder;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalesCustomerService
{
    public function __construct(
        protected SalesCreditControlService $creditService
    ) {
    }

    /**
     * Detect potential duplicate customer profiles by normalized TIN, email, or business name.
     */
    public function detectDuplicates(string $companyId, array $input, ?string $excludeCustomerId = null): array
    {
        $rawName = strtolower(trim((string) ($input['customer_name'] ?? '')));
        $rawEmail = strtolower(trim((string) ($input['email'] ?? '')));
        $rawTinDigits = preg_replace('/\D+/', '', (string) ($input['tin'] ?? ''));

        if ($rawName === '' && $rawEmail === '' && strlen($rawTinDigits) < 6) {
            return [];
        }

        $candidates = AcctCustomer::where('company_id', $companyId)
            ->when($excludeCustomerId, fn ($q) => $q->where('customer_id', '!=', $excludeCustomerId))
            ->get(['customer_id', 'customer_code', 'customer_name', 'trade_name', 'tin', 'email', 'account_stage']);

        $matches = [];
        foreach ($candidates as $c) {
            $reasons = [];
            $candName = strtolower(trim((string) $c->customer_name));
            $candEmail = strtolower(trim((string) $c->email));
            $candTinDigits = preg_replace('/\D+/', '', (string) $c->tin);

            if ($rawTinDigits !== '' && strlen($rawTinDigits) >= 6 && $candTinDigits !== '' && $rawTinDigits === $candTinDigits) {
                $reasons[] = 'TIN match';
            }
            if ($rawEmail !== '' && $candEmail !== '' && $rawEmail === $candEmail) {
                $reasons[] = 'Email match';
            }
            if ($rawName !== '' && ($candName === $rawName || strtolower(trim((string) $c->trade_name)) === $rawName)) {
                $reasons[] = 'Business name match';
            }

            if (!empty($reasons)) {
                $matches[] = [
                    'customer_id' => $c->customer_id,
                    'customer_code' => $c->customer_code,
                    'customer_name' => $c->customer_name,
                    'tin' => $c->tin,
                    'email' => $c->email,
                    'account_stage' => $c->account_stage ?: 'ACTIVE',
                    'match_reasons' => $reasons,
                ];
            }
        }

        return $matches;
    }

    /**
     * Create or update a Customer MDM record with duplicate guard, hierarchy validation & timeline logging.
     */
    public function saveCustomer(string $companyId, array $data, string $actor): AcctCustomer
    {
        return DB::transaction(function () use ($companyId, $data, $actor) {
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = AcctCustomer::where('company_id', $companyId)
                    ->where('customer_id', $data['customer_id'])
                    ->lockForUpdate()
                    ->first();
            }

            $allowDuplicate = (bool) ($data['allow_duplicate'] ?? false);
            if (!$allowDuplicate) {
                $dups = $this->detectDuplicates($companyId, $data, $customer?->customer_id);
                if (!empty($dups)) {
                    $first = $dups[0];
                    $reasonStr = implode(', ', $first['match_reasons']);
                    throw new DomainException(
                        "Possible duplicate customer [{$first['customer_code']} — {$first['customer_name']}] ({$reasonStr}). Review existing record or confirm to override."
                    );
                }
            }

            $parentId = !empty($data['parent_customer_id']) ? (string) $data['parent_customer_id'] : null;
            if ($parentId && $customer && $parentId === $customer->customer_id) {
                throw new DomainException('A customer cannot be assigned as its own parent headquarters.');
            }
            if ($parentId) {
                $parentExists = AcctCustomer::where('company_id', $companyId)
                    ->where('customer_id', $parentId)
                    ->exists();
                if (!$parentExists) {
                    throw new DomainException('Selected parent company does not exist in this company.');
                }
            }

            $code = !empty($data['customer_code'])
                ? strtoupper(trim((string) $data['customer_code']))
                : ($customer?->customer_code ?? ('CUST-' . strtoupper(Str::random(5))));

            $stage = strtoupper(trim((string) ($data['account_stage'] ?? ($customer?->account_stage ?? 'ACTIVE'))));
            if (!in_array($stage, ['PROSPECT', 'ACTIVE', 'ARCHIVED'], true)) {
                $stage = 'ACTIVE';
            }

            $attrs = [
                'company_id' => $companyId,
                'customer_code' => $code,
                'customer_name' => trim((string) $data['customer_name']),
                'trade_name' => $data['trade_name'] ?? trim((string) $data['customer_name']),
                'tin' => $data['tin'] ?? null,
                'address' => $data['address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? ($data['address'] ?? null),
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'payment_terms_code' => $data['payment_terms_code'] ?? 'NET_30',
                'credit_limit' => (float) ($data['credit_limit'] ?? ($stage === 'PROSPECT' ? 0.0 : 50000.0)),
                'credit_hold' => (bool) ($data['credit_hold'] ?? false),
                'credit_grace_days' => (int) ($data['credit_grace_days'] ?? 15),
                'customer_tier' => strtoupper((string) ($data['customer_tier'] ?? 'STANDARD')),
                'default_tax_code' => $data['default_tax_code'] ?? 'VAT12_OUT',
                'sales_rep_name' => $data['sales_rep_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $stage !== 'ARCHIVED' && (bool) ($data['is_active'] ?? true),
            ];

            if (Schema::hasColumn('acct_customers', 'parent_customer_id')) {
                $attrs['parent_customer_id'] = $parentId;
                $attrs['account_stage'] = $stage;
                $attrs['website'] = $data['website'] ?? null;
                $attrs['industry'] = $data['industry'] ?? null;
                $attrs['tax_exempt_cert_no'] = $data['tax_exempt_cert_no'] ?? null;
            }

            $isNew = $customer === null;
            if ($customer) {
                $customer->fill($attrs);
                $customer->save();
            } else {
                $attrs['customer_id'] = (string) Str::uuid();
                $attrs['current_balance'] = 0.0;
                $customer = AcctCustomer::create($attrs);
            }

            $this->recordSystemActivity(
                $companyId,
                $customer->customer_id,
                $isNew ? 'Customer Profile Created' : 'Customer Profile Updated',
                sprintf(
                    '%s %s [%s] (%s, Limit: ₱%s).',
                    $actor,
                    $isNew ? 'registered' : 'updated',
                    $customer->customer_code,
                    $stage,
                    number_format((float) $customer->credit_limit, 2)
                ),
                $actor
            );

            return $customer;
        });
    }

    /**
     * One-click Lead/Prospect conversion to Active Customer without re-entering data.
     */
    public function convertProspectToActive(string $companyId, string $customerId, string $actor): AcctCustomer
    {
        return DB::transaction(function () use ($companyId, $customerId, $actor) {
            $customer = AcctCustomer::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->firstOrFail();

            $customer->account_stage = 'ACTIVE';
            $customer->is_active = true;
            $customer->converted_at = now();
            $customer->archived_at = null;
            $customer->save();

            $this->recordSystemActivity(
                $companyId,
                $customer->customer_id,
                'Prospect Converted to Active Customer',
                "Converted by {$actor}; unlocked for full O2C Quotes, Orders & AR billing.",
                $actor
            );

            return $customer;
        });
    }

    /**
     * Archive or restore a Customer profile (blocks archiving while open AR balance > 0).
     */
    public function toggleArchive(string $companyId, string $customerId, string $actor): AcctCustomer
    {
        return DB::transaction(function () use ($companyId, $customerId, $actor) {
            $customer = AcctCustomer::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->firstOrFail();

            $currentlyArchived = strtoupper((string) $customer->account_stage) === 'ARCHIVED';
            if (!$currentlyArchived && (float) $customer->current_balance > 0.01) {
                throw new DomainException(sprintf(
                    'Cannot archive [%s] with open AR balance ₱%s. Settle or write off receivables first.',
                    $customer->customer_code,
                    number_format((float) $customer->current_balance, 2)
                ));
            }

            if ($currentlyArchived) {
                $customer->account_stage = 'ACTIVE';
                $customer->is_active = true;
                $customer->archived_at = null;
            } else {
                $customer->account_stage = 'ARCHIVED';
                $customer->is_active = false;
                $customer->archived_at = now();
            }
            $customer->save();

            $this->recordSystemActivity(
                $companyId,
                $customer->customer_id,
                $currentlyArchived ? 'Customer Restored from Archive' : 'Customer Archived',
                ($currentlyArchived ? 'Restored to Active' : 'Archived') . " by {$actor}.",
                $actor
            );

            return $customer;
        });
    }

    /**
     * Build enriched Customer Masterlist directory cards (Credit, Hierarchy, CLV, Doc Expiry counts).
     */
    public function getEnrichedDirectory(string $companyId): Collection
    {
        $customers = AcctCustomer::where('company_id', $companyId)
            ->orderBy('customer_name')
            ->get();

        $customerNamesById = $customers->pluck('customer_name', 'customer_id');
        $subsidiaryCounts = $customers->whereNotNull('parent_customer_id')->groupBy('parent_customer_id')->map->count();

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

        $ordersCounts = Schema::hasTable('sales_orders')
            ? SalesOrder::where('company_id', $companyId)
                ->whereNotIn('status', ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_EXPIRED', 'CANCELLED', 'ARCHIVED'])
                ->select('customer_id', DB::raw('count(*) as cnt'))
                ->groupBy('customer_id')
                ->pluck('cnt', 'customer_id')
            : collect();

        $clvByCustomer = Schema::hasTable('acct_sales_invoices')
            ? AcctSalesInvoice::where('company_id', $companyId)
                ->whereIn('status', ['POSTED', 'PARTIAL', 'PAID'])
                ->select('customer_id', DB::raw('sum(subtotal_amount) as clv'))
                ->groupBy('customer_id')
                ->pluck('clv', 'customer_id')
            : collect();

        $docsByCustomer = Schema::hasTable('acct_customer_documents')
            ? AcctCustomerDocument::where('company_id', $companyId)->get()->groupBy('customer_id')
            : collect();

        $today = now()->startOfDay();
        $soonThreshold = $today->copy()->addDays(30);

        return $customers->map(function (AcctCustomer $customer) use (
            $customerNamesById,
            $subsidiaryCounts,
            $openOrdersByCustomer,
            $creditHoldCounts,
            $ordersCounts,
            $clvByCustomer,
            $docsByCustomer,
            $today,
            $soonThreshold
        ) {
            $openOrdersAmt = (float) $openOrdersByCustomer->get($customer->customer_id, collect())->sum(
                fn ($so) => max(0.0, (float) $so->gross_total - (float) $so->amount_billed)
            );
            $arBalance = (float) $customer->current_balance;
            $limit = (float) $customer->credit_limit;
            $exposure = round($arBalance + $openOrdersAmt, 4);
            $available = max(0.0, round($limit - $exposure, 4));
            $utilPct = $limit > 0.0001 ? min(100.0, round(($exposure / $limit) * 100, 1)) : 0.0;

            $docs = $docsByCustomer->get($customer->customer_id, collect());
            $expiredDocs = $docs->filter(fn ($d) => $d->expiry_date && Carbon::parse($d->expiry_date)->lt($today))->count();
            $expiringDocs = $docs->filter(fn ($d) => $d->expiry_date && Carbon::parse($d->expiry_date)->gte($today) && Carbon::parse($d->expiry_date)->lte($soonThreshold))->count();

            $customer->setAttribute('open_orders_exposure', round($openOrdersAmt, 2));
            $customer->setAttribute('total_exposure', round($exposure, 2));
            $customer->setAttribute('available_credit', round($available, 2));
            $customer->setAttribute('credit_utilization_pct', $utilPct);
            $customer->setAttribute('credit_hold_orders_count', (int) ($creditHoldCounts->get($customer->customer_id) ?? 0));
            $customer->setAttribute('orders_count', (int) ($ordersCounts->get($customer->customer_id) ?? 0));
            $customer->setAttribute('clv_amount', round((float) ($clvByCustomer->get($customer->customer_id) ?? 0), 2));
            $customer->setAttribute('subsidiaries_count', (int) ($subsidiaryCounts->get($customer->customer_id) ?? 0));
            $customer->setAttribute('parent_customer_name', $customer->parent_customer_id ? $customerNamesById->get($customer->parent_customer_id) : null);
            $customer->setAttribute('expiring_docs_count', $expiringDocs);
            $customer->setAttribute('expired_docs_count', $expiredDocs);
            if (empty($customer->account_stage)) {
                $customer->setAttribute('account_stage', $customer->is_active ? 'ACTIVE' : 'ARCHIVED');
            }

            return $customer;
        })->values();
    }

    /**
     * Full Customer 360° Profile payload: Hierarchy, Contacts, Addresses, Price Lists,
     * O2C Pipeline, Live AR Aging + SOA, Compliance Docs with expiry alerts, CRM Timeline & CLV/Margin/DSO.
     */
    public function getCustomer360Profile(string $companyId, string $customerId): array
    {
        $customer = AcctCustomer::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $relations = ['parentCustomer', 'subsidiaries'];
        if (Schema::hasTable('acct_customer_contacts')) {
            $relations['contacts'] = fn ($q) => $q->orderByDesc('is_primary')->orderBy('full_name');
        }
        if (Schema::hasTable('acct_customer_addresses')) {
            $relations['addresses'] = fn ($q) => $q->orderByDesc('is_default')->orderBy('label');
        }
        if (Schema::hasTable('acct_customer_price_lists')) {
            $relations['priceLists'] = fn ($q) => $q->with('item')->orderBy('item_name');
        }
        if (Schema::hasTable('acct_customer_documents')) {
            $relations['documents'] = fn ($q) => $q->orderByDesc('created_at');
        }
        if (Schema::hasTable('acct_customer_activities')) {
            $relations['activities'] = fn ($q) => $q->orderByDesc('acted_at');
        }
        $customer->load($relations);

        $today = now()->startOfDay();
        $docsEnriched = ($customer->documents ?? collect())->map(function (AcctCustomerDocument $doc) use ($today) {
            if (!$doc->expiry_date) {
                $doc->setAttribute('expiry_status', 'NO_EXPIRY');
                $doc->setAttribute('days_to_expiry', null);
            } else {
                $exp = Carbon::parse($doc->expiry_date)->startOfDay();
                $days = (int) $today->diffInDays($exp, false);
                $doc->setAttribute('days_to_expiry', $days);
                $doc->setAttribute('expiry_status', $days < 0 ? 'EXPIRED' : ($days <= 30 ? 'EXPIRING_SOON' : 'VALID'));
            }
            return $doc;
        })->values();
        $customer->setRelation('documents', $docsEnriched);

        // Credit Evaluation
        $credit = $this->creditService->evaluateCustomerCredit($customer, 0.0, (string) ($customer->payment_terms_code ?: 'NET_30'));

        // O2C Pipeline
        $allOrders = Schema::hasTable('sales_orders')
            ? SalesOrder::with(['lines', 'deliveries', 'invoices'])
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $quotes = $allOrders->filter(fn ($o) => in_array(strtoupper((string) $o->status), ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'QUOTE_EXPIRED'], true))->values();
        $activeOrders = $allOrders->filter(fn ($o) => in_array(strtoupper((string) $o->status), ['DRAFT', 'CREDIT_HOLD', 'APPROVED', 'PARTIALLY_DELIVERED'], true))->values();
        $backorders = $allOrders->filter(function ($o) {
            return in_array(strtoupper((string) $o->status), ['APPROVED', 'PARTIALLY_DELIVERED'], true)
                && $o->lines->some(fn ($l) => (float) $l->qty_ordered > (float) $l->qty_delivered + 0.0001);
        })->values();
        $completedOrders = $allOrders->filter(fn ($o) => in_array(strtoupper((string) $o->status), ['DELIVERED', 'BILLED', 'CLOSED'], true))->values();

        $deliveries = Schema::hasTable('stock_out_header')
            ? StockOutOrder::where('customer_id', $customerId)->orderByDesc('created_at')->take(30)->get()
            : collect();

        // AR & Aging Buckets + Statement of Account
        $invoices = Schema::hasTable('acct_sales_invoices')
            ? AcctSalesInvoice::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->orderByDesc('invoice_date')
                ->get()
            : collect();

        $receipts = Schema::hasTable('acct_collection_receipts')
            ? AcctCollectionReceipt::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->orderByDesc('receipt_date')
                ->get()
            : collect();

        $memos = Schema::hasTable('acct_customer_memos')
            ? AcctCustomerMemo::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->orderByDesc('memo_date')
                ->get()
            : collect();

        $buckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_plus' => 0.0,
            'total_open_ar' => 0.0,
        ];

        foreach ($invoices->whereIn('status', ['POSTED', 'PARTIAL']) as $inv) {
            $bal = max(0.0, (float) $inv->balance_due);
            if ($bal <= 0.0001) {
                continue;
            }
            $due = $inv->due_date ? Carbon::parse($inv->due_date)->startOfDay() : $today;
            $daysPast = (int) $due->diffInDays($today, false);
            if ($daysPast <= 0) {
                $buckets['current'] += $bal;
            } elseif ($daysPast <= 30) {
                $buckets['days_1_30'] += $bal;
            } elseif ($daysPast <= 60) {
                $buckets['days_31_60'] += $bal;
            } elseif ($daysPast <= 90) {
                $buckets['days_61_90'] += $bal;
            } else {
                $buckets['days_90_plus'] += $bal;
            }
            $buckets['total_open_ar'] += $bal;
        }
        foreach ($buckets as $k => $val) {
            $buckets[$k] = round($val, 2);
        }

        // Build Statement of Account (Chronological Debit/Credit Ledger)
        $soaEntries = collect();
        foreach ($invoices->whereIn('status', ['POSTED', 'PARTIAL', 'PAID']) as $inv) {
            $soaEntries->push([
                'date' => optional($inv->invoice_date)->toDateString(),
                'ref' => $inv->invoice_number,
                'type' => 'INVOICE',
                'description' => 'Sales Invoice' . ($inv->sales_order_number ? " ({$inv->sales_order_number})" : ''),
                'debit' => round((float) $inv->total_amount, 2),
                'credit' => 0.0,
                'status' => $inv->status,
            ]);
        }
        foreach ($receipts->where('status', 'POSTED') as $rcp) {
            $soaEntries->push([
                'date' => optional($rcp->receipt_date)->toDateString(),
                'ref' => $rcp->receipt_number,
                'type' => 'RECEIPT',
                'description' => "Collection ({$rcp->payment_method})" . ($rcp->cwt_amount > 0 ? ' incl. BIR 2307 CWT' : ''),
                'debit' => 0.0,
                'credit' => round((float) $rcp->gross_amount, 2),
                'status' => $rcp->status,
            ]);
        }
        foreach ($memos->where('status', 'POSTED') as $m) {
            $isCredit = strtoupper((string) $m->memo_type) === 'CREDIT';
            $soaEntries->push([
                'date' => optional($m->memo_date)->toDateString(),
                'ref' => $m->memo_number,
                'type' => $isCredit ? 'CREDIT_MEMO' : 'DEBIT_MEMO',
                'description' => ($isCredit ? 'Credit Memo: ' : 'Debit Memo: ') . ($m->reason ?: 'Adjustment'),
                'debit' => $isCredit ? 0.0 : round((float) $m->total_amount, 2),
                'credit' => $isCredit ? round((float) $m->total_amount, 2) : 0.0,
                'status' => $m->status,
            ]);
        }

        $running = 0.0;
        $soaRows = $soaEntries->sortBy('date')->values()->map(function ($row) use (&$running) {
            $running = round($running + $row['debit'] - $row['credit'], 2);
            $row['running_balance'] = $running;
            return $row;
        })->all();

        // Reporting & Analytics (CLV, Order Frequency, Gross Margin %, DSO)
        $postedInvoices = $invoices->whereIn('status', ['POSTED', 'PARTIAL', 'PAID']);
        $clv = round((float) $postedInvoices->sum('subtotal_amount'), 2);
        $totalBilledGross = round((float) $postedInvoices->sum('total_amount'), 2);
        $shippedCogs = round((float) $deliveries->where('status', 'SHIPPED')->sum('total_cost_value'), 2);
        $grossProfit = round($clv - $shippedCogs, 2);
        $marginPct = $clv > 0.01 ? round(($grossProfit / $clv) * 100, 1) : 0.0;

        $confirmedOrdersCount = $allOrders->whereNotIn('status', ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_EXPIRED', 'CANCELLED', 'ARCHIVED'])->count();
        $firstOrderDate = $allOrders->last()?->created_at ? Carbon::parse($allOrders->last()->created_at) : now();
        $monthsActive = max(1, (int) $firstOrderDate->diffInMonths(now()) + 1);
        $ordersPerMonth = round($confirmedOrdersCount / $monthsActive, 1);
        $avgOrderVal = $confirmedOrdersCount > 0 ? round($clv / $confirmedOrdersCount, 2) : 0.0;

        $last365Billed = (float) $postedInvoices
            ->filter(fn ($i) => $i->invoice_date && Carbon::parse($i->invoice_date)->gte($today->copy()->subDays(365)))
            ->sum('total_amount');
        $dsoBasis = $last365Billed > 0.01 ? $last365Billed : $totalBilledGross;
        $dsoDays = $dsoBasis > 0.01 ? (int) round(($buckets['total_open_ar'] / $dsoBasis) * 365) : 0;

        return [
            'customer' => $customer,
            'credit' => $credit,
            'pipeline' => [
                'quotes' => $quotes,
                'active_orders' => $activeOrders,
                'backorders' => $backorders,
                'completed_orders' => $completedOrders,
                'deliveries' => $deliveries,
            ],
            'ar_aging' => [
                'buckets' => $buckets,
                'invoices' => $invoices->take(40)->values(),
                'receipts' => $receipts->take(40)->values(),
                'memos' => $memos->take(30)->values(),
                'soa_rows' => $soaRows,
            ],
            'analytics' => [
                'clv_amount' => $clv,
                'orders_count' => $confirmedOrdersCount,
                'orders_per_month' => $ordersPerMonth,
                'avg_order_value' => $avgOrderVal,
                'total_cogs' => $shippedCogs,
                'gross_profit' => $grossProfit,
                'gross_margin_pct' => $marginPct,
                'dso_days' => $dsoDays,
            ],
        ];
    }

    // =========================================================================
    // SUB-RESOURCES: CONTACTS, ADDRESSES, PRICE LIST, COMPLIANCE DOCS, ACTIVITIES
    // =========================================================================

    public function saveContact(string $companyId, string $customerId, array $data, string $actor): AcctCustomerContact
    {
        return DB::transaction(function () use ($companyId, $customerId, $data, $actor) {
            $customer = AcctCustomer::where('company_id', $companyId)->where('customer_id', $customerId)->lockForUpdate()->firstOrFail();

            $isPrimary = (bool) ($data['is_primary'] ?? false);
            $hasAny = AcctCustomerContact::where('customer_id', $customerId)->exists();
            if (!$hasAny) {
                $isPrimary = true;
            }
            if ($isPrimary) {
                AcctCustomerContact::where('customer_id', $customerId)->update(['is_primary' => false]);
            }

            $contact = !empty($data['contact_id'])
                ? AcctCustomerContact::where('customer_id', $customerId)->where('contact_id', $data['contact_id'])->first()
                : null;

            $attrs = [
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'full_name' => trim((string) $data['full_name']),
                'role_title' => $data['role_title'] ?? null,
                'department' => $data['department'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_primary' => $isPrimary,
                'receives_invoices' => (bool) ($data['receives_invoices'] ?? false),
                'notes' => $data['notes'] ?? null,
            ];

            if ($contact) {
                $contact->fill($attrs);
                $contact->save();
            } else {
                $attrs['contact_id'] = (string) Str::uuid();
                $attrs['created_by'] = $actor;
                $contact = AcctCustomerContact::create($attrs);
            }

            if ($isPrimary) {
                $customer->contact_person = $contact->full_name;
                if ($contact->email) $customer->email = $contact->email;
                if ($contact->phone) $customer->phone = $contact->phone;
                $customer->save();
            }

            return $contact;
        });
    }

    public function deleteContact(string $companyId, string $customerId, string $contactId): void
    {
        AcctCustomerContact::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('contact_id', $contactId)
            ->delete();
    }

    public function saveAddress(string $companyId, string $customerId, array $data, string $actor): AcctCustomerAddress
    {
        return DB::transaction(function () use ($companyId, $customerId, $data, $actor) {
            $customer = AcctCustomer::where('company_id', $companyId)->where('customer_id', $customerId)->lockForUpdate()->firstOrFail();

            $type = strtoupper((string) ($data['address_type'] ?? 'SHIPPING'));
            $isDefault = (bool) ($data['is_default'] ?? false);
            $hasAny = AcctCustomerAddress::where('customer_id', $customerId)->where('address_type', $type)->exists();
            if (!$hasAny) {
                $isDefault = true;
            }
            if ($isDefault) {
                AcctCustomerAddress::where('customer_id', $customerId)
                    ->where('address_type', $type)
                    ->update(['is_default' => false]);
            }

            $addr = !empty($data['address_id'])
                ? AcctCustomerAddress::where('customer_id', $customerId)->where('address_id', $data['address_id'])->first()
                : null;

            $attrs = [
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'address_type' => $type,
                'label' => trim((string) $data['label']),
                'address_line' => trim((string) $data['address_line']),
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'receiving_contact' => $data['receiving_contact'] ?? null,
                'receiving_phone' => $data['receiving_phone'] ?? null,
                'delivery_window' => $data['delivery_window'] ?? null,
                'is_default' => $isDefault,
            ];

            if ($addr) {
                $addr->fill($attrs);
                $addr->save();
            } else {
                $attrs['address_id'] = (string) Str::uuid();
                $attrs['created_by'] = $actor;
                $addr = AcctCustomerAddress::create($attrs);
            }

            if ($isDefault) {
                $full = trim($addr->address_line . ($addr->city ? ', ' . $addr->city : ''));
                if ($type === 'BILLING' || $type === 'BOTH') {
                    $customer->address = $full;
                }
                if ($type === 'SHIPPING' || $type === 'BOTH') {
                    $customer->shipping_address = $full;
                }
                $customer->save();
            }

            return $addr;
        });
    }

    public function deleteAddress(string $companyId, string $customerId, string $addressId): void
    {
        AcctCustomerAddress::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('address_id', $addressId)
            ->delete();
    }

    public function savePriceListEntry(string $companyId, string $customerId, array $data, string $actor): AcctCustomerPriceList
    {
        AcctCustomer::where('company_id', $companyId)->where('customer_id', $customerId)->firstOrFail();

        $inv = !empty($data['inventory_item_id'])
            ? InventoryItem::find((int) $data['inventory_item_id'])
            : null;

        $stdPrice = $inv ? (float) $inv->selling_price : (float) ($data['standard_price'] ?? 0.0);
        $contractPrice = round((float) $data['contract_price'], 4);
        $discPct = $stdPrice > 0.0001
            ? max(0.0, round((($stdPrice - $contractPrice) / $stdPrice) * 100, 2))
            : (float) ($data['discount_pct'] ?? 0.0);

        $entry = !empty($data['price_list_id'])
            ? AcctCustomerPriceList::where('customer_id', $customerId)->where('price_list_id', $data['price_list_id'])->first()
            : null;

        $attrs = [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'inventory_item_id' => $inv?->id,
            'internal_sku' => $inv?->sku ?? ($data['internal_sku'] ?? null),
            'item_name' => $inv?->name ?? trim((string) ($data['item_name'] ?? 'Contract SKU')),
            'customer_sku' => !empty($data['customer_sku']) ? strtoupper(trim((string) $data['customer_sku'])) : null,
            'uom' => $data['uom'] ?? ($inv?->uom ?? 'PCS'),
            'standard_price' => $stdPrice,
            'contract_price' => $contractPrice,
            'discount_pct' => $discPct,
            'min_qty' => max(0.0001, (float) ($data['min_qty'] ?? 1.0)),
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
        ];

        if ($entry) {
            $entry->fill($attrs);
            $entry->save();
        } else {
            $attrs['price_list_id'] = (string) Str::uuid();
            $attrs['created_by'] = $actor;
            $entry = AcctCustomerPriceList::create($attrs);
        }

        return $entry->fresh('item');
    }

    public function deletePriceListEntry(string $companyId, string $customerId, string $priceListId): void
    {
        AcctCustomerPriceList::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('price_list_id', $priceListId)
            ->delete();
    }

    public function saveDocument(
        string $companyId,
        string $customerId,
        array $data,
        ?UploadedFile $file,
        string $actor
    ): AcctCustomerDocument {
        AcctCustomer::where('company_id', $companyId)->where('customer_id', $customerId)->firstOrFail();

        $filePath = null;
        $fileName = null;
        if ($file && $file->isValid()) {
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store("customer_docs/{$companyId}/{$customerId}", 'public');
        }

        $doc = AcctCustomerDocument::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'doc_type' => strtoupper((string) ($data['doc_type'] ?? 'CONTRACT')),
            'document_title' => trim((string) $data['document_title']),
            'reference_no' => $data['reference_no'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $actor,
        ]);

        $this->recordSystemActivity(
            $companyId,
            $customerId,
            'Compliance Document Added',
            "{$doc->doc_type}: {$doc->document_title}" . ($doc->expiry_date ? " (Expires {$doc->expiry_date->toDateString()})" : ''),
            $actor
        );

        return $doc;
    }

    public function deleteDocument(string $companyId, string $customerId, string $documentId): void
    {
        $doc = AcctCustomerDocument::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('document_id', $documentId)
            ->firstOrFail();

        if (!empty($doc->file_path) && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
    }

    public function logActivity(string $companyId, string $customerId, array $data, string $actor): AcctCustomerActivity
    {
        AcctCustomer::where('company_id', $companyId)->where('customer_id', $customerId)->firstOrFail();

        return AcctCustomerActivity::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'activity_type' => strtoupper((string) ($data['activity_type'] ?? 'NOTE')),
            'subject' => trim((string) $data['subject']),
            'description' => $data['description'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'acted_by' => $actor,
            'acted_at' => now(),
        ]);
    }

    public function recordSystemActivity(string $companyId, string $customerId, string $subject, string $description, string $actor): void
    {
        if (!Schema::hasTable('acct_customer_activities')) {
            return;
        }

        AcctCustomerActivity::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'activity_type' => 'SYSTEM',
            'subject' => $subject,
            'description' => $description,
            'acted_by' => $actor ?: 'System',
            'acted_at' => now(),
        ]);
    }
}
