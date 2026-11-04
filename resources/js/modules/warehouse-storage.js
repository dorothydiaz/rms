/**
 * ==========================================================================
 * WAREHOUSE & STORAGE AREA ENGINE (INVENTORY CONFIGURATION MODULE 1)
 * ==========================================================================
 */

const WH_STORAGE_KEY = 'rms_inv_warehouses_v1';
const AREA_STORAGE_KEY = 'rms_inv_storage_areas_v1';

window.WhStore = {
    warehouses: [],
    storageAreas: [],
    boundaryAreas: [],
    selectedWarehouseId: 'WH-MAIN',
    behaviorFilter: 'ALL',
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

    window.WhStore.storageAreas = Array.isArray(savedAreas) && savedAreas.length > 0
        ? savedAreas
        : (cfg.initialStorageAreas || []);

    window.WhStore.boundaryAreas = cfg.initialBoundaryAreas || [];

    if (window.WhStore.warehouses.length > 0 && !window.WhStore.warehouses.some(w => w.id === window.WhStore.selectedWarehouseId)) {
        window.WhStore.selectedWarehouseId = window.WhStore.warehouses[0].id;
    }

    persistWarehouseState();
}

function persistWarehouseState() {
    try {
        localStorage.setItem(WH_STORAGE_KEY, JSON.stringify(window.WhStore.warehouses));
        localStorage.setItem(AREA_STORAGE_KEY, JSON.stringify(window.WhStore.storageAreas));
    } catch (e) {}
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
    const areas = window.WhStore.storageAreas; // strictly physical areas

    const activeWhCount = whs.filter(w => w.is_active).length;
    const storageCount = areas.filter(a => a.behavior === 'Storage').length;
    const stagingCount = areas.filter(a => a.behavior === 'Staging').length;
    const dockCount = areas.filter(a => a.behavior === 'Dock').length;
    const binRackCount = areas.filter(a => a.area_type === 'Bin' || a.area_type === 'Rack').length;
    const stockedAreas = areas.filter(a => (Number(a.stock_qty) || 0) > 0);
    const totalStockUnits = stockedAreas.reduce((acc, a) => acc + (Number(a.stock_qty) || 0), 0);

    document.getElementById('whKpiActiveWarehouses').textContent = `${activeWhCount} / ${whs.length}`;
    document.getElementById('whKpiTotalAreas').textContent = areas.length;
    document.getElementById('whKpiBehaviorBreakdownSub').textContent = `${storageCount} Storage • ${stagingCount} Staging • ${dockCount} Dock`;
    document.getElementById('whKpiBarcodedBins').textContent = binRackCount;
    document.getElementById('whKpiStockedAreas').textContent = stockedAreas.length;
    document.getElementById('whKpiStockedUnitsSub').textContent = `${totalStockUnits.toLocaleString()} physical units protected`;

    // Sample Barcodes for quick 1-click scanner testing
    const samplesEl = document.getElementById('whScannerSamplePills');
    if (samplesEl) {
        const sampleAreas = areas.slice(0, 4);
        samplesEl.innerHTML = `<span style="font-size: 11px; color: #64748b; font-weight: 700;">Quick Scan Test:</span>` +
            sampleAreas.map(a => `
                <button type="button" class="wh-sample-barcode-pill" onclick="simulateBarcodeScan('${escapeHtml(a.barcode)}')" title="Click to simulate handheld barcode scan for ${escapeHtml(a.name)}">
                    <i class="ph ph-barcode"></i> ${escapeHtml(a.barcode)}
                </button>
            `).join('');
    }

    // Boundary areas reference list
    const bndEl = document.getElementById('whBoundaryPillsList');
    if (bndEl) {
        bndEl.innerHTML = (window.WhStore.boundaryAreas || []).map(b => `
            <div class="wh-boundary-pill" title="${escapeHtml(b.description)}">
                <div class="wh-boundary-pill-name">${escapeHtml(b.name)}</div>
                <div class="wh-boundary-pill-code">${escapeHtml(b.barcode)} • No Balance</div>
            </div>
        `).join('');
    }
}

