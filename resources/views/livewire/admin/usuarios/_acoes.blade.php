@php
    /** @var \App\Models\AdminUser $row */
    $ator = auth('admin')->user();
    $ehDesativar = (bool) $row->ativo;

    // Payload da confirmacao tematica (bridge SweetAlert2 em admin/confirm.js):
    // ao confirmar, dispara o evento `onConfirm` com os `params` para o #[On].
    $confirmToggle = [
        'title' => $ehDesativar ? 'Desativar usuário?' : 'Reativar usuário?',
        'text' => $ehDesativar
            ? 'O usuário perderá o acesso ao sistema enquanto estiver inativo.'
            : 'O usuário poderá acessar o sistema novamente.',
        'onConfirm' => 'usuarios::toggle-status',
        'params' => ['id' => $row->id],
    ];
@endphp

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
            wire:click="$dispatch('confirm', @js($confirmToggle))"
        >
            {{ $ehDesativar ? 'Desativar' : 'Reativar' }}
        </x-shared.dropdown-item>
    @endif

    @if ($ator?->can('impersonate', $row))
        <x-shared.dropdown-item
            icon="tabler--login-2"
            wire:click="$dispatch('impersonation::abrir', @js(['id' => $row->id]))"
        >
            Entrar como
        </x-shared.dropdown-item>
    @endif

    @if ($row->estaBloqueada() && $ator?->can('update', $row))
        <x-shared.dropdown-item
            icon="tabler--lock-open"
            wire:click="$dispatch('usuarios::desbloquear', @js(['id' => $row->id]))"
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
            wire:click="$dispatch('lgpd::anonimizar', @js(['id' => $row->id]))"
        >
            Anonimizar
        </x-shared.dropdown-item>
    @endif
</x-admin.row-actions>
