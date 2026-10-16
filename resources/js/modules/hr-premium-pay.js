// Filter Dropdown & Active Pills Handler
function togglePremiumFilterDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('premiumFilterDropdownMenu');
    const caret = document.getElementById('premiumFilterCaret');
    if (!menu) return;
    const isHidden = (menu.style.display === 'none' || menu.style.display === '');
    menu.style.display = isHidden ? 'block' : 'none';
    if (caret) caret.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
}

function closePremiumFilterDropdown() {
    const menu = document.getElementById('premiumFilterDropdownMenu');
    const caret = document.getElementById('premiumFilterCaret');
    if (menu) menu.style.display = 'none';
    if (caret) caret.style.transform = 'rotate(0deg)';
}

document.addEventListener('click', function(e) {
    const container = document.querySelector('.pp-filter-dropdown-container');
    const menu = document.getElementById('premiumFilterDropdownMenu');
    if (container && menu && !container.contains(e.target)) {
        closePremiumFilterDropdown();
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatDateShort(dateStr) {
    if (!dateStr) return '';
    try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const m = months[parseInt(parts[1], 10) - 1];
            return `${m} ${parseInt(parts[2], 10)}, ${parts[0]}`;
        }
    } catch(e) {}
    return dateStr;
}

function updatePremiumFilterUI() {
    const search = (document.getElementById('filterSearch')?.value || '').trim();
    const startDate = document.getElementById('filterStartDate')?.value || '';
    const endDate = document.getElementById('filterEndDate')?.value || '';
    const periodSelect = document.getElementById('filterPayrollPeriod');
    const deptSelect = document.getElementById('filterDepartment');
    const branchSelect = document.getElementById('filterBranch');
    const statusSelect = document.getElementById('filterStatus');
    const typeSelect = document.getElementById('filterPremiumType');
    const holidaySelect = document.getElementById('filterHolidayType');

    const chips = [];

    if (search) {
        chips.push({ key: 'search', label: 'Search', val: `"${search}"` });
    }

    if (startDate || endDate) {
        let dateVal = '';
        if (startDate && endDate) {
            dateVal = `${formatDateShort(startDate)} - ${formatDateShort(endDate)}`;
        } else if (startDate) {
            dateVal = `From ${formatDateShort(startDate)}`;
        } else {
            dateVal = `Until ${formatDateShort(endDate)}`;
        }
        chips.push({ key: 'date', label: 'Date', val: dateVal });
    }

    if (periodSelect && periodSelect.value && periodSelect.selectedIndex > 0) {
        const text = periodSelect.options[periodSelect.selectedIndex].text.split('(')[0].trim();
        chips.push({ key: 'payroll_period_id', label: 'Period', val: text });
    }

    if (deptSelect && deptSelect.value && deptSelect.selectedIndex > 0) {
        const text = deptSelect.options[deptSelect.selectedIndex].text.trim();
        chips.push({ key: 'department_id', label: 'Department', val: text });
    }

    if (branchSelect && branchSelect.value && branchSelect.selectedIndex > 0) {
        const text = branchSelect.options[branchSelect.selectedIndex].text.trim();
        chips.push({ key: 'branch_id', label: 'Branch', val: text });
    }

    if (statusSelect && statusSelect.value) {
        chips.push({ key: 'status', label: 'Status', val: statusSelect.value });
    }

    if (typeSelect && typeSelect.value) {
        chips.push({ key: 'premium_type', label: 'Type', val: typeSelect.value });
    }

    if (holidaySelect && holidaySelect.value) {
        chips.push({ key: 'holiday_type', label: 'Holiday', val: holidaySelect.value });
    }

    // Update badge counter
    const badge = document.getElementById('premiumActiveFilterBadge');
    if (badge) {
        if (chips.length > 0) {
            badge.textContent = chips.length;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // Update separator
    const sep = document.getElementById('premiumFilterToolbarSeparator');
    if (sep) {
        sep.style.display = chips.length > 0 ? 'block' : 'none';
    }

    // Update quick reset button on right
    const quickReset = document.getElementById('btnQuickResetPremiumFilters');
    if (quickReset) {
        quickReset.style.display = chips.length > 0 ? 'inline-flex' : 'none';
    }

    // Render pills
    renderPremiumFilterPills(chips);
}

function renderPremiumFilterPills(chips) {
    const row = document.getElementById('premiumActiveFilterPillsRow');
    if (!row) return;

    if (!chips || chips.length === 0) {
        row.innerHTML = '';
        return;
    }

    let html = '';
    chips.forEach(chip => {
        html += `
            <span class="hr-filter-tag-chip" style="margin: 0; display: inline-flex; align-items: center; gap: 5px;">
                <span class="hr-chip-text" style="display: inline-flex; align-items: center; gap: 4px;">
                    <strong class="hr-chip-category">${escapeHtml(chip.label)}:</strong>
                    <span class="hr-chip-value">${escapeHtml(chip.val)}</span>
                </span>
                <button type="button" class="hr-chip-remove-btn" onclick="removePremiumFilter('${chip.key}')" title="Remove ${escapeHtml(chip.label)} filter" aria-label="Remove filter">&times;</button>
            </span>
        `;
    });

    if (chips.length >= 2) {
        html += `
            <button type="button" class="hr-clear-all-chips-btn" onclick="resetPremiumFilters()" title="Clear all filters">
                <i class="ph ph-x"></i> Clear all
            </button>
        `;
    }

    row.innerHTML = html;
}

function removePremiumFilter(key) {
    if (key === 'search') {
        const el = document.getElementById('filterSearch');
        if (el) el.value = '';
    } else if (key === 'date') {
        const s = document.getElementById('filterStartDate');
        const e = document.getElementById('filterEndDate');
        if (s) s.value = '';
        if (e) e.value = '';
    } else if (key === 'payroll_period_id') {
        const el = document.getElementById('filterPayrollPeriod');
        if (el) el.value = '';
    } else if (key === 'department_id') {
        const el = document.getElementById('filterDepartment');
        if (el) el.value = '';
    } else if (key === 'branch_id') {
        const el = document.getElementById('filterBranch');
        if (el) el.value = '';
    } else if (key === 'status') {
        const el = document.getElementById('filterStatus');
        if (el) el.value = '';
    } else if (key === 'premium_type') {
        const el = document.getElementById('filterPremiumType');
        if (el) el.value = '';
    } else if (key === 'holiday_type') {
        const el = document.getElementById('filterHolidayType');
        if (el) el.value = '';
    }
    
    updatePremiumFilterUI();

    const hasQuery = window.location.search && window.location.search.length > 1;
    if (hasQuery) {
        document.getElementById('premiumFilterForm').submit();
    }
}

function resetPremiumFilters() {
    const search = document.getElementById('filterSearch');
    const start = document.getElementById('filterStartDate');
    const end = document.getElementById('filterEndDate');
    const period = document.getElementById('filterPayrollPeriod');
    const dept = document.getElementById('filterDepartment');
    const branch = document.getElementById('filterBranch');
    const status = document.getElementById('filterStatus');
    const pType = document.getElementById('filterPremiumType');
    const hType = document.getElementById('filterHolidayType');

    if (search) search.value = '';
    if (start) start.value = '';
    if (end) end.value = '';
    if (period) period.value = '';
    if (dept) dept.value = '';
    if (branch) branch.value = '';
    if (status) status.value = '';
    if (pType) pType.value = '';
    if (hType) hType.value = '';

    updatePremiumFilterUI();

    const hasQuery = window.location.search && window.location.search.length > 1;
    if (hasQuery) {
        window.location.href = window.HrPremiumPayConfig?.routes?.premiumPay || '/hr/payroll/premium-pay';
    }
}

function setDatePreset(type) {
    const startInput = document.getElementById('filterStartDate');
    const endInput = document.getElementById('filterEndDate');
    if (!startInput || !endInput) return;
    
    const today = new Date();
    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (type === 'today') {
        const tStr = formatDate(today);
        startInput.value = tStr;
        endInput.value = tStr;
    } else if (type === 'this_week') {
        const dayOfWeek = today.getDay(); // 0 is Sunday
        const monday = new Date(today);
        monday.setDate(today.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1));
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        startInput.value = formatDate(monday);
        endInput.value = formatDate(sunday);
    } else if (type === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        startInput.value = formatDate(firstDay);
        endInput.value = formatDate(lastDay);
    } else if (type === 'clear') {
        startInput.value = '';
        endInput.value = '';
    }

    updatePremiumFilterUI();
}

document.addEventListener('DOMContentLoaded', function() {
    updatePremiumFilterUI();
});
// Modal / Drawer Helper
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
        modal.classList.add('active');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');
        modal.classList.remove('active');
    }
}

