@extends('layouts.guest')

@section('title', 'Sign In - Restaurant Management System')

@section('content')
<div class="login-page-container">
    <div class="login-card">
        <!-- Brand Header -->
        <div class="login-header">
            <div class="login-brand-logo">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="RMS Logo" class="login-brand-logo-img">
            </div>
            <div class="login-brand-text">
                <span class="login-system-title">Restaurant Management System</span>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Enter your credentials to access your operations dashboard</p>
            </div>
        </div>

        <!-- Notification / Alert Badges -->
        @if (session('error'))
            <div class="login-alert danger" role="alert">
                <i class="ph ph-warning-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @elseif (request()->has('logged_out'))
            <div class="login-alert success" role="alert">
                <i class="ph ph-check-circle"></i>
                <span>You have been securely signed out.</span>
            </div>
        @elseif (request()->has('session_expired'))
            <div class="login-alert warning" role="alert">
                <i class="ph ph-clock"></i>
                <span>Your session has expired. Please sign in again.</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="login-alert danger" role="alert">
                <i class="ph ph-warning-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Portal Destination Switcher Tabs -->
        <div class="login-portal-selector" style="margin: 1.25rem 0 1.5rem 0; background: #f1f5f9; padding: 4px; border-radius: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 4px; border: 1px solid #e2e8f0;">
            <button type="button" class="portal-choice-btn {{ ($defaultPortal ?? 'erp') !== 'ess' ? 'active' : '' }}" id="portalChoiceErp" onclick="selectLoginPortal('erp')" style="border: none; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; transition: all 0.2s ease;">
                <i class="ph ph-buildings" style="font-size: 16px;"></i>
                <span>Management ERP</span>
            </button>
            <button type="button" class="portal-choice-btn {{ ($defaultPortal ?? 'erp') === 'ess' ? 'active' : '' }}" id="portalChoiceEss" onclick="selectLoginPortal('ess')" style="border: none; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; transition: all 0.2s ease;">
                <i class="ph ph-identification-card" style="font-size: 16px;"></i>
                <span>Employee Portal (ESS)</span>
            </button>
        </div>

        <!-- Login Form -->
        <form method="POST" action="{{ route('login.attempt') }}" class="login-form" novalidate id="loginForm">
            @csrf
            <input type="hidden" name="portal" id="portalInput" value="{{ $defaultPortal ?? 'erp' }}">

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
                        value="{{ old('identity') }}"
                        required 
                        autofocus 
                        autocomplete="username"
                        {{ session('locked') ? 'disabled' : '' }}
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
                        {{ session('locked') ? 'disabled' : '' }}
                    >
                    <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Show or hide password" aria-label="Toggle password visibility">
                        <i class="ph ph-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Options Row: Remember Me & Security Badge -->
            <div class="form-options-row">
                <label class="remember-me-checkbox">
                    <input type="checkbox" name="remember_me" value="1" {{ old('remember_me') ? 'checked' : '' }}>
                    <span class="custom-checkbox"></span>
                    <span class="checkbox-label">Remember me for 30 days</span>
                </label>
                <span class="security-badge" title="Encrypted session and brute-force protected">
                    <i class="ph ph-shield-check"></i> Encrypted
                </span>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="login-submit-btn" id="loginBtn" {{ session('locked') ? 'disabled' : '' }}>
                <span class="btn-text">Sign In</span>
                <i class="ph ph-arrow-right btn-icon"></i>
            </button>
        </form>

        <!-- Quick Demo Accounts Switcher -->
        <div class="quick-accounts-section" style="margin-top: 1.5rem;">
            <!-- ERP Quick Accounts -->
            <div id="erpQuickAccounts" style="{{ ($defaultPortal ?? 'erp') === 'ess' ? 'display:none;' : '' }}">
                <div class="quick-accounts-divider">
                    <span>Quick Sign In — Management ERP</span>
                </div>
                <div class="quick-accounts-grid">
                    <button type="button" class="demo-chip" data-user="peter" data-pass="Admin@12345" data-role="Admin" data-portal="erp" title="Sign in as Systems Admin">
                        <span class="demo-role-badge admin">Admin</span>
                        <span class="demo-name">peter</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="dorothy" data-pass="Manager@12345" data-role="Manager" data-portal="erp" title="Sign in as General Manager">
                        <span class="demo-role-badge manager">General Mgr</span>
                        <span class="demo-name">dorothy</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="manager_makati" data-pass="Manager@12345" data-role="Manager" data-portal="erp" title="Sign in as Branch Manager">
                        <span class="demo-role-badge manager">Branch Mgr</span>
                        <span class="demo-name">carlos</span>
                    </button>
                    <button type="button" class="demo-chip" data-user="cashier" data-pass="Cashier@12345" data-role="Cashier" data-portal="erp" title="Sign in as Cashier">
                        <span class="demo-role-badge cashier">Cashier</span>
                        <span class="demo-name">cashier</span>
                    </button>
                </div>
            </div>

            <!-- ESS Quick Accounts -->
            <div id="essQuickAccounts" style="{{ ($defaultPortal ?? 'erp') !== 'ess' ? 'display:none;' : '' }}">
                <div class="quick-accounts-divider">
                    <span style="color:#2563eb;font-weight:700;"><i class="ph ph-identification-card"></i> Quick Sign In — Employee Self-Service</span>
                </div>
                <div class="quick-accounts-grid">
                    <button type="button" class="demo-chip ess-chip" data-user="staff" data-pass="Staff@12345" data-role="Staff" data-portal="ess" title="Sarah Staff (Server - EMP-2026-STF)">
                        <span class="demo-role-badge staff" style="background:rgba(37,99,235,0.12);color:#2563eb;">Server</span>
                        <span class="demo-name">sarah.staff</span>
                    </button>
                    <button type="button" class="demo-chip ess-chip" data-user="cashier" data-pass="Cashier@12345" data-role="Cashier" data-portal="ess" title="John Cashier (Cashier - EMP-2026-CSH)">
                        <span class="demo-role-badge cashier">Cashier</span>
                        <span class="demo-name">john.cashier</span>
                    </button>
                    <button type="button" class="demo-chip ess-chip" data-user="kitchen" data-pass="Kitchen@12345" data-role="Kitchen" data-portal="ess" title="Marco Rossi (Cook - EMP-2026-KTC)">
                        <span class="demo-role-badge kitchen">Line Cook</span>
                        <span class="demo-name">marco.cook</span>
                    </button>
                    <button type="button" class="demo-chip ess-chip" data-user="hr_admin" data-pass="HrAdmin@12345" data-role="HR Admin" data-portal="ess" title="Elena Reyes (HR Manager - EMP-2026-HRA)">
                        <span class="demo-role-badge admin" style="background:rgba(124,58,237,0.12);color:#7c3aed;">HR Lead</span>
                        <span class="demo-name">elena.reyes</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer Meta -->
        <div class="login-footer">
            <span>Restaurant Management System &bull; Secure Portal</span>
        </div>
    </div>
