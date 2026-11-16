/**
 * ==========================================================================
 * MOVEMENT TYPES & SUPPLY ROUTES ENGINE (INVENTORY CONFIGURATION)
 * Formal Enterprise UI, 5-Per-Row Step Grid, Drag-and-Drop Sequence Reordering,
 * 3-Tab Full-Page Configure Route Workspace, Continuity Checks & Category Rules
 * ==========================================================================
 */

const MT_STORAGE_KEY = 'rms_inv_movement_types_v2';
const WH_STORAGE_KEY = 'rms_inv_warehouses_v1';
const AREA_STORAGE_KEY = 'rms_inv_storage_areas_v1';

const DEFAULT_CATEGORIES = [
    'Dry Goods & Grains',
    'Chilled & Dairy',
    'Frozen Proteins',
    'Beverages & Syrups',
    'Packaging & Disposables',
];

window.MsfStore = {
    warehouses: [],
    storageAreas: [],
    boundaryAreas: [],
    movementTypes: [],
    categories: [],
    flowRules: [],
    filterWarehouse: 'ALL',
    filterKind: 'ALL',
    searchQuery: '',
    editingModalSteps: [],
    editingCategories: [],
    activeWorkspaceTab: 'general',
    dragState: {
        type: null, // 'card' | 'card-step' | 'editor-step' | 'preview-step'
        mtId: null,
        fromIndex: -1,
    },
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

function showMsfToast(message, type = 'success') {
    const container = document.getElementById('msfToastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `msf-toast ${type}`;
    const icon = type === 'danger' ? 'ph-warning-octagon' : (type === 'warning' ? 'ph-warning' : 'ph-check-circle');
    toast.innerHTML = `<i class="ph ${icon}" style="font-size: 16px;"></i><span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        if (toast.parentNode) toast.remove();
    }, 3200);
}

function isAdjustmentKind(kind) {
    return kind === 'Adjustment / Scrap' || kind === 'Stock Adjustment';
}

function getCanonicalKind(kind) {
    if (isAdjustmentKind(kind)) return 'Stock Adjustment';
    return kind || 'Inbound Receipt';
}

function getKindMeta(kind) {
    if (isAdjustmentKind(kind)) {
        return { cls: 'adjustment', text: 'Adjustment / Scrap', effect: 'Adjustment / Write-Off', icon: 'ph-trash' };
    }
    switch (kind) {
        case 'Inbound Receipt':
            return { cls: 'inbound', text: 'Inbound Receipt', effect: '+ Receipt (Stock In)', icon: 'ph-arrow-down-left' };
        case 'Outbound Dispatch':
            return { cls: 'outbound', text: 'Outbound Dispatch', effect: '- Dispatch (Stock Out)', icon: 'ph-arrow-up-right' };
        case 'Internal Transfer':
            return { cls: 'transfer', text: 'Internal Transfer', effect: 'Bin Relocation', icon: 'ph-arrows-left-right' };
        default:
            return { cls: 'transfer', text: kind || 'Transfer', effect: 'Transfer', icon: 'ph-arrows-clockwise' };
    }
}

function defaultTriggerForMovement(mt) {
    const kind = getCanonicalKind(mt.operation_kind);
    if (mt.id === 'MT-MAIN-DISPATCH') return 'Branch Transfer Requisition';
    if (kind === 'Inbound Receipt') return 'PO Goods Receipt (GRN)';
    if (kind === 'Outbound Dispatch') return 'POS / Sales Order Dispatch';
    if (kind === 'Internal Transfer') return 'Internal Bin Relocation';
    if (kind === 'Stock Adjustment') return 'Cycle Count / Scrap Write-Off';
    return 'PO Goods Receipt (GRN)';
}

function defaultCategoriesForMovement(mt, flowRules) {
    if (Array.isArray(mt.assigned_categories)) {
        return mt.assigned_categories;
    }
    if (mt.id === 'MT-COLD-QA-RCV') {
        const coldRule = (flowRules || []).find(r => r.warehouse_id === 'WH-COLD' && r.category && r.category !== '*');
        return coldRule ? [coldRule.category, 'Chilled & Dairy'] : ['Frozen Proteins', 'Chilled & Dairy'];
    }
    if (mt.id === 'MT-MAIN-RCV') {
        return ['Dry Goods & Grains', 'Beverages & Syrups', 'Packaging & Disposables'];
    }
    return [];
}

function enrichMovementType(mt, flowRules) {
    const steps = Array.isArray(mt.steps)
        ? mt.steps.map((s, idx) => ({ ...s, step_no: idx + 1 }))
        : [];
    return {
        ...mt,
        trigger_doc: mt.trigger_doc || defaultTriggerForMovement(mt),
        assigned_categories: defaultCategoriesForMovement(mt, flowRules),
        handling_note: mt.handling_note || (mt.id === 'MT-COLD-QA-RCV'
            ? 'Verify cold-chain seal & probe core temperature below -18°C at QA Bench before freezer put-away.'
            : ''),
        allocation_strategy: mt.allocation_strategy || 'FEFO (Earliest Expiry First)',
        shortage_policy: mt.shortage_policy || 'Create Backorder',
        steps,
    };
}

function normalizeAreaName(name) {
    return String(name || '').trim().toLowerCase();
}

function checkRouteContinuity(steps) {
    const brokenAfterIndices = [];
    if (!Array.isArray(steps) || steps.length <= 1) {
        return { isContinuous: true, brokenAfterIndices };
    }
    for (let i = 0; i < steps.length - 1; i++) {
        if (normalizeAreaName(steps[i].to_area) !== normalizeAreaName(steps[i + 1].from_area)) {
            brokenAfterIndices.push(i);
        }
    }
    return { isContinuous: brokenAfterIndices.length === 0, brokenAfterIndices };
}

function autoChainStepsArray(steps) {
    if (!Array.isArray(steps) || steps.length <= 1) return 0;
    let fixed = 0;
    for (let i = 1; i < steps.length; i++) {
        const expectedFrom = steps[i - 1].to_area || '';
        if (expectedFrom && normalizeAreaName(steps[i].from_area) !== normalizeAreaName(expectedFrom)) {
            steps[i].from_area = expectedFrom;
            fixed += 1;
        }
        steps[i].step_no = i + 1;
    }
    if (steps[0]) steps[0].step_no = 1;
    return fixed;
}

function reorderArray(list, fromIdx, toIdx) {
    if (!Array.isArray(list)) return list;
    if (fromIdx < 0 || toIdx < 0 || fromIdx >= list.length || toIdx >= list.length || fromIdx === toIdx) {
        return list;
    }
    const [moved] = list.splice(fromIdx, 1);
    list.splice(toIdx, 0, moved);
    return list;
}

// 1. Initial State Hydration
function hydrateMsfState() {
    const cfg = window.MovementFlowsConfig || {};
    let savedWh = null, savedAreas = null, savedMt = null;

    try {
        savedWh = JSON.parse(localStorage.getItem(WH_STORAGE_KEY));
        savedAreas = JSON.parse(localStorage.getItem(AREA_STORAGE_KEY));
        savedMt = JSON.parse(localStorage.getItem(MT_STORAGE_KEY));
    } catch (e) {}

    window.MsfStore.warehouses = Array.isArray(savedWh) && savedWh.length > 0 ? savedWh : (cfg.initialWarehouses || []);
    window.MsfStore.storageAreas = Array.isArray(savedAreas) && savedAreas.length > 0 ? savedAreas : (cfg.initialStorageAreas || []);
    window.MsfStore.boundaryAreas = cfg.initialBoundaryAreas || [];
    window.MsfStore.categories = Array.isArray(cfg.initialCategories) && cfg.initialCategories.length > 0
        ? cfg.initialCategories
        : DEFAULT_CATEGORIES;
    window.MsfStore.flowRules = Array.isArray(cfg.initialFlowRules) ? cfg.initialFlowRules : [];

    const hasValidSteps = Array.isArray(savedMt) && savedMt.length > 0 && Array.isArray(savedMt[0].steps);
    const rawTypes = hasValidSteps ? savedMt : (cfg.initialMovementTypes || []);
    window.MsfStore.movementTypes = rawTypes.map(mt => enrichMovementType(mt, window.MsfStore.flowRules));
    persistMsfState();

    renderWarehouseFilterPills();
    renderKpis();
    renderMovementTypeCards();
}

function persistMsfState() {
    try {
        localStorage.setItem(MT_STORAGE_KEY, JSON.stringify(window.MsfStore.movementTypes));
    } catch (e) {}
}

// 2. Warehouse Filter Pills & Segmented Counts
function renderWarehouseFilterPills() {
    const container = document.getElementById('msfWarehousePills');
    if (!container) return;

    let html = `<button type="button" class="msf-fac-btn ${window.MsfStore.filterWarehouse === 'ALL' ? 'active' : ''}" data-wh="ALL" onclick="filterByWarehouse('ALL')">All</button>`;

    window.MsfStore.warehouses.forEach(wh => {
        const isActive = window.MsfStore.filterWarehouse === wh.id;
        html += `<button type="button" class="msf-fac-btn ${isActive ? 'active' : ''}" data-wh="${escapeHtml(wh.id)}" onclick="filterByWarehouse('${escapeHtml(wh.id)}')">${escapeHtml(wh.code)}</button>`;
    });

    container.innerHTML = html;
}

function updateControlsCounts() {
    const { filterWarehouse, movementTypes } = window.MsfStore;
    const scoped = filterWarehouse === 'ALL'
        ? movementTypes
        : movementTypes.filter(m => m.warehouse_id === filterWarehouse);

    const setCount = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(val);
    };

    setCount('msfCountAll', scoped.length);
    setCount('msfCountInbound', scoped.filter(m => m.operation_kind === 'Inbound Receipt').length);
    setCount('msfCountOutbound', scoped.filter(m => m.operation_kind === 'Outbound Dispatch').length);
    setCount('msfCountTransfer', scoped.filter(m => m.operation_kind === 'Internal Transfer').length);
    setCount('msfCountAdjust', scoped.filter(m => isAdjustmentKind(m.operation_kind)).length);
}

// 3. Top KPI Cards
function renderKpis() {
    const types = window.MsfStore.movementTypes;
    const activeTypes = types.filter(t => t.is_active !== false);

    const kpiTotal = document.getElementById('msfKpiTotalTypes');
    if (kpiTotal) kpiTotal.textContent = `${activeTypes.length} / ${types.length} Active`;

    const brokenCount = activeTypes.filter(t => !checkRouteContinuity(t.steps).isContinuous).length;
    const kpiTotalSub = document.getElementById('msfKpiTotalSub');
    if (kpiTotalSub) {
        kpiTotalSub.textContent = brokenCount > 0
            ? `${brokenCount} route${brokenCount === 1 ? '' : 's'} with hand-off mismatch`
            : 'All active routes pass hand-off continuity checks';
    }

    const inboundCount = activeTypes.filter(t => t.operation_kind === 'Inbound Receipt').length;
    const outboundCount = activeTypes.filter(t => t.operation_kind === 'Outbound Dispatch').length;
    const transferCount = activeTypes.filter(t => t.operation_kind === 'Internal Transfer').length;
    const scrapCount = activeTypes.filter(t => isAdjustmentKind(t.operation_kind)).length;

    const kpiBreakdown = document.getElementById('msfKpiOpsBreakdown');
    if (kpiBreakdown) {
        kpiBreakdown.textContent = `${inboundCount} In · ${outboundCount} Out · ${transferCount + scrapCount} Trf/Adj`;
    }

    const whCount = window.MsfStore.warehouses.length;
    const kpiWh = document.getElementById('msfKpiFacilities');
    if (kpiWh) kpiWh.textContent = `${whCount} Facilities`;

    const kpiWhList = document.getElementById('msfKpiFacilitiesList');
    if (kpiWhList) {
        const codes = window.MsfStore.warehouses.map(w => w.code).join(' · ');
        kpiWhList.textContent = codes || 'Central, Cold-Chain & Branch Hubs';
    }

    updateControlsCounts();
}

// 4. Filters & Search Handlers
window.filterByWarehouse = function(whCode) {
    window.MsfStore.filterWarehouse = whCode;
    renderWarehouseFilterPills();
    updateControlsCounts();
    renderMovementTypeCards();
};

window.filterByKind = function(kind) {
    window.MsfStore.filterKind = kind;
    const container = document.getElementById('msfKindPills');
    if (container) {
        container.querySelectorAll('.msf-seg-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-kind') === kind);
        });
    }
    renderMovementTypeCards();
};

window.onSearchInput = function(query) {
    window.MsfStore.searchQuery = (query || '').toLowerCase().trim();
    const clearBtn = document.getElementById('msfSearchClearBtn');
    if (clearBtn) clearBtn.style.display = query && query.length > 0 ? 'inline-flex' : 'none';
    renderMovementTypeCards();
};

window.clearMsfSearch = function() {
    const input = document.getElementById('msfSearchInput');
    if (input) {
        input.value = '';
        input.focus();
    }
    window.onSearchInput('');
};

function matchesKindFilter(mtKind, filterKind) {
    if (filterKind === 'ALL') return true;
    if (isAdjustmentKind(filterKind) && isAdjustmentKind(mtKind)) return true;
    return mtKind === filterKind;
}

function buildStepScansHtml(step) {
    const chips = [];
    if (step.scan_source) chips.push('<span class="msf-scan-badge active"><i class="ph ph-barcode"></i> Src Scan</span>');
    if (step.scan_product !== false) chips.push('<span class="msf-scan-badge active"><i class="ph ph-package"></i> SKU Scan</span>');
    if (step.scan_destination !== false) chips.push('<span class="msf-scan-badge active"><i class="ph ph-map-pin"></i> Dest Scan</span>');
    if (step.auto_print_label) chips.push('<span class="msf-scan-badge print"><i class="ph ph-printer"></i> Auto-Label</span>');
    if (chips.length === 0) chips.push('<span class="msf-scan-badge muted">Manual Confirm</span>');
    return chips.join('');
}

// 5. Render Movement Type Cards Grid
function renderMovementTypeCards() {
    const container = document.getElementById('msfCardsContainer');
    if (!container) return;

    const { filterWarehouse, filterKind, searchQuery, movementTypes, warehouses } = window.MsfStore;

    const filtered = movementTypes.filter(mt => {
        if (filterWarehouse !== 'ALL' && mt.warehouse_id !== filterWarehouse) return false;
        if (!matchesKindFilter(mt.operation_kind, filterKind)) return false;
        if (searchQuery) {
            const code = (mt.code || '').toLowerCase();
            const name = (mt.name || '').toLowerCase();
            const kind = (mt.operation_kind || '').toLowerCase();
            const trigger = (mt.trigger_doc || '').toLowerCase();
            const catMatch = (mt.assigned_categories || []).some(c => c.toLowerCase().includes(searchQuery));
            const stepMatches = (mt.steps || []).some(s =>
                (s.name || '').toLowerCase().includes(searchQuery) ||
                (s.from_area || '').toLowerCase().includes(searchQuery) ||
                (s.to_area || '').toLowerCase().includes(searchQuery)
            );
            if (!code.includes(searchQuery) && !name.includes(searchQuery) && !kind.includes(searchQuery) && !trigger.includes(searchQuery) && !catMatch && !stepMatches) {
                return false;
            }
        }
        return true;
    });

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="msf-empty-box">
                <i class="ph ph-barcode msf-empty-icon"></i>
                <div style="font-weight: 700; font-size: 14px; color: #0f172a;">No Movement Types Found</div>
                <p style="font-size: 12px; margin: 4px 0 14px 0;">No movement types match your current warehouse, operation kind, or search query.</p>
                <button type="button" class="msf-btn msf-btn-primary msf-btn-sm" onclick="openMovementTypeModal()">
                    <i class="ph ph-plus"></i> Create New Movement Type
                </button>
            </div>
        `;
        return;
    }

    container.innerHTML = filtered.map(mt => {
        const wh = warehouses.find(w => w.id === mt.warehouse_id);
        const whName = wh ? wh.code : (mt.warehouse_id || 'WH-MAIN');
        const kindMeta = getKindMeta(mt.operation_kind);
        const isActive = mt.is_active !== false;
        const steps = Array.isArray(mt.steps) ? mt.steps : [];
        const continuity = checkRouteContinuity(steps);
        const presetTag = mt.route_preset || `${steps.length}-Step Route`;
        const categories = Array.isArray(mt.assigned_categories) ? mt.assigned_categories : [];

        const categoryHtml = categories.length > 0
            ? categories.map(cat => `<span class="msf-cat-chip"><i class="ph ph-tag"></i> ${escapeHtml(cat)}</span>`).join('')
            : '<span class="msf-cat-chip">All Categories</span>';

        const continuityHtml = continuity.isContinuous
            ? '<span class="msf-chain-pill ok"><i class="ph ph-link"></i> Continuous Chain</span>'
            : `<span class="msf-chain-pill broken" title="Adjacent steps do not share matching To/From areas"><i class="ph ph-warning"></i> Handoff Break (${continuity.brokenAfterIndices.length})</span>
               <button type="button" class="msf-auto-link-inline-btn" onclick="autoChainCardSteps('${escapeHtml(mt.id)}')" title="Sync each step's From Area to match the prior step's To Area">
                   <i class="ph ph-magic-wand"></i> Auto-Chain
               </button>`;

        const stepsHtml = steps.map((step, idx) => {
            const stepNum = idx + 1;
            const isBrokenAfter = continuity.brokenAfterIndices.includes(idx);
            const connectorTitle = isBrokenAfter
                ? `Handoff break: "${step.to_area}" does not match next step origin`
                : 'Sequential hand-off';

            return `
                <div class="msf-step-node"
                     draggable="true"
                     data-mt-id="${escapeHtml(mt.id)}"
                     data-step-idx="${idx}"
                     ondragstart="onCardStepDragStart(event, '${escapeHtml(mt.id)}', ${idx})"
                     ondragover="onCardStepDragOver(event)"
                     ondragenter="onCardStepDragEnter(event)"
                     ondragleave="onCardStepDragLeave(event)"
                     ondrop="onCardStepDrop(event, '${escapeHtml(mt.id)}', ${idx})"
                     ondragend="onCardStepDragEnd(event)">
                    <div>
                        <div class="msf-step-node-head">
                            <div class="msf-step-title-group">
                                <span class="msf-step-num">${stepNum}</span>
                                <span class="msf-step-name" title="${escapeHtml(step.name)}">${escapeHtml(step.name || `Step ${stepNum}`)}</span>
                            </div>
                            <i class="ph ph-dots-six-vertical msf-step-drag-grip" title="Drag step card to reorder"></i>
                        </div>

                        <div class="msf-step-path-stack" style="margin-top: 8px;">
                            <div class="msf-step-area-row">
                                <span class="msf-step-dir-tag">From</span>
                                <span class="msf-step-area" title="${escapeHtml(step.from_area)}">${escapeHtml(step.from_area)}</span>
                            </div>
                            <div class="msf-step-area-row">
                                <span class="msf-step-dir-tag">To</span>
                                <span class="msf-step-area" title="${escapeHtml(step.to_area)}">${escapeHtml(step.to_area)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="msf-step-scans">
                        ${buildStepScansHtml(step)}
                    </div>

                    <span class="msf-step-connector ${isBrokenAfter ? 'is-broken' : ''}" title="${escapeHtml(connectorTitle)}">
                        <i class="ph ${isBrokenAfter ? 'ph-warning' : 'ph-arrow-right'}"></i>
                    </span>
                </div>
            `;
        }).join('');

        return `
            <div class="msf-card ${!isActive ? 'inactive' : ''}"
                 data-mt-id="${escapeHtml(mt.id)}"
                 ondragstart="onMtCardDragStart(event, '${escapeHtml(mt.id)}')"
                 ondragover="onMtCardDragOver(event)"
                 ondragenter="onMtCardDragEnter(event)"
                 ondragleave="onMtCardDragLeave(event)"
                 ondrop="onMtCardDrop(event, '${escapeHtml(mt.id)}')"
                 ondragend="onMtCardDragEnd(event)">

                <div class="msf-card-header">
                    <div class="msf-card-head-left">
                        <span class="msf-drag-handle"
                              title="Drag card to reorder movement priority"
                              onmousedown="enableParentCardDrag(this, true)"
                              onmouseup="enableParentCardDrag(this, false)">
                            <i class="ph ph-dots-six-vertical"></i>
                        </span>
                        <span class="msf-card-code">${escapeHtml(mt.code)}</span>
                        <h3 class="msf-card-title">${escapeHtml(mt.name)}</h3>
                        <span class="msf-wh-inline" title="Target Facility">
                            <i class="ph ph-warehouse"></i>
                            <span>${escapeHtml(whName)}</span>
                        </span>
                        <span class="msf-kind-indicator ${kindMeta.cls}">
                            <span class="msf-kind-dot"></span>
                            <span>${escapeHtml(kindMeta.text)}</span>
                        </span>
                    </div>

                    <div class="msf-card-head-actions">
                        <div class="msf-toggle-wrap" onclick="toggleMovementTypeActive('${escapeHtml(mt.id)}')" title="Toggle Active Status">
                            <span class="msf-toggle ${isActive ? 'checked' : ''}"></span>
                            <span>${isActive ? 'Active' : 'Inactive'}</span>
                        </div>
                        <button type="button" class="msf-btn msf-btn-outline msf-btn-sm" onclick="duplicateMovementType('${escapeHtml(mt.id)}')" title="Clone Movement Type & Route">
                            <i class="ph ph-copy"></i>
                            <span>Duplicate</span>
                        </button>
                        <button type="button" class="msf-btn msf-btn-outline msf-btn-sm" onclick="openMovementTypeModal('${escapeHtml(mt.id)}')">
                            <i class="ph ph-sliders"></i>
                            <span>Configure Route</span>
                        </button>
                        <button type="button" class="msf-btn msf-btn-danger-outline msf-btn-sm" onclick="deleteMovementType('${escapeHtml(mt.id)}')" title="Delete Movement Type">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="msf-card-body">
                    <div class="msf-route-meta">
                        <div class="msf-route-meta-left">
                            <span class="msf-route-tag"><i class="ph ph-git-fork"></i> ${escapeHtml(presetTag)}</span>
                            <span class="msf-trigger-chip"><i class="ph ph-lightning"></i> ${escapeHtml(mt.trigger_doc || defaultTriggerForMovement(mt))}</span>
                            ${categoryHtml}
                        </div>
                        <div class="msf-route-meta-right">
                            <span>${steps.length} ${steps.length === 1 ? 'Step' : 'Steps'}</span>
                            ${continuityHtml}
                        </div>
                    </div>

                    <div class="msf-route-pipeline">
                        ${stepsHtml}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// 5B. Drag-and-Drop Handlers (Movement Type Cards + Card Route Steps)
window.enableParentCardDrag = function(handleEl, enabled) {
    const card = handleEl ? handleEl.closest('.msf-card') : null;
    if (!card) return;
    if (enabled) card.setAttribute('draggable', 'true');
    else card.removeAttribute('draggable');
};

window.onMtCardDragStart = function(event, mtId) {
    if (window.MsfStore.dragState.type === 'card-step') return;
    window.MsfStore.dragState = { type: 'card', mtId, fromIndex: -1 };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', `mt:${mtId}`);
    const card = event.currentTarget;
    if (card) card.classList.add('is-dragging');
};

window.onMtCardDragOver = function(event) {
    if (window.MsfStore.dragState.type !== 'card') return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

window.onMtCardDragEnter = function(event) {
    if (window.MsfStore.dragState.type !== 'card') return;
    const card = event.currentTarget;
    if (card && card.getAttribute('data-mt-id') !== window.MsfStore.dragState.mtId) {
        card.classList.add('is-drag-over');
    }
};

window.onMtCardDragLeave = function(event) {
    if (window.MsfStore.dragState.type !== 'card') return;
    const card = event.currentTarget;
    if (card && !card.contains(event.relatedTarget)) {
        card.classList.remove('is-drag-over');
    }
};

window.onMtCardDrop = function(event, targetMtId) {
    if (window.MsfStore.dragState.type !== 'card') return;
    event.preventDefault();
    const srcId = window.MsfStore.dragState.mtId;
    if (!srcId || srcId === targetMtId) return;

    const list = window.MsfStore.movementTypes;
    const fromIdx = list.findIndex(m => m.id === srcId);
    const toIdx = list.findIndex(m => m.id === targetMtId);
    if (fromIdx === -1 || toIdx === -1) return;

    reorderArray(list, fromIdx, toIdx);
    persistMsfState();
    renderMovementTypeCards();
    showMsfToast('Movement type display sequence updated');
};

window.onMtCardDragEnd = function(event) {
    const card = event.currentTarget;
    if (card) {
        card.classList.remove('is-dragging');
        card.removeAttribute('draggable');
    }
    document.querySelectorAll('.msf-card.is-drag-over').forEach(el => el.classList.remove('is-drag-over'));
    window.MsfStore.dragState = { type: null, mtId: null, fromIndex: -1 };
};

window.onCardStepDragStart = function(event, mtId, stepIdx) {
    event.stopPropagation();
    window.MsfStore.dragState = { type: 'card-step', mtId, fromIndex: stepIdx };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', `step:${mtId}:${stepIdx}`);
    const node = event.currentTarget;
    if (node) node.classList.add('is-dragging');
};

window.onCardStepDragOver = function(event) {
    if (window.MsfStore.dragState.type !== 'card-step') return;
    event.preventDefault();
    event.stopPropagation();
    event.dataTransfer.dropEffect = 'move';
};

window.onCardStepDragEnter = function(event) {
    if (window.MsfStore.dragState.type !== 'card-step') return;
    const node = event.currentTarget;
    if (node && node.getAttribute('data-mt-id') === window.MsfStore.dragState.mtId) {
        node.classList.add('is-drag-over');
    }
};

window.onCardStepDragLeave = function(event) {
    if (window.MsfStore.dragState.type !== 'card-step') return;
    const node = event.currentTarget;
    if (node && !node.contains(event.relatedTarget)) {
        node.classList.remove('is-drag-over');
    }
};

window.onCardStepDrop = function(event, targetMtId, targetStepIdx) {
    if (window.MsfStore.dragState.type !== 'card-step') return;
    event.preventDefault();
    event.stopPropagation();

    const { mtId, fromIndex } = window.MsfStore.dragState;
    if (mtId !== targetMtId || fromIndex === targetStepIdx || fromIndex < 0) return;

    const mt = window.MsfStore.movementTypes.find(m => m.id === mtId);
    if (!mt || !Array.isArray(mt.steps)) return;

    reorderArray(mt.steps, fromIndex, targetStepIdx);
    mt.steps.forEach((s, i) => { s.step_no = i + 1; });

    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast(`Reordered route steps for "${mt.code}"`);
};

window.onCardStepDragEnd = function(event) {
    event.stopPropagation();
    document.querySelectorAll('.msf-step-node.is-dragging, .msf-step-node.is-drag-over').forEach(el => {
        el.classList.remove('is-dragging', 'is-drag-over');
    });
    window.MsfStore.dragState = { type: null, mtId: null, fromIndex: -1 };
};

window.autoChainCardSteps = function(mtId) {
    const mt = window.MsfStore.movementTypes.find(m => m.id === mtId);
    if (!mt || !Array.isArray(mt.steps)) return;
    const fixed = autoChainStepsArray(mt.steps);
    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast(fixed > 0 ? `Linked ${fixed} hand-off${fixed === 1 ? '' : 's'} for "${mt.code}"` : 'Route chain is already continuous');
};

// 6. Full-Page 3-Tab Configure Route Workspace Operations
function getAreaOptionsForWarehouse(whId, preserveValues = []) {
    const physical = window.MsfStore.storageAreas.filter(a => a.warehouse_id === whId && a.is_active !== false);
    const boundary = window.MsfStore.boundaryAreas;

    const list = [];
    const seen = new Set();

    const addOption = (label, val) => {
        if (!val || seen.has(val)) return;
        seen.add(val);
        list.push({ label, val });
    };

    boundary.forEach(b => {
        const boundaryVal = b.name.includes('(Boundary)') ? b.name : `${b.name} (Boundary)`;
        addOption(`${b.name} (Virtual Boundary)`, boundaryVal);
    });

    physical.forEach(p => {
        addOption(`${p.name} [${p.behavior}]`, p.name);
    });

    (preserveValues || []).forEach(val => {
        if (val && !seen.has(val)) {
            addOption(`${val} (Assigned Area)`, val);
        }
    });

    return list;
}

window.switchMsfWorkspaceTab = function(tabName) {
    const allowed = ['general', 'pipeline', 'rules'];
    const target = allowed.includes(tabName) ? tabName : 'general';
    window.MsfStore.activeWorkspaceTab = target;

    document.querySelectorAll('.msf-page-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-msf-tab') === target);
    });

    const panes = {
        general: document.getElementById('msfPaneGeneral'),
        pipeline: document.getElementById('msfPanePipeline'),
        rules: document.getElementById('msfPaneRules'),
    };

    Object.entries(panes).forEach(([key, el]) => {
        if (el) el.classList.toggle('active', key === target);
    });
};

function syncKindSelectorCards(kind) {
    const canonical = getCanonicalKind(kind);
    const kindSelect = document.getElementById('msfFormKind');
    if (kindSelect) kindSelect.value = canonical;

    document.querySelectorAll('#msfKindSelectorCards .msf-kind-select-card').forEach(card => {
        card.classList.toggle('active', card.getAttribute('data-kind-val') === canonical);
    });
}

window.selectModalOperationKind = function(kindVal) {
    syncKindSelectorCards(kindVal);
    onModalKindChange();
};

function syncMsfHeaderOffset() {
    const headerEl = document.querySelector('.main-header');
    const offset = headerEl ? Math.max(56, Math.round(headerEl.getBoundingClientRect().bottom)) : 64;
    document.documentElement.style.setProperty('--rms-header-offset', `${offset}px`);
}

window.openMovementTypeModal = function(id = null) {
    const drawer = document.getElementById('msfMovementTypeModal');
    const backdrop = document.getElementById('msfDrawerBackdrop');
    if (!drawer) return;

    syncMsfHeaderOffset();

    const whSelect = document.getElementById('msfFormWarehouse');
    if (whSelect) {
        whSelect.innerHTML = window.MsfStore.warehouses.map(w =>
            `<option value="${escapeHtml(w.id)}">${escapeHtml(w.code)} — ${escapeHtml(w.name)}</option>`
        ).join('');
    }

    const dupBtn = document.getElementById('msfWorkspaceDuplicateBtn');
    const delBtn = document.getElementById('msfWorkspaceDeleteBtn');

    if (id) {
        const mt = window.MsfStore.movementTypes.find(m => m.id === id);
        if (!mt) return;

        document.getElementById('msfModalTitle').textContent = mt.name || mt.code || 'Configure Route';
        document.getElementById('msfModalSubtitle').textContent = `Configure operation properties, multi-step routing hand-offs, and category rules for ${mt.code}`;
        document.getElementById('msfFormId').value = mt.id;
        document.getElementById('msfFormName').value = mt.name || '';
        document.getElementById('msfFormCode').value = mt.code || '';
        if (whSelect) whSelect.value = mt.warehouse_id || 'WH-MAIN';
        syncKindSelectorCards(mt.operation_kind);
        document.getElementById('msfFormTriggerDoc').value = mt.trigger_doc || defaultTriggerForMovement(mt);
        document.getElementById('msfFormHandlingNote').value = mt.handling_note || '';
        const activeChk = document.getElementById('msfFormIsActive');
        if (activeChk) activeChk.checked = mt.is_active !== false;
        const allocEl = document.getElementById('msfFormAllocation');
        if (allocEl) allocEl.value = mt.allocation_strategy || 'FEFO (Earliest Expiry First)';
        const shortageEl = document.getElementById('msfFormShortagePolicy');
        if (shortageEl) shortageEl.value = mt.shortage_policy || 'Create Backorder';

        window.MsfStore.editingModalSteps = JSON.parse(JSON.stringify(mt.steps || []));
        window.MsfStore.editingCategories = Array.isArray(mt.assigned_categories) ? [...mt.assigned_categories] : [];
        if (dupBtn) dupBtn.style.display = 'inline-flex';
        if (delBtn) delBtn.style.display = 'inline-flex';

        switchMsfWorkspaceTab('pipeline');
    } else {
        const defaultWh = window.MsfStore.filterWarehouse !== 'ALL'
            ? window.MsfStore.filterWarehouse
            : (window.MsfStore.warehouses[0] ? window.MsfStore.warehouses[0].id : 'WH-MAIN');
        const newCode = `${defaultWh}-OP-${Date.now().toString().slice(-3)}`;

        document.getElementById('msfModalTitle').textContent = 'Create Movement Type & Supply Route';
        document.getElementById('msfModalSubtitle').textContent = 'Define operation identity, warehouse routing sequence, and category auto-routing triggers.';
        document.getElementById('msfFormId').value = '';
        document.getElementById('msfFormName').value = '';
        document.getElementById('msfFormCode').value = newCode;
        if (whSelect) whSelect.value = defaultWh;
        syncKindSelectorCards('Inbound Receipt');
        document.getElementById('msfFormTriggerDoc').value = 'PO Goods Receipt (GRN)';
        document.getElementById('msfFormHandlingNote').value = '';
        const activeChk = document.getElementById('msfFormIsActive');
        if (activeChk) activeChk.checked = true;
        const allocEl = document.getElementById('msfFormAllocation');
        if (allocEl) allocEl.value = 'FEFO (Earliest Expiry First)';
        const shortageEl = document.getElementById('msfFormShortagePolicy');
        if (shortageEl) shortageEl.value = 'Create Backorder';

        window.MsfStore.editingCategories = [];
        applyRoutePreset('2-Step Staged', false);
        if (dupBtn) dupBtn.style.display = 'none';
        if (delBtn) delBtn.style.display = 'none';

        switchMsfWorkspaceTab('general');
    }

    renderModalCategoryChips();
    renderModalSteps();
    updateModalRoutePreview();

    if (backdrop) {
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
    }
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    const drawerBody = drawer.querySelector('.msf-drawer-body');
    if (drawerBody) drawerBody.scrollTop = 0;
};

window.closeMovementTypeModal = function() {
    const drawer = document.getElementById('msfMovementTypeModal');
    const backdrop = document.getElementById('msfDrawerBackdrop');
    if (drawer) {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
    }
    if (backdrop) {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
    }
};

window.onModalWarehouseChange = function() {
    renderModalSteps();
    updateModalRoutePreview();
};

window.onModalKindChange = function() {
    const kind = document.getElementById('msfFormKind').value;
    syncKindSelectorCards(kind);
    const triggerEl = document.getElementById('msfFormTriggerDoc');
    if (triggerEl) {
        triggerEl.value = defaultTriggerForMovement({ operation_kind: kind });
    }
    if (kind === 'Inbound Receipt' || kind === 'Outbound Dispatch') {
        applyRoutePreset('2-Step Staged', !document.getElementById('msfFormId').value);
    } else {
        applyRoutePreset('1-Step Direct', !document.getElementById('msfFormId').value);
    }
    if (document.getElementById('msfFormId').value) {
        renderModalSteps();
        updateModalRoutePreview();
    }
};

// 7. Route Presets Application
window.applyRoutePreset = function(presetName, render = true) {
    const whId = document.getElementById('msfFormWarehouse') ? document.getElementById('msfFormWarehouse').value : 'WH-MAIN';
    const kind = document.getElementById('msfFormKind') ? document.getElementById('msfFormKind').value : 'Inbound Receipt';

    const whAreas = window.MsfStore.storageAreas.filter(a => a.warehouse_id === whId);
    const dock = (whAreas.find(a => a.behavior === 'Dock') || {}).name || 'Receiving Dock';
    const storage = (whAreas.find(a => a.behavior === 'Storage') || {}).name || 'Stock Zone A (Dry Goods)';
    const staging = (whAreas.find(a => a.behavior === 'Staging') || {}).name || 'Outbound Packing & Kitting Table';

    if (presetName === '1-Step Direct') {
        if (kind === 'Inbound Receipt') {
            window.MsfStore.editingModalSteps = [{
                step_no: 1,
                name: 'Direct Receipt to Storage',
                from_area: 'Supplier / Vendor Origin (Boundary)',
                to_area: storage,
                scan_source: false,
                scan_product: true,
                scan_destination: true,
                auto_print_label: true,
            }];
        } else if (kind === 'Outbound Dispatch') {
            window.MsfStore.editingModalSteps = [{
                step_no: 1,
                name: 'Direct Dispatch from Storage',
                from_area: storage,
                to_area: 'Customer / Branch Destination (Boundary)',
                scan_source: true,
                scan_product: true,
                scan_destination: false,
                auto_print_label: true,
            }];
        } else if (kind === 'Internal Transfer') {
            window.MsfStore.editingModalSteps = [{
                step_no: 1,
                name: 'Direct Relocation Between Areas',
                from_area: storage,
                to_area: staging,
                scan_source: true,
                scan_product: true,
                scan_destination: true,
                auto_print_label: false,
            }];
        } else {
            window.MsfStore.editingModalSteps = [{
                step_no: 1,
                name: 'Scrap Write-Off',
                from_area: storage,
                to_area: 'Scrap / Spoilage & Write-Off (Boundary)',
                scan_source: true,
                scan_product: true,
                scan_destination: false,
                auto_print_label: false,
            }];
        }
    } else if (presetName === '2-Step Staged') {
        if (kind === 'Outbound Dispatch') {
            window.MsfStore.editingModalSteps = [
                {
                    step_no: 1,
                    name: 'Pick from Bins to Packing Table',
                    from_area: storage,
                    to_area: staging,
                    scan_source: true,
                    scan_product: true,
                    scan_destination: true,
                    auto_print_label: false,
                },
                {
                    step_no: 2,
                    name: 'Dispatch to Branch Logistics',
                    from_area: staging,
                    to_area: 'Customer / Branch Destination (Boundary)',
                    scan_source: true,
                    scan_product: true,
                    scan_destination: false,
                    auto_print_label: true,
                },
            ];
        } else {
            window.MsfStore.editingModalSteps = [
                {
                    step_no: 1,
                    name: 'Receive at Dock',
                    from_area: 'Supplier / Vendor Origin (Boundary)',
                    to_area: dock,
                    scan_source: false,
                    scan_product: true,
                    scan_destination: true,
                    auto_print_label: true,
                },
                {
                    step_no: 2,
                    name: 'Put Away to Storage',
                    from_area: dock,
                    to_area: storage,
                    scan_source: true,
                    scan_product: true,
                    scan_destination: true,
                    auto_print_label: false,
                },
            ];
        }
    } else if (presetName === '3-Step Quality Check') {
        window.MsfStore.editingModalSteps = [
            {
                step_no: 1,
                name: 'Receive at Inbound Dock',
                from_area: 'Supplier / Vendor Origin (Boundary)',
                to_area: dock,
                scan_source: false,
                scan_product: true,
                scan_destination: true,
                auto_print_label: true,
            },
            {
                step_no: 2,
                name: 'Quality Check & Temp Probe',
                from_area: dock,
                to_area: staging,
                scan_source: true,
                scan_product: true,
                scan_destination: true,
                auto_print_label: true,
            },
            {
                step_no: 3,
                name: 'Put Away to Storage Bin',
                from_area: staging,
                to_area: storage,
                scan_source: true,
                scan_product: true,
                scan_destination: true,
                auto_print_label: false,
            },
        ];
    }

    if (render) {
        renderModalSteps();
        updateModalRoutePreview();
        showMsfToast(`Applied "${presetName}" Route Template`);
    }
};

// 8. Category Rules Chips inside Tab 3
function renderModalCategoryChips() {
    const grid = document.getElementById('msfCategoryRulesGrid');
    if (!grid) return;
    const selected = window.MsfStore.editingCategories || [];

    grid.innerHTML = window.MsfStore.categories.map(cat => {
        const isSelected = selected.includes(cat);
        return `
            <button type="button" class="msf-category-toggle-btn ${isSelected ? 'active' : ''}" data-category="${escapeHtml(cat)}" onclick="toggleModalCategory(this.getAttribute('data-category'))">
                <i class="ph ${isSelected ? 'ph-check-circle' : 'ph-tag'}"></i>
                <span>${escapeHtml(cat)}</span>
            </button>
        `;
    }).join('');
}

window.toggleModalCategory = function(cat) {
    const list = window.MsfStore.editingCategories;
    const idx = list.indexOf(cat);
    if (idx === -1) list.push(cat);
    else list.splice(idx, 1);
    renderModalCategoryChips();
    updateLivePreview();
};

window.clearModalCategories = function() {
    window.MsfStore.editingCategories = [];
    renderModalCategoryChips();
    updateLivePreview();
};

// 9. Render Draggable Step Sequence Editor
function renderModalSteps() {
    const container = document.getElementById('msfModalStepsContainer');
    if (!container) return;

    const whId = document.getElementById('msfFormWarehouse') ? document.getElementById('msfFormWarehouse').value : 'WH-MAIN';
    const steps = window.MsfStore.editingModalSteps;
    const continuity = checkRouteContinuity(steps);

    if (steps.length === 0) {
        container.innerHTML = `
            <div style="padding: 20px; text-align: center; color: #64748b; font-size: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
                No steps configured yet. Click "Add Next Step" or choose a quick template above.
            </div>
        `;
        return;
    }

    container.innerHTML = steps.map((step, idx) => {
        const stepNum = idx + 1;
        step.step_no = stepNum;
        const areaOptions = getAreaOptionsForWarehouse(whId, [step.from_area, step.to_area]);
        const isBrokenAfter = continuity.brokenAfterIndices.includes(idx);

        const fromOpts = areaOptions.map(opt =>
            `<option value="${escapeHtml(opt.val)}" ${step.from_area === opt.val ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
        ).join('');

        const toOpts = areaOptions.map(opt =>
            `<option value="${escapeHtml(opt.val)}" ${step.to_area === opt.val ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
        ).join('');

        const scanSrc = !!step.scan_source;
        const scanSku = step.scan_product !== false;
        const scanDest = step.scan_destination !== false;
        const autoPrint = !!step.auto_print_label;

        return `
            <div class="msf-step-editor-card ${isBrokenAfter ? 'is-broken-handoff' : ''}"
                 data-step-idx="${idx}"
                 ondragstart="onEditorStepDragStart(event, ${idx})"
                 ondragover="onEditorStepDragOver(event)"
                 ondragenter="onEditorStepDragEnter(event)"
                 ondragleave="onEditorStepDragLeave(event)"
                 ondrop="onEditorStepDrop(event, ${idx})"
                 ondragend="onEditorStepDragEnd(event)">

                <div class="msf-step-editor-head">
                    <div class="msf-step-editor-title-row">
                        <span class="msf-drag-handle msf-step-drag-btn"
                              title="Drag step to reorder sequence"
                              onmousedown="enableEditorRowDrag(this, true)"
                              onmouseup="enableEditorRowDrag(this, false)">
                            <i class="ph ph-dots-six-vertical"></i>
                        </span>
                        <span class="msf-step-num">${stepNum}</span>
                        <input type="text"
                               class="msf-input"
                               style="font-weight: 700; flex: 1;"
                               value="${escapeHtml(step.name || `Step ${stepNum}`)}"
                               placeholder="Step Action Title"
                               oninput="onStepFieldChange(${idx}, 'name', this.value)">
                    </div>

                    <div class="msf-step-editor-actions">
                        <button type="button" class="msf-step-move-btn" ${idx === 0 ? 'disabled' : ''} onclick="moveModalStep(${idx}, -1)" title="Move Step Earlier">
                            <i class="ph ph-arrow-up"></i>
                        </button>
                        <button type="button" class="msf-step-move-btn" ${idx === steps.length - 1 ? 'disabled' : ''} onclick="moveModalStep(${idx}, 1)" title="Move Step Later">
                            <i class="ph ph-arrow-down"></i>
                        </button>
                        ${steps.length > 1 ? `
                        <button type="button" class="msf-btn msf-btn-danger-outline msf-btn-sm" style="height: 28px; padding: 0 8px;" onclick="removeModalStep(${idx})" title="Remove Step">
                            <i class="ph ph-trash"></i>
                        </button>` : ''}
                    </div>
                </div>

                <div class="msf-step-editor-fields">
                    <div class="msf-form-group">
                        <label class="msf-label">Origin Area (From)</label>
                        <select class="msf-select" onchange="onStepFieldChange(${idx}, 'from_area', this.value, true)">
                            ${fromOpts}
                        </select>
                    </div>
                    <div class="msf-step-fields-arrow">
                        <i class="ph ph-arrow-right"></i>
                    </div>
                    <div class="msf-form-group">
                        <label class="msf-label">Destination Area (To)</label>
                        <select class="msf-select" onchange="onStepFieldChange(${idx}, 'to_area', this.value, true)">
                            ${toOpts}
                        </select>
                    </div>
                </div>

                ${isBrokenAfter ? `
                <div class="msf-handoff-alert">
                    <i class="ph ph-warning"></i>
                    <span>Destination <strong>${escapeHtml(step.to_area)}</strong> does not match Step ${stepNum + 1} Origin <strong>${escapeHtml(steps[idx + 1].from_area)}</strong>.</span>
                </div>` : ''}

                <div class="msf-step-checks">
                    <button type="button" class="msf-scan-toggle-chip ${scanSrc ? 'active' : ''}" onclick="toggleEditorStepFlag(${idx}, 'scan_source')">
                        <i class="ph ${scanSrc ? 'ph-check-circle' : 'ph-circle'}"></i>
                        <span>Scan Source</span>
                    </button>
                    <button type="button" class="msf-scan-toggle-chip ${scanSku ? 'active' : ''}" onclick="toggleEditorStepFlag(${idx}, 'scan_product')">
                        <i class="ph ${scanSku ? 'ph-check-circle' : 'ph-circle'}"></i>
                        <span>Scan Product / SKU</span>
                    </button>
                    <button type="button" class="msf-scan-toggle-chip ${scanDest ? 'active' : ''}" onclick="toggleEditorStepFlag(${idx}, 'scan_destination')">
                        <i class="ph ${scanDest ? 'ph-check-circle' : 'ph-circle'}"></i>
                        <span>Scan Dest Area</span>
                    </button>
                    <button type="button" class="msf-scan-toggle-chip ${autoPrint ? 'active print' : ''}" onclick="toggleEditorStepFlag(${idx}, 'auto_print_label')">
                        <i class="ph ph-printer"></i>
                        <span>Auto-Print Label</span>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

window.enableEditorRowDrag = function(handleEl, enabled) {
    const row = handleEl ? handleEl.closest('.msf-step-editor-card') : null;
    if (!row) return;
    if (enabled) row.setAttribute('draggable', 'true');
    else row.removeAttribute('draggable');
};

window.onEditorStepDragStart = function(event, idx) {
    window.MsfStore.dragState = { type: 'editor-step', mtId: null, fromIndex: idx };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', `ed:${idx}`);
    const row = event.currentTarget;
    if (row) row.classList.add('is-dragging');
};

window.onEditorStepDragOver = function(event) {
    const t = window.MsfStore.dragState.type;
    if (t !== 'editor-step' && t !== 'preview-step') return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
};

window.onEditorStepDragEnter = function(event) {
    const t = window.MsfStore.dragState.type;
    if (t !== 'editor-step' && t !== 'preview-step') return;
    const row = event.currentTarget;
    if (row && Number(row.getAttribute('data-step-idx')) !== window.MsfStore.dragState.fromIndex) {
        row.classList.add('is-drag-over');
    }
};

window.onEditorStepDragLeave = function(event) {
    const row = event.currentTarget;
    if (row && !row.contains(event.relatedTarget)) {
        row.classList.remove('is-drag-over');
    }
};

window.onEditorStepDrop = function(event, targetIdx) {
    const t = window.MsfStore.dragState.type;
    if (t !== 'editor-step' && t !== 'preview-step') return;
    event.preventDefault();
    const fromIdx = window.MsfStore.dragState.fromIndex;
    if (fromIdx < 0 || fromIdx === targetIdx) return;

    reorderArray(window.MsfStore.editingModalSteps, fromIdx, targetIdx);
    renderModalSteps();
    updateModalRoutePreview();
    showMsfToast(`Moved step ${fromIdx + 1} to position ${targetIdx + 1}`);
};

window.onEditorStepDragEnd = function(event) {
    const row = event.currentTarget;
    if (row) row.removeAttribute('draggable');
    document.querySelectorAll('.msf-step-editor-card.is-dragging, .msf-step-editor-card.is-drag-over, .msf-step-node.is-dragging, .msf-step-node.is-drag-over').forEach(el => {
        el.classList.remove('is-dragging', 'is-drag-over');
    });
    window.MsfStore.dragState = { type: null, mtId: null, fromIndex: -1 };
};

window.onPreviewStepDragStart = function(event, idx) {
    window.MsfStore.dragState = { type: 'preview-step', mtId: null, fromIndex: idx };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', `pv:${idx}`);
    if (event.currentTarget) event.currentTarget.classList.add('is-dragging');
};

window.moveModalStep = function(idx, delta) {
    const target = idx + delta;
    const steps = window.MsfStore.editingModalSteps;
    if (target < 0 || target >= steps.length) return;
    reorderArray(steps, idx, target);
    renderModalSteps();
    updateModalRoutePreview();
};

window.toggleEditorStepFlag = function(idx, field) {
    const step = window.MsfStore.editingModalSteps[idx];
    if (!step) return;
    const current = field === 'scan_product' || field === 'scan_destination' ? step[field] !== false : !!step[field];
    step[field] = !current;
    renderModalSteps();
    updateModalRoutePreview();
};

window.onStepFieldChange = function(idx, field, value, recheckContinuity = false) {
    if (!window.MsfStore.editingModalSteps[idx]) return;
    window.MsfStore.editingModalSteps[idx][field] = value;
    if (recheckContinuity) {
        renderModalSteps();
    }
    updateModalRoutePreview();
};

window.autoChainModalSteps = function() {
    const fixed = autoChainStepsArray(window.MsfStore.editingModalSteps);
    renderModalSteps();
    updateModalRoutePreview();
    showMsfToast(fixed > 0 ? `Linked ${fixed} hand-off area${fixed === 1 ? '' : 's'} sequentially` : 'Route chain is already continuous');
};

window.addNewStepToModal = function() {
    const steps = window.MsfStore.editingModalSteps;
    const whId = document.getElementById('msfFormWarehouse') ? document.getElementById('msfFormWarehouse').value : 'WH-MAIN';
    const whAreas = window.MsfStore.storageAreas.filter(a => a.warehouse_id === whId);
    const storage = (whAreas.find(a => a.behavior === 'Storage') || {}).name || 'Stock Zone A (Dry Goods)';

    const lastStep = steps[steps.length - 1];
    const prevTo = lastStep ? lastStep.to_area : storage;

    steps.push({
        step_no: steps.length + 1,
        name: `Relocation Step ${steps.length + 1}`,
        from_area: prevTo,
        to_area: storage,
        scan_source: true,
        scan_product: true,
        scan_destination: true,
        auto_print_label: false,
    });

    renderModalSteps();
    updateModalRoutePreview();
};

window.removeModalStep = function(idx) {
    window.MsfStore.editingModalSteps.splice(idx, 1);
    renderModalSteps();
    updateModalRoutePreview();
};

// 10. Live 5-Per-Row Pipeline Preview & Cross-Tab Blueprint Sync
function updateModalRoutePreview() {
    const previewContainer = document.getElementById('msfModalRoutePreview');
    const steps = window.MsfStore.editingModalSteps || [];
    const continuity = checkRouteContinuity(steps);

    const nameVal = (document.getElementById('msfFormName')?.value || '').trim() || 'New Movement Type';
    const codeVal = (document.getElementById('msfFormCode')?.value || '').trim().toUpperCase() || 'NEW-CODE';
    const whId = document.getElementById('msfFormWarehouse')?.value || 'WH-MAIN';
    const whObj = window.MsfStore.warehouses.find(w => w.id === whId);
    const whCode = whObj ? whObj.code : whId;
    const kindVal = document.getElementById('msfFormKind')?.value || 'Inbound Receipt';
    const triggerVal = document.getElementById('msfFormTriggerDoc')?.value || defaultTriggerForMovement({ operation_kind: kindVal });
    const allocVal = document.getElementById('msfFormAllocation')?.value || 'FEFO (Earliest Expiry First)';
    const shortageVal = document.getElementById('msfFormShortagePolicy')?.value || 'Create Backorder';
    const isActive = document.getElementById('msfFormIsActive')?.checked !== false;
    const kindMeta = getKindMeta(kindVal);
    const cats = window.MsfStore.editingCategories || [];

    const totalScans = steps.reduce((acc, s) => {
        return acc
            + (s.scan_source ? 1 : 0)
            + (s.scan_product !== false ? 1 : 0)
            + (s.scan_destination !== false ? 1 : 0)
            + (s.auto_print_label ? 1 : 0);
    }, 0);

    // Update Hero Chips & Tab Badges
    const heroTitle = document.getElementById('msfModalTitle');
    if (heroTitle) heroTitle.textContent = nameVal;
    const heroCode = document.getElementById('msfHeroCodeChip');
    if (heroCode) heroCode.textContent = codeVal;
    const heroKind = document.getElementById('msfHeroKindChip');
    if (heroKind) {
        heroKind.className = `msf-kind-indicator ${kindMeta.cls}`;
        heroKind.innerHTML = `<span class="msf-kind-dot"></span><span>${escapeHtml(kindMeta.text)}</span>`;
    }
    const heroStatus = document.getElementById('msfHeroStatusPill');
    if (heroStatus) {
        heroStatus.className = `msf-status-pill ${isActive ? 'active' : 'inactive'}`;
        heroStatus.textContent = isActive ? 'Active' : 'Inactive';
    }
    const stepBadge = document.getElementById('msfTabStepCount');
    if (stepBadge) stepBadge.textContent = String(steps.length);
    const ruleBadge = document.getElementById('msfTabRuleCount');
    if (ruleBadge) ruleBadge.textContent = cats.length > 0 ? String(cats.length) : 'All';

    // Update Continuity Banners
    const applyContinuityBadge = (el) => {
        if (!el) return;
        el.className = `msf-chain-pill ${continuity.isContinuous ? 'ok' : 'broken'}`;
        el.innerHTML = continuity.isContinuous
            ? '<i class="ph ph-link"></i><span>Continuous Chain</span>'
            : `<i class="ph ph-warning"></i><span>${continuity.brokenAfterIndices.length} Handoff Break</span>`;
    };
    applyContinuityBadge(document.getElementById('msfHeroChainBadge'));
    applyContinuityBadge(document.getElementById('msfChainStatusBanner'));

    const autoBtn = document.getElementById('msfAutoChainBtn');
    if (autoBtn) {
        autoBtn.style.borderColor = continuity.isContinuous ? '#cbd5e1' : '#f59e0b';
        autoBtn.style.color = continuity.isContinuous ? '#334155' : '#b45309';
        autoBtn.style.background = continuity.isContinuous ? '#ffffff' : '#fffbeb';
    }

    // Sync Sidebars across all 3 Tabs
    const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setTxt('msfSummaryActivePill', isActive ? 'Active' : 'Inactive');
    setTxt('msfMetricStepCount', String(steps.length));
    setTxt('msfMetricScanCount', String(totalScans));
    setTxt('msfMetricChainState', continuity.isContinuous ? 'OK' : `${continuity.brokenAfterIndices.length} Break`);
    setTxt('msfSumCode', codeVal);
    setTxt('msfSumWarehouse', whCode);
    setTxt('msfSumKind', kindMeta.text);
    setTxt('msfSumEffect', kindMeta.effect);
    setTxt('msfSumTrigger', triggerVal);
    setTxt('msfSumCategories', cats.length > 0 ? cats.join(', ') : 'All Categories');
    setTxt('msfBreadcrumbCurrent', `${nameVal} (${codeVal})`);

    setTxt('msfSumSteps', `${steps.length} ${steps.length === 1 ? 'Step' : 'Steps'}`);
    setTxt('msfSumChain', continuity.isContinuous ? 'Continuous' : `${continuity.brokenAfterIndices.length} Break`);
    setTxt('msfDiagOrigin', steps[0]?.from_area || '—');
    setTxt('msfDiagDest', steps[steps.length - 1]?.to_area || '—');

    setTxt('msfRuleSumAlloc', allocVal);
    setTxt('msfRuleSumShortage', shortageVal);
    setTxt('msfRuleSumCats', cats.length > 0 ? `${cats.length} Selected` : 'All Categories');

    if (!previewContainer) return;

    if (steps.length === 0) {
        previewContainer.innerHTML = '<span style="font-size: 11.5px; color: #94a3b8;">Add steps below to see the 5-per-row live pipeline preview...</span>';
        return;
    }

    previewContainer.innerHTML = `
        <div class="msf-route-pipeline">
            ${steps.map((step, idx) => {
                const isBrokenAfter = continuity.brokenAfterIndices.includes(idx);
                return `
                    <div class="msf-step-node"
                         draggable="true"
                         data-step-idx="${idx}"
                         ondragstart="onPreviewStepDragStart(event, ${idx})"
                         ondragover="onEditorStepDragOver(event)"
                         ondragenter="onEditorStepDragEnter(event)"
                         ondragleave="onEditorStepDragLeave(event)"
                         ondrop="onEditorStepDrop(event, ${idx})"
                         ondragend="onEditorStepDragEnd(event)">
                        <div>
                            <div class="msf-step-node-head">
                                <div class="msf-step-title-group">
                                    <span class="msf-step-num">${idx + 1}</span>
                                    <span class="msf-step-name" title="${escapeHtml(step.name)}">${escapeHtml(step.name || `Step ${idx + 1}`)}</span>
                                </div>
                                <i class="ph ph-dots-six-vertical msf-step-drag-grip" title="Drag to reorder"></i>
                            </div>
                            <div class="msf-step-path-stack" style="margin-top: 6px;">
                                <div class="msf-step-area-row">
                                    <span class="msf-step-dir-tag">From</span>
                                    <span class="msf-step-area" title="${escapeHtml(step.from_area)}">${escapeHtml(step.from_area)}</span>
                                </div>
                                <div class="msf-step-area-row">
                                    <span class="msf-step-dir-tag">To</span>
                                    <span class="msf-step-area" title="${escapeHtml(step.to_area)}">${escapeHtml(step.to_area)}</span>
                                </div>
                            </div>
                        </div>
                        <span class="msf-step-connector ${isBrokenAfter ? 'is-broken' : ''}">
                            <i class="ph ${isBrokenAfter ? 'ph-warning' : 'ph-arrow-right'}"></i>
                        </span>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

window.updateLivePreview = function() {
    updateModalRoutePreview();
};

// 11. Save Movement Type & Configured Route
window.saveMovementType = function() {
    const id = document.getElementById('msfFormId').value;
    const name = (document.getElementById('msfFormName').value || '').trim();
    const code = (document.getElementById('msfFormCode').value || '').trim().toUpperCase();
    const warehouseId = document.getElementById('msfFormWarehouse').value;
    const kind = document.getElementById('msfFormKind').value;
    const triggerDoc = document.getElementById('msfFormTriggerDoc')?.value || defaultTriggerForMovement({ operation_kind: kind });
    const handlingNote = (document.getElementById('msfFormHandlingNote')?.value || '').trim();
    const isActive = document.getElementById('msfFormIsActive')?.checked !== false;
    const allocationStrategy = document.getElementById('msfFormAllocation')?.value || 'FEFO (Earliest Expiry First)';
    const shortagePolicy = document.getElementById('msfFormShortagePolicy')?.value || 'Create Backorder';
    const steps = (window.MsfStore.editingModalSteps || []).map((s, idx) => ({ ...s, step_no: idx + 1 }));
    const assignedCategories = [...(window.MsfStore.editingCategories || [])];

    if (!name || !code) {
        showMsfToast('Please provide both an Operation Name and Code', 'warning');
        switchMsfWorkspaceTab('general');
        return;
    }

    if (steps.length === 0) {
        showMsfToast('Please configure at least one routing step', 'warning');
        switchMsfWorkspaceTab('pipeline');
        return;
    }

    let stockEffect = 'Transfer';
    if (kind === 'Inbound Receipt') stockEffect = 'Receipt';
    else if (kind === 'Outbound Dispatch') stockEffect = 'Dispatch';
    else if (isAdjustmentKind(kind)) stockEffect = 'Adjustment';

    let routePreset = `${steps.length}-Step Route`;
    if (steps.length === 1) routePreset = '1-Step Direct';
    else if (steps.length === 2) routePreset = '2-Step Staged';
    else if (steps.length === 3) routePreset = '3-Step Quality Check';

    if (id) {
        const idx = window.MsfStore.movementTypes.findIndex(m => m.id === id);
        if (idx !== -1) {
            window.MsfStore.movementTypes[idx] = {
                ...window.MsfStore.movementTypes[idx],
                name,
                code,
                warehouse_id: warehouseId,
                operation_kind: kind,
                stock_effect: stockEffect,
                route_preset: routePreset,
                trigger_doc: triggerDoc,
                handling_note: handlingNote,
                allocation_strategy: allocationStrategy,
                shortage_policy: shortagePolicy,
                assigned_categories: assignedCategories,
                is_active: isActive,
                steps,
            };
        }
        showMsfToast(`Movement Type "${code}" updated successfully`);
    } else {
        const newRecord = {
            id: `MT-CUSTOM-${Date.now()}`,
            code,
            name,
            warehouse_id: warehouseId,
            operation_kind: kind,
            stock_effect: stockEffect,
            route_preset: routePreset,
            trigger_doc: triggerDoc,
            handling_note: handlingNote,
            allocation_strategy: allocationStrategy,
            shortage_policy: shortagePolicy,
            assigned_categories: assignedCategories,
            is_active: isActive,
            steps,
        };
        window.MsfStore.movementTypes.unshift(newRecord);
        showMsfToast(`Movement Type "${code}" created with ${steps.length} route step${steps.length === 1 ? '' : 's'}`);
    }

    persistMsfState();
    closeMovementTypeModal();
    renderKpis();
    renderMovementTypeCards();
};

// 12. Duplicate / Toggle Active / Delete
window.duplicateMovementType = function(id) {
    const src = window.MsfStore.movementTypes.find(m => m.id === id);
    if (!src) return;

    const clone = JSON.parse(JSON.stringify(src));
    const suffix = Date.now().toString().slice(-3);
    clone.id = `MT-CUSTOM-${Date.now()}`;
    clone.code = `${src.code}-CPY${suffix}`;
    clone.name = `${src.name} (Copy)`;
    clone.is_active = true;

    const srcIdx = window.MsfStore.movementTypes.findIndex(m => m.id === id);
    window.MsfStore.movementTypes.splice(srcIdx + 1, 0, clone);

    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast(`Cloned "${src.code}" as "${clone.code}"`);
};

window.duplicateEditingMovementType = function() {
    const id = document.getElementById('msfFormId')?.value;
    if (!id) return;
    closeMovementTypeModal();
    duplicateMovementType(id);
};

window.deleteEditingMovementType = function() {
    const id = document.getElementById('msfFormId')?.value;
    if (!id) return;
    const item = window.MsfStore.movementTypes.find(m => m.id === id);
    const label = item ? `${item.name} (${item.code})` : 'this movement type';
    if (!confirm(`Are you sure you want to delete "${label}"?`)) return;

    window.MsfStore.movementTypes = window.MsfStore.movementTypes.filter(m => m.id !== id);
    persistMsfState();
    closeMovementTypeModal();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast('Movement type removed', 'warning');
};

window.toggleMovementTypeActive = function(id) {
    const item = window.MsfStore.movementTypes.find(m => m.id === id);
    if (!item) return;

    item.is_active = item.is_active === false ? true : false;
    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast(`"${item.code}" set to ${item.is_active ? 'Active' : 'Inactive'}`);
};

window.deleteMovementType = function(id) {
    const item = window.MsfStore.movementTypes.find(m => m.id === id);
    const label = item ? `${item.name} (${item.code})` : 'this movement type';
    if (!confirm(`Are you sure you want to delete "${label}"?`)) return;

    window.MsfStore.movementTypes = window.MsfStore.movementTypes.filter(m => m.id !== id);
    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast('Movement type removed', 'warning');
};

// Bootstrap on DOMContentLoaded (or immediately if already parsed)
function initMsfModule() {
    hydrateMsfState();
    window.addEventListener('resize', syncMsfHeaderOffset);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('msfMovementTypeModal');
            if (drawer && drawer.classList.contains('is-open')) {
                closeMovementTypeModal();
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMsfModule);
} else {
    initMsfModule();
}
