function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Positions
// =========================================================================
let _posPage = 1, _posRows = [];
function posGetPerPage() { return parseInt(document.getElementById('posPerPageSelect')?.value || '15', 10); }
function posChangePerPage() { _posPage = 1; renderPosPage(); }
function posGoToPage(p) { _posPage = p; renderPosPage(); const w = document.getElementById('posTableBody')?.closest('.hr-table-wrapper'); if(w) w.scrollTop = 0; }

function renderPosPage() {
    const pp = posGetPerPage(), total = _posRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_posPage > totalPages) _posPage = totalPages;
    const start = (_posPage - 1) * pp, end = Math.min(start + pp, total);
    document.querySelectorAll('#posTableBody tr.pos-row').forEach(r => r.style.display = 'none');
    _posRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    const bar = document.getElementById('posPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('posPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> positions`;
    const nav = document.getElementById('posPageNav');
    if (!nav) return;
    let h = _posPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="posGoToPage(${_posPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _posPage-2), pe = Math.min(totalPages, _posPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="posGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _posPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="posGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="posGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _posPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="posGoToPage(${_posPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshPosPage = function() {
    _posRows = Array.from(document.querySelectorAll('#posTableBody tr.pos-row'));
    renderPosPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshPosPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshPosPage();
});
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    posGetPerPage,
    posChangePerPage,
    posGoToPage,
    renderPosPage,
});
