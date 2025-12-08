@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center py-6">
        <div class="flex items-center gap-3">
            {{-- Previous Page Link --}}
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

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-12 h-12 text-gray-500 text-lg font-semibold">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" 
                                  class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-white font-bold shadow-lg text-base">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" 
                               class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border-2 border-gray-300 text-gray-700 hover:bg-primary hover:text-white hover:border-primary transition-all duration-200 shadow-sm font-semibold text-base"
                               aria-label="Go to page {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
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
        </div>
    </nav>

    {{-- Info text below pagination --}}
    <div class="text-center text-base text-gray-600 pb-4">
        Hiển thị <span class="font-semibold">{{ $paginator->firstItem() }}</span> đến 
        <span class="font-semibold">{{ $paginator->lastItem() }}</span> trong tổng số 
        <span class="font-semibold">{{ $paginator->total() }}</span> kết quả
    </div>
@endif
