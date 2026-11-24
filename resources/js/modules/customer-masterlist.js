(() => {
    const cfg = window.CustomerMasterlistConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let customers = Array.isArray(cfg.data?.initialCustomers) ? [...cfg.data.initialCustomers] : [];
    let activeTier = 'ALL';
    let searchQuery = '';

    const esc = (str) => String(str ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));

    const fmtPhp = (val) => '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function toast(type, msg) {
        const root = document.getElementById('customerToastContainer');
        if (!root) return alert(msg);
        const el = document.createElement('div');
        el.className = `rfq-toast ${type === 'error' ? 'error' : 'success'}`;
        el.innerHTML = `<i class="ph ${type === 'error' ? 'ph-warning-circle' : 'ph-check-circle'}"></i><span>${esc(msg)}</span>`;
        root.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    function renderGrid() {
        const grid = document.getElementById('customerKanbanGrid');
        if (!grid) return;

        const q = searchQuery.trim().toLowerCase();
        const filtered = customers.filter((c) => {
            if (activeTier === 'CREDIT_HOLD' && !c.credit_hold) return false;
            if (activeTier !== 'ALL' && activeTier !== 'CREDIT_HOLD' && String(c.customer_tier || 'STANDARD').toUpperCase() !== activeTier) return false;
            if (!q) return true;
            return [c.customer_name, c.customer_code, c.tin, c.contact_person, c.email].some((v) => String(v || '').toLowerCase().includes(q));
        });

        if (!filtered.length) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;">No matching customers found. Click <strong>New Customer</strong> to register one.</div>`;
            return;
        }

        grid.innerHTML = filtered.map((c) => {
            const limit = Number(c.credit_limit || 0);
            const exposure = Number(c.total_exposure ?? c.current_balance ?? 0);
            const util = limit > 0 ? Math.min(100, Math.round((exposure / limit) * 100)) : 0;
            const isOver = limit > 0 && exposure > limit;
            const initials = String(c.customer_name || 'CU').trim().slice(0, 2).toUpperCase();

            return `
                <div class="vendor-kanban-card">
                    <div class="vendor-card-top">
                        <div class="vendor-card-avatar">${esc(initials)}</div>
                        <div class="vendor-card-title-wrap">
                            <h3 class="vendor-card-name">${esc(c.customer_name)}</h3>
                            <div class="vendor-card-meta">
                                <span style="font-family:monospace;font-weight:700;color:#7c3aed;">${esc(c.customer_code)}</span>
                                <span>&bull; ${esc(c.payment_terms_code || 'NET_30')}</span>
                            </div>
                        </div>
                        <span class="vendor-status-badge ${c.credit_hold ? 'status-hold' : 'status-active'}" style="padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:${c.credit_hold ? '#fef2f2' : '#f0fdf4'};color:${c.credit_hold ? '#b91c1c' : '#15803d'};">
                            ${c.credit_hold ? 'Credit Hold' : esc(c.customer_tier || 'STANDARD')}
                        </span>
                    </div>

                    <div style="margin:10px 0;padding:8px 10px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;font-size:0.78rem;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span style="color:#64748b;">Credit Limit:</span>
                            <strong style="font-variant-numeric:tabular-nums;">${fmtPhp(limit)}</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                            <span style="color:#64748b;">AR + Open Exposure:</span>
                            <strong style="color:${isOver ? '#dc2626' : '#0f172a'};font-variant-numeric:tabular-nums;">${fmtPhp(exposure)}</strong>
                        </div>
                        <div style="width:100%;height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;">
                            <div style="width:${util}%;height:100%;background:${isOver ? '#dc2626' : '#9333ea'};"></div>
                        </div>
                    </div>

                    <div style="font-size:0.76rem;color:#64748b;margin-bottom:10px;">
                        ${c.tin ? `<div>TIN: <strong>${esc(c.tin)}</strong></div>` : ''}
                        ${c.contact_person ? `<div>Contact: ${esc(c.contact_person)} ${c.phone ? `(${esc(c.phone)})` : ''}</div>` : ''}
                    </div>

                    <div style="display:flex;gap:6px;flex-wrap:wrap;border-top:1px solid #f1f5f9;padding-top:10px;">
                        <a href="${esc(cfg.routes?.salesQuotes)}?customer_id=${encodeURIComponent(c.customer_id)}" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;text-decoration:none;">
                            <i class="ph ph-file-text"></i> Quote
                        </a>
                        <a href="${esc(cfg.routes?.salesOrders)}?customer_id=${encodeURIComponent(c.customer_id)}" class="rfq-btn rfq-btn-primary" style="padding:4px 9px;font-size:11px;text-decoration:none;">
                            <i class="ph ph-plus-circle"></i> Order
                        </a>
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;" onclick="openCustomerModal('${esc(c.customer_id)}')">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 9px;font-size:11px;color:${c.credit_hold ? '#059669' : '#dc2626'};" onclick="toggleCustomerCreditHold('${esc(c.customer_id)}')">
                            ${c.credit_hold ? 'Lift Hold' : 'Hold'}
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    window.filterCustomerDirectory = (val) => {
        searchQuery = val || '';
        renderGrid();
    };

    window.setCustomerTierFilter = (tier, btn) => {
        activeTier = tier;
        document.querySelectorAll('#customerTierPills .vendor-filter-pill').forEach((p) => p.classList.remove('active'));
        btn?.classList.add('active');
        renderGrid();
    };

    window.openCustomerModal = (customerId = null) => {
        const modal = document.getElementById('customerFormModal');
        const c = customerId ? customers.find((x) => x.customer_id === customerId) : null;
        document.getElementById('customerModalTitle').innerHTML = c
            ? '<i class="ph ph-pencil-simple"></i> Edit Customer Profile'
            : '<i class="ph ph-user-plus"></i> Register Customer';
        document.getElementById('custIdInput').value = c?.customer_id || '';
        document.getElementById('custNameInput').value = c?.customer_name || '';
        document.getElementById('custCodeInput').value = c?.customer_code || '';
        document.getElementById('custTermsInput').value = c?.payment_terms_code || 'NET_30';
        document.getElementById('custCreditLimitInput').value = c?.credit_limit ?? 50000;
        document.getElementById('custTinInput').value = c?.tin || '';
        document.getElementById('custContactInput').value = c?.contact_person || '';
        document.getElementById('custEmailInput').value = c?.email || '';
        document.getElementById('custPhoneInput').value = c?.phone || '';
        document.getElementById('custTierInput').value = c?.customer_tier || 'STANDARD';
        document.getElementById('custTaxInput').value = c?.default_tax_code || 'VAT12_OUT';
        document.getElementById('custAddressInput').value = c?.shipping_address || c?.address || '';
        if (modal) modal.style.display = 'flex';
    };

    window.closeCustomerModal = () => {
        const modal = document.getElementById('customerFormModal');
        if (modal) modal.style.display = 'none';
    };

    window.submitCustomerForm = async () => {
        const name = document.getElementById('custNameInput')?.value?.trim();
        if (!name) return toast('error', 'Customer Name is required.');

        const payload = {
            customer_id: document.getElementById('custIdInput')?.value || null,
            customer_code: document.getElementById('custCodeInput')?.value?.trim() || null,
            customer_name: name,
            payment_terms_code: document.getElementById('custTermsInput')?.value || 'NET_30',
            credit_limit: parseFloat(document.getElementById('custCreditLimitInput')?.value || 0),
            tin: document.getElementById('custTinInput')?.value?.trim() || null,
            contact_person: document.getElementById('custContactInput')?.value?.trim() || null,
            email: document.getElementById('custEmailInput')?.value?.trim() || null,
            phone: document.getElementById('custPhoneInput')?.value?.trim() || null,
            customer_tier: document.getElementById('custTierInput')?.value || 'STANDARD',
            default_tax_code: document.getElementById('custTaxInput')?.value || 'VAT12_OUT',
            address: document.getElementById('custAddressInput')?.value?.trim() || null,
            shipping_address: document.getElementById('custAddressInput')?.value?.trim() || null,
        };

        try {
            const res = await fetch(cfg.routes.customersSave, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed to save customer.');

            const idx = customers.findIndex((x) => x.customer_id === json.data.customer_id);
            if (idx >= 0) customers[idx] = json.data;
            else customers.unshift(json.data);

            window.closeCustomerModal();
            renderGrid();
            toast('success', json.message);
        } catch (err) {
            toast('error', err.message);
        }
    };

    window.toggleCustomerCreditHold = async (customerId) => {
        const url = String(cfg.routes.toggleHold || '').replace('__ID__', encodeURIComponent(customerId));
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed to update hold.');

            const idx = customers.findIndex((x) => x.customer_id === customerId);
            if (idx >= 0) customers[idx].credit_hold = json.data.credit_hold;
            renderGrid();
            toast('success', json.message);
        } catch (err) {
            toast('error', err.message);
        }
    };

    document.addEventListener('DOMContentLoaded', renderGrid);
})();
