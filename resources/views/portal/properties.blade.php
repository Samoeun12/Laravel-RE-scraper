@extends('layouts.portal')

@section('title', 'Real Estate Listings')

@push('styles')
<!-- Leaflet CSS for Modal Mini-Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Real Estate Listings</h1>
        <p class="page-subtitle">Harvested listings across Cambodia's leading real estate portals via live direct APIs ({{ number_format($properties->total()) }} total)</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <a href="{{ route('portal.map') }}" class="btn btn-secondary">
            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <span>Listings Map</span>
        </a>
        @if(Auth::user()->hasPermission('properties.create'))
            <button type="button" class="btn btn-primary" onclick="openModal('modal-add-property')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Record Property</span>
            </button>
        @endif
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form action="{{ route('portal.properties') }}" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div style="flex: 2; min-width: 220px;">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Search by title, district, province, or portal source..."
                >
            </div>

            <!-- Portal Source Filter -->
            <div style="flex: 1; min-width: 150px;">
                <select name="source" class="form-control" onchange="this.form.submit()">
                    <option value="">All Portals (7 Sources)</option>
                    <option value="bayon" {{ request('source') == 'bayon' ? 'selected' : '' }}>Bayon App (API)</option>
                    <option value="cambodia_re" {{ request('source') == 'cambodia_re' ? 'selected' : '' }}>Century 21 (C21 API)</option>
                    <option value="arc" {{ request('source') == 'arc' ? 'selected' : '' }}>ARC Cambodia (API)</option>
                    <option value="propnex" {{ request('source') == 'propnex' ? 'selected' : '' }}>PropNex Cambodia (API)</option>
                    <option value="realestate" {{ request('source') == 'realestate' ? 'selected' : '' }}>Realestate.com.kh (API)</option>
                    <option value="khmer24" {{ request('source') == 'khmer24' ? 'selected' : '' }}>Khmer24 Property</option>
                    <option value="harbor" {{ request('source') == 'harbor' ? 'selected' : '' }}>Harbor Property (API)</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 130px;">
                <select name="type" class="form-control" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="Land" {{ request('type') == 'Land' ? 'selected' : '' }}>Land</option>
                    <option value="Villa" {{ request('type') == 'Villa' ? 'selected' : '' }}>Villa</option>
                    <option value="Condo" {{ request('type') == 'Condo' ? 'selected' : '' }}>Condo</option>
                    <option value="House" {{ request('type') == 'House' ? 'selected' : '' }}>House</option>
                    <option value="Apartment" {{ request('type') == 'Apartment' ? 'selected' : '' }}>Apartment</option>
                    <option value="Commercial" {{ request('type') == 'Commercial' ? 'selected' : '' }}>Commercial</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 120px;">
                <select name="listing_type" class="form-control" onchange="this.form.submit()">
                    <option value="">All Markets</option>
                    <option value="Sale" {{ request('listing_type') == 'Sale' ? 'selected' : '' }}>For Sale</option>
                    <option value="Rent" {{ request('listing_type') == 'Rent' ? 'selected' : '' }}>For Rent</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 130px;">
                <select name="province" class="form-control" onchange="this.form.submit()">
                    <option value="">All Provinces</option>
                    <option value="Phnom Penh" {{ request('province') == 'Phnom Penh' ? 'selected' : '' }}>Phnom Penh</option>
                    <option value="Siem Reap" {{ request('province') == 'Siem Reap' ? 'selected' : '' }}>Siem Reap</option>
                    <option value="Sihanoukville" {{ request('province') == 'Sihanoukville' ? 'selected' : '' }}>Sihanoukville</option>
                    <option value="Kandal" {{ request('province') == 'Kandal' ? 'selected' : '' }}>Kandal</option>
                    <option value="Kampot" {{ request('province') == 'Kampot' ? 'selected' : '' }}>Kampot</option>
                </select>
            </div>

            <div style="flex: 0 0 110px;">
                <select name="per_page" class="form-control" onchange="this.form.submit()" title="Cards per page">
                    <option value="12" {{ request('per_page', 12) == 12 ? 'selected' : '' }}>12 / page</option>
                    <option value="24" {{ request('per_page') == 24 ? 'selected' : '' }}>24 / page</option>
                    <option value="36" {{ request('per_page') == 36 ? 'selected' : '' }}>36 / page</option>
                    <option value="48" {{ request('per_page') == 48 ? 'selected' : '' }}>48 / page</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.75rem 1.25rem;">Filter</button>
            @if(request()->hasAny(['search', 'source', 'type', 'listing_type', 'province', 'status']) || (request('per_page') && request('per_page') != 12))
                <a href="{{ route('portal.properties') }}" class="btn btn-secondary btn-sm" style="padding: 0.75rem 1rem;">Reset</a>
            @endif
        </form>
    </div>
</div>

