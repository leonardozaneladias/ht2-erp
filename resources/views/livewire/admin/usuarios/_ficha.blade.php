@php
    /** @var \App\Models\AdminUser $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Identificação">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="E-mail">{{ $registro->email }}</x-shared.field-display>
        <x-shared.field-display label="Cargo">{{ $registro->cargo ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Telefone">{{ $registro->telefone ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Situação">
            @if ($registro->estaBloqueada())
                <x-shared.badge variant="danger" pill size="sm">Bloqueado</x-shared.badge>
            @else
                <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                    {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
                </x-shared.badge>
            @endif
        </x-shared.field-display>
    </x-admin.ficha-section>
    <x-admin.ficha-section title="Acesso">
        <x-shared.field-display label="Perfis">
            <span class="flex flex-wrap gap-1.5">
                @forelse ($registro->roles as $papel)
                    <x-shared.badge variant="primary" size="sm" pill>{{ $papel->name }}</x-shared.badge>
                @empty
                    —
                @endforelse
            </span>
        </x-shared.field-display>
        <x-shared.field-display label="Empresas vinculadas">
            <span class="flex flex-wrap gap-1.5">
                @forelse ($registro->empresasAcessiveis as $empresa)
                    <x-shared.badge variant="default" size="sm" pill>{{ $empresa->nome }}</x-shared.badge>
                @empty
                    —
                @endforelse
            </span>
        </x-shared.field-display>
    </x-admin.ficha-section>
    <x-admin.ficha-section title="Preferências">
        <x-shared.field-display label="Idioma">{{ $registro->locale ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Fuso horário">{{ $registro->timezone ?: '—' }}</x-shared.field-display>
    </x-admin.ficha-section>
</div>
