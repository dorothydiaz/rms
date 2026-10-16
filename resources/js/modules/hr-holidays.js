/**
 * Enterprise Philippine Payroll - Holidays Management Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Filter Holidays client-side without page reload
export function applyHolidayFilters() {
    const q = (document.getElementById('holidaySearchInput')?.value || '').toLowerCase().trim();
    const type = document.getElementById('filterHolidayType')?.value;
    const scope = document.getElementById('filterScope')?.value;
    const branch = document.getElementById('filterBranch')?.value;
    const status = document.getElementById('filterStatus')?.value;

    const rows = document.querySelectorAll('.holiday-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const hType = row.getAttribute('data-type');
        const hScope = row.getAttribute('data-scope');
        const hStatus = row.getAttribute('data-status');
        const branches = JSON.parse(row.getAttribute('data-branches') || '[]');

        let match = true;

        if (q && !name.includes(q)) match = false;
        if (type && hType !== type) match = false;
        if (scope && hScope !== scope) match = false;
        if (status && hStatus !== status) match = false;
        if (branch && branches.length > 0 && !branches.includes(branch) && !branches.includes(parseInt(branch))) {
            match = false;
        }

        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    const badge = document.getElementById('tableHolidayCountBadge');
    if (badge) badge.innerText = `${visibleCount} holidays`;

    const emptyRow = document.getElementById('emptyHolidayRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

export function resetHolidayFilters() {
    const searchInput = document.getElementById('holidaySearchInput');
    const typeInput = document.getElementById('filterHolidayType');
    const scopeInput = document.getElementById('filterScope');
    const branchInput = document.getElementById('filterBranch');
    const statusInput = document.getElementById('filterStatus');

    if (searchInput) searchInput.value = '';
    if (typeInput) typeInput.value = '';
    if (scopeInput) scopeInput.value = '';
    if (branchInput) branchInput.value = '';
    if (statusInput) statusInput.value = '';
    applyHolidayFilters();
}

// Open Detail Drawer
export function viewHolidayDetails(id) {
    const row = document.querySelector(`.holiday-row[data-id="${id}"]`);
    if (!row) return;

    const data = JSON.parse(row.getAttribute('data-json'));

    const dtlName = document.getElementById('dtlName');
    const dtlDate = document.getElementById('dtlDate');
    const dtlTypeBadge = document.getElementById('dtlTypeBadge');
    const dtlScope = document.getElementById('dtlScope');
    const dtlStatus = document.getElementById('dtlStatus');
    const dtlReference = document.getElementById('dtlReference');
    const dtlTreatment = document.getElementById('dtlTreatment');
    const dtlLocations = document.getElementById('dtlLocations');

    if (dtlName) dtlName.innerText = data.name;
    if (dtlDate) dtlDate.innerText = data.date ? new Date(data.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', weekday: 'long' }) : '';
    if (dtlTypeBadge) dtlTypeBadge.innerText = data.holiday_type;
    if (dtlScope) dtlScope.innerText = data.scope;
    if (dtlStatus) dtlStatus.innerText = data.is_active ? 'Active' : 'Inactive';
    if (dtlReference) dtlReference.innerText = data.official_reference || 'DOLE Statutory Labor Code';
    if (dtlTreatment) dtlTreatment.innerText = data.payroll_treatment || 'Statutory DOLE Rules Apply';
    if (dtlLocations) {
        dtlLocations.innerText = (data.scope === 'Nationwide' || !data.applicable_branches || data.applicable_branches.length === 0) 
            ? 'All Branches Nationwide' 
            : `${data.applicable_branches.length} Selected Branches`;
    }

    // Calculation rules display based on type
    const dtlRuleUnworked = document.getElementById('dtlRuleUnworked');
    const dtlRuleWorked = document.getElementById('dtlRuleWorked');
    const dtlRuleRestDay = document.getElementById('dtlRuleRestDay');
    const dtlRuleOt = document.getElementById('dtlRuleOt');

    if (data.holiday_type === 'Regular Holiday') {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = '100%';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '200%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '+30% (260%)';
        if (dtlRuleOt) dtlRuleOt.innerText = '+30% of day rate';
    } else if (data.holiday_type === 'Special Non-Working') {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = '0% (No work no pay)';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '130%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '+50% (150%)';
        if (dtlRuleOt) dtlRuleOt.innerText = '+30% of day rate';
    } else if (data.holiday_type === 'Special Working') {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = '100%';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '100%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '130%';
        if (dtlRuleOt) dtlRuleOt.innerText = '125%';
    } else {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = 'Per Company Policy';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '130%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '150%';
        if (dtlRuleOt) dtlRuleOt.innerText = '+30%';
    }

    openModal('holidayDetailDrawer');
}

// Edit Holiday
export function editHoliday(id) {
    const row = document.querySelector(`.holiday-row[data-id="${id}"]`);
    if (!row) return;

    const data = JSON.parse(row.getAttribute('data-json'));
    const form = document.getElementById('editHolidayForm');
    const baseUrl = window.HrHolidaysConfig?.baseUrl || '/hr/payroll/holidays';
    if (form) form.action = `${baseUrl}/${id}`;

    const nameInput = document.getElementById('editHolidayName');
    const yearInput = document.getElementById('editHolidayYear');
    const dateInput = document.getElementById('editHolidayDate');
    const typeInput = document.getElementById('editHolidayType');
    const scopeInput = document.getElementById('editHolidayScope');
    const refInput = document.getElementById('editHolidayRef');
    const descInput = document.getElementById('editHolidayDesc');
    const activeInput = document.getElementById('editIsActive');

    if (nameInput) nameInput.value = data.name;
    if (yearInput) yearInput.value = data.year;
    if (dateInput) dateInput.value = data.date ? data.date.substring(0, 10) : '';
    if (typeInput) typeInput.value = data.holiday_type;
    if (scopeInput) scopeInput.value = data.scope;
    if (refInput) refInput.value = data.official_reference || '';

    // Set Payroll Treatment select dropdown
    const treatmentSelect = document.getElementById('editPayrollTreatment');
    if (treatmentSelect) {
        let found = false;
        for (let opt of treatmentSelect.options) {
            if (opt.value === data.payroll_treatment) {
                treatmentSelect.value = data.payroll_treatment;
                found = true;
                break;
            }
        }
        if (!found && data.payroll_treatment) {
            const customOpt = new Option(data.payroll_treatment, data.payroll_treatment, true, true);
            treatmentSelect.add(customOpt);
        }
    }

    if (descInput) descInput.value = data.description || '';
    if (activeInput) activeInput.checked = !!data.is_active;

    openModal('editHolidayModal');
}

export function onHolidayTypeChange(type, prefix) {
    const treatmentSelect = document.getElementById(`${prefix}PayrollTreatment`);
    if (!treatmentSelect) return;

    if (type === 'Regular Holiday') {
        treatmentSelect.value = '100% unworked / 200% worked (+30% rest day)';
    } else if (type === 'Special Non-Working') {
        treatmentSelect.value = 'No work no pay / 130% worked (+50% rest day)';
    } else if (type === 'Special Working') {
        treatmentSelect.value = '100% standard rate (no premium)';
    } else {
        treatmentSelect.value = '130% worked rate';
    }
}

// Global window exposure
Object.assign(window, {
    openModal,
    closeModal,
    applyHolidayFilters,
    resetHolidayFilters,
    viewHolidayDetails,
    editHoliday,
    onHolidayTypeChange,
});

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('holidaySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyHolidayFilters);
    }
});
