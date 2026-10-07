@extends('layouts.portal')

@section('title', 'Comparable Market Analysis (CMA)')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Comparable Market Analysis (CMA) Engine</h1>
        <p class="page-subtitle">Algorithmic property valuation and fair-market appraisal using real Cambodian comps</p>
    </div>
</div>

<!-- Input Parameters Card -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div class="card-title">
            <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span>Subject Property Specifications</span>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('portal.cma') }}" method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Subject Area (sqm)</label>
                    <input type="number" step="1" name="area_sqm" value="{{ $area }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Province / City</label>
                    <select name="province" class="form-control">
                        <option value="Phnom Penh" {{ $province == 'Phnom Penh' ? 'selected' : '' }}>Phnom Penh</option>
                        <option value="Siem Reap" {{ $province == 'Siem Reap' ? 'selected' : '' }}>Siem Reap</option>
                        <option value="Kandal" {{ $province == 'Kandal' ? 'selected' : '' }}>Kandal</option>
                        <option value="Sihanoukville" {{ $province == 'Sihanoukville' ? 'selected' : '' }}>Sihanoukville</option>
                        <option value="Kampot" {{ $province == 'Kampot' ? 'selected' : '' }}>Kampot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">District / Khan</label>
                    <input type="text" name="district" value="{{ $district }}" class="form-control" placeholder="e.g. Sen Sok, Chbar Ampov, Toul Kork">
                </div>

                <div class="form-group">
                    <label class="form-label">Property Type</label>
                    <select name="property_type" class="form-control">
                        <option value="Land" {{ $propType == 'Land' ? 'selected' : '' }}>Land</option>
                        <option value="Villa" {{ $propType == 'Villa' ? 'selected' : '' }}>Villa</option>
                        <option value="House" {{ $propType == 'House' ? 'selected' : '' }}>House / Shophouse</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Asking Price ($ USD, Optional)</label>
                    <input type="number" step="100" name="target_price" value="{{ $targetPrice ?: '' }}" class="form-control" placeholder="e.g. 450000">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Calculate Automated Valuation Model (AVM)</span>
            </button>
        </form>
    </div>
</div>

@if($report)
    <!-- Valuation Result Tier Cards -->
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1rem;">
            Estimated Valuation: {{ number_format($area) }} m² {{ $propType }} in {{ $district }}, {{ $province }}
        </h2>

        <div class="stats-grid">
            <!-- Conservative -->
            <div class="stat-card" style="border-top: 4px solid var(--info);">
                <div class="stat-label" style="text-transform: uppercase; font-weight: 700; color: var(--info);">Conservative (25th Percentile)</div>
                <div class="stat-value" style="font-size: 2.2rem; margin: 0.5rem 0;">${{ number_format($report['val_conservative']) }}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">${{ number_format($report['val_conservative'] / $area, 0) }}/m² benchmark</div>
            </div>

            <!-- Fair Market Value -->
            <div class="stat-card" style="border: 2px solid var(--primary); box-shadow: var(--shadow-glow);">
                <div class="stat-label" style="text-transform: uppercase; font-weight: 800; color: var(--primary);">Fair Market Value (Median)</div>
                <div class="stat-value gradient-text" style="font-size: 2.4rem; margin: 0.5rem 0;">${{ number_format($report['val_fair']) }}</div>
                <div style="font-size: 0.825rem; color: var(--text-primary); font-weight: 600;">${{ number_format($report['median_sqm'], 0) }}/m² benchmark</div>
            </div>

            <!-- Premium -->
            <div class="stat-card" style="border-top: 4px solid var(--success);">
                <div class="stat-label" style="text-transform: uppercase; font-weight: 700; color: var(--success);">Premium (75th Percentile)</div>
                <div class="stat-value" style="font-size: 2.2rem; margin: 0.5rem 0;">${{ number_format($report['val_premium']) }}</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">${{ number_format($report['val_premium'] / $area, 0) }}/m² benchmark</div>
            </div>
        </div>

        @if(isset($report['asking_diff_pct']))
            <div style="padding: 1.25rem; border-radius: var(--radius-md); background: var(--bg-surface-elevated); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                <div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Asking Price Comparison:</div>
                    <div style="font-size: 1.15rem; font-weight: 700;">${{ number_format($targetPrice) }} vs Fair Market ${{ number_format($report['val_fair']) }}</div>
                </div>
                <div>
                    <span class="badge" style="font-size: 0.9rem; padding: 6px 14px; {{ $report['asking_diff_pct'] > 10 ? 'background:var(--danger-bg);color:var(--danger);' : ($report['asking_diff_pct'] < -10 ? 'background:var(--success-bg);color:var(--success);' : 'background:var(--info-bg);color:var(--info);') }}">
                        {{ $report['status_evaluation'] }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Comps Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Top Comparable Listings Analyzed ({{ $report['count'] }} Comps)</span>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Min: ${{ number_format($report['min_sqm']) }}/m² &bull; Avg: ${{ number_format($report['avg_sqm']) }}/m² &bull; Max: ${{ number_format($report['max_sqm']) }}/m²
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Comparable Property</th>
                        <th>District / Location</th>
                        <th>Price (USD)</th>
                        <th>Area</th>
                        <th>$/m²</th>
                        <th>Source Portal</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['comps'] as $comp)
                        <tr>
                            <td>
                                <div style="font-weight: 600;">{{ $comp->title }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $comp->property_type }}</div>
                            </td>
                            <td>{{ $comp->district }}, {{ $comp->province }}</td>
                            <td style="font-weight: 700;">{{ $comp->formatted_price }}</td>
                            <td>{{ number_format($comp->area_sqm) }} m²</td>
                            <td style="font-weight: 700; color: var(--primary);">${{ number_format($comp->price_per_sqm, 0) }}/m²</td>
                            <td>{{ $comp->source_name }}</td>
                            <td style="text-align: right;">
                                @if($comp->url)
                                    <a href="{{ $comp->url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                        View Listing &nearr;
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif($area > 0)
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <p style="color: var(--text-muted); font-size: 1rem;">
                Not enough comparable listings found in <strong>{{ $district }}, {{ $province }}</strong> matching <strong>{{ $propType }} (±30% size)</strong>.
            </p>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                Try testing with a broader district or adjust the subject area.
            </p>
        </div>
    </div>
@endif
@endsection
