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
                    'icon' => 'ph-chart-bar',
                    'items' => [
                        ['title' => 'HR Dashboard', 'url' => route('hr.dashboard'), 'icon' => 'ph-chart-pie-slice', 'route' => 'hr.dashboard']
                    ]
                ],
                'people' => [
                    'title' => 'People Administration',
                    'url' => route('hr.people.employees'),
                    'icon' => 'ph-users',
                    'items' => [
                        ['title' => 'Employees Directory', 'url' => route('hr.people.employees'), 'icon' => 'ph-user-list', 'route' => 'hr.people.employees'],
                        ['title' => 'Departments', 'url' => route('hr.people.departments'), 'icon' => 'ph-tree-structure', 'route' => 'hr.people.departments'],
                        ['title' => 'Positions', 'url' => route('hr.people.positions'), 'icon' => 'ph-identification-card', 'route' => 'hr.people.positions'],
                        ['title' => 'Branches', 'url' => route('hr.people.branches'), 'icon' => 'ph-storefront', 'route' => 'hr.people.branches'],
                        ['title' => 'Documents Repository', 'url' => route('hr.people.documents'), 'icon' => 'ph-folder-simple-user', 'route' => 'hr.people.documents'],
                        ['title' => 'Employee Masterlist (Legacy)', 'url' => route('hr.employee'), 'icon' => 'ph-users', 'route' => 'hr.employee'],
                    ]
                ],
                'recruitment' => [
                    'title' => 'Recruitment',
                    'url' => route('hr.recruitment.vacancies'),
                    'icon' => 'ph-user-plus',
                    'items' => [
                        ['title' => 'Job Vacancies', 'url' => route('hr.recruitment.vacancies'), 'icon' => 'ph-briefcase', 'route' => 'hr.recruitment.vacancies'],
                        ['title' => 'Applicants Pipeline', 'url' => route('hr.recruitment.applicants'), 'icon' => 'ph-user-focus', 'route' => 'hr.recruitment.applicants'],
                        ['title' => 'Interview Evaluations', 'url' => route('hr.recruitment.interviews'), 'icon' => 'ph-chat-circle-dots', 'route' => 'hr.recruitment.interviews'],
                    ]
                ],
                'attendance' => [
                    'title' => 'Attendance & Timekeeping',
                    'url' => route('hr.attendance.timekeeping'),
                    'icon' => 'ph-clock',
                    'items' => [
                        ['title' => 'Timekeeping Punch', 'url' => route('hr.attendance.timekeeping'), 'icon' => 'ph-fingerprint', 'route' => 'hr.attendance.timekeeping'],
                        ['title' => 'Daily Time Record (DTR)', 'url' => route('hr.attendance.dtr'), 'icon' => 'ph-calendar-check', 'route' => 'hr.attendance.dtr'],
                        ['title' => 'Work Schedules', 'url' => route('hr.attendance.schedules'), 'icon' => 'ph-calendar', 'route' => 'hr.attendance.schedules'],
                        ['title' => 'Overtime Requests', 'url' => route('hr.attendance.overtime'), 'icon' => 'ph-clock-countdown', 'route' => 'hr.attendance.overtime'],
                        ['title' => 'Attendance Corrections', 'url' => route('hr.attendance.corrections'), 'icon' => 'ph-clock-afternoon', 'route' => 'hr.attendance.corrections'],
                        ['title' => 'Check-in (Legacy)', 'url' => route('hr.attendance-checkin'), 'icon' => 'ph-clock', 'route' => 'hr.attendance-checkin'],
                        ['title' => 'Schedule (Legacy)', 'url' => route('hr.attendance-schedule'), 'icon' => 'ph-calendar', 'route' => 'hr.attendance-schedule'],
                    ]
                ],
                'leave' => [
                    'title' => 'Leave & Absence',
                    'url' => route('hr.leave.requests'),
                    'icon' => 'ph-calendar-blank',
                    'items' => [
                        ['title' => 'Leave Requests', 'url' => route('hr.leave.requests'), 'icon' => 'ph-calendar-plus', 'route' => 'hr.leave.requests'],
                        ['title' => 'Leave Types', 'url' => route('hr.leave.types'), 'icon' => 'ph-list-checks', 'route' => 'hr.leave.types'],
                        ['title' => 'Leave Credits', 'url' => route('hr.leave.credits'), 'icon' => 'ph-coins', 'route' => 'hr.leave.credits'],
                        ['title' => 'Leave Reports', 'url' => route('hr.leave.reports'), 'icon' => 'ph-chart-pie', 'route' => 'hr.leave.reports'],
                        ['title' => 'Leave Portal (Legacy)', 'url' => route('hr.employee-leave'), 'icon' => 'ph-calendar-x', 'route' => 'hr.employee-leave'],
                    ]
                ],
                'payroll' => [
                    'title' => 'Payroll & Statutory',
                    'url' => route('hr.payroll.register'),
                    'icon' => 'ph-wallet',
                    'items' => [
                        ['title' => 'Payroll Periods', 'url' => route('hr.payroll.periods'), 'icon' => 'ph-calendar-dots', 'route' => 'hr.payroll.periods'],
                        ['title' => 'Process Payroll', 'url' => route('hr.payroll.process'), 'icon' => 'ph-calculator', 'route' => 'hr.payroll.process'],
                        ['title' => 'Payroll Register', 'url' => route('hr.payroll.register'), 'icon' => 'ph-receipt', 'route' => 'hr.payroll.register'],
                        ['title' => 'Employee Payslips', 'url' => route('hr.payroll.payslips'), 'icon' => 'ph-file-text', 'route' => 'hr.payroll.payslips'],
                        ['title' => 'Statutory Contribution Rules', 'url' => route('hr.payroll.statutory-rules'), 'icon' => 'ph-sliders-horizontal', 'route' => 'hr.payroll.statutory-rules'],
                    ]
                ],
                'performance' => [
                    'title' => 'Performance',
                    'url' => route('hr.performance.evaluations'),
                    'icon' => 'ph-star',
                    'items' => [
                        ['title' => 'Evaluation Periods', 'url' => route('hr.performance.periods'), 'icon' => 'ph-calendar-check', 'route' => 'hr.performance.periods'],
                        ['title' => 'Competency Criteria', 'url' => route('hr.performance.criteria'), 'icon' => 'ph-list-numbers', 'route' => 'hr.performance.criteria'],
                        ['title' => 'Staff Evaluations', 'url' => route('hr.performance.evaluations'), 'icon' => 'ph-chart-line-up', 'route' => 'hr.performance.evaluations'],
                        ['title' => 'Performance Reports', 'url' => route('hr.performance.reports'), 'icon' => 'ph-chart-polar', 'route' => 'hr.performance.reports'],
                    ]
                ],
                'training' => [
                    'title' => 'Training & Development',
                    'url' => route('hr.training.programs'),
                    'icon' => 'ph-graduation-cap',
                    'items' => [
                        ['title' => 'Training Programs', 'url' => route('hr.training.programs'), 'icon' => 'ph-books', 'route' => 'hr.training.programs'],
                        ['title' => 'Training Records', 'url' => route('hr.training.records'), 'icon' => 'ph-certificate', 'route' => 'hr.training.records'],
                        ['title' => 'Training Reports', 'url' => route('hr.training.reports'), 'icon' => 'ph-chart-donut', 'route' => 'hr.training.reports'],
                    ]
                ],
                'reports' => [
                    'title' => 'HR Reports Center',
                    'url' => route('hr.reports.index'),
                    'icon' => 'ph-file-text',
                    'items' => [
                        ['title' => 'Reports & CSV Export', 'url' => route('hr.reports.index'), 'icon' => 'ph-download-simple', 'route' => 'hr.reports.index'],
                    ]
                ],
                'admin' => [
                    'title' => 'Administration & Security',
                    'url' => route('hr.admin.users'),
                    'icon' => 'ph-shield-check',
                    'items' => [
                        ['title' => 'User Accounts', 'url' => route('hr.admin.users'), 'icon' => 'ph-users-three', 'route' => 'hr.admin.users'],
                        ['title' => 'Roles & Permissions', 'url' => route('hr.admin.roles'), 'icon' => 'ph-key', 'route' => 'hr.admin.roles'],
                        ['title' => 'System Settings', 'url' => route('hr.admin.settings'), 'icon' => 'ph-gear', 'route' => 'hr.admin.settings'],
                        ['title' => 'Audit Logs', 'url' => route('hr.admin.audit-logs'), 'icon' => 'ph-shield-check', 'route' => 'hr.admin.audit-logs'],
                        ['title' => 'Users Auth (Legacy)', 'url' => route('hr.users-auth'), 'icon' => 'ph-lock', 'route' => 'hr.users-auth'],
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
                <button type="button" class="breadcrumb-btn breadcrumb-pill-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="breadcrumb-text">{{ $currentModule['title'] }}</span>
                </button>
                <div class="breadcrumb-dropdown-menu">
                    <div class="dropdown-header">{{ strtoupper($currentModule['title']) }} PAGES</div>
                    @foreach ($currentModule['sections'] as $sKey => $sInfo)
                        <div class="dropdown-section-title">{{ $sInfo['title'] }}</div>
                        @foreach ($sInfo['items'] as $subItem)
                            <a href="{{ $subItem['url'] }}" class="dropdown-item{{ $currentRouteName === $subItem['route'] ? ' active' : '' }}">
                                <i class="ph {{ $subItem['icon'] ?? 'ph-circle' }}"></i>
                                <span class="dropdown-item-text">{{ $subItem['title'] }}</span>
                                @if ($currentRouteName === $subItem['route'])<span class="dropdown-badge">CURRENT</span>@endif
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
                    <button type="button" class="breadcrumb-btn breadcrumb-plain-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="breadcrumb-text">{{ $currentSection['title'] }}</span>
                    </button>
                    <div class="breadcrumb-dropdown-menu">
                        <div class="dropdown-header">Pages in {{ $currentSection['title'] }}</div>
                        @foreach ($currentSection['items'] as $sItem)
                            <a href="{{ $sItem['url'] }}" class="dropdown-item{{ $currentRouteName === $sItem['route'] ? ' active' : '' }}">
                                <i class="ph {{ $sItem['icon'] ?? 'ph-file-text' }}"></i>
                                <span class="dropdown-item-text">{{ $sItem['title'] }}</span>
                                @if ($currentRouteName === $sItem['route'])
                                    <span class="dropdown-badge">CURRENT</span>
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
