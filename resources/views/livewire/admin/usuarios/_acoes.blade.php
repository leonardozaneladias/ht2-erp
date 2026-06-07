@php
    /** @var \App\Models\AdminUser $row */
    $ator = auth('admin')->user();
    $ehDesativar = (bool) $row->ativo;
@endphp

{{--
    Ações da linha (kebab). Atenção: dentro de atributos de componente Blade as
    DIRETIVAS (@js, @class, ...) NÃO compilam — só o echo {{ }} compila. Por isso:
    - ações só-id usam $dispatch('evento', { id: {{ $row->id }} }) inline;
    - o toggle (que precisa de título/texto) chama um método no servidor, que
      monta e dispara o `confirm` temático (sem montar JSON no atributo).
--}}
<x-admin.row-actions>
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
</x-admin.row-actions>
