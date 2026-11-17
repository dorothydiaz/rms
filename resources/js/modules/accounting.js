/**
 * RMS Enterprise Accounting & Finance Module (accounting.js)
 * Coordinates Wave 1 General Ledger, Dashboard, Configuration, Tax Preview,
 * Financial Reports Preview, and System-Wide Drawers (Approvals, Audit Log, Attachments).
 */
(function () {
    'use strict';

    if (typeof window.AccountingConfig === 'undefined') {
        return;
    }

    const cfg = window.AccountingConfig;
    const state = {
        data: cfg.data || {},
        jeLines: [],
        activeJournal: null,
    };

    const escapeHtml = (val) => {
        if (val === null || val === undefined) return '';
        return String(val)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const fmtMoney = (val, cur) => {
        const num = Number(val || 0);
        const prefix = cur ? `${escapeHtml(cur)} ` : '';
        return `${prefix}${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    const notify = (type, message) => {
        if (window.Swal) {
            window.Swal.fire({
                toast: type === 'success',
                position: type === 'success' ? 'top-end' : 'center',
                icon: type === 'success' ? 'success' : 'error',
                title: message,
                showConfirmButton: type !== 'success',
                timer: type === 'success' ? 2800 : undefined,
            });
            return;
        }
        alert(message);
    };

    const routeWithId = (template, id) => String(template || '').replace('__ID__', encodeURIComponent(id));

    async function apiRequest(url, method, payload) {
        const res = await fetch(url, {
            method: method || 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': cfg.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload !== undefined ? JSON.stringify(payload) : undefined,
        });

        let json = {};
        try {
            json = await res.json();
        } catch (e) {
            json = { success: false, message: `Server returned status ${res.status}` };
        }

        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Operation failed.');
        }
        return json;
    }

    function statusBadgeHtml(status) {
        const s = String(status || 'DRAFT').toUpperCase();
        const cls = s === 'POSTED' || s === 'OPEN' ? 'acct-badge-posted'
            : s === 'APPROVED' ? 'acct-badge-approved'
                : s === 'REVERSED' || s === 'LOCKED' ? 'acct-badge-reversed'
                    : s === 'CLOSED' ? 'acct-badge-closed'
                        : 'acct-badge-draft';
        return `<span class="acct-badge ${cls}">${escapeHtml(s)}</span>`;
    }

    // =========================================================================
    // TAB SWITCHING & DRAWER / MODAL CONTROLS
    // =========================================================================

    function initTabs() {
        document.querySelectorAll('[data-acct-tab]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-acct-tab');
                const root = btn.closest('.acct-workspace') || document;
                root.querySelectorAll('[data-acct-tab]').forEach((b) => {
                    b.classList.toggle('active', b.getAttribute('data-acct-tab') === target);
                });
                root.querySelectorAll('[data-acct-pane]').forEach((pane) => {
                    pane.classList.toggle('is-active', pane.getAttribute('data-acct-pane') === target);
                });
            });
        });
    }

    function openBackdrop(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('is-open');
            el.setAttribute('aria-hidden', 'false');
        }
    }

    function closeBackdrop(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
        }
    }

    function initSystemToolbar() {
        document.addEventListener('click', (e) => {
            const opener = e.target.closest('[data-acct-open]');
            if (opener) {
                const key = opener.getAttribute('data-acct-open');
                if (key === 'approval-drawer') {
                    renderApprovalsDrawer();
                    openBackdrop('acctApprovalBackdrop');
                } else if (key === 'audit-drawer') {
                    renderAuditDrawer('');
                    openBackdrop('acctAuditBackdrop');
                } else if (key === 'attachment-modal') {
                    renderAttachmentsTable();
                    openBackdrop('acctAttachmentBackdrop');
                }
                return;
            }

            const closer = e.target.closest('[data-acct-close]');
            if (closer) {
                closeBackdrop(closer.getAttribute('data-acct-close'));
                return;
            }

            if (e.target.classList && e.target.classList.contains('acct-backdrop')) {
                closeBackdrop(e.target.id);
            }
        });

        const auditSearch = document.getElementById('acctAuditSearch');
        if (auditSearch) {
            auditSearch.addEventListener('input', (e) => renderAuditDrawer(e.target.value));
        }

        const attForm = document.getElementById('acctAttachmentForm');
        if (attForm) {
            attForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const payload = {
                        document_type: document.getElementById('attDocType').value,
                        document_number: document.getElementById('attDocNumber').value,
                        document_id: document.getElementById('attDocId').value,
                        file_name: document.getElementById('attFileName').value,
                    };
                    const res = await apiRequest(cfg.routes.attachmentStore, 'POST', payload);
                    state.data.attachments = [res.data, ...(state.data.attachments || [])];
                    const badge = document.getElementById('acctAttachmentCountBadge');
                    if (badge) badge.textContent = String(state.data.attachments.length);
                    attForm.reset();
                    renderAttachmentsTable();
                    notify('success', res.message || 'Attachment recorded.');
                } catch (err) {
                    notify('error', err.message);
                }
            });
        }
    }

    function renderApprovalsDrawer() {
        const container = document.getElementById('acctApprovalList');
        if (!container) return;
        const rows = state.data.pendingApprovals || [];
        if (!rows.length) {
            container.innerHTML = '<div class="acct-empty">No pending financial approvals in queue.</div>';
            return;
        }
        container.innerHTML = rows.map((req) => `
            <div class="acct-panel" style="margin-bottom:10px;">
                <div class="acct-panel-body" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div>
                        <div class="acct-mono" style="color:#9333ea;">${escapeHtml(req.approval_document_number || req.approval_document_id)}</div>
                        <div style="font-size:12px;color:#475569;">${escapeHtml(req.approval_document_type)} &bull; ${fmtMoney(req.approval_amount, req.approval_currency_code)}</div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button type="button" class="acct-row-btn" data-approval-act="APPROVED" data-approval-id="${escapeHtml(req.approval_request_id)}">
                            <i class="ph ph-check"></i> Approve &amp; Post
                        </button>
                        <button type="button" class="acct-row-btn danger" data-approval-act="REJECTED" data-approval-id="${escapeHtml(req.approval_request_id)}">
                            <i class="ph ph-x"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        container.querySelectorAll('[data-approval-act]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const reqId = btn.getAttribute('data-approval-id');
                const action = btn.getAttribute('data-approval-act');
                try {
                    const res = await apiRequest(routeWithId(cfg.routes.approvalAct, reqId), 'POST', { action });
                    notify('success', res.message);
                    window.location.reload();
                } catch (err) {
                    notify('error', err.message);
                }
            });
        });
    }

    function renderAuditDrawer(query) {
        const container = document.getElementById('acctAuditList');
        if (!container) return;
        const q = String(query || '').trim().toLowerCase();
        const logs = (state.data.auditLogs || []).filter((l) => {
            if (!q) return true;
            return [l.document_number, l.action, l.actor_name, l.module, l.summary]
                .some((v) => String(v || '').toLowerCase().includes(q));
        });
        if (!logs.length) {
            container.innerHTML = '<div class="acct-empty">No matching audit log entries.</div>';
            return;
        }
        container.innerHTML = logs.map((l) => `
            <div style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;justify-content:space-between;gap:8px;font-size:12px;">
                    <span class="acct-mono">${escapeHtml(l.document_number || l.module)}</span>
                    <span class="acct-badge acct-badge-draft">${escapeHtml(l.action)}</span>
                </div>
                <div style="font-size:12.5px;margin-top:3px;color:#0f172a;">${escapeHtml(l.summary)}</div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">
                    ${escapeHtml(l.actor_name || 'System')} &bull; ${escapeHtml(String(l.acted_at || '').slice(0, 19).replace('T', ' '))}
                </div>
            </div>
        `).join('');
    }

    function renderAttachmentsTable() {
        const tbody = document.getElementById('acctAttachmentTableBody');
        if (!tbody) return;
        const rows = state.data.attachments || [];
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="acct-empty">No attachments uploaded yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map((a) => `
            <tr>
                <td><span class="acct-mono">${escapeHtml(a.document_number || a.document_id)}</span> <span style="font-size:11px;color:#64748b;">(${escapeHtml(a.document_type)})</span></td>
                <td>${escapeHtml(a.file_name)}</td>
                <td>${escapeHtml(a.uploaded_by)}</td>
                <td style="font-size:11.5px;color:#64748b;">${escapeHtml(String(a.uploaded_at || '').slice(0, 16).replace('T', ' '))}</td>
            </tr>
        `).join('');
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    function initDashboard() {
        const jBody = document.getElementById('acctDashboardRecentJournalsBody');
        const aBody = document.getElementById('acctDashboardAuditBody');
        const cur = state.data.baseCurrency || 'PHP';

        if (jBody) {
            const list = (state.data.journals || []).slice(0, 8);
            jBody.innerHTML = list.length ? list.map((j) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(j.journal_number)}</td>
                    <td>${escapeHtml(String(j.journal_date || '').slice(0, 10))}</td>
                    <td>${escapeHtml(j.journal_type)}</td>
                    <td class="acct-num">${fmtMoney(j.total_debit, cur)}</td>
                    <td>${statusBadgeHtml(j.status)}</td>
                </tr>
            `).join('') : '<tr><td colspan="5" class="acct-empty">No journals posted yet.</td></tr>';
        }

        if (aBody) {
            const logs = (state.data.auditLogs || []).slice(0, 8);
            aBody.innerHTML = logs.length ? logs.map((l) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(l.document_number || l.module)}</td>
                    <td>${escapeHtml(l.action)}</td>
                    <td>${escapeHtml(l.actor_name)}</td>
                    <td style="font-size:11.5px;color:#64748b;">${escapeHtml(String(l.acted_at || '').slice(0, 16).replace('T', ' '))}</td>
                </tr>
            `).join('') : '<tr><td colspan="4" class="acct-empty">No audit records yet.</td></tr>';
        }
    }

    // =========================================================================
    // GENERAL LEDGER: BUILDER, COA, INQUIRY, RECURRING, PERIOD CLOSE
    // =========================================================================

    function blankLine() {
        const defaultBranch = (state.data.branches && state.data.branches[0])
            ? String(state.data.branches[0].branch_id)
            : 'BR-01';
        return {
            account_id: '',
            branch_id: defaultBranch,
            department_id: '',
            project_code: '',
            tax_code: '',
            transaction_debit: 0,
            transaction_credit: 0,
            description: '',
        };
    }

    function ensureTenRows(lines) {
        const list = (lines || []).map((l) => ({ ...l }));
        while (list.length < 10) {
            list.push(blankLine());
        }
        return list;
    }

    function populateHeaderSelectors() {
        const curEl = document.getElementById('jeCurrency');
        const brEl = document.getElementById('jeHeaderBranch');
        const tplEl = document.getElementById('jeTemplatePreset');
        const roleEl = document.getElementById('coaSystemRole');
        const inqAcc = document.getElementById('inquiryAccountSelect');
        const inqBr = document.getElementById('inquiryBranchSelect');

        if (curEl) {
            const curs = state.data.currencies || [{ currency_code: 'PHP', currency_name: 'Philippine Peso' }];
            curEl.innerHTML = curs.map((c) => `<option value="${escapeHtml(c.currency_code)}">${escapeHtml(c.currency_code)}</option>`).join('');
            curEl.value = state.data.baseCurrency || 'PHP';
            curEl.addEventListener('change', () => {
                const code = curEl.value;
                const fxEl = document.getElementById('jeFxRate');
                if (!fxEl) return;
                if (code === (state.data.baseCurrency || 'PHP')) {
                    fxEl.value = '1.000000';
                } else {
                    const rate = (state.data.exchangeRates || []).find((r) => r.from_currency_code === code);
                    if (rate) fxEl.value = Number(rate.exchange_rate).toFixed(6);
                }
                recomputeJournalTotals();
            });
        }

        if (brEl) {
            const branches = state.data.branches || [];
            brEl.innerHTML = branches.map((b) => `<option value="${escapeHtml(b.branch_id)}">${escapeHtml(b.branch_code || b.branch_id)} - ${escapeHtml(b.branch_name)}</option>`).join('');
        }

        if (tplEl) {
            const tpls = state.data.recurringTemplates || [];
            tplEl.innerHTML = '<option value="">-- Choose Recurring / Preset Template --</option>' +
                tpls.map((t) => `<option value="${escapeHtml(t.template_id)}">${escapeHtml(t.template_code)} - ${escapeHtml(t.template_name)}</option>`).join('');
        }

        if (roleEl) {
            const roles = state.data.systemRoles || [];
            roleEl.innerHTML = '<option value="">(None / Regular GL Account)</option>' +
                roles.map((r) => `<option value="${escapeHtml(r)}">${escapeHtml(r)}</option>`).join('');
        }

        if (inqAcc) {
            const accs = state.data.accounts || [];
            inqAcc.innerHTML = accs.map((a) => `<option value="${escapeHtml(a.account_id)}">${escapeHtml(a.account_code)} - ${escapeHtml(a.account_name)}</option>`).join('');
            inqAcc.addEventListener('change', renderGlInquiry);
        }

        if (inqBr) {
            const branches = state.data.branches || [];
            inqBr.innerHTML = '<option value="">All Branches</option>' +
                branches.map((b) => `<option value="${escapeHtml(b.branch_id)}">${escapeHtml(b.branch_code || b.branch_id)} - ${escapeHtml(b.branch_name)}</option>`).join('');
            inqBr.addEventListener('change', renderGlInquiry);
        }
    }

    function renderJeLinesGrid() {
        const tbody = document.getElementById('jeLinesBody');
        if (!tbody) return;

        const accOptions = '<option value="">-- Select Account --</option>' +
            (state.data.accounts || [])
                .filter((a) => a.is_active)
                .map((a) => `<option value="${escapeHtml(a.account_id)}">${escapeHtml(a.account_code)} - ${escapeHtml(a.account_name)}</option>`)
                .join('');

        const brOptions = (state.data.branches || [])
            .map((b) => `<option value="${escapeHtml(b.branch_id)}">${escapeHtml(b.branch_code || b.branch_id)}</option>`)
            .join('');

        const deptOptions = '<option value="">--</option>' +
            (state.data.departments || [])
                .map((d) => `<option value="${escapeHtml(d.department_id)}">${escapeHtml(d.code || d.name)}</option>`)
                .join('');

        const taxOptions = '<option value="">None</option>' +
            (state.data.taxCodes || [])
                .filter((t) => t.is_active)
                .map((t) => `<option value="${escapeHtml(t.tax_code)}">${escapeHtml(t.tax_code)} (${Number(t.rate_pct)}%)</option>`)
                .join('');

        const isReadOnly = Boolean(
            state.activeJournal &&
            (state.activeJournal.is_system_generated ||
                ['POSTED', 'REVERSED'].includes(String(state.activeJournal.status || '').toUpperCase()))
        );

        tbody.innerHTML = state.jeLines.map((line, idx) => `
            <tr data-line-idx="${idx}">
                <td class="acct-mono" style="color:#64748b;">${idx + 1}</td>
                <td><select class="acct-select" data-je-field="account_id" ${isReadOnly ? 'disabled' : ''}>${accOptions}</select></td>
                <td><select class="acct-select" data-je-field="branch_id" ${isReadOnly ? 'disabled' : ''}>${brOptions}</select></td>
                <td><select class="acct-select" data-je-field="department_id" ${isReadOnly ? 'disabled' : ''}>${deptOptions}</select></td>
                <td><input type="text" class="acct-input" data-je-field="project_code" value="${escapeHtml(line.project_code || '')}" placeholder="PROJ" ${isReadOnly ? 'readonly' : ''}></td>
                <td><select class="acct-select" data-je-field="tax_code" ${isReadOnly ? 'disabled' : ''}>${taxOptions}</select></td>
                <td><input type="number" step="0.01" min="0" class="acct-input acct-num" data-je-field="transaction_debit" value="${Number(line.transaction_debit || 0) || ''}" placeholder="0.00" ${isReadOnly ? 'readonly' : ''}></td>
                <td><input type="number" step="0.01" min="0" class="acct-input acct-num" data-je-field="transaction_credit" value="${Number(line.transaction_credit || 0) || ''}" placeholder="0.00" ${isReadOnly ? 'readonly' : ''}></td>
                <td><input type="text" class="acct-input" data-je-field="description" value="${escapeHtml(line.description || '')}" placeholder="Line memo" ${isReadOnly ? 'readonly' : ''}></td>
                <td style="text-align:center;">
                    <button type="button" class="acct-row-btn danger" data-je-del="${idx}" ${isReadOnly ? 'disabled' : ''} title="Remove line"><i class="ph ph-trash"></i></button>
                </td>
            </tr>
        `).join('');

        // Set selected values cleanly after innerHTML mount
        tbody.querySelectorAll('tr[data-line-idx]').forEach((tr) => {
            const idx = Number(tr.getAttribute('data-line-idx'));
            const line = state.jeLines[idx];
            if (!line) return;
            const setSel = (field, val) => {
                const el = tr.querySelector(`[data-je-field="${field}"]`);
                if (el && val !== undefined && val !== null) el.value = String(val);
            };
            setSel('account_id', line.account_id || '');
            setSel('branch_id', line.branch_id || '');
            setSel('department_id', line.department_id || '');
            setSel('tax_code', line.tax_code || '');
        });

        recomputeJournalTotals();
    }

    function recomputeJournalTotals() {
        const fx = Math.max(0.000001, Number(document.getElementById('jeFxRate')?.value || 1));
        const cur = state.data.baseCurrency || 'PHP';
        let dr = 0;
        let cr = 0;
        state.jeLines.forEach((l) => {
            dr += Math.round(Number(l.transaction_debit || 0) * fx * 100) / 100;
            cr += Math.round(Number(l.transaction_credit || 0) * fx * 100) / 100;
        });

        const drEl = document.getElementById('jeSumDebit');
        const crEl = document.getElementById('jeSumCredit');
        const badgeEl = document.getElementById('jeBalanceBadge');
        if (drEl) drEl.textContent = fmtMoney(dr, cur);
        if (crEl) crEl.textContent = fmtMoney(cr, cur);
        if (badgeEl) {
            const diff = Math.round((dr - cr) * 100) / 100;
            if (Math.abs(diff) < 0.005) {
                badgeEl.className = 'acct-badge acct-badge-balanced';
                badgeEl.textContent = '0.00 BALANCED';
            } else {
                badgeEl.className = 'acct-badge acct-badge-warn';
                badgeEl.textContent = `${fmtMoney(Math.abs(diff), '')} OUT OF BALANCE`;
            }
        }
    }

    function resetJournalBuilder() {
        state.activeJournal = null;
        state.jeLines = ensureTenRows([]);
        const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        setVal('jeJournalId', '');
        setVal('jeDate', new Date().toISOString().slice(0, 10));
        setVal('jeType', 'MANUAL');
        setVal('jeCurrency', state.data.baseCurrency || 'PHP');
        setVal('jeFxRate', '1.000000');
        setVal('jeDescription', '');
        setVal('jeReversalInfo', 'Not reversed');

        const badge = document.getElementById('jeStatusBadge');
        if (badge) { badge.className = 'acct-badge acct-badge-draft'; badge.textContent = 'NEW DRAFT'; }
        const num = document.getElementById('jeNumberDisplay');
        if (num) num.textContent = 'AUTO-NUMBERED';
        const srcBadge = document.getElementById('jeSourceLinkBadge');
        if (srcBadge) srcBadge.style.display = 'none';
        const ro = document.getElementById('jeReadOnlyBanner');
        if (ro) ro.style.display = 'none';
        const revBtn = document.getElementById('btnJeOpenReverseModal');
        if (revBtn) revBtn.style.display = 'none';
        const saveBtn = document.getElementById('btnJeSaveDraft');
        const postBtn = document.getElementById('btnJePost');
        if (saveBtn) saveBtn.style.display = '';
        if (postBtn) postBtn.style.display = '';

        ['jeDate', 'jeType', 'jeCurrency', 'jeFxRate', 'jeDescription'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) { el.removeAttribute('readonly'); el.removeAttribute('disabled'); }
        });

        renderJeLinesGrid();
    }

    function loadJournalIntoBuilder(journalId) {
        const j = (state.data.journals || []).find((row) => row.journal_id === journalId);
        if (!j) return;
        state.activeJournal = j;
        state.jeLines = ensureTenRows((j.lines || []).map((l) => ({
            account_id: l.account_id,
            branch_id: l.branch_id,
            department_id: l.department_id || '',
            project_code: l.project_code || '',
            tax_code: l.tax_code || '',
            transaction_debit: Number(l.transaction_debit || 0),
            transaction_credit: Number(l.transaction_credit || 0),
            description: l.description || '',
        })));

        const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        setVal('jeJournalId', j.journal_id);
        setVal('jeDate', String(j.journal_date || '').slice(0, 10));
        setVal('jeType', j.journal_type || 'MANUAL');
        setVal('jeCurrency', j.currency_code || state.data.baseCurrency || 'PHP');
        setVal('jeFxRate', Number(j.exchange_rate || 1).toFixed(6));
        setVal('jeDescription', j.description || '');
        setVal('jeReversalInfo', j.reversal_reason ? `Reversed: ${j.reversal_reason}` : 'Not reversed');

        const statusUpper = String(j.status || 'DRAFT').toUpperCase();
        const badge = document.getElementById('jeStatusBadge');
        if (badge) badge.outerHTML = statusBadgeHtml(statusUpper).replace('<span class="', '<span id="jeStatusBadge" class="');
        const num = document.getElementById('jeNumberDisplay');
        if (num) num.textContent = j.journal_number;

        const srcBadge = document.getElementById('jeSourceLinkBadge');
        if (srcBadge) {
            if (j.is_system_generated || j.source_document_number) {
                srcBadge.style.display = 'inline-flex';
                srcBadge.textContent = `SOURCE: ${j.source_module || 'SYS'} • ${j.source_document_number || j.source_document_type}`;
            } else {
                srcBadge.style.display = 'none';
            }
        }

        const isReadOnly = Boolean(j.is_system_generated || ['POSTED', 'REVERSED'].includes(statusUpper));
        const ro = document.getElementById('jeReadOnlyBanner');
        const roText = document.getElementById('jeReadOnlyText');
        if (ro) {
            ro.style.display = isReadOnly ? 'flex' : 'none';
            if (roText) {
                roText.textContent = j.is_system_generated
                    ? `System-Generated Entry (${j.source_document_number || j.source_module}):`
                    : `Immutable ${statusUpper} Journal:`;
            }
        }

        const revBtn = document.getElementById('btnJeOpenReverseModal');
        if (revBtn) revBtn.style.display = statusUpper === 'POSTED' ? '' : 'none';

        const saveBtn = document.getElementById('btnJeSaveDraft');
        const postBtn = document.getElementById('btnJePost');
        if (saveBtn) saveBtn.style.display = isReadOnly ? 'none' : '';
        if (postBtn) postBtn.style.display = isReadOnly ? 'none' : '';

        ['jeDate', 'jeType', 'jeCurrency', 'jeFxRate', 'jeDescription'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (isReadOnly) {
                if (el.tagName === 'SELECT') el.setAttribute('disabled', 'disabled');
                else el.setAttribute('readonly', 'readonly');
            } else {
                el.removeAttribute('readonly');
                el.removeAttribute('disabled');
            }
        });

        renderJeLinesGrid();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function submitJournal(targetStatus) {
        const nonZero = state.jeLines.filter((l) => Number(l.transaction_debit || 0) > 0 || Number(l.transaction_credit || 0) > 0);
        if (nonZero.length < 2) {
            notify('error', 'Please enter at least 2 balanced Debit/Credit lines before saving.');
            return;
        }
        try {
            const payload = {
                journal_id: document.getElementById('jeJournalId')?.value || null,
                journal_date: document.getElementById('jeDate')?.value,
                journal_type: document.getElementById('jeType')?.value || 'MANUAL',
                branch_id: document.getElementById('jeHeaderBranch')?.value || 'BR-01',
                currency_code: document.getElementById('jeCurrency')?.value || 'PHP',
                exchange_rate: Number(document.getElementById('jeFxRate')?.value || 1),
                description: document.getElementById('jeDescription')?.value || '',
                target_status: targetStatus,
                lines: nonZero,
            };
            const res = await apiRequest(cfg.routes.journalSave, 'POST', payload);
            notify('success', res.message);
            window.location.reload();
        } catch (err) {
            notify('error', err.message);
        }
    }

    function renderJournalRegister() {
        const tbody = document.getElementById('jeRegisterBody');
        if (!tbody) return;
        const q = String(document.getElementById('jeRegisterSearch')?.value || '').trim().toLowerCase();
        const typeF = document.getElementById('jeRegisterTypeFilter')?.value || '';
        const statusF = document.getElementById('jeRegisterStatusFilter')?.value || '';
        const cur = state.data.baseCurrency || 'PHP';

        const list = (state.data.journals || []).filter((j) => {
            if (typeF && j.journal_type !== typeF) return false;
            if (statusF && j.status !== statusF) return false;
            if (q && !(`${j.journal_number} ${j.description || ''} ${j.source_document_number || ''}`).toLowerCase().includes(q)) return false;
            return true;
        });

        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="acct-empty">No journal entries match your filters.</td></tr>';
            return;
        }

        tbody.innerHTML = list.map((j) => {
            const statusUpper = String(j.status || '').toUpperCase();
            const isDraft = statusUpper === 'DRAFT';
            const isPosted = statusUpper === 'POSTED';
            return `
                <tr>
                    <td class="acct-mono">
                        <button type="button" class="acct-row-btn" data-je-load="${escapeHtml(j.journal_id)}">${escapeHtml(j.journal_number)}</button>
                    </td>
                    <td>${escapeHtml(String(j.journal_date || '').slice(0, 10))}</td>
                    <td>
                        ${escapeHtml(j.journal_type)}
                        ${j.is_system_generated ? '<span class="acct-badge acct-badge-system" style="margin-left:4px;">SYS</span>' : ''}
                    </td>
                    <td class="acct-mono" style="font-size:11.5px;">${escapeHtml(j.source_document_number || '-')}</td>
                    <td>${escapeHtml(j.description || '-')}</td>
                    <td class="acct-num">${fmtMoney(j.total_debit, cur)}</td>
                    <td class="acct-num">${fmtMoney(j.total_credit, cur)}</td>
                    <td>${statusBadgeHtml(statusUpper)}</td>
                    <td style="white-space:nowrap;">
                        <button type="button" class="acct-row-btn" data-je-load="${escapeHtml(j.journal_id)}">${isDraft ? 'Edit' : 'View'}</button>
                        ${isDraft ? `<button type="button" class="acct-row-btn" data-je-post-id="${escapeHtml(j.journal_id)}">Post</button>` : ''}
                        ${isDraft ? `<button type="button" class="acct-row-btn danger" data-je-del-id="${escapeHtml(j.journal_id)}">Delete</button>` : ''}
                        ${isPosted ? `<button type="button" class="acct-row-btn danger" data-je-rev-id="${escapeHtml(j.journal_id)}">Reverse</button>` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderCoaTable() {
        const tbody = document.getElementById('coaTableBody');
        if (!tbody) return;
        const q = String(document.getElementById('coaSearchInput')?.value || '').trim().toLowerCase();
        const typeF = document.getElementById('coaTypeFilter')?.value || '';
        const cur = state.data.baseCurrency || 'PHP';

        const list = (state.data.accounts || []).filter((a) => {
            if (typeF && a.account_type !== typeF) return false;
            if (q && !(`${a.account_code} ${a.account_name} ${a.system_role || ''}`).toLowerCase().includes(q)) return false;
            return true;
        });

        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="acct-empty">No Chart of Accounts match your filters.</td></tr>';
            return;
        }

        tbody.innerHTML = list.map((a) => `
            <tr>
                <td class="acct-mono">${escapeHtml(a.account_code)}</td>
                <td style="font-weight:600;">${escapeHtml(a.account_name)}</td>
                <td>${escapeHtml(a.account_type)}</td>
                <td>${escapeHtml(a.normal_balance)}</td>
                <td style="font-size:11.5px;color:#64748b;">${escapeHtml(a.cash_flow_category || 'OPERATING')}</td>
                <td>${a.system_role ? `<span class="acct-badge acct-badge-role"><i class="ph ph-lock-simple"></i> ${escapeHtml(a.system_role)}</span>` : '<span style="color:#94a3b8;">-</span>'}</td>
                <td class="acct-num">${fmtMoney(a.net_balance, cur)}</td>
                <td>${a.is_active ? '<span class="acct-badge acct-badge-posted">ACTIVE</span>' : '<span class="acct-badge acct-badge-closed">INACTIVE</span>'}</td>
                <td style="white-space:nowrap;">
                    <button type="button" class="acct-row-btn" data-coa-edit="${escapeHtml(a.account_id)}">Edit</button>
                    ${!a.system_role ? `<button type="button" class="acct-row-btn danger" data-coa-del="${escapeHtml(a.account_id)}">Delete</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    function openCoaModal(accountId) {
        const modalTitle = document.getElementById('coaModalTitle');
        const acc = accountId ? (state.data.accounts || []).find((a) => a.account_id === accountId) : null;
        const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        const setDisabled = (id, flag) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (flag) el.setAttribute('disabled', 'disabled');
            else el.removeAttribute('disabled');
        };

        if (acc) {
            if (modalTitle) modalTitle.innerHTML = '<i class="ph ph-pencil-simple"></i> Edit GL Account';
            setVal('coaAccountId', acc.account_id);
            setVal('coaCode', acc.account_code);
            setVal('coaName', acc.account_name);
            setVal('coaType', acc.account_type);
            setVal('coaNormal', acc.normal_balance);
            setVal('coaCashFlow', acc.cash_flow_category || 'OPERATING');
            setVal('coaSystemRole', acc.system_role || '');
            document.getElementById('coaAllowManual').checked = Boolean(acc.allow_manual_journal);
            document.getElementById('coaIsActive').checked = Boolean(acc.is_active);
            const protectedRole = Boolean(acc.system_role);
            setDisabled('coaType', protectedRole);
            setDisabled('coaSystemRole', protectedRole);
            setDisabled('coaIsActive', protectedRole);
        } else {
            if (modalTitle) modalTitle.innerHTML = '<i class="ph ph-plus-circle"></i> Add GL Account';
            setVal('coaAccountId', '');
            setVal('coaCode', '');
            setVal('coaName', '');
            setVal('coaType', 'EXPENSE');
            setVal('coaNormal', 'DEBIT');
            setVal('coaCashFlow', 'OPERATING');
            setVal('coaSystemRole', '');
            document.getElementById('coaAllowManual').checked = true;
            document.getElementById('coaIsActive').checked = true;
            setDisabled('coaType', false);
            setDisabled('coaSystemRole', false);
            setDisabled('coaIsActive', false);
        }

        openBackdrop('acctCoaModalBackdrop');
    }

    function renderGlInquiry() {
        const tbody = document.getElementById('inquiryTableBody');
        const summaryEl = document.getElementById('inquiryAccountSummary');
        const accId = document.getElementById('inquiryAccountSelect')?.value;
        const branchF = document.getElementById('inquiryBranchSelect')?.value || '';
        if (!tbody || !accId) return;

        const acc = (state.data.accounts || []).find((a) => a.account_id === accId);
        const cur = state.data.baseCurrency || 'PHP';
        const rows = [];
        const postedJournals = (state.data.journals || [])
            .filter((j) => ['POSTED', 'REVERSED'].includes(String(j.status || '').toUpperCase()))
            .sort((a, b) => String(a.journal_date).localeCompare(String(b.journal_date)));

        let running = 0;
        postedJournals.forEach((j) => {
            (j.lines || []).forEach((l) => {
                if (l.account_id !== accId) return;
                if (branchF && String(l.branch_id) !== String(branchF)) return;
                const dr = Number(l.base_debit || 0);
                const cr = Number(l.base_credit || 0);
                running += acc && acc.normal_balance === 'CREDIT' ? (cr - dr) : (dr - cr);
                rows.push({ j, l, dr, cr, running });
            });
        });

        if (summaryEl && acc) {
            summaryEl.innerHTML = `<strong>${escapeHtml(acc.account_code)} - ${escapeHtml(acc.account_name)}</strong> (${escapeHtml(acc.account_type)}, Normal ${escapeHtml(acc.normal_balance)}) &bull; Posted Entries: <strong>${rows.length}</strong> &bull; Filtered Running Net: <strong>${fmtMoney(running, cur)}</strong>`;
        }

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="acct-empty">No posted GL movements for this account and filter.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(({ j, l, dr, cr, running: bal }) => `
            <tr>
                <td>${escapeHtml(String(j.journal_date || '').slice(0, 10))}</td>
                <td class="acct-mono"><button type="button" class="acct-row-btn" data-inq-je="${escapeHtml(j.journal_id)}">${escapeHtml(j.journal_number)}</button></td>
                <td>${escapeHtml(j.journal_type)}</td>
                <td class="acct-mono" style="font-size:11.5px;">${escapeHtml(j.source_document_number || '-')}</td>
                <td>${escapeHtml(l.branch_id || '-')}</td>
                <td style="font-size:11.5px;">${escapeHtml([l.department_id, l.project_code].filter(Boolean).join(' / ') || '-')}</td>
                <td>${escapeHtml(l.description || j.description || '-')}</td>
                <td class="acct-num">${fmtMoney(dr, cur)}</td>
                <td class="acct-num">${fmtMoney(cr, cur)}</td>
                <td class="acct-num" style="font-weight:700;">${fmtMoney(bal, cur)}</td>
            </tr>
        `).join('');

        tbody.querySelectorAll('[data-inq-je]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelector('[data-acct-tab="journals"]')?.click();
                loadJournalIntoBuilder(btn.getAttribute('data-inq-je'));
            });
        });
    }

    function renderRecurringTable() {
        const tbody = document.getElementById('recurringTableBody');
        if (!tbody) return;
        const rows = state.data.recurringTemplates || [];
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="acct-empty">No recurring journal templates defined.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map((t) => `
            <tr>
                <td class="acct-mono">${escapeHtml(t.template_code)}</td>
                <td style="font-weight:600;">${escapeHtml(t.template_name)}</td>
                <td>${escapeHtml(t.frequency)}</td>
                <td>${escapeHtml(String(t.next_run_date || '').slice(0, 10) || '-')}</td>
                <td>${escapeHtml(String(t.last_run_date || '').slice(0, 10) || 'Never')}</td>
                <td>${t.auto_post ? '<span class="acct-badge acct-badge-posted">AUTO-POST</span>' : '<span class="acct-badge acct-badge-draft">DRAFT QUEUE</span>'}</td>
                <td><button type="button" class="acct-row-btn" data-rec-exec="${escapeHtml(t.template_id)}"><i class="ph ph-play"></i> Execute Now</button></td>
            </tr>
        `).join('');

        tbody.querySelectorAll('[data-rec-exec]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                try {
                    const res = await apiRequest(routeWithId(cfg.routes.recurringExecute, btn.getAttribute('data-rec-exec')), 'POST', {});
                    notify('success', res.message);
                    window.location.reload();
                } catch (err) {
                    notify('error', err.message);
                }
            });
        });
    }

    function renderPeriodCloseTable() {
        const tbody = document.getElementById('periodCloseTableBody');
        if (!tbody) return;
        const rows = state.data.periods || [];
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="acct-empty">No fiscal periods configured.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map((p) => {
            const st = String(p.status || 'OPEN').toUpperCase();
            return `
                <tr>
                    <td class="acct-mono">${escapeHtml(p.period_name)}</td>
                    <td>${escapeHtml(String(p.start_date || '').slice(0, 10))}</td>
                    <td>${escapeHtml(String(p.end_date || '').slice(0, 10))}</td>
                    <td>${statusBadgeHtml(st)}</td>
                    <td>${escapeHtml(p.locked_by || '-')}</td>
                    <td style="white-space:nowrap;">
                        ${st === 'OPEN' ? `<button type="button" class="acct-row-btn danger" data-period-lock="${escapeHtml(p.period_id)}" data-target-status="LOCKED">Lock</button>` : ''}
                        ${st !== 'CLOSED' ? `<button type="button" class="acct-row-btn" data-period-lock="${escapeHtml(p.period_id)}" data-target-status="CLOSED">Close</button>` : ''}
                        ${st !== 'OPEN' ? `<button type="button" class="acct-row-btn" data-period-lock="${escapeHtml(p.period_id)}" data-target-status="OPEN">Reopen</button>` : ''}
                    </td>
                </tr>
            `;
        }).join('');

        tbody.querySelectorAll('[data-period-lock]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const pid = btn.getAttribute('data-period-lock');
                const targetStatus = btn.getAttribute('data-target-status');
                const checklist = {};
                document.querySelectorAll('.acct-close-chk').forEach((chk) => {
                    checklist[chk.value] = chk.checked;
                });
                try {
                    const res = await apiRequest(routeWithId(cfg.routes.periodToggleLock, pid), 'POST', {
                        target_status: targetStatus,
                        checklist,
                    });
                    notify('success', res.message);
                    window.location.reload();
                } catch (err) {
                    notify('error', err.message);
                }
            });
        });
    }

    function initGeneralLedger() {
        populateHeaderSelectors();
        resetJournalBuilder();
        renderJournalRegister();
        renderCoaTable();
        renderGlInquiry();
        renderRecurringTable();
        renderPeriodCloseTable();

        // Delegated line events inside Journal Lines Grid
        const jeBody = document.getElementById('jeLinesBody');
        if (jeBody) {
            jeBody.addEventListener('input', (e) => {
                const tr = e.target.closest('tr[data-line-idx]');
                const field = e.target.getAttribute('data-je-field');
                if (!tr || !field) return;
                const idx = Number(tr.getAttribute('data-line-idx'));
                if (!state.jeLines[idx]) return;
                state.jeLines[idx][field] = e.target.value;
                if (field === 'transaction_debit' || field === 'transaction_credit') {
                    recomputeJournalTotals();
                }
            });
            jeBody.addEventListener('change', (e) => {
                const tr = e.target.closest('tr[data-line-idx]');
                const field = e.target.getAttribute('data-je-field');
                if (!tr || !field) return;
                const idx = Number(tr.getAttribute('data-line-idx'));
                if (state.jeLines[idx]) state.jeLines[idx][field] = e.target.value;
            });
            jeBody.addEventListener('click', (e) => {
                const delBtn = e.target.closest('[data-je-del]');
                if (!delBtn) return;
                const idx = Number(delBtn.getAttribute('data-je-del'));
                state.jeLines.splice(idx, 1);
                state.jeLines = ensureTenRows(state.jeLines);
                renderJeLinesGrid();
            });
        }

        document.getElementById('btnJeAddLine')?.addEventListener('click', () => {
            state.jeLines.push(blankLine());
            renderJeLinesGrid();
        });
        document.getElementById('btnJeNewDraft')?.addEventListener('click', resetJournalBuilder);
        document.getElementById('btnJeSaveDraft')?.addEventListener('click', () => submitJournal('DRAFT'));
        document.getElementById('btnJePost')?.addEventListener('click', () => submitJournal('POSTED'));

        document.getElementById('btnJeLoadTemplate')?.addEventListener('click', () => {
            const tplId = document.getElementById('jeTemplatePreset')?.value || (state.data.recurringTemplates?.[0]?.template_id);
            const tpl = (state.data.recurringTemplates || []).find((t) => t.template_id === tplId);
            if (!tpl) {
                notify('error', 'No template available to load.');
                return;
            }
            resetJournalBuilder();
            document.getElementById('jeType').value = 'RECURRING';
            document.getElementById('jeDescription').value = tpl.template_name;
            state.jeLines = ensureTenRows((tpl.lines_json || []).map((l) => {
                const matched = (state.data.accounts || []).find((a) =>
                    (l.account_id && a.account_id === l.account_id) ||
                    (l.system_role && a.system_role === l.system_role) ||
                    (l.account_code && a.account_code === l.account_code)
                );
                return {
                    ...blankLine(),
                    account_id: matched ? matched.account_id : '',
                    branch_id: l.branch_id || 'BR-01',
                    department_id: l.department_id || '',
                    project_code: l.project_code || '',
                    transaction_debit: Number(l.debit || l.transaction_debit || 0),
                    transaction_credit: Number(l.credit || l.transaction_credit || 0),
                    description: l.description || tpl.template_name,
                };
            }));
            renderJeLinesGrid();
            notify('success', `Loaded template [${tpl.template_code}] into Journal Voucher.`);
        });

        // CSV Import Modal
        document.getElementById('btnJeOpenCsvModal')?.addEventListener('click', () => openBackdrop('acctCsvModalBackdrop'));
        document.getElementById('acctCsvFileInput')?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                const ta = document.getElementById('acctCsvTextArea');
                if (ta) ta.value = String(reader.result || '');
            };
            reader.readAsText(file);
        });
        document.getElementById('btnAcctCsvApply')?.addEventListener('click', () => {
            const raw = String(document.getElementById('acctCsvTextArea')?.value || '').trim();
            if (!raw) {
                notify('error', 'Paste CSV rows or select a file first.');
                return;
            }
            const parsed = [];
            raw.split(/\r?\n/).forEach((rowStr) => {
                const cols = rowStr.split(',').map((s) => s.trim());
                if (cols.length < 6 || cols[0].toLowerCase() === 'account_code') return;
                const acc = (state.data.accounts || []).find((a) => a.account_code === cols[0]);
                parsed.push({
                    account_id: acc ? acc.account_id : '',
                    branch_id: cols[1] || 'BR-01',
                    department_id: cols[2] || '',
                    project_code: cols[3] || '',
                    tax_code: '',
                    transaction_debit: Number(cols[4] || 0),
                    transaction_credit: Number(cols[5] || 0),
                    description: cols[6] || '',
                });
            });
            if (!parsed.length) {
                notify('error', 'No valid CSV journal rows found.');
                return;
            }
            state.jeLines = ensureTenRows(parsed);
            renderJeLinesGrid();
            closeBackdrop('acctCsvModalBackdrop');
            notify('success', `Imported ${parsed.length} rows into Journal Voucher.`);
        });

        // Reversal Modal
        const openReversalModalFor = (journalId) => {
            document.getElementById('reverseJournalId').value = journalId;
            openBackdrop('acctReverseModalBackdrop');
        };
        document.getElementById('btnJeOpenReverseModal')?.addEventListener('click', () => {
            if (state.activeJournal) openReversalModalFor(state.activeJournal.journal_id);
        });
        document.getElementById('acctReverseForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('reverseJournalId').value;
            try {
                const res = await apiRequest(routeWithId(cfg.routes.journalReverse, id), 'POST', {
                    reversal_date: document.getElementById('reverseDate').value,
                    reason: document.getElementById('reverseReason').value,
                });
                notify('success', res.message);
                window.location.reload();
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Register Filters & Delegated Row Buttons
        document.getElementById('jeRegisterSearch')?.addEventListener('input', renderJournalRegister);
        document.getElementById('jeRegisterTypeFilter')?.addEventListener('change', renderJournalRegister);
        document.getElementById('jeRegisterStatusFilter')?.addEventListener('change', renderJournalRegister);
        document.getElementById('jeRegisterBody')?.addEventListener('click', async (e) => {
            const loadBtn = e.target.closest('[data-je-load]');
            if (loadBtn) {
                loadJournalIntoBuilder(loadBtn.getAttribute('data-je-load'));
                return;
            }
            const revBtn = e.target.closest('[data-je-rev-id]');
            if (revBtn) {
                openReversalModalFor(revBtn.getAttribute('data-je-rev-id'));
                return;
            }
            const postBtn = e.target.closest('[data-je-post-id]');
            if (postBtn) {
                try {
                    const res = await apiRequest(routeWithId(cfg.routes.journalTransition, postBtn.getAttribute('data-je-post-id')), 'POST', { target_status: 'POSTED' });
                    notify('success', res.message);
                    window.location.reload();
                } catch (err) {
                    notify('error', err.message);
                }
                return;
            }
            const delBtn = e.target.closest('[data-je-del-id]');
            if (delBtn) {
                try {
                    const res = await apiRequest(routeWithId(cfg.routes.journalDelete, delBtn.getAttribute('data-je-del-id')), 'DELETE');
                    notify('success', res.message);
                    window.location.reload();
                } catch (err) {
                    notify('error', err.message);
                }
            }
        });

        // COA Filters, Add, Edit, Delete
        document.getElementById('coaSearchInput')?.addEventListener('input', renderCoaTable);
        document.getElementById('coaTypeFilter')?.addEventListener('change', renderCoaTable);
        document.getElementById('btnCoaAddAccount')?.addEventListener('click', () => openCoaModal(null));
        document.getElementById('coaTableBody')?.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('[data-coa-edit]');
            if (editBtn) {
                openCoaModal(editBtn.getAttribute('data-coa-edit'));
                return;
            }
            const delBtn = e.target.closest('[data-coa-del]');
            if (delBtn) {
                try {
                    const res = await apiRequest(routeWithId(cfg.routes.coaDelete, delBtn.getAttribute('data-coa-del')), 'DELETE');
                    notify('success', res.message);
                    window.location.reload();
                } catch (err) {
                    notify('error', err.message);
                }
            }
        });
        document.getElementById('acctCoaForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const res = await apiRequest(cfg.routes.coaSave, 'POST', {
                    account_id: document.getElementById('coaAccountId').value || null,
                    account_code: document.getElementById('coaCode').value,
                    account_name: document.getElementById('coaName').value,
                    account_type: document.getElementById('coaType').value,
                    normal_balance: document.getElementById('coaNormal').value,
                    cash_flow_category: document.getElementById('coaCashFlow').value,
                    system_role: document.getElementById('coaSystemRole').value || null,
                    allow_manual_journal: document.getElementById('coaAllowManual').checked,
                    is_active: document.getElementById('coaIsActive').checked,
                });
                notify('success', res.message);
                window.location.reload();
            } catch (err) {
                notify('error', err.message);
            }
        });
    }

    // =========================================================================
    // CONFIGURATION, TAX PREVIEW & REPORTS PREVIEW
    // =========================================================================

    function initConfiguration() {
        const fisBody = document.getElementById('cfgFiscalTableBody');
        if (fisBody) {
            fisBody.innerHTML = (state.data.periods || []).map((p) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(p.period_code)}</td>
                    <td style="font-weight:600;">${escapeHtml(p.period_name)}</td>
                    <td>${escapeHtml(String(p.start_date || '').slice(0, 10))}</td>
                    <td>${escapeHtml(String(p.end_date || '').slice(0, 10))}</td>
                    <td>${statusBadgeHtml(p.status)}</td>
                </tr>
            `).join('');
        }

        const termsBody = document.getElementById('cfgTermsTableBody');
        if (termsBody) {
            termsBody.innerHTML = (state.data.paymentTerms || []).map((t) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(t.term_code)}</td>
                    <td>${escapeHtml(t.term_name)}</td>
                    <td class="acct-num">${Number(t.due_days)}</td>
                    <td class="acct-num">${Number(t.discount_pct || 0).toFixed(2)}%</td>
                    <td>${t.is_active ? '<span class="acct-badge acct-badge-posted">ACTIVE</span>' : '<span class="acct-badge acct-badge-closed">INACTIVE</span>'}</td>
                </tr>
            `).join('');
        }

        const fxBody = document.getElementById('cfgFxTableBody');
        if (fxBody) {
            fxBody.innerHTML = (state.data.exchangeRates || []).map((r) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(r.from_currency_code)} / ${escapeHtml(r.to_currency_code)}</td>
                    <td class="acct-num">${Number(r.exchange_rate).toFixed(6)}</td>
                    <td>${escapeHtml(String(r.effective_date || '').slice(0, 10))}</td>
                    <td>${escapeHtml(r.created_by || 'System')}</td>
                </tr>
            `).join('');
        }

        const wfBody = document.getElementById('cfgWorkflowTableBody');
        if (wfBody) {
            const rows = state.data.approvalWorkflows || [];
            wfBody.innerHTML = rows.length ? rows.map((w) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(w.approval_workflow_code)}</td>
                    <td>${escapeHtml(w.approval_workflow_name)}</td>
                    <td>${escapeHtml(w.approval_document_type)}</td>
                    <td>${w.approval_workflow_is_active ? '<span class="acct-badge acct-badge-posted">ACTIVE</span>' : '<span class="acct-badge acct-badge-closed">INACTIVE</span>'}</td>
                </tr>
            `).join('') : '<tr><td colspan="4" class="acct-empty">No approval workflows configured.</td></tr>';
        }

        const numBody = document.getElementById('cfgNumberingTableBody');
        if (numBody) {
            numBody.innerHTML = (state.data.numberingSeries || []).map((s) => `
                <tr data-series-code="${escapeHtml(s.module_code)}">
                    <td class="acct-mono">${escapeHtml(s.module_code)}</td>
                    <td>${escapeHtml(s.document_name)}</td>
                    <td><input type="text" class="acct-input acct-mono" data-ser-prefix value="${escapeHtml(s.prefix)}" style="width:90px;height:30px;"></td>
                    <td class="acct-num"><input type="number" class="acct-input acct-num" data-ser-next value="${Number(s.next_number)}" style="width:90px;height:30px;"></td>
                    <td class="acct-num"><input type="number" class="acct-input acct-num" data-ser-pad value="${Number(s.padding)}" style="width:70px;height:30px;"></td>
                    <td><button type="button" class="acct-row-btn" data-ser-save="${escapeHtml(s.module_code)}">Save</button></td>
                </tr>
            `).join('');
            numBody.querySelectorAll('[data-ser-save]').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const tr = btn.closest('tr');
                    try {
                        const res = await apiRequest(cfg.routes.configSave, 'POST', {
                            section: 'numbering_series',
                            module_code: btn.getAttribute('data-ser-save'),
                            prefix: tr.querySelector('[data-ser-prefix]').value,
                            next_number: Number(tr.querySelector('[data-ser-next]').value),
                            padding: Number(tr.querySelector('[data-ser-pad]').value),
                        });
                        notify('success', res.message);
                    } catch (err) {
                        notify('error', err.message);
                    }
                });
            });
        }

        const dimBody = document.getElementById('cfgDimensionsTableBody');
        if (dimBody) {
            const branches = (state.data.branches || []).map((b) => `<tr><td>Branch</td><td class="acct-mono">${escapeHtml(b.branch_code || b.branch_id)}</td><td>${escapeHtml(b.branch_name)}</td></tr>`);
            const depts = (state.data.departments || []).map((d) => `<tr><td>Department</td><td class="acct-mono">${escapeHtml(d.code || d.department_id)}</td><td>${escapeHtml(d.name)}</td></tr>`);
            dimBody.innerHTML = [...branches, ...depts].join('') || '<tr><td colspan="3" class="acct-empty">No dimensions configured.</td></tr>';
        }

        document.getElementById('cfgPaymentTermForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const res = await apiRequest(cfg.routes.configSave, 'POST', {
                    section: 'payment_term',
                    term_code: document.getElementById('cfgTermCode').value,
                    term_name: document.getElementById('cfgTermName').value,
                    due_days: Number(document.getElementById('cfgTermDays').value),
                });
                notify('success', res.message);
                window.location.reload();
            } catch (err) {
                notify('error', err.message);
            }
        });

        document.getElementById('cfgFxForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const res = await apiRequest(cfg.routes.configSave, 'POST', {
                    section: 'exchange_rate',
                    from_currency_code: document.getElementById('cfgFxCurrency').value,
                    exchange_rate: Number(document.getElementById('cfgFxRate').value),
                    effective_date: document.getElementById('cfgFxDate').value,
                });
                notify('success', res.message);
                window.location.reload();
            } catch (err) {
                notify('error', err.message);
            }
        });
    }

    function initTaxPreview() {
        const cur = state.data.baseCurrency || 'PHP';
        const tbody = document.getElementById('taxCodesTableBody');
        if (tbody) {
            const rows = state.data.taxCodes || [];
            tbody.innerHTML = rows.length ? rows.map((t) => `
                <tr>
                    <td class="acct-mono">${escapeHtml(t.tax_code)}</td>
                    <td>${escapeHtml(t.tax_name)}</td>
                    <td><span class="acct-badge acct-badge-role">${escapeHtml(t.tax_type)}</span></td>
                    <td class="acct-num">${Number(t.rate_pct || t.tax_rate_percent || 0).toFixed(2)}%</td>
                    <td class="acct-mono">${escapeHtml(t.atc_code || '-')}</td>
                    <td>${escapeHtml(t.bir_form || '-')}</td>
                </tr>
            `).join('') : '<tr><td colspan="6" class="acct-empty">No tax codes configured.</td></tr>';
        }

        document.getElementById('taxCodeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const res = await apiRequest(cfg.routes.configSave, 'POST', {
                    section: 'tax_code',
                    tax_code: document.getElementById('taxCodeVal').value,
                    tax_name: document.getElementById('taxNameVal').value,
                    tax_type: document.getElementById('taxTypeVal').value,
                    rate_pct: Number(document.getElementById('taxRateVal').value),
                });
                notify('success', res.message);
                window.location.reload();
            } catch (err) {
                notify('error', err.message);
            }
        });

        // BIR Statutory Report Generator
        document.getElementById('btnGenerateTaxReport')?.addEventListener('click', async () => {
            const yr = document.getElementById('taxRepYear')?.value || 2026;
            const qtr = document.getElementById('taxRepQuarter')?.value || 1;
            const rType = document.getElementById('taxRepType')?.value || '2550Q';
            const container = document.getElementById('taxReportContainer');
            if (!container) return;

            container.innerHTML = '<div style="text-align:center;padding:30px;"><i class="ph ph-spinner ph-spin" style="font-size:32px;"></i> Generating CAS report schedule...</div>';

            try {
                const url = `${cfg.routes.reportsTax}?report_type=${encodeURIComponent(rType)}&year=${yr}&quarter=${qtr}`;
                const res = await apiRequest(url, 'GET');
                const data = res.data;

                if (rType === '2550Q') {
                    const c = data.part2_computation || {};
                    container.innerHTML = `
                        <div class="acct-panel" style="border:1px solid #cbd5e1;">
                            <div class="acct-panel-head" style="background:#f1f5f9;">
                                <div>
                                    <h3 style="margin:0;font-size:16px;font-weight:700;"><i class="ph ph-file-pdf"></i> BIR Form No. 2550Q - Quarterly Value-Added Tax Return</h3>
                                    <div style="font-size:12px;color:#64748b;">Period: Q${escapeHtml(qtr)} ${escapeHtml(yr)} (${escapeHtml(data.return_period.start_date)} to ${escapeHtml(data.return_period.end_date)})</div>
                                </div>
                                <span class="acct-badge acct-badge-posted">CAS COMPLIANT</span>
                            </div>
                            <div class="acct-table-wrap">
                                <table class="acct-table">
                                    <thead><tr><th style="width:70%;">Part II - Computation of Tax</th><th class="acct-num" style="width:30%;">Amount (${cur})</th></tr></thead>
                                    <tbody>
                                        <tr><td><strong>15A.</strong> Vatable Sales / Receipts (Net of VAT)</td><td class="acct-num">${fmtMoney(c.line_15a_vatable_sales, cur)}</td></tr>
                                        <tr><td><strong>15A.</strong> Output Tax Due (12%)</td><td class="acct-num" style="font-weight:700;color:#9333ea;">${fmtMoney(c.line_15a_output_tax, cur)}</td></tr>
                                        <tr><td><strong>15C.</strong> Zero-Rated Sales / Receipts</td><td class="acct-num">${fmtMoney(c.line_15c_zero_rated_sales, cur)}</td></tr>
                                        <tr><td><strong>15D.</strong> Exempt Sales / Receipts</td><td class="acct-num">${fmtMoney(c.line_15d_exempt_sales, cur)}</td></tr>
                                        <tr style="background:#f8fafc;font-weight:700;"><td><strong>15E.</strong> Total Sales / Receipts and Output Tax Due</td><td class="acct-num">${fmtMoney(c.line_15e_total_output_tax, cur)}</td></tr>
                                        <tr><td><strong>16A.</strong> Input Tax on Capital Goods</td><td class="acct-num">${fmtMoney(c.line_16a_capital_goods_input_tax, cur)}</td></tr>
                                        <tr><td><strong>16B.</strong> Input Tax on Goods Other Than Capital Goods</td><td class="acct-num">${fmtMoney(c.line_16b_goods_input_tax, cur)}</td></tr>
                                        <tr><td><strong>16C.</strong> Input Tax on Services</td><td class="acct-num">${fmtMoney(c.line_16c_services_input_tax, cur)}</td></tr>
                                        <tr style="background:#f8fafc;font-weight:700;"><td><strong>16I.</strong> Total Allowable Input Tax</td><td class="acct-num">${fmtMoney(c.line_16i_total_allowable_input_tax, cur)}</td></tr>
                                        <tr style="font-weight:700;background:#eff6ff;"><td><strong>19.</strong> Net VAT Payable / (Excess Input Tax)</td><td class="acct-num" style="color:#2563eb;">${fmtMoney(c.line_19_net_vat_payable, cur)}</td></tr>
                                        <tr><td><strong>20B.</strong> Creditable Value-Added Tax Withheld (BIR Form 2307)</td><td class="acct-num">${fmtMoney(c.line_20b_creditable_vat_withheld, cur)}</td></tr>
                                        <tr style="background:#fef2f2;font-weight:800;font-size:14px;border-top:2px solid #cbd5e1;"><td><strong>22.</strong> TOTAL TAX STILL PAYABLE / (Overpayment)</td><td class="acct-num" style="color:#dc2626;">${fmtMoney(c.line_22_total_tax_still_payable, cur)}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else if (rType === 'SLSP') {
                    const slsRows = data.sls?.rows || [];
                    const slpRows = data.slp?.rows || [];
                    container.innerHTML = `
                        <div style="display:flex;flex-direction:column;gap:20px;">
                            <div class="acct-panel">
                                <div class="acct-panel-head"><h3 style="margin:0;font-size:15px;"><i class="ph ph-receipt"></i> Summary List of Sales (SLS) - Q${escapeHtml(qtr)} ${escapeHtml(yr)}</h3></div>
                                <div class="acct-table-wrap">
                                    <table class="acct-table">
                                        <thead><tr><th>Customer TIN</th><th>Registered Name</th><th class="acct-num">Gross Sales</th><th class="acct-num">Taxable Sales</th><th class="acct-num">Output Tax</th></tr></thead>
                                        <tbody>
                                            ${slsRows.length ? slsRows.map(r => `<tr><td class="acct-mono">${escapeHtml(r.tin)}</td><td>${escapeHtml(r.registered_name)}</td><td class="acct-num">${fmtMoney(r.gross_sales, cur)}</td><td class="acct-num">${fmtMoney(r.taxable_sales, cur)}</td><td class="acct-num" style="font-weight:700;">${fmtMoney(r.output_tax, cur)}</td></tr>`).join('') : '<tr><td colspan="5" class="acct-empty">No sales in this quarter.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="acct-panel">
                                <div class="acct-panel-head"><h3 style="margin:0;font-size:15px;"><i class="ph ph-shopping-cart"></i> Summary List of Purchases (SLP) - Q${escapeHtml(qtr)} ${escapeHtml(yr)}</h3></div>
                                <div class="acct-table-wrap">
                                    <table class="acct-table">
                                        <thead><tr><th>Vendor TIN</th><th>Registered Name</th><th class="acct-num">Gross Purchases</th><th class="acct-num">Taxable Goods</th><th class="acct-num">Capital Goods</th><th class="acct-num">Input Tax</th></tr></thead>
                                        <tbody>
                                            ${slpRows.length ? slpRows.map(r => `<tr><td class="acct-mono">${escapeHtml(r.tin)}</td><td>${escapeHtml(r.registered_name)}</td><td class="acct-num">${fmtMoney(r.gross_purchases, cur)}</td><td class="acct-num">${fmtMoney(r.taxable_goods, cur)}</td><td class="acct-num">${fmtMoney(r.capital_goods, cur)}</td><td class="acct-num" style="font-weight:700;">${fmtMoney(r.input_tax, cur)}</td></tr>`).join('') : '<tr><td colspan="6" class="acct-empty">No purchases in this quarter.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (rType === '1601-EQ') {
                    const sched = data.schedule_1 || [];
                    container.innerHTML = `
                        <div class="acct-panel">
                            <div class="acct-panel-head"><h3 style="margin:0;font-size:15px;"><i class="ph ph-certificate"></i> BIR Form 1601-EQ (Quarterly EWT Remittance)</h3><span class="acct-badge acct-badge-posted">Total Remittance: ${fmtMoney(data.total_remittance_due, cur)}</span></div>
                            <div class="acct-table-wrap">
                                <table class="acct-table">
                                    <thead><tr><th>ATC Code</th><th>Nature of Payment</th><th class="acct-num">Tax Base</th><th class="acct-num">Tax Rate</th><th class="acct-num">Tax Withheld (${cur})</th></tr></thead>
                                    <tbody>
                                        ${sched.length ? sched.map(s => `<tr><td class="acct-mono" style="font-weight:700;">${escapeHtml(s.atc_code)}</td><td>${escapeHtml(s.nature_of_payment)}</td><td class="acct-num">${fmtMoney(s.tax_base, cur)}</td><td class="acct-num">${s.tax_rate_pct}%</td><td class="acct-num" style="font-weight:700;color:#9333ea;">${fmtMoney(s.tax_withheld, cur)}</td></tr>`).join('') : '<tr><td colspan="5" class="acct-empty">No EWT withheld in this quarter.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else if (rType === 'QAP' || rType === 'SAWT') {
                    const list = rType === 'QAP' ? (data.payees || []) : (data.customers || []);
                    container.innerHTML = `
                        <div class="acct-panel">
                            <div class="acct-panel-head"><h3 style="margin:0;font-size:15px;">${rType === 'QAP' ? 'Quarterly Alphanumeric Tax Code List of Payees (QAP)' : 'Summary Alphanumeric Withholding Tax (SAWT)'}</h3></div>
                            <div class="acct-table-wrap">
                                <table class="acct-table">
                                    <thead><tr><th>#</th><th>TIN</th><th>Name</th><th>ATC</th><th class="acct-num">Gross Amount</th><th class="acct-num">Rate</th><th class="acct-num">Tax Withheld (${cur})</th></tr></thead>
                                    <tbody>
                                        ${list.length ? list.map(item => `<tr><td>${item.seq_no}</td><td class="acct-mono">${escapeHtml(item.tin)}</td><td>${escapeHtml(item.payee_name || item.customer_name)}</td><td class="acct-mono">${escapeHtml(item.atc_code)}</td><td class="acct-num">${fmtMoney(item.gross_amount || item.gross_sales, cur)}</td><td class="acct-num">${item.tax_rate}%</td><td class="acct-num" style="font-weight:700;color:#9333ea;">${fmtMoney(item.tax_withheld, cur)}</td></tr>`).join('') : `<tr><td colspan="7" class="acct-empty">No records in this quarter.</td></tr>`}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }
            } catch (err) {
                container.innerHTML = `<div style="padding:20px;color:#dc2626;"><i class="ph ph-warning-circle"></i> Error generating tax report: ${escapeHtml(err.message)}</div>`;
            }
        });
    }

    function initReportsPreview() {
        const cur = state.data.baseCurrency || 'PHP';

        // 1. Trial Balance update
        async function fetchTrialBalance() {
            const date = document.getElementById('tbAsOfDate')?.value || '';
            try {
                const res = await apiRequest(`${cfg.routes.reportsFinancial}?report_type=trial-balance&as_of_date=${encodeURIComponent(date)}`, 'GET');
                const tb = res.data;
                const tbody = document.getElementById('tbTableBody');
                if (tbody) {
                    tbody.innerHTML = (tb.rows || []).map(r => `
                        <tr>
                            <td class="acct-mono" style="font-weight:700;color:#9333ea;">${escapeHtml(r.account_code)}</td>
                            <td>${escapeHtml(r.account_name)}</td>
                            <td><span class="acct-badge acct-badge-draft">${escapeHtml(r.account_type)}</span></td>
                            <td>${escapeHtml(r.normal_balance)}</td>
                            <td class="acct-num">${fmtMoney(r.total_debit, cur)}</td>
                            <td class="acct-num">${fmtMoney(r.total_credit, cur)}</td>
                            <td class="acct-num" style="font-weight:700;">${fmtMoney(r.net_balance, cur)}</td>
                        </tr>
                    `).join('');
                }
                const elDr = document.getElementById('tbTotalDebit');
                const elCr = document.getElementById('tbTotalCredit');
                const elDiff = document.getElementById('tbDifference');
                if (elDr) elDr.textContent = fmtMoney(tb.total_debit, cur);
                if (elCr) elCr.textContent = fmtMoney(tb.total_credit, cur);
                if (elDiff) {
                    elDiff.textContent = fmtMoney(tb.difference, cur);
                    elDiff.style.color = tb.is_balanced ? '#16a34a' : '#dc2626';
                }
                const badge = document.getElementById('tbBalancedBadge');
                if (badge) {
                    badge.innerHTML = tb.is_balanced ? '<i class="ph ph-check-circle"></i> BALANCED' : '<i class="ph ph-warning"></i> UNBALANCED';
                    badge.className = `acct-badge ${tb.is_balanced ? 'acct-badge-posted' : 'acct-badge-danger'}`;
                }
            } catch (e) {
                notify('error', 'Error loading Trial Balance: ' + e.message);
            }
        }
        document.getElementById('btnRefreshTrialBalance')?.addEventListener('click', fetchTrialBalance);
        fetchTrialBalance();

        // 2. Income Statement update
        async function fetchIncomeStatement() {
            const start = document.getElementById('isStartDate')?.value || '';
            const end = document.getElementById('isEndDate')?.value || '';
            const area = document.getElementById('isContentArea');
            if (!area) return;

            try {
                const res = await apiRequest(`${cfg.routes.reportsFinancial}?report_type=income-statement&start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`, 'GET');
                const is = res.data;
                area.innerHTML = `
                    <div style="max-width:760px;margin:0 auto;border:1px solid #cbd5e1;border-radius:10px;padding:20px;background:#fff;">
                        <div style="text-align:center;margin-bottom:20px;border-bottom:1px solid #e2e8f0;padding-bottom:12px;">
                            <h3 style="margin:0;font-size:18px;font-weight:800;">Statement of Profit or Loss (Income Statement)</h3>
                            <div style="font-size:12.5px;color:#64748b;">For the period ${escapeHtml(is.period.start_date)} to ${escapeHtml(is.period.end_date)}</div>
                        </div>
                        <table class="acct-table" style="font-size:13.5px;">
                            <tbody>
                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">REVENUE</td></tr>
                                ${(is.revenue?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Operating Revenue</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(is.revenue?.total, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">COST OF GOODS SOLD (COGS)</td></tr>
                                ${(is.cogs?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Cost of Goods Sold</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(is.cogs?.total, cur)}</td></tr>

                                <tr style="font-weight:800;background:#eff6ff;font-size:14px;"><td>GROSS PROFIT (${is.gross_margin_pct}% Margin)</td><td class="acct-num" style="color:#2563eb;">${fmtMoney(is.gross_profit, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">OPERATING EXPENSES</td></tr>
                                ${(is.operating_expenses?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Operating Expenses</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(is.operating_expenses?.total, cur)}</td></tr>

                                <tr style="font-weight:800;font-size:16px;background:${is.net_income >= 0 ? '#f0fdf4' : '#fef2f2'};border-top:2px solid #0f172a;border-bottom:3px double #0f172a;">
                                    <td>NET INCOME / (LOSS)</td>
                                    <td class="acct-num" style="color:${is.net_income >= 0 ? '#16a34a' : '#dc2626'};">${fmtMoney(is.net_income, cur)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) {
                notify('error', 'Error loading Income Statement: ' + e.message);
            }
        }
        document.getElementById('btnRefreshIncomeStatement')?.addEventListener('click', fetchIncomeStatement);

        // 3. Balance Sheet update
        async function fetchBalanceSheet() {
            const date = document.getElementById('bsAsOfDate')?.value || '';
            const area = document.getElementById('bsContentArea');
            if (!area) return;

            try {
                const res = await apiRequest(`${cfg.routes.reportsFinancial}?report_type=balance-sheet&as_of_date=${encodeURIComponent(date)}`, 'GET');
                const bs = res.data;
                area.innerHTML = `
                    <div style="max-width:760px;margin:0 auto;border:1px solid #cbd5e1;border-radius:10px;padding:20px;background:#fff;">
                        <div style="text-align:center;margin-bottom:20px;border-bottom:1px solid #e2e8f0;padding-bottom:12px;">
                            <h3 style="margin:0;font-size:18px;font-weight:800;">Statement of Financial Position (Balance Sheet)</h3>
                            <div style="font-size:12.5px;color:#64748b;">As of ${escapeHtml(bs.as_of_date)}</div>
                        </div>
                        <table class="acct-table" style="font-size:13.5px;">
                            <tbody>
                                <tr style="font-weight:800;background:#f1f5f9;"><td colspan="2">ASSETS</td></tr>
                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">Current Assets</td></tr>
                                ${(bs.assets?.current_assets?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Current Assets</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(bs.assets?.current_assets?.total, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">Non-Current / Fixed Assets</td></tr>
                                ${(bs.assets?.fixed_assets?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Net Carrying Fixed Assets</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(bs.assets?.fixed_assets?.total, cur)}</td></tr>

                                <tr style="font-weight:800;background:#eff6ff;font-size:14.5px;border-top:2px solid #0f172a;"><td>TOTAL ASSETS</td><td class="acct-num" style="color:#2563eb;">${fmtMoney(bs.assets?.total_assets, cur)}</td></tr>

                                <tr style="font-weight:800;background:#f1f5f9;"><td colspan="2">LIABILITIES &amp; EQUITY</td></tr>
                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">Current Liabilities</td></tr>
                                ${(bs.liabilities?.current_liabilities?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Current Liabilities</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(bs.liabilities?.current_liabilities?.total, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">Long-Term Debt &amp; Borrowings</td></tr>
                                ${(bs.liabilities?.long_term_liabilities?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Long-Term Liabilities</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(bs.liabilities?.long_term_liabilities?.total, cur)}</td></tr>

                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Liabilities</td><td class="acct-num" style="font-weight:700;">${fmtMoney(bs.liabilities?.total_liabilities, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">Equity &amp; Retained Earnings</td></tr>
                                ${(bs.equity?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Total Equity</td><td class="acct-num" style="border-top:1px solid #cbd5e1;">${fmtMoney(bs.equity?.total_equity, cur)}</td></tr>

                                <tr style="font-weight:800;background:#eff6ff;font-size:14.5px;border-top:2px solid #0f172a;border-bottom:3px double #0f172a;">
                                    <td>TOTAL LIABILITIES &amp; EQUITY</td>
                                    <td class="acct-num" style="color:#2563eb;">${fmtMoney(bs.total_liabilities_and_equity, cur)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) {
                notify('error', 'Error loading Balance Sheet: ' + e.message);
            }
        }
        document.getElementById('btnRefreshBalanceSheet')?.addEventListener('click', fetchBalanceSheet);

        // 4. Cash Flow Statement update
        async function fetchCashFlow() {
            const start = document.getElementById('cfStartDate')?.value || '';
            const end = document.getElementById('cfEndDate')?.value || '';
            const area = document.getElementById('cfContentArea');
            if (!area) return;

            try {
                const res = await apiRequest(`${cfg.routes.reportsFinancial}?report_type=cash-flow&start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`, 'GET');
                const cf = res.data;
                area.innerHTML = `
                    <div style="max-width:760px;margin:0 auto;border:1px solid #cbd5e1;border-radius:10px;padding:20px;background:#fff;">
                        <div style="text-align:center;margin-bottom:20px;border-bottom:1px solid #e2e8f0;padding-bottom:12px;">
                            <h3 style="margin:0;font-size:18px;font-weight:800;">Statement of Cash Flows</h3>
                            <div style="font-size:12.5px;color:#64748b;">For the period ${escapeHtml(cf.period.start_date)} to ${escapeHtml(cf.period.end_date)}</div>
                        </div>
                        <table class="acct-table" style="font-size:13.5px;">
                            <tbody>
                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">CASH FLOWS FROM OPERATING ACTIVITIES</td></tr>
                                ${(cf.operating_activities?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Net Cash Provided by / (Used in) Operating Activities</td><td class="acct-num">${fmtMoney(cf.operating_activities?.total, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">CASH FLOWS FROM INVESTING ACTIVITIES</td></tr>
                                ${(cf.investing_activities?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Net Cash Provided by / (Used in) Investing Activities</td><td class="acct-num">${fmtMoney(cf.investing_activities?.total, cur)}</td></tr>

                                <tr style="font-weight:700;background:#f8fafc;"><td colspan="2">CASH FLOWS FROM FINANCING ACTIVITIES</td></tr>
                                ${(cf.financing_activities?.rows || []).map(r => `<tr><td style="padding-left:24px;">${escapeHtml(r.account_code)} ${escapeHtml(r.account_name)}</td><td class="acct-num">${fmtMoney(r.amount, cur)}</td></tr>`).join('')}
                                <tr style="font-weight:700;"><td style="padding-left:12px;">Net Cash Provided by / (Used in) Financing Activities</td><td class="acct-num">${fmtMoney(cf.financing_activities?.total, cur)}</td></tr>

                                <tr style="font-weight:800;background:#eff6ff;font-size:14px;border-top:2px solid #0f172a;"><td>NET INCREASE / (DECREASE) IN CASH &amp; EQUIVALENTS</td><td class="acct-num" style="color:#2563eb;">${fmtMoney(cf.net_cash_flow, cur)}</td></tr>
                                <tr><td>Cash and Cash Equivalents at Beginning of Period</td><td class="acct-num">${fmtMoney(cf.beginning_cash, cur)}</td></tr>
                                <tr style="font-weight:800;font-size:15px;background:#f0fdf4;border-bottom:3px double #0f172a;"><td>CASH AND CASH EQUIVALENTS AT END OF PERIOD</td><td class="acct-num" style="color:#16a34a;">${fmtMoney(cf.ending_cash, cur)}</td></tr>
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) {
                notify('error', 'Error loading Cash Flow: ' + e.message);
            }
        }
        document.getElementById('btnRefreshCashFlow')?.addEventListener('click', fetchCashFlow);

        // 5. AP & AR Aging update
        async function fetchAging() {
            try {
                const [apRes, arRes] = await Promise.all([
                    apiRequest(`${cfg.routes.reportsFinancial}?report_type=aging-ap`, 'GET'),
                    apiRequest(`${cfg.routes.reportsFinancial}?report_type=aging-ar`, 'GET'),
                ]);

                const apBody = document.getElementById('apAgingTableBody');
                if (apBody) {
                    const vendors = apRes.data?.vendors || [];
                    apBody.innerHTML = vendors.length ? vendors.map(v => `
                        <tr>
                            <td><strong>${escapeHtml(v.vendor_name)}</strong></td>
                            <td class="acct-num">${fmtMoney(v.current, cur)}</td>
                            <td class="acct-num">${fmtMoney(v.days_1_30, cur)}</td>
                            <td class="acct-num">${fmtMoney(v.days_31_60, cur)}</td>
                            <td class="acct-num">${fmtMoney(v.days_61_90, cur)}</td>
                            <td class="acct-num" style="color:#dc2626;">${fmtMoney(v.over_90, cur)}</td>
                            <td class="acct-num" style="font-weight:700;">${fmtMoney(v.total, cur)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="7" class="acct-empty">No open vendor payables.</td></tr>';
                }

                const arBody = document.getElementById('arAgingTableBody');
                if (arBody) {
                    const customers = arRes.data?.customers || [];
                    arBody.innerHTML = customers.length ? customers.map(c => `
                        <tr>
                            <td><strong>${escapeHtml(c.customer_name)}</strong></td>
                            <td class="acct-num">${fmtMoney(c.current, cur)}</td>
                            <td class="acct-num">${fmtMoney(c.days_1_30, cur)}</td>
                            <td class="acct-num">${fmtMoney(c.days_31_60, cur)}</td>
                            <td class="acct-num">${fmtMoney(c.days_61_90, cur)}</td>
                            <td class="acct-num" style="color:#dc2626;">${fmtMoney(c.over_90, cur)}</td>
                            <td class="acct-num" style="font-weight:700;">${fmtMoney(c.total, cur)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="7" class="acct-empty">No open customer receivables.</td></tr>';
                }
            } catch (e) {
                console.error('Aging fetch error:', e);
            }
        }
        fetchAging();

        // 6. Inventory vs GL Reconciliation & Landed Cost Modal
        async function fetchInvRecon() {
            try {
                const res = await apiRequest(`${cfg.routes.reportsFinancial}?report_type=inv-recon`, 'GET');
                const rec = res.data;
                const elGl = document.getElementById('reconGlInvBalance');
                const elPerp = document.getElementById('reconPerpetualVal');
                const elVar = document.getElementById('reconVariance');
                const elGrni = document.getElementById('reconGrniBalance');

                if (elGl) elGl.textContent = fmtMoney(rec.inventory_asset?.gl_balance, cur);
                if (elPerp) elPerp.textContent = fmtMoney(rec.inventory_asset?.perpetual_valuation, cur);
                if (elVar) {
                    elVar.textContent = fmtMoney(rec.inventory_asset?.variance, cur);
                    elVar.style.color = rec.inventory_asset?.is_reconciled ? '#16a34a' : '#dc2626';
                }
                if (elGrni) elGrni.textContent = fmtMoney(rec.grni_accrual?.gl_balance, cur);
            } catch (e) {
                console.error('Inventory recon error:', e);
            }
        }
        fetchInvRecon();

        const lcModal = document.getElementById('acctLandedCostModalBackdrop');
        document.getElementById('btnOpenLandedCostModal')?.addEventListener('click', () => {
            if (lcModal) lcModal.style.display = 'flex';
        });
        document.querySelectorAll('[data-acct-close="acctLandedCostModalBackdrop"]').forEach(b => {
            b.addEventListener('click', () => { if (lcModal) lcModal.style.display = 'none'; });
        });

        document.getElementById('landedCostAllocForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const sid = document.getElementById('lcStockInId')?.value;
            const amt = parseFloat(document.getElementById('lcAmount')?.value) || 0;
            const cType = document.getElementById('lcType')?.value || 'FREIGHT';
            const cRatio = parseFloat(document.getElementById('lcConsumedRatio')?.value) || 0.0;

            if (!sid || amt <= 0) {
                notify('error', 'Valid Stock Intake reference and landed cost amount required.');
                return;
            }

            try {
                const res = await apiRequest(cfg.routes.landedCostAllocate, 'POST', {
                    stock_in_id: sid,
                    branch_id: 'BR-01',
                    landed_costs: [
                        { amount: amt, cost_type: cType },
                        { consumed_ratio: cRatio }
                    ]
                });
                notify('success', res.message || 'Landed cost split and capitalized successfully.');
                if (lcModal) lcModal.style.display = 'none';
                fetchInvRecon();
                fetchTrialBalance();
            } catch (err) {
                notify('error', err.message);
            }
        });
    }

    // =========================================================================
    // WAVE 2: ACCOUNTS PAYABLE (AP) CONTROLLER
    // =========================================================================

    function initAccountsPayable() {
        const root = document.getElementById('accountingApRoot');
        if (!root) return;

        const cur = state.data.baseCurrency || 'PHP';
        const accounts = state.data.accounts || [];
        const expenseAccounts = accounts.filter(a => ['COGS', 'EXPENSE', 'ASSET'].includes(a.account_type));
        const taxCodes = state.data.taxCodes || [];
        const purchaseOrders = state.data.purchaseOrders || [];
        const vendorsList = state.data.vendors || [];
        const defaultTaxCode = taxCodes.find(t => t.tax_code === 'VAT12_IN' || t.tax_code === 'VAT_12')?.tax_code || taxCodes[0]?.tax_code || 'VAT_12';

        let apBillLines = [];
        let currentPoAdvance = 0.0;
        let availableVendorCredit = 0.0;
        let currentGrandTotal = 0.0;
        let activeBillRecord = null;

        // =====================================================================
        // SHARED RFQ CUSTOMIZABLE COLUMN & WIDTH ENGINE (BILLS + OPEN BALANCES)
        // =====================================================================
        function setupApRfqCustomizableTable(tblCfg) {
            const {
                columns,
                storagePrefix,
                theadRowId,
                tbodyId,
                settingsBtnId,
                dropdownId,
                colListId,
                counterId,
                searchInputId,
                resetDefId,
                showAllId,
                resetWidthsId,
                resetMessage,
                rowFilterSelector,
            } = tblCfg;

            const lsColsKey = `${storagePrefix}_cols_v1`;
            const lsOrderKey = `${storagePrefix}_cols_order_v1`;
            const lsWidthKey = `${storagePrefix}_col_widths_v1`;

            let activeColIds = (() => {
                try {
                    const saved = JSON.parse(localStorage.getItem(lsColsKey) || 'null');
                    if (Array.isArray(saved) && saved.length > 0) return saved;
                } catch (e) {}
                return columns.filter(c => c.default).map(c => c.id);
            })();

            let colOrder = (() => {
                try {
                    const saved = JSON.parse(localStorage.getItem(lsOrderKey) || 'null');
                    if (Array.isArray(saved) && saved.length > 0) {
                        const allIds = columns.map(c => c.id);
                        const merged = saved.filter(id => allIds.includes(id));
                        allIds.forEach(id => { if (!merged.includes(id)) merged.push(id); });
                        return merged;
                    }
                } catch (e) {}
                return columns.map(c => c.id);
            })();

            let savedColWidths = (() => {
                try {
                    const saved = JSON.parse(localStorage.getItem(lsWidthKey) || 'null');
                    if (saved && typeof saved === 'object') return saved;
                } catch (e) {}
                return {};
            })();

            let colSearchTerm = '';
            let headerDragSourceId = null;
            let dropdownDragSourceId = null;
            let resizingColId = null;
            let resizeStartX = 0;
            let resizeStartW = 0;
            let resizeRaf = null;

            const getColWidth = (col) => savedColWidths[col.id] || col.defaultWidth || '125px';
            const getOrderedVisibleCols = () => colOrder
                .filter(id => activeColIds.includes(id))
                .map(id => columns.find(c => c.id === id))
                .filter(Boolean);

            function applyColumnLayout() {
                const tbody = document.getElementById(tbodyId);
                if (!tbody) return;
                const visibleCols = getOrderedVisibleCols();
                const visibleIds = new Set(visibleCols.map(c => c.id));

                tbody.querySelectorAll('tr').forEach(tr => {
                    if (
                        tr.classList.contains('ap-empty-row')
                        || tr.classList.contains('ap-vendor-empty-row')
                        || tr.classList.contains('ap-vendor-pos-detail-row')
                    ) {
                        const spanTd = tr.querySelector('td');
                        if (spanTd) spanTd.setAttribute('colspan', String(Math.max(1, visibleCols.length)));
                        return;
                    }
                    if (rowFilterSelector && !tr.matches(rowFilterSelector)) {
                        return;
                    }
                    const cellMap = {};
                    Array.from(tr.children).forEach(td => {
                        const cid = td.getAttribute('data-col-id');
                        if (cid) cellMap[cid] = td;
                    });
                    colOrder.forEach(cid => {
                        const td = cellMap[cid];
                        if (!td) return;
                        td.style.display = visibleIds.has(cid) ? '' : 'none';
                        tr.appendChild(td);
                    });
                });
            }

            function renderColDropdownChecklist() {
                const listEl = document.getElementById(colListId);
                if (!listEl) return;

                const q = (colSearchTerm || '').toLowerCase().trim();
                const orderedCols = colOrder.map(id => columns.find(c => c.id === id)).filter(Boolean);

                listEl.innerHTML = orderedCols
                    .filter(col => !q || col.label.toLowerCase().includes(q) || (col.subLabel && col.subLabel.toLowerCase().includes(q)))
                    .map(col => {
                        const isChecked = activeColIds.includes(col.id);
                        const isDisabled = col.lockVisible ? 'disabled' : '';
                        return `
                            <div class="rfq-dropdown-col-item" data-col-id="${col.id}" draggable="${col.id !== 'actions' ? 'true' : 'false'}">
                                ${col.id !== 'actions'
                                    ? '<span class="rfq-drag-handle" title="Drag to reorder column"><i class="ph ph-dots-six-vertical"></i></span>'
                                    : '<span style="width:14px;display:inline-block;"></span>'}
                                <label class="rfq-col-item-label">
                                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                                        <input type="checkbox" class="ap-col-vis-chk" data-col-id="${col.id}" ${isChecked ? 'checked' : ''} ${isDisabled}>
                                        <div style="display:flex;flex-direction:column;min-width:0;">
                                            <span class="rfq-col-name">${escapeHtml(col.label)}</span>
                                            ${col.subLabel ? `<span class="rfq-col-subname">${escapeHtml(col.subLabel)}</span>` : ''}
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        ${col.default ? '<span class="rfq-col-badge-default">Default</span>' : ''}
                                        ${col.lockVisible ? '<span style="font-size:0.65rem;color:#94a3b8;font-weight:600;">(Locked)</span>' : ''}
                                    </div>
                                </label>
                            </div>
                        `;
                    }).join('');

                const counter = document.getElementById(counterId);
                if (counter) {
                    counter.textContent = `${activeColIds.length} of ${columns.length} visible`;
                }
            }

            function renderTableHeader() {
                const theadRow = document.getElementById(theadRowId);
                if (!theadRow) return;

                const visibleCols = getOrderedVisibleCols();
                theadRow.innerHTML = visibleCols.map(col => {
                    const w = getColWidth(col);
                    const isRight = col.align === 'right';
                    const alignClass = isRight ? 'th-num' : 'th-left';

                    if (col.id === 'actions') {
                        return `
                            <th class="rfq-th-stacked th-num th-actions-sticky" data-col-id="actions" style="width:${w};text-align:right;user-select:none;">
                                <div class="rfq-header-action-wrapper" style="justify-content:flex-end;gap:8px;">
                                    <div class="rfq-th-content" style="align-items:flex-end;text-align:right;">
                                        <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                                        <div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>
                                    </div>
                                    <button type="button" class="rfq-th-settings-btn" id="${settingsBtnId}" title="Customize Columns &amp; Widths">
                                        <i class="ph ph-gear"></i>
                                    </button>
                                </div>
                            </th>
                        `;
                    }

                    return `
                        <th class="rfq-th-stacked ${alignClass}" data-col-id="${col.id}" draggable="true"
                            style="width:${w};text-align:${isRight ? 'right' : 'left'};position:relative;user-select:none;">
                            <div class="rfq-th-content" style="align-items:${isRight ? 'flex-end' : 'flex-start'};text-align:${isRight ? 'right' : 'left'};">
                                <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                                ${col.subLabel ? `<div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>` : ''}
                            </div>
                            <div class="rfq-col-resizer" data-resize-col="${col.id}"></div>
                        </th>
                    `;
                }).join('');

                applyColumnLayout();
                renderColDropdownChecklist();
            }

            function reorderColumns(sourceId, targetId) {
                if (!sourceId || !targetId || sourceId === targetId || sourceId === 'actions' || targetId === 'actions') return;
                const sIdx = colOrder.indexOf(sourceId);
                const tIdx = colOrder.indexOf(targetId);
                if (sIdx === -1 || tIdx === -1) return;
                colOrder.splice(sIdx, 1);
                colOrder.splice(tIdx, 0, sourceId);
                localStorage.setItem(lsOrderKey, JSON.stringify(colOrder));
                renderTableHeader();
            }

            const theadRow = document.getElementById(theadRowId);
            if (theadRow) {
                theadRow.addEventListener('click', (e) => {
                    const gearBtn = e.target.closest(`#${settingsBtnId}`);
                    if (!gearBtn) return;
                    e.stopPropagation();
                    const dd = document.getElementById(dropdownId);
                    if (!dd) return;
                    const isOpen = dd.classList.contains('is-active');
                    if (isOpen) {
                        dd.classList.remove('is-active');
                        dd.style.display = 'none';
                        gearBtn.classList.remove('is-active');
                    } else {
                        dd.style.display = 'block';
                        dd.classList.add('is-active');
                        gearBtn.classList.add('is-active');
                        const rect = gearBtn.getBoundingClientRect();
                        const left = Math.max(10, rect.right - 370);
                        let top = rect.bottom + 6;
                        if (top + 400 > window.innerHeight) top = Math.max(10, rect.top - 410);
                        dd.style.left = `${left}px`;
                        dd.style.top = `${top}px`;
                        renderColDropdownChecklist();
                    }
                });

                theadRow.addEventListener('mousedown', (e) => {
                    const resizer = e.target.closest('.rfq-col-resizer');
                    if (!resizer) return;
                    e.preventDefault();
                    e.stopPropagation();
                    resizingColId = resizer.getAttribute('data-resize-col');
                    resizeStartX = e.pageX;
                    const th = theadRow.querySelector(`th[data-col-id="${resizingColId}"]`);
                    resizeStartW = th ? th.offsetWidth : 120;
                    resizer.classList.add('is-resizing');
                });

                theadRow.addEventListener('dragstart', (e) => {
                    const th = e.target.closest('th[data-col-id]');
                    const cid = th?.getAttribute('data-col-id');
                    if (!cid || cid === 'actions') { e.preventDefault(); return; }
                    headerDragSourceId = cid;
                    e.dataTransfer.effectAllowed = 'move';
                    th.classList.add('is-header-dragging');
                });
                theadRow.addEventListener('dragover', (e) => {
                    const th = e.target.closest('th[data-col-id]');
                    const cid = th?.getAttribute('data-col-id');
                    if (!cid || cid === 'actions' || !headerDragSourceId) return;
                    e.preventDefault();
                    if (headerDragSourceId !== cid) th.classList.add('is-header-drag-over');
                });
                theadRow.addEventListener('dragleave', (e) => {
                    e.target.closest('th[data-col-id]')?.classList.remove('is-header-drag-over');
                });
                theadRow.addEventListener('drop', (e) => {
                    const th = e.target.closest('th[data-col-id]');
                    const cid = th?.getAttribute('data-col-id');
                    th?.classList.remove('is-header-drag-over');
                    if (headerDragSourceId && cid && cid !== 'actions') {
                        e.preventDefault();
                        reorderColumns(headerDragSourceId, cid);
                    }
                    headerDragSourceId = null;
                });
                theadRow.addEventListener('dragend', () => {
                    headerDragSourceId = null;
                    theadRow.querySelectorAll('th').forEach(th => th.classList.remove('is-header-dragging', 'is-header-drag-over'));
                });
            }

            document.addEventListener('mousemove', (e) => {
                if (!resizingColId) return;
                const pageX = e.pageX;
                if (resizeRaf) return;
                resizeRaf = window.requestAnimationFrame(() => {
                    resizeRaf = null;
                    if (!resizingColId) return;
                    const newWidth = Math.max(75, resizeStartW + (pageX - resizeStartX));
                    savedColWidths[resizingColId] = `${newWidth}px`;
                    const th = document.querySelector(`#${theadRowId} th[data-col-id="${resizingColId}"]`);
                    if (th) th.style.width = `${newWidth}px`;
                });
            });

            document.addEventListener('mouseup', () => {
                if (!resizingColId) return;
                localStorage.setItem(lsWidthKey, JSON.stringify(savedColWidths));
                document.querySelectorAll(`#${theadRowId} .rfq-col-resizer`).forEach(r => r.classList.remove('is-resizing'));
                resizingColId = null;
            });

            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                dropdown.addEventListener('click', (e) => e.stopPropagation());
                dropdown.addEventListener('change', (e) => {
                    const chk = e.target.closest('.ap-col-vis-chk');
                    if (!chk) return;
                    const cid = chk.getAttribute('data-col-id');
                    if (chk.checked) {
                        if (!activeColIds.includes(cid)) activeColIds.push(cid);
                    } else {
                        activeColIds = activeColIds.filter(id => id !== cid);
                    }
                    localStorage.setItem(lsColsKey, JSON.stringify(activeColIds));
                    renderTableHeader();
                });
                dropdown.addEventListener('dragstart', (e) => {
                    const item = e.target.closest('.rfq-dropdown-col-item');
                    const cid = item?.getAttribute('data-col-id');
                    if (!cid || cid === 'actions') { e.preventDefault(); return; }
                    dropdownDragSourceId = cid;
                    item.classList.add('is-dragging');
                });
                dropdown.addEventListener('dragover', (e) => {
                    const item = e.target.closest('.rfq-dropdown-col-item');
                    const cid = item?.getAttribute('data-col-id');
                    if (!cid || cid === 'actions' || !dropdownDragSourceId) return;
                    e.preventDefault();
                    if (dropdownDragSourceId !== cid) item.classList.add('is-drag-over');
                });
                dropdown.addEventListener('dragleave', (e) => {
                    e.target.closest('.rfq-dropdown-col-item')?.classList.remove('is-drag-over');
                });
                dropdown.addEventListener('drop', (e) => {
                    const item = e.target.closest('.rfq-dropdown-col-item');
                    const cid = item?.getAttribute('data-col-id');
                    item?.classList.remove('is-drag-over');
                    if (dropdownDragSourceId && cid && cid !== 'actions') {
                        e.preventDefault();
                        reorderColumns(dropdownDragSourceId, cid);
                    }
                    dropdownDragSourceId = null;
                });
                dropdown.addEventListener('dragend', () => {
                    dropdownDragSourceId = null;
                    dropdown.querySelectorAll('.rfq-dropdown-col-item').forEach(el => el.classList.remove('is-dragging', 'is-drag-over'));
                });
            }

            document.addEventListener('click', () => {
                if (dropdown && dropdown.classList.contains('is-active')) {
                    dropdown.classList.remove('is-active');
                    dropdown.style.display = 'none';
                    document.getElementById(settingsBtnId)?.classList.remove('is-active');
                }
            });

            document.getElementById(searchInputId)?.addEventListener('input', (e) => {
                colSearchTerm = e.target.value || '';
                renderColDropdownChecklist();
            });
            document.getElementById(resetDefId)?.addEventListener('click', () => {
                activeColIds = columns.filter(c => c.default).map(c => c.id);
                colOrder = columns.map(c => c.id);
                localStorage.setItem(lsColsKey, JSON.stringify(activeColIds));
                localStorage.removeItem(lsOrderKey);
                renderTableHeader();
                notify('success', resetMessage || 'Reset to default columns.');
            });
            document.getElementById(showAllId)?.addEventListener('click', () => {
                activeColIds = columns.map(c => c.id);
                localStorage.setItem(lsColsKey, JSON.stringify(activeColIds));
                renderTableHeader();
            });
            document.getElementById(resetWidthsId)?.addEventListener('click', () => {
                savedColWidths = {};
                localStorage.removeItem(lsWidthKey);
                renderTableHeader();
                notify('success', 'Column widths reset.');
            });

            renderTableHeader();
        }

        // 1. Vendor Bills Tracker Table Customizer
        setupApRfqCustomizableTable({
            columns: [
                { id: 'bill_no', label: 'Bill #', subLabel: 'System Voucher', default: true, align: 'left', defaultWidth: '130px', lockVisible: true },
                { id: 'vendor', label: 'Vendor', subLabel: 'Supplier / Code', default: true, align: 'left', defaultWidth: '190px' },
                { id: 'invoice_no', label: 'Invoice / Ref #', subLabel: 'Vendor SI #', default: true, align: 'left', defaultWidth: '140px' },
                { id: 'po_match', label: 'PO / GRN Match', subLabel: 'Linked Order', default: true, align: 'left', defaultWidth: '135px' },
                { id: 'bill_date', label: 'Bill Date', subLabel: 'Invoice Date', default: true, align: 'left', defaultWidth: '110px' },
                { id: 'due_date', label: 'Due Date', subLabel: 'Maturity', default: true, align: 'left', defaultWidth: '110px' },
                { id: 'match_type', label: 'Match Type', subLabel: '3-Way Status', default: true, align: 'left', defaultWidth: '135px' },
                { id: 'total', label: 'Total Amount', subLabel: 'Gross Payable', default: true, align: 'right', defaultWidth: '125px' },
                { id: 'paid', label: 'Paid / Credit', subLabel: 'Settled', default: true, align: 'right', defaultWidth: '125px' },
                { id: 'balance', label: 'Balance Due', subLabel: 'Open Payable', default: true, align: 'right', defaultWidth: '125px' },
                { id: 'status', label: 'Status', subLabel: 'Posting State', default: true, align: 'left', defaultWidth: '105px' },
                { id: 'actions', label: 'Actions', subLabel: 'Pay / View', default: true, align: 'right', defaultWidth: '165px', lockVisible: true },
            ],
            storagePrefix: 'rms_ap_bills',
            theadRowId: 'apBillsTheadRow',
            tbodyId: 'apBillsRegisterBody',
            settingsBtnId: 'btnApBillsTableSettings',
            dropdownId: 'apBillsTableSettingsDropdown',
            colListId: 'apBillsColCheckboxesList',
            counterId: 'apBillsColActiveCounter',
            searchInputId: 'apBillsColSearchInput',
            resetDefId: 'btnApBillsResetDefaults',
            showAllId: 'btnApBillsShowAll',
            resetWidthsId: 'btnApBillsResetWidths',
            resetMessage: 'Reset to default Vendor Bills columns.',
        });

        // 2. Vendor Open Balances Table Customizer (Tab 4: 80% Right Panel)
        setupApRfqCustomizableTable({
            columns: [
                { id: 'vendor_code', label: 'Vendor Code', subLabel: 'Expand > POs', default: true, align: 'left', defaultWidth: '140px', lockVisible: true },
                { id: 'trade_name', label: 'Supplier Name', subLabel: 'Trade / Legal', default: true, align: 'left', defaultWidth: '210px' },
                { id: 'tin', label: 'TIN', subLabel: 'BIR Tax ID', default: true, align: 'left', defaultWidth: '130px' },
                { id: 'payment_terms', label: 'Terms', subLabel: 'Credit Window', default: true, align: 'left', defaultWidth: '110px' },
                { id: 'pending_pos', label: 'Pending POs', subLabel: 'Unsettled Orders', default: true, align: 'left', defaultWidth: '130px' },
                { id: 'open_bills', label: 'Open Bills', subLabel: 'Unpaid Count', default: true, align: 'right', defaultWidth: '95px' },
                { id: 'credit_avail', label: 'Vendor Credit', subLabel: 'Unapplied Memos', default: true, align: 'right', defaultWidth: '130px' },
                { id: 'outstanding_balance', label: 'Outstanding Balance', subLabel: 'Total AP Due', default: true, align: 'right', defaultWidth: '155px' },
                { id: 'actions', label: 'Actions', subLabel: 'Settle PO / Bill', default: true, align: 'right', defaultWidth: '130px', lockVisible: true },
            ],
            storagePrefix: 'rms_ap_vendor_bal',
            theadRowId: 'apVendorBalancesTheadRow',
            tbodyId: 'apVendorBalancesBody',
            settingsBtnId: 'btnApVendorBalancesTableSettings',
            dropdownId: 'apVendorBalancesTableSettingsDropdown',
            colListId: 'apVendorBalancesColCheckboxesList',
            counterId: 'apVendorBalancesColActiveCounter',
            searchInputId: 'apVendorBalancesColSearchInput',
            resetDefId: 'btnApVendorBalancesResetDefaults',
            showAllId: 'btnApVendorBalancesShowAll',
            resetWidthsId: 'btnApVendorBalancesResetWidths',
            resetMessage: 'Reset to default Vendor Open Balances columns.',
            rowFilterSelector: 'tr.ap-vendor-balance-row',
        });

        // Tab 4: Expand / Collapse Vendor Pending POs (>) + Search/Filter
        document.getElementById('apVendorBalancesBody')?.addEventListener('click', (e) => {
            const expandTrigger = e.target.closest('.btnApExpandVendorPos');
            if (expandTrigger) {
                const vId = expandTrigger.getAttribute('data-vendor-id');
                if (!vId) return;
                const detailRow = document.querySelector(`#apVendorBalancesBody tr.ap-vendor-pos-detail-row[data-parent-vendor-id="${vId}"]`);
                const caretBtn = document.querySelector(`#apVendorBalancesBody tr.ap-vendor-balance-row[data-vendor-id="${vId}"] .ap-expand-po-btn`);
                if (!detailRow) return;
                const isOpen = detailRow.style.display !== 'none';
                detailRow.style.display = isOpen ? 'none' : 'table-row';
                if (caretBtn) caretBtn.classList.toggle('is-expanded', !isOpen);
                return;
            }
        });

        const filterVendorOpenBalances = () => {
            const q = (document.getElementById('apLedgerVendorSearch')?.value || '').trim().toLowerCase();
            const fMode = (document.getElementById('apLedgerBalanceFilter')?.value || '').trim().toUpperCase();
            document.querySelectorAll('#apVendorBalancesBody tr.ap-vendor-balance-row').forEach(tr => {
                const vId = tr.getAttribute('data-vendor-id');
                const hay = tr.getAttribute('data-search') || '';
                const hasOpen = tr.getAttribute('data-has-open') === '1';
                const hasPending = tr.getAttribute('data-has-pending-po') === '1';
                const hasCredit = tr.getAttribute('data-has-credit') === '1';
                const matchesMode = !fMode
                    || (fMode === 'OPEN' && hasOpen)
                    || (fMode === 'PENDING_PO' && hasPending)
                    || (fMode === 'WITH_CREDIT' && hasCredit);
                const visible = (!q || hay.includes(q)) && matchesMode;
                tr.style.display = visible ? '' : 'none';
                if (!visible && vId) {
                    const detailRow = document.querySelector(`#apVendorBalancesBody tr.ap-vendor-pos-detail-row[data-parent-vendor-id="${vId}"]`);
                    const caretBtn = tr.querySelector('.ap-expand-po-btn');
                    if (detailRow) detailRow.style.display = 'none';
                    if (caretBtn) caretBtn.classList.remove('is-expanded');
                }
            });
        };
        document.getElementById('apLedgerVendorSearch')?.addEventListener('input', filterVendorOpenBalances);
        document.getElementById('apLedgerBalanceFilter')?.addEventListener('change', filterVendorOpenBalances);

        // =====================================================================
        // VENDOR BILL WORKSPACE DRAWER & LINE ITEMS
        // =====================================================================
        function resolveVendorKeys(vendorId) {
            const keys = new Set();
            if (!vendorId) return keys;
            keys.add(String(vendorId));
            const vObj = vendorsList.find(v => String(v.id) === String(vendorId) || String(v.vendor_code) === String(vendorId));
            if (vObj) {
                keys.add(String(vObj.id));
                if (vObj.vendor_code) keys.add(String(vObj.vendor_code));
            }
            return keys;
        }

        function createEmptyBillLine() {
            return {
                description: '',
                po_line_ref: '',
                quantity: 1,
                uom: 'PCS',
                unit_price: 0.00,
                tax_code: defaultTaxCode,
                tax_amount: 0.00,
                line_total: 0.00,
                expense_account_id: expenseAccounts[0]?.account_id || '',
                po_line_id: null,
            };
        }

        function buildUomSelectOptions(selectedUom) {
            const codeVal = String(selectedUom || 'PCS');
            if (window.UomRegistry && window.UomRegistry.isLoaded) {
                const units = window.UomRegistry.getUnits();
                let opts = units.map(u => `<option value="${escapeHtml(u.uom_code)}" ${codeVal.toUpperCase() === u.uom_code.toUpperCase() ? 'selected' : ''}>${escapeHtml(u.uom_code)}</option>`).join('');
                if (codeVal && !units.some(u => u.uom_code.toUpperCase() === codeVal.toUpperCase())) {
                    opts = `<option value="${escapeHtml(codeVal)}" selected>${escapeHtml(codeVal)}</option>` + opts;
                }
                return opts;
            }
            const fallback = ['PCS', 'KG', 'L', 'BOX', 'PACK', 'UNIT', 'TRAY', 'SACK'];
            if (codeVal && !fallback.includes(codeVal.toUpperCase())) fallback.unshift(codeVal);
            return fallback.map(u => `<option value="${escapeHtml(u)}" ${codeVal.toUpperCase() === u.toUpperCase() ? 'selected' : ''}>${escapeHtml(u)}</option>`).join('');
        }

        // Initialize with 1 line (dynamic row)
        apBillLines.push(createEmptyBillLine());

        function renderBillLines() {
            const body = document.getElementById('apBillLinesBody');
            if (!body) return;

            body.innerHTML = apBillLines.map((line, idx) => `
                <tr data-line-idx="${idx}">
                    <td class="acct-mono" style="font-size:12px;color:#94a3b8;">${idx + 1}</td>
                    <td>
                        <input type="text" class="acct-input ap-line-desc" value="${escapeHtml(line.description)}" placeholder="Item / service description" style="width:100%;">
                    </td>
                    <td>
                        <input type="text" class="acct-input ap-line-ref" value="${escapeHtml(line.po_line_ref)}" placeholder="PO / GRN line" style="width:100%;">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="acct-input acct-num ap-line-qty" value="${line.quantity}" style="width:100%;">
                    </td>
                    <td>
                        <select class="ap-line-uom" data-uom-select data-uom-placeholder="Select UOM" data-uom-current="${escapeHtml(line.uom || 'PCS')}" style="width:100%;">
                            ${buildUomSelectOptions(line.uom)}
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="acct-input acct-num ap-line-price" value="${line.unit_price}" style="width:100%;">
                    </td>
                    <td>
                        <select class="acct-select ap-line-tax" style="width:100%;">
                            ${taxCodes.map(tc => `<option value="${escapeHtml(tc.tax_code)}"${tc.tax_code === line.tax_code ? ' selected' : ''}>${escapeHtml(tc.tax_code)} (${tc.rate_pct}%)</option>`).join('')}
                        </select>
                    </td>
                    <td class="acct-num ap-line-tax-amt" style="font-size:12.5px;">${fmtMoney(line.tax_amount, '')}</td>
                    <td class="acct-num ap-line-total" style="font-size:12.5px;font-weight:600;">${fmtMoney(line.line_total, '')}</td>
                    <td>
                        <select class="acct-select ap-line-acc" style="width:100%;">
                            <option value="">-- Auto GRNI / Default --</option>
                            ${expenseAccounts.map(ea => `<option value="${escapeHtml(ea.account_id)}"${ea.account_id === line.expense_account_id ? ' selected' : ''}>${escapeHtml(ea.account_code)} ${escapeHtml(ea.account_name)}</option>`).join('')}
                        </select>
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="acct-row-btn btnApDelBillLine" data-line-idx="${idx}" title="Remove line">
                            <i class="ph ph-trash" style="color:#ef4444;"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            const lineCountBadge = document.getElementById('apBillLinesCountBadge');
            if (lineCountBadge) {
                lineCountBadge.textContent = `${apBillLines.length} Line${apBillLines.length === 1 ? '' : 's'}`;
            }

            window.UomRegistry?.autoHydrate(body);
            recalcBillTotals();
        }

        function recalcBillTotals() {
            let subtotal = 0;
            let taxTotal = 0;

            const rows = document.querySelectorAll('#apBillLinesBody tr');
            rows.forEach((row, idx) => {
                const line = apBillLines[idx];
                if (!line) return;

                const qty = parseFloat(row.querySelector('.ap-line-qty')?.value) || 0;
                const price = parseFloat(row.querySelector('.ap-line-price')?.value) || 0;
                const taxCode = row.querySelector('.ap-line-tax')?.value || defaultTaxCode;

                const tcObj = taxCodes.find(t => t.tax_code === taxCode);
                const isZeroTax = ['EXEMPT', 'ZERO_RATED', 'VAT_EXEMPT', 'VAT_ZERO'].includes(String(taxCode).toUpperCase());
                const rate = tcObj ? parseFloat(tcObj.rate_pct) : (isZeroTax ? 0 : 12);

                const lineSubtotal = qty * price;
                const lineTax = (isZeroTax || rate <= 0) ? 0 : lineSubtotal * (rate / 100);
                const lineTotal = lineSubtotal + lineTax;

                line.quantity = qty;
                line.unit_price = price;
                line.tax_code = taxCode;
                line.tax_amount = lineTax;
                line.line_total = lineTotal;
                line.description = row.querySelector('.ap-line-desc')?.value || '';
                line.po_line_ref = row.querySelector('.ap-line-ref')?.value || '';
                const uomEl = row.querySelector('.ap-line-uom');
                if (uomEl && uomEl.value) line.uom = uomEl.value;
                line.expense_account_id = row.querySelector('.ap-line-acc')?.value || '';

                const elTaxAmt = row.querySelector('.ap-line-tax-amt');
                const elTot = row.querySelector('.ap-line-total');
                if (elTaxAmt) elTaxAmt.textContent = fmtMoney(lineTax, '');
                if (elTot) elTot.textContent = fmtMoney(lineTotal, '');

                if (line.description || line.quantity > 0) {
                    subtotal += lineSubtotal;
                    taxTotal += lineTax;
                }
            });

            const shipping = parseFloat(document.getElementById('apBillShipping')?.value) || 0;
            currentGrandTotal = subtotal + taxTotal + shipping;

            // Handle optional vendor credit deduction
            const creditChk = document.getElementById('apBillApplyCreditChk');
            const creditInput = document.getElementById('apBillCreditAppliedAmount');
            let appliedCredit = 0;
            if (creditChk && creditChk.checked) {
                appliedCredit = parseFloat(creditInput?.value) || 0;
                const maxApplicable = Math.max(0, currentGrandTotal - currentPoAdvance);
                appliedCredit = Math.min(appliedCredit, availableVendorCredit, maxApplicable);
            }

            const alreadyPaid = activeBillRecord && ['POSTED', 'PARTIAL', 'PAID'].includes(String(activeBillRecord.vendor_bill_status || '').toUpperCase())
                ? parseFloat(activeBillRecord.bill_paid_amount || 0)
                : currentPoAdvance;
            const netBalanceDue = Math.max(0, currentGrandTotal - alreadyPaid - appliedCredit);

            const elSub = document.getElementById('apBillSumSubtotal');
            const elTax = document.getElementById('apBillSumTax');
            const elGrand = document.getElementById('apBillSumGrandTotal');
            const elAdv = document.getElementById('apBillPoAdvanceDisplay');
            const elBal = document.getElementById('apBillNetBalanceDueDisplay');
            const elFootTot = document.getElementById('apBillDrawerFooterTotal');
            const elFootBal = document.getElementById('apBillDrawerFooterBalance');

            if (elSub) elSub.textContent = fmtMoney(subtotal, cur);
            if (elTax) elTax.textContent = fmtMoney(taxTotal, cur);
            if (elGrand) elGrand.textContent = fmtMoney(currentGrandTotal, cur);
            if (elAdv) elAdv.textContent = fmtMoney(alreadyPaid, cur);
            if (elBal) {
                elBal.textContent = fmtMoney(netBalanceDue, cur);
                elBal.style.color = netBalanceDue <= 0.01 ? '#059669' : '#ef4444';
            }
            if (elFootTot) elFootTot.textContent = fmtMoney(currentGrandTotal, cur);
            if (elFootBal) {
                elFootBal.textContent = fmtMoney(netBalanceDue, cur);
                elFootBal.style.color = netBalanceDue <= 0.01 ? '#059669' : '#ef4444';
            }
        }

        function updateVendorCredits(vendorId) {
            const keys = resolveVendorKeys(vendorId);
            const memos = (state.data.vendorMemos || []).filter(m =>
                keys.has(String(m.vendor_id)) &&
                ['CREDIT', 'CREDIT_MEMO'].includes(String(m.memo_type || '').toUpperCase()) &&
                parseFloat(m.unapplied_amount) > 0.001
            );
            availableVendorCredit = memos.reduce((sum, m) => sum + parseFloat(m.unapplied_amount || 0), 0);
            const badge = document.getElementById('apBillAvailCreditBadge');
            if (badge) {
                badge.textContent = `(${fmtMoney(availableVendorCredit, cur)} avail)`;
            }

            const creditChk = document.getElementById('apBillApplyCreditChk');
            const creditInput = document.getElementById('apBillCreditAppliedAmount');
            if (creditChk && creditChk.checked) {
                const maxApplicable = Math.min(availableVendorCredit, Math.max(0, currentGrandTotal - currentPoAdvance));
                if (creditInput) creditInput.value = maxApplicable.toFixed(2);
            }
            recalcBillTotals();
        }

        function filterPoDropdownByVendor(vendorId) {
            const poSelect = document.getElementById('apBillPoReference');
            if (!poSelect) return;

            const keys = resolveVendorKeys(vendorId);
            const currentVal = poSelect.value;
            let currentStillValid = false;

            Array.from(poSelect.options).forEach(opt => {
                if (!opt.value) {
                    opt.style.display = '';
                    return;
                }
                const optVendorId = opt.getAttribute('data-vendor-id');
                const matches = !vendorId || keys.has(String(optVendorId));
                opt.style.display = matches ? '' : 'none';
                if (matches && opt.value === currentVal) {
                    currentStillValid = true;
                }
            });

            if (!currentStillValid && currentVal) {
                poSelect.value = '';
                handlePoSelection('');
            }
        }

        function handlePoSelection(poId) {
            if (!poId) {
                currentPoAdvance = 0.0;
                const matchBadge = document.getElementById('apBillMatchBadge');
                if (matchBadge) matchBadge.textContent = 'DIRECT_EXPENSE';
                recalcBillTotals();
                return;
            }

            const po = purchaseOrders.find(p => String(p.id) === String(poId) || String(p.po_number) === String(poId));
            if (!po) return;

            const matchBadge = document.getElementById('apBillMatchBadge');
            if (matchBadge) matchBadge.textContent = 'PO_MATCHED';

            // Auto-align vendor if not set
            const resolvedVendorId = po.resolved_vendor_id || po.vendor_id;
            const vSelect = document.getElementById('apBillVendor');
            if (vSelect && (!vSelect.value || String(vSelect.value) !== String(resolvedVendorId))) {
                vSelect.value = resolvedVendorId;
                updateVendorCredits(resolvedVendorId);
            }

            // Read PO advance payment
            currentPoAdvance = parseFloat(po.amount_paid || 0);

            // Auto-fill invoice placeholder if empty
            const invInput = document.getElementById('apBillInvoiceNum');
            if (invInput && !invInput.value) {
                invInput.placeholder = `e.g. INV-${po.po_number}`;
            }

            // Auto-populate line items from PO lines
            if (po.lines && po.lines.length > 0) {
                apBillLines = po.lines.map(line => {
                    const qty = parseFloat(line.quantity || 1);
                    const unitPrice = parseFloat(line.unit_price || 0);
                    const sub = qty * unitPrice;
                    return {
                        description: `${line.sku ? '[' + line.sku + '] ' : ''}${line.item_name || 'Item'}${line.specs ? ' - ' + line.specs : ''}`,
                        po_line_ref: `${po.po_number} (Item: ${line.sku || line.id})`,
                        quantity: qty,
                        uom: line.uom || 'PCS',
                        unit_price: unitPrice,
                        tax_code: taxCodes.find(t => t.tax_code === 'VAT_EXEMPT')?.tax_code || defaultTaxCode,
                        tax_amount: 0,
                        line_total: sub,
                        expense_account_id: expenseAccounts[0]?.account_id || '',
                        po_line_id: line.id,
                    };
                });
            } else {
                apBillLines = [createEmptyBillLine()];
            }

            renderBillLines();
        }

        function renderBillPaymentHistory(bill) {
            const wrap = document.getElementById('apBillPaymentHistoryWrap');
            const list = document.getElementById('apBillPaymentHistoryList');
            const btnDrawerPay = document.getElementById('btnApDrawerPayBill');
            const btnDrawerPayInline = document.getElementById('btnApDrawerPayBillInline');
            const btnSaveDraft = document.getElementById('btnApSaveBillDraft');
            const btnPostBill = document.getElementById('btnApPostBill');

            if (!bill) {
                if (wrap) wrap.style.display = 'none';
                if (list) list.innerHTML = '';
                if (btnDrawerPay) btnDrawerPay.style.display = 'none';
                if (btnDrawerPayInline) btnDrawerPayInline.style.display = 'none';
                if (btnSaveDraft) btnSaveDraft.style.display = '';
                if (btnPostBill) btnPostBill.style.display = '';
                return;
            }

            const status = String(bill.vendor_bill_status || 'DRAFT').toUpperCase();
            const isDraft = status === 'DRAFT';
            const balance = Math.max(0, parseFloat(bill.bill_total_amount || 0) - parseFloat(bill.bill_paid_amount || 0));
            const canPay = !isDraft && balance > 0.01;

            if (btnSaveDraft) btnSaveDraft.style.display = isDraft ? '' : 'none';
            if (btnPostBill) btnPostBill.style.display = isDraft ? '' : 'none';
            if (btnDrawerPay) btnDrawerPay.style.display = canPay ? 'inline-flex' : 'none';
            if (btnDrawerPayInline) btnDrawerPayInline.style.display = canPay ? 'inline-flex' : 'none';

            const entries = [];
            (bill.payment_allocations || []).forEach(alloc => {
                const pmt = alloc.payment || {};
                entries.push({
                    badge: pmt.payment_number || 'PV',
                    date: (pmt.payment_date || '').split('T')[0],
                    desc: `${pmt.payment_method || 'Payment'}${pmt.bank_reference ? ' • ' + pmt.bank_reference : ''}`,
                    amount: parseFloat(alloc.allocated_amount || 0),
                    ewt: parseFloat(alloc.ewt_withheld || 0),
                    tone: '#059669',
                });
            });
            (bill.credit_applications || []).forEach(cr => {
                entries.push({
                    badge: cr.memo_number || 'VCM',
                    date: cr.acted_at || '',
                    desc: 'Applied Vendor Credit Memo',
                    amount: parseFloat(cr.applied_amount || 0),
                    ewt: 0,
                    tone: '#7c3aed',
                });
            });

            if (entries.length === 0 && parseFloat(bill.bill_paid_amount || 0) > 0) {
                entries.push({
                    badge: 'PO-ADV',
                    date: (bill.vendor_invoice_date || '').split('T')[0],
                    desc: 'Purchase Order Advance / Prior Settlement',
                    amount: parseFloat(bill.bill_paid_amount || 0),
                    ewt: 0,
                    tone: '#0284c7',
                });
            }

            if (wrap && list) {
                if (entries.length > 0) {
                    wrap.style.display = 'block';
                    list.innerHTML = entries.map(e => `
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 10px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;font-size:12px;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span class="acct-mono" style="font-weight:700;color:${e.tone};">${escapeHtml(e.badge)}</span>
                                <span style="color:#64748b;">${escapeHtml(e.date)}</span>
                                <span style="color:#334155;">${escapeHtml(e.desc)}</span>
                            </div>
                            <div class="acct-num" style="font-weight:700;color:${e.tone};">
                                -${fmtMoney(e.amount, cur)}${e.ewt > 0 ? ` <span style="font-size:10.5px;color:#64748b;">(EWT ${fmtMoney(e.ewt, '')})</span>` : ''}
                            </div>
                        </div>
                    `).join('');
                } else {
                    wrap.style.display = 'none';
                    list.innerHTML = '';
                }
            }
        }

        // Canonical RMS Drawer Show/Hide Controls
        const drawerBackdrop = document.getElementById('apBillDrawerBackdrop');
        const drawerPanel = document.getElementById('apBillDrawerPanel');

        function showBillWorkspace() {
            if (drawerBackdrop) drawerBackdrop.classList.add('is-open');
            if (drawerPanel) {
                drawerPanel.classList.add('is-open');
                drawerPanel.setAttribute('aria-hidden', 'false');
            }
        }

        function hideBillWorkspace() {
            if (drawerPanel) {
                drawerPanel.classList.remove('is-open');
                drawerPanel.setAttribute('aria-hidden', 'true');
            }
            if (drawerBackdrop) drawerBackdrop.classList.remove('is-open');
        }

        document.getElementById('btnApNewBill')?.addEventListener('click', () => {
            activeBillRecord = null;
            const elId = document.getElementById('apBillId');
            const elInv = document.getElementById('apBillInvoiceNum');
            const elNotes = document.getElementById('apBillNotes');
            const elVendor = document.getElementById('apBillVendor');
            const elPo = document.getElementById('apBillPoReference');
            const elNum = document.getElementById('apBillNumberDisplay');
            const elStatus = document.getElementById('apBillStatusBadge');
            const elMatch = document.getElementById('apBillMatchBadge');
            const elShip = document.getElementById('apBillShipping');

            if (elId) elId.value = '';
            if (elInv) elInv.value = '';
            if (elNotes) elNotes.value = '';
            if (elVendor) elVendor.value = '';
            if (elPo) elPo.value = '';
            if (elNum) elNum.textContent = 'AUTO-NUMBERED';
            if (elStatus) elStatus.textContent = 'NEW DRAFT';
            if (elMatch) elMatch.textContent = 'DIRECT_EXPENSE';
            if (elShip) elShip.value = '0.00';

            const creditChk = document.getElementById('apBillApplyCreditChk');
            if (creditChk) creditChk.checked = false;
            const creditInput = document.getElementById('apBillCreditAppliedAmount');
            if (creditInput) {
                creditInput.value = '0.00';
                creditInput.disabled = true;
            }

            currentPoAdvance = 0.0;
            availableVendorCredit = 0.0;
            filterPoDropdownByVendor('');
            renderBillPaymentHistory(null);

            apBillLines = [createEmptyBillLine()];
            renderBillLines();
            showBillWorkspace();
        });

        document.getElementById('btnApCloseBillDrawer')?.addEventListener('click', hideBillWorkspace);
        document.getElementById('btnApCancelBill')?.addEventListener('click', hideBillWorkspace);
        drawerBackdrop?.addEventListener('click', hideBillWorkspace);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && drawerPanel?.classList.contains('is-open')) {
                hideBillWorkspace();
            }
        });

        document.getElementById('btnApResetDraft')?.addEventListener('click', () => {
            apBillLines = [createEmptyBillLine()];
            renderBillLines();
        });

        // Vendor selection changes: filter POs and update available credits
        document.getElementById('apBillVendor')?.addEventListener('change', (e) => {
            const vId = e.target.value;
            filterPoDropdownByVendor(vId);
            updateVendorCredits(vId);
        });

        // PO selection changes: auto-populate lines and PO advance
        document.getElementById('apBillPoReference')?.addEventListener('change', (e) => {
            handlePoSelection(e.target.value);
        });

        // Checkbox: Apply Vendor Credit
        document.getElementById('apBillApplyCreditChk')?.addEventListener('change', (e) => {
            const creditInput = document.getElementById('apBillCreditAppliedAmount');
            if (e.target.checked) {
                if (creditInput) creditInput.disabled = false;
                const maxApplicable = Math.min(availableVendorCredit, Math.max(0, currentGrandTotal - currentPoAdvance));
                if (creditInput) creditInput.value = maxApplicable.toFixed(2);
            } else {
                if (creditInput) {
                    creditInput.value = '0.00';
                    creditInput.disabled = true;
                }
            }
            recalcBillTotals();
        });

        document.getElementById('apBillCreditAppliedAmount')?.addEventListener('input', recalcBillTotals);

        // Lines table event delegation
        const linesTable = document.getElementById('apBillLinesTable');
        if (linesTable) {
            linesTable.addEventListener('input', (e) => {
                if (e.target.matches('.ap-line-qty, .ap-line-price, .ap-line-desc, .ap-line-ref, .ap-line-uom')) {
                    recalcBillTotals();
                }
            });

            linesTable.addEventListener('change', (e) => {
                if (e.target.matches('.ap-line-tax, .ap-line-acc, .ap-line-uom')) {
                    recalcBillTotals();
                }
            });

            linesTable.addEventListener('click', (e) => {
                const btn = e.target.closest('.btnApDelBillLine');
                if (btn) {
                    const idx = parseInt(btn.getAttribute('data-line-idx'), 10);
                    if (apBillLines.length > 1) {
                        apBillLines.splice(idx, 1);
                    } else {
                        apBillLines = [createEmptyBillLine()];
                    }
                    renderBillLines();
                }
            });
        }

        document.getElementById('apBillShipping')?.addEventListener('input', recalcBillTotals);

        function addBillLineRow() {
            apBillLines.push(createEmptyBillLine());
            renderBillLines();
            const lastRowDesc = document.querySelector('#apBillLinesBody tr:last-child .ap-line-desc');
            lastRowDesc?.focus();
        }
        document.getElementById('btnApAddBillLine')?.addEventListener('click', addBillLineRow);
        document.getElementById('btnApAddBillLineTop')?.addEventListener('click', addBillLineRow);

        // View existing bill in RMS Drawer
        document.querySelectorAll('.btnApViewBill').forEach(btn => {
            btn.addEventListener('click', () => {
                const bId = btn.getAttribute('data-bill-id');
                const bill = (state.data.vendorBills || []).find(b => String(b.vendor_bill_id) === String(bId));
                if (!bill) return;

                activeBillRecord = bill;

                const elId = document.getElementById('apBillId');
                const elInv = document.getElementById('apBillInvoiceNum');
                const elInvDate = document.getElementById('apBillInvoiceDate');
                const elDueDate = document.getElementById('apBillDueDate');
                const elVendor = document.getElementById('apBillVendor');
                const elPo = document.getElementById('apBillPoReference');
                const elNum = document.getElementById('apBillNumberDisplay');
                const elStatus = document.getElementById('apBillStatusBadge');
                const elMatch = document.getElementById('apBillMatchBadge');
                const elShip = document.getElementById('apBillShipping');
                const elNotes = document.getElementById('apBillNotes');

                const matchedVendor = vendorsList.find(v => String(v.id) === String(bill.vendor_id) || String(v.vendor_code) === String(bill.vendor_id));
                const resolvedVId = matchedVendor ? String(matchedVendor.id) : String(bill.vendor_id);

                if (elId) elId.value = bill.vendor_bill_id;
                if (elInv) elInv.value = bill.vendor_invoice_number || '';
                if (elInvDate) elInvDate.value = (bill.vendor_invoice_date || '').split('T')[0];
                if (elDueDate) elDueDate.value = (bill.bill_due_date || '').split('T')[0];
                if (elVendor) elVendor.value = resolvedVId;
                if (elPo) elPo.value = bill.purchase_order_id || '';
                if (elNum) elNum.textContent = bill.vendor_bill_number;
                if (elStatus) elStatus.textContent = bill.vendor_bill_status;
                if (elMatch) elMatch.textContent = bill.bill_match_status || 'DIRECT_EXPENSE';
                if (elShip) elShip.value = parseFloat(bill.bill_shipping_amount || 0).toFixed(2);
                if (elNotes) elNotes.value = bill.vendor_bill_notes || '';

                const creditChk = document.getElementById('apBillApplyCreditChk');
                if (creditChk) creditChk.checked = false;
                const creditInput = document.getElementById('apBillCreditAppliedAmount');
                if (creditInput) {
                    creditInput.value = '0.00';
                    creditInput.disabled = true;
                }

                filterPoDropdownByVendor(resolvedVId);
                updateVendorCredits(resolvedVId);

                const po = purchaseOrders.find(p => String(p.id) === String(bill.purchase_order_id) || String(p.po_number) === String(bill.purchase_order_id));
                currentPoAdvance = po ? parseFloat(po.amount_paid || 0) : 0.0;

                if (bill.lines && bill.lines.length > 0) {
                    apBillLines = bill.lines.map(l => ({
                        description: l.bill_line_description,
                        po_line_ref: l.po_line_id ? `PO Line: ${l.po_line_id}` : '',
                        quantity: parseFloat(l.bill_quantity),
                        uom: l.purchase_uom_code || 'PCS',
                        unit_price: parseFloat(l.bill_unit_price),
                        tax_code: l.tax_code || defaultTaxCode,
                        tax_amount: parseFloat(l.bill_line_tax_amount || 0),
                        line_total: parseFloat(l.bill_line_total || 0),
                        expense_account_id: l.gl_expense_account_id || '',
                        po_line_id: l.po_line_id || null,
                    }));
                } else {
                    apBillLines = [createEmptyBillLine()];
                }

                renderBillPaymentHistory(bill);
                renderBillLines();
                showBillWorkspace();
            });
        });

        // Filter bills register by search text or status
        function filterBillsRegister() {
            const search = (document.getElementById('apBillSearch')?.value || '').toLowerCase().trim();
            const status = document.getElementById('apBillStatusFilter')?.value || '';
            const rows = document.querySelectorAll('#apBillsRegisterBody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-bill-status') || '';
                const matchesSearch = !search || text.includes(search);
                const matchesStatus = !status || rowStatus === status;
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        document.getElementById('apBillSearch')?.addEventListener('input', filterBillsRegister);
        document.getElementById('apBillStatusFilter')?.addEventListener('change', filterBillsRegister);

        // Save Bill Draft or Post Bill
        async function submitBill(targetStatus) {
            const vendorId = document.getElementById('apBillVendor')?.value;
            const invNum = document.getElementById('apBillInvoiceNum')?.value;
            const invDate = document.getElementById('apBillInvoiceDate')?.value;
            const dueDate = document.getElementById('apBillDueDate')?.value;

            if (!vendorId) {
                notify('error', 'Please select a vendor.');
                return;
            }
            if (!invNum) {
                notify('error', 'Please enter the Vendor Invoice / Reference Number.');
                return;
            }

            const validLines = apBillLines.filter(l => l.description.trim() !== '' || l.quantity > 0);
            if (validLines.length === 0) {
                notify('error', 'Please provide at least one valid line item description.');
                return;
            }

            const creditChk = document.getElementById('apBillApplyCreditChk');
            const creditApplied = (creditChk && creditChk.checked)
                ? (parseFloat(document.getElementById('apBillCreditAppliedAmount')?.value) || 0.0)
                : 0.0;

            const header = {
                vendor_bill_id: document.getElementById('apBillId')?.value || undefined,
                vendor_id: vendorId,
                vendor_invoice_number: invNum,
                vendor_invoice_date: invDate,
                bill_due_date: dueDate,
                purchase_order_id: document.getElementById('apBillPoReference')?.value || null,
                branch_id: document.getElementById('apBillBranch')?.value || 'BR-01',
                bill_currency_code: document.getElementById('apBillCurrency')?.value || 'PHP',
                bill_exchange_rate: parseFloat(document.getElementById('apBillFxRate')?.value) || 1.0,
                bill_shipping_amount: parseFloat(document.getElementById('apBillShipping')?.value) || 0.0,
                credit_applied_amount: creditApplied,
                vendor_bill_notes: document.getElementById('apBillNotes')?.value || null,
            };

            const payloadLines = validLines.map(l => ({
                bill_line_description: l.description,
                quantity: l.quantity,
                uom: l.uom,
                bill_unit_price: l.unit_price,
                tax_code: l.tax_code,
                gl_expense_account_id: l.expense_account_id || null,
                po_line_id: l.po_line_id || null,
            }));

            try {
                const res = await apiRequest(cfg.routes.apBillSave, 'POST', {
                    header,
                    lines: payloadLines,
                    action: targetStatus,
                });
                notify('success', res.message || 'Vendor Bill saved successfully.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        }

        document.getElementById('btnApSaveBillDraft')?.addEventListener('click', () => submitBill('DRAFT'));
        document.getElementById('btnApPostBill')?.addEventListener('click', () => submitBill('POSTED'));

        // Canonical RMS Payment Voucher Drawer Controls & PO Matching Engine
        const pmtDrawerBackdrop = document.getElementById('apPaymentDrawerBackdrop');
        const pmtDrawerPanel = document.getElementById('apPaymentDrawerPanel');

        function openPaymentDrawer() {
            if (pmtDrawerBackdrop) pmtDrawerBackdrop.classList.add('is-open');
            if (pmtDrawerPanel) {
                pmtDrawerPanel.classList.add('is-open');
                pmtDrawerPanel.setAttribute('aria-hidden', 'false');
            }
        }

        function closePaymentDrawer() {
            if (pmtDrawerPanel) {
                pmtDrawerPanel.classList.remove('is-open');
                pmtDrawerPanel.setAttribute('aria-hidden', 'true');
            }
            if (pmtDrawerBackdrop) pmtDrawerBackdrop.classList.remove('is-open');
        }

        pmtDrawerBackdrop?.addEventListener('click', closePaymentDrawer);
        document.getElementById('btnApNewPayment')?.addEventListener('click', () => {
            const modeSel = document.getElementById('apPmtSettlementMode');
            if (modeSel) modeSel.value = 'COMBINED';
            openPaymentDrawer();
            recalcPaymentModalNet();
        });
        document.getElementById('btnApNewSeparate2307')?.addEventListener('click', () => {
            const modeSel = document.getElementById('apPmtSettlementMode');
            if (modeSel) {
                modeSel.value = 'EWT_2307_ONLY';
                modeSel.dispatchEvent(new Event('change'));
            }
            openPaymentDrawer();
            recalcPaymentModalNet();
        });
        document.getElementById('btnApOpenPaymentFromBills')?.addEventListener('click', () => {
            const modeSel = document.getElementById('apPmtSettlementMode');
            if (modeSel) modeSel.value = 'COMBINED';
            openPaymentDrawer();
            recalcPaymentModalNet();
        });
        document.getElementById('btnApClosePaymentModal')?.addEventListener('click', closePaymentDrawer);
        document.getElementById('btnApCancelPayment')?.addEventListener('click', closePaymentDrawer);

        document.getElementById('apPmtMethod')?.addEventListener('change', (e) => {
            const pdcBlock = document.getElementById('apPmtPdcBlock');
            if (pdcBlock) pdcBlock.style.display = e.target.value === 'PDC' ? 'block' : 'none';
        });

        const poByIdLookup = new Map((state.data.purchaseOrders || []).map(p => [String(p.id), p]));

        function updateVendorCreditBannerForPayment(vId) {
            const banner = document.getElementById('apPmtVendorCreditBanner');
            const valEl = document.getElementById('apPmtAvailCreditVal');
            if (!banner || !valEl) return;
            if (!vId) {
                banner.style.display = 'none';
                return;
            }
            const keys = resolveVendorKeys(vId);
            const availCredit = (state.data.vendorMemos || []).reduce((sum, m) => {
                const mType = String(m.memo_type || '').toUpperCase();
                if (!['CREDIT', 'CREDIT_MEMO'].includes(mType)) return sum;
                if (String(m.status || '').toUpperCase() !== 'POSTED') return sum;
                if (!keys.has(String(m.vendor_id))) return sum;
                return sum + (parseFloat(m.unapplied_amount) || 0);
            }, 0);
            if (availCredit > 0.009) {
                valEl.textContent = fmtMoney(availCredit, cur);
                banner.style.display = 'flex';
            } else {
                banner.style.display = 'none';
            }
        }

        function syncTotalInputFromPaymentRows() {
            let rowSum = 0;
            document.querySelectorAll('#apPmtAllocationsBody tr').forEach(row => {
                const chk = row.querySelector('.ap-pmt-row-check');
                if (chk && !chk.checked) return;
                rowSum += parseFloat(row.querySelector('.ap-pmt-alloc-amt')?.value) || 0;
            });
            const totalInput = document.getElementById('apPmtTotalInput');
            if (totalInput) totalInput.value = rowSum.toFixed(2);
            recalcPaymentModalNet();
        }

        function renderPaymentAllocationsForVendor(vId, preserveCheckedBillIds = null) {
            const body = document.getElementById('apPmtAllocationsBody');
            const countBadge = document.getElementById('apPmtMatchedCountBadge');
            if (!body) return;

            const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            const issue2307El = document.getElementById('apPmtIssue2307');
            if (issue2307El) {
                issue2307El.checked = mode !== 'BILL_ONLY';
            }

            updateVendorCreditBannerForPayment(vId);
            if (!vId) {
                if (countBadge) countBadge.textContent = 'Select vendor to load open POs';
                body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:18px;color:#94a3b8;">Select a <strong>Quick Match PO</strong> or <strong>Vendor</strong> above to load payable obligations.</td></tr>';
                syncTotalInputFromPaymentRows();
                return;
            }

            const keys = resolveVendorKeys(vId);
            const bills = (state.data.vendorBills || []).filter(b => {
                if (!keys.has(String(b.vendor_id))) return false;
                if (String(b.vendor_bill_status || '').toUpperCase() === 'DRAFT') return false;
                const bal = parseFloat(b.bill_total_amount) - parseFloat(b.bill_paid_amount);
                return mode === 'EWT_2307_ONLY' ? true : bal > 0.01;
            });

            if (countBadge) {
                countBadge.textContent = mode === 'EWT_2307_ONLY'
                    ? `${bills.length} Bill(s) Eligible for 2307`
                    : `${bills.length} Open Obligation(s)`;
            }

            if (bills.length === 0) {
                body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:18px;color:#94a3b8;">No matching posted bills/POs for this vendor. Entering a <strong>Total Payment Amount</strong> above will create a Vendor Credit balance.</td></tr>';
                const totEl = document.getElementById('apPmtTotalInput');
                if (totEl) totEl.value = '0.00';
                recalcPaymentModalNet();
                return;
            }

            body.innerHTML = bills.map((b) => {
                const bal = Math.max(0, parseFloat(b.bill_total_amount) - parseFloat(b.bill_paid_amount));
                const billTotal = parseFloat(b.bill_total_amount) || 0;
                const billSub = parseFloat(b.bill_subtotal_amount) || billTotal;
                const isChecked = preserveCheckedBillIds
                    ? preserveCheckedBillIds.has(String(b.vendor_bill_id))
                    : true;

                let allocAmt = 0;
                let ewtAmt = 0;
                let defaultRule = '1';
                if (mode === 'BILL_ONLY') {
                    allocAmt = isChecked ? bal : 0;
                    ewtAmt = 0;
                    defaultRule = '0';
                } else if (mode === 'EWT_2307_ONLY') {
                    defaultRule = '1';
                    ewtAmt = isChecked ? Math.round((billSub * 0.01 + Number.EPSILON) * 100) / 100 : 0;
                    allocAmt = ewtAmt;
                } else {
                    allocAmt = isChecked ? bal : 0;
                    ewtAmt = isChecked ? Math.round((bal * 0.01 + Number.EPSILON) * 100) / 100 : 0;
                    defaultRule = '1';
                }

                const poObj = b.purchase_order_id ? poByIdLookup.get(String(b.purchase_order_id)) : null;
                const poLabel = poObj ? poObj.po_number : (String(b.purchase_order_id || '').startsWith('PO-') ? b.purchase_order_id : '');
                return `
                    <tr data-bill-id="${escapeHtml(b.vendor_bill_id)}" data-po-id="${escapeHtml(b.purchase_order_id || '')}" data-po-no="${escapeHtml(poLabel)}" data-open-balance="${bal.toFixed(2)}" data-bill-total="${billTotal.toFixed(2)}" data-bill-subtotal="${billSub.toFixed(2)}">
                        <td style="text-align:center;">
                            <input type="checkbox" class="ap-pmt-row-check" ${isChecked ? 'checked' : ''}>
                        </td>
                        <td>
                            ${poLabel ? `<span class="acct-badge" style="background:#f3e8ff;color:#7e22ce;font-weight:700;">${escapeHtml(poLabel)}</span><br>` : ''}
                            <span class="acct-mono" style="font-size:11.5px;color:#475569;">${escapeHtml(b.vendor_bill_number)}</span>
                        </td>
                        <td>${escapeHtml(b.vendor_invoice_number)}</td>
                        <td class="acct-num">${fmtMoney(b.bill_total_amount, '')}</td>
                        <td class="acct-num" style="font-weight:700;color:${bal > 0.01 ? '#ef4444' : '#059669'};">${fmtMoney(bal, '')}</td>
                        <td>
                            <input type="number" step="0.01" min="0" class="acct-input acct-num ap-pmt-alloc-amt" value="${allocAmt.toFixed(2)}" style="width:100%;">
                        </td>
                        <td>
                            <select class="acct-select ap-pmt-ewt-rule" style="width:100%;font-size:12px;">
                                <option value="1" data-atc="WC158" ${defaultRule === '1' ? 'selected' : ''}>WC158 - Goods (1%)</option>
                                <option value="2" data-atc="WC160" ${defaultRule === '2' ? 'selected' : ''}>WC160 - Services (2%)</option>
                                <option value="0" data-atc="NONE" ${defaultRule === '0' ? 'selected' : ''}>Exempt / No EWT (0%)</option>
                                <option value="custom" data-atc="CUSTOM">Manual EWT Input</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="acct-input acct-num ap-pmt-ewt-amt" value="${ewtAmt.toFixed(2)}" style="width:100%;" title="EWT Withheld amount (editable)">
                        </td>
                        <td class="acct-num ap-pmt-row-net" style="font-weight:700;color:#0f172a;">${fmtMoney(Math.max(0, allocAmt - ewtAmt), '')}</td>
                    </tr>
                `;
            }).join('');

            syncTotalInputFromPaymentRows();
        }

        document.getElementById('apPmtVendor')?.addEventListener('change', (e) => {
            renderPaymentAllocationsForVendor(e.target.value);
        });

        document.getElementById('apPmtSettlementMode')?.addEventListener('change', () => {
            const vId = document.getElementById('apPmtVendor')?.value;
            const checkedIds = new Set();
            document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(tr => {
                if (tr.querySelector('.ap-pmt-row-check')?.checked) {
                    checkedIds.add(String(tr.getAttribute('data-bill-id')));
                }
            });
            renderPaymentAllocationsForVendor(vId, checkedIds.size > 0 ? checkedIds : null);
        });

        // Quick Match Purchase Order dropdown inside Payment Drawer
        document.getElementById('apPmtPoQuickSelect')?.addEventListener('change', (e) => {
            const opt = e.target.options[e.target.selectedIndex];
            const poId = e.target.value;
            const poNo = opt?.getAttribute('data-po-no') || '';
            const poVendorId = opt?.getAttribute('data-vendor-id') || '';
            if (!poId && !poNo) return;

            const vendorSelect = document.getElementById('apPmtVendor');
            if (vendorSelect && poVendorId) {
                const matchedVendor = vendorsList.find(v => String(v.id) === String(poVendorId) || String(v.vendor_code) === String(poVendorId));
                vendorSelect.value = matchedVendor ? String(matchedVendor.id) : String(poVendorId);
                renderPaymentAllocationsForVendor(vendorSelect.value);
            }

            const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            let foundRow = false;
            document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(tr => {
                const rowPoId = tr.getAttribute('data-po-id');
                const rowPoNo = tr.getAttribute('data-po-no');
                const openBal = parseFloat(tr.getAttribute('data-open-balance')) || 0;
                const billSub = parseFloat(tr.getAttribute('data-bill-subtotal')) || parseFloat(tr.getAttribute('data-bill-total')) || openBal;
                const chk = tr.querySelector('.ap-pmt-row-check');
                const amtInput = tr.querySelector('.ap-pmt-alloc-amt');
                const ruleSel = tr.querySelector('.ap-pmt-ewt-rule');
                const ewtInput = tr.querySelector('.ap-pmt-ewt-amt');
                const isMatch = String(rowPoId) === String(poId) || (poNo && String(rowPoNo) === String(poNo));
                if (isMatch) {
                    foundRow = true;
                    tr.style.background = '#faf5ff';
                    if (chk) chk.checked = true;
                    if (mode === 'EWT_2307_ONLY') {
                        const pct = ruleSel && ruleSel.value !== 'custom' ? (parseFloat(ruleSel.value) || 1) : 1;
                        const ewtVal = (billSub * (pct / 100)).toFixed(2);
                        if (amtInput) amtInput.value = ewtVal;
                        if (ewtInput) ewtInput.value = ewtVal;
                    } else if (mode === 'BILL_ONLY') {
                        if (amtInput) amtInput.value = openBal.toFixed(2);
                        if (ruleSel) ruleSel.value = '0';
                        if (ewtInput) ewtInput.value = '0.00';
                    } else {
                        if (amtInput) amtInput.value = openBal.toFixed(2);
                        if (ruleSel && ruleSel.value !== 'custom' && ewtInput) {
                            const pct = parseFloat(ruleSel.value) || 0;
                            ewtInput.value = (openBal * (pct / 100)).toFixed(2);
                        }
                    }
                } else {
                    tr.style.background = '';
                    if (chk) chk.checked = false;
                    if (amtInput) amtInput.value = '0.00';
                    if (ewtInput) ewtInput.value = '0.00';
                }
            });

            if (foundRow) {
                syncTotalInputFromPaymentRows();
            }
        });

        function openPaymentModalForBill(billId, vendorIdOverride = null, modeOverride = 'COMBINED', poIdOverride = null) {
            const modeSel = document.getElementById('apPmtSettlementMode');
            if (modeSel) modeSel.value = modeOverride || 'COMBINED';

            const bill = (state.data.vendorBills || []).find(b =>
                (billId && String(b.vendor_bill_id) === String(billId))
                || (poIdOverride && String(b.purchase_order_id) === String(poIdOverride))
            );
            const targetBillId = billId || bill?.vendor_bill_id || '';
            const rawVendorId = vendorIdOverride || bill?.vendor_id || '';
            const matchedVendor = vendorsList.find(v => String(v.id) === String(rawVendorId) || String(v.vendor_code) === String(rawVendorId));
            const selectVendorVal = matchedVendor ? String(matchedVendor.id) : String(rawVendorId);

            const poQuick = document.getElementById('apPmtPoQuickSelect');
            if (poQuick) {
                poQuick.value = (poIdOverride || bill?.purchase_order_id) ? String(poIdOverride || bill.purchase_order_id) : '';
            }

            const vendorSelect = document.getElementById('apPmtVendor');
            if (vendorSelect && selectVendorVal) {
                vendorSelect.value = selectVendorVal;
                const preserveSet = targetBillId ? new Set([String(targetBillId)]) : null;
                renderPaymentAllocationsForVendor(selectVendorVal, preserveSet);
            }

            if (targetBillId) {
                document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(tr => {
                    if (String(tr.getAttribute('data-bill-id')) === String(targetBillId)) {
                        tr.style.background = '#faf5ff';
                    }
                });
            }

            openPaymentDrawer();
            recalcPaymentModalNet();
        }

        document.querySelectorAll('.btnApPayBill').forEach(btn => {
            btn.addEventListener('click', () => {
                const bId = btn.getAttribute('data-bill-id');
                const vId = btn.getAttribute('data-vendor-id');
                const poId = btn.getAttribute('data-po-id');
                openPaymentModalForBill(bId, vId, 'COMBINED', poId);
            });
        });

        document.querySelectorAll('.btnApSeparate2307').forEach(btn => {
            btn.addEventListener('click', () => {
                const bId = btn.getAttribute('data-bill-id');
                const vId = btn.getAttribute('data-vendor-id');
                openPaymentModalForBill(bId, vId, 'EWT_2307_ONLY');
            });
        });

        document.querySelectorAll('.btnApPayVendorFromLedger').forEach(btn => {
            btn.addEventListener('click', () => {
                const vId = btn.getAttribute('data-vendor-id');
                openPaymentModalForBill('', vId, 'COMBINED');
            });
        });

        const triggerDrawerPay = () => {
            if (!activeBillRecord) return;
            hideBillWorkspace();
            openPaymentModalForBill(activeBillRecord.vendor_bill_id, activeBillRecord.vendor_id, 'COMBINED');
        };
        document.getElementById('btnApDrawerPayBill')?.addEventListener('click', triggerDrawerPay);
        document.getElementById('btnApDrawerPayBillInline')?.addEventListener('click', triggerDrawerPay);

        // Quick Preset Buttons & Total Payment Input Waterfall Distribution
        document.getElementById('btnApPmtPresetMatch')?.addEventListener('click', () => {
            const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(row => {
                const chk = row.querySelector('.ap-pmt-row-check');
                const openBal = parseFloat(row.getAttribute('data-open-balance')) || 0;
                const billSub = parseFloat(row.getAttribute('data-bill-subtotal')) || parseFloat(row.getAttribute('data-bill-total')) || openBal;
                const amtInput = row.querySelector('.ap-pmt-alloc-amt');
                const ruleSel = row.querySelector('.ap-pmt-ewt-rule');
                const ewtInput = row.querySelector('.ap-pmt-ewt-amt');
                if (chk && chk.checked && amtInput) {
                    if (mode === 'EWT_2307_ONLY') {
                        const pct = (ruleSel && ruleSel.value !== 'custom') ? (parseFloat(ruleSel.value) || 1) : 1;
                        const ewtVal = (billSub * (pct / 100)).toFixed(2);
                        amtInput.value = ewtVal;
                        if (ewtInput) ewtInput.value = ewtVal;
                    } else if (mode === 'BILL_ONLY') {
                        amtInput.value = openBal.toFixed(2);
                        if (ewtInput) ewtInput.value = '0.00';
                    } else {
                        amtInput.value = openBal.toFixed(2);
                        if (ruleSel && ruleSel.value !== 'custom' && ewtInput) {
                            ewtInput.value = (openBal * ((parseFloat(ruleSel.value) || 0) / 100)).toFixed(2);
                        }
                    }
                }
            });
            syncTotalInputFromPaymentRows();
        });

        document.getElementById('btnApPmtPresetHalf')?.addEventListener('click', () => {
            const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(row => {
                const chk = row.querySelector('.ap-pmt-row-check');
                const openBal = parseFloat(row.getAttribute('data-open-balance')) || 0;
                const half = Math.round((openBal * 0.5 + Number.EPSILON) * 100) / 100;
                const amtInput = row.querySelector('.ap-pmt-alloc-amt');
                const ruleSel = row.querySelector('.ap-pmt-ewt-rule');
                const ewtInput = row.querySelector('.ap-pmt-ewt-amt');
                if (chk && chk.checked && amtInput) {
                    amtInput.value = half.toFixed(2);
                    if (mode === 'BILL_ONLY') {
                        if (ewtInput) ewtInput.value = '0.00';
                    } else if (ruleSel && ruleSel.value !== 'custom' && ewtInput) {
                        ewtInput.value = (half * ((parseFloat(ruleSel.value) || 0) / 100)).toFixed(2);
                    }
                }
            });
            syncTotalInputFromPaymentRows();
        });

        document.getElementById('apPmtTotalInput')?.addEventListener('input', () => {
            const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            const totalWanted = Math.max(0, parseFloat(document.getElementById('apPmtTotalInput')?.value) || 0);
            const checkedRows = Array.from(document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]'))
                .filter(row => row.querySelector('.ap-pmt-row-check')?.checked);

            if (checkedRows.length > 0) {
                let remaining = totalWanted;
                checkedRows.forEach((row, idx) => {
                    const openBal = parseFloat(row.getAttribute('data-open-balance')) || 0;
                    const amtInput = row.querySelector('.ap-pmt-alloc-amt');
                    const ruleSel = row.querySelector('.ap-pmt-ewt-rule');
                    const ewtInput = row.querySelector('.ap-pmt-ewt-amt');
                    const rowAlloc = (idx === checkedRows.length - 1)
                        ? Math.max(0, remaining)
                        : Math.min(openBal, Math.max(0, remaining));
                    remaining = Math.max(0, remaining - rowAlloc);
                    if (amtInput) amtInput.value = rowAlloc.toFixed(2);
                    if (mode === 'EWT_2307_ONLY' && ewtInput) {
                        ewtInput.value = rowAlloc.toFixed(2);
                    } else if (mode === 'BILL_ONLY' && ewtInput) {
                        ewtInput.value = '0.00';
                    } else if (ruleSel && ruleSel.value !== 'custom' && ewtInput) {
                        ewtInput.value = (rowAlloc * ((parseFloat(ruleSel.value) || 0) / 100)).toFixed(2);
                    }
                });
            }
            recalcPaymentModalNet();
        });

        // Dynamic Row Matching, EWT calculation & Overpayment-to-Credit tracking
        const pmtAllocTable = document.getElementById('apPmtAllocationsTable');
        if (pmtAllocTable) {
            pmtAllocTable.addEventListener('input', (e) => {
                const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
                if (e.target.matches('.ap-pmt-alloc-amt')) {
                    const row = e.target.closest('tr');
                    const chk = row?.querySelector('.ap-pmt-row-check');
                    const pay = parseFloat(e.target.value) || 0;
                    if (chk && pay > 0 && !chk.checked) chk.checked = true;
                    const ruleSel = row?.querySelector('.ap-pmt-ewt-rule');
                    const ewtInput = row?.querySelector('.ap-pmt-ewt-amt');
                    if (mode === 'EWT_2307_ONLY' && ewtInput) {
                        ewtInput.value = pay.toFixed(2);
                    } else if (mode === 'BILL_ONLY' && ewtInput) {
                        ewtInput.value = '0.00';
                    } else if (ruleSel && ruleSel.value !== 'custom' && ewtInput) {
                        const pct = parseFloat(ruleSel.value) || 0;
                        ewtInput.value = (pay * (pct / 100)).toFixed(2);
                    }
                    syncTotalInputFromPaymentRows();
                    return;
                } else if (e.target.matches('.ap-pmt-ewt-amt')) {
                    const row = e.target.closest('tr');
                    const ruleSel = row?.querySelector('.ap-pmt-ewt-rule');
                    if (ruleSel) ruleSel.value = 'custom';
                }
                recalcPaymentModalNet();
            });

            pmtAllocTable.addEventListener('change', (e) => {
                const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
                if (e.target.matches('.ap-pmt-row-check')) {
                    const row = e.target.closest('tr');
                    const openBal = parseFloat(row?.getAttribute('data-open-balance')) || 0;
                    const billSub = parseFloat(row?.getAttribute('data-bill-subtotal')) || parseFloat(row?.getAttribute('data-bill-total')) || openBal;
                    const amtInput = row?.querySelector('.ap-pmt-alloc-amt');
                    const ruleSel = row?.querySelector('.ap-pmt-ewt-rule');
                    const ewtInput = row?.querySelector('.ap-pmt-ewt-amt');
                    if (!e.target.checked) {
                        if (amtInput) amtInput.value = '0.00';
                        if (ewtInput) ewtInput.value = '0.00';
                    } else if (mode === 'EWT_2307_ONLY') {
                        const pct = (ruleSel && ruleSel.value !== 'custom') ? (parseFloat(ruleSel.value) || 1) : 1;
                        const ewtVal = (billSub * (pct / 100)).toFixed(2);
                        if (amtInput) amtInput.value = ewtVal;
                        if (ewtInput) ewtInput.value = ewtVal;
                    } else {
                        if (amtInput) amtInput.value = openBal.toFixed(2);
                        if (mode === 'BILL_ONLY' && ewtInput) {
                            ewtInput.value = '0.00';
                        } else if (ruleSel && ruleSel.value !== 'custom' && ewtInput) {
                            ewtInput.value = (openBal * ((parseFloat(ruleSel.value) || 0) / 100)).toFixed(2);
                        }
                    }
                    syncTotalInputFromPaymentRows();
                } else if (e.target.matches('.ap-pmt-ewt-rule')) {
                    const row = e.target.closest('tr');
                    const billSub = parseFloat(row?.getAttribute('data-bill-subtotal')) || parseFloat(row?.getAttribute('data-bill-total')) || 0;
                    const amtInput = row?.querySelector('.ap-pmt-alloc-amt');
                    const ewtInput = row?.querySelector('.ap-pmt-ewt-amt');
                    if (e.target.value !== 'custom' && ewtInput) {
                        const pct = parseFloat(e.target.value) || 0;
                        if (mode === 'EWT_2307_ONLY') {
                            const nextEwt = (billSub * (pct / 100)).toFixed(2);
                            if (amtInput) amtInput.value = nextEwt;
                            ewtInput.value = nextEwt;
                            syncTotalInputFromPaymentRows();
                            return;
                        }
                        const pay = parseFloat(amtInput?.value) || 0;
                        ewtInput.value = (pay * (pct / 100)).toFixed(2);
                    }
                    recalcPaymentModalNet();
                }
            });
        }

        function recalcPaymentModalNet() {
            const mode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            let rowAllocSum = 0;
            let totalMatched = 0;
            let totalEwt = 0;
            document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(row => {
                const chk = row.querySelector('.ap-pmt-row-check');
                const openBal = parseFloat(row.getAttribute('data-open-balance')) || 0;
                const pay = (chk && !chk.checked) ? 0 : (parseFloat(row.querySelector('.ap-pmt-alloc-amt')?.value) || 0);
                const ewt = (chk && !chk.checked) ? 0 : (parseFloat(row.querySelector('.ap-pmt-ewt-amt')?.value) || 0);
                const netCell = row.querySelector('.ap-pmt-row-net');
                if (netCell) netCell.textContent = fmtMoney(Math.max(0, pay - ewt), '');
                rowAllocSum += pay;
                totalMatched += mode === 'EWT_2307_ONLY' ? pay : Math.min(pay, openBal);
                totalEwt += ewt;
            });

            const inputTotal = parseFloat(document.getElementById('apPmtTotalInput')?.value) || 0;
            const totalPay = Math.max(inputTotal, rowAllocSum);
            const excessCredit = mode === 'EWT_2307_ONLY'
                ? 0
                : Math.max(0, Math.round((totalPay - totalMatched + Number.EPSILON) * 100) / 100);
            const net = Math.max(0, totalPay - totalEwt);

            const elGross = document.getElementById('apPmtKpiGross');
            const elMatched = document.getElementById('apPmtKpiMatched');
            const elEwt = document.getElementById('apPmtKpiEwt');
            const elDisp = document.getElementById('apPmtNetDisplay');
            const elExcessSub = document.getElementById('apPmtKpiExcessSub');
            const excessBanner = document.getElementById('apPmtExcessBanner');
            const excessDisp = document.getElementById('apPmtExcessCreditDisplay');

            if (elGross) elGross.textContent = fmtMoney(totalPay, cur);
            if (elMatched) elMatched.textContent = fmtMoney(totalMatched, cur);
            if (elEwt) elEwt.textContent = fmtMoney(totalEwt, cur);
            if (elDisp) elDisp.textContent = fmtMoney(net, cur);
            if (elExcessSub) {
                elExcessSub.textContent = `Excess Credit: ${fmtMoney(excessCredit, cur)}`;
            }
            if (excessBanner && excessDisp) {
                if (excessCredit > 0.009) {
                    excessDisp.textContent = fmtMoney(excessCredit, cur);
                    excessBanner.style.display = 'flex';
                } else {
                    excessBanner.style.display = 'none';
                }
            }
        }

        document.getElementById('btnApSubmitPayment')?.addEventListener('click', async () => {
            const vendorId = document.getElementById('apPmtVendor')?.value;
            const pmtDate = document.getElementById('apPmtDate')?.value;
            const method = document.getElementById('apPmtMethod')?.value;
            const bankAccId = document.getElementById('apPmtBankAccount')?.value;
            const settlementMode = document.getElementById('apPmtSettlementMode')?.value || 'COMBINED';
            const totalInputVal = parseFloat(document.getElementById('apPmtTotalInput')?.value) || 0;

            if (!vendorId) {
                notify('error', 'Please select a vendor.');
                return;
            }

            const allocations = [];
            let rowsTotal = 0;
            let ewtTotal = 0;

            document.querySelectorAll('#apPmtAllocationsBody tr[data-bill-id]').forEach(row => {
                const chk = row.querySelector('.ap-pmt-row-check');
                if (chk && !chk.checked) return;
                const billId = row.getAttribute('data-bill-id');
                const allocAmt = parseFloat(row.querySelector('.ap-pmt-alloc-amt')?.value) || 0;
                const ewtAmt = parseFloat(row.querySelector('.ap-pmt-ewt-amt')?.value) || 0;
                const ruleSel = row.querySelector('.ap-pmt-ewt-rule');
                const selectedAtc = ruleSel?.options[ruleSel.selectedIndex]?.getAttribute('data-atc') || 'WC158';
                const ewtRatePct = (ruleSel && ruleSel.value !== 'custom') ? (parseFloat(ruleSel.value) || 0) : 0;

                if (billId && (allocAmt > 0 || (settlementMode === 'EWT_2307_ONLY' && ewtAmt > 0))) {
                    allocations.push({
                        vendor_bill_id: billId,
                        allocated_amount: allocAmt > 0 ? allocAmt : ewtAmt,
                        ewt_amount: settlementMode === 'BILL_ONLY' ? 0 : ewtAmt,
                        ewt_rate_pct: settlementMode === 'BILL_ONLY' ? 0 : ewtRatePct,
                        ewt_tax_code: settlementMode === 'BILL_ONLY' ? 'NONE' : selectedAtc,
                    });
                    rowsTotal += (allocAmt > 0 ? allocAmt : ewtAmt);
                    ewtTotal += (settlementMode === 'BILL_ONLY' ? 0 : ewtAmt);
                }
            });

            const grossTotal = Math.max(totalInputVal, rowsTotal);
            if (allocations.length === 0 && grossTotal <= 0.009) {
                notify('error', 'Please select at least one PO/Bill obligation or enter a payment amount.');
                return;
            }

            const payment = {
                vendor_id: vendorId,
                payment_date: pmtDate,
                payment_method: method,
                settlement_mode: settlementMode,
                bank_account_id: bankAccId,
                reference_number: document.getElementById('apPmtReference')?.value || null,
                notes: document.getElementById('apPmtNotes')?.value || null,
                is_pdc: method === 'PDC',
                pdc_maturity_date: method === 'PDC' ? document.getElementById('apPmtPdcDate')?.value : null,
                total_amount: grossTotal,
                total_ewt_amount: ewtTotal,
                total_net_amount: Math.max(0, grossTotal - ewtTotal),
            };

            try {
                const res = await apiRequest(cfg.routes.apPaymentSave, 'POST', { payment, allocations });
                notify('success', res.message || 'Vendor Payment Voucher posted.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Unified Tab 2 Payment Register Search & Filter + BIR 2307 Preview Controls
        const filterPaymentsRegister = () => {
            const q = (document.getElementById('apPaymentSearch')?.value || '').trim().toLowerCase();
            const filterVal = (document.getElementById('apPaymentMethodFilter')?.value || '').trim().toUpperCase();
            document.querySelectorAll('#apPaymentsRegisterBody tr[data-pmt-method]').forEach(tr => {
                const hay = tr.getAttribute('data-search') || '';
                const m = tr.getAttribute('data-pmt-method') || '';
                const has2307 = tr.getAttribute('data-has-2307') === '1';
                let matchesFilter = true;
                if (filterVal === 'WITH_2307') matchesFilter = has2307;
                else if (filterVal === 'NO_2307') matchesFilter = !has2307;
                else if (filterVal) matchesFilter = m === filterVal;
                const show = (!q || hay.includes(q)) && matchesFilter;
                tr.style.display = show ? '' : 'none';
            });
        };
        document.getElementById('apPaymentSearch')?.addEventListener('input', filterPaymentsRegister);
        document.getElementById('apPaymentMethodFilter')?.addEventListener('change', filterPaymentsRegister);

        const modal2307 = document.getElementById('ap2307PreviewModal');
        const close2307 = () => { if (modal2307) modal2307.style.display = 'none'; };
        document.getElementById('btnApClose2307Modal')?.addEventListener('click', close2307);
        document.getElementById('btnApClose2307Btn')?.addEventListener('click', close2307);
        document.querySelectorAll('.btnApView2307').forEach(btn => {
            btn.addEventListener('click', () => {
                const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                setTxt('prev2307CertNo', btn.getAttribute('data-cert-no') || '--');
                setTxt('prev2307Voucher', btn.getAttribute('data-voucher') || '--');
                setTxt('prev2307Period', 'Period Covered: ' + (btn.getAttribute('data-period') || '--'));
                setTxt('prev2307Payee', btn.getAttribute('data-payee') || '--');
                setTxt('prev2307Tin', btn.getAttribute('data-tin') || '--');
                setTxt('prev2307Atc', btn.getAttribute('data-atc') || 'WC158');
                setTxt('prev2307Quarter', btn.getAttribute('data-quarter') || '--');
                setTxt('prev2307Signed', btn.getAttribute('data-signed') || 'Disbursement Officer');
                setTxt('prev2307TableAtc', btn.getAttribute('data-atc') || 'WC158');
                setTxt('prev2307TableQuarter', btn.getAttribute('data-quarter') || '--');
                setTxt('prev2307Income', `${cur} ${btn.getAttribute('data-income') || '0.00'}`);
                setTxt('prev2307Tax', `${cur} ${btn.getAttribute('data-tax') || '0.00'}`);
                if (modal2307) modal2307.style.display = 'flex';
            });
        });

        // Vendor Memo Modal Handlers
        const memoModal = document.getElementById('apMemoModalBackdrop');
        document.getElementById('btnApNewMemo')?.addEventListener('click', () => {
            if (memoModal) memoModal.style.display = 'flex';
        });
        document.getElementById('btnApCloseMemoModal')?.addEventListener('click', () => {
            if (memoModal) memoModal.style.display = 'none';
        });
        document.getElementById('btnApCancelMemo')?.addEventListener('click', () => {
            if (memoModal) memoModal.style.display = 'none';
        });

        document.getElementById('btnApSubmitMemo')?.addEventListener('click', async () => {
            const vendorId = document.getElementById('apMemoVendor')?.value;
            const amount = parseFloat(document.getElementById('apMemoAmount')?.value) || 0;
            const reason = document.getElementById('apMemoReason')?.value;

            if (!vendorId || amount <= 0 || !reason) {
                notify('error', 'Please fill in vendor, valid amount, and memo reason.');
                return;
            }

            const memo = {
                vendor_id: vendorId,
                memo_type: document.getElementById('apMemoType')?.value || 'CREDIT_MEMO',
                memo_date: document.getElementById('apMemoDate')?.value,
                amount: amount,
                target_vendor_bill_id: document.getElementById('apMemoTargetBill')?.value || null,
                reason_description: reason,
                reason_code: 'PRICE_OR_RETURN',
            };

            try {
                const res = await apiRequest(cfg.routes.apMemoSave, 'POST', { memo });
                notify('success', res.message || 'Vendor Memo posted.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        renderBillLines();
    }

    // =========================================================================
    // WAVE 2: ACCOUNTS RECEIVABLE (AR) CONTROLLER
    // =========================================================================

    function initAccountsReceivable() {
        const root = document.getElementById('accountingArRoot');
        if (!root) return;

        const cur = state.data.baseCurrency || 'PHP';
        const accounts = state.data.accounts || [];
        const revenueAccounts = accounts.filter(a => ['REVENUE', 'LIABILITY'].includes(a.account_type));
        const taxCodes = state.data.taxCodes || [];

        let arInvoiceLines = [];
        const defaultLineCount = 10;

        function createEmptyInvoiceLine() {
            return {
                description: '',
                quantity: 1,
                uom: 'Unit',
                unit_price: 0.00,
                tax_code: 'VAT_12',
                tax_amount: 0.00,
                line_total: 0.00,
                revenue_account_id: revenueAccounts[0]?.account_id || '',
            };
        }

        for (let i = 0; i < defaultLineCount; i++) {
            arInvoiceLines.push(createEmptyInvoiceLine());
        }

        function renderInvoiceLines() {
            const body = document.getElementById('arInvoiceLinesBody');
            if (!body) return;

            body.innerHTML = arInvoiceLines.map((line, idx) => `
                <tr data-line-idx="${idx}">
                    <td class="acct-mono" style="font-size:12px;color:#94a3b8;">${idx + 1}</td>
                    <td>
                        <input type="text" class="acct-input ar-line-desc" value="${escapeHtml(line.description)}" placeholder="Product / service rendered" style="width:100%;">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="acct-input acct-num ar-line-qty" value="${line.quantity}" style="width:100%;">
                    </td>
                    <td>
                        <input type="text" class="acct-input ar-line-uom" value="${escapeHtml(line.uom)}" style="width:100%;">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="acct-input acct-num ar-line-price" value="${line.unit_price}" style="width:100%;">
                    </td>
                    <td>
                        <select class="acct-select ar-line-tax" style="width:100%;">
                            ${taxCodes.map(tc => `<option value="${escapeHtml(tc.tax_code)}"${tc.tax_code === line.tax_code ? ' selected' : ''}>${escapeHtml(tc.tax_code)} (${tc.rate_pct}%)</option>`).join('')}
                        </select>
                    </td>
                    <td class="acct-num ar-line-tax-amt" style="font-size:12.5px;">${fmtMoney(line.tax_amount, '')}</td>
                    <td class="acct-num ar-line-total" style="font-size:12.5px;font-weight:600;">${fmtMoney(line.line_total, '')}</td>
                    <td>
                        <select class="acct-select ar-line-acc" style="width:100%;">
                            ${revenueAccounts.map(ra => `<option value="${escapeHtml(ra.account_id)}"${ra.account_id === line.revenue_account_id ? ' selected' : ''}>${escapeHtml(ra.account_code)} ${escapeHtml(ra.account_name)}</option>`).join('')}
                        </select>
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="acct-row-btn btnArDelInvoiceLine" data-line-idx="${idx}">
                            <i class="ph ph-trash" style="color:#ef4444;"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            recalcInvoiceTotals();
        }

        function recalcInvoiceTotals() {
            let subtotal = 0;
            let taxTotal = 0;

            const rows = document.querySelectorAll('#arInvoiceLinesBody tr');
            rows.forEach((row, idx) => {
                const line = arInvoiceLines[idx];
                if (!line) return;

                const qty = parseFloat(row.querySelector('.ar-line-qty')?.value) || 0;
                const price = parseFloat(row.querySelector('.ar-line-price')?.value) || 0;
                const taxCode = row.querySelector('.ar-line-tax')?.value || 'VAT_12';

                const tcObj = taxCodes.find(t => t.tax_code === taxCode);
                const rate = tcObj ? parseFloat(tcObj.rate_pct) : 12;

                const lineSubtotal = qty * price;
                const lineTax = (taxCode === 'EXEMPT' || taxCode === 'ZERO_RATED') ? 0 : lineSubtotal * (rate / 100);
                const lineTotal = lineSubtotal + lineTax;

                line.quantity = qty;
                line.unit_price = price;
                line.tax_code = taxCode;
                line.tax_amount = lineTax;
                line.line_total = lineTotal;
                line.description = row.querySelector('.ar-line-desc')?.value || '';
                line.uom = row.querySelector('.ar-line-uom')?.value || 'Unit';
                line.revenue_account_id = row.querySelector('.ar-line-acc')?.value || '';

                const elTax = row.querySelector('.ar-line-tax-amt');
                const elTot = row.querySelector('.ar-line-total');
                if (elTax) elTax.textContent = fmtMoney(lineTax, '');
                if (elTot) elTot.textContent = fmtMoney(lineTotal, '');

                if (line.description || line.quantity > 0) {
                    subtotal += lineSubtotal;
                    taxTotal += lineTax;
                }
            });

            const discount = parseFloat(document.getElementById('arInvoiceDiscount')?.value) || 0;
            const grandTotal = Math.max(0, subtotal + taxTotal - discount);

            const elSub = document.getElementById('arInvoiceSumSubtotal');
            const elTax = document.getElementById('arInvoiceSumTax');
            const elGrand = document.getElementById('arInvoiceSumGrandTotal');

            if (elSub) elSub.textContent = fmtMoney(subtotal, cur);
            if (elTax) elTax.textContent = fmtMoney(taxTotal, cur);
            if (elGrand) elGrand.textContent = fmtMoney(grandTotal, cur);

            // Check Customer Credit Limit
            const custSelect = document.getElementById('arInvoiceCustomer');
            const opt = custSelect?.selectedOptions[0];
            const alertBanner = document.getElementById('arCreditLimitAlert');
            const alertText = document.getElementById('arCreditLimitText');

            if (opt && opt.value) {
                const limit = parseFloat(opt.getAttribute('data-limit')) || 0;
                const bal = parseFloat(opt.getAttribute('data-balance')) || 0;
                const projected = bal + grandTotal;

                if (limit > 0 && projected > limit) {
                    if (alertBanner) alertBanner.style.display = 'flex';
                    if (alertText) alertText.textContent = `Credit Limit Exceeded: Customer limit is ${fmtMoney(limit, cur)}, current open balance is ${fmtMoney(bal, cur)}. Projected total is ${fmtMoney(projected, cur)}.`;
                } else {
                    if (alertBanner) alertBanner.style.display = 'none';
                }
            } else {
                if (alertBanner) alertBanner.style.display = 'none';
            }
        }

        // Lines table event delegation
        const invLinesTable = document.getElementById('arInvoiceLinesTable');
        if (invLinesTable) {
            invLinesTable.addEventListener('input', (e) => {
                if (e.target.matches('.ar-line-qty, .ar-line-price, .ar-line-desc, .ar-line-uom')) {
                    recalcInvoiceTotals();
                }
            });

            invLinesTable.addEventListener('change', (e) => {
                if (e.target.matches('.ar-line-tax, .ar-line-acc')) {
                    recalcInvoiceTotals();
                }
            });

            invLinesTable.addEventListener('click', (e) => {
                const btn = e.target.closest('.btnArDelInvoiceLine');
                if (btn) {
                    const idx = parseInt(btn.getAttribute('data-line-idx'), 10);
                    if (arInvoiceLines.length > 1) {
                        arInvoiceLines.splice(idx, 1);
                    } else {
                        arInvoiceLines = [createEmptyInvoiceLine()];
                    }
                    renderInvoiceLines();
                }
            });
        }

        document.getElementById('arInvoiceDiscount')?.addEventListener('input', recalcInvoiceTotals);
        document.getElementById('arInvoiceCustomer')?.addEventListener('change', recalcInvoiceTotals);

        document.getElementById('btnArAddInvoiceLine')?.addEventListener('click', () => {
            arInvoiceLines.push(createEmptyInvoiceLine());
            renderInvoiceLines();
        });

        document.getElementById('btnArNewInvoice')?.addEventListener('click', () => {
            document.getElementById('arInvoiceId').value = '';
            document.getElementById('arInvoicePoNum').value = '';
            document.getElementById('arInvoiceNotes').value = '';
            document.getElementById('arInvoiceNumberDisplay').textContent = 'AUTO-NUMBERED';
            document.getElementById('arInvoiceStatusBadge').textContent = 'NEW DRAFT';
            arInvoiceLines = [];
            for (let i = 0; i < defaultLineCount; i++) arInvoiceLines.push(createEmptyInvoiceLine());
            renderInvoiceLines();
        });

        // Submit Invoice
        async function submitInvoice(targetStatus) {
            const customerId = document.getElementById('arInvoiceCustomer')?.value;
            const invDate = document.getElementById('arInvoiceDate')?.value;
            const dueDate = document.getElementById('arInvoiceDueDate')?.value;

            if (!customerId) {
                notify('error', 'Please select a customer.');
                return;
            }

            const validLines = arInvoiceLines.filter(l => l.description.trim() !== '' || l.quantity > 0);
            if (validLines.length === 0) {
                notify('error', 'Please provide at least one valid invoice line.');
                return;
            }

            const header = {
                sales_invoice_id: document.getElementById('arInvoiceId')?.value || undefined,
                customer_id: customerId,
                customer_po_number: document.getElementById('arInvoicePoNum')?.value || null,
                invoice_date: invDate,
                due_date: dueDate,
                branch_id: document.getElementById('arInvoiceBranch')?.value || 'BR-01',
                currency_code: document.getElementById('arInvoiceCurrency')?.value || 'PHP',
                exchange_rate: parseFloat(document.getElementById('arInvoiceFxRate')?.value) || 1.0,
                discount_amount: parseFloat(document.getElementById('arInvoiceDiscount')?.value) || 0.0,
                notes: document.getElementById('arInvoiceNotes')?.value || null,
            };

            const payloadLines = validLines.map(l => ({
                description: l.description,
                quantity: l.quantity,
                uom: l.uom,
                unit_price: l.unit_price,
                tax_code: l.tax_code,
                gl_revenue_account_id: l.revenue_account_id || null,
            }));

            try {
                const res = await apiRequest(cfg.routes.arInvoiceSave, 'POST', {
                    header,
                    lines: payloadLines,
                    action: targetStatus,
                });
                notify('success', res.message || 'Sales Invoice saved successfully.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        }

        document.getElementById('btnArSaveInvoiceDraft')?.addEventListener('click', () => submitInvoice('DRAFT'));
        document.getElementById('btnArPostInvoice')?.addEventListener('click', () => submitInvoice('POSTED'));

        // Customer Modal
        const custModal = document.getElementById('arCustomerModalBackdrop');
        document.getElementById('btnArNewCustomer')?.addEventListener('click', () => {
            if (custModal) custModal.style.display = 'flex';
        });
        document.getElementById('btnArCloseCustomerModal')?.addEventListener('click', () => {
            if (custModal) custModal.style.display = 'none';
        });
        document.getElementById('btnArCancelCustomer')?.addEventListener('click', () => {
            if (custModal) custModal.style.display = 'none';
        });

        document.getElementById('btnArSubmitCustomer')?.addEventListener('click', async () => {
            const code = document.getElementById('arCustCode')?.value;
            const name = document.getElementById('arCustName')?.value;
            if (!code || !name) {
                notify('error', 'Customer Code and Customer Name are required.');
                return;
            }

            const payload = {
                customer_code: code,
                customer_name: name,
                tin: document.getElementById('arCustTin')?.value || null,
                payment_terms_code: document.getElementById('arCustTerms')?.value || 'NET30',
                credit_limit: parseFloat(document.getElementById('arCustCreditLimit')?.value) || 0.0,
                email: document.getElementById('arCustEmail')?.value || null,
            };

            try {
                const res = await apiRequest(cfg.routes.arCustomerSave, 'POST', payload);
                notify('success', res.message || 'Customer registered.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Collection Receipt Modal
        const rcpModal = document.getElementById('arReceiptModalBackdrop');
        document.getElementById('btnArNewReceipt')?.addEventListener('click', () => {
            if (rcpModal) rcpModal.style.display = 'flex';
        });
        document.getElementById('btnArCloseReceiptModal')?.addEventListener('click', () => {
            if (rcpModal) rcpModal.style.display = 'none';
        });
        document.getElementById('btnArCancelReceipt')?.addEventListener('click', () => {
            if (rcpModal) rcpModal.style.display = 'none';
        });

        document.getElementById('arRcpMethod')?.addEventListener('change', (e) => {
            const pdcBlock = document.getElementById('arRcpPdcBlock');
            if (pdcBlock) pdcBlock.style.display = e.target.value === 'PDC' ? 'block' : 'none';
        });

        document.getElementById('arRcpCustomer')?.addEventListener('change', (e) => {
            const cId = e.target.value;
            const body = document.getElementById('arRcpAllocationsBody');
            if (!body) return;

            const invoices = (state.data.salesInvoices || []).filter(inv => {
                const bal = parseFloat(inv.balance_due || (parseFloat(inv.total_amount) - parseFloat(inv.paid_amount)));
                return String(inv.customer_id) === String(cId) && bal > 0.01;
            });

            if (invoices.length === 0) {
                body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:16px;color:#94a3b8;">No open invoices for this customer.</td></tr>';
                return;
            }

            body.innerHTML = invoices.map(inv => {
                const bal = parseFloat(inv.balance_due || (parseFloat(inv.total_amount) - parseFloat(inv.paid_amount)));
                return `
                    <tr data-invoice-id="${escapeHtml(inv.invoice_id)}">
                        <td class="acct-mono">${escapeHtml(inv.invoice_number)}</td>
                        <td class="acct-num">${fmtMoney(inv.total_amount, '')}</td>
                        <td class="acct-num" style="font-weight:600;color:#ef4444;">${fmtMoney(bal, '')}</td>
                        <td>
                            <input type="number" step="0.01" min="0" max="${bal}" class="acct-input acct-num ar-rcp-alloc-amt" value="${bal.toFixed(2)}" style="width:100%;">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="acct-input acct-num ar-rcp-cwt-amt" value="0.00" style="width:100%;">
                        </td>
                    </tr>
                `;
            }).join('');

            recalcReceiptNet();
        });

        function recalcReceiptNet() {
            let totalColl = 0;
            let totalCwt = 0;
            document.querySelectorAll('#arRcpAllocationsBody tr').forEach(row => {
                const coll = parseFloat(row.querySelector('.ar-rcp-alloc-amt')?.value) || 0;
                const cwt = parseFloat(row.querySelector('.ar-rcp-cwt-amt')?.value) || 0;
                totalColl += coll;
                totalCwt += cwt;
            });
            const net = Math.max(0, totalColl - totalCwt);
            const elDisp = document.getElementById('arRcpNetDisplay');
            if (elDisp) elDisp.textContent = fmtMoney(net, cur);
        }

        document.getElementById('arRcpAllocationsTable')?.addEventListener('input', recalcReceiptNet);

        document.getElementById('btnArSubmitReceipt')?.addEventListener('click', async () => {
            const customerId = document.getElementById('arRcpCustomer')?.value;
            const rcpDate = document.getElementById('arRcpDate')?.value;
            const method = document.getElementById('arRcpMethod')?.value;
            const bankAccId = document.getElementById('arRcpBankAccount')?.value;

            if (!customerId) {
                notify('error', 'Please select a customer.');
                return;
            }

            const allocations = [];
            let grossTotal = 0;
            let cwtTotal = 0;

            document.querySelectorAll('#arRcpAllocationsBody tr').forEach(row => {
                const invId = row.getAttribute('data-invoice-id');
                const allocAmt = parseFloat(row.querySelector('.ar-rcp-alloc-amt')?.value) || 0;
                const cwtAmt = parseFloat(row.querySelector('.ar-rcp-cwt-amt')?.value) || 0;
                if (invId && allocAmt > 0) {
                    allocations.push({
                        sales_invoice_id: invId,
                        allocated_amount: allocAmt,
                        cwt_amount: cwtAmt,
                    });
                    grossTotal += allocAmt;
                    cwtTotal += cwtAmt;
                }
            });

            if (allocations.length === 0) {
                notify('error', 'Please enter collection amounts for at least one invoice.');
                return;
            }

            const receipt = {
                customer_id: customerId,
                receipt_date: rcpDate,
                payment_method: method,
                bank_account_id: bankAccId,
                reference_number: document.getElementById('arRcpReference')?.value || null,
                is_pdc: method === 'PDC',
                pdc_maturity_date: method === 'PDC' ? document.getElementById('arRcpPdcDate')?.value : null,
                bir_2307_form_number: document.getElementById('arRcp2307Num')?.value || null,
                bir_2307_quarter: document.getElementById('arRcp2307Quarter')?.value || null,
                total_amount: grossTotal,
                total_cwt_amount: cwtTotal,
                total_net_amount: grossTotal - cwtTotal,
            };

            try {
                const res = await apiRequest(cfg.routes.arReceiptSave, 'POST', { receipt, allocations });
                notify('success', res.message || 'Collection Receipt posted.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Customer Memo Modal
        const memoModal = document.getElementById('arMemoModalBackdrop');
        document.getElementById('btnArNewMemo')?.addEventListener('click', () => {
            if (memoModal) memoModal.style.display = 'flex';
        });
        document.getElementById('btnArCloseMemoModal')?.addEventListener('click', () => {
            if (memoModal) memoModal.style.display = 'none';
        });
        document.getElementById('btnArCancelMemo')?.addEventListener('click', () => {
            if (memoModal) memoModal.style.display = 'none';
        });

        document.getElementById('btnArSubmitMemo')?.addEventListener('click', async () => {
            const customerId = document.getElementById('arMemoCustomer')?.value;
            const amount = parseFloat(document.getElementById('arMemoAmount')?.value) || 0;
            const reason = document.getElementById('arMemoReason')?.value;

            if (!customerId || amount <= 0 || !reason) {
                notify('error', 'Please provide customer, valid amount, and memo reason.');
                return;
            }

            const memo = {
                customer_id: customerId,
                memo_type: document.getElementById('arMemoType')?.value || 'CREDIT_MEMO',
                memo_date: document.getElementById('arMemoDate')?.value,
                amount: amount,
                target_sales_invoice_id: document.getElementById('arMemoTargetInvoice')?.value || null,
                reason_description: reason,
                reason_code: 'SALES_RETURN_OR_DISCOUNT',
            };

            try {
                const res = await apiRequest(cfg.routes.arMemoSave, 'POST', { memo });
                notify('success', res.message || 'Customer Memo posted.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // DAIF Bounced Check Handling
        const bounceModal = document.getElementById('arBounceModalBackdrop');
        document.querySelectorAll('.btnArBounceCheck').forEach(btn => {
            btn.addEventListener('click', () => {
                const chkId = btn.getAttribute('data-check-id');
                const chkNum = btn.getAttribute('data-check-num');
                document.getElementById('arBounceCheckId').value = chkId;
                document.getElementById('arBounceCheckDisplay').textContent = '#' + chkNum;
                if (bounceModal) bounceModal.style.display = 'flex';
            });
        });

        document.getElementById('btnArCloseBounceModal')?.addEventListener('click', () => {
            if (bounceModal) bounceModal.style.display = 'none';
        });
        document.getElementById('btnArCancelBounce')?.addEventListener('click', () => {
            if (bounceModal) bounceModal.style.display = 'none';
        });

        document.getElementById('btnArSubmitBounce')?.addEventListener('click', async () => {
            const chkId = document.getElementById('arBounceCheckId')?.value;
            const reason = document.getElementById('arBounceReason')?.value;
            const penalty = parseFloat(document.getElementById('arBouncePenalty')?.value) || 500;

            if (!chkId || !reason) {
                notify('error', 'Bounce reason is required.');
                return;
            }

            try {
                const res = await apiRequest(routeWithId(cfg.routes.arCheckBounce, chkId), 'POST', {
                    bounce_reason: reason,
                    penalty_fee: penalty,
                });
                notify('success', res.message || 'Check flagged as DAIF bounced. Invoice reopened.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        renderInvoiceLines();
    }

    // =========================================================================
    // WAVE 2: CASH & BANK CONTROLLER
    // =========================================================================

    function initCashAndBank() {
        const root = document.getElementById('accountingCashBankRoot');
        if (!root) return;

        const cur = state.data.baseCurrency || 'PHP';
        const accounts = state.data.accounts || [];
        const expenseAccounts = accounts.filter(a => ['EXPENSE', 'COGS'].includes(a.account_type));

        // Bank Account Modal
        const accModal = document.getElementById('cbAccountModalBackdrop');
        document.getElementById('btnCbNewAccount')?.addEventListener('click', () => {
            if (accModal) accModal.style.display = 'flex';
        });
        document.getElementById('btnCbCloseAccountModal')?.addEventListener('click', () => {
            if (accModal) accModal.style.display = 'none';
        });
        document.getElementById('btnCbCancelAccount')?.addEventListener('click', () => {
            if (accModal) accModal.style.display = 'none';
        });

        document.getElementById('btnCbSubmitAccount')?.addEventListener('click', async () => {
            const name = document.getElementById('cbAccName')?.value;
            const bank = document.getElementById('cbAccBank')?.value;
            const num = document.getElementById('cbAccNumber')?.value;
            const glId = document.getElementById('cbAccGlAccount')?.value;

            if (!name || !bank || !num || !glId) {
                notify('error', 'Please fill in account name, bank name, account number, and linked GL account.');
                return;
            }

            const payload = {
                account_name: name,
                bank_name: bank,
                account_number: num,
                account_type: document.getElementById('cbAccType')?.value || 'CHECKING',
                gl_account_id: glId,
                opening_balance: parseFloat(document.getElementById('cbAccOpeningBal')?.value) || 0.0,
                custodian_name: document.getElementById('cbAccCustodian')?.value || null,
            };

            try {
                const res = await apiRequest(cfg.routes.cashBankSave, 'POST', payload);
                notify('success', res.message || 'Bank account created.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Employee Advance Modal
        const advModal = document.getElementById('cbAdvanceModalBackdrop');
        document.getElementById('btnCbNewAdvance')?.addEventListener('click', () => {
            if (advModal) advModal.style.display = 'flex';
        });
        document.getElementById('btnCbCloseAdvanceModal')?.addEventListener('click', () => {
            if (advModal) advModal.style.display = 'none';
        });
        document.getElementById('btnCbCancelAdvance')?.addEventListener('click', () => {
            if (advModal) advModal.style.display = 'none';
        });

        document.getElementById('btnCbSubmitAdvance')?.addEventListener('click', async () => {
            const empSelect = document.getElementById('cbAdvEmployee');
            const empId = empSelect?.value;
            const empName = empSelect?.selectedOptions[0]?.getAttribute('data-name');
            const amount = parseFloat(document.getElementById('cbAdvAmount')?.value) || 0;
            const purpose = document.getElementById('cbAdvPurpose')?.value;
            const disbAcc = document.getElementById('cbAdvDisbursementAcc')?.value;

            if (!empId || amount <= 0 || !purpose || !disbAcc) {
                notify('error', 'Please select an employee, valid amount, disbursement account, and purpose.');
                return;
            }

            const payload = {
                employee_id: empId,
                employee_name: empName,
                advance_date: document.getElementById('cbAdvDate')?.value,
                settlement_due_date: document.getElementById('cbAdvDueDate')?.value || null,
                amount: amount,
                disbursement_account_id: disbAcc,
                purpose: purpose,
            };

            try {
                const res = await apiRequest(cfg.routes.cashAdvanceSave, 'POST', payload);
                notify('success', res.message || 'Employee Advance disbursed.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Advance Liquidation Modal
        const liqModal = document.getElementById('cbLiquidationModalBackdrop');
        let liqItems = [];

        function renderLiqItems() {
            const body = document.getElementById('cbLiqItemsBody');
            if (!body) return;

            body.innerHTML = liqItems.map((item, idx) => `
                <tr data-item-idx="${idx}">
                    <td>
                        <select class="acct-select cb-liq-acc" style="width:100%;">
                            ${expenseAccounts.map(ea => `<option value="${escapeHtml(ea.account_id)}"${ea.account_id === item.gl_expense_account_id ? ' selected' : ''}>${escapeHtml(ea.account_code)} ${escapeHtml(ea.account_name)}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="acct-input cb-liq-desc" value="${escapeHtml(item.description)}" placeholder="Expense description" style="width:100%;">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="acct-input acct-num cb-liq-amt" value="${item.expense_amount}" style="width:100%;">
                    </td>
                    <td style="text-align:center;">
                        <button type="button" class="acct-row-btn btnCbDelLiqItem" data-item-idx="${idx}">
                            <i class="ph ph-trash" style="color:#ef4444;"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            recalcLiquidationDiff();
        }

        function recalcLiquidationDiff() {
            let totalExp = 0;
            document.querySelectorAll('#cbLiqItemsBody tr').forEach(row => {
                totalExp += parseFloat(row.querySelector('.cb-liq-amt')?.value) || 0;
            });

            const sel = document.getElementById('cbLiqAdvance');
            const bal = parseFloat(sel?.selectedOptions[0]?.getAttribute('data-bal')) || 0;

            const diff = bal - totalExp; // If > 0, refund due to company. If < 0, reimbursement due to employee.
            const elExp = document.getElementById('cbLiqTotalExpDisplay');
            const elDiff = document.getElementById('cbLiqDiffDisplay');
            const elLbl = document.getElementById('cbLiqDiffLabel');

            if (elExp) elExp.textContent = fmtMoney(totalExp, cur);
            if (elDiff) {
                elDiff.textContent = fmtMoney(Math.abs(diff), cur);
                elDiff.style.color = diff >= 0 ? '#10b981' : '#ef4444';
            }
            if (elLbl) elLbl.textContent = diff >= 0 ? 'Refund Due to Company (Return Unused):' : 'Reimbursement Due to Employee (Out-of-Pocket):';
        }

        document.getElementById('btnCbNewLiquidation')?.addEventListener('click', () => {
            liqItems = [{
                gl_expense_account_id: expenseAccounts[0]?.account_id || '',
                description: '',
                expense_amount: 0.00,
            }];
            renderLiqItems();
            if (liqModal) liqModal.style.display = 'flex';
        });
        document.getElementById('btnCbCloseLiquidationModal')?.addEventListener('click', () => {
            if (liqModal) liqModal.style.display = 'none';
        });
        document.getElementById('btnCbCancelLiquidation')?.addEventListener('click', () => {
            if (liqModal) liqModal.style.display = 'none';
        });

        document.getElementById('btnCbAddLiqItem')?.addEventListener('click', () => {
            liqItems.push({
                gl_expense_account_id: expenseAccounts[0]?.account_id || '',
                description: '',
                expense_amount: 0.00,
            });
            renderLiqItems();
        });

        document.getElementById('cbLiqItemsTable')?.addEventListener('input', recalcLiquidationDiff);
        document.getElementById('cbLiqAdvance')?.addEventListener('change', recalcLiquidationDiff);

        document.getElementById('cbLiqItemsTable')?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btnCbDelLiqItem');
            if (btn) {
                const idx = parseInt(btn.getAttribute('data-item-idx'), 10);
                if (liqItems.length > 1) {
                    liqItems.splice(idx, 1);
                }
                renderLiqItems();
            }
        });

        document.getElementById('btnCbSubmitLiquidation')?.addEventListener('click', async () => {
            const advId = document.getElementById('cbLiqAdvance')?.value;
            const liqDate = document.getElementById('cbLiqDate')?.value;
            const bankAcc = document.getElementById('cbLiqRefundBank')?.value;

            if (!advId) {
                notify('error', 'Please select an open employee advance.');
                return;
            }

            const items = [];
            document.querySelectorAll('#cbLiqItemsBody tr').forEach(row => {
                const accId = row.querySelector('.cb-liq-acc')?.value;
                const desc = row.querySelector('.cb-liq-desc')?.value || 'Travel / Field Expense';
                const amt = parseFloat(row.querySelector('.cb-liq-amt')?.value) || 0;
                if (amt > 0) {
                    items.push({
                        gl_expense_account_id: accId,
                        description: desc,
                        expense_amount: amt,
                    });
                }
            });

            if (items.length === 0) {
                notify('error', 'Please enter at least one expense item.');
                return;
            }

            const payload = {
                advance_id: advId,
                liquidation_date: liqDate,
                refund_bank_account_id: bankAcc,
                reimbursement_bank_account_id: bankAcc,
                items: items,
            };

            try {
                const res = await apiRequest(cfg.routes.cashAdvanceLiquidate, 'POST', payload);
                notify('success', res.message || 'Advance liquidated successfully.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Fund Transfer Modal
        const trfModal = document.getElementById('cbTransferModalBackdrop');
        document.getElementById('btnCbNewTransfer')?.addEventListener('click', () => {
            if (trfModal) trfModal.style.display = 'flex';
        });
        document.getElementById('btnCbCloseTransferModal')?.addEventListener('click', () => {
            if (trfModal) trfModal.style.display = 'none';
        });
        document.getElementById('btnCbCancelTransfer')?.addEventListener('click', () => {
            if (trfModal) trfModal.style.display = 'none';
        });

        document.getElementById('btnCbSubmitTransfer')?.addEventListener('click', async () => {
            const from = document.getElementById('cbTrfFrom')?.value;
            const to = document.getElementById('cbTrfTo')?.value;
            const amt = parseFloat(document.getElementById('cbTrfAmount')?.value) || 0;

            if (!from || !to || amt <= 0) {
                notify('error', 'Please select source, destination, and valid transfer amount.');
                return;
            }
            if (from === to) {
                notify('error', 'Source and destination accounts must be different.');
                return;
            }

            const payload = {
                source_bank_account_id: from,
                destination_bank_account_id: to,
                transfer_date: document.getElementById('cbTrfDate')?.value,
                amount: amt,
                fee_amount: parseFloat(document.getElementById('cbTrfFee')?.value) || 0.0,
                reference_number: document.getElementById('cbTrfRef')?.value || null,
            };

            try {
                const res = await apiRequest(cfg.routes.cashTransferSave, 'POST', payload);
                notify('success', res.message || 'Fund transfer posted.');
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                notify('error', err.message);
            }
        });

        // Clear PDC Check Button Delegation
        document.querySelectorAll('.btnCbClearCheck').forEach(btn => {
            btn.addEventListener('click', async () => {
                const chkId = btn.getAttribute('data-check-id');
                if (!chkId) return;

                if (!confirm('Clear this PDC check into Cash in Bank?')) return;

                try {
                    const res = await apiRequest(routeWithId(cfg.routes.cashCheckClear, chkId), 'POST', {});
                    notify('success', res.message || 'PDC check cleared.');
                    setTimeout(() => window.location.reload(), 1000);
                } catch (err) {
                    notify('error', err.message);
                }
            });
        });

        // Bank Reconciliation Controls
        const reconBank = document.getElementById('cbReconBank');
        const reconEndingBal = document.getElementById('cbReconEndingBal');

        function updateReconDisplays() {
            const opt = reconBank?.selectedOptions[0];
            const bookBal = parseFloat(opt?.getAttribute('data-balance')) || 0;
            const endingBal = parseFloat(reconEndingBal?.value) || 0;
            const diff = endingBal - bookBal;

            const elBook = document.getElementById('cbReconBookBalDisplay');
            const elStmt = document.getElementById('cbReconStmtBalDisplay');
            const elVar = document.getElementById('cbReconVarianceDisplay');

            if (elBook) elBook.textContent = fmtMoney(bookBal, cur);
            if (elStmt) elStmt.textContent = fmtMoney(endingBal, cur);
            if (elVar) {
                elVar.textContent = Math.abs(diff) < 0.01 ? '0.00 BALANCED' : fmtMoney(diff, cur);
                elVar.style.color = Math.abs(diff) < 0.01 ? '#10b981' : '#ef4444';
            }
        }

        reconBank?.addEventListener('change', updateReconDisplays);
        reconEndingBal?.addEventListener('input', updateReconDisplays);
        updateReconDisplays();

        document.getElementById('btnCbFinalizeRecon')?.addEventListener('click', async () => {
            const bankId = reconBank?.value;
            const date = document.getElementById('cbReconDate')?.value;
            const endingBal = parseFloat(reconEndingBal?.value) || 0;

            if (!bankId || !date) {
                notify('error', 'Bank account and statement cut-off date are required.');
                return;
            }

            try {
                const res = await apiRequest(cfg.routes.cashReconSave, 'POST', {
                    bank_account_id: bankId,
                    statement_date: date,
                    statement_ending_balance: endingBal,
                    statement_lines: [],
                });
                notify('success', res.message || 'Bank reconciliation saved.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });
    }

    // =========================================================================
    // WAVE 3: FIXED ASSETS & DEPRECIATION CONTROLLER
    // =========================================================================

    function initFixedAssets() {
        const cur = state.data.baseCurrency || 'PHP';
        const newModal = document.getElementById('acctFaNewModalBackdrop');
        const deprModal = document.getElementById('acctFaDeprModalBackdrop');
        const dispModal = document.getElementById('acctFaDisposeModalBackdrop');

        document.getElementById('btnFaNewAsset')?.addEventListener('click', () => {
            if (newModal) newModal.style.display = 'flex';
        });
        document.getElementById('btnFaRunDepreciation')?.addEventListener('click', () => {
            if (deprModal) deprModal.style.display = 'flex';
        });

        document.querySelectorAll('[data-acct-close="acctFaNewModalBackdrop"]').forEach(b => {
            b.addEventListener('click', () => { if (newModal) newModal.style.display = 'none'; });
        });
        document.querySelectorAll('[data-acct-close="acctFaDeprModalBackdrop"]').forEach(b => {
            b.addEventListener('click', () => { if (deprModal) deprModal.style.display = 'none'; });
        });
        document.querySelectorAll('[data-acct-close="acctFaDisposeModalBackdrop"]').forEach(b => {
            b.addEventListener('click', () => { if (dispModal) dispModal.style.display = 'none'; });
        });

        document.getElementById('faNewAssetForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                asset_name: document.getElementById('faName')?.value,
                asset_category: document.getElementById('faCategory')?.value,
                acquisition_date: document.getElementById('faAcqDate')?.value,
                acquisition_cost: parseFloat(document.getElementById('faCost')?.value) || 0,
                salvage_value: parseFloat(document.getElementById('faSalvage')?.value) || 0,
                useful_life_months: parseInt(document.getElementById('faUsefulLife')?.value, 10) || 60,
                branch_id: document.getElementById('faBranch')?.value || 'BR-01',
                department_id: document.getElementById('faDepartment')?.value || null,
                capitalize_immediately: document.getElementById('faCapitalizeImmediate')?.checked ?? true,
            };

            try {
                const res = await apiRequest(cfg.routes.fixedAssetSave, 'POST', payload);
                notify('success', res.message || 'Fixed Asset saved successfully.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });

        document.getElementById('faRunDeprForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                fiscal_period_id: document.getElementById('faDeprPeriod')?.value,
                depreciation_date: document.getElementById('faDeprDate')?.value,
                branch_id: document.getElementById('faDeprBranch')?.value || null,
            };

            try {
                const res = await apiRequest(cfg.routes.fixedAssetDepreciate, 'POST', payload);
                notify('success', res.message || 'Monthly depreciation posted.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });

        document.querySelectorAll('.btn-dispose-asset').forEach(btn => {
            btn.addEventListener('click', () => {
                const aid = btn.getAttribute('data-asset-id');
                const name = btn.getAttribute('data-asset-name');
                const nbv = btn.getAttribute('data-nbv');

                document.getElementById('dispAssetId').value = aid;
                document.getElementById('dispAssetName').value = `${name} (NBV: ${fmtMoney(nbv, cur)})`;
                if (dispModal) dispModal.style.display = 'flex';
            });
        });

        document.getElementById('faDisposeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const aid = document.getElementById('dispAssetId')?.value;
            if (!aid) return;

            const payload = {
                disposal_date: document.getElementById('dispDate')?.value,
                disposal_type: document.getElementById('dispType')?.value,
                proceeds_amount: parseFloat(document.getElementById('dispProceeds')?.value) || 0,
                disposal_notes: document.getElementById('dispReason')?.value || null,
            };

            try {
                const res = await apiRequest(routeWithId(cfg.routes.fixedAssetDispose, aid), 'POST', payload);
                notify('success', res.message || 'Asset derecognized and disposal journal posted.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });
    }

    // =========================================================================
    // WAVE 3: LOANS & DEBT FACILITIES CONTROLLER
    // =========================================================================

    function initLoans() {
        const cur = state.data.baseCurrency || 'PHP';
        const newModal = document.getElementById('acctLoanNewModalBackdrop');
        const payModal = document.getElementById('acctLoanPayModalBackdrop');

        document.getElementById('btnLoanNewFacility')?.addEventListener('click', () => {
            if (newModal) newModal.style.display = 'flex';
        });
        document.querySelectorAll('[data-acct-close="acctLoanNewModalBackdrop"]').forEach(b => {
            b.addEventListener('click', () => { if (newModal) newModal.style.display = 'none'; });
        });
        document.querySelectorAll('[data-acct-close="acctLoanPayModalBackdrop"]').forEach(b => {
            b.addEventListener('click', () => { if (payModal) payModal.style.display = 'none'; });
        });

        document.getElementById('loanNewFacilityForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                loan_reference: document.getElementById('loanRef')?.value,
                lender_name: document.getElementById('loanLender')?.value,
                principal_amount: parseFloat(document.getElementById('loanPrincipal')?.value) || 0,
                interest_rate_pct: parseFloat(document.getElementById('loanRate')?.value) || 0,
                term_months: parseInt(document.getElementById('loanTerm')?.value, 10) || 12,
                start_date: document.getElementById('loanStartDate')?.value,
                interest_calculation_method: document.getElementById('loanMethod')?.value || 'ANNUITY',
                bank_account_id: document.getElementById('loanBankAcc')?.value,
                branch_id: 'BR-01',
            };

            try {
                const res = await apiRequest(cfg.routes.loanSave, 'POST', payload);
                notify('success', res.message || 'Loan facility and amortization schedule created.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });

        document.querySelectorAll('.btn-pay-loan').forEach(btn => {
            btn.addEventListener('click', () => {
                const lid = btn.getAttribute('data-loan-id');
                const ref = btn.getAttribute('data-loan-ref');
                const out = btn.getAttribute('data-out');

                document.getElementById('payLoanId').value = lid;
                document.getElementById('payLoanRef').value = `${ref} (Outstanding: ${fmtMoney(out, cur)})`;
                if (payModal) payModal.style.display = 'flex';
            });
        });

        document.getElementById('loanRecordPaymentForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const lid = document.getElementById('payLoanId')?.value;
            if (!lid) return;

            const payload = {
                payment_date: document.getElementById('payDate')?.value,
                principal_amount: parseFloat(document.getElementById('payPrincipal')?.value) || 0,
                interest_amount: parseFloat(document.getElementById('payInterest')?.value) || 0,
                penalty_amount: parseFloat(document.getElementById('payPenalty')?.value) || 0,
                bank_account_id: document.getElementById('payBankAcc')?.value,
            };

            try {
                const res = await apiRequest(routeWithId(cfg.routes.loanPayment, lid), 'POST', payload);
                notify('success', res.message || 'Loan repayment voucher posted.');
                setTimeout(() => window.location.reload(), 1000);
            } catch (err) {
                notify('error', err.message);
            }
        });

        const scheduleSel = document.getElementById('loanScheduleSelector');
        function renderAmortization(loanId) {
            const tbody = document.getElementById('loanScheduleTableBody');
            if (!tbody) return;

            const loan = (state.data.loans || []).find(l => (l.loan_id || l['loan_id']) === loanId);
            const amorts = loan?.amortizations || [];

            if (!amorts.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:36px;color:#64748b;">No amortization schedule items found for this loan.</td></tr>';
                return;
            }

            tbody.innerHTML = amorts.map(a => `
                <tr>
                    <td class="acct-mono">${a.installment_number}</td>
                    <td>${escapeHtml(String(a.due_date).slice(0, 10))}</td>
                    <td class="acct-num" style="font-weight:700;">${fmtMoney(a.total_payment, cur)}</td>
                    <td class="acct-num">${fmtMoney(a.principal_component, cur)}</td>
                    <td class="acct-num">${fmtMoney(a.interest_component, cur)}</td>
                    <td class="acct-num">${fmtMoney(a.ending_principal_balance, cur)}</td>
                    <td><span class="acct-badge acct-badge-${a.status === 'PAID' ? 'posted' : 'draft'}">${escapeHtml(a.status)}</span></td>
                </tr>
            `).join('');
        }

        scheduleSel?.addEventListener('change', () => {
            renderAmortization(scheduleSel.value);
        });

        document.querySelectorAll('.btn-view-amortization').forEach(btn => {
            btn.addEventListener('click', () => {
                const lid = btn.getAttribute('data-loan-id');
                if (scheduleSel) scheduleSel.value = lid;
                renderAmortization(lid);
                document.querySelector('[data-acct-tab="schedule"]')?.click();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initTabs();
        initSystemToolbar();

        if (cfg.activePage === 'dashboard') initDashboard();
        if (cfg.activePage === 'gl') initGeneralLedger();
        if (cfg.activePage === 'ap') initAccountsPayable();
        if (cfg.activePage === 'ar') initAccountsReceivable();
        if (cfg.activePage === 'cash-bank') initCashAndBank();
        if (cfg.activePage === 'fixed-assets') initFixedAssets();
        if (cfg.activePage === 'loans') initLoans();
        if (cfg.activePage === 'config') initConfiguration();
        if (cfg.activePage === 'tax') initTaxPreview();
        if (cfg.activePage === 'reports') initReportsPreview();
    });
})();
