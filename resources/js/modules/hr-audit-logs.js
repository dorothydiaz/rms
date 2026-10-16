/**
 * Audit Logs & Security Monitoring Module
 */

// =========================================================================
// Client-Side Pagination — Audit Logs
// =========================================================================
let _logsPage = 1, _logsRows = [];
function logsGetPerPage() { return parseInt(document.getElementById('logsPerPageSelect')?.value || '10', 10); }
function logsChangePerPage() { _logsPage = 1; renderLogsPage(); }
function logsGoToPage(p) { _logsPage = p; renderLogsPage(); const w = document.getElementById('logsTableWrapper'); if(w) w.scrollTop = 0; }

function renderLogsPage() {
    const pp = logsGetPerPage(), total = _logsRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_logsPage > totalPages) _logsPage = totalPages;
    const start = (_logsPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#logsTableBody tr.log-row').forEach(r => r.style.display = 'none');
    _logsRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });


    const bar = document.getElementById('logsPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('logsPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start+1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> log entries`;
    const nav = document.getElementById('logsPageNav');
    if (!nav) return;
    let h = _logsPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="logsGoToPage(${_logsPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _logsPage-2), pe = Math.min(totalPages, _logsPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="logsGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _logsPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="logsGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="logsGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _logsPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="logsGoToPage(${_logsPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshLogsPage = function() {
    _logsRows = Array.from(document.querySelectorAll('#logsTableBody tr.log-row'));
    renderLogsPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshLogsPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshLogsPage();
});

Object.assign(window, {
    logsGetPerPage,
    logsChangePerPage,
    logsGoToPage,
    renderLogsPage,
    refreshLogsPage,
});
