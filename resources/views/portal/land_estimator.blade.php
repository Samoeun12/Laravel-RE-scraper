@extends('layouts.portal')

@section('title', 'Land Price Estimator & Index')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Cambodia Land Price Estimator</h1>
        <p class="page-subtitle">Algorithmic land valuation engine and comprehensive district land price index</p>
    </div>
</div>

<!-- Calculator Form -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div class="card-title">
            <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <span>Land Parcel Parameters</span>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('portal.land_estimator') }}" method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Land Parcel Area (sqm)</label>
                    <input type="number" step="10" name="area_sqm" value="{{ $area }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Province</label>
                    <select name="province" class="form-control">
                        <option value="Phnom Penh" {{ $province == 'Phnom Penh' ? 'selected' : '' }}>Phnom Penh</option>
                        <option value="Kandal" {{ $province == 'Kandal' ? 'selected' : '' }}>Kandal</option>
                        <option value="Siem Reap" {{ $province == 'Siem Reap' ? 'selected' : '' }}>Siem Reap</option>
                        <option value="Sihanoukville" {{ $province == 'Sihanoukville' ? 'selected' : '' }}>Sihanoukville</option>
                        <option value="Kampot" {{ $province == 'Kampot' ? 'selected' : '' }}>Kampot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">District / Khan</label>
                    <select name="district" class="form-control">
                        @foreach($districtStats as $st)
                            <option value="{{ $st->district }}" {{ $district == $st->district ? 'selected' : '' }}>
                                {{ $st->district }} (Avg: ${{ number_format($st->avg_sqm, 0) }}/m²)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Road Frontage / Access Type</label>
                    <select name="road_type" class="form-control">
                        <option value="Main Boulevard" {{ $roadType == 'Main Boulevard' ? 'selected' : '' }}>Main Boulevard (+25% premium)</option>
                        <option value="Secondary Road" {{ $roadType == 'Secondary Road' ? 'selected' : '' }}>Secondary Road (100% baseline)</option>
                        <option value="Residential Lane" {{ $roadType == 'Residential Lane' ? 'selected' : '' }}>Residential Lane / Alley (-15% discount)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                <span>Calculate Land Valuation</span>
            </button>
        </form>
    </div>
</div>

@if($estimate)
    <!-- Valuation Result Banner -->
    <div class="card" style="margin-bottom: 2.5rem; background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-surface-elevated) 100%); border: 2px solid var(--primary); box-shadow: var(--shadow-glow);">
        <div class="card-body" style="padding: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div>
                <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--primary);">
                    Valuation Estimate &bull; {{ number_format($area) }} m² in {{ $district }}, {{ $province }}
                </div>
                <div style="font-size: 3rem; font-weight: 800; line-height: 1.1; margin: 0.5rem 0;" class="gradient-text">
                    ${{ number_format($estimate['total_valuation']) }}
                </div>
                <div style="font-size: 0.95rem; color: var(--text-secondary);">
                    Adjusted Price: <strong style="color:var(--text-primary);">${{ number_format($estimate['adjusted_sqm'], 0) }}/m²</strong> (Road factor: {{ $estimate['road_type'] }})
                </div>
            </div>

            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <div style="padding: 1rem 1.5rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">DISTRICT MIN</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">${{ number_format($estimate['min_sqm'], 0) }}/m²</div>
                </div>

                <div style="padding: 1rem 1.5rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">DISTRICT MAX</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">${{ number_format($estimate['max_sqm'], 0) }}/m²</div>
                </div>

                <div style="padding: 1rem 1.5rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">SAMPLE COMPS</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">{{ number_format($estimate['sample_size']) }}</div>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- District Land Price Index Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <span>Cambodia District Land Price Index ({{ $province }})</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>District / Khan</th>
                    <th>Minimum $/m²</th>
                    <th>Average Benchmark $/m²</th>
                    <th>Maximum $/m²</th>
                    <th>Scraped Land Listings</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($districtStats as $st)
                    <tr>
                        <td style="font-weight: 700; color: var(--text-primary);">
                            {{ $st->district }}
                        </td>
                        <td>${{ number_format($st->min_sqm, 0) }}</td>
                        <td>
                            <strong style="color: var(--primary); font-size: 1rem;">
                                ${{ number_format($st->avg_sqm, 0) }}/m²
                            </strong>
                        </td>
                        <td>${{ number_format($st->max_sqm, 0) }}</td>
                        <td>
                            <span class="badge" style="background: var(--bg-surface-elevated); color: var(--text-primary);">
                                {{ number_format($st->total) }} records
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('portal.properties') }}?type=Land&search={{ urlencode($st->district) }}" class="btn btn-secondary btn-sm">
                                View Land Listings &rarr;
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
