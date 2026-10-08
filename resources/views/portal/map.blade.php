@extends('layouts.portal')

@section('title', 'Listings Map')

@push('styles')
<!-- Leaflet & MarkerCluster CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
/* Map Page Layout */
.map-page-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: calc(100vh - 120px);
    position: relative;
}

.map-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

/* Filter Bar */
.map-filter-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 0.85rem 1.25rem;
    box-shadow: var(--shadow-sm);
}

.map-filter-form {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
}

/* Quick Jump Pills */
.quick-jump-bar {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    overflow-x: auto;
    padding-bottom: 2px;
}

.quick-jump-pill {
    padding: 4px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: var(--radius-full);
    background: var(--bg-surface-elevated);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    cursor: pointer;
    white-space: nowrap;
    transition: all var(--transition-fast);
}

.quick-jump-pill:hover, .quick-jump-pill.active {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
    transform: translateY(-1px);
}

/* Map & Listings Workspace */
.map-workspace {
    display: flex;
    flex: 1;
    position: relative;
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-md);
    background: var(--bg-surface);
}

#listings-google-map {
    flex: 1;
    height: 100%;
    min-height: 480px;
    width: 100%;
    z-index: 10;
}

/* Listings Side Panel */
.map-sidebar-panel {
    width: 380px;
    height: 100%;
    background: var(--bg-surface);
    border-left: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    z-index: 20;
    transition: transform var(--transition-normal);
}

.map-sidebar-panel.collapsed {
    transform: translateX(100%);
    position: absolute;
    right: 0;
    pointer-events: none;
}

.sidebar-panel-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-surface-elevated);
}

.sidebar-panel-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-cards-list {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

/* Mini Listing Card in Sidebar */
.mini-listing-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
    display: flex;
    gap: 0.85rem;
    padding: 0.65rem;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.mini-listing-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    background: var(--bg-surface-elevated);
}

.mini-listing-card.highlighted {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.35);
}

.mini-card-thumb {
    width: 90px;
    height: 80px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    flex-shrink: 0;
}

.mini-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}

.mini-card-price {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--primary);
}

.mini-card-title {
    font-size: 0.825rem;
    font-weight: 700;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 2px 0 4px;
}

.mini-card-meta {
    font-size: 0.725rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Custom Map Marker Pins (Zillow / Google Style) */
.price-pill-pin {
    background: #ffffff;
    color: #0f172a;
    font-weight: 800;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 9999px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
    border: 2px solid #10b981;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
}

.price-pill-pin:hover {
    transform: scale(1.12);
    z-index: 1000 !important;
}

.price-pill-pin.pin-rent {
    border-color: #2563eb;
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

/* Custom Marker Cluster Styles */
.marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
    background-color: rgba(59, 130, 246, 0.45) !important;
}
.marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
    background-color: #2563eb !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 12px !important;
}

/* Leaflet Popup Styling */
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
    width: 270px !important;
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

.map-popup-badge.rent {
    background: rgba(37, 99, 235, 0.9);
}

.map-popup-badge.sale {
    background: rgba(16, 185, 129, 0.9);
}

.map-popup-price {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(15, 23, 42, 0.85);
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
}

