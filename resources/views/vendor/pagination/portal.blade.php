@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav role="navigation" aria-label="Pagination Navigation" class="portal-pagination-wrapper">
        {{-- Results Counter Summary --}}
        <div class="portal-pagination-info">
            <span>Showing</span>
            @if ($paginator->firstItem())
                <strong>{{ number_format($paginator->firstItem()) }}</strong>
                <span>to</span>
                <strong>{{ number_format($paginator->lastItem()) }}</strong>
            @else
                <strong>{{ number_format($paginator->count()) }}</strong>
            @endif
            <span>of</span>
            <strong>{{ number_format($paginator->total()) }}</strong>
            <span>results</span>
        </div>

        {{-- Page Navigation Links --}}
        @if ($paginator->hasPages())
            <ul class="portal-pagination-nav">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li>
                        <span class="portal-page-btn disabled" aria-disabled="true" title="Previous Page">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span style="margin-left: 4px;">Prev</span>
                        </span>
                    </li>
                @else
                    <li>
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="portal-page-btn" title="Previous Page">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span style="margin-left: 4px;">Prev</span>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li><span class="portal-page-btn dots">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li>
                                    <span class="portal-page-btn active" aria-current="page">{{ $page }}</span>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $url }}" class="portal-page-btn" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="portal-page-btn" title="Next Page">
                            <span style="margin-right: 4px;">Next</span>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                @else
                    <li>
                        <span class="portal-page-btn disabled" aria-disabled="true" title="Next Page">
                            <span style="margin-right: 4px;">Next</span>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </li>
                @endif
            </ul>
        @endif
    </nav>
@endif
