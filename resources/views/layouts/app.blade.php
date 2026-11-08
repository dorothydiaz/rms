<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title'){{ Str::contains($__env->yieldContent('title'), 'Restaurant Management System') ? $__env->yieldContent('title') : $__env->yieldContent('title') . ' - Restaurant Management System' }}@else Restaurant Management System @endif</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo-tab.svg') }}?v={{ file_exists(public_path('assets/img/logo-tab.svg')) ? filemtime(public_path('assets/img/logo-tab.svg')) : '1.0' }}">

    <!-- Custom RMS Stylesheet (Cached with filemtime to prevent FOUC on page refresh) -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}?v={{ file_exists(public_path('assets/css/styles.css')) ? filemtime(public_path('assets/css/styles.css')) : '1.0' }}">
    <!-- Custom UoM Dropdown Component Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/uom-dropdown.css') }}?v={{ file_exists(public_path('assets/css/uom-dropdown.css')) ? filemtime(public_path('assets/css/uom-dropdown.css')) : '1.0' }}">

    <!-- Low-End Hardware ('Potato PC') Lite Mode Auto-Detection (Pre-Paint) -->
    <script>
        (function() {
            try {
                var saved = localStorage.getItem('rms_lite_mode');
                var cores = navigator.hardwareConcurrency || 4;
                var mem = navigator.deviceMemory || 4;
                var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var net = navigator.connection && ['slow-2g', '2g', '3g'].indexOf(navigator.connection.effectiveType) !== -1;
                var isLite = saved === 'on' || (saved !== 'off' && (cores <= 4 || mem <= 4 || reduced || net));
                if (isLite) document.documentElement.classList.add('rms-lite-mode');
                else if (saved !== 'off' && window.requestAnimationFrame) {
                    var frames = 0, t0 = performance.now();
                    var probe = function(now) {
                        if (++frames >= 45) {
                            if ((frames * 1000) / (now - t0) < 28) document.documentElement.classList.add('rms-lite-mode');
                            return;
                        }
                        requestAnimationFrame(probe);
                    };
                    requestAnimationFrame(probe);
                }
            } catch (e) {}
        })();
    </script>

    <!-- Phosphor Icons (Pinned CSS weights instead of runtime JS injector to prevent 6-font waterfall & CLS) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css" media="print" onload="this.media='all'">

    <!-- Google Typography (Pruned to essential weights for Low-Spec Performance) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@600;700&family=Outfit:wght@600&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Layer 2: Canonical RFQ/ERP Design System Core -->
    @vite('resources/css/erp-rfq-core.css')
    @stack('styles')
</head>
<body class="{{ request()->routeIs('account-settings*') ? 'page-account-settings' : '' }}">
    <div class="app-container">
        <!-- Dual-Rail Interactive Sidebar Component -->
        <x-sidebar />

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <x-breadcrumbs />
                </div>
                <div class="header-actions">
                    <div class="header-date-badge">
                        <i class="ph ph-calendar-blank"></i>
                        <span id="currentHeaderDate">{{ now()->format('D, M d, Y') }}</span>
                    </div>

                    <!-- Entity Hierarchy & Context Switcher -->
                    <x-context-switcher />

                    <button class="icon-btn notification-btn" title="Notifications" aria-label="Notifications">
                        <i class="ph ph-bell"></i>
                    </button>
                    <button class="mobile-menu-btn" aria-label="Toggle mobile menu">
                        <i class="ph ph-list"></i>
                    </button>
                </div>
            </header>

            <div class="content-area">
                @if (session('success'))
                    <div class="login-alert success" style="margin-bottom: 20px;">
                        <i class="ph ph-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="login-alert danger" style="margin-bottom: 20px;">
                        <i class="ph ph-warning-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    
    <!-- Dedicated Direct-Child Print Container for Spotless PDF/Print Rendering -->
    <div id="rfqDedicatedPrintSheet" class="rfq-dedicated-print-sheet"></div>

    <!-- Default Landing Location Modal -->
    <x-default-landing-modal />

    <!-- SweetAlert2 (Deferred for non-blocking render; kept global for system-wide dialogs) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <!-- Client Interactive JS (Cached with filemtime for instant 0ms 304 reloads) -->
    <script src="{{ asset('assets/js/script.js') }}?v={{ file_exists(public_path('assets/js/script.js')) ? filemtime(public_path('assets/js/script.js')) : '1.0' }}"></script>
    <!-- Universal UOM Registry & Cross-Module Hydration Engine -->
    <script src="{{ asset('assets/js/uom-registry.js') }}?v={{ file_exists(public_path('assets/js/uom-registry.js')) ? filemtime(public_path('assets/js/uom-registry.js')) : '1.0' }}"></script>
    @stack('blade-data')
    @stack('scripts')
</body>
</html>
