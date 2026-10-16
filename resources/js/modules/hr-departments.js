function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Departments
// =========================================================================
let _deptPage = 1, _deptRows = [];
function deptGetPerPage() { return parseInt(document.getElementById('deptPerPageSelect')?.value || '15', 10); }
function deptChangePerPage() { _deptPage = 1; renderDeptPage(); }
function deptGoToPage(p) { _deptPage = p; renderDeptPage(); const w = document.getElementById('deptsTableBody')?.closest('.hr-table-wrapper'); if(w) w.scrollTop = 0; }

function renderDeptPage() {
    const pp = deptGetPerPage(), total = _deptRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_deptPage > totalPages) _deptPage = totalPages;
    const start = (_deptPage - 1) * pp, end = Math.min(start + pp, total);
    document.querySelectorAll('#deptsTableBody tr.dept-row').forEach(r => r.style.display = 'none');
    _deptRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    const bar = document.getElementById('deptPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('deptPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> departments`;
    const nav = document.getElementById('deptPageNav');
    if (!nav) return;
    let h = _deptPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="deptGoToPage(${_deptPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _deptPage-2), pe = Math.min(totalPages, _deptPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="deptGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _deptPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="deptGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="deptGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _deptPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="deptGoToPage(${_deptPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

function filterDeptsTable() {
    _deptRows = Array.from(document.querySelectorAll('#deptsTableBody tr.dept-row'));
    _deptPage = 1;
    renderDeptPage();
}
window.filterDeptsTable = filterDeptsTable;

document.addEventListener('DOMContentLoaded', () => { filterDeptsTable(); });
document.addEventListener('rmsTableSorted', () => { filterDeptsTable(); });
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    deptGetPerPage,
    deptChangePerPage,
    deptGoToPage,
    renderDeptPage,
    filterDeptsTable,
});
