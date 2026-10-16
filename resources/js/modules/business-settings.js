/**
 * Business Settings Module
 */

function previewImage(input, previewId, placeholderId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById(previewId);
            const placeholder = document.getElementById(placeholderId);
            if (!preview) {
                preview = document.createElement('img');
                preview.id = previewId;
                preview.className = previewId === 'logoPreview' ? 'preview-logo-img' : 'preview-favicon-img';
                input.closest('.upload-dropzone-box').querySelector('.dropzone-preview-area').appendChild(preview);
            }
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function openUploadDocModal() {
    const modal = document.getElementById('uploadDocModal');
    if (modal) modal.style.display = 'flex';
}

function closeUploadDocModal() {
    const modal = document.getElementById('uploadDocModal');
    if (modal) modal.style.display = 'none';
}

function openAddBranchModal() {
    const modal = document.getElementById('addBranchModal');
    if (modal) modal.style.display = 'flex';
}

function closeAddBranchModal() {
    const modal = document.getElementById('addBranchModal');
    if (modal) modal.style.display = 'none';
}

function openEditBranchModal(branch) {
    const modal = document.getElementById('editBranchModal');
    const form = document.getElementById('editBranchForm');
    if (!modal || !form) return;

    form.action = `/config/business-settings/branches/${branch.branch_id}`;
    document.getElementById('editBrName').value = branch.branch_name || '';
    document.getElementById('editBrCode').value = branch.branch_code || '';
    document.getElementById('editBrType').value = branch.branch_type || 'Store / Dine-In';
    document.getElementById('editBrTaxCode').value = branch.branch_tax_code || '';
    document.getElementById('editBrHours').value = branch.operating_hours || '';
    document.getElementById('editBrContact').value = branch.branch_contact_person || '';
    document.getElementById('editBrPhone').value = branch.branch_phone_number || '';
    document.getElementById('editBrAddress').value = branch.branch_address_line_1 || '';
    document.getElementById('editBrCity').value = branch.branch_city || '';

    modal.style.display = 'flex';
}

function closeEditBranchModal() {
    const modal = document.getElementById('editBranchModal');
    if (modal) modal.style.display = 'none';
}

Object.assign(window, {
    previewImage,
    openUploadDocModal,
    closeUploadDocModal,
    openAddBranchModal,
    closeAddBranchModal,
    openEditBranchModal,
    closeEditBranchModal,
});
