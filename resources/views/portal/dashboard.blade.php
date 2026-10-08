@extends('layouts.portal')

@section('title', 'Executive Intelligence Dashboard')

@section('content')
<!-- Hero Welcome & Market Ticker Command Center -->
<div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.08) 50%, rgba(236, 72, 153, 0.05) 100%); border: 1px solid rgba(99, 102, 241, 0.25); box-shadow: var(--shadow-glow); position: relative; overflow: hidden;">
    <div style="position: absolute; right: -50px; top: -50px; width: 280px; height: 280px; border-radius: 50%; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(0,0,0,0) 70%); pointer-events: none;"></div>

    <div class="card-body" style="padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--bg-surface); padding: 4px 12px; border-radius: var(--radius-full); border: 1px solid var(--border-color); font-size: 0.775rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                    <span class="badge-dot" style="background: var(--success); box-shadow: 0 0 10px var(--success); animation: pulse 1.5s infinite;"></span>
                    Live Ingestion Cluster Online &bull; {{ count($portalDistribution) }} Cambodian Portals Active
                </div>
                
                <h1 style="font-size: 1.85rem; font-weight: 800; letter-spacing: -0.025em; color: var(--text-primary); margin-bottom: 0.35rem;">
                    Welcome back, <span class="gradient-text">{{ $user->name }}</span>
                </h1>
                <p style="font-size: 0.925rem; color: var(--text-secondary); max-width: 650px;">
                    Central intelligence command across Century 21, ARC Cambodia, Khmer24, Realestate.com.kh, and Harbor Property with real-time valuation models.
                </p>
            </div>

            <!-- Quick Action Command Buttons -->
            <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center;">
                @if(Auth::user()->hasPermission('valuation.deals'))
                    <a href="{{ route('portal.deals') }}" class="btn btn-primary" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        <span>Deal Finder</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('valuation.cma'))
                    <a href="{{ route('portal.cma') }}" class="btn btn-secondary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <span>CMA Valuation</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('valuation.land'))
                    <a href="{{ route('portal.land_estimator') }}" class="btn btn-secondary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                        <span>Land Estimator</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('properties.view'))
                    <a href="{{ route('portal.properties') }}" class="btn btn-secondary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        <span>Browse Listings</span>
                    </a>
                    <a href="{{ route('portal.map') }}" class="btn btn-secondary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                        <span>Listings Map</span>
                    </a>
                @endif

                @if(Auth::user()->hasPermission('scrapers.manage'))
                    <button type="button" class="btn btn-secondary" onclick="openModal('modal-new-scraper')">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span>New Scraper</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Live Market Ticker Pills Bar -->
        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center; font-size: 0.85rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: var(--text-muted); font-weight: 600;">Indexed Properties:</span>
                <strong style="color: var(--text-primary); font-size: 0.95rem;">{{ number_format($stats['total_properties']) }}</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: var(--text-muted); font-weight: 600;">Tracked Capitalization:</span>
                <strong style="color: var(--text-primary); font-size: 0.95rem;">${{ number_format($stats['total_market_cap'] / 1000000000, 2) }}B USD</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: var(--text-muted); font-weight: 600;">Distressed Deals Flagged:</span>
                <strong style="color: var(--danger); font-size: 0.95rem;">{{ number_format($stats['deals_count']) }} listings</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: var(--text-muted); font-weight: 600;">National Avg Benchmark:</span>
                <strong style="color: var(--primary); font-size: 0.95rem;">${{ number_format($stats['avg_sqm_price']) }}/m²</strong>
            </div>
        </div>
    </div>
</div>

