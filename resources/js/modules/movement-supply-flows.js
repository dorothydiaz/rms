/**
 * ==========================================================================
 * MOVEMENT TYPES & SUPPLY ROUTES ENGINE (INVENTORY CONFIGURATION)
 * Unified Single-Entity ERP Model:
 * Each Movement Type directly contains and executes its configured Supply Route.
 * ==========================================================================
 */

const MT_STORAGE_KEY = 'rms_inv_movement_types_v2';
const WH_STORAGE_KEY = 'rms_inv_warehouses_v1';
const AREA_STORAGE_KEY = 'rms_inv_storage_areas_v1';

window.MsfStore = {
    warehouses: [],
    storageAreas: [],
    boundaryAreas: [],
    movementTypes: [],
    filterWarehouse: 'ALL',
    filterKind: 'ALL',
    searchQuery: '',
    editingModalSteps: [],
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

    // Check if savedMt has modern steps format
    const hasValidSteps = Array.isArray(savedMt) && savedMt.length > 0 && Array.isArray(savedMt[0].steps);
    if (hasValidSteps) {
        window.MsfStore.movementTypes = savedMt;
    } else {
        window.MsfStore.movementTypes = cfg.initialMovementTypes || [];
        persistMsfState();
    }

    renderWarehouseFilterPills();
    renderKpis();
    renderMovementTypeCards();
}

function persistMsfState() {
    try {
        localStorage.setItem(MT_STORAGE_KEY, JSON.stringify(window.MsfStore.movementTypes));
    } catch (e) {}
}

// 2. Warehouse Filter Pills
function renderWarehouseFilterPills() {
    const container = document.getElementById('msfWarehousePills');
    if (!container) return;

    let html = `<button type="button" class="msf-pill-btn ${window.MsfStore.filterWarehouse === 'ALL' ? 'active' : ''}" data-wh="ALL" onclick="filterByWarehouse('ALL')">All Warehouses</button>`;

    window.MsfStore.warehouses.forEach(wh => {
        const isActive = window.MsfStore.filterWarehouse === wh.id;
        html += `<button type="button" class="msf-pill-btn ${isActive ? 'active' : ''}" data-wh="${escapeHtml(wh.id)}" onclick="filterByWarehouse('${escapeHtml(wh.id)}')">${escapeHtml(wh.code)}</button>`;
    });

    container.innerHTML = html;
}

// 3. Top KPI Cards
function renderKpis() {
    const types = window.MsfStore.movementTypes;
    const activeTypes = types.filter(t => t.is_active !== false);

    const kpiTotal = document.getElementById('msfKpiTotalTypes');
    if (kpiTotal) kpiTotal.textContent = `${activeTypes.length} Active`;

    const inboundCount = activeTypes.filter(t => t.operation_kind === 'Inbound Receipt').length;
    const outboundCount = activeTypes.filter(t => t.operation_kind === 'Outbound Dispatch').length;
    const transferCount = activeTypes.filter(t => t.operation_kind === 'Internal Transfer').length;
    const scrapCount = activeTypes.filter(t => t.operation_kind === 'Adjustment / Scrap').length;

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
}

// 4. Filters & Search Handlers
window.filterByWarehouse = function(whCode) {
    window.MsfStore.filterWarehouse = whCode;
    renderWarehouseFilterPills();
    renderMovementTypeCards();
};

window.filterByKind = function(kind) {
    window.MsfStore.filterKind = kind;
    const container = document.getElementById('msfKindPills');
    if (container) {
        container.querySelectorAll('.msf-pill-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-kind') === kind);
        });
    }
    renderMovementTypeCards();
};

window.onSearchInput = function(query) {
    window.MsfStore.searchQuery = (query || '').toLowerCase().trim();
    renderMovementTypeCards();
};

