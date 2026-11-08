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
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    @vite('resources/js/modules/product-categories.js')
@endpush

@section('content')
<div class="inv-page-container">

    <!-- 1. Page Header — HR-style flat title row -->
    <div class="inv-parent-header">
        <div class="inv-parent-title-row">
            <div class="inv-title-text-group">
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

                <!-- Button for Add Products -->
                <button type="button" class="inv-action-btn-primary" id="btnOpenNewProduct" onclick="openProductDrawer('new')" title="Add New Product (F2)">
                    <i class="ph ph-plus-circle"></i>
                    <span>Add Product</span>
                    <span class="inv-hotkey-badge">F2</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Top-Level Item Master Segmented Tab Switcher (Canonical ERP / RFQ Design System) -->
    <nav class="rfq-nav-tabs-bar" id="itemMasterWorkspaceTabs" aria-label="Item Master Sections">
        <button type="button" class="rfq-tab-btn active" id="btnWorkspaceCatalog" data-workspace="catalog" onclick="switchMasterWorkspaceTab('catalog')">
            <i class="ph ph-table"></i>
            <span>Product Catalog</span>
            <span class="rfq-tab-count" id="tabBadgeProductCount">12</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="btnWorkspaceDetails" data-workspace="details" onclick="switchMasterWorkspaceTab('details')">
            <i class="ph ph-sliders-horizontal"></i>
            <span id="tabLabelProductDetails">Product Details</span>
            <span class="rfq-tab-badge" id="tabBadgeDetailMode">View / Edit</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="btnWorkspaceCategories" data-workspace="categories" onclick="switchMasterWorkspaceTab('categories')">
            <i class="ph ph-tree-structure"></i>
            <span>Category Settings</span>
            <span class="rfq-tab-badge" id="tabBadgeCategoryCount">Cat 1 • Cat 2</span>
        </button>
    </nav>

    <!-- ===================================================================== -->
    <!-- WORKSPACE TAB 1: PRODUCT CATALOG (KPI BAR + FLAT MULTI-COLUMN GRID)   -->
    <!-- ===================================================================== -->
    <div class="inv-workspace-pane active" id="workspace-catalog">

        <!-- 4-Card Interactive KPI Metrics Cluster (Canonical ERP / RFQ Parity) -->
        <div class="rfq-kpi-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 12px;">
            <div class="rfq-kpi-card is-active-filter" id="kpiCardAll" data-kpi-mode="ALL" onclick="handleCatalogKpiFilter('ALL')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Total Catalog Items</span>
                    <div class="rfq-kpi-icon"><i class="ph ph-package"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiTotalItems">12</div>
                <div class="rfq-kpi-sub" id="kpiTotalItemsSub">12 Active SKUs in Masterlist</div>
            </div>

            <div class="rfq-kpi-card" id="kpiCardSellable" data-kpi-mode="SELLABLE" onclick="handleCatalogKpiFilter('SELLABLE')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">POS / Sellable Items</span>
                    <div class="rfq-kpi-icon awarded"><i class="ph ph-storefront"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiSellableItems">10</div>
                <div class="rfq-kpi-sub" id="kpiSellableSub">Avg Profit Margin: 64.2%</div>
            </div>

            <div class="rfq-kpi-card" id="kpiCardBom" data-kpi-mode="BOM" onclick="handleCatalogKpiFilter('BOM')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">BOM / Recipe Items</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-cooking-pot"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiBomItems">4</div>
                <div class="rfq-kpi-sub" id="kpiBomSub">Explodes sub-ingredients on sale</div>
            </div>

            <div class="rfq-kpi-card" id="kpiCardCategories" onclick="switchMasterWorkspaceTab('categories')" title="Click to open Category Settings">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Category Hierarchy</span>
                    <div class="rfq-kpi-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb; border-color: #bfdbfe;"><i class="ph ph-tree-structure"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiCategoryLevels">2 Levels</div>
                <div class="rfq-kpi-sub" id="kpiCategorySub">Cat 1 • Cat 2 active columns</div>
            </div>
        </div>

        <!-- Industrial Filter & Action Toolbar (Zero-Scroll Clean Layout) -->
        <section class="inv-filter-bar">
            <div class="inv-filter-left">
                <div class="inv-search-wrapper">
                    <i class="ph ph-magnifying-glass inv-search-icon"></i>
                    <input type="text" id="invMasterSearch" class="inv-search-input" placeholder="Search SKU, name, barcode, category, supplier..." autocomplete="off">
                    <kbd class="inv-search-kbd">/</kbd>
                </div>

                <select id="invCategoryFilterSelect" class="inv-form-select inv-cat-filter-select" onchange="handleCatalogCategoryFilter(this.value)">
                    <option value="ALL">All Categories</option>
                </select>
            </div>

            <div class="inv-filter-right">
                <div class="inv-filter-stats">
                    <span id="filteredItemCount">12 Products</span>
                </div>
            </div>
        </section>

        <!-- Flat Multi-Column Data Grid with Dynamic Category Columns (Cat 1, Cat 2, ...) -->
        <div class="inv-grid-card">
            <div class="inv-table-responsive" id="masterTableResponsiveContainer">
                <table class="inv-master-table" id="masterProductTable">
                    <colgroup id="masterTableColgroup">
                        <!-- Populated dynamically with col widths matching active columns + Action column -->
                    </colgroup>
                    <thead>
                        <tr id="masterTableTheadRow">
                            <!-- Populated dynamically with active columns (including Cat 1, Cat 2, ...) + Action column -->
                        </tr>
                    </thead>
                    <tbody id="masterProductTbody">
                        <!-- Populated dynamically via JS as flat product rows with dynamic Cat 1, Cat 2... cells -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- WORKSPACE TAB 2: PRODUCT DETAILS (ENTERPRISE BENTO WORKSPACE TAB)     -->
    <!-- ===================================================================== -->
    <div class="inv-workspace-pane" id="workspace-details">
        <div class="inv-drawer-backdrop" id="drawerBackdrop" style="display:none;"></div>

        <section class="inv-product-workspace-card" id="productDetailDrawer" aria-labelledby="drawerTitle">
            <!-- Top Identity & Action Hero Header -->
            <div class="inv-detail-hero-header">
                <div class="inv-detail-hero-left">
                    <div class="inv-drawer-header-icon" id="drawerIcon">
                        <i class="ph ph-tag"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <h2 class="inv-drawer-title" id="drawerTitle" style="margin: 0;">Edit Product</h2>
                            <span class="inv-status-pill active" id="detailHeaderStatusPill">
                                <span class="inv-status-dot"></span>
                                <span id="detailHeaderStatusText">Active</span>
                            </span>
                        </div>
                        <p class="inv-drawer-subtitle" id="drawerSubtitle" style="margin: 2px 0 0 0;">SKU: PRD-1001 &bull; Active Item</p>
                    </div>
                </div>
                <div class="inv-detail-hero-actions">
                    <button type="button" class="inv-btn-secondary" onclick="openProductDrawer('new')" title="Reset form to create a new product (F2)">
                        <i class="ph ph-plus-circle"></i>
                        <span>New Product</span>
                    </button>
                    <button type="button" class="inv-btn-primary" onclick="triggerSaveForm()">
                        <i class="ph ph-floppy-disk"></i>
                        <span id="btnSaveTextTop">Save Changes</span>
                    </button>
                </div>
            </div>

            <!-- Sub-Navigation Segmented Bar (Uses Canonical ERP / RFQ Tab Switcher) -->
            <div class="inv-detail-subnav-wrap">
                <nav class="rfq-nav-tabs-bar" id="drawerPrimaryNav" style="margin-bottom: 0;">
                    <button type="button" class="rfq-tab-btn inv-drawer-tab-btn active" data-tab="tab-general">
                        <i class="ph ph-info"></i>
                        <span>General &amp; Hierarchy</span>
                    </button>
                    <button type="button" class="rfq-tab-btn inv-drawer-tab-btn" data-tab="tab-variants">
                        <i class="ph ph-stack"></i>
                        <span>Variants &amp; Option Pricing</span>
                        <span class="rfq-tab-count" id="tabVariantsCountBadge">0</span>
                    </button>
                    <button type="button" class="rfq-tab-btn inv-drawer-tab-btn" data-tab="tab-inventory">
                        <i class="ph ph-warehouse"></i>
                        <span>Stock Thresholds &amp; Ledger</span>
                    </button>
                    <button type="button" class="rfq-tab-btn inv-drawer-tab-btn" data-tab="tab-pricing">
                        <i class="ph ph-currency-circle-dollar"></i>
                        <span>Pricing, Margin &amp; Suppliers</span>
                    </button>
                    <button type="button" class="rfq-tab-btn inv-drawer-tab-btn" data-tab="tab-audit">
                        <i class="ph ph-clock-counter-clockwise"></i>
                        <span>Audit Trail</span>
                    </button>
                </nav>
            </div>

            <!-- Form Body -->
            <div class="inv-drawer-body">
                <form id="productForm" onsubmit="saveProductChanges(event)">
                    <input type="hidden" id="formProductId" value="">

                    <!-- Sub-Tab 1: General Info (2-Column Enterprise Bento Layout) -->
                    <div class="drawer-tab-pane active" id="pane-tab-general">
                        <div class="inv-detail-bento-grid">
                            <!-- Left Column: Core Identity + Dynamic Multi-Level Category Hierarchy -->
                            <div class="inv-detail-main-col">
                                <!-- Card 1: Core Product Identification & Packaging -->
                                <div class="inv-detail-section-card">
                                    <div class="inv-detail-section-head">
                                        <div class="inv-detail-section-title">
                                            <i class="ph ph-identification-card"></i>
                                            <span>Core Product Identification &amp; UOM Spec</span>
                                        </div>
                                        <span class="inv-detail-section-badge">Required Master Fields</span>
                                    </div>
                                    <div class="inv-detail-section-body">
                                        <!-- Item Offering Classification: Goods, Service, or Both -->
                                        <div class="inv-item-type-section">
                                            <div class="inv-item-type-label">
                                                <i class="ph ph-cube"></i>
                                                <span>Offering Classification *</span>
                                                <span class="inv-detail-section-badge" id="itemTypeBadge">Physical Goods</span>
                                            </div>
                                            <input type="hidden" id="formItemType" value="goods">
                                            <div class="inv-item-type-grid">
                                                <button type="button" class="inv-item-type-card active" data-item-type="goods" onclick="selectProductItemType('goods')">
                                                    <div class="inv-item-type-top">
                                                        <div class="inv-item-type-icon"><i class="ph ph-package"></i></div>
                                                        <span class="inv-item-type-title">Physical Goods</span>
                                                        <span class="inv-item-type-radio"></span>
                                                    </div>
                                                    <p class="inv-item-type-desc">Physical inventoried items. Requires receiving, stock counts &amp; warehouse storage.</p>
                                                </button>

                                                <button type="button" class="inv-item-type-card" data-item-type="service" onclick="selectProductItemType('service')">
                                                    <div class="inv-item-type-top">
                                                        <div class="inv-item-type-icon"><i class="ph ph-handshake"></i></div>
                                                        <span class="inv-item-type-title">Service / Labor</span>
                                                        <span class="inv-item-type-radio"></span>
                                                    </div>
                                                    <p class="inv-item-type-desc">Intangible service or labor fee. Sold on POS/bills without physical stock tracking.</p>
                                                </button>

                                                <button type="button" class="inv-item-type-card" data-item-type="both" onclick="selectProductItemType('both')">
                                                    <div class="inv-item-type-top">
                                                        <div class="inv-item-type-icon"><i class="ph ph-arrows-merge"></i></div>
                                                        <span class="inv-item-type-title">Both (Hybrid Bundle)</span>
                                                        <span class="inv-item-type-radio"></span>
                                                    </div>
                                                    <p class="inv-item-type-desc">Hybrid package combining tangible physical materials with billable service/prep time.</p>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Top Grid: Left Image Upload Container | Right Stacked Fields (Description, SKU/Barcode, Quantity/UOM) -->
                                        <div class="inv-identity-top-grid">
                                            <!-- Left: Product Image Upload Container -->
                                            <div class="inv-image-upload-container">
                                                <input type="file" id="formProductImageInput" accept="image/*" style="display: none;" onchange="handleProductImageUpload(event)">
                                                <input type="hidden" id="formProductImageData" value="">
                                                <div class="inv-image-dropzone" id="productImageDropzone" onclick="document.getElementById('formProductImageInput').click()" title="Click to upload product photo">
                                                    <img id="productImagePreview" src="" alt="Product Preview" class="inv-image-preview-img" style="display: none;">
                                                    <div class="inv-image-placeholder" id="productImagePlaceholder">
                                                        <div class="inv-image-placeholder-icon">
                                                            <i class="ph ph-image-square"></i>
                                                        </div>
                                                        <span class="inv-image-placeholder-title">Upload Product Image</span>
                                                        <span class="inv-image-placeholder-sub">Click to browse PNG, JPG, WEBP</span>
                                                    </div>
                                                    <button type="button" class="inv-image-remove-btn" id="btnRemoveProductImage" style="display: none;" onclick="clearProductImage(event)" title="Remove Image">
                                                        <i class="ph ph-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Right: 3-Row Stack (1: Description, 2: SKU / Barcode, 3: Quantity / UOM) -->
                                            <div class="inv-identity-fields-stack">
                                                <!-- Row 1: Description (Product Name / Item Title) -->
                                                <div class="inv-form-group">
                                                    <label for="formName">Description / Product Name *</label>
                                                    <input type="text" id="formName" class="inv-form-input" required placeholder="e.g. Signature Spanish Latte">
                                                </div>

                                                <!-- Row 2: SKU / Barcode -->
                                                <div class="inv-form-row-2">
                                                    <div class="inv-form-group">
                                                        <label for="formSku" style="display: flex; justify-content: space-between; align-items: center;">
                                                            <span>SKU Code <strong style="color: #ef4444;">*</strong></span>
                                                            <span id="formSkuStatusBadge" style="font-size: 0.68rem; font-weight: 700; display: none;"></span>
                                                        </label>
                                                        <input type="text" id="formSku" class="inv-form-input" required placeholder="e.g. BEV-001" style="font-family: monospace; font-weight: 700;" autocomplete="off">
                                                        <div id="formSkuError" class="inv-sku-error-msg" style="display: none;">
                                                            <i class="ph ph-warning-circle"></i>
                                                            <span></span>
                                                        </div>
                                                    </div>
                                                    <div class="inv-form-group">
                                                        <label for="formBarcode">Barcode (UPC / EAN)</label>
                                                        <input type="text" id="formBarcode" class="inv-form-input" placeholder="e.g. 4800123456789" style="font-family: monospace;">
                                                    </div>
                                                </div>

                                                <!-- Row 3: Quantity / UOM -->
                                                <div class="inv-form-row-2">
                                                    <div class="inv-form-group">
                                                        <label for="formPackSize">Quantity / Pack Spec *</label>
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
                                            </div>
                                        </div>

                                        <!-- Bottom Row: Product Description & Spec Notes / Allergen & Dietary Tags -->
                                        <div class="inv-form-row-2">
                                            <div class="inv-form-group">
                                                <label for="formDescription">Product Description &amp; Spec Notes</label>
                                                <textarea id="formDescription" class="inv-form-textarea" style="min-height: 76px;" placeholder="Detailed product specifications, notes, or serving instructions..."></textarea>
                                            </div>
                                            <div class="inv-form-group">
                                                <label for="formAllergens">Allergen / Dietary Tags (comma separated)</label>
                                                <input type="text" id="formAllergens" class="inv-form-input" placeholder="e.g. Dairy, Gluten Free, Vegan, Nut Free">
                                                <span style="font-size: 0.71rem; color: var(--inv-text-muted); margin-top: 4px; display: block;">Displayed as dietary warning badges across POS &amp; recipe cards.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Dynamic Multi-Level Category Hierarchy (Cat 1, Cat 2, Cat 3...) -->
                                <div class="inv-detail-section-card">
                                    <div class="inv-detail-section-head">
                                        <div class="inv-detail-section-title">
                                            <i class="ph ph-tree-structure"></i>
                                            <span>Multi-Level Category Classification</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <button type="button" class="inv-btn-quick-create" onclick="openNewCategoryModal()" title="Create a new Parent or Child category">
                                                <i class="ph ph-plus"></i>
                                                <span>Add Category / Child</span>
                                            </button>
                                            <button type="button" class="inv-btn-quick-create" onclick="switchMasterWorkspaceTab('categories')" title="Rename Cat 1, Cat 2 columns or manage hierarchy">
                                                <i class="ph ph-gear"></i>
                                                <span>Category Settings</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="inv-detail-section-body">
                                        <div class="inv-cat-cascade-grid" id="dynamicCategorySelectorsContainer">
                                            <!-- Dynamically populated with Cat 1, Cat 2, Cat 3... cascading dropdowns -->
                                        </div>
                                        <!-- Hidden synced legacy fields for cross-module compatibility -->
                                        <div style="display: none;">
                                            <select id="formCategory"><option value="Beverages">Beverages</option></select>
                                            <select id="formSubcategorySelect"><option value="Standard">Standard</option></select>
                                            <input type="text" id="formSubcategory" value="Standard">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Operational Routing Flags & Inventory Tracking Mode -->
                            <div class="inv-detail-side-col">
                                <div class="inv-detail-section-card">
                                    <div class="inv-detail-section-head">
                                        <div class="inv-detail-section-title">
                                            <i class="ph ph-sliders"></i>
                                            <span>Operational &amp; ERP Routing Rules</span>
                                        </div>
                                    </div>
                                    <div class="inv-detail-section-body" style="display: flex; flex-direction: column; gap: 10px;">
                                        <!-- Track in Inventory + Tracking Method (Lots, Quantity, Serial Number) -->
                                        <div class="inv-track-inventory-box">
                                            <label class="inv-checkbox-card" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom: none;">
                                                <input type="checkbox" id="formTrackStock" checked onchange="handleTrackStockToggle(this.checked)">
                                                <div>
                                                    <div class="inv-checkbox-title">Track in Inventory</div>
                                                    <div class="inv-checkbox-desc">Maintains on-hand balance, low-stock alerts, and syncs with Stock-In &amp; Stocks Overview.</div>
                                                </div>
                                            </label>
                                            <input type="hidden" id="formTrackingBy" value="lots">
                                            <div class="inv-tracking-method-panel" id="trackByOptionsGroup">
                                                <div class="inv-tracking-method-label">
                                                    <i class="ph ph-barcode"></i>
                                                    <span>Track Inventory By:</span>
                                                </div>
                                                <div class="inv-tracking-method-grid">
                                                    <button type="button" class="inv-track-mode-card active" data-track-mode="lots" onclick="selectTrackingByMethod('lots')">
                                                        <div class="inv-track-mode-top">
                                                            <i class="ph ph-calendar-check"></i>
                                                            <span>By Lots</span>
                                                        </div>
                                                        <div class="inv-track-mode-desc">Enables Lot / Batch # &amp; Expiry option in Stock-In</div>
                                                    </button>
                                                    <button type="button" class="inv-track-mode-card" data-track-mode="quantity" onclick="selectTrackingByMethod('quantity')">
                                                        <div class="inv-track-mode-top">
                                                            <i class="ph ph-hash"></i>
                                                            <span>By Quantity</span>
                                                        </div>
                                                        <div class="inv-track-mode-desc">Tracks unit quantity only (hides Lot in Stock-In)</div>
                                                    </button>
                                                    <button type="button" class="inv-track-mode-card" data-track-mode="serial" onclick="selectTrackingByMethod('serial')">
                                                        <div class="inv-track-mode-top">
                                                            <i class="ph ph-qr-code"></i>
                                                            <span>By Serial Number</span>
                                                        </div>
                                                        <div class="inv-track-mode-desc">Unique unit serial number identification</div>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

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
                                            <input type="checkbox" id="formHasBom">
                                            <div>
                                                <div class="inv-checkbox-title">Explode Recipe / BOM on Sale</div>
                                                <div class="inv-checkbox-desc">When sold on POS, deducts sub-ingredients from Item Master instead of this item directly.</div>
                                            </div>
                                        </label>

                                        <label class="inv-checkbox-card">
                                            <input type="checkbox" id="formIsActive" checked onchange="syncDetailStatusPill(this.checked)">
                                            <div>
                                                <div class="inv-checkbox-title">Active Product Status</div>
                                                <div class="inv-checkbox-desc">Visible across active inventory catalog and reporting.</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Tab 2: Product Variants & Option Pricing (Dedicated Full Tab) -->
                    <div class="drawer-tab-pane" id="pane-tab-variants" style="display: none;">
                        <div class="inv-variants-workspace-wrap">
                            <!-- Variant Metrics Header Cluster -->
                            <div class="inv-variant-kpi-grid">
                                <div class="inv-variant-kpi-card">
                                    <div class="inv-variant-kpi-icon"><i class="ph ph-stack"></i></div>
                                    <div>
                                        <div class="inv-variant-kpi-label">Configured Variants</div>
                                        <div class="inv-variant-kpi-val" id="variantKpiCount">0 Options</div>
                                    </div>
                                </div>
                                <div class="inv-variant-kpi-card">
                                    <div class="inv-variant-kpi-icon"><i class="ph ph-tag"></i></div>
                                    <div>
                                        <div class="inv-variant-kpi-label">Price Range (Selling)</div>
                                        <div class="inv-variant-kpi-val" id="variantKpiPriceRange">₱0.00</div>
                                    </div>
                                </div>
                                <div class="inv-variant-kpi-card">
                                    <div class="inv-variant-kpi-icon"><i class="ph ph-coins"></i></div>
                                    <div>
                                        <div class="inv-variant-kpi-label">Cost Range</div>
                                        <div class="inv-variant-kpi-val" id="variantKpiCostRange">₱0.00</div>
                                    </div>
                                </div>
                                <div class="inv-variant-kpi-card">
                                    <div class="inv-variant-kpi-icon"><i class="ph ph-trend-up"></i></div>
                                    <div>
                                        <div class="inv-variant-kpi-label">Avg Profit Margin</div>
                                        <div class="inv-variant-kpi-val" id="variantKpiAvgMargin">0.0%</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Variant Management Table Card -->
                            <div class="inv-detail-section-card" style="margin-top: 14px;">
                                <div class="inv-detail-section-head">
                                    <div class="inv-detail-section-title">
                                        <i class="ph ph-stack"></i>
                                        <span>Product Variants &amp; Individual Option Pricing</span>
                                        <span class="inv-detail-section-badge" id="productVariantsCountBadge">0 Variants</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <button type="button" class="inv-btn-quick-create" onclick="addVariantPreset('size')" title="Quick-add Size variants (Regular / Large / Venti)">
                                            <i class="ph ph-magic-wand"></i>
                                            <span>+ Size Set</span>
                                        </button>
                                        <button type="button" class="inv-btn-quick-create" onclick="addVariantPreset('temp')" title="Quick-add Temp variants (Hot / Iced)">
                                            <i class="ph ph-thermometer"></i>
                                            <span>+ Hot / Iced</span>
                                        </button>
                                        <button type="button" class="inv-btn-quick-create" onclick="addVariantPreset('flavor')" title="Quick-add Flavor variants (Vanilla / Caramel / Hazelnut)">
                                            <i class="ph ph-drop"></i>
                                            <span>+ Flavor Set</span>
                                        </button>
                                        <button type="button" class="inv-action-btn-primary" onclick="addProductVariantRow()" style="padding: 5px 12px; font-size: 0.74rem;">
                                            <i class="ph ph-plus-bold"></i>
                                            <span>Add Variant</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="inv-detail-section-body" id="productVariantsListContainer" style="padding: 16px;">
                                    <!-- Populated dynamically via JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Tab 3: Stock Thresholds & Ledger -->
                    <div class="drawer-tab-pane" id="pane-tab-inventory" style="display: none;">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <!-- Top Grid: Stock Thresholds + Reordering Rules Engine Overview Card -->
                            <div class="inv-detail-bento-grid" style="align-items: stretch;">
                                <div class="inv-detail-section-card" style="height: 100%;">
                                    <div class="inv-detail-section-head">
                                        <div class="inv-detail-section-title">
                                            <i class="ph ph-gauge"></i>
                                            <span>Stock Replenishment Thresholds &amp; Tracking Mode</span>
                                        </div>
                                        <span class="inv-detail-section-badge" id="inventoryTabTrackingBadge">Tracked by: Lots</span>
                                    </div>
                                    <div class="inv-detail-section-body">
                                        <div class="inv-form-row-2">
                                            <div class="inv-form-group">
                                                <label for="formReorderPoint">Global Reorder Point (Trigger Level) *</label>
                                                <input type="number" id="formReorderPoint" class="inv-form-input" min="0" value="15">
                                            </div>
                                            <div class="inv-form-group">
                                                <label for="formTargetStock">Target Par Stock (Max Ceiling) *</label>
                                                <input type="number" id="formTargetStock" class="inv-form-input" min="0" value="50">
                                            </div>
                                        </div>
                                        <div class="inv-form-row-2">
                                            <div class="inv-form-group">
                                                <label for="formSafetyStock">Safety Stock Buffer</label>
                                                <input type="number" id="formSafetyStock" class="inv-form-input" min="0" value="5" placeholder="Emergency buffer">
                                            </div>
                                            <div class="inv-form-group">
                                                <label for="formLeadTimeDays">Supplier Lead Time (Days)</label>
                                                <input type="number" id="formLeadTimeDays" class="inv-form-input" min="1" value="3" placeholder="Days to deliver">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reordering Rules Architecture & Integration Card -->
                                <div class="inv-detail-section-card inv-reorder-rule-summary-card" style="height: 100%;">
                                    <div class="inv-detail-section-head">
                                        <div class="inv-detail-section-title">
                                            <i class="ph ph-git-branch" style="color: #9333ea;"></i>
                                            <span>Automated Reordering Rules Engine</span>
                                        </div>
                                        <span class="inv-status-pill active" style="font-size: 0.68rem;">Auto PO &amp; Transfer Ready</span>
                                    </div>
                                    <div class="inv-detail-section-body" style="justify-content: space-between;">
                                        <p style="font-size: 0.74rem; color: var(--inv-text-medium); margin: 0; line-height: 1.45;">
                                            When net on-hand drops below <strong id="ruleDisplayReorderPoint">15 units</strong>, the ERP auto-evaluates replenishment source:
                                        </p>
                                        <div class="inv-reorder-triggers-list">
                                            <div class="inv-reorder-trigger-item">
                                                <div class="inv-reorder-trigger-bullet po">PO</div>
                                                <div>
                                                    <strong>External Procurement (PO):</strong>
                                                    <span>Drafts Purchase Order to primary vendor when central commissary is low.</span>
                                                </div>
                                            </div>
                                            <div class="inv-reorder-trigger-item">
                                                <div class="inv-reorder-trigger-bullet trf">TRF</div>
                                                <div>
                                                    <strong>Internal Transfer (TRF):</strong>
                                                    <span>Auto-suggests commissary-to-kiosk transfer when branch reaches minimum.</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; flex-wrap: wrap;">
                                            <span style="font-size: 0.69rem; color: var(--inv-text-muted);"><i class="ph ph-clock"></i> Evaluated nightly &amp; post-sales</span>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <button type="button" class="inv-action-btn-secondary" onclick="openReorderRulesModal()" style="padding: 5px 10px; font-size: 0.73rem;">
                                                    <i class="ph ph-gear"></i>
                                                    <span>Configure Item Rule</span>
                                                </button>
                                                <a href="{{ route('inventory.reorder-rules') }}" class="inv-action-btn-secondary" style="padding: 5px 10px; font-size: 0.73rem; text-decoration: none; color: #7e22ce;">
                                                    <i class="ph ph-arrow-square-out"></i>
                                                    <span>Control Tower</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nested Content Section: Secondary Tabs + Sub-Tables -->
                            <div class="inv-nested-section" style="display: flex; flex-direction: column; flex: 1; min-height: 340px;">
                                <div class="inv-subtabs-nav" id="drawerSubtabsNav">
                                    <button type="button" class="inv-subtab-btn active" data-subtab="sub-branch">Branch Stock Levels</button>
                                    <button type="button" class="inv-subtab-btn" data-subtab="sub-locations">Location Tracking (Storage &amp; Bins)</button>
                                    <button type="button" class="inv-subtab-btn" data-subtab="sub-movements">Recent Ledger Log (Source &amp; Destination)</button>
                                </div>

                                <!-- Sub-table 1: Branch Stock Levels with pagination -->
                                <div class="inv-subtable-wrapper" id="subpane-sub-branch" style="flex: 1; min-height: 250px; overflow-y: auto;">
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

                                <!-- Sub-table 2: Location Tracking (Storage Zones, Racks & Bins) -->
                                <div class="inv-subtable-wrapper" id="subpane-sub-locations" style="display: none; flex: 1; min-height: 250px; overflow-y: auto;">
                                    <table class="inv-subtable">
                                        <thead>
                                            <tr>
                                                <th>Facility / Branch</th>
                                                <th>Storage Zone</th>
                                                <th>Aisle / Rack / Shelf</th>
                                                <th>Bin Barcode</th>
                                                <th>Area Behavior</th>
                                                <th class="th-right">On Hand</th>
                                                <th class="th-right">Reserved</th>
                                                <th class="th-right">Available</th>
                                            </tr>
                                        </thead>
                                        <tbody id="locationStockSubtableBody">
                                            <!-- Populated dynamically via JS -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Sub-table 3: Recent Ledger Log with Source & Destination Tracker -->
                                <div class="inv-subtable-wrapper" id="subpane-sub-movements" style="display: none; flex: 1; min-height: 250px; overflow-y: auto;">
                                    <table class="inv-subtable">
                                        <thead>
                                            <tr>
                                                <th>Date &amp; Ref</th>
                                                <th>Movement Type</th>
                                                <th>Source (From)</th>
                                                <th>Destination (To)</th>
                                                <th class="th-right">Qty</th>
                                                <th class="th-right">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ledgerLogSubtableBody">
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

                                <!-- Location stock pagination -->
                                <div class="inv-subtable-pagination" id="locationStockPagination" style="display: none;">
                                    <span id="locationStockPageInfo">Page 1 of 1</span>
                                    <div class="inv-subtable-pagination-nav">
                                        <button type="button" class="inv-subtable-page-btn" id="btnLocationPrev" onclick="navigateLocationPage(-1)">
                                            <i class="ph ph-caret-left"></i>
                                        </button>
                                        <button type="button" class="inv-subtable-page-btn" id="btnLocationNext" onclick="navigateLocationPage(1)">
                                            <i class="ph ph-caret-right"></i>
                                        </button>
                                    </div>
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

                    <!-- Sub-Tab 3: Pricing & Suppliers (2-Column Bento: Left Calculator & Suppliers, Right 3-Month Trend Chart) -->
                    <div class="drawer-tab-pane" id="pane-tab-pricing" style="display: none;">
                        <div class="inv-detail-bento-grid">
                            <!-- Left Column: Costing, Margin & Primary Supplier -->
                            <div class="inv-detail-main-col">
                                <div class="inv-detail-section-card">
                                    <div class="inv-detail-section-head">
                                        <div class="inv-detail-section-title">
                                            <i class="ph ph-calculator"></i>
                                            <span>Costing, Selling Price &amp; Margin Matrix</span>
                                        </div>
                                        <span style="font-size: 0.70rem; color: var(--inv-primary-dark); font-weight: 700;">Auto-computes Margin &amp; Markup</span>
                                    </div>
                                    <div class="inv-detail-section-body">
                                        <div class="inv-form-row-2">
                                            <div class="inv-form-group">
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <label for="formCostPrice">Cost Price (PHP) *</label>
                                                    <span style="font-size: 0.68rem; color: var(--inv-primary-dark); font-weight: 600;" title="Lowest supplier price + 5% markup">Lowest + 5%</span>
                                                </div>
                                                <input type="number" step="0.01" id="formCostPrice" class="inv-form-input" required placeholder="0.00" style="font-variant-numeric: tabular-nums;">
                                            </div>
                                            <div class="inv-form-group">
                                                <label for="formSellingPrice">Selling Price (PHP) *</label>
                                                <input type="number" step="0.01" id="formSellingPrice" class="inv-form-input" required placeholder="0.00" style="font-variant-numeric: tabular-nums; font-weight: 700;">
                                            </div>
                                        </div>

                                        <div class="inv-form-row-2">
                                            <div class="inv-form-group">
                                                <label for="formProfitMargin">Gross Margin / Markup %</label>
                                                <input type="text" id="formProfitMargin" class="inv-form-input" readonly style="background: #f8fafc; font-weight: 700; color: var(--inv-success); font-variant-numeric: tabular-nums;">
                                            </div>
                                            <div class="inv-form-group">
                                                <label for="formTaxRate">Tax Classification</label>
                                                <select id="formTaxRate" class="inv-form-select">
                                                    <option value="12% VAT">12% VAT Inclusive</option>
                                                    <option value="0% Exempt">0% VAT Exempt</option>
                                                    <option value="Zero-Rated">Zero-Rated</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="inv-form-group">
                                            <label for="formSupplier">Primary Accredited Supplier</label>
                                            <input type="text" id="formSupplier" class="inv-form-input" placeholder="e.g. Power Mac Roasters, Golden Grains, Fresh Dairy Corp">
                                        </div>
                                    </div>
                                </div>

                                <!-- Supplier Quotes & Recent Price Updates List -->
                                <div class="inv-supplier-list-card">
                                    <div class="inv-supplier-list-header">
                                        <span><i class="ph ph-Handshake" style="color: var(--inv-primary-dark); margin-right: 4px;"></i> Supplier Price Benchmarks &amp; Updates</span>
                                        <span style="font-size: 0.68rem; color: var(--inv-text-muted);">Recent Vendor Quotes</span>
                                    </div>
                                    <div class="inv-supplier-list-body" id="supplierPricesListBody">
                                        <!-- Dynamically populated with supplier price rows and recent update dates -->
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Column Chart (Last 3 Months Price History) -->
                            <div class="inv-detail-side-col">
                                <div class="inv-pricing-chart-card" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div class="inv-pricing-chart-header">
                                        <div class="inv-pricing-chart-title">
                                            <i class="ph ph-chart-bar" style="color: var(--inv-primary-dark); font-size: 16px;"></i>
                                            <span>Price Trend History (Last 3 Months)</span>
                                        </div>
                                        <span class="inv-pricing-chart-badge" id="chartMonthsBadge">Jul - Sep 2026</span>
                                    </div>
                                    <div class="inv-pricing-chart-canvas-wrapper" style="height: 260px; flex: 1;">
                                        <canvas id="priceHistoryChart"></canvas>
                                    </div>
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

            <!-- Fixed Bottom Action Container with Universal Margins -->
            <footer class="inv-drawer-footer inv-detail-fixed-footer">
                <div class="inv-footer-status-meta">
                    <span class="inv-footer-status-dot"></span>
                    <span id="detailFooterTrackingHint">Synced with Item Master, POS, BOM &amp; Stock-In</span>
                </div>
                <div class="inv-footer-actions-right">
                    <button type="button" class="inv-btn-secondary" onclick="openProductDrawer(window.AppStore.selectedProductId || 'new')" title="Reset unsaved edits">
                        <i class="ph ph-arrow-counter-clockwise"></i>
                        <span>Reset</span>
                    </button>
                    <button type="button" class="inv-btn-primary" onclick="triggerSaveForm()">
                        <i class="ph ph-floppy-disk"></i>
                        <span id="btnSaveText">Save Changes</span>
                    </button>
                </div>
            </footer>
        </section>
    </div>

    <!-- ===================================================================== -->
    <!-- WORKSPACE TAB 3: CATEGORY SETTINGS (LEVEL RENAMER & PARENT/CHILD TREE) -->
    <!-- ===================================================================== -->
    <div class="inv-workspace-pane" id="workspace-categories">
        <div class="inv-cat-settings-grid">
            <!-- Left Card: Category Column Level Renamer (Cat 1, Cat 2, Cat 3...) -->
            <div class="inv-cat-settings-card">
                <div class="inv-cat-settings-header">
                    <div>
                        <h3 class="inv-cat-settings-title">
                            <i class="ph ph-columns" style="color: var(--brand-primary); font-size: 18px;"></i>
                            <span>Category Column Headers</span>
                            <span class="inv-level-depth-badge" id="catLevelDepthBadge">3 Levels</span>
                        </h3>
                        <p class="inv-cat-settings-sub">Manage column labels for your catalog table. Add or delete unused hierarchy levels.</p>
                    </div>
                    <button type="button" class="inv-action-btn-primary" onclick="addCategoryLevelColumn()" title="Add another Category Level Column" style="padding: 7px 14px; font-size: 0.78rem;">
                        <i class="ph ph-plus"></i>
                        <span>Add Level</span>
                    </button>
                </div>
                <div class="inv-cat-settings-body">
                    <div class="inv-level-info-banner">
                        <i class="ph ph-info"></i>
                        <span>Each level corresponds to a dynamic column in your Item Master table. Unused levels with 0 categories can be deleted.</span>
                    </div>
                    <div id="categoryLevelNamesList">
                        <!-- Populated dynamically with rich level cards -->
                    </div>
                    <div class="inv-cat-settings-footer">
                        <button type="button" class="inv-col-quick-link is-reset" onclick="resetCategoryLevelNames()" title="Restore default Cat 1, Cat 2... labels">
                            <i class="ph ph-arrow-counter-clockwise"></i>
                            <span>Reset Defaults</span>
                        </button>
                        <button type="button" class="inv-action-btn-primary" onclick="saveCategoryLevelNamesFromUI()" style="padding: 7px 16px; font-size: 0.78rem;">
                            <i class="ph ph-check"></i>
                            <span>Save Column Names</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Card: Parent & Child Category Hierarchy Tree Manager -->
            <div class="inv-cat-settings-card">
                <div class="inv-cat-settings-header" style="flex-wrap: wrap;">
                    <div>
                        <h3 class="inv-cat-settings-title">
                            <i class="ph ph-tree-structure" style="color: var(--inv-primary-dark); font-size: 18px;"></i>
                            <span>Parent &amp; Child Categories</span>
                        </h3>
                        <p class="inv-cat-settings-sub">Create root categories or nested child categories at any depth to auto-expand table columns.</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <div class="inv-search-wrapper" style="width: 220px;">
                            <i class="ph ph-magnifying-glass inv-search-icon"></i>
                            <input type="text" id="catTreeSearchInput" class="inv-search-input" placeholder="Filter categories..." oninput="renderCategorySettingsTab()" style="height: 34px; font-size: 0.76rem;">
                        </div>
                        <button type="button" class="inv-action-btn-primary" onclick="openNewCategoryModal('')" style="padding: 7px 13px; font-size: 0.78rem;">
                            <i class="ph ph-folder-plus"></i>
                            <span>Add Root Category</span>
                        </button>
                    </div>
                </div>

                <div class="inv-table-responsive">
                    <table class="inv-cat-tree-table">
                        <thead>
                            <tr>
                                <th>Category Hierarchy (Parent / Child)</th>
                                <th>Column Level</th>
                                <th>Parent Category</th>
                                <th>Products</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoryHierarchyTreeBody">
                            <!-- Populated dynamically with parent & child category tree rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Product Category (Supports Parent & Child Hierarchy) -->
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
                <!-- Parent Category Selector (Determines Cat 1, Cat 2, Cat 3... level) -->
                <div class="inv-form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label for="modalCategoryParent">Parent Category (Hierarchy Level)</label>
                        <span id="modalCategoryLevelHint" style="font-size: 0.70rem; color: var(--inv-primary-dark); font-weight: 700;">Column: Cat 1 (Level 1)</span>
                    </div>
                    <select id="modalCategoryParent" class="inv-form-select" onchange="handleModalParentCategoryChange(this.value)">
                        <option value="">— Top-Level Parent (Root Category) —</option>
                    </select>
                </div>

                <div class="inv-form-group">
                    <label for="modalCategoryName">Category Name *</label>
                    <div class="inv-cat-input-wrapper">
                        <input type="text" id="modalCategoryName" class="inv-form-input" required autocomplete="off" placeholder="e.g. Beverages, Espresso Based, Hot Lattes" oninput="handleCategoryInputCheck(this.value)">
                        
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

