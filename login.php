<?php
// RMS Login Page Controller & View
require_once __DIR__ . '/config/auth.php';

// If already logged in, redirect straight to dashboard
if (is_authenticated()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$errorMessage = '';
$isLocked = false;
$identity = '';

// Handle POST Login Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = !empty($_POST['remember_me']);

    if (!verify_csrf_token($submittedToken)) {
        $errorMessage = "Security token mismatch or session expired. Please refresh and try again.";
    } elseif (empty($identity) || empty($password)) {
        $errorMessage = "Please enter both username/email and password.";
    } else {
        $loginResult = attempt_login($pdo, $identity, $password, $rememberMe);
        if ($loginResult['success']) {
            $redirectUrl = $_SESSION['redirect_url'] ?? (BASE_URL . 'index.php');
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $errorMessage = $loginResult['error'];
            $isLocked = $loginResult['locked'] ?? false;
        }
    }
}

// Generate CSRF token for the form
$csrfToken = get_csrf_token();
$loggedOutNotice = isset($_GET['logged_out']);
$sessionExpiredNotice = isset($_GET['session_expired']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Restaurant Management System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/styles.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800;900&family=Outfit:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-page-container">
        <div class="login-card">
            <!-- Brand Header -->
            <div class="login-header">
                <div class="login-brand-logo">
                    <i class="ph ph-fork-knife"></i>
                </div>
                <div class="login-brand-text">
                    <span class="login-system-title">Restaurant Management System</span>
                    <h1 class="login-title">Welcome Back</h1>
                    <p class="login-subtitle">Enter your credentials to access your operations dashboard</p>
                </div>
            </div>

            <!-- Notification / Alert Badges -->
            <?php if (!empty($errorMessage)): ?>
                <div class="login-alert danger" role="alert">
                    <i class="ph ph-warning-circle"></i>
                    <span><?= htmlspecialchars($errorMessage) ?></span>
                </div>
            <?php elseif ($loggedOutNotice): ?>
                <div class="login-alert success" role="alert">
                    <i class="ph ph-check-circle"></i>
                    <span>You have been securely signed out.</span>
                </div>
            <?php elseif ($sessionExpiredNotice): ?>
                <div class="login-alert warning" role="alert">
                    <i class="ph ph-clock"></i>
                    <span>Your session has expired. Please sign in again.</span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="<?= BASE_URL ?>login.php" class="login-form" novalidate id="loginForm">
                <!-- CSRF Protection Hidden Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Username or Email Field -->
                <div class="form-group">
                    <label for="identity" class="form-label">Username or Email</label>
                    <div class="input-wrapper">
                        <i class="ph ph-user input-icon"></i>
                        <input 
                            type="text" 
                            id="identity" 
                            name="identity" 
                            class="form-control" 
                            placeholder="e.g. admin or dorothy@rms.local" 
                            value="<?= htmlspecialchars($identity) ?>"
                            required 
                            autofocus 
                            autocomplete="username"
                            <?= $isLocked ? 'disabled' : '' ?>
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <div class="label-row">
                        <label for="password" class="form-label">Password</label>
                    </div>
                    <div class="input-wrapper">
                        <i class="ph ph-lock-key input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control password-input" 
                            placeholder="••••••••••••" 
                            required 
                            autocomplete="current-password"
                            <?= $isLocked ? 'disabled' : '' ?>
                        >
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Show or hide password" aria-label="Toggle password visibility">
                            <i class="ph ph-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Options Row: Remember Me & Security Badge -->
                <div class="form-options-row">
                    <label class="remember-me-checkbox">
                        <input type="checkbox" name="remember_me" value="1" <?= !empty($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span class="custom-checkbox"></span>
                        <span class="checkbox-label">Remember me for 30 days</span>
                    </label>
                    <span class="security-badge" title="Encrypted session and brute-force protected">
                        <i class="ph ph-shield-check"></i> Encrypted
                    </span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="login-submit-btn" id="loginBtn" <?= $isLocked ? 'disabled' : '' ?>>
                    <span class="btn-text">Sign In</span>
                    <i class="ph ph-arrow-right btn-icon"></i>
                </button>
            </form>

            <!-- Quick Demo Accounts Switcher -->
            <div class="quick-accounts-section">
                <div class="quick-accounts-divider">
                    <span>Quick Sign In for Testing</span>
                </div>
                <div class="quick-accounts-grid">
                    <button type="button" class="demo-chip" data-user="peter" data-pass="Admin@12345" data-role="Admin">
                        <span class="demo-role-badge admin">Admin</span>
                        <span class="demo-name">peter</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="dorothy" data-pass="Manager@12345" data-role="Manager">
                        <span class="demo-role-badge manager">Manager</span>
                        <span class="demo-name">dorothy</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="cashier" data-pass="Cashier@12345" data-role="Cashier">
                        <span class="demo-role-badge cashier">Cashier</span>
                        <span class="demo-name">cashier</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="staff" data-pass="Staff@12345" data-role="Staff">
                        <span class="demo-role-badge staff">Staff</span>
                        <span class="demo-name">staff</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="kitchen" data-pass="Kitchen@12345" data-role="Kitchen">
                        <span class="demo-role-badge kitchen">Kitchen</span>
                        <span class="demo-name">kitchen</span>
                    </button>
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="login-footer">
                <span>Restaurant Management System &bull; Secure Portal</span>
            </div>
        </div>
    </div>

    <!-- Interactive Client Script -->
    <script>
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
    </script>
</body>
</html>
