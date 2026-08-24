<div class="space-y-6">
    <x-admin.page-header title="Exemplos" subtitle="Cadastro de Exemplos.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.exemplos.create')" icon="tabler--plus" wire:navigate>
                    Novo registro
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:exemplos.exemplo-table />

    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('exemplo-demo::exemplos._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
</div>
