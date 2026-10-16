/**
 * Enterprise Philippine Payroll - Payslip Detail Module
 */

export function openPayslipModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'flex';
}

export function closePayslipModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}

Object.assign(window, {
    openPayslipModal,
    closePayslipModal,
});
