@php
    /** @var \App\Models\Referencia\Banco $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados do banco">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Nome completo">{{ $registro->nome_completo ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Código COMPE">{{ $registro->codigo_compe ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="ISPB">{{ $registro->ispb ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Situação">
            <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
            </x-shared.badge>
        </x-shared.field-display>
    </x-admin.ficha-section>
</div>