<!-- Properties Grid -->
<div class="property-grid">
    @forelse($properties as $item)
        @php
            $propJson = [
                'id' => $item->id,
                'title' => $item->title,
                'source' => $item->source,
                'source_name' => $item->source_name,
                'source_id' => $item->source_id,
                'property_type' => $item->property_type,
                'listing_type' => $item->listing_type,
                'price' => $item->effective_price,
                'formatted_price' => $item->formatted_price,
                'price_per_sqm' => $item->computed_price_per_sqm,
                'formatted_price_per_sqm' => $item->computed_price_per_sqm ? '$' . number_format($item->computed_price_per_sqm, 0) . '/m²' : null,
                'area_sqm' => $item->area_sqm ? number_format($item->area_sqm, 1) : null,
                'bedrooms' => $item->bedrooms,
                'bathrooms' => $item->bathrooms,
                'location' => $item->display_location,
                'address' => $item->address,
                'commune' => $item->commune,
                'district' => $item->district,
                'province' => $item->province,
                'city' => $item->city,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'image_url' => $item->display_image_url,
                'images' => $item->gallery_images,
                'urgency_tag' => $item->urgency_tag,
                'status' => ucfirst($item->status ?? 'available'),
                'url' => $item->url ?: $item->source_url,
                'created_at' => $item->created_at ? $item->created_at->format('M d, Y') : null,
                'updated_at' => $item->updated_at ? $item->updated_at->format('M d, Y') : null,
            ];
        @endphp
        <div class="property-card" 
             id="property-card-{{ $item->id }}" 
             data-property="{{ json_encode($propJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}"
             onclick="openPropertyModalFromElement(this)"
             title="Click to view complete property information">
            <div class="property-thumb-wrapper">
                <img src="{{ $item->display_image_url }}" alt="{{ $item->title }}" class="property-thumb" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80'">
                
                <span class="property-tag-badge {{ strtolower($item->listing_type) === 'rent' ? 'badge-rent' : 'badge-sale' }}">
                    {{ $item->listing_type }} &bull; {{ $item->property_type }}
                </span>

                @if($item->urgency_tag)
                    <span style="position: absolute; top: 12px; right: 12px; background: var(--danger); color: #fff; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: var(--radius-full);">
                        {{ $item->urgency_tag }}
                    </span>
                @endif

                <span class="property-price-badge">{{ $item->formatted_price }}</span>

                <!-- Quick View Overlay Hint -->
                <div class="quick-view-badge">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Full Details</span>
                </div>
            </div>
            <div class="property-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.35rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                        {{ $item->source_name }}
                    </span>
                    @if($item->computed_price_per_sqm)
                        <span style="font-size: 0.775rem; font-weight: 800; color: var(--text-primary); background: var(--bg-surface-elevated); padding: 1px 6px; border-radius: var(--radius-sm);">
                            ${{ number_format($item->computed_price_per_sqm, 0) }}/m²
                        </span>
                    @endif
                </div>

                <h2 class="property-title" style="font-size: 1rem; line-height: 1.35; margin-bottom: 0.5rem;">
                    {{ $item->title }}
                </h2>
                
                <div class="property-location">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ $item->display_location }}</span>
                </div>

                <div class="property-specs">
                    <span class="spec-item">{{ $item->area_sqm ? number_format($item->area_sqm) . ' m²' : 'N/A' }}</span>
                    @if($item->bedrooms)
                        <span class="spec-item">{{ $item->bedrooms }} Beds</span>
                    @endif

                    <div style="margin-left: auto; display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.75rem; height: 26px;" onclick="event.stopPropagation(); openPropertyModalFromElement(this.closest('.property-card'))">
                            Details
                        </button>

                        @if($item->url || $item->source_url)
                            <a href="{{ $item->url ?: $item->source_url }}" target="_blank" rel="noopener noreferrer" style="color: var(--primary); font-weight: 600; font-size: 0.775rem; text-decoration: underline;" title="Open original portal listing in new tab" onclick="event.stopPropagation()">
                                Portal &nearr;
                            </a>
                        @endif

                        @if(Auth::user()->hasPermission('properties.delete'))
                            <form action="{{ route('portal.properties.delete', $item->id) }}" method="POST" style="margin: 0;" onsubmit="event.stopPropagation(); return confirm('Remove property?');" onclick="event.stopPropagation()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background: none; color: var(--danger); cursor: pointer; padding: 2px;" title="Delete Listing">
                                    <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div style="grid-column: 1/-1;" class="card">
            <div class="card-body" style="text-align: center; padding: 4rem 2rem;">
                <svg style="width:48px;height:48px;color:var(--text-muted);margin:0 auto 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">No properties match your filter criteria</h3>
                <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.35rem;">Try adjusting your keyword search or portal source.</p>
            </div>
        </div>
    @endforelse
</div>

<div style="margin-top: 2rem;">
    {{ $properties->links() }}
</div>
@endsection