function renderWarehouseList() {
    const query = (document.getElementById('whDirectorySearch')?.value || '').trim().toLowerCase();
    const whs = window.WhStore.warehouses;
    const areas = window.WhStore.storageAreas;

    document.getElementById('whListCountBadge').textContent = whs.length;

    const filtered = whs.filter(w => {
        if (!query) return true;
        return (w.code || '').toLowerCase().includes(query) ||
               (w.name || '').toLowerCase().includes(query) ||
               (w.address || '').toLowerCase().includes(query);
    });

    const container = document.getElementById('whWarehouseListContainer');
    if (!container) return;

    container.innerHTML = filtered.map(w => {
        const whAreas = areas.filter(a => a.warehouse_id === w.id);
        const binsCount = whAreas.filter(a => a.area_type === 'Bin').length;
        const totalUnits = whAreas.reduce((acc, a) => acc + (Number(a.stock_qty) || 0), 0);
        const isSelected = w.id === window.WhStore.selectedWarehouseId;

        return `
            <div class="wh-item-card ${isSelected ? 'active' : ''}" onclick="selectWarehouse('${escapeHtml(w.id)}')">
                <div class="wh-item-top">
                    <span class="wh-code-badge">${escapeHtml(w.code)}</span>
                    <div class="wh-item-actions" onclick="event.stopPropagation()">
                        <span class="wh-status-pill ${w.is_active ? 'active' : 'inactive'}">${w.is_active ? 'Active' : 'Inactive'}</span>
                        <button type="button" class="wh-node-btn" onclick="openWarehouseModal('${escapeHtml(w.id)}')" title="Edit Warehouse">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                    </div>
                </div>
                <div class="wh-item-name">${escapeHtml(w.name)}</div>
                <div class="wh-item-addr"><i class="ph ph-map-pin"></i> <span>${escapeHtml(w.address)}</span></div>
                <div class="wh-item-stats">
                    <span><strong>${whAreas.length}</strong> Areas</span>
                    <span><strong>${binsCount}</strong> Bins</span>
                    <span style="color: ${totalUnits > 0 ? '#047857' : '#64748b'}; font-weight: 700;">${totalUnits.toLocaleString()} Units</span>
                </div>
            </div>
        `;
    }).join('');
}

function selectWarehouse(whId) {
    window.WhStore.selectedWarehouseId = whId;
    renderWarehouseList();
    renderSelectedWarehouseTree();
}

function setAreaBehaviorFilter(behavior) {
    window.WhStore.behaviorFilter = behavior;
    document.querySelectorAll('#whBehaviorFilterGroup .wh-filter-chip').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-behavior') === behavior);
    });
    renderSelectedWarehouseTree();
}

function getTypePillClass(areaType) {
    const t = String(areaType || '').toLowerCase();
    if (t.includes('zone')) return 'type-zone';
    if (t.includes('aisle')) return 'type-aisle';
    if (t.includes('rack')) return 'type-rack';
    if (t.includes('bin')) return 'type-bin';
    if (t.includes('dock')) return 'type-dock';
    return 'type-table';
}

