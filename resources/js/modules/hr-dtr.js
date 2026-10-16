let dtrAvailableTags = [];
let dtrEmployeesData = [];
let dtrSelectedTags = [];
let dtrTagsLoaded = false;
let currentDtrScope = 'all';

function openDtrExportModal() {
    const modal = document.getElementById('dtrExportModal');
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    if (!dtrTagsLoaded) {
        loadDtrTags();
    } else {
        updateDtrMatchCount();
    }
}

function closeDtrExportModal() {
    const modal = document.getElementById('dtrExportModal');
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
    const dropdown = document.getElementById('dtrTagDropdown');
    if (dropdown) dropdown.style.display = 'none';
}

function selectDtrScope(scope) {
    currentDtrScope = scope;
    const cardAll = document.getElementById('scopeCardAll');
    const cardFiltered = document.getElementById('scopeCardFiltered');
    const radioAll = document.getElementById('scopeRadioAll');
    const radioFiltered = document.getElementById('scopeRadioFiltered');
    const filterSection = document.getElementById('dtrTagFilterSection');

    if (scope === 'all') {
        cardAll.classList.add('active');
        cardFiltered.classList.remove('active');
        radioAll.checked = true;
        if (filterSection) filterSection.style.display = 'none';
    } else {
        cardFiltered.classList.add('active');
        cardAll.classList.remove('active');
        radioFiltered.checked = true;
        if (filterSection) filterSection.style.display = 'block';
        setTimeout(() => {
            const input = document.getElementById('dtrTagSearchInput');
            if (input) input.focus();
        }, 50);
    }
    updateDtrMatchCount();
}

function focusDtrTagSearch(e) {
    if (e.target.id !== 'dtrTagSearchInput' && !e.target.classList.contains('dtr-tag-remove')) {
        const input = document.getElementById('dtrTagSearchInput');
        if (input) input.focus();
    }
}

function setCutoffPreset(type) {
    const startInput = document.getElementById('dtrExportStartDate');
    const endInput = document.getElementById('dtrExportEndDate');
    if (!startInput || !endInput) return;

    let base = new Date(startInput.value || new Date());
    if (isNaN(base.getTime())) base = new Date();
    const year = base.getFullYear();
    const month = base.getMonth(); // 0-indexed

    const pad = (n) => String(n).padStart(2, '0');
    const monthStr = pad(month + 1);

    if (type === 'first_half') {
        startInput.value = `${year}-${monthStr}-01`;
        endInput.value = `${year}-${monthStr}-15`;
    } else if (type === 'second_half') {
        const lastDay = new Date(year, month + 1, 0).getDate();
        startInput.value = `${year}-${monthStr}-16`;
        endInput.value = `${year}-${monthStr}-${pad(lastDay)}`;
    } else if (type === 'full_month') {
        const lastDay = new Date(year, month + 1, 0).getDate();
        startInput.value = `${year}-${monthStr}-01`;
        endInput.value = `${year}-${monthStr}-${pad(lastDay)}`;
    }
}

function loadDtrTags() {
    const matchText = document.getElementById('dtrMatchCountText');
    if (matchText) matchText.textContent = 'Loading employee data & tags...';

    fetch(window.HrDtrConfig?.routes?.tags || '')
        .then(res => res.json())
        .then(data => {
            dtrAvailableTags = data.tags || [];
            dtrEmployeesData = data.employees || [];
            dtrTagsLoaded = true;
            updateDtrMatchCount();
        })
        .catch(err => {
            console.error('Failed to load DTR tags:', err);
            if (matchText) matchText.textContent = 'Failed to load dynamic tags';
        });
}

