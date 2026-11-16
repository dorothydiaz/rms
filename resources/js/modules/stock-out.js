/* ============================================================================
   RMS STOCK OUT / USAGE ORCHESTRATOR (Stock In / GRN Parity)
   Dual-Mode ERP Workspace:
     Mode A: List of Dispatches (Queue, KPI Cards & Custom Columns)
     Mode B: Stock Out & Usage Sheet (Draft Builder, Direct Outbound & Pick-Pack-Ship)
   ============================================================================ */

window.StockOutStore = {
    orders: window.StockOutConfig?.data?.initialOrders || [],
    products: window.StockOutConfig?.data?.initialProducts || [],
    vendors: window.StockOutConfig?.data?.initialVendors || [],
    categories: window.StockOutConfig?.data?.initialCategories || [],
    warehouses: window.StockOutConfig?.data?.initialWarehouses || [],
    storageAreas: window.StockOutConfig?.data?.initialStorageAreas || [],
    movementTypes: window.StockOutConfig?.data?.initialMovementTypes || [],

    activeTab: 'tab-out-list',
    builderMode: 'draft', // 'draft' | 'direct' | 'pick'
    activeRouteStepIndex: 0,
    draftItems: [],
    activePickOrder: null,
};

// Backward-compatible state aliases synced with StockOutStore
let rawOrders = window.StockOutStore.orders;
let rawProducts = window.StockOutStore.products;
let rawVendors = window.StockOutStore.vendors;
let rawCategories = window.StockOutStore.categories;
let draftItems = window.StockOutStore.draftItems;
let activePickOrder = null;

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return String(dateStr).slice(0, 10);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
        return String(dateStr).slice(0, 10);
    }
}

function formatOrderType(type) {
    if (!type) return 'Kitchen Line Usage';
    switch (type) {
        case 'KITCHEN_USAGE': return 'Kitchen Line Usage';
        case 'BRANCH_TRANSFER': return 'Inter-Branch Transfer';
        case 'VENDOR_RETURN': return 'Vendor Return (RMA)';
        case 'SPOILAGE_DISPOSAL': return 'Spoilage / Disposal';
        case 'STAFF_MEALS': return 'Staff Meals';
        case 'SAMPLE_TASTING': return 'Sample Tasting';
        default: return String(type).replace(/_/g, ' ');
    }
}

function showOutToast(message, type = 'success') {
    const container = document.getElementById('outToastContainer');
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
// A. TAB 1: QUEUE COLUMNS & GEAR CUSTOMIZER (Stock In / RFQ Parity)
// ============================================================================
const OUT_QUEUE_COLUMNS = [
    { id: 'order_number', label: 'SO Reference', subLabel: 'Order ID & Timestamp', default: true, lockVisible: true, defaultWidth: '145px', align: 'left' },
    { id: 'order_type', label: 'Usage Purpose', subLabel: 'Reason Code', default: true, lockVisible: false, defaultWidth: '155px', align: 'left' },
    { id: 'destination', label: 'Destination / Partner', subLabel: 'Target Location', default: true, lockVisible: false, defaultWidth: '215px', align: 'left' },
    { id: 'requester', label: 'Requester & Dept', subLabel: 'Requested By', default: true, lockVisible: false, defaultWidth: '160px', align: 'left' },
    { id: 'priority', label: 'Priority', subLabel: 'Urgency', default: false, lockVisible: false, defaultWidth: '95px', align: 'left' },
    { id: 'lines_qty', label: 'Lines & Qty', subLabel: 'Requested Total', default: true, lockVisible: false, defaultWidth: '110px', align: 'right' },
    { id: 'packed_qty', label: 'Packed', subLabel: 'Staged / Shipped', default: false, lockVisible: false, defaultWidth: '100px', align: 'right' },
    { id: 'progress', label: 'Fulfillment Progress', subLabel: 'Packed vs Requested', default: true, lockVisible: false, defaultWidth: '165px', align: 'left' },
    { id: 'valuation', label: 'Valuation (₱)', subLabel: 'Estimated Cost', default: true, lockVisible: false, defaultWidth: '125px', align: 'right' },
    { id: 'picker_waybill', label: 'Picker & Waybill', subLabel: 'Custody Info', default: false, lockVisible: false, defaultWidth: '155px', align: 'left' },
    { id: 'status', label: 'Status', subLabel: 'Dispatch State', default: true, lockVisible: false, defaultWidth: '130px', align: 'left' },
    { id: 'actions', label: 'Action', subLabel: 'Pick / Inspect', default: true, lockVisible: true, defaultWidth: '140px', align: 'right' },
];

let outQueueColOrder = OUT_QUEUE_COLUMNS.map(c => c.id);
let activeOutQueueColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_out_queue_cols_v1');
        if (saved) {
            const parsed = JSON.parse(saved);
            const validIds = OUT_QUEUE_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.length >= 4 && parsed.every(id => validIds.includes(id))) {
                return parsed;
            }
        }
    } catch (e) {}
    return OUT_QUEUE_COLUMNS.filter(c => c.default).map(c => c.id);
})();
let savedOutQueueWidths = {};
let outQueueColSearchTerm = '';
let draggedOutQueueColId = null;

function saveOutQueueColsState() {
    try {
        localStorage.setItem('rms_out_queue_cols_v1', JSON.stringify(activeOutQueueColIds));
    } catch (e) {}
}

