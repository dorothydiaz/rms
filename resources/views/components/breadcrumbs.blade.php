@php
    $currentRouteName = request()->route() ? request()->route()->getName() : '';
    $isHome = ($currentRouteName === 'dashboard');

    $moduleRegistry = [
        'hr' => [
            'title' => 'HR Operations',
            'icon' => 'ph-users-three',
            'dashboard_url' => route('hr.dashboard'),
            'sections' => [
                'overview' => [
                    'title' => 'HR Overview',
                    'url' => route('hr.dashboard'),
                    'items' => [
                        ['title' => 'HR Dashboard', 'url' => route('hr.dashboard'), 'icon' => 'ph-chart-bar', 'route' => 'hr.dashboard']
                    ]
                ],
                'employee' => [
                    'title' => 'Employee Management',
                    'url' => route('hr.employee'),
                    'icon' => 'ph-users',
                    'items' => [
                        ['title' => 'Employee Masterlist', 'url' => route('hr.employee'), 'icon' => 'ph-user-list', 'route' => 'hr.employee'],
                        ['title' => 'Users & Authentication', 'url' => route('hr.users-auth'), 'icon' => 'ph-lock-key', 'route' => 'hr.users-auth']
                    ]
                ],
                'payroll' => [
                    'title' => 'Payroll',
                    'url' => route('hr.attendance-schedule'),
                    'icon' => 'ph-wallet',
                    'items' => [
                        ['title' => 'Attendance Schedule', 'url' => route('hr.attendance-schedule'), 'icon' => 'ph-calendar-check', 'route' => 'hr.attendance-schedule'],
                        ['title' => 'Attendance Check IN / OUT', 'url' => route('hr.attendance-checkin'), 'icon' => 'ph-clock-user', 'route' => 'hr.attendance-checkin'],
                        ['title' => 'Employee Leave / Time Request', 'url' => route('hr.employee-leave'), 'icon' => 'ph-calendar-x', 'route' => 'hr.employee-leave']
                    ]
                ]
            ]
        ],
        'sales' => [
            'title' => 'Sales Operations',
            'icon' => 'ph-chart-line-up',
            'dashboard_url' => route('sales.dashboard'),
            'sections' => [
                'overview' => [
                    'title' => 'Sales Overview',
                    'url' => route('sales.dashboard'),
                    'items' => [
                        ['title' => 'Sales Dashboard', 'url' => route('sales.dashboard'), 'icon' => 'ph-chart-line-up', 'route' => 'sales.dashboard']
                    ]
                ],
                'sales_mgt' => [
                    'title' => 'Sales Management',
                    'url' => route('sales.daily-sales'),
                    'icon' => 'ph-currency-dollar',
                    'items' => [
                        ['title' => 'Daily Sales Report', 'url' => route('sales.daily-sales'), 'icon' => 'ph-receipt', 'route' => 'sales.daily-sales'],
                        ['title' => 'Payment Report', 'url' => route('sales.payment-report'), 'icon' => 'ph-credit-card', 'route' => 'sales.payment-report'],
                        ['title' => 'Reconciliations', 'url' => route('sales.reconciliations'), 'icon' => 'ph-arrows-left-right', 'route' => 'sales.reconciliations']
                    ]
                ],
                'promo_mgt' => [
                    'title' => 'Promotion Management',
                    'url' => route('sales.discount-config'),
                    'icon' => 'ph-tag',
                    'items' => [
                        ['title' => 'Discount Configuration', 'url' => route('sales.discount-config'), 'icon' => 'ph-percent', 'route' => 'sales.discount-config'],
                        ['title' => 'Voucher Configuration', 'url' => route('sales.voucher-config'), 'icon' => 'ph-ticket', 'route' => 'sales.voucher-config'],
                        ['title' => 'Bundle Promotions', 'url' => route('sales.bundle-promotions'), 'icon' => 'ph-gift', 'route' => 'sales.bundle-promotions']
                    ]
                ],
                'crm' => [
                    'title' => 'Customer Relation Mgt.',
                    'url' => route('sales.customer-masterlist'),
                    'icon' => 'ph-address-book',
                    'items' => [
                        ['title' => 'Customer Masterlist', 'url' => route('sales.customer-masterlist'), 'icon' => 'ph-user-circle-gear', 'route' => 'sales.customer-masterlist']
                    ]
                ]
            ]
        ],
        'inventory' => [
            'title' => 'Inventory Operations',
            'icon' => 'ph-package',
            'dashboard_url' => route('inventory.dashboard'),
            'sections' => [
                'overview' => [
                    'title' => 'Inventory Overview',
                    'url' => route('inventory.dashboard'),
                    'items' => [
                        ['title' => 'Inventory Dashboard', 'url' => route('inventory.dashboard'), 'icon' => 'ph-chart-bar', 'route' => 'inventory.dashboard']
                    ]
                ],
                'inventory_mgt' => [
                    'title' => 'Inventory Management',
                    'url' => route('inventory.stocks-overview'),
                    'icon' => 'ph-package',
                    'items' => [
                        ['title' => 'Stocks Overview', 'url' => route('inventory.stocks-overview'), 'icon' => 'ph-squares-four', 'route' => 'inventory.stocks-overview'],
                        ['title' => 'Beg Balance', 'url' => route('inventory.beg-balance'), 'icon' => 'ph-scales', 'route' => 'inventory.beg-balance'],
                        ['title' => 'Stock In / Receiving', 'url' => route('inventory.stock-in'), 'icon' => 'ph-arrow-down-left', 'route' => 'inventory.stock-in'],
                        ['title' => 'Stock Out / Usage', 'url' => route('inventory.stock-out'), 'icon' => 'ph-arrow-up-right', 'route' => 'inventory.stock-out'],
                        ['title' => 'Stock Adjustment', 'url' => route('inventory.stock-adjustment'), 'icon' => 'ph-sliders-horizontal', 'route' => 'inventory.stock-adjustment'],
                        ['title' => 'Waste & Expiry', 'url' => route('inventory.waste-expiry'), 'icon' => 'ph-trash-simple', 'route' => 'inventory.waste-expiry']
                    ]
                ],
                'product_mgt' => [
                    'title' => 'Product Management',
                    'url' => route('inventory.product-categories'),
                    'icon' => 'ph-pizza',
                    'items' => [
                        ['title' => 'Product / Categories', 'url' => route('inventory.product-categories'), 'icon' => 'ph-folder-simple', 'route' => 'inventory.product-categories'],
                        ['title' => 'Recipe / Menu Management', 'url' => route('inventory.recipe-management'), 'icon' => 'ph-book-open', 'route' => 'inventory.recipe-management']
                    ]
                ]
            ]
        ],
        'purchase' => [
            'title' => 'Purchase Operations',
            'icon' => 'ph-shopping-cart',
            'dashboard_url' => route('purchase.dashboard'),
            'sections' => [
                'overview' => [
                    'title' => 'Purchase Overview',
                    'url' => route('purchase.dashboard'),
                    'items' => [
                        ['title' => 'Purchase Dashboard', 'url' => route('purchase.dashboard'), 'icon' => 'ph-chart-bar', 'route' => 'purchase.dashboard']
                    ]
                ],
                'purchase_mgt' => [
                    'title' => 'Purchase Management',
                    'url' => route('purchase.request-quotations'),
                    'icon' => 'ph-shopping-cart',
                    'items' => [
                        ['title' => 'Request for Quotations', 'url' => route('purchase.request-quotations'), 'icon' => 'ph-file-text', 'route' => 'purchase.request-quotations'],
                        ['title' => 'Purchase Orders', 'url' => route('purchase.purchase-orders'), 'icon' => 'ph-clipboard-text', 'route' => 'purchase.purchase-orders']
                    ]
                ],
                'vendor_mgt' => [
                    'title' => 'Vendor Management',
                    'url' => route('purchase.vendor-masterlist'),
                    'icon' => 'ph-truck',
                    'items' => [
                        ['title' => 'Vendor Masterlist', 'url' => route('purchase.vendor-masterlist'), 'icon' => 'ph-storefront', 'route' => 'purchase.vendor-masterlist'],
                        ['title' => 'Vendor Bills', 'url' => route('purchase.vendor-bills'), 'icon' => 'ph-receipt', 'route' => 'purchase.vendor-bills']
                    ]
                ]
            ]
        ],
        'config' => [
            'title' => 'Business Configuration',
            'icon' => 'ph-storefront',
            'dashboard_url' => route('config.business-settings'),
            'sections' => [
                'settings' => [
                    'title' => 'Configuration',
                    'url' => route('config.business-settings'),
                    'items' => [
                        ['title' => 'Business Settings', 'url' => route('config.business-settings'), 'icon' => 'ph-storefront', 'route' => 'config.business-settings'],
                        ['title' => 'Account Settings', 'url' => route('config.account-settings'), 'icon' => 'ph-user-gear', 'route' => 'config.account-settings']
                    ]
                ]
            ]
        ],
        'credits' => [
            'title' => 'Credits',
            'icon' => 'ph-info',
            'dashboard_url' => route('credits.tickets'),
            'sections' => [
                'help' => [
                    'title' => 'Support & Credits',
                    'url' => route('credits.tickets'),
                    'items' => [
                        ['title' => 'Tickets / Help Desk', 'url' => route('credits.tickets'), 'icon' => 'ph-headset', 'route' => 'credits.tickets'],
                        ['title' => 'Developers', 'url' => route('credits.developers'), 'icon' => 'ph-code', 'route' => 'credits.developers']
                    ]
                ]
            ]
        ]
    ];

    $currentModule = null;
    $currentSection = null;
    $currentSectionKey = null;
    $currentPageItem = null;

    if ($isHome) {
        $currentPageItem = [
            'title' => 'Dashboard',
            'url' => route('dashboard'),
            'icon' => 'ph-house',
            'route' => 'dashboard'
        ];
    } else {
        foreach ($moduleRegistry as $mKey => $mVal) {
            foreach ($mVal['sections'] as $sKey => $sVal) {
                foreach ($sVal['items'] as $item) {
                    if ($item['route'] === $currentRouteName) {
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

    if (!$currentPageItem) {
        $currentPageItem = [
            'title' => ucwords(str_replace(['.', '-'], [' ', ' '], $currentRouteName)),
            'url' => '#',
            'icon' => 'ph-file',
            'route' => $currentRouteName
        ];
    }
@endphp

<button type="button" class="sidebar-toggle-btn header-sidebar-toggle" data-tooltip="{{ $isHome ? 'Expand Sidebar' : 'Collapse Sidebar' }}" aria-label="Toggle Sidebar">
    <i class="ph {{ $isHome ? 'ph-list' : 'ph-caret-left' }}"></i>
</button>
<script>
    (function() {
        try {
            var isHome = {{ $isHome ? 'true' : 'false' }};
            var state = localStorage.getItem('rms_sidebar_collapsed');
            var isCol = isHome || (state === 'true');
            if (!isHome && state === 'false') isCol = false;
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
    <h1 class="page-header-title">{{ $currentPageItem['title'] }}</h1>
    
    @if ($currentModule)
    <nav class="breadcrumb-nav" aria-label="Breadcrumb navigation">
        <ol class="breadcrumb-list">
            <!-- Module Crumb -->
            <li class="breadcrumb-item breadcrumb-dropdown-container">
                <button type="button" class="breadcrumb-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="breadcrumb-text">{{ $currentModule['title'] }}</span>
                </button>
                <div class="breadcrumb-dropdown-menu">
                    <div class="dropdown-header">{{ $currentModule['title'] }} Pages</div>
                    @foreach ($currentModule['sections'] as $sKey => $sInfo)
                        <div class="dropdown-section-title">{{ $sInfo['title'] }}</div>
                        @foreach ($sInfo['items'] as $subItem)
                            <a href="{{ $subItem['url'] }}" class="dropdown-item{{ $currentRouteName === $subItem['route'] ? ' active' : '' }}">
                                <i class="ph {{ $subItem['icon'] ?? 'ph-circle' }}"></i>
                                <span class="dropdown-item-text">{{ $subItem['title'] }}</span>
                                @if ($currentRouteName === $subItem['route'])<span class="dropdown-badge">Current</span>@endif
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </li>

            @if ($currentSection && $currentSectionKey !== 'overview')
                <li class="breadcrumb-separator" aria-hidden="true">
                    <i class="ph ph-caret-right"></i>
                </li>

                <!-- Section Crumb -->
                <li class="breadcrumb-item breadcrumb-dropdown-container">
                    <button type="button" class="breadcrumb-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="breadcrumb-text">{{ $currentSection['title'] }}</span>
                    </button>
                    <div class="breadcrumb-dropdown-menu">
                        <div class="dropdown-header">Pages in {{ $currentSection['title'] }}</div>
                        @foreach ($currentSection['items'] as $sItem)
                            <a href="{{ $sItem['url'] }}" class="dropdown-item{{ $currentRouteName === $sItem['route'] ? ' active' : '' }}">
                                <i class="ph {{ $sItem['icon'] ?? 'ph-file-text' }}"></i>
                                <span class="dropdown-item-text">{{ $sItem['title'] }}</span>
                                @if ($currentRouteName === $sItem['route'])
                                    <span class="dropdown-badge">Current</span>
                                @endif
                            </a>
                        @endforeach

                        @if (count($currentModule['sections']) > 1)
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Other {{ $currentModule['title'] }} Sections</div>
                            @foreach ($currentModule['sections'] as $otherKey => $otherSec)
                                @if ($otherKey !== $currentSectionKey)
                                    <a href="{{ $otherSec['url'] }}" class="dropdown-item secondary">
                                        <i class="ph {{ $otherSec['icon'] ?? 'ph-folder' }}"></i>
                                        <span class="dropdown-item-text">{{ $otherSec['title'] }}</span>
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </li>
            @endif
        </ol>
    </nav>
    @endif
</div>
