// Raw server data payload
const ALL_EMPLOYEES = window.HrWageDistortionConfig?.allEmployees || [];
const FORMULAS_CONFIG = window.HrWageDistortionConfig?.formulasConfig || {};

// Application State
let state = {
    selectedEmpIds: new Set(),
    filteredEmployees: [...ALL_EMPLOYEES],
    activeFormulaKey: 'pineda',
    monthlyDivisor: window.HrWageDistortionConfig?.defaultDivisor ?? 313,
    params: {
        previous_min_wage: 610.00,
        mandated_increase: 35.00,
        existing_min_wage: 695.00,
        creditable_increase: 10.00,
        formula_base_range: 750.00,
        exponent: 1.20,
        percentile_weight: 85.00
    },
    sortColumn: 'name',
    sortAsc: true,
    results: []
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Default selection: select all active employees
    ALL_EMPLOYEES.forEach(emp => {
        if (!['Resigned', 'Terminated', 'Inactive', 'Retired'].includes(emp.employment_status)) {
            state.selectedEmpIds.add(emp.id);
        }
    });

    renderFormulaCards();
    renderDynamicInputFields();
    updateFormulaInfoCard();
    applyFilters();
});

// Format currency in Philippine Peso
function formatPhp(amount) {
    const num = parseFloat(amount) || 0.0;
    return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ---------------------------------------------------------
// 1. FILTERING & SELECTION ENGINE
// ---------------------------------------------------------
function applyFilters() {
    const search = (document.getElementById('filterSearch').value || '').trim().toLowerCase();
    const salMin = parseFloat(document.getElementById('filterSalaryMin').value) || 0;
    const salMax = parseFloat(document.getElementById('filterSalaryMax').value) || 999999999;
    const dept = document.getElementById('filterDept').value;
    const pos = document.getElementById('filterPosition').value;
    const branch = document.getElementById('filterBranch').value;
    const status = document.getElementById('filterStatus').value;
    const empType = document.getElementById('filterType').value;
    const payGroup = document.getElementById('filterPayGroup').value;
    const jobLevel = document.getElementById('filterJobLevel').value;
    const source = document.getElementById('filterSource').value;

    state.filteredEmployees = ALL_EMPLOYEES.filter(emp => {
        // Search
        if (search) {
            const matchName = (emp.full_name || '').toLowerCase().includes(search);
            const matchId = (emp.employee_id || '').toLowerCase().includes(search);
            const matchPos = (emp.position_name || '').toLowerCase().includes(search);
            if (!matchName && !matchId && !matchPos) return false;
        }

        // Salary Range
        if (emp.basic_salary < salMin || emp.basic_salary > salMax) return false;

        // Department
        if (dept && emp.department_name !== dept) return false;

        // Position
        if (pos && emp.position_name !== pos) return false;

        // Branch
        if (branch && emp.branch_name !== branch) return false;

        // Status
        if (status === 'ACTIVE_ALL') {
            if (['Resigned', 'Terminated', 'Inactive', 'Retired'].includes(emp.employment_status)) return false;
        } else if (status && emp.employment_status !== status) {
            return false;
        }

        // Employee Type
        if (empType && emp.employment_type !== empType) return false;

        // Pay Group
        if (payGroup && emp.pay_frequency !== payGroup) return false;

        // Job Level
        if (jobLevel && emp.job_level !== jobLevel) return false;

        // Classification / Source
        if (source && emp.employment_source !== source) return false;

        return true;
    });

    sortFilteredEmployees();
    renderEmployeeTable();
    recalculateAll();

    // Update count in toolbar
    const lblToolbar = document.getElementById('lblFilteredCountToolbar');
    if (lblToolbar) {
        lblToolbar.textContent = state.filteredEmployees.length;
    }

    // Compute active filter count
    let activeCount = 0;
    if (search) activeCount++;
    if (salMin > 0 || (document.getElementById('filterSalaryMax').value !== '' && salMax < 999999999)) activeCount++;
    if (dept) activeCount++;
    if (pos) activeCount++;
    if (branch) activeCount++;
    if (status && status !== 'ACTIVE_ALL') activeCount++;
    if (empType) activeCount++;
    if (payGroup) activeCount++;
    if (jobLevel) activeCount++;
    if (source) activeCount++;
    const periodSelect = document.getElementById('filterPayrollPeriod');
    if (periodSelect && periodSelect.value) activeCount++;

    updateFilterUIState(activeCount);
    renderActiveFilterPills();
}

function updateFilterUIState(count) {
    const badge = document.getElementById('activeFilterBadge');
    const quickReset = document.getElementById('btnQuickResetFilters');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }
    if (quickReset) {
        quickReset.style.display = count > 0 ? 'inline-flex' : 'none';
    }
}

