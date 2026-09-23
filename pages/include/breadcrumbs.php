<?php
// Centralized Breadcrumb Navigation Component
if (!defined('BASE_URL')) {
    define('BASE_URL', '/rms/');
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Comprehensive Module, Section, and Page Registry
$moduleRegistry = [
    'hr' => [
        'title' => 'HR Operations',
        'icon' => 'ph-users-three',
        'dashboard_url' => BASE_URL . 'pages/hr/hr-dashboard.php',
        'sections' => [
            'overview' => [
                'title' => 'HR Overview',
                'url' => BASE_URL . 'pages/hr/hr-dashboard.php',
                'items' => [
                    ['title' => 'HR Dashboard', 'url' => BASE_URL . 'pages/hr/hr-dashboard.php', 'icon' => 'ph-chart-bar', 'file' => 'hr-dashboard.php']
                ]
            ],
            'employee' => [
                'title' => 'Employee Management',
                'url' => BASE_URL . 'pages/hr/employee-management/employee.php',
                'icon' => 'ph-users',
                'items' => [
                    ['title' => 'Employee Masterlist', 'url' => BASE_URL . 'pages/hr/employee-management/employee.php', 'icon' => 'ph-user-list', 'file' => 'employee.php'],
                    ['title' => 'Users & Authentication', 'url' => BASE_URL . 'pages/hr/employee-management/users-auth.php', 'icon' => 'ph-lock-key', 'file' => 'users-auth.php']
                ]
            ],
            'payroll' => [
                'title' => 'Payroll',
                'url' => BASE_URL . 'pages/hr/payroll/attendance-schedule.php',
                'icon' => 'ph-wallet',
                'items' => [
                    ['title' => 'Attendance Schedule', 'url' => BASE_URL . 'pages/hr/payroll/attendance-schedule.php', 'icon' => 'ph-calendar-check', 'file' => 'attendance-schedule.php'],
                    ['title' => 'Attendance Check IN / OUT', 'url' => BASE_URL . 'pages/hr/payroll/attendance-checkin.php', 'icon' => 'ph-clock-user', 'file' => 'attendance-checkin.php'],
                    ['title' => 'Employee Leave / Time Request', 'url' => BASE_URL . 'pages/hr/payroll/employee-leave.php', 'icon' => 'ph-calendar-x', 'file' => 'employee-leave.php']
                ]
            ]
        ]
    ],
    'sales' => [
        'title' => 'Sales Operations',
        'icon' => 'ph-chart-line-up',
        'dashboard_url' => BASE_URL . 'pages/sales/sales-dashboard.php',
        'sections' => [
            'overview' => [
                'title' => 'Sales Overview',
                'url' => BASE_URL . 'pages/sales/sales-dashboard.php',
                'items' => [
                    ['title' => 'Sales Dashboard', 'url' => BASE_URL . 'pages/sales/sales-dashboard.php', 'icon' => 'ph-chart-line-up', 'file' => 'sales-dashboard.php']
                ]
            ],
            'sales_mgt' => [
                'title' => 'Sales Management',
                'url' => BASE_URL . 'pages/sales/sales-management/daily-sales.php',
                'icon' => 'ph-currency-dollar',
                'items' => [
                    ['title' => 'Daily Sales Report', 'url' => BASE_URL . 'pages/sales/sales-management/daily-sales.php', 'icon' => 'ph-receipt', 'file' => 'daily-sales.php'],
                    ['title' => 'Payment Report', 'url' => BASE_URL . 'pages/sales/sales-management/payment-report.php', 'icon' => 'ph-credit-card', 'file' => 'payment-report.php'],
                    ['title' => 'Reconciliations', 'url' => BASE_URL . 'pages/sales/sales-management/reconciliations.php', 'icon' => 'ph-arrows-left-right', 'file' => 'reconciliations.php']
                ]
            ],
            'promo_mgt' => [
                'title' => 'Promotion Management',
                'url' => BASE_URL . 'pages/sales/promotion-management/discount-config.php',
                'icon' => 'ph-tag',
                'items' => [
                    ['title' => 'Discount Configuration', 'url' => BASE_URL . 'pages/sales/promotion-management/discount-config.php', 'icon' => 'ph-percent', 'file' => 'discount-config.php'],
                    ['title' => 'Voucher Configuration', 'url' => BASE_URL . 'pages/sales/promotion-management/voucher-config.php', 'icon' => 'ph-ticket', 'file' => 'voucher-config.php'],
                    ['title' => 'Bundle Promotions', 'url' => BASE_URL . 'pages/sales/promotion-management/bundle-promotions.php', 'icon' => 'ph-gift', 'file' => 'bundle-promotions.php']
                ]
            ],
            'crm' => [
                'title' => 'Customer Relation Mgt.',
                'url' => BASE_URL . 'pages/sales/customer-relation/customer-masterlist.php',
                'icon' => 'ph-address-book',
                'items' => [
                    ['title' => 'Customer Masterlist', 'url' => BASE_URL . 'pages/sales/customer-relation/customer-masterlist.php', 'icon' => 'ph-user-circle-gear', 'file' => 'customer-masterlist.php']
                ]
            ]
        ]
    ],
    'inventory' => [
        'title' => 'Inventory Operations',
        'icon' => 'ph-package',
        'dashboard_url' => BASE_URL . 'pages/inventory/inventory-dashboard.php',
        'sections' => [
            'overview' => [
                'title' => 'Inventory Overview',
                'url' => BASE_URL . 'pages/inventory/inventory-dashboard.php',
                'items' => [
                    ['title' => 'Inventory Dashboard', 'url' => BASE_URL . 'pages/inventory/inventory-dashboard.php', 'icon' => 'ph-chart-bar', 'file' => 'inventory-dashboard.php']
                ]
            ],
            'inventory_mgt' => [
                'title' => 'Inventory Management',
                'url' => BASE_URL . 'pages/inventory/inventory-management/stocks-overview.php',
                'icon' => 'ph-package',
                'items' => [
                    ['title' => 'Stocks Overview', 'url' => BASE_URL . 'pages/inventory/inventory-management/stocks-overview.php', 'icon' => 'ph-squares-four', 'file' => 'stocks-overview.php'],
                    ['title' => 'Beginning Balance', 'url' => BASE_URL . 'pages/inventory/inventory-management/beg-balance.php', 'icon' => 'ph-scales', 'file' => 'beg-balance.php'],
                    ['title' => 'Stock In / Receiving', 'url' => BASE_URL . 'pages/inventory/inventory-management/stock-in.php', 'icon' => 'ph-tray-arrow-down', 'file' => 'stock-in.php'],
                    ['title' => 'Stock Out / Usage', 'url' => BASE_URL . 'pages/inventory/inventory-management/stock-out.php', 'icon' => 'ph-tray-arrow-up', 'file' => 'stock-out.php'],
                    ['title' => 'Stock Adjustment', 'url' => BASE_URL . 'pages/inventory/inventory-management/stock-adjustment.php', 'icon' => 'ph-sliders-horizontal', 'file' => 'stock-adjustment.php'],
                    ['title' => 'Waste & Expiry', 'url' => BASE_URL . 'pages/inventory/inventory-management/waste-expiry.php', 'icon' => 'ph-trash', 'file' => 'waste-expiry.php']
                ]
            ],
            'prod_mgt' => [
                'title' => 'Product Management',
                'url' => BASE_URL . 'pages/inventory/product-management/product-categories.php',
                'icon' => 'ph-pizza',
                'items' => [
                    ['title' => 'Product / Categories', 'url' => BASE_URL . 'pages/inventory/product-management/product-categories.php', 'icon' => 'ph-folder-simple', 'file' => 'product-categories.php'],
                    ['title' => 'Recipe / Menu Management', 'url' => BASE_URL . 'pages/inventory/product-management/recipe-management.php', 'icon' => 'ph-cooking-pot', 'file' => 'recipe-management.php']
                ]
            ]
        ]
    ],
    'purchase' => [
        'title' => 'Purchase Operations',
        'icon' => 'ph-shopping-cart',
        'dashboard_url' => BASE_URL . 'pages/purchase/purchase-dashboard.php',
        'sections' => [
            'overview' => [
                'title' => 'Purchase Overview',
                'url' => BASE_URL . 'pages/purchase/purchase-dashboard.php',
                'items' => [
                    ['title' => 'Purchase Dashboard', 'url' => BASE_URL . 'pages/purchase/purchase-dashboard.php', 'icon' => 'ph-chart-bar', 'file' => 'purchase-dashboard.php']
                ]
            ],
            'purchase_mgt' => [
                'title' => 'Purchase Management',
                'url' => BASE_URL . 'pages/purchase/purchase-management/request-quotations.php',
                'icon' => 'ph-shopping-cart',
                'items' => [
                    ['title' => 'Request for Quotations', 'url' => BASE_URL . 'pages/purchase/purchase-management/request-quotations.php', 'icon' => 'ph-file-text', 'file' => 'request-quotations.php'],
                    ['title' => 'Purchase Orders', 'url' => BASE_URL . 'pages/purchase/purchase-management/purchase-orders.php', 'icon' => 'ph-check-square', 'file' => 'purchase-orders.php']
                ]
            ],
            'vendor_mgt' => [
                'title' => 'Vendor Management',
                'url' => BASE_URL . 'pages/purchase/vendor-management/vendor-masterlist.php',
                'icon' => 'ph-truck',
                'items' => [
                    ['title' => 'Vendor Masterlist', 'url' => BASE_URL . 'pages/purchase/vendor-management/vendor-masterlist.php', 'icon' => 'ph-storefront', 'file' => 'vendor-masterlist.php'],
                    ['title' => 'Vendor Bills', 'url' => BASE_URL . 'pages/purchase/vendor-management/vendor-bills.php', 'icon' => 'ph-receipt', 'file' => 'vendor-bills.php']
                ]
            ]
        ]
    ],
    'config' => [
        'title' => 'Business Configuration',
        'icon' => 'ph-storefront',
        'dashboard_url' => BASE_URL . 'pages/config/business-settings.php',
        'sections' => [
            'config_items' => [
                'title' => 'Configuration',
                'url' => BASE_URL . 'pages/config/business-settings.php',
                'icon' => 'ph-gear',
                'items' => [
                    ['title' => 'Business Settings', 'url' => BASE_URL . 'pages/config/business-settings.php', 'icon' => 'ph-storefront', 'file' => 'business-settings.php'],
                    ['title' => 'Account Settings', 'url' => BASE_URL . 'pages/config/account-settings.php', 'icon' => 'ph-user-gear', 'file' => 'account-settings.php']
                ]
            ]
        ]
    ],
    'credits' => [
        'title' => 'Credits & Support',
        'icon' => 'ph-info',
        'dashboard_url' => BASE_URL . 'pages/credits/tickets.php',
        'sections' => [
            'credits_items' => [
                'title' => 'Support',
                'url' => BASE_URL . 'pages/credits/tickets.php',
                'icon' => 'ph-lifebuoy',
                'items' => [
                    ['title' => 'Tickets / Help Desk', 'url' => BASE_URL . 'pages/credits/tickets.php', 'icon' => 'ph-headset', 'file' => 'tickets.php'],
                    ['title' => 'Developers', 'url' => BASE_URL . 'pages/credits/developers.php', 'icon' => 'ph-code', 'file' => 'developers.php']
                ]
            ]
        ]
    ]
];

// Lookup active hierarchy
$currentModuleKey = null;
$currentModule = null;
$currentSectionKey = null;
$currentSection = null;
$currentPageItem = null;

if ($currentPage === 'index.php') {
    $currentPageItem = [
        'title' => 'Analytics Dashboard',
        'url' => BASE_URL . 'index.php',
        'icon' => 'ph-gauge',
        'file' => 'index.php'
    ];
} else {
    foreach ($moduleRegistry as $mKey => $mVal) {
        foreach ($mVal['sections'] as $sKey => $sVal) {
            foreach ($sVal['items'] as $item) {
                if ($item['file'] === $currentPage) {
                    $currentModuleKey = $mKey;
                    $currentModule = $mVal;
                    $currentSectionKey = $sKey;
                    $currentSection = $sVal;
                    $currentPageItem = $item;
                    break 3;
                }
            }
        }
    }
}

// Fallback if not found in registry
if (!$currentPageItem) {
    $currentPageItem = [
        'title' => ucwords(str_replace(['-', '.php'], [' ', ''], $currentPage)),
        'url' => '#',
        'icon' => 'ph-file',
        'file' => $currentPage
    ];
}
?>

<?php
$isInitiallyCollapsed = ($currentPage === 'index.php');
?>
<button type="button" class="sidebar-toggle-btn header-sidebar-toggle" data-tooltip="<?= $isInitiallyCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar' ?>" aria-label="Toggle Sidebar">
    <i class="ph <?= $isInitiallyCollapsed ? 'ph-list' : 'ph-caret-left' ?>"></i>
</button>
<script>
    (function() {
        try {
            var state = localStorage.getItem('rms_sidebar_collapsed');
            var isCol = (state === 'true' || (state === null && <?= $currentPage === 'index.php' ? 'true' : 'false' ?>));
            if (state === 'false') isCol = false;
            var btn = document.querySelector('.header-sidebar-toggle');
            if (btn) {
                var icon = btn.querySelector('i');
                if (icon) icon.className = isCol ? 'ph ph-list' : 'ph ph-caret-left';
                btn.setAttribute('data-tooltip', isCol ? 'Expand Sidebar' : 'Collapse Sidebar');
            }
        } catch(e) {}
    })();
</script>

<div class="breadcrumb-header-block">
    <h1 class="page-header-title"><?= htmlspecialchars($currentPageItem['title']) ?></h1>
    
    <?php if ($currentModule): ?>
    <nav class="breadcrumb-nav" aria-label="Breadcrumb navigation">
        <ol class="breadcrumb-list">
            <!-- Module Crumb -->
            <li class="breadcrumb-item breadcrumb-dropdown-container">
                <button type="button" class="breadcrumb-btn" aria-haspopup="true" aria-expanded="false" title="<?= htmlspecialchars($currentModule['title']) ?>">
                    <span class="breadcrumb-text"><?= htmlspecialchars($currentModule['title']) ?></span>
                    <i class="ph ph-caret-down dropdown-arrow"></i>
                </button>
                <div class="breadcrumb-dropdown-menu">
                    <div class="dropdown-header"><?= htmlspecialchars($currentModule['title']) ?> Pages</div>
                    <?php foreach ($currentModule['sections'] as $sKey => $sInfo): ?>
                        <div class="dropdown-section-title"><?= htmlspecialchars($sInfo['title']) ?></div>
                        <?php foreach ($sInfo['items'] as $subItem): ?>
                            <a href="<?= $subItem['url'] ?>" class="dropdown-item<?= $currentPage === $subItem['file'] ? ' active' : '' ?>">
                                <i class="ph <?= !empty($subItem['icon']) ? $subItem['icon'] : 'ph-circle' ?>"></i>
                                <span class="dropdown-item-text"><?= htmlspecialchars($subItem['title']) ?></span>
                                <?php if ($currentPage === $subItem['file']): ?><span class="dropdown-badge">Current</span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </li>

            <?php if ($currentSection && $currentSectionKey !== 'overview'): ?>
                <li class="breadcrumb-separator" aria-hidden="true">
                    <i class="ph ph-caret-right"></i>
                </li>

                <!-- Section Crumb -->
                <li class="breadcrumb-item breadcrumb-dropdown-container">
                    <button type="button" class="breadcrumb-btn" aria-haspopup="true" aria-expanded="false" title="<?= htmlspecialchars($currentSection['title']) ?>">
                        <span class="breadcrumb-text"><?= htmlspecialchars($currentSection['title']) ?></span>
                        <i class="ph ph-caret-down dropdown-arrow"></i>
                    </button>
                    <div class="breadcrumb-dropdown-menu">
                        <div class="dropdown-header">Pages in <?= htmlspecialchars($currentSection['title']) ?></div>
                        <?php foreach ($currentSection['items'] as $sItem): ?>
                            <a href="<?= $sItem['url'] ?>" class="dropdown-item<?= $currentPage === $sItem['file'] ? ' active' : '' ?>">
                                <i class="ph <?= !empty($sItem['icon']) ? $sItem['icon'] : 'ph-file-text' ?>"></i>
                                <span class="dropdown-item-text"><?= htmlspecialchars($sItem['title']) ?></span>
                                <?php if ($currentPage === $sItem['file']): ?>
                                    <span class="dropdown-badge">Current</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>

                        <?php if (count($currentModule['sections']) > 1): ?>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Other <?= htmlspecialchars($currentModule['title']) ?> Sections</div>
                            <?php foreach ($currentModule['sections'] as $otherKey => $otherSec): ?>
                                <?php if ($otherKey !== $currentSectionKey): ?>
                                    <a href="<?= $otherSec['url'] ?>" class="dropdown-item secondary">
                                        <i class="ph <?= !empty($otherSec['icon']) ? $otherSec['icon'] : 'ph-folder' ?>"></i>
                                        <span class="dropdown-item-text"><?= htmlspecialchars($otherSec['title']) ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>
        </ol>
    </nav>
    <?php endif; ?>
</div>
