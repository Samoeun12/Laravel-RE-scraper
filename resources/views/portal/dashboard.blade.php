@extends('layouts.portal')

@section('title', 'Intelligence Dashboard')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Executive Intelligence Overview</h1>
        <p class="page-subtitle">Real-time real estate market crawlers, analytics, and data pipeline monitoring</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <button type="button" class="btn btn-secondary" onclick="openModal('modal-add-property')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Property</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="openModal('modal-new-scraper')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span>New Scraper Job</span>
        </button>
    </div>
</div>

<!-- Stats KPI Grid -->
<div class="stats-grid">
    <!-- Stat 1 -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon indigo">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <span class="stat-trend up">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                +14.8%
            </span>
        </div>
        <div class="stat-value">{{ number_format($stats['total_properties']) }}</div>
        <div class="stat-label">Properties Cataloged</div>
    </div>

    <!-- Stat 2 -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon emerald">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <span class="stat-trend up">
                <span class="badge-dot" style="background:var(--success);animation:pulse 1.5s infinite;"></span>
                {{ $stats['running_scrapers'] }} Active
            </span>
        </div>
        <div class="stat-value">{{ $stats['total_scrapers'] }}</div>
        <div class="stat-label">Configured Scrapers</div>
    </div>

    <!-- Stat 3 -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon violet">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <span class="stat-trend up">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                +320 Today
            </span>
        </div>
        <div class="stat-value">{{ number_format($stats['total_items_scraped']) }}</div>
        <div class="stat-label">Total Data Points Ingested</div>
    </div>

    <!-- Stat 4 -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon amber">
                <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <span class="stat-trend warning">Market Yield</span>
        </div>
        <div class="stat-value">{{ $stats['for_sale_count'] }} <span style="font-size:1.1rem;color:var(--text-muted);font-weight:500;">/ {{ $stats['for_rent_count'] }}</span></div>
        <div class="stat-label">Sale vs Rent Inventory</div>
    </div>
</div>

<!-- Dashboard Grid (2 Columns) -->
<div class="dashboard-grid">
    <!-- Left Column -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Scraper Pipeline Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Active Scraper Crawlers</span>
                </div>
                <a href="{{ route('portal.scrapers') }}" class="btn btn-secondary btn-sm">
                    <span>Manage All</span>
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pipeline Target</th>
                            <th>Portal Source</th>
                            <th>Status</th>
                            <th>Harvested</th>
                            <th>Last Dispatch</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scrapers as $task)
                            <tr id="scraper-row-{{ $task->id }}">
                                <td>
                                    <div style="font-weight: 600;">{{ $task->name }}</div>
                                    <div style="font-size: 0.775rem; color: var(--text-muted);">{{ $task->category }} &bull; {{ $task->frequency }}</div>
                                </td>
                                <td>
                                    <span style="font-weight: 500;">{{ $task->source_name }}</span>
                                </td>
                                <td>
                                    <span class="badge status-{{ $task->status }}">
                                        <span class="badge-dot"></span>
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="scraped-count" style="font-weight: 700;">{{ number_format($task->items_scraped) }}</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">units</span>
                                </td>
                                <td class="last-run-time" style="font-size: 0.8rem; color: var(--text-secondary);">
                                    {{ $task->last_run_at ? $task->last_run_at->diffForHumans() : 'Never' }}
                                </td>
                                <td style="text-align: right;">
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary btn-sm"
                                        onclick="triggerScraperAjax(this, {{ $task->id }})"
                                        title="{{ $task->status === 'running' ? 'Pause Scraper' : 'Dispatch Immediate Extraction' }}"
                                    >
                                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            @if($task->status === 'running')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            @endif
                                        </svg>
                                        <span>{{ $task->status === 'running' ? 'Pause' : 'Run Now' }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    No scrapers configured yet. Click "New Scraper Job" to set one up.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Properties Grid -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Latest Scraped Listings</span>
                </div>
                <a href="{{ route('portal.properties') }}" class="btn btn-secondary btn-sm">
                    <span>View All Listings</span>
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>
            <div class="card-body">
                <div class="property-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                    @forelse($recentProperties->take(4) as $item)
                        <div class="property-card">
                            <div class="property-thumb-wrapper" style="height: 160px;">
                                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="property-thumb" loading="lazy">
                                <span class="property-tag-badge">{{ $item->listing_type }} &bull; {{ $item->property_type }}</span>
                                <span class="property-price-badge">{{ $item->formatted_price }}</span>
                            </div>
                            <div class="property-content">
                                <h3 class="property-title" style="font-size: 0.95rem;">{{ $item->title }}</h3>
                                <div class="property-location">
                                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span>{{ $item->location }}, {{ $item->city }}</span>
                                </div>
                                <div class="property-specs">
                                    <span class="spec-item">{{ $item->bedrooms }} Beds</span>
                                    <span class="spec-item">{{ $item->bathrooms }} Baths</span>
                                    <span class="spec-item">{{ $item->area_sqm ? $item->area_sqm . ' m²' : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">
                            No properties recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Status & Activity -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Theme & Environment Control Box -->
        <div class="card" style="background: linear-gradient(145deg, var(--bg-surface) 0%, var(--bg-surface-elevated) 100%);">
            <div class="card-header">
                <div class="card-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Appearance & Mode</span>
                </div>
            </div>
            <div class="card-body">
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Quickly switch portal visual style. Light mode delivers sharp contrast for bright daylight, dark mode provides optimal focus.
                </p>

                <div class="theme-switch-wrapper" style="width: 100%; justify-content: space-around; padding: 6px;">
                    <button type="button" class="theme-toggle-btn" data-theme-set="light" onclick="setPortalTheme('light')" style="flex:1; justify-content:center; padding:8px 12px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <span>Light</span>
                    </button>
                    <button type="button" class="theme-toggle-btn" data-theme-set="dark" onclick="setPortalTheme('dark')" style="flex:1; justify-content:center; padding:8px 12px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        <span>Dark</span>
                    </button>
                    <button type="button" class="theme-toggle-btn" data-theme-set="system" onclick="setPortalTheme('system')" style="flex:1; justify-content:center; padding:8px 12px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <span>Auto</span>
                    </button>
                </div>
            </div>
        </div>

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
                    <input type="text" name="name" class="form-control" placeholder="e.g. Phnom Penh Prime Condos" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Source Platform</label>
                        <input type="text" name="source_name" class="form-control" placeholder="e.g. Realestate.com.kh" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
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

<!-- Modal: Quick Add Property -->
<div class="modal-backdrop" id="modal-add-property">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Record Property Listing</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-add-property')">
                <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('portal.properties.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Listing Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Modern High-Rise Loft with Pool" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Property Type</label>
                        <select name="property_type" class="form-control">
                            <option value="Condo">Condo</option>
                            <option value="Apartment">Apartment</option>
                            <option value="Villa">Villa</option>
                            <option value="Land">Land</option>
                            <option value="Commercial">Commercial</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Market Listing Type</label>
                        <select name="listing_type" class="form-control">
                            <option value="Sale">For Sale</option>
                            <option value="Rent">For Rent</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price (USD)</label>
                        <input type="number" step="100" name="price" class="form-control" placeholder="350000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Area (sqm)</label>
                        <input type="number" step="0.1" name="area_sqm" class="form-control" placeholder="120">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" name="bedrooms" value="2" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" name="bathrooms" value="2" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Neighborhood / Area</label>
                        <input type="text" name="location" class="form-control" placeholder="BKK1, Chamkarmon" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" value="Phnom Penh" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-property')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Listing</button>
            </div>
        </form>
    </div>
</div>
@endsection
