<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In - Restaurant Management System')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo-tab.svg') }}?v={{ file_exists(public_path('assets/img/logo-tab.svg')) ? filemtime(public_path('assets/img/logo-tab.svg')) : '1.0' }}">

    <!-- Custom RMS Stylesheet (Cached with filemtime instead of time() cache-buster) -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}?v={{ file_exists(public_path('assets/css/styles.css')) ? filemtime(public_path('assets/css/styles.css')) : '1.0' }}">

    <!-- Phosphor Icons (Pinned CSS instead of blocking JS loader) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css" media="print" onload="this.media='all'">

    <!-- Google Typography (Pruned to essential weights for Low-Spec Performance) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@600;700&family=Outfit:wght@600&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    @stack('styles')
</head>
<body class="login-body">
    @yield('content')

    @stack('scripts')
</body>
</html>
