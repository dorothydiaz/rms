/**
 * ==========================================================================
 * UOM CATEGORY DIRECTORY CONTROLLER WITH EXPANDABLE MEASUREMENT SUBTABLES
 * Strictly modeled after RFQ Directory & Status Tracker Table Architecture
 * ==========================================================================
 */
const config = window.UomConfig || {};
let UOM_DATA = config.initialUnits || [];
let UOM_CATEGORIES = config.initialCategories || [];

// Category Column Definitions (Modular Stacked Headers)
// Start Column (Category Info) and End Column (Actions) are permanently LOCKED!
const CATEGORY_COLUMNS = [
    {
        id: 'category_info',
        label: 'Category Info',
        subLabel: 'Code & Name',
        default: true,
        lockVisible: true, // LOCKED START COLUMN
        defaultWidth: '220px',
        align: 'left',
        topPurpose: 'Primary measurement category code',
        bottomPurpose: 'Descriptive name and physical dimension'
    },
    {
        id: 'base_uom',
        label: 'Reference Base Unit',
        subLabel: '1.0 Reference',
        default: true,
        lockVisible: false,
        defaultWidth: '170px',
        align: 'left',
        topPurpose: 'Base unit with 1.0 quantity multiplier',
        bottomPurpose: 'Standard reference dimension'
    },
    {
        id: 'units_count',
        label: 'Measurements',
        subLabel: 'Active / Total Count',
        default: true,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'left',
        topPurpose: 'Total number of units configured in this category',
        bottomPurpose: 'Active units available in transaction dropdowns'
    },
    {
        id: 'category_desc',
        label: 'Description & Scope',
        subLabel: 'Operational Purpose',
        default: true,
        lockVisible: false,
        defaultWidth: '240px',
        align: 'left',
        topPurpose: 'Detailed explanation of category use-cases',
        bottomPurpose: 'Culinary or procurement dimension'
    },
    {
        id: 'status',
        label: 'Category Status',
        subLabel: 'Active / Archived',
        default: true,
        lockVisible: false,
        defaultWidth: '130px',
        align: 'center',
        topPurpose: 'Category availability in ERP menus',
        bottomPurpose: 'Status switch toggle'
    },
    {
        id: 'audit_trail',
        label: 'Audit Trail',
        subLabel: 'Updated At / UUID',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topPurpose: 'Timestamp of last modification',
        bottomPurpose: 'Category unique identifier'
    }
];

// Active State
let searchQuery = '';
let currentPage = 1;
let pageSize = 10;
let visibleColumnIds = [];
let expandedCategoryCodes = new Set(['COUNT']); // Default COUNT expanded for rich first impression

// Initialize LocalStorage Column Configuration
function initColumnConfig() {
    const saved = localStorage.getItem('rms_uom_category_cols_v2');
    if (saved) {
        try {
            visibleColumnIds = JSON.parse(saved);
        } catch(e) {
            visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.default).map(c => c.id);
        }
    } else {
        visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.default).map(c => c.id);
    }
    // Guarantee locked start column is first
    if (!visibleColumnIds.includes('category_info')) {
        visibleColumnIds.unshift('category_info');
    }
}

function saveColumnConfig() {
    localStorage.setItem('rms_uom_category_cols_v2', JSON.stringify(visibleColumnIds));
    updateSettingsGearCounter();
    renderTableHeader();
    renderCategoryDirectory();
}

function resetColumnDefaults() {
    visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.default).map(c => c.id);
    saveColumnConfig();
    populateSettingsSwitchList();
    showToast("Columns reset to default view.", "success");
}

function toggleAllColumns(selectAll) {
    if (selectAll) {
        visibleColumnIds = CATEGORY_COLUMNS.map(c => c.id);
    } else {
        visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.lockVisible).map(c => c.id);
    }
    saveColumnConfig();
    populateSettingsSwitchList();
}

function updateSettingsGearCounter() {
    const counterEl = document.getElementById('uomColActiveCounter');
    if (counterEl) {
        // Count includes actions column
        counterEl.textContent = `${visibleColumnIds.length + 1} of ${CATEGORY_COLUMNS.length + 1} visible`;
    }
}

