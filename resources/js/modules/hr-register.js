/**
 * Enterprise Philippine Payroll - Payroll Register Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

export function openAdjModal(recId, name) {
    const adjRec = document.getElementById('adjRecordId');
    const adjStaff = document.getElementById('adjStaffName');
    if (adjRec) adjRec.value = recId;
    if (adjStaff) adjStaff.innerText = name;
    openModal('payrollAdjModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    openAdjModal,
});