function renderActiveFilterPills() {
    const pillsRow = document.getElementById('activeFilterPillsRow');
    const sep = document.getElementById('filterToolbarSeparator');
    if (!pillsRow) return;

    const search = (document.getElementById('filterSearch').value || '').trim();
    const salMinVal = document.getElementById('filterSalaryMin').value.trim();
    const salMaxVal = document.getElementById('filterSalaryMax').value.trim();
    const dept = document.getElementById('filterDept').value;
    const pos = document.getElementById('filterPosition').value;
    const branch = document.getElementById('filterBranch').value;
    const status = document.getElementById('filterStatus').value;
    const empType = document.getElementById('filterType').value;
    const payGroup = document.getElementById('filterPayGroup').value;
    const jobLevel = document.getElementById('filterJobLevel').value;
    const source = document.getElementById('filterSource').value;
    const periodSelect = document.getElementById('filterPayrollPeriod');
    const period = periodSelect ? periodSelect.value : '';

    const chips = [];

    if (search) {
        chips.push({ key: 'search', cat: 'Search', val: `"${search}"` });
    }

    if (salMinVal || salMaxVal) {
        let salLabel = '';
        const minNum = parseFloat(salMinVal);
        const maxNum = parseFloat(salMaxVal);
        if (salMinVal && salMaxVal) {
            salLabel = `₱${minNum.toLocaleString()} - ₱${maxNum.toLocaleString()}`;
        } else if (salMinVal) {
            salLabel = `≥ ₱${minNum.toLocaleString()}`;
        } else if (salMaxVal) {
            salLabel = `≤ ₱${maxNum.toLocaleString()}`;
        }
        chips.push({ key: 'salary', cat: 'Salary', val: salLabel });
    }

    if (dept) {
        chips.push({ key: 'dept', cat: 'Department', val: dept });
    }

    if (pos) {
        chips.push({ key: 'pos', cat: 'Position', val: pos });
    }

    if (branch) {
        chips.push({ key: 'branch', cat: 'Branch', val: branch });
    }

    if (status && status !== 'ACTIVE_ALL') {
        chips.push({ key: 'status', cat: 'Status', val: status });
    }

    if (empType) {
        chips.push({ key: 'empType', cat: 'Type', val: empType });
    }

    if (payGroup) {
        chips.push({ key: 'payGroup', cat: 'Pay Group', val: payGroup });
    }

    if (jobLevel) {
        chips.push({ key: 'jobLevel', cat: 'Level', val: jobLevel });
    }

    if (source) {
        chips.push({ key: 'source', cat: 'Classification', val: source === 'Direct' ? 'Direct Hire' : 'Agency' });
    }

    if (period && periodSelect && periodSelect.selectedIndex > 0) {
        const periodText = periodSelect.options[periodSelect.selectedIndex].text.split('(')[0].trim();
        chips.push({ key: 'period', cat: 'Period', val: periodText });
    }

    // Toggle separator
    if (sep) {
        sep.style.display = chips.length > 0 ? 'block' : 'none';
    }

    // Render chips HTML
    if (chips.length === 0) {
        pillsRow.innerHTML = '';
        return;
    }

    let html = '';
    chips.forEach(chip => {
        html += `
            <span class="hr-filter-tag-chip" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                <span class="hr-chip-text" style="display: inline-flex; align-items: center; gap: 4px;">
                    <strong class="hr-chip-category">${chip.cat}:</strong>
                    <span class="hr-chip-value">${escapeHtml(chip.val)}</span>
                </span>
                <button type="button" class="hr-chip-remove-btn" onclick="removeFilter('${chip.key}')" title="Remove ${chip.cat} filter" aria-label="Remove filter">&times;</button>
            </span>
        `;
    });

    if (chips.length >= 2) {
        html += `
            <button type="button" class="hr-clear-all-chips-btn" onclick="resetFilters()" title="Clear all filters">
                <i class="ph ph-x"></i> Clear all
            </button>
        `;
    }

    pillsRow.innerHTML = html;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function removeFilter(key) {
    switch (key) {
        case 'search':
            document.getElementById('filterSearch').value = '';
            break;
        case 'salary':
            document.getElementById('filterSalaryMin').value = '';
            document.getElementById('filterSalaryMax').value = '';
            break;
        case 'dept':
            document.getElementById('filterDept').value = '';
            break;
        case 'pos':
            document.getElementById('filterPosition').value = '';
            break;
        case 'branch':
            document.getElementById('filterBranch').value = '';
            break;
        case 'status':
            document.getElementById('filterStatus').value = 'ACTIVE_ALL';
            break;
        case 'empType':
            document.getElementById('filterType').value = '';
            break;
        case 'payGroup':
            document.getElementById('filterPayGroup').value = '';
            break;
        case 'jobLevel':
            document.getElementById('filterJobLevel').value = '';
            break;
        case 'source':
            document.getElementById('filterSource').value = '';
            break;
        case 'period':
            const pSel = document.getElementById('filterPayrollPeriod');
            if (pSel) pSel.value = '';
            break;
    }
    applyFilters();
}

function toggleFilterDropdown(event) {
    if (event) {
        event.stopPropagation();
    }
    const menu = document.getElementById('wdFilterDropdownMenu');
    const caret = document.getElementById('filterDropdownCaret');
    if (!menu) return;
    const isHidden = (menu.style.display === 'none' || menu.style.display === '');
    menu.style.display = isHidden ? 'block' : 'none';
    if (caret) {
        caret.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

function closeFilterDropdown() {
    const menu = document.getElementById('wdFilterDropdownMenu');
    const caret = document.getElementById('filterDropdownCaret');
    if (menu) menu.style.display = 'none';
    if (caret) caret.style.transform = 'rotate(0deg)';
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const container = document.querySelector('.wd-filter-dropdown-container');
    const menu = document.getElementById('wdFilterDropdownMenu');
    if (container && menu && !container.contains(e.target)) {
        closeFilterDropdown();
    }
});

function onFilterChange() {
    applyFilters();
}

function setSalaryPreset(min, max) {
    document.getElementById('filterSalaryMin').value = min;
    document.getElementById('filterSalaryMax').value = (max === 999999) ? '' : max;
    applyFilters();
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSalaryMin').value = '';
    document.getElementById('filterSalaryMax').value = '';
    document.getElementById('filterDept').value = '';
    document.getElementById('filterPosition').value = '';
    document.getElementById('filterBranch').value = '';
    document.getElementById('filterStatus').value = 'ACTIVE_ALL';
    document.getElementById('filterType').value = '';
    document.getElementById('filterPayGroup').value = '';
    document.getElementById('filterJobLevel').value = '';
    document.getElementById('filterSource').value = '';
    const pSel = document.getElementById('filterPayrollPeriod');
    if (pSel) pSel.value = '';
    applyFilters();
}

function sortTable(column) {
    if (state.sortColumn === column) {
        state.sortAsc = !state.sortAsc;
    } else {
        state.sortColumn = column;
        state.sortAsc = true;
    }
    sortFilteredEmployees();
    renderEmployeeTable();
}

function sortFilteredEmployees() {
    state.filteredEmployees.sort((a, b) => {
        let valA, valB;
        switch (state.sortColumn) {
            case 'name':
                valA = a.full_name.toLowerCase();
                valB = b.full_name.toLowerCase();
                break;
            case 'department':
                valA = (a.department_name || '').toLowerCase();
                valB = (b.department_name || '').toLowerCase();
                break;
            case 'position':
                valA = (a.position_name || '').toLowerCase();
                valB = (b.position_name || '').toLowerCase();
                break;
            case 'status':
                valA = (a.employment_status || '').toLowerCase();
                valB = (b.employment_status || '').toLowerCase();
                break;
            case 'salary':
                valA = parseFloat(a.basic_salary) || 0;
                valB = parseFloat(b.basic_salary) || 0;
                break;
            case 'grade':
                valA = (a.salary_grade || '').toLowerCase();
                valB = (b.salary_grade || '').toLowerCase();
                break;
            default:
                valA = a.id;
                valB = b.id;
        }

        if (valA < valB) return state.sortAsc ? -1 : 1;
        if (valA > valB) return state.sortAsc ? 1 : -1;
        return 0;
    });
}

function renderEmployeeTable() {
    const tbody = document.getElementById('empTableBody');
    if (!tbody) return;

    if (state.filteredEmployees.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 32px; color: #94a3b8;">
                    <i class="ph ph-magnifying-glass" style="font-size: 24px; display: block; margin-bottom: 6px;"></i>
                    No employees found matching the filter criteria.
                </td>
            </tr>
        `;
        updateSelectionCounters();
        return;
    }

    let html = '';
    state.filteredEmployees.forEach(emp => {
        const isChecked = state.selectedEmpIds.has(emp.id);
        
        let statusBadgeClass = 'hr-badge-neutral';
        if (emp.employment_status === 'Regular' || emp.employment_status === 'Active') statusBadgeClass = 'hr-badge-success';
        else if (emp.employment_status === 'Probationary') statusBadgeClass = 'hr-badge-warning';
        else if (emp.employment_status === 'Contractual') statusBadgeClass = 'hr-badge-info';
        else if (['Resigned', 'Terminated', 'Inactive'].includes(emp.employment_status)) statusBadgeClass = 'hr-badge-danger';

        const avatar = emp.photo_url 
            ? `<img src="${emp.photo_url}" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0;">`
            : `<div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">${emp.initials}</div>`;

        html += `
            <tr style="background: ${isChecked ? 'rgba(124, 58, 237, 0.03)' : '#ffffff'}; transition: background 0.15s ease;">
                <td style="text-align: center;">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="toggleSelectEmp(${emp.id}, this.checked)" style="cursor: pointer; width: 16px; height: 16px;">
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        ${avatar}
                        <div>
                            <div style="font-weight: 600; color: #0f172a; font-size: 13px;">${emp.full_name}</div>
                            <div style="font-family: monospace; font-size: 11px; color: #7c3aed;">${emp.employee_id}</div>
                        </div>
                    </div>
                </td>
                <td style="font-size: 12.5px; color: #334155; font-weight: 500;">${emp.department_name}</td>
                <td style="font-size: 12.5px; color: #0f172a;">${emp.position_name}</td>
                <td style="font-size: 12px; color: #64748b;">${emp.branch_name}</td>
                <td>
                    <span class="hr-badge ${statusBadgeClass}" style="font-size: 11px;">${emp.employment_status}</span>
                </td>
                <td style="font-size: 12px; color: #475569;">${emp.pay_frequency}</td>
                <td style="text-align: right;">
                    <div style="font-weight: 700; font-size: 13px; color: #0f172a;">${formatPhp(emp.basic_salary)}</div>
                    <div style="font-size: 11px; color: #64748b;">${formatPhp(emp.daily_rate)} / day</div>
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px;">${emp.salary_grade}</span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    updateSelectionCounters();
}

function toggleSelectEmp(empId, isSelected) {
    if (isSelected) {
        state.selectedEmpIds.add(empId);
    } else {
        state.selectedEmpIds.delete(empId);
    }
    updateSelectionCounters();
    recalculateAll();
}

function selectAllFiltered(doSelect) {
    state.filteredEmployees.forEach(emp => {
        if (doSelect) {
            state.selectedEmpIds.add(emp.id);
        } else {
            state.selectedEmpIds.delete(emp.id);
        }
    });
    renderEmployeeTable();
    recalculateAll();
}

function toggleSelectAll(isChecked) {
    selectAllFiltered(isChecked);
}

function updateSelectionCounters() {
    const totalFiltered = state.filteredEmployees.length;
    const selectedCount = state.selectedEmpIds.size;

    document.getElementById('lblSelectedCount').innerText = selectedCount;
    document.getElementById('lblFilteredCount').innerText = totalFiltered;
    document.getElementById('lblVisibleRows').innerText = totalFiltered;
    document.getElementById('lblSelectedCountFooter').innerText = selectedCount;
    document.getElementById('scopeSelectedCount').innerText = selectedCount;
    document.getElementById('btnApplyCount').innerText = selectedCount;

    // Checkbox master state
    const chkMaster = document.getElementById('chkSelectAll');
    if (chkMaster) {
        if (totalFiltered === 0) {
            chkMaster.checked = false;
            chkMaster.indeterminate = false;
        } else {
            const allSelected = state.filteredEmployees.every(emp => state.selectedEmpIds.has(emp.id));
            const someSelected = state.filteredEmployees.some(emp => state.selectedEmpIds.has(emp.id));
            chkMaster.checked = allSelected;
            chkMaster.indeterminate = !allSelected && someSelected;
        }
    }
}


// ---------------------------------------------------------
// 2. FORMULA SELECTION & DYNAMIC PARAMETER FIELDS
// ---------------------------------------------------------
function selectFormula(formulaKey) {
    if (!FORMULAS_CONFIG[formulaKey]) return;
    state.activeFormulaKey = formulaKey;

    // Sync dropdown
    const dd = document.getElementById('formulaDropdown');
    if (dd) dd.value = formulaKey;

    renderFormulaCards();
    renderDynamicInputFields();
    updateFormulaInfoCard();
    recalculateAll();
}

function renderFormulaCards() {
    document.querySelectorAll('.formula-card').forEach(card => {
        const id = card.id.replace('formulaCard_', '');
        const isCurrent = (id === state.activeFormulaKey);
        card.classList.toggle('active', isCurrent);
        const radioIcon = card.querySelector('.formula-radio i');
        if (radioIcon) {
            radioIcon.className = 'ph ' + (isCurrent ? 'ph-radio-button' : 'ph-circle');
        }
    });
}

function renderDynamicInputFields() {
    const container = document.getElementById('dynamicFieldsContainer');
    const formula = FORMULAS_CONFIG[state.activeFormulaKey];
    if (!container || !formula) return;

    let html = '';
    const fields = formula.fields || {};

    Object.keys(fields).forEach(fKey => {
        const field = fields[fKey];
        const curVal = state.params[fKey] ?? field.default;

        let prefix = '₱';
        if (field.type === 'percentage') prefix = '%';
        if (field.type === 'number') prefix = '#';

        html += `
            <div>
                <label style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">
                    <span>${field.label}</span>
                    <span style="font-size: 11px; color: #64748b; font-weight: normal;">${field.description}</span>
                </label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #7c3aed; font-size: 13px;">${prefix}</span>
                    <input type="number" 
                           id="param_${fKey}"
                           step="${field.step || '0.01'}" 
                           value="${curVal}" 
                           class="hr-input"
                           placeholder="${field.placeholder}"
                           oninput="onParamChange('${fKey}', this.value)"
                           style="padding-left: 28px; width: 100%; box-sizing: border-box; height: 36px; font-size: 13px; font-weight: 600;">
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    document.getElementById('lblActiveFormulaName').innerText = formula.name;
}

function onParamChange(key, value) {
    state.params[key] = parseFloat(value) || 0.0;
    recalculateAll();
}

function onDivisorChange(val) {
    state.monthlyDivisor = parseFloat(val) || (window.HrWageDistortionConfig?.defaultDivisor ?? 313);
    recalculateAll();
}

function updateFormulaInfoCard() {
    const f = FORMULAS_CONFIG[state.activeFormulaKey];
    if (!f) return;

    document.getElementById('lblFormulaBadge').innerText = f.badge;
    document.getElementById('lblInfoFormulaName').innerText = f.name;
    document.getElementById('lblInfoFormulaDesc').innerText = f.description;
    document.getElementById('lblInfoFormulaMath').innerText = f.latex;
    document.getElementById('lblInfoLegalNote').innerText = f.notes;

    const list = document.getElementById('lblInfoRequiredInputs');
    if (list) {
        let items = '';
        Object.values(f.fields || {}).forEach(fld => {
            items += `<li><strong>${fld.label}:</strong> ${fld.description}</li>`;
        });
        items += `<li><strong>Employee Wage:</strong> Retrieved from employee masterlist</li>`;
        list.innerHTML = items;
    }
}


// ---------------------------------------------------------
// 3. REAL-TIME CALCULATION & CONVERSION ENGINE (0ms Latency)
// ---------------------------------------------------------
function recalculateAll() {
    const selectedEmps = ALL_EMPLOYEES.filter(e => state.selectedEmpIds.has(e.id));
    const resultsTableBody = document.getElementById('resultsTableBody');
    const emptyPlaceholder = document.getElementById('resultsEmptyPlaceholder');

    if (selectedEmps.length === 0) {
        state.results = [];
        if (resultsTableBody) resultsTableBody.innerHTML = '';
        if (emptyPlaceholder) emptyPlaceholder.style.display = 'block';
        updateSummaryKPIs([]);
        return;
    }

    if (emptyPlaceholder) emptyPlaceholder.style.display = 'none';

    // Compute for each selected employee
    const formulaKey = state.activeFormulaKey;
    const divisor = state.monthlyDivisor;
    const computed = [];

    selectedEmps.forEach(emp => {
        const item = computeSingleEmployee(emp, formulaKey, state.params, divisor);
        computed.push(item);
    });

    state.results = computed;
    renderResultsTable(computed);
    updateSummaryKPIs(computed);
}

function computeSingleEmployee(emp, formulaKey, params, divisor) {
    const currentMonthly = parseFloat(emp.basic_salary) || 0.0;
    const dailyWage = (emp.salary_type === 'Daily')
        ? currentMonthly
        : (divisor > 0 ? (currentMonthly / divisor) : 0.0);

    const prevMin = parseFloat(params.previous_min_wage) || 610.00;
    const mandatedInc = parseFloat(params.mandated_increase) || 35.00;
    const existMin = parseFloat(params.existing_min_wage) || 695.00;
    const creditableInc = parseFloat(params.creditable_increase) || 10.00;
    const fbr = parseFloat(params.formula_base_range) || 750.00;
    const exponent = parseFloat(params.exponent) || 1.20;
    const rawWeight = parseFloat(params.percentile_weight) || 85.00;
    const percentileWeight = (rawWeight > 1.0) ? (rawWeight / 100.0) : rawWeight;

    let dailyAdj = 0.0;
    let equation = '';

    switch (formulaKey) {
        case 'pineda':
            if (dailyWage > 0) {
                dailyAdj = (prevMin / dailyWage) * mandatedInc;
                equation = `(₱${prevMin.toFixed(2)} ÷ ₱${dailyWage.toFixed(2)}) × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'pineda_cruz_so':
            if (dailyWage > 0 && exponent > 0) {
                const ratio = prevMin / dailyWage;
                dailyAdj = Math.pow(ratio, exponent) * mandatedInc;
                equation = `(₱${prevMin.toFixed(2)} ÷ ₱${dailyWage.toFixed(2)})^${exponent.toFixed(2)} × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'percentile_carian':
            dailyAdj = percentileWeight * mandatedInc;
            equation = `${(percentileWeight * 100).toFixed(1)}% × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            break;

        case 'pcs':
            if (fbr > 0) {
                dailyAdj = (existMin / fbr) * mandatedInc;
                equation = `(₱${existMin.toFixed(2)} ÷ ₱${fbr.toFixed(2)}) × ₱${mandatedInc.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'joda':
            const diff = Math.max(0, dailyWage - prevMin);
            dailyAdj = diff / 2.0;
            equation = `(₱${dailyWage.toFixed(2)} – ₱${prevMin.toFixed(2)}) ÷ 2 = ₱${dailyAdj.toFixed(2)}/day`;
            break;

        case 'bagtas':
            if (existMin > 0) {
                dailyAdj = (mandatedInc * dailyWage) / existMin;
                equation = `(₱${mandatedInc.toFixed(2)} × ₱${dailyWage.toFixed(2)}) ÷ ₱${existMin.toFixed(2)} = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;

        case 'wirerope':
            if (dailyWage > 0 && creditableInc > 0) {
                dailyAdj = (existMin / dailyWage) * (mandatedInc / creditableInc);
                equation = `(₱${existMin.toFixed(2)} ÷ ₱${dailyWage.toFixed(2)}) × (₱${mandatedInc.toFixed(2)} ÷ ₱${creditableInc.toFixed(2)}) = ₱${dailyAdj.toFixed(2)}/day`;
            }
            break;
    }

    dailyAdj = Math.max(0, Math.round(dailyAdj * 100) / 100);
    const monthlyAdj = (emp.salary_type === 'Daily')
        ? dailyAdj
        : Math.round(dailyAdj * divisor * 100) / 100;

    const newMonthly = Math.round((currentMonthly + monthlyAdj) * 100) / 100;
    const newDaily = Math.round((dailyWage + dailyAdj) * 100) / 100;
    const pctInc = currentMonthly > 0 ? ((monthlyAdj / currentMonthly) * 100) : 0.0;

    return {
        employee_id: emp.id,
        employee_code: emp.employee_id,
        employee_name: emp.full_name,
        photo_url: emp.photo_url,
        initials: emp.initials,
        department_name: emp.department_name,
        position_name: emp.position_name,
        branch_name: emp.branch_name,
        employment_status: emp.employment_status,
        salary_type: emp.salary_type,
        current_monthly_salary: currentMonthly,
        current_daily_wage: dailyWage,
        formula_key: formulaKey,
        formula_name: FORMULAS_CONFIG[formulaKey]?.name || 'Wage Converter',
        daily_adjustment: dailyAdj,
        monthly_adjustment: monthlyAdj,
        new_monthly_salary: newMonthly,
        new_daily_wage: newDaily,
        percent_increase: pctInc,
        equation_breakdown: equation,
        status: 'Distortion Resolved'
    };
}

function renderResultsTable(items) {
    const tbody = document.getElementById('resultsTableBody');
    if (!tbody) return;

    let html = '';
    items.forEach((row, idx) => {
        const avatar = row.photo_url 
            ? `<img src="${row.photo_url}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0;">`
            : `<div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0;">${row.initials}</div>`;

        html += `
            <tr>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="removeSingleFromCalc(${row.employee_id})" style="padding: 2px 6px; color: #dc2626;" title="Remove from wage distortion calculation">
                        <i class="ph ph-x"></i>
                    </button>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${avatar}
                        <div>
                            <div style="font-weight: 600; color: #0f172a; font-size: 13px;">${row.employee_name}</div>
                            <div style="font-family: monospace; font-size: 11px; color: #7c3aed;">${row.employee_code}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight: 500; font-size: 12.5px; color: #334155;">${row.department_name}</div>
                    <div style="font-size: 11.5px; color: #64748b;">${row.position_name}</div>
                </td>
                <td style="text-align: right; font-weight: 600; color: #334155; font-size: 13px;">
                    ${formatPhp(row.current_monthly_salary)}
                </td>
                <td style="text-align: right; font-size: 12px; color: #64748b;">
                    ${formatPhp(row.current_daily_wage)}/d
                </td>
                <td style="text-align: center;">
                    <button type="button" onclick="showBreakdownModal(${idx})" class="hr-badge hr-badge-purple" style="cursor: pointer; border: none; font-size: 10.5px;" title="Click to view mathematical substitution">
                        <i class="ph ph-function"></i> ${FORMULAS_CONFIG[row.formula_key]?.badge || 'Standard'}
                    </button>
                </td>
                <td style="text-align: right; font-weight: 700; color: #7c3aed; background: #faf5ff; font-size: 13px;">
                    +${formatPhp(row.daily_adjustment)}
                </td>
                <td style="text-align: right; font-weight: 800; color: #6b21a8; background: #faf5ff; font-size: 13.5px;">
                    +${formatPhp(row.monthly_adjustment)}
                </td>
                <td style="text-align: right; font-weight: 800; color: #15803d; background: #f0fdf4; font-size: 13.5px;">
                    ${formatPhp(row.new_monthly_salary)}
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-success" style="font-size: 11px; font-weight: 700;">
                        +${row.percent_increase.toFixed(2)}%
                    </span>
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-success" style="font-size: 11px;">
                        <i class="ph ph-check"></i> Resolved
                    </span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('resultsEmpCount').innerText = items.length;
}

function removeSingleFromCalc(empId) {
    state.selectedEmpIds.delete(empId);
    renderEmployeeTable();
    recalculateAll();
}

function updateSummaryKPIs(items) {
    const totalCount = items.length;
    let prevTotal = 0.0;
    let totalAdj = 0.0;
    let newTotal = 0.0;

    items.forEach(it => {
        prevTotal += it.current_monthly_salary;
        totalAdj += it.monthly_adjustment;
        newTotal += it.new_monthly_salary;
    });

    const avgAdj = totalCount > 0 ? (totalAdj / totalCount) : 0.0;
    const totalPct = prevTotal > 0 ? ((totalAdj / prevTotal) * 100) : 0.0;
    const totalActiveWorkforce = ALL_EMPLOYEES.filter(e => !['Resigned', 'Terminated', 'Inactive'].includes(e.employment_status)).length;
    const pctOfWorkforce = totalActiveWorkforce > 0 ? Math.round((totalCount / totalActiveWorkforce) * 100) : 0;

    document.getElementById('kpiEmpCount').innerText = totalCount;
    document.getElementById('kpiEmpPercent').innerText = pctOfWorkforce + '%';
    document.getElementById('kpiTotalAdjustment').innerText = formatPhp(totalAdj);
    document.getElementById('kpiAvgAdjustment').innerText = formatPhp(avgAdj);
    document.getElementById('kpiNewPayroll').innerText = formatPhp(newTotal);
    document.getElementById('kpiPrevPayroll').innerText = formatPhp(prevTotal);
    document.getElementById('kpiPercentIncrease').innerText = '+' + totalPct.toFixed(2) + '%';
}


// ---------------------------------------------------------
// 4. BREAKDOWN MODAL & APPLY MODAL WORKFLOW
// ---------------------------------------------------------
function showBreakdownModal(idx) {
    const item = state.results[idx];
    if (!item) return;

    document.getElementById('breakdownEmpName').innerText = item.employee_name;
    document.getElementById('breakdownEmpMeta').innerText = `${item.employee_code} • ${item.department_name} • ${item.position_name}`;
    document.getElementById('breakdownFormulaName').innerText = item.formula_name;
    document.getElementById('breakdownEquation').innerText = item.equation_breakdown;
    document.getElementById('breakdownDailyAdj').innerText = '+' + formatPhp(item.daily_adjustment);
    document.getElementById('breakdownMonthlyAdj').innerText = '+' + formatPhp(item.monthly_adjustment);
    document.getElementById('breakdownCurrentSalary').innerText = formatPhp(item.current_monthly_salary);
    document.getElementById('breakdownNewSalary').innerText = formatPhp(item.new_monthly_salary);

    const modal = document.getElementById('breakdownModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('open');
        modal.classList.add('active');
    }
}

function closeBreakdownModal() {
    const modal = document.getElementById('breakdownModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('open');
        modal.classList.remove('active');
    }
}

function openApplyAdjustmentModal() {
    if (state.results.length === 0 && state.selectedEmpIds.size > 0) {
        recalculateAll();
    }

    if (state.results.length === 0) {
        alert('Please select at least one employee in Section 1 to calculate and apply wage distortion adjustments.');
        return;
    }

    let totalSpend = 0.0;
    state.results.forEach(r => totalSpend += (parseFloat(r.monthly_adjustment) || 0));
    const avgSpend = state.results.length > 0 ? (totalSpend / state.results.length) : 0.0;

    const modalEmpCount = document.getElementById('modalEmpCount');
    if (modalEmpCount) modalEmpCount.innerText = state.results.length + ' Employees';

    const modalFormulaName = document.getElementById('modalFormulaName');
    if (modalFormulaName) modalFormulaName.innerText = FORMULAS_CONFIG[state.activeFormulaKey]?.name || 'Standard';

    const modalTotalSpend = document.getElementById('modalTotalSpend');
    if (modalTotalSpend) modalTotalSpend.innerText = '+' + formatPhp(totalSpend);

    const modalAvgSpend = document.getElementById('modalAvgSpend');
    if (modalAvgSpend) modalAvgSpend.innerText = '+' + formatPhp(avgSpend);

    const applyReason = document.getElementById('applyReason');
    if (applyReason) applyReason.value = `Compliance with Wage Order (${FORMULAS_CONFIG[state.activeFormulaKey]?.name || 'Standard'})`;

    const modal = document.getElementById('applyModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('open');
        modal.classList.add('active');
    }
}

function closeApplyModal() {
    const modal = document.getElementById('applyModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('open');
        modal.classList.remove('active');
    }
}

// Close on backdrop click for Wage Distortion
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'applyModal') {
        closeApplyModal();
    }
    if (e.target && e.target.id === 'breakdownModal') {
        closeBreakdownModal();
    }
});

async function submitApplyAdjustments(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitApply');
    btn.disabled = true;
    btn.innerHTML = `<i class="ph ph-spinner ph-spin"></i> Processing updates...`;

    const payload = {
        _token: window.HrWageDistortionConfig?.csrfToken || '',
        formula_key: state.activeFormulaKey,
        effective_date: document.getElementById('applyEffectiveDate').value,
        reason: document.getElementById('applyReason').value,
        params: state.params,
        adjustments: state.results.map(r => ({
            employee_id: r.employee_id,
            new_monthly_salary: r.new_monthly_salary,
            monthly_adjustment: r.monthly_adjustment,
            current_monthly_salary: r.current_monthly_salary
        }))
    };

    try {
        const res = await fetch(window.HrWageDistortionConfig?.routes?.apply || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.HrWageDistortionConfig?.csrfToken || ''
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            closeApplyModal();
            alert('Success! ' + data.message);
            window.location.reload();
        } else {
            alert('Error applying adjustments: ' + (data.message || 'Unknown error occurred'));
            btn.disabled = false;
            btn.innerHTML = `<i class="ph ph-check"></i> Confirm & Apply Adjustments`;
        }
    } catch (err) {
        alert('Network or server error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = `<i class="ph ph-check"></i> Confirm & Apply Adjustments`;
    }
}


// ---------------------------------------------------------
// 5. EXPORT CSV & PRINT COMPLIANCE REPORT
// ---------------------------------------------------------
function exportConversionCSV() {
    if (state.results.length === 0) {
        alert('No calculation results available to export. Please select employees first.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.HrWageDistortionConfig?.routes?.export || '';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = window.HrWageDistortionConfig?.csrfToken || '';
    form.appendChild(csrfInput);

    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'export_data';
    dataInput.value = JSON.stringify(state.results);
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Prepare printable DOLE table before print dialog opens
window.onbeforeprint = () => {
    const printTbody = document.getElementById('printTableBody');
    if (!printTbody) return;

    document.getElementById('printFormulaName').innerText = FORMULAS_CONFIG[state.activeFormulaKey]?.name || 'Standard';
    document.getElementById('printDivisor').innerText = state.monthlyDivisor;
    document.getElementById('printEmpCount').innerText = state.results.length;

    let totalSpend = 0.0;
    state.results.forEach(r => totalSpend += r.monthly_adjustment);
    const avgSpend = state.results.length > 0 ? (totalSpend / state.results.length) : 0.0;

    document.getElementById('printTotalSpend').innerText = formatPhp(totalSpend);
    document.getElementById('printAvgSpend').innerText = formatPhp(avgSpend);

    let html = '';
    state.results.forEach(r => {
        html += `
            <tr>
                <td style="padding: 5px; text-align: center;">${r.employee_code}</td>
                <td style="padding: 5px;">${r.employee_name}</td>
                <td style="padding: 5px;">${r.department_name}</td>
                <td style="padding: 5px;">${r.position_name}</td>
                <td style="padding: 5px; text-align: right;">${formatPhp(r.current_monthly_salary)}</td>
                <td style="padding: 5px; text-align: right;">${formatPhp(r.current_daily_wage)}</td>
                <td style="padding: 5px; text-align: right;">${formatPhp(r.daily_adjustment)}</td>
                <td style="padding: 5px; text-align: right; font-weight: bold;">${formatPhp(r.monthly_adjustment)}</td>
                <td style="padding: 5px; text-align: right; font-weight: bold;">${formatPhp(r.new_monthly_salary)}</td>
                <td style="padding: 5px; text-align: center;">+${r.percent_increase.toFixed(2)}%</td>
            </tr>
        `;
    });
    printTbody.innerHTML = html;
};

function resetAllConverter() {
    if (confirm('Reset all parameters, formulas, and employee selections?')) {
        state.selectedEmpIds.clear();
        ALL_EMPLOYEES.forEach(emp => {
            if (!['Resigned', 'Terminated', 'Inactive', 'Retired'].includes(emp.employment_status)) {
                state.selectedEmpIds.add(emp.id);
            }
        });
        state.activeFormulaKey = 'pineda';
        state.monthlyDivisor = window.HrWageDistortionConfig?.defaultDivisor ?? 313;
        state.params = {
            previous_min_wage: 610.00,
            mandated_increase: 35.00,
            existing_min_wage: 695.00,
            creditable_increase: 10.00,
            formula_base_range: 750.00,
            exponent: 1.20,
            percentile_weight: 85.00
        };
        resetFilters();
        selectFormula('pineda');
    }
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    formatPhp,
    applyFilters,
    updateFilterUIState,
    renderActiveFilterPills,
    escapeHtml,
    removeFilter,
    toggleFilterDropdown,
    closeFilterDropdown,
    onFilterChange,
    setSalaryPreset,
    resetFilters,
    sortTable,
    sortFilteredEmployees,
    renderEmployeeTable,
    toggleSelectEmp,
    selectAllFiltered,
    toggleSelectAll,
    updateSelectionCounters,
    selectFormula,
    renderFormulaCards,
    renderDynamicInputFields,
    onParamChange,
    onDivisorChange,
    updateFormulaInfoCard,
    recalculateAll,
    computeSingleEmployee,
    renderResultsTable,
    removeSingleFromCalc,
    updateSummaryKPIs,
    showBreakdownModal,
    closeBreakdownModal,
    openApplyAdjustmentModal,
    closeApplyModal,
    submitApplyAdjustments,
    exportConversionCSV,
    resetAllConverter,
});
