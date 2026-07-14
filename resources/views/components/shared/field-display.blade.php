@props (['label'])

{{-- Exibição read-only de um campo (rótulo + valor) — o tijolo das fichas de
     visualização (drawer "Ver" e telas de detalhe). Tokens do tema (dark via
     data-theme). Módulos-pacote que precisem de um equivalente próprio devem
     delegar aqui, para não divergir do padrão. --}}
<div>
    <p class="text-default-400 text-xs font-medium tracking-wide uppercase">{{ $label }}</p>
    <div class="text-body-color mt-0.5 text-sm">{{ $slot }}</div>
</div>