// --------------------------------------------------------------------------
// RENDER TABLE HEADERS (MODULAR STACKED FORMAT & SETTINGS GEAR)
// --------------------------------------------------------------------------
function renderTableHeader() {
    const tr = document.getElementById('uomTheadRow');
    if (!tr) return;
    tr.innerHTML = '';

    CATEGORY_COLUMNS.forEach(col => {
        if (!visibleColumnIds.includes(col.id)) return;

        const th = document.createElement('th');
        th.className = 'uom-th-stacked';
        th.style.width = col.defaultWidth || 'auto';
        th.style.textAlign = col.align || 'left';
        th.title = `${col.topPurpose} — ${col.bottomPurpose}`;

        th.innerHTML = `
            <div class="uom-th-content" style="align-items: ${col.align === 'center' ? 'center' : (col.align === 'right' ? 'flex-end' : 'flex-start')};">
                <span class="uom-th-title">${col.label}</span>
                <span class="uom-th-sub">${col.subLabel}</span>
            </div>
        `;
        tr.appendChild(th);
    });

    // End Column: Actions with Settings Gear (PERMANENTLY LOCKED)
    const actionTh = document.createElement('th');
    actionTh.className = 'uom-th-stacked';
    actionTh.style.width = '110px';
    actionTh.style.textAlign = 'center';
    actionTh.innerHTML = `
        <div class="uom-header-action-wrapper" style="justify-content: center;">
            <div class="uom-th-content" style="align-items: center;">
                <span class="uom-th-title">Actions</span>
                <span class="uom-th-sub">Manage</span>
            </div>
            <button type="button" class="uom-th-settings-btn" id="uomSettingsGearBtn" title="Customize Visible Table Columns" onclick="toggleSettingsDropdown(event)">
                <i class="ph ph-gear-six"></i>
            </button>
        </div>
    `;
    tr.appendChild(actionTh);
}

// --------------------------------------------------------------------------
// SETTINGS GEAR DROPDOWN (SWITCHABLE LIST)
// --------------------------------------------------------------------------
function toggleSettingsDropdown(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const dropdown = document.getElementById('uomTableSettingsDropdown');
    const gearBtn = document.getElementById('uomSettingsGearBtn');
    if (!dropdown || !gearBtn) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        gearBtn.classList.remove('is-active');
    } else {
        const rect = gearBtn.getBoundingClientRect();
        dropdown.style.top = `${rect.bottom + 6}px`;
        dropdown.style.left = `${Math.max(10, rect.right - 350)}px`;
        dropdown.classList.add('is-active');
        gearBtn.classList.add('is-active');
        populateSettingsSwitchList();
    }
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('uomTableSettingsDropdown');
    const gearBtn = document.getElementById('uomSettingsGearBtn');
    if (dropdown && dropdown.classList.contains('is-active')) {
        if (!dropdown.contains(e.target) && (!gearBtn || !gearBtn.contains(e.target))) {
            dropdown.classList.remove('is-active');
            if (gearBtn) gearBtn.classList.remove('is-active');
        }
    }
});

function populateSettingsSwitchList(filterText = '') {
    const list = document.getElementById('uomSettingsCheckboxesList');
    if (!list) return;
    list.innerHTML = '';

    const filter = (filterText || '').toLowerCase().trim();

    CATEGORY_COLUMNS.forEach(col => {
        if (filter && !col.label.toLowerCase().includes(filter) && !col.subLabel.toLowerCase().includes(filter)) {
            return;
        }

        const isChecked = visibleColumnIds.includes(col.id);
        const item = document.createElement('div');
        item.className = 'uom-settings-col-item';

        item.innerHTML = `
            <div class="uom-col-item-info">
                <span class="uom-col-item-title">${col.label}</span>
                <span class="uom-col-item-subtitle">${col.subLabel}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                ${col.lockVisible 
                    ? '<span style="font-size: 0.67rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Locked</span>' 
                    : `
                        <label class="uom-switch" title="Toggle column visibility">
                            <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="handleColumnSwitch('${col.id}', this.checked)">
                            <span class="uom-slider"></span>
                        </label>
                    `}
            </div>
        `;
        list.appendChild(item);
    });

    // Actions Header Item (Locked End Column)
    if (!filter || 'actions'.includes(filter)) {
        const actionItem = document.createElement('div');
        actionItem.className = 'uom-settings-col-item';
        actionItem.innerHTML = `
            <div class="uom-col-item-info">
                <span class="uom-col-item-title">Actions Column</span>
                <span class="uom-col-item-subtitle">Manage & Settings Gear</span>
            </div>
            <span style="font-size: 0.67rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Locked</span>
        `;
        list.appendChild(actionItem);
    }

    updateSettingsGearCounter();
}

function filterColumnSettingsList(val) {
    populateSettingsSwitchList(val);
}

function handleColumnSwitch(colId, checked) {
    if (checked) {
        if (!visibleColumnIds.includes(colId)) visibleColumnIds.push(colId);
    } else {
        visibleColumnIds = visibleColumnIds.filter(id => id !== colId);
    }
    saveColumnConfig();
}

// --------------------------------------------------------------------------
// EXPANSION CONTROLLER
// --------------------------------------------------------------------------
function toggleCategoryExpansion(catCode, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    if (expandedCategoryCodes.has(catCode)) {
        expandedCategoryCodes.delete(catCode);
    } else {
        expandedCategoryCodes.add(catCode);
    }
    renderCategoryDirectory();
}

