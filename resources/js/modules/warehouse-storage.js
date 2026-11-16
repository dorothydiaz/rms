/**
 * ==========================================================================
 * WAREHOUSE & STORAGE AREA ENGINE (INVENTORY CONFIGURATION MODULE 1)
 * ==========================================================================
 */

const WH_STORAGE_KEY = 'rms_inv_warehouses_v2';
const AREA_STORAGE_KEY = 'rms_inv_storage_areas_v2';
const WH_LEGACY_STORAGE_KEY = 'rms_inv_warehouses_v1';
const AREA_LEGACY_STORAGE_KEY = 'rms_inv_storage_areas_v1';
const WH_LOCATION_COLS_KEY = 'rms_wh_location_visible_cols_v3';
const WH_LOCATION_ORDER_KEY = 'rms_wh_location_col_order_v2';
const WH_LOCATION_WIDTHS_KEY = 'rms_wh_location_col_widths_v2';

const WH_LOCATION_COLUMNS = [
    {
        key: 'location',
        title: 'Location',
        info: '',
        showInfo: false,
        defaultWidth: 320,
        minWidth: 210,
        locked: true,
        defaultVisible: true,
        align: 'left',
    },
    {
        key: 'area_type',
        title: 'Type / Level',
        info: '',
        showInfo: false,
        defaultWidth: 165,
        minWidth: 120,
        defaultVisible: true,
        align: 'left',
    },
    {
        key: 'behavior',
        title: 'Behavior',
        info: 'Operational rule: Storage (stock rests here), Staging (QA/packing pass-through), Dock (inbound/outbound bay), or Virtual (ledger counterpart).',
        showInfo: true,
        defaultWidth: 130,
        minWidth: 105,
        defaultVisible: true,
        align: 'left',
    },
    {
        key: 'barcode',
        title: 'Barcode',
        info: '',
        showInfo: false,
        defaultWidth: 195,
        minWidth: 140,
        defaultVisible: true,
        align: 'left',
    },
    {
        key: 'stock_qty',
        title: 'Stock (Units)',
        info: 'Rolled-up unit balance across this location and its nested children.',
        showInfo: true,
        defaultWidth: 135,
        minWidth: 105,
        defaultVisible: true,
        align: 'right',
    },
];

let _whIsResizingCol = false;

