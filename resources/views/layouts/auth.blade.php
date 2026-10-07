@extends('layouts.base')

@section('body')
<div class="auth-wrapper">
    <!-- Ambient glowing backdrop orbs -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <!-- Top Auth Bar with Light/Dark Mode Switcher -->
    <header style="position: absolute; top: 1.5rem; left: 2rem; right: 2rem; display: flex; align-items: center; justify-content: space-between; z-index: 20;">
        <a href="{{ url('/') }}" class="brand-logo">
            <div class="brand-icon-box">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <span>APEX <span class="gradient-text">ESTATES</span></span>
        </a>

        <!-- Light / Dark Switcher in Auth Navbar -->
        <div class="theme-switch-wrapper">
            <button type="button" class="theme-toggle-btn" data-theme-set="light" onclick="setPortalTheme('light')" title="Light Mode">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Light</span>
            </button>
            <button type="button" class="theme-toggle-btn" data-theme-set="dark" onclick="setPortalTheme('dark')" title="Dark Mode">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <span>Dark</span>
            </button>
            <button type="button" class="theme-toggle-btn" data-theme-set="system" onclick="setPortalTheme('system')" title="System Match">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Auto</span>
            </button>
        </div>
    </header>

    <!-- Main Auth Card -->
    <main class="auth-card">
        @if(session('success'))
            <div class="alert alert-success">
                <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer style="margin-top: 2rem; font-size: 0.8rem; color: var(--text-muted); text-align: center; z-index: 10;">
        &copy; {{ date('Y') }} Apex Real Estate Intelligence Platform. End-to-end Encrypted.
    </footer>
</div>
@endsection
