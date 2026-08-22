<div class="space-y-6">
    <x-admin.page-header title="Tipos de logradouro" subtitle="Tipos de logradouro — dado de referência.">
        <x-slot:actions>
            @if ($podeCriar)
                <x-shared.button
                    :href="route('admin.referencia.tipos_logradouro.create')"
                    icon="tabler--plus"
                    wire:navigate
                >
                    Novo tipo de logradouro
                </x-shared.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <livewire:admin.referencia.tipo-logradouro-table />

    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('livewire.admin.referencia.tipos_logradouro._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
</div>
