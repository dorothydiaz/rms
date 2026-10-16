function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEditCompanyModal(data) {
    var form = document.getElementById('editCompanyForm');
    form.action = (window.HrCompaniesConfig?.baseUrl || '/hr/people/companies') + '/' + data.id;

    if (data.type === 'Agency') {
        document.getElementById('edit_type_agency').checked = true;
    } else {
        document.getElementById('edit_type_company').checked = true;
    }

    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_code').value = data.code || '';
    document.getElementById('edit_tin').value = data.tin || '';
    document.getElementById('edit_contact_person').value = data.contact_person || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_address').value = data.address || '';
    document.getElementById('edit_notes').value = data.notes || '';
    document.getElementById('edit_is_active').checked = !!data.is_active;

    openModal('editCompanyModal');
}


// =========================================================================
// Client-Side Pagination State — Companies
// =========================================================================
let _compCurrentPage = 1;
let _compFilteredRows = [];

function compGetPerPage() { return parseInt(document.getElementById('compPerPageSelect')?.value || '15', 10); }
function compChangePerPage() { _compCurrentPage = 1; renderCompPage(); }
function compGoToPage(p) {
    _compCurrentPage = p;
    renderCompPage();
    const w = document.getElementById('companiesTableWrapper');
    if (w) w.scrollTop = 0;
}

