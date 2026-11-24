(() => {
    const cfg = window.CustomerMasterlistConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let customers = Array.isArray(cfg.data?.initialCustomers) ? [...cfg.data.initialCustomers] : [];
    const items = Array.isArray(cfg.data?.initialItems) ? cfg.data.initialItems : [];
    let activeTier = 'ALL';
    let searchQuery = '';
    let viewMode = localStorage.getItem('rms_customer_directory_view') === 'cards' ? 'cards' : 'list';
    let activeCustomerId = null;
    let activeProfile = null;
    let dupTimer = null;

    const esc = (str) => String(str ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
    const fmtPhp = (val) => '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtDate = (d) => d ? String(d).slice(0, 10) : '—';

    function toast(type, msg) {
        const root = document.getElementById('customerToastContainer');
        if (!root) return alert(msg);
        const el = document.createElement('div');
        el.className = `rfq-toast ${type === 'error' ? 'error' : 'success'}`;
        el.innerHTML = `<i class="ph ${type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'}"></i><span>${esc(msg)}</span>`;
        root.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    async function apiCall(url, method = 'POST', body = null) {
        const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
        if (!isForm && body !== null) headers['Content-Type'] = 'application/json';
        const res = await fetch(url, {
            method,
            headers,
            body: body === null ? null : (isForm ? body : JSON.stringify(body)),
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            const firstErr = json.errors ? Object.values(json.errors)[0]?.[0] : null;
            const err = new Error(firstErr || json.message || 'Operation failed.');
            err.duplicates = json.duplicates || null;
            throw err;
        }
        return json;
    }

    function hydrateUoms() {
        if (window.UomRegistry && typeof window.UomRegistry.autoHydrate === 'function') {
            window.UomRegistry.autoHydrate();
        }
    }

    // =========================================================================
    // DIRECTORY RENDERING, KPI COUNTS, LIST VIEW & CARDS VIEW
    // =========================================================================
    function getStage(c) {
        return String(c.account_stage || (c.is_active === false ? 'ARCHIVED' : 'ACTIVE')).toUpperCase();
    }

    function renderDirectoryKpis() {
        const nonArchived = customers.filter((c) => getStage(c) !== 'ARCHIVED');
        const active = customers.filter((c) => getStage(c) === 'ACTIVE');
        const prospects = customers.filter((c) => getStage(c) === 'PROSPECT');
        const hold = nonArchived.filter((c) => !!c.credit_hold);
        const archived = customers.filter((c) => getStage(c) === 'ARCHIVED');
        const openAr = customers.reduce((s, c) => s + Number(c.current_balance || 0), 0);
        const totalClv = customers.reduce((s, c) => s + Number(c.clv_amount || 0), 0);
        const docAlertCustomers = nonArchived.filter((c) => (Number(c.expiring_docs_count || 0) + Number(c.expired_docs_count || 0)) > 0);
        const docAlerts = customers.reduce((s, c) => s + Number(c.expiring_docs_count || 0) + Number(c.expired_docs_count || 0), 0);

        document.getElementById('kpiCustActive').textContent = active.length;
        document.getElementById('kpiCustProspects').textContent = prospects.length;
        document.getElementById('kpiCustHold').textContent = hold.length;
        document.getElementById('kpiCustOpenAr').textContent = fmtPhp(openAr);
        document.getElementById('kpiCustTotalClv').textContent = fmtPhp(totalClv);
        document.getElementById('kpiCustDocAlerts').textContent = docAlerts;

        const pillCounts = {
            ALL: nonArchived.length,
            PROSPECT: prospects.length,
            CREDIT_HOLD: hold.length,
            CORPORATE: nonArchived.filter((c) => String(c.customer_tier || 'STANDARD').toUpperCase() === 'CORPORATE').length,
            WHOLESALE: nonArchived.filter((c) => String(c.customer_tier || 'STANDARD').toUpperCase() === 'WHOLESALE').length,
            VIP: nonArchived.filter((c) => String(c.customer_tier || 'STANDARD').toUpperCase() === 'VIP').length,
            DOC_ALERTS: docAlertCustomers.length,
            ARCHIVED: archived.length,
        };
        Object.entries(pillCounts).forEach(([tier, count]) => {
            const badge = document.querySelector(`[data-pill-count="${tier}"]`);
            if (badge) badge.textContent = count;
        });
    }

    function getFilteredCustomers() {
        const q = searchQuery.trim().toLowerCase();
        return customers.filter((c) => {
            const stage = getStage(c);
            if (activeTier === 'ARCHIVED') {
                if (stage !== 'ARCHIVED') return false;
            } else if (stage === 'ARCHIVED') {
                return false;
            } else if (activeTier === 'PROSPECT') {
                if (stage !== 'PROSPECT') return false;
            } else if (activeTier === 'CREDIT_HOLD') {
                if (!c.credit_hold) return false;
            } else if (activeTier === 'DOC_ALERTS') {
                if ((Number(c.expiring_docs_count || 0) + Number(c.expired_docs_count || 0)) <= 0) return false;
            } else if (activeTier !== 'ALL') {
                if (String(c.customer_tier || 'STANDARD').toUpperCase() !== activeTier) return false;
            }

            if (!q) return true;
            return [c.customer_name, c.trade_name, c.customer_code, c.tin, c.contact_person, c.email].some((v) => String(v || '').toLowerCase().includes(q));
        });
    }

    function buildCustomerMetrics(c) {
        const limit = Number(c.credit_limit || 0);
        const exposure = Number(c.total_exposure ?? c.current_balance ?? 0);
        const available = Math.max(0, limit - exposure);
        const util = limit > 0 ? Math.min(100, Math.round((exposure / limit) * 100)) : 0;
        const isOver = limit > 0 && exposure > limit;
        const isWarn = !isOver && util >= 80;
        const initials = String(c.customer_name || 'CU').trim().slice(0, 2).toUpperCase();
        const stage = getStage(c);
        const docAlerts = Number(c.expiring_docs_count || 0) + Number(c.expired_docs_count || 0);

        let stageBadgeClass = 'is-active';
        let stageLabel = c.customer_tier || 'STANDARD';
        if (stage === 'ARCHIVED') {
            stageBadgeClass = 'is-archived';
            stageLabel = 'Archived';
        } else if (c.credit_hold) {
            stageBadgeClass = 'is-hold';
            stageLabel = 'Credit Hold';
        } else if (stage === 'PROSPECT') {
            stageBadgeClass = 'is-prospect';
            stageLabel = 'Prospect';
        }

        const barClass = isOver ? 'is-breach' : (isWarn ? 'is-warn' : '');
        return { limit, exposure, available, util, isOver, isWarn, initials, stage, docAlerts, stageBadgeClass, stageLabel, barClass };
    }

    function renderEmptyDirectoryHtml() {
        return `
            <div class="cm-empty-state">
                <i class="ph ph-users-three"></i>
                <strong>No matching customer accounts found</strong>
                <span>Adjust your search or segment filter, or register a new customer profile.</span>
                <div class="cm-row-actions">
                    <button type="button" class="cm-action-btn" onclick="clearCustomerSearch(); setCustomerTierFilter('ALL')">Reset Filters</button>
                    <button type="button" class="cm-action-btn cm-action-primary" onclick="openCustomerDrawer(null, 'ACTIVE')"><i class="ph ph-plus"></i> New Customer</button>
                </div>
            </div>
        `;
    }

    function renderListTable(filtered) {
        const tbody = document.getElementById('customerListTbody');
        if (!tbody) return;

        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="6">${renderEmptyDirectoryHtml()}</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map((c) => {
            const m = buildCustomerMetrics(c);
            const contactSub = [c.contact_person, c.email].filter(Boolean).map((v) => esc(v)).join(' &bull; ');
            return `
                <tr>
                    <td>
                        <div class="cm-cust-cell">
                            <div class="cm-avatar">${esc(m.initials)}</div>
                            <div class="cm-cust-info">
                                <button type="button" class="cm-cust-name-btn" onclick="openCustomerProfile('${esc(c.customer_id)}')">${esc(c.customer_name)}</button>
                                <div class="cm-cust-sub">
                                    <span class="cm-code-chip">${esc(c.customer_code)}</span>
                                    ${contactSub ? `<span>${contactSub}</span>` : ''}
                                    ${c.parent_customer_name ? `<span class="so-bundle-tag"><i class="ph ph-git-branch"></i> ${esc(c.parent_customer_name)}</span>` : ''}
                                    ${Number(c.subsidiaries_count || 0) > 0 ? `<span class="so-linked-chip">${c.subsidiaries_count} Branches</span>` : ''}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="cm-cust-info">
                            <span class="cm-stage-pill ${m.stageBadgeClass}">${esc(m.stageLabel)}</span>
                            <span class="cm-credit-sub">${esc(c.payment_terms_code || 'NET_30')} &bull; ${esc(c.default_tax_code || 'VAT12_OUT')}</span>
                        </div>
                    </td>
                    <td>
                        <div class="cm-credit-cell">
                            <div class="cm-credit-nums ${m.isOver ? 'is-breach' : ''}">${fmtPhp(m.exposure)} / ${fmtPhp(m.limit)}</div>
                            <div class="cm-credit-bar"><div class="cm-credit-bar-fill ${m.barClass}" style="width:${m.util}%;"></div></div>
                            <div class="cm-credit-sub">${m.limit > 0 ? `${m.util}% used &bull; Avail ${fmtPhp(m.available)}` : 'No credit line'}</div>
                        </div>
                    </td>
                    <td class="cm-th-right cm-tabular">
                        <strong>${fmtPhp(c.clv_amount || 0)}</strong>
                        <div class="cm-credit-sub">${Number(c.orders_count || 0)} orders</div>
                    </td>
                    <td>
                        ${m.docAlerts > 0
                            ? `<span class="sp-schedule-pill is-expired"><i class="ph ph-warning"></i> ${m.docAlerts} Alert${m.docAlerts > 1 ? 's' : ''}</span>`
                            : `<span class="sp-schedule-pill is-active"><i class="ph ph-check"></i> Clear</span>`}
                    </td>
                    <td class="cm-th-right">
                        <div class="cm-row-actions">
                            <button type="button" class="cm-action-btn cm-action-primary" onclick="openCustomerProfile('${esc(c.customer_id)}')">
                                <i class="ph ph-eye"></i> View 360°
                            </button>
                            <a href="${esc(cfg.routes?.salesOrders)}?customer_id=${encodeURIComponent(c.customer_id)}" class="cm-action-btn">
                                <i class="ph ph-plus-circle"></i> Order
                            </a>
                            <button type="button" class="cm-action-btn" onclick="openCustomerDrawer('${esc(c.customer_id)}')">Edit</button>
                            <button type="button" class="cm-action-btn ${c.credit_hold ? 'cm-action-success' : 'cm-action-danger'}" onclick="toggleCustomerCreditHold('${esc(c.customer_id)}')">
                                ${c.credit_hold ? 'Lift' : 'Hold'}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderCardsGrid(filtered) {
        const grid = document.getElementById('customerKanbanGrid');
        if (!grid) return;

        if (!filtered.length) {
            grid.innerHTML = `<div style="grid-column:1/-1;">${renderEmptyDirectoryHtml()}</div>`;
            return;
        }

        grid.innerHTML = filtered.map((c) => {
            const m = buildCustomerMetrics(c);
            return `
                <article class="cm-card">
                    <div class="cm-card-header">
                        <div class="cm-card-identity">
                            <div class="cm-avatar">${esc(m.initials)}</div>
                            <div class="cm-card-title-col">
                                <h3 class="cm-card-name" title="${esc(c.customer_name)}">${esc(c.customer_name)}</h3>
                                <div class="cm-cust-sub">
                                    <span class="cm-code-chip">${esc(c.customer_code)}</span>
                                    <span>${esc(c.payment_terms_code || 'NET_30')}</span>
                                </div>
                            </div>
                        </div>
                        <span class="cm-stage-pill ${m.stageBadgeClass}">${esc(m.stageLabel)}</span>
                    </div>

                    ${(c.parent_customer_name || Number(c.subsidiaries_count || 0) > 0 || m.docAlerts > 0) ? `
                        <div class="cm-card-tags">
                            ${c.parent_customer_name ? `<span class="so-bundle-tag"><i class="ph ph-git-branch"></i> Branch of ${esc(c.parent_customer_name)}</span>` : ''}
                            ${Number(c.subsidiaries_count || 0) > 0 ? `<span class="so-linked-chip">${c.subsidiaries_count} Branches</span>` : ''}
                            ${m.docAlerts > 0 ? `<span class="sp-schedule-pill is-expired"><i class="ph ph-warning"></i> ${m.docAlerts} Doc Alert</span>` : ''}
                        </div>
                    ` : ''}

                    <div class="cm-card-metrics">
                        <div class="cm-card-metric-row">
                            <span class="cm-card-metric-label">CLV / Orders</span>
                            <strong class="cm-tabular">${fmtPhp(c.clv_amount || 0)} (${Number(c.orders_count || 0)})</strong>
                        </div>
                        <div class="cm-card-metric-row">
                            <span class="cm-card-metric-label">Exposure / Limit</span>
                            <strong class="cm-credit-nums ${m.isOver ? 'is-breach' : ''}">${fmtPhp(m.exposure)} / ${fmtPhp(m.limit)}</strong>
                        </div>
                        <div class="cm-credit-bar"><div class="cm-credit-bar-fill ${m.barClass}" style="width:${m.util}%;"></div></div>
                    </div>

                    <div class="cm-card-footer">
                        <button type="button" class="cm-action-btn cm-action-primary" onclick="openCustomerProfile('${esc(c.customer_id)}')">
                            <i class="ph ph-eye"></i> View 360°
                        </button>
                        <a href="${esc(cfg.routes?.salesOrders)}?customer_id=${encodeURIComponent(c.customer_id)}" class="cm-action-btn">
                            <i class="ph ph-plus-circle"></i> Order
                        </a>
                        <button type="button" class="cm-action-btn" onclick="openCustomerDrawer('${esc(c.customer_id)}')">Edit</button>
                        <button type="button" class="cm-action-btn ${c.credit_hold ? 'cm-action-success' : 'cm-action-danger'}" onclick="toggleCustomerCreditHold('${esc(c.customer_id)}')">
                            ${c.credit_hold ? 'Lift Hold' : 'Hold'}
                        </button>
                    </div>
                </article>
            `;
        }).join('');
    }

    function renderGrid() {
        renderDirectoryKpis();
        const filtered = getFilteredCustomers();
        const countEl = document.getElementById('custResultCount');
        if (countEl) {
            countEl.textContent = `${filtered.length} of ${customers.length} account${customers.length === 1 ? '' : 's'}`;
        }

        const listWrap = document.getElementById('customerListTableWrap');
        const cardsGrid = document.getElementById('customerKanbanGrid');
        listWrap?.classList.toggle('cm-hidden', viewMode !== 'list');
        cardsGrid?.classList.toggle('cm-hidden', viewMode !== 'cards');

        if (viewMode === 'cards') {
            renderCardsGrid(filtered);
        } else {
            renderListTable(filtered);
        }
    }

    window.setCustomerViewMode = (mode) => {
        viewMode = mode === 'cards' ? 'cards' : 'list';
        localStorage.setItem('rms_customer_directory_view', viewMode);
        const listBtn = document.getElementById('custViewToggleList');
        const cardsBtn = document.getElementById('custViewToggleCards');
        listBtn?.classList.toggle('is-active', viewMode === 'list');
        listBtn?.setAttribute('aria-pressed', viewMode === 'list' ? 'true' : 'false');
        cardsBtn?.classList.toggle('is-active', viewMode === 'cards');
        cardsBtn?.setAttribute('aria-pressed', viewMode === 'cards' ? 'true' : 'false');
        renderGrid();
    };

    window.filterCustomerDirectory = (val) => {
        searchQuery = val || '';
        document.getElementById('custSearchClearBtn')?.classList.toggle('cm-hidden', !searchQuery.trim());
        renderGrid();
    };

    window.clearCustomerSearch = () => {
        const input = document.getElementById('customerSearchInput');
        if (input) input.value = '';
        window.filterCustomerDirectory('');
        input?.focus();
    };

    window.setCustomerTierFilter = (tier, btn = null) => {
        activeTier = tier || 'ALL';
        document.querySelectorAll('#customerTierPills .cm-filter-pill').forEach((p) => {
            p.classList.toggle('active', p === btn || p.dataset.tier === activeTier);
        });
        document.querySelectorAll('.cm-kpi-card[data-kpi-tier]').forEach((card) => {
            card.classList.toggle('is-active-filter', card.dataset.kpiTier === activeTier);
        });
        renderGrid();
    };

    // =========================================================================
    // SLIDE-OVER DRAWERS (REGISTER/EDIT & SOA) + LIVE DUPLICATE CHECK
    // =========================================================================
    window.closeCustomerDrawer = () => {
        document.getElementById('custDrawerBackdrop')?.classList.remove('is-open');
        document.getElementById('customerFormDrawer')?.classList.remove('is-open');
        document.getElementById('customerSoaDrawer')?.classList.remove('is-open');
    };

    function populateParentSelect(excludeId = null) {
        const sel = document.getElementById('custParentInput');
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Standalone / HQ --</option>' +
            customers.filter((c) => c.customer_id !== excludeId && (c.account_stage || 'ACTIVE') !== 'ARCHIVED')
                .map((c) => `<option value="${esc(c.customer_id)}">[${esc(c.customer_code)}] ${esc(c.customer_name)}</option>`)
                .join('');
    }

    function setNormalizedSelectVal(selId, rawVal) {
        const sel = document.getElementById(selId);
        if (!sel || !rawVal) return;
        const raw = String(rawVal).toUpperCase().trim();
        const compact = raw.replace(/[_\-\s]/g, '');
        const match = [...sel.options].find((o) => {
            const ov = o.value.toUpperCase();
            return ov === raw || ov.replace(/[_\-\s]/g, '') === compact;
        });
        if (match) sel.value = match.value;
    }

    function renderDuplicateWarning(dups) {
        const alertEl = document.getElementById('custDuplicateAlert');
        const allowWrap = document.getElementById('custAllowDupWrap');
        if (!dups || !dups.length) {
            if (alertEl) alertEl.style.display = 'none';
            if (allowWrap) allowWrap.style.display = 'none';
            return;
        }
        if (alertEl) {
            alertEl.style.display = 'flex';
            alertEl.innerHTML = `
                <div><strong><i class="ph ph-warning-octagon"></i> Possible Duplicate Customer Detected:</strong></div>
                <ul style="margin:4px 0 0 16px;padding:0;">
                    ${dups.map((d) => `<li><strong>[${esc(d.customer_code)}] ${esc(d.customer_name)}</strong> (${esc((d.match_reasons || []).join(', '))}) <button type="button" class="rfq-btn rfq-btn-outline" style="padding:1px 6px;font-size:10px;margin-left:6px;" onclick="closeCustomerDrawer();openCustomerProfile('${esc(d.customer_id)}')">Open Profile</button></li>`).join('')}
                </ul>
            `;
        }
        if (allowWrap) allowWrap.style.display = 'block';
    }

    window.debounceDuplicateCheck = () => {
        clearTimeout(dupTimer);
        dupTimer = setTimeout(async () => {
            const name = document.getElementById('custNameInput')?.value?.trim();
            const tin = document.getElementById('custTinInput')?.value?.trim();
            const email = document.getElementById('custEmailInput')?.value?.trim();
            const excludeId = document.getElementById('custIdInput')?.value || null;
            if (!name && !tin && !email) return renderDuplicateWarning([]);
            try {
                const res = await apiCall(cfg.routes.checkDuplicates, 'POST', {
                    customer_name: name, tin, email, exclude_customer_id: excludeId
                });
                renderDuplicateWarning(res.duplicates || []);
            } catch (e) {}
        }, 350);
    };

    window.openCustomerDrawer = (customerId = null, defaultStage = 'ACTIVE') => {
        const c = customerId ? customers.find((x) => x.customer_id === customerId) : null;
        populateParentSelect(c?.customer_id || null);

        document.getElementById('customerDrawerTitle').innerHTML = c
            ? `<i class="ph ph-pencil-simple"></i> Edit Customer: ${esc(c.customer_code)}`
            : `<i class="ph ph-user-plus"></i> ${defaultStage === 'PROSPECT' ? 'Register Prospect / Lead' : 'Register Customer'}`;
        document.getElementById('custIdInput').value = c?.customer_id || '';
        document.getElementById('custNameInput').value = c?.customer_name || '';
        document.getElementById('custStageInput').value = c?.account_stage || defaultStage || 'ACTIVE';
        document.getElementById('custTinInput').value = c?.tin || '';
        document.getElementById('custEmailInput').value = c?.email || '';
        setNormalizedSelectVal('custTermsInput', c?.payment_terms_code || 'NET30');
        document.getElementById('custCreditLimitInput').value = c ? Number(c.credit_limit) : (defaultStage === 'PROSPECT' ? 0 : 50000);
        document.getElementById('custCodeInput').value = c?.customer_code || '';
        document.getElementById('custParentInput').value = c?.parent_customer_id || '';
        document.getElementById('custTierInput').value = c?.customer_tier || 'STANDARD';
        setNormalizedSelectVal('custTaxInput', c?.default_tax_code || 'VAT12_OUT');
        document.getElementById('custContactInput').value = c?.contact_person || '';
        document.getElementById('custPhoneInput').value = c?.phone || '';
        document.getElementById('custSalesRepInput').value = c?.sales_rep_name || '';
        document.getElementById('custTaxCertInput').value = c?.tax_exempt_cert_no || '';
        document.getElementById('custAddressInput').value = c?.address || c?.shipping_address || '';
        document.getElementById('custIndustryInput').value = c?.industry || '';
        document.getElementById('custGraceInput').value = c?.credit_grace_days ?? 15;
        document.getElementById('custNotesInput').value = c?.notes || '';
        document.getElementById('custAllowDupCheck').checked = false;
        renderDuplicateWarning([]);

        document.getElementById('custDrawerBackdrop')?.classList.add('is-open');
        document.getElementById('customerFormDrawer')?.classList.add('is-open');
    };
    window.openCustomerModal = window.openCustomerDrawer;
    window.closeCustomerModal = window.closeCustomerDrawer;

    window.submitCustomerForm = async () => {
        const name = document.getElementById('custNameInput')?.value?.trim();
        if (!name) return toast('error', 'Business / Legal Name is required.');

        const payload = {
            customer_id: document.getElementById('custIdInput')?.value || null,
            customer_code: document.getElementById('custCodeInput')?.value?.trim() || null,
            customer_name: name,
            account_stage: document.getElementById('custStageInput')?.value || 'ACTIVE',
            parent_customer_id: document.getElementById('custParentInput')?.value || null,
            payment_terms_code: document.getElementById('custTermsInput')?.value || 'NET_30',
            credit_limit: parseFloat(document.getElementById('custCreditLimitInput')?.value || 0),
            tin: document.getElementById('custTinInput')?.value?.trim() || null,
            contact_person: document.getElementById('custContactInput')?.value?.trim() || null,
            email: document.getElementById('custEmailInput')?.value?.trim() || null,
            phone: document.getElementById('custPhoneInput')?.value?.trim() || null,
            customer_tier: document.getElementById('custTierInput')?.value || 'STANDARD',
            default_tax_code: document.getElementById('custTaxInput')?.value || 'VAT12_OUT',
            sales_rep_name: document.getElementById('custSalesRepInput')?.value || null,
            tax_exempt_cert_no: document.getElementById('custTaxCertInput')?.value?.trim() || null,
            address: document.getElementById('custAddressInput')?.value?.trim() || null,
            shipping_address: document.getElementById('custAddressInput')?.value?.trim() || null,
            industry: document.getElementById('custIndustryInput')?.value?.trim() || null,
            credit_grace_days: parseInt(document.getElementById('custGraceInput')?.value || '15', 10),
            notes: document.getElementById('custNotesInput')?.value?.trim() || null,
            allow_duplicate: !!document.getElementById('custAllowDupCheck')?.checked,
        };

        try {
            const json = await apiCall(cfg.routes.customersSave, 'POST', payload);
            const idx = customers.findIndex((c) => c.customer_id === json.data.customer_id);
            if (idx >= 0) customers[idx] = json.data;
            else customers.unshift(json.data);
            window.closeCustomerDrawer();
            renderGrid();
            if (activeCustomerId === json.data.customer_id) window.openCustomerProfile(activeCustomerId);
            toast('success', json.message);
        } catch (err) {
            if (err.duplicates) renderDuplicateWarning(err.duplicates);
            toast('error', err.message);
        }
    };

    // =========================================================================
    // CUSTOMER 360° PROFILE WORKSPACE & 5 TABS
    // =========================================================================
    window.switchCustomerTab = (tab) => {
        const map = {
            'tab-overview': { btn: 'custTabBtnOverview', pane: 'custPaneOverview' },
            'tab-pipeline': { btn: 'custTabBtnPipeline', pane: 'custPanePipeline' },
            'tab-ar': { btn: 'custTabBtnAr', pane: 'custPaneAr' },
            'tab-compliance': { btn: 'custTabBtnCompliance', pane: 'custPaneCompliance' },
            'tab-crm': { btn: 'custTabBtnCrm', pane: 'custPaneCrm' },
        };
        Object.entries(map).forEach(([key, ids]) => {
            const active = key === tab;
            document.getElementById(ids.btn)?.classList.toggle('active', active);
            const pane = document.getElementById(ids.pane);
            if (pane) {
                pane.classList.toggle('is-active', active);
                pane.style.display = active ? 'flex' : 'none';
            }
        });
        if (tab === 'tab-pipeline') hydrateUoms();
    };

    window.closeCustomerProfile = () => {
        activeCustomerId = null;
        activeProfile = null;
        const prof = document.getElementById('customerProfileWrapper');
        const dir = document.getElementById('customerDirectorySection');
        if (prof) { prof.style.display = 'none'; prof.setAttribute('aria-hidden', 'true'); }
        if (dir) { dir.style.display = 'flex'; dir.setAttribute('aria-hidden', 'false'); }
        renderGrid();
    };

    window.cycleCustomer = (delta) => {
        const list = getFilteredCustomers();
        if (!list.length) return;
        const idx = list.findIndex((c) => c.customer_id === activeCustomerId);
        const nextIdx = (idx + delta + list.length) % list.length;
        window.openCustomerProfile(list[nextIdx].customer_id);
    };

    window.openCustomerProfile = async (customerId) => {
        try {
            const url = String(cfg.routes.profile).replace('__ID__', encodeURIComponent(customerId));
            const res = await apiCall(url, 'GET');
            activeCustomerId = customerId;
            activeProfile = res.data;
            const prof = document.getElementById('customerProfileWrapper');
            const dir = document.getElementById('customerDirectorySection');
            if (dir) { dir.style.display = 'none'; dir.setAttribute('aria-hidden', 'true'); }
            if (prof) { prof.style.display = 'flex'; prof.setAttribute('aria-hidden', 'false'); }
            renderProfileView();
            hydrateUoms();
        } catch (err) { toast('error', err.message); }
    };

    function renderProfileView() {
        const p = activeProfile;
        if (!p || !p.customer) return;
        const c = p.customer;
        const an = p.analytics || {};
        const cr = p.credit || {};
        const stage = String(c.account_stage || 'ACTIVE').toUpperCase();

        const list = getFilteredCustomers();
        const pos = list.findIndex((x) => x.customer_id === c.customer_id);
        document.getElementById('custProfileCounter').textContent = `Customer ${pos >= 0 ? pos + 1 : 1} of ${Math.max(1, list.length)}`;
        document.getElementById('custProfileTierBadge').textContent = c.customer_tier || 'STANDARD';
        document.getElementById('custProfileBreadcrumbName').textContent = c.customer_name;

        document.getElementById('custBannerAvatar').innerHTML = `<span>${esc(String(c.customer_name || 'CU').slice(0, 2).toUpperCase())}</span>`;
        document.getElementById('custBannerName').textContent = c.customer_name;

        const stagePill = document.getElementById('custBannerStagePill');
        stagePill.textContent = c.credit_hold ? 'CREDIT HOLD' : stage;
        stagePill.className = `status-pill ${c.credit_hold ? 'cancelled' : (stage === 'PROSPECT' ? 'quote_sent' : 'approved')}`;

        document.getElementById('custBannerMetaPills').innerHTML = `
            <span class="vendor-meta-pill"><i class="ph ph-barcode"></i> ${esc(c.customer_code)}</span>
            <span class="vendor-meta-pill"><i class="ph ph-credit-card"></i> ${esc(c.payment_terms_code || 'NET_30')}</span>
            <span class="vendor-meta-pill"><i class="ph ph-percent"></i> ${esc(c.default_tax_code || 'VAT12_OUT')}</span>
            ${c.parent_customer ? `<span class="so-bundle-tag" style="cursor:pointer;" onclick="openCustomerProfile('${esc(c.parent_customer.customer_id)}')"><i class="ph ph-git-branch"></i> Subsidiary of ${esc(c.parent_customer.customer_name)}</span>` : ''}
        `;

        document.getElementById('custBannerContactStrip').innerHTML = `
            ${c.tin ? `<span class="vendor-contact-link-pill"><i class="ph ph-identification-badge"></i> TIN: ${esc(c.tin)}</span>` : ''}
            ${c.email ? `<span class="vendor-contact-link-pill"><i class="ph ph-envelope"></i> ${esc(c.email)}</span>` : ''}
            ${c.phone ? `<span class="vendor-contact-link-pill"><i class="ph ph-phone"></i> ${esc(c.phone)}</span>` : ''}
            ${c.sales_rep_name ? `<span class="vendor-contact-link-pill"><i class="ph ph-user-check"></i> Rep: ${esc(c.sales_rep_name)}</span>` : ''}
        `;

        document.getElementById('custKpiClv').textContent = fmtPhp(an.clv_amount || 0);
        document.getElementById('custKpiFreq').textContent = `${an.orders_count || 0} (${an.orders_per_month || 0}/mo)`;
        document.getElementById('custKpiMargin').textContent = `${Number(an.gross_margin_pct || 0).toFixed(1)}%`;
        document.getElementById('custKpiDso').textContent = `${an.dso_days || 0}d`;
        document.getElementById('custKpiAvailCredit').textContent = `${fmtPhp(cr.available_credit || 0)} / ${fmtPhp(c.credit_limit || 0)}`;

        document.getElementById('custBtnConvertProspect')?.classList.toggle('cm-hidden', stage !== 'PROSPECT');
        document.getElementById('custBtnLaunchOrder').href = `${cfg.routes.salesOrders}?customer_id=${encodeURIComponent(c.customer_id)}`;
        document.getElementById('custBtnLaunchQuote').href = `${cfg.routes.salesQuotes}?customer_id=${encodeURIComponent(c.customer_id)}`;
        document.getElementById('custBtnToggleHold').textContent = c.credit_hold ? 'Lift Hold' : 'Hold';
        document.getElementById('custBtnToggleArchive').textContent = stage === 'ARCHIVED' ? 'Restore' : 'Archive';

        // Tab 1: Overview, Hierarchy, Contacts, Addresses
        document.getElementById('custOverviewDetailsGrid').innerHTML = `
            <div><span style="color:#64748b;">Legal / Trade Name:</span><div><strong>${esc(c.customer_name)}</strong> (${esc(c.trade_name || c.customer_name)})</div></div>
            <div><span style="color:#64748b;">Industry:</span><div><strong>${esc(c.industry || '—')}</strong></div></div>
            <div><span style="color:#64748b;">Primary Billing Address:</span><div>${esc(c.address || '—')}</div></div>
            <div><span style="color:#64748b;">Primary Shipping Address:</span><div>${esc(c.shipping_address || c.address || '—')}</div></div>
            <div><span style="color:#64748b;">Credit Limit / Grace Days:</span><div><strong>${fmtPhp(c.credit_limit)}</strong> &bull; ${c.credit_grace_days ?? 15} days</div></div>
            <div><span style="color:#64748b;">Notes:</span><div>${esc(c.notes || '—')}</div></div>
        `;

        const subs = c.subsidiaries || [];
        document.getElementById('custHierarchySection').innerHTML = `
            <div style="font-size:12px;font-weight:700;color:#334155;margin-bottom:6px;">Account Hierarchy</div>
            ${c.parent_customer ? `<div style="font-size:12px;margin-bottom:4px;">Parent HQ: <a href="#" onclick="event.preventDefault();openCustomerProfile('${esc(c.parent_customer.customer_id)}')" style="font-weight:700;color:#7c3aed;">[${esc(c.parent_customer.customer_code)}] ${esc(c.parent_customer.customer_name)}</a></div>` : ''}
            ${subs.length ? `<div style="display:flex;gap:6px;flex-wrap:wrap;">${subs.map((s) => `<button type="button" class="so-linked-chip" onclick="openCustomerProfile('${esc(s.customer_id)}')">${esc(s.customer_code)} — ${esc(s.customer_name)}</button>`).join('')}</div>` : `<div style="font-size:11.5px;color:#94a3b8;">No subsidiary branches linked to this account.</div>`}
        `;

        const contacts = c.contacts || [];
        document.getElementById('custContactsTbody').innerHTML = contacts.length ? contacts.map((ct) => `
            <tr>
                <td><strong>${esc(ct.full_name)}</strong><div style="font-size:11px;color:#64748b;">${esc(ct.role_title || ct.department || '')}</div></td>
                <td style="font-size:11.5px;">${esc(ct.email || '')}<div style="color:#64748b;">${esc(ct.phone || '')}</div></td>
                <td>${ct.is_primary ? '<span class="status-pill approved">Primary</span>' : ''}</td>
                <td><button type="button" class="rfq-btn rfq-btn-danger" style="padding:3px 6px;" onclick="deleteCustomerContact('${esc(ct.contact_id)}')"><i class="ph ph-trash"></i></button></td>
            </tr>
        `).join('') : `<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:14px;">No contacts added yet.</td></tr>`;

        const addrs = c.addresses || [];
        document.getElementById('custAddressesTbody').innerHTML = addrs.length ? addrs.map((ad) => `
            <tr>
                <td><span class="rfq-label-badge">${esc(ad.address_type)}</span>${ad.is_default ? ' <span class="status-pill approved" style="font-size:9.5px;">Default</span>' : ''}</td>
                <td><strong>${esc(ad.label)}</strong></td>
                <td>${esc(ad.address_line)}</td>
                <td>${esc(ad.receiving_contact || '—')}</td>
                <td>${esc(ad.delivery_window || '—')}</td>
                <td><button type="button" class="rfq-btn rfq-btn-danger" style="padding:3px 6px;" onclick="deleteCustomerAddress('${esc(ad.address_id)}')"><i class="ph ph-trash"></i></button></td>
            </tr>
        `).join('') : `<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:14px;">No branch addresses added yet.</td></tr>`;

        // Tab 2: Price List & O2C Pipeline
        const pls = c.price_lists || [];
        document.getElementById('custPriceListTbody').innerHTML = pls.length ? pls.map((pl) => `
            <tr>
                <td><strong>${esc(pl.item_name)}</strong><div style="font-size:11px;color:#64748b;font-family:monospace;">${esc(pl.internal_sku || '')}</div></td>
                <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(pl.customer_sku || '—')}</td>
                <td>${esc(pl.uom)}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;">${fmtPhp(pl.standard_price)}</td>
                <td style="text-align:right;font-weight:700;color:#047857;font-variant-numeric:tabular-nums;">${fmtPhp(pl.contract_price)}</td>
                <td>${Number(pl.discount_pct || 0).toFixed(1)}% off</td>
                <td style="font-size:11px;color:#64748b;">${fmtDate(pl.valid_from)} → ${fmtDate(pl.valid_until)}</td>
                <td><button type="button" class="rfq-btn rfq-btn-danger" style="padding:3px 6px;" onclick="deleteCustomerPriceList('${esc(pl.price_list_id)}')"><i class="ph ph-trash"></i></button></td>
            </tr>
        `).join('') : `<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:14px;">No negotiated contract rates configured yet.</td></tr>`;

        const pipeDocs = [...(p.pipeline?.quotes || []), ...(p.pipeline?.active_orders || []), ...(p.pipeline?.completed_orders || [])];
        document.getElementById('custPipelineOrdersTbody').innerHTML = pipeDocs.length ? pipeDocs.slice(0, 20).map((o) => `
            <tr>
                <td><a href="${esc(cfg.routes.salesOrders)}?order_id=${encodeURIComponent(o.sales_order_id)}" style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(o.order_number)}</a></td>
                <td>${fmtDate(o.order_date)}</td>
                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmtPhp(o.gross_total)}</td>
                <td><span class="status-pill approved">${esc(o.status)}</span></td>
            </tr>
        `).join('') : `<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:14px;">No quotes or orders yet.</td></tr>`;

        const dels = p.pipeline?.deliveries || [];
        document.getElementById('custPipelineDeliveriesTbody').innerHTML = dels.length ? dels.map((d) => `
            <tr>
                <td style="font-family:monospace;font-weight:700;color:#0e7490;">${esc(d.order_number)}</td>
                <td>${esc(d.sales_order_number || '—')}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;">${fmtPhp(d.total_cost_value)}</td>
                <td><span class="status-pill approved">${esc(d.status)}</span></td>
            </tr>
        `).join('') : `<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:14px;">No deliveries dispatched yet.</td></tr>`;

        // Tab 3: AR Aging Buckets & Invoices
        const b = p.ar_aging?.buckets || {};
        document.getElementById('custAgingBucketsGrid').innerHTML = [
            ['Current', b.current || 0, '#047857'],
            ['1–30 Days', b.days_1_30 || 0, '#0284c7'],
            ['31–60 Days', b.days_31_60 || 0, '#d97706'],
            ['61–90 Days', b.days_61_90 || 0, '#ea580c'],
            ['90+ Overdue', b.days_90_plus || 0, '#dc2626'],
        ].map(([lbl, val, col]) => `
            <div class="rfq-kpi-card">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">${lbl}</span></div>
                <div class="rfq-kpi-val" style="color:${col};font-variant-numeric:tabular-nums;">${fmtPhp(val)}</div>
                <div class="rfq-kpi-sub">Total Open: ${fmtPhp(b.total_open_ar || 0)}</div>
            </div>
        `).join('');

        const invs = p.ar_aging?.invoices || [];
        document.getElementById('custInvoicesTbody').innerHTML = invs.length ? invs.map((i) => `
            <tr>
                <td style="font-family:monospace;font-weight:700;">${esc(i.invoice_number)}</td>
                <td>${fmtDate(i.due_date)}</td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;">${fmtPhp(i.total_amount)}</td>
                <td style="text-align:right;font-weight:700;color:${Number(i.balance_due) > 0 ? '#b45309' : '#047857'};font-variant-numeric:tabular-nums;">${fmtPhp(i.balance_due)}</td>
                <td><span class="status-pill approved">${esc(i.status)}</span></td>
            </tr>
        `).join('') : `<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:14px;">No invoices yet.</td></tr>`;

        const rcps = (p.ar_aging?.receipts || []).map((r) => ({ ref: r.receipt_number, dt: r.receipt_date, type: r.payment_method, amt: r.gross_amount, st: r.status }));
        const mems = (p.ar_aging?.memos || []).map((m) => ({ ref: m.memo_number, dt: m.memo_date, type: m.memo_type + ' MEMO', amt: m.total_amount, st: m.status }));
        const combinedAr = [...rcps, ...mems];
        document.getElementById('custReceiptsMemosTbody').innerHTML = combinedAr.length ? combinedAr.map((x) => `
            <tr>
                <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(x.ref)}</td>
                <td>${fmtDate(x.dt)}</td>
                <td><span class="rfq-label-badge">${esc(x.type)}</span></td>
                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmtPhp(x.amt)}</td>
                <td><span class="status-pill approved">${esc(x.st)}</span></td>
            </tr>
        `).join('') : `<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:14px;">No receipts or memos yet.</td></tr>`;

        // Tab 4: Tax & Compliance Vault
        document.getElementById('custTaxSummaryStrip').innerHTML = `
            <div>Default Output Tax: <strong>${esc(c.default_tax_code || 'VAT12_OUT')}</strong> &bull; TIN: <strong>${esc(c.tin || 'Unassigned')}</strong></div>
            <div>Exemption Cert #: <strong>${esc(c.tax_exempt_cert_no || 'None')}</strong></div>
        `;
        const docs = c.documents || [];
        document.getElementById('custDocsTbody').innerHTML = docs.length ? docs.map((d) => {
            const st = d.expiry_status || 'NO_EXPIRY';
            const cls = st === 'EXPIRED' ? 'is-expired' : (st === 'EXPIRING_SOON' ? 'is-scheduled' : (st === 'VALID' ? 'is-active' : 'is-always'));
            const lbl = st === 'EXPIRED' ? `Expired (${fmtDate(d.expiry_date)})` : (st === 'EXPIRING_SOON' ? `Expires in ${d.days_to_expiry}d` : (st === 'VALID' ? `Valid until ${fmtDate(d.expiry_date)}` : 'No Expiry'));
            return `
                <tr>
                    <td><span class="rfq-label-badge">${esc(d.doc_type)}</span></td>
                    <td><strong>${esc(d.document_title)}</strong>${d.reference_no ? `<div style="font-size:11px;color:#64748b;">Ref: ${esc(d.reference_no)}</div>` : ''}</td>
                    <td>${fmtDate(d.issue_date)}</td>
                    <td><span class="sp-schedule-pill ${cls}">${esc(lbl)}</span></td>
                    <td style="font-size:11.5px;">${esc(d.uploaded_by || 'System')}${d.file_path ? `<div><a href="/storage/${esc(d.file_path)}" target="_blank" style="color:#2563eb;font-weight:700;">${esc(d.file_name || 'Download')}</a></div>` : ''}</td>
                    <td><button type="button" class="rfq-btn rfq-btn-danger" style="padding:3px 6px;" onclick="deleteCustomerDocument('${esc(d.document_id)}')"><i class="ph ph-trash"></i></button></td>
                </tr>
            `;
        }).join('') : `<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:14px;">No compliance documents uploaded yet.</td></tr>`;

        // Tab 5: CRM Timeline
        const acts = c.activities || [];
        document.getElementById('custActivitiesTbody').innerHTML = acts.length ? acts.map((a) => `
            <tr>
                <td style="font-family:monospace;font-size:11px;white-space:nowrap;">${esc(String(a.acted_at || '').replace('T', ' ').slice(0, 16))}</td>
                <td><span class="sp-audit-pill ${a.activity_type === 'SYSTEM' ? 'is-updated' : 'is-applied'}">${esc(a.activity_type)}</span></td>
                <td><strong>${esc(a.subject)}</strong>${a.description ? `<div style="font-size:11.5px;color:#475569;">${esc(a.description)}</div>` : ''}</td>
                <td>${fmtDate(a.follow_up_date)}</td>
                <td><strong>${esc(a.acted_by)}</strong></td>
            </tr>
        `).join('') : `<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:14px;">No CRM activities logged yet.</td></tr>`;
    }

    // =========================================================================
    // PROFILE ACTIONS & SUB-RESOURCE CRUD
    // =========================================================================
    window.editCurrentCustomer = () => { if (activeCustomerId) window.openCustomerDrawer(activeCustomerId); };

    window.convertCurrentProspect = async () => {
        if (!activeCustomerId) return;
        try {
            const res = await apiCall(String(cfg.routes.convert).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', {});
            const idx = customers.findIndex((c) => c.customer_id === res.data.customer_id);
            if (idx >= 0) customers[idx] = res.data;
            await window.openCustomerProfile(activeCustomerId);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.toggleCurrentCustomerArchive = async () => {
        if (!activeCustomerId) return;
        try {
            const res = await apiCall(String(cfg.routes.archive).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', {});
            const idx = customers.findIndex((c) => c.customer_id === res.data.customer_id);
            if (idx >= 0) customers[idx] = res.data;
            await window.openCustomerProfile(activeCustomerId);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.toggleCurrentCustomerHold = async () => {
        if (activeCustomerId) await window.toggleCustomerCreditHold(activeCustomerId);
    };

    window.toggleCustomerCreditHold = async (customerId) => {
        try {
            const json = await apiCall(String(cfg.routes.toggleHold).replace('__ID__', encodeURIComponent(customerId)), 'POST', {});
            const idx = customers.findIndex((c) => c.customer_id === customerId);
            if (idx >= 0) customers[idx] = json.data;
            renderGrid();
            if (activeCustomerId === customerId) await window.openCustomerProfile(customerId);
            toast('success', json.message);
        } catch (err) { toast('error', err.message); }
    };

    window.saveCustomerContact = async () => {
        const fullName = document.getElementById('cContactName')?.value?.trim();
        if (!fullName) return toast('error', 'Contact full name is required.');
        try {
            const res = await apiCall(String(cfg.routes.saveContact).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', {
                full_name: fullName,
                role_title: document.getElementById('cContactRole')?.value?.trim() || null,
                phone: document.getElementById('cContactPhone')?.value?.trim() || null,
                email: document.getElementById('cContactEmail')?.value?.trim() || null,
                department: document.getElementById('cContactDept')?.value || 'PURCHASING',
            });
            activeProfile = res.data;
            ['cContactName', 'cContactRole', 'cContactPhone', 'cContactEmail'].forEach((id) => { document.getElementById(id).value = ''; });
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.deleteCustomerContact = async (contactId) => {
        try {
            const url = String(cfg.routes.deleteContact).replace('__ID__', encodeURIComponent(activeCustomerId)).replace('__SUBID__', encodeURIComponent(contactId));
            const res = await apiCall(url, 'DELETE');
            activeProfile = res.data;
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.saveCustomerAddress = async () => {
        const label = document.getElementById('cAddrLabel')?.value?.trim();
        const line = document.getElementById('cAddrLine')?.value?.trim();
        if (!label || !line) return toast('error', 'Branch label and street address are required.');
        try {
            const res = await apiCall(String(cfg.routes.saveAddress).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', {
                address_type: document.getElementById('cAddrType')?.value || 'SHIPPING',
                label,
                address_line: line,
                receiving_contact: document.getElementById('cAddrRecContact')?.value?.trim() || null,
                delivery_window: document.getElementById('cAddrWindow')?.value?.trim() || null,
            });
            activeProfile = res.data;
            ['cAddrLabel', 'cAddrLine', 'cAddrRecContact', 'cAddrWindow'].forEach((id) => { document.getElementById(id).value = ''; });
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.deleteCustomerAddress = async (addrId) => {
        try {
            const url = String(cfg.routes.deleteAddress).replace('__ID__', encodeURIComponent(activeCustomerId)).replace('__SUBID__', encodeURIComponent(addrId));
            const res = await apiCall(url, 'DELETE');
            activeProfile = res.data;
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.onPriceListItemChange = (id) => {
        const it = items.find((i) => String(i.id) === String(id));
        if (!it) return;
        document.getElementById('cPlPrice').value = Number(it.selling_price || 0);
        const uomSel = document.getElementById('cPlUomSelect');
        if (uomSel) {
            uomSel.dataset.uomCurrent = it.uom || 'PCS';
            hydrateUoms();
        }
    };

    window.saveCustomerPriceList = async () => {
        const itemId = document.getElementById('cPlItemSelect')?.value;
        const price = parseFloat(document.getElementById('cPlPrice')?.value || '0');
        if (!itemId) return toast('error', 'Select an Item Master SKU first.');
        try {
            const res = await apiCall(String(cfg.routes.savePriceList).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', {
                inventory_item_id: Number(itemId),
                customer_sku: document.getElementById('cPlCustomerSku')?.value?.trim() || null,
                uom: document.getElementById('cPlUomSelect')?.value || 'PCS',
                contract_price: price,
                valid_from: document.getElementById('cPlValidFrom')?.value || null,
                valid_until: document.getElementById('cPlValidUntil')?.value || null,
            });
            activeProfile = res.data;
            document.getElementById('cPlCustomerSku').value = '';
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.deleteCustomerPriceList = async (plId) => {
        try {
            const url = String(cfg.routes.deletePriceList).replace('__ID__', encodeURIComponent(activeCustomerId)).replace('__SUBID__', encodeURIComponent(plId));
            const res = await apiCall(url, 'DELETE');
            activeProfile = res.data;
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.saveCustomerDocument = async () => {
        const title = document.getElementById('cDocTitle')?.value?.trim();
        if (!title) return toast('error', 'Document Title is required.');
        const fd = new FormData();
        fd.append('doc_type', document.getElementById('cDocType')?.value || 'CONTRACT');
        fd.append('document_title', title);
        fd.append('reference_no', document.getElementById('cDocRef')?.value?.trim() || '');
        fd.append('issue_date', document.getElementById('cDocIssue')?.value || '');
        fd.append('expiry_date', document.getElementById('cDocExpiry')?.value || '');
        const fileEl = document.getElementById('cDocFile');
        if (fileEl?.files?.[0]) fd.append('document_file', fileEl.files[0]);

        try {
            const res = await apiCall(String(cfg.routes.saveDocument).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', fd);
            activeProfile = res.data;
            ['cDocTitle', 'cDocRef', 'cDocIssue', 'cDocExpiry', 'cDocFile'].forEach((id) => { document.getElementById(id).value = ''; });
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.deleteCustomerDocument = async (docId) => {
        try {
            const url = String(cfg.routes.deleteDocument).replace('__ID__', encodeURIComponent(activeCustomerId)).replace('__SUBID__', encodeURIComponent(docId));
            const res = await apiCall(url, 'DELETE');
            activeProfile = res.data;
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.saveCustomerActivity = async () => {
        const subj = document.getElementById('cActSubject')?.value?.trim();
        if (!subj) return toast('error', 'Activity Subject is required.');
        try {
            const res = await apiCall(String(cfg.routes.saveActivity).replace('__ID__', encodeURIComponent(activeCustomerId)), 'POST', {
                activity_type: document.getElementById('cActType')?.value || 'NOTE',
                subject: subj,
                description: document.getElementById('cActDesc')?.value?.trim() || null,
                follow_up_date: document.getElementById('cActFollowUp')?.value || null,
            });
            activeProfile = res.data;
            ['cActSubject', 'cActDesc', 'cActFollowUp'].forEach((id) => { document.getElementById(id).value = ''; });
            renderProfileView();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.openSoaDrawer = () => {
        if (!activeProfile) return;
        const c = activeProfile.customer;
        const rows = activeProfile.ar_aging?.soa_rows || [];
        const b = activeProfile.ar_aging?.buckets || {};
        document.getElementById('customerSoaBody').innerHTML = `
            <div style="border-bottom:2px solid #7c3aed;padding-bottom:10px;display:flex;justify-content:space-between;">
                <div>
                    <div style="font-weight:800;font-size:15px;">STATEMENT OF ACCOUNT</div>
                    <div><strong>${esc(c.customer_name)}</strong> (${esc(c.customer_code)})</div>
                    <div style="font-size:11.5px;color:#64748b;">TIN: ${esc(c.tin || 'N/A')} &bull; ${esc(c.address || '')}</div>
                </div>
                <div style="text-align:right;font-size:11.5px;">
                    <div>As of: <strong>${new Date().toISOString().slice(0, 10)}</strong></div>
                    <div>Total Open AR: <strong style="color:#7c3aed;font-size:14px;">${fmtPhp(b.total_open_ar || 0)}</strong></div>
                </div>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead><tr><th>Date</th><th>Ref #</th><th>Description</th><th style="text-align:right;">Charges (DR)</th><th style="text-align:right;">Payments (CR)</th><th style="text-align:right;">Balance</th></tr></thead>
                    <tbody>
                        ${rows.length ? rows.map((r) => `
                            <tr>
                                <td style="font-family:monospace;">${esc(r.date)}</td>
                                <td style="font-family:monospace;font-weight:700;">${esc(r.ref)}</td>
                                <td>${esc(r.description)}</td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums;">${r.debit > 0 ? fmtPhp(r.debit) : '—'}</td>
                                <td style="text-align:right;color:#047857;font-variant-numeric:tabular-nums;">${r.credit > 0 ? fmtPhp(r.credit) : '—'}</td>
                                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmtPhp(r.running_balance)}</td>
                            </tr>
                        `).join('') : `<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">No posted AR transactions yet.</td></tr>`}
                    </tbody>
                </table>
            </div>
        `;
        document.getElementById('custDrawerBackdrop')?.classList.add('is-open');
        document.getElementById('customerSoaDrawer')?.classList.add('is-open');
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const drawerOpen = document.querySelector('#custDrawerBackdrop.is-open');
            if (drawerOpen) window.closeCustomerDrawer();
            else if (activeCustomerId) window.closeCustomerProfile();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('custDrawerBackdrop')?.addEventListener('click', window.closeCustomerDrawer);
        window.setCustomerViewMode(viewMode);
        hydrateUoms();
        const params = new URLSearchParams(window.location.search);
        const cid = params.get('customer_id');
        if (cid) window.openCustomerProfile(cid);
    });
})();
