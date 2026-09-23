@php
    $isHome = request()->routeIs('dashboard');
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
<aside id="app-sidebar" class="sidebar{{ $isHome ? ' collapsed' : '' }}">
    <script>
        (function() {
            try {
                var sb = document.getElementById('app-sidebar');
                @if ($isHome)
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
            <a href="{{ route('dashboard') }}" class="brand-logo" title="Restaurant Management System">
                <i class="ph ph-fork-knife"></i>
            </a>
        </div>
        
        <nav class="rail-nav">
            <a href="{{ route('dashboard') }}" class="rail-item{{ $isHome ? ' active' : '' }}" data-title="Home" title="Home"><i class="ph ph-house"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'hr' ? ' active' : '' }}" data-title="HR Operations" data-target="submenu-hr"><i class="ph ph-users-three"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'sales' ? ' active' : '' }}" data-title="Sales Operations" data-target="submenu-sales"><i class="ph ph-chart-line-up"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'inventory' ? ' active' : '' }}" data-title="Inventory Operations" data-target="submenu-inventory"><i class="ph ph-package"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'purchase' ? ' active' : '' }}" data-title="Purchase Operations" data-target="submenu-purchase"><i class="ph ph-shopping-cart"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'config' ? ' active' : '' }}" data-title="Business Config" data-target="submenu-config"><i class="ph ph-storefront"></i></a>
        </nav>
        
        <div class="rail-footer">
            <a href="#" class="rail-item{{ $activeModule === 'credits' ? ' active' : '' }}" data-title="Credits" data-target="submenu-credits"><i class="ph ph-info"></i></a>
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
            <div class="nav-section">
                <a href="{{ route('hr.dashboard') }}" class="nav-item{{ request()->routeIs('hr.dashboard') ? ' active' : '' }}">
                    <i class="ph ph-chart-bar"></i>
                    <span>HR Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                @php $empActive = request()->routeIs('hr.employee', 'hr.users-auth'); @endphp
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-users"></i>
                        <span>Employee Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $empActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $empActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.employee') }}" class="sub-nav-item{{ request()->routeIs('hr.employee') ? ' active' : '' }}"><span>Employee</span></a>
                    <a href="{{ route('hr.users-auth') }}" class="sub-nav-item{{ request()->routeIs('hr.users-auth') ? ' active' : '' }}"><span>Users & Authentication</span></a>
                </div>
            </div>
            <div class="nav-section">
                @php $payrollActive = request()->routeIs('hr.attendance-schedule', 'hr.attendance-checkin', 'hr.employee-leave'); @endphp
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-wallet"></i>
                        <span>Payroll</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $payrollActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $payrollActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.attendance-schedule') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance-schedule') ? ' active' : '' }}"><span>Attendance Schedule</span></a>
                    <a href="{{ route('hr.attendance-checkin') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance-checkin') ? ' active' : '' }}"><span>Attendance Check IN /OUT</span></a>
                    <a href="{{ route('hr.employee-leave') }}" class="sub-nav-item{{ request()->routeIs('hr.employee-leave') ? ' active' : '' }}"><span>Employee Leave / Time Request</span></a>
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
                <div class="nav-item-group">
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
                <div class="nav-item-group">
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
                <div class="nav-item-group">
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
                @php $invMgtActive = request()->routeIs('inventory.stocks-overview', 'inventory.beg-balance', 'inventory.stock-in', 'inventory.stock-out', 'inventory.stock-adjustment', 'inventory.waste-expiry'); @endphp
                <div class="nav-item-group">
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
                    <a href="{{ route('inventory.stock-adjustment') }}" class="sub-nav-item{{ request()->routeIs('inventory.stock-adjustment') ? ' active' : '' }}"><span>Stock Adjustment</span></a>
                    <a href="{{ route('inventory.waste-expiry') }}" class="sub-nav-item{{ request()->routeIs('inventory.waste-expiry') ? ' active' : '' }}"><span>Waste & Expiry</span></a>
                </div>
            </div>
            <div class="nav-section">
                @php $prodMgtActive = request()->routeIs('inventory.product-categories', 'inventory.recipe-management'); @endphp
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-pizza"></i>
                        <span>Product Management</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $prodMgtActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $prodMgtActive ? ' expanded' : '' }}">
                    <a href="{{ route('inventory.product-categories') }}" class="sub-nav-item{{ request()->routeIs('inventory.product-categories') ? ' active' : '' }}"><span>Product / Categories</span></a>
                    <a href="{{ route('inventory.recipe-management') }}" class="sub-nav-item{{ request()->routeIs('inventory.recipe-management') ? ' active' : '' }}"><span>Recipe / Menu Management</span></a>
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
                <div class="nav-item-group">
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
                <div class="nav-item-group">
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

        <!-- Business Configuration -->
        <div id="submenu-config" class="panel-content submenu{{ $activeModule === 'config' ? ' active' : '' }}">
            <div class="submenu-header">
                <span class="submenu-title">Business Configuration</span>
            </div>
            <div class="nav-section">
                <a href="{{ route('config.business-settings') }}" class="nav-item{{ request()->routeIs('config.business-settings') ? ' active' : '' }}">
                    <i class="ph ph-storefront"></i>
                    <span>Business Settings</span>
                </a>
                <a href="{{ route('config.account-settings') }}" class="nav-item{{ request()->routeIs('config.account-settings') ? ' active' : '' }}">
                    <i class="ph ph-user-gear"></i>
                    <span>Account Settings</span>
                </a>
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

        <div class="panel-footer">
            <div class="user-profile">
                <div class="user-avatar-icon">
                    <i class="ph ph-user"></i>
                </div>
                <div class="user-info">
                    <span class="user-name">{{ $userFullName }}</span>
                    <span class="user-role">{{ $userRole }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="sidebarLogoutForm" style="display:inline;">
                    @csrf
                    <button type="submit" class="logout-btn" title="Sign Out" aria-label="Sign Out" style="background:none;border:none;cursor:pointer;padding:0;">
                        <i class="ph ph-sign-out"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
