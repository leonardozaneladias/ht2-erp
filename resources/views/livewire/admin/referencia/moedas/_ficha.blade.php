@php
    /** @var \App\Models\Referencia\Moeda $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados da moeda">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Código ISO">{{ $registro->codigo_iso }}</x-shared.field-display>
        <x-shared.field-display label="Numérico">{{ $registro->numerico ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Símbolo">{{ $registro->simbolo ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Casas decimais">{{ $registro->casas_decimais }}</x-shared.field-display>
        <x-shared.field-display label="Situação">
            <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
            </x-shared.badge>
        </x-shared.field-display>
    </x-admin.ficha-section>
</div>
