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
        filters: {
            branch: 'all',
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

    // 3. Render Rules Table
    function renderRulesTable() {
        const tbody = document.getElementById('rulesTableBody');
        if (!tbody) return;

        let filtered = AppStore.rules.filter(r => {
            if (AppStore.filters.branch !== 'all' && String(r.branch_id) !== String(AppStore.filters.branch)) return false;
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
                return code.includes(term) || sku.includes(term) || name.includes(term) || bName.includes(term);
            }
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" style="text-align: center; padding: 36px 16px; color: var(--rr-text-muted);">
                        <i class="ph ph-folder-dashed" style="font-size: 2.2rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                        No reordering rules match the selected filters.
                        <div style="margin-top: 10px;">
                            <button type="button" class="rr-btn rr-btn-secondary" onclick="resetFilters()">Reset Filters</button>
                            <button type="button" class="rr-btn rr-btn-primary" onclick="openCreateRuleModal()">Create First Rule</button>
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
                        <strong style="color: var(--rr-brand); font-family: monospace; font-size: 0.8rem;">${r.rule_code || ('RUL-' + r.id)}</strong>
                    </td>
                    <td>
                        <span class="rr-branch-pill">
                            <span class="rr-branch-dot"></span>
                            <span>${r.branch_name}</span>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;">${r.product_name}</div>
                        <div style="font-size: 0.7rem; color: #64748b; font-family: monospace;">SKU: ${r.product_sku} • ${r.uom}</div>
                    </td>
                    <td>
                        <span class="rr-badge ${stratClass}">${r.strategy_label || r.strategy}</span>
                    </td>
                    <td class="num"><strong>${formatNumber(r.min_stock, 1)}</strong></td>
                    <td class="num"><strong>${formatNumber(r.max_stock, 1)}</strong></td>
                    <td class="num" style="color: #64748b;">${formatNumber(r.moq, 1)}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span class="rr-badge ${routeClass}">${r.action_route === 'internal_transfer' ? 'TRF' : 'PO'}</span>
                            <span style="font-size: 0.74rem; color: #334155; max-width: 160px; overflow: hidden; text-overflow: ellipsis;">${partnerText}</span>
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
        const sEl = document.getElementById('strategyFilter');
        const rEl = document.getElementById('routeFilter');
        const stEl = document.getElementById('statusFilter');

        if (sEl) AppStore.filters.strategy = sEl.value;
        if (rEl) AppStore.filters.route = rEl.value;
        if (stEl) AppStore.filters.status = stEl.value;

        renderRulesTable();
    };

    window.resetFilters = function () {
        AppStore.filters = { branch: 'all', search: '', strategy: 'all', route: 'all', status: 'all' };
        const bEl = document.getElementById('branchGlobalFilter');
        const sEl = document.getElementById('strategyFilter');
        const rEl = document.getElementById('routeFilter');
        const stEl = document.getElementById('statusFilter');
        const searchEl = document.getElementById('ruleSearchInput');

        if (bEl) bEl.value = 'all';
        if (sEl) sEl.value = 'all';
        if (rEl) rEl.value = 'all';
        if (stEl) stEl.value = 'all';
        if (searchEl) searchEl.value = '';

        renderRulesTable();
        renderSimulatorTable();
    };

    // 7. Modals: Create & Edit Rule
    window.openCreateRuleModal = function () {
        const modal = document.getElementById('ruleModalBackdrop');
        const title = document.getElementById('ruleModalTitle');
        const form = document.getElementById('ruleForm');
        if (!modal || !form) return;

        form.reset();
        document.getElementById('formRuleId').value = '';
        if (title) title.textContent = 'Configure New Reordering Rule';

        document.getElementById('formMoq').value = '1.00';
        document.getElementById('formLeadTime').value = '2';
        document.getElementById('formAutoDraft').checked = true;

        handleFormRouteToggle('purchase_order');
        modal.classList.add('open');
    };

    window.openEditRuleModal = function (ruleId) {
        const rule = AppStore.rules.find(r => r.id === ruleId);
        if (!rule) return;

        const modal = document.getElementById('ruleModalBackdrop');
        const title = document.getElementById('ruleModalTitle');
        if (!modal) return;

        if (title) title.textContent = `Edit Reordering Rule (${rule.rule_code || ('RUL-' + rule.id)})`;

        document.getElementById('formRuleId').value = rule.id;
        document.getElementById('formBranchSelect').value = rule.branch_id;
        document.getElementById('formProductSelect').value = rule.product_id;
        document.getElementById('formStrategy').value = rule.strategy || 'min_max';
        document.getElementById('formActionRoute').value = rule.action_route || 'purchase_order';
        document.getElementById('formMinStock').value = rule.min_stock;
        document.getElementById('formMaxStock').value = rule.max_stock;
        document.getElementById('formMoq').value = rule.moq || 1.00;
        document.getElementById('formLeadTime').value = rule.lead_time_days || 2;
        document.getElementById('formNotes').value = rule.notes || '';
        document.getElementById('formAutoDraft').checked = !!rule.auto_generate_draft;

        if (rule.preferred_vendor_id) {
            document.getElementById('formVendorSelect').value = rule.preferred_vendor_id;
        }
        if (rule.source_warehouse_id) {
            document.getElementById('formWarehouseSelect').value = rule.source_warehouse_id;
        }

        handleFormRouteToggle(rule.action_route || 'purchase_order');
        modal.classList.add('open');
    };

    window.closeRuleModal = function () {
        const modal = document.getElementById('ruleModalBackdrop');
        if (modal) modal.classList.remove('open');
    };

    window.handleBackdropClick = function (e) {
        if (e.target && e.target.id === 'ruleModalBackdrop') {
            closeRuleModal();
        }
    };

    window.handleFormRouteToggle = function (routeVal) {
        const vendorSec = document.getElementById('groupVendorSection');
        const commSec = document.getElementById('groupCommissarySection');
        if (vendorSec) vendorSec.style.display = (routeVal === 'purchase_order') ? 'flex' : 'none';
        if (commSec) commSec.style.display = (routeVal === 'internal_transfer') ? 'flex' : 'none';
    };

    window.handleFormProductChange = function (itemId) {
        const sel = document.getElementById('formProductSelect');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.dataset) return;

        const minStockEl = document.getElementById('formMinStock');
        const maxStockEl = document.getElementById('formMaxStock');

        if (minStockEl && !minStockEl.value) minStockEl.value = '15.00';
        if (maxStockEl && !maxStockEl.value) maxStockEl.value = '50.00';
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

        const branchOpt = branchSel.options[branchSel.selectedIndex];
        const prodOpt = prodSel.options[prodSel.selectedIndex];
        const vendorOpt = vendorSel.options[vendorSel.selectedIndex];
        const whOpt = whSel ? whSel.options[whSel.selectedIndex] : null;

        const payload = {
            id: id ? parseInt(id, 10) : null,
            branch_id: parseInt(branchSel.value, 10),
            branch_name: branchOpt ? branchOpt.getAttribute('data-name') : 'Branch',
            product_id: parseInt(prodSel.value, 10),
            product_sku: prodOpt ? prodOpt.getAttribute('data-sku') : 'SKU',
            product_name: prodOpt ? prodOpt.getAttribute('data-name') : 'Item',
            category: prodOpt ? prodOpt.getAttribute('data-category') : 'General',
            uom: prodOpt ? prodOpt.getAttribute('data-uom') : 'Piece',
            strategy: document.getElementById('formStrategy').value,
            action_route: document.getElementById('formActionRoute').value,
            min_stock: parseFloat(document.getElementById('formMinStock').value) || 0,
            max_stock: parseFloat(document.getElementById('formMaxStock').value) || 0,
            moq: parseFloat(document.getElementById('formMoq').value) || 1,
            lead_time_days: parseInt(document.getElementById('formLeadTime').value, 10) || 2,
            preferred_vendor_id: vendorSel.value ? parseInt(vendorSel.value, 10) : null,
            preferred_vendor_name: (vendorOpt && vendorSel.value) ? vendorOpt.getAttribute('data-name') : null,
            source_warehouse_id: whSel ? whSel.value : null,
            source_warehouse_name: (whOpt && whSel.value) ? whOpt.getAttribute('data-name') : null,
            notes: document.getElementById('formNotes').value,
            auto_generate_draft: document.getElementById('formAutoDraft').checked,
            is_active: true,
        };

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
            if (data.success && data.rule) {
                showToast(data.message || 'Rule successfully saved!');
                closeRuleModal();

                const existingIdx = AppStore.rules.findIndex(r => r.id === data.rule.id);
                if (existingIdx !== -1) {
                    AppStore.rules[existingIdx] = data.rule;
                } else {
                    AppStore.rules.unshift(data.rule);
                }

                renderRulesTable();
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

                // Append to history log
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
    renderKpis();
    renderRulesTable();
    renderSimulatorTable();
    renderHistoryTable();

    // Expose AppStore for ERP cross-module debugging
    window.ReorderRulesStore = AppStore;

})();