.map-popup-location {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 4px;
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

/* Floating Layer Switcher Pill */
.map-layer-floating-control {
    position: absolute;
    bottom: 24px;
    left: 24px;
    z-index: 1000;
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 6px 10px;
    box-shadow: var(--shadow-lg);
    display: flex;
    gap: 6px;
    align-items: center;
}

.map-layer-btn {
    border: none;
    background: transparent;
    color: var(--text-secondary);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.map-layer-btn.active, .map-layer-btn:hover {
    background: var(--primary);
    color: #ffffff;
}

/* Loading Overlay */
.map-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 1500;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: #ffffff;
    font-weight: 700;
    transition: opacity 0.2s ease;
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
    margin-bottom: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 900px) {
    .map-sidebar-panel {
        display: none;
    }
}
</style>
@endpush

@section('content')
<div class="map-page-container">
    <!-- Header Row -->
    <div class="map-header-row">
        <div>
            <h1 class="page-title" style="margin-bottom: 0.25rem;">Real Estate Listings Map</h1>
            <p class="page-subtitle">Full Google Map spatial visualization with {{ number_format($totalWithGps) }} verified properties across Cambodia</p>
        </div>

        <!-- Quick Jump Links -->
        <div class="quick-jump-bar">
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-right: 4px;">JUMP TO:</span>
            <button type="button" class="quick-jump-pill active" onclick="panToLocation(11.5564, 104.9282, 13, 'Phnom Penh')">📍 Phnom Penh</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(11.5732, 104.8988, 14, 'Toul Kork')">Toul Kork</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(11.5505, 104.9265, 15, 'BKK1')">BKK1</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(11.5830, 104.8624, 14, 'Sen Sok')">Sen Sok</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(13.3671, 103.8448, 13, 'Siem Reap')">Siem Reap</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(10.6275, 103.5221, 13, 'Sihanoukville')">Sihanoukville</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(10.6104, 104.1815, 13, 'Kampot')">Kampot</button>
            <button type="button" class="quick-jump-pill" onclick="panToLocation(12.5657, 104.9910, 8, 'Cambodia')">🇰🇭 Entire Cambodia</button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="map-filter-card">
        <form id="map-filter-form" class="map-filter-form" onsubmit="event.preventDefault(); loadMapProperties();">
            <!-- Search Text -->
            <div style="flex: 2; min-width: 220px;">
                <input 
                    type="text" 
                    id="filter-search" 
                    class="form-control form-control-sm" 
                    placeholder="Search titles, districts (e.g. Chamkarmon, Sen Sok)..."
                >
            </div>

            <!-- Market Type -->
            <div style="flex: 1; min-width: 130px;">
                <select id="filter-listing-type" class="form-control form-control-sm" onchange="loadMapProperties()">
                    <option value="">All Markets (Sale & Rent)</option>
                    <option value="Sale">For Sale</option>
                    <option value="Rent">For Rent</option>
                </select>
            </div>

            <!-- Property Type -->
            <div style="flex: 1; min-width: 130px;">
                <select id="filter-property-type" class="form-control form-control-sm" onchange="loadMapProperties()">
                    <option value="">All Property Types</option>
                    @foreach($propertyTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Province -->
            <div style="flex: 1; min-width: 140px;">
                <select id="filter-province" class="form-control form-control-sm" onchange="onProvinceSelect(this.value)">
                    <option value="">All Provinces</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov }}">{{ $prov }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Source Portal -->
            <div style="flex: 1; min-width: 140px;">
                <select id="filter-source" class="form-control form-control-sm" onchange="loadMapProperties()">
                    <option value="">All Portals (6 Sources)</option>
                    <option value="cambodia_re">Century 21 (C21)</option>
                    <option value="arc">ARC Cambodia</option>
                    <option value="realestate">Realestate.com.kh</option>
                    <option value="khmer24">Khmer24 Property</option>
                    <option value="harbor">Harbor Property</option>
                    <option value="propnex">PropNex Cambodia</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.5rem 1rem;">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Filter Map</span>
            </button>

            <button type="button" class="btn btn-secondary btn-sm" onclick="resetFilters()">Reset</button>

            <!-- Toggle Sidebar Cards Button -->
            <button type="button" class="btn btn-secondary btn-sm" style="margin-left: auto;" onclick="toggleSidebar()">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <span id="sidebar-toggle-text">Hide List</span>
            </button>
        </form>
    </div>

    <!-- Main Map Workspace -->
    <div class="map-workspace">
        <!-- Interactive Google Map -->
        <div id="listings-google-map"></div>

        <!-- Floating Google Map Tile Layer Switcher -->
        <div class="map-layer-floating-control">
            <span style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); margin-right: 2px;">BASEMAP:</span>
            <button type="button" class="map-layer-btn active" id="btn-layer-streets" onclick="setBasemap('streets')">🗺️ Google Roads</button>
            <button type="button" class="map-layer-btn" id="btn-layer-satellite" onclick="setBasemap('satellite')">🛰️ Google Satellite</button>
            <button type="button" class="map-layer-btn" id="btn-layer-hybrid" onclick="setBasemap('hybrid')">🌐 Google Hybrid</button>
            <button type="button" class="map-layer-btn" id="btn-layer-dark" onclick="setBasemap('dark')">🌙 Dark Mode</button>
        </div>

        <!-- Loading Overlay -->
        <div id="map-loading-overlay" class="map-loading-overlay">
            <div class="spinner"></div>
            <span>Plotting Cambodian listings on Google Map...</span>
        </div>

        <!-- Sidebar Listings Panel -->
        <aside class="map-sidebar-panel" id="map-sidebar-panel">
            <div class="sidebar-panel-header">
                <div class="sidebar-panel-title">
                    <svg style="width:16px;height:16px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Listings in View</span>
                </div>
                <span class="badge" style="background:var(--primary-light);color:var(--primary);font-size:0.75rem;" id="matching-counter">
                    0 plotted
                </span>
            </div>

            <!-- List of Cards -->
            <div class="sidebar-cards-list" id="sidebar-cards-list">
                <!-- Injected via JavaScript -->
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<!-- Leaflet & MarkerCluster JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// Map Instance & Layer Setup
let map = null;
let currentLayer = null;
let markerClusterGroup = null;
let propertiesData = [];
let markersMap = new Map(); // id -> marker