@section('modals')
<!-- Modal: Comprehensive Property Listing Detail -->
<div class="modal-backdrop" id="modal-property-detail" onclick="if(event.target === this) closeModal('modal-property-detail')">
    <div class="modal-container modal-container-property" role="dialog" aria-modal="true" aria-labelledby="pm-title">
        
        <!-- Hero Visual Banner -->
        <div class="prop-modal-hero" onclick="openLightbox(event)" title="Click photo to view full-screen high resolution">
            <img src="" id="pm-hero-img" alt="Property Photo" class="prop-modal-hero-img" loading="eager" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80'">
            <div class="prop-modal-hero-gradient"></div>

            <!-- Floating Top Navigation -->
            <div class="prop-modal-top-bar" onclick="event.stopPropagation()">
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span id="pm-source-badge" class="prop-source-pill">
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span id="pm-source-name">Portal Source</span>
                    </span>

                    <span class="prop-status-live-pill">
                        <span class="prop-pulse-dot"></span>
                        <span id="pm-live-status-text">Available</span>
                    </span>

                    <span id="pm-id-badge" class="prop-id-copy-pill" onclick="copyCurrentPropertyId(event)" title="Click to copy listing ID">
                        ID: #0
                    </span>
                </div>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <!-- Fit / Cover Toggle -->
                    <button type="button" class="prop-modal-close-btn" id="pm-fit-btn" onclick="toggleHeroFit(event)" title="Toggle Fit Uncropped / Fill Cover">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>

                    <!-- Fullscreen Zoom Lightbox Button -->
                    <button type="button" class="prop-modal-close-btn" onclick="openLightbox(event)" title="Open Fullscreen Lightbox (Zoom)">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                        </svg>
                    </button>

                    <!-- Copy Share Link -->
                    <button type="button" class="prop-modal-close-btn" onclick="copyPropertyShareLink(event)" title="Copy Share Link">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </button>

                    <!-- Close Modal (Esc) -->
                    <button type="button" class="prop-modal-close-btn close-red" onclick="event.stopPropagation(); closeModal('modal-property-detail')" title="Close Modal (Esc)">
                        <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Photo Counter Badge -->
            <div class="prop-photo-counter-badge" id="pm-photo-counter" style="display: none;">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span id="pm-photo-counter-text">1 / 1</span>
            </div>

            <!-- Carousel Navigation Arrows -->
            <button type="button" class="prop-carousel-btn prev" id="pm-carousel-prev" onclick="prevModalPhoto(event)" style="display: none;" title="Previous Photo (Left Arrow)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button type="button" class="prop-carousel-btn next" id="pm-carousel-next" onclick="nextModalPhoto(event)" style="display: none;" title="Next Photo (Right Arrow)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Quick Hero Action Pills -->
            <div class="prop-hero-action-pill">
                <button type="button" class="prop-hero-pill-btn" id="pm-all-photos-btn" onclick="openAllPhotosGrid(event)" style="display: none;" title="View all photos as grid">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span id="pm-all-photos-text">All Photos</span>
                </button>
                <button type="button" class="prop-hero-pill-btn" onclick="openLightbox(event)" title="Click to view full photo">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                    </svg>
                    <span>Click to Zoom</span>
                </button>
            </div>

            <!-- Bottom Floating Badges & Pricing -->
            <div class="prop-modal-bottom-badges" onclick="event.stopPropagation()">
                <div class="prop-modal-price-tag">
                    <span class="prop-modal-price-val" id="pm-price">$0</span>
                    <span id="pm-sqm-rate" class="prop-modal-sqm-tag" style="display: none;">$0/m²</span>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span id="pm-urgency-badge" style="display: none; background: var(--danger); color: #fff; font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: var(--radius-full); box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);">
                        🔥 Distressed Deal
                    </span>
                    <span id="pm-market-type-badge" style="background: rgba(37, 99, 235, 0.85); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-full);">
                        For Sale
                    </span>
                    <span id="pm-category-badge" style="background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(255, 255, 255, 0.25); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-full);">
                        Condo
                    </span>
                </div>
            </div>
        </div>

        <!-- Thumbnails Strip -->
        <div class="prop-thumbnails-strip" id="pm-thumbnails-strip" style="display: none;"></div>

        <!-- Scrollable Modal Body -->
        <div class="prop-modal-scrollable">
            <!-- Property Title & Headline -->
            <div class="prop-modal-header-section">
                <h2 id="pm-title" class="prop-modal-title">Property Title</h2>
                <div class="prop-modal-loc">
                    <svg style="width:18px;height:18px;color:var(--primary);flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span id="pm-location-text" style="font-weight: 600;">Location Details</span>
                    <a href="#" id="pm-loc-jump-map" class="loc-jump-btn" title="View location on GIS Map" onclick="event.preventDefault(); switchPropertyModalTab('location');">
                        View Map &rarr;
                    </a>
                </div>
            </div>

            <!-- Key Metric Strip (4 Cards with Color-Coded Gradient Icons) -->
            <div class="prop-metrics-strip">
                <div class="prop-metric-box">
                    <div class="prop-metric-icon icon-price">
                        <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="prop-metric-content">
                        <span class="label">Asking Price</span>
                        <span class="value" id="pm-card-price">$0</span>
                    </div>
                </div>

                <div class="prop-metric-box">
                    <div class="prop-metric-icon icon-area">
                        <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </div>
                    <div class="prop-metric-content">
                        <span class="label">Total Area</span>
                        <span class="value" id="pm-card-area">N/A</span>
                    </div>
                </div>

                <div class="prop-metric-box">
                    <div class="prop-metric-icon icon-rooms">
                        <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div class="prop-metric-content">
                        <span class="label">Bed & Bath</span>
                        <span class="value" id="pm-card-rooms">N/A</span>
                    </div>
                </div>

                <div class="prop-metric-box">
                    <div class="prop-metric-icon icon-rate">
                        <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="prop-metric-content">
                        <span class="label">Unit Valuation</span>
                        <span class="value" id="pm-card-sqm">N/A</span>
                    </div>
                </div>
            </div>

            <!-- Segmented Interactive Navigation Tabs -->
            <div class="prop-modal-tabs">
                <button type="button" class="prop-tab-btn active" data-tab="specs" onclick="switchPropertyModalTab('specs')">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Specifications & Details</span>
                </button>
                <button type="button" class="prop-tab-btn" data-tab="location" onclick="switchPropertyModalTab('location')">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    <span>Location & GIS Map</span>
                </button>
                <button type="button" class="prop-tab-btn" data-tab="finance" onclick="switchPropertyModalTab('finance')">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span>Investment & Financial Insights</span>
                </button>
            </div>

            <!-- Tab 1: Specifications & Living Attributes -->
            <div id="prop-tab-specs" class="prop-tab-pane active">
                <div class="prop-details-grid">
                    <!-- Column 1: Core Physical Specifications -->
                    <div class="prop-info-card">
                        <div class="prop-info-card-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width:18px;height:18px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>Living Space & Architectural Details</span>
                            </div>
                        </div>

                        <div class="prop-key-value-row">
                            <span class="k">Property Category</span>
                            <span class="v" id="pm-spec-category">Condo</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Listing Market</span>
                            <span class="v" id="pm-spec-market">For Sale</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Bedrooms</span>
                            <span class="v" id="pm-spec-beds">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Bathrooms</span>
                            <span class="v" id="pm-spec-baths">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Total Floor Area</span>
                            <span class="v" id="pm-spec-area">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Calculated Rate</span>
                            <span class="v" id="pm-spec-rate">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Market Urgency</span>
                            <span class="v" id="pm-spec-urgency" style="color: var(--primary);">Standard Listing</span>
                        </div>
                    </div>

                    <!-- Column 2: Data Provenance & Verification -->
                    <div class="prop-info-card">
                        <div class="prop-info-card-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width:18px;height:18px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Platform Provenance & System Status</span>
                            </div>
                            <span id="pm-status-pill" class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 700;">Available</span>
                        </div>

                        <div class="prop-key-value-row">
                            <span class="k">Origin Real Estate Portal</span>
                            <span class="v" id="pm-spec-source">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Portal Ingestion ID</span>
                            <span class="v font-mono" id="pm-spec-source-id" style="color: var(--text-secondary); font-size: 0.8rem;">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">First Ingested Date</span>
                            <span class="v" id="pm-spec-created">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">System Record ID</span>
                            <span class="v font-mono" id="pm-spec-internal-id" style="color: var(--primary); font-size: 0.825rem;">-</span>
                        </div>
                        <div class="prop-key-value-row" style="border-top: 1px dashed var(--border-color); padding-top: 0.6rem; margin-top: 0.35rem;">
                            <span class="k">Direct Portal Reference</span>
                            <span class="v">
                                <a id="pm-spec-raw-link" href="#" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: underline; font-size: 0.825rem;">
                                    View Source Page &nearr;
                                </a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Geographical Location & Mini Map -->
            <div id="prop-tab-location" class="prop-tab-pane">
                <div class="prop-details-grid">
                    <!-- Left: Location Hierarchy -->
                    <div class="prop-info-card">
                        <div class="prop-info-card-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width:18px;height:18px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Administrative Location</span>
                            </div>
                        </div>

                        <div class="prop-key-value-row">
                            <span class="k">Province / Capital City</span>
                            <span class="v" id="pm-loc-city">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">District / Khan</span>
                            <span class="v" id="pm-loc-district">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Commune / Sangkat</span>
                            <span class="v" id="pm-loc-commune">-</span>
                        </div>
                        <div class="prop-key-value-row">
                            <span class="k">Street / Neighborhood</span>
                            <span class="v" id="pm-loc-address" style="max-width: 60%; word-break: break-word;">-</span>
                        </div>
                        <div class="prop-key-value-row" style="border-top: 1px dashed var(--border-color); padding-top: 0.65rem;">
                            <span class="k">GIS GPS Coordinates</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="v font-mono" id="pm-loc-coords" style="font-size: 0.8rem; color: var(--primary);">-</span>
                                <button type="button" id="pm-copy-coords-btn" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 0.725rem;" onclick="copyPropertyCoordinates()" title="Copy Coordinates">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Leaflet Interactive Mini Map -->
                    <div class="prop-info-card">
                        <div class="prop-info-card-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <svg style="width:18px;height:18px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <span>Interactive Map Preview</span>
                            </div>
                            <a id="pm-gis-mini-link" href="{{ route('portal.map') }}" target="_blank" style="font-size: 0.775rem; color: var(--primary); font-weight: 700; text-decoration: underline;">
                                Full GIS View &nearr;
                            </a>
                        </div>

                        <!-- Mini Leaflet Map Container -->
                        <div id="pm-mini-map" class="prop-mini-map-box"></div>
                        <div id="pm-mini-map-empty" style="display: none; height: 190px; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 1.5rem; border-radius: 12px; background: var(--bg-surface-elevated); border: 1px dashed var(--border-color);">
                            <div>
                                <svg style="width:32px;height:32px;margin:0 auto 0.5rem;opacity:0.6;color:var(--text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <div style="font-weight: 600; color: var(--text-secondary);">Exact GPS coordinates not published</div>
                                <div style="font-size: 0.775rem; margin-top: 4px;">Searchable via district & commune boundaries.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Investment & Financial Insights -->
            <div id="prop-tab-finance" class="prop-tab-pane">
                <div class="prop-financial-grid">
                    <!-- Mortgage Card -->
                    <div class="prop-finance-card">
                        <div class="prop-finance-header">
                            <svg style="width:18px;height:18px;color:#10b981;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                            </svg>
                            <span>Estimated Mortgage</span>
                        </div>
                        <div class="prop-finance-val" id="pm-finance-mortgage" style="color: #10b981;">$0 /mo</div>
                        <div class="prop-finance-sub" id="pm-finance-mortgage-sub">Standard 20-yr amortized @ 7% APR, 20% down.</div>
                    </div>

                    <!-- Gross Rental Yield Card -->
                    <div class="prop-finance-card">
                        <div class="prop-finance-header">
                            <svg style="width:18px;height:18px;color:#3b82f6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Gross Rental Yield</span>
                        </div>
                        <div class="prop-finance-val" id="pm-finance-yield" style="color: #3b82f6;">7.2% /yr</div>
                        <div class="prop-finance-sub" id="pm-finance-yield-sub">Benchmark based on prime urban residential yields.</div>
                    </div>

                    <!-- Unit Valuation Benchmark Card -->
                    <div class="prop-finance-card">
                        <div class="prop-finance-header">
                            <svg style="width:18px;height:18px;color:#f59e0b;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span>Unit Rate Benchmark</span>
                        </div>
                        <div class="prop-finance-val" id="pm-finance-benchmark" style="color: #f59e0b;">$0 /m²</div>
                        <div class="prop-finance-sub" id="pm-finance-benchmark-sub">Calculated market assessment for this category.</div>
                    </div>
                </div>

                <!-- Financial Advisory Note -->
                <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 12px; padding: 1rem 1.25rem; display: flex; gap: 12px; align-items: flex-start;">
                    <svg style="width:22px;height:22px;color:var(--primary);flex-shrink:0;margin-top:2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div style="font-size: 0.825rem; color: var(--text-secondary); line-height: 1.5;">
                        <strong style="color: var(--text-primary); display: block; margin-bottom: 2px;">Algorithmic Valuation & Investment Indicator</strong>
                        Calculations represent automated market estimates derived from Cambodia real estate portal aggregations and standard commercial bank underwriting assumptions. For formal loan approvals, consult partner institutions.
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Frosted Modal Footer with Direct Actions -->
        <div class="prop-modal-footer-bar">
            <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyFullPropertySummary()" title="Copy all listing specs to clipboard">
                    <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    <span>Copy Listing Info</span>
                </button>
                <a id="pm-gis-map-link" href="{{ route('portal.map') }}" class="btn btn-secondary btn-sm" title="Locate on full Cambodia GIS Map">
                    <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span>View in GIS Map</span>
                </a>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal-property-detail')">
                    Close
                </button>
                <a id="pm-portal-link" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">
                    <span>Open Original Portal</span>
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>
        </div>

    </div>
