@extends('layouts.app')

@section('title', 'Reordering Rules & Replenishment Control Tower - RMS Inventory')

@push('styles')
    @vite('resources/css/modules/reorder-rules.css')
@endpush

@push('blade-data')
<script>
    window.ReorderRulesConfig = Object.freeze({
        initialRules: @json($initialRules ?? []),
        initialSimulation: @json($initialSimulation ?? ['summary' => [], 'items' => []]),
        branches: @json($branches ?? []),
        items: @json($items ?? []),
        vendors: @json($vendors ?? []),
        warehouses: @json($warehouses ?? []),
        csrfToken: @json(csrf_token()),
        routes: {
            rulesApi: @json(route('inventory.api.reorder-rules')),
            saveRuleApi: @json(route('inventory.api.reorder-rules.save')),
            toggleRuleApi: @json(url('/inventory/api/reorder-rules')),
            deleteRuleApi: @json(url('/inventory/api/reorder-rules')),
            simulateApi: @json(route('inventory.api.reorder-rules.simulate')),
            generateDraftsApi: @json(route('inventory.api.reorder-rules.generate-drafts')),
            purchaseOrdersPage: @json(route('purchase.purchase-orders')),
            internalTransfersPage: @json(route('inventory.internal-transfer')),
            itemMasterPage: @json(route('inventory.product-categories')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/reorder-rules.js')
@endpush

@section('content')
<div class="rr-workspace" id="reorderRulesApp">

    <!-- Toast Notifications Container -->
    <div class="rr-toast-container" id="rrToastContainer"></div>

    <!-- 1. Page Header Row -->
    <div class="rr-header-row">
        <div class="rr-title-group">
            <div class="rr-title-icon">
                <i class="ph ph-arrows-clockwise"></i>
            </div>
            <div class="rr-title-text">
                <h1>Reordering Rules &amp; Replenishment Control Tower</h1>
                <p>Automated inventory replenishment par levels, commissary push, Min-Max thresholds &amp; 1-click batch PO / Transfer generator</p>
            </div>
        </div>
        <div class="rr-header-actions">
            <a href="{{ route('inventory.product-categories') }}" class="rr-btn rr-btn-secondary" title="View Item Master Catalog">
                <i class="ph ph-folder-simple"></i>
                <span>Item Master</span>
            </a>
            <button type="button" class="rr-btn rr-btn-accent" onclick="runSimulationCheck()">
                <i class="ph ph-lightning"></i>
                <span>Run Simulator Check</span>
            </button>
            <button type="button" class="rr-btn rr-btn-secondary" onclick="openBatchDraftModal()">
                <i class="ph ph-sparkle"></i>
                <span>Generate Drafts</span>
            </button>
            <button type="button" class="rr-btn rr-btn-primary" onclick="openCreateRuleModal()">
                <i class="ph ph-plus-circle"></i>
                <span>New Reorder Rule</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Metrics Ribbon -->
    <div class="rr-kpi-grid">
        <div class="rr-kpi-card" style="--rr-kpi-accent: #9333ea; --rr-kpi-icon-bg: #f5f3ff;">
            <div class="rr-kpi-icon"><i class="ph ph-git-branch"></i></div>
            <div class="rr-kpi-info">
                <span class="rr-kpi-label">Active Reorder Rules</span>
                <span class="rr-kpi-value" id="kpiActiveRules">0</span>
                <span class="rr-kpi-subtext" id="kpiActiveSub">Across all branch locations</span>
            </div>
        </div>
        <div class="rr-kpi-card" style="--rr-kpi-accent: #ef4444; --rr-kpi-icon-bg: #fef2f2;">
            <div class="rr-kpi-icon"><i class="ph ph-warning-circle"></i></div>
            <div class="rr-kpi-info">
                <span class="rr-kpi-label">Under-Par Deficits</span>
                <span class="rr-kpi-value" id="kpiTriggeredCount">0</span>
                <span class="rr-kpi-subtext" id="kpiTriggeredSub">Action required: stock below min par</span>
            </div>
        </div>
        <div class="rr-kpi-card" style="--rr-kpi-accent: #0284c7; --rr-kpi-icon-bg: #f0f9ff;">
            <div class="rr-kpi-icon"><i class="ph ph-shopping-cart-simple"></i></div>
            <div class="rr-kpi-info">
                <span class="rr-kpi-label">Est. PO Reorder Value</span>
                <span class="rr-kpi-value" id="kpiPoValue">₱0.00</span>
                <span class="rr-kpi-subtext" id="kpiPoSub">Suggested vendor purchase total</span>
            </div>
        </div>
        <div class="rr-kpi-card" style="--rr-kpi-accent: #10b981; --rr-kpi-icon-bg: #ecfdf5;">
            <div class="rr-kpi-icon"><i class="ph ph-arrows-left-right"></i></div>
            <div class="rr-kpi-info">
                <span class="rr-kpi-label">Commissary Push Units</span>
                <span class="rr-kpi-value" id="kpiTransferUnits">0 Units</span>
                <span class="rr-kpi-subtext" id="kpiTransferSub">Pending internal transfers from hub</span>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Bar -->
    <div class="rr-tabs-bar">
        <div class="rr-tabs-group">
            <button type="button" class="rr-tab-btn active" data-tab="rules" onclick="switchRrTab('rules')">
                <i class="ph ph-list-dashes"></i>
                <span>All Reordering Rules</span>
                <span class="rr-tab-badge" id="tabBadgeRules">0</span>
            </button>
            <button type="button" class="rr-tab-btn" data-tab="simulator" onclick="switchRrTab('simulator')">
                <i class="ph ph-radar"></i>
                <span>Replenishment Control Tower (Live Simulator)</span>
                <span class="rr-tab-badge" id="tabBadgeSimulator" style="background:#fee2e2; color:#b91c1c;">0</span>
            </button>
            <button type="button" class="rr-tab-btn" data-tab="history" onclick="switchRrTab('history')">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Execution History &amp; Drafts</span>
                <span class="rr-tab-badge" id="tabBadgeHistory">0</span>
            </button>
        </div>
        <div class="rr-tabs-filter">
            <label for="branchGlobalFilter" style="font-size: 0.74rem; font-weight: 600; color: #64748b;">Branch:</label>
            <select id="branchGlobalFilter" class="rr-select" onchange="filterRulesByBranch(this.value)">
                <option value="all">All Branches (Global)</option>
                @foreach($branches as $b)
                    <option value="{{ $b->branch_id }}">{{ $b->branch_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- 4. Tab Panes -->
    <div class="rr-content-card">

        <!-- TAB 1: ALL REORDERING RULES -->
        <div class="rr-tab-pane active" id="pane-rules">
            <div class="rr-toolbar">
                <div class="rr-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="ruleSearchInput" class="rr-search-input" placeholder="Search by rule code, SKU, or item name..." oninput="handleRuleSearch(this.value)">
                </div>
                <div class="rr-filters-group">
                    <select id="strategyFilter" class="rr-select" onchange="applyFilters()">
                        <option value="all">All Strategies</option>
                        <option value="min_max">Min-Max (Order Point)</option>
                        <option value="commissary_push">Commissary Push</option>
                        <option value="jit_back_to_back">JIT Demand Driven</option>
                    </select>
                    <select id="routeFilter" class="rr-select" onchange="applyFilters()">
                        <option value="all">All Routes</option>
                        <option value="purchase_order">Purchase Order (PO)</option>
                        <option value="internal_transfer">Internal Transfer (TRF)</option>
                    </select>
                    <select id="statusFilter" class="rr-select" onchange="applyFilters()">
                        <option value="all">All Status</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
            </div>

            <div class="rr-table-container">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">Active</th>
                            <th>Rule Code</th>
                            <th>Branch Location</th>
                            <th>Product SKU &amp; Item Name</th>
                            <th>Strategy</th>
                            <th class="num">Min Par</th>
                            <th class="num">Max Par</th>
                            <th class="num">MOQ</th>
                            <th>Route &amp; Fulfillment Partner</th>
                            <th>Lead Time</th>
                            <th>Auto-Draft</th>
                            <th style="text-align: right; width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rulesTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: REPLENISHMENT CONTROL TOWER (LIVE SIMULATOR) -->
        <div class="rr-tab-pane" id="pane-simulator">
            <div class="rr-sim-banner">
                <div class="rr-sim-banner-text">
                    <i class="ph ph-info" style="font-size: 1.1rem;"></i>
                    <span>
                        <strong>Net Available Stock Formula:</strong>
                        <code>Net Available = On Hand + In-Transit POs - Committed</code>. 
                        When <code>Net Available &le; Min Par</code>, required order quantity is rounded up to MOQ:
                        <code>Ceil((Max - Net) / MOQ) &times; MOQ</code>.
                    </span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="rr-btn rr-btn-secondary" onclick="runSimulationCheck()" style="font-size: 0.74rem; padding: 5px 10px;">
                        <i class="ph ph-arrows-clockwise"></i>
                        <span>Re-evaluate</span>
                    </button>
                    <button type="button" class="rr-btn rr-btn-primary" onclick="openBatchDraftModal()" style="font-size: 0.74rem; padding: 5px 10px;">
                        <i class="ph ph-sparkle"></i>
                        <span>Draft All Triggered</span>
                    </button>
                </div>
            </div>

            <div class="rr-table-container" style="margin-top: 12px;">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th>Health State</th>
                            <th>Branch</th>
                            <th>Product &amp; SKU</th>
                            <th class="num">On-Hand</th>
                            <th class="num">In-Transit</th>
                            <th class="num" style="background:#faf5ff;">Net Available</th>
                            <th class="num">Min Reorder</th>
                            <th class="num">Max Par</th>
                            <th class="num" style="font-weight: 800; color: #9333ea;">Order Qty</th>
                            <th class="num">Est. Cost</th>
                            <th>Fulfillment Route</th>
                            <th style="text-align: right; width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="simulatorTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: EXECUTION HISTORY & DRAFTS -->
        <div class="rr-tab-pane" id="pane-history">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
                <p style="font-size: 0.78rem; color: #64748b; margin: 0;">
                    Log of automated replenishment runs, generated Purchase Order drafts, and Internal Transfer requisitions.
                </p>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ route('purchase.purchase-orders') }}" class="rr-btn rr-btn-secondary" style="font-size: 0.74rem; padding: 5px 10px;">
                        <i class="ph ph-arrow-square-out"></i>
                        <span>Open Purchase Orders Module</span>
                    </a>
                    <a href="{{ route('inventory.internal-transfer') }}" class="rr-btn rr-btn-secondary" style="font-size: 0.74rem; padding: 5px 10px;">
                        <i class="ph ph-arrow-square-out"></i>
                        <span>Open Internal Transfers Module</span>
                    </a>
                </div>
            </div>

            <div class="rr-table-container">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Document Reference</th>
                            <th>Document Type</th>
                            <th>Source &rarr; Destination</th>
                            <th class="num">Item Count</th>
                            <th class="num">Total Value / Units</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 5. CREATE / EDIT RULE MODAL -->
    <div class="rr-modal-backdrop" id="ruleModalBackdrop" onclick="handleBackdropClick(event)">
        <div class="rr-modal-card">
            <div class="rr-modal-header">
                <div class="rr-modal-title">
                    <i class="ph ph-sliders-horizontal" style="color: var(--rr-brand);"></i>
                    <span id="ruleModalTitle">Configure Reordering Rule</span>
                </div>
                <button type="button" class="rr-modal-close-btn" onclick="closeRuleModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <form id="ruleForm" onsubmit="handleRuleFormSubmit(event)">
                <input type="hidden" id="formRuleId" name="id">
                <div class="rr-modal-body">
                    <div class="rr-form-grid">
                        
                        <!-- Branch Selector -->
                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formBranchSelect">
                                <span>Target Branch Location <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <select id="formBranchSelect" class="rr-form-select" required onchange="handleFormBranchChange(this.value)">
                                @foreach($branches as $b)
                                    <option value="{{ $b->branch_id }}" data-name="{{ $b->branch_name }}">{{ $b->branch_name }} ({{ $b->branch_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Product Selector -->
                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formProductSelect">
                                <span>Product / SKU <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <select id="formProductSelect" class="rr-form-select" required onchange="handleFormProductChange(this.value)">
                                <option value="">-- Select Product Master --</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}" data-sku="{{ $it->sku }}" data-name="{{ $it->name }}" data-category="{{ $it->category }}" data-uom="{{ $it->uom }}" data-cost="{{ $it->cost_price }}">
                                        {{ $it->sku }} — {{ $it->name }} ({{ $it->uom }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Strategy -->
                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formStrategy">
                                <span>Replenishment Strategy <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <select id="formStrategy" class="rr-form-select" required>
                                <option value="min_max">Min-Max (Order Point)</option>
                                <option value="commissary_push">Central Commissary Push</option>
                                <option value="jit_back_to_back">JIT / Demand Driven</option>
                            </select>
                        </div>

                        <!-- Action Route -->
                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formActionRoute">
                                <span>Action Fulfillment Route <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <select id="formActionRoute" class="rr-form-select" required onchange="handleFormRouteToggle(this.value)">
                                <option value="purchase_order">Purchase Order (External Vendor)</option>
                                <option value="internal_transfer">Internal Transfer (Central Commissary)</option>
                            </select>
                        </div>

                        <!-- Min Par & Max Par -->
                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formMinStock">
                                <span>Min Stock / Reorder Point <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <input type="number" step="0.01" min="0" id="formMinStock" class="rr-form-input" required placeholder="e.g. 15.00">
                            <span class="rr-form-hint">Triggers order when Net Available &le; Min</span>
                        </div>

                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formMaxStock">
                                <span>Max Target Par Stock <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <input type="number" step="0.01" min="0.01" id="formMaxStock" class="rr-form-input" required placeholder="e.g. 50.00">
                            <span class="rr-form-hint">Target capacity stock to replenish up to</span>
                        </div>

                        <!-- MOQ & Safety Buffer -->
                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formMoq">
                                <span>Minimum Order Qty (MOQ) <strong style="color: #ef4444;">*</strong></span>
                            </label>
                            <input type="number" step="0.01" min="1" id="formMoq" class="rr-form-input" required value="1.00">
                            <span class="rr-form-hint">Order multiples (crate, sack, box packaging)</span>
                        </div>

                        <div class="rr-form-group">
                            <label class="rr-form-label" for="formLeadTime">
                                <span>Supplier Lead Time (Days)</span>
                            </label>
                            <input type="number" min="0" id="formLeadTime" class="rr-form-input" value="2">
                            <span class="rr-form-hint">Days required for shipment delivery</span>
                        </div>

                        <!-- Vendor Section (Visible if Route == purchase_order) -->
                        <div class="rr-form-group full" id="groupVendorSection">
                            <label class="rr-form-label" for="formVendorSelect">
                                <span>Preferred Vendor Partner</span>
                            </label>
                            <select id="formVendorSelect" class="rr-form-select">
                                <option value="">-- Direct Supply / Default Vendor --</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" data-name="{{ $v->legal_name }}">{{ $v->legal_name }} ({{ $v->vendor_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Commissary Section (Visible if Route == internal_transfer) -->
                        <div class="rr-form-group full" id="groupCommissarySection" style="display: none;">
                            <label class="rr-form-label" for="formWarehouseSelect">
                                <span>Source Central Commissary / Warehouse</span>
                            </label>
                            <select id="formWarehouseSelect" class="rr-form-select">
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->warehouse_id }}" data-name="{{ $wh->warehouse_name }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Notes & Checkbox -->
                        <div class="rr-form-group full">
                            <label class="rr-form-label" for="formNotes">Notes &amp; Operational Instructions</label>
                            <input type="text" id="formNotes" class="rr-form-input" placeholder="e.g. Keep extra buffer during weekend peak sales.">
                        </div>

                        <div class="rr-form-group full" style="display: flex; flex-direction: row; align-items: center; gap: 10px; margin-top: 4px;">
                            <input type="checkbox" id="formAutoDraft" style="width: 16px; height: 16px; accent-color: var(--rr-brand); cursor: pointer;">
                            <label for="formAutoDraft" style="font-size: 0.78rem; font-weight: 600; color: #1e293b; cursor: pointer;">
                                Enable Automated Batch Draft Generation (Automatically queue in PO / Transfer run)
                            </label>
                        </div>

                    </div>
                </div>
                <div class="rr-modal-footer">
                    <button type="button" class="rr-btn rr-btn-secondary" onclick="closeRuleModal()">Cancel</button>
                    <button type="submit" class="rr-btn rr-btn-primary" id="btnSaveRule">
                        <i class="ph ph-check"></i>
                        <span>Save Rule</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. BATCH DRAFT CONFIRMATION MODAL -->
    <div class="rr-modal-backdrop" id="batchModalBackdrop" onclick="handleBatchBackdropClick(event)">
        <div class="rr-modal-card" style="width: 720px;">
            <div class="rr-modal-header">
                <div class="rr-modal-title">
                    <i class="ph ph-sparkle" style="color: var(--rr-brand);"></i>
                    <span>Generate Replenishment Drafts</span>
                </div>
                <button type="button" class="rr-modal-close-btn" onclick="closeBatchDraftModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <div class="rr-modal-body">
                <div id="batchModalSummaryText" style="font-size: 0.8rem; color: #475569; line-height: 1.5;">
                    Evaluating live par level deficits...
                </div>
                <div class="rr-table-container" style="max-height: 280px; overflow-y: auto; margin-top: 10px;">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Item &amp; SKU</th>
                                <th>Branch</th>
                                <th class="num">Net Avail</th>
                                <th class="num">Min</th>
                                <th class="num">Order Qty</th>
                                <th>Route</th>
                                <th>Target Partner / Source</th>
                            </tr>
                        </thead>
                        <tbody id="batchModalTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="rr-modal-footer">
                <button type="button" class="rr-btn rr-btn-secondary" onclick="closeBatchDraftModal()">Cancel</button>
                <button type="button" class="rr-btn rr-btn-primary" id="btnConfirmBatchDraft" onclick="executeBatchDraftGeneration()">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span>Confirm &amp; Create Drafts</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

