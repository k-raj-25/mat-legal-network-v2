@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="btn-outline pointer-events-none opacity-40" aria-disabled="true"><x-icon name="chevron-left" class="size-4"/> Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-outline"><x-icon name="chevron-left" class="size-4"/> Previous</a>
        @endif

        <ol class="hidden items-center gap-1.5 md:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-1 text-slate-400">…</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex size-10 items-center justify-center rounded-full bg-ink text-sm font-bold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex size-10 items-center justify-center rounded-full text-sm font-bold text-slate-600 transition hover:bg-slate-100 hover:text-ink" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ol>
        <p class="text-sm font-semibold text-slate-500 md:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-outline">Next <x-icon name="chevron-right" class="size-4"/></a>
        @else
            <span class="btn-outline pointer-events-none opacity-40" aria-disabled="true">Next <x-icon name="chevron-right" class="size-4"/></span>
        @endif
    </nav>
@endif
