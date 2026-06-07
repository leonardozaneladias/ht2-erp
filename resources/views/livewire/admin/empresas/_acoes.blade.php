@php
    /** @var \App\Models\Empresa $row */
    $ator = auth('admin')->user();
@endphp

<x-admin.row-actions>
    @if ($ator?->can('update', $row))
        <x-shared.dropdown-item
            icon="tabler--edit"
            :href="route('admin.empresas.edit', ['empresa' => $row->id])"
            wire:navigate
        >
            Editar
        </x-shared.dropdown-item>
    @endif
</x-admin.row-actions>
