@extends('layouts.app')

@section('title', 'Financial Reports')

@section('content')
@php
    $initialTab = request()->query('tab', 'trial-balance');
    $validTabs = ['trial-balance', 'income-statement', 'balance-sheet', 'cash-flow', 'aging', 'inv-recon'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'trial-balance';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
@endphp

<div class="acct-workspace" id="accountingReportsRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Financial Reports & Analytics',
        'pageSubtitle' => 'Live Trial Balance & IFRS Financial Statements computed dynamically from the General Ledger',
        'pageIcon' => 'ph-chart-bar',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'trial-balance' ? ' active' : '' }}" data-acct-tab="trial-balance">
            <i class="ph ph-scales"></i> <span>Trial Balance</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'income-statement' ? ' active' : '' }}" data-acct-tab="income-statement">
            <i class="ph ph-trend-up"></i> <span>Income Statement (P&amp;L)</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'balance-sheet' ? ' active' : '' }}" data-acct-tab="balance-sheet">
            <i class="ph ph-bank"></i> <span>Balance Sheet</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'cash-flow' ? ' active' : '' }}" data-acct-tab="cash-flow">
            <i class="ph ph-arrows-clockwise"></i> <span>Cash Flow Statement</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'aging' ? ' active' : '' }}" data-acct-tab="aging">
            <i class="ph ph-clock"></i> <span>AP &amp; AR Aging</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'inv-recon' ? ' active' : '' }}" data-acct-tab="inv-recon">
            <i class="ph ph-package"></i> <span>Inventory &amp; Landed Cost</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: TRIAL BALANCE -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'trial-balance' ? ' is-active' : '' }}" data-acct-pane="trial-balance">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-scales"></i> Live Trial Balance</h2>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <label class="acct-label" style="margin:0;">As of Date:</label>
                        <input type="date" class="acct-input" id="tbAsOfDate" value="{{ now()->toDateString() }}" style="width:145px;">
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnRefreshTrialBalance"><i class="ph ph-arrows-clockwise"></i> Update</button>
                    </div>
                </div>
                <div>
                    <span class="acct-badge acct-badge-posted" id="tbBalancedBadge"><i class="ph ph-check-circle"></i> BALANCED</span>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Normal Balance</th>
                            <th class="acct-num">Total Debit ({{ $cur }})</th>
                            <th class="acct-num">Total Credit ({{ $cur }})</th>
                            <th class="acct-num">Net Carrying Balance</th>
                        </tr>
                    </thead>
                    <tbody id="tbTableBody"></tbody>
                    <tfoot>
                        <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #cbd5e1;">
                            <td colspan="4">TOTAL TRIAL BALANCE</td>
                            <td class="acct-num" id="tbTotalDebit">0.00</td>
                            <td class="acct-num" id="tbTotalCredit">0.00</td>
                            <td class="acct-num" id="tbDifference" style="color:#16a34a;">0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: INCOME STATEMENT -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'income-statement' ? ' is-active' : '' }}" data-acct-pane="income-statement">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-trend-up"></i> Income Statement (Profit &amp; Loss)</h2>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="date" class="acct-input" id="isStartDate" value="{{ now()->startOfYear()->toDateString() }}" style="width:145px;">
                        <span>to</span>
                        <input type="date" class="acct-input" id="isEndDate" value="{{ now()->toDateString() }}" style="width:145px;">
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnRefreshIncomeStatement"><i class="ph ph-arrows-clockwise"></i> Compute P&amp;L</button>
                    </div>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;padding:16px;">
                <div id="isContentArea"></div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: BALANCE SHEET -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'balance-sheet' ? ' is-active' : '' }}" data-acct-pane="balance-sheet">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-bank"></i> Statement of Financial Position (Balance Sheet)</h2>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <label class="acct-label" style="margin:0;">As of Date:</label>
                        <input type="date" class="acct-input" id="bsAsOfDate" value="{{ now()->toDateString() }}" style="width:145px;">
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnRefreshBalanceSheet"><i class="ph ph-arrows-clockwise"></i> Generate</button>
                    </div>
                </div>
                <div>
                    <span class="acct-badge acct-badge-posted" id="bsBalancedBadge"><i class="ph ph-check-circle"></i> ASSETS = LIABILITIES + EQUITY</span>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;padding:16px;">
                <div id="bsContentArea"></div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: CASH FLOW STATEMENT -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'cash-flow' ? ' is-active' : '' }}" data-acct-pane="cash-flow">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-arrows-clockwise"></i> Statement of Cash Flows</h2>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="date" class="acct-input" id="cfStartDate" value="{{ now()->startOfYear()->toDateString() }}" style="width:145px;">
                        <span>to</span>
                        <input type="date" class="acct-input" id="cfEndDate" value="{{ now()->toDateString() }}" style="width:145px;">
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnRefreshCashFlow"><i class="ph ph-arrows-clockwise"></i> Generate</button>
                    </div>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;padding:16px;">
                <div id="cfContentArea"></div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 5: AP & AR AGING -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'aging' ? ' is-active' : '' }}" data-acct-pane="aging">
        <div class="acct-panel" style="margin-bottom:20px;">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-clock"></i> Accounts Payable (AP) Aging Schedule</h2>
            </div>
            <div class="acct-table-wrap" style="min-height:200px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Vendor Name</th>
                            <th class="acct-num">Current</th>
                            <th class="acct-num">1-30 Days</th>
                            <th class="acct-num">31-60 Days</th>
                            <th class="acct-num">61-90 Days</th>
                            <th class="acct-num">Over 90 Days</th>
                            <th class="acct-num">Total Open Balance</th>
                        </tr>
                    </thead>
                    <tbody id="apAgingTableBody"></tbody>
                </table>
            </div>
        </div>

        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-clock"></i> Accounts Receivable (AR) Aging Schedule</h2>
            </div>
            <div class="acct-table-wrap" style="min-height:200px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th class="acct-num">Current</th>
                            <th class="acct-num">1-30 Days</th>
                            <th class="acct-num">31-60 Days</th>
                            <th class="acct-num">61-90 Days</th>
                            <th class="acct-num">Over 90 Days</th>
                            <th class="acct-num">Total Open Balance</th>
                        </tr>
                    </thead>
                    <tbody id="arAgingTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 6: INVENTORY RECONCILIATION & LANDED COST ALLOCATION -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'inv-recon' ? ' is-active' : '' }}" data-acct-pane="inv-recon">
        <div class="acct-panel" style="margin-bottom:20px;">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-package"></i> Perpetual Inventory vs General Ledger Reconciliation</h2>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnOpenLandedCostModal"><i class="ph ph-plus"></i> Allocate Landed Cost</button>
            </div>
            <div class="acct-panel-body" style="padding:20px;">
                <div class="acct-kpi-grid-4">
                    <div class="rfq-kpi-card">
                        <div class="rfq-kpi-header"><span class="rfq-kpi-label">GL Inventory Balance</span><span class="rfq-kpi-icon awarded"><i class="ph ph-database"></i></span></div>
                        <div class="rfq-kpi-val acct-num" id="reconGlInvBalance">0.00</div>
                        <div class="rfq-kpi-sub">Carrying value in 1310-INVENTORY</div>
                    </div>
                    <div class="rfq-kpi-card">
                        <div class="rfq-kpi-header"><span class="rfq-kpi-label">Perpetual Stock Value</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-package"></i></span></div>
                        <div class="rfq-kpi-val acct-num" id="reconPerpetualVal">0.00</div>
                        <div class="rfq-kpi-sub">Sum of warehouse stock balances</div>
                    </div>
                    <div class="rfq-kpi-card">
                        <div class="rfq-kpi-header"><span class="rfq-kpi-label">Reconciliation Variance</span><span class="rfq-kpi-icon draft"><i class="ph ph-scales"></i></span></div>
                        <div class="rfq-kpi-val acct-num" id="reconVariance" style="color:#16a34a;">0.00</div>
                        <div class="rfq-kpi-sub">Difference between GL &amp; Stock</div>
                    </div>
                    <div class="rfq-kpi-card">
                        <div class="rfq-kpi-header"><span class="rfq-kpi-label">GRNI Accrual Balance</span><span class="rfq-kpi-icon"><i class="ph ph-receipt"></i></span></div>
                        <div class="rfq-kpi-val acct-num" id="reconGrniBalance">0.00</div>
                        <div class="rfq-kpi-sub">Pending vendor bills clearing</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Landed Cost Allocation with Consumed Stock Protection -->
