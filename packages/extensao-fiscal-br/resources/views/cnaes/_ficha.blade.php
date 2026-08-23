@php
    /** @var \HT2ML\FiscalBr\Models\Cnae $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do CNAE">
        <x-shared.field-display label="Código">{{ $registro->codigo }}</x-shared.field-display>
        <x-shared.field-display label="Descrição">{{ $registro->descricao }}</x-shared.field-display>
        <x-shared.field-display label="Seção">{{ $registro->secao ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Divisão">{{ $registro->divisao ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Grupo">{{ $registro->grupo ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Classe">{{ $registro->classe ?: '—' }}</x-shared.field-display>
    </x-admin.ficha-section>
</div>
