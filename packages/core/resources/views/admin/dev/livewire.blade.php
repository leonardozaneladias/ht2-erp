<x-admin.layout title="Livewire" subtitle="Componentes reativos com Livewire 4">
    <x-admin.page-header title="Livewire — Exemplos" subtitle="Componentes reativos server-side com Livewire 4" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <livewire:admin.exemplo-counter />

        <x-shared.card title="Como usar">
            <p class="text-default-500">Componentes Livewire ficam em <code>app/Livewire/Admin/</code></p>
            <p class="text-default-500">Views em <code>resources/views/livewire/admin/</code></p>
            <p class="text-default-500 mb-0">Use com a tag <code>&lt;livewire:admin.exemplo-counter /&gt;</code></p>
        </x-shared.card>
    </div>
</x-admin.layout>