function renderSelectedWarehouseTree() {
    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    // Header updates
    document.getElementById('selectedWhCodeBadge').textContent = wh.code;
    document.getElementById('selectedWhNameTitle').textContent = wh.name;
    const statusEl = document.getElementById('selectedWhStatusBadge');
    statusEl.className = `wh-status-pill ${wh.is_active ? 'active' : 'inactive'}`;
    statusEl.textContent = wh.is_active ? 'Active' : 'Inactive';
    document.getElementById('selectedWhAddressText').innerHTML = `<i class="ph ph-map-pin"></i><span>${escapeHtml(wh.address)}</span>`;

    const whAreas = window.WhStore.storageAreas.filter(a => a.warehouse_id === whId);

    // Filter Chip Counts
    document.getElementById('countBehAll').textContent = whAreas.length;
    document.getElementById('countBehStorage').textContent = whAreas.filter(a => a.behavior === 'Storage').length;
    document.getElementById('countBehStaging').textContent = whAreas.filter(a => a.behavior === 'Staging').length;
    document.getElementById('countBehDock').textContent = whAreas.filter(a => a.behavior === 'Dock').length;
    document.getElementById('countBehStocked').textContent = whAreas.filter(a => (Number(a.stock_qty) || 0) > 0).length;

    const searchQ = (document.getElementById('whTreeSearchInput')?.value || '').trim().toLowerCase();
    const behFilter = window.WhStore.behaviorFilter;

    // Determine which nodes match filter/search (and keep ancestors visible so tree structure stays intact)
    const matchingIds = new Set();
    whAreas.forEach(a => {
        const matchesBeh = behFilter === 'ALL'
            ? true
            : (behFilter === 'STOCKED' ? (Number(a.stock_qty) || 0) > 0 : a.behavior === behFilter);
        const matchesSearch = !searchQ ||
            (a.name || '').toLowerCase().includes(searchQ) ||
            (a.code || '').toLowerCase().includes(searchQ) ||
            (a.barcode || '').toLowerCase().includes(searchQ) ||
            (a.area_type || '').toLowerCase().includes(searchQ);

        if (matchesBeh && matchesSearch) {
            matchingIds.add(a.id);
            // Walk up ancestors
            let curr = a;
            let guard = 0;
            while (curr && curr.parent_id && guard < 50) {
                matchingIds.add(curr.parent_id);
                curr = whAreas.find(p => p.id === curr.parent_id);
                guard++;
            }
        }
    });

    const viewport = document.getElementById('whStorageTreeViewport');
    const rootNodes = whAreas.filter(a => !a.parent_id || !whAreas.some(p => p.id === a.parent_id))
                             .filter(a => matchingIds.has(a.id));

    document.getElementById('whTreeSummaryCount').textContent = `${whAreas.length} physical areas in ${wh.code}`;

    if (rootNodes.length === 0) {
        viewport.innerHTML = `
            <div style="text-align: center; padding: 48px 20px; color: #64748b;">
                <i class="ph ph-tree-structure" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 800; color: #334155; font-size: 13.5px;">No Matching Physical Storage Areas</div>
                <div style="font-size: 12px; margin-top: 4px;">Try clearing the filter or click <strong>+ Add Zone</strong> / <strong>Bulk Generate Bins</strong>.</div>
            </div>
        `;
        return;
    }

    function renderNodeRecursive(area) {
        const children = whAreas.filter(ch => ch.parent_id === area.id && matchingIds.has(ch.id));
        const hasChildren = children.length > 0;
        const isCollapsed = window.WhStore.collapsedNodeIds.has(area.id) && !searchQ;
        const subtreeStock = getSubtreeStockQty(area.id);
        const ownStock = Math.max(0, Number(area.stock_qty) || 0);
        const isHighlight = window.WhStore.highlightedAreaId === area.id;

        return `
            <div class="wh-tree-node-group" id="tree-node-${escapeHtml(area.id)}">
                <div class="wh-tree-node-row ${!area.is_active ? 'is-inactive' : ''} ${isHighlight ? 'scan-highlight' : ''}">
                    <div class="wh-node-left">
                        ${hasChildren ? `
                            <button type="button" class="wh-node-toggle" onclick="toggleTreeNode('${escapeHtml(area.id)}')" title="${isCollapsed ? 'Expand' : 'Collapse'}">
                                <i class="ph ${isCollapsed ? 'ph-caret-right' : 'ph-caret-down'}"></i>
                            </button>
                        ` : `
                            <span class="wh-node-toggle leaf"><i class="ph ph-dot-outline"></i></span>
                        `}
                        <span class="wh-type-pill ${getTypePillClass(area.area_type)}">${escapeHtml(area.area_type)}</span>
                        <span class="wh-node-name">${escapeHtml(area.name)}</span>
                        <span class="wh-node-code">(${escapeHtml(area.code)})</span>
                        <span class="wh-behavior-badge ${escapeHtml(area.behavior)}">
                            <i class="ph ${area.behavior === 'Dock' ? 'ph-door-open' : (area.behavior === 'Staging' ? 'ph-arrows-left-right' : 'ph-cube')}"></i>
                            ${escapeHtml(area.behavior)}
                        </span>
                        ${!area.is_active ? `<span class="wh-status-pill inactive">Deactivated</span>` : ''}
                    </div>

                    <div class="wh-node-right">
                        <span class="wh-barcode-pill" onclick="copyAreaBarcode('${escapeHtml(area.barcode)}')" title="Handheld Scanner Barcode — Click to copy">
                            <i class="ph ph-barcode"></i>
                            <span>${escapeHtml(area.barcode)}</span>
                        </span>

                        <span class="wh-stock-pill ${subtreeStock > 0 ? 'has-stock' : 'empty'}" title="${subtreeStock > 0 ? 'Area or nested bins hold stock — protected against deletion' : '0 units on hand'}">
                            <i class="ph ${subtreeStock > 0 ? 'ph-lock-key' : 'ph-check'}"></i>
                            <span>${ownStock > 0 ? `${ownStock} Units` : (subtreeStock > 0 ? `${subtreeStock} Sub-Units` : '0 Units')}</span>
                        </span>

                        <div class="wh-node-actions">
                            <button type="button" class="wh-node-btn" onclick="openStorageAreaModal({ parentId: '${escapeHtml(area.id)}' })" title="Add child area inside ${escapeHtml(area.name)}">
                                <i class="ph ph-plus"></i> Child
                            </button>
                            <button type="button" class="wh-node-btn" onclick="openStorageAreaModal({ areaId: '${escapeHtml(area.id)}' })" title="Edit area details & barcode">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button type="button" class="wh-node-btn" onclick="toggleAreaActiveStatus('${escapeHtml(area.id)}')" title="${area.is_active ? 'Deactivate area' : 'Activate area'}">
                                <i class="ph ${area.is_active ? 'ph-toggle-right' : 'ph-toggle-left'}"></i>
                            </button>
                            <button type="button" class="wh-node-btn danger ${subtreeStock > 0 ? 'is-locked-del' : ''}" onclick="deleteStorageArea('${escapeHtml(area.id)}')" title="${subtreeStock > 0 ? 'Cannot delete area holding stock (Deactivate instead)' : 'Delete empty area'}">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                ${hasChildren && !isCollapsed ? `
                    <div class="wh-tree-children">
                        ${children.map(ch => renderNodeRecursive(ch)).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    viewport.innerHTML = rootNodes.map(root => renderNodeRecursive(root)).join('');
}

function toggleTreeNode(areaId) {
    if (window.WhStore.collapsedNodeIds.has(areaId)) {
        window.WhStore.collapsedNodeIds.delete(areaId);
    } else {
        window.WhStore.collapsedNodeIds.add(areaId);
    }
    renderSelectedWarehouseTree();
}

function expandOrCollapseAllTree(expand) {
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
 * Handheld Barcode Scanner Lookup & Hardware Debounce (100ms)
 */
function triggerBarcodeScanLookup(explicitBarcode) {
    const input = document.getElementById('whBarcodeScannerInput');
    const code = (explicitBarcode || input?.value || '').trim().toUpperCase();
    if (!code) {
        showWhToast('Enter or scan a storage area barcode to locate.', 'warning');
        return;
    }

    const match = window.WhStore.storageAreas.find(a => String(a.barcode).toUpperCase() === code);
    if (!match) {
        const bndMatch = (window.WhStore.boundaryAreas || []).find(b => String(b.barcode).toUpperCase() === code);
        if (bndMatch) {
            showWhToast(`Barcode ${code} is a Virtual Boundary Area (${bndMatch.name}) — ledger-only, not in physical tree.`, 'warning');
            return;
        }
        showWhToast(`No physical storage area found matching barcode "${code}".`, 'danger');
        return;
    }

    // Switch warehouse, clear restrictive filters, expand ancestors, and highlight
    window.WhStore.selectedWarehouseId = match.warehouse_id;
    window.WhStore.behaviorFilter = 'ALL';
    document.querySelectorAll('#whBehaviorFilterGroup .wh-filter-chip').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-behavior') === 'ALL');
    });

    // Ensure all ancestors of matched node are expanded
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

    const rowEl = document.getElementById(`tree-node-${match.id}`);
    if (rowEl) {
        rowEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    showWhToast(`Scanner located [${match.barcode}] → ${match.name} (${match.behavior})`, 'success');
    setTimeout(() => {
        if (window.WhStore.highlightedAreaId === match.id) {
            window.WhStore.highlightedAreaId = null;
            renderSelectedWarehouseTree();
        }
    }, 3500);
}

function simulateBarcodeScan(barcode) {
    const input = document.getElementById('whBarcodeScannerInput');
    if (input) input.value = barcode;
    triggerBarcodeScanLookup(barcode);
}

/**
 * Warehouse Create / Edit Modal
 * Automatically provisions Receiving Dock, Shipping Dock, and Stock Zone when creating a new warehouse!
 */
function openWarehouseModal(whId = null) {
    const modal = document.getElementById('whWarehouseModal');
    const isEdit = Boolean(whId);
    const wh = isEdit ? window.WhStore.warehouses.find(w => w.id === whId) : null;

    document.getElementById('whModalTitle').textContent = isEdit ? `Edit Warehouse (${wh.code})` : 'Create Warehouse';
    document.getElementById('whFormId').value = wh ? wh.id : '';
    document.getElementById('whFormName').value = wh ? wh.name : '';
    document.getElementById('whFormCode').value = wh ? wh.code : '';
    document.getElementById('whFormAddress').value = wh ? wh.address : '';
    document.getElementById('whFormActive').checked = wh ? Boolean(wh.is_active) : true;

    const autoSeedBox = document.getElementById('whAutoSeedNoticeBox');
    if (autoSeedBox) autoSeedBox.style.display = isEdit ? 'none' : 'block';

    modal.classList.add('is-open');
    setTimeout(() => document.getElementById('whFormName')?.focus(), 50);
}

function closeWarehouseModal() {
    document.getElementById('whWarehouseModal')?.classList.remove('is-open');
}

function saveWarehouseForm() {
    const editId = document.getElementById('whFormId').value.trim();
    const name = document.getElementById('whFormName').value.trim();
    const code = document.getElementById('whFormCode').value.trim().toUpperCase();
    const address = document.getElementById('whFormAddress').value.trim();
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
                is_active: isActive,
            };
        }
        showWhToast(`Updated warehouse ${code}`, 'success');
    } else {
        const newWhId = code;
        window.WhStore.warehouses.push({
            id: newWhId,
            code,
            name,
            address,
            is_active: isActive,
            created_at: new Date().toISOString().split('T')[0],
        });

        // Automatically seed the 3 mandatory default areas: Receiving Dock, Shipping Dock, and Stock Zone
        const defaultAreas = [
            {
                id: `AREA-${code}-RCV-${Date.now()}`,
                warehouse_id: newWhId,
                parent_id: null,
                name: 'Receiving Dock',
                code: 'RCV-DOCK',
                area_type: 'Dock Door',
                behavior: 'Dock',
                barcode: `${code}-RCV-DOCK`,
                stock_qty: 0,
                is_active: true,
                is_default: true,
            },
            {
                id: `AREA-${code}-STK-${Date.now() + 1}`,
                warehouse_id: newWhId,
                parent_id: null,
                name: 'Stock Zone (Main Storage)',
                code: 'STK-ZONE',
                area_type: 'Zone',
                behavior: 'Storage',
                barcode: `${code}-STK-ZONE`,
                stock_qty: 0,
                is_active: true,
                is_default: true,
            },
            {
                id: `AREA-${code}-SHP-${Date.now() + 2}`,
                warehouse_id: newWhId,
                parent_id: null,
                name: 'Shipping Dock',
                code: 'SHP-DOCK',
                area_type: 'Dock Door',
                behavior: 'Dock',
                barcode: `${code}-SHP-DOCK`,
                stock_qty: 0,
                is_active: true,
                is_default: true,
            },
        ];
        window.WhStore.storageAreas.push(...defaultAreas);
        window.WhStore.selectedWarehouseId = newWhId;
        showWhToast(`Created warehouse ${code} with default Receiving Dock, Shipping Dock & Stock Zone!`, 'success');
    }

    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    closeWarehouseModal();
}