</div>

@if(Auth::user()->hasPermission('properties.create'))
<!-- Modal: Add Property -->
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
                        <input type="number" name="bedrooms" value="2" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" name="bathrooms" value="2" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Neighborhood / District</label>
                        <input type="text" name="location" class="form-control" placeholder="BKK1, Chamkarmon" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Province / City</label>
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
@endif

<!-- Modal: Full-Screen Image Lightbox -->
<div class="lightbox-backdrop" id="modal-image-lightbox" onclick="if(event.target === this) closeLightbox()">
    <div class="lightbox-container">
        <!-- Lightbox Top Controls -->
        <div class="lightbox-top-bar">
            <div class="lightbox-counter" id="lb-counter">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span id="lb-counter-text">Photo 1 of 1</span>
            </div>

            <div class="lightbox-actions">
                <button type="button" class="lightbox-btn" onclick="toggleLightboxZoom(event)" id="lb-zoom-btn" title="Toggle Zoom In/Out">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                    </svg>
                    <span id="lb-zoom-text">Zoom</span>
                </button>
                <button type="button" class="lightbox-btn" onclick="openAllPhotosGrid(event)" title="View all photos as grid">
                    <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>All Photos</span>
                </button>
                <button type="button" class="lightbox-btn close" onclick="closeLightbox()" title="Close Lightbox (Esc)">
                    <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Main Lightbox Stage -->
        <div class="lightbox-stage">
            <button type="button" class="lightbox-nav-btn prev" id="lb-prev-btn" onclick="prevLightboxPhoto(event)" title="Previous Photo (Left Arrow)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="lightbox-img-wrapper" onclick="toggleLightboxZoom(event)" title="Click to toggle 150% zoom">
                <img src="" id="lb-main-img" alt="Property Photo Fullscreen" class="lightbox-img">
            </div>

            <button type="button" class="lightbox-nav-btn next" id="lb-next-btn" onclick="nextLightboxPhoto(event)" title="Next Photo (Right Arrow)">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Lightbox Thumbnails Strip -->
        <div class="lightbox-thumbnails" id="lb-thumbnails"></div>
    </div>
</div>

