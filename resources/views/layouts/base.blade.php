<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-user-theme="{{ Auth::user()->theme_preference ?? 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Apex RE Portal') - Intelligence & Data Scraper</title>

    <!-- Pre-render theme script to avoid FOUC (flash of light/dark) -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('apex_portal_theme') || document.documentElement.getAttribute('data-user-theme') || 'dark';
                let effectiveTheme = savedTheme;
                if (savedTheme === 'system') {
                    effectiveTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', effectiveTheme);
                document.documentElement.setAttribute('data-theme-setting', savedTheme);
            } catch (e) {}
        })();
    </script>

    <!-- Fonts: Plus Jakarta Sans & Khmer Typography (Kantumruy Pro, Noto Sans Khmer) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Noto+Sans+Khmer:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @php
        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);
        $cssUrl = $isLocalhost ? asset('css/portal.css') : secure_asset('css/portal.css');
        $jsUrl = $isLocalhost ? asset('js/portal.js') : secure_asset('js/portal.js');
    @endphp

    <!-- Portal CSS -->
    <link rel="stylesheet" href="{{ $cssUrl }}">
    
    @stack('styles')
</head>
<body>
    @yield('body')

    <!-- Portal JS -->
    <script src="{{ $jsUrl }}"></script>
    @stack('scripts')
</body>
</html>
