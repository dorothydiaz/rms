@extends('layouts.app')

@section('title', 'General Ledger')

@section('content')
@php
    $initialTab = request()->query('tab', 'journals');
    $validTabs = ['journals', 'coa', 'inquiry', 'recurring', 'period-close'];
    if (!in_array($initialTab, $validTabs, true)) {
        $initialTab = 'journals';
    }
@endphp

<div class="acct-workspace" id="accountingGlRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'General Ledger',
        'pageSubtitle' => 'Unified multi-dimensional journal engine, Chart of Accounts, drill-down inquiry, and period close',
        'pageIcon' => 'ph-books',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- Sub-module Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist" aria-label="General Ledger Sections">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'journals' ? ' active' : '' }}" data-acct-tab="journals">
            <i class="ph ph-note-pencil"></i> <span>Journal Entries</span>
            <span class="rfq-tab-count" id="glTabCountJournals">{{ count($accountingPayload['journals'] ?? []) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'coa' ? ' active' : '' }}" data-acct-tab="coa">
            <i class="ph ph-tree-structure"></i> <span>Chart of Accounts</span>
            <span class="rfq-tab-count" id="glTabCountCoa">{{ count($accountingPayload['accounts'] ?? []) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'inquiry' ? ' active' : '' }}" data-acct-tab="inquiry">
            <i class="ph ph-magnifying-glass"></i> <span>GL Inquiry</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'recurring' ? ' active' : '' }}" data-acct-tab="recurring">
            <i class="ph ph-arrows-clockwise"></i> <span>Recurring Journals</span>
            <span class="rfq-tab-count">{{ count($accountingPayload['recurringTemplates'] ?? []) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'period-close' ? ' active' : '' }}" data-acct-tab="period-close">
            <i class="ph ph-lock-key"></i> <span>Period Close</span>
        </button>
    </div>

    <!-- ================================================================
         TAB 1: JOURNAL ENTRIES (UNIFIED MANUAL / PAYROLL / ACCRUAL / SYSTEM VIEWER)
         ================================================================ -->
    <div class="acct-tab-pane{{ $initialTab === 'journals' ? ' is-active' : '' }}" data-acct-pane="journals">
        <!-- Journal Entry Builder Panel -->
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-file-text"></i> Journal Voucher Workspace</h2>
                    <span class="acct-badge acct-badge-draft" id="jeStatusBadge">NEW DRAFT</span>
                    <span class="acct-mono" id="jeNumberDisplay" style="color:#9333ea;">AUTO-NUMBERED</span>
                    <span class="acct-badge acct-badge-system" id="jeSourceLinkBadge" style="display:none;"></span>
                </div>

                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnJeNewDraft">
                        <i class="ph ph-plus"></i> New Draft
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnJeLoadTemplate">
                        <i class="ph ph-copy"></i> Load Template
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnJeOpenCsvModal">
                        <i class="ph ph-file-csv"></i> Excel / CSV Import
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-danger" id="btnJeOpenReverseModal" style="display:none;">
                        <i class="ph ph-arrow-u-up-left"></i> Reverse Entry
                    </button>
                </div>
            </div>

            <div class="acct-panel-body">
                <!-- Anti-bloat Read-Only Guardrail Banner for Posted / System-Generated Journals -->
                <div id="jeReadOnlyBanner" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-size:12.5px;align-items:center;justify-content:space-between;gap:10px;">
                    <span><i class="ph ph-lock-simple"></i> <strong id="jeReadOnlyText">Immutable Ledger Entry:</strong> Posted or system-generated journals cannot be modified directly. Use Reverse Entry to adjust.</span>
                </div>

                <!-- 4-Column Anti-Bloat Document Header -->
                <input type="hidden" id="jeJournalId" value="">
                <div class="acct-header-fields">
                    <div class="acct-field">
                        <label class="acct-label" for="jeDate">Posting Date</label>
                        <input type="date" class="acct-input" id="jeDate" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="jeType">Journal Type</label>
                        <select class="acct-select" id="jeType">
                            <option value="MANUAL">Manual Journal</option>
                            <option value="PAYROLL">Payroll Journal</option>
                            <option value="ACCRUAL">Accrual Entry</option>
                            <option value="ADJUSTING">Adjusting Entry</option>
                            <option value="OPENING_BALANCE">Opening Balance</option>
                            <option value="CLOSING">Closing Entry</option>
                            <option value="RECURRING">Recurring Journal</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="jeCurrency">Currency & FX Rate</label>
                        <div style="display:flex;gap:6px;">
                            <select class="acct-select" id="jeCurrency" style="width:90px;flex-shrink:0;"></select>
                            <input type="number" step="0.000001" min="0.000001" class="acct-input acct-num" id="jeFxRate" value="1.000000" title="Exchange Rate to Base Currency">
                        </div>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="jeDescription">Narration / Description</label>
                        <input type="text" class="acct-input" id="jeDescription" placeholder="Brief audit memo for this journal...">
                    </div>
                </div>

                <!-- Optional Metadata Progressive Disclosure (<20% usage) -->
                <details class="acct-details-box">
                    <summary><i class="ph ph-caret-right"></i> Optional Header Defaults (Default Branch & Template Selector)</summary>
                    <div class="acct-header-fields" style="margin-top:10px;grid-template-columns: repeat(3, 1fr);">
                        <div class="acct-field">
                            <label class="acct-label" for="jeHeaderBranch">Default Header Branch</label>
                            <select class="acct-select" id="jeHeaderBranch"></select>
                        </div>
                        <div class="acct-field">
                            <label class="acct-label" for="jeTemplatePreset">Template Preset</label>
                            <select class="acct-select" id="jeTemplatePreset"></select>
                        </div>
                        <div class="acct-field">
                            <label class="acct-label">Reversal Info</label>
                            <input type="text" class="acct-input" id="jeReversalInfo" readonly placeholder="Not reversed">
                        </div>
                    </div>
                </details>
            </div>

            <!-- 10-Row Non-Scrolling Journal Lines Grid -->
            <div class="acct-lines-table-wrap">
                <table class="acct-table acct-lines-table" id="jeLinesTable">
                    <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th style="min-width:220px;">Account (Code & Name)</th>
                            <th style="min-width:115px;">Branch</th>
                            <th style="min-width:115px;">Department</th>
                            <th style="min-width:95px;">Project</th>
                            <th style="min-width:95px;">Tax</th>
                            <th class="acct-num" style="min-width:115px;">Debit (Tx)</th>
                            <th class="acct-num" style="min-width:115px;">Credit (Tx)</th>
                            <th style="min-width:140px;">Line Memo</th>
                            <th style="width:44px;text-align:center;">Del</th>
                        </tr>
                    </thead>
                    <tbody id="jeLinesBody"></tbody>
                </table>
            </div>

            <div style="padding:8px 16px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                <button type="button" class="acct-row-btn" id="btnJeAddLine">
                    <i class="ph ph-plus"></i> Add Line
                </button>
                <span style="font-size:11.5px;color:#64748b;">10 rows visible by default without inner scrolling &bull; Dimensions stamped per line</span>
            </div>
        </div>

        <!-- Separate Compact Summary Card (Separated from Items Grid) -->
        <div class="acct-summary-card" id="jeSummaryCard">
            <div class="acct-summary-metrics">
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Total Functional Debit</span>
                    <span class="acct-summary-stat-val" id="jeSumDebit">0.00</span>
                </div>
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Total Functional Credit</span>
                    <span class="acct-summary-stat-val" id="jeSumCredit">0.00</span>
                </div>
                <div class="acct-summary-stat">
                    <span class="acct-summary-stat-label">Difference / Check</span>
                    <span class="acct-badge acct-badge-balanced" id="jeBalanceBadge">0.00 BALANCED</span>
                </div>
            </div>

            <div class="acct-summary-actions">
                <button type="button" class="rfq-btn rfq-btn-outline" id="btnJeSaveDraft">
                    <i class="ph ph-floppy-disk"></i> Save Draft
                </button>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnJePost">
                    <i class="ph ph-check-circle"></i> Post to GL
                </button>
            </div>
        </div>

        <!-- Journal Register List -->
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-list-magnifying-glass"></i> General Ledger Journal Register</h2>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="text" class="acct-input" id="jeRegisterSearch" placeholder="Search journal # or narration..." style="width:200px;height:32px;">
                    <select class="acct-select" id="jeRegisterTypeFilter" style="width:150px;height:32px;">
                        <option value="">All Types</option>
                        <option value="MANUAL">Manual</option>
                        <option value="PAYROLL">Payroll</option>
                        <option value="ACCRUAL">Accrual</option>
                        <option value="RECURRING">Recurring</option>
                        <option value="ADJUSTING">Adjusting</option>
                        <option value="OPENING_BALANCE">Opening Balance</option>
                        <option value="CLOSING">Closing</option>
                        <option value="AP_BILL">AP Bill (System)</option>
                        <option value="AR_INVOICE">AR Invoice (System)</option>
                        <option value="REVERSAL">Reversal</option>
                    </select>
                    <select class="acct-select" id="jeRegisterStatusFilter" style="width:130px;height:32px;">
                        <option value="">All Statuses</option>
                        <option value="DRAFT">Draft</option>
                        <option value="APPROVED">Approved</option>
                        <option value="POSTED">Posted</option>
                        <option value="REVERSED">Reversed</option>
                    </select>
                </div>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Journal #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Source Doc</th>
                            <th>Description</th>
                            <th class="acct-num">Debit (Base)</th>
                            <th class="acct-num">Credit (Base)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="jeRegisterBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================
         TAB 2: CHART OF ACCOUNTS (COMPANY-SCOPED + SYSTEM_ROLE PROTECTION)
         ================================================================ -->
    <div class="acct-tab-pane{{ $initialTab === 'coa' ? ' is-active' : '' }}" data-acct-pane="coa">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-tree-structure"></i> Chart of Accounts (Company-Scoped)</h2>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="text" class="acct-input" id="coaSearchInput" placeholder="Search code, name, or system role..." style="width:220px;height:32px;">
                    <select class="acct-select" id="coaTypeFilter" style="width:140px;height:32px;">
                        <option value="">All Types</option>
                        <option value="ASSET">Asset</option>
                        <option value="LIABILITY">Liability</option>
                        <option value="EQUITY">Equity</option>
                        <option value="REVENUE">Revenue</option>
                        <option value="COGS">COGS</option>
                        <option value="EXPENSE">Expense</option>
                    </select>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnCoaAddAccount">
                        <i class="ph ph-plus-circle"></i> Add GL Account
                    </button>
                </div>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Normal</th>
                            <th>Cash Flow</th>
                            <th>System Role</th>
                            <th class="acct-num">Net Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="coaTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================
         TAB 3: GL INQUIRY (ACCOUNT DRILL-DOWN TO SOURCE DOCUMENT)
         ================================================================ -->
    <div class="acct-tab-pane{{ $initialTab === 'inquiry' ? ' is-active' : '' }}" data-acct-pane="inquiry">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-magnifying-glass"></i> General Ledger Account Inquiry</h2>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <select class="acct-select" id="inquiryAccountSelect" style="width:280px;height:34px;"></select>
                    <select class="acct-select" id="inquiryBranchSelect" style="width:160px;height:34px;"></select>
                </div>
            </div>
            <div class="acct-panel-body" id="inquiryAccountSummary" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12.5px;"></div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Journal #</th>
                            <th>Type</th>
                            <th>Source Ref</th>
                            <th>Branch</th>
                            <th>Dept / Project</th>
                            <th>Narration</th>
                            <th class="acct-num">Debit</th>
                            <th class="acct-num">Credit</th>
                            <th class="acct-num">Running Net</th>
                        </tr>
                    </thead>
                    <tbody id="inquiryTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================
         TAB 4: RECURRING JOURNALS
         ================================================================ -->
    <div class="acct-tab-pane{{ $initialTab === 'recurring' ? ' is-active' : '' }}" data-acct-pane="recurring">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-arrows-clockwise"></i> Recurring Journal Templates</h2>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Template Name</th>
                            <th>Frequency</th>
                            <th>Next Run</th>
                            <th>Last Run</th>
                            <th>Mode</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="recurringTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================
         TAB 5: PERIOD CLOSE CHECKLIST & PERIOD LOCK/REOPEN
         ================================================================ -->
    <div class="acct-tab-pane{{ $initialTab === 'period-close' ? ' is-active' : '' }}" data-acct-pane="period-close">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-lock-key"></i> Period Close Checklist & Lock Controls</h2>
                <span style="font-size:12px;color:#64748b;">No journals can post into LOCKED or CLOSED periods</span>
            </div>
            <div class="acct-panel-body" style="border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                <div style="display:flex;gap:18px;flex-wrap:wrap;font-size:12.5px;align-items:center;">
                    <strong>Close Checklist:</strong>
                    <label style="display:inline-flex;align-items:center;gap:5px;"><input type="checkbox" class="acct-close-chk" value="subledgers" checked> AP & AR Subledgers Reconciled</label>
                    <label style="display:inline-flex;align-items:center;gap:5px;"><input type="checkbox" class="acct-close-chk" value="bank_recon" checked> Bank Reconciliation Complete</label>
                    <label style="display:inline-flex;align-items:center;gap:5px;"><input type="checkbox" class="acct-close-chk" value="recurring" checked> Recurring Amortizations Posted</label>
                    <label style="display:inline-flex;align-items:center;gap:5px;"><input type="checkbox" class="acct-close-chk" value="fx_reval" checked> FX Revaluation Verified</label>
                </div>
            </div>
            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Locked By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="periodCloseTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart of Accounts Add/Edit Modal -->