// Google Map Basemap Tile URLs
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
    // Google Hybrid (Satellite + Roads/Town labels)
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
    initMap();
    loadMapProperties();
});

function initMap() {
    // Center initially on Phnom Penh, Cambodia
    map = L.map('listings-google-map', {
        center: [11.5564, 104.9282],
        zoom: 13,
        zoomControl: false
    });

    // Custom Zoom Control at Top-Right
    L.control.zoom({ position: 'topright' }).addTo(map);

    // Initial Default Layer: Google Roadmap
    currentLayer = basemapLayers.streets;
    currentLayer.addTo(map);

    // Initialize Cluster Group with Smooth Animations
    markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 40,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        disableClusteringAtZoom: 18
    });
    map.addLayer(markerClusterGroup);
}

// Basemap Switcher
function setBasemap(type) {
    if (!map || !basemapLayers[type]) return;

    if (currentLayer) {
        map.removeLayer(currentLayer);
    }

    currentLayer = basemapLayers[type];
    currentLayer.addTo(map);

    // Update active button state
    document.querySelectorAll('.map-layer-btn').forEach(b => b.classList.remove('active'));
    const activeBtn = document.getElementById(`btn-layer-${type}`);
    if (activeBtn) activeBtn.classList.add('active');
}

// Quick Jump Location
function panToLocation(lat, lng, zoom, label) {
    if (!map) return;
    map.flyTo([lat, lng], zoom, {
        animate: true,
        duration: 1.2
    });

    document.querySelectorAll('.quick-jump-pill').forEach(p => p.classList.remove('active'));
    if (event && event.target) {
        event.target.classList.add('active');
    }
}

function onProvinceSelect(provinceName) {
    if (!provinceName) {
        loadMapProperties();
        return;
    }

    // Known center points for Cambodian provinces
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

    loadMapProperties();
}

