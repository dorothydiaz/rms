/**
 * Companies & Branches Management Module
 */

/**
 * 12-Column Granular Modular Table Definitions
 */
const DIRECTORY_COLUMNS = [
    {
        id: 'identity',
        label: 'Company Entity',
        subLabel: 'Code & Visual Identity',
        topPurpose: 'Unique identifier, entity code and brand logo',
        default: true,
        lockVisible: true,
        align: 'left',
        width: '180px',
        minWidth: '150px'
    },
    {
        id: 'legal_name',
        label: 'Legal & Trade Name',
        subLabel: 'Corporate Registered Title',
        topPurpose: 'Statutory registered business name and public trade name',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '240px',
        minWidth: '180px'
    },
    {
        id: 'entity_type',
        label: 'Classification',
        subLabel: 'Corporate Structure',
        topPurpose: 'Holding group, subsidiary, or standalone company type',
        default: true,
        lockVisible: false,
        align: 'center',
        width: '130px',
        minWidth: '110px'
    },
    {
        id: 'branch_pills',
        label: 'Attached Branches',
        subLabel: 'Operational Outlets & Pills',
        topPurpose: 'Connected physical stores, kiosks, and central commissaries',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '280px',
        minWidth: '220px'
    },
    {
        id: 'tax_tin',
        label: 'TIN & BIR Suffix',
        subLabel: 'Tax Registration & RDO',
        topPurpose: 'Bureau of Internal Revenue Tax Identification Number and Branch Code',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '180px',
        minWidth: '150px'
    },
    {
        id: 'base_currency',
        label: 'Currency',
        subLabel: 'Base Accounting Unit',
        topPurpose: 'Standard currency and fiscal timezone',
        default: false,
        lockVisible: false,
        align: 'center',
        width: '110px',
        minWidth: '90px'
    },
    {
        id: 'contact',
        label: 'Contact Info',
        subLabel: 'Phone & Administrator',
        topPurpose: 'Official phone number and primary administrator',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '160px',
        minWidth: '140px'
    },
    {
        id: 'web_domain',
        label: 'Digital Presence',
        subLabel: 'Domain & Website URL',
        topPurpose: 'Corporate email domain and external portal URL',
        default: false,
        lockVisible: false,
        align: 'left',
        width: '170px',
        minWidth: '140px'
    },
    {
        id: 'location',
        label: 'Headquarters',
        subLabel: 'City & Province',
        topPurpose: 'Principal office location and city',
        default: false,
        lockVisible: false,
        align: 'left',
        width: '170px',
        minWidth: '130px'
    },
    {
        id: 'brand_color',
        label: 'Brand Color',
        subLabel: 'Theme Identity',
        topPurpose: 'Primary brand accent color',
        default: false,
        lockVisible: false,
        align: 'center',
        width: '110px',
        minWidth: '90px'
    },
    {
        id: 'status',
        label: 'Operational Status',
        subLabel: 'Active Flag',
        topPurpose: 'System active status',
        default: true,
        lockVisible: false,
        align: 'center',
        width: '110px',
        minWidth: '95px'
    },
    {
        id: 'actions',
        label: 'Action',
        subLabel: 'Console Controls',
        topPurpose: 'View Company Details, context switch, and status controls',
        default: true,
        lockVisible: true,
        align: 'center',
        width: '140px',
        minWidth: '120px'
    }
];

// State & Persistent Storage Keys
const STORAGE_PREFS_KEY = 'rms_companies_table_prefs_v1';
let activeDirectoryColIds = [];
let directoryColOrder = [];
let directoryColWidths = {};
let directorySearchTerm = '';
let directoryColSearchTerm = '';
let currentDirectoryPage = 1;
let directoryPageSize = 10;

/**
 * Initialize Modular Table on Page Load
 */