function renderCompPage() {
    const pp = compGetPerPage(), total = _compFilteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_compCurrentPage > totalPages) _compCurrentPage = totalPages;
    const start = (_compCurrentPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#companiesTableBody tr.company-row').forEach(r => r.style.display = 'none');
    _compFilteredRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });


    const noResultsRow = document.getElementById('noCompanyResultsRow');
    if (noResultsRow) noResultsRow.style.display = total === 0 ? '' : 'none';

    const countElem = document.getElementById('companyVisibleCount');
    if (countElem) countElem.textContent = total;

    const bar = document.getElementById('compPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';

    const info = document.getElementById('compPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> entities`;

    const nav = document.getElementById('compPageNav');
    if (!nav) return;
    let h = _compCurrentPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="compGoToPage(${_compCurrentPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _compCurrentPage-2), pe = Math.min(totalPages, _compCurrentPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="compGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _compCurrentPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="compGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="compGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _compCurrentPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="compGoToPage(${_compCurrentPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

// =========================================================================
// Real-Time Table Filter
// =========================================================================
function filterCompaniesTable() {
    const searchVal = (document.getElementById('companySearchInput')?.value || '').toLowerCase().trim();
    const typeVal   = (document.getElementById('companyTypeFilter')?.value || '').trim();
    const statusVal = (document.getElementById('companyStatusFilter')?.value || '').trim();

    const allRows = document.querySelectorAll('#companiesTableBody tr.company-row');

    _compFilteredRows = Array.from(allRows).filter(row => {
        const code    = row.getAttribute('data-code')    || '';
        const name    = row.getAttribute('data-name')    || '';
        const type    = row.getAttribute('data-type')    || '';
        const contact = row.getAttribute('data-contact') || '';
        const email   = row.getAttribute('data-email')   || '';
        const phone   = row.getAttribute('data-phone')   || '';
        const tin     = row.getAttribute('data-tin')     || '';
        const status  = row.getAttribute('data-status')  || '';

        const matchesSearch = !searchVal ||
            code.includes(searchVal) ||
            name.includes(searchVal) ||
            contact.includes(searchVal) ||
            email.includes(searchVal) ||
            phone.includes(searchVal) ||
            tin.includes(searchVal);

        const matchesType   = !typeVal   || type === typeVal;
        const matchesStatus = !statusVal || status === statusVal;

        return matchesSearch && matchesType && matchesStatus;
    });

    _compCurrentPage = 1;
    renderCompPage();
}

document.addEventListener('DOMContentLoaded', () => { filterCompaniesTable(); });

function resetCompaniesFilters() {
    const s = document.getElementById('companySearchInput');
    if (s) s.value = '';
    const t = document.getElementById('companyTypeFilter');
    if (t) t.value = '';
    const st = document.getElementById('companyStatusFilter');
    if (st) st.value = '';
    const sel = document.getElementById('companySortSelect');
    if (sel) sel.value = '';

    // Clear header sort classes
    document.querySelectorAll('#companiesTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    compSortCol = -1;

    filterCompaniesTable();
}

// =========================================================================
// Real-Time Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let compSortCol = -1;
let compSortDir = 'asc';

function applyCompanySortFromSelect(val) {
    if (!val) {
        document.querySelectorAll('#companiesTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        compSortCol = -1;
        return;
    }

    const sortMap = {
        'name_asc': [1, 'text', 'asc'],
        'name_desc': [1, 'text', 'desc'],
        'code_asc': [0, 'text', 'asc'],
        'code_desc': [0, 'text', 'desc'],
        'contact_asc': [2, 'text', 'asc'],
        'tin_asc': [4, 'text', 'asc'],
        'staff_desc': [5, 'number', 'desc'],
        'staff_asc': [5, 'number', 'asc'],
        'status_asc': [6, 'text', 'asc'],
    };

    if (sortMap[val]) {
        sortCompaniesTable(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortCompaniesTable(colIndex, dataType, forceDir = null) {
    const tableBody = document.getElementById('companiesTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr.company-row'));
    const headers = document.querySelectorAll('#companiesTable thead th.sortable');

    if (forceDir) {
        compSortDir = forceDir;
        compSortCol = colIndex;
    } else {
        if (compSortCol === colIndex) {
            compSortDir = compSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            compSortCol = colIndex;
            compSortDir = 'asc';
        }
    }

    headers.forEach((th, idx) => {
        const icon = th.querySelector('.sort-icon i');
        if (idx === colIndex) {
            th.classList.remove('sorted-asc', 'sorted-desc');
            th.classList.add(compSortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) {
                icon.className = compSortDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
            }
        } else {
            th.classList.remove('sorted-asc', 'sorted-desc');
            if (icon) {
                icon.className = 'ph ph-arrows-down-up';
            }
        }
    });

    // Synchronize Toolbar Sort Dropdown
    const sortSelect = document.getElementById('companySortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'code_asc', '0_desc': 'code_desc',
            '1_asc': 'name_asc', '1_desc': 'name_desc',
            '2_asc': 'contact_asc',
            '4_asc': 'tin_asc',
            '5_desc': 'staff_desc', '5_asc': 'staff_asc',
            '6_asc': 'status_asc'
        };
        sortSelect.value = keyMap[colIndex + '_' + compSortDir] || '';
    }

    rows.sort((a, b) => {
        let valA = '', valB = '';
        if (colIndex === 0) {
            valA = a.getAttribute('data-code') || '';
            valB = b.getAttribute('data-code') || '';
        } else if (colIndex === 1) {
            valA = a.getAttribute('data-name') || '';
            valB = b.getAttribute('data-name') || '';
        } else if (colIndex === 2) {
            valA = a.getAttribute('data-contact') || '';
            valB = b.getAttribute('data-contact') || '';
        } else if (colIndex === 4) {
            valA = a.getAttribute('data-tin') || '';
            valB = b.getAttribute('data-tin') || '';
        } else if (colIndex === 5) {
            const numA = parseFloat(a.getAttribute('data-staff')) || 0;
            const numB = parseFloat(b.getAttribute('data-staff')) || 0;
            return compSortDir === 'asc' ? numA - numB : numB - numA;
        } else if (colIndex === 6) {
            valA = a.getAttribute('data-status') || '';
            valB = b.getAttribute('data-status') || '';
        }

        const cmp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
        return compSortDir === 'asc' ? cmp : -cmp;
    });

    const noResultsRow = document.getElementById('noCompanyResultsRow');
    rows.forEach(r => tableBody.appendChild(r));
    if (noResultsRow) tableBody.appendChild(noResultsRow);

    // Re-apply filter + pagination after sort reorders DOM
    filterCompaniesTable();
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    openEditCompanyModal,
    compGetPerPage,
    compChangePerPage,
    compGoToPage,
    renderCompPage,
    filterCompaniesTable,
    resetCompaniesFilters,
    applyCompanySortFromSelect,
    sortCompaniesTable,
});
