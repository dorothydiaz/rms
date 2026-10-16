function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
function previewAddPhoto(input) {
    var preview = document.getElementById('addPhotoPreviewImg');
    var placeholder = document.getElementById('addPhotoAvatarPlaceholder');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = 'flex';
    }
}

// =========================================================================
// Client-Side Pagination State
// =========================================================================
let _empCurrentPage  = 1;
let _empFilteredRows = []; // holds references to all currently-matching <tr> elements

function empGetPerPage() {
    return parseInt(document.getElementById('empPerPageSelect')?.value || '15', 10);
}

function empChangePerPage() {
    _empCurrentPage = 1;
    renderEmpPage();
}

/**
 * Renders the current page of _empFilteredRows into the tbody
 * and updates the pagination bar.
 */
function renderEmpPage() {
    const perPage    = empGetPerPage();
    const total      = _empFilteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (_empCurrentPage > totalPages) _empCurrentPage = totalPages;

    const start = (_empCurrentPage - 1) * perPage; // 0-based
    const end   = Math.min(start + perPage, total);

    // Show/hide rows
    const allRows = document.querySelectorAll('#employeesTableBody tr.emp-row');
    allRows.forEach(r => { r.style.display = 'none'; });
    _empFilteredRows.forEach((r, idx) => {
        r.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    // No-results row
    const noResultsRow = document.getElementById('noEmpResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = (total === 0) ? '' : 'none';
    }

    // Visible count badge
    const countElem = document.getElementById('empVisibleCount');
    if (countElem) countElem.textContent = total;

    // Pagination bar visibility
    const bar = document.getElementById('empPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';

    // Info text
    const info = document.getElementById('empPaginationInfo');
    if (info) {
        const from = total === 0 ? 0 : start + 1;
        info.innerHTML = `Showing <strong>${from}</strong> to <strong>${end}</strong> of <strong>${total}</strong> employees`;
    }

    // Build page-number nav
    const nav = document.getElementById('empPageNav');
    if (!nav) return;

    let html = '';

    // Prev button
    if (_empCurrentPage === 1) {
        html += `<span class="hr-page-btn disabled" aria-disabled="true"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    } else {
        html += `<span class="hr-page-btn" onclick="empGoToPage(${_empCurrentPage - 1})" style="cursor:pointer;"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    }

    // Page numbers (windowed: show up to 5 around current)
    html += `<div class="hr-page-numbers">`;
    const window_size = 2;
    let pageStart = Math.max(1, _empCurrentPage - window_size);
    let pageEnd   = Math.min(totalPages, _empCurrentPage + window_size);
    if (pageStart > 1) {
        html += `<span class="hr-page-num" onclick="empGoToPage(1)" style="cursor:pointer;">1</span>`;
        if (pageStart > 2) html += `<span class="hr-page-num dots">…</span>`;
    }
    for (let p = pageStart; p <= pageEnd; p++) {
        if (p === _empCurrentPage) {
            html += `<span class="hr-page-num active" aria-current="page">${p}</span>`;
        } else {
            html += `<span class="hr-page-num" onclick="empGoToPage(${p})" style="cursor:pointer;">${p}</span>`;
        }
    }
    if (pageEnd < totalPages) {
        if (pageEnd < totalPages - 1) html += `<span class="hr-page-num dots">…</span>`;
        html += `<span class="hr-page-num" onclick="empGoToPage(${totalPages})" style="cursor:pointer;">${totalPages}</span>`;
    }
    html += `</div>`;

    // Next button
    if (_empCurrentPage >= totalPages) {
        html += `<span class="hr-page-btn disabled" aria-disabled="true"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    } else {
        html += `<span class="hr-page-btn" onclick="empGoToPage(${_empCurrentPage + 1})" style="cursor:pointer;"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    }

    nav.innerHTML = html;
}

function empGoToPage(page) {
    _empCurrentPage = page;
    renderEmpPage();
    // Scroll table back to top on page change
    const wrapper = document.getElementById('employeesTableWrapper');
    if (wrapper) wrapper.scrollTop = 0;
}

// =========================================================================
// Real-Time Table Filter (No Enter Key or Submit Button Required)
// =========================================================================
function toggleMoreFilters() {
    const p = document.getElementById('moreFiltersPanel');
    const b = document.getElementById('moreFiltersBtn');
    if (!p) return;
    if (p.style.display === 'none' || !p.style.display) {
        p.style.display = 'flex';
        b.classList.add('active');
    } else {
        p.style.display = 'none';
        b.classList.remove('active');
    }
}

function filterEmployeesDirectory() {
    const searchVal  = (document.getElementById('empSearchInput')?.value || '').toLowerCase().trim();
    const branchVal  = (document.getElementById('empBranchFilter')?.value || '').trim();
    const deptVal    = (document.getElementById('empDeptFilter')?.value || '').trim();
    const posVal     = (document.getElementById('empPositionFilter')?.value || '').trim();
    const typeVal    = (document.getElementById('empTypeFilter')?.value || '').trim();
    const statusVal  = (document.getElementById('empStatusFilter')?.value || '').trim();
    const companyVal = (document.getElementById('empCompanyFilter')?.value || '').trim();

    const allRows = document.querySelectorAll('#employeesTableBody tr.emp-row');

    _empFilteredRows = Array.from(allRows).filter(row => {
        const id      = row.getAttribute('data-id')       || '';
        const name    = row.getAttribute('data-name')     || '';
        const email   = row.getAttribute('data-email')    || '';
        const branch  = row.getAttribute('data-branch')   || '';
        const dept    = row.getAttribute('data-dept')     || '';
        const pos     = row.getAttribute('data-position') || '';
        const type    = row.getAttribute('data-type')     || '';
        const status  = row.getAttribute('data-status')   || '';
        const company = row.getAttribute('data-company')  || '';

        const matchesSearch = !searchVal ||
            id.includes(searchVal) ||
            name.includes(searchVal) ||
            email.includes(searchVal) ||
            pos.toLowerCase().includes(searchVal) ||
            dept.toLowerCase().includes(searchVal) ||
            branch.toLowerCase().includes(searchVal);

        const allBranch = (row.getAttribute('data-all-branch-names') || branch).toLowerCase();
        const allDept   = (row.getAttribute('data-all-dept-names')   || dept).toLowerCase();
        const allPos    = (row.getAttribute('data-all-pos-names')    || pos).toLowerCase();

        const matchesBranch  = !branchVal  || branch.toLowerCase() === branchVal.toLowerCase() || allBranch.includes(branchVal.toLowerCase());
        const matchesDept    = !deptVal    || dept.toLowerCase() === deptVal.toLowerCase() || allDept.includes(deptVal.toLowerCase());
        const matchesPos     = !posVal     || pos.toLowerCase() === posVal.toLowerCase() || allPos.includes(posVal.toLowerCase());
        const matchesType    = !typeVal    || type.toLowerCase() === typeVal.toLowerCase();
        const matchesStatus  = !statusVal  || status.toLowerCase() === statusVal.toLowerCase();
        const matchesCompany = !companyVal || company.toLowerCase() === companyVal.toLowerCase();

        return matchesSearch && matchesBranch && matchesDept && matchesPos && matchesType && matchesStatus && matchesCompany;
    });

    _empCurrentPage = 1;
    renderEmpPage();
}

// Run pagination on page load
document.addEventListener('DOMContentLoaded', () => {
    const cVal = document.getElementById('empCompanyFilter')?.value;
    if (cVal) {
        const p = document.getElementById('moreFiltersPanel');
        const b = document.getElementById('moreFiltersBtn');
        if (p) { p.style.display = 'flex'; if (b) b.classList.add('active'); }
    }
    filterEmployeesDirectory();
});

function resetEmployeesDirectory() {
    const s = document.getElementById('empSearchInput');
    if (s) s.value = '';
    const b = document.getElementById('empBranchFilter');
    if (b) b.value = '';
    const d = document.getElementById('empDeptFilter');
    if (d) d.value = '';
    const p = document.getElementById('empPositionFilter');
    if (p) p.value = '';
    const t = document.getElementById('empTypeFilter');
    if (t) t.value = '';
    const st = document.getElementById('empStatusFilter');
    if (st) st.value = '';
    const c = document.getElementById('empCompanyFilter');
    if (c) c.value = '';
    const sel = document.getElementById('empSortSelect');
    if (sel) sel.value = '';

    // Clear header sort classes
    document.querySelectorAll('#employeesDirectoryTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    empSortCol = -1;

    filterEmployeesDirectory();
}

// =========================================================================
// Real-Time Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let empSortCol = -1;
let empSortDir = 'asc';

function applyEmpSortFromSelect(val) {
    if (!val) {
        document.querySelectorAll('#employeesDirectoryTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        empSortCol = -1;
        return;
    }

    const sortMap = {
        'name_asc': [1, 'text', 'asc'],
        'name_desc': [1, 'text', 'desc'],
        'id_asc': [0, 'text', 'asc'],
        'id_desc': [0, 'text', 'desc'],
        'dept_asc': [2, 'text', 'asc'],
        'pos_asc': [3, 'text', 'asc'],
        'branch_asc': [4, 'text', 'asc'],
        'type_asc': [5, 'text', 'asc'],
        'date_desc': [6, 'date', 'desc'],
        'date_asc': [6, 'date', 'asc'],
        'status_asc': [7, 'text', 'asc'],
    };

    if (sortMap[val]) {
        sortEmployeesDirectory(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortEmployeesDirectory(colIndex, dataType, forceDir = null) {
    const tableBody = document.getElementById('employeesTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr.emp-row'));
    const headers = document.querySelectorAll('#employeesDirectoryTable thead th.sortable');

    if (forceDir) {
        empSortDir = forceDir;
        empSortCol = colIndex;
    } else {
        if (empSortCol === colIndex) {
            empSortDir = empSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            empSortCol = colIndex;
            empSortDir = 'asc';
        }
    }

    headers.forEach((th, idx) => {
        const icon = th.querySelector('.sort-icon i');
        if (idx === colIndex) {
            th.classList.remove('sorted-asc', 'sorted-desc');
            th.classList.add(empSortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) {
                icon.className = empSortDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
            }
        } else {
            th.classList.remove('sorted-asc', 'sorted-desc');
            if (icon) {
                icon.className = 'ph ph-arrows-down-up';
            }
        }
    });

    // Synchronize Toolbar Sort Dropdown
    const sortSelect = document.getElementById('empSortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'id_asc', '0_desc': 'id_desc',
            '1_asc': 'name_asc', '1_desc': 'name_desc',
            '2_asc': 'dept_asc', '2_desc': 'dept_asc',
            '3_asc': 'pos_asc', '3_desc': 'pos_asc',
            '4_asc': 'branch_asc', '4_desc': 'branch_asc',
            '5_asc': 'type_asc', '5_desc': 'type_asc',
            '6_asc': 'date_asc', '6_desc': 'date_desc',
            '7_asc': 'status_asc', '7_desc': 'status_asc'
        };
        sortSelect.value = keyMap[colIndex + '_' + empSortDir] || '';
    }

    rows.sort((a, b) => {
        let valA, valB;
        if (colIndex === 0) {
            valA = a.getAttribute('data-id') || '';
            valB = b.getAttribute('data-id') || '';
        } else if (colIndex === 1) {
            valA = a.getAttribute('data-name') || '';
            valB = b.getAttribute('data-name') || '';
        } else if (colIndex === 2) {
            valA = a.getAttribute('data-dept') || '';
            valB = b.getAttribute('data-dept') || '';
        } else if (colIndex === 3) {
            valA = a.getAttribute('data-position') || '';
            valB = b.getAttribute('data-position') || '';
        } else if (colIndex === 4) {
            valA = a.getAttribute('data-branch') || '';
            valB = b.getAttribute('data-branch') || '';
        } else if (colIndex === 5) {
            valA = a.getAttribute('data-type') || '';
            valB = b.getAttribute('data-type') || '';
        } else if (colIndex === 6) {
            valA = parseFloat(a.getAttribute('data-date')) || 0;
            valB = parseFloat(b.getAttribute('data-date')) || 0;
            return empSortDir === 'asc' ? valA - valB : valB - valA;
        } else if (colIndex === 7) {
            valA = a.getAttribute('data-status') || '';
            valB = b.getAttribute('data-status') || '';
        }

        const cmp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
        return empSortDir === 'asc' ? cmp : -cmp;
    });

    const noResultsRow = document.getElementById('noEmpResultsRow');
    rows.forEach(r => tableBody.appendChild(r));
    if (noResultsRow) tableBody.appendChild(noResultsRow);

    // Re-apply filter + pagination after sort reorders the DOM
    filterEmployeesDirectory();
}

function syncAddPrimaryCheckbox(type, id) {
    if (!id) return;
    const cb = document.getElementById('cb_add_' + type + '_' + id);
    if (cb) cb.checked = true;
}

// Action dropdown & quick modals
function toggleEmpActionMenu(event, menuId) {
    event.stopPropagation();
    event.preventDefault();
    const current = document.getElementById(menuId);
    if (!current) return;
    const isVisible = current.style.display === 'block';
    document.querySelectorAll('.hr-action-dropdown').forEach(d => d.style.display = 'none');
    if (!isVisible) {
        current.style.display = 'block';
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.hr-action-menu-wrap')) {
        document.querySelectorAll('.hr-action-dropdown').forEach(d => d.style.display = 'none');
    }
});

function updateCpQuickCount() {
    const checked = document.querySelectorAll('.cp-quick-pos-cb:checked').length;
    const badge = document.getElementById('cpQuickSelectedCount');
    if (badge) badge.innerText = `${checked} selected`;
}

function updateTrQuickCounts() {
    const bChecked = document.querySelectorAll('.tr-quick-branch-cb:checked').length;
    const bBadge = document.getElementById('trQuickBranchesCount');
    if (bBadge) bBadge.innerText = `${bChecked} selected`;

    const dChecked = document.querySelectorAll('.tr-quick-dept-cb:checked').length;
    const dBadge = document.getElementById('trQuickDeptsCount');
    if (dBadge) dBadge.innerText = `${dChecked} selected`;
}

function syncCpQuickPrimary(posId) {
    if (!posId) return;
    const cb = document.getElementById('cp_quick_pos_' + posId);
    if (cb) {
        cb.checked = true;
        updateCpQuickCount();
    }
}

function openChangePositionModal(empId, empName, currentPosId, assignedPosIds) {
    const form = document.getElementById('changePositionQuickForm');
    form.action = `/hr/people/employees/${empId}/change-position`;
    document.getElementById('cpQuickEmpName').value = empName;
    const display = document.getElementById('cpQuickEmpNameDisplay');
    if (display) display.innerText = empName;
    const avatar = document.getElementById('cpQuickEmpAvatar');
    if (avatar) {
        const inits = empName.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        avatar.innerText = inits || 'EM';
    }
    if (currentPosId) document.getElementById('cpQuickPositionId').value = currentPosId;

    const assigned = Array.isArray(assignedPosIds) ? assignedPosIds : (currentPosId ? [currentPosId] : []);
    document.querySelectorAll('.cp-quick-pos-cb').forEach(cb => {
        cb.checked = assigned.includes(parseInt(cb.value));
    });
    updateCpQuickCount();

    openModal('changePositionQuickModal');
}

function openTransferModal(empId, empName, currentDeptId, currentBranchId, assignedDeptIds, assignedBranchIds) {
    const form = document.getElementById('transferQuickForm');
    form.action = `/hr/people/employees/${empId}/transfer`;
    document.getElementById('trQuickEmpName').value = empName;
    const display = document.getElementById('trQuickEmpNameDisplay');
    if (display) display.innerText = empName;
    const avatar = document.getElementById('trQuickEmpAvatar');
    if (avatar) {
        const inits = empName.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        avatar.innerText = inits || 'EM';
    }
    if (currentDeptId) document.getElementById('trQuickDeptId').value = currentDeptId;
    if (currentBranchId) document.getElementById('trQuickBranchId').value = currentBranchId;

    const assignedBranches = Array.isArray(assignedBranchIds) ? assignedBranchIds : (currentBranchId ? [currentBranchId] : []);
    document.querySelectorAll('.tr-quick-branch-cb').forEach(cb => {
        cb.checked = assignedBranches.includes(parseInt(cb.value));
    });

    const assignedDepts = Array.isArray(assignedDeptIds) ? assignedDeptIds : (currentDeptId ? [currentDeptId] : []);
    document.querySelectorAll('.tr-quick-dept-cb').forEach(cb => {
        cb.checked = assignedDepts.includes(parseInt(cb.value));
    });
    updateTrQuickCounts();

    openModal('transferQuickModal');
}

function openChangeStatusModal(empId, empName, currentStatus) {
    const form = document.getElementById('changeStatusQuickForm');
    form.action = `/hr/people/employees/${empId}/change-status`;
    document.getElementById('csQuickEmpName').value = empName;
    const display = document.getElementById('csQuickEmpNameDisplay');
    if (display) display.innerText = empName;
    const avatar = document.getElementById('csQuickEmpAvatar');
    if (avatar) {
        const inits = empName.split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
        avatar.innerText = inits || 'EM';
    }
    if (currentStatus) document.getElementById('csQuickStatus').value = currentStatus;
    openModal('changeStatusQuickModal');
}

function openEditEmployeeModal(empId) {
    // Close any open action menus
    document.querySelectorAll('.hr-action-dropdown').forEach(d => d.style.display = 'none');

    const jsonEl = document.getElementById('emp-json-' + empId);
    if (jsonEl) {
        try {
            const emp = JSON.parse(jsonEl.textContent);
            populateEditEmployeeModal(emp);
            return;
        } catch (e) {
            console.error('Failed to parse employee JSON data:', e);
        }
    }

    // Fallback fetch from server
    fetch(`/hr/people/employees/${empId}/data`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(emp => {
        populateEditEmployeeModal(emp);
    })
    .catch(err => {
        console.error('Error fetching employee data:', err);
        alert('Could not load employee details. Please refresh and try again.');
    });
}

function populateEditEmployeeModal(emp) {
    const form = document.getElementById('editEmployeeForm');
    form.action = `/hr/people/employees/${emp.id}`;

    // 1. Basic Information
    document.getElementById('edit_emp_first_name').value = emp.first_name || '';
    document.getElementById('edit_emp_middle_name').value = emp.middle_name || '';
    document.getElementById('edit_emp_last_name').value = emp.last_name || '';
    document.getElementById('edit_emp_suffix').value = emp.suffix || '';
    document.getElementById('edit_emp_preferred_name').value = emp.preferred_name || '';
    document.getElementById('edit_emp_date_of_birth').value = emp.date_of_birth ? emp.date_of_birth.substring(0, 10) : '';
    document.getElementById('edit_emp_birth_place').value = emp.birth_place || '';
    document.getElementById('edit_emp_gender').value = emp.gender || 'Male';
    document.getElementById('edit_emp_civil_status').value = emp.civil_status || 'Single';
    const editEmpNat = document.getElementById('edit_emp_nationality');
    if (editEmpNat) {
        editEmpNat.value = emp.nationality || 'Filipino';
        editEmpNat.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // 2. Contact & Addresses
    document.getElementById('edit_emp_personal_email').value = emp.personal_email || emp.email || '';
    document.getElementById('edit_emp_company_email').value = emp.company_email || '';
    document.getElementById('edit_emp_mobile_number').value = emp.mobile_number || '';
    document.getElementById('edit_emp_telephone_number').value = emp.telephone_number || '';
    document.getElementById('edit_emp_address').value = emp.address || '';
    document.getElementById('edit_emp_permanent_address').value = emp.permanent_address || emp.address || '';
    const sameAddrCb = document.getElementById('edit_sameAddressCb');
    if (sameAddrCb) {
        sameAddrCb.checked = Boolean(emp.permanent_address && emp.address && emp.permanent_address === emp.address);
    }

    // 3. Employment & Assignments
    // Primary & multi branch
    const primaryBranch = document.getElementById('edit_primary_branch_id');
    if (primaryBranch) primaryBranch.value = emp.branch_id || '';
    const assignedBranches = Array.isArray(emp.all_branch_ids) ? emp.all_branch_ids : (emp.branch_id ? [emp.branch_id] : []);
    document.querySelectorAll('.edit-emp-branch-cb').forEach(cb => {
        cb.checked = assignedBranches.map(Number).includes(Number(cb.value));
    });

    // Primary & multi department
    const primaryDept = document.getElementById('edit_primary_department_id');
    if (primaryDept) primaryDept.value = emp.department_id || '';
    const assignedDepts = Array.isArray(emp.all_department_ids) ? emp.all_department_ids : (emp.department_id ? [emp.department_id] : []);
    document.querySelectorAll('.edit-emp-dept-cb').forEach(cb => {
        cb.checked = assignedDepts.map(Number).includes(Number(cb.value));
    });

    // Primary & multi position
    const primaryPos = document.getElementById('edit_primary_position_id');
    if (primaryPos) primaryPos.value = emp.position_id || '';
    const assignedPos = Array.isArray(emp.all_position_ids) ? emp.all_position_ids : (emp.position_id ? [emp.position_id] : []);
    document.querySelectorAll('.edit-emp-pos-cb').forEach(cb => {
        cb.checked = assignedPos.map(Number).includes(Number(cb.value));
    });

    // Job Level, Supervisor, Type, Status, Schedule
    document.getElementById('edit_emp_job_level').value = emp.job_level || 'Staff';
    document.getElementById('edit_emp_supervisor_id').value = emp.supervisor_id || '';
    document.getElementById('edit_emp_employment_type').value = emp.employment_type || 'Regular';
    document.getElementById('edit_emp_employment_status').value = emp.employment_status || 'Active';
    document.getElementById('edit_emp_work_location').value = emp.work_location || '';
    document.getElementById('edit_emp_work_schedule').value = emp.work_schedule || '';
    document.getElementById('edit_emp_date_hired').value = emp.date_hired ? emp.date_hired.substring(0, 10) : '';
    document.getElementById('edit_emp_date_of_regularization').value = emp.date_of_regularization ? emp.date_of_regularization.substring(0, 10) : '';
    document.getElementById('edit_emp_basic_salary').value = (emp.basic_salary !== null && emp.basic_salary !== undefined) ? emp.basic_salary : '';
    document.getElementById('edit_emp_salary_type').value = emp.payroll_type || emp.salary_type || 'Monthly';
    document.getElementById('edit_emp_pay_frequency').value = (emp.pay_frequency && emp.pay_frequency.toLowerCase() === 'semi-monthly') ? 'Semi-Monthly' : (emp.pay_frequency || 'Semi-Monthly');
    document.getElementById('edit_emp_allowances').value = (emp.allowances !== null && emp.allowances !== undefined) ? emp.allowances : '0.00';

    // Source (Company / Agency)
    const isAgency = (emp.employment_source === 'Agency') || (emp.agency_name && !emp.company_name);
    if (isAgency) {
        const agencyRadio = document.getElementById('edit_emp_src_agency');
        if (agencyRadio) agencyRadio.checked = true;
        toggleEditEmpSource('Agency');
        if (emp.agency_name) document.getElementById('edit_agency_name').value = emp.agency_name;
    } else {
        const companyRadio = document.getElementById('edit_emp_src_company');
        if (companyRadio) companyRadio.checked = true;
        toggleEditEmpSource('Company');
        if (emp.company_name) document.getElementById('edit_company_name').value = emp.company_name;
    }

    // 4. Statutory & Government IDs
    document.getElementById('edit_emp_sss_number').value = emp.sss_number || '';
    document.getElementById('edit_emp_philhealth_number').value = emp.philhealth_number || '';
    document.getElementById('edit_emp_pagibig_number').value = emp.pagibig_number || '';
    document.getElementById('edit_emp_tin').value = emp.tin || '';
    document.getElementById('edit_emp_rdo_code').value = emp.rdo_code || '';
    document.getElementById('edit_emp_philsys_id').value = emp.philsys_id || '';
    document.getElementById('edit_emp_passport_number').value = emp.passport_number || '';
    document.getElementById('edit_emp_driver_license').value = emp.driver_license || '';

    // 5. Photo
    const fileInput = document.getElementById('editEmpPhotoInput');
    if (fileInput) fileInput.value = '';
    const previewImg = document.getElementById('editEmpPhotoPreviewImg');
    const placeholder = document.getElementById('editEmpPhotoAvatarPlaceholder');
    if (emp.photo_url) {
        if (previewImg) {
            previewImg.src = emp.photo_url;
            previewImg.style.display = 'block';
        }
        if (placeholder) placeholder.style.display = 'none';
    } else {
        if (previewImg) previewImg.style.display = 'none';
        if (placeholder) {
            placeholder.style.display = 'flex';
            const name = (emp.first_name || '') + ' ' + (emp.last_name || '');
            const inits = name.trim().split(' ').map(n => n[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
            placeholder.innerHTML = inits ? `<span>${inits}</span>` : '<i class="ph ph-user"></i>';
        }
    }

    openModal('editEmployeeModal');
}

function syncEditEmpPrimaryCheckbox(type, val) {
    if (!val) return;
    const prefix = type === 'branch' ? 'cb_edit_emp_branch_' : (type === 'dept' ? 'cb_edit_emp_dept_' : 'cb_edit_emp_pos_');
    const cb = document.getElementById(prefix + val);
    if (cb) cb.checked = true;
}

function toggleEditEmpSource(type) {
    const compBox = document.getElementById('edit_emp_company_box');
    const agBox = document.getElementById('edit_emp_agency_box');
    if (type === 'Agency') {
        if (compBox) compBox.style.display = 'none';
        if (agBox) agBox.style.display = 'block';
    } else {
        if (compBox) compBox.style.display = 'block';
        if (agBox) agBox.style.display = 'none';
    }
}

function previewEditEmpPhoto(input) {
    const preview = document.getElementById('editEmpPhotoPreviewImg');
    const placeholder = document.getElementById('editEmpPhotoAvatarPlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    previewAddPhoto,
    empGetPerPage,
    empChangePerPage,
    renderEmpPage,
    empGoToPage,
    toggleMoreFilters,
    filterEmployeesDirectory,
    resetEmployeesDirectory,
    applyEmpSortFromSelect,
    sortEmployeesDirectory,
    syncAddPrimaryCheckbox,
    toggleEmpActionMenu,
    updateCpQuickCount,
    updateTrQuickCounts,
    syncCpQuickPrimary,
    openChangePositionModal,
    openTransferModal,
    openChangeStatusModal,
    openEditEmployeeModal,
    populateEditEmployeeModal,
    syncEditEmpPrimaryCheckbox,
    toggleEditEmpSource,
    previewEditEmpPhoto,
});
