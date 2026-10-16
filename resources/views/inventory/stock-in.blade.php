@extends('layouts.app')

@section('title', 'Stock In / Receiving (GRN) - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/stock-in.css')
@endpush

@push('blade-data')
<script>
    window.StockInConfig = Object.freeze({
        routes: {
            stockInData: @json(route('inventory.api.stock-in-data')),
            receiveStock: @json(route('inventory.api.receive-stock')),
            purchaseOrders: @json(route('purchase.purchase-orders')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Warehouse Logistics Supervisor'),
        },
        data: {
            initialPurchaseOrders: @json($initialPurchaseOrders ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialReceipts: @json($initialReceipts ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/stock-in.js')
@endpush

@section('content')

<div class="grn-workspace">
    <!-- Header Row -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row grn-header-row">
            <div class="grn-title-group">
                <h1 class="hr-parent-title">
                    <i class="ph ph-tray-arrow-down"></i>
                    <span>Stock In / Receiving (GRN)</span>
                </h1>
                <p class="hr-parent-subtitle">Purchase Order Receiving, Backlog Tracking, and Direct Inbound Receiving Builder</p>
            </div>
            <div class="hr-page-actions grn-actions-group">
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Current Stock Levels">
                    <i class="ph ph-squares-four"></i> Stocks Overview
                </a>
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Item Master">
                    <i class="ph ph-folder-simple"></i> Item Master
                </a>
                <a href="{{ route('purchase.purchase-orders') }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Purchase Orders Workspace">
                    <i class="ph ph-receipt"></i> Purchase Orders
                </a>
                <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="exportOrdersToCsv()">
                    <i class="ph ph-file-csv"></i> Export CSV
                </button>
                <button class="hr-btn hr-btn-primary hr-btn-sm" onclick="switchGrnTab('tab-create-po')">
                    <i class="ph ph-plus-circle"></i> + Create New Receiving Order (F2)
                </button>
            </div>
        </div>
    </div>



    <!-- =====================================================================
         PRIMARY TABS-WRAPPER BAR
         1. All Orders  2. Pending & Backlogs  3. Direct Receiving
         ===================================================================== -->
    <nav class="hr-tabs-wrapper tabs-wrapper" id="grnTabsBar">
        <button type="button" class="tab-btn active" id="tabBtnPoList" onclick="switchGrnTab('tab-po-list')">
            <i class="ph ph-list-dashes"></i>
            <span>All Orders</span>
            <span class="tab-count" id="badgePoListCount">0</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnPendingPo" onclick="switchGrnTab('tab-pending-po')">
            <i class="ph ph-clock-countdown"></i>
            <span>Pending &amp; Backlogs</span>
            <span class="tab-count" id="badgePendingPoCount">0</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnCreatePo" onclick="switchGrnTab('tab-create-po')">
            <i class="ph ph-plus-circle"></i>
            <span>Direct Receiving</span>
            <span class="tab-badge-pill">Bypass PO</span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: LIST OF THE PO (Master List connected to Purchase Module)
         ===================================================================== -->
    <div id="tab-po-list" class="grn-tab-panel">
        <div class="grn-card-strip">
            <div class="grn-filter-bar">
                <div class="grn-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="poListSearchInput" class="grn-search-input" placeholder="Search orders by PO #, Vendor, Stall, or Item... (/)" oninput="renderPoMasterList()">
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="poListStatusFilter" class="grn-select" onchange="renderPoMasterList()">
                        <option value="ALL">All Order Statuses</option>
                        <option value="Approved / Issued">Approved / Ready to Receive</option>
                        <option value="Partially Received">Partially Received (Backlog)</option>
                        <option value="Fully Received">Fully Received</option>
                        <option value="Draft / Pending Review">Draft / Pending</option>
                        <option value="Short-Closed / Completed">Short-Closed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <select id="poListTypeFilter" class="grn-select" onchange="renderPoMasterList()">
                        <option value="ALL">All PO Types</option>
                        <option value="vendor">Commercial Vendor</option>
                        <option value="wet_market">Wet Market Direct</option>
                        <option value="ad_hoc">Direct / Bypass Order</option>
                    </select>
                </div>
            </div>

            <div class="grn-table-container">
                <table class="grn-table">
                    <thead>
                        <tr>
                            <th>PO Reference</th>
                            <th>Order Date</th>
                            <th>Vendor / Supplier</th>
                            <th>Sourcing Channel</th>
                            <th>Delivery Location</th>
                            <th style="text-align: center;">Line Items</th>
                            <th style="text-align: right;">Total Amount</th>
                            <th style="text-align: center;">Payment Status</th>
                            <th style="text-align: center;">Order Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poMasterTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: PENDING PO (Backlogs & Open Deliveries Queue)
         ===================================================================== -->
    <div id="tab-pending-po" class="grn-tab-panel" style="display: none;">
        <div class="grn-card-strip">
            <div class="grn-filter-bar" style="background: #fffbeb; border-bottom: 1px solid #fde68a;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-warning-circle" style="font-size: 22px; color: #b45309;"></i>
                    <span style="font-size: 13px; font-weight: 600; color: #92400e;">
                        Pending Deliveries & Backlogs: Showing active Purchase Orders with pending shipments or partially received quantities awaiting dock fulfillment.
                    </span>
                </div>
            </div>

            <div class="grn-table-container">
                <table class="grn-table">
                    <thead>
                        <tr>
                            <th>PO Reference</th>
                            <th>Vendor / Supplier</th>
                            <th>Expected Delivery</th>
                            <th>Delivery Destination</th>
                            <th style="text-align: center;">Ordered Qty</th>
                            <th style="text-align: center;">Already Received</th>
                            <th style="text-align: center;">Backlog / Open Balance</th>
                            <th style="text-align: right;">Backlog Value</th>
                            <th style="text-align: center;">Delivery State</th>
                            <th style="text-align: center;">Fulfillment Action</th>
                        </tr>
                    </thead>
                    <tbody id="pendingPoTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: CREATE NEW PO TO RECEIVED
         Reusing the Request for Quotation (RFQ) Builder Asymmetric Workspace
         ===================================================================== -->
    <div id="tab-create-po" class="grn-tab-panel" style="display: none;">
        <div class="grn-card-strip" style="padding: 20px;">
            <!-- Top Command Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--grn-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-sliders-horizontal"></i> Direct Inbound Receiving Workspace (Bypass PO)
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetDirectReceivingForm()">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Form
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="submitDirectReceivingOrder()">
                        <i class="ph ph-check-circle"></i> ✓ Approve & Receive Stock In (F10)
                    </button>
                </div>
            </div>

            <!-- RFQ Builder Asymmetric Workspace Grid -->
            <div class="rfq-builder-workspace-grid">

                <!-- Left Column (4.2fr): Inbound Identity, Vendor & Settlement Details -->
                <div class="rfq-builder-left-col">

                    <!-- Card 1: Receiving Reference & Inflow Purpose -->
                    <div class="rfq-panel-card" style="border-left: 4px solid var(--grn-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Direct Receiving Reference</div>
                                <div style="font-family: monospace; font-size: 1.2rem; font-weight: 800; color: var(--grn-primary);" id="directRecRefDisplay">DIR-REC-2026-0001</div>
                            </div>
                            <span class="hr-badge hr-badge-purple">Direct Dock Inflow</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Receipt Reason / Purpose Code *</label>
                            <select id="directReasonCode" class="grn-select" style="font-size: 12px; padding: 7px 10px;">
                                <option value="Direct Spot Purchase">Direct Spot Purchase / Walk-In</option>
                                <option value="Emergency Market Run">Emergency Wet Market Run</option>
                                <option value="Vendor Promotional Sample">Vendor Promotional Free Sample</option>
                                <option value="Customer Return / Exchange">Customer Return / Exchange</option>
                                <option value="Commissary Inter-Branch Inflow">Commissary Inter-Branch Transfer In</option>
                                <option value="Consignment Stock In">Consignment Stock In</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 2: Vendor Selection & Partner Profile -->
                    <div class="rfq-panel-card">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-buildings"></i>
                                <span>Vendor Selection & Partner Profile</span>
                            </div>
                            <span class="hr-badge hr-badge-neutral">Vendor Masterlist</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Select Registered Vendor *</label>
                            <select id="directVendorSelect" class="grn-select" onchange="handleDirectVendorSelect(this.value)">
                                <option value="">-- Choose Approved Vendor from Masterlist --</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>

                        <!-- Vendor Pill Box -->
                        <div class="rfq-vendor-pill-box" id="directVendorPillBox" style="display: none;">
                            <div class="rfq-vendor-avatar" id="directVendorAvatar">VM</div>
                            <div class="rfq-vendor-summary">
                                <div style="font-weight: 700; font-size: 13px; color: #0f172a;" id="directVendorTradeName">Vendor Name</div>
                                <div style="font-size: 11px; color: #64748b;" id="directVendorCategoryTag">Category: Raw Ingredients</div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Direct Stall / Walk-in Supplier Name (if unlisted)</label>
                            <input type="text" id="directCustomStallName" class="grn-search-input" style="padding-left: 12px;" placeholder="e.g. Farmer's Market Seafood Section Stall 8">
                        </div>
                    </div>

                    <!-- Card 3: Inbound Logistics & Delivery Slip -->
                    <div class="rfq-panel-card">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-truck"></i>
                                <span>Inbound Logistics & Carrier</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Supplier DR / Slip # *</label>
                                <input type="text" id="directDeliverySlip" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. DR-884920">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Receiving Date & Time *</label>
                                <input type="datetime-local" id="directReceivedAt" class="grn-search-input" style="padding-left: 10px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Delivery Location</label>
                                <select id="directDeliveryLocation" class="grn-select" style="font-size: 12px; padding: 6px 10px;">
                                    <option value="Central Commissary - Receiving Dock 1">Central Commissary - Dock 1</option>
                                    <option value="Branch 1 - Makati Bistro">Branch 1 - Makati Bistro</option>
                                    <option value="Branch 2 - BGC Bistro Kitchen">Branch 2 - BGC Bistro Kitchen</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Carrier / Driver & Plate</label>
                                <input type="text" id="directCarrier" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. Purchaser Vehicle (NDF 8812)">
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Financial Settlement & Payment Integration -->
                    <div class="rfq-panel-card">
                        <div class="rfq-panel-header" style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="rfq-panel-title">
                                <i class="ph ph-bank"></i>
                                <span>Financial Settlement & Payment</span>
                            </div>
                            <span id="directPaymentStatusBadge" class="hr-badge hr-badge-neutral" style="font-size: 11px;">Unpaid / Credit</span>
                        </div>

                        <!-- Mini Financial Calculation KPIs -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; margin-bottom: 2px;">
                            <div>
                                <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Due Total</div>
                                <div id="directSettlementGrossDisplay" style="font-size: 13px; font-weight: 800; color: #0f172a;">₱0.00</div>
                            </div>
                            <div>
                                <div style="font-size: 10px; font-weight: 700; color: #059669; text-transform: uppercase;">Paid Out</div>
                                <div id="directSettlementPaidDisplay" style="font-size: 13px; font-weight: 800; color: #059669;">₱0.00</div>
                            </div>
                            <div>
                                <div style="font-size: 10px; font-weight: 700; color: #dc2626; text-transform: uppercase;">Balance Due</div>
                                <div id="directSettlementBalanceDisplay" style="font-size: 13px; font-weight: 800; color: #dc2626;">₱0.00</div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Settlement Method *</label>
                            <select id="directSettlementMode" class="grn-select" onchange="handleDirectSettlementChange(this.value)">
                                <option value="IMMEDIATE_COD">Immediate Settlement (Cash-on-Delivery / Dock Cash Out)</option>
                                <option value="CREDIT_NET30">Standard Credit (Net 30) - Generate Pending AP Bill</option>
                                <option value="ADVANCE_APPLIED">Pre-Paid / Advance Payment Applied</option>
                            </select>
                        </div>

                        <!-- Amount to Disburse (₱) field with Quick Pay Full Balance button -->
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;" for="directPaymentAmount">Amount to Disburse / Settle (₱) *</label>
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fillDirectFullBalance()" style="font-size: 10.5px; padding: 1px 7px; height: 20px; color: #059669; border-color: #a7f3d0; background: #ecfdf5;" title="Fill full capitalized valuation">
                                    <i class="ph ph-lightning"></i> Pay Full Amount
                                </button>
                            </div>
                            <input type="number" step="0.01" min="0" id="directPaymentAmount" class="grn-search-input" style="padding-left: 10px; font-weight: 700; font-size: 13px; color: #0f172a;" placeholder="0.00" oninput="handleDirectPaymentAmountChange(this.value)">
                        </div>

                        <div id="directCodFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Payment Mode</label>
                                <select id="directPaymentMethod" class="grn-select" style="font-size: 12px; padding: 6px 10px;">
                                    <option value="Cash / Currency">Cash / Currency</option>
                                    <option value="Petty Cash Fund">Petty Cash Fund</option>
                                    <option value="Trade Credit (Net 30/15)">Trade Credit (Net 30/15)</option>
                                    <option value="Company Check">Company Check (Spot / Post-Dated)</option>
                                    <option value="Bank Transfer / Electronic">Bank Transfer / Electronic</option>
                                    <option value="GCash / Maya">GCash / Maya</option>
                                    <option value="Corporate Credit Card">Corporate Credit Card</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Official Receipt (OR) / Ref #</label>
                                <input type="text" id="directPaymentRef" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. OR #882019, Check #00412">
                            </div>
                        </div>

                        <div id="directDisbursementExtraFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Disbursing Fund Source</label>
                                <select id="directPaymentFundSource" class="grn-select" style="font-size: 12px; padding: 6px 10px;">
                                    <option value="Branch Petty Cash Fund">Branch Petty Cash Fund</option>
                                    <option value="Main Commissary Checking Acct">Main Commissary Checking Account</option>
                                    <option value="Purchaser Cash Advance">Purchaser Cash Advance Fund</option>
                                    <option value="Corporate Card Account">Corporate Card Account</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Payment Date</label>
                                <input type="date" id="directPaymentDate" class="grn-search-input" style="padding-left: 10px;">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Financial Remarks / Settlement Notes</label>
                            <input type="text" id="directPaymentRemarks" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. Paid in cash at dock upon unloading">
                        </div>
                    </div>

                </div>

                <!-- Right Column (5.8fr): Product Catalog, Receiving Line Items, Landed Costs & Valuation -->
                <div class="rfq-builder-right-col">

                    <!-- Card 5: Items Panel & Product Catalog Quick-Add -->
                    <div class="rfq-panel-card" style="flex: 1;">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-package"></i>
                                <span>Receiving Line Items & Inspection</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="directItemCountBadge">0 Items Added</span>
                        </div>

                        <!-- Catalog Quick-Add Bar -->
                        <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                            <div style="flex: 1;">
                                <select id="directCatalogPicker" class="grn-select" style="width: 100%;">
                                    <option value="">-- Quick Pick from Product Catalog (Item Master) --</option>
                                    <!-- Populated from rms_inventory_products -->
                                </select>
                            </div>
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="addPickedCatalogItem()">
                                <i class="ph ph-plus"></i> Add Item
                            </button>
                        </div>

                        <!-- Items Grid Table -->
                        <div style="overflow-x: auto; max-height: 380px; overflow-y: auto;">
                            <table class="grn-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th>Purchasing UOM</th>
                                        <th style="text-align: center;">UOM Conv</th>
                                        <th style="text-align: center;">Received Qty</th>
                                        <th style="text-align: right;">Unit Cost (₱) *</th>
                                        <th style="text-align: right;">Subtotal (₱)</th>
                                        <th style="text-align: center;">Trace / Lot</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="directItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="directNoItemsPrompt" style="padding: 24px; text-align: center; color: var(--grn-text-muted); font-size: 13px;">
                            <i class="ph ph-shopping-cart-simple" style="font-size: 32px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No items added yet. Pick a product from the catalog above to add receiving lines.
                        </div>
                    </div>

                    <!-- Card 6: Landed Cost Allocation Surcharges (Collapsible) -->
                    <div class="rfq-panel-card">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-calculator" style="color: var(--grn-primary);"></i>
                                <span>Landed Cost Surcharges (Freight & Handling)</span>
                            </div>
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleDirectLandedCost()">
                                <i class="ph ph-caret-down"></i> Surcharges
                            </button>
                        </div>

                        <div id="directLandedCostPanel" style="display: none; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569;">Freight / Delivery Fee (₱)</label>
                                    <input type="number" id="directFreightFee" class="grn-search-input" style="padding-left: 8px;" value="0" min="0" step="0.01" oninput="calculateDirectTotals()">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569;">Customs / Tariffs (₱)</label>
                                    <input type="number" id="directCustomsFee" class="grn-search-input" style="padding-left: 8px;" value="0" min="0" step="0.01" oninput="calculateDirectTotals()">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569;">Port Handling (₱)</label>
                                    <input type="number" id="directHandlingFee" class="grn-search-input" style="padding-left: 8px;" value="0" min="0" step="0.01" oninput="calculateDirectTotals()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7: Accounting Capitalization & Summary Total -->
                    <div class="rfq-panel-card" style="background: linear-gradient(135deg, rgba(250, 245, 255, 0.9), rgba(255, 255, 255, 0.95)); border: 1.5px solid #e9d5ff;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <div style="font-size: 11.5px; font-weight: 700; color: #7e22ce; text-transform: uppercase;">Real-time Asset Valuation:</div>
                                <div style="font-family: monospace; font-size: 11px; color: #4c1d95;">
                                    [DR] 1200 Inventory Asset &bull; [CR] 2150 GRNI / Cash Outflow
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Capitalized Value</div>
                                <div style="font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: var(--grn-primary);" id="directTotalValuationDisplay">₱0.00</div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     SLIDE-OVER DRAWER: INBOUND RECEIVING & INSPECTION FOR EXISTING POs
     ========================================================================= -->
<div class="grn-drawer-overlay" id="grnInspectionDrawer">
    <div class="grn-drawer">
        <div class="grn-drawer-header">
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--grn-text-strong); display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-tray-arrow-down" style="color: var(--grn-primary);"></i> Inbound Receiving Inspection
                </h2>
                <div style="font-size: 12px; color: var(--grn-text-muted);" id="drawerPoSubtitle">Receiving fulfillment for Purchase Order</div>
            </div>
            <button class="grn-drawer-close" onclick="closeInspectionDrawer()"><i class="ph ph-x"></i></button>
        </div>

        <div class="grn-drawer-body grn-insp-body">
            <!-- Summary strip: PO info | Delivery doc | Payment linkage -->
            <div class="grn-insp-grid">
                <section class="grn-insp-card" aria-label="Purchase order">
                    <h3 class="grn-insp-h"><i class="ph ph-receipt"></i> Purchase Order <span id="drawerPoStatus" class="grn-insp-chip"></span></h3>
                    <dl class="grn-insp-dl">
                        <dt>Supplier</dt><dd id="drawerVendorName" style="font-weight:700;">--</dd>
                        <dt>Ordered</dt><dd id="drawerOrderDate">--</dd>
                        <dt>Expected</dt><dd id="drawerExpectedDate">--</dd>
                        <dt>Deliver to</dt><dd id="drawerDeliverTo">--</dd>
                    </dl>
                </section>

                <section class="grn-insp-card" aria-label="Delivery document">
                    <h3 class="grn-insp-h"><i class="ph ph-truck"></i> Delivery Document</h3>
                    <label class="grn-insp-lbl" for="drawerDeliverySlip">Delivery Slip / DR # *</label>
                    <input type="text" id="drawerDeliverySlip" class="grn-search-input grn-insp-input" placeholder="e.g. DR-SMF-9941">
                    <label class="grn-insp-lbl" for="drawerPaymentRef">Payment / OR Ref (optional)</label>
                    <input type="text" id="drawerPaymentRef" class="grn-search-input grn-insp-input" placeholder="e.g. OR-10234">
                </section>

                <section class="grn-insp-card grn-insp-pay" aria-label="Payment linkage">
                    <h3 class="grn-insp-h"><i class="ph ph-credit-card"></i> Payment <span id="drawerPayStatus" class="grn-insp-chip"></span></h3>
                    <dl class="grn-insp-dl">
                        <dt>Method</dt><dd id="drawerPayMethod">--</dd>
                        <dt>PO Total</dt><dd id="drawerPayTotal">--</dd>
                        <dt>Paid</dt><dd id="drawerPayPaid" style="color:#047857;font-weight:700;">--</dd>
                        <dt>Balance</dt><dd id="drawerPayBalance" style="color:#b45309;font-weight:700;">--</dd>
                    </dl>
                </section>
            </div>

            <!-- Over-Tolerance Warning Box -->
            <div id="drawerOverToleranceBox" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 12px; font-size: 12px; color: #92400e; align-items: center; justify-content: space-between;">
                <div><strong>⚠️ Over-Receiving Warning:</strong> Delivered units exceed standard 5% tolerance. Supervisor Override required.</div>
                <button type="button" class="hr-btn hr-btn-sm hr-btn-primary" onclick="openSupervisorOverrideModal()">Authorize</button>
            </div>

            <!-- Line Items (compressed) -->
            <section class="grn-insp-card" aria-label="Line items inspection">
                <h3 class="grn-insp-h"><i class="ph ph-list-checks"></i> Line Items &amp; Quantity Inspection <span class="grn-insp-hint">Rejected = Delivered − Accepted</span></h3>
                <div style="overflow-x: auto;">
                    <table class="grn-table grn-insp-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="c">Ordered · Rcvd · Open</th>
                                <th class="c">Delivered</th>
                                <th class="c">Accepted</th>
                                <th class="c">Rejected / Reason</th>
                                <th class="r">Value</th>
                                <th class="c" title="Short-close remaining units">SC</th>
                            </tr>
                        </thead>
                        <tbody id="drawerLinesTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Payment & receiving history (linked to PO) -->
            <section class="grn-insp-card" aria-label="Payment and receiving history">
                <h3 class="grn-insp-h"><i class="ph ph-clock-counter-clockwise"></i> Payment &amp; Receiving History <span id="drawerHistCount" class="grn-insp-chip"></span></h3>
                <div style="overflow-x: auto;">
                    <table class="grn-table grn-insp-table">
                        <thead>
                            <tr><th>Date</th><th>Type</th><th>Reference</th><th>Method</th><th class="r">Amount</th><th>Note</th></tr>
                        </thead>
                        <tbody id="drawerHistoryBody"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="grn-drawer-footer">
            <div>
                <span style="font-size: 11px; color: #64748b;">Total Received Asset Value:</span>
                <div style="font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: var(--grn-primary);" id="drawerTotalAssetValue">₱0.00</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeInspectionDrawer()">Cancel</button>
                <button type="button" class="hr-btn hr-btn-success" id="btnConfirmDrawerReceive" onclick="commitInspectionReceipt()">
                    <i class="ph ph-check-circle"></i> ✓ Complete Stock In
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: SUPERVISOR APPROVAL OVERRIDE
     ========================================================================= -->
<div class="grn-modal-backdrop" id="supervisorOverrideModal">
    <div class="grn-dialog">
        <div class="grn-dialog-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-shield-check" style="font-size: 22px; color: #b45309;"></i>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Supervisor Over-Delivery Override</h3>
            </div>
            <button class="grn-drawer-close" onclick="closeSupervisorOverrideModal()"><i class="ph ph-x"></i></button>
        </div>
        <div class="grn-dialog-body">
            <div style="font-size: 12px; color: #64748b;">
                Delivered count exceeds standard 5% tolerance allowance. Please enter supervisor authorization PIN to permit inventory reception.
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11.5px; font-weight: 600;">Supervisor PIN (Default: 1234) *</label>
                <input type="password" id="supervisorPinInput" class="grn-search-input" style="padding-left: 10px;" placeholder="Enter PIN">
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11.5px; font-weight: 600;">Supervisor Name</label>
                <input type="text" id="supervisorNameInput" class="grn-search-input" style="padding-left: 10px;" value="Engr. Roberto Santos (Warehouse Manager)">
            </div>
        </div>
        <div class="grn-dialog-footer">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeSupervisorOverrideModal()">Cancel</button>
            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="verifySupervisorPin()">Authorize</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: TRACEABILITY (Batch, Lot, Expiry)
     ========================================================================= -->
<div class="grn-modal-backdrop" id="traceModal">
    <div class="grn-dialog">
        <div class="grn-dialog-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-barcode" style="font-size: 22px; color: var(--grn-primary);"></i>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Batch & Traceability Parameters</h3>
            </div>
            <button class="grn-drawer-close" onclick="closeTraceModal()"><i class="ph ph-x"></i></button>
        </div>
        <div class="grn-dialog-body">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11.5px; font-weight: 600;">Batch / Lot Number *</label>
                <input type="text" id="traceLotInput" class="grn-search-input" style="padding-left: 10px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 11.5px; font-weight: 600;">Manufacturing Date (MFG)</label>
                    <input type="date" id="traceMfgInput" class="grn-search-input" style="padding-left: 10px;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 11.5px; font-weight: 600;">Expiration Date (EXP) *</label>
                    <input type="date" id="traceExpInput" class="grn-search-input" style="padding-left: 10px;">
                </div>
            </div>
        </div>
        <div class="grn-dialog-footer">
            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="saveTraceModalData()">Save Traceability</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: PURCHASE ORDER LINE ITEMS VIEWER & BUILDER DISPATCHER
     ========================================================================= -->
<div class="grn-modal-backdrop" id="poItemsViewerModal">
    <div class="grn-dialog wide" style="max-height: 88vh;">
        <div class="grn-dialog-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: var(--grn-purple-bg); color: var(--grn-primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--grn-text-strong);" id="poModalPoRef">PO-XXXX-XXXX</h3>
                        <span id="poModalStatus"></span>
                    </div>
                    <div style="font-size: 12px; color: var(--grn-text-muted);" id="poModalVendor">Vendor / Supplier</div>
                </div>
            </div>
            <button class="grn-drawer-close" onclick="closePoItemsModal()"><i class="ph ph-x"></i></button>
        </div>

        <div class="grn-dialog-body" style="gap: 16px;">
            <!-- Meta KPI Strip -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Issued Date</span>
                    <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;" id="poModalOrderDate">--</div>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Expected Delivery</span>
                    <div style="font-size: 12.5px; font-weight: 700; color: #0284c7;" id="poModalDeliveryDate">--</div>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Delivery Location</span>
                    <div style="font-size: 12px; font-weight: 600; color: #334155;" id="poModalLocation">--</div>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Gross Total</span>
                    <div style="font-size: 13.5px; font-weight: 800; color: #047857;" class="grn-currency" id="poModalTotalValue">₱0.00</div>
                </div>
            </div>

            <!-- Items Table Container -->
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff;">
                <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12.5px; font-weight: 700; color: var(--grn-text-strong);">
                        <i class="ph ph-package"></i> Ordered Line Items (<span id="poModalTotalItems">0 items</span>)
                    </span>
                    <span style="font-size: 12px; font-weight: 600; color: #b45309;">
                        Open Backlog: <strong id="poModalTotalOpen">0 units</strong>
                    </span>
                </div>
                <div style="overflow-x: auto; max-height: 360px;">
                    <table class="grn-table" style="margin: 0;">
                        <thead>
                            <tr style="background: #ffffff;">
                                <th>SKU</th>
                                <th>Item Name & Description</th>
                                <th style="text-align: center;">Ordered</th>
                                <th style="text-align: center;">Received</th>
                                <th style="text-align: center;">Pending / Open</th>
                                <th style="text-align: right;">Unit Cost</th>
                                <th style="text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="poModalItemsTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grn-dialog-footer" style="justify-content: space-between;">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closePoItemsModal()">
                Close
            </button>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" id="poModalBuilderBtn" onclick="pushPoToReceivingBuilder(currentInspectedPoNumber)">
                    <i class="ph ph-arrow-fat-line-right"></i> Move to Receiving Builder (Tab 3)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Element -->
<div id="grnToast" style="position: fixed; bottom: 24px; right: 28px; z-index: 9999; display: none; padding: 12px 20px; border-radius: 10px; background: #0f172a; color: #ffffff; font-size: 13px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.25); display: flex; align-items: center; gap: 10px; opacity: 0; transition: opacity 0.25s ease;">
    <i id="grnToastIcon" class="ph ph-check-circle" style="font-size: 20px; color: #10b981;"></i>
    <span id="grnToastMsg">Notification message</span>
</div>

@endsection
