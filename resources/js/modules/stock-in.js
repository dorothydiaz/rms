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
    isCurrentGrnPosted: false,
    lastPostedGrnNumber: null,
    activeRouteStepIndex: 0,
    unlockedRouteStepIndex: 0,
    completedRouteSteps: [],
    backorderPolicy: 'CREATE_BACKORDER',
    confirmedShortageSignature: null,
    pendingBackorderContinuation: null,
    qaInspectionState: {
        verified: true,
        tempC: '2.4',
        result: 'PASS',
        quarantined: false,
    },

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

function normalizeIsoDate(val) {
    if (!val) return '';
    const str = String(val).trim();
    const isoMatch = str.match(/^(\d{4}-\d{2}-\d{2})/);
    if (isoMatch) return isoMatch[1];
    try {
        const d = new Date(str);
        if (isNaN(d.getTime())) return '';
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    } catch (e) {
        return '';
    }
}

function getTodayIsoDate() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function getPoExpectedEtaIso(po) {
    if (!po) return '';
    return normalizeIsoDate(po.expected_delivery || po.expected_delivery_date || po.delivery_date || '');
}

function getPoActualArrivalIso(po) {
    if (!po) return '';
    const receipts = Array.isArray(window.StockInStore.goodsReceipts) ? window.StockInStore.goodsReceipts : [];
    const matched = receipts.find(r => r && r.po_number && r.po_number === po.po_number && (r.received_at || r.created_at));
    if (matched) {
        const dt = normalizeIsoDate(matched.received_at || matched.created_at);
        if (dt) return dt;
    }
    return normalizeIsoDate(po.arrival_date || po.received_at || '');
}

function formatDisplayDate(val) {
    if (!val) return '—';
    try {
        const iso = normalizeIsoDate(val);
        const d = iso ? new Date(`${iso}T12:00:00`) : new Date(val);
        if (isNaN(d.getTime())) return String(val).slice(0, 10);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
        return String(val).slice(0, 10);
    }
}

