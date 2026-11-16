@extends('layouts.app')

@section('title', 'Accounts Receivable')

@section('content')
@php
    $initialTab = request()->query('tab', 'invoices');
    $validTabs = ['invoices', 'receipts', 'memos', 'dunning', 'ledger'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'invoices';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
@endphp

<div class="acct-workspace" id="accountingArRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Accounts Receivable',
        'pageSubtitle' => 'Customer invoicing, credit limits, collection receipts (with CWT 2307 deductions) and DAIF bounced checks',
        'pageIcon' => 'ph-hand-coins',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- AR KPI Summary Cards -->
    <div class="acct-kpi-grid-4">
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">AR Control Balance</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-hand-coins"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format((float) ($accountingPayload['kpis']['receivables'] ?? 0), 2) }}</div>
            <div class="rfq-kpi-sub">Total open customer receivables</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Active Customers</span><span class="rfq-kpi-icon awarded"><i class="ph ph-users"></i></span></div>
            <div class="rfq-kpi-val">{{ count($accountingPayload['customers'] ?? []) }}</div>
            <div class="rfq-kpi-sub">Customers registered with credit terms</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Output VAT Rules</span><span class="rfq-kpi-icon"><i class="ph ph-percent"></i></span></div>
            <div class="rfq-kpi-val">{{ count($accountingPayload['taxCodes'] ?? []) }}</div>
            <div class="rfq-kpi-sub">12% Standard, Zero-Rated, Exempt</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">PDC Maturities</span><span class="rfq-kpi-icon draft"><i class="ph ph-calendar-check"></i></span></div>
            <div class="rfq-kpi-val">{{ (int) ($accountingPayload['kpis']['upcomingPdcMaturities'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Post-dated checks in monitoring</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'invoices' ? ' active' : '' }}" data-acct-tab="invoices">
            <i class="ph ph-file-text"></i> <span>Sales Invoices</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'receipts' ? ' active' : '' }}" data-acct-tab="receipts">
            <i class="ph ph-receipt"></i> <span>Collection Receipts &amp; 2307</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'memos' ? ' active' : '' }}" data-acct-tab="memos">
            <i class="ph ph-arrows-left-right"></i> <span>Customer Credit / Debit Memos</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'dunning' ? ' active' : '' }}" data-acct-tab="dunning">
            <i class="ph ph-shield-warning"></i> <span>DAIF Bounced Checks &amp; Dunning</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'ledger' ? ' active' : '' }}" data-acct-tab="ledger">
            <i class="ph ph-list-numbers"></i> <span>Customer Ledger &amp; Aging</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: SALES INVOICES -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'invoices' ? ' is-active' : '' }}" data-acct-pane="invoices">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-file-text"></i> Sales Invoice Workspace</h2>
                    <span class="acct-badge acct-badge-draft" id="arInvoiceStatusBadge">NEW DRAFT</span>
                    <span class="acct-mono" id="arInvoiceNumberDisplay" style="color:#9333ea;">AUTO-NUMBERED</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnArNewCustomer">
                        <i class="ph ph-user-plus"></i> New Customer
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnArNewInvoice">
                        <i class="ph ph-plus"></i> New Invoice Draft
                    </button>
                </div>
            </div>

            <div class="acct-panel-body">
                <input type="hidden" id="arInvoiceId" value="">

                <!-- Credit Limit Alert Banner (Pessimistic Safeguard) -->
                <div id="arCreditLimitAlert" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:12.5px;align-items:center;gap:8px;">
                    <i class="ph ph-warning-circle" style="font-size:16px;"></i>
                    <span id="arCreditLimitText">Warning: Customer is approaching or exceeding credit limit.</span>
                </div>

                <!-- 4-Column Header -->
                <div class="acct-header-fields">
                    <div class="acct-field">
                        <label class="acct-label" for="arInvoiceCustomer">Customer <span style="color:#ef4444;">*</span></label>
                        <select class="acct-select" id="arInvoiceCustomer">
                            <option value="">-- Select Customer --</option>
                            @foreach($accountingPayload['customers'] ?? [] as $c)
                                <option value="{{ $c->customer_id }}" data-code="{{ $c->customer_code }}" data-limit="{{ $c->credit_limit }}" data-balance="{{ $c->current_balance }}" data-terms="{{ $c->payment_terms_code }}">
                                    {{ $c->customer_name }} ({{ $c->customer_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="arInvoicePoNum">Customer PO / Ref #</label>
                        <input type="text" class="acct-input" id="arInvoicePoNum" placeholder="e.g. CUST-PO-8821">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="arInvoiceDate">Invoice Date <span style="color:#ef4444;">*</span></label>
                        <input type="date" class="acct-input" id="arInvoiceDate" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="arInvoiceDueDate">Due Date <span style="color:#ef4444;">*</span></label>
                        <input type="date" class="acct-input" id="arInvoiceDueDate" value="{{ now()->addDays(30)->toDateString() }}">
                    </div>
                </div>

                <!-- Secondary Collapsible Settings -->
                <details class="acct-details-box" style="margin-top:10px;">
                    <summary><i class="ph ph-caret-right"></i> Branch Dimension &amp; Multi-Currency Options</summary>
                    <div class="acct-header-fields" style="margin-top:10px;grid-template-columns: repeat(3, 1fr);">
                        <div class="acct-field">
                            <label class="acct-label" for="arInvoiceBranch">Branch Dimension</label>
                            <select class="acct-select" id="arInvoiceBranch">
                                @foreach($accountingPayload['branches'] ?? [] as $br)
                                    @php $bId = is_array($br) ? ($br['branch_id'] ?? '') : ($br->branch_id ?? ''); $bName = is_array($br) ? ($br['branch_name'] ?? '') : ($br->branch_name ?? ''); @endphp
                                    <option value="{{ $bId }}">{{ $bName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="acct-field">
                            <label class="acct-label" for="arInvoiceCurrency">Currency &amp; Rate</label>
                            <div style="display:flex;gap:6px;">
                                <select class="acct-select" id="arInvoiceCurrency" style="width:85px;flex-shrink:0;">
                                    <option value="PHP">PHP</option>
                                    <option value="USD">USD</option>
                                </select>
                                <input type="number" step="0.000001" min="0.000001" class="acct-input acct-num" id="arInvoiceFxRate" value="1.000000">
                            </div>
                        </div>
                        <div class="acct-field">
                            <label class="acct-label" for="arInvoiceNotes">Customer Notes / Payment Terms</label>
                            <input type="text" class="acct-input" id="arInvoiceNotes" placeholder="Payment instructions, bank info...">
                        </div>
                    </div>
                </details>
            </div>

            <!-- 10-Row Non-Scrolling Invoice Lines Grid -->
            <div class="acct-lines-table-wrap">
                <table class="acct-table acct-lines-table" id="arInvoiceLinesTable">
                    <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th style="min-width:240px;">Product / Service Description</th>
                            <th class="acct-num" style="min-width:90px;">Quantity</th>
                            <th style="min-width:85px;">UOM</th>
                            <th class="acct-num" style="min-width:115px;">Unit Price ({{ $cur }})</th>
                            <th style="min-width:105px;">Tax Code</th>
                            <th class="acct-num" style="min-width:110px;">Output VAT ({{ $cur }})</th>
                            <th class="acct-num" style="min-width:120px;">Line Total ({{ $cur }})</th>
                            <th style="min-width:150px;">Revenue Account</th>
                            <th style="width:44px;text-align:center;">Del</th>
                        </tr>
                    </thead>
                    <tbody id="arInvoiceLinesBody"></tbody>
                </table>
            </div>

            <div style="padding:8px 16px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                <button type="button" class="acct-row-btn" id="btnArAddInvoiceLine">
                    <i class="ph ph-plus"></i> Add Line
                </button>
                <span style="font-size:11.5px;color:#64748b;">10 rows visible without scrolling &bull; Automatic credit limit validation before posting</span>
            </div>
        </div>

        <!-- Separate Summary Card -->
        <div class="acct-summary-card" id="arInvoiceSummaryCard">
            <div class="acct-summary-metrics">
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Subtotal</span>
                    <span class="acct-summary-stat-val" id="arInvoiceSumSubtotal">0.00</span>
                </div>
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Output VAT</span>
                    <span class="acct-summary-stat-val" id="arInvoiceSumTax">0.00</span>
                </div>
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Discount</span>
                    <input type="number" step="0.01" min="0" class="acct-input acct-num" id="arInvoiceDiscount" value="0.00" style="width:110px;height:32px;">
                </div>
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Total Receivable Amount</span>
                    <span class="acct-summary-stat-val" id="arInvoiceSumGrandTotal" style="font-weight:700;color:#0f172a;">0.00</span>
                </div>
            </div>

            <div class="acct-summary-actions">
                <button type="button" class="rfq-btn rfq-btn-outline" id="btnArSaveInvoiceDraft">
                    <i class="ph ph-floppy-disk"></i> Save Draft
                </button>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnArPostInvoice">
                    <i class="ph ph-check-circle"></i> Post Invoice to GL
                </button>
            </div>
        </div>

        <!-- Sales Invoices Register -->
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-list-magnifying-glass"></i> Sales Invoices Register</h2>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="text" class="acct-input" id="arInvoiceSearch" placeholder="Search invoice # or customer..." style="width:200px;height:32px;">
                    <select class="acct-select" id="arInvoiceStatusFilter" style="width:130px;height:32px;">
                        <option value="">All Statuses</option>
                        <option value="DRAFT">Draft</option>
                        <option value="POSTED">Posted</option>
                        <option value="PARTIAL">Partial</option>
                        <option value="PAID">Paid</option>
                    </select>
                </div>
            </div>
            <div class="acct-table-responsive">
                <table class="acct-table" id="arInvoicesRegisterTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Customer PO</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th class="acct-num">Total Amount</th>
                            <th class="acct-num">Balance Due</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="arInvoicesRegisterBody">
                        @forelse($accountingPayload['salesInvoices'] ?? [] as $inv)
                            <tr data-invoice-id="{{ $inv->invoice_id }}">
                                <td class="acct-mono" style="font-weight:600;">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->customer_name }}</td>
                                <td>{{ $inv->customer_po_number ?: 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($inv->due_date)->format('Y-m-d') }}</td>
                                <td class="acct-num">{{ number_format((float) $inv->total_amount, 2) }}</td>
                                <td class="acct-num" style="font-weight:600;color:{{ (float) $inv->balance_due > 0 ? '#ef4444' : '#10b981' }};">
                                    {{ number_format((float) $inv->balance_due, 2) }}
                                </td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ strtolower($inv->status) }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <button type="button" class="acct-row-btn btnArViewInvoice" data-invoice-id="{{ $inv->invoice_id }}" title="View details">
                                        <i class="ph ph-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No sales invoices recorded yet. Click <strong>New Invoice Draft</strong> to issue an invoice.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: COLLECTION RECEIPTS & CWT 2307 -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'receipts' ? ' is-active' : '' }}" data-acct-pane="receipts">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-receipt"></i> Customer Collection Receipts &amp; CWT 2307 Received</h2>
                </div>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnArNewReceipt">
                    <i class="ph ph-plus"></i> New Collection Receipt
                </button>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="arReceiptsRegisterTable">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Bank / Reference</th>
                            <th class="acct-num">Gross Collected</th>
                            <th class="acct-num">CWT 2307 Deducted</th>
                            <th class="acct-num">Net Cash Received</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="arReceiptsRegisterBody">
                        @forelse($accountingPayload['collectionReceipts'] ?? [] as $rcp)
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $rcp->receipt_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($rcp->receipt_date)->format('Y-m-d') }}</td>
                                <td>{{ $rcp->customer_name }}</td>
                                <td><span class="acct-badge">{{ $rcp->payment_method }}</span></td>
                                <td>{{ $rcp->reference_number ?: ($rcp->check_number ?: 'N/A') }}</td>
                                <td class="acct-num">{{ number_format((float) $rcp->total_amount, 2) }}</td>
                                <td class="acct-num" style="color:#2563eb;">{{ number_format((float) $rcp->total_cwt_amount, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format((float) $rcp->total_net_amount, 2) }}</td>
                                <td><span class="acct-badge acct-badge-posted">{{ $rcp->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No collection receipts recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: CUSTOMER CREDIT / DEBIT MEMOS -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'memos' ? ' is-active' : '' }}" data-acct-pane="memos">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-arrows-left-right"></i> Customer Credit &amp; Debit Memos</h2>
                </div>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnArNewMemo">
                    <i class="ph ph-plus"></i> New Customer Memo
                </button>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="arMemosRegisterTable">
                    <thead>
                        <tr>
                            <th>Memo #</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Target Invoice</th>
                            <th>Reason</th>
                            <th class="acct-num">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="arMemosRegisterBody">
                        @forelse($accountingPayload['customerMemos'] ?? [] as $memo)
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $memo->memo_number }}</td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ strtolower($memo->memo_type) }}">
                                        {{ $memo->memo_type }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($memo->memo_date)->format('Y-m-d') }}</td>
                                <td>{{ $memo->customer_name }}</td>
                                <td>{{ $memo->target_sales_invoice_id ?: 'General' }}</td>
                                <td>{{ $memo->reason_description ?: $memo->reason_code }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format((float) $memo->amount, 2) }}</td>
                                <td><span class="acct-badge acct-badge-posted">{{ $memo->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No customer debit or credit memos issued yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: DAIF BOUNCED CHECKS & DUNNING QUEUE -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'dunning' ? ' is-active' : '' }}" data-acct-pane="dunning">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-shield-warning"></i> DAIF Bounced Check Management Queue</h2>
            </div>
            <div class="acct-panel-body">
                <p class="hr-parent-subtitle">
                    When a customer check bounces (Drawn Against Insufficient Funds), the action marks the check as <code>BOUNCED_DAIF</code>, reverses the collection receipt, reopens the original sales invoice, restores the customer's open balance, and charges the bank return penalty to <code>BANK_PENALTY_EXPENSE</code>.
                </p>
            </div>
            <div class="acct-table-responsive">
                <table class="acct-table" id="arChecksTable">
                    <thead>
                        <tr>
                            <th>Check #</th>
                            <th>Customer / Drawer</th>
                            <th>Drawn Bank</th>
                            <th>Check Date</th>
                            <th>Maturity Date</th>
                            <th class="acct-num">Amount</th>
                            <th>Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $receivedChecks = collect($accountingPayload['checkRegister'] ?? [])->where('direction', 'RECEIVED');
                        @endphp
                        @forelse($receivedChecks as $chk)
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $chk->check_number }}</td>
                                <td>{{ $chk->party_name }}</td>
                                <td>{{ $chk->bank_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($chk->check_date)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($chk->maturity_date)->format('Y-m-d') }}</td>
                                <td class="acct-num">{{ number_format((float) $chk->amount, 2) }}</td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ strtolower($chk->status) }}">
                                        {{ $chk->status }}
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    @if(in_array($chk->status, ['RECEIVED', 'DEPOSITED']))
                                        <button type="button" class="rfq-btn rfq-btn-danger btnArBounceCheck" data-check-id="{{ $chk->check_id }}" data-check-num="{{ $chk->check_number }}">
                                            <i class="ph ph-warning"></i> Flag DAIF Bounce
                                        </button>
                                    @else
                                        <span style="font-size:12px;color:#94a3b8;">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No customer checks registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 5: CUSTOMER LEDGER & AGING -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'ledger' ? ' is-active' : '' }}" data-acct-pane="ledger">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-clock-counter-clockwise"></i> Customer Receivables Aging Schedule</h2>
            </div>
            <div class="acct-kpi-grid-4" style="margin-top:12px;">
                <div class="rfq-kpi-card">
                    <div class="rfq-kpi-header"><span class="rfq-kpi-label">Current (0-30 Days)</span></div>
                    <div class="rfq-kpi-val acct-num" style="text-align:left;">
                        {{ $cur }} {{ number_format((float) ($accountingPayload['customerAging']['summary']['current'] ?? 0), 2) }}
                    </div>
                </div>
                <div class="rfq-kpi-card">
                    <div class="rfq-kpi-header"><span class="rfq-kpi-label">31 - 60 Days</span></div>
                    <div class="rfq-kpi-val acct-num" style="text-align:left;">
                        {{ $cur }} {{ number_format((float) ($accountingPayload['customerAging']['summary']['31_60'] ?? 0), 2) }}
                    </div>
                </div>
                <div class="rfq-kpi-card">
                    <div class="rfq-kpi-header"><span class="rfq-kpi-label">61 - 90 Days</span></div>
                    <div class="rfq-kpi-val acct-num" style="text-align:left;">
                        {{ $cur }} {{ number_format((float) ($accountingPayload['customerAging']['summary']['61_90'] ?? 0), 2) }}
                    </div>
                </div>
                <div class="rfq-kpi-card">
                    <div class="rfq-kpi-header"><span class="rfq-kpi-label">Over 90 Days</span><span class="rfq-kpi-icon draft"><i class="ph ph-warning"></i></span></div>
                    <div class="rfq-kpi-val acct-num" style="text-align:left;color:#ef4444;">
                        {{ $cur }} {{ number_format((float) ($accountingPayload['customerAging']['summary']['over_90'] ?? 0), 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="acct-panel" style="margin-top:16px;">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-users"></i> Customer Balances &amp; Credit Limits</h2>
            </div>
            <div class="acct-table-responsive">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Customer Code</th>
                            <th>Customer Name</th>
                            <th>TIN</th>
                            <th>Terms</th>
                            <th class="acct-num">Credit Limit</th>
                            <th class="acct-num">Current Balance</th>
                            <th class="acct-num">Available Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accountingPayload['customers'] ?? [] as $c)
                            @php
                                $avail = max(0, (float) $c->credit_limit - (float) $c->current_balance);
                            @endphp
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $c->customer_code }}</td>
                                <td>{{ $c->customer_name }}</td>
                                <td>{{ $c->tin ?: 'N/A' }}</td>
                                <td><span class="acct-badge">{{ $c->payment_terms_code ?: 'NET30' }}</span></td>
                                <td class="acct-num">{{ number_format((float) $c->credit_limit, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;color:{{ (float) $c->current_balance > (float) $c->credit_limit ? '#ef4444' : '#0f172a' }};">
                                    {{ number_format((float) $c->current_balance, 2) }}
                                </td>
                                <td class="acct-num" style="color:{{ $avail > 0 ? '#10b981' : '#ef4444' }};">
                                    {{ number_format($avail, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;">No customers registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- NEW CUSTOMER MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="arCustomerModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:550px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-user-plus"></i> Register New Customer</h3>
            <button type="button" class="acct-modal-close" id="btnArCloseCustomerModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="arCustCode">Customer Code <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="arCustCode" placeholder="e.g. CUST-001">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arCustName">Customer Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="arCustName" placeholder="Legal corporate name">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arCustTin">TIN (Tax ID)</label>
                    <input type="text" class="acct-input" id="arCustTin" placeholder="000-000-000-000">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arCustTerms">Payment Terms</label>
                    <select class="acct-select" id="arCustTerms">
                        @foreach($accountingPayload['paymentTerms'] ?? [] as $t)
                            <option value="{{ $t->terms_code }}">{{ $t->terms_name }} ({{ $t->due_days }} days)</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arCustCreditLimit">Credit Limit ({{ $cur }})</label>
                    <input type="number" step="0.01" min="0" class="acct-input acct-num" id="arCustCreditLimit" value="50000.00">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arCustEmail">Contact Email</label>
                    <input type="email" class="acct-input" id="arCustEmail" placeholder="billing@client.com">
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnArCancelCustomer">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnArSubmitCustomer">
                <i class="ph ph-check"></i> Save Customer
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- COLLECTION RECEIPT MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="arReceiptModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:750px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-receipt"></i> Create Collection Receipt</h3>
            <button type="button" class="acct-modal-close" id="btnArCloseReceiptModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(3, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="arRcpCustomer">Customer <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="arRcpCustomer">
                        <option value="">-- Select Customer --</option>
                        @foreach($accountingPayload['customers'] ?? [] as $c)
                            <option value="{{ $c->customer_id }}">{{ $c->customer_name }} ({{ $c->customer_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arRcpDate">Receipt Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="arRcpDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arRcpMethod">Payment Method <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="arRcpMethod">
                        <option value="BANK_TRANSFER">Bank Deposit / Transfer</option>
                        <option value="CHECK">Check (Immediate)</option>
                        <option value="PDC">Post-Dated Check (PDC)</option>
                        <option value="CASH">Cash Collection</option>
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arRcpBankAccount">Deposit Bank Account <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="arRcpBankAccount">
                        @foreach($accountingPayload['bankAccounts'] ?? [] as $ba)
                            <option value="{{ $ba->bank_account_id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arRcpReference">Check / Ref #</label>
                    <input type="text" class="acct-input" id="arRcpReference" placeholder="Deposit slip or check number">
                </div>
                <div class="acct-field" id="arRcpPdcBlock" style="display:none;">
                    <label class="acct-label" for="arRcpPdcDate">PDC Maturity Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="arRcpPdcDate" value="{{ now()->addDays(30)->toDateString() }}">
                </div>
            </div>

            <!-- CWT 2307 Received Disclosure -->
            <details class="acct-details-box" style="margin-top:10px;">
                <summary><i class="ph ph-caret-right"></i> BIR Form 2307 Creditable Withholding Tax (CWT) Received</summary>
                <div class="acct-header-fields" style="margin-top:10px;grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label" for="arRcp2307Num">BIR 2307 Certificate #</label>
                        <input type="text" class="acct-input" id="arRcp2307Num" placeholder="e.g. 2307-2026-Q4-001">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="arRcp2307Quarter">Tax Quarter</label>
                        <select class="acct-select" id="arRcp2307Quarter">
                            <option value="Q1">Q1 (Jan - Mar)</option>
                            <option value="Q2">Q2 (Apr - Jun)</option>
                            <option value="Q3">Q3 (Jul - Sep)</option>
                            <option value="Q4" selected>Q4 (Oct - Dec)</option>
                        </select>
                    </div>
                </div>
            </details>

            <h4 style="margin:16px 0 8px 0;font-size:13.5px;font-weight:600;color:#0f172a;">Open Invoices for Collection Allocation</h4>
            <div class="acct-lines-table-wrap">
                <table class="acct-table acct-lines-table" id="arRcpAllocationsTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th class="acct-num">Total Amount</th>
                            <th class="acct-num">Open Balance</th>
                            <th class="acct-num" style="min-width:110px;">Collection Amount</th>
                            <th class="acct-num" style="min-width:100px;">CWT Deducted</th>
                        </tr>
                    </thead>
                    <tbody id="arRcpAllocationsBody">
                        <tr>
                            <td colspan="5" style="text-align:center;padding:16px;color:#94a3b8;">
                                Select a customer to load open invoices.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:12px;padding:12px;background:#f8fafc;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:12px;color:#64748b;">
                    CWT debits <code>CWT_RECEIVABLE</code> asset account; net funds credit customer receivable.
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11.5px;color:#64748b;">Net Cash/Bank Collected:</div>
                    <div class="acct-num" style="font-size:16px;font-weight:700;color:#0f172a;" id="arRcpNetDisplay">{{ $cur }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnArCancelReceipt">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnArSubmitReceipt">
                <i class="ph ph-check"></i> Post Collection Receipt
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CUSTOMER MEMO MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="arMemoModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:550px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-arrows-left-right"></i> Issue Customer Credit / Debit Memo</h3>
            <button type="button" class="acct-modal-close" id="btnArCloseMemoModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="arMemoCustomer">Customer <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="arMemoCustomer">
                        <option value="">-- Select Customer --</option>
                        @foreach($accountingPayload['customers'] ?? [] as $c)
                            <option value="{{ $c->customer_id }}">{{ $c->customer_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arMemoType">Memo Type <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="arMemoType">
                        <option value="CREDIT_MEMO">Customer Credit Memo (Reduces Balance)</option>
                        <option value="DEBIT_MEMO">Customer Debit Memo (Increases Balance)</option>
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arMemoDate">Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="arMemoDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="arMemoAmount">Amount ({{ $cur }}) <span style="color:#ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="acct-input acct-num" id="arMemoAmount" value="0.00">
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="arMemoTargetInvoice">Target Sales Invoice (Optional)</label>
                    <select class="acct-select" id="arMemoTargetInvoice">
                        <option value="">-- No specific invoice (General AR balance) --</option>
                        @foreach($accountingPayload['salesInvoices'] ?? [] as $inv)
                            <option value="{{ $inv->invoice_id }}">{{ $inv->invoice_number }} (Bal: {{ number_format((float) $inv->balance_due, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="arMemoReason">Reason / Description <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="arMemoReason" placeholder="e.g. Sales return or price correction">
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnArCancelMemo">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnArSubmitMemo">
                <i class="ph ph-check"></i> Post Memo
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DAIF BOUNCE MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="arBounceModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:500px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title" style="color:#ef4444;"><i class="ph ph-warning"></i> Flag Check as DAIF Bounced</h3>
            <button type="button" class="acct-modal-close" id="btnArCloseBounceModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <input type="hidden" id="arBounceCheckId" value="">
            <p style="font-size:13px;color:#334155;margin-bottom:12px;">
                You are flagging Check <strong id="arBounceCheckDisplay">#</strong> as bounced. This will automatically reverse the collection receipt, restore customer balance, and charge bank return penalty.
            </p>
            <div class="acct-field" style="margin-bottom:12px;">
                <label class="acct-label" for="arBounceReason">Bounce Reason / Remarks <span style="color:#ef4444;">*</span></label>
                <input type="text" class="acct-input" id="arBounceReason" value="DAIF - Drawn Against Insufficient Funds">
            </div>
            <div class="acct-field">
                <label class="acct-label" for="arBouncePenalty">Bank Penalty Fee ({{ $cur }})</label>
                <input type="number" step="0.01" min="0" class="acct-input acct-num" id="arBouncePenalty" value="500.00">
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnArCancelBounce">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-danger" id="btnArSubmitBounce">
                <i class="ph ph-arrow-counter-clockwise"></i> Confirm DAIF Reversal
            </button>
        </div>
    </div>
</div>
@endsection
