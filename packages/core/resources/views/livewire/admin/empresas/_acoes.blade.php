@php
    /** @var \HT2ML\Core\Models\Empresa $row */
    $ator = auth('admin')->user();
@endphp

{{--
    Ações da linha (kebab). Itens condicionais a $verLixeira; @if(...) em volta
    (diretivas @can não compilam como atributo de componente Blade).
--}}
<x-admin.row-actions>
    @if ($ator?->can('view', $row))
        <x-shared.dropdown-item icon="tabler--eye" wire:click="$dispatch('empresas::ver', { id: {{ $row->id }} })">
            Ver
        </x-shared.dropdown-item>
    @endif

    @if (! $verLixeira)
        @if ($ator?->can('update', $row))
            <x-shared.dropdown-item
                icon="tabler--edit"
                :href="route('admin.empresas.edit', ['empresa' => $row->id])"
                wire:navigate
            >
                Editar
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('delete', $row))
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