<!-- Modal: Advanced Reordering Rules & Replenishment Policy -->
<div class="inv-modal-backdrop" id="reorderRulesModal" style="display: none;">
    <div class="inv-modal-card inv-modal-lg">
        <div class="inv-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="inv-drawer-header-icon" style="width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-git-branch"></i>
                </div>
                <div>
                    <h3 class="inv-modal-title" style="margin: 0;">Automated Reordering Rule Setup</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.72rem; color: var(--inv-text-muted);" id="reorderModalSubtitle">Item: PRD-1001 &bull; Signature Spanish Latte</p>
                </div>
            </div>
            <button type="button" class="inv-drawer-close-btn" onclick="closeReorderRulesModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="inv-modal-body" style="display: flex; flex-direction: column; gap: 16px; padding: 20px;">
            <!-- Strategy Selector -->
            <div class="inv-form-group">
                <label style="font-size: 0.76rem; font-weight: 700; color: var(--inv-text-strong);">Replenishment Automation Strategy *</label>
                <div class="inv-tracking-method-grid" style="grid-template-columns: repeat(3, 1fr); margin-top: 6px;">
                    <button type="button" class="inv-track-mode-card active" id="btnRuleMinMax" onclick="selectReorderStrategy('min_max')">
                        <div class="inv-track-mode-top">
                            <i class="ph ph-arrows-down-up"></i>
                            <span>Min-Max Order Point</span>
                        </div>
                        <div class="inv-track-mode-desc">Triggers PO / Transfer up to Max ceiling whenever stock &lt; Min.</div>
                    </button>
                    <button type="button" class="inv-track-mode-card" id="btnRuleCommissary" onclick="selectReorderStrategy('commissary_push')">
                        <div class="inv-track-mode-top">
                            <i class="ph ph-warehouse"></i>
                            <span>Commissary Push</span>
                        </div>
                        <div class="inv-track-mode-desc">Central HQ distributes fixed batch to branch kiosks daily.</div>
                    </button>
                    <button type="button" class="inv-track-mode-card" id="btnRuleJit" onclick="selectReorderStrategy('jit_order')">
                        <div class="inv-track-mode-top">
                            <i class="ph ph-lightning"></i>
                            <span>Just-In-Time (JIT)</span>
                        </div>
                        <div class="inv-track-mode-desc">Zero buffer stock; orders placed strictly against active customer orders.</div>
                    </button>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="inv-form-row-3" style="grid-template-columns: 1fr 1fr 1fr;">
                <div class="inv-form-group">
                    <label for="ruleMinStock">Min Reorder Trigger *</label>
                    <input type="number" id="ruleMinStock" class="inv-form-input" min="0" value="15">
                    <span style="font-size: 0.69rem; color: var(--inv-text-muted);">Stock level that fires auto-order</span>
                </div>
                <div class="inv-form-group">
                    <label for="ruleMaxStock">Max Target Par Stock *</label>
                    <input type="number" id="ruleMaxStock" class="inv-form-input" min="0" value="50">
                    <span style="font-size: 0.69rem; color: var(--inv-text-muted);">Top-up ceiling limit</span>
                </div>
                <div class="inv-form-group">
                    <label for="ruleMoq">Order Multiple / MOQ</label>
                    <input type="number" id="ruleMoq" class="inv-form-input" min="1" value="10">
                    <span style="font-size: 0.69rem; color: var(--inv-text-muted);">Pack bundle order increment</span>
                </div>
            </div>

            <!-- Fulfillment Target & Automation Switches -->
            <div class="inv-form-row-2">
                <div class="inv-form-group">
                    <label for="rulePreferredSource">Fulfillment Route</label>
                    <select id="rulePreferredSource" class="inv-form-select">
                        <option value="PO">Procurement (Auto-generate Draft Purchase Order)</option>
                        <option value="TRF">Internal Transfer (Dispatch from Central Commissary)</option>
                        <option value="HYBRID">Smart Hybrid (Check Commissary first, fallback to PO)</option>
                    </select>
                </div>
                <div class="inv-form-group">
                    <label for="ruleApprovalMode">Order Approval Policy</label>
                    <select id="ruleApprovalMode" class="inv-form-select">
                        <option value="draft">Save as Pending Draft for Purchasing Review</option>
                        <option value="auto_submit">Auto-Submit to Vendor if under ₱5,000 threshold</option>
                    </select>
                </div>
            </div>

            <!-- Simulation Banner -->
            <div style="padding: 12px 14px; border-radius: 9px; background: #faf5ff; border: 1px solid rgba(168, 85, 247, 0.25); display: flex; align-items: flex-start; gap: 10px;">
                <i class="ph ph-sparkle" style="color: var(--inv-primary-dark); font-size: 18px; margin-top: 2px;"></i>
                <div style="font-size: 0.74rem; color: var(--inv-text-strong); line-height: 1.4;">
                    <strong>Simulation Preview:</strong> If current on-hand drops to 12 units (3 below Min of 15), the system will calculate:
                    <code>(50 Max - 12 Current) = 38 units &rarr; Rounded to MOQ 10 = <strong>40 units</strong></code> automatically drafted in the next replenishment cycle.
                </div>
            </div>
        </div>
        <div class="inv-modal-footer">
            <button type="button" class="inv-btn-secondary" onclick="closeReorderRulesModal()">Close</button>
            <button type="button" class="inv-btn-primary" onclick="saveReorderRulesModal()">
                <i class="ph ph-check-bold"></i>
                <span>Save Replenishment Rule</span>
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
