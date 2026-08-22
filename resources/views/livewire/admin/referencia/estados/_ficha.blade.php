@php
    /** @var \App\Models\Referencia\Estado $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do estado">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Sigla">{{ $registro->sigla }}</x-shared.field-display>
        <x-shared.field-display label="Código IBGE">{{ $registro->codigo_ibge }}</x-shared.field-display>
        <x-shared.field-display label="Região">{{ $registro->regiao ?: '—' }}</x-shared.field-display>
    </x-admin.ficha-section>
</div>
