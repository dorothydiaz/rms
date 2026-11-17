@extends('layouts.app')

@section('title', 'Accounts Payable')

@section('content')
@php
    $initialTab = request()->query('tab', 'bills');
    $validTabs = ['bills', 'payments', 'memos', 'ledger'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'bills';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';

    $apPmtsCollection = collect($accountingPayload['vendorPayments'] ?? []);
    $apTotalEwtWithheld = $apPmtsCollection->sum(fn($p) => (float) ($p->total_ewt_amount ?? $p->ewt_amount ?? 0));
    $apAvailVendorCredits = collect($accountingPayload['vendorMemos'] ?? [])
        ->filter(fn($m) => in_array(strtoupper((string) ($m->memo_type ?? '')), ['CREDIT', 'CREDIT_MEMO'], true)
            && strtoupper((string) ($m->status ?? '')) === 'POSTED'
            && (float) ($m->unapplied_amount ?? 0) > 0)
        ->sum(fn($m) => (float) $m->unapplied_amount);

    $apBankAccountMap = collect($accountingPayload['bankAccounts'] ?? [])->keyBy('bank_account_id');
    $apCoaMap = collect($accountingPayload['accounts'] ?? [])->keyBy('account_id');
    $apPoByIdMap = collect($accountingPayload['purchaseOrders'] ?? [])->keyBy(fn($po) => (string) $po->id);
    $apBillByIdMap = collect($accountingPayload['vendorBills'] ?? [])->keyBy('vendor_bill_id');
    $apCertificatesList = collect($accountingPayload['vendorCertificates'] ?? []);
    $apCertByPmtIdMap = $apCertificatesList->filter(fn($c) => !empty($c->vendor_payment_id))->keyBy('vendor_payment_id');
    $apCertBySourceIdMap = $apCertificatesList->filter(fn($c) => !empty($c->source_document_id))->keyBy('source_document_id');
@endphp

<div class="acct-workspace" id="accountingApRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Accounts Payable',
        'pageSubtitle' => 'Unified AP subledger connected to Purchase Orders & Stock In (3-way matching, EWT & BIR 2307)',
        'pageIcon' => 'ph-receipt',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- Unified Top AP KPI Summary Cards (4 Metrics) -->
    <div class="acct-kpi-grid-4">
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">AP Control Balance</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-receipt"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format((float) ($accountingPayload['kpis']['payables'] ?? 0), 2) }}</div>
            <div class="rfq-kpi-sub">Total open vendor payables</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Overdue Vendor Bills</span><span class="rfq-kpi-icon draft"><i class="ph ph-warning-circle"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format((float) ($accountingPayload['kpis']['overdueBills'] ?? 0), 2) }}</div>
            <div class="rfq-kpi-sub">Past-due vendor obligations</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">EWT Withheld (BIR 2307)</span><span class="rfq-kpi-icon"><i class="ph ph-percent"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;color:#9333ea;">{{ $cur }} {{ number_format($apTotalEwtWithheld, 2) }}</div>
            <div class="rfq-kpi-sub">{{ $apCertificatesList->count() }} BIR 2307 certificate(s) issued</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Unapplied Vendor Credits</span><span class="rfq-kpi-icon awarded"><i class="ph ph-wallet"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;color:#059669;">{{ $cur }} {{ number_format($apAvailVendorCredits, 2) }}</div>
            <div class="rfq-kpi-sub">From overpayments &amp; credit memos</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'bills' ? ' active' : '' }}" data-acct-tab="bills">
            <i class="ph ph-files"></i> <span>Vendor Bills Tracker</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'payments' ? ' active' : '' }}" data-acct-tab="payments">
            <i class="ph ph-credit-card"></i> <span>Vendor Payments &amp; 2307</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'memos' ? ' active' : '' }}" data-acct-tab="memos">
            <i class="ph ph-arrows-left-right"></i> <span>Vendor Credit / Debit Memos</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'ledger' ? ' active' : '' }}" data-acct-tab="ledger">
            <i class="ph ph-list-numbers"></i> <span>Vendor Ledger &amp; Aging</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: VENDOR BILLS TRACKER (AUTO-SYNCED FROM PURCHASE ORDERS) -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'bills' ? ' is-active' : '' }}" data-acct-pane="bills">
        
        <!-- 1. VENDOR BILLS TRACKER (PO Obligations Registry) -->
        <div class="acct-panel" id="apBillsRegisterPanel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-receipt"></i> Vendor Bills &amp; PO Obligations Tracker</h2>
                    <span class="acct-badge acct-badge-system" id="apBillsCountBadge">{{ count($accountingPayload['vendorBills'] ?? []) }} PO Obligations</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="text" class="acct-input" id="apBillSearch" placeholder="Search PO #, bill #, or vendor..." style="width:210px;height:34px;">
                    <select class="acct-select" id="apBillStatusFilter" style="width:130px;height:34px;">
                        <option value="">All Statuses</option>
                        <option value="POSTED">Open / Unpaid</option>
                        <option value="PARTIAL">Partial</option>
                        <option value="PAID">Paid</option>
                        <option value="DRAFT">Draft</option>
                    </select>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnApOpenPaymentFromBills" title="Open Payment Matching Drawer">
                        <i class="ph ph-credit-card"></i> Record PO Payment
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnApNewBill" style="display:none;" title="Legacy direct bill adjustment">
                        <i class="ph ph-plus-circle"></i> Direct Adjustment
                    </button>
                    <a href="{{ route('purchase.purchase-orders') }}" class="rfq-btn rfq-btn-outline" title="Approved POs automatically sync into this tracker">
                        <i class="ph ph-shopping-cart"></i> View POs
                    </a>
                </div>
            </div>
            <div class="rfq-table-responsive" style="overflow-x: auto; position: relative; min-height: 220px;">
                <table class="rfq-table acct-table" id="apBillsRegisterTable">
                    <thead id="apBillsThead">
                        <tr id="apBillsTheadRow">
                            <th class="rfq-th-stacked th-left" data-col-id="bill_no" style="width:130px;">Bill #</th>
                            <th class="rfq-th-stacked th-left" data-col-id="vendor" style="width:190px;">Vendor</th>
                            <th class="rfq-th-stacked th-left" data-col-id="invoice_no" style="width:140px;">Invoice / Ref #</th>
                            <th class="rfq-th-stacked th-left" data-col-id="po_match" style="width:135px;">PO / GRN Match</th>
                            <th class="rfq-th-stacked th-left" data-col-id="bill_date" style="width:110px;">Bill Date</th>
                            <th class="rfq-th-stacked th-left" data-col-id="due_date" style="width:110px;">Due Date</th>
                            <th class="rfq-th-stacked th-left" data-col-id="match_type" style="width:130px;">Match Type</th>
                            <th class="rfq-th-stacked th-num" data-col-id="total" style="width:125px;text-align:right;">Total Amount</th>
                            <th class="rfq-th-stacked th-num" data-col-id="paid" style="width:125px;text-align:right;">Paid / Credit</th>
                            <th class="rfq-th-stacked th-num" data-col-id="balance" style="width:125px;text-align:right;">Balance Due</th>
                            <th class="rfq-th-stacked th-left" data-col-id="status" style="width:105px;">Status</th>
                            <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width:165px;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="apBillsRegisterBody">
                        @forelse($accountingPayload['vendorBills'] ?? [] as $bill)
                            @php
                                $matchedPo = collect($accountingPayload['purchaseOrders'] ?? [])->first(function ($p) use ($bill) {
                                    return (string) $p->id === (string) $bill->purchase_order_id
                                        || (string) $p->po_number === (string) $bill->purchase_order_id;
                                });
                                $vObj = collect($accountingPayload['vendors'] ?? [])->first(function ($v) use ($bill) {
                                    return (string) $v->id === (string) $bill->vendor_id
                                        || (string) $v->vendor_code === (string) $bill->vendor_id;
                                });
                                $vName = $vObj ? ($vObj->trade_name ?: $vObj->legal_name) : $bill->vendor_id;
                                $paid = (float) $bill->bill_paid_amount;
                                $balance = max(0, (float) $bill->bill_total_amount - $paid);
                                $canPay = $balance > 0.01 && in_array(strtoupper((string) $bill->vendor_bill_status), ['POSTED', 'PARTIAL', 'APPROVED'], true);
                            @endphp
                            <tr data-bill-id="{{ $bill->vendor_bill_id }}" data-bill-status="{{ $bill->vendor_bill_status }}">
                                <td data-col-id="bill_no" class="acct-mono" style="font-weight:600;color:#9333ea;">{{ $bill->vendor_bill_number }}</td>
                                <td data-col-id="vendor">
                                    <div style="font-weight:500;">{{ $vName }}</div>
                                    @if($vObj && $vObj->vendor_code)
                                        <div style="font-size:11px;color:#64748b;">{{ $vObj->vendor_code }}</div>
                                    @endif
                                </td>
                                <td data-col-id="invoice_no">{{ $bill->vendor_invoice_number }}</td>
                                <td data-col-id="po_match">
                                    @if($matchedPo)
                                        <span class="acct-badge" style="background:#e0f2fe;color:#0369a1;font-weight:600;">PO: {{ $matchedPo->po_number }}</span>
                                    @else
                                        <span style="color:#94a3b8;font-size:12px;">Direct / None</span>
                                    @endif
                                </td>
                                <td data-col-id="bill_date">{{ \Carbon\Carbon::parse($bill->vendor_invoice_date)->format('Y-m-d') }}</td>
                                <td data-col-id="due_date">{{ \Carbon\Carbon::parse($bill->bill_due_date)->format('Y-m-d') }}</td>
                                <td data-col-id="match_type">
                                    <span class="acct-badge acct-badge-{{ strtolower($bill->bill_match_status ?? 'direct') }}">
                                        {{ $bill->bill_match_status ?? 'DIRECT_EXPENSE' }}
                                    </span>
                                </td>
                                <td data-col-id="total" class="acct-num">{{ number_format((float) $bill->bill_total_amount, 2) }}</td>
                                <td data-col-id="paid" class="acct-num" style="color:#0284c7;">{{ number_format($paid, 2) }}</td>
                                <td data-col-id="balance" class="acct-num" style="font-weight:700;color:{{ $balance > 0 ? '#ef4444' : '#10b981' }};">
                                    {{ number_format($balance, 2) }}
                                </td>
                                <td data-col-id="status">
                                    <span class="acct-badge acct-badge-{{ strtolower($bill->vendor_bill_status) }}">
                                        {{ $bill->vendor_bill_status }}
                                    </span>
                                </td>
                                <td data-col-id="actions" style="text-align:right;overflow:visible;">
                                    <div style="display:inline-flex;align-items:center;justify-content:flex-end;gap:5px;">
                                        @if($canPay)
                                            <button type="button" class="acct-row-btn btnApPayBill" data-bill-id="{{ $bill->vendor_bill_id }}" data-vendor-id="{{ $vObj ? $vObj->id : $bill->vendor_id }}" style="border-color:#a7f3d0;background:#ecfdf5;color:#047857;" title="Record Payment Voucher / Settle Bill">
                                                <i class="ph ph-credit-card"></i> Pay
                                            </button>
                                        @endif
                                        <button type="button" class="acct-row-btn btnApViewBill" data-bill-id="{{ $bill->vendor_bill_id }}" title="View / Edit bill in workspace drawer">
                                            <i class="ph ph-eye"></i> View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="ap-empty-row">
                                <td colspan="12" style="text-align:center;padding:28px;color:#94a3b8;">
                                    No PO obligations found yet. Approved Purchase Orders automatically sync into this AP tracker.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- RFQ-Style Floating Table Columns & Width Settings Dropdown -->
                <div id="apBillsTableSettingsDropdown" class="rfq-table-settings-dropdown" style="display:none;">
                    <div class="rfq-settings-dropdown-header">
                        <div class="rfq-settings-title-row" style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="rfq-settings-title">
                                <i class="ph ph-gear-six"></i>
                                <span>Table Columns &amp; Sequence</span>
                            </div>
                            <span class="rfq-col-badge-default" id="apBillsColActiveCounter">12 of 12 visible</span>
                        </div>
                        <div class="rfq-settings-links-row">
                            <button type="button" class="rfq-settings-link-btn" id="btnApBillsResetDefaults">Reset to Default</button>
                            <button type="button" class="rfq-settings-link-btn" id="btnApBillsShowAll">Select All</button>
                            <button type="button" class="rfq-settings-link-btn" id="btnApBillsResetWidths">Reset Widths</button>
                        </div>
                        <input type="text" class="rfq-settings-search-input" id="apBillsColSearchInput" placeholder="Filter columns...">
                    </div>
                    <div class="rfq-settings-col-list" id="apBillsColCheckboxesList"></div>
                </div>
            </div>
        </div>

        <!-- 2. VENDOR BILL WORKSPACE (Canonical RMS Drawer) -->
        <div class="rms-drawer-backdrop" id="apBillDrawerBackdrop"></div>
        <aside class="rms-drawer-panel" id="apBillDrawerPanel" style="width: min(1040px, 96vw);" role="dialog" aria-modal="true" aria-labelledby="apBillDrawerTitle" aria-hidden="true">
            <div class="rms-drawer-header">
                <div class="rms-drawer-head-left">
                    <div class="rms-drawer-icon">
                        <i class="ph ph-files"></i>
                    </div>
                    <div style="min-width:0;">
                        <div class="rms-drawer-title-row">
                            <h2 class="rms-drawer-title" id="apBillDrawerTitle">Vendor Bill Workspace</h2>
                            <span class="acct-badge acct-badge-draft" id="apBillStatusBadge">NEW DRAFT</span>
                            <span class="acct-mono" id="apBillNumberDisplay" style="color:#9333ea;">AUTO-NUMBERED</span>
                            <span class="acct-badge acct-badge-system" id="apBillMatchBadge">DIRECT_EXPENSE</span>
                        </div>
                        <p class="rms-drawer-sub">Create a direct supplier bill or link a Purchase Order, apply vendor credits &amp; track settlement.</p>
                    </div>
                </div>
                <div class="rms-drawer-head-actions">
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnApResetDraft" style="height:32px;padding:0 10px;font-size:12px;">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset
                    </button>
                    <button type="button" class="rms-drawer-close" id="btnApCloseBillDrawer" aria-label="Close Vendor Bill Workspace">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>

            <div class="rms-drawer-body">
                <input type="hidden" id="apBillId" value="">

                <!-- Card 1: Primary Bill Header & PO Link -->
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <div class="rms-drawer-card-title">
                            <i class="ph ph-buildings"></i>
                            <span>Vendor &amp; Purchase Order Link</span>
                        </div>
                        <span class="rms-drawer-card-badge">Header Details</span>
                    </div>
                    <div class="rms-drawer-card-body">
                        <div class="acct-header-fields">
                            <div class="acct-field">
                                <label class="acct-label" for="apBillVendor">Vendor <span style="color:#ef4444;">*</span></label>
                                <select class="acct-select" id="apBillVendor">
                                    <option value="">-- Select Vendor --</option>
                                    @foreach($accountingPayload['vendors'] ?? [] as $v)
                                        <option value="{{ $v->id }}" data-code="{{ $v->vendor_code }}" data-terms="{{ $v->payment_terms }}" data-tax-id="{{ $v->tax_id }}">
                                            {{ $v->trade_name ?: $v->legal_name }} ({{ $v->vendor_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="acct-field">
                                <label class="acct-label" for="apBillPoReference">
                                    Link Purchase Order <span style="font-size:10px;font-weight:normal;color:#64748b;">(Optional)</span>
                                </label>
                                <select class="acct-select" id="apBillPoReference">
                                    <option value="">-- No PO (Direct Bill) --</option>
                                    @foreach($accountingPayload['purchaseOrders'] ?? [] as $po)
                                        <option value="{{ $po->id }}" data-po-no="{{ $po->po_number }}" data-vendor-id="{{ $po->resolved_vendor_id ?? $po->vendor_id }}" data-total="{{ $po->gross_total }}" data-advance="{{ $po->amount_paid ?? 0 }}">
                                            {{ $po->po_number }} - {{ $po->vendor_name }} ({{ $cur }} {{ number_format((float) $po->gross_total, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="acct-field">
                                <label class="acct-label" for="apBillInvoiceNum">Vendor Invoice / Ref # <span style="color:#ef4444;">*</span></label>
                                <input type="text" class="acct-input" id="apBillInvoiceNum" placeholder="e.g. SI-2026-90412">
                            </div>
                            <div class="acct-field">
                                <label class="acct-label" for="apBillInvoiceDate">Invoice &amp; Due Date <span style="color:#ef4444;">*</span></label>
                                <div style="display:flex;gap:6px;">
                                    <input type="date" class="acct-input" id="apBillInvoiceDate" value="{{ now()->toDateString() }}">
                                    <input type="date" class="acct-input" id="apBillDueDate" value="{{ now()->addDays(30)->toDateString() }}" title="Due Date">
                                </div>
                            </div>
                        </div>

                        <details class="acct-details-box" style="margin-top:10px;">
                            <summary><i class="ph ph-caret-right"></i> Branch Dimension, Multi-Currency &amp; Remarks</summary>
                            <div class="acct-header-fields" style="margin-top:10px;grid-template-columns: repeat(3, 1fr);">
                                <div class="acct-field">
                                    <label class="acct-label" for="apBillBranch">Branch Dimension</label>
                                    <select class="acct-select" id="apBillBranch">
                                        @foreach($accountingPayload['branches'] ?? [] as $br)
                                            @php $bId = is_array($br) ? ($br['branch_id'] ?? '') : ($br->branch_id ?? ''); $bName = is_array($br) ? ($br['branch_name'] ?? '') : ($br->branch_name ?? ''); @endphp
                                            <option value="{{ $bId }}">{{ $bName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="acct-field">
                                    <label class="acct-label" for="apBillCurrency">Currency &amp; Rate</label>
                                    <div style="display:flex;gap:6px;">
                                        <select class="acct-select" id="apBillCurrency" style="width:85px;flex-shrink:0;">
                                            <option value="PHP">PHP</option>
                                            <option value="USD">USD</option>
                                        </select>
                                        <input type="number" step="0.000001" min="0.000001" class="acct-input acct-num" id="apBillFxRate" value="1.000000">
                                    </div>
                                </div>
                                <div class="acct-field">
                                    <label class="acct-label" for="apBillNotes">Bill Remarks</label>
                                    <input type="text" class="acct-input" id="apBillNotes" placeholder="Delivery notes, internal memo...">
                                </div>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Card 2: Dynamic Line Items Grid + Add Row Controls -->
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="rms-drawer-card-title">
                                <i class="ph ph-list-bullets"></i>
                                <span>Bill Line Items</span>
                            </div>
                            <span class="rms-drawer-card-badge" id="apBillLinesCountBadge">1 Line</span>
                        </div>
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnApAddBillLineTop" style="height:30px;padding:0 12px;font-size:12px;">
                            <i class="ph ph-plus"></i> Add Row
                        </button>
                    </div>
                    <div class="acct-lines-table-wrap">
                        <table class="acct-table acct-lines-table" id="apBillLinesTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;">#</th>
                                    <th style="min-width:210px;">Description / Item</th>
                                    <th style="min-width:120px;">PO / GRN Match Ref</th>
                                    <th class="acct-num" style="min-width:80px;">Quantity</th>
                                    <th style="min-width:130px;">UOM</th>
                                    <th class="acct-num" style="min-width:105px;">Unit Price ({{ $cur }})</th>
                                    <th style="min-width:110px;">Tax Code</th>
                                    <th class="acct-num" style="min-width:95px;">Tax ({{ $cur }})</th>
                                    <th class="acct-num" style="min-width:105px;">Line Total ({{ $cur }})</th>
                                    <th style="min-width:150px;">Expense Account</th>
                                    <th style="width:44px;text-align:center;">Del</th>
                                </tr>
                            </thead>
                            <tbody id="apBillLinesBody"></tbody>
                        </table>
                    </div>
                    <div style="padding:10px 16px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;background:#fafafa;">
                        <button type="button" class="acct-row-btn" id="btnApAddBillLine">
                            <i class="ph ph-plus"></i> Add Row
                        </button>
                        <span style="font-size:11.5px;color:#64748b;">Dynamic bill lines &bull; Auto-populated upon selecting a matched Purchase Order</span>
                    </div>
                </div>

                <!-- Card 3: Financial Summary, Credit Application & Payment Settlement -->
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <div class="rms-drawer-card-title">
                            <i class="ph ph-scales"></i>
                            <span>Financial Summary &amp; Payment Settlement</span>
                        </div>
                        <button type="button" class="acct-row-btn" id="btnApDrawerPayBillInline" style="display:none;border-color:#a7f3d0;background:#ecfdf5;color:#047857;">
                            <i class="ph ph-credit-card"></i> Record Payment / Settle
                        </button>
                    </div>
                    <div class="rms-drawer-card-body">
                        <div class="acct-summary-metrics" id="apBillSummaryCard">
                            <div class="acct-summary-stat">
                                <span class="acct-summary-stat-label">Subtotal</span>
                                <span class="acct-summary-stat-val" id="apBillSumSubtotal">0.00</span>
                            </div>
                            <div class="acct-summary-stat">
                                <span class="acct-summary-stat-label">Input VAT</span>
                                <span class="acct-summary-stat-val" id="apBillSumTax">0.00</span>
                            </div>
                            <div class="acct-summary-stat">
                                <span class="acct-summary-stat-label">Shipping / Freight</span>
                                <input type="number" step="0.01" min="0" class="acct-input acct-num" id="apBillShipping" value="0.00" style="width:110px;height:32px;">
                            </div>
                            <div class="acct-summary-stat">
                                <span class="acct-summary-stat-label">Grand Total Payable</span>
                                <span class="acct-summary-stat-val" id="apBillSumGrandTotal" style="font-weight:700;color:#0f172a;">0.00</span>
                            </div>
                            <div class="acct-summary-stat">
                                <span class="acct-summary-stat-label">PO Advance / Paid</span>
                                <span class="acct-summary-stat-val" id="apBillPoAdvanceDisplay" style="color:#0284c7;font-weight:600;">0.00</span>
                            </div>
                            <div class="acct-summary-stat" style="min-width:220px;">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                    <input type="checkbox" id="apBillApplyCreditChk">
                                    <label for="apBillApplyCreditChk" style="font-size:11.5px;font-weight:600;color:#334155;cursor:pointer;">
                                        Apply Vendor Credit <span id="apBillAvailCreditBadge" style="color:#16a34a;font-weight:600;">(₱0.00 avail)</span>
                                    </label>
                                </div>
                                <input type="number" step="0.01" min="0" class="acct-input acct-num" id="apBillCreditAppliedAmount" value="0.00" disabled style="width:120px;height:32px;">
                            </div>
                            <div class="acct-summary-stat">
                                <span class="acct-summary-stat-label">Net Balance Due</span>
                                <span class="acct-summary-stat-val" id="apBillNetBalanceDueDisplay" style="font-weight:700;color:#ef4444;font-size:16px;">0.00</span>
                            </div>
                        </div>

                        <!-- Recorded Settlement History for this Bill -->
                        <div id="apBillPaymentHistoryWrap" style="margin-top:14px;padding-top:12px;border-top:1px dashed #e2e8f0;display:none;">
                            <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#475569;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                <i class="ph ph-receipt" style="color:#7c3aed;"></i>
                                <span>Recorded Payments &amp; Applied Credits</span>
                            </div>
                            <div id="apBillPaymentHistoryList" style="display:flex;flex-direction:column;gap:6px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rms-drawer-footer">
                <div class="rms-drawer-footer-meta" id="apBillDrawerFooterMeta">
                    <span>Total: <strong id="apBillDrawerFooterTotal">{{ $cur }} 0.00</strong></span>
                    <span>&bull;</span>
                    <span>Balance Due: <strong id="apBillDrawerFooterBalance" style="color:#ef4444;">{{ $cur }} 0.00</strong></span>
                </div>
                <div class="rms-drawer-footer-actions">
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnApCancelBill">
                        Cancel
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnApSaveBillDraft">
                        <i class="ph ph-floppy-disk"></i> Save Draft
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnApPostBill">
                        <i class="ph ph-check-circle"></i> Post Bill to GL
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnApDrawerPayBill" style="display:none;background:#059669;border-color:#047857;">
                        <i class="ph ph-credit-card"></i> Pay / Settle Bill
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: UNIFIED VENDOR PAYMENTS & BIR FORM 2307 REGISTER -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'payments' ? ' is-active' : '' }}" data-acct-pane="payments">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-credit-card"></i> Vendor Payments &amp; BIR Form 2307 Register</h2>
                    <span class="acct-badge acct-badge-system">{{ $apPmtsCollection->count() }} Vouchers</span>
                    <span class="acct-badge" style="background:#f3e8ff;color:#7e22ce;font-weight:700;">{{ $apCertificatesList->count() }} Form 2307 Issued</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="text" class="acct-input" id="apPaymentSearch" placeholder="Search voucher #, PO #, 2307 #, vendor..." style="width:225px;height:34px;">
                    <select class="acct-select" id="apPaymentMethodFilter" style="width:165px;height:34px;">
                        <option value="">All Vouchers</option>
                        <option value="WITH_2307">With BIR 2307 Issued</option>
                        <option value="NO_2307">Bill Only (No 2307)</option>
                        <option value="BANK_TRANSFER">Bank Transfer</option>
                        <option value="CHECK">Check</option>
                        <option value="PDC">PDC</option>
                        <option value="CASH">Cash</option>
                        <option value="PO_ADVANCE">PO Advance</option>
                    </select>
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnApNewSeparate2307" title="Record a separate BIR 2307 / EWT withholding settlement">
                        <i class="ph ph-certificate"></i> + Separate 2307
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnApNewPayment">
                        <i class="ph ph-plus-circle"></i> New Payment / Settle PO
                    </button>
                </div>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="apPaymentsRegisterTable">
                    <thead>
                        <tr>
                            <th>Voucher #</th>
                            <th>Payment Date</th>
                            <th>Vendor</th>
                            <th>Matched PO / Bill</th>
                            <th>Method</th>
                            <th>Bank / Reference</th>
                            <th class="acct-num">Gross Amount</th>
                            <th class="acct-num">EWT (2307)</th>
                            <th class="acct-num">Net Disbursed</th>
                            <th>Status</th>
                            <th style="text-align:right;">BIR Form 2307 / Action</th>
                        </tr>
                    </thead>
                    <tbody id="apPaymentsRegisterBody">
                        @forelse($accountingPayload['vendorPayments'] ?? [] as $pmt)
                            @php
                                $vObj = collect($accountingPayload['vendors'] ?? [])->first(function ($v) use ($pmt) {
                                    return (string) $v->id === (string) $pmt->vendor_id || (string) $v->vendor_code === (string) $pmt->vendor_id;
                                });
                                $vName = $pmt->vendor_name ?? ($vObj ? ($vObj->trade_name ?: $vObj->legal_name) : $pmt->vendor_id);
                                $isPoAdv = ($pmt->status ?? '') === 'PO_ADVANCE';
                                $pmtLines = collect($pmt->lines ?? []);
                                $matchedTags = [];
                                $firstBillId = '';
                                $excessCreditAmt = 0.0;
                                foreach ($pmtLines as $pl) {
                                    if (!empty($pl->is_prepayment) || ($pl->bill_number ?? '') === 'OVERPAYMENT-CREDIT') {
                                        $excessCreditAmt += (float) ($pl->allocated_amount ?? 0);
                                        continue;
                                    }
                                    if (!$firstBillId && !empty($pl->vendor_bill_id)) {
                                        $firstBillId = (string) $pl->vendor_bill_id;
                                    }
                                    $bObj = $apBillByIdMap->get($pl->vendor_bill_id ?? '');
                                    $poObj = $bObj && !empty($bObj->purchase_order_id) ? $apPoByIdMap->get((string) $bObj->purchase_order_id) : null;
                                    $matchedTags[] = $poObj ? $poObj->po_number : ($pl->bill_number ?? 'BILL');
                                }
                                $matchedTags = array_values(array_unique(array_filter($matchedTags)));
                                $disbGl = $apCoaMap->get($pmt->disbursement_account_id ?? '');
                                $bankRefLabel = $pmt->bank_reference ?? $pmt->reference_number ?? $pmt->check_number ?? ($isPoAdv ? 'PO Settlement' : 'N/A');
                                $bankAcctName = $disbGl ? ($disbGl->account_name ?? '') : ($pmt->fund_source ?? '');
                                $cert = $apCertByPmtIdMap->get($pmt->payment_id ?? '') ?? $apCertBySourceIdMap->get($pmt->payment_id ?? '');
                                $certTin = $cert ? ($cert->payee_tin ?: ($cert->party_tin ?: '000-000-000-000')) : '';
                                $certFrom = $cert && $cert->period_from ? \Carbon\Carbon::parse($cert->period_from) : \Carbon\Carbon::parse($pmt->payment_date ?? now());
                                $certQuarter = 'Q' . $certFrom->quarter . ' ' . $certFrom->year;
                                $certIncome = $cert ? (float) ($cert->gross_income_payment ?: $cert->income_payment_amount ?: 0) : 0.0;
                                $certTax = $cert ? (float) ($cert->amount_withheld ?: $cert->tax_withheld_amount ?: 0) : (float) ($pmt->total_ewt_amount ?? 0);
                            @endphp
                            <tr data-pmt-method="{{ $isPoAdv ? 'PO_ADVANCE' : strtoupper((string) $pmt->payment_method) }}"
                                data-has-2307="{{ $cert ? '1' : '0' }}"
                                data-search="{{ strtolower(trim(($pmt->payment_number ?? '') . ' ' . $vName . ' ' . implode(' ', $matchedTags) . ' ' . $bankRefLabel . ' ' . ($cert->certificate_number ?? '') . ' ' . $certTin)) }}">
                                <td class="acct-mono" style="font-weight:700;color:{{ $isPoAdv ? '#0284c7' : '#7e22ce' }};">
                                    {{ $pmt->payment_number }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($pmt->payment_date)->format('Y-m-d') }}</td>
                                <td style="font-weight:600;color:#0f172a;">{{ $vName }}</td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
                                        @forelse($matchedTags as $mTag)
                                            <span class="acct-badge" style="background:#f3e8ff;color:#7e22ce;font-weight:600;">{{ $mTag }}</span>
                                        @empty
                                            <span style="color:#94a3b8;font-size:12px;">Advance / Credit</span>
                                        @endforelse
                                        @if($excessCreditAmt > 0.009)
                                            <span class="acct-badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-weight:700;" title="Excess recorded as Vendor Credit Memo">
                                                +Credit {{ $cur }} {{ number_format($excessCreditAmt, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="acct-badge">{{ $pmt->payment_method }}</span></td>
                                <td>
                                    <div style="font-size:12px;font-weight:600;color:#1e293b;">{{ $bankAcctName ?: 'Treasury Cash / Bank' }}</div>
                                    <div style="font-size:11px;color:#64748b;" class="acct-mono">{{ $bankRefLabel }}</div>
                                </td>
                                <td class="acct-num">{{ number_format((float) $pmt->total_amount, 2) }}</td>
                                <td class="acct-num" style="color:#9333ea;font-weight:600;">{{ number_format((float) $pmt->total_ewt_amount, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;color:#0f172a;">{{ number_format((float) $pmt->total_net_amount, 2) }}</td>
                                <td>
                                    @if($isPoAdv)
                                        <span class="acct-badge" style="background:#e0f2fe;color:#0369a1;font-weight:600;">PO ADVANCE</span>
                                    @else
                                        <span class="acct-badge acct-badge-posted">{{ $pmt->status }}</span>
                                    @endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    @if($cert)
                                        <button
                                            type="button"
                                            class="acct-row-btn btnApView2307"
                                            style="border-color:#d8b4fe;background:#faf5ff;color:#7e22ce;"
                                            data-cert-no="{{ $cert->certificate_number }}"
                                            data-payee="{{ $cert->party_name ?? $vName }}"
                                            data-tin="{{ $certTin }}"
                                            data-atc="{{ $cert->atc_code }}"
                                            data-quarter="{{ $certQuarter }}"
                                            data-period="{{ ($cert->period_from ? \Carbon\Carbon::parse($cert->period_from)->format('M d, Y') : '') . ' – ' . ($cert->period_to ? \Carbon\Carbon::parse($cert->period_to)->format('M d, Y') : '') }}"
                                            data-income="{{ number_format($certIncome, 2) }}"
                                            data-tax="{{ number_format($certTax, 2) }}"
                                            data-voucher="{{ $cert->source_document_number }}"
                                            data-signed="{{ $cert->signed_by ?? 'Disbursement Officer' }}"
                                            title="{{ $cert->certificate_number }} ({{ $cert->atc_code }})"
                                        >
                                            <i class="ph ph-certificate"></i> View 2307
                                        </button>
                                    @elseif(!$isPoAdv)
                                        <button
                                            type="button"
                                            class="acct-row-btn btnApSeparate2307"
                                            data-vendor-id="{{ $vObj ? $vObj->id : $pmt->vendor_id }}"
                                            data-bill-id="{{ $firstBillId }}"
                                            title="Record a separate BIR Form 2307 EWT voucher for this vendor/bill"
                                        >
                                            <i class="ph ph-plus-circle"></i> Record 2307
                                        </button>
                                    @else
                                        <span style="color:#94a3b8;font-size:11.5px;">Synced PO Adv</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="ap-pmt-empty-row">
                                <td colspan="11" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No vendor payments disbursed yet. Click <strong>+ New Payment / Settle PO</strong> to post your first payment voucher.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: VENDOR CREDIT / DEBIT MEMOS -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'memos' ? ' is-active' : '' }}" data-acct-pane="memos">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-arrows-left-right"></i> Vendor Credit &amp; Debit Memos</h2>
                </div>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnApNewMemo">
                    <i class="ph ph-plus"></i> New Vendor Memo
                </button>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="apMemosRegisterTable">
                    <thead>
                        <tr>
                            <th>Memo #</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Target Bill</th>
                            <th>Reason</th>
                            <th class="acct-num">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="apMemosRegisterBody">
                        @forelse($accountingPayload['vendorMemos'] ?? [] as $memo)
                            @php
                                $memoVendor = collect($accountingPayload['vendors'] ?? [])->first(fn($v) => (string)$v->id === (string)$memo->vendor_id || (string)$v->vendor_code === (string)$memo->vendor_id);
                                $memoVendorName = $memo->vendor_name ?: ($memoVendor ? ($memoVendor->trade_name ?: $memoVendor->legal_name) : $memo->vendor_id);
                            @endphp
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $memo->memo_number }}</td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ strtolower($memo->memo_type) }}">
                                        {{ $memo->memo_type }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($memo->memo_date)->format('Y-m-d') }}</td>
                                <td>{{ $memoVendorName }}</td>
                                <td>{{ $memo->vendor_bill_number ?: ($memo->vendor_bill_id ?: 'General') }}</td>
                                <td>{{ $memo->reason ?: 'Goods Return / Allowance' }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format((float) $memo->amount, 2) }} <span style="font-size:10.5px;color:#059669;font-weight:600;">({{ number_format((float) $memo->unapplied_amount, 2) }} avail)</span></td>
                                <td><span class="acct-badge acct-badge-posted">{{ $memo->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No vendor debit or credit memos issued yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: VENDOR LEDGER & AGING (20% AGING SCHEDULE : 80% OPEN BALANCES) -->
    <!-- ===================================================================== -->
    @php
        $apAgingBuckets = $accountingPayload['vendorAging']['aging'] ?? [];
        $apAgingTotalOpen = (float) ($accountingPayload['vendorAging']['total_open_payables'] ?? ($apAgingBuckets['total_open'] ?? 0));
        $apAgingTotalCredits = (float) ($accountingPayload['vendorAging']['total_open_credits'] ?? $apAvailVendorCredits);
        $apAgingNetExposure = (float) ($accountingPayload['vendorAging']['net_payable_due'] ?? ($apAgingTotalOpen - $apAgingTotalCredits));
    @endphp
    <div class="acct-tab-pane{{ $initialTab === 'ledger' ? ' is-active' : '' }}" data-acct-pane="ledger">
        <div class="acct-ledger-split-20-80" id="apLedgerSplitContainer">
            <!-- LEFT 20% CONTAINER: VENDOR PAYABLES AGING SCHEDULE -->
            <div class="acct-panel" id="apAgingSchedulePanel">
                <div class="acct-panel-head" style="margin-bottom:10px;">
                    <h2 class="acct-panel-title" style="font-size:14px;"><i class="ph ph-clock-counter-clockwise"></i> Payables Aging Schedule</h2>
                </div>
                <div class="ap-aging-stack-list">
                    <div class="ap-aging-bucket-card">
                        <span class="ap-aging-bucket-label">Current (Not Yet Due)</span>
                        <span class="ap-aging-bucket-val">{{ $cur }} {{ number_format((float) ($apAgingBuckets['current'] ?? 0), 2) }}</span>
                    </div>
                    <div class="ap-aging-bucket-card">
                        <span class="ap-aging-bucket-label">1 - 30 Days Past Due</span>
                        <span class="ap-aging-bucket-val">{{ $cur }} {{ number_format((float) ($apAgingBuckets['days_1_30'] ?? 0), 2) }}</span>
                    </div>
                    <div class="ap-aging-bucket-card">
                        <span class="ap-aging-bucket-label">31 - 60 Days Past Due</span>
                        <span class="ap-aging-bucket-val">{{ $cur }} {{ number_format((float) ($apAgingBuckets['days_31_60'] ?? 0), 2) }}</span>
                    </div>
                    <div class="ap-aging-bucket-card">
                        <span class="ap-aging-bucket-label">61 - 90 Days Past Due</span>
                        <span class="ap-aging-bucket-val">{{ $cur }} {{ number_format((float) ($apAgingBuckets['days_61_90'] ?? 0), 2) }}</span>
                    </div>
                    <div class="ap-aging-bucket-card is-danger">
                        <span class="ap-aging-bucket-label" style="color:#b91c1c;">Over 90 Days Past Due</span>
                        <span class="ap-aging-bucket-val" style="color:#dc2626;">{{ $cur }} {{ number_format((float) ($apAgingBuckets['days_over_90'] ?? 0), 2) }}</span>
                    </div>
                </div>
                <div style="margin-top:12px;padding-top:10px;border-top:1px dashed #cbd5e1;display:flex;flex-direction:column;gap:6px;font-size:12px;">
                    <div style="display:flex;justify-content:space-between;color:#475569;">
                        <span>Total Open Payables</span>
                        <strong class="acct-num">{{ $cur }} {{ number_format($apAgingTotalOpen, 2) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;color:#059669;">
                        <span>Less: Unapplied Credits</span>
                        <strong class="acct-num">- {{ $cur }} {{ number_format($apAgingTotalCredits, 2) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding-top:6px;border-top:1px solid #e2e8f0;font-size:13px;color:#0f172a;">
                        <strong>Net Payable Exposure</strong>
                        <strong class="acct-num" style="color:#7e22ce;">{{ $cur }} {{ number_format($apAgingNetExposure, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- RIGHT 80% CONTAINER: VENDOR OPEN BALANCES (CUSTOMIZABLE RFQ TABLE + > EXPAND PENDING POs) -->
            <div class="acct-panel" id="apVendorOpenBalancesPanel">
                <div class="acct-panel-head">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h2 class="acct-panel-title"><i class="ph ph-users"></i> Vendor Open Balances &amp; Pending POs</h2>
                        <span class="acct-badge acct-badge-system">{{ count($accountingPayload['vendors'] ?? []) }} Suppliers</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <input type="text" class="acct-input" id="apLedgerVendorSearch" placeholder="Search vendor, code, or PO #..." style="width:205px;height:34px;">
                        <select class="acct-select" id="apLedgerBalanceFilter" style="width:165px;height:34px;">
                            <option value="">All Suppliers</option>
                            <option value="OPEN">With Open Balance</option>
                            <option value="PENDING_PO">With Pending POs</option>
                            <option value="WITH_CREDIT">With Available Credit</option>
                        </select>
                    </div>
                </div>

                <div class="rfq-table-responsive" style="overflow-x:auto;position:relative;min-height:220px;">
                    <table class="rfq-table acct-table" id="apVendorOpenBalancesTable">
                        <thead id="apVendorBalancesThead">
                            <tr id="apVendorBalancesTheadRow">
                                <th class="rfq-th-stacked th-left" data-col-id="vendor_code" style="width:140px;">Vendor Code</th>
                                <th class="rfq-th-stacked th-left" data-col-id="trade_name" style="width:210px;">Supplier Name</th>
                                <th class="rfq-th-stacked th-left" data-col-id="tin" style="width:130px;">TIN</th>
                                <th class="rfq-th-stacked th-left" data-col-id="payment_terms" style="width:110px;">Terms</th>
                                <th class="rfq-th-stacked th-left" data-col-id="pending_pos" style="width:125px;">Pending POs</th>
                                <th class="rfq-th-stacked th-num" data-col-id="open_bills" style="width:95px;text-align:right;">Open Bills</th>
                                <th class="rfq-th-stacked th-num" data-col-id="credit_avail" style="width:130px;text-align:right;">Vendor Credit</th>
                                <th class="rfq-th-stacked th-num" data-col-id="outstanding_balance" style="width:155px;text-align:right;">Outstanding Balance</th>
                                <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width:130px;text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="apVendorBalancesBody">
                            @forelse($accountingPayload['vendors'] ?? [] as $v)
                                @php
                                    $vKeys = array_filter([(string) $v->id, (string) ($v->vendor_code ?? '')]);
                                    $vOpenBills = collect($accountingPayload['vendorBills'] ?? [])->filter(function ($b) use ($vKeys) {
                                        return in_array((string) $b->vendor_id, $vKeys, true)
                                            && in_array(strtoupper((string) $b->vendor_bill_status), ['POSTED', 'PARTIAL', 'APPROVED'], true);
                                    });
                                    $vBalance = $vOpenBills->sum(fn($b) => max(0, (float) $b->bill_total_amount - (float) $b->bill_paid_amount));
                                    $vCredits = collect($accountingPayload['vendorMemos'] ?? [])->filter(function ($m) use ($vKeys) {
                                        return in_array((string) $m->vendor_id, $vKeys, true)
                                            && in_array(strtoupper((string) ($m->memo_type ?? '')), ['CREDIT', 'CREDIT_MEMO'], true)
                                            && strtoupper((string) ($m->status ?? '')) === 'POSTED'
                                            && (float) ($m->unapplied_amount ?? 0) > 0;
                                    })->sum(fn($m) => (float) $m->unapplied_amount);

                                    $vNameDisplay = $v->trade_name ?: $v->legal_name;
                                    $vPendingPos = collect($accountingPayload['purchaseOrders'] ?? [])->filter(function ($po) use ($vKeys, $vNameDisplay, $v) {
                                        $poVendorMatches = in_array((string) ($po->resolved_vendor_id ?? ''), $vKeys, true)
                                            || in_array((string) ($po->vendor_id ?? ''), $vKeys, true)
                                            || ($vNameDisplay && strcasecmp((string) ($po->vendor_name ?? ''), (string) $vNameDisplay) === 0)
                                            || (!empty($v->legal_name) && strcasecmp((string) ($po->vendor_name ?? ''), (string) $v->legal_name) === 0);
                                        $poBal = max(0, (float) ($po->gross_total ?? 0) - (float) ($po->amount_paid ?? 0));
                                        return $poVendorMatches && $poBal > 0.01;
                                    })->values();

                                    $poNumbersSearch = $vPendingPos->pluck('po_number')->implode(' ');
                                @endphp
                                <tr class="ap-vendor-balance-row"
                                    data-vendor-id="{{ $v->id }}"
                                    data-has-open="{{ $vBalance > 0.01 ? '1' : '0' }}"
                                    data-has-pending-po="{{ $vPendingPos->isNotEmpty() ? '1' : '0' }}"
                                    data-has-credit="{{ $vCredits > 0.01 ? '1' : '0' }}"
                                    data-search="{{ strtolower(trim(($v->vendor_code ?? '') . ' ' . $vNameDisplay . ' ' . ($v->tax_id ?? '') . ' ' . $poNumbersSearch)) }}">
                                    <td data-col-id="vendor_code">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <button type="button" class="ap-expand-po-btn btnApExpandVendorPos" data-vendor-id="{{ $v->id }}" title="Expand pending Purchase Orders &amp; bills">
                                                <i class="ph ph-caret-right"></i>
                                            </button>
                                            <span class="acct-mono" style="font-weight:700;color:#7e22ce;">{{ $v->vendor_code }}</span>
                                        </div>
                                    </td>
                                    <td data-col-id="trade_name" style="font-weight:600;color:#0f172a;">{{ $vNameDisplay }}</td>
                                    <td data-col-id="tin" class="acct-mono" style="font-size:12px;">{{ $v->tax_id ?: 'N/A' }}</td>
                                    <td data-col-id="payment_terms"><span class="acct-badge">{{ $v->payment_terms ?: 'NET30' }}</span></td>
                                    <td data-col-id="pending_pos">
                                        @if($vPendingPos->isNotEmpty())
                                            <button type="button" class="acct-badge btnApExpandVendorPos" data-vendor-id="{{ $v->id }}" style="background:#f3e8ff;color:#7e22ce;font-weight:700;border:1px solid #e9d5ff;cursor:pointer;">
                                                {{ $vPendingPos->count() }} Pending PO{{ $vPendingPos->count() === 1 ? '' : 's' }}
                                            </button>
                                        @else
                                            <span style="color:#94a3b8;font-size:12px;">None</span>
                                        @endif
                                    </td>
                                    <td data-col-id="open_bills" class="acct-num">{{ $vOpenBills->count() }}</td>
                                    <td data-col-id="credit_avail" class="acct-num" style="color:{{ $vCredits > 0 ? '#059669' : '#94a3b8' }};font-weight:{{ $vCredits > 0 ? '700' : '400' }};">
                                        {{ number_format($vCredits, 2) }}
                                    </td>
                                    <td data-col-id="outstanding_balance" class="acct-num" style="font-weight:700;color:{{ $vBalance > 0 ? '#ef4444' : '#10b981' }};">
                                        {{ number_format($vBalance, 2) }}
                                    </td>
                                    <td data-col-id="actions" style="text-align:right;overflow:visible;">
                                        <button type="button" class="acct-row-btn btnApPayVendorFromLedger" data-vendor-id="{{ $v->id }}" title="Open Payment Drawer for {{ $vNameDisplay }}">
                                            <i class="ph ph-credit-card"></i> Settle
                                        </button>
                                    </td>
                                </tr>
                                <tr class="ap-vendor-pos-detail-row" data-parent-vendor-id="{{ $v->id }}" style="display:none;">
                                    <td colspan="9">
                                        <div class="ap-vendor-pos-subbox">
                                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                                <div style="font-size:12px;font-weight:700;color:#581c87;display:flex;align-items:center;gap:6px;">
                                                    <i class="ph ph-shopping-cart"></i>
                                                    <span>Pending Purchase Orders &amp; Unsettled Obligations — {{ $vNameDisplay }}</span>
                                                </div>
                                                <button type="button" class="acct-row-btn btnApPayVendorFromLedger" data-vendor-id="{{ $v->id }}">
                                                    <i class="ph ph-plus-circle"></i> Pay All / Match POs
                                                </button>
                                            </div>
                                            @if($vPendingPos->isNotEmpty() || $vOpenBills->isNotEmpty())
                                                <table class="ap-vendor-pos-subtable">
                                                    <thead>
                                                        <tr>
                                                            <th>PO / Document #</th>
                                                            <th>Order / Bill Date</th>
                                                            <th>Status</th>
                                                            <th>Linked AP Bill #</th>
                                                            <th class="acct-num">Total Amount</th>
                                                            <th class="acct-num">Paid / Credit</th>
                                                            <th class="acct-num">Balance Due</th>
                                                            <th style="text-align:right;">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($vPendingPos as $po)
                                                            @php
                                                                $poGross = (float) ($po->gross_total ?? 0);
                                                                $poPaid = (float) ($po->amount_paid ?? 0);
                                                                $poBal = max(0, $poGross - $poPaid);
                                                                $linkedBill = collect($accountingPayload['vendorBills'] ?? [])->first(fn($b) => (string) $b->purchase_order_id === (string) $po->id || (string) $b->purchase_order_id === (string) $po->po_number);
                                                            @endphp
                                                            <tr>
                                                                <td class="acct-mono" style="font-weight:700;color:#7e22ce;">{{ $po->po_number }}</td>
                                                                <td>{{ $po->order_date ? \Carbon\Carbon::parse($po->order_date)->format('Y-m-d') : '--' }}</td>
                                                                <td><span class="acct-badge">{{ $po->status ?? 'Approved / Issued' }}</span></td>
                                                                <td class="acct-mono" style="font-size:11.5px;color:#475569;">{{ $linkedBill ? $linkedBill->vendor_bill_number : 'Auto-Sync Ready' }}</td>
                                                                <td class="acct-num">{{ $cur }} {{ number_format($poGross, 2) }}</td>
                                                                <td class="acct-num" style="color:#0284c7;">{{ $cur }} {{ number_format($poPaid, 2) }}</td>
                                                                <td class="acct-num" style="font-weight:700;color:#ef4444;">{{ $cur }} {{ number_format($poBal, 2) }}</td>
                                                                <td style="text-align:right;">
                                                                    <button type="button"
                                                                        class="acct-row-btn btnApPayBill"
                                                                        data-bill-id="{{ $linkedBill ? $linkedBill->vendor_bill_id : '' }}"
                                                                        data-vendor-id="{{ $v->id }}"
                                                                        data-po-id="{{ $po->id }}"
                                                                        style="border-color:#a7f3d0;background:#ecfdf5;color:#047857;">
                                                                        <i class="ph ph-credit-card"></i> Pay PO
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        @foreach($vOpenBills->filter(fn($b) => empty($b->purchase_order_id)) as $directBill)
                                                            @php $dbBal = max(0, (float) $directBill->bill_total_amount - (float) $directBill->bill_paid_amount); @endphp
                                                            <tr>
                                                                <td class="acct-mono" style="font-weight:700;color:#0f172a;">{{ $directBill->vendor_bill_number }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($directBill->vendor_invoice_date)->format('Y-m-d') }}</td>
                                                                <td><span class="acct-badge">{{ $directBill->vendor_bill_status }}</span></td>
                                                                <td class="acct-mono" style="font-size:11.5px;color:#475569;">{{ $directBill->vendor_invoice_number }}</td>
                                                                <td class="acct-num">{{ $cur }} {{ number_format((float) $directBill->bill_total_amount, 2) }}</td>
                                                                <td class="acct-num" style="color:#0284c7;">{{ $cur }} {{ number_format((float) $directBill->bill_paid_amount, 2) }}</td>
                                                                <td class="acct-num" style="font-weight:700;color:#ef4444;">{{ $cur }} {{ number_format($dbBal, 2) }}</td>
                                                                <td style="text-align:right;">
                                                                    <button type="button"
                                                                        class="acct-row-btn btnApPayBill"
                                                                        data-bill-id="{{ $directBill->vendor_bill_id }}"
                                                                        data-vendor-id="{{ $v->id }}"
                                                                        style="border-color:#a7f3d0;background:#ecfdf5;color:#047857;">
                                                                        <i class="ph ph-credit-card"></i> Pay Bill
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div style="padding:12px;background:#fff;border:1px dashed #cbd5e1;border-radius:8px;color:#64748b;font-size:12px;text-align:center;">
                                                    All Purchase Orders and Vendor Bills for {{ $vNameDisplay }} are settled.
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="ap-vendor-empty-row">
                                    <td colspan="9" style="text-align:center;padding:20px;color:#94a3b8;">No vendors available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- RFQ-Style Floating Table Columns & Width Settings Dropdown for Vendor Open Balances -->
                    <div id="apVendorBalancesTableSettingsDropdown" class="rfq-table-settings-dropdown" style="display:none;">
                        <div class="rfq-settings-dropdown-header">
                            <div class="rfq-settings-title-row" style="display:flex;align-items:center;justify-content:space-between;">
                                <div class="rfq-settings-title">
                                    <i class="ph ph-gear-six"></i>
                                    <span>Table Columns &amp; Sequence</span>
                                </div>
                                <span class="rfq-col-badge-default" id="apVendorBalancesColActiveCounter">9 of 9 visible</span>
                            </div>
                            <div class="rfq-settings-links-row">
                                <button type="button" class="rfq-settings-link-btn" id="btnApVendorBalancesResetDefaults">Reset to Default</button>
                                <button type="button" class="rfq-settings-link-btn" id="btnApVendorBalancesShowAll">Select All</button>
                                <button type="button" class="rfq-settings-link-btn" id="btnApVendorBalancesResetWidths">Reset Widths</button>
                            </div>
                            <input type="text" class="rfq-settings-search-input" id="apVendorBalancesColSearchInput" placeholder="Filter columns...">
                        </div>
                        <div class="rfq-settings-col-list" id="apVendorBalancesColCheckboxesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PAYMENT VOUCHER & PO MATCHING DRAWER (CANONICAL RMS DRAWER) -->
<!-- ========================================================================= -->
<div class="rms-drawer-backdrop" id="apPaymentDrawerBackdrop"></div>
<aside class="rms-drawer-panel" id="apPaymentDrawerPanel" style="width: min(1040px, 96vw);" role="dialog" aria-modal="true" aria-labelledby="apPaymentDrawerTitle" aria-hidden="true">
    <div class="rms-drawer-header">
        <div class="rms-drawer-head-left">
            <div class="rms-drawer-icon">
                <i class="ph ph-credit-card"></i>
            </div>
            <div style="min-width:0;">
                <div class="rms-drawer-title-row">
                    <h2 class="rms-drawer-title" id="apPaymentDrawerTitle">Vendor Payment Voucher &amp; PO Settlement</h2>
                    <span class="acct-badge acct-badge-system">PO MATCHING</span>
                    <span class="acct-badge" style="background:#f3e8ff;color:#7e22ce;font-weight:700;">BIR 2307 EWT</span>
                    <span class="acct-badge" style="background:#ecfdf5;color:#047857;font-weight:700;">EXCESS &rarr; CREDIT</span>
                </div>
                <p class="rms-drawer-sub">Match disbursements against approved Purchase Orders &amp; Vendor Bills — any overpayment automatically records as Vendor Credit.</p>
            </div>
        </div>
        <div class="rms-drawer-head-actions">
            <button type="button" class="rms-drawer-close" id="btnApClosePaymentModal" aria-label="Close Payment Voucher Drawer">
                <i class="ph ph-x"></i>
            </button>
        </div>
    </div>

    <div class="rms-drawer-body">
        <!-- Live 4-KPI Settlement Strip -->
        <div class="acct-kpi-grid-4" style="margin-bottom:14px;">
            <div class="rfq-kpi-card" style="padding:12px 14px;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Total Payment Input</span><span class="rfq-kpi-icon"><i class="ph ph-coins"></i></span></div>
                <div class="rfq-kpi-val acct-num" style="text-align:left;font-size:18px;" id="apPmtKpiGross">{{ $cur }} 0.00</div>
                <div class="rfq-kpi-sub">Gross voucher amount</div>
            </div>
            <div class="rfq-kpi-card" style="padding:12px 14px;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Matched to PO / Bills</span><span class="rfq-kpi-icon awarded"><i class="ph ph-link-simple"></i></span></div>
                <div class="rfq-kpi-val acct-num" style="text-align:left;font-size:18px;color:#0284c7;" id="apPmtKpiMatched">{{ $cur }} 0.00</div>
                <div class="rfq-kpi-sub">Applied against open balances</div>
            </div>
            <div class="rfq-kpi-card" style="padding:12px 14px;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">EWT Withheld (2307)</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-percent"></i></span></div>
                <div class="rfq-kpi-val acct-num" style="text-align:left;font-size:18px;color:#9333ea;" id="apPmtKpiEwt">{{ $cur }} 0.00</div>
                <div class="rfq-kpi-sub">Auto-issues BIR 2307</div>
            </div>
            <div class="rfq-kpi-card" style="padding:12px 14px;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Net Cash Outflow</span><span class="rfq-kpi-icon draft"><i class="ph ph-bank"></i></span></div>
                <div class="rfq-kpi-val acct-num" style="text-align:left;font-size:18px;color:#0f172a;" id="apPmtNetDisplay">{{ $cur }} 0.00</div>
                <div class="rfq-kpi-sub" id="apPmtKpiExcessSub" style="color:#059669;font-weight:600;">Excess Credit: {{ $cur }} 0.00</div>
            </div>
        </div>

        <!-- Card 1: Quick PO Match, Vendor & Disbursement Account -->
        <div class="rms-drawer-card">
            <div class="rms-drawer-card-head">
                <div class="rms-drawer-card-title">
                    <i class="ph ph-buildings"></i>
                    <span>Payee, Quick PO Match &amp; Disbursing Account</span>
                </div>
                <span class="rms-drawer-card-badge">Disbursement Header</span>
            </div>
            <div class="rms-drawer-card-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtSettlementMode">Settlement Mode <span style="color:#ef4444;">*</span></label>
                        <select class="acct-select" id="apPmtSettlementMode" style="font-weight:600;color:#7e22ce;">
                            <option value="COMBINED">Combined Bill + EWT 2307 (Standard)</option>
                            <option value="BILL_ONLY">Bill Principal Only (Separate 2307 Later)</option>
                            <option value="EWT_2307_ONLY">Separate BIR 2307 / EWT Settlement Only</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtPoQuickSelect">Quick Match Purchase Order</label>
                        <select class="acct-select" id="apPmtPoQuickSelect">
                            <option value="">-- Select PO to Auto-Match --</option>
                            @foreach($accountingPayload['purchaseOrders'] ?? [] as $po)
                                @php
                                    $poBal = max(0, (float) ($po->gross_total ?? 0) - (float) ($po->amount_paid ?? 0));
                                @endphp
                                <option value="{{ $po->id }}" data-po-no="{{ $po->po_number }}" data-vendor-id="{{ $po->resolved_vendor_id ?? $po->vendor_id }}" data-balance="{{ number_format($poBal, 2, '.', '') }}">
                                    {{ $po->po_number }} — {{ $po->vendor_name }} (Bal: {{ $cur }} {{ number_format($poBal, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtVendor">Vendor / Payee <span style="color:#ef4444;">*</span></label>
                        <select class="acct-select" id="apPmtVendor">
                            <option value="">-- Select Vendor --</option>
                            @foreach($accountingPayload['vendors'] ?? [] as $v)
                                <option value="{{ $v->id }}" data-code="{{ $v->vendor_code }}">{{ $v->trade_name ?: $v->legal_name }} ({{ $v->vendor_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtDate">Payment Date <span style="color:#ef4444;">*</span></label>
                        <input type="date" class="acct-input" id="apPmtDate" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtMethod">Payment Method <span style="color:#ef4444;">*</span></label>
                        <select class="acct-select" id="apPmtMethod">
                            <option value="BANK_TRANSFER">Bank Transfer / Online</option>
                            <option value="CHECK">Company Check (Immediate)</option>
                            <option value="PDC">Post-Dated Check (PDC)</option>
                            <option value="CASH">Cash Voucher / Petty Cash</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtBankAccount">Disbursing Bank / GL Account <span style="color:#ef4444;">*</span></label>
                        <select class="acct-select" id="apPmtBankAccount">
                            @if(!empty($accountingPayload['bankAccounts']) && count($accountingPayload['bankAccounts']) > 0)
                                <optgroup label="Registered Bank Accounts">
                                    @foreach($accountingPayload['bankAccounts'] as $ba)
                                        <option value="{{ $ba->bank_account_id }}">{{ $ba->account_name }} ({{ $ba->bank_name }} - {{ $ba->account_number }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="GL Cash &amp; Bank Control Accounts">
                                @foreach(collect($accountingPayload['accounts'] ?? [])->whereIn('system_role', ['CASH_IN_BANK', 'CASH_ON_HAND', 'PETTY_CASH', 'PDC_PAYABLE']) as $glCash)
                                    <option value="{{ $glCash['account_id'] }}">{{ $glCash['account_code'] }} — {{ $glCash['account_name'] }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtReference">Check # / Bank Transfer Ref #</label>
                        <input type="text" class="acct-input" id="apPmtReference" placeholder="e.g. CHK-00412 or TRF-99881">
                    </div>
                    <div class="acct-field" id="apPmtPdcBlock" style="display:none;">
                        <label class="acct-label" for="apPmtPdcDate">PDC Maturity Date <span style="color:#ef4444;">*</span></label>
                        <input type="date" class="acct-input" id="apPmtPdcDate" value="{{ now()->addDays(30)->toDateString() }}">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="apPmtTotalInput">Total Payment / Voucher Amount ({{ $cur }}) <span style="color:#ef4444;">*</span></label>
                        <input type="number" step="0.01" min="0" class="acct-input acct-num" id="apPmtTotalInput" value="0.00" style="font-weight:700;color:#0f172a;">
                    </div>
                    <div class="acct-field" style="grid-column: span 3;">
                        <label class="acct-label" for="apPmtNotes">Payment Particulars &amp; Quick Presets</label>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="text" class="acct-input" id="apPmtNotes" placeholder="Optional remarks, check memo, or 2307 remittance ref..." style="flex:1;min-width:180px;">
                            <button type="button" class="rfq-btn rfq-btn-outline" id="btnApPmtPresetMatch" style="height:34px;padding:0 10px;font-size:11.5px;">Match Selected POs</button>
                            <button type="button" class="rfq-btn rfq-btn-outline" id="btnApPmtPresetHalf" style="height:34px;padding:0 10px;font-size:11.5px;">50% Partial</button>
                        </div>
                    </div>
                </div>

                <!-- Available Vendor AP Credit Memo Banner -->
                <div id="apPmtVendorCreditBanner" style="display:none;margin-top:12px;padding:10px 14px;border-radius:8px;background:#f0fdf4;border:1px solid #86efac;align-items:center;justify-content:space-between;gap:10px;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#065f46;">
                        <i class="ph ph-wallet" style="font-size:18px;color:#059669;"></i>
                        <span>Vendor has available AP Credit Memos totaling <strong id="apPmtAvailCreditVal">{{ $cur }} 0.00</strong> (can be applied via PO Settlement or Credit Memo tab).</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: PO & Vendor Bill Payment Matching Matrix -->
        <div class="rms-drawer-card">
            <div class="rms-drawer-card-head">
                <div class="rms-drawer-card-title">
                    <i class="ph ph-list-checks"></i>
                    <span>Purchase Order &amp; Bill Payment Matching</span>
                </div>
                <span class="acct-badge acct-badge-system" id="apPmtMatchedCountBadge">Select vendor to load open POs</span>
            </div>
            <div class="rms-drawer-card-body">
                <div class="acct-lines-table-wrap" style="overflow-x:auto;">
                    <table class="acct-table acct-lines-table" id="apPmtAllocationsTable">
                        <thead>
                            <tr>
                                <th style="width:40px;text-align:center;">Match</th>
                                <th>PO # / Bill #</th>
                                <th>Invoice Ref #</th>
                                <th class="acct-num">Total Amount</th>
                                <th class="acct-num">Open Balance</th>
                                <th class="acct-num" style="min-width:125px;">Pay Allocation</th>
                                <th style="min-width:145px;">EWT Rule / ATC</th>
                                <th class="acct-num" style="min-width:105px;">EWT (2307)</th>
                                <th class="acct-num" style="min-width:110px;">Net Outflow</th>
                            </tr>
                        </thead>
                        <tbody id="apPmtAllocationsBody">
                            <tr>
                                <td colspan="9" style="text-align:center;padding:18px;color:#94a3b8;">
                                    Select a <strong>Quick Match PO</strong> or <strong>Vendor</strong> above to load payable obligations.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Live Overpayment -> Auto Credit Memo Banner -->
                <div id="apPmtExcessBanner" style="display:none;margin-top:12px;padding:12px 14px;border-radius:8px;background:#ecfdf5;border:1px solid #6ee7b7;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:10px;color:#065f46;font-size:12.5px;">
                        <i class="ph ph-sparkle" style="font-size:20px;color:#059669;"></i>
                        <div>
                            <div style="font-weight:700;">Automatic Overpayment &rarr; Vendor Credit Memo Detected</div>
                            <div>Excess payment of <strong id="apPmtExcessCreditDisplay" class="acct-mono">{{ $cur }} 0.00</strong> above open PO/Bill balances will automatically be recorded as a posted <strong>Vendor Credit Balance</strong>.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rms-drawer-footer">
        <div class="rms-drawer-footer-meta">
            <label style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:#334155;font-weight:500;">
                <input type="checkbox" id="apPmtIssue2307" checked> Auto-issue BIR Form 2307 Certificate when EWT &gt; 0
            </label>
        </div>
        <div class="rms-drawer-footer-actions">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnApCancelPayment">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnApSubmitPayment">
                <i class="ph ph-check-circle"></i> Post Payment Voucher &amp; Settle
            </button>
        </div>
    </div>
</aside>

<!-- ========================================================================= -->
<!-- BIR FORM 2307 CERTIFICATE PREVIEW MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="ap2307PreviewModal" style="display:none;">
    <div class="acct-modal" style="max-width:640px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-certificate"></i> BIR Form No. 2307 — Certificate of Creditable Tax Withheld</h3>
            <button type="button" class="acct-modal-close" id="btnApClose2307Modal">&times;</button>
        </div>
        <div class="acct-modal-body" id="ap2307PrintableArea">
            <div style="border:1.5px solid #cbd5e1;border-radius:10px;padding:18px;background:#fff;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #e2e8f0;padding-bottom:12px;margin-bottom:14px;">
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Republic of the Philippines • Bureau of Internal Revenue</div>
                        <div style="font-size:17px;font-weight:800;color:#0f172a;margin-top:2px;">BIR FORM NO. 2307 Summary</div>
                        <div style="font-size:12px;color:#475569;" id="prev2307Period">Period Covered: --</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="acct-mono" style="font-size:14px;font-weight:800;color:#7e22ce;" id="prev2307CertNo">2307-PV-0001</div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">Source: <span id="prev2307Voucher">--</span></div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;font-size:12.5px;">
                    <div style="background:#f8fafc;padding:10px;border-radius:8px;border:1px solid #e2e8f0;">
                        <div style="font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;">Payee / Supplier</div>
                        <div style="font-weight:700;color:#0f172a;margin-top:3px;" id="prev2307Payee">--</div>
                        <div style="font-size:12px;color:#475569;margin-top:2px;">TIN: <span class="acct-mono" id="prev2307Tin">--</span></div>
                    </div>
                    <div style="background:#f8fafc;padding:10px;border-radius:8px;border:1px solid #e2e8f0;">
                        <div style="font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;">Tax Classification</div>
                        <div style="font-weight:700;color:#0f172a;margin-top:3px;">ATC: <span id="prev2307Atc">WC158</span> (<span id="prev2307Quarter">--</span>)</div>
                        <div style="font-size:12px;color:#475569;margin-top:2px;">Authorized Signatory: <span id="prev2307Signed">--</span></div>
                    </div>
                </div>
                <table class="acct-table" style="margin-top:6px;">
                    <thead>
                        <tr>
                            <th>ATC</th>
                            <th>Quarter Covered</th>
                            <th class="acct-num">Income Payment Base</th>
                            <th class="acct-num">Tax Withheld (EWT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="acct-mono" style="font-weight:700;" id="prev2307TableAtc">WC158</td>
                            <td id="prev2307TableQuarter">--</td>
                            <td class="acct-num" style="font-weight:600;" id="prev2307Income">{{ $cur }} 0.00</td>
                            <td class="acct-num" style="font-weight:800;color:#9333ea;" id="prev2307Tax">{{ $cur }} 0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnApClose2307Btn">Close</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnApPrint2307" onclick="window.print()">
                <i class="ph ph-printer"></i> Print 2307 Certificate
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- VENDOR MEMO MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="apMemoModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:550px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-arrows-left-right"></i> Issue Vendor Credit / Debit Memo</h3>
            <button type="button" class="acct-modal-close" id="btnApCloseMemoModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="apMemoVendor">Vendor <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="apMemoVendor">
                        <option value="">-- Select Vendor --</option>
                        @foreach($accountingPayload['vendors'] ?? [] as $v)
                            <option value="{{ $v->id }}">{{ $v->trade_name ?: $v->legal_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="apMemoType">Memo Type <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="apMemoType">
                        <option value="CREDIT_MEMO">Vendor Credit Memo (Reduces Payable)</option>
                        <option value="DEBIT_MEMO">Vendor Debit Memo (Increases Payable)</option>
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="apMemoDate">Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="apMemoDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="apMemoAmount">Amount ({{ $cur }}) <span style="color:#ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="acct-input acct-num" id="apMemoAmount" value="0.00">
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="apMemoTargetBill">Target Vendor Bill (Optional)</label>
                    <select class="acct-select" id="apMemoTargetBill">
                        <option value="">-- No specific bill (General AP balance) --</option>
                        @foreach($accountingPayload['vendorBills'] ?? [] as $b)
                            <option value="{{ $b->vendor_bill_id }}">{{ $b->vendor_bill_number }} - {{ $b->vendor_invoice_number }} (Bal: {{ number_format((float) $b->bill_total_amount - (float) $b->bill_paid_amount, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="apMemoReason">Reason / Description <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="apMemoReason" placeholder="e.g. Return of damaged items / price correction">
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnApCancelMemo">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnApSubmitMemo">
                <i class="ph ph-check"></i> Post Memo
            </button>
        </div>
    </div>
</div>
@endsection