function openDrawer(id) {
    const drawer = document.getElementById(id);
    if (drawer) {
        drawer.classList.add('open');
        drawer.classList.add('active');
    }
}

function closeDrawer(id) {
    const drawer = document.getElementById(id);
    if (drawer) {
        drawer.classList.remove('open');
        drawer.classList.remove('active');
    }
}

// Close on backdrop click
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('hr-modal-overlay')) {
        closeDrawer(e.target.id);
        closeModal(e.target.id);
    }
});

// Select All & Row Selection handling
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    handleRowSelect();
}

function handleRowSelect() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulkActionBar');
    const badge = document.getElementById('selectedCountBadge');

    if (count > 0) {
        bulkBar.style.display = 'flex';
        badge.innerText = count;
    } else {
        bulkBar.style.display = 'none';
        badge.innerText = '0';
        const master = document.getElementById('selectAllCheckbox');
        if (master) master.checked = false;
    }
}

function getSelectedIds() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

// Bulk Actions
function confirmBulkApprove() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    if (!confirm(`Are you sure you want to approve ${ids.length} selected premium pay record(s)?`)) {
        return;
    }

    const container = document.getElementById('bulkApproveInputs');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });

    document.getElementById('bulkApproveForm').submit();
}

function openBulkRejectModal() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    document.getElementById('bulkRejectCountText').innerText = ids.length;

    const container = document.getElementById('bulkRejectHiddenInputs');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });

    openModal('bulkRejectModal');
}

