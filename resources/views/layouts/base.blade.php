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

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Portal CSS -->
    <link rel="stylesheet" href="{{ app()->environment('production') || request()->secure() ? secure_asset('css/portal.css') : asset('css/portal.css') }}">
    
    @stack('styles')
</head>
<body>
    @yield('body')

    <!-- Portal JS -->
    <script src="{{ app()->environment('production') || request()->secure() ? secure_asset('js/portal.js') : asset('js/portal.js') }}"></script>
    @stack('scripts')
</body>
</html>
