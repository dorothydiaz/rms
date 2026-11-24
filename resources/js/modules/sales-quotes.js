(() => {
    const cfg = window.SalesQuoteConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let quotes = Array.isArray(cfg.data?.initialQuotes) ? [...cfg.data.initialQuotes] : [];
    const customers = Array.isArray(cfg.data?.initialCustomers) ? cfg.data.initialCustomers : [];
    const items = Array.isArray(cfg.data?.initialItems) ? cfg.data.initialItems : [];
    const taxCodes = Array.isArray(cfg.data?.initialTaxCodes) ? cfg.data.initialTaxCodes : [];
    const linkedOrderMap = cfg.data?.linkedOrderMap || {};

    let statusFilter = 'all';
    let searchQuery = '';
    let selectedIds = new Set();
    let activeQuoteId = null;
    let activeLines = [];

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    const fmt = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(type, msg) {
        const root = document.getElementById('sqToastContainer');
        if (!root) return alert(msg);
        const el = document.createElement('div');
        el.className = `rfq-toast ${type === 'error' ? 'error' : 'success'}`;
        el.innerHTML = `<i class="ph ${type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'}"></i><span>${esc(msg)}</span>`;
        root.appendChild(el);
        setTimeout(() => el.remove(), 3800);
    }

    function setLock(on, msg = 'Processing Sales Quote...') {
        const el = document.getElementById('sqGlobalLockOverlay');
        if (!el) return;
        document.getElementById('sqLockOverlayMessage').textContent = msg;
        el.classList.toggle('is-active', !!on);
    }

    function emptyLine() {
        return {
            inventory_item_id: null,
            sku: '',
            item_name: '',
            quantity: 1,
            uom: 'PCS',
            unit_cost_snapshot: 0,
            unit_price: 0,
            discount_pct: 0,
            tax_code: 'VAT12_OUT',
            line_notes: '',
        };
    }

    function hydrateUoms() {
        if (window.UomRegistry && typeof window.UomRegistry.autoHydrate === 'function') {
            window.UomRegistry.autoHydrate();
        }
    }

    window.switchSqTab = (tab) => {
        document.getElementById('sqTabBtnList')?.classList.toggle('active', tab === 'tab-list');
        document.getElementById('sqTabBtnBuilder')?.classList.toggle('active', tab === 'tab-builder');
        document.getElementById('sq-pane-list')?.classList.toggle('active', tab === 'tab-list');
        document.getElementById('sq-pane-builder')?.classList.toggle('active', tab === 'tab-builder');
    };

    function updateKpis() {
        const nonArchived = quotes.filter((q) => String(q.status).toUpperCase() !== 'ARCHIVED');
        document.getElementById('sqTabListCount').textContent = nonArchived.length;
        document.getElementById('kpiSqTotal').textContent = nonArchived.length;
        document.getElementById('kpiSqDraft').textContent = nonArchived.filter((q) => q.status === 'DRAFT').length;
        document.getElementById('kpiSqSent').textContent = nonArchived.filter((q) => q.status === 'SENT').length;
        document.getElementById('kpiSqConfirmed').textContent = nonArchived.filter((q) => q.status === 'CONFIRMED').length;
        document.getElementById('kpiSqConverted').textContent = nonArchived.filter((q) => q.status === 'CONVERTED').length;
    }

    function renderDirectory() {
        updateKpis();
        const body = document.getElementById('sqDirectoryBody');
        if (!body) return;

        const q = searchQuery.trim().toLowerCase();
        const rows = quotes.filter((row) => {
            const st = String(row.status || 'DRAFT').toUpperCase();
            if (st === 'ARCHIVED') return false;
            if (statusFilter !== 'all' && st !== statusFilter) return false;
            if (!q) return true;
            return [row.quote_number, row.customer_name, row.customer_reference, row.sales_order_number].some((v) => String(v || '').toLowerCase().includes(q));
        });

        if (!rows.length) {
            body.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">No sales quotes found. Click <strong>New Quote</strong> to draft a proposal.</td></tr>`;
            return;
        }

        body.innerHTML = rows.map((row) => {
            const st = String(row.status || 'DRAFT').toUpperCase();
            const soNum = row.sales_order_number || linkedOrderMap[row.quote_number] || '';
            const expiredTag = row.is_expired ? '<span class="status-pill expired" style="margin-left:4px;">EXPIRED</span>' : '';
            return `
                <tr>
                    <td><input type="checkbox" ${selectedIds.has(row.quote_id) ? 'checked' : ''} onchange="toggleSqSelect('${esc(row.quote_id)}', this.checked)"></td>
                    <td style="font-family:monospace;font-weight:700;color:#7c3aed;">
                        ${esc(row.quote_number)} <span style="font-size:10px;color:#64748b;">v${row.revision_no || 1}</span>
                    </td>
                    <td><strong>${esc(row.customer_name)}</strong></td>
                    <td><span class="rfq-label-badge">${esc(row.payment_terms_code || 'NET_30')}</span></td>
                    <td>${esc(String(row.valid_until_date || '').slice(0, 10) || '—')}</td>
                    <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmt(row.gross_total)}</td>
                    <td style="font-variant-numeric:tabular-nums;">${Number(row.estimated_margin_pct || 0).toFixed(1)}%</td>
                    <td>
                        <span class="status-pill ${st.toLowerCase()}">${esc(st)}</span>${expiredTag}
                        ${soNum ? `<div style="font-size:11px;font-family:monospace;color:#059669;font-weight:700;margin-top:2px;">&rarr; ${esc(soNum)}</div>` : ''}
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="openSalesQuote('${esc(row.quote_id)}')">Open</button>
                        ${st === 'CONFIRMED' && !soNum ? `<button type="button" class="rfq-btn rfq-btn-primary" style="padding:4px 9px;font-size:11px;" onclick="convertQuoteFromRow('${esc(row.quote_id)}')">Convert SO</button>` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.filterSqByStatus = (st) => {
        statusFilter = st;
        document.querySelectorAll('#sq-pane-list .rfq-kpi-card').forEach((c) => {
            c.classList.toggle('is-active-filter', c.dataset.kpiFilter === st);
        });
        renderDirectory();
    };

    window.handleSqSearch = (val) => {
        searchQuery = val || '';
        renderDirectory();
    };

    window.toggleSqSelect = (id, checked) => {
        if (checked) selectedIds.add(id);
        else selectedIds.delete(id);
        const bar = document.getElementById('sqBatchActionBar');
        if (bar) bar.style.display = selectedIds.size > 0 ? 'inline-flex' : 'none';
        const cnt = document.getElementById('sqSelectedCount');
        if (cnt) cnt.textContent = selectedIds.size;
    };

    window.toggleSelectAllQuotes = (checked) => {
        selectedIds.clear();
        if (checked) quotes.forEach((q) => { if (q.status !== 'ARCHIVED') selectedIds.add(q.quote_id); });
        document.getElementById('sqBatchActionBar').style.display = selectedIds.size > 0 ? 'inline-flex' : 'none';
        document.getElementById('sqSelectedCount').textContent = selectedIds.size;
        renderDirectory();
    };

    function renderCustomerComboList(filter = '') {
        const list = document.getElementById('sqCustomerComboList');
        if (!list) return;
        const q = filter.trim().toLowerCase();
        const matches = customers.filter((c) => !q || [c.customer_name, c.customer_code, c.tin].some((v) => String(v || '').toLowerCase().includes(q)));
        list.innerHTML = matches.length ? matches.map((c) => `
            <button type="button" class="rfq-vendor-combo-option" onclick="selectSqCustomer('${esc(c.customer_id)}')" style="display:flex;justify-content:space-between;width:100%;text-align:left;padding:8px 10px;border:none;background:transparent;cursor:pointer;">
                <div>
                    <div style="font-weight:700;font-size:0.82rem;color:#0f172a;">${esc(c.customer_name)}</div>
                    <div style="font-size:0.71rem;color:#64748b;">${esc(c.customer_code)} &bull; ${esc(c.payment_terms_code || 'NET_30')}</div>
                </div>
                <div style="text-align:right;font-size:0.71rem;font-weight:700;color:${c.credit_hold ? '#dc2626' : '#059669'};">
                    ${c.credit_hold ? 'HOLD' : 'Limit ' + fmt(c.credit_limit)}
                </div>
            </button>
        `).join('') : `<div style="padding:12px;text-align:center;color:#94a3b8;font-size:0.78rem;">No matching customers</div>`;
    }

    window.toggleSqCustomerCombo = (ev) => {
        ev?.stopPropagation();
        const dd = document.getElementById('sqCustomerComboDropdown');
        const card = document.getElementById('sqTopBannerCard');
        const open = !dd.classList.contains('is-active');
        dd.classList.toggle('is-active', open);
        card?.classList.toggle('has-dropdown-open', open);
        if (open) {
            renderCustomerComboList('');
            document.getElementById('sqCustomerSearchInput')?.focus();
        }
    };

    window.filterSqCustomers = (val) => renderCustomerComboList(val);

    window.selectSqCustomer = (customerId, keepTerms = false) => {
        const c = customers.find((x) => x.customer_id === customerId);
        document.getElementById('sqCustomerIdInput').value = c?.customer_id || '';
        document.getElementById('sqCustomerComboText').textContent = c ? `${c.customer_code} — ${c.customer_name}` : '-- Choose Customer from Masterlist --';
        document.getElementById('sqCustomerComboText').style.color = c ? '#0f172a' : '#64748b';
        document.getElementById('sqCustomerComboDropdown')?.classList.remove('is-active');
        document.getElementById('sqTopBannerCard')?.classList.remove('has-dropdown-open');

        if (c && !keepTerms && c.payment_terms_code) {
            document.getElementById('sqTermsSelect').value = c.payment_terms_code;
        }

        const chip = document.getElementById('sqCustomerCreditChip');
        if (chip && c) {
            chip.style.display = 'inline-flex';
            chip.className = `sq-credit-chip ${c.credit_hold ? 'is-hold' : 'is-ok'}`;
            chip.innerHTML = c.credit_hold
                ? `<i class="ph ph-warning"></i> On Credit Hold`
                : `<i class="ph ph-shield-check"></i> Avail Credit: ${fmt(c.available_credit ?? c.credit_limit)}`;
        } else if (chip) {
            chip.style.display = 'none';
        }
        window.syncSqMockup();
    };

    function renderLineRows() {
        const tbody = document.getElementById('sqItemsTbody');
        if (!tbody) return;

        document.getElementById('sqItemsCountBadge').textContent = `${activeLines.length} Line Item${activeLines.length === 1 ? '' : 's'}`;

        tbody.innerHTML = activeLines.map((line, idx) => {
            const matchedItem = items.find((i) => Number(i.id) === Number(line.inventory_item_id) || (line.sku && i.sku === line.sku));
            const atp = matchedItem ? Number(matchedItem.atp_qty ?? matchedItem.current_stock ?? 0) : null;
            const netUnit = Number(line.unit_price || 0) * (1 - (Number(line.discount_pct || 0) / 100));
            const cost = Number(line.unit_cost_snapshot || matchedItem?.cost_price || 0);
            const isBelowCost = cost > 0 && netUnit < cost;

            return `
                <tr data-line-idx="${idx}">
                    <td style="color:#94a3b8;font-family:monospace;">${idx + 1}</td>
                    <td>
                        <select class="rfq-select" onchange="onSqItemSelect(${idx}, this.value)" style="font-size:12px;">
                            <option value="">-- Select Item Master SKU --</option>
                            ${items.map((it) => `<option value="${it.id}" ${Number(line.inventory_item_id) === Number(it.id) ? 'selected' : ''}>[${esc(it.sku)}] ${esc(it.name)}</option>`).join('')}
                        </select>
                        ${!line.inventory_item_id ? `<input type="text" class="rfq-input" value="${esc(line.item_name)}" placeholder="Or custom description..." oninput="updateSqLine(${idx}, 'item_name', this.value)" style="margin-top:4px;font-size:11.5px;">` : ''}
                    </td>
                    <td>
                        ${atp !== null ? `<span class="sq-atp-pill ${atp < line.quantity ? 'is-low' : ''}">${atp.toFixed(1)}</span>` : '<span style="color:#94a3b8;">—</span>'}
                    </td>
                    <td>
                        <select class="form-control" data-uom-select data-uom-category="ALL" data-uom-current="${esc(line.uom || 'PCS')}" onchange="updateSqLine(${idx}, 'uom', this.value)"></select>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.0001" class="rfq-input" value="${line.quantity}" oninput="updateSqLine(${idx}, 'quantity', parseFloat(this.value)||0)" style="text-align:right;font-variant-numeric:tabular-nums;">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="rfq-input" value="${line.unit_price}" oninput="updateSqLine(${idx}, 'unit_price', parseFloat(this.value)||0)" style="text-align:right;font-variant-numeric:tabular-nums;">
                        ${isBelowCost ? `<div class="sq-margin-warn">Cost: ${fmt(cost)}</div>` : ''}
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" max="100" class="rfq-input" value="${line.discount_pct || 0}" oninput="updateSqLine(${idx}, 'discount_pct', parseFloat(this.value)||0)" style="text-align:right;">
                    </td>
                    <td>
                        <select class="po-tax-select ${line.tax_code === 'VAT12_OUT' ? 'vat' : 'is-none'}" onchange="updateSqLine(${idx}, 'tax_code', this.value)">
                            <option value="VAT12_OUT" ${line.tax_code === 'VAT12_OUT' ? 'selected' : ''}>12% VAT</option>
                            <option value="VAT_ZERO" ${line.tax_code === 'VAT_ZERO' ? 'selected' : ''}>0% Zero</option>
                            <option value="VAT_EXEMPT" ${line.tax_code === 'VAT_EXEMPT' ? 'selected' : ''}>Exempt</option>
                        </select>
                    </td>
                    <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;" class="sq-line-total-cell">₱0.00</td>
                    <td style="text-align:center;">
                        <button type="button" class="rfq-btn rfq-btn-danger" style="padding:4px 7px;" onclick="removeSqLine(${idx})"><i class="ph ph-trash"></i></button>
                    </td>
                </tr>
            `;
        }).join('');

        hydrateUoms();
        window.recalcSqTotals();
    }

    window.onSqItemSelect = (idx, itemId) => {
        const it = items.find((i) => String(i.id) === String(itemId));
        if (!it) return;
        activeLines[idx].inventory_item_id = it.id;
        activeLines[idx].sku = it.sku;
        activeLines[idx].item_name = it.name;
        activeLines[idx].uom = it.uom || 'PCS';
        activeLines[idx].unit_price = Number(it.selling_price || 0);
        activeLines[idx].unit_cost_snapshot = Number(it.cost_price || 0);
        renderLineRows();
    };

    window.updateSqLine = (idx, field, val) => {
        if (!activeLines[idx]) return;
        activeLines[idx][field] = val;
        if (field === 'tax_code') renderLineRows();
        else window.recalcSqTotals();
    };

    window.addSqItemRow = () => {
        activeLines.push(emptyLine());
        renderLineRows();
    };

    window.removeSqLine = (idx) => {
        activeLines.splice(idx, 1);
        if (!activeLines.length) activeLines.push(emptyLine());
        renderLineRows();
    };

    window.recalcSqTotals = () => {
        let linesSub = 0;
        let costTotal = 0;
        let belowCost = false;

        const rows = document.querySelectorAll('#sqItemsTbody tr');
        activeLines.forEach((l, idx) => {
            const qty = Math.max(0, Number(l.quantity || 0));
            const price = Math.max(0, Number(l.unit_price || 0));
            const discPct = Math.min(100, Math.max(0, Number(l.discount_pct || 0)));
            const sub = qty * price * (1 - discPct / 100);
            const cost = Number(l.unit_cost_snapshot || 0);
            if (cost > 0 && qty > 0 && (sub / qty) < cost) belowCost = true;
            linesSub += sub;
            costTotal += qty * cost;

            const rate = l.tax_code === 'VAT12_OUT' ? 0.12 : 0;
            const totCell = rows[idx]?.querySelector('.sq-line-total-cell');
            if (totCell) totCell.textContent = fmt(sub * (1 + rate));
        });

        const discType = document.getElementById('sqHeaderDiscType')?.value || 'NONE';
        const discVal = Number(document.getElementById('sqHeaderDiscVal')?.value || 0);
        let headerDisc = 0;
        if (discType === 'PERCENT') headerDisc = linesSub * (Math.min(100, Math.max(0, discVal)) / 100);
        else if (discType === 'FIXED') headerDisc = Math.min(linesSub, Math.max(0, discVal));

        const netSub = Math.max(0, linesSub - headerDisc);
        const factor = linesSub > 0 ? (netSub / linesSub) : 1;
        let taxSum = 0;
        activeLines.forEach((l) => {
            const qty = Math.max(0, Number(l.quantity || 0));
            const price = Math.max(0, Number(l.unit_price || 0));
            const discPct = Math.min(100, Math.max(0, Number(l.discount_pct || 0)));
            const sub = qty * price * (1 - discPct / 100) * factor;
            if (l.tax_code === 'VAT12_OUT') taxSum += sub * 0.12;
        });

        const gross = netSub + taxSum;
        const marginPct = netSub > 0 ? (((netSub - costTotal) / netSub) * 100) : 0;

        document.getElementById('sqSumSubtotal').textContent = fmt(netSub);
        document.getElementById('sqSumTax').textContent = fmt(taxSum);
        document.getElementById('sqSumGross').textContent = fmt(gross);
        document.getElementById('sqSumMargin').textContent = `${marginPct.toFixed(1)}%`;
        document.getElementById('sqMarginWarningHint').style.display = belowCost ? 'inline-flex' : 'none';
        window.syncSqMockup(netSub, taxSum, gross);
    };

    window.syncSqMockup = (netSub, taxSum, gross) => {
        const box = document.getElementById('sqLiveMockupBody');
        if (!box) return;
        const cId = document.getElementById('sqCustomerIdInput')?.value;
        const cust = customers.find((c) => c.customer_id === cId);
        const qNum = document.getElementById('sqRefDisplay')?.textContent || 'DRAFT';
        const validUntil = document.getElementById('sqValidUntilInput')?.value || '';
        const ref = document.getElementById('sqCustomerRefInput')?.value || '';

        box.innerHTML = `
            <div style="display:flex;justify-content:space-between;border-bottom:2px solid #9333ea;padding-bottom:8px;margin-bottom:10px;">
                <div>
                    <div style="font-weight:800;font-size:13px;color:#0f172a;">SALES QUOTATION</div>
                    <div style="font-family:monospace;color:#7c3aed;font-weight:700;">${esc(qNum)}</div>
                </div>
                <div style="text-align:right;font-size:10.5px;color:#64748b;">
                    <div>Valid Until: <strong>${esc(validUntil)}</strong></div>
                    <div>Terms: <strong>${esc(document.getElementById('sqTermsSelect')?.value || 'NET_30')}</strong></div>
                </div>
            </div>
            <div style="margin-bottom:10px;">
                <div style="font-size:10px;text-transform:uppercase;color:#64748b;font-weight:700;">Prepared For</div>
                <div style="font-weight:700;">${esc(cust ? cust.customer_name : 'Select Customer...')}</div>
                ${ref ? `<div style="color:#475569;">Ref: ${esc(ref)}</div>` : ''}
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:10.5px;margin-bottom:10px;">
                <thead><tr style="background:#f8fafc;border-bottom:1px solid #cbd5e1;"><th style="text-align:left;padding:4px;">Item</th><th style="text-align:right;padding:4px;">Qty</th><th style="text-align:right;padding:4px;">Total</th></tr></thead>
                <tbody>
                    ${activeLines.filter((l) => l.item_name).map((l) => `
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:4px;">${esc(l.item_name)}</td>
                            <td style="text-align:right;padding:4px;">${l.quantity} ${esc(l.uom)}</td>
                            <td style="text-align:right;padding:4px;">${fmt((l.quantity * l.unit_price) * (1 - (l.discount_pct || 0) / 100))}</td>
                        </tr>
                    `).join('') || '<tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:10px;">No line items</td></tr>'}
                </tbody>
            </table>
            <div style="border-top:1px solid #cbd5e1;padding-top:6px;text-align:right;">
                <div>Subtotal: <strong>${document.getElementById('sqSumSubtotal')?.textContent}</strong></div>
                <div>Output VAT: <strong>${document.getElementById('sqSumTax')?.textContent}</strong></div>
                <div style="font-size:13px;font-weight:800;color:#7c3aed;margin-top:3px;">Total: ${document.getElementById('sqSumGross')?.textContent}</div>
            </div>
        `;
    };

    function updateBuilderStageUi(quote) {
        const st = String(quote?.status || 'DRAFT').toUpperCase();
        document.getElementById('sqRefDisplay').textContent = quote?.quote_number || 'NEW DRAFT';
        const revBadge = document.getElementById('sqRevisionBadge');
        if (revBadge) {
            revBadge.style.display = quote ? 'inline-block' : 'none';
            revBadge.textContent = `v${quote?.revision_no || 1}`;
        }

        const pill = document.getElementById('sqStatusBadge');
        if (pill) {
            pill.className = `status-pill ${st.toLowerCase()}`;
            pill.innerHTML = `<i class="ph ph-dot"></i> ${esc(st)}`;
        }

        const stages = ['DRAFT', 'SENT', 'CONFIRMED', 'CONVERTED'];
        const curIdx = Math.max(0, stages.indexOf(st));
        document.querySelectorAll('#sqChevronPipeline .rfq-chevron-step').forEach((step, idx) => {
            step.classList.toggle('is-active', idx === curIdx);
            step.classList.toggle('is-done', idx < curIdx);
        });

        const canSend = !!quote && st === 'DRAFT';
        const canConfirm = !!quote && (st === 'DRAFT' || st === 'SENT');
        const canConvert = !!quote && st === 'CONFIRMED' && !quote.is_expired;
        document.getElementById('sqBtnSendQuote').style.display = canSend ? 'inline-flex' : 'none';
        document.getElementById('sqBtnConfirmQuote').style.display = canConfirm ? 'inline-flex' : 'none';
        document.getElementById('sqBtnConvertOrder').style.display = canConvert ? 'inline-flex' : 'none';
        document.getElementById('sqBtnReviseQuote').style.display = quote ? 'inline-flex' : 'none';

        const linked = document.getElementById('sqLinkedOrderChip');
        if (linked && quote?.sales_order_number) {
            linked.style.display = 'inline-flex';
            linked.href = `${cfg.routes.salesOrders}?order_number=${encodeURIComponent(quote.sales_order_number)}`;
            linked.innerHTML = `<i class="ph ph-arrow-square-out"></i> Sales Order ${esc(quote.sales_order_number)}`;
        } else if (linked) {
            linked.style.display = 'none';
        }
    }

    window.createNewSalesQuote = (preselectCustomerId = null) => {
        activeQuoteId = null;
        activeLines = [emptyLine()];
        document.getElementById('sqCustomerRefInput').value = '';
        document.getElementById('sqNotesInput').value = '';
        document.getElementById('sqHeaderDiscType').value = 'NONE';
        document.getElementById('sqHeaderDiscVal').value = '0';
        window.selectSqCustomer(preselectCustomerId || '');
        updateBuilderStageUi(null);
        renderLineRows();
        window.switchSqTab('tab-builder');
    };

    window.openSalesQuote = (quoteId) => {
        const q = quotes.find((x) => x.quote_id === quoteId);
        if (!q) return;
        activeQuoteId = q.quote_id;
        window.selectSqCustomer(q.customer_id, true);
        document.getElementById('sqCustomerRefInput').value = q.customer_reference || '';
        document.getElementById('sqWarehouseSelect').value = q.source_warehouse_name || '';
        document.getElementById('sqTermsSelect').value = q.payment_terms_code || 'NET_30';
        document.getElementById('sqQuoteDateInput').value = String(q.quote_date || '').slice(0, 10);
        document.getElementById('sqValidUntilInput').value = String(q.valid_until_date || '').slice(0, 10);
        document.getElementById('sqHeaderDiscType').value = q.discount_type || 'NONE';
        document.getElementById('sqHeaderDiscVal').value = q.discount_value || 0;
        document.getElementById('sqNotesInput').value = q.notes || '';
        activeLines = (q.lines || []).map((l) => ({
            inventory_item_id: l.inventory_item_id,
            sku: l.sku || '',
            item_name: l.item_name,
            quantity: Number(l.quantity),
            uom: l.uom || 'PCS',
            unit_cost_snapshot: Number(l.unit_cost_snapshot || 0),
            unit_price: Number(l.unit_price || 0),
            discount_pct: Number(l.discount_pct || 0),
            tax_code: l.tax_code || 'VAT12_OUT',
            line_notes: l.line_notes || '',
        }));
        if (!activeLines.length) activeLines.push(emptyLine());
        updateBuilderStageUi(q);
        renderLineRows();
        window.switchSqTab('tab-builder');
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
            if (!res.ok || !json.success) throw new Error(json.message || 'Request failed.');
            return json;
        } finally {
            setLock(false);
        }
    }

    function upsertQuote(q) {
        const idx = quotes.findIndex((x) => x.quote_id === q.quote_id);
        if (idx >= 0) quotes[idx] = q;
        else quotes.unshift(q);
    }

    window.saveSalesQuoteDraft = async () => {
        const customerId = document.getElementById('sqCustomerIdInput')?.value;
        if (!customerId) return toast('error', 'Please select a Customer first.');
        const validLines = activeLines.filter((l) => l.item_name && Number(l.quantity) > 0);
        if (!validLines.length) return toast('error', 'Add at least one valid line item.');

        try {
            const res = await postJson(cfg.routes.saveDraft, {
                quote_id: activeQuoteId,
                customer_id: customerId,
                source_warehouse_name: document.getElementById('sqWarehouseSelect')?.value || null,
                payment_terms_code: document.getElementById('sqTermsSelect')?.value || 'NET_30',
                quote_date: document.getElementById('sqQuoteDateInput')?.value || null,
                valid_until_date: document.getElementById('sqValidUntilInput')?.value || null,
                discount_type: document.getElementById('sqHeaderDiscType')?.value || 'NONE',
                discount_value: parseFloat(document.getElementById('sqHeaderDiscVal')?.value || 0),
                customer_reference: document.getElementById('sqCustomerRefInput')?.value || null,
                notes: document.getElementById('sqNotesInput')?.value || null,
                lines: validLines,
            }, 'Saving Sales Quote Draft...');
            upsertQuote(res.data);
            renderDirectory();
            window.openSalesQuote(res.data.quote_id);
            toast('success', res.message);
        } catch (err) {
            toast('error', err.message);
        }
    };

    window.transitionActiveQuote = async (targetStatus) => {
        if (!activeQuoteId) return toast('error', 'Save the quote draft first.');
        try {
            const res = await postJson(cfg.routes.transitionStatus, {
                quote_id: activeQuoteId,
                target_status: targetStatus,
            }, `Transitioning Quote to ${targetStatus}...`);
            upsertQuote(res.data);
            renderDirectory();
            window.openSalesQuote(res.data.quote_id);
            toast('success', res.message);
        } catch (err) {
            toast('error', err.message);
        }
    };

    window.reviseActiveQuote = async () => {
        if (!activeQuoteId) return;
        try {
            const res = await postJson(cfg.routes.reviseQuote, { quote_id: activeQuoteId }, 'Cloning Quote Revision...');
            upsertQuote(res.data);
            renderDirectory();
            window.openSalesQuote(res.data.quote_id);
            toast('success', res.message);
        } catch (err) {
            toast('error', err.message);
        }
    };

    window.convertActiveQuoteToOrder = async () => {
        if (!activeQuoteId) return;
        await window.convertQuoteFromRow(activeQuoteId);
    };

    window.convertQuoteFromRow = async (quoteId) => {
        try {
            const res = await postJson(cfg.routes.convertOrder, { quote_id: quoteId }, 'Converting Quote to Sales Order...');
            const so = res.data;
            const qIdx = quotes.findIndex((x) => x.quote_id === quoteId);
            if (qIdx >= 0) {
                quotes[qIdx].status = 'CONVERTED';
                quotes[qIdx].sales_order_id = so.sales_order_id;
                quotes[qIdx].sales_order_number = so.order_number;
            }
            toast('success', `${res.message} Redirecting to Sales Order...`);
            setTimeout(() => {
                window.location.href = `${cfg.routes.salesOrders}?order_id=${encodeURIComponent(so.sales_order_id)}`;
            }, 650);
        } catch (err) {
            toast('error', err.message);
        }
    };

    window.batchArchiveQuotes = async () => {
        if (!selectedIds.size) return;
        try {
            const res = await postJson(cfg.routes.batchArchive, { quote_ids: [...selectedIds] }, 'Archiving Quotes...');
            quotes = quotes.map((q) => selectedIds.has(q.quote_id) && q.status !== 'CONVERTED' ? { ...q, status: 'ARCHIVED' } : q);
            selectedIds.clear();
            renderDirectory();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    window.batchDeleteQuotes = async () => {
        if (!selectedIds.size) return;
        try {
            const res = await postJson(cfg.routes.batchDelete, { quote_ids: [...selectedIds] }, 'Deleting Draft Quotes...');
            quotes = quotes.filter((q) => !selectedIds.has(q.quote_id) || !['DRAFT', 'CANCELLED', 'EXPIRED'].includes(q.status));
            selectedIds.clear();
            renderDirectory();
            toast('success', res.message);
        } catch (err) { toast('error', err.message); }
    };

    document.addEventListener('click', () => {
        document.getElementById('sqCustomerComboDropdown')?.classList.remove('is-active');
        document.getElementById('sqTopBannerCard')?.classList.remove('has-dropdown-open');
    });

    document.addEventListener('DOMContentLoaded', () => {
        renderDirectory();
        const params = new URLSearchParams(window.location.search);
        const custId = params.get('customer_id');
        if (custId) window.createNewSalesQuote(custId);
    });
})();
