@extends('layouts.app')

@section('title', 'Accounting Configuration')

@section('content')
@php
    $initialTab = request()->query('tab', 'fiscal');
    $validTabs = ['fiscal', 'terms', 'fx', 'approvals', 'numbering'];
    if (!in_array($initialTab, $validTabs, true)) {
        $initialTab = 'fiscal';
    }
@endphp

<div class="acct-workspace" id="accountingConfigRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Accounting Configuration',
        'pageSubtitle' => 'Fiscal calendars, payment terms, currencies, approval workflows, and numbering series',
        'pageIcon' => 'ph-sliders-horizontal',
        'accountingPayload' => $accountingPayload,
    ])

    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'fiscal' ? ' active' : '' }}" data-acct-tab="fiscal">
            <i class="ph ph-calendar-blank"></i> <span>Fiscal Years & Periods</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'terms' ? ' active' : '' }}" data-acct-tab="terms">
            <i class="ph ph-clock"></i> <span>Payment Terms</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'fx' ? ' active' : '' }}" data-acct-tab="fx">
            <i class="ph ph-currency-dollar"></i> <span>Currencies & FX Rates</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'approvals' ? ' active' : '' }}" data-acct-tab="approvals">
            <i class="ph ph-git-branch"></i> <span>Approval Workflows</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'numbering' ? ' active' : '' }}" data-acct-tab="numbering">
            <i class="ph ph-hash"></i> <span>Numbering Series & Branches</span>
        </button>
    </div>

    <!-- TAB 1: FISCAL YEARS & PERIODS -->
    <div class="acct-tab-pane{{ $initialTab === 'fiscal' ? ' is-active' : '' }}" data-acct-pane="fiscal">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-calendar-blank"></i> Fiscal Calendar</h2>
                <a href="{{ route('accounting.gl', ['tab' => 'period-close']) }}" class="acct-row-btn">Manage Locks in Period Close</a>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Period Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="cfgFiscalTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: PAYMENT TERMS -->
    <div class="acct-tab-pane{{ $initialTab === 'terms' ? ' is-active' : '' }}" data-acct-pane="terms">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-clock"></i> Payment Terms</h2>
            </div>
            <div class="acct-panel-body" style="border-bottom:1px solid #e2e8f0;">
                <form id="cfgPaymentTermForm" class="acct-header-fields">
                    <div class="acct-field">
                        <label class="acct-label">Term Code</label>
                        <input type="text" class="acct-input acct-mono" id="cfgTermCode" placeholder="NET_45" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Description</label>
                        <input type="text" class="acct-input" id="cfgTermName" placeholder="Net 45 Days" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Due Days</label>
                        <input type="number" class="acct-input acct-num" id="cfgTermDays" value="30" min="0" max="365" required>
                    </div>
                    <div class="acct-field">
                        <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-plus"></i> Save Term</button>
                    </div>
                </form>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="acct-num">Due Days</th>
                            <th class="acct-num">Discount %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="cfgTermsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: CURRENCIES & FX RATES -->
    <div class="acct-tab-pane{{ $initialTab === 'fx' ? ' is-active' : '' }}" data-acct-pane="fx">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-currency-dollar"></i> Currencies & Exchange Rates (to PHP)</h2>
            </div>
            <div class="acct-panel-body" style="border-bottom:1px solid #e2e8f0;">
                <form id="cfgFxForm" class="acct-header-fields">
                    <div class="acct-field">
                        <label class="acct-label">Currency</label>
                        <select class="acct-select" id="cfgFxCurrency">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Rate to Base (PHP)</label>
                        <input type="number" step="0.0001" min="0.0001" class="acct-input acct-num" id="cfgFxRate" placeholder="57.5000" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Effective Date</label>
                        <input type="date" class="acct-input" id="cfgFxDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field">
                        <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-plus"></i> Add FX Rate</button>
                    </div>
                </form>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Pair</th>
                            <th class="acct-num">Rate</th>
                            <th>Effective Date</th>
                            <th>Updated By</th>
                        </tr>
                    </thead>
                    <tbody id="cfgFxTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: APPROVAL WORKFLOWS -->
    <div class="acct-tab-pane{{ $initialTab === 'approvals' ? ' is-active' : '' }}" data-acct-pane="approvals">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-git-branch"></i> Maker-Checker Approval Workflows</h2>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Workflow Name</th>
                            <th>Document Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="cfgWorkflowTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 5: NUMBERING SERIES & BRANCHES -->
    <div class="acct-tab-pane{{ $initialTab === 'numbering' ? ' is-active' : '' }}" data-acct-pane="numbering">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-hash"></i> Document Numbering Series (Row-Locked + Gap Controlled)</h2>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Series Code</th>
                            <th>Document Name</th>
                            <th>Prefix</th>
                            <th class="acct-num">Next #</th>
                            <th class="acct-num">Padding</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cfgNumberingTableBody"></tbody>
                </table>
            </div>
        </div>

        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-buildings"></i> Line Dimensions: Active Branches & Departments</h2>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Dimension</th>
                            <th>Code</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody id="cfgDimensionsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

