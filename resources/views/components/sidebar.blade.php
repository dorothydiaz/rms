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
<aside id="app-sidebar" class="sidebar{{ ($isHome || $isAccountSettings) ? ' collapsed' : '' }}">
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
            <a href="{{ route('dashboard') }}" class="brand-logo" data-tooltip="RMS Dashboard">
                <i class="ph ph-fork-knife"></i>
            </a>
        </div>
        
        <nav class="rail-nav">
            <a href="{{ route('dashboard') }}" class="rail-item{{ $isHome ? ' active' : '' }}" data-tooltip="Home" data-title="Home"><i class="ph ph-house"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'hr' ? ' active' : '' }}" data-tooltip="HR Operations" data-title="HR Operations" data-target="submenu-hr"><i class="ph ph-users-three"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'sales' ? ' active' : '' }}" data-tooltip="Sales Operations" data-title="Sales Operations" data-target="submenu-sales"><i class="ph ph-chart-line-up"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'inventory' ? ' active' : '' }}" data-tooltip="Inventory Operations" data-title="Inventory Operations" data-target="submenu-inventory"><i class="ph ph-package"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'purchase' ? ' active' : '' }}" data-tooltip="Purchase Operations" data-title="Purchase Operations" data-target="submenu-purchase"><i class="ph ph-shopping-cart"></i></a>
        </nav>
        
        <div class="rail-footer">
            <a href="#" class="rail-item{{ $activeModule === 'credits' ? ' active' : '' }}" data-tooltip="Credits" data-title="Credits" data-target="submenu-credits"><i class="ph ph-info"></i></a>
            <a href="#" class="rail-item{{ $activeModule === 'config' ? ' active' : '' }}" data-tooltip="Settings" data-title="Settings" data-target="submenu-config"><i class="ph ph-gear"></i></a>
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

            <!-- 1. Dashboard -->
            <div class="nav-section">
                <a href="{{ route('hr.dashboard') }}" class="nav-item{{ request()->routeIs('hr.dashboard') ? ' active' : '' }}">
                    <i class="ph ph-chart-pie-slice"></i>
                    <span>HR Dashboard</span>
                </a>
            </div>

            <!-- 2. People -->
            <div class="nav-section">
                @php $peopleActive = request()->routeIs('hr.people.*', 'hr.employee'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.people.employees') }}" class="nav-item{{ $peopleActive ? ' active' : '' }}">
                        <i class="ph ph-users"></i>
                        <span>People</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $peopleActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $peopleActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.people.employees') }}" class="sub-nav-item{{ request()->routeIs('hr.people.employees*', 'hr.employee') ? ' active' : '' }}"><span>Employees</span></a>
                    <a href="{{ route('hr.people.departments') }}" class="sub-nav-item{{ request()->routeIs('hr.people.departments*') ? ' active' : '' }}"><span>Departments</span></a>
                    <a href="{{ route('hr.people.positions') }}" class="sub-nav-item{{ request()->routeIs('hr.people.positions*') ? ' active' : '' }}"><span>Positions</span></a>
                    <a href="{{ route('hr.people.branches') }}" class="sub-nav-item{{ request()->routeIs('hr.people.branches*') ? ' active' : '' }}"><span>Branches</span></a>
                    <a href="{{ route('hr.people.documents') }}" class="sub-nav-item{{ request()->routeIs('hr.people.documents*') ? ' active' : '' }}"><span>Documents</span></a>
                </div>
            </div>

            <!-- 3. Recruitment -->
            <div class="nav-section">
                @php $recActive = request()->routeIs('hr.recruitment.*'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.recruitment.vacancies') }}" class="nav-item{{ $recActive ? ' active' : '' }}">
                        <i class="ph ph-user-plus"></i>
                        <span>Recruitment</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $recActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $recActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.recruitment.vacancies') }}" class="sub-nav-item{{ request()->routeIs('hr.recruitment.vacancies*') ? ' active' : '' }}"><span>Job Vacancies</span></a>
                    <a href="{{ route('hr.recruitment.applicants') }}" class="sub-nav-item{{ request()->routeIs('hr.recruitment.applicants*') ? ' active' : '' }}"><span>Applicants</span></a>
                    <a href="{{ route('hr.recruitment.interviews') }}" class="sub-nav-item{{ request()->routeIs('hr.recruitment.interviews*') ? ' active' : '' }}"><span>Interviews</span></a>
                </div>
            </div>

            <!-- 4. Attendance -->
            <div class="nav-section">
                @php $attActive = request()->routeIs('hr.attendance.*', 'hr.attendance-schedule', 'hr.attendance-checkin'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.attendance.timekeeping') }}" class="nav-item{{ $attActive ? ' active' : '' }}">
                        <i class="ph ph-clock"></i>
                        <span>Attendance</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $attActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $attActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.attendance.timekeeping') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance.timekeeping*', 'hr.attendance-checkin') ? ' active' : '' }}"><span>Timekeeping</span></a>
                    <a href="{{ route('hr.attendance.dtr') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance.dtr*') ? ' active' : '' }}"><span>DTR</span></a>
                    <a href="{{ route('hr.attendance.schedules') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance.schedules*', 'hr.attendance-schedule') ? ' active' : '' }}"><span>Schedules</span></a>
                    <a href="{{ route('hr.attendance.overtime') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance.overtime*') ? ' active' : '' }}"><span>Overtime</span></a>
                    <a href="{{ route('hr.attendance.corrections') }}" class="sub-nav-item{{ request()->routeIs('hr.attendance.corrections*') ? ' active' : '' }}"><span>Attendance Corrections</span></a>
                </div>
            </div>

            <!-- 5. Leave & Absence -->
            <div class="nav-section">
                @php $leaveActive = request()->routeIs('hr.leave.*', 'hr.employee-leave'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.leave.requests') }}" class="nav-item{{ $leaveActive ? ' active' : '' }}">
                        <i class="ph ph-calendar-blank"></i>
                        <span>Leave & Absence</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $leaveActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $leaveActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.leave.requests') }}" class="sub-nav-item{{ request()->routeIs('hr.leave.requests*', 'hr.employee-leave') ? ' active' : '' }}"><span>Leave Requests</span></a>
                    <a href="{{ route('hr.leave.types') }}" class="sub-nav-item{{ request()->routeIs('hr.leave.types*') ? ' active' : '' }}"><span>Leave Types</span></a>
                    <a href="{{ route('hr.leave.credits') }}" class="sub-nav-item{{ request()->routeIs('hr.leave.credits*') ? ' active' : '' }}"><span>Leave Credits</span></a>
                    <a href="{{ route('hr.leave.reports') }}" class="sub-nav-item{{ request()->routeIs('hr.leave.reports*') ? ' active' : '' }}"><span>Leave Reports</span></a>
                </div>
            </div>

            <!-- 6. Payroll -->
            <div class="nav-section">
                @php $payrollActive = request()->routeIs('hr.payroll.*'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.payroll.register') }}" class="nav-item{{ $payrollActive ? ' active' : '' }}">
                        <i class="ph ph-wallet"></i>
                        <span>Payroll</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $payrollActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $payrollActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.payroll.periods') }}" class="sub-nav-item{{ request()->routeIs('hr.payroll.periods*') ? ' active' : '' }}"><span>Payroll Periods</span></a>
                    <a href="{{ route('hr.payroll.process') }}" class="sub-nav-item{{ request()->routeIs('hr.payroll.process*') ? ' active' : '' }}"><span>Process Payroll</span></a>
                    <a href="{{ route('hr.payroll.register') }}" class="sub-nav-item{{ request()->routeIs('hr.payroll.register*') ? ' active' : '' }}"><span>Payroll Register</span></a>
                    <a href="{{ route('hr.payroll.payslips') }}" class="sub-nav-item{{ request()->routeIs('hr.payroll.payslips*') ? ' active' : '' }}"><span>Payslips</span></a>
                    <a href="{{ route('hr.payroll.statutory-rules') }}" class="sub-nav-item{{ request()->routeIs('hr.payroll.statutory-rules*') ? ' active' : '' }}"><span>Statutory Rules</span></a>
                </div>
            </div>

            <!-- 7. Performance -->
            <div class="nav-section">
                @php $perfActive = request()->routeIs('hr.performance.*'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.performance.evaluations') }}" class="nav-item{{ $perfActive ? ' active' : '' }}">
                        <i class="ph ph-star"></i>
                        <span>Performance</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $perfActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $perfActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.performance.periods') }}" class="sub-nav-item{{ request()->routeIs('hr.performance.periods*') ? ' active' : '' }}"><span>Evaluation Periods</span></a>
                    <a href="{{ route('hr.performance.criteria') }}" class="sub-nav-item{{ request()->routeIs('hr.performance.criteria*') ? ' active' : '' }}"><span>Criteria</span></a>
                    <a href="{{ route('hr.performance.evaluations') }}" class="sub-nav-item{{ request()->routeIs('hr.performance.evaluations*') ? ' active' : '' }}"><span>Evaluations</span></a>
                    <a href="{{ route('hr.performance.reports') }}" class="sub-nav-item{{ request()->routeIs('hr.performance.reports*') ? ' active' : '' }}"><span>Performance Reports</span></a>
                </div>
            </div>

            <!-- 8. Training -->
            <div class="nav-section">
                @php $trainingActive = request()->routeIs('hr.training.*'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.training.programs') }}" class="nav-item{{ $trainingActive ? ' active' : '' }}">
                        <i class="ph ph-graduation-cap"></i>
                        <span>Training</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $trainingActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $trainingActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.training.programs') }}" class="sub-nav-item{{ request()->routeIs('hr.training.programs*') ? ' active' : '' }}"><span>Training Programs</span></a>
                    <a href="{{ route('hr.training.records') }}" class="sub-nav-item{{ request()->routeIs('hr.training.records*') ? ' active' : '' }}"><span>Training Records</span></a>
                    <a href="{{ route('hr.training.reports') }}" class="sub-nav-item{{ request()->routeIs('hr.training.reports*') ? ' active' : '' }}"><span>Training Reports</span></a>
                </div>
            </div>

            <!-- 9. Reports -->
            <div class="nav-section">
                <a href="{{ route('hr.reports.index') }}" class="nav-item{{ request()->routeIs('hr.reports.*') ? ' active' : '' }}">
                    <i class="ph ph-file-text"></i>
                    <span>Reports</span>
                </a>
            </div>

            <!-- 10. Administration -->
            <div class="nav-section">
                @php $adminActive = request()->routeIs('hr.admin.*', 'hr.users-auth'); @endphp
                <div class="nav-item-group">
                    <a href="{{ route('hr.admin.users') }}" class="nav-item{{ $adminActive ? ' active' : '' }}">
                        <i class="ph ph-shield-check"></i>
                        <span>Administration</span>
                    </a>
                    <button class="add-btn"><i class="ph {{ $adminActive ? 'ph-minus' : 'ph-plus' }}"></i></button>
                </div>
                <div class="sub-nav{{ $adminActive ? ' expanded' : '' }}">
                    <a href="{{ route('hr.admin.users') }}" class="sub-nav-item{{ request()->routeIs('hr.admin.users*', 'hr.users-auth') ? ' active' : '' }}"><span>Users</span></a>
                    <a href="{{ route('hr.admin.roles') }}" class="sub-nav-item{{ request()->routeIs('hr.admin.roles*') ? ' active' : '' }}"><span>Roles & Permissions</span></a>
                    <a href="{{ route('hr.admin.settings') }}" class="sub-nav-item{{ request()->routeIs('hr.admin.settings*') ? ' active' : '' }}"><span>System Settings</span></a>
                    <a href="{{ route('hr.admin.audit-logs') }}" class="sub-nav-item{{ request()->routeIs('hr.admin.audit-logs*') ? ' active' : '' }}"><span>Audit Logs</span></a>
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
</aside>