// --------------------------------------------------------------------------
// RENDER TABLE ROWS (CATEGORY PRIMARY ROWS WITH EXPANDABLE SUBTABLES)
// --------------------------------------------------------------------------
function getFilteredCategories() {
    let list = [...UOM_CATEGORIES];

    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        list = list.filter(cat => {
            const matchesCat = (cat.category_code && cat.category_code.toLowerCase().includes(q)) ||
                               (cat.category_name && cat.category_name.toLowerCase().includes(q)) ||
                               (cat.category_description && cat.category_description.toLowerCase().includes(q));
            // Or if any unit under this category matches
            const matchingUnits = UOM_DATA.filter(u => u.uom_category === cat.category_code && (
                (u.uom_code && u.uom_code.toLowerCase().includes(q)) ||
                (u.uom_name && u.uom_name.toLowerCase().includes(q))
            ));
            return matchesCat || matchingUnits.length > 0;
        });
    }

    return list;
}

function renderCategoryDirectory() {
    const tbody = document.getElementById('uomTbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const filtered = getFilteredCategories();
    const total = filtered.length;

    // Pagination slice
    const startIdx = (currentPage - 1) * pageSize;
    const endIdx = Math.min(startIdx + pageSize, total);
    const paginated = filtered.slice(startIdx, endIdx);

    const visibleColsCount = visibleColumnIds.length + 1; // +1 for Actions

    if (paginated.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleColsCount}" style="text-align: center; padding: 48px 16px; color: var(--uom-text-muted);">
                    <i class="ph ph-folder" style="font-size: 34px; opacity: 0.35; display: block; margin-bottom: 8px;"></i>
                    <strong style="color: var(--uom-text-strong);">No measurement categories match your query.</strong>
                    <div style="font-size: 0.78rem; margin-top: 4px;">Click "New Category" above to create one.</div>
                </td>
            </tr>
        `;
        renderDirectoryPagination(0, 0, 0);
        return;
    }

    paginated.forEach(cat => {
        const isExpanded = expandedCategoryCodes.has(cat.category_code);
        const units = UOM_DATA.filter(u => u.uom_category === cat.category_code);
        const activeUnitsCount = units.filter(u => u.uom_is_active).length;
        const baseUnit = units.find(u => u.is_base_unit) || units[0] || null;
        const catBadgeClass = getCategoryBadgeClass(cat.category_code);

        // Render Master Category Row
        const tr = document.createElement('tr');
        let rowCells = '';

        visibleColumnIds.forEach(colId => {
            switch(colId) {
                case 'category_info':
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div style="display: flex; align-items: center;">
                                <button type="button" class="uom-row-expand-btn ${isExpanded ? 'is-expanded' : ''}" 
                                    onclick="toggleCategoryExpansion('${escapeHtml(cat.category_code)}', event)" 
                                    title="${isExpanded ? 'Collapse measurements' : 'Expand ' + units.length + ' measurements'}">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                                <div>
                                    <div class="uom-cell-top" style="display: flex; align-items: center; gap: 6px;">
                                        <span class="uom-badge ${catBadgeClass}" style="${!cat.category_is_active ? 'opacity: 0.6; filter: grayscale(0.6);' : ''}">${escapeHtml(cat.category_code)}</span>
                                        <span style="font-size: 0.8rem; font-weight: 700; ${!cat.category_is_active ? 'color: var(--uom-text-muted);' : ''}">${escapeHtml(cat.category_name)}</span>
                                        ${!cat.category_is_active ? '<span class="uom-badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.62rem; padding: 1px 5px;"><i class="ph ph-archive"></i> ARCHIVED</span>' : ''}
                                    </div>
                                    <div class="uom-cell-bottom" style="margin-left: 2px;">
                                        ${escapeHtml(cat.category_description || 'Standard Measurement Dimension')}
                                    </div>
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'base_uom':
                    rowCells += `
                        <td class="uom-td-stacked">
                            ${baseUnit ? `
                                <div class="uom-cell-top" style="display: flex; align-items: center; gap: 5px;">
                                    <span style="font-family: monospace; font-weight: 800; color: var(--uom-text-strong);">${escapeHtml(baseUnit.uom_code)}</span>
                                    <span class="uom-badge uom-badge-base"><i class="ph ph-star-fill"></i> BASE</span>
                                </div>
                                <div class="uom-cell-bottom">${escapeHtml(baseUnit.uom_name)}</div>
                            ` : `<span style="color: var(--uom-text-subtle);">None Configured</span>`}
                        </td>
                    `;
                    break;

                case 'units_count':
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div class="uom-cell-top" style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-weight: 800; font-size: 0.86rem; color: var(--uom-primary-dark);">${units.length} Units</span>
                            </div>
                            <div class="uom-cell-bottom">
                                <span class="uom-badge uom-badge-active" style="padding: 1px 6px; font-size: 0.66rem;">${activeUnitsCount} Active</span>
                            </div>
                        </td>
                    `;
                    break;

                case 'category_desc':
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div class="uom-cell-top" style="font-weight: 500; font-size: 0.77rem; color: var(--uom-text-medium);">
                                ${escapeHtml(cat.category_description || 'No special operational constraints.')}
                            </div>
                            <div class="uom-cell-bottom" style="font-size: 0.69rem;">Standard Ratio Calculation Basis</div>
                        </td>
                    `;
                    break;

                case 'status':
                    rowCells += `
                        <td class="uom-td-stacked" style="text-align: center;">
                            <label class="uom-switch" title="Toggle category status (Deactivating cascades to all units)">
                                <input type="checkbox" ${cat.category_is_active ? 'checked' : ''} onchange="toggleCategoryActive('${escapeHtml(cat.category_id || cat.category_code)}')">
                                <span class="uom-slider"></span>
                            </label>
                        </td>
                    `;
                    break;

                case 'audit_trail':
                    const updatedStr = cat.category_updated_at ? new Date(cat.category_updated_at).toLocaleDateString() : 'System';
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div class="uom-cell-top" style="font-size: 0.74rem;">${updatedStr}</div>
                            <div class="uom-cell-bottom" style="font-family: monospace; font-size: 0.65rem;" title="${cat.category_id}">ID: ${cat.category_id ? cat.category_id.substring(0, 8) : 'seed'}...</div>
                        </td>
                    `;
                    break;
            }
        });

        // Actions Column
        rowCells += `
            <td class="uom-td-stacked" style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <button type="button" class="uom-btn uom-btn-primary" style="padding: 4px 8px; font-size: 11px;" onclick="openNewUomDrawerForCategory('${escapeHtml(cat.category_code)}')" title="Add Measurement to ${cat.category_code}">
                        <i class="ph ph-plus"></i> <span>Add Unit</span>
                    </button>
                </div>
            </td>
        `;

        tr.innerHTML = rowCells;
        tbody.appendChild(tr);

        // Render Multi-Line Accordion Subtable (Expanded Measurements)
        if (isExpanded) {
            const expTr = document.createElement('tr');
            expTr.className = 'uom-line-expansion-row';
            expTr.innerHTML = `
                <td colspan="${visibleColsCount}" style="padding: 0;">
                    <div class="uom-subtable-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="font-weight: 700; font-size: 0.80rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-scales" style="color: var(--uom-primary);"></i>
                                <span>Measurement Units in <strong>${escapeHtml(cat.category_code)}</strong> (${units.length} registered)</span>
                            </div>
                            <button type="button" class="uom-btn uom-btn-secondary" style="padding: 3px 8px; font-size: 11px;" onclick="openNewUomDrawerForCategory('${escapeHtml(cat.category_code)}')">
                                <i class="ph ph-plus-circle"></i> <span>Add Unit to ${escapeHtml(cat.category_code)}</span>
                            </button>
                        </div>

                        ${units.length === 0 ? `
                            <div style="padding: 16px; text-align: center; color: var(--uom-text-muted); font-size: 0.77rem; background: #ffffff; border-radius: 8px; border: 1px dashed #e2e8f0;">
                                No units configured yet for ${escapeHtml(cat.category_code)}. Click "+ Add Unit" to define the reference unit.
                            </div>
                        ` : `
                            <table class="uom-subtable">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">UoM Code</th>
                                        <th>Descriptive Name</th>
                                        <th style="width: 130px;">Classification</th>
                                        <th style="width: 220px;">Quantity / Conversion Formula</th>
                                        <th style="width: 110px; text-align: right;">Multiplier</th>
                                        <th style="width: 90px; text-align: center;">Precision</th>
                                        <th style="width: 90px; text-align: center;">Dropdown</th>
                                        <th style="width: 80px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${units.map((uom, idx) => {
                                        const refCode = cat.base_uom_code || (baseUnit ? baseUnit.uom_code : 'Base');
                                        const formula = uom.is_base_unit 
                                            ? `1 ${uom.uom_code} (Category Reference)` 
                                            : `1 ${uom.uom_code} = ${formatNumber(uom.base_ratio)} ${refCode}`;

                                        return `
                                            <tr style="${!uom.uom_is_active ? 'opacity: 0.62; background: #fafafa;' : ''}">
                                                <td style="font-family: monospace; font-weight: 800; color: var(--uom-text-strong);">
                                                    <div style="display: flex; align-items: center; gap: 5px;">
                                                        <span style="${!uom.uom_is_active ? 'text-decoration: line-through; color: #94a3b8;' : ''}">${escapeHtml(uom.uom_code)}</span>
                                                        ${uom.is_base_unit ? '<span class="uom-badge uom-badge-base"><i class="ph ph-star-fill"></i> BASE</span>' : ''}
                                                        ${!uom.uom_is_active ? '<span class="uom-badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-size: 0.60rem; padding: 0 4px;">ARCHIVED</span>' : ''}
                                                    </div>
                                                </td>
                                                <td style="font-weight: 600; color: var(--uom-text-strong);">${escapeHtml(uom.uom_name)}</td>
                                                <td><span style="font-size: 0.72rem; color: #64748b;">${escapeHtml(uom.uom_type || 'STANDARD')}</span></td>
                                                <td style="color: var(--uom-primary-dark); font-weight: 700;">${formula}</td>
                                                <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">${formatMultiplier(uom.base_ratio)}x</td>
                                                <td style="text-align: center; font-size: 0.72rem; color: #64748b;">${uom.rounding_precision || '0.0100'}</td>
                                                <td style="text-align: center;">
                                                    <label class="uom-switch" title="Toggle active status">
                                                        <input type="checkbox" ${uom.uom_is_active ? 'checked' : ''} onchange="toggleUomActive('${uom.uom_id}')">
                                                        <span class="uom-slider"></span>
                                                    </label>
                                                </td>
                                                <td style="text-align: center;">
                                                    <button type="button" class="uom-btn uom-btn-secondary" style="padding: 2px 6px; font-size: 11px;" onclick="openEditUomDrawer('${uom.uom_id}')" title="Edit Unit Properties">
                                                        <i class="ph ph-pencil-simple"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        `}
                    </div>
                </td>
            `;
            tbody.appendChild(expTr);
        }
    });

    renderDirectoryPagination(startIdx + 1, endIdx, total);
}

function getCategoryBadgeClass(catCode) {
    switch(catCode) {
        case 'COUNT': return 'uom-badge-cat-count';
        case 'WEIGHT': return 'uom-badge-cat-weight';
        case 'VOLUME': return 'uom-badge-cat-volume';
        case 'PORTION': return 'uom-badge-cat-portion';
        case 'LENGTH': return 'uom-badge-cat-length';
        case 'AREA': return 'uom-badge-cat-area';
        case 'TIME': return 'uom-badge-cat-time';
        case 'OVERHEAD': return 'uom-badge-cat-overhead';
        default: return 'uom-badge-cat-custom';
    }
}

function formatNumber(num) {
    const n = Number(num);
    if (isNaN(n)) return '0';
    if (n % 1 === 0) return String(n);
    if (n < 0.001) return n.toFixed(6).replace(/\.?0+$/, '');
    return n.toFixed(4).replace(/\.?0+$/, '');
}

function formatMultiplier(num) {
    const n = Number(num);
    if (isNaN(n)) return '1';
    if (n % 1 === 0) return String(n);
    if (n < 0.001) return n.toFixed(6).replace(/\.?0+$/, '');
    return n.toFixed(4).replace(/\.?0+$/, '');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// --------------------------------------------------------------------------
// FOOTER PAGINATION
// --------------------------------------------------------------------------
function renderDirectoryPagination(start, end, total) {
    const info = document.getElementById('uomPaginationInfo');
    const container = document.getElementById('uomPaginationButtons');
    if (!info || !container) return;

    if (total === 0) {
        info.innerHTML = 'Showing <strong>0</strong> to <strong>0</strong> of <strong>0</strong> categories';
        container.innerHTML = '';
        return;
    }

    info.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${total}</strong> categories`;

    const totalPages = Math.ceil(total / pageSize);
    let buttonsHtml = '';

    buttonsHtml += `<button type="button" class="uom-page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})"><i class="ph ph-caret-left"></i></button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)) {
            buttonsHtml += `<button type="button" class="uom-page-btn ${p === currentPage ? 'is-active' : ''}" onclick="goToPage(${p})">${p}</button>`;
        } else if (p === currentPage - 2 || p === currentPage + 2) {
            buttonsHtml += `<span style="padding: 0 4px; color: #94a3b8;">...</span>`;
        }
    }

    buttonsHtml += `<button type="button" class="uom-page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})"><i class="ph ph-caret-right"></i></button>`;

    container.innerHTML = buttonsHtml;
}

function goToPage(p) {
    currentPage = p;
    renderCategoryDirectory();
}

function changePageSize(sz) {
    pageSize = parseInt(sz) || 10;
    currentPage = 1;
    renderCategoryDirectory();
}

function handleUomSearch(q) {
    searchQuery = q.trim();
    currentPage = 1;
    renderCategoryDirectory();
}

// --------------------------------------------------------------------------
// SLIDE-OUT DRAWER: ADD / EDIT UOM
// --------------------------------------------------------------------------
function openNewUomDrawer() {
    openNewUomDrawerForCategory('');
}

function openNewUomDrawerForCategory(catCode) {
    document.getElementById('uomDrawerTitle').textContent = 'Add Unit of Measurement';
    document.getElementById('formUomId').value = '';
    document.getElementById('formUomCode').value = '';
    document.getElementById('formUomCode').readOnly = false;
    document.getElementById('formUomName').value = '';
    document.getElementById('formIsBase').checked = false;
    document.getElementById('formBaseRatio').value = '1.0';
    document.getElementById('formPrecision').value = '0.0100';
    document.getElementById('formIsActive').checked = true;
    document.getElementById('uomSubmitBtnText').textContent = 'Save Measurement';

    if (catCode) {
        document.getElementById('formUomCategory').value = catCode;
    }

    handleIsBaseToggle(false);
    updateDrawerFormulaPreview();

    document.getElementById('uomDrawerOverlay').classList.add('is-open');
    document.getElementById('uomDrawer').classList.add('is-open');
    setTimeout(() => document.getElementById('formUomCode').focus(), 150);
}

function openEditUomDrawer(id) {
    const uom = UOM_DATA.find(u => u.uom_id === id);
    if (!uom) return;

    document.getElementById('uomDrawerTitle').textContent = `Edit Measurement: ${uom.uom_code}`;
    document.getElementById('formUomId').value = uom.uom_id;
    document.getElementById('formUomCode').value = uom.uom_code;
    document.getElementById('formUomCode').readOnly = false;
    document.getElementById('formUomCategory').value = uom.uom_category;
    document.getElementById('formUomName').value = uom.uom_name;
    document.getElementById('formIsBase').checked = !!uom.is_base_unit;
    document.getElementById('formBaseRatio').value = uom.base_ratio;
    document.getElementById('formPrecision').value = uom.rounding_precision || '0.0100';
    document.getElementById('formIsActive').checked = !!uom.uom_is_active;
    document.getElementById('uomSubmitBtnText').textContent = 'Update Measurement';

    handleIsBaseToggle(!!uom.is_base_unit);
    updateDrawerFormulaPreview();

    document.getElementById('uomDrawerOverlay').classList.add('is-open');
    document.getElementById('uomDrawer').classList.add('is-open');
}

function closeUomDrawer() {
    document.getElementById('uomDrawerOverlay').classList.remove('is-open');
    document.getElementById('uomDrawer').classList.remove('is-open');
}

function handleIsBaseToggle(isBase) {
    const ratioInput = document.getElementById('formBaseRatio');
    if (isBase) {
        ratioInput.value = '1.0';
        ratioInput.readOnly = true;
    } else {
        ratioInput.readOnly = false;
    }
    updateDrawerFormulaPreview();
}

function handleDrawerCategoryChange() {
    updateDrawerFormulaPreview();
}

function updateDrawerFormulaPreview() {
    const code = (document.getElementById('formUomCode')?.value || 'UNIT').toUpperCase().trim();
    const catCode = document.getElementById('formUomCategory')?.value || '';
    const ratio = parseFloat(document.getElementById('formBaseRatio')?.value || 1);
    const isBase = document.getElementById('formIsBase')?.checked;

    const catObj = UOM_CATEGORIES.find(c => c.category_code === catCode);
    const baseCode = catObj ? (catObj.base_uom_code || 'Base') : 'Base';

    const textEl = document.getElementById('uomFormulaPreviewText');
    if (!textEl) return;

    if (isBase) {
        textEl.textContent = `1 ${code} = Reference (Base Unit for ${catCode})`;
    } else {
        textEl.textContent = `1 ${code} = ${ratio} ${baseCode}`;
    }
}

async function handleSaveUom(e) {
    e.preventDefault();
    const btn = document.getElementById('uomSubmitBtn');
    btn.disabled = true;

    const id = document.getElementById('formUomId').value;
    const catVal = document.getElementById('formUomCategory').value;
    const payload = {
        uom_code: document.getElementById('formUomCode').value.toUpperCase().trim(),
        uom_name: document.getElementById('formUomName').value.trim(),
        uom_category: catVal,
        base_ratio: parseFloat(document.getElementById('formBaseRatio').value) || 1.0,
        is_base_unit: document.getElementById('formIsBase').checked,
        rounding_precision: parseFloat(document.getElementById('formPrecision').value) || 0.0100,
        uom_is_active: document.getElementById('formIsActive').checked,
        _token: (window.UomConfig && window.UomConfig.csrfToken) || ""
    };

    const isEdit = !!id;
    const url = isEdit ? `${window.UomConfig.routes.uomsApi}/${id}` : window.UomConfig.routes.uomsStore;
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const resp = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (window.UomConfig && window.UomConfig.csrfToken) || ''
            },
            body: JSON.stringify(payload)
        });

        const data = await resp.json();
        if (data.success) {
            showToast(data.message || 'Saved successfully!', 'success');
            closeUomDrawer();
            expandedCategoryCodes.add(catVal); // Auto-expand category where item was added
            await reloadDataFromServer();
        } else {
            showToast(data.message || 'Failed to save UoM.', 'danger');
        }
    } catch(err) {
        showToast('Network error while saving UoM.', 'danger');
    } finally {
        btn.disabled = false;
    }
}

async function toggleUomActive(id) {
    const uom = UOM_DATA.find(u => u.uom_id === id);
    if (!uom) return;

    // 1. Optimistic UI update: flip in-memory status immediately
    const prevStatus = uom.uom_is_active;
    uom.uom_is_active = !prevStatus;

    // Immediately update KPI counters
    const kpiActive = document.getElementById('kpiActiveUnits');
    if (kpiActive) kpiActive.textContent = UOM_DATA.filter(u => u.uom_is_active).length;

    // Immediately re-render without requiring page reload
    renderCategoryDirectory();

    try {
        const resp = await fetch(`${window.UomConfig.routes.uomsApi}/${id}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (window.UomConfig && window.UomConfig.csrfToken) || ''
            }
        });
        const data = await resp.json();
        if (data.success) {
            uom.uom_is_active = data.is_active;
            showToast(data.message, 'success');
            // Background sync with ?all=1 to ensure persisted state
            await reloadDataFromServer();
        } else {
            // Rollback optimistic update
            uom.uom_is_active = prevStatus;
            renderCategoryDirectory();
            showToast(data.message || 'Failed to update status.', 'danger');
        }
    } catch(err) {
        // Rollback optimistic update
        uom.uom_is_active = prevStatus;
        renderCategoryDirectory();
        showToast('Network error while toggling status.', 'danger');
    }
}

