@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center gap-4 py-6">
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="fas fa-chevron-left text-base"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" 
               class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border-2 border-gray-300 text-gray-700 hover:bg-primary hover:text-white hover:border-primary transition-all duration-200 shadow-sm">
                <i class="fas fa-chevron-left text-base"></i>
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" 
               class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border-2 border-gray-300 text-gray-700 hover:bg-primary hover:text-white hover:border-primary transition-all duration-200 shadow-sm">
                <i class="fas fa-chevron-right text-base"></i>
            </a>
        @else
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="fas fa-chevron-right text-base"></i>
            </span>
        @endif
    </nav>
@endif
