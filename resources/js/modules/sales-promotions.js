(() => {
    const cfg = window.SalesPromoConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    const fmt = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtDate = (d) => d ? String(d).slice(0, 10) : '';
    const fmtDt = (dt) => {
        if (!dt) return '—';
        const s = String(dt).replace('T', ' ').slice(0, 16);
        return s;
    };

    let telemetry = cfg.initialTelemetry || { usage_summary: {}, usage_logs: [], audit_logs: [] };

    function toast(type, msg) {
        const root = document.getElementById('spToastContainer');
        if (!root) return alert(msg);
        const el = document.createElement('div');
        el.className = `rfq-toast ${type === 'error' ? 'error' : 'success'}`;
        el.innerHTML = `<i class="ph ${type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'}"></i><span>${esc(msg)}</span>`;
        root.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body || {}),
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            const firstErr = json.errors ? Object.values(json.errors)[0]?.[0] : null;
            throw new Error(firstErr || json.message || 'Operation failed.');
        }
        return json;
    }

    function hydrateUoms() {
        if (window.UomRegistry && typeof window.UomRegistry.autoHydrate === 'function') {
            window.UomRegistry.autoHydrate();
        }
    }

    // =========================================================================
    // TAB SEPARATION & NAVIGATION
    // =========================================================================
    window.switchSpTab = (tab) => {
        const map = {
            'tab-catalog': { btn: 'spTabBtnCatalog', pane: 'sp-pane-catalog' },
            'tab-usage': { btn: 'spTabBtnUsage', pane: 'sp-pane-usage' },
            'tab-audit': { btn: 'spTabBtnAudit', pane: 'sp-pane-audit' },
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
    };

    window.jumpToPromoUsage = (promoId) => {
        const sel = document.getElementById('spUsagePromoFilter');
        if (sel) sel.value = promoId || 'ALL';
        window.renderUsageTab();
        window.switchSpTab('tab-usage');
    };

    // =========================================================================
    // SLIDE-OVER DRAWER LIFECYCLE
    // =========================================================================
    window.openPromoDrawer = () => {
        document.getElementById('spDrawerBackdrop')?.classList.add('is-open');
        document.getElementById('spPromoDrawer')?.classList.add('is-open');
    };

    window.closePromoDrawer = () => {
        document.getElementById('spDrawerBackdrop')?.classList.remove('is-open');
        document.getElementById('spPromoDrawer')?.classList.remove('is-open');
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.closePromoDrawer();
    });

    // =========================================================================
    // SHARED PROMO DURATION & SCHEDULE CONTROLS
    // =========================================================================
    function addDaysStr(days) {
        const d = new Date();
        d.setDate(d.getDate() + days);
        return d.toISOString().slice(0, 10);
    }

    window.setDurationMode = (mode) => {
        const m = String(mode || 'ALWAYS').toUpperCase() === 'SCHEDULED' ? 'SCHEDULED' : 'ALWAYS';
        const modeInput = document.getElementById('spDurMode');
        if (modeInput) modeInput.value = m;
        document.getElementById('spDurBtnAlways')?.classList.toggle('is-active', m === 'ALWAYS');
        document.getElementById('spDurBtnScheduled')?.classList.toggle('is-active', m === 'SCHEDULED');
        const fields = document.getElementById('spDurScheduleFields');
        if (fields) fields.style.display = m === 'SCHEDULED' ? 'flex' : 'none';
        if (m === 'SCHEDULED') {
            const fromEl = document.getElementById('spDurValidFrom');
            const untilEl = document.getElementById('spDurValidUntil');
            if (fromEl && !fromEl.value) fromEl.value = addDaysStr(0);
            if (untilEl && !untilEl.value) untilEl.value = addDaysStr(13);
        }
        window.updateDurationPreview();
    };

    window.applyDurationPreset = (preset) => {
        window.setDurationMode('SCHEDULED');
        const now = new Date();
        let fromStr = now.toISOString().slice(0, 10);
        let untilStr = fromStr;
        if (preset === '7d') untilStr = addDaysStr(6);
        else if (preset === '14d') untilStr = addDaysStr(13);
        else if (preset === '30d') untilStr = addDaysStr(29);
        else if (preset === 'month') {
            const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            untilStr = last.toISOString().slice(0, 10);
        }
        document.getElementById('spDurValidFrom').value = fromStr;
        document.getElementById('spDurValidUntil').value = untilStr;
        window.updateDurationPreview();
    };

    window.updateDurationPreview = () => {
        const el = document.getElementById('spDurationPreviewText');
        if (!el) return;
        const mode = document.getElementById('spDurMode')?.value || 'ALWAYS';
        if (mode !== 'SCHEDULED') {
            el.innerHTML = `<i class="ph ph-infinity"></i> Always Active (no expiration window)`;
            return;
        }
        const from = document.getElementById('spDurValidFrom')?.value;
        const until = document.getElementById('spDurValidUntil')?.value;
        if (from && until) {
            const ms = new Date(until) - new Date(from);
            const days = Math.round(ms / 86400000) + 1;
            if (days <= 0) {
                el.innerHTML = `<span style="color:#dc2626;"><i class="ph ph-warning"></i> End date must be on or after start date.</span>`;
            } else {
                el.innerHTML = `<i class="ph ph-calendar-check" style="color:#7c3aed;"></i> Active for <strong>${days} day${days === 1 ? '' : 's'}</strong> (${esc(from)} → ${esc(until)})`;
            }
        } else if (from || until) {
            el.innerHTML = `<i class="ph ph-calendar"></i> Window: ${esc(from || 'Any')} → ${esc(until || 'Open')}`;
        } else {
            el.innerHTML = `<i class="ph ph-calendar"></i> Select Start &amp; End dates`;
        }
    };

    function getDurationPayload() {
        const mode = document.getElementById('spDurMode')?.value || 'ALWAYS';
        if (mode !== 'SCHEDULED') {
            return { duration_mode: 'ALWAYS', valid_from: null, valid_until: null };
        }
        return {
            duration_mode: 'SCHEDULED',
            valid_from: document.getElementById('spDurValidFrom')?.value || null,
            valid_until: document.getElementById('spDurValidUntil')?.value || null,
        };
    }

    function hydrateDurationDrawer(record) {
        const from = fmtDate(record?.valid_from);
        const until = fmtDate(record?.valid_until);
        const mode = (record?.duration_mode === 'SCHEDULED' || from || until) ? 'SCHEDULED' : 'ALWAYS';
        document.getElementById('spDurValidFrom').value = from;
        document.getElementById('spDurValidUntil').value = until;
        window.setDurationMode(mode);
    }

    function renderDrawerMeta(record) {
        const banner = document.getElementById('spDrawerMetaBanner');
        if (!banner) return;
        if (!record) {
            banner.style.display = 'none';
            return;
        }
        banner.style.display = 'flex';
        banner.innerHTML = `
            <div>Created by <strong>${esc(record.created_by || 'System')}</strong> &bull; ${esc(fmtDt(record.created_at))}</div>
            <div>Last updated by <strong>${esc(record.updated_by || record.created_by || 'System')}</strong> &bull; Uses: <strong>${Number(record.uses_count ?? record.current_uses_count ?? 0)}</strong></div>
        `;
    }

    function durationCellHtml(item) {
        const st = item.schedule_status || 'ALWAYS_ACTIVE';
        const pillCls = st === 'ACTIVE_NOW' ? 'is-active'
            : st === 'SCHEDULED' ? 'is-scheduled'
            : st === 'EXPIRED' ? 'is-expired'
            : 'is-always';
        const range = (item.valid_from || item.valid_until)
            ? `<div style="font-size:10.5px;color:#64748b;margin-top:3px;">${esc(fmtDate(item.valid_from) || 'Any')} → ${esc(fmtDate(item.valid_until) || 'Open')}</div>`
            : '';
        return `<span class="sp-schedule-pill ${pillCls}">${esc(item.duration_label || 'Always Active')}</span>${range}`;
    }

    function auditBadgeClass(action) {
        const a = String(action || '').toUpperCase();
        if (a === 'CREATED') return 'is-created';
        if (a === 'UPDATED') return 'is-updated';
        if (a === 'ACTIVATED') return 'is-activated';
        if (a === 'DISABLED') return 'is-disabled';
        if (a === 'RELEASED') return 'is-released';
        return 'is-applied';
    }

    // =========================================================================
    // USAGE & AUDIT TRAIL SHARED RENDERERS
    // =========================================================================
    function syncUsageFilterOptions(catalog, idKey, codeKey, nameKey, allLabel) {
        const sel = document.getElementById('spUsagePromoFilter');
        if (!sel) return;
        const current = sel.value || 'ALL';
        sel.innerHTML = `<option value="ALL">${esc(allLabel)}</option>` +
            catalog.map((x) => `<option value="${esc(x[idKey])}">[${esc(x[codeKey])}] ${esc(x[nameKey])}</option>`).join('');
        sel.value = [...sel.options].some((o) => o.value === current) ? current : 'ALL';
    }

    window.renderUsageTab = () => {
        const logs = Array.isArray(telemetry.usage_logs) ? telemetry.usage_logs : [];
        const auditLogs = Array.isArray(telemetry.audit_logs) ? telemetry.audit_logs : [];
        document.getElementById('spTabUsageCount').textContent = logs.length;
        document.getElementById('spTabAuditCount').textContent = auditLogs.length;

        const tbody = document.getElementById('spUsageTbody');
        if (!tbody) return;
        const promoFilter = document.getElementById('spUsagePromoFilter')?.value || 'ALL';
        const q = (document.getElementById('spUsageSearchInput')?.value || '').trim().toLowerCase();

        const rows = logs.filter((r) => {
            if (promoFilter !== 'ALL' && String(r.promo_id) !== String(promoFilter)) return false;
            if (!q) return true;
            return [r.promo_code, r.order_number, r.customer_name, r.acted_by].some((s) => String(s || '').toLowerCase().includes(q));
        });

        const summaryEl = document.getElementById('spUsageTotalsSummary');
        if (summaryEl) {
            const totalAmt = rows.reduce((acc, r) => acc + Number(r.discount_amount ?? r.revenue_amount ?? 0), 0);
            summaryEl.textContent = `${rows.length} usage record(s) • Total: ${fmt(totalAmt)}`;
        }

        if (!rows.length) {
            const cols = cfg.mode === 'bundles' ? 8 : cfg.mode === 'vouchers' ? 7 : 8;
            tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:28px;color:#94a3b8;">No usage records found for this module yet.</td></tr>`;
            return;
        }

        const soBase = cfg.routes.salesOrderUrl || '/sales/orders';
        tbody.innerHTML = rows.map((r) => {
            const soLink = r.sales_order_id
                ? `<a href="${esc(soBase)}?order_id=${encodeURIComponent(r.sales_order_id)}" style="font-family:monospace;font-weight:700;color:#2563eb;text-decoration:none;">${esc(r.order_number)}</a>`
                : esc(r.order_number);

            if (cfg.mode === 'vouchers') {
                return `
                    <tr>
                        <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(r.promo_code)}</td>
                        <td>${soLink}</td>
                        <td><strong>${esc(r.customer_name)}</strong></td>
                        <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmt(r.discount_amount)}</td>
                        <td><span class="status-pill ${r.status === 'REDEEMED' ? 'approved' : 'cancelled'}">${esc(r.status)}</span></td>
                        <td style="font-size:11.5px;color:#475569;">${esc(fmtDt(r.used_at))}</td>
                        <td style="font-size:11.5px;color:#64748b;">${esc(fmtDt(r.released_at))}</td>
                    </tr>
                `;
            }
            if (cfg.mode === 'bundles') {
                return `
                    <tr>
                        <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(r.promo_code)}</td>
                        <td>${soLink}</td>
                        <td><strong>${esc(r.customer_name)}</strong></td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;">${Number(r.component_count || 0)} SKUs</td>
                        <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">${fmt(r.revenue_amount)}</td>
                        <td><span class="status-pill approved">${esc(r.status)}</span></td>
                        <td style="font-size:11.5px;color:#475569;">${esc(fmtDt(r.used_at))}</td>
                        <td style="font-size:11.5px;color:#475569;">${esc(r.acted_by || 'System')}</td>
                    </tr>
                `;
            }
            return `
                <tr>
                    <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(r.promo_code)}</td>
                    <td>${soLink}</td>
                    <td><strong>${esc(r.customer_name)}</strong></td>
                    <td style="text-align:right;font-weight:700;color:#b45309;font-variant-numeric:tabular-nums;">${fmt(r.discount_amount)}</td>
                    <td style="text-align:right;font-variant-numeric:tabular-nums;">${fmt(r.gross_total)}</td>
                    <td><span class="status-pill approved">${esc(r.status)}</span></td>
                    <td style="font-size:11.5px;color:#475569;">${esc(fmtDt(r.used_at))}</td>
                    <td style="font-size:11.5px;color:#475569;">${esc(r.acted_by || 'System')}</td>
                </tr>
            `;
        }).join('');
    };

    window.renderAuditTab = () => {
        const auditLogs = Array.isArray(telemetry.audit_logs) ? telemetry.audit_logs : [];
        document.getElementById('spTabAuditCount').textContent = auditLogs.length;

        const tbody = document.getElementById('spAuditTbody');
        if (!tbody) return;
        const actionFilter = document.getElementById('spAuditActionFilter')?.value || 'ALL';
        const q = (document.getElementById('spAuditSearchInput')?.value || '').trim().toLowerCase();

        const rows = auditLogs.filter((a) => {
            if (actionFilter !== 'ALL' && String(a.action).toUpperCase() !== actionFilter) return false;
            if (!q) return true;
            return [a.promo_code, a.promo_name, a.acted_by, a.summary, a.action].some((s) => String(s || '').toLowerCase().includes(q));
        });

        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:28px;color:#94a3b8;">No audit events recorded yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map((a) => `
            <tr>
                <td style="font-family:monospace;font-size:11.5px;color:#475569;white-space:nowrap;">${esc(fmtDt(a.acted_at || a.created_at))}</td>
                <td><strong>${esc(a.acted_by)}</strong></td>
                <td><span class="sp-audit-pill ${auditBadgeClass(a.action)}">${esc(String(a.action).replace(/_/g, ' '))}</span></td>
                <td>
                    <span style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(a.promo_code)}</span>
                    <div style="font-size:11px;color:#64748b;">${esc(a.promo_name)}</div>
                </td>
                <td style="font-size:12px;color:#334155;">${esc(a.summary)}</td>
            </tr>
        `).join('');
    };

    // =========================================================================
    // DISCOUNT RULES
    // =========================================================================
    if (cfg.mode === 'discounts') {
        let discounts = Array.isArray(cfg.initialDiscounts) ? [...cfg.initialDiscounts] : [];
        let query = '';

        function renderDiscounts() {
            const tbody = document.getElementById('spDiscountTbody');
            if (!tbody) return;

            const activeCount = discounts.filter((d) => d.is_active).length;
            const vatCount = discounts.filter((d) => d.vat_exempt_override && d.is_active).length;
            const scheduledCount = discounts.filter((d) => d.duration_mode === 'SCHEDULED').length;
            const totalUses = discounts.reduce((s, d) => s + Number(d.uses_count || 0), 0);
            const totalImpact = discounts.reduce((s, d) => s + Number(d.total_discount_given || 0), 0);

            document.getElementById('kpiDiscTotal').textContent = discounts.length;
            document.getElementById('kpiDiscActive').textContent = activeCount;
            document.getElementById('kpiDiscVatExempt').textContent = vatCount;
            document.getElementById('kpiDiscScheduled').textContent = scheduledCount;
            document.getElementById('kpiDiscUsesTotal').textContent = totalUses;
            document.getElementById('kpiDiscImpactTotal').textContent = fmt(totalImpact);
            document.getElementById('spTabCatalogCount').textContent = discounts.length;

            syncUsageFilterOptions(discounts, 'discount_id', 'discount_code', 'discount_name', 'All Discount Rules');

            const q = query.trim().toLowerCase();
            const rows = discounts.filter((d) => !q || [d.discount_code, d.discount_name, d.discount_category, d.created_by, d.updated_by].some((v) => String(v || '').toLowerCase().includes(q)));
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No discount rules found. Click <strong>New Discount Rule</strong> to configure commercial or BIR Senior/PWD discounts.</td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map((d) => `
                <tr>
                    <td>
                        <div style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(d.discount_code)}</div>
                        <div style="font-size:11px;color:#64748b;">${esc(d.discount_category || 'COMMERCIAL')}</div>
                    </td>
                    <td>
                        <strong>${esc(d.discount_name)}</strong>
                        ${d.vat_exempt_override ? '<div style="margin-top:2px;"><span class="rfq-label-badge" style="background:#fef3c7;color:#92400e;">BIR VAT-Exempt</span></div>' : ''}
                    </td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;">
                        ${d.discount_type === 'PERCENT' ? Number(d.discount_value) + '%' : fmt(d.discount_value)}
                        <div style="font-size:10.5px;color:#64748b;font-weight:500;">Min: ${fmt(d.min_order_amount)}</div>
                    </td>
                    <td>${durationCellHtml(d)}</td>
                    <td>
                        <button type="button" class="sp-usage-chip" onclick="jumpToPromoUsage('${esc(d.discount_id)}')" title="View usage records">
                            <i class="ph ph-chart-bar"></i> ${Number(d.uses_count || 0)} order${Number(d.uses_count || 0) === 1 ? '' : 's'}
                        </button>
                        <div style="font-size:10.5px;color:#64748b;margin-top:2px;">Saved ${fmt(d.total_discount_given || 0)}</div>
                    </td>
                    <td style="font-size:11.5px;">
                        <div>By: <strong>${esc(d.created_by || 'System')}</strong></div>
                        ${d.updated_by ? `<div style="color:#64748b;font-size:10.5px;">Ed: ${esc(d.updated_by)}</div>` : ''}
                    </td>
                    <td><span class="status-pill ${d.is_active ? 'approved' : 'cancelled'}">${d.is_active ? 'ACTIVE' : 'DISABLED'}</span></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="openDiscountDrawer('${esc(d.discount_id)}')">Edit</button>
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="toggleDiscount('${esc(d.discount_id)}')">${d.is_active ? 'Disable' : 'Enable'}</button>
                    </td>
                </tr>
            `).join('');
        }

        window.filterSpSearch = (val) => { query = val || ''; renderDiscounts(); };
        window.openDiscountDrawer = (id = null) => {
            const d = id ? discounts.find((x) => x.discount_id === id) : null;
            document.getElementById('spDrawerTitle').innerHTML = `<i class="ph ph-percent"></i> ${d ? 'Edit Discount Rule: ' + esc(d.discount_code) : 'New Discount Rule'}`;
            renderDrawerMeta(d);
            document.getElementById('spDiscId').value = d?.discount_id || '';
            document.getElementById('spDiscCode').value = d?.discount_code || '';
            document.getElementById('spDiscName').value = d?.discount_name || '';
            document.getElementById('spDiscCat').value = d?.discount_category || 'COMMERCIAL';
            document.getElementById('spDiscType').value = d?.discount_type || 'PERCENT';
            document.getElementById('spDiscVal').value = d?.discount_value ?? 10;
            document.getElementById('spDiscMinOrder').value = d?.min_order_amount ?? 0;
            document.getElementById('spDiscCap').value = d?.max_discount_cap ?? '';
            document.getElementById('spDiscTier').value = d?.applicable_customer_tier || 'ALL';
            document.getElementById('spDiscVatExempt').checked = !!d?.vat_exempt_override;
            document.getElementById('spDiscMgrApproval').checked = !!d?.requires_manager_approval;
            document.getElementById('spDiscNotes').value = d?.notes || '';
            hydrateDurationDrawer(d);
            window.openPromoDrawer();
        };
        window.saveDiscountRule = async () => {
            try {
                const capRaw = document.getElementById('spDiscCap').value;
                const res = await postJson(cfg.routes.saveDiscount, {
                    discount_id: document.getElementById('spDiscId').value || null,
                    discount_code: document.getElementById('spDiscCode').value,
                    discount_name: document.getElementById('spDiscName').value,
                    discount_category: document.getElementById('spDiscCat').value,
                    discount_type: document.getElementById('spDiscType').value,
                    discount_value: parseFloat(document.getElementById('spDiscVal').value || 0),
                    min_order_amount: parseFloat(document.getElementById('spDiscMinOrder').value || 0),
                    max_discount_cap: capRaw !== '' ? parseFloat(capRaw) : null,
                    applicable_customer_tier: document.getElementById('spDiscTier').value,
                    vat_exempt_override: document.getElementById('spDiscVatExempt').checked,
                    requires_manager_approval: document.getElementById('spDiscMgrApproval').checked,
                    notes: document.getElementById('spDiscNotes').value || null,
                    ...getDurationPayload(),
                });
                const idx = discounts.findIndex((x) => x.discount_id === res.data.discount_id);
                if (idx >= 0) discounts[idx] = res.data;
                else discounts.unshift(res.data);
                if (res.telemetry) telemetry = res.telemetry;
                window.closePromoDrawer();
                renderDiscounts();
                window.renderUsageTab();
                window.renderAuditTab();
                toast('success', res.message);
            } catch (err) { toast('error', err.message); }
        };
        window.toggleDiscount = async (id) => {
            try {
                const res = await postJson(String(cfg.routes.toggleDiscount).replace('__ID__', encodeURIComponent(id)));
                const idx = discounts.findIndex((x) => x.discount_id === id);
                if (idx >= 0) discounts[idx] = res.data;
                if (res.telemetry) telemetry = res.telemetry;
                renderDiscounts();
                window.renderUsageTab();
                window.renderAuditTab();
                toast('success', res.message);
            } catch (err) { toast('error', err.message); }
        };
        document.addEventListener('DOMContentLoaded', () => {
            renderDiscounts();
            window.renderUsageTab();
            window.renderAuditTab();
        });
    }

    // =========================================================================
    // PROMO VOUCHERS
    // =========================================================================
    if (cfg.mode === 'vouchers') {
        let vouchers = Array.isArray(cfg.initialVouchers) ? [...cfg.initialVouchers] : [];
        let query = '';

        function renderVouchers() {
            const tbody = document.getElementById('spVoucherTbody');
            if (!tbody) return;
            const totalRedeemed = vouchers.reduce((s, v) => s + Number(v.uses_count ?? v.current_uses_count ?? 0), 0);
            const totalImpact = vouchers.reduce((s, v) => s + Number(v.total_discount_given || 0), 0);

            document.getElementById('kpiVchTotal').textContent = vouchers.length;
            document.getElementById('kpiVchActive').textContent = vouchers.filter((v) => v.status === 'ACTIVE').length;
            document.getElementById('kpiVchRedeemed').textContent = totalRedeemed;
            document.getElementById('kpiVchImpactTotal').textContent = fmt(totalImpact);
            document.getElementById('kpiVchScheduled').textContent = vouchers.filter((v) => v.duration_mode === 'SCHEDULED').length;
            document.getElementById('kpiVchTargeted').textContent = vouchers.filter((v) => !!v.customer_id).length;
            document.getElementById('spTabCatalogCount').textContent = vouchers.length;

            syncUsageFilterOptions(vouchers, 'voucher_id', 'voucher_code', 'voucher_name', 'All Promo Vouchers');

            const q = query.trim().toLowerCase();
            const rows = vouchers.filter((v) => !q || [v.voucher_code, v.voucher_name, v.customer?.customer_name, v.created_by].some((s) => String(s || '').toLowerCase().includes(q)));
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">No vouchers found. Click <strong>New Voucher</strong> to create a promo code.</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map((v) => {
                const used = Number(v.uses_count ?? v.current_uses_count ?? 0);
                return `
                    <tr>
                        <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(v.voucher_code)}</td>
                        <td><strong>${esc(v.voucher_name)}</strong>${v.customer ? `<div style="font-size:11px;color:#64748b;">Exclusive: ${esc(v.customer.customer_name)}</div>` : ''}</td>
                        <td style="font-weight:700;font-variant-numeric:tabular-nums;">
                            ${v.discount_type === 'PERCENT' ? Number(v.discount_value) + '%' : fmt(v.discount_value)}
                            <div style="font-size:10.5px;color:#64748b;font-weight:500;">Min: ${fmt(v.min_spend_amount)}</div>
                        </td>
                        <td>${durationCellHtml(v)}</td>
                        <td>
                            <button type="button" class="sp-usage-chip" onclick="jumpToPromoUsage('${esc(v.voucher_id)}')" title="View redemption records">
                                <i class="ph ph-ticket"></i> ${used} / ${v.max_total_uses ?? '∞'}
                            </button>
                            <div style="font-size:10.5px;color:#64748b;margin-top:2px;">${v.max_uses_per_customer ?? 1} / customer</div>
                        </td>
                        <td style="font-size:11.5px;">
                            <div>By: <strong>${esc(v.created_by || 'System')}</strong></div>
                            ${v.updated_by ? `<div style="color:#64748b;font-size:10.5px;">Ed: ${esc(v.updated_by)}</div>` : ''}
                        </td>
                        <td><span class="status-pill ${v.status === 'ACTIVE' ? 'approved' : 'cancelled'}">${esc(v.status)}</span></td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="openVoucherDrawer('${esc(v.voucher_id)}')">Edit</button>
                            <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="toggleVoucher('${esc(v.voucher_id)}')">${v.status === 'ACTIVE' ? 'Disable' : 'Activate'}</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        window.filterSpSearch = (val) => { query = val || ''; renderVouchers(); };
        window.openVoucherDrawer = (id = null) => {
            const v = id ? vouchers.find((x) => x.voucher_id === id) : null;
            document.getElementById('spDrawerTitle').innerHTML = `<i class="ph ph-ticket"></i> ${v ? 'Edit Voucher: ' + esc(v.voucher_code) : 'New Promo Voucher'}`;
            renderDrawerMeta(v);
            document.getElementById('spVchId').value = v?.voucher_id || '';
            document.getElementById('spVchCode').value = v?.voucher_code || '';
            document.getElementById('spVchName').value = v?.voucher_name || '';
            document.getElementById('spVchType').value = v?.discount_type || 'FIXED';
            document.getElementById('spVchVal').value = v?.discount_value ?? 200;
            document.getElementById('spVchMinSpend').value = v?.min_spend_amount ?? 0;
            document.getElementById('spVchMaxDisc').value = v?.max_discount_amount ?? '';
            document.getElementById('spVchMaxTotal').value = v?.max_total_uses ?? '';
            document.getElementById('spVchMaxCust').value = v?.max_uses_per_customer ?? 1;
            document.getElementById('spVchCustomer').value = v?.customer_id || '';
            document.getElementById('spVchNotes').value = v?.notes || '';
            hydrateDurationDrawer(v);
            window.openPromoDrawer();
        };
        window.saveVoucherRule = async () => {
            try {
                const maxD = document.getElementById('spVchMaxDisc').value;
                const maxU = document.getElementById('spVchMaxTotal').value;
                const res = await postJson(cfg.routes.saveVoucher, {
                    voucher_id: document.getElementById('spVchId').value || null,
                    voucher_code: document.getElementById('spVchCode').value,
                    voucher_name: document.getElementById('spVchName').value,
                    discount_type: document.getElementById('spVchType').value,
                    discount_value: parseFloat(document.getElementById('spVchVal').value || 0),
                    min_spend_amount: parseFloat(document.getElementById('spVchMinSpend').value || 0),
                    max_discount_amount: maxD !== '' ? parseFloat(maxD) : null,
                    max_total_uses: maxU !== '' ? parseInt(maxU, 10) : null,
                    max_uses_per_customer: parseInt(document.getElementById('spVchMaxCust').value || '1', 10),
                    customer_id: document.getElementById('spVchCustomer').value || null,
                    notes: document.getElementById('spVchNotes').value || null,
                    ...getDurationPayload(),
                });
                const idx = vouchers.findIndex((x) => x.voucher_id === res.data.voucher_id);
                if (idx >= 0) vouchers[idx] = res.data;
                else vouchers.unshift(res.data);
                if (res.telemetry) telemetry = res.telemetry;
                window.closePromoDrawer();
                renderVouchers();
                window.renderUsageTab();
                window.renderAuditTab();
                toast('success', res.message);
            } catch (err) { toast('error', err.message); }
        };
        window.toggleVoucher = async (id) => {
            try {
                const res = await postJson(String(cfg.routes.toggleVoucher).replace('__ID__', encodeURIComponent(id)));
                const idx = vouchers.findIndex((x) => x.voucher_id === id);
                if (idx >= 0) vouchers[idx] = { ...vouchers[idx], ...res.data };
                if (res.telemetry) telemetry = res.telemetry;
                renderVouchers();
                window.renderUsageTab();
                window.renderAuditTab();
                toast('success', res.message);
            } catch (err) { toast('error', err.message); }
        };
        document.addEventListener('DOMContentLoaded', () => {
            renderVouchers();
            window.renderUsageTab();
            window.renderAuditTab();
        });
    }

    // =========================================================================
    // BUNDLE PROMOTIONS
    // =========================================================================
    if (cfg.mode === 'bundles') {
        let bundles = Array.isArray(cfg.initialBundles) ? [...cfg.initialBundles] : [];
        const items = Array.isArray(cfg.initialItems) ? cfg.initialItems : [];
        let bundleLines = [];
        let query = '';

        function renderBundles() {
            const tbody = document.getElementById('spBundleTbody');
            if (!tbody) return;
            const totalUses = bundles.reduce((s, b) => s + Number(b.uses_count || 0), 0);
            const totalRev = bundles.reduce((s, b) => s + Number(b.total_revenue || 0), 0);

            document.getElementById('kpiBndTotal').textContent = bundles.length;
            document.getElementById('kpiBndActive').textContent = bundles.filter((b) => b.is_active).length;
            document.getElementById('kpiBndLowAtp').textContent = bundles.filter((b) => b.is_active && Number(b.bundle_atp || 0) <= 0).length;
            document.getElementById('kpiBndScheduled').textContent = bundles.filter((b) => b.duration_mode === 'SCHEDULED').length;
            document.getElementById('kpiBndUsesTotal').textContent = totalUses;
            document.getElementById('kpiBndImpactTotal').textContent = fmt(totalRev);
            document.getElementById('spTabCatalogCount').textContent = bundles.length;

            syncUsageFilterOptions(bundles, 'bundle_id', 'bundle_code', 'bundle_name', 'All Bundle Promos');

            const q = query.trim().toLowerCase();
            const rows = bundles.filter((b) => !q || [b.bundle_code, b.bundle_name, b.created_by].some((s) => String(s || '').toLowerCase().includes(q)));
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8;">No bundles configured yet. Click <strong>New Bundle Promo</strong> to package SKUs.</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map((b) => {
                const comps = (b.items || []).map((c) => `
                    <span class="so-bundle-tag">${esc(c.quantity)}x ${esc(c.item_name)} (ATP ${Number(c.atp_qty || 0).toFixed(0)})${c.bom_code ? ' &bull; BOM' : ''}</span>
                `).join(' ');
                return `
                    <tr>
                        <td style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(b.bundle_code)}</td>
                        <td><strong>${esc(b.bundle_name)}</strong><div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">${comps}</div></td>
                        <td style="font-variant-numeric:tabular-nums;">
                            <div style="font-weight:700;">${fmt(b.bundle_price)}</div>
                            <div style="font-size:10.5px;color:#047857;">Retail ${fmt(b.regular_retail_total)} • Save ${fmt(b.savings_amount)}</div>
                        </td>
                        <td style="font-variant-numeric:tabular-nums;">
                            <div>${Number(b.margin_pct || 0).toFixed(1)}%</div>
                            <div style="font-size:10.5px;color:#64748b;">Cost ${fmt(b.component_cost_total)}</div>
                        </td>
                        <td>${durationCellHtml(b)}</td>
                        <td>
                            <button type="button" class="sp-usage-chip" onclick="jumpToPromoUsage('${esc(b.bundle_id)}')" title="View bundle sales records">
                                <i class="ph ph-gift"></i> ${Number(b.uses_count || 0)} sold
                            </button>
                            <div style="margin-top:4px;"><span class="sq-atp-pill ${Number(b.bundle_atp || 0) <= 0 ? 'is-low' : ''}">ATP ${Number(b.bundle_atp || 0).toFixed(0)}</span></div>
                        </td>
                        <td style="font-size:11.5px;">
                            <div>By: <strong>${esc(b.created_by || 'System')}</strong></div>
                            ${b.updated_by ? `<div style="color:#64748b;font-size:10.5px;">Ed: ${esc(b.updated_by)}</div>` : ''}
                        </td>
                        <td><span class="status-pill ${b.is_active ? 'approved' : 'cancelled'}">${b.is_active ? 'ACTIVE' : 'DISABLED'}</span></td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="openBundleDrawer('${esc(b.bundle_id)}')">Edit</button>
                            <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="toggleBundle('${esc(b.bundle_id)}')">${b.is_active ? 'Disable' : 'Activate'}</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderBundleLines() {
            const tbody = document.getElementById('spBundleLinesTbody');
            if (!tbody) return;
            tbody.innerHTML = bundleLines.map((row, idx) => `
                <tr>
                    <td>
                        <select class="rfq-select" onchange="onBundleCompSelect(${idx}, this.value)">
                            <option value="">-- Select Component SKU --</option>
                            ${items.map((it) => `<option value="${it.id}" ${Number(row.inventory_item_id) === Number(it.id) ? 'selected' : ''}>[${esc(it.sku)}] ${esc(it.name)} (ATP ${Number(it.atp_qty || 0).toFixed(0)})</option>`).join('')}
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0.01" class="rfq-input" value="${row.quantity}" oninput="updateBundleComp(${idx}, 'quantity', parseFloat(this.value)||1)" style="text-align:right;"></td>
                    <td><select class="form-control" data-uom-select data-uom-category="ALL" data-uom-current="${esc(row.uom || 'PCS')}" onchange="updateBundleComp(${idx}, 'uom', this.value)"></select></td>
                    <td style="text-align:center;"><button type="button" class="rfq-btn rfq-btn-danger" style="padding:4px 7px;" onclick="removeBundleComp(${idx})"><i class="ph ph-trash"></i></button></td>
                </tr>
            `).join('');
            hydrateUoms();
        }

        window.filterSpSearch = (val) => { query = val || ''; renderBundles(); };
        window.addBundleCompRow = () => { bundleLines.push({ inventory_item_id: '', quantity: 1, uom: 'PCS' }); renderBundleLines(); };
        window.removeBundleComp = (idx) => { bundleLines.splice(idx, 1); if (!bundleLines.length) window.addBundleCompRow(); else renderBundleLines(); };
        window.onBundleCompSelect = (idx, id) => {
            const it = items.find((i) => String(i.id) === String(id));
            bundleLines[idx].inventory_item_id = id ? Number(id) : '';
            if (it?.uom) bundleLines[idx].uom = it.uom;
            renderBundleLines();
        };
        window.updateBundleComp = (idx, field, val) => { if (bundleLines[idx]) bundleLines[idx][field] = val; };

        window.openBundleDrawer = (id = null) => {
            const b = id ? bundles.find((x) => x.bundle_id === id) : null;
            document.getElementById('spDrawerTitle').innerHTML = `<i class="ph ph-gift"></i> ${b ? 'Edit Bundle: ' + esc(b.bundle_code) : 'New Bundle Promo'}`;
            renderDrawerMeta(b);
            document.getElementById('spBndId').value = b?.bundle_id || '';
            document.getElementById('spBndCode').value = b?.bundle_code || '';
            document.getElementById('spBndName').value = b?.bundle_name || '';
            document.getElementById('spBndPrice').value = b?.bundle_price ?? 0;
            document.getElementById('spBndTax').value = b?.tax_code || 'VAT12_OUT';
            document.getElementById('spBndDesc').value = b?.description || '';
            bundleLines = b?.items?.length
                ? b.items.map((x) => ({ inventory_item_id: x.inventory_item_id, quantity: Number(x.quantity), uom: x.uom || 'PCS' }))
                : [{ inventory_item_id: '', quantity: 1, uom: 'PCS' }];
            renderBundleLines();
            hydrateDurationDrawer(b);
            window.openPromoDrawer();
        };
        window.saveBundleRule = async () => {
            const validItems = bundleLines.filter((r) => Number(r.inventory_item_id) > 0 && Number(r.quantity) > 0);
            if (!validItems.length) return toast('error', 'Select at least one component SKU.');
            try {
                const res = await postJson(cfg.routes.saveBundle, {
                    bundle_id: document.getElementById('spBndId').value || null,
                    bundle_code: document.getElementById('spBndCode').value,
                    bundle_name: document.getElementById('spBndName').value,
                    bundle_price: parseFloat(document.getElementById('spBndPrice').value || 0),
                    tax_code: document.getElementById('spBndTax').value,
                    description: document.getElementById('spBndDesc').value || null,
                    items: validItems,
                    ...getDurationPayload(),
                });
                const idx = bundles.findIndex((x) => x.bundle_id === res.data.bundle_id);
                if (idx >= 0) bundles[idx] = res.data;
                else bundles.unshift(res.data);
                if (res.telemetry) telemetry = res.telemetry;
                window.closePromoDrawer();
                renderBundles();
                window.renderUsageTab();
                window.renderAuditTab();
                toast('success', res.message);
            } catch (err) { toast('error', err.message); }
        };
        window.toggleBundle = async (id) => {
            try {
                const res = await postJson(String(cfg.routes.toggleBundle).replace('__ID__', encodeURIComponent(id)));
                const idx = bundles.findIndex((x) => x.bundle_id === id);
                if (idx >= 0) bundles[idx] = { ...bundles[idx], ...res.data };
                if (res.telemetry) telemetry = res.telemetry;
                renderBundles();
                window.renderUsageTab();
                window.renderAuditTab();
                toast('success', res.message);
            } catch (err) { toast('error', err.message); }
        };
        document.addEventListener('DOMContentLoaded', () => {
            renderBundles();
            window.renderUsageTab();
            window.renderAuditTab();
        });
    }
})();