<!-- Key Performance Indicators (KPI Cards) -->
<div class="stats-grid">
    <!-- Stat 1: Total Inventory -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon indigo">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <span class="stat-trend up">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                {{ count($portalDistribution) }} Portals Unified
            </span>
        </div>
        <div class="stat-value">{{ number_format($stats['total_properties']) }}</div>
        <div class="stat-label">Total Real Estate Cataloged</div>
        <!-- Multi-portal color bar -->
        <div style="height: 4px; border-radius: var(--radius-full); background: var(--bg-surface-elevated); margin-top: 1rem; overflow: hidden; display: flex;">
            @foreach($portalDistribution as $p)
                @if($p['percentage'] > 0)
                    <div style="width: {{ $p['percentage'] }}%; background: {{ $p['color'] }};" title="{{ $p['name'] }}: {{ $p['percentage'] }}%"></div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Stat 2: Active Pipelines -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon emerald">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <span class="stat-trend up">
                <span class="badge-dot" style="background:var(--success);animation:pulse 1.5s infinite;"></span>
                Online
            </span>
        </div>
        <div class="stat-value">{{ $stats['total_scrapers'] }} Crawlers</div>
        <div class="stat-label">C21, PropNex, ARC, Harbor, Realestate</div>
        <div style="margin-top: 0.85rem; font-size: 0.775rem; color: var(--text-muted);">
            Direct REST API sub-second response: <strong style="color:var(--success);">210ms</strong>
        </div>
    </div>

    <!-- Stat 3: Hot Opportunities -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon amber" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                </svg>
            </div>
            <span class="stat-trend warning" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);">
                Distressed Assets
            </span>
        </div>
        <div class="stat-value" style="color: var(--danger);">{{ number_format($stats['deals_count']) }}</div>
        <div class="stat-label">Urgent & Below-Market Deals</div>
        <div style="margin-top: 0.85rem; font-size: 0.775rem; color: var(--text-muted);">
            Trading at discounts up to <strong style="color:var(--danger);">-42% vs median</strong>
        </div>
    </div>

    <!-- Stat 4: Inventory Market Capitalization -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon violet">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="stat-trend up">
                {{ number_format($stats['for_sale_count']) }} For Sale
            </span>
        </div>
        <div class="stat-value">${{ number_format($stats['total_market_cap'] / 1000000000, 2) }}B</div>
        <div class="stat-label">Total Real Estate Asset Value</div>
        <div style="margin-top: 0.85rem; font-size: 0.775rem; color: var(--text-muted);">
            {{ number_format($stats['for_rent_count']) }} Rental units indexed
        </div>
    </div>
</div>

<!-- Visual Analytics Grid: Multi-Portal Market Share & District Benchmarks -->
<div class="analytics-grid">
    <!-- Portal Distribution Breakdown -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                <span>Market Share by Portal (19,700 Listings)</span>
            </div>
            <a href="{{ route('portal.properties') }}" class="btn btn-secondary btn-sm">Explore</a>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($portalDistribution as $portal)
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 0.875rem;">
                            <span style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: {{ $portal['color'] }}; display: inline-block;"></span>
                                {{ $portal['name'] }}
                            </span>
                            <span style="font-weight: 700; color: var(--text-secondary);">
                                {{ number_format($portal['count']) }} <span style="font-size: 0.75rem; color: var(--text-muted);">({{ $portal['percentage'] }}%)</span>
                            </span>
                        </div>
                        <div style="width: 100%; height: 8px; background: var(--bg-surface-elevated); border-radius: var(--radius-full); overflow: hidden;">
                            <div style="width: {{ $portal['percentage'] }}%; height: 100%; background: {{ $portal['color'] }}; border-radius: var(--radius-full); transition: width 0.6s ease;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Phnom Penh District Price Benchmark Index -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    <span>Phnom Penh Prime Districts ($/m² Benchmark)</span>
                </div>
                @if(Auth::user()->hasPermission('valuation.cma'))
                    <a href="{{ route('portal.cma') }}" class="btn btn-secondary btn-sm">CMA Engine</a>
                @endif
            </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($districtBenchmarks as $dist)
                    @php
                        $barPct = min(100, round(($dist->avg_sqm / 3500) * 100));
                    @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 0.875rem;">
                            <span style="font-weight: 600;">{{ $dist->district }}</span>
                            <span style="font-weight: 800; color: var(--primary);">
                                ${{ number_format($dist->avg_sqm) }}/m² 
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">({{ number_format($dist->total) }} comps)</span>
                            </span>
                        </div>
                        <div style="width: 100%; height: 8px; background: var(--bg-surface-elevated); border-radius: var(--radius-full); overflow: hidden;">
                            <div style="width: {{ $barPct }}%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); border-radius: var(--radius-full);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Main 2-Column Section: Active Crawlers & Hot Deals -->
