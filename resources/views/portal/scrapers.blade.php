@extends('layouts.portal')

@section('title', 'Scraper Hub')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Scraper Ingestion Hub</h1>
        <p class="page-subtitle">Configure real estate automated web scrapers, spiders, and scheduled extraction pipelines</p>
    </div>
    <div>
        @if(Auth::user()->hasPermission('scrapers.manage'))
            <button type="button" class="btn btn-primary" onclick="openModal('modal-new-scraper')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Create Scraper Pipeline</span>
            </button>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg style="width:20px;height:20px;color:var(--primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span>All Configured Scrapers ({{ $scrapers->count() }})</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pipeline Name</th>
                    <th>Source & URL</th>
                    <th>Category</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Extracted Records</th>
                    <th>Latest Execution Log</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($scrapers as $task)
                    <tr id="scraper-row-{{ $task->id }}">
                        <td>
                            <div style="font-weight: 700; color: var(--text-primary);">{{ $task->name }}</div>
                            <div style="font-size: 0.775rem; color: var(--text-muted);">Operator: {{ $task->user ? $task->user->name : 'System' }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $task->source_name }}</div>
                            <a href="{{ $task->target_url }}" target="_blank" rel="noopener" style="font-size: 0.775rem; color: var(--primary); text-decoration: underline;" title="{{ $task->target_url }}">
                                View Source Target &nearr;
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background: var(--bg-surface-elevated); color: var(--text-primary); border: 1px solid var(--border-color);">
                                {{ $task->category }}
                            </span>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            {{ $task->frequency }}
                        </td>
                        <td>
                            <span class="badge status-{{ $task->status }}">
                                <span class="badge-dot"></span>
                                {{ ucfirst($task->status) }}
                            </span>
                        </td>
                        <td>
                            <span class="scraped-count" style="font-weight: 800; font-size: 1.05rem; color: var(--text-primary);">{{ number_format($task->items_scraped) }}</span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">listings</span>
                        </td>
                        <td style="max-width: 260px;">
                            <div style="font-size: 0.775rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $task->last_log }}">
                                {{ $task->last_log ?? 'No execution logs recorded.' }}
                            </div>
                            <div class="last-run-time" style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                {{ $task->last_run_at ? 'Run ' . $task->last_run_at->diffForHumans() : 'Never dispatched' }}
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.5rem; align-items: center;">
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
                                        <span>Run</span>
                                    </button>
                                @endif

                                @if(Auth::user()->hasPermission('scrapers.manage'))
                                    <form action="{{ route('portal.scrapers.delete', $task->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Permanently delete scraper pipeline {{ $task->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Pipeline">
                                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if(!Auth::user()->hasPermission('scrapers.trigger') && !Auth::user()->hasPermission('scrapers.manage'))
                                    <span style="font-size: 0.775rem; color: var(--text-muted);">View Only</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            No scraper pipelines currently active.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
@endsection
