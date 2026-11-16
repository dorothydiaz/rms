/**
 * REORDERING RULES & REPLENISHMENT CONTROL TOWER JAVASCRIPT
 * RMS Enterprise Modular Architecture
 */

(function () {
    'use strict';

    const cfg = window.ReorderRulesConfig || {};

    const AppStore = {
        rules: Array.isArray(cfg.initialRules) ? [...cfg.initialRules] : [],
        simulation: cfg.initialSimulation || { summary: {}, items: [] },
        branches: Array.isArray(cfg.branches) ? cfg.branches : [],
        items: Array.isArray(cfg.items) ? cfg.items : [],
        vendors: Array.isArray(cfg.vendors) ? cfg.vendors : [],
        warehouses: Array.isArray(cfg.warehouses) ? cfg.warehouses : [],
        activeTab: 'rules',
        selectedProductIds: new Set(),
        selectedCategories: new Set(),
        filters: {
            branch: 'all',
            category: 'all',
            search: '',
            strategy: 'all',
            route: 'all',
            status: 'all',
        },
        history: [
            {
                timestamp: 'Today • 08:30 AM',
                ref: 'PO-AUTO-9481A',
                type: 'Purchase Order (Draft)',
                route: 'Aling Nena Produce → Makati Flagship',
                itemCount: 2,
                totalVal: '₱3,450.00',
                status: 'DRAFT_PENDING_APPROVAL',
                createdBy: 'Automated Replenishment Engine',
                url: cfg.routes ? cfg.routes.purchaseOrdersPage : '#',
            },
            {
                timestamp: 'Yesterday • 06:00 PM',
                ref: 'TRF-AUTO-8219B',
                type: 'Internal Transfer (Draft)',
                route: 'Central Commissary → BGC High Street',
                itemCount: 3,
                totalVal: '24 Units',
                status: 'DRAFT_PICK_QUEUE',
                createdBy: 'Automated Replenishment Engine',
                url: cfg.routes ? cfg.routes.internalTransfersPage : '#',
            }
        ]
    };

    // Format PHP currency
    function formatPHP(amount) {
        const num = parseFloat(amount) || 0;
        return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Format general number
    function formatNumber(amount, decimals = 2) {
        const num = parseFloat(amount) || 0;
        return num.toLocaleString('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    // Toast notifications
    function showToast(message, type = 'success') {
        const container = document.getElementById('rrToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'rr-toast';
        const icon = type === 'error' ? 'ph-x-circle' : (type === 'info' ? 'ph-info' : 'ph-check-circle');
        const color = type === 'error' ? '#f87171' : (type === 'info' ? '#38bdf8' : '#4ade80');
        
        toast.innerHTML = `<i class="ph ${icon}" style="font-size: 1.15rem; color: ${color};"></i><span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.25s ease';
            setTimeout(() => toast.remove(), 260);
        }, 3200);
    }

    // 1. Tab Switching
    window.switchRrTab = function (tabName) {
        AppStore.activeTab = tabName;
        document.querySelectorAll('.rr-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
        });
        document.querySelectorAll('.rr-tab-pane').forEach(pane => {
            pane.classList.toggle('active', pane.id === 'pane-' + tabName);
        });

        if (tabName === 'simulator') {
            renderSimulatorTable();
        } else if (tabName === 'history') {
            renderHistoryTable();
        } else {
            renderRulesTable();
        }
    };

    // 2. Render KPIs
    function renderKpis() {
        const activeCount = AppStore.rules.filter(r => r.is_active).length;
        const simSummary = (AppStore.simulation && AppStore.simulation.summary) ? AppStore.simulation.summary : {};
        
        const triggeredCount = simSummary.triggered_count || 0;
        const poValue = simSummary.total_suggested_po_value || 0;
        const transferUnits = simSummary.total_suggested_transfer_units || 0;

        const elActive = document.getElementById('kpiActiveRules');
        const elTriggered = document.getElementById('kpiTriggeredCount');
        const elPo = document.getElementById('kpiPoValue');
        const elTransfer = document.getElementById('kpiTransferUnits');

        if (elActive) elActive.textContent = activeCount;
        if (elTriggered) elTriggered.textContent = triggeredCount;
        if (elPo) elPo.textContent = formatPHP(poValue);
        if (elTransfer) elTransfer.textContent = `${formatNumber(transferUnits, 0)} Units`;

        // Update badges on tabs
        const badgeRules = document.getElementById('tabBadgeRules');
        const badgeSim = document.getElementById('tabBadgeSimulator');
        const badgeHistory = document.getElementById('tabBadgeHistory');

        if (badgeRules) badgeRules.textContent = AppStore.rules.length;
        if (badgeSim) badgeSim.textContent = triggeredCount;
        if (badgeHistory) badgeHistory.textContent = AppStore.history.length;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function populateMainCategoryFilter() {
        const sel = document.getElementById('categoryFilter');
        if (!sel) return;
        const prev = sel.value || 'all';
        const catSet = new Set();
        AppStore.items.forEach(it => { if (it.category) catSet.add(it.category); });
        AppStore.rules.forEach(r => { if (r.category) catSet.add(r.category); });
        const sorted = Array.from(catSet).sort((a, b) => a.localeCompare(b));
        sel.innerHTML = `<option value="all">All Categories</option>` + sorted.map(c =>
            `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`
        ).join('');
        if (prev !== 'all' && catSet.has(prev)) {
            sel.value = prev;
        }
    }

    // 3. Render Rules Table
    function renderRulesTable() {
        const tbody = document.getElementById('rulesTableBody');
        if (!tbody) return;

        let filtered = AppStore.rules.filter(r => {
            if (AppStore.filters.branch !== 'all' && String(r.branch_id) !== String(AppStore.filters.branch)) return false;
            if (AppStore.filters.category !== 'all' && (r.category || '') !== AppStore.filters.category) return false;
            if (AppStore.filters.strategy !== 'all' && r.strategy !== AppStore.filters.strategy) return false;
            if (AppStore.filters.route !== 'all' && r.action_route !== AppStore.filters.route) return false;
            if (AppStore.filters.status === 'active' && !r.is_active) return false;
            if (AppStore.filters.status === 'inactive' && r.is_active) return false;

            if (AppStore.filters.search) {
                const term = AppStore.filters.search.toLowerCase();
                const code = (r.rule_code || '').toLowerCase();
                const sku = (r.product_sku || '').toLowerCase();
                const name = (r.product_name || '').toLowerCase();
                const bName = (r.branch_name || '').toLowerCase();
                const cat = (r.category || '').toLowerCase();
                return code.includes(term) || sku.includes(term) || name.includes(term) || bName.includes(term) || cat.includes(term);
            }
            return true;
        });

        const countEl = document.getElementById('filteredRuleCount');
        if (countEl) {
            countEl.textContent = `${filtered.length} Rule${filtered.length === 1 ? '' : 's'}`;
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" style="text-align: center; padding: 36px 16px; color: var(--rr-text-muted);">
                        <i class="ph ph-folder-dashed" style="font-size: 2.2rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                        No reordering rules match the selected filters.
                        <div style="margin-top: 10px;">
                            <button type="button" class="rr-btn rr-btn-secondary" onclick="resetFilters()">Reset Filters</button>
                            <button type="button" class="rr-btn rr-btn-primary" onclick="openCreateRuleModal()">Create Rule</button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map(r => {
            const stratClass = r.strategy === 'commissary_push' ? 'strat-comm' : (r.strategy === 'jit_back_to_back' ? 'strat-jit' : 'strat-minmax');
            const routeClass = r.action_route === 'internal_transfer' ? 'route-trf' : 'route-po';
            const partnerText = r.action_route === 'internal_transfer'
                ? (r.source_warehouse_name || 'Central Commissary')
                : (r.preferred_vendor_name || 'Direct Supplier');

            return `
                <tr>
                    <td style="text-align: center;">
                        <label class="rr-toggle-label" title="Toggle active status">
                            <input type="checkbox" ${r.is_active ? 'checked' : ''} onchange="toggleRuleActive(${r.id})">
                            <span class="rr-toggle-slider"></span>
                        </label>
                    </td>
                    <td>
                        <strong style="color: var(--rr-brand); font-family: monospace; font-size: 0.8rem;">${escapeHtml(r.rule_code || ('RUL-' + r.id))}</strong>
                    </td>
                    <td>
                        <span class="rr-branch-pill">
                            <span class="rr-branch-dot"></span>
                            <span>${escapeHtml(r.branch_name)}</span>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${escapeHtml(r.product_name)}</div>
                        <div style="font-size: 0.7rem; color: #64748b; font-family: monospace;">SKU: ${escapeHtml(r.product_sku)} • ${escapeHtml(r.uom)}</div>
                    </td>
                    <td>
                        <span class="rr-sku-check-cat">${escapeHtml(r.category || 'General')}</span>
                    </td>
                    <td>
                        <span class="rr-badge ${stratClass}">${escapeHtml(r.strategy_label || r.strategy)}</span>
                    </td>
                    <td class="num"><strong>${formatNumber(r.min_stock, 1)}</strong></td>
                    <td class="num"><strong>${formatNumber(r.max_stock, 1)}</strong></td>
                    <td class="num" style="color: #64748b;">${formatNumber(r.moq, 1)}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span class="rr-badge ${routeClass}">${r.action_route === 'internal_transfer' ? 'TRF' : 'PO'}</span>
                            <span style="font-size: 0.74rem; color: #334155; max-width: 160px; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(partnerText)}</span>
                        </div>
                    </td>
                    <td style="font-size: 0.74rem; color: #64748b;">${r.lead_time_days || 2}d lead</td>
                    <td>
                        ${r.auto_generate_draft
                            ? `<span style="color: #10b981; font-size: 0.72rem; font-weight: 600;"><i class="ph ph-check-circle"></i> Auto</span>`
                            : `<span style="color: #94a3b8; font-size: 0.72rem;">Manual</span>`}
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 4px;">
                            <button type="button" class="rr-action-btn" title="Edit Rule" onclick="openEditRuleModal(${r.id})">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button type="button" class="rr-action-btn delete" title="Delete Rule" onclick="deleteRule(${r.id})">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // 4. Render Simulator Table
    function renderSimulatorTable() {
        const tbody = document.getElementById('simulatorTableBody');
        if (!tbody) return;

        const items = (AppStore.simulation && AppStore.simulation.items) ? AppStore.simulation.items : [];
        let filtered = items.filter(it => {
            if (AppStore.filters.branch !== 'all' && String(it.branch_id) !== String(AppStore.filters.branch)) return false;
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" style="text-align: center; padding: 36px 16px; color: var(--rr-text-muted);">
                        <i class="ph ph-check-circle" style="font-size: 2.2rem; color: #10b981; display: block; margin-bottom: 8px;"></i>
                        All active stock levels are fully within healthy par buffer boundaries.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map(it => {
            let statusBadge = `<span class="rr-badge status-healthy">Healthy Par</span>`;
            if (it.status === 'CRITICAL_UNDER_PAR') {
                statusBadge = `<span class="rr-badge status-critical">Critical Under Par</span>`;
            } else if (it.status === 'WARNING_LOW') {
                statusBadge = `<span class="rr-badge status-warning">Under Min Par</span>`;
            } else if (it.status === 'OVERSTOCKED') {
                statusBadge = `<span class="rr-badge status-over">Overstocked</span>`;
            }

            const routeClass = it.action_route === 'internal_transfer' ? 'route-trf' : 'route-po';
            const partnerText = it.action_route === 'internal_transfer' ? it.source_warehouse_name : it.preferred_vendor_name;

            return `
                <tr style="${it.is_triggered ? 'background: #fffdfd;' : ''}">
                    <td>${statusBadge}</td>
                    <td>
                        <span class="rr-branch-pill">${it.branch_name}</span>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${it.product_name}</div>
                        <div style="font-size: 0.7rem; color: #64748b; font-family: monospace;">SKU: ${it.product_sku} • ${it.uom}</div>
                    </td>
                    <td class="num">${formatNumber(it.on_hand, 1)}</td>
                    <td class="num" style="color: #0284c7;">+${formatNumber(it.in_transit, 1)}</td>
                    <td class="num" style="background:#faf5ff; font-weight: 700; color: ${it.is_triggered ? '#ef4444' : '#0f172a'};">
                        ${formatNumber(it.net_available, 1)}
                    </td>
                    <td class="num">${formatNumber(it.min_stock, 1)}</td>
                    <td class="num">${formatNumber(it.max_stock, 1)}</td>
                    <td class="num" style="font-size: 0.88rem; font-weight: 800; color: ${it.is_triggered ? '#9333ea' : '#94a3b8'};">
                        ${it.suggested_order_qty > 0 ? (formatNumber(it.suggested_order_qty, 1) + ' ' + it.uom) : '—'}
                    </td>
                    <td class="num">${it.estimated_cost > 0 ? formatPHP(it.estimated_cost) : '—'}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span class="rr-badge ${routeClass}">${it.action_route === 'internal_transfer' ? 'TRF' : 'PO'}</span>
                            <span style="font-size: 0.74rem; color: #475569;">${partnerText}</span>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        ${it.is_triggered 
                            ? `<button type="button" class="rr-btn rr-btn-primary" style="font-size: 0.7rem; padding: 4px 8px;" onclick="draftSingleItem(${it.rule_id})">
                                   <i class="ph ph-lightning"></i>
                                   <span>Draft ${it.action_route === 'internal_transfer' ? 'TRF' : 'PO'}</span>
                               </button>` 
                            : `<span style="font-size: 0.72rem; color: #94a3b8;">Sufficient</span>`}
                    </td>
                </tr>
            `;
        }).join('');
    }

    // 5. Render History Table
    function renderHistoryTable() {
        const tbody = document.getElementById('historyTableBody');
        if (!tbody) return;

        tbody.innerHTML = AppStore.history.map(h => `
            <tr>
                <td style="font-size: 0.74rem; color: #64748b;">${h.timestamp}</td>
                <td>
                    <strong style="color: var(--rr-brand); font-family: monospace;">${h.ref}</strong>
                </td>
                <td>
                    <span class="rr-badge ${h.type.includes('Transfer') ? 'route-trf' : 'route-po'}">${h.type}</span>
                </td>
                <td style="font-size: 0.76rem; color: #334155;">${h.route}</td>
                <td class="num" style="font-weight: 600;">${h.itemCount} item(s)</td>
                <td class="num" style="font-weight: 700; color: #0f172a;">${h.totalVal}</td>
                <td>
                    <span class="rr-badge status-healthy">${h.status}</span>
                </td>
                <td style="font-size: 0.74rem; color: #64748b;">${h.createdBy}</td>
                <td style="text-align: right;">
                    <a href="${h.url}" class="rr-btn rr-btn-secondary" style="font-size: 0.7rem; padding: 3px 8px; text-decoration: none;">
                        <i class="ph ph-arrow-square-out"></i>
                        <span>Inspect</span>
                    </a>
                </td>
            </tr>
        `).join('');
    }

    // 6. Filter Controls
    window.filterRulesByBranch = function (branchId) {
        AppStore.filters.branch = branchId;
        renderRulesTable();
        renderSimulatorTable();
    };

    window.handleRuleSearch = function (val) {
        AppStore.filters.search = val.trim();
        renderRulesTable();
    };

    window.applyFilters = function () {
        const cEl = document.getElementById('categoryFilter');
        const sEl = document.getElementById('strategyFilter');
        const rEl = document.getElementById('routeFilter');
        const stEl = document.getElementById('statusFilter');

        if (cEl) AppStore.filters.category = cEl.value;
        if (sEl) AppStore.filters.strategy = sEl.value;
        if (rEl) AppStore.filters.route = rEl.value;
        if (stEl) AppStore.filters.status = stEl.value;

        renderRulesTable();
    };

    window.applyStatusQuickFilter = function (status) {
        window.switchRrTab('rules');
        const stEl = document.getElementById('statusFilter');
        if (stEl) stEl.value = status;
        AppStore.filters.status = status;
        renderRulesTable();
    };

    window.resetFilters = function () {
        AppStore.filters = { branch: 'all', category: 'all', search: '', strategy: 'all', route: 'all', status: 'all' };
        const bEl = document.getElementById('branchGlobalFilter');
        const cEl = document.getElementById('categoryFilter');
        const sEl = document.getElementById('strategyFilter');
        const rEl = document.getElementById('routeFilter');
        const stEl = document.getElementById('statusFilter');
        const searchEl = document.getElementById('ruleSearchInput');

        if (bEl) bEl.value = 'all';
        if (cEl) cEl.value = 'all';
        if (sEl) sEl.value = 'all';
        if (rEl) rEl.value = 'all';
        if (stEl) stEl.value = 'all';
        if (searchEl) searchEl.value = '';

        renderRulesTable();
        renderSimulatorTable();
    };

    // 7. Half-Screen Drawer: Create & Edit Reordering Rule
    function syncRrHeaderOffset() {
        const headerEl = document.querySelector('.main-header');
        const offset = headerEl ? Math.max(56, Math.round(headerEl.getBoundingClientRect().bottom)) : 64;
        document.documentElement.style.setProperty('--rms-header-offset', `${offset}px`);
    }

    function appendGeneratedDraftsToHistory(data) {
        (data.created_purchase_orders || []).forEach(po => {
            AppStore.history.unshift({
                timestamp: 'Just now',
                ref: po.po_number,
                type: 'Purchase Order (Draft)',
                route: `${po.vendor_name} → ${po.branch_name}`,
                itemCount: po.items_count,
                totalVal: formatPHP(po.gross_total),
                status: 'DRAFT_CREATED',
                createdBy: 'Automated Replenishment Engine',
                url: cfg.routes.purchaseOrdersPage,
            });
        });

        (data.created_transfers || []).forEach(trf => {
            AppStore.history.unshift({
                timestamp: 'Just now',
                ref: trf.transfer_number,
                type: 'Internal Transfer (Draft)',
                route: `${trf.source_location} → ${trf.destination_location}`,
                itemCount: trf.items_count,
                totalVal: `${trf.total_units} Units`,
                status: 'DRAFT_CREATED',
                createdBy: 'Automated Replenishment Engine',
                url: cfg.routes.internalTransfersPage,
            });
        });
    }

    window.switchRuleDrawerTab = function (tabKey) {
        const allowed = ['general', 'thresholds', 'routing'];
        const target = allowed.includes(tabKey) ? tabKey : 'general';
        document.querySelectorAll('#ruleModalBackdrop [data-rr-drawer-tab]').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-rr-drawer-tab') === target);
        });
        document.querySelectorAll('#ruleModalBackdrop .rms-drawer-tab-pane').forEach(pane => {
            pane.classList.toggle('active', pane.id === `rrDrawerPane-${target}`);
        });
    };

    window.selectRuleStrategyCard = function (stratVal) {
        const sel = document.getElementById('formStrategy');
        if (sel) sel.value = stratVal;
        document.querySelectorAll('#rrStrategyCards .rr-select-card').forEach(card => {
            card.classList.toggle('active', card.getAttribute('data-strat') === stratVal);
        });
        window.updateRuleDrawerLivePreview();
    };

    window.selectRuleRouteCard = function (routeVal) {
        const sel = document.getElementById('formActionRoute');
        if (sel) sel.value = routeVal;
        document.querySelectorAll('#rrRouteCards .rr-select-card').forEach(card => {
            card.classList.toggle('active', card.getAttribute('data-route') === routeVal);
        });
        window.handleFormRouteToggle(routeVal);
        window.updateRuleDrawerLivePreview();
    };

    function syncBulkScopeSummary() {
        const isEdit = Boolean(document.getElementById('formRuleId')?.value);
        const count = isEdit ? (document.getElementById('formProductSelect')?.value ? 1 : 0) : AppStore.selectedProductIds.size;
        const badgeText = `${count} SKU${count === 1 ? '' : 's'}`;

        const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        setEl('drawerScopeTabCount', badgeText);
        setEl('drawerSelectedSkuBadge', `${badgeText} Selected`);
        setEl('drawerFooterScopeSummary', badgeText);

        const saveLbl = document.getElementById('btnSaveRuleLabel');
        if (saveLbl) {
            saveLbl.textContent = (!isEdit && count > 1) ? `Apply to ${count} SKUs` : 'Save Rule';
        }

        // If exactly 1 SKU selected in create mode, keep hidden #formProductSelect in sync for preview math
        if (!isEdit) {
            const prodSel = document.getElementById('formProductSelect');
            if (prodSel) {
                if (count === 1) {
                    prodSel.value = String(Array.from(AppStore.selectedProductIds)[0]);
                } else {
                    prodSel.value = '';
                }
            }
        }
    }

    function renderDrawerCategoryChips() {
        const container = document.getElementById('drawerCategoryChips');
        if (!container) return;

        const countsByCat = {};
        AppStore.items.forEach(it => {
            const cat = it.category || 'Uncategorized';
            countsByCat[cat] = (countsByCat[cat] || 0) + 1;
        });

        const sortedCats = Object.keys(countsByCat).sort((a, b) => a.localeCompare(b));
        container.innerHTML = sortedCats.map(cat => {
            const active = AppStore.selectedCategories.has(cat);
            return `
                <button type="button"
                        class="rr-cat-chip ${active ? 'active' : ''}"
                        data-cat="${escapeHtml(cat)}"
                        onclick="toggleScopeCategory(this.getAttribute('data-cat'))">
                    <i class="ph ${active ? 'ph-check-square' : 'ph-square'}"></i>
                    <span>${escapeHtml(cat)}</span>
                    <span class="rr-cat-chip-count">${countsByCat[cat]}</span>
                </button>
            `;
        }).join('');
    }

    window.renderDrawerSkuChecklist = function () {
        const listEl = document.getElementById('drawerScopeSkuChecklist');
        if (!listEl) return;

        const query = (document.getElementById('drawerSkuFilterInput')?.value || '').toLowerCase().trim();
        const filtered = AppStore.items.filter(it => {
            if (!query) return true;
            return (it.sku || '').toLowerCase().includes(query)
                || (it.name || '').toLowerCase().includes(query)
                || (it.category || '').toLowerCase().includes(query);
        });

        if (filtered.length === 0) {
            listEl.innerHTML = `<div style="padding: 18px; text-align: center; font-size: 12px; color: #64748b;">No products match "${escapeHtml(query)}".</div>`;
            return;
        }

        listEl.innerHTML = filtered.map(it => {
            const idNum = Number(it.id);
            const checked = AppStore.selectedProductIds.has(idNum);
            return `
                <label class="rr-sku-check-row">
                    <div class="rr-sku-check-left">
                        <input type="checkbox" ${checked ? 'checked' : ''} onchange="toggleScopeProduct(${idNum}, this.checked)">
                        <div style="min-width: 0;">
                            <div class="rr-sku-check-title">${escapeHtml(it.name)}</div>
                            <div class="rr-sku-check-sub">${escapeHtml(it.sku)} · ${escapeHtml(it.uom || 'Units')}</div>
                        </div>
                    </div>
                    <span class="rr-sku-check-cat">${escapeHtml(it.category || 'General')}</span>
                </label>
            `;
        }).join('');
    };

    window.toggleScopeCategory = function (cat) {
        const isSelecting = !AppStore.selectedCategories.has(cat);
        if (isSelecting) {
            AppStore.selectedCategories.add(cat);
            AppStore.items.forEach(it => {
                if ((it.category || 'Uncategorized') === cat) {
                    AppStore.selectedProductIds.add(Number(it.id));
                }
            });
        } else {
            AppStore.selectedCategories.delete(cat);
            AppStore.items.forEach(it => {
                if ((it.category || 'Uncategorized') === cat) {
                    AppStore.selectedProductIds.delete(Number(it.id));
                }
            });
        }
        renderDrawerCategoryChips();
        window.renderDrawerSkuChecklist();
        window.updateRuleDrawerLivePreview();
    };

    window.toggleScopeProduct = function (itemId, checked) {
        const idNum = Number(itemId);
        if (checked) AppStore.selectedProductIds.add(idNum);
        else AppStore.selectedProductIds.delete(idNum);

        // Keep category chip highlighted if ALL SKUs of that category are currently selected
        const allCats = new Set(AppStore.items.map(it => it.category || 'Uncategorized'));
        AppStore.selectedCategories.clear();
        allCats.forEach(cat => {
            const catItems = AppStore.items.filter(it => (it.category || 'Uncategorized') === cat);
            if (catItems.length > 0 && catItems.every(it => AppStore.selectedProductIds.has(Number(it.id)))) {
                AppStore.selectedCategories.add(cat);
            }
        });

        renderDrawerCategoryChips();
        window.updateRuleDrawerLivePreview();
    };

    window.selectAllVisibleScopeSkus = function () {
        const query = (document.getElementById('drawerSkuFilterInput')?.value || '').toLowerCase().trim();
        AppStore.items.forEach(it => {
            const match = !query
                || (it.sku || '').toLowerCase().includes(query)
                || (it.name || '').toLowerCase().includes(query)
                || (it.category || '').toLowerCase().includes(query);
            if (match) AppStore.selectedProductIds.add(Number(it.id));
        });
        renderDrawerCategoryChips();
        window.renderDrawerSkuChecklist();
        window.updateRuleDrawerLivePreview();
    };

    window.clearScopeSelection = function () {
        AppStore.selectedProductIds.clear();
        AppStore.selectedCategories.clear();
        renderDrawerCategoryChips();
        window.renderDrawerSkuChecklist();
        window.updateRuleDrawerLivePreview();
    };

    window.updateRuleDrawerLivePreview = function () {
        syncBulkScopeSummary();

        const isEdit = Boolean(document.getElementById('formRuleId')?.value);
        const stratVal = document.getElementById('formStrategy')?.value || 'min_max';
        const routeVal = document.getElementById('formActionRoute')?.value || 'purchase_order';
        const branchId = parseInt(document.getElementById('formBranchSelect')?.value || '1', 10);
        const minVal = parseFloat(document.getElementById('formMinStock')?.value) || 0;
        const maxVal = parseFloat(document.getElementById('formMaxStock')?.value) || 0;
        const safetyVal = parseFloat(document.getElementById('formSafetyBuffer')?.value) || 0;
        const moqVal = Math.max(1, parseFloat(document.getElementById('formMoq')?.value) || 1);
        const isActive = document.getElementById('formIsActive')?.checked !== false;

        const stratChip = document.getElementById('drawerStrategyChip');
        if (stratChip) {
            stratChip.className = `rr-badge ${stratVal === 'commissary_push' ? 'strat-comm' : (stratVal === 'jit_back_to_back' ? 'strat-jit' : 'strat-minmax')}`;
            stratChip.textContent = stratVal === 'commissary_push' ? 'Commissary Push' : (stratVal === 'jit_back_to_back' ? 'JIT Demand' : 'Min-Max');
        }

        const statusPill = document.getElementById('drawerStatusPill');
        if (statusPill) {
            statusPill.className = `rr-status-pill ${isActive ? 'active' : 'inactive'}`;
            statusPill.textContent = isActive ? 'Active' : 'Inactive';
        }

        // Lookup Selected Product Chip & Multi-SKU Math Preview
        const prodSel = document.getElementById('formProductSelect');
        const prodOpt = prodSel && prodSel.selectedIndex > 0 ? prodSel.options[prodSel.selectedIndex] : null;
        const lookupChip = document.getElementById('drawerProductSummaryChip');
        const selectedItems = isEdit
            ? (prodOpt ? [AppStore.items.find(it => Number(it.id) === Number(prodSel.value))].filter(Boolean) : [])
            : AppStore.items.filter(it => AppStore.selectedProductIds.has(Number(it.id)));

        const branchRatio = branchId === 1 ? 0.45 : (branchId === 2 ? 0.35 : 0.20);
        let estOnHandDisplay = '0';
        let uomLabel = '';
        let totalSuggestedQty = 0;
        let totalEstCost = 0;
        let hasCritical = false;
        let hasTrigger = false;

        if (selectedItems.length === 1 && lookupChip) {
            const single = selectedItems[0];
            const cost = parseFloat(single.cost_price || '0') || 0;
            const gStock = parseFloat(single.current_stock || '0') || 0;
            uomLabel = single.uom || 'Units';
            document.getElementById('chipProdSku').textContent = single.sku || '—';
            document.getElementById('chipProdCategory').textContent = single.category || 'General';
            document.getElementById('chipProdUom').textContent = uomLabel;
            document.getElementById('chipProdCost').textContent = formatPHP(cost);
            document.getElementById('chipProdStock').textContent = `${formatNumber(gStock, 1)} ${uomLabel}`;
            lookupChip.style.display = 'grid';

            const estOnHand = Math.round(gStock * branchRatio * 100) / 100;
            estOnHandDisplay = `${formatNumber(estOnHand, 1)} ${uomLabel}`;
            if (estOnHand <= minVal && maxVal > minVal) {
                hasTrigger = true;
                const critFloor = safetyVal > 0 ? safetyVal : (minVal * 0.3);
                if (estOnHand <= critFloor) hasCritical = true;
                const deficit = Math.max(0, maxVal - estOnHand);
                totalSuggestedQty = Math.ceil(deficit / moqVal) * moqVal;
                totalEstCost = Math.round(totalSuggestedQty * cost * 100) / 100;
            }
        } else if (lookupChip) {
            lookupChip.style.display = 'none';
            let sumStock = 0;
            selectedItems.forEach(it => {
                const cost = parseFloat(it.cost_price || '0') || 0;
                const estOnHand = Math.round((parseFloat(it.current_stock || '0') || 0) * branchRatio * 100) / 100;
                sumStock += estOnHand;
                if (estOnHand <= minVal && maxVal > minVal) {
                    hasTrigger = true;
                    const critFloor = safetyVal > 0 ? safetyVal : (minVal * 0.3);
                    if (estOnHand <= critFloor) hasCritical = true;
                    const q = Math.ceil(Math.max(0, maxVal - estOnHand) / moqVal) * moqVal;
                    totalSuggestedQty += q;
                    totalEstCost += (q * cost);
                }
            });
            estOnHandDisplay = selectedItems.length > 0 ? `${formatNumber(sumStock, 1)} Total (${selectedItems.length} SKUs)` : '0';
        }

        const setEl = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        setEl('mathEstOnHand', estOnHandDisplay);
        setEl('mathTriggerAt', `${formatNumber(minVal, 1)}${uomLabel ? ' ' + uomLabel : ''}`);
        setEl('mathSuggestedQty', totalSuggestedQty > 0 ? `${formatNumber(totalSuggestedQty, 1)}${uomLabel ? ' ' + uomLabel : ' Units'}` : '0');
        setEl('mathEstCost', formatPHP(Math.round(totalEstCost * 100) / 100));

        const mathBadge = document.getElementById('drawerMathBadge');
        if (mathBadge) {
            if (selectedItems.length === 0) {
                mathBadge.textContent = 'Select Product / Category';
            } else if (maxVal <= minVal) {
                mathBadge.textContent = 'Max must exceed Min';
            } else if (hasCritical) {
                mathBadge.textContent = 'Critical Under Par';
            } else if (hasTrigger) {
                mathBadge.textContent = `Triggers Restock`;
            } else {
                mathBadge.textContent = 'Healthy Par';
            }
        }

        setEl('drawerFooterParSummary', `${formatNumber(minVal, 0)} / ${formatNumber(maxVal, 0)}`);
        setEl('drawerFooterRouteSummary', routeVal === 'internal_transfer' ? 'Internal Transfer (TRF)' : 'Vendor Purchase Order (PO)');
    };

    window.openCreateRuleModal = function () {
        const drawer = document.getElementById('ruleModalBackdrop');
        const backdrop = document.getElementById('ruleDrawerBackdrop');
        const form = document.getElementById('ruleForm');
        if (!drawer || !form) return;

        syncRrHeaderOffset();
        form.reset();
        document.getElementById('formRuleId').value = '';
        document.getElementById('ruleModalTitle').textContent = 'Configure New Reordering Rules';
        document.getElementById('ruleModalSubtitle').textContent = 'Apply par thresholds to individual SKUs or select entire Product Categories in bulk.';
        document.getElementById('drawerRuleCodeChip').textContent = 'NEW-RULE';

        document.getElementById('drawerSingleSkuSection').style.display = 'none';
        document.getElementById('drawerBulkScopeSection').style.display = 'block';
        AppStore.selectedProductIds.clear();
        AppStore.selectedCategories.clear();
        const skuFilter = document.getElementById('drawerSkuFilterInput');
        if (skuFilter) skuFilter.value = '';
        renderDrawerCategoryChips();
        window.renderDrawerSkuChecklist();

        document.getElementById('formMinStock').value = '15.00';
        document.getElementById('formMaxStock').value = '50.00';
        document.getElementById('formSafetyBuffer').value = '0';
        document.getElementById('formMoq').value = '1.00';
        document.getElementById('formLeadTime').value = '2';
        document.getElementById('formAutoDraft').checked = true;
        const activeChk = document.getElementById('formIsActive');
        if (activeChk) activeChk.checked = true;
        const targetWh = document.getElementById('formTargetWarehouseSelect');
        if (targetWh) targetWh.value = '';

        const delBtn = document.getElementById('btnDeleteRuleInDrawer');
        if (delBtn) delBtn.style.display = 'none';

        window.selectRuleStrategyCard('min_max');
        window.selectRuleRouteCard('purchase_order');
        window.switchRuleDrawerTab('general');
        window.updateRuleDrawerLivePreview();

        if (backdrop) {
            backdrop.classList.add('is-open');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        const body = drawer.querySelector('.rms-drawer-body');
        if (body) body.scrollTop = 0;
    };

    window.openEditRuleModal = function (ruleId) {
        const rule = AppStore.rules.find(r => r.id === ruleId);
        if (!rule) return;

        const drawer = document.getElementById('ruleModalBackdrop');
        const backdrop = document.getElementById('ruleDrawerBackdrop');
        if (!drawer) return;

        syncRrHeaderOffset();

        const code = rule.rule_code || ('RUL-' + rule.id);
        document.getElementById('ruleModalTitle').textContent = rule.product_name || `Edit Reordering Rule (${code})`;
        document.getElementById('ruleModalSubtitle').textContent = `${rule.branch_name || 'Branch'} • Configure par boundaries, MOQ multiples, and replenishment route`;
        document.getElementById('drawerRuleCodeChip').textContent = code;

        document.getElementById('drawerSingleSkuSection').style.display = 'block';
        document.getElementById('drawerBulkScopeSection').style.display = 'none';

        document.getElementById('formRuleId').value = rule.id;
        document.getElementById('formBranchSelect').value = rule.branch_id;
        document.getElementById('formProductSelect').value = rule.product_id;
        document.getElementById('formMinStock').value = rule.min_stock;
        document.getElementById('formMaxStock').value = rule.max_stock;
        document.getElementById('formSafetyBuffer').value = rule.safety_buffer ?? 0;
        document.getElementById('formMoq').value = rule.moq || 1.00;
        document.getElementById('formLeadTime').value = rule.lead_time_days ?? 2;
        document.getElementById('formNotes').value = rule.notes || '';
        document.getElementById('formAutoDraft').checked = !!rule.auto_generate_draft;

        const activeChk = document.getElementById('formIsActive');
        if (activeChk) activeChk.checked = rule.is_active !== false;

        const targetWh = document.getElementById('formTargetWarehouseSelect');
        if (targetWh) targetWh.value = rule.target_warehouse_id || '';

        const vendorEl = document.getElementById('formVendorSelect');
        if (vendorEl) vendorEl.value = rule.preferred_vendor_id || '';

        const whEl = document.getElementById('formWarehouseSelect');
        if (whEl && rule.source_warehouse_id) whEl.value = rule.source_warehouse_id;

        const delBtn = document.getElementById('btnDeleteRuleInDrawer');
        if (delBtn) delBtn.style.display = 'inline-flex';

        window.selectRuleStrategyCard(rule.strategy || 'min_max');
        window.selectRuleRouteCard(rule.action_route || 'purchase_order');
        window.switchRuleDrawerTab('thresholds');
        window.updateRuleDrawerLivePreview();

        if (backdrop) {
            backdrop.classList.add('is-open');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        const body = drawer.querySelector('.rms-drawer-body');
        if (body) body.scrollTop = 0;
    };

    window.closeRuleModal = function () {
        const drawer = document.getElementById('ruleModalBackdrop');
        const backdrop = document.getElementById('ruleDrawerBackdrop');
        if (drawer) {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
        }
        if (backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.setAttribute('aria-hidden', 'true');
        }
    };

    window.deleteEditingRuleFromDrawer = async function () {
        const id = parseInt(document.getElementById('formRuleId')?.value || '0', 10);
        if (!id) return;
        const countBefore = AppStore.rules.length;
        await window.deleteRule(id);
        if (AppStore.rules.length < countBefore) {
            window.closeRuleModal();
        }
    };

    window.handleBackdropClick = function (e) {
        if (e.target && (e.target.id === 'ruleDrawerBackdrop' || e.target.id === 'ruleModalBackdrop')) {
            closeRuleModal();
        }
    };

    window.handleFormRouteToggle = function (routeVal) {
        const vendorSec = document.getElementById('groupVendorSection');
        const commSec = document.getElementById('groupCommissarySection');
        if (vendorSec) vendorSec.style.display = (routeVal === 'purchase_order') ? 'flex' : 'none';
        if (commSec) commSec.style.display = (routeVal === 'internal_transfer') ? 'flex' : 'none';
    };

    window.handleFormProductChange = function () {
        const sel = document.getElementById('formProductSelect');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.dataset) return;

        const minStockEl = document.getElementById('formMinStock');
        const maxStockEl = document.getElementById('formMaxStock');

        if (minStockEl && !minStockEl.value) minStockEl.value = '15.00';
        if (maxStockEl && !maxStockEl.value) maxStockEl.value = '50.00';
        window.updateRuleDrawerLivePreview();
    };

    window.handleRuleFormSubmit = async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveRule');
        if (btn) btn.disabled = true;

        const id = document.getElementById('formRuleId').value;
        const branchSel = document.getElementById('formBranchSelect');
        const prodSel = document.getElementById('formProductSelect');
        const vendorSel = document.getElementById('formVendorSelect');
        const whSel = document.getElementById('formWarehouseSelect');
        const targetWhSel = document.getElementById('formTargetWarehouseSelect');

        const branchOpt = branchSel.options[branchSel.selectedIndex];
        const prodOpt = prodSel.options[prodSel.selectedIndex];
        const vendorOpt = vendorSel.options[vendorSel.selectedIndex];
        const whOpt = whSel ? whSel.options[whSel.selectedIndex] : null;

        const minStockRaw = document.getElementById('formMinStock')?.value;
        const maxStockRaw = document.getElementById('formMaxStock')?.value;
        const moqRaw = document.getElementById('formMoq')?.value;
        const minStockVal = parseFloat(minStockRaw);
        const maxStockVal = parseFloat(maxStockRaw);
        const moqVal = parseFloat(moqRaw);

        const isEdit = Boolean(id);
        const batchItems = !isEdit
            ? AppStore.items
                .filter(it => AppStore.selectedProductIds.has(Number(it.id)))
                .map(it => ({
                    product_id: Number(it.id),
                    product_sku: it.sku || 'SKU',
                    product_name: it.name || 'Item',
                    category: it.category || 'General',
                    uom: it.uom || 'Piece',
                }))
            : [];

        if (isEdit && !prodSel.value) {
            showToast('Please select a Product / SKU.', 'error');
            window.switchRuleDrawerTab('general');
            if (btn) btn.disabled = false;
            return;
        }
        if (!isEdit && batchItems.length === 0) {
            showToast('Please select at least one Product / SKU or Product Category.', 'error');
            window.switchRuleDrawerTab('general');
            if (btn) btn.disabled = false;
            return;
        }
        if (isNaN(minStockVal) || minStockVal < 0 || isNaN(maxStockVal) || maxStockVal <= minStockVal) {
            showToast('Max Target Par Stock must be greater than Min Stock.', 'error');
            window.switchRuleDrawerTab('thresholds');
            if (btn) btn.disabled = false;
            return;
        }
        if (isNaN(moqVal) || moqVal < 1) {
            showToast('Minimum Order Qty (MOQ) must be at least 1.', 'error');
            window.switchRuleDrawerTab('thresholds');
            if (btn) btn.disabled = false;
            return;
        }

        const payload = {
            id: isEdit ? parseInt(id, 10) : null,
            branch_id: parseInt(branchSel.value, 10),
            branch_name: branchOpt ? branchOpt.getAttribute('data-name') : 'Branch',
            strategy: document.getElementById('formStrategy').value,
            action_route: document.getElementById('formActionRoute').value,
            min_stock: minStockVal,
            max_stock: maxStockVal,
            safety_buffer: parseFloat(document.getElementById('formSafetyBuffer')?.value) || 0,
            moq: moqVal,
            lead_time_days: Math.max(0, parseInt(document.getElementById('formLeadTime').value, 10) || 0),
            preferred_vendor_id: vendorSel.value ? parseInt(vendorSel.value, 10) : null,
            preferred_vendor_name: (vendorOpt && vendorSel.value) ? vendorOpt.getAttribute('data-name') : null,
            source_warehouse_id: whSel ? whSel.value : null,
            source_warehouse_name: (whOpt && whSel.value) ? whOpt.getAttribute('data-name') : null,
            target_warehouse_id: targetWhSel && targetWhSel.value ? targetWhSel.value : null,
            notes: document.getElementById('formNotes').value,
            auto_generate_draft: document.getElementById('formAutoDraft').checked,
            is_active: document.getElementById('formIsActive')?.checked !== false,
        };

        if (isEdit) {
            payload.product_id = parseInt(prodSel.value, 10);
            payload.product_sku = prodOpt ? prodOpt.getAttribute('data-sku') : 'SKU';
            payload.product_name = prodOpt ? prodOpt.getAttribute('data-name') : 'Item';
            payload.category = prodOpt ? prodOpt.getAttribute('data-category') : 'General';
            payload.uom = prodOpt ? prodOpt.getAttribute('data-uom') : 'Piece';
        } else if (batchItems.length === 1) {
            Object.assign(payload, batchItems[0]);
        } else {
            payload.items_batch = batchItems;
        }

        try {
            const resp = await fetch(cfg.routes.saveRuleApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await resp.json();
            const savedList = Array.isArray(data.rules) ? data.rules : (data.rule ? [data.rule] : []);
            if (data.success && savedList.length > 0) {
                showToast(data.message || 'Rule successfully saved!');
                closeRuleModal();

                savedList.forEach(saved => {
                    const existingIdx = AppStore.rules.findIndex(r => r.id === saved.id);
                    if (existingIdx !== -1) {
                        AppStore.rules[existingIdx] = saved;
                    } else {
                        AppStore.rules.unshift(saved);
                    }
                });

                populateMainCategoryFilter();
                renderRulesTable();
                renderKpis();
                await runSimulationCheck(false);
            } else {
                showToast(data.message || 'Error saving rule', 'error');
            }
        } catch (err) {
            console.error('Save rule error:', err);
            showToast('Network error while saving rule', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    // 8. Toggle Rule Active
    window.toggleRuleActive = async function (ruleId) {
        try {
            const resp = await fetch(`${cfg.routes.toggleRuleApi}/${ruleId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success) {
                const rule = AppStore.rules.find(r => r.id === ruleId);
                if (rule) rule.is_active = data.is_active;
                showToast(data.message);
                renderKpis();
                await runSimulationCheck(false);
            }
        } catch (err) {
            console.error('Toggle error:', err);
            showToast('Failed to toggle rule state', 'error');
        }
    };

    // 9. Delete Rule
    window.deleteRule = async function (ruleId) {
        const rule = AppStore.rules.find(r => r.id === ruleId);
        const name = rule ? (rule.rule_code || rule.product_name) : 'Rule';
        if (!confirm(`Are you sure you want to delete reordering rule "${name}"?`)) return;

        try {
            const resp = await fetch(`${cfg.routes.deleteRuleApi}/${ruleId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success) {
                AppStore.rules = AppStore.rules.filter(r => r.id !== ruleId);
                showToast(data.message);
                renderRulesTable();
                renderKpis();
                await runSimulationCheck(false);
            }
        } catch (err) {
            console.error('Delete error:', err);
            showToast('Failed to delete rule', 'error');
        }
    };

    // 10. Run Simulator Check
    window.runSimulationCheck = async function (showNotification = true) {
        try {
            const branchParam = AppStore.filters.branch !== 'all' ? `?branch_id=${AppStore.filters.branch}` : '';
            const resp = await fetch(`${cfg.routes.simulateApi}${branchParam}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success && data.simulation) {
                AppStore.simulation = data.simulation;
                renderKpis();
                renderSimulatorTable();
                if (showNotification) {
                    showToast(`Simulator check complete — ${data.simulation.summary.triggered_count || 0} deficits found.`);
                }
            }
        } catch (err) {
            console.error('Simulation check error:', err);
            if (showNotification) showToast('Failed to run simulator check', 'error');
        }
    };

    // 11. Batch Draft Modal & Execution
    window.openBatchDraftModal = function () {
        const items = (AppStore.simulation && AppStore.simulation.items) ? AppStore.simulation.items : [];
        const triggered = items.filter(it => it.is_triggered && it.suggested_order_qty > 0);

        const modal = document.getElementById('batchModalBackdrop');
        const summaryText = document.getElementById('batchModalSummaryText');
        const tbody = document.getElementById('batchModalTableBody');
        const btn = document.getElementById('btnConfirmBatchDraft');
        if (!modal) return;

        if (triggered.length === 0) {
            summaryText.innerHTML = `
                <div style="padding: 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #047857;">
                    <i class="ph ph-check-circle" style="font-size: 1.15rem; vertical-align: middle;"></i>
                    <strong>All stock levels are optimal!</strong> There are currently zero items breaching par thresholds.
                </div>
            `;
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">No draft items queued.</td></tr>`;
            if (btn) btn.disabled = true;
        } else {
            const poCount = triggered.filter(t => t.action_route === 'purchase_order').length;
            const trfCount = triggered.filter(t => t.action_route === 'internal_transfer').length;
            const totalCost = triggered.reduce((acc, it) => acc + (it.estimated_cost || 0), 0);

            summaryText.innerHTML = `
                <div style="padding: 12px; background: #fdf4ff; border: 1px solid #f0abfc; border-radius: 8px; color: #86198f;">
                    Found <strong>${triggered.length} triggered items</strong> requiring replenishment:
                    <strong>${poCount} Vendor PO item(s)</strong> (est. ${formatPHP(totalCost)}) and 
                    <strong>${trfCount} Commissary Transfer item(s)</strong>.
                    Click below to generate official ERP drafts in 1 click.
                </div>
            `;

            tbody.innerHTML = triggered.map(it => `
                <tr>
                    <td>
                        <strong>${it.product_name}</strong>
                        <div style="font-size: 0.69rem; color: #64748b; font-family: monospace;">${it.product_sku}</div>
                    </td>
                    <td><span class="rr-branch-pill">${it.branch_name}</span></td>
                    <td class="num" style="color: #ef4444; font-weight: 700;">${formatNumber(it.net_available, 1)}</td>
                    <td class="num">${formatNumber(it.min_stock, 1)}</td>
                    <td class="num" style="font-weight: 800; color: #9333ea;">${formatNumber(it.suggested_order_qty, 1)} ${it.uom}</td>
                    <td>
                        <span class="rr-badge ${it.action_route === 'internal_transfer' ? 'route-trf' : 'route-po'}">
                            ${it.action_route === 'internal_transfer' ? 'TRF' : 'PO'}
                        </span>
                    </td>
                    <td style="font-size: 0.74rem;">${it.action_route === 'internal_transfer' ? it.source_warehouse_name : it.preferred_vendor_name}</td>
                </tr>
            `).join('');

            if (btn) btn.disabled = false;
        }

        modal.classList.add('open');
    };

    window.closeBatchDraftModal = function () {
        const modal = document.getElementById('batchModalBackdrop');
        if (modal) modal.classList.remove('open');
    };

    window.handleBatchBackdropClick = function (e) {
        if (e.target && e.target.id === 'batchModalBackdrop') {
            closeBatchDraftModal();
        }
    };

    window.executeBatchDraftGeneration = async function () {
        const btn = document.getElementById('btnConfirmBatchDraft');
        if (btn) btn.disabled = true;

        try {
            const items = (AppStore.simulation && AppStore.simulation.items) ? AppStore.simulation.items : [];
            const triggeredRuleIds = items.filter(it => it.is_triggered && it.suggested_order_qty > 0).map(it => it.rule_id);

            const resp = await fetch(cfg.routes.generateDraftsApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rule_ids: triggeredRuleIds })
            });

            const data = await resp.json();
            if (data.success) {
                closeBatchDraftModal();
                showToast(data.message || 'Draft Purchase Orders and Transfers generated!');
                appendGeneratedDraftsToHistory(data);
                renderKpis();
                renderHistoryTable();
                await runSimulationCheck(false);
            } else {
                showToast(data.message || 'Failed to generate drafts', 'error');
            }
        } catch (err) {
            console.error('Batch draft error:', err);
            showToast('Network error while generating drafts', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    window.draftSingleItem = async function (ruleId) {
        try {
            const resp = await fetch(cfg.routes.generateDraftsApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rule_ids: [ruleId] })
            });

            const data = await resp.json();
            if (data.success) {
                showToast('Draft created successfully!');
                appendGeneratedDraftsToHistory(data);
                renderKpis();
                renderHistoryTable();
                await runSimulationCheck(false);
            } else {
                showToast(data.message || 'Could not draft item', 'error');
            }
        } catch (err) {
            console.error('Single draft error:', err);
            showToast('Failed to create draft', 'error');
        }
    };

    // Initialize View
    populateMainCategoryFilter();
    renderKpis();
    renderRulesTable();
    renderSimulatorTable();
    renderHistoryTable();

    window.addEventListener('resize', syncRrHeaderOffset);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeRuleModal();
            closeBatchDraftModal();
        }
    });

    // Expose AppStore for ERP cross-module debugging
    window.ReorderRulesStore = AppStore;

})();