async function toggleCategoryActive(idOrCode) {
    const cat = UOM_CATEGORIES.find(c => c.category_id === idOrCode || c.category_code === idOrCode);
    if (!cat) return;

    // 1. Optimistic UI update: flip in-memory category state immediately
    const prevCatStatus = cat.category_is_active;
    const newCatStatus = !prevCatStatus;
    cat.category_is_active = newCatStatus;

    // Snapshot child unit statuses for possible rollback
    const childUnits = UOM_DATA.filter(u => u.uom_category === cat.category_code);
    const prevUnitStatuses = new Map();
    childUnits.forEach(u => {
        prevUnitStatuses.set(u.uom_id, u.uom_is_active);
        if (!newCatStatus) {
            // When turning off category, deactivate all child UOMs under it!
            u.uom_is_active = false;
        }
    });

    // Immediately update KPI counters
    const kpiActive = document.getElementById('kpiActiveUnits');
    if (kpiActive) kpiActive.textContent = UOM_DATA.filter(u => u.uom_is_active).length;

    // Immediately re-render table and open subtables without page reload
    renderCategoryDirectory();

    try {
        const targetId = cat.category_id || cat.category_code;
        const resp = await fetch(`${window.UomConfig.routes.uomCategoriesApi}/${targetId}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (window.UomConfig && window.UomConfig.csrfToken) || ''
            }
        });
        const data = await resp.json();
        if (data.success) {
            cat.category_is_active = data.is_active;
            showToast(data.message, 'success');
            // Background sync with ?all=1
            await reloadDataFromServer();
        } else {
            // Rollback optimistic state
            cat.category_is_active = prevCatStatus;
            childUnits.forEach(u => {
                if (prevUnitStatuses.has(u.uom_id)) {
                    u.uom_is_active = prevUnitStatuses.get(u.uom_id);
                }
            });
            renderCategoryDirectory();
            showToast(data.message || 'Failed to update category status.', 'danger');
        }
    } catch(err) {
        // Rollback optimistic state
        cat.category_is_active = prevCatStatus;
        childUnits.forEach(u => {
            if (prevUnitStatuses.has(u.uom_id)) {
                u.uom_is_active = prevUnitStatuses.get(u.uom_id);
            }
        });
        renderCategoryDirectory();
        showToast('Network error while toggling category status.', 'danger');
    }
}

// --------------------------------------------------------------------------
// MODAL: CREATE NEW CATEGORY
// --------------------------------------------------------------------------
function openNewCategoryModal() {
    document.getElementById('catCode').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catBase').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('uomCategoryModalOverlay').classList.add('is-open');
    document.getElementById('uomCategoryModal').classList.add('is-open');
    setTimeout(() => document.getElementById('catCode').focus(), 150);
}

function closeCategoryModal() {
    document.getElementById('uomCategoryModalOverlay').classList.remove('is-open');
    document.getElementById('uomCategoryModal').classList.remove('is-open');
}

async function handleSaveCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('catSubmitBtn');
    btn.disabled = true;

    const payload = {
        category_code: document.getElementById('catCode').value.toUpperCase().trim(),
        category_name: document.getElementById('catName').value.trim(),
        base_uom_code: document.getElementById('catBase').value.toUpperCase().trim(),
        category_description: document.getElementById('catDesc').value.trim(),
        _token: (window.UomConfig && window.UomConfig.csrfToken) || ""
    };

    try {
        const resp = await fetch(window.UomConfig.routes.uomCategoriesStore, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (window.UomConfig && window.UomConfig.csrfToken) || ''
            },
            body: JSON.stringify(payload)
        });

        const data = await resp.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeCategoryModal();
            expandedCategoryCodes.add(payload.category_code); // Auto-expand new category
            await reloadDataFromServer();

            // Select newly created category in drawer if open
            const formCatSelect = document.getElementById('formUomCategory');
            if (formCatSelect) {
                formCatSelect.value = payload.category_code;
                updateDrawerFormulaPreview();
            }
        } else {
            showToast(data.message || 'Failed to create category.', 'danger');
        }
    } catch(err) {
        showToast('Network error while creating category.', 'danger');
    } finally {
        btn.disabled = false;
    }
}

// --------------------------------------------------------------------------
// REFRESH DATA FROM BACKEND API
// --------------------------------------------------------------------------
async function reloadDataFromServer() {
    try {
        const resp = await fetch(`${window.UomConfig.routes.uomsList}?all=1`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (data.success) {
            UOM_DATA = data.units;
            UOM_CATEGORIES = data.categories;

            // Update KPI counters
            document.getElementById('kpiTotalUnits').textContent = UOM_DATA.length;
            document.getElementById('kpiActiveUnits').textContent = UOM_DATA.filter(u => u.uom_is_active).length;
            document.getElementById('kpiTotalCategories').textContent = UOM_CATEGORIES.length;
            document.getElementById('kpiBaseUnits').textContent = UOM_DATA.filter(u => u.is_base_unit).length;

            // Update Category Select in Drawer
            const formCat = document.getElementById('formUomCategory');
            if (formCat) {
                const curVal = formCat.value;
                formCat.innerHTML = UOM_CATEGORIES.map(c => 
                    `<option value="${c.category_code}" data-base="${c.base_uom_code || ''}">${c.category_code} (${c.category_name})</option>`
                ).join('');
                if (curVal) formCat.value = curVal;
            }

            // Invalidate UomRegistry cache and storage so all app dropdowns immediately reflect the active-only state
            if (window.UomRegistry) {
                window.UomRegistry.invalidateCache();
                window.UomRegistry.load(true);
            }
            try {
                sessionStorage.removeItem('rms_uom_registry_cache_v3');
                sessionStorage.removeItem('rms_uom_registry_cache_v2');
                sessionStorage.removeItem('rms_uom_registry_cache_v1');
            } catch(e) {}
            window.dispatchEvent(new CustomEvent('rms:uom-registry-invalidated'));

            renderCategoryDirectory();
        }
    } catch(e) {
        console.error('Error reloading UOM data', e);
    }
}

// --------------------------------------------------------------------------
// TOAST NOTIFICATIONS
// --------------------------------------------------------------------------
let toastTimeout;
function showToast(msg, type = 'success') {
    const toast = document.getElementById('uomToast');
    const text = document.getElementById('uomToastText');
    const icon = document.getElementById('uomToastIcon');
    if (!toast) return;

    clearTimeout(toastTimeout);
    toast.className = `uom-toast ${type} show`;
    text.textContent = msg;
    icon.className = type === 'success' ? 'ph ph-check-circle' : 'ph ph-warning-circle';

    toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 3200);
}

// Initial Boot
document.addEventListener('DOMContentLoaded', function() {
    initColumnConfig();
    renderTableHeader();
    renderCategoryDirectory();
});

// Expose functions to window for inline HTML event handlers & cross-module calls
Object.assign(window, {
    initColumnConfig,
    saveColumnConfig,
    resetColumnDefaults,
    toggleAllColumns,
    updateSettingsGearCounter,
    renderTableHeader,
    toggleSettingsDropdown,
    populateSettingsSwitchList,
    filterColumnSettingsList,
    handleColumnSwitch,
    toggleCategoryExpansion,
    getFilteredCategories,
    renderCategoryDirectory,
    getCategoryBadgeClass,
    formatNumber,
    formatMultiplier,
    escapeHtml,
    renderDirectoryPagination,
    goToPage,
    changePageSize,
    handleUomSearch,
    openNewUomDrawer,
    openNewUomDrawerForCategory,
    openEditUomDrawer,
    closeUomDrawer,
    handleIsBaseToggle,
    handleDrawerCategoryChange,
    updateDrawerFormulaPreview,
    handleSaveUom,
    toggleUomActive,
    toggleCategoryActive,
    openNewCategoryModal,
    closeCategoryModal,
    handleSaveCategory,
    reloadDataFromServer,
    showToast
});
