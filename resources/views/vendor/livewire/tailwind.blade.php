{{--
    Paginação Livewire (WithPagination) — tema Inspinia, espelhando
    resources/views/vendor/pagination/inspinia.blade.php (a view do paginador
    Laravel puro), porém com os controles disparando via wire:click/gotoPage
    (navegação SPA), em vez de <a href>. Faz override de `livewire::tailwind`
    (o loadViewsFrom do Livewire dá precedência a resources/views/vendor/livewire/),
    então TODA tela Livewire paginada com o tema default herda este visual/idioma
    automaticamente — sem precisar de paginationView() por componente.

    Acessível: role="navigation" + aria-label em PT-BR (CLAUDE.md §4),
    aria-current="page" na página atual, aria-label descritivo nos controles e
    setas decorativas (aria-hidden). Classes idênticas às da inspinia.blade.php,
    já presentes no bundle do tema.
--}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp

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
                <button
                    type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                    class="{{ $linkBase }} {{ $linkIdle }} rounded-lg"
                >{!! __('pagination.previous') !!}</button>
            @endif

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                    class="{{ $linkBase }} {{ $linkIdle }} rounded-lg"
                >{!! __('pagination.next') !!}</button>
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
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                        class="{{ $linkBase }} {{ $linkIdle }} rounded-l-lg"
                        aria-label="Página anterior"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

                {{-- Números e reticências --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $linkBase }} {{ $linkDisabled }}" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $linkBase }} {{ $linkCurrent }}" aria-current="page" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}">{{ $page }}</span>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                    class="{{ $linkBase }} {{ $linkIdle }}"
                                    aria-label="Ir para a página {{ $page }}"
                                >{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Próxima página --}}
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        wire:loading.attr="disabled"
                        dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                        class="{{ $linkBase }} {{ $linkIdle }} rounded-r-lg"
                        aria-label="Próxima página"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
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