<div class="acct-backdrop" id="acctCoaModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="GL Account Form">
        <div class="acct-modal-head">
            <h3 id="coaModalTitle"><i class="ph ph-tree-structure"></i> Add GL Account</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctCoaModalBackdrop"><i class="ph ph-x"></i> Close</button>
        </div>
        <form id="acctCoaForm">
            <div class="acct-modal-body">
                <input type="hidden" id="coaAccountId">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label">Account Code</label>
                        <input type="text" class="acct-input acct-mono" id="coaCode" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Account Name</label>
                        <input type="text" class="acct-input" id="coaName" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Account Type</label>
                        <select class="acct-select" id="coaType">
                            <option value="ASSET">ASSET</option>
                            <option value="LIABILITY">LIABILITY</option>
                            <option value="EQUITY">EQUITY</option>
                            <option value="REVENUE">REVENUE</option>
                            <option value="COGS">COGS</option>
                            <option value="EXPENSE">EXPENSE</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Normal Balance</label>
                        <select class="acct-select" id="coaNormal">
                            <option value="DEBIT">DEBIT</option>
                            <option value="CREDIT">CREDIT</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Cash Flow Category</label>
                        <select class="acct-select" id="coaCashFlow">
                            <option value="OPERATING">OPERATING</option>
                            <option value="INVESTING">INVESTING</option>
                            <option value="FINANCING">FINANCING</option>
                            <option value="CASH_EQUIVALENT">CASH_EQUIVALENT</option>
                            <option value="NONE">NONE</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">System Role (Optional)</label>
                        <select class="acct-select" id="coaSystemRole"></select>
                    </div>
                </div>
                <div style="margin-top:12px;display:flex;gap:16px;">
                    <label style="font-size:12.5px;display:inline-flex;align-items:center;gap:6px;">
                        <input type="checkbox" id="coaAllowManual" checked> Allow Manual Journal Posting
                    </label>
                    <label style="font-size:12.5px;display:inline-flex;align-items:center;gap:6px;">
                        <input type="checkbox" id="coaIsActive" checked> Active
                    </label>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctCoaModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-floppy-disk"></i> Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Journal Reversal Modal -->
