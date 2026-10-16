function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Branches
// =========================================================================
let _branchPage = 1, _branchRows = [];
function branchGetPerPage() { return parseInt(document.getElementById('branchPerPageSelect')?.value || '15', 10); }
function branchChangePerPage() { _branchPage = 1; renderBranchPage(); }
function branchGoToPage(p) { _branchPage = p; renderBranchPage(); const w = document.getElementById('branchTableBody')?.closest('.hr-table-wrapper'); if(w) w.scrollTop = 0; }

function renderBranchPage() {
    const pp = branchGetPerPage(), total = _branchRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_branchPage > totalPages) _branchPage = totalPages;
    const start = (_branchPage - 1) * pp, end = Math.min(start + pp, total);
    document.querySelectorAll('#branchTableBody tr.branch-row').forEach(r => r.style.display = 'none');
    _branchRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    const bar = document.getElementById('branchPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('branchPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> branches`;
    const nav = document.getElementById('branchPageNav');
    if (!nav) return;
    let h = _branchPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="branchGoToPage(${_branchPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _branchPage-2), pe = Math.min(totalPages, _branchPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="branchGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _branchPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="branchGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="branchGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _branchPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="branchGoToPage(${_branchPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshBranchPage = function() {
    _branchRows = Array.from(document.querySelectorAll('#branchTableBody tr.branch-row'));
    renderBranchPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshBranchPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshBranchPage();
});
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    branchGetPerPage,
    branchChangePerPage,
    branchGoToPage,
    renderBranchPage,
});
