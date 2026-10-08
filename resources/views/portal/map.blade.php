@extends('layouts.portal')

@section('title', 'Listings Map')

@push('styles')
<!-- Leaflet & MarkerCluster CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
/* Override content-body container to allow full right-side edge-to-edge map bleed */
.content-body {
    padding: 0 !important;
    max-width: 100% !important;
    height: calc(100vh - var(--topbar-height)) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
}

/* Theater Mode: Collapse portal sidebar for ultra-wide edge-to-edge GIS exploration */
.app-layout.map-theater-mode .sidebar {
    transform: translateX(-100%) !important;
}
.app-layout.map-theater-mode .main-wrapper {
    margin-left: 0 !important;
}

.map-layout-container {
    display: flex;
    flex: 1;
    height: 100%;
    width: 100%;
    overflow: hidden;
    position: relative;
}

/* ==========================================================================
   LEFT SIDE: Search, Filters & Scrollable Property Cards Feed
   ========================================================================== */
.map-left-panel {
    width: 440px;
    min-width: 380px;
    max-width: 480px;
    height: 100%;
    background: var(--bg-surface);
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    z-index: 25;
    transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s ease;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.06);
}

.map-left-panel.collapsed {
    width: 0 !important;
    min-width: 0 !important;
    max-width: 0 !important;
    transform: translateX(-100%);
    overflow: hidden;
    border-right: none;
    box-shadow: none;
}

/* Left Header & Search Section */
.left-panel-header {
    padding: 1rem 1.15rem 0.75rem;
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    flex-shrink: 0;
}

.left-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.left-panel-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.left-panel-badge {
    font-size: 0.7rem;
    font-weight: 700;
    background: rgba(99, 102, 241, 0.12);
    color: var(--primary);
    padding: 2px 8px;
    border-radius: var(--radius-full);
}

.left-search-box {
    position: relative;
    width: 100%;
}

.left-search-box input {
    width: 100%;
    padding: 0.55rem 2.25rem 0.55rem 2.25rem;
    border-radius: var(--radius-md);
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    font-size: 0.85rem;
    color: var(--text-primary);
    transition: all var(--transition-fast);
}

.left-search-box input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.left-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--text-muted);
    pointer-events: none;
}

.left-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 16px;
    padding: 2px 4px;
    display: none;
}

/* Filter Controls Rows */
.left-filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.filter-select {
    padding: 0.45rem 0.65rem;
    font-size: 0.775rem;
    border-radius: var(--radius-sm);
    background: var(--bg-surface-elevated);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    font-weight: 600;
    cursor: pointer;
    width: 100%;
}

.filter-select:focus {
    border-color: var(--primary);
}

/* Quick Jump Location Pills */
.left-quick-jumps {
    display: flex;
    gap: 0.35rem;
    overflow-x: auto;
    padding-bottom: 2px;
    scrollbar-width: none;
}
.left-quick-jumps::-webkit-scrollbar { display: none; }

.quick-pill {
    padding: 3px 9px;
    font-size: 0.725rem;
    font-weight: 700;
    border-radius: var(--radius-full);
    background: var(--bg-surface-elevated);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    cursor: pointer;
    white-space: nowrap;
    transition: all var(--transition-fast);
}

.quick-pill:hover, .quick-pill.active {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
}

/* Counter & Sync Controls Bar */
.left-status-bar {
    padding: 0.65rem 1.15rem;
    background: var(--bg-surface-elevated);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.775rem;
    flex-shrink: 0;
}

.results-counter {
    font-weight: 800;
    color: var(--text-primary);
}

.sync-toggle-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.725rem;
    color: var(--text-secondary);
    cursor: pointer;
    user-select: none;
}

/* Scrollable Property Cards Feed */
.map-cards-scroll-feed {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

/* Individual Property Card in Left Feed */
.property-feed-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
    display: flex;
    gap: 0.75rem;
    padding: 0.65rem;
    cursor: pointer;
    transition: all var(--transition-fast);
    position: relative;
}

.property-feed-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    background: var(--bg-surface-elevated);
}