function renderQueueExpectedEtaCell(po, arrivalIso) {
    const etaIso = getPoExpectedEtaIso(po);
    if (!etaIso) {
        return `<span class="grn-date-empty">—</span>`;
    }

    const formatted = formatDisplayDate(etaIso);
    const hasArrived = (po.status === 'Fully Received') || !!arrivalIso;

    // If already arrived, show ONLY the clean date with zero countdown badge
    if (hasArrived) {
        return `<span class="grn-eta-cell" title="Expected ETA: ${escapeHtml(formatted)} (Arrived)"><span class="grn-eta-date">${escapeHtml(formatted)}</span></span>`;
    }

    const today = new Date();
    const todayMid = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const [y, m, d] = etaIso.split('-').map(Number);
    const etaMid = new Date(y, (m || 1) - 1, d || 1);
    const diffDays = Math.round((etaMid - todayMid) / (1000 * 60 * 60 * 24));

    let pillCls = 'is-scheduled';
    let pillText = `${diffDays}d left`;
    let pillIcon = 'ph-calendar-blank';
    let tooltip = `Expected ETA: ${formatted} (${diffDays} days left)`;

    if (diffDays < 0) {
        const late = Math.abs(diffDays);
        pillCls = 'is-overdue';
        pillText = `${late}d overdue`;
        pillIcon = 'ph-warning-circle';
        tooltip = `Expected ETA: ${formatted} (${late} day${late === 1 ? '' : 's'} overdue)`;
    } else if (diffDays === 0) {
        pillCls = 'is-today';
        pillText = 'Due today';
        pillIcon = 'ph-clock';
        tooltip = `Expected ETA: ${formatted} (Arriving today)`;
    } else if (diffDays <= 7) {
        pillCls = diffDays <= 2 ? 'is-imminent' : 'is-within-7d';
        pillText = `≤7d · ${diffDays}d left`;
        pillIcon = 'ph-hourglass-medium';
        tooltip = `Arriving within 7 days — ${diffDays} day${diffDays === 1 ? '' : 's'} left (${formatted})`;
    }

    return `
        <span class="grn-eta-cell" title="${escapeHtml(tooltip)}">
            <span class="grn-eta-date">${escapeHtml(formatted)}</span>
            <span class="grn-eta-pill ${pillCls}"><i class="ph ${pillIcon}" aria-hidden="true"></i>${escapeHtml(pillText)}</span>
        </span>
    `;
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
    { id: 'expected_delivery', label: 'Expected ETA', subLabel: 'Countdown & Dock Date', default: true, lockVisible: false, defaultWidth: '195px', align: 'left' },
    { id: 'arrival_date', label: 'Arrival Date', subLabel: 'Actual Dock Arrival', default: true, lockVisible: false, defaultWidth: '125px', align: 'left' },
    { id: 'target_vendor', label: 'Target Vendor', subLabel: 'Supplier Partner', default: true, lockVisible: false, defaultWidth: '205px', align: 'left' },
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
        const saved = localStorage.getItem('rms_grn_queue_cols_v2');
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
let savedQueueColWidths = (() => {
    try { return JSON.parse(localStorage.getItem('rms_grn_queue_col_widths_v1') || '{}') || {}; } catch (e) { return {}; }
})();
let queueColSearchTerm = '';
let draggedQueueColId = null;

function saveQueueColsState() {
    try {
        localStorage.setItem('rms_grn_queue_cols_v2', JSON.stringify(activeQueueColIds));
    } catch (e) {}
}

// ============================================================================
// B. STOCK INTAKE SHEET (GRN) BUILDER COLUMNS (PO BUILDER PHASE 3 PARITY)
// Cost & Amount columns are included in the Gear Menu but HIDDEN by default!
// ============================================================================
const GRN_BUILDER_COLUMNS = [
    { id: 'index', label: '#', category: 'Core Stock Specs', lockVisible: true, default: true, align: 'center', width: '44px' },
    { id: 'item_name', label: 'Item Description & Specs', category: 'Core Stock Specs', lockVisible: true, default: true, align: 'left', width: '250px' },
    { id: 'uom', label: 'UOM', category: 'Core Stock Specs', lockVisible: false, default: true, align: 'center', width: '80px' },
    { id: 'ordered_qty', label: 'Ordered', category: 'Receiving & Fulfillment', lockVisible: false, default: true, align: 'right', width: '88px' },
    { id: 'prev_recv_qty', label: 'Prev Recv', category: 'Receiving & Fulfillment', lockVisible: false, default: true, align: 'right', width: '90px' },
    { id: 'receive_now', label: 'Receive Now', category: 'Receiving & Fulfillment', lockVisible: true, default: true, align: 'right', width: '112px' },
    { id: 'balance_fulfillment', label: 'Pending Balance & Status', category: 'Receiving & Fulfillment', lockVisible: false, default: true, align: 'right', width: '148px' },
    // Commercial / Cost Columns — HIDDEN BY DEFAULT in Inventory Module (Toggleable in Gear Menu)
    { id: 'unit_cost', label: 'Unit Cost (₱)', category: 'Cost & Amount (Hidden)', lockVisible: false, default: false, align: 'right', width: '105px' },
    { id: 'line_total', label: 'Line Amount (₱)', category: 'Cost & Amount (Hidden)', lockVisible: false, default: false, align: 'right', width: '115px' }
];

const GRN_DEFAULT_STAGE_COLUMNS = GRN_BUILDER_COLUMNS.filter(c => c.default).map(c => c.id);
let activeGrnBuilderCols = [...GRN_DEFAULT_STAGE_COLUMNS];
let savedGrnBuilderColWidths = (() => {
    try { return JSON.parse(localStorage.getItem('rms_grn_builder_col_widths_v1') || '{}') || {}; } catch (e) { return {}; }
})();

function getGrnBuilderColWidth(col) {
    return savedGrnBuilderColWidths[col.id] || col.width || col.minWidth || '120px';
}

function createGrnTableColResizer(colgroupId, storageKey, widthsObj) {
    let resizingColId = null;
    let startX = 0;
    let startW = 0;
    let rafId = null;

    function onMouseMove(e) {
        if (!resizingColId) return;
        const pageX = e.pageX;
        if (rafId) return;
        rafId = window.requestAnimationFrame(() => {
            rafId = null;
            if (!resizingColId) return;
            const newWidth = Math.max(48, startW + (pageX - startX));
            widthsObj[resizingColId] = `${newWidth}px`;
            const colEl = document.querySelector(`#${colgroupId} col[data-col-id="${resizingColId}"]`);
            if (colEl) colEl.style.width = `${newWidth}px`;
            const thEl = document.querySelector(`#${colgroupId}`)?.parentElement?.querySelector(`th[data-col-id="${resizingColId}"]`);
            if (thEl) thEl.style.width = `${newWidth}px`;
        });
    }

    function onMouseUp() {
        if (!resizingColId) return;
        try { localStorage.setItem(storageKey, JSON.stringify(widthsObj)); } catch (e) {}
        resizingColId = null;
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    return function initResize(e, colId) {
        e.preventDefault();
        e.stopPropagation();
        resizingColId = colId;
        startX = e.pageX;
        const colEl = document.querySelector(`#${colgroupId} col[data-col-id="${colId}"]`);
        const thEl = document.querySelector(`#${colgroupId}`)?.parentElement?.querySelector(`th[data-col-id="${colId}"]`);
        startW = (thEl && thEl.offsetWidth) || (colEl && colEl.offsetWidth) || 120;
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    };
}

window.initGrnBuilderColResize = createGrnTableColResizer('intakeColgroup', 'rms_grn_builder_col_widths_v1', savedGrnBuilderColWidths);
window.initGrnQueueColResize = createGrnTableColResizer('queueColgroup', 'rms_grn_queue_col_widths_v1', savedQueueColWidths);

// Helper: Sync Item Master products (including trackingBy: 'lots' | 'quantity' | 'serial' & variants) from localStorage
function getSyncedItemMasterCatalog() {
    let localProds = [];
    try {
        localProds = JSON.parse(localStorage.getItem('rms_inventory_products') || '[]') || [];
    } catch (e) {
        localProds = [];
    }
    const baseProds = (window.StockInStore && Array.isArray(window.StockInStore.products)) ? window.StockInStore.products : [];
    const mergedMap = new Map();

    baseProds.forEach(p => {
        const key = String(p.sku || p.item_code || p.id || '').toUpperCase();
        if (key) mergedMap.set(key, Object.assign({}, p));
    });

    localProds.forEach(lp => {
        const key = String(lp.sku || lp.id || '').toUpperCase();
        const existing = key ? mergedMap.get(key) : null;
        mergedMap.set(key || `LOCAL-${lp.id}`, Object.assign({}, existing || {}, {
            id: lp.id,
            sku: lp.sku,
            item_code: lp.sku,
            barcode: lp.barcode || (existing && existing.barcode) || '',
            name: lp.name,
            description: lp.description || '',
            uom: lp.unit || (existing && existing.uom) || 'PCS',
            cost_price: Number(lp.costPrice ?? (existing && existing.cost_price) ?? 0),
            trackingBy: lp.trackingBy || 'lots',
            trackPhysicalStock: lp.trackPhysicalStock !== undefined ? lp.trackPhysicalStock : (lp.trackStock !== false),
            variants: Array.isArray(lp.variants) ? lp.variants : []
        }));
    });

    return Array.from(mergedMap.values());
}

window.getItemMasterTrackingMode = function(itemOrProd) {
    if (!itemOrProd) return 'lots';
    const skuKey = String(itemOrProd.sku || itemOrProd.item_code || '').trim().toUpperCase();
    const nameKey = String(itemOrProd.name || itemOrProd.item_name || '').trim().toUpperCase();

    const catalog = getSyncedItemMasterCatalog();
    const matched = catalog.find(p =>
        (skuKey && String(p.sku || p.item_code || '').trim().toUpperCase() === skuKey) ||
        (nameKey && String(p.name || '').trim().toUpperCase() === nameKey)
    );

    if (matched) {
        if (matched.trackPhysicalStock === false) return 'none';
        return matched.trackingBy || matched.tracking_by || 'lots';
    }
    if (itemOrProd.trackingBy || itemOrProd.tracking_by) {
        return itemOrProd.trackingBy || itemOrProd.tracking_by;
    }
    // Check heuristic for packaging/disposables if not explicitly in localStorage yet
    if (skuKey.startsWith('PKG-')) return 'quantity';
    return 'lots';
};

window.isLotTrackingEnabledForItem = function(itemOrProd) {
    return window.getItemMasterTrackingMode(itemOrProd) === 'lots';
};

function mergeSyncedEntitiesById(baseList, storageKeys) {
    const mergedMap = new Map();
    (Array.isArray(baseList) ? baseList : []).forEach(item => {
        if (item && item.id) mergedMap.set(item.id, Object.assign({}, item));
    });
    for (const key of storageKeys) {
        try {
            const raw = localStorage.getItem(key);
            if (!raw) continue;
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                parsed.forEach(item => {
                    if (item && item.id) mergedMap.set(item.id, Object.assign({}, item));
                });
            }
        } catch (e) {}
    }
    return Array.from(mergedMap.values());
}

function getSyncedWarehouses() {
    const base = window.StockInConfig?.data?.initialWarehouses || [];
    return mergeSyncedEntitiesById(base, ['rms_inv_warehouses_v1']);
}

function getSyncedStorageAreas() {
    const base = window.StockInConfig?.data?.initialStorageAreas || [];
    return mergeSyncedEntitiesById(base, ['rms_inv_storage_areas_v1']);
}

function getSyncedInboundMovementTypes() {
    const base = window.StockInConfig?.data?.initialMovementTypes || [];
    const merged = mergeSyncedEntitiesById(base, ['rms_movement_supply_flows_v1', 'rms_inv_movement_types_v2']);
    return merged.filter(mt =>
        mt && (mt.operation_kind === 'Inbound Receipt' || mt.stock_effect === 'Receipt')
    );
}

function getActiveInboundMovementType() {
    const list = getSyncedInboundMovementTypes();
    window.StockInStore.movementTypes = list;
    const selVal = document.getElementById('intakeMovementTypeSelect')?.value;
    return list.find(m => m.id === selVal) || list[0] || { id: 'MT-MAIN-RCV', route_preset: '2-Step Staged', steps: [] };
}

function refreshSyncedRouteCatalog() {
    window.StockInStore.warehouses = getSyncedWarehouses();
    window.StockInStore.storageAreas = getSyncedStorageAreas();
    window.StockInStore.movementTypes = getSyncedInboundMovementTypes();
    const whId = document.getElementById('intakeWarehouseSelect')?.value || window.StockInStore.warehouses[0]?.id || 'WH-MAIN';
    syncMovementTypesForWarehouse(whId, true);
}

// 1. Initial State Hydration
function initStockInModule() {
    const cfg = window.StockInConfig || {};
    const d = cfg.data || {};

    window.StockInStore.purchaseOrders = d.initialPurchaseOrders || [];
    window.StockInStore.vendors = d.initialVendors || [];
    window.StockInStore.products = d.initialProducts || [];
    window.StockInStore.products = getSyncedItemMasterCatalog();
    window.StockInStore.goodsReceipts = d.initialReceipts || [];
    window.StockInStore.warehouses = getSyncedWarehouses();
    window.StockInStore.storageAreas = getSyncedStorageAreas();
    window.StockInStore.movementTypes = getSyncedInboundMovementTypes();

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

    // Sync route additions/edits made in Movement Types & Supply Flows
    window.addEventListener('storage', (e) => {
        if (!e.key || ['rms_inv_movement_types_v2', 'rms_movement_supply_flows_v1', 'rms_inv_warehouses_v1', 'rms_inv_storage_areas_v1'].includes(e.key)) {
            refreshSyncedRouteCatalog();
        }
    });
    window.addEventListener('focus', () => {
        refreshSyncedRouteCatalog();
    });

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

    // Intake Header Warehouse Select (only populate options if it is a <select>, not hidden input)
    const intakeWhSelect = document.getElementById('intakeWarehouseSelect');
    if (intakeWhSelect && intakeWhSelect.tagName === 'SELECT') {
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
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();
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
    if (typeof window.syncGrnPoPickerLabel === 'function') window.syncGrnPoPickerLabel();
}

// 4. Top KPI Cards
function renderKpis() {
    const orders = window.StockInStore.purchaseOrders;
    const receipts = window.StockInStore.goodsReceipts;
    const warehouses = window.StockInStore.warehouses;

    // Pending Orders count (Issued / Approved / Ready to receive / Draft POs + Partially Received)
    const pendingOrders = orders.filter(po => 
        ['Issued', 'Approved', 'Approved / Issued', 'Partially Received', 'Ready to Receive', 'Draft', 'Draft PO', 'Pending Approval'].includes(po.status)
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
    const colgroup = document.getElementById('queueColgroup');
    if (!theadRow) return;

    const visibleCols = queueColOrder
        .filter(id => activeQueueColIds.includes(id))
        .map(id => QUEUE_DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let colHtml = '';
    let thHtml = '';
    visibleCols.forEach(col => {
        const w = getQueueColWidth(col);
        const isRight = (col.align === 'right');
        const alignClass = isRight ? 'th-num' : 'th-left';
        colHtml += `<col data-col-id="${col.id}" style="width: ${w};">`;

        if (col.id === 'actions') {
            thHtml += `
                <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width: ${w}; text-align: right; user-select: none;">
                    <div class="grn-action-th-inner">
                        <div class="rfq-th-content" style="align-items: flex-end; text-align: right;">
                            <div class="rfq-th-title">${escapeHtml(col.label || 'Action')}</div>
                            <div class="rfq-th-sub">${escapeHtml(col.subLabel || 'Receive / View')}</div>
                        </div>
                        <button type="button" class="rfq-th-settings-btn" id="btnQueueTableSettings" onclick="toggleQueueTableSettingsDropdown(event)" title="Customize Columns &amp; Drag Sequence">
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
                    <div class="rfq-col-resizer" onmousedown="initGrnQueueColResize(event, '${col.id}')" title="Drag to resize column"></div>
                </th>
            `;
        }
    });

    if (colgroup) colgroup.innerHTML = colHtml;
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

    const trigger = event?.currentTarget || document.getElementById('btnQueueTableSettings');
    if (trigger && typeof trigger.getBoundingClientRect === 'function') {
        const rect = trigger.getBoundingClientRect();
        const viewportW = window.innerWidth || document.documentElement?.clientWidth || 1280;
        const viewportH = window.innerHeight || document.documentElement?.clientHeight || 800;
        const dropdownW = Math.min(dd.offsetWidth || 320, viewportW - 24);
        const dropdownH = Math.min(dd.offsetHeight || 420, viewportH - 24);

        let left = rect.right - dropdownW;
        if (left + dropdownW > viewportW - 12) left = viewportW - dropdownW - 12;
        if (left < 12) left = 12;

        let top = rect.bottom + 6;
        if (top + dropdownH > viewportH - 12) {
            if (rect.top - dropdownH - 6 >= 12) {
                top = rect.top - dropdownH - 6;
            } else {
                top = Math.max(12, viewportH - dropdownH - 12);
            }
        }

        dd.style.position = 'fixed';
        dd.style.top = `${Math.round(top)}px`;
        dd.style.left = `${Math.round(left)}px`;
        dd.style.right = 'auto';
        dd.style.zIndex = '10075';
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
    try { localStorage.removeItem('rms_grn_queue_col_widths_v1'); } catch (e) {}
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

let queueSearchDebounce = null;
window.onQueueSearchInput = function(query) {
    window.StockInStore.queueSearchQuery = (query || '').toLowerCase().trim();
    clearTimeout(queueSearchDebounce);
    queueSearchDebounce = setTimeout(() => {
        window.requestAnimationFrame(renderOrdersQueue);
    }, 140);
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
            if (!['Issued', 'Approved', 'Approved / Issued', 'Ready to Receive', 'Draft', 'Draft PO', 'Pending Approval'].includes(status)) return false;
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

        const arrivalIso = getPoActualArrivalIso(po);

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
                return `<td style="white-space: nowrap;">${renderQueueExpectedEtaCell(po, arrivalIso)}</td>`;
            }
            if (col.id === 'arrival_date') {
                if (arrivalIso) {
                    const fmtArr = formatDisplayDate(arrivalIso);
                    return `<td style="white-space: nowrap;"><span class="grn-arrival-cell is-arrived" title="Arrived on ${escapeHtml(fmtArr)}"><i class="ph-fill ph-check-circle" aria-hidden="true"></i><span>${escapeHtml(fmtArr)}</span></span></td>`;
                }
                return `<td style="white-space: nowrap;"><span class="grn-arrival-cell is-pending">— Pending</span></td>`;
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
                    <td class="td-actions-sticky" style="text-align: right;">
                        ${status !== 'Fully Received' ? `
                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openPoInIntakeBuilder('${escapeHtml(po.po_number)}', false)">
                            <i class="ph ph-tray-arrow-down"></i> Receive Intake
                        </button>` : `
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPoInIntakeBuilder('${escapeHtml(po.po_number)}', true)" title="View completed Goods Receipt &amp; A4 GRN">
                            <i class="ph ph-eye"></i> View
                        </button>`}
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
    const viewportW = window.innerWidth || document.documentElement?.clientWidth || 1280;
    const viewportH = window.innerHeight || document.documentElement?.clientHeight || 800;
    const dropdownWidth = Math.min(dd.offsetWidth || 320, viewportW - 24);
    const dropdownH = Math.min(dd.offsetHeight || 360, viewportH - 24);

    let left = rect.right - dropdownWidth;
    if (left + dropdownWidth > viewportW - 12) left = viewportW - dropdownWidth - 12;
    if (left < 12) left = 12;

    let top = rect.bottom + 6;
    if (top + dropdownH > viewportH - 12) {
        if (rect.top - dropdownH - 6 >= 12) {
            top = rect.top - dropdownH - 6;
        } else {
            top = Math.max(12, viewportH - dropdownH - 12);
        }
    }

    dd.style.position = 'fixed';
    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.right = 'auto';
    dd.style.zIndex = '10075';
}

window.renderGrnBuilderTableHeader = function() {
    const theadRow = document.getElementById('intakeItemsTheadRow');
    const colgroup = document.getElementById('intakeColgroup');
    if (!theadRow) return;

    const wasOpen = document.getElementById('grnBuilderSettingsDropdown')?.style.display === 'block';
    const visibleCols = GRN_BUILDER_COLUMNS.filter(c => c.lockVisible || activeGrnBuilderCols.includes(c.id));

    let colHtml = '';
    let html = visibleCols.map(col => {
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');
        const w = getGrnBuilderColWidth(col);
        colHtml += `<col data-col-id="${col.id}" style="width: ${w};">`;
        return `
            <th class="${alignClass}" data-col-id="${col.id}" style="width: ${w};">
                <span>${escapeHtml(col.label)}</span>
                <div class="rfq-col-resizer" onmousedown="initGrnBuilderColResize(event, '${col.id}')" title="Drag to resize column"></div>
            </th>
        `;
    }).join('');

    colHtml += `<col data-col-id="actions" style="width: 112px;">`;
    html += `
        <th class="th-center th-actions-sticky" data-col-id="actions" style="width: 112px;">
            <div class="grn-action-th-inner">
                <span>Action</span>
                <button type="button" class="rfq-th-settings-btn" id="btnGrnBuilderSettings" onclick="toggleGrnBuilderSettingsDropdown(event)" title="Customize Stock Intake Sheet Columns">
                    <i class="ph ph-gear"></i>
                </button>
            </div>
            <div id="grnBuilderSettingsDropdown" class="po-builder-settings-dropdown rfq-builder-settings-dropdown grn-settings-dropdown" style="display: ${wasOpen ? 'block' : 'none'}; text-align: left;" onclick="event.stopPropagation()">
                <div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 800; font-size: 12px; color: #0f172a;"><i class="ph ph-gear-six" style="color: #9333ea;"></i> Intake Sheet Columns</span>
                    <span style="font-size: 10.5px; font-weight: 700; color: #7e22ce; background: #f3e8ff; padding: 2px 7px; border-radius: 8px;">${visibleCols.length} of ${GRN_BUILDER_COLUMNS.length}</span>
                </div>
                <div style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; gap: 6px; background: #ffffff; flex-wrap: wrap;">
                    <button type="button" class="inv-col-quick-link" onclick="resetGrnBuilderColumnsToStage()" style="font-size: 11px; color: #7e22ce; font-weight: 700; background: none; border: none; cursor: pointer;">Stocks Default</button>
                    <button type="button" class="inv-col-quick-link" onclick="showAllGrnBuilderColumns()" style="font-size: 11px; color: #475569; font-weight: 600; background: none; border: none; cursor: pointer;">Show All</button>
                    <button type="button" class="inv-col-quick-link" onclick="resetGrnBuilderColumnWidths()" style="font-size: 11px; color: #475569; font-weight: 600; background: none; border: none; cursor: pointer;">Reset Widths</button>
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

    if (colgroup) colgroup.innerHTML = colHtml;
    theadRow.innerHTML = html;
    if (wasOpen) {
        requestAnimationFrame(() => positionGrnBuilderSettingsDropdown());
    }
};

window.resetGrnBuilderColumnWidths = function() {
    savedGrnBuilderColWidths = {};
    try { localStorage.removeItem('rms_grn_builder_col_widths_v1'); } catch (e) {}
    renderGrnBuilderTableHeader();
    showGrnToast('Intake table column widths reset');
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

function saveGrnDraft(isManual = false) {
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
            if (isManual) showGrnToast('Add at least one item or select a PO/Vendor to save a draft.', 'warning');
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
            activeRouteStepIndex: window.StockInStore.activeRouteStepIndex || 0,
            unlockedRouteStepIndex: window.StockInStore.unlockedRouteStepIndex || 0,
            completedRouteSteps: Array.isArray(window.StockInStore.completedRouteSteps) ? window.StockInStore.completedRouteSteps : [],
            qaInspectionState: Object.assign({}, window.StockInStore.qaInspectionState || {}),
            items,
        };

        localStorage.setItem('rms_grn_draft_v1', JSON.stringify(draft));

        const ind = document.getElementById('grnDraftIndicator');
        const indTxt = document.getElementById('grnDraftIndicatorText');
        if (ind && indTxt) {
            ind.hidden = false;
            indTxt.textContent = isManual ? 'Draft saved' : 'Draft autosaved';
        }
        if (isManual) {
            showGrnToast('Intake sheet draft saved locally');
        }
    } catch (e) {
        console.warn('Could not save GRN draft:', e);
    }
}

window.saveGrnDraftManual = function() {
    saveGrnDraft(true);
};

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
        if (typeof window.syncGrnPoPickerLabel === 'function') window.syncGrnPoPickerLabel();
        if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();
        if (draft.vendorId) {
            const vSelect = document.getElementById('intakeVendorSelect');
            if (vSelect) vSelect.value = draft.vendorId;
        }
        if (draft.movementTypeId) {
            refreshSyncedRouteCatalog();
            const mtSelect = document.getElementById('intakeMovementTypeSelect');
            if (mtSelect && Array.from(mtSelect.options).some(o => o.value === draft.movementTypeId)) {
                mtSelect.value = draft.movementTypeId;
                const mt = window.StockInStore.movementTypes.find(m => m.id === draft.movementTypeId);
                if (mt) {
                    if (mt.warehouse_id) {
                        const whInput = document.getElementById('intakeWarehouseSelect');
                        if (whInput) whInput.value = mt.warehouse_id;
                    }
                    resetRouteStepProgress(mt);
                    const maxIdx = Math.max(0, (mt.steps || []).length - 1);
                    const unlockedIdx = Math.max(0, Math.min(Number(draft.unlockedRouteStepIndex || 0), maxIdx));
                    const activeIdx = Math.max(0, Math.min(Number(draft.activeRouteStepIndex ?? unlockedIdx), unlockedIdx));
                    window.StockInStore.unlockedRouteStepIndex = unlockedIdx;
                    window.StockInStore.activeRouteStepIndex = activeIdx;
                    window.StockInStore.completedRouteSteps = Array.isArray(draft.completedRouteSteps)
                        ? draft.completedRouteSteps.filter(i => Number.isInteger(i) && i >= 0 && i < unlockedIdx)
                        : [];
                    if (draft.qaInspectionState && typeof draft.qaInspectionState === 'object') {
                        window.StockInStore.qaInspectionState = Object.assign({}, window.StockInStore.qaInspectionState, draft.qaInspectionState);
                        syncQaInputsFromStore();
                    }
                    updateRouteIndicator(mt);
                    populateRouteLocationDropdowns(mt);
                }
            }
        }
        const srcSelect = document.getElementById('intakeSourceLocationSelect');
        if (draft.sourceLocation && srcSelect) {
            ensureGrnSelectOption(srcSelect, draft.sourceLocation);
        }
        const destSelect = document.getElementById('intakeDestinationLocationSelect');
        if (draft.destinationLocation && destSelect) {
            ensureGrnSelectOption(destSelect, draft.destinationLocation);
        }
        renderGrnChevronPipeline();
        if (draft.expectedDelivery) {
            const eta = document.getElementById('intakeExpectedDelivery');
            if (eta) eta.value = draft.expectedDelivery;
        }
        if (draft.receivedAt) {
            const rcv = document.getElementById('intakeReceivedAt');
            if (rcv) rcv.value = String(draft.receivedAt).slice(0, 10);
        }
        if (draft.deliverySlip) {
            const slip = document.getElementById('intakeDeliverySlip');
            if (slip) slip.value = draft.deliverySlip;
        }
        if (draft.deliveryNotes) {
            const notes = document.getElementById('intakeDeliveryNotes');
            if (notes) notes.value = draft.deliveryNotes;
        }

        const rawItems = Array.isArray(draft.items) ? draft.items : [];
        let reconciledNotice = null;
        if (draft.poNumber) {
            const livePo = (window.StockInStore.purchaseOrders || []).find(p => p.po_number === draft.poNumber);
            if (livePo) {
                const liveStatusUpper = String(livePo.status || '').toUpperCase();
                if (liveStatusUpper === 'FULLY RECEIVED' || liveStatusUpper === 'FULFILLED') {
                    localStorage.removeItem('rms_grn_draft_v1');
                    const banner = document.getElementById('grnResumeBanner');
                    if (banner) banner.hidden = true;
                    openPoInIntakeBuilder(livePo.po_number, true);
                    showGrnToast(`Offline draft discarded: ${livePo.po_number} was already Fully Received.`, 'warning');
                    return;
                }
                const liveLines = Array.isArray(livePo.items) ? livePo.items : [];
                rawItems.forEach(it => {
                    const liveLine = liveLines.find(pl =>
                        (it.purchase_order_item_id && pl.id === it.purchase_order_item_id) || pl.sku === it.sku
                    );
                    if (liveLine) {
                        const livePrev = Number(liveLine.received_quantity || 0);
                        const ordered = Number(liveLine.quantity ?? it.ordered_qty ?? 0);
                        const remaining = Math.max(0, ordered - livePrev);
                        if (Number(it.prev_recv_qty || 0) !== livePrev) {
                            reconciledNotice = `Reconciled offline draft with latest receipts on ${livePo.po_number}.`;
                        }
                        it.purchase_order_item_id = liveLine.id ?? it.purchase_order_item_id ?? null;
                        it.ordered_qty = ordered;
                        it.prev_recv_qty = livePrev;
                        it.receive_now = Math.min(Number(it.receive_now || 0), remaining);
                        it.balance = Math.max(0, remaining - it.receive_now);
                    }
                });
            }
        }

        window.StockInStore.intakeItems = rawItems;

        const banner = document.getElementById('grnResumeBanner');
        if (banner) banner.hidden = true;

        switchGrnMode('builder');
        renderIntakeTable();
        updateFulfillmentProgress();
        updateLiveMockup();
        updateTimelinessBadge();
        showGrnToast(reconciledNotice || 'Resumed intake draft successfully', reconciledNotice ? 'warning' : 'success');
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
            const trackingMode = window.getItemMasterTrackingMode(matchedProd);
            const lotEnabled = trackingMode === 'lots';
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
                trackingBy: trackingMode,
                lot_number: lotEnabled ? `LOT-${dateStr}-${nextIdx.toString().padStart(2, '0')}` : '',
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
    const activePoNum = window.StockInStore.activePoNumber || document.getElementById('intakePoSelect')?.value || '';
    const etaVal = normalizeIsoDate(document.getElementById('intakeExpectedDelivery')?.value || '');
    if (activePoNum && etaVal) {
        const activePo = (window.StockInStore.purchaseOrders || []).find(p => p.po_number === activePoNum);
        if (activePo) {
            activePo.expected_delivery = etaVal;
            activePo.expected_delivery_date = etaVal;
            renderOrdersQueue();
        }
    }
    updateTimelinessBadge();
    syncGrnStepActionButtons();
    scheduleLiveMockupUpdate();
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
    const recvDate = new Date(String(recvVal).includes('T') ? recvVal : `${recvVal}T12:00:00`);

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

function ensureGrnSelectOption(selectEl, value) {
    if (!selectEl || !value) return;
    const exists = Array.from(selectEl.options || []).some(opt => opt.value === value);
    if (!exists) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        selectEl.appendChild(opt);
    }
    selectEl.value = value;
}

function getActiveRouteSteps(movementType) {
    const mt = movementType || getActiveInboundMovementType();
    return (Array.isArray(mt?.steps) && mt.steps.length > 0)
        ? mt.steps
        : [{ step_no: 1, name: 'Receiving & Putaway', from_area: 'Supplier / Vendor Origin (Boundary)', to_area: 'Stock Zone A (Dry Goods)' }];
}

function isStepQaInspection(step, movementType) {
    const text = `${step?.name || ''} ${step?.to_area || ''}`.toLowerCase();
    return /qa|qc|quality|inspect|temp/.test(text);
}

function isColdChainMovement(movementType) {
    const mt = movementType || getActiveInboundMovementType();
    const stepsText = (mt?.steps || []).map(s => `${s?.name || ''} ${s?.to_area || ''}`).join(' ');
    return /cold|chill|freez|meat|dairy/i.test(`${mt?.code || ''} ${mt?.name || ''} ${stepsText}`);
}

function syncQaInputsFromStore() {
    const qa = window.StockInStore.qaInspectionState || {};
    const tempInput = document.getElementById('grnQaTempInput');
    const resultSel = document.getElementById('grnQaResultSelect');
    const checkEl = document.getElementById('grnQaChecklistVerified');
    if (tempInput) tempInput.value = qa.tempC ?? '2.4';
    if (resultSel) resultSel.value = qa.result || 'PASS';
    if (checkEl) checkEl.checked = qa.verified !== false;
}

function resetRouteStepProgress(movementType) {
    window.StockInStore.activeRouteStepIndex = 0;
    window.StockInStore.unlockedRouteStepIndex = 0;
    window.StockInStore.completedRouteSteps = [];
    window.StockInStore.qaInspectionState = {
        verified: true,
        tempC: isColdChainMovement(movementType) ? '2.4' : '22.0',
        result: 'PASS',
        quarantined: false,
    };
    syncQaInputsFromStore();
}

function isViewingCompletedStepReadOnly() {
    if (window.StockInStore.isCurrentGrnPosted) return false;
    const activeIdx = Number(window.StockInStore.activeRouteStepIndex || 0);
    const unlockedIdx = Number(window.StockInStore.unlockedRouteStepIndex || 0);
    return activeIdx < unlockedIdx;
}

window.isIntakeSheetLocked = function() {
    return isViewingCompletedStepReadOnly() || !!window.StockInStore.isCurrentGrnPosted;
};

function isExpiredDate(dateStr) {
    if (!dateStr) return false;
    const expDate = new Date(String(dateStr).includes('T') ? dateStr : `${dateStr}T00:00:00`);
    if (isNaN(expDate.getTime())) return true;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return expDate < today;
}

function evaluateGrnStepValidation() {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), steps.length - 1));
    const activeStep = steps[activeIdx] || {};
    const items = window.StockInStore.intakeItems || [];
    const receivingItems = items.filter(it => Number(it.receive_now || 0) > 0);

    if (receivingItems.length === 0) {
        return { valid: false, reason: 'Enter a "Receive Now" quantity greater than 0 for at least one item.' };
    }

    const missingLotItem = receivingItems.find(it =>
        window.isLotTrackingEnabledForItem(it) && !String(it.lot_number || '').trim()
    );
    if (missingLotItem) {
        return { valid: false, reason: `Mandatory Batch / Lot # is empty for ${missingLotItem.sku}.` };
    }

    const expiredItem = receivingItems.find(it =>
        window.isLotTrackingEnabledForItem(it) && it.expiry_date && isExpiredDate(it.expiry_date)
    );
    if (expiredItem) {
        return { valid: false, reason: `Expiry date for ${expiredItem.sku} is invalid or already expired.` };
    }

    if (isStepQaInspection(activeStep, mt)) {
        const qa = window.StockInStore.qaInspectionState || {};
        if (qa.quarantined || qa.result === 'FAIL') {
            return { valid: false, reason: 'QA disposition is FAIL / Quarantined. Switch Disposition to Pass or keep in Quarantine.' };
        }
        if (!qa.verified) {
            return { valid: false, reason: 'Verify the QA & Lot inspection checklist before proceeding.' };
        }
        const tempStr = String(qa.tempC ?? '').trim();
        const tempNum = parseFloat(tempStr);
        if (tempStr === '' || Number.isNaN(tempNum)) {
            return { valid: false, reason: 'Enter a valid QA temperature reading (°C).' };
        }
        if (isColdChainMovement(mt) && tempNum > 8) {
            return { valid: false, reason: `Cold-chain temperature (${tempNum}°C) exceeds max safe threshold (8.0°C).` };
        }
    }

    if (activeIdx === steps.length - 1) {
        const destVal = (document.getElementById('intakeDestinationLocationSelect')?.value || '').trim();
        if (!destVal) {
            return { valid: false, reason: 'Select a target storage bin / destination before posting GRN.' };
        }
    }

    return { valid: true, reason: '' };
}

function syncGrnStepActionButtons() {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const totalSteps = steps.length;
    const unlockedIdx = Math.max(0, Math.min(Number(window.StockInStore.unlockedRouteStepIndex || 0), totalSteps - 1));
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), unlockedIdx));
    window.StockInStore.unlockedRouteStepIndex = unlockedIdx;
    window.StockInStore.activeRouteStepIndex = activeIdx;

    const activeStep = steps[activeIdx] || {};
    const isPosted = !!window.StockInStore.isCurrentGrnPosted;
    const isViewingHistory = !isPosted && (activeIdx < unlockedIdx);
    const isReadOnly = isViewingHistory || isPosted;
    const isQcStep = isStepQaInspection(activeStep, mt);
    const validation = evaluateGrnStepValidation();

    const roBanner = document.getElementById('grnReadOnlyStepBanner');
    const roText = document.getElementById('grnReadOnlyStepText');
    const roBtnLabel = document.getElementById('btnBannerReturnActiveStepLabel');
    if (roBanner) {
        roBanner.hidden = !isViewingHistory;
        if (roText && isViewingHistory) {
            const label = getGrnStepShortLabel(activeStep, activeIdx, totalSteps);
            roText.textContent = `Viewing completed Step ${activeIdx + 1} (${label}) in read-only mode.`;
        }
        if (roBtnLabel) {
            roBtnLabel.textContent = `Return to Active Step [Step ${unlockedIdx + 1}]`;
        }
    }

    const qaBar = document.getElementById('grnQaInspectionBar');
    if (qaBar) {
        qaBar.hidden = !isQcStep || isReadOnly;
    }

    ['intakePoSelect', 'intakeVendorSelect', 'intakeMovementTypeSelect', 'intakeExpectedDelivery', 'intakeReceivedAt', 'intakeDeliverySlip', 'intakeDeliveryNotes']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = isPosted;
        });
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();

    const srcSelect = document.getElementById('intakeSourceLocationSelect');
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    const scanInput = document.getElementById('grnScanInput');
    const unplannedBtn = document.getElementById('btnAddUnplannedItem') || document.querySelector('[onclick="addAdHocIntakeRow()"]');
    if (srcSelect) srcSelect.disabled = isReadOnly || (srcSelect.options?.length || 0) <= 1;
    if (destSelect) destSelect.disabled = isReadOnly;
    if (scanInput) scanInput.disabled = isReadOnly;
    if (unplannedBtn) unplannedBtn.disabled = isReadOnly;
    if (typeof window.syncCombinedRouteDropdown === 'function') window.syncCombinedRouteDropdown();

    const btnSaveDraft = document.getElementById('btnFooterSaveDraft');
    const btnSaveDraftLabel = document.getElementById('btnFooterSaveDraftLabel');
    const btnAttachProof = document.getElementById('btnGrnAttachProof');
    const btnRejectReturn = document.getElementById('btnRejectReturnGrn');
    if (btnAttachProof) {
        btnAttachProof.hidden = isPosted;
        btnAttachProof.disabled = isReadOnly;
    }
    if (btnRejectReturn) {
        btnRejectReturn.hidden = isPosted;
        btnRejectReturn.disabled = isViewingHistory;
    }
    if (btnSaveDraft) {
        btnSaveDraft.hidden = isPosted;
        btnSaveDraft.disabled = isViewingHistory;
        btnSaveDraft.title = 'Save intake draft locally';
    }
    if (btnSaveDraftLabel) {
        btnSaveDraftLabel.textContent = 'Save Draft';
    }

    const btnFailQa = document.getElementById('btnFailQaQuarantine');
    const btnConfirm = document.getElementById('btnConfirmIntake');
    const btnLabel = document.getElementById('btnConfirmIntakeLabel');
    if (!btnConfirm) return;

    if (isPosted) {
        if (btnFailQa) btnFailQa.hidden = true;
        if (btnLabel) btnLabel.textContent = 'Print GRN (A4)';
        btnConfirm.classList.add('is-final-step');
        btnConfirm.disabled = false;
        btnConfirm.title = 'Preview & Print A4 Goods Receipt Note';
    } else if (isViewingHistory) {
        if (btnFailQa) btnFailQa.hidden = true;
        if (btnLabel) btnLabel.textContent = `Return to Active Step [Step ${unlockedIdx + 1}]`;
        btnConfirm.classList.remove('is-final-step');
        btnConfirm.disabled = false;
        btnConfirm.title = `Return to Active Step [Step ${unlockedIdx + 1}]`;
    } else if (totalSteps === 1) {
        if (btnFailQa) btnFailQa.hidden = true;
        if (btnLabel) btnLabel.textContent = 'Confirm & Post GRN';
        btnConfirm.classList.add('is-final-step');
        btnConfirm.disabled = !validation.valid;
        btnConfirm.title = validation.valid ? 'Confirm & Post GRN (F10)' : validation.reason;
    } else if (activeIdx === 0) {
        if (btnFailQa) btnFailQa.hidden = true;
        if (btnLabel) btnLabel.textContent = 'Confirm Dock Receipt →';
        btnConfirm.classList.remove('is-final-step');
        btnConfirm.disabled = !validation.valid;
        btnConfirm.title = validation.valid ? 'Confirm Dock Receipt & Proceed to Step 2 (F10)' : validation.reason;
    } else if (activeIdx < totalSteps - 1) {
        if (btnFailQa) btnFailQa.hidden = false;
        if (btnLabel) btnLabel.textContent = isQcStep ? 'Pass QA & Proceed →' : `Confirm Step ${activeIdx + 1} & Proceed →`;
        btnConfirm.classList.remove('is-final-step');
        btnConfirm.disabled = !validation.valid;
        btnConfirm.title = validation.valid ? 'Confirm inspection & proceed to next step (F10)' : validation.reason;
    } else {
        if (btnFailQa) btnFailQa.hidden = true;
        if (btnLabel) btnLabel.textContent = 'Confirm Putaway & Post GRN';
        btnConfirm.classList.add('is-final-step');
        btnConfirm.disabled = !validation.valid;
        btnConfirm.title = validation.valid ? 'Confirm Putaway & Post GRN (F10)' : validation.reason;
    }
}

