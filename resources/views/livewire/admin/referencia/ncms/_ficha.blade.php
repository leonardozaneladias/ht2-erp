@php
    /** @var \App\Models\Referencia\Ncm $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do NCM">
        <x-shared.field-display label="Código">{{ $registro->codigo }}</x-shared.field-display>
        <x-shared.field-display label="Descrição">{{ $registro->descricao }}</x-shared.field-display>
    </x-admin.ficha-section>
</div>