.property-feed-card.highlighted {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px var(--primary);
    background: var(--bg-surface-elevated);
}

.feed-card-thumb-wrap {
    width: 105px;
    height: 92px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
    background: var(--bg-surface-elevated);
}

.feed-card-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.feed-card-badge {
    position: absolute;
    top: 4px;
    left: 4px;
    padding: 1px 6px;
    border-radius: var(--radius-full);
    font-size: 9px;
    font-weight: 800;
    color: #ffffff;
}

.feed-card-badge.rent { background: #2563eb; }
.feed-card-badge.sale { background: #10b981; }

.feed-card-urgency-badge {
    position: absolute;
    bottom: 4px;
    left: 4px;
    padding: 1px 5px;
    border-radius: var(--radius-sm);
    font-size: 8px;
    font-weight: 800;
    background: rgba(239, 68, 68, 0.92);
    color: #ffffff;
}

.feed-card-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}

.feed-card-price {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--primary);
}

.feed-card-title {
    font-size: 0.825rem;
    font-weight: 700;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    margin: 2px 0;
    font-family: 'Battambang', 'Plus Jakarta Sans', sans-serif;
}

.feed-card-location {
    font-size: 0.725rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-family: 'Battambang', 'Plus Jakarta Sans', sans-serif;
}

.feed-card-specs {
    font-size: 0.725rem;
    color: var(--text-muted);
    display: flex;
    gap: 8px;
    align-items: center;
}

.feed-card-source {
    font-size: 0.675rem;
    font-weight: 700;
    color: var(--primary);
}

/* ==========================================================================
   RIGHT SIDE: Full Edge-to-Edge Interactive Google Map
   ========================================================================== */
.map-right-panel {
    flex: 1;
    height: 100%;
    position: relative;
    display: flex;
    background: var(--bg-surface-elevated);
    min-width: 0;
}

#listings-google-map {
    width: 100%;
    height: 100%;
    z-index: 10;
}

/* Floating Controls on Google Map */
.map-floating-topbar {
    position: absolute;
    top: 16px;
    left: 16px;
    z-index: 1000;
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.map-control-pill {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 6px 13px;
    border-radius: var(--radius-full);
    font-size: 0.785rem;
    font-weight: 700;
    box-shadow: var(--shadow-md);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all var(--transition-fast);
}

.map-control-pill:hover {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
    transform: translateY(-1px);
}

.map-stat-badge-pill {
    background: rgba(15, 23, 42, 0.85);
    color: #ffffff;
    padding: 6px 14px;
    border-radius: var(--radius-full);
    font-size: 0.785rem;
    font-weight: 700;
    backdrop-filter: blur(8px);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Basemap Layer Switcher (Google Roads, Satellite, Hybrid, Dark) */
.map-floating-basemap-box {
    position: absolute;
    bottom: 24px;
    right: 24px;
    z-index: 1000;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 4px;
    box-shadow: var(--shadow-lg);
    display: flex;
    gap: 4px;
    align-items: center;
}

.basemap-opt-btn {
    border: none;
    background: transparent;
    color: var(--text-secondary);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 6px 10px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    gap: 4px;
}

.basemap-opt-btn.active, .basemap-opt-btn:hover {
    background: var(--primary);
    color: #ffffff;
}

/* Custom Price Tag Marker Pin (Zillow / Airbnb Style) */
.price-pill-pin {
    background: #ffffff;
    color: #0f172a;
    font-weight: 800;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 9999px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
    border: 2px solid #10b981;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    user-select: none;
}

.price-pill-pin:hover, .price-pill-pin.hovered {
    transform: scale(1.18);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
    z-index: 1000 !important;
}

.price-pill-pin.pin-rent {
    border-color: #2563eb;
    color: #0f172a;
}

.price-pill-pin.pin-urgent {
    border-color: #ef4444;
    background: #ef4444;
    color: #ffffff;
}

.price-pill-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
}

.price-pill-pin.pin-rent .price-pill-dot {
    background: #2563eb;
}

.price-pill-pin.pin-urgent .price-pill-dot {
    background: #ffffff;
}

/* Cluster Marker Ring */
.marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
    background-color: rgba(99, 102, 241, 0.4) !important;
}
.marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
    background-color: #6366f1 !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 12px !important;
}

