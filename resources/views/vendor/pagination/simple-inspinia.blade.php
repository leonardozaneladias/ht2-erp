{{--
    Paginação simples (Paginator / simplePaginate) — tema Inspinia.
    Registrada como Paginator::defaultSimpleView no AppServiceProvider.
    Acessível: role="navigation" e aria-disabled nos extremos.
--}}
@if ($paginator->hasPages())
    @php
        $linkBase = 'inline-flex items-center justify-center px-3.5 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded-lg';
        $linkIdle = 'text-body-color bg-card border border-default-300 hover:bg-light hover:text-primary';
        $linkDisabled = 'text-default-400 bg-light border border-default-300 cursor-not-allowed';
    @endphp
    <nav role="navigation" aria-label="Navegação de páginas" class="flex items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span class="{{ $linkBase }} {{ $linkDisabled }}" aria-disabled="true">{!! __('pagination.previous') !!}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $linkBase }} {{ $linkIdle }}">{!! __('pagination.previous') !!}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $linkBase }} {{ $linkIdle }}">{!! __('pagination.next') !!}</a>
        @else
            <span class="{{ $linkBase }} {{ $linkDisabled }}" aria-disabled="true">{!! __('pagination.next') !!}</span>
        @endif
    </nav>
@endif
