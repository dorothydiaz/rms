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

    <!-- ESS Portal Dedicated Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/ess-portal.css') }}?v={{ file_exists(public_path('assets/css/ess-portal.css')) ? filemtime(public_path('assets/css/ess-portal.css')) : '1.0' }}">

    @stack('styles')
</head>
<body class="ess-portal-body">

    <!-- Top Navigation Header -->
    <header class="ess-header">
        <div class="ess-header-inner">
            <!-- Brand -->
            <a href="{{ route('ess.dashboard') }}" class="ess-brand">
                <img src="{{ asset('assets/img/logo-tab.svg') }}" alt="Stellar One">
                <div class="ess-brand-text">
                    <span class="ess-brand-title">Stellar One</span>
                    <span class="ess-brand-badge">Employee Self-Service</span>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="ess-nav">
                <a href="{{ route('ess.dashboard') }}" class="ess-nav-link {{ request()->routeIs('ess.dashboard') ? 'active' : '' }}">
                    <i class="ph ph-squares-four"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('ess.dtr') }}" class="ess-nav-link {{ request()->routeIs('ess.dtr*') ? 'active' : '' }}">
                    <i class="ph ph-clock-countdown"></i>
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
                    <span>My Requests</span>
                </a>
                <a href="{{ route('ess.payroll') }}" class="ess-nav-link {{ request()->routeIs('ess.payroll*') ? 'active' : '' }}">
                    <i class="ph ph-receipt"></i>
                    <span>Payslips</span>
                </a>
                <a href="{{ route('ess.profile') }}" class="ess-nav-link {{ request()->routeIs('ess.profile*') ? 'active' : '' }}">
                    <i class="ph ph-user"></i>
                    <span>Profile</span>
                </a>
                <a href="{{ route('ess.helpdesk') }}" class="ess-nav-link {{ request()->routeIs('ess.helpdesk*') ? 'active' : '' }}">
                    <i class="ph ph-chats-circle"></i>
                    <span>Helpdesk</span>
                </a>
            </nav>

            <!-- Actions & User Profile -->
            <div class="ess-actions">
                <button type="button" class="ess-quick-btn" onclick="openQuickRequestModal()">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Request</span>
                </button>

                <div class="ess-user-menu" onclick="toggleUserDropdown(event)">
                    <div class="ess-user-avatar">
                        @if(!empty($currentEmployee->photo_url))
                            <img src="{{ $currentEmployee->photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            {{ $currentEmployee->initials ?? 'EM' }}
                        @endif
                    </div>
                    <div class="ess-user-info">
                        <span class="ess-user-name">{{ $currentEmployee->first_name ?? Auth::user()->name }}</span>
                        <span class="ess-user-role">{{ $currentEmployee->position->name ?? 'Staff' }}</span>
                    </div>
                    <i class="ph ph-caret-down" style="font-size:12px;color:var(--ess-text-muted);"></i>
                </div>

                <!-- User Dropdown Menu -->
                <div id="essUserDropdown" style="display:none;position:absolute;top:65px;right:24px;background:#fff;border:1px solid var(--ess-border);border-radius:12px;box-shadow:var(--ess-shadow-lg);padding:8px;min-width:210px;z-index:200;">
                    <div style="padding:10px 12px;border-bottom:1px solid var(--ess-border);margin-bottom:6px;">
                        <div style="font-weight:700;font-size:13px;">{{ $currentEmployee->full_name ?? Auth::user()->name }}</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">{{ $currentEmployee->company_email ?? Auth::user()->email }}</div>
                        <div style="font-size:10px;font-weight:700;color:var(--ess-primary);margin-top:2px;">{{ $currentEmployee->employee_id ?? 'ID: —' }}</div>
                    </div>
                    @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin() || Auth::user()->isManager())
                        <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:8px;padding:8px 12px;color:var(--ess-text-main);text-decoration:none;font-size:12.5px;font-weight:600;border-radius:8px;">
                            <i class="ph ph-arrows-left-right" style="color:var(--ess-primary);"></i>
                            <span>Switch to Admin ERP</span>
                        </a>
                    @endif
                    <a href="{{ route('ess.profile') }}" style="display:flex;align-items:center;gap:8px;padding:8px 12px;color:var(--ess-text-main);text-decoration:none;font-size:12.5px;font-weight:600;border-radius:8px;">
                        <i class="ph ph-user"></i>
                        <span>My 201 Profile</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="width:100%;text-align:left;display:flex;align-items:center;gap:8px;padding:8px 12px;color:var(--ess-danger);background:none;border:none;font-size:12.5px;font-weight:600;border-radius:8px;cursor:pointer;">
                            <i class="ph ph-sign-out"></i>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

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

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="ess-mobile-bottom-bar">
        <a href="{{ route('ess.dashboard') }}" class="ess-mobile-tab {{ request()->routeIs('ess.dashboard') ? 'active' : '' }}">
            <i class="ph ph-squares-four"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('ess.dtr') }}" class="ess-mobile-tab {{ request()->routeIs('ess.dtr*') ? 'active' : '' }}">
            <i class="ph ph-clock-countdown"></i>
            <span>DTR</span>
        </a>
        <button type="button" class="ess-mobile-action-btn" onclick="openQuickRequestModal()">
            <i class="ph ph-plus"></i>
        </button>
        <a href="{{ route('ess.leave') }}" class="ess-mobile-tab {{ request()->routeIs('ess.leave*') ? 'active' : '' }}">
            <i class="ph ph-airplane-tilt"></i>
            <span>Leaves</span>
        </a>
        <a href="{{ route('ess.payroll') }}" class="ess-mobile-tab {{ request()->routeIs('ess.payroll*') ? 'active' : '' }}">
            <i class="ph ph-receipt"></i>
            <span>Payslips</span>
        </a>
    </nav>

    <!-- Quick Request Modal Backdrop -->
    <div id="quickRequestModal" class="ess-modal-backdrop">
        <div class="ess-modal">
            <div class="ess-modal-header">
                <h3 class="ess-modal-title">Create New Request</h3>
                <button type="button" class="ess-modal-close" onclick="closeQuickRequestModal()">&times;</button>
            </div>
            <div class="ess-modal-body" style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                <a href="{{ route('ess.leave') }}?action=new" style="text-decoration:none;padding:16px;border:1px solid var(--ess-border);border-radius:12px;display:flex;align-items:center;gap:12px;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--ess-primary)'" onmouseout="this.style.borderColor='var(--ess-border)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(236,72,153,0.12);color:#ec4899;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="ph ph-airplane-tilt"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;">File Leave</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">SIL, Vacation, Sick, etc.</div>
                    </div>
                </a>

                <a href="{{ route('ess.requests') }}?type=OVERTIME" style="text-decoration:none;padding:16px;border:1px solid var(--ess-border);border-radius:12px;display:flex;align-items:center;gap:12px;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--ess-primary)'" onmouseout="this.style.borderColor='var(--ess-border)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(168,85,247,0.12);color:#a855f7;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="ph ph-clock-afternoon"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;">File Overtime</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">Pre/Post-shift & Rest Day</div>
                    </div>
                </a>

                <a href="{{ route('ess.dtr') }}?action=correction" style="text-decoration:none;padding:16px;border:1px solid var(--ess-border);border-radius:12px;display:flex;align-items:center;gap:12px;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--ess-primary)'" onmouseout="this.style.borderColor='var(--ess-border)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(245,158,11,0.12);color:#d97706;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="ph ph-fingerprint"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;">DTR Correction</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">Missing or failed punches</div>
                    </div>
                </a>

                <a href="{{ route('ess.schedule') }}?action=swap" style="text-decoration:none;padding:16px;border:1px solid var(--ess-border);border-radius:12px;display:flex;align-items:center;gap:12px;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--ess-primary)'" onmouseout="this.style.borderColor='var(--ess-border)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(14,165,233,0.12);color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="ph ph-arrows-clockwise"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Shift Swap</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">Exchange shifts with peer</div>
                    </div>
                </a>

                <a href="{{ route('ess.requests') }}?type=OFFICIAL_BUSINESS" style="text-decoration:none;padding:16px;border:1px solid var(--ess-border);border-radius:12px;display:flex;align-items:center;gap:12px;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--ess-primary)'" onmouseout="this.style.borderColor='var(--ess-border)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(16,185,129,0.12);color:#059669;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="ph ph-briefcase"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;">Official Business</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">Field work & client visits</div>
                    </div>
                </a>

                <a href="{{ route('ess.helpdesk') }}?action=new" style="text-decoration:none;padding:16px;border:1px solid var(--ess-border);border-radius:12px;display:flex;align-items:center;gap:12px;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='var(--ess-primary)'" onmouseout="this.style.borderColor='var(--ess-border)'">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:20px;">
                        <i class="ph ph-question"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;">HR Inquiry</div>
                        <div style="font-size:11px;color:var(--ess-text-muted);">Support ticket to HR</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function toggleUserDropdown(e) {
            e.stopPropagation();
            const d = document.getElementById('essUserDropdown');
            d.style.display = d.style.display === 'none' ? 'block' : 'none';
        }
        document.addEventListener('click', () => {
            const d = document.getElementById('essUserDropdown');
            if (d) d.style.display = 'none';
        });

        function openQuickRequestModal() {
            document.getElementById('quickRequestModal').classList.add('open');
        }
        function closeQuickRequestModal() {
            document.getElementById('quickRequestModal').classList.remove('open');
        }
    </script>

    @stack('scripts')
</body>
</html>