/* Leaflet Popup Card */
.leaflet-popup-content-wrapper {
    background: var(--bg-surface) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: var(--radius-lg) !important;
    padding: 0 !important;
    overflow: hidden !important;
    box-shadow: var(--shadow-xl) !important;
}

.leaflet-popup-content {
    margin: 0 !important;
    width: 280px !important;
    line-height: 1.35 !important;
}

.leaflet-popup-tip {
    background: var(--bg-surface) !important;
    border: 1px solid var(--border-color) !important;
}

.map-popup-card {
    display: flex;
    flex-direction: column;
}

.map-popup-img-wrap {
    width: 100%;
    height: 140px;
    position: relative;
    overflow: hidden;
    background: var(--bg-surface-elevated);
}

.map-popup-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.map-popup-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 10px;
    font-weight: 800;
    color: #ffffff;
}

.map-popup-badge.rent { background: rgba(37, 99, 235, 0.95); }
.map-popup-badge.sale { background: rgba(16, 185, 129, 0.95); }

.map-popup-price {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(15, 23, 42, 0.88);
    color: #ffffff;
    font-weight: 800;
    font-size: 13px;
    padding: 3px 8px;
    border-radius: var(--radius-sm);
    backdrop-filter: blur(4px);
}

.map-popup-body {
    padding: 0.85rem;
}

.map-popup-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.35rem;
    line-height: 1.3;
    font-family: 'Battambang', 'Plus Jakarta Sans', sans-serif;
}

.map-popup-location {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 4px;
    font-family: 'Battambang', 'Plus Jakarta Sans', sans-serif;
}

.map-popup-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.5rem;
    border-top: 1px solid var(--border-color);
}

.map-popup-source {
    font-size: 0.725rem;
    font-weight: 700;
    color: var(--primary);
}

.map-popup-link {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--primary);
    text-decoration: underline;
}

/* Loading Spinner Overlay */
.map-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 1500;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: #ffffff;
    font-weight: 700;
    transition: opacity 0.25s ease;
    pointer-events: none;
    opacity: 0;
}

.map-loading-overlay.active {
    opacity: 1;
    pointer-events: all;
}

.spinner {
    width: 38px;
    height: 38px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 0.8s ease-in-out infinite;
    margin-bottom: 0.6rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .map-left-panel {
        width: 100% !important;
        max-width: 100% !important;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
    }
}
</style>
@endpush

