@php
    /** @var \HT2ML\Core\Models\Referencia\TipoLogradouro $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do tipo de logradouro">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Código">{{ $registro->codigo ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Abreviação">{{ $registro->abrev ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Situação">
            <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
            </x-shared.badge>
        </x-shared.field-display>
    </x-admin.ficha-section>
</div>