// Fetch Filtered Properties and Render
function loadMapProperties() {
    const overlay = document.getElementById('map-loading-overlay');
    if (overlay) overlay.classList.add('active');

    const search = document.getElementById('filter-search').value.trim();
    const listingType = document.getElementById('filter-listing-type').value;
    const propertyType = document.getElementById('filter-property-type').value;
    const province = document.getElementById('filter-province').value;
    const source = document.getElementById('filter-source').value;

    const params = new URLSearchParams({
        limit: 1500
    });

    if (search) params.append('search', search);
    if (listingType) params.append('listing_type', listingType);
    if (propertyType) params.append('type', propertyType);
    if (province) params.append('province', province);
    if (source) params.append('source', source);

    fetch(`{{ route('portal.map.api') }}?${params.toString()}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.properties) {
                propertiesData = res.properties;
                renderMapMarkers(propertiesData);
                renderSidebarListings(propertiesData);

                const counter = document.getElementById('matching-counter');
                if (counter) {
                    counter.innerText = `${res.count.toLocaleString()} plotted`;
                }
            }
        })
        .catch(err => {
            console.error('Map Properties API Error:', err);
        })
        .finally(() => {
            if (overlay) overlay.classList.remove('active');
        });
}

function renderMapMarkers(properties) {
    if (!markerClusterGroup) return;

    markerClusterGroup.clearLayers();
    markersMap.clear();

    const bounds = L.latLngBounds();

    properties.forEach(item => {
        if (!item.lat || !item.lng) return;

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

        // Build Glassmorphism Popup
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
                        ${item.url ? `<a href="${item.url}" target="_blank" rel="noopener" class="map-popup-link">View Listing &nearr;</a>` : ''}
                    </div>
                </div>
            </div>
        `;

        marker.bindPopup(popupContent, { maxWidth: 280 });
        marker.on('click', () => {
            highlightSidebarCard(item.id);
        });

        markerClusterGroup.addLayer(marker);
        markersMap.set(item.id, marker);
        bounds.extend([item.lat, item.lng]);
    });
}

function renderSidebarListings(properties) {
    const container = document.getElementById('sidebar-cards-list');
    if (!container) return;

    if (!properties || properties.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                <p style="font-size: 0.875rem;">No properties matched your search.</p>
            </div>
        `;
        return;
    }

    const cardsHtml = properties.slice(0, 100).map(item => {
        const isRent = (item.listing_type || '').toLowerCase() === 'rent';
        return `
            <div class="mini-listing-card" id="sidebar-card-${item.id}" onclick="focusPropertyOnMap(${item.id})">
                <img src="${item.image}" alt="${escapeHtml(item.title)}" class="mini-card-thumb" loading="lazy">
                <div class="mini-card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="mini-card-price">${item.formatted_price}</span>
                        <span style="font-size:0.65rem;font-weight:800;padding:1px 6px;border-radius:4px;background:${isRent ? 'rgba(37,99,235,0.15)' : 'rgba(16,185,129,0.15)'};color:${isRent ? '#2563eb' : '#10b981'};">
                            ${item.listing_type}
                        </span>
                    </div>
                    <div class="mini-card-title" title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</div>
                    <div class="mini-card-meta">
                        <span>${escapeHtml(item.district || item.province || 'Cambodia')}</span>
                        ${item.area_sqm ? `&bull; <span>${item.area_sqm}m²</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = cardsHtml;
}

function focusPropertyOnMap(id) {
    const marker = markersMap.get(id);
    if (!marker || !map) return;

    const latLng = marker.getLatLng();
    map.flyTo(latLng, 17, { animate: true, duration: 1.0 });

    setTimeout(() => {
        markerClusterGroup.zoomToShowLayer(marker, () => {
            marker.openPopup();
        });
    }, 400);

    highlightSidebarCard(id);
}

function highlightSidebarCard(id) {
    document.querySelectorAll('.mini-listing-card').forEach(c => c.classList.remove('highlighted'));
    const target = document.getElementById(`sidebar-card-${id}`);
    if (target) {
        target.classList.add('highlighted');
        target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function resetFilters() {
    document.getElementById('map-filter-form').reset();
    panToLocation(11.5564, 104.9282, 13, 'Phnom Penh');
    loadMapProperties();
}

function toggleSidebar() {
    const sidebar = document.getElementById('map-sidebar-panel');
    const toggleText = document.getElementById('sidebar-toggle-text');
    if (!sidebar) return;

    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    if (toggleText) {
        toggleText.innerText = isCollapsed ? 'Show List' : 'Hide List';
    }

    setTimeout(() => {
        if (map) map.invalidateSize();
    }, 250);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
@endpush
