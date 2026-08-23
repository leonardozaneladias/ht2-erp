@php
    /** @var \HT2ML\Core\Models\AdminUser $row */
    $ator = auth('admin')->user();
    $ehDesativar = (bool) $row->ativo;
@endphp

{{--
    Ações da linha (kebab). Itens condicionais a $verLixeira (lista de ativos vs.
    lixeira). Dentro de atributos de componente Blade as DIRETIVAS (@js, @class)
    NÃO compilam — só o echo {{ }}. Por isso ações só-id usam {{ $row->id }}
    interpolado; o toggle de status monta o `confirm` no servidor.
--}}
<x-admin.row-actions>
    @if ($ator?->can('view', $row))
        <x-shared.dropdown-item icon="tabler--eye" wire:click="$dispatch('usuarios::ver', { id: {{ $row->id }} })">
            Ver
        </x-shared.dropdown-item>
    @endif

    @if (! $verLixeira)
        @if ($ator?->can('update', $row))
            <x-shared.dropdown-item
                icon="tabler--edit"
                :href="route('admin.usuarios.edit', ['usuario' => $row->id])"
                wire:navigate
            >
                Editar
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('toggleStatus', $row))
            <x-shared.dropdown-item
                :icon="$ehDesativar ? 'tabler--player-pause' : 'tabler--player-play'"
                wire:click="solicitarToggleStatus({{ $row->id }})"
            >
                {{ $ehDesativar ? 'Desativar' : 'Reativar' }}
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('impersonate', $row))
            <x-shared.dropdown-item
                icon="tabler--login-2"
                wire:click="$dispatch('impersonation::abrir', { id: {{ $row->id }} })"
            >
                Entrar como
            </x-shared.dropdown-item>
        @endif
        @if ($row->estaBloqueada() && $ator?->can('update', $row))
            <x-shared.dropdown-item
                icon="tabler--lock-open"
                wire:click="$dispatch('usuarios::desbloquear', { id: {{ $row->id }} })"
            >
                Desbloquear
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('exportarDados', $row))
            <x-shared.dropdown-divider />
            <x-shared.dropdown-item
                icon="tabler--file-code"
                :href="route('admin.usuarios.lgpd.json', ['usuario' => $row->id])"
                target="_blank"
            >
                Exportar JSON
            </x-shared.dropdown-item>
            <x-shared.dropdown-item
                icon="tabler--file-text"
                :href="route('admin.usuarios.lgpd.pdf', ['usuario' => $row->id])"
                target="_blank"
            >
                Exportar PDF
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('anonimizar', $row))
            <x-shared.dropdown-divider />
            <x-shared.dropdown-item
                icon="tabler--user-off"
                variant="danger"
                wire:click="$dispatch('lgpd::anonimizar', { id: {{ $row->id }} })"
            >
                Anonimizar
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('delete', $row) && $ator?->isNot($row))
            <x-shared.dropdown-divider />
            <x-shared.dropdown-item icon="tabler--trash" variant="danger" wire:click="solicitarExcluir({{ $row->id }})">
                Excluir
            </x-shared.dropdown-item>
        @endif
    @else
        @if ($ator?->can('restore', $row))
            <x-shared.dropdown-item icon="tabler--arrow-back-up" wire:click="solicitarRestaurar({{ $row->id }})">
                Restaurar
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('forceDelete', $row))
            <x-shared.dropdown-item
                icon="tabler--trash-x"
                variant="danger"
                wire:click="solicitarExcluirDefinitivo({{ $row->id }})"
            >
                Excluir definitivamente
            </x-shared.dropdown-item>
        @endif
    @endif
</x-admin.row-actions>