</div>
@endsection


@push('scripts')
    @vite(['resources/js/modules/auth-login.js'])
    <script>
        function selectLoginPortal(portal) {
            const input = document.getElementById('portalInput');
            if (input) input.value = portal;

            const btnErp = document.getElementById('portalChoiceErp');
            const btnEss = document.getElementById('portalChoiceEss');
            const subtitle = document.querySelector('.login-subtitle');
            const erpAccounts = document.getElementById('erpQuickAccounts');
            const essAccounts = document.getElementById('essQuickAccounts');

            if (portal === 'ess') {
                if (btnEss) {
                    btnEss.classList.add('active');
                    btnEss.style.background = '#2563eb';
                    btnEss.style.color = '#ffffff';
                    btnEss.style.boxShadow = '0 4px 12px rgba(37,99,235,0.25)';
                }
                if (btnErp) {
                    btnErp.classList.remove('active');
                    btnErp.style.background = 'transparent';
                    btnErp.style.color = '#64748b';
                    btnErp.style.boxShadow = 'none';
                }
                if (erpAccounts) erpAccounts.style.display = 'none';
                if (essAccounts) essAccounts.style.display = 'block';
                if (subtitle) {
                    subtitle.textContent = 'Sign in to access your Philippine DOLE DTR, leaves, payslips, and self-service records';
                }
            } else {
                if (btnErp) {
                    btnErp.classList.add('active');
                    btnErp.style.background = '#ffffff';
                    btnErp.style.color = '#0f172a';
                    btnErp.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)';
                }
                if (btnEss) {
                    btnEss.classList.remove('active');
                    btnEss.style.background = 'transparent';
                    btnEss.style.color = '#64748b';
                    btnEss.style.boxShadow = 'none';
                }
                if (erpAccounts) erpAccounts.style.display = 'block';
                if (essAccounts) essAccounts.style.display = 'none';
                if (subtitle) {
                    subtitle.textContent = 'Enter your credentials to access your operations dashboard';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            selectLoginPortal('{{ $defaultPortal ?? 'erp' }}');

            // Wire up demo chips with automatic portal switching and field population
            document.querySelectorAll('.demo-chip').forEach(chip => {
                chip.addEventListener('click', function(e) {
                    e.preventDefault();
                    const portal = chip.getAttribute('data-portal');
                    if (portal) {
                        selectLoginPortal(portal);
                    }
                    const u = chip.getAttribute('data-user');
                    const p = chip.getAttribute('data-pass');
                    const identityInput = document.getElementById('identity');
                    const passwordInput = document.getElementById('password');
                    if (identityInput && passwordInput) {
                        identityInput.value = u;
                        passwordInput.value = p;
                        identityInput.classList.add('flash-highlight');
                        passwordInput.classList.add('flash-highlight');
                        setTimeout(() => {
                            identityInput.classList.remove('flash-highlight');
                            passwordInput.classList.remove('flash-highlight');
                        }, 500);
                        const loginBtn = document.getElementById('loginBtn');
                        if (loginBtn) loginBtn.focus();
                    }
                });
            });
        });
    </script>
@endpush
