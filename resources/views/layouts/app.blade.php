<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title'){{ Str::contains($__env->yieldContent('title'), 'Restaurant Management System') ? $__env->yieldContent('title') : $__env->yieldContent('title') . ' - Restaurant Management System' }}@else Restaurant Management System @endif</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo.svg') }}">

    <!-- Custom RMS Stylesheet (Cached with filemtime to prevent FOUC on page refresh) -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}?v={{ file_exists(public_path('assets/css/styles.css')) ? filemtime(public_path('assets/css/styles.css')) : '1.0' }}">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Google Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800;900&family=Outfit:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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

    <!-- SweetAlert2 (Rich Interactive Alerts & Confirmation Modals) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Client Interactive JS -->
    <script src="{{ asset('assets/js/script.js') }}?v={{ time() }}"></script>
    @stack('scripts')
</body>
</html>
