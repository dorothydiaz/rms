/**
 * ============================================================================
 * RMS STOCK IN & RECEIVING STATE ORCHESTRATOR
 * Features 3 Primary Tabs:
 * 1. List of the PO (Master PO list)
 * 2. Pending PO (Backlogs & Open queue)
 * 3. Create new PO to received (Bypass PO via RFQ Builder Layout)
 * ============================================================================
 */
window.StockInStore = {
    purchaseOrders: [],
    products: [],
    vendors: [],
    goodsReceipts: [],
    
    // Direct Bypass Inbound Form State (Tab 3)
    directForm: {
        refNumber: '',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        customStallName: '',
        reasonCode: 'Direct Spot Purchase',
        deliverySlip: '',
        carrier: '',
        receivedAt: '',
        deliveryLocation: 'Central Commissary - Receiving Dock 1',
        settlementMode: 'IMMEDIATE_COD',
        paymentMethod: 'Cash / Currency',
        paymentRef: '',
        freight: 0,
        customs: 0,
        handling: 0,
        items: []
    },

    // Active Inspection Drawer State (for receiving existing POs)
    activePoReceiving: null,
    activeTraceLineIndex: null
};

document.addEventListener('DOMContentLoaded', () => {
    initStockInModule();

    // Hotkey bindings
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            switchGrnTab('tab-create-po');
        } else if (e.key === 'F10') {
            const activeTab = document.querySelector('.tabs-wrapper .tab-btn.active')?.id;
            if (activeTab === 'tabBtnCreatePo') {
                e.preventDefault();
                submitDirectReceivingOrder();
            }
        } else if (e.key === 'Escape') {
            closeInspectionDrawer();
            closeSupervisorOverrideModal();
            closeTraceModal();
            closePoItemsModal();
        } else if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            const search = document.getElementById('poListSearchInput');
            if (search) search.focus();
        }
    });
});

let currentInspectedPoNumber = null;

function initStockInModule() {
    // 1. Prioritize Server-Hydrated Data from MySQL DB
    const serverPos = window.StockInConfig?.data?.initialPurchaseOrders || [];
    const serverProd = window.StockInConfig?.data?.initialProducts || [];
    const serverVend = window.StockInConfig?.data?.initialVendors || [];
    const serverGrn = window.StockInConfig?.data?.initialReceipts || [];

    if (serverPos && serverPos.length > 0) {
        window.StockInStore.purchaseOrders = serverPos.map(p => ({
            ...p,
            poNumber: p.po_number,
            poType: p.po_type,
            vendorId: p.vendor_id,
            vendorName: p.vendor_name,
            vendorTradeName: p.vendor_trade_name,
            orderDate: p.order_date,
            expectedDelivery: p.expected_delivery,
            deliveryLocation: p.delivery_location,
            paymentStatus: p.payment_status,
            paymentMethod: p.payment_method,
            grossTotal: parseFloat(p.gross_total) || 0,
            status: p.status,
            items: (p.items || []).map(it => ({
                ...it,
                name: it.item_name,
                unit: it.uom,
                quantity: parseFloat(it.quantity) || 0,
                unitPrice: parseFloat(it.unit_price) || 0,
                receivedQuantity: parseFloat(it.received_quantity) || 0,
            }))
        }));
    } else {
        const rawPos = localStorage.getItem('rms_purchase_orders');
        window.StockInStore.purchaseOrders = rawPos ? JSON.parse(rawPos) : getFallbackPurchaseOrders();
    }

    if (serverProd && serverProd.length > 0) {
        window.StockInStore.products = serverProd.map(p => ({
            ...p,
            unit: p.uom,
            stock: parseFloat(p.current_stock) || 0,
            costPrice: parseFloat(p.cost_price) || 0,
            sellingPrice: parseFloat(p.selling_price) || 0
        }));
    } else {
        const rawProd = localStorage.getItem('rms_inventory_products');
        window.StockInStore.products = rawProd ? JSON.parse(rawProd) : getFallbackProducts();
    }

    if (serverVend && serverVend.length > 0) {
        window.StockInStore.vendors = serverVend.map(v => ({
            id: v.vendor_code,
            code: v.vendor_code,
            legalName: v.legal_name,
            tradeName: v.trade_name,
            category: v.category,
            contactPerson: v.contact_person,
            email: v.email,
            phone: v.phone,
            address: v.address
        }));
    } else {
        const rawVend = localStorage.getItem('rms_vendors');
        window.StockInStore.vendors = rawVend ? JSON.parse(rawVend) : getFallbackVendors();
    }

    window.StockInStore.goodsReceipts = (serverGrn && serverGrn.length > 0) ? serverGrn : (JSON.parse(localStorage.getItem('rms_goods_receipts') || '[]'));

    // Cache to localStorage for offline resilience
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.StockInStore.purchaseOrders));
    localStorage.setItem('rms_inventory_products', JSON.stringify(window.StockInStore.products));
    localStorage.setItem('rms_vendors', JSON.stringify(window.StockInStore.vendors));
    localStorage.setItem('rms_goods_receipts', JSON.stringify(window.StockInStore.goodsReceipts));

    // Populate UI Dropdowns
    populateVendorDropdown();
    populateCatalogPicker();
    resetDirectReceivingForm();

    // Render Views
    renderPoMasterList();
    renderPendingPoList();
    updateHeaderKpis();

    // Check for deep-link from Purchase Orders module: ?po=PO-2026-0103
    const urlParams = new URLSearchParams(window.location.search);
    const poParam = urlParams.get('po');
    if (poParam) {
        switchGrnTab('tab-pending-po');
        openReceiveForPo(poParam);
    }
}

/**
 * ----------------------------------------------------------------------------
 * PO LINE ITEMS VIEWER & RECEIVING BUILDER DISPATCHER
 * ----------------------------------------------------------------------------
 */
function openPoItemsModal(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;

    currentInspectedPoNumber = poNumber;

    document.getElementById('poModalPoRef').textContent = po.poNumber;
    document.getElementById('poModalVendor').textContent = po.vendorTradeName || po.vendorName || 'Vendor';
    document.getElementById('poModalStatus').innerHTML = getOrderStatusBadge(po.status);
    document.getElementById('poModalOrderDate').textContent = po.orderDate || 'N/A';
    document.getElementById('poModalDeliveryDate').textContent = po.expectedDelivery || 'Immediate / Scheduled';
    document.getElementById('poModalLocation').textContent = po.deliveryLocation || 'Central Commissary';

    const items = po.items || [];
    const tbody = document.getElementById('poModalItemsTableBody');
    if (!tbody) return;

    let totalOrdered = 0;
    let totalReceived = 0;
    let totalOpen = 0;
    let totalValue = 0;

    tbody.innerHTML = items.map((it) => {
        const ord = parseFloat(it.quantity) || 0;
        const rec = parseFloat(it.receivedQuantity) || 0;
        const open = Math.max(0, ord - rec);
        const cost = parseFloat(it.unitPrice) || 0;
        const subtotal = ord * cost;

        totalOrdered += ord;
        totalReceived += rec;
        totalOpen += open;
        totalValue += subtotal;

        return `
            <tr>
                <td><strong class="grn-mono" style="color: var(--grn-primary);">${it.sku || 'N/A'}</strong></td>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${it.name || it.itemName}</div>
                    ${it.specs ? `<div style="font-size: 11px; color: var(--grn-text-muted);">${it.specs}</div>` : ''}
                </td>
                <td style="text-align: center; font-weight: 600;">${ord} ${it.unit || it.uom || 'Unit'}</td>
                <td style="text-align: center; font-weight: 600; color: #047857;">${rec} ${it.unit || it.uom || 'Unit'}</td>
                <td style="text-align: center; font-weight: 700; color: ${open > 0 ? '#b45309' : '#047857'};">
                    ${open > 0 ? `${open} ${it.unit || it.uom || 'Unit'}` : '<span class="hr-badge hr-badge-success" style="font-size: 10px;">Fulfilled</span>'}
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(cost)}</td>
                <td style="text-align: right; font-weight: 600;" class="grn-currency">₱${formatMoney(subtotal)}</td>
            </tr>
        `;
    }).join('');

    document.getElementById('poModalTotalItems').textContent = `${items.length} items`;
    document.getElementById('poModalTotalValue').textContent = `₱${formatMoney(totalValue)}`;
    document.getElementById('poModalTotalOpen').textContent = `${totalOpen} units`;

    const canReceive = ['Approved / Issued', 'Partially Received'].includes(po.status);
    const builderBtn = document.getElementById('poModalBuilderBtn');
    if (builderBtn) {
        builderBtn.style.display = canReceive ? 'inline-flex' : 'none';
    }

    document.getElementById('poItemsViewerModal')?.classList.add('active');
}

function closePoItemsModal() {
    document.getElementById('poItemsViewerModal')?.classList.remove('active');
}