@section('content')
<div class="map-layout-container" id="map-layout-root">
    <!-- =====================================================================
         LEFT PANEL: Search, Interactive Filters & Property Cards
         ===================================================================== -->
    <aside class="map-left-panel" id="map-left-panel">
        <div class="left-panel-header">
            <!-- Top Title Row -->
            <div class="left-header-top">
                <div class="left-panel-title">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span>Listings</span>
                    <span class="left-panel-badge" id="total-badge">{{ number_format($totalWithGps) }}</span>
                </div>

                <a href="{{ route('portal.properties') }}" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 0.75rem;" title="Switch to Grid View">
                    Grid View &rarr;
                </a>
            </div>

            <!-- Search Input -->
            <div class="left-search-box">
                <svg class="left-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input 
                    type="text" 
                    id="filter-search" 
                    placeholder="Search title, district, or project..." 
                    oninput="onSearchInput(this.value)"
                >
                <button type="button" id="search-clear-btn" class="left-search-clear" onclick="clearSearch()" title="Clear">&times;</button>
            </div>

            <!-- Filter Dropdowns Row 1 -->
            <div class="left-filter-row">
                <select id="filter-listing-type" class="filter-select" onchange="applyFilters()">
                    <option value="">All Markets (Sale & Rent)</option>
                    <option value="Sale">🏷️ For Sale</option>
                    <option value="Rent">🔑 For Rent</option>
                </select>

                <select id="filter-property-type" class="filter-select" onchange="applyFilters()">
                    <option value="">All Property Types</option>
                    @foreach($propertyTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Dropdowns Row 2 -->
            <div class="left-filter-row">
                <select id="filter-province" class="filter-select" onchange="onProvinceSelect(this.value)">
                    <option value="">All Provinces</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov }}">{{ $prov }}</option>
                    @endforeach
                </select>

                <select id="filter-source" class="filter-select" onchange="applyFilters()">
                    <option value="">All Portals (6 Sources)</option>
                    <option value="cambodia_re">Century 21 (C21)</option>
                    <option value="arc">ARC Cambodia</option>
                    <option value="realestate">Realestate.com.kh</option>
                    <option value="khmer24">Khmer24 Property</option>
                    <option value="harbor">Harbor Property</option>
                    <option value="propnex">PropNex Cambodia</option>
                </select>
            </div>

            <!-- Quick Location Jump Pills -->
            <div class="left-quick-jumps">
                <button type="button" class="quick-pill active" onclick="panToLocation(11.5564, 104.9282, 13)">Phnom Penh</button>
                <button type="button" class="quick-pill" onclick="panToLocation(11.5732, 104.8988, 14)">Toul Kork</button>
                <button type="button" class="quick-pill" onclick="panToLocation(11.5505, 104.9265, 15)">BKK1</button>
                <button type="button" class="quick-pill" onclick="panToLocation(11.5830, 104.8624, 14)">Sen Sok</button>
                <button type="button" class="quick-pill" onclick="panToLocation(11.5200, 104.9600, 14)">Chbar Ampov</button>
                <button type="button" class="quick-pill" onclick="panToLocation(13.3671, 103.8448, 13)">Siem Reap</button>
                <button type="button" class="quick-pill" onclick="panToLocation(10.6275, 103.5221, 13)">Sihanoukville</button>
                <button type="button" class="quick-pill" onclick="panToLocation(10.6104, 104.1815, 13)">Kampot</button>
                <button type="button" class="quick-pill" onclick="panToLocation(13.0957, 103.2022, 13)">Battambang</button>
                <button type="button" class="quick-pill" onclick="panToLocation(12.5657, 104.9910, 8)">All Cambodia</button>
            </div>
        </div>

        <!-- Result Status & Auto Sync -->
        <div class="left-status-bar">
            <span class="results-counter" id="matching-counter">
                Loading all listings...
            </span>

            <label class="sync-toggle-label" title="Automatically reload listings when you move or zoom the map">
                <input type="checkbox" id="sync-map-bounds" checked onchange="onSyncToggle(this.checked)">
                <span>Sync with map</span>
            </label>
        </div>

        <!-- Scrollable Property Cards Feed -->
        <div class="map-cards-scroll-feed" id="map-cards-scroll-feed">
            <!-- Cards injected via JavaScript -->
        </div>
    </aside>

    <!-- =====================================================================
         RIGHT PANEL: Full Edge-to-Edge Google Map
         ===================================================================== -->
    <main class="map-right-panel" id="map-right-panel">
        <!-- Google Map Container -->
        <div id="listings-google-map"></div>

        <!-- Floating Left-Panel Collapse Toggle -->
        <div class="map-floating-topbar">
            <button type="button" class="map-control-pill" id="toggle-panel-btn" onclick="toggleLeftPanel()" title="Toggle Listings Side Panel">
                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <span id="toggle-panel-text">Hide Listings</span>
            </button>

            <button type="button" class="map-control-pill" id="theater-btn" onclick="toggleTheaterMode()" title="Toggle Edge-to-Edge Full View">
                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                <span id="theater-text">Theater Mode</span>
            </button>

            <button type="button" class="map-control-pill" onclick="resetMapBounds()" title="Reset to Cambodia Overview">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Reset View</span>
            </button>

            <div class="map-stat-badge-pill" id="map-pill-counter">
                <span>📍 Loading all Cambodia listings...</span>
            </div>
        </div>

        <!-- Floating Basemap Selector (Google Roadmap, Satellite, Hybrid, Dark) -->
        <div class="map-floating-basemap-box">
            <button type="button" class="basemap-opt-btn active" id="btn-layer-streets" onclick="setBasemap('streets')">
                🗺️ Google Roads
            </button>
            <button type="button" class="basemap-opt-btn" id="btn-layer-satellite" onclick="setBasemap('satellite')">
                🛰️ Satellite
            </button>
            <button type="button" class="basemap-opt-btn" id="btn-layer-hybrid" onclick="setBasemap('hybrid')">
                🌐 Hybrid
            </button>
            <button type="button" class="basemap-opt-btn" id="btn-layer-dark" onclick="setBasemap('dark')">
                🌙 Dark GIS
            </button>
        </div>

        <!-- Loading Overlay -->
        <div id="map-loading-overlay" class="map-loading-overlay active">
            <div class="spinner"></div>
            <span id="loading-overlay-text">Plotting 19,783 Cambodian listings on Google Map...</span>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<!-- Leaflet & MarkerCluster JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// Map Instance & Layers