/**
 * Physical Storage Area Create / Edit Modal
 */
function openStorageAreaModal(opts = {}) {
    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const editArea = opts.areaId ? window.WhStore.storageAreas.find(a => a.id === opts.areaId) : null;
    document.getElementById('areaModalTitle').textContent = editArea ? `Edit Storage Area (${editArea.code})` : 'Add Physical Storage Area';
    document.getElementById('areaFormId').value = editArea ? editArea.id : '';
    document.getElementById('areaFormWarehouse').value = `${wh.code} — ${wh.name}`;

    // Populate valid Parent Areas (same warehouse, excluding self & descendants to prevent cycles)
    const parentSelect = document.getElementById('areaFormParent');
    const whAreas = window.WhStore.storageAreas.filter(a => a.warehouse_id === whId);
    const validParents = whAreas.filter(a => !editArea || !isDescendantOrSelf(editArea.id, a.id));

    const targetParentId = editArea ? (editArea.parent_id || '') : (opts.parentId || '');
    parentSelect.innerHTML = `<option value="">-- Top-Level Root Area in ${escapeHtml(wh.code)} --</option>` +
        validParents.map(p => `
            <option value="${escapeHtml(p.id)}" ${p.id === targetParentId ? 'selected' : ''}>
                [${escapeHtml(p.area_type)}] ${escapeHtml(p.name)} (${escapeHtml(p.code)})
            </option>
        `).join('');

    const areaType = editArea ? editArea.area_type : (opts.presetType || (opts.parentId ? 'Bin' : 'Zone'));
    const behavior = editArea ? editArea.behavior : (opts.presetBehavior || (areaType === 'Dock Door' ? 'Dock' : (areaType === 'Packing Table' ? 'Staging' : 'Storage')));

    document.getElementById('areaFormType').value = areaType;
    document.getElementById('areaFormBehavior').value = behavior;
    document.getElementById('areaFormName').value = editArea ? editArea.name : '';
    document.getElementById('areaFormCode').value = editArea ? editArea.code : '';
    document.getElementById('areaFormBarcode').value = editArea ? editArea.barcode : '';
    document.getElementById('areaFormStockQty').value = editArea ? Math.max(0, Number(editArea.stock_qty) || 0) : 0;
    document.getElementById('areaFormActive').checked = editArea ? Boolean(editArea.is_active) : true;

    if (!editArea) {
        autoSuggestAreaBarcode();
    }

    document.getElementById('whStorageAreaModal')?.classList.add('is-open');
    setTimeout(() => document.getElementById('areaFormName')?.focus(), 50);
}

