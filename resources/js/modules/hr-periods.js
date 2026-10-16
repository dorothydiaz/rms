/**
 * Enterprise Philippine Payroll - Payroll Periods Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

Object.assign(window, {
    openModal,
    closeModal,
});