window.WhStore = {
    warehouses: [],
    storageAreas: [],
    boundaryAreas: [],
    selectedWarehouseId: 'WH-MAIN',
    behaviorFilter: 'ALL',
    holdsStockOnly: false,
    openRowMenuAreaId: null,
    collapsedNodeIds: new Set(),
    highlightedAreaId: null,
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

function showWhToast(message, type = 'success') {
    const container = document.getElementById('whToastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `wh-toast ${type}`;
    const icon = type === 'danger' ? 'ph-warning-octagon' : (type === 'warning' ? 'ph-warning' : 'ph-check-circle');
    toast.innerHTML = `<i class="ph ${icon}" style="font-size: 16px;"></i><span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3200);
}

function inferLocationKind(area) {
    if (area?.location_kind === 'Virtual' || area?.location_kind === 'Physical') {
        return area.location_kind;
    }
    const beh = String(area?.behavior || '').toLowerCase();
    const type = String(area?.area_type || '').toLowerCase();
    return (beh === 'virtual' || type.includes('virtual')) ? 'Virtual' : 'Physical';
}

function hydrateWarehouseState() {
    const cfg = window.WarehouseStorageConfig || {};
    let savedWh = null;
    let savedAreas = null;
    try {
        savedWh = JSON.parse(localStorage.getItem(WH_STORAGE_KEY));
        savedAreas = JSON.parse(localStorage.getItem(AREA_STORAGE_KEY));
    } catch (e) {}

    window.WhStore.warehouses = Array.isArray(savedWh) && savedWh.length > 0
        ? savedWh
        : (cfg.initialWarehouses || []);

    const rawAreas = Array.isArray(savedAreas) && savedAreas.length > 0
        ? savedAreas
        : (cfg.initialStorageAreas || []);

    window.WhStore.storageAreas = rawAreas.map(a => ({
        ...a,
        location_kind: inferLocationKind(a),
    }));

    window.WhStore.boundaryAreas = cfg.initialBoundaryAreas || [];

    if (window.WhStore.warehouses.length > 0 && !window.WhStore.warehouses.some(w => w.id === window.WhStore.selectedWarehouseId)) {
        window.WhStore.selectedWarehouseId = window.WhStore.warehouses[0].id;
    }

    persistWarehouseState();
}

function persistWarehouseState() {
    try {
        const whJson = JSON.stringify(window.WhStore.warehouses);
        const areaJson = JSON.stringify(window.WhStore.storageAreas);
        localStorage.setItem(WH_STORAGE_KEY, whJson);
        localStorage.setItem(AREA_STORAGE_KEY, areaJson);
        localStorage.setItem(WH_LEGACY_STORAGE_KEY, whJson);
        localStorage.setItem(AREA_LEGACY_STORAGE_KEY, areaJson);
    } catch (e) {}
}

function getOrderedLocationColumns() {
    const validKeys = new Set(WH_LOCATION_COLUMNS.map(c => c.key));
    let savedOrder = [];
    try {
        const raw = JSON.parse(localStorage.getItem(WH_LOCATION_ORDER_KEY));
        if (Array.isArray(raw)) {
            savedOrder = raw.filter(k => validKeys.has(k));
        }
    } catch (e) {}

    const seen = new Set();
    const orderedKeys = [];
    savedOrder.forEach(k => {
        if (!seen.has(k)) {
            seen.add(k);
            orderedKeys.push(k);
        }
    });
    WH_LOCATION_COLUMNS.forEach(c => {
        if (!seen.has(c.key)) {
            seen.add(c.key);
            orderedKeys.push(c.key);
        }
    });

    return orderedKeys.map(k => WH_LOCATION_COLUMNS.find(c => c.key === k));
}

function saveLocationColumnOrder(orderKeys) {
    try {
        localStorage.setItem(WH_LOCATION_ORDER_KEY, JSON.stringify(orderKeys));
    } catch (e) {}
}

function getLocationColumnWidths() {
    try {
        const raw = JSON.parse(localStorage.getItem(WH_LOCATION_WIDTHS_KEY));
        if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
            return raw;
        }
    } catch (e) {}
    return {};
}

function saveLocationColumnWidths(widthsMap) {
    try {
        localStorage.setItem(WH_LOCATION_WIDTHS_KEY, JSON.stringify(widthsMap));
    } catch (e) {}
}

function getVisibleLocationColumns() {
    const ordered = getOrderedLocationColumns();
    try {
        const raw = JSON.parse(localStorage.getItem(WH_LOCATION_COLS_KEY));
        if (Array.isArray(raw) && raw.length > 0) {
            const set = new Set(raw);
            set.add('location');
            return ordered.filter(c => c.locked || set.has(c.key)).map(c => c.key);
        }
    } catch (e) {}
    return ordered.filter(c => c.defaultVisible).map(c => c.key);
}

function saveVisibleLocationColumns(keys) {
    const unique = Array.from(new Set(['location', ...keys]));
    try {
        localStorage.setItem(WH_LOCATION_COLS_KEY, JSON.stringify(unique));
    } catch (e) {}
}

function moveWhLocationColumn(key, delta) {
    const ordered = getOrderedLocationColumns().map(c => c.key);
    const idx = ordered.indexOf(key);
    const targetIdx = idx + delta;
    if (idx < 0 || targetIdx < 0 || targetIdx >= ordered.length) return;

    const [moved] = ordered.splice(idx, 1);
    ordered.splice(targetIdx, 0, moved);
    saveLocationColumnOrder(ordered);
    renderWhColSettingsDropdown();
    renderSelectedWarehouseTree();
}

function startWhColResize(event, key) {
    event.preventDefault();
    event.stopPropagation();

    const table = document.getElementById('whLocationsTable');
    const th = event.currentTarget?.closest('th');
    if (!table || !th) return;

    _whIsResizingCol = true;
    const allThs = Array.from(table.querySelectorAll('thead th'));
    const thWidths = new Map();
    let totalInitialW = 0;

    allThs.forEach(cell => {
        const w = Math.round(cell.getBoundingClientRect().width);
        thWidths.set(cell, w);
        totalInitialW += w;
    });

    allThs.forEach(cell => {
        const w = thWidths.get(cell);
        cell.style.width = `${w}px`;
        cell.style.minWidth = `${w}px`;
    });
    table.style.width = `${totalInitialW}px`;

    const colMeta = WH_LOCATION_COLUMNS.find(c => c.key === key);
    const minW = colMeta?.minWidth || 105;
    const startX = event.clientX;
    const startW = thWidths.get(th) || colMeta?.defaultWidth || 160;
    const savedWidths = getLocationColumnWidths();
    const resizer = event.currentTarget;
    if (resizer) resizer.classList.add('is-resizing');
    document.body.style.cursor = 'col-resize';

    function onMouseMove(moveEv) {
        const nextW = Math.max(minW, Math.round(startW + (moveEv.clientX - startX)));
        const delta = nextW - startW;
        th.style.width = `${nextW}px`;
        th.style.minWidth = `${nextW}px`;
        table.style.width = `${Math.max(400, totalInitialW + delta)}px`;
        savedWidths[key] = nextW;
    }

    function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        _whIsResizingCol = false;
        document.body.style.cursor = '';
        if (resizer) resizer.classList.remove('is-resizing');
        saveLocationColumnWidths(savedWidths);
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

function toggleWhColSettingsDropdown(event) {
    if (event) event.stopPropagation();
    const dd = document.getElementById('whColSettingsDropdown');
    if (!dd) return;
    const btn = event?.currentTarget;
    const opening = !dd.classList.contains('is-active');
    if (opening) {
        renderWhColSettingsDropdown();
        if (btn && btn.getBoundingClientRect) {
            const rect = btn.getBoundingClientRect();
            const dropW = Math.min(370, window.innerWidth * 0.92);
            dd.style.top = `${Math.round(rect.bottom + 6)}px`;
            dd.style.left = `${Math.max(12, Math.round(rect.right - dropW))}px`;
            dd.style.right = 'auto';
        }
        dd.classList.add('is-active');
        dd.style.display = 'block';
    } else {
        dd.classList.remove('is-active');
        dd.style.display = 'none';
    }
}

function renderWhColSettingsDropdown() {
    const dd = document.getElementById('whColSettingsDropdown');
    if (!dd) return;
    const ordered = getOrderedLocationColumns();
    const visibleKeys = new Set(getVisibleLocationColumns());
    const visibleCount = visibleKeys.size;
    const totalCount = ordered.length;

    dd.innerHTML = `
        <div class="rfq-settings-dropdown-header">
            <div class="rfq-settings-title-row">
                <div class="rfq-settings-title"><i class="ph ph-gear-six"></i><span>Location Columns &amp; Order</span></div>
                <span class="rfq-col-badge-default">${visibleCount} of ${totalCount} visible</span>
            </div>
            <div class="rfq-settings-links-row">
                <button type="button" class="rfq-settings-link-btn" onclick="resetWhLocationColumns()">Reset Layout &amp; Widths</button>
                <button type="button" class="rfq-settings-link-btn" onclick="selectAllWhLocationColumns()">Select All</button>
            </div>
        </div>
        <div class="rfq-settings-col-list">
            ${ordered.map((col, idx) => `
                <div class="rfq-dropdown-col-item">
                    <label class="rfq-col-item-label">
                        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                            <input type="checkbox" ${visibleKeys.has(col.key) ? 'checked' : ''} ${col.locked ? 'disabled' : ''} onchange="toggleWhLocationColumn('${escapeHtml(col.key)}')">
                            <span class="rfq-col-name">${escapeHtml(col.title)}</span>
                            ${col.locked ? '<span style="font-size:0.65rem;color:#94a3b8;font-weight:600;">(Locked)</span>' : ''}
                        </div>
                        <div class="wh-col-move-btns" onclick="event.preventDefault(); event.stopPropagation();">
                            <button type="button" class="wh-col-move-btn" ${idx === 0 ? 'disabled' : ''} onclick="moveWhLocationColumn('${escapeHtml(col.key)}', -1)" title="Move column left">
                                <i class="ph ph-arrow-up"></i>
                            </button>
                            <button type="button" class="wh-col-move-btn" ${idx === ordered.length - 1 ? 'disabled' : ''} onclick="moveWhLocationColumn('${escapeHtml(col.key)}', 1)" title="Move column right">
                                <i class="ph ph-arrow-down"></i>
                            </button>
                        </div>
                    </label>
                </div>
            `).join('')}
        </div>
    `;
}

function toggleWhLocationColumn(key) {
    const col = WH_LOCATION_COLUMNS.find(c => c.key === key);
    if (!col || col.locked) return;
    const visible = new Set(getVisibleLocationColumns());
    if (visible.has(key)) {
        visible.delete(key);
    } else {
        visible.add(key);
    }
    saveVisibleLocationColumns(Array.from(visible));
    renderWhColSettingsDropdown();
    renderSelectedWarehouseTree();
}

function resetWhLocationColumns() {
    saveVisibleLocationColumns(WH_LOCATION_COLUMNS.filter(c => c.defaultVisible).map(c => c.key));
    saveLocationColumnOrder(WH_LOCATION_COLUMNS.map(c => c.key));
    saveLocationColumnWidths({});
    const table = document.getElementById('whLocationsTable');
    if (table) table.style.width = '100%';
    renderWhColSettingsDropdown();
    renderSelectedWarehouseTree();
}

function selectAllWhLocationColumns() {
    saveVisibleLocationColumns(WH_LOCATION_COLUMNS.map(c => c.key));
    renderWhColSettingsDropdown();
    renderSelectedWarehouseTree();
}

/**
 * Compute total stock for an area including all nested descendant areas
 */
function getSubtreeStockQty(areaId) {
    const areas = window.WhStore.storageAreas;
    const target = areas.find(a => a.id === areaId);
    if (!target) return 0;

    let total = Math.max(0, Number(target.stock_qty) || 0);
    const children = areas.filter(a => a.parent_id === areaId);
    children.forEach(ch => {
        total += getSubtreeStockQty(ch.id);
    });
    return total;
}

/**
 * Cycle prevention: check if candidateParentId is inside nodeId's subtree
 */
function isDescendantOrSelf(nodeId, candidateParentId) {
    if (!nodeId || !candidateParentId) return false;
    if (nodeId === candidateParentId) return true;
    const areas = window.WhStore.storageAreas;
    let curr = areas.find(a => a.id === candidateParentId);
    let guard = 0;
    while (curr && curr.parent_id && guard < 100) {
        if (curr.parent_id === nodeId) return true;
        curr = areas.find(a => a.id === curr.parent_id);
        guard++;
    }
    return false;
}

function updateWhKpisAndSamples() {
    const whs = window.WhStore.warehouses;
    const areas = window.WhStore.storageAreas;

    const activeWhCount = whs.filter(w => w.is_active).length;
    const physicalCount = areas.filter(a => inferLocationKind(a) === 'Physical').length;
    const virtualCount = areas.filter(a => inferLocationKind(a) === 'Virtual').length;
    const stockedAreas = areas.filter(a => (Number(a.stock_qty) || 0) > 0);
    const totalStockUnits = stockedAreas.reduce((acc, a) => acc + (Number(a.stock_qty) || 0), 0);

    const setTxt = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };

    setTxt('whKpiActiveWarehouses', `${activeWhCount} / ${whs.length}`);
    setTxt('whKpiTotalAreas', areas.length);
    setTxt('whKpiBehaviorBreakdownSub', `${physicalCount} Physical • ${virtualCount} Virtual`);
    setTxt('whKpiVirtualLocations', virtualCount);
    setTxt('whKpiVirtualSub', `${virtualCount} virtual across ${whs.length} facilities`);
    setTxt('whKpiStockedAreas', stockedAreas.length);
    setTxt('whKpiStockedUnitsSub', `${totalStockUnits.toLocaleString()} units tracked across locations`);
}

function renderWarehouseList() {
    const query = (document.getElementById('whDirectorySearch')?.value || '').trim().toLowerCase();
    const whs = window.WhStore.warehouses;
    const areas = window.WhStore.storageAreas;

    const countBadge = document.getElementById('whListCountBadge');
    if (countBadge) countBadge.textContent = whs.length;

    const filtered = whs.filter(w => {
        if (!query) return true;
        return (w.code || '').toLowerCase().includes(query) ||
               (w.name || '').toLowerCase().includes(query) ||
               (w.address || '').toLowerCase().includes(query);
    });

    const container = document.getElementById('whWarehouseListContainer');
    if (!container) return;

    if (filtered.length === 0) {
        container.innerHTML = `<div style="text-align:center; padding: 28px 12px; color:#64748b; font-size:12px;">No warehouses match your search.</div>`;
        return;
    }

    container.innerHTML = filtered.map(w => {
        const whAreas = areas.filter(a => a.warehouse_id === w.id);
        const virtualCount = whAreas.filter(a => inferLocationKind(a) === 'Virtual').length;
        const totalUnits = whAreas.reduce((acc, a) => acc + (Number(a.stock_qty) || 0), 0);
        const isSelected = w.id === window.WhStore.selectedWarehouseId;

        return `
            <div class="wh-item-card ${isSelected ? 'active' : ''}" onclick="selectWarehouse('${escapeHtml(w.id)}')">
                <div class="wh-item-top">
                    <div class="wh-item-code-row">
                        <span class="wh-status-dot ${w.is_active ? 'active' : 'inactive'}" title="${w.is_active ? 'Active facility' : 'Inactive facility'}"></span>
                        <span class="wh-card-mono-code">${escapeHtml(w.code)}</span>
                        ${!w.is_active ? `<span class="wh-inactive-tag">Inactive</span>` : ''}
                    </div>
                    <div class="wh-item-actions" onclick="event.stopPropagation()">
                        <button type="button" class="wh-ghost-icon-btn" onclick="openWarehouseModal('${escapeHtml(w.id)}')" title="Edit Warehouse">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                        <button type="button" class="wh-ghost-icon-btn danger" onclick="deleteWarehouse('${escapeHtml(w.id)}')" title="Delete Warehouse &amp; Locations">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="wh-item-name">${escapeHtml(w.name)}</div>
                <div class="wh-item-addr"><i class="ph ph-map-pin"></i> <span>${escapeHtml(w.address)}</span></div>
                <div class="wh-item-stats">
                    <span><strong>${whAreas.length - virtualCount}</strong> Physical</span>
                    <span><strong>${virtualCount}</strong> Virtual</span>
                    <span class="wh-card-units ${totalUnits > 0 ? 'has-stock' : ''}">${totalUnits.toLocaleString()} Units</span>
                </div>
            </div>
        `;
    }).join('');
}

function selectWarehouse(whId) {
    closeWhRowActionMenu();
    window.WhStore.selectedWarehouseId = whId;
    renderWarehouseList();
    renderSelectedWarehouseTree();
}

function setAreaBehaviorFilter(behavior) {
    closeWhRowActionMenu();
    window.WhStore.behaviorFilter = behavior;
    document.querySelectorAll('#whBehaviorFilterGroup .wh-filter-chip').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-behavior') === behavior);
    });
    renderSelectedWarehouseTree();
}

function toggleHoldsStockFilter(checked) {
    closeWhRowActionMenu();
    window.WhStore.holdsStockOnly = Boolean(checked);
    renderSelectedWarehouseTree();
}

function canLocationHaveChildren(area) {
    const type = String(area?.area_type || '').toLowerCase();
    if (type.includes('bin')) return false;
    if (type.includes('packing')) return false;
    if (type.includes('dock')) return false;
    if (type.includes('loss') || type.includes('adj') || type.includes('transit')) return false;
    return true;
}

function getStructuralIcon(area) {
    const kind = inferLocationKind(area);
    if (kind === 'Virtual') return 'ph-cloud';
    const t = String(area?.area_type || '').toLowerCase();
    if (t.includes('zone')) return 'ph-warehouse';
    if (t.includes('aisle')) return 'ph-arrows-vertical';
    if (t.includes('rack')) return 'ph-rows';
    if (t.includes('bin')) return 'ph-package';
    if (t.includes('dock')) return 'ph-door-open';
    return 'ph-table';
}

function closeWhRowActionMenu() {
    window.WhStore.openRowMenuAreaId = null;
    const menu = document.getElementById('whRowActionMenu');
    if (menu) {
        menu.classList.remove('is-open');
        menu.style.display = 'none';
    }
}

function toggleWhRowActionMenu(event, areaId) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('whRowActionMenu');
    if (!menu) return;

    if (window.WhStore.openRowMenuAreaId === areaId && menu.classList.contains('is-open')) {
        closeWhRowActionMenu();
        return;
    }

    const area = window.WhStore.storageAreas.find(a => a.id === areaId);
    if (!area) return;

    const kind = inferLocationKind(area);
    const allowChild = canLocationHaveChildren(area);

    menu.innerHTML = `
        ${allowChild ? `
            <button type="button" class="wh-menu-item" onclick="closeWhRowActionMenu(); openStorageAreaModal({ parentId: '${escapeHtml(area.id)}', presetKind: '${escapeHtml(kind)}' })">
                <i class="ph ph-plus"></i><span>Add child location</span>
            </button>
        ` : ''}
        <button type="button" class="wh-menu-item" onclick="closeWhRowActionMenu(); openStorageAreaModal({ areaId: '${escapeHtml(area.id)}' })">
            <i class="ph ph-pencil-simple"></i><span>Edit location</span>
        </button>
        <button type="button" class="wh-menu-item" onclick="closeWhRowActionMenu(); copyAreaBarcode('${escapeHtml(area.barcode)}')">
            <i class="ph ph-copy"></i><span>Copy barcode</span>
        </button>
        <button type="button" class="wh-menu-item" onclick="closeWhRowActionMenu(); toggleAreaActiveStatus('${escapeHtml(area.id)}')">
            <i class="ph ${area.is_active ? 'ph-toggle-left' : 'ph-toggle-right'}"></i><span>${area.is_active ? 'Deactivate' : 'Activate'}</span>
        </button>
        <div class="wh-menu-divider"></div>
        <button type="button" class="wh-menu-item danger" onclick="closeWhRowActionMenu(); deleteStorageArea('${escapeHtml(area.id)}')">
            <i class="ph ph-trash"></i><span>Delete location</span>
        </button>
    `;

    const btn = event?.currentTarget;
    if (btn && btn.getBoundingClientRect) {
        const rect = btn.getBoundingClientRect();
        const menuW = 190;
        const estH = allowChild ? 185 : 152;
        const openUp = (rect.bottom + estH + 12) > window.innerHeight;
        menu.style.top = openUp
            ? `${Math.max(12, Math.round(rect.top - estH - 4))}px`
            : `${Math.round(rect.bottom + 4)}px`;
        menu.style.left = `${Math.max(12, Math.round(rect.right - menuW))}px`;
    }

    window.WhStore.openRowMenuAreaId = areaId;
    menu.classList.add('is-open');
    menu.style.display = 'flex';
}

function renderLocationTableHeaders(visibleKeys) {
    const thead = document.getElementById('whLocationsThead');
    const table = document.getElementById('whLocationsTable');
    if (!thead || !table) return;

    const orderedVisible = getOrderedLocationColumns().filter(c => visibleKeys.includes(c.key));
    const savedWidths = getLocationColumnWidths();
    const hasCustomWidth = orderedVisible.some(c => Number(savedWidths[c.key]) > 0);
    const actionsWidth = 128;

    if (hasCustomWidth) {
        const sumW = orderedVisible.reduce((acc, c) => acc + (Number(savedWidths[c.key]) || c.defaultWidth), 0) + actionsWidth;
        table.style.width = `${sumW}px`;
    } else {
        table.style.width = '100%';
    }

    const pctDefaults = { location: '36%', area_type: '17%', behavior: '13%', barcode: '20%', stock_qty: '14%' };

    thead.innerHTML = `
        <tr>
            ${orderedVisible.map((c, idx) => {
                const customW = Number(savedWidths[c.key]);
                const wStyle = customW > 0
                    ? `width: ${customW}px; min-width: ${c.minWidth}px;`
                    : (hasCustomWidth
                        ? `width: ${c.defaultWidth}px; min-width: ${c.minWidth}px;`
                        : `width: ${pctDefaults[c.key] || 'auto'}; min-width: ${c.minWidth}px;`);
                const alignCls = c.align === 'right' ? 'wh-align-right' : (c.align === 'center' ? 'wh-align-center' : 'wh-align-left');
                const tipRightCls = (c.align === 'right' || idx >= orderedVisible.length - 1) ? 'wh-th-tip-right' : '';
                return `
                    <th class="wh-th wh-col-${escapeHtml(c.key)} ${alignCls} ${tipRightCls}"
                        data-col-key="${escapeHtml(c.key)}"
                        style="${wStyle}">
                        <div class="wh-th-inner ${c.align === 'right' ? 'is-right' : ''}">
                            <span class="wh-th-title-txt">${escapeHtml(c.title)}</span>
                            ${c.showInfo && c.info ? `
                                <span class="wh-th-info-btn" tabindex="0" role="button" aria-label="${escapeHtml(c.info)}" onclick="event.stopPropagation()">
                                    <i class="ph ph-question"></i>
                                    <span class="wh-th-tooltip" role="tooltip">${escapeHtml(c.info)}</span>
                                </span>
                            ` : ''}
                        </div>
                        <div class="wh-col-resizer" onmousedown="startWhColResize(event, '${escapeHtml(c.key)}')" title="Drag to resize column"></div>
                    </th>
                `;
            }).join('')}
            <th class="wh-th wh-th-actions wh-th-tip-right" style="width: ${actionsWidth}px; min-width: ${actionsWidth}px; max-width: ${actionsWidth}px;">
                <div class="wh-th-actions-inner">
                    <span class="wh-th-title-txt">Actions</span>
                    <button type="button" class="rfq-th-settings-btn" onclick="toggleWhColSettingsDropdown(event)" title="Configure columns &amp; order">
                        <i class="ph ph-gear-six"></i>
                    </button>
                </div>
            </th>
        </tr>
    `;
}

function renderSelectedWarehouseTree() {
    const visibleKeys = getVisibleLocationColumns();
    renderLocationTableHeaders(visibleKeys);

    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    const tbody = document.getElementById('whLocationsTbody');

    if (!wh) {
        const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setTxt('selectedWhCodeBadge', 'NONE');
        setTxt('selectedWhNameTitle', 'No Warehouse Selected');
        setTxt('whTreeSummaryCount', '0 locations');
        if (tbody) {
            tbody.innerHTML = `<tr class="wh-empty-tr"><td colspan="${visibleKeys.length + 1}">Create a warehouse on the left panel to begin managing locations.</td></tr>`;
        }
        return;
    }

    // Header updates
    document.getElementById('selectedWhCodeBadge').textContent = wh.code;
    document.getElementById('selectedWhNameTitle').textContent = wh.name;
    const statusEl = document.getElementById('selectedWhStatusBadge');
    statusEl.className = `wh-status-pill ${wh.is_active ? 'active' : 'inactive'}`;
    statusEl.textContent = wh.is_active ? 'Active' : 'Inactive';
    document.getElementById('selectedWhAddressText').innerHTML = `<i class="ph ph-map-pin"></i><span>${escapeHtml(wh.address)}</span>`;

    const whAreas = window.WhStore.storageAreas.filter(a => a.warehouse_id === whId);

    // Filter Chip Counts
    const setCount = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setCount('countBehAll', whAreas.length);
    setCount('countBehStorage', whAreas.filter(a => a.behavior === 'Storage').length);
    setCount('countBehStaging', whAreas.filter(a => a.behavior === 'Staging').length);
    setCount('countBehDock', whAreas.filter(a => a.behavior === 'Dock').length);
    setCount('countBehVirtual', whAreas.filter(a => inferLocationKind(a) === 'Virtual' || a.behavior === 'Virtual').length);
    setCount('countBehStocked', whAreas.filter(a => getSubtreeStockQty(a.id) > 0 && (Number(a.stock_qty) || 0) > 0).length);

    const searchQ = (document.getElementById('whTreeSearchInput')?.value || '').trim().toLowerCase();
    const behFilter = window.WhStore.behaviorFilter;
    const holdsStockOnly = Boolean(window.WhStore.holdsStockOnly);

    // Match filter dimensions + search while preserving ancestor hierarchy
    const matchingIds = new Set();
    whAreas.forEach(a => {
        const kind = inferLocationKind(a);
        const matchesBeh = behFilter === 'ALL'
            ? true
            : (behFilter === 'Virtual' ? (kind === 'Virtual' || a.behavior === 'Virtual') : a.behavior === behFilter);
        const matchesStock = !holdsStockOnly || (Number(a.stock_qty) || 0) > 0;
        const matchesSearch = !searchQ ||
            (a.name || '').toLowerCase().includes(searchQ) ||
            (a.code || '').toLowerCase().includes(searchQ) ||
            (a.barcode || '').toLowerCase().includes(searchQ) ||
            (a.area_type || '').toLowerCase().includes(searchQ) ||
            (a.behavior || '').toLowerCase().includes(searchQ) ||
            kind.toLowerCase().includes(searchQ);

        if (matchesBeh && matchesStock && matchesSearch) {
            matchingIds.add(a.id);
            let curr = a;
            let guard = 0;
            while (curr && curr.parent_id && guard < 50) {
                matchingIds.add(curr.parent_id);
                curr = whAreas.find(p => p.id === curr.parent_id);
                guard++;
            }
        }
    });

    const rootNodes = whAreas.filter(a => !a.parent_id || !whAreas.some(p => p.id === a.parent_id))
                             .filter(a => matchingIds.has(a.id));

    document.getElementById('whTreeSummaryCount').textContent = `${whAreas.length} location${whAreas.length === 1 ? '' : 's'} in ${wh.code}`;

    if (!tbody) return;

    if (rootNodes.length === 0) {
        tbody.innerHTML = `
            <tr class="wh-empty-tr">
                <td colspan="${visibleKeys.length + 1}">
                    <div style="text-align: center; padding: 44px 20px; color: #64748b;">
                        <i class="ph ph-tree-structure" style="font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                        <div style="font-weight: 700; color: #334155; font-size: 13.5px;">No Matching Storage Locations</div>
                        <div style="font-size: 12px; margin-top: 4px;">Clear your active filters or add a Physical / Virtual location above.</div>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    const flatRows = [];
    function flattenNode(area, depth, ancestorLines = [], isLastChild = false) {
        const children = whAreas.filter(ch => ch.parent_id === area.id && matchingIds.has(ch.id));
        const hasChildren = children.length > 0;
        const isCollapsed = window.WhStore.collapsedNodeIds.has(area.id) && !searchQ && !holdsStockOnly;
        flatRows.push({ area, depth, hasChildren, isCollapsed, ancestorLines, isLastChild });
        if (hasChildren && !isCollapsed) {
            children.forEach((ch, idx) => {
                const childIsLast = idx === children.length - 1;
                const nextAncestors = depth === 0 ? [] : [...ancestorLines, !isLastChild];
                flattenNode(ch, depth + 1, nextAncestors, childIsLast);
            });
        }
    }
    rootNodes.forEach((root, idx) => flattenNode(root, 0, [], idx === rootNodes.length - 1));

    const visibleCols = getOrderedLocationColumns().filter(c => visibleKeys.includes(c.key));
    tbody.innerHTML = flatRows.map(({ area, depth, hasChildren, isCollapsed, ancestorLines, isLastChild }) => {
        const kind = inferLocationKind(area);
        const subtreeStock = getSubtreeStockQty(area.id);
        const ownStock = Math.max(0, Number(area.stock_qty) || 0);
        const isHighlight = window.WhStore.highlightedAreaId === area.id;
        const allowChild = canLocationHaveChildren(area);

        const cellsHtml = visibleCols.map(col => {
            switch (col.key) {
                case 'location': {
                    const indentGuides = depth > 0
                        ? [
                            ...ancestorLines.map(hasLine => `<span class="wh-tree-indent-unit ${hasLine ? 'has-line' : 'is-empty'}" aria-hidden="true"></span>`),
                            `<span class="wh-tree-indent-unit is-branch ${isLastChild ? 'is-last' : 'is-mid'} ${!hasChildren ? 'to-leaf' : ''}" aria-hidden="true"></span>`
                          ].join('')
                        : '';
                    const structIcon = getStructuralIcon(area);
                    return `
                        <td class="wh-td wh-td-location">
                            <div class="wh-loc-cell">
                                ${indentGuides ? `<div class="wh-tree-indent">${indentGuides}</div>` : ''}
                                ${hasChildren ? `
                                    <button type="button" class="wh-chevron-btn" onclick="toggleTreeNode('${escapeHtml(area.id)}')" title="${isCollapsed ? 'Expand' : 'Collapse'}">
                                        <i class="ph ${isCollapsed ? 'ph-caret-right' : 'ph-caret-down'}"></i>
                                    </button>
                                ` : (depth === 0 ? `<span class="wh-chevron-spacer"></span>` : '')}
                                <i class="ph ${structIcon} wh-loc-icon ${kind === 'Virtual' ? 'is-virtual' : ''}" title="${escapeHtml(kind)} ${escapeHtml(area.area_type)}"></i>
                                <span class="wh-node-name" role="button" tabindex="0" onclick="openStorageAreaModal({ editId: '${escapeHtml(area.id)}' })" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openStorageAreaModal({ editId: '${escapeHtml(area.id)}' });}" title="${escapeHtml(area.name)}">${escapeHtml(area.name)}</span>
                                <span class="wh-inline-code">${escapeHtml(area.code)}</span>
                                ${area.is_default ? '<span class="wh-default-tag" title="Default warehouse location">Default</span>' : ''}
                                ${!area.is_active ? `<span class="wh-inactive-tag">Inactive</span>` : ''}
                            </div>
                        </td>
                    `;
                }
                case 'area_type': {
                    const typeIcon = kind === 'Virtual' ? 'ph-cloud' : 'ph-buildings';
                    return `
                        <td class="wh-td">
                            <div class="wh-type-cell">
                                <i class="ph ${typeIcon} ${kind === 'Virtual' ? 'is-virtual' : ''}" title="${escapeHtml(kind)} location"></i>
                                <span>${escapeHtml(area.area_type)}</span>
                            </div>
                        </td>
                    `;
                }
                case 'behavior':
                    return `
                        <td class="wh-td">
                            <span class="wh-beh-indicator ${escapeHtml(area.behavior)}">
                                <span class="wh-beh-dot"></span>
                                <span>${escapeHtml(area.behavior)}</span>
                            </span>
                        </td>
                    `;
                case 'barcode':
                    return `
                        <td class="wh-td">
                            <div class="wh-barcode-cell">
                                <span class="wh-mono-barcode">${escapeHtml(area.barcode)}</span>
                                <button type="button" class="wh-copy-ghost-btn" onclick="copyAreaBarcode('${escapeHtml(area.barcode)}')" title="Copy barcode">
                                    <i class="ph ph-copy"></i>
                                </button>
                            </div>
                        </td>
                    `;
                case 'stock_qty': {
                    const isRolledUp = subtreeStock > ownStock;
                    const rollupTitle = isRolledUp
                        ? `Total rolled up: ${subtreeStock.toLocaleString()} (Direct: ${ownStock.toLocaleString()} • Nested: ${(subtreeStock - ownStock).toLocaleString()})`
                        : `${ownStock.toLocaleString()} units on hand`;
                    return `
                        <td class="wh-td wh-align-right wh-td-qty" title="${escapeHtml(rollupTitle)}">
                            <span class="wh-stock-val ${subtreeStock > 0 ? 'has-stock' : 'is-zero'}">${subtreeStock.toLocaleString()}</span>
                            ${isRolledUp && ownStock > 0 ? `<span class="wh-stock-rollup-hint">(${ownStock.toLocaleString()} direct)</span>` : ''}
                        </td>
                    `;
                }
                default:
                    return `<td class="wh-td">—</td>`;
            }
        }).join('');

        return `
            <tr id="wh-loc-row-${escapeHtml(area.id)}" class="wh-loc-row ${depth === 0 ? 'is-root-row' : (hasChildren ? 'is-parent-row' : 'is-leaf-row')} ${!area.is_active ? 'is-inactive' : ''} ${isHighlight ? 'scan-highlight' : ''} ${kind === 'Virtual' ? 'is-virtual-row' : ''}">
                ${cellsHtml}
                <td class="wh-td wh-td-actions">
                    <div class="wh-node-actions">
                        ${allowChild ? `
                            <button type="button" class="wh-ghost-add-btn" onclick="openStorageAreaModal({ parentId: '${escapeHtml(area.id)}', presetKind: '${escapeHtml(kind)}' })" title="Add child location inside ${escapeHtml(area.name)}">
                                <i class="ph ph-plus"></i> Child
                            </button>
                        ` : ''}
                        <button type="button" class="wh-row-menu-btn" onclick="toggleWhRowActionMenu(event, '${escapeHtml(area.id)}')" title="Location actions">
                            <i class="ph ph-dots-three"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function toggleTreeNode(areaId) {
    closeWhRowActionMenu();
    if (window.WhStore.collapsedNodeIds.has(areaId)) {
        window.WhStore.collapsedNodeIds.delete(areaId);
    } else {
        window.WhStore.collapsedNodeIds.add(areaId);
    }
    renderSelectedWarehouseTree();
}

function expandOrCollapseAllTree(expand) {
    closeWhRowActionMenu();
    if (expand) {
        window.WhStore.collapsedNodeIds.clear();
    } else {
        window.WhStore.storageAreas
            .filter(a => a.warehouse_id === window.WhStore.selectedWarehouseId)
            .forEach(a => window.WhStore.collapsedNodeIds.add(a.id));
    }
    renderSelectedWarehouseTree();
}

function copyAreaBarcode(barcode) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(barcode).catch(() => {});
    }
    showWhToast(`Copied scanner barcode: ${barcode}`, 'success');
}

/**
 * Intelligent Search + Barcode Lookup (supports pressing Enter from search/scanner)
 */
let _whScanTimer = null;
function handleSmartLocationSearchKey(event) {
    if (!event || event.key !== 'Enter') return;
    event.preventDefault();
    clearTimeout(_whScanTimer);
    _whScanTimer = setTimeout(() => {
        const val = document.getElementById('whTreeSearchInput')?.value || '';
        if (val.trim()) {
            triggerBarcodeScanLookup(val);
        }
    }, 80);
}

function triggerBarcodeScanLookup(explicitBarcode) {
    const input = document.getElementById('whTreeSearchInput');
    const code = (explicitBarcode || input?.value || '').trim().toUpperCase();
    if (!code) {
        showWhToast('Enter or scan a storage location barcode to locate.', 'warning');
        return;
    }

    const match = window.WhStore.storageAreas.find(a =>
        String(a.barcode || '').toUpperCase() === code ||
        String(a.code || '').toUpperCase() === code
    );
    if (!match) {
        showWhToast(`No storage location found matching barcode/code "${code}".`, 'warning');
        return;
    }

    // Switch warehouse, clear restrictive filters & search box so full hierarchy context is shown, expand ancestors, highlight
    window.WhStore.selectedWarehouseId = match.warehouse_id;
    window.WhStore.behaviorFilter = 'ALL';
    window.WhStore.holdsStockOnly = false;
    const stockToggle = document.getElementById('whHoldsStockToggle');
    if (stockToggle) stockToggle.checked = false;
    document.querySelectorAll('#whBehaviorFilterGroup .wh-filter-chip').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-behavior') === 'ALL');
    });

    if (input && input.value.trim().toUpperCase() === code) {
        input.value = '';
    }

    let curr = match;
    let guard = 0;
    while (curr && curr.parent_id && guard < 50) {
        window.WhStore.collapsedNodeIds.delete(curr.parent_id);
        curr = window.WhStore.storageAreas.find(a => a.id === curr.parent_id);
        guard++;
    }

    window.WhStore.highlightedAreaId = match.id;
    renderWarehouseList();
    renderSelectedWarehouseTree();

    const rowEl = document.getElementById(`wh-loc-row-${match.id}`);
    if (rowEl) {
        rowEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    showWhToast(`Located [${match.barcode}] → ${match.name} (${inferLocationKind(match)} • ${match.behavior})`, 'success');
    setTimeout(() => {
        if (window.WhStore.highlightedAreaId === match.id) {
            window.WhStore.highlightedAreaId = null;
            renderSelectedWarehouseTree();
        }
    }, 3500);
}

function simulateBarcodeScan(barcode) {
    triggerBarcodeScanLookup(barcode);
}

/**
 * Warehouse Create / Edit Page Workspace & Delete
 * Automatically provisions Stock (Physical) and Virtual Stock (Virtual) when creating a new warehouse!
 */
function switchWhPageTab(tabKey) {
    document.querySelectorAll('#whWarehouseModal [data-wh-tab]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.whTab === tabKey);
    });
    document.querySelectorAll('#whWarehouseModal .wh-page-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === `whTabPane-${tabKey}`);
    });
}

function updateWhPageLivePreview() {
    const editId = document.getElementById('whFormId')?.value.trim();
    const name = document.getElementById('whFormName')?.value.trim() || '';
    const code = (document.getElementById('whFormCode')?.value.trim() || '').toUpperCase();
    const isActive = Boolean(document.getElementById('whFormActive')?.checked);

    const codeBadge = document.getElementById('whPageCodePreview');
    if (codeBadge) codeBadge.textContent = code || 'WH-NEW';

    const statusPill = document.getElementById('whPageStatusPill');
    if (statusPill) {
        statusPill.className = `wh-status-pill ${isActive ? 'active' : 'inactive'}`;
        statusPill.textContent = isActive ? 'Active' : 'Inactive';
    }

    const breadcrumbLabel = document.getElementById('whPageBreadcrumbLabel');
    if (breadcrumbLabel) {
        breadcrumbLabel.textContent = name || code || (editId ? 'Edit Warehouse' : 'New Warehouse');
    }

    if (!editId) {
        const titleEl = document.getElementById('whModalTitle');
        if (titleEl) titleEl.textContent = name ? `Create Warehouse: ${name}` : 'Create Warehouse';
    }
}

function renderWarehousePageSubTabs(whId) {
    const whAreas = whId ? window.WhStore.storageAreas.filter(a => a.warehouse_id === whId) : [];
    const tabCountEl = document.getElementById('whPageHierarchyTabCount');
    if (tabCountEl) tabCountEl.textContent = whAreas.length;

    const metricsBox = document.getElementById('whPageEditMetricsBox');
    if (metricsBox) {
        if (whId) {
            const physCount = whAreas.filter(a => inferLocationKind(a) === 'Physical').length;
            const virtCount = whAreas.filter(a => inferLocationKind(a) === 'Virtual').length;
            const totalUnits = whAreas.reduce((acc, a) => acc + Math.max(0, Number(a.stock_qty) || 0), 0);
            metricsBox.style.display = 'grid';
            metricsBox.innerHTML = `
                <div class="wh-mini-metric"><span>Locations</span><strong>${whAreas.length}</strong></div>
                <div class="wh-mini-metric"><span>Physical / Virtual</span><strong>${physCount} / ${virtCount}</strong></div>
                <div class="wh-mini-metric"><span>Total Units</span><strong>${totalUnits.toLocaleString()}</strong></div>
            `;
        } else {
            metricsBox.style.display = 'none';
            metricsBox.innerHTML = '';
        }
    }

    const defaultsEl = document.getElementById('whPageDefaultsList');
    if (defaultsEl) {
        if (!whId) {
            defaultsEl.innerHTML = `
                <div class="wh-autoseed-banner">
                    <i class="ph ph-sparkle"></i>
                    <div>
                        <div style="font-weight: 700; font-size: 0.8rem; color: #5b21b6;">Two Default Stock Roots Pending Creation</div>
                        <div style="font-size: 0.74rem; color: #6d28d9; margin-top: 3px;">
                            Saving this warehouse will automatically provision <strong>Stock</strong> (Physical Storage) and <strong>Virtual Stock</strong> (Virtual Counterpart).
                        </div>
                    </div>
                </div>
            `;
        } else {
            const defaultAreas = whAreas.filter(a => a.is_default);
            if (defaultAreas.length === 0) {
                defaultsEl.innerHTML = `<div class="wh-empty-subnote">No default locations are flagged for this warehouse. You can mark any location as Default in its settings.</div>`;
            } else {
                defaultsEl.innerHTML = `
                    <div class="wh-default-cards-list">
                        ${defaultAreas.map(a => {
                            const kind = inferLocationKind(a);
                            const units = getSubtreeStockQty(a.id);
                            return `
                                <div class="wh-default-row-card" role="button" tabindex="0" onclick="openStorageAreaModal({ areaId: '${escapeHtml(a.id)}' })">
                                    <div>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <i class="ph ${getStructuralIcon(a)} wh-loc-icon ${kind === 'Virtual' ? 'is-virtual' : ''}"></i>
                                            <strong style="color:#0f172a;font-size:0.84rem;">${escapeHtml(a.name)}</strong>
                                            <span class="wh-inline-code">${escapeHtml(a.code)}</span>
                                        </div>
                                        <div style="font-size:0.73rem;color:#64748b;margin-top:3px;">
                                            ${escapeHtml(kind)} &bull; ${escapeHtml(a.area_type)} &bull; Barcode: <code>${escapeHtml(a.barcode)}</code>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <span style="font-weight:700;font-size:0.82rem;color:#0f172a;font-variant-numeric:tabular-nums;">${units.toLocaleString()} units</span>
                                        <i class="ph ph-arrow-right" style="color:#7c3aed;"></i>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }
        }
    }

    const actionsEl = document.getElementById('whPageHierarchyActions');
    if (actionsEl) actionsEl.style.display = whId ? 'flex' : 'none';

    const hierEl = document.getElementById('whPageHierarchyList');
    if (hierEl) {
        if (!whId) {
            hierEl.innerHTML = `<div class="wh-empty-subnote" style="padding: 28px 20px;">Save the new warehouse first, then add zones, racks, bins, or virtual ledger locations.</div>`;
        } else if (whAreas.length === 0) {
            hierEl.innerHTML = `<div class="wh-empty-subnote" style="padding: 28px 20px;">This warehouse has no storage locations yet. Use the buttons above to create one.</div>`;
        } else {
            const ordered = [];
            function walk(parentId, depth) {
                whAreas.filter(a => (a.parent_id || null) === parentId).forEach(node => {
                    ordered.push({ node, depth });
                    walk(node.id, depth + 1);
                });
            }
            const rootIds = whAreas.filter(a => !a.parent_id || !whAreas.some(p => p.id === a.parent_id));
            rootIds.forEach(r => {
                ordered.push({ node: r, depth: 0 });
                walk(r.id, 1);
            });

            hierEl.innerHTML = `
                <div class="wh-subtable-wrap">
                    <table class="wh-subtable">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Type / Level</th>
                                <th>Behavior</th>
                                <th>Barcode</th>
                                <th class="wh-align-right">Stock (Units)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${ordered.map(({ node, depth }) => {
                                const kind = inferLocationKind(node);
                                const units = getSubtreeStockQty(node.id);
                                return `
                                    <tr class="wh-subtable-clickable" onclick="openStorageAreaModal({ areaId: '${escapeHtml(node.id)}' })">
                                        <td>
                                            <div style="display:flex;align-items:center;gap:6px;padding-left:${depth * 18}px;">
                                                <i class="ph ${getStructuralIcon(node)} wh-loc-icon ${kind === 'Virtual' ? 'is-virtual' : ''}"></i>
                                                <span style="font-weight:${depth === 0 ? '600' : '500'};color:#0f172a;">${escapeHtml(node.name)}</span>
                                                <span class="wh-inline-code">${escapeHtml(node.code)}</span>
                                                ${node.is_default ? '<span class="wh-default-tag">Default</span>' : ''}
                                            </div>
                                        </td>
                                        <td>${escapeHtml(node.area_type)}</td>
                                        <td><span class="wh-beh-indicator ${escapeHtml(node.behavior)}"><span class="wh-beh-dot"></span><span>${escapeHtml(node.behavior)}</span></span></td>
                                        <td><span class="wh-mono-barcode">${escapeHtml(node.barcode)}</span></td>
                                        <td class="wh-align-right" style="font-variant-numeric:tabular-nums;font-weight:${units > 0 ? '700' : '500'};color:${units > 0 ? '#0f172a' : '#94a3b8'};">${units.toLocaleString()}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }
}

function openStorageAreaFromWhPage(presetKind) {
    const whId = document.getElementById('whFormId')?.value.trim();
    if (!whId) {
        showWhToast('Save the warehouse before adding storage locations.', 'warning');
        return;
    }
    window.WhStore.selectedWarehouseId = whId;
    openStorageAreaModal({
        presetKind,
        presetType: presetKind === 'Virtual' ? 'Virtual Location' : 'Zone',
        presetBehavior: presetKind === 'Virtual' ? 'Virtual' : 'Storage',
    });
}

function syncWhHeaderOffset() {
    const headerEl = document.querySelector('.main-header');
    const offset = headerEl ? Math.max(56, Math.round(headerEl.getBoundingClientRect().bottom)) : 64;
    document.documentElement.style.setProperty('--rms-header-offset', `${offset}px`);
}

function closeActiveWhDrawers() {
    document.getElementById('whWarehouseModal')?.classList.remove('is-open');
    document.getElementById('whWarehouseModal')?.setAttribute('aria-hidden', 'true');
    document.getElementById('whStorageAreaModal')?.classList.remove('is-open');
    document.getElementById('whStorageAreaModal')?.setAttribute('aria-hidden', 'true');
    const backdrop = document.getElementById('whDrawerBackdrop');
    if (backdrop) {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
    }
}

function openWarehouseModal(whId = null) {
    const page = document.getElementById('whWarehouseModal');
    const areaPage = document.getElementById('whStorageAreaModal');
    const backdrop = document.getElementById('whDrawerBackdrop');
    const isEdit = Boolean(whId);
    const wh = isEdit ? window.WhStore.warehouses.find(w => w.id === whId) : null;

    syncWhHeaderOffset();
    if (wh) window.WhStore.selectedWarehouseId = wh.id;

    document.getElementById('whModalTitle').textContent = isEdit ? `Edit Warehouse — ${wh.name}` : 'Create Warehouse';
    document.getElementById('whFormId').value = wh ? wh.id : '';
    document.getElementById('whFormName').value = wh ? wh.name : '';
    document.getElementById('whFormCode').value = wh ? wh.code : '';
    document.getElementById('whFormAddress').value = wh ? wh.address : '';
    const mgrEl = document.getElementById('whFormManager');
    if (mgrEl) mgrEl.value = wh?.manager || '';
    const phoneEl = document.getElementById('whFormPhone');
    if (phoneEl) phoneEl.value = wh?.phone || '';
    const notesEl = document.getElementById('whFormNotes');
    if (notesEl) notesEl.value = wh?.notes || '';
    document.getElementById('whFormActive').checked = wh ? Boolean(wh.is_active) : true;

    const autoSeedBox = document.getElementById('whAutoSeedNoticeBox');
    if (autoSeedBox) autoSeedBox.style.display = isEdit ? 'none' : 'flex';

    const delBtn = document.getElementById('btnDeleteWhFromPage');
    if (delBtn) delBtn.style.display = isEdit ? 'inline-flex' : 'none';

    switchWhPageTab('general');
    updateWhPageLivePreview();
    renderWarehousePageSubTabs(wh ? wh.id : null);

    if (areaPage) {
        areaPage.classList.remove('is-open');
        areaPage.setAttribute('aria-hidden', 'true');
    }
    if (backdrop) {
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
    }
    if (page) {
        page.classList.add('is-open');
        page.setAttribute('aria-hidden', 'false');
        const body = page.querySelector('.wh-drawer-body');
        if (body) body.scrollTop = 0;
    }
    setTimeout(() => document.getElementById('whFormName')?.focus(), 50);
}

function closeWarehouseModal() {
    const whDrawer = document.getElementById('whWarehouseModal');
    whDrawer?.classList.remove('is-open');
    whDrawer?.setAttribute('aria-hidden', 'true');
    const areaOpen = document.getElementById('whStorageAreaModal')?.classList.contains('is-open');
    const backdrop = document.getElementById('whDrawerBackdrop');
    if (backdrop && !areaOpen) {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
    }
}

function deleteWarehouseFromPage() {
    const whId = document.getElementById('whFormId')?.value.trim();
    if (!whId) return;
    deleteWarehouse(whId);
    if (!window.WhStore.warehouses.some(w => w.id === whId)) {
        closeWarehouseModal();
    }
}

function deleteWarehouse(whId) {
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const whAreas = window.WhStore.storageAreas.filter(a => a.warehouse_id === whId);
    const totalUnits = whAreas.reduce((acc, a) => acc + Math.max(0, Number(a.stock_qty) || 0), 0);

    const msg = (whAreas.length > 0 || totalUnits > 0)
        ? `Delete warehouse "${wh.name}" (${wh.code}) together with its ${whAreas.length} storage location(s) and ${totalUnits.toLocaleString()} tracked unit(s)?\n\nThis removes the warehouse and all nested locations.`
        : `Delete warehouse "${wh.name}" (${wh.code})?`;

    if (!window.confirm(msg)) return;

    window.WhStore.warehouses = window.WhStore.warehouses.filter(w => w.id !== whId);
    window.WhStore.storageAreas = window.WhStore.storageAreas.filter(a => a.warehouse_id !== whId);

    if (window.WhStore.selectedWarehouseId === whId) {
        window.WhStore.selectedWarehouseId = window.WhStore.warehouses[0]?.id || null;
    }

    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    showWhToast(`Deleted warehouse "${wh.name}" (${wh.code}).`, 'success');
}

function saveWarehouseForm() {
    const editId = document.getElementById('whFormId').value.trim();
    const name = document.getElementById('whFormName').value.trim();
    const code = document.getElementById('whFormCode').value.trim().toUpperCase();
    const address = document.getElementById('whFormAddress').value.trim();
    const manager = document.getElementById('whFormManager')?.value.trim() || '';
    const phone = document.getElementById('whFormPhone')?.value.trim() || '';
    const notes = document.getElementById('whFormNotes')?.value.trim() || '';
    const isActive = document.getElementById('whFormActive').checked;

    if (!name || !code || !address) {
        showWhToast('Please fill in Warehouse Name, Short Code, and Physical Address.', 'danger');
        return;
    }

    const duplicateCode = window.WhStore.warehouses.find(w => w.code.toUpperCase() === code && w.id !== editId);
    if (duplicateCode) {
        showWhToast(`Warehouse short code "${code}" is already in use.`, 'danger');
        return;
    }

    if (editId) {
        const idx = window.WhStore.warehouses.findIndex(w => w.id === editId);
        if (idx >= 0) {
            window.WhStore.warehouses[idx] = {
                ...window.WhStore.warehouses[idx],
                name,
                code,
                address,
                manager,
                phone,
                notes,
                is_active: isActive,
            };
        }
        showWhToast(`Updated warehouse ${code}`, 'success');
    } else {
        const newWhId = code;
        const ts = Date.now();
        window.WhStore.warehouses.push({
            id: newWhId,
            code,
            name,
            address,
            manager,
            phone,
            notes,
            is_active: isActive,
            created_at: new Date().toISOString().split('T')[0],
        });

        // Automatically seed ONLY the default Stock locations: Physical Stock + Virtual Stock
        const defaultAreas = [
            {
                id: `AREA-${code}-STK-${ts}`,
                warehouse_id: newWhId,
                parent_id: null,
                location_kind: 'Physical',
                name: 'Stock',
                code: 'STOCK',
                area_type: 'Zone',
                behavior: 'Storage',
                barcode: `${code}-STOCK`,
                stock_qty: 0,
                is_active: true,
                is_default: true,
            },
            {
                id: `AREA-${code}-VSTK-${ts + 1}`,
                warehouse_id: newWhId,
                parent_id: null,
                location_kind: 'Virtual',
                name: 'Virtual Stock',
                code: 'VRT-STOCK',
                area_type: 'Virtual Location',
                behavior: 'Virtual',
                barcode: `${code}-VRT-STOCK`,
                stock_qty: 0,
                is_active: true,
                is_default: true,
            },
        ];
        window.WhStore.storageAreas.push(...defaultAreas);
        window.WhStore.selectedWarehouseId = newWhId;
        showWhToast(`Created warehouse ${code} with default Stock (Physical) & Virtual Stock locations!`, 'success');
    }

    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    closeWarehouseModal();
}

/**
 * Storage Location (Physical or Virtual) Create / Edit Full-Page Workspace
 */
function switchAreaPageTab(tabKey) {
    document.querySelectorAll('#whStorageAreaModal [data-area-tab]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.areaTab === tabKey);
    });
    document.querySelectorAll('#whStorageAreaModal .wh-page-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === `areaTabPane-${tabKey}`);
    });
}

function selectAreaKindCard(kindVal) {
    const kindSelect = document.getElementById('areaFormKind');
    if (kindSelect) kindSelect.value = kindVal;
    document.getElementById('kindCardPhysical')?.classList.toggle('active', kindVal !== 'Virtual');
    document.getElementById('kindCardVirtual')?.classList.toggle('active', kindVal === 'Virtual');
    handleAreaKindChange(kindVal);
    updateAreaPageLivePreview();
}

function updateAreaPageLivePreview() {
    const editId = document.getElementById('areaFormId')?.value.trim();
    const kind = document.getElementById('areaFormKind')?.value || 'Physical';
    const behavior = document.getElementById('areaFormBehavior')?.value || 'Storage';
    const name = document.getElementById('areaFormName')?.value.trim() || '';
    const code = (document.getElementById('areaFormCode')?.value.trim() || '').toUpperCase();
    const barcode = (document.getElementById('areaFormBarcode')?.value.trim() || '').toUpperCase();
    const isActive = Boolean(document.getElementById('areaFormActive')?.checked);
    const directQty = Math.max(0, Math.round(parseFloat(document.getElementById('areaFormStockQty')?.value) || 0));

    document.getElementById('kindCardPhysical')?.classList.toggle('active', kind !== 'Virtual');
    document.getElementById('kindCardVirtual')?.classList.toggle('active', kind === 'Virtual');

    const heroIcon = document.getElementById('areaPageHeroIcon');
    if (heroIcon) {
        heroIcon.innerHTML = `<i class="ph ${kind === 'Virtual' ? 'ph-cloud' : 'ph-tree-structure'}"></i>`;
        heroIcon.classList.toggle('is-virtual', kind === 'Virtual');
    }

    const codeChip = document.getElementById('areaPageCodeChip');
    if (codeChip) codeChip.textContent = code || 'NEW';

    const kindPill = document.getElementById('areaPageKindPill');
    if (kindPill) {
        kindPill.textContent = kind;
        kindPill.className = `wh-kind-tag ${kind === 'Virtual' ? 'virtual' : 'physical'}`;
    }

    const behPill = document.getElementById('areaPageBehaviorPill');
    if (behPill) {
        behPill.textContent = behavior;
        behPill.className = `wh-beh-tag ${behavior}`;
    }

    const statusPill = document.getElementById('areaPageStatusPill');
    if (statusPill) {
        statusPill.className = `wh-status-pill ${isActive ? 'active' : 'inactive'}`;
        statusPill.textContent = isActive ? 'Active' : 'Inactive';
    }

    const barcodePreview = document.getElementById('areaBarcodePreviewText');
    if (barcodePreview) barcodePreview.textContent = barcode || 'BARCODE-PENDING';

    if (!editId) {
        const titleEl = document.getElementById('areaModalTitle');
        if (titleEl) titleEl.textContent = name ? `${kind} Location — ${name}` : `Add ${kind} Location`;
    }

    const editArea = editId ? window.WhStore.storageAreas.find(a => a.id === editId) : null;
    const oldOwn = editArea ? Math.max(0, Number(editArea.stock_qty) || 0) : 0;
    const nestedQty = editArea ? Math.max(0, getSubtreeStockQty(editArea.id) - oldOwn) : 0;
    const totalQty = directQty + nestedQty;

    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    setTxt('areaSummaryDirectStock', directQty.toLocaleString());
    setTxt('areaSummaryNestedStock', nestedQty.toLocaleString());
    setTxt('areaSummaryTotalStock', totalQty.toLocaleString());
}

function renderAreaPageChildrenTab(editArea) {
    const countEl = document.getElementById('areaPageChildrenTabCount');
    const listEl = document.getElementById('areaPageChildrenList');
    const tabAddBtn = document.getElementById('btnTabAddChildArea');
    const allowChild = editArea ? canLocationHaveChildren(editArea) : false;

    if (tabAddBtn) tabAddBtn.style.display = (editArea && allowChild) ? 'inline-flex' : 'none';

    if (!editArea) {
        if (countEl) countEl.textContent = '0';
        if (listEl) {
            listEl.innerHTML = `<div class="wh-empty-subnote" style="padding: 28px 20px;">Save this location first to attach nested racks, shelves, bins, or virtual sub-locations.</div>`;
        }
        return;
    }

    const directChildren = window.WhStore.storageAreas.filter(a => a.parent_id === editArea.id);
    if (countEl) countEl.textContent = directChildren.length;

    if (!listEl) return;

    if (directChildren.length === 0) {
        listEl.innerHTML = `<div class="wh-empty-subnote" style="padding: 28px 20px;">${allowChild ? 'No child locations nested inside this area yet.' : `${escapeHtml(editArea.area_type)} is a leaf level and does not hold sub-locations.`}</div>`;
        return;
    }

    listEl.innerHTML = `
        <div class="wh-subtable-wrap">
            <table class="wh-subtable">
                <thead>
                    <tr>
                        <th>Child Location</th>
                        <th>Type / Level</th>
                        <th>Behavior</th>
                        <th>Barcode</th>
                        <th class="wh-align-right">Stock (Units)</th>
                    </tr>
                </thead>
                <tbody>
                    ${directChildren.map(ch => {
                        const kind = inferLocationKind(ch);
                        const units = getSubtreeStockQty(ch.id);
                        return `
                            <tr class="wh-subtable-clickable" onclick="openStorageAreaModal({ areaId: '${escapeHtml(ch.id)}' })">
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <i class="ph ${getStructuralIcon(ch)} wh-loc-icon ${kind === 'Virtual' ? 'is-virtual' : ''}"></i>
                                        <span style="font-weight:600;color:#0f172a;">${escapeHtml(ch.name)}</span>
                                        <span class="wh-inline-code">${escapeHtml(ch.code)}</span>
                                    </div>
                                </td>
                                <td>${escapeHtml(ch.area_type)}</td>
                                <td><span class="wh-beh-indicator ${escapeHtml(ch.behavior)}"><span class="wh-beh-dot"></span><span>${escapeHtml(ch.behavior)}</span></span></td>
                                <td><span class="wh-mono-barcode">${escapeHtml(ch.barcode)}</span></td>
                                <td class="wh-align-right" style="font-variant-numeric:tabular-nums;font-weight:${units > 0 ? '700' : '500'};color:${units > 0 ? '#0f172a' : '#94a3b8'};">${units.toLocaleString()}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function addChildFromAreaPage() {
    const areaId = document.getElementById('areaFormId')?.value.trim();
    const area = areaId ? window.WhStore.storageAreas.find(a => a.id === areaId) : null;
    if (!area) return;
    openStorageAreaModal({ parentId: area.id, presetKind: inferLocationKind(area) });
}

function deleteAreaFromPage() {
    const areaId = document.getElementById('areaFormId')?.value.trim();
    if (!areaId) return;
    deleteStorageArea(areaId);
    if (!window.WhStore.storageAreas.some(a => a.id === areaId)) {
        closeStorageAreaModal();
    }
}

function openStorageAreaModal(opts = {}) {
    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const targetAreaId = opts.areaId || opts.editId || '';
    const editArea = targetAreaId ? window.WhStore.storageAreas.find(a => a.id === targetAreaId) : null;
    const parentObj = opts.parentId ? window.WhStore.storageAreas.find(a => a.id === opts.parentId) : null;

    const locKind = editArea
        ? inferLocationKind(editArea)
        : (opts.presetKind || (parentObj ? inferLocationKind(parentObj) : 'Physical'));

    document.getElementById('areaModalTitle').textContent = editArea
        ? `${editArea.name}`
        : `Add ${locKind} Location`;
    document.getElementById('areaFormId').value = editArea ? editArea.id : '';
    document.getElementById('areaFormWarehouse').value = `${wh.code} — ${wh.name}`;

    // Build Breadcrumb
    const bcEl = document.getElementById('areaPageBreadcrumb');
    if (bcEl) {
        const trail = [];
        let cursor = editArea ? window.WhStore.storageAreas.find(a => a.id === editArea.parent_id) : parentObj;
        let guard = 0;
        while (cursor && guard < 12) {
            trail.unshift(cursor.name);
            cursor = cursor.parent_id ? window.WhStore.storageAreas.find(a => a.id === cursor.parent_id) : null;
            guard++;
        }
        const parts = [wh.code, ...trail, editArea ? editArea.name : `New ${locKind} Location`];
        bcEl.innerHTML = parts.map((p, idx) =>
            idx === parts.length - 1 ? `<strong>${escapeHtml(p)}</strong>` : `<span>${escapeHtml(p)}</span><i class="ph ph-caret-right"></i>`
        ).join('');
    }

    // Populate valid Parent Areas (same warehouse, excluding self & descendants to prevent cycles)
    const parentSelect = document.getElementById('areaFormParent');
    const whAreas = window.WhStore.storageAreas.filter(a => a.warehouse_id === whId);
    const validParents = whAreas.filter(a => !editArea || !isDescendantOrSelf(editArea.id, a.id));

    const targetParentId = editArea ? (editArea.parent_id || '') : (opts.parentId || '');
    parentSelect.innerHTML = `<option value="">-- Top-Level Root Location in ${escapeHtml(wh.code)} --</option>` +
        validParents.map(p => `
            <option value="${escapeHtml(p.id)}" ${p.id === targetParentId ? 'selected' : ''}>
                [${escapeHtml(inferLocationKind(p))} • ${escapeHtml(p.area_type)}] ${escapeHtml(p.name)} (${escapeHtml(p.code)})
            </option>
        `).join('');

    const defaultType = locKind === 'Virtual' ? 'Virtual Location' : (opts.parentId ? 'Bin' : 'Zone');
    const areaType = editArea ? editArea.area_type : (opts.presetType || defaultType);
    const defaultBeh = locKind === 'Virtual'
        ? 'Virtual'
        : (areaType === 'Dock Door' ? 'Dock' : (areaType === 'Packing Table' ? 'Staging' : 'Storage'));
    const behavior = editArea ? editArea.behavior : (opts.presetBehavior || defaultBeh);

    const kindSelect = document.getElementById('areaFormKind');
    if (kindSelect) kindSelect.value = locKind;
    document.getElementById('areaFormType').value = areaType;
    document.getElementById('areaFormBehavior').value = behavior;
    document.getElementById('areaFormName').value = editArea ? editArea.name : '';
    document.getElementById('areaFormCode').value = editArea ? editArea.code : '';
    document.getElementById('areaFormBarcode').value = editArea ? editArea.barcode : '';
    document.getElementById('areaFormStockQty').value = editArea ? Math.max(0, Number(editArea.stock_qty) || 0) : 0;
    document.getElementById('areaFormActive').checked = editArea ? Boolean(editArea.is_active) : true;

    const defaultChk = document.getElementById('areaFormDefault');
    if (defaultChk) defaultChk.checked = editArea ? Boolean(editArea.is_default) : false;
    const capEl = document.getElementById('areaFormMaxCapacity');
    if (capEl) capEl.value = editArea && editArea.max_capacity != null ? editArea.max_capacity : '';
    const tempEl = document.getElementById('areaFormTempZone');
    if (tempEl) tempEl.value = editArea?.temp_zone || (locKind === 'Virtual' ? 'Virtual / N/A' : 'Ambient / Dry');
    const pickEl = document.getElementById('areaFormPickStrategy');
    if (pickEl) pickEl.value = editArea?.pick_strategy || 'FEFO';
    const notesEl = document.getElementById('areaFormNotes');
    if (notesEl) notesEl.value = editArea?.notes || '';

    const delBtn = document.getElementById('btnDeleteAreaFromPage');
    if (delBtn) delBtn.style.display = editArea ? 'inline-flex' : 'none';
    const addChildBtn = document.getElementById('btnAddChildFromAreaPage');
    if (addChildBtn) addChildBtn.style.display = (editArea && canLocationHaveChildren(editArea)) ? 'inline-flex' : 'none';

    if (!editArea) {
        autoSuggestAreaBarcode();
    }

    switchAreaPageTab('general');
    updateAreaPageLivePreview();
    renderAreaPageChildrenTab(editArea);

    syncWhHeaderOffset();
    const whPage = document.getElementById('whWarehouseModal');
    const areaPage = document.getElementById('whStorageAreaModal');
    const backdrop = document.getElementById('whDrawerBackdrop');
    if (whPage) {
        whPage.classList.remove('is-open');
        whPage.setAttribute('aria-hidden', 'true');
    }
    if (backdrop) {
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
    }
    if (areaPage) {
        areaPage.classList.add('is-open');
        areaPage.setAttribute('aria-hidden', 'false');
        const body = areaPage.querySelector('.wh-drawer-body');
        if (body) body.scrollTop = 0;
    }
    setTimeout(() => document.getElementById('areaFormName')?.focus(), 50);
}

function closeStorageAreaModal() {
    const areaPage = document.getElementById('whStorageAreaModal');
    areaPage?.classList.remove('is-open');
    areaPage?.setAttribute('aria-hidden', 'true');
    const whOpen = document.getElementById('whWarehouseModal')?.classList.contains('is-open');
    const backdrop = document.getElementById('whDrawerBackdrop');
    if (backdrop && !whOpen) {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
    }
}

function handleAreaKindChange(kindVal) {
    const typeSelect = document.getElementById('areaFormType');
    const behSelect = document.getElementById('areaFormBehavior');
    const titleEl = document.getElementById('areaModalTitle');
    const editId = document.getElementById('areaFormId')?.value;

    document.getElementById('kindCardPhysical')?.classList.toggle('active', kindVal !== 'Virtual');
    document.getElementById('kindCardVirtual')?.classList.toggle('active', kindVal === 'Virtual');

    if (kindVal === 'Virtual') {
        if (typeSelect && !String(typeSelect.value).startsWith('Virtual')) {
            typeSelect.value = 'Virtual Location';
        }
        if (behSelect) behSelect.value = 'Virtual';
        if (titleEl && !editId) titleEl.textContent = 'Add Virtual Location';
    } else {
        if (typeSelect && String(typeSelect.value).startsWith('Virtual')) {
            typeSelect.value = 'Zone';
        }
        if (behSelect && behSelect.value === 'Virtual') {
            behSelect.value = 'Storage';
        }
        if (titleEl && !editId) titleEl.textContent = 'Add Physical Location';
    }
    autoSuggestAreaBarcode();
    updateAreaPageLivePreview();
}

function handleAreaTypeChange(typeVal) {
    const kindSelect = document.getElementById('areaFormKind');
    const behSelect = document.getElementById('areaFormBehavior');

    if (String(typeVal).startsWith('Virtual')) {
        if (kindSelect) kindSelect.value = 'Virtual';
        if (behSelect) behSelect.value = 'Virtual';
    } else if (typeVal === 'Dock Door') {
        if (kindSelect) kindSelect.value = 'Physical';
        if (behSelect) behSelect.value = 'Dock';
    } else if (typeVal === 'Packing Table') {
        if (kindSelect) kindSelect.value = 'Physical';
        if (behSelect) behSelect.value = 'Staging';
    } else {
        if (kindSelect) kindSelect.value = 'Physical';
        if (behSelect) behSelect.value = 'Storage';
    }
    const kindNow = kindSelect?.value || 'Physical';
    document.getElementById('kindCardPhysical')?.classList.toggle('active', kindNow !== 'Virtual');
    document.getElementById('kindCardVirtual')?.classList.toggle('active', kindNow === 'Virtual');
    autoSuggestAreaBarcode();
    updateAreaPageLivePreview();
}

function autoSuggestAreaBarcode(force = false) {
    const editId = document.getElementById('areaFormId').value;
    if (editId && !force) return; // Don't overwrite existing barcode on edit unless user explicitly clicks Auto

    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const parentId = document.getElementById('areaFormParent').value;
    const parent = parentId ? window.WhStore.storageAreas.find(a => a.id === parentId) : null;
    const codeInput = document.getElementById('areaFormCode');
    const nameVal = document.getElementById('areaFormName').value.trim();
    const kindVal = document.getElementById('areaFormKind')?.value || 'Physical';

    if ((!codeInput.value || force) && nameVal) {
        const prefix = kindVal === 'Virtual' && !nameVal.toUpperCase().startsWith('VRT') ? 'VRT-' : '';
        if (!codeInput.value) {
            codeInput.value = (prefix + nameVal.toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-|-$/g, '')).slice(0, 14);
        }
    }

    const shortCode = (codeInput.value || (kindVal === 'Virtual' ? 'VRT-LOC' : 'LOC')).toUpperCase();
    const basePrefix = parent ? parent.barcode : wh.code;
    document.getElementById('areaFormBarcode').value = `${basePrefix}-${shortCode}`.replace(/--+/g, '-');
    updateAreaPageLivePreview();
}

function saveStorageAreaForm() {
    const whId = window.WhStore.selectedWarehouseId;
    const editId = document.getElementById('areaFormId').value.trim();
    const parentId = document.getElementById('areaFormParent').value || null;
    const locationKind = document.getElementById('areaFormKind')?.value || 'Physical';
    const areaType = document.getElementById('areaFormType').value;
    const behavior = document.getElementById('areaFormBehavior').value;
    const name = document.getElementById('areaFormName').value.trim();
    const code = document.getElementById('areaFormCode').value.trim().toUpperCase();
    const barcode = document.getElementById('areaFormBarcode').value.trim().toUpperCase();
    const rawStock = parseFloat(document.getElementById('areaFormStockQty').value);
    const isActive = document.getElementById('areaFormActive').checked;
    const isDefault = Boolean(document.getElementById('areaFormDefault')?.checked);
    const capRaw = document.getElementById('areaFormMaxCapacity')?.value;
    const maxCapacity = capRaw !== undefined && capRaw !== '' && !isNaN(Number(capRaw)) ? Math.max(0, Math.round(Number(capRaw))) : null;
    const tempZone = document.getElementById('areaFormTempZone')?.value || (locationKind === 'Virtual' ? 'Virtual / N/A' : 'Ambient / Dry');
    const pickStrategy = document.getElementById('areaFormPickStrategy')?.value || 'FEFO';
    const notes = document.getElementById('areaFormNotes')?.value.trim() || '';

    if (!name || !code || !barcode) {
        showWhToast('Location Name, Location Code, and Unique Scanner Barcode are required.', 'danger');
        return;
    }

    // Rule 1: Non-negative stock quantity only
    if (isNaN(rawStock) || rawStock < 0) {
        showWhToast('Locations can never hold negative stock. Enter 0 or a positive quantity.', 'danger');
        return;
    }

    // Rule 2: No cycles in the tree
    if (editId && parentId && isDescendantOrSelf(editId, parentId)) {
        showWhToast('Invalid parent selection: a location cannot be nested inside itself or its own child.', 'danger');
        return;
    }

    // Rule 3: Unique barcode check
    const dupBarcode = window.WhStore.storageAreas.find(a => a.barcode.toUpperCase() === barcode && a.id !== editId);
    if (dupBarcode) {
        showWhToast(`Barcode "${barcode}" is already assigned to "${dupBarcode.name}". Every location must have a unique barcode.`, 'danger');
        return;
    }

    if (editId) {
        const idx = window.WhStore.storageAreas.findIndex(a => a.id === editId);
        if (idx >= 0) {
            window.WhStore.storageAreas[idx] = {
                ...window.WhStore.storageAreas[idx],
                parent_id: parentId,
                location_kind: locationKind,
                area_type: areaType,
                behavior,
                name,
                code,
                barcode,
                stock_qty: Math.round(rawStock),
                is_active: isActive,
                is_default: isDefault,
                max_capacity: maxCapacity,
                temp_zone: tempZone,
                pick_strategy: pickStrategy,
                notes,
            };
        }
        showWhToast(`Updated ${locationKind} location "${name}" [${barcode}]`, 'success');
    } else {
        const newArea = {
            id: `AREA-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
            warehouse_id: whId,
            parent_id: parentId,
            location_kind: locationKind,
            name,
            code,
            area_type: areaType,
            behavior,
            barcode,
            stock_qty: Math.round(rawStock),
            is_active: isActive,
            is_default: isDefault,
            max_capacity: maxCapacity,
            temp_zone: tempZone,
            pick_strategy: pickStrategy,
            notes,
        };
        window.WhStore.storageAreas.push(newArea);
        if (parentId) {
            window.WhStore.collapsedNodeIds.delete(parentId);
        }
        showWhToast(`Added ${locationKind} ${areaType} "${name}" with barcode ${barcode}`, 'success');
    }

    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    closeStorageAreaModal();
}

function toggleAreaActiveStatus(areaId) {
    const area = window.WhStore.storageAreas.find(a => a.id === areaId);
    if (!area) return;
    area.is_active = !area.is_active;
    persistWarehouseState();
    renderSelectedWarehouseTree();
    showWhToast(`${area.name} (${area.barcode}) ${area.is_active ? 'activated' : 'deactivated'}.`, 'success');
}

/**
 * Delete Storage Location (Physical or Virtual)
 * Allows deleting empty, nested, default, or stocked locations (with confirmation when stocked/nested/default).
 */
function deleteStorageArea(areaId) {
    const area = window.WhStore.storageAreas.find(a => a.id === areaId);
    if (!area) return;

    const toRemove = new Set();
    function collect(id) {
        toRemove.add(id);
        window.WhStore.storageAreas.filter(a => a.parent_id === id).forEach(ch => collect(ch.id));
    }
    collect(areaId);

    const subtreeStock = getSubtreeStockQty(areaId);
    const childCount = toRemove.size - 1;

    if (subtreeStock > 0 || childCount > 0 || area.is_default) {
        const details = [];
        if (subtreeStock > 0) details.push(`${subtreeStock.toLocaleString()} unit(s) on hand`);
        if (childCount > 0) details.push(`${childCount} nested sub-location(s)`);
        if (area.is_default) details.push(`is a default warehouse location`);
        const ok = window.confirm(`Delete "${area.name}" (${area.code})?\n\nThis location ${details.join(' and ')} and will remove the location from this warehouse.`);
        if (!ok) return;
    }

    window.WhStore.storageAreas = window.WhStore.storageAreas.filter(a => !toRemove.has(a.id));
    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    showWhToast(`Deleted location "${area.name}".`, 'success');
}

/**
 * Bulk Bin Generator ("Aisle A, Racks 1-10, Bins 1-5" -> 50 Barcoded Bins)
 */
function openBulkGeneratorModal() {
    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const whZones = window.WhStore.storageAreas.filter(a =>
        a.warehouse_id === whId && (a.area_type === 'Zone' || a.area_type === 'Aisle' || !a.parent_id)
    );

    const parentSelect = document.getElementById('bulkParentZoneSelect');
    parentSelect.innerHTML = whZones.map(z => `
        <option value="${escapeHtml(z.id)}" ${z.area_type === 'Zone' ? 'selected' : ''}>
            [${escapeHtml(z.area_type)}] ${escapeHtml(z.name)} (${escapeHtml(z.barcode)})
        </option>
    `).join('');

    updateBulkGeneratorPreview();
    document.getElementById('whBulkGeneratorModal')?.classList.add('is-open');
}

function closeBulkGeneratorModal() {
    document.getElementById('whBulkGeneratorModal')?.classList.remove('is-open');
}

function updateBulkGeneratorPreview() {
    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const aisleCode = (document.getElementById('bulkAisleCode')?.value || 'A').trim().toUpperCase();
    const rStart = Math.max(1, parseInt(document.getElementById('bulkRackStart')?.value) || 1);
    const rEnd = Math.max(rStart, Math.min(rStart + 30, parseInt(document.getElementById('bulkRackEnd')?.value) || 10));
    const bStart = Math.max(1, parseInt(document.getElementById('bulkBinStart')?.value) || 1);
    const bEnd = Math.max(bStart, Math.min(bStart + 30, parseInt(document.getElementById('bulkBinEnd')?.value) || 5));

    const rackCount = (rEnd - rStart) + 1;
    const binsPerRack = (bEnd - bStart) + 1;
    const totalBins = rackCount * binsPerRack;

    document.getElementById('bulkPreviewTotalBadge').textContent = `${totalBins} Bins across ${rackCount} Racks`;
    document.getElementById('bulkPreviewSummaryText').innerHTML =
        `In <strong>${escapeHtml(wh.code)}</strong>: Creates <strong>Aisle ${escapeHtml(aisleCode)}</strong> → <strong>${rackCount} Racks (${rStart}–${rEnd})</strong> → <strong>${totalBins} Storage Bins (${bStart}–${bEnd} per rack)</strong>:`;

    const samples = [];
    for (let r = rStart; r <= rEnd && samples.length < 12; r++) {
        for (let b = bStart; b <= bEnd && samples.length < 12; b++) {
            const rPad = String(r).padStart(2, '0');
            const bPad = String(b).padStart(2, '0');
            samples.push(`${wh.code}-A${aisleCode}-R${rPad}-B${bPad}`);
        }
    }

    const listEl = document.getElementById('bulkPreviewBarcodesList');
    if (listEl) {
        listEl.innerHTML = samples.map(code => `<span class="wh-sample-barcode-pill">${escapeHtml(code)}</span>`).join('') +
            (totalBins > samples.length ? `<span class="wh-sample-barcode-pill">+ ${totalBins - samples.length} more...</span>` : '');
    }
}

function executeBulkBinGeneration() {
    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const parentZoneId = document.getElementById('bulkParentZoneSelect')?.value || null;
    const aisleCode = (document.getElementById('bulkAisleCode')?.value || 'A').trim().toUpperCase();
    const rStart = Math.max(1, parseInt(document.getElementById('bulkRackStart')?.value) || 1);
    const rEnd = Math.max(rStart, Math.min(rStart + 30, parseInt(document.getElementById('bulkRackEnd')?.value) || 10));
    const bStart = Math.max(1, parseInt(document.getElementById('bulkBinStart')?.value) || 1);
    const bEnd = Math.max(bStart, Math.min(bStart + 30, parseInt(document.getElementById('bulkBinEnd')?.value) || 5));

    const existingBarcodes = new Set(window.WhStore.storageAreas.map(a => a.barcode.toUpperCase()));
    const nowTs = Date.now();

    // 1. Find or create Aisle node under Parent Zone
    const aisleBarcode = `${wh.code}-AISLE-${aisleCode}`;
    let aisleNode = window.WhStore.storageAreas.find(a => a.warehouse_id === whId && a.barcode.toUpperCase() === aisleBarcode);
    if (!aisleNode) {
        aisleNode = {
            id: `AREA-AISLE-${aisleCode}-${nowTs}`,
            warehouse_id: whId,
            parent_id: parentZoneId,
            name: `Aisle ${aisleCode}`,
            code: `AISLE-${aisleCode}`,
            area_type: 'Aisle',
            behavior: 'Storage',
            barcode: aisleBarcode,
            stock_qty: 0,
            is_active: true,
            is_default: false,
        };
        window.WhStore.storageAreas.push(aisleNode);
    }

    let createdRacks = 0;
    let createdBins = 0;
    let firstGeneratedBarcode = null;

    for (let r = rStart; r <= rEnd; r++) {
        const rPad = String(r).padStart(2, '0');
        const rackBarcode = `${wh.code}-A${aisleCode}-R${rPad}`;
        let rackNode = window.WhStore.storageAreas.find(a => a.warehouse_id === whId && a.barcode.toUpperCase() === rackBarcode);

        if (!rackNode) {
            rackNode = {
                id: `AREA-RACK-${aisleCode}-${rPad}-${nowTs}-${r}`,
                warehouse_id: whId,
                parent_id: aisleNode.id,
                name: `Rack ${aisleCode}-${rPad}`,
                code: `RACK-${aisleCode}${rPad}`,
                location_kind: 'Physical',
                area_type: 'Rack',
                behavior: 'Storage',
                barcode: rackBarcode,
                stock_qty: 0,
                is_active: true,
                is_default: false,
            };
            window.WhStore.storageAreas.push(rackNode);
            createdRacks++;
        }

        for (let b = bStart; b <= bEnd; b++) {
            const bPad = String(b).padStart(2, '0');
            const binBarcode = `${wh.code}-A${aisleCode}-R${rPad}-B${bPad}`;
            if (!existingBarcodes.has(binBarcode.toUpperCase())) {
                window.WhStore.storageAreas.push({
                    id: `AREA-BIN-${aisleCode}-${rPad}-${bPad}-${nowTs}-${r}-${b}`,
                    warehouse_id: whId,
                    parent_id: rackNode.id,
                    location_kind: 'Physical',
                    name: `Bin ${aisleCode}${rPad}-${bPad}`,
                    code: `BIN-${aisleCode}${rPad}-${bPad}`,
                    area_type: 'Bin',
                    behavior: 'Storage',
                    barcode: binBarcode,
                    stock_qty: 0,
                    is_active: true,
                    is_default: false,
                });
                existingBarcodes.add(binBarcode.toUpperCase());
                createdBins++;
                if (!firstGeneratedBarcode) firstGeneratedBarcode = binBarcode;
            }
        }
    }

    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    closeBulkGeneratorModal();

    showWhToast(`Generated ${createdBins} barcoded Bins across ${createdRacks} Racks in ${wh.code}!`, 'success');
    if (firstGeneratedBarcode) {
        const input = document.getElementById('whTreeSearchInput');
        if (input) {
            input.value = firstGeneratedBarcode;
            renderSelectedWarehouseTree();
        }
    }
}

// Expose functions globally for inline handlers
Object.assign(window, {
    openWarehouseModal,
    closeWarehouseModal,
    closeActiveWhDrawers,
    switchWhPageTab,
    updateWhPageLivePreview,
    openStorageAreaFromWhPage,
    deleteWarehouseFromPage,
    deleteWarehouse,
    saveWarehouseForm,
    selectWarehouse,
    setAreaBehaviorFilter,
    toggleHoldsStockFilter,
    toggleWhRowActionMenu,
    closeWhRowActionMenu,
    renderWarehouseList,
    renderSelectedWarehouseTree,
    toggleTreeNode,
    expandOrCollapseAllTree,
    copyAreaBarcode,
    triggerBarcodeScanLookup,
    handleSmartLocationSearchKey,
    simulateBarcodeScan,
    openStorageAreaModal,
    closeStorageAreaModal,
    switchAreaPageTab,
    selectAreaKindCard,
    updateAreaPageLivePreview,
    addChildFromAreaPage,
    deleteAreaFromPage,
    handleAreaKindChange,
    handleAreaTypeChange,
    autoSuggestAreaBarcode,
    saveStorageAreaForm,
    toggleAreaActiveStatus,
    deleteStorageArea,
    openBulkGeneratorModal,
    closeBulkGeneratorModal,
    updateBulkGeneratorPreview,
    executeBulkBinGeneration,
    toggleWhColSettingsDropdown,
    toggleWhLocationColumn,
    moveWhLocationColumn,
    startWhColResize,
    resetWhLocationColumns,
    selectAllWhLocationColumns,
});

document.addEventListener('DOMContentLoaded', () => {
    hydrateWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    window.addEventListener('resize', syncWhHeaderOffset);

    document.addEventListener('click', (e) => {
        const dd = document.getElementById('whColSettingsDropdown');
        if (dd && dd.classList.contains('is-active') && !e.target.closest('#whColSettingsDropdown') && !e.target.closest('.rfq-th-settings-btn')) {
            dd.classList.remove('is-active');
            dd.style.display = 'none';
        }
        const rowMenu = document.getElementById('whRowActionMenu');
        if (rowMenu && rowMenu.classList.contains('is-open') && !e.target.closest('#whRowActionMenu') && !e.target.closest('.wh-row-menu-btn')) {
            closeWhRowActionMenu();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeWhRowActionMenu();
            closeActiveWhDrawers();
        }
    });
});