window.onGrnQaParamChanged = function() {
    const tempInput = document.getElementById('grnQaTempInput');
    const resultSel = document.getElementById('grnQaResultSelect');
    const checkEl = document.getElementById('grnQaChecklistVerified');
    const resultVal = resultSel?.value || 'PASS';
    window.StockInStore.qaInspectionState = {
        tempC: tempInput ? tempInput.value : '2.4',
        result: resultVal,
        verified: checkEl ? !!checkEl.checked : true,
        quarantined: resultVal === 'FAIL',
    };
    if (resultVal === 'PASS') {
        const mt = getActiveInboundMovementType();
        const steps = getActiveRouteSteps(mt);
        const curStep = steps[window.StockInStore.activeRouteStepIndex || 0];
        const destSelect = document.getElementById('intakeDestinationLocationSelect');
        if (destSelect && curStep?.to_area) {
            ensureGrnSelectOption(destSelect, curStep.to_area);
        }
        updateFulfillmentProgress();
    } else {
        syncGrnStepActionButtons();
    }
    updateLiveMockup();
    scheduleDraftSave();
};

window.failGrnQaAndQuarantine = function() {
    window.StockInStore.qaInspectionState = Object.assign({}, window.StockInStore.qaInspectionState || {}, {
        result: 'FAIL',
        quarantined: true,
    });
    const resultSel = document.getElementById('grnQaResultSelect');
    if (resultSel) resultSel.value = 'FAIL';

    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    if (destSelect) ensureGrnSelectOption(destSelect, 'QA Quarantine & Hold Cage');

    const statusChip = document.getElementById('grnStatusChip');
    const statusChipText = document.getElementById('grnStatusChipText');
    if (statusChip && statusChipText) {
        statusChip.setAttribute('data-state', 'blocked');
        statusChipText.textContent = 'QA Failed · Quarantined';
    }
    const statusTitle = document.getElementById('grnStepStatusTitle');
    const statusDesc = document.getElementById('grnStepStatusDesc');
    if (statusTitle) statusTitle.textContent = 'Quarantined';
    if (statusDesc) statusDesc.textContent = 'Held at QA Bench';

    syncGrnStepActionButtons();
    updateLiveMockup();
    scheduleDraftSave();
    showGrnToast('QA Failed: Batch moved to Quarantine hold. Posting blocked.', 'danger');
};

window.returnToActiveGrnStep = function() {
    const unlockedIdx = Number(window.StockInStore.unlockedRouteStepIndex || 0);
    selectGrnRouteStep(unlockedIdx);
};

let grnStageTransitTimer = null;

function getShortageLinesAndSignature() {
    const items = Array.isArray(window.StockInStore.intakeItems) ? window.StockInStore.intakeItems : [];
    const shortLines = [];
    let totalExpected = 0;
    let totalReceiving = 0;
    let totalShortage = 0;

    items.forEach(it => {
        const ordered = Number(it.ordered_qty || 0);
        const prev = Number(it.prev_recv_qty || 0);
        const remaining = Math.max(0, ordered - prev);
        const recvNow = Math.max(0, Number(it.receive_now || 0));
        if (ordered > 0 && remaining > 0) {
            totalExpected += remaining;
            totalReceiving += recvNow;
            if (recvNow < remaining) {
                const shortQty = Math.max(0, remaining - recvNow);
                totalShortage += shortQty;
                shortLines.push({
                    sku: it.sku || '',
                    name: it.name || it.sku || 'Item',
                    uom: it.uom || 'PCS',
                    remaining,
                    receiving: recvNow,
                    shortage: shortQty,
                });
            }
        }
    });

    const isPartial = !!window.StockInStore.activePoNumber && shortLines.length > 0;
    const signature = isPartial
        ? `${window.StockInStore.activePoNumber}|${shortLines.map(l => `${l.sku}:${l.receiving.toFixed(4)}/${l.remaining.toFixed(4)}`).join(';')}`
        : null;

    return { isPartial, shortLines, totalExpected, totalReceiving, totalShortage, signature };
}

function getGrnWorkflowStageNames(mt) {
    const steps = getActiveRouteSteps(mt || getActiveInboundMovementType());
    const hasQc = steps.some(s => isStepQaInspection(s, mt));
    return hasQc
        ? ['Vendor', 'Receipt', 'QC Inspection', 'Stocks']
        : ['Vendor', 'Receipt', 'Stocks'];
}

window.triggerGrnStageMovementSimulation = function(fromLabel, toLabel, curIdx, nextIdx, totalSteps) {
    const banner = document.getElementById('grnStageTransitBanner');
    const fromEl = document.getElementById('grnTransitFromLabel');
    const toEl = document.getElementById('grnTransitToLabel');
    const unitsEl = document.getElementById('grnTransitUnitsBadge');
    const chipEl = document.getElementById('grnTransitStageChip');

    const totalMoving = (window.StockInStore.intakeItems || []).reduce((acc, it) => acc + Math.max(0, Number(it.receive_now || 0)), 0);

    if (fromEl) fromEl.textContent = fromLabel || 'Vendor / Origin';
    if (toEl) toEl.textContent = toLabel || 'Next Stage';
    if (unitsEl) unitsEl.textContent = `${totalMoving.toFixed(2)} Units`;
    if (chipEl) {
        chipEl.textContent = nextIdx >= totalSteps
            ? 'Final Putaway → Stocks'
            : `Stage ${curIdx + 1} → Stage ${nextIdx + 1}`;
    }

    if (banner) {
        banner.hidden = false;
        banner.classList?.remove('is-animating');
        if (typeof banner.offsetWidth !== 'undefined') void banner.offsetWidth;
        banner.classList?.add('is-animating');
        clearTimeout(grnStageTransitTimer);
        grnStageTransitTimer = setTimeout(() => {
            banner.classList?.remove('is-animating');
            banner.hidden = true;
        }, 1250);
    }

    const pipeline = document.getElementById('grnLifecycleProgressBar');
    if (pipeline) {
        pipeline.classList?.remove('is-transiting');
        if (typeof pipeline.offsetWidth !== 'undefined') void pipeline.offsetWidth;
        pipeline.classList?.add('is-transiting');
        setTimeout(() => pipeline.classList?.remove('is-transiting'), 700);
    }

    if (typeof document !== 'undefined' && typeof document.querySelectorAll === 'function') {
        const rows = document.querySelectorAll('#intakeTableBody tr');
        rows.forEach((tr, idx) => {
            tr.classList?.remove('grn-row-transit-wave');
            if (typeof tr.offsetWidth !== 'undefined') void tr.offsetWidth;
            tr.style.animationDelay = `${Math.min(idx * 35, 220)}ms`;
            tr.classList?.add('grn-row-transit-wave');
        });
    }
};

window.syncGrnBackorderStageBanner = function() {
    const banner = document.getElementById('grnBackorderStageBanner');
    if (!banner) return;

    const poNum = window.StockInStore.activePoNumber;
    const po = poNum ? (window.StockInStore.purchaseOrders || []).find(p => p.po_number === poNum) : null;
    const mt = getActiveInboundMovementType();
    const stageChain = getGrnWorkflowStageNames(mt).join(' → ');
    const flowEl = document.getElementById('grnBackorderFlowStepsText');
    const titleEl = document.getElementById('grnBackorderBannerTitle');
    const textEl = document.getElementById('grnBackorderBannerText');
    const startBtn = document.getElementById('btnStartNextBackorderStage');

    if (flowEl) flowEl.textContent = stageChain;

    // Case 1: PO is already posted as Partially Received -> offer 1-click Backorder Intake starting at Stage 1
    if (po && window.StockInStore.isCurrentGrnPosted && po.status === 'Partially Received') {
        const pendingTotal = (po.items || []).reduce((sum, it) => sum + Math.max(0, Number(it.quantity || 0) - Number(it.received_quantity || 0)), 0);
        if (pendingTotal > 0) {
            banner.hidden = false;
            if (titleEl) titleEl.textContent = `Backorder Queued (${pendingTotal.toFixed(2)} Units Remaining)`;
            if (textEl) textEl.textContent = `Next backorder delivery for ${po.po_number} will start from Stage 1 (${stageChain}).`;
            if (startBtn) startBtn.hidden = false;
            return;
        }
    }

    // Case 2: Currently mid-flow with CREATE_BACKORDER policy confirmed on a partial delivery
    const { isPartial, totalShortage, signature } = getShortageLinesAndSignature();
    if (poNum && !window.StockInStore.isCurrentGrnPosted && isPartial && window.StockInStore.confirmedShortageSignature === signature && window.StockInStore.backorderPolicy === 'CREATE_BACKORDER') {
        banner.hidden = false;
        if (titleEl) titleEl.textContent = `Backorder Active (${totalShortage.toFixed(2)} Units Backordered)`;
        if (textEl) textEl.textContent = `Current delivery moves through stages now; remainder re-enters at Stage 1 (${stageChain}).`;
        if (startBtn) startBtn.hidden = true;
        return;
    }

    banner.hidden = true;
    if (startBtn) startBtn.hidden = true;
};

window.startBackorderStageFlow = function() {
    const poNum = window.StockInStore.activePoNumber;
    if (!poNum) return;
    window.StockInStore.backorderPolicy = 'CREATE_BACKORDER';
    window.StockInStore.confirmedShortageSignature = null;
    openPoInIntakeBuilder(poNum, false);
    showGrnToast(`Started Backorder Intake for ${poNum} at Stage 1 (Vendor → Receipt)`);
};

window.selectGrnBoPolicyOption = function(policy) {
    const chosen = policy === 'SHORT_CLOSE' ? 'SHORT_CLOSE' : 'CREATE_BACKORDER';
    window.StockInStore.backorderPolicy = chosen;
    const modal = document.getElementById('grnBackorderModal');
    if (modal && typeof modal.querySelectorAll === 'function') {
        modal.querySelectorAll('.grn-bo-option-card').forEach(card => {
            const isSel = card.getAttribute('data-bo-policy') === chosen;
            card.classList?.toggle('is-selected', isSel);
            card.setAttribute('aria-checked', isSel ? 'true' : 'false');
            const iconWrap = card.querySelector('.grn-bo-opt-radio i');
            if (iconWrap) {
                iconWrap.className = isSel ? 'ph-fill ph-check-circle' : 'ph ph-circle';
            }
        });
    }
};

window.openGrnBackorderModal = function(stageContextLabel, onConfirmCallback) {
    const modal = document.getElementById('grnBackorderModal');
    const summary = getShortageLinesAndSignature();
    if (!modal || !summary.isPartial) {
        if (typeof onConfirmCallback === 'function') onConfirmCallback();
        return;
    }

    window.StockInStore.pendingBackorderContinuation = onConfirmCallback || null;

    const mt = getActiveInboundMovementType();
    const stageNames = getGrnWorkflowStageNames(mt);
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), stageNames.length - 1));

    const subEl = document.getElementById('grnBoModalSubtitle');
    if (subEl) subEl.textContent = `${window.StockInStore.activePoNumber} • ${stageContextLabel || 'Stage Transition'}`;

    const flowEl = document.getElementById('grnBoModalStageFlow');
    if (flowEl) {
        flowEl.innerHTML = stageNames.map((name, i) => {
            const activeCls = i === activeIdx || i === activeIdx + 1 ? 'is-active-stage' : '';
            const arrow = i < stageNames.length - 1 ? '<i class="ph ph-arrow-right grn-bo-stage-arrow" aria-hidden="true"></i>' : '';
            return `<span class="grn-bo-stage-pill ${activeCls}">${escapeHtml(name)}</span>${arrow}`;
        }).join('');
    }

    const expEl = document.getElementById('grnBoExpectedUnits');
    const recEl = document.getElementById('grnBoReceivingUnits');
    const shEl = document.getElementById('grnBoShortageUnits');
    if (expEl) expEl.textContent = summary.totalExpected.toFixed(2);
    if (recEl) recEl.textContent = summary.totalReceiving.toFixed(2);
    if (shEl) shEl.textContent = summary.totalShortage.toFixed(2);

    const listEl = document.getElementById('grnBoLinesList');
    if (listEl) {
        listEl.innerHTML = summary.shortLines.map(l => `
            <div class="grn-bo-line-row">
                <span class="grn-bo-line-name">${escapeHtml(l.sku ? `${l.sku} — ${l.name}` : l.name)}</span>
                <span class="grn-bo-line-delta">Receiving ${l.receiving.toFixed(2)} / ${l.remaining.toFixed(2)} ${escapeHtml(l.uom)} · Backorder: ${l.shortage.toFixed(2)}</span>
            </div>
        `).join('');
    }

    selectGrnBoPolicyOption(window.StockInStore.backorderPolicy || 'CREATE_BACKORDER');
    modal.style.display = 'flex';
};

window.closeGrnBackorderModal = function() {
    const modal = document.getElementById('grnBackorderModal');
    if (modal) modal.style.display = 'none';
    window.StockInStore.pendingBackorderContinuation = null;
};

window.confirmGrnBackorderModalChoice = function() {
    const { signature } = getShortageLinesAndSignature();
    window.StockInStore.confirmedShortageSignature = signature;
    const modal = document.getElementById('grnBackorderModal');
    if (modal) modal.style.display = 'none';
    const cb = window.StockInStore.pendingBackorderContinuation;
    window.StockInStore.pendingBackorderContinuation = null;
    syncGrnBackorderStageBanner();
    if (typeof cb === 'function') cb();
};

function advanceGrnRouteStep() {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const curIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), steps.length - 1));
    if (curIdx >= steps.length - 1) return;

    const completed = new Set(window.StockInStore.completedRouteSteps || []);
    completed.add(curIdx);
    window.StockInStore.completedRouteSteps = Array.from(completed).sort((a, b) => a - b);

    const nextIdx = curIdx + 1;
    window.StockInStore.unlockedRouteStepIndex = Math.max(window.StockInStore.unlockedRouteStepIndex || 0, nextIdx);
    window.StockInStore.activeRouteStepIndex = nextIdx;

    const nextStep = steps[nextIdx];
    const srcSelect = document.getElementById('intakeSourceLocationSelect');
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    if (nextStep) {
        if (srcSelect && nextStep.from_area) ensureGrnSelectOption(srcSelect, nextStep.from_area);
        if (destSelect && nextStep.to_area) ensureGrnSelectOption(destSelect, nextStep.to_area);
    }

    renderGrnChevronPipeline(mt);
    renderIntakeTable();
    updateLiveMockup();
    syncGrnBackorderStageBanner();
    scheduleDraftSave();

    const completedLabel = getGrnStepShortLabel(steps[curIdx], curIdx, steps.length);
    const nextLabel = getGrnStepShortLabel(nextStep, nextIdx, steps.length);
    const fromZone = curIdx === 0 ? `Vendor → ${steps[curIdx]?.to_area || completedLabel}` : (steps[curIdx]?.to_area || completedLabel);
    const toZone = nextStep?.to_area || nextLabel;
    triggerGrnStageMovementSimulation(fromZone, toZone, curIdx, nextIdx, steps.length);

    showGrnToast(`Moved stock to Step ${nextIdx + 1}: ${nextLabel}`);
}

