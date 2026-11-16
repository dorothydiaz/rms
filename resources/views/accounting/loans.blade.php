@extends('layouts.app')

@section('title', 'Loans & Debt Facilities')

@section('content')
@php
    $initialTab = request()->query('tab', 'register');
    $validTabs = ['register', 'schedule', 'payments'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'register';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
    $loans = $accountingPayload['loans'] ?? [];
    $loanPayments = $accountingPayload['loanPayments'] ?? [];

    $totalPrincipal = 0.0;
    $totalOutstanding = 0.0;
    foreach ($loans as $l) {
        $totalPrincipal += (float) ($l['principal_amount'] ?? $l->principal_amount ?? 0);
        $totalOutstanding += (float) ($l['outstanding_principal'] ?? $l->outstanding_principal ?? 0);
    }
    $totalPaidPrincipal = max(0.0, $totalPrincipal - $totalOutstanding);
@endphp

<div class="acct-workspace" id="accountingLoansRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Loans & Debt Facilities',
        'pageSubtitle' => 'Bank borrowings, amortization schedules, P/I splits & debt repayment tracking',
        'pageIcon' => 'ph-currency-circle-dollar',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- KPI Summary Grid -->
    <div class="acct-kpi-grid-4">
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Total Debt Facilities</span><span class="rfq-kpi-icon"><i class="ph ph-bank"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format($totalPrincipal, 2) }}</div>
            <div class="rfq-kpi-sub">Total original borrowing principal</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Outstanding Principal</span><span class="rfq-kpi-icon draft"><i class="ph ph-receipt"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format($totalOutstanding, 2) }}</div>
            <div class="rfq-kpi-sub">Remaining balance on books</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Principal Repaid</span><span class="rfq-kpi-icon awarded"><i class="ph ph-check-circle"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format($totalPaidPrincipal, 2) }}</div>
            <div class="rfq-kpi-sub">Amortized debt reduction</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Active Facilities</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-notebook"></i></span></div>
            <div class="rfq-kpi-val">{{ count($loans) }}</div>
            <div class="rfq-kpi-sub">Bank, lease &amp; owner loans</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'register' ? ' active' : '' }}" data-acct-tab="register">
            <i class="ph ph-notebook"></i> <span>Loan Register</span>
            <span class="rfq-tab-count">{{ count($loans) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'schedule' ? ' active' : '' }}" data-acct-tab="schedule">
            <i class="ph ph-calendar"></i> <span>Amortization Schedule</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'payments' ? ' active' : '' }}" data-acct-tab="payments">
            <i class="ph ph-money"></i> <span>Loan Payments</span>
            <span class="rfq-tab-count">{{ count($loanPayments) }}</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: LOAN REGISTER -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'register' ? ' is-active' : '' }}" data-acct-pane="register">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="acct-panel-title"><i class="ph ph-notebook"></i> Loan &amp; Debt Facility Register</h2>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnLoanNewFacility">
                        <i class="ph ph-plus"></i> New Loan Facility
                    </button>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Loan Reference</th>
                            <th>Lender / Bank</th>
                            <th>Branch</th>
                            <th class="acct-num">Principal ({{ $cur }})</th>
                            <th class="acct-num">Rate %</th>
                            <th class="acct-num">Term (Mo)</th>
                            <th class="acct-num">Outstanding Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="loanRegisterTableBody">
                        @forelse($loans as $ln)
                            @php
                                $p = (float) ($ln['principal_amount'] ?? $ln->principal_amount ?? 0);
                                $out = (float) ($ln['outstanding_principal'] ?? $ln->outstanding_principal ?? 0);
                                $rate = (float) ($ln['interest_rate_pct'] ?? $ln->interest_rate_pct ?? 0);
                                $term = (int) ($ln['term_months'] ?? $ln->term_months ?? 0);
                                $status = (string) ($ln['status'] ?? $ln->status ?? 'ACTIVE');
                                $lid = (string) ($ln['loan_id'] ?? $ln->loan_id);
                            @endphp
                            <tr data-loan-id="{{ $lid }}">
                                <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $ln['loan_reference'] ?? $ln->loan_reference }}</td>
                                <td><strong>{{ $ln['lender_name'] ?? $ln->lender_name }}</strong></td>
                                <td>{{ $ln['branch_id'] ?? $ln->branch_id }}</td>
                                <td class="acct-num">{{ number_format($p, 2) }}</td>
                                <td class="acct-num">{{ number_format($rate, 2) }}%</td>
                                <td class="acct-num">{{ $term }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format($out, 2) }}</td>
                                <td><span class="acct-badge acct-badge-{{ strtolower($status) === 'fully_paid' ? 'awarded' : 'posted' }}">{{ $status }}</span></td>
                                <td>
                                    <button type="button" class="acct-row-btn btn-view-amortization" data-loan-id="{{ $lid }}"><i class="ph ph-calendar"></i> Schedule</button>
                                    @if($status === 'ACTIVE')
                                        <button type="button" class="acct-row-btn btn-pay-loan" data-loan-id="{{ $lid }}" data-loan-ref="{{ $ln['loan_reference'] ?? $ln->loan_reference }}" data-out="{{ $out }}"><i class="ph ph-money"></i> Pay</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:36px;color:#64748b;">
                                    <i class="ph ph-notebook" style="font-size:32px;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                                    No debt facilities registered yet. Click <strong>New Loan Facility</strong> to record borrowings and generate amortization schedules.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: AMORTIZATION SCHEDULE -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'schedule' ? ' is-active' : '' }}" data-acct-pane="schedule">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <h2 class="acct-panel-title"><i class="ph ph-calendar"></i> Principal &amp; Interest Amortization Schedule</h2>
                    <select class="acct-select" id="loanScheduleSelector" style="width:260px;">
                        <option value="">-- Select Loan Facility --</option>
                        @foreach($loans as $ln)
                            <option value="{{ $ln['loan_id'] ?? $ln->loan_id }}">{{ $ln['loan_reference'] ?? $ln->loan_reference }} - {{ $ln['lender_name'] ?? $ln->lender_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th class="acct-num">Total Due ({{ $cur }})</th>
                            <th class="acct-num">Principal</th>
                            <th class="acct-num">Interest</th>
                            <th class="acct-num">Ending Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="loanScheduleTableBody">
                        <tr>
                            <td colspan="7" style="text-align:center;padding:36px;color:#64748b;">
                                Select a loan facility above to view its monthly Principal &amp; Interest amortization schedule.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: LOAN PAYMENTS -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'payments' ? ' is-active' : '' }}" data-acct-pane="payments">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="acct-panel-title"><i class="ph ph-money"></i> Loan Repayment Transactions</h2>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Loan Reference</th>
                            <th>Lender</th>
                            <th class="acct-num">Principal Paid ({{ $cur }})</th>
                            <th class="acct-num">Interest Paid</th>
                            <th class="acct-num">Penalty Paid</th>
                            <th class="acct-num">Total Paid</th>
                            <th>GL Journal Voucher</th>
                        </tr>
                    </thead>
                    <tbody id="loanPaymentsTableBody">
                        @forelse($loanPayments as $lp)
                            @php
                                $pr = (float) ($lp['principal_paid'] ?? $lp->principal_paid ?? 0);
                                $in = (float) ($lp['interest_paid'] ?? $lp->interest_paid ?? 0);
                                $pen = (float) ($lp['penalty_paid'] ?? $lp->penalty_paid ?? 0);
                                $tot = (float) ($lp['total_paid'] ?? $lp->total_paid ?? ($pr + $in + $pen));
                                $loan = $lp['loan'] ?? $lp->loan ?? null;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($lp['payment_date'] ?? $lp->payment_date)->toDateString() }}</td>
                                <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $loan?->loan_reference ?? 'LOAN-REF' }}</td>
                                <td>{{ $loan?->lender_name ?? 'Lender' }}</td>
                                <td class="acct-num">{{ number_format($pr, 2) }}</td>
                                <td class="acct-num">{{ number_format($in, 2) }}</td>
                                <td class="acct-num">{{ number_format($pen, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format($tot, 2) }}</td>
                                <td><span class="acct-mono" style="color:#9333ea;">{{ $lp['journal_id'] ?? $lp->journal_id }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:36px;color:#64748b;">
                                    <i class="ph ph-money" style="font-size:32px;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                                    No loan repayments recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Loan Facility -->
<div class="acct-backdrop" id="acctLoanNewModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="New Loan Facility">
        <div class="acct-modal-head">
            <h3><i class="ph ph-bank"></i> Record New Borrowing / Loan Facility</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctLoanNewModalBackdrop"><i class="ph ph-x"></i></button>
        </div>
        <form id="loanNewFacilityForm">
            <div class="acct-modal-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label">Loan Reference / Account #</label>
                        <input type="text" class="acct-input acct-mono" id="loanRef" placeholder="BDO-TERM-2026-01" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Lender / Financial Institution</label>
                        <input type="text" class="acct-input" id="loanLender" placeholder="e.g. BDO Unibank, Inc." required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Principal Amount ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="loanPrincipal" placeholder="1000000.00" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Annual Interest Rate %</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="loanRate" value="8.50" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Term in Months</label>
                        <input type="number" class="acct-input acct-num" id="loanTerm" value="24" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Start Date</label>
                        <input type="date" class="acct-input" id="loanStartDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Interest Method</label>
                        <select class="acct-select" id="loanMethod">
                            <option value="ANNUITY">Amortized Annuity (Equal Monthly Payment)</option>
                            <option value="FLAT_RATE">Flat Rate (Equal Principal + Flat Interest)</option>
                            <option value="REDUCING_BALANCE">Reducing Balance</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Deposit Bank Account</label>
                        <select class="acct-select" id="loanBankAcc" required>
                            @foreach($accountingPayload['bankAccounts'] ?? [] as $b)
                                <option value="{{ $b->bank_account_id ?? $b['bank_account_id'] }}">{{ $b->bank_name ?? $b['bank_name'] }} - {{ $b->account_name ?? $b['account_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctLoanNewModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-floppy-disk"></i> Disburse &amp; Create Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Loan Payment -->
<div class="acct-backdrop" id="acctLoanPayModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Record Loan Payment">
        <div class="acct-modal-head">
            <h3><i class="ph ph-money"></i> Record Debt Repayment Voucher</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctLoanPayModalBackdrop"><i class="ph ph-x"></i></button>
        </div>
        <form id="loanRecordPaymentForm">
            <input type="hidden" id="payLoanId">
            <div class="acct-modal-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field" style="grid-column:span 2;">
                        <label class="acct-label">Loan Facility</label>
                        <input type="text" class="acct-input" id="payLoanRef" readonly style="background:#f1f5f9;">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Payment Date</label>
                        <input type="date" class="acct-input" id="payDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Principal Amount ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="payPrincipal" placeholder="0.00" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Interest Expense Amount ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="payInterest" placeholder="0.00" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Penalty / Late Fee</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="payPenalty" value="0.00">
                    </div>
                    <div class="acct-field" style="grid-column:span 2;">
                        <label class="acct-label">Paid From Bank Account</label>
                        <select class="acct-select" id="payBankAcc" required>
                            @foreach($accountingPayload['bankAccounts'] ?? [] as $b)
                                <option value="{{ $b->bank_account_id ?? $b['bank_account_id'] }}">{{ $b->bank_name ?? $b['bank_name'] }} - {{ $b->account_name ?? $b['account_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctLoanPayModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-check"></i> Post Payment Voucher</button>
            </div>
        </form>
    </div>
</div>
@endsection
