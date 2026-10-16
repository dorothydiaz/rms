@extends('layouts.app')

@section('title', 'Stocks Overview - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/stocks-overview.css')
@endpush

@push('blade-data')
<script>
    window.StocksOverviewConfig = Object.freeze({
        routes: {
            stocksOverviewData: @json(route('inventory.api.stocks-overview-data')),
        },
        data: {
            initialProducts: @json($initialProducts ?? []),
            initialCategories: @json($initialCategories ?? []),
            initialLedger: @json($initialLedger ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/stocks-overview.js')
@endpush

@section('content')

<div class="stk-page-container">

    <!-- 1. Page Header Section -->
    <header class="stk-header-bar">
        <div class="stk-header-title-box">
            <div class="stk-header-icon">
                <i class="ph ph-squares-four"></i>
            </div>
            <div>
                <h1>Stocks Overview</h1>
                <p>Real-time inventory ledger tracking beginning balances, operational movements, cycle count audits, in-transit shipments, and projected availability.</p>
            </div>
        </div>
        <div class="stk-header-actions">
            <!-- Item Master -->
            <a href="{{ route('inventory.product-categories') }}" class="stk-btn-secondary" title="View Item Master Catalog">
                <i class="ph ph-folder-simple"></i>
                <span>Item Master</span>
            </a>
            <!-- Bill of Materials -->
            <a href="{{ route('inventory.recipe-management') }}" class="stk-btn-secondary" title="View Bill of Materials (BOM)">
                <i class="ph ph-cooking-pot"></i>
                <span>BOM Recipes</span>
            </a>
            <!-- Sync / Refresh -->
            <button type="button" class="stk-btn-secondary" onclick="refreshStocksData()" title="Recompute Live Inventory Movements">
                <i class="ph ph-arrows-clockwise"></i>
                <span>Refresh</span>
            </button>
            <!-- Print Report -->
            <button type="button" class="stk-btn-secondary" onclick="window.print()" title="Print Stocks Overview Report">
                <i class="ph ph-printer"></i>
                <span>Print</span>
            </button>
            <!-- Export CSV -->
            <button type="button" class="stk-btn-primary" onclick="exportStocksCSV()" title="Export 10-Column Ledger to CSV">
                <i class="ph ph-file-arrow-down"></i>
                <span>Export CSV</span>
            </button>
        </div>
    </header>

    <!-- 2. KPI Summary Bar (5 Stat Cards) -->
    <section class="stk-metrics-grid">
        <!-- Metric 1: Physical On-Hand -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-onhand">
                <i class="ph ph-package"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Physical On-Hand</span>
                <span class="stk-metric-value" id="kpiTotalOnHand">0 units</span>
                <span class="stk-metric-subtext" id="kpiOnHandValuation">Valuation: ₱0.00</span>
            </div>
        </div>

        <!-- Metric 2: Stock In Flow -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-in">
                <i class="ph ph-arrow-circle-down-right"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Period Inflow (+)</span>
                <span class="stk-metric-value" id="kpiTotalStockIn">+0 units</span>
                <span class="stk-metric-subtext" id="kpiStockInBreakdown">Purchases & Transfers In</span>
            </div>
        </div>

        <!-- Metric 3: Stock Out Flow -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-out">
                <i class="ph ph-arrow-circle-up-right"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Period Outflow (-)</span>
                <span class="stk-metric-value" id="kpiTotalStockOut">-0 units</span>
                <span class="stk-metric-subtext" id="kpiStockOutBreakdown">POS Sales & Recipe Usage</span>
            </div>
        </div>

        <!-- Metric 4: In-Transit Pipeline -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-transit">
                <i class="ph ph-truck"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">In-Transit Pipeline</span>
                <span class="stk-metric-value" id="kpiTotalInTransit">0 net</span>
                <span class="stk-metric-subtext" id="kpiInTransitBreakdown">Incoming vs Outgoing</span>
            </div>
        </div>

        <!-- Metric 5: Low Stock & Reorder Alerts -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-alert">
                <i class="ph ph-warning-circle"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Stock Status Alerts</span>
                <span class="stk-metric-value" id="kpiLowStockCount">0 Items</span>
                <span class="stk-metric-subtext" id="kpiLowStockSub">At or below reorder threshold</span>
            </div>
        </div>
    </section>

    <!-- 3. Redesigned Filter Ribbon with Single Date Range Picker -->
    <section class="stk-filter-ribbon-card">
        <div class="stk-filter-ribbon-row">
            <!-- Search Bar -->
            <div class="stk-search-box">
                <i class="ph ph-magnifying-glass stk-search-icon"></i>
                <input type="text" id="stkSearchInput" class="stk-search-input" placeholder="Search item name, SKU code, or barcode..." autocomplete="off">
                <span class="stk-search-shortcut">/</span>
            </div>

            <!-- Redesigned Unified Single Date Range Picker Component -->
            <div class="stk-daterange-picker-wrapper" id="stkDateRangePickerWrap">
                <button type="button" class="stk-daterange-trigger" id="stkDateRangeTrigger" onclick="toggleDateRangePopover(event)" aria-haspopup="true" aria-expanded="false" title="Click to modify operational date window">
                    <div class="stk-daterange-trigger-icon">
                        <i class="ph ph-calendar"></i>
                    </div>
                    <div class="stk-daterange-trigger-content">
                        <span class="stk-daterange-tag">Operational Period</span>
                        <span class="stk-daterange-label" id="stkDateRangeDisplay">This Month (Sep 01 - Sep 29, 2026)</span>
                    </div>
                    <i class="ph ph-caret-down stk-daterange-caret" id="stkDateRangeCaret"></i>
                </button>

                <!-- Popover Dropdown -->
                <div class="stk-daterange-popover" id="stkDateRangePopover">
                    <div class="stk-popover-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-calendar-blank" style="color: var(--stk-primary); font-size: 16px;"></i>
                            <span class="stk-popover-title">Select Date Interval</span>
                        </div>
                        <button type="button" class="stk-popover-close-btn" onclick="closeDateRangePopover()" title="Close (Esc)">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>
                    <div class="stk-popover-body">
                        <!-- Left: Presets List -->
                        <div class="stk-popover-presets">
                            <span class="stk-popover-col-header">Quick Presets</span>
                            <button type="button" class="stk-popover-preset-btn" data-preset="today" onclick="selectDatePreset('today')">
                                <span>Today</span>
                                <span class="stk-preset-shortcut">1D</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="yesterday" onclick="selectDatePreset('yesterday')">
                                <span>Yesterday</span>
                                <span class="stk-preset-shortcut">Yday</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="last_7_days" onclick="selectDatePreset('last_7_days')">
                                <span>Last 7 Days</span>
                                <span class="stk-preset-shortcut">7D</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn active" data-preset="this_month" onclick="selectDatePreset('this_month')">
                                <span>This Month</span>
                                <span class="stk-preset-shortcut">MTD</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="last_30_days" onclick="selectDatePreset('last_30_days')">
                                <span>Last 30 Days</span>
                                <span class="stk-preset-shortcut">30D</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="this_quarter" onclick="selectDatePreset('this_quarter')">
                                <span>This Quarter</span>
                                <span class="stk-preset-shortcut">Q3</span>
                            </button>
                        </div>

                        <!-- Right: Custom Date Range Form -->
                        <div class="stk-popover-custom">
                            <span class="stk-popover-col-header">Custom Range Interval</span>
                            <div class="stk-dual-input-row">
                                <div class="stk-popover-field">
                                    <label for="stkStartDate">Start Date</label>
                                    <div class="stk-input-with-icon">
                                        <i class="ph ph-calendar-plus"></i>
                                        <input type="date" id="stkStartDate" class="stk-popover-date-input" onchange="handleManualDateChange()">
                                    </div>
                                </div>
                                <div class="stk-dual-arrow">
                                    <i class="ph ph-arrow-right"></i>
                                </div>
                                <div class="stk-popover-field">
                                    <label for="stkEndDate">End Date</label>
                                    <div class="stk-input-with-icon">
                                        <i class="ph ph-calendar-check"></i>
                                        <input type="date" id="stkEndDate" class="stk-popover-date-input" onchange="handleManualDateChange()">
                                    </div>
                                </div>
                            </div>

                            <div class="stk-popover-footer-actions">
                                <button type="button" class="stk-btn-secondary" onclick="resetToThisMonth()" style="padding: 6px 12px; font-size: 0.74rem;">Reset to Month</button>
                                <button type="button" class="stk-btn-primary" onclick="applyCustomDateRange()" style="padding: 6px 16px; font-size: 0.74rem;">
                                    <i class="ph ph-check"></i> Apply Range
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Selector -->
            <select id="stkCategoryFilter" class="stk-select-dropdown" onchange="handleCategoryFilter(this.value)">
                <option value="ALL">All Categories</option>
            </select>

            <!-- Branch Selector -->
            <select id="stkBranchFilter" class="stk-select-dropdown" onchange="handleBranchFilter(this.value)">
                <option value="ALL">All Branches</option>
                <option value="Power Mac Center - Main HQ">Power Mac Center - Main HQ</option>
                <option value="Makati Greenbelt Kiosk">Makati Greenbelt Kiosk</option>
                <option value="BGC High Street Branch">BGC High Street Branch</option>
            </select>

            <!-- Stock Status Selector -->
            <select id="stkStatusFilter" class="stk-select-dropdown" onchange="handleStatusFilter(this.value)">
                <option value="ALL">All Stock Statuses</option>
                <option value="LOW_STOCK">Low Stock (Reorder Needed)</option>
                <option value="OUT_OF_STOCK">Out of Stock (Zero Units)</option>
                <option value="HEALTHY">Healthy Stock Levels</option>
                <option value="HAS_TRANSIT">Has Active In-Transit</option>
            </select>

            <!-- Reset Filters -->
            <button type="button" class="stk-btn-secondary" onclick="resetAllFilters()" title="Reset All Filters">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- Filter Chips & Row Counter -->
        <div class="stk-active-chips-bar">
            <div class="stk-chips-list" id="activeFilterChips">
                <!-- Dynamically generated active filter tags -->
            </div>
            <div class="stk-results-counter">
                Showing <strong id="visibleRowCount">0</strong> of <strong id="totalRowCount">0</strong> items
            </div>
        </div>
    </section>

    <!-- 4. Advanced 10-Column Data Table -->
    <section class="stk-table-card">
        <div class="stk-table-responsive-wrapper">
            <table class="stk-table" id="stocksOverviewTable">
                <thead>
                    <tr>
                        <!-- Col 1: Item / SKU -->
                        <th style="width: 19%; min-width: 170px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-package" style="color: var(--stk-primary);"></i> Item / SKU</span>
                                <span class="stk-th-sub">Item Name &bull; SKU / Barcode</span>
                            </div>
                        </th>

                        <!-- Col 2: Category -->
                        <th style="width: 11%; min-width: 105px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-tag" style="color: #64748b;"></i> Category</span>
                                <span class="stk-th-sub">Category &bull; Sub-category</span>
                            </div>
                        </th>

                        <!-- Col 3: Beginning -->
                        <th style="width: 7%; min-width: 70px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-hourglass-high" style="color: #64748b;"></i> Beginning</span>
                                <span class="stk-th-sub">Starting Qty &bull; UOM</span>
                            </div>
                        </th>

                        <!-- Col 4: Stock In (+) -->
                        <th style="width: 9%; min-width: 85px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-success-dark);"><i class="ph ph-arrow-circle-down-right"></i> Stock In (+)</span>
                                <span class="stk-th-sub">Received &bull; Purchases / Transfers</span>
                            </div>
                        </th>

                        <!-- Col 5: Stock Out (-) -->
                        <th style="width: 9%; min-width: 85px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-info-dark);"><i class="ph ph-arrow-circle-up-right"></i> Stock Out (-)</span>
                                <span class="stk-th-sub">Sales &bull; POS / Recipe Usage</span>
                            </div>
                        </th>

                        <!-- Col 6: Waste / Loss (-) -->
                        <th style="width: 8%; min-width: 80px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-danger-dark);"><i class="ph ph-trash"></i> Waste / Loss (-)</span>
                                <span class="stk-th-sub">Spoilage &bull; Damaged / Expired</span>
                            </div>
                        </th>

                        <!-- Col 7: Adjustments (±) -->
                        <th style="width: 8%; min-width: 80px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-scales" style="color: #d97706;"></i> Adjustments (&plusmn;)</span>
                                <span class="stk-th-sub">Audit Count &bull; Variance</span>
                            </div>
                        </th>

                        <!-- Col 8: Production (±) -->
                        <th style="width: 8%; min-width: 85px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: #9333ea;"><i class="ph ph-factory"></i> Production (&plusmn;)</span>
                                <span class="stk-th-sub">Yield (+) &bull; Consumed (-)</span>
                            </div>
                        </th>

                        <!-- Col 8: Physical On-Hand -->
                        <th style="width: 10%; min-width: 95px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-warehouse" style="color: var(--stk-text-strong);"></i> Physical On-Hand</span>
                                <span class="stk-th-sub">Ending Stock &bull; Shelf Stock</span>
                            </div>
                        </th>

                        <!-- Col 9: In-Transit Pipeline (Both in 1 column!) -->
                        <th style="width: 9%; min-width: 95px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-truck" style="color: #2563eb;"></i> In-Transit Pipeline</span>
                                <span class="stk-th-sub">Incoming (+) &bull; Outgoing (-)</span>
                            </div>
                        </th>

                        <!-- Col 10: Projected Total -->
                        <th style="width: 10%; min-width: 95px;" class="stk-projected-cell">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-primary-dark);"><i class="ph ph-sparkle"></i> Projected Total</span>
                                <span class="stk-th-sub">Available &bull; Net Expected</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="stocksTableTbody">
                    <!-- Populated dynamically via JS renderTable() -->
                </tbody>
                <tfoot id="stocksTableTfoot">
                    <tr>
                        <td colspan="2">
                            <div class="stk-tfoot-title"><i class="ph ph-chart-line-up" style="color: var(--stk-primary); font-size: 15px;"></i> Portfolio Grand Totals</div>
                            <div class="stk-tfoot-sub">Active Filtered Inventory Records</div>
                        </td>
                        <td id="footBegQty" class="stk-val-tabular" style="font-weight: 700; color: var(--stk-text-strong);">0</td>
                        <td id="footStockIn" class="stk-val-tabular" style="font-weight: 700; color: #15803d;">+0</td>
                        <td id="footStockOut" class="stk-val-tabular" style="font-weight: 700; color: #1d4ed8;">-0</td>
                        <td id="footWaste" class="stk-val-tabular" style="font-weight: 700; color: #b91c1c;">0</td>
                        <td id="footAdj" class="stk-val-tabular" style="font-weight: 700; color: #d97706;">0</td>
                        <td id="footOnHand" class="stk-val-tabular" style="font-weight: 800; color: var(--stk-text-strong);">0</td>
                        <td id="footInTransit" class="stk-val-tabular" style="font-weight: 700; color: #2563eb;">Net 0</td>
                        <td id="footProjected" class="stk-val-tabular" style="font-weight: 800; color: #7c3aed;">0</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Table Pagination and Page Size Controls -->
        <div class="stk-table-footer-controls">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="stkPageSizeSelect" style="font-size: 0.75rem; color: var(--stk-text-muted); font-weight: 500;">Rows per page:</label>
                <select id="stkPageSizeSelect" class="stk-select-dropdown" style="height: 32px; padding: 0 28px 0 10px; font-size: 0.75rem;" onchange="handlePageSizeChange(this.value)">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="ALL">All Items</option>
                </select>
            </div>
            <div class="stk-pagination-group" id="stkPaginationGroup">
                <!-- Dynamically generated pagination buttons -->
            </div>
        </div>
    </section>

</div>

<!-- 5. Slide-Out Detail Drawer for Row Audit Breakdown -->
<div class="stk-drawer-backdrop" id="stkDrawerBackdrop" onclick="closeItemDetailDrawer()"></div>
<aside class="stk-drawer-container" id="stkDetailDrawer" role="dialog" aria-modal="true" aria-labelledby="drawerItemTitle">
    <header class="stk-drawer-header">
        <div class="stk-drawer-title-box">
            <div class="stk-drawer-icon" id="drawerItemIcon">
                <i class="ph ph-package"></i>
            </div>
            <div>
                <h3 class="stk-drawer-title" id="drawerItemTitle">Item Stock Breakdown</h3>
                <p class="stk-drawer-subtitle" id="drawerItemSubtitle">SKU: --- &bull; Category: ---</p>
            </div>
        </div>
        <button type="button" class="stk-drawer-close-btn" onclick="closeItemDetailDrawer()" title="Close Drawer (Esc)">
            <i class="ph ph-x"></i>
        </button>
    </header>

    <div class="stk-drawer-body">
        
        <!-- Formula Decomposition Box -->
        <div class="stk-formula-card">
            <div class="stk-formula-title">
                <i class="ph ph-calculator"></i>
                <span>Inventory Ledger Identity</span>
            </div>
            <div class="stk-formula-eq" id="drawerFormulaEquation">
                Beg (0) + In (0) - Out (0) - Waste (0) ± Adj (0) = <strong>Physical On-Hand (0)</strong><br>
                On-Hand (0) + Incoming (0) - Outgoing (0) = <strong>Projected Total (0)</strong>
            </div>
        </div>

        <!-- Quick Operational Actions -->
        <div>
            <span style="font-size: 0.72rem; font-weight: 700; color: var(--stk-text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Operational Quick Actions</span>
            <div class="stk-quick-actions-bar" style="margin-top: 8px;">
                <a href="{{ route('inventory.stock-in') }}" class="stk-quick-btn btn-stock-in" title="Receive New Stock In">
                    <i class="ph ph-arrow-down-left" style="font-size: 16px;"></i>
                    <span>Stock In</span>
                </a>
                <a href="{{ route('inventory.stock-out') }}" class="stk-quick-btn btn-stock-out" title="Dispatch / Transfer Out">
                    <i class="ph ph-arrow-up-right" style="font-size: 16px;"></i>
                    <span>Stock Out</span>
                </a>
                <a href="{{ route('inventory.internal-transfer') }}" class="stk-quick-btn btn-transfer" title="Branch Transfer / Requisition">
                    <i class="ph ph-arrows-left-right" style="font-size: 16px;"></i>
                    <span>Transfer</span>
                </a>
                <a href="{{ route('inventory.production') }}" class="stk-quick-btn btn-production" title="Batch Assembly & Recipe Run">
                    <i class="ph ph-factory" style="font-size: 16px;"></i>
                    <span>Produce</span>
                </a>
                <a href="{{ route('inventory.waste-expiry') }}" class="stk-quick-btn btn-waste" title="Log Spoilage / Damage">
                    <i class="ph ph-trash" style="font-size: 16px;"></i>
                    <span>Log Waste</span>
                </a>
                <a href="{{ route('inventory.stock-adjustment') }}" class="stk-quick-btn btn-adjust" title="Cycle Count Adjustment">
                    <i class="ph ph-scales" style="font-size: 16px;"></i>
                    <span>Audit Count</span>
                </a>
            </div>
        </div>

        <!-- Detailed Ledger Breakdown -->
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.78rem; font-weight: 700; color: var(--stk-text-strong);">Period Movement History</span>
                <span id="drawerPeriodLabel" style="font-size: 0.70rem; color: var(--stk-text-muted); font-weight: 500;">This Month</span>
            </div>
            <div class="stk-timeline-wrap" id="drawerMovementList">
                <!-- Dynamically populated movement ledger items -->
            </div>
        </div>

    </div>
</aside>
@endsection

