@php
    /** @var \HT2ML\Core\Models\Referencia\Municipio $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do município">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Código IBGE">{{ $registro->codigo_ibge }}</x-shared.field-display>
        <x-shared.field-display label="Estado">
            {{ $registro->estado ? $registro->estado->nome . ' (' . $registro->estado->sigla . ')' : '—' }}</x-shared.field-display
        >
    </x-admin.ficha-section>
</div>