window.handleGrnSecondaryAction = function() {
    if (window.StockInStore.isCurrentGrnPosted) {
        switchGrnMode('queue');
        return;
    }
    saveGrnDraftManual();
};

window.onGrnProofAttached = function(input) {
    const files = input?.files ? Array.from(input.files) : [];
    const lbl = document.getElementById('btnGrnAttachProofLabel');
    if (files.length > 0) {
        window.StockInStore.attachedProofFiles = files.map(f => f.name);
        if (lbl) lbl.textContent = `Proof (${files.length})`;
        showGrnToast(`Attached ${files.length} proof file${files.length > 1 ? 's' : ''}`);
    }
};

window.handleGrnRejectOrReturn = function() {
    if (window.StockInStore.isCurrentGrnPosted) {
        switchGrnMode('queue');
        return;
    }
    failGrnQaAndQuarantine();
};

window.handleGrnPrimaryStepAction = function() {
    if (window.StockInStore.isCurrentGrnPosted) {
        openGrnDocumentPreview();
        return;
    }

    if (isViewingCompletedStepReadOnly()) {
        returnToActiveGrnStep();
        return;
    }

    const validation = evaluateGrnStepValidation();
    if (!validation.valid) {
        showGrnToast(validation.reason, 'warning');
        return;
    }

    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (!vendorSelect || !vendorSelect.value) {
        showGrnToast('Please select a Vendor Partner before proceeding.', 'warning');
        return;
    }

    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), steps.length - 1));
    const currentStepLabel = getGrnStepShortLabel(steps[activeIdx], activeIdx, steps.length);

    // Trigger Backorder / Partial Shipment Resolution at the earliest stage where shortage occurs (Vendor > Receipt > QC > Stocks)
    const shortageSummary = getShortageLinesAndSignature();
    if (shortageSummary.isPartial && window.StockInStore.confirmedShortageSignature !== shortageSummary.signature) {
        const boModal = document.getElementById('grnBackorderModal');
        if (boModal) {
            openGrnBackorderModal(`Stage ${activeIdx + 1}: ${currentStepLabel}`, () => {
                if (activeIdx < steps.length - 1) {
                    advanceGrnRouteStep();
                } else {
                    confirmAndCommitStockIntake(true);
                }
            });
            return;
        }
    }

    if (activeIdx < steps.length - 1) {
        advanceGrnRouteStep();
        return;
    }

    confirmAndCommitStockIntake(true);
};

// Route location listeners
window.onIntakeSourceLocationChanged = function(val) {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const unlockedIdx = Number(window.StockInStore.unlockedRouteStepIndex || 0);
    const activeIdx = Number(window.StockInStore.activeRouteStepIndex || 0);
    const matchedIdx = steps.findIndex(s => s.from_area === val);
    if (matchedIdx > unlockedIdx) {
        const srcSelect = document.getElementById('intakeSourceLocationSelect');
        if (srcSelect && steps[activeIdx]?.from_area) {
            ensureGrnSelectOption(srcSelect, steps[activeIdx].from_area);
        }
        showGrnToast(`Step ${matchedIdx + 1} is locked. Complete Step ${unlockedIdx + 1} first.`, 'warning');
        return;
    }
    syncGrnStepActionButtons();
    updateLiveMockup();
    scheduleDraftSave();
};

window.onIntakeDestinationLocationChanged = function(val) {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const unlockedIdx = Number(window.StockInStore.unlockedRouteStepIndex || 0);
    const activeIdx = Number(window.StockInStore.activeRouteStepIndex || 0);
    const matchedIdx = steps.findIndex(s => s.to_area === val);
    if (matchedIdx > unlockedIdx) {
        const destSelect = document.getElementById('intakeDestinationLocationSelect');
        if (destSelect && steps[activeIdx]?.to_area) {
            ensureGrnSelectOption(destSelect, steps[activeIdx].to_area);
        }
        showGrnToast(`Step ${matchedIdx + 1} is locked. Complete Step ${unlockedIdx + 1} first.`, 'warning');
        return;
    }
    renderGrnChevronPipeline(mt);
    updateLiveMockup();
    scheduleDraftSave();
};

window.openPoInIntakeBuilder = function(poNumber, viewCompleted = false) {
    window.StockInStore.isCurrentGrnPosted = false;
    window.StockInStore.lastPostedGrnNumber = null;
    window.StockInStore.backorderPolicy = 'CREATE_BACKORDER';
    window.StockInStore.confirmedShortageSignature = null;
    window.StockInStore.pendingBackorderContinuation = null;
    const transitBanner = document.getElementById('grnStageTransitBanner');
    if (transitBanner) transitBanner.hidden = true;
    const po = window.StockInStore.purchaseOrders.find(p => p.po_number === poNumber);
    if (!po) return;

    const existingGrn = (window.StockInStore.goodsReceipts || []).find(g => g.po_number === poNumber);
    const isCompletedView = Boolean(viewCompleted || po.status === 'Fully Received');

    window.StockInStore.activePoNumber = poNumber;
    window.StockInStore.isCurrentGrnPosted = isCompletedView;
    window.StockInStore.lastPostedGrnNumber = isCompletedView
        ? (existingGrn?.grn_number || `GRN-${po.po_number.replace(/^PO-?/, '')}`)
        : null;

    const poSelect = document.getElementById('intakePoSelect');
    if (poSelect) {
        poSelect.value = po.po_number;
        if (poSelect.value !== po.po_number && po.po_number) {
            const label = `${po.po_number}${po.vendor_name ? ' — ' + po.vendor_name : ''}`;
            poSelect.innerHTML += `<option value="${escapeHtml(po.po_number)}" selected>${escapeHtml(label)}</option>`;
            poSelect.value = po.po_number;
        }
    }
    if (typeof window.syncGrnPoPickerLabel === 'function') window.syncGrnPoPickerLabel();

    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (vendorSelect) {
        vendorSelect.value = po.vendor_id || '';
        if (!vendorSelect.value && po.vendor_name) {
            vendorSelect.innerHTML += `<option value="${escapeHtml(po.vendor_id || 'VEN')}" selected>${escapeHtml(po.vendor_name)}</option>`;
        }
    }
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();

    const etaInput = document.getElementById('intakeExpectedDelivery');
    if (etaInput) {
        etaInput.value = getPoExpectedEtaIso(po);
    }

    const rcvInput = document.getElementById('intakeReceivedAt');
    if (rcvInput) {
        const existingArrIso = normalizeIsoDate(existingGrn?.received_at || existingGrn?.created_at || po.arrival_date || po.received_at || '');
        rcvInput.value = existingArrIso || getTodayIsoDate();
    }

    const slipInput = document.getElementById('intakeDeliverySlip');
    if (slipInput) slipInput.value = existingGrn?.delivery_slip || '';
    const notesInput = document.getElementById('intakeDeliveryNotes');
    if (notesInput) notesInput.value = existingGrn?.notes || '';

    let resolvedWhId = 'WH-MAIN';
    const locLower = (po.delivery_location || '').toLowerCase();
    const matchedWh = window.StockInStore.warehouses.find(w => 
        locLower.includes(w.id.toLowerCase()) || locLower.includes(w.name.toLowerCase()) || locLower.includes(w.code.toLowerCase())
    );
    if (matchedWh) resolvedWhId = matchedWh.id;

    const whSelect = document.getElementById('intakeWarehouseSelect');
    if (whSelect) whSelect.value = resolvedWhId;

    syncMovementTypesForWarehouse(resolvedWhId);

    if (isCompletedView) {
        const activeMt = getActiveInboundMovementType();
        const mtSteps = getActiveRouteSteps(activeMt);
        window.StockInStore.completedRouteSteps = mtSteps.map((_, idx) => idx);
        window.StockInStore.unlockedRouteStepIndex = Math.max(0, mtSteps.length - 1);
        window.StockInStore.activeRouteStepIndex = Math.max(0, mtSteps.length - 1);
        populateRouteLocationDropdowns(activeMt, false);
        renderGrnChevronPipeline(activeMt);
    }

    window.StockInStore.intakeItems = (po.items || []).map((it, idx) => {
        const ordered = Number(it.quantity || 0);
        const prev = Number(it.received_quantity || 0);
        const remaining = Math.max(0, ordered - prev);
        const grnItem = existingGrn?.items?.find(gi => gi.sku === it.sku);
        const displayReceiveNow = isCompletedView
            ? Number(grnItem?.receive_now ?? grnItem?.received_qty ?? (prev > 0 ? prev : ordered))
            : remaining;
        const displayPrevRecv = isCompletedView
            ? Math.max(0, prev - displayReceiveNow)
            : prev;
        const unitCost = Number(it.unit_price || it.cost_price || 0);

        const matchedProd = window.StockInStore.products.find(p => 
            p.sku === it.sku || p.item_code === it.sku || p.id === it.inventory_item_id
        );
        const cleanName = (it.item_name && it.item_name !== it.sku) 
            ? it.item_name 
            : (matchedProd?.name || it.item_name || it.sku);
        const cleanSpecs = it.specs || matchedProd?.description || matchedProd?.specs || '';
        const trackingMode = window.getItemMasterTrackingMode(matchedProd || { sku: it.sku, name: cleanName });
        const lotEnabled = trackingMode === 'lots';

        return {
            purchase_order_item_id: it.id || null,
            sku: it.sku || `SKU-${idx + 1}`,
            name: cleanName,
            specs: cleanSpecs,
            uom: it.uom || matchedProd?.uom || 'PCS',
            base_uom: matchedProd?.uom || it.uom || 'PCS',
            ordered_qty: ordered,
            prev_recv_qty: displayPrevRecv,
            receive_now: displayReceiveNow,
            balance: isCompletedView ? 0 : Math.max(0, ordered - prev - remaining),
            unit_cost: unitCost,
            vat_type: '12% VAT',
            trackingBy: trackingMode,
            lot_number: grnItem?.lot_number || (lotEnabled ? `LOT-${new Date().toISOString().slice(2, 10).replace(/-/g, '')}-${(idx + 1).toString().padStart(2, '0')}` : ''),
            expiry_date: grnItem?.expiry_date || '',
            mfg_date: grnItem?.mfg_date || '',
            notes: grnItem?.notes || '',
            is_adhoc: false,
        };
    });

    const docDisplay = document.getElementById('intakeDocNoDisplay');
    if (docDisplay) docDisplay.textContent = window.StockInStore.lastPostedGrnNumber || `GRN-${po.po_number.replace(/^PO-?/, '')}`;

    const modeBadge = document.getElementById('intakeModeBadge');
    const modeText = document.getElementById('intakeModeText');
    if (modeBadge && modeText) {
        modeBadge.className = isCompletedView ? 'grn-mode-tag awarded' : 'grn-mode-tag awarded';
        modeText.textContent = isCompletedView ? `Posted: ${window.StockInStore.lastPostedGrnNumber}` : `PO: ${po.po_number}`;
    }

    updateTimelinessBadge();
    syncGrnBackorderStageBanner();
    switchGrnMode('builder');
    if (isCompletedView) {
        showGrnToast(`Viewing completed GRN (${window.StockInStore.lastPostedGrnNumber}) for ${po.po_number}`);
    } else {
        showGrnToast(`Loaded ${window.StockInStore.intakeItems.length} lines from ${po.po_number}`);
        scheduleDraftSave();
    }
};

window.startDirectBypassIntake = function() {
    resetIntakeSheet(true);
    switchGrnMode('builder');
    showGrnToast('Started Direct Inbound Receiving (Bypass PO)');
};

window.resetIntakeSheet = function(addBlankRow = true) {
    window.StockInStore.activePoNumber = null;
    window.StockInStore.isCurrentGrnPosted = false;
    window.StockInStore.lastPostedGrnNumber = null;
    window.StockInStore.backorderPolicy = 'CREATE_BACKORDER';
    window.StockInStore.confirmedShortageSignature = null;
    window.StockInStore.pendingBackorderContinuation = null;
    const transitBanner = document.getElementById('grnStageTransitBanner');
    if (transitBanner) transitBanner.hidden = true;
    syncGrnBackorderStageBanner();

    const poSelect = document.getElementById('intakePoSelect');
    if (poSelect) poSelect.value = '';
    if (typeof window.syncGrnPoPickerLabel === 'function') window.syncGrnPoPickerLabel();

    const vendorSelect = document.getElementById('intakeVendorSelect');
    if (vendorSelect) vendorSelect.selectedIndex = 0;
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();

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
        modeBadge.className = 'grn-mode-tag sent';
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

function syncMovementTypesForWarehouse(whId, preserveSelection = false) {
    const mtSelect = document.getElementById('intakeMovementTypeSelect');
    if (!mtSelect) return;

    const prevMtId = mtSelect.value;
    const allTypes = getSyncedInboundMovementTypes();
    window.StockInStore.movementTypes = allTypes;

    const activeInbound = allTypes.filter(mt =>
        mt.is_active !== false && (mt.operation_kind === 'Inbound Receipt' || mt.stock_effect === 'Receipt')
    );
    const basePool = activeInbound.length > 0
        ? activeInbound
        : allTypes.filter(mt => mt.operation_kind === 'Inbound Receipt' || mt.stock_effect === 'Receipt');

    // Keep warehouse-matching routes first, but keep ALL active inbound routes selectable (1-Step, 2-Step, 3-Step, Custom)
    const available = [...basePool].sort((a, b) => {
        const prioA = a.warehouse_id === whId ? 0 : (!a.warehouse_id ? 1 : 2);
        const prioB = b.warehouse_id === whId ? 0 : (!b.warehouse_id ? 1 : 2);
        return prioA - prioB;
    });

    mtSelect.innerHTML = available.map(mt =>
        `<option value="${escapeHtml(mt.id)}">${escapeHtml(mt.code)} — ${escapeHtml(mt.name)}</option>`
    ).join('');

    if (available.length > 0) {
        const preservedMt = preserveSelection ? available.find(m => m.id === prevMtId) : null;
        const selectedMt = preservedMt || available[0];
        mtSelect.value = selectedMt.id;

        if (!preserveSelection) {
            const whInput = document.getElementById('intakeWarehouseSelect');
            if (whInput && selectedMt.warehouse_id) {
                whInput.value = selectedMt.warehouse_id;
            }
            resetRouteStepProgress(selectedMt);
        } else {
            const maxIdx = Math.max(0, getActiveRouteSteps(selectedMt).length - 1);
            window.StockInStore.unlockedRouteStepIndex = Math.max(0, Math.min(window.StockInStore.unlockedRouteStepIndex || 0, maxIdx));
            window.StockInStore.activeRouteStepIndex = Math.max(0, Math.min(window.StockInStore.activeRouteStepIndex || 0, window.StockInStore.unlockedRouteStepIndex));
        }

        updateRouteIndicator(selectedMt);
        populateRouteLocationDropdowns(selectedMt, preserveSelection);
        renderGrnChevronPipeline(selectedMt);
    }
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();
}

window.onIntakeMovementTypeChanged = function(mtId) {
    window.StockInStore.movementTypes = getSyncedInboundMovementTypes();
    const mt = window.StockInStore.movementTypes.find(m => m.id === mtId);
    if (mt) {
        const whInput = document.getElementById('intakeWarehouseSelect');
        if (whInput && mt.warehouse_id) {
            whInput.value = mt.warehouse_id;
        }
        resetRouteStepProgress(mt);
        updateRouteIndicator(mt);
        populateRouteLocationDropdowns(mt, false);
        renderGrnChevronPipeline(mt);
        renderIntakeTable();
    }
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();
    updateLiveMockup();
    scheduleDraftSave();
};

function updateRouteIndicator(movementType) {
    const badge = document.getElementById('routePresetBadge');
    if (badge && movementType) {
        const stepCount = (movementType.steps || []).length;
        badge.textContent = movementType.route_preset || `${stepCount}-Step Route`;
    }
}

function getGrnStepShortLabel(step, idx, totalSteps) {
    const rawName = (step?.name || '').trim();
    if (rawName) return rawName;
    const txt = `${step?.to_area || ''}`.toLowerCase();
    if (/qa|qc|quality|inspect/.test(txt)) return 'Quality Check';
    if (idx === 0) return 'Receiving';
    if (idx === totalSteps - 1) return 'Storage';
    return `Step ${idx + 1}`;
}

window.selectGrnRouteStep = function(stepIdx) {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    if (steps.length === 0) return;

    const targetIdx = Math.max(0, Math.min(Number(stepIdx) || 0, steps.length - 1));
    const unlockedIdx = Math.max(0, Math.min(Number(window.StockInStore.unlockedRouteStepIndex || 0), steps.length - 1));

    // Strict forward lock: prevent skipping past the current active unlocked step
    if (targetIdx > unlockedIdx) {
        showGrnToast(`Step ${targetIdx + 1} is locked. Please complete Step ${unlockedIdx + 1} first.`, 'warning');
        return;
    }

    window.StockInStore.activeRouteStepIndex = targetIdx;

    const step = steps[targetIdx];
    if (step) {
        const srcSelect = document.getElementById('intakeSourceLocationSelect');
        const destSelect = document.getElementById('intakeDestinationLocationSelect');
        if (srcSelect && step.from_area) ensureGrnSelectOption(srcSelect, step.from_area);
        if (destSelect && step.to_area) ensureGrnSelectOption(destSelect, step.to_area);
    }

    renderGrnChevronPipeline(mt);
    renderIntakeTable();
    updateLiveMockup();
    scheduleDraftSave();
};

// Dynamic Inbound Chevron Pipeline synced with sequential step gating
window.renderGrnChevronPipeline = function(movementType) {
    const pipeline = document.getElementById('grnLifecycleProgressBar');
    if (!pipeline) return;

    const mt = movementType || getActiveInboundMovementType();
    const rawSteps = getActiveRouteSteps(mt);

    const unlockedIdx = Math.max(0, Math.min(Number(window.StockInStore.unlockedRouteStepIndex || 0), rawSteps.length - 1));
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), unlockedIdx));
    window.StockInStore.unlockedRouteStepIndex = unlockedIdx;
    window.StockInStore.activeRouteStepIndex = activeIdx;

    const isPosted = !!window.StockInStore.isCurrentGrnPosted;
    const destLocRaw = (document.getElementById('intakeDestinationLocationSelect')?.value || '').trim();
    const completedSet = new Set(window.StockInStore.completedRouteSteps || []);

    const stepsHtml = rawSteps.map((step, idx) => {
        const stageNum = idx + 1;
        const shortLabel = getGrnStepShortLabel(step, idx, rawSteps.length);
        const stepTitle = shortLabel;
        const stepDesc = (idx === activeIdx && destLocRaw) ? destLocRaw : (step.to_area || step.from_area || 'Stage Target');
        const isCompleted = isPosted || idx < unlockedIdx || completedSet.has(idx);
        const isInProgress = !isPosted && idx === unlockedIdx;
        const isLocked = !isPosted && idx > unlockedIdx;
        const isViewingHistory = isCompleted && idx === activeIdx;
        const isQcStep = isStepQaInspection(step, mt);

        let stepClass = '';
        if (isCompleted) {
            stepClass = isViewingHistory ? 'is-done is-viewing-history' : 'is-done';
        } else if (isInProgress) {
            stepClass = isQcStep ? 'is-active stage-approval' : 'is-active stage-draft';
        } else {
            stepClass = 'is-locked stage-pending';
        }

        const iconClass = isCompleted
            ? 'ph-check'
            : (isLocked ? 'ph-lock-simple' : (isQcStep ? 'ph-seal-check' : (idx === 0 ? 'ph-truck' : (idx === rawSteps.length - 1 ? 'ph-warehouse' : 'ph-arrows-left-right'))));
        const descIcon = isQcStep
            ? 'ph-clipboard-text'
            : (idx === 0 ? 'ph-warehouse' : (idx === rawSteps.length - 1 ? 'ph-map-pin' : 'ph-arrows-left-right'));

        const stateLabel = isCompleted ? 'Completed' : (isInProgress ? 'In Progress' : `Locked (complete Step ${unlockedIdx + 1} first)`);
        const fullTooltip = `Step ${stageNum}: ${stepTitle} — ${stepDesc} (${stateLabel})`;

        return `
            <div class="po-chevron-step rfq-chevron-step ${stepClass}" id="grnStepIndicatorStep${stageNum}" role="button" tabindex="${isLocked ? '-1' : '0'}"
                 aria-disabled="${isLocked ? 'true' : 'false'}"
                 data-step-title="${escapeHtml(`Step ${stageNum}: ${stepTitle}`)}"
                 data-step-desc="${escapeHtml(stepDesc)}"
                 data-step-state="${escapeHtml(stateLabel)}"
                 onclick="selectGrnRouteStep(${idx})"
                 onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectGrnRouteStep(${idx});}"
                 onmouseenter="showGrnChevronTooltip(this)"
                 onmouseleave="hideGrnChevronTooltip()"
                 onfocus="showGrnChevronTooltip(this)"
                 onblur="hideGrnChevronTooltip()"
                 title="${escapeHtml(fullTooltip)}">
                <div class="rfq-chevron-badge"><i class="ph ${iconClass}"></i><span class="rfq-chevron-num">${stageNum}</span></div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">${escapeHtml(stepTitle)}</span>
                    <span class="rfq-chevron-desc"><i class="ph ${descIcon}" aria-hidden="true"></i> <span>${escapeHtml(stepDesc)}</span></span>
                </div>
            </div>
        `;
    }).join('');

    const statusStageNum = rawSteps.length + 1;
    const statusTitleText = isPosted ? 'Posted' : 'Status';
    const statusDescText = isPosted ? (window.StockInStore.lastPostedGrnNumber || 'Committed') : 'Ready to Commit';
    const statusStateText = isPosted ? 'GRN Posted & Stock Updated' : 'Pending Final Step';
    pipeline.innerHTML = `
        ${stepsHtml}
        <div class="po-chevron-step rfq-chevron-step stage-status ${isPosted ? 'is-done' : 'is-locked'}" id="grnStepIndicatorStatus"
             aria-disabled="${isPosted ? 'false' : 'true'}"
             data-step-title="${escapeHtml(statusTitleText)}"
             data-step-desc="${escapeHtml(statusDescText)}"
             data-step-state="${escapeHtml(statusStateText)}"
             onmouseenter="showGrnChevronTooltip(this)"
             onmouseleave="hideGrnChevronTooltip()"
             title="${escapeHtml(`${statusTitleText} — ${statusDescText}`)}">
            <div class="rfq-chevron-badge"><i class="ph ${isPosted ? 'ph-check' : 'ph-check-circle'}"></i><span class="rfq-chevron-num">${statusStageNum}</span></div>
            <div class="rfq-chevron-info">
                <span class="rfq-chevron-title" id="grnStepStatusTitle">${escapeHtml(statusTitleText)}</span>
                <span class="rfq-chevron-desc"><i class="ph ph-flag-checkered" aria-hidden="true"></i> <span id="grnStepStatusDesc">${escapeHtml(statusDescText)}</span></span>
            </div>
        </div>
    `;

    const activeStep = rawSteps[activeIdx] || {};
    const activeShortLabel = getGrnStepShortLabel(activeStep, activeIdx, rawSteps.length);
    const isQcActive = isStepQaInspection(activeStep, mt);
    const stageBadge = document.getElementById('grnRouteStageBadge');
    if (stageBadge) {
        const roTag = (!isPosted && activeIdx < unlockedIdx) ? ' (Read-Only)' : '';
        stageBadge.textContent = `Step ${activeIdx + 1}/${rawSteps.length}: ${activeShortLabel}${roTag}`;
        stageBadge.className = isPosted ? 'grn-label-badge grn-divider-pill is-success' : (isQcActive ? 'grn-label-badge grn-divider-pill is-accent' : 'grn-label-badge grn-divider-pill is-success');
    }

    updateFulfillmentProgress();
};