let map = null;
let currentLayer = null;
let markerClusterGroup = null;
let allProperties = [];      // Entire dataset (all 19,783)
let filteredProperties = []; // Filtered by search/type/market/province
let markersMap = new Map();  // id -> L.marker
let searchDebounceTimer = null;
let isSyncingBounds = true;

// Google Maps Raster Basemap Layers (No API Key Required)
const basemapLayers = {
    // Official Google Roads / Roadmap
    streets: L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: '&copy; Google Maps'
    }),
    // Google Satellite Imagery
    satellite: L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: '&copy; Google Maps Satellite'
    }),
    // Google Hybrid (Satellite + Roads & Town labels)
    hybrid: L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: '&copy; Google Maps Hybrid'
    }),
    // Dark GIS Map (CartoDB)
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 20,
        subdomains: 'abcd',
        attribution: '&copy; CartoDB &copy; OpenStreetMap'
    })
};

document.addEventListener('DOMContentLoaded', function() {
    initGoogleMap();
    fetchAllMapProperties();
});

function initGoogleMap() {
    // Center initially on Phnom Penh, Cambodia
    map = L.map('listings-google-map', {
        center: [11.5564, 104.9282],
        zoom: 13,
        zoomControl: false
    });

    // Custom Zoom Controls at Top-Right
    L.control.zoom({ position: 'topright' }).addTo(map);

    // Initial Default Layer: Google Roadmap
    currentLayer = basemapLayers.streets;
    currentLayer.addTo(map);

    // Marker Cluster Setup with chunkedLoading for 20,000+ points
    markerClusterGroup = L.markerClusterGroup({
        chunkedLoading: true,
        chunkInterval: 60,
        chunkDelay: 10,
        maxClusterRadius: 45,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        disableClusteringAtZoom: 18
    });
    map.addLayer(markerClusterGroup);

    // Map Move End Event (syncs listings with visible map viewport)
    map.on('moveend', function() {
        if (isSyncingBounds) {
            filterVisiblePropertiesInViewport();
        }
    });
}

