@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="portal-pagination-wrapper">
        <div class="portal-pagination-info">
            <span>Page</span>
            <strong>{{ number_format($paginator->currentPage()) }}</strong>
        </div>

        <ul class="portal-pagination-nav">
            @if ($paginator->onFirstPage())
                <li>
                    <span class="portal-page-btn disabled" aria-disabled="true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span style="margin-left: 4px;">Prev</span>
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="portal-page-btn">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span style="margin-left: 4px;">Prev</span>
                    </a>
                </li>
            @endif

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="portal-page-btn">
                        <span style="margin-right: 4px;">Next</span>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </li>
            @else
                <li>
                    <span class="portal-page-btn disabled" aria-disabled="true">
                        <span style="margin-right: 4px;">Next</span>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