document.addEventListener('DOMContentLoaded', function() {
    loadSavedDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();

    // Close settings dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('companiesTableSettingsDropdown');
        const trigger = document.getElementById('btnCompaniesColFilter');
        if (dropdown && !dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

/**
 * Load Preferences from LocalStorage
 */
function loadSavedDirectoryPreferences() {
    try {
        const raw = localStorage.getItem(STORAGE_PREFS_KEY);
        if (raw) {
            const parsed = JSON.parse(raw);
            activeDirectoryColIds = Array.isArray(parsed.activeIds) ? parsed.activeIds : null;
            directoryColOrder = Array.isArray(parsed.order) ? parsed.order : null;
            directoryColWidths = (parsed.widths && typeof parsed.widths === 'object') ? parsed.widths : {};
        }
    } catch(e) {
        console.warn('Could not parse table preferences:', e);
    }

    if (!directoryColOrder || directoryColOrder.length === 0) {
        directoryColOrder = DIRECTORY_COLUMNS.map(c => c.id);
    } else {
        // Ensure all registered columns are present
        DIRECTORY_COLUMNS.forEach(c => {
            if (!directoryColOrder.includes(c.id)) directoryColOrder.push(c.id);
        });
    }

    if (!activeDirectoryColIds || activeDirectoryColIds.length === 0) {
        activeDirectoryColIds = DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    }

    // Ensure locked columns are strictly visible
    DIRECTORY_COLUMNS.filter(c => c.lockVisible).forEach(c => {
        if (!activeDirectoryColIds.includes(c.id)) activeDirectoryColIds.push(c.id);
    });
}

/**
 * Save Preferences to LocalStorage
 */
function saveDirectoryPreferences() {
    try {
        localStorage.setItem(STORAGE_PREFS_KEY, JSON.stringify({
            activeIds: activeDirectoryColIds,
            order: directoryColOrder,
            widths: directoryColWidths
        }));
    } catch(e) {}
}

/**
 * Render Modular Table Thead
 */
function renderDirectoryTableHeader() {
    const theadRow = document.getElementById('companiesDirectoryTheadRow');
    if (!theadRow) return;

    const orderedVisibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let html = '';
    orderedVisibleCols.forEach(col => {
        const w = directoryColWidths[col.id] || col.width;
        const align = col.align === 'center' ? 'text-align: center;' : (col.align === 'right' ? 'text-align: right;' : 'text-align: left;');
        
        if (col.id === 'actions') {
            html += `
                <th style="width: ${w}; min-width: ${col.minWidth}; ${align} padding: 10px 12px; font-weight: 700; color: #475569; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span>Action</span>
                        <button type="button" onclick="toggleCompaniesTableSettingsDropdown(event)" style="border: none; background: none; color: #64748b; cursor: pointer; padding: 2px;" title="Customize Columns">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            html += `
                <th draggable="true" ondragstart="handleHeaderDragStart(event, '${col.id}')" ondragover="handleHeaderDragOver(event, '${col.id}')" ondrop="handleHeaderDrop(event, '${col.id}')"
                    style="width: ${w}; min-width: ${col.minWidth}; ${align} padding: 10px 12px; font-weight: 700; color: #475569; position: relative; cursor: grab;"
                    title="${col.topPurpose}">
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <span style="font-size: 12px; color: #1e293b; font-weight: 700;">${col.label}</span>
                        ${col.subLabel ? `<span style="font-size: 10px; color: #94a3b8; font-weight: 500;">${col.subLabel}</span>` : ''}
                    </div>
                    <div class="col-resizer" onmousedown="initColResize(event, '${col.id}')" style="position: absolute; right: 0; top: 0; bottom: 0; width: 5px; cursor: col-resize; user-select: none;"></div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = html;
    renderDirectoryColDropdownChecklist();
}

/**
 * Render Column Visibility Checkbox List in Settings Dropdown
 */
function renderDirectoryColDropdownChecklist() {
    const listEl = document.getElementById('directoryColCheckboxesList');
    if (!listEl) return;

    const query = (directoryColSearchTerm || '').toLowerCase().trim();
    const orderedCols = directoryColOrder.map(id => DIRECTORY_COLUMNS.find(c => c.id === id)).filter(Boolean);

    listEl.innerHTML = orderedCols
        .filter(col => {
            if (!query) return true;
            return col.label.toLowerCase().includes(query) || (col.subLabel && col.subLabel.toLowerCase().includes(query));
        })
        .map(col => {
            const isChecked = activeDirectoryColIds.includes(col.id);
            const isDisabled = col.lockVisible ? 'disabled' : '';

            return `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 4px 6px; border-radius: 6px; background: #f8fafc; font-size: 12px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; flex: 1; user-select: none;">
                        <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleDirectoryColVisibility('${col.id}', this.checked)" style="accent-color: #2563eb;">
                        <span style="color: #1e293b; font-weight: 600;">${col.label}</span>
                    </label>
                    ${col.lockVisible ? '<span style="font-size: 10px; color: #94a3b8; font-weight: 600;">(Locked)</span>' : ''}
                </div>
            `;
        }).join('');

    const counter = document.getElementById('companiesColActiveCounter');
    if (counter) {
        counter.textContent = `${activeDirectoryColIds.length} of ${DIRECTORY_COLUMNS.length} visible`;
    }
}

/**
 * Toggle Column Visibility
 */
function toggleDirectoryColVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activeDirectoryColIds.includes(colId)) activeDirectoryColIds.push(colId);
    } else {
        activeDirectoryColIds = activeDirectoryColIds.filter(id => id !== colId);
    }
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();
}

function showAllDirectoryColumns() {
    activeDirectoryColIds = DIRECTORY_COLUMNS.map(c => c.id);
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();
}

function resetDirectoryColumnDefaults() {
    activeDirectoryColIds = DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    directoryColOrder = DIRECTORY_COLUMNS.map(c => c.id);
    directoryColWidths = {};
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();
}

function resetDirectoryColumnWidths() {
    directoryColWidths = {};
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
}

function handleDirectoryColSearch(val) {
    directoryColSearchTerm = val;
    renderDirectoryColDropdownChecklist();
}

function toggleCompaniesTableSettingsDropdown(e) {
    if (e) e.stopPropagation();
    const dropdown = document.getElementById('companiesTableSettingsDropdown');
    if (!dropdown) return;
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

/**
 * Render Table Body with Dynamic Companies and Interactive Branch Pills
 */
function renderCompaniesDirectory() {
    const tbody = document.getElementById('companiesDirectoryTbody');
    if (!tbody) return;

    let items = Array.isArray(window.COMPANIES_DATA) ? [...window.COMPANIES_DATA] : [];

    // Filter by live search
    if (directorySearchTerm) {
        const q = directorySearchTerm.toLowerCase();
        items = items.filter(c => {
            const branchesStr = (c.branches || []).map(b => b.branch_name + ' ' + b.branch_code).join(' ').toLowerCase();
            return (c.company_code && c.company_code.toLowerCase().includes(q)) ||
                   (c.company_legal_name && c.company_legal_name.toLowerCase().includes(q)) ||
                   (c.company_trade_name && c.company_trade_name.toLowerCase().includes(q)) ||
                   (c.company_tax_id && c.company_tax_id.toLowerCase().includes(q)) ||
                   (c.company_city && c.company_city.toLowerCase().includes(q)) ||
                   (c.company_contact_person && c.company_contact_person.toLowerCase().includes(q)) ||
                   branchesStr.includes(q);
        });
    }

    // Pagination slicing
    const totalCount = items.length;
    const startIdx = (currentDirectoryPage - 1) * directoryPageSize;
    const endIdx = startIdx + directoryPageSize;
    const pageItems = items.slice(startIdx, endIdx);

    const orderedVisibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    if (pageItems.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${orderedVisibleCols.length}" style="padding: 36px; text-align: center; color: #94a3b8;">
                    <i class="ph ph-magnifying-glass" style="font-size: 28px; margin-bottom: 6px;"></i>
                    <p style="margin: 0; font-size: 13px;">No companies matching "${escapeHtml(directorySearchTerm)}"</p>
                </td>
            </tr>
        `;
        renderPaginationBar(0, 0, 0);
        return;
    }

    let rowsHtml = '';
    pageItems.forEach(company => {
        rowsHtml += `<tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">`;

        orderedVisibleCols.forEach(col => {
            rowsHtml += `<td style="padding: 10px 12px; vertical-align: middle;">`;

            switch(col.id) {
                case 'identity':
                    const logoSrc = company.company_logo ? `/storage/${company.company_logo}` : null;
                    rowsHtml += `
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                ${logoSrc ? `<img src="${logoSrc}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">` : `<i class="ph ph-buildings" style="color: #94a3b8; font-size: 18px;"></i>`}
                            </div>
                            <div>
                                <span style="font-size: 12px; font-weight: 700; color: #2563eb; font-family: monospace;">${escapeHtml(company.company_code)}</span>
                                <div style="display: flex; align-items: center; gap: 4px; margin-top: 1px;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: ${company.company_is_active ? '#10b981' : '#ef4444'};"></span>
                                    <span style="font-size: 10.5px; color: #64748b;">${company.company_is_active ? 'Active' : 'Inactive'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    break;

                case 'legal_name':
                    rowsHtml += `
                        <div style="max-height: 44px; overflow: hidden; line-height: 1.3;">
                            <a href="javascript:void(0)" onclick="selectCompanyForDetails(${company.company_id})" style="font-size: 12.5px; font-weight: 700; color: #0f172a; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;" title="${escapeHtml(company.company_legal_name)}" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#0f172a'">
                                ${escapeHtml(company.company_legal_name)}
                            </a>
                            ${company.company_trade_name ? `<div style="font-size: 11px; color: #64748b; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;" title="DBA: ${escapeHtml(company.company_trade_name)}">DBA: ${escapeHtml(company.company_trade_name)}</div>` : ''}
                        </div>
                    `;
                    break;

                case 'entity_type':
                    const badgeBg = company.company_type === 'Holding Enterprise' ? '#f5f3ff' : '#eff6ff';
                    const badgeColor = company.company_type === 'Holding Enterprise' ? '#7c3aed' : '#2563eb';
                    rowsHtml += `
                        <div style="text-align: center;">
                            <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor};">
                                ${escapeHtml(company.company_type || 'Company')}
                            </span>
                        </div>
                    `;
                    break;

                case 'branch_pills':
                    const branches = company.branches || [];
                    if (branches.length === 0) {
                        rowsHtml += `<span style="font-size: 11px; color: #94a3b8; font-style: italic;">No branches linked</span>`;
                    } else {
                        const maxPills = 2;
                        const visiblePills = branches.slice(0, maxPills);
                        const remainder = branches.length - maxPills;

                        rowsHtml += `<div class="branch-pills-wrap">`;
                        visiblePills.forEach(b => {
                            rowsHtml += `
                                <span class="branch-pill" title="${escapeHtml(b.branch_name)} (${b.branch_code}) - ${b.branch_type}">
                                    <span style="width: 5px; height: 5px; border-radius: 50%; background: ${b.branch_is_active ? '#10b981' : '#ef4444'}; flex-shrink: 0;"></span>
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(b.branch_name)}</span>
                                </span>
                            `;
                        });
                        if (remainder > 0) {
                            const extraNames = escapeHtml(branches.slice(maxPills).map(b => b.branch_name).join(', '));
                            rowsHtml += `
                                <span class="branch-pill-more" onclick="event.stopPropagation(); selectCompanyForDetails(${company.company_id})" title="${extraNames}">
                                    (+${remainder})
                                </span>
                            `;
                        }
                        rowsHtml += `</div>`;
                    }
                    break;

                case 'tax_tin':
                    const tinDisplay = company.company_tax_id ? (company.company_branch_code ? `${company.company_tax_id}-${company.company_branch_code}` : company.company_tax_id) : 'Not Registered';
                    const isVat = company.company_is_vat_registered || company.company_tax_type === 'VAT Registered';
                    rowsHtml += `
                        <div>
                            <span style="font-size: 11.5px; font-family: monospace; font-weight: 600; color: #1e293b;">${escapeHtml(tinDisplay)}</span>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 1px;">
                                ${company.company_rdo_code ? `<span style="font-size: 10.5px; color: #64748b;">RDO: ${escapeHtml(company.company_rdo_code)}</span>` : ''}
                                <span style="font-size: 10px; font-weight: 600; padding: 1px 5px; border-radius: 4px; background: ${isVat ? '#ecfdf5' : '#f1f5f9'}; color: ${isVat ? '#059669' : '#64748b'};">
                                    ${isVat ? 'VAT' : 'Non-VAT'}
                                </span>
                            </div>
                        </div>
                    `;
                    break;

                case 'base_currency':
                    rowsHtml += `
                        <div style="text-align: center;">
                            <span style="font-size: 11.5px; font-weight: 700; color: #0f172a;">${escapeHtml(company.company_base_currency_code || 'PHP')}</span>
                        </div>
                    `;
                    break;

                case 'contact':
                    rowsHtml += `
                        <div style="font-size: 11.5px;">
                            ${company.company_phone_number ? `<div style="color: #1e293b;"><i class="ph ph-phone" style="color: #64748b;"></i> ${escapeHtml(company.company_phone_number)}</div>` : ''}
                            ${company.company_email_address ? `<div style="color: #64748b;"><i class="ph ph-envelope" style="color: #64748b;"></i> ${escapeHtml(company.company_email_address)}</div>` : ''}
                        </div>
                    `;
                    break;

                case 'web_domain':
                    rowsHtml += `
                        <div style="font-size: 11.5px;">
                            ${company.company_email_domain ? `<span style="color: #2563eb; font-weight: 600;">@${escapeHtml(company.company_email_domain)}</span>` : ''}
                            ${company.company_website_url ? `<div style="color: #64748b;"><a href="${escapeHtml(company.company_website_url)}" target="_blank" style="color: #64748b; text-decoration: none;"><i class="ph ph-globe"></i> Website</a></div>` : ''}
                        </div>
                    `;
                    break;

                case 'location':
                    const loc = [company.company_city, company.company_state_province_code, company.company_country_code].filter(Boolean).join(', ');
                    rowsHtml += `<span style="font-size: 11.5px; color: #475569;">${escapeHtml(loc || 'Philippines')}</span>`;
                    break;

                case 'brand_color':
                    const colorHex = company.company_brand_color || '#ef4444';
                    rowsHtml += `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <span style="width: 14px; height: 14px; border-radius: 50%; background-color: ${colorHex}; display: inline-block;"></span>
                            <span style="font-size: 11px; font-family: monospace; color: #64748b;">${colorHex}</span>
                        </div>
                    `;
                    break;

                case 'status':
                    rowsHtml += `
                        <div style="text-align: center;">
                            <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; background: ${company.company_is_active ? '#ecfdf5' : '#fee2e2'}; color: ${company.company_is_active ? '#059669' : '#dc2626'};">
                                ${company.company_is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    `;
                    break;

                case 'actions':
                    rowsHtml += `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <button type="button" onclick="selectCompanyForDetails(${company.company_id})" style="border: 1px solid #bfdbfe; background: #eff6ff; color: #2563eb; font-size: 11.5px; font-weight: 600; padding: 4px 9px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;" title="View & Edit Company Details">
                                <i class="ph ph-arrow-right"></i> Details
                            </button>
                            <button type="button" onclick="confirmDeleteCompany(${company.company_id}, '${escapeHtml(company.company_legal_name).replace(/'/g, "\\'")}')" style="border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; font-size: 11.5px; font-weight: 600; padding: 4px 8px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;" title="Delete Company">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    `;
                    break;
            }

            rowsHtml += `</td>`;
        });

        rowsHtml += `</tr>`;
    });

    tbody.innerHTML = rowsHtml;
    renderPaginationBar(startIdx + 1, Math.min(endIdx, totalCount), totalCount);
}

/**
 * Render Pagination Controls
 */
function renderPaginationBar(start, end, total) {
    const info = document.getElementById('companiesPaginationInfo');
    if (info) {
        info.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${total}</strong> companies`;
    }

    const controls = document.getElementById('companiesPaginationControls');
    if (!controls) return;

    const totalPages = Math.ceil(total / directoryPageSize) || 1;
    let btns = `
        <button type="button" onclick="changeDirectoryPage(${currentDirectoryPage - 1})" ${currentDirectoryPage <= 1 ? 'disabled' : ''} style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 11px; padding: 3px 8px; border-radius: 6px; cursor: pointer;">
            Prev
        </button>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - currentDirectoryPage) > 2 && p !== 1 && p !== totalPages) {
            continue;
        }
        btns += `
            <button type="button" onclick="changeDirectoryPage(${p})" style="border: 1px solid ${p === currentDirectoryPage ? '#2563eb' : '#cbd5e1'}; background: ${p === currentDirectoryPage ? '#2563eb' : '#ffffff'}; color: ${p === currentDirectoryPage ? '#ffffff' : '#334155'}; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; cursor: pointer;">
                ${p}
            </button>
        `;
    }

    btns += `
        <button type="button" onclick="changeDirectoryPage(${currentDirectoryPage + 1})" ${currentDirectoryPage >= totalPages ? 'disabled' : ''} style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 11px; padding: 3px 8px; border-radius: 6px; cursor: pointer;">
            Next
        </button>
    `;

    controls.innerHTML = btns;
}