<div class="acct-backdrop" id="acctLandedCostModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Allocate Landed Cost">
        <div class="acct-modal-head">
            <h3><i class="ph ph-package"></i> Allocate Landed Cost (Freight / Customs)</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctLandedCostModalBackdrop"><i class="ph ph-x"></i></button>
        </div>
        <form id="landedCostAllocForm">
            <div class="acct-modal-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field" style="grid-column: span 2;">
                        <label class="acct-label">Stock Intake ID (GRN Reference)</label>
                        <input type="text" class="acct-input acct-mono" id="lcStockInId" placeholder="e.g. GRN-2026-0042" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Total Landed Cost ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="lcAmount" placeholder="5000.00" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Cost Type</label>
                        <select class="acct-select" id="lcType">
                            <option value="FREIGHT">Freight &amp; Delivery</option>
                            <option value="CUSTOMS">Customs Duties &amp; Tariffs</option>
                            <option value="HANDLING">Port Handling &amp; Storage</option>
                            <option value="INSURANCE">Transit Insurance</option>
                        </select>
                    </div>
                    <div class="acct-field" style="grid-column: span 2;">
                        <label class="acct-label">Stock Consumption Status</label>
                        <div style="font-size:12.5px;color:#64748b;margin-bottom:6px;">
                            Portion of stock already consumed or sold from this intake batch (0.0 = all on-hand, 1.0 = fully consumed):
                        </div>
                        <input type="number" step="0.05" min="0" max="1" class="acct-input acct-num" id="lcConsumedRatio" value="0.00" required>
                        <small style="font-size:11.5px;color:#0284c7;display:block;margin-top:4px;">
                            <i class="ph ph-shield-check"></i> <strong>Architectural Rule:</strong> The remaining on-hand ratio is capitalized to <code>INVENTORY_ASSET</code>, while the consumed ratio is sent directly to <code>COGS</code> to avoid rewriting past unit costs.
                        </small>
                    </div>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctLandedCostModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-check"></i> Post Landed Cost Split</button>
            </div>
        </form>
    </div>
</div>
@endsection
