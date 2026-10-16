function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Fuzzy Search & Client-Side Pagination for Timekeeping
// =========================================================================
function _tkLevenshtein(a, b) {
    const m = a.length, n = b.length;
    const dp = Array.from({ length: m + 1 }, (_, i) =>
        Array.from({ length: n + 1 }, (_, j) => (i === 0 ? j : j === 0 ? i : 0))
    );
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = a[i-1] === b[j-1]
                ? dp[i-1][j-1]
                : 1 + Math.min(dp[i-1][j], dp[i][j-1], dp[i-1][j-1]);
        }
    }
    return dp[m][n];
}

function _tkFuzzyMatch(haystack, query) {
    if (!query) return true;
    if (haystack.includes(query)) return true;
    const tokens = haystack.split(/[\s,._\-\/]+/).filter(Boolean);
    const qLen = query.length;
    for (const token of tokens) {
        if (token.length < 2) continue;
        const threshold = Math.floor(Math.min(token.length, qLen) / 4) + 1;
        if (_tkLevenshtein(token, query) <= threshold) return true;
    }
    return false;
}

let _tkPage = 1, _tkRows = [];

function tkGetPerPage() {
    return parseInt(document.getElementById('timekeepingPerPageSelect')?.value || '15', 10);
}

function tkChangePerPage() {
    _tkPage = 1;
    renderTkPage();
}

function tkGoToPage(p) {
    _tkPage = p;
    renderTkPage();
    const w = document.getElementById('timekeepingTableBody')?.closest('.hr-table-wrapper');
    if (w) w.scrollTop = 0;
}

function renderTkPage() {
    const pp = tkGetPerPage(), total = _tkRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_tkPage > totalPages) _tkPage = totalPages;
    const start = (_tkPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#timekeepingTableBody tr.timekeeping-row').forEach(r => r.style.display = 'none');
    _tkRows.forEach((r, i) => {
        r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    const bar = document.getElementById('timekeepingPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';

    const info = document.getElementById('timekeepingPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> staff`;

    const badge = document.getElementById('tkStaffCountBadge');
    if (badge) badge.innerText = `${total} Staff`;

    const nav = document.getElementById('timekeepingPageNav');
    if (!nav) return;

    let h = _tkPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="tkGoToPage(${_tkPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;

    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _tkPage - 2), pe = Math.min(totalPages, _tkPage + 2);
    if (ps > 1) {
        h += `<span class="hr-page-num" onclick="tkGoToPage(1)" style="cursor:pointer">1</span>`;
        if (ps > 2) h += `<span class="hr-page-num dots">…</span>`;
    }
    for (let p = ps; p <= pe; p++) {
        h += p === _tkPage
            ? `<span class="hr-page-num active">${p}</span>`
            : `<span class="hr-page-num" onclick="tkGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    }
    if (pe < totalPages) {
        if (pe < totalPages - 1) h += `<span class="hr-page-num dots">…</span>`;
        h += `<span class="hr-page-num" onclick="tkGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`;
    }
    h += `</div>`;

    h += _tkPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="tkGoToPage(${_tkPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;

    nav.innerHTML = h;
}

function filterTimekeepingRows() {
    const q = (document.getElementById('tkSearchInput')?.value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('tkClearSearchBtn');
    if (clearBtn) clearBtn.style.display = q ? 'block' : 'none';

    const allRows = Array.from(document.querySelectorAll('#timekeepingTableBody tr.timekeeping-row'));
    if (!q) {
        _tkRows = allRows;
    } else {
        _tkRows = allRows.filter(r => {
            const id = r.getAttribute('data-emp-id') || '';
            const name = r.getAttribute('data-emp-name') || '';
            const branch = r.getAttribute('data-emp-branch') || '';
            const dept = r.getAttribute('data-emp-dept') || '';
            const pos = r.getAttribute('data-emp-pos') || '';
            return _tkFuzzyMatch(id, q) || _tkFuzzyMatch(name, q) || _tkFuzzyMatch(branch, q) || _tkFuzzyMatch(dept, q) || _tkFuzzyMatch(pos, q);
        });
    }

    _tkPage = 1;
    renderTkPage();
}

function clearTkSearch() {
    const inp = document.getElementById('tkSearchInput');
    if (inp) inp.value = '';
    filterTimekeepingRows();
    inp?.focus();
}


document.addEventListener('DOMContentLoaded', () => {
    filterTimekeepingRows();
});
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    _tkLevenshtein,
    _tkFuzzyMatch,
    tkGetPerPage,
    tkChangePerPage,
    tkGoToPage,
    renderTkPage,
    filterTimekeepingRows,
    clearTkSearch,
});
