@extends('layouts.app')

@section('title', 'Item Master - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/product-categories.css')
@endpush

@push('blade-data')
<script>
    window.ProductCategoriesConfig = Object.freeze({
        routes: {
            stocksOverview: @json(route('inventory.stocks-overview')),
            recipeManagement: @json(route('inventory.recipe-management')),
            stockIn: @json(route('inventory.stock-in')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/product-categories.js')
@endpush

@section('content')
<div class="inv-page-container">

    <!-- 1. Page Header — HR-style flat title row -->
    <div class="inv-parent-header">
        <div class="inv-parent-title-row">
            <div>
                <h1 class="inv-parent-title">
                    <i class="ph ph-package"></i>
                    <span>Item Master</span>
                </h1>
                <p class="inv-parent-subtitle">Centralized catalog for managing raw materials, packaging, suppliers, units of measure (UOM), and stock pricing.</p>
            </div>
            <div class="inv-header-actions">
                <a href="{{ route('inventory.stocks-overview') }}" class="inv-action-btn-secondary" title="View Live Stock Levels & Balances">
                    <i class="ph ph-squares-four"></i>
                    <span>Stocks Overview</span>
                </a>
                <a href="{{ route('inventory.recipe-management') }}" class="inv-action-btn-secondary" title="View Bill of Materials (BOM) Recipes">
                    <i class="ph ph-cooking-pot"></i>
                    <span>BOM Recipes</span>
                </a>
                <a href="{{ route('inventory.stock-in') }}" class="inv-action-btn-secondary" title="Receive Stock (Inbound GRN)">
                    <i class="ph ph-tray-arrow-down"></i>
                    <span>Stock In</span>
                </a>
                <!-- Button to Export Products to CSV -->
                <button type="button" class="inv-action-btn-secondary" onclick="exportProductsCSV()" title="Export Product Catalog to CSV">
                    <i class="ph ph-file-arrow-down"></i>
                    <span>Export</span>
                </button>

                <!-- Button to Import Products from CSV -->
                <button type="button" class="inv-action-btn-secondary" onclick="openImportModal()" title="Import Products from CSV">
                    <i class="ph ph-file-arrow-up"></i>
                    <span>Import</span>
                </button>

                <!-- Button to Add New Category -->
                <button type="button" class="inv-action-btn-secondary" onclick="openNewCategoryModal()" title="Create New Category Group">
                    <i class="ph ph-folder-plus"></i>
                    <span>Add Category</span>
                </button>

                <!-- Button for Add Products -->
                <button type="button" class="inv-action-btn-primary" id="btnOpenNewProduct" onclick="openProductDrawer('new')" title="Add New Product (F2)">
                    <i class="ph ph-plus-circle"></i>
                    <span>Add Product</span>
                    <span class="inv-hotkey-badge">F2</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Bar: Search input field, all categories horizontal scroll pills, and global collapse/expand -->
    <section class="inv-filter-bar">
        <div class="inv-filter-left">
            <div class="inv-search-wrapper" style="width: 360px;">
                <i class="ph ph-magnifying-glass inv-search-icon"></i>
                <input type="text" id="invMasterSearch" class="inv-search-input" placeholder="Search SKU, name, barcode, supplier..." autocomplete="off">
                <kbd class="inv-search-kbd">/</kbd>
            </div>
        </div>

        <div class="inv-filter-right">
            <!-- Accordion Expand / Collapse All Toggle -->
            <button type="button" class="inv-action-btn-secondary" id="btnToggleAllAccordion" onclick="toggleAllAccordions()" style="padding: 6px 12px; font-size: 0.77rem;">
                <i class="ph ph-arrows-in-line-vertical" id="iconToggleAll"></i>
                <span id="textToggleAll">Collapse All</span>
            </button>

            <div class="inv-filter-stats">
                <span id="filteredItemCount">12 Products</span>
            </div>
        </div>
    </section>

    <!-- Grouped Data Grid: Multi-column table partitioned by Accordion Group Headers with horizontal scroll -->
    <div class="inv-grid-card">
        <div class="inv-table-responsive" id="masterTableResponsiveContainer">
            <table class="inv-master-table" id="masterProductTable">
                <colgroup id="masterTableColgroup">
                    <!-- Populated dynamically with col widths matching active columns + Action column -->
                </colgroup>
                <thead>
                    <tr id="masterTableTheadRow">
                        <!-- Populated dynamically with active columns + Action column with Filter Icon trigger -->
                    </tr>
                </thead>
                <tbody id="masterProductTbody">
                    <!-- Populated dynamically via JS with Accordion Category Headers & Nested Product Rows -->
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 2. The Foreground Layer (Slide-out Detail Drawer) -->
<div class="inv-drawer-backdrop" id="drawerBackdrop" onclick="closeProductDrawer()"></div>

<aside class="inv-drawer-container" id="productDetailDrawer" role="dialog" aria-modal="true" aria-labelledby="drawerTitle">
    <!-- Panel Header: Title on left, Close (X) icon on right -->
    <div class="inv-drawer-header">
        <div class="inv-drawer-title-box">
            <div class="inv-drawer-header-icon" id="drawerIcon">
                <i class="ph ph-tag"></i>
            </div>
            <div>
                <h2 class="inv-drawer-title" id="drawerTitle">Edit Product</h2>
                <p class="inv-drawer-subtitle" id="drawerSubtitle">SKU: PRD-1001 &bull; Active Item</p>
            </div>
        </div>
        <button type="button" class="inv-drawer-close-btn" onclick="closeProductDrawer()" title="Close Drawer (Esc)">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Primary Navigation: Horizontal Tab Bar -->
    <nav class="inv-drawer-nav" id="drawerPrimaryNav">
        <button type="button" class="inv-drawer-tab-btn active" data-tab="tab-general">
            <i class="ph ph-info"></i>
            <span>General Info</span>
        </button>
        <button type="button" class="inv-drawer-tab-btn" data-tab="tab-inventory">
            <i class="ph ph-warehouse"></i>
            <span>Inventory & Units</span>
        </button>
        <button type="button" class="inv-drawer-tab-btn" data-tab="tab-pricing">
            <i class="ph ph-currency-circle-dollar"></i>
            <span>Pricing & Suppliers</span>
        </button>
        <button type="button" class="inv-drawer-tab-btn" data-tab="tab-audit">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Audit Logs</span>
        </button>
    </nav>

    <!-- Form Content (Vertical Stack) -->
    <div class="inv-drawer-body">
        <form id="productForm" onsubmit="saveProductChanges(event)">
            <input type="hidden" id="formProductId" value="">

            <!-- Tab 1: General Info -->
            <div class="drawer-tab-pane active" id="pane-tab-general">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formSku">SKU Code *</label>
                            <input type="text" id="formSku" class="inv-form-input" required placeholder="e.g. BEV-001">
                        </div>
                        <div class="inv-form-group">
                            <label for="formBarcode">Barcode (UPC / EAN)</label>
                            <input type="text" id="formBarcode" class="inv-form-input" placeholder="e.g. 4800123456789">
                        </div>
                    </div>

                    <div class="inv-form-group">
                        <label for="formName">Product Name *</label>
                        <input type="text" id="formName" class="inv-form-input" required placeholder="e.g. Signature Spanish Latte">
                    </div>

                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formCategory">Category *</label>
                            <div class="inv-custom-select-group">
                                <select id="formCategory" class="inv-form-select" required onchange="handleDrawerCategoryChange(this.value)">
                                    <!-- Categories populated dynamically -->
                                </select>
                                <button type="button" class="inv-btn-quick-create" onclick="openEditCategoryModal(document.getElementById('formCategory').value)" title="Edit selected category visuals and name" style="padding: 0 8px;">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button type="button" class="inv-btn-quick-create" onclick="openNewCategoryModal()" title="Add new product category">
                                    <i class="ph ph-plus"></i>
                                    <span>New</span>
                                </button>
                            </div>
                        </div>
                        <div class="inv-form-group">
                            <label for="formSubcategory">Sub-category</label>
                            <div style="display: flex; gap: 6px;">
                                <select id="formSubcategorySelect" class="inv-form-select" onchange="handleSubcategorySelectChange(this.value)" style="flex: 1;">
                                    <!-- Dynamically populated with unique existing subcategories -->
                                </select>
                                <input type="text" id="formSubcategory" class="inv-form-input" placeholder="Custom subcategory..." style="flex: 1; display: none;">
                                <button type="button" class="inv-btn-secondary" id="btnToggleCustomSubcat" onclick="toggleCustomSubcategoryInput()" style="padding: 0 10px; font-size: 0.76rem;" title="Toggle new/existing subcategory">
                                    <i class="ph ph-pencil-simple-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Switched place: Pack Size / Spec FIRST (numbers only), then UOM -->
                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formPackSize">Pack Size / Spec (Numbers Only) *</label>
                            <input type="number" step="any" min="0" id="formPackSize" class="inv-form-input" required placeholder="e.g. 16, 500, 1" onkeypress="return /[0-9.]/.test(event.key)">
                        </div>
                        <div class="inv-form-group">
                            <label for="formUnit">Unit of Measure (UOM) *</label>
                            <select id="formUnit" class="inv-form-select" data-uom-select required>
                                <option value="Piece">Piece (PCS)</option>
                                <option value="Plate">Plate</option>
                                <option value="Slice">Slice</option>
                                <option value="Piece">Piece</option>
                                <option value="Bottle">Bottle</option>
                                <option value="Can">Can</option>
                                <option value="Box">Box</option>
                                <option value="Pack">Pack</option>
                                <option value="Kg">Kilogram (Kg)</option>
                                <option value="Liter">Liter (L)</option>
                            </select>
                        </div>
                    </div>

                    <div class="inv-form-group">
                        <label for="formDescription">Product Description</label>
                        <textarea id="formDescription" class="inv-form-textarea" placeholder="Detailed product specifications, notes, or serving instructions..."></textarea>
                    </div>

                    <div class="inv-form-group">
                        <label for="formAllergens">Allergen / Dietary Tags (comma separated)</label>
                        <input type="text" id="formAllergens" class="inv-form-input" placeholder="e.g. Dairy, Gluten Free, Vegan, Nut Free">
                    </div>

                    <!-- Product Operational Flags & BOM Rules -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formCanBeSold" checked>
                            <div>
                                <div class="inv-checkbox-title">Can be Sold (POS / Sales Menu)</div>
                                <div class="inv-checkbox-desc">Makes this item available for ordering on POS and online catalog.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formCanBePurchased" checked>
                            <div>
                                <div class="inv-checkbox-title">Can be Purchased (Procurement)</div>
                                <div class="inv-checkbox-desc">Allows this item to be ordered from suppliers and added to POs.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formTrackStock" checked>
                            <div>
                                <div class="inv-checkbox-title">Track Physical Stock</div>
                                <div class="inv-checkbox-desc">Maintains on-hand balance, low-stock alerts, and shows in Stocks Overview for counting/receiving.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formHasBom">
                            <div>
                                <div class="inv-checkbox-title">Explode Recipe / BOM on Sale</div>
                                <div class="inv-checkbox-desc">When sold on POS, deducts sub-ingredients from Item Master instead of deducting this item directly.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formIsActive" checked>
                            <div>
                                <div class="inv-checkbox-title">Active Product Status</div>
                                <div class="inv-checkbox-desc">Visible across active inventory catalog and reporting.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Inventory & Units (Includes Nested Content Section stretched with pagination) -->
            <div class="drawer-tab-pane" id="pane-tab-inventory" style="display: none; height: 100%;">
                <div style="display: flex; flex-direction: column; gap: 16px; height: 100%;">
                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formReorderPoint">Global Reorder Point</label>
                            <input type="number" id="formReorderPoint" class="inv-form-input" min="0" value="15">
                        </div>
                        <div class="inv-form-group">
                            <label for="formTargetStock">Target Stock Level</label>
                            <input type="number" id="formTargetStock" class="inv-form-input" min="0" value="50">
                        </div>
                    </div>

                    <!-- Nested Content Section: Secondary Tabs + Sub-Table stretched -->
                    <div class="inv-nested-section" style="display: flex; flex-direction: column; flex: 1; min-height: 380px;">
                        <div class="inv-subtabs-nav" id="drawerSubtabsNav">
                            <button type="button" class="inv-subtab-btn active" data-subtab="sub-branch">Branch Stock Levels</button>
                            <button type="button" class="inv-subtab-btn" data-subtab="sub-movements">Recent Ledger Log</button>
                        </div>

                        <!-- Sub-table: Branch Stock Levels with pagination -->
                        <div class="inv-subtable-wrapper" id="subpane-sub-branch" style="flex: 1; min-height: 290px; max-height: 360px; overflow-y: auto;">
                            <table class="inv-subtable">
                                <thead>
                                    <tr>
                                        <th>Branch Location</th>
                                        <th class="th-right">On Hand</th>
                                        <th class="th-right">Reserved</th>
                                        <th class="th-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="branchStockSubtableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Branch stock pagination -->
                        <div class="inv-subtable-pagination" id="branchStockPagination">
                            <span id="branchStockPageInfo">Page 1 of 1</span>
                            <div class="inv-subtable-pagination-nav">
                                <button type="button" class="inv-subtable-page-btn" id="btnBranchPrev" onclick="navigateBranchPage(-1)">
                                    <i class="ph ph-caret-left"></i>
                                </button>
                                <button type="button" class="inv-subtable-page-btn" id="btnBranchNext" onclick="navigateBranchPage(1)">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Sub-table: Recent Ledger Log with pagination -->
                        <div class="inv-subtable-wrapper" id="subpane-sub-movements" style="display: none; flex: 1; min-height: 290px; max-height: 360px; overflow-y: auto;">
                            <table class="inv-subtable">
                                <thead>
                                    <tr>
                                        <th>Date & Ref</th>
                                        <th>Type</th>
                                        <th class="th-right">Qty</th>
                                        <th class="th-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="ledgerLogSubtableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Ledger log pagination -->
                        <div class="inv-subtable-pagination" id="ledgerLogPagination" style="display: none;">
                            <span id="ledgerLogPageInfo">Page 1 of 1</span>
                            <div class="inv-subtable-pagination-nav">
                                <button type="button" class="inv-subtable-page-btn" id="btnLedgerPrev" onclick="navigateLedgerPage(-1)">
                                    <i class="ph ph-caret-left"></i>
                                </button>
                                <button type="button" class="inv-subtable-page-btn" id="btnLedgerNext" onclick="navigateLedgerPage(1)">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Pricing & Suppliers (Column Chart for last 3 mos + Cost Price auto-calculation + Supplier Prices list) -->
            <div class="drawer-tab-pane" id="pane-tab-pricing" style="display: none;">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    
                    <!-- Column Chart: Last 3 Months Price History -->
                    <div class="inv-pricing-chart-card">
                        <div class="inv-pricing-chart-header">
                            <div class="inv-pricing-chart-title">
                                <i class="ph ph-chart-bar" style="color: var(--inv-primary-dark); font-size: 16px;"></i>
                                <span>Price Trend History (Last 3 Months)</span>
                            </div>
                            <span class="inv-pricing-chart-badge" id="chartMonthsBadge">Jul - Sep 2026</span>
                        </div>
                        <div class="inv-pricing-chart-canvas-wrapper">
                            <canvas id="priceHistoryChart"></canvas>
                        </div>
                    </div>

                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label for="formCostPrice">Cost Price (PHP) *</label>
                                <span style="font-size: 0.68rem; color: var(--inv-primary-dark); font-weight: 600;" title="Lowest supplier price + 5% markup">Lowest + 5%</span>
                            </div>
                            <input type="number" step="0.01" id="formCostPrice" class="inv-form-input" required placeholder="0.00">
                        </div>
                        <div class="inv-form-group">
                            <label for="formSellingPrice">Selling Price (PHP) *</label>
                            <input type="number" step="0.01" id="formSellingPrice" class="inv-form-input" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formProfitMargin">Margin / Markup %</label>
                            <input type="text" id="formProfitMargin" class="inv-form-input" readonly style="background: #f8fafc; font-weight: 700; color: var(--inv-success);">
                        </div>
                        <div class="inv-form-group">
                            <label for="formTaxRate">Tax Rate</label>
                            <select id="formTaxRate" class="inv-form-select">
                                <option value="12% VAT">12% VAT Inclusive</option>
                                <option value="0% Exempt">0% VAT Exempt</option>
                                <option value="Zero-Rated">Zero-Rated</option>
                            </select>
                        </div>
                    </div>

                    <div class="inv-form-group">
                        <label for="formSupplier">Primary Supplier</label>
                        <input type="text" id="formSupplier" class="inv-form-input" placeholder="e.g. Power Mac Roasters, Golden Grains, Fresh Dairy Corp">
                    </div>

                    <!-- Supplier Quotes & Recent Price Updates List -->
                    <div class="inv-supplier-list-card">
                        <div class="inv-supplier-list-header">
                            <span>Supplier Price Benchmarks & Updates</span>
                            <span style="font-size: 0.68rem; color: var(--inv-text-muted);">Recent Quotes</span>
                        </div>
                        <div class="inv-supplier-list-body" id="supplierPricesListBody">
                            <!-- Dynamically populated with supplier price rows and recent update dates -->
                        </div>
                    </div>

                </div>
            </div>

            <!-- Tab 4: Audit Logs -->
            <div class="drawer-tab-pane" id="pane-tab-audit" style="display: none;">
                <div id="productAuditLogsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Dynamically populated by renderProductAuditLogs -->
                </div>
            </div>
        </form>
    </div>

    <!-- Panel Footer: Fixed bottom section containing two right-aligned action buttons -->
    <footer class="inv-drawer-footer">
        <button type="button" class="inv-btn-secondary" onclick="closeProductDrawer()">Cancel</button>
        <button type="button" class="inv-btn-primary" onclick="triggerSaveForm()">
            <i class="ph ph-floppy-disk"></i>
            <span id="btnSaveText">Save Changes</span>
        </button>
    </footer>
</aside>

<!-- Modal: Add / Edit Product Category -->
<div class="inv-modal-backdrop" id="newCategoryModal">
    <div class="inv-modal-card">
        <div class="inv-modal-header">
            <h3 class="inv-modal-title">
                <i class="ph ph-folder-plus" id="modalCategoryHeaderIcon" style="color: var(--inv-primary-dark); font-size: 20px;"></i>
                <span id="modalCategoryTitle">Add Product Category</span>
            </h3>
            <button type="button" class="inv-drawer-close-btn" onclick="closeNewCategoryModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form onsubmit="handleCreateCategory(event)">
            <input type="hidden" id="modalCategoryOriginalName" value="">
            <div class="inv-modal-body">
                <div class="inv-form-group">
                    <label for="modalCategoryName">Category Name *</label>
                    <div class="inv-cat-input-wrapper">
                        <input type="text" id="modalCategoryName" class="inv-form-input" required autocomplete="off" placeholder="e.g. Appetizers, Bottled Drinks, Packaging" oninput="handleCategoryInputCheck(this.value)">
                        
                        <!-- Live Search / Existing Categories Dropdown -->
                        <div class="inv-cat-autocomplete-dropdown" id="catAutocompleteDropdown">
                            <div class="inv-cat-dropdown-header">Existing Categories Matching</div>
                            <div id="catAutocompleteList"></div>
                        </div>
                    </div>
                    <!-- Duplicate warning banner -->
                    <div class="inv-cat-duplicate-warning" id="catDuplicateWarning">
                        <i class="ph ph-warning-circle" style="font-size: 16px;"></i>
                        <span id="catDuplicateMsg">A category with this name already exists!</span>
                    </div>
                </div>
                <div class="inv-form-group">
                    <label for="modalCategoryDesc">Category Info / Description</label>
                    <textarea id="modalCategoryDesc" class="inv-form-textarea" style="min-height: 60px;" placeholder="e.g. Cold bar ingredients, syrups, and beverage packaging"></textarea>
                </div>

                <!-- Category Icon Selector -->
                <div class="inv-form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label>Category Icon *</label>
                        <span id="selectedIconLabel" style="font-size: 0.70rem; color: var(--inv-primary-dark); font-weight: 600;">Coffee / Drink</span>
                    </div>
                    <input type="hidden" id="modalCategoryIcon" value="ph-tag">
                    <div class="inv-cat-icon-grid" id="catIconGrid">
                        <!-- Populated dynamically via JS renderCategoryIconGrid() -->
                    </div>
                </div>

                <!-- Category Color Palette Selector -->
                <div class="inv-form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label>Category Color Palette *</label>
                        <span id="selectedColorLabel" style="font-size: 0.70rem; color: var(--inv-primary-dark); font-weight: 600;">Purple / Violet</span>
                    </div>
                    <input type="hidden" id="modalCategoryColor" value="purple">
                    <div class="inv-cat-color-grid" id="catColorGrid">
                        <!-- Populated dynamically via JS renderCategoryColorGrid() -->
                    </div>
                </div>

                <!-- Live Badge Preview -->
                <div class="inv-form-group">
                    <label>Badge Preview</label>
                    <div class="inv-cat-preview-box">
                        <span style="font-size: 0.74rem; color: var(--inv-text-muted);">Preview in catalog & stocks:</span>
                        <div class="inv-cat-preview-pill" id="catLivePreviewBadge">
                            <i id="previewBadgeIcon" class="ph ph-tag"></i>
                            <span id="previewBadgeText">New Category</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn-secondary" onclick="closeNewCategoryModal()">Cancel</button>
                <button type="submit" class="inv-btn-primary" id="btnSaveCategory">
                    <i class="ph ph-check"></i>
                    <span id="btnSaveCategoryText">Save Category</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Import Products from CSV -->
<div class="inv-modal-backdrop" id="importProductsModal">
    <div class="inv-modal-card inv-modal-lg">
        <div class="inv-modal-header">
            <h3 class="inv-modal-title">
                <i class="ph ph-file-arrow-up" style="color: var(--inv-primary-dark); font-size: 20px;"></i>
                <span>Import Products Catalog</span>
            </h3>
            <button type="button" class="inv-drawer-close-btn" onclick="closeImportModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="inv-modal-body">
            <div class="inv-dropzone" id="importDropzone" onclick="document.getElementById('importFileInput').click()">
                <i class="ph ph-cloud-arrow-up inv-dropzone-icon"></i>
                <div class="inv-dropzone-text">Click or drag & drop a CSV file here</div>
                <div class="inv-dropzone-hint">Supports columns: SKU, Name, Barcode, Category, Subcategory, UOM, Cost, Selling Price, Status</div>
                <input type="file" id="importFileInput" accept=".csv,text/csv" style="display:none;" onchange="handleImportFileSelect(event)">
            </div>

            <!-- Preview box populated once file is selected -->
            <div id="importPreviewWrapper" class="inv-import-preview-wrapper" style="display: none;">
                <div class="inv-import-preview-summary">
                    <span id="importPreviewCount">0 products parsed</span>
                    <button type="button" class="inv-col-quick-link" onclick="downloadSampleCSV()">
                        <i class="ph ph-download-simple"></i>
                        <span>Download Sample CSV</span>
                    </button>
                </div>
                <div class="inv-import-preview-table-box">
                    <table class="inv-import-preview-table" id="importPreviewTable">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Selling Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="importPreviewTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="inv-modal-footer">
            <button type="button" class="inv-btn-secondary" onclick="closeImportModal()">Cancel</button>
            <button type="button" class="inv-col-quick-link" onclick="downloadSampleCSV()" style="margin-right: auto;">
                <i class="ph ph-file-text"></i>
                <span>Sample Template</span>
            </button>
            <button type="button" class="inv-btn-primary" id="btnConfirmImport" onclick="confirmImportProducts()" disabled>
                <i class="ph ph-check"></i>
                <span>Import Products</span>
            </button>
        </div>
    </div>
</div>

<!-- Soft Toast Notification -->
<div class="inv-toast" id="invToast">
    <i class="ph ph-check-circle inv-toast-icon"></i>
    <span id="invToastMsg">Changes saved successfully</span>
</div>

@endsection
