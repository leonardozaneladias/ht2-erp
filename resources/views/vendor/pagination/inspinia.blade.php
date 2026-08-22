{{--
    Paginação padrão (LengthAwarePaginator) — tema Inspinia.
    Registrada como Paginator::defaultView no AppServiceProvider.
    Acessível: role="navigation", aria-current="page" na página atual,
    aria-label descritivo nos links e aria-disabled nos extremos. Setas são
    decorativas (aria-hidden); o nome acessível vem do aria-label do controle.
--}}
@if ($paginator->hasPages())
    @php
        $linkBase = 'inline-flex items-center justify-center px-3.5 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40';
        $linkIdle = 'text-body-color bg-card border border-default-300 hover:bg-light hover:text-primary';
        $linkDisabled = 'text-default-400 bg-light border border-default-300 cursor-not-allowed';
        $linkCurrent = 'text-white bg-primary border border-primary';
    @endphp
    <nav role="navigation" aria-label="Navegação de páginas" class="flex items-center justify-between gap-3">
        {{-- Mobile: anterior / próximo --}}
        <div class="flex flex-1 items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $linkBase }} {{ $linkDisabled }} rounded-lg" aria-disabled="true">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $linkBase }} {{ $linkIdle }} rounded-lg">{!! __('pagination.previous') !!}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $linkBase }} {{ $linkIdle }} rounded-lg">{!! __('pagination.next') !!}</a>
            @else
                <span class="{{ $linkBase }} {{ $linkDisabled }} rounded-lg" aria-disabled="true">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        {{-- Desktop: resumo + números de página --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-4">
            <p class="text-default-500 text-sm">
                Mostrando
                @if ($paginator->firstItem())
                    <span class="text-body-color font-semibold">{{ $paginator->firstItem() }}</span>
                    a
                    <span class="text-body-color font-semibold">{{ $paginator->lastItem() }}</span>
                @else
                    <span class="text-body-color font-semibold">{{ $paginator->count() }}</span>
                @endif
                de
                <span class="text-body-color font-semibold">{{ $paginator->total() }}</span>
                {{ $paginator->total() === 1 ? 'resultado' : 'resultados' }}
            </p>

            <span class="isolate inline-flex -space-x-px rounded-lg shadow-sm">
                {{-- Página anterior --}}
                @if ($paginator->onFirstPage())
                    <span class="{{ $linkBase }} {{ $linkDisabled }} rounded-l-lg" aria-disabled="true" aria-label="Página anterior">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $linkBase }} {{ $linkIdle }} rounded-l-lg" aria-label="Página anterior">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                {{-- Números e reticências --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $linkBase }} {{ $linkDisabled }}" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $linkBase }} {{ $linkCurrent }}" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $linkBase }} {{ $linkIdle }}" aria-label="Ir para a página {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Próxima página --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $linkBase }} {{ $linkIdle }} rounded-r-lg" aria-label="Próxima página">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span class="{{ $linkBase }} {{ $linkDisabled }} rounded-r-lg" aria-disabled="true" aria-label="Próxima página">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