// Tag search autocomplete
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dtrTagSearchInput');
    const dropdown = document.getElementById('dtrTagDropdown');

    if (searchInput && dropdown) {
        searchInput.addEventListener('input', function() {
            renderTagDropdown(this.value);
        });

        searchInput.addEventListener('focus', function() {
            renderTagDropdown(this.value);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && dtrSelectedTags.length > 0) {
                removeDtrTag(dtrSelectedTags.length - 1);
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('dtrTagSearchContainer');
        const dropdown = document.getElementById('dtrTagDropdown');
        if (dropdown && container && !container.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

function renderTagDropdown(query) {
    const dropdown = document.getElementById('dtrTagDropdown');
    if (!dropdown) return;

    const q = (query || '').trim().toLowerCase();
    
    // Filter tags not already selected
    const unselectedTags = dtrAvailableTags.filter(t => {
        return !dtrSelectedTags.some(sel => sel.type === t.type && String(sel.value).toLowerCase() === String(t.value).toLowerCase());
    });

    let matched = unselectedTags;
    if (q) {
        matched = unselectedTags.filter(t => {
            const label = (t.label || '').toLowerCase();
            const cat = (t.category || '').toLowerCase();
            const sub = (t.subtext || '').toLowerCase();
            return label.includes(q) || cat.includes(q) || sub.includes(q);
        });
    }

    if (matched.length === 0) {
        dropdown.innerHTML = `<div style="padding: 12px; color: #94a3b8; font-size: 12px; text-align: center;">No matching employee attributes found</div>`;
        dropdown.style.display = 'block';
        return;
    }

    // Limit to top 25 matches for speed
    const list = matched.slice(0, 25);
    let html = '';
    list.forEach((t, i) => {
        const safeLabel = escapeHtml(t.label);
        const safeCat = escapeHtml(t.category);
        html += `
            <div class="dtr-tag-item" onclick="addDtrTag('${escapeJs(t.category)}', '${escapeJs(t.type)}', '${escapeJs(t.label)}', '${escapeJs(t.value)}')">
                <span style="font-weight: 500;">${safeLabel}</span>
                <span class="dtr-tag-item-cat">${safeCat}</span>
            </div>
        `;
    });

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
}

function addDtrTag(category, type, label, value) {
    dtrSelectedTags.push({ category, type, label, value });
    const searchInput = document.getElementById('dtrTagSearchInput');
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    const dropdown = document.getElementById('dtrTagDropdown');
    if (dropdown) dropdown.style.display = 'none';

    renderDtrTagPills();
    updateDtrMatchCount();
}

function removeDtrTag(index) {
    if (index >= 0 && index < dtrSelectedTags.length) {
        dtrSelectedTags.splice(index, 1);
        renderDtrTagPills();
        updateDtrMatchCount();
    }
}

function clearAllDtrTags() {
    dtrSelectedTags = [];
    renderDtrTagPills();
    updateDtrMatchCount();
    const searchInput = document.getElementById('dtrTagSearchInput');
    if (searchInput) searchInput.focus();
}

function renderDtrTagPills() {
    const wrapper = document.getElementById('dtrTagPillsWrapper');
    const clearBtn = document.getElementById('dtrClearAllBtn');
    const summaryText = document.getElementById('dtrTagSummaryText');
    const hiddenInput = document.getElementById('dtrSelectedTagsInput');

    if (!wrapper) return;

    if (dtrSelectedTags.length === 0) {
        wrapper.innerHTML = '';
        if (clearBtn) clearBtn.style.display = 'none';
        if (summaryText) summaryText.textContent = '0 tags applied';
        if (hiddenInput) hiddenInput.value = '[]';
        return;
    }

    if (clearBtn) clearBtn.style.display = 'inline-block';
    if (summaryText) summaryText.textContent = `${dtrSelectedTags.length} ${dtrSelectedTags.length === 1 ? 'tag' : 'tags'} applied`;
    if (hiddenInput) hiddenInput.value = JSON.stringify(dtrSelectedTags);

    let html = '';
    dtrSelectedTags.forEach((t, idx) => {
        html += `
            <span class="dtr-tag-pill">
                <span class="dtr-tag-cat">${escapeHtml(t.category)}</span>
                <span>${escapeHtml(t.label)}</span>
                <span class="dtr-tag-remove" onclick="removeDtrTag(${idx})" title="Remove tag">&times;</span>
            </span>
        `;
    });
    wrapper.innerHTML = html;
}

function updateDtrMatchCount() {
    const badge = document.getElementById('dtrMatchBadge');
    const countText = document.getElementById('dtrMatchCountText');
    const submitBtn = document.getElementById('btnSubmitDtrExport');
    const submitBtnText = document.getElementById('btnSubmitDtrExportText');

    if (!countText) return;

    if (currentDtrScope === 'all') {
        const total = dtrEmployeesData.length;
        countText.innerHTML = `<strong>${total}</strong> active employees selected`;
        if (submitBtn) submitBtn.disabled = total === 0;
        if (submitBtnText) submitBtnText.textContent = total > 0 ? `Generate DTR PDF (${total})` : 'Generate DTR PDF';
        return;
    }

    // Filtered mode
    if (dtrSelectedTags.length === 0) {
        countText.innerHTML = `<span style="color: #64748b;">No filter tags applied — add tags above to filter employees</span>`;
        if (submitBtn) submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = 'Generate DTR PDF';
        return;
    }

    // Group selected tags by type for intuitive faceted matching
    const grouped = {};
    dtrSelectedTags.forEach(t => {
        if (!grouped[t.type]) grouped[t.type] = [];
        grouped[t.type].push(String(t.value).toLowerCase());
    });

    let matchCount = 0;
    dtrEmployeesData.forEach(emp => {
        let employeeMatches = true;

        for (const type in grouped) {
            const values = grouped[type];
            let typeMatches = false;

            if (type === 'status') {
                const empStatuses = (emp.statuses || []).map(s => String(s).toLowerCase());
                typeMatches = values.some(v => empStatuses.includes(v));
            } else if (type === 'source') {
                const empSrc = String(emp.source || 'Company').toLowerCase();
                typeMatches = values.includes(empSrc);
            } else if (type === 'company_agency') {
                const empCos = (emp.companies || []).map(c => String(c).toLowerCase());
                typeMatches = values.some(v => empCos.includes(v));
            } else if (type === 'branch') {
                const bId = String(emp.branch_id || '').toLowerCase();
                const bName = String(emp.branch_name || '').toLowerCase();
                typeMatches = values.some(v => v === bId || v === bName);
            } else if (type === 'department') {
                const dId = String(emp.department_id || '').toLowerCase();
                const dName = String(emp.department_name || '').toLowerCase();
                typeMatches = values.some(v => v === dId || v === dName);
            } else if (type === 'position') {
                const pId = String(emp.position_id || '').toLowerCase();
                const pName = String(emp.position_name || '').toLowerCase();
                typeMatches = values.some(v => v === pId || v === pName);
            } else if (type === 'employee') {
                const eId = String(emp.id || '').toLowerCase();
                const empCode = String(emp.employee_id || '').toLowerCase();
                typeMatches = values.some(v => v === eId || v === empCode);
            } else {
                typeMatches = true;
            }

            if (!typeMatches) {
                employeeMatches = false;
                break; // AND across different categories
            }
        }

        if (employeeMatches) {
            matchCount++;
        }
    });

    if (matchCount === 0) {
        countText.innerHTML = `<span style="color: #ef4444; font-weight: 600;">0 employees match your filters</span>`;
        if (submitBtn) submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = 'Generate DTR PDF (0)';
    } else {
        const noun = matchCount === 1 ? 'employee matches' : 'employees match';
        countText.innerHTML = `<strong>${matchCount}</strong> ${noun} your filters`;
        if (submitBtn) submitBtn.disabled = false;
        if (submitBtnText) submitBtnText.textContent = `Generate DTR PDF (${matchCount})`;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openDtrExportModal,
    closeDtrExportModal,
    selectDtrScope,
    focusDtrTagSearch,
    setCutoffPreset,
    loadDtrTags,
    renderTagDropdown,
    addDtrTag,
    removeDtrTag,
    clearAllDtrTags,
    renderDtrTagPills,
    updateDtrMatchCount,
    escapeHtml,
    escapeJs,
});