function closeStorageAreaModal() {
    document.getElementById('whStorageAreaModal')?.classList.remove('is-open');
}

function handleAreaTypeChange(typeVal) {
    const behSelect = document.getElementById('areaFormBehavior');
    if (typeVal === 'Dock Door') behSelect.value = 'Dock';
    else if (typeVal === 'Packing Table') behSelect.value = 'Staging';
    else behSelect.value = 'Storage';
    autoSuggestAreaBarcode();
}

function autoSuggestAreaBarcode() {
    const editId = document.getElementById('areaFormId').value;
    if (editId) return; // Don't overwrite existing barcode on edit unless user edits it manually

    const whId = window.WhStore.selectedWarehouseId;
    const wh = window.WhStore.warehouses.find(w => w.id === whId);
    if (!wh) return;

    const parentId = document.getElementById('areaFormParent').value;
    const parent = parentId ? window.WhStore.storageAreas.find(a => a.id === parentId) : null;
    const codeInput = document.getElementById('areaFormCode');
    const nameVal = document.getElementById('areaFormName').value.trim();

    if (!codeInput.value && nameVal) {
        codeInput.value = nameVal.toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 12);
    }

    const shortCode = (codeInput.value || 'AREA').toUpperCase();
    const basePrefix = parent ? parent.barcode : wh.code;
    document.getElementById('areaFormBarcode').value = `${basePrefix}-${shortCode}`.replace(/--+/g, '-');
}