// Helper: Get Badge Class for Operation Kind
function getKindBadge(kind) {
    switch (kind) {
        case 'Inbound Receipt':
            return { cls: 'msf-badge-inbound', icon: 'ph-arrow-down-left', text: 'Inbound Receipt' };
        case 'Outbound Dispatch':
            return { cls: 'msf-badge-outbound', icon: 'ph-arrow-up-right', text: 'Outbound Dispatch' };
        case 'Internal Transfer':
            return { cls: 'msf-badge-transfer', icon: 'ph-arrows-left-right', text: 'Internal Transfer' };
        case 'Adjustment / Scrap':
            return { cls: 'msf-badge-adjustment', icon: 'ph-trash', text: 'Adjustment / Scrap' };
        default:
            return { cls: 'msf-badge-transfer', icon: 'ph-arrows-clockwise', text: kind };
    }
}

// 5. Render Movement Type Cards Grid
function renderMovementTypeCards() {
    const container = document.getElementById('msfCardsContainer');
    if (!container) return;

    const { filterWarehouse, filterKind, searchQuery, movementTypes, warehouses } = window.MsfStore;

    const filtered = movementTypes.filter(mt => {
        if (filterWarehouse !== 'ALL' && mt.warehouse_id !== filterWarehouse) return false;
        if (filterKind !== 'ALL' && mt.operation_kind !== filterKind) return false;
        if (searchQuery) {
            const code = (mt.code || '').toLowerCase();
            const name = (mt.name || '').toLowerCase();
            const kind = (mt.operation_kind || '').toLowerCase();
            const stepMatches = (mt.steps || []).some(s =>
                (s.name || '').toLowerCase().includes(searchQuery) ||
                (s.from_area || '').toLowerCase().includes(searchQuery) ||
                (s.to_area || '').toLowerCase().includes(searchQuery)
            );
            if (!code.includes(searchQuery) && !name.includes(searchQuery) && !kind.includes(searchQuery) && !stepMatches) {
                return false;
            }
        }
        return true;
    });

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="msf-empty-box">
                <i class="ph ph-barcode msf-empty-icon"></i>
                <div style="font-weight: 700; font-size: 14px; color: #334155;">No Movement Types Found</div>
                <p style="font-size: 12px; margin: 4px 0 14px 0;">No movement types match your current warehouse or operation filter.</p>
                <button type="button" class="msf-btn msf-btn-primary msf-btn-sm" onclick="openMovementTypeModal()">
                    <i class="ph ph-plus"></i> Create New Movement Type
                </button>
            </div>
        `;
        return;
    }

    let html = '';

    filtered.forEach(mt => {
        const wh = warehouses.find(w => w.id === mt.warehouse_id);
        const whName = wh ? wh.code : (mt.warehouse_id || 'WH-MAIN');
        const kindBadge = getKindBadge(mt.operation_kind);
        const isActive = mt.is_active !== false;
        const steps = Array.isArray(mt.steps) ? mt.steps : [];
        const presetTag = mt.route_preset || `${steps.length}-Step Route`;

        html += `
            <div class="msf-card ${!isActive ? 'inactive' : ''}" data-id="${escapeHtml(mt.id)}">
                <!-- Card Header -->
                <div class="msf-card-header">
                    <div class="msf-card-head-left">
                        <span class="msf-card-code">${escapeHtml(mt.code)}</span>
                        <h3 class="msf-card-title">${escapeHtml(mt.name)}</h3>
                        <span class="msf-badge msf-badge-wh">
                            <i class="ph ph-warehouse"></i> ${escapeHtml(whName)}
                        </span>
                        <span class="msf-badge ${kindBadge.cls}">
                            <i class="ph ${kindBadge.icon}"></i> ${escapeHtml(kindBadge.text)}
                        </span>
                    </div>
                    <div class="msf-card-head-actions">
                        <!-- Active Toggle -->
                        <div class="msf-toggle-wrap" onclick="toggleMovementTypeActive('${escapeHtml(mt.id)}')" title="Toggle Active Status">
                            <span class="msf-toggle ${isActive ? 'checked' : ''}"></span>
                            <span>${isActive ? 'Active' : 'Inactive'}</span>
                        </div>
                        <!-- Configure Route Button -->
                        <button type="button" class="msf-btn msf-btn-outline msf-btn-sm" onclick="openMovementTypeModal('${escapeHtml(mt.id)}')">
                            <i class="ph ph-sliders"></i>
                            <span>Configure Route</span>
                        </button>
                        <!-- Delete Button (Only for custom types) -->
                        ${mt.id.startsWith('MT-CUSTOM') ? `
                        <button type="button" class="msf-btn msf-btn-danger-outline msf-btn-sm" onclick="deleteMovementType('${escapeHtml(mt.id)}')" title="Delete Movement Type">
                            <i class="ph ph-trash"></i>
                        </button>` : ''}
                    </div>
                </div>

                <!-- Card Body: Visual Route Ribbon -->
                <div class="msf-card-body">
                    <div class="msf-route-meta">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="msf-route-tag"><i class="ph ph-git-fork"></i> ${escapeHtml(presetTag)}</span>
                            <span>${steps.length} Sequential Route ${steps.length === 1 ? 'Step' : 'Steps'}</span>
                        </div>
                        <span style="font-size: 11px; color: #64748b;">
                            <i class="ph ph-check-circle"></i> Handheld scanner validated
                        </span>
                    </div>

                    <!-- Steps Ribbon -->
                    <div class="msf-route-pipeline">
        `;

        steps.forEach((step, idx) => {
            const stepNum = step.step_no || (idx + 1);
            const scanSource = !!step.scan_source;
            const scanSku = !!step.scan_product;
            const scanDest = !!step.scan_destination;
            const autoPrint = !!step.auto_print_label;

            html += `
                <div class="msf-step-node">
                    <div class="msf-step-node-head">
                        <span class="msf-step-num">${stepNum}</span>
                        <span class="msf-step-name" title="${escapeHtml(step.name)}">${escapeHtml(step.name || `Step ${stepNum}`)}</span>
                    </div>
                    <div class="msf-step-path">
                        <span class="msf-step-area" title="${escapeHtml(step.from_area)}">${escapeHtml(step.from_area)}</span>
                        <i class="ph ph-arrow-right" style="color: #94a3b8; font-size: 10px;"></i>
                        <span class="msf-step-area" title="${escapeHtml(step.to_area)}">${escapeHtml(step.to_area)}</span>
                    </div>
                    <div class="msf-step-scans">
                        <span class="msf-scan-badge ${scanSource ? 'active' : ''}">Source Scan: ${scanSource ? 'Yes' : 'No'}</span>
                        <span class="msf-scan-badge ${scanSku ? 'active' : ''}">Product/SKU: ${scanSku ? 'Yes' : 'No'}</span>
                        <span class="msf-scan-badge ${scanDest ? 'active' : ''}">Dest Scan: ${scanDest ? 'Yes' : 'No'}</span>
                        ${autoPrint ? '<span class="msf-scan-badge print"><i class="ph ph-printer"></i> Auto-Label</span>' : ''}
                    </div>
                </div>
            `;

            if (idx < steps.length - 1) {
                html += `
                    <div class="msf-route-arrow">
                        <i class="ph ph-arrow-right"></i>
                    </div>
                `;
            }
        });

        html += `
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// 6. Modal Operations
function getAreaOptionsForWarehouse(whId) {
    const physical = window.MsfStore.storageAreas.filter(a => a.warehouse_id === whId && a.is_active !== false);
    const boundary = window.MsfStore.boundaryAreas;

    const list = [];
    // Boundary areas first
    boundary.forEach(b => {
        list.push({ label: `${b.name} (Virtual Boundary)`, val: b.name });
    });
    // Physical warehouse areas
    physical.forEach(p => {
        list.push({ label: `${p.name} [${p.behavior}]`, val: p.name });
    });

    return list;
}

window.openMovementTypeModal = function(id = null) {
    const modal = document.getElementById('msfMovementTypeModal');
    if (!modal) return;

    // Populate Warehouse Dropdown
    const whSelect = document.getElementById('msfFormWarehouse');
    if (whSelect) {
        whSelect.innerHTML = window.MsfStore.warehouses.map(w => 
            `<option value="${escapeHtml(w.id)}">${escapeHtml(w.code)} — ${escapeHtml(w.name)}</option>`
        ).join('');
    }

    if (id) {
        // Edit existing movement type
        const mt = window.MsfStore.movementTypes.find(m => m.id === id);
        if (!mt) return;

        document.getElementById('msfModalTitle').textContent = `Edit Movement Type: ${mt.code}`;
        document.getElementById('msfFormId').value = mt.id;
        document.getElementById('msfFormName').value = mt.name || '';
        document.getElementById('msfFormCode').value = mt.code || '';
        if (whSelect) whSelect.value = mt.warehouse_id || 'WH-MAIN';
        document.getElementById('msfFormKind').value = mt.operation_kind || 'Inbound Receipt';

        window.MsfStore.editingModalSteps = JSON.parse(JSON.stringify(mt.steps || []));
    } else {
        // Create new movement type
        const newCode = `WH-MAIN-${Date.now().toString().slice(-4)}`;
        document.getElementById('msfModalTitle').textContent = 'Create New Movement Type & Route';
        document.getElementById('msfFormId').value = '';
        document.getElementById('msfFormName').value = '';
        document.getElementById('msfFormCode').value = newCode;
        if (whSelect) whSelect.value = window.MsfStore.filterWarehouse !== 'ALL' ? window.MsfStore.filterWarehouse : 'WH-MAIN';
        document.getElementById('msfFormKind').value = 'Inbound Receipt';

        // Apply default 2-Step Staged preset
        applyRoutePreset('2-Step Staged', false);
    }

    renderModalSteps();
    updateModalRoutePreview();
    modal.style.display = 'flex';
};

window.closeMovementTypeModal = function() {
    const modal = document.getElementById('msfMovementTypeModal');
    if (modal) modal.style.display = 'none';
};

window.onModalOverlayClick = function(event) {
    if (event.target.id === 'msfMovementTypeModal') {
        closeMovementTypeModal();
    }
};

window.onModalWarehouseChange = function() {
    renderModalSteps();
    updateModalRoutePreview();
};

window.onModalKindChange = function() {
    const kind = document.getElementById('msfFormKind').value;
    if (kind === 'Inbound Receipt') {
        applyRoutePreset('2-Step Staged');
    } else if (kind === 'Outbound Dispatch') {
        applyRoutePreset('2-Step Staged');
    } else if (kind === 'Internal Transfer') {
        applyRoutePreset('1-Step Direct');
    } else if (kind === 'Adjustment / Scrap') {
        applyRoutePreset('1-Step Direct');
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
            window.MsfStore.editingModalSteps = [
                {
                    step_no: 1,
                    name: 'Direct Receipt to Storage',
                    from_area: 'Supplier / Vendor Origin (Boundary)',
                    to_area: storage,
                    scan_source: false,
                    scan_product: true,
                    scan_destination: true,
                    auto_print_label: true,
                }
            ];
        } else if (kind === 'Outbound Dispatch') {
            window.MsfStore.editingModalSteps = [
                {
                    step_no: 1,
                    name: 'Direct Dispatch from Storage',
                    from_area: storage,
                    to_area: 'Customer / Branch Destination (Boundary)',
                    scan_source: true,
                    scan_product: true,
                    scan_destination: false,
                    auto_print_label: true,
                }
            ];
        } else if (kind === 'Internal Transfer') {
            window.MsfStore.editingModalSteps = [
                {
                    step_no: 1,
                    name: 'Direct Relocation between Areas',
                    from_area: storage,
                    to_area: staging,
                    scan_source: true,
                    scan_product: true,
                    scan_destination: true,
                    auto_print_label: false,
                }
            ];
        } else {
            window.MsfStore.editingModalSteps = [
                {
                    step_no: 1,
                    name: 'Scrap Write-Off',
                    from_area: storage,
                    to_area: 'Scrap / Spoilage & Write-Off (Boundary)',
                    scan_source: true,
                    scan_product: true,
                    scan_destination: false,
                    auto_print_label: false,
                }
            ];
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
                }
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
                }
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
            }
        ];
    }

    if (render) {
        renderModalSteps();
        updateModalRoutePreview();
        showMsfToast(`Applied "${presetName}" Route Preset`);
    }
};