function changeDirectoryPage(page) {
    const totalPages = Math.ceil((window.COMPANIES_DATA || []).length / directoryPageSize) || 1;
    if (page < 1 || page > totalPages) return;
    currentDirectoryPage = page;
    renderCompaniesDirectory();
}

function changeDirectoryPageSize(size) {
    directoryPageSize = parseInt(size, 10) || 10;
    currentDirectoryPage = 1;
    renderCompaniesDirectory();
}

function handleCompaniesDirectorySearch(val) {
    directorySearchTerm = val;
    currentDirectoryPage = 1;
    renderCompaniesDirectory();
}

/**
 * View Mode Switching: Directory vs Company Details
 */
function switchViewMode(mode) {
    const dirSec = document.getElementById('sectionViewDirectory');
    const detSec = document.getElementById('sectionViewDetails');
    const pills = document.querySelectorAll('.view-mode-pill');

    if (mode === 'details') {
        if (dirSec) dirSec.style.display = 'none';
        if (detSec) detSec.style.display = 'block';
        if (pills[0]) { pills[0].style.background = 'transparent'; pills[0].style.color = '#64748b'; pills[0].style.boxShadow = 'none'; }
        if (pills[1]) { pills[1].style.background = '#ffffff'; pills[1].style.color = '#0f172a'; pills[1].style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)'; }
        window.history.replaceState(null, '', `?view=details${window.SELECTED_COMPANY_ID ? '&company_id=' + window.SELECTED_COMPANY_ID : ''}`);
    } else {
        if (dirSec) dirSec.style.display = 'block';
        if (detSec) detSec.style.display = 'none';
        if (pills[0]) { pills[0].style.background = '#ffffff'; pills[0].style.color = '#0f172a'; pills[0].style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)'; }
        if (pills[1]) { pills[1].style.background = 'transparent'; pills[1].style.color = '#64748b'; pills[1].style.boxShadow = 'none'; }
        window.history.replaceState(null, '', '?view=directory');
    }
}

