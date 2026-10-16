/**
 * Training & Development Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open', 'active');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open', 'active');
}

Object.assign(window, {
    openModal,
    closeModal,
});
