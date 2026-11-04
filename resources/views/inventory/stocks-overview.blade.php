@extends('layouts.app')

@section('title', 'Stocks Overview - Multi-Warehouse Inventory')

@push('styles')
    @vite('resources/css/modules/stocks-overview.css')
@endpush

@push('blade-data')
<script>
    window.StocksOverviewConfig = Object.freeze({
        routes: {
            stocksOverviewData: @json(route('inventory.api.stocks-overview-data')),
            stockIn: @json(route('inventory.stock-in')),
            internalTransfer: @json(route('inventory.internal-transfer')),
            stockOut: @json(route('inventory.stock-out')),
        },
        data: {
            initialProducts: @json($initialProducts ?? []),
            initialCategories: @json($initialCategories ?? []),
            initialLedger: @json($initialLedger ?? []),
            initialWarehouses: @json($initialWarehouses ?? []),
            initialStorageAreas: @json($initialStorageAreas ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/stocks-overview.js')
@endpush

@section('content')
<div class="stk-workspace">

    <!-- 1. Enterprise Header Bar -->
    <header class="stk-header-card">
        <div class="stk-header-left">
            <div class="stk-brand-badge">
                <i class="ph ph-warehouse"></i>
            </div>
            <div>
                <div class="stk-title-row">
                    <h1 class="stk-title">Stocks Overview</h1>
                    <span class="stk-status-pill"><span class="stk-pulse-dot"></span> Multi-Warehouse Live</span>
                </div>
                <p class="stk-subtitle">
                    Real-time physical stock across facilities, specific storage bins, inbound PO pipeline, and event-sourced ledger balances.
                </p>
            </div>
        </div>
        <div class="stk-header-actions">
            <a href="{{ route('inventory.stock-in') }}" class="hr-btn hr-btn-primary hr-btn-sm" title="Receive Inbound Purchase Orders or Spot Stock">
                <i class="ph ph-arrow-down-left"></i>
                <span>Stock In Intake</span>
            </a>
            <a href="{{ route('inventory.stock-out') }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="Dispatch Outbound Shipments">
                <i class="ph ph-arrow-up-right"></i>
                <span>Stock Out</span>
            </a>
            <a href="{{ route('inventory.internal-transfer') }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="Warehouse to Warehouse Requisition">
                <i class="ph ph-arrows-left-right"></i>
                <span>Transfer</span>
            </a>
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="refreshStocksData()" title="Recompute Live Inventory Movements">
                <i class="ph ph-arrows-clockwise"></i>
                <span>Sync</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="exportStocksCSV()" title="Export Filtered Inventory to CSV">
                <i class="ph ph-file-arrow-down"></i>
                <span>Export</span>
            </button>
        </div>
    </header>

    <!-- 2. KPI Summary Bar (4 High-Signal Cards) -->
    <section class="stk-metrics-grid">
        <!-- Metric 1: Physical On-Hand -->
        <div class="stk-kpi-card">
            <div class="stk-kpi-icon-wrap is-onhand">
                <i class="ph ph-package"></i>
            </div>
            <div class="stk-kpi-content">
                <span class="stk-kpi-label">Physical On-Hand</span>
                <span class="stk-kpi-value" id="kpiTotalOnHand">0 units</span>
                <span class="stk-kpi-subtext" id="kpiActiveFacilities">Facilities: WH-MAIN &bull; WH-COLD &bull; WH-BGC</span>
            </div>
        </div>

        <!-- Metric 2: Inbound Supply Demand (Open POs) -->
        <div class="stk-kpi-card">
            <div class="stk-kpi-icon-wrap is-inbound">
                <i class="ph ph-truck"></i>
            </div>
            <div class="stk-kpi-content">
                <span class="stk-kpi-label">Inbound PO Pipeline</span>
                <span class="stk-kpi-value is-inbound" id="kpiInboundPO">+0 units</span>
                <span class="stk-kpi-subtext" id="kpiInboundSub">Awaiting Dock Check-In</span>
            </div>
        </div>

        <!-- Metric 3: Portfolio Inventory Valuation -->
        <div class="stk-kpi-card">
            <div class="stk-kpi-icon-wrap is-valuation">
                <i class="ph ph-coins"></i>
            </div>
            <div class="stk-kpi-content">
                <span class="stk-kpi-label">Inventory Valuation</span>
                <span class="stk-kpi-value" id="kpiTotalValuation">₱0.00</span>
                <span class="stk-kpi-subtext">Weighted Unit Cost Basis</span>
            </div>
        </div>

        <!-- Metric 4: Reorder & Shortage Alerts -->
        <div class="stk-kpi-card">
            <div class="stk-kpi-icon-wrap is-alert">
                <i class="ph ph-warning-circle"></i>
            </div>
            <div class="stk-kpi-content">
                <span class="stk-kpi-label">Replenishment Alerts</span>
                <span class="stk-kpi-value is-alert" id="kpiAlertCount">0 Items</span>
                <span class="stk-kpi-subtext" id="kpiAlertSub">At or Below Safety Threshold</span>
            </div>
        </div>
    </section>

    <!-- 3. Modern Multi-Warehouse Filter Ribbon -->
    <section class="stk-filter-card">
        <div class="stk-filter-row">
            <!-- Search Bar -->
            <div class="stk-search-box">
                <i class="ph ph-magnifying-glass stk-search-icon"></i>
                <input type="text" id="stkSearchInput" class="stk-search-input" placeholder="Search item name, SKU, or barcode..." aria-label="Search inventory by item name, SKU, or barcode" autocomplete="off">
                <span class="stk-search-shortcut">/</span>
            </div>

            <!-- Warehouse Facility Filter -->
            <div class="stk-select-wrapper">
                <i class="ph ph-buildings stk-select-icon"></i>
                <select id="stkWarehouseFilter" class="stk-select-dropdown" aria-label="Filter by warehouse facility" onchange="handleWarehouseFilter(this.value)">
                    <option value="ALL">All Facilities (Consolidated)</option>
                    @foreach($initialWarehouses as $wh)
                        <option value="{{ $wh['id'] }}">{{ $wh['code'] }} — {{ $wh['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Storage Area / Specific Bin Filter (Dynamically Cascaded) -->
            <div class="stk-select-wrapper">
                <i class="ph ph-map-pin stk-select-icon"></i>
                <select id="stkLocationFilter" class="stk-select-dropdown" aria-label="Filter by storage bin location" onchange="handleLocationFilter(this.value)">
                    <option value="ALL">All Storage Bins & Zones</option>
                    @foreach($initialStorageAreas as $area)
                        <option value="{{ $area['name'] }}" data-warehouse="{{ $area['warehouse_id'] }}">{{ $area['warehouse_id'] }} &bull; {{ $area['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Category Filter -->
            <div class="stk-select-wrapper">
                <i class="ph ph-tag stk-select-icon"></i>
                <select id="stkCategoryFilter" class="stk-select-dropdown" aria-label="Filter by product category" onchange="handleCategoryFilter(this.value)">
                    <option value="ALL">All Categories</option>
                    @foreach($initialCategories as $cat)
                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Stock Health Status Filter -->
            <div class="stk-select-wrapper">
                <i class="ph ph-activity stk-select-icon"></i>
                <select id="stkStatusFilter" class="stk-select-dropdown" aria-label="Filter by stock health status" onchange="handleStatusFilter(this.value)">
                    <option value="ALL">All Stock Statuses</option>
                    <option value="LOW_STOCK">Low Stock (Reorder Needed)</option>
                    <option value="OUT_OF_STOCK">Out of Stock (Zero Units)</option>
                    <option value="HEALTHY">Healthy Stock Levels</option>
                    <option value="HAS_INBOUND">Has Inbound Open POs</option>
                </select>
            </div>

            <!-- Reset Filters -->
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetAllFilters()" title="Reset All Filters">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- Active Filter Indicator & Item Count -->
        <div class="stk-filter-meta">
            <div class="stk-meta-tags" id="activeFilterChips">
                <!-- Dynamically populated filter chips -->
            </div>
            <div class="stk-meta-count">
                Showing <strong id="visibleRowCount">{{ count($initialProducts) }}</strong> of <strong id="totalRowCount">{{ count($initialProducts) }}</strong> items
            </div>
        </div>
    </section>

    <!-- 4. Modern High-Signal 6-Column Inventory Table -->
    <section class="stk-table-card">
        <div class="stk-table-scroll">
            <table class="stk-table" id="stocksOverviewTable">
                <thead>
                    <tr>
                        <th class="stk-col-item">
                            <div class="stk-th-box">
                                <span class="stk-th-title"><i class="ph ph-package"></i> Item & Category</span>
                                <span class="stk-th-sub">Item Name &bull; SKU &bull; Base UOM</span>
                            </div>
                        </th>
                        <th class="stk-col-wh">
                            <div class="stk-th-box">
                                <span class="stk-th-title"><i class="ph ph-map-pin"></i> Primary Facility & Storage Bin</span>
                                <span class="stk-th-sub">Assigned Warehouse & Location</span>
                            </div>
                        </th>
                        <th class="stk-col-onhand">
                            <div class="stk-th-box is-right">
                                <span class="stk-th-title"><i class="ph ph-stack"></i> Physical On-Hand</span>
                                <span class="stk-th-sub">Current Count &bull; Valuation</span>
                            </div>
                        </th>
                        <th class="stk-col-inbound">
                            <div class="stk-th-box is-right">
                                <span class="stk-th-title is-inbound"><i class="ph ph-truck"></i> Inbound Supply</span>
                                <span class="stk-th-sub">Open POs &bull; Available-to-Promise</span>
                            </div>
                        </th>
                        <th class="stk-col-health">
                            <div class="stk-th-box">
                                <span class="stk-th-title"><i class="ph ph-gauge"></i> Replenishment Health</span>
                                <span class="stk-th-sub">Safety Level vs Reorder Point</span>
                            </div>
                        </th>
                        <th class="stk-col-action">
                            <div class="stk-th-box is-center">
                                <span class="stk-th-title"><i class="ph ph-dots-three-circle"></i> Breakdown</span>
                                <span class="stk-th-sub">Facility Bins</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="stocksTableTbody">
                    @forelse($initialProducts as $item)
                        @php
                            $onHand = (float) $item->current_stock;
                            $minStock = (float) ($item->min_stock ?? 10);
                            $maxStock = (float) ($item->max_stock ?? 50);
                            $inboundQty = (float) ($item->inbound_po_qty ?? 0);
                            $atp = max(0, $onHand + $inboundQty);
                            $primaryWh = $item->primary_warehouse ?? 'WH-MAIN';
                            $primaryLoc = $item->primary_location ?? ($item->storage_location ?? 'Stock Zone A');

                            $statusClass = 'is-healthy';
                            $statusLabel = 'In Stock';
                            if ($onHand <= 0) {
                                $statusClass = 'is-danger';
                                $statusLabel = 'Out of Stock';
                            } elseif ($onHand <= $minStock) {
                                $statusClass = 'is-warning';
                                $statusLabel = 'Low Stock';
                            }

                            $healthPct = $maxStock > 0 ? min(100, max(5, round(($onHand / $maxStock) * 100))) : 50;
                        @endphp
                        <tr class="stk-row" data-sku="{{ $item->sku }}" data-category="{{ $item->category }}" data-warehouse="{{ $primaryWh }}" data-status="{{ $statusClass }}">
                            <!-- Col 1: Item & Category -->
                            <td>
                                <div class="stk-item-cell">
                                    <div class="stk-item-name-wrap">
                                        <button type="button" class="stk-item-name-link" onclick="openLocationBreakdownModal('{{ $item->sku }}')">
                                            {{ $item->name }}
                                        </button>
                                        <div class="stk-item-badges">
                                            <span class="stk-sku-pill">{{ $item->sku }}</span>
                                            <span class="stk-cat-pill">{{ $item->category }}</span>
                                            <span class="stk-uom-pill">{{ $item->uom }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 2: Primary Facility & Bin -->
                            <td>
                                <div class="stk-wh-cell">
                                    <div class="stk-facility-tag {{ $primaryWh === 'WH-COLD' ? 'wh-cold' : ($primaryWh === 'WH-BGC' ? 'wh-bgc' : 'wh-main') }}">
                                        <i class="ph ph-buildings"></i>
                                        <span>{{ $primaryWh }}</span>
                                    </div>
                                    <div class="stk-loc-text" title="{{ $primaryLoc }}">
                                        <i class="ph ph-map-pin"></i>
                                        <span>{{ $primaryLoc }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 3: Physical On-Hand -->
                            <td class="text-right">
                                <div class="stk-qty-cell">
                                    <div class="stk-qty-val">{{ number_format($onHand, 2) }} <span class="stk-qty-unit">{{ $item->uom }}</span></div>
                                    <div class="stk-stock-tag {{ $statusClass }}">
                                        <span class="stk-dot"></span>
                                        <span>{{ $statusLabel }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 4: Inbound Supply (Open POs) -->
                            <td class="text-right">
                                <div class="stk-inbound-cell">
                                    @if($inboundQty > 0)
                                        <button type="button" class="stk-inbound-badge" onclick="openLocationBreakdownModal('{{ $item->sku }}')" title="Click to view inbound PO details">
                                            <i class="ph ph-arrow-down-left"></i>
                                            <span>+{{ number_format($inboundQty, 2) }}</span>
                                        </button>
                                        <div class="stk-atp-sub">ATP: {{ number_format($atp, 2) }}</div>
                                    @else
                                        <span class="stk-inbound-none">—</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Col 5: Replenishment Health Bar -->
                            <td>
                                <div class="stk-health-cell">
                                    <div class="stk-health-bar-track">
                                        <div class="stk-health-bar-fill {{ $statusClass }}" style="width: {{ $healthPct }}%;"></div>
                                    </div>
                                    <div class="stk-health-limits">
                                        <span>Min: {{ number_format($minStock, 0) }}</span>
                                        <span>Max: {{ number_format($maxStock, 0) }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 6: Action -->
                            <td class="text-center">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm stk-drill-btn" onclick="openLocationBreakdownModal('{{ $item->sku }}')" title="View Facility Bins & Inbound Orders">
                                    <i class="ph ph-magnifying-glass-plus"></i>
                                    <span>Bins</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="stk-empty-state">
                                <i class="ph ph-package-x"></i>
                                <p>No inventory records found in the database.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer Controls (Pagination & Count) -->
        <div class="stk-table-footer">
            <div class="stk-page-size-wrap">
                <label for="stkPageSizeSelect">Rows per page:</label>
                <select id="stkPageSizeSelect" class="stk-select-dropdown" aria-label="Select number of rows per page" onchange="handlePageSizeChange(this.value)">
                    <option value="15">15</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="ALL">All Items</option>
                </select>
            </div>
            <div class="stk-pagination-wrap" id="stkPaginationGroup">
                <!-- Dynamically managed pagination controls -->
            </div>
        </div>
    </section>

</div>

<!-- 5. Interactive Warehouse & Bin Location Drill-Down Modal -->
<div class="stk-modal-backdrop" id="stkLocationModalBackdrop" aria-hidden="true"></div>
<div class="stk-modal-dialog" id="stkLocationModal" role="dialog" aria-modal="true" aria-labelledby="stkModalItemTitle">
    <div class="stk-modal-content">
        <header class="stk-modal-header">
            <div class="stk-modal-title-box">
                <div class="stk-modal-icon">
                    <i class="ph ph-buildings"></i>
                </div>
                <div>
                    <h2 class="stk-modal-title" id="stkModalItemTitle">Item Stock Breakdown</h2>
                    <div class="stk-modal-badges">
                        <span class="stk-sku-pill" id="stkModalSku">SKU: ---</span>
                        <span class="stk-cat-pill" id="stkModalCategory">Category: ---</span>
                        <span class="stk-uom-pill" id="stkModalUom">Base UOM: ---</span>
                    </div>
                </div>
            </div>
            <button type="button" class="stk-modal-close-btn" onclick="closeLocationBreakdownModal()" title="Close (Esc)">
                <i class="ph ph-x"></i>
            </button>
        </header>

        <div class="stk-modal-body">
            <!-- Facility Distribution Breakdown -->
            <div class="stk-modal-section">
                <div class="stk-section-title">
                    <i class="ph ph-warehouse"></i>
                    <span>Multi-Facility Distribution & Bin Locations</span>
                </div>
                <div class="stk-wh-cards-grid" id="stkModalWhCards">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Inbound Open Purchase Orders -->
            <div class="stk-modal-section">
                <div class="stk-section-title">
                    <i class="ph ph-truck"></i>
                    <span>Inbound Purchase Orders Pipeline (Open POs)</span>
                </div>
                <div class="stk-modal-table-wrap">
                    <table class="stk-subtable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Vendor / Supplier</th>
                                <th>Target Warehouse</th>
                                <th>Expected Delivery</th>
                                <th class="text-right">Pending Qty</th>
                            </tr>
                        </thead>
                        <tbody id="stkModalOpenPoTbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Stock Movement History -->
            <div class="stk-modal-section">
                <div class="stk-section-title">
                    <i class="ph ph-clock-counter-clockwise"></i>
                    <span>Recent Master Ledger Movements (SYS_LEDGER)</span>
                </div>
                <div class="stk-ledger-timeline" id="stkModalLedgerList">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>

        <footer class="stk-modal-footer">
            <div class="stk-modal-footer-actions">
                <a href="{{ route('inventory.stock-in') }}" class="hr-btn hr-btn-primary hr-btn-sm">
                    <i class="ph ph-arrow-down-left"></i>
                    <span>Receive Intake (GRN)</span>
                </a>
                <a href="{{ route('inventory.internal-transfer') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                    <i class="ph ph-arrows-left-right"></i>
                    <span>Transfer to Warehouse</span>
                </a>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeLocationBreakdownModal()">
                    <span>Close</span>
                </button>
            </div>
        </footer>
    </div>
</div>
@endsection
