@php
    /** @var \HT2ML\Core\Models\Referencia\Cargo $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do cargo">
        <x-shared.field-display label="Descrição">{{ $registro->descricao }}</x-shared.field-display>
        <x-shared.field-display label="Código CBO">{{ $registro->codigo_cbo ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Situação">
            <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
            </x-shared.badge>
        </x-shared.field-display>
    </x-admin.ficha-section>
</div>
