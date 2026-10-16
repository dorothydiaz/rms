// Universal Modal Open & Close functions to guarantee working under all environments
window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
};

window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('open');
        if (!document.querySelector('.hr-modal-overlay.open')) {
            document.body.style.overflow = '';
        }
    }
};

// =========================================================================
// Real-Time Table Filter (No Enter Key Required)
// =========================================================================
function filterDocumentsTable() {
    const searchVal = document.getElementById('docSearchInput').value.toLowerCase().trim();
    const typeVal = document.getElementById('docTypeFilter').value.trim();
    const branchVal = document.getElementById('docBranchFilter').value.trim();
    const expiryVal = document.getElementById('docExpiryFilter').value.trim();

    const rows = document.querySelectorAll('#documentsTableBody tr.doc-row');
    const noResultsRow = document.getElementById('noResultsRow');
    let visibleCount = 0;

    rows.forEach(row => {
        const title = row.getAttribute('data-title') || '';
        const docType = row.getAttribute('data-type') || '';
        const emp = row.getAttribute('data-employee') || '';
        const branch = row.getAttribute('data-branch') || '';
        const expStatus = row.getAttribute('data-expiry-status') || '';

        const matchesSearch = !searchVal || 
            title.includes(searchVal) || 
            emp.includes(searchVal) || 
            branch.toLowerCase().includes(searchVal) || 
            docType.toLowerCase().includes(searchVal);

        const matchesType = !typeVal || docType === typeVal;
        const matchesBranch = !branchVal || branch === branchVal;
        const matchesExpiry = !expiryVal || expStatus === expiryVal;

        if (matchesSearch && matchesType && matchesBranch && matchesExpiry) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('docVisibleCount');
    if (countElem) countElem.textContent = visibleCount;

    if (noResultsRow) {
        if (visibleCount === 0 && rows.length > 0) {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    }
}

function resetDocumentFilters() {
    const s = document.getElementById('docSearchInput');
    if (s) s.value = '';
    const t = document.getElementById('docTypeFilter');
    if (t) t.value = '';
    const b = document.getElementById('docBranchFilter');
    if (b) b.value = '';
    const e = document.getElementById('docExpiryFilter');
    if (e) e.value = '';
    const sel = document.getElementById('docSortSelect');
    if (sel) sel.value = '';

    // Clear sort classes from headers
    document.querySelectorAll('#documentsTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    currentSortColumn = -1;

    filterDocumentsTable();
}

// =========================================================================
// Real-Time Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let currentSortColumn = -1;
let currentSortDirection = 'asc';

function applyDocSortFromSelect(val) {
    if (!val) {
        // Reset sort
        document.querySelectorAll('#documentsTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        currentSortColumn = -1;
        return;
    }

    const sortMap = {
        'title_asc': [0, 'text', 'asc'],
        'title_desc': [0, 'text', 'desc'],
        'type_asc': [1, 'text', 'asc'],
        'employee_asc': [2, 'text', 'asc'],
        'employee_desc': [2, 'text', 'desc'],
        'branch_asc': [3, 'text', 'asc'],
        'size_asc': [4, 'number', 'asc'],
        'size_desc': [4, 'number', 'desc'],
        'expiry_asc': [5, 'date', 'asc'],
        'expiry_desc': [5, 'date', 'desc'],
    };

    if (sortMap[val]) {
        sortDocumentsTable(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortDocumentsTable(colIndex, dataType, forceDir = null) {
    const tableBody = document.getElementById('documentsTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr.doc-row'));
    const headers = document.querySelectorAll('#documentsTable thead th.sortable');

    if (forceDir) {
        currentSortDirection = forceDir;
        currentSortColumn = colIndex;
    } else {
        if (currentSortColumn === colIndex) {
            currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortColumn = colIndex;
            currentSortDirection = 'asc';
        }
    }

    // Update Header Sort Icons & Badges
    headers.forEach((th, idx) => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';

        if (idx === colIndex) {
            th.classList.add(currentSortDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) icon.className = currentSortDirection === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
        }
    });

    // Synchronize Toolbar Sort Dropdown
    const sortSelect = document.getElementById('docSortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'title_asc', '0_desc': 'title_desc',
            '1_asc': 'type_asc', '1_desc': 'type_asc',
            '2_asc': 'employee_asc', '2_desc': 'employee_desc',
            '3_asc': 'branch_asc', '3_desc': 'branch_asc',
            '4_asc': 'size_asc', '4_desc': 'size_desc',
            '5_asc': 'expiry_asc', '5_desc': 'expiry_desc',
        };
        sortSelect.value = keyMap[colIndex + '_' + currentSortDirection] || '';
    }

    rows.sort((a, b) => {
        let valA, valB;

        switch (colIndex) {
            case 0: // Title
                valA = a.getAttribute('data-title') || '';
                valB = b.getAttribute('data-title') || '';
                break;
            case 1: // Document Type
                valA = a.getAttribute('data-type') || '';
                valB = b.getAttribute('data-type') || '';
                break;
            case 2: // Employee
                valA = a.getAttribute('data-employee') || '';
                valB = b.getAttribute('data-employee') || '';
                break;
            case 3: // Branch
                valA = a.getAttribute('data-branch') || '';
                valB = b.getAttribute('data-branch') || '';
                break;
            case 4: // File Size
                valA = parseFloat(a.getAttribute('data-size') || 0);
                valB = parseFloat(b.getAttribute('data-size') || 0);
                return currentSortDirection === 'asc' ? valA - valB : valB - valA;
            case 5: // Expiry Date
                valA = parseInt(a.getAttribute('data-expiry-date') || 0, 10);
                valB = parseInt(b.getAttribute('data-expiry-date') || 0, 10);
                // Non-expiring documents placed at bottom
                if (valA === 0) return 1;
                if (valB === 0) return -1;
                return currentSortDirection === 'asc' ? valA - valB : valB - valA;
            default:
                valA = a.children[colIndex].textContent.trim();
                valB = b.children[colIndex].textContent.trim();
        }

        const cmp = String(valA).localeCompare(String(valB), undefined, { numeric: true, sensitivity: 'base' });
        return currentSortDirection === 'asc' ? cmp : -cmp;
    });

    // Re-append sorted rows to tbody
    rows.forEach(row => tableBody.appendChild(row));

    // Keep noResultsRow at bottom
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) tableBody.appendChild(noResultsRow);
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    filterDocumentsTable,
    resetDocumentFilters,
    applyDocSortFromSelect,
    sortDocumentsTable,
});
