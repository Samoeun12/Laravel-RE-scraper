@extends('layouts.portal')

@section('title', 'Deal & Good Property Finder')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Deal & "Good Property" Finder</h1>
        <p class="page-subtitle">Algorithmic detection of underpriced real estate, urgent sales, and distressed assets across Cambodia</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form action="{{ route('portal.deals') }}" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 160px;">
                <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 4px;">PROVINCE</label>
                <select name="province" class="form-control" onchange="this.form.submit()">
                    <option value="All" {{ $province == 'All' ? 'selected' : '' }}>All Provinces</option>
                    <option value="Phnom Penh" {{ $province == 'Phnom Penh' ? 'selected' : '' }}>Phnom Penh</option>
                    <option value="Siem Reap" {{ $province == 'Siem Reap' ? 'selected' : '' }}>Siem Reap</option>
                    <option value="Sihanoukville" {{ $province == 'Sihanoukville' ? 'selected' : '' }}>Sihanoukville / Preah Sihanouk</option>
                    <option value="Kandal" {{ $province == 'Kandal' ? 'selected' : '' }}>Kandal</option>
                    <option value="Kampot" {{ $province == 'Kampot' ? 'selected' : '' }}>Kampot</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 4px;">PROPERTY TYPE</label>
                <select name="type" class="form-control" onchange="this.form.submit()">
                    <option value="All" {{ $propType == 'All' ? 'selected' : '' }}>All Types</option>
                    <option value="Land" {{ $propType == 'Land' ? 'selected' : '' }}>Land</option>
                    <option value="Villa" {{ $propType == 'Villa' ? 'selected' : '' }}>Villa</option>
                    <option value="House" {{ $propType == 'House' ? 'selected' : '' }}>House / Shophouse</option>
                    <option value="Condo" {{ $propType == 'Condo' ? 'selected' : '' }}>Condo</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 130px;">
                <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 4px;">MARKET</label>
                <select name="listing_type" class="form-control" onchange="this.form.submit()">
                    <option value="Sale" {{ $listingType == 'Sale' ? 'selected' : '' }}>For Sale</option>
                    <option value="Rent" {{ $listingType == 'Rent' ? 'selected' : '' }}>For Rent</option>
                </select>
            </div>

            <div style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.25rem;">Scan Market</button>
            </div>
        </form>
    </div>
</div>

<!-- District Benchmark Index Pills -->
@if($districtAverages->isNotEmpty())
    <div style="margin-bottom: 1.5rem;">
        <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem;">
            District Benchmark Averages (${{ $listingType == 'Sale' ? '/m²' : '/mo' }})
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @foreach($districtAverages->take(8) as $dist => $avg)
                <div style="padding: 4px 10px; border-radius: var(--radius-sm); background: var(--bg-surface-elevated); border: 1px solid var(--border-color); font-size: 0.775rem;">
                    <strong>{{ $dist }}:</strong> ${{ number_format($avg, 0) }}/m²
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Deals Grid -->
<div class="property-grid">
    @forelse($deals as $item)
        <div class="property-card" style="border: 2px solid rgba(239, 68, 68, 0.35);">
            <div class="property-thumb-wrapper">
                <img src="{{ $item->image_url ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80' }}" alt="{{ $item->title }}" class="property-thumb" loading="lazy">
                
                @if($item->urgency_tag)
                    <span class="property-tag-badge" style="background: var(--danger); color: #fff; font-weight: 800;">
                        🔥 {{ $item->urgency_tag }}
                    </span>
                @else
                    <span class="property-tag-badge" style="background: var(--success); color: #fff; font-weight: 800;">
                        💎 Below Market Deal
                    </span>
                @endif

                <span class="property-price-badge">{{ $item->formatted_price }}</span>
            </div>
            <div class="property-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                        {{ $item->source_name }}
                    </span>
                    @if($item->price_per_sqm > 0)
                        <span style="font-size: 0.825rem; font-weight: 800; color: var(--text-primary); background: var(--bg-surface-elevated); padding: 2px 6px; border-radius: var(--radius-sm);">
                            ${{ number_format($item->price_per_sqm, 0) }}/m²
                        </span>
                    @endif
                </div>

                <h2 class="property-title">{{ $item->title }}</h2>

                <div class="property-location">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    <span>{{ $item->district ? $item->district . ', ' : '' }}{{ $item->province ?? 'Phnom Penh' }}</span>
                </div>

                <div class="property-specs">
                    <span class="spec-item">{{ $item->area_sqm ? number_format($item->area_sqm) . ' m²' : 'N/A' }}</span>
                    @if($item->bedrooms)
                        <span class="spec-item">{{ $item->bedrooms }} Beds</span>
                    @endif

                    @if($item->url)
                        <a href="{{ $item->url }}" target="_blank" rel="noopener" style="margin-left: auto; color: var(--primary); font-weight: 600; font-size: 0.8rem; text-decoration: underline;">
                            View Source &nearr;
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="grid-column: 1/-1;" class="card">
            <div class="card-body" style="text-align: center; padding: 4rem 2rem;">
                <h3 style="font-size: 1.15rem; font-weight: 700;">No urgent deals matched criteria</h3>
                <p style="color: var(--text-secondary); margin-top: 0.25rem;">Try selecting "All Provinces" or "All Types" to view available listings.</p>
            </div>
        </div>
    @endforelse
</div>

<div style="margin-top: 2rem;">
    {{ $deals->links() }}
</div>
@endsection
