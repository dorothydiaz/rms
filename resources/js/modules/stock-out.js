/* ==========================================================================
   STOCK OUT / USAGE APPLICATION SCRIPT
   Seamlessly connected to Stocks Overview, Item Master, and Vendors
   ========================================================================== */

// Hydrated Global State
    let rawOrders = window.StockOutConfig?.data?.initialOrders || [];
    let rawProducts = window.StockOutConfig?.data?.initialProducts || [];
    let rawVendors = window.StockOutConfig?.data?.initialVendors || [];
    let rawCategories = window.StockOutConfig?.data?.initialCategories || [];

// Draft Builder State
let draftItems = [];

// Pick Pack Ship State
let activePickOrder = null;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    renderOrdersList();
    renderDraftTable();
    setupKeyboardShortcuts();
});

/* --------------------------------------------------------------------------
   TAB NAVIGATION
   -------------------------------------------------------------------------- */
function switchOutTab(tabId) {
    document.querySelectorAll('.out-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) targetPanel.classList.add('active');

    if (tabId === 'tab-out-list') document.getElementById('tabBtnList').classList.add('active');
    if (tabId === 'tab-create-draft') document.getElementById('tabBtnCreateDraft').classList.add('active');
    if (tabId === 'tab-pick-pack-ship') document.getElementById('tabBtnPickPackShip').classList.add('active');

    // Scroll window smoothly to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* --------------------------------------------------------------------------
   TAB 1: LIST OF DISPATCHES
   -------------------------------------------------------------------------- */
function renderOrdersList() {
    const tbody = document.getElementById('ordersTableBody');
    const searchVal = (document.getElementById('orderSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('statusFilter')?.value || 'ALL';
    const typeVal = document.getElementById('typeFilter')?.value || 'ALL';

    const filtered = rawOrders.filter(ord => {
        if (statusVal !== 'ALL' && ord.status !== statusVal) return false;
        if (typeVal !== 'ALL' && ord.order_type !== typeVal) return false;
        if (searchVal) {
            const num = (ord.order_number || '').toLowerCase();
            const req = (ord.requested_by || '').toLowerCase();
            const dest = (ord.destination_location || '').toLowerCase();
            const hasSku = (ord.items || []).some(it => (it.sku || '').toLowerCase().includes(searchVal) || (it.item_name || '').toLowerCase().includes(searchVal));
            if (!num.includes(searchVal) && !req.includes(searchVal) && !dest.includes(searchVal) && !hasSku) {
                return false;
            }
        }
        return true;
    });

    // Update KPI counters
    updateKpis();

    const noPrompt = document.getElementById('noOrdersPrompt');
    if (filtered.length === 0) {
        tbody.innerHTML = '';
        noPrompt.style.display = 'block';
        return;
    }
    noPrompt.style.display = 'none';

    let html = '';
    filtered.forEach(ord => {
        const reqQty = parseFloat(ord.total_requested_qty) || 0;
        const packQty = parseFloat(ord.total_packed_qty) || 0;
        const pct = reqQty > 0 ? Math.min(100, Math.round((packQty / reqQty) * 100)) : 0;

        let statusBadge = '<span class="hr-badge hr-badge-neutral">Draft</span>';
        if (ord.status === 'PICKING') statusBadge = '<span class="hr-badge hr-badge-blue"><i class="ph ph-arrows-clockwise"></i> Picking</span>';
        if (ord.status === 'PACKED') statusBadge = '<span class="hr-badge hr-badge-amber"><i class="ph ph-package"></i> Packed</span>';
        if (ord.status === 'SHIPPED') statusBadge = '<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Shipped</span>';
        if (ord.status === 'CANCELLED') statusBadge = '<span class="hr-badge hr-badge-red"><i class="ph ph-x"></i> Cancelled</span>';

        let typeBadge = `<span class="hr-badge hr-badge-purple">${formatOrderType(ord.order_type)}</span>`;
        let destIcon = 'ph-map-pin';
        if (ord.order_type === 'VENDOR_RETURN') destIcon = 'ph-buildings';
        if (ord.order_type === 'BRANCH_TRANSFER') destIcon = 'ph-share-network';

        html += `
            <tr>
                <td>
                    <strong style="color: var(--out-primary); cursor: pointer;" onclick="openOrderDetailModal(${ord.id})">
                        ${ord.order_number}
                    </strong>
                    <div style="font-size: 11px; color: #94a3b8;">${formatDate(ord.created_at)}</div>
                </td>
                <td>${typeBadge}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #1e293b;">
                        <i class="ph ${destIcon}" style="color: var(--out-primary);"></i>
                        <span>${escapeHtml(ord.destination_location || 'Main Kitchen')}</span>
                    </div>
                    ${ord.vendor_name ? `<div style="font-size: 11px; color: #64748b;">${escapeHtml(ord.vendor_name)}</div>` : ''}
                </td>
                <td>
                    <strong>${escapeHtml(ord.requested_by || 'Chef')}</strong>
                    <div style="font-size: 11px; color: #64748b;">${escapeHtml(ord.department || 'Kitchen Operations')}</div>
                </td>
                <td>
                    <div><strong>${ord.total_items_count || 0}</strong> lines</div>
                    <div style="font-size: 11px; color: #64748b;">Req: ${reqQty.toFixed(2)}</div>
                </td>
                <td>
                    <div class="line-progress-container">
                        <div class="line-progress-track">
                            <div class="line-progress-fill ${pct >= 100 ? 'fill-matched' : (pct > 0 ? 'fill-partial' : 'fill-gray')}" style="width: ${pct}%;"></div>
                        </div>
                        <div class="line-progress-meta">
                            <span style="color: #64748b;">${packQty.toFixed(1)} / ${reqQty.toFixed(1)}</span>
                            <span style="color: ${pct >= 100 ? '#059669' : 'var(--out-primary)'};">${pct}%</span>
                        </div>
                    </div>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${parseFloat(ord.total_cost_value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">${statusBadge}</td>
                <td style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        ${ord.status !== 'SHIPPED' && ord.status !== 'CANCELLED' ? `
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="jumpToPickPack(${ord.id})" title="Pick & Pack this order">
                                <i class="ph ph-package"></i> Pick
                            </button>
                        ` : ''}
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openOrderDetailModal(${ord.id})" title="View Details">
                            <i class="ph ph-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function filterOrdersList() {
    renderOrdersList();
}

function updateKpis() {
    const shipped = rawOrders.filter(o => o.status === 'SHIPPED');
    const drafts = rawOrders.filter(o => o.status === 'DRAFT');
    const picking = rawOrders.filter(o => o.status === 'PICKING' || o.status === 'PACKED');
    const val = shipped.reduce((acc, o) => acc + (parseFloat(o.total_cost_value) || 0), 0);

    const elShipped = document.getElementById('kpiShippedCount');
    if (elShipped) elShipped.textContent = shipped.length;

    const elDraft = document.getElementById('kpiDraftCount');
    if (elDraft) elDraft.textContent = drafts.length;

    const elPicking = document.getElementById('kpiPickingCount');
    if (elPicking) elPicking.textContent = picking.length;

    const elVal = document.getElementById('kpiValuation');
    if (elVal) elVal.textContent = '₱' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const badgePending = document.getElementById('badgePendingPickCount');
    if (badgePending) badgePending.textContent = (drafts.length + picking.length) + ' Active';

    const badgeList = document.getElementById('badgeListCount');
    if (badgeList) badgeList.textContent = rawOrders.length;
}

/* --------------------------------------------------------------------------
   TAB 2: CREATION OF DRAFT (Asymmetric Builder Layout)
   -------------------------------------------------------------------------- */
function handleOrderTypeChange(val) {
    const vBlock = document.getElementById('vendorReturnBlock');
    const bBlock = document.getElementById('branchTransferBlock');
    const kBlock = document.getElementById('kitchenLineBlock');
    const dBlock = document.getElementById('disposalBlock');
    const pBadge = document.getElementById('partnerTypeBadge');

    vBlock.style.display = 'none';
    bBlock.style.display = 'none';
    kBlock.style.display = 'none';
    dBlock.style.display = 'none';

    if (val === 'VENDOR_RETURN') {
        vBlock.style.display = 'flex';
        pBadge.textContent = 'Vendor Masterlist';
        pBadge.className = 'hr-badge hr-badge-purple';
    } else if (val === 'BRANCH_TRANSFER') {
        bBlock.style.display = 'flex';
        pBadge.textContent = 'Branch Destination';
        pBadge.className = 'hr-badge hr-badge-blue';
    } else if (val === 'SPOILAGE_DISPOSAL') {
        dBlock.style.display = 'flex';
        pBadge.textContent = 'Waste & Disposal Vault';
        pBadge.className = 'hr-badge hr-badge-amber';
    } else {
        kBlock.style.display = 'flex';
        pBadge.textContent = 'Internal Station';
        pBadge.className = 'hr-badge hr-badge-neutral';
    }
}

function handleVendorSelect(vendorId) {
    const pill = document.getElementById('vendorPillBox');
    if (!vendorId) {
        pill.style.display = 'none';
        return;
    }

    const sel = document.getElementById('draftVendorSelect');
    const opt = sel.options[sel.selectedIndex];
    if (opt) {
        document.getElementById('vPillName').textContent = opt.dataset.name || 'Vendor';
        document.getElementById('vPillTrade').textContent = opt.dataset.trade || 'Commercial Partner';
        document.getElementById('vPillContact').textContent = opt.dataset.contact || 'No Contact';
        document.getElementById('vPillPhone').textContent = opt.dataset.phone || '-';
        document.getElementById('vPillAddress').textContent = opt.dataset.address || 'Standard Warehouse Receiving Dock';
        pill.style.display = 'flex';
    }
}

function addPickedCatalogItem() {
    const picker = document.getElementById('draftCatalogPicker');
    const sku = picker.value;
    if (!sku) {
        Swal.fire({
            icon: 'warning',
            title: 'Please Select Product',
            text: 'Choose an item from the Item Master catalog before clicking Add Item.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const opt = picker.options[picker.selectedIndex];
    const qtyInput = document.getElementById('draftQuickQty');
    const qty = parseFloat(qtyInput.value) || 1.0;

    if (qty <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Quantity',
            text: 'Requested quantity must be greater than zero.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    // Check if item already exists in draft lines
    const existing = draftItems.find(it => it.sku === sku);
    if (existing) {
        existing.requested_qty += qty;
        existing.subtotal = existing.requested_qty * existing.unit_cost;
    } else {
        const itemObj = {
            sku: sku,
            inventory_item_id: parseInt(opt.dataset.id) || null,
            item_name: opt.dataset.name,
            category: opt.dataset.category || 'Raw Ingredients',
            uom: opt.dataset.uom || 'Unit',
            unit_cost: parseFloat(opt.dataset.cost) || 0.00,
            available_stock: parseFloat(opt.dataset.stock) || 0.00,
            storage_location: opt.dataset.location || 'Main Storage',
            requested_qty: qty,
            subtotal: qty * (parseFloat(opt.dataset.cost) || 0.00)
        };
        draftItems.push(itemObj);
    }

    // Reset picker
    picker.value = '';
    qtyInput.value = '1.00';

    renderDraftTable();
}

function renderDraftTable() {
    const tbody = document.getElementById('draftItemsTableBody');
    const prompt = document.getElementById('draftNoItemsPrompt');
    const countBadge = document.getElementById('draftItemCountBadge');

    countBadge.textContent = draftItems.length + ' Items Added';

    if (draftItems.length === 0) {
        tbody.innerHTML = '';
        prompt.style.display = 'block';
        document.getElementById('draftTotalUnitsDisplay').textContent = '0.00';
        document.getElementById('draftTotalValueDisplay').textContent = '₱0.00';
        return;
    }
    prompt.style.display = 'none';

    let html = '';
    let totalUnits = 0;
    let totalValue = 0;

    draftItems.forEach((it, idx) => {
        totalUnits += it.requested_qty;
        const lineTotal = it.requested_qty * it.unit_cost;
        totalValue += lineTotal;

        const isLow = it.requested_qty > it.available_stock;

        html += `
            <tr>
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(it.item_name)}</strong>
                    <div style="font-size: 11px; font-family: monospace; color: var(--out-primary);">${it.sku}</div>
                    <div style="font-size: 10.5px; color: #94a3b8;"><i class="ph ph-map-pin"></i> ${escapeHtml(it.storage_location)}</div>
                </td>
                <td>
                    <span class="hr-badge hr-badge-neutral">${it.uom}</span>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    <span style="font-weight: 700; color: ${isLow ? '#dc2626' : '#059669'};">
                        ${it.available_stock.toFixed(2)}
                    </span>
                    ${isLow ? `<div style="font-size: 10px; color: #dc2626; font-weight: 700;">Deficit: ${(it.available_stock - it.requested_qty).toFixed(2)}</div>` : ''}
                </td>
                <td style="text-align: center;">
                    <input type="number" class="out-search-input" style="width: 80px; text-align: right; padding: 4px 6px; font-weight: 700;"
                           value="${it.requested_qty.toFixed(2)}" min="0.01" step="0.01"
                           onchange="updateDraftItemQty(${idx}, this.value)">
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    ₱${it.unit_cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #dc2626; border-color: #fee2e2;" onclick="removeDraftItem(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('draftTotalUnitsDisplay').textContent = totalUnits.toFixed(2);
    document.getElementById('draftTotalValueDisplay').textContent = '₱' + totalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateDraftItemQty(idx, val) {
    const q = parseFloat(val) || 0;
    if (q <= 0) {
        removeDraftItem(idx);
        return;
    }
    draftItems[idx].requested_qty = q;
    draftItems[idx].subtotal = q * draftItems[idx].unit_cost;
    renderDraftTable();
}

function removeDraftItem(idx) {
    draftItems.splice(idx, 1);
    renderDraftTable();
}

function resetDraftForm() {
    draftItems = [];
    renderDraftTable();
    document.getElementById('draftNotes').value = '';
    document.getElementById('draftReferenceNo').value = '';
    document.getElementById('draftVendorSelect').value = '';
    document.getElementById('vendorPillBox').style.display = 'none';
}

async function saveDraftOrder(moveToPick) {
    if (draftItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Requisition',
            text: 'Please add at least one line item from Item Master before saving.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const orderType = document.getElementById('draftOrderType').value;
    let destLocation = 'Main Kitchen Line';
    let vendorId = null;
    let vendorName = null;

    if (orderType === 'VENDOR_RETURN') {
        const vSel = document.getElementById('draftVendorSelect');
        vendorId = vSel.value ? parseInt(vSel.value) : null;
        if (!vendorId) {
            Swal.fire({ icon: 'warning', title: 'Vendor Required', text: 'Please select a registered vendor for vendor returns.', confirmButtonColor: '#9333ea' });
            return;
        }
        const opt = vSel.options[vSel.selectedIndex];
        vendorName = opt.dataset.name || 'Vendor';
        destLocation = opt.dataset.address || ('Vendor RMA: ' + vendorName);
    } else if (orderType === 'BRANCH_TRANSFER') {
        destLocation = document.getElementById('draftBranchSelect').value;
    } else if (orderType === 'SPOILAGE_DISPOSAL') {
        destLocation = document.getElementById('draftDisposalVault').value;
    } else {
        destLocation = document.getElementById('draftKitchenStation').value;
    }

    const payload = {
        order_type: orderType,
        destination_type: orderType === 'VENDOR_RETURN' ? 'VENDOR' : (orderType === 'BRANCH_TRANSFER' ? 'BRANCH' : 'INTERNAL_KITCHEN'),
        destination_location: destLocation,
        vendor_id: vendorId,
        vendor_name: vendorName,
        requested_by: document.getElementById('draftRequestedBy').value || 'Executive Chef',
        department: 'Kitchen Operations',
        priority: document.getElementById('draftPriority').value || 'NORMAL',
        reference_no: document.getElementById('draftReferenceNo').value || null,
        required_at: document.getElementById('draftRequiredAt').value || null,
        notes: document.getElementById('draftNotes').value || null,
        items: draftItems.map(it => ({
            sku: it.sku,
            inventory_item_id: it.inventory_item_id,
            item_name: it.item_name,
            category: it.category,
            uom: it.uom,
            requested_qty: it.requested_qty,
            unit_cost: it.unit_cost,
            storage_location: it.storage_location
        }))
    };

    try {
        Swal.fire({
            title: 'Creating Requisition...',
            text: 'Saving draft and syncing with Item Master',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch(window.StockOutConfig?.routes?.createStockOutDraft || '/inventory/api/create-stock-out-draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to create requisition draft.');
        }

        const newOrder = result.data.order;
        rawOrders.unshift(newOrder);

        // Update pick selector
        updatePickOrderDropdown();

        resetDraftForm();
        renderOrdersList();

        if (moveToPick) {
            Swal.fire({
                icon: 'success',
                title: 'Requisition Created!',
                text: `${newOrder.order_number} is ready for Pick, Pack & Ship.`,
                confirmButtonColor: '#9333ea',
                timer: 1600
            });
            jumpToPickPack(newOrder.id);
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Draft Saved!',
                text: `Requisition ${newOrder.order_number} saved as Draft.`,
                confirmButtonColor: '#9333ea'
            });
            switchOutTab('tab-out-list');
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error Saving Requisition',
            text: err.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

/* --------------------------------------------------------------------------
   TAB 3: PICK PACK SHIP (Fulfillment Engine with Pack Qty & Line Progress Bar)
   -------------------------------------------------------------------------- */
function updatePickOrderDropdown() {
    const sel = document.getElementById('pickOrderSelect');
    if (!sel) return;
    const activeOrders = rawOrders.filter(o => ['DRAFT', 'PICKING', 'PACKED'].includes(o.status));
    let optHtml = '<option value="">-- Choose Active Dispatch Order --</option>';
    activeOrders.forEach(ord => {
        optHtml += `<option value="${ord.id}">${ord.order_number} [${ord.status}] - ${escapeHtml(ord.destination_location)} (${ord.total_items_count} items)</option>`;
    });
    sel.innerHTML = optHtml;
}

function jumpToPickPack(orderId) {
    updatePickOrderDropdown();
    document.getElementById('pickOrderSelect').value = orderId;
    loadOrderIntoPickPack(orderId);
    switchOutTab('tab-pick-pack-ship');
}

function loadOrderIntoPickPack(orderId) {
    if (!orderId) {
        activePickOrder = null;
        document.getElementById('pickActiveOrderNum').textContent = 'NO ORDER SELECTED';
        document.getElementById('pickActiveStatusBadge').textContent = 'Awaiting Selection';
        document.getElementById('pickActiveDestDisplay').textContent = '-';
        document.getElementById('pickActiveReqDisplay').textContent = '-';
        document.getElementById('pickActiveTypeDisplay').textContent = '-';
        document.getElementById('pickItemsTableBody').innerHTML = '';
        document.getElementById('pickNoItemsPrompt').style.display = 'block';
        document.getElementById('pickTotalValueDisplay').textContent = '₱0.00';
        updatePickOverallProgress();
        return;
    }

    const order = rawOrders.find(o => o.id == orderId);
    if (!order) return;

    activePickOrder = JSON.parse(JSON.stringify(order)); // clone

    document.getElementById('pickActiveOrderNum').textContent = activePickOrder.order_number;
    document.getElementById('pickActiveStatusBadge').textContent = activePickOrder.status;
    document.getElementById('pickActiveDestDisplay').textContent = activePickOrder.destination_location;
    document.getElementById('pickActiveReqDisplay').textContent = activePickOrder.requested_by + ' (' + (activePickOrder.department || 'Operations') + ')';
    document.getElementById('pickActiveTypeDisplay').textContent = formatOrderType(activePickOrder.order_type);

    if (activePickOrder.picker_name) document.getElementById('pickPickerName').value = activePickOrder.picker_name;
    if (activePickOrder.carrier_name) document.getElementById('pickCarrierName').value = activePickOrder.carrier_name;
    if (activePickOrder.tracking_waybill) document.getElementById('pickWaybill').value = activePickOrder.tracking_waybill;

    renderPickPackTable();
}

function renderPickPackTable() {
    const tbody = document.getElementById('pickItemsTableBody');
    const prompt = document.getElementById('pickNoItemsPrompt');
    const countBadge = document.getElementById('pickItemsCountBadge');

    if (!activePickOrder || !activePickOrder.items || activePickOrder.items.length === 0) {
        tbody.innerHTML = '';
        prompt.style.display = 'block';
        countBadge.textContent = '0 Items';
        updatePickOverallProgress();
        return;
    }
    prompt.style.display = 'none';
    countBadge.textContent = activePickOrder.items.length + ' Items';

    let html = '';
    let totalValuation = 0;

    activePickOrder.items.forEach((it, idx) => {
        const reqQty = parseFloat(it.requested_qty) || 0;
        const packedQty = parseFloat(it.packed_qty) || 0;
        const unitCost = parseFloat(it.unit_cost) || 0;
        const lineVal = packedQty * unitCost;
        totalValuation += lineVal;

        // Progress Calculation & Match Status
        const pct = reqQty > 0 ? (packedQty / reqQty) * 100 : 0;
        const displayPct = Math.round(pct);

        let progressClass = 'fill-gray';
        let statusBadge = '<span class="hr-badge hr-badge-neutral">Unpicked</span>';

        if (packedQty > 0 && packedQty < reqQty) {
            progressClass = 'fill-partial';
            statusBadge = `<span class="hr-badge hr-badge-blue">${displayPct}% (Partial)</span>`;
        } else if (packedQty > 0 && packedQty === reqQty) {
            progressClass = 'fill-matched';
            statusBadge = `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Matched</span>`;
        } else if (packedQty > reqQty) {
            progressClass = 'fill-exceeds';
            const excess = (packedQty - reqQty).toFixed(2);
            statusBadge = `<span class="hr-badge hr-badge-amber"><i class="ph ph-warning"></i> Exceeds (+${excess})</span>`;
        }

        const barWidth = Math.min(100, displayPct);

        html += `
            <tr id="pickRow_${idx}">
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(it.item_name)}</strong>
                    <div style="font-size: 11px; font-family: monospace; color: var(--out-primary);">${it.sku}</div>
                    <div style="font-size: 10.5px; color: #94a3b8;"><i class="ph ph-archive"></i> ${escapeHtml(it.storage_location || 'Main Storage')}</div>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    <span style="font-weight: 700; color: #059669;">
                        ${(parseFloat(it.available_stock) || 0).toFixed(2)}
                    </span>
                    <div style="font-size: 10.5px; color: #64748b;">${it.uom}</div>
                </td>
                <td style="text-align: center; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ${reqQty.toFixed(2)}
                    <span style="font-size: 10.5px; color: #64748b;">${it.uom}</span>
                </td>
                <td style="text-align: center;">
                    <div class="pack-qty-input-group" style="justify-content: center;">
                        <button type="button" class="pack-quick-btn" onclick="stepPackQty(${idx}, -1)" title="Decrement 1">-</button>
                        <input type="number" class="pack-qty-input" id="packInput_${idx}"
                               value="${packedQty.toFixed(2)}" min="0" step="0.01"
                               oninput="handlePackQtyInput(${idx}, this.value)">
                        <button type="button" class="pack-quick-btn" onclick="stepPackQty(${idx}, 1)" title="Increment 1">+</button>
                    </div>
                </td>
                <td>
                    <div class="line-progress-container">
                        <div class="line-progress-track">
                            <div class="line-progress-fill ${progressClass}" id="lineBar_${idx}" style="width: ${barWidth}%;"></div>
                        </div>
                        <div class="line-progress-meta">
                            <span id="lineMetaUnits_${idx}" style="color: #64748b;">${packedQty.toFixed(2)} / ${reqQty.toFixed(2)}</span>
                            <div id="lineMetaBadge_${idx}">${statusBadge}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="text" class="out-search-input" style="padding: 4px 8px; font-size: 11.5px; width: 100px;"
                           placeholder="LOT-2026..." value="${it.batch_lot_no || ''}"
                           onchange="activePickOrder.items[${idx}].batch_lot_no = this.value">
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;" id="lineVal_${idx}">
                    ₱${lineVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="matchSingleItem(${idx})" title="Match Requested Qty">
                        <i class="ph ph-check"></i> 100%
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('pickTotalValueDisplay').textContent = '₱' + totalValuation.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    updatePickOverallProgress();
}

function handlePackQtyInput(idx, val) {
    if (!activePickOrder || !activePickOrder.items[idx]) return;
    const q = parseFloat(val) || 0;
    activePickOrder.items[idx].packed_qty = Math.max(0, q);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
}

function stepPackQty(idx, delta) {
    if (!activePickOrder || !activePickOrder.items[idx]) return;
    let curr = parseFloat(activePickOrder.items[idx].packed_qty) || 0;
    curr = Math.max(0, curr + delta);
    activePickOrder.items[idx].packed_qty = curr;
    const input = document.getElementById(`packInput_${idx}`);
    if (input) input.value = curr.toFixed(2);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
}

function matchSingleItem(idx) {
    if (!activePickOrder || !activePickOrder.items[idx]) return;
    const req = parseFloat(activePickOrder.items[idx].requested_qty) || 0;
    activePickOrder.items[idx].packed_qty = req;
    const input = document.getElementById(`packInput_${idx}`);
    if (input) input.value = req.toFixed(2);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
}

function updateSingleRowUI(idx) {
    const it = activePickOrder.items[idx];
    const reqQty = parseFloat(it.requested_qty) || 0;
    const packedQty = parseFloat(it.packed_qty) || 0;
    const unitCost = parseFloat(it.unit_cost) || 0;
    const lineVal = packedQty * unitCost;

    const pct = reqQty > 0 ? (packedQty / reqQty) * 100 : 0;
    const displayPct = Math.round(pct);
    const barWidth = Math.min(100, displayPct);

    let progressClass = 'fill-gray';
    let statusBadge = '<span class="hr-badge hr-badge-neutral">Unpicked</span>';

    if (packedQty > 0 && packedQty < reqQty) {
        progressClass = 'fill-partial';
        statusBadge = `<span class="hr-badge hr-badge-blue">${displayPct}% (Partial)</span>`;
    } else if (packedQty > 0 && packedQty === reqQty) {
        progressClass = 'fill-matched';
        statusBadge = `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Matched</span>`;
    } else if (packedQty > reqQty) {
        progressClass = 'fill-exceeds';
        const excess = (packedQty - reqQty).toFixed(2);
        statusBadge = `<span class="hr-badge hr-badge-amber"><i class="ph ph-warning"></i> Exceeds (+${excess})</span>`;
    }

    const bar = document.getElementById(`lineBar_${idx}`);
    if (bar) {
        bar.className = 'line-progress-fill ' + progressClass;
        bar.style.width = barWidth + '%';
    }

    const metaUnits = document.getElementById(`lineMetaUnits_${idx}`);
    if (metaUnits) metaUnits.textContent = `${packedQty.toFixed(2)} / ${reqQty.toFixed(2)}`;

    const metaBadge = document.getElementById(`lineMetaBadge_${idx}`);
    if (metaBadge) metaBadge.innerHTML = statusBadge;

    const valCell = document.getElementById(`lineVal_${idx}`);
    if (valCell) valCell.textContent = '₱' + lineVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updatePickOverallProgress() {
    if (!activePickOrder || !activePickOrder.items || activePickOrder.items.length === 0) {
        document.getElementById('pickOverallProgressBar').style.width = '0%';
        document.getElementById('pickGaugeBadge').textContent = '0% Packed';
        document.getElementById('pickTotalReqDisplay').textContent = '0.00';
        document.getElementById('pickTotalPackedDisplay').textContent = '0.00';
        return;
    }

    let totalReq = 0;
    let totalPacked = 0;
    let totalVal = 0;
    let hasExceeded = false;

    activePickOrder.items.forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        totalReq += r;
        totalPacked += p;
        totalVal += (p * (parseFloat(it.unit_cost) || 0));
        if (p > r) hasExceeded = true;
    });

    const pct = totalReq > 0 ? Math.min(100, Math.round((totalPacked / totalReq) * 100)) : 0;
    const bar = document.getElementById('pickOverallProgressBar');
    bar.style.width = pct + '%';
    if (pct >= 100) {
        bar.className = hasExceeded ? 'line-progress-fill fill-exceeds' : 'line-progress-fill fill-matched';
    } else {
        bar.className = 'line-progress-fill fill-partial';
    }

    document.getElementById('pickGaugeBadge').textContent = pct + '% Packed' + (hasExceeded ? ' (Exceeds)' : '');
    document.getElementById('pickTotalReqDisplay').textContent = totalReq.toFixed(2);
    document.getElementById('pickTotalPackedDisplay').textContent = totalPacked.toFixed(2);
    document.getElementById('pickTotalValueDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const alertBox = document.getElementById('pickFulfillmentAlert');
    if (pct >= 100 && !hasExceeded) {
        alertBox.innerHTML = '<i class="ph ph-check-circle" style="color: #059669;"></i> <strong>All Line Items 100% Matched!</strong> Ready for final dispatch authorization and stock deduction.';
        alertBox.style.background = '#ecfdf5';
        alertBox.style.borderColor = '#d1fae5';
        alertBox.style.color = '#059669';
    } else if (hasExceeded) {
        alertBox.innerHTML = '<i class="ph ph-warning" style="color: #d97706;"></i> <strong>Line Items Exceed Requested Quantities!</strong> Please verify if additional units were authorized by kitchen chef.';
        alertBox.style.background = '#fffbeb';
        alertBox.style.borderColor = '#fef3c7';
        alertBox.style.color = '#d97706';
    } else {
        alertBox.innerHTML = `<i class="ph ph-info"></i> Pack physical quantities to verify against requested amounts before confirming dispatch. (${totalPacked.toFixed(1)} / ${totalReq.toFixed(1)} packed)`;
        alertBox.style.background = '#faf5ff';
        alertBox.style.borderColor = '#f3e8ff';
        alertBox.style.color = 'var(--out-primary)';
    }
}

function autoMatchAllPackQuantities() {
    if (!activePickOrder || !activePickOrder.items) return;
    activePickOrder.items.forEach((it, idx) => {
        it.packed_qty = parseFloat(it.requested_qty) || 0;
        const input = document.getElementById(`packInput_${idx}`);
        if (input) input.value = it.packed_qty.toFixed(2);
        updateSingleRowUI(idx);
    });
    updatePickOverallProgress();
}

function clearAllPackQuantities() {
    if (!activePickOrder || !activePickOrder.items) return;
    activePickOrder.items.forEach((it, idx) => {
        it.packed_qty = 0;
        const input = document.getElementById(`packInput_${idx}`);
        if (input) input.value = '0.00';
        updateSingleRowUI(idx);
    });
    updatePickOverallProgress();
}

async function savePickPackProgress() {
    if (!activePickOrder) {
        Swal.fire({ icon: 'warning', title: 'No Order Selected', text: 'Select an order first before saving.', confirmButtonColor: '#9333ea' });
        return;
    }

    const payload = {
        order_id: activePickOrder.id,
        picker_name: document.getElementById('pickPickerName').value || null,
        carrier_name: document.getElementById('pickCarrierName').value || null,
        tracking_waybill: document.getElementById('pickWaybill').value || null,
        items: activePickOrder.items.map(it => ({
            sku: it.sku,
            packed_qty: parseFloat(it.packed_qty) || 0,
            batch_lot_no: it.batch_lot_no || null
        }))
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(window.StockOutConfig?.routes?.updateStockOutPack || '/inventory/api/update-stock-out-pack', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to save progress.');

        // Update in global array
        const idx = rawOrders.findIndex(o => o.id == activePickOrder.id);
        if (idx !== -1) rawOrders[idx] = data.data.order;

        renderOrdersList();
        updatePickOrderDropdown();
        document.getElementById('pickOrderSelect').value = activePickOrder.id;

        Swal.fire({
            icon: 'success',
            title: 'Progress Saved',
            text: `Pick & Pack progress recorded for ${activePickOrder.order_number}.`,
            timer: 1400,
            showConfirmButton: false
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error Saving Progress', text: err.message, confirmButtonColor: '#9333ea' });
    }
}

async function confirmShipOrder() {
    if (!activePickOrder) {
        Swal.fire({ icon: 'warning', title: 'No Order Selected', text: 'Select an active order first before dispatching.', confirmButtonColor: '#9333ea' });
        return;
    }

    const totalPacked = activePickOrder.items.reduce((acc, it) => acc + (parseFloat(it.packed_qty) || 0), 0);
    if (totalPacked <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Zero Packed Items',
            text: 'Cannot ship an order with 0 packed items. Please enter packed quantities.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const confirmRes = await Swal.fire({
        title: 'Confirm Dispatch & Deduct Stock?',
        html: `
            <div style="font-size: 13px; text-align: left; background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 8px;">
                <div><strong>Order #:</strong> ${activePickOrder.order_number}</div>
                <div><strong>Destination:</strong> ${escapeHtml(activePickOrder.destination_location)}</div>
                <div><strong>Total Packed Units:</strong> ${totalPacked.toFixed(2)}</div>
                <div style="margin-top: 8px; color: #dc2626; font-size: 12px;">
                    <i class="ph ph-warning"></i> This action will atomically deduct on-hand quantities from Item Master and post an immutable <strong>STOCK_OUT</strong> transaction to the Master Stock Ledger.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '✓ Yes, Dispatch & Deduct Stock',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    });

    if (!confirmRes.isConfirmed) return;

    const payload = {
        order_id: activePickOrder.id,
        picker_name: document.getElementById('pickPickerName').value || 'Warehouse Lead',
        carrier_name: document.getElementById('pickCarrierName').value || null,
        tracking_waybill: document.getElementById('pickWaybill').value || null,
        items: activePickOrder.items.map(it => ({
            sku: it.sku,
            packed_qty: parseFloat(it.packed_qty) || 0,
            batch_lot_no: it.batch_lot_no || null
        }))
    };

    try {
        Swal.fire({
            title: 'Processing Dispatch...',
            text: 'Deducting inventory balances and creating ledger records',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(window.StockOutConfig?.routes?.confirmShipStockOut || '/inventory/api/confirm-ship-stock-out', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch order.');

        const shippedOrder = data.data.order;

        // Replace in rawOrders
        const idx = rawOrders.findIndex(o => o.id == shippedOrder.id);
        if (idx !== -1) rawOrders[idx] = shippedOrder;

        // Refresh live data from backend to ensure Item Master & Stocks Overview balances sync
        await fetchLiveStockOutData();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            html: `
                <p>Order <strong>${shippedOrder.order_number}</strong> has been marked as SHIPPED.</p>
                <p style="font-size: 12px; color: #64748b;">Physical stock has been decremented and logged in Master Stock Ledger.</p>
                <div style="margin-top: 14px; display: flex; justify-content: center; gap: 10px;">
                    <a href="${window.StockOutConfig?.routes?.stocksOverview || '/inventory/stocks-overview'}" class="hr-btn hr-btn-secondary hr-btn-sm">
                        <i class="ph ph-squares-four"></i> View in Stocks Overview
                    </a>
                </div>
            `,
            confirmButtonColor: '#9333ea'
        });

        // Clear active order and switch to list
        loadOrderIntoPickPack(null);
        updatePickOrderDropdown();
        switchOutTab('tab-out-list');

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Dispatch Error', text: err.message, confirmButtonColor: '#9333ea' });
    }
}

/* --------------------------------------------------------------------------
   BACKEND SYNCHRONIZATION
   -------------------------------------------------------------------------- */
async function fetchLiveStockOutData() {
    try {
        const res = await fetch(window.StockOutConfig?.routes?.stockOutData || '/inventory/api/stock-out-data');
        const json = await res.json();
        if (json.success && json.data) {
            rawOrders = json.data.orders || [];
            rawProducts = json.data.products || [];
            rawVendors = json.data.vendors || [];
            rawCategories = json.data.categories || [];
            renderOrdersList();
            updatePickOrderDropdown();
        }
    } catch (e) {
        console.error('Failed to sync live stock-out data:', e);
    }
}

/* --------------------------------------------------------------------------
   DETAIL & PRINTING MODAL
   -------------------------------------------------------------------------- */
function openOrderDetailModal(orderId) {
    const order = rawOrders.find(o => o.id == orderId);
    if (!order) return;

    document.getElementById('modalOrderNum').textContent = order.order_number + ' [' + order.status + ']';
    document.getElementById('modalOrderDate').textContent = 'Created: ' + formatDate(order.created_at) + (order.dispatched_at ? ' | Dispatched: ' + formatDate(order.dispatched_at) : '');
    document.getElementById('modalDest').textContent = order.destination_location;
    document.getElementById('modalType').textContent = formatOrderType(order.order_type);
    document.getElementById('modalReq').textContent = order.requested_by + ' (' + (order.department || 'Kitchen') + ')';
    document.getElementById('modalPicker').textContent = order.picker_name || 'Pending assignment';
    document.getElementById('modalCarrier').textContent = order.carrier_name || 'Standard In-House Logistics';
    document.getElementById('modalWaybill').textContent = order.tracking_waybill || 'N/A';

    const tbody = document.getElementById('modalItemsTableBody');
    let html = '';
    let totalVal = 0;

    (order.items || []).forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        const lineT = p > 0 ? (p * cost) : (r * cost);
        totalVal += lineT;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--out-primary);">${it.sku}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td><span class="hr-badge hr-badge-neutral">${escapeHtml(it.category || 'Raw')}</span></td>
                <td>${it.uom}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">${r.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: ${p >= r ? '#059669' : '#0f172a'};">${p.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${cost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${lineT.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('modalTotalValDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    document.getElementById('orderDetailModal').classList.add('open');
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal').classList.remove('open');
}

function printPackingSlip() {
    window.print();
}

/* --------------------------------------------------------------------------
   KEYBOARD SHORTCUTS & HELPERS
   -------------------------------------------------------------------------- */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Press '/' to search
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            const s = document.getElementById('orderSearchInput');
            if (s) s.focus();
        }

        // Press 'F10' for Primary Action
        if (e.key === 'F10') {
            e.preventDefault();
            const activeTab = document.querySelector('.out-tab-panel.active');
            if (activeTab && activeTab.id === 'tab-create-draft') {
                saveDraftOrder(true);
            } else if (activeTab && activeTab.id === 'tab-pick-pack-ship') {
                confirmShipOrder();
            }
        }
    });
}

function formatOrderType(type) {
    if (!type) return 'Kitchen Usage';
    switch (type) {
        case 'KITCHEN_USAGE': return 'Kitchen Line Usage';
        case 'BRANCH_TRANSFER': return 'Inter-Branch Transfer';
        case 'VENDOR_RETURN': return 'Vendor Return (RMA)';
        case 'SPOILAGE_DISPOSAL': return 'Spoilage / Disposal';
        case 'STAFF_MEALS': return 'Staff Meals';
        case 'SAMPLE_TASTING': return 'Sample Tasting';
        default: return type.replace(/_/g, ' ');
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch(e) {
        return dateStr;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  switchOutTab,
  renderOrdersList,
  filterOrdersList,
  updateKpis,
  handleOrderTypeChange,
  handleVendorSelect,
  addPickedCatalogItem,
  renderDraftTable,
  updateDraftItemQty,
  removeDraftItem,
  resetDraftForm,
  saveDraftOrder,
  updatePickOrderDropdown,
  jumpToPickPack,
  loadOrderIntoPickPack,
  renderPickPackTable,
  handlePackQtyInput,
  stepPackQty,
  matchSingleItem,
  updateSingleRowUI,
  updatePickOverallProgress,
  autoMatchAllPackQuantities,
  clearAllPackQuantities,
  savePickPackProgress,
  confirmShipOrder,
  fetchLiveStockOutData,
  openOrderDetailModal,
  closeOrderDetailModal,
  printPackingSlip,
  setupKeyboardShortcuts,
  formatOrderType,
  formatDate,
  escapeHtml
});
