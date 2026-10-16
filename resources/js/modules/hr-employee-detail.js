function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Tab Switching
function switchProfileTab(tabName) {
    // Buttons
    document.querySelectorAll('.hr-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });
    // Panes
    document.querySelectorAll('.hr-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === 'tab-' + tabName);
    });
    // Persist in URL hash
    window.location.hash = tabName;
}

// On load hash check
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('tab-' + hash)) {
        switchProfileTab(hash);
    }
    // Check for ?action=edit
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'edit') {
        openModal('editEmployeeModal');
    }
});

// Profile Header More Actions dropdown
function toggleProfileMoreActions(e) {
    e.stopPropagation();
    const d = document.getElementById('profileMoreActionsDropdown');
    if (!d) return;
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
}

function closeProfileMoreActions() {
    const d = document.getElementById('profileMoreActionsDropdown');
    if (d) d.style.display = 'none';
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.hr-action-menu-wrap')) {
        closeProfileMoreActions();
    }
});

function syncEditPrimaryCheckbox(type, id) {
    if (!id) return;
    const cb = document.getElementById('cb_edit_' + type + '_' + id);
    if (cb) cb.checked = true;
}

// Sensitive Gov ID Masking Toggle
let govIdMasked = true;
function toggleGovIdMasking() {
    govIdMasked = !govIdMasked;
    const btnText = document.getElementById('maskBtnText');
    const icon = document.getElementById('maskIcon');
    const fields = document.querySelectorAll('.gov-id-field');

    if (govIdMasked) {
        btnText.textContent = 'Show ID Numbers';
        icon.className = 'ph ph-eye';
        fields.forEach(f => f.textContent = f.getAttribute('data-masked'));
    } else {
        btnText.textContent = 'Mask ID Numbers';
        icon.className = 'ph ph-eye-slash';
        fields.forEach(f => f.textContent = f.getAttribute('data-real'));
    }
}

// Filter Documents by Category
function filterDocCategory(category, btn) {
    document.querySelectorAll('.hr-pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('#empDocumentsTable tbody tr.doc-row');
    rows.forEach(r => {
        const c = r.getAttribute('data-category');
        if (category === 'all' || c === category) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// Replace Doc Modal trigger
function openReplaceDocModal(docId, docName) {
    const form = document.getElementById('replaceDocForm');
    form.action = `/hr/documents/${docId}/replace`;
    document.getElementById('replaceDocName').value = docName;
    openModal('replaceDocModal');
}

// Document Preview
function openDocPreview(fileUrl, docTitle) {
    document.getElementById('previewDocTitle').innerHTML = '<i class="ph ph-file-text"></i> ' + docTitle;
    document.getElementById('previewDocFrame').src = fileUrl;
    openModal('previewDocModal');
}
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    switchProfileTab,
    toggleProfileMoreActions,
    closeProfileMoreActions,
    syncEditPrimaryCheckbox,
    toggleGovIdMasking,
    filterDocCategory,
    openReplaceDocModal,
    openDocPreview,
});
