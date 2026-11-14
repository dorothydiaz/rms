/**
 * RMS Table Standard — column visibility settings (RFQ Directory parity) for plain data tables.
 * Works from the DOM header so it survives JS re-renders of <tbody>: visibility is enforced by a
 * generated stylesheet keyed on the table id, persisted in localStorage.
 * First and last (Actions) columns are locked visible.
 */
(function () {
    // Tables that already ship their own column settings (RFQ / PO / UOM / Stock In / Products): style + sticky only.
    const OWN_SETTINGS_IDS = ['rfqDirectoryTable', 'poDirectoryTable', 'uomDirectoryTable', 'queueTable', 'masterProductTable', 'stocksOverviewTable', 'schedMatrixTable'];
    const TABLE_SELECTOR = 'table.hr-table, table.vendor-table, table.prd-table, table.bom-table, table.rr-table, table.biz-table, table.org-dir-table, table.trf-table, table.out-table, table.wst-table, table.stk-table, table.uom-table, table.inv-master-table, table.rfq-table, table.po-table';
    const EXCLUDE = '[class*="modal"], dialog, .swal2-container, .inv-subtable, .rfq-subtable, .uom-subtable, .stk-subtable, .grn-items-table, #intakeTable, #rfqTableContainer table, #poItemsTable, .sched-matrix-table, [id*="print"]';
    let seq = 0;
    const STORE = 'rms_tbl_hidden_cols_v1:';
    let openDropdown = null;

    const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    function headers(table) {
        return Array.from(table.querySelectorAll('thead tr:first-child > th'));
    }

    function labelOf(th) {
        const c = th.cloneNode(true);
        c.querySelectorAll('[style*="display:none"], [style*="display: none"], .rms-col-gear').forEach((n) => n.remove());
        return (c.textContent || '').replace(/\s+/g, ' ').trim() || 'Column';
    }

    function loadHidden(id) {
        try { return new Set(JSON.parse(localStorage.getItem(STORE + id) || '[]')); } catch (e) { return new Set(); }
    }

    function saveHidden(id, set) {
        localStorage.setItem(STORE + id, JSON.stringify(Array.from(set)));
    }

    function applyHidden(table, hidden) {
        const id = table.id;
        let style = document.getElementById('rms-cols-' + id);
        if (!style) {
            style = document.createElement('style');
            style.id = 'rms-cols-' + id;
            document.head.appendChild(style);
        }
        style.textContent = Array.from(hidden)
            .map((i) => `#${id} thead tr > :nth-child(${i + 1}), #${id} tbody tr:not([colspan]) > :nth-child(${i + 1}) { display: none; }`)
            .join('\n');
    }

    function closeDropdown() {
        if (openDropdown) { openDropdown.remove(); openDropdown = null; }
    }

    function openSettings(table, btn) {
        closeDropdown();
        const ths = headers(table);
        const hidden = loadHidden(table.id);
        const dd = document.createElement('div');
        dd.className = 'rfq-table-settings-dropdown is-active';
        dd.style.display = 'block';
        dd.addEventListener('click', (e) => e.stopPropagation());

        const render = () => {
            const visible = ths.length - hidden.size;
            dd.innerHTML = `
                <div class="rfq-settings-dropdown-header">
                    <div class="rfq-settings-title-row">
                        <div class="rfq-settings-title"><i class="ph ph-gear-six"></i><span>Table Columns</span></div>
                        <span class="rfq-col-badge-default">${visible} of ${ths.length} visible</span>
                    </div>
                    <div class="rfq-settings-links-row">
                        <button type="button" class="rfq-settings-link-btn" data-act="reset">Reset to Default</button>
                        <button type="button" class="rfq-settings-link-btn" data-act="all">Select All</button>
                    </div>
                </div>
                <div class="rfq-settings-col-list">
                    ${ths.map((th, i) => {
                        const locked = i === 0 || i === ths.length - 1;
                        return `<div class="rfq-dropdown-col-item"><label class="rfq-col-item-label">
                            <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                                <input type="checkbox" data-idx="${i}" ${hidden.has(i) ? '' : 'checked'} ${locked ? 'disabled' : ''}>
                                <span class="rfq-col-name">${esc(labelOf(th))}</span>
                            </div>
                            ${locked ? '<span style="font-size:0.65rem;color:#94a3b8;font-weight:600;">(Locked)</span>' : ''}
                        </label></div>`;
                    }).join('')}
                </div>`;
        };
        render();

        dd.addEventListener('change', (e) => {
            const cb = e.target.closest('input[data-idx]');
            if (!cb) return;
            const i = Number(cb.dataset.idx);
            cb.checked ? hidden.delete(i) : hidden.add(i);
            saveHidden(table.id, hidden);
            applyHidden(table, hidden);
            render();
        });
        dd.addEventListener('click', (e) => {
            const act = e.target.closest('[data-act]');
            if (!act) return;
            hidden.clear();
            saveHidden(table.id, hidden);
            applyHidden(table, hidden);
            render();
        });

        document.body.appendChild(dd);
        const r = btn.getBoundingClientRect();
        dd.style.top = Math.min(r.bottom + 6, window.innerHeight - 380) + 'px';
        dd.style.left = Math.max(8, Math.min(r.right - 370, window.innerWidth - 378)) + 'px';
        dd.style.zIndex = 10050;
        openDropdown = dd;
    }

    function enhance(table) {
        if (table.dataset.rmsStd) return;
        const ths = headers(table);
        if (!ths.length) return;
        table.dataset.rmsStd = '1';
        table.classList.add('rms-std-table');
        if (!table.id) table.id = 'rms-tbl-' + location.pathname.replace(/\W+/g, '_') + '-' + (++seq);
        const lastTxt = (ths[ths.length - 1].textContent || '').trim();
        if (/action/i.test(lastTxt)) table.classList.add('rms-std-has-actions');
        if (table.parentElement) table.parentElement.classList.add('rms-std-scroll');
        if (OWN_SETTINGS_IDS.includes(table.id) || ths.length < 4 || table.querySelector('.rfq-th-settings-btn, .uom-th-settings-btn, .rms-col-gear')) return;

        const last = ths[ths.length - 1];
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rfq-th-settings-btn rms-col-gear';
        btn.title = 'Customize visible columns';
        btn.innerHTML = '<i class="ph ph-gear"></i>';
        btn.style.marginLeft = '6px';
        btn.addEventListener('click', (e) => { e.stopPropagation(); openSettings(table, btn); });
        last.appendChild(btn);

        applyHidden(table, loadHidden(table.id));
    }

    function scan() {
        document.querySelectorAll(TABLE_SELECTOR).forEach((t) => { if (!t.closest(EXCLUDE)) enhance(t); });
        OWN_SETTINGS_IDS.forEach((id) => { const t = document.getElementById(id); if (t && t.parentElement) { t.classList.add('rms-std-table'); t.parentElement.classList.add('rms-std-scroll'); } });
    }

    function init() {
        scan();
        setTimeout(scan, 800); // pick up tables rendered after load
        document.addEventListener('click', closeDropdown);
        window.addEventListener('resize', closeDropdown);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