function selectCompanyForDetails(companyId) {
    window.location.href = `${window.CompaniesBranchesConfig?.indexUrl || '/config/companies-branches'}?view=details&company_id=${companyId}`;
}

/**
 * Company Sub-tab Switching (General Info, Branches, Vault, Hierarchy)
 */
function switchCompanySubTab(tab) {
    const panes = {
        'general': document.getElementById('companyTabPaneGeneral'),
        'branches': document.getElementById('companyTabPaneBranches'),
        'vault': document.getElementById('companyTabPaneVault'),
        'hierarchy': document.getElementById('companyTabPaneHierarchy')
    };

    const btns = {
        'general': document.getElementById('tabBtnGeneral'),
        'branches': document.getElementById('tabBtnBranches'),
        'vault': document.getElementById('tabBtnVault'),
        'hierarchy': document.getElementById('tabBtnHierarchy')
    };

    Object.keys(panes).forEach(k => {
        if (panes[k]) panes[k].style.display = (k === tab ? 'block' : 'none');
        if (btns[k]) {
            if (k === tab) {
                btns[k].style.color = '#2563eb';
                btns[k].style.borderBottomColor = '#2563eb';
            } else {
                btns[k].style.color = '#64748b';
                btns[k].style.borderBottomColor = 'transparent';
            }
        }
    });
}

