@extends('layouts.app')

@section('title', 'Tax & BIR Compliance')

@section('content')
@php
    $initialTab = request()->query('tab', 'codes');
    $validTabs = ['codes', 'vat-ledger', 'ewt-2307', 'bir-reports'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'codes';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
    $taxCodes = $accountingPayload['taxCodes'] ?? [];
    $certs = $accountingPayload['vendorCertificates'] ?? [];
    $invoices = $accountingPayload['salesInvoices'] ?? [];
    $bills = $accountingPayload['vendorBills'] ?? [];
@endphp

<div class="acct-workspace" id="accountingTaxRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Tax & BIR Compliance',
        'pageSubtitle' => 'Tax codes, ATC withholding rules, Input/Output VAT subledger, BIR Form 2307 & statutory returns',
        'pageIcon' => 'ph-percent',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- KPI Summary Grid -->
    <div class="acct-kpi-grid-4">
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Active Tax Codes</span><span class="rfq-kpi-icon"><i class="ph ph-tag"></i></span></div>
            <div class="rfq-kpi-val">{{ count($taxCodes) }}</div>
            <div class="rfq-kpi-sub">VAT 12%, EWT &amp; Zero-Rated rules</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">BIR 2307 Certificates</span><span class="rfq-kpi-icon awarded"><i class="ph ph-certificate"></i></span></div>
            <div class="rfq-kpi-val">{{ count($certs) }}</div>
            <div class="rfq-kpi-sub">Issued on AP &amp; received on AR</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Input VAT Source Bills</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-receipt"></i></span></div>
            <div class="rfq-kpi-val">{{ count($bills) }}</div>
            <div class="rfq-kpi-sub">Allowable input tax purchases</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Output VAT Invoices</span><span class="rfq-kpi-icon draft"><i class="ph ph-trend-up"></i></span></div>
            <div class="rfq-kpi-val">{{ count($invoices) }}</div>
            <div class="rfq-kpi-sub">Vatable taxable sales &amp; receipts</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'codes' ? ' active' : '' }}" data-acct-tab="codes">
            <i class="ph ph-tag"></i> <span>Tax Codes &amp; Withholding Rules</span>
            <span class="rfq-tab-count">{{ count($taxCodes) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'vat-ledger' ? ' active' : '' }}" data-acct-tab="vat-ledger">
            <i class="ph ph-list-dashes"></i> <span>VAT Subledger</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'ewt-2307' ? ' active' : '' }}" data-acct-tab="ewt-2307">
            <i class="ph ph-certificate"></i> <span>EWT &amp; BIR 2307</span>
            <span class="rfq-tab-count">{{ count($certs) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'bir-reports' ? ' active' : '' }}" data-acct-tab="bir-reports">
            <i class="ph ph-file-pdf"></i> <span>BIR Statutory Returns (2550Q / SLSP / 1601-EQ)</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: TAX CODES & WITHHOLDING RULES -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'codes' ? ' is-active' : '' }}" data-acct-pane="codes">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-tag"></i> Active Tax Codes &amp; EWT Rates</h2>
            </div>
            <div class="acct-panel-body" style="border-bottom:1px solid #e2e8f0;">
                <form id="taxCodeForm" class="acct-header-fields" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="acct-field"><label class="acct-label">Code</label><input type="text" class="acct-input acct-mono" id="taxCodeVal" placeholder="WC158" required></div>
                    <div class="acct-field"><label class="acct-label">Tax Name</label><input type="text" class="acct-input" id="taxNameVal" placeholder="EWT 1% Goods" required></div>
                    <div class="acct-field">
                        <label class="acct-label">Type</label>
                        <select class="acct-select" id="taxTypeVal">
                            <option value="EWT">EWT (AP Payable)</option>
                            <option value="CWT">CWT (AR Creditable)</option>
                            <option value="VAT_INPUT">VAT_INPUT</option>
                            <option value="VAT_OUTPUT">VAT_OUTPUT</option>
                            <option value="EXEMPT">EXEMPT</option>
                            <option value="ZERO_RATED">ZERO_RATED</option>
                        </select>
                    </div>
                    <div class="acct-field"><label class="acct-label">Rate %</label><input type="number" step="0.01" min="0" max="100" class="acct-input acct-num" id="taxRateVal" value="1.00" required></div>
                    <div class="acct-field" style="display:flex;align-items:flex-end;"><button type="submit" class="rfq-btn rfq-btn-primary" style="width:100%;"><i class="ph ph-plus"></i> Save Rule</button></div>
                </form>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th class="acct-num">Rate %</th>
                            <th>ATC</th>
                            <th>BIR Form</th>
                        </tr>
                    </thead>
                    <tbody id="taxCodesTableBody">
                        @foreach($taxCodes as $tc)
                            <tr>
                                <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $tc->tax_code ?? $tc['tax_code'] }}</td>
                                <td>{{ $tc->tax_name ?? $tc['tax_name'] }}</td>
                                <td><span class="acct-badge acct-badge-draft">{{ $tc->tax_type ?? $tc['tax_type'] }}</span></td>
                                <td class="acct-num">{{ number_format((float) ($tc->tax_rate_percent ?? $tc['tax_rate_percent'] ?? 0), 2) }}%</td>
                                <td class="acct-mono">{{ $tc->atc_code ?? $tc['atc_code'] ?? 'WC158' }}</td>
                                <td><span class="acct-badge acct-badge-system">{{ $tc->bir_form ?? $tc['bir_form'] ?? '2307' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: VAT SUBLEDGER -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'vat-ledger' ? ' is-active' : '' }}" data-acct-pane="vat-ledger">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-list-dashes"></i> Input &amp; Output VAT Subledger</h2>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>VAT Category</th>
                            <th>Source Document</th>
                            <th>Party Name</th>
                            <th class="acct-num">Net Taxable Amount ({{ $cur }})</th>
                            <th class="acct-num">VAT (12%)</th>
                            <th>Tax Code</th>
                        </tr>
                    </thead>
                    <tbody id="vatSubledgerTableBody">
                        @foreach($invoices as $inv)
                            @if((float) ($inv['tax_amount'] ?? $inv->tax_amount ?? 0) > 0)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($inv['invoice_date'] ?? $inv->invoice_date)->toDateString() }}</td>
                                    <td><span class="acct-badge acct-badge-posted">OUTPUT_VAT</span></td>
                                    <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $inv['invoice_number'] ?? $inv->invoice_number }}</td>
                                    <td>{{ $inv['customer_name'] ?? $inv->customer_name }}</td>
                                    <td class="acct-num">{{ number_format((float) ($inv['subtotal_amount'] ?? $inv->subtotal_amount ?? 0), 2) }}</td>
                                    <td class="acct-num" style="font-weight:700;">{{ number_format((float) ($inv['tax_amount'] ?? $inv->tax_amount ?? 0), 2) }}</td>
                                    <td><span class="acct-badge acct-badge-system">VAT_12</span></td>
                                </tr>
                            @endif
                        @endforeach
                        @foreach($bills as $b)
                            @if((float) ($b['bill_tax_amount'] ?? $b->bill_tax_amount ?? 0) > 0)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($b['vendor_invoice_date'] ?? $b->vendor_invoice_date)->toDateString() }}</td>
                                    <td><span class="acct-badge acct-badge-draft">INPUT_VAT</span></td>
                                    <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $b['vendor_bill_number'] ?? $b->vendor_bill_number }}</td>
                                    <td>{{ $b['vendor_name'] ?? $b->vendor_name }}</td>
                                    <td class="acct-num">{{ number_format((float) ($b['bill_subtotal_amount'] ?? $b->bill_subtotal_amount ?? 0), 2) }}</td>
                                    <td class="acct-num" style="font-weight:700;">{{ number_format((float) ($b['bill_tax_amount'] ?? $b->bill_tax_amount ?? 0), 2) }}</td>
                                    <td><span class="acct-badge acct-badge-system">INPUT_12</span></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: EWT & BIR 2307 -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'ewt-2307' ? ' is-active' : '' }}" data-acct-pane="ewt-2307">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-certificate"></i> BIR Form 2307 Certificates Register</h2>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Certificate #</th>
                            <th>Direction</th>
                            <th>Payee / Client</th>
                            <th>TIN</th>
                            <th>Period Covered</th>
                            <th>ATC</th>
                            <th class="acct-num">Gross Payment ({{ $cur }})</th>
                            <th class="acct-num">Withheld Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="ewtCertificatesTableBody">
                        @forelse($certs as $c)
                            @php
                                $gross = (float) ($c['gross_income_payment'] ?? $c->gross_income_payment ?? ($c['income_payment_amount'] ?? 0));
                                $withheld = (float) ($c['amount_withheld'] ?? $c->amount_withheld ?? ($c['tax_withheld_amount'] ?? 0));
                                $pType = (string) ($c['party_type'] ?? $c->party_type ?? 'VENDOR');
                            @endphp
                            <tr>
                                <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $c['certificate_number'] ?? $c->certificate_number }}</td>
                                <td><span class="acct-badge acct-badge-{{ $pType === 'VENDOR' ? 'draft' : 'posted' }}">{{ $pType === 'VENDOR' ? 'ISSUED (AP)' : 'RECEIVED (AR)' }}</span></td>
                                <td>{{ $c['party_name'] ?? $c->party_name }}</td>
                                <td class="acct-mono">{{ $c['payee_tin'] ?? $c->payee_tin ?? ($c['party_tin'] ?? '000-000-000-000') }}</td>
                                <td>{{ \Carbon\Carbon::parse($c['period_from'] ?? $c->period_from)->format('M d') }} - {{ \Carbon\Carbon::parse($c['period_to'] ?? $c->period_to)->format('M d, Y') }}</td>
                                <td class="acct-mono">{{ $c['atc_code'] ?? $c->atc_code ?? 'WC158' }}</td>
                                <td class="acct-num">{{ number_format($gross, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;color:#9333ea;">{{ number_format($withheld, 2) }}</td>
                                <td><span class="acct-badge acct-badge-awarded">{{ $c['status'] ?? $c->status ?? 'ACTIVE' }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:36px;color:#64748b;">
                                    <i class="ph ph-certificate" style="font-size:32px;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                                    No BIR 2307 certificates issued or received yet. They generate automatically when posting Vendor Payments with EWT or Customer Collection Receipts with CWT.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: BIR STATUTORY REPORTS (2550Q / SLSP / 1601-EQ / QAP / SAWT) -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'bir-reports' ? ' is-active' : '' }}" data-acct-pane="bir-reports">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-file-pdf"></i> BIR Statutory Report Engine</h2>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <select class="acct-select" id="taxRepYear" style="width:110px;">
                            @for($y = 2026; $y >= 2024; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                        <select class="acct-select" id="taxRepQuarter" style="width:130px;">
                            <option value="1">Q1 (Jan - Mar)</option>
                            <option value="2">Q2 (Apr - Jun)</option>
                            <option value="3">Q3 (Jul - Sep)</option>
                            <option value="4">Q4 (Oct - Dec)</option>
                        </select>
                        <select class="acct-select" id="taxRepType" style="width:180px;">
                            <option value="2550Q">BIR Form 2550Q (VAT)</option>
                            <option value="SLSP">SLSP (Sales &amp; Purchases)</option>
                            <option value="1601-EQ">BIR Form 1601-EQ (EWT)</option>
                            <option value="QAP">QAP (EWT Payees)</option>
                            <option value="SAWT">SAWT (CWT Clients)</option>
                        </select>
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnGenerateTaxReport">
                            <i class="ph ph-magnifying-glass"></i> View Report
                        </button>
                    </div>
                </div>
            </div>
            <div class="acct-panel-body" id="taxReportContainer" style="min-height:360px;padding:20px;">
                <div style="text-align:center;padding:40px;color:#64748b;">
                    <i class="ph ph-file-text" style="font-size:40px;color:#94a3b8;display:block;margin-bottom:10px;"></i>
                    Select the Return Period and BIR Form above, then click <strong>View Report</strong> to compute live CAS statutory schedules.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