function pushPoToReceivingBuilder(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) {
        showToast('Purchase Order not found.', 'error');
        return;
    }

    // 1. Close modal
    closePoItemsModal();

    // 2. Switch tab to Tab 3 (Create new PO to received)
    switchGrnTab('tab-create-po');

    // 3. Populate Direct Form State with PO Data
    window.StockInStore.directForm.poNumber = po.poNumber;
    window.StockInStore.directForm.vendorId = po.vendorId || '';
    window.StockInStore.directForm.vendorName = po.vendorName || '';
    window.StockInStore.directForm.vendorTradeName = po.vendorTradeName || po.vendorName || '';
    window.StockInStore.directForm.deliveryLocation = po.deliveryLocation || 'Central Commissary - Receiving Dock 1';
    window.StockInStore.directForm.settlementMode = po.paymentStatus || 'PURCHASE_ORDER';
    window.StockInStore.directForm.paymentMethod = po.paymentMethod || 'Trade Credit (Net 30/15)';
    window.StockInStore.directForm.reasonCode = `PO Intake: ${po.poNumber}`;

    // 4. Map PO Items to Builder Line Items (prefill with open/pending balance)
    const poItems = po.items || [];
    window.StockInStore.directForm.items = poItems.map(it => {
        const ord = parseFloat(it.quantity) || 0;
        const rec = parseFloat(it.receivedQuantity) || 0;
        const open = Math.max(0, ord - rec);

        return {
            sku: it.sku || '',
            name: it.name || it.itemName || 'Item',
            category: it.category || 'Raw Ingredients',
            unit: it.unit || it.uom || 'Unit',
            purchasingUnit: it.unit || it.uom || 'Unit',
            uomMultiplier: 1.0,
            orderedQty: ord,
            alreadyReceived: rec,
            receivedQty: open > 0 ? open : ord,
            unitCost: parseFloat(it.unitPrice) || 0,
            lotNumber: '',
            expiryDate: '',
            notes: `From PO ${po.poNumber}`
        };
    });

    // 5. Update UI Controls in Tab 3
    const refDisplay = document.getElementById('directRecRefDisplay');
    if (refDisplay) refDisplay.textContent = `GRN-${po.poNumber}`;

    const vendorSelect = document.getElementById('directVendorSelect');
    if (vendorSelect) {
        let exists = Array.from(vendorSelect.options).some(o => o.value === po.vendorId);
        if (!exists && po.vendorId) {
            const opt = document.createElement('option');
            opt.value = po.vendorId;
            opt.textContent = `${po.vendorTradeName || po.vendorName} (From PO)`;
            vendorSelect.appendChild(opt);
        }
        vendorSelect.value = po.vendorId || '';
    }

    const pill = document.getElementById('directVendorPillBox');
    const pillName = document.getElementById('directVendorPillName');
    if (pill && pillName) {
        pillName.textContent = po.vendorTradeName || po.vendorName;
        pill.style.display = 'block';
    }

    const locSelect = document.getElementById('directDeliveryLocation');
    if (locSelect) locSelect.value = po.deliveryLocation || 'Central Commissary - Receiving Dock 1';

    // Show top banner in Tab 3 indicating PO fulfillment mode
    let poBanner = document.getElementById('builderPoFulfillmentBanner');
    if (!poBanner) {
        const rfqGrid = document.querySelector('.rfq-builder-workspace-grid');
        if (rfqGrid) {
            poBanner = document.createElement('div');
            poBanner.id = 'builderPoFulfillmentBanner';
            poBanner.style.cssText = 'margin-bottom: 14px; padding: 12px 18px; border-radius: 12px; background: linear-gradient(135deg, rgba(147, 51, 234, 0.08), rgba(236, 72, 153, 0.08)); border: 1.5px solid rgba(168, 85, 247, 0.35); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;';
            rfqGrid.parentNode.insertBefore(poBanner, rfqGrid);
        }
    }
    if (poBanner) {
        poBanner.style.display = 'flex';
        poBanner.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--grn-purple-bg); color: var(--grn-primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ph ph-tray-arrow-down"></i>
                </div>
                <div>
                    <strong style="color: var(--grn-primary); font-size: 14px;">Fulfilling Active Purchase Order: ${po.poNumber}</strong>
                    <div style="font-size: 12px; color: var(--grn-text-muted);">
                        Vendor: <strong>${po.vendorTradeName || po.vendorName}</strong> &bull; Line items loaded with remaining open quantities.
                    </div>
                </div>
            </div>
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetToAdHocBuilder()">
                <i class="ph ph-arrow-counter-clockwise"></i> Clear / Reset to Ad-Hoc
            </button>
        `;
    }

    // Render items & totals
    renderDirectItemsTable();
    calculateDirectTotals();

    showToast(`✓ Order ${po.poNumber} loaded into Receiving Builder. Review quantities and submit intake!`, 'success');
}

function resetToAdHocBuilder() {
    const poBanner = document.getElementById('builderPoFulfillmentBanner');
    if (poBanner) poBanner.style.display = 'none';
    resetDirectReceivingForm();
    showToast('Reset builder to standard ad-hoc intake mode.', 'info');
}

async function refreshStockInDataFromServer() {
    try {
        const res = await fetch(window.StockInConfig?.routes?.stockInData || '/inventory/api/stock-in-data');
        const json = await res.json();
        if (json.success && json.data) {
            const serverPos = json.data.purchaseOrders || [];
            window.StockInStore.purchaseOrders = serverPos.map(p => ({
                ...p,
                poNumber: p.po_number,
                poType: p.po_type,
                vendorId: p.vendor_id,
                vendorName: p.vendor_name,
                vendorTradeName: p.vendor_trade_name,
                orderDate: p.order_date,
                expectedDelivery: p.expected_delivery,
                deliveryLocation: p.delivery_location,
                paymentStatus: p.payment_status,
                paymentMethod: p.payment_method,
                grossTotal: parseFloat(p.gross_total) || 0,
                status: p.status,
                items: (p.items || []).map(it => ({
                    ...it,
                    name: it.item_name,
                    unit: it.uom,
                    quantity: parseFloat(it.quantity) || 0,
                    unitPrice: parseFloat(it.unit_price) || 0,
                    receivedQuantity: parseFloat(it.received_quantity) || 0,
                }))
            }));
            window.StockInStore.vendors = (json.data.vendors || []).map(v => ({
                id: v.vendor_code,
                code: v.vendor_code,
                legalName: v.legal_name,
                tradeName: v.trade_name,
                category: v.category,
                contactPerson: v.contact_person,
                email: v.email,
                phone: v.phone,
                address: v.address
            }));
            window.StockInStore.products = (json.data.products || []).map(p => ({
                ...p,
                unit: p.uom,
                stock: parseFloat(p.current_stock) || 0,
                costPrice: parseFloat(p.cost_price) || 0,
                sellingPrice: parseFloat(p.selling_price) || 0
            }));
            window.StockInStore.goodsReceipts = json.data.goodsReceipts || [];

            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.StockInStore.purchaseOrders));
            localStorage.setItem('rms_vendors', JSON.stringify(window.StockInStore.vendors));
            localStorage.setItem('rms_inventory_products', JSON.stringify(window.StockInStore.products));
            localStorage.setItem('rms_goods_receipts', JSON.stringify(window.StockInStore.goodsReceipts));

            renderPoMasterList();
            renderPendingPoList();
            updateHeaderKpis();
            resetDirectReceivingForm();
        }
    } catch(e) {
        console.warn('Could not refresh from server, using local state:', e);
    }
}

/**
 * ----------------------------------------------------------------------------
 * PRIMARY TABS NAVIGATION SWITCHER
 * ----------------------------------------------------------------------------
 */
function switchGrnTab(tabId) {
    document.querySelectorAll('.grn-tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tabs-wrapper .tab-btn').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(tabId);
    if (panel) panel.style.display = 'block';

    if (tabId === 'tab-po-list') {
        document.getElementById('tabBtnPoList')?.classList.add('active');
        renderPoMasterList();
    } else if (tabId === 'tab-pending-po') {
        document.getElementById('tabBtnPendingPo')?.classList.add('active');
        renderPendingPoList();
    } else if (tabId === 'tab-create-po') {
        document.getElementById('tabBtnCreatePo')?.classList.add('active');
    }
}

/**
 * ----------------------------------------------------------------------------
 * KPI SUMMARY STATS
 * ----------------------------------------------------------------------------
 */
function updateHeaderKpis() {
    const pos = window.StockInStore.purchaseOrders || [];
    const totalPos = pos.length;
    const pendingPos = pos.filter(p => ['Approved / Issued', 'Partially Received'].includes(p.status)).length;
    const completedPos = pos.filter(p => p.status === 'Fully Received').length;

    const receipts = window.StockInStore.goodsReceipts || [];
    const totalReceivedVal = receipts.reduce((acc, r) => acc + (parseFloat(r.totalValuation) || 0), 0);

    const elTotal = document.getElementById('kpiTotalPos');
    if (elTotal) elTotal.textContent = totalPos;
    const elPending = document.getElementById('kpiPendingPos');
    if (elPending) elPending.textContent = pendingPos;
    const elComp = document.getElementById('kpiCompletedPos');
    if (elComp) elComp.textContent = completedPos;
    const elVal = document.getElementById('kpiTotalReceivedValue');
    if (elVal) elVal.textContent = `₱${formatMoney(totalReceivedVal)}`;

    // Tab badges
    const bTotal = document.getElementById('badgePoListCount');
    if (bTotal) bTotal.textContent = totalPos;
    const bPending = document.getElementById('badgePendingPoCount');
    if (bPending) bPending.textContent = pendingPos;
}

/**
 * ----------------------------------------------------------------------------
 * TAB 1: LIST OF THE PO (Master List connected to Purchase Module)
 * ----------------------------------------------------------------------------
 */
function renderPoMasterList() {
    const tbody = document.getElementById('poMasterTableBody');
    if (!tbody) return;

    const query = (document.getElementById('poListSearchInput')?.value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('poListStatusFilter')?.value || 'ALL';
    const typeFilter = document.getElementById('poListTypeFilter')?.value || 'ALL';

    const pos = (window.StockInStore.purchaseOrders || []).filter(p => {
        if (query) {
            const mPo = (p.poNumber || '').toLowerCase().includes(query);
            const mVnd = ((p.vendorTradeName || '') + ' ' + (p.vendorName || '')).toLowerCase().includes(query);
            if (!mPo && !mVnd) return false;
        }
        if (statusFilter !== 'ALL' && p.status !== statusFilter) return false;
        if (typeFilter !== 'ALL' && (p.poType || 'vendor') !== typeFilter) return false;
        return true;
    });

    if (pos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px; color: var(--grn-text-muted);">
                    <i class="ph ph-receipt" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                    No purchase orders found matching the filter criteria.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = pos.map(p => {
        const items = p.items || [];
        const gross = items.reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        const canReceive = ['Approved / Issued', 'Partially Received'].includes(p.status);

        return `
            <tr>
                <td><strong class="grn-mono" style="color: var(--grn-primary);">${p.poNumber}</strong></td>
                <td style="font-size: 11.5px; color: var(--grn-text-muted);">${p.orderDate || 'N/A'}</td>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${p.vendorTradeName || p.vendorName}</div>
                </td>
                <td><span class="hr-badge hr-badge-neutral">${p.poType === 'wet_market' ? 'Wet Market' : (p.poType === 'ad_hoc' ? 'Direct / Bypass' : 'Commercial')}</span></td>
                <td style="font-size: 11.5px; color: var(--grn-text-muted);">${p.deliveryLocation || 'Central Warehouse'}</td>
                <td style="text-align: center;">
                    <button type="button" class="hr-badge hr-badge-purple" style="cursor: pointer; border: 1px solid var(--grn-border); font-family: inherit; font-size: 11.5px; padding: 4px 8px; transition: all 0.2s;" onclick="openPoItemsModal('${p.poNumber}')" title="Click to view PO line items">
                        <i class="ph ph-list-bullets"></i> ${items.length} items
                    </button>
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(gross)}</td>
                <td style="text-align: center;">${getPaymentBadge(p.paymentStatus)}</td>
                <td style="text-align: center;">${getOrderStatusBadge(p.status)}</td>
                <td style="text-align: center;">
                    ${grnActionCell(p.poNumber, canReceive)}
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Standard ERP action cell: one primary button + dropdown (View / Edit / Print GRN).
 */
