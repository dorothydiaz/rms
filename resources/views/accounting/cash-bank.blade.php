@extends('layouts.app')

@section('title', 'Cash & Bank')

@section('content')
@php
    $initialTab = request()->query('tab', 'accounts');
    $validTabs = ['accounts', 'advances', 'transfers', 'checks', 'recon'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'accounts';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
@endphp

<div class="acct-workspace" id="accountingCashBankRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Cash & Bank Management',
        'pageSubtitle' => 'Bank accounts, petty cash funds, employee advances (separate subledger), check register & bank reconciliation',
        'pageIcon' => 'ph-bank',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- Cash & Bank KPI Summary Cards -->
    <div class="acct-kpi-grid-4">
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Cash Position</span><span class="rfq-kpi-icon awarded"><i class="ph ph-money"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format((float) ($accountingPayload['kpis']['cashPosition'] ?? 0), 2) }}</div>
            <div class="rfq-kpi-sub">Cash on Hand + Cash in Bank balances</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Bank Accounts &amp; Funds</span><span class="rfq-kpi-icon"><i class="ph ph-bank"></i></span></div>
            <div class="rfq-kpi-val">{{ count($accountingPayload['bankAccounts'] ?? []) }}</div>
            <div class="rfq-kpi-sub">Checking, Savings &amp; Petty Cash funds</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Active Advances</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-user-circle"></i></span></div>
            <div class="rfq-kpi-val">{{ collect($accountingPayload['employeeAdvances'] ?? [])->where('status', 'DISBURSED')->count() }}</div>
            <div class="rfq-kpi-sub">Employee advance subledger (non-AP)</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">PDCs in Monitoring</span><span class="rfq-kpi-icon draft"><i class="ph ph-calendar-check"></i></span></div>
            <div class="rfq-kpi-val">{{ (int) ($accountingPayload['kpis']['upcomingPdcMaturities'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Awaiting maturity clearance</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'accounts' ? ' active' : '' }}" data-acct-tab="accounts">
            <i class="ph ph-bank"></i> <span>Bank Accounts &amp; Petty Cash</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'advances' ? ' active' : '' }}" data-acct-tab="advances">
            <i class="ph ph-user-circle"></i> <span>Employee Advances &amp; Liquidations</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'transfers' ? ' active' : '' }}" data-acct-tab="transfers">
            <i class="ph ph-arrows-clockwise"></i> <span>Fund Transfers</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'checks' ? ' active' : '' }}" data-acct-tab="checks">
            <i class="ph ph-checks"></i> <span>Check Register &amp; PDC Clearance</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'recon' ? ' active' : '' }}" data-acct-tab="recon">
            <i class="ph ph-scale"></i> <span>Bank Statement Reconciliation</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: BANK ACCOUNTS & PETTY CASH -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'accounts' ? ' is-active' : '' }}" data-acct-pane="accounts">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-bank"></i> Configured Accounts &amp; Imprest Funds</h2>
                </div>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbNewAccount">
                    <i class="ph ph-plus"></i> New Bank / Cash Fund
                </button>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="cbAccountsTable">
                    <thead>
                        <tr>
                            <th>Account Name</th>
                            <th>Bank / Institution</th>
                            <th>Account #</th>
                            <th>Type</th>
                            <th>GL Linked Account</th>
                            <th>Custodian</th>
                            <th class="acct-num">Current Book Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accountingPayload['bankAccounts'] ?? [] as $ba)
                            <tr>
                                <td style="font-weight:600;color:#0f172a;">{{ $ba->account_name }}</td>
                                <td>{{ $ba->bank_name }}</td>
                                <td class="acct-mono">{{ $ba->account_number }}</td>
                                <td><span class="acct-badge">{{ $ba->account_type }}</span></td>
                                <td>
                                    <span class="acct-mono" style="font-size:12px;">{{ $ba->glAccount?->account_code }} - {{ $ba->glAccount?->account_name }}</span>
                                </td>
                                <td>{{ $ba->custodian_name ?: 'N/A' }}</td>
                                <td class="acct-num" style="font-weight:700;color:{{ (float) $ba->current_balance < 0 ? '#ef4444' : '#0f172a' }};">
                                    {{ $cur }} {{ number_format((float) $ba->current_balance, 2) }}
                                </td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ $ba->is_active ? 'posted' : 'draft' }}">
                                        {{ $ba->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No bank accounts or petty cash funds configured yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: EMPLOYEE ADVANCES & LIQUIDATIONS -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'advances' ? ' is-active' : '' }}" data-acct-pane="advances">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-user-circle"></i> Employee Advances &amp; Liquidations Subledger</h2>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="rfq-btn rfq-btn-outline" id="btnCbNewLiquidation">
                        <i class="ph ph-receipt"></i> Liquidate Advance
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbNewAdvance">
                        <i class="ph ph-plus"></i> Disburse Advance
                    </button>
                </div>
            </div>
            <div class="acct-panel-body">
                <div style="padding:10px 14px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;font-size:12.5px;color:#475569;display:flex;align-items:center;gap:8px;">
                    <i class="ph ph-info" style="font-size:16px;color:#2563eb;"></i>
                    <span><strong>Enterprise Subledger Isolation:</strong> Employee advances are posted strictly to <code>EMPLOYEE_ADVANCES</code> (Asset) and tracked per employee. They are completely excluded from AP trade aging to safeguard vendor payable integrity.</span>
                </div>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="cbAdvancesTable">
                    <thead>
                        <tr>
                            <th>Advance #</th>
                            <th>Disbursed Date</th>
                            <th>Employee</th>
                            <th>Purpose</th>
                            <th>Settlement Due</th>
                            <th class="acct-num">Disbursed Amount</th>
                            <th class="acct-num">Liquidated</th>
                            <th class="acct-num">Balance Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="cbAdvancesBody">
                        @forelse($accountingPayload['employeeAdvances'] ?? [] as $adv)
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $adv->advance_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($adv->disbursement_date ?: $adv->request_date)->format('Y-m-d') }}</td>
                                <td>{{ $adv->employee_name }}</td>
                                <td>{{ $adv->purpose }}</td>
                                <td>{{ $adv->settlement_due_date ? \Carbon\Carbon::parse($adv->settlement_due_date)->format('Y-m-d') : 'N/A' }}</td>
                                <td class="acct-num">{{ number_format((float) $adv->amount, 2) }}</td>
                                <td class="acct-num" style="color:#10b981;">{{ number_format((float) $adv->liquidated_amount, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;color:{{ (float) $adv->balance_due > 0 ? '#ef4444' : '#10b981' }};">
                                    {{ number_format((float) $adv->balance_due, 2) }}
                                </td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ strtolower($adv->status) }}">
                                        {{ $adv->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No employee advances disbursed yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: FUND TRANSFERS -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'transfers' ? ' is-active' : '' }}" data-acct-pane="transfers">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-arrows-clockwise"></i> Inter-Account Fund Transfers</h2>
                </div>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbNewTransfer">
                    <i class="ph ph-plus"></i> New Fund Transfer
                </button>
            </div>

            <div class="acct-table-responsive">
                <table class="acct-table" id="cbTransfersTable">
                    <thead>
                        <tr>
                            <th>Transfer #</th>
                            <th>Date</th>
                            <th>Source Account</th>
                            <th>Destination Account</th>
                            <th>Reference #</th>
                            <th class="acct-num">Transfer Amount</th>
                            <th class="acct-num">Bank Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="cbTransfersBody">
                        @forelse($accountingPayload['fundTransfers'] ?? [] as $trf)
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $trf->transfer_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($trf->transfer_date)->format('Y-m-d') }}</td>
                                <td>{{ $trf->fromBankAccount?->account_name ?: $trf->source_bank_account_id }}</td>
                                <td>{{ $trf->toBankAccount?->account_name ?: $trf->destination_bank_account_id }}</td>
                                <td>{{ $trf->reference_number ?: 'N/A' }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format((float) $trf->amount, 2) }}</td>
                                <td class="acct-num">{{ number_format((float) $trf->fee_amount, 2) }}</td>
                                <td><span class="acct-badge acct-badge-posted">{{ $trf->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No inter-account fund transfers recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 4: CHECK REGISTER & PDC CLEARANCE -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'checks' ? ' is-active' : '' }}" data-acct-pane="checks">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-checks"></i> Check Register &amp; Post-Dated Check (PDC) Lifecycle</h2>
            </div>
            <div class="acct-table-responsive">
                <table class="acct-table" id="cbChecksTable">
                    <thead>
                        <tr>
                            <th>Check #</th>
                            <th>Direction</th>
                            <th>Party / Payee / Drawer</th>
                            <th>Drawn Bank</th>
                            <th>Issue Date</th>
                            <th>Maturity Date</th>
                            <th class="acct-num">Amount</th>
                            <th>Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cbChecksBody">
                        @forelse($accountingPayload['checkRegister'] ?? [] as $chk)
                            <tr>
                                <td class="acct-mono" style="font-weight:600;">{{ $chk->check_number }}</td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ $chk->direction === 'ISSUED' ? 'draft' : 'awarded' }}">
                                        {{ $chk->direction }}
                                    </span>
                                </td>
                                <td>{{ $chk->party_name }}</td>
                                <td>{{ $chk->bank_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($chk->check_date)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($chk->maturity_date)->format('Y-m-d') }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format((float) $chk->amount, 2) }}</td>
                                <td>
                                    <span class="acct-badge acct-badge-{{ strtolower($chk->status) }}">
                                        {{ $chk->status }}
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    @if(in_array($chk->status, ['ISSUED', 'RECEIVED', 'DEPOSITED']))
                                        <button type="button" class="rfq-btn rfq-btn-primary btnCbClearCheck" data-check-id="{{ $chk->check_id }}">
                                            <i class="ph ph-check"></i> Clear PDC
                                        </button>
                                    @else
                                        <span style="font-size:12px;color:#94a3b8;">Cleared</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">
                                    No checks in register.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 5: BANK STATEMENT RECONCILIATION -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'recon' ? ' is-active' : '' }}" data-acct-pane="recon">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <h2 class="acct-panel-title"><i class="ph ph-scale"></i> Bank Statement Reconciliation</h2>
            </div>
            <div class="acct-panel-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label" for="cbReconBank">Select Bank Account</label>
                        <select class="acct-select" id="cbReconBank">
                            @foreach($accountingPayload['bankAccounts'] ?? [] as $ba)
                                <option value="{{ $ba->bank_account_id }}" data-balance="{{ $ba->current_balance }}">
                                    {{ $ba->account_name }} ({{ $ba->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="cbReconDate">Statement Cut-Off Date</label>
                        <input type="date" class="acct-input" id="cbReconDate" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label" for="cbReconEndingBal">Bank Statement Ending Balance ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="cbReconEndingBal" value="0.00">
                    </div>
                </div>

                <div style="margin-top:16px;padding:16px;background:#f8fafc;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-size:12px;color:#64748b;">GL Book Balance:</div>
                        <div class="acct-num" style="font-size:16px;font-weight:700;" id="cbReconBookBalDisplay">{{ $cur }} 0.00</div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#64748b;">Statement Ending Balance:</div>
                        <div class="acct-num" style="font-size:16px;font-weight:700;" id="cbReconStmtBalDisplay">{{ $cur }} 0.00</div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#64748b;">Unreconciled Variance:</div>
                        <div class="acct-num" style="font-size:16px;font-weight:700;color:#10b981;" id="cbReconVarianceDisplay">0.00 BALANCED</div>
                    </div>
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbFinalizeRecon">
                        <i class="ph ph-check-circle"></i> Save &amp; Finalize Reconciliation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- NEW BANK ACCOUNT MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="cbAccountModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:550px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-bank"></i> Add Bank Account / Petty Cash Fund</h3>
            <button type="button" class="acct-modal-close" id="btnCbCloseAccountModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="cbAccName">Account Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="cbAccName" placeholder="e.g. BDO Operating Account">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAccBank">Bank Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="cbAccBank" placeholder="e.g. BDO Unibank">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAccNumber">Account Number <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="cbAccNumber" placeholder="Account # or Fund Code">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAccType">Account Type <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbAccType">
                        <option value="CHECKING">Checking / Current Account</option>
                        <option value="SAVINGS">Savings Account</option>
                        <option value="PETTY_CASH">Petty Cash Fund (Imprest)</option>
                    </select>
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="cbAccGlAccount">Linked GL Account <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbAccGlAccount">
                        @foreach($accountingPayload['accounts'] ?? [] as $acc)
                            @if(in_array($acc['account_type'], ['ASSET']))
                                <option value="{{ $acc['account_id'] }}">{{ $acc['account_code'] }} - {{ $acc['account_name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAccOpeningBal">Opening Balance ({{ $cur }})</label>
                    <input type="number" step="0.01" class="acct-input acct-num" id="cbAccOpeningBal" value="0.00">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAccCustodian">Custodian (if Petty Cash)</label>
                    <input type="text" class="acct-input" id="cbAccCustodian" placeholder="e.g. Maria Santos">
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnCbCancelAccount">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbSubmitAccount">
                <i class="ph ph-check"></i> Save Account
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- EMPLOYEE ADVANCE MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="cbAdvanceModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:550px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-user-circle"></i> Disburse Employee Cash Advance</h3>
            <button type="button" class="acct-modal-close" id="btnCbCloseAdvanceModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="cbAdvEmployee">Employee <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbAdvEmployee">
                        <option value="">-- Select Employee --</option>
                        @foreach($accountingPayload['employees'] ?? [] as $emp)
                            <option value="{{ $emp->employee_id }}" data-name="{{ $emp->first_name }} {{ $emp->last_name }}">
                                {{ $emp->last_name }}, {{ $emp->first_name }} ({{ $emp->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAdvDate">Disbursement Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="cbAdvDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAdvAmount">Advance Amount ({{ $cur }}) <span style="color:#ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="acct-input acct-num" id="cbAdvAmount" value="0.00">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbAdvDueDate">Liquidation Due Date</label>
                    <input type="date" class="acct-input" id="cbAdvDueDate" value="{{ now()->addDays(15)->toDateString() }}">
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="cbAdvDisbursementAcc">Disbursing Cash / Bank Account <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbAdvDisbursementAcc">
                        @foreach($accountingPayload['bankAccounts'] ?? [] as $ba)
                            <option value="{{ $ba->bank_account_id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="cbAdvPurpose">Business Purpose <span style="color:#ef4444;">*</span></label>
                    <input type="text" class="acct-input" id="cbAdvPurpose" placeholder="e.g. Travel allowance for Visayas branch audit">
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnCbCancelAdvance">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbSubmitAdvance">
                <i class="ph ph-check"></i> Disburse Advance
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- ADVANCE LIQUIDATION MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="cbLiquidationModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:700px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-receipt"></i> Liquidate Employee Advance</h3>
            <button type="button" class="acct-modal-close" id="btnCbCloseLiquidationModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field" style="grid-column: span 2;">
                    <label class="acct-label" for="cbLiqAdvance">Select Open Advance <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbLiqAdvance">
                        <option value="">-- Select Advance --</option>
                        @foreach($accountingPayload['employeeAdvances'] ?? [] as $adv)
                            @if(in_array($adv->status, ['DISBURSED', 'PARTIALLY_LIQUIDATED']))
                                <option value="{{ $adv->advance_id }}" data-bal="{{ $adv->balance_due }}" data-emp="{{ $adv->employee_name }}">
                                    {{ $adv->advance_number }} - {{ $adv->employee_name }} (Bal: {{ number_format((float) $adv->balance_due, 2) }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbLiqDate">Liquidation Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="cbLiqDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbLiqRefundBank">Settlement Bank/Cash Account</label>
                    <select class="acct-select" id="cbLiqRefundBank">
                        @foreach($accountingPayload['bankAccounts'] ?? [] as $ba)
                            <option value="{{ $ba->bank_account_id }}">{{ $ba->account_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h4 style="margin:16px 0 8px 0;font-size:13.5px;font-weight:600;color:#0f172a;">Expense Items Breakdown</h4>
            <div class="acct-lines-table-wrap">
                <table class="acct-table acct-lines-table" id="cbLiqItemsTable">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Expense Account</th>
                            <th style="min-width:180px;">Description</th>
                            <th class="acct-num" style="min-width:110px;">Amount</th>
                            <th style="width:40px;">Del</th>
                        </tr>
                    </thead>
                    <tbody id="cbLiqItemsBody"></tbody>
                </table>
            </div>
            <div style="padding:6px 0;">
                <button type="button" class="acct-row-btn" id="btnCbAddLiqItem">
                    <i class="ph ph-plus"></i> Add Expense Item
                </button>
            </div>

            <div style="margin-top:12px;padding:12px;background:#f8fafc;border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:11.5px;color:#64748b;">Total Expenses:</div>
                    <div class="acct-num" style="font-size:15px;font-weight:700;" id="cbLiqTotalExpDisplay">{{ $cur }} 0.00</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11.5px;color:#64748b;" id="cbLiqDiffLabel">Refund Due to Company:</div>
                    <div class="acct-num" style="font-size:15px;font-weight:700;color:#10b981;" id="cbLiqDiffDisplay">{{ $cur }} 0.00</div>
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnCbCancelLiquidation">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbSubmitLiquidation">
                <i class="ph ph-check"></i> Post Liquidation
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- FUND TRANSFER MODAL -->
<!-- ========================================================================= -->
<div class="acct-modal-backdrop" id="cbTransferModalBackdrop" style="display:none;">
    <div class="acct-modal" style="max-width:550px;">
        <div class="acct-modal-head">
            <h3 class="acct-modal-title"><i class="ph ph-arrows-clockwise"></i> Execute Inter-Account Fund Transfer</h3>
            <button type="button" class="acct-modal-close" id="btnCbCloseTransferModal">&times;</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                <div class="acct-field">
                    <label class="acct-label" for="cbTrfFrom">From Account <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbTrfFrom">
                        <option value="">-- Source Account --</option>
                        @foreach($accountingPayload['bankAccounts'] ?? [] as $ba)
                            <option value="{{ $ba->bank_account_id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbTrfTo">To Account <span style="color:#ef4444;">*</span></label>
                    <select class="acct-select" id="cbTrfTo">
                        <option value="">-- Destination Account --</option>
                        @foreach($accountingPayload['bankAccounts'] ?? [] as $ba)
                            <option value="{{ $ba->bank_account_id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbTrfDate">Transfer Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" class="acct-input" id="cbTrfDate" value="{{ now()->toDateString() }}">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbTrfAmount">Transfer Amount ({{ $cur }}) <span style="color:#ef4444;">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="acct-input acct-num" id="cbTrfAmount" value="0.00">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbTrfFee">Bank Transfer Fee ({{ $cur }})</label>
                    <input type="number" step="0.01" min="0" class="acct-input acct-num" id="cbTrfFee" value="0.00">
                </div>
                <div class="acct-field">
                    <label class="acct-label" for="cbTrfRef">Reference #</label>
                    <input type="text" class="acct-input" id="cbTrfRef" placeholder="Online confirmation #">
                </div>
            </div>
        </div>
        <div class="acct-modal-foot">
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnCbCancelTransfer">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnCbSubmitTransfer">
                <i class="ph ph-check"></i> Post Transfer
            </button>
        </div>
    </div>
</div>
@endsection