window.showGrnChevronTooltip = function(el) {
    const tip = document.getElementById('grnChevronTooltip');
    if (!tip || !el) return;
    const title = el.getAttribute('data-step-title') || '';
    const desc = el.getAttribute('data-step-desc') || '';
    const state = el.getAttribute('data-step-state') || '';
    tip.innerHTML = `<div class="grn-chevron-tooltip-title">${escapeHtml(title)}</div>` +
        (desc ? `<div class="grn-chevron-tooltip-desc">${escapeHtml(desc)}</div>` : '') +
        (state ? `<div class="grn-chevron-tooltip-state">${escapeHtml(state)}</div>` : '');
    tip.hidden = false;
    if (typeof el.getBoundingClientRect === 'function') {
        const rect = el.getBoundingClientRect();
        const top = Math.max(8, rect.bottom + 6);
        const left = Math.max(12, Math.min(rect.left + (rect.width / 2), (window.innerWidth || 1280) - 160));
        tip.style.top = `${top}px`;
        tip.style.left = `${left}px`;
        tip.style.transform = 'translateX(-50%)';
    }
};

window.hideGrnChevronTooltip = function() {
    const tip = document.getElementById('grnChevronTooltip');
    if (tip) tip.hidden = true;
};

window.populateRouteLocationDropdowns = function(movementType, preserveLocations = false) {
    const srcSelect = document.getElementById('intakeSourceLocationSelect');
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    if (!srcSelect || !destSelect) return;

    const mt = movementType || getActiveInboundMovementType();
    const whId = mt?.warehouse_id || document.getElementById('intakeWarehouseSelect')?.value || 'WH-MAIN';
    const whAreas = window.StockInStore.storageAreas.filter(a => a.warehouse_id === whId);
    const steps = getActiveRouteSteps(mt);

    const prevSrc = srcSelect.value;
    const prevDest = destSelect.value;

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

    const activeStepIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), Math.max(0, steps.length - 1)));
    const activeStep = steps[activeStepIdx] || {};

    const defaultSrc = activeStep.from_area || sourceOrigins[0];
    const defaultDest = activeStep.to_area || destTargets[0] || 'Stock Zone A (Dry Goods)';
    if (defaultSrc && !sourceOrigins.includes(defaultSrc)) sourceOrigins.push(defaultSrc);
    if (defaultDest && !destTargets.includes(defaultDest)) destTargets.unshift(defaultDest);

    const chosenSrc = (preserveLocations && prevSrc && sourceOrigins.includes(prevSrc)) ? prevSrc : defaultSrc;
    const chosenDest = (preserveLocations && prevDest && destTargets.includes(prevDest)) ? prevDest : defaultDest;

    srcSelect.innerHTML = sourceOrigins.map(src =>
        `<option value="${escapeHtml(src)}" ${src === chosenSrc ? 'selected' : ''}>${escapeHtml(src)}</option>`
    ).join('');
    srcSelect.disabled = isViewingCompletedStepReadOnly() || sourceOrigins.length <= 1;

    destSelect.innerHTML = destTargets.map(dest =>
        `<option value="${escapeHtml(dest)}" ${dest === chosenDest ? 'selected' : ''}>${escapeHtml(dest)}</option>`
    ).join('');
    destSelect.disabled = isViewingCompletedStepReadOnly();

    syncCombinedRouteDropdown();
};

window.syncCombinedRouteDropdown = function() {
    const combinedSel = document.getElementById('intakeCombinedRouteSelect');
    if (!combinedSel) return;

    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const total = steps.length;
    const unlockedIdx = Math.max(0, Math.min(Number(window.StockInStore.unlockedRouteStepIndex || 0), Math.max(0, total - 1)));
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), unlockedIdx));
    const isPosted = !!window.StockInStore.isCurrentGrnPosted;
    const isReadOnly = window.isIntakeSheetLocked();

    const srcVal = (document.getElementById('intakeSourceLocationSelect')?.value || '').trim();
    const destVal = (document.getElementById('intakeDestinationLocationSelect')?.value || '').trim();
    const activeStep = steps[activeIdx] || {};
    const defaultSrc = (activeStep.from_area || 'Supplier / Vendor Origin (Boundary)').trim();
    const defaultDest = (activeStep.to_area || 'Stock Zone A (Dry Goods)').trim();
    const hasCustomOverride = (srcVal && srcVal !== defaultSrc) || (destVal && destVal !== defaultDest);

    let optsHtml = steps.map((s, idx) => {
        const fromTxt = (s.from_area || 'Source').trim();
        const toTxt = (s.to_area || 'Destination').trim();
        const isLockedOpt = !isPosted && idx > unlockedIdx;
        const selected = !hasCustomOverride && idx === activeIdx;
        return `<option value="step:${idx}" ${selected ? 'selected' : ''} ${isLockedOpt ? 'disabled' : ''}>${escapeHtml(fromTxt)} &gt; ${escapeHtml(toTxt)}</option>`;
    }).join('');

    if (hasCustomOverride) {
        optsHtml += `<option value="custom" selected>${escapeHtml(srcVal || defaultSrc)} &gt; ${escapeHtml(destVal || defaultDest)}</option>`;
    }

    combinedSel.innerHTML = optsHtml;
    combinedSel.value = hasCustomOverride ? 'custom' : `step:${activeIdx}`;
    combinedSel.disabled = isReadOnly;
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();
};

window.onIntakeCombinedRouteChanged = function(val) {
    if (!val || val === 'custom') return;
    const match = String(val).match(/^step:(\d+)$/);
    if (!match) return;
    selectGrnRouteStep(Number(match[1]));
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
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        closeLotExpiryModal();
        return;
    }

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
    updateFulfillmentProgress();
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
    if (typeof window.syncGrnHeaderPickerLabels === 'function') window.syncGrnHeaderPickerLabels();
    syncGrnStepActionButtons();
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
    const isReadOnly = window.isIntakeSheetLocked();
    const disAttr = isReadOnly ? 'disabled' : '';
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
                const lotAllowed = window.isLotTrackingEnabledForItem(it);
                if (it.is_custom_unlisted) {
                    return `
                        <td class="td-item-cell">
                            <div class="grn-item-cell">
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <input type="text" class="grn-adhoc-input is-title" style="flex: 1;" value="${escapeHtml(it.name || '')}" onchange="onAdhocFieldChange(${idx}, 'name', this.value)" placeholder="Custom Item Description" ${disAttr}>
                                    <button type="button" class="grn-btn-icon" style="width: 22px; height: 22px; flex-shrink: 0;" onclick="onIntakeItemSelected(${idx}, '__CATALOG__')" title="Switch back to Item Master dropdown" aria-label="Switch back to Item Master dropdown" ${disAttr}>
                                        <i class="ph ph-list-magnifying-glass" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <input type="text" class="grn-adhoc-input is-sku" value="${escapeHtml(it.sku || '')}" onchange="onAdhocFieldChange(${idx}, 'sku', this.value)" placeholder="SKU" ${disAttr}>
                                    <input type="text" class="grn-adhoc-input" style="flex: 1;" value="${escapeHtml(it.specs || '')}" onchange="onAdhocFieldChange(${idx}, 'specs', this.value)" placeholder="Specs / Brand" ${disAttr}>
                                </div>
                                ${lotAllowed && (it.lot_number || it.expiry_date) ? `
                                    <div class="grn-trace-line">
                                        <span class="grn-mono"><i class="ph ph-barcode" style="color: var(--grn-primary); margin-right: 2px;"></i>${escapeHtml(it.lot_number || 'No Lot')}</span>
                                        ${renderExpiryChip(it.expiry_date)}
                                    </div>
                                ` : ''}
                            </div>
                        </td>
                    `;
                }

                const curSku = String(it.sku || '').trim();
                const curName = String(it.name || '').trim();
                const displayLabel = curName || curSku || 'Select Item...';

                return `
                    <td class="td-item-cell">
                        <div class="grn-item-cell">
                            <button type="button" class="grn-item-picker-trigger" data-row-item-select="${idx}" onclick="toggleRowItemPicker(${idx}, this, event)" title="${escapeHtml(curSku && curName ? `${curSku} — ${curName}` : displayLabel)}" aria-haspopup="listbox" aria-expanded="false" ${disAttr}>
                                <span class="grn-item-picker-text">${escapeHtml(displayLabel)}</span>
                                <i class="ph ph-caret-down grn-picker-caret" aria-hidden="true"></i>
                            </button>
                            <div class="grn-item-meta">
                                ${curSku ? `<span class="grn-sku-badge" title="${escapeHtml(curSku)}">${escapeHtml(curSku)}</span>` : ''}
                                ${it.specs ? `<span class="grn-specs" title="${escapeHtml(it.specs)}">${escapeHtml(it.specs)}</span>` : ''}
                            </div>
                            ${lotAllowed && (it.lot_number || it.expiry_date) ? `
                                <div class="grn-trace-line">
                                    <span class="grn-mono" title="${escapeHtml(it.lot_number || 'No Lot')}"><i class="ph ph-barcode" style="color: var(--grn-primary); margin-right: 2px;"></i>${escapeHtml(it.lot_number || 'No Lot')}</span>
                                    ${renderExpiryChip(it.expiry_date)}
                                </div>
                            ` : ''}
                        </div>
                    </td>
                `;
            }
            if (col.id === 'uom') {
                return `
                    <td class="td-center td-uom-cell" style="min-width: 96px;">
                        <select data-uom-select data-uom-placeholder="Select UOM" data-uom-current="${escapeHtml(it.uom || 'PCS')}" onchange="onAdhocFieldChange(${idx}, 'uom', this.value)" ${disAttr}>
                            ${(() => {
                                if (window.UomRegistry && window.UomRegistry.isLoaded) {
                                    const units = window.UomRegistry.getUnits();
                                    let opts = units.map(u => `<option value="${escapeHtml(u.uom_code)}" ${String(it.uom || 'PCS').toUpperCase() === String(u.uom_code).toUpperCase() ? 'selected' : ''}>${escapeHtml(u.uom_code)}</option>`).join('');
                                    if (it.uom && !units.some(u => String(u.uom_code).toUpperCase() === String(it.uom).toUpperCase())) {
                                        opts = `<option value="${escapeHtml(it.uom)}" selected>${escapeHtml(it.uom)}</option>` + opts;
                                    }
                                    return opts;
                                }
                                const fallbackUnits = ['KG', 'L', 'PCS', 'BOX', 'PACK', 'TRAY', 'SACK'];
                                const cur = String(it.uom || 'PCS').toUpperCase();
                                let opts = fallbackUnits.map(u => `<option value="${u}" ${cur === u ? 'selected' : ''}>${u}</option>`).join('');
                                if (it.uom && !fallbackUnits.includes(cur)) {
                                    opts = `<option value="${escapeHtml(it.uom)}" selected>${escapeHtml(it.uom)}</option>` + opts;
                                }
                                return opts;
                            })()}
                        </select>
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
                const qtyTitle = isOver
                    ? `Exceeds remaining PO balance (${remaining.toFixed(2)})`
                    : (ordered > 0 ? `Expected remaining: ${remaining.toFixed(2)}` : 'Enter received quantity');
                return `
                    <td class="td-center">
                        <input type="number"
                               class="grn-qty-input ${isOver ? 'is-over' : ''}"
                               value="${recvNow}"
                               min="0"
                               step="any"
                               placeholder="0"
                               data-remaining="${remaining}"
                               aria-invalid="${isOver ? 'true' : 'false'}"
                               title="${escapeHtml(qtyTitle)}"
                               oninput="onReceiveQtyInput(${idx}, this.value)"
                               aria-label="Receive quantity" ${disAttr}>
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
                        <input type="number" class="grn-table-line-input" step="0.01" min="0" value="${unitCost.toFixed(2)}" onchange="onAdhocFieldChange(${idx}, 'unit_cost', this.value)" style="text-align: right; font-weight: 700; width: 100%; height: 30px; font-size: 12px;" ${disAttr}>
                    </td>
                `;
            }
            if (col.id === 'line_total') {
                return `<td class="td-num" data-cell-role="line-total" style="font-weight: 800; color: var(--grn-on-surface);">₱${lineAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            return `<td>—</td>`;
        }).join('');

        const trackMode = window.getItemMasterTrackingMode(it);
        const lotEnabledForRow = trackMode === 'lots';

        return `
            <tr data-row-idx="${idx}" class="${isOver ? 'is-over-receipt' : ''}">
                ${cells}
                <td class="td-center td-actions-sticky" style="white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                        ${lotEnabledForRow ? `
                            <button type="button" class="grn-lot-btn ${it.lot_number ? 'has-lot' : ''}" onclick="openLotExpiryModal(${idx})" title="Configure Batch / Lot # & Expiry" ${disAttr}>
                                <span class="grn-state-dot" aria-hidden="true"></span>
                                <i class="ph ${it.lot_number ? 'ph-barcode' : 'ph-calendar-plus'}" aria-hidden="true"></i>
                                <span>${it.lot_number ? 'Lot Set' : 'Add Lot'}</span>
                            </button>
                        ` : `
                            <span style="font-size: 11px; font-weight: 600; color: #64748b; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px;" title="Lot tracking is disabled for this item in Item Master (${trackMode === 'serial' ? 'Tracked by Serial #' : 'Tracked by Quantity'})">
                                <i class="ph ${trackMode === 'serial' ? 'ph-qr-code' : 'ph-hash'}"></i>
                                ${trackMode === 'serial' ? 'Serial' : 'Qty Only'}
                            </span>
                        `}
                        ${it.is_adhoc ? `
                            <button type="button" class="grn-row-remove" onclick="removeAdHocRow(${idx})" title="Remove item" aria-label="Remove item" ${disAttr}>
                                <i class="ph ph-trash" aria-hidden="true"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    window.UomRegistry?.autoHydrate(tbody);
}

let liveMockupDebounce = null;
function scheduleLiveMockupUpdate() {
    clearTimeout(liveMockupDebounce);
    liveMockupDebounce = setTimeout(() => {
        window.requestAnimationFrame(() => updateLiveMockup());
    }, 120);
}

window.adjustRowQty = function(idx, delta) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;
    const cur = Number(item.receive_now || 0);
    const next = Math.max(0, cur + delta);
    setRowReceiveQty(idx, next);
};

window.onAdhocFieldChange = function(idx, field, val) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;
    if (field === 'unit_cost') {
        item.unit_cost = Math.max(0, parseFloat(val) || 0);
    } else {
        item[field] = val;
    }
    renderIntakeTable();
    updateFulfillmentProgress();
    scheduleLiveMockupUpdate();
    scheduleDraftSave();
};

