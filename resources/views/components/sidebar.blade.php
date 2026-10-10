@php
    $isHome = request()->routeIs('dashboard');
    $isAccountSettings = request()->routeIs('account-settings*');
    $activeModule = '';
    if (request()->routeIs('hr.*')) $activeModule = 'hr';
    elseif (request()->routeIs('sales.*')) $activeModule = 'sales';
    elseif (request()->routeIs('inventory.*')) $activeModule = 'inventory';
    elseif (request()->routeIs('purchase.*')) $activeModule = 'purchase';
    elseif (request()->routeIs('config.*')) $activeModule = 'config';
    elseif (request()->routeIs('credits.*')) $activeModule = 'credits';

    $user = Auth::user();
    $userFullName = $user ? ($user->full_name ?? $user->username) : 'Dorothy Diaz';
    $userRole = $user ? $user->role : 'Manager';
@endphp

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay"></div>

<!-- Sidebar Container -->
<aside id="app-sidebar" class="sidebar no-nav-transition{{ ($isHome || $isAccountSettings) ? ' collapsed' : '' }}">
    <script>
        (function() {
            try {
                var sb = document.getElementById('app-sidebar');
                @if ($isHome || $isAccountSettings)
                    sb.classList.add('collapsed');
                    try { localStorage.setItem('rms_sidebar_collapsed', 'true'); } catch(e) {}
                @else
                    var state = localStorage.getItem('rms_sidebar_collapsed');
                    if (state === 'true') {
                        sb.classList.add('collapsed');
                    } else if (state === 'false') {
                        sb.classList.remove('collapsed');
                    }
                @endif
            } catch(e) {}
        })();
    </script>

    <!-- Left Rail -->
    <div class="sidebar-rail">
        <div class="rail-header">
            <a href="{{ route('dashboard') }}" class="brand-logo" aria-label="RMS Dashboard">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="RMS Logo" class="brand-logo-img">
            </a>
        </div>
        
        <nav class="rail-nav">
            <a href="{{ route('dashboard') }}" class="rail-item{{ $isHome ? ' active' : '' }}" data-tooltip="Home" data-title="Home" data-tooltip-pos="right"><i class="ph ph-house"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'hr' ? ' active' : '' }}" data-tooltip="HR Operations" data-title="HR Operations" data-target="submenu-hr" data-tooltip-pos="right"><i class="ph ph-users-three"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'sales' ? ' active' : '' }}" data-tooltip="Sales Operations" data-title="Sales Operations" data-target="submenu-sales" data-tooltip-pos="right"><i class="ph ph-chart-line-up"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'inventory' ? ' active' : '' }}" data-tooltip="Inventory Operations" data-title="Inventory Operations" data-target="submenu-inventory" data-tooltip-pos="right"><i class="ph ph-package"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'purchase' ? ' active' : '' }}" data-tooltip="Purchase Operations" data-title="Purchase Operations" data-target="submenu-purchase" data-tooltip-pos="right"><i class="ph ph-shopping-cart"></i></a>
        </nav>
        
        <div class="rail-footer">
            <a href="#" class="rail-item{{ $activeModule === 'credits' ? ' active' : '' }}" data-tooltip="Credits" data-title="Credits" data-target="submenu-credits" data-tooltip-pos="right"><i class="ph ph-info"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'config' ? ' active' : '' }}" data-tooltip="Settings" data-title="Settings" data-target="submenu-config" data-tooltip-pos="right"><i class="ph ph-gear"></i></a>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="sidebar-panel">
        <div class="panel-header">
            <div class="workspace-selector">
                <div class="workspace-icon">
                    <i class="ph ph-storefront"></i>
                </div>
                <div class="workspace-info">
                    <span class="workspace-name">Restaurant Name</span>
                </div>
            </div>
        </div>

        <!-- HR Operations -->
        <div id="submenu-hr" class="panel-content submenu{{ $activeModule === 'hr' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">HR Operations</span>
            </div>

            <!-- HR Dashboard -->
            <div class="nav-section">
                <a href="{{ route('hr.dashboard') }}" class="nav-item{{ request()->routeIs('hr.dashboard') ? ' active' : '' }}">
                    <i class="ph ph-chart-pie-slice"></i>
                    <span>HR Dashboard</span>
                </a>
            </div>

            <!-- Workforce -->
            <div class="nav-section">
                @php $workforceActive = request()->routeIs('hr.people.*', 'hr.employee', 'hr.recruitment.*'); @endphp
                <div class="nav-item-group" data-group-id="hr-people">
                    <a href="#" class="nav-item">
                        <i class="ph ph-users"></i>
                        <span>Workforce</span>
                    </a>
                    <button type="button" class="add-btn"><i class="ph {{ $workforceActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $workforceActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.people.employees') }}" class="sub-nav-item{{ request()->routeIs('hr.people.*', 'hr.employee') ? ' active' : '' }}" title="Employee Management, 201-files, and workforce directory"><span>Employee Management</span></a>
                    <a href="{{ route('hr.recruitment.vacancies') }}" class="sub-nav-item{{ request()->routeIs('hr.recruitment.*') ? ' active' : '' }}" title="Talent acquisition, job vacancies, candidate pipeline, and hiring"><span>Talent Acquisition</span></a>
                </div>
            </div>

            <!-- Time & Compensation -->
            <div class="nav-section">
                @php $timeCompActive = request()->routeIs('hr.attendance.*', 'hr.attendance-schedule', 'hr.attendance-checkin', 'hr.leave.*', 'hr.employee-leave', 'hr.payroll.*'); @endphp
                <div class="nav-item-group" data-group-id="hr-time">
                    <a href="#" class="nav-item">
                        <i class="ph ph-clock"></i>
                        <span>Time & Compensation</span>
                    </a>
                    <button type="button" class="add-btn"><i class="ph {{ $timeCompActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $timeCompActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.attendance.timekeeping') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance.*', 'hr.attendance-schedule', 'hr.attendance-checkin') ? ' active' : '' }}" title="Daily time records, punch logs, schedules, and overtime"><span>Time & Attendance</span></a>
                    <a href="{{ route('hr.leave.requests') }}" class="sub-nav-item{{ request()->routeIs('hr.leave.*', 'hr.employee-leave') ? ' active' : '' }}" title="Leave requests, leave types, balances, and accrual credits"><span>Leave & Absences</span></a>
                    <a href="{{ route('hr.payroll.periods') }}" class="sub-nav-item{{ request()->routeIs('hr.payroll.*') ? ' active' : '' }}" title="Pay period processing, automated statutory deductions, and payslips"><span>Payroll</span></a>
                </div>
            </div>

            <!-- Talent Lifecycle & Development -->
            <div class="nav-section">
                @php $talentActive = request()->routeIs('hr.performance.*', 'hr.training.*'); @endphp
                <div class="nav-item-group" data-group-id="hr-talent">
                    <a href="#" class="nav-item">
                        <i class="ph ph-sparkle"></i>
                        <span>Talent Lifecycle & Development</span>
                    </a>
                    <button type="button" class="add-btn"><i class="ph {{ $talentActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $talentActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.performance.periods') }}" class="sub-nav-item{{ request()->routeIs('hr.performance.*') ? ' active' : '' }}" title="Staff performance appraisal cycles, competency evaluations, and reviews"><span>Performance Management</span></a>
                    <a href="{{ route('hr.training.programs') }}" class="sub-nav-item{{ request()->routeIs('hr.training.*') ? ' active' : '' }}" title="Staff training programs, certifications, hygiene compliance, and course records"><span>Learning & Development</span></a>
                </div>
            </div>

            <!-- Administration -->
            <div class="nav-section">
                @php $adminActive = request()->routeIs('hr.admin.*', 'hr.users-auth'); @endphp
                <div class="nav-item-group" data-group-id="hr-admin">
                    <a href="#" class="nav-item">
                        <i class="ph ph-gear-six"></i>
                        <span>Administration</span>
                    </a>
                    <button type="button" class="add-btn"><i class="ph {{ $adminActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $adminActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.admin.users') }}" class="sub-nav-item{{ request()->routeIs('hr.admin.*', 'hr.users-auth') ? ' active' : '' }}" title="User accounts, role assignments, security permissions, and audit logs"><span>System Administration</span></a>
                </div>
            </div>

            <!-- Analytics Hub -->
            <div class="nav-section">
                @php $analyticsActive = request()->routeIs('hr.reports.*'); @endphp
                <div class="nav-item-group" data-group-id="hr-analytics">
                    <a href="#" class="nav-item">
                        <i class="ph ph-chart-polar"></i>
                        <span>Analytics Hub</span>
                    </a>
                    <button type="button" class="add-btn"><i class="ph {{ $analyticsActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $analyticsActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.reports.index') }}" class="sub-nav-item{{ request()->routeIs('hr.reports.*') ? ' active' : '' }}" title="Centralized export center for employee masterlist, attendance time logs, and statutory payroll registers"><span>Reports & Exports</span></a>
                </div>
            </div>
        </div>

        <!-- Sales Operations -->
        <div id="submenu-sales" class="panel-content submenu{{ $activeModule === 'sales' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">Sales Operations</span>
            </div>
            <div class="nav-section">
                <a href="{{ route('sales.dashboard') }}" class="nav-item{{ request()->routeIs('sales.dashboard') ? ' active' : '' }}">
                    <i class="ph ph-chart-line-up"></i>
                    <span>Sales Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                @php $salesMgtActive = request()->routeIs('sales.daily-sales', 'sales.payment-report', 'sales.reconciliations'); @endphp
                <div class="nav-item-group" data-group-id="sales-mgt">
                    <a href="#" class="nav-item">
                        <i class="ph ph-currency-dollar"></i>
                        <span>Sales Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $salesMgtActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $salesMgtActive ? ' expanded' : '' }}">
                    <a href="{{ route('sales.daily-sales') }}" class="sub-nav-item{{ request()->routeIs('sales.daily-sales') ? ' active' : '' }}"><span>Daily Sales Report</span></a>
                    <a href="{{ route('sales.payment-report') }}" class="sub-nav-item{{ request()->routeIs('sales.payment-report') ? ' active' : '' }}"><span>Payment Report</span></a>
                    <a href="{{ route('sales.reconciliations') }}" class="sub-nav-item{{ request()->routeIs('sales.reconciliations') ? ' active' : '' }}"><span>Reconciliations</span></a>
                </div>
            </div>
            <div class="nav-section">
                @php $promoActive = request()->routeIs('sales.discount-config', 'sales.voucher-config', 'sales.bundle-promotions'); @endphp
                <div class="nav-item-group" data-group-id="sales-promo">
                    <a href="#" class="nav-item">
                        <i class="ph ph-tag"></i>
                        <span>Promotion Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $promoActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $promoActive ? ' expanded' : '' }}">
                    <a href="{{ route('sales.discount-config') }}" class="sub-nav-item{{ request()->routeIs('sales.discount-config') ? ' active' : '' }}"><span>Discount Configuration</span></a>
                    <a href="{{ route('sales.voucher-config') }}" class="sub-nav-item{{ request()->routeIs('sales.voucher-config') ? ' active' : '' }}"><span>Voucher Configuration</span></a>
                    <a href="{{ route('sales.bundle-promotions') }}" class="sub-nav-item{{ request()->routeIs('sales.bundle-promotions') ? ' active' : '' }}"><span>Bundle Promotions</span></a>
                </div>
            </div>
            <div class="nav-section">
                @php $crmActive = request()->routeIs('sales.customer-masterlist'); @endphp
                <div class="nav-item-group" data-group-id="sales-crm">
                    <a href="#" class="nav-item">
                        <i class="ph ph-address-book"></i>
                        <span>Customer Relation Mgt.</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $crmActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $crmActive ? ' expanded' : '' }}">
                    <a href="{{ route('sales.customer-masterlist') }}" class="sub-nav-item{{ request()->routeIs('sales.customer-masterlist') ? ' active' : '' }}"><span>Customer Masterlist</span></a>
                </div>
            </div>
        </div>

        <!-- Inventory Operations -->
        <div id="submenu-inventory" class="panel-content submenu{{ $activeModule === 'inventory' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">Inventory Operations</span>
            </div>
            <div class="nav-section">
                <a href="{{ route('inventory.dashboard') }}" class="nav-item{{ request()->routeIs('inventory.dashboard') ? ' active' : '' }}">
                    <i class="ph ph-chart-bar"></i>
                    <span>Inventory Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                @php $invMgtActive = request()->routeIs('inventory.stocks-overview', 'inventory.beg-balance', 'inventory.stock-in', 'inventory.stock-out', 'inventory.internal-transfer', 'inventory.production', 'inventory.stock-adjustment', 'inventory.waste-expiry'); @endphp
                <div class="nav-item-group" data-group-id="inv-mgt">
                    <a href="#" class="nav-item">
                        <i class="ph ph-package"></i>
                        <span>Inventory Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $invMgtActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $invMgtActive ? ' expanded' : '' }}">
                    <a href="{{ route('inventory.stocks-overview') }}" class="sub-nav-item{{ request()->routeIs('inventory.stocks-overview') ? ' active' : '' }}"><span>Stocks Overview</span></a>
                    <a href="{{ route('inventory.beg-balance') }}" class="sub-nav-item{{ request()->routeIs('inventory.beg-balance') ? ' active' : '' }}"><span>Beg Balance</span></a>
                    <a href="{{ route('inventory.stock-in') }}" class="sub-nav-item{{ request()->routeIs('inventory.stock-in') ? ' active' : '' }}"><span>Stock In / Receiving</span></a>
                    <a href="{{ route('inventory.stock-out') }}" class="sub-nav-item{{ request()->routeIs('inventory.stock-out') ? ' active' : '' }}"><span>Stock Out / Usage</span></a>
                    <a href="{{ route('inventory.internal-transfer') }}" class="sub-nav-item{{ request()->routeIs('inventory.internal-transfer') ? ' active' : '' }}"><span>Internal Transfer</span></a>
                    <a href="{{ route('inventory.production') }}" class="sub-nav-item{{ request()->routeIs('inventory.production') ? ' active' : '' }}" title="Batch production runs, BOM recipe assembly, yield tracking, and ingredient stock deduction."><span>Production / Assembly</span></a>
                    <a href="{{ route('inventory.stock-adjustment') }}" class="sub-nav-item{{ request()->routeIs('inventory.stock-adjustment') ? ' active' : '' }}"><span>Stock Adjustment</span></a>
                    <a href="{{ route('inventory.waste-expiry') }}" class="sub-nav-item{{ request()->routeIs('inventory.waste-expiry') ? ' active' : '' }}"><span>Waste & Expiry</span></a>
                </div>
            </div>
            <div class="nav-section">
                @php $prodMgtActive = request()->routeIs('inventory.product-categories', 'inventory.recipe-management'); @endphp
                <div class="nav-item-group" data-group-id="inv-prod">
                    <a href="#" class="nav-item">
                        <i class="ph ph-pizza"></i>
                        <span>Product Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $prodMgtActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $prodMgtActive ? ' expanded' : '' }}">
                    <a href="{{ route('inventory.product-categories') }}" class="sub-nav-item{{ request()->routeIs('inventory.product-categories') ? ' active' : '' }}" title="Centralized catalog for managing raw materials, packaging, suppliers, units of measure (UOM), and stock pricing."><span>Item Master</span></a>
                    <a href="{{ route('inventory.recipe-management') }}" class="sub-nav-item{{ request()->routeIs('inventory.recipe-management') ? ' active' : '' }}" title="Configure product recipes, component quantities, automated inventory deductions, and production costing."><span>Bill of Materials (BOM)</span></a>
                </div>
            </div>
        </div>

        <!-- Purchase Operations -->
        <div id="submenu-purchase" class="panel-content submenu{{ $activeModule === 'purchase' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">Purchase Operations</span>
            </div>
            <div class="nav-section">
                <a href="{{ route('purchase.dashboard') }}" class="nav-item{{ request()->routeIs('purchase.dashboard') ? ' active' : '' }}">
                    <i class="ph ph-chart-bar"></i>
                    <span>Purchase Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                @php $purchMgtActive = request()->routeIs('purchase.request-quotations', 'purchase.purchase-orders'); @endphp
                <div class="nav-item-group" data-group-id="purch-mgt">
                    <a href="#" class="nav-item">
                        <i class="ph ph-shopping-cart"></i>
                        <span>Purchase Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $purchMgtActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $purchMgtActive ? ' expanded' : '' }}">
                    <a href="{{ route('purchase.request-quotations') }}" class="sub-nav-item{{ request()->routeIs('purchase.request-quotations') ? ' active' : '' }}"><span>Request for Quotations</span></a>
                    <a href="{{ route('purchase.purchase-orders') }}" class="sub-nav-item{{ request()->routeIs('purchase.purchase-orders') ? ' active' : '' }}"><span>Purchase Orders</span></a>
                </div>
            </div>
            <div class="nav-section">
                @php $vendorMgtActive = request()->routeIs('purchase.vendor-masterlist', 'purchase.vendor-bills'); @endphp
                <div class="nav-item-group" data-group-id="purch-vendor">
                    <a href="#" class="nav-item">
                        <i class="ph ph-truck"></i>
                        <span>Vendor Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $vendorMgtActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $vendorMgtActive ? ' expanded' : '' }}">
                    <a href="{{ route('purchase.vendor-masterlist') }}" class="sub-nav-item{{ request()->routeIs('purchase.vendor-masterlist') ? ' active' : '' }}"><span>Vendor Masterlist</span></a>
                    <a href="{{ route('purchase.vendor-bills') }}" class="sub-nav-item{{ request()->routeIs('purchase.vendor-bills') ? ' active' : '' }}"><span>Vendor Bills</span></a>
                </div>
            </div>
        </div>

        <!-- Credits -->
        <div id="submenu-credits" class="panel-content submenu{{ $activeModule === 'credits' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">Credits</span>
            </div>
            <div class="nav-section">
                <a href="{{ route('credits.tickets') }}" class="nav-item{{ request()->routeIs('credits.tickets') ? ' active' : '' }}">
                    <i class="ph ph-headset"></i>
                    <span>Tickets / Help Desk</span>
                </a>
                <a href="{{ route('credits.developers') }}" class="nav-item{{ request()->routeIs('credits.developers') ? ' active' : '' }}">
                    <i class="ph ph-code"></i>
                    <span>Developers</span>
                </a>
            </div>
        </div>

        <!-- Settings (Business Configuration) -->
        <div id="submenu-config" class="panel-content submenu{{ $activeModule === 'config' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">Settings</span>
            </div>
            <div class="nav-section">
                <a href="{{ route('config.business-settings') }}" class="nav-item{{ request()->routeIs('config.business-settings') ? ' active' : '' }}">
                    <i class="ph ph-storefront"></i>
                    <span>Business Settings</span>
                </a>
            </div>
        </div>

        <div class="panel-footer">
            <!-- User Profile Pop-up Menu -->
            <div class="user-profile-popup" id="userProfilePopup" role="menu" aria-label="User Account Menu">
                <div class="popup-user-header">
                    <div class="popup-avatar">
                        <i class="ph ph-user"></i>
                    </div>
                    <div class="popup-user-meta">
                        <span class="popup-user-name">{{ $userFullName }}</span>
                        <span class="popup-user-role">{{ $userRole }}</span>
                        @if($user && $user->email)
                            <span class="popup-user-email">{{ $user->email }}</span>
                        @endif
                    </div>
                </div>
                <div class="popup-menu-divider"></div>
                <div class="popup-menu-items">
                    <a href="{{ route('account-settings') }}" class="popup-menu-item{{ request()->routeIs('account-settings*') ? ' active' : '' }}" role="menuitem">
                        <div class="popup-item-icon">
                            <i class="ph ph-user-gear"></i>
                        </div>
                        <div class="popup-item-text">
                            <span class="popup-item-title">Account Settings</span>
                            <span class="popup-item-desc">Profile, password & security</span>
                        </div>
                        <i class="ph ph-caret-right popup-item-arrow"></i>
                    </a>
                    <div class="popup-menu-divider"></div>
                    <form method="POST" action="{{ route('logout') }}" id="popupLogoutForm">
                        @csrf
                        <button type="submit" class="popup-menu-item logout-item" role="menuitem">
                            <div class="popup-item-icon">
                                <i class="ph ph-sign-out"></i>
                            </div>
                            <div class="popup-item-text">
                                <span class="popup-item-title">Sign Out</span>
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Clickable User Profile Card -->
            <div class="user-profile{{ request()->routeIs('account-settings*') ? ' active' : '' }}" id="userProfileToggle" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" title="Click to view account options">
                <div class="user-avatar-icon">
                    <i class="ph ph-user"></i>
                </div>
                <div class="user-info">
                    <span class="user-name">{{ $userFullName }}</span>
                    <span class="user-role">{{ $userRole }}</span>
                </div>
                <div class="user-profile-actions">
                    <span class="user-popup-caret">
                        <i class="ph ph-caret-up"></i>
                    </span>
                    <form method="POST" action="{{ route('logout') }}" id="sidebarLogoutForm" style="display:inline;" onclick="event.stopPropagation();">
                        @csrf
                        <button type="submit" class="logout-btn" data-tooltip="Sign Out" aria-label="Sign Out" style="background:none;border:none;cursor:pointer;padding:0;">
                            <i class="ph ph-sign-out"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Immediate Pre-Paint Accordion State Restoration & Anti-Flicker Script -->
    <script>
        (function() {
            try {
                var sb = document.getElementById('app-sidebar');
                if (!sb) return;

                // 1. Immediately restore all expanded accordion groups from localStorage before paint
                var saved = [];
                try {
                    var raw = localStorage.getItem('rms_expanded_accordions');
                    saved = raw ? JSON.parse(raw) : [];
                    if (!Array.isArray(saved)) saved = [];
                } catch(e) {
                    saved = [];
                }

                var groups = sb.querySelectorAll('.nav-item-group[data-group-id]');
                var activeSet = new Set(saved);

                groups.forEach(function(g) {
                    var gid = g.getAttribute('data-group-id');
                    var sub = g.nextElementSibling;
                    if (!sub || !sub.classList.contains('sub-nav')) return;

                    var hasActive = !!sub.querySelector('.sub-nav-item.active');
                    if (hasActive) {
                        activeSet.add(gid);
                    }

                    if (activeSet.has(gid)) {
                        sub.classList.add('expanded');
                        var icon = g.querySelector('.add-btn i');
                        if (icon) {
                            icon.classList.remove('ph-plus', 'ph-caret-down');
                            icon.classList.add('ph-minus', 'ph-caret-up');
                        }
                    } else {
                        sub.classList.remove('expanded');
                        var icon = g.querySelector('.add-btn i');
                        if (icon) {
                            icon.classList.remove('ph-minus', 'ph-caret-up');
                            icon.classList.add('ph-plus', 'ph-caret-down');
                        }
                    }
                });

                try {
                    localStorage.setItem('rms_expanded_accordions', JSON.stringify(Array.from(activeSet)));
                } catch(e) {}

                // 2. Remove initial no-nav-transition after paint so clicks animate smoothly but tab switches never flicker
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        sb.classList.remove('no-nav-transition');
                    });
                });
            } catch(e) {}
        })();
    </script>
</aside>
