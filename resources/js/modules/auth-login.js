    document.addEventListener('DOMContentLoaded', () => {
        // 1. Password Visibility Toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const isPass = passwordInput.type === 'password';
                passwordInput.type = isPass ? 'text' : 'password';
                toggleIcon.className = isPass ? 'ph ph-eye-slash' : 'ph ph-eye';
            });
        }

        // 2. Demo Account Click-to-Fill
        const demoChips = document.querySelectorAll('.demo-chip');
        const identityInput = document.getElementById('identity');

        demoChips.forEach(chip => {
            chip.addEventListener('click', () => {
                const u = chip.getAttribute('data-user');
                const p = chip.getAttribute('data-pass');
                if (identityInput && passwordInput) {
                    identityInput.value = u;
                    passwordInput.value = p;
                    
                    // Flash effect on inputs
                    identityInput.classList.add('flash-highlight');
                    passwordInput.classList.add('flash-highlight');
                    setTimeout(() => {
                        identityInput.classList.remove('flash-highlight');
                        passwordInput.classList.remove('flash-highlight');
                    }, 500);

                    // Give focus to submit button
                    const loginBtn = document.getElementById('loginBtn');
                    if (loginBtn) loginBtn.focus();
                }
            });
        });

        // 3. Form Submit Loading State
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        if (loginForm && loginBtn) {
            loginForm.addEventListener('submit', () => {
                try {
                    localStorage.setItem('rms_sidebar_collapsed', 'true');
                } catch (e) {}
                if (identityInput.value.trim() && passwordInput.value) {
                    loginBtn.classList.add('loading');
                    const textSpan = loginBtn.querySelector('.btn-text');
                    if (textSpan) textSpan.textContent = 'Authenticating...';
                }
            });
        }
    });