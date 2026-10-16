@extends('layouts.app')

@section('title', 'Internal Transfer - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/internal-transfer.css')
@endpush

@push('blade-data')
<script>
    window.InternalTransferConfig = Object.freeze({
        routes: {
            dispatchTransfer: @json(route('inventory.api.dispatch-transfer')),
            createCustomTransfer: @json(route('inventory.api.create-custom-transfer')),
            internalTransferData: @json(route('inventory.api.internal-transfer-data')),
            receiveTransfer: @json(route('inventory.api.receive-transfer')),
        },
        user: {
            commissaryLead: @json(auth()->user()->full_name ?? auth()->user()->username ?? 'Commissary Lead'),
            receivingLead: @json(auth()->user()->full_name ?? auth()->user()->username ?? 'Branch Receiving Lead'),
        },
        data: {
            initialTransfers: @json($initialTransfers ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialBranches: @json($initialBranches ?? []),
            initialCategories: @json($initialCategories ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/internal-transfer.js')
@endpush

@section('content')

<div class="trf-workspace">

    <!-- =====================================================================
         PAGE HEADER
         ===================================================================== -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row trf-page-header">
            <div class="trf-page-title-group">
                <h1 class="hr-parent-title">
                    <i class="ph ph-arrows-left-right"></i>
                    <span>Internal Transfer</span>
                </h1>
                <p class="hr-parent-subtitle trf-page-subtitle">Manage commissary-to-branch logistics, pending branch transfer requests & backlogs, and direct custom dispatches with real-time stock validation.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Current Stock Levels">
                    <i class="ph ph-squares-four"></i> Stocks Overview
                </a>
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master">
                    <i class="ph ph-folder-simple"></i> Item Master
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="switchTrfTab('tab-custom-transfer')">
                    <i class="ph ph-paper-plane-tilt"></i> New Custom Transfer
                </button>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         PRIMARY TABS NAVIGATION (Renamed & Styled Elegantly)
         1. Transfer Register & Masterlist (List)
         2. Pending Branch Requests & Backlog (Pending Transfer Request)
         3. Direct Push Transfer (Custom Transfer)
         ===================================================================== -->
    <nav class="hr-tabs-wrapper tabs-wrapper" id="internalTransferTabsBar">
        <button type="button" class="tab-btn active" id="tabBtnList" onclick="switchTrfTab('tab-transfer-list')">
            <i class="ph ph-list-dashes"></i>
            <span>Transfer Register & Masterlist</span>
            <span class="tab-count" id="badgeListCount">{{ $initialTransfers->count() }}</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnPendingRequests" onclick="switchTrfTab('tab-pending-requests')">
            <i class="ph ph-clock-countdown"></i>
            <span>Pending Branch Requests & Backlog</span>
            <span class="tab-badge-pill" id="badgePendingCount" style="background: #fef3c7; color: #b45309; border-color: #fde68a;">
                {{ $initialTransfers->where('status', 'PENDING')->count() }} Backlog
            </span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnCustomTransfer" onclick="switchTrfTab('tab-custom-transfer')">
            <i class="ph ph-paper-plane-tilt"></i>
            <span>Direct Push Transfer</span>
            <span class="tab-badge-pill">No Request Required</span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: TRANSFER REGISTER & MASTERLIST (List)
         ===================================================================== -->
    <div id="tab-transfer-list" class="trf-tab-panel active">
        <!-- Filter & Search Toolbar -->
        <div class="trf-card-strip">
            <div class="trf-filter-bar">
                <div class="trf-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="transferSearchInput" class="trf-search-input" placeholder="Search by Transfer #, Destination, Carrier, or SKU... (/)" oninput="filterTransfersList()">
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="transferStatusFilter" class="trf-select" onchange="filterTransfersList()">
                        <option value="ALL">All Statuses</option>
                        <option value="PENDING">Pending Requisition</option>
                        <option value="IN_TRANSIT">In-Transit / Dispatched</option>
                        <option value="COMPLETED">Completed / Received</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <select id="transferTypeFilter" class="trf-select" onchange="filterTransfersList()">
                        <option value="ALL">All Transfer Types</option>
                        <option value="BRANCH_REQUEST">Branch Requisition</option>
                        <option value="CUSTOM_PUSH">Direct Push (No Request)</option>
                    </select>

                    <select id="transferBranchFilter" class="trf-select" onchange="filterTransfersList()">
                        <option value="ALL">All Destination Branches</option>
                        @foreach($initialBranches as $br)
                            <option value="{{ $br->name }}">{{ $br->name }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="hr-btn hr-btn-secondary" onclick="fetchLiveTransferData()" title="Refresh Data">
                        <i class="ph ph-arrows-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Master Table -->
            <div class="trf-table-wrapper">
                <table class="trf-table" id="transfersMasterTable">
                    <thead>
                        <tr>
                            <th>Transfer #</th>
                            <th>Type</th>
                            <th>Origin / Source</th>
                            <th>Destination Branch</th>
                            <th>Items & Qty</th>
                            <th>Dispatched Units</th>
                            <th style="text-align: right;">Valuation (₱)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="transfersTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div id="noTransfersPrompt" style="display: none; padding: 40px; text-align: center; color: var(--trf-text-muted);">
                <i class="ph ph-arrows-left-right" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                No internal transfers match your search criteria.
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: PENDING BRANCH REQUESTS & BACKLOG
         ===================================================================== -->
    <div id="tab-pending-requests" class="trf-tab-panel">
        <div class="trf-card-strip" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-clock-countdown" style="font-size: 22px; color: #d97706;"></i>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">Branch Requisitions & Backlog Queue</h3>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Review items requested by retail branches, compare against central inventory, and fulfill dispatches.</p>
                    </div>
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fetchLiveTransferData()">
                    <i class="ph ph-arrows-clockwise"></i> Refresh Queue
                </button>
            </div>

            <div id="pendingRequestsContainer" style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Populated dynamically via JS -->
            </div>

            <div id="noPendingPrompt" style="display: none; padding: 40px; text-align: center; color: var(--trf-text-muted);">
                <i class="ph ph-check-circle" style="font-size: 40px; color: #10b981; display: block; margin-bottom: 8px;"></i>
                <strong>All branch requisitions are fulfilled!</strong> There are no pending transfer requests or backlogs in queue.
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: CUSTOM TRANSFER (Direct Push Delivery - No Request Required)
         ===================================================================== -->
    <div id="tab-custom-transfer" class="trf-tab-panel">
        <div class="trf-card-strip" style="padding: 22px;">
            <!-- Top Command Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--trf-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-paper-plane-tilt"></i> Direct Push Commissary Workspace
                    </span>
                    <span class="hr-badge hr-badge-neutral">Internal delivery without prior branch request</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetCustomTransferForm()">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Form
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" id="btnDispatchCustomTransfer" onclick="submitCustomTransfer()">
                        <i class="ph ph-truck"></i> ✓ Dispatch Now & Deduct Stock (F10)
                    </button>
                </div>
            </div>

            <!-- Asymmetric Grid -->
            <div class="workspace-asymmetric-grid">

                <!-- Left Column (4.2fr): Logistics & Delivery Route -->
                <div class="panel-col">

                    <!-- Card 1: Route & Origin -->
                    <div class="panel-card" style="border-left: 4px solid var(--trf-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Direct Transfer Code</div>
                                <div style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--trf-primary);">DIR-TRF-{{ date('Y') }}-AUTO</div>
                            </div>
                            <span class="hr-badge hr-badge-purple">Direct Push</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Target Destination Branch *</label>
                            <select id="customDestBranch" class="trf-select" style="font-size: 13px; font-weight: 600;">
                                @foreach($initialBranches as $br)
                                    <option value="{{ $br->id }}" data-name="{{ $br->name }}">{{ $br->name }} ({{ $br->address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Origin / Source Storage *</label>
                            <input type="text" id="customSourceLoc" class="trf-search-input" value="Central Commissary Main Storage" readonly style="background: #f8fafc; font-weight: 600;">
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Transfer Reason Code *</label>
                            <select id="customReasonCode" class="trf-select">
                                <option value="Commissary Bulk Batch Push">Central Commissary Scheduled Bulk Push</option>
                                <option value="Excess Stock Rebalancing">Excess Stock Rebalancing between Locations</option>
                                <option value="New Menu Seasonal Push">New Menu Seasonal Promo Ingredients Kit</option>
                                <option value="Emergency Kitchen Replenishment">Emergency Direct Replenishment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 2: Transport & Driver Details -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-steering-wheel"></i>
                                <span>Carrier, Vehicle & Custody</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Dispatcher / Supervisor *</label>
                                <input type="text" id="customDispatchedBy" class="trf-search-input" style="padding-left: 10px;" value="{{ auth()->user()->full_name ?? auth()->user()->username ?? 'Commissary Lead' }}">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Logistics Carrier / Vehicle</label>
                                <input type="text" id="customCarrierName" class="trf-search-input" style="padding-left: 10px;" placeholder="e.g. In-House Refrigerated Van 1">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Vehicle Plate #</label>
                                <input type="text" id="customDriverPlate" class="trf-search-input" style="padding-left: 10px;" placeholder="e.g. NDF 8820">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Shipping Waybill #</label>
                                <input type="text" id="customWaybill" class="trf-search-input" style="padding-left: 10px;" placeholder="e.g. WB-TRF-2026-004">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Delivery Notes & Special Handling</label>
                            <textarea id="customNotes" class="trf-search-input" style="padding: 8px 10px; height: 50px; resize: vertical;" placeholder="e.g., Maintain cold chain at 2°C to 4°C during transit..."></textarea>
                        </div>
                    </div>

                </div>

                <!-- Right Column (5.8fr): Item Master Catalog Picker & Quantity Validation Grid -->
                <div class="panel-col">

                    <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-package"></i>
                                <span>Transfer Line Items & Stock Validation</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="customItemCountBadge">0 Items</span>
                        </div>

                        <!-- Catalog Quick-Add (Connected to Item Master & Stocks Overview) -->
                        <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                            <div style="flex: 1;">
                                <select id="customCatalogPicker" class="trf-select" style="width: 100%;">
                                    <option value="">-- Select Product from Item Master --</option>
                                    @foreach($initialProducts as $p)
                                        <option value="{{ $p->sku }}" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-category="{{ $p->category }}" data-uom="{{ $p->uom }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $p->current_stock }}" data-location="{{ $p->storage_location }}">
                                            [{{ $p->sku }}] {{ $p->name }} — Available: {{ number_format($p->current_stock, 2) }} {{ $p->uom }} (Cost: ₱{{ number_format($p->cost_price, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="number" id="customQuickQty" class="trf-search-input" style="width: 80px; padding-left: 10px;" placeholder="Qty" min="0.01" step="0.01" value="1.00">
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="addCustomTransferItem()">
                                <i class="ph ph-plus"></i> Add Line
                            </button>
                        </div>

                        <!-- Real-time Quantity Validation Alert Banner (if any item exceeds stock) -->
                        <div id="customOverdraftBanner" class="qty-warning-box" style="display: none;">
                            <i class="ph ph-warning-circle" style="font-size: 18px; color: #dc2626;"></i>
                            <div>
                                <strong style="color: #dc2626;">Stock Overdraft Warning!</strong>
                                <span id="customOverdraftMsg">One or more items exceed current central inventory. Adjust quantities to proceed.</span>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div style="overflow-x: auto; max-height: 380px; overflow-y: auto;">
                            <table class="trf-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th>UOM</th>
                                        <th style="text-align: right;">Stock on Hand</th>
                                        <th style="text-align: center; width: 110px;">Transfer Qty *</th>
                                        <th style="text-align: right;">Unit Cost (₱)</th>
                                        <th style="text-align: right;">Subtotal (₱)</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="customItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="customNoItemsPrompt" style="padding: 30px; text-align: center; color: var(--trf-text-muted); font-size: 13px;">
                            <i class="ph ph-arrows-clockwise" style="font-size: 32px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No items added yet. Select products from the Item Master above to configure your custom direct transfer.
                        </div>

                        <!-- Valuation Summary Strip -->
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: #64748b;">
                                Total Transfer Units: <strong id="customTotalUnitsDisplay" style="color: #0f172a;">0.00</strong>
                            </div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <span>Transfer Valuation:</span>
                                <span style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--trf-primary);" id="customTotalValDisplay">₱0.00</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

</div>

<!-- =====================================================================
     TRANSFER WAYBILL & RECEIVING MODAL
     ===================================================================== -->
<div class="rms-modal-overlay" id="transferModal">
    <div class="rms-modal-card" style="padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-paper-plane-tilt" style="font-size: 24px; color: var(--trf-primary);"></i>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;" id="modalTrfNum">TRF-2026-0001</h3>
                    <div style="font-size: 11.5px; color: #64748b;" id="modalTrfDates">Dispatched at -</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="window.print()">
                    <i class="ph ph-printer"></i> Print Waybill
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeTransferModal()">
                    <i class="ph ph-x"></i> Close
                </button>
            </div>
        </div>

        <div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; margin-bottom: 16px; font-size: 12px;">
                <div>
                    <div><strong>Origin:</strong> <span id="modalSource">-</span></div>
                    <div><strong>Destination Branch:</strong> <span id="modalDest">-</span></div>
                    <div><strong>Transfer Type:</strong> <span id="modalType">-</span></div>
                </div>
                <div>
                    <div><strong>Carrier / Van:</strong> <span id="modalCarrier">-</span></div>
                    <div><strong>Driver Plate:</strong> <span id="modalPlate">-</span></div>
                    <div><strong>Waybill #:</strong> <span id="modalWaybill">-</span></div>
                </div>
            </div>

            <div style="overflow-x: auto; margin-bottom: 16px;">
                <table class="trf-table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th style="text-align: right;">Requested</th>
                            <th style="text-align: right;">Dispatched</th>
                            <th style="text-align: right;">Unit Cost (₱)</th>
                            <th style="text-align: right;">Total Cost (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTbody">
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">
                Total Transfer Valuation: <span id="modalValuationDisplay" style="color: var(--trf-primary); margin-left: 8px;">₱0.00</span>
            </div>

            <div id="modalReceiveActionSection" style="display: none; background: #faf5ff; padding: 14px; border-radius: 12px; border: 1px solid #f3e8ff; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="color: var(--trf-primary);">Destination Branch Acknowledgment</strong>
                        <div style="font-size: 11.5px; color: #64748b;">Confirm physical inspection and receive stock into branch storage.</div>
                    </div>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="confirmReceiveModalTransfer()">
                        <i class="ph ph-check-fat"></i> Confirm Receipt & Close
                    </button>
                </div>
            </div>

            <!-- Signatures Strip for Print -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 11px; text-align: center;">
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Commissary Dispatcher Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Destination Branch Receiving Manager</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
