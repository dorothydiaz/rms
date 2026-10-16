@extends('layouts.app')

@section('title', 'Stock Out / Usage - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/stock-out.css')
@endpush

@push('blade-data')
<script>
    window.StockOutConfig = Object.freeze({
        routes: {
            createStockOutDraft: @json(route('inventory.api.create-stock-out-draft')),
            updateStockOutPack: @json(route('inventory.api.update-stock-out-pack')),
            confirmShipStockOut: @json(route('inventory.api.confirm-ship-stock-out')),
            stocksOverview: @json(route('inventory.stocks-overview')),
            stockOutData: @json(route('inventory.api.stock-out-data')),
        },
        data: {
            initialOrders: @json($initialOrders ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialCategories: @json($initialCategories ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/stock-out.js')
@endpush

@section('content')

<div class="out-workspace">

    <!-- =====================================================================
         PAGE HEADER
         ===================================================================== -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row out-page-header">
            <div class="out-page-title-group">
                <h1 class="hr-parent-title">
                    <i class="ph ph-arrow-up-right"></i>
                    <span>Stock Out / Usage</span>
                </h1>
                <p class="hr-parent-subtitle out-page-subtitle">Manage kitchen requisitions, inter-branch transfers, vendor returns (RMA), and live stock-out fulfillment with physical pick & pack verification.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Current Stock Levels">
                    <i class="ph ph-squares-four"></i> Stocks Overview
                </a>
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master">
                    <i class="ph ph-folder-simple"></i> Item Master
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="switchOutTab('tab-create-draft')">
                    <i class="ph ph-plus-circle"></i> New Requisition Draft
                </button>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         PRIMARY TABS NAVIGATION
         1. List of Dispatches  2. Create Draft Requisition  3. Pick Pack Ship
         ===================================================================== -->
    <nav class="hr-tabs-wrapper tabs-wrapper" id="stockOutTabsBar">
        <button type="button" class="tab-btn active" id="tabBtnList" onclick="switchOutTab('tab-out-list')">
            <i class="ph ph-list-dashes"></i>
            <span>List of Dispatches</span>
            <span class="tab-count" id="badgeListCount">{{ $initialOrders->count() }}</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnCreateDraft" onclick="switchOutTab('tab-create-draft')">
            <i class="ph ph-file-plus"></i>
            <span>Create Draft Requisition</span>
            <span class="tab-badge-pill">New Requisition</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnPickPackShip" onclick="switchOutTab('tab-pick-pack-ship')">
            <i class="ph ph-package"></i>
            <span>Pick Pack Ship</span>
            <span class="tab-badge-pill" id="badgePendingPickCount" style="background: #fef3c7; color: #b45309; border-color: #fde68a;">
                {{ $initialOrders->whereIn('status', ['DRAFT', 'PICKING', 'PACKED'])->count() }} Active
            </span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: LIST OF DISPATCHES (Master Register)
         ===================================================================== -->
    <div id="tab-out-list" class="out-tab-panel active">
        <!-- Filter & Search Toolbar Strip -->
        <div class="out-card-strip">
            <div class="out-filter-bar">
                <div class="out-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="orderSearchInput" class="out-search-input" placeholder="Search orders by SO #, Requester, Destination, or SKU... (/)" oninput="filterOrdersList()">
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="statusFilter" class="out-select" onchange="filterOrdersList()">
                        <option value="ALL">All Statuses</option>
                        <option value="DRAFT">Draft Requisitions</option>
                        <option value="PICKING">In Picking</option>
                        <option value="PACKED">Packed & Ready</option>
                        <option value="SHIPPED">Dispatched / Shipped</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <select id="typeFilter" class="out-select" onchange="filterOrdersList()">
                        <option value="ALL">All Usage Types</option>
                        <option value="KITCHEN_USAGE">Kitchen Operations Usage</option>
                        <option value="BRANCH_TRANSFER">Inter-Branch Transfer</option>
                        <option value="VENDOR_RETURN">Vendor Return (RMA)</option>
                        <option value="SPOILAGE_DISPOSAL">Spoilage / Disposal</option>
                        <option value="STAFF_MEALS">Staff Meals</option>
                        <option value="SAMPLE_TASTING">Sample Tasting</option>
                    </select>

                    <button type="button" class="hr-btn hr-btn-secondary" onclick="fetchLiveStockOutData()" title="Refresh Data">
                        <i class="ph ph-arrows-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Master Table -->
            <div class="out-table-wrapper">
                <table class="out-table" id="ordersMasterTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Usage Purpose</th>
                            <th>Destination / Recipient</th>
                            <th>Requester & Dept</th>
                            <th>Items & Qty</th>
                            <th style="min-width: 150px;">Fulfillment Progress</th>
                            <th style="text-align: right;">Valuation (₱)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div id="noOrdersPrompt" style="display: none; padding: 40px; text-align: center; color: var(--out-text-muted);">
                <i class="ph ph-tray" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                No stock-out dispatches match your filter criteria.
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: CREATION OF DRAFT (Asymmetric Workspace like "Create new PO to received")
         ===================================================================== -->
    <div id="tab-create-draft" class="out-tab-panel">
        <div class="out-card-strip" style="padding: 22px;">
            <!-- Top Command Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--out-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-sliders-horizontal"></i> Direct Outbound Requisition Workspace
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetDraftForm()">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Form
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="saveDraftOrder(false)">
                        <i class="ph ph-floppy-disk"></i> Save as Draft
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="saveDraftOrder(true)">
                        <i class="ph ph-arrow-right"></i> Approve & Move to Pick Pack Ship (F10)
                    </button>
                </div>
            </div>

            <!-- Asymmetric Grid -->
            <div class="workspace-asymmetric-grid">

                <!-- Left Column (4.2fr): Outbound Identity, Destination & Logistics -->
                <div class="panel-col">

                    <!-- Card 1: Requisition Reference & Usage Code -->
                    <div class="panel-card" style="border-left: 4px solid var(--out-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Stock Out Reference</div>
                                <div style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--out-primary);" id="draftRefDisplay">SO-{{ date('Y') }}-AUTO</div>
                            </div>
                            <span class="hr-badge hr-badge-purple">Direct Stock Out</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Stock Out / Usage Reason Code *</label>
                            <select id="draftOrderType" class="out-select" style="font-size: 12.5px; padding: 8px 10px;" onchange="handleOrderTypeChange(this.value)">
                                <option value="KITCHEN_USAGE">Kitchen Operations Line Usage / Daily Production</option>
                                <option value="BRANCH_TRANSFER">Inter-Branch Transfer Out</option>
                                <option value="VENDOR_RETURN">Vendor Return / Supplier RMA (Connect to Vendors)</option>
                                <option value="SPOILAGE_DISPOSAL">Spoilage / Damaged Goods Disposal</option>
                                <option value="STAFF_MEALS">Staff Meals & Banquet Consumables</option>
                                <option value="SAMPLE_TASTING">Sample Tasting & Marketing Demo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 2: Destination & Partner Selection -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-map-pin"></i>
                                <span>Destination & Partner Profile</span>
                            </div>
                            <span class="hr-badge hr-badge-neutral" id="partnerTypeBadge">Internal Station</span>
                        </div>

                        <!-- Dynamic Section A: Vendor Return (Connected to Procurement Vendors) -->
                        <div id="vendorReturnBlock" style="display: none; flex-direction: column; gap: 8px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Select Registered Vendor from Masterlist *</label>
                            <select id="draftVendorSelect" class="out-select" onchange="handleVendorSelect(this.value)">
                                <option value="">-- Choose Vendor from Vendor Masterlist --</option>
                                @foreach($initialVendors as $v)
                                    <option value="{{ $v->id }}" data-name="{{ $v->legal_name }}" data-trade="{{ $v->trade_name }}" data-contact="{{ $v->contact_person }}" data-phone="{{ $v->phone }}" data-email="{{ $v->email }}" data-address="{{ $v->address }}">
                                        {{ $v->vendor_code }} - {{ $v->legal_name }} ({{ $v->category }})
                                    </option>
                                @endforeach
                            </select>

                            <div class="vendor-pill-box" id="vendorPillBox" style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <strong id="vPillName" style="color: #701a75; font-size: 13px;">Vendor Name</strong>
                                    <span class="hr-badge hr-badge-purple" id="vPillTrade">Trade Name</span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; color: #475569;">
                                    <div><i class="ph ph-user"></i> <span id="vPillContact">-</span></div>
                                    <div><i class="ph ph-phone"></i> <span id="vPillPhone">-</span></div>
                                </div>
                                <div style="color: #64748b; font-size: 11px;"><i class="ph ph-map-pin"></i> <span id="vPillAddress">-</span></div>
                            </div>
                        </div>

                        <!-- Dynamic Section B: Branch Transfer Destination -->
                        <div id="branchTransferBlock" style="display: none; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Target Destination Branch *</label>
                            <select id="draftBranchSelect" class="out-select">
                                <option value="Branch 1 - Makati Bistro Kitchen">Branch 1 - Makati Bistro Kitchen</option>
                                <option value="Branch 2 - BGC Bistro & Dining">Branch 2 - BGC Bistro & Dining</option>
                                <option value="Branch 3 - Ortigas Commissary Hub">Branch 3 - Ortigas Commissary Hub</option>
                                <option value="Alabang Satellite Kiosk">Alabang Satellite Kiosk</option>
                            </select>
                        </div>

                        <!-- Dynamic Section C: Kitchen Line Destination -->
                        <div id="kitchenLineBlock" style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Kitchen Prep Station / Department *</label>
                            <select id="draftKitchenStation" class="out-select">
                                <option value="Main Kitchen - Hot Line (Saute & Grill)">Main Kitchen - Hot Line (Saute & Grill)</option>
                                <option value="Cold Kitchen & Salad Pantry">Cold Kitchen & Salad Pantry</option>
                                <option value="Bakery & Pastry Production">Bakery & Pastry Production</option>
                                <option value="Bar & Craft Beverage Line">Bar & Craft Beverage Line</option>
                                <option value="Central Commissary Prep Dock">Central Commissary Prep Dock</option>
                            </select>
                        </div>

                        <!-- Dynamic Section D: Spoilage / Disposal Destination -->
                        <div id="disposalBlock" style="display: none; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Disposal Holding Vault / Protocol</label>
                            <select id="draftDisposalVault" class="out-select">
                                <option value="Condemned Meat & Perishables Freezer">Condemned Meat & Perishables Freezer</option>
                                <option value="Kitchen Organic Compost Bin">Kitchen Organic Compost Bin</option>
                                <option value="Hazardous Waste Sanitation Vault">Hazardous Waste Sanitation Vault</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 3: Logistics, Requester & Priority Details -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-identification-badge"></i>
                                <span>Requisition Schedule & Requester</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Requested By (Chef/Lead) *</label>
                                <input type="text" id="draftRequestedBy" class="out-search-input" style="padding-left: 10px;" value="{{ auth()->user()->full_name ?? auth()->user()->username ?? 'Executive Chef' }}">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Priority Level</label>
                                <select id="draftPriority" class="out-select">
                                    <option value="NORMAL">Normal Priority</option>
                                    <option value="URGENT">Urgent (Dinner Rush)</option>
                                    <option value="EMERGENCY">Emergency Expedited Run</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Requisition / Ticket #</label>
                                <input type="text" id="draftReferenceNo" class="out-search-input" style="padding-left: 10px;" placeholder="e.g. REQ-KT-8021">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Required Date & Time</label>
                                <input type="datetime-local" id="draftRequiredAt" class="out-search-input" style="padding-left: 10px;" value="{{ date('Y-m-d\TH:i') }}">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Special Handling / Kitchen Notes</label>
                            <textarea id="draftNotes" class="out-search-input" style="padding: 8px 10px; height: 58px; resize: vertical;" placeholder="e.g., Keep refrigerated at -2°C, prioritize primal cut selection..."></textarea>
                        </div>
                    </div>

                </div>

                <!-- Right Column (5.8fr): Item Master Quick-Add, Line Items Grid & Valuation -->
                <div class="panel-col">

                    <!-- Card 4: Items Panel & Product Catalog Quick-Add -->
                    <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-shopping-cart"></i>
                                <span>Requisition Line Items & Inventory Balance</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="draftItemCountBadge">0 Items Added</span>
                        </div>

                        <!-- Catalog Quick-Add Bar (Connected to Item Master & Stocks Overview) -->
                        <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                            <div style="flex: 1;">
                                <select id="draftCatalogPicker" class="out-select" style="width: 100%;">
                                    <option value="">-- Quick Pick from Product Catalog (Item Master) --</option>
                                    @foreach($initialProducts as $p)
                                        <option value="{{ $p->sku }}" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-category="{{ $p->category }}" data-uom="{{ $p->uom }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $p->current_stock }}" data-location="{{ $p->storage_location }}">
                                            [{{ $p->sku }}] {{ $p->name }} — Available: {{ number_format($p->current_stock, 2) }} {{ $p->uom }} (₱{{ number_format($p->cost_price, 2) }}/{{ $p->uom }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="number" id="draftQuickQty" class="out-search-input" style="width: 80px; padding-left: 10px;" placeholder="Qty" min="0.01" step="0.01" value="1.00">
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="addPickedCatalogItem()">
                                <i class="ph ph-plus"></i> Add Item
                            </button>
                        </div>

                        <!-- Items Grid Table -->
                        <div style="overflow-x: auto; max-height: 380px; overflow-y: auto;">
                            <table class="out-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th>UOM</th>
                                        <th style="text-align: right;">Stock on Hand</th>
                                        <th style="text-align: center; width: 100px;">Req Qty *</th>
                                        <th style="text-align: right;">Unit Cost (₱)</th>
                                        <th style="text-align: right;">Subtotal (₱)</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="draftItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="draftNoItemsPrompt" style="padding: 28px; text-align: center; color: var(--out-text-muted); font-size: 13px;">
                            <i class="ph ph-basket" style="font-size: 32px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No items added yet. Select a product from the Item Master catalog above to add requisition lines.
                        </div>

                        <!-- Valuation Summary Strip -->
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: #64748b;">
                                Total Requisitioned Units: <strong id="draftTotalUnitsDisplay" style="color: #0f172a;">0.00</strong>
                            </div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <span>Estimated Valuation:</span>
                                <span style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--out-primary);" id="draftTotalValueDisplay">₱0.00</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: PICK PACK SHIP (Execution Workspace with Pack Qty & Line Progress Bar)
         ===================================================================== -->
    <div id="tab-pick-pack-ship" class="out-tab-panel">
        <div class="out-card-strip" style="padding: 22px;">

            <!-- Order Fulfillment Selector Strip -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: 800; color: var(--out-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-package"></i> Pick Pack Ship Engine
                    </span>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <label style="font-size: 12px; font-weight: 600; color: #475569;">Select Order to Fulfill:</label>
                        <select id="pickOrderSelect" class="out-select" style="min-width: 280px;" onchange="loadOrderIntoPickPack(this.value)">
                            <option value="">-- Choose Active Dispatch Order --</option>
                            @foreach($initialOrders->whereIn('status', ['DRAFT', 'PICKING', 'PACKED']) as $ord)
                                <option value="{{ $ord->id }}">
                                    {{ $ord->order_number }} [{{ $ord->status }}] - {{ $ord->destination_location }} ({{ $ord->total_items_count }} items)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="autoMatchAllPackQuantities()">
                        <i class="ph ph-check-square-offset"></i> Auto-Fill Matched (100%)
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="clearAllPackQuantities()">
                        <i class="ph ph-x-circle"></i> Clear Packed
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="savePickPackProgress()">
                        <i class="ph ph-floppy-disk"></i> Save Progress
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="confirmShipOrder()">
                        <i class="ph ph-truck"></i> ✓ Confirm Ship & Deduct Stock (F10)
                    </button>
                </div>
            </div>

            <!-- Asymmetric Grid -->
            <div class="workspace-asymmetric-grid">

                <!-- Left Column (4.0fr): Dispatch Logistics & Shipping Details -->
                <div class="panel-col">

                    <!-- Active Order Summary Card -->
                    <div class="panel-card" style="border-left: 4px solid var(--out-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Active Dispatch</div>
                                <div style="font-family: monospace; font-size: 1.3rem; font-weight: 800; color: var(--out-primary);" id="pickActiveOrderNum">NO ORDER SELECTED</div>
                            </div>
                            <span class="hr-badge hr-badge-neutral" id="pickActiveStatusBadge">Awaiting Selection</span>
                        </div>

                        <div style="font-size: 12px; color: #475569; display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                            <div><strong>Destination:</strong> <span id="pickActiveDestDisplay">-</span></div>
                            <div><strong>Requisitioner:</strong> <span id="pickActiveReqDisplay">-</span></div>
                            <div><strong>Usage Code:</strong> <span id="pickActiveTypeDisplay">-</span></div>
                        </div>
                    </div>

                    <!-- Dispatch Shipping Logistics Details -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-truck"></i>
                                <span>Logistics & Custody Transfer</span>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Warehouse Picker / Verifier Name *</label>
                            <input type="text" id="pickPickerName" class="out-search-input" style="padding-left: 10px;" value="{{ auth()->user()->full_name ?? auth()->user()->username ?? 'Warehouse Picker 1' }}">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Carrier / Vehicle Plate</label>
                                <input type="text" id="pickCarrierName" class="out-search-input" style="padding-left: 10px;" placeholder="e.g. Van B (NDF 8812)">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Shipping Waybill / Slip #</label>
                                <input type="text" id="pickWaybill" class="out-search-input" style="padding-left: 10px;" placeholder="e.g. WB-2026-9041">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Dispatch Timestamp</label>
                            <input type="datetime-local" id="pickDispatchedAt" class="out-search-input" style="padding-left: 10px;" value="{{ date('Y-m-d\TH:i') }}">
                        </div>
                    </div>

                    <!-- Overall Fulfillment Progress Gauge Card -->
                    <div class="panel-card" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-chart-donut"></i>
                                <span>Overall Order Fulfillment</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="pickGaugeBadge">0% Packed</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="line-progress-track" style="height: 12px;">
                                <div class="line-progress-fill fill-partial" id="pickOverallProgressBar" style="width: 0%;"></div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px; color: #475569;">
                                <div style="background: #ffffff; padding: 8px 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <div style="font-size: 10.5px; color: #64748b;">Total Requested</div>
                                    <strong style="font-size: 14px; color: #0f172a;" id="pickTotalReqDisplay">0.00</strong>
                                </div>
                                <div style="background: #ffffff; padding: 8px 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <div style="font-size: 10.5px; color: #64748b;">Total Packed</div>
                                    <strong style="font-size: 14px; color: var(--out-primary);" id="pickTotalPackedDisplay">0.00</strong>
                                </div>
                            </div>

                            <div id="pickFulfillmentAlert" style="font-size: 11.5px; padding: 8px 12px; border-radius: 8px; background: #faf5ff; color: var(--out-primary); border: 1px solid #f3e8ff;">
                                <i class="ph ph-info"></i> Pack physical quantities to verify against requested amounts before confirming dispatch.
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (6.0fr): Physical Pick & Pack Verification Table -->
                <div class="panel-col">

                    <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-check-circle"></i>
                                <span>Line Items Verification & Progress</span>
                            </div>
                            <span class="hr-badge hr-badge-neutral" id="pickItemsCountBadge">0 Items</span>
                        </div>

                        <!-- Pick & Pack Table with NEW Pack Qty Column and Line Progress Bar -->
                        <div style="overflow-x: auto; max-height: 480px; overflow-y: auto;">
                            <table class="out-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th style="text-align: right;">Stock on Hand</th>
                                        <th style="text-align: center;">Req Qty</th>
                                        <th style="text-align: center; width: 140px;">Pack Qty *</th>
                                        <th style="min-width: 170px;">Progress & Status</th>
                                        <th>Lot / Batch #</th>
                                        <th style="text-align: right;">Value (₱)</th>
                                        <th style="text-align: center;">Match</th>
                                    </tr>
                                </thead>
                                <tbody id="pickItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="pickNoItemsPrompt" style="padding: 36px; text-align: center; color: var(--out-text-muted); font-size: 13px;">
                            <i class="ph ph-clipboard-text" style="font-size: 36px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No order selected. Choose an active order from the top dropdown to load the pick, pack & ship workspace.
                        </div>

                        <!-- Footer Actions -->
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: #64748b;">
                                Dispatch Valuation: <strong id="pickTotalValueDisplay" style="color: var(--out-primary); font-size: 1.15rem;">₱0.00</strong>
                            </div>
                            <button type="button" class="hr-btn hr-btn-primary" onclick="confirmShipOrder()">
                                <i class="ph ph-check-fat"></i> Confirm Ship & Deduct Stock
                            </button>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

<!-- =====================================================================
     VIEW ORDER DETAILS & PACKING SLIP MODAL
     ===================================================================== -->
<div class="rms-modal-overlay" id="orderDetailModal">
    <div class="rms-modal-card" style="padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-receipt" style="font-size: 24px; color: var(--out-primary);"></i>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;" id="modalOrderNum">SO-2026-0001</h3>
                    <div style="font-size: 11.5px; color: #64748b;" id="modalOrderDate">Dispatched at -</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="printPackingSlip()">
                    <i class="ph ph-printer"></i> Print Slip
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeOrderDetailModal()">
                    <i class="ph ph-x"></i> Close
                </button>
            </div>
        </div>

        <div id="modalPrintArea">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; margin-bottom: 16px; font-size: 12px;">
                <div>
                    <div><strong>Destination / Station:</strong> <span id="modalDest">-</span></div>
                    <div><strong>Usage Purpose:</strong> <span id="modalType">-</span></div>
                    <div><strong>Requester:</strong> <span id="modalReq">-</span></div>
                </div>
                <div>
                    <div><strong>Warehouse Picker:</strong> <span id="modalPicker">-</span></div>
                    <div><strong>Carrier / Driver:</strong> <span id="modalCarrier">-</span></div>
                    <div><strong>Tracking / Waybill:</strong> <span id="modalWaybill">-</span></div>
                </div>
            </div>

            <div style="overflow-x: auto; margin-bottom: 16px;">
                <table class="out-table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>UOM</th>
                            <th style="text-align: right;">Requested</th>
                            <th style="text-align: right;">Packed / Shipped</th>
                            <th style="text-align: right;">Unit Cost (₱)</th>
                            <th style="text-align: right;">Total Cost (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTableBody">
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">
                Total Dispatched Valuation: <span id="modalTotalValDisplay" style="color: var(--out-primary); margin-left: 8px;">₱0.00</span>
            </div>

            <!-- Signatures Strip for Print -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 11px; text-align: center;">
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Requisitioner Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Warehouse Picker Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Recipient Acknowledged</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