// Basemap Switcher
function setBasemap(type) {
    if (!map || !basemapLayers[type]) return;

    if (currentLayer) {
        map.removeLayer(currentLayer);
    }

    currentLayer = basemapLayers[type];
    currentLayer.addTo(map);

    document.querySelectorAll('.basemap-opt-btn').forEach(b => b.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-layer-${type}`);
    if (activeBtn) activeBtn.classList.add('active');
}

// Quick Jump Location
function panToLocation(lat, lng, zoom) {
    if (!map) return;
    map.flyTo([lat, lng], zoom, {
        animate: true,
        duration: 1.1
    });

    document.querySelectorAll('.quick-pill').forEach(p => p.classList.remove('active'));
    if (window.event && window.event.target && window.event.target.classList.contains('quick-pill')) {
        window.event.target.classList.add('active');
    }
}

function onProvinceSelect(provinceName) {
    if (provinceName) {
        const centers = {
            'Phnom Penh': [11.5564, 104.9282, 13],
            'Siem Reap': [13.3671, 103.8448, 13],
            'Preah Sihanouk': [10.6275, 103.5221, 13],
            'Sihanoukville': [10.6275, 103.5221, 13],
            'Kandal': [11.4550, 104.9810, 12],
            'Kampot': [10.6104, 104.1815, 13],
            'Battambang': [13.0957, 103.2022, 13],
            'Kep': [10.4829, 104.3167, 13],
            'Koh Kong': [11.6153, 102.9838, 12],
            'Kampong Speu': [11.4533, 104.5209, 12]
        };

        if (centers[provinceName]) {
            const [lat, lng, z] = centers[provinceName];
            map.flyTo([lat, lng], z, { animate: true, duration: 1.0 });
        }
    }

    applyFilters();
}

function onSearchInput(val) {
    const clearBtn = document.getElementById('search-clear-btn');
    if (clearBtn) {
        clearBtn.style.display = val.trim() ? 'block' : 'none';
    }

    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        applyFilters();
    }, 250);
}

function clearSearch() {
    const input = document.getElementById('filter-search');
    input.value = '';
    const clearBtn = document.getElementById('search-clear-btn');
    if (clearBtn) clearBtn.style.display = 'none';
    applyFilters();
}

function onSyncToggle(enabled) {
    isSyncingBounds = enabled;
    if (isSyncingBounds) {
        filterVisiblePropertiesInViewport();
    } else {
        renderLeftCards(filteredProperties);
    }
}

// Fetch ALL Cambodian Properties from API
function fetchAllMapProperties() {
    const overlay = document.getElementById('map-loading-overlay');
    if (overlay) overlay.classList.add('active');

    // Request all properties up to 30,000 (covers entire 19,783 dataset)
    fetch(`{{ route('portal.map.api') }}?limit=30000`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.properties) {
                allProperties = res.properties;
                filteredProperties = allProperties;

                const totalBadge = document.getElementById('total-badge');
                if (totalBadge) totalBadge.innerText = allProperties.length.toLocaleString();

                plotMarkersOnMap(filteredProperties);

                if (isSyncingBounds) {
                    filterVisiblePropertiesInViewport();
                } else {
                    renderLeftCards(filteredProperties);
                }

                updateCounterBadges(filteredProperties.length, allProperties.length);
            }
        })
        .catch(err => {
            console.error('Map Properties API Error:', err);
        })
        .finally(() => {
            if (overlay) overlay.classList.remove('active');
        });
}

// Client-side Instant Filtering
function applyFilters() {
    const search = document.getElementById('filter-search').value.trim().toLowerCase();
    const listingType = document.getElementById('filter-listing-type').value;
    const propertyType = document.getElementById('filter-property-type').value;
    const province = document.getElementById('filter-province').value.toLowerCase();
    const source = document.getElementById('filter-source').value;

    filteredProperties = allProperties.filter(item => {
        if (listingType && item.listing_type !== listingType) {
            return false;
        }

        if (propertyType && item.property_type !== propertyType) {
            return false;
        }

        if (province) {
            const itemProv = (item.province || '').toLowerCase();
            const itemDist = (item.district || '').toLowerCase();
            const itemLoc = (item.location || '').toLowerCase();
            if (!itemProv.includes(province) && !itemDist.includes(province) && !itemLoc.includes(province)) {
                return false;
            }
        }

        if (source && item.source !== source) {
            return false;
        }

        if (search) {
            const title = (item.title || '').toLowerCase();
            const loc = (item.location || '').toLowerCase();
            const dist = (item.district || '').toLowerCase();
            const prov = (item.province || '').toLowerCase();
            const src = (item.source_name || '').toLowerCase();

            if (!title.includes(search) && !loc.includes(search) && !dist.includes(search) && !prov.includes(search) && !src.includes(search)) {
                return false;
            }
        }

        return true;
    });

    plotMarkersOnMap(filteredProperties);

    if (isSyncingBounds) {
        filterVisiblePropertiesInViewport();
    } else {
        renderLeftCards(filteredProperties);
    }

    updateCounterBadges(filteredProperties.length, allProperties.length);
}

// Plot Markers using Bulk Batching for Max Performance
function plotMarkersOnMap(properties) {
    if (!markerClusterGroup) return;

    markerClusterGroup.clearLayers();
    markersMap.clear();

    const markers = [];

    for (let i = 0; i < properties.length; i++) {
        const item = properties[i];
        if (!item.lat || !item.lng) continue;

        const isRent = (item.listing_type || '').toLowerCase() === 'rent';
        const isUrgent = !!item.urgency_tag;

        let pinClass = 'price-pill-pin';
        if (isUrgent) pinClass += ' pin-urgent';
        else if (isRent) pinClass += ' pin-rent';

        const customIcon = L.divIcon({
            className: 'custom-price-marker',
            html: `
                <div class="${pinClass}" id="marker-pill-${item.id}">
                    <span class="price-pill-dot"></span>
                    <span>${item.short_price}</span>
                </div>
            `,
            iconSize: [60, 24],
            iconAnchor: [30, 12]
        });

        const marker = L.marker([item.lat, item.lng], { icon: customIcon });

        // Glassmorphism Popup
        const popupContent = `
            <div class="map-popup-card">
                <div class="map-popup-img-wrap">
                    <img src="${item.image}" alt="${escapeHtml(item.title)}" class="map-popup-img" loading="lazy">
                    <span class="map-popup-badge ${isRent ? 'rent' : 'sale'}">
                        ${item.listing_type} &bull; ${item.property_type}
                    </span>
                    <span class="map-popup-price">${item.formatted_price}</span>
                </div>
                <div class="map-popup-body">
                    <h4 class="map-popup-title">${escapeHtml(item.title)}</h4>
                    <div class="map-popup-location">
                        <svg style="width:12px;height:12px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        <span>${escapeHtml(item.location || 'Cambodia')}</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);display:flex;gap:8px;margin-bottom:0.4rem;">
                        ${item.area_sqm ? `<span>${item.area_sqm} m²</span>` : ''}
                        ${item.bedrooms ? `<span>${item.bedrooms} Beds</span>` : ''}
                        ${item.computed_price_per_sqm ? `<span>$${Math.round(item.computed_price_per_sqm).toLocaleString()}/m²</span>` : ''}
                    </div>
                    <div class="map-popup-footer">
                        <span class="map-popup-source">${item.source_name || 'Portal'}</span>
                        ${item.url ? `<a href="${item.url}" target="_blank" rel="noopener" class="map-popup-link">View Original &nearr;</a>` : ''}
                    </div>
                </div>
            </div>
        `;

        marker.bindPopup(popupContent, { maxWidth: 285 });
        marker.on('click', () => {
            highlightFeedCard(item.id);
        });

        markers.push(marker);
        markersMap.set(item.id, marker);
    }

    // Add all markers in bulk chunked layers
    markerClusterGroup.addLayers(markers);
}

// Filters visible properties within map bounds
function filterVisiblePropertiesInViewport() {
    if (!map) return;
    const bounds = map.getBounds();

    const visibleProperties = filteredProperties.filter(item => {
        if (!item.lat || !item.lng) return false;
        return bounds.contains([item.lat, item.lng]);
    });

    renderLeftCards(visibleProperties);
    updateCounterBadges(visibleProperties.length, filteredProperties.length);
}

function updateCounterBadges(inViewCount, totalMatchingCount) {
    const counter = document.getElementById('matching-counter');
    if (counter) {
        if (isSyncingBounds) {
            counter.innerText = `${inViewCount.toLocaleString()} in view (${totalMatchingCount.toLocaleString()} total)`;
        } else {
            counter.innerText = `${totalMatchingCount.toLocaleString()} matching listings`;
        }
    }

    const pillCounter = document.getElementById('map-pill-counter');
    if (pillCounter) {
        pillCounter.innerText = `📍 ${totalMatchingCount.toLocaleString()} listings plotted`;
    }
}

// Render Property Cards in the Left Scrollable Panel
function renderLeftCards(properties) {
    const container = document.getElementById('map-cards-scroll-feed');
    if (!container) return;

    if (!properties || properties.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                <svg style="width:36px;height:36px;margin:0 auto 0.5rem;opacity:0.6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <p style="font-size: 0.85rem; font-weight: 600;">No properties found in this view.</p>
                <p style="font-size: 0.75rem; margin-top: 4px;">Try zooming out or clearing your search filters.</p>
            </div>
        `;
        return;
    }

    // Render up to 80 cards to maintain silky-smooth 60fps scrolling
    const cardsHtml = properties.slice(0, 80).map(item => {
        const isRent = (item.listing_type || '').toLowerCase() === 'rent';
        const isUrgent = !!item.urgency_tag;

        return `
            <div 
                class="property-feed-card" 
                id="feed-card-${item.id}" 
                onclick="panToProperty(${item.id})"
                onmouseenter="hoverMarker(${item.id}, true)"
                onmouseleave="hoverMarker(${item.id}, false)"
            >
                <div class="feed-card-thumb-wrap">
                    <img src="${item.image}" alt="${escapeHtml(item.title)}" class="feed-card-thumb" loading="lazy">
                    <span class="feed-card-badge ${isRent ? 'rent' : 'sale'}">
                        ${item.listing_type}
                    </span>
                    ${isUrgent ? `<span class="feed-card-urgency-badge">🔥 HOT</span>` : ''}
                </div>
                <div class="feed-card-info">
                    <div>
                        <div class="feed-card-price">${item.formatted_price}</div>
                        <h4 class="feed-card-title" title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</h4>
                        <div class="feed-card-location">
                            <svg style="width:11px;height:11px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span>${escapeHtml(item.district || item.province || 'Cambodia')}</span>
                        </div>
                    </div>
                    <div class="feed-card-specs">
                        ${item.area_sqm ? `<span>${item.area_sqm}m²</span>` : ''}
                        ${item.bedrooms ? `<span>${item.bedrooms} Beds</span>` : ''}
                        <span class="feed-card-source" style="margin-left:auto;">${item.source_name || 'Direct'}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = cardsHtml;
}

// Pan & Zoom Map to Property on Left Card Click
function panToProperty(id) {
    const marker = markersMap.get(id);
    if (!marker || !map) return;

    const latLng = marker.getLatLng();
    map.flyTo(latLng, 17, { animate: true, duration: 0.8 });

    setTimeout(() => {
        markerClusterGroup.zoomToShowLayer(marker, () => {
            marker.openPopup();
        });
    }, 300);

    highlightFeedCard(id);
}

function hoverMarker(id, isHovered) {
    const el = document.getElementById(`marker-pill-${id}`);
    if (el) {
        if (isHovered) el.classList.add('hovered');
        else el.classList.remove('hovered');
    }
}

function highlightFeedCard(id) {
    document.querySelectorAll('.property-feed-card').forEach(c => c.classList.remove('highlighted'));
    const target = document.getElementById(`feed-card-${id}`);
    if (target) {
        target.classList.add('highlighted');
        target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Toggle Left Panel to give Map Full Screen Width
function toggleLeftPanel() {
    const panel = document.getElementById('map-left-panel');
    const text = document.getElementById('toggle-panel-text');
    if (!panel) return;

    panel.classList.toggle('collapsed');
    const isCollapsed = panel.classList.contains('collapsed');
    if (text) {
        text.innerText = isCollapsed ? 'Show Listings' : 'Hide Listings';
    }

    setTimeout(() => {
        if (map) map.invalidateSize();
    }, 280);
}

// Toggle Theater Mode: Collapses portal navigation sidebar
function toggleTheaterMode() {
    const appLayout = document.getElementById('map-layout-root').closest('.app-layout');
    const text = document.getElementById('theater-text');
    if (!appLayout) return;

    appLayout.classList.toggle('map-theater-mode');
    const isTheater = appLayout.classList.contains('map-theater-mode');
    if (text) {
        text.innerText = isTheater ? 'Standard View' : 'Theater Mode';
    }

    setTimeout(() => {
        if (map) map.invalidateSize();
    }, 320);
}

function resetMapBounds() {
    panToLocation(11.5564, 104.9282, 13);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
@endpush