/**
 * Logo Preview Handling
 */
function handleLogoFileSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('companyLogoPreviewImg');
            const placeholder = document.getElementById('companyLogoPlaceholder');
            if (placeholder) placeholder.style.display = 'none';
            if (!img) {
                img = document.createElement('img');
                img.id = 'companyLogoPreviewImg';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
                input.parentElement.appendChild(img);
            }
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Brand Color Swatch Picker Handling
 */
function handleBrandColorChange(color) {
    const hexLabel = document.getElementById('brandColorHexLabel');
    if (hexLabel) hexLabel.textContent = color;
    const dot = document.getElementById('headerBrandColorDot');
    if (dot) {
        dot.style.backgroundColor = color;
        dot.style.boxShadow = `0 0 0 2px #ffffff, 0 0 0 3px ${color}`;
    }
}

function selectBrandColorPreset(color) {
    const picker = document.getElementById('companyBrandColorPicker');
    if (picker) {
        picker.value = color;
        handleBrandColorChange(color);
    }
}

/**
 * Modal Open/Close Controls
 */
function openCreateCompanyModal() {
    const m = document.getElementById('modalCreateCompany');
    if (m) m.style.display = 'flex';
}
function closeCreateCompanyModal() {
    const m = document.getElementById('modalCreateCompany');
    if (m) m.style.display = 'none';
}