function saveStorageAreaForm() {
    const whId = window.WhStore.selectedWarehouseId;
    const editId = document.getElementById('areaFormId').value.trim();
    const parentId = document.getElementById('areaFormParent').value || null;
    const areaType = document.getElementById('areaFormType').value;
    const behavior = document.getElementById('areaFormBehavior').value;
    const name = document.getElementById('areaFormName').value.trim();
    const code = document.getElementById('areaFormCode').value.trim().toUpperCase();
    const barcode = document.getElementById('areaFormBarcode').value.trim().toUpperCase();
    const rawStock = parseFloat(document.getElementById('areaFormStockQty').value);
    const isActive = document.getElementById('areaFormActive').checked;

    if (!name || !code || !barcode) {
        showWhToast('Area Name, Area Code, and Unique Scanner Barcode are required.', 'danger');
        return;
    }

    // Rule 1: Physical areas can never hold negative stock
    if (isNaN(rawStock) || rawStock < 0) {
        showWhToast('Physical storage areas can never hold negative stock. Enter 0 or a positive quantity.', 'danger');
        return;
    }

    // Rule 2: No cycles in the tree
    if (editId && parentId && isDescendantOrSelf(editId, parentId)) {
        showWhToast('Invalid parent selection: an area cannot be nested inside itself or its own child.', 'danger');
        return;
    }

    // Rule 3: Unique barcode check
    const dupBarcode = window.WhStore.storageAreas.find(a => a.barcode.toUpperCase() === barcode && a.id !== editId);
    if (dupBarcode) {
        showWhToast(`Barcode "${barcode}" is already assigned to "${dupBarcode.name}". Every area must have a unique barcode.`, 'danger');
        return;
    }

    if (editId) {
        const idx = window.WhStore.storageAreas.findIndex(a => a.id === editId);
        if (idx >= 0) {
            window.WhStore.storageAreas[idx] = {
                ...window.WhStore.storageAreas[idx],
                parent_id: parentId,
                area_type: areaType,
                behavior,
                name,
                code,
                barcode,
                stock_qty: Math.round(rawStock),
                is_active: isActive,
            };
        }
        showWhToast(`Updated storage area ${name} [${barcode}]`, 'success');
    } else {
        const newArea = {
            id: `AREA-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
            warehouse_id: whId,
            parent_id: parentId,
            name,
            code,
            area_type: areaType,
            behavior,
            barcode,
            stock_qty: Math.round(rawStock),
            is_active: isActive,
            is_default: false,
        };
        window.WhStore.storageAreas.push(newArea);
        if (parentId) {
            window.WhStore.collapsedNodeIds.delete(parentId);
        }
        showWhToast(`Added ${areaType} "${name}" with barcode ${barcode}`, 'success');
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
 * Delete Storage Area Guard:
 * A bin or parent area that still holds stock (> 0) cannot be deleted, only deactivated!
 */
function deleteStorageArea(areaId) {
    const area = window.WhStore.storageAreas.find(a => a.id === areaId);
    if (!area) return;

    const subtreeStock = getSubtreeStockQty(areaId);
    if (subtreeStock > 0) {
        showWhToast(`Protected: "${area.name}" holds ${subtreeStock} physical units in stock and cannot be deleted. Deactivate it instead.`, 'danger');
        return;
    }

    // Collect self + empty descendants
    const toRemove = new Set();
    function collect(id) {
        toRemove.add(id);
        window.WhStore.storageAreas.filter(a => a.parent_id === id).forEach(ch => collect(ch.id));
    }
    collect(areaId);

    window.WhStore.storageAreas = window.WhStore.storageAreas.filter(a => !toRemove.has(a.id));
    persistWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();
    showWhToast(`Deleted empty storage area "${area.name}".`, 'success');
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
        const input = document.getElementById('whBarcodeScannerInput');
        if (input) input.value = firstGeneratedBarcode;
    }
}

function toggleBoundaryDrawerInfo() {
    const body = document.getElementById('whBoundaryBody');
    const caret = document.getElementById('whBoundaryCaret');
    if (!body) return;
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? 'flex' : 'none';
    if (caret) caret.className = `ph ${isHidden ? 'ph-caret-down' : 'ph-caret-up'}`;
}

// Expose functions globally for inline handlers
Object.assign(window, {
    openWarehouseModal,
    closeWarehouseModal,
    saveWarehouseForm,
    selectWarehouse,
    setAreaBehaviorFilter,
    renderWarehouseList,
    renderSelectedWarehouseTree,
    toggleTreeNode,
    expandOrCollapseAllTree,
    copyAreaBarcode,
    triggerBarcodeScanLookup,
    simulateBarcodeScan,
    openStorageAreaModal,
    closeStorageAreaModal,
    handleAreaTypeChange,
    autoSuggestAreaBarcode,
    saveStorageAreaForm,
    toggleAreaActiveStatus,
    deleteStorageArea,
    openBulkGeneratorModal,
    closeBulkGeneratorModal,
    updateBulkGeneratorPreview,
    executeBulkBinGeneration,
    toggleBoundaryDrawerInfo,
});

document.addEventListener('DOMContentLoaded', () => {
    hydrateWarehouseState();
    updateWhKpisAndSamples();
    renderWarehouseList();
    renderSelectedWarehouseTree();

    // Hardware Barcode Scanner Debounce (100ms on Enter key)
    const scannerInput = document.getElementById('whBarcodeScannerInput');
    if (scannerInput) {
        let scanDebounce = null;
        scannerInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(scanDebounce);
                scanDebounce = setTimeout(() => {
                    triggerBarcodeScanLookup();
                }, 100);
            }
        });
    }
});