window.renderOutQueueTableHeader = function () {
    const theadRow = document.getElementById('ordersTableTheadRow');
    if (!theadRow) return;

    const visibleCols = outQueueColOrder
        .filter(id => activeOutQueueColIds.includes(id))
        .map(id => OUT_QUEUE_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let thHtml = '';
    visibleCols.forEach(col => {
        const w = savedOutQueueWidths[col.id] || col.defaultWidth || '120px';
        const isRight = col.align === 'right';
        const alignClass = isRight ? 'th-num' : 'th-left';

        if (col.id === 'actions') {
            thHtml += `
                <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width: ${w}; text-align: right; user-select: none;">
                    <div class="rfq-header-action-wrapper" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                        <div class="rfq-th-content" style="align-items: flex-end; text-align: right;">
                            <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                            <div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>
                        </div>
                        <button type="button" class="rfq-th-settings-btn" id="btnOutQueueTableSettings" onclick="toggleOutQueueSettingsDropdown(event)" title="Customize Columns & Sequence">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            thHtml += `
                <th class="rfq-th-stacked ${alignClass}" data-col-id="${col.id}" style="width: ${w}; text-align: ${isRight ? 'right' : 'left'}; position: relative; user-select: none;">
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
    renderOutQueueColChecklist();
};

function renderOutQueueColChecklist() {
    const listEl = document.getElementById('outQueueColCheckboxesList');
    if (!listEl) return;

    const query = (outQueueColSearchTerm || '').toLowerCase().trim();
    const orderedCols = outQueueColOrder.map(id => OUT_QUEUE_COLUMNS.find(c => c.id === id)).filter(Boolean);

    listEl.innerHTML = orderedCols
        .filter(col => !query || col.label.toLowerCase().includes(query) || (col.subLabel && col.subLabel.toLowerCase().includes(query)))
        .map(col => {
            const isChecked = activeOutQueueColIds.includes(col.id);
            const isDisabled = col.lockVisible ? 'disabled' : '';
            return `
                <div class="rfq-dropdown-col-item" data-col-id="${col.id}" draggable="${col.id !== 'actions' ? 'true' : 'false'}"
                    ondragstart="handleOutQueueColDragStart(event, '${col.id}')"
                    ondragover="handleOutQueueColDragOver(event)"
                    ondrop="handleOutQueueColDrop(event, '${col.id}')">
                    ${col.id !== 'actions'
                        ? `<span class="rfq-drag-handle" title="Drag to reorder column sequence"><i class="ph ph-dots-six-vertical"></i></span>`
                        : `<span style="width: 14px; display: inline-block;"></span>`}
                    <label class="rfq-col-item-label" title="${escapeHtml(col.label)}">
                        <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                            <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleOutQueueColVisibility('${col.id}', this.checked)">
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <span class="rfq-col-name">${escapeHtml(col.label)}</span>
                                ${col.subLabel ? `<span class="rfq-col-subname">${escapeHtml(col.subLabel)}</span>` : ''}
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            ${col.default ? '<span class="rfq-col-badge-default">Default</span>' : ''}
                            ${col.lockVisible ? '<span style="font-size: 0.65rem; color:#94a3b8; font-weight:600;">(Locked)</span>' : ''}
                        </div>
                    </label>
                </div>
            `;
        }).join('');

    const counter = document.getElementById('outQueueColActiveCounter');
    if (counter) counter.textContent = `${activeOutQueueColIds.length} of ${OUT_QUEUE_COLUMNS.length} visible`;
}

window.toggleOutQueueSettingsDropdown = function (event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('outQueueTableSettingsDropdown');
    if (!dd) return;

    if (dd.style.display === 'block') {
        dd.style.display = 'none';
        return;
    }

    renderOutQueueColChecklist();
    dd.style.display = 'block';

    const trigger = event?.currentTarget || document.getElementById('btnOutQueueTableSettings') || document.getElementById('btnOutQueueColConfig');
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

window.toggleOutQueueColVisibility = function (colId, isChecked) {
    const col = OUT_QUEUE_COLUMNS.find(c => c.id === colId);
    if (!col || col.lockVisible) return;
    if (isChecked) {
        if (!activeOutQueueColIds.includes(colId)) activeOutQueueColIds.push(colId);
    } else {
        activeOutQueueColIds = activeOutQueueColIds.filter(id => id !== colId);
    }
    saveOutQueueColsState();
    renderOutQueueTableHeader();
    renderOrdersList();
};

window.resetOutQueueColumnDefaults = function () {
    outQueueColOrder = OUT_QUEUE_COLUMNS.map(c => c.id);
    activeOutQueueColIds = OUT_QUEUE_COLUMNS.filter(c => c.default).map(c => c.id);
    saveOutQueueColsState();
    renderOutQueueTableHeader();
    renderOrdersList();
    showOutToast('Queue columns reset to default');
};

window.showAllOutQueueColumns = function () {
    activeOutQueueColIds = OUT_QUEUE_COLUMNS.map(c => c.id);
    saveOutQueueColsState();
    renderOutQueueTableHeader();
    renderOrdersList();
    showOutToast('Showing all Dispatches Queue columns');
};

window.resetOutQueueColumnWidths = function () {
    savedOutQueueWidths = {};
    renderOutQueueTableHeader();
    renderOrdersList();
    showOutToast('Column widths reset');
};

window.handleOutQueueColSearch = function (val) {
    outQueueColSearchTerm = val || '';
    renderOutQueueColChecklist();
};

window.handleOutQueueColDragStart = function (e, colId) {
    draggedOutQueueColId = colId;
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
};

window.handleOutQueueColDragOver = function (e) {
    e.preventDefault();
};

window.handleOutQueueColDrop = function (e, targetColId) {
    e.preventDefault();
    if (!draggedOutQueueColId || draggedOutQueueColId === targetColId || targetColId === 'actions') return;
    const fromIdx = outQueueColOrder.indexOf(draggedOutQueueColId);
    const toIdx = outQueueColOrder.indexOf(targetColId);
    if (fromIdx === -1 || toIdx === -1) return;
    outQueueColOrder.splice(fromIdx, 1);
    outQueueColOrder.splice(toIdx, 0, draggedOutQueueColId);
    draggedOutQueueColId = null;
    renderOutQueueTableHeader();
    renderOrdersList();
};

// ============================================================================
// B. TAB 2 BUILDER COLUMNS & GEAR CUSTOMIZER (Stock In Sheet Parity)
// ============================================================================
const OUT_BUILDER_COLUMNS = [
    { id: 'index', label: '#', category: 'Core Stock Specs', lockVisible: true, default: true, align: 'center', width: '40px' },
    { id: 'item_name', label: 'Item Description & Specs', category: 'Core Stock Specs', lockVisible: true, default: true, align: 'left', minWidth: '220px' },
    { id: 'uom', label: 'UOM', category: 'Core Stock Specs', lockVisible: false, default: true, align: 'center', width: '78px' },
    { id: 'on_hand', label: 'Stock on Hand', category: 'Stock Verification', lockVisible: false, default: true, align: 'right', width: '105px' },
    { id: 'requested_qty', label: 'Req Qty', category: 'Pick & Pack Execution', lockVisible: true, default: true, align: 'center', width: '95px' },
    { id: 'issue_now', label: 'Pack / Issue Now', category: 'Pick & Pack Execution', lockVisible: true, default: true, align: 'center', width: '165px' },
    { id: 'progress', label: 'Progress & Status', category: 'Pick & Pack Execution', lockVisible: false, default: true, align: 'left', width: '155px' },
    { id: 'unit_cost', label: 'Unit Cost (₱)', category: 'Cost & Amount', lockVisible: false, default: false, align: 'right', width: '100px' },
    { id: 'line_total', label: 'Line Valuation (₱)', category: 'Cost & Amount', lockVisible: false, default: true, align: 'right', width: '115px' },
];

const OUT_DEFAULT_BUILDER_COLS = OUT_BUILDER_COLUMNS.filter(c => c.default).map(c => c.id);
let activeOutBuilderCols = (() => {
    try {
        const saved = localStorage.getItem('rms_out_builder_cols_v1');
        if (saved) {
            const parsed = JSON.parse(saved);
            const validIds = OUT_BUILDER_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.every(id => validIds.includes(id))) {
                return parsed;
            }
        }
    } catch (e) {}
    return [...OUT_DEFAULT_BUILDER_COLS];
})();

function saveOutBuilderColsState() {
    try {
        localStorage.setItem('rms_out_builder_cols_v1', JSON.stringify(activeOutBuilderCols));
    } catch (e) {}
}

function positionOutBuilderSettingsDropdown() {
    const dd = document.getElementById('outBuilderSettingsDropdown');
    const btn = document.getElementById('btnOutBuilderSettings');
    if (!dd || !btn) return;
    const rect = btn.getBoundingClientRect();
    const dropdownWidth = 300;
    const viewportW = window.innerWidth || 1280;
    const viewportH = window.innerHeight || 800;

    let left = rect.right - dropdownWidth;
    if (left < 12) left = 12;
    if (left + dropdownWidth > viewportW - 12) left = Math.max(12, viewportW - dropdownWidth - 12);

    let top = rect.bottom + 6;
    if (top + 340 > viewportH - 12 && rect.top > 340) {
        top = Math.max(12, rect.top - 340 - 6);
    }

    dd.style.position = 'fixed';
    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.right = 'auto';
    dd.style.zIndex = '9999';
}

window.renderOutBuilderTableHeader = function () {
    const theadRow = document.getElementById('outBuilderTheadRow');
    if (!theadRow) return;

    const wasOpen = document.getElementById('outBuilderSettingsDropdown')?.style.display === 'block';
    const visibleCols = OUT_BUILDER_COLUMNS.filter(c => c.lockVisible || activeOutBuilderCols.includes(c.id));

    let html = visibleCols.map(col => {
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');
        const wStyle = col.width ? `width: ${col.width};` : (col.minWidth ? `min-width: ${col.minWidth};` : '');
        return `<th class="${alignClass}" style="${wStyle}">${escapeHtml(col.label)}</th>`;
    }).join('');

    html += `
        <th class="th-center" style="width: 112px; position: relative;">
            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; position: relative;">
                <span>Action</span>
                <button type="button" class="rfq-th-settings-btn" id="btnOutBuilderSettings" onclick="toggleOutBuilderSettingsDropdown(event)" title="Customize Stock Out Sheet Columns">
                    <i class="ph ph-gear"></i>
                </button>
            </div>
            <div id="outBuilderSettingsDropdown" class="po-builder-settings-dropdown rfq-builder-settings-dropdown grn-settings-dropdown" style="display: ${wasOpen ? 'block' : 'none'}; text-align: left;" onclick="event.stopPropagation()">
                <div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 800; font-size: 12px; color: #0f172a;"><i class="ph ph-gear-six" style="color: #9333ea;"></i> Sheet Columns</span>
                    <span style="font-size: 10.5px; font-weight: 700; color: #7e22ce; background: #f3e8ff; padding: 2px 7px; border-radius: 8px;">${visibleCols.length} of ${OUT_BUILDER_COLUMNS.length}</span>
                </div>
                <div style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; gap: 6px; background: #ffffff;">
                    <button type="button" class="inv-col-quick-link" onclick="resetOutBuilderColumnsDefault()" style="font-size: 11px; color: #7e22ce; font-weight: 700; background: none; border: none; cursor: pointer;">Reset Default</button>
                    <button type="button" class="inv-col-quick-link" onclick="showAllOutBuilderColumns()" style="font-size: 11px; color: #475569; font-weight: 600; background: none; border: none; cursor: pointer;">Show All</button>
                </div>
                <div style="max-height: 270px; overflow-y: auto; padding: 6px 10px; background: #ffffff;">
                    ${OUT_BUILDER_COLUMNS.map(col => {
                        const checked = col.lockVisible || activeOutBuilderCols.includes(col.id);
                        return `
                            <label style="display: flex; align-items: center; justify-content: space-between; padding: 4px 6px; font-size: 11.5px; cursor: ${col.lockVisible ? 'default' : 'pointer'}; border-radius: 6px;">
                                <span style="display: flex; align-items: center; gap: 6px; color: ${col.lockVisible ? '#94a3b8' : '#1e293b'}; font-weight: ${checked ? '600' : '400'};">
                                    <input type="checkbox" ${checked ? 'checked' : ''} ${col.lockVisible ? 'disabled' : ''} onchange="toggleOutBuilderColumn('${col.id}', this.checked)" style="accent-color: #9333ea;">
                                    <span>${escapeHtml(col.label)}</span>
                                </span>
                                <span style="font-size: 9.5px; font-weight: 600; padding: 1px 5px; border-radius: 4px; background: #f1f5f9; color: #64748b;">${escapeHtml(col.category.split(' ')[0])}</span>
                            </label>
                        `;
                    }).join('')}
                </div>
            </div>
        </th>
    `;

    theadRow.innerHTML = html;
    if (wasOpen) {
        requestAnimationFrame(() => positionOutBuilderSettingsDropdown());
    }
};

window.toggleOutBuilderSettingsDropdown = function (event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('outBuilderSettingsDropdown');
    if (!dd) return;
    if (dd.style.display === 'none' || !dd.style.display) {
        dd.style.display = 'block';
        positionOutBuilderSettingsDropdown();
    } else {
        dd.style.display = 'none';
    }
};

window.toggleOutBuilderColumn = function (colId, isVisible) {
    if (isVisible) {
        if (!activeOutBuilderCols.includes(colId)) activeOutBuilderCols.push(colId);
    } else {
        activeOutBuilderCols = activeOutBuilderCols.filter(c => c !== colId);
    }
    saveOutBuilderColsState();
    renderActiveBuilderTable();
    updatePickOverallProgress();
    scheduleOutMockupUpdate();
};

window.resetOutBuilderColumnsDefault = function () {
    activeOutBuilderCols = [...OUT_DEFAULT_BUILDER_COLS];
    saveOutBuilderColsState();
    renderActiveBuilderTable();
    updatePickOverallProgress();
    scheduleOutMockupUpdate();
    showOutToast('Reset sheet columns to default');
};

window.showAllOutBuilderColumns = function () {
    activeOutBuilderCols = OUT_BUILDER_COLUMNS.map(c => c.id);
    saveOutBuilderColsState();
    renderActiveBuilderTable();
    updatePickOverallProgress();
    scheduleOutMockupUpdate();
    showOutToast('Showing all sheet columns');
};

// ============================================================================
// C. HYDRATION & MOVEMENT TYPE / ROUTE ENGINE
// ============================================================================
function getCurrentBuilderItems() {
    return activePickOrder ? (activePickOrder.items || []) : draftItems;
}

function populateOutWarehouseDropdowns() {
    const warehouses = window.StockOutStore.warehouses || [];
    const whFilter = document.getElementById('warehouseFilter');
    if (whFilter) {
        let opts = '<option value="ALL">All Source Warehouses</option>';
        warehouses.forEach(w => {
            opts += `<option value="${escapeHtml(w.id)}">${escapeHtml(w.code)} — ${escapeHtml(w.name)}</option>`;
        });
        whFilter.innerHTML = opts;
    }
}

function getOutboundMovementTypes() {
    const serverTypes = Array.isArray(window.StockOutStore.movementTypes) ? window.StockOutStore.movementTypes : [];
    const mergedMap = new Map();

    // Seed from server-provided defaults
    serverTypes.forEach(mt => {
        if (mt && mt.id) mergedMap.set(mt.id, mt);
    });

    // Overlay any routes configured in Movement Types & Supply Flows (MT_STORAGE_KEY)
    try {
        const raw = localStorage.getItem('rms_inv_movement_types_v2') || localStorage.getItem('rms_movement_supply_flows_v1');
        if (raw) {
            const custom = JSON.parse(raw);
            if (Array.isArray(custom)) {
                custom.forEach(mt => {
                    if (mt && mt.id) mergedMap.set(mt.id, mt);
                });
            }
        }
    } catch (e) {}

    const outbound = Array.from(mergedMap.values()).filter(mt =>
        mt.is_active !== false &&
        mt.operation_kind !== 'Inbound Receipt' &&
        mt.stock_effect !== 'Receipt'
    );
    if (outbound.length > 0) return outbound;

    return [
        {
            id: 'MT-KITCHEN-DIRECT',
            code: 'WH-KITCHEN-OUT',
            name: 'Direct Kitchen Line Issue (1-Step)',
            warehouse_id: 'WH-MAIN',
            operation_kind: 'Outbound Dispatch',
            stock_effect: 'Dispatch',
            route_preset: '1-Step Direct',
            steps: [
                { step_no: 1, name: 'Issue from Storage to Kitchen Line', from_area: 'Stock Zone A (Dry Goods)', to_area: 'Main Kitchen - Hot Line (Saute & Grill)' },
            ],
        },
        {
            id: 'MT-MAIN-DISPATCH',
            code: 'WH-MAIN-OUT',
            name: 'Branch Store Daily Delivery Dispatch',
            warehouse_id: 'WH-MAIN',
            operation_kind: 'Outbound Dispatch',
            stock_effect: 'Dispatch',
            route_preset: '2-Step Staged',
            steps: [
                { step_no: 1, name: 'Pick from Bins to Packing Table', from_area: 'Stock Zone A (Dry Goods)', to_area: 'Outbound Packing & Kitting Table' },
                { step_no: 2, name: 'Dispatch to Branch Logistics', from_area: 'Outbound Packing & Kitting Table', to_area: 'Customer / Branch Destination (Boundary)' },
            ],
        },
        {
            id: 'MT-MAIN-PPS',
            code: 'WH-MAIN-PPS',
            name: 'Full Pick, Pack & Ship Fulfillment (3-Step)',
            warehouse_id: 'WH-MAIN',
            operation_kind: 'Outbound Dispatch',
            stock_effect: 'Dispatch',
            route_preset: '3-Step Pick-Pack-Ship',
            steps: [
                { step_no: 1, name: 'Pick from Storage Bins', from_area: 'Stock Zone A (Dry Goods)', to_area: 'Picking Staging Cage' },
                { step_no: 2, name: 'Pack & Kitting Verification', from_area: 'Picking Staging Cage', to_area: 'Outbound Packing & Kitting Table' },
                { step_no: 3, name: 'Ship & Dispatch at Loading Bay', from_area: 'Outbound Packing & Kitting Table', to_area: 'Customer / Branch Destination (Boundary)' },
            ],
        },
        {
            id: 'MT-MAIN-SCRAP',
            code: 'WH-MAIN-SCRAP',
            name: 'Waste, Spoilage & Scrap Write-Off',
            warehouse_id: 'WH-MAIN',
            operation_kind: 'Stock Adjustment',
            stock_effect: 'Adjustment',
            route_preset: '1-Step Direct',
            steps: [
                { step_no: 1, name: 'Dispose to Scrap Sink', from_area: 'Stock Zone A (Dry Goods)', to_area: 'Scrap / Spoilage & Write-Off (Boundary)' },
            ],
        },
    ];
}

function getActiveOutMovementType() {
    const list = getOutboundMovementTypes();
    const selVal = document.getElementById('outMovementTypeSelect')?.value;
    return list.find(m => m.id === selVal) || list[0] || { id: 'MT-KITCHEN-DIRECT', route_preset: '1-Step Direct', steps: [] };
}

function getOutStepShortLabel(step, idx, totalSteps) {
    const txt = `${step?.name || ''} ${step?.to_area || ''}`.toLowerCase();
    if (/scrap|waste|dispos|write-off/.test(txt)) return 'Write-Off';
    if (/pack|kit/.test(txt)) return 'Pack';
    if (/pick|stag|cage/.test(txt) && !/dispatch|ship/.test(txt)) return 'Pick';
    if (totalSteps === 1) return 'Direct Issue';
    if (/ship|dispatch|deliver|bay|logistics/.test(txt) || idx === totalSteps - 1) return 'Ship & Deduct';
    return step?.name || `Step ${idx + 1}`;
}

function populateOutMovementTypes(preferOrderType = null) {
    const mtSelect = document.getElementById('outMovementTypeSelect');
    if (!mtSelect) return;

    const list = getOutboundMovementTypes();
    mtSelect.innerHTML = list.map(mt =>
        `<option value="${escapeHtml(mt.id)}">${escapeHtml(mt.code)} — ${escapeHtml(mt.name)}</option>`
    ).join('');

    let chosen = list[0];
    if (preferOrderType === 'SPOILAGE_DISPOSAL') {
        chosen = list.find(m => m.id === 'MT-MAIN-SCRAP' || m.stock_effect === 'Adjustment' || /scrap|waste/i.test(m.code)) || chosen;
    } else if (preferOrderType === 'VENDOR_RETURN') {
        chosen = list.find(m => m.id === 'MT-MAIN-PPS' || (m.steps || []).length >= 3) || list.find(m => m.id === 'MT-MAIN-DISPATCH') || chosen;
    } else if (preferOrderType === 'BRANCH_TRANSFER') {
        chosen = list.find(m => m.id === 'MT-MAIN-DISPATCH' || (m.steps || []).length === 2) || chosen;
    } else if (preferOrderType === 'KITCHEN_USAGE' || preferOrderType === 'STAFF_MEALS' || preferOrderType === 'SAMPLE_TASTING') {
        chosen = list.find(m => m.id === 'MT-KITCHEN-DIRECT' || ((m.steps || []).length === 1 && m.stock_effect === 'Dispatch')) || chosen;
    }

    if (chosen) {
        mtSelect.value = chosen.id;
        window.StockOutStore.activeRouteStepIndex = 0;
        syncRouteAndPipelineForMovement(chosen);
    }
}

window.onOutMovementTypeChanged = function (mtId) {
    const list = getOutboundMovementTypes();
    const mt = list.find(m => m.id === mtId) || list[0];
    if (mt) {
        const stepsLen = (mt.steps || []).length || 1;
        if (!activePickOrder) {
            window.StockOutStore.activeRouteStepIndex = 0;
        } else {
            window.StockOutStore.activeRouteStepIndex = Math.min(window.StockOutStore.activeRouteStepIndex || 0, stepsLen - 1);
        }
        syncRouteAndPipelineForMovement(mt);
        syncBuilderModeChrome();
        updatePickOverallProgress();
    }
    onOutHeaderChanged();
};

function syncRouteAndPipelineForMovement(mt) {
    const badge = document.getElementById('outRoutePresetBadge');
    const steps = (mt && mt.steps) || [];
    if (badge && mt) {
        badge.textContent = mt.route_preset || `${steps.length}-Step Route`;
    }
    populateOutRouteLocations(mt);
    const stepIdx = Math.max(0, Math.min(window.StockOutStore.activeRouteStepIndex || 0, Math.max(0, steps.length - 1)));
    const activeStep = steps[stepIdx];
    if (activeStep) {
        if (activeStep.from_area) ensureSelectOption('outSourceLocationSelect', activeStep.from_area);
        if (activeStep.to_area && !/boundary/i.test(activeStep.to_area)) {
            ensureSelectOption('outDestinationLocationSelect', activeStep.to_area);
        } else {
            const destLabel = getSelectedDestinationLabel();
            if (destLabel) ensureSelectOption('outDestinationLocationSelect', destLabel);
        }
    }
    renderOutChevronPipeline(mt);
}

window.selectOutRouteStep = function (stepIdx) {
    const mt = getActiveOutMovementType();
    const steps = mt.steps || [];
    if (steps.length === 0) return;

    const clamped = Math.max(0, Math.min(Number(stepIdx) || 0, steps.length - 1));
    window.StockOutStore.activeRouteStepIndex = clamped;

    const step = steps[clamped];
    if (step) {
        if (step.from_area) ensureSelectOption('outSourceLocationSelect', step.from_area);
        if (step.to_area && !/boundary/i.test(step.to_area)) {
            ensureSelectOption('outDestinationLocationSelect', step.to_area);
        } else {
            const destLabel = getSelectedDestinationLabel();
            if (destLabel) ensureSelectOption('outDestinationLocationSelect', destLabel);
        }
    }

    renderOutChevronPipeline(mt);
    syncBuilderModeChrome();
    updatePickOverallProgress();
    scheduleOutMockupUpdate();
};

function populateOutRouteLocations(movementType) {
    const srcSelect = document.getElementById('outSourceLocationSelect');
    const destSelect = document.getElementById('outDestinationLocationSelect');
    if (!srcSelect || !destSelect) return;

    const areas = window.StockOutStore.storageAreas || [];
    const steps = (movementType && movementType.steps) || [];

    const sourceList = [];
    const destList = [];

    steps.forEach(s => {
        if (s.from_area && !sourceList.includes(s.from_area)) sourceList.push(s.from_area);
        if (s.to_area && !destList.includes(s.to_area)) destList.push(s.to_area);
    });

    areas.forEach(a => {
        if (a.area_role === 'STORAGE' || a.area_role === 'STAGING' || !a.area_role) {
            if (!sourceList.includes(a.name)) sourceList.push(a.name);
        }
        if (!destList.includes(a.name)) destList.push(a.name);
    });

    const activeDest = getSelectedDestinationLabel();
    if (activeDest && !destList.includes(activeDest)) {
        destList.unshift(activeDest);
    }

    if (sourceList.length === 0) sourceList.push('Stock Zone A (Dry Goods)');
    if (destList.length === 0) destList.push('Main Kitchen - Hot Line (Saute & Grill)');

    const prevSrc = srcSelect.value;
    srcSelect.innerHTML = sourceList.map((src, i) =>
        `<option value="${escapeHtml(src)}" ${(prevSrc ? prevSrc === src : i === 0) ? 'selected' : ''}>${escapeHtml(src)}</option>`
    ).join('');

    const preferredDest = activeDest || destList[0];
    destSelect.innerHTML = destList.map(dest =>
        `<option value="${escapeHtml(dest)}" ${dest === preferredDest ? 'selected' : ''}>${escapeHtml(dest)}</option>`
    ).join('');
}

window.onOutSourceLocationChanged = function () {
    renderOutChevronPipeline();
    onOutHeaderChanged();
};

window.onOutDestinationLocationChanged = function () {
    renderOutChevronPipeline();
    onOutHeaderChanged();
};

window.renderOutChevronPipeline = function (movementType = null) {
    const pipeline = document.getElementById('outLifecycleProgressBar');
    if (!pipeline) return;

    const mt = movementType || getActiveOutMovementType();
    const steps = (mt.steps && mt.steps.length > 0) ? mt.steps : [
        { step_no: 1, name: 'Direct Issue', from_area: 'Stock Zone A (Dry Goods)', to_area: getSelectedDestinationLabel() },
    ];

    const items = getCurrentBuilderItems();
    const activeIdx = Math.max(0, Math.min(window.StockOutStore.activeRouteStepIndex || 0, steps.length - 1));
    const isShipped = activePickOrder?.status === 'SHIPPED';

    const stepsHtml = steps.map((step, idx) => {
        const shortLabel = getOutStepShortLabel(step, idx, steps.length);
        const isDone = isShipped || idx < activeIdx;
        const isActive = !isShipped && idx === activeIdx;
        const cls = isDone ? 'is-done' : (isActive ? 'is-active stage-approval' : 'stage-draft');

        const fromTxt = step.from_area || 'Storage Bins';
        const toTxt = /boundary/i.test(step.to_area || '') ? (getSelectedDestinationLabel() || step.to_area) : (step.to_area || 'Next Area');
        const stepTitle = steps.length === 1 ? shortLabel : `Step ${idx + 1}: ${shortLabel}`;

        return `
            <div class="po-chevron-step rfq-chevron-step ${cls}" role="button" tabindex="0"
                 onclick="selectOutRouteStep(${idx})"
                 onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectOutRouteStep(${idx});}"
                 title="${escapeHtml(step.name || shortLabel)} (${escapeHtml(fromTxt)} → ${escapeHtml(toTxt)})">
                <div class="rfq-chevron-badge">
                    <i class="ph ${isDone ? 'ph-check' : (idx === steps.length - 1 ? 'ph-truck' : 'ph-package')}"></i>
                    ${!isDone ? `<span class="rfq-chevron-num">${idx + 1}</span>` : ''}
                </div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">${escapeHtml(stepTitle)}</span>
                    <span class="rfq-chevron-desc">${escapeHtml(toTxt)}</span>
                </div>
            </div>
        `;
    }).join('');

    const statusCls = isShipped ? 'is-done' : 'stage-status';
    const statusTitle = isShipped ? 'Dispatched' : (activeIdx === steps.length - 1 ? 'Ledger Commit' : `Stage ${activeIdx + 1}/${steps.length}`);
    const statusDesc = isShipped
        ? 'SYS_LEDGER Updated'
        : (items.length === 0 ? 'Add Items First' : (activeIdx === steps.length - 1 ? 'Ready to Deduct' : 'In Route Staging'));

    pipeline.innerHTML = `
        ${stepsHtml}
        <div class="po-chevron-step rfq-chevron-step ${statusCls}">
            <div class="rfq-chevron-badge"><i class="ph ${isShipped ? 'ph-check' : 'ph-flag-checkered'}"></i>${!isShipped ? `<span class="rfq-chevron-num">${steps.length + 1}</span>` : ''}</div>
            <div class="rfq-chevron-info">
                <span class="rfq-chevron-title" id="outStepStatusTitle">${escapeHtml(statusTitle)}</span>
                <span class="rfq-chevron-desc" id="outStepStatusDesc">${escapeHtml(statusDesc)}</span>
            </div>
        </div>
    `;

    const activeStep = steps[activeIdx];
    const activeLabel = getOutStepShortLabel(activeStep, activeIdx, steps.length);
    const stageBadge = document.getElementById('outRouteStageBadge');
    if (stageBadge) {
        stageBadge.textContent = isShipped ? 'Completed · SYS_LEDGER' : `Step ${activeIdx + 1}/${steps.length}: ${activeLabel}`;
    }
};

function getSelectedDestinationLabel() {
    const orderType = document.getElementById('draftOrderType')?.value || 'KITCHEN_USAGE';
    if (orderType === 'VENDOR_RETURN') {
        const vSel = document.getElementById('draftVendorSelect');
        const opt = vSel && vSel.selectedIndex >= 0 ? vSel.options[vSel.selectedIndex] : null;
        if (opt && opt.value) {
            return opt.dataset.address || opt.dataset.name || 'Vendor RMA';
        }
        return 'Select Vendor RMA';
    }
    if (orderType === 'BRANCH_TRANSFER') {
        return document.getElementById('draftBranchSelect')?.value || 'Destination Branch';
    }
    if (orderType === 'SPOILAGE_DISPOSAL') {
        return document.getElementById('draftDisposalVault')?.value || 'Disposal Vault';
    }
    return document.getElementById('draftKitchenStation')?.value || 'Main Kitchen Line';
}

// ============================================================================
// D. TAB NAVIGATION & BUILDER MODE SWITCHING (2-Tab Stock In Parity)
// ============================================================================
function switchOutTab(tabId) {
    window.StockOutStore.activeTab = tabId === 'tab-out-list' ? 'tab-out-list' : 'tab-pick-pack-ship';

    const btnList = document.getElementById('tabBtnList');
    const btnBuilder = document.getElementById('tabBtnPickPackShip');
    const panelList = document.getElementById('tab-out-list');
    const panelBuilder = document.getElementById('tab-pick-pack-ship');

    if (btnList) btnList.classList.remove('active');
    if (btnBuilder) btnBuilder.classList.remove('active');
    if (panelList) { panelList.classList.remove('active'); panelList.style.display = 'none'; }
    if (panelBuilder) { panelBuilder.classList.remove('active'); panelBuilder.style.display = 'none'; }

    if (tabId === 'tab-out-list') {
        if (btnList) btnList.classList.add('active');
        if (panelList) {
            panelList.classList.add('active');
            panelList.style.display = 'flex';
        }
        renderOrdersList();
    } else {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (panelBuilder) {
            panelBuilder.classList.add('active');
            panelBuilder.style.display = 'flex';
        }
        syncBuilderModeChrome();
        renderOutChevronPipeline();
        renderActiveBuilderTable();
        updatePickOverallProgress();
        updateOutLiveMockup();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.startNewOutRequisition = function () {
    window.StockOutStore.builderMode = 'draft';
    window.StockOutStore.activeRouteStepIndex = 0;
    resetDraftForm();
    loadOrderIntoPickPack(null, false);
    populateOutMovementTypes(document.getElementById('draftOrderType')?.value || 'KITCHEN_USAGE');
    syncBuilderModeChrome();
    switchOutTab('tab-pick-pack-ship');
    showOutToast('Started New Stock Out Requisition');
};

window.startDirectOutboundMode = function () {
    window.StockOutStore.builderMode = 'direct';
    const orderTypeSel = document.getElementById('draftOrderType');
    if (orderTypeSel) {
        orderTypeSel.value = 'KITCHEN_USAGE';
        handleOrderTypeChange('KITCHEN_USAGE');
    }
    resetDraftForm();
    loadOrderIntoPickPack(null, false);
    window.StockOutStore.activeRouteStepIndex = 0;
    syncBuilderModeChrome();
    switchOutTab('tab-pick-pack-ship');
    setTimeout(() => document.getElementById('outScanInput')?.focus(), 80);
    showOutToast('Started Direct Outbound (1-Step Issue & Deduct)');
};

function syncBuilderModeChrome() {
    const mode = activePickOrder ? 'pick' : window.StockOutStore.builderMode;
    const mt = getActiveOutMovementType();
    const steps = (mt && mt.steps) || [];
    const totalSteps = Math.max(1, steps.length);
    const stepIdx = Math.max(0, Math.min(window.StockOutStore.activeRouteStepIndex || 0, totalSteps - 1));
    const isFinalStep = stepIdx >= totalSteps - 1;

    const btnSaveDraft = document.getElementById('btnSaveDraftOrder');
    const btnSaveProgress = document.getElementById('btnSavePickProgress');
    const btnPrimary = document.getElementById('btnConfirmOutPrimary');
    const btnPrimaryLabel = document.getElementById('btnConfirmOutPrimaryLabel');
    const modeBadge = document.getElementById('outModeBadge');
    const modeText = document.getElementById('outModeText');
    const refDisplay = document.getElementById('draftRefDisplay');

    const curLabel = getOutStepShortLabel(steps[stepIdx], stepIdx, totalSteps);
    const nextLabel = !isFinalStep ? getOutStepShortLabel(steps[stepIdx + 1], stepIdx + 1, totalSteps) : '';

    if (activePickOrder) {
        if (btnSaveDraft) btnSaveDraft.style.display = 'none';
        if (btnSaveProgress) btnSaveProgress.style.display = 'inline-flex';
        if (btnPrimaryLabel) {
            btnPrimaryLabel.textContent = isFinalStep
                ? (totalSteps === 1 ? 'Confirm Issue & Deduct Stock' : 'Confirm Ship & Deduct Stock')
                : `Confirm Step ${stepIdx + 1} (${curLabel}) & Next`;
        }
        if (btnPrimary) btnPrimary.disabled = activePickOrder.status === 'SHIPPED';
        if (modeBadge && modeText) {
            modeBadge.className = 'status-pill awarded grn-mode-pill';
            modeText.textContent = `${activePickOrder.order_number} · Step ${stepIdx + 1}/${totalSteps}: ${curLabel}`;
        }
        if (refDisplay) refDisplay.textContent = activePickOrder.order_number;
    } else if (totalSteps === 1 || mode === 'direct' || isFinalStep) {
        if (btnSaveDraft) btnSaveDraft.style.display = 'inline-flex';
        if (btnSaveProgress) btnSaveProgress.style.display = 'none';
        if (btnPrimaryLabel) btnPrimaryLabel.textContent = 'Confirm Issue & Deduct Stock';
        if (btnPrimary) btnPrimary.disabled = false;
        if (modeBadge && modeText) {
            modeBadge.className = 'status-pill approved grn-mode-pill';
            modeText.textContent = totalSteps === 1 ? 'Direct Outbound (1-Step)' : `${mt.route_preset || 'Route'} · Final Step`;
        }
        if (refDisplay) refDisplay.textContent = `SO-${new Date().getFullYear()}-DIRECT`;
    } else {
        if (btnSaveDraft) btnSaveDraft.style.display = 'inline-flex';
        if (btnSaveProgress) btnSaveProgress.style.display = 'none';
        if (btnPrimaryLabel) btnPrimaryLabel.textContent = `Create & Advance to Step ${stepIdx + 2} (${nextLabel})`;
        if (btnPrimary) btnPrimary.disabled = false;
        if (modeBadge && modeText) {
            modeBadge.className = 'status-pill sent grn-mode-pill';
            modeText.textContent = `${mt.route_preset || `${totalSteps}-Step`} · Step 1: ${curLabel}`;
        }
        if (refDisplay) refDisplay.textContent = `SO-${new Date().getFullYear()}-NEW`;
    }
}

// ============================================================================
// E. TAB 1: LIST OF DISPATCHES RENDERER & KPI COUNTERS
// ============================================================================
function renderOrdersList() {
    renderOutQueueTableHeader();

    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    const searchVal = (document.getElementById('orderSearchInput')?.value || '').toLowerCase().trim();
    const whVal = document.getElementById('warehouseFilter')?.value || 'ALL';
    const statusVal = document.getElementById('statusFilter')?.value || 'ALL';
    const typeVal = document.getElementById('typeFilter')?.value || 'ALL';

    const warehouses = window.StockOutStore.warehouses || [];
    const matchedWh = whVal !== 'ALL' ? warehouses.find(w => w.id === whVal) : null;

    const filtered = rawOrders.filter(ord => {
        if (statusVal !== 'ALL' && ord.status !== statusVal) return false;
        if (typeVal !== 'ALL' && ord.order_type !== typeVal) return false;

        if (matchedWh) {
            const whTokens = [matchedWh.id, matchedWh.code, matchedWh.name].filter(Boolean).map(t => t.toLowerCase());
            const destLower = (ord.destination_location || '').toLowerCase();
            const itemMatch = (ord.items || []).some(it => {
                const locLower = (it.storage_location || '').toLowerCase();
                return whTokens.some(tok => locLower.includes(tok));
            });
            const destMatch = whTokens.some(tok => destLower.includes(tok));
            if (!itemMatch && !destMatch) return false;
        }

        if (searchVal) {
            const num = (ord.order_number || '').toLowerCase();
            const req = (ord.requested_by || '').toLowerCase();
            const dest = (ord.destination_location || '').toLowerCase();
            const vName = (ord.vendor_name || '').toLowerCase();
            const hasSku = (ord.items || []).some(it =>
                (it.sku || '').toLowerCase().includes(searchVal) ||
                (it.item_name || '').toLowerCase().includes(searchVal)
            );
            if (!num.includes(searchVal) && !req.includes(searchVal) && !dest.includes(searchVal) && !vName.includes(searchVal) && !hasSku) {
                return false;
            }
        }
        return true;
    });

    updateKpis();

    const elVis = document.getElementById('outQueueCountVisible');
    const elTot = document.getElementById('outQueueCountTotal');
    if (elVis) elVis.textContent = String(filtered.length);
    if (elTot) elTot.textContent = String(rawOrders.length);

    const visibleCols = outQueueColOrder
        .filter(id => activeOutQueueColIds.includes(id))
        .map(id => OUT_QUEUE_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    const noPrompt = document.getElementById('noOrdersPrompt');
    if (noPrompt) noPrompt.style.display = 'none';

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${Math.max(1, visibleCols.length)}" style="text-align: center; padding: 40px 16px; color: #64748b;">
                    <i class="ph ph-tray" style="font-size: 34px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                    <strong style="color: #334155; font-size: 13px;">No Stock-Out Dispatches Match Your Filter</strong>
                    <p style="font-size: 11.5px; margin: 4px 0 0 0;">Adjust status/purpose filters above or click <strong>"New Requisition"</strong> to create a dispatch.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(ord => {
        const reqQty = parseFloat(ord.total_requested_qty) || 0;
        const packQty = parseFloat(ord.total_packed_qty) || 0;
        const pct = reqQty > 0 ? Math.min(100, Math.round((packQty / reqQty) * 100)) : 0;

        let statusPill = '<span class="status-pill draft"><i class="ph ph-dot"></i> Draft</span>';
        if (ord.status === 'PICKING') statusPill = '<span class="status-pill sent"><i class="ph ph-arrows-clockwise"></i> Picking</span>';
        if (ord.status === 'PACKED') statusPill = '<span class="status-pill awarded"><i class="ph ph-package"></i> Packed</span>';
        if (ord.status === 'SHIPPED') statusPill = '<span class="status-pill approved"><i class="ph ph-check-circle"></i> Shipped</span>';
        if (ord.status === 'CANCELLED') statusPill = '<span class="hr-badge hr-badge-red"><i class="ph ph-x"></i> Cancelled</span>';

        let destIcon = 'ph-map-pin';
        if (ord.order_type === 'VENDOR_RETURN') destIcon = 'ph-buildings';
        if (ord.order_type === 'BRANCH_TRANSFER') destIcon = 'ph-share-network';
        if (ord.order_type === 'SPOILAGE_DISPOSAL') destIcon = 'ph-trash';

        const cellsHtml = visibleCols.map(col => {
            if (col.id === 'order_number') {
                return `
                    <td>
                        <strong style="font-family: monospace; color: #9333ea; cursor: pointer;" onclick="openOrderDetailModal(${ord.id})" title="View Order Slip">
                            ${escapeHtml(ord.order_number)}
                        </strong>
                        <div style="font-size: 11px; color: #94a3b8;">${formatDate(ord.created_at)}</div>
                    </td>
                `;
            }
            if (col.id === 'order_type') {
                return `<td><span class="hr-badge hr-badge-purple">${escapeHtml(formatOrderType(ord.order_type))}</span></td>`;
            }
            if (col.id === 'destination') {
                return `
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #1e293b;">
                            <i class="ph ${destIcon}" style="color: #9333ea;"></i>
                            <span>${escapeHtml(ord.destination_location || 'Main Kitchen')}</span>
                        </div>
                        ${ord.vendor_name ? `<div style="font-size: 11px; color: #64748b;">${escapeHtml(ord.vendor_name)}</div>` : ''}
                    </td>
                `;
            }
            if (col.id === 'requester') {
                return `
                    <td>
                        <strong>${escapeHtml(ord.requested_by || 'Chef')}</strong>
                        <div style="font-size: 11px; color: #64748b;">${escapeHtml(ord.department || 'Kitchen Operations')}</div>
                    </td>
                `;
            }
            if (col.id === 'priority') {
                const prio = ord.priority || 'NORMAL';
                const pCls = prio === 'EMERGENCY' ? 'hr-badge-red' : (prio === 'URGENT' ? 'hr-badge-amber' : 'hr-badge-neutral');
                return `<td><span class="hr-badge ${pCls}">${escapeHtml(prio)}</span></td>`;
            }
            if (col.id === 'lines_qty') {
                return `
                    <td style="text-align: right; font-variant-numeric: tabular-nums;">
                        <div><strong>${ord.total_items_count || (ord.items ? ord.items.length : 0)}</strong> lines</div>
                        <div style="font-size: 11px; color: #64748b;">Req: ${reqQty.toFixed(2)}</div>
                    </td>
                `;
            }
            if (col.id === 'packed_qty') {
                return `<td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #059669;">${packQty.toFixed(2)}</td>`;
            }
            if (col.id === 'progress') {
                return `
                    <td>
                        <div class="line-progress-container">
                            <div class="line-progress-track">
                                <div class="line-progress-fill ${pct >= 100 ? 'fill-matched' : (pct > 0 ? 'fill-partial' : 'fill-gray')}" style="width: ${pct}%;"></div>
                            </div>
                            <div class="line-progress-meta">
                                <span style="color: #64748b;">${packQty.toFixed(1)} / ${reqQty.toFixed(1)}</span>
                                <span style="color: ${pct >= 100 ? '#059669' : '#9333ea'};">${pct}%</span>
                            </div>
                        </div>
                    </td>
                `;
            }
            if (col.id === 'valuation') {
                const v = parseFloat(ord.total_cost_value || 0);
                return `<td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">₱${v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            if (col.id === 'picker_waybill') {
                return `
                    <td style="font-size: 11.5px; color: #475569;">
                        <div>${escapeHtml(ord.picker_name || 'Unassigned')}</div>
                        <div style="font-size: 10.5px; color: #94a3b8; font-family: monospace;">${escapeHtml(ord.tracking_waybill || ord.reference_no || '—')}</div>
                    </td>
                `;
            }
            if (col.id === 'status') {
                return `<td>${statusPill}</td>`;
            }
            if (col.id === 'actions') {
                return `
                    <td style="text-align: right; white-space: nowrap;">
                        <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 6px;">
                            ${ord.status !== 'SHIPPED' && ord.status !== 'CANCELLED' ? `
                                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="jumpToPickPack(${ord.id})" title="Pick, Pack & Confirm Ship">
                                    <i class="ph ph-package"></i> Pick
                                </button>
                            ` : ''}
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openOrderDetailModal(${ord.id})" title="View Order Details & Packing Slip">
                                <i class="ph ph-eye"></i>
                            </button>
                        </div>
                    </td>
                `;
            }
            return `<td>—</td>`;
        }).join('');

        return `<tr>${cellsHtml}</tr>`;
    }).join('');
}

function filterOrdersList() {
    renderOrdersList();
}

window.filterOrdersByKpi = function (statusKey) {
    const sel = document.getElementById('statusFilter');
    if (sel) sel.value = statusKey;
    renderOrdersList();
};

function updateKpis() {
    const shipped = rawOrders.filter(o => o.status === 'SHIPPED');
    const drafts = rawOrders.filter(o => o.status === 'DRAFT');
    const picking = rawOrders.filter(o => o.status === 'PICKING' || o.status === 'PACKED');
    const val = shipped.reduce((acc, o) => acc + (parseFloat(o.total_cost_value) || 0), 0);

    const elShipped = document.getElementById('kpiShippedCount');
    if (elShipped) elShipped.textContent = String(shipped.length);

    const elDraft = document.getElementById('kpiDraftCount');
    if (elDraft) elDraft.textContent = String(drafts.length);

    const elPicking = document.getElementById('kpiPickingCount');
    if (elPicking) elPicking.textContent = String(picking.length);

    const elVal = document.getElementById('kpiValuation');
    if (elVal) elVal.textContent = '₱' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const badgePending = document.getElementById('badgePendingPickCount');
    if (badgePending) {
        const activeCnt = drafts.length + picking.length;
        badgePending.textContent = activeCnt > 0 ? `${activeCnt} Active` : 'New Requisition';
    }

    const badgeList = document.getElementById('badgeListCount');
    if (badgeList) badgeList.textContent = String(rawOrders.length);
}

// ============================================================================
// F. TAB 2 FORM HEADER, ORDER TYPE & PARTNER HANDLERS
// ============================================================================
function handleOrderTypeChange(val) {
    const vBlock = document.getElementById('vendorReturnBlock');
    const bBlock = document.getElementById('branchTransferBlock');
    const kBlock = document.getElementById('kitchenLineBlock');
    const dBlock = document.getElementById('disposalBlock');
    const pBadge = document.getElementById('partnerTypeBadge');

    if (vBlock) vBlock.style.display = 'none';
    if (bBlock) bBlock.style.display = 'none';
    if (kBlock) kBlock.style.display = 'none';
    if (dBlock) dBlock.style.display = 'none';

    if (val === 'VENDOR_RETURN') {
        if (vBlock) vBlock.style.display = 'flex';
        if (pBadge) { pBadge.textContent = 'Vendor Masterlist'; pBadge.className = 'hr-badge hr-badge-purple'; }
    } else if (val === 'BRANCH_TRANSFER') {
        if (bBlock) bBlock.style.display = 'flex';
        if (pBadge) { pBadge.textContent = 'Branch Destination'; pBadge.className = 'hr-badge hr-badge-blue'; }
    } else if (val === 'SPOILAGE_DISPOSAL') {
        if (dBlock) dBlock.style.display = 'flex';
        if (pBadge) { pBadge.textContent = 'Waste & Disposal Vault'; pBadge.className = 'hr-badge hr-badge-amber'; }
    } else {
        if (kBlock) kBlock.style.display = 'flex';
        if (pBadge) { pBadge.textContent = 'Internal Station'; pBadge.className = 'hr-badge hr-badge-neutral'; }
    }

    populateOutMovementTypes(val);
    onOutHeaderChanged();
}

function handleVendorSelect(vendorId) {
    const pill = document.getElementById('vendorPillBox');
    if (!pill) return;
    if (!vendorId) {
        pill.style.display = 'none';
        onOutHeaderChanged();
        return;
    }

    const sel = document.getElementById('draftVendorSelect');
    const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
    if (opt) {
        const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setTxt('vPillName', opt.dataset.name || 'Vendor');
        setTxt('vPillTrade', opt.dataset.trade || 'Commercial Partner');
        setTxt('vPillContact', opt.dataset.contact || 'No Contact');
        setTxt('vPillPhone', opt.dataset.phone || '-');
        setTxt('vPillAddress', opt.dataset.address || 'Standard Warehouse Receiving Dock');
        pill.style.display = 'flex';
    }
    populateOutRouteLocations(getOutboundMovementTypes().find(m => m.id === document.getElementById('outMovementTypeSelect')?.value));
    onOutHeaderChanged();
}

window.onOutHeaderChanged = function () {
    updateOutOptionalSummaryChip();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
};

function updateOutOptionalSummaryChip() {
    const carrier = (document.getElementById('pickCarrierName')?.value || '').trim();
    const waybill = (document.getElementById('pickWaybill')?.value || '').trim();
    const notes = (document.getElementById('draftNotes')?.value || '').trim();
    const chip = document.getElementById('outOptionalSummaryChip');
    if (!chip) return;
    const parts = [carrier, waybill, notes ? 'Notes attached' : ''].filter(Boolean);
    chip.textContent = parts.length > 0 ? parts.join(' · ') : 'Default Logistics';
}

// ============================================================================
// G. UNIFIED ITEMS TABLE (DRAFT REQUISITION + DIRECT + PICK PACK SHIP)
// ============================================================================
function isDirectOutboundRouteActive() {
    const steps = (getActiveOutMovementType().steps) || [];
    return window.StockOutStore.builderMode === 'direct' || steps.length <= 1;
}

window.onOutScanKeydown = function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();

    const input = document.getElementById('outScanInput');
    const query = (input?.value || '').trim();
    if (!query) return;

    const scanBar = input.closest('.grn-scan-bar');
    const qUpper = query.toUpperCase();
    const items = getCurrentBuilderItems();
    const directMode = isDirectOutboundRouteActive();

    // 1. Check active builder lines first
    const foundIdx = items.findIndex(it =>
        (it.sku && it.sku.toUpperCase() === qUpper) ||
        (it.barcode && it.barcode.toUpperCase() === qUpper) ||
        (it.item_name && it.item_name.toUpperCase().includes(qUpper))
    );

    if (foundIdx !== -1) {
        const line = items[foundIdx];
        if (activePickOrder || directMode) {
            line.packed_qty = (parseFloat(line.packed_qty) || 0) + 1;
            if (!activePickOrder) {
                line.requested_qty = Math.max(parseFloat(line.requested_qty) || 0, line.packed_qty);
            }
        } else {
            line.requested_qty = (parseFloat(line.requested_qty) || 0) + 1;
        }
        renderActiveBuilderTable();
        updatePickOverallProgress();
        scheduleOutMockupUpdate();
        flashOutTableRow(foundIdx);
        showOutToast(`Scanned: +1 unit for ${line.sku}`);
        if (input) input.value = '';
        if (!activePickOrder) scheduleOutDraftSave();
        return;
    }

    // 2. If in New/Direct Draft mode, search Item Master catalog and add row
    if (!activePickOrder) {
        const matchedProd = rawProducts.find(p =>
            (p.sku && p.sku.toUpperCase() === qUpper) ||
            (p.item_code && p.item_code.toUpperCase() === qUpper) ||
            (p.barcode && p.barcode.toUpperCase() === qUpper) ||
            (p.name && p.name.toUpperCase().includes(qUpper))
        );

        if (matchedProd) {
            const cost = parseFloat(matchedProd.cost_price) || 0;
            draftItems.push({
                sku: matchedProd.sku || matchedProd.item_code,
                inventory_item_id: matchedProd.id || null,
                item_name: matchedProd.name,
                category: matchedProd.category || 'Raw Ingredients',
                uom: matchedProd.uom || 'Unit',
                unit_cost: cost,
                available_stock: parseFloat(matchedProd.current_stock) || 0,
                storage_location: matchedProd.storage_location || document.getElementById('outSourceLocationSelect')?.value || 'Main Storage',
                requested_qty: 1.0,
                packed_qty: directMode ? 1.0 : 0.0,
                batch_lot_no: '',
                notes: '',
                subtotal: cost,
            });
            renderActiveBuilderTable();
            updatePickOverallProgress();
            renderOutChevronPipeline();
            scheduleOutMockupUpdate();
            flashOutTableRow(draftItems.length - 1);
            showOutToast(`Added ${matchedProd.name} (${matchedProd.sku})`);
            if (input) input.value = '';
            scheduleOutDraftSave();
            return;
        }
    }

    if (scanBar) {
        scanBar.classList.add('is-error');
        setTimeout(() => scanBar.classList.remove('is-error'), 400);
    }
    showOutToast(`SKU / barcode "${query}" not found`, 'warning');
};

function flashOutTableRow(idx) {
    const row = document.querySelector(`#outBuilderTableBody tr[data-row-idx="${idx}"]`);
    if (row) {
        row.classList.remove('grn-row-flash');
        void row.offsetWidth;
        row.classList.add('grn-row-flash');
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function addPickedCatalogItem() {
    if (activePickOrder) {
        Swal.fire({
            icon: 'info',
            title: 'Active Order Loaded',
            text: 'You are picking an existing order. Switch to "New Requisition" in the ORDER selector or scan existing order SKUs.',
            confirmButtonColor: '#9333ea',
        });
        return;
    }

    const picker = document.getElementById('draftCatalogPicker');
    const sku = picker?.value || '';
    if (!sku) {
        Swal.fire({
            icon: 'warning',
            title: 'Please Select Product',
            text: 'Choose an item from the Item Master catalog before clicking Add Item.',
            confirmButtonColor: '#9333ea',
        });
        return;
    }

    const opt = picker.options[picker.selectedIndex];
    const qtyInput = document.getElementById('draftQuickQty');
    const qty = parseFloat(qtyInput?.value || '1') || 1.0;

    if (qty <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Quantity',
            text: 'Requested quantity must be greater than zero.',
            confirmButtonColor: '#9333ea',
        });
        return;
    }

    const isDirect = isDirectOutboundRouteActive();
    const existing = draftItems.find(it => it.sku === sku);
    if (existing) {
        existing.requested_qty = (parseFloat(existing.requested_qty) || 0) + qty;
        if (isDirect) {
            existing.packed_qty = (parseFloat(existing.packed_qty) || 0) + qty;
        }
        existing.subtotal = existing.requested_qty * (parseFloat(existing.unit_cost) || 0);
    } else {
        const cost = parseFloat(opt.dataset.cost) || 0;
        draftItems.push({
            sku: sku,
            inventory_item_id: parseInt(opt.dataset.id, 10) || null,
            item_name: opt.dataset.name || sku,
            category: opt.dataset.category || 'Raw Ingredients',
            uom: opt.dataset.uom || 'Unit',
            unit_cost: cost,
            available_stock: parseFloat(opt.dataset.stock) || 0,
            storage_location: opt.dataset.location || 'Main Storage',
            requested_qty: qty,
            packed_qty: isDirect ? qty : 0.0,
            batch_lot_no: '',
            notes: '',
            subtotal: qty * cost,
        });
    }

    picker.value = '';
    if (qtyInput) qtyInput.value = '1.00';

    renderDraftTable();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    flashOutTableRow(draftItems.findIndex(it => it.sku === sku));
    scheduleOutDraftSave();
}

function renderActiveBuilderTable() {
    renderOutBuilderTableHeader();

    const tbody = document.getElementById('outBuilderTableBody');
    const countBadge = document.getElementById('draftItemCountBadge');
    const pickBadge = document.getElementById('pickItemsCountBadge');
    if (!tbody) return;

    const items = getCurrentBuilderItems();
    const isPickMode = Boolean(activePickOrder);
    const isDirect = !isPickMode && window.StockOutStore.builderMode === 'direct';

    if (countBadge) countBadge.textContent = `${items.length} Line Item${items.length === 1 ? '' : 's'}`;
    if (pickBadge) pickBadge.textContent = `${items.length} Items`;

    const visibleCols = OUT_BUILDER_COLUMNS.filter(c => c.lockVisible || activeOutBuilderCols.includes(c.id));

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleCols.length + 1}" style="text-align: center; padding: 48px 16px; color: var(--grn-on-surface-subtle);">
                    <i class="ph ph-package" style="font-size: 40px; color: var(--grn-border-strong); display: block; margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--grn-on-surface); font-size: 13.5px;">
                        ${isPickMode ? 'No Items in Selected Dispatch Order' : 'No Line Items Added to Stock Out Sheet'}
                    </div>
                    <div style="font-size: 12px; margin-top: 4px;">
                        ${isPickMode
                            ? 'Choose another active dispatch from the <strong>ORDER</strong> selector above.'
                            : 'Scan a barcode above (<kbd>F2</kbd>) or select a product from the <strong>Item Master</strong> picker.'}
                    </div>
                </td>
            </tr>
        `;
        updatePickOverallProgress();
        return;
    }

    tbody.innerHTML = items.map((it, idx) => {
        const reqQty = parseFloat(it.requested_qty) || 0;
        const packedQty = parseFloat(it.packed_qty) || 0;
        const avail = parseFloat(it.available_stock) || 0;
        const unitCost = parseFloat(it.unit_cost) || 0;
        const effectiveQty = isPickMode ? packedQty : (packedQty > 0 ? packedQty : reqQty);
        const lineVal = effectiveQty * unitCost;
        const isDeficit = effectiveQty > avail;

        const pct = reqQty > 0 ? (packedQty / reqQty) * 100 : 0;
        const displayPct = Math.round(pct);
        const barWidth = Math.min(100, Math.max(0, displayPct));

        let progressClass = 'fill-gray';
        let statusBadge = `<span class="hr-badge hr-badge-neutral">${isPickMode ? 'Unpicked' : 'Pending Pack'}</span>`;
        if (packedQty > 0 && packedQty < reqQty) {
            progressClass = 'fill-partial';
            statusBadge = `<span class="hr-badge hr-badge-blue">${displayPct}% Partial</span>`;
        } else if (packedQty > 0 && packedQty === reqQty) {
            progressClass = 'fill-matched';
            statusBadge = '<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Matched</span>';
        } else if (packedQty > reqQty) {
            progressClass = 'fill-exceeds';
            statusBadge = `<span class="hr-badge hr-badge-amber"><i class="ph ph-warning"></i> Exceeds (+${(packedQty - reqQty).toFixed(2)})</span>`;
        }

        const cells = visibleCols.map(col => {
            if (col.id === 'index') {
                return `<td class="td-center" style="font-weight: 700; color: var(--grn-on-surface-subtle);">${idx + 1}</td>`;
            }
            if (col.id === 'item_name') {
                return `
                    <td>
                        <div class="grn-item-cell">
                            <div class="grn-item-title">${escapeHtml(it.item_name || it.sku)}</div>
                            <div class="grn-item-meta">
                                <span class="grn-sku-badge">${escapeHtml(it.sku)}</span>
                                <span><i class="ph ph-map-pin"></i> ${escapeHtml(it.storage_location || 'Main Storage')}</span>
                                ${it.batch_lot_no ? `<span class="grn-mono" style="color: #7e22ce;"><i class="ph ph-barcode"></i> ${escapeHtml(it.batch_lot_no)}</span>` : ''}
                            </div>
                        </div>
                    </td>
                `;
            }
            if (col.id === 'uom') {
                return `<td class="td-center"><span class="grn-uom-pill">${escapeHtml(it.uom || 'PCS')}</span></td>`;
            }
            if (col.id === 'on_hand') {
                return `
                    <td class="td-num" style="font-variant-numeric: tabular-nums;">
                        <span style="font-weight: 700; color: ${isDeficit ? '#dc2626' : '#059669'};">${avail.toFixed(2)}</span>
                        ${isDeficit ? `<div style="font-size: 10px; color: #dc2626; font-weight: 700;">Deficit: ${(avail - effectiveQty).toFixed(2)}</div>` : ''}
                    </td>
                `;
            }
            if (col.id === 'requested_qty') {
                if (isPickMode) {
                    return `<td class="td-center" style="font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums;">${reqQty.toFixed(2)}</td>`;
                }
                return `
                    <td class="td-center">
                        <input type="number" class="grn-control" style="width: 82px; height: 30px; text-align: right; font-weight: 800; padding: 0 8px;"
                               value="${reqQty}" min="0.01" step="any"
                               oninput="onBuilderQtyInput(${idx}, 'requested_qty', this.value)"
                               aria-label="Requested quantity">
                    </td>
                `;
            }
            if (col.id === 'issue_now') {
                const disabledAttr = (activePickOrder && activePickOrder.status === 'SHIPPED') ? 'disabled' : '';
                return `
                    <td class="td-center">
                        <div class="grn-stepper-wrap ${packedQty > reqQty ? 'is-over' : ''}">
                            <button type="button" class="grn-stepper-btn" onclick="stepPackQty(${idx}, -1)" title="Decrement 1" ${disabledAttr}>−</button>
                            <input type="number" class="grn-stepper-input" id="packInput_${idx}"
                                   value="${packedQty}" min="0" step="any"
                                   oninput="handlePackQtyInput(${idx}, this.value)"
                                   aria-label="Pack or issue quantity" ${disabledAttr}>
                            <button type="button" class="grn-stepper-btn" onclick="stepPackQty(${idx}, 1)" title="Increment 1" ${disabledAttr}>+</button>
                            <button type="button" class="grn-stepper-btn is-max" onclick="matchSingleItem(${idx})" title="Match Requested Qty" ${disabledAttr}>100%</button>
                        </div>
                    </td>
                `;
            }
            if (col.id === 'progress') {
                return `
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
                `;
            }
            if (col.id === 'unit_cost') {
                return `<td class="td-num" style="font-variant-numeric: tabular-nums;">₱${unitCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            if (col.id === 'line_total') {
                return `<td class="td-num" id="lineVal_${idx}" style="font-variant-numeric: tabular-nums; font-weight: 800; color: #0f172a;">₱${lineVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            }
            return `<td>—</td>`;
        }).join('');

        return `
            <tr id="pickRow_${idx}" data-row-idx="${idx}" class="${isDeficit ? 'is-deficit' : ''}">
                ${cells}
                <td class="td-center" style="white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                        <button type="button" class="grn-lot-btn ${it.batch_lot_no ? 'has-lot' : ''}" onclick="openOutLotModal(${idx})" title="Specify Picked Batch / Lot #">
                            <span class="grn-state-dot" aria-hidden="true"></span>
                            <i class="ph ${it.batch_lot_no ? 'ph-barcode' : 'ph-tag'}" aria-hidden="true"></i>
                            <span>${it.batch_lot_no ? 'Lot Set' : 'Lot #'}</span>
                        </button>
                        ${!isPickMode ? `
                            <button type="button" class="grn-row-remove" onclick="removeDraftItem(${idx})" title="Remove item" aria-label="Remove item">
                                <i class="ph ph-trash" aria-hidden="true"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    updatePickOverallProgress();
}

function renderDraftTable() {
    renderActiveBuilderTable();
}

function renderPickPackTable() {
    renderActiveBuilderTable();
}

window.onBuilderQtyInput = function (idx, field, val) {
    const items = getCurrentBuilderItems();
    const it = items[idx];
    if (!it) return;

    const num = Math.max(0, parseFloat(val) || 0);
    if (field === 'requested_qty') {
        it.requested_qty = Math.max(0.01, num || 0.01);
        if (!activePickOrder && isDirectOutboundRouteActive()) {
            it.packed_qty = it.requested_qty;
            const pIn = document.getElementById(`packInput_${idx}`);
            if (pIn) pIn.value = String(it.packed_qty);
        }
        it.subtotal = it.requested_qty * (parseFloat(it.unit_cost) || 0);
    } else {
        it.packed_qty = num;
    }

    updateSingleRowUI(idx);
    updatePickOverallProgress();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
};

function updateDraftItemQty(idx, val) {
    const q = parseFloat(val) || 0;
    if (q <= 0) {
        removeDraftItem(idx);
        return;
    }
    if (draftItems[idx]) {
        draftItems[idx].requested_qty = q;
        draftItems[idx].subtotal = q * (parseFloat(draftItems[idx].unit_cost) || 0);
    }
    renderDraftTable();
    scheduleOutMockupUpdate();
    scheduleOutDraftSave();
}

function removeDraftItem(idx) {
    draftItems.splice(idx, 1);
    renderDraftTable();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    scheduleOutDraftSave();
}

function resetDraftForm() {
    draftItems.length = 0;
    window.StockOutStore.draftItems = draftItems;

    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    setVal('draftNotes', '');
    setVal('draftReferenceNo', '');
    setVal('draftVendorSelect', '');
    setVal('pickCarrierName', '');
    setVal('pickWaybill', '');
    const pill = document.getElementById('vendorPillBox');
    if (pill) pill.style.display = 'none';

    updateOutOptionalSummaryChip();
    renderDraftTable();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
}

function handlePackQtyInput(idx, val) {
    const items = getCurrentBuilderItems();
    if (!items[idx]) return;
    items[idx].packed_qty = Math.max(0, parseFloat(val) || 0);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
}

function stepPackQty(idx, delta) {
    const items = getCurrentBuilderItems();
    if (!items[idx]) return;
    let curr = parseFloat(items[idx].packed_qty) || 0;
    curr = Math.max(0, curr + delta);
    items[idx].packed_qty = curr;
    const input = document.getElementById(`packInput_${idx}`);
    if (input) input.value = curr.toFixed(2);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
}

function matchSingleItem(idx) {
    const items = getCurrentBuilderItems();
    if (!items[idx]) return;
    const req = parseFloat(items[idx].requested_qty) || 0;
    items[idx].packed_qty = req;
    const input = document.getElementById(`packInput_${idx}`);
    if (input) input.value = req.toFixed(2);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
}

function updateSingleRowUI(idx) {
    const items = getCurrentBuilderItems();
    const it = items[idx];
    if (!it) return;

    const reqQty = parseFloat(it.requested_qty) || 0;
    const packedQty = parseFloat(it.packed_qty) || 0;
    const unitCost = parseFloat(it.unit_cost) || 0;
    const isPickMode = Boolean(activePickOrder);
    const effectiveQty = isPickMode ? packedQty : (packedQty > 0 ? packedQty : reqQty);
    const lineVal = effectiveQty * unitCost;

    const pct = reqQty > 0 ? (packedQty / reqQty) * 100 : 0;
    const displayPct = Math.round(pct);
    const barWidth = Math.min(100, Math.max(0, displayPct));

    let progressClass = 'fill-gray';
    let statusBadge = `<span class="hr-badge hr-badge-neutral">${isPickMode ? 'Unpicked' : 'Pending Pack'}</span>`;
    if (packedQty > 0 && packedQty < reqQty) {
        progressClass = 'fill-partial';
        statusBadge = `<span class="hr-badge hr-badge-blue">${displayPct}% Partial</span>`;
    } else if (packedQty > 0 && packedQty === reqQty) {
        progressClass = 'fill-matched';
        statusBadge = '<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Matched</span>';
    } else if (packedQty > reqQty) {
        progressClass = 'fill-exceeds';
        statusBadge = `<span class="hr-badge hr-badge-amber"><i class="ph ph-warning"></i> Exceeds (+${(packedQty - reqQty).toFixed(2)})</span>`;
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

    const row = document.getElementById(`pickRow_${idx}`);
    if (row) {
        const isDeficit = effectiveQty > (parseFloat(it.available_stock) || 0);
        row.classList.toggle('is-deficit', isDeficit);
        const wrap = row.querySelector('.grn-stepper-wrap');
        if (wrap) wrap.classList.toggle('is-over', packedQty > reqQty);
    }
}

function updatePickOverallProgress() {
    const items = getCurrentBuilderItems();
    const isPickMode = Boolean(activePickOrder);

    let totalReq = 0;
    let totalPacked = 0;
    let totalVal = 0;
    let hasExceeded = false;
    let overUnits = 0;

    items.forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        totalReq += r;
        totalPacked += p;
        const eff = isPickMode ? p : (p > 0 ? p : r);
        totalVal += (eff * cost);
        if (p > r) {
            hasExceeded = true;
            overUnits += (p - r);
        }
    });

    const pct = totalReq > 0 ? Math.min(100, Math.round((totalPacked / totalReq) * 100)) : 0;
    const remaining = Math.max(0, totalReq - totalPacked);

    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setTxt('summaryOutLines', String(items.length));
    setTxt('draftTotalUnitsDisplay', totalReq.toFixed(2));
    setTxt('pickTotalReqDisplay', totalReq.toFixed(2));
    setTxt('pickTotalPackedDisplay', totalPacked.toFixed(2));
    setTxt('sumOutBalanceUnits', remaining.toFixed(2));
    const moneyStr = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    setTxt('pickTotalValueDisplay', moneyStr);
    setTxt('draftTotalValueDisplay', moneyStr);
    setTxt('pickGaugeBadge', pct + '% Packed' + (hasExceeded ? ' (Exceeds)' : ''));

    const overWrap = document.getElementById('outOverIssueMetric');
    const overVal = document.getElementById('sumOutOverUnits');
    if (overWrap && overVal) {
        if (overUnits > 0) {
            overWrap.hidden = false;
            overVal.textContent = `+${overUnits.toFixed(2)}`;
        } else {
            overWrap.hidden = true;
        }
    }

    const bar = document.getElementById('pickOverallProgressBar');
    if (bar) {
        bar.style.width = pct + '%';
    }

    const statusChip = document.getElementById('outStatusChip');
    const statusChipText = document.getElementById('outStatusChipText');
    if (statusChip && statusChipText) {
        if (activePickOrder?.status === 'SHIPPED') {
            statusChip.setAttribute('data-state', 'ready');
            statusChipText.textContent = 'Shipped · Ledger Posted';
        } else if (pct >= 100 && items.length > 0 && !hasExceeded) {
            statusChip.setAttribute('data-state', 'ready');
            statusChipText.textContent = isPickMode ? '100% Packed · Ready to Ship' : 'Staged · Ready';
        } else if (hasExceeded) {
            statusChip.setAttribute('data-state', 'partial');
            statusChipText.textContent = `Exceeds (+${overUnits.toFixed(1)})`;
        } else if (totalPacked > 0) {
            statusChip.setAttribute('data-state', 'partial');
            statusChipText.textContent = `Picking (${pct}%)`;
        } else {
            statusChip.setAttribute('data-state', 'draft');
            statusChipText.textContent = isPickMode ? `${activePickOrder.status} · Unpicked` : 'Draft · Not Dispatched';
        }
    }

    const alertBox = document.getElementById('pickFulfillmentAlert');
    if (alertBox) {
        if (pct >= 100 && items.length > 0 && !hasExceeded) {
            alertBox.textContent = 'All items 100% matched — ready to dispatch & deduct stock.';
            alertBox.style.color = '#059669';
        } else if (hasExceeded) {
            alertBox.textContent = 'Packed units exceed requested quantity — verify authorization.';
            alertBox.style.color = '#d97706';
        } else if (items.length === 0) {
            alertBox.textContent = 'Scan barcodes or pick products from Item Master above.';
            alertBox.style.color = '#64748b';
        } else {
            alertBox.textContent = `Staged ${totalPacked.toFixed(1)} of ${totalReq.toFixed(1)} requested units.`;
            alertBox.style.color = '#7e22ce';
        }
    }
}

function autoMatchAllPackQuantities() {
    const items = getCurrentBuilderItems();
    if (!items || items.length === 0) return;
    items.forEach((it, idx) => {
        it.packed_qty = parseFloat(it.requested_qty) || 0;
        const input = document.getElementById(`packInput_${idx}`);
        if (input) input.value = it.packed_qty.toFixed(2);
        updateSingleRowUI(idx);
    });
    updatePickOverallProgress();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
    showOutToast('Matched all lines to 100% of requested quantity');
}

function clearAllPackQuantities() {
    const items = getCurrentBuilderItems();
    if (!items || items.length === 0) return;
    items.forEach((it, idx) => {
        it.packed_qty = 0;
        const input = document.getElementById(`packInput_${idx}`);
        if (input) input.value = '0.00';
        updateSingleRowUI(idx);
    });
    updatePickOverallProgress();
    renderOutChevronPipeline();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
}

// ============================================================================
// H. ORDER SELECTION, SAVE DRAFT, PICK/PACK, DIRECT DISPATCH & CONFIRM SHIP
// ============================================================================
function updatePickOrderDropdown() {
    const sel = document.getElementById('pickOrderSelect');
    if (!sel) return;
    const activeOrders = rawOrders.filter(o => ['DRAFT', 'PICKING', 'PACKED'].includes(o.status));
    let optHtml = '<option value="">-- New Requisition / Direct Outbound --</option>';
    activeOrders.forEach(ord => {
        optHtml += `<option value="${ord.id}">${escapeHtml(ord.order_number)} [${escapeHtml(ord.status)}] - ${escapeHtml(ord.destination_location)} (${ord.total_items_count} items)</option>`;
    });
    sel.innerHTML = optHtml;
}

function jumpToPickPack(orderId, targetStepIdx = null) {
    updatePickOrderDropdown();
    const sel = document.getElementById('pickOrderSelect');
    if (sel) sel.value = String(orderId);
    loadOrderIntoPickPack(orderId, false, targetStepIdx);
    switchOutTab('tab-pick-pack-ship');
}

function loadOrderIntoPickPack(orderId, reRender = true, overrideStepIdx = null) {
    if (!orderId) {
        activePickOrder = null;
        window.StockOutStore.activePickOrder = null;
        window.StockOutStore.activeRouteStepIndex = 0;
        const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setTxt('pickActiveOrderNum', 'NO ORDER SELECTED');
        setTxt('pickActiveStatusBadge', 'New Draft');
        setTxt('pickActiveDestDisplay', '-');
        setTxt('pickActiveReqDisplay', '-');
        setTxt('pickActiveTypeDisplay', '-');

        syncBuilderModeChrome();
        if (reRender) {
            renderActiveBuilderTable();
            updatePickOverallProgress();
            renderOutChevronPipeline();
            updateOutLiveMockup();
        }
        return;
    }

    const order = rawOrders.find(o => o.id == orderId);
    if (!order) return;

    activePickOrder = JSON.parse(JSON.stringify(order));
    window.StockOutStore.activePickOrder = activePickOrder;
    window.StockOutStore.builderMode = 'pick';

    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    const setVal = (id, v) => { const el = document.getElementById(id); if (el && v !== undefined && v !== null) el.value = v; };

    setTxt('pickActiveOrderNum', activePickOrder.order_number);
    setTxt('pickActiveStatusBadge', activePickOrder.status);
    setTxt('pickActiveDestDisplay', activePickOrder.destination_location || '-');
    setTxt('pickActiveReqDisplay', (activePickOrder.requested_by || '-') + ' (' + (activePickOrder.department || 'Operations') + ')');
    setTxt('pickActiveTypeDisplay', formatOrderType(activePickOrder.order_type));

    const orderTypeSel = document.getElementById('draftOrderType');
    if (orderTypeSel && activePickOrder.order_type) {
        orderTypeSel.value = activePickOrder.order_type;
        handleOrderTypeChange(activePickOrder.order_type);
    }

    if (activePickOrder.order_type === 'VENDOR_RETURN' && activePickOrder.vendor_id) {
        setVal('draftVendorSelect', String(activePickOrder.vendor_id));
        handleVendorSelect(activePickOrder.vendor_id);
    } else if (activePickOrder.order_type === 'BRANCH_TRANSFER') {
        ensureSelectOption('draftBranchSelect', activePickOrder.destination_location);
    } else if (activePickOrder.order_type === 'SPOILAGE_DISPOSAL') {
        ensureSelectOption('draftDisposalVault', activePickOrder.destination_location);
    } else {
        ensureSelectOption('draftKitchenStation', activePickOrder.destination_location);
    }

    setVal('draftRequestedBy', activePickOrder.requested_by || '');
    setVal('draftPriority', activePickOrder.priority || 'NORMAL');
    setVal('draftReferenceNo', activePickOrder.reference_no || '');
    setVal('draftNotes', activePickOrder.notes || '');
    if (activePickOrder.picker_name) setVal('pickPickerName', activePickOrder.picker_name);
    if (activePickOrder.carrier_name) setVal('pickCarrierName', activePickOrder.carrier_name);
    if (activePickOrder.tracking_waybill) setVal('pickWaybill', activePickOrder.tracking_waybill);

    const mt = getActiveOutMovementType();
    const stepsLen = (mt.steps || []).length || 1;
    let initialStep = 0;
    if (overrideStepIdx !== null && overrideStepIdx !== undefined) {
        initialStep = Math.max(0, Math.min(overrideStepIdx, stepsLen - 1));
    } else if (activePickOrder.status === 'SHIPPED' || activePickOrder.status === 'PACKED') {
        initialStep = stepsLen - 1;
    } else if (activePickOrder.status === 'PICKING') {
        initialStep = Math.min(stepsLen - 1, 1);
    } else {
        initialStep = 0;
    }
    window.StockOutStore.activeRouteStepIndex = initialStep;
    syncRouteAndPipelineForMovement(mt);

    syncBuilderModeChrome();
    updateOutOptionalSummaryChip();
    renderPickPackTable();
    updateOutLiveMockup();
}

function ensureSelectOption(selectId, value) {
    const sel = document.getElementById(selectId);
    if (!sel || !value) return;
    const exists = Array.from(sel.options).some(o => o.value === value);
    if (!exists) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        sel.appendChild(opt);
    }
    sel.value = value;
}

window.advanceOutRouteStep = async function () {
    const mt = getActiveOutMovementType();
    const steps = mt.steps || [];
    const curIdx = window.StockOutStore.activeRouteStepIndex || 0;
    const nextIdx = Math.min(curIdx + 1, Math.max(0, steps.length - 1));

    if (activePickOrder) {
        await savePickPackProgress(true);
    }
    selectOutRouteStep(nextIdx);
    const nextLabel = getOutStepShortLabel(steps[nextIdx], nextIdx, steps.length);
    showOutToast(`Moved to Step ${nextIdx + 1}/${steps.length}: ${nextLabel}`);
};

window.handlePrimaryOutboundAction = function () {
    const mt = getActiveOutMovementType();
    const steps = (mt && mt.steps) || [];
    const totalSteps = Math.max(1, steps.length);
    const stepIdx = Math.max(0, Math.min(window.StockOutStore.activeRouteStepIndex || 0, totalSteps - 1));
    const isFinalStep = stepIdx >= totalSteps - 1;

    if (activePickOrder) {
        if (!isFinalStep) {
            advanceOutRouteStep();
        } else {
            confirmShipOrder();
        }
    } else if (totalSteps === 1 || window.StockOutStore.builderMode === 'direct' || isFinalStep) {
        executeDirectOutboundDispatch();
    } else {
        saveDraftOrder(true);
    }
};

function buildDraftRequestPayload(includePackedQty = false) {
    const orderType = document.getElementById('draftOrderType')?.value || 'KITCHEN_USAGE';
    let destLocation = 'Main Kitchen Line';
    let vendorId = null;
    let vendorName = null;

    if (orderType === 'VENDOR_RETURN') {
        const vSel = document.getElementById('draftVendorSelect');
        vendorId = vSel?.value ? parseInt(vSel.value, 10) : null;
        if (!vendorId) {
            Swal.fire({ icon: 'warning', title: 'Vendor Required', text: 'Please select a registered vendor for vendor returns.', confirmButtonColor: '#9333ea' });
            return null;
        }
        const opt = vSel.options[vSel.selectedIndex];
        vendorName = opt.dataset.name || 'Vendor';
        destLocation = opt.dataset.address || ('Vendor RMA: ' + vendorName);
    } else if (orderType === 'BRANCH_TRANSFER') {
        destLocation = document.getElementById('draftBranchSelect')?.value || 'Branch Transfer';
    } else if (orderType === 'SPOILAGE_DISPOSAL') {
        destLocation = document.getElementById('draftDisposalVault')?.value || 'Disposal Vault';
    } else {
        destLocation = document.getElementById('draftKitchenStation')?.value || 'Main Kitchen Line';
    }

    return {
        order_type: orderType,
        destination_type: orderType === 'VENDOR_RETURN' ? 'VENDOR' : (orderType === 'BRANCH_TRANSFER' ? 'BRANCH' : 'INTERNAL_KITCHEN'),
        destination_location: destLocation,
        vendor_id: vendorId,
        vendor_name: vendorName,
        requested_by: document.getElementById('draftRequestedBy')?.value || window.StockOutConfig?.user?.name || 'Executive Chef',
        department: 'Kitchen Operations',
        priority: document.getElementById('draftPriority')?.value || 'NORMAL',
        reference_no: document.getElementById('draftReferenceNo')?.value || null,
        required_at: document.getElementById('draftRequiredAt')?.value || null,
        notes: document.getElementById('draftNotes')?.value || null,
        items: draftItems.map(it => ({
            sku: it.sku,
            inventory_item_id: it.inventory_item_id,
            item_name: it.item_name,
            category: it.category,
            uom: it.uom,
            requested_qty: parseFloat(it.requested_qty) || 0,
            packed_qty: includePackedQty ? (parseFloat(it.packed_qty) || parseFloat(it.requested_qty) || 0) : (parseFloat(it.packed_qty) || 0),
            batch_lot_no: it.batch_lot_no || null,
            unit_cost: parseFloat(it.unit_cost) || 0,
            storage_location: document.getElementById('outSourceLocationSelect')?.value || it.storage_location || 'Main Storage',
        })),
    };
}

async function saveDraftOrder(moveToPick) {
    if (draftItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Requisition',
            text: 'Please add at least one line item from Item Master before saving.',
            confirmButtonColor: '#9333ea',
        });
        return;
    }

    const payload = buildDraftRequestPayload(false);
    if (!payload) return;

    try {
        Swal.fire({
            title: 'Creating Requisition...',
            text: 'Saving draft and syncing with Item Master',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const csrfToken = window.StockOutConfig?.data?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(window.StockOutConfig?.routes?.createStockOutDraft || '/inventory/api/create-stock-out-draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to create requisition draft.');
        }

        const newOrder = result.data.order;
        rawOrders.unshift(newOrder);
        window.StockOutStore.orders = rawOrders;
        discardOutDraft(true);
        updatePickOrderDropdown();
        resetDraftForm();
        renderOrdersList();

        if (moveToPick) {
            Swal.fire({
                icon: 'success',
                title: 'Requisition Created!',
                text: `${newOrder.order_number} staged and ready for next route step.`,
                confirmButtonColor: '#9333ea',
                timer: 1300,
            });
            jumpToPickPack(newOrder.id, 1);
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Draft Saved!',
                text: `Requisition ${newOrder.order_number} saved as Draft.`,
                confirmButtonColor: '#9333ea',
            });
            switchOutTab('tab-out-list');
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error Saving Requisition',
            text: err.message,
            confirmButtonColor: '#9333ea',
        });
    }
}

async function executeDirectOutboundDispatch() {
    if (draftItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Sheet',
            text: 'Scan or add at least one product before confirming direct outbound dispatch.',
            confirmButtonColor: '#9333ea',
        });
        return;
    }

    const totalIssue = draftItems.reduce((a, it) => a + (parseFloat(it.packed_qty) || parseFloat(it.requested_qty) || 0), 0);
    if (totalIssue <= 0) {
        Swal.fire({ icon: 'error', title: 'Zero Quantity', text: 'Enter issue quantity greater than zero.', confirmButtonColor: '#9333ea' });
        return;
    }

    const payload = buildDraftRequestPayload(true);
    if (!payload) return;

    const confirmRes = await Swal.fire({
        title: 'Confirm Direct Stock Out & Ledger Commit?',
        html: `
            <div style="font-size: 13px; text-align: left; background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 8px;">
                <div><strong>Purpose:</strong> ${escapeHtml(formatOrderType(payload.order_type))}</div>
                <div><strong>Destination:</strong> ${escapeHtml(payload.destination_location)}</div>
                <div><strong>Total Units Issuing:</strong> ${totalIssue.toFixed(2)}</div>
                <div style="margin-top: 8px; color: #dc2626; font-size: 12px;">
                    <i class="ph ph-warning"></i> Creates dispatch order and atomically deducts stock in <strong>SYS_LEDGER</strong>.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Issue & Deduct Stock',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
    });
    if (!confirmRes.isConfirmed) return;

    const lockOverlay = document.getElementById('outGlobalLockOverlay');
    const btnPrimary = document.getElementById('btnConfirmOutPrimary');
    if (lockOverlay) lockOverlay.style.display = 'flex';
    if (btnPrimary) btnPrimary.disabled = true;

    try {
        const csrfToken = window.StockOutConfig?.data?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const createRes = await fetch(window.StockOutConfig?.routes?.createStockOutDraft || '/inventory/api/create-stock-out-draft', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const createJson = await createRes.json();
        if (!createRes.ok || !createJson.success) {
            throw new Error(createJson.message || 'Failed creating outbound order.');
        }

        const newOrder = createJson.data.order;
        const idemKey = `so-direct-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

        const shipRes = await fetch(window.StockOutConfig?.routes?.confirmShipStockOut || '/inventory/api/confirm-ship-stock-out', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                order_id: newOrder.id,
                idempotency_key: idemKey,
                picker_name: document.getElementById('pickPickerName')?.value || window.StockOutConfig?.user?.name || 'Warehouse Lead',
                carrier_name: document.getElementById('pickCarrierName')?.value || null,
                tracking_waybill: document.getElementById('pickWaybill')?.value || null,
                items: payload.items.map(it => ({
                    sku: it.sku,
                    packed_qty: it.packed_qty,
                    batch_lot_no: it.batch_lot_no,
                })),
            }),
        });
        const shipJson = await shipRes.json();
        if (!shipRes.ok || !shipJson.success) {
            throw new Error(shipJson.message || 'Failed dispatching created order.');
        }

        if (lockOverlay) lockOverlay.style.display = 'none';
        if (btnPrimary) btnPrimary.disabled = false;

        discardOutDraft(true);
        resetDraftForm();
        await fetchLiveStockOutData();

        const shippedOrder = shipJson.data.order;
        Swal.fire({
            icon: 'success',
            title: 'Direct Outbound Dispatched!',
            html: `<p>Order <strong>${escapeHtml(shippedOrder.order_number)}</strong> posted to <strong>SYS_LEDGER</strong> and stock deducted.</p>`,
            confirmButtonColor: '#9333ea',
        });
        switchOutTab('tab-out-list');
    } catch (err) {
        if (lockOverlay) lockOverlay.style.display = 'none';
        if (btnPrimary) btnPrimary.disabled = false;
        Swal.fire({ icon: 'error', title: 'Direct Outbound Error', text: err.message, confirmButtonColor: '#dc2626' });
    }
}

async function savePickPackProgress(silent = false) {
    if (!activePickOrder) {
        Swal.fire({ icon: 'warning', title: 'No Order Selected', text: 'Select an order first before saving.', confirmButtonColor: '#9333ea' });
        return;
    }

    const payload = {
        order_id: activePickOrder.id,
        picker_name: document.getElementById('pickPickerName')?.value || null,
        carrier_name: document.getElementById('pickCarrierName')?.value || null,
        tracking_waybill: document.getElementById('pickWaybill')?.value || null,
        items: activePickOrder.items.map(it => ({
            sku: it.sku,
            packed_qty: parseFloat(it.packed_qty) || 0,
            batch_lot_no: it.batch_lot_no || null,
        })),
    };

    try {
        const csrfToken = window.StockOutConfig?.data?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(window.StockOutConfig?.routes?.updateStockOutPack || '/inventory/api/update-stock-out-pack', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to save progress.');

        const idx = rawOrders.findIndex(o => o.id == activePickOrder.id);
        if (idx !== -1) rawOrders[idx] = data.data.order;
        activePickOrder = JSON.parse(JSON.stringify(data.data.order));
        window.StockOutStore.activePickOrder = activePickOrder;

        renderOrdersList();
        updatePickOrderDropdown();
        const sel = document.getElementById('pickOrderSelect');
        if (sel) sel.value = String(activePickOrder.id);
        syncBuilderModeChrome();
        updatePickOverallProgress();
        renderOutChevronPipeline();

        if (!silent) showOutToast(`Saved Pick & Pack progress for ${activePickOrder.order_number}`);
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error Saving Progress', text: err.message, confirmButtonColor: '#9333ea' });
    }
}

async function confirmShipOrder() {
    if (!activePickOrder) {
        Swal.fire({ icon: 'warning', title: 'No Order Selected', text: 'Select an active order first before dispatching.', confirmButtonColor: '#9333ea' });
        return;
    }

    const totalPacked = (activePickOrder.items || []).reduce((acc, it) => acc + (parseFloat(it.packed_qty) || 0), 0);
    if (totalPacked <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Zero Packed Items',
            text: 'Cannot ship an order with 0 packed items. Please enter packed quantities.',
            confirmButtonColor: '#9333ea',
        });
        return;
    }

    const confirmRes = await Swal.fire({
        title: 'Confirm Dispatch & Deduct Stock?',
        html: `
            <div style="font-size: 13px; text-align: left; background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 8px;">
                <div><strong>Order #:</strong> ${escapeHtml(activePickOrder.order_number)}</div>
                <div><strong>Destination:</strong> ${escapeHtml(activePickOrder.destination_location)}</div>
                <div><strong>Total Packed Units:</strong> ${totalPacked.toFixed(2)}</div>
                <div style="margin-top: 8px; color: #dc2626; font-size: 12px;">
                    <i class="ph ph-warning"></i> Atomically deducts on-hand stock from Item Master and appends an immutable <strong>STOCK_OUT</strong> entry to <strong>SYS_LEDGER</strong>.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Dispatch & Deduct Stock',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
    });

    if (!confirmRes.isConfirmed) return;

    const lockOverlay = document.getElementById('outGlobalLockOverlay');
    const btnPrimary = document.getElementById('btnConfirmOutPrimary');
    if (lockOverlay) lockOverlay.style.display = 'flex';
    if (btnPrimary) btnPrimary.disabled = true;

    const payload = {
        order_id: activePickOrder.id,
        idempotency_key: `so-ship-${activePickOrder.id}-${Date.now()}`,
        picker_name: document.getElementById('pickPickerName')?.value || 'Warehouse Lead',
        carrier_name: document.getElementById('pickCarrierName')?.value || null,
        tracking_waybill: document.getElementById('pickWaybill')?.value || null,
        items: activePickOrder.items.map(it => ({
            sku: it.sku,
            packed_qty: parseFloat(it.packed_qty) || 0,
            batch_lot_no: it.batch_lot_no || null,
        })),
    };

    try {
        const csrfToken = window.StockOutConfig?.data?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch(window.StockOutConfig?.routes?.confirmShipStockOut || '/inventory/api/confirm-ship-stock-out', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch order.');

        if (lockOverlay) lockOverlay.style.display = 'none';
        if (btnPrimary) btnPrimary.disabled = false;

        const shippedOrder = data.data.order;
        const idx = rawOrders.findIndex(o => o.id == shippedOrder.id);
        if (idx !== -1) rawOrders[idx] = shippedOrder;

        await fetchLiveStockOutData();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            html: `
                <p>Order <strong>${escapeHtml(shippedOrder.order_number)}</strong> has been marked as SHIPPED.</p>
                <p style="font-size: 12px; color: #64748b;">Physical stock has been decremented and logged in Master Stock Ledger.</p>
                <div style="margin-top: 14px; display: flex; justify-content: center; gap: 10px;">
                    <a href="${window.StockOutConfig?.routes?.stocksOverview || '/inventory/stocks-overview'}" class="hr-btn hr-btn-secondary hr-btn-sm">
                        <i class="ph ph-squares-four"></i> View in Stocks Overview
                    </a>
                </div>
            `,
            confirmButtonColor: '#9333ea',
        });

        loadOrderIntoPickPack(null);
        updatePickOrderDropdown();
        switchOutTab('tab-out-list');
    } catch (err) {
        if (lockOverlay) lockOverlay.style.display = 'none';
        if (btnPrimary) btnPrimary.disabled = false;
        Swal.fire({ icon: 'error', title: 'Dispatch Error', text: err.message, confirmButtonColor: '#9333ea' });
    }
}

// ============================================================================
// I. LOT / BATCH MODAL, LIVE MOCKUP SLIP, LOCAL DRAFT PERSISTENCE
// ============================================================================
window.openOutLotModal = function (idx) {
    const items = getCurrentBuilderItems();
    const it = items[idx];
    if (!it) return;

    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

    setVal('outLotModalRowIdx', String(idx));
    setTxt('outLotModalItemTitle', `${it.sku} • ${it.item_name}`);
    setVal('outLotModalLotNumber', it.batch_lot_no || '');
    setVal('outLotModalNotes', it.notes || '');
    const errEl = document.getElementById('outLotModalError');
    if (errEl) errEl.hidden = true;

    const modal = document.getElementById('outLotExpiryModal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => document.getElementById('outLotModalLotNumber')?.focus(), 50);
    }
};

window.closeOutLotModal = function () {
    const modal = document.getElementById('outLotExpiryModal');
    if (modal) modal.style.display = 'none';
};

window.closeOutLotModalOnBackdrop = function (e) {
    if (e.target && e.target.id === 'outLotExpiryModal') closeOutLotModal();
};

window.onOutLotModalKeydown = function (e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        closeOutLotModal();
    } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        saveOutLotModal();
    }
};

window.generateOutLotNumber = function () {
    const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
    const randSuffix = Math.floor(1000 + Math.random() * 9000);
    const el = document.getElementById('outLotModalLotNumber');
    if (el) el.value = `LOT-${dateStr}-${randSuffix}`;
};

window.saveOutLotModal = function () {
    const idx = parseInt(document.getElementById('outLotModalRowIdx')?.value || '-1', 10);
    const items = getCurrentBuilderItems();
    const it = items[idx];
    if (!it) {
        closeOutLotModal();
        return;
    }

    const lotNum = (document.getElementById('outLotModalLotNumber')?.value || '').trim();
    const notes = (document.getElementById('outLotModalNotes')?.value || '').trim();
    const errEl = document.getElementById('outLotModalError');
    if (!lotNum) {
        if (errEl) {
            errEl.textContent = 'Picked Batch / Lot Number is required for traceability.';
            errEl.hidden = false;
        }
        document.getElementById('outLotModalLotNumber')?.focus();
        return;
    }

    it.batch_lot_no = lotNum;
    it.notes = notes;
    closeOutLotModal();
    renderActiveBuilderTable();
    scheduleOutMockupUpdate();
    if (!activePickOrder) scheduleOutDraftSave();
    showOutToast(`Lot # updated for ${it.sku}`);
};

let outMockupDebounce = null;
function scheduleOutMockupUpdate() {
    clearTimeout(outMockupDebounce);
    outMockupDebounce = setTimeout(() => {
        window.requestAnimationFrame(updateOutLiveMockup);
    }, 100);
}

window.updateOutLiveMockup = function () {
    const sheet = document.getElementById('outLiveMockupSheet');
    if (!sheet) return;

    const companyName = window.StockOutConfig?.company?.name || 'RMS Logistics';
    const orderRef = activePickOrder ? activePickOrder.order_number : `SO-${new Date().getFullYear()}-${window.StockOutStore.builderMode === 'direct' ? 'DIRECT' : 'DRAFT'}`;
    const orderType = document.getElementById('draftOrderType')?.value || 'KITCHEN_USAGE';
    const destLoc = activePickOrder ? activePickOrder.destination_location : getSelectedDestinationLabel();
    const srcLoc = document.getElementById('outSourceLocationSelect')?.value || 'Stock Zone A';
    const requester = document.getElementById('draftRequestedBy')?.value || 'Chef';
    const picker = document.getElementById('pickPickerName')?.value || 'Warehouse Picker';
    const carrier = document.getElementById('pickCarrierName')?.value || 'In-House Logistics';
    const waybill = document.getElementById('pickWaybill')?.value || document.getElementById('draftReferenceNo')?.value || 'Pending Ref';
    const timestamp = (document.getElementById('draftRequiredAt')?.value || '').replace('T', ' ') || formatDate(new Date());

    const items = getCurrentBuilderItems();
    const isPickMode = Boolean(activePickOrder);
    const pageBadge = document.getElementById('outMockupPageCountBadge');
    if (pageBadge) {
        const pages = Math.max(1, Math.ceil(items.length / 10));
        pageBadge.textContent = `${pages} Page${pages > 1 ? 's' : ''}`;
    }

    let sumReq = 0;
    let sumPack = 0;
    let sumVal = 0;
    items.forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        sumReq += r;
        sumPack += p;
        sumVal += (isPickMode ? p : (p > 0 ? p : r)) * cost;
    });

    const rowsHtml = items.length === 0
        ? `<tr><td colspan="6" style="text-align: center; padding: 14px; color: #94a3b8;">No items loaded</td></tr>`
        : items.map((it, i) => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 4px 5px; color: #64748b;">${i + 1}</td>
                <td style="padding: 4px 5px;">
                    <div style="font-weight: 700; color: #0f172a;">${escapeHtml(it.item_name || it.sku)}</div>
                    <div style="font-family: monospace; font-size: 8.5px; color: #64748b;">${escapeHtml(it.sku)}${it.batch_lot_no ? ` · Lot: ${escapeHtml(it.batch_lot_no)}` : ''}</div>
                </td>
                <td style="padding: 4px 5px; text-align: center; font-weight: 700;">${escapeHtml(it.uom || 'PCS')}</td>
                <td style="padding: 4px 5px; text-align: right;">${(parseFloat(it.requested_qty) || 0).toFixed(2)}</td>
                <td style="padding: 4px 5px; text-align: right; font-weight: 800; color: #7e22ce;">${(parseFloat(it.packed_qty) || 0).toFixed(2)}</td>
                <td style="padding: 4px 5px; text-align: right; font-weight: 700;">₱${((isPickMode ? (parseFloat(it.packed_qty) || 0) : (parseFloat(it.packed_qty) || parseFloat(it.requested_qty) || 0)) * (parseFloat(it.unit_cost) || 0)).toFixed(2)}</td>
            </tr>
        `).join('');

    sheet.innerHTML = `
        <div style="border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-weight: 900; font-size: 13px; color: #0f172a; letter-spacing: -0.02em;">${escapeHtml(companyName.toUpperCase())}</div>
                <div style="font-size: 10px; color: #64748b;">Outbound Stock Dispatch &amp; Kitchen Issue Slip</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 900; font-size: 11px; color: #7e22ce; text-transform: uppercase;">STOCK OUT SLIP</div>
                <div style="font-family: monospace; font-weight: 800; font-size: 11px; color: #0f172a;">${escapeHtml(orderRef)}</div>
                <div style="font-size: 9.5px; color: #059669; font-weight: 700;">${escapeHtml(formatOrderType(orderType))}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; font-size: 10px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                <div style="font-weight: 800; font-size: 8.5px; color: #64748b; text-transform: uppercase; margin-bottom: 3px;">Custody &amp; Route</div>
                <div style="font-weight: 800; color: #0f172a;">${escapeHtml(destLoc)}</div>
                <div style="color: #475569; font-size: 9.5px; margin-top: 2px;">From: <strong>${escapeHtml(srcLoc)}</strong></div>
                <div style="color: #64748b; font-size: 9px; margin-top: 1px;">Requester: <strong>${escapeHtml(requester)}</strong></div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                <div style="font-weight: 800; font-size: 8.5px; color: #64748b; text-transform: uppercase; margin-bottom: 3px;">Dispatch &amp; Carrier</div>
                <div style="color: #0f172a; font-size: 9.5px;">Picker: <strong>${escapeHtml(picker)}</strong></div>
                <div style="color: #475569; font-size: 9px; margin-top: 2px;">${escapeHtml(carrier)} · ${escapeHtml(waybill)}</div>
                <div style="color: #64748b; font-size: 9px; margin-top: 1px;">Schedule: ${escapeHtml(timestamp)}</div>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 9.8px; margin-bottom: 12px;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 1.5px solid #cbd5e1; text-align: left;">
                    <th style="padding: 4px 5px;">#</th>
                    <th style="padding: 4px 5px;">Item &amp; SKU</th>
                    <th style="padding: 4px 5px; text-align: center;">UOM</th>
                    <th style="padding: 4px 5px; text-align: right;">Req</th>
                    <th style="padding: 4px 5px; text-align: right;">Pack</th>
                    <th style="padding: 4px 5px; text-align: right;">Value</th>
                </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
        </table>

        <div style="border-top: 1.5px solid #cbd5e1; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 10px;">
            <div style="color: #64748b;">
                <div>Requested: <strong>${sumReq.toFixed(2)}</strong> · Packed: <strong>${sumPack.toFixed(2)}</strong></div>
                <div>Ledger: <strong>SYS_LEDGER (STOCK_OUT)</strong></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11.5px; font-weight: 900; color: #7e22ce;">₱${sumVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
            </div>
        </div>
    `;
};

window.openOutDocumentPreview = function () {
    const sheet = document.getElementById('outLiveMockupSheet');
    if (!sheet) return;
    Swal.fire({
        title: 'Stock Issue & Packing Slip Preview',
        html: `<div style="text-align: left; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;">${sheet.innerHTML}</div>`,
        width: 720,
        showCancelButton: true,
        confirmButtonText: '<i class="ph ph-printer"></i> Print Issue Slip',
        confirmButtonColor: '#7c3aed',
        cancelButtonText: 'Close',
    }).then(res => {
        if (res.isConfirmed) window.print();
    });
};

window.copyOutReference = function () {
    const ref = document.getElementById('draftRefDisplay')?.textContent?.trim() || 'SO-NEW';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(ref).then(() => showOutToast(`Copied ${ref} to clipboard`));
    } else {
        showOutToast(`Reference: ${ref}`);
    }
};

window.toggleOutMockupDrawer = function (open) {
    const splitBody = document.getElementById('outBuilderSplitBody');
    const backdrop = document.getElementById('outDrawerBackdrop');
    const btn = document.getElementById('outMockupToggleBtn');
    if (!splitBody) return;
    const isOpen = open !== undefined ? open : !splitBody.classList.contains('grn-mockup-open');
    splitBody.classList.toggle('grn-mockup-open', isOpen);
    if (backdrop) backdrop.hidden = !isOpen;
    if (btn) btn.setAttribute('aria-expanded', String(isOpen));
};

window.toggleOutOverflowMenu = function (event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('outOverflowMenu');
    const btn = document.getElementById('outOverflowBtn');
    if (!menu) return;
    const isHidden = menu.hidden;
    menu.hidden = !isHidden;
    if (btn) btn.setAttribute('aria-expanded', String(!isHidden));
};

window.confirmClearOutSheet = function () {
    toggleOutOverflowMenu();
    Swal.fire({
        title: 'Reset Stock Out Sheet?',
        text: 'Clears current unsaved lines and resets the form back to New Requisition Draft.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, Clear Sheet',
    }).then(res => {
        if (res.isConfirmed) {
            loadOrderIntoPickPack(null, false);
            resetDraftForm();
            discardOutDraft(true);
            showOutToast('Stock Out sheet reset');
        }
    });
};

// LocalStorage Draft Recovery (Frontend Rule 16)
let outDraftTimer = null;
function scheduleOutDraftSave() {
    clearTimeout(outDraftTimer);
    outDraftTimer = setTimeout(saveOutLocalDraft, 450);
}

function saveOutLocalDraft() {
    if (activePickOrder || draftItems.length === 0) return;
    try {
        localStorage.setItem('rms_stock_out_draft_v1', JSON.stringify({
            savedAt: new Date().toISOString(),
            builderMode: window.StockOutStore.builderMode,
            orderType: document.getElementById('draftOrderType')?.value || 'KITCHEN_USAGE',
            notes: document.getElementById('draftNotes')?.value || '',
            referenceNo: document.getElementById('draftReferenceNo')?.value || '',
            items: draftItems,
        }));
        const ind = document.getElementById('outDraftIndicator');
        if (ind) ind.hidden = false;
    } catch (e) {}
}

function checkOutLocalDraft() {
    try {
        const raw = localStorage.getItem('rms_stock_out_draft_v1');
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (draft && Array.isArray(draft.items) && draft.items.length > 0) {
            const banner = document.getElementById('outResumeBanner');
            const text = document.getElementById('outResumeBannerText');
            if (banner && text) {
                text.textContent = `Unsaved Stock Out draft (${draft.items.length} line${draft.items.length === 1 ? '' : 's'}) found.`;
                banner.hidden = false;
            }
        }
    } catch (e) {}
}

window.resumeOutDraft = function () {
    try {
        const raw = localStorage.getItem('rms_stock_out_draft_v1');
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft) return;

        window.StockOutStore.builderMode = draft.builderMode || 'draft';
        activePickOrder = null;
        window.StockOutStore.activePickOrder = null;

        if (draft.orderType) {
            const sel = document.getElementById('draftOrderType');
            if (sel) { sel.value = draft.orderType; handleOrderTypeChange(draft.orderType); }
        }
        if (draft.notes) document.getElementById('draftNotes').value = draft.notes;
        if (draft.referenceNo) document.getElementById('draftReferenceNo').value = draft.referenceNo;

        draftItems.length = 0;
        (draft.items || []).forEach(it => draftItems.push(it));

        const banner = document.getElementById('outResumeBanner');
        if (banner) banner.hidden = true;

        switchOutTab('tab-pick-pack-ship');
        showOutToast('Resumed Stock Out draft');
    } catch (e) {}
};

window.discardOutDraft = function (silent = false) {
    localStorage.removeItem('rms_stock_out_draft_v1');
    const banner = document.getElementById('outResumeBanner');
    if (banner) banner.hidden = true;
    const ind = document.getElementById('outDraftIndicator');
    if (ind) ind.hidden = true;
    if (!silent) showOutToast('Local draft discarded');
};

// ============================================================================
// J. BACKEND SYNC & DETAIL MODAL
// ============================================================================
async function fetchLiveStockOutData() {
    try {
        const res = await fetch(window.StockOutConfig?.routes?.stockOutData || '/inventory/api/stock-out-data');
        const json = await res.json();
        if (json.success && json.data) {
            rawOrders = json.data.orders || [];
            rawProducts = json.data.products || [];
            rawVendors = json.data.vendors || [];
            rawCategories = json.data.categories || [];
            window.StockOutStore.orders = rawOrders;
            window.StockOutStore.products = rawProducts;
            window.StockOutStore.vendors = rawVendors;
            window.StockOutStore.categories = rawCategories;
            if (json.data.warehouses) window.StockOutStore.warehouses = json.data.warehouses;
            if (json.data.storageAreas) window.StockOutStore.storageAreas = json.data.storageAreas;
            if (json.data.movementTypes) window.StockOutStore.movementTypes = json.data.movementTypes;

            renderOrdersList();
            updatePickOrderDropdown();
        }
    } catch (e) {
        console.error('Failed to sync live stock-out data:', e);
    }
}

function openOrderDetailModal(orderId) {
    const order = rawOrders.find(o => o.id == orderId);
    if (!order) return;

    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setTxt('modalOrderNum', `${order.order_number} [${order.status}]`);
    setTxt('modalOrderDate', 'Created: ' + formatDate(order.created_at) + (order.dispatched_at ? ' | Dispatched: ' + formatDate(order.dispatched_at) : ''));
    setTxt('modalDest', order.destination_location || '-');
    setTxt('modalType', formatOrderType(order.order_type));
    setTxt('modalReq', `${order.requested_by || '-'} (${order.department || 'Kitchen'})`);
    setTxt('modalPicker', order.picker_name || 'Pending assignment');
    setTxt('modalCarrier', order.carrier_name || 'Standard In-House Logistics');
    setTxt('modalWaybill', order.tracking_waybill || 'N/A');

    const tbody = document.getElementById('modalItemsTableBody');
    let html = '';
    let totalVal = 0;

    (order.items || []).forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        const lineT = p > 0 ? p * cost : r * cost;
        totalVal += lineT;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: #9333ea;">${escapeHtml(it.sku)}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td><span class="hr-badge hr-badge-neutral">${escapeHtml(it.category || 'Raw')}</span></td>
                <td>${escapeHtml(it.uom)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">${r.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: ${p >= r ? '#059669' : '#0f172a'};">${p.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${cost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${lineT.toFixed(2)}</td>
            </tr>
        `;
    });

    if (tbody) tbody.innerHTML = html;
    setTxt('modalTotalValDisplay', '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    document.getElementById('orderDetailModal')?.classList.add('open');
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal')?.classList.remove('open');
}

function printPackingSlip() {
    window.print();
}

// ============================================================================
// K. KEYBOARD SHORTCUTS, GLOBAL CLICK LISTENERS & INITIALIZATION
// ============================================================================
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            if (window.StockOutStore.activeTab === 'tab-out-list') {
                switchOutTab('tab-create-draft');
            }
            setTimeout(() => document.getElementById('outScanInput')?.focus(), 60);
        } else if (e.key === 'F8') {
            if (window.StockOutStore.activeTab !== 'tab-out-list') {
                e.preventDefault();
                openOutDocumentPreview();
            }
        } else if (e.key === 'F10') {
            if (window.StockOutStore.activeTab !== 'tab-out-list') {
                e.preventDefault();
                handlePrimaryOutboundAction();
            }
        } else if (e.key === 'Escape') {
            const lotModal = document.getElementById('outLotExpiryModal');
            if (lotModal && lotModal.style.display !== 'none') {
                closeOutLotModal();
                return;
            }
            closeOrderDetailModal();
            toggleOutMockupDrawer(false);
            const oMenu = document.getElementById('outOverflowMenu');
            if (oMenu) oMenu.hidden = true;
        } else if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            document.getElementById('orderSearchInput')?.focus();
        }
    });
}

document.addEventListener('click', (e) => {
    const qDd = document.getElementById('outQueueTableSettingsDropdown');
    if (qDd && !qDd.contains(e.target) && !e.target.closest('#btnOutQueueTableSettings') && !e.target.closest('#btnOutQueueColConfig')) {
        qDd.style.display = 'none';
    }
    const bDd = document.getElementById('outBuilderSettingsDropdown');
    if (bDd && !bDd.contains(e.target) && !e.target.closest('#btnOutBuilderSettings')) {
        bDd.style.display = 'none';
    }
    const oMenu = document.getElementById('outOverflowMenu');
    const oBtn = document.getElementById('outOverflowBtn');
    if (oMenu && !oMenu.hidden && !oMenu.contains(e.target) && !oBtn?.contains(e.target)) {
        oMenu.hidden = true;
        if (oBtn) oBtn.setAttribute('aria-expanded', 'false');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    populateOutWarehouseDropdowns();
    populateOutMovementTypes('KITCHEN_USAGE');
    renderOrdersList();
    renderActiveBuilderTable();
    renderOutChevronPipeline();
    updateOutLiveMockup();
    setupKeyboardShortcuts();
    checkOutLocalDraft();
});

// ============================================================================
// WINDOW EXPORTS (Backward-Compatible + Stock In Parity)
// ============================================================================
Object.assign(window, {
    switchOutTab,
    renderOrdersList,
    filterOrdersList,
    filterOrdersByKpi,
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
    escapeHtml,
    startNewOutRequisition,
    startDirectOutboundMode,
    handlePrimaryOutboundAction,
    selectOutRouteStep: window.selectOutRouteStep,
    advanceOutRouteStep: window.advanceOutRouteStep,
});