// 8. Render Steps inside Modal Editor
function renderModalSteps() {
    const container = document.getElementById('msfModalStepsContainer');
    if (!container) return;

    const whId = document.getElementById('msfFormWarehouse') ? document.getElementById('msfFormWarehouse').value : 'WH-MAIN';
    const areaOptions = getAreaOptionsForWarehouse(whId);

    const steps = window.MsfStore.editingModalSteps;
    if (steps.length === 0) {
        container.innerHTML = `
            <div style="padding: 16px; text-align: center; color: #64748b; font-size: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px;">
                No steps configured for this route yet. Click "Add Next Routing Step" or choose a Quick Preset above.
            </div>
        `;
        return;
    }

    let html = '';

    steps.forEach((step, idx) => {
        const stepNum = idx + 1;
        step.step_no = stepNum;

        // Build from_area options
        const fromOpts = areaOptions.map(opt => 
            `<option value="${escapeHtml(opt.val)}" ${step.from_area === opt.val ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
        ).join('');

        // Build to_area options
        const toOpts = areaOptions.map(opt => 
            `<option value="${escapeHtml(opt.val)}" ${step.to_area === opt.val ? 'selected' : ''}>${escapeHtml(opt.label)}</option>`
        ).join('');

        html += `
            <div class="msf-step-editor-card" data-step-idx="${idx}">
                <div class="msf-step-editor-head">
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        <span class="msf-step-num">${stepNum}</span>
                        <input type="text" class="msf-input" style="height: 28px; font-weight: 700; flex: 1;" value="${escapeHtml(step.name || `Step ${stepNum}`)}" placeholder="Step Title" oninput="onStepFieldChange(${idx}, 'name', this.value)">
                    </div>
                    ${steps.length > 1 ? `
                    <button type="button" class="msf-btn msf-btn-danger-outline msf-btn-sm" style="padding: 3px 6px;" onclick="removeModalStep(${idx})" title="Remove Step">
                        <i class="ph ph-trash"></i>
                    </button>` : ''}
                </div>

                <div class="msf-step-editor-fields">
                    <div class="msf-form-group">
                        <label class="msf-label" style="font-size: 11px;">From Area (Origin)</label>
                        <select class="msf-select" style="height: 28px; font-size: 11.5px;" onchange="onStepFieldChange(${idx}, 'from_area', this.value)">
                            ${fromOpts}
                        </select>
                    </div>
                    <div class="msf-form-group">
                        <label class="msf-label" style="font-size: 11px;">To Area (Destination)</label>
                        <select class="msf-select" style="height: 28px; font-size: 11.5px;" onchange="onStepFieldChange(${idx}, 'to_area', this.value)">
                            ${toOpts}
                        </select>
                    </div>
                </div>

                <div class="msf-step-checks">
                    <label class="msf-check-label">
                        <input type="checkbox" ${step.scan_source ? 'checked' : ''} onchange="onStepFieldChange(${idx}, 'scan_source', this.checked)">
                        <span>Scan Source Barcode</span>
                    </label>
                    <label class="msf-check-label">
                        <input type="checkbox" ${step.scan_product !== false ? 'checked' : ''} onchange="onStepFieldChange(${idx}, 'scan_product', this.checked)">
                        <span>Scan Product / SKU</span>
                    </label>
                    <label class="msf-check-label">
                        <input type="checkbox" ${step.scan_destination !== false ? 'checked' : ''} onchange="onStepFieldChange(${idx}, 'scan_destination', this.checked)">
                        <span>Scan Dest Area Barcode</span>
                    </label>
                    <label class="msf-check-label">
                        <input type="checkbox" ${step.auto_print_label ? 'checked' : ''} onchange="onStepFieldChange(${idx}, 'auto_print_label', this.checked)">
                        <span>Auto-Print Labels</span>
                    </label>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

window.onStepFieldChange = function(idx, field, value) {
    if (!window.MsfStore.editingModalSteps[idx]) return;
    window.MsfStore.editingModalSteps[idx][field] = value;
    updateModalRoutePreview();
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

// 9. Live Route Preview inside Modal
function updateModalRoutePreview() {
    const container = document.getElementById('msfModalRoutePreview');
    if (!container) return;

    const steps = window.MsfStore.editingModalSteps;
    if (steps.length === 0) {
        container.innerHTML = '<span style="font-size: 11.5px; color: #94a3b8;">Add steps to see the live routing pipeline preview...</span>';
        return;
    }

    let html = '<div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">';

    steps.forEach((step, idx) => {
        html += `
            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; font-size: 11px; display: flex; align-items: center; gap: 6px;">
                <span style="font-weight: 800; color: #7c3aed;">Step ${idx + 1}:</span>
                <span style="font-weight: 600; color: #0f172a;">${escapeHtml(step.name || 'Untitled')}</span>
                <span style="color: #64748b; font-size: 10px;">(${escapeHtml(step.from_area)} ➔ ${escapeHtml(step.to_area)})</span>
            </div>
        `;
        if (idx < steps.length - 1) {
            html += '<i class="ph ph-arrow-right" style="color: #94a3b8; font-size: 12px;"></i>';
        }
    });

    html += '</div>';
    container.innerHTML = html;
}

window.updateLivePreview = function() {
    updateModalRoutePreview();
};

// 10. Save Movement Type & Configured Route
window.saveMovementType = function() {
    const id = document.getElementById('msfFormId').value;
    const name = (document.getElementById('msfFormName').value || '').trim();
    const code = (document.getElementById('msfFormCode').value || '').trim().toUpperCase();
    const warehouseId = document.getElementById('msfFormWarehouse').value;
    const kind = document.getElementById('msfFormKind').value;
    const steps = window.MsfStore.editingModalSteps;

    if (!name || !code) {
        showMsfToast('Please provide both an Operation Name and Code', 'warning');
        return;
    }

    if (!steps || steps.length === 0) {
        showMsfToast('Please configure at least one routing step', 'warning');
        return;
    }

    // Determine stock effect from kind
    let stockEffect = 'Transfer';
    if (kind === 'Inbound Receipt') stockEffect = 'Receipt';
    else if (kind === 'Outbound Dispatch') stockEffect = 'Dispatch';
    else if (kind === 'Adjustment / Scrap') stockEffect = 'Scrap';

    let routePreset = `${steps.length}-Step Route`;
    if (steps.length === 1) routePreset = '1-Step Direct';
    else if (steps.length === 2) routePreset = '2-Step Staged';
    else if (steps.length === 3) routePreset = '3-Step Quality Check';

    if (id) {
        // Update existing
        const idx = window.MsfStore.movementTypes.findIndex(m => m.id === id);
        if (idx !== -1) {
            window.MsfStore.movementTypes[idx].name = name;
            window.MsfStore.movementTypes[idx].code = code;
            window.MsfStore.movementTypes[idx].warehouse_id = warehouseId;
            window.MsfStore.movementTypes[idx].operation_kind = kind;
            window.MsfStore.movementTypes[idx].stock_effect = stockEffect;
            window.MsfStore.movementTypes[idx].route_preset = routePreset;
            window.MsfStore.movementTypes[idx].steps = steps;
        }
        showMsfToast(`Movement Type "${code}" updated successfully`);
    } else {
        // Create new
        const newId = `MT-CUSTOM-${Date.now()}`;
        const newRecord = {
            id: newId,
            code: code,
            name: name,
            warehouse_id: warehouseId,
            operation_kind: kind,
            stock_effect: stockEffect,
            route_preset: routePreset,
            is_active: true,
            steps: steps,
        };
        window.MsfStore.movementTypes.unshift(newRecord);
        showMsfToast(`Movement Type "${code}" created with ${steps.length} Route steps`);
    }

    persistMsfState();
    closeMovementTypeModal();
    renderKpis();
    renderMovementTypeCards();
};

// 11. Toggle Active
window.toggleMovementTypeActive = function(id) {
    const item = window.MsfStore.movementTypes.find(m => m.id === id);
    if (!item) return;

    item.is_active = item.is_active === false ? true : false;
    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast(`"${item.code}" set to ${item.is_active ? 'Active' : 'Inactive'}`);
};

// 12. Delete Movement Type
window.deleteMovementType = function(id) {
    if (!confirm('Are you sure you want to delete this custom movement type?')) return;
    window.MsfStore.movementTypes = window.MsfStore.movementTypes.filter(m => m.id !== id);
    persistMsfState();
    renderKpis();
    renderMovementTypeCards();
    showMsfToast('Movement type deleted', 'warning');
};

// Bootstrap on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    hydrateMsfState();
});
