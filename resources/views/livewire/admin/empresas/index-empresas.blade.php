<div class="space-y-6">
    <x-admin.page-header title="Empresas" subtitle="Cadastro de empresas e filiais do ambiente.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button :href="route('admin.empresas.create')" icon="tabler--plus" wire:navigate>
                    Nova empresa
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.empresas.empresas-table />

    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('livewire.admin.empresas._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
</div>
