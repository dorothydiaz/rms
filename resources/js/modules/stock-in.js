/**
 * ============================================================================
 * RMS STOCK IN & RECEIVING (GRN) STATE ORCHESTRATOR
 * Industrial Dual-Mode ERP Workspace:
 * Mode A: Incoming Orders Queue (PO Tracker & Backlog Monitor)
 * Mode B: Stock Intake Sheet / GRN Builder (Cost-Free Physical Operations)
 * ============================================================================
 */

window.StockInStore = {
    purchaseOrders: [],
    vendors: [],
    products: [],
    goodsReceipts: [],
    warehouses: [],
    storageAreas: [],
    movementTypes: [],

    activeMode: 'queue', // 'queue' | 'builder'
    activePoNumber: null,

    queueWarehouseFilter: 'ALL',
    queueStatusFilter: 'ALL',
    queueSearchQuery: '',

    // Active Intake Sheet State (Mode B)
    intakeHeader: {
        poNumber: '',
        vendorId: '',
        vendorName: '',
        warehouseId: 'WH-MAIN',
        movementTypeId: 'WH-MAIN-IN',
        deliverySlip: '',
        carrier: '',
        receiverName: '',
        receivedAt: '',
    },
    intakeItems: [], // array of lines currently being received
};

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDisplayDate(val) {
    if (!val) return '—';
    try {
        const d = new Date(val);
        if (isNaN(d.getTime())) return String(val).slice(0, 10);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
        return String(val).slice(0, 10);
    }
}

