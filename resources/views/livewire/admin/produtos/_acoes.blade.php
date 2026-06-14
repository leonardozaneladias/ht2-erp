@php
    /** @var \App\Models\Produto $row */
@endphp

<x-admin.row-actions>
    @can ('update', $row)
        <x-shared.dropdown-item
            icon="tabler--edit"
            :href="route('admin.produtos.edit', ['produto' => $row->id])"
            wire:navigate
        >
            Editar
        </x-shared.dropdown-item>
    @endcan
</x-admin.row-actions>