function openCreateBranchModal(companyId) {
    const m = document.getElementById('modalCreateBranch');
    const select = document.getElementById('branchModalCompanySelect');
    if (select && companyId) select.value = companyId;
    if (m) m.style.display = 'flex';
}
function closeCreateBranchModal() {
    const m = document.getElementById('modalCreateBranch');
    if (m) m.style.display = 'none';
}

let currentEditingBranch = null;

function editBranch(branch) {
    currentEditingBranch = branch;
    const m = document.getElementById('modalEditBranch');
    const form = document.getElementById('editBranchForm');
    if (!m || !form) return;

    form.action = `/config/business-settings/branches/${branch.branch_id}`;
    document.getElementById('editBranchCode').value = branch.branch_code || '';
    document.getElementById('editBranchName').value = branch.branch_name || '';
    document.getElementById('editBranchType').value = branch.branch_type || 'Store / Dine-In';
    document.getElementById('editBranchTaxCode').value = branch.branch_tax_code || '';
    document.getElementById('editBranchCity').value = branch.branch_city || '';
    document.getElementById('editBranchHours').value = branch.operating_hours || '';

    m.style.display = 'flex';
}

function closeEditBranchModal() {
    currentEditingBranch = null;
    const m = document.getElementById('modalEditBranch');
    if (m) m.style.display = 'none';
}