<!-- Modal: All Photos Visual Grid -->
<div class="modal-backdrop" id="modal-all-photos-grid" onclick="if(event.target === this) closeModal('modal-all-photos-grid')">
    <div class="modal-container modal-container-photos-grid" role="dialog" aria-modal="true" aria-labelledby="photos-grid-title">
        <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="modal-title" id="photos-grid-title" style="margin: 0; font-size: 1.15rem; font-weight: 700;">Property Photo Gallery</h3>
                <span class="badge" id="photos-grid-count-badge" style="background: var(--bg-surface-elevated); color: var(--primary); font-weight: 700;">0 Photos</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modal-all-photos-grid')">&times;</button>
        </div>
        <div class="photos-grid-body" id="photos-grid-content"></div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Leaflet JS for mini map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
let currentModalProperty = null;
let miniMapInstance = null;
let miniMapMarker = null;
let modalImagesList = [];
let currentPhotoIndex = 0;
let isHeroFitContain = false;
let isLightboxZoomed = false;

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function toggleHeroFit(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    const heroImg = document.getElementById('pm-hero-img');
    const fitBtn = document.getElementById('pm-fit-btn');
    if (!heroImg) return;
    isHeroFitContain = !isHeroFitContain;
    heroImg.classList.toggle('fit-contain', isHeroFitContain);
    if (fitBtn) {
        fitBtn.title = isHeroFitContain ? 'Switch to Fill (Cover)' : 'Switch to Fit (Uncropped)';
        fitBtn.style.color = isHeroFitContain ? 'var(--primary)' : '#ffffff';
        fitBtn.style.borderColor = isHeroFitContain ? 'var(--primary)' : 'rgba(255, 255, 255, 0.18)';
    }
}

