/**
 * Account Settings Module
 */

function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('ph-eye');
            icon.classList.add('ph-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('ph-eye-slash');
            icon.classList.add('ph-eye');
        }
    }
}

Object.assign(window, {
    togglePassVisibility,
});
