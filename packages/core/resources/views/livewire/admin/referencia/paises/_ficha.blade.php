@php
    /** @var \HT2ML\Core\Models\Referencia\Pais $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do país">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Código ISO 2">{{ $registro->codigo_iso2 ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Código ISO 3">{{ $registro->codigo_iso3 ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Código numérico">
            {{ $registro->codigo_numerico ?: '—' }}</x-shared.field-display
        >
        <x-shared.field-display label="Situação">
            <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
            </x-shared.badge>
        </x-shared.field-display>
    </x-admin.ficha-section>
</div>
