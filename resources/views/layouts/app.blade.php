<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Restaurant Management System')</title>

    <!-- Custom RMS Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}?v={{ time() }}">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Google Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800;900&family=Outfit:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
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

    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Client Interactive JS -->
    <script src="{{ asset('assets/js/script.js') }}"></script>
    @stack('scripts')
</body>
</html>