window.onReceiveQtyInput = function(idx, val) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
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

        const qtyInput = row.querySelector('.grn-qty-input');
        if (qtyInput) {
            if (isOver) {
                qtyInput.classList.add('is-over');
                qtyInput.setAttribute('aria-invalid', 'true');
                qtyInput.title = `Exceeds remaining PO balance (${remaining.toFixed(2)})`;
            } else {
                qtyInput.classList.remove('is-over');
                qtyInput.setAttribute('aria-invalid', 'false');
                qtyInput.title = ordered > 0 ? `Expected remaining: ${remaining.toFixed(2)}` : 'Enter received quantity';
            }
        }

        const totTd = row.querySelector('[data-cell-role="line-total"]');
        if (totTd) {
            const amt = num * Number(item.unit_cost || 0);
            totTd.textContent = `₱${amt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    }

    updateFulfillmentProgress();
    scheduleLiveMockupUpdate();
    scheduleDraftSave();
};

window.setRowReceiveQty = function(idx, qty) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;

    const row = document.querySelector(`tr[data-row-idx="${idx}"]`);
    const input = row ? row.querySelector('.grn-qty-input') : null;
    if (input) {
        input.value = qty;
        onReceiveQtyInput(idx, qty);
    } else {
        item.receive_now = qty;
        renderIntakeTable();
        updateFulfillmentProgress();
        scheduleLiveMockupUpdate();
        scheduleDraftSave();
    }
};

window.onRowLotChange = function(idx, lot) {
    if (window.StockInStore.intakeItems[idx]) {
        window.StockInStore.intakeItems[idx].lot_number = lot;
        syncGrnStepActionButtons();
        scheduleLiveMockupUpdate();
        scheduleDraftSave();
    }
};

window.onRowExpiryChange = function(idx, date) {
    if (window.StockInStore.intakeItems[idx]) {
        window.StockInStore.intakeItems[idx].expiry_date = date;
        syncGrnStepActionButtons();
        scheduleLiveMockupUpdate();
        scheduleDraftSave();
    }
};

window.onIntakeItemSelected = function(idx, selectedVal) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        renderIntakeTable();
        return;
    }
    const item = window.StockInStore.intakeItems[idx];
    if (!item) return;

    if (selectedVal === '__CUSTOM__') {
        item.is_custom_unlisted = true;
        renderIntakeTable();
        scheduleDraftSave();
        return;
    }
    if (selectedVal === '__CATALOG__') {
        item.is_custom_unlisted = false;
        renderIntakeTable();
        scheduleDraftSave();
        return;
    }

    const products = Array.isArray(window.StockInStore.products) ? window.StockInStore.products : [];
    const targetUpper = String(selectedVal || '').trim().toUpperCase();
    const matched = products.find(p => String(p.sku || p.item_code || '').trim().toUpperCase() === targetUpper);
    if (!matched) return;

    const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
    const trackingMode = window.getItemMasterTrackingMode(matched);
    const lotEnabled = trackingMode === 'lots';

    item.sku = matched.sku || matched.item_code || item.sku;
    item.name = matched.name || matched.item_name || item.name;
    item.specs = matched.description || matched.specs || '';
    item.uom = matched.uom || matched.base_uom || 'PCS';
    item.unit_cost = Number(matched.cost_price ?? matched.unit_cost ?? item.unit_cost ?? 0);
    item.trackingBy = trackingMode;
    item.is_custom_unlisted = false;
    if (lotEnabled && !item.lot_number) {
        item.lot_number = `LOT-${dateStr}-${(idx + 1).toString().padStart(2, '0')}`;
    } else if (!lotEnabled) {
        item.lot_number = '';
    }

    renderIntakeTable();
    updateFulfillmentProgress();
    syncGrnStepActionButtons();
    scheduleLiveMockupUpdate();
    scheduleDraftSave();
};

window.closeUnplannedItemPicker = function() {
    const dd = document.getElementById('grnUnplannedItemDropdown');
    const btn = document.getElementById('btnAddUnplannedItem');
    if (dd) dd.hidden = true;
    if (btn) btn.setAttribute('aria-expanded', 'false');
};

window.renderUnplannedItemPickerList = function(query = '') {
    const listEl = document.getElementById('grnUnplannedItemList');
    if (!listEl) return;

    const products = Array.isArray(window.StockInStore.products) ? window.StockInStore.products : [];
    const q = String(query || '').trim().toLowerCase();
    const filtered = q
        ? products.filter(p => {
            const sku = String(p.sku || p.item_code || '').toLowerCase();
            const name = String(p.name || p.item_name || '').toLowerCase();
            const specs = String(p.description || p.specs || '').toLowerCase();
            return sku.includes(q) || name.includes(q) || specs.includes(q);
        })
        : products;

    if (filtered.length === 0) {
        listEl.innerHTML = `<div style="padding: 14px 10px; text-align: center; font-size: 12px; color: #64748b;">No matching items in catalog. Use "Add Blank / Custom Unlisted Line" below.</div>`;
        return;
    }

    listEl.innerHTML = filtered.slice(0, 80).map(p => {
        const sku = String(p.sku || p.item_code || '').trim();
        const name = String(p.name || p.item_name || sku || 'Unnamed Item').trim();
        const uom = String(p.uom || p.base_uom || 'PCS').trim();
        const safeSkuArg = escapeHtml(sku).replace(/&#39;/g, "\\'");
        return `
            <button type="button" class="grn-unplanned-opt" role="option" onclick="addAdHocIntakeRowBySku('${safeSkuArg}'); closeUnplannedItemPicker();">
                <span class="grn-unplanned-opt-main">
                    <span class="grn-unplanned-opt-name">${escapeHtml(name)}</span>
                    <span class="grn-unplanned-opt-meta">${escapeHtml(sku || 'NO-SKU')}</span>
                </span>
                <span class="grn-unplanned-opt-uom">${escapeHtml(uom)}</span>
            </button>
        `;
    }).join('');
};

let activeRowItemPickerIdx = null;
let activeRowItemPickerTrigger = null;
let activeHeaderPickerKind = null; // 'vendor' | 'movementType' | 'route'
let activeHeaderPickerTrigger = null;

function positionFloatingGrnPopover(triggerEl, popoverEl, preferredWidth = 360) {
    if (!triggerEl || !popoverEl || typeof triggerEl.getBoundingClientRect !== 'function') return;
    const rect = triggerEl.getBoundingClientRect();
    const vw = window.innerWidth || 1280;
    const vh = window.innerHeight || 800;
    const width = Math.min(Math.max(preferredWidth, rect.width), vw - 16);
    popoverEl.style.width = `${width}px`;
    const measuredHeight = popoverEl.offsetHeight || 320;
    const estHeight = Math.min(Math.max(measuredHeight, 120), 360);
    const spaceBelow = vh - rect.bottom;
    const top = (spaceBelow < estHeight + 12 && rect.top > estHeight + 12)
        ? Math.max(8, rect.top - estHeight - 6)
        : Math.max(8, Math.min(vh - estHeight - 8, rect.bottom + 6));
    const left = Math.max(8, Math.min(rect.left, vw - width - 8));
    popoverEl.style.top = `${top}px`;
    popoverEl.style.left = `${left}px`;
}

window.syncGrnHeaderPickerLabels = function() {
    if (typeof window.syncGrnPoPickerLabel === 'function') window.syncGrnPoPickerLabel();

    // 1. Vendor / Supplier
    const vendorSelect = document.getElementById('intakeVendorSelect');
    const vendorTrigger = document.getElementById('grnVendorPickerTrigger');
    const vendorLabel = document.getElementById('grnVendorPickerLabel');
    if (vendorSelect && vendorLabel) {
        if (vendorTrigger) vendorTrigger.disabled = !!vendorSelect.disabled;
        const vVal = String(vendorSelect.value || '').trim();
        if (!vVal) {
            vendorLabel.textContent = 'Choose Vendor Partner...';
            vendorLabel.classList?.add('is-placeholder');
            if (vendorTrigger) vendorTrigger.title = 'Choose Vendor Partner from Masterlist';
        } else {
            const matched = (window.StockInStore.vendors || []).find(v => (v.vendor_code || v.id) === vVal || v.id === vVal);
            const txt = matched
                ? (matched.legal_name || matched.name || vVal)
                : (vendorSelect.options?.[vendorSelect.selectedIndex]?.text || vVal);
            vendorLabel.textContent = txt;
            vendorLabel.classList?.remove('is-placeholder');
            if (vendorTrigger) vendorTrigger.title = matched ? `${matched.vendor_code || matched.id} — ${txt}` : txt;
        }
    }

    // 2. Movement Type
    const mtSelect = document.getElementById('intakeMovementTypeSelect');
    const mtTrigger = document.getElementById('grnMovementTypePickerTrigger');
    const mtLabel = document.getElementById('grnMovementTypePickerLabel');
    if (mtSelect && mtLabel) {
        if (mtTrigger) mtTrigger.disabled = !!mtSelect.disabled;
        const mtVal = String(mtSelect.value || '').trim();
        const mtObj = (window.StockInStore.movementTypes || []).find(m => m.id === mtVal);
        const txt = mtObj
            ? `${mtObj.code} — ${mtObj.name}`
            : (mtSelect.options?.[mtSelect.selectedIndex]?.text || 'Select Movement Type...');
        mtLabel.textContent = txt;
        if (mtVal) mtLabel.classList?.remove('is-placeholder');
        else mtLabel.classList?.add('is-placeholder');
        if (mtTrigger) mtTrigger.title = txt;
    }

    // 3. Route
    const routeSelect = document.getElementById('intakeCombinedRouteSelect');
    const routeTrigger = document.getElementById('grnRoutePickerTrigger');
    const routeLabel = document.getElementById('grnRoutePickerLabel');
    if (routeSelect && routeLabel) {
        if (routeTrigger) routeTrigger.disabled = !!routeSelect.disabled;
        const selOpt = routeSelect.options?.[routeSelect.selectedIndex];
        const txt = selOpt?.text || 'Source > Destination';
        routeLabel.textContent = txt;
        if (selOpt?.text) routeLabel.classList?.remove('is-placeholder');
        else routeLabel.classList?.add('is-placeholder');
        if (routeTrigger) routeTrigger.title = txt;
    }
};

window.closeGrnHeaderPicker = function() {
    const dd = document.getElementById('grnHeaderFieldPickerDropdown');
    if (dd) dd.hidden = true;
    if (activeHeaderPickerTrigger) {
        activeHeaderPickerTrigger.setAttribute('aria-expanded', 'false');
        activeHeaderPickerTrigger = null;
    }
    activeHeaderPickerKind = null;
};

window.renderGrnHeaderPickerList = function(query = '') {
    const listEl = document.getElementById('grnHeaderFieldPickerList');
    if (!listEl || !activeHeaderPickerKind) return;

    const q = String(query || '').trim().toLowerCase();

    if (activeHeaderPickerKind === 'vendor') {
        const vendorSelect = document.getElementById('intakeVendorSelect');
        const curVal = String(vendorSelect?.value || '').trim();
        const vendors = Array.isArray(window.StockInStore.vendors) ? window.StockInStore.vendors : [];
        const list = vendors.map(v => ({
            val: String(v.vendor_code || v.id || '').trim(),
            name: String(v.legal_name || v.name || v.vendor_code || v.id || 'Vendor').trim(),
            meta: String(v.vendor_code || v.id || '').trim(),
            badge: String(v.category || v.payment_terms || 'Vendor').trim(),
        }));
        if (curVal && !list.some(item => item.val === curVal)) {
            const fallbackTxt = vendorSelect?.options?.[vendorSelect.selectedIndex]?.text || curVal;
            list.unshift({ val: curVal, name: fallbackTxt, meta: curVal, badge: 'Selected' });
        }
        const filtered = q
            ? list.filter(v => v.name.toLowerCase().includes(q) || v.meta.toLowerCase().includes(q) || v.badge.toLowerCase().includes(q))
            : list;

        if (filtered.length === 0) {
            listEl.innerHTML = `<div style="padding: 14px 10px; text-align: center; font-size: 12px; color: #64748b;">No matching vendors found.</div>`;
            return;
        }
        listEl.innerHTML = filtered.slice(0, 80).map(v => {
            const isSel = curVal !== '' && v.val === curVal;
            const safeVal = escapeHtml(v.val).replace(/&#39;/g, "\\'");
            return `
                <button type="button" class="grn-unplanned-opt ${isSel ? 'is-selected' : ''}" role="option" aria-selected="${isSel ? 'true' : 'false'}" onclick="selectGrnHeaderPickerOption('${safeVal}')">
                    <span class="grn-unplanned-opt-main">
                        <span class="grn-unplanned-opt-name">${escapeHtml(v.name)}</span>
                        <span class="grn-unplanned-opt-meta">${escapeHtml(v.meta)}</span>
                    </span>
                    <span class="grn-unplanned-opt-uom">${escapeHtml(v.badge)}</span>
                </button>
            `;
        }).join('');
        return;
    }

    if (activeHeaderPickerKind === 'movementType') {
        const mtSelect = document.getElementById('intakeMovementTypeSelect');
        const curVal = String(mtSelect?.value || '').trim();
        const optionIds = Array.from(mtSelect?.options || []).map(o => o.value);
        const allMts = Array.isArray(window.StockInStore.movementTypes) ? window.StockInStore.movementTypes : [];
        const pool = optionIds.length > 0
            ? optionIds.map(id => allMts.find(m => m.id === id)).filter(Boolean)
            : allMts.filter(mt => mt.is_active !== false && (mt.operation_kind === 'Inbound Receipt' || mt.stock_effect === 'Receipt'));

        const filtered = q
            ? pool.filter(mt =>
                String(mt.code || '').toLowerCase().includes(q) ||
                String(mt.name || '').toLowerCase().includes(q) ||
                String(mt.route_preset || '').toLowerCase().includes(q)
            )
            : pool;

        if (filtered.length === 0) {
            listEl.innerHTML = `<div style="padding: 14px 10px; text-align: center; font-size: 12px; color: #64748b;">No matching movement types found.</div>`;
            return;
        }
        listEl.innerHTML = filtered.map(mt => {
            const isSel = curVal !== '' && mt.id === curVal;
            const stepCount = getActiveRouteSteps(mt).length;
            const badge = mt.route_preset || `${stepCount}-Step`;
            const safeVal = escapeHtml(mt.id).replace(/&#39;/g, "\\'");
            return `
                <button type="button" class="grn-unplanned-opt ${isSel ? 'is-selected' : ''}" role="option" aria-selected="${isSel ? 'true' : 'false'}" onclick="selectGrnHeaderPickerOption('${safeVal}')">
                    <span class="grn-unplanned-opt-main">
                        <span class="grn-unplanned-opt-name">${escapeHtml(mt.name || mt.code)}</span>
                        <span class="grn-unplanned-opt-meta">${escapeHtml(mt.code || mt.id)} · ${stepCount} step${stepCount === 1 ? '' : 's'}</span>
                    </span>
                    <span class="grn-unplanned-opt-uom is-status-ready">${escapeHtml(badge)}</span>
                </button>
            `;
        }).join('');
        return;
    }

    if (activeHeaderPickerKind === 'route') {
        const routeSelect = document.getElementById('intakeCombinedRouteSelect');
        const curVal = String(routeSelect?.value || '').trim();
        const mt = getActiveInboundMovementType();
        const steps = getActiveRouteSteps(mt);
        const total = steps.length;
        const unlockedIdx = Math.max(0, Math.min(Number(window.StockInStore.unlockedRouteStepIndex || 0), Math.max(0, total - 1)));
        const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), unlockedIdx));
        const completed = new Set(window.StockInStore.completedRouteSteps || []);
        const isPosted = !!window.StockInStore.isCurrentGrnPosted;

        const items = steps.map((s, idx) => {
            const fromTxt = (s.from_area || 'Source').trim();
            const toTxt = (s.to_area || 'Destination').trim();
            const isLocked = !isPosted && idx > unlockedIdx;
            const isDone = isPosted || completed.has(idx);
            const isCurrent = curVal === `step:${idx}` || (curVal === '' && idx === activeIdx);
            const badge = isDone ? 'Done' : (isLocked ? 'Locked' : (isCurrent ? 'Active' : 'Ready'));
            const badgeCls = isDone ? 'is-status-done' : (isLocked ? 'is-status-locked' : 'is-status-ready');
            return {
                val: `step:${idx}`,
                title: `${fromTxt} → ${toTxt}`,
                meta: `Step ${idx + 1}: ${s.name || 'Stage'}`,
                badge,
                badgeCls,
                isLocked,
                isSel: isCurrent,
            };
        });

        const filtered = q
            ? items.filter(it => it.title.toLowerCase().includes(q) || it.meta.toLowerCase().includes(q) || it.badge.toLowerCase().includes(q))
            : items;

        if (filtered.length === 0) {
            listEl.innerHTML = `<div style="padding: 14px 10px; text-align: center; font-size: 12px; color: #64748b;">No matching route steps found.</div>`;
            return;
        }
        listEl.innerHTML = filtered.map(it => {
            const safeVal = escapeHtml(it.val).replace(/&#39;/g, "\\'");
            return `
                <button type="button" class="grn-unplanned-opt ${it.isSel ? 'is-selected' : ''} ${it.isLocked ? 'is-disabled' : ''}" role="option" aria-selected="${it.isSel ? 'true' : 'false'}" ${it.isLocked ? 'disabled' : `onclick="selectGrnHeaderPickerOption('${safeVal}')"`}>
                    <span class="grn-unplanned-opt-main">
                        <span class="grn-unplanned-opt-name">${escapeHtml(it.title)}</span>
                        <span class="grn-unplanned-opt-meta">${escapeHtml(it.meta)}</span>
                    </span>
                    <span class="grn-unplanned-opt-uom ${it.badgeCls}">${escapeHtml(it.badge)}</span>
                </button>
            `;
        }).join('');
    }
};

window.toggleGrnHeaderPicker = function(kind, triggerEl, event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('grnHeaderFieldPickerDropdown');
    if (!dd || triggerEl?.disabled) return;

    const isSameOpen = !dd.hidden && activeHeaderPickerKind === kind;
    closeUnplannedItemPicker();
    closeGrnPoPicker();
    closeRowItemPicker();

    if (isSameOpen) {
        closeGrnHeaderPicker();
        return;
    }

    if (activeHeaderPickerTrigger && activeHeaderPickerTrigger !== triggerEl) {
        activeHeaderPickerTrigger.setAttribute('aria-expanded', 'false');
    }

    activeHeaderPickerKind = kind;
    activeHeaderPickerTrigger = triggerEl || null;
    if (triggerEl) triggerEl.setAttribute('aria-expanded', 'true');

    const searchInput = document.getElementById('grnHeaderFieldSearchInput');
    if (searchInput) {
        searchInput.value = '';
        searchInput.placeholder = kind === 'vendor'
            ? 'Search vendor name or code...'
            : (kind === 'movementType' ? 'Search movement code or preset...' : 'Search route step or location...');
    }

    dd.hidden = false;
    renderGrnHeaderPickerList('');
    positionFloatingGrnPopover(triggerEl, dd, kind === 'route' ? 360 : 340);
    setTimeout(() => searchInput?.focus(), 20);
};

window.selectGrnHeaderPickerOption = function(val) {
    const kind = activeHeaderPickerKind;
    closeGrnHeaderPicker();
    if (!kind) return;

    if (kind === 'vendor') {
        const sel = document.getElementById('intakeVendorSelect');
        if (sel) sel.value = val;
        syncGrnHeaderPickerLabels();
        onIntakeVendorChanged();
    } else if (kind === 'movementType') {
        const sel = document.getElementById('intakeMovementTypeSelect');
        if (sel) sel.value = val;
        syncGrnHeaderPickerLabels();
        onIntakeMovementTypeChanged(val);
    } else if (kind === 'route') {
        const sel = document.getElementById('intakeCombinedRouteSelect');
        if (sel) sel.value = val;
        syncGrnHeaderPickerLabels();
        onIntakeCombinedRouteChanged(val);
    }
};

window.syncGrnPoPickerLabel = function() {
    const poSelect = document.getElementById('intakePoSelect');
    const trigger = document.getElementById('grnPoPickerTrigger');
    const labelEl = document.getElementById('grnPoPickerLabel');
    if (!labelEl) return;

    const poVal = String(poSelect?.value || '').trim();
    if (trigger) trigger.disabled = !!poSelect?.disabled;

    if (!poVal) {
        labelEl.textContent = 'Direct Inbound (Bypass PO)';
        if (trigger) trigger.title = 'Direct Inbound (Bypass PO)';
        return;
    }

    const orders = Array.isArray(window.StockInStore.purchaseOrders) ? window.StockInStore.purchaseOrders : [];
    const matched = orders.find(o => o.po_number === poVal);
    const display = matched
        ? `${matched.po_number}${matched.vendor_name ? ' — ' + matched.vendor_name : ''}`
        : (poSelect?.options?.[poSelect.selectedIndex]?.text || poVal);
    labelEl.textContent = display;
    if (trigger) trigger.title = display;
};

window.closeGrnPoPicker = function() {
    const dd = document.getElementById('grnPoPickerDropdown');
    const trigger = document.getElementById('grnPoPickerTrigger');
    if (dd) dd.hidden = true;
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
};

window.renderGrnPoPickerList = function(query = '') {
    const listEl = document.getElementById('grnPoPickerList');
    if (!listEl) return;

    const orders = (Array.isArray(window.StockInStore.purchaseOrders) ? window.StockInStore.purchaseOrders : [])
        .filter(po => {
            const st = po.status || 'Issued';
            return st !== 'Cancelled' && st !== 'Archived';
        });
    const activePo = String(document.getElementById('intakePoSelect')?.value || '').trim();
    const q = String(query || '').trim().toLowerCase();
    const filtered = q
        ? orders.filter(po => {
            const num = String(po.po_number || '').toLowerCase();
            const vendor = String(po.vendor_name || '').toLowerCase();
            const status = String(po.status || '').toLowerCase();
            return num.includes(q) || vendor.includes(q) || status.includes(q);
        })
        : orders;

    if (filtered.length === 0) {
        listEl.innerHTML = `<div style="padding: 14px 10px; text-align: center; font-size: 12px; color: #64748b;">No matching purchase orders found.</div>`;
        return;
    }

    listEl.innerHTML = filtered.slice(0, 80).map(po => {
        const poNum = String(po.po_number || '').trim();
        const vendor = String(po.vendor_name || 'Vendor').trim();
        const status = String(po.status || 'Issued').trim();
        const itemCount = Array.isArray(po.items) ? po.items.length : 0;
        const isSelected = activePo !== '' && activePo === poNum;
        const stLower = status.toLowerCase();
        const statusCls = stLower.includes('fully') || stLower.includes('received') && !stLower.includes('partial')
            ? 'is-status-done'
            : (stLower.includes('partial') ? 'is-status-partial' : 'is-status-ready');
        const safePoArg = escapeHtml(poNum).replace(/&#39;/g, "\\'");
        return `
            <button type="button" class="grn-unplanned-opt ${isSelected ? 'is-selected' : ''}" role="option" aria-selected="${isSelected ? 'true' : 'false'}" onclick="selectGrnPoFromPicker('${safePoArg}')">
                <span class="grn-unplanned-opt-main">
                    <span class="grn-unplanned-opt-name">${escapeHtml(poNum)}</span>
                    <span class="grn-unplanned-opt-meta">${escapeHtml(vendor)}${itemCount > 0 ? ` · ${itemCount} item${itemCount === 1 ? '' : 's'}` : ''}</span>
                </span>
                <span class="grn-unplanned-opt-uom ${statusCls}">${escapeHtml(status)}</span>
            </button>
        `;
    }).join('');
};

window.toggleGrnPoPicker = function(event) {
    if (event) event.stopPropagation();
    const trigger = document.getElementById('grnPoPickerTrigger');
    const dd = document.getElementById('grnPoPickerDropdown');
    if (!dd || trigger?.disabled) return;

    const willOpen = dd.hidden;
    closeUnplannedItemPicker();
    closeGrnHeaderPicker();
    closeRowItemPicker();

    dd.hidden = !willOpen;
    if (trigger) trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if (willOpen) {
        const searchInput = document.getElementById('grnPoSearchInput');
        if (searchInput) searchInput.value = '';
        renderGrnPoPickerList('');
        positionFloatingGrnPopover(trigger, dd, 380);
        setTimeout(() => searchInput?.focus(), 20);
    }
};

window.selectGrnPoFromPicker = function(poNumber) {
    closeGrnPoPicker();
    const poSelect = document.getElementById('intakePoSelect');
    if (poSelect) {
        poSelect.value = poNumber || '';
    }
    syncGrnPoPickerLabel();
    onIntakePoSelected(poNumber || '');
};

window.closeRowItemPicker = function() {
    const dd = document.getElementById('grnRowItemPickerDropdown');
    if (dd) dd.hidden = true;
    if (activeRowItemPickerTrigger) {
        activeRowItemPickerTrigger.setAttribute('aria-expanded', 'false');
        activeRowItemPickerTrigger = null;
    }
    activeRowItemPickerIdx = null;
};

window.renderRowItemPickerList = function(query = '') {
    const listEl = document.getElementById('grnRowItemPickerList');
    if (!listEl) return;

    const rowItem = activeRowItemPickerIdx !== null ? window.StockInStore.intakeItems[activeRowItemPickerIdx] : null;
    const curSkuUpper = String(rowItem?.sku || '').trim().toUpperCase();
    const products = Array.isArray(window.StockInStore.products) ? window.StockInStore.products : [];
    const q = String(query || '').trim().toLowerCase();
    const filtered = q
        ? products.filter(p => {
            const sku = String(p.sku || p.item_code || '').toLowerCase();
            const name = String(p.name || p.item_name || '').toLowerCase();
            const specs = String(p.description || p.specs || '').toLowerCase();
            return sku.includes(q) || name.includes(q) || specs.includes(q);
        })
        : products;

    if (filtered.length === 0) {
        listEl.innerHTML = `<div style="padding: 14px 10px; text-align: center; font-size: 12px; color: #64748b;">No matching items in catalog. Use "Custom / Unlisted Item..." below.</div>`;
        return;
    }

    listEl.innerHTML = filtered.slice(0, 80).map(p => {
        const sku = String(p.sku || p.item_code || '').trim();
        const name = String(p.name || p.item_name || sku || 'Unnamed Item').trim();
        const uom = String(p.uom || p.base_uom || 'PCS').trim();
        const isSelected = curSkuUpper !== '' && sku.toUpperCase() === curSkuUpper;
        const safeSkuArg = escapeHtml(sku).replace(/&#39;/g, "\\'");
        return `
            <button type="button" class="grn-unplanned-opt ${isSelected ? 'is-selected' : ''}" role="option" aria-selected="${isSelected ? 'true' : 'false'}" onclick="selectCatalogItemForActiveRow('${safeSkuArg}')">
                <span class="grn-unplanned-opt-main">
                    <span class="grn-unplanned-opt-name">${escapeHtml(name)}</span>
                    <span class="grn-unplanned-opt-meta">${escapeHtml(sku || 'NO-SKU')}</span>
                </span>
                <span class="grn-unplanned-opt-uom">${escapeHtml(uom)}</span>
            </button>
        `;
    }).join('');
};

window.toggleRowItemPicker = function(idx, triggerEl, event) {
    if (event) event.stopPropagation();
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
    const dd = document.getElementById('grnRowItemPickerDropdown');
    if (!dd) return;

    const isSameRowOpen = !dd.hidden && activeRowItemPickerIdx === idx;
    closeUnplannedItemPicker();
    closeGrnPoPicker();
    closeGrnHeaderPicker();

    if (isSameRowOpen) {
        closeRowItemPicker();
        return;
    }

    if (activeRowItemPickerTrigger && activeRowItemPickerTrigger !== triggerEl) {
        activeRowItemPickerTrigger.setAttribute('aria-expanded', 'false');
    }

    activeRowItemPickerIdx = idx;
    activeRowItemPickerTrigger = triggerEl || null;
    dd.hidden = false;
    if (triggerEl) triggerEl.setAttribute('aria-expanded', 'true');

    const searchInput = document.getElementById('grnRowItemSearchInput');
    if (searchInput) searchInput.value = '';
    renderRowItemPickerList('');
    positionFloatingGrnPopover(triggerEl, dd, 350);
    setTimeout(() => searchInput?.focus(), 20);
};

window.selectCatalogItemForActiveRow = function(sku) {
    const idx = activeRowItemPickerIdx;
    closeRowItemPicker();
    if (idx !== null && idx !== undefined) {
        onIntakeItemSelected(idx, sku);
    }
};

window.selectCustomUnlistedForActiveRow = function() {
    const idx = activeRowItemPickerIdx;
    closeRowItemPicker();
    if (idx !== null && idx !== undefined) {
        onIntakeItemSelected(idx, '__CUSTOM__');
    }
};

window.toggleUnplannedItemPicker = function(event) {
    if (event) event.stopPropagation();
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
    const dd = document.getElementById('grnUnplannedItemDropdown');
    const btn = document.getElementById('btnAddUnplannedItem');
    if (!dd) {
        addAdHocIntakeRow();
        return;
    }
    const willOpen = dd.hidden;
    closeGrnPoPicker();
    closeGrnHeaderPicker();
    closeRowItemPicker();
    dd.hidden = !willOpen;
    if (btn) btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if (willOpen) {
        const searchInput = document.getElementById('grnUnplannedSearchInput');
        if (searchInput) searchInput.value = '';
        renderUnplannedItemPickerList('');
        setTimeout(() => searchInput?.focus(), 20);
    }
};

window.addAdHocIntakeRowBySku = function(sku) {
    const products = Array.isArray(window.StockInStore.products) ? window.StockInStore.products : [];
    const target = String(sku || '').trim().toUpperCase();
    const matched = products.find(p => String(p.sku || p.item_code || '').trim().toUpperCase() === target);
    window.addAdHocIntakeRow(matched || null, !matched);
};

window.addAdHocIntakeRow = function(product = null, isBlankCustom = false) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
    const products = Array.isArray(window.StockInStore.products) ? window.StockInStore.products : [];
    const p = isBlankCustom
        ? { sku: '', name: '', specs: '', uom: 'PCS', cost_price: 0 }
        : (product || products[0] || { sku: 'ITEM-DIRECT', name: 'Direct Spot Item', uom: 'KG', cost_price: 0 });
    const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
    const nextIdx = window.StockInStore.intakeItems.length + 1;
    const trackingMode = isBlankCustom ? 'lots' : window.getItemMasterTrackingMode(p);
    const lotEnabled = trackingMode === 'lots';

    window.StockInStore.intakeItems.push({
        sku: isBlankCustom ? '' : (p.sku || p.item_code || `SPOT-${nextIdx}`),
        name: isBlankCustom ? '' : (p.name || p.item_name || 'Direct Spot Inbound Item'),
        specs: p.description || p.specs || '',
        uom: p.uom || p.base_uom || 'PCS',
        ordered_qty: 0,
        prev_recv_qty: 0,
        receive_now: 1,
        balance: 0,
        unit_cost: Number(p.cost_price ?? p.unit_cost ?? 0),
        vat_type: '12% VAT',
        trackingBy: trackingMode,
        lot_number: lotEnabled ? `LOT-${dateStr}-${nextIdx.toString().padStart(2, '0')}` : '',
        expiry_date: '',
        mfg_date: '',
        notes: '',
        is_adhoc: true,
        is_custom_unlisted: !!isBlankCustom,
    });

    renderIntakeTable();
    updateFulfillmentProgress();
    syncGrnStepActionButtons();
    updateLiveMockup();
    flashTableRow(window.StockInStore.intakeItems.length - 1);
    scheduleDraftSave();
};

window.removeAdHocRow = function(idx) {
    if (isViewingCompletedStepReadOnly()) {
        showGrnToast('Completed step is read-only. Return to active step to edit.', 'warning');
        return;
    }
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

    if (window.StockInStore.isCurrentGrnPosted) {
        const postedNo = window.StockInStore.lastPostedGrnNumber || 'Committed';
        if (statusTitle) statusTitle.textContent = 'Posted';
        if (statusDesc) statusDesc.textContent = postedNo;
        if (statusChip && statusChipText) {
            statusChip.setAttribute('data-state', 'ready');
            statusChipText.textContent = `Posted · ${postedNo}`;
        }
    } else if (window.StockInStore.qaInspectionState?.quarantined) {
        if (statusTitle) statusTitle.textContent = 'Quarantined';
        if (statusDesc) statusDesc.textContent = 'Held at QA Bench';
        if (statusChip && statusChipText) {
            statusChip.setAttribute('data-state', 'blocked');
            statusChipText.textContent = 'QA Failed · Quarantined';
        }
    } else if (sumOver > 0) {
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

    syncGrnStepActionButtons();
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

// 8. Live Document Mockup & A4 Printable Sheet Sync (mirrors RFQ Live Mockup A4 layout)
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
    const printSheet = document.getElementById('grnDedicatedPrintSheet');

    const poVal = poSelect?.value || '';
    const isPosted = !!window.StockInStore.isCurrentGrnPosted;
    const grnRef = (isPosted && window.StockInStore.lastPostedGrnNumber)
        ? window.StockInStore.lastPostedGrnNumber
        : (poVal ? `GRN-${poVal.replace(/^PO-?/, '')}` : 'GRN-DIRECT');

    if (docDisplay) docDisplay.textContent = grnRef;

    if (modeBadge && modeText) {
        if (isPosted) {
            modeBadge.className = 'grn-mode-tag awarded';
            modeText.textContent = `Posted: ${grnRef}`;
        } else if (poVal) {
            modeBadge.className = 'grn-mode-tag awarded';
            modeText.textContent = `PO: ${poVal}`;
        } else {
            modeBadge.className = 'grn-mode-tag sent';
            modeText.textContent = 'Direct Spot Inbound';
        }
    }

    if (!sheet && !printSheet) return;

    const companyName = window.StockInConfig?.company?.name || 'RMS Enterprise Logistics';
    const companyAddr = window.StockInConfig?.company?.address || 'Central Receiving & Warehousing Hub';
    const vendorName = vendorSelect?.options?.[vendorSelect.selectedIndex]?.text?.replace(/^.*—\s*/, '') || 'Select Target Vendor';
    const mtName = mtSelect?.options?.[mtSelect.selectedIndex]?.text || 'Standard Inbound Receipt';
    const sourceLoc = srcSelect?.value || 'Supplier / Vendor Origin';
    const destLoc = destSelect?.value || 'Storage Putaway';
    const etaDate = etaInput?.value || 'Scheduled';
    const timestamp = tsInput?.value ? tsInput.value.replace('T', ' ') : new Date().toISOString().slice(0, 16).replace('T', ' ');
    const slipNo = slipInput?.value?.trim() || 'Pending DR #';
    const notesText = notesInput?.value?.trim() || 'Physical dock count verified against delivery receipt.';

    const items = window.StockInStore.intakeItems || [];
    const showCostInMockup = activeGrnBuilderCols.includes('unit_cost') || activeGrnBuilderCols.includes('line_total');

    let sumOrdered = 0;
    let sumRecvNow = 0;
    let sumBalance = 0;
    let sumAmount = 0;
    items.forEach(it => {
        const recv = Number(it.receive_now || 0);
        sumOrdered += Number(it.ordered_qty || recv || 0);
        sumRecvNow += recv;
        sumBalance += Number(it.balance || 0);
        sumAmount += recv * Number(it.unit_cost || 0);
    });

    const PAGE_1_CAPACITY = 20;
    const SUBSEQUENT_PAGE_CAPACITY = 24;
    const pages = [];
    if (items.length === 0) {
        pages.push([]);
    } else {
        pages.push(items.slice(0, PAGE_1_CAPACITY));
        let cursor = PAGE_1_CAPACITY;
        while (cursor < items.length) {
            pages.push(items.slice(cursor, cursor + SUBSEQUENT_PAGE_CAPACITY));
            cursor += SUBSEQUENT_PAGE_CAPACITY;
        }
    }

    const totalPages = pages.length;
    const pageBadge = document.getElementById('grnMockupPageCountBadge');
    if (pageBadge) {
        pageBadge.textContent = `${totalPages} Page${totalPages > 1 ? 's' : ''} (A4)`;
    }

    const colSpanCount = showCostInMockup ? 7 : 6;
    const statusLabel = isPosted ? 'POSTED · SYS_LEDGER' : 'DRAFT · IN PROGRESS';
    const qa = window.StockInStore.qaInspectionState || {};
    const qaSummary = qa.quarantined ? `QA FAIL / QUARANTINED (${qa.tempC || '-'}°C)` : `Route: ${sourceLoc} -> ${destLoc}`;

    const userName = window.StockInConfig?.user?.name || 'Warehouse Receiver';
    const qtyFmt = (n) => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
    const theadHtml = `
        <thead>
            <tr>
                <th style="width: 24px; text-align: center;">#</th>
                <th style="width: 76px;">SKU</th>
                <th>Item Description &amp; Specifications</th>
                <th style="width: 44px; text-align: center;">UOM</th>
                <th style="width: 55px; text-align: right;">Ordered</th>
                <th style="width: 60px; text-align: right;">Received</th>
                <th style="width: 92px;">Lot / Expiry</th>
                ${showCostInMockup ? '<th style="width: 78px; text-align: right;">Amount ₱</th>' : ''}
            </tr>
        </thead>
    `;

    const renderRowsHtml = (slice, startOffset) => {
        if (!slice || slice.length === 0) {
            return `<tr><td colspan="${colSpanCount + 1}" style="text-align: center; padding: 22px 8px; color: #94a3b8; font-style: italic;">No line items loaded in intake sheet yet</td></tr>`;
        }
        return slice.map((it, idx) => {
            const recv = Number(it.receive_now || 0);
            const ord = Number(it.ordered_qty || 0);
            const amt = recv * Number(it.unit_cost || 0);
            const lotHtml = it.lot_number
                ? `<div style="font-family: monospace; font-weight: 700; color: #6d28d9; font-size: 0.60rem;">${escapeHtml(it.lot_number)}</div>${it.expiry_date ? `<div style="font-size: 0.58rem; color: #64748b;">Exp: ${escapeHtml(it.expiry_date)}</div>` : ''}`
                : `<span style="font-size: 0.60rem; color: #94a3b8;">Standard Qty</span>`;
            return `
            <tr>
                <td style="text-align: center; font-weight: 700; color: #64748b; font-size: 0.65rem;">${startOffset + idx + 1}</td>
                <td style="font-family: monospace; font-weight: 700; color: #9333ea; font-size: 0.65rem;">${escapeHtml(it.sku || '--')}</td>
                <td>
                    <div style="font-weight: 700; color: #0f172a; font-size: 0.68rem;">${escapeHtml(it.name || it.sku || 'Item')}</div>
                    ${it.specs ? `<div style="font-size: 0.60rem; color: #64748b; margin-top: 1px;">${escapeHtml(it.specs)}</div>` : ''}
                </td>
                <td style="text-align: center; font-weight: 600; color: #475569; font-size: 0.65rem;">${escapeHtml(it.uom || 'PCS')}</td>
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: #475569; font-size: 0.67rem;">${ord > 0 ? qtyFmt(ord) : '—'}</td>
                <td style="text-align: right; font-family: monospace; font-weight: 800; color: #047857; font-size: 0.67rem;">${qtyFmt(recv)}</td>
                <td>${lotHtml}</td>
                ${showCostInMockup ? `<td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a; font-size: 0.67rem;">₱${amt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>` : ''}
            </tr>
            `;
        }).join('');
    };

    const tfootHtml = `
        <tfoot>
            <tr style="background: #f8fafc; border-top: 1.5px solid #cbd5e1;">
                <td colspan="4" style="text-align: right; font-weight: 700; font-size: 0.65rem; color: #475569;">Totals (${items.length} Line${items.length === 1 ? '' : 's'}):</td>
                <td style="text-align: right; font-weight: 700; font-size: 0.67rem; font-family: monospace; color: #0f172a;">${qtyFmt(sumOrdered)}</td>
                <td style="text-align: right; font-weight: 800; font-size: 0.70rem; font-family: monospace; color: #047857;">${qtyFmt(sumRecvNow)}</td>
                <td style="font-size: 0.62rem; font-weight: 700; color: #b45309;">Backlog: ${qtyFmt(sumBalance)}</td>
                ${showCostInMockup ? `<td style="text-align: right; font-weight: 800; font-size: 0.70rem; font-family: monospace; color: #0f172a;">₱${sumAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>` : ''}
            </tr>
        </tfoot>
    `;

    let pagesHtml = '';
    let startIdx = 0;
    pages.forEach((pageItems, pIdx) => {
        const pageNumber = pIdx + 1;
        const isFirst = pIdx === 0;
        const isLast = pIdx === totalPages - 1;

        const endingHtml = isLast ? `
            <div class="mockup-terms-disclaimer">
                <div class="mockup-disclaimer-title"><i class="ph ph-info"></i> Receiving Remarks &amp; Ledger Notes:</div>
                <div class="mockup-disclaimer-body">
                    1. ${escapeHtml(notesText)}<br>
                    2. Quantities received under <strong>${escapeHtml(mtName)}</strong> are stamped to the immutable <strong>SYS_LEDGER</strong>.<br>
                    3. Discrepancies, damages, or short deliveries must be reported to Procurement within 24 hours.
                </div>
            </div>

            <div class="mockup-signatures-grid">
                <div class="mockup-sig-box">
                    <div class="mockup-sig-title">Delivered By (Carrier / Vendor):</div>
                    <div class="mockup-sig-line">
                        <span>Signature over Printed Name</span>
                        <span>Date: _________</span>
                    </div>
                </div>
                <div class="mockup-sig-box">
                    <div class="mockup-sig-title">Received &amp; Inspected By:</div>
                    <div class="mockup-sig-line">
                        <span>${escapeHtml(userName)}</span>
                        <span>Date: ${escapeHtml(timestamp.slice(0, 10))}</span>
                    </div>
                </div>
            </div>

            <div class="mockup-page-footer">
                <span>RMS Warehouse Official Goods Receipt Document</span>
                <span>Page ${pageNumber} of ${totalPages}</span>
            </div>
        ` : `
            <div class="mockup-page-continuation-banner">
                <span><i class="ph ph-arrow-down" style="vertical-align: middle;"></i> Continued on Page ${pageNumber + 1}...</span>
                <span>Page ${pageNumber} of ${totalPages}</span>
            </div>
        `;

        if (isFirst) {
            pagesHtml += `
                <div class="rfq-live-mockup-paper">
                    <div class="mockup-sheet-header">
                        <div class="mockup-brand-block">
                            <div class="mockup-brand-title">${escapeHtml(companyName.toUpperCase())}</div>
                            <div class="mockup-brand-subtitle">${escapeHtml(companyAddr)} • Warehouse Receiving Division</div>
                        </div>
                        <div class="mockup-badge-block">
                            <div class="mockup-badge-tag">GOODS RECEIPT NOTE</div>
                            <div class="mockup-meta-kv">Ref: <strong>${escapeHtml(grnRef)}</strong></div>
                            <div class="mockup-meta-kv">Received: <strong>${escapeHtml(timestamp)}</strong></div>
                            <div class="mockup-meta-kv mockup-due-accent">Status: <strong>${escapeHtml(statusLabel)}</strong></div>
                        </div>
                    </div>

                    <div class="mockup-parties-grid">
                        <div class="mockup-party-box">
                            <div class="mockup-party-heading">SUPPLIER &amp; DELIVERY</div>
                            <div class="mockup-party-name">${escapeHtml(vendorName)}</div>
                            <div class="mockup-party-sub">PO Ref: <strong>${escapeHtml(poVal || 'Direct Spot Intake')}</strong></div>
                            <div class="mockup-party-sub">DR #: <strong>${escapeHtml(slipNo)}</strong></div>
                            <div class="mockup-party-sub">Expected (ETA): <span>${escapeHtml(etaDate)}</span></div>
                        </div>
                        <div class="mockup-party-box">
                            <div class="mockup-party-heading">MOVEMENT ROUTE &amp; PUTAWAY</div>
                            <div class="mockup-party-name">${escapeHtml(mtName)}</div>
                            <div class="mockup-party-sub">${escapeHtml(qaSummary)}</div>
                            <div class="mockup-party-sub">Ledger Target: <strong>SYS_LEDGER (Immutable)</strong></div>
                        </div>
                    </div>

                    <div class="mockup-table-container">
                        <table class="mockup-table">
                            ${theadHtml}
                            <tbody>${renderRowsHtml(pageItems, startIdx)}</tbody>
                            ${isLast ? tfootHtml : ''}
                        </table>
                    </div>

                    ${endingHtml}
                </div>
            `;
        } else {
            pagesHtml += `
                <div class="mockup-page-divider">
                    <span class="mockup-page-divider-pill">PAGE ${pageNumber} OF ${totalPages}</span>
                </div>

                <div class="rfq-live-mockup-paper">
                    <div class="mockup-sheet-header-mini">
                        <div>
                            <strong>${escapeHtml(companyName.toUpperCase())}</strong>
                            <span style="font-size: 0.65rem; color: #64748b; font-weight: normal; margin-left: 4px;">• Goods Receipt Note</span>
                        </div>
                        <span>Ref: ${escapeHtml(grnRef)} (Cont.)</span>
                    </div>

                    <div class="mockup-table-container">
                        <table class="mockup-table">
                            ${theadHtml}
                            <tbody>${renderRowsHtml(pageItems, startIdx)}</tbody>
                            ${isLast ? tfootHtml : ''}
                        </table>
                    </div>

                    ${endingHtml}
                </div>
            `;
        }
        startIdx += pageItems.length;
    });

    if (sheet) {
        sheet.innerHTML = pagesHtml;
    }
    syncGrnDedicatedPrintSheet(pagesHtml);
};

// Dedicated A4 print sheet lives directly under <body> (mirrors #rfqDedicatedPrintSheet)
function syncGrnDedicatedPrintSheet(html) {
    let printSheet = document.getElementById('grnDedicatedPrintSheet');
    if (!printSheet && document.body && typeof document.createElement === 'function') {
        printSheet = document.createElement('div');
        printSheet.id = 'grnDedicatedPrintSheet';
        printSheet.className = 'rfq-dedicated-print-sheet';
        document.body.appendChild(printSheet);
    }
    if (!printSheet) return;
    if (typeof html === 'string') {
        printSheet.innerHTML = html;
    } else {
        const container = document.getElementById('grnLiveMockupSheet');
        if (container) printSheet.innerHTML = container.innerHTML;
    }
}

// Print / PDF: identical flow to openRfqDocumentPreview() — sync A4 sheet, then native print (Save as PDF)
window.openGrnDocumentPreview = function() {
    updateLiveMockup();
    syncGrnDedicatedPrintSheet();
    setTimeout(() => {
        window.print();
    }, 80);
};

window.printGrnDocument = function() {
    openGrnDocumentPreview();
};

// Global browser print listener for Ctrl+P or browser menu print
if (typeof window !== 'undefined' && typeof window.addEventListener === 'function') {
    window.addEventListener('beforeprint', () => {
        if (typeof window.updateLiveMockup === 'function') {
            window.updateLiveMockup();
        }
        syncGrnDedicatedPrintSheet();
    });
}

// Close floating menus when clicking outside & elevate open table UOM menus above scroll viewport
document.addEventListener('click', (e) => {
    const qDd = document.getElementById('queueTableSettingsDropdown');
    if (qDd && !qDd.contains(e.target) && e.target.id !== 'btnQueueTableSettings') {
        qDd.style.display = 'none';
    }
    const bDd = document.getElementById('grnBuilderSettingsDropdown');
    if (bDd && !bDd.contains(e.target) && e.target.id !== 'btnGrnBuilderSettings') {
        bDd.style.display = 'none';
    }
    const uDd = document.getElementById('grnUnplannedItemDropdown');
    const uBtn = document.getElementById('btnAddUnplannedItem');
    if (uDd && !uDd.hidden && !uDd.contains(e.target) && !uBtn?.contains(e.target)) {
        closeUnplannedItemPicker();
    }
    const poDd = document.getElementById('grnPoPickerDropdown');
    const poBtn = document.getElementById('grnPoPickerTrigger');
    if (poDd && !poDd.hidden && !poDd.contains(e.target) && !poBtn?.contains(e.target)) {
        closeGrnPoPicker();
    }
    const hdrDd = document.getElementById('grnHeaderFieldPickerDropdown');
    if (hdrDd && !hdrDd.hidden && !hdrDd.contains(e.target) && !e.target?.closest?.('.grn-header-picker-trigger')) {
        closeGrnHeaderPicker();
    }
    const rowDd = document.getElementById('grnRowItemPickerDropdown');
    if (rowDd && !rowDd.hidden && !rowDd.contains(e.target) && !e.target?.closest?.('.grn-item-picker-trigger')) {
        closeRowItemPicker();
    }

    const uomTrigger = e.target?.closest?.('#intakeTableBody .uom-dropdown-trigger');
    if (uomTrigger && typeof window.requestAnimationFrame === 'function') {
        window.requestAnimationFrame(() => {
            const wrapper = uomTrigger.closest('.uom-dropdown-wrapper');
            const menu = wrapper?.querySelector('.uom-dropdown-menu');
            if (!wrapper || !menu || !wrapper.classList.contains('is-open')) return;
            const rect = uomTrigger.getBoundingClientRect();
            const menuW = Math.max(220, rect.width);
            const menuH = 290;
            const spaceBelow = window.innerHeight - rect.bottom;
            const top = (spaceBelow < menuH && rect.top > menuH)
                ? Math.max(8, rect.top - menuH - 4)
                : Math.min(window.innerHeight - 60, rect.bottom + 4);
            const left = Math.max(8, Math.min(rect.left, window.innerWidth - menuW - 8));
            menu.style.position = 'fixed';
            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
            menu.style.width = `${menuW}px`;
            menu.style.zIndex = '10060';
        });
    }
});

// Close fixed-positioned UOM menus if the intake table scrolls
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const tc = document.getElementById('grnTableContainer');
        if (tc) {
            tc.addEventListener('scroll', () => {
                closeRowItemPicker();
                tc.querySelectorAll('.uom-dropdown-wrapper.is-open').forEach(w => {
                    w.classList.remove('is-open');
                    const trig = w.querySelector('.uom-dropdown-trigger');
                    if (trig) trig.setAttribute('aria-expanded', 'false');
                });
            }, { passive: true });
        }
        window.addEventListener('resize', () => {
            closeGrnPoPicker();
            closeGrnHeaderPicker();
            closeRowItemPicker();
            const qDd = document.getElementById('queueTableSettingsDropdown');
            if (qDd) qDd.style.display = 'none';
            const bDd = document.getElementById('grnBuilderSettingsDropdown');
            if (bDd) bDd.style.display = 'none';
        });
        window.addEventListener('scroll', (ev) => {
            if (ev?.target?.closest?.('.grn-unplanned-popover')) return;
            closeGrnPoPicker();
            closeGrnHeaderPicker();
            closeRowItemPicker();
        }, true);
    });
}

// 9. Confirm Receipt & Putaway (Atomic Vectorized Execution)
window.confirmAndCommitStockIntake = function(fromFinalGate = false) {
    const mt = getActiveInboundMovementType();
    const steps = getActiveRouteSteps(mt);
    const activeIdx = Math.max(0, Math.min(Number(window.StockInStore.activeRouteStepIndex || 0), steps.length - 1));
    if (!fromFinalGate && (isViewingCompletedStepReadOnly() || activeIdx < steps.length - 1 || !evaluateGrnStepValidation().valid)) {
        handleGrnPrimaryStepAction();
        return;
    }

    const validation = evaluateGrnStepValidation();
    if (!validation.valid) {
        showGrnToast(validation.reason, 'warning');
        return;
    }

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
        const shortage = getShortageLinesAndSignature();
        if (shortage.isPartial) {
            // If user already chose policy in earlier stage (Vendor > Receipt > QC) for these exact quantities, proceed directly!
            if (window.StockInStore.confirmedShortageSignature === shortage.signature) {
                executeIntakeSubmission(window.StockInStore.backorderPolicy || 'CREATE_BACKORDER');
                return;
            }
            const boModal = document.getElementById('grnBackorderModal');
            if (boModal) {
                openGrnBackorderModal('Final Putaway → Stocks', () => {
                    executeIntakeSubmission(window.StockInStore.backorderPolicy || 'CREATE_BACKORDER');
                });
                return;
            }
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

        executeIntakeSubmission(window.StockInStore.backorderPolicy || 'CREATE_BACKORDER');
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
    const vendorName = vendorSelect?.options?.[vendorSelect.selectedIndex]?.text || 'Vendor';
    const activeMt = getActiveInboundMovementType();
    const warehouseId = whSelect?.value || activeMt?.warehouse_id || 'WH-MAIN';
    const movementTypeId = mtSelect?.value || activeMt?.id || 'WH-MAIN-IN';
    const deliverySlip = (slipInput?.value || '').trim();
    const carrier = (carrierInput?.value || '').trim();
    const destSelect = document.getElementById('intakeDestinationLocationSelect');
    const destinationLocation = destSelect?.value || 'Stock Zone A (Dry Goods)';
    const matchedWh = (window.StockInStore.warehouses || []).find(w => w.id === warehouseId);
    const deliveryLocation = whSelect?.options?.[whSelect.selectedIndex]?.text || matchedWh?.name || destinationLocation || warehouseId;

    const resolveClientUomMultiplier = (fromUom, toUom) => {
        const f = String(fromUom || '').trim().toUpperCase();
        const t = String(toUom || '').trim().toUpperCase();
        if (!f || !t || f === t) return 1.0;
        if (window.UomRegistry && window.UomRegistry.isLoaded && typeof window.UomRegistry.convert === 'function') {
            const converted = window.UomRegistry.convert(1, f, t);
            if (converted !== null && isFinite(converted) && converted > 0) return Number(converted);
        }
        const factors = { MG: 0.001, G: 1, GRAM: 1, KG: 1000, LB: 453.59237, SACK: 25000, ML: 1, L: 1000, LITER: 1000, GAL: 3785.41, PCS: 1, DOZ: 12 };
        const dims = { MG: 'M', G: 'M', GRAM: 'M', KG: 'M', LB: 'M', SACK: 'M', ML: 'V', L: 'V', LITER: 'V', GAL: 'V', PCS: 'C', DOZ: 'C' };
        if (dims[f] && dims[f] === dims[t] && factors[f] && factors[t]) {
            return factors[f] / factors[t];
        }
        return 1.0;
    };

    const activeLines = window.StockInStore.intakeItems
        .filter(it => (Number(it.receive_now || 0) + Number(it.rejected_qty || 0)) > 0)
        .map(it => {
            const prod = (window.StockInStore.products || []).find(p => p.sku === it.sku || p.item_code === it.sku);
            const baseUom = it.base_uom || prod?.uom || it.uom;
            const multiplier = resolveClientUomMultiplier(it.uom, baseUom);
            return {
                purchase_order_item_id: it.purchase_order_item_id || null,
                sku: it.sku,
                name: it.name,
                unit: it.uom,
                uom_multiplier: multiplier,
                received_qty: Number(it.receive_now || 0),
                rejected_qty: Number(it.rejected_qty || 0),
                rejection_reason: it.rejection_reason || null,
                unit_cost: Number(it.unit_cost || 0),
                storage_location: destinationLocation,
                lot_number: it.lot_number,
                expiry_date: it.expiry_date,
                notes: it.notes || (notesInput?.value || null),
            };
        });

    const clientUuid = `intake-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

    const payload = {
        po_number: poNumber,
        vendor_id: vendorId,
        vendor_name: vendorName,
        warehouse_id: warehouseId,
        movement_type_id: movementTypeId,
        delivery_location: deliveryLocation,
        delivery_slip: deliverySlip,
        carrier: carrier,
        expected_delivery: normalizeIsoDate(document.getElementById('intakeExpectedDelivery')?.value || '') || null,
        received_at: normalizeIsoDate(document.getElementById('intakeReceivedAt')?.value || '') || null,
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

        const postedReceivedAt = normalizeIsoDate(data.received_at || document.getElementById('intakeReceivedAt')?.value || '') || getTodayIsoDate();
        const postedEta = normalizeIsoDate(document.getElementById('intakeExpectedDelivery')?.value || '');

        if (poNumber) {
        window.StockInStore.isCurrentGrnPosted = false;
    window.StockInStore.lastPostedGrnNumber = null;
    window.StockInStore.backorderPolicy = 'CREATE_BACKORDER';
    window.StockInStore.confirmedShortageSignature = null;
    window.StockInStore.pendingBackorderContinuation = null;
    const transitBanner = document.getElementById('grnStageTransitBanner');
    if (transitBanner) transitBanner.hidden = true;
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

                if (postedEta) {
                    po.expected_delivery = postedEta;
                    po.expected_delivery_date = postedEta;
                }
                po.arrival_date = postedReceivedAt;

                if (backorderPolicy === 'SHORT_CLOSE') {
                    po.status = 'Fully Received';
                } else {
                    po.status = allDone ? 'Fully Received' : 'Partially Received';
                }
            }
        }

        // Persist posted receipt record in store for queue/builder historical inspection
        const existingReceipts = window.StockInStore.goodsReceipts || [];
        existingReceipts.unshift({
            grn_number: grnNo,
            po_number: poNumber || null,
            received_at: postedReceivedAt,
            delivery_slip: document.getElementById('intakeDeliverySlip')?.value || '',
            notes: document.getElementById('intakeDeliveryNotes')?.value || '',
            items: (window.StockInStore.intakeItems || []).map(it => Object.assign({}, it))
        });
        window.StockInStore.goodsReceipts = existingReceipts;

        // Keep user in the Builder showing the completed posted GRN ending (all steps + Status green, read-only)
        window.StockInStore.isCurrentGrnPosted = true;
        window.StockInStore.lastPostedGrnNumber = grnNo;
        const activeMt = getActiveInboundMovementType();
        const mtSteps = getActiveRouteSteps(activeMt);
        window.StockInStore.completedRouteSteps = mtSteps.map((_, idx) => idx);
        window.StockInStore.unlockedRouteStepIndex = Math.max(0, mtSteps.length - 1);
        window.StockInStore.activeRouteStepIndex = Math.max(0, mtSteps.length - 1);

        renderGrnChevronPipeline(activeMt);
        renderIntakeTable();
        updateFulfillmentProgress();
        updateLiveMockup();
        renderOrdersQueue();
        renderKpis();
        syncGrnBackorderStageBanner();
        const finalStepObj = mtSteps[mtSteps.length - 1] || {};
        triggerGrnStageMovementSimulation(
            finalStepObj.from_area || 'Receiving / QA',
            finalStepObj.to_area || 'Stocks Ledger',
            Math.max(0, mtSteps.length - 1),
            mtSteps.length,
            mtSteps.length
        );

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
                confirmButtonText: 'View Completed GRN',
                showCancelButton: true,
                cancelButtonText: 'Return to Orders Queue'
            }).then((res) => {
                if (res?.dismiss === Swal.DismissReason?.cancel) {
                    resetIntakeSheet(false);
                    switchGrnMode('queue');
                }
            });
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
            handleGrnPrimaryStepAction();
        }
    } else if (e.key === 'F8') {
        if (window.StockInStore.activeMode === 'builder') {
            e.preventDefault();
            openGrnDocumentPreview();
        }
    } else if (e.key === 'Escape') {
        const boModal = document.getElementById('grnBackorderModal');
        if (boModal && boModal.style.display !== 'none') {
            closeGrnBackorderModal();
            return;
        }
        const lotModal = document.getElementById('grnLotExpiryModal');
        if (lotModal && lotModal.style.display !== 'none') {
            closeLotExpiryModal();
            return;
        }
        toggleGrnMockupDrawer(false);
        closeUnplannedItemPicker();
        closeGrnPoPicker();
        closeGrnHeaderPicker();
        closeRowItemPicker();
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
