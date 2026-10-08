@extends('layouts.portal')

@section('title', 'Properties Catalog')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Real Estate Properties Directory</h1>
        <p class="page-subtitle">Harvested listings across Cambodia's leading real estate portals via live direct APIs ({{ number_format($properties->total()) }} total)</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modal-add-property')">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Record Property</span>
        </button>
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
                    <option value="">All Portals (6 Sources)</option>
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

            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.75rem 1.25rem;">Filter</button>
            @if(request()->hasAny(['search', 'source', 'type', 'listing_type', 'province', 'status']))
                <a href="{{ route('portal.properties') }}" class="btn btn-secondary btn-sm" style="padding: 0.75rem 1rem;">Reset</a>
            @endif
        </form>
    </div>
</div>

<!-- Properties Grid -->
<div class="property-grid">
    @forelse($properties as $item)
        <div class="property-card">
            <div class="property-thumb-wrapper">
                <img src="{{ $item->image_url ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80' }}" alt="{{ $item->title }}" class="property-thumb" loading="lazy">
                
                <span class="property-tag-badge {{ strtolower($item->listing_type) === 'rent' ? 'badge-rent' : 'badge-sale' }}">
                    {{ $item->listing_type }} &bull; {{ $item->property_type }}
                </span>

                @if($item->urgency_tag)
                    <span style="position: absolute; top: 12px; right: 12px; background: var(--danger); color: #fff; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: var(--radius-full);">
                        {{ $item->urgency_tag }}
                    </span>
                @endif

                <span class="property-price-badge">{{ $item->formatted_price }}</span>
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
                        @if($item->url)
                            <a href="{{ $item->url }}" target="_blank" rel="noopener" style="color: var(--primary); font-weight: 600; font-size: 0.775rem; text-decoration: underline;" title="Open original listing">
                                Portal &nearr;
                            </a>
                        @endif

                        <form action="{{ route('portal.properties.delete', $item->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Remove property?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: none; color: var(--danger); cursor: pointer; padding: 2px;" title="Delete Listing">
                                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
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
@endsection