function showGrnToast(message, type = 'success') {
    const container = document.getElementById('grnToastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `grn-toast ${type}`;
    const icon = type === 'danger' ? 'ph-warning-octagon' : (type === 'warning' ? 'ph-warning' : 'ph-check-circle');
    toast.innerHTML = `<i class="ph ${icon}" style="font-size: 16px;"></i><span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentNode) toast.remove();
    }, 3200);
}

// ============================================================================
// A. INCOMING ORDERS QUEUE COLUMNS & GEAR CUSTOMIZER (RFQ DIRECTORY PARITY)
// ============================================================================
const QUEUE_DIRECTORY_COLUMNS = [
    { id: 'po_reference', label: 'PO Reference', subLabel: 'Order ID & Source', default: true, lockVisible: true, defaultWidth: '135px', align: 'left' },
    { id: 'order_date', label: 'Order Date', subLabel: 'Issued Timestamp', default: true, lockVisible: false, defaultWidth: '115px', align: 'left' },
    { id: 'expected_delivery', label: 'Expected ETA', subLabel: 'Scheduled Dock Date', default: false, lockVisible: false, defaultWidth: '120px', align: 'left' },
    { id: 'target_vendor', label: 'Target Vendor', subLabel: 'Supplier Partner', default: true, lockVisible: false, defaultWidth: '210px', align: 'left' },
    { id: 'delivery_facility', label: 'Delivery Facility', subLabel: 'Receiving Hub', default: true, lockVisible: false, defaultWidth: '175px', align: 'left' },
    { id: 'lines', label: 'Lines', subLabel: 'SKU Count', default: true, lockVisible: false, defaultWidth: '75px', align: 'right' },
    { id: 'ordered', label: 'Ordered', subLabel: 'Contract Units', default: true, lockVisible: false, defaultWidth: '105px', align: 'right' },
    { id: 'received', label: 'Received', subLabel: 'Verified Dock Qty', default: true, lockVisible: false, defaultWidth: '105px', align: 'right' },
    { id: 'backlog', label: 'Backlog', subLabel: 'Pending Balance', default: true, lockVisible: false, defaultWidth: '105px', align: 'right' },
    { id: 'fulfillment_pct', label: 'Fulfillment %', subLabel: 'Dock Completion', default: false, lockVisible: false, defaultWidth: '115px', align: 'right' },
    { id: 'payment_terms', label: 'Payment Terms', subLabel: 'Commercial Terms', default: false, lockVisible: false, defaultWidth: '125px', align: 'left' },
    { id: 'order_cost', label: 'Order Cost (₱)', subLabel: 'PO Valuation (Hidden)', default: false, lockVisible: false, defaultWidth: '130px', align: 'right' },
    { id: 'status', label: 'Status', subLabel: 'Dock Intake State', default: true, lockVisible: false, defaultWidth: '145px', align: 'left' },
    { id: 'actions', label: 'Action', subLabel: 'Receive / Inspect', default: true, lockVisible: true, defaultWidth: '150px', align: 'right' }
];

let queueColOrder = QUEUE_DIRECTORY_COLUMNS.map(c => c.id);
let activeQueueColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_grn_queue_cols_v1');
        if (saved) {
            const parsed = JSON.parse(saved);
            const validIds = QUEUE_DIRECTORY_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.length >= 4 && parsed.every(id => validIds.includes(id))) {
                return parsed;
            }
        }
    } catch (e) {}
    return QUEUE_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
})();
let savedQueueColWidths = {};
let queueColSearchTerm = '';
let draggedQueueColId = null;

function saveQueueColsState() {
    try {
        localStorage.setItem('rms_grn_queue_cols_v1', JSON.stringify(activeQueueColIds));
    } catch (e) {}
}

// ============================================================================
// B. STOCK INTAKE SHEET (GRN) BUILDER COLUMNS (PO BUILDER PHASE 3 PARITY)
// Cost & Amount columns are included in the Gear Menu but HIDDEN by default!
// ============================================================================
const GRN_BUILDER_COLUMNS = [
    { id: 'index', label: '#', category: 'Core Stock Specs', lockVisible: true, default: true, align: 'center', width: '40px' },
    { id: 'item_name', label: 'Item Description & Specs', category: 'Core Stock Specs', lockVisible: true, default: true, align: 'left', minWidth: '240px' },
    { id: 'uom', label: 'UOM', category: 'Core Stock Specs', lockVisible: false, default: true, align: 'center', width: '90px' },
    { id: 'ordered_qty', label: 'Ordered', category: 'Receiving & Fulfillment', lockVisible: false, default: true, align: 'right', width: '95px' },
    { id: 'prev_recv_qty', label: 'Prev Recv', category: 'Receiving & Fulfillment', lockVisible: false, default: true, align: 'right', width: '95px' },
    { id: 'receive_now', label: 'Receive Now', category: 'Receiving & Fulfillment', lockVisible: true, default: true, align: 'center', width: '140px' },
    { id: 'balance_fulfillment', label: 'Pending Balance & Status', category: 'Receiving & Fulfillment', lockVisible: false, default: true, align: 'right', width: '150px' },
    // Commercial / Cost Columns — HIDDEN BY DEFAULT in Inventory Module (Toggleable in Gear Menu)
    { id: 'unit_cost', label: 'Unit Cost (₱)', category: 'Cost & Amount (Hidden)', lockVisible: false, default: false, align: 'right', width: '105px' },
    { id: 'line_total', label: 'Line Amount (₱)', category: 'Cost & Amount (Hidden)', lockVisible: false, default: false, align: 'right', width: '115px' }
];

const GRN_DEFAULT_STAGE_COLUMNS = GRN_BUILDER_COLUMNS.filter(c => c.default).map(c => c.id);
let activeGrnBuilderCols = [...GRN_DEFAULT_STAGE_COLUMNS];

// 1. Initial State Hydration
function initStockInModule() {
    const cfg = window.StockInConfig || {};
    const d = cfg.data || {};

    window.StockInStore.purchaseOrders = d.initialPurchaseOrders || [];
    window.StockInStore.vendors = d.initialVendors || [];
    window.StockInStore.products = d.initialProducts || [];
    window.StockInStore.goodsReceipts = d.initialReceipts || [];
    window.StockInStore.warehouses = d.initialWarehouses || [];
    window.StockInStore.storageAreas = d.initialStorageAreas || [];
    window.StockInStore.movementTypes = d.initialMovementTypes || [];

    // Populate Filters & Dropdowns
    populateWarehouseDropdowns();
    populateVendorDropdown();
    populatePoSelectDropdown();

    // Render KPIs & Queue Header + Rows
    renderKpis();
    renderQueueTableHeader();
    renderOrdersQueue();

    // Initialize Default Intake Sheet
    resetIntakeSheet(false);

    // Handle deep-link ?po=PO-XXXX from Purchase Order Builder Phase 3
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const deepLinkPo = urlParams.get('po');
        if (deepLinkPo) {
            const exists = window.StockInStore.purchaseOrders.some(p => p.po_number === deepLinkPo);
            if (exists) {
                openPoInIntakeBuilder(deepLinkPo);
            }
        }
    } catch (e) {}
}

// 2. Mode Switching (Queue vs Builder)
window.switchGrnMode = function(mode) {
    window.StockInStore.activeMode = mode;

    const btnQueue = document.getElementById('tabBtnOrdersQueue');
    const btnBuilder = document.getElementById('tabBtnIntakeBuilder');
    const panelQueue = document.getElementById('panelOrdersQueue');
    const panelBuilder = document.getElementById('panelIntakeBuilder');

    if (mode === 'queue') {
        if (btnQueue) btnQueue.classList.add('active');
        if (btnBuilder) btnBuilder.classList.remove('active');
        if (panelQueue) panelQueue.style.display = 'flex';
        if (panelBuilder) panelBuilder.style.display = 'none';
        renderOrdersQueue();
    } else {
        if (btnQueue) btnQueue.classList.remove('active');
        if (btnBuilder) btnBuilder.classList.add('active');
        if (panelQueue) panelQueue.style.display = 'none';
        if (panelBuilder) panelBuilder.style.display = 'flex';
        renderIntakeTable();
        updateFulfillmentProgress();
        updateLiveMockup();
    }
};

// 3. Dropdown Hydration
function populateWarehouseDropdowns() {
    const warehouses = window.StockInStore.warehouses;

    // Queue Filter
    const queueWhFilter = document.getElementById('queueWarehouseFilter');
    if (queueWhFilter) {
        let opts = '<option value="ALL">All Receiving Hubs</option>';
        warehouses.forEach(w => {
            opts += `<option value="${escapeHtml(w.id)}">${escapeHtml(w.code)} — ${escapeHtml(w.name)}</option>`;
        });
        queueWhFilter.innerHTML = opts;
    }

    // Intake Header Warehouse Select
    const intakeWhSelect = document.getElementById('intakeWarehouseSelect');
    if (intakeWhSelect) {
        let opts = '';
        warehouses.forEach(w => {
            opts += `<option value="${escapeHtml(w.id)}">${escapeHtml(w.code)} — ${escapeHtml(w.name)}</option>`;
        });
        intakeWhSelect.innerHTML = opts;
    }
}

function populateVendorDropdown() {
    const vendors = window.StockInStore.vendors;
    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (!vendorSelect) return;

    let opts = '<option value="">-- Choose Vendor Partner from Masterlist --</option>';
    vendors.forEach(v => {
        const code = v.vendor_code || v.id;
        const name = v.legal_name || v.name;
        opts += `<option value="${escapeHtml(code)}">${escapeHtml(code)} — ${escapeHtml(name)}</option>`;
    });
    vendorSelect.innerHTML = opts;
}

function populatePoSelectDropdown() {
    const orders = window.StockInStore.purchaseOrders;
    const poSelect = document.getElementById('intakePoSelect');
    if (!poSelect) return;

    let opts = '<option value="">-- Direct Inbound (Bypass PO) --</option>';
    orders.forEach(po => {
        const status = po.status || 'Issued';
        if (status !== 'Cancelled' && status !== 'Archived') {
            opts += `<option value="${escapeHtml(po.po_number)}">${escapeHtml(po.po_number)} — ${escapeHtml(po.vendor_name || 'Vendor')} (${escapeHtml(status)})</option>`;
        }
    });
    poSelect.innerHTML = opts;
}

// 4. Top KPI Cards
function renderKpis() {
    const orders = window.StockInStore.purchaseOrders;
    const receipts = window.StockInStore.goodsReceipts;
    const warehouses = window.StockInStore.warehouses;

    // Pending Orders count (Issued / Ready to receive + Partially Received)
    const pendingOrders = orders.filter(po => 
        ['Issued', 'Approved', 'Partially Received', 'Ready to Receive'].includes(po.status)
    );
    const kpiPending = document.getElementById('grnKpiPendingOrders');
    if (kpiPending) kpiPending.textContent = `${pendingOrders.length} Ready`;

    const badgeQueue = document.getElementById('badgeQueueCount');
    if (badgeQueue) badgeQueue.textContent = String(pendingOrders.length);

    // Units Received Today
    let todayUnits = 0;
    receipts.forEach(r => {
        (r.items || []).forEach(it => {
            todayUnits += Number(it.received_qty || 0);
        });
    });
    const kpiUnits = document.getElementById('grnKpiUnitsToday');
    if (kpiUnits) kpiUnits.textContent = todayUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Active Backorders
    const backorders = orders.filter(po => po.status === 'Partially Received');
    const kpiBackorders = document.getElementById('grnKpiActiveBackorders');
    if (kpiBackorders) kpiBackorders.textContent = `${backorders.length} Orders`;

    // Hubs
    const kpiHubs = document.getElementById('grnKpiHubsCount');
    if (kpiHubs) kpiHubs.textContent = `${warehouses.length} Facilities`;

    const kpiHubsList = document.getElementById('grnKpiHubsList');
    if (kpiHubsList) {
        kpiHubsList.textContent = warehouses.map(w => w.code).join(' · ') || 'Central, Cold-Chain & Branch Hubs';
    }
}

// 5. Mode A: Incoming Orders Queue & Stacked Header Gear Customizer
function getQueueColWidth(col) {
    return savedQueueColWidths[col.id] || col.defaultWidth || '120px';
}

window.renderQueueTableHeader = function() {
    const theadRow = document.getElementById('queueTableTheadRow');
    if (!theadRow) return;

    const visibleCols = queueColOrder
        .filter(id => activeQueueColIds.includes(id))
        .map(id => QUEUE_DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let thHtml = '';
    visibleCols.forEach(col => {
        const w = getQueueColWidth(col);
        const isRight = (col.align === 'right');
        const alignClass = isRight ? 'th-num' : 'th-left';

        if (col.id === 'actions') {
            thHtml += `
                <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width: ${w}; text-align: right; user-select: none;">
                    <div class="rfq-header-action-wrapper" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                        <div class="rfq-th-content" style="align-items: flex-end; text-align: right;">
                            <div class="rfq-th-title">${escapeHtml(col.label || 'Action')}</div>
                            <div class="rfq-th-sub">${escapeHtml(col.subLabel || 'Receive / Inspect')}</div>
                        </div>
                        <button type="button" class="rfq-th-settings-btn" id="btnQueueTableSettings" onclick="toggleQueueTableSettingsDropdown(event)" title="Customize Columns & Drag Sequence">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            thHtml += `
                <th class="rfq-th-stacked ${alignClass}" data-col-id="${col.id}"
                    style="width: ${w}; text-align: ${isRight ? 'right' : 'left'}; position: relative; user-select: none;">
                    <div style="display: flex; align-items: center; justify-content: ${isRight ? 'flex-end' : 'space-between'}; gap: 6px; min-width: 0; width: 100%;">
                        <div class="rfq-th-content" style="align-items: ${isRight ? 'flex-end' : 'flex-start'}; text-align: ${isRight ? 'right' : 'left'};">
                            <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                            ${col.subLabel ? `<div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>` : ''}
                        </div>
                    </div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = thHtml;
    renderQueueColChecklist();
};

function renderQueueColChecklist() {
    const listEl = document.getElementById('queueColCheckboxesList');
    if (!listEl) return;

    const query = (queueColSearchTerm || '').toLowerCase().trim();
    const orderedCols = queueColOrder.map(id => QUEUE_DIRECTORY_COLUMNS.find(c => c.id === id)).filter(Boolean);

    listEl.innerHTML = orderedCols
        .filter(col => {
            if (!query) return true;
            return col.label.toLowerCase().includes(query) ||
                   (col.subLabel && col.subLabel.toLowerCase().includes(query)) ||
                   col.id.toLowerCase().includes(query);
        })
        .map(col => {
            const isChecked = activeQueueColIds.includes(col.id);
            const isDisabled = col.lockVisible ? 'disabled' : '';
            const isDefault = col.default;

            return `
                <div class="rfq-dropdown-col-item" data-col-id="${col.id}" draggable="${col.id !== 'actions' ? 'true' : 'false'}"
                    ondragstart="handleQueueColDragStart(event, '${col.id}')"
                    ondragover="handleQueueColDragOver(event)"
                    ondrop="handleQueueColDrop(event, '${col.id}')">
                    ${col.id !== 'actions' ? `
                        <span class="rfq-drag-handle" title="Drag to reorder column sequence">
                            <i class="ph ph-dots-six-vertical"></i>
                        </span>
                    ` : `
                        <span style="width: 14px; display: inline-block;"></span>
                    `}
                    <label class="rfq-col-item-label" title="${escapeHtml(col.label)}">
                        <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                            <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleQueueColVisibility('${col.id}', this.checked)">
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <span class="rfq-col-name">${escapeHtml(col.label)}</span>
                                ${col.subLabel ? `<span class="rfq-col-subname">${escapeHtml(col.subLabel)}</span>` : ''}
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            ${isDefault ? '<span class="rfq-col-badge-default">Default</span>' : ''}
                            ${col.lockVisible ? '<span style="font-size: 0.65rem; color:#94a3b8; font-weight:600;">(Locked)</span>' : ''}
                        </div>
                    </label>
                </div>
            `;
        }).join('');

    const counter = document.getElementById('queueColActiveCounter');
    if (counter) {
        counter.textContent = `${activeQueueColIds.length} of ${QUEUE_DIRECTORY_COLUMNS.length} visible`;
    }
}

window.toggleQueueTableSettingsDropdown = function(event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('queueTableSettingsDropdown');
    if (!dd) return;

    const isOpen = dd.style.display === 'block';
    if (isOpen) {
        dd.style.display = 'none';
        return;
    }

    renderQueueColChecklist();
    dd.style.display = 'block';

    const trigger = event?.currentTarget || document.getElementById('btnQueueTableSettings') || document.getElementById('btnQueueColFilter');
    if (trigger) {
        const rect = trigger.getBoundingClientRect();
        const dropdownW = 290;
        const viewportW = window.innerWidth || 1280;
        let left = rect.right - dropdownW;
        if (left < 12) left = 12;
        if (left + dropdownW > viewportW - 12) left = Math.max(12, viewportW - dropdownW - 12);

        dd.style.position = 'fixed';
        dd.style.top = `${Math.round(rect.bottom + 6)}px`;
        dd.style.left = `${Math.round(left)}px`;
        dd.style.right = 'auto';
        dd.style.zIndex = '9999';
    }
};

window.toggleQueueColVisibility = function(colId, isChecked) {
    const col = QUEUE_DIRECTORY_COLUMNS.find(c => c.id === colId);
    if (!col || col.lockVisible) return;

    if (isChecked) {
        if (!activeQueueColIds.includes(colId)) activeQueueColIds.push(colId);
    } else {
        activeQueueColIds = activeQueueColIds.filter(id => id !== colId);
    }
    saveQueueColsState();
    renderQueueTableHeader();
    renderOrdersQueue();
};

window.resetQueueColumnDefaults = function() {
    queueColOrder = QUEUE_DIRECTORY_COLUMNS.map(c => c.id);
    activeQueueColIds = QUEUE_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    saveQueueColsState();
    renderQueueTableHeader();
    renderOrdersQueue();
    showGrnToast('Queue table columns reset to default');
};

window.showAllQueueColumns = function() {
    activeQueueColIds = QUEUE_DIRECTORY_COLUMNS.map(c => c.id);
    saveQueueColsState();
    renderQueueTableHeader();
    renderOrdersQueue();
    showGrnToast('Showing all Incoming Orders Queue columns');
};

window.resetQueueColumnWidths = function() {
    savedQueueColWidths = {};
    renderQueueTableHeader();
    renderOrdersQueue();
    showGrnToast('Column widths reset');
};

window.handleQueueColSearch = function(val) {
    queueColSearchTerm = val || '';
    renderQueueColChecklist();
};

window.handleQueueColDragStart = function(e, colId) {
    draggedQueueColId = colId;
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
};

window.handleQueueColDragOver = function(e) {
    e.preventDefault();
};

window.handleQueueColDrop = function(e, targetColId) {
    e.preventDefault();
    if (!draggedQueueColId || draggedQueueColId === targetColId || targetColId === 'actions') return;
    const fromIdx = queueColOrder.indexOf(draggedQueueColId);
    const toIdx = queueColOrder.indexOf(targetColId);
    if (fromIdx === -1 || toIdx === -1) return;
    queueColOrder.splice(fromIdx, 1);
    queueColOrder.splice(toIdx, 0, draggedQueueColId);
    draggedQueueColId = null;
    renderQueueTableHeader();
    renderOrdersQueue();
};

window.onQueueSearchInput = function(query) {
    window.StockInStore.queueSearchQuery = (query || '').toLowerCase().trim();
    renderOrdersQueue();
};

window.onQueueWarehouseFilterChange = function(wh) {
    window.StockInStore.queueWarehouseFilter = wh;
    renderOrdersQueue();
};

window.onQueueStatusFilterChange = function(st) {
    window.StockInStore.queueStatusFilter = st;
    renderOrdersQueue();
};

window.filterQueueByStatus = function(st) {
    window.StockInStore.queueStatusFilter = st;
    const sel = document.getElementById('queueStatusFilter');
    if (sel) sel.value = st;
    renderOrdersQueue();
};

window.renderOrdersQueue = function() {
    const tbody = document.getElementById('queueTableBody');
    if (!tbody) return;

    const { purchaseOrders, queueWarehouseFilter, queueStatusFilter, queueSearchQuery, warehouses } = window.StockInStore;

    const filtered = purchaseOrders.filter(po => {
        if (queueWarehouseFilter !== 'ALL') {
            const wh = warehouses.find(w => w.id === queueWarehouseFilter);
            const whName = wh ? wh.name.toLowerCase() : '';
            const poLoc = (po.delivery_location || '').toLowerCase();
            if (!poLoc.includes(whName) && !poLoc.includes(queueWarehouseFilter.toLowerCase())) {
                return false;
            }
        }

        const status = po.status || 'Issued';
        if (queueStatusFilter === 'READY') {
            if (!['Issued', 'Approved', 'Ready to Receive', 'Draft'].includes(status)) return false;
        } else if (queueStatusFilter === 'PARTIAL') {
            if (status !== 'Partially Received') return false;
        } else if (queueStatusFilter === 'FULFILLED') {
            if (status !== 'Fully Received') return false;
        }

        if (queueSearchQuery) {
            const poNum = (po.po_number || '').toLowerCase();
            const vendor = (po.vendor_name || '').toLowerCase();
            const loc = (po.delivery_location || '').toLowerCase();
            const hasItem = (po.items || []).some(it => 
                (it.sku || '').toLowerCase().includes(queueSearchQuery) || 
                (it.item_name || '').toLowerCase().includes(queueSearchQuery)
            );
            if (!poNum.includes(queueSearchQuery) && !vendor.includes(queueSearchQuery) && !loc.includes(queueSearchQuery) && !hasItem) {
                return false;
            }
        }

        return true;
    });

    const elVis = document.getElementById('queueCountVisible');
    const elTot = document.getElementById('queueCountTotal');
    if (elVis) elVis.textContent = filtered.length;
    if (elTot) elTot.textContent = purchaseOrders.length;

    const visibleCols = queueColOrder
        .filter(id => activeQueueColIds.includes(id))
        .map(id => QUEUE_DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${Math.max(1, visibleCols.length)}" style="text-align: center; padding: 36px 14px; color: #64748b;">
                    <i class="ph ph-tray" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                    <strong style="color: #334155; font-size: 13px;">No Incoming Orders Match Your Filter</strong>
                    <p style="font-size: 11.5px; margin: 4px 0 0 0;">Select another status or facility, or click "Direct Inbound" to receive spot stock.</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';

    filtered.forEach(po => {
        const items = po.items || [];
        let totalOrdered = 0;
        let totalReceived = 0;
        let totalAmount = 0;

        items.forEach(it => {
            const q = Number(it.quantity || 0);
            const r = Number(it.received_quantity || 0);
            const p = Number(it.unit_price || it.cost_price || 0);
            totalOrdered += q;
            totalReceived += r;
            totalAmount += (q * p);
        });

        const backlogUnits = Math.max(0, totalOrdered - totalReceived);
        const pct = totalOrdered > 0 ? Math.min(100, (totalReceived / totalOrdered) * 100) : 0;
        const status = po.status || 'Issued';

        let statusPillHtml = '';
        if (status === 'Fully Received') {
            statusPillHtml = `<span class="status-pill approved"><i class="ph ph-check-circle"></i> Fulfilled</span>`;
        } else if (status === 'Partially Received' || totalReceived > 0) {
            statusPillHtml = `<span class="status-pill sent"><i class="ph ph-dot"></i> Partial Backlog</span>`;
        } else {
            statusPillHtml = `<span class="status-pill awarded"><i class="ph ph-dot"></i> Ready to Receive</span>`;
        }

        const cellsHtml = visibleCols.map(col => {
            if (col.id === 'po_reference') {
                return `
                    <td style="font-family: monospace; font-weight: 800; color: #9333ea;">
                        <a href="javascript:void(0)" onclick="openPoInIntakeBuilder('${escapeHtml(po.po_number)}')" style="color: inherit; text-decoration: none;" title="Open in Stock Intake Sheet">
                            ${escapeHtml(po.po_number)}
                        </a>
                    </td>
                `;
            }
            if (col.id === 'order_date') {
                return `<td style="color: #64748b; font-size: 11.5px; white-space: nowrap;">${formatDisplayDate(po.order_date)}</td>`;
            }
            if (col.id === 'expected_delivery') {
                return `<td style="color: #475569; font-size: 11.5px; white-space: nowrap;">${formatDisplayDate(po.expected_delivery || po.order_date)}</td>`;
            }
            if (col.id === 'target_vendor') {
                return `<td style="font-weight: 600; color: #0f172a;">${escapeHtml(po.vendor_name || 'Vendor')}</td>`;
            }
            if (col.id === 'delivery_facility') {
                return `<td style="font-size: 11.5px; color: #475569;"><i class="ph ph-warehouse" style="color: #2563eb; margin-right: 3px;"></i>${escapeHtml(po.delivery_location || 'Central Commissary')}</td>`;
            }
            if (col.id === 'lines') {
                return `<td style="text-align: right; font-weight: 700;">${items.length}</td>`;
            }
            if (col.id === 'ordered') {
                return `<td style="text-align: right; font-weight: 700; color: #0f172a;">${totalOrdered.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            if (col.id === 'received') {
                return `<td style="text-align: right; font-weight: 700; color: #059669;">${totalReceived.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            if (col.id === 'backlog') {
                return `<td style="text-align: right; font-weight: 700; color: ${backlogUnits > 0 ? '#d97706' : '#64748b'};">${backlogUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            if (col.id === 'fulfillment_pct') {
                return `<td style="text-align: right; font-weight: 700; color: #7e22ce;">${pct.toFixed(1)}%</td>`;
            }
            if (col.id === 'payment_terms') {
                return `<td style="font-size: 11.5px; color: #475569;">${escapeHtml(po.payment_terms || 'Net 30 Days')}</td>`;
            }
            if (col.id === 'order_cost') {
                const costVal = Number(po.total_amount || totalAmount || 0);
                return `<td style="text-align: right; font-weight: 700; color: #334155;">₱${costVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            if (col.id === 'status') {
                return `<td style="text-align: left;">${statusPillHtml}</td>`;
            }
            if (col.id === 'actions') {
                return `
                    <td style="text-align: right;">
                        ${status !== 'Fully Received' ? `
                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openPoInIntakeBuilder('${escapeHtml(po.po_number)}')">
                            <i class="ph ph-tray-arrow-down"></i> Receive Intake
                        </button>` : `
                        <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Fulfilled</span>`}
                    </td>
                `;
            }
            return `<td>--</td>`;
        }).join('');

        html += `<tr>${cellsHtml}</tr>`;
    });

    tbody.innerHTML = html;
};

// ============================================================================
// 6. Mode B: Stock Intake Sheet (GRN) — Purchase Order Builder (Phase 3) Layout
// ============================================================================
function positionGrnBuilderSettingsDropdown() {
    const dd = document.getElementById('grnBuilderSettingsDropdown');
    const btn = document.getElementById('btnGrnBuilderSettings');
    if (!dd || !btn) return;

    const rect = btn.getBoundingClientRect();
    const dropdownWidth = 310;
    const viewportW = window.innerWidth || document.documentElement.clientWidth || 1280;
    const viewportH = window.innerHeight || document.documentElement.clientHeight || 800;

    let left = rect.right - dropdownWidth;
    if (left < 12) left = 12;
    if (left + dropdownWidth > viewportW - 12) left = Math.max(12, viewportW - dropdownWidth - 12);

    let top = rect.bottom + 6;
    const maxDropdownH = 360;
    if (top + maxDropdownH > viewportH - 12 && rect.top > maxDropdownH) {
        top = Math.max(12, rect.top - maxDropdownH - 6);
    }

    dd.style.position = 'fixed';
    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.right = 'auto';
    dd.style.zIndex = '9999';
}

window.renderGrnBuilderTableHeader = function() {
    const theadRow = document.getElementById('intakeItemsTheadRow');
    if (!theadRow) return;

    const wasOpen = document.getElementById('grnBuilderSettingsDropdown')?.style.display === 'block';
    const visibleCols = GRN_BUILDER_COLUMNS.filter(c => c.lockVisible || activeGrnBuilderCols.includes(c.id));

    let html = visibleCols.map(col => {
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');
        const wStyle = col.width ? `width: ${col.width};` : (col.minWidth ? `min-width: ${col.minWidth};` : '');
        return `<th class="${alignClass}" style="${wStyle}">${escapeHtml(col.label)}</th>`;
    }).join('');

    html += `
        <th class="th-center" style="width: 78px; position: relative;">
            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; position: relative;">
                <span>Action</span>
                <button type="button" class="rfq-th-settings-btn" id="btnGrnBuilderSettings" onclick="toggleGrnBuilderSettingsDropdown(event)" title="Customize Stock Intake Sheet Columns (Cost Hidden by Default)">
                    <i class="ph ph-gear"></i>
                </button>
            </div>
            <div id="grnBuilderSettingsDropdown" class="po-builder-settings-dropdown rfq-builder-settings-dropdown grn-settings-dropdown" style="display: ${wasOpen ? 'block' : 'none'}; text-align: left;" onclick="event.stopPropagation()">
                <div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 800; font-size: 12px; color: #0f172a;"><i class="ph ph-gear-six" style="color: #9333ea;"></i> Intake Sheet Columns</span>
                    <span style="font-size: 10.5px; font-weight: 700; color: #7e22ce; background: #f3e8ff; padding: 2px 7px; border-radius: 8px;">${visibleCols.length} of ${GRN_BUILDER_COLUMNS.length}</span>
                </div>
                <div style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; gap: 6px; background: #ffffff;">
                    <button type="button" class="inv-col-quick-link" onclick="resetGrnBuilderColumnsToStage()" style="font-size: 11px; color: #7e22ce; font-weight: 700; background: none; border: none; cursor: pointer;">Stocks Default (No Cost)</button>
                    <button type="button" class="inv-col-quick-link" onclick="showAllGrnBuilderColumns()" style="font-size: 11px; color: #475569; font-weight: 600; background: none; border: none; cursor: pointer;">Show All (+ Cost)</button>
                </div>
                <div style="max-height: 270px; overflow-y: auto; padding: 6px 10px; background: #ffffff;">
                    ${GRN_BUILDER_COLUMNS.map(col => {
                        const checked = col.lockVisible || activeGrnBuilderCols.includes(col.id);
                        const isCostCol = col.category.includes('Cost');
                        return `
                            <label style="display: flex; align-items: center; justify-content: space-between; padding: 4px 6px; font-size: 11.5px; cursor: ${col.lockVisible ? 'default' : 'pointer'}; border-radius: 6px;">
                                <span style="display: flex; align-items: center; gap: 6px; color: ${col.lockVisible ? '#94a3b8' : '#1e293b'}; font-weight: ${checked ? '600' : '400'};">
                                    <input type="checkbox" ${checked ? 'checked' : ''} ${col.lockVisible ? 'disabled' : ''} onchange="toggleGrnBuilderColumn('${col.id}', this.checked)" style="accent-color: #9333ea;">
                                    <span>${escapeHtml(col.label)}</span>
                                </span>
                                <span style="font-size: 9.5px; font-weight: 600; padding: 1px 5px; border-radius: 4px; background: ${isCostCol ? '#fef3c7' : '#f1f5f9'}; color: ${isCostCol ? '#b45309' : '#64748b'};">${isCostCol ? 'Cost (Opt)' : escapeHtml(col.category.split(' ')[0])}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            </div>
        </th>
    `;

    theadRow.innerHTML = html;
    if (wasOpen) {
        requestAnimationFrame(() => positionGrnBuilderSettingsDropdown());
    }
};

window.toggleGrnBuilderSettingsDropdown = function(event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('grnBuilderSettingsDropdown');
    if (!dd) return;
    const isHidden = dd.style.display === 'none' || !dd.style.display;
    if (isHidden) {
        dd.style.display = 'block';
        positionGrnBuilderSettingsDropdown();
    } else {
        dd.style.display = 'none';
    }
};

window.toggleGrnBuilderColumn = function(colId, isVisible) {
    if (isVisible) {
        if (!activeGrnBuilderCols.includes(colId)) activeGrnBuilderCols.push(colId);
    } else {
        activeGrnBuilderCols = activeGrnBuilderCols.filter(c => c !== colId);
    }
    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
};

window.showAllGrnBuilderColumns = function() {
    activeGrnBuilderCols = GRN_BUILDER_COLUMNS.map(c => c.id);
    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
    showGrnToast('Showing all columns including Unit Cost & Amount');
};

window.resetGrnBuilderColumnsToStage = function() {
    activeGrnBuilderCols = [...GRN_DEFAULT_STAGE_COLUMNS];
    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
    showGrnToast('Reset to Stocks Default (Cost & Amount hidden)');
};

window.copyGrnReference = function() {
    const docNo = document.getElementById('intakeDocNoDisplay')?.textContent?.trim() || 'GRN-DIRECT';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(docNo).then(() => showGrnToast(`Copied ${docNo} to clipboard`));
    } else {
        showGrnToast(`Reference: ${docNo}`);
    }
};

window.openActivePoInPurchaseModule = function() {
    const baseUrl = window.StockInConfig?.routes?.purchaseOrders || '/purchase/purchase-orders';
    const poNum = window.StockInStore.activePoNumber || document.getElementById('intakePoSelect')?.value || '';
    if (poNum) {
        window.location.href = `${baseUrl}?po=${encodeURIComponent(poNum)}`;
    } else {
        window.location.href = baseUrl;
    }
};


// 6. Draft Persistence (Frontend Rule 16)
let draftSaveTimer = null;
function scheduleDraftSave() {
    clearTimeout(draftSaveTimer);
    draftSaveTimer = setTimeout(saveGrnDraft, 500);
}

function saveGrnDraft() {
    try {
        const poNumber = window.StockInStore.activePoNumber || document.getElementById('intakePoSelect')?.value || '';
        const vendorId = document.getElementById('intakeVendorSelect')?.value || '';
        const movementTypeId = document.getElementById('intakeMovementTypeSelect')?.value || '';
        const expectedDelivery = document.getElementById('intakeExpectedDelivery')?.value || '';
        const receivedAt = document.getElementById('intakeReceivedAt')?.value || '';
        const sourceLocation = document.getElementById('intakeSourceLocationSelect')?.value || '';
        const destinationLocation = document.getElementById('intakeDestinationLocationSelect')?.value || '';
        const deliverySlip = document.getElementById('intakeDeliverySlip')?.value || '';
        const deliveryNotes = document.getElementById('intakeDeliveryNotes')?.value || '';

        const items = window.StockInStore.intakeItems || [];
        if (items.length === 0 && !poNumber && !vendorId) {
            return;
        }

        const draft = {
            savedAt: new Date().toISOString(),
            poNumber,
            vendorId,
            movementTypeId,
            expectedDelivery,
            receivedAt,
            sourceLocation,
            destinationLocation,
            deliverySlip,
            deliveryNotes,
            items,
        };

        localStorage.setItem('rms_grn_draft_v1', JSON.stringify(draft));

        const ind = document.getElementById('grnDraftIndicator');
        const indTxt = document.getElementById('grnDraftIndicatorText');
        if (ind && indTxt) {
            ind.hidden = false;
            indTxt.textContent = 'Draft autosaved';
        }
    } catch (e) {
        console.warn('Could not save GRN draft:', e);
    }
}

function checkGrnDraft() {
    try {
        const raw = localStorage.getItem('rms_grn_draft_v1');
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (draft && draft.items && draft.items.length > 0) {
            const banner = document.getElementById('grnResumeBanner');
            const text = document.getElementById('grnResumeBannerText');
            if (banner && text) {
                text.textContent = `Unsaved draft for ${draft.poNumber || 'Direct Inbound'} (${draft.items.length} lines) found.`;
                banner.hidden = false;
            }
        }
    } catch (e) {}
}

window.resumeGrnDraft = function() {
    try {
        const raw = localStorage.getItem('rms_grn_draft_v1');
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft) return;

        window.StockInStore.activePoNumber = draft.poNumber || null;
        if (draft.poNumber) {
            const poSelect = document.getElementById('intakePoSelect');
            if (poSelect) poSelect.value = draft.poNumber;
        }
        if (draft.vendorId) {
            const vSelect = document.getElementById('intakeVendorSelect');
            if (vSelect) vSelect.value = draft.vendorId;
        }
        if (draft.movementTypeId) {
            const mtSelect = document.getElementById('intakeMovementTypeSelect');
            if (mtSelect) mtSelect.value = draft.movementTypeId;
        }
        if (draft.expectedDelivery) {
            const eta = document.getElementById('intakeExpectedDelivery');
            if (eta) eta.value = draft.expectedDelivery;
        }
        if (draft.receivedAt) {
            const rcv = document.getElementById('intakeReceivedAt');
            if (rcv) rcv.value = draft.receivedAt;
        }
        if (draft.deliverySlip) {
            const slip = document.getElementById('intakeDeliverySlip');
            if (slip) slip.value = draft.deliverySlip;
        }
        if (draft.deliveryNotes) {
            const notes = document.getElementById('intakeDeliveryNotes');
            if (notes) notes.value = draft.deliveryNotes;
        }

        window.StockInStore.intakeItems = draft.items || [];

        const banner = document.getElementById('grnResumeBanner');
        if (banner) banner.hidden = true;

        switchGrnMode('builder');
        renderIntakeTable();
        updateFulfillmentProgress();
        updateLiveMockup();
        updateTimelinessBadge();
        showGrnToast('Resumed intake draft successfully');
    } catch (e) {
        console.error('Draft resume failed', e);
    }
};

window.discardGrnDraft = function() {
    localStorage.removeItem('rms_grn_draft_v1');
    const banner = document.getElementById('grnResumeBanner');
    if (banner) banner.hidden = true;
    const indicator = document.getElementById('grnDraftIndicator');
    if (indicator) indicator.hidden = true;
    showGrnToast('Draft discarded');
};

// Barcode / SKU Scan Keydown Listener
window.onGrnScanKeydown = function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const input = document.getElementById('grnScanInput');
        const query = (input?.value || '').trim();
        if (!query) return;

        const scanBar = input.closest('.grn-scan-bar');
        const qUpper = query.toUpperCase();

        // 1. Check in active intake items
        const foundIdx = window.StockInStore.intakeItems.findIndex(it => 
            (it.sku && it.sku.toUpperCase() === qUpper) ||
            (it.barcode && it.barcode.toUpperCase() === qUpper) ||
            (it.name && it.name.toUpperCase().includes(qUpper))
        );

        if (foundIdx !== -1) {
            const item = window.StockInStore.intakeItems[foundIdx];
            item.receive_now = Number(item.receive_now || 0) + 1;
            renderIntakeTable();
            updateFulfillmentProgress();
            updateLiveMockup();
            flashTableRow(foundIdx);
            showGrnToast(`Scanned: +1 unit for ${item.sku}`);
            if (input) input.value = '';
            scheduleDraftSave();
            return;
        }

        // 2. Check in product catalog
        const matchedProd = window.StockInStore.products.find(p => 
            (p.sku && p.sku.toUpperCase() === qUpper) ||
            (p.item_code && p.item_code.toUpperCase() === qUpper) ||
            (p.barcode && p.barcode.toUpperCase() === qUpper) ||
            (p.name && p.name.toUpperCase().includes(qUpper))
        );

        if (matchedProd) {
            const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
            const nextIdx = window.StockInStore.intakeItems.length + 1;
            window.StockInStore.intakeItems.push({
                sku: matchedProd.sku || matchedProd.item_code || `SPOT-${nextIdx}`,
                name: matchedProd.name,
                specs: matchedProd.description || matchedProd.specs || '',
                uom: matchedProd.uom || 'PCS',
                ordered_qty: 0,
                prev_recv_qty: 0,
                receive_now: 1,
                balance: 0,
                unit_cost: Number(matchedProd.cost_price || 0),
                vat_type: '12% VAT',
                lot_number: `LOT-${dateStr}-${nextIdx.toString().padStart(2, '0')}`,
                expiry_date: '',
                mfg_date: '',
                notes: '',
                is_adhoc: true,
            });
            renderIntakeTable();
            updateFulfillmentProgress();
            updateLiveMockup();
            flashTableRow(window.StockInStore.intakeItems.length - 1);
            showGrnToast(`Added ${matchedProd.name} from item master`);
            if (input) input.value = '';
            scheduleDraftSave();
            return;
        }

        // 3. Not found
        if (scanBar) {
            scanBar.classList.add('is-error');
            setTimeout(() => scanBar.classList.remove('is-error'), 400);
        }
        showGrnToast(`Item "${query}" not found in sheet or catalog`, 'warning');
    }
};

function flashTableRow(idx) {
    const row = document.querySelector(`tr[data-row-idx="${idx}"]`);
    if (row) {
        row.classList.remove('grn-row-flash');
        void row.offsetWidth;
        row.classList.add('grn-row-flash');
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Timeliness Badge & Header Change Listener
window.onIntakeHeaderChanged = function() {
    updateTimelinessBadge();
    updateLiveMockup();
    scheduleDraftSave();
};

function updateTimelinessBadge() {
    const etaVal = document.getElementById('intakeExpectedDelivery')?.value;
    const recvVal = document.getElementById('intakeReceivedAt')?.value;
    const badge = document.getElementById('grnTimelinessBadge');
    if (!badge) return;

    if (!etaVal || !recvVal) {
        badge.textContent = 'Dock Stamp';
        badge.setAttribute('data-state', 'none');
        return;
    }

    const etaDate = new Date(etaVal + 'T23:59:59');
    const recvDate = new Date(recvVal);

    if (isNaN(etaDate.getTime()) || isNaN(recvDate.getTime())) {
        badge.textContent = 'Dock Stamp';
        badge.setAttribute('data-state', 'none');
        return;
    }

    const diffDays = Math.round((recvDate - etaDate) / (1000 * 60 * 60 * 24));
    if (diffDays <= 0) {
        badge.textContent = 'On-time';
        badge.setAttribute('data-state', 'ontime');
    } else {
        badge.textContent = `+${diffDays}d Late`;
        badge.setAttribute('data-state', 'late');
    }
}

// Route location listeners
window.onIntakeSourceLocationChanged = function(val) {
    updateLiveMockup();
    scheduleDraftSave();
};

window.onIntakeDestinationLocationChanged = function(val) {
    const mtSelect = document.getElementById('intakeMovementTypeSelect');
    const mt = window.StockInStore.movementTypes.find(m => m.id === mtSelect?.value);
    renderGrnChevronPipeline(mt);
    updateLiveMockup();
    scheduleDraftSave();
};

window.openPoInIntakeBuilder = function(poNumber) {
    const po = window.StockInStore.purchaseOrders.find(p => p.po_number === poNumber);
    if (!po) return;

    window.StockInStore.activePoNumber = poNumber;

    const poSelect = document.getElementById('intakePoSelect');
    if (poSelect) poSelect.value = po.po_number;

    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (vendorSelect) {
        vendorSelect.value = po.vendor_id || '';
        if (!vendorSelect.value && po.vendor_name) {
            vendorSelect.innerHTML += `<option value="${escapeHtml(po.vendor_id || 'VEN')}" selected>${escapeHtml(po.vendor_name)}</option>`;
        }
    }

    const etaInput = document.getElementById('intakeExpectedDelivery');
    if (etaInput) {
        etaInput.value = po.expected_delivery_date || po.delivery_date || '';
    }

    let resolvedWhId = 'WH-MAIN';
    const locLower = (po.delivery_location || '').toLowerCase();
    const matchedWh = window.StockInStore.warehouses.find(w => 
        locLower.includes(w.id.toLowerCase()) || locLower.includes(w.name.toLowerCase()) || locLower.includes(w.code.toLowerCase())
    );
    if (matchedWh) resolvedWhId = matchedWh.id;

    const whSelect = document.getElementById('intakeWarehouseSelect');
    if (whSelect) whSelect.value = resolvedWhId;

    syncMovementTypesForWarehouse(resolvedWhId);

    window.StockInStore.intakeItems = (po.items || []).map((it, idx) => {
        const ordered = Number(it.quantity || 0);
        const prev = Number(it.received_quantity || 0);
        const remaining = Math.max(0, ordered - prev);
        const unitCost = Number(it.unit_price || it.cost_price || 0);

        const matchedProd = window.StockInStore.products.find(p => 
            p.sku === it.sku || p.item_code === it.sku || p.id === it.inventory_item_id
        );
        const cleanName = (it.item_name && it.item_name !== it.sku) 
            ? it.item_name 
            : (matchedProd?.name || it.item_name || it.sku);
        const cleanSpecs = it.specs || matchedProd?.description || matchedProd?.specs || '';

        return {
            sku: it.sku || `SKU-${idx + 1}`,
            name: cleanName,
            specs: cleanSpecs,
            uom: it.uom || matchedProd?.uom || 'PCS',
            ordered_qty: ordered,
            prev_recv_qty: prev,
            receive_now: remaining,
            balance: 0,
            unit_cost: unitCost,
            vat_type: '12% VAT',
            lot_number: `LOT-${new Date().toISOString().slice(2, 10).replace(/-/g, '')}-${(idx + 1).toString().padStart(2, '0')}`,
            expiry_date: '',
            mfg_date: '',
            notes: '',
            is_adhoc: false,
        };
    });

    const docDisplay = document.getElementById('intakeDocNoDisplay');
    if (docDisplay) docDisplay.textContent = `GRN-${po.po_number.replace(/^PO-?/, '')}`;

    const modeBadge = document.getElementById('intakeModeBadge');
    const modeText = document.getElementById('intakeModeText');
    if (modeBadge && modeText) {
        modeBadge.className = 'status-pill awarded grn-mode-pill';
        modeText.textContent = `PO: ${po.po_number}`;
    }

    updateTimelinessBadge();
    switchGrnMode('builder');
    showGrnToast(`Loaded ${window.StockInStore.intakeItems.length} lines from ${po.po_number}`);
    scheduleDraftSave();
};

window.startDirectBypassIntake = function() {
    resetIntakeSheet(true);
    switchGrnMode('builder');
    showGrnToast('Started Direct Inbound Receiving (Bypass PO)');
};

window.resetIntakeSheet = function(addBlankRow = true) {
    window.StockInStore.activePoNumber = null;

    const poSelect = document.getElementById('intakePoSelect');
    if (poSelect) poSelect.value = '';

    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (vendorSelect) vendorSelect.selectedIndex = 0;

    const whSelect = document.getElementById('intakeWarehouseSelect');
    const defaultWh = window.StockInStore.warehouses[0]?.id || 'WH-MAIN';
    if (whSelect) whSelect.value = defaultWh;

    syncMovementTypesForWarehouse(defaultWh);

    const slipInput = document.getElementById('intakeDeliverySlip');
    if (slipInput) slipInput.value = '';

    const notesInput = document.getElementById('intakeDeliveryNotes');
    if (notesInput) notesInput.value = '';

    const docDisplay = document.getElementById('intakeDocNoDisplay');
    if (docDisplay) docDisplay.textContent = 'GRN-DIRECT';

    const modeBadge = document.getElementById('intakeModeBadge');
    const modeText = document.getElementById('intakeModeText');
    if (modeBadge && modeText) {
        modeBadge.className = 'status-pill sent grn-mode-pill';
        modeText.textContent = 'Direct Spot Inbound';
    }

    window.StockInStore.intakeItems = [];
    if (addBlankRow) {
        addAdHocIntakeRow();
    } else {
        renderIntakeTable();
        updateFulfillmentProgress();
        updateLiveMockup();
    }
    updateTimelinessBadge();
};

window.onIntakeWarehouseChanged = function(whId) {
    syncMovementTypesForWarehouse(whId);
    renderIntakeTable();
    updateLiveMockup();
    showGrnToast(`Warehouse switched to ${whId}. Route options updated.`);
    scheduleDraftSave();
};

function syncMovementTypesForWarehouse(whId) {
    const mtSelect = document.getElementById('intakeMovementTypeSelect');
    if (!mtSelect) return;

    const allTypes = window.StockInStore.movementTypes;
    const matched = allTypes.filter(mt => 
        (mt.warehouse_id === whId || !mt.warehouse_id) && mt.operation_kind === 'Inbound Receipt' && mt.is_active !== false
    );

    let opts = '';
    const available = matched.length > 0 ? matched : allTypes.filter(mt => mt.operation_kind === 'Inbound Receipt');
    available.forEach(mt => {
        opts += `<option value="${escapeHtml(mt.id)}">${escapeHtml(mt.code)} — ${escapeHtml(mt.name)}</option>`;
    });

    mtSelect.innerHTML = opts;
    if (available.length > 0) {
        mtSelect.value = available[0].id;
        updateRouteIndicator(available[0]);
        populateRouteLocationDropdowns(available[0]);
        renderGrnChevronPipeline(available[0]);
    }
}

window.onIntakeMovementTypeChanged = function(mtId) {
    const mt = window.StockInStore.movementTypes.find(m => m.id === mtId);
    if (mt) {
        updateRouteIndicator(mt);
        populateRouteLocationDropdowns(mt);
        renderGrnChevronPipeline(mt);
    }
    updateLiveMockup();
    scheduleDraftSave();
};

function updateRouteIndicator(movementType) {
    const badge = document.getElementById('routePresetBadge');
    if (badge && movementType) {
        badge.textContent = movementType.route_preset || `${(movementType.steps || []).length}-Step Route`;
    }
}

// Dynamic Inbound Chevron Pipeline synced with current destination location
window.renderGrnChevronPipeline = function(movementType) {
    const pipeline = document.getElementById('grnLifecycleProgressBar');
    if (!pipeline) return;

    const mt = movementType || window.StockInStore.movementTypes[0] || {};
    const steps = mt.steps || [];
    const hasQc = steps.some(s => {
        const text = ((s.name || '') + ' ' + (s.to_area || '') + ' ' + (mt.route_preset || '')).toLowerCase();
        return text.includes('qa') || text.includes('qc') || text.includes('quality') || text.includes('inspect');
    });

    const destLoc = (document.getElementById('intakeDestinationLocationSelect')?.value || '').toLowerCase();
    const isQcActive = destLoc.includes('qa') || destLoc.includes('qc') || destLoc.includes('inspect');

    let stageIdx = 1;
    let html = `
        <div class="po-chevron-step rfq-chevron-step is-done" id="grnStepIndicatorReceiving">
            <div class="rfq-chevron-badge"><i class="ph ph-check"></i></div>
            <div class="rfq-chevron-info">
                <span class="rfq-chevron-title">Receiving</span>
                <span class="rfq-chevron-desc">${escapeHtml(steps[0]?.to_area || 'Dock Airlock')}</span>
            </div>
        </div>
    `;

    if (hasQc) {
        const qcDone = !isQcActive && destLoc !== '';
        const qcClass = isQcActive ? 'is-active stage-approval' : (qcDone ? 'is-done' : 'stage-approval');
        const qcIcon = qcDone ? 'ph-check' : 'ph-seal-check';
        html += `
            <div class="po-chevron-step rfq-chevron-step ${qcClass}" id="grnStepIndicatorQc">
                <div class="rfq-chevron-badge"><i class="ph ${qcIcon}"></i>${!qcDone ? `<span class="rfq-chevron-num">${++stageIdx}</span>` : ''}</div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">Quality Check</span>
                    <span class="rfq-chevron-desc">Inbound QA Bench</span>
                </div>
            </div>
        `;
    }

    const storageActive = !isQcActive;
    const storageClass = storageActive ? 'is-active stage-draft' : 'stage-draft';
    html += `
        <div class="po-chevron-step rfq-chevron-step ${storageClass}" id="grnStepIndicatorStocks">
            <div class="rfq-chevron-badge"><i class="ph ph-warehouse"></i><span class="rfq-chevron-num">${++stageIdx}</span></div>
            <div class="rfq-chevron-info">
                <span class="rfq-chevron-title">Storage</span>
                <span class="rfq-chevron-desc">${escapeHtml(destLoc || 'Stock Zone A')}</span>
            </div>
        </div>
        <div class="po-chevron-step rfq-chevron-step stage-status" id="grnStepIndicatorStatus">
            <div class="rfq-chevron-badge"><i class="ph ph-check-circle"></i><span class="rfq-chevron-num">${++stageIdx}</span></div>
            <div class="rfq-chevron-info">
                <span class="rfq-chevron-title" id="grnStepStatusTitle">Status</span>
                <span class="rfq-chevron-desc" id="grnStepStatusDesc">Ready to Commit</span>
            </div>
        </div>
    `;

    pipeline.innerHTML = html;

    const stageBadge = document.getElementById('grnRouteStageBadge');
    if (stageBadge) {
        stageBadge.textContent = isQcActive ? 'Route: Quality Check' : 'Route: Storage Putaway';
        stageBadge.className = isQcActive ? 'grn-label-badge is-accent' : 'grn-label-badge is-success';
    }
};

window.populateRouteLocationDropdowns = function(movementType) {
    const srcSelect = document.getElementById('intakeSourceLocationSelect');
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    if (!srcSelect || !destSelect) return;

    const whId = document.getElementById('intakeWarehouseSelect')?.value || 'WH-MAIN';
    const whAreas = window.StockInStore.storageAreas.filter(a => a.warehouse_id === whId);

    const mt = movementType || window.StockInStore.movementTypes[0] || {};
    const steps = mt.steps || [];

    const sourceOrigins = ['Supplier / Vendor Origin (Boundary)'];
    const destTargets = [];

    steps.forEach(s => {
        if (s.from_area && !sourceOrigins.includes(s.from_area)) {
            sourceOrigins.push(s.from_area);
        }
        if (s.to_area && !destTargets.includes(s.to_area)) {
            destTargets.push(s.to_area);
        }
    });

    whAreas.forEach(a => {
        if (!destTargets.includes(a.name)) {
            destTargets.push(a.name);
        }
    });

    srcSelect.innerHTML = sourceOrigins.map((src, i) => 
        `<option value="${escapeHtml(src)}" ${i === 0 ? 'selected' : ''}>${escapeHtml(src)}</option>`
    ).join('');
    srcSelect.disabled = sourceOrigins.length <= 1;

    const isQcRoute = steps.some(s => {
        const text = ((s.name || '') + ' ' + (s.to_area || '')).toLowerCase();
        return text.includes('qa') || text.includes('qc') || text.includes('quality') || text.includes('inspect');
    });

    const defaultDest = isQcRoute 
        ? (destTargets.find(d => /qa|qc|inspect|check/i.test(d)) || destTargets[0])
        : (destTargets.find(d => /stock|store|freezer|chiller|dry/i.test(d)) || destTargets[destTargets.length - 1] || destTargets[0]);

    destSelect.innerHTML = destTargets.map(dest => 
        `<option value="${escapeHtml(dest)}" ${dest === defaultDest ? 'selected' : ''}>${escapeHtml(dest)}</option>`
    ).join('');
};

function renderExpiryChip(dateStr) {
    if (!dateStr) return '';
    const expDate = new Date(dateStr);
    if (isNaN(expDate.getTime())) return `<span class="grn-exp-chip"><i class="ph ph-calendar"></i> Exp: ${escapeHtml(dateStr)}</span>`;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffDays = Math.round((expDate - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return `<span class="grn-exp-chip is-expired"><i class="ph ph-warning-circle"></i> Expired (${escapeHtml(dateStr)})</span>`;
    } else if (diffDays <= 30) {
        return `<span class="grn-exp-chip is-soon"><i class="ph ph-clock"></i> Exp: ${escapeHtml(dateStr)} (${diffDays}d)</span>`;
    }
    return `<span class="grn-exp-chip"><i class="ph ph-calendar"></i> Exp: ${escapeHtml(dateStr)}</span>`;
}

// Lot & Expiry Specification Modal
window.openLotExpiryModal = function(idx) {
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;

    const rowIdxInput = document.getElementById('lotModalRowIdx');
    const titleEl = document.getElementById('lotModalItemTitle');
    const lotInput = document.getElementById('lotModalLotNumber');
    const expInput = document.getElementById('lotModalExpiryDate');
    const mfgInput = document.getElementById('lotModalMfgDate');
    const notesInput = document.getElementById('lotModalNotes');
    const errEl = document.getElementById('lotModalError');

    if (rowIdxInput) rowIdxInput.value = String(idx);
    if (titleEl) titleEl.textContent = `${item.sku} • ${item.name}`;
    if (lotInput) lotInput.value = item.lot_number || '';
    if (expInput) expInput.value = item.expiry_date || '';
    if (mfgInput) mfgInput.value = item.mfg_date || '';
    if (notesInput) notesInput.value = item.notes || '';
    if (errEl) errEl.hidden = true;

    const modal = document.getElementById('grnLotExpiryModal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => lotInput?.focus(), 50);
    }
};

window.closeLotExpiryModal = function() {
    const modal = document.getElementById('grnLotExpiryModal');
    if (modal) modal.style.display = 'none';
};

window.closeLotExpiryModalOnBackdrop = function(e) {
    if (e.target && e.target.id === 'grnLotExpiryModal') {
        closeLotExpiryModal();
    }
};

window.onLotModalKeydown = function(e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        closeLotExpiryModal();
    } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        saveLotExpiryModal();
    }
};

window.generateRandomLotNumber = function() {
    const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
    const randSuffix = Math.floor(1000 + Math.random() * 9000);
    const lotInput = document.getElementById('lotModalLotNumber');
    if (lotInput) lotInput.value = `LOT-${dateStr}-${randSuffix}`;
};

window.saveLotExpiryModal = function() {
    const idx = parseInt(document.getElementById('lotModalRowIdx')?.value || '-1', 10);
    if (idx < 0 || !window.StockInStore.intakeItems[idx]) {
        closeLotExpiryModal();
        return;
    }

    const item = window.StockInStore.intakeItems[idx];
    const lotNum = (document.getElementById('lotModalLotNumber')?.value || '').trim();
    const expiry = (document.getElementById('lotModalExpiryDate')?.value || '').trim();
    const mfg = (document.getElementById('lotModalMfgDate')?.value || '').trim();
    const notes = (document.getElementById('lotModalNotes')?.value || '').trim();
    const errEl = document.getElementById('lotModalError');

    if (!lotNum) {
        if (errEl) {
            errEl.textContent = 'Batch / Lot Number is required for dock traceability.';
            errEl.hidden = false;
        }
        document.getElementById('lotModalLotNumber')?.focus();
        return;
    }

    item.lot_number = lotNum;
    item.expiry_date = expiry;
    item.mfg_date = mfg;
    item.notes = notes;

    closeLotExpiryModal();
    renderIntakeTable();
    updateLiveMockup();
    showGrnToast(`Lot & Expiry updated for ${item.sku}`);
    scheduleDraftSave();
};

window.onIntakePoSelected = function(poNumber) {
    if (poNumber) {
        openPoInIntakeBuilder(poNumber);
    } else {
        startDirectBypassIntake();
    }
};

window.onIntakeVendorChanged = function() {
    updateLiveMockup();
    scheduleDraftSave();
};

// 7. Render Stock Intake Table
function renderIntakeTable() {
    renderGrnBuilderTableHeader();

    const tbody = document.getElementById('intakeTableBody');
    const countBadge = document.getElementById('intakeItemCountBadge');
    const summaryLines = document.getElementById('summaryGrnLines');
    if (!tbody) return;

    const items = window.StockInStore.intakeItems;
    if (countBadge) countBadge.textContent = `${items.length} Line Item${items.length === 1 ? '' : 's'}`;
    if (summaryLines) summaryLines.textContent = String(items.length);

    const visibleCols = GRN_BUILDER_COLUMNS.filter(c => c.lockVisible || activeGrnBuilderCols.includes(c.id));

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleCols.length + 1}" style="text-align: center; padding: 48px 16px; color: var(--grn-on-surface-subtle);">
                    <i class="ph ph-package" style="font-size: 40px; color: var(--grn-border-strong); display: block; margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--grn-on-surface); font-size: 13.5px;">No Line Items Loaded in Stock Intake Sheet</div>
                    <div style="font-size: 12px; margin-top: 4px;">Scan an item barcode above, select a <strong>Purchase Order</strong>, or click <strong>"Unplanned Item"</strong>.</div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = items.map((it, idx) => {
        const ordered = Number(it.ordered_qty || 0);
        const prev = Number(it.prev_recv_qty || 0);
        const recvNow = Number(it.receive_now || 0);
        const remaining = Math.max(0, ordered - prev);
        const isOver = ordered > 0 && recvNow > remaining;
        const overQty = isOver ? (recvNow - remaining) : 0;
        const balance = isOver ? 0 : Math.max(0, remaining - recvNow);
        it.balance = balance;
        it.is_over = isOver;
        it.over_qty = overQty;

        const unitCost = Number(it.unit_cost || 0);
        const lineAmount = recvNow * unitCost;
        const totalAfterThis = prev + recvNow;

        const cells = visibleCols.map(col => {
            if (col.id === 'index') {
                return `<td class="td-center" style="font-weight: 700; color: var(--grn-on-surface-subtle);">${idx + 1}</td>`;
            }
            if (col.id === 'item_name') {
                if (it.is_adhoc) {
                    return `
                        <td>
                            <div class="grn-item-cell">
                                <input type="text" class="grn-adhoc-input is-title" value="${escapeHtml(it.name || '')}" onchange="onAdhocFieldChange(${idx}, 'name', this.value)" placeholder="Item Description">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <input type="text" class="grn-adhoc-input is-sku" value="${escapeHtml(it.sku || '')}" onchange="onAdhocFieldChange(${idx}, 'sku', this.value)" placeholder="SKU">
                                    <input type="text" class="grn-adhoc-input" style="flex: 1;" value="${escapeHtml(it.specs || '')}" onchange="onAdhocFieldChange(${idx}, 'specs', this.value)" placeholder="Specs / Brand">
                                </div>
                                ${it.lot_number || it.expiry_date ? `
                                    <div class="grn-trace-line">
                                        <span class="grn-mono"><i class="ph ph-barcode" style="color: var(--grn-primary); margin-right: 2px;"></i>${escapeHtml(it.lot_number || 'No Lot')}</span>
                                        ${renderExpiryChip(it.expiry_date)}
                                    </div>
                                ` : ''}
                            </div>
                        </td>
                    `;
                }
                return `
                    <td>
                        <div class="grn-item-cell">
                            <div class="grn-item-title">${escapeHtml(it.name)}</div>
                            <div class="grn-item-meta">
                                <span class="grn-sku-badge">${escapeHtml(it.sku)}</span>
                                ${it.specs ? `<span class="grn-specs">${escapeHtml(it.specs)}</span>` : ''}
                            </div>
                            ${it.lot_number || it.expiry_date ? `
                                <div class="grn-trace-line">
                                    <span class="grn-mono"><i class="ph ph-barcode" style="color: var(--grn-primary); margin-right: 2px;"></i>${escapeHtml(it.lot_number || 'No Lot')}</span>
                                    ${renderExpiryChip(it.expiry_date)}
                                </div>
                            ` : ''}
                        </div>
                    </td>
                `;
            }
            if (col.id === 'uom') {
                if (it.is_adhoc) {
                    return `
                        <td class="td-center" style="min-width: 90px;">
                            <select data-uom-select data-uom-placeholder="Select UOM" data-uom-current="${escapeHtml(it.uom || 'PCS')}" onchange="onAdhocFieldChange(${idx}, 'uom', this.value)">
                                ${(() => {
                                    if (window.UomRegistry && window.UomRegistry.isLoaded) {
                                        const units = window.UomRegistry.getUnits();
                                        let opts = units.map(u => `<option value="${u.uom_code}" ${String(it.uom).toUpperCase() === u.uom_code.toUpperCase() ? 'selected' : ''}>${u.uom_code}</option>`).join('');
                                        if (it.uom && !units.some(u => u.uom_code.toUpperCase() === String(it.uom).toUpperCase())) {
                                            opts = `<option value="${escapeHtml(it.uom)}" selected>${escapeHtml(it.uom)}</option>` + opts;
                                        }
                                        return opts;
                                    }
                                    const fallbackUnits = ['KG', 'L', 'PCS', 'BOX', 'PACK', 'TRAY', 'SACK'];
                                    const cur = String(it.uom || 'PCS').toUpperCase();
                                    let opts = fallbackUnits.map(u => `<option value="${u}" ${cur === u ? 'selected' : ''}>${u}</option>`).join('');
                                    if (!fallbackUnits.includes(cur)) {
                                        opts = `<option value="${escapeHtml(it.uom)}" selected>${escapeHtml(it.uom)}</option>` + opts;
                                    }
                                    return opts;
                                })()}
                            </select>
                        </td>
                    `;
                }
                return `
                    <td class="td-center">
                        <span class="grn-uom-pill">${escapeHtml(it.uom || 'PCS')}</span>
                    </td>
                `;
            }
            if (col.id === 'ordered_qty') {
                return `<td class="td-num" style="font-weight: 700; color: var(--grn-on-surface-muted);">${ordered > 0 ? ordered.toFixed(2) : '—'}</td>`;
            }
            if (col.id === 'prev_recv_qty') {
                return `<td class="td-num" style="color: var(--grn-on-surface-subtle); font-weight: 600;">${prev > 0 ? prev.toFixed(2) : '0.00'}</td>`;
            }
            if (col.id === 'receive_now') {
                return `
                    <td class="td-center">
                        <div class="grn-stepper-wrap ${isOver ? 'is-over' : ''}">
                            <button type="button" class="grn-stepper-btn" onclick="adjustRowQty(${idx}, -1)" title="Decrement 1" aria-label="Decrease quantity">−</button>
                            <input type="number" class="grn-stepper-input" value="${recvNow}" min="0" step="any" oninput="onReceiveQtyInput(${idx}, this.value)" aria-label="Receive quantity">
                            <button type="button" class="grn-stepper-btn" onclick="adjustRowQty(${idx}, 1)" title="Increment 1" aria-label="Increase quantity">+</button>
                            <button type="button" class="grn-stepper-btn is-max" onclick="setRowReceiveQty(${idx}, ${ordered > 0 ? remaining : (recvNow || 1)})" title="Set to remaining balance">Max</button>
                            <button type="button" class="grn-stepper-btn" onclick="setRowReceiveQty(${idx}, 0)" title="Zero out" style="font-size: 11px;">0</button>
                        </div>
                    </td>
                `;
            }
            if (col.id === 'balance_fulfillment') {
                if (isOver) {
                    return `
                        <td class="td-num" data-cell-role="balance-fulfillment">
                            <div class="grn-balance-cell">
                                <span class="grn-balance-val" style="color: var(--grn-danger);">0.00</span>
                                <span class="grn-fulfillment-badge over"><i class="ph ph-warning"></i> Over +${overQty.toFixed(2)}</span>
                            </div>
                        </td>
                    `;
                }
                const isFull = (ordered > 0 && totalAfterThis >= ordered) || (ordered === 0 && recvNow > 0);
                const isPart = totalAfterThis > 0 && !isFull;
                const fClass = isFull ? 'fulfilled' : (isPart ? 'partial' : 'pending');
                const fText = isFull ? 'Fulfilled' : (isPart ? 'Partial GRN' : 'Pending Intake');
                const balColor = balance > 0 ? 'var(--grn-warning)' : 'var(--grn-success)';

                return `
                    <td class="td-num" data-cell-role="balance-fulfillment">
                        <div class="grn-balance-cell">
                            <span class="grn-balance-val" style="color: ${balColor};">${balance.toFixed(2)}</span>
                            <span class="grn-fulfillment-badge ${fClass}">${fText}</span>
                        </div>
                    </td>
                `;
            }
            if (col.id === 'unit_cost') {
                return `
                    <td class="td-num">
                        <input type="number" step="0.01" min="0" value="${unitCost.toFixed(2)}" onchange="onAdhocFieldChange(${idx}, 'unit_cost', this.value)" style="text-align: right; font-weight: 700; width: 95px; height: 30px; font-size: 12px; border: 1px solid var(--grn-border-strong); border-radius: 4px; padding: 2px 6px;">
                    </td>
                `;
            }
            if (col.id === 'line_total') {
                return `<td class="td-num" data-cell-role="line-total" style="font-weight: 800; color: var(--grn-on-surface);">₱${lineAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            return `<td>—</td>`;
        }).join('');

        return `
            <tr data-row-idx="${idx}" class="${isOver ? 'is-over-receipt' : ''}">
                ${cells}
                <td class="td-center" style="white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                        <button type="button" class="grn-lot-btn ${it.lot_number ? 'has-lot' : ''}" onclick="openLotExpiryModal(${idx})" title="Configure Batch / Lot # & Expiry">
                            <span class="grn-state-dot" aria-hidden="true"></span>
                            <i class="ph ${it.lot_number ? 'ph-barcode' : 'ph-calendar-plus'}" aria-hidden="true"></i>
                            <span>${it.lot_number ? 'Lot Set' : 'Add Lot'}</span>
                        </button>
                        ${it.is_adhoc ? `
                            <button type="button" class="grn-row-remove" onclick="removeAdHocRow(${idx})" title="Remove item" aria-label="Remove item">
                                <i class="ph ph-trash" aria-hidden="true"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    window.UomRegistry?.autoHydrate();
}

window.adjustRowQty = function(idx, delta) {
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;
    const cur = Number(item.receive_now || 0);
    const next = Math.max(0, cur + delta);
    setRowReceiveQty(idx, next);
};

window.onAdhocFieldChange = function(idx, field, val) {
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;
    if (field === 'unit_cost') {
        item.unit_cost = Math.max(0, parseFloat(val) || 0);
    } else {
        item[field] = val;
    }
    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
    scheduleDraftSave();
};

window.onReceiveQtyInput = function(idx, val) {
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;

    const num = Math.max(0, parseFloat(val) || 0);
    item.receive_now = num;

    const ordered = Number(item.ordered_qty || 0);
    const prev = Number(item.prev_recv_qty || 0);
    const remaining = Math.max(0, ordered - prev);
    const isOver = ordered > 0 && num > remaining;
    const overQty = isOver ? (num - remaining) : 0;
    item.balance = isOver ? 0 : Math.max(0, remaining - num);
    item.is_over = isOver;
    item.over_qty = overQty;

    const row = document.querySelector(`tr[data-row-idx="${idx}"]`);
    if (row) {
        if (isOver) {
            row.classList.add('is-over-receipt');
        } else {
            row.classList.remove('is-over-receipt');
        }

        const balCell = row.querySelector('[data-cell-role="balance-fulfillment"]');
        if (balCell) {
            const valEl = balCell.querySelector('.grn-balance-val');
            const badgeEl = balCell.querySelector('.grn-fulfillment-badge');
            if (isOver) {
                if (valEl) {
                    valEl.textContent = '0.00';
                    valEl.style.color = 'var(--grn-danger)';
                }
                if (badgeEl) {
                    badgeEl.className = 'grn-fulfillment-badge over';
                    badgeEl.innerHTML = `<i class="ph ph-warning"></i> Over +${overQty.toFixed(2)}`;
                }
            } else {
                const totalAfterThis = prev + num;
                const isFull = (ordered > 0 && totalAfterThis >= ordered) || (ordered === 0 && num > 0);
                const isPart = totalAfterThis > 0 && !isFull;
                const fClass = isFull ? 'fulfilled' : (isPart ? 'partial' : 'pending');
                const fText = isFull ? 'Fulfilled' : (isPart ? 'Partial GRN' : 'Pending Intake');
                const balColor = item.balance > 0 ? 'var(--grn-warning)' : 'var(--grn-success)';
                if (valEl) {
                    valEl.textContent = item.balance.toFixed(2);
                    valEl.style.color = balColor;
                }
                if (badgeEl) {
                    badgeEl.className = `grn-fulfillment-badge ${fClass}`;
                    badgeEl.textContent = fText;
                }
            }
        }

        const stepperWrap = row.querySelector('.grn-stepper-wrap');
        if (stepperWrap) {
            if (isOver) stepperWrap.classList.add('is-over');
            else stepperWrap.classList.remove('is-over');
        }

        const totTd = row.querySelector('[data-cell-role="line-total"]');
        if (totTd) {
            const amt = num * Number(item.unit_cost || 0);
            totTd.textContent = `₱${amt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    }

    updateFulfillmentProgress();
    updateLiveMockup();
    scheduleDraftSave();
};

window.setRowReceiveQty = function(idx, qty) {
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;

    item.receive_now = qty;
    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
    scheduleDraftSave();
};

window.onRowLotChange = function(idx, lot) {
    if (window.StockInStore.intakeItems[idx]) {
        window.StockInStore.intakeItems[idx].lot_number = lot;
        updateLiveMockup();
        scheduleDraftSave();
    }
};

window.onRowExpiryChange = function(idx, date) {
    if (window.StockInStore.intakeItems[idx]) {
        window.StockInStore.intakeItems[idx].expiry_date = date;
        updateLiveMockup();
        scheduleDraftSave();
    }
};

window.addAdHocIntakeRow = function() {
    const products = window.StockInStore.products;
    const p = products[0] || { sku: 'ITEM-DIRECT', name: 'Direct Spot Item', uom: 'KG', cost_price: 0 };
    const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
    const nextIdx = window.StockInStore.intakeItems.length + 1;

    window.StockInStore.intakeItems.push({
        sku: p.sku || `SPOT-${nextIdx}`,
        name: p.name || 'Direct Spot Inbound Item',
        specs: p.description || p.specs || '',
        uom: p.uom || 'PCS',
        ordered_qty: 0,
        prev_recv_qty: 0,
        receive_now: 1,
        balance: 0,
        unit_cost: Number(p.cost_price || 0),
        vat_type: '12% VAT',
        lot_number: `LOT-${dateStr}-${nextIdx.toString().padStart(2, '0')}`,
        expiry_date: '',
        mfg_date: '',
        notes: '',
        is_adhoc: true,
    });

    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
    flashTableRow(window.StockInStore.intakeItems.length - 1);
    scheduleDraftSave();
};

window.removeAdHocRow = function(idx) {
    window.StockInStore.intakeItems.splice(idx, 1);
    renderIntakeTable();
    updateFulfillmentProgress();
    updateLiveMockup();
    scheduleDraftSave();
};

function updateFulfillmentProgress() {
    const items = window.StockInStore.intakeItems;

    let sumOrdered = 0;
    let sumPrev = 0;
    let sumRecvNow = 0;
    let sumBalance = 0;
    let sumOver = 0;
    let sumValuation = 0;

    items.forEach(it => {
        sumOrdered += Number(it.ordered_qty || it.receive_now || 0);
        sumPrev += Number(it.prev_recv_qty || 0);
        sumRecvNow += Number(it.receive_now || 0);
        sumBalance += Number(it.balance || 0);
        sumOver += Number(it.over_qty || 0);
        sumValuation += Number(it.receive_now || 0) * Number(it.unit_cost || 0);
    });

    const totalCompleted = sumPrev + sumRecvNow;
    const rate = sumOrdered > 0 ? Math.min(100, (totalCompleted / sumOrdered) * 100) : (sumRecvNow > 0 ? 100 : 0);

    const txtOrdered = document.getElementById('sumOrderedUnits');
    const txtRecv = document.getElementById('sumReceivedNowUnits');
    const txtBal = document.getElementById('sumBalanceUnits');
    const overMetric = document.getElementById('grnOverReceiptMetric');
    const txtOver = document.getElementById('sumOverUnits');
    const txtRate = document.getElementById('fulfillmentRateText');
    const progressBar = document.getElementById('fulfillmentProgressBar');
    const progressTrack = document.getElementById('fulfillmentProgressTrack');
    const costRow = document.getElementById('grnOptionalCostSummaryRow');
    const costVal = document.getElementById('sumIntakeValuation');

    if (txtOrdered) txtOrdered.textContent = sumOrdered.toFixed(2);
    if (txtRecv) txtRecv.textContent = sumRecvNow.toFixed(2);
    if (txtBal) txtBal.textContent = sumBalance.toFixed(2);

    if (overMetric && txtOver) {
        if (sumOver > 0) {
            overMetric.hidden = false;
            txtOver.textContent = `+${sumOver.toFixed(2)}`;
        } else {
            overMetric.hidden = true;
        }
    }

    if (txtRate) txtRate.textContent = `${rate.toFixed(1)}%`;
    if (progressBar) progressBar.style.width = `${rate}%`;
    if (progressTrack) progressTrack.setAttribute('aria-valuenow', String(rate.toFixed(0)));

    const isCostShown = activeGrnBuilderCols.includes('unit_cost') || activeGrnBuilderCols.includes('line_total');
    if (costRow) costRow.hidden = !isCostShown;
    if (costVal) costVal.textContent = `₱ ${sumValuation.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const statusTitle = document.getElementById('grnStepStatusTitle');
    const statusDesc = document.getElementById('grnStepStatusDesc');
    const statusChip = document.getElementById('grnStatusChip');
    const statusChipText = document.getElementById('grnStatusChipText');

    if (sumOver > 0) {
        if (statusTitle) statusTitle.textContent = 'Over-Receipt';
        if (statusDesc) statusDesc.textContent = `+${sumOver.toFixed(0)} units excess`;
        if (statusChip && statusChipText) {
            statusChip.setAttribute('data-state', 'partial');
            statusChipText.textContent = `Over-Receipt (+${sumOver.toFixed(1)})`;
        }
    } else if (rate >= 100 && items.length > 0) {
        if (statusTitle) statusTitle.textContent = 'Ready to Fulfill';
        if (statusDesc) statusDesc.textContent = '100% Dock Match';
        if (statusChip && statusChipText) {
            statusChip.setAttribute('data-state', 'ready');
            statusChipText.textContent = 'Fulfilled · Ready to Post';
        }
    } else {
        if (statusTitle) statusTitle.textContent = 'Status';
        if (statusDesc) statusDesc.textContent = rate > 0 ? `${rate.toFixed(0)}% Intake` : 'Ready to Commit';
        if (statusChip && statusChipText) {
            statusChip.setAttribute('data-state', rate > 0 ? 'partial' : 'draft');
            statusChipText.textContent = rate > 0 ? `In Progress (${rate.toFixed(0)}%)` : 'Draft · Not Posted';
        }
    }
}

// Collapsible Mockup Drawer & Overflow Menu
window.toggleGrnMockupDrawer = function(open) {
    const splitBody = document.getElementById('grnBuilderSplitBody');
    const backdrop = document.getElementById('grnDrawerBackdrop');
    const btn = document.getElementById('grnMockupToggleBtn');
    if (!splitBody) return;

    const isOpen = open !== undefined ? open : !splitBody.classList.contains('grn-mockup-open');
    if (isOpen) {
        splitBody.classList.add('grn-mockup-open');
        if (backdrop) backdrop.hidden = false;
        if (btn) btn.setAttribute('aria-expanded', 'true');
    } else {
        splitBody.classList.remove('grn-mockup-open');
        if (backdrop) backdrop.hidden = true;
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }
};

window.toggleGrnOverflowMenu = function(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('grnOverflowMenu');
    const btn = document.getElementById('grnOverflowBtn');
    if (!menu) return;
    const isHidden = menu.hidden;
    menu.hidden = !isHidden;
    if (btn) btn.setAttribute('aria-expanded', String(!isHidden));
};

window.confirmClearIntakeSheet = function() {
    toggleGrnOverflowMenu();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Clear All Items?',
            text: 'This will remove all lines from the current intake sheet. Any unsaved edits will be reset.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, Clear Sheet',
            cancelButtonText: 'Cancel'
        }).then(res => {
            if (res.isConfirmed) {
                resetIntakeSheet(true);
                discardGrnDraft();
                showGrnToast('Intake sheet cleared');
            }
        });
    } else {
        if (confirm('Clear all items from intake sheet?')) {
            resetIntakeSheet(true);
            discardGrnDraft();
        }
    }
};

// 8. Live Document Mockup & Status Sync
window.updateLiveMockup = function() {
    const poSelect = document.getElementById('intakePoSelect');
    const vendorSelect = document.getElementById('intakeVendorSelect');
    const mtSelect = document.getElementById('intakeMovementTypeSelect');
    const srcSelect = document.getElementById('intakeSourceLocationSelect');
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    const etaInput = document.getElementById('intakeExpectedDelivery');
    const tsInput = document.getElementById('intakeReceivedAt');
    const slipInput = document.getElementById('intakeDeliverySlip');
    const notesInput = document.getElementById('intakeDeliveryNotes');

    const docDisplay = document.getElementById('intakeDocNoDisplay');
    const modeBadge = document.getElementById('intakeModeBadge');
    const modeText = document.getElementById('intakeModeText');
    const sheet = document.getElementById('grnLiveMockupSheet');

    const poVal = poSelect?.value || '';
    const grnRef = poVal ? `GRN-${poVal.replace(/^PO-?/, '')}` : 'GRN-DIRECT';

    if (docDisplay) docDisplay.textContent = grnRef;

    if (modeBadge && modeText) {
        if (poVal) {
            modeBadge.className = 'status-pill awarded grn-mode-pill';
            modeText.textContent = `PO: ${poVal}`;
        } else {
            modeBadge.className = 'status-pill sent grn-mode-pill';
            modeText.textContent = 'Direct Spot Inbound';
        }
    }

    if (!sheet) return;

    const companyName = window.StockInConfig?.company?.name || 'RMS Logistics';
    const vendorName = vendorSelect?.options[vendorSelect.selectedIndex]?.text?.replace(/^.*—\s*/, '') || 'Select Target Vendor';
    const mtName = mtSelect?.options[mtSelect.selectedIndex]?.text || 'Standard Inbound Receipt';
    const sourceLoc = srcSelect?.value || 'Supplier / Vendor Origin';
    const destLoc = destSelect?.value || 'Storage Putaway';
    const etaDate = etaInput?.value || 'Scheduled';
    const timestamp = tsInput?.value ? tsInput.value.replace('T', ' ') : 'Live Stamp';
    const slipNotes = [slipInput?.value?.trim(), notesInput?.value?.trim()].filter(Boolean).join(' • ') || 'Pending DR #';

    const items = window.StockInStore.intakeItems || [];
    const showCostInMockup = activeGrnBuilderCols.includes('unit_cost') || activeGrnBuilderCols.includes('line_total');

    const pageBadge = document.getElementById('grnMockupPageCountBadge');
    if (pageBadge) {
        const pages = Math.max(1, Math.ceil(items.length / 10));
        pageBadge.textContent = `${pages} Page${pages > 1 ? 's' : ''}`;
    }

    let sumOrdered = 0;
    let sumRecvNow = 0;
    let sumBalance = 0;
    items.forEach(it => {
        sumOrdered += Number(it.ordered_qty || it.receive_now || 0);
        sumRecvNow += Number(it.receive_now || 0);
        sumBalance += Number(it.balance || 0);
    });

    const rowsHtml = items.length === 0 ? `
        <tr>
            <td colspan="${showCostInMockup ? 7 : 6}" style="text-align: center; padding: 14px; color: #94a3b8;">No items loaded</td>
        </tr>
    ` : items.map((it, i) => `
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 4px 5px; color: #64748b;">${i + 1}</td>
            <td style="padding: 4px 5px;">
                <div style="font-weight: 700; color: #0f172a;">${escapeHtml(it.name || it.sku)}</div>
                <div style="font-family: monospace; font-size: 8.5px; color: #64748b;">${escapeHtml(it.sku)}${it.specs ? ` • ${escapeHtml(it.specs)}` : ''}</div>
            </td>
            <td style="padding: 4px 5px; text-align: center; font-weight: 700;">${escapeHtml(it.uom)}</td>
            <td style="padding: 4px 5px; text-align: right;">${Number(it.ordered_qty || 0).toFixed(2)}</td>
            <td style="padding: 4px 5px; text-align: right; font-weight: 800; color: #059669;">+${Number(it.receive_now || 0).toFixed(2)}</td>
            <td style="padding: 4px 5px; text-align: left; font-size: 9px; color: #7e22ce; font-family: monospace; font-weight: 600;">${escapeHtml(it.lot_number || 'No Lot')}${it.expiry_date ? ` (Exp: ${escapeHtml(it.expiry_date)})` : ''}</td>
            ${showCostInMockup ? `<td style="padding: 4px 5px; text-align: right; font-weight: 700;">₱${(Number(it.receive_now || 0) * Number(it.unit_cost || 0)).toFixed(2)}</td>` : ''}
        </tr>
    `).join('');

    sheet.innerHTML = `
        <div style="border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-weight: 900; font-size: 13px; color: #0f172a; letter-spacing: -0.02em;">${escapeHtml(companyName.toUpperCase())}</div>
                <div style="font-size: 10px; color: #64748b;">Warehouse Logistics & Receiving Operations</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 900; font-size: 11.5px; color: #7e22ce; text-transform: uppercase;">GOODS RECEIPT NOTE</div>
                <div style="font-family: monospace; font-weight: 800; font-size: 11px; color: #0f172a;">${escapeHtml(grnRef)}</div>
                <div style="font-family: monospace; font-size: 9.5px; color: #059669; font-weight: 700;">${poVal ? 'PO Ref: ' + escapeHtml(poVal) : 'Direct Spot Intake'}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; font-size: 10px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                <div style="font-weight: 800; font-size: 8.5px; color: #64748b; text-transform: uppercase; margin-bottom: 3px;">Vendor & Delivery</div>
                <div style="font-weight: 800; color: #0f172a;">${escapeHtml(vendorName)}</div>
                <div style="color: #475569; font-size: 9.5px; margin-top: 2px;">DR / Notes: <strong>${escapeHtml(slipNotes)}</strong></div>
                <div style="color: #64748b; font-size: 9px; margin-top: 1px;">ETA: <strong>${escapeHtml(etaDate)}</strong></div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                <div style="font-weight: 800; font-size: 8.5px; color: #64748b; text-transform: uppercase; margin-bottom: 3px;">Movement Route (Source ➔ Destination)</div>
                <div style="font-weight: 700; color: #7e22ce; font-size: 10px;">${escapeHtml(mtName)}</div>
                <div style="color: #0f172a; font-size: 9.5px; margin-top: 2px;">Route: <strong>${escapeHtml(sourceLoc)}</strong> ➔ <strong>${escapeHtml(destLoc)}</strong></div>
                <div style="color: #64748b; font-size: 9px; margin-top: 1px;">Arrival: ${escapeHtml(timestamp)}</div>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 9.8px; margin-bottom: 12px;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 1.5px solid #cbd5e1; text-align: left;">
                    <th style="padding: 4px 5px;">#</th>
                    <th style="padding: 4px 5px;">Item & Specs</th>
                    <th style="padding: 4px 5px; text-align: center;">UOM</th>
                    <th style="padding: 4px 5px; text-align: right;">Ord</th>
                    <th style="padding: 4px 5px; text-align: right;">Recv</th>
                    <th style="padding: 4px 5px;">Batch / Lot & Expiry</th>
                    ${showCostInMockup ? `<th style="padding: 4px 5px; text-align: right;">Amt</th>` : ''}
                </tr>
            </thead>
            <tbody>
                ${rowsHtml}
            </tbody>
        </table>

        <div style="border-top: 1.5px solid #cbd5e1; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 10px;">
            <div style="color: #64748b;">
                <div>Dock Arrival: <strong>${escapeHtml(timestamp)}</strong></div>
                <div>Ledger Target: <strong>SYS_LEDGER (Immutable)</strong></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 9.5px; color: #64748b;">Ordered: <strong>${sumOrdered.toFixed(2)}</strong> • Backlog: <strong style="color: #d97706;">${sumBalance.toFixed(2)}</strong></div>
                <div style="font-size: 11.5px; font-weight: 900; color: #059669; margin-top: 2px;">Total Received Now: +${sumRecvNow.toFixed(2)} Units</div>
            </div>
        </div>
    `;
};

window.openGrnDocumentPreview = function() {
    const sheet = document.getElementById('grnLiveMockupSheet');
    if (!sheet) return;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Goods Receipt Note (GRN) Preview',
            html: `<div style="text-align: left; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;">${sheet.innerHTML}</div>`,
            width: 720,
            showCancelButton: true,
            confirmButtonText: '<i class="ph ph-printer"></i> Print GRN Slip',
            confirmButtonColor: '#7c3aed',
            cancelButtonText: 'Close'
        }).then(res => {
            if (res.isConfirmed) window.print();
        });
    } else {
        window.print();
    }
};

// Close floating menus when clicking outside
document.addEventListener('click', (e) => {
    const qDd = document.getElementById('queueTableSettingsDropdown');
    if (qDd && !qDd.contains(e.target) && e.target.id !== 'btnQueueTableSettings') {
        qDd.style.display = 'none';
    }
    const bDd = document.getElementById('grnBuilderSettingsDropdown');
    if (bDd && !bDd.contains(e.target) && e.target.id !== 'btnGrnBuilderSettings') {
        bDd.style.display = 'none';
    }
    const oMenu = document.getElementById('grnOverflowMenu');
    const oBtn = document.getElementById('grnOverflowBtn');
    if (oMenu && !oMenu.hidden && !oMenu.contains(e.target) && !oBtn?.contains(e.target)) {
        oMenu.hidden = true;
        if (oBtn) oBtn.setAttribute('aria-expanded', 'false');
    }
});

// 9. Confirm Receipt & Putaway (Atomic Vectorized Execution)
window.confirmAndCommitStockIntake = function() {
    const items = window.StockInStore.intakeItems;
    const receivingItems = items.filter(it => it.receive_now > 0);

    if (receivingItems.length === 0) {
        showGrnToast('Please enter a "Receive Now" quantity greater than 0 for at least one item.', 'warning');
        return;
    }

    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (!vendorSelect || !vendorSelect.value) {
        showGrnToast('Please select a Vendor Partner before submitting.', 'warning');
        return;
    }

    // Over-Receipt Warning Guard
    const hasOverReceipt = items.some(it => it.is_over);
    if (hasOverReceipt) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Over-Receipt Detected',
                text: 'One or more items exceed the original ordered quantity. Do you want to accept this excess delivery into physical inventory?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Yes, Accept Excess & Proceed',
                cancelButtonColor: '#64748b',
                cancelButtonText: 'Review Quantities'
            }).then(result => {
                if (result.isConfirmed) {
                    proceedWithPartialCheck();
                }
            });
            return;
        }
    }

    proceedWithPartialCheck();

    function proceedWithPartialCheck() {
        const isPartial = items.some(it => {
            const remaining = Math.max(0, it.ordered_qty - it.prev_recv_qty);
            return it.ordered_qty > 0 && it.receive_now < remaining;
        });

        if (isPartial && window.StockInStore.activePoNumber) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Partial Shipment Detected',
                    html: `
                        <div style="font-size: 13px; color: #475569; text-align: left; margin-top: 8px;">
                            <p>You are receiving less units than the remaining balance on <strong>${escapeHtml(window.StockInStore.activePoNumber)}</strong>.</p>
                            <p>How would you like the system to handle the unfulfilled remainder?</p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ph ph-clock-countdown"></i> Create Backorder',
                    confirmButtonColor: '#7c3aed',
                    cancelButtonText: '<i class="ph ph-x-circle"></i> Short-Close Remainder',
                    cancelButtonColor: '#64748b',
                    customClass: { popup: 'swal2-overflow' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeIntakeSubmission('CREATE_BACKORDER');
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        executeIntakeSubmission('SHORT_CLOSE');
                    }
                });
                return;
            }
        }

        executeIntakeSubmission('CREATE_BACKORDER');
    }
};

function executeIntakeSubmission(backorderPolicy) {
    const lockOverlay = document.getElementById('grnGlobalLockOverlay');
    const btnConfirm = document.getElementById('btnConfirmIntake');

    if (lockOverlay) lockOverlay.style.display = 'flex';
    if (btnConfirm) btnConfirm.disabled = true;

    const vendorSelect = document.getElementById('intakeVendorSelect');
    const whSelect = document.getElementById('intakeWarehouseSelect');
    const mtSelect = document.getElementById('intakeMovementTypeSelect');
    const slipInput = document.getElementById('intakeDeliverySlip');
    const notesInput = document.getElementById('intakeDeliveryNotes');
    const carrierInput = document.getElementById('intakeCarrier');

    const poNumber = window.StockInStore.activePoNumber || null;
    const vendorId = vendorSelect?.value || null;
    const vendorName = vendorSelect?.options[vendorSelect.selectedIndex]?.text || 'Vendor';
    const warehouseId = whSelect?.value || 'WH-MAIN';
    const movementTypeId = mtSelect?.value || 'WH-MAIN-IN';
    const deliverySlip = (slipInput?.value || '').trim();
    const carrier = (carrierInput?.value || '').trim();
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    const destinationLocation = destSelect?.value || 'Stock Zone A (Dry Goods)';

    const activeLines = window.StockInStore.intakeItems.filter(it => it.receive_now > 0).map(it => ({
        sku: it.sku,
        name: it.name,
        unit: it.uom,
        received_qty: it.receive_now,
        unit_cost: Number(it.unit_cost || 0),
        storage_location: destinationLocation,
        lot_number: it.lot_number,
        expiry_date: it.expiry_date,
        notes: it.notes || (notesInput?.value || null),
    }));

    const clientUuid = `intake-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

    const payload = {
        po_number: poNumber,
        vendor_id: vendorId,
        vendor_name: vendorName,
        warehouse_id: warehouseId,
        movement_type_id: movementTypeId,
        delivery_location: whSelect?.options[whSelect.selectedIndex]?.text || warehouseId,
        delivery_slip: deliverySlip,
        carrier: carrier,
        backorder_policy: backorderPolicy,
        intake_uuid: clientUuid,
        items: activeLines,
    };

    fetch(window.StockInConfig.routes.receiveStock, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.StockInConfig.data.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
        body: JSON.stringify(payload)
    })
    .then(async (res) => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Server returned an error');
        return data;
    })
    .then(data => {
        if (lockOverlay) lockOverlay.style.display = 'none';
        if (btnConfirm) btnConfirm.disabled = false;

        const grnNo = data.grn_number || 'GRN-SUCCESS';
        showGrnToast(`Intake Successful: ${grnNo} posted to SYS_LEDGER`);

        // Discard local draft upon success
        localStorage.removeItem('rms_grn_draft_v1');
        const indicator = document.getElementById('grnDraftIndicator');
        if (indicator) indicator.hidden = true;

        if (poNumber) {
            const po = window.StockInStore.purchaseOrders.find(p => p.po_number === poNumber);
            if (po) {
                let allDone = true;
                activeLines.forEach(al => {
                    const poLine = (po.items || []).find(pi => pi.sku === al.sku);
                    if (poLine) {
                        poLine.received_quantity = (Number(poLine.received_quantity) || 0) + al.received_qty;
                        if (poLine.received_quantity < Number(poLine.quantity)) allDone = false;
                    }
                });

                if (backorderPolicy === 'SHORT_CLOSE') {
                    po.status = 'Fully Received';
                } else {
                    po.status = allDone ? 'Fully Received' : 'Partially Received';
                }
            }
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Goods Receipt Posted!',
                html: `
                    <div style="font-size: 13.5px; color: #334155;">
                        <p style="margin-bottom: 6px;">Reference: <strong style="font-family: monospace; color: #7c3aed;">${escapeHtml(grnNo)}</strong></p>
                        <p style="margin: 0;">Physical inventory balances updated and immutable append-only record stamped in <strong>SYS_LEDGER</strong>.</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'Return to Orders Queue'
            }).then(() => {
                resetIntakeSheet(false);
                switchGrnMode('queue');
                renderKpis();
            });
        } else {
            resetIntakeSheet(false);
            switchGrnMode('queue');
            renderKpis();
        }
    })
    .catch(err => {
        if (lockOverlay) lockOverlay.style.display = 'none';
        if (btnConfirm) btnConfirm.disabled = false;
        console.error('Intake Error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Intake Error',
                text: err.message || 'An error occurred while posting inventory. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc2626',
            });
        } else {
            showGrnToast(err.message || 'Intake failed', 'danger');
        }
    });
}

// Global hotkeys
document.addEventListener('keydown', (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        if (window.StockInStore.activeMode !== 'builder') {
            switchGrnMode('builder');
        }
        setTimeout(() => document.getElementById('grnScanInput')?.focus(), 50);
    } else if (e.key === 'F10') {
        if (window.StockInStore.activeMode === 'builder') {
            e.preventDefault();
            confirmAndCommitStockIntake();
        }
    } else if (e.key === 'F8') {
        if (window.StockInStore.activeMode === 'builder') {
            e.preventDefault();
            openGrnDocumentPreview();
        }
    } else if (e.key === 'Escape') {
        const lotModal = document.getElementById('grnLotExpiryModal');
        if (lotModal && lotModal.style.display !== 'none') {
            closeLotExpiryModal();
            return;
        }
        toggleGrnMockupDrawer(false);
        const oMenu = document.getElementById('grnOverflowMenu');
        if (oMenu) oMenu.hidden = true;
    } else if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        const search = document.getElementById('queueSearchInput');
        if (search) search.focus();
    }
});

// Bootstrap on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    initStockInModule();
    checkGrnDraft();
});
