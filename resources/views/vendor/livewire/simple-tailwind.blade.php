{{--
    Paginação Livewire simples (simplePaginate) — tema Inspinia, espelhando
    resources/views/vendor/pagination/simple-inspinia.blade.php, mas com os
    controles disparando via wire:click (navegação SPA) em vez de <a href>.
    Override de `livewire::simple-tailwind`. Acessível: role="navigation" +
    aria-label em PT-BR (CLAUDE.md §4) e aria-disabled nos extremos.
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
        $linkBase = 'inline-flex items-center justify-center px-3.5 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded-lg';
        $linkIdle = 'text-body-color bg-card border border-default-300 hover:bg-light hover:text-primary';
        $linkDisabled = 'text-default-400 bg-light border border-default-300 cursor-not-allowed';
    @endphp
    <nav role="navigation" aria-label="Navegação de páginas" class="flex items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span class="{{ $linkBase }} {{ $linkDisabled }}" aria-disabled="true">{!! __('pagination.previous') !!}</span>
        @else
            <button
                type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                class="{{ $linkBase }} {{ $linkIdle }}"
            >{!! __('pagination.previous') !!}</button>
        @endif

        @if ($paginator->hasMorePages())
            <button
                type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                class="{{ $linkBase }} {{ $linkIdle }}"
            >{!! __('pagination.next') !!}</button>
        @else
            <span class="{{ $linkBase }} {{ $linkDisabled }}" aria-disabled="true">{!! __('pagination.next') !!}</span>
        @endif
    </nav>
@endif
