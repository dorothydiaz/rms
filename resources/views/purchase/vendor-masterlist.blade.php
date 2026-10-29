@extends('layouts.app')

@section('title', 'Vendor Masterlist & Partner Directory - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/vendor-masterlist.css')
@endpush

@push('blade-data')
<script>
    window.VendorMasterlistConfig = Object.freeze({
        routes: {
            purchaseOrders: @json(route('purchase.purchase-orders')),
            rfq: @json(route('purchase.request-quotations')),
            productCategories: @json(route('inventory.product-categories')),
        },
        data: {
            initialVendors: @json($initialVendors ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/vendor-masterlist.js')
@endpush

@section('content')
<div class="vendor-page-container">

    <!-- 1. Header Bar -->
    <header class="vendor-header-bar" id="vendorHeaderBar">
        <div class="vendor-header-title-box">
            <div class="vendor-header-icon">
                <i class="ph ph-truck"></i>
            </div>
            <div>
                <h1>Vendor Masterlist & Partner Directory</h1>
                <p>Manage supplier agreements, logistics terms, and connect wholesale packaging to Item Master pricing</p>
            </div>
        </div>

        <div class="vendor-header-actions">
            <button type="button" class="vendor-btn-secondary" onclick="resetVendorDatabaseToDefaults()" title="Reset mock data to system defaults">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Reset Defaults</span>
            </button>
            <button type="button" class="vendor-btn-secondary" onclick="exportVendorDirectory()">
                <i class="ph ph-download-simple"></i>
                <span>Export Directory</span>
            </button>
            <button type="button" class="vendor-btn-primary" onclick="openAddVendorModal()">
                <i class="ph ph-plus-circle"></i>
                <span>New Vendor Partner</span>
            </button>
        </div>
    </header>

    <!-- =====================================================================
         KANBAN GRID DIRECTORY (Default Main View matching user screenshot)
         ===================================================================== -->
    <div id="vendorKanbanSection" class="vendor-kanban-section">
        <!-- Toolbar with live Search (Suppliers & Items) & Modular Scalable Filter Pills -->
        <div class="vendor-kanban-toolbar">
            <div class="vendor-search-wrap">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="kanbanSearchInput" class="vendor-search-field" placeholder="Search supplier, category, or items (SKU, ingredient, product)..." oninput="handleKanbanSearch(this.value)">
                <button type="button" id="kanbanSearchClearBtn" class="vendor-search-clear" onclick="clearKanbanSearch()" style="display: none;" title="Clear search">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <!-- Modular & Scalable Category / Type Filter Container -->
            <div class="vendor-filter-container">
                <div class="vendor-filter-scroll-wrapper">
                    <button type="button" class="vendor-pill-scroll-btn" id="pillScrollLeft" onclick="scrollFilterPills(-180)" aria-label="Scroll left">
                        <i class="ph ph-caret-left"></i>
                    </button>
                    <div class="vendor-filter-pills" id="vendorFilterPillsContainer">
                        <!-- Populated dynamically via JS: All Suppliers + Active Categories with Live Counts -->
                    </div>
                    <button type="button" class="vendor-pill-scroll-btn" id="pillScrollRight" onclick="scrollFilterPills(180)" aria-label="Scroll right">
                        <i class="ph ph-caret-right"></i>
                    </button>
                </div>

                <!-- Scalable Category Dropdown for Large Taxonomies (10+ or 50+ types) -->
                <div class="vendor-category-select-wrap">
                    <select id="vendorCategorySelect" class="vendor-category-select" onchange="handleCategorySelectChange(this.value)" title="Filter by vendor type">
                        <!-- Populated dynamically with all categories -->
                    </select>
                </div>
            </div>
        </div>

        <!-- Kanban Cards Grid -->
        <div class="vendor-kanban-grid" id="vendorKanbanGrid">
            <!-- Dynamically populated with ERP-style cards matching screenshot -->
        </div>
    </div>

    <!-- =====================================================================
         VENDOR PROFILE WRAPPER (Shown strictly when user clicks "View")
         ===================================================================== -->
    <div class="vendor-profile-wrapper" id="vendorProfileWrapper">

        <!-- Top Breadcrumb & Control Bar -->
        <div class="vendor-detail-nav-bar">
            <div class="vendor-breadcrumb-left">
                <button type="button" class="vendor-back-btn" onclick="closeVendorProfile()" title="Return to vendor directory [Esc]">
                    <i class="ph ph-arrow-left"></i>
                    <span>Back to All Vendors</span>
                    <kbd class="vendor-kbd-badge">Esc</kbd>
                </button>
                <span class="vendor-nav-divider">/</span>
                <span class="vendor-nav-category-badge" id="navVendorCategoryBadge">Dairy & Cold Storage</span>
                <span class="vendor-nav-divider">/</span>
                <span class="vendor-nav-current-name" id="breadcrumbVendorName">Magnolia Fresh Dairy Philippines, Inc.</span>
            </div>

            <div class="vendor-nav-actions-right">
                <div class="vendor-nav-counter" id="vendorNavCounter">Supplier 1 of 12</div>
                <div class="vendor-nav-cycle-btns">
                    <button type="button" class="vendor-btn-secondary vendor-btn-sm" onclick="cycleVendor(-1)" title="Previous Supplier">
                        <i class="ph ph-caret-left"></i> <span>Prev</span>
                    </button>
                    <button type="button" class="vendor-btn-secondary vendor-btn-sm" onclick="cycleVendor(1)" title="Next Supplier">
                        <span>Next</span> <i class="ph ph-caret-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Executive Vendor Profile Hero Banner -->
        <div class="vendor-profile-banner" id="vendorProfileBanner">
            <!-- Left Hero Identity Cluster -->
            <div class="vendor-banner-identity">
                <div class="vendor-big-logo-wrap">
                    <div class="vendor-big-logo" id="vendorBannerLogo">
                        <span>MG</span>
                    </div>
                    <span class="vendor-verified-shield" title="Verified Commercial Supplier Partner">
                        <i class="ph ph-seal-check-fill"></i>
                    </span>
                </div>

                <div class="vendor-banner-info-col">
                    <div class="vendor-banner-title-row">
                        <h2 id="bannerVendorName">Magnolia Fresh Dairy Philippines, Inc.</h2>
                        <!-- Interactive Quick Status Badge & Dropdown -->
                        <div class="vendor-quick-status-wrap">
                            <button type="button" class="vendor-status-pill status-active" id="bannerVendorStatusBtn" onclick="toggleQuickStatusDropdown(event)" title="Click to quickly switch vendor status">
                                <span class="vendor-status-dot" id="bannerStatusDot"></span>
                                <span id="bannerVendorStatus">Active</span>
                                <i class="ph ph-caret-down" style="font-size: 11px; margin-left: 2px;"></i>
                            </button>
                            <div class="vendor-quick-status-menu" id="quickStatusMenu">
                                <div class="vendor-status-option" onclick="quickChangeVendorStatus('Active')">
                                    <span class="vendor-status-dot" style="background:#10b981;"></span> Active Preferred
                                </div>
                                <div class="vendor-status-option" onclick="quickChangeVendorStatus('On Hold')">
                                    <span class="vendor-status-dot" style="background:#f59e0b;"></span> On Hold / Under Review
                                </div>
                                <div class="vendor-status-option" onclick="quickChangeVendorStatus('Inactive')">
                                    <span class="vendor-status-dot" style="background:#94a3b8;"></span> Inactive / Suspended
                                </div>
                                <div class="vendor-status-option" onclick="quickChangeVendorStatus('Blacklisted')">
                                    <span class="vendor-status-dot" style="background:#ef4444;"></span> Blacklisted
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Badges: Code, DBA, TIN, Entity Type -->
                    <div class="vendor-banner-meta-pills">
                        <span class="vendor-meta-pill" onclick="copyToClipboard(document.getElementById('bannerVendorCode').textContent, 'Vendor Code')" title="Click to copy Code">
                            <i class="ph ph-identification-badge"></i>
                            <strong>Code:</strong> <span id="bannerVendorCode">VND-001</span>
                            <i class="ph ph-copy" style="font-size: 11px; opacity: 0.7;"></i>
                        </span>
                        <span class="vendor-meta-pill">
                            <i class="ph ph-tag"></i>
                            <strong>DBA:</strong> <span id="bannerVendorDBA">Magnolia Fresh</span>
                        </span>
                        <span class="vendor-meta-pill" onclick="copyToClipboard(document.getElementById('bannerVendorTIN').textContent, 'Tax ID (TIN)')" title="Click to copy TIN">
                            <i class="ph ph-file-text"></i>
                            <strong>TIN:</strong> <span id="bannerVendorTIN">123-456-789-000</span>
                            <i class="ph ph-copy" style="font-size: 11px; opacity: 0.7;"></i>
                        </span>
                        <span class="vendor-meta-pill" id="bannerVendorEntityPill">
                            <i class="ph ph-buildings"></i>
                            <span id="bannerVendorEntity">Corporation</span>
                        </span>
                    </div>

                    <!-- Direct Quick Communication Bar -->
                    <div class="vendor-banner-contact-strip">
                        <a href="#" id="bannerVendorPhoneLink" class="vendor-contact-link-pill" onclick="handleQuickPhoneCall(event)" title="Call Primary Representative">
                            <i class="ph ph-phone-call"></i>
                            <span id="bannerVendorPhone">+63 917 123 4567</span>
                        </a>
                        <a href="#" id="bannerVendorEmailLink" class="vendor-contact-link-pill" onclick="handleQuickEmail(event)" title="Send Email / Inquiry">
                            <i class="ph ph-envelope-simple"></i>
                            <span id="bannerVendorEmail">maria@magnolia.ph</span>
                        </a>
                        <span class="vendor-contact-link-pill static">
                            <i class="ph ph-map-pin"></i>
                            <span id="bannerVendorLocation">Pasig City, Philippines</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Center/Right KPI Metrics Strip -->
            <div class="vendor-banner-kpis">
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box"><i class="ph ph-clock"></i></div>
                    <div>
                        <span class="vendor-kpi-val" id="bannerLeadTime">2 Days</span>
                        <span class="vendor-kpi-lbl">Lead Time</span>
                    </div>
                </div>
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box" style="color: var(--vendor-primary);"><i class="ph ph-credit-card"></i></div>
                    <div>
                        <span class="vendor-kpi-val" id="bannerPaymentTerms" style="color: var(--vendor-primary);">Net 30 Days</span>
                        <span class="vendor-kpi-lbl">Payment Terms</span>
                    </div>
                </div>
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box" style="color: var(--vendor-success);"><i class="ph ph-barcode"></i></div>
                    <div>
                        <span class="vendor-kpi-val" id="bannerCatalogCount" style="color: var(--vendor-success);">4 SKUs</span>
                        <span class="vendor-kpi-lbl">Catalog SKUs</span>
                    </div>
                </div>
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box" style="color: #8b5cf6;"><i class="ph ph-shield-check"></i></div>
                    <div>
                        <span class="vendor-kpi-val" id="bannerCreditLimit" style="color: #8b5cf6;">₱ 100k</span>
                        <span class="vendor-kpi-lbl">Credit Limit</span>
                    </div>
                </div>
            </div>

            <!-- Right Quick Actions Column -->
            <div class="vendor-banner-actions">
                <button type="button" class="vendor-btn-primary vendor-hero-cta" onclick="openCreatePOModalForCurrentVendor()" title="Issue Purchase Order for this Supplier">
                    <i class="ph ph-file-plus" style="font-size: 18px;"></i>
                    <span>+ Create PO</span>
                </button>
                <div style="display: flex; gap: 8px; width: 100%;">
                    <button type="button" class="vendor-btn-secondary" style="flex: 1; font-size: 0.76rem; padding: 7px 10px;" onclick="exportVendorDossier()" title="Export / Print Vendor Dossier">
                        <i class="ph ph-printer"></i> <span>Dossier</span>
                    </button>
                    <button type="button" class="vendor-btn-secondary" style="flex: 1; font-size: 0.76rem; padding: 7px 10px;" onclick="saveCurrentVendorProfileForm()" title="Save Current Tab Edits [Ctrl+S]">
                        <i class="ph ph-floppy-disk"></i> <span>Save All</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3 Interactive Navigation Tabs (Tab 1, 2, 3 - Skipping Tab 4 & 5) -->
        <nav class="vendor-nav-tabs-bar" id="vendorTabsBar">
            <button type="button" class="vendor-tab-btn active" data-tab="tab-general" onclick="switchVendorTab('tab-general')">
                <i class="ph ph-buildings"></i>
                <span>Tab 1: General Info & Contacts</span>
            </button>
            <button type="button" class="vendor-tab-btn" data-tab="tab-terms" onclick="switchVendorTab('tab-terms')">
                <i class="ph ph-truck"></i>
                <span>Tab 2: Purchasing & Logistics Terms</span>
            </button>
            <button type="button" class="vendor-tab-btn" data-tab="tab-catalog" onclick="switchVendorTab('tab-catalog')">
                <i class="ph ph-barcode"></i>
                <span>Tab 3: Vendor Catalog & Price List (Item Master Link)</span>
                <span class="vendor-tab-count" id="tabCatalogCount">4</span>
            </button>
        </nav>

        <!-- =================================================================
             TAB 1: GENERAL INFO & CONTACTS (FULL EDITING ENABLED)
             ================================================================= -->
        <div class="vendor-tab-pane active" id="pane-tab-general">
            <form id="formGeneralInfo" onsubmit="handleSaveGeneralInfo(event)">
                <div class="vendor-content-card">
                    <!-- 1. VENDOR IDENTITY -->
                    <div class="vendor-section-block">
                        <div class="vendor-section-heading">
                            <span class="vendor-sec-num">1</span>
                            <span>Vendor Identity</span>
                        </div>
                        <div class="vendor-info-grid-3" style="margin-bottom: 16px;">
                            <div class="vendor-form-group">
                                <label for="editVendorCode">Vendor Code * <span class="vendor-form-hint" style="text-transform:none; font-weight:normal;">(Auto-generated / Unique)</span></label>
                                <input type="text" id="editVendorCode" class="vendor-form-input" required placeholder="e.g. VND-001">
                            </div>
                            <div class="vendor-form-group">
                                <label for="editLegalName">Legal Business Name *</label>
                                <input type="text" id="editLegalName" class="vendor-form-input" required placeholder="e.g. Magnolia Fresh Dairy Philippines, Inc.">
                            </div>
                            <div class="vendor-form-group">
                                <label for="editTradeName">Trade / Display Name (DBA)</label>
                                <input type="text" id="editTradeName" class="vendor-form-input" placeholder="e.g. Magnolia Fresh">
                            </div>
                        </div>
                        <div class="vendor-info-grid-category-status">
                            <div class="vendor-form-group">
                                <label for="editCategory">Vendor Category *</label>
                                <div class="vendor-category-control-group">
                                    <select id="editCategory" class="vendor-form-select" required style="flex: 1;">
                                        <!-- Populated dynamically via JS -->
                                    </select>
                                    <button type="button" class="vendor-btn-secondary" onclick="openCategoryManagerModal()" title="Customize and add categories" style="display: inline-flex; align-items: center; gap: 6px; padding: 0 12px; height: 38px; font-size: 0.78rem; font-weight: 700; white-space: nowrap; border-radius: 8px;">
                                        <i class="ph ph-squares-four" style="font-size: 16px; color: var(--vendor-primary);"></i>
                                        <span>Manage Categories</span>
                                    </button>
                                </div>
                                <span class="vendor-form-hint">Customize, insert, or rename vendor categories across the catalog.</span>
                            </div>
                            <div class="vendor-form-group">
                                <label>Vendor Status *</label>
                                <div class="vendor-radio-group" id="vendorStatusRadioGroup">
                                    <label class="vendor-radio-label">
                                        <input type="radio" name="vendorStatus" value="Active" required>
                                        <span class="vendor-status-dot" style="background: #10b981;"></span>
                                        <span>Active</span>
                                    </label>
                                    <label class="vendor-radio-label">
                                        <input type="radio" name="vendorStatus" value="Inactive">
                                        <span class="vendor-status-dot" style="background: #94a3b8;"></span>
                                        <span>Inactive</span>
                                    </label>
                                    <label class="vendor-radio-label">
                                        <input type="radio" name="vendorStatus" value="On Hold">
                                        <span class="vendor-status-dot" style="background: #f59e0b;"></span>
                                        <span>On Hold</span>
                                    </label>
                                    <label class="vendor-radio-label">
                                        <input type="radio" name="vendorStatus" value="Blacklisted">
                                        <span class="vendor-status-dot" style="background: #ef4444;"></span>
                                        <span>Blacklisted</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. TAX & LEGAL REGISTRATION -->
                    <div class="vendor-section-block">
                        <div class="vendor-section-heading">
                            <span class="vendor-sec-num">2</span>
                            <span>Tax & Legal Registration</span>
                        </div>
                        <div class="vendor-info-grid-4">
                            <div class="vendor-form-group">
                                <label for="editTIN">Tax Identification (TIN) *</label>
                                <input type="text" id="editTIN" class="vendor-form-input" required placeholder="e.g. 123-456-789-000">
                            </div>
                            <div class="vendor-form-group">
                                <label for="editVatType">VAT Registration Type *</label>
                                <select id="editVatType" class="vendor-form-select" required>
                                    <option value="VAT Registered (12%)">VAT Registered (12%)</option>
                                    <option value="Non-VAT (3% Percentage Tax)">Non-VAT (3% Percentage Tax)</option>
                                    <option value="Zero-Rated (0%)">Zero-Rated (0%)</option>
                                    <option value="Tax Exempt">Tax Exempt</option>
                                </select>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editEwtRate">Withholding Tax (EWT) Rate *</label>
                                <select id="editEwtRate" class="vendor-form-select" required>
                                    <option value="1% (Purchase of Goods)">1% (Purchase of Goods)</option>
                                    <option value="2% (Purchase of Services)">2% (Purchase of Services)</option>
                                    <option value="5% (Rental / Real Property)">5% (Rental / Real Property)</option>
                                    <option value="0% (Tax Exempt)">0% (Tax Exempt)</option>
                                </select>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editEntityType">Business Entity Type *</label>
                                <select id="editEntityType" class="vendor-form-select" required>
                                    <option value="Corporation">Corporation</option>
                                    <option value="Sole Proprietorship">Sole Proprietorship</option>
                                    <option value="Partnership">Partnership</option>
                                    <option value="Cooperative">Cooperative</option>
                                    <option value="Individual / Single Proprietor">Individual / Single Proprietor</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ADDRESSES & LOCATIONS -->
                    <div class="vendor-section-block">
                        <div class="vendor-section-heading">
                            <span class="vendor-sec-num">3</span>
                            <span>Addresses & Locations</span>
                        </div>
                        <div class="vendor-info-grid-2">
                            <div class="vendor-form-group">
                                <label for="editBillingAddress">Billing / Registered Office *</label>
                                <textarea id="editBillingAddress" class="vendor-form-textarea" rows="2" required placeholder="e.g. Building 4, Megabiz Park, C5 Road, Pasig City"></textarea>
                            </div>
                            <div class="vendor-form-group">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <label for="editDispatchAddress">Dispatch / Warehouse (Pickup)</label>
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.74rem; font-weight: 600; color: var(--vendor-primary); cursor: pointer; text-transform: none;">
                                        <input type="checkbox" id="sameAsBillingCheckbox" onchange="toggleSameAsBilling(this.checked)" style="accent-color: var(--vendor-primary); cursor: pointer;">
                                        Same as billing address
                                    </label>
                                </div>
                                <textarea id="editDispatchAddress" class="vendor-form-textarea" rows="2" placeholder="Same as billing / Custom address for truck dispatch"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 4. CONTACT DIRECTORY (Multi-Contact Support) -->
                    <div class="vendor-section-block" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                            <div class="vendor-section-heading" style="margin-bottom: 0;">
                                <span class="vendor-sec-num">4</span>
                                <span>Contact Directory (Multi-Contact Support)</span>
                            </div>
                            <button type="button" class="vendor-btn-secondary" onclick="openAddContactModal()" style="padding: 6px 12px; font-size: 0.78rem;">
                                <i class="ph ph-user-plus"></i>
                                <span>+ Add Another Contact Person</span>
                            </button>
                        </div>
                        <div class="vendor-table-container" style="overflow-x: auto; border: 1px solid var(--vendor-border-subtle); border-radius: 10px; background: #ffffff;">
                            <table class="vendor-table" id="contactsTable" style="width: 100%; border-collapse: collapse; min-width: 680px;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1px solid var(--vendor-border-subtle);">
                                        <th style="padding: 10px 14px; text-align: left; font-size: 0.74rem; font-weight: 700; color: var(--vendor-text-muted); text-transform: uppercase;">Name</th>
                                        <th style="padding: 10px 14px; text-align: left; font-size: 0.74rem; font-weight: 700; color: var(--vendor-text-muted); text-transform: uppercase;">Role / Department</th>
                                        <th style="padding: 10px 14px; text-align: left; font-size: 0.74rem; font-weight: 700; color: var(--vendor-text-muted); text-transform: uppercase;">Mobile / Phone</th>
                                        <th style="padding: 10px 14px; text-align: left; font-size: 0.74rem; font-weight: 700; color: var(--vendor-text-muted); text-transform: uppercase;">Email</th>
                                        <th style="padding: 10px 14px; text-align: center; font-size: 0.74rem; font-weight: 700; color: var(--vendor-text-muted); text-transform: uppercase;">Primary Contact</th>
                                        <th style="padding: 10px 14px; text-align: right; font-size: 0.74rem; font-weight: 700; color: var(--vendor-text-muted); text-transform: uppercase;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="contactsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div style="display: flex; justify-content: flex-end; padding-top: 16px; margin-top: 20px; border-top: 1px solid var(--vendor-border-subtle);">
                        <button type="submit" class="vendor-btn-primary" id="btnSaveGeneralInfoBottom">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save General Info</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- =================================================================
             TAB 2: PURCHASING & LOGISTICS TERMS (FULL EDITING ENABLED)
             ================================================================= -->
        <div class="vendor-tab-pane" id="pane-tab-terms">
            <form id="formPurchasingTerms" onsubmit="handleSavePurchasingTerms(event)">
                <div class="vendor-content-card">
                    <!-- Top Action Bar -->
                    <div class="vendor-card-header">
                        <div class="vendor-card-title">
                            <i class="ph ph-truck"></i>
                            <span>Tab 2: Purchasing & Logistics Terms</span>
                        </div>
                        <button type="submit" class="vendor-btn-primary" id="btnSaveTermsTop">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Purchasing Terms</span>
                        </button>
                    </div>

                    <!-- 1. PAYMENT & CREDIT TERMS -->
                    <div class="vendor-section-block">
                        <div class="vendor-section-heading">
                            <span class="vendor-sec-num">1</span>
                            <span>Payment & Credit Terms</span>
                        </div>
                        <div class="vendor-info-grid-3">
                            <div class="vendor-form-group">
                                <label for="editPaymentTerms">Payment Terms *</label>
                                <select id="editPaymentTerms" class="vendor-form-select" required>
                                    <option value="Net 30 Days">Net 30 Days</option>
                                    <option value="COD">Cash On Delivery (COD)</option>
                                    <option value="Pre-paid">Pre-paid / Advance Payment</option>
                                    <option value="Net 7 Days">Net 7 Days</option>
                                    <option value="Net 15 Days">Net 15 Days</option>
                                    <option value="Net 60 Days">Net 60 Days</option>
                                </select>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editCreditLimit">Credit Limit (₱)</label>
                                <input type="number" id="editCreditLimit" class="vendor-form-input" min="0" step="1000" placeholder="100000">
                                <span class="vendor-form-hint">(Max allowed unpaid PO balance)</span>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editPaymentMethod">Default Payment Method *</label>
                                <select id="editPaymentMethod" class="vendor-form-select" required>
                                    <option value="Bank Transfer (ACH)">Bank Transfer (ACH / PESONet / InstaPay)</option>
                                    <option value="Corporate Check">Corporate Check</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Direct Debit">Direct Debit / Auto-Debit</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2. BANKING & PAYOUT DETAILS -->
                    <div class="vendor-section-block">
                        <div class="vendor-section-heading">
                            <span class="vendor-sec-num">2</span>
                            <span>Banking & Payout Details <span style="font-size:0.75rem; font-weight:normal; text-transform:none; color:var(--vendor-text-muted);">(For Accounts Payable Transfers)</span></span>
                        </div>
                        <div class="vendor-info-grid-4">
                            <div class="vendor-form-group">
                                <label for="editBankName">Bank Name *</label>
                                <select id="editBankName" class="vendor-form-select" required>
                                    <option value="BDO Unibank">BDO Unibank</option>
                                    <option value="Bank of the Philippine Islands (BPI)">Bank of the Philippine Islands (BPI)</option>
                                    <option value="Metrobank">Metrobank</option>
                                    <option value="UnionBank of the Philippines">UnionBank of the Philippines</option>
                                    <option value="Security Bank">Security Bank</option>
                                    <option value="Land Bank of the Philippines">Land Bank of the Philippines</option>
                                    <option value="RCBC">RCBC</option>
                                    <option value="Philippine National Bank (PNB)">Philippine National Bank (PNB)</option>
                                    <option value="China Banking Corporation (China Bank)">China Bank</option>
                                    <option value="Other Bank">Other Bank</option>
                                </select>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editBankAccountName">Account Name (Payee) *</label>
                                <input type="text" id="editBankAccountName" class="vendor-form-input" required placeholder="e.g. Magnolia Fresh Dairy Philippines, Inc.">
                            </div>
                            <div class="vendor-form-group">
                                <label for="editBankAccountNumber">Bank Account Number *</label>
                                <input type="text" id="editBankAccountNumber" class="vendor-form-input" required placeholder="e.g. 0012-3456-7890">
                            </div>
                            <div class="vendor-form-group">
                                <label for="editBankBranch">Branch / Routing Code</label>
                                <input type="text" id="editBankBranch" class="vendor-form-input" placeholder="e.g. Pasig-Ortigas Branch">
                            </div>
                        </div>
                    </div>

                    <!-- 3. LOGISTICS, LEAD TIME & ORDER CONSTRAINTS -->
                    <div class="vendor-section-block" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <div class="vendor-section-heading">
                            <span class="vendor-sec-num">3</span>
                            <span>Logistics, Lead Time & Order Constraints <span style="font-size:0.75rem; font-weight:normal; text-transform:none; color:var(--vendor-text-muted);">(For Reorder Calculations)</span></span>
                        </div>
                        <div class="vendor-info-grid-4" style="margin-bottom: 16px;">
                            <div class="vendor-form-group">
                                <label for="editLeadTimeDays">Delivery Lead Time *</label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" id="editLeadTimeDays" class="vendor-form-input" min="1" max="60" required placeholder="2" style="width: 90px;">
                                    <span style="font-size: 0.84rem; font-weight: 700; color: var(--vendor-text-strong);">Days</span>
                                </div>
                                <span class="vendor-form-hint">(Time between sending PO and delivery arrival)</span>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editMOV">Minimum Order Value (MOV)</label>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--vendor-text-muted);">₱</span>
                                    <input type="number" id="editMOV" class="vendor-form-input" step="100" min="0" placeholder="5000">
                                </div>
                                <span class="vendor-form-hint">(Minimum total ₱ to accept PO)</span>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editMOQ">Minimum Order Quantity (MOQ)</label>
                                <input type="text" id="editMOQ" class="vendor-form-input" placeholder="e.g. 5 Cases">
                                <span class="vendor-form-hint">(Optional unit constraint)</span>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editFreeDeliveryThreshold">Free Delivery Threshold</label>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--vendor-text-muted);">₱</span>
                                    <input type="number" id="editFreeDeliveryThreshold" class="vendor-form-input" step="100" min="0" placeholder="8000">
                                </div>
                                <span class="vendor-form-hint">(Orders below this pay shipping fee)</span>
                            </div>
                        </div>

                        <div class="vendor-info-grid-3" style="margin-bottom: 16px;">
                            <div class="vendor-form-group">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                                    <label style="margin: 0;">Delivery Days Available</label>
                                    <div style="display: inline-flex; gap: 4px;">
                                        <button type="button" class="vendor-btn-secondary" style="padding: 2px 7px; font-size: 0.68rem; font-weight: 700;" onclick="setDeliveryDaysPreset('weekdays')">Mon-Fri</button>
                                        <button type="button" class="vendor-btn-secondary" style="padding: 2px 7px; font-size: 0.68rem; font-weight: 700;" onclick="setDeliveryDaysPreset('all')">All 7</button>
                                        <button type="button" class="vendor-btn-secondary" style="padding: 2px 7px; font-size: 0.68rem; font-weight: 700;" onclick="setDeliveryDaysPreset('clear')">Clear</button>
                                    </div>
                                </div>
                                <div class="vendor-days-picker" id="vendorDeliveryDaysPicker">
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Mon"> <span>Mon</span>
                                    </label>
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Tue"> <span>Tue</span>
                                    </label>
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Wed"> <span>Wed</span>
                                    </label>
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Thu"> <span>Thu</span>
                                    </label>
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Fri"> <span>Fri</span>
                                    </label>
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Sat"> <span>Sat</span>
                                    </label>
                                    <label class="vendor-day-checkbox-label">
                                        <input type="checkbox" name="deliveryDays" value="Sun"> <span>Sun</span>
                                    </label>
                                </div>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editOrderCutoffTime">Daily Order Cut-off Time *</label>
                                <input type="text" id="editOrderCutoffTime" class="vendor-form-input" required placeholder="e.g. 02:00 PM">
                                <span class="vendor-form-hint">(Orders after this push to next cycle)</span>
                            </div>
                            <div class="vendor-form-group">
                                <label for="editFulfillmentType">Fulfillment Type *</label>
                                <select id="editFulfillmentType" class="vendor-form-select" required>
                                    <option value="Supplier Delivery">Supplier Delivery</option>
                                    <option value="Self-Pickup">Self-Pickup</option>
                                    <option value="3PL / Third-Party Logistics">3PL / Third-Party Logistics</option>
                                </select>
                                <span class="vendor-form-hint">(Supplier Delivery vs. Self-Pickup)</span>
                            </div>
                        </div>

                        <div class="vendor-form-group" style="margin-bottom: 16px;">
                            <label for="editReturnPolicy">Return / Spoilage Policy *</label>
                            <select id="editReturnPolicy" class="vendor-form-select" required>
                                <option value="Credit Memo on next bill">Credit Memo on next bill</option>
                                <option value="Immediate Exchange">Immediate Exchange</option>
                                <option value="Return to Vendor within 48 Hours">Return to Vendor within 48 Hours</option>
                                <option value="No Returns">No Return</option>
                            </select>
                            <span class="vendor-form-hint">(Exchange, Credit Memo, No Return)</span>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div style="display: flex; justify-content: flex-end; padding-top: 16px; margin-top: 20px; border-top: 1px solid var(--vendor-border-subtle);">
                        <button type="submit" class="vendor-btn-primary" id="btnSaveTermsBottom">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Purchasing Terms</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- =================================================================
             TAB 3: VENDOR CATALOG & PRICE LIST (CONNECTED TO ITEM MASTER)
             ================================================================= -->
        <div class="vendor-tab-pane" id="pane-tab-catalog">
            <!-- Enterprise Connection to Item Master Banner -->
            <div class="vendor-im-link-banner">
                <div class="vendor-im-link-text">
                    <i class="ph ph-link-simple-horizontal"></i>
                    <div>
                        <strong>Connected to Item Master (Pricing & Suppliers)</strong>
                        <p>Maps vendor packaging (e.g. Case of 12) directly to Item Master recipe units (Liters/Kg). Changes automatically compute base costs and allow 1-click price synchronization!</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="vendor-btn-secondary" onclick="syncAllCatalogPricesToItemMaster()">
                        <i class="ph ph-arrows-clockwise"></i>
                        <span>Sync All to Item Master</span>
                    </button>
                    <button type="button" class="vendor-btn-primary" onclick="openAddCatalogModal()">
                        <i class="ph ph-plus"></i>
                        <span>Map Item from Item Master</span>
                    </button>
                </div>
            </div>

            <!-- Catalog Items Search Toolbar -->
            <div class="vendor-catalog-search-toolbar">
                <div class="vendor-search-wrap" style="max-width: 380px;">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="catalogSearchInput" class="vendor-search-field" placeholder="Search this supplier's catalog items (SKU, name, UOM)..." oninput="handleCatalogSearch(this.value)">
                </div>
                <div class="vendor-catalog-toolbar-stats">
                    <span id="catalogItemCountBadge">0 items mapped</span>
                </div>
            </div>

            <!-- Catalog Items Table -->
            <div class="vendor-content-card" style="padding: 0; overflow: hidden;">
                <div class="vendor-table-responsive">
                    <table class="vendor-table" id="catalogTable">
                        <thead>
                            <tr>
                                <th>Vendor SKU</th>
                                <th>Item Master Link</th>
                                <th>Purchased UOM</th>
                                <th>Conversion Ratio</th>
                                <th>Negotiated Cost</th>
                                <th>Kitchen Base Cost</th>
                                <th>Item Master Sync</th>
                                <th>Validity</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="catalogTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ==========================================================================
     MODAL: MAP ITEM FROM ITEM MASTER (UOM CONVERSION ENGINE)
     ========================================================================== -->
<div class="vendor-modal-overlay" id="addCatalogModal">
    <div class="vendor-modal-box">
        <div class="vendor-modal-header">
            <div class="vendor-modal-title">
                <i class="ph ph-barcode"></i>
                <span id="catalogModalTitle">Map Item Master SKU & Conversion</span>
            </div>
            <button type="button" class="vendor-modal-close" onclick="closeModal('addCatalogModal')">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form onsubmit="handleSaveCatalogItem(event)">
            <input type="hidden" id="catEditIndex" value="-1">
            <div class="vendor-modal-body">
                <div class="vendor-form-group">
                    <label>Link to Item Master Product *</label>
                    <select id="catItemMasterSelect" class="vendor-form-select" required onchange="handleItemMasterSelectChange(this.value)">
                        <!-- Populated dynamically from ITEM_MASTER_DICTIONARY -->
                    </select>
                </div>

                <div class="vendor-info-grid-2">
                    <div class="vendor-form-group">
                        <label>Vendor SKU Code *</label>
                        <input type="text" id="catSku" class="vendor-form-input" required placeholder="e.g. MAG-MILK-1L">
                    </div>
                    <div class="vendor-form-group">
                        <label>Supplier Display Name *</label>
                        <input type="text" id="catName" class="vendor-form-input" required placeholder="e.g. Barista Fresh Milk">
                    </div>
                </div>

                <div class="vendor-info-grid-2">
                    <div class="vendor-form-group">
                        <label>Vendor UOM (Purchased Packaging) *</label>
                        <select id="catUom" class="vendor-form-select" data-uom-select required onchange="updateConversionPreview()">
                            <option value="CS">Case (CS)</option>
                            <option value="BOX">Box (BOX)</option>
                            <option value="PACK">Pack (PACK)</option>
                            <option value="TRAY">Tray (TRAY)</option>
                            <option value="SCK">Sack (SCK)</option>
                            <option value="BTL">Bottle (BTL)</option>
                            <option value="CAN">Can (CAN)</option>
                            <option value="KG">Kilogram (KG)</option>
                            <option value="L">Liter (L)</option>
                            <option value="PCS">Piece (PCS)</option>
                        </select>
                    </div>
                    <div class="vendor-form-group">
                        <label>Negotiated Cost per UOM (₱) *</label>
                        <input type="number" id="catCost" class="vendor-form-input" step="0.01" min="0.01" required placeholder="1020.00" oninput="updateConversionPreview()">
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                    <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; color: var(--vendor-text-muted); margin-bottom: 8px;">
                        <i class="ph ph-scales" style="color: var(--vendor-primary);"></i> Kitchen Conversion Formula
                    </div>
                    <div class="vendor-info-grid-2">
                        <div class="vendor-form-group">
                            <label>Kitchen Units in 1 Purchase UOM *</label>
                            <input type="number" id="catRatioQty" class="vendor-form-input" min="0.001" step="any" required value="12" oninput="updateConversionPreview()">
                        </div>
                        <div class="vendor-form-group">
                            <label>Kitchen Base UOM *</label>
                            <input type="text" id="catRatioUnit" class="vendor-form-input" readonly style="background: #f1f5f9; font-weight: 700;" value="Liter">
                        </div>
                    </div>

                    <!-- Live Conversion Math Preview Banner -->
                    <div style="margin-top: 10px; padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.78rem; font-weight: 700; color: #166534;" id="conversionFormulaText">1 Case = 12 Liters</span>
                        <span style="font-size: 0.84rem; font-weight: 800; color: #0369a1;" id="conversionResultText">Unit: ₱ 85.00 / Liter</span>
                    </div>
                </div>

                <div class="vendor-form-group">
                    <label>Price Effective Validity</label>
                    <input type="text" id="catValidity" class="vendor-form-input" value="Valid until Dec 31, 2026" placeholder="e.g. Valid until Dec 2026">
                </div>
            </div>
            <div class="vendor-modal-footer">
                <button type="button" class="vendor-btn-secondary" onclick="closeModal('addCatalogModal')">Cancel</button>
                <button type="submit" class="vendor-btn-primary">
                    <i class="ph ph-check"></i>
                    <span>Save & Link to Item Master</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     MODAL: REGISTER NEW VENDOR PARTNER
     ========================================================================== -->
<div class="vendor-modal-overlay" id="addVendorModal">
    <div class="vendor-modal-box" style="max-width: 620px;">
        <div class="vendor-modal-header">
            <div class="vendor-modal-title">
                <i class="ph ph-plus-circle"></i>
                <span>Register New Vendor Partner</span>
            </div>
            <button type="button" class="vendor-modal-close" onclick="closeModal('addVendorModal')">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form onsubmit="handleSaveNewVendor(event)">
            <div class="vendor-modal-body">
                <div class="vendor-info-grid-2">
                    <div class="vendor-form-group">
                        <label>Legal Business Name *</label>
                        <input type="text" id="newLegalName" class="vendor-form-input" required placeholder="e.g. Pacific Mills Foodservice Inc">
                    </div>
                    <div class="vendor-form-group">
                        <label>Trade Name (DBA) *</label>
                        <input type="text" id="newTradeName" class="vendor-form-input" required placeholder="e.g. Pacific Flour & Mills">
                    </div>
                </div>

                <div class="vendor-info-grid-3">
                    <div class="vendor-form-group">
                        <label>Vendor Code *</label>
                        <input type="text" id="newVendorCode" class="vendor-form-input" required placeholder="e.g. VND-PAC-006">
                    </div>
                    <div class="vendor-form-group">
                        <label>TIN / VAT Reg *</label>
                        <input type="text" id="newTIN" class="vendor-form-input" required placeholder="000-000-000-VAT">
                    </div>
                    <div class="vendor-form-group">
                        <label>Category / Vendor Type *</label>
                        <select id="newCategory" class="vendor-form-select" required onchange="handleNewVendorCategoryChange(this.value)">
                            <!-- Populated dynamically via JS -->
                        </select>
                        <input type="text" id="newCustomCategory" class="vendor-form-input" placeholder="Type new vendor type name..." style="display: none; margin-top: 6px;">
                    </div>
                </div>

                <div class="vendor-info-grid-3">
                    <div class="vendor-form-group">
                        <label>Payment Terms *</label>
                        <select id="newTerms" class="vendor-form-select" required>
                            <option value="Net 30">Net 30</option>
                            <option value="Net 15">Net 15</option>
                            <option value="Net 7">Net 7</option>
                            <option value="COD">Cash On Delivery (COD)</option>
                            <option value="Net 60">Net 60</option>
                        </select>
                    </div>
                    <div class="vendor-form-group">
                        <label>Avg Lead Time (Days)</label>
                        <input type="number" id="newLeadTime" class="vendor-form-input" value="3" min="1" max="30">
                    </div>
                    <div class="vendor-form-group">
                        <label>Minimum Order (MOV ₱)</label>
                        <input type="number" id="newMOV" class="vendor-form-input" value="5000" step="100">
                    </div>
                </div>

                <div class="vendor-info-grid-2">
                    <div class="vendor-form-group">
                        <label>City / Location *</label>
                        <input type="text" id="newLocation" class="vendor-form-input" required placeholder="e.g. Mandaluyong City, Philippines">
                    </div>
                    <div class="vendor-form-group">
                        <label>Primary Contact Email *</label>
                        <input type="email" id="newEmail" class="vendor-form-input" required placeholder="orders@supplier.com">
                    </div>
                </div>

                <div class="vendor-form-group">
                    <label>Registered Business Address *</label>
                    <textarea id="newAddress" class="vendor-form-textarea" rows="2" required placeholder="Building, Street, City, Metro Manila"></textarea>
                </div>
            </div>
            <div class="vendor-modal-footer">
                <button type="button" class="vendor-btn-secondary" onclick="closeModal('addVendorModal')">Cancel</button>
                <button type="submit" class="vendor-btn-primary">
                    <i class="ph ph-check-circle"></i>
                    <span>Register Supplier</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     MODAL: ADD / EDIT CONTACT PERSON
     ========================================================================== -->
<div class="vendor-modal-overlay" id="addContactModal">
    <div class="vendor-modal-box">
        <div class="vendor-modal-header">
            <div class="vendor-modal-title">
                <i class="ph ph-user-plus"></i>
                <span id="contactModalTitle">Add Contact Person</span>
            </div>
            <button type="button" class="vendor-modal-close" onclick="closeModal('addContactModal')">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form onsubmit="handleSaveContact(event)">
            <input type="hidden" id="contactEditIndex" value="-1">
            <div class="vendor-modal-body">
                <div class="vendor-info-grid-2">
                    <div class="vendor-form-group">
                        <label>Full Name *</label>
                        <input type="text" id="contactName" class="vendor-form-input" required placeholder="e.g. Maria Clara Ramos">
                    </div>
                    <div class="vendor-form-group">
                        <label>Designated Role *</label>
                        <select id="contactRole" class="vendor-form-select" required>
                            <option value="sales">Sales Rep (For Placing Orders)</option>
                            <option value="billing">Billing / Accounting Rep (Invoice Reconciliation)</option>
                            <option value="dispatch">Warehouse / Dispatch Rep (Delivery Tracking)</option>
                        </select>
                    </div>
                </div>
                <div class="vendor-info-grid-2">
                    <div class="vendor-form-group">
                        <label>Phone / Mobile *</label>
                        <input type="text" id="contactPhone" class="vendor-form-input" required placeholder="+63 917 ...">
                    </div>
                    <div class="vendor-form-group">
                        <label>Email Address *</label>
                        <input type="email" id="contactEmail" class="vendor-form-input" required placeholder="rep@supplier.com">
                    </div>
                </div>
                <div class="vendor-form-group">
                    <label>Official Job Title</label>
                    <input type="text" id="contactTitle" class="vendor-form-input" placeholder="e.g. Senior Key Account Executive">
                </div>
                <div class="vendor-form-group">
                    <label>Operational Hours & Notes</label>
                    <input type="text" id="contactNotes" class="vendor-form-input" placeholder="e.g. Mon-Fri 8am-5pm. Call for rush orders.">
                </div>
                <div class="vendor-form-group" style="display: flex; flex-direction: row; align-items: center; gap: 8px; margin-top: 4px;">
                    <input type="checkbox" id="contactIsDefault" style="width: 17px; height: 17px; accent-color: var(--vendor-primary); cursor: pointer;">
                    <label for="contactIsDefault" style="margin: 0; cursor: pointer; font-size: 0.82rem; font-weight: 700; color: var(--vendor-text-strong); text-transform: none;">
                        Set as Primary / Default Contact Person
                    </label>
                </div>
            </div>
            <div class="vendor-modal-footer">
                <button type="button" class="vendor-btn-secondary" onclick="closeModal('addContactModal')">Cancel</button>
                <button type="submit" class="vendor-btn-primary">
                    <i class="ph ph-check"></i>
                    <span>Save Contact</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================================
     MODAL: CUSTOMIZE & MANAGE VENDOR CATEGORIES (DYNAMIC TAXONOMY)
     ========================================================================== -->
<div class="vendor-modal-overlay" id="categoryManagerModal">
    <div class="vendor-modal-box" style="max-width: 600px;">
        <div class="vendor-modal-header">
            <div class="vendor-modal-title">
                <i class="ph ph-squares-four" style="color: var(--vendor-primary);"></i>
                <span>Customize Vendor Categories</span>
            </div>
            <button type="button" class="vendor-modal-close" onclick="closeModal('categoryManagerModal')">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="vendor-modal-body" style="padding: 20px;">
            <!-- Fast Insert New Category Form -->
            <div style="background: #f8fafc; border: 1px solid var(--vendor-border-subtle); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <label style="display: block; font-size: 0.78rem; font-weight: 800; color: var(--vendor-text-strong); text-transform: uppercase; margin-bottom: 8px;">
                    <i class="ph ph-plus-circle" style="color: var(--vendor-primary);"></i> Add New Category Field
                </label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <input type="text" id="newCategoryInput" class="vendor-form-input" placeholder="e.g. Seafood & Frozen Catch, Packaging & Disposables" style="flex: 1; min-width: 220px;" onkeydown="if(event.key==='Enter'){event.preventDefault(); addNewCategoryFromModal();}">
                    <button type="button" class="vendor-btn-primary" onclick="addNewCategoryFromModal()" style="padding: 0 16px; height: 38px; white-space: nowrap;">
                        <i class="ph ph-plus"></i>
                        <span>Add Category</span>
                    </button>
                </div>
                <div style="font-size: 0.72rem; color: var(--vendor-text-muted); margin-top: 6px;">
                    Inserted categories immediately sync across all vendor forms, filter pills, and master lists.
                </div>
            </div>

            <!-- Existing Categories List -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 0.78rem; font-weight: 800; color: var(--vendor-text-strong); text-transform: uppercase;">
                        Category Directory (<span id="categoryCountBadge">0</span>)
                    </span>
                    <span style="font-size: 0.72rem; color: var(--vendor-text-muted);">Quick-apply or rename categories</span>
                </div>
                <div id="categoryManagerList" style="max-height: 260px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px;">
                    <!-- Dynamically populated via renderCategoryManagerList() -->
                </div>
            </div>
        </div>
        <div class="vendor-modal-footer">
            <button type="button" class="vendor-btn-primary" onclick="closeModal('categoryManagerModal')">Done</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: CREATE PURCHASE ORDER (QUICK ERP PROCUREMENT ISSUANCE)
     ========================================================================== -->
<div class="vendor-modal-overlay" id="quickPOModal">
    <div class="vendor-modal-box" style="max-width: 740px;">
        <div class="vendor-modal-header">
            <div class="vendor-modal-title">
                <i class="ph ph-file-plus" style="color: var(--vendor-primary);"></i>
                <span>Issue Purchase Order: <span id="poVendorNameDisplay" style="color: var(--vendor-text-strong);">Supplier</span></span>
            </div>
            <button type="button" class="vendor-modal-close" onclick="closeModal('quickPOModal')">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form onsubmit="handleCreatePOSubmit(event)">
            <input type="hidden" id="poRefHidden" value="">
            <div class="vendor-modal-body">
                <div class="vendor-info-grid-3" style="margin-bottom: 14px;">
                    <div class="vendor-form-group">
                        <label>PO Reference Number</label>
                        <div style="padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 800; color: var(--vendor-primary-dark); font-size: 0.88rem;" id="poRefDisplay">
                            PO-2026-0000
                        </div>
                    </div>
                    <div class="vendor-form-group">
                        <label>Supplier Code & Terms</label>
                        <div style="padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: var(--vendor-text-medium);">
                            <span id="poVendorCodeDisplay" style="font-weight: 700;">VND-001</span> • <span id="poPaymentTermsDisplay">Net 30 Days</span>
                        </div>
                    </div>
                    <div class="vendor-form-group">
                        <label for="poDeliveryDateInput">Target Delivery Date *</label>
                        <input type="date" id="poDeliveryDateInput" class="vendor-form-input" required>
                    </div>
                </div>

                <div class="vendor-info-grid-2" style="margin-bottom: 14px;">
                    <div class="vendor-form-group">
                        <label for="poPrioritySelect">Order Priority & Classification</label>
                        <select id="poPrioritySelect" class="vendor-form-select">
                            <option value="Standard Replenishment">Standard Replenishment (Default)</option>
                            <option value="Emergency Rush Order">Emergency Rush Order (Critical Stockout)</option>
                            <option value="Commissary Bulk Batch">Commissary Bulk Batch (Scheduled Delivery)</option>
                        </select>
                    </div>
                    <div class="vendor-form-group">
                        <label>Receiving Dock / Commissary Location</label>
                        <input type="text" class="vendor-form-input" value="Main Commissary Central Receiving Dock (Bay 2)" readonly style="background: #f8fafc;">
                    </div>
                </div>

                <!-- Catalog Items Selection Table -->
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <label style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: var(--vendor-text-muted);">
                            Supplier Catalog Items & Order Quantities
                        </label>
                        <span style="font-size: 0.74rem; color: var(--vendor-text-muted);">Enter quantities to purchase</span>
                    </div>
                    <div class="vendor-table-container" style="max-height: 220px; overflow-y: auto; border: 1px solid var(--vendor-border-subtle); border-radius: 10px;">
                        <table class="vendor-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid var(--vendor-border-subtle); position: sticky; top: 0; z-index: 2;">
                                    <th style="padding: 8px 12px; font-size: 0.72rem; text-align: left;">Item Description / SKU</th>
                                    <th style="padding: 8px 12px; font-size: 0.72rem; text-align: left;">Packaging UOM</th>
                                    <th style="padding: 8px 12px; font-size: 0.72rem; text-align: left;">Unit Cost (₱)</th>
                                    <th style="padding: 8px 12px; font-size: 0.72rem; text-align: center;">Order Qty</th>
                                    <th style="padding: 8px 12px; font-size: 0.72rem; text-align: right;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody id="poItemsTableBody">
                                <!-- Populated dynamically by openCreatePOModalForCurrentVendor -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Live Computed Totals & Constraints Banner -->
                <div class="po-summary-banner">
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--vendor-text-muted);">Total Order Value</div>
                        <div class="po-total-display" id="poTotalAmountDisplay">₱ 0.00</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.76rem; text-align: right;">
                        <div id="poMovComplianceBadge"><span style="color:var(--vendor-text-muted);">Minimum Order Value: ₱ 5,000.00</span></div>
                        <div id="poFreeDeliveryBadge"><span style="color:var(--vendor-text-muted);">Free Shipping Threshold: ₱ 8,000.00</span></div>
                    </div>
                </div>

                <div class="vendor-form-group" style="margin-top: 14px;">
                    <label for="poNotesTextarea">Delivery Instructions / Receiving Notes</label>
                    <textarea id="poNotesTextarea" class="vendor-form-textarea" rows="2" placeholder="e.g. Refrigerated truck temperature check required. Delivery window 8:00 AM - 11:00 AM."></textarea>
                </div>
            </div>
            <div class="vendor-modal-footer">
                <button type="button" class="vendor-btn-secondary" onclick="closeModal('quickPOModal')">Cancel</button>
                <button type="submit" class="vendor-btn-primary" style="padding: 8px 18px;">
                    <i class="ph ph-check"></i>
                    <span>Generate Official Purchase Order</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Soft Toast Notification -->
<div id="vendorToast" class="vendor-toast">
    <i class="ph ph-check-circle" style="font-size: 20px;"></i>
    <span id="vendorToastText">Operation successful</span>
</div>

@endsection