function grnEsc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function grnActionCell(poNumber, canReceive) {
    const po = grnEsc(poNumber);
    const primary = canReceive
        ? `<button type="button" class="grn-act-primary" data-act="receive" data-po="${po}" title="Open dock check-in drawer"><i class="ph ph-tray-arrow-down"></i> Receive Inbound</button>`
        : `<button type="button" class="grn-act-primary grn-act-neutral" data-act="view" data-po="${po}" title="Inspect items &amp; quantities"><i class="ph ph-eye"></i> View Items</button>`;
    return `<div class="grn-act-split">${primary}<button type="button" class="grn-act-caret" data-act="menu" data-po="${po}" aria-haspopup="true" aria-label="More actions"><i class="ph ph-caret-down"></i></button></div>`;
}

(function initGrnActionMenu() {
    let menu = null;
    function closeMenu() { if (menu) { menu.remove(); menu = null; } }

    function runAction(act, po) {
        closeMenu();
        if (act === 'receive') openReceiveForPo(po);
        else if (act === 'view') openPoItemsModal(po);
        else if (act === 'edit') editPurchaseOrder(po);
        else if (act === 'print') printGrnSlip(po);
    }

    function openMenu(btn, po) {
        closeMenu();
        menu = document.createElement('div');
        menu.className = 'grn-act-menu';
        menu.setAttribute('role', 'menu');
        menu.innerHTML = `
            <button type="button" role="menuitem" data-act="view" data-po="${po}"><i class="ph ph-eye"></i> View Items</button>
            <button type="button" role="menuitem" data-act="edit" data-po="${po}"><i class="ph ph-pencil-simple"></i> Edit Purchase Order</button>
            <button type="button" role="menuitem" data-act="print" data-po="${po}"><i class="ph ph-printer"></i> Print Receiving Slip / GRN</button>`;
        document.body.appendChild(menu);
        const r = btn.getBoundingClientRect();
        const mw = menu.offsetWidth, mh = menu.offsetHeight;
        let left = Math.min(Math.max(8, r.right - mw), window.innerWidth - mw - 8);
        let top = r.bottom + 4;
        if (top + mh > window.innerHeight - 8) top = Math.max(8, r.top - mh - 4);
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    // Event delegation: single document-level listener
    document.addEventListener('click', function (e) {
        const t = e.target.closest('[data-act]');
        if (!t || !t.dataset.po) { if (!e.target.closest('.grn-act-menu')) closeMenu(); return; }
        e.stopPropagation();
        if (t.dataset.act === 'menu') {
            const wasOpen = menu && menu.dataset.owner === t.dataset.po;
            closeMenu();
            if (!wasOpen) { openMenu(t, t.dataset.po); menu.dataset.owner = t.dataset.po; }
            return;
        }
        runAction(t.dataset.act, t.dataset.po);
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
    window.addEventListener('resize', closeMenu);
    window.addEventListener('scroll', closeMenu, true);
})();

function editPurchaseOrder(poNumber) {
    window.location.href = (window.StockInConfig?.routes?.purchaseOrders || '/purchase/purchase-orders') + "?edit=" + encodeURIComponent(poNumber);
}

function printGrnSlip(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;
    const rows = (po.items || []).map((it, i) => {
        const ord = parseFloat(it.quantity) || 0;
        const rec = parseFloat(it.receivedQuantity) || 0;
        return `<tr><td>${i + 1}</td><td>${grnEsc(it.name || it.itemName)}<br><small>${grnEsc(it.sku)}</small></td><td>${grnEsc(it.unit || it.uom)}</td><td class="n">${ord}</td><td class="n">${rec}</td><td class="n">${Math.max(0, ord - rec)}</td><td class="box"></td><td class="box"></td></tr>`;
    }).join('');
    const w = window.open('', '_blank', 'width=900,height=700');
    if (!w) { alert('Pop-up blocked. Please allow pop-ups to print the receiving slip.'); return; }
    w.document.write(`<!doctype html><html><head><title>GRN - ${grnEsc(po.poNumber)}</title><style>
        body{font-family:Arial,sans-serif;font-size:12px;color:#111;margin:24px}
        h1{font-size:18px;margin:0 0 4px} .meta{display:grid;grid-template-columns:1fr 1fr;gap:4px 24px;margin:12px 0}
        table{width:100%;border-collapse:collapse;margin-top:8px} th,td{border:1px solid #444;padding:5px 6px;text-align:left}
        th{background:#eee} .n{text-align:right} .box{width:70px} .sig{display:flex;gap:40px;margin-top:48px}
        .sig div{flex:1;border-top:1px solid #111;padding-top:4px;text-align:center}
    </style></head><body>
        <h1>Receiving Slip / Goods Received Note</h1>
        <div class="meta"><div><b>PO No.:</b> ${grnEsc(po.poNumber)}</div><div><b>Order Date:</b> ${grnEsc(po.orderDate || 'N/A')}</div>
        <div><b>Vendor:</b> ${grnEsc(po.vendorTradeName || po.vendorName)}</div><div><b>Deliver To:</b> ${grnEsc(po.deliveryLocation || 'Central Warehouse')}</div>
        <div><b>Status:</b> ${grnEsc(po.status)}</div><div><b>Delivery Slip No.:</b> ______________</div></div>
        <table><thead><tr><th>#</th><th>Item</th><th>UoM</th><th>Ordered</th><th>Prior Rcvd</th><th>Open</th><th>Accepted</th><th>Rejected</th></tr></thead><tbody>${rows}</tbody></table>
        <div class="sig"><div>Received by</div><div>Checked by</div><div>Supplier Rep.</div></div>
        <script>window.onload=function(){window.print();}<\/script>
    </body></html>`);
    w.document.close();
}

function getPaymentBadge(status) {
    status = status || 'Unpaid';
    if (status.includes('Paid in Full')) return `<span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Paid</span>`;
    if (status.includes('Partial')) return `<span class="hr-badge hr-badge-warning"><i class="ph ph-clock"></i> Partial</span>`;
    return `<span class="hr-badge hr-badge-info"><i class="ph ph-credit-card"></i> On Credit</span>`;
}

function getOrderStatusBadge(status) {
    status = status || 'Draft';
    if (status.includes('Fully Received')) return `<span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Fully Received</span>`;
    if (status.includes('Partially Received')) return `<span class="hr-badge hr-badge-warning"><i class="ph ph-clock-countdown"></i> Partial Backlog</span>`;
    if (status.includes('Approved')) return `<span class="hr-badge hr-badge-purple"><i class="ph ph-truck"></i> Ready to Receive</span>`;
    if (status.includes('Short-Closed')) return `<span class="hr-badge hr-badge-neutral"><i class="ph ph-x-circle"></i> Short-Closed</span>`;
    return `<span class="hr-badge hr-badge-neutral">${status}</span>`;
}

/**
 * ----------------------------------------------------------------------------
 * TAB 2: PENDING PO (Backlogs & Open Deliveries Queue)
 * ----------------------------------------------------------------------------
 */
function renderPendingPoList() {
    const tbody = document.getElementById('pendingPoTableBody');
    if (!tbody) return;

    const pendingPos = (window.StockInStore.purchaseOrders || []).filter(p => ['Approved / Issued', 'Partially Received'].includes(p.status));

    if (pendingPos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px; color: var(--grn-text-muted);">
                    <i class="ph ph-check-circle" style="font-size: 32px; display: block; margin-bottom: 8px; color: #10b981;"></i>
                    No pending orders or backlogs! All purchase orders have been received.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = pendingPos.map(p => {
        const items = p.items || [];
        const totalOrdered = items.reduce((acc, it) => acc + (parseFloat(it.quantity) || 0), 0);
        const totalReceived = items.reduce((acc, it) => acc + (parseFloat(it.receivedQuantity) || 0), 0);
        const backlogQty = Math.max(0, totalOrdered - totalReceived);

        const backlogValue = items.reduce((acc, it) => {
            const ord = parseFloat(it.quantity) || 0;
            const rec = parseFloat(it.receivedQuantity) || 0;
            const open = Math.max(0, ord - rec);
            return acc + (open * (parseFloat(it.unitPrice) || 0));
        }, 0);

        return `
            <tr>
                <td><strong class="grn-mono" style="color: var(--grn-primary);">${p.poNumber}</strong></td>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${p.vendorTradeName || p.vendorName}</div>
                </td>
                <td style="font-size: 12px; font-weight: 600; color: #0284c7;">${p.expectedDelivery || 'Scheduled'}</td>
                <td style="font-size: 11.5px; color: var(--grn-text-muted);">${p.deliveryLocation || 'Central Warehouse'}</td>
                <td style="text-align: center; font-weight: 600;">${totalOrdered}</td>
                <td style="text-align: center; font-weight: 600; color: #047857;">${totalReceived}</td>
                <td style="text-align: center; font-weight: 800; color: #b45309; font-size: 13.5px;">${backlogQty}</td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(backlogValue)}</td>
                <td style="text-align: center;">${getOrderStatusBadge(p.status)}</td>
                <td style="text-align: center;">
                    ${grnActionCell(p.poNumber, true)}
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * ----------------------------------------------------------------------------
 * TAB 3: CREATE NEW PO TO RECEIVED (Bypass PO via RFQ Builder Layout)
 * ----------------------------------------------------------------------------
 */
function resetDirectReceivingForm() {
    const year = new Date().getFullYear();
    const count = (window.StockInStore.goodsReceipts || []).length + 1;
    const ref = `DIR-REC-${year}-${String(count).padStart(4, '0')}`;

    window.StockInStore.directForm = {
        refNumber: ref,
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        customStallName: '',
        reasonCode: 'Direct Spot Purchase',
        deliverySlip: '',
        carrier: '',
        receivedAt: new Date().toISOString().slice(0, 16),
        deliveryLocation: 'Central Commissary - Receiving Dock 1',
        settlementMode: 'IMMEDIATE_COD',
        paymentMethod: 'Cash / Currency',
        paymentRef: '',
        amountPaid: 0,
        paymentDate: new Date().toISOString().slice(0, 10),
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: '',
        freight: 0,
        customs: 0,
        handling: 0,
        items: []
    };

    document.getElementById('directRecRefDisplay').textContent = ref;
    document.getElementById('directReceivedAt').value = window.StockInStore.directForm.receivedAt;
    document.getElementById('directDeliverySlip').value = '';
    document.getElementById('directCarrier').value = '';
    document.getElementById('directCustomStallName').value = '';
    document.getElementById('directVendorSelect').value = '';
    document.getElementById('directVendorPillBox').style.display = 'none';

    // Reset financial settlement inputs
    const elPayAmt = document.getElementById('directPaymentAmount');
    if (elPayAmt) elPayAmt.value = '';
    const elPayRef = document.getElementById('directPaymentRef');
    if (elPayRef) elPayRef.value = '';
    const elPayRem = document.getElementById('directPaymentRemarks');
    if (elPayRem) elPayRem.value = '';
    const elPayDate = document.getElementById('directPaymentDate');
    if (elPayDate) elPayDate.value = new Date().toISOString().slice(0, 10);
    const elSettlementMode = document.getElementById('directSettlementMode');
    if (elSettlementMode) elSettlementMode.value = 'IMMEDIATE_COD';
    const elPaymentMethod = document.getElementById('directPaymentMethod');
    if (elPaymentMethod) elPaymentMethod.value = 'Cash / Currency';
    const elFundSource = document.getElementById('directPaymentFundSource');
    if (elFundSource) elFundSource.value = 'Branch Petty Cash Fund';

    renderDirectItemsTable();
    calculateDirectTotals();
}

function populateVendorDropdown() {
    const select = document.getElementById('directVendorSelect');
    if (!select) return;

    const vendors = window.StockInStore.vendors || [];
    select.innerHTML = '<option value="">-- Choose Approved Vendor from Masterlist --</option>' +
        vendors.map(v => `<option value="${v.id || v.code}">${v.tradeName || v.legalName} (${v.category || 'Supplier'})</option>`).join('');
}

function populateCatalogPicker() {
    const picker = document.getElementById('directCatalogPicker');
    if (!picker) return;

    const products = window.StockInStore.products || [];
    picker.innerHTML = '<option value="">-- Quick Pick from Product Catalog (Item Master) --</option>' +
        products.map(p => `<option value="${p.sku}">[${p.sku}] ${p.name} &bull; ${p.category} (Stock: ${p.stock} ${p.unit}) - Cost: ₱${formatMoney(p.costPrice)}</option>`).join('');
}

function handleDirectVendorSelect(vendorId) {
    if (!vendorId) {
        document.getElementById('directVendorPillBox').style.display = 'none';
        window.StockInStore.directForm.vendorId = '';
        window.StockInStore.directForm.vendorName = '';
        return;
    }

    const vendor = (window.StockInStore.vendors || []).find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    window.StockInStore.directForm.vendorId = vendor.id || vendor.code;
    window.StockInStore.directForm.vendorName = vendor.legalName || vendor.tradeName;
    window.StockInStore.directForm.vendorTradeName = vendor.tradeName || vendor.legalName;

    document.getElementById('directVendorTradeName').textContent = vendor.tradeName || vendor.legalName;
    document.getElementById('directVendorCategoryTag').textContent = `Category: ${vendor.category || 'General'}`;
    document.getElementById('directVendorAvatar').textContent = (vendor.tradeName || 'V').slice(0, 2).toUpperCase();
    document.getElementById('directVendorPillBox').style.display = 'flex';
}

function handleDirectSettlementChange(val) {
    window.StockInStore.directForm.settlementMode = val;
    const codBox = document.getElementById('directCodFields');
    const extraBox = document.getElementById('directDisbursementExtraFields');
    const methodSelect = document.getElementById('directPaymentMethod');

    if (val === 'IMMEDIATE_COD') {
        if (codBox) codBox.style.display = 'grid';
        if (extraBox) extraBox.style.display = 'grid';
        if (methodSelect && methodSelect.value.includes('Trade Credit')) {
            methodSelect.value = 'Cash / Currency';
        }
        fillDirectFullBalance();
    } else if (val === 'CREDIT_NET30') {
        if (codBox) codBox.style.display = 'grid';
        if (extraBox) extraBox.style.display = 'grid';
        if (methodSelect) methodSelect.value = 'Trade Credit (Net 30/15)';
        const amtInput = document.getElementById('directPaymentAmount');
        if (amtInput) amtInput.value = '0.00';
        handleDirectPaymentAmountChange(0);
    } else {
        if (codBox) codBox.style.display = 'grid';
        if (extraBox) extraBox.style.display = 'grid';
        fillDirectFullBalance();
    }
}

function fillDirectFullBalance() {
    const grandValuation = window.StockInStore.directForm.totalValuation || 0;
    const amtInput = document.getElementById('directPaymentAmount');
    if (amtInput) {
        amtInput.value = grandValuation.toFixed(2);
        handleDirectPaymentAmountChange(grandValuation);
        amtInput.focus();
    }
}

function handleDirectPaymentAmountChange(amount) {
    const val = parseFloat(amount) || 0;
    window.StockInStore.directForm.amountPaid = val;
    const grandValuation = window.StockInStore.directForm.totalValuation || 0;
    const balanceDue = Math.max(0, grandValuation - val);

    const grossEl = document.getElementById('directSettlementGrossDisplay');
    if (grossEl) grossEl.textContent = `₱${formatMoney(grandValuation)}`;
    const paidEl = document.getElementById('directSettlementPaidDisplay');
    if (paidEl) paidEl.textContent = `₱${formatMoney(val)}`;
    const balEl = document.getElementById('directSettlementBalanceDisplay');
    if (balEl) balEl.textContent = `₱${formatMoney(balanceDue)}`;

    const badge = document.getElementById('directPaymentStatusBadge');
    if (badge) {
        if (val >= grandValuation && grandValuation > 0) {
            badge.className = 'hr-badge hr-badge-success';
            badge.innerHTML = '<i class="ph ph-check-circle"></i> Paid in Full';
            window.StockInStore.directForm.paymentStatus = 'Paid in Full / Cash Out';
        } else if (val > 0) {
            badge.className = 'hr-badge hr-badge-amber';
            badge.innerHTML = `<i class="ph ph-clock"></i> Partial (₱${formatMoney(val)})`;
            window.StockInStore.directForm.paymentStatus = 'Partial Payment';
        } else {
            badge.className = 'hr-badge hr-badge-neutral';
            badge.innerHTML = '<i class="ph ph-credit-card"></i> Unpaid / Credit';
            window.StockInStore.directForm.paymentStatus = 'Unpaid / Credit';
        }
    }
}

function toggleDirectLandedCost() {
    const panel = document.getElementById('directLandedCostPanel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function addPickedCatalogItem() {
    const picker = document.getElementById('directCatalogPicker');
    const sku = picker?.value;
    if (!sku) {
        alert('Please select a product from the catalog dropdown.');
        return;
    }

    const product = (window.StockInStore.products || []).find(p => p.sku === sku);
    if (!product) return;

    const idx = window.StockInStore.directForm.items.length + 1;
    window.StockInStore.directForm.items.push({
        id: `direct_item_${idx}_${Date.now()}`,
        sku: product.sku,
        name: product.name,
        category: product.category,
        unit: product.unit || 'Pc',
        purchasingUnit: product.unit || 'Pc',
        uomMultiplier: 1.0,
        receivedQty: 10,
        unitCost: parseFloat(product.costPrice) || 100.0,
        lotNumber: `LOT-DIR-${new Date().toISOString().slice(2,10).replace(/-/g,'')}-${idx}`,
        mfgDate: '',
        expiryDate: '',
        storageBin: 'DOCK-STAGING-01'
    });

    renderDirectItemsTable();
    calculateDirectTotals();
    picker.value = '';
}

function renderDirectItemsTable() {
    const tbody = document.getElementById('directItemsTableBody');
    const prompt = document.getElementById('directNoItemsPrompt');
    const countBadge = document.getElementById('directItemCountBadge');
    if (!tbody) return;

    const items = window.StockInStore.directForm.items || [];
    if (countBadge) countBadge.textContent = `${items.length} Items Added`;

    if (items.length === 0) {
        tbody.innerHTML = '';
        if (prompt) prompt.style.display = 'block';
        return;
    }
    if (prompt) prompt.style.display = 'none';

    tbody.innerHTML = items.map((it, idx) => {
        const subtotal = (parseFloat(it.receivedQty) || 0) * (parseFloat(it.unitCost) || 0);

        return `
            <tr>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${it.name}</div>
                    <div class="grn-mono" style="font-size: 11px; color: var(--grn-text-muted);">SKU: ${it.sku} &bull; ${it.category}</div>
                </td>
                <td>
                    <select class="grn-select" style="padding: 4px 6px; font-size: 11.5px;" onchange="updateDirectUom(${idx}, this.value)">
                        <option value="${it.unit}" ${it.purchasingUnit === it.unit ? 'selected' : ''}>${it.unit} (Base)</option>
                        <option value="Box (24 Pcs)" ${it.purchasingUnit === 'Box (24 Pcs)' ? 'selected' : ''}>Box (24 Pcs)</option>
                        <option value="Carton (12 Pcs)" ${it.purchasingUnit === 'Carton (12 Pcs)' ? 'selected' : ''}>Carton (12 Pcs)</option>
                        <option value="Sack (50 Kg)" ${it.purchasingUnit === 'Sack (50 Kg)' ? 'selected' : ''}>Sack (50 Kg)</option>
                    </select>
                </td>
                <td style="text-align: center; font-size: 11.5px; font-weight: 600; color: #64748b;">
                    &times;${it.uomMultiplier || 1.0}
                </td>
                <td style="text-align: center;">
                    <input type="number" class="grn-search-input" style="width: 75px; text-align: center; padding: 4px;" value="${it.receivedQty}" min="0.01" step="any" oninput="updateDirectLineQty(${idx}, this.value)">
                </td>
                <td style="text-align: right;">
                    <input type="number" class="grn-search-input" style="width: 80px; text-align: right; padding: 4px;" value="${it.unitCost}" min="0.01" step="0.01" oninput="updateDirectLineCost(${idx}, this.value)">
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(subtotal)}</td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 3px 7px; font-size: 10.5px;" onclick="openTraceModalForLine(${idx})">
                        <i class="ph ph-barcode"></i> Lot
                    </button>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #ef4444; padding: 3px 6px;" onclick="removeDirectLine(${idx})">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function updateDirectUom(idx, val) {
    const it = window.StockInStore.directForm.items[idx];
    if (!it) return;
    it.purchasingUnit = val;
    if (val === 'Box (24 Pcs)') it.uomMultiplier = 24.0;
    else if (val === 'Carton (12 Pcs)') it.uomMultiplier = 12.0;
    else if (val === 'Sack (50 Kg)') it.uomMultiplier = 50.0;
    else it.uomMultiplier = 1.0;

    renderDirectItemsTable();
    calculateDirectTotals();
}

function updateDirectLineQty(idx, val) {
    const it = window.StockInStore.directForm.items[idx];
    if (it) {
        it.receivedQty = Math.max(0.01, parseFloat(val) || 0);
        calculateDirectTotals();
    }
}

function updateDirectLineCost(idx, val) {
    const it = window.StockInStore.directForm.items[idx];
    if (it) {
        it.unitCost = Math.max(0.01, parseFloat(val) || 0);
        calculateDirectTotals();
    }
}

function removeDirectLine(idx) {
    window.StockInStore.directForm.items.splice(idx, 1);
    renderDirectItemsTable();
    calculateDirectTotals();
}

function calculateDirectTotals() {
    const items = window.StockInStore.directForm.items || [];
    const baseSubtotal = items.reduce((acc, it) => acc + ((parseFloat(it.receivedQty) || 0) * (parseFloat(it.unitCost) || 0)), 0);

    const freight = parseFloat(document.getElementById('directFreightFee')?.value) || 0;
    const customs = parseFloat(document.getElementById('directCustomsFee')?.value) || 0;
    const handling = parseFloat(document.getElementById('directHandlingFee')?.value) || 0;
    const totalSurcharges = freight + customs + handling;

    const grandValuation = baseSubtotal + totalSurcharges;
    window.StockInStore.directForm.totalValuation = grandValuation;

    document.getElementById('directTotalValuationDisplay').textContent = `₱${formatMoney(grandValuation)}`;

    // Sync settlement displays
    const grossEl = document.getElementById('directSettlementGrossDisplay');
    if (grossEl) grossEl.textContent = `₱${formatMoney(grandValuation)}`;

    const amtInput = document.getElementById('directPaymentAmount');
    const settlementMode = document.getElementById('directSettlementMode')?.value || 'IMMEDIATE_COD';
    
    // Auto-update amount if COD and not yet manually entered or is full
    if (amtInput) {
        if (settlementMode === 'IMMEDIATE_COD' && (!amtInput.value || parseFloat(amtInput.value) === 0)) {
            amtInput.value = grandValuation.toFixed(2);
        }
        handleDirectPaymentAmountChange(amtInput.value);
    }
}

/**
 * Submit Direct Inbound Receiving (Bypassing standard PO creation)
 */
async function submitDirectReceivingOrder() {
    const form = window.StockInStore.directForm;

    // Validate inputs
    const slip = document.getElementById('directDeliverySlip')?.value;
    if (!slip) {
        alert('Supplier Delivery Slip / DR # is required.');
        document.getElementById('directDeliverySlip')?.focus();
        return;
    }

    const vendorId = document.getElementById('directVendorSelect')?.value;
    const customStall = document.getElementById('directCustomStallName')?.value;
    if (!vendorId && !customStall && !form.vendorName) {
        alert('Please select an approved vendor or enter a custom stall name.');
        return;
    }

    if (form.items.length === 0) {
        alert('Please add at least one line item from the product catalog.');
        return;
    }

    // Check mandatory unit cost
    const hasZeroCost = form.items.some(it => (parseFloat(it.unitCost) || 0) <= 0);
    if (hasZeroCost) {
        alert('Every line item must have a valid unit cost greater than ₱0.00 for inventory valuation.');
        return;
    }

    const finalVendorName = form.vendorTradeName || form.vendorName || customStall || 'Direct Spot Vendor';
    const recAt = document.getElementById('directReceivedAt')?.value || new Date().toISOString();
    const settlement = document.getElementById('directSettlementMode')?.value || 'IMMEDIATE_COD';
    const carrier = document.getElementById('directCarrier')?.value || 'Purchaser Transport';
    const loc = document.getElementById('directDeliveryLocation')?.value || 'Central Commissary - Receiving Dock 1';

    const freight = parseFloat(document.getElementById('directFreightFee')?.value) || 0;
    const customs = parseFloat(document.getElementById('directCustomsFee')?.value) || 0;
    const handling = parseFloat(document.getElementById('directHandlingFee')?.value) || 0;

    const amountPaidVal = parseFloat(document.getElementById('directPaymentAmount')?.value) || 0;
    const paymentMethodVal = document.getElementById('directPaymentMethod')?.value || 'Cash / Currency';
    const paymentRefVal = document.getElementById('directPaymentRef')?.value?.trim() || '';
    const paymentDateVal = document.getElementById('directPaymentDate')?.value || new Date().toISOString().slice(0, 10);
    const fundSourceVal = document.getElementById('directPaymentFundSource')?.value || 'Branch Petty Cash Fund';
    const paymentRemarksVal = document.getElementById('directPaymentRemarks')?.value?.trim() || '';

    const payload = {
        po_number: form.poNumber || null,
        vendor_id: form.vendorId || vendorId || null,
        vendor_name: finalVendorName,
        vendor_trade_name: form.vendorTradeName || finalVendorName,
        delivery_slip: slip,
        carrier: carrier,
        reason_code: document.getElementById('directReasonCode')?.value || (form.poNumber ? `PO Fulfillment: ${form.poNumber}` : 'Direct Spot Purchase'),
        delivery_location: loc,
        settlement_mode: settlement,
        payment_method: paymentMethodVal,
        payment_ref: paymentRefVal,
        amount_paid: amountPaidVal,
        payment_date: paymentDateVal,
        fund_source: fundSourceVal,
        payment_remarks: paymentRemarksVal,
        freight: freight,
        customs: customs,
        handling: handling,
        items: form.items.map(it => ({
            sku: it.sku,
            name: it.name,
            unit: it.unit,
            purchasing_unit: it.purchasingUnit,
            uom_multiplier: it.uomMultiplier || 1.0,
            received_qty: parseFloat(it.receivedQty) || 0,
            unit_cost: parseFloat(it.unitCost) || 0,
            lot_number: it.lotNumber || null,
            expiry_date: it.expiryDate || null,
            notes: it.notes || null
        }))
    };

    // Pessimistic UI Locking
    const submitBtn = document.querySelector('button[onclick="submitDirectReceivingOrder()"]');
    const origHtml = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving to Database...';
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(window.StockInConfig?.routes?.receiveStock || '/inventory/api/receive-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Server rejected receiving transaction.');
        }

        showToast(`✓ Stock In Received! Voucher ${json.data.grn_number} posted to Stock Ledger & Live Inventory.`, 'success');

        // Record payment in shared rms_po_payments for cross-module consistency
        if (amountPaidVal > 0) {
            try {
                const storedPayments = JSON.parse(localStorage.getItem('rms_po_payments') || '[]');
                storedPayments.push({
                    id: `PAY-DIR-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
                    poNumber: json.data?.grn_number || 'DIR-REC-2026-0001',
                    amount: amountPaidVal,
                    method: paymentMethodVal,
                    reference: paymentRefVal || `DOCK-${Date.now().toString().slice(-4)}`,
                    paymentDate: paymentDateVal,
                    fundSource: fundSourceVal,
                    remarks: paymentRemarksVal || `Direct Inbound Stock Settlement for ${finalVendorName}`,
                    recordedAt: new Date().toISOString(),
            actor: window.StockInConfig?.user?.name || 'Warehouse Logistics Supervisor'
                });
                localStorage.setItem('rms_po_payments', JSON.stringify(storedPayments));
            } catch (payErr) {
                console.warn('Failed to mirror payment to localStorage:', payErr);
            }
        }

        const poBanner = document.getElementById('builderPoFulfillmentBanner');
        if (poBanner) poBanner.style.display = 'none';

        // Refresh live state from server
        await refreshStockInDataFromServer();
        switchGrnTab('tab-po-list');
    } catch(err) {
        console.error('Receiving transaction failed:', err);
        alert(`Error processing receiving transaction: ${err.message}`);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origHtml;
        }
    }
}

/**
 * ----------------------------------------------------------------------------
 * EXISTING PO INBOUND RECEIVING INSPECTION DRAWER
 * ----------------------------------------------------------------------------
 */
function openReceiveForPo(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;

    window.StockInStore.activePoReceiving = JSON.parse(JSON.stringify(po));

    document.getElementById('drawerPoSubtitle').textContent = `Fulfilling Purchase Order: ${po.poNumber}`;
    document.getElementById('drawerVendorName').textContent = po.vendorTradeName || po.vendorName;
    document.getElementById('drawerDeliverySlip').value = '';
    document.getElementById('drawerOverToleranceBox').style.display = 'none';

    // Header / payment linkage
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setTxt('drawerOrderDate', po.orderDate || 'N/A');
    setTxt('drawerExpectedDate', po.expectedDelivery || 'Scheduled');
    setTxt('drawerDeliverTo', po.deliveryLocation || 'Central Warehouse');
    const stEl = document.getElementById('drawerPoStatus');
    if (stEl) stEl.textContent = po.status || '';
    const payRefEl = document.getElementById('drawerPaymentRef');
    if (payRefEl) payRefEl.value = po.payment_reference || '';
    renderDrawerPaymentLinkage(po);

    // Populate line items with open balance (compressed columns)
    const items = po.items || [];
    const tbody = document.getElementById('drawerLinesTableBody');

    tbody.innerHTML = items.map((it, idx) => {
        const ordered = parseFloat(it.quantity) || 0;
        const prior = parseFloat(it.receivedQuantity) || 0;
        const open = Math.max(0, ordered - prior);
        const price = parseFloat(it.unitPrice) || 0;

        return `
            <tr id="drawerLineRow_${idx}">
                <td>
                    <div class="grn-insp-item">${grnEsc(it.name || it.itemName)}</div>
                    <div class="grn-mono grn-insp-sub">${grnEsc(it.sku)} · ${grnEsc(it.unit || it.uom)} · ₱${formatMoney(price)}</div>
                </td>
                <td class="c"><span class="grn-insp-qty">${ordered}</span> · <span class="grn-insp-qty" style="color:#047857;">${prior}</span> · <span class="grn-insp-qty" style="color:#b45309;">${open}</span></td>
                <td class="c"><input type="number" min="0" class="grn-search-input grn-insp-num" id="lineDelivered_${idx}" value="${open}" oninput="calcDrawerTriad(${idx})" aria-label="Delivered quantity"></td>
                <td class="c"><input type="number" min="0" class="grn-search-input grn-insp-num grn-insp-ok" id="lineAccepted_${idx}" value="${open}" oninput="calcDrawerTriad(${idx})" aria-label="Accepted quantity"></td>
                <td class="c">
                    <div class="grn-insp-rej">
                        <input type="number" class="grn-search-input grn-insp-num grn-insp-bad" id="lineRejected_${idx}" value="0" readonly tabindex="-1" aria-label="Rejected quantity">
                        <select class="grn-select grn-insp-reason" id="lineReason_${idx}" style="display:none;" aria-label="Rejection reason">
                            <option value="">Reason…</option>
                            <option value="Damaged in Transit">Damaged</option>
                            <option value="Expired / Near Expiry">Expired</option>
                            <option value="Wrong Specification">Wrong spec</option>
                        </select>
                    </div>
                </td>
                <td class="r grn-currency" id="lineValue_${idx}">₱${formatMoney(open * price)}</td>
                <td class="c"><input type="checkbox" id="lineShortClose_${idx}" title="Short-Close remaining units if supplier confirmed out of stock" aria-label="Short-close"></td>
            </tr>
        `;
    }).join('');

    calcDrawerTotalValuation();
    document.getElementById('grnInspectionDrawer')?.classList.add('active');
}

function closeInspectionDrawer() {
    document.getElementById('grnInspectionDrawer')?.classList.remove('active');
}

function calcDrawerTriad(idx) {
    const elDel = document.getElementById(`lineDelivered_${idx}`);
    const elAcc = document.getElementById(`lineAccepted_${idx}`);
    const elRej = document.getElementById(`lineRejected_${idx}`);
    const elReason = document.getElementById(`lineReason_${idx}`);

    const del = parseFloat(elDel?.value) || 0;
    const acc = parseFloat(elAcc?.value) || 0;
    const rej = Math.max(0, del - acc);
    if (elRej) elRej.value = rej;
    if (elReason) elReason.style.display = rej > 0 ? '' : 'none';

    const it = (window.StockInStore.activePoReceiving?.items || [])[idx];
    const elVal = document.getElementById(`lineValue_${idx}`);
    if (it && elVal) elVal.textContent = `₱${formatMoney(acc * (parseFloat(it.unitPrice) || 0))}`;

    calcDrawerTotalValuation();
}

function renderDrawerPaymentLinkage(po) {
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    const gross = parseFloat(po.grossTotal) || (po.items || []).reduce((a, it) => a + (parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0), 0);
    const paid = parseFloat(po.amount_paid) || 0;
    const balance = po.balance_due != null && po.balance_due !== '' ? (parseFloat(po.balance_due) || 0) : Math.max(0, gross - paid);

    setTxt('drawerPayMethod', po.paymentMethod || 'N/A');
    setTxt('drawerPayTotal', `₱${formatMoney(gross)}`);
    setTxt('drawerPayPaid', `₱${formatMoney(paid)}`);
    setTxt('drawerPayBalance', `₱${formatMoney(balance)}`);
    setTxt('drawerPayStatus', po.paymentStatus || 'Unpaid');

    // Build unified history: PO payment record + locally registered payments + linked GRNs
    const hist = [];
    if (paid > 0 || po.payment_date) {
        hist.push({
            date: po.payment_date || '', type: 'Payment', ref: po.payment_reference || '—',
            method: po.paymentMethod || '', amount: paid, note: po.payment_remarks || po.fund_source || ''
        });
    }
    try {
        JSON.parse(localStorage.getItem('rms_po_payments') || '[]')
            .filter(x => x.poNumber === po.poNumber)
            .forEach(x => hist.push({
                date: x.paymentDate || '', type: 'Payment', ref: x.reference || x.id || '—',
                method: x.paymentMethod || '', amount: parseFloat(x.amount) || 0, note: x.remarks || ''
            }));
    } catch (e) {}
    (window.StockInStore.goodsReceipts || [])
        .filter(r => (r.po_number || r.poNumber) === po.poNumber)
        .forEach(r => hist.push({
            date: String(r.received_at || r.receivedAt || '').slice(0, 10), type: 'Receipt',
            ref: (r.grn_number || r.grnNumber || '—') + ((r.delivery_slip_no || r.deliverySlip) ? ' · ' + (r.delivery_slip_no || r.deliverySlip) : ''),
            method: r.payment_method || r.paymentMethod || '', amount: parseFloat(r.gross_total ?? r.totalValuation) || 0,
            note: r.status || ''
        }));
    hist.sort((a, b) => String(b.date).localeCompare(String(a.date)));

    setTxt('drawerHistCount', hist.length + (hist.length === 1 ? ' entry' : ' entries'));
    const hb = document.getElementById('drawerHistoryBody');
    if (!hb) return;
    hb.innerHTML = hist.length === 0
        ? '<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:14px;">No payments or prior receipts recorded for this PO yet.</td></tr>'
        : hist.map(h => `<tr>
            <td>${grnEsc(h.date || '—')}</td>
            <td><span class="hr-badge ${h.type === 'Payment' ? 'hr-badge-success' : 'hr-badge-purple'}">${h.type}</span></td>
            <td class="grn-mono">${grnEsc(h.ref)}</td>
            <td>${grnEsc(h.method)}</td>
            <td class="r grn-currency">₱${formatMoney(h.amount)}</td>
            <td style="color:#64748b;">${grnEsc(h.note)}</td></tr>`).join('');
}

function calcDrawerTotalValuation() {
    const po = window.StockInStore.activePoReceiving;
    if (!po) return;

    let total = 0;
    (po.items || []).forEach((it, idx) => {
        const acc = parseFloat(document.getElementById(`lineAccepted_${idx}`)?.value) || 0;
        total += acc * (parseFloat(it.unitPrice) || 0);
    });

    document.getElementById('drawerTotalAssetValue').textContent = `₱${formatMoney(total)}`;
}

async function commitInspectionReceipt() {
    const po = window.StockInStore.activePoReceiving;
    if (!po) return;

    const slip = document.getElementById('drawerDeliverySlip')?.value;
    if (!slip) {
        alert('Please enter the Delivery Slip / DR #.');
        document.getElementById('drawerDeliverySlip')?.focus();
        return;
    }

    const itemsToReceive = [];
    (po.items || []).forEach((it, idx) => {
        const acc = parseFloat(document.getElementById(`lineAccepted_${idx}`)?.value) || 0;
        const rejQty = parseFloat(document.getElementById(`lineRejected_${idx}`)?.value) || 0;
        const rejNote = rejQty > 0 ? (document.getElementById(`lineReason_${idx}`)?.value || 'unspecified') : '';
        if (acc > 0) {
            itemsToReceive.push({
                sku: it.sku,
                name: it.name || it.itemName,
                unit: it.unit || it.uom || 'Unit',
                purchasing_unit: it.unit || it.uom || 'Unit',
                uom_multiplier: 1.0,
                received_qty: acc,
                unit_cost: parseFloat(it.unitPrice) || 0,
                lot_number: null,
                expiry_date: null,
                notes: `Received via Dock Inspection for ${po.poNumber}` + (rejNote ? ` | Rejected ${rejQty}: ${rejNote}` : '')
            });
        }
    });

    if (itemsToReceive.length === 0) {
        alert('Please accept at least one item quantity to receive.');
        return;
    }

    const payload = {
        po_number: po.poNumber,
        vendor_id: po.vendorId,
        vendor_name: po.vendorName,
        vendor_trade_name: po.vendorTradeName,
        delivery_slip: slip,
        carrier: 'Commercial Carrier',
        reason_code: `PO Receiving Dock: ${po.poNumber}`,
        delivery_location: po.deliveryLocation || 'Central Commissary - Receiving Dock 1',
        settlement_mode: po.paymentStatus || 'PURCHASE_ORDER',
        payment_method: po.paymentMethod || 'Trade Credit (Net 30/15)',
        payment_ref: (document.getElementById('drawerPaymentRef')?.value || '').trim(),
        freight: 0,
        customs: 0,
        handling: 0,
        items: itemsToReceive
    };

    const commitBtn = document.querySelector('button[onclick="commitInspectionReceipt()"]');
    const origHtml = commitBtn ? commitBtn.innerHTML : '';
    if (commitBtn) {
        commitBtn.disabled = true;
        commitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(window.StockInConfig?.routes?.receiveStock || '/inventory/api/receive-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Server error committing dock inspection.');
        }

        closeInspectionDrawer();
        showToast(`✓ Purchase Order ${po.poNumber} fulfilled! Voucher ${json.data.grn_number} saved to DB.`, 'success');
        await refreshStockInDataFromServer();
    } catch(err) {
        console.error('Dock inspection commit error:', err);
        alert(`Error: ${err.message}`);
    } finally {
        if (commitBtn) {
            commitBtn.disabled = false;
            commitBtn.innerHTML = origHtml;
        }
    }
}

/**
 * ----------------------------------------------------------------------------
 * STORAGE & LEDGER HELPERS
 * ----------------------------------------------------------------------------
 */
function updateProductsStock(grn) {
    (grn.items || []).forEach(it => {
        incrementProductStock(it.sku, it.baseQuantity || it.receivedQty, it.unitCost);
    });
}

function incrementProductStock(sku, qty, cost) {
    const products = window.StockInStore.products || [];
    const p = products.find(prod => prod.sku === sku);
    if (p) {
        const oldStock = parseFloat(p.stock) || 0;
        const oldCost = parseFloat(p.costPrice) || 0;
        const addQty = parseFloat(qty) || 0;
        const newCost = parseFloat(cost) || oldCost;

        const totalQty = oldStock + addQty;
        const movingAvg = totalQty > 0 ? ((oldStock * oldCost) + (addQty * newCost)) / totalQty : newCost;

        p.stock = totalQty;
        p.costPrice = Math.round(movingAvg * 100) / 100;
        localStorage.setItem('rms_inventory_products', JSON.stringify(products));
    }
}

function appendLedgerStockIn(grn) {
    let ledger = [];
    try {
        const raw = localStorage.getItem('rms_stocks_ledger');
        if (raw) ledger = JSON.parse(raw);
    } catch(e) {}

    const dateStr = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

    (grn.items || []).forEach(it => {
        ledger.push({
            date: dateStr,
            ref: grn.grnNumber,
            type: `Stock In (${grn.receiptReasonCode})`,
            sku: it.sku,
            productName: it.name,
            qty: `+${it.baseQuantity || it.receivedQty}`,
            unit: it.unit,
            lotNumber: it.lotNumber,
            storageLocation: it.storageBin || 'DOCK-STAGING-01',
            note: `Direct receiving via ${grn.deliveryNote} from ${grn.vendorName}`
        });
    });

    localStorage.setItem('rms_stocks_ledger', JSON.stringify(ledger));
}

function logLedgerMovement(ref, sku, name, qty, cost, vendor) {
    let ledger = [];
    try {
        const raw = localStorage.getItem('rms_stocks_ledger');
        if (raw) ledger = JSON.parse(raw);
    } catch(e) {}

    const dateStr = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    ledger.push({
        date: dateStr,
        ref: ref,
        type: 'Stock In (Purchase Order)',
        sku: sku,
        productName: name,
        qty: `+${qty}`,
        note: `Received from ${vendor}`
    });

    localStorage.setItem('rms_stocks_ledger', JSON.stringify(ledger));
}

function registerPaymentVoucher(poNumber, amount, method, ref) {
    let payments = [];
    try {
        const raw = localStorage.getItem('rms_po_payments');
        if (raw) payments = JSON.parse(raw);
    } catch(e) {}

    payments.push({
        id: `PAY-${Date.now()}`,
        poNumber: poNumber,
        amount: amount,
        paymentMethod: method,
        reference: ref,
        paymentDate: new Date().toISOString().slice(0, 10),
        remarks: 'Direct COD settlement at dock'
    });

    localStorage.setItem('rms_po_payments', JSON.stringify(payments));
}

/**
 * ----------------------------------------------------------------------------
 * TRACEABILITY MODAL FOR DIRECT LINES
 * ----------------------------------------------------------------------------
 */
function openTraceModalForLine(idx) {
    window.StockInStore.activeTraceLineIndex = idx;
    const it = window.StockInStore.directForm.items[idx];
    if (!it) return;

    document.getElementById('traceLotInput').value = it.lotNumber || '';
    document.getElementById('traceMfgInput').value = it.mfgDate || '';
    document.getElementById('traceExpInput').value = it.expiryDate || '';

    document.getElementById('traceModal')?.classList.add('active');
}

function closeTraceModal() {
    document.getElementById('traceModal')?.classList.remove('active');
}

function saveTraceModalData() {
    const idx = window.StockInStore.activeTraceLineIndex;
    if (idx === null || idx === undefined) return;
    const it = window.StockInStore.directForm.items[idx];
    if (!it) return;

    it.lotNumber = document.getElementById('traceLotInput')?.value || `LOT-${Date.now()}`;
    it.mfgDate = document.getElementById('traceMfgInput')?.value || '';
    it.expiryDate = document.getElementById('traceExpInput')?.value || '';

    closeTraceModal();
    showToast(`Traceability parameters saved for ${it.name}`, 'success');
}

function closeSupervisorOverrideModal() {
    document.getElementById('supervisorOverrideModal')?.classList.remove('active');
}

function openSupervisorOverrideModal() {
    document.getElementById('supervisorOverrideModal')?.classList.add('active');
}

function verifySupervisorPin() {
    const pin = document.getElementById('supervisorPinInput')?.value;
    if (pin === '1234') {
        closeSupervisorOverrideModal();
        document.getElementById('drawerOverToleranceBox').style.display = 'none';
        showToast('Supervisor override authorized', 'success');
    } else {
        alert('Invalid Supervisor PIN.');
    }
}

function viewPoSummaryModal(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;
    alert(`Purchase Order: ${po.poNumber}\nVendor: ${po.vendorTradeName || po.vendorName}\nStatus: ${po.status}\nItems: ${(po.items || []).length} items`);
}

function exportOrdersToCsv() {
    const pos = window.StockInStore.purchaseOrders || [];
    if (pos.length === 0) {
        alert('No orders to export.');
        return;
    }

    const headers = ['PO Number', 'Order Date', 'Vendor', 'Type', 'Delivery Location', 'Items Count', 'Status'];
    const rows = pos.map(p => [
        `"${p.poNumber}"`,
        `"${p.orderDate || ''}"`,
        `"${(p.vendorTradeName || p.vendorName || '').replace(/"/g, '""')}"`,
        `"${p.poType || 'vendor'}"`,
        `"${p.deliveryLocation || ''}"`,
        (p.items || []).length,
        `"${p.status}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `Purchase_Orders_Export_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * ----------------------------------------------------------------------------
 * UTILITY HELPERS & SEED DATA
 * ----------------------------------------------------------------------------
 */
function formatMoney(amount) {
    const num = parseFloat(amount) || 0;
    return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('grnToast');
    const toastMsg = document.getElementById('grnToastMsg');
    const toastIcon = document.getElementById('grnToastIcon');
    if (!toast) return;

    toastMsg.textContent = msg;
    toastIcon.className = type === 'success' ? 'ph ph-check-circle' : 'ph ph-info';
    toastIcon.style.color = type === 'success' ? '#10b981' : '#38bdf8';

    toast.style.display = 'flex';
    setTimeout(() => { toast.style.opacity = '1'; }, 10);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.style.display = 'none'; }, 250);
    }, 3500);
}

function getFallbackPurchaseOrders() {
    return [
        {
            poNumber: 'PO-2026-0103',
            poType: 'vendor',
            vendorName: 'EcoPack Solutions Philippines Corp.',
            vendorTradeName: 'EcoPack Packaging',
            orderDate: '2026-09-29',
            expectedDelivery: '2026-10-04',
            deliveryLocation: 'Central Warehouse - Dry Storage',
            paymentStatus: 'Paid in Full / Cash Out',
            status: 'Approved / Issued',
            items: [
                { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', category: 'Packaging', unit: 'Pc', quantity: 2000.0, receivedQuantity: 0, unitPrice: 4.50 },
                { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', category: 'Packaging', unit: 'Pc', quantity: 1000.0, receivedQuantity: 0, unitPrice: 8.20 }
            ]
        },
        {
            poNumber: 'PO-2026-0102',
            poType: 'wet_market',
            vendorName: 'Balintawak Central Wet Market Stall 12',
            vendorTradeName: 'Balintawak Fresh Meat Stall',
            orderDate: '2026-09-28',
            expectedDelivery: '2026-10-04',
            deliveryLocation: 'Commissary Butchery Cold Room',
            paymentStatus: 'Unpaid / On Credit Terms',
            status: 'Partially Received',
            items: [
                { sku: 'WET-BEEF', name: 'Fresh Beef Brisket Slab', category: 'Raw Ingredients', unit: 'Kg', quantity: 20.0, receivedQuantity: 10.0, unitPrice: 380.00 },
                { sku: 'WET-BONE', name: 'Beef Marrow Bones', category: 'Raw Ingredients', unit: 'Kg', quantity: 10.0, receivedQuantity: 0, unitPrice: 90.00 }
            ]
        }
    ];
}

function getFallbackProducts() {
    return [
        { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', category: 'Packaging', unit: 'Pc', stock: 500, costPrice: 4.50 },
        { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', category: 'Packaging', unit: 'Pc', stock: 250, costPrice: 8.20 },
        { sku: 'WET-BEEF', name: 'Fresh Beef Brisket Slab', category: 'Raw Ingredients', unit: 'Kg', stock: 15, costPrice: 380.00 },
        { sku: 'WET-BONE', name: 'Beef Marrow Bones', category: 'Raw Ingredients', unit: 'Kg', stock: 8, costPrice: 90.00 },
        { sku: 'RAW-DAIRY-01', name: 'Fresh Whole Milk Barista Blend', category: 'Dairy & Eggs', unit: 'Liter', stock: 80, costPrice: 95.00 }
    ];
}

function getFallbackVendors() {
    return [
        { id: 'VND-001', code: 'VND-001', legalName: 'San Miguel Foods Inc.', tradeName: 'San Miguel Foods', category: 'Raw Ingredients' },
        { id: 'VND-002', code: 'VND-002', legalName: 'Universal Robina Corp.', tradeName: 'URC Commercial', category: 'Dry Goods' },
        { id: 'VND-ECO-004', code: 'VND-ECO-004', legalName: 'EcoPack Solutions Philippines Corp.', tradeName: 'EcoPack Packaging', category: 'Packaging' },
        { id: 'WET-MKT-001', code: 'WET-MKT-001', legalName: 'Balintawak Central Wet Market Stall 12', tradeName: 'Balintawak Fresh Meat Stall', category: 'Wet Market' }
    ];
}

// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  initStockInModule,
  openPoItemsModal,
  closePoItemsModal,
  pushPoToReceivingBuilder,
  resetToAdHocBuilder,
  refreshStockInDataFromServer,
  switchGrnTab,
  updateHeaderKpis,
  renderPoMasterList,
  grnEsc,
  grnActionCell,
  editPurchaseOrder,
  printGrnSlip,
  getPaymentBadge,
  getOrderStatusBadge,
  renderPendingPoList,
  resetDirectReceivingForm,
  populateVendorDropdown,
  populateCatalogPicker,
  handleDirectVendorSelect,
  handleDirectSettlementChange,
  fillDirectFullBalance,
  handleDirectPaymentAmountChange,
  toggleDirectLandedCost,
  addPickedCatalogItem,
  renderDirectItemsTable,
  updateDirectUom,
  updateDirectLineQty,
  updateDirectLineCost,
  removeDirectLine,
  calculateDirectTotals,
  submitDirectReceivingOrder,
  openReceiveForPo,
  closeInspectionDrawer,
  calcDrawerTriad,
  renderDrawerPaymentLinkage,
  calcDrawerTotalValuation,
  commitInspectionReceipt,
  updateProductsStock,
  incrementProductStock,
  appendLedgerStockIn,
  logLedgerMovement,
  registerPaymentVoucher,
  openTraceModalForLine,
  closeTraceModal,
  saveTraceModalData,
  closeSupervisorOverrideModal,
  openSupervisorOverrideModal,
  verifySupervisorPin,
  viewPoSummaryModal,
  exportOrdersToCsv,
  formatMoney,
  showToast,
  getFallbackPurchaseOrders,
  getFallbackProducts,
  getFallbackVendors
});