function confirmBulkRecalculate() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;

    const container = document.getElementById('bulkRecalcInputs');
    container.innerHTML = '';
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        container.appendChild(input);
    });

    document.getElementById('bulkRecalculateForm').submit();
}

// Single Reject Modal
function openRejectModal(id, empName, workDate) {
    document.getElementById('rejectModalEmpName').innerText = empName;
    document.getElementById('rejectModalDate').innerText = workDate;
    document.getElementById('singleRejectForm').action = `${window.HrPremiumPayConfig?.routes?.baseUrl || '/hr/payroll/premium-pay'}/${id}/reject`;
    document.getElementById('singleRejectReason').value = '';
    openModal('rejectModal');
}

// Real-Time Calculation Drawer Inspector
function openCalculationDrawer(item) {
    const emp = item.employee || {};
    const empName = emp.first_name ? `${emp.first_name} ${emp.last_name}` : `Employee #${item.employee_id}`;
    const empSub = `${emp.employee_id || 'EMP'} • ${emp.position ? emp.position.title : 'Staff'}`;

    document.getElementById('drawerEmpName').innerText = empName;
    document.getElementById('drawerEmpSub').innerText = empSub;

    // Status Badge
    const badge = document.getElementById('drawerStatusBadge');
    badge.innerText = item.status;
    if (item.status === 'Approved') {
        badge.style.background = '#ecfdf5';
        badge.style.color = '#059669';
    } else if (item.status === 'Rejected') {
        badge.style.background = '#fee2e2';
        badge.style.color = '#dc2626';
    } else {
        badge.style.background = '#fef3c7';
        badge.style.color = '#b45309';
    }

    // Daily & Hourly Rates
    const dailyRate = parseFloat(item.base_daily_rate || 800);
    const hourlyRate = parseFloat(item.base_hourly_rate || (dailyRate / 8));
    document.getElementById('drawerDailyRate').innerText = `₱${dailyRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('drawerHourlyRate').innerText = `₱${hourlyRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} / hr`;

    // Date & Context
    const d = new Date(item.work_date);
    const dateFormatted = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    document.getElementById('drawerWorkDate').innerText = dateFormatted;
    document.getElementById('drawerHolidayName').innerText = item.holiday_name || 'None (Regular Day)';
    document.getElementById('drawerHolidayType').innerText = item.holiday_type || '—';
    document.getElementById('drawerRestDay').innerText = item.is_rest_day ? 'Yes' : 'No';

    const totalHours = parseFloat(item.hours_worked || 8);
    const otHours = parseFloat(item.overtime_hours || 0);
    const regHours = Math.max(0, totalHours - otHours);

    document.getElementById('drawerHoursWorked').innerText = `${totalHours.toFixed(1)} hrs`;
    document.getElementById('drawerRegHours').innerText = regHours.toFixed(1);
    document.getElementById('drawerOtHours').innerText = otHours.toFixed(1);

    // Classification & Multipliers
    document.getElementById('drawerClassification').innerText = item.work_type || 'Rest Day';
    const multPercent = Math.round(parseFloat(item.applied_multiplier || 1.3) * 100);
    document.getElementById('drawerMultiplier').innerText = `${multPercent}%`;

    // Formula equation
    let equation = `₱${dailyRate.toFixed(2)} × ${multPercent}%`;
    document.getElementById('drawerFormulaEquation').innerText = `Calculation: ${equation}`;

    // DOLE Separate Breakdown
    const regularRateMult = parseFloat(item.applied_multiplier || 1.3);
    const regPremiumPay = (hourlyRate * regularRateMult * regHours);
    const otMultiplier = parseFloat(item.overtime_multiplier || 1.3);
    const otPremiumPay = (hourlyRate * regularRateMult * otMultiplier * otHours);
    const totalAmount = parseFloat(item.premium_amount || (regPremiumPay + otPremiumPay));

    document.getElementById('drawerRegAmount').innerText = `₱${regPremiumPay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('drawerRegRateNote').innerText = `Base Day Multiplier: ${multPercent}% (${regHours}h × ₱${(hourlyRate * regularRateMult).toFixed(2)})`;

    document.getElementById('drawerOtAmount').innerText = `₱${otPremiumPay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('drawerOtRateNote').innerText = otHours > 0
        ? `₱${hourlyRate.toFixed(2)}/hr × ${multPercent}% × 130% OT (${otHours}h)`
        : 'No overtime hours on this shift';

    document.getElementById('drawerTotalPremium').innerText = `₱${totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    // Breakdown details from breakdown_json if present
    if (item.breakdown_json) {
        try {
            const b = typeof item.breakdown_json === 'string' ? JSON.parse(item.breakdown_json) : item.breakdown_json;
            if (b.rule_version) {
                document.getElementById('drawerRuleVersion').innerText = b.rule_version;
            }
        } catch(e) {}
    }

    openDrawer('calculationDrawer');
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    togglePremiumFilterDropdown,
    closePremiumFilterDropdown,
    escapeHtml,
    formatDateShort,
    updatePremiumFilterUI,
    renderPremiumFilterPills,
    removePremiumFilter,
    resetPremiumFilters,
    setDatePreset,
    openModal,
    closeModal,
    openDrawer,
    closeDrawer,
    toggleSelectAll,
    handleRowSelect,
    getSelectedIds,
    confirmBulkApprove,
    openBulkRejectModal,
    confirmBulkRecalculate,
    openRejectModal,
    openCalculationDrawer,
});
