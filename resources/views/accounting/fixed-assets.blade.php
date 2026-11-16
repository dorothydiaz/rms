@extends('layouts.app')

@section('title', 'Fixed Assets')

@section('content')
@php
    $initialTab = request()->query('tab', 'register');
    $validTabs = ['register', 'depreciation', 'disposals'];
    if (!in_array($initialTab, $validTabs, true)) $initialTab = 'register';
    $cur = $accountingPayload['baseCurrency'] ?? 'PHP';
    $assets = $accountingPayload['fixedAssets'] ?? [];
    $depreciations = $accountingPayload['fixedAssetDepreciations'] ?? [];
    $disposals = $accountingPayload['fixedAssetDisposals'] ?? [];

    $totalCost = 0.0;
    $totalAccum = 0.0;
    foreach ($assets as $a) {
        $totalCost += (float) ($a['acquisition_cost'] ?? $a->acquisition_cost ?? 0);
        $totalAccum += (float) ($a['accumulated_depreciation'] ?? $a->accumulated_depreciation ?? 0);
    }
    $totalNbv = max(0.0, $totalCost - $totalAccum);
@endphp

<div class="acct-workspace" id="accountingFaRoot" data-initial-tab="{{ $initialTab }}">
    @include('accounting.partials.system-toolbar', [
        'pageTitle' => 'Fixed Assets',
        'pageSubtitle' => 'Capital asset register, automated straight-line depreciation runs & disposals',
        'pageIcon' => 'ph-buildings',
        'accountingPayload' => $accountingPayload,
    ])

    <!-- KPI Summary Grid -->
    <div class="acct-kpi-grid-4">
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Gross Asset Cost</span><span class="rfq-kpi-icon"><i class="ph ph-buildings"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format($totalCost, 2) }}</div>
            <div class="rfq-kpi-sub">Total historical acquisition cost</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Accumulated Depreciation</span><span class="rfq-kpi-icon draft"><i class="ph ph-chart-line-down"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format($totalAccum, 2) }}</div>
            <div class="rfq-kpi-sub">Total recognized depreciation</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Net Book Value (NBV)</span><span class="rfq-kpi-icon awarded"><i class="ph ph-scales"></i></span></div>
            <div class="rfq-kpi-val acct-num" style="text-align:left;">{{ $cur }} {{ number_format($totalNbv, 2) }}</div>
            <div class="rfq-kpi-sub">Carrying value on balance sheet</div>
        </div>
        <div class="rfq-kpi-card">
            <div class="rfq-kpi-header"><span class="rfq-kpi-label">Registered Assets</span><span class="rfq-kpi-icon awaiting"><i class="ph ph-package"></i></span></div>
            <div class="rfq-kpi-val">{{ count($assets) }}</div>
            <div class="rfq-kpi-sub">Kitchen, POS, Leasehold &amp; Vehicles</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rfq-nav-tabs-bar" role="tablist">
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'register' ? ' active' : '' }}" data-acct-tab="register">
            <i class="ph ph-buildings"></i> <span>Asset Register</span>
            <span class="rfq-tab-count">{{ count($assets) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'depreciation' ? ' active' : '' }}" data-acct-tab="depreciation">
            <i class="ph ph-chart-line-down"></i> <span>Depreciation Schedules</span>
            <span class="rfq-tab-count">{{ count($depreciations) }}</span>
        </button>
        <button type="button" class="rfq-tab-btn{{ $initialTab === 'disposals' ? ' active' : '' }}" data-acct-tab="disposals">
            <i class="ph ph-swap"></i> <span>Disposals &amp; Transfers</span>
            <span class="rfq-tab-count">{{ count($disposals) }}</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: ASSET REGISTER -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'register' ? ' is-active' : '' }}" data-acct-pane="register">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="acct-panel-title"><i class="ph ph-buildings"></i> Capital Asset Register</h2>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnFaNewAsset">
                        <i class="ph ph-plus"></i> New Asset
                    </button>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Asset Tag / Code</th>
                            <th>Asset Name</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th>Acquired</th>
                            <th class="acct-num">Cost ({{ $cur }})</th>
                            <th class="acct-num">Accum Depr</th>
                            <th class="acct-num">Net Book Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="faRegisterTableBody">
                        @forelse($assets as $a)
                            @php
                                $cost = (float) ($a['acquisition_cost'] ?? $a->acquisition_cost ?? 0);
                                $accum = (float) ($a['accumulated_depreciation'] ?? $a->accumulated_depreciation ?? 0);
                                $nbv = (float) ($a['net_book_value'] ?? $a->net_book_value ?? ($cost - $accum));
                                $status = (string) ($a['status'] ?? $a->status ?? 'CAPITALIZED');
                                $aid = (string) ($a['asset_id'] ?? $a->asset_id);
                            @endphp
                            <tr data-asset-id="{{ $aid }}">
                                <td class="acct-mono" style="font-weight:700;color:#9333ea;">{{ $a['asset_code'] ?? $a->asset_code }}</td>
                                <td><strong>{{ $a['asset_name'] ?? $a->asset_name }}</strong></td>
                                <td><span class="acct-badge acct-badge-draft">{{ $a['asset_category'] ?? $a->asset_category }}</span></td>
                                <td>{{ $a['branch_id'] ?? $a->branch_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($a['acquisition_date'] ?? $a->acquisition_date)->toDateString() }}</td>
                                <td class="acct-num">{{ number_format($cost, 2) }}</td>
                                <td class="acct-num">{{ number_format($accum, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format($nbv, 2) }}</td>
                                <td><span class="acct-badge acct-badge-{{ strtolower($status) === 'disposed' ? 'danger' : 'posted' }}">{{ $status }}</span></td>
                                <td>
                                    @if($status !== 'DISPOSED')
                                        <button type="button" class="acct-row-btn btn-dispose-asset" data-asset-id="{{ $aid }}" data-asset-name="{{ $a['asset_name'] ?? $a->asset_name }}" data-nbv="{{ $nbv }}" title="Dispose Asset"><i class="ph ph-trash"></i> Dispose</button>
                                    @else
                                        <span style="color:#64748b;font-size:12px;">Disposed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center;padding:36px;color:#64748b;">
                                    <i class="ph ph-buildings" style="font-size:32px;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                                    No capital assets registered yet. Click <strong>New Asset</strong> to capitalize equipment or leasehold improvements.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: DEPRECIATION SCHEDULES -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'depreciation' ? ' is-active' : '' }}" data-acct-pane="depreciation">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="acct-panel-title"><i class="ph ph-chart-line-down"></i> Monthly Depreciation Runs</h2>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" class="rfq-btn rfq-btn-primary" id="btnFaRunDepreciation">
                        <i class="ph ph-play"></i> Run Monthly Depreciation
                    </button>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Date</th>
                            <th>Asset Code</th>
                            <th>Asset Name</th>
                            <th class="acct-num">Monthly Depreciation</th>
                            <th class="acct-num">Accumulated to Date</th>
                            <th class="acct-num">Net Book Value After</th>
                            <th>GL Journal</th>
                        </tr>
                    </thead>
                    <tbody id="faDepreciationTableBody">
                        @forelse($depreciations as $d)
                            @php
                                $dAmount = (float) ($d['depreciation_amount'] ?? $d->depreciation_amount ?? 0);
                                $accAfter = (float) ($d['accumulated_depreciation_after'] ?? $d->accumulated_depreciation_after ?? 0);
                                $nbvAfter = (float) ($d['net_book_value_after'] ?? $d->net_book_value_after ?? 0);
                                $asset = $d['asset'] ?? $d->asset ?? null;
                            @endphp
                            <tr>
                                <td>{{ $d['fiscal_period_id'] ?? $d->fiscal_period_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($d['depreciation_date'] ?? $d->depreciation_date)->toDateString() }}</td>
                                <td class="acct-mono">{{ $asset?->asset_code ?? 'FA-TAG' }}</td>
                                <td>{{ $asset?->asset_name ?? 'Capital Asset' }}</td>
                                <td class="acct-num">{{ number_format($dAmount, 2) }}</td>
                                <td class="acct-num">{{ number_format($accAfter, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;">{{ number_format($nbvAfter, 2) }}</td>
                                <td><span class="acct-mono" style="color:#9333ea;">{{ $d['journal_id'] ?? $d->journal_id }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:36px;color:#64748b;">
                                    <i class="ph ph-chart-line-down" style="font-size:32px;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                                    No depreciation runs executed yet. Click <strong>Run Monthly Depreciation</strong> to post straight-line amortization to GL.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 3: DISPOSALS & TRANSFERS -->
    <!-- ===================================================================== -->
    <div class="acct-tab-pane{{ $initialTab === 'disposals' ? ' is-active' : '' }}" data-acct-pane="disposals">
        <div class="acct-panel">
            <div class="acct-panel-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="acct-panel-title"><i class="ph ph-swap"></i> Asset Disposals, Retirements &amp; Transfers</h2>
                </div>
            </div>
            <div class="acct-table-wrap" style="min-height:360px;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Disposal Date</th>
                            <th>Asset Code</th>
                            <th>Asset Name</th>
                            <th>Type</th>
                            <th class="acct-num">Cost at Disposal</th>
                            <th class="acct-num">Accum Depr</th>
                            <th class="acct-num">Net Book Value</th>
                            <th class="acct-num">Proceeds</th>
                            <th class="acct-num">Gain / (Loss)</th>
                            <th>GL Journal</th>
                        </tr>
                    </thead>
                    <tbody id="faDisposalsTableBody">
                        @forelse($disposals as $disp)
                            @php
                                $dCost = (float) ($disp['cost_at_disposal'] ?? $disp->cost_at_disposal ?? 0);
                                $dAccum = (float) ($disp['accumulated_depreciation_at_disposal'] ?? $disp->accumulated_depreciation_at_disposal ?? 0);
                                $dNbv = (float) ($disp['net_book_value_at_disposal'] ?? $disp->net_book_value_at_disposal ?? 0);
                                $dProc = (float) ($disp['proceeds_amount'] ?? $disp->proceeds_amount ?? 0);
                                $dGain = (float) ($disp['gain_loss_amount'] ?? $disp->gain_loss_amount ?? 0);
                                $asset = $disp['asset'] ?? $disp->asset ?? null;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($disp['disposal_date'] ?? $disp->disposal_date)->toDateString() }}</td>
                                <td class="acct-mono">{{ $asset?->asset_code ?? 'FA-TAG' }}</td>
                                <td>{{ $asset?->asset_name ?? 'Capital Asset' }}</td>
                                <td><span class="acct-badge acct-badge-draft">{{ $disp['disposal_type'] ?? $disp->disposal_type }}</span></td>
                                <td class="acct-num">{{ number_format($dCost, 2) }}</td>
                                <td class="acct-num">{{ number_format($dAccum, 2) }}</td>
                                <td class="acct-num">{{ number_format($dNbv, 2) }}</td>
                                <td class="acct-num">{{ number_format($dProc, 2) }}</td>
                                <td class="acct-num" style="font-weight:700;color:{{ $dGain >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($dGain, 2) }}</td>
                                <td><span class="acct-mono" style="color:#9333ea;">{{ $disp['journal_id'] ?? $disp->journal_id }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center;padding:36px;color:#64748b;">
                                    <i class="ph ph-swap" style="font-size:32px;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                                    No assets have been disposed or retired.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Capital Fixed Asset -->
<div class="acct-backdrop" id="acctFaNewModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="New Fixed Asset">
        <div class="acct-modal-head">
            <h3><i class="ph ph-buildings"></i> Register New Capital Fixed Asset</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctFaNewModalBackdrop"><i class="ph ph-x"></i></button>
        </div>
        <form id="faNewAssetForm">
            <div class="acct-modal-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label">Asset Name</label>
                        <input type="text" class="acct-input" id="faName" placeholder="e.g. Rational Combi Oven Model 20" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Category</label>
                        <select class="acct-select" id="faCategory">
                            <option value="KITCHEN_EQUIPMENT">Kitchen Equipment</option>
                            <option value="POS_HARDWARE">POS &amp; IT Hardware</option>
                            <option value="FURNITURE_FIXTURES">Furniture &amp; Fixtures</option>
                            <option value="LEASEHOLD_IMPROVEMENTS">Leasehold Improvements</option>
                            <option value="VEHICLES">Delivery Vehicles</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Acquisition Date</label>
                        <input type="date" class="acct-input" id="faAcqDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Acquisition Cost ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="faCost" placeholder="0.00" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Salvage / Scrap Value</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="faSalvage" value="0.00">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Useful Life (Months)</label>
                        <input type="number" class="acct-input acct-num" id="faUsefulLife" value="60" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Branch</label>
                        <select class="acct-select" id="faBranch">
                            @foreach($accountingPayload['branches'] ?? [] as $b)
                                <option value="{{ $b->branch_id ?? $b['branch_id'] ?? 'BR-01' }}">{{ $b->branch_name ?? $b['branch_name'] ?? 'Main Branch' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Department</label>
                        <select class="acct-select" id="faDepartment">
                            <option value="">-- None --</option>
                            @foreach($accountingPayload['departments'] ?? [] as $d)
                                <option value="{{ $d->department_id ?? $d['department_id'] }}">{{ $d->department_name ?? $d['department_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label style="font-size:12.5px;display:inline-flex;align-items:center;gap:6px;">
                        <input type="checkbox" id="faCapitalizeImmediate" checked> Capitalize immediately (Post DR Asset Cost / CR Asset Clearing)
                    </label>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctFaNewModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-floppy-disk"></i> Save Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Run Monthly Depreciation -->
<div class="acct-backdrop" id="acctFaDeprModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Run Monthly Depreciation">
        <div class="acct-modal-head">
            <h3><i class="ph ph-play"></i> Execute Monthly Straight-Line Depreciation</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctFaDeprModalBackdrop"><i class="ph ph-x"></i></button>
        </div>
        <form id="faRunDeprForm">
            <div class="acct-modal-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field">
                        <label class="acct-label">Fiscal Period</label>
                        <select class="acct-select" id="faDeprPeriod" required>
                            @foreach($accountingPayload['periods'] ?? [] as $p)
                                @if(($p->status ?? $p['status'] ?? '') === 'OPEN')
                                    <option value="{{ $p->period_id ?? $p['period_id'] }}">{{ $p->period_name ?? $p['period_name'] ?? 'Period' }} ({{ $p->period_code ?? $p['period_code'] }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Posting Date</label>
                        <input type="date" class="acct-input" id="faDeprDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field" style="grid-column: span 2;">
                        <label class="acct-label">Branch Scope</label>
                        <select class="acct-select" id="faDeprBranch">
                            <option value="">-- All Branches --</option>
                            @foreach($accountingPayload['branches'] ?? [] as $b)
                                <option value="{{ $b->branch_id ?? $b['branch_id'] }}">{{ $b->branch_name ?? $b['branch_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p style="margin-top:12px;font-size:12.5px;color:#64748b;">
                    <i class="ph ph-info"></i> Calculates monthly depreciation for all capitalized assets in the selected branch scope and generates a multi-dimensional journal entry (DR <code>DEPRECIATION_EXPENSE</code> / CR <code>ACCUMULATED_DEPRECIATION</code>).
                </p>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctFaDeprModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-primary"><i class="ph ph-play"></i> Post Depreciation</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Dispose Fixed Asset -->
<div class="acct-backdrop" id="acctFaDisposeModalBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Dispose Fixed Asset">
        <div class="acct-modal-head">
            <h3><i class="ph ph-trash"></i> Asset Derecognition &amp; Disposal</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctFaDisposeModalBackdrop"><i class="ph ph-x"></i></button>
        </div>
        <form id="faDisposeForm">
            <input type="hidden" id="dispAssetId">
            <div class="acct-modal-body">
                <div class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="acct-field" style="grid-column:span 2;">
                        <label class="acct-label">Target Asset</label>
                        <input type="text" class="acct-input" id="dispAssetName" readonly style="background:#f1f5f9;">
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Disposal Date</label>
                        <input type="date" class="acct-input" id="dispDate" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Disposal Type</label>
                        <select class="acct-select" id="dispType">
                            <option value="SALE">SALE (Sold with proceeds)</option>
                            <option value="SCRAP">SCRAP (Zero proceeds written off)</option>
                            <option value="RETIREMENT">RETIREMENT (Taken out of service)</option>
                            <option value="CASUALTY">CASUALTY (Damage / Fire / Loss)</option>
                        </select>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Proceeds Amount ({{ $cur }})</label>
                        <input type="number" step="0.01" class="acct-input acct-num" id="dispProceeds" value="0.00" required>
                    </div>
                    <div class="acct-field">
                        <label class="acct-label">Reason / Notes</label>
                        <input type="text" class="acct-input" id="dispReason" placeholder="Replaced with new model">
                    </div>
                </div>
            </div>
            <div class="acct-modal-foot">
                <button type="button" class="rfq-btn rfq-btn-outline" data-acct-close="acctFaDisposeModalBackdrop">Cancel</button>
                <button type="submit" class="rfq-btn rfq-btn-danger"><i class="ph ph-trash"></i> Confirm Disposal</button>
            </div>
        </form>
    </div>
</div>
@endsection
