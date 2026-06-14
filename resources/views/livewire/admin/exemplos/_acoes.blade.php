@php
    /** @var \App\Models\Exemplo $row */
@endphp

<x-admin.row-actions>
    @can ('update', $row)
        <x-shared.dropdown-item
            icon="tabler--edit"
            :href="route('admin.exemplos.edit', ['exemplo' => $row->id])"
            wire:navigate
        >
            Editar
        </x-shared.dropdown-item>
    @endcan
</x-admin.row-actions>
