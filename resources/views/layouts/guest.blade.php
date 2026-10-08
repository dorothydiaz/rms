<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In - Restaurant Management System')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/logo.svg') }}">

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
<body class="login-body">
    @yield('content')

    @stack('scripts')
</body>
</html>
