@extends('layouts.app')

@section('title', 'Bill of Materials (BOM) - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/recipe-management.css')
@endpush

@push('blade-data')
<script>
    window.RecipeManagementConfig = Object.freeze({
        routes: {
            stocksOverview: @json(route('inventory.stocks-overview')),
            production: @json(route('inventory.production')),
            productCategories: @json(route('inventory.product-categories')),
        },
        data: {
            initialBoms: @json($initialBoms ?? []),
            products: @json($products ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/recipe-management.js')
@endpush

@section('content')
<div class="bom-page-container">

    <!-- 1. Page Header — HR-style flat title row -->
    <div class="bom-parent-header">
        <div class="bom-parent-title-row">
            <div>
                <h1 class="bom-parent-title">
                    <i class="ph ph-cooking-pot"></i>
                    <span>Bill of Materials (BOM) & Recipe Management</span>
                </h1>
                <p class="bom-parent-subtitle">Configure product recipes, raw ingredient deductions, live component costing, and profit margin analysis.</p>
            </div>
            <div class="bom-header-actions">
                <a href="{{ route('inventory.stocks-overview') }}" class="bom-action-btn-secondary" title="View Live Stock Overview">
                    <i class="ph ph-squares-four"></i>
                    <span>Stocks Overview</span>
                </a>
                <a href="{{ route('inventory.production') }}" class="bom-action-btn-secondary" title="Execute Batch Assembly & Production Runs">
                    <i class="ph ph-factory"></i>
                    <span>Production</span>
                </a>
                <a href="{{ route('inventory.product-categories') }}" class="bom-action-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-package"></i>
                    <span>Item Master</span>
                </a>
                <button type="button" class="bom-action-btn-primary" onclick="openBomDrawer('new')" title="Configure new recipe or product (F2)">
                    <i class="ph ph-plus-circle"></i>
                    <span>New BOM Recipe</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. KPI Metrics Bar -->
    <section class="bom-metrics-grid">
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Active BOM Recipes</span>
                <span class="bom-metric-value" id="statActiveBoms">0</span>
                <span class="bom-metric-sub"><i class="ph ph-check-circle"></i> Connected to Item Master</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-book-open"></i></div>
        </div>
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Average Raw Cost</span>
                <span class="bom-metric-value" id="statAvgCost">₱0.00</span>
                <span class="bom-metric-sub" style="color: var(--bom-text-muted);">Per batch / serving</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-scales"></i></div>
        </div>
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Average Profit Margin</span>
                <span class="bom-metric-value" id="statAvgMargin" style="color: var(--bom-success);">0.0%</span>
                <span class="bom-metric-sub" id="statMarginHealth">Healthy Margins</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-trend-up"></i></div>
        </div>
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Profitability Status</span>
                <span class="bom-metric-value" id="statProfitableCount" style="color: var(--bom-success);">0 Gaining</span>
                <span class="bom-metric-sub" id="statDeficitCount" style="color: var(--bom-text-muted);">0 At Deficit</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-shield-check"></i></div>
        </div>
    </section>

    <!-- 3. Filter & Search Bar -->
    <section class="bom-filter-bar">
        <div class="bom-search-wrapper">
            <i class="ph ph-magnifying-glass bom-search-icon"></i>
            <input type="text" id="bomSearchInput" class="bom-search-input" placeholder="Search recipe name, SKU, raw ingredient..." autocomplete="off">
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <select id="bomCategoryFilter" class="bom-filter-category-select" onchange="handleBomCategoryFilter(this.value)">
                <option value="ALL">All Categories</option>
            </select>
            <button type="button" class="bom-action-btn-secondary" onclick="toggleAllBomBreakdowns()" style="font-size: 0.78rem; padding: 7px 12px;">
                <i class="ph ph-arrows-out-line-vertical" id="iconExpandAllBoms"></i>
                <span id="textExpandAllBoms">Expand All</span>
            </button>
        </div>
    </section>

    <!-- 4. BOM Master Grid Table with Expandable Raw Materials Breakdown -->
    <div class="bom-master-card">
        <div class="bom-table-container">
            <table class="bom-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Finished Product</th>
                        <th>Category</th>
                        <th class="th-right">Total BOM Cost</th>
                        <th class="th-right">Selling Price</th>
                        <th class="th-right">Gross Profit (PHP)</th>
                        <th class="th-right">Margin (%)</th>
                        <th style="width: 140px;">Profitability Status</th>
                        <th class="th-right" style="width: 90px;">Action</th>
                    </tr>
                </thead>
                <tbody id="bomMasterTbody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 5. Slide-Out Detail Drawer: Create / Edit Product BOM -->
<div class="bom-drawer-backdrop" id="bomDrawerBackdrop" onclick="closeBomDrawer()"></div>
<aside class="bom-drawer-container" id="bomDrawer">
    <header class="bom-drawer-header">
        <div class="bom-drawer-title-box">
            <div class="bom-header-icon" style="width: 38px; height: 38px; font-size: 20px;">
                <i class="ph ph-cooking-pot"></i>
            </div>
            <div>
                <h3 class="bom-drawer-title" id="drawerBomTitle">Configure Bill of Materials</h3>
                <p class="bom-drawer-subtitle" id="drawerBomSubtitle">Define raw materials, component quantities & pricing</p>
            </div>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeBomDrawer()">
            <i class="ph ph-x"></i>
        </button>
    </header>

    <!-- BOM Drawer Tab Navigation -->
    <nav class="bom-drawer-nav" id="bomDrawerNav">
        <button type="button" class="bom-drawer-tab-btn active" data-tab="tab-bom-builder" onclick="switchBomDrawerTab('tab-bom-builder')">
            <i class="ph ph-cooking-pot"></i>
            <span>Recipe Builder</span>
        </button>
        <button type="button" class="bom-drawer-tab-btn" data-tab="tab-bom-audit" onclick="switchBomDrawerTab('tab-bom-audit')">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Audit Logs</span>
        </button>
    </nav>

    <div class="bom-drawer-body">
        
        <!-- Tab 1: Recipe Builder Pane -->
        <div class="bom-tab-pane active" id="pane-tab-bom-builder">
            <!-- Mode Switcher: Link Existing vs Create New Product -->
            <div class="bom-mode-switcher" id="bomModeSwitcher">
            <button type="button" class="bom-mode-btn active" id="btnModeExisting" onclick="switchBomDrawerMode('existing')">
                <i class="ph ph-link"></i>
                <span>Existing Item Master</span>
            </button>
            <button type="button" class="bom-mode-btn" id="btnModeNew" onclick="switchBomDrawerMode('new_product')">
                <i class="ph ph-plus-circle"></i>
                <span>Create New Product</span>
            </button>
        </div>

        <form id="bomForm" onsubmit="handleSaveBom(event)">
            <input type="hidden" id="bomProductId" value="">

            <!-- Existing Item Selector Section -->
            <div id="sectionExistingProduct" class="bom-form-group">
                <label for="selectExistingItem">Select Finished Product *</label>
                <select id="selectExistingItem" class="bom-form-select" onchange="handleSelectExistingProduct(this.value)">
                    <!-- Options populated from window.AppStore.products -->
                </select>
            </div>

            <!-- New Product Fields Section -->
            <div id="sectionNewProduct" style="display: none; flex-direction: column; gap: 12px;">
                <div class="bom-form-row-2">
                    <div class="bom-form-group">
                        <label for="newProdSku">Product SKU *</label>
                        <input type="text" id="newProdSku" class="bom-form-input" placeholder="e.g. BEV-901">
                    </div>
                    <div class="bom-form-group">
                        <label for="newProdCategory">Category *</label>
                        <select id="newProdCategory" class="bom-form-select">
                            <!-- Populated from categories -->
                        </select>
                    </div>
                </div>
                <div class="bom-form-group">
                    <label for="newProdName">Product Name *</label>
                    <input type="text" id="newProdName" class="bom-form-input" placeholder="e.g. Pistachio Matcha Cold Foam">
                </div>
                <div class="bom-form-row-2">
                    <div class="bom-form-group">
                        <label for="newProdUom">Unit of Measure (UOM) *</label>
                        <select id="newProdUom" class="bom-form-select" data-uom-select>
                            <option value="Cup">Cup</option>
                            <option value="Plate">Plate</option>
                            <option value="Slice">Slice</option>
                            <option value="Piece">Piece</option>
                            <option value="Bottle">Bottle</option>
                            <option value="Serving">Serving</option>
                        </select>
                    </div>
                    <div class="bom-form-group">
                        <label for="newProdPackSize">Pack Size / Spec</label>
                        <input type="text" id="newProdPackSize" class="bom-form-input" placeholder="e.g. 16 oz">
                    </div>
                </div>
            </div>

            <!-- Selling Price Input -->
            <div class="bom-form-row-2" style="margin-top: 12px;">
                <div class="bom-form-group">
                    <label for="bomSellingPrice">Selling Price (PHP) *</label>
                    <input type="number" step="0.01" min="0" id="bomSellingPrice" class="bom-form-input" required placeholder="0.00" oninput="recalculateDrawerBomFinancials()">
                </div>
                <div class="bom-form-group">
                    <label for="bomBatchYield">Batch Yield / Output Qty *</label>
                    <input type="number" step="any" min="1" id="bomBatchYield" class="bom-form-input" value="1" required oninput="recalculateDrawerBomFinancials()">
                </div>
            </div>

            <!-- Inventory Movement & BOM Behavior Settings -->
            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
                <label class="bom-checkbox-card">
                    <input type="checkbox" id="bomTrackPhysicalStock" checked>
                    <div>
                        <div class="bom-checkbox-title">Track Physical Stock</div>
                        <div class="bom-checkbox-desc">Maintains on-hand balance, low-stock alerts, and shows in Stocks Overview for counting/receiving.</div>
                    </div>
                </label>

                <label class="bom-checkbox-card">
                    <input type="checkbox" id="bomExplodeBomOnSale" checked>
                    <div>
                        <div class="bom-checkbox-title">Explode Recipe / BOM on Sale</div>
                        <div class="bom-checkbox-desc">When sold on POS, deducts sub-ingredients from Item Master instead of deducting this item directly.</div>
                    </div>
                </label>
            </div>

            <!-- Raw Materials / Ingredients Builder Repeater -->
            <div class="bom-builder-card" style="margin-top: 14px;">
                <div class="bom-builder-header">
                    <div>
                        <span style="font-size: 0.78rem; font-weight: 700; color: var(--bom-text-strong);">Raw Materials & Ingredients</span>
                        <div style="font-size: 0.68rem; color: var(--bom-text-muted);">Pulls live unit costs from Item Master (customizable per recipe unit)</div>
                    </div>
                    <button type="button" class="bom-action-btn-secondary" onclick="addBomIngredientRow()" style="font-size: 0.72rem; padding: 4px 8px;">
                        <i class="ph ph-plus"></i>
                        <span>Add Material</span>
                    </button>
                </div>
                <div class="bom-builder-items-list">
                    <div class="bom-builder-cols-header">
                        <span>Component Item</span>
                        <span>Qty & UOM</span>
                        <span style="text-align: right;">Unit Cost</span>
                        <span style="text-align: right;">Subtotal</span>
                        <span></span>
                    </div>
                    <div id="bomIngredientRowsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Dynamic ingredient rows -->
                    </div>
                </div>
            </div>

            <!-- Profitability Radar Widget -->
            <div class="bom-radar-card" style="margin-top: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--bom-text-strong);">Financial & Profitability Radar</span>
                    <span id="radarHealthTag" class="bom-profit-pill profitable">Gaining Profit</span>
                </div>
                <div class="bom-radar-summary-grid">
                    <div class="bom-radar-stat-box">
                        <div class="bom-radar-stat-label">Total Raw Cost</div>
                        <div class="bom-radar-stat-val" id="radarTotalCost">₱0.00</div>
                    </div>
                    <div class="bom-radar-stat-box">
                        <div class="bom-radar-stat-label">Gross Profit</div>
                        <div class="bom-radar-stat-val" id="radarGrossProfit">₱0.00</div>
                    </div>
                    <div class="bom-radar-stat-box">
                        <div class="bom-radar-stat-label">Profit Margin</div>
                        <div class="bom-radar-stat-val" id="radarProfitMargin">0.0%</div>
                    </div>
                </div>
                <div id="radarBanner" class="bom-radar-banner is-profit">
                    <i id="radarBannerIcon" class="ph ph-trend-up"></i>
                    <span id="radarBannerText">Healthy Margin: You are gaining profit on every unit sold.</span>
                </div>
            </div>

            <div class="bom-drawer-footer" style="padding: 16px 0 0 0; margin-top: 14px; border-top: 1px solid var(--bom-border-subtle);">
                <button type="button" class="bom-action-btn-secondary" onclick="closeBomDrawer()">Cancel</button>
                <button type="submit" class="bom-action-btn-primary">
                    <i class="ph ph-check"></i>
                    <span id="btnSaveBomText">Save BOM & Product</span>
                </button>
            </div>
        </form>
        </div>

        <!-- Tab 2: Audit Logs Pane -->
        <div class="bom-tab-pane" id="pane-tab-bom-audit" style="display: none; padding-top: 4px;">
            <div id="bomAuditLogsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Populated dynamically via renderBomAuditLogs -->
            </div>
            <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--bom-border-subtle); display: flex; justify-content: flex-end;">
                <button type="button" class="bom-action-btn-secondary" onclick="closeBomDrawer()">Close</button>
            </div>
        </div>
    </div>
</aside>

<!-- Toast -->
<div id="bomToast" style="position: fixed; bottom: 24px; right: 24px; background: #0f172a; color: #fff; padding: 12px 18px; border-radius: 10px; font-size: 0.82rem; font-weight: 500; display: flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(100px); opacity: 0; transition: all 0.25s ease; z-index: 2000;">
    <i class="ph ph-check-circle" style="color: #10b981; font-size: 18px;"></i>
    <span id="bomToastMsg">Action completed!</span>
</div>
@endsection


