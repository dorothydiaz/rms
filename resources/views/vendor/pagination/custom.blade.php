@if ($paginator->total() > 0)
    <div class="hr-pagination-container">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info">
                Showing <strong>{{ $paginator->firstItem() ?? 1 }}</strong> to <strong>{{ $paginator->lastItem() ?? $paginator->total() }}</strong> of <strong>{{ $paginator->total() }}</strong> records
            </div>

            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" onchange="changeTablePerPage(this)" aria-label="Rows per page">
                    @php
                        $currPerPage = (int) $paginator->perPage();
                        if ($currPerPage < 10) $currPerPage = 10;
                        $perPageOptions = [10, 25, 50, 100];
                        if (!in_array($currPerPage, $perPageOptions)) {
                            $perPageOptions[] = $currPerPage;
                            sort($perPageOptions);
                        }
                    @endphp
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ $currPerPage === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>

        @if ($paginator->hasPages())
            <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="hr-page-btn disabled" aria-disabled="true">
                        <i class="ph ph-caret-left"></i>
                        <span>Prev</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="hr-page-btn">
                        <i class="ph ph-caret-left"></i>
                        <span>Prev</span>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                <div class="hr-page-numbers">
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span class="hr-page-num dots" aria-disabled="true">{{ $element }}</span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="hr-page-num active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="hr-page-num">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="hr-page-btn">
                        <span>Next</span>
                        <i class="ph ph-caret-right"></i>
                    </a>
                @else
                    <span class="hr-page-btn disabled" aria-disabled="true">
                        <span>Next</span>
                        <i class="ph ph-caret-right"></i>
                    </span>
                @endif
            </nav>
        @else
            <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation">
                <span class="hr-page-btn disabled" aria-disabled="true">
                    <i class="ph ph-caret-left"></i>
                    <span>Prev</span>
                </span>
                <div class="hr-page-numbers">
                    <span class="hr-page-num active" aria-current="page">1</span>
                </div>
                <span class="hr-page-btn disabled" aria-disabled="true">
                    <span>Next</span>
                    <i class="ph ph-caret-right"></i>
                </span>
            </nav>
        @endif
    </div>
@else
    <div class="hr-pagination-container">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info">
                Showing <strong>0</strong> to <strong>0</strong> of <strong>0</strong> records
            </div>

            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" onchange="changeTablePerPage(this)" aria-label="Rows per page">
                    @php
                        $currPerPage = (int) $paginator->perPage();
                        if ($currPerPage < 10) $currPerPage = 10;
                        $perPageOptions = [10, 25, 50, 100];
                        if (!in_array($currPerPage, $perPageOptions)) {
                            $perPageOptions[] = $currPerPage;
                            sort($perPageOptions);
                        }
                    @endphp
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" {{ $currPerPage === $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>

        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation">
            <span class="hr-page-btn disabled" aria-disabled="true">
                <i class="ph ph-caret-left"></i>
                <span>Prev</span>
            </span>
            <div class="hr-page-numbers">
                <span class="hr-page-num active" aria-current="page">1</span>
            </div>
            <span class="hr-page-btn disabled" aria-disabled="true">
                <span>Next</span>
                <i class="ph ph-caret-right"></i>
            </span>
        </nav>
    </div>
@endif

<script>
if (typeof window.changeTablePerPage === 'undefined') {
    window.changeTablePerPage = function(select) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', select.value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    };
}
</script>
