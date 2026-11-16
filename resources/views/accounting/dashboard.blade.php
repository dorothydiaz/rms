@extends('layouts.app')

@section('title', 'Accounting Dashboard')

@section('content')
@php
    $kpis = $accountingPayload['kpis'] ?? [];
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
    $fmt = fn($val) => $cur . ' ' . number_format((float) $val, 2);
@endphp

<div class="acct-workspace" id="accountingDashboardRoot">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Accounting Dashboard',
        'pageSubtitle' => 'Real-time financial position, subledger health, and period governance',
        'pageIcon' => 'ph-gauge',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- Row 1: Primary 5 Financial Position KPIs -->
    <div class="acct-kpi-grid-5">
        <a href="{{ route('accounting.cash-bank') }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Cash Position</span>
                <span class="rfq-kpi-icon awarded"><i class="ph ph-wallet"></i></span>
            </div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $fmt($kpis['cashPosition'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Cash on Hand + Bank GL Balances</div>
        </a>

        <a href="{{ route('accounting.ap') }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Accounts Payable</span>
                <span class="rfq-kpi-icon awaiting"><i class="ph ph-receipt"></i></span>
            </div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $fmt($kpis['payables'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">AP Control & GRNI Accrued</div>
        </a>

        <a href="{{ route('accounting.ar') }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Accounts Receivable</span>
                <span class="rfq-kpi-icon"><i class="ph ph-hand-coins"></i></span>
            </div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $fmt($kpis['receivables'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Open Trade Receivables</div>
        </a>

        <a href="{{ route('accounting.reports') }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Net Profit (YTD)</span>
                <span class="rfq-kpi-icon awarded"><i class="ph ph-trend-up"></i></span>
            </div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $fmt($kpis['netProfit'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Revenue minus COGS & OPEX</div>
        </a>

        <a href="{{ route('accounting.ap', ['tab' => 'bills']) }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Overdue Bills</span>
                <span class="rfq-kpi-icon draft"><i class="ph ph-warning-circle"></i></span>
            </div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $fmt($kpis['overdueBills'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Past-due vendor obligations</div>
        </a>
    </div>

    <!-- Row 2: 4 Operational & Governance KPIs -->
    <div class="acct-kpi-grid-4">
        <button type="button" class="rfq-kpi-card" style="text-align:left;" data-acct-open="approval-drawer">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Pending Approvals</span>
                <span class="rfq-kpi-icon awaiting"><i class="ph ph-stamp"></i></span>
            </div>
            <div class="rfq-kpi-val">{{ $kpis['pendingApprovals'] ?? 0 }}</div>
            <div class="rfq-kpi-sub">Maker-Checker queue items</div>
        </button>

        <a href="{{ route('accounting.cash-bank', ['tab' => 'recon']) }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Unreconciled Bank Items</span>
                <span class="rfq-kpi-icon draft"><i class="ph ph-bank"></i></span>
            </div>
            <div class="rfq-kpi-val">{{ $kpis['unreconciledBankItems'] ?? 0 }}</div>
            <div class="rfq-kpi-sub">Statement lines pending match</div>
        </a>

        <a href="{{ route('accounting.gl', ['tab' => 'period-close']) }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Open Fiscal Periods</span>
                <span class="rfq-kpi-icon"><i class="ph ph-calendar-blank"></i></span>
            </div>
            <div class="rfq-kpi-val">{{ $kpis['openPeriods'] ?? 0 }}</div>
            <div class="rfq-kpi-sub">Postings allowed for open months</div>
        </a>

        <a href="{{ route('accounting.cash-bank', ['tab' => 'checks']) }}" class="rfq-kpi-card" style="text-decoration:none;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Upcoming PDC Maturities</span>
                <span class="rfq-kpi-icon awaiting"><i class="ph ph-money"></i></span>
            </div>
            <div class="rfq-kpi-val">{{ $kpis['upcomingPdcMaturities'] ?? 0 }}</div>
            <div class="rfq-kpi-sub">Checks maturing within 14 days</div>
        </a>
    </div>

    <!-- Submodule Navigation Grid -->
    <div class="acct-panel">
        <div class="acct-panel-head">
            <h2 class="acct-panel-title"><i class="ph ph-squares-four"></i> Financial Control Center</h2>
            <a href="{{ route('accounting.gl') }}" class="rfq-btn rfq-btn-primary">
                <i class="ph ph-plus-circle"></i> New Journal Entry
            </a>
        </div>
        <div class="acct-panel-body">
            <div class="acct-quick-links">
                <a href="{{ route('accounting.gl') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-books"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">General Ledger</div>
                        <div style="font-size:11.5px;color:#64748b;">Journals, COA, Inquiry, Close</div>
                    </div>
                </a>
                <a href="{{ route('accounting.ap') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-receipt"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Accounts Payable</div>
                        <div style="font-size:11.5px;color:#64748b;">Bills, 3-Way Match, Payments</div>
                    </div>
                </a>
                <a href="{{ route('accounting.ar') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-hand-coins"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Accounts Receivable</div>
                        <div style="font-size:11.5px;color:#64748b;">Invoices, Receipts, Dunning</div>
                    </div>
                </a>
                <a href="{{ route('accounting.cash-bank') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-bank"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Cash & Bank</div>
                        <div style="font-size:11.5px;color:#64748b;">Banks, Advances, PDC, Recon</div>
                    </div>
                </a>
                <a href="{{ route('accounting.fixed-assets') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-buildings"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Fixed Assets</div>
                        <div style="font-size:11.5px;color:#64748b;">Register, Depreciation, Disposals</div>
                    </div>
                </a>
                <a href="{{ route('accounting.loans') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-currency-circle-dollar"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Loans</div>
                        <div style="font-size:11.5px;color:#64748b;">Amortization & Repayments</div>
                    </div>
                </a>
                <a href="{{ route('accounting.tax') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-percent"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Tax & BIR</div>
                        <div style="font-size:11.5px;color:#64748b;">VAT, EWT, 2307 & Returns</div>
                    </div>
                </a>
                <a href="{{ route('accounting.reports') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-chart-bar"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Financial Reports</div>
                        <div style="font-size:11.5px;color:#64748b;">TB, IS, BS, Cash Flow, Aging</div>
                    </div>
                </a>
                <a href="{{ route('accounting.config') }}" class="acct-module-link-card">
                    <span class="acct-module-link-icon"><i class="ph ph-sliders-horizontal"></i></span>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Configuration</div>
                        <div style="font-size:11.5px;color:#64748b;">FY, Terms, FX, Approvals</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Two-Column Operational Activity -->
    <div class="acct-two-col">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-list-dashes"></i> Recent General Ledger Journals</h2>
                <a href="{{ route('accounting.gl') }}" class="acct-row-btn">Open GL</a>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Journal #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="acct-num">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="acctDashboardRecentJournalsBody"></tbody>
                </table>
            </div>
        </div>

        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-shield-check"></i> Recent Immutable Audit Activity</h2>
                <button type="button" class="acct-row-btn" data-acct-open="audit-drawer">Full Log</button>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Doc #</th>
                            <th>Action</th>
                            <th>Actor</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody id="acctDashboardAuditBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