function openLightbox(e, index) {
    if (e && e.stopPropagation) e.stopPropagation();
    // Guard against clicks that bubbled up from interactive controls (e.g. close button, badges, toolbar)
    if (e && e.target && e.target.closest('button, .prop-modal-top-bar, .prop-modal-bottom-badges, .prop-carousel-btn')) {
        return;
    }
    if (typeof index === 'number' && index >= 0 && index < modalImagesList.length) {
        currentPhotoIndex = index;
    }
    updateLightboxDisplay();
    const lb = document.getElementById('modal-image-lightbox');
    if (lb) {
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    const lb = document.getElementById('modal-image-lightbox');
    if (lb) lb.classList.remove('open');
    isLightboxZoomed = false;
    const lbImg = document.getElementById('lb-main-img');
    if (lbImg) lbImg.classList.remove('zoomed');
    const zoomText = document.getElementById('lb-zoom-text');
    if (zoomText) zoomText.textContent = 'Zoom';

    const propModal = document.getElementById('modal-property-detail');
    if (propModal && propModal.classList.contains('open')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

function nextLightboxPhoto(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    setModalPhotoIndex(currentPhotoIndex + 1);
    updateLightboxDisplay();
}

function prevLightboxPhoto(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    setModalPhotoIndex(currentPhotoIndex - 1);
    updateLightboxDisplay();
}

function toggleLightboxZoom(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    isLightboxZoomed = !isLightboxZoomed;
    const lbImg = document.getElementById('lb-main-img');
    const zoomText = document.getElementById('lb-zoom-text');
    if (lbImg) lbImg.classList.toggle('zoomed', isLightboxZoomed);
    if (zoomText) zoomText.textContent = isLightboxZoomed ? 'Reset' : 'Zoom';
}

function updateLightboxDisplay() {
    if (modalImagesList.length === 0) return;
    const lbImg = document.getElementById('lb-main-img');
    const targetUrl = modalImagesList[currentPhotoIndex];
    if (lbImg && targetUrl) {
        lbImg.src = targetUrl;
    }
    const counterText = document.getElementById('lb-counter-text');
    if (counterText) {
        counterText.textContent = `Photo ${currentPhotoIndex + 1} of ${modalImagesList.length}`;
    }
    const prevBtn = document.getElementById('lb-prev-btn');
    const nextBtn = document.getElementById('lb-next-btn');
    const thumbs = document.getElementById('lb-thumbnails');
    if (prevBtn) prevBtn.style.display = modalImagesList.length > 1 ? 'flex' : 'none';
    if (nextBtn) nextBtn.style.display = modalImagesList.length > 1 ? 'flex' : 'none';
    if (thumbs) {
        thumbs.style.display = modalImagesList.length > 1 ? 'flex' : 'none';
        const items = thumbs.querySelectorAll('.lb-thumb-item');
        items.forEach((item, idx) => {
            if (idx === currentPhotoIndex) {
                item.classList.add('active');
                item.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }
}

function openAllPhotosGrid(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    const gridContent = document.getElementById('photos-grid-content');
    const badge = document.getElementById('photos-grid-count-badge');
    if (badge) badge.textContent = `${modalImagesList.length} Photos`;
    if (gridContent) {
        gridContent.innerHTML = modalImagesList.map((url, idx) => `
            <div class="grid-photo-card" onclick="selectPhotoFromGrid(${idx})" title="Click to view photo ${idx + 1}">
                <img src="${escapeHtml(url)}" alt="Photo ${idx + 1}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80'">
                <span class="photo-badge">${idx + 1} / ${modalImagesList.length}</span>
            </div>
        `).join('');
    }
    openModal('modal-all-photos-grid');
}

function selectPhotoFromGrid(idx) {
    setModalPhotoIndex(idx);
    closeModal('modal-all-photos-grid');
    openLightbox(null, idx);
}

function renderGallery(images, fallbackUrl) {
    modalImagesList = [];
    if (typeof images === 'string') {
        try {
            images = JSON.parse(images);
        } catch (e) {
            images = [images];
        }
    }
    if (Array.isArray(images) && images.length > 0) {
        modalImagesList = images.filter(url => typeof url === 'string' && url.trim().length > 0);
    }
    if (modalImagesList.length === 0 && fallbackUrl) {
        modalImagesList = [fallbackUrl];
    }
    if (modalImagesList.length === 0) {
        modalImagesList = ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80'];
    }

    currentPhotoIndex = 0;
    updateModalPhotoDisplay();

    const strip = document.getElementById('pm-thumbnails-strip');
    const prevBtn = document.getElementById('pm-carousel-prev');
    const nextBtn = document.getElementById('pm-carousel-next');
    const counterBadge = document.getElementById('pm-photo-counter');
    const allPhotosBtn = document.getElementById('pm-all-photos-btn');
    const allPhotosText = document.getElementById('pm-all-photos-text');
    const lbThumbs = document.getElementById('lb-thumbnails');

    if (modalImagesList.length > 1) {
        if (prevBtn) prevBtn.style.display = 'flex';
        if (nextBtn) nextBtn.style.display = 'flex';
        if (counterBadge) counterBadge.style.display = 'inline-flex';
        if (allPhotosBtn && allPhotosText) {
            allPhotosBtn.style.display = 'inline-flex';
            allPhotosText.textContent = `All Photos (${modalImagesList.length})`;
        }
        if (strip) {
            strip.style.display = 'flex';
            strip.innerHTML = modalImagesList.map((url, idx) => `
                <div class="prop-thumb-item ${idx === 0 ? 'active' : ''}" data-idx="${idx}" onclick="setModalPhotoIndex(${idx})" title="View photo ${idx + 1}">
                    <img src="${escapeHtml(url)}" alt="Thumbnail ${idx + 1}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=300&q=80'">
                </div>
            `).join('');
        }
        if (lbThumbs) {
            lbThumbs.innerHTML = modalImagesList.map((url, idx) => `
                <div class="lb-thumb-item ${idx === 0 ? 'active' : ''}" data-idx="${idx}" onclick="openLightbox(event, ${idx})" title="View photo ${idx + 1}">
                    <img src="${escapeHtml(url)}" alt="Thumbnail ${idx + 1}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=300&q=80'">
                </div>
            `).join('');
        }
    } else {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        if (counterBadge) counterBadge.style.display = 'none';
        if (allPhotosBtn) allPhotosBtn.style.display = 'none';
        if (strip) {
            strip.style.display = 'none';
            strip.innerHTML = '';
        }
        if (lbThumbs) {
            lbThumbs.innerHTML = '';
        }
    }
}

function setModalPhotoIndex(index) {
    if (modalImagesList.length === 0) return;
    currentPhotoIndex = (index + modalImagesList.length) % modalImagesList.length;
    updateModalPhotoDisplay();
    const lb = document.getElementById('modal-image-lightbox');
    if (lb && lb.classList.contains('open')) {
        updateLightboxDisplay();
    }
}

function nextModalPhoto(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    setModalPhotoIndex(currentPhotoIndex + 1);
}

function prevModalPhoto(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    setModalPhotoIndex(currentPhotoIndex - 1);
}

function updateModalPhotoDisplay() {
    if (modalImagesList.length === 0) return;
    const heroImg = document.getElementById('pm-hero-img');
    const targetUrl = modalImagesList[currentPhotoIndex];
    if (heroImg && targetUrl) {
        heroImg.src = targetUrl;
    }
    
    const counterText = document.getElementById('pm-photo-counter-text');
    if (counterText) {
        counterText.textContent = `${currentPhotoIndex + 1} / ${modalImagesList.length}`;
    }

    const strip = document.getElementById('pm-thumbnails-strip');
    if (strip) {
        const thumbs = strip.querySelectorAll('.prop-thumb-item');
        thumbs.forEach((el, idx) => {
            if (idx === currentPhotoIndex) {
                el.classList.add('active');
                el.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } else {
                el.classList.remove('active');
            }
        });
    }
}

function openPropertyModalFromElement(cardElement) {
    if (!cardElement) return;
    try {
        const raw = cardElement.getAttribute('data-property');
        if (!raw) return;
        const prop = JSON.parse(raw);
        openPropertyModal(prop);
    } catch (e) {
        console.error('Error parsing property data', e);
    }
}

function switchPropertyModalTab(tabId) {
    const tabs = document.querySelectorAll('.prop-tab-btn');
    const panes = document.querySelectorAll('.prop-tab-pane');
    
    tabs.forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });
    
    panes.forEach(pane => {
        pane.classList.toggle('active', pane.id === 'prop-tab-' + tabId);
    });
    
    if (tabId === 'location') {
        if (miniMapInstance) {
            setTimeout(() => {
                miniMapInstance.invalidateSize();
            }, 120);
        }
    }
}

function copyCurrentPropertyId(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (!currentModalProperty) return;
    const text = `#${currentModalProperty.id}`;
    navigator.clipboard.writeText(text).then(() => {
        showToast(`Listing ID copied: ${text}`, 'success');
    });
}

function copyPropertyCoordinates() {
    if (!currentModalProperty || !currentModalProperty.latitude || !currentModalProperty.longitude) {
        showToast('Coordinates not available for this listing', 'danger');
        return;
    }
    const coords = `${Number(currentModalProperty.latitude).toFixed(6)}, ${Number(currentModalProperty.longitude).toFixed(6)}`;
    navigator.clipboard.writeText(coords).then(() => {
        showToast(`GPS Coordinates copied: ${coords}`, 'success');
    });
}

function openPropertyModal(prop) {
    if (!prop) return;
    currentModalProperty = prop;

    // Reset to first tab (Specifications)
    switchPropertyModalTab('specs');

    // 1. Text & Headings
    document.getElementById('pm-title').textContent = prop.title || 'Property Listing';
    document.getElementById('pm-source-name').textContent = prop.source_name || 'Portal Source';
    document.getElementById('pm-id-badge').textContent = `ID: #${prop.id}${prop.source_id ? ' (' + prop.source_id + ')' : ''}`;
    document.getElementById('pm-location-text').textContent = prop.location || prop.address || 'Cambodia';
    
    const liveStatus = document.getElementById('pm-live-status-text');
    if (liveStatus) liveStatus.textContent = prop.status || 'Available';
    
    // 2. Pricing
    document.getElementById('pm-price').textContent = prop.formatted_price || 'Contact for Price';
    document.getElementById('pm-card-price').textContent = prop.formatted_price || 'Contact for Price';

    const sqmTag = document.getElementById('pm-sqm-rate');
    const cardSqm = document.getElementById('pm-card-sqm');
    const specRate = document.getElementById('pm-spec-rate');
    if (prop.formatted_price_per_sqm) {
        sqmTag.textContent = prop.formatted_price_per_sqm;
        sqmTag.style.display = 'inline-block';
        cardSqm.textContent = prop.formatted_price_per_sqm;
        specRate.textContent = prop.formatted_price_per_sqm;
    } else {
        sqmTag.style.display = 'none';
        cardSqm.textContent = 'N/A';
        specRate.textContent = 'N/A';
    }

    // 3. Badges
    document.getElementById('pm-market-type-badge').textContent = prop.listing_type || 'Listing';
    document.getElementById('pm-category-badge').textContent = prop.property_type || 'Property';

    const urgencyBadge = document.getElementById('pm-urgency-badge');
    const specUrgency = document.getElementById('pm-spec-urgency');
    if (prop.urgency_tag) {
        urgencyBadge.textContent = `🔥 ${prop.urgency_tag}`;
        urgencyBadge.style.display = 'inline-block';
        specUrgency.textContent = prop.urgency_tag;
        specUrgency.style.color = 'var(--danger)';
    } else {
        urgencyBadge.style.display = 'none';
        specUrgency.textContent = 'Standard Verified Listing';
        specUrgency.style.color = 'var(--text-secondary)';
    }

    // 4. Specs
    document.getElementById('pm-card-area').textContent = prop.area_sqm ? `${prop.area_sqm} m²` : 'N/A';
    document.getElementById('pm-spec-area').textContent = prop.area_sqm ? `${prop.area_sqm} m²` : 'N/A';

    let roomsStr = [];
    if (prop.bedrooms) roomsStr.push(`${prop.bedrooms} Beds`);
    if (prop.bathrooms) roomsStr.push(`${prop.bathrooms} Baths`);
    document.getElementById('pm-card-rooms').textContent = roomsStr.length > 0 ? roomsStr.join(' • ') : 'N/A';
    document.getElementById('pm-spec-beds').textContent = prop.bedrooms ? `${prop.bedrooms} Bedrooms` : 'N/A';
    document.getElementById('pm-spec-baths').textContent = prop.bathrooms ? `${prop.bathrooms} Bathrooms` : 'N/A';

    document.getElementById('pm-spec-category').textContent = prop.property_type || '-';
    document.getElementById('pm-spec-market').textContent = prop.listing_type || '-';
    document.getElementById('pm-spec-source').textContent = prop.source_name || '-';
    document.getElementById('pm-spec-created').textContent = prop.created_at || 'Recently indexed';
    document.getElementById('pm-status-pill').textContent = prop.status || 'Available';

    const specInternalId = document.getElementById('pm-spec-internal-id');
    if (specInternalId) specInternalId.textContent = '#' + prop.id;

    const specSourceId = document.getElementById('pm-spec-source-id');
    if (specSourceId) specSourceId.textContent = prop.source_id || 'N/A';

    const rawLink = document.getElementById('pm-spec-raw-link');
    if (rawLink) {
        if (prop.url || prop.source_url) {
            rawLink.href = prop.url || prop.source_url;
            rawLink.style.display = 'inline-block';
        } else {
            rawLink.style.display = 'none';
        }
    }

    // 5. Hero Gallery & Multi-Photo Carousel
    renderGallery(prop.images, prop.image_url);

    // 6. Location breakdown
    document.getElementById('pm-loc-city').textContent = prop.province || prop.city || 'Phnom Penh';
    document.getElementById('pm-loc-district').textContent = prop.district || '-';
    document.getElementById('pm-loc-commune').textContent = prop.commune || '-';
    document.getElementById('pm-loc-address').textContent = prop.address || prop.location || '-';

    const coordsEl = document.getElementById('pm-loc-coords');
    const mapBox = document.getElementById('pm-mini-map');
    const mapEmpty = document.getElementById('pm-mini-map-empty');
    const gisMiniLink = document.getElementById('pm-gis-mini-link');

    const hasCoords = prop.latitude && prop.longitude && (prop.latitude > 9 && prop.latitude < 15.5) && (prop.longitude > 102 && prop.longitude < 108);
    if (hasCoords) {
        coordsEl.textContent = `${Number(prop.latitude).toFixed(6)}, ${Number(prop.longitude).toFixed(6)}`;
        mapBox.style.display = 'block';
        mapEmpty.style.display = 'none';

        // Update GIS map link with coordinate focus
        const mapUrl = `{{ route('portal.map') }}?search=${encodeURIComponent(prop.title || prop.district || '')}`;
        document.getElementById('pm-gis-map-link').href = mapUrl;
        if (gisMiniLink) gisMiniLink.href = mapUrl;

        // Initialize or update Leaflet Mini Map
        initMiniMap(prop.latitude, prop.longitude, prop.title, prop.formatted_price);
    } else {
        coordsEl.textContent = 'Approximate / Unspecified';
        mapBox.style.display = 'none';
        mapEmpty.style.display = 'flex';
        const searchUrl = `{{ route('portal.map') }}?search=${encodeURIComponent(prop.district || prop.province || '')}`;
        document.getElementById('pm-gis-map-link').href = searchUrl;
        if (gisMiniLink) gisMiniLink.href = searchUrl;
    }

    // 7. Investment & Financial Insights Intelligence
    const priceNum = Number(prop.price) || 0;
    const areaNum = Number(prop.area_sqm) || 0;
    const isRent = (prop.listing_type || '').toLowerCase().includes('rent');

    const mortgageEl = document.getElementById('pm-finance-mortgage');
    const mortgageSub = document.getElementById('pm-finance-mortgage-sub');
    const yieldEl = document.getElementById('pm-finance-yield');
    const yieldSub = document.getElementById('pm-finance-yield-sub');
    const benchmarkEl = document.getElementById('pm-finance-benchmark');
    const benchmarkSub = document.getElementById('pm-finance-benchmark-sub');

    if (isRent) {
        if (mortgageEl) mortgageEl.textContent = 'Rental Listing';
        if (mortgageSub) mortgageSub.textContent = 'Monthly lease rate: ' + (prop.formatted_price || '$' + priceNum);
        
        const annualRent = priceNum * 12;
        if (yieldEl) yieldEl.textContent = '$' + annualRent.toLocaleString() + ' /yr';
        if (yieldSub) yieldSub.textContent = 'Projected annual gross revenue';
    } else if (priceNum > 0) {
        // Standard 20-year mortgage, 7% APR, 20% down (loan = 80%)
        const principal = priceNum * 0.8;
        const monthlyRate = 0.07 / 12;
        const nPayments = 240;
        const monthlyPayment = Math.round(principal * (monthlyRate * Math.pow(1 + monthlyRate, nPayments)) / (Math.pow(1 + monthlyRate, nPayments) - 1));
        if (mortgageEl) mortgageEl.textContent = '$' + monthlyPayment.toLocaleString() + ' /mo';
        if (mortgageSub) mortgageSub.textContent = 'Est. 20-yr loan @ 7% APR, 20% down ($' + Math.round(priceNum * 0.2).toLocaleString() + ')';
        
        // Benchmark Rental Yield ~ 7.2% annual
        const estAnnualRent = Math.round(priceNum * 0.072);
        const estMonthlyRent = Math.round(estAnnualRent / 12);
        if (yieldEl) yieldEl.textContent = '7.2% (~$' + estMonthlyRent.toLocaleString() + '/mo)';
        if (yieldSub) yieldSub.textContent = 'Based on Phnom Penh prime residential gross yield';
    } else {
        if (mortgageEl) mortgageEl.textContent = 'Contact Agent';
        if (mortgageSub) mortgageSub.textContent = 'Price upon inquiry';
        if (yieldEl) yieldEl.textContent = 'Inquire';
        if (yieldSub) yieldSub.textContent = 'Yield subject to negotiated price';
    }

    // Valuation / $/m² benchmark
    if (prop.price_per_sqm && prop.price_per_sqm > 0) {
        const rate = Math.round(prop.price_per_sqm);
        if (benchmarkEl) benchmarkEl.textContent = '$' + rate.toLocaleString() + ' /m²';
        let statusText = 'Fair Market Value';
        if (rate < 1200) {
            statusText = '🔥 Below District Median (High Potential)';
        } else if (rate <= 2200) {
            statusText = '✅ Prime Competitive Market Range';
        } else {
            statusText = '💎 Luxury / Ultra-Prime Grade';
        }
        if (benchmarkSub) benchmarkSub.textContent = statusText;
    } else if (areaNum > 0 && priceNum > 0) {
        const computedRate = Math.round(priceNum / areaNum);
        if (benchmarkEl) benchmarkEl.textContent = '$' + computedRate.toLocaleString() + ' /m²';
        if (benchmarkSub) benchmarkSub.textContent = computedRate < 1500 ? '✅ Attractive $/m² unit rate' : '💎 Premium tier unit pricing';
    } else {
        if (benchmarkEl) benchmarkEl.textContent = 'N/A';
        if (benchmarkSub) benchmarkSub.textContent = 'Requires floor area specification';
    }

    // 8. External Portal Link
    const portalLink = document.getElementById('pm-portal-link');
    if (prop.url) {
        portalLink.href = prop.url;
        portalLink.style.display = 'inline-flex';
    } else {
        portalLink.style.display = 'none';
    }

    // 9. Open modal
    openModal('modal-property-detail');
}

function initMiniMap(lat, lng, title, price) {
    if (typeof L === 'undefined') return;

    setTimeout(() => {
        const container = document.getElementById('pm-mini-map');
        if (!container) return;

        if (!miniMapInstance) {
            miniMapInstance = L.map('pm-mini-map', {
                center: [lat, lng],
                zoom: 14,
                zoomControl: true,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(miniMapInstance);
        } else {
            miniMapInstance.setView([lat, lng], 14);
            miniMapInstance.invalidateSize();
        }

        if (miniMapMarker) {
            miniMapMarker.setLatLng([lat, lng]);
        } else {
            const icon = L.divIcon({
                className: 'custom-map-pin',
                html: `<div style="background:var(--primary);color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.3);border:2px solid #fff;"><svg style='width:16px;height:16px;' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z' clip-rule='evenodd'/></svg></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 28]
            });
            miniMapMarker = L.marker([lat, lng], { icon: icon }).addTo(miniMapInstance);
        }

        miniMapMarker.bindPopup(`<strong>${price}</strong><br><small>${title}</small>`);
        setTimeout(() => miniMapInstance.invalidateSize(), 200);
    }, 100);
}

function copyFullPropertySummary() {
    if (!currentModalProperty) return;
    const p = currentModalProperty;
    const summary = [
        `🏡 ${p.title}`,
        `💰 Price: ${p.formatted_price}` + (p.formatted_price_per_sqm ? ` (${p.formatted_price_per_sqm})` : ''),
        `📍 Location: ${p.location}`,
        p.area_sqm ? `📐 Area: ${p.area_sqm} m²` : null,
        (p.bedrooms || p.bathrooms) ? `🛏️ Rooms: ${p.bedrooms || 0} Beds / ${p.bathrooms || 0} Baths` : null,
        `🏷️ Type: ${p.property_type} (${p.listing_type})`,
        `🌐 Source: ${p.source_name}`,
        p.url ? `🔗 Original Link: ${p.url}` : null
    ].filter(Boolean).join('\n');

    navigator.clipboard.writeText(summary).then(() => {
        showToast('Full listing details copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Unable to copy to clipboard', 'danger');
    });
}

function copyPropertyShareLink(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (!currentModalProperty) return;
    const url = new URL(window.location.href);
    url.searchParams.set('property_id', currentModalProperty.id);
    navigator.clipboard.writeText(url.toString()).then(() => {
        showToast('Direct listing link copied to clipboard!', 'success');
    });
}

// Keyboard navigation for Lightbox, Photos Grid, and Property Detail Modal
document.addEventListener('keydown', (e) => {
    const lb = document.getElementById('modal-image-lightbox');
    const gridModal = document.getElementById('modal-all-photos-grid');
    const propModal = document.getElementById('modal-property-detail');

    if (lb && lb.classList.contains('open')) {
        if (e.key === 'ArrowRight') {
            nextLightboxPhoto();
        } else if (e.key === 'ArrowLeft') {
            prevLightboxPhoto();
        } else if (e.key === 'Escape') {
            closeLightbox();
        }
    } else if (gridModal && gridModal.classList.contains('open')) {
        if (e.key === 'Escape') {
            closeModal('modal-all-photos-grid');
        }
    } else if (propModal && propModal.classList.contains('open')) {
        if (e.key === 'ArrowRight') {
            nextModalPhoto();
        } else if (e.key === 'ArrowLeft') {
            prevModalPhoto();
        }
    }
});

// Horizontal mouse wheel scrolling for thumbnails
document.addEventListener('DOMContentLoaded', () => {
    const strip = document.getElementById('pm-thumbnails-strip');
    if (strip) {
        strip.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault();
                strip.scrollLeft += e.deltaY;
            }
        }, { passive: false });
    }
    const lbStrip = document.getElementById('lb-thumbnails');
    if (lbStrip) {
        lbStrip.addEventListener('wheel', (e) => {
            if (e.deltaY !== 0) {
                e.preventDefault();
                lbStrip.scrollLeft += e.deltaY;
            }
        }, { passive: false });
    }
});

// Deep linking auto-open
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const targetId = params.get('property_id') || params.get('view');
    if (targetId) {
        const card = document.getElementById(`property-card-${targetId}`);
        if (card) {
            openPropertyModalFromElement(card);
        } else {
            // Fetch via API endpoint
            fetch(`/portal/properties/${targetId}/details`)
                .then(res => res.json())
                .then(data => openPropertyModal(data))
                .catch(err => console.error('Error fetching property deep link:', err));
        }
    }
});
</script>
@endpush
