<x-admin.layout title="Livewire" subtitle="Componentes reativos com Livewire 4">
    <x-admin.page-header title="Livewire — Exemplos" subtitle="Componentes reativos server-side com Livewire 4" />

    <div class="row">
        <div class="col-lg-6">
            <livewire:admin.exemplo-counter />
        </div>
        <div class="col-lg-6">
            <x-shared.card title="Como usar">
                <p class="text-muted">Componentes Livewire ficam em <code>app/Livewire/Admin/</code></p>
                <p class="text-muted">Views em <code>resources/views/livewire/admin/</code></p>
                <p class="text-muted mb-0">Use com a tag <code>&lt;livewire:admin.exemplo-counter /&gt;</code></p>
            </x-shared.card>
        </div>
    </div>
</x-admin.layout>
