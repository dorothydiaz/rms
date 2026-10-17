<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee Self-Service') — Stellar One ERP</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo-tab.svg') }}">

    <!-- Google Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600;700;800&family=Outfit:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Custom RMS Stylesheet (HR Tokens, Bento Layout, Buttons, Badges, Tables) -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}?v={{ file_exists(public_path('assets/css/styles.css')) ? filemtime(public_path('assets/css/styles.css')) : '1.0' }}">

    <!-- ESS Portal Dedicated Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/ess-portal.css') }}?v={{ file_exists(public_path('assets/css/ess-portal.css')) ? filemtime(public_path('assets/css/ess-portal.css')) : time() }}">

    @stack('styles')
</head>
<body class="ess-portal-body">

    @php
        $emp = $currentEmployee ?? Auth::user()->employee;
        $initials = $emp ? strtoupper(substr($emp->first_name ?? 'E', 0, 1) . substr($emp->last_name ?? 'M', 0, 1)) : 'EM';
    @endphp

    <!-- Top Navigation Header -->
    <header class="ess-header">
        <div class="ess-header-inner">
            <!-- Brand -->
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="button" class="ess-mobile-toggle" onclick="toggleMobileDrawer(true)" aria-label="Open Navigation Menu">
                    <i class="ph ph-list"></i>
                </button>
                <a href="{{ route('ess.dashboard') }}" class="ess-brand">
                    <img src="{{ asset('assets/img/logo-tab.svg') }}" alt="Stellar One" onerror="this.src='{{ asset('assets/img/logo.svg') }}'">
                    <div class="ess-brand-text">
                        <span class="ess-brand-title">Stellar One</span>
                        <span class="ess-brand-badge">Employee Self-Service</span>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation Links (Single Clean Row) -->
            <nav class="ess-nav">
                <a href="{{ route('ess.dashboard') }}" class="ess-nav-link {{ request()->routeIs('ess.dashboard') ? 'active' : '' }}">
                    <i class="ph ph-squares-four"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('ess.dtr') }}" class="ess-nav-link {{ request()->routeIs('ess.dtr*') ? 'active' : '' }}">
                    <i class="ph ph-clock"></i>
                    <span>My DTR</span>
                </a>
                <a href="{{ route('ess.schedule') }}" class="ess-nav-link {{ request()->routeIs('ess.schedule*') ? 'active' : '' }}">
                    <i class="ph ph-calendar"></i>
                    <span>Schedule</span>
                </a>
                <a href="{{ route('ess.leave') }}" class="ess-nav-link {{ request()->routeIs('ess.leave*') ? 'active' : '' }}">
                    <i class="ph ph-airplane-tilt"></i>
                    <span>Leaves</span>
                </a>
                <a href="{{ route('ess.requests') }}" class="ess-nav-link {{ request()->routeIs('ess.requests*') ? 'active' : '' }}">
                    <i class="ph ph-files"></i>
                    <span>Requests</span>
                </a>
                <a href="{{ route('ess.payroll') }}" class="ess-nav-link {{ request()->routeIs('ess.payroll*') ? 'active' : '' }}">
                    <i class="ph ph-receipt"></i>
                    <span>Payslips</span>
                </a>
                <a href="{{ route('ess.profile') }}" class="ess-nav-link {{ request()->routeIs('ess.profile*') ? 'active' : '' }}">
                    <i class="ph ph-identification-badge"></i>
                    <span>201 File</span>
                </a>
                <a href="{{ route('ess.helpdesk') }}" class="ess-nav-link {{ request()->routeIs('ess.helpdesk*') ? 'active' : '' }}">
                    <i class="ph ph-chats-circle"></i>
                    <span>Helpdesk</span>
                </a>
            </nav>

            <!-- Actions & User Profile -->
            <div class="ess-actions">
                @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin() || Auth::user()->isManager())
                <a href="{{ route('dashboard') }}" class="hr-btn hr-btn-secondary" title="Return to Management Console" style="padding:6px 12px;font-size:12px;">
                    <i class="ph ph-arrows-left-right" style="color:var(--ess-primary);"></i>
                    <span>ERP Console</span>
                </a>
                @endif

                <div class="ess-user-menu" id="userMenuBtn" onclick="toggleUserDropdown(event)">
                    <div class="ess-user-avatar">
                        {{ $initials }}
                    </div>
                    <div class="ess-user-info">
                        <span class="ess-user-name">{{ $emp->first_name ?? Auth::user()->name }}</span>
                        <span class="ess-user-role">{{ $emp->position->position_title ?? 'Staff Member' }}</span>
                    </div>
                    <i class="ph ph-caret-down" style="font-size:12px;color:var(--ess-text-muted);"></i>
                </div>

                <!-- User Dropdown Menu -->
                <div id="essUserDropdown" class="ess-dropdown-menu">
                    <div class="ess-dropdown-header">
                        <div style="font-weight:700;font-size:13px;color:var(--ess-text-main);">{{ $emp->first_name ?? '' }} {{ $emp->last_name ?? Auth::user()->name }}</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">{{ $emp->work_email ?? Auth::user()->email }}</div>
                        <div style="font-size:10.5px;font-weight:700;color:var(--ess-primary);margin-top:4px;font-family:monospace;">{{ $emp->employee_code ?? 'EMP' }}</div>
                    </div>

                    <a href="{{ route('ess.profile') }}" class="ess-dropdown-item">
                        <i class="ph ph-identification-card" style="font-size:16px;color:var(--ess-primary);"></i>
                        <span>My 201 Digital File</span>
                    </a>
                    <a href="{{ route('ess.payroll') }}" class="ess-dropdown-item">
                        <i class="ph ph-receipt" style="font-size:16px;color:var(--ess-primary);"></i>
                        <span>Payslips & Tax Records</span>
                    </a>
                    <a href="{{ route('ess.helpdesk') }}" class="ess-dropdown-item">
                        <i class="ph ph-chat-circle-dots" style="font-size:16px;color:var(--ess-primary);"></i>
                        <span>HR Support & Helpdesk</span>
                    </a>

                    @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin() || Auth::user()->isManager())
                    <div class="ess-dropdown-divider"></div>
                    <a href="{{ route('dashboard') }}" class="ess-dropdown-item" style="color:var(--ess-primary);">
                        <i class="ph ph-buildings" style="font-size:16px;"></i>
                        <span>Switch to Management ERP</span>
                    </a>
                    @endif

                    <div class="ess-dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="ess-dropdown-item" style="width:100%;background:none;border:none;cursor:pointer;color:var(--ess-danger);">
                            <i class="ph ph-sign-out" style="font-size:16px;"></i>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Drawer -->
    <div id="essMobileDrawer" class="ess-mobile-drawer" onclick="if(event.target === this) toggleMobileDrawer(false)">
        <div class="ess-drawer-content">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--ess-border);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <img src="{{ asset('assets/img/logo-tab.svg') }}" alt="" style="height:32px;">
                    <strong style="font-size:16px;color:var(--ess-text-main);">Stellar One ESS</strong>
                </div>
                <button type="button" onclick="toggleMobileDrawer(false)" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--ess-text-muted);">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <!-- Employee Quick Card -->
            <div style="background:#f8fafc;padding:12px;border-radius:12px;border:1px solid var(--ess-border);margin-bottom:16px;">
                <div style="font-weight:700;font-size:13px;color:var(--ess-text-main);">{{ $emp->first_name ?? '' }} {{ $emp->last_name ?? Auth::user()->name }}</div>
                <div style="font-size:11.5px;color:var(--ess-text-muted);">{{ $emp->position->position_title ?? 'Employee' }}</div>
                <div style="font-size:10px;font-weight:700;color:var(--ess-primary);margin-top:2px;">{{ $emp->employee_code ?? '' }}</div>
            </div>

            <!-- Links -->
            <nav style="display:flex;flex-direction:column;gap:6px;flex:1;">
                <a href="{{ route('ess.dashboard') }}" class="ess-dropdown-item {{ request()->routeIs('ess.dashboard') ? 'active' : '' }}">
                    <i class="ph ph-squares-four" style="font-size:18px;"></i> <span>Dashboard</span>
                </a>
                <a href="{{ route('ess.dtr') }}" class="ess-dropdown-item {{ request()->routeIs('ess.dtr*') ? 'active' : '' }}">
                    <i class="ph ph-clock" style="font-size:18px;"></i> <span>My DTR</span>
                </a>
                <a href="{{ route('ess.schedule') }}" class="ess-dropdown-item {{ request()->routeIs('ess.schedule*') ? 'active' : '' }}">
                    <i class="ph ph-calendar" style="font-size:18px;"></i> <span>Work Schedule</span>
                </a>
                <a href="{{ route('ess.leave') }}" class="ess-dropdown-item {{ request()->routeIs('ess.leave*') ? 'active' : '' }}">
                    <i class="ph ph-airplane-tilt" style="font-size:18px;"></i> <span>Leaves & Absences</span>
                </a>
                <a href="{{ route('ess.requests') }}" class="ess-dropdown-item {{ request()->routeIs('ess.requests*') ? 'active' : '' }}">
                    <i class="ph ph-files" style="font-size:18px;"></i> <span>My Requests</span>
                </a>
                <a href="{{ route('ess.payroll') }}" class="ess-dropdown-item {{ request()->routeIs('ess.payroll*') ? 'active' : '' }}">
                    <i class="ph ph-receipt" style="font-size:18px;"></i> <span>Payroll & Payslips</span>
                </a>
                <a href="{{ route('ess.profile') }}" class="ess-dropdown-item {{ request()->routeIs('ess.profile*') ? 'active' : '' }}">
                    <i class="ph ph-identification-badge" style="font-size:18px;"></i> <span>201 Digital File</span>
                </a>
                <a href="{{ route('ess.helpdesk') }}" class="ess-dropdown-item {{ request()->routeIs('ess.helpdesk*') ? 'active' : '' }}">
                    <i class="ph ph-chats-circle" style="font-size:18px;"></i> <span>HR Helpdesk</span>
                </a>
            </nav>

            <div style="border-top:1px solid var(--ess-border);padding-top:14px;margin-top:14px;">
                @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin() || Auth::user()->isManager())
                <a href="{{ route('dashboard') }}" class="ess-dropdown-item" style="color:var(--ess-primary);margin-bottom:6px;">
                    <i class="ph ph-buildings" style="font-size:18px;"></i>
                    <span>Switch to Management ERP</span>
                </a>
                @endif
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="ess-dropdown-item" style="width:100%;background:none;border:none;cursor:pointer;color:var(--ess-danger);">
                        <i class="ph ph-sign-out" style="font-size:18px;"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content Canvas -->
    <main class="ess-main">
        @if (session('success'))
            <div style="background:var(--ess-success-bg);border:1px solid rgba(5,150,105,0.3);color:var(--ess-success);padding:12px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:600;font-size:13px;">
                <i class="ph ph-check-circle" style="font-size:18px;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div style="background:var(--ess-danger-bg);border:1px solid rgba(220,38,38,0.3);color:var(--ess-danger);padding:12px 18px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:600;font-size:13px;">
                <i class="ph ph-warning-circle" style="font-size:18px;"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar (Phones) -->
    <nav class="ess-mobile-bottom-bar">
        <a href="{{ route('ess.dashboard') }}" class="ess-bottom-link {{ request()->routeIs('ess.dashboard') ? 'active' : '' }}">
            <i class="ph ph-squares-four"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('ess.dtr') }}" class="ess-bottom-link {{ request()->routeIs('ess.dtr*') ? 'active' : '' }}">
            <i class="ph ph-clock"></i>
            <span>DTR</span>
        </a>
        <a href="{{ route('ess.schedule') }}" class="ess-bottom-link {{ request()->routeIs('ess.schedule*') ? 'active' : '' }}">
            <i class="ph ph-calendar"></i>
            <span>Schedule</span>
        </a>
        <a href="{{ route('ess.requests') }}" class="ess-bottom-link {{ request()->routeIs('ess.requests*') ? 'active' : '' }}">
            <i class="ph ph-files"></i>
            <span>Requests</span>
        </a>
        <a href="{{ route('ess.payroll') }}" class="ess-bottom-link {{ request()->routeIs('ess.payroll*') ? 'active' : '' }}">
            <i class="ph ph-receipt"></i>
            <span>Payslips</span>
        </a>
    </nav>

    <!-- Global Layout Scripts -->
    <script>
        function toggleUserDropdown(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('essUserDropdown');
            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        }

        function toggleMobileDrawer(show) {
            const drawer = document.getElementById('essMobileDrawer');
            if (drawer) {
                drawer.style.display = show ? 'block' : 'none';
            }
        }

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('essUserDropdown');
            const menuBtn = document.getElementById('userMenuBtn');
            if (dropdown && !dropdown.contains(e.target) && (!menuBtn || !menuBtn.contains(e.target))) {
                dropdown.style.display = 'none';
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
