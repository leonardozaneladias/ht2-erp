@php
    /**
     * Ações da linha (kebab) — view ÚNICA de todos os CRUDs padrão, entregue pelo
     * `actionsFromView()` do trait ComAcoesCrud.
     *
     * Substituiu 22 arquivos `_acoes.blade.php` que, normalizados, eram o MESMO arquivo:
     * variavam só no docblock do $row, no evento da ficha e na rota de edição.
     *
     * Os itens são condicionais a $verLixeira (ativos vs. lixeira) e guardados pela Policy
     * do model. @can NÃO compila como atributo de componente Blade — daí o @if em volta, e
     * o {{ $row->getKey() }} interpolado.
     *
     * @var \Illuminate\Database\Eloquent\Model $row
     * @var bool $verLixeira
     * @var string $eventoVer
     * @var string $rotaEditar
     */
    $ator = auth('admin')->user();
@endphp

<x-admin.row-actions>
    @if ($ator?->can('view', $row))
        <x-shared.dropdown-item
            icon="tabler--eye"
            wire:click="$dispatch('{{ $eventoVer }}', { id: {{ $row->getKey() }} })"
        >
            Ver
        </x-shared.dropdown-item>
    @endif

    @if (! $verLixeira)
        @if ($ator?->can('update', $row))
            <x-shared.dropdown-item icon="tabler--edit" :href="$rotaEditar" wire:navigate>
                Editar</x-shared.dropdown-item
            >
        @endif
        @if ($ator?->can('delete', $row))
            <x-shared.dropdown-item
                icon="tabler--trash"
                variant="danger"
                wire:click="solicitarExcluir({{ $row->getKey() }})"
            >
                Excluir
            </x-shared.dropdown-item>
        @endif
    @else
        @if ($ator?->can('restore', $row))
            <x-shared.dropdown-item icon="tabler--arrow-back-up" wire:click="solicitarRestaurar({{ $row->getKey() }})">
                Restaurar
            </x-shared.dropdown-item>
        @endif
        @if ($ator?->can('forceDelete', $row))
            <x-shared.dropdown-item
                icon="tabler--trash-x"
                variant="danger"
                wire:click="solicitarExcluirDefinitivo({{ $row->getKey() }})"
            >
                Excluir definitivamente
            </x-shared.dropdown-item>
        @endif
    @endif
</x-admin.row-actions>