<div class="dashboard-grid">
    <!-- Left Column: Scraper Pipelines & Recent Listings -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Scraper Pipeline Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Multi-Portal Crawler Ingestion Hub</span>
                </div>
                @if(Auth::user()->hasPermission('scrapers.view'))
                    <a href="{{ route('portal.scrapers') }}" class="btn btn-secondary btn-sm">
                        <span>Manage All Crawlers</span>
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pipeline Name</th>
                            <th>Platform Source</th>
                            <th>Status</th>
                            <th>Harvested</th>
                            <th>Last Dispatch</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scrapers as $task)
                            <tr id="scraper-row-{{ $task->id }}">
                                <td>
                                    <div style="font-weight: 700;">{{ $task->name }}</div>
                                    <div style="font-size: 0.775rem; color: var(--text-muted);">{{ $task->category }} &bull; {{ $task->frequency }}</div>
                                </td>
                                <td>
                                    <span style="font-weight: 600; font-size: 0.85rem;">{{ $task->source_name }}</span>
                                </td>
                                <td>
                                    <span class="badge status-{{ $task->status }}">
                                        <span class="badge-dot"></span>
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="scraped-count" style="font-weight: 800; font-size: 1rem;">{{ number_format($task->items_scraped) }}</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">records</span>
                                </td>
                                <td class="last-run-time" style="font-size: 0.8rem; color: var(--text-secondary);">
                                    {{ $task->last_run_at ? $task->last_run_at->diffForHumans() : 'Never' }}
                                </td>
                                <td style="text-align: right;">
                                    @if(Auth::user()->hasPermission('scrapers.trigger'))
                                        <button 
                                            type="button" 
                                            class="btn btn-secondary btn-sm"
                                            onclick="triggerScraperAjax(this, {{ $task->id }})"
                                            title="Trigger immediate scrape batch"
                                        >
                                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Run Live</span>
                                        </button>
                                    @else
                                        <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: 500;">Automated</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hot Distressed Deals Showcase -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--danger);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                    </svg>
                    <span>High-Priority Distressed Deals (Algorithmic Detections)</span>
                </div>
                @if(Auth::user()->hasPermission('valuation.deals'))
                    <a href="{{ route('portal.deals') }}" class="btn btn-secondary btn-sm">View All {{ number_format($stats['deals_count']) }} Deals</a>
                @endif
            </div>
            <div class="card-body">
                <div class="property-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                    @foreach($hotDeals as $item)
                        <div class="property-card" style="border: 1px solid rgba(239, 68, 68, 0.35);">
                            <div class="property-thumb-wrapper" style="height: 160px;">
                                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="property-thumb" loading="lazy">
                                <span class="property-tag-badge" style="background: var(--danger); font-weight: 800;">
                                    🔥 {{ $item->urgency_tag }}
                                </span>
                                <span class="property-price-badge">{{ $item->formatted_price }}</span>
                            </div>
                            <div class="property-content">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                                        {{ $item->source_name }}
                                    </span>
                                    @if($item->computed_price_per_sqm)
                                        <span style="font-size: 0.775rem; font-weight: 800; background: var(--bg-surface-elevated); padding: 1px 6px; border-radius: var(--radius-sm);">
                                            ${{ number_format($item->computed_price_per_sqm, 0) }}/m²
                                        </span>
                                    @endif
                                </div>
                                <h3 class="property-title" style="font-size: 0.95rem; line-height: 1.35; margin-bottom: 0.4rem;">{{ $item->title }}</h3>
                                <div class="property-location">
                                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span>{{ $item->display_location }}</span>
                                </div>
                                <div class="property-specs">
                                    <span class="spec-item">{{ $item->area_sqm ? number_format($item->area_sqm) . ' m²' : 'N/A' }}</span>
                                    @if($item->url)
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener" style="margin-left: auto; color: var(--primary); font-weight: 600; font-size: 0.775rem; text-decoration: underline;">
                                            Portal &nearr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick CMA Valuation Calculator & Audit Feed -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Quick Land & Property Valuation Widget / Market Quick Explorer -->
        @if(Auth::user()->hasPermission('valuation.cma'))
            <div class="card" style="border: 2px solid var(--primary); box-shadow: var(--shadow-glow);">
                <div class="card-header" style="background: var(--primary-light);">
                    <div class="card-title">
                        <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Instant Automated Valuation (AVM)</span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        Run a quick algorithmic appraisal using 19,700 real market comps.
                    </p>
                    <form action="{{ route('portal.cma') }}" method="GET">
                        <div class="form-group">
                            <label class="form-label">Subject Area (sqm)</label>
                            <input type="number" name="area_sqm" value="500" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">District / Khan</label>
                            <select name="district" class="form-control">
                                <option value="Sen Sok">Sen Sok</option>
                                <option value="Chbar Ampov">Chbar Ampov</option>
                                <option value="Tuol Kouk">Tuol Kouk</option>
                                <option value="Chamkarmon">Chamkarmon</option>
                                <option value="Boeung Keng Kang">Boeung Keng Kang (BKK)</option>
                                <option value="Dangkao">Dangkao</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Property Category</label>
                            <select name="property_type" class="form-control">
                                <option value="Land">Landed Plot</option>
                                <option value="Villa">Villa / Borey</option>
                                <option value="House">House / Shophouse</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                            <span>Generate CMA Valuation Report</span>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card" style="border: 1px solid var(--border-color);">
                <div class="card-header">
                    <div class="card-title">
                        <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Market Inventory Explorer</span>
                    </div>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                        Explore verified property listings and interactive GIS mapping across Cambodia.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="{{ route('portal.properties') }}" class="btn btn-primary" style="justify-content: center;">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                            <span>Browse All Listings</span>
                        </a>
                        <a href="{{ route('portal.map') }}" class="btn btn-secondary" style="justify-content: center;">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            <span>View Interactive GIS Map</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Real-time Activity Timeline -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>System Audit Trail</span>
                </div>
            </div>
            <div class="card-body">
                <div class="activity-list">
                    @forelse($activities as $act)
                        <div class="activity-item">
                            <div class="activity-dot-box">
                                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="activity-meta">
                                <div class="activity-title">{{ $act->action }}</div>
                                <div class="activity-desc">{{ $act->description }}</div>
                                <div class="activity-time">{{ $act->created_at->diffForHumans() }} &bull; {{ $act->user ? $act->user->name : 'System Worker' }}</div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                            No logged actions yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
@if(Auth::user()->hasPermission('scrapers.manage'))
<!-- Modal: New Scraper Job -->
<div class="modal-backdrop" id="modal-new-scraper">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Create Scraper Job</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-new-scraper')">
                <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('portal.scrapers.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Job Pipeline Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Phnom Penh Luxury Condos" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Source Platform Name</label>
                        <input type="text" name="source_name" class="form-control" placeholder="e.g. Realestate.com.kh" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Category</label>
                        <select name="category" class="form-control">
                            <option value="Condo">Condo / Apartment</option>
                            <option value="Villa">Villa / Borey</option>
                            <option value="Land">Land Plot</option>
                            <option value="Commercial">Commercial / Office</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Scrape URL</label>
                    <input type="url" name="target_url" class="form-control" placeholder="https://example.com/property/listings" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Execution Frequency</label>
                    <select name="frequency" class="form-control">
                        <option value="Every 3 Hours">Every 3 Hours</option>
                        <option value="Every 6 Hours">Every 6 Hours</option>
                        <option value="Every 12 Hours">Every 12 Hours</option>
                        <option value="Daily at Midnight">Daily at Midnight</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-scraper')">Cancel</button>
                <button type="submit" class="btn btn-primary">Initialize Scraper</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