<div class="acct-backdrop" id="acctReverseModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Reverse Posted Journal">
        <div class="acct-modal-head">
            <h3><i class="ph ph-arrow-u-up-left"></i> Reverse Posted Journal</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctReverseModalBackdrop"><i class="ph ph-x"></i> Close</button>
        </div>
        <form id="acctReverseForm">
            <div class="acct-modal-body">
                <input type="hidden" id="reverseJournalId">
                <p style="font-size:13px;color:#475569;margin-top:0;">Posting a reversal creates an equal-and-opposite <strong>REVERSAL</strong> entry while keeping the original voucher intact in the audit trail.</p>
                <div class="acct-header-fields" style="grid-template-columns: 1fr;">
                    <div class="acct-field">
                        <label class="acct-label">Reversal Posting Date</label>
                        <input type="date" class="acct-input" id="reverseDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Reason for Reversal</label>
                        <input type="text" class="acct-input" id="reverseReason" placeholder="e.g. Incorrect branch dimension / period adjustment" required>
                    </div>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctReverseModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-danger"><i class="ph ph-arrow-u-up-left"></i> Post Reversal Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- Excel / CSV Journal Lines Import Modal -->
<div class="acct-backdrop" id="acctCsvModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Import Journal CSV">
        <div class="acct-modal-head">
            <h3><i class="ph ph-file-csv"></i> Import Journal Lines from CSV</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctCsvModalBackdrop"><i class="ph ph-x"></i> Close</button>
        </div>
        <div class="acct-modal-body">
            <p style="font-size:12.5px;color:#475569;margin-top:0;">
                Format: <code>account_code,branch_id,department_id,project_code,debit,credit,description</code>
            </p>
            <input type="file" id="acctCsvFileInput" accept=".csv,.txt" style="margin-bottom:10px;font-size:12.5px;">
            <textarea id="acctCsvTextArea" class="acct-input" style="height:150px;padding:10px;font-family:monospace;font-size:12px;" placeholder="6100,BR-01,DEPT-OPS,PROJ-Q4,15000,0,Rent expense&#10;1020,BR-01,DEPT-OPS,PROJ-Q4,0,15000,Bank transfer"></textarea>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctCsvModalBackdrop">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnAcctCsvApply"><i class="ph ph-upload-simple"></i> Load Into Lines Grid</button>
        </div>
    </div>
</div>
@endsection

