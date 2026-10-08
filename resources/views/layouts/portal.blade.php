@extends('layouts.base')

@section('body')
<div class="app-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="portal-sidebar">
        <!-- Sidebar Header / Logo -->
        <div class="sidebar-header">
            <a href="{{ route('portal.dashboard') }}" class="brand-logo">
                <div class="brand-icon-box">
                    <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span>APEX <span class="gradient-text">ESTATES</span></span>
                <span class="brand-badge">PRO</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="sidebar-nav">
            <div class="nav-section-title">Overview</div>
            <a href="{{ route('portal.dashboard') }}" class="nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>

            @if(Auth::user()->hasPermission('scrapers.view') || Auth::user()->hasPermission('properties.view'))
                <div class="nav-section-title">Data Ingestion</div>
                @if(Auth::user()->hasPermission('scrapers.view'))
                    <a href="{{ route('portal.scrapers') }}" class="nav-item {{ request()->routeIs('portal.scrapers*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Scraper Hub</span>
                        <span class="nav-pill-badge">{{ \App\Models\ScraperTask::where('status', 'running')->count() }} live</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('properties.view'))
                    <a href="{{ route('portal.properties') }}" class="nav-item {{ request()->routeIs('portal.properties*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Listings</span>
                        <span class="nav-pill-badge">{{ number_format(\App\Models\Property::count()) }}</span>
                    </a>

                    <a href="{{ route('portal.map') }}" class="nav-item {{ request()->routeIs('portal.map*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <span>Listings Map</span>
                        <span class="nav-pill-badge" style="background:rgba(59,130,246,0.15);color:var(--primary);font-weight:700;">GIS</span>
                    </a>
                @endif
            @endif

            @if(Auth::user()->hasPermission('valuation.deals') || Auth::user()->hasPermission('valuation.cma') || Auth::user()->hasPermission('valuation.land'))
                <div class="nav-section-title">Valuation & Intelligence</div>
                @if(Auth::user()->hasPermission('valuation.deals'))
                    <a href="{{ route('portal.deals') }}" class="nav-item {{ request()->routeIs('portal.deals*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Deal & Discount Finder</span>
                        <span class="nav-pill-badge" style="background:var(--danger-bg);color:var(--danger);font-weight:700;">Hot</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('valuation.cma'))
                    <a href="{{ route('portal.cma') }}" class="nav-item {{ request()->routeIs('portal.cma*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>CMA Valuation</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('valuation.land'))
                    <a href="{{ route('portal.land_estimator') }}" class="nav-item {{ request()->routeIs('portal.land_estimator*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <span>Land Estimator</span>
                    </a>
                @endif
            @endif

            @if(Auth::user()->hasPermission('users.view') || Auth::user()->hasPermission('permissions.view'))
                <div class="nav-section-title">Administration</div>
                @if(Auth::user()->hasPermission('users.view'))
                    <a href="{{ route('portal.users') }}" class="nav-item {{ request()->routeIs('portal.users*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>User Management</span>
                        <span class="nav-pill-badge">{{ \App\Models\User::count() }}</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('permissions.view'))
                    <a href="{{ route('portal.permissions') }}" class="nav-item {{ request()->routeIs('portal.permissions*') ? 'active' : '' }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Access Permissions</span>
                        <span class="nav-pill-badge" style="background:rgba(16,185,129,0.15);color:var(--success);font-weight:700;">RBAC</span>
                    </a>
                @endif
            @endif

            <div class="nav-section-title">Account & Config</div>
            <a href="{{ route('portal.profile') }}" class="nav-item {{ request()->routeIs('portal.profile*') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Settings & Theme</span>
            </a>
        </nav>

        <!-- Sidebar User Footer -->
        <div class="sidebar-footer">
            <div class="user-mini-card">
                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="user-avatar-sm">
                <div class="user-mini-meta">
                    <div class="user-mini-name">{{ Auth::user()->name }}</div>
                    <div class="user-mini-role">{{ Auth::user()->role }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-icon" style="width:32px;height:32px;border:none;" title="Sign Out">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="menu-toggle-btn" id="menu-toggle-btn" aria-label="Toggle menu">
                    <svg style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="topbar-search">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Search scrapers, listings, or logs... (Press /)" onkeydown="if(event.key==='Enter') window.location.href='{{ route('portal.properties') }}?search='+this.value">
                </div>
            </div>

            <div class="topbar-right">
                <!-- LIGHT / DARK MODE SWITCHER (Segmented control) -->
                <div class="theme-switch-wrapper" id="theme-switch-control">
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
                    <button type="button" class="theme-toggle-btn" data-theme-set="system" onclick="setPortalTheme('system')" title="Sync with System OS">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Auto</span>
                    </button>
                </div>

                <!-- Quick Theme Toggle Icon -->
                <button type="button" class="theme-quick-btn" onclick="toggleQuickTheme()" title="Quick Switch Light/Dark">
                    <svg id="theme-quick-icon" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Notifications -->
                <button type="button" class="btn-icon" onclick="showToast('You have 2 pending scraper jobs completed.', 'info')" title="Notifications">
                    <span class="badge-dot"></span>
                    <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>

                <!-- User Profile Trigger -->
                <a href="{{ route('portal.profile') }}" class="user-menu-trigger" title="Account Settings">
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="user-avatar-md">
                </a>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="content-body">
            @if(session('error'))
                <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                    <svg style="width:20px;height:20px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

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

            @if($errors->any())
                <div class="alert alert-danger">
                    <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <div>
                        <strong>Please resolve the following:</strong>
                        <ul style="margin-left: 1.25rem; margin-top: 0.25rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@yield('modals')
@endsection
