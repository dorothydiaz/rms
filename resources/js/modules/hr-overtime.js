function filterOvertimeTable() {
    const q = (document.getElementById('otSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.overtime-row');
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

    const badge = document.getElementById('otVisibleCount');
    if (badge) badge.innerText = visible;

    const empty = document.getElementById('emptyOtRow');
    if (empty) {
        empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyOtFilter(key, val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set(key, val);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.ot-record-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
    updateBatchState();
}

function updateBatchState() {
    const checked = document.querySelectorAll('.ot-record-checkbox:checked');
    const btn = document.getElementById('btnBatchApprove');
    const countSpan = document.getElementById('batchCount');
    if (btn && countSpan) {
        countSpan.innerText = checked.length;
        btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    }
}

function submitBatchApprove() {
    const checked = document.querySelectorAll('.ot-record-checkbox:checked');
    if (checked.length === 0) return;

    if (!confirm(`Approve ${checked.length} selected overtime record(s)?`)) return;

    const form = document.getElementById('batchApproveForm');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (window.HrOvertimeConfig?.csrfToken || '');
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
    filterOvertimeTable,
    applyOtFilter,
    toggleSelectAll,
    updateBatchState,
    submitBatchApprove,
});
