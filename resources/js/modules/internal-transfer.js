/* internal-transfer.js — Unified Stock Transfer & Branch Request Workbench (Stock In Parity) */
(function () {
    'use strict';

    const CFG = window.InternalTransferConfig || {};
    const ROUTES = CFG.routes || {};
    const BRANCH_CTX = CFG.branchContext || {};
    const INITIAL = (CFG.data && CFG.data.initialPayload) || {};
    const PRODUCTS = (CFG.data && CFG.data.initialProducts) || [];
    const BRANCHES = (CFG.data && CFG.data.initialBranches) || [];
    const CSRF = (CFG.data && CFG.data.csrfToken) || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const PRODUCTS_BY_ID = new Map(PRODUCTS.map((p) => [Number(p.id), p]));
    const PRODUCTS_BY_SKU = new Map(PRODUCTS.map((p) => [String(p.sku).toUpperCase(), p]));
    const BRANCHES_BY_ID = new Map(BRANCHES.map((b) => [Number(b.branch_id || b.id), b]));

    const VARIANCE_REASONS = [
        { value: '', label: 'Select variance reason...' },
        { value: 'DAMAGED_IN_TRANSIT', label: 'Damaged in Transit' },
        { value: 'SHORT_PICKED', label: 'Short-Picked by Source' },
        { value: 'SPOILED_TEMPERATURE', label: 'Spoiled / Temp Breach' },
        { value: 'LOST_IN_TRANSIT', label: 'Lost / Missing in Transit' },
    ];

    const QUEUE_COLUMNS = [
        { key: 'transfer_number', label: 'Transfer / Receipt #', width: '16%', defaultVisible: true },
        { key: 'transfer_type', label: 'Flow Type', width: '12%', defaultVisible: true },
        { key: 'source_location', label: 'Source Branch', width: '14%', defaultVisible: true },
        { key: 'destination_location', label: 'Destination Branch', width: '14%', defaultVisible: true },
        { key: 'items_count', label: 'Lines & Qty', width: '11%', defaultVisible: true },
        { key: 'progress', label: 'Shipped / Accepted', width: '12%', defaultVisible: true },
        { key: 'total_valuation', label: 'Valuation (₱)', width: '10%', defaultVisible: false },
        { key: 'status', label: 'Status', width: '11%', defaultVisible: true },
        { key: 'action', label: 'Action', width: '10%', defaultVisible: true },
    ];

    const COL_STORAGE_KEY = 'rms_trf_queue_cols_v1';

    const state = {
        activeTab: 'queue',
        perspective: 'all',
        page: 1,
        searchTimer: null,
        queueRows: INITIAL.transfers || [],
        pagination: INITIAL.pagination || { current_page: 1, last_page: 1, per_page: 25, total: 0 },
        stats: INITIAL.stats || {},
        visibleCols: loadVisibleCols(),
        workbench: createEmptyWorkbench('BRANCH_REQUEST'),
        sourceStockMap: {},
        destStockMap: {},
        pendingPartialPayload: null,
        pendingReasonMode: null,
    };

    function loadVisibleCols() {
        try {
            const raw = localStorage.getItem(COL_STORAGE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length > 0) return new Set(parsed);
            }
        } catch (e) {}
        return new Set(QUEUE_COLUMNS.filter((c) => c.defaultVisible).map((c) => c.key));
    }

    function saveVisibleCols() {
        try {
            localStorage.setItem(COL_STORAGE_KEY, JSON.stringify([...state.visibleCols]));
        } catch (e) {}
    }

    function createEmptyWorkbench(mode) {
        const activeId = BRANCH_CTX.activeBranchId ? Number(BRANCH_CTX.activeBranchId) : null;
        const firstOther = BRANCHES.find((b) => Number(b.branch_id) !== activeId) || BRANCHES[1] || BRANCHES[0];
        const firstPrimary = BRANCHES.find((b) => /commissary|central|main/i.test(b.name || '')) || BRANCHES[0];

        let srcId = null;
        let dstId = null;
        if (mode === 'BRANCH_REQUEST') {
            dstId = activeId || (BRANCHES[1]?.branch_id ?? BRANCHES[0]?.branch_id ?? null);
            const dstBranch = BRANCHES_BY_ID.get(Number(dstId));
            const configuredSrc = dstBranch?.default_source_branch_id ? Number(dstBranch.default_source_branch_id) : null;
            if (configuredSrc && configuredSrc !== Number(dstId) && BRANCHES_BY_ID.has(configuredSrc)) {
                srcId = configuredSrc;
            } else {
                srcId = (firstPrimary && Number(firstPrimary.branch_id) !== Number(dstId))
                    ? Number(firstPrimary.branch_id)
                    : (BRANCHES.find((b) => Number(b.branch_id) !== Number(dstId))?.branch_id ?? null);
            }
        } else {
            srcId = activeId || (firstPrimary?.branch_id ?? null);
            dstId = (firstOther && Number(firstOther.branch_id) !== Number(srcId))
                ? Number(firstOther.branch_id)
                : (BRANCHES.find((b) => Number(b.branch_id) !== Number(srcId))?.branch_id ?? null);
        }

        return {
            id: null,
            transfer_number: mode === 'BRANCH_REQUEST' ? 'REQ-TRF-AUTO' : 'DIR-TRF-AUTO',
            receipt_number: null,
            transfer_type: mode,
            status: 'NEW',
            priority: 'NORMAL',
            source_branch_id: srcId ? Number(srcId) : null,
            destination_branch_id: dstId ? Number(dstId) : null,
            required_at: new Date().toISOString().slice(0, 10),
            reason_code: mode === 'BRANCH_REQUEST' ? 'Branch Replenishment Request' : 'Commissary Bulk Batch Push',
            carrier_name: '',
            driver_plate: '',
            waybill_number: '',
            notes: '',
            requested_by: CFG.user?.name || 'Branch Supervisor',
            approved_by: null,
            dispatched_by: null,
            received_by: null,
            idempotency_key: generateUuid(),
            items: [],
        };
    }

    function generateUuid() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return 'trf-' + Date.now() + '-' + Math.random().toString(16).slice(2, 10);
    }

    // =====================================================================
    // INITIALIZATION & GLOBAL LISTENERS
    // =====================================================================
    document.addEventListener('DOMContentLoaded', () => {
        populateBranchSelects();
        renderQueueGearMenu();
        renderQueueHeader();
        renderQueueRows();
        renderKpis();
        renderPagination();
        hydrateWorkbenchDom();

        document.getElementById('trfTableSettingsDropdown')?.addEventListener('click', (e) => e.stopPropagation());
        document.getElementById('trfItemPickerDropdown')?.addEventListener('click', (e) => e.stopPropagation());

        document.addEventListener('click', () => {
            const gearMenu = document.getElementById('trfTableSettingsDropdown');
            if (gearMenu) gearMenu.style.display = 'none';
            const picker = document.getElementById('trfItemPickerDropdown');
            if (picker) picker.hidden = true;
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeBackorderModal();
                closeReasonModal();
                toggleTrfSlipDrawer(false);
                const picker = document.getElementById('trfItemPickerDropdown');
                if (picker) picker.hidden = true;
                const gear = document.getElementById('trfTableSettingsDropdown');
                if (gear) gear.style.display = 'none';
                return;
            }
            if (e.key === '/' && state.activeTab === 'queue' && !isTypingInInput(e.target)) {
                e.preventDefault();
                document.getElementById('trfSearchInput')?.focus();
            } else if (e.key === 'F2' && state.activeTab === 'builder') {
                e.preventDefault();
                document.getElementById('trfScanInput')?.focus();
            } else if (e.key === 'F10' && state.activeTab === 'builder') {
                e.preventDefault();
                const btn = document.getElementById('btnTrfPrimaryAction');
                if (btn && !btn.disabled && !btn.hidden) handlePrimaryWorkbenchAction();
            }
        });
    });

    function isTypingInInput(el) {
        if (!el) return false;
        const tag = el.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    // =====================================================================
    // TAB & QUEUE ENGINE (O(1) SERVER PAGINATION)
    // =====================================================================
    window.switchTrfTab = function (tab) {
        state.activeTab = tab;
        const isQueue = tab === 'queue';
        document.getElementById('tabBtnTrfQueue')?.classList.toggle('active', isQueue);
        document.getElementById('tabBtnTrfBuilder')?.classList.toggle('active', !isQueue);
        const qPanel = document.getElementById('panelTrfQueue');
        const bPanel = document.getElementById('panelTrfBuilder');
        if (qPanel) {
            qPanel.classList.toggle('trf-panel-hidden', !isQueue);
            qPanel.style.display = isQueue ? '' : 'none';
        }
        if (bPanel) {
            bPanel.classList.toggle('trf-panel-hidden', isQueue);
            bPanel.style.display = isQueue ? 'none' : '';
        }
    };

    function syncKpiActiveHighlight(activeKey) {
        document.querySelectorAll('#panelTrfQueue .trf-kpi-btn').forEach((btn) => {
            btn.classList.toggle('is-active-filter', Boolean(activeKey) && btn.dataset.kpiFilter === activeKey);
        });
    }

    window.setPerspectiveFilter = function (persp) {
        state.perspective = persp;
        document.querySelectorAll('#trfPerspectiveBar .trf-persp-btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.persp === persp);
        });
        syncKpiActiveHighlight(persp === 'all' ? '' : persp);
        fetchTransferQueue(1);
    };

    window.applyKpiQuickFilter = function (kind) {
        const statusSel = document.getElementById('trfStatusFilter');
        if (kind === 'completed') {
            state.perspective = 'all';
            document.querySelectorAll('#trfPerspectiveBar .trf-persp-btn').forEach((btn) => {
                btn.classList.toggle('active', btn.dataset.persp === 'all');
            });
            if (statusSel) statusSel.value = 'COMPLETED';
            syncKpiActiveHighlight('completed');
            fetchTransferQueue(1);
            return;
        }
        if (statusSel) statusSel.value = 'ALL';
        setPerspectiveFilter(kind);
    };

    window.resetTrfFilters = function () {
        const searchEl = document.getElementById('trfSearchInput');
        const statusEl = document.getElementById('trfStatusFilter');
        const typeEl = document.getElementById('trfTypeFilter');
        const branchEl = document.getElementById('trfBranchFilter');
        if (searchEl) searchEl.value = '';
        if (statusEl) statusEl.value = 'ALL';
        if (typeEl) typeEl.value = 'ALL';
        if (branchEl) branchEl.value = '';
        setPerspectiveFilter('all');
    };

    window.onTrfSearchInput = function () {
        clearTimeout(state.searchTimer);
        state.searchTimer = setTimeout(() => fetchTransferQueue(1), 280);
    };

    window.onTrfFilterChanged = function () {
        fetchTransferQueue(1);
    };

    window.fetchTransferQueue = async function (page = 1) {
        state.page = page;
        const params = new URLSearchParams({
            page: String(page),
            per_page: '25',
            perspective: state.perspective,
            status: document.getElementById('trfStatusFilter')?.value || 'ALL',
            transfer_type: document.getElementById('trfTypeFilter')?.value || 'ALL',
            branch_id: document.getElementById('trfBranchFilter')?.value || '',
            search: document.getElementById('trfSearchInput')?.value.trim() || '',
        });

        try {
            const res = await fetch(`${ROUTES.internalTransferData}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed to load transfers.');

            const data = json.data || {};
            state.queueRows = data.transfers || [];
            state.pagination = data.pagination || state.pagination;
            state.stats = data.stats || state.stats;

            renderKpis();
            renderQueueRows();
            renderPagination();
        } catch (err) {
            showToast(err.message || 'Failed to refresh transfer queue.', 'error');
        }
    };

    function renderKpis() {
        const s = state.stats || {};
        setText('badgeQueueTotal', s.totalTransfers ?? 0);
        setText('trfKpiIncoming', `${s.incomingRequestsCount ?? 0} Open`);
        setText('trfKpiOutgoing', `${s.outgoingRequestsCount ?? 0} Pending`);
        setText('trfKpiTransit', `${s.toReceiveCount ?? 0} En Route`);
        setText('trfKpiTransitSub', `${s.inTransitCount ?? 0} total in-transit shipments`);
        setText('trfKpiCompleted', `${s.completedCount ?? 0} Done`);
        setText('trfKpiVarianceSub', `${s.discrepancyCount ?? 0} with transit variance`);
    }

    function renderQueueGearMenu() {
        const list = document.getElementById('trfColCheckboxesList');
        if (!list) return;
        list.innerHTML = QUEUE_COLUMNS.map((col) => {
            const checked = state.visibleCols.has(col.key) ? 'checked' : '';
            return `
                <label class="rfq-settings-col-item" style="display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;font-size:12px;">
                    <input type="checkbox" ${checked} onchange="toggleTrfQueueCol('${col.key}', this.checked)">
                    <span>${escapeHtml(col.label)}</span>
                </label>
            `;
        }).join('');
        setText('trfColActiveCounter', `${state.visibleCols.size} of ${QUEUE_COLUMNS.length} visible`);
    }

    window.toggleTrfQueueCol = function (key, show) {
        if (show) state.visibleCols.add(key);
        else if (state.visibleCols.size > 2) state.visibleCols.delete(key);
        saveVisibleCols();
        renderQueueGearMenu();
        renderQueueHeader();
        renderQueueRows();
    };

    window.resetTrfQueueCols = function () {
        state.visibleCols = new Set(QUEUE_COLUMNS.filter((c) => c.defaultVisible).map((c) => c.key));
        saveVisibleCols();
        renderQueueGearMenu();
        renderQueueHeader();
        renderQueueRows();
    };

    window.showAllTrfQueueCols = function () {
        state.visibleCols = new Set(QUEUE_COLUMNS.map((c) => c.key));
        saveVisibleCols();
        renderQueueGearMenu();
        renderQueueHeader();
        renderQueueRows();
    };

    window.toggleTrfGearDropdown = function (ev) {
        ev.stopPropagation();
        const menu = document.getElementById('trfTableSettingsDropdown');
        if (!menu) return;
        const willOpen = menu.style.display === 'none' || !menu.style.display;
        if (!willOpen) {
            menu.style.display = 'none';
            return;
        }
        const rect = ev.currentTarget.getBoundingClientRect();
        menu.style.display = 'block';
        menu.style.top = `${Math.min(window.innerHeight - 280, rect.bottom + 6)}px`;
        menu.style.left = `${Math.max(12, Math.min(window.innerWidth - 332, rect.right - 290))}px`;
    };

    function renderQueueHeader() {
        const thead = document.getElementById('trfQueueTheadRow');
        const colgroup = document.getElementById('trfQueueColgroup');
        if (!thead) return;
        const activeCols = QUEUE_COLUMNS.filter((c) => state.visibleCols.has(c.key));
        if (colgroup) {
            colgroup.innerHTML = activeCols.map((col) => `<col style="width:${col.width};">`).join('');
        }
        thead.innerHTML = activeCols.map((col, idx) => {
            const isLast = idx === activeCols.length - 1;
            const align = col.key === 'total_valuation' ? 'text-align:right;' : (col.key === 'status' || col.key === 'action' ? 'text-align:center;' : '');
            if (isLast) {
                return `<th style="${align} white-space:nowrap;">
                    <div style="display:inline-flex;align-items:center;gap:6px;">
                        <span>${escapeHtml(col.label)}</span>
                        <button type="button" class="rfq-th-settings-btn" onclick="toggleTrfGearDropdown(event)" title="Configure visible columns" aria-label="Configure visible columns">
                            <i class="ph ph-gear-six" aria-hidden="true"></i>
                        </button>
                    </div>
                </th>`;
            }
            return `<th style="${align} white-space:nowrap;">${escapeHtml(col.label)}</th>`;
        }).join('');
    }

    function hasActiveQueueFilter() {
        const q = (document.getElementById('trfSearchInput')?.value || '').trim();
        const st = document.getElementById('trfStatusFilter')?.value || 'ALL';
        const tp = document.getElementById('trfTypeFilter')?.value || 'ALL';
        const br = document.getElementById('trfBranchFilter')?.value || '';
        return Boolean(q || st !== 'ALL' || tp !== 'ALL' || br || state.perspective !== 'all');
    }

    function renderQueueRows() {
        const tbody = document.getElementById('trfQueueTableBody');
        if (!tbody) return;
        const activeCols = QUEUE_COLUMNS.filter((c) => state.visibleCols.has(c.key));

        if (!state.queueRows.length) {
            const filtered = hasActiveQueueFilter();
            tbody.innerHTML = `<tr><td colspan="${activeCols.length}">
                <div class="trf-empty-state">
                    <div class="trf-empty-icon"><i class="ph ph-arrows-left-right" aria-hidden="true"></i></div>
                    <div class="trf-empty-title">${filtered ? 'No matching transfers found' : 'No stock transfers or requests yet'}</div>
                    <div class="trf-empty-desc">${filtered
                        ? 'Adjust your perspective tab, status, branch filter, or search query to view more activity.'
                        : 'Request stock from another branch or dispatch a direct transfer to start tracking transit custody.'}</div>
                    <div class="trf-empty-actions">
                        ${filtered ? `<button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetTrfFilters()"><i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i> Reset Filters</button>` : ''}
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="startNewTransferWorkbench('BRANCH_REQUEST')"><i class="ph ph-hand-grabbing" aria-hidden="true"></i> Request Stock</button>
                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="startNewTransferWorkbench('CUSTOM_PUSH')"><i class="ph ph-paper-plane-tilt" aria-hidden="true"></i> Direct Transfer</button>
                    </div>
                </div>
            </td></tr>`;
            return;
        }

        tbody.innerHTML = state.queueRows.map((trf) => {
            const cells = activeCols.map((col) => renderQueueCell(trf, col.key)).join('');
            return `<tr style="cursor:pointer;" onclick="openTransferWorkbench(${Number(trf.id)})">${cells}</tr>`;
        }).join('');
    }

    function renderQueueCell(trf, key) {
        const st = String(trf.status || 'REQUESTED').toUpperCase();
        const activeId = BRANCH_CTX.activeBranchId ? Number(BRANCH_CTX.activeBranchId) : null;
        const isConsolidated = Boolean(BRANCH_CTX.isConsolidated);
        const isSource = !activeId || isConsolidated || Number(trf.source_branch_id) === activeId;
        const isDest = !activeId || isConsolidated || Number(trf.destination_branch_id) === activeId;

        switch (key) {
            case 'transfer_number':
                return `<td>
                    <div class="trf-mono" style="color:var(--trf-primary-strong);">${escapeHtml(trf.transfer_number)}</div>
                    ${trf.receipt_number ? `<div class="trf-mono" style="font-size:11px;color:#047857;">Rec: ${escapeHtml(trf.receipt_number)}</div>` : ''}
                    <div style="font-size:11px;color:#64748b;">${formatDate(trf.created_at)}</div>
                </td>`;
            case 'transfer_type': {
                const t = String(trf.transfer_type || 'CUSTOM_PUSH');
                const cls = t === 'CUSTOM_PUSH' ? 'is-push' : (t === 'BACKORDER_REQUEST' ? 'is-bo' : 'is-pull');
                const lbl = t === 'CUSTOM_PUSH' ? 'Direct Push' : (t === 'BACKORDER_REQUEST' ? 'Backorder' : 'Branch Request');
                return `<td><span class="trf-type-tag ${cls}">${lbl}</span></td>`;
            }
            case 'source_location':
                return `<td><strong>${escapeHtml(trf.source_location || '—')}</strong></td>`;
            case 'destination_location':
                return `<td><strong>${escapeHtml(trf.destination_location || '—')}</strong></td>`;
            case 'items_count': {
                const cnt = trf.items_count ?? trf.total_items_count ?? 0;
                const req = Number(trf.total_requested_qty || 0).toFixed(2);
                return `<td class="trf-num">${cnt} item(s) · <strong>${req}</strong></td>`;
            }
            case 'progress': {
                const ship = Number(trf.total_transferred_qty || 0).toFixed(2);
                const rec = Number(trf.total_received_qty || 0).toFixed(2);
                return `<td class="trf-num"><span style="color:#6d28d9;font-weight:700;">${ship}</span> / <span style="color:#047857;font-weight:700;">${rec}</span></td>`;
            }
            case 'total_valuation':
                return `<td class="trf-num" style="text-align:right;font-weight:700;">₱${Number(trf.total_valuation || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
            case 'status':
                return `<td style="text-align:center;">${renderStatusPill(st)}</td>`;
            case 'action': {
                let btnLabel = 'View';
                let btnClass = 'hr-btn-secondary';
                if (['DRAFT', 'PENDING', 'REQUESTED'].includes(st) && isSource) {
                    btnLabel = 'Review & Dispatch';
                    btnClass = 'hr-btn-primary';
                } else if (st === 'APPROVED' && isSource) {
                    btnLabel = 'Dispatch';
                    btnClass = 'hr-btn-primary';
                } else if (['IN_TRANSIT', 'PARTIALLY_RECEIVED'].includes(st) && isDest) {
                    btnLabel = 'Accept Receipt';
                    btnClass = 'hr-btn-primary';
                }
                return `<td style="text-align:center;" onclick="event.stopPropagation()">
                    <button type="button" class="hr-btn ${btnClass} hr-btn-sm" onclick="openTransferWorkbench(${Number(trf.id)})">${btnLabel}</button>
                </td>`;
            }
            default:
                return '<td>—</td>';
        }
    }

    function renderPagination() {
        const p = state.pagination || { current_page: 1, last_page: 1, total: 0 };
        setText('trfCountVisible', state.queueRows.length);
        setText('trfCountTotal', p.total ?? state.queueRows.length);

        const wrap = document.getElementById('trfPagerButtons');
        if (!wrap) return;
        const cur = Number(p.current_page || 1);
        const last = Number(p.last_page || 1);
        wrap.innerHTML = `
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" ${cur <= 1 ? 'disabled' : ''} onclick="fetchTransferQueue(${cur - 1})">Prev</button>
            <span style="font-size:12px;font-weight:600;color:#475569;padding:0 8px;">Page ${cur} of ${last}</span>
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" ${cur >= last ? 'disabled' : ''} onclick="fetchTransferQueue(${cur + 1})">Next</button>
        `;
    }

    // =====================================================================
    // WORKBENCH HYDRATION, STOCK LOOKUPS & CUSTODY SEPARATION
    // =====================================================================
    window.startNewTransferWorkbench = async function (mode) {
        state.workbench = createEmptyWorkbench(mode);
        switchTrfTab('builder');
        hydrateWorkbenchDom();
        await loadSourceBranchStock();
    };

    window.openTransferWorkbench = async function (transferId) {
        showLock('Loading Transfer Sheet & Live Branch Balances...');
        try {
            const res = await fetch(`${ROUTES.transferDetailBase}/${transferId}`, {
                headers: { Accept: 'application/json' },
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Unable to load transfer detail.');

            const { transfer, sourceStock, destinationStock } = json.data;
            state.sourceStockMap = sourceStock || {};
            state.destStockMap = destinationStock || {};
            state.workbench = {
                ...transfer,
                source_branch_id: transfer.source_branch_id ? Number(transfer.source_branch_id) : null,
                destination_branch_id: transfer.destination_branch_id ? Number(transfer.destination_branch_id) : null,
                required_at: transfer.required_at ? String(transfer.required_at).slice(0, 10) : '',
                items: (transfer.items || []).map((it) => {
                    const ship = Number(it.transferred_qty || 0);
                    const rec = Number(it.received_qty || 0);
                    const prevVar = Number(it.variance_qty || 0);
                    const remaining = Math.max(0, Number((ship - rec - prevVar).toFixed(2)));
                    return {
                        ...it,
                        requested_qty: Number(it.requested_qty || 0),
                        approved_qty: Number(it.approved_qty || 0),
                        transferred_qty: ship,
                        received_qty: rec,
                        variance_qty: prevVar,
                        input_dispatch_qty: Number(it.transferred_qty || it.approved_qty || it.requested_qty || 0),
                        input_receive_qty: remaining,
                        input_variance_qty: 0,
                        input_variance_reason: it.variance_reason || '',
                        uom_multiplier: Number(it.uom_multiplier || 1),
                        base_uom: it.base_uom || it.uom || 'PCS',
                        unit_cost: Number(it.unit_cost || 0),
                    };
                }),
            };

            switchTrfTab('builder');
            hydrateWorkbenchDom();
            if (state.workbench.source_branch_id && Object.keys(state.sourceStockMap).length === 0) {
                await loadSourceBranchStock();
            }
        } catch (err) {
            showToast(err.message || 'Failed to open transfer.', 'error');
        } finally {
            hideLock();
        }
    };

    function populateBranchSelects() {
        const srcEl = document.getElementById('wbSourceBranchSelect');
        const dstEl = document.getElementById('wbDestBranchSelect');
        if (!srcEl || !dstEl) return;

        const opts = ['<option value="">Choose Branch...</option>'].concat(
            BRANCHES.map((b) => `<option value="${b.branch_id}">${escapeHtml(b.name)}${b.code ? ` (${escapeHtml(b.code)})` : ''}</option>`)
        ).join('');

        srcEl.innerHTML = opts;
        dstEl.innerHTML = opts;
    }

    async function loadSourceBranchStock() {
        const srcId = state.workbench.source_branch_id;
        if (!srcId) {
            state.sourceStockMap = {};
            renderWorkbenchLines();
            return;
        }
        try {
            const res = await fetch(`${ROUTES.branchStock}?branch_id=${srcId}`, {
                headers: { Accept: 'application/json' },
            });
            const json = await res.json();
            if (res.ok && json.success) {
                state.sourceStockMap = json.data?.balances || {};
                renderWorkbenchLines();
            }
        } catch (e) {}
    }

    window.onSourceBranchChanged = async function (val) {
        const nextId = val ? Number(val) : null;
        if (nextId && nextId === Number(state.workbench.destination_branch_id)) {
            showToast('Source and destination branches must be different.', 'error');
            document.getElementById('wbSourceBranchSelect').value = state.workbench.source_branch_id || '';
            return;
        }
        state.workbench.source_branch_id = nextId;
        await loadSourceBranchStock();
        hydrateBannerAndHeaderOnly();
    };

    window.onDestBranchChanged = function (val) {
        const nextId = val ? Number(val) : null;
        if (nextId && nextId === Number(state.workbench.source_branch_id)) {
            showToast('Destination branch cannot match source branch.', 'error');
            document.getElementById('wbDestBranchSelect').value = state.workbench.destination_branch_id || '';
            return;
        }
        state.workbench.destination_branch_id = nextId;
        hydrateBannerAndHeaderOnly();
    };

    window.onWorkbenchHeaderChanged = function () {
        const wb = state.workbench;
        wb.priority = document.getElementById('wbPrioritySelect')?.value || 'NORMAL';
        wb.required_at = document.getElementById('wbRequiredAtInput')?.value || '';
        wb.reason_code = document.getElementById('wbReasonCodeSelect')?.value || '';
        wb.carrier_name = document.getElementById('wbCarrierInput')?.value || '';
        wb.driver_plate = document.getElementById('wbPlateInput')?.value || '';
        wb.waybill_number = document.getElementById('wbWaybillInput')?.value || '';
        wb.notes = document.getElementById('wbNotesInput')?.value || '';
        renderSlipPreview();
    };

    function getCustodyPermissions() {
        const wb = state.workbench;
        const activeId = BRANCH_CTX.activeBranchId ? Number(BRANCH_CTX.activeBranchId) : null;
        const isConsolidated = Boolean(BRANCH_CTX.isConsolidated);

        const canSource = isConsolidated || !activeId || !wb.source_branch_id || Number(wb.source_branch_id) === activeId;
        const canDest = isConsolidated || !activeId || !wb.destination_branch_id || Number(wb.destination_branch_id) === activeId;

        return { canSource, canDest, activeId, isConsolidated };
    }

    function hydrateWorkbenchDom() {
        const wb = state.workbench;
        const st = String(wb.status || 'NEW').toUpperCase();
        const isNew = st === 'NEW';
        const isEditableHeader = isNew || st === 'DRAFT';

        setText('tabBuilderTitle', isNew ? (wb.transfer_type === 'CUSTOM_PUSH' ? 'Direct Transfer (Push)' : 'New Stock Request') : wb.transfer_number);
        setText('badgeBuilderStatus', isNew ? 'New Sheet' : st.replace(/_/g, ' '));
        setText('wbTransferNumber', wb.transfer_number || 'AUTO');

        const recSeg = document.getElementById('wbReceiptSeg');
        if (recSeg) {
            recSeg.hidden = !wb.receipt_number;
            setText('wbReceiptNumber', wb.receipt_number || '—');
        }

        const modeTag = document.getElementById('wbModeTag');
        if (modeTag) {
            const isPush = wb.transfer_type === 'CUSTOM_PUSH';
            modeTag.className = `trf-type-tag ${isPush ? 'is-push' : 'is-pull'}`;
            modeTag.textContent = isPush ? 'Direct Transfer (Push)' : (wb.transfer_type === 'BACKORDER_REQUEST' ? 'Backorder Split' : 'Branch Request (Pull)');
        }

        const pill = document.getElementById('wbStatusPill');
        if (pill) {
            pill.outerHTML = `<span id="wbStatusPill" class="trf-status-pill ${statusClass(st)}">${statusLabel(st)}</span>`;
        }

        const srcSel = document.getElementById('wbSourceBranchSelect');
        const dstSel = document.getElementById('wbDestBranchSelect');
        if (srcSel) {
            srcSel.value = wb.source_branch_id || '';
            srcSel.disabled = !isEditableHeader && !(st === 'DRAFT' && !wb.source_branch_id);
        }
        if (dstSel) {
            dstSel.value = wb.destination_branch_id || '';
            dstSel.disabled = !isEditableHeader;
        }

        setInputVal('wbPrioritySelect', wb.priority || 'NORMAL', !isEditableHeader);
        setInputVal('wbRequiredAtInput', wb.required_at || '', !isEditableHeader);
        setInputVal('wbReasonCodeSelect', wb.reason_code || 'Branch Replenishment Request', !isEditableHeader);
        setInputVal('wbCarrierInput', wb.carrier_name || '', ['COMPLETED', 'DISCREPANCY', 'REJECTED', 'CANCELLED'].includes(st));
        setInputVal('wbPlateInput', wb.driver_plate || '', ['COMPLETED', 'DISCREPANCY', 'REJECTED', 'CANCELLED'].includes(st));
        setInputVal('wbWaybillInput', wb.waybill_number || '', ['COMPLETED', 'DISCREPANCY', 'REJECTED', 'CANCELLED'].includes(st));
        setInputVal('wbNotesInput', wb.notes || '', ['COMPLETED', 'DISCREPANCY', 'REJECTED', 'CANCELLED'].includes(st));

        // Expand logistics section when source is preparing dispatch
        const logDetails = document.getElementById('wbLogisticsDetails');
        if (logDetails && (wb.transfer_type === 'CUSTOM_PUSH' || ['APPROVED', 'REQUESTED', 'PENDING', 'IN_TRANSIT'].includes(st))) {
            logDetails.open = true;
        }

        hydrateActionButtonsAndCustody();
        renderLifecyclePipeline();
        hydrateBannerAndHeaderOnly();
        renderWorkbenchLines();
    }

    function hydrateActionButtonsAndCustody() {
        const wb = state.workbench;
        const st = String(wb.status || 'NEW').toUpperCase();
        const { canSource, canDest } = getCustodyPermissions();

        const btnDraft = document.getElementById('btnTrfSaveDraft');
        const btnDanger = document.getElementById('btnTrfSecondaryDanger');
        const btnDangerLbl = document.getElementById('btnTrfSecondaryDangerLabel');
        const btnQuickShip = document.getElementById('btnTrfApproveAndDispatch');
        const btnPrimary = document.getElementById('btnTrfPrimaryAction');
        const btnPrimaryLbl = document.getElementById('btnTrfPrimaryActionLabel');
        const custodyBanner = document.getElementById('trfCustodyNoticeBanner');
        const custodyText = document.getElementById('trfCustodyNoticeText');

        const btnPull = document.getElementById('btnPullLowStock');
        const btnFill = document.getElementById('btnFillAvailable');
        const btnRecvAll = document.getElementById('btnReceiveAllShipped');
        const btnAddWrap = document.getElementById('wbAddItemWrap');
        const scanWrap = document.getElementById('trfScanInput')?.closest('.trf-scan-bar');

        if (btnDraft) btnDraft.hidden = !(st === 'NEW' && wb.transfer_type === 'BRANCH_REQUEST');
        if (btnDanger) btnDanger.hidden = true;
        if (btnQuickShip) btnQuickShip.hidden = true;
        if (btnPrimary) {
            btnPrimary.hidden = false;
            btnPrimary.disabled = false;
        }
        if (custodyBanner) custodyBanner.hidden = true;

        const canAddLines = st === 'NEW' || st === 'DRAFT';
        if (btnPull) btnPull.hidden = !(canAddLines && wb.transfer_type === 'BRANCH_REQUEST');
        if (btnAddWrap) btnAddWrap.hidden = !canAddLines;
        if (scanWrap) scanWrap.style.display = canAddLines ? '' : 'none';
        if (btnFill) btnFill.hidden = !(['DRAFT', 'PENDING', 'REQUESTED', 'APPROVED'].includes(st) && canSource && wb.id);
        if (btnRecvAll) btnRecvAll.hidden = !(['IN_TRANSIT', 'PARTIALLY_RECEIVED'].includes(st) && canDest);

        if (st === 'NEW') {
            if (wb.transfer_type === 'CUSTOM_PUSH') {
                btnPrimaryLbl.textContent = 'Dispatch Now & Deduct Stock →';
                if (!canSource) {
                    btnPrimary.disabled = true;
                    showCustodyNotice('Direct Push transfers can only be dispatched from your currently active branch.');
                }
            } else {
                btnPrimaryLbl.textContent = 'Submit Stock Request →';
            }
        } else if (['DRAFT', 'PENDING', 'REQUESTED'].includes(st)) {
            if (canSource) {
                btnPrimaryLbl.textContent = 'Approve & Reserve Stock →';
                if (btnQuickShip) btnQuickShip.hidden = false;
                if (btnDanger) {
                    btnDanger.hidden = false;
                    btnDangerLbl.textContent = st === 'DRAFT' ? 'Cancel' : 'Reject';
                }
            } else {
                btnPrimary.hidden = true;
                if (btnDanger && canDest) {
                    btnDanger.hidden = false;
                    btnDangerLbl.textContent = 'Cancel Request';
                }
                showCustodyNotice(`Awaiting Source Branch (${wb.source_location}) to approve & dispatch this request.`);
            }
        } else if (st === 'APPROVED') {
            if (canSource) {
                btnPrimaryLbl.textContent = 'Dispatch Shipment (In Transit) →';
                if (btnDanger) {
                    btnDanger.hidden = false;
                    btnDangerLbl.textContent = 'Cancel & Release';
                }
            } else {
                btnPrimary.hidden = true;
                showCustodyNotice(`Stock reserved at ${wb.source_location}. Waiting for source branch to dispatch shipment.`);
            }
        } else if (['IN_TRANSIT', 'PARTIALLY_RECEIVED'].includes(st)) {
            if (canDest) {
                btnPrimaryLbl.textContent = 'Accept & Confirm Receipt →';
            } else {
                btnPrimary.hidden = true;
                showCustodyNotice(`Shipment is In Transit. Switch active branch to ${wb.destination_location} to accept receipt.`);
            }
        } else {
            if (btnPrimary) btnPrimary.hidden = true;
        }

        function showCustodyNotice(msg) {
            if (custodyBanner && custodyText) {
                custodyText.textContent = msg;
                custodyBanner.hidden = false;
            }
        }
    }

    function renderLifecyclePipeline() {
        const el = document.getElementById('trfLifecyclePipeline');
        if (!el) return;
        const wb = state.workbench;
        const st = String(wb.status || 'NEW').toUpperCase();
        const isPush = wb.transfer_type === 'CUSTOM_PUSH';

        const steps = isPush
            ? [
                { key: 'DRAFT', title: '1. Prepare Push', desc: 'Source Picks Items' },
                { key: 'IN_TRANSIT', title: '2. In Transit', desc: 'Source Deducted' },
                { key: 'COMPLETED', title: '3. Accepted', desc: 'Dest Receipt Posted' },
            ]
            : [
                { key: 'REQUESTED', title: '1. Request', desc: 'Dest Submits Pull' },
                { key: 'APPROVED', title: '2. Approve', desc: 'Source Reserves' },
                { key: 'IN_TRANSIT', title: '3. In Transit', desc: 'Source Dispatches' },
                { key: 'COMPLETED', title: '4. Accepted', desc: 'Dest Receipt Posted' },
            ];

        const rankMap = {
            NEW: 0, DRAFT: 0, PENDING: 0, REQUESTED: 0,
            APPROVED: 1,
            IN_TRANSIT: isPush ? 1 : 2, PARTIALLY_RECEIVED: isPush ? 1 : 2,
            COMPLETED: isPush ? 2 : 3, DISCREPANCY: isPush ? 2 : 3,
        };
        const activeIdx = rankMap[st] ?? 0;

        el.innerHTML = steps.map((s, idx) => {
            const cls = idx < activeIdx || ['COMPLETED', 'DISCREPANCY'].includes(st) ? 'is-done' : (idx === activeIdx ? 'is-active' : '');
            return `<div class="rfq-chevron-step ${cls}" role="listitem">
                <span class="rfq-chevron-badge">${idx < activeIdx ? '<i class="ph-bold ph-check"></i>' : idx + 1}</span>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">${s.title}</span>
                    <span class="rfq-chevron-desc">${s.desc}</span>
                </div>
            </div>`;
        }).join('');
    }

    function hydrateBannerAndHeaderOnly() {
        const wb = state.workbench;
        const srcName = BRANCHES_BY_ID.get(Number(wb.source_branch_id))?.name || wb.source_location || 'Select Source Branch';
        const dstName = BRANCHES_BY_ID.get(Number(wb.destination_branch_id))?.name || wb.destination_location || 'Select Destination Branch';

        setText('trfBannerSourceLabel', srcName);
        setText('trfBannerDestLabel', dstName);

        const totalUnits = wb.items.reduce((acc, it) => acc + Number(it.input_dispatch_qty ?? it.requested_qty ?? it.transferred_qty ?? 0), 0);
        setText('trfBannerUnitsBadge', `${totalUnits.toFixed(2)} Units`);

        const st = String(wb.status || 'NEW').toUpperCase();
        const fill = document.getElementById('trfBannerTrackFill');
        if (fill) {
            const pctMap = { NEW: '20%', DRAFT: '25%', PENDING: '35%', REQUESTED: '35%', APPROVED: '55%', IN_TRANSIT: '75%', PARTIALLY_RECEIVED: '85%', COMPLETED: '100%', DISCREPANCY: '100%' };
            fill.style.width = pctMap[st] || '25%';
        }
        const stageText = st === 'NEW'
            ? (wb.transfer_type === 'CUSTOM_PUSH' ? 'Drafting Direct Transfer' : 'Drafting Stock Request')
            : statusLabel(st);
        setText('trfBannerStageLabel', stageText);
        const srcBr = BRANCHES_BY_ID.get(Number(wb.source_branch_id));
        const pMode = wb.pricing_mode || srcBr?.internal_pricing_mode || 'AT_COST';
        const mPct = Number(wb.markup_pct ?? srcBr?.internal_markup_pct ?? 0);
        setText('wbPricingTag', pMode === 'COST_PLUS_PCT' && mPct > 0 ? `Cost + ${mPct.toFixed(2)}%` : 'At Cost (0%)');
        renderSlipPreview();
    }

    // =====================================================================
    // LINE ITEMS GRID, QUICK SCAN, LOW-STOCK PULL & UOM REGISTRY
    // =====================================================================
    window.onTrfScanKeydown = function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const input = e.target;
        const q = input.value.trim().toUpperCase();
        if (!q) return;

        const match = PRODUCTS_BY_SKU.get(q)
            || PRODUCTS.find((p) => String(p.barcode || '').toUpperCase() === q)
            || PRODUCTS.find((p) => String(p.name || '').toUpperCase().includes(q));

        if (!match) {
            showToast(`No active catalog item found for "${input.value.trim()}".`, 'error');
            return;
        }
        addWorkbenchItem(match);
        input.value = '';
    };

    window.toggleTrfItemPicker = function (ev) {
        ev.stopPropagation();
        const dd = document.getElementById('trfItemPickerDropdown');
        if (!dd) return;
        dd.hidden = !dd.hidden;
        if (!dd.hidden) {
            renderTrfItemPickerList(document.getElementById('trfItemPickerSearch')?.value || '');
            document.getElementById('trfItemPickerSearch')?.focus();
        }
    };

    window.renderTrfItemPickerList = function (query = '') {
        const list = document.getElementById('trfItemPickerList');
        if (!list) return;
        const q = query.trim().toLowerCase();
        const matches = PRODUCTS.filter((p) =>
            !q || String(p.sku).toLowerCase().includes(q) || String(p.name).toLowerCase().includes(q)
        ).slice(0, 40);

        if (!matches.length) {
            list.innerHTML = '<div style="padding:14px;text-align:center;font-size:12px;color:#64748b;">No matching items</div>';
            return;
        }

        list.innerHTML = matches.map((p) => {
            const avail = Number(state.sourceStockMap[p.id]?.available_qty ?? 0);
            const chipCls = avail > 0 ? 'is-ok' : 'is-low';
            return `<button type="button" class="trf-popover-opt" onclick="selectItemFromPicker(${Number(p.id)})">
                <div>
                    <div style="font-weight:700;color:#0f172a;">${escapeHtml(p.name)}</div>
                    <div style="font-size:11px;color:#64748b;" class="trf-mono">${escapeHtml(p.sku)} · Base: ${escapeHtml(p.uom)}</div>
                </div>
                <span class="trf-stock-chip ${chipCls}">${avail.toFixed(2)} avail</span>
            </button>`;
        }).join('');
    };

    window.selectItemFromPicker = function (productId) {
        const prod = PRODUCTS_BY_ID.get(Number(productId));
        if (prod) addWorkbenchItem(prod);
        const dd = document.getElementById('trfItemPickerDropdown');
        if (dd) dd.hidden = true;
    };

    function addWorkbenchItem(prod, qtyOverride = 1) {
        const existing = state.workbench.items.find((it) => it.sku === prod.sku);
        if (existing) {
            existing.requested_qty = Number((Number(existing.requested_qty || 0) + qtyOverride).toFixed(2));
            existing.input_dispatch_qty = existing.requested_qty;
            renderWorkbenchLines();
            return;
        }

        state.workbench.items.push({
            inventory_item_id: Number(prod.id),
            sku: prod.sku,
            item_name: prod.name,
            category: prod.category,
            uom: prod.uom || 'PCS',
            base_uom: prod.uom || 'PCS',
            uom_multiplier: 1,
            requested_qty: qtyOverride,
            approved_qty: 0,
            transferred_qty: 0,
            received_qty: 0,
            variance_qty: 0,
            input_dispatch_qty: qtyOverride,
            input_receive_qty: 0,
            input_variance_qty: 0,
            input_variance_reason: '',
            unit_cost: Number(prod.cost_price || 0),
            storage_location: prod.storage_location || '',
        });
        renderWorkbenchLines();
    }

    window.pullLowStockSuggestions = async function () {
        const wb = state.workbench;
        if (!wb.source_branch_id || !wb.destination_branch_id) {
            showToast('Select both Source and Destination branches first.', 'error');
            return;
        }
        showLock('Scanning Destination Low-Stock Par Deficits...');
        try {
            const url = `${ROUTES.lowStockSuggestions}?source_branch_id=${wb.source_branch_id}&destination_branch_id=${wb.destination_branch_id}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Unable to fetch low-stock suggestions.');

            const suggestions = json.data?.suggestions || [];
            if (!suggestions.length) {
                showToast('No items below par level at destination with available stock at source.', 'info');
                return;
            }

            suggestions.forEach((s) => {
                const prod = PRODUCTS_BY_ID.get(Number(s.inventory_item_id)) || {
                    id: s.inventory_item_id,
                    sku: s.sku,
                    name: s.item_name,
                    category: s.category,
                    uom: s.uom,
                    cost_price: s.unit_cost,
                    storage_location: s.storage_location,
                };
                if (!wb.items.some((it) => it.sku === s.sku)) {
                    addWorkbenchItem(prod, Number(s.suggested_qty || 1));
                }
            });
            showToast(`Added ${suggestions.length} low-stock item(s) to request.`, 'success');
        } catch (err) {
            showToast(err.message || 'Failed to pull low-stock suggestions.', 'error');
        } finally {
            hideLock();
        }
    };

    window.fillAllAvailableQuantities = function () {
        state.workbench.items.forEach((it) => {
            const availBase = Number(state.sourceStockMap[it.inventory_item_id]?.available_qty ?? 0);
            const mult = Math.max(0.0001, Number(it.uom_multiplier || 1));
            const availInUom = Math.floor((availBase / mult) * 100) / 100;
            const cap = Number(it.requested_qty || availInUom);
            it.input_dispatch_qty = Math.max(0, Math.min(cap, availInUom));
        });
        renderWorkbenchLines();
    };

    window.receiveAllAsShipped = function () {
        state.workbench.items.forEach((it) => {
            const remaining = Math.max(0, Number((Number(it.transferred_qty) - Number(it.received_qty) - Number(it.variance_qty)).toFixed(2)));
            it.input_receive_qty = remaining;
            it.input_variance_qty = 0;
            it.input_variance_reason = '';
        });
        renderWorkbenchLines();
    };

    function renderWorkbenchLines() {
        const wb = state.workbench;
        const st = String(wb.status || 'NEW').toUpperCase();
        const isNewOrDraft = st === 'NEW' || st === 'DRAFT';
        const isDispatchPhase = ['DRAFT', 'PENDING', 'REQUESTED', 'APPROVED'].includes(st) && wb.id;
        const isReceivePhase = ['IN_TRANSIT', 'PARTIALLY_RECEIVED'].includes(st);
        const { canSource, canDest } = getCustodyPermissions();

        const thead = document.getElementById('wbItemsTheadRow');
        const colgroup = document.getElementById('wbItemsColgroup');
        const tbody = document.getElementById('wbItemsTableBody');
        if (!thead || !tbody) return;

        let colSpecs = [
            { width: '38%', html: '<th>Item &amp; SKU</th>' },
            { width: '19%', html: '<th>Source Available</th>' },
            { width: '19%', html: '<th>Unit (UOM)</th>' },
        ];
        if (isNewOrDraft) {
            colSpecs.push(
                { width: '16%', html: `<th style="text-align:right;">${wb.transfer_type === 'CUSTOM_PUSH' ? 'Dispatch Qty' : 'Request Qty'}</th>` },
                { width: '8%', html: '<th style="text-align:center;">Action</th>' }
            );
        } else if (isDispatchPhase) {
            colSpecs = [
                { width: '36%', html: '<th>Item &amp; SKU</th>' },
                { width: '18%', html: '<th>Source Available</th>' },
                { width: '14%', html: '<th>Unit (UOM)</th>' },
                { width: '16%', html: '<th style="text-align:right;">Requested</th>' },
                { width: '16%', html: '<th style="text-align:right;">Approve / Dispatch Qty</th>' },
            ];
        } else if (isReceivePhase) {
            colSpecs = [
                { width: '24%', html: '<th>Item &amp; SKU</th>' },
                { width: '13%', html: '<th>Source Available</th>' },
                { width: '10%', html: '<th>Unit (UOM)</th>' },
                { width: '10%', html: '<th style="text-align:right;">Shipped</th>' },
                { width: '10%', html: '<th style="text-align:right;">Already Recv</th>' },
                { width: '11%', html: '<th style="text-align:right;">Accept Now</th>' },
                { width: '10%', html: '<th style="text-align:right;">Short / Variance</th>' },
                { width: '12%', html: '<th>Variance Reason</th>' },
            ];
        } else {
            colSpecs = [
                { width: '30%', html: '<th>Item &amp; SKU</th>' },
                { width: '16%', html: '<th>Source Available</th>' },
                { width: '12%', html: '<th>Unit (UOM)</th>' },
                { width: '10%', html: '<th style="text-align:right;">Requested</th>' },
                { width: '11%', html: '<th style="text-align:right;">Dispatched</th>' },
                { width: '11%', html: '<th style="text-align:right;">Accepted</th>' },
                { width: '10%', html: '<th style="text-align:right;">Variance</th>' },
            ];
        }

        if (colgroup) {
            colgroup.innerHTML = colSpecs.map((c) => `<col style="width:${c.width};">`).join('');
        }
        thead.innerHTML = colSpecs.map((c) => c.html).join('');
        const colCount = colSpecs.length;

        if (!wb.items.length) {
            tbody.innerHTML = `<tr><td colspan="${colCount}">
                <div class="trf-empty-state">
                    <div class="trf-empty-icon"><i class="ph ph-barcode" aria-hidden="true"></i></div>
                    <div class="trf-empty-title">No line items added yet</div>
                    <div class="trf-empty-desc">Scan a barcode (<strong>F2</strong>), pick items from the catalog, or auto-fill low-stock items for this branch route.</div>
                    <div class="trf-empty-actions">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleTrfItemPicker(event)"><i class="ph ph-plus" aria-hidden="true"></i> Add Item</button>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="pullLowStockSuggestions()"><i class="ph ph-sparkle" aria-hidden="true"></i> Pull Low-Stock Items</button>
                    </div>
                </div>
            </td></tr>`;
            updateFooterTotals();
            hydrateBannerAndHeaderOnly();
            return;
        }

        tbody.innerHTML = wb.items.map((it, idx) => {
            const availBase = Number(state.sourceStockMap[it.inventory_item_id]?.available_qty ?? 0);
            const mult = Math.max(0.0001, Number(it.uom_multiplier || 1));
            const targetQty = isNewOrDraft ? Number(it.requested_qty || 0) : Number(it.input_dispatch_qty || 0);
            const neededBase = targetQty * mult;
            const chipCls = availBase >= neededBase ? 'is-ok' : (availBase > 0 ? 'is-tight' : 'is-low');
            const convNote = mult !== 1 ? `<div style="font-size:10.5px;color:#64748b;">= ${(targetQty * mult).toFixed(2)} ${escapeHtml(it.base_uom || it.uom)}</div>` : '';

            const uomCell = isNewOrDraft
                ? `<td class="trf-uom-cell">
                        <select data-uom-select data-uom-current="${escapeHtml(it.uom)}" onchange="onLineUomChanged(${idx}, this.value)"></select>
                        ${convNote}
                   </td>`
                : `<td class="trf-uom-cell"><span class="hr-badge hr-badge-neutral">${escapeHtml(it.uom)}</span>${convNote}</td>`;

            let phaseCells = '';
            if (isNewOrDraft) {
                phaseCells = `
                    <td style="text-align:right;">
                        <input type="number" step="0.01" min="0.01" class="trf-qty-input" value="${Number(it.requested_qty || 0)}" onchange="onLineQtyChanged(${idx}, 'requested_qty', this.value)">
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="removeWorkbenchLine(${idx})" title="Remove line"><i class="ph ph-trash"></i></button>
                    </td>
                `;
            } else if (isDispatchPhase) {
                phaseCells = `
                    <td class="trf-num" style="text-align:right;font-weight:700;">${Number(it.requested_qty || 0).toFixed(2)}</td>
                    <td style="text-align:right;">
                        <input type="number" step="0.01" min="0" max="${Number(it.requested_qty || 999999)}" ${!canSource ? 'disabled' : ''} class="trf-qty-input" value="${Number(it.input_dispatch_qty || 0)}" onchange="onLineQtyChanged(${idx}, 'input_dispatch_qty', this.value)">
                    </td>
                `;
            } else if (isReceivePhase) {
                const remaining = Math.max(0, Number((Number(it.transferred_qty) - Number(it.received_qty) - Number(it.variance_qty)).toFixed(2)));
                const varQty = Math.max(0, Number((remaining - Number(it.input_receive_qty || 0)).toFixed(2)));
                it.input_variance_qty = varQty;
                const reasonOpts = VARIANCE_REASONS.map((r) => `<option value="${r.value}" ${it.input_variance_reason === r.value ? 'selected' : ''}>${escapeHtml(r.label)}</option>`).join('');
                phaseCells = `
                    <td class="trf-num" style="text-align:right;font-weight:700;">${Number(it.transferred_qty || 0).toFixed(2)}</td>
                    <td class="trf-num" style="text-align:right;color:#047857;">${Number(it.received_qty || 0).toFixed(2)}</td>
                    <td style="text-align:right;">
                        <input type="number" step="0.01" min="0" max="${remaining}" ${!canDest ? 'disabled' : ''} class="trf-qty-input" value="${Number(it.input_receive_qty || 0)}" onchange="onLineReceiveChanged(${idx}, this.value)">
                    </td>
                    <td class="trf-num" style="text-align:right;font-weight:700;color:${varQty > 0 ? '#b91c1c' : '#64748b'};">${varQty.toFixed(2)}</td>
                    <td>
                        ${varQty > 0
                            ? `<select class="trf-control" style="height:32px;font-size:11.5px;" ${!canDest ? 'disabled' : ''} onchange="onLineVarianceReasonChanged(${idx}, this.value)">${reasonOpts}</select>`
                            : '<span style="font-size:11px;color:#94a3b8;">None</span>'}
                    </td>
                `;
            } else {
                phaseCells = `
                    <td class="trf-num" style="text-align:right;">${Number(it.requested_qty || 0).toFixed(2)}</td>
                    <td class="trf-num" style="text-align:right;color:#6d28d9;font-weight:700;">${Number(it.transferred_qty || 0).toFixed(2)}</td>
                    <td class="trf-num" style="text-align:right;color:#047857;font-weight:700;">${Number(it.received_qty || 0).toFixed(2)}</td>
                    <td class="trf-num" style="text-align:right;color:${Number(it.variance_qty || 0) > 0 ? '#b91c1c' : '#64748b'};font-weight:700;">
                        ${Number(it.variance_qty || 0).toFixed(2)} ${it.variance_reason ? `<div style="font-size:10px;">${escapeHtml(it.variance_reason)}</div>` : ''}
                    </td>
                `;
            }

            return `<tr>
                <td>
                    <div style="font-weight:700;color:#0f172a;">${escapeHtml(it.item_name)}</div>
                    <div class="trf-mono" style="font-size:11px;color:#64748b;">${escapeHtml(it.sku)}</div>
                </td>
                <td><span class="trf-stock-chip ${chipCls}">${availBase.toFixed(2)} ${escapeHtml(it.base_uom || it.uom)}</span></td>
                ${uomCell}
                ${phaseCells}
            </tr>`;
        }).join('');

        if (window.UomRegistry && typeof window.UomRegistry.autoHydrate === 'function') {
            window.UomRegistry.autoHydrate(tbody);
        }

        updateFooterTotals();
        hydrateBannerAndHeaderOnly();
    }

    window.onLineUomChanged = function (idx, uomCode) {
        const it = state.workbench.items[idx];
        if (!it) return;
        it.uom = uomCode;
        const base = it.base_uom || uomCode;
        let ratio = 1;
        if (window.UomRegistry && typeof window.UomRegistry.convert === 'function') {
            const conv = window.UomRegistry.convert(1, uomCode, base);
            if (conv && conv > 0) ratio = conv;
        }
        it.uom_multiplier = ratio;
        renderWorkbenchLines();
    };

    window.onLineQtyChanged = function (idx, field, val) {
        const it = state.workbench.items[idx];
        if (!it) return;
        const num = Math.max(0, Number(val || 0));
        it[field] = num;
        if (field === 'requested_qty') it.input_dispatch_qty = num;
        renderWorkbenchLines();
    };

    window.onLineReceiveChanged = function (idx, val) {
        const it = state.workbench.items[idx];
        if (!it) return;
        const remaining = Math.max(0, Number((Number(it.transferred_qty) - Number(it.received_qty) - Number(it.variance_qty)).toFixed(2)));
        const recv = Math.max(0, Math.min(remaining, Number(val || 0)));
        it.input_receive_qty = recv;
        it.input_variance_qty = Math.max(0, Number((remaining - recv).toFixed(2)));
        renderWorkbenchLines();
    };

    window.onLineVarianceReasonChanged = function (idx, reason) {
        const it = state.workbench.items[idx];
        if (it) it.input_variance_reason = reason;
    };

    window.removeWorkbenchLine = function (idx) {
        state.workbench.items.splice(idx, 1);
        renderWorkbenchLines();
    };

    function updateFooterTotals() {
        const items = state.workbench.items || [];
        setText('wbLinesCountBadge', `${items.length} Line Items`);
        setText('wbSumLines', items.length);

        const reqSum = items.reduce((s, it) => s + Number(it.requested_qty || 0), 0);
        const dispSum = items.reduce((s, it) => s + Number(it.transferred_qty || it.input_dispatch_qty || 0), 0);
        const recvSum = items.reduce((s, it) => s + Number(it.received_qty || it.input_receive_qty || 0), 0);
        const varSum = items.reduce((s, it) => s + Number(it.variance_qty || it.input_variance_qty || 0), 0);
        const valSum = items.reduce((s, it) => {
            const q = Number(it.transferred_qty || it.requested_qty || 0) * Number(it.uom_multiplier || 1);
            return s + (q * Number(it.unit_cost || 0));
        }, 0);

        setText('wbSumRequested', reqSum.toFixed(2));
        setText('wbSumDispatched', dispSum.toFixed(2));
        setText('wbSumReceived', recvSum.toFixed(2));
        setText('wbSumVariance', varSum.toFixed(2));
        setText('wbSumValuation', `₱${valSum.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        const varMetric = document.getElementById('wbVarianceMetric');
        if (varMetric) varMetric.hidden = varSum <= 0;
    }

    // =====================================================================
    // LIFECYCLE API DISPATCHERS
    // =====================================================================
    window.submitWorkbenchDraft = () => submitNewBranchRequest(true);

    window.handlePrimaryWorkbenchAction = async function () {
        const wb = state.workbench;
        const st = String(wb.status || 'NEW').toUpperCase();

        if (st === 'NEW') {
            if (wb.transfer_type === 'CUSTOM_PUSH') await submitNewDirectPush();
            else await submitNewBranchRequest(false);
        } else if (['DRAFT', 'PENDING', 'REQUESTED'].includes(st)) {
            await submitApproveRequest(false);
        } else if (st === 'APPROVED') {
            await triggerDispatchWithBackorderCheck(false);
        } else if (['IN_TRANSIT', 'PARTIALLY_RECEIVED'].includes(st)) {
            await submitDestinationReceive();
        }
    };

    window.handleApproveAndDispatchShortcut = async function () {
        await triggerDispatchWithBackorderCheck(true);
    };

    async function submitNewBranchRequest(saveAsDraft) {
        const wb = state.workbench;
        if (!wb.source_branch_id || !wb.destination_branch_id) {
            return showToast('Select both Source and Destination branches.', 'error');
        }
        if (!wb.items.length) {
            return showToast('Add at least one item to request.', 'error');
        }

        await postJson(ROUTES.createRequest, {
            source_branch_id: wb.source_branch_id,
            destination_branch_id: wb.destination_branch_id,
            priority: wb.priority,
            reason_code: wb.reason_code,
            required_at: wb.required_at || null,
            notes: wb.notes || null,
            save_as_draft: saveAsDraft,
            idempotency_key: wb.idempotency_key,
            items: wb.items.map((it) => ({
                sku: it.sku,
                item_name: it.item_name,
                category: it.category,
                uom: it.uom,
                uom_multiplier: it.uom_multiplier || 1,
                requested_qty: Number(it.requested_qty),
                unit_cost: Number(it.unit_cost || 0),
                storage_location: it.storage_location || null,
            })),
        }, saveAsDraft ? 'Saving Request Draft...' : 'Submitting Branch Stock Request...', (data) => {
            openTransferWorkbench(data.transfer.id);
            fetchTransferQueue(1);
        });
    }

    async function submitNewDirectPush() {
        const wb = state.workbench;
        if (!wb.source_branch_id || !wb.destination_branch_id) {
            return showToast('Select both Source and Destination branches.', 'error');
        }
        if (!wb.items.length) {
            return showToast('Add at least one item to dispatch.', 'error');
        }

        await postJson(ROUTES.createCustomTransfer, {
            source_branch_id: wb.source_branch_id,
            destination_branch_id: wb.destination_branch_id,
            priority: wb.priority,
            reason_code: wb.reason_code,
            required_at: wb.required_at || null,
            carrier_name: wb.carrier_name || null,
            driver_plate: wb.driver_plate || null,
            waybill_number: wb.waybill_number || null,
            notes: wb.notes || null,
            idempotency_key: wb.idempotency_key,
            items: wb.items.map((it) => ({
                sku: it.sku,
                item_name: it.item_name,
                category: it.category,
                uom: it.uom,
                uom_multiplier: it.uom_multiplier || 1,
                transferred_qty: Number(it.requested_qty),
                unit_cost: Number(it.unit_cost || 0),
                storage_location: it.storage_location || null,
            })),
        }, 'Deducting Source Stock & Dispatching Shipment...', (data) => {
            openTransferWorkbench(data.transfer.id);
            fetchTransferQueue(1);
        });
    }

    async function submitApproveRequest(dispatchImmediately, createBackorder = false) {
        const wb = state.workbench;
        const itemsPayload = wb.items.map((it) => ({
            sku: it.sku,
            approved_qty: Number(it.input_dispatch_qty ?? it.requested_qty),
        }));

        await postJson(ROUTES.approveTransfer, {
            transfer_id: wb.id,
            source_branch_id: wb.source_branch_id,
            dispatch_immediately: dispatchImmediately,
            create_backorder: createBackorder,
            carrier_name: wb.carrier_name || null,
            driver_plate: wb.driver_plate || null,
            waybill_number: wb.waybill_number || null,
            items: itemsPayload,
        }, dispatchImmediately ? 'Approving & Dispatching Shipment...' : 'Reserving Source Stock...', (data) => {
            openTransferWorkbench(data.transfer.id);
            fetchTransferQueue(state.page);
        });
    }

    async function triggerDispatchWithBackorderCheck(fromApproveShortcut) {
        const wb = state.workbench;
        const shortfalls = wb.items.filter((it) => Number(it.input_dispatch_qty || 0) < Number(it.requested_qty || 0) - 0.0001);
        if (shortfalls.length > 0 && wb.transfer_type !== 'CUSTOM_PUSH') {
            state.pendingPartialPayload = { fromApproveShortcut };
            setText(
                'trfBoModalSummary',
                `${shortfalls.length} item(s) have a dispatch quantity below the requested quantity. How should the remaining balance be handled?`
            );
            const modal = document.getElementById('trfBackorderModal');
            if (modal) modal.hidden = false;
            return;
        }
        if (fromApproveShortcut) await submitApproveRequest(true, false);
        else await submitDispatch(false);
    }

    window.closeBackorderModal = function () {
        state.pendingPartialPayload = null;
        const modal = document.getElementById('trfBackorderModal');
        if (modal) modal.hidden = true;
    };

    window.confirmPartialDispatchChoice = async function (createBackorder) {
        const ctx = state.pendingPartialPayload || {};
        closeBackorderModal();
        if (ctx.fromApproveShortcut) await submitApproveRequest(true, createBackorder);
        else await submitDispatch(createBackorder);
    };

    async function submitDispatch(createBackorder) {
        const wb = state.workbench;
        await postJson(ROUTES.dispatchTransfer, {
            transfer_id: wb.id,
            source_branch_id: wb.source_branch_id,
            carrier_name: wb.carrier_name || null,
            driver_plate: wb.driver_plate || null,
            waybill_number: wb.waybill_number || null,
            create_backorder: createBackorder,
            items: wb.items.map((it) => ({
                sku: it.sku,
                transferred_qty: Number(it.input_dispatch_qty || 0),
            })),
        }, 'Dispatching Shipment & Writing TRANSFER_OUT Ledger...', (data) => {
            openTransferWorkbench(data.transfer.id);
            fetchTransferQueue(state.page);
        });
    }

    async function submitDestinationReceive() {
        const wb = state.workbench;
        const itemsPayload = [];
        for (const it of wb.items) {
            const remaining = Math.max(0, Number((Number(it.transferred_qty) - Number(it.received_qty) - Number(it.variance_qty)).toFixed(2)));
            if (remaining <= 0) continue;
            const recv = Number(it.input_receive_qty || 0);
            const variance = Math.max(0, Number((remaining - recv).toFixed(2)));
            if (variance > 0 && !it.input_variance_reason) {
                return showToast(`Select a variance reason for shortage on ${it.sku}.`, 'error');
            }
            itemsPayload.push({
                sku: it.sku,
                received_qty: recv,
                variance_qty: variance,
                variance_reason: variance > 0 ? it.input_variance_reason : null,
            });
        }

        await postJson(ROUTES.receiveTransfer, {
            transfer_id: wb.id,
            notes: wb.notes || null,
            items: itemsPayload,
        }, 'Accepting Transfer & Crediting Destination Branch...', (data) => {
            openTransferWorkbench(data.transfer.id);
            fetchTransferQueue(state.page);
        });
    }

    // Reject / Cancel Modal
    window.openReasonModalForCurrentDoc = function () {
        const st = String(state.workbench.status || '').toUpperCase();
        const { canSource } = getCustodyPermissions();
        const isReject = ['REQUESTED', 'PENDING'].includes(st) && canSource;
        state.pendingReasonMode = isReject ? 'reject' : 'cancel';

        setText('trfReasonModalHeading', isReject ? 'Reject Transfer Request' : 'Cancel Transfer');
        const ta = document.getElementById('trfReasonTextarea');
        if (ta) ta.value = '';
        const modal = document.getElementById('trfReasonModal');
        if (modal) modal.hidden = false;
        ta?.focus();
    };

    window.closeReasonModal = function () {
        state.pendingReasonMode = null;
        const modal = document.getElementById('trfReasonModal');
        if (modal) modal.hidden = true;
    };

    window.submitReasonModal = async function () {
        const reason = document.getElementById('trfReasonTextarea')?.value.trim();
        if (!reason) return showToast('Please enter a reason.', 'error');
        const mode = state.pendingReasonMode;
        closeReasonModal();

        const url = mode === 'reject' ? ROUTES.rejectTransfer : ROUTES.cancelTransfer;
        await postJson(url, { transfer_id: state.workbench.id, reason }, 'Updating Transfer Status...', (data) => {
            openTransferWorkbench(data.transfer.id);
            fetchTransferQueue(state.page);
        });
    };

    async function postJson(url, payload, lockMsg, onSuccess) {
        showLock(lockMsg);
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                const firstErr = json.errors ? Object.values(json.errors).flat()[0] : null;
                throw new Error(firstErr || json.message || 'Operation failed.');
            }
            showToast(json.message || 'Success.', 'success');
            if (onSuccess) onSuccess(json.data || {});
        } catch (err) {
            showToast(err.message || 'Request failed.', 'error');
        } finally {
            hideLock();
        }
    }

    // =====================================================================
    // SLIP PREVIEW & UTILITIES
    // =====================================================================
    function renderSlipPreview() {
        const el = document.getElementById('trfSlipPreviewContent');
        if (!el) return;
        const wb = state.workbench;
        const srcName = BRANCHES_BY_ID.get(Number(wb.source_branch_id))?.name || wb.source_location || '—';
        const dstName = BRANCHES_BY_ID.get(Number(wb.destination_branch_id))?.name || wb.destination_location || '—';

        const rowsHtml = (wb.items || []).map((it) => `
            <tr>
                <td><strong>${escapeHtml(it.item_name)}</strong><div style="font-size:10px;color:#64748b;">${escapeHtml(it.sku)}</div></td>
                <td class="trf-num is-num">${Number(it.transferred_qty || it.requested_qty || 0).toFixed(2)} ${escapeHtml(it.uom)}</td>
                <td class="trf-num is-num" style="color:#047857;font-weight:700;">${Number(it.received_qty || 0).toFixed(2)}</td>
            </tr>
        `).join('') || '<tr><td colspan="3" class="trf-slip-empty">No items added yet</td></tr>';

        el.innerHTML = `
            <div class="trf-slip-doc-box">
                <div class="trf-slip-accent-bar"></div>
                <div class="trf-slip-inner">
                    <div class="trf-slip-header">
                        <div>
                            <div class="trf-slip-kicker">Inter-Branch Transfer Slip</div>
                            <div class="trf-mono trf-slip-ref">${escapeHtml(wb.transfer_number)}</div>
                            ${wb.receipt_number ? `<div class="trf-mono trf-slip-rec">Receipt: ${escapeHtml(wb.receipt_number)}</div>` : ''}
                        </div>
                        ${renderStatusPill(String(wb.status || 'NEW'))}
                    </div>
                    <div class="trf-slip-route">
                        <div><span class="trf-slip-route-label">From Source</span><strong>${escapeHtml(srcName)}</strong></div>
                        <div><span class="trf-slip-route-label">To Destination</span><strong>${escapeHtml(dstName)}</strong></div>
                    </div>
                    <table class="trf-slip-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="is-num">Req/Ship</th>
                                <th class="is-num">Accepted</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                    <div class="trf-slip-custody">
                        <div><span class="trf-slip-route-label">Requested</span><strong>${escapeHtml(wb.requested_by || '—')}</strong></div>
                        <div><span class="trf-slip-route-label">Dispatched</span><strong>${escapeHtml(wb.dispatched_by || 'Pending')}</strong></div>
                        <div><span class="trf-slip-route-label">Accepted</span><strong>${escapeHtml(wb.received_by || 'Pending')}</strong></div>
                    </div>
                    ${wb.total_internal_billed ? `<div class="trf-slip-billing">
                        <span>Internal Billed (${wb.pricing_mode === 'COST_PLUS_PCT' ? '+' + Number(wb.markup_pct || 0).toFixed(1) + '%' : 'At Cost'}): <strong>₱${Number(wb.total_internal_billed || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></span>
                        <span class="hr-badge hr-badge-neutral">${escapeHtml(wb.settlement_status || 'UNBILLED')}</span>
                    </div>` : ''}
                </div>
            </div>
        `;
    }

    window.openSettlementModal = async function () {
        const modal = document.getElementById('trfSettlementModal');
        const body = document.getElementById('trfSettlementModalBody');
        if (!modal || !body) return;
        modal.hidden = false;
        body.innerHTML = '<div style="text-align:center;padding:24px;color:#64748b;">Loading inter-branch balances...</div>';

        try {
            const res = await fetch(ROUTES.settlementMatrix, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Unable to load settlement matrix.');
            const data = json.data || {};
            const pairs = data.pairs || [];
            const unsettled = data.unsettled_transfers || [];
            const totals = data.totals || {};

            const pairsHtml = pairs.length
                ? pairs.map((p) => {
                    const pairIds = unsettled
                        .filter((u) => Number(u.source_branch_id) === Number(p.source_branch_id) && Number(u.destination_branch_id) === Number(p.destination_branch_id))
                        .map((u) => Number(u.id));
                    return `<tr>
                        <td><strong>${escapeHtml(p.destination_location)}</strong> <span style="color:#64748b;">owes</span> <strong>${escapeHtml(p.source_location)}</strong></td>
                        <td class="trf-num" style="text-align:right;">${p.transfer_count}</td>
                        <td class="trf-num" style="text-align:right;font-weight:800;color:#b45309;">₱${Number(p.unsettled_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td class="trf-num" style="text-align:right;color:#047857;">₱${Number(p.settled_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td style="text-align:right;">
                            ${pairIds.length > 0
                                ? `<button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick='settlePairTransfers(${JSON.stringify(pairIds)})'>Settle &amp; Post GL</button>`
                                : '<span class="hr-badge hr-badge-neutral">Settled</span>'}
                        </td>
                    </tr>`;
                }).join('')
                : '<tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">No completed inter-branch transfers to settle yet.</td></tr>';

            body.innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                    <div style="padding:10px 12px;border-radius:10px;background:#fef3c7;border:1px solid #fde68a;">
                        <div style="font-size:10.5px;font-weight:700;color:#92400e;text-transform:uppercase;">Open Unsettled Inter-Branch</div>
                        <div class="trf-num" style="font-size:16px;font-weight:800;color:#78350f;">₱${Number(totals.unsettled_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    </div>
                    <div style="padding:10px 12px;border-radius:10px;background:#d1fae5;border:1px solid #a7f3d0;">
                        <div style="font-size:10.5px;font-weight:700;color:#065f46;text-transform:uppercase;">Cleared / Settled Total</div>
                        <div class="trf-num" style="font-size:16px;font-weight:800;color:#064e3b;">₱${Number(totals.settled_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    </div>
                </div>
                <table class="rfq-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Debtor → Creditor Branch</th>
                            <th style="text-align:right;">Transfers</th>
                            <th style="text-align:right;">Unsettled</th>
                            <th style="text-align:right;">Settled</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>${pairsHtml}</tbody>
                </table>
            `;
        } catch (err) {
            body.innerHTML = `<div style="padding:16px;color:#991b1b;">${escapeHtml(err.message)}</div>`;
        }
    };

    window.closeSettlementModal = function () {
        const modal = document.getElementById('trfSettlementModal');
        if (modal) modal.hidden = true;
    };

    window.settlePairTransfers = async function (transferIds) {
        await postJson(ROUTES.settleTransfers, { transfer_ids: transferIds }, 'Posting Inter-Branch Settlement GL Journal...', () => {
            openSettlementModal();
            fetchTransferQueue(state.page);
        });
    };

    window.toggleTrfSlipDrawer = function (force) {
        const col = document.getElementById('trfSlipPanelCol');
        if (!col) return;
        if (typeof force === 'boolean') col.classList.toggle('is-open', force);
        else col.classList.toggle('is-open');
    };

    window.printTransferSlip = function () {
        const slip = document.getElementById('trfSlipPreviewContent')?.innerHTML || '';
        const target = document.getElementById('rfqDedicatedPrintSheet');
        if (target) {
            target.innerHTML = `<div style="padding:24px;font-family:Poppins,sans-serif;">${slip}</div>`;
            window.print();
        }
    };

    function statusClass(st) {
        switch (st) {
            case 'DRAFT': case 'NEW': return 'is-draft';
            case 'PENDING': case 'REQUESTED': return 'is-requested';
            case 'APPROVED': return 'is-approved';
            case 'IN_TRANSIT': return 'is-transit';
            case 'PARTIALLY_RECEIVED': return 'is-partial';
            case 'COMPLETED': return 'is-completed';
            case 'DISCREPANCY': return 'is-discrepancy';
            default: return 'is-cancelled';
        }
    }

    function statusLabel(st) {
        const map = {
            NEW: 'New', DRAFT: 'Draft', PENDING: 'Requested', REQUESTED: 'Requested',
            APPROVED: 'Approved & Reserved', IN_TRANSIT: 'In Transit',
            PARTIALLY_RECEIVED: 'Partially Received', COMPLETED: 'Completed',
            DISCREPANCY: 'Received w/ Variance', REJECTED: 'Rejected', CANCELLED: 'Cancelled',
        };
        return map[st] || st;
    }

    function renderStatusPill(st) {
        return `<span class="trf-status-pill ${statusClass(st)}">${statusLabel(st)}</span>`;
    }

    function showLock(msg) {
        setText('trfLockMessage', msg || 'Processing...');
        const el = document.getElementById('trfGlobalLockOverlay');
        if (el) el.style.display = 'flex';
    }

    function hideLock() {
        const el = document.getElementById('trfGlobalLockOverlay');
        if (el) el.style.display = 'none';
    }

    function showToast(msg, type = 'info') {
        const root = document.getElementById('trfToastContainer');
        if (!root) return;
        const div = document.createElement('div');
        div.className = `rfq-toast ${type === 'error' ? 'is-error' : (type === 'success' ? 'is-success' : '')}`;
        div.style.cssText = 'padding:10px 14px;border-radius:10px;background:#0f172a;color:#fff;font-size:12.5px;font-weight:600;box-shadow:0 8px 20px rgba(0,0,0,0.18);margin-bottom:8px;';
        if (type === 'error') div.style.background = '#991b1b';
        if (type === 'success') div.style.background = '#065f46';
        div.textContent = msg;
        root.appendChild(div);
        setTimeout(() => div.remove(), 4200);
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = String(val ?? '');
    }

    function setInputVal(id, val, disabled) {
        const el = document.getElementById(id);
        if (!el) return;
        el.value = val ?? '';
        el.disabled = Boolean(disabled);
    }

    function formatDate(str) {
        if (!str) return '—';
        const d = new Date(str);
        return Number.isNaN(d.getTime()) ? String(str).slice(0, 10) : d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();
