<?php
// Ensure BASE_URL is available
if (!defined('BASE_URL')) {
    define('BASE_URL', '/rms/');
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Determine active module section
$hrPages = ['hr-dashboard.php', 'employee.php', 'users-auth.php', 'attendance-schedule.php', 'attendance-checkin.php', 'employee-leave.php'];
$salesPages = ['sales-dashboard.php', 'daily-sales.php', 'payment-report.php', 'reconciliations.php', 'discount-config.php', 'voucher-config.php', 'bundle-promotions.php', 'customer-masterlist.php'];
$inventoryPages = ['inventory-dashboard.php', 'stocks-overview.php', 'beg-balance.php', 'stock-in.php', 'stock-out.php', 'stock-adjustment.php', 'waste-expiry.php', 'product-categories.php', 'recipe-management.php'];
$purchasePages = ['purchase-dashboard.php', 'request-quotations.php', 'purchase-orders.php', 'vendor-masterlist.php', 'vendor-bills.php'];
$configPages = ['business-settings.php', 'account-settings.php'];
$creditsPages = ['tickets.php', 'developers.php'];

$activeModule = '';
if (in_array($currentPage, $hrPages)) $activeModule = 'hr';
elseif (in_array($currentPage, $salesPages)) $activeModule = 'sales';
elseif (in_array($currentPage, $inventoryPages)) $activeModule = 'inventory';
elseif (in_array($currentPage, $purchasePages)) $activeModule = 'purchase';
elseif (in_array($currentPage, $configPages)) $activeModule = 'config';
elseif (in_array($currentPage, $creditsPages)) $activeModule = 'credits';
?>
<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay"></div>
<!-- Sidebar Container -->
<aside id="app-sidebar" class="sidebar<?= $currentPage === 'index.php' ? ' collapsed' : '' ?>">
    <script>
        (function() {
            try {
                var sb = document.getElementById('app-sidebar');
                <?php if ($currentPage === 'index.php'): ?>
                    sb.classList.add('collapsed');
                <?php else: ?>
                    var state = localStorage.getItem('rms_sidebar_collapsed');
                    if (state === 'true') {
                        sb.classList.add('collapsed');
                    } else if (state === 'false') {
                        sb.classList.remove('collapsed');
                    }
                <?php endif; ?>
            } catch(e) {}
        })();
    </script>
    <!-- Left Rail -->
    <div class="sidebar-rail">
        <div class="rail-header">
            <div class="brand-logo">
                <i class="ph ph-fork-knife"></i>
            </div>
        </div>
        
        <nav class="rail-nav">
            <a href="<?= BASE_URL ?>index.php" class="rail-item<?= $currentPage === 'index.php' ? ' active' : '' ?>" data-title="Home"><i class="ph ph-house"></i></a>
            <a href="#" class="rail-item<?= $activeModule === 'hr' ? ' active' : '' ?>" data-title="HR Operations" data-target="submenu-hr"><i class="ph ph-users-three"></i></a>
            <a href="#" class="rail-item<?= $activeModule === 'sales' ? ' active' : '' ?>" data-title="Sales Operations" data-target="submenu-sales"><i class="ph ph-chart-line-up"></i></a>
            <a href="#" class="rail-item<?= $activeModule === 'inventory' ? ' active' : '' ?>" data-title="Inventory Operations" data-target="submenu-inventory"><i class="ph ph-package"></i></a>
            <a href="#" class="rail-item<?= $activeModule === 'purchase' ? ' active' : '' ?>" data-title="Purchase Operations" data-target="submenu-purchase"><i class="ph ph-shopping-cart"></i></a>
            <a href="#" class="rail-item<?= $activeModule === 'config' ? ' active' : '' ?>" data-title="Business Config" data-target="submenu-config"><i class="ph ph-storefront"></i></a>
        </nav>
        
        <div class="rail-footer">
            <a href="#" class="rail-item<?= $activeModule === 'credits' ? ' active' : '' ?>" data-title="Credits" data-target="submenu-credits"><i class="ph ph-info"></i></a>
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
        <div id="submenu-hr" class="panel-content submenu<?= $activeModule === 'hr' ? ' active' : '' ?>">
            <div class="submenu-header">
                <span class="submenu-title">HR Operations</span>
            </div>
            <div class="nav-section">
                <a href="<?= BASE_URL ?>pages/hr/hr-dashboard.php" class="nav-item<?= $currentPage === 'hr-dashboard.php' ? ' active' : '' ?>">
                    <i class="ph ph-chart-bar"></i>
                    <span>HR Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-users"></i>
                        <span>Employee Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['employee.php', 'users-auth.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['employee.php', 'users-auth.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/hr/employee-management/employee.php" class="sub-nav-item<?= $currentPage === 'employee.php' ? ' active' : '' ?>"><span>Employee</span></a>
                    <a href="<?= BASE_URL ?>pages/hr/employee-management/users-auth.php" class="sub-nav-item<?= $currentPage === 'users-auth.php' ? ' active' : '' ?>"><span>Users & Authentication</span></a>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-wallet"></i>
                        <span>Payroll</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['attendance-schedule.php', 'attendance-checkin.php', 'employee-leave.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['attendance-schedule.php', 'attendance-checkin.php', 'employee-leave.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/hr/payroll/attendance-schedule.php" class="sub-nav-item<?= $currentPage === 'attendance-schedule.php' ? ' active' : '' ?>"><span>Attendance Schedule</span></a>
                    <a href="<?= BASE_URL ?>pages/hr/payroll/attendance-checkin.php" class="sub-nav-item<?= $currentPage === 'attendance-checkin.php' ? ' active' : '' ?>"><span>Attendance Check IN /OUT</span></a>
                    <a href="<?= BASE_URL ?>pages/hr/payroll/employee-leave.php" class="sub-nav-item<?= $currentPage === 'employee-leave.php' ? ' active' : '' ?>"><span>Employee Leave / Time Request</span></a>
                </div>
            </div>
        </div>

        <!-- Sales Operations -->
        <div id="submenu-sales" class="panel-content submenu<?= $activeModule === 'sales' ? ' active' : '' ?>">
            <div class="submenu-header">
                <span class="submenu-title">Sales Operations</span>
            </div>
            <div class="nav-section">
                <a href="<?= BASE_URL ?>pages/sales/sales-dashboard.php" class="nav-item<?= $currentPage === 'sales-dashboard.php' ? ' active' : '' ?>">
                    <i class="ph ph-chart-line-up"></i>
                    <span>Sales Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-currency-dollar"></i>
                        <span>Sales Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['daily-sales.php', 'payment-report.php', 'reconciliations.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['daily-sales.php', 'payment-report.php', 'reconciliations.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/sales/sales-management/daily-sales.php" class="sub-nav-item<?= $currentPage === 'daily-sales.php' ? ' active' : '' ?>"><span>Daily Sales Report</span></a>
                    <a href="<?= BASE_URL ?>pages/sales/sales-management/payment-report.php" class="sub-nav-item<?= $currentPage === 'payment-report.php' ? ' active' : '' ?>"><span>Payment Report</span></a>
                    <a href="<?= BASE_URL ?>pages/sales/sales-management/reconciliations.php" class="sub-nav-item<?= $currentPage === 'reconciliations.php' ? ' active' : '' ?>"><span>Reconciliations</span></a>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-tag"></i>
                        <span>Promotion Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['discount-config.php', 'voucher-config.php', 'bundle-promotions.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['discount-config.php', 'voucher-config.php', 'bundle-promotions.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/sales/promotion-management/discount-config.php" class="sub-nav-item<?= $currentPage === 'discount-config.php' ? ' active' : '' ?>"><span>Discount Configuration</span></a>
                    <a href="<?= BASE_URL ?>pages/sales/promotion-management/voucher-config.php" class="sub-nav-item<?= $currentPage === 'voucher-config.php' ? ' active' : '' ?>"><span>Voucher Configuration</span></a>
                    <a href="<?= BASE_URL ?>pages/sales/promotion-management/bundle-promotions.php" class="sub-nav-item<?= $currentPage === 'bundle-promotions.php' ? ' active' : '' ?>"><span>Bundle Promotions</span></a>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-address-book"></i>
                        <span>Customer Relation Mgt.</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= $currentPage === 'customer-masterlist.php' ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= $currentPage === 'customer-masterlist.php' ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/sales/customer-relation/customer-masterlist.php" class="sub-nav-item<?= $currentPage === 'customer-masterlist.php' ? ' active' : '' ?>"><span>Customer Masterlist</span></a>
                </div>
            </div>
        </div>

        <!-- Inventory Operations -->
        <div id="submenu-inventory" class="panel-content submenu<?= $activeModule === 'inventory' ? ' active' : '' ?>">
            <div class="submenu-header">
                <span class="submenu-title">Inventory Operations</span>
            </div>
            <div class="nav-section">
                <a href="<?= BASE_URL ?>pages/inventory/inventory-dashboard.php" class="nav-item<?= $currentPage === 'inventory-dashboard.php' ? ' active' : '' ?>">
                    <i class="ph ph-chart-bar"></i>
                    <span>Inventory Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-package"></i>
                        <span>Inventory Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['stocks-overview.php', 'beg-balance.php', 'stock-in.php', 'stock-out.php', 'stock-adjustment.php', 'waste-expiry.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['stocks-overview.php', 'beg-balance.php', 'stock-in.php', 'stock-out.php', 'stock-adjustment.php', 'waste-expiry.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/inventory/inventory-management/stocks-overview.php" class="sub-nav-item<?= $currentPage === 'stocks-overview.php' ? ' active' : '' ?>"><span>Stocks Overview</span></a>
                    <a href="<?= BASE_URL ?>pages/inventory/inventory-management/beg-balance.php" class="sub-nav-item<?= $currentPage === 'beg-balance.php' ? ' active' : '' ?>"><span>Beg Balance</span></a>
                    <a href="<?= BASE_URL ?>pages/inventory/inventory-management/stock-in.php" class="sub-nav-item<?= $currentPage === 'stock-in.php' ? ' active' : '' ?>"><span>Stock In / Receiving</span></a>
                    <a href="<?= BASE_URL ?>pages/inventory/inventory-management/stock-out.php" class="sub-nav-item<?= $currentPage === 'stock-out.php' ? ' active' : '' ?>"><span>Stock Out / Usage</span></a>
                    <a href="<?= BASE_URL ?>pages/inventory/inventory-management/stock-adjustment.php" class="sub-nav-item<?= $currentPage === 'stock-adjustment.php' ? ' active' : '' ?>"><span>Stock Adjustment</span></a>
                    <a href="<?= BASE_URL ?>pages/inventory/inventory-management/waste-expiry.php" class="sub-nav-item<?= $currentPage === 'waste-expiry.php' ? ' active' : '' ?>"><span>Waste & Expiry</span></a>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-pizza"></i>
                        <span>Product Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['product-categories.php', 'recipe-management.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['product-categories.php', 'recipe-management.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/inventory/product-management/product-categories.php" class="sub-nav-item<?= $currentPage === 'product-categories.php' ? ' active' : '' ?>"><span>Product / Categories</span></a>
                    <a href="<?= BASE_URL ?>pages/inventory/product-management/recipe-management.php" class="sub-nav-item<?= $currentPage === 'recipe-management.php' ? ' active' : '' ?>"><span>Recipe / Menu Management</span></a>
                </div>
            </div>
        </div>

        <!-- Purchase Operations -->
        <div id="submenu-purchase" class="panel-content submenu<?= $activeModule === 'purchase' ? ' active' : '' ?>">
            <div class="submenu-header">
                <span class="submenu-title">Purchase Operations</span>
            </div>
            <div class="nav-section">
                <a href="<?= BASE_URL ?>pages/purchase/purchase-dashboard.php" class="nav-item<?= $currentPage === 'purchase-dashboard.php' ? ' active' : '' ?>">
                    <i class="ph ph-chart-bar"></i>
                    <span>Purchase Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-shopping-cart"></i>
                        <span>Purchase Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['request-quotations.php', 'purchase-orders.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['request-quotations.php', 'purchase-orders.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/purchase/purchase-management/request-quotations.php" class="sub-nav-item<?= $currentPage === 'request-quotations.php' ? ' active' : '' ?>"><span>Request for Quotations</span></a>
                    <a href="<?= BASE_URL ?>pages/purchase/purchase-management/purchase-orders.php" class="sub-nav-item<?= $currentPage === 'purchase-orders.php' ? ' active' : '' ?>"><span>Purchase Orders</span></a>
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item-group">
                    <a href="#" class="nav-item">
                        <i class="ph ph-truck"></i>
                        <span>Vendor Management</span>
                    </a>
                    <button class="add-btn"><i class="ph <?= in_array($currentPage, ['vendor-masterlist.php', 'vendor-bills.php']) ? 'ph-minus' : 'ph-plus' ?>"></i></button>
                </div>
                <div class="sub-nav<?= in_array($currentPage, ['vendor-masterlist.php', 'vendor-bills.php']) ? ' expanded' : '' ?>">
                    <a href="<?= BASE_URL ?>pages/purchase/vendor-management/vendor-masterlist.php" class="sub-nav-item<?= $currentPage === 'vendor-masterlist.php' ? ' active' : '' ?>"><span>Vendor Masterlist</span></a>
                    <a href="<?= BASE_URL ?>pages/purchase/vendor-management/vendor-bills.php" class="sub-nav-item<?= $currentPage === 'vendor-bills.php' ? ' active' : '' ?>"><span>Vendor Bills</span></a>
                </div>
            </div>
        </div>

        <!-- Business Configuration -->
        <div id="submenu-config" class="panel-content submenu<?= $activeModule === 'config' ? ' active' : '' ?>">
            <div class="submenu-header">
                <span class="submenu-title">Business Configuration</span>
            </div>
            <div class="nav-section">
                <a href="<?= BASE_URL ?>pages/config/business-settings.php" class="nav-item<?= $currentPage === 'business-settings.php' ? ' active' : '' ?>">
                    <i class="ph ph-storefront"></i>
                    <span>Business Settings</span>
                </a>
                <a href="<?= BASE_URL ?>pages/config/account-settings.php" class="nav-item<?= $currentPage === 'account-settings.php' ? ' active' : '' ?>">
                    <i class="ph ph-user-gear"></i>
                    <span>Account Settings</span>
                </a>
            </div>
        </div>

        <!-- Credits -->
        <div id="submenu-credits" class="panel-content submenu<?= $activeModule === 'credits' ? ' active' : '' ?>">
            <div class="submenu-header">
                <span class="submenu-title">Credits</span>
            </div>
            <div class="nav-section">
                <a href="<?= BASE_URL ?>pages/credits/tickets.php" class="nav-item<?= $currentPage === 'tickets.php' ? ' active' : '' ?>">
                    <i class="ph ph-headset"></i>
                    <span>Tickets / Help Desk</span>
                </a>
                <a href="<?= BASE_URL ?>pages/credits/developers.php" class="nav-item<?= $currentPage === 'developers.php' ? ' active' : '' ?>">
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
                    <span class="user-name">Dorothy Diaz</span>
                    <span class="user-role">Manager</span>
                </div>
                <a href="#" class="logout-btn" title="Logout">
                    <i class="ph ph-sign-out"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
