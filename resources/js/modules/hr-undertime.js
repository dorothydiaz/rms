function filterUndertimeTable() {
    const q = (document.getElementById('utSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.undertime-row');
    let visible = 0;

    rows.forEach(r => {
        const search = r.getAttribute('data-search') || '';
        const matchQ = !q || search.includes(q);

        if (matchQ) {
            r.style.display = '';
            visible++;
        } else {
            r.style.display = 'none';
        }
    });

    const badge = document.getElementById('utVisibleCount');
    if (badge) badge.innerText = visible;

    const pagInfo = document.querySelector('#utPaginationBar .hr-pagination-info');
    if (pagInfo) {
        if (q) {
            pagInfo.innerHTML = `Showing <strong>${visible === 0 ? 0 : 1}</strong> to <strong>${visible}</strong> of <strong>${visible}</strong> filtered records`;
        } else {
            pagInfo.innerHTML = window.HrUndertimeConfig?.defaultPaginationHtml || pagInfo.innerHTML;
        }
    }

    const empty = document.getElementById('emptyUtRow');
    if (empty) {
        empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyUtFilter(key, val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set(key, val);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function toggleSelectAllUt(master) {
    const checkboxes = document.querySelectorAll('.ut-record-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
    updateBatchUtState();
}

function updateBatchUtState() {
    const checked = document.querySelectorAll('.ut-record-checkbox:checked');
    const btn = document.getElementById('btnBatchAuthorize');
    const countSpan = document.getElementById('batchUtCount');
    if (btn && countSpan) {
        countSpan.innerText = checked.length;
        btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    }
}

function submitBatchAuthorize() {
    const checked = document.querySelectorAll('.ut-record-checkbox:checked');
    if (checked.length === 0) return;

    if (!confirm(`Authorize ${checked.length} selected undertime record(s)?`)) return;

    const form = document.getElementById('batchAuthorizeForm');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (window.HrUndertimeConfig?.csrfToken || '');
    form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}">`;
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'record_ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });
    form.submit();
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    filterUndertimeTable,
    applyUtFilter,
    toggleSelectAllUt,
    updateBatchUtState,
    submitBatchAuthorize,
});
