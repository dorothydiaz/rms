(() => {
    const cfg = window.SalesOrderConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let orders = Array.isArray(cfg.data?.initialOrders) ? [...cfg.data.initialOrders] : [];
    const customers = Array.isArray(cfg.data?.initialCustomers) ? cfg.data.initialCustomers : [];
    const items = Array.isArray(cfg.data?.initialItems) ? cfg.data.initialItems : [];
    const taxCodes = Array.isArray(cfg.data?.initialTaxCodes) ? cfg.data.initialTaxCodes : [];
    const vouchersCatalog = Array.isArray(cfg.data?.initialVouchers) ? cfg.data.initialVouchers : [];
    const bomItemIds = cfg.data?.bomItemIds || {};

    let statusFilter = cfg.initialStageFilter === 'QUOTE' ? 'QUOTE' : 'all';
    let searchQuery = '';
    let activeOrderId = null;
    let activeDocStage = 'ORDER';
    let activeLines = [];
    let appliedVoucher = null;
    let isDirty = false;
    let lastCreditEval = null;

    const QUOTE_STATUSES = ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'QUOTE_EXPIRED'];
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    const fmt = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(type, msg) {
        const root = document.getElementById('soToastContainer');
        if (!root) return alert(msg);
        const el = document.createElement('div');
        el.className = `rfq-toast ${type === 'error' ? 'error' : 'success'}`;
        el.innerHTML = `<i class="ph ${type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'}"></i><span>${esc(msg)}</span>`;
        root.appendChild(el);
        setTimeout(() => el.remove(), 4200);
    }

    function setLock(on, msg = 'Processing Sales Record...') {
        const el = document.getElementById('soGlobalLockOverlay');
        if (!el) return;
        document.getElementById('soLockOverlayMessage').textContent = msg;
        el.classList.toggle('is-active', !!on);
    }

    // =========================================================================
    // ACCOUNTING TAX & PAYMENT TERM HELPERS
    // =========================================================================
    function getSalesTaxCodes() {
        const filtered = taxCodes.filter((t) => ['VAT_OUTPUT', 'EXEMPT', 'ZERO_RATED'].includes(String(t.tax_type || '').toUpperCase()));
        if (filtered.length) return filtered;
        return [
            { tax_code: 'VAT12_OUT', tax_name: '12% Output VAT (Sales)', tax_type: 'VAT_OUTPUT', rate_pct: 12 },
            { tax_code: 'VAT_ZERO', tax_name: '0% Zero-Rated VAT', tax_type: 'ZERO_RATED', rate_pct: 0 },
            { tax_code: 'VAT_EXEMPT', tax_name: 'VAT-Exempt Sale', tax_type: 'EXEMPT', rate_pct: 0 },
        ];
    }

    function normalizeTaxCode(code) {
        const s = String(code || 'VAT12_OUT').toUpperCase().trim();
        return s === 'VAT_12' ? 'VAT12_OUT' : s;
    }

    function getTaxMeta(code) {
        const norm = normalizeTaxCode(code);
        const list = getSalesTaxCodes();
        const found = list.find((t) => String(t.tax_code).toUpperCase() === norm);
        if (found) {
            const tt = String(found.tax_type || '').toUpperCase();
            const isExempt = ['EXEMPT', 'ZERO_RATED'].includes(tt) || ['EXEMPT', 'ZERO_RATED', 'VAT_EXEMPT', 'VAT_ZERO'].includes(norm);
            return {
                tax_code: String(found.tax_code).toUpperCase(),
                tax_name: found.tax_name || norm,
                tax_type: tt || 'VAT_OUTPUT',
                rate_pct: isExempt ? 0 : Number(found.rate_pct || 0),
            };
        }
        const isZero = ['EXEMPT', 'ZERO_RATED', 'VAT_EXEMPT', 'VAT_ZERO'].includes(norm);
        return {
            tax_code: norm,
            tax_name: isZero ? norm : '12% Output VAT',
            tax_type: isZero ? 'EXEMPT' : 'VAT_OUTPUT',
            rate_pct: isZero ? 0 : 12,
        };
    }

    function taxSelectClass(code) {
        const meta = getTaxMeta(code);
        return meta.rate_pct > 0 ? 'vat' : 'is-none';
    }

    function setSelectNormalizedTerms(rawCode) {
        const sel = document.getElementById('soTermsSelect');
        if (!sel || !rawCode) return;
        const raw = String(rawCode).toUpperCase().trim();
        const compact = raw.replace(/[_\-\s]/g, '');
        const match = [...sel.options].find((o) => {
            const ov = o.value.toUpperCase();
            return ov === raw || ov.replace(/[_\-\s]/g, '') === compact;
        });
        if (match) sel.value = match.value;
    }

    window.markSoDirty = (val = true) => {
        isDirty = !!val;
        const ind = document.getElementById('soDirtyIndicator');
        if (ind) ind.style.display = isDirty ? 'inline-flex' : 'none';
    };

    function defaultTaxForCustomer(cust) {
        const dt = cust?.default_tax_code ? normalizeTaxCode(cust.default_tax_code) : 'VAT12_OUT';
        const match = getSalesTaxCodes().find((t) => String(t.tax_code).toUpperCase() === dt);
        return match ? match.tax_code : (getSalesTaxCodes()[0]?.tax_code || 'VAT12_OUT');
    }

    function emptyLine(defaultTax = 'VAT12_OUT') {
        return {
            inventory_item_id: null,
            sku: '',
            item_name: '',
            quantity: 1,
            uom: 'PCS',
            qty_delivered: 0,
            qty_invoiced: 0,
            unit_cost_snapshot: 0,
            unit_price: 0,
            discount_pct: 0,
            tax_code: defaultTax,
            pre_exempt_tax_code: null,
            bundle_id: null,
            bundle_code: null,
            bundle_name: null,
            is_custom: false,
        };
    }

    function hydrateUoms() {
        if (window.UomRegistry && typeof window.UomRegistry.autoHydrate === 'function') {
            window.UomRegistry.autoHydrate();
        }
    }

    function mergeAtpMap(atpMap) {
        if (!atpMap || typeof atpMap !== 'object') return;
        items.forEach((it) => {
            const entry = atpMap[it.id] || atpMap[String(it.id)];
            if (entry) {
                it.current_stock = Number(entry.on_hand ?? it.current_stock);
                it.reserved_qty = Number(entry.reserved_qty ?? it.reserved_qty);
                it.atp_qty = Number(entry.atp_qty ?? it.atp_qty);
            }
        });
    }

    // =========================================================================
    // SLIDE-OVER DRAWERS & KEYBOARD SHORTCUTS
    // =========================================================================
    window.closeAllSoDrawers = () => {
        document.getElementById('soDrawerBackdrop')?.classList.remove('is-open');
        ['soDeliveryDrawer', 'soItemPickerDrawer', 'soCreditReviewDrawer', 'soCancelDrawer'].forEach((id) => {
            document.getElementById(id)?.classList.remove('is-open');
        });
    };

    function openDrawerById(id) {
        window.closeAllSoDrawers();
        document.getElementById('soDrawerBackdrop')?.classList.add('is-open');
        document.getElementById(id)?.classList.add('is-open');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            window.closeAllSoDrawers();
            document.getElementById('soCustomerComboDropdown')?.classList.remove('is-active');
        }
        if (e.key === 'F2') {
            const builderActive = document.getElementById('so-pane-builder')?.classList.contains('is-active');
            const pickerBtn = document.getElementById('soBtnItemPicker');
            if (builderActive && pickerBtn && pickerBtn.style.display !== 'none') {
                e.preventDefault();
                window.openSoItemPickerDrawer();
            }
        }
    });

    window.copySoReference = () => {
        const ref = document.getElementById('soRefDisplay')?.textContent?.trim();
        if (ref && navigator.clipboard) {
            navigator.clipboard.writeText(ref).then(() => toast('success', `Copied [${ref}] to clipboard.`)).catch(() => {});
        }
    };

    // =========================================================================
    // TAB SWITCHING & TRACKER
    // =========================================================================
    window.switchSoTab = (tab) => {
        const isList = tab === 'tab-list';
        document.getElementById('soTabBtnList')?.classList.toggle('active', isList);
        document.getElementById('soTabBtnBuilder')?.classList.toggle('active', !isList);
        const listPane = document.getElementById('so-pane-list');
        const builderPane = document.getElementById('so-pane-builder');
        if (listPane) {
            listPane.classList.toggle('is-active', isList);
            listPane.style.display = isList ? 'flex' : 'none';
        }
        if (builderPane) {
            builderPane.classList.toggle('is-active', !isList);
            builderPane.style.display = isList ? 'none' : 'flex';
        }
    };

    function updateKpis() {
        const active = orders.filter((o) => String(o.status).toUpperCase() !== 'ARCHIVED');
        document.getElementById('soTabListCount').textContent = active.length;
        document.getElementById('kpiSoTotal').textContent = active.length;
        document.getElementById('kpiSoQuotes').textContent = active.filter((o) => QUOTE_STATUSES.includes(String(o.status).toUpperCase())).length;
        document.getElementById('kpiSoCreditHold').textContent = active.filter((o) => o.status === 'CREDIT_HOLD').length;
        document.getElementById('kpiSoReady').textContent = active.filter((o) => ['APPROVED', 'PARTIALLY_DELIVERED'].includes(o.status)).length;
        document.getElementById('kpiSoBilled').textContent = active.filter((o) => o.fulfillment_status === 'DELIVERED' || ['BILLED', 'CLOSED'].includes(o.status)).length;
    }

    function syncFilterHighlights() {
        document.querySelectorAll('#so-pane-list .rfq-kpi-card').forEach((c) => {
            c.classList.toggle('is-active-filter', c.dataset.kpiFilter === statusFilter);
        });
        document.querySelectorAll('#soStageFilterPills .so-stage-pill-btn').forEach((b) => {
            b.classList.toggle('is-active', b.dataset.stageFilter === statusFilter);
        });
    }

    function renderDirectory() {
        updateKpis();
        syncFilterHighlights();
        const body = document.getElementById('soDirectoryBody');
        if (!body) return;
        const q = searchQuery.trim().toLowerCase();

        const rows = orders.filter((o) => {
            const st = String(o.status || 'DRAFT').toUpperCase();
            if (st === 'ARCHIVED') return false;
            if (statusFilter === 'QUOTE' && !QUOTE_STATUSES.includes(st)) return false;
            else if (statusFilter === 'APPROVED' && !['APPROVED', 'PARTIALLY_DELIVERED'].includes(st)) return false;
            else if (statusFilter === 'BILLED' && !(o.fulfillment_status === 'DELIVERED' || ['BILLED', 'CLOSED'].includes(st))) return false;
            else if (statusFilter !== 'all' && !['QUOTE', 'APPROVED', 'BILLED'].includes(statusFilter) && st !== statusFilter) return false;

            if (!q) return true;
            return [o.order_number, o.quote_number, o.customer_name, o.customer_po_number, o.salesperson_name].some((v) => String(v || '').toLowerCase().includes(q));
        });

        if (!rows.length) {
            body.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">No matching Quotes or Sales Orders. Click <strong>New Quote</strong> or <strong>New Sales Order</strong>.</td></tr>`;
            return;
        }

        body.innerHTML = rows.map((o) => {
            const st = String(o.status || 'DRAFT').toUpperCase();
            const isQuoteStage = QUOTE_STATUSES.includes(st);
            const delChips = (o.deliveries || []).map((d) => `<span class="so-linked-chip is-do">${esc(d.order_number)} (${esc(d.status)})</span>`).join(' ');
            const invChips = (o.invoices || []).map((i) => `<span class="so-linked-chip is-bill">${esc(i.invoice_number)} (${esc(i.status)})</span>`).join(' ');
            const promoChip = o.voucher_code ? `<span class="so-bundle-tag" style="margin-top:2px;"><i class="ph ph-ticket"></i> ${esc(o.voucher_code)}</span>` : '';
            return `
                <tr>
                    <td>
                        <div style="font-family:monospace;font-weight:700;color:${isQuoteStage ? '#0284c7' : '#7c3aed'};">${esc(o.order_number)}</div>
                        ${o.quote_number && o.quote_number !== o.order_number ? `<div style="font-size:10.5px;color:#64748b;">from ${esc(o.quote_number)} v${o.revision_no || 1}</div>` : (isQuoteStage ? `<div style="font-size:10.5px;color:#64748b;">Quote Rev v${o.revision_no || 1}</div>` : '')}
                        ${promoChip}
                    </td>
                    <td>
                        <strong>${esc(o.customer_name)}</strong>
                        <div style="font-size:11px;color:#64748b;">${esc(o.payment_terms_code || 'NET_30')}${o.salesperson_name ? ' &bull; ' + esc(o.salesperson_name) : ''}</div>
                    </td>
                    <td><span class="rfq-label-badge">${esc(o.credit_review_status || 'NOT_EVALUATED')}</span></td>
                    <td>
                        <div>${esc(o.fulfillment_status || 'UNFULFILLED')}</div>
                        <div style="margin-top:3px;">${delChips}</div>
                    </td>
                    <td>
                        <div>${esc(o.payment_status || 'UNBILLED')}</div>
                        <div style="margin-top:3px;">${invChips}</div>
                    </td>
                    <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmt(o.gross_total)}</td>
                    <td><span class="status-pill ${st.toLowerCase()}">${esc(st.replace(/_/g, ' '))}</span></td>
                    <td style="text-align:right;">
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 10px;font-size:11px;" onclick="openSalesOrder('${esc(o.sales_order_id)}')">Manage</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.filterSoByStatus = (st) => { statusFilter = st; renderDirectory(); };
    window.handleSoSearch = (val) => { searchQuery = val || ''; renderDirectory(); };

    // =========================================================================
    // CUSTOMER COMBOBOX & LIVE CREDIT CHECK
    // =========================================================================
    function renderCustomerComboList(filter = '') {
        const list = document.getElementById('soCustomerComboList');
        if (!list) return;
        const q = filter.trim().toLowerCase();
        const matches = customers.filter((c) => !q || [c.customer_name, c.customer_code, c.tin].some((v) => String(v || '').toLowerCase().includes(q)));
        list.innerHTML = matches.length ? matches.map((c) => `
            <button type="button" onclick="selectSoCustomer('${esc(c.customer_id)}')" style="display:flex;justify-content:space-between;width:100%;text-align:left;padding:8px 10px;border:none;border-bottom:1px solid #f1f5f9;background:transparent;cursor:pointer;">
                <div>
                    <div style="font-weight:700;font-size:0.82rem;color:#0f172a;">${esc(c.customer_name)}</div>
                    <div style="font-size:0.71rem;color:#64748b;">${esc(c.customer_code)} &bull; ${esc(c.payment_terms_code || 'NET_30')} &bull; ${esc(c.default_tax_code || 'VAT12_OUT')}</div>
                </div>
                <div style="text-align:right;font-size:0.71rem;font-weight:700;color:${c.credit_hold ? '#dc2626' : '#059669'};">
                    ${c.credit_hold ? 'HOLD' : 'Avail ' + fmt(c.available_credit ?? c.credit_limit)}
                </div>
            </button>
        `).join('') : `<div style="padding:12px;text-align:center;color:#94a3b8;font-size:0.78rem;">No matching customers</div>`;
    }

    window.toggleSoCustomerCombo = (ev) => {
        ev?.stopPropagation();
        const dd = document.getElementById('soCustomerComboDropdown');
        const card = document.getElementById('soTopBannerCard');
        const open = !dd.classList.contains('is-active');
        dd.classList.toggle('is-active', open);
        card?.classList.toggle('has-dropdown-open', open);
        if (open) { renderCustomerComboList(''); document.getElementById('soCustomerSearchInput')?.focus(); }
    };

    window.filterSoCustomers = (val) => renderCustomerComboList(val);

    window.selectSoCustomer = (customerId, keepFields = false) => {
        const c = customers.find((x) => x.customer_id === customerId);
        document.getElementById('soCustomerIdInput').value = c?.customer_id || '';
        document.getElementById('soCustomerComboText').textContent = c ? `${c.customer_code} — ${c.customer_name}` : '-- Choose Customer --';
        document.getElementById('soCustomerComboText').style.color = c ? '#0f172a' : '#64748b';
        document.getElementById('soCustomerComboDropdown')?.classList.remove('is-active');
        document.getElementById('soTopBannerCard')?.classList.remove('has-dropdown-open');

        const profileLink = document.getElementById('soCustomerProfileLink');
        if (profileLink) {
            profileLink.style.display = c ? 'inline-flex' : 'none';
            if (c) document.getElementById('soCustomerProfileText').textContent = c.customer_name;
        }

        if (c && !keepFields) {
            if (c.payment_terms_code) setSelectNormalizedTerms(c.payment_terms_code);
            document.getElementById('soShippingAddressInput').value = c.shipping_address || c.address || '';
            const defTax = defaultTaxForCustomer(c);
            let taxChanged = false;
            activeLines.forEach((l) => {
                if (!l.inventory_item_id && l.tax_code === 'VAT12_OUT' && defTax !== 'VAT12_OUT') {
                    l.tax_code = defTax;
                    taxChanged = true;
                }
            });
            window.markSoDirty(true);
            if (taxChanged) renderLineRows(false);
        }
        window.refreshLiveCreditCheck();
        window.syncSoMockup();
    };

    window.refreshLiveCreditCheck = async () => {
        const banner = document.getElementById('soCreditExposureBanner');
        const cId = document.getElementById('soCustomerIdInput')?.value;
        if (!banner || !cId) {
            if (banner) banner.style.display = 'none';
            lastCreditEval = null;
            return;
        }

        const gross = parseFloat(String(document.getElementById('soSumGross')?.textContent || '0').replace(/[^0-9.-]/g, '')) || 0;
        const terms = document.getElementById('soTermsSelect')?.value || 'NET_30';
        const url = String(cfg.routes.creditCheck).replace('__ID__', encodeURIComponent(cId))
            + `?gross_total=${encodeURIComponent(gross)}&payment_terms_code=${encodeURIComponent(terms)}`
            + (activeOrderId ? `&exclude_sales_order_id=${encodeURIComponent(activeOrderId)}` : '');

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) return;
            const ev = json.data;
            lastCreditEval = ev;
            const limit = Number(ev.credit_limit || 0);
            const pct = limit > 0 ? Math.min(100, Math.round((ev.projected_exposure / limit) * 100)) : 100;
            const warn = ev.requires_review || !ev.within_limit;

            banner.style.display = 'flex';
            banner.className = `so-credit-banner ${warn ? 'is-hold' : 'is-ok'}`;
            banner.innerHTML = `
                <div>
                    <strong>${ev.is_cash_terms ? 'COD / Advance Fast-Path' : 'Credit-Term Order (Accounting Credit Review Required)'}</strong>
                    <div style="font-size:0.75rem;margin-top:2px;">
                        Limit: <strong>${fmt(limit)}</strong> &bull;
                        AR + Open Orders: <strong>${fmt(ev.current_ar_balance + ev.open_orders_exposure)}</strong> &bull;
                        Projected: <strong>${fmt(ev.projected_exposure)}</strong>
                        ${ev.reasons?.length ? ` &bull; <span style="color:#b91c1c;font-weight:700;">${esc(ev.reasons.join(' '))}</span>` : ''}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="so-credit-meter"><div class="so-credit-meter-fill ${!ev.within_limit ? 'is-breach' : ''}" style="width:${pct}%;"></div></div>
                    <a href="${esc(cfg.routes.accountingArCredit)}" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;text-decoration:none;">AR Credit Queue</a>
                </div>
            `;
        } catch (e) {}
    };

    // =========================================================================
    // LINE ITEMS & ZERO-FOCUS-LOSS LIVE UPDATES
    // =========================================================================
    function computeLineNetSubtotal(line) {
        const qty = Math.max(0, Number(line.quantity || 0));
        const price = Math.max(0, Number(line.unit_price || 0));
        const discPct = Math.max(0, Math.min(100, Number(line.discount_pct || 0)));
        return Math.max(0, qty * price * (1 - discPct / 100));
    }

    function atpCellHtml(line, matchedItem) {
        const atp = matchedItem ? Number(matchedItem.atp_qty ?? matchedItem.current_stock ?? 0) : null;
        if (atp === null) return '—';
        const shortage = atp < Number(line.quantity || 0);
        const bomCode = matchedItem ? bomItemIds[matchedItem.id] : null;
        const supplyLink = shortage
            ? (bomCode
                ? `<a href="${esc(cfg.routes.inventoryProduction)}?bom_code=${encodeURIComponent(bomCode)}" target="_blank" style="display:block;font-size:10px;color:#7c3aed;font-weight:700;margin-top:2px;">Produce (BOM)</a>`
                : `<a href="${esc(cfg.routes.purchaseRfq)}" target="_blank" style="display:block;font-size:10px;color:#b45309;font-weight:700;margin-top:2px;">Request RFQ</a>`)
            : '';
        return `<span class="sq-atp-pill ${shortage ? 'is-low' : ''}">${atp.toFixed(1)}</span>${supplyLink}`;
    }

    function renderLineRows(readonly = false) {
        const tbody = document.getElementById('soItemsTbody');
        if (!tbody) return;
        document.getElementById('soItemsCountBadge').textContent = `${activeLines.length} Line Item${activeLines.length === 1 ? '' : 's'}`;
        const taxList = getSalesTaxCodes();

        tbody.innerHTML = activeLines.map((line, idx) => {
            const matchedItem = items.find((i) => Number(i.id) === Number(line.inventory_item_id) || (line.sku && i.sku === line.sku));
            const bundleTag = line.bundle_code
                ? `<div style="margin-top:3px;"><span class="so-bundle-tag"><i class="ph ph-gift"></i> ${esc(line.bundle_code)}</span></div>`
                : '';
            const normLineTax = normalizeTaxCode(line.tax_code);
            const isCustomRow = line.is_custom || (!line.inventory_item_id && line.item_name);

            return `
                <tr data-line-idx="${idx}">
                    <td style="color:#94a3b8;font-family:monospace;">${idx + 1}</td>
                    <td>
                        <select class="rfq-select" ${readonly ? 'disabled' : ''} onchange="onSoItemSelect(${idx}, this.value)" style="font-size:12px;">
                            <option value="">-- Select Item Master SKU --</option>
                            <option value="__CUSTOM__" ${isCustomRow ? 'selected' : ''}>Custom / Non-Catalog Description</option>
                            ${items.map((it) => `<option value="${it.id}" ${Number(line.inventory_item_id) === Number(it.id) ? 'selected' : ''}>[${esc(it.sku)}] ${esc(it.name)}</option>`).join('')}
                        </select>
                        ${isCustomRow ? `<input type="text" ${readonly ? 'disabled' : ''} class="rfq-input" style="margin-top:4px;font-size:11.5px;" placeholder="Enter custom service / catering item..." value="${esc(line.item_name || '')}" oninput="updateSoLine(${idx}, 'item_name', this.value)">` : ''}
                        ${bundleTag}
                    </td>
                    <td class="so-atp-cell">${atpCellHtml(line, matchedItem)}</td>
                    <td><select class="form-control" ${readonly ? 'disabled' : ''} data-uom-select data-uom-category="ALL" data-uom-current="${esc(line.uom || 'PCS')}" onchange="updateSoLine(${idx}, 'uom', this.value)"></select></td>
                    <td><input type="number" step="0.01" min="0.0001" ${readonly ? 'disabled' : ''} class="rfq-input" value="${line.quantity}" oninput="updateSoLine(${idx}, 'quantity', parseFloat(this.value)||0)" style="text-align:right;"></td>
                    <td style="font-size:11px;color:#475569;font-variant-numeric:tabular-nums;">Del: <strong>${Number(line.qty_delivered || 0).toFixed(1)}</strong> / Inv: <strong>${Number(line.qty_invoiced || 0).toFixed(1)}</strong></td>
                    <td><input type="number" step="0.01" min="0" ${readonly ? 'disabled' : ''} class="rfq-input" value="${line.unit_price}" oninput="updateSoLine(${idx}, 'unit_price', parseFloat(this.value)||0)" style="text-align:right;"></td>
                    <td><input type="number" step="0.1" min="0" max="100" ${readonly ? 'disabled' : ''} class="rfq-input" value="${line.discount_pct || 0}" oninput="updateSoLine(${idx}, 'discount_pct', parseFloat(this.value)||0)" style="text-align:right;"></td>
                    <td>
                        <select class="po-tax-select ${taxSelectClass(normLineTax)}" ${readonly ? 'disabled' : ''} onchange="updateSoLine(${idx}, 'tax_code', this.value)">
                            ${taxList.map((tc) => `<option value="${esc(tc.tax_code)}" ${normLineTax === String(tc.tax_code).toUpperCase() ? 'selected' : ''}>${esc(tc.tax_code)} (${Number(tc.rate_pct)}%)</option>`).join('')}
                        </select>
                    </td>
                    <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;" class="so-line-total-cell">₱0.00</td>
                    <td style="text-align:center;">${readonly ? '' : `<button type="button" class="rfq-btn rfq-btn-danger" style="padding:4px 7px;" onclick="removeSoLine(${idx})"><i class="ph ph-trash"></i></button>`}</td>
                </tr>
            `;
        }).join('');

        hydrateUoms();
        window.recalcSoTotals();
    }

    window.onSoItemSelect = (idx, val) => {
        if (val === '__CUSTOM__') {
            activeLines[idx].inventory_item_id = null;
            activeLines[idx].sku = '';
            activeLines[idx].is_custom = true;
            window.markSoDirty(true);
            renderLineRows(false);
            return;
        }
        const it = items.find((i) => String(i.id) === String(val));
        if (!it) return;
        activeLines[idx].inventory_item_id = it.id;
        activeLines[idx].sku = it.sku;
        activeLines[idx].item_name = it.name;
        activeLines[idx].uom = it.uom || 'PCS';
        activeLines[idx].unit_price = Number(it.selling_price || 0);
        activeLines[idx].unit_cost_snapshot = Number(it.cost_price || 0);
        activeLines[idx].is_custom = false;
        window.markSoDirty(true);
        renderLineRows(false);
    };

    window.updateSoLine = (idx, field, val) => {
        if (!activeLines[idx]) return;
        activeLines[idx][field] = val;
        window.markSoDirty(true);

        if (field === 'tax_code') {
            renderLineRows(false);
            return;
        }

        if (field === 'quantity') {
            const matchedItem = items.find((i) => Number(i.id) === Number(activeLines[idx].inventory_item_id));
            const atpTd = document.querySelector(`#soItemsTbody tr[data-line-idx="${idx}"] .so-atp-cell`);
            if (atpTd) atpTd.innerHTML = atpCellHtml(activeLines[idx], matchedItem);
        }

        window.recalcSoTotals();
    };

    window.addSoItemRow = () => {
        const cust = customers.find((c) => c.customer_id === document.getElementById('soCustomerIdInput')?.value);
        activeLines.push(emptyLine(defaultTaxForCustomer(cust)));
        window.markSoDirty(true);
        renderLineRows(false);
    };

    window.removeSoLine = (idx) => {
        const cust = customers.find((c) => c.customer_id === document.getElementById('soCustomerIdInput')?.value);
        activeLines.splice(idx, 1);
        if (!activeLines.length) activeLines.push(emptyLine(defaultTaxForCustomer(cust)));
        window.markSoDirty(true);
        renderLineRows(false);
    };

    // =========================================================================
    // ITEM MASTER QUICK PICKER DRAWER (F2)
    // =========================================================================
    window.renderSoPickerList = () => {
        const container = document.getElementById('soPickerList');
        if (!container) return;
        const q = (document.getElementById('soPickerSearchInput')?.value || '').trim().toLowerCase();
        const matches = items.filter((it) => !q || [it.sku, it.name, it.category].some((v) => String(v || '').toLowerCase().includes(q)));
        container.innerHTML = matches.length ? matches.map((it) => {
            const atp = Number(it.atp_qty ?? it.current_stock ?? 0);
            return `
                <div class="so-picker-row">
                    <div>
                        <div style="font-weight:700;font-size:12.5px;color:#0f172a;">[${esc(it.sku)}] ${esc(it.name)}</div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">${esc(it.category || 'Catalog')} &bull; UOM: <strong>${esc(it.uom || 'PCS')}</strong> &bull; Price: <strong>${fmt(it.selling_price)}</strong></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="sq-atp-pill ${atp <= 0 ? 'is-low' : ''}">ATP ${atp.toFixed(1)}</span>
                        <button type="button" class="rfq-btn rfq-btn-primary" style="padding:5px 10px;font-size:11px;" onclick="addSoItemFromPicker(${it.id})">+ Add</button>
                    </div>
                </div>
            `;
        }).join('') : `<div style="text-align:center;padding:24px;color:#94a3b8;">No matching catalog items.</div>`;
    };

    window.openSoItemPickerDrawer = () => {
        window.renderSoPickerList();
        openDrawerById('soItemPickerDrawer');
        setTimeout(() => document.getElementById('soPickerSearchInput')?.focus(), 80);
    };

    window.addSoItemFromPicker = (itemId) => {
        const it = items.find((i) => Number(i.id) === Number(itemId));
        if (!it) return;
        const cust = customers.find((c) => c.customer_id === document.getElementById('soCustomerIdInput')?.value);
        const defTax = defaultTaxForCustomer(cust);

        const existing = activeLines.find((l) => Number(l.inventory_item_id) === Number(it.id) && !l.bundle_id);
        if (existing) {
            existing.quantity = Number(existing.quantity || 0) + 1;
        } else {
            if (activeLines.length === 1 && !activeLines[0].item_name && !activeLines[0].inventory_item_id) {
                activeLines = [];
            }
            activeLines.push({
                ...emptyLine(defTax),
                inventory_item_id: it.id,
                sku: it.sku,
                item_name: it.name,
                uom: it.uom || 'PCS',
                unit_price: Number(it.selling_price || 0),
                unit_cost_snapshot: Number(it.cost_price || 0),
            });
        }
        window.markSoDirty(true);
        renderLineRows(false);
        toast('success', `Added [${it.sku}] ${it.name}.`);
    };

    // =========================================================================
    // BUNDLES, DISCOUNTS & VOUCHERS
    // =========================================================================
    window.addSoBundleRows = async () => {
        const sel = document.getElementById('soBundleAddSelect');
        const bundleId = sel?.value;
        if (!bundleId) return toast('error', 'Select a Bundle Promo to explode into component lines.');
        try {
            const url = String(cfg.routes.explodeBundle).replace('__ID__', encodeURIComponent(bundleId));
            const res = await postJson(url, { quantity: 1 }, 'Exploding Bundle Components & Checking ATP...');
            const exploded = res.data?.lines || [];
            if (!exploded.length) return toast('error', 'Bundle has no active component SKUs.');
            if (activeLines.length === 1 && !activeLines[0].item_name && !activeLines[0].inventory_item_id) {
                activeLines = [];
            }
            exploded.forEach((l) => activeLines.push({
                ...emptyLine(l.tax_code || 'VAT12_OUT'),
                inventory_item_id: l.inventory_item_id,
                sku: l.sku || '',
                item_name: l.item_name,
                quantity: Number(l.quantity),
                uom: l.uom || 'PCS',
                unit_cost_snapshot: Number(l.unit_cost_snapshot || 0),
                unit_price: Number(l.unit_price || 0),
                bundle_id: l.bundle_id,
                bundle_code: l.bundle_code,
                bundle_name: l.bundle_name,
            }));
            sel.value = '';
            window.markSoDirty(true);
            renderLineRows(false);
            toast('success', `Bundle exploded into ${exploded.length} component SKU line(s).`);
        } catch (err) { toast('error', err.message); }
    };

    window.onDiscountRuleChanged = () => {
        const sel = document.getElementById('soDiscountRuleSelect');
        const opt = sel?.selectedOptions?.[0];
        const discTypeEl = document.getElementById('soHeaderDiscType');
        const discValEl = document.getElementById('soHeaderDiscVal');
        window.markSoDirty(true);

        if (opt && opt.value) {
            if (discTypeEl) { discTypeEl.value = opt.dataset.type || 'NONE'; discTypeEl.disabled = true; }
            if (discValEl) { discValEl.value = opt.dataset.val || '0'; discValEl.disabled = true; }

            if (opt.dataset.vatExempt === '1') {
                activeLines.forEach((l) => {
                    if (!l.pre_exempt_tax_code) l.pre_exempt_tax_code = l.tax_code;
                    l.tax_code = 'VAT_EXEMPT';
                });
                renderLineRows(false);
                toast('success', 'BIR Senior/PWD VAT-Exempt override applied to lines.');
                return;
            }
        } else {
            if (discTypeEl) { discTypeEl.disabled = false; discTypeEl.value = 'NONE'; }
            if (discValEl) { discValEl.disabled = false; discValEl.value = '0'; }
            let restored = false;
            activeLines.forEach((l) => {
                if (l.pre_exempt_tax_code) {
                    l.tax_code = l.pre_exempt_tax_code;
                    l.pre_exempt_tax_code = null;
                    restored = true;
                }
            });
            if (restored) {
                renderLineRows(false);
                return;
            }
        }
        window.recalcSoTotals();
    };

    window.onManualDiscountChanged = () => {
        document.getElementById('soDiscountRuleSelect').value = '';
        window.markSoDirty(true);
        window.recalcSoTotals();
    };

    window.validateAndApplyVoucher = async () => {
        const code = (document.getElementById('soVoucherCodeInput')?.value || '').trim().toUpperCase();
        if (!code) return toast('error', 'Enter a voucher code first.');
        const netSub = parseFloat(String(document.getElementById('soSumGrossSubtotal')?.textContent || '0').replace(/[^0-9.-]/g, '')) || 0;
        try {
            const res = await postJson(cfg.routes.validateVoucher, {
                voucher_code: code,
                customer_id: document.getElementById('soCustomerIdInput')?.value || null,
                order_subtotal: netSub,
                exclude_sales_order_id: activeOrderId,
            }, 'Validating Promo Voucher...');
            appliedVoucher = res.data;
            document.getElementById('soVoucherCodeInput').value = appliedVoucher.voucher_code;
            document.getElementById('soVoucherStatusChip').textContent = `${appliedVoucher.voucher_code} (-${fmt(appliedVoucher.discount_amount)})`;
            document.getElementById('soBtnClearVoucher').style.display = 'inline-flex';
            window.markSoDirty(true);
            window.recalcSoTotals();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.clearVoucher = () => {
        appliedVoucher = null;
        document.getElementById('soVoucherCodeInput').value = '';
        document.getElementById('soVoucherStatusChip').textContent = '';
        document.getElementById('soBtnClearVoucher').style.display = 'none';
        window.markSoDirty(true);
        window.recalcSoTotals();
    };

    // =========================================================================
    // DYNAMIC ACCOUNTING TAX & FINANCIAL TOTALS ENGINE
    // =========================================================================
    window.recalcSoTotals = () => {
        let linesSub = 0;
        let unitsSum = 0;
        const rows = document.querySelectorAll('#soItemsTbody tr');

        activeLines.forEach((l, idx) => {
            const qty = Math.max(0, Number(l.quantity || 0));
            unitsSum += qty;
            const sub = computeLineNetSubtotal(l);
            linesSub += sub;
            const taxMeta = getTaxMeta(l.tax_code);
            const rate = taxMeta.rate_pct / 100;
            const cell = rows[idx]?.querySelector('.so-line-total-cell');
            if (cell) cell.textContent = fmt(sub * (1 + rate));
        });

        const discOpt = document.getElementById('soDiscountRuleSelect')?.selectedOptions?.[0];
        const discCap = discOpt?.dataset?.cap ? parseFloat(discOpt.dataset.cap) : null;
        const discType = document.getElementById('soHeaderDiscType')?.value || 'NONE';
        const discVal = Number(document.getElementById('soHeaderDiscVal')?.value || 0);
        let headerDisc = 0;
        if (discType === 'PERCENT') headerDisc = linesSub * (Math.min(100, Math.max(0, discVal)) / 100);
        else if (discType === 'FIXED') headerDisc = Math.min(linesSub, Math.max(0, discVal));
        if (discCap !== null && !Number.isNaN(discCap)) headerDisc = Math.min(headerDisc, discCap);

        let voucherDisc = 0;
        if (appliedVoucher) {
            const afterRule = Math.max(0, linesSub - headerDisc);
            voucherDisc = appliedVoucher.discount_type === 'PERCENT'
                ? afterRule * (Math.min(100, Number(appliedVoucher.discount_value || 0)) / 100)
                : Math.min(afterRule, Number(appliedVoucher.discount_value || 0));
            if (appliedVoucher.max_discount_amount) voucherDisc = Math.min(voucherDisc, Number(appliedVoucher.max_discount_amount));
        }

        const totalDisc = Math.min(linesSub, headerDisc + voucherDisc);
        const netSub = Math.max(0, linesSub - totalDisc);
        const factor = linesSub > 0 ? (netSub / linesSub) : 1;

        let taxSum = 0;
        const taxGroups = {};
        activeLines.forEach((l) => {
            const meta = getTaxMeta(l.tax_code);
            const base = computeLineNetSubtotal(l) * factor;
            const taxAmt = meta.rate_pct > 0 ? (base * (meta.rate_pct / 100)) : 0;
            taxSum += taxAmt;
            if (!taxGroups[meta.tax_code]) {
                taxGroups[meta.tax_code] = { label: meta.tax_name, rate: meta.rate_pct, amount: 0 };
            }
            taxGroups[meta.tax_code].amount += taxAmt;
        });

        const grossTotal = netSub + taxSum;
        document.getElementById('soSumLinesCount').textContent = activeLines.filter((l) => l.item_name || l.inventory_item_id).length;
        document.getElementById('soSumUnitsCount').textContent = unitsSum.toFixed(2);
        document.getElementById('soSumGrossSubtotal').textContent = fmt(linesSub);
        document.getElementById('soSumDiscounts').textContent = fmt(totalDisc);
        document.getElementById('soSumSubtotal').textContent = fmt(netSub);
        document.getElementById('soSumTax').textContent = fmt(taxSum);
        document.getElementById('soSumGross').textContent = fmt(grossTotal);

        const bdEl = document.getElementById('soTaxBreakdownRows');
        if (bdEl) {
            bdEl.innerHTML = Object.entries(taxGroups).map(([code, g]) => `
                <div class="po-fin-row" style="font-size:0.75rem;color:#475569;">
                    <span class="po-fin-label">${esc(code)} (${Number(g.rate)}%):</span>
                    <span class="po-fin-value">${fmt(g.amount)}</span>
                </div>
            `).join('');
        }

        const cwtRate = parseFloat(document.getElementById('soCwtEstimateSelect')?.value || '0') || 0;
        const cwtRow = document.getElementById('soSumCwtRow');
        if (cwtRow) {
            cwtRow.style.display = cwtRate > 0 ? 'flex' : 'none';
            document.getElementById('soSumCwtVal').textContent = `(${fmt(netSub * (cwtRate / 100))})`;
        }

        window.syncSoMockup(taxGroups, netSub, totalDisc, taxSum, grossTotal);
    };

    window.syncSoMockup = (taxGroups = null, netSub = null, totalDisc = null, taxSum = null, grossTotal = null) => {
        const box = document.getElementById('soLiveMockupBody');
        if (!box) return;
        const cId = document.getElementById('soCustomerIdInput')?.value;
        const cust = customers.find((c) => c.customer_id === cId);
        const orderNum = document.getElementById('soRefDisplay')?.textContent || (activeDocStage === 'QUOTE' ? 'NEW QUOTE' : 'NEW ORDER');
        const repOpt = document.getElementById('soSalespersonSelect')?.selectedOptions?.[0];
        const repName = repOpt?.dataset?.name || '';
        const termOpt = document.getElementById('soTermsSelect')?.selectedOptions?.[0];

        const lineRowsHtml = activeLines.filter((l) => l.item_name || l.inventory_item_id).map((l) => {
            const sub = computeLineNetSubtotal(l);
            const meta = getTaxMeta(l.tax_code);
            const total = sub * (1 + meta.rate_pct / 100);
            return `
                <tr>
                    <td><strong>${esc(l.item_name || 'Item')}</strong>${l.bundle_code ? `<div style="font-size:9px;color:#7c3aed;">[${esc(l.bundle_code)}]</div>` : ''}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;">${Number(l.quantity || 0).toFixed(1)} ${esc(l.uom)}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;">${fmt(l.unit_price)}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;">${fmt(total)}</td>
                </tr>
            `;
        }).join('') || `<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:8px;">No line items added yet</td></tr>`;

        box.innerHTML = `
            <div style="display:flex;justify-content:space-between;border-bottom:2px solid #7c3aed;padding-bottom:8px;margin-bottom:10px;">
                <div>
                    <div style="font-weight:800;font-size:13px;color:#0f172a;">${activeDocStage === 'QUOTE' ? 'SALES QUOTATION' : 'SALES ORDER'}</div>
                    <div style="font-family:monospace;color:#7c3aed;font-weight:700;">${esc(orderNum)}</div>
                </div>
                <div style="text-align:right;font-size:10.5px;color:#64748b;">
                    <div>Terms: <strong>${esc(termOpt?.textContent || document.getElementById('soTermsSelect')?.value || 'NET_30')}</strong></div>
                    <div>Date: <strong>${esc(document.getElementById('soOrderDateInput')?.value || '')}</strong></div>
                    <div>Delivery: <strong>${esc(document.getElementById('soDeliveryDateInput')?.value || '')}</strong></div>
                    ${activeDocStage === 'QUOTE' ? `<div>Valid Until: <strong>${esc(document.getElementById('soValidUntilInput')?.value || '')}</strong></div>` : ''}
                </div>
            </div>
            <div style="margin-bottom:8px;">
                <div style="font-size:10px;text-transform:uppercase;color:#64748b;font-weight:700;">Bill / Ship To</div>
                <div style="font-weight:700;">${esc(cust ? cust.customer_name : 'Select Customer...')}</div>
                ${cust?.tin ? `<div style="font-size:10px;color:#64748b;">TIN: ${esc(cust.tin)}</div>` : ''}
                <div style="color:#475569;">${esc(document.getElementById('soShippingAddressInput')?.value || '')}</div>
                ${repName ? `<div style="font-size:10.5px;color:#64748b;margin-top:2px;">Sales Rep: <strong>${esc(repName)}</strong></div>` : ''}
            </div>
            <table class="so-mockup-table">
                <thead><tr><th>Item</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Price</th><th style="text-align:right;">Total</th></tr></thead>
                <tbody>${lineRowsHtml}</tbody>
            </table>
            <div style="border-top:1px solid #cbd5e1;padding-top:6px;text-align:right;">
                <div>Discounts: <strong>${document.getElementById('soSumDiscounts')?.textContent}</strong></div>
                <div>Net Subtotal: <strong>${document.getElementById('soSumSubtotal')?.textContent}</strong></div>
                <div>Total Tax: <strong>${document.getElementById('soSumTax')?.textContent}</strong></div>
                <div style="font-size:13px;font-weight:800;color:#7c3aed;margin-top:3px;">Grand Total: ${document.getElementById('soSumGross')?.textContent}</div>
            </div>
        `;
    };

    // =========================================================================
    // LINKED FULFILLMENT & BILLING PANELS + STAGE UI
    // =========================================================================
    function renderLinkedPanels(order) {
        const wrap = document.getElementById('soFulfillmentBillingPanel');
        const grid = document.getElementById('soLinkedPanelsGrid');
        if (!wrap || !grid || !order || QUOTE_STATUSES.includes(String(order.status).toUpperCase())) {
            if (wrap) wrap.style.display = 'none';
            return;
        }

        wrap.style.display = 'flex';
        const deliveries = order.deliveries || [];
        const invoices = order.invoices || [];

        const delHtml = deliveries.length ? deliveries.map((d) => `
            <div class="po-pay-stmt-payment-row">
                <div><strong>${esc(d.order_number)}</strong> &bull; ${esc(d.status)}</div>
                <div style="font-family:monospace;color:#0e7490;">Cost: ${fmt(d.total_cost_value)}</div>
            </div>
        `).join('') : '<div style="color:#94a3b8;font-size:0.78rem;">No deliveries dispatched yet.</div>';

        const invHtml = invoices.length ? invoices.map((inv) => `
            <div class="po-fin-payment-row" style="background:${inv.status === 'DRAFT' ? '#fffbeb' : '#f0fdf4'};border-color:${inv.status === 'DRAFT' ? '#fde68a' : '#dcfce7'};color:#0f172a;">
                <div><strong>${esc(inv.invoice_number)}</strong> (${esc(inv.status)}) &bull; Bal: ${fmt(inv.balance_due)}</div>
                <div class="po-fin-payment-right">
                    <span class="po-fin-payment-amt">${fmt(inv.total_amount)}</span>
                    ${inv.status === 'DRAFT' ? `<button type="button" class="rfq-btn rfq-btn-primary" style="padding:3px 8px;font-size:10.5px;" onclick="postDraftInvoiceFromOrder('${esc(inv.invoice_id)}')">Post Bill</button>` : ''}
                </div>
            </div>
        `).join('') : '<div style="color:#94a3b8;font-size:0.78rem;">No customer bills generated yet.</div>';

        grid.innerHTML = `
            <div>
                <div style="font-weight:700;font-size:0.78rem;margin-bottom:6px;color:#334155;">Inventory Deliveries (Stock-Out)</div>
                <div class="po-pay-stmt-payments">${delHtml}</div>
            </div>
            <div>
                <div style="font-weight:700;font-size:0.78rem;margin-bottom:6px;color:#334155;">Customer Bills &amp; AR Invoices</div>
                <div class="po-fin-payments-after-due">${invHtml}</div>
            </div>
        `;
    }

    function updateStageUi(order) {
        const st = String(order?.status || (activeDocStage === 'QUOTE' ? 'QUOTE_DRAFT' : 'DRAFT')).toUpperCase();
        const isQuote = QUOTE_STATUSES.includes(st) || (!order && activeDocStage === 'QUOTE');
        activeDocStage = isQuote ? 'QUOTE' : 'ORDER';

        document.getElementById('soDocTypeLabel').textContent = isQuote ? 'Sales Quote #' : 'Sales Order #';
        document.getElementById('soRefDisplay').textContent = order?.order_number || (isQuote ? 'NEW QUOTE' : 'NEW ORDER');
        document.getElementById('soBuilderTabBadge').textContent = order?.order_number || (isQuote ? 'New Quote' : 'New Order');
        document.getElementById('soMockupTitleTag').textContent = isQuote ? 'Live Sales Quote Preview' : 'Live Sales Order Preview';

        const pill = document.getElementById('soStatusBadge');
        if (pill) {
            pill.className = `status-pill ${st.toLowerCase()}`;
            pill.innerHTML = `<i class="ph ph-dot"></i> ${esc(st.replace(/_/g, ' '))}`;
        }

        const stepIdx = ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_EXPIRED'].includes(st) ? 0
            : st === 'QUOTE_CONFIRMED' ? 1
            : ['DRAFT', 'CREDIT_HOLD'].includes(st) ? 2
            : ['APPROVED', 'PARTIALLY_DELIVERED', 'DELIVERED'].includes(st) ? 3
            : st === 'BILLED' ? 4
            : 5;
        document.querySelectorAll('#soChevronPipeline .rfq-chevron-step').forEach((s, idx) => {
            s.classList.toggle('is-active', idx === stepIdx);
            s.classList.toggle('is-done', idx < stepIdx);
        });

        // Update Stage Helper Ribbon
        const bannerTitles = {
            QUOTE_DRAFT: ['Stage 1: Quote Proposal', 'Prepare pricing, discounts & bundles, then send Quote to Customer or Confirm directly.'],
            QUOTE_SENT: ['Stage 1: Quote Sent to Buyer', 'Awaiting buyer confirmation before promoting to a formal Sales Order (SLS-).'],
            QUOTE_CONFIRMED: ['Stage 2: Buyer Confirmed Quote', 'Click "Promote to SO & Confirm" to evaluate Credit Review and reserve Inventory ATP.'],
            DRAFT: ['Stage 3: Draft Sales Order', 'Review Accounting taxes, payment terms & credit exposure, then Confirm Order.'],
            CREDIT_HOLD: ['Stage 3: Pending Accounting Credit Review', 'Click "Credit Review" to approve limit/override and reserve Inventory ATP stock.'],
            APPROVED: ['Stage 4: Approved & ATP Reserved', 'Click "Create Delivery (DO)" to dispatch Stock-Out & auto-draft the Customer AR Bill.'],
            PARTIALLY_DELIVERED: ['Stage 4: Partially Delivered', 'Dispatch remaining backorder items or post Customer Bill for shipped quantities.'],
            DELIVERED: ['Stage 5: Delivered & Stock-Out Posted', 'COGS GL is posted. Click "Post Draft Bill" to recognize AR & Output VAT.'],
            BILLED: ['Stage 5: AR Invoice Posted', 'Record customer collection receipt in Accounting AR to settle and close order.'],
            CLOSED: ['Stage 6: Settled & Closed', 'Fulfillment, COGS, AR Billing, and Cash Collection are fully reconciled.'],
            CANCELLED: ['Cancelled', 'ATP stock reservations and promo voucher quotas have been released.'],
        };
        const bt = bannerTitles[st] || bannerTitles.DRAFT;
        document.getElementById('soStageBannerTitle').textContent = bt[0];
        document.getElementById('soStageBannerSub').textContent = bt[1];
        document.getElementById('soActiveStagePill').textContent = st.replace(/_/g, ' ');

        const isEditableDraft = !order || ['DRAFT', 'QUOTE_DRAFT', 'QUOTE_SENT'].includes(st);
        document.getElementById('soBtnSaveDraft').style.display = isEditableDraft ? 'inline-flex' : 'none';
        document.getElementById('soBtnSaveDraftLabel').textContent = isQuote ? 'Save Quote' : 'Save Draft';
        document.getElementById('soBtnAddItemRow').style.display = isEditableDraft ? 'inline-flex' : 'none';
        document.getElementById('soBtnExplodeBundle').style.display = isEditableDraft ? 'inline-flex' : 'none';
        document.getElementById('soBtnItemPicker').style.display = isEditableDraft ? 'inline-flex' : 'none';

        document.getElementById('soBtnSendQuote').style.display = (isQuote && st === 'QUOTE_DRAFT') ? 'inline-flex' : 'none';
        document.getElementById('soBtnConfirmQuote').style.display = (isQuote && ['QUOTE_DRAFT', 'QUOTE_SENT'].includes(st)) ? 'inline-flex' : 'none';
        document.getElementById('soBtnReviseQuote').style.display = (!!order && isQuote) ? 'inline-flex' : 'none';

        const canConfirmSo = ['DRAFT', 'QUOTE_CONFIRMED'].includes(st) && !isQuote ? !!order : (st === 'QUOTE_CONFIRMED' || (!order && !isQuote));
        document.getElementById('soBtnConfirmOrder').style.display = (st === 'DRAFT' || st === 'QUOTE_CONFIRMED' || !order) ? 'inline-flex' : 'none';
        document.getElementById('soBtnConfirmOrderLabel').textContent = st === 'QUOTE_CONFIRMED' ? 'Promote to SO & Confirm' : 'Confirm Order';

        document.getElementById('soBtnOpenCreditDrawer').style.display = (!!order && st === 'CREDIT_HOLD') ? 'inline-flex' : 'none';

        const canDeliver = !!order && ['APPROVED', 'PARTIALLY_DELIVERED', 'BILLED'].includes(st) && order.fulfillment_status !== 'DELIVERED';
        document.getElementById('soBtnOpenDeliveryModal').style.display = canDeliver ? 'inline-flex' : 'none';

        const draftInv = (order?.invoices || []).find((i) => i.status === 'DRAFT');
        const hasBillable = !!order && (!!draftInv || (order.lines || []).some((l) => Number(l.qty_delivered) > Number(l.qty_invoiced)));
        const canBill = !!order && !['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'QUOTE_EXPIRED', 'DRAFT', 'CREDIT_HOLD', 'CANCELLED', 'CLOSED'].includes(st) && hasBillable;
        document.getElementById('soBtnPostBill').style.display = canBill ? 'inline-flex' : 'none';
        document.getElementById('soBtnPostBillLabel').textContent = draftInv ? `Post Draft Bill (${draftInv.invoice_number})` : 'Create & Post Bill';

        const canCancel = !!order && ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'DRAFT', 'CREDIT_HOLD', 'APPROVED'].includes(st) && !(order.lines || []).some((l) => Number(l.qty_delivered) > 0);
        document.getElementById('soBtnCancelOrder').style.display = canCancel ? 'inline-flex' : 'none';

        document.getElementById('soSumPaid').textContent = order ? `${fmt(order.amount_billed)} / ${fmt(order.amount_paid)}` : '₱0.00 / ₱0.00';
        renderLinkedPanels(order);
    }

    // =========================================================================
    // CREATE / OPEN / SAVE / TRANSITIONS
    // =========================================================================
    window.createNewSalesOrder = (preselectCustomerId = null, docStage = 'ORDER') => {
        activeOrderId = null;
        activeDocStage = docStage === 'QUOTE' ? 'QUOTE' : 'ORDER';
        appliedVoucher = null;
        const cust = customers.find((c) => c.customer_id === preselectCustomerId);
        activeLines = [emptyLine(defaultTaxForCustomer(cust))];
        document.getElementById('soCustomerPoInput').value = '';
        document.getElementById('soDiscountRuleSelect').value = '';
        document.getElementById('soVoucherCodeInput').value = '';
        document.getElementById('soVoucherStatusChip').textContent = '';
        document.getElementById('soBtnClearVoucher').style.display = 'none';
        document.getElementById('soHeaderDiscType').disabled = false;
        document.getElementById('soHeaderDiscVal').disabled = false;
        document.getElementById('soHeaderDiscType').value = 'NONE';
        document.getElementById('soHeaderDiscVal').value = '0';
        document.getElementById('soSalespersonSelect').value = '';
        document.getElementById('soSpecialNotesInput').value = '';
        window.selectSoCustomer(preselectCustomerId || '', false);
        window.markSoDirty(false);
        updateStageUi(null);
        renderLineRows(false);
        window.switchSoTab('tab-builder');
    };

    window.openSalesOrder = (orderId) => {
        const o = orders.find((x) => x.sales_order_id === orderId);
        if (!o) return;
        activeOrderId = o.sales_order_id;
        activeDocStage = QUOTE_STATUSES.includes(String(o.status).toUpperCase()) || o.document_stage === 'QUOTE' ? 'QUOTE' : 'ORDER';

        if (o.voucher_code) {
            const matchedV = vouchersCatalog.find((vc) => String(vc.voucher_code).toUpperCase() === String(o.voucher_code).toUpperCase());
            appliedVoucher = matchedV
                ? { voucher_code: matchedV.voucher_code, discount_type: matchedV.discount_type, discount_value: Number(matchedV.discount_value), max_discount_amount: matchedV.max_discount_amount, discount_amount: Number(o.voucher_discount_amount || 0) }
                : { voucher_code: o.voucher_code, discount_amount: Number(o.voucher_discount_amount || 0), discount_type: 'FIXED', discount_value: Number(o.voucher_discount_amount || 0) };
        } else {
            appliedVoucher = null;
        }

        window.selectSoCustomer(o.customer_id, true);
        document.getElementById('soCustomerPoInput').value = o.customer_po_number || '';
        document.getElementById('soWarehouseSelect').value = o.source_warehouse_name || '';
        setSelectNormalizedTerms(o.payment_terms_code || 'NET30');
        document.getElementById('soOrderDateInput').value = String(o.order_date || '').slice(0, 10) || new Date().toISOString().slice(0, 10);
        document.getElementById('soDeliveryDateInput').value = String(o.requested_delivery_date || '').slice(0, 10);
        document.getElementById('soValidUntilInput').value = String(o.valid_until_date || '').slice(0, 10);
        document.getElementById('soShippingAddressInput').value = o.shipping_address || '';
        document.getElementById('soSalespersonSelect').value = o.salesperson_employee_id || '';
        document.getElementById('soSpecialNotesInput').value = o.special_notes || '';
        document.getElementById('soDiscountRuleSelect').value = o.discount_id || '';
        document.getElementById('soVoucherCodeInput').value = o.voucher_code || '';
        document.getElementById('soVoucherStatusChip').textContent = o.voucher_code ? `${o.voucher_code} (-${fmt(o.voucher_discount_amount || 0)})` : '';
        document.getElementById('soBtnClearVoucher').style.display = o.voucher_code ? 'inline-flex' : 'none';
        document.getElementById('soHeaderDiscType').value = o.discount_type || 'NONE';
        document.getElementById('soHeaderDiscVal').value = o.discount_value || 0;
        document.getElementById('soHeaderDiscType').disabled = !!o.discount_id;
        document.getElementById('soHeaderDiscVal').disabled = !!o.discount_id;

        activeLines = (o.lines || []).map((l) => ({
            sales_order_line_id: l.sales_order_line_id,
            inventory_item_id: l.inventory_item_id,
            sku: l.sku || '',
            item_name: l.item_name,
            quantity: Number(l.qty_ordered),
            uom: l.uom || 'PCS',
            qty_delivered: Number(l.qty_delivered || 0),
            qty_invoiced: Number(l.qty_invoiced || 0),
            unit_cost_snapshot: Number(l.unit_cost_snapshot || 0),
            unit_price: Number(l.unit_price || 0),
            discount_pct: Number(l.discount_pct || 0),
            tax_code: normalizeTaxCode(l.tax_code || 'VAT12_OUT'),
            pre_exempt_tax_code: null,
            bundle_id: l.bundle_id || null,
            bundle_code: l.bundle_code || null,
            bundle_name: l.bundle_name || null,
            is_custom: !l.inventory_item_id && !!l.item_name,
        }));
        if (!activeLines.length) activeLines.push(emptyLine());

        const readonly = !['DRAFT', 'QUOTE_DRAFT', 'QUOTE_SENT'].includes(String(o.status).toUpperCase());
        window.markSoDirty(false);
        updateStageUi(o);
        renderLineRows(readonly);
        window.refreshLiveCreditCheck();
        window.switchSoTab('tab-builder');
    };

    async function postJson(url, body, lockMsg) {
        setLock(true, lockMsg);
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                const firstFieldErr = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                throw new Error(firstFieldErr || json.message || 'Operation failed.');
            }
            if (json.atp_map || json.data?.atp_map) {
                mergeAtpMap(json.atp_map || json.data?.atp_map);
            }
            return json;
        } finally {
            setLock(false);
        }
    }

    function upsertOrder(o) {
        const idx = orders.findIndex((x) => x.sales_order_id === o.sales_order_id);
        if (idx >= 0) orders[idx] = o;
        else orders.unshift(o);
    }

    window.saveSalesOrderDraft = async (opts = {}) => {
        const customerId = document.getElementById('soCustomerIdInput')?.value;
        if (!customerId) {
            toast('error', 'Please select a Customer first.');
            return false;
        }
        const validLines = activeLines.filter((l) => l.item_name && Number(l.quantity) > 0);
        if (!validLines.length) {
            toast('error', 'Add at least one item with positive quantity.');
            return false;
        }

        const repSel = document.getElementById('soSalespersonSelect');
        const repOpt = repSel?.selectedOptions?.[0];

        try {
            const res = await postJson(cfg.routes.saveDraft, {
                sales_order_id: activeOrderId,
                document_stage: activeDocStage,
                customer_id: customerId,
                source_warehouse_name: document.getElementById('soWarehouseSelect')?.value || null,
                payment_terms_code: document.getElementById('soTermsSelect')?.value || 'NET30',
                order_date: document.getElementById('soOrderDateInput')?.value || null,
                requested_delivery_date: document.getElementById('soDeliveryDateInput')?.value || null,
                valid_until_date: document.getElementById('soValidUntilInput')?.value || null,
                shipping_address: document.getElementById('soShippingAddressInput')?.value || null,
                customer_po_number: document.getElementById('soCustomerPoInput')?.value || null,
                salesperson_employee_id: repSel?.value || null,
                salesperson_name: repOpt?.dataset?.name || null,
                special_notes: document.getElementById('soSpecialNotesInput')?.value || null,
                discount_id: document.getElementById('soDiscountRuleSelect')?.value || null,
                voucher_code: (document.getElementById('soVoucherCodeInput')?.value || '').trim() || null,
                discount_type: document.getElementById('soHeaderDiscType')?.value || 'NONE',
                discount_value: parseFloat(document.getElementById('soHeaderDiscVal')?.value || 0),
                lines: validLines,
            }, activeDocStage === 'QUOTE' ? 'Saving Sales Quote...' : 'Saving Sales Order Draft...');
            upsertOrder(res.data);
            renderDirectory();
            window.openSalesOrder(res.data.sales_order_id);
            if (!opts.silent) toast('success', res.message);
            return true;
        } catch (err) {
            toast('error', err.message);
            return false;
        }
    };

    async function ensureDraftSavedBeforeTransition() {
        if (!activeOrderId || isDirty) {
            return await window.saveSalesOrderDraft({ silent: true });
        }
        return true;
    }

    window.transitionQuoteStage = async (targetStatus) => {
        if (!(await ensureDraftSavedBeforeTransition())) return;
        try {
            const url = String(cfg.routes.quoteStage).replace('__ID__', encodeURIComponent(activeOrderId));
            const res = await postJson(url, { target_status: targetStatus }, 'Updating Quote Lifecycle Stage...');
            upsertOrder(res.data);
            renderDirectory();
            window.openSalesOrder(res.data.sales_order_id);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.cloneQuoteRevision = async () => {
        if (!activeOrderId) return;
        try {
            const url = String(cfg.routes.reviseQuote).replace('__ID__', encodeURIComponent(activeOrderId));
            const res = await postJson(url, {}, 'Cloning Quote Revision...');
            upsertOrder(res.data);
            renderDirectory();
            window.openSalesOrder(res.data.sales_order_id);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.confirmActiveSalesOrder = async () => {
        if (!(await ensureDraftSavedBeforeTransition())) return;
        try {
            const res = await postJson(cfg.routes.confirmOrder, { sales_order_id: activeOrderId }, 'Evaluating Credit, Redeeming Voucher & Reserving Stock...');
            upsertOrder(res.data);
            renderDirectory();
            window.openSalesOrder(res.data.sales_order_id);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    // =========================================================================
    // DRAWER WORKFLOWS: CREDIT REVIEW, DELIVERY DISPATCH, CANCEL ORDER
    // =========================================================================
    window.openSoCreditReviewDrawer = () => {
        const o = orders.find((x) => x.sales_order_id === activeOrderId);
        if (!o) return;
        const summary = document.getElementById('soCreditDrawerSummary');
        const ev = lastCreditEval || {};
        summary.innerHTML = `
            <div><strong>${esc(o.order_number)}</strong> &bull; ${esc(o.customer_name)} &bull; Order Total: <strong>${fmt(o.gross_total)}</strong></div>
            <div>Limit: <strong>${fmt(ev.credit_limit ?? o.credit_limit_snapshot)}</strong> &bull; Projected Exposure: <strong>${fmt(ev.projected_exposure ?? o.credit_exposure_snapshot)}</strong></div>
            ${(ev.reasons?.length || o.credit_breach_amount > 0) ? `<div style="color:#b91c1c;font-weight:700;">${esc((ev.reasons || ['Requires Accounting sign-off.']).join(' '))}</div>` : ''}
        `;
        document.getElementById('soCreditRemarksInput').value = '';
        document.getElementById('soCreditNewLimitInput').value = '';
        document.getElementById('soCreditClearHoldCheck').checked = false;
        openDrawerById('soCreditReviewDrawer');
    };

    window.submitSoCreditReview = async (decision) => {
        if (!activeOrderId) return;
        const remarks = (document.getElementById('soCreditRemarksInput')?.value || '').trim();
        const newLimRaw = document.getElementById('soCreditNewLimitInput')?.value;
        const clearHold = !!document.getElementById('soCreditClearHoldCheck')?.checked;
        try {
            const url = String(cfg.routes.creditReview).replace('__ID__', encodeURIComponent(activeOrderId));
            const res = await postJson(url, {
                decision,
                remarks: remarks || null,
                new_credit_limit: newLimRaw !== '' ? parseFloat(newLimRaw) : null,
                clear_customer_hold: clearHold,
            }, 'Processing Accounting Credit Decision...');
            window.closeAllSoDrawers();
            upsertOrder(res.data);
            renderDirectory();
            window.openSalesOrder(res.data.sales_order_id);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.openSoDeliveryDrawer = () => {
        const o = orders.find((x) => x.sales_order_id === activeOrderId);
        if (!o) return;
        const tbody = document.getElementById('soDeliveryLinesBody');
        tbody.innerHTML = (o.lines || []).map((l) => {
            const rem = Math.max(0, Number(l.qty_ordered) - Number(l.qty_delivered || 0));
            const invItem = items.find((i) => Number(i.id) === Number(l.inventory_item_id));
            const onHand = invItem ? Number(invItem.current_stock ?? 0) : null;
            const lowStock = onHand !== null && rem > onHand;
            return `
                <tr data-so-line-id="${esc(l.sales_order_line_id)}">
                    <td><strong>${esc(l.item_name)}</strong><div style="font-size:11px;color:#64748b;">${esc(l.sku || '')}${l.bundle_code ? ' &bull; ' + esc(l.bundle_code) : ''}</div></td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;">${rem.toFixed(2)} ${esc(l.uom)}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;">${onHand !== null ? `<span class="sq-atp-pill ${lowStock ? 'is-low' : ''}">${onHand.toFixed(2)}</span>` : '—'}</td>
                    <td><input type="number" step="0.01" min="0" max="${rem}" class="rfq-input so-del-qty-input" value="${rem.toFixed(2)}" style="text-align:right;"></td>
                </tr>
            `;
        }).join('');
        openDrawerById('soDeliveryDrawer');
    };

    window.submitSoDelivery = async (shipImmediately) => {
        if (!activeOrderId) return;
        const lines = [];
        document.querySelectorAll('#soDeliveryLinesBody tr').forEach((tr) => {
            const qty = parseFloat(tr.querySelector('.so-del-qty-input')?.value || 0);
            if (qty > 0) lines.push({ sales_order_line_id: tr.dataset.soLineId, qty });
        });
        if (!lines.length) return toast('error', 'Enter at least one positive dispatch quantity.');

        const url = String(cfg.routes.createDelivery).replace('__ID__', encodeURIComponent(activeOrderId));
        try {
            const res = await postJson(url, {
                ship_immediately: !!shipImmediately,
                carrier_name: document.getElementById('soDelCarrier')?.value || null,
                tracking_waybill: document.getElementById('soDelWaybill')?.value || null,
                lines,
            }, shipImmediately ? 'Dispatching Delivery, Posting COGS & Drafting Bill...' : 'Creating Stock-Out Delivery Order...');
            window.closeAllSoDrawers();
            if (res.data?.order) {
                upsertOrder(res.data.order);
                renderDirectory();
                window.openSalesOrder(res.data.order.sales_order_id);
            }
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.postOrCreateSoBill = async () => {
        const o = orders.find((x) => x.sales_order_id === activeOrderId);
        if (!o) return;
        const draft = (o.invoices || []).find((i) => i.status === 'DRAFT');
        await window.postDraftInvoiceFromOrder(draft ? draft.invoice_id : null);
    };

    window.postDraftInvoiceFromOrder = async (invoiceId) => {
        if (!activeOrderId) return;
        const url = String(cfg.routes.createBill).replace('__ID__', encodeURIComponent(activeOrderId));
        try {
            const res = await postJson(url, {
                invoice_id: invoiceId || null,
                target_status: 'POSTED',
            }, 'Posting Customer Bill to AR & GL...');
            if (res.data?.order) {
                upsertOrder(res.data.order);
                renderDirectory();
                window.openSalesOrder(res.data.order.sales_order_id);
            }
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.openSoCancelDrawer = () => {
        const o = orders.find((x) => x.sales_order_id === activeOrderId);
        if (!o) return;
        document.getElementById('soCancelOrderLabel').textContent = o.order_number;
        document.getElementById('soCancelReasonInput').value = '';
        openDrawerById('soCancelDrawer');
    };

    window.submitSoCancel = async () => {
        if (!activeOrderId) return;
        const reason = (document.getElementById('soCancelReasonInput')?.value || '').trim() || 'Cancelled by user';
        const url = String(cfg.routes.cancelOrder).replace('__ID__', encodeURIComponent(activeOrderId));
        try {
            const res = await postJson(url, { reason }, 'Cancelling & Releasing ATP / Vouchers...');
            window.closeAllSoDrawers();
            upsertOrder(res.data);
            renderDirectory();
            window.openSalesOrder(res.data.sales_order_id);
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    document.addEventListener('click', () => {
        document.getElementById('soCustomerComboDropdown')?.classList.remove('is-active');
        document.getElementById('soTopBannerCard')?.classList.remove('has-dropdown-open');
    });

    document.addEventListener('DOMContentLoaded', () => {
        renderDirectory();
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('order_id');
        const orderNum = params.get('order_number');
        const custId = params.get('customer_id');
        const stageParam = String(params.get('stage') || '').toUpperCase();
        if (orderId) {
            window.openSalesOrder(orderId);
        } else if (orderNum) {
            const found = orders.find((x) => x.order_number === orderNum || x.quote_number === orderNum);
            if (found) window.openSalesOrder(found.sales_order_id);
        } else if (custId || params.get('new')) {
            window.createNewSalesOrder(custId, stageParam === 'QUOTE' ? 'QUOTE' : 'ORDER');
        }
    });
})();
