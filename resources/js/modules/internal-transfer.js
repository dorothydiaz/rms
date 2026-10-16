/* ==========================================================================
   INTERNAL TRANSFER JAVASCRIPT ENGINE
   Connected to Stocks Overview and Item Master with strict quantity validation
   ========================================================================== */

    let rawTransfers = window.InternalTransferConfig?.data?.initialTransfers || [];
    let rawProducts = window.InternalTransferConfig?.data?.initialProducts || [];
    let rawBranches = window.InternalTransferConfig?.data?.initialBranches || [];
    let rawCategories = window.InternalTransferConfig?.data?.initialCategories || [];

// Custom Direct Transfer State
let customItems = [];
let activeModalTransferId = null;

document.addEventListener('DOMContentLoaded', function() {
    renderTransfersList();
    renderPendingRequestsList();
    renderCustomTransferTable();
});

/* --------------------------------------------------------------------------
   TAB NAVIGATION
   -------------------------------------------------------------------------- */
function switchTrfTab(tabId) {
    document.querySelectorAll('.trf-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) targetPanel.classList.add('active');

    if (tabId === 'tab-transfer-list') document.getElementById('tabBtnList').classList.add('active');
    if (tabId === 'tab-pending-requests') document.getElementById('tabBtnPendingRequests').classList.add('active');
    if (tabId === 'tab-custom-transfer') document.getElementById('tabBtnCustomTransfer').classList.add('active');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* --------------------------------------------------------------------------
   TAB 1: TRANSFER REGISTER & MASTERLIST (List)
   -------------------------------------------------------------------------- */
function renderTransfersList() {
    const tbody = document.getElementById('transfersTableBody');
    const searchVal = (document.getElementById('transferSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('transferStatusFilter')?.value || 'ALL';
    const typeVal = document.getElementById('transferTypeFilter')?.value || 'ALL';
    const branchVal = document.getElementById('transferBranchFilter')?.value || 'ALL';

    const filtered = rawTransfers.filter(trf => {
        if (statusVal !== 'ALL' && trf.status !== statusVal) return false;
        if (typeVal !== 'ALL' && trf.transfer_type !== typeVal) return false;
        if (branchVal !== 'ALL' && (trf.destination_location || '') !== branchVal) return false;
        if (searchVal) {
            const num = (trf.transfer_number || '').toLowerCase();
            const dest = (trf.destination_location || '').toLowerCase();
            const car = (trf.carrier_name || '').toLowerCase();
            const hasSku = (trf.items || []).some(it => (it.sku || '').toLowerCase().includes(searchVal) || (it.item_name || '').toLowerCase().includes(searchVal));
            if (!num.includes(searchVal) && !dest.includes(searchVal) && !car.includes(searchVal) && !hasSku) {
                return false;
            }
        }
        return true;
    });

    updateKpis();

    const noPrompt = document.getElementById('noTransfersPrompt');
    if (filtered.length === 0) {
        tbody.innerHTML = '';
        noPrompt.style.display = 'block';
        return;
    }
    noPrompt.style.display = 'none';

    let html = '';
    filtered.forEach(trf => {
        const reqQty = parseFloat(trf.total_requested_qty) || 0;
        const sentQty = parseFloat(trf.total_transferred_qty) || 0;

        let statusBadge = '<span class="hr-badge hr-badge-amber">Pending</span>';
        if (trf.status === 'IN_TRANSIT') statusBadge = '<span class="hr-badge hr-badge-blue"><i class="ph ph-truck"></i> In-Transit</span>';
        if (trf.status === 'COMPLETED') statusBadge = '<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Completed</span>';
        if (trf.status === 'CANCELLED') statusBadge = '<span class="hr-badge hr-badge-red">Cancelled</span>';

        let typeBadge = trf.transfer_type === 'CUSTOM_PUSH'
            ? '<span class="hr-badge hr-badge-purple">Direct Push</span>'
            : '<span class="hr-badge hr-badge-neutral">Branch Requisition</span>';

        html += `
            <tr>
                <td>
                    <strong style="color: var(--trf-primary); cursor: pointer;" onclick="openTransferModal(${trf.id})">
                        ${trf.transfer_number}
                    </strong>
                    <div style="font-size: 11px; color: #94a3b8;">${formatDate(trf.created_at)}</div>
                </td>
                <td>${typeBadge}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #1e293b;">
                        <i class="ph ph-storefront" style="color: #64748b;"></i>
                        <span>${escapeHtml(trf.source_location)}</span>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; color: #0f172a;">
                        <i class="ph ph-map-pin" style="color: var(--trf-primary);"></i>
                        <span>${escapeHtml(trf.destination_location)}</span>
                    </div>
                </td>
                <td>
                    <div><strong>${trf.total_items_count || 0}</strong> items</div>
                    <div style="font-size: 11px; color: #64748b;">Req: ${reqQty.toFixed(2)}</div>
                </td>
                <td>
                    <strong>${sentQty.toFixed(2)}</strong> units
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${parseFloat(trf.total_valuation || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">${statusBadge}</td>
                <td style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openTransferModal(${trf.id})" title="View Details">
                            <i class="ph ph-eye"></i> View
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function filterTransfersList() {
    renderTransfersList();
}

function updateKpis() {
    const total = rawTransfers.length;
    const pending = rawTransfers.filter(t => t.status === 'PENDING').length;
    const inTransit = rawTransfers.filter(t => t.status === 'IN_TRANSIT').length;
    const val = rawTransfers.filter(t => ['IN_TRANSIT', 'COMPLETED'].includes(t.status))
                            .reduce((acc, t) => acc + (parseFloat(t.total_valuation) || 0), 0);

    const elTotal = document.getElementById('kpiTotalTransfers');
    if (elTotal) elTotal.textContent = total;

    const elPending = document.getElementById('kpiPendingCount');
    if (elPending) elPending.textContent = pending;

    const elTransit = document.getElementById('kpiInTransitCount');
    if (elTransit) elTransit.textContent = inTransit;

    const elVal = document.getElementById('kpiValuation');
    if (elVal) elVal.textContent = '₱' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const badgePending = document.getElementById('badgePendingCount');
    if (badgePending) badgePending.textContent = pending + ' Backlog';

    const badgeList = document.getElementById('badgeListCount');
    if (badgeList) badgeList.textContent = total;
}

/* --------------------------------------------------------------------------
   TAB 2: PENDING BRANCH REQUESTS & BACKLOG
   -------------------------------------------------------------------------- */
function renderPendingRequestsList() {
    const container = document.getElementById('pendingRequestsContainer');
    const noPrompt = document.getElementById('noPendingPrompt');

    const pendingTransfers = rawTransfers.filter(t => t.status === 'PENDING');
    if (pendingTransfers.length === 0) {
        container.innerHTML = '';
        noPrompt.style.display = 'block';
        return;
    }
    noPrompt.style.display = 'none';

    let html = '';
    pendingTransfers.forEach(trf => {
        let itemsRows = '';
        let hasShortage = false;

        (trf.items || []).forEach(it => {
            const p = rawProducts.find(prod => prod.sku === it.sku);
            const stockOnHand = p ? parseFloat(p.current_stock) : 0;
            const req = parseFloat(it.requested_qty) || 0;
            const isShort = req > stockOnHand;
            if (isShort) hasShortage = true;

            itemsRows += `
                <tr>
                    <td><strong>${escapeHtml(it.item_name)}</strong> <span style="font-family: monospace; color: var(--trf-primary);">(${it.sku})</span></td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums;">${req.toFixed(2)} ${it.uom}</td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: ${isShort ? '#dc2626' : '#059669'};">
                        ${stockOnHand.toFixed(2)} ${it.uom}
                    </td>
                    <td style="text-align: center;">
                        ${isShort
                            ? `<span class="hr-badge hr-badge-red"><i class="ph ph-warning"></i> Deficit: -${(req - stockOnHand).toFixed(2)}</span>`
                            : `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> In Stock</span>`
                        }
                    </td>
                </tr>
            `;
        });

        html += `
            <div class="panel-card" style="border-left: 4px solid #f59e0b;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span class="hr-badge hr-badge-amber">PENDING REQUISITION</span>
                        <strong style="font-size: 1.1rem; color: #0f172a; margin-left: 8px;">${trf.transfer_number}</strong>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">(Ref: ${escapeHtml(trf.requisition_reference || 'N/A')})</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="dispatchPendingRequestPrompt(${trf.id})" ${hasShortage ? 'title="Warning: Item shortage detected"' : ''}>
                            <i class="ph ph-truck"></i> Fulfill & Dispatch
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 12px; background: #f8fafc; padding: 10px 14px; border-radius: 8px;">
                    <div><strong>Requesting Branch:</strong> ${escapeHtml(trf.destination_location)}</div>
                    <div><strong>Requisitioner:</strong> ${escapeHtml(trf.requested_by || 'Branch Store Manager')}</div>
                    <div><strong>Date Requested:</strong> ${formatDate(trf.created_at)}</div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="trf-table" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Item & SKU</th>
                                <th style="text-align: right;">Requested Qty</th>
                                <th style="text-align: right;">Commissary Stock on Hand</th>
                                <th style="text-align: center;">Stock Availability</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsRows}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

async function dispatchPendingRequestPrompt(transferId) {
    const trf = rawTransfers.find(t => t.id == transferId);
    if (!trf) return;

    // Check inventory availability
    for (let it of (trf.items || [])) {
        const prod = rawProducts.find(p => p.sku === it.sku);
        const onHand = prod ? parseFloat(prod.current_stock) : 0;
        const req = parseFloat(it.requested_qty) || 0;
        if (req > onHand) {
            const confirmShort = await Swal.fire({
                title: 'Insufficient Inventory at Commissary!',
                html: `Item <strong>${it.item_name}</strong> requested <strong>${req}</strong>, but current central stock on hand is only <strong>${onHand}</strong>.<br><br>Adjust transfer quantity or replenish commissary before dispatching.`,
                icon: 'warning',
                confirmButtonColor: '#9333ea'
            });
            return;
        }
    }

    const { value: formValues } = await Swal.fire({
        title: `Dispatch Transfer ${trf.transfer_number}`,
        html: `
            <div style="text-align: left; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <label><strong>Carrier / Delivery Vehicle:</strong></label>
                    <input id="swalCarrier" class="trf-search-input" value="In-House Logistics Van 1">
                </div>
                <div>
                    <label><strong>Driver Plate #:</strong></label>
                    <input id="swalPlate" class="trf-search-input" placeholder="e.g. NDF 9012">
                </div>
                <div>
                    <label><strong>Waybill / Tracking #:</strong></label>
                    <input id="swalWaybill" class="trf-search-input" value="WB-TRF-${trf.transfer_number.replace(/\D/g,'')}">
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: '✓ Confirm Dispatch & Deduct Stock',
        confirmButtonColor: '#059669',
        preConfirm: () => {
            return {
                carrier: document.getElementById('swalCarrier').value,
                plate: document.getElementById('swalPlate').value,
                waybill: document.getElementById('swalWaybill').value,
            }
        }
    });

    if (!formValues) return;

    const payload = {
        transfer_id: trf.id,
        dispatched_by: window.InternalTransferConfig?.user?.commissaryLead || 'Commissary Lead',
        carrier_name: formValues.carrier,
        driver_plate: formValues.plate,
        waybill_number: formValues.waybill,
        items: trf.items.map(it => ({
            sku: it.sku,
            transferred_qty: parseFloat(it.requested_qty) || 0
        }))
    };

    try {
        Swal.fire({ title: 'Dispatching...', text: 'Deducting commissary stock and logging ledger entries...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(window.InternalTransferConfig?.routes?.dispatchTransfer || '/inventory/api/dispatch-transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch transfer.');

        await fetchLiveTransferData();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            text: `Transfer ${trf.transfer_number} is now in-transit to ${trf.destination_location}.`,
            confirmButtonColor: '#9333ea'
        });
        switchTrfTab('tab-transfer-list');
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Dispatch Error', text: e.message, confirmButtonColor: '#9333ea' });
    }
}

/* --------------------------------------------------------------------------
   TAB 3: CUSTOM TRANSFER (Direct Push Delivery)
   -------------------------------------------------------------------------- */
function addCustomTransferItem() {
    const picker = document.getElementById('customCatalogPicker');
    const sku = picker.value;
    if (!sku) {
        Swal.fire({ icon: 'warning', title: 'Select Product', text: 'Please pick an item from Item Master first.', confirmButtonColor: '#9333ea' });
        return;
    }

    const opt = picker.options[picker.selectedIndex];
    const qtyInput = document.getElementById('customQuickQty');
    const qty = parseFloat(qtyInput.value) || 1.0;
    const stockOnHand = parseFloat(opt.dataset.stock) || 0.0;

    if (qty <= 0) {
        Swal.fire({ icon: 'error', title: 'Invalid Quantity', text: 'Transfer quantity must be greater than zero.', confirmButtonColor: '#9333ea' });
        return;
    }

    // Check if adding exceeds current stock
    const existing = customItems.find(it => it.sku === sku);
    if (existing) {
        existing.transferred_qty += qty;
        existing.subtotal = existing.transferred_qty * existing.unit_cost;
    } else {
        customItems.push({
            sku: sku,
            inventory_item_id: parseInt(opt.dataset.id) || null,
            item_name: opt.dataset.name,
            category: opt.dataset.category || 'General',
            uom: opt.dataset.uom || 'Unit',
            unit_cost: parseFloat(opt.dataset.cost) || 0.0,
            available_stock: stockOnHand,
            storage_location: opt.dataset.location || 'Central Commissary',
            transferred_qty: qty,
            subtotal: qty * (parseFloat(opt.dataset.cost) || 0.0)
        });
    }

    picker.value = '';
    qtyInput.value = '1.00';
    renderCustomTransferTable();
}

function renderCustomTransferTable() {
    const tbody = document.getElementById('customItemsTableBody');
    const prompt = document.getElementById('customNoItemsPrompt');
    const countBadge = document.getElementById('customItemCountBadge');
    const overdraftBanner = document.getElementById('customOverdraftBanner');
    const btnDispatch = document.getElementById('btnDispatchCustomTransfer');

    countBadge.textContent = customItems.length + ' Items';

    if (customItems.length === 0) {
        tbody.innerHTML = '';
        prompt.style.display = 'block';
        overdraftBanner.style.display = 'none';
        btnDispatch.disabled = false;
        document.getElementById('customTotalUnitsDisplay').textContent = '0.00';
        document.getElementById('customTotalValDisplay').textContent = '₱0.00';
        return;
    }
    prompt.style.display = 'none';

    let html = '';
    let totalUnits = 0;
    let totalVal = 0;
    let hasOverdraft = false;

    customItems.forEach((it, idx) => {
        totalUnits += it.transferred_qty;
        const lineTotal = it.transferred_qty * it.unit_cost;
        totalVal += lineTotal;

        const isOverdraft = it.transferred_qty > it.available_stock;
        if (isOverdraft) hasOverdraft = true;

        html += `
            <tr style="${isOverdraft ? 'background: #fef2f2;' : ''}">
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(it.item_name)}</strong>
                    <div style="font-size: 11px; font-family: monospace; color: var(--trf-primary);">${it.sku}</div>
                    <div style="font-size: 10.5px; color: #94a3b8;"><i class="ph ph-archive"></i> ${escapeHtml(it.storage_location)}</div>
                </td>
                <td><span class="hr-badge hr-badge-neutral">${it.uom}</span></td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    <span style="font-weight: 700; color: ${isOverdraft ? '#dc2626' : '#059669'};">
                        ${it.available_stock.toFixed(2)}
                    </span>
                </td>
                <td style="text-align: center;">
                    <input type="number" class="trf-search-input" style="width: 80px; text-align: right; padding: 4px 6px; font-weight: 700; ${isOverdraft ? 'border-color: #dc2626; color: #dc2626;' : ''}"
                           value="${it.transferred_qty.toFixed(2)}" min="0.01" step="0.01"
                           onchange="updateCustomItemQty(${idx}, this.value)">
                    ${isOverdraft ? `<div style="font-size: 10px; color: #dc2626; font-weight: 700; margin-top: 2px;">Overdraft!</div>` : ''}
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    ₱${it.unit_cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #dc2626; border-color: #fee2e2;" onclick="removeCustomItem(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('customTotalUnitsDisplay').textContent = totalUnits.toFixed(2);
    document.getElementById('customTotalValDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (hasOverdraft) {
        overdraftBanner.style.display = 'flex';
        btnDispatch.disabled = true;
        btnDispatch.style.opacity = '0.5';
    } else {
        overdraftBanner.style.display = 'none';
        btnDispatch.disabled = false;
        btnDispatch.style.opacity = '1';
    }
}

function updateCustomItemQty(idx, val) {
    const q = parseFloat(val) || 0;
    if (q <= 0) {
        removeCustomItem(idx);
        return;
    }
    customItems[idx].transferred_qty = q;
    customItems[idx].subtotal = q * customItems[idx].unit_cost;
    renderCustomTransferTable();
}

function removeCustomItem(idx) {
    customItems.splice(idx, 1);
    renderCustomTransferTable();
}

function resetCustomTransferForm() {
    customItems = [];
    renderCustomTransferTable();
    document.getElementById('customNotes').value = '';
    document.getElementById('customWaybill').value = '';
    document.getElementById('customCarrierName').value = '';
    document.getElementById('customDriverPlate').value = '';
}

async function submitCustomTransfer() {
    if (customItems.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Empty Transfer', text: 'Please add items before dispatching.', confirmButtonColor: '#9333ea' });
        return;
    }

    // Check for any overdraft
    for (let it of customItems) {
        if (it.transferred_qty > it.available_stock) {
            Swal.fire({
                icon: 'error',
                title: 'Quantity Validation Error',
                text: `Transfer quantity for '${it.item_name}' (${it.transferred_qty}) exceeds available stock (${it.available_stock}). Please adjust.`,
                confirmButtonColor: '#9333ea'
            });
            return;
        }
    }

    const branchSel = document.getElementById('customDestBranch');
    const branchId = parseInt(branchSel.value);
    const branchName = branchSel.options[branchSel.selectedIndex].dataset.name || branchSel.options[branchSel.selectedIndex].text;

    const payload = {
        transfer_type: 'CUSTOM_PUSH',
        destination_branch_id: branchId,
        destination_location: branchName,
        source_location: document.getElementById('customSourceLoc').value,
        reason_code: document.getElementById('customReasonCode').value,
        dispatched_by: document.getElementById('customDispatchedBy').value || 'Commissary Lead',
        carrier_name: document.getElementById('customCarrierName').value || null,
        driver_plate: document.getElementById('customDriverPlate').value || null,
        waybill_number: document.getElementById('customWaybill').value || null,
        priority: 'NORMAL',
        notes: document.getElementById('customNotes').value || null,
        items: customItems.map(it => ({
            sku: it.sku,
            inventory_item_id: it.inventory_item_id,
            item_name: it.item_name,
            category: it.category,
            uom: it.uom,
            transferred_qty: it.transferred_qty,
            unit_cost: it.unit_cost,
            storage_location: it.storage_location
        }))
    };

    try {
        Swal.fire({ title: 'Dispatching Transfer...', text: 'Validating central stock and updating ledger...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(window.InternalTransferConfig?.routes?.createCustomTransfer || '/inventory/api/create-custom-transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch custom transfer.');

        await fetchLiveTransferData();
        resetCustomTransferForm();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            html: `
                <p>Custom Transfer <strong>${data.data.transfer.transfer_number}</strong> has been dispatched to <strong>${branchName}</strong>.</p>
                <p style="font-size: 12px; color: #64748b;">Central inventory has been decremented and logged to Master Stock Ledger.</p>
            `,
            confirmButtonColor: '#9333ea'
        });

        switchTrfTab('tab-transfer-list');
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Transfer Error', text: e.message, confirmButtonColor: '#9333ea' });
    }
}

/* --------------------------------------------------------------------------
   BACKEND SYNCHRONIZATION
   -------------------------------------------------------------------------- */
async function fetchLiveTransferData() {
    try {
        const res = await fetch(window.InternalTransferConfig?.routes?.internalTransferData || '/inventory/api/internal-transfer-data');
        const json = await res.json();
        if (json.success && json.data) {
            rawTransfers = json.data.transfers || [];
            rawProducts = json.data.products || [];
            rawBranches = json.data.branches || [];
            rawCategories = json.data.categories || [];
            renderTransfersList();
            renderPendingRequestsList();
        }
    } catch (e) {
        console.error('Failed to sync internal transfer data:', e);
    }
}

/* --------------------------------------------------------------------------
   MODAL & RECEIVING ACTIONS
   -------------------------------------------------------------------------- */
function openTransferModal(transferId) {
    const trf = rawTransfers.find(t => t.id == transferId);
    if (!trf) return;
    activeModalTransferId = trf.id;

    document.getElementById('modalTrfNum').textContent = trf.transfer_number + ' [' + trf.status + ']';
    document.getElementById('modalTrfDates').textContent = 'Created: ' + formatDate(trf.created_at) + (trf.dispatched_at ? ' | Dispatched: ' + formatDate(trf.dispatched_at) : '');
    document.getElementById('modalSource').textContent = trf.source_location;
    document.getElementById('modalDest').textContent = trf.destination_location;
    document.getElementById('modalType').textContent = trf.transfer_type === 'CUSTOM_PUSH' ? 'Direct Push Transfer' : 'Branch Requisition';
    document.getElementById('modalCarrier').textContent = trf.carrier_name || 'Standard Courier';
    document.getElementById('modalPlate').textContent = trf.driver_plate || '-';
    document.getElementById('modalWaybill').textContent = trf.waybill_number || '-';

    const tbody = document.getElementById('modalItemsTbody');
    let html = '';
    let totalVal = 0;

    (trf.items || []).forEach(it => {
        const req = parseFloat(it.requested_qty) || 0;
        const sent = parseFloat(it.transferred_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        const lineVal = sent > 0 ? (sent * cost) : (req * cost);
        totalVal += lineVal;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--trf-primary);">${it.sku}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td><span class="hr-badge hr-badge-neutral">${it.uom}</span></td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">${req.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #059669;">${sent.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${cost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${lineVal.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('modalValuationDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const receiveSec = document.getElementById('modalReceiveActionSection');
    if (trf.status === 'IN_TRANSIT') {
        receiveSec.style.display = 'block';
    } else {
        receiveSec.style.display = 'none';
    }

    document.getElementById('transferModal').classList.add('open');
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.remove('open');
    activeModalTransferId = null;
}

async function confirmReceiveModalTransfer() {
    if (!activeModalTransferId) return;

    try {
        Swal.fire({ title: 'Confirming Delivery...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch(window.InternalTransferConfig?.routes?.receiveTransfer || '/inventory/api/receive-transfer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                transfer_id: activeModalTransferId,
            received_by: window.InternalTransferConfig?.user?.receivingLead || 'Branch Receiving Lead',
            })
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to confirm receipt.');

        await fetchLiveTransferData();
        closeTransferModal();

        Swal.fire({
            icon: 'success',
            title: 'Transfer Completed!',
            text: 'Delivery confirmed and receipt logged.',
            confirmButtonColor: '#9333ea'
        });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Receipt Error', text: e.message, confirmButtonColor: '#9333ea' });
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
  switchTrfTab,
  renderTransfersList,
  filterTransfersList,
  updateKpis,
  renderPendingRequestsList,
  dispatchPendingRequestPrompt,
  addCustomTransferItem,
  renderCustomTransferTable,
  updateCustomItemQty,
  removeCustomItem,
  resetCustomTransferForm,
  submitCustomTransfer,
  fetchLiveTransferData,
  openTransferModal,
  closeTransferModal,
  confirmReceiveModalTransfer,
  formatDate,
  escapeHtml
});
