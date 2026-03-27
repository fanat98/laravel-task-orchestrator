@if ($paginator->hasPages())
    <nav class="pagination-wrap" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-meta">
            Showing {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </div>

        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="pagination-link is-nav is-disabled" aria-disabled="true">&larr; Previous</span>
            @else
                <a class="pagination-link is-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-link is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-link is-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
            @else
                <span class="pagination-link is-nav is-disabled" aria-disabled="true">Next &rarr;</span>
            @endif
        </div>
    </nav>
@endif