function handleDeleteFromEditBranchModal() {
    if (!currentEditingBranch) return;
    const bId = currentEditingBranch.branch_id;
    const bName = currentEditingBranch.branch_name;
    closeEditBranchModal();
    confirmDeleteBranch(bId, bName);
}

function confirmDeleteCompany(companyId, companyName) {
    const modal = document.getElementById('modalConfirmDeleteCompany');
    const form = document.getElementById('deleteCompanyForm');
    const nameEl = document.getElementById('deleteCompanyTargetName');
    if (!modal || !form) return;

    form.action = `/config/companies-branches/${companyId}`;
    if (nameEl) nameEl.textContent = companyName || 'this company';
    modal.style.display = 'flex';
}

function closeConfirmDeleteCompanyModal() {
    const modal = document.getElementById('modalConfirmDeleteCompany');
    if (modal) modal.style.display = 'none';
}

function confirmDeleteBranch(branchId, branchName) {
    const modal = document.getElementById('modalConfirmDeleteBranch');
    const form = document.getElementById('deleteBranchForm');
    const nameEl = document.getElementById('deleteBranchTargetName');
    if (!modal || !form) return;

    form.action = `/config/companies-branches/branches/${branchId}`;
    if (nameEl) nameEl.textContent = branchName || 'this branch';
    modal.style.display = 'flex';
}

function closeConfirmDeleteBranchModal() {
    const modal = document.getElementById('modalConfirmDeleteBranch');
    if (modal) modal.style.display = 'none';
}

/**
 * Drag and drop column sequence handlers
 */
let draggedColId = null;
function handleHeaderDragStart(e, id) {
    draggedColId = id;
    e.dataTransfer.effectAllowed = 'move';
}
function handleHeaderDragOver(e, id) {
    e.preventDefault();
}
function handleHeaderDrop(e, targetId) {
    e.preventDefault();
    if (!draggedColId || draggedColId === targetId) return;

    const fromIdx = directoryColOrder.indexOf(draggedColId);
    const toIdx = directoryColOrder.indexOf(targetId);
    if (fromIdx !== -1 && toIdx !== -1) {
        directoryColOrder.splice(fromIdx, 1);
        directoryColOrder.splice(toIdx, 0, draggedColId);
        saveDirectoryPreferences();
        renderDirectoryTableHeader();
        renderCompaniesDirectory();
    }
    draggedColId = null;
}

/**
 * Column Resizing handler
 */
function initColResize(e, colId) {
    e.stopPropagation();
    const th = e.target.parentElement;
    const startX = e.clientX;
    const startWidth = th.offsetWidth;

    function onMouseMove(ev) {
        const newWidth = Math.max(80, startWidth + (ev.clientX - startX));
        directoryColWidths[colId] = newWidth + 'px';
        th.style.width = newWidth + 'px';
    }

    function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        saveDirectoryPreferences();
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Global window exposure
Object.assign(window, {
    loadSavedDirectoryPreferences,
    saveDirectoryPreferences,
    renderDirectoryTableHeader,
    renderDirectoryColDropdownChecklist,
    toggleDirectoryColVisibility,
    showAllDirectoryColumns,
    resetDirectoryColumnDefaults,
    resetDirectoryColumnWidths,
    handleDirectoryColSearch,
    toggleCompaniesTableSettingsDropdown,
    renderCompaniesDirectory,
    renderPaginationBar,
    changeDirectoryPage,
    changeDirectoryPageSize,
    handleCompaniesDirectorySearch,
    switchViewMode,
    selectCompanyForDetails,
    switchCompanySubTab,
    handleLogoFileSelect,
    handleBrandColorChange,
    selectBrandColorPreset,
    openCreateCompanyModal,
    closeCreateCompanyModal,
    openCreateBranchModal,
    closeCreateBranchModal,
    editBranch,
    closeEditBranchModal,
    handleDeleteFromEditBranchModal,
    confirmDeleteCompany,
    closeConfirmDeleteCompanyModal,
    confirmDeleteBranch,
    closeConfirmDeleteBranchModal,
    handleHeaderDragStart,
    handleHeaderDragOver,
    handleHeaderDrop,
    initColResize,
    escapeHtml,
});
